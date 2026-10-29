<?php
/**
 * ترانه یار — هستهٔ قالب (Taranehyar Theme Core)
 * =============================================================================
 * این فایل جای هستهٔ رمزگذاری‌شدهٔ قالب مادر را گرفته است:
 *   • هیچ فایل رمزگذاری‌شده‌ای در قالب نیست (نه هسته، نه فایل لایسنس)
 *   • هیچ اتصالی به سرور لایسنس یا دامنه وجود ندارد
 *   • همه‌چیز خوانا و قابل تغییر است
 *
 * کارهایی که این فایل انجام می‌دهد (همان کارهایی که هستهٔ قبلی می‌کرد):
 *   ۱) خواندن تنظیمات با `ot_get_option()` — دقیقاً با همان کلید و همان رفتار
 *      (`option_tree`) تا هیچ تنظیمی از دست نرود.
 *   ۲) راه‌اندازی قالب: منوها، پشتیبانی‌ها، اندازهٔ تصاویر.
 *   ۳) بارگذاری دارایی‌ها: style.css، icofont، owl-carousel، scripts.js.
 *   ۴) شمارش و نمایش بازدید (`the_views()`).
 *   ۵) صفحه‌بندیِ آرشیوها با همان مارکاپِ `.pagination_box`.
 *   ۶) بارگذاری لایه‌های خودِ قالب (myfunctions.php، ابزارک‌ها، تنظیمات).
 *
 * خاموشیِ شمارش بازدید:  add_filter( 'taranehyar_count_views', '__return_false' );
 * =============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * ۰) ثابت‌ها
 * ---------------------------------------------------------------------- */
if ( ! defined( 'TARANEHYAR_VERSION' ) ) {
	$taranehyar_theme = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
	define( 'TARANEHYAR_VERSION', $taranehyar_theme && $taranehyar_theme->get( 'Version' ) ? $taranehyar_theme->get( 'Version' ) : '3.8.4' );
}
if ( ! defined( 'TARANEHYAR_DIR' ) ) {
	define( 'TARANEHYAR_DIR', get_template_directory() );
}
if ( ! defined( 'TARANEHYAR_URI' ) ) {
	define( 'TARANEHYAR_URI', get_template_directory_uri() );
}
/* سازگاری با لایه‌های ۳.۵.x که این نام‌ها را می‌خوانند */
if ( ! defined( 'TARA_VERSION' ) ) {
	define( 'TARA_VERSION', TARANEHYAR_VERSION );
}

/* -------------------------------------------------------------------------
 * ۱) تنظیمات قالب — سازگار با داده‌های موجود (`option_tree`)
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'taranehyar_options_id' ) ) {
	/** شناسهٔ آپشنی که همهٔ تنظیمات در آن ذخیره شده‌اند (همان کلید قدیمی). */
	function taranehyar_options_id() {
		return (string) apply_filters( 'ot_options_id', 'option_tree' );
	}
}

