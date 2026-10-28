<?php
/**
 * پوستهٔ تازهٔ صفحهٔ «تنظیمات قالب» — نسخهٔ ۳.۷.۰
 * ==========================================================================
 * چه مشکلی را حل می‌کند؟
 *   صفحهٔ تنظیماتِ OptionTree با مارکاپِ ویرایشگرِ نوشته رندر می‌شود
 *   (`#poststuff` · `#post-body` · `.postbox`) و روی سایت شما «شبیه یک برگه»
 *   دیده می‌شد: کارت‌های خاکستری، تب‌های ریز، و فیلدهای روی هم.
 *
 * چه کاری می‌کند؟
 *   • فقط روی همان یک صفحه (`taranehyar_page_ot-theme-options`) یک فایل CSS و
 *     یک فایل JS بار می‌کند؛ هیچ صفحهٔ دیگری، هیچ تغییری.
 *   • هیچ رفتاری از OptionTree عوض نمی‌شود: تب‌ها همان jQuery UI هستند، فرم
 *     همان `options.php` است و ذخیره‌سازی همان است. فقط ظاهر و مرتب‌سازی.
 *   • یک نوار جست‌وجوی فیلد + لینک «بازگشت به پنل» + پشتیبانی از
 *     `?section=ab_ui` برای بازشدن مستقیم روی یک تب اضافه می‌کند.
 *
 * خاموش‌کردن: تنظیمات قالب → کنترل رابط → «پوستۀ همین صفحه» = خاموش
 *             یا define( 'TARANEHYAR_SETTINGS_SKIN', false ); در wp-config.php
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_settings_shell_on' ) ) {
	/**
	 * آیا پوستهٔ تازهٔ صفحهٔ تنظیمات روشن است؟
	 *
	 * @return bool
	 */
	function tara_settings_shell_on() {
		if ( defined( 'TARANEHYAR_SETTINGS_SKIN' ) ) {
			return (bool) TARANEHYAR_SETTINGS_SKIN;
		}

		if ( function_exists( 'ot_get_option' ) ) {
			$value = ot_get_option( 'ab_admin_ui' );
			if ( is_string( $value ) && '' !== $value ) {
				return 'off' !== $value;
			}
		}

		return true;
	}
}

if ( ! function_exists( 'tara_settings_shell_screen' ) ) {
	/**
	 * شناسهٔ صفحهٔ تنظیماتِ همین قالب.
	 *
	 * @return string
	 */
	function tara_settings_shell_screen() {
		return 'taranehyar_page_ot-theme-options';
	}
}

if ( ! function_exists( 'tara_settings_shell_assets' ) ) {
	/**
	 * بارگذاری CSS/JS — فقط روی همان صفحه.
	 *
	 * @param string $hook شناسهٔ صفحهٔ جاری.
	 */
	function tara_settings_shell_assets( $hook ) {
		/* فقط همان یک صفحه — مستقل از اینکه از کدام هوک صدا زده شود. */
		if ( tara_settings_shell_screen() !== $hook ) {
			return;
		}

		if ( ! tara_settings_shell_on() ) {
			return;
		}

		$css = TARANEHYAR_DIR . '/css/admin-settings.css';
		$js  = TARANEHYAR_DIR . '/js/admin-settings.js';

		if ( file_exists( $css ) ) {
			wp_enqueue_style( 'taranehyar-settings', TARANEHYAR_URI . '/css/admin-settings.css', array(), TARANEHYAR_VERSION );
		}
		if ( file_exists( $js ) ) {
			wp_enqueue_script( 'taranehyar-settings', TARANEHYAR_URI . '/js/admin-settings.js', array( 'jquery' ), TARANEHYAR_VERSION, true );
			wp_localize_script(
				'taranehyar-settings',
				'taraSettings',
				array(
					'panelUrl' => admin_url( 'admin.php?page=taranehyar' ),
					'section'  => isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '',
					'i18n'     => array(
						'back'  => 'بازگشت به پنل',
						'find'  => 'جست‌وجوی تنظیم…',
						'empty' => 'چیزی با این نام پیدا نشد.',
					),
				)
			);
		}
	}
	add_action( 'admin_enqueue_scripts', 'tara_settings_shell_assets', 20 );
}

if ( ! function_exists( 'tara_settings_shell_body_class' ) ) {
	/**
	 * یک کلاس روی <body> می‌گذارد تا CSS فقط در همین صفحه اثر کند.
	 *
	 * @param string[] $classes کلاس‌های فعلی.
	 * @return string[]
	 */
	function tara_settings_shell_body_class( $classes ) {
		if ( ! tara_settings_shell_on() ) {
			return $classes;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$id     = ( is_object( $screen ) && isset( $screen->id ) ) ? (string) $screen->id : '';

		if ( tara_settings_shell_screen() === $id ) {
			$classes[] = 'tara-settings-screen';
		}

		return $classes;
	}
	add_filter( 'admin_body_class', 'tara_settings_shell_body_class' );
}

if ( ! function_exists( 'tara_settings_shell_title' ) ) {
	/**
	 * یک سرتیتر کوتاه و مفید بالای فرم (بدون تغییر در ساختار فرم).
	 */
	function tara_settings_shell_title() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$id     = ( is_object( $screen ) && isset( $screen->id ) ) ? (string) $screen->id : '';

		if ( tara_settings_shell_screen() !== $id || ! tara_settings_shell_on() ) {
			return;
		}
		?>
		<div class="tara-settings-intro">
			<div class="tara-settings-intro__text">
				<h1>تنظیمات ترانه یار</h1>
				<p>هر تب یک بخش از قالب است. تغییرها با دکمهٔ «ذخیرهٔ تغییرات» ثبت می‌شوند و در همان کلید <code><?php echo esc_html( function_exists( 'taranehyar_options_id' ) ? taranehyar_options_id() : 'option_tree' ); ?></code> می‌مانند — هیچ تنظیمی جابه‌جا نمی‌شود.</p>
			</div>
			<a class="tara-settings-intro__back" href="<?php echo esc_url( admin_url( 'admin.php?page=taranehyar' ) ); ?>">
				<span class="dashicons dashicons-arrow-right-alt"></span>
				بازگشت به پنل
			</a>
		</div>
		<?php
	}
	add_action( 'admin_notices', 'tara_settings_shell_title', 5 );
}
