<?php
/**
 * ترانه یار ۳.۵.۲ — فرم‌های پیشخوانِ هنرمند و دسته‌بندی
 * ==========================================================================
 * چه چیزی را برمی‌گرداند؟
 *   نسخهٔ مبنا (۳.۴.۸) در صفحهٔ «افزودن/ویرایش هنرمند» هیچ فیلدی نداشت؛ مقدارها
 *   فقط از طریق گروه فیلدِ ACF (که روی این سرور نیست/حذف شده) قابل ثبت بودند.
 *   نتیجه: تصویر پروفایل، شبکه‌های اجتماعی، نام لاتین و بیوگرافی هیچ راهی برای
 *   ثبت از پیشخوان نداشتند. این فایل همان فیلدها را با **همان کلیدهای متا**
 *   برمی‌گرداند تا داده‌های قدیمی بدون مهاجرت خوانده و ویرایش شوند.
 *
 *   برای دسته‌بندی‌ها هم دو فیلد تازه می‌آورد: توضیح کوتاه (بالای آرشیو) و
 *   توضیح کامل (پایین آرشیو، با «نمایش بیشتر»).
 *
 * قواعد امنیت:
 *   • هر فرم یک nonce دارد (tara_term_fields).
 *   • ذخیره فقط با تواناییِ ویرایشِ همان تاکسونومی (cap->edit_terms) و
 *     در نبودِ آن manage_categories.
 *   • هر مقدار بر اساس نوعش پاک‌سازی می‌شود (esc_url_raw / absint /
 *     sanitize_text_field / wp_kses_post).
 *   • AJAX جست‌وجو: nonce + edit_posts + سقف ۲۰ نتیجه + حداقل ۲ نویسه.
 *
 * تداخل با ACF:
 *   اگر روی همین سرور گروه فیلدِ ACF برای این تاکسونومی وجود داشته باشد
 *   (یعنی فیلدها در پیشخوان دیده می‌شوند)، فرمِ ما چاپ نمی‌شود تا فیلد تکراری
 *   ساخته نشود؛ ولی خواندن/ذخیرهٔ مقدارها همچنان از همان کلیدها انجام می‌شود.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==================================================================================
   ۱) تشخیص مدیریتِ ACF (برای جلوگیری از فیلد تکراری)
   ================================================================================== */

if ( ! function_exists( 'tara_acf_manages_fields' ) ) {
	/**
	 * آیا ACF برای این تاکسونومی/کلیدها گروه فیلد دارد؟
	 * فقط خواندن است؛ اگر API نبود false ⇒ فرمِ خودمان چاپ می‌شود.
	 */
	function tara_acf_manages_fields( $taxonomy, $keys = array( 'aimg2' ) ) {
		static $cache = array();
		$ckey = $taxonomy . '|' . implode( ',', (array) $keys );
		if ( isset( $cache[ $ckey ] ) ) {
			return $cache[ $ckey ];
		}
		$managed = false;
		if ( function_exists( 'acf_get_field_groups' ) && function_exists( 'acf_get_fields' ) ) {
			$groups = acf_get_field_groups( array( 'taxonomy' => $taxonomy ) );
			if ( is_array( $groups ) ) {
				foreach ( $groups as $group ) {
					if ( empty( $group['ID'] ) ) {
						continue;
					}
					$fields = acf_get_fields( $group );
					if ( ! is_array( $fields ) ) {
						continue;
					}
					foreach ( $fields as $field ) {
						if ( isset( $field['name'] ) && in_array( $field['name'], (array) $keys, true ) ) {
							$managed = true;
							break 2;
						}
					}
				}
			}
		}
		$cache[ $ckey ] = $managed;
		return $managed;
	}
}

/* ==================================================================================
   ۲) ثبت term meta (برای REST و پاک‌سازیِ استاندارد)
   ================================================================================== */

