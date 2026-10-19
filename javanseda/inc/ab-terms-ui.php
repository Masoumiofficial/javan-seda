<?php
/**
 * جوان‌صدا — «فیلدهای ترم» (نسخۀ 3.4.9)
 * ---------------------------------------------------------------------------
 * ۱) صفحۀ ویرایشِ هنرمند در پیشخوان دوباره پر می‌شود: تصویر پروفایل، نام لاتین،
 *    صفحات مجازی، بیوگرافیِ کوتاه، نشانیِ «بیوگرافی کامل» و دکمۀ «ثابت در ردیف خانه».
 *    همه روی همان کلیدهای متای همیشگی می‌نویسند (aimg2، artist_en، afacebook، …)؛
 *    پس اگر افزونۀ ACF فیلدهایش را نشان بدهد، هر دو **یک داده** را می‌خوانند و هیچ
 *    مقدارِ از‌دست‌رفته‌ای وجود ندارد. اگر ACF نباشد هم همین صفحه کار می‌کند.
 * ۲) «توضیح کوتاه» + «توضیح کامل» برای دسته‌ها و برچسب‌ها: کوتاه جایِ توضیحِ فعلی
 *    در بالای صفحۀ آرشیو می‌نشیند و کامل پایینِ همان صفحه، با دکمۀ
 *    «مشاهدۀ بیشتر / کمتر» تا صفحه بلند نشود.
 * ۳) javan_seda_term_field() — خواندنِ یک‌جای داده: اول ACF، بعد term_meta.
 *
 * خاموش‌کردنِ این لایه:  define( 'JAVANSEDA_TERM_FIELDS', false );  در wp-config.php
 * (فقط فرمِ پیشخوان را می‌بندد؛ نمایشِ بیو و توضیح‌ها با فیلترهای پایین فعال است.)
 *
 * منطق بازدید/رأی (views / kksr) هیچ‌جا نوشته نمی‌شود — فقط خوانده می‌شود.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'JAVANSEDA_TERM_FIELDS' ) ) {
	define( 'JAVANSEDA_TERM_FIELDS', true );
}

/* =========================================================================
   ۰) خواندنِ مقدار — ACF، بعد term_meta، بعد فیلتر
   ========================================================================= */
if ( ! function_exists( 'javan_seda_term_id' ) ) {
	function javan_seda_term_id( $term = null ) {
		if ( null === $term ) {
			$term = get_queried_object();
		}
		if ( $term instanceof WP_Term ) {
			return (int) $term->term_id;
		}
		if ( is_object( $term ) && isset( $term->term_id ) ) {
			return (int) $term->term_id;
		}
		if ( is_numeric( $term ) ) {
			return (int) $term;
		}
		return 0;
	}
}

if ( ! function_exists( 'javan_seda_term_field' ) ) {
	/**
	 * یک فیلدِ ترم را می‌خواند: اگر ACF فعال است از آن، وگرنه از term_meta.
	 * (فیلدهای ACFِ این پوسته با همان نامِ کلید ذخیره می‌شوند، پس هر دو مسیر یکی است.)
	 */
	function javan_seda_term_field( $key, $term = null, $default = '' ) {
		$term_id = javan_seda_term_id( $term );
		$value   = '';

		if ( $term_id ) {
			if ( function_exists( 'get_field' ) ) {
				$for_acf = ( $term instanceof WP_Term ) ? $term : 'term_' . $term_id;
				$value   = get_field( $key, $for_acf );
			}
			if ( '' === $value || null === $value || array() === $value ) {
				$value = get_term_meta( $term_id, $key, true );
			}
		}

		if ( is_string( $value ) ) {
			$value = trim( $value );
		}
		if ( '' === $value || null === $value || array() === $value ) {
			$value = $default;
		}

		/** add_filter( 'javan_seda_term_field', ..., 10, 3 ) — $value, $key, $term_id */
		return apply_filters( 'javan_seda_term_field', $value, $key, $term_id );
	}
}