if ( ! function_exists( 'ot_get_option' ) ) {
	/**
	 * خواندن یک تنظیم — رفتار یکسان با OptionTree:
	 * مقدارِ رشتهٔ خالی مانند «ذخیره‌نشده» تلقی می‌شود و پیش‌فرض برمی‌گردد.
	 *
	 * @param string $option_id کلید تنظیم.
	 * @param mixed  $default   مقدار پیش‌فرض.
	 * @return mixed
	 */
	function ot_get_option( $option_id, $default = '' ) {
		$options = get_option( taranehyar_options_id() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$value = isset( $options[ $option_id ] ) ? $options[ $option_id ] : null;
		if ( null === $value || '' === $value ) {
			$value = $default;
		}
		return apply_filters( 'ot_get_option', $value, $option_id, $default );
	}
}

/* -------------------------------------------------------------------------
 * ۲) راه‌اندازی قالب
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'taranehyar_setup' ) ) {
	function taranehyar_setup() {
		/* ترجمه — همان دامنهٔ متنیِ قبلی تا رشته‌های موجود از دست نروند */
		load_theme_textdomain( 'taranehyar', TARANEHYAR_DIR . '/languages' );

		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'customize-selective-refresh-widgets' );
		add_theme_support(
			'html5',
			array( 'search-form', 'gallery', 'caption', 'style', 'script' )
		);

		/* اندازه‌های تصویر (همان نام‌های قبلی؛ تمپلیت‌ها به همین‌ها وابسته‌اند) */
		if ( function_exists( 'add_image_size' ) ) {
			add_image_size( 'thumb4', 150, 150, true );
			add_image_size( 'thumb3', 60, 60, true );
			add_image_size( 'thumb2', 190, 190, true );
			add_image_size( 'thumb2x', 380, 380, true );
			add_image_size( 'thumb1', 640, 360, true );
			/* v3.8.0 — کارت شبکه‌های اجتماعی (OG/Twitter) به ۱۲۰۰×۶۳۰ نیاز دارد؛
			   پیش از این از thumb1 (۶۴۰×۳۶۰) استفاده می‌شد. برای تصویرهای
			   آپلودشدهٔ قدیمی این سایز ساخته نمی‌شود و همان مسیر قبلی می‌ماند. */
			add_image_size( 'tara-og', 1200, 630, true );
			/* v3.8.3 — بنرِ اسلایدر ویژه: نسبتِ استانداردِ کادر ۱۶:۷ ⇒ ۱۹۲۰×۸۴۰.
			   اندازهٔ قبلیِ همین بنر در فیلدِ ACF «۶۴۰×۳۶۰» بود و در کادرِ
			   تمام‌عرض کشیده می‌شد. */
			add_image_size( 'tara-vip', 1920, 840, true );
		}

		/* جای منوها — همان نامک‌هایی که در قالب‌ها فراخوانی شده‌اند */
		register_nav_menus(
			array(
				'top_menu'     => 'منوی اصلی (بالای سایت)',
				'mobiles_menu' => 'منوی موبایل',
				'ft_menu'      => 'منوی پانوشت',
			)
		);
	}
	add_action( 'after_setup_theme', 'taranehyar_setup' );
}

/* -------------------------------------------------------------------------
 * ۳) دارایی‌ها (فقط یک‌بار و با نسخهٔ قالب برای شکستن کش)
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'taranehyar_asset_ver' ) ) {
	/**
	 * نسخهٔ دارایی = نسخهٔ قالب + زمانِ آخرین تغییر فایل.
	 *
	 * چرا؟ اگر فایل JS/CSS عوض شود ولی شمارهٔ نسخه در style.css یادمان برود،
	 * مرورگر و افزونه‌های کش نسخهٔ قدیمی را سرو می‌کنند و کاربر فکر می‌کند
	 * «هیچ چیز عوض نشده». با mtime، آدرس فایل به‌طور خودکار تغییر می‌کند.
	 *
	 * @param string $relative مسیر نسبی از ریشهٔ قالب.
	 * @return string
	 */
	function taranehyar_asset_ver( $relative ) {
		$base = defined( 'TARANEHYAR_DIR' ) ? TARANEHYAR_DIR : get_template_directory();
		$file = $base . $relative;
		$ver  = defined( 'TARANEHYAR_VERSION' ) ? TARANEHYAR_VERSION : '0';
		if ( is_readable( $file ) ) {
			$ver .= '.' . filemtime( $file );
		}
		return $ver;
	}
}

if ( ! function_exists( 'tara_page_is_home' ) ) {
	/** آیا این صفحه، صفحهٔ اصلی است؟ (یک‌جا، برای enqueue و نگهبان دارایی‌ها) */
	function tara_page_is_home() {
		/* home.php هم برای «صفحهٔ اصلی» و هم برای «صفحهٔ نوشته‌ها» رندر می‌شود؛
		   پس هر دو حالت باید همین CSS/JS را بگیرند. */
		return ( function_exists( 'is_front_page' ) && is_front_page() )
			|| ( function_exists( 'is_home' ) && is_home() );
	}
}

if ( ! function_exists( 'tara_page_is_artist' ) ) {
	/** آرشیو هنرمند/خواننده؟ (کلاس‌های `.ty-artist__*` فقط این‌جا لازم‌اند) */
	function tara_page_is_artist() {
		if ( ! function_exists( 'is_tax' ) ) {
			return false;
		}
		$taxes = array( 'artist', 'singer' );
		if ( function_exists( 'tara_artist_tax' ) ) {
			$taxes[] = tara_artist_tax();
		}
		foreach ( array_unique( array_filter( $taxes ) ) as $tax ) {
			if ( is_tax( $tax ) ) {
				return true;
			}
		}
		return false;
	}
}

