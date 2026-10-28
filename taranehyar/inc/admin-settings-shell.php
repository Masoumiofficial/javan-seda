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

		/* v3.7.4 — گزینهٔ قدیمی «پوستۀ همین صفحه» دیگر پوسته را خاموش نمی‌کند.
		   دلیل: اگر یک بار روی «خاموش» می‌ماند، صفحه به ظاهر خام Option Tree
		   برمی‌گشت و کاربر فکر می‌کرد آپدیت اعمال نشده است. برای خاموش‌کردن،
		   define( 'TARANEHYAR_SETTINGS_SKIN', false ) در wp-config.php. */
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

if ( ! function_exists( 'tara_settings_shell_is_page' ) ) {
	/**
	 * آیا همین حالا روی صفحهٔ «تنظیمات قالب» هستیم؟
	 *
	 * سه راه بررسی می‌شود تا اگر نام منو/پدرِ صفحه عوض شد (که شناسهٔ صفحه را
	 * عوض می‌کند) پوسته از کار نیفتد:
	 *   ۱) شناسهٔ صفحهٔ استاندارد
	 *   ۲) پارامتر آدرس `?page=ot-theme-options`
	 *   ۳) بودنِ ریشهٔ مارکاپ خودِ Option Tree در صفحه
	 *
	 * @return bool
	 */
	function tara_settings_shell_is_page() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$id     = ( is_object( $screen ) && isset( $screen->id ) ) ? (string) $screen->id : '';

		if ( tara_settings_shell_screen() === $id ) {
			return true;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- فقط خواندن برای نمایش
		if ( 'ot-theme-options' === $page ) {
			return true;
		}

		return ( false !== strpos( $id, 'ot-theme-options' ) && false !== strpos( $id, 'taranehyar' ) );
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
	function tara_settings_shell_assets( $hook = '' ) {
		/* فقط همان یک صفحه — با سه راه تشخیص (شناسهٔ صفحه، پارامتر آدرس، …) */
		if ( tara_settings_shell_screen() !== $hook && ! tara_settings_shell_is_page() ) {
			return;
		}


		if ( ! tara_settings_shell_on() ) {
			return;
		}

		$css = TARANEHYAR_DIR . '/css/admin-settings.css';
		$js  = TARANEHYAR_DIR . '/js/admin-settings.js';

		if ( file_exists( $css ) ) {
			wp_enqueue_style( 'taranehyar-settings', TARANEHYAR_URI . '/css/admin-settings.css', array(), function_exists( 'taranehyar_asset_ver' ) ? taranehyar_asset_ver( '/css/admin-settings.css' ) : TARANEHYAR_VERSION );
			/* آیکون‌های پیشخوان برای فهرست بخش‌ها */
			wp_enqueue_style( 'dashicons' );
		}
		if ( file_exists( $js ) ) {
			wp_enqueue_script( 'taranehyar-settings', TARANEHYAR_URI . '/js/admin-settings.js', array(), function_exists( 'taranehyar_asset_ver' ) ? taranehyar_asset_ver( '/js/admin-settings.js' ) : TARANEHYAR_VERSION, true );
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

	/*
	 * تضمین دوم (v3.7.4): اگر به هر دلیلی enqueue بالا چاپ نشد (کش افزونه‌های
	 * بهینه‌سازی، فیلتر سوم‌شخص، یا شناسهٔ صفحهٔ متفاوت)، خودمان تگ‌ها را
	 * مستقیم چاپ می‌کنیم. CSS در سر صفحه، JS در پانوشت. این کار فقط روی
	 * همان صفحه انجام می‌شود و اگر قبلاً چاپ شده باشد تکرار نمی‌شود.
	 */
	if ( ! function_exists( 'tara_settings_shell_hard_print' ) ) {
		function tara_settings_shell_hard_print( $where = 'css' ) {
			static $done = array();

			if ( ! tara_settings_shell_is_page() || ! tara_settings_shell_on() ) {
				return;
			}

			if ( empty( $done[ $where ] ) ) {
				$done[ $where ] = true;
			} else {
				return;
			}

			if ( 'css' === $where ) {
				if ( wp_style_is( 'taranehyar-settings', 'done' ) ) {
					return;
				}
				if ( ! file_exists( TARANEHYAR_DIR . '/css/admin-settings.css' ) ) {
					return;
				}
				echo '<link rel="stylesheet" id="taranehyar-settings-css" href="' . esc_url( add_query_arg( 'ver', rawurlencode( TARANEHYAR_VERSION . '.' . filemtime( TARANEHYAR_DIR . '/css/admin-settings.css' ) ), TARANEHYAR_URI . '/css/admin-settings.css' ) ) . '" media="all" />' . "\n";
				return;
			}

			if ( wp_script_is( 'taranehyar-settings', 'done' ) ) {
				return;
			}
			if ( ! file_exists( TARANEHYAR_DIR . '/js/admin-settings.js' ) ) {
				return;
			}
			echo '<script id="taranehyar-settings-js" src="' . esc_url( add_query_arg( 'ver', rawurlencode( TARANEHYAR_VERSION . '.' . filemtime( TARANEHYAR_DIR . '/js/admin-settings.js' ) ), TARANEHYAR_URI . '/js/admin-settings.js' ) ) . '"></script>' . "\n";
		}
	}
	add_action( 'admin_print_styles', 'tara_settings_shell_hard_print', 999 );
	add_action( 'admin_print_footer_scripts', 'tara_settings_shell_hard_print', 999 );
}

if ( ! function_exists( 'tara_settings_shell_body_class' ) ) {
	/**
	 * یک کلاس روی <body> می‌گذارد تا CSS فقط در همین صفحه اثر کند.
	 *
	 * @param string[] $classes کلاس‌های فعلی.
	 * @return string[]
	 */
	function tara_settings_shell_body_class( $classes ) {
		if ( ! tara_settings_shell_on() || ! tara_settings_shell_is_page() ) {
			return $classes;
		}

		/*
		 * bug واقعی ۳.۷.۰–۳.۷.۳: هستهٔ وردپرس این فیلتر را با **رشته** صدا می‌زند
		 * (`' wp-admin wp-core-ui …'`) و `$classes[] = …` روی رشته در PHP 8 خطای
		 * کشنده می‌دهد. آزمون‌ها آرایه می‌فرستادند، پس باگ دیده نمی‌شد.
		 * حالا هر دو حالت پشتیبانی می‌شود.
		 */
		if ( is_array( $classes ) ) {
			if ( ! in_array( 'tara-settings-screen', $classes, true ) ) {
				$classes[] = 'tara-settings-screen';
			}
			return $classes;
		}

		$classes = (string) $classes;
		if ( false === strpos( $classes, 'tara-settings-screen' ) ) {
			$classes .= ' tara-settings-screen';
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
		if ( ! tara_settings_shell_is_page() || ! tara_settings_shell_on() ) {
			return;
		}

		$store = function_exists( 'taranehyar_options_id' ) ? taranehyar_options_id() : 'option_tree';
		$ver   = defined( 'TARANEHYAR_VERSION' ) ? TARANEHYAR_VERSION : '—';
		$css   = defined( 'TARANEHYAR_DIR' ) && file_exists( TARANEHYAR_DIR . '/css/admin-settings.css' );
		$js    = defined( 'TARANEHYAR_DIR' ) && file_exists( TARANEHYAR_DIR . '/js/admin-settings.js' );
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
			<div class="tara-settings-hero__art" aria-hidden="true">
				<span class="tara-settings-hero__ver">نسخهٔ <?php echo esc_html( $ver ); ?></span>
				<span class="dashicons dashicons-admin-settings"></span>
			</div>
		</div>
		<div class="tara-settings-status" role="status">
			<span class="tara-settings-status__item<?php echo $css ? ' is-ok' : ' is-bad'; ?>">
				<span class="dashicons <?php echo $css ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
				فایل ظاهر (CSS)
			</span>
			<span class="tara-settings-status__item<?php echo $js ? ' is-ok' : ' is-bad'; ?>">
				<span class="dashicons <?php echo $js ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
				فایل رفتار (JS)
			</span>
			<span class="tara-settings-status__item is-ok">
				<span class="dashicons dashicons-database-view"></span>
				کلید ذخیره: <code><?php echo esc_html( $store ); ?></code>
			</span>
			<span class="tara-settings-status__item">
				<span class="dashicons dashicons-editor-help"></span>
				اگر این کارت را می‌بینید، یعنی فایل‌های نسخهٔ تازه روی سایت هستند.
			</span>
		</div>
		<?php
	}
	add_action( 'admin_notices', 'tara_settings_shell_title', 5 );
}

if ( ! function_exists( 'tara_settings_persian_switches' ) ) {
	/**
	 * برچسب‌های کلید روشن/خاموش را فارسی می‌کند.
	 *
	 * OptionTree خودش «On» و «Off» را با فیلتر می‌دهد؛ پس بدون دست‌زدن به فایل‌های
	 * خودش، فقط برچسب عوض می‌شود. مقدارِ ذخیره‌شده همان `on`/`off` می‌ماند، پس
	 * هیچ تنظیمی جابه‌جا نمی‌شود.
	 */
	function tara_settings_persian_switches() {
		add_filter(
			'ot_on_off_switch_on_label',
			function () {
				return 'روشن';
			}
		);
		add_filter(
			'ot_on_off_switch_off_label',
			function () {
				return 'خاموش';
			}
		);
	}
	add_action( 'init', 'tara_settings_persian_switches', 20 );
}

if ( ! function_exists( 'tara_settings_persian_strings' ) ) {
	/**
	 * رشته‌های انگلیسیِ خودِ OptionTree در همین صفحه فارسی می‌شوند.
	 *
	 * @param string $translated ترجمهٔ فعلی.
	 * @param string $text       متن اصلی.
	 * @param string $domain     دامنه.
	 * @return string
	 */
	function tara_settings_persian_strings( $translated, $text, $domain ) {
		if ( 'option-tree' !== $domain ) {
			return $translated;
		}

		$map = array(
			'Save Changes'        => 'ذخیرهٔ تغییرات',
			'Save'                => 'ذخیره',
			'New Layout'          => 'چیدمان تازه',
			'Reset'               => 'بازگردانی',
			'Import'              => 'درون‌ریزی',
			'Export'              => 'برون‌بری',
			'Settings'            => 'تنظیمات',
			'On'                  => 'روشن',
			'Off'                 => 'خاموش',
			'Add Media'           => 'افزودن فایل',
			'Remove Media'        => 'حذف فایل',
			'Add Slide'           => 'افزودن اسلاید',
			'Edit Slide'          => 'ویرایش اسلاید',
			'Delete Slide'        => 'حذف اسلاید',
			'Add Gallery'         => 'افزودن گالری',
			'Edit Gallery'        => 'ویرایش گالری',
			'Delete Gallery'      => 'حذف گالری',
			'Create Gallery'      => 'ساخت گالری',
			'Today'               => 'امروز',
			'Now'                 => 'اکنون',
			'Close'               => 'بستن',
			'Image'               => 'تصویر',
			'Featured Image'      => 'تصویر شاخص',
			'Send to OptionTree'  => 'انتخاب فایل',
			'Are you sure?'       => 'مطمئن هستید؟',
		);

		if ( isset( $map[ $text ] ) && function_exists( 'tara_settings_shell_is_page' ) && tara_settings_shell_is_page() ) {
			return $map[ $text ];
		}

		return $translated;
	}
	add_filter( 'gettext', 'tara_settings_persian_strings', 20, 3 );
}
