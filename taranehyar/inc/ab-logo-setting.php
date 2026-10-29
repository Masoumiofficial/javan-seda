<?php
/**
 * ab-logo-setting.php — کارتِ «لوگو سایت» در پنل (نسخهٔ ۳.۸.۲)
 * =====================================================================
 * چرا این فایل؟
 *   فیلد «لوگو» از قدیم در «تنظیمات قالب → همگانی» بود (نوع upload در
 *   OptionTree)، ولی پیدا کردنش برای کاربر سخت بود؛ کارتِ تنظیمات تازه هم
 *   فقط لینک به آن تب می‌داد. نتیجه این بود که کاربر می‌گفت «امکان تغییر
 *   لوگو را فراهم کن» در حالی که فیلدش وجود داشت.
 *
 * چه می‌کند؟
 *   • یک کارت در پیشخوانِ «ترانه یار» با پیش‌نمایشِ لوگوی فعلی.
 *   • آپلودِ فایل (به کتابخانهٔ رسانه می‌رود، پس بعداً هم قابل استفاده است)
 *     یا وارد کردنِ آدرسِ تصویر.
 *   • «بازگردانی به لوگوی پیش‌فرضِ قالب».
 *
 * چرا نه در همان صفحهٔ تنظیمات؟ چون OptionTree فیلد را با فرمِ خودش ذخیره
 * می‌کند و دست‌کاری آن ریسک دارد. این کارت مقدار را مستقیم در همان
 * آرایهٔ `option_tree` می‌نویسد (همان کلیدی که `ot_get_option('logo')`
 * می‌خواند) و بعد از ذخیره، به همان صفحه برمی‌گردد.
 *
 * امنیت: فقط `manage_options`، همهٔ ورودی‌ها با nonce، آپلود با
 * `media_handle_upload` (بررسی نوع/اندازه توسط خودِ وردپرس).
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* توجه: `tara_logo_default()` و `tara_logo_url()` در inc/tara-ui.php هستند
   (چون سربرگ و کشوی موبایل هم در سمت کاربر به آن‌ها نیاز دارند و آن فایل
   همیشه بار می‌شود). این‌جا فقط ذخیره و کارت پنل است. */

if ( ! function_exists( 'tara_logo_save' ) ) {
	/**
	 * نوشتن آدرس لوگو در همان گزینه‌ای که قالب می‌خواند.
	 *
	 * @param string $url آدرس تازه (رشتهٔ خالی = بازگشت به پیش‌فرض).
	 * @return bool
	 */
	function tara_logo_save( $url ) {
		$option_id = function_exists( 'taranehyar_options_id' ) ? taranehyar_options_id() : 'option_tree';
		$options   = get_option( $option_id );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$options['logo'] = (string) $url;

		return (bool) update_option( $option_id, $options );
	}
}

if ( ! function_exists( 'tara_logo_admin_handle' ) ) {
	/**
	 * پردازشِ فرمِ کارت لوگو (`admin-post.php?action=tara_save_logo`).
	 */
	function tara_logo_admin_handle() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی ندارید.' );
		}

		check_admin_referer( 'tara_save_logo' );

		$redirect = add_query_arg(
			array( 'page' => function_exists( 'taranehyar_panel_slug' ) ? taranehyar_panel_slug() : 'taranehyar' ),
			admin_url( 'admin.php' )
		);

		/* ۱) بازگردانی به پیش‌فرض */
		if ( ! empty( $_POST['tara_logo_reset'] ) ) {
			tara_logo_save( '' );
			wp_safe_redirect( add_query_arg( 'tara-logo', 'reset', $redirect ) );
			exit;
		}

		$url = '';

		/* ۲) آپلود فایل — به کتابخانهٔ رسانه می‌رود */
		if ( ! empty( $_FILES['tara_logo_file']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$attachment_id = media_handle_upload( 'tara_logo_file', 0 );
			if ( is_wp_error( $attachment_id ) ) {
				wp_safe_redirect( add_query_arg( 'tara-logo', 'error', $redirect ) );
				exit;
			}
			$url = (string) wp_get_attachment_url( $attachment_id );
		} elseif ( isset( $_POST['tara_logo_url'] ) ) {
			/* ۳) آدرس دستی */
			$url = esc_url_raw( wp_unslash( (string) $_POST['tara_logo_url'] ) );
		}

		if ( '' === $url ) {
			wp_safe_redirect( add_query_arg( 'tara-logo', 'error', $redirect ) );
			exit;
		}

		tara_logo_save( $url );
		wp_safe_redirect( add_query_arg( 'tara-logo', 'saved', $redirect ) );
		exit;
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'admin_post_tara_save_logo', 'tara_logo_admin_handle' );
}

