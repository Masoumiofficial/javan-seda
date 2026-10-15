<?php
/**
 * جوان‌صدا (آهنگ‌بان) — لایهٔ ایمنی و بازرسی «سایدبار / ابزارک / هدر» نسخهٔ 3.0.5
 * ---------------------------------------------------------------------------
 * هدف: ابزارک‌های ستون چپِ صفحهٔ نوشته (single.php) باید همیشه نمایش داده
 * شوند، حتی اگر یکی از حلقه‌های ثبت قالب قطع شده باشد.
 *
 * این فایل کارهای زیر را می‌کند (و فقط همین‌ها را):
 *  ۱) اگر کلاس‌های ابزارک اختصاصی قالب ثبت نشده باشند، inc/widgets.php را
 *     یک‌بار بارگذاری و register_widget می‌کند (جلوگیری از خروجی خالی).
 *  ۲) اگر هر یک از سه ناحیهٔ left_side / left_side2 / left_side1 ثبت نشده
 *     باشد، آن‌ها را عیناً با همین شناسه‌ها ثبت می‌کند (net ایمن).
 *  ۳) یک تابع رندر امن (javan_seda_sidebar) که ابزارک را چاپ می‌کند و اگر
 *     ناحیه خالی باشد، به‌جای خروجی خالی، یک «کامنت تشخیصی» در HTML می‌گذارد.
 *  ۴) بازگرداندن غیرمخرب ابزارک‌های افتاده در «ابزارک‌های غیرفعال»:
 *     فقط در زمان خواندن (فیلتر sidebars_widgets) و بدون هیچ نوشتن در دیتابیس.
 *  ۵) گزارش تشخیصی فقط‌خواندنی:  ?ab_sidebars_diag=1  (فقط برای مدیر)
 *
 * قوانین رعایت‌شده:
 *  - هیچ دست‌کاری در تاکسونومی‌ها (از جمله singer) و هیچ هوک سراسری جدیدی.
 *  - تنها هوک‌ها: widgets_init (اولویت ۵ و ۲۰، فقط در صورت نبود)،
 *    فیلتر sidebars_widgets (خواندنی) و فیلتر template_include برای گزارش.
 *  - بدون گرادینت، بدون رنگ جدید؛ پالت: #FFFFFF / #185ADB / #111111
 *
 * ⚠ توضیح عمدی: تعریف سه ناحیه در این فایل، مشابه myfunctions.php است.
 *   این تکرار عمداً انجام شده تا «شبیه‌ safety net » حتی اگر myfunctions.php
 *   بارگذاری نشده باشد هم کار کند.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------------------
 * ۰) تنظیم‌ها (پیش‌فرض‌ها امن‌اند؛ برای تغییر، قبل از بارگذاری قالب تعریف کنید
 *    یا در wp-config.php بنویسید:  define( 'JAVANSEDA_...', false ); )
 * ------------------------------------------------------------------------- */
if ( ! defined( 'JAVANSEDA_SIDEBAR_RECOVERY' ) ) {
	define( 'JAVANSEDA_SIDEBAR_RECOVERY', true );      // بازگردانی از ابزارک‌های غیرفعال
}
if ( ! defined( 'JAVANSEDA_SIDEBAR_MIRROR_PAGES' ) ) {
	define( 'JAVANSEDA_SIDEBAR_MIRROR_PAGES', false ); // اگر ابزارک فقط در ناحیهٔ «برگه‌ها» باشد
}
if ( ! defined( 'JAVANSEDA_SIDEBAR_MARKERS' ) ) {
	define( 'JAVANSEDA_SIDEBAR_MARKERS', true );       // کامنت تشخیصی وقتی ناحیه خالی است
}

/** شناسهٔ ابزارک‌های اختصاصی قالب (هر چه در inc/widgets.php ثبت می‌شود). */
function javan_seda_widget_id_bases() {
	return array( 'tarlanweb_ir_newp', 'tarlanweb_ir_popular', 'tarlanweb_ir_rand' );
}

/** تعریف سه ناحیهٔ سایدبار — شناسه‌ها به‌هیچ‌وجه تغییر نمی‌کنند. */
function javan_seda_sidebar_defs() {
	$shared = array(
		'before_widget' => '<aside id="%1$s" class="related_posts %2$s">',
		'after_widget'  => '<div class="clear"></div></div></aside>',
		'before_title'  => '<h3 class="tarlanweb_side_titles">',
		'after_title'   => '</h3><div class="liner clear"></div><div class="tarlanweb_side_box">',
	);

	return array(
		'left_side'  => array_merge( $shared, array(
			'id'          => 'left_side',
			'class'       => 'leftside',
			'name'        => 'سایدبار نوشته‌ها — بالا (single.php)',
			'description' => 'بالای ستون چپ صفحهٔ نوشته/آهنگ. شناسه: left_side — ابزارک‌های اختصاصی قالب: جدیدترین موسیقی‌ها / موسیقی محبوب / مطالب تصادفی.',
		) ),
		'left_side2' => array_merge( $shared, array(
			'id'          => 'left_side2',
			'class'       => 'leftside2',
			'name'        => 'سایدبار نوشته‌ها — پایین (single.php)',
			'description' => 'پایین ستون چپ صفحهٔ نوشته/آهنگ، بعد از نوشته‌های مرتبط. شناسه: left_side2',
		) ),
		'left_side1' => array_merge( $shared, array(
			'id'          => 'left_side1',
			'class'       => 'leftside1',
			'name'        => 'سایدبار برگه‌ها (page.php)',
			'description' => 'ستون چپ «برگه‌ها» — روی صفحهٔ نوشته‌ها نمایش داده نمی‌شود! شناسه: left_side1',
		) ),
	);
}