/* ۶-ب-۰) کمکِ دارایی‌ها (۳.۸.۳) — فایلِ جدا تا آزمونِ خودکار هم بتواند
   بدونِ بالا آوردنِ کلِ functions.php صدایش بزند. */
$taranehyar_assets_helper = TARANEHYAR_DIR . '/inc/tara-assets.php';
if ( is_readable( $taranehyar_assets_helper ) ) {
	require_once $taranehyar_assets_helper;
}
unset( $taranehyar_assets_helper );

if ( ! function_exists( 'tara_asset_styles' ) ) {
	/**
	 * فهرست «چه CSS‌ای در این صفحه لازم است».
	 *
	 * v3.7.8 — سبک‌بارسازی: `tara-home.css` فقط صفحهٔ اصلی و `tara-artist.css`
	 * فقط آرشیو هنرمند بار می‌شود. پیش از این هر سه فایل (حدود ۱۵۰KB) در همهٔ
	 * صفحه‌ها دانلود می‌شد. `tara-ui.css` چون پلیر/کارت‌ها همه‌جا هستند سراسری
	 * می‌ماند. همین فهرست، هم در enqueue و هم در «نگهبان دارایی‌ها» استفاده
	 * می‌شود تا این دو هرگز از هم جدا نشوند.
	 *
	 * @return array<string,string> handle => مسیر نسبی
	 */
	function tara_asset_styles() {
		$styles = array( 'taranehyar-ui' => '/css/tara-ui.css' );

		if ( tara_page_is_home() ) {
			$styles['taranehyar-home'] = '/css/tara-home.css';
		}
		if ( tara_page_is_artist() ) {
			$styles['taranehyar-artist'] = '/css/tara-artist.css';
		}

		/* v3.8.3 — اگر نسخهٔ فشرده هست، همان سرو می‌شود (بدون ترکیب). */
		foreach ( $styles as $tara_handle => $tara_rel ) {
			$styles[ $tara_handle ] = tara_css_rel( $tara_rel );
		}
		unset( $tara_handle, $tara_rel );

		return apply_filters( 'tara_asset_styles', $styles );
	}
}

