<?php
/**
 * ab-logo-setting.php — کارتِ «لوگوی سایت» در پنل (نسخهٔ ۳.۸.۳)
 * =====================================================================
 * چرا این فایل؟
 *   فیلد «لوگو» از قدیم در «تنظیمات قالب → همگانی» بود (نوع upload در
 *   OptionTree)، ولی پیدا کردنش برای کاربر سخت بود. نسخهٔ ۳.۸.۲ کارتِ
 *   ساده‌ای در پیشخوان پنل گذاشت؛ این نسخه همان کارت را **دو حالته**
 *   می‌کند: لوگوی «روز» و لوگوی «شب».
 *
 * چرا دو لوگو؟
 *   لوگوی تیره روی هدر تیرهٔ حالت شب گم می‌شود. حالا هر حالت لوگوی خودش را
 *   دارد و اگر لوگوی شب خالی بماند، همان لوگوی روز استفاده می‌شود —
 *   یعنی هیچ سایتی با به‌روزرسانی این نسخه سفید/خالی نمی‌شود.
 *
 * ذخیره‌سازی: مستقیم در همان آرایهٔ `option_tree` که `ot_get_option()`
 * می‌خواند (`logo` و `logo_night`). فرمِ OptionTree دست نمی‌خورد.
 *
 * امنیت: فقط `manage_options`، همهٔ ورودی‌ها با nonce، آپلود با
 * `media_handle_upload` (بررسی نوع/اندازه توسط خودِ وردپرس).
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* توجه: `tara_logo_default()`، `tara_logo_url()` و `tara_logo_night_url()`
   در inc/tara-ui.php هستند (چون سربرگ و کشوی موبایل هم در سمت کاربر به
   آن‌ها نیاز دارند و آن فایل همیشه بار می‌شود). این‌جا فقط ذخیره و کارت. */

if ( ! function_exists( 'tara_logo_option_key' ) ) {
	/**
	 * کلیدِ گزینه برای هر حالت.
	 *
	 * @param string $variant day|night.
	 * @return string
	 */
	function tara_logo_option_key( $variant ) {
		return ( 'night' === $variant ) ? 'logo_night' : 'logo';
	}
}