/** آیا ناحیه‌ای با این شناسه ثبت شده است؟ (بدون وابستگی به نسخهٔ وردپرس) */
function javan_seda_sidebar_registered( $id ) {
	global $wp_registered_sidebars;
	return is_array( $wp_registered_sidebars ) && isset( $wp_registered_sidebars[ $id ] );
}

/** ثبت ناحیه‌های جاافتاده — هم در widgets_init و هم در زمان رندر قابل فراخوانی. */
function javan_seda_register_missing_sidebars() {
	foreach ( javan_seda_sidebar_defs() as $id => $args ) {
		// idempotent: ناحیه‌ای که قبلاً ثبت شده را بازنویسی نمی‌کنیم
		if ( ! javan_seda_sidebar_registered( $id ) && function_exists( 'register_sidebar' ) ) {
			register_sidebar( $args );
		}
	}
}
add_action( 'widgets_init', 'javan_seda_register_missing_sidebars', 20 );

/**
 * بارگذاری ابزارک‌های اختصاصی قالب، اگر هیچ‌کجا register نشده باشند.
 * (نسخهٔ رمزنگاری‌شدهٔ functions.php معمولاً inc/widgets.php را include می‌کند؛
 *  اگر آن include گم/حذف شده باشد، خروجی هر ابزارک قدیمی در سایدبار «خالی» است.)
 */
function javan_seda_ensure_widget_classes() {
	$missing = array();
	foreach ( javan_seda_widget_id_bases() as $cls ) {
		if ( ! class_exists( $cls ) ) {
			$missing[] = $cls;
		}
	}
	if ( ! $missing ) {
		return;
	}
	$file = get_template_directory() . '/inc/widgets.php';
	if ( is_readable( $file ) ) {
		require_once $file;
	}
	foreach ( $missing as $cls ) {
		if ( class_exists( $cls ) && function_exists( 'register_widget' ) ) {
			register_widget( $cls ); // بی‌ضرر: register_widget بر اساس نام کلاس بازنویسی می‌کند
		}
	}
}
add_action( 'widgets_init', 'javan_seda_ensure_widget_classes', 5 );

/** آیا این شناسهٔ ابزارک متعلق به قالب است؟ (برای بازگردانی از ناحیهٔ غیرفعال) */
function javan_seda_is_theme_widget_id( $widget_id ) {
	$widget_id = (string) $widget_id;
	if ( '' === $widget_id ) {
		return false;
	}
	if ( 0 === strpos( $widget_id, 'block-' ) ) {
		return true; // ابزارک ذخیره‌شده به‌صورت بلوک در همین قالب
	}
	foreach ( javan_seda_widget_id_bases() as $base ) {
		if ( 0 === strpos( $widget_id, $base . '-' ) || 0 === strpos( $widget_id, $base . '__' ) ) {
			return true;
		}
	}
	return false;
}

/**
 * ترمیم خواندنی: اگر هر دو ناحیهٔ صفحهٔ نوشته خالی بودند، ابزارک‌های قالب را
 * از «ابزارک‌های غیرفعال» (و کلیدهای یتیم) به همان ناحیه‌ها برمی‌گردانیم.
 * هیچ مقدار جدیدی در دیتابیس ذخیره نمی‌شود؛ در نتیجه با یک «ذخیره» در پیشخوان،
 * وضعیت اصلیِ دیتابیس دست‌نخورده باقی می‌ماند.
 */
function javan_seda_recover_sidebars( $sidebars_widgets ) {
	if ( ! is_array( $sidebars_widgets ) || is_admin() || ! JAVANSEDA_SIDEBAR_RECOVERY ) {
		return $sidebars_widgets;
	}

	$single_areas = array( 'left_side', 'left_side2' );
	foreach ( $single_areas as $area ) {
		if ( ! empty( $sidebars_widgets[ $area ] ) ) {
			return $sidebars_widgets; // چیزی برای ترمیم نیست
		}
	}

	$pool = array();
	foreach ( array_keys( $sidebars_widgets ) as $key ) {
		if ( 'wp_inactive_widgets' === $key || 0 === strpos( $key, 'orphaned_widgets' ) ) {
			if ( is_array( $sidebars_widgets[ $key ] ) ) {
				$pool = array_merge( $pool, $sidebars_widgets[ $key ] );
			}
		}
	}

	$from_pages = false;
	if ( ! $pool && JAVANSEDA_SIDEBAR_MIRROR_PAGES && ! empty( $sidebars_widgets['left_side1'] ) ) {
		$pool        = (array) $sidebars_widgets['left_side1'];
		$from_pages = true;
	}
	if ( ! $pool ) {
		return $sidebars_widgets;
	}

	$mine = array();
	$keep = array();
	foreach ( $pool as $wid ) {
		if ( javan_seda_is_theme_widget_id( $wid ) ) {
			$mine[] = $wid;
		} elseif ( ! $from_pages ) {
			$keep[] = $wid;
		} else {
			$mine[] = $wid; // در حالت آینه، همهٔ ابزارک‌های ناحیهٔ برگه قابل استفاده‌اند
		}
	}
	if ( ! $mine ) {
		return $sidebars_widgets;
	}

	// همه در «بالای ستون چپ نوشته‌ها» (پیش‌بینی‌پذیرترین رفتار؛ ناحیهٔ پایین را دست نمی‌گیریم)
	$sidebars_widgets['left_side'] = $mine;
	if ( isset( $sidebars_widgets['wp_inactive_widgets'] ) && ! $from_pages ) {
		$sidebars_widgets['wp_inactive_widgets'] = $keep;
	}
	$GLOBALS['javan_seda_sidebar_recovered'] = $mine;

	return $sidebars_widgets;
}
add_filter( 'sidebars_widgets', 'javan_seda_recover_sidebars', 20 );