if ( ! function_exists( 'taranehyar_assets' ) ) {
	function taranehyar_assets() {
		if ( ! function_exists( 'wp_enqueue_style' ) ) {
			return;
		}

		/* اولویت ۵ تا لایه‌های شیشه‌ای/رابط (۲۰ به بعد) بعد از آن بیایند */
		/* v3.8.3 — نسخهٔ فشردهٔ style.css اگر موجود و تازه باشد. فایل اصلی
		   سر جایش می‌ماند (هدر قالب، ابزارهای وردپرس و بازگشتِ آسان). */
		$tara_main_css = TARANEHYAR_DIR . '/style.css';
		$tara_min_css  = TARANEHYAR_DIR . '/css/style.min.css';
		if ( is_file( $tara_min_css ) && is_file( $tara_main_css ) && @filemtime( $tara_min_css ) >= @filemtime( $tara_main_css ) ) {
			wp_enqueue_style( 'taranehyar-style', TARANEHYAR_URI . '/css/style.min.css', array(), (string) @filemtime( $tara_min_css ) );
		} else {
			wp_enqueue_style( 'taranehyar-style', get_stylesheet_uri(), array(), TARANEHYAR_VERSION );
		}
		unset( $tara_main_css, $tara_min_css );

		if ( file_exists( TARANEHYAR_DIR . '/css/icofont.min.css' ) ) {
			wp_enqueue_style( 'icofont', TARANEHYAR_URI . '/css/icofont.min.css', array(), '1.0.1' );
		}

		/* اسکریپت‌ها در پانوشت؛ jquery را خودِ هسته می‌دهد.
		   v3.7.8 — owl.carousel (۹۰KB) فقط برای ریل هنرمندانِ صفحهٔ اصلی لازم است؛
		   پیش از این در همهٔ صفحه‌ها دانلود می‌شد. `scripts.js` هم دیگر به آن
		   وابسته نیست و خودش وجودش را بررسی می‌کند. */
		if ( tara_page_is_home() && file_exists( TARANEHYAR_DIR . '/js/owl.carousel.js' ) ) {
			wp_enqueue_script( 'owl-carousel', TARANEHYAR_URI . '/js/owl.carousel.js', array( 'jquery' ), '2.3.4', true );
		}
		if ( file_exists( TARANEHYAR_DIR . '/js/scripts.js' ) ) {
			wp_enqueue_script( 'taranehyar-scripts', TARANEHYAR_URI . '/js/scripts.js', array( 'jquery' ), taranehyar_asset_ver( '/js/scripts.js' ), true );
		}

		/* لایهٔ تازهٔ ۳.۷.۰ — پلیر «رادیو»، ردیف آهنگ‌ها و چیپ‌ها.
		   بدون jQuery و بدون وابستگی؛ اگر فایل نبود، هیچ‌چیز نمی‌شکند. */
		/* v3.7.8 — فقط CSS لازمِ همین صفحه (فهرست در tara_asset_styles). */
		foreach ( tara_asset_styles() as $tara_handle => $tara_rel ) {
			if ( ! file_exists( TARANEHYAR_DIR . $tara_rel ) ) {
				continue;
			}
			$tara_deps = ( 'taranehyar-ui' === $tara_handle ) ? array( 'taranehyar-style' ) : array( 'taranehyar-ui' );
			wp_enqueue_style( $tara_handle, TARANEHYAR_URI . $tara_rel, $tara_deps, taranehyar_asset_ver( $tara_rel ) );
		}
		unset( $tara_handle, $tara_rel, $tara_deps );

		/* v3.7.8 — اسلایدر تازهٔ خانه فقط همان‌جا بار می‌شود (بدون jQuery). */
		if ( tara_page_is_home() && file_exists( TARANEHYAR_DIR . '/js/hero-slider.js' ) ) {
			wp_enqueue_script( 'taranehyar-hero', TARANEHYAR_URI . '/js/hero-slider.js', array(), taranehyar_asset_ver( '/js/hero-slider.js' ), true );
		}

		if ( file_exists( TARANEHYAR_DIR . '/js/tara-player.js' ) ) {
			wp_enqueue_script( 'taranehyar-player', TARANEHYAR_URI . '/js/tara-player.js', array(), taranehyar_asset_ver( '/js/tara-player.js' ), true );
		}
	}
	add_action( 'wp_enqueue_scripts', 'taranehyar_assets', 5 );
}