if ( ! function_exists( 'tara_register_term_meta' ) ) {
	function tara_register_term_meta() {
		if ( ! function_exists( 'register_term_meta' ) ) {
			return;
		}
		$defs = tara_artist_field_keys();
		$taxes = array( 'artist', 'singer' );
		if ( function_exists( 'tara_artist_tax' ) ) {
			$taxes[] = tara_artist_tax();
		}
		foreach ( array_unique( $taxes ) as $tax ) {
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}
			foreach ( $defs as $key => $def ) {
				register_term_meta(
					$tax,
					$key,
					array(
						'type'              => 'string',
						'single'            => true,
						'show_in_rest'      => true,
						'sanitize_callback' => function ( $value ) use ( $def ) {
							switch ( $def['type'] ) {
								case 'url':
									return esc_url_raw( (string) $value );
								case 'id':
									return (string) absint( $value );
								case 'textarea':
									return trim( wp_strip_all_tags( (string) $value ) );
								default:
									return trim( wp_strip_all_tags( (string) $value ) );
							}
						},
					)
				);
			}
		}

		/* توضیح کوتاه/کامل دسته‌بندی */
		foreach ( array( 'ab_desc_short', 'ab_desc_full' ) as $key ) {
			register_term_meta(
				'category',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => ( 'ab_desc_full' === $key )
						? function ( $value ) {
							return wp_kses_post( (string) $value );
						}
						: function ( $value ) {
							return trim( wp_strip_all_tags( (string) $value ) );
						},
				)
			);
		}
	}
	add_action( 'init', 'tara_register_term_meta', 20 );
}

/* ==================================================================================
   ۳) قطعه‌های رابط مشترک
   ================================================================================== */

if ( ! function_exists( 'tara_field_image_control' ) ) {
	/** کنترل تصویر با پیش‌نمایش + کتابخانهٔ رسانه (بدون ساخت سیستم آپلود موازی). */
	function tara_field_image_control( $url, $id, $name_url, $name_id, $label = 'تصویر پروفایل', $description = '' ) {
		$has = ( '' !== $url );
		?>
		<div class="ab-imgfield" data-ab-image>
			<span class="ab-imgfield__preview" data-ab-image-preview<?php echo $has ? '' : ' hidden'; ?>>
				<img src="<?php echo esc_url( $has ? $url : '' ); ?>" alt="" width="120" height="120">
			</span>
			<span class="ab-imgfield__empty" data-ab-image-empty<?php echo $has ? ' hidden' : ''; ?>>
				<em>تصویری ثبت نشده است.</em>
			</span>
			<input type="hidden" name="<?php echo esc_attr( $name_url ); ?>" value="<?php echo esc_attr( $url ); ?>" data-ab-image-url>
			<input type="hidden" name="<?php echo esc_attr( $name_id ); ?>" value="<?php echo esc_attr( $id ? $id : '' ); ?>" data-ab-image-id>
			<span class="ab-imgfield__actions">
				<button type="button" class="button" data-ab-media><?php echo esc_html( $has ? 'تغییر تصویر' : 'انتخاب از کتابخانه' ); ?></button>
				<button type="button" class="button-link ab-imgfield__clear" data-ab-media-clear<?php echo $has ? '' : ' hidden'; ?>>حذف تصویر</button>
			</span>
			<p class="description">
				<?php
				if ( '' !== $description ) {
					echo wp_kses_post( $description );
				} else {
					?>
				همان تصویری که در صفحهٔ اصلی، آرشیو هنرمندان و صفحهٔ هنرمند دیده می‌شود.
				اگر برای همین هنرمند قبلاً تصویر ثبت شده باشد، همین‌جا نمایش داده می‌شود و
				نیازی به بارگذاری دوباره نیست.
				<?php } ?>
			</p>
		</div>
		<?php
	}
}

if ( ! function_exists( 'tara_field_social_controls' ) ) {
	function tara_field_social_controls( $term = null ) {
		foreach ( tara_artist_social_keys() as $key => $meta ) {
			$value = $term ? tara_artist_field( $term, $key ) : '';
			?>
			<p class="ab-social-field">
				<label for="ab-<?php echo esc_attr( $key ); ?>"><i class="<?php echo esc_attr( $meta['icon'] ); ?>" aria-hidden="true"></i> <?php echo esc_html( $meta['label'] ); ?></label>
				<input type="url" id="ab-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"
					value="<?php echo esc_attr( $value ); ?>" class="widefat" dir="ltr"
					placeholder="https://" inputmode="url">
			</p>
			<?php
		}
	}
}