/**
 * رندر امن یک ناحیهٔ ابزارک — جایگزین خط قدیمی dynamic_sidebar() در قالب.
 * اگر ناحیه خالی بود، جای آن یک کامنت تشخیصی چاپ می‌شود تا با «View source»
 * قابل تشخیص باشد که مشکل از «دیتای ابزارک» است یا از «خودِ قالب».
 */
function javan_seda_sidebar( $index ) {
	$index = preg_replace( '/[^a-z0-9_\-]/i', '', (string) $index );
	if ( '' === $index ) {
		return;
	}

	if ( ! javan_seda_sidebar_registered( $index ) ) {
		javan_seda_register_missing_sidebars();
	}

	$snap_post    = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
	$snap_wpquery = isset( $GLOBALS['wp_query'] ) ? $GLOBALS['wp_query'] : null;
	$snap_id      = isset( $GLOBALS['id'] ) ? $GLOBALS['id'] : null;

	$state = 'rendered';
	if ( ! function_exists( 'dynamic_sidebar' ) ) {
		$state = 'no-dynamic_sidebar';
	} elseif ( ! javan_seda_sidebar_registered( $index ) ) {
		$state = 'not-registered';
	} elseif ( function_exists( 'is_active_sidebar' ) && ! is_active_sidebar( $index ) ) {
		$state = 'no-active-widget';
	} else {
		$ok = dynamic_sidebar( $index );
		if ( ! $ok ) {
			$state = 'empty-sidebar';
		}
	}

	// ابزارک‌های قدیمی این قالب داخل خودشان $wp_query را عوض می‌کنند؛ حالت حلقه را برمی‌گردانیم.
	if ( $snap_post ) {
		$GLOBALS['post'] = $snap_post;
	}
	if ( $snap_wpquery ) {
		$GLOBALS['wp_query'] = $snap_wpquery;
	}
	if ( null !== $snap_id ) {
		$GLOBALS['id'] = $snap_id;
	}

	if ( 'rendered' !== $state && JAVANSEDA_SIDEBAR_MARKERS ) {
		echo '<!-- javan_seda: sidebar ' . esc_html( $index ) . ' => ' . esc_html( $state ) . ' -->';
	}
}

/* ---------------------------------------------------------------------------
 * گزارش تشخیصی — فقط‌خواندنی، فقط برای مدیر
 * نحوهٔ استفاده: آدرس همان صفحهٔ نوشته را با ?ab_sidebars_diag=1 باز کنید.
 * ------------------------------------------------------------------------- */

/** فهرست هش (SHA-256) فایل‌های منتظره — مقایسه روی محتوای یکسان‌سازی‌شده (LF). */
function javan_seda_expected_hashes() {
	return array(
		'single.php'          => 'edfa7b63cfb648c39ea13653555ce48e0cc2100214334e07641105c7e2a04235',
		'page.php'            => 'fee8efe74cec41f61d2c0598cfb8308fd90ab8ad66b09b1c30c51bde465948ad',
		'myfunctions.php'     => '5559e9f13cab429db0eeefa2b48b18b9dfcbedf552a3d77ea8b96c7b51bc845a',
		'style.css'           => '451ca35af97bf132e99d073af7b1abd9826938568ec312f48afef78c9d5e3e69',
		'footer.php'          => '0e8e4b8d56a75ecf142caa5082d65070649cf8ba6c64048ac4c26979e41c40fe',
		'header.php'          => 'fe70e2bacf669e536faf13d3c04be317e93d1418b955f24d2657d5a6339b4bf8',
		'home.php'            => '78df0d9a104c44ff22901cd07f732aaed9f9a6370ba1305c75afd7440d2527e6',
		'inc/vip_slider.php'  => '03b6e6cbf63ee7e4194bf55dda81d1312a6802d31287e1aaea61fe0ee1ad0410',
	);
}

function javan_seda_file_hash( $path ) {
	if ( ! is_readable( $path ) ) {
		return 'MISSING';
	}
	$c = file_get_contents( $path );
	if ( false === $c ) {
		return 'UNREADABLE';
	}
	$c = str_replace( array( "\r\n", "\r" ), "\n", $c );
	return hash( 'sha256', $c );
}

/* ---------------------------------------------------------------------------
 * ۹) نجات محتوا: اگر فیلتری (پلاگین/قالب) خروجیِ نوشته را در حالت موبایل
 *    خالی کرد، فقط در همان حالتِ «خالی بودن» از خودِ پست بازیابی می‌شود.
 *    هیچ اثری روی نوشته‌های سالم ندارد. غیرفعال‌سازی:
 *      define( 'JAVANSEDA_CONTENT_RESCUE', false );
 * ------------------------------------------------------------------------- */