/* -------------------------------------------------------------------------
 * ۴) بازدید: شمارش و نمایش
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'taranehyar_is_bot' ) ) {
	/** تشخیصِ سادهٔ خزنده‌ها (تا بازدیدِ گوگل/تلگرام در آمار حساب نشود). */
	function taranehyar_is_bot() {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}
		$ua = strtolower( (string) $_SERVER['HTTP_USER_AGENT'] );
		$bots = array(
			'bot', 'crawl', 'spider', 'slurp', 'mediapartners', 'facebookexternalhit',
			'telegrambot', 'whatsapp', 'preview', 'curl/', 'wget', 'python-requests',
			'headlesschrome', 'lighthouse', 'pingdom', 'uptime',
		);
		foreach ( $bots as $needle ) {
			if ( false !== strpos( $ua, $needle ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'the_views' ) ) {
	/**
	 * چاپ (یا برگرداندن) تعداد بازدیدِ نوشته.
	 * برای سازگاری با امضای WP-PostViews: ( $display = true, $prefix = '', $suffix = '' )
	 *
	 * @param bool   $display چاپ یا برگرداندن.
	 * @param string $prefix  پیش از عدد.
	 * @param string $suffix  پس از عدد.
	 * @return string|void
	 */
	function the_views( $display = true, $prefix = '', $suffix = '' ) {
		$post_id = function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0;
		$views   = $post_id ? (int) get_post_meta( $post_id, 'views', true ) : 0;
		$views   = max( 0, $views );

		if ( function_exists( 'tara_number_fa' ) ) {
			$number = tara_number_fa( $views );
		} else {
			$number = number_format_i18n( $views );
		}
		$out = $prefix . $number . $suffix;

		if ( $display ) {
			echo $out; /* عدد و پیشوند/پسوند همه از توابع خودِ وردپرس/قالب می‌آیند */
			return;
		}
		return $out;
	}
}

if ( ! function_exists( 'taranehyar_other_view_counter' ) ) {
	/**
	 * آیا افزونهٔ دیگری هم بازدید می‌شمارد؟ (v3.7.7)
	 *
	 * چرا مهم است: WP-PostViews و Post Views Counter هم دقیقاً روی همین متای
	 * `views` می‌نویسند. اگر هر دو فعال باشند، هر بازدید **دو** واحد اضافه
	 * می‌شد — همان چیزی که کاربر دید («هر بازدید ۲ بازدید»).
	 *
	 * @return string نام افزونه یا رشتهٔ خالی.
	 */
	function taranehyar_other_view_counter() {
		if ( defined( 'WP_POSTVIEWS_VER' ) || class_exists( 'WP_PostViews' ) || function_exists( 'process_postviews' ) ) {
			return 'WP-PostViews';
		}
		if ( defined( 'POSTVIEWS_VERSION' ) || class_exists( 'Post_Views_Counter' ) || function_exists( 'pvc_get_post_views' ) ) {
			return 'Post Views Counter';
		}
		if ( defined( 'WPP_VERSION' ) || class_exists( 'WPP' ) ) {
			return 'WP Statistics';
		}
		if ( function_exists( 'wp_statistics_visitor' ) ) {
			return 'WP Statistics';
		}

		return '';
	}
}

if ( ! function_exists( 'taranehyar_count_views' ) ) {
	/**
	 * یک بازدید به نوشتهٔ در حال نمایش اضافه می‌کند.
	 * شرایط: فقط نوشتهٔ تکیِ منتشرشده، فقط در فرانت، بدون خزنده، یک‌بار در هر درخواست
	 * و **یک‌بار برای هر بازدیدکننده در هر ۶ ساعت** (v3.7.7)، تا رفرش/بازگشت
	 * شمارنده را دو برابر نکند. اگر افزونهٔ دیگری هم بشمارد، اصلاً نمی‌شماریم.
	 * این تابع هیچ عددی «برای آمار» نمی‌سازد؛ فقط متای `views` موجود را یک واحد
	 * بالا می‌برد — همان چیزی که مرتب‌سازیِ «پربازدیدترین‌ها» بر آن استوار است.
	 */
	function taranehyar_count_views() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return;
		}
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return;
		}
		if ( ! function_exists( 'is_singular' ) || ! is_singular( 'post' ) ) {
			return;
		}
		if ( function_exists( 'is_feed' ) && is_feed() ) {
			return;
		}
		if ( function_exists( 'is_preview' ) && is_preview() ) {
			return;
		}
		if ( function_exists( 'is_robots' ) && is_robots() ) {
			return;
		}
		if ( taranehyar_is_bot() ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		if ( $post_id < 1 ) {
			$post_id = (int) get_the_ID();
		}
		if ( $post_id < 1 ) {
			return;
		}
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status || 'revision' === $post->post_type ) {
			return;
		}

		/**
		 * خاموش‌کردنِ شمارش (مثلاً وقتی افزونهٔ دیگری بازدید می‌شمارد).
		 *
		 * @param bool $count پیش‌فرض true.
		 * @param int  $post_id شناسهٔ نوشته.
		 */
		if ( ! apply_filters( 'taranehyar_count_views', true, $post_id ) ) {
			return;
		}

		/*
		 * v3.7.7 — اگر افزونهٔ دیگری هم می‌شمارد، ما نمی‌شماریم (جلوگیری از ۲ شدن).
		 * با فیلتر می‌شود این تصمیم را عوض کرد:
		 *   add_filter( 'taranehyar_skip_view_count', '__return_false' );
		 */
		$other = taranehyar_other_view_counter();
		if ( '' !== $other && apply_filters( 'taranehyar_skip_view_count', true, $post_id, $other ) ) {
			return;
		}

		/* یک‌بار در هر ۶ ساعت برای هر بازدیدکننده — رفرش/بازگشت شمارنده را دو برابر نمی‌کند */
		$cookie = 'tara_seen_' . $post_id;
		if ( ! empty( $_COOKIE[ $cookie ] ) ) {
			return;
		}

		$current = (int) get_post_meta( $post_id, 'views', true );
		update_post_meta( $post_id, 'views', max( 0, $current ) + 1 );

		/* کوکی مهمان‌پسند: بدون داده‌های شخصی، فقط «این نوشته را دیده‌ام» */
		if ( ! headers_sent() ) {
			$path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
			$domain = defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '';
			setcookie( $cookie, '1', time() + 6 * HOUR_IN_SECONDS, $path, $domain, is_ssl(), true );
			$_COOKIE[ $cookie ] = '1'; // در همین درخواست دوباره شمرده نشود
		}
	}
	add_action( 'wp', 'taranehyar_count_views', 20 );
}