if ( ! function_exists( 'tara_field_post_picker' ) ) {
	/**
	 * جست‌وجو و انتخاب یک نوشته (برای «بیوگرافی کامل»).
	 * ورودیِ مخفی، شناسه را نگه می‌دارد؛ نتایج با AJAX می‌آید (بدون لیستِ کاملِ نوشته‌ها).
	 */
	function tara_field_post_picker( $value_id, $name = 'ab_bio_post', $label = 'نوشتهٔ بیوگرافی کامل' ) {
		$value_id = absint( $value_id );
		$title    = '';
		if ( $value_id ) {
			$post = get_post( $value_id );
			if ( $post ) {
				$title = get_the_title( $post ) . ' — ' . ( 'publish' === $post->post_status ? 'منتشرشده' : $post->post_status );
			}
		}
		?>
		<div class="ab-picker" data-ab-picker data-type="post" data-mode="single">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value_id ? $value_id : '' ); ?>" data-ab-picker-value>
			<div class="ab-picker__selected" data-ab-picker-selected<?php echo $value_id ? '' : ' hidden'; ?>>
				<span data-ab-picker-title><?php echo esc_html( $title ); ?></span>
				<button type="button" class="button-link" data-ab-picker-clear>برداشتن</button>
			</div>
			<input type="search" class="regular-text" data-ab-picker-input
				placeholder="جست‌وجوی عنوان نوشته…" autocomplete="off"
				aria-label="<?php echo esc_attr( $label ); ?>">
			<div class="ab-picker__results" data-ab-picker-results hidden></div>
			<p class="description">
				اگر نوشته‌ای انتخاب شود، در صفحهٔ هنرمند دکمهٔ «بیوگرافی کامل» فقط وقتی نمایش داده می‌شود
				که آن نوشته منتشر شده باشد (یا کاربرِ فعلی اجازهٔ خواندنش را داشته باشد).
			</p>
		</div>
		<?php
	}
}

if ( ! function_exists( 'tara_artist_banner_group' ) ) {
	/**
	 * گروه فیلد «بنر سربرگ» — v3.7.8
	 * ------------------------------------------------------------------
	 * جدا شده تا حتی وقتی ACF فیلدهای هنرمند را مدیریت می‌کند (و فرمِ ما
	 * پنهان است) هم مدیر بتواند بنر را از همین صفحهٔ ترم ثبت کند.
	 * سایز استاندارد ۱۲۰۰×۴۰۰ (۱۶:۵) و سقف ۳۰۰KB همین‌جا نوشته شده است.
	 */
	function tara_artist_banner_group( $term = null ) {
		?>
		<div class="ab-termgroup">
			<h3 class="ab-termgroup__title">بنر سربرگ صفحهٔ هنرمند <span class="ab-termgroup__badge">اختیاری</span></h3>
			<?php
			$banner_url = ( $term && function_exists( 'tara_artist_banner_url' ) ) ? tara_artist_banner_url( $term, 'full' ) : '';
			$banner_id  = $term ? absint( tara_artist_field( $term, 'ab_artist_banner_id' ) ) : 0;
			tara_field_image_control(
				$banner_url,
				$banner_id,
				'ab_artist_banner',
				'ab_artist_banner_id',
				'بنر صفحهٔ هنرمند',
				'<strong>سایز استاندارد: ۱۲۰۰×۴۰۰ پیکسل (نسبت ۱۶:۵)، حداکثر ۳۰۰ کیلوبایت</strong> — فرمت JPG یا WebP.'
				. ' این تصویر در «ستون سوم» سربرگ صفحهٔ هنرمند نمایش داده می‌شود و کل عرض همان ستون را می‌پوشاند؛'
				. ' نام خواننده و متن روی آن نگذارید (قالب خودش نام را می‌نویسد).'
				. ' اگر خالی بماند، همان تصویر پروفایل (مربعی) مثل قبل نشان داده می‌شود — یعنی صفحه خراب نمی‌شود.'
			);
			?>
		</div>

		<?php
	}
}