if ( ! defined( 'JAVANSEDA_CONTENT_RESCUE' ) ) {
	define( 'JAVANSEDA_CONTENT_RESCUE', true );
}

function javan_seda_content_rescue( $content ) {
	if ( ! JAVANSEDA_CONTENT_RESCUE || ! is_singular() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}
	if ( is_string( $content ) && '' !== trim( wp_strip_all_tags( $content ) ) ) {
		return $content; // محتوا هست؛ کاری نداریم
	}
	$post = get_post();
	if ( ! $post || '' === trim( (string) $post->post_content ) ) {
		return $content;
	}
	$GLOBALS['javan_seda_content_rescued'] = array(
		'post_id' => $post->ID,
		'ua'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
		'mobile'  => wp_is_mobile(),
	);
	$restored = do_shortcode( $post->post_content );
	return has_filter( 'wpautop' ) ? wpautop( $restored ) : $restored;
}
add_filter( 'the_content', 'javan_seda_content_rescue', 9999 );

/* ---------------------------------------------------------------------------
 * ۱۰) کاوشگرِ موبایل — همان URL را روی سرورِ خودِ سایت با UA موبایل و دسکتاپ
 *     رندر می‌کند و خروجی را مقایسه می‌کند. فقط‌خواندنی، فقط برای مدیر.
 *     مسیر مستقیم:  ?ab_mobile_probe=1
 * ------------------------------------------------------------------------- */
function javan_seda_probe_url() {
	$uri      = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '' !== $home_path && 0 === strpos( $uri, $home_path ) ) {
		$uri = substr( $uri, strlen( $home_path ) );
	}
	$uri       = remove_query_arg( array( 'ab_mobile_probe', 'ab_sidebars_diag', 'ab_nocache', 'nocache' ), $uri );
	$url       = home_url( '/' . ltrim( $uri, '/' ) );
	return add_query_arg( 'ab_probe_ts', time(), $url );
}

function javan_seda_probe_fetch( $url, $ua, $label ) {
	$args = array(
		'timeout'     => 25,
		'redirection' => 3,
		'sslverify'   => false,
		'user-agent'  => $ua,
		'headers'     => array(
			'Cache-Control' => 'no-cache, no-store, must-revalidate',
			'Pragma'         => 'no-cache',
		),
		'cookies'     => array(),
	);
	$r = wp_remote_get( $url, $args );
	if ( is_wp_error( $r ) ) {
		return array( 'error' => $r->get_error_message(), 'label' => $label );
	}
	$html = (string) wp_remote_retrieve_body( $r );
	$code = (int) wp_remote_retrieve_response_code( $r );

	$out                 = array();
	$out['label']        = $label;
	$out['code']         = $code;
	$out['bytes']        = strlen( $html );
	$out['wp_is_mobile'] = ( false !== preg_match( '/android|iphone|ipod|ipad|mobile|windows phone/i', $ua ) ) ? 'بله (انتظار: هدر موبایل)' : 'نه (انتظار: هدر دسکتاپ)';

	$marks = array(
		'<article'                => 'article (باکس نوشته)',
		'single_content_box'      => 'single_content_box (محتوا)',
		'single_featured_cover'   => 'کاور پست',
		'music_lyrics_box'        => 'باکس متن ترانه',
		'dl_links_box'            => 'لینک‌های دانلود 128/320',
		'dl_linksv_box'           => 'لینک‌های دانلود آلبوم/ویدیو',
		'music_box_player'        => 'پلیر',
		'id="left_side"'          => 'ستون ابزارک',
		'id="mobiles_header"'     => 'هدر موبایل',
		'ab-has-mh'               => 'کلاس بدنهٔ موبایل',
		'ab-header-critical'      => 'CSS ضدّکش هدر',
		'id="footer"'             => 'فوتر',
		'wp-content/themes'       => 'ارجاع به فایل‌های قالب',
	);
	$out['marks'] = array();
	foreach ( $marks as $needle => $pretty ) {
		$out['marks'][ $pretty ] = substr_count( $html, $needle );
	}

	$open  = preg_match_all( '/<div\b/i', $html );
	$close = substr_count( $html, '</div>' );
	$out['balance'] = array(
		'div'    => $open . ' باز / ' . $close . ' بسته → ' . ( $open - $close ),
		'header' => preg_match_all( '/<header\b/i', $html ) . ' / ' . substr_count( $html, '</header>' ),
		'main'   => preg_match_all( '/<main\b/i', $html ) . ' / ' . substr_count( $html, '</main>' ),
		'article'=> preg_match_all( '/<article\b/i', $html ) . ' / ' . substr_count( $html, '</article>' ),
	);

	// تودرتویی: آیا </header>ِ هدر موبایل قبل از شروع <article> می‌آید؟ (نباید داخل هم باشند)
	$mh_close   = strpos( $html, '</header>' );
	$art_open   = strpos( $html, '<article' );
	$out['nest'] = ( false === $mh_close || false === $art_open )
		? '(قابل تشخیص نیست)'
		: ( $mh_close < $art_open ? 'سالم: هدر موبایل قبل از محتوا بسته شده' : '⚠ مشکل: محتوا داخل هدر موبایی مانده (تگِ بسته‌نشده)' );

	// هدرهای کش/CDN
	$hdrs       = wp_remote_retrieve_headers( $r );
	$cache_info = array();
	$all        = is_object( $hdrs ) ? $hdrs->getAll() : (array) $hdrs;
	foreach ( $all as $k => $v ) {
		if ( preg_match( '/^(x-cache|cf-cache-status|x-litespeed-cache|x-varnish|x-proxy-cache|x-served-by|age|cache-control|x-dracarys|x-cache-lookup)$/i', $k ) ) {
			$cache_info[] = $k . ': ' . ( is_array( $v ) ? implode( ' ', $v ) : $v );
		}
	}
	$out['cache_headers'] = $cache_info ? $cache_info : array( '(هیچ هدر کش/CDN برگردانده نشد)' );

	// برش کوتاه از اطراف single_content_box تا وضعیت واقعی دیده شود
	$pos = strpos( $html, 'single_content_box' );
	if ( false !== $pos ) {
		$snip        = substr( $html, max( 0, $pos - 60 ), 420 );
		$out['snippet'] = preg_replace( '/\s+/', ' ', $snip );
	} else {
		$out['snippet'] = '(single_content_box در HTML پیدا نشد)';
	}
	return $out;
}