if ( ! function_exists( 'javan_seda_term_image' ) ) {
	/** دوربستِ امن (3.4.10) — خطا در این لایه صفحۀ سایت را نمی‌اندازد، فقط در لاگ می‌نشیند */
	function javan_seda_term_image( $term = null, $size = 'medium' ) {
		if ( ! function_exists( 'javan_seda_safe' ) ) {
			return javan_seda_term_image_impl( $term, $size );
		}
		return javan_seda_safe( 'javan_seda_term_image_impl', array( $term, $size ), array( "id" => 0, "url" => "", "alt" => "", "size" => "" ) );
	}
}
if ( ! function_exists( 'javan_seda_term_image_impl' ) ) {
	/**
	 * تصویرِ ترم را به شکل آرایه برمی‌گرداند: array( 'id', 'url', 'alt' ).
	 * منابع، به ترتیب: فیلد aimg2 (ACF/term_meta) → ردیفِ دستیِ «هنرمندانِ صفحۀ اصلی»
	 * (siing_t، با تطبیقِ نام یا آدرس) → تصویرِ پیش‌فرضِ قالب.
	 *
	 * چرا ردیفِ صفحۀ اصلی؟ چون تا پیش از این، عکسِ هر هنرمند باید دو بار آپلود می‌شد:
	 * یک بار در فیلدِ ترم و یک بار در «تنظیمات پوسته ← هنرمندانِ صفحۀ اصلی».
	 * از این نسخه، یک آپلود برای هر دو جا کافی است.
	 */
	function javan_seda_term_image_impl( $term = null, $size = 'medium' ) {
		$out = array(
			'id'   => 0,
			'url'  => '',
			'alt'  => '',
			'size' => $size,
		);

		$name = '';
		if ( $term instanceof WP_Term ) {
			$name    = $term->name;
			$out['alt'] = $name;
		}

		$raw = javan_seda_term_field( 'aimg2', $term );
		if ( is_array( $raw ) ) {
			if ( ! empty( $raw['ID'] ) ) {
				$out['id'] = (int) $raw['ID'];
			} elseif ( ! empty( $raw['id'] ) ) {
				$out['id'] = (int) $raw['id'];
			}
			if ( ! empty( $raw['url'] ) ) {
				$out['url'] = (string) $raw['url'];
			}
			if ( empty( $out['id'] ) && ! empty( $raw['alt'] ) ) {
				$out['alt'] = (string) $raw['alt'];
			}
		} elseif ( is_numeric( $raw ) ) {
			$out['id'] = (int) $raw;
		} elseif ( is_string( $raw ) && '' !== trim( $raw ) ) {
			$out['url'] = trim( $raw );
		}

		if ( ! $out['id'] && '' === $out['url'] ) {
			$row = function_exists( 'javan_seda_artist_home_row_for' ) ? javan_seda_artist_home_row_for( $term ) : array();
			if ( ! empty( $row['img'] ) ) {
				$out['url'] = (string) $row['img'];
			}
		}

		if ( $out['id'] && '' === $out['url'] ) {
			$src = wp_get_attachment_image_src( $out['id'], $size );
			if ( $src && ! empty( $src[0] ) ) {
				$out['url'] = (string) $src[0];
			}
		}
		if ( '' === $out['url'] ) {
			$out['url'] = function_exists( 'javan_seda_artist_default_image' )
				? javan_seda_artist_default_image()
				: get_template_directory_uri() . '/images/def-artist.webp';
		}

		$out = (array) apply_filters( 'javan_seda_term_image', $out, $term, $size );
		return $out;
	}
}