if ( ! function_exists( 'tara_artist_form_body' ) ) {
	/** بدنهٔ مشترکِ فرم هنرمند (در «افزودن» و «ویرایش» عیناً یکی است). */
	function tara_artist_form_body( $term = null ) {
		$img_url = $term ? tara_image_url_from_value( tara_artist_field( $term, 'aimg2' ) ) : '';
		$img_id  = $term ? absint( tara_artist_field( $term, 'aimg2_id' ) ) : 0;
		$latin   = $term ? tara_artist_field( $term, 'artist_en' ) : '';
		$bio     = $term ? tara_artist_field( $term, 'ab_bio_short' ) : '';
		$bio_id  = $term ? absint( tara_artist_field( $term, 'ab_bio_post' ) ) : 0;

		wp_nonce_field( 'tara_save_term', 'tara_term_nonce' );
		echo '<input type="hidden" name="tara_artist_form" value="1">';
		?>
		<div class="ab-termgroup">
			<h3 class="ab-termgroup__title">تصویر و نام</h3>
			<?php tara_field_image_control( $img_url, $img_id, 'aimg2', 'aimg2_id' ); ?>
			<p class="ab-field">
				<label for="ab-artist-en">نام لاتین</label>
				<input type="text" id="ab-artist-en" name="artist_en" value="<?php echo esc_attr( $latin ); ?>" class="regular-text" dir="ltr">
				<span class="description">مثل <code>Mohsen Yeganeh</code> — در سرتیتر صفحهٔ هنرمند استفاده می‌شود.</span>
			</p>
		</div>

		<?php tara_artist_banner_group( $term ); ?>

		<div class="ab-termgroup">
			<h3 class="ab-termgroup__title">شبکه‌های اجتماعی</h3>
			<p class="description ab-termgroup__hint">هر مورد خالی بماند، آیکونش در سایت چاپ نمی‌شود.</p>
			<?php tara_field_social_controls( $term ); ?>
		</div>

		<div class="ab-termgroup">
			<h3 class="ab-termgroup__title">بیوگرافی</h3>
			<p class="ab-field">
				<label for="ab-bio-short">بیوگرافی کوتاه</label>
				<textarea id="ab-bio-short" name="ab_bio_short" rows="4" class="large-text" maxlength="600"
					placeholder="معرفی کوتاه، مثلاً ۱ تا ۳ جمله…"><?php echo esc_textarea( $bio ); ?></textarea>
				<span class="description">
					حدود ۲۵۰ تا ۳۵۰ نویسه معمولاً در کارت و سرتیتر خوب جا می‌شود (سقف ۶۰۰). متن ساده است،
					نه جای بیوگرافی کامل.
				</span>
			</p>
			<p class="ab-field">
				<label><strong>بیوگرافی کامل</strong> (نوشتهٔ مستقل)</label>
				<?php tara_field_post_picker( $bio_id ); ?>
			</p>
		</div>
		<?php
	}
}

/* ==================================================================================
   ۴) فرم هنرمند — افزودن و ویرایش
   ================================================================================== */

if ( ! function_exists( 'tara_artist_add_fields' ) ) {
	function tara_artist_add_fields( $taxonomy ) {
		if ( tara_acf_manages_fields( $taxonomy ) ) {
			/* v3.7.8 — فرمِ کامل پنهان است، ولی بنر باید از همین‌جا قابل ثبت باشد. */
			?>
			<div class="form-field ab-termform">
				<?php wp_nonce_field( 'tara_save_term', 'tara_term_nonce' ); ?>
				<input type="hidden" name="tara_artist_form" value="1">
				<?php tara_artist_banner_group( null ); ?>
			</div>
			<?php
			return;
		}
		?>
		<div class="form-field ab-termform">
			<?php tara_artist_form_body( null ); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'tara_artist_edit_fields' ) ) {
	function tara_artist_edit_fields( $term, $taxonomy ) {
		if ( tara_acf_manages_fields( $taxonomy ) ) {
			?>
			<tr class="form-field ab-termform">
				<th scope="row"><label>بنر صفحهٔ هنرمند</label></th>
				<td>
					<?php wp_nonce_field( 'tara_save_term', 'tara_term_nonce' ); ?>
					<input type="hidden" name="tara_artist_form" value="1">
					<?php tara_artist_banner_group( $term ); ?>
				</td>
			</tr>
			<?php
			return;
		}
		?>
		<tr class="form-field ab-termform">
			<th scope="row"><label>اطلاعات هنرمند</label></th>
			<td><?php tara_artist_form_body( $term ); ?></td>
		</tr>
		<?php
	}
}