/* -------------------------------------------------------------------------
 * ۵) صفحه‌بندی آرشیوها — مارکاپِ موجود در style.css (.pagination_box)
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'pagination' ) ) {
	/**
	 * صفحه‌بندیِ شماره‌دار.
	 *
	 * @param int $pages کلِ صفحه‌ها (پیش‌فرض: از کوئریِ اصلی).
	 * @param int $range شعاعِ شماره‌های اطرافِ صفحهٔ جاری.
	 */
	function pagination( $pages = '', $range = 2 ) {
		global $wp_query, $paged;

		$pages = (int) $pages;
		if ( $pages < 1 ) {
			$pages = ( isset( $wp_query->max_num_pages ) ) ? (int) $wp_query->max_num_pages : 1;
		}
		if ( $pages < 2 ) {
			return;
		}

		$current = max( 1, (int) $paged );
		$range   = max( 1, (int) $range );
		$show    = ( $range * 2 ) + 1;

		/* عدد فارسی اگر لایه‌اش هست */
		$num = function ( $n ) {
			return function_exists( 'tara_digits' ) ? tara_digits( (string) $n ) : (string) $n;
		};

		echo '<div class="pagination_box"><ul class="pagination" aria-label="صفحه‌بندی">';

		if ( $current > 1 ) {
			echo '<li><a rel="prev" class="ab-pg-prev" href="' . esc_url( get_pagenum_link( $current - 1 ) ) . '" aria-label="صفحهٔ قبل">';
			echo $num( '‹' );
			echo '</a></li>';
		}

		for ( $i = 1; $i <= $pages; $i++ ) {
			if ( $pages <= $show || ( $i >= $current - $range && $i <= $current + $range ) || 1 === $i || $pages === $i ) {
				if ( $i === $current ) {
					echo '<li><span class="current" aria-current="page">' . esc_html( $num( $i ) ) . '</span></li>';
				} else {
					echo '<li><a href="' . esc_url( get_pagenum_link( $i ) ) . '">' . esc_html( $num( $i ) ) . '</a></li>';
				}
			} elseif ( $i === $current - $range - 1 || $i === $current + $range + 1 ) {
				echo '<li><span class="inactive">…</span></li>';
			}
		}

		if ( $current < $pages ) {
			echo '<li><a rel="next" class="ab-pg-next" href="' . esc_url( get_pagenum_link( $current + 1 ) ) . '" aria-label="صفحهٔ بعد">';
			echo $num( '›' );
			echo '</a></li>';
		}

		echo '</ul></div>';
	}
}

/* -------------------------------------------------------------------------
 * ۶) بارگذاری لایه‌های قالب
 * ---------------------------------------------------------------------- */

/* ۶-الف) OptionTree (فقط برای صفحهٔ تنظیمات؛ داده‌ها همان `option_tree` است) */
$taranehyar_ot = TARANEHYAR_DIR . '/inc/option-tree/ot-loader.php';
if ( is_readable( $taranehyar_ot ) && ! class_exists( 'OT_Loader' ) ) {
	require_once $taranehyar_ot;
}
unset( $taranehyar_ot );

/* ۶-الف-۲) تاکسونومی‌های موسیقی (ژانر، حال‌وهوا، ترانه‌سرا، آهنگساز، تنظیم) */
$taranehyar_taxonomies = TARANEHYAR_DIR . '/inc/tara-taxonomies.php';
if ( is_readable( $taranehyar_taxonomies ) ) {
	require_once $taranehyar_taxonomies;
}
unset( $taranehyar_taxonomies );