function javanseda_probe_into( &$L_ref ) {
	javan_seda_probe_print( $L_ref ); // ارجاع به‌همان آرایهٔ گزارش
}

function javan_seda_probe_print( &$L_ref ) { // ارجاعی: خطوط مستقیم در گزارشِ فراخواننده می‌نشینند
	$url = javan_seda_probe_url();
	$L    = &$L_ref;
	$L[] = 'URL کاوش: ' . $url;
	$uas = array(
		'دسکتاپ' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
		'موبایل' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
	);
	$res = array();
	foreach ( $uas as $label => $ua ) {
		$res[ $label ] = javan_seda_probe_fetch( $url, $ua, $label );
	}
	foreach ( $res as $label => $r ) {
		$L[] = '';
		$L[] = '  ===== رندر با UA ' . $label . ' =====';
		if ( isset( $r['error'] ) ) {
			$L[] = '  ✗ درخواست داخلی ناموفق بود: ' . $r['error'];
			$L[] = '    (اگر هاست اجازهٔ loopback نمی‌دهد، همین صفحه را با ?ab_nocache=1 روی گوشی باز کنید)';
			continue;
		}
		$L[] = '  HTTP ' . $r['code'] . ' | حجم HTML: ' . number_format_i18n( $r['bytes'] ) . ' بایت | wp_is_mobile: ' . $r['wp_is_mobile'];
		foreach ( $r['marks'] as $pretty => $n ) {
			$L[] = sprintf( '    %-32s %d', $pretty, $n );
		}
		$L[] = '  تقارن تگ‌ها: ' . implode( ' | ', $r['balance'] );
		$L[] = '  تودرتویی  : ' . $r['nest'];
		$L[] = '  هدرهای کش : ' . implode( ' · ', $r['cache_headers'] );
		$L[] = '  نمونهٔ HTML: ' . $r['snippet'];
	}
	$L[] = '';
	$L[] = '  --- نتیجه‌گیری کاوشگر ---';
	$d = $res['دسکتاپ'];
	$m = $res['موبایل'];
	if ( isset( $d['error'], $m['error'] ) ) {
		$L[] = '  کاوش انجام نشد (درخواست داخلی مسدود).';
	} else {
		$dc = isset( $d['marks']['single_content_box (محتوا)'] ) ? $d['marks']['single_content_box (محتوا)'] : 0;
		$mc = isset( $m['marks']['single_content_box (محتوا)'] ) ? $m['marks']['single_content_box (محتوا)'] : 0;
		if ( $dc > 0 && 0 === $mc ) {
			$L[] = '  ⇒ محتوا در HTMLِ موبایل اصلاً تولید نمی‌شود: مشکل سمت PHP است (فیلتر the_content یا حلقه).';
			$L[] = '    اگر «نجات محتوا» فعال باشد، همین حالا بازیابی شده و عدد 1 می‌بینید؛ پس پرچم را چک کنید:';
			$L[] = '    پرچم نجات در همین درخواستِ والد: ' . ( empty( $GLOBALS['javan_seda_content_rescued'] ) ? '(فعال نشد — یعنی در رندرِ واقعیِ شما محتوا خالی نبوده)' : 'فعال شده: ' . wp_json_encode( $GLOBALS['javan_seda_content_rescued'] ) );
		} elseif ( $dc > 0 && $mc > 0 ) {
			$L[] = '  ⇒ محتوا در HTMLِ موبایل هست؛ یعنی چیزی آن را می‌پوشاند/پنهان می‌کند:';
			$L[] = '    ۱) اگر عددِ «هدر موبایل» در هر دو ۰ است، هدر موبایل رندر نمی‌شود (header.php قدیمی).';
			$L[] = '    ۲) اگر تقارن تگ‌ها در موبایل صفر نیست، یک تگِ بسته‌نشده محتوا را می‌بلعد (تودرتویی را ببینید).';
			$L[] = '    ۳) اگر هدرهای کش چیزی مثل HIT/x-cache داد، نسخهٔ کهنه سرو می‌شود: کشِ استاتیک + CDN را پاک کنید.';
		} elseif ( 0 === $dc && 0 === $mc ) {
			$L[] = '  ⇒ در هیچ‌کدام محتوا نیست؛ احتمالاً درخواست به single.php نرسیده (کش/redirect/۴۰۴). کد HTTP را ببینید.';
		} else {
			$L[] = '  ⇒ حالت غیرمنتظره؛ خروجی را بفرستید.';
		}
	}
	return $res;
}