if ( ! function_exists( 'tara_artist_fields_hooks' ) ) {
	function tara_artist_fields_hooks() {
		$taxes = array( 'artist', 'singer' );
		if ( function_exists( 'tara_artist_tax' ) ) {
			$taxes[] = tara_artist_tax();
		}
		foreach ( array_unique( $taxes ) as $tax ) {
			add_action( $tax . '_add_form_fields', 'tara_artist_add_fields', 10, 1 );
			add_action( $tax . '_edit_form_fields', 'tara_artist_edit_fields', 10, 2 );
		}
	}
	add_action( 'init', 'tara_artist_fields_hooks', 30 );
}

/* ==================================================================================
   ۵) فرم دسته‌بندی — توضیح کوتاه و کامل
   ================================================================================== */

if ( ! function_exists( 'tara_category_desc_fields' ) ) {
	function tara_category_desc_fields( $term = null ) {
		$short = '';
		$full  = '';
		if ( $term ) {
			$short = (string) get_term_meta( $term->term_id, 'ab_desc_short', true );
			$full  = (string) get_term_meta( $term->term_id, 'ab_desc_full', true );
		}
		wp_nonce_field( 'tara_save_term', 'tara_term_nonce' );
		echo '<input type="hidden" name="tara_cat_form" value="1">';
		?>
		<p class="ab-field">
			<label for="ab-desc-short">توضیح کوتاه (بالای آرشیو)</label>
			<textarea id="ab-desc-short" name="ab_desc_short" rows="3" class="large-text" maxlength="400"
				placeholder="یک یا دو جمله برای معرفی دسته…"><?php echo esc_textarea( $short ); ?></textarea>
			<span class="description">
				همین متن، بالای فهرست آثارِ این دسته نمایش داده می‌شود. اگر خالی بماند،
				توضیحِ خودِ دسته (همان فیلد قدیمی وردپرس) نشان داده می‌شود تا محتوای قبلی از دست نرود.
			</span>
		</p>
		<div class="ab-field">
			<label for="ab-desc-full">توضیح کامل (پایین آرشیو)</label>
			<?php
			if ( function_exists( 'wp_editor' ) ) {
				wp_editor(
					$full,
					'ab_desc_full',
					array(
						'textarea_name' => 'ab_desc_full',
						'textarea_rows' => 8,
						'media_buttons' => false,
						'teeny'         => true,
						'quicktags'     => true,
						'wpautop'       => true,
					)
				);
			} else {
				?>
				<textarea id="ab-desc-full" name="ab_desc_full" rows="8" class="large-text"><?php echo esc_textarea( $full ); ?></textarea>
				<?php
			}
			?>
			<span class="description">
				متن بلندتر دربارهٔ دسته (تاریخچه، سبک، نکته‌ها). در پایین آرشیو با دکمهٔ «نمایش بیشتر»
				نمایش داده می‌شود؛ اگر JavaScript نباشد، کامل و قابل خواندن باقی می‌ماند.
			</span>
		</div>
		<?php
	}
}

if ( ! function_exists( 'tara_category_add_fields' ) ) {
	function tara_category_add_fields( $taxonomy ) {
		if ( 'category' !== $taxonomy ) {
			return;
		}
		?>
		<div class="form-field ab-termform ab-termform--cat"><?php tara_category_desc_fields( null ); ?></div>
		<?php
	}
}

