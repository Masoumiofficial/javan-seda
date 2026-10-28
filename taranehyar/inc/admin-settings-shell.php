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
			'general' => array( 'title' => 'همگانی', 'icon' => 'dashicons-admin-generic', 'hint' => 'نشانک (فاویکون)، لوگو، عنوان و رفتار پایهٔ سایت' ),
			'brand'   => array( 'title' => 'رنگ‌ها و هویت', 'icon' => 'dashicons-art', 'hint' => 'رنگ اصلی و فرعی سایت، رنگ منو و جست‌وجوی گوشی' ),
			'homes'   => array( 'title' => 'صفحه اصلی', 'icon' => 'dashicons-admin-home', 'hint' => 'اسلایدر، بخش‌ها و ترتیب مطالب صفحهٔ اصلی' ),
			'dl_pages' => array( 'title' => 'صفحه دانلود آهنگ', 'icon' => 'dashicons-download', 'hint' => 'چیدمان صفحهٔ آهنگ و دکمه‌های دانلود' ),
			'ab_ui'   => array( 'title' => 'ظاهر و رفتار ترانه یار', 'icon' => 'dashicons-admin-appearance', 'hint' => 'پلیر، کارت‌ها، مود شب و جزئیات ظاهری همین قالب' ),
			'footer'  => array( 'title' => 'پانوشت', 'icon' => 'dashicons-editor-insertmore', 'hint' => 'متن، دسته‌ها و کپی‌رایت پایین سایت' ),
			'socials' => array( 'title' => 'شبکه‌های اجتماعی', 'icon' => 'dashicons-share', 'hint' => 'آدرس اینستاگرام، تلگرام و بقیه' ),
			'ads1'    => array( 'title' => 'تبلیغات', 'icon' => 'dashicons-megaphone', 'hint' => 'کدهای تبلیغاتی بالای سایت و کنار مطالب' ),
			'about'   => array( 'title' => 'درباره سایت', 'icon' => 'dashicons-info-outline', 'hint' => 'متنی که در پانوشت و صفحهٔ هنرمند دیده می‌شود' ),
			'dev'     => array( 'title' => 'توسعه دهنده', 'icon' => 'dashicons-editor-code', 'hint' => 'تنظیمات فنی و ریز — برای کاربر عادی لازم نیست' ),
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
		<?php tara_settings_shell_core_ui(); ?>
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