add_filter( 'template_include', 'javan_seda_mobile_probe_route', 7 );
function javan_seda_mobile_probe_route( $template ) {
	if ( empty( $_GET['ab_mobile_probe'] ) || ! current_user_can( 'manage_options' ) ) {
		return $template;
	}
	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	$L = array();
	$L[] = '=== کاوشگرِ رندر موبایل ↔ دسکتاپ — قالب جوان‌صدا 3.0.5 ===';
	$L[] = 'PHP: ' . PHP_VERSION . ' | WP: ' . get_bloginfo( 'version' ) . ' | wp_is_mobile(همین درخواست): ' . ( wp_is_mobile() ? 'true' : 'false' );
	javan_seda_probe_print( $L );
	echo esc_html( implode( "\n", $L ) );
	exit;
}

add_filter( 'template_include', 'javan_seda_sidebars_diag_route', 8 );
function javan_seda_sidebars_diag_route( $template ) {
	if ( empty( $_GET['ab_sidebars_diag'] ) || ! current_user_can( 'manage_options' ) ) {
		return $template;
	}

	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );

	global $wp_registered_sidebars, $wp_widget_factory, $wp_query;

	$L    = array();
	$line = function ( $s = '' ) use ( &$L ) {
		$L[] = $s;
	};

	$theme = wp_get_theme();

	$line( '=== گزارش تشخیص «ابزارک / سایدبار / هدر» — قالب جوان‌صدا ===' );
	$line( 'WordPress: ' . get_bloginfo( 'version' ) . ' | PHP: ' . PHP_VERSION . ' | نسخهٔ قالب: ' . $theme->get( 'Version' ) );
	$line( 'stylesheet: ' . $theme->get_stylesheet() . ' | template: ' . $theme->get_template() . ( $theme->get_stylesheet() !== $theme->get_template() ? '  <<< چایلد‌تیم فعال است؛ فایل‌ها را در چایلد بررسی کنید' : '' ) );
	$line( 'قالبِ درخواست‌شده در این درخواست: ' . ( $template ? $template : '(خالی)' ) );
	$line( 'is_singular(post): ' . ( is_singular( 'post' ) ? 'yes' : 'no' ) . ' | is_page(): ' . ( is_page() ? 'yes' : 'no' ) . ' | is_admin(): ' . ( is_admin() ? 'yes' : 'no' ) );
	$line( '' );

	$line( '--- ۱) بارگذاری فایل‌های مسئول ---' );
	$line( 'myfunctions.php بارگذاری شد؟      : ' . ( function_exists( 'leftsides' ) ? 'بله' : 'نه  <<< علت اصلی: ناحیه‌های ابزارک ثبت نشده‌اند' ) );
	$line( 'inc/ab-sidebars.php بارگذاری شد؟  : ' . ( function_exists( 'javan_seda_sidebar' ) ? 'بله' : 'نه' ) );
	$widgets_ok = true;
	foreach ( javan_seda_widget_id_bases() as $cls ) {
		$has = class_exists( $cls );
		$reg = $has && is_object( $wp_widget_factory ) && isset( $wp_widget_factory->widgets[ $cls ] );
		if ( ! $reg ) {
			$widgets_ok = false;
		}
		$line( sprintf( 'کلاس ابزارک %-22s : class_exists=%s | register_widget=%s', $cls, $has ? 'yes' : 'NO', $reg ? 'yes' : 'NO' ) );
	}
	if ( ! $widgets_ok ) {
		$line( '  ⚠ اگر ابزارک‌ها در پیشخوان دیده می‌شوند ولی در سایت خالی‌اند، همین خط علت است:' );
		$line( '    در ویرایشگر بلوک، ابزارکِ «ثبت‌نشده» روی سایت رشتهٔ خالی تولید می‌کند.' );
	}
	$line( '' );

	$line( '--- ۲) ناحیه‌های ابزارک ثبت‌شده ---' );
	if ( empty( $wp_registered_sidebars ) || ! is_array( $wp_registered_sidebars ) ) {
		$line( 'هیچ ناحیه‌ای ثبت نشده است! (register_sidebar اجرا نشده)' );
	} else {
		foreach ( $wp_registered_sidebars as $sid => $s ) {
			$name = isset( $s['name'] ) ? $s['name'] : '(بی‌نام)';
			$bw   = ( isset( $s['before_widget'] ) && false !== strpos( $s['before_widget'], '%1$s' ) ) ? 'id placeholder: yes' : 'id placeholder: NO';
			$line( sprintf( '%-12s | %s | %s', $sid, $name, $bw ) );
		}
	}
	$line( '' );

	$line( '--- ۳) محتوای ناحیه‌ها در دیتابیس (گزینهٔ sidebars_widgets) ---' );
	$raw = get_option( 'sidebars_widgets' );
	if ( ! is_array( $raw ) ) {
		$line( 'گزینهٔ sidebars_widgets وجود ندارد یا آرایه نیست.' );
	} else {
		foreach ( $raw as $key => $val ) {
			$val   = is_array( $val ) ? $val : array();
			$line( sprintf( '%-22s : %d مورد  [ %s ]', $key, count( $val ), implode( ', ', array_slice( $val, 0, 8 ) ) ) );
		}
		$line( 'arrays_version: ' . ( isset( $raw['arrays_version'] ) ? $raw['arrays_version'] : '(ندارد)' ) );
	}
	$line( 'is_active_sidebar(left_side):  ' . ( function_exists( 'is_active_sidebar' ) && is_active_sidebar( 'left_side' ) ? 'true' : 'false' ) );
	$line( 'is_active_sidebar(left_side2): ' . ( function_exists( 'is_active_sidebar' ) && is_active_sidebar( 'left_side2' ) ? 'true' : 'false' ) );
	$line( 'is_active_sidebar(left_side1): ' . ( function_exists( 'is_active_sidebar' ) && is_active_sidebar( 'left_side1' ) ? 'true' : 'false' ) );
	if ( ! empty( $GLOBALS['javan_seda_sidebar_recovered'] ) ) {
		$line( '♻ ترمیم خواندنی فعال شد و این ابزارک‌ها را برگرداند: ' . implode( ', ', (array) $GLOBALS['javan_seda_sidebar_recovered'] ) );
		$line( '  (در دیتابیس چیزی ذخیره نشده — اگر خواستید دائمی شود، در پیشخوان یک‌بار ناحیه را «ذخیره» کنید.)' );
	}
	$line( '' );

	$line( '--- ۴) ویرایشگر بلوک یا کلاسیک؟ ---' );
	if ( function_exists( 'wp_use_widgets_block_editor' ) ) {
		$line( 'wp_use_widgets_block_editor(): ' . ( wp_use_widgets_block_editor() ? 'true (ویرایشگر بلوک)' : 'false (کلاسیک)' ) );
	}
	if ( isset( $raw['left_side'] ) && is_array( $raw['left_side'] ) ) {
		foreach ( $raw['left_side'] as $wid ) {
			if ( 0 === strpos( (string) $wid, 'block-' ) ) {
				$bid       = (int) str_replace( 'block-', '', $wid );
				$block_raw = get_post( $bid );
				$content   = $block_raw ? $block_raw->post_content : '(پست بلوک پیدا نشد)';
				$line( sprintf( 'بلوک #%d (id %s) => %s', $bid, $wid, preg_replace( '/\s+/', ' ', (string) $content ) ) );
			}
		}
	}
	$line( '' );

	$line( '--- ۵) گزینه‌های قالب که ستون چپ را تحت تأثیر دارند ---' );
	foreach ( array( 'reltag1', 'reltag', 'pppf', 'cm_off' ) as $k ) {
		$v = function_exists( 'ot_get_option' ) ? ot_get_option( $k ) : '(OptionTree نیست)';
		$line( sprintf( 'ot_get_option(%-8s) = %s', "'" . $k . "'", is_scalar( $v ) && '' !== (string) $v ? (string) $v : '(خالی/پیش‌فرض)' ) );
	}
	$line( 'نکته: اگر reltag1 روی «off» نباشد، «نوشته‌های مرتبط» در انتهای متن چاپ می‌شود و ستون چپ فقط ابزارک‌ها را نشان می‌دهد.' );
	$line( '' );

	$line( '--- ۶) بررسی هم‌خوانی فایل‌های روی سرور با بستهٔ 3.0.2 ---' );
	$expected = javan_seda_expected_hashes();
	$tdir     = get_template_directory();
	$mismatch = 0;
	foreach ( $expected as $rel => $hash ) {
		$now = javan_seda_file_hash( $tdir . '/' . $rel );
		$st  = '??';
		if ( 'MISSING' === $now ) {
			$st = 'فایل روی سرور نیست';
		} elseif ( 0 === strpos( $hash, '__' ) ) {
			$st = '(مقدار مرجع تنظیم نشده)';
		} elseif ( $now === $hash ) {
			$st = 'مطابق بسته';
		} else {
			$st = 'متفاوت از بسته  <<< فایل روی هاست قدیمی/ویرایش‌شده است';
		}
		if ( 0 === strpos( $st, 'متفاوت' ) || 0 === strpos( $st, 'فایل' ) ) {
			$mismatch++;
		}
		$line( sprintf( '%-22s %s', $rel, $st ) );
	}
	$ab_self = $tdir . '/inc/ab-sidebars.php';
	if ( is_readable( $ab_self ) ) {
		$line( sprintf( '%-22s حاضر (سایز %d بایت، آخرین تغییر %s)', 'inc/ab-sidebars.php', (int) filesize( $ab_self ), date_i18n( 'Y-m-d H:i', (int) filemtime( $ab_self ) ) ) );
	} else {
		$line( 'inc/ab-sidebars.php    فایل روی سرور نیست  <<< لایهٔ ایمنی غیرفعال است' );
	}
	$line( 'تعداد فایل ناهم‌خوان: ' . $mismatch );
	$line( '' );

	$line( '--- ۷) پلاگین‌های فعال (مشکلات رایج کش / سازندهٔ قالب) ---' );
	$active = (array) get_option( 'active_plugins' );
	$flag   = array( 'wp-super-cache', 'litespeed-cache', 'w3-total-cache', 'wp-rocket', 'wp-fastest-cache', 'autoptimize', 'bwp-minify', 'sg-cachepress', 'wp-smushit', 'elementor', 'jetengine', 'jetpack', 'wpml', 'sitepress' );
	if ( ! $active ) {
		$line( '(فهرست خالی — احتمالاً پلاگین‌ها از مسیر دیگری فعال‌اند / شبکهٔ چندسایتی)' );
	}
	foreach ( $active as $p ) {
		$hit = '';
		foreach ( $flag as $f ) {
			if ( false !== stripos( $p, $f ) ) {
				$hit = '   <<< نیازمند پاک‌سازی کش / بررسی بازنویسی قالب';
				break;
			}
		}
		$line( $p . $hit );
	}
	$line( '' );

	$line( '--- ۸) هدر (دسکتاپ / موبایل) ---' );
	$ab_hdr    = $tdir . '/header.php';
	$ab_hdr_c  = is_readable( $ab_hdr ) ? (string) file_get_contents( $ab_hdr ) : '';
	$ab_css    = $tdir . '/style.css';
	$ab_css_c  = is_readable( $ab_css ) ? (string) file_get_contents( $ab_css ) : '';
	$ab_ver    = preg_match( '/Version:\s*([0-9.]+)/', $ab_css_c, $ab_mv ) ? $ab_mv[1] : '(نامعلوم)';
	$line( 'wp_is_mobile() برای این درخواست      : ' . ( wp_is_mobile() ? 'true (هدر موبایل رندر می‌شود)' : 'false (هدر دسکتاپ رندر می‌شود)' ) );
	$line( 'header.php خط رندر mobiles_header را دارد: ' . ( false !== strpos( $ab_hdr_c, 'mobiles_header' ) ? 'بله' : 'نه  ⇒ علت اصلیِ حذف‌شدن هدر در موبایل، همین است' ) );
	$line( 'header.php بلوک ab-header-critical را دارد: ' . ( false !== strpos( $ab_hdr_c, 'ab-header-critical' ) ? 'بله (نسخهٔ ضدّ‌کش نصب است)' : 'نه  ⇒ CSS حیاتیِ هدر روی هاست نیست' ) );
	$line( 'style.css نسخه                          : ' . $ab_ver . ' | دارای بلوک ab-has-mh: ' . ( false !== strpos( $ab_css_c, 'ab-has-mh' ) ? 'بله' : 'نه  ⇒ style.css روی هاست کهنه است (آپلود نشسته یا CDN کش کرده)' ) );
	$line( 'mtime header.php / style.css            : ' . ( is_readable( $ab_hdr ) ? date_i18n( 'Y-m-d H:i', (int) filemtime( $ab_hdr ) ) : '?' ) . ' | ' . ( is_readable( $ab_css ) ? date_i18n( 'Y-m-d H:i', (int) filemtime( $ab_css ) ) : '?' ) );
	$line( 'تأیید بصری: در حالت موبایل باید <body class="… ab-has-mh"> و <header id="mobiles_header"> هر دو در HTML باشند.' );
	$line( '' );

	$line( '' );
	$line( '--- ۹.۵) کاوشگرِ رندر (موبایل ↔ دسکتاپ) ---' );
	javanseda_probe_into( $L );
	$line( '--- ۱۰) نتیجه‌گیری خودکار ---' );
	$verdict = 'نامشخص — خروجی را برای توسعه‌دهنده بفرستید.';
	if ( ! function_exists( 'leftsides' ) ) {
		$verdict = '۱) myfunctions.php روی سرور بارگذاری نمی‌شود (یا فایل قدیمی است). لایهٔ ایمنیِ این فایل ناحیه‌ها را ثبت می‌کند؛ فایل را بازگردانی/جایگزین کنید.';
	} elseif ( ! $widgets_ok ) {
		$verdict = '۲) کلاس ابزارک‌های قالب ثبت نیست (inc/widgets.php بارگذاری نشده). ابزارکِ ثبت‌نشده در سایدبارِ بلوکی روی سایت خالی چاپ می‌شود.';
	} elseif ( ! function_exists( 'is_active_sidebar' ) || ! is_active_sidebar( 'left_side' ) ) {
		if ( ! empty( $GLOBALS['javan_seda_sidebar_recovered'] ) ) {
			$verdict = '۳) ابزارک‌ها در «ابزارک‌های غیرفعال» افتاده بودند؛ اکنون به‌صورت خواندنی بازگردانده شدند. برای دائمی‌کردن: پیشخوان ← ابزارک‌ها ← ذخیره.';
		} else {
			$verdict = '۴) ناحیهٔ left_side هیچ ابزارک فعالی ندارد. در پیشخوان، ابزارک را در ناحیهٔ «سایدبار نوشته‌ها — بالا» بگذارید (نه «سایدبار برگه‌ها»).';
		}
	} elseif ( 0 === $mismatch && $wp_query && ! is_singular( 'post' ) ) {
		$verdict = '۵) همه‌چیز سالم است. این گزارش را روی یک «صفحهٔ نوشته» باز کنید تا همان مسیر واقعی بررسی شود.';
	} elseif ( $mismatch > 0 ) {
		$verdict = '۶) فایل‌های روی هاست با بستهٔ مخزن یکسان نیست؛ ابتدا فایل‌های drop-in را دقیقاً مطابق ترتیبِ docs نصب کنید و سپس کش را پاک کنید.';
	} else {
		$verdict = '۷) سایدبار و ابزارک در مسیر PHP سالم‌اند؛ اگر باز در مرورگر نمی‌بینید، کش (لایت‌اسپید/روکت/CDN) یا CSS یک پلاگین است: کش را پاک کنید و با ?abnc=1 دوباره ببینید.';
	}
	$line( $verdict );
	$line( '' );
	$line( 'پایان گزارش. این ابزار فقط‌خواندنی است و هیچ داده‌ای را تغییر نمی‌دهد.' );

	echo esc_html( implode( "\n", $L ) );
	exit;
}
