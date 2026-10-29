<?php
/**
 * ab-vip-image.php — بنرِ «اسلایدر ویژه» در صفحهٔ ویرایش نوشته (نسخهٔ ۳.۸.۳)
 * =========================================================================
 * چرا این فایل؟
 *   بنرِ اسلایدهای «ویژه» تا امروز فقط از فیلدِ ACF با نام `vip_img` می‌آمد و
 *   توضیحِ خودِ آن فیلد روی سرور «۶۴۰×۳۶۰» نوشته شده بود؛ یعنی در عمل عکسِ
 *   کوچکی وسطِ کادری ۱۶:۷ کشیده/برش می‌خورد. کادرِ اسلایدر در ۳.۸.۲ نسبتِ
 *   استاندارد ۱۶:۷ گرفت و اندازهٔ درستش **۱۹۲۰×۸۴۰** است.
 *
 * چه می‌کند؟
 *   • یک جعبهٔ تازه در صفحهٔ ویرایش نوشته: «بنر اسلایدر ویژه — ۱۹۲۰×۸۴۰».
 *   • آپلود/انتخاب از کتابخانهٔ رسانه + پیش‌نمایش + حذف.
 *   • مقدار در متای `_tara_vip_banner` (شناسهٔ پیوست) ذخیره می‌شود.
 *
 * چرا کلیدِ تازه و نه همان `vip_img`؟
 *   فیلدِ ACF هنگامِ ذخیرهٔ نوشته مقدارِ خودش را روی همان کلید می‌نویسد و
 *   اگر ما هم به `vip_img` بنویسیم، یکی از دو مقدار بی‌صدا پاک می‌شود. با
 *   کلیدِ تازه هیچ تداخلی نیست؛ `inc/vip_slider.php` اول این کلید را
 *   می‌خواند و اگر خالی بود همان `vip_img` قدیمی را.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_vip_meta_key' ) ) {
	/** کلیدِ متای بنرِ ویژه. */
	function tara_vip_meta_key() {
		return '_tara_vip_banner';
	}
}

if ( ! function_exists( 'tara_vip_banner_id' ) ) {
	/**
	 * شناسهٔ بنرِ ویژهٔ یک نوشته (یا ۰).
	 *
	 * @param int $post_id شناسه.
	 * @return int
	 */
	function tara_vip_banner_id( $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		if ( ! $post_id ) {
			return 0;
		}

		return absint( get_post_meta( $post_id, tara_vip_meta_key(), true ) );
	}
}

if ( ! function_exists( 'tara_vip_metabox' ) ) {
	/** ثبتِ جعبه در صفحهٔ ویرایش نوشته. */
	function tara_vip_metabox() {
		add_meta_box(
			'tara-vip-banner',
			'بنر اسلایدر ویژه — ۱۹۲۰×۸۴۰',
			'tara_vip_metabox_render',
			'post',
			'normal',
			'default'
		);
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'add_meta_boxes', 'tara_vip_metabox' );
}

