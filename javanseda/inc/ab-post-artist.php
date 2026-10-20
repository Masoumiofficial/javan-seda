<?php
/**
 * جوان‌صدا ۳.۵.۲ — جعبهٔ «هنرمندِ اثر» در ویرایش نوشته
 * ==========================================================================
 * در نسخهٔ مبنا رابطهٔ اثر با هنرمند دو مسیر جدا داشت:
 *   • تاکسونومی (singer/artist) — دقیق، ولی انتخابش در پیشخوان جست‌وجو نداشت،
 *   • متای متنیِ «artist» — همان چیزی که single.php و کارت‌ها چاپ می‌کنند و
 *     کوئریِ «آهنگ‌های دیگرِ همین هنرمند» با LIKE روی آن کار می‌کند.
 *
 * نتیجه: مدیر برای هر آهنگ باید نام هنرمند را دستی تایپ می‌کرد؛ اگر یک حرف
 * فرق داشت، اثر از صفحهٔ هنرمند و از «مجموع بازدید» جا می‌ماند.
 *
 * این فایل یک جعبهٔ ساده اضافه می‌کند:
 *   جست‌وجوی هنرمند (AJAX، با تصویر) → افزودن → ذخیره در تاکسونومی،
 *   و همگام‌سازیِ خودکارِ متای متنیِ «artist» با نام همان هنرمندان،
 *   تا هیچ بخشی از قالبِ مبنا (single.php، ویجت‌ها، کارت‌ها) نشکند.
 *
 * نکته‌ها:
 *   • فقط برای پست‌تایپ‌هایی که تاکسونومی هنرمند به آن‌ها وصل است.
 *   • ذخیره با nonce + current_user_can( 'edit_post', $post_id ).
 *   • اگر فیلد «artist» را افزونهٔ ACF مدیریت می‌کند و نمی‌خواهید همگام شود:
 *       add_filter( 'javan_seda_sync_artist_meta', '__return_false' );
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'javan_seda_artist_taxonomy_for_post' ) ) {
	/** تاکسونومیِ فعالِ هنرمند، اگر به این پست‌تایپ وصل باشد. */
	function javan_seda_artist_taxonomy_for_post( $post_type ) {
		$tax = function_exists( 'javan_seda_artist_tax' ) ? javan_seda_artist_tax() : 'artist';
		if ( ! taxonomy_exists( $tax ) ) {
			$tax = taxonomy_exists( 'singer' ) ? 'singer' : '';
		}
		if ( ! $tax ) {
			return '';
		}
		$obj = get_taxonomy( $tax );
		if ( ! $obj || ! isset( $obj->object_type ) || ! in_array( $post_type, (array) $obj->object_type, true ) ) {
			return '';
		}
		return $tax;
	}
}

if ( ! function_exists( 'javan_seda_add_artist_metabox' ) ) {
	function javan_seda_add_artist_metabox() {
		$screen  = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$post_type = $screen && isset( $screen->post_type ) ? $screen->post_type : 'post';
		if ( ! javan_seda_artist_taxonomy_for_post( $post_type ) ) {
			return;
		}
		add_meta_box(
			'javan-seda-artist',
			'هنرمندِ اثر',
			'javan_seda_render_artist_metabox',
			$post_type,
			'side',
			'high'
		);
	}
	add_action( 'add_meta_boxes', 'javan_seda_add_artist_metabox' );
}

