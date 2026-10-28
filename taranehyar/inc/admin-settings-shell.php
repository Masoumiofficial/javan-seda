<?php
/**
 * پوستهٔ صفحهٔ «تنظیمات قالب» — نسخهٔ ۳.۷.۲
 * ==========================================================================
 * چه مشکلی را حل می‌کند؟
 *   صفحهٔ تنظیماتِ OptionTree با مارکاپِ ویرایشگرِ نوشته رندر می‌شود
 *   (`#poststuff` · `#post-body` · `.postbox`) و همان‌جا هم بدون پوست، «شبیه
 *   یک برگه» دیده می‌شد. این فایل همان یک صفحه را به یک صفحهٔ تنظیمات مدرن
 *   تبدیل می‌کند — با همان زبان بصری پنل «ترانه یار».
 *
 * چه کاری می‌کند؟
 *   • فقط روی همان صفحه (`taranehyar_page_ot-theme-options`) یک CSS و یک JS
 *     بار می‌کند؛ هیچ صفحهٔ دیگری از پیشخوان تغییر نمی‌کند.
 *   • هیچ رفتاری از OptionTree عوض نمی‌شود: تب‌ها همان jQuery UI هستند، فرم
 *     همان `options.php` است و ذخیره‌سازی همان است. فقط ظاهر و چیدمان.
 *   • سربرگ گرادیانی (مثل داشبورد پنل)، ستون فهرست بخش‌ها، نوار چسبان
 *     جست‌وجو/ذخیره و ردیف‌های فیلد خوانا اضافه می‌کند.
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

if ( ! function_exists( 'tara_settings_shell_sections' ) ) {
	/**
	 * عنوان و آیکون هر بخش — برای ستون فهرست و سرصفحهٔ کارت.
	 * کلیدها همان شناسهٔ بخش‌های inc/theme-options.php هستند.
	 *
	 * @return array
	 */
	function tara_settings_shell_sections() {
		$sections = array(
			'general' => array( 'title' => 'همگانی', 'icon' => 'dashicons-admin-generic', 'hint' => 'لوگو، نشانک، عنوان و رفتار پایهٔ سایت' ),
			'homes'   => array( 'title' => 'صفحه اصلی', 'icon' => 'dashicons-admin-home', 'hint' => 'اسلایدر، بخش‌ها و ترتیب مطالب صفحهٔ اصلی' ),
			'dl_pages' => array( 'title' => 'صفحه دانلود آهنگ', 'icon' => 'dashicons-download', 'hint' => 'چیدمان صفحهٔ آهنگ و دکمه‌های دانلود' ),
			'ab_ui'   => array( 'title' => 'کنترل رابط', 'icon' => 'dashicons-admin-appearance', 'hint' => 'کلیدهای روشن/خاموش ظاهر و لایه‌های سایت' ),
			'footer'  => array( 'title' => 'پانوشت', 'icon' => 'dashicons-editor-insertmore', 'hint' => 'متن، دسته‌ها و کپی‌رایت پایین سایت' ),
			'socials' => array( 'title' => 'شبکه‌های اجتماعی', 'icon' => 'dashicons-share', 'hint' => 'آدرس اینستاگرام، تلگرام و بقیه' ),
			'ads1'    => array( 'title' => 'تبلیغات', 'icon' => 'dashicons-megaphone', 'hint' => 'کدهای تبلیغاتی بالای سایت و کنار مطالب' ),
			'about'   => array( 'title' => 'درباره سایت', 'icon' => 'dashicons-info-outline', 'hint' => 'متنی که در پانوشت و صفحهٔ هنرمند دیده می‌شود' ),
		);

		return apply_filters( 'tara_settings_shell_sections', $sections );
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
			/* آیکون‌های پیشخوان برای فهرست بخش‌ها */
			wp_enqueue_style( 'dashicons' );
		}
		if ( file_exists( $js ) ) {
			wp_enqueue_script( 'taranehyar-settings', TARANEHYAR_URI . '/js/admin-settings.js', array(), TARANEHYAR_VERSION, true );
			wp_localize_script(
				'taranehyar-settings',
				'taraSettings',
				array(
					'panelUrl' => admin_url( 'admin.php?page=taranehyar' ),
					'siteUrl'  => home_url( '/' ),
					'section'  => isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '',
					'sections' => tara_settings_shell_sections(),
					'i18n'     => array(
						'back'    => 'بازگشت به پنل',
						'save'    => 'ذخیرهٔ تغییرات',
						'visit'   => 'دیدن سایت',
						'find'    => 'جست‌وجوی تنظیم…',
						'none'    => 'چیزی با این نام پیدا نشد. کلمهٔ کوتاه‌تری بنویسید.',
						'found'   => 'تنظیم پیدا شد',
						'searching' => 'جست‌وجو در همهٔ بخش‌ها',
					),
				)
			);
		}
	}
	add_action( 'admin_enqueue_scripts', 'tara_settings_shell_assets', 20 );

	/*
	 * OptionTree استایل خودش را روی `admin_print_styles-<screen>` چاپ می‌کند؛
	 * اگر ما هم همان‌جا (با اولویت بالا) صدا زده نشویم، فایل OT بعد از ما چاپ
	 * می‌شود و بعضی قاعده‌های قدیمی‌اش روی ظاهر تازه می‌نشیند. این هوک فقط
	 * تضمین می‌کند فایل ما آخرین نفر باشد. enqueue دوبارهٔ همان handle بی‌خطر است.
	 */
	if ( ! function_exists( 'tara_settings_shell_late_styles' ) ) {
		function tara_settings_shell_late_styles() {
			tara_settings_shell_assets( tara_settings_shell_screen() );
		}
	}
	add_action( 'admin_print_styles-' . tara_settings_shell_screen(), 'tara_settings_shell_late_styles', 99 );
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
	 * سربرگ صفحه — همان زبان بصری `.ty-hero` در داشبورد پنل.
	 */
	function tara_settings_shell_title() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$id     = ( is_object( $screen ) && isset( $screen->id ) ) ? (string) $screen->id : '';

		if ( tara_settings_shell_screen() !== $id || ! tara_settings_shell_on() ) {
			return;
		}

		$store = function_exists( 'taranehyar_options_id' ) ? taranehyar_options_id() : 'option_tree';
		?>
		<div class="tara-settings-hero">
			<div class="tara-settings-hero__text">
				<span class="tara-settings-hero__badge">تنظیمات قالب</span>
				<h1>تنظیمات ترانه یار</h1>
				<p>
					هر بخش در ستون کنار فهرست شده است؛ تغییرها را با «ذخیرهٔ تغییرات» ثبت کنید.
					همهٔ گزینه‌ها در همان کلید <code><?php echo esc_html( $store ); ?></code> می‌مانند،
					پس هیچ تنظیمی جابه‌جا نمی‌شود.
				</p>
				<div class="tara-settings-hero__actions">
					<button type="button" class="tara-hero-btn tara-hero-btn--primary" data-tara-save>
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'ذخیرهٔ تغییرات', 'taranehyar' ); ?>
					</button>
					<a class="tara-hero-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=taranehyar' ) ); ?>">
						<span class="dashicons dashicons-arrow-right-alt"></span>
						<?php esc_html_e( 'بازگشت به پنل', 'taranehyar' ); ?>
					</a>
					<a class="tara-hero-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">
						<span class="dashicons dashicons-external"></span>
						<?php esc_html_e( 'دیدن سایت', 'taranehyar' ); ?>
					</a>
				</div>
			</div>
			<span class="tara-settings-hero__art" aria-hidden="true">
				<span class="dashicons dashicons-admin-settings"></span>
			</span>
		</div>
		<?php
	}
	add_action( 'admin_notices', 'tara_settings_shell_title', 5 );
}