if ( ! function_exists( 'javan_seda_term_image_html' ) ) {
	/** تگ <img> آماده (با lazy/decoding و altِ پر) — همان چیزی که کارت‌ها لازم دارند */
	function javan_seda_term_image_html( $term = null, $size = 'medium', $atts = array() ) {
		$img  = javan_seda_term_image( $term, $size );
		if ( empty( $img ) ) {
			$img = array( 'id' => 0, 'url' => '', 'alt' => '', 'size' => $size );
		}
		$atts = wp_parse_args(
			(array) $atts,
			array(
				'class'    => '',
				'width'    => 200,
				'height'   => 200,
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);

		if ( ! empty( $img['id'] ) ) {
			$html = wp_get_attachment_image(
				$img['id'],
				$size,
				false,
				array(
					'alt'      => $img['alt'],
					'title'    => $img['alt'],
					'loading'  => $atts['loading'],
					'decoding' => $atts['decoding'],
					'class'    => trim( 'ab-artist__img ' . $atts['class'] ),
				)
			);
			if ( $html ) {
				return $html;
			}
		}

		$class = trim( 'ab-artist__img ' . $atts['class'] );
		return '<img class="' . esc_attr( $class ) . '" src="' . esc_url( $img['url'] ) . '" alt="' . esc_attr( $img['alt'] ) . '" title="' . esc_attr( $img['alt'] ) . '" width="' . esc_attr( (string) $atts['width'] ) . '" height="' . esc_attr( (string) $atts['height'] ) . '" loading="' . esc_attr( $atts['loading'] ) . '" decoding="' . esc_attr( $atts['decoding'] ) . '">';
	}
}

/* =========================================================================
   ۱) تعریفِ فیلدها
   ========================================================================= */
if ( ! function_exists( 'javan_seda_term_tax_list' ) ) {
	/**
	 * تاکسونومی‌هایی که این لایه برایشان فیلد می‌سازد.
	 * عمداً فهرست ثابت است و به taxonomy_exists() وابسته نیست: این فایل هنگام
	 * بارگذاریِ قالب اجرا می‌شود — یعنی پیش از init و پیش از ثبتِ «artist» — و
	 * اگر آن‌جا بررسی می‌شد، فیلدهای هنرمند برای همیشه از فهرست می‌افتادند.
	 * هوک روی تاکسونومیِ ثبت‌نشده بی‌ضرر است (هرگز صدا زده نمی‌شود).
	 */
	function javan_seda_term_tax_list() {
		$list = array( 'category', 'post_tag', 'artist', 'singer' );
		if ( function_exists( 'javan_seda_artist_tax' ) ) {
			$list[] = javan_seda_artist_tax();
		}
		$list = array_values( array_unique( array_filter( $list ) ) );
		return (array) apply_filters( 'javan_seda_term_field_taxes', $list );
	}
}

if ( ! function_exists( 'javan_seda_term_field_defs' ) ) {
	/**
	 * فیلدهای هر تاکسونومی. کلیدها عمداً همان کلیدهای ACFِ قالب مادر است تا
	 * داده‌های ۲۰۲۱ تا امروز بدون مهاجرت خوانده/ویرایش شوند.
	 */
	function javan_seda_term_field_defs( $tax = '' ) {
		$fields = array();

		if ( in_array( $tax, array( 'artist', 'singer' ), true ) || ( function_exists( 'javan_seda_artist_tax' ) && $tax === javan_seda_artist_tax() ) ) {
			$fields = array_merge(
				$fields,
				array(
					array(
						'key'      => 'aimg2',
						'label'    => 'تصویر پروفایل هنرمند',
						'type'     => 'upload',
						'sanitize' => 'id',
						'desc'     => 'اندازۀ پیشنهادی ۵۰۰×۵۰۰ (مربع). همین تصویر در ردیف «هنرمندانِ صفحۀ اصلی» هم به کار می‌رود؛ لازم نیست دوباره جایی آپلود کنید.',
					),
					array(
						'key'      => 'artist_en',
						'label'    => 'نام لاتین',
						'type'     => 'text',
						'sanitize' => 'text',
						'desc'     => 'زیرِ نامِ فارسی در فهرست نمایش داده می‌شود (مثلاً Mohsen Yeganeh). اگر خالی بماند از اسلاگِ انگلیسی ساخته می‌شود.',
					),
					array(
						'key'      => 'ab_bio_short',
						'label'    => 'بیوگرافیِ کوتاه',
						'type'     => 'textarea',
						'sanitize' => 'textarea',
						'desc'     => '۲ تا ۴ جمله. اگر خالی بماند، خودکار از «توضیحِ ترم» خلاصه می‌شود.',
					),
					array(
						'key'      => 'ab_bio_post',
						'label'    => 'بیوگرافیِ کامل — نوشته یا آدرس',
						'type'     => 'text',
						'sanitize' => 'url_or_id',
						'desc'     => 'شمارۀ یک نوشته (مثلاً ۴۸۲۱) یا آدرسِ کاملِ آن نوشته. اگر پر شود، دکمۀ «بیوگرافی کامل» به همین آدرس می‌رود؛ اگر خالی بماند و «توضیحِ ترم» بلندتر باشد، متن کامل همین‌جا با دکمۀ «بیشتر/کمتر» باز می‌شود.',
					),
					array(
						'key'      => 'ab_home_pin',
						'label'    => 'ثابت در ردیف خانۀ هنرمندان',
						'type'     => 'on-off',
						'sanitize' => 'bool',
						'desc'     => 'روشن = این هنرمند در ردیف «محبوبترین هنرمندان» صفحۀ اصلی، بالاتر از بقیه می‌آید.',
					),
					array(
						'key'      => 'ab_home_exclude',
						'label'    => 'خارج از ردیف خانه',
						'type'     => 'on-off',
						'sanitize' => 'bool',
						'desc'     => 'روشن = در ردیفِ خودکارِ صفحۀ اصلی نمایش داده نشود (صفحهٔ خودش سر جایش است).',
					),
				)
			);
			$socials = array(
				'afacebook'  => 'فیس‌بوک',
				'atelegram'  => 'تلگرام',
				'ainstagram' => 'اینستاگرام',
				'atwitter'   => 'توییتر / X',
				'ayoutube'   => 'یوتیوب',
			);
			foreach ( $socials as $key => $label ) {
				$fields[] = array(
					'key'      => $key,
					'label'    => $label,
					'type'     => 'text',
					'sanitize' => 'url',
					'desc'     => 'آدرسِ کامل با https:// — خالی = دکمه نمایش داده نمی‌شود.',
				);
			}
		} else {
			$fields = array(
				array(
					'key'      => 'ab_desc_short',
					'label'    => 'توضیح کوتاه',
					'type'     => 'textarea',
					'sanitize' => 'textarea',
					'desc'     => 'جای «توضیح»ِ بالای صفحۀ آرشیو را می‌گیرد (۱ تا ۳ خط). اگر خالی بماند، همان توضیحِ همیشگی نمایش داده می‌شود.',
				),
				array(
					'key'      => 'ab_desc_full',
					'label'    => 'توضیح کامل',
					'type'     => 'textarea',
					'sanitize' => 'html',
					'desc'     => 'پایینِ همان صفحه، بسته و با دکمۀ «مشاهدۀ بیشتر / کمتر». HTML ساده (پاراگراف، فهرست، لینک، بولد) پذیرفته می‌شود.',
				),
			);
		}

		return (array) apply_filters( 'javan_seda_term_field_defs', $fields, $tax );
	}
}

/* =========================================================================
   ۲) پیشخوان — نمایش و ذخیره
   ========================================================================= */
if ( JAVANSEDA_TERM_FIELDS && is_admin() ) {

	if ( ! function_exists( 'javan_seda_term_fields_is_term_screen' ) ) {
		function javan_seda_term_fields_is_term_screen() {
			if ( function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();
				if ( $screen && ! empty( $screen->id ) ) {
					return false !== strpos( (string) $screen->id, '-tags' );
				}
			}
			return isset( $GLOBALS['pagenow'] ) && in_array( (string) $GLOBALS['pagenow'], array( 'edit-tags.php', 'term.php' ), true );
		}
	}

	if ( ! function_exists( 'javan_seda_term_fields_enqueue' ) ) {
	/** دوربستِ امن (3.4.10) — خطا در این لایه صفحۀ سایت را نمی‌اندازد، فقط در لاگ می‌نشیند */
	function javan_seda_term_fields_enqueue( $hook = '' ) {
		if ( ! function_exists( 'javan_seda_safe' ) ) {
			return javan_seda_term_fields_enqueue_impl( $hook );
		}
		return javan_seda_safe( 'javan_seda_term_fields_enqueue_impl', array( $hook ), null );
	}
}
if ( ! function_exists( 'javan_seda_term_fields_enqueue_impl' ) ) {
		function javan_seda_term_fields_enqueue_impl( $hook = '' ) {
			unset( $hook );
			if ( ! javan_seda_term_fields_is_term_screen() ) {
				return;
			}
			wp_enqueue_media();
			wp_register_style( 'javan-seda-term-fields', false, array(), false );
			wp_enqueue_style( 'javan-seda-term-fields' );
			$css = '.js-ab-term-img{display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap}'
				. '.js-ab-term-img__box{width:120px;height:120px;border:1px dashed #c3c4c7;border-radius:6px;background:#f6f7f7 center/cover no-repeat;flex:0 0 auto;cursor:pointer}'
				. '.night .js-ab-term-img__box{border-color:#3c3f43;background-color:#23282d}'
				. '.ab-term-fields{margin:0;padding:0}'
				. '.ab-term-fields>div{padding:10px 0;border-top:1px solid #eee}'
				. '.ab-term-fields label{display:block;font-weight:600;margin-bottom:4px}'
				. '.ab-term-fields .howto{display:block;margin-top:4px;color:#646970;font-size:12px;line-height:1.9}'
				. 'html[dir="rtl"] .ab-term-fields input[type=text],html[dir="rtl"] .ab-term-fields textarea{width:100%;max-width:640px}';
			wp_add_inline_style( 'javan-seda-term-fields', $css );
			$js = "(function(){document.addEventListener('click',function(e){var t=e.target;if(!t.closest)return;var box=t.closest('.js-ab-term-img');if(!box){return;}var inp=box.querySelector('input[type=hidden]'),prev=box.querySelector('.js-ab-term-img__box'),btns=box.querySelectorAll('button');var frame=wp.media({title:'انتخاب تصویر',multiple:false,library:{type:'image'}});frame.on('select',function(){var a=frame.state().get('selection').first().toJSON();if(inp){inp.value=a.id;}if(prev){prev.style.backgroundImage='url('+(a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url)+')';}});frame.open();e.preventDefault();},false);document.addEventListener('click',function(e){var t=e.target;if(!t.closest)return;var rm=t.closest('.js-ab-term-img__rm');if(!rm){return;}var box=rm.closest('.js-ab-term-img');var inp=box&&box.querySelector('input[type=hidden]');var prev=box&&box.querySelector('.js-ab-term-img__box');if(inp){inp.value='';}if(prev){prev.style.backgroundImage='none';}e.preventDefault();},false);})();";
			wp_add_inline_script( 'jquery', $js );
		}
	}
	add_action( 'admin_enqueue_scripts', 'javan_seda_term_fields_enqueue', 20 );

	if ( ! function_exists( 'javan_seda_term_fields_html' ) ) {
		/** چاپِ ردیف‌ها — هم برای «ویرایش» هم برای «افزودن» */
		function javan_seda_term_fields_html( $term, $in_edit = true ) {
			$tax  = is_object( $term ) ? (string) $term->taxonomy : '';
			$id   = javan_seda_term_id( $term );
			$fields = javan_seda_term_field_defs( $tax );
			if ( ! $fields ) {
				return;
			}
			?>
			<tr class="form-field ab-term-fields-row">
				<th scope="col" colspan="2"><strong>جوان‌صدا — اطلاعات تکمیلی</strong></th>
				<td>
					<?php if ( $in_edit ) : ?>
						<div class="ab-term-fields">
					<?php endif; ?>
					<?php wp_nonce_field( 'javan_seda_term_fields', 'javan_seda_term_fields_nonce' ); ?>
					<?php foreach ( $fields as $f ) : ?>
						<?php
						$val = $id ? javan_seda_term_field( $f['key'], $term, '' ) : '';
						if ( 'upload' === $f['type'] ) {
							$val = (int) get_term_meta( $id, $f['key'], true );
							$src = $val ? wp_get_attachment_image_url( $val, 'medium' ) : '';
							?>
							<div class="js-ab-term-img" style="margin-bottom:12px">
								<label><?php echo esc_html( $f['label'] ); ?></label>
								<div class="js-ab-term-img__box" style="<?php echo $src ? 'background-image:url(' . esc_url( $src ) . ')' : ''; ?>" title="برای انتخاب یا تغییر، کلیک کنید"></div>
								<div>
									<input type="hidden" name="<?php echo esc_attr( $f['key'] ); ?>" value="<?php echo $val ? esc_attr( (string) $val ) : ''; ?>">
									<button type="button" class="button button-secondary"><?php echo $val ? 'تغییر تصویر' : 'انتخاب تصویر'; ?></button>
									<?php if ( $val ) : ?>
										<button type="button" class="button-link js-ab-term-img__rm">برداشتن</button>
									<?php endif; ?>
								</div>
							</div>
							<?php if ( ! empty( $f['desc'] ) ) : ?>
								<span class="howto"><?php echo esc_html( $f['desc'] ); ?></span>
							<?php endif; ?>
							<?php continue; ?>
						<?php } ?>
						<div style="margin-bottom:12px">
							<label for="jv-<?php echo esc_attr( $f['key'] ) ?>"><?php echo esc_html( $f['label'] ); ?></label>
							<?php if ( 'textarea' === $f['type'] ) : ?>
								<textarea id="jv-<?php echo esc_attr( $f['key'] ) ?>" name="<?php echo esc_attr( $f['key'] ); ?>" rows="6" class="large-text"><?php echo esc_textarea( is_string( $val ) ? $val : '' ); ?></textarea>
							<?php elseif ( 'on-off' === $f['type'] ) : ?>
								<label style="font-weight:400;display:inline">
									<input type="checkbox" name="<?php echo esc_attr( $f['key'] ); ?>" value="on" <?php checked( 'on', (string) $val ); ?>>
									نمایش
								</label>
							<?php else : ?>
								<input type="text" id="jv-<?php echo esc_attr( $f['key'] ) ?>" name="<?php echo esc_attr( $f['key'] ); ?>" value="<?php echo esc_attr( is_string( $val ) ? $val : (string) $val ); ?>" class="regular-text" dir="<?php echo in_array( $f['sanitize'], array( 'url', 'url_or_id' ), true ) ? 'ltr' : 'auto'; ?>">
							<?php endif; ?>
							<?php if ( ! empty( $f['desc'] ) ) : ?>
								<span class="howto"><?php echo esc_html( $f['desc'] ); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					<?php if ( $in_edit ) : ?>
						</div>
					<?php endif; ?>
				</td>
			</tr>
			<?php
		}
	}

	if ( ! function_exists( 'javan_seda_term_fields_ui' ) ) {
	/** دوربستِ امن (3.4.10) — خطا در این لایه صفحۀ سایت را نمی‌اندازد، فقط در لاگ می‌نشیند */
	function javan_seda_term_fields_ui( $term, $tax = '' ) {
		if ( ! function_exists( 'javan_seda_safe' ) ) {
			return javan_seda_term_fields_ui_impl( $term, $tax );
		}
		return javan_seda_safe( 'javan_seda_term_fields_ui_impl', array( $term, $tax ), null );
	}
}
if ( ! function_exists( 'javan_seda_term_fields_ui_impl' ) ) {
		/** صفحۀ «ویرایش» — ردیف‌ها داخل همان جدولِ فرم */
		function javan_seda_term_fields_ui_impl( $term, $tax = '' ) {
			javan_seda_term_fields_html( $term, true );
		}
	}

	if ( ! function_exists( 'javan_seda_term_fields_ui_add' ) ) {
	/** دوربستِ امن (3.4.10) — خطا در این لایه صفحۀ سایت را نمی‌اندازد، فقط در لاگ می‌نشیند */
	function javan_seda_term_fields_ui_add( $tax ) {
		if ( ! function_exists( 'javan_seda_safe' ) ) {
			return javan_seda_term_fields_ui_add_impl( $tax );
		}
		return javan_seda_safe( 'javan_seda_term_fields_ui_add_impl', array( $tax ), null );
	}
}
if ( ! function_exists( 'javan_seda_term_fields_ui_add_impl' ) ) {
		/** صفحۀ «افزودن» — فرمِ ترم آن‌جا <table> ندارد، پس یک بلوک ساده */
		function javan_seda_term_fields_ui_add_impl( $tax ) {
			$fields = javan_seda_term_field_defs( (string) $tax );
			if ( ! $fields ) {
				return;
			}
			echo '<div class="form-field ab-term-fields" style="padding:10px 0;border-top:1px solid #eee">';
			echo '<strong>جوان‌صدا — اطلاعات تکمیلی</strong>';
			wp_nonce_field( 'javan_seda_term_fields', 'javan_seda_term_fields_nonce' );
			foreach ( $fields as $f ) {
				if ( 'upload' === $f['type'] ) {
					continue; // تصویرِ ترم تازه، بعد از ساختن در صفحۀ ویرایش
				}
				echo '<div style="margin:10px 0"><label for="jva-' . esc_attr( $f['key'] ) . '">' . esc_html( $f['label'] ) . '</label>';
				if ( 'textarea' === $f['type'] ) {
					echo '<textarea id="jva-' . esc_attr( $f['key'] ) . '" name="' . esc_attr( $f['key'] ) . '" rows="5" class="large-text"></textarea>';
				} elseif ( 'on-off' === $f['type'] ) {
					echo '<label style="font-weight:400"><input type="checkbox" name="' . esc_attr( $f['key'] ) . '" value="on"> نمایش</label>';
				} else {
					echo '<input type="text" id="jva-' . esc_attr( $f['key'] ) . '" name="' . esc_attr( $f['key'] ) . '" class="regular-text">';
				}
				if ( ! empty( $f['desc'] ) ) {
					echo '<span class="howto">' . esc_html( $f['desc'] ) . '</span>';
				}
				echo '</div>';
			}
			echo '</div>';
		}
	}

	if ( ! function_exists( 'javan_seda_term_fields_save' ) ) {
	/** دوربستِ امن (3.4.10) — خطا در این لایه صفحۀ سایت را نمی‌اندازد، فقط در لاگ می‌نشیند */
	function javan_seda_term_fields_save( $term_id, $tt_id = 0, $taxonomy = '' ) {
		if ( ! function_exists( 'javan_seda_safe' ) ) {
			return javan_seda_term_fields_save_impl( $term_id, $tt_id, $taxonomy );
		}
		return javan_seda_safe( 'javan_seda_term_fields_save_impl', array( $term_id, $tt_id, $taxonomy ), null );
	}
}
if ( ! function_exists( 'javan_seda_term_fields_save_impl' ) ) {
		function javan_seda_term_fields_save_impl( $term_id, $tt_id = 0, $taxonomy = '' ) {
			unset( $tt_id );
			$tax = (string) $taxonomy;
			if ( '' === $tax && isset( $_POST['taxonomy'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$tax = sanitize_key( wp_unslash( (string) $_POST['taxonomy'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
			if ( ! in_array( $tax, javan_seda_term_tax_list(), true ) ) {
				return;
			}
			if ( ! current_user_can( 'manage_categories' ) ) {
				return;
			}
			$nonce = isset( $_POST['javan_seda_term_fields_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['javan_seda_term_fields_nonce'] ) ) : '';
			if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'javan_seda_term_fields' ) ) {
				return;
			}
			foreach ( javan_seda_term_field_defs( $tax ) as $f ) {
				$key = $f['key'];
				if ( ! isset( $_POST[ $key ] ) && 'on-off' !== $f['type'] ) { // phpcs:ignore WordPress.Security.NonceVerification
					continue; // فیلدی که در این فرم نبود را پاک نکن
				}
				$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$val = '';
				switch ( $f['sanitize'] ) {
					case 'id':
						$val = absint( $raw );
						break;
					case 'bool':
						$val = ( 'on' === (string) $raw ) ? 'on' : 'off';
						break;
					case 'url':
						$val = esc_url_raw( (string) $raw );
						break;
					case 'url_or_id':
						$val = is_numeric( $raw ) ? absint( $raw ) : esc_url_raw( (string) $raw );
						break;
					case 'textarea':
						$val = sanitize_textarea_field( (string) $raw );
						break;
					case 'html':
						$val = wp_kses_post( (string) $raw );
						break;
					default:
						$val = sanitize_text_field( (string) $raw );
				}
				if ( '' === $val || array() === $val ) {
					delete_term_meta( $term_id, $key );
				} else {
					update_term_meta( $term_id, $key, $val );
				}
				/* ACF هم همین کلید را می‌خواند؛ متای اشاره‌گرِ ACF ( _<key> ) عمداً دست
				   نمی‌خورد تا خودِ ACF هم مقدار را درست بشناسد. */
			}
			clean_term_cache( array( (int) $term_id ), $tax );
			if ( function_exists( 'javan_seda_artist_flush_url_cache' ) ) {
				javan_seda_artist_flush_url_cache();
			}
		}
	}

	$jv_tf_taxes = javan_seda_term_tax_list();
	foreach ( $jv_tf_taxes as $jv_tf_tax ) {
		add_action( $jv_tf_tax . '_edit_form_fields', 'javan_seda_term_fields_ui', 10, 2 );
		add_action( $jv_tf_tax . '_add_form_fields', 'javan_seda_term_fields_ui_add', 10, 1 );
		add_action( 'edited_' . $jv_tf_tax, 'javan_seda_term_fields_save', 10, 3 );
		add_action( 'created_' . $jv_tf_tax, 'javan_seda_term_fields_save', 10, 3 );
	}
	unset( $jv_tf_taxes, $jv_tf_tax );
}

/* =========================================================================
   ۳) پیشخوان — یادداشتِ کنارِ فیلدهای ACF (تا کاربر نداند کدام را پر کند، گیج نشود)
   ========================================================================= */
if ( JAVANSEDA_TERM_FIELDS && is_admin() && ! function_exists( 'javan_seda_term_fields_notice' ) ) {
	function javan_seda_term_fields_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || false === strpos( (string) $screen->id, '-tags' ) ) {
			return;
		}
		echo '<div class="notice notice-info is-dismissible" style="margin-top:8px"><p>';
		echo 'این پوسته اطلاعات هنرمند را در دو جا نشان می‌دهد: فیلدهای ACF (اگر افزونه فعال باشد و قواعدش این تاکسونومی را بپوشاند) و بلوک «جوان‌صدا — اطلاعات تکمیلی». ';
		echo '<strong>هر کدام را دوست داشتید پر کنید — هر دو یک داده را می‌خوانند.</strong> اگر فیلدهای ACF غیب شده‌اند، بلوک همین پایین کار را راه می‌اندازد.';
		echo '</p></div>';
	}
	add_action( 'admin_notices', 'javan_seda_term_fields_notice' );
}

/* =========================================================================
   ۴) نمایش — بیوگرافیِ هنرمند و توضیحِ دسته/برچسب
   ========================================================================= */
if ( ! function_exists( 'javan_seda_term_bio' ) ) {
	/** دوربستِ امن (3.4.10) — خطا در این لایه صفحۀ سایت را نمی‌اندازد، فقط در لاگ می‌نشیند */
	function javan_seda_term_bio( $term = null ) {
		if ( ! function_exists( 'javan_seda_safe' ) ) {
			return javan_seda_term_bio_impl( $term );
		}
		return javan_seda_safe( 'javan_seda_term_bio_impl', array( $term ), array( 'short' => '', 'full' => '', 'url' => '', 'inline' => false ) );
	}
}
if ( ! function_exists( 'javan_seda_term_bio_impl' ) ) {
	/**
	 * بیوی هنرمند: کوتاه + کامل + نشانی.
	 * short = فیلدِ ab_bio_short ← خلاصۀ «توضیحِ ترم»
	 * full  = «توضیحِ ترم» (اگر بلندتر از کوتاه باشد)
	 * url   = ab_bio_post (شمارۀ نوشته یا آدرس) — اگر باشد، دکمه به جای بازکردنِ inline لینک می‌شود
	 */
	function javan_seda_term_bio_impl( $term = null ) {
		$bio = array(
			'short'  => '',
			'full'   => '',
			'url'    => '',
			'inline' => false,
		);
		$id = javan_seda_term_id( $term );
		if ( ! $id ) {
			return $bio;
		}

		$t = ( $term instanceof WP_Term ) ? $term : get_term( $id );
		$full_raw = ( $t instanceof WP_Term ) ? trim( (string) $t->description ) : '';
		$full_html = wpautop( esc_html( $full_raw ) );
		$short     = (string) javan_seda_term_field( 'ab_bio_short', $id, '' );

		if ( '' === $short && '' !== $full_raw ) {
			$words = (int) apply_filters( 'javan_seda_bio_trim_words', 48 );
			$short = javan_seda_term_excerpt( $full_raw, $words );
		}

		$url = '';
		$link = (string) javan_seda_term_field( 'ab_bio_post', $id, '' );
		if ( '' !== $link ) {
			if ( is_numeric( $link ) ) {
				$perm = get_permalink( (int) $link );
				$url  = ( $perm && ! is_wp_error( $perm ) ) ? $perm : '';
			} elseif ( preg_match( '#^https?://#i', $link ) ) {
				$url = $link;
			}
		}

		$bio['short']  = wp_kses_post( wpautop( esc_html( $short ) ) );
		$bio['full']   = (string) $full_html;
		$bio['url']    = (string) $url;
		$bio['inline'] = false;

		/* اگر «کوتاه» خلاصۀ خودِ توضیح باشد، در بلوکِ بازشونده فقط *ادامه* را نشان بده،
		   تا متن دوباره تکرار نشود. */
		$nf = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $bio['full'] ) ) );
		$ns = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $bio['short'] ) ) );
		if ( '' !== $ns && substr( $nf, 0, strlen( $ns ) ) === $ns ) {
			$rest = trim( (string) substr( $nf, strlen( $ns ) ) );
			$rest = ltrim( $rest, " .،:-\xE2\x80\x8C" ); // نقطه، ویرگول فارسی، دونقطه، تیره، نیم‌فاصله
			$bio['full'] = wpautop( esc_html( $rest ) );
		}
		if ( '' === $url ) {
			$bio['inline'] = ( '' !== trim( wp_strip_all_tags( $bio['full'] ) ) );
		}

		return (array) apply_filters( 'javan_seda_term_bio', $bio, $term, $id );
	}
}