if ( ! function_exists( 'javan_seda_render_artist_metabox' ) ) {
	function javan_seda_render_artist_metabox( $post ) {
		$tax = javan_seda_artist_taxonomy_for_post( $post->post_type );
		if ( ! $tax ) {
			return;
		}
		$terms = wp_get_object_terms( $post->ID, $tax );
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}
		wp_nonce_field( 'javan_seda_post_artist', 'javan_seda_post_artist_nonce' );
		/* وضعیتِ اولیه: بدونِ آن، «هیچ چیز انتخاب نشده» از «فرم اصلاً نمایش داده نشده» قابل تفکیک نیست */
		$original_ids = array();
		foreach ( $terms as $term ) {
			$original_ids[] = (int) $term->term_id;
		}
		?>
		<input type="hidden" name="ab_artist_original" value="<?php echo esc_attr( implode( ',', $original_ids ) ); ?>">
		<div class="ab-picker ab-picker--meta" data-ab-picker data-type="artist" data-mode="multi">
			<p class="ab-picker__hint">
				هنرمند را جست‌وجو و انتخاب کنید. با ذخیرهٔ نوشته،
				<code>artist</code> متنی هم با همین نام‌ها همگام می‌شود.
			</p>

			<ul class="ab-picker__chips" data-ab-picker-chips>
				<?php foreach ( $terms as $term ) : ?>
					<li class="ab-chip" data-term-id="<?php echo esc_attr( (int) $term->term_id ); ?>">
						<span class="ab-chip__img">
							<img src="<?php echo esc_url( javan_seda_artist_image_url( $term, 'thumbnail' ) ); ?>" alt="" width="28" height="28">
						</span>
						<span class="ab-chip__name"><?php echo esc_html( $term->name ); ?></span>
						<input type="hidden" name="ab_artist_ids[]" value="<?php echo esc_attr( (int) $term->term_id ); ?>">
						<button type="button" class="ab-chip__remove" data-ab-picker-remove aria-label="حذف">×</button>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="ab-picker__empty" data-ab-picker-empty<?php echo $terms ? ' hidden' : ''; ?>>
				هنوز هنرمندی انتخاب نشده است.
			</p>

			<input type="search" class="widefat" data-ab-picker-input
				placeholder="نام هنرمند…" autocomplete="off" aria-label="جست‌وجوی هنرمند">
			<div class="ab-picker__results" data-ab-picker-results hidden></div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'javan_seda_save_post_artists' ) ) {
	function javan_seda_save_post_artists( $post_id, $post = null ) {
		if ( ! $post ) {
			$post = get_post( $post_id );
		}
		if ( ! $post ) {
			return;
		}
		if ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['javan_seda_post_artist_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['javan_seda_post_artist_nonce'] ) ), 'javan_seda_post_artist' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$tax = javan_seda_artist_taxonomy_for_post( $post->post_type );
		if ( ! $tax ) {
			return;
		}

		$ids = array();
		if ( isset( $_POST['ab_artist_ids'] ) && is_array( $_POST['ab_artist_ids'] ) ) {
			foreach ( (array) wp_unslash( $_POST['ab_artist_ids'] ) as $raw ) {
				$id = absint( $raw );
				if ( $id && ! in_array( $id, $ids, true ) ) {
					$ids[] = $id;
				}
			}
		}

		/*
		 * گاردِ داده: اگر نه چیزی انتخاب شده و نه از قبل چیزی انتخاب بود، یعنی این فرم
		 * عملاً چیزی را تغییر نداده (مثلاً ذخیره از ویرایش سریع یا ویرایشگرِ کلاسیک در
		 * صفحه‌ای که جعبه نمایش داده نمی‌شود). در این حالت نباید متای متنیِ قدیمیِ
		 * `artist` — که ممکن است از ACF یا نسخه‌های قبل مانده باشد — پاک شود.
		 */
		$original = array();
		if ( isset( $_POST['ab_artist_original'] ) ) {
			foreach ( explode( ',', (string) wp_unslash( $_POST['ab_artist_original'] ) ) as $raw ) {
				$id = absint( trim( $raw ) );
				if ( $id ) {
					$original[] = $id;
				}
			}
		}
		if ( empty( $ids ) && empty( $original ) ) {
			return;
		}

		/* فقط شناسه‌هایی که واقعاً ترمِ همین تاکسونومی‌اند */
		$valid = array();
		foreach ( $ids as $id ) {
			$term = get_term( $id, $tax );
			if ( $term && ! is_wp_error( $term ) ) {
				$valid[] = (int) $term->term_id;
			}
		}

		wp_set_object_terms( $post_id, $valid, $tax, false );

		/* همگام‌سازیِ متای متنیِ قدیمی — تا single.php و کوئری‌های LIKE نشکنند */
		if ( apply_filters( 'javan_seda_sync_artist_meta', true, $post_id, $tax ) ) {
			$names = array();
			foreach ( $valid as $id ) {
				$term = get_term( $id, $tax );
				if ( $term && ! is_wp_error( $term ) ) {
					$names[] = $term->name;
				}
			}
			$text = implode( ', ', $names );
			if ( '' === $text ) {
				delete_post_meta( $post_id, 'artist' );
			} else {
				update_post_meta( $post_id, 'artist', $text );
			}
		}

		if ( function_exists( 'javan_seda_artist_views_flush' ) ) {
			javan_seda_artist_views_flush();
		}
	}
	add_action( 'save_post', 'javan_seda_save_post_artists', 20, 2 );
}