/* ۶-الف-۳) عناصر مشترک رابط: چیپ، ردیف آهنگ، پلیر */
$taranehyar_ui = TARANEHYAR_DIR . '/inc/tara-ui.php';
if ( is_readable( $taranehyar_ui ) ) {
	require_once $taranehyar_ui;
}
unset( $taranehyar_ui );

/* ۶-الف-۳-۲) رنگ‌ها و هویت (۳.۷.۷) — متغیرهای CSS از تنظیمات قالب */
$taranehyar_brand = TARANEHYAR_DIR . '/inc/tara-brand.php';
if ( is_readable( $taranehyar_brand ) ) {
	require_once $taranehyar_brand;
}
unset( $taranehyar_brand );

/* ۶-الف-۳-۳) بنرِ «اسلایدر ویژه» (۳.۸.۳)
   این فایل باید در سمت کاربر هم بار شود، چون تابعِ `tara_vip_banner_id()`
   را `inc/vip_slider.php` روی صفحهٔ اصلی صدا می‌زند؛ جعبهٔ ویرایش و ذخیرهٔ
   متا خودشان فقط در پیشخوان اجرا می‌شوند. */
$taranehyar_vip_image = TARANEHYAR_DIR . '/inc/ab-vip-image.php';
if ( is_readable( $taranehyar_vip_image ) ) {
	require_once $taranehyar_vip_image;
}
unset( $taranehyar_vip_image );

/* ۶-الف-۴) لایهٔ تضمینیِ ظاهر + جست‌وجوی زنده (۳.۷.۶)
   CSS ضروری را درون‌خطی چاپ می‌کند و اگر فایل‌های قالب به صفحه نرسند،
   مستقیم تگ <link>/<script> می‌نویسد. --- */
$taranehyar_critical = TARANEHYAR_DIR . '/inc/tara-critical.php';
if ( is_readable( $taranehyar_critical ) ) {
	require_once $taranehyar_critical;
}
unset( $taranehyar_critical );

/* ۶-ب) ابزارک‌های اختصاصی (خودشان روی widgets_init هوک می‌زنند) */
$taranehyar_widgets = TARANEHYAR_DIR . '/inc/widgets.php';
if ( is_readable( $taranehyar_widgets ) ) {
	require_once $taranehyar_widgets;
}
unset( $taranehyar_widgets );

/* ۶-ج) فهرست تنظیمات و پنل مدیریت (فقط در پیشخوان لازم است) */
if ( is_admin() ) {
	$taranehyar_options = TARANEHYAR_DIR . '/inc/theme-options.php';
	if ( is_readable( $taranehyar_options ) ) {
		require_once $taranehyar_options;
	}
	unset( $taranehyar_options );

	$taranehyar_panel = TARANEHYAR_DIR . '/inc/admin-panel.php';
	if ( is_readable( $taranehyar_panel ) ) {
		require_once $taranehyar_panel;
	}
	unset( $taranehyar_panel );

	/* v3.8.2 — کارتِ «لوگو سایت» در پیشخوان پنل (آپلود/آدرس/بازگردانی).
	   قبل از افزونهٔ پنل لازم نیست؛ فقط باید در is_admin() باشد. */
	$taranehyar_logo = TARANEHYAR_DIR . '/inc/ab-logo-setting.php';
	if ( is_readable( $taranehyar_logo ) ) {
		require_once $taranehyar_logo;
	}
	unset( $taranehyar_logo );

	/* پوستهٔ تازهٔ صفحهٔ تنظیمات (۳.۷.۰) — فقط ظاهر و جست‌وجو؛ نه ذخیره‌سازی */
	$taranehyar_shell = TARANEHYAR_DIR . '/inc/admin-settings-shell.php';
	if ( is_readable( $taranehyar_shell ) ) {
		require_once $taranehyar_shell;
	}
	unset( $taranehyar_shell );
}

/* ۶-د) لایهٔ اصلیِ قالب (سی‌اس‌اس/ابزارک/سئو/دیدگاه‌ها…). آخر از همه می‌آید
   چون به `ot_get_option()` و ثابت‌های بالا تکیه می‌کند. */
$taranehyar_myfunctions = TARANEHYAR_DIR . '/myfunctions.php';
if ( is_readable( $taranehyar_myfunctions ) ) {
	require_once $taranehyar_myfunctions;
}
unset( $taranehyar_myfunctions );