if ( ! function_exists( 'tara_logo_save' ) ) {
	/**
	 * نوشتن آدرس لوگو در همان گزینه‌ای که قالب می‌خواند.
	 *
	 * @param string $url     آدرس تازه (رشتهٔ خالی = بازگشت به پیش‌فرض).
	 * @param string $variant day|night.
	 * @return bool
	 */
	function tara_logo_save( $url, $variant = 'day' ) {
		$option_id = function_exists( 'taranehyar_options_id' ) ? taranehyar_options_id() : 'option_tree';
		$options   = get_option( $option_id );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$options[ tara_logo_option_key( $variant ) ] = (string) $url;

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

		$variant = isset( $_POST['tara_logo_target'] ) ? sanitize_key( wp_unslash( (string) $_POST['tara_logo_target'] ) ) : 'day';
		$variant = ( 'night' === $variant ) ? 'night' : 'day';

		$redirect = add_query_arg(
			array(
				'page'        => function_exists( 'taranehyar_panel_slug' ) ? taranehyar_panel_slug() : 'taranehyar',
				'tara-logo-v' => $variant,
			),
			admin_url( 'admin.php' )
		);

		/* ۱) بازگردانی به پیش‌فرض */
		if ( ! empty( $_POST['tara_logo_reset'] ) ) {
			tara_logo_save( '', $variant );
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

		tara_logo_save( $url, $variant );
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

if ( ! function_exists( 'tara_logo_card_variant' ) ) {
	/**
	 * یک ستونِ کارت (روز یا شب) با فرمِ مستقل خودش.
	 *
	 * @param string $variant day|night.
	 * @param string $current آدرسِ فعلیِ همین حالت.
	 * @param bool   $is_std  آیا همان پیش‌فرضِ قالب است؟
	 */
	function tara_logo_card_variant( $variant, $current, $is_std ) {
		$is_night = ( 'night' === $variant );
		$id       = $is_night ? 'tara-logo-night' : 'tara-logo-day';
		$title    = $is_night ? 'لوگوی حالت شب' : 'لوگوی حالت روز';
		$hint     = $is_night
			? 'اگر خالی بماند، همان لوگوی روز در شب هم نشان داده می‌شود. لوگوی روشن/سفید بگذارید.'
			: 'لوگوی اصلی سایت؛ در سربرگ دسکتاپ و کشوی گوشی همین دیده می‌شود.';
		?>
		<div class="ty-logo-card__col<?php echo $is_night ? ' ty-logo-card__col--night' : ''; ?>">
			<div class="ty-logo-card__preview<?php echo $is_night ? ' is-night' : ''; ?>">
				<img src="<?php echo esc_url( $current ); ?>" alt="<?php echo esc_attr( $title ); ?>" width="150" height="40" loading="lazy" decoding="async">
			</div>
			<p class="ty-logo-card__caption">
				<strong><?php echo esc_html( $title ); ?></strong>
				<?php if ( $is_std ) : ?>
					<span class="ty-pill ty-pill--ok">پیش‌فرضِ قالب</span>
				<?php else : ?>
					<span class="ty-pill">سفارشی</span>
				<?php endif; ?>
			</p>
			<p class="ty-logo-card__hint"><?php echo esc_html( $hint ); ?></p>

			<form class="ty-logo-card__form" method="post" enctype="multipart/form-data"
				action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="tara_save_logo">
				<input type="hidden" name="tara_logo_target" value="<?php echo esc_attr( $variant ); ?>">
				<?php wp_nonce_field( 'tara_save_logo' ); ?>

				<p class="ty-logo-card__row">
					<label for="<?php echo esc_attr( $id . '-file' ); ?>">انتخاب فایل از رایانه</label>
					<input id="<?php echo esc_attr( $id . '-file' ); ?>" type="file" name="tara_logo_file" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif">
				</p>

				<p class="ty-logo-card__row">
					<label for="<?php echo esc_attr( $id . '-url' ); ?>">یا آدرس تصویر</label>
					<input id="<?php echo esc_attr( $id . '-url' ); ?>" type="url" name="tara_logo_url" placeholder="https://example.com/logo.png" dir="ltr">
				</p>

				<p class="ty-logo-card__actions">
					<button type="submit" class="ty-btn ty-btn--primary">
						<span class="dashicons dashicons-saved"></span>
						ذخیرهٔ <?php echo $is_night ? 'لوگوی شب' : 'لوگو'; ?>
					</button>
					<button type="submit" name="tara_logo_reset" value="1" class="ty-btn">
						<span class="dashicons dashicons-image-rotate"></span>
						بازگردانی
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}

if ( ! function_exists( 'tara_logo_card' ) ) {
	/**
	 * کارتِ «لوگو سایت» برای پیشخوان پنل — دو حالته (روز/شب).
	 */
	function tara_logo_card() {
		if ( ! function_exists( 'tara_logo_url' ) || ! function_exists( 'tara_logo_night_url' ) ) {
			return;
		}
		$day       = tara_logo_url();
		$night     = tara_logo_night_url();
		$day_std   = ( $day === tara_logo_default() );
		$night_std = ( '' === tara_logo_raw( 'logo_night' ) );
		?>
		<h2 class="ty-heading">
			لوگوی سایت — روز و شب
			<span class="ty-heading__hint">همین‌جا عوضش کنید — نیازی به رفتن به تنظیمات نیست</span>
		</h2>

		<div class="ty-card ty-card--wide ty-logo-card" dir="rtl">
			<div class="ty-logo-card__grid">
				<?php
				tara_logo_card_variant( 'day', $day, $day_std );
				tara_logo_card_variant( 'night', $night, $night_std );
				?>
			</div>

			<p class="ty-logo-card__hint ty-logo-card__hint--foot">
				اندازهٔ پیشنهادی هر دو لوگو: <strong>۳۰۰×۸۰ پیکسل</strong> (دو برابرِ نمایشِ
				۱۵۰×۴۰ برای صفحه‌های رِتینا) — PNG یا SVG با پس‌زمینهٔ شفاف. نسبتِ ۱۵۰:۴۰ را
				نگه دارید تا در سربرگ و کشوی گوشی بدون کشیدگی بنشیند. فایل‌ها در
				«کتابخانهٔ رسانه» ذخیره می‌شوند.
			</p>
		</div>
		<?php
	}
}