if ( ! function_exists( 'tara_category_edit_fields' ) ) {
	function tara_category_edit_fields( $term, $taxonomy ) {
		if ( 'category' !== $taxonomy ) {
			return;
		}
		?>
		<tr class="form-field ab-termform ab-termform--cat">
			<th scope="row"><label>توضیحات دسته</label></th>
			<td><?php tara_category_desc_fields( $term ); ?></td>
		</tr>
		<?php
	}
}

add_action( 'category_add_form_fields', 'tara_category_add_fields', 10, 1 );
add_action( 'category_edit_form_fields', 'tara_category_edit_fields', 10, 2 );

/* ==================================================================================
   ۶) ذخیره‌سازی
   ================================================================================== */

if ( ! function_exists( 'tara_can_edit_terms' ) ) {
	/** توانایی ویرایش ترم‌های همان تاکسونومی (پیش‌فرض وردپرس: manage_categories). */
	function tara_can_edit_terms( $taxonomy ) {
		$tax = get_taxonomy( $taxonomy );
		if ( $tax && isset( $tax->cap ) && isset( $tax->cap->edit_terms ) ) {
			return current_user_can( $tax->cap->edit_terms );
		}
		return current_user_can( 'manage_categories' );
	}
}

if ( ! function_exists( 'tara_save_term_fields' ) ) {
	/**
	 * ذخیرهٔ فیلدهای هنرمند و دسته‌بندی.
	 * روی edited_terms و created_term نصب می‌شود (هر دو، بعد از ذخیرهٔ خودِ ترم).
	 */
	function tara_save_term_fields( $term_id, $tt_id = 0, $taxonomy = '' ) {
		$term_id = absint( $term_id );
		if ( ! $term_id ) {
			return;
		}
		if ( '' === $taxonomy || ! $taxonomy ) {
			$term = get_term( $term_id );
			$taxonomy = ( $term && ! is_wp_error( $term ) ) ? $term->taxonomy : '';
		}
		if ( ! $taxonomy ) {
			return;
		}

		if ( ! isset( $_POST['tara_term_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tara_term_nonce'] ) ), 'tara_save_term' ) ) {
			return;
		}
		if ( ! tara_can_edit_terms( $taxonomy ) ) {
			return;
		}

		/* ---------- هنرمند ---------- */
		if ( isset( $_POST['tara_artist_form'] ) ) {
			$keys = tara_artist_field_keys();
			foreach ( $keys as $key => $def ) {
				if ( ! isset( $_POST[ $key ] ) ) {
					continue;
				}
				$raw = wp_unslash( $_POST[ $key ] );
				switch ( $def['type'] ) {
					case 'url':
						$value = esc_url_raw( trim( (string) $raw ) );
						break;
					case 'id':
						$value = (string) absint( $raw );
						break;
					case 'textarea':
						$value = trim( wp_strip_all_tags( (string) $raw ) );
						break;
					default:
						$value = trim( sanitize_text_field( (string) $raw ) );
				}
				if ( '' === $value ) {
					delete_term_meta( $term_id, $key );
				} else {
					update_term_meta( $term_id, $key, $value );
				}
			}
		}

		/* ---------- دسته‌بندی ---------- */
		if ( isset( $_POST['tara_cat_form'] ) && 'category' === $taxonomy ) {
			$short = isset( $_POST['ab_desc_short'] ) ? trim( wp_strip_all_tags( (string) wp_unslash( $_POST['ab_desc_short'] ) ) ) : '';
			if ( '' === $short ) {
				delete_term_meta( $term_id, 'ab_desc_short' );
			} else {
				update_term_meta( $term_id, 'ab_desc_short', tara_substr_utf8( $short, 0, 400 ) );
			}

			$full = isset( $_POST['ab_desc_full'] ) ? wp_kses_post( (string) wp_unslash( $_POST['ab_desc_full'] ) ) : '';
			if ( '' === trim( wp_strip_all_tags( $full ) ) ) {
				delete_term_meta( $term_id, 'ab_desc_full' );
			} else {
				update_term_meta( $term_id, 'ab_desc_full', $full );
			}
		}

		tara_artist_views_flush();
	}
	add_action( 'edited_term', 'tara_save_term_fields', 10, 3 );
	add_action( 'created_term', 'tara_save_term_fields', 10, 3 );
}