if ( ! function_exists( 'tara_settings_shell_core_ui' ) ) {
	/**
	 * هستهٔ کاربردیِ صفحهٔ تنظیمات — درون‌خطی و بی‌وابستگی (v3.7.5)
	 * =====================================================================
	 * چرا؟ گزارش کاربر: «همهٔ بخش‌ها با هم روی یک صفحه باز هستند و کلی فضا
	 * اشغال کرده‌اند». علتش این بود که جابه‌جاییِ بخش‌ها فقط به تب‌های
	 * jQuery UI (خودِ OptionTree) تکیه داشت؛ اگر آن اسکریپت بارگذاری یا
	 * مقداردهی نمی‌شد، همهٔ پنل‌ها هم‌زمان دیده می‌شدند.
	 *
	 * این تکه **درون خودِ صفحه** چاپ می‌شود (نه فایل بیرونی)، پس هیچ کش،
	 * فیلتر یا خطای اسکریپتی نمی‌تواند از کار بیندازدش:
	 *   • با کلیک هر تب، فقط پنل همان بخش دیده می‌شود
	 *   • هر گزینه یک ردیف جمع‌شونده است (پیش‌فرض باز) + «باز/بستن همه»
	 *   • آدرس صفحه با #section_x هم‌گام است، پس رفرش روی همان بخش می‌ماند
	 */
	function tara_settings_shell_core_ui() {
		?>
		<style id="tara-settings-core-css">
		/* ---------- چیدمان دوستونی: فهرست بخش‌ها کنار، محتوا روبه‌رویش ----------
		   v3.7.6 — پیش از این، فهرست بخش‌ها یک <ul> عمودیِ بی‌استایل بود و
		   محتوای هر بخش «زیر» تمام فهرست می‌افتاد. حالا در RTL فهرست سمت
		   راست ستون می‌شود و محتوای بخش دقیقاً روبه‌روی آن (ستون چپ) می‌آید. */
		#option-tree-settings-api .ui-tabs {
			display: grid !important;
			grid-template-columns: minmax(170px, 232px) minmax(0, 1fr);
			gap: 16px;
			align-items: start;
		}
		#option-tree-settings-api .ui-tabs-nav {
			grid-column: 1;
			display: flex !important;
			flex-direction: column;
			gap: 4px;
			margin: 0 !important;
			padding: 8px !important;
			list-style: none !important;
			border: 1px solid #e6e4ef;
			border-radius: 14px;
			background: #fff;
			position: sticky;
			top: 46px;
		}
		#option-tree-settings-api .ui-tabs-nav li { margin: 0 !important; width: 100%; }
		#option-tree-settings-api .ui-tabs-nav li a {
			display: block;
			padding: 9px 12px;
			border-radius: 10px;
			color: #4b4960 !important;
			font-size: 12.5px;
			font-weight: 700;
			text-decoration: none;
			box-shadow: none !important;
		}
		#option-tree-settings-api .ui-tabs-nav li a:hover { background: #f6f3ff; color: #4c1d95 !important; }
		#option-tree-settings-api .tara-settings-toolbar { grid-column: 1; margin: 0 !important; }
		#option-tree-settings-api #poststuff {
			grid-column: 2;
			grid-row: 1 / span 2;
			margin: 0 !important;
			min-width: 0;
			padding: 0 !important;
		}
		#option-tree-settings-api #poststuff .postbox,
		#option-tree-settings-api .ui-tabs-panel {
			margin: 0 !important;
			border: 0 !important;
			background: transparent !important;
			box-shadow: none !important;
		}
		@media (max-width: 960px) {
			#option-tree-settings-api .ui-tabs {
				grid-template-columns: 1fr;
			}
			#option-tree-settings-api .ui-tabs-nav {
				flex-direction: row;
				flex-wrap: wrap;
				position: static;
			}
			#option-tree-settings-api .ui-tabs-nav li { width: auto; }
			#option-tree-settings-api .tara-settings-toolbar { grid-column: 1; }
			#option-tree-settings-api #poststuff { grid-column: 1; grid-row: auto; }
		}

		/* فقط وقتی اسکریپت بالا اجرا و آماده شد پنل‌ها پنهان می‌شوند؛
		   اگر JS نبود، همان رفتار قبلی (همه باز) می‌ماند تا صفحه خالی نشود. */
		#option-tree-settings-api.tara-tabs-ready .ui-tabs-panel { display: none !important; }
		#option-tree-settings-api.tara-tabs-ready .ui-tabs-panel.tara-on { display: block !important; }
		#option-tree-settings-api .ui-tabs-nav li.ui-tabs-active > a,
		#option-tree-settings-api .ui-tabs-nav li.tara-on > a {
			background: #6d28d9 !important;
			color: #fff !important;
		}
		#option-tree-settings-api .tara-settings-toolbar {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			margin: 0 0 12px;
		}
		#option-tree-settings-api .tara-settings-toolbar button {
			padding: 6px 14px;
			border: 1px solid #e6e4ef;
			border-radius: 999px;
			background: #fff;
			color: #4b4960;
			font-size: 12px;
			font-weight: 700;
			cursor: pointer;
		}
		#option-tree-settings-api .tara-settings-toolbar button:hover {
			border-color: #6d28d9;
			color: #4c1d95;
		}
		#option-tree-settings-api .format-settings.has-label {
			margin: 0 0 8px;
			padding: 0;
			border: 1px solid #eeecf6;
			border-radius: 12px;
			background: #fff;
		}
		#option-tree-settings-api .format-settings.has-label > .format-setting-wrap > .format-setting-label {
			cursor: pointer;
			margin: 0;
			padding: 11px 14px;
			border-radius: 12px;
		}
		#option-tree-settings-api .format-settings.has-label > .format-setting-wrap > .format-setting-label:hover {
			background: #faf8ff;
		}
		#option-tree-settings-api .format-settings.has-label > .format-setting-wrap > .format-setting-label .label { margin: 0; }
		#option-tree-settings-api .format-settings.has-label > .format-setting-wrap > .format-setting-label::after {
			content: "▾";
			float: left;
			color: #8b8899;
			font-size: 13px;
		}
		#option-tree-settings-api .format-settings.has-label.is-open > .format-setting-wrap > .format-setting-label {
			border-bottom: 1px solid #f1eff8;
			border-radius: 12px 12px 0 0;
			background: #faf8ff;
		}
		#option-tree-settings-api .format-settings.has-label.is-open > .format-setting-wrap > .format-setting-label::after { content: "▴"; }
		#option-tree-settings-api .format-settings.has-label > .format-setting-wrap > *:not(.format-setting-label) { display: none; }
		#option-tree-settings-api .format-settings.has-label.is-open > .format-setting-wrap > *:not(.format-setting-label) { display: block; }
		#option-tree-settings-api .format-settings.has-label.is-open > .format-setting-wrap { padding-bottom: 6px; }

		/* ---------- آیتم‌های فهرستی (مثل «باکس مطالب») — v3.7.9 ----------
		   این‌ها مارکاپِ خودِ OptionTree هستند: هر آیتم یک سرتیتر (`.open`) و یک
		   بدنه دارد که تا امروز فقط با دکمهٔ «ویرایش» باز/بسته می‌شد و کاربر
		   فکر می‌کرد «قابل جمع‌شدن نیست». حالا سرتیتر خودش دکمهٔ باز/بستن است
		   (با فلش، هاور و دسترس‌پذیری) و شکل کارتِ مرتب گرفته است. */
		#option-tree-settings-api .option-tree-setting-wrap { margin: 0; padding: 0; list-style: none; }
		#option-tree-settings-api .list-list-item { margin: 0 0 8px; padding: 0; border: 0; background: transparent; }
		#option-tree-settings-api .option-tree-setting {
			border: 1px solid #ece9f7;
			border-radius: 12px;
			background: #fff;
			overflow: hidden;
		}
		#option-tree-settings-api .option-tree-setting > .open {
			position: relative;
			margin: 0;
			padding: 11px 40px 11px 14px;
			font-size: 13px;
			font-weight: 700;
			color: #3b3550;
			background: #fbfaff;
			cursor: pointer;
			user-select: none;
		}
		#option-tree-settings-api .option-tree-setting > .open:hover { background: #f5f1ff; color: #4c1d95; }
		#option-tree-settings-api .option-tree-setting > .open:focus-visible {
			outline: 2px solid #6d28d9;
			outline-offset: -2px;
		}
		#option-tree-settings-api .option-tree-setting > .open::after {
			content: "▾";
			position: absolute;
			inset-inline-start: 14px;
			top: 50%;
			transform: translateY(-50%);
			color: #8b8899;
			font-size: 12px;
			line-height: 1;
		}
		#option-tree-settings-api .option-tree-setting.tara-li-open > .open {
			background: #f5f1ff;
			color: #4c1d95;
			border-bottom: 1px solid #ece9f7;
		}
		#option-tree-settings-api .option-tree-setting.tara-li-open > .open::after { content: "▴"; }
		#option-tree-settings-api .option-tree-setting .button-section { padding: 0 14px 8px; background: transparent; }
		#option-tree-settings-api .option-tree-setting .option-tree-setting-body { padding: 4px 14px 12px; }
		#option-tree-settings-api .option-tree-setting .option-tree-setting-body .format-setting { border: 0; background: transparent; padding: 6px 0; }
		/* نوار ابزار بالای محتوا، نه زیر فهرست بخش‌ها */
		#option-tree-settings-api #poststuff > .tara-settings-toolbar {
			grid-column: auto;
			position: sticky;
			top: 32px;
			z-index: 5;
			margin: 0 0 10px !important;
			padding: 8px 10px;
			border: 1px solid #ece9f7;
			border-radius: 12px;
			background: rgba(255, 255, 255, .94);
			backdrop-filter: blur(6px);
		}
		</style>
		<script id="tara-settings-core-js">
		(function () {
			function ready(fn) {
				if (document.readyState === 'loading') {
					document.addEventListener('DOMContentLoaded', fn);
				} else {
					fn();
				}
			}

			ready(function () {
				var root = document.getElementById('option-tree-settings-api');
				if (!root || root.getAttribute('data-tara-core') === '1') {
					return;
				}
				root.setAttribute('data-tara-core', '1');

				var nav = root.querySelector('.ui-tabs-nav');
				root.classList.add('tara-tabs-ready');
				var panels = root.querySelectorAll('.ui-tabs-panel');

				function show(id) {
					if (!id) {
						return;
					}
					for (var i = 0; i < panels.length; i++) {
						panels[i].classList.toggle('tara-on', panels[i].id === 'section_' + id);
					}
					if (nav) {
						var lis = nav.querySelectorAll('li');
						for (var j = 0; j < lis.length; j++) {
							var a = lis[j].querySelector('a[href^="#section_"]');
							var active = a && a.getAttribute('href') === '#section_' + id;
							lis[j].classList.toggle('ui-tabs-active', !!active);
							lis[j].classList.toggle('tara-on', !!active);
						}
					}
					if (window.history && window.history.replaceState) {
						try { window.history.replaceState(null, '', '#section_' + id); } catch (e) {}
					}
				}

				function current() {
					var hash = String(window.location.hash || '');
					if (hash.indexOf('#section_') === 0) {
						return hash.replace('#section_', '');
					}
					if (nav) {
						var first = nav.querySelector('a[href^="#section_"]');
						if (first) {
							return first.getAttribute('href').replace('#section_', '');
						}
					}
					return '';
				}

				if (nav) {
					nav.addEventListener('click', function (ev) {
						var a = ev.target.closest ? ev.target.closest('a[href^="#section_"]') : null;
						if (!a) {
							return;
						}
						ev.preventDefault();
						show(a.getAttribute('href').replace('#section_', ''));
					});
				}

				/* ردیف‌های جمع‌شونده */
				var rows = root.querySelectorAll('.format-settings');
				for (var k = 0; k < rows.length; k++) {
					var label = rows[k].querySelector('.format-setting-label');
					if (!label) {
						continue;
					}
					/* v3.7.7 — همهٔ گزینه‌ها «باز» شروع می‌شوند.
					   پیش‌تر هر ردیف بسته بود و کاربر باید یکی‌یکی بازش می‌کرد
					   (همان «باز بسته»ای که لوگو و فاویکون را پنهان می‌کرد).
					   حالا مقدار همه دیده می‌شود؛ فقط اگر خواست می‌تواند جمعش کند. */
					rows[k].classList.add('has-label', 'is-open');
					label.setAttribute('role', 'button');
					label.setAttribute('tabindex', '0');
					label.setAttribute('aria-expanded', 'true');
				}

				function toggle(row, open) {
					if (!row) {
						return;
					}
					row.classList.toggle('is-open', open);
					var lb = row.querySelector('.format-setting-label');
					if (lb) {
						lb.setAttribute('aria-expanded', open ? 'true' : 'false');
					}
				}

				root.addEventListener('click', function (ev) {
					var lb = ev.target.closest ? ev.target.closest('.format-setting-label') : null;
					if (!lb) {
						return;
					}
					var row = lb.parentNode ? lb.parentNode.parentNode : null;
					if (!row || !row.classList.contains('has-label')) {
						return;
					}
					toggle(row, !row.classList.contains('is-open'));
				});

				root.addEventListener('keydown', function (ev) {
					if (ev.key !== 'Enter' && ev.key !== ' ') {
						return;
					}
					var lb = ev.target.closest ? ev.target.closest('.format-setting-label') : null;
					if (!lb) {
						return;
					}
					ev.preventDefault();
					lb.click();
				});

				/* ---------- جمع‌شدنِ آیتم‌های فهرستی (باکس مطالب و مانند آن) ----
				   سرتیتر هر آیتم خودش دکمهٔ باز/بستن می‌شود؛ دکمهٔ «ویرایش» خودِ
				   OptionTree هم سر جایش می‌ماند و هیچ‌کدام دیگری را خراب نمی‌کند. */
				function itemBody(box) {
					return box.querySelector('.option-tree-setting-body');
				}

				function setItem(box, open) {
					var body = itemBody(box);
					box.classList.toggle('tara-li-open', !!open);
					if (body) {
						body.style.display = open ? 'block' : 'none';
					}
					var head = box.querySelector('.open');
					if (head) {
						head.setAttribute('aria-expanded', open ? 'true' : 'false');
					}
				}

				function prepItems(scope) {
					var items = (scope || root).querySelectorAll('.option-tree-setting');
					for (var n = 0; n < items.length; n++) {
						var box = items[n];
						if (box.getAttribute('data-tara-li') === '1') {
							continue;
						}
						box.setAttribute('data-tara-li', '1');
						var head = box.querySelector('.open');
						if (!head) {
							continue;
						}
						head.setAttribute('role', 'button');
						head.setAttribute('tabindex', '0');
						var body = itemBody(box);
						setItem(box, !!body && body.offsetParent !== null);
					}
				}

				prepItems(root);

				/* آیتمی که با «افزودن» ساخته می‌شود هم همین ظاهر را بگیرد */
				if (window.MutationObserver) {
					var mo = new MutationObserver(function () { prepItems(root); });
					mo.observe(root, { childList: true, subtree: true });
				}

				root.addEventListener('click', function (ev) {
					var head = ev.target.closest ? ev.target.closest('.option-tree-setting > .open') : null;
					if (!head) {
						return;
					}
					/* اگر روی دکمه‌های داخل سرتیتر کلیک شد، دخالت نکن */
					if (ev.target.closest('.option-tree-ui-button')) {
						return;
					}
					var box = head.parentNode;
					if (!box || !box.classList.contains('option-tree-setting')) {
						return;
					}
					setItem(box, !box.classList.contains('tara-li-open'));
				});

				root.addEventListener('keydown', function (ev) {
					if (ev.key !== 'Enter' && ev.key !== ' ') {
						return;
					}
					var head = ev.target.closest ? ev.target.closest('.option-tree-setting > .open') : null;
					if (!head) {
						return;
					}
					ev.preventDefault();
					head.click();
				});

				/* نوار ابزار: باز/بستن همه */
				var bar = document.createElement('div');
				bar.className = 'tara-settings-toolbar';
				bar.innerHTML = '<button type="button" data-all="open">باز کردن همه</button>'
					+ '<button type="button" data-all="close">بستن همه</button>';
				/* v3.7.9 — بالای محتوا می‌نشیند تا همیشه دم دست باشد */
				var poststuff = root.querySelector('#poststuff');
				if (poststuff) {
					poststuff.insertBefore(bar, poststuff.firstChild);
				} else if (nav && nav.parentNode) {
					nav.parentNode.insertBefore(bar, nav.nextSibling);
				} else {
					root.insertBefore(bar, root.firstChild);
				}

				bar.addEventListener('click', function (ev) {
					var btn = ev.target.closest ? ev.target.closest('button[data-all]') : null;
					if (!btn) {
						return;
					}
					var open = btn.getAttribute('data-all') === 'open';
					var panel = root.querySelector('.ui-tabs-panel.tara-on') || root;
					var list = panel.querySelectorAll('.format-settings.has-label');
					for (var i = 0; i < list.length; i++) {
						toggle(list[i], open);
					}
					/* v3.7.9 — آیتم‌های فهرستی هم با «همه» جمع/باز شوند */
					var items = panel.querySelectorAll('.option-tree-setting');
					for (var m = 0; m < items.length; m++) {
						setItem(items[m], open);
					}
				});

				show(current());
			});
		})();
		</script>
		<?php
	}
}