if ( ! function_exists( 'tara_logo_notice' ) ) {
	/**
	 * پیامِ نتیجهٔ ذخیره — فقط وقتی پارامترش در آدرس باشد.
	 */
	function tara_logo_notice() {
		$state = isset( $_GET['tara-logo'] ) ? sanitize_key( wp_unslash( $_GET['tara-logo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- فقط نمایش
		if ( '' === $state ) {
			return;
		}

		$map = array(
			'saved' => array( 'notice-success', 'لوگو ذخیره شد.' ),
			'reset' => array( 'notice-success', 'لوگو به پیش‌فرضِ قالب برگشت.' ),
			'error' => array( 'notice-error', 'ذخیره نشد: فایل یا آدرس معتبر نبود.' ),
		);
		if ( ! isset( $map[ $state ] ) ) {
			return;
		}
		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $map[ $state ][0] ),
			esc_html( $map[ $state ][1] )
		);
	}
}

if ( ! function_exists( 'tara_logo_card' ) ) {
	/**
	 * کارتِ «لوگو سایت» برای پیشخوان پنل.
	 */
	function tara_logo_card() {
		if ( ! function_exists( 'tara_logo_url' ) ) {
			return;
		}
		$current = tara_logo_url();
		$is_std  = ( $current === tara_logo_default() );
		?>
		<h2 class="ty-heading">
			لوگوی سایت
			<span class="ty-heading__hint">همین‌جا عوضش کنید — نیازی به رفتن به تنظیمات نیست</span>
		</h2>

		<div class="ty-card ty-card--wide ty-logo-card" dir="rtl">
			<div class="ty-logo-card__preview">
				<img src="<?php echo esc_url( $current ); ?>" alt="لوگوی فعلی سایت" width="150" height="40">
				<?php if ( $is_std ) : ?>
					<span class="ty-pill ty-pill--ok">پیش‌فرضِ قالب</span>
				<?php else : ?>
					<span class="ty-pill">لوگوی سفارشی</span>
				<?php endif; ?>
			</div>

			<form class="ty-logo-card__form" method="post" enctype="multipart/form-data"
				action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="tara_save_logo">
				<?php wp_nonce_field( 'tara_save_logo' ); ?>

				<p class="ty-logo-card__row">
					<label for="tara-logo-file">انتخاب فایل از رایانه</label>
					<input id="tara-logo-file" type="file" name="tara_logo_file" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif">
				</p>

				<p class="ty-logo-card__row">
					<label for="tara-logo-url">یا آدرس تصویر</label>
					<input id="tara-logo-url" type="url" name="tara_logo_url" placeholder="https://example.com/logo.png" dir="ltr">
				</p>

				<p class="ty-logo-card__actions">
					<button type="submit" class="ty-btn ty-btn--primary">
						<span class="dashicons dashicons-saved"></span>
						ذخیرهٔ لوگو
					</button>
					<button type="submit" name="tara_logo_reset" value="1" class="ty-btn">
						<span class="dashicons dashicons-image-rotate"></span>
						بازگردانی به پیش‌فرض
					</button>
				</p>

				<p class="ty-logo-card__hint">
					اندازهٔ پیشنهادی: <strong>۳۰۰×۸۰ پیکسل</strong> (دو برابرِ نمایشِ ۱۵۰×۴۰ برای صفحه‌های
					رِتینا) — PNG یا SVG با پس‌زمینهٔ شفاف. نسبتِ ۱۵۰:۴۰ را نگه دارید تا در سربرگ و
					کشوی گوشی بدون کشیدگی بنشیند. فایل در «کتابخانهٔ رسانه» ذخیره می‌شود.
				</p>
			</form>
		</div>
		<?php
	}
}