/* ==================================================================================
   ۷) دارایی‌های پیشخوان (کتابخانهٔ رسانه + انتخاب‌گر)
   ================================================================================== */

if ( ! function_exists( 'tara_admin_assets' ) ) {
	function tara_admin_assets( $hook = '' ) {
		$screens = array( 'edit-tags.php', 'term.php', 'post.php', 'post-new.php' );
		if ( ! in_array( $hook, $screens, true ) ) {
			return;
		}
		$dir = function_exists( 'get_template_directory' ) ? get_template_directory() : '';
		$uri = function_exists( 'get_template_directory_uri' ) ? get_template_directory_uri() : '';
		$ver = function_exists( 'wp_get_theme' ) ? wp_get_theme()->get( 'Version' ) : '3.5.2';

		if ( function_exists( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}
		if ( $dir && is_readable( trailingslashit( $dir ) . 'js/ab-admin-picker.js' ) ) {
			wp_enqueue_script( 'tara-admin-picker', trailingslashit( $uri ) . 'js/ab-admin-picker.js', array(), $ver, true );
			wp_localize_script(
				'tara-admin-picker',
				'TaraPicker',
				array(
					'ajax'  => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'tara_picker' ),
					'empty' => 'نتیجه‌ای پیدا نشد.',
					'error' => 'خطا در جست‌وجو. دوباره تلاش کنید.',
					'min'   => 'دست‌کم ۲ نویسه بنویسید…',
				)
			);
		}
		if ( $dir && is_readable( trailingslashit( $dir ) . 'css/ab-admin-352.css' ) ) {
			wp_enqueue_style( 'tara-admin-352', trailingslashit( $uri ) . 'css/ab-admin-352.css', array(), $ver );
		}
	}
	add_action( 'admin_enqueue_scripts', 'tara_admin_assets' );
}

/* ==================================================================================
   ۸) AJAX: جست‌وجوی هنرمند و نوشته
   ================================================================================== */

if ( ! function_exists( 'tara_ajax_picker' ) ) {
	function tara_ajax_picker() {
		check_ajax_referer( 'tara_picker', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی ندارید.' ), 403 );
		}

		$type  = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'artist';
		$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$query = trim( $query );

		if ( tara_strlen_utf8( $query ) < 2 && '' !== $query ) {
			wp_send_json_success( array( 'items' => array(), 'hint' => 'min' ) );
		}

		$items = array();

		if ( 'artist' === $type ) {
			$tax = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
			if ( ! taxonomy_exists( $tax ) && taxonomy_exists( 'singer' ) ) {
				$tax = 'singer';
			}
			$args = array(
				'taxonomy'   => $tax,
				'hide_empty' => false,
				'number'     => 20,
				'orderby'    => 'name',
				'order'      => 'ASC',
			);
			if ( '' !== $query ) {
				$args['search'] = $query;
			} else {
				$args['number'] = 10;
			}
			$terms = get_terms( $args );
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$items[] = array(
						'id'     => (int) $term->term_id,
						'title'  => $term->name,
						'sub'    => (int) $term->count . ' اثر',
						'image'  => tara_artist_image_url( $term, 'thumbnail' ),
						'status' => '',
					);
				}
			}
		} else {
			$statuses = array( 'publish', 'private' );
			if ( current_user_can( 'edit_others_posts' ) ) {
				$statuses[] = 'draft';
				$statuses[] = 'pending';
			}
			$args = array(
				'post_type'           => 'post',
				'post_status'         => $statuses,
				'posts_per_page'      => 20,
				's'                   => $query,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			);
			$q = new WP_Query( $args );
			foreach ( $q->posts as $post ) {
				$items[] = array(
					'id'     => (int) $post->ID,
					'title'  => get_the_title( $post ),
					'sub'    => get_the_date( '', $post ),
					'image'  => '',
					'status' => $post->post_status,
				);
			}
			wp_reset_postdata();
		}

		wp_send_json_success( array( 'items' => $items ) );
	}
	add_action( 'wp_ajax_tara_picker', 'tara_ajax_picker' );
}