if ( ! function_exists( 'tara_vip_metabox_render' ) ) {
	/**
	 * بدنهٔ جعبه.
	 *
	 * @param WP_Post $post نوشتهٔ جاری.
	 */
	function tara_vip_metabox_render( $post ) {
		wp_nonce_field( 'tara_vip_banner', 'tara_vip_banner_nonce' );

		$id  = tara_vip_banner_id( $post->ID );
		$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
		$is_vip = (string) get_post_meta( $post->ID, 'vip_song', true );
		?>
		<div class="tara-vip-box" data-tara-vip>
			<div class="tara-vip-box__preview">
				<?php if ( $src ) : ?>
					<img data-tara-vip-img src="<?php echo esc_url( $src ); ?>" alt="" width="320" height="140">
				<?php else : ?>
					<img data-tara-vip-img src="" alt="" width="320" height="140" hidden>
					<span class="tara-vip-box__empty" data-tara-vip-empty>هنوز بنری انتخاب نشده است</span>
				<?php endif; ?>
			</div>

			<div class="tara-vip-box__body">
				<input type="hidden" name="tara_vip_banner" data-tara-vip-input value="<?php echo esc_attr( $id ? $id : '' ); ?>">

				<p class="tara-vip-box__hint">
					<strong>اندازه: ۱۹۲۰ × ۸۴۰ پیکسل</strong> (نسبت ۱۶:۷) — همان نسبتی که کادر اسلایدر
					روی سایت دارد، پس هیچ برشی نمی‌خورد. فرمت WebP با کیفیت ۸۰ و حجم زیر ۳۰۰ کیلوبایت
					بهترین نتیجه را می‌دهد. اگر بنر نگذارید، کاور آهنگ استفاده می‌شود.
				</p>

				<?php if ( '' !== $is_vip ) : ?>
					<p class="tara-vip-box__flag">
						<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
						این نوشته در اسلایدر ویژه است (متای <code>vip_song</code>).
					</p>
				<?php else : ?>
					<p class="tara-vip-box__flag tara-vip-box__flag--off">
						<span class="dashicons dashicons-star-empty" aria-hidden="true"></span>
						برای دیده‌شدن در اسلایدر ویژه، فیلد <code>vip_song</code> باید مقدار <code>1</code> داشته باشد.
					</p>
				<?php endif; ?>

				<p class="tara-vip-box__actions">
					<button type="button" class="button button-primary" data-tara-vip-pick>انتخاب / آپلود بنر</button>
					<button type="button" class="button" data-tara-vip-clear<?php echo $id ? '' : ' hidden'; ?>>حذف بنر</button>
				</p>

				<p class="tara-vip-box__hint tara-vip-box__hint--muted">
					فیلد قدیمی «تصویر ویژه» (اگر افزونهٔ ACF آن را نشان می‌دهد) اندازهٔ ۶۴۰×۳۶۰
					می‌خواهد و فقط زمانی به کار می‌آید که این‌جا بنری انتخاب نشده باشد.
				</p>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'tara_vip_metabox_save' ) ) {
	/**
	 * ذخیرهٔ شناسهٔ بنر.
	 *
	 * @param int $post_id شناسهٔ نوشته.
	 */
	function tara_vip_metabox_save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['tara_vip_banner_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['tara_vip_banner_nonce'] ) ), 'tara_vip_banner' ) ) {
			return;
		}

		$raw = isset( $_POST['tara_vip_banner'] ) ? absint( wp_unslash( $_POST['tara_vip_banner'] ) ) : 0;
		if ( $raw ) {
			update_post_meta( $post_id, tara_vip_meta_key(), $raw );
		} else {
			delete_post_meta( $post_id, tara_vip_meta_key() );
		}
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'save_post', 'tara_vip_metabox_save' );
}

if ( ! function_exists( 'tara_vip_metabox_assets' ) ) {
	/** اسکریپت/استایلِ جعبه، فقط در صفحهٔ ویرایش نوشته. */
	function tara_vip_metabox_assets( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		if ( ! function_exists( 'wp_enqueue_media' ) ) {
			return;
		}
		wp_enqueue_media();

		$js = get_template_directory() . '/js/ab-vip-image.js';
		if ( file_exists( $js ) ) {
			wp_enqueue_script(
				'tara-vip-image',
				get_template_directory_uri() . '/js/ab-vip-image.js',
				array( 'jquery' ),
				(string) filemtime( $js ),
				true
			);
			wp_localize_script(
				'tara-vip-image',
				'TaraVipImage',
				array(
					'title'  => 'بنر اسلایدر ویژه (۱۹۲۰×۸۴۰)',
					'button' => 'استفاده از این تصویر',
				)
			);
		}

		$css = get_template_directory() . '/css/ab-vip-image.css';
		if ( file_exists( $css ) ) {
			wp_enqueue_style(
				'tara-vip-image',
				get_template_directory_uri() . '/css/ab-vip-image.css',
				array(),
				(string) filemtime( $css )
			);
		}
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'admin_enqueue_scripts', 'tara_vip_metabox_assets' );
}