if ( ! function_exists( 'javan_seda_term_excerpt' ) ) {
	/** خلاصه‌سازی با حفظِ مرزِ واژه‌ها (فارسی هم درست؛ بدون strip_shortcodes) */
	function javan_seda_term_excerpt( $text, $words = 48 ) {
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );
		if ( '' === $text ) {
			return '';
		}
		$words = max( 8, (int) $words );
		$chars = preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $chars ) ) {
			return $text;
		}
		$out   = array();
		$count = 0;
		$buf   = array();
		foreach ( $chars as $c ) {
			if ( ' ' === $c ) {
				if ( $buf ) {
					$out[] = implode( '', $buf );
					$buf   = array();
					++$count;
					if ( $count >= $words ) {
						break;
					}
				}
				continue;
			}
			$buf[] = $c;
		}
		if ( $buf && $count < $words ) {
			$out[] = implode( '', $buf );
		}
		$cut = implode( ' ', $out );
		if ( function_exists( 'mb_substr' ) ? mb_strlen( $text, 'UTF-8' ) > mb_strlen( $cut, 'UTF-8' ) : strlen( $text ) > strlen( $cut ) ) {
			$cut .= '…';
		}
		return $cut;
	}
}

if ( ! function_exists( 'javan_seda_term_more_html' ) ) {
	/**
	 * بلوکِ «متن کامل + دکمۀ بیشتر/کمتر». یک بار در هر صفحه صدا زده شود.
	 * $args: id (منحصر‌به‌فرد), title, full, button_more, button_less
	 */
	function javan_seda_term_more_html( $args = array() ) {
		$args = wp_parse_args(
			(array) $args,
			array(
				'id'           => 'ab-more',
				'title'        => '',
				'full'         => '',
				'button_more'  => 'مشاهدۀ کامل',
				'button_less'  => 'بستن',
				'class'        => '',
			)
		);
		$full = trim( (string) $args['full'] );
		if ( '' === wp_strip_all_tags( $full ) ) {
			return '';
		}
		$uid = preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $args['id'] );
		if ( '' === $uid ) {
			$uid = 'ab-more-' . substr( md5( $full ), 0, 10 );
		}
		$GLOBALS['javan_seda_term_more'] = true;

		ob_start();
		?>
		<div class="ab-term-more <?php echo esc_attr( $args['class'] ); ?>" id="<?php echo esc_attr( $uid ); ?>">
			<?php if ( '' !== (string) $args['title'] ) : ?>
				<h3 class="ab-term-more__title"><?php echo esc_html( $args['title'] ); ?></h3>
			<?php endif; ?>
			<div class="ab-term-more__inner"><?php echo wp_kses_post( wpautop( $full ) ); ?></div>
		</div>
		<p class="ab-term-more__bar">
			<button type="button" class="ab-term-more__btn" data-ab-more="<?php echo esc_attr( $uid ); ?>" data-more="<?php echo esc_attr( $args['button_more'] ); ?>" data-less="<?php echo esc_attr( $args['button_less'] ); ?>">
				<?php echo esc_html( $args['button_more'] ); ?>
			</button>
		</p>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'javan_seda_term_footer_script' ) ) {
	/** اسکریپتِ دکمۀ «بیشتر/کمتر» — فقط وقتی لازم شده باشد چاپ می‌شود */
	function javan_seda_term_footer_script() {
		if ( empty( $GLOBALS['javan_seda_term_more'] ) ) {
			return;
		}
		$js = "(function(){document.addEventListener('click',function(e){var b=e.target;if(!b||!b.closest){return;}b=b.closest('[data-ab-more]');if(!b){return;}var box=document.getElementById(b.getAttribute('data-ab-more'));if(!box){return;}var on=box.classList.toggle('is-open');var more=b.getAttribute('data-more')||'بیشتر';var less=b.getAttribute('data-less')||'بستن';b.textContent=on?less:more;e.preventDefault();},false);})();";
		echo '<script>' . $js . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- رشتهٔ ثابت
	}
}
add_action( 'wp_footer', 'javan_seda_term_footer_script', 99 );

