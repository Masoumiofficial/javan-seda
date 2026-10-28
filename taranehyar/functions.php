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
	define( 'TARANEHYAR_VERSION', $taranehyar_theme && $taranehyar_theme->get( 'Version' ) ? $taranehyar_theme->get( 'Version' ) : '3.6.0' );
}
if ( ! defined( 'TARANEHYAR_DIR' ) ) {
	define( 'TARANEHYAR_DIR', get_template_directory() );
}
if ( ! defined( 'TARANEHYAR_URI' ) ) {
	define( 'TARANEHYAR_URI', get_template_directory_uri() );
}
/* سازگاری با لایه‌های ۳.۵.x که این نام‌ها را می‌خوانند */
if ( ! defined( 'JAVANSEDA_VERSION' ) ) {
	define( 'JAVANSEDA_VERSION', TARANEHYAR_VERSION );
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
		load_theme_textdomain( 'javanseda', TARANEHYAR_DIR . '/languages' );

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
if ( ! function_exists( 'taranehyar_assets' ) ) {
	function taranehyar_assets() {
		if ( ! function_exists( 'wp_enqueue_style' ) ) {
			return;
		}

		/* اولویت ۵ تا لایه‌های شیشه‌ای/رابط (۲۰ به بعد) بعد از آن بیایند */
		wp_enqueue_style( 'taranehyar-style', get_stylesheet_uri(), array(), TARANEHYAR_VERSION );

		if ( file_exists( TARANEHYAR_DIR . '/css/icofont.min.css' ) ) {
			wp_enqueue_style( 'icofont', TARANEHYAR_URI . '/css/icofont.min.css', array(), '1.0.1' );
		}

		/* اسکریپت‌ها در پانوشت؛ jquery را خودِ هسته می‌دهد */
		if ( file_exists( TARANEHYAR_DIR . '/js/owl.carousel.js' ) ) {
			wp_enqueue_script( 'owl-carousel', TARANEHYAR_URI . '/js/owl.carousel.js', array( 'jquery' ), '2.3.4', true );
		}
		if ( file_exists( TARANEHYAR_DIR . '/js/scripts.js' ) ) {
			$deps = array( 'jquery' );
			if ( wp_script_is( 'owl-carousel', 'registered' ) ) {
				$deps[] = 'owl-carousel';
			}
			wp_enqueue_script( 'taranehyar-scripts', TARANEHYAR_URI . '/js/scripts.js', $deps, TARANEHYAR_VERSION, true );
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

		if ( function_exists( 'javan_seda_number_fa' ) ) {
			$number = javan_seda_number_fa( $views );
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

if ( ! function_exists( 'taranehyar_count_views' ) ) {
	/**
	 * یک بازدید به نوشتهٔ در حال نمایش اضافه می‌کند.
	 * شرایط: فقط نوشتهٔ تکیِ منتشرشده، فقط در فرانت، بدون خزنده، یک‌بار در هر درخواست.
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

		$current = (int) get_post_meta( $post_id, 'views', true );
		update_post_meta( $post_id, 'views', max( 0, $current ) + 1 );
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
			return function_exists( 'javan_seda_digits' ) ? javan_seda_digits( (string) $n ) : (string) $n;
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
}

/* ۶-د) لایهٔ اصلیِ قالب (سی‌اس‌اس/ابزارک/سئو/دیدگاه‌ها…). آخر از همه می‌آید
   چون به `ot_get_option()` و ثابت‌های بالا تکیه می‌کند. */
$taranehyar_myfunctions = TARANEHYAR_DIR . '/myfunctions.php';
if ( is_readable( $taranehyar_myfunctions ) ) {
	require_once $taranehyar_myfunctions;
}
unset( $taranehyar_myfunctions );