if ( ! function_exists( 'javan_seda_term_desc_top' ) ) {
	/** دوربستِ امن (3.4.10) — خطا در این لایه صفحۀ سایت را نمی‌اندازد، فقط در لاگ می‌نشیند */
	function javan_seda_term_desc_top( $term = null ) {
		if ( ! function_exists( 'javan_seda_safe' ) ) {
			return javan_seda_term_desc_top_impl( $term );
		}
		return javan_seda_safe( 'javan_seda_term_desc_top_impl', array( $term ), '' );
	}
}
if ( ! function_exists( 'javan_seda_term_desc_top_impl' ) ) {
	/**
	 * توضیحِ بالای صفحۀ آرشیو: «توضیح کوتاه» اگر پر شده باشد، وگرنه توضیحِ همیشگی.
	 * خروجی escape شده است؛ اگر خالی باشد رشتهٔ خالی می‌دهد تا کادرِ خالی چاپ نشود.
	 */
	function javan_seda_term_desc_top_impl( $term = null ) {
		$id    = javan_seda_term_id( $term );
		$short = ( '' === $id ) ? '' : (string) get_term_meta( $id, 'ab_desc_short', true );
		$short = trim( wp_strip_all_tags( $short ) );
		if ( '' === $short ) {
			// «توضیح کوتاه» پر نشده ⇒ همان توضیحِ همیشگی می‌ماند (رفتارِ قبل از این نسخه)
			$desc = '';
			if ( is_category() && function_exists( 'category_description' ) ) {
				$desc = (string) category_description();
			} elseif ( $id ) {
				$t = get_term( $id );
				$desc = ( $t instanceof WP_Term ) ? (string) $t->description : '';
			}
			return wpautop( esc_html( wp_strip_all_tags( $desc ) ) );
		}
		return wpautop( esc_html( $short ) );
	}
}

if ( ! function_exists( 'javan_seda_term_desc_bottom' ) ) {
	/** دوربستِ امن (3.4.10) — خطا در این لایه صفحۀ سایت را نمی‌اندازد، فقط در لاگ می‌نشیند */
	function javan_seda_term_desc_bottom( $term = null ) {
		if ( ! function_exists( 'javan_seda_safe' ) ) {
			return javan_seda_term_desc_bottom_impl( $term );
		}
		return javan_seda_safe( 'javan_seda_term_desc_bottom_impl', array( $term ), '' );
	}
}
if ( ! function_exists( 'javan_seda_term_desc_bottom_impl' ) ) {
	/** توضیحِ کاملِ پایینِ صفحۀ آرشیو، با دکمۀ بیشتر/کمتر */
	function javan_seda_term_desc_bottom_impl( $term = null ) {
		$id = javan_seda_term_id( $term );
		if ( ! $id ) {
			return '';
		}
		$full = (string) get_term_meta( $id, 'ab_desc_full', true );
		if ( '' === trim( wp_strip_all_tags( $full ) ) ) {
			return '';
		}
		$name = '';
		$t    = get_term( $id );
		if ( $t instanceof WP_Term ) {
			$name = $t->name;
		}
		return javan_seda_term_more_html(
			array(
				'id'          => 'ab-desc-full-' . $id,
				'title'       => '' !== $name ? ( 'دربارۀ ' . $name ) : '',
				'full'        => wp_kses_post( $full ),
				'button_more' => 'مشاهدۀ توضیح کامل',
				'button_less' => 'بستنِ توضیح',
				'class'       => 'ab-term-more--desc',
			)
		);
	}
}
