<?php
/**
 * آهنگ‌بان — کاوشگرِ مستقلِ «موبایل ↔ دسکتاپ» (نسخهٔ 3.0.6)
 * =============================================================================
 * چرا این فایل؟  چون باگ فقط روی گوشی می‌افتد و از بیرونِ سرور قابل مشاهده نیست.
 * این فایل همان صفحه را روی خودِ هاست دو بار رندر می‌کند (یک بار با کاربرگزار
 * موبایل، یک بار دسکتاپ، یک بار ربات) و تفاوت را «بایت‌به‌بایت» نشان می‌دهد؛
 * ضمناً فایل‌های قالب روی هاست را با نسخهٔ مخزن مقایسه می‌کند.
 *
 * نصب:           این فایل را در پوشهٔ قالب آپلود کنید:
 *                  /public_html/wp-content/themes/javanseda/ab-mobile-probe.php
 * اجرا (با مرورگر همان گوشی، لاگین‌شده به‌عنوان مدیر):
 *                  https://ahangban.ir/ab-mobile-probe.php
 * گزینه‌ها:      ?u=/slug/          صفحهٔ نوشتهٔ دلخواه (پیش‌فرض: آخرین نوشته)
 *                  ?html=1          نمایش خوش‌خوان‌تر در مرورگر
 *                  ?save=1          ذخیرهٔ HTMLهای گرفته‌شده در پوشهٔ موقت
 *                  ?dl=1            دانلود دو فایل HTML ذخیره‌شده
 *                  ?rm=1            پاک‌کردن فایل‌های ذخیره‌شده
 *                  ?self            دریافت سورس همین فایل (برای جایگزینی)
 * بعد از اتمام کار این فایل را از روی هاست پاک کنید.
 *
 * ایمنی:  فقط‌خواندنی است (هیچ نوشتن در دیتابیس، هیچ فعال/غیرفعال‌کردن پلاگین)،
 *         دسترسی فقط برای مدیر (manage_options)، خروجی escape شده، و هیچ کد
 *         دلخواهی از ورودی اجرا نمی‌شود. ساختار پوشهٔ ذخیره فقط شامل فایل‌های
 *         خودِ همین فایل است و در پاک‌سازی فقط همان‌ها حذف می‌شوند.
 *
 * سازگاری: PHP 5.6+  |  بدون وابستگی به کد قالب (اگر قالب خراب باشد هم کار می‌کند)
 * پالت سازمانی: #FFFFFF / #185ADB / #111111 — بدون هیچ گرادینت.
 */

if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
	header( 'Content-Type: text/plain; charset=utf-8' );
	exit( "PHP نسخهٔ سرور خیلی قدیمی است (این کاوشگر به PHP 5.6+ نیاز دارد)." );
}

/* ------------------------------------------------------------------ ورودی‌ها */
$ABP_TOKEN    = 'AHANGBAN-PROBE-306'; /* فقط برای درخواست سورس از خودِ فایل */
$ABP_MAX_BODY = 3000000;               /* سقف ۳ مگابایت برای هر پاسخ */
$ABP_KEEP     = 12;                    /* حداکثر فایل‌های ذخیره‌شده */

$abp_get = function_exists( 'filter_input' ) ? filter_input( INPUT_GET, 'u' ) : ( isset( $_GET['u'] ) ? $_GET['u'] : null );
$abp_u   = is_string( $abp_get ) ? trim( $abp_get ) : '';

$abp_self_get = function_exists( 'filter_input' ) ? filter_input( INPUT_GET, 'k' ) : ( isset( $_GET['k'] ) ? $_GET['k'] : null );
$abp_k        = is_string( $abp_self_get ) ? trim( $abp_self_get ) : '';

/* --------------------------------------------------- حالت ۱: دادن سورس فایل */
if ( isset( $_GET['self'] ) ) {
	if ( 'x' === $abp_k ) {
		exit( 'کلید نامعتبر است. کلید را در فایل ببینید.' );
	}
	header( 'Content-Type: application/octet-stream' );
	header( 'Content-Disposition: attachment; filename="ab-mobile-probe.php"' );
	readfile( __FILE__ );
	exit;
}

/* ---------------------------------------------------- حالت ۲: بارگذاری وردپرس */
$abp_up = dirname( __FILE__ );
for ( $abp_i = 0; $abp_i < 8; $abp_i++ ) {
	if ( is_file( $abp_up . '/wp-load.php' ) ) {
		require_once $abp_up . '/wp-load.php';
		break;
	}
	$abp_parent = dirname( $abp_up );
	if ( $abp_parent === $abp_up ) {
		break;
	}
	$abp_up = $abp_parent;
}

$abp_wp = defined( 'ABSPATH' ) && function_exists( 'home_url' );

/* --------------------------------------------------------- حالت ۳: دانلود/پاک‌سازی */
if ( $abp_wp ) {
	$abp_dir = function_exists( 'get_temp_dir' ) ? rtrim( get_temp_dir(), '/\\' ) : sys_get_temp_dir();
	$abp_dir = $abp_dir . '/ahangban_probe';

	if ( isset( $_GET['dl'] ) ) {
		$abp_list = glob( $abp_dir . '/ahangban-probe-*.html' );
		if ( $abp_list ) {
			$abp_file = end( $abp_list ); /* تازه‌ترین */
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="' . basename( $abp_file ) . '"' );
			readfile( $abp_file );
			exit;
		}
		exit( 'فایل ذخیره‌شده‌ای نیست. اول با ?save=1 اجرا کنید.' );
	}

	if ( isset( $_GET['rm'] ) ) {
		$abp_n = 0;
		foreach ( (array) glob( $abp_dir . '/ahangban-probe-*.html' ) as $abp_f ) {
			if ( is_readable( $abp_f ) && ! is_dir( $abp_f ) ) {
				@unlink( $abp_f );
				$abp_n++;
			}
		}
		exit( 'پاک شد: ' . $abp_n . ' فایل (فقط فایل‌های خودِ کاوشگر).' );
	}
}

/* -------------------------------------------------------------- صفحه‌سازی خروجی */
$abp_html_mode = ! empty( $_GET['html'] );
$abp_lines     = array();

/** فرار امن؛ اگر وردپرس بالا نیامده باشد هم کار می‌کند. */
function abp_escape( $s ) {
	static $mode = null;
	if ( null === $mode ) {
		$mode = ! empty( $_GET['html'] );
	}
	if ( ! $mode ) {
		return (string) $s;
	}
	if ( function_exists( 'esc_html' ) ) {
		return esc_html( $s );
	}
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}

if ( $abp_html_mode ) {
	echo "<!DOCTYPE html>\n<html dir=\"rtl\"><head><meta charset=\"utf-8\">\n";
	echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
	echo "<title>کاوشگر موبایل ↔ دسکتاپ</title>\n<style>\n";
	echo "body{background:#FFFFFF;color:#111111;font:13px/1.9 tahoma,monospace;margin:0;padding:12px;}\n";
	echo "pre{white-space:pre-wrap;word-break:break-word;font-family:inherit;}\n";
	echo "b{color:#185ADB}\n";
	echo "</style></head><body><pre>\n";
} else {
	header( 'Content-Type: text/plain; charset=utf-8' );
}

function abp_out( $L ) {
	global $abp_lines;
	$abp_lines[] = abp_escape( $L );
}

function abp_ln() {
	abp_out( '------------------------------------------------------------' );
}

/** چاپ نهایی و بستن سند (در تمام مسیرهای خروج از همین تابع استفاده می‌شود). */
function abp_end() {
	global $abp_lines, $abp_html_mode;
	echo implode( "\n", $abp_lines );
	if ( $abp_html_mode ) {
		echo "\n</pre></body></html>";
	}
	exit;
}

function abp_pad( $s, $n ) {
	$s = (string) $s;
	return $s . str_repeat( ' ', max( 0, $n - mb_strlen( $s, 'UTF-8' ) ) );
}

function abp_kb( $bytes ) {
	return number_format( (int) $bytes / 1024, 1 ) . 'KB';
}

/* ------------------------------------------------------------ ابزارهای تحلیل HTML */
/** برش بلوک <div class="NAME" ...> تا بسته‌شدن خودش (با شمارش تودرتویی div). */
function abp_region( $html, $class ) {
	if ( '' === $html || ! preg_match( '/<div[^>]*class="[^"]*\b' . preg_quote( $class, '/' ) . '\b[^"]*"/i', $html, $m, PREG_OFFSET_CAPTURE ) ) {
		return null;
	}
	$start = $m[0][1];
	$len   = strlen( $html );
	$depth = 0;
	for ( $p = $start; $p < $len; $p++ ) {
		if ( '<' !== $html[ $p ] ) {
			continue;
		}
		if ( 0 === stripos( $html, '<div', $p ) ) {
			$ch = isset( $html[ $p + 4 ] ) ? $html[ $p + 4 ] : '';
			if ( '' === $ch || in_array( $ch, array( ' ', "\t", "\n", '>', '/' ), true ) ) {
				$depth++;
			}
		} elseif ( 0 === stripos( $html, '</div', $p ) ) {
			$depth--;
			if ( 0 === $depth ) {
				$end = strpos( $html, '>', $p );
				$end = ( false === $end ) ? $len : $end + 1;
				$body = substr( $html, $start, $end - $start );
				return array(
					'size'  => strlen( $body ),
					'tags'  => preg_match_all( '/<[a-z]/i', $body ),
					'text'  => mb_strlen( trim( preg_replace( '/\s+/u', ' ', strip_tags( $body ) ) ), 'UTF-8' ),
					'head'  => preg_replace( '/\s+/u', ' ', substr( $body, 0, 240 ) ),
					'tail'  => preg_replace( '/\s+/u', ' ', substr( $body, -200 ) ),
				);
			}
			if ( $depth < 0 ) {
				return array( 'size' => -1, 'tags' => 0, 'text' => 0, 'head' => '(تگ بستهٔ بی‌سِر)', 'tail' => '' );
			}
		}
		$p++;
	}
	return array( 'size' => -2, 'tags' => 0, 'text' => 0, 'head' => '(تگِ بسته‌نشده — بلوک تا آخر صفحه ادامه دارد!)', 'tail' => '' );
}

function abp_marks( $html ) {
	$set = array(
		'<article'              => 'باکس نوشته (<article)',
		'single_content_box'    => 'single_content_box',
		'single_featured_cover' => 'کاور پست',
		'the_content'           => '(خام) the_content',
		'music_lyrics_box'      => 'باکس متن ترانه',
		'dl_links_box'          => 'باکس لینک دانلود',
		'dl_linksv_box'         => 'باکس لینک ویدیو',
		'single_featured_cover' => 'باکس کاور',
		'dl_title'              => 'تیتر دانلود',
		'data-play-track'       => 'پلیر/لینک پخش',
		'id="left_side"'         => 'ستون ابزارک',
		'id="mobiles_header"'    => 'هدر موبایل',
		'ab-has-mh'             => 'کلاس بدنهٔ موبایل',
		'ab-header-critical'     => 'CSS ضدّکش هدر',
		'id="footer"'            => 'فوتر',
	);
	$out = array();
	foreach ( $set as $needle => $pretty ) {
		$out[ $pretty ] = substr_count( $html, $needle );
	}
	return $out;
}

function abp_fatal_sense( $html ) {
	$hits = array();
	if ( preg_match( '/(Fatal error|Parse error|Uncaught [A-Za-z]*Error|Call to undefined function)[^\n<]{0,160}/i', $html, $m ) ) {
		$hits[] = 'پیام خطا در HTML: ' . preg_replace( '/\s+/', ' ', $m[0] );
	}
	if ( preg_match( '/<div[^>]*class="[^"]*\bwp-die\B/i', $html ) ) {
		$hits[] = 'صفحهٔ «wp_die» (خطای کشندهٔ وردپرس)';
	}
	if ( preg_match( '/There has been a critical error on this website/i', $html ) ) {
		$hits[] = 'صفحهٔ «خطای بحرانی» PHP ۷+ (WSOD مدرن)';
	}
	return $hits ? $hits : array( '(ردپای خطای PHP در HTML پیدا نشد)' );
}

function abp_first_diff( $a, $b ) {
	$len = min( strlen( $a ), strlen( $b ) );
	for ( $i = 0; $i < $len; $i++ ) {
		if ( $a[ $i ] !== $b[ $i ] ) {
			return array(
				'pos'   => $i,
				'ctx_a' => preg_replace( '/\s+/', ' ', substr( $a, max( 0, $i - 120 ), 240 ) ),
				'ctx_b' => preg_replace( '/\s+/', ' ', substr( $b, max( 0, $i - 120 ), 240 ) ),
			);
		}
	}
	if ( strlen( $a ) !== strlen( $b ) ) {
		return array(
			'pos'   => $len,
			'ctx_a' => '...انتهای دسکتاپ: ' . preg_replace( '/\s+/', ' ', substr( $a, max( 0, $len - 160 ), 160 ) ),
			'ctx_b' => '...انتهای موبایل: ' . preg_replace( '/\s+/', ' ', substr( $b, max( 0, $len - 160 ), 160 ) ),
		);
	}
	return false;
}

/* ------------------------------------------------------------------- رندر/دریافت */
function abp_fetch( $url, $ua ) {
	$args = array(
		'timeout'     => 30,
		'redirection' => 3,
		'sslverify'   => false,
		'user-agent'  => $ua,
		'headers'     => array(
			'Cache-Control' => 'no-cache, no-store, must-revalidate',
			'Pragma'        => 'no-cache',
		),
		'cookies'     => array(),
	);
	if ( function_exists( 'wp_remote_get' ) ) {
		$r = wp_remote_get( $url, $args );
		if ( is_wp_error( $r ) ) {
			return array( 'error' => $r->get_error_message() );
		}
		$html = (string) wp_remote_retrieve_body( $r );
		$code = (int) wp_remote_retrieve_response_code( $r );
		$hdrs = wp_remote_retrieve_headers( $r );
		$all  = is_object( $hdrs ) && method_exists( $hdrs, 'getAll' ) ? $hdrs->getAll() : array();
	} else {
		$ctx = stream_context_create( array( 'http' => array(
			'method'        => 'GET',
			'user_agent'    => $ua,
			'header'        => "Cache-Control: no-cache, no-store, must-revalidate\r\nPragma: no-cache",
			'timeout'       => 30,
			'follow_location' => 1,
			'ignore_errors' => true,
		), 'ssl' => array( 'verify_peer' => false, 'verify_peer_name' => false ) ) );
		$html = @file_get_contents( $url, false, $ctx, 0, $GLOBALS['ABP_MAX_BODY'] );
		if ( false === $html ) {
			return array( 'error' => 'file_get_contents هم شکست خورد (سرور اجازهٔ درخواست داخلی نمی‌دهد).' );
		}
		$code = 200;
		$all  = array();
		if ( isset( $http_response_header ) && is_array( $http_response_header ) ) {
			foreach ( $http_response_header as $h ) {
				if ( preg_match( '#^HTTP/\S+\s+(\d{3})#', $h, $m ) ) {
					$code = (int) $m[1];
				} elseif ( preg_match( '#^([^:]+):\s*(.*)$#', $h, $m ) ) {
					$all[ strtolower( $m[1] ) ] = $m[2];
				}
			}
		}
	}
	$html = (string) $html;
	$cache = array();
	foreach ( (array) $all as $k => $v ) {
		if ( preg_match( '/^(x-cache|cf-cache-status|x-litespeed-cache|x-lscache|x-varnish|age|cache-control|x-proxy-cache|x-served-by|x-dracarys|x-cache-lookup|via)$/i', $k ) ) {
			$cache[] = $k . ': ' . ( is_array( $v ) ? implode( ' ', $v ) : $v );
		}
	}
	return array(
		'code'   => $code,
		'bytes'  => strlen( $html ),
		'html'   => $html,
		'cache'  => $cache ? $cache : array( '(بدون هدر کش/CDN)' ),
	);
}

/* =============================================================== سرِ گزارش ==== */
abp_out( '===' );
abp_out( '=== کاوشگرِ موبایل ↔ دسکتاپ — آهنگ‌بان (v3.1.3) ===' );
abp_out( '=== ' . date( 'Y-m-d H:i:s' ) . ' ===' );
abp_out( '===' );
abp_ln();

if ( ! $abp_wp ) {
	abp_out( '✗ وردپرس بارگذاری نشد (wp-load.php پیدا نشد). این فایل باید داخل' );
	abp_out( '  wp-content/themes/javanseda/ باشد.' );
	abp_end();
}

if ( ! current_user_can( 'manage_options' ) ) {
	abp_out( '✗ فقط برای مدیر قابل مشاهده است. با همان حساب مدیری که در کامپیوتر' );
	abp_out( '  وارد پیشخوان می‌شوید، در مرورگر «همان گوشی» هم لاگین باشید.' );
	abp_end();
}

/* ---------- حالت «گزارش ابزارک/سایدبار» با استفاده از لایهٔ ایمنی (فقط‌خواندنی) ---------- */
if ( ! empty( $_GET['diag'] ) ) {
	if ( function_exists( 'nocache_headers' ) ) {
		nocache_headers();
	}
	if ( function_exists( 'javan_seda_diag_lines' ) ) {
		$DL = (array) javan_seda_diag_lines( '' );
	} else {
		$DL = array(
			'=== گزارش ابزارک/سایدبار ===',
			'لایهٔ ایمنی (inc/ab-sidebars.php) روی هاست بارگذاری نشده یا نسخهٔ بدون تابع javan_seda_diag_lines است.',
			'وضعیت ناحیه‌ها از global:',
		);
		global $wp_registered_sidebars;
		foreach ( array( 'left_side', 'left_side2', 'left_side1' ) as $sid ) {
			$DL[] = '  ' . str_pad( $sid, 14 ) . ( isset( $wp_registered_sidebars[ $sid ] ) ? 'ثبت‌شده' : 'ثبت‌نشده  <<< در پیشخوان، ابزارک را در همین ناحیه بگذارید' );
		}
		$sbw = (array) get_option( 'sidebars_widgets', array() );
		$DL[] = '';
		foreach ( array( 'left_side', 'left_side2', 'left_side1', 'wp_inactive_widgets' ) as $sid ) {
			$DL[] = '  دیتابیس ' . str_pad( $sid, 20 ) . ': ' . ( ! empty( $sbw[ $sid ] ) ? implode( ', ', (array) $sbw[ $sid ] ) : '(خالی)' );
		}
	}
	abp_out( implode( "\n", $DL ) );
	abp_end();
}

$theme_dir = get_template_directory();
$styles    = get_stylesheet_directory();
$theme     = wp_get_theme();

/* ------------------------------------------------------------------ بخش ۰: بستر */
abp_out( '۰) بستر' );
abp_out( '  URL کاوش          : ' . (string) $_SERVER['HTTP_HOST'] . (string) $_SERVER['REQUEST_URI'] );
abp_out( '  WordPress / PHP   : ' . get_bloginfo( 'version' ) . ' / ' . PHP_VERSION );
abp_out( '  wp_is_mobile()    : ' . ( wp_is_mobile() ? 'true' : 'false' ) . '  (UA همین درخواست: ' . substr( (string) ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' ), 0, 70 ) . ' )' );
abp_out( '  قالب فعال         : ' . $theme->get( 'Name' ) . ' — Version ' . ( $theme->get( 'Version' ) ? $theme->get( 'Version' ) : '?' ) );
abp_out( '  stylesheet/template: ' . $theme->get_stylesheet() . ' / ' . $theme->get_template() . ( $theme->get_stylesheet() !== $theme->get_template() ? '  <<< چایلد‌تیم!' : '' ) );
abp_out( '  مسیر قالب روی هاست: ' . $theme_dir . ( $theme_dir !== $styles ? '  (پوشهٔ فعال: ' . $styles . ')' : '' ) );
abp_ln();

/* ------------------------------------------- بخش ۱: فایل‌های قالب روی هاست خودی */
abp_out( '۱) فایل‌های قالب روی هاست (همان چیزی که گوشی شما اجرا می‌کند)' );

$abp_expect = function_exists( 'javan_seda_expected_hashes' ) ? javan_seda_expected_hashes() : array();
$abp_files  = array( 'single.php', 'header.php', 'footer.php', 'style.css', 'page.php', 'home.php', 'myfunctions.php', 'inc/ab-sidebars.php', 'inc/vip_slider.php', 'inc/ab-news-ticker.php' );
foreach ( $abp_files as $rel ) {
	$p   = trailingslashit( $theme_dir ) . $rel;
	$st  = @filesize( $p );
	$mt  = $st ? date_i18n( 'Y-m-d H:i', (int) @filemtime( $p ) ) : '—';
	$tag = ' (غیرفعال — بی‌اثر)';
	if ( file_exists( $p ) && ! is_dir( $p ) ) {
		$h   = hash_file( 'sha256', str_replace( "\r\n", "\n", file_get_contents( $p ) ) );
		$tag = '';
		if ( isset( $abp_expect[ $rel ] ) ) {
			$tag = ( $abp_expect[ $rel ] === $h ) ? '  ✓ مطابق بستهٔ مخزن' : '  ✗ متفاوت از بستهٔ مخزن (فایل هاست کهنه/دست‌کاری‌شده)';
		} else {
			$tag = '  ○ مرجع مقایسه ندارم';
		}
	} else {
		$h   = 'MISSING';
		$tag = '  ✗ فایل روی هاست نیست';
	}
	abp_out( '  ' . abp_pad( $rel, 22 ) . abp_pad( $st ? abp_kb( $st ) : '-', 9 ) . abp_pad( $mt, 18 ) . substr( $h, 0, 12 ) . $tag );
}
abp_out( '  (معیار «مخزن» = آرایهٔ javan_seda_expected_hashes در inc/ab-sidebars.php، بدون CR)' );
abp_ln();

/* ---------------------------------- بخش ۲: کدهای موبایل‌محور داخل فایل‌های قالب */
abp_out( '۲) شرط‌های موبایل در فایل‌های قالب روی هاست (آیا جایی محتوا با wp_is_mobile سانسور می‌شود؟)' );
$abp_scan = array( 'single.php', 'header.php', 'footer.php', 'myfunctions.php', 'functions.php', 'inc/widgets.php', 'inc/vip_slider.php' );
$abp_hits = array();
foreach ( $abp_scan as $rel ) {
	$p = trailingslashit( $theme_dir ) . $rel;
	if ( ! is_readable( $p ) ) {
		continue;
	}
	$src    = (string) file_get_contents( $p );
	$lines  = preg_split( '/\R/', $src );
	foreach ( $lines as $i => $line ) {
		if ( preg_match( '/wp_is_mobile|\bis_mobile\b|mobile_hide|hide_mobile|wp_is_mobile\(\)\s*\?|\bamp\b\s*&&/i', $line ) ) {
			$abp_hits[] = $rel . ':' . ( $i + 1 ) . '  ' . trim( preg_replace( '/\s+/', ' ', $line ) );
		}
	}
}
$abp_p = trailingslashit( $theme_dir ) . 'functions.php';
if ( is_readable( $abp_p ) ) {
	$head = (string) file_get_contents( $abp_p, false, null, 0, 400 );
	$enc  = ( false !== strpos( $head, 'ionCube' ) || false !== strpos( $head, 'encoded by' ) || false !== strpos( $head, '<?php //***' ) );
	abp_out( '  functions.php        : ' . abp_kb( @filesize( $abp_p ) ) . ' بایت — ' . ( $enc ? '⚠ رمزنگاری‌شده (ionCube) ⇒ محتوایش قابل خواندن نیست، ولی فیلترهایش در بخش ۳ دیده می‌شود' : 'متن باز' ) );
}
if ( $abp_hits ) {
	foreach ( array_slice( $abp_hits, 0, 20 ) as $h ) {
		abp_out( '  ⚠ ' . $h );
	}
	abp_out( '  ⇒ اگر در single.php یا footer.php شرط موبایل دیدید، همان‌جا محتوا سانسور می‌شود.' );
} else {
	abp_out(  '  ✓ هیچ شرطِ wp_is_mobile()/mobile_hide در این فایل‌ها نیست؛' );
	abp_out(   '    پس «کد single.php» به‌تنهایی علتِ غیب‌شدن محتوا در موبایل نیست (مگر فایل هاست ≠ مخزن باشد → بخش ۱).' );
}
abp_ln();

/* --------------------------------------- بخش ۳: زنجیرهٔ فیلترِ محتوا (روی همان ریکوئست) */
abp_out( '۳) چه کسی خروجیِ the_content را دستکاری می‌کند؟ (فیلترهای ثبت‌شدهٔ زنده)' );
global $wp_filter;
$abp_hooks = array( 'the_content', 'the_title', 'template_redirect', 'wp' );
foreach ( $abp_hooks as $hook ) {
	$regs = array();
	if ( isset( $wp_filter[ $hook ] ) ) {
		if ( is_object( $wp_filter[ $hook ] ) && method_exists( $wp_filter[ $hook ], 'get_callbacks' ) ) {
			$callbacks = $wp_filter[ $hook ]->get_callbacks( PHP_INT_MAX );
		} else {
			$callbacks = isset( $wp_filter[ $hook ] ) ? (array) $wp_filter[ $hook ] : array();
			$callbacks = isset( $callbacks['*'] ) ? $callbacks['*'] : $callbacks;
		}
		foreach ( (array) $callbacks as $prio => $set ) {
			foreach ( (array) $set as $fn ) {
				$name = '(بدون‌نام)';
				if ( isset( $fn['function'] ) ) {
					if ( is_string( $fn['function'] ) ) {
						$name = $fn['function'];
					} elseif ( is_array( $fn['function'] ) ) {
						$name = ( is_object( $fn['function'][0] ) ? get_class( $fn['function'][0] ) : (string) $fn['function'][0] ) . '::' . $fn['function'][1];
					} else {
						$name = '{closure}';
					}
				}
				$regs[] = $prio . ' → ' . $name;
			}
		}
	}
	abp_out( '  ' . abp_pad( $hook, 18 ) . ': ' . ( $regs ? implode( '  |  ', array_slice( $regs, 0, 14 ) ) . ( count( $regs ) > 14 ? ' …(' . count( $regs ) . ' عدد)' : '' ) : '(هیچ)' ) );
	if ( 'the_content' === $hook ) {
		$flat = "\n" . implode( "\n", $regs );
		if ( false !== strpos( $flat, 'javan_seda_content_rescue' ) ) {
			abp_out( '    ✓ «نجات محتوا» (v3.0.5) روی هاست فعال است.' );
		} else {
			abp_out( '    ✓ تابع javan_seda_content_rescue ثبت نشده (در v3.1.0 عمدًا حذف شد؛ این فیلتر خودش منبعِ خالی‌ماندنِ بلوک بود).' );
		}
		if ( preg_match_all( '/→ *([a-z0-9_]*(mobile|amp|hide|down|dl_|protect|cut|strip)[a-z0-9_]*)/i', $flat, $mm ) && $mm[1] ) {
			abp_out( '    ⚠ فیلترهای مشکوکِ موبایل/مخفی‌ساز: ' . implode( ', ', array_unique( $mm[1] ) ) );
		}
	}
}
abp_out( '  پرچم نجات در این ریکوئست : ' . ( empty( $GLOBALS['javan_seda_content_rescued'] ) ? '(فعال نشد)' : 'فعال: ' . wp_json_encode( $GLOBALS['javan_seda_content_rescued'] ) ) );
abp_ln();

/* --------------------------------------------- بخش ۴: نوشته‌ای که کاوش می‌شود */
$url = home_url( '/' );
if ( '' !== $abp_u ) {
	$url = 0 === strpos( $abp_u, 'http' ) ? $abp_u : home_url( '/' . ltrim( $abp_u, '/' ) );
} else {
	$last = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish', 'post_type' => 'post' ) );
	if ( $last ) {
		$url = get_permalink( $last[0]->ID );
	}
}
abp_out( '۴) صفحهٔ مورد کاوش: ' . $url );

$pid = 0;
if ( function_exists( 'url_to_postid' ) ) {
	$pid = (int) url_to_postid( $url );
}
if ( $pid ) {
	$pp = get_post( $pid );
	abp_out( '  post id=' . $pid . ' | نوع: ' . $pp->post_type . ' | وضعیت: ' . $pp->post_status );
	abp_out( '  طول post_content در دیتابیس: ' . mb_strlen( (string) $pp->post_content, 'UTF-8' ) . ' نویسه | ' . str_word_count( wp_strip_all_tags( (string) $pp->post_content ) ) . ' کلمه' );
	abp_out( '  شروع post_content: ' . mb_substr( preg_replace( '/\s+/u', ' ', strip_tags( (string) $pp->post_content ) ), 0, 160, 'UTF-8' ) );
	if ( function_exists( 'get_post_meta' ) ) {
		$am = get_post_meta( $pid, 'album', true );
		if ( is_array( $am ) ) {
			abp_out( '  متای album: ' . count( $am ) . ' ردیف دانلود (پلیر/لینک‌ها از همین‌جا ساخته می‌شوند)' );
		}
	}
} else {
	abp_out( '  ⚠ این URL به هیچ نوشته‌ای نگاشت نشد — اگر صفحهٔ نوشته می‌خواهید، با ?u=/slug-of-post/ بدهید.' );
}
abp_ln();

/* ---------------------------------------------------- بخش ۵: رندر با سه UA */
/* --- ۴.۱) منبع «اخبار روز» (v3.1.3: فقط دستهٔ news، در حالت سخت‌گیر «هیچ») --- */
abp_ln();
abp_out( '۴.۱) نوار «اخبار روز» — منبعِ داده (home.php زیر هدر / شورت‌کد [ab_news])' );
$ncat  = 0;
$slugs = array( 'news' );
if ( function_exists( 'javan_seda_news_category_slugs' ) ) {
	$slugs = (array) javan_seda_news_category_slugs();
}
if ( function_exists( 'javan_seda_news_category_id' ) ) {
	$ncat = (int) javan_seda_news_category_id();
} else {
	foreach ( $slugs as $sl ) {
		$t = get_term_by( 'slug', $sl, 'category' );
		if ( $t && ! is_wp_error( $t ) ) {
			$ncat = (int) $t->term_id;
			break;
		}
	}
}
$strict = ! ( defined( 'JAVANSEDA_NEWS_STRICT' ) && false === JAVANSEDA_NEWS_STRICT );
abp_out( '  فایل لایه موجود است؟     : ' . ( function_exists( 'javan_seda_news_ticker' ) ? '✓ بله (inc/ab-news-ticker.php بارگذاری شده)' : '✗ نه ⇒ home.php نوار قدیمی (دسترسی سریع) را چاپ می‌کند — drop-in اعمال نشده' ) );
abp_out( '  slugهای مجاز            : ' . implode( ', ', $slugs ) . ( defined( 'JAVANSEDA_NEWS_CATEGORY' ) ? '   (JAVANSEDA_NEWS_CATEGORY=' . JAVANSEDA_NEWS_CATEGORY . ')' : '' ) );
$cat_name = '?';
$cat_link = '';
if ( $ncat ) {
	$ti = get_term( $ncat, 'category' );
	if ( $ti && ! is_wp_error( $ti ) ) {
		$cat_name = $ti->name . ' (' . (int) $ti->count . ' نوشته)';
		$tl       = get_term_link( $ti, 'category' );
		$cat_link = ( ! is_wp_error( $tl ) ) ? (string) $tl : '';
	}
	abp_out( '  دستهٔ یافت‌شده            : id=' . $ncat . ' → ' . $cat_name );
	abp_out( '  لینک آرشیو دسته         : ' . ( $cat_link ? $cat_link : '✗ ساخته نشد' ) );
	if ( $cat_link ) {
		$hc = array( 'timeout' => 4, 'sslverify' => false, 'user-agent' => 'ahangban-probe' );
		$rp = function_exists( 'wp_remote_get' ) ? wp_remote_get( $cat_link, $hc ) : null;
		if ( is_array( $rp ) && ! is_wp_error( $rp ) ) {
			$code = (int) wp_remote_retrieve_response_code( $rp );
			abp_out( '    وضعیت HTTP آرشیو      : ' . $code . ( 200 === $code ? ' ✓' : ' ✗ اگر 404 است، یعنی لینک دستهٔ news روی این سایت کار نمی‌کند — یا slug را بسازید یا JAVANSEDA_NEWS_CATEGORY را روی id/slug درست تنظیم کنید' ) );
		} else {
			abp_out( '    وضعیت HTTP آرشیو      : (.req ناموفق — روی بعضی هاست‌ها درخواست بیرونی بسته است؛ دستی در مرورگر باز کنید)' );
		}
	}
} else {
	abp_out( '  دستهٔ یافت‌شده            : ✗ هیچ‌کدام از slugهای بالا در «نوشته‌ها › دسته‌ها» وجود ندارد' );
}
if ( function_exists( 'javan_seda_news_posts' ) ) {
	$nn = javan_seda_news_posts( 3 );
	abp_out( '  سه خبرِ اول              : ' . ( ! empty( $nn['items'] ) ? implode( ' | ', array_map( function ( $x ) { return $x['date'] . ' ' . $x['title']; }, $nn['items'] ) ) : '(خالی)' ) );
	$how = 'دستهٔ انتخابی';
	if ( ! empty( $nn['fallback'] ) ) {
		$how = ( 1 == $nn['fallback'] ) ? '⚠ fallback: دسته نبود → آخرین نوشته‌ها' : '⚠ fallback: دسته خالی بود → آخرین نوشته‌ها';
	}
	abp_out( '  منبعِ چاپ‌شده             : ' . $how );
}
abp_out( '  حالت                     : ' . ( $strict ? 'سخت‌گیر (اگر دستهٔ اخبار نباشد → نوار خالی/بدون چاپ) ✓ پیش‌فرض v3.1.3' : 'مجاز ⇒ در نبودِ دسته، «آخرین نوشته‌ها» می‌آید (JAVANSEDA_NEWS_STRICT=false)' ) );
abp_out( '  تنظیم‌ها                   : JAVANSEDA_NEWS_COUNT=' . ( defined( 'JAVANSEDA_NEWS_COUNT' ) ? (int) JAVANSEDA_NEWS_COUNT : '8' ) . ' | JAVANSEDA_NEWS_CACHE=' . ( defined( 'JAVANSEDA_NEWS_CACHE' ) ? (int) JAVANSEDA_NEWS_CACHE : '60' ) . ' ثانیه' );
abp_out( '  نکته                     : تیکر هیچ خبری «تولید» نمی‌کند؛ فقط نوشته‌های همان دسته را می‌خواند.' );
abp_out( '     در View Sourceِ همان صفحه، کامنت <!-- javan_seda: news ticker | cat=… | N خبر | strict=on/off --> را ببینید:' );
abp_out( '     اگر cat=NONE بود ⇒ دسته‌ای با آن slugها وجود ندارد (در حالت سخت‌گیر نوار چاپ نمی‌شود).' );

abp_ln();
abp_out( '۴.۵) سلامتِ فایل‌ها از نظر «خطرِ خطای کشنده» (علتِ احتمالیِ باکسِ خالی)' );
$abp_single = trailingslashit( $theme_dir ) . 'single.php';
$abp_src    = is_readable( $abp_single ) ? (string) file_get_contents( $abp_single ) : '';
if ( '' === $abp_src ) {
	abp_out( '  ✗ single.php خوانده نشد.');
} else {
	$abp_all = preg_match_all( '/\b(?:get_field|the_field|get_sub_field|the_sub_field|have_rows)\s*\(/', $abp_src );
	$abp_gd  = preg_match_all( '/function_exists\s*\(\s*.[a-z_]+./', $abp_src );
	$abp_ung = 0;
	foreach ( preg_split( '/\R/', $abp_src ) as $abp_l ) {
		if ( preg_match( '/\b(?:get_field|the_field|get_sub_field|the_sub_field|have_rows)\s*\(/', $abp_l ) && false === strpos( $abp_l, 'function_exists' ) ) {
			$abp_ung++;
		}
	}
	$abp_ung = max( $abp_ung, 0 );
	abp_out( '  ACF روی سایت ' . ( class_exists( 'ACF' ) || defined( 'ACF_VERSION' ) ? '✓ نصب است' : '✗ نصب نیست/غیرفعال است' ) . ' | خطوطِ بی‌محافظِ get_field/the_field در single.php: ' . $abp_ung . ' از ' . $abp_all . ' فراخوانی (محافظ‌شده با function_exists: ' . $abp_gd . ' خط)' );
	if ( ! ( class_exists( 'ACF' ) || defined( 'ACF_VERSION' ) ) && $abp_ung > 0 ) {
		abp_out( '    ⚠ خطر جدی: با غیرفعال‌بودن ACF، first unguarded call صفحه را وسط <div class="single_content_box"> می‌کشد' );
		abp_out( '      ⇒ نتیجه دقیقاً همان چیزی است که می‌بینید: باکس هست، محتوا نیست (و اگر کشِ موبایل جدا باشد، فقط در گوشی).' );
		abp_out( '      راه‌حل: فعال‌کردن افزونهٔ ACF 5  یا  افزودن لایهٔ سازگاری inc/acf-compat.php (در بستهٔ 3.1.0 هست).' );
	}
	$abp_plug = preg_match_all( '/\bis_plugin_active\s*\(/', $abp_src );
	abp_out( '  فراخوانی is_plugin_active() در single.php: ' . $abp_plug . ' مورد ' . ( $abp_plug && ! function_exists( 'is_plugin_active' ) ? '(⚠ این تابع فقط با include فایل wp-admin/includes/plugin.php وجود دارد)' : '' ) );
	if ( $abp_plug ) {
		abp_out( '    اگر نسخهٔ فایل روی هاست include_once آن خط را نداشته باشد ⇒ خطای کشنده در همون خط ۲۱، درست قبل از باکس محتوا.' );
	}
	$abp_lines_arr = preg_split( '/\R/', $abp_src );
	$abp_hits = array();
	foreach ( $abp_lines_arr as $ii => $ll ) {
		if ( preg_match( '/\b(?:get_field|the_field|is_plugin_active|have_rows)\s*\(/', $ll ) && false === strpos( $ll, 'function_exists' ) ) {
			$abp_hits[] = ( $ii + 1 );
		}
	}
	abp_out( '  شمارهٔ خطوطِ بی‌محافظ: ' . ( $abp_hits ? implode( ', ', array_slice( $abp_hits, 0, 18 ) ) . ( count( $abp_hits ) > 18 ? ' …' : '' ) : '—' ) );
}
$abp_side = trailingslashit( $theme_dir ) . 'inc/ab-sidebars.php';
if ( ! file_exists( $abp_side ) ) {
	abp_out( '  ○ inc/ab-sidebars.php روی هاست نیست ⇒ لایهٔ ایمنی غیرفعال است؛ قالب (single.php) به dynamic_sidebarِ اصلی برمی‌گردد و این «سالم» است.' );
} else {
	$abp_sc = (string) file_get_contents( $abp_side );
	$bad = array();
	foreach ( array(
		'the_content'      => "add_filter( 'the_content'",
		'template_include' => "add_filter( 'template_include'",
		'the_title'        => "add_filter( 'the_title'",
		'include دستی'     => 'require_once $file',
	) as $blurb => $needle ) {
		if ( false !== strpos( $abp_sc, $needle ) ) {
			$bad[] = $blurb;
		}
	}
	$lay = ( false !== strpos( $abp_sc, '3.1.0' ) ) ? '3.1.0 (کمینه — بدون دخالت در رندر)' : '⚠ نسخهٔ قدیمی (۳.۰.x) که در خطِ رندر دخالت می‌کند';
	abp_out( '  inc/ab-sidebars.php: ' . abp_kb( (int) filesize( $abp_side ) ) . ' بایت | ' . $lay );
	if ( $bad ) {
		abp_out( '    ⚠ هلکرهای پرخطر در فایل: ' . implode( ' · ', $bad ) . '  ⇒ همین فایل را با نسخهٔ 3.1.0 جایگزین کنید' );
	} else {
		abp_out( '    ✓ هیچ هلکرِ پرخطری (the_content / template_include / include دستی) ندارد' );
	}
}
$mh = abp_module_hooks();
if ( $mh ) {
	abp_out( '  ⚠ لایهٔ ایمنی، هوکِ حساس روی خطِ رندر ثبت کرده: ' . implode( '  |  ', $mh ) );
	abp_out( '    ⇒ همان چیزی که در نسخهٔ ۳.۰.x «باکس خالی» می‌ساخت. فایل inc/ab-sidebars.php را با نسخهٔ 3.1.0 عوض کنید.' );
} else {
	abp_out( '  ✓ لایهٔ ایمنی هیچ هوکی روی the_content / template_include / the_title ندارد (رفتارِ v3.1.0 = کمینه).' );
}
abp_out( '  فهرست کامل فیلترهای the_content در بخش ۳ می‌آید (فقط روی همان ریکوئستِ کاوش).' );

abp_out( '۵) رندر همان URL روی همین سرور با سه کاربرگزار' );
$abp_uas = array(
	'موبایل (آیفون)' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
	'دسکتاپ (کروم)'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
	'ربات (گوگل)'    => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
);
$res = array();
foreach ( $abp_uas as $label => $ua ) {
	$r = abp_fetch( $url, $ua );
	$res[ $label ] = $r;
	if ( isset( $r['error'] ) ) {
		abp_out( '  ' . abp_pad( $label, 18 ) . ' ✗ ' . $r['error'] );
		continue;
	}
	abp_out( '  ' . abp_pad( $label, 18 ) . ' HTTP ' . $r['code'] . ' | ' . abp_kb( $r['bytes'] ) . ' | ' . strlen( $r['html'] ) . ' بایت' );
	abp_out( '      هدرهای کش/CDN: ' . implode( ' · ', $r['cache'] ) );
}
if ( count( array_filter( $res, 'abp_err_filter' ) ) === count( $res ) ) {
	abp_out( '' );
	abp_out( '  ✗ هر سه درخواست داخلی ناموفق بود ⇒ سرور اجازهٔ loopback نمی‌دهد.' );
	abp_out( '    در این حالت فقط همین گزارش را بفرستید (بخش ۱ و ۳ و ۶ همچنان ارزشمندند).' );
}

function abp_err_filter( $v ) {
	return ! empty( $v['error'] );
}

$mob = isset( $res['موبایل (آیفون)']['html'] ) ? $res['موبایل (آیفون)']['html'] : '';
$des = isset( $res['دسکتاپ (کروم)']['html'] ) ? $res['دسکتاپ (کروم)']['html'] : '';

/* ------------------------------------------- بخش ۶: نشانه‌ها و بلوک محتوا در هر رندر */
abp_ln();
abp_out( '۶) بلوک‌ها و نشانه‌ها در HTMLِ هر رندر' );
foreach ( $res as $label => $r ) {
	abp_out( '' );
	if ( isset( $r['error'] ) ) {
		abp_out( '  ===== ' . $label . ' : ناموفق =====' );
		continue;
	}
	abp_out( '  ===== ' . $label . ' =====' );
	$h = $r['html'];
	foreach ( abp_marks( $h ) as $pretty => $n ) {
		abp_out( '    ' . abp_pad( $pretty, 26 ) . ' ' . ( $n > 0 ? $n : '✗ ۰ (نیست!)' ) );
	}
	if ( preg_match( '/<body[^>]*class="([^"]*)"/i', $h, $mm ) ) {
		abp_out( '    class بدنه              : ' . mb_substr( preg_replace( '/\s+/', ' ', $mm[1] ), 0, 200, 'UTF-8' ) );
	} else {
		abp_out( '    class بدنه              : ✗ تگ <body> با class پیدا نشد (رندر ناقص؟)' );
	}
	foreach ( array( 'single_content_box', 'single_posts_right' ) as $cls ) {
		$reg = abp_region( $h, $cls );
		if ( null === $reg ) {
			abp_out( '    بلوک .' . $cls . '        : ✗ در HTML وجود ندارد' );
		} elseif ( $reg['size'] < 0 ) {
			abp_out( '    بلوک .' . $cls . '        : ⚠ ' . $reg['head'] );
		} else {
			abp_out( '    بلوک .' . $cls . '        : ' . abp_kb( $reg['size'] ) . ' | ' . $reg['tags'] . ' تگ | ' . $reg['text'] . ' نویسهٔ متن' );
			abp_out( '        سرآغاز: ' . mb_substr( $reg['head'], 0, 180, 'UTF-8' ) );
			if ( 'single_content_box' === $cls ) {
				abp_out( '        پایان : ' . mb_substr( $reg['tail'], 0, 160, 'UTF-8' ) );
			}
		}
	}
	$op  = preg_match_all( '/<div\b/i', $h );
	$cl  = substr_count( $h, '</div>' );
	$he  = preg_match_all( '/<header\b/i', $h ) . '/' . substr_count( $h, '</header>' );
	$ma  = preg_match_all( '/<main\b/i', $h ) . '/' . substr_count( $h, '</main>' );
	$ar  = preg_match_all( '/<article\b/i', $h ) . '/' . substr_count( $h, '</article>' );
	abp_out( '    تقارن تگ‌ها             : div ' . $op . '/' . $cl . ' (Δ ' . ( $op - $cl ) . ') | header ' . $he . ' | main ' . $ma . ' | article ' . $ar );
	abp_out( '    انتها (۱۴۰ نویسهٔ آخر)  : ' . preg_replace( '/\s+/', ' ', mb_substr( $h, -140, 140, 'UTF-8' ) ) );
	abp_out( '    پایان نرمال؟            : ' . ( false !== strpos( $h, '</html>' ) ? '✓ بله، </html> هست' : '✗ نه! صفحه وسط کار قطع شده ⇒ خطای کشندهٔ PHP' ) );
	foreach ( abp_fatal_sense( $h ) as $f ) {
		abp_out( '    ' . $f );
	}
}

/** فهرست هوکهایی که لایهٔ ایمنیِ قالب روی آن‌ها نشسته است (تشخیص نسخهٔ پرخطر). */
function abp_module_hooks() {
	global $wp_filter;
	$found = array();
	$risky = array( 'the_content', 'template_include', 'the_excerpt', 'the_title', 'template_redirect' );
	foreach ( $risky as $hook ) {
		if ( empty( $wp_filter[ $hook ] ) ) {
			continue;
		}
		$h = $wp_filter[ $hook ];
		$cb = ( is_object( $h ) && method_exists( $h, 'get_callbacks' ) ) ? $h->get_callbacks( PHP_INT_MAX ) : (array) $h;
		if ( isset( $cb['*'] ) ) {
			$cb = $cb['*'];
		}
		foreach ( (array) $cb as $prio => $set ) {
			foreach ( (array) $set as $f ) {
				if ( isset( $f['function'] ) && is_string( $f['function'] ) && 0 === strpos( $f['function'], 'javan_seda_' ) ) {
					$found[] = $hook . '@' . $prio . ' → ' . $f['function'];
				}
			}
		}
	}
	return $found;
}

/* ------------------------------------------------- بخش ۷: مقایسهٔ بایت‌به‌بایت */
$diff = false;
if ( $mob && $des ) {
	abp_ln();
	abp_out( '۷) مقایسهٔ بایت‌به‌بایتِ موبایل با دسکتاپ' );
	abp_out( '  حجم: دسکتاپ ' . abp_kb( strlen( $des ) ) . ' ↔ موبایل ' . abp_kb( strlen( $mob ) ) . ' (اختلاف ' . abp_kb( abs( strlen( $des ) - strlen( $mob ) ) ) . ')' );
	$d = abp_first_diff( $des, $mob );
	if ( false === $d ) {
		abp_out( '  ✓ HTML دو رندر «کاملاً یکسان» است ⇒ سرور با موبایل کاری ندارد؛ علت ۱۰۰٪ سمت CSS/کشِ مرورگر/گوشی است.' );
	} else {
		abp_out( '  اولین اختلاف در بایت ' . $d['pos'] . ' :' );
		abp_out( '    دسکتاپ: …' . mb_substr( $d['ctx_a'], 0, 200, 'UTF-8' ) . '…' );
		abp_out( '    موبایل: …' . mb_substr( $d['ctx_b'], 0, 200, 'UTF-8' ) . '…' );
	}
	$regd = abp_region( $des, 'single_content_box' );
	$regm = abp_region( $mob, 'single_content_box' );
	$s_d = is_array( $regd ) && $regd['size'] > 0 ? $regd['size'] : 0;
	$s_m = is_array( $regm ) && $regm['size'] > 0 ? $regm['size'] : 0;
	abp_out( '  بلوک single_content_box: دسکتاپ ' . $s_d . ' بایت ↔ موبایل ' . $s_m . ' بایت' . ( $s_d && $s_m && abs( $s_d - $s_m ) < 200 ? '  ≈ هم‌حجم' : '' ) );
}

/* ------------------------------------------------------- بخش ۸: نتیجه‌گیری */
$saved_diff = ( $mob && $des ) ? abp_first_diff( $des, $mob ) : false;
$same       = ( $mob && $des && ! $saved_diff );

/* آیا فایل‌های هاست با بستهٔ مخزن یکی‌اند؟ */
$fresh = true;
$ncopy = 0;
if ( $abp_expect ) {
	foreach ( array( 'style.css', 'header.php', 'single.php', 'inc/ab-sidebars.php' ) as $rel ) {
		if ( ! isset( $abp_expect[ $rel ] ) ) {
			continue;
		}
		$ncopy++;
		$pf = trailingslashit( $theme_dir ) . $rel;
		if ( ! is_readable( $pf ) ) {
			$fresh = false;
			break;
		}
		$hh = hash_file( 'sha256', str_replace( "\r\n", "\n", file_get_contents( $pf ) ) );
		if ( $abp_expect[ $rel ] !== $hh ) {
			$fresh = false;
		}
	}
}

$hit  = false;
if ( isset( $res['موبایل (آیفون)']['cache'] ) ) {
	$hit = (bool) preg_match( '/x-cache[^\n]{0,40}HIT|cf-cache-status[^\n]{0,20}HIT|age:\s*[1-9]/i', implode( ' ', (array) $res['موبایل (آیفون)']['cache'] ) );
}
$resc = ! empty( $GLOBALS['javan_seda_content_rescued'] );
$dlen = isset( $res['دسکتاپ (کروم)']['html'] ) ? strlen( (string) $res['دسکتاپ (کروم)']['html'] ) : 0;
$stop = ( $dlen >= 3000 && strlen( (string) $mob ) < (int) ( 0.6 * $dlen ) );
$css_inline = ( substr_count( (string) $mob, 'ab-header-critical' ) > 0 );

abp_ln();
abp_out( '۸) نتیجه‌گیری خودکار' );
abp_out( '  [الف] فایل‌های هاست با بستهٔ مخزن: ' . ( $ncopy ? ( $fresh ? '✓ ' . $ncopy . ' فایل هم‌سان است (اعمال شده)' : '✗ دست‌کم یکی از فایل‌ها کهنه است؛ اول همان‌ها را جایگزین کن' ) : '○ مرجع هش ندارد' ) );
abp_out( '  [ب] هدرِ کش در رندر موبایل     : ' . ( $hit ? '⚠ نشانهٔ کشِ کهنه (HIT یا age) ⇒ کش استاتیک و CDN را پاک کن' : '✓ در پاسخِ داخلی نشانه‌ای از کشِ کهنه نیست' ) );
abp_out( '  [پ] single_content_box         : دسکتاپ ' . ( $content_in_des ? 'هست' : 'نیست' ) . ' ↔ موبایل ' . ( $content_in_mob ? 'هست' : 'نیست' ) . ( $content_in_mob ? '  ⇒ در گوشی دیده نمی‌شود پس مسئله CSS/کش است، نه PHP' : '' ) );
abp_out( '  [ت] کامل‌بودن HTML              : دسکتاپ ' . ( $full_des ? '✓' : '✗ ناقص' ) . ' | موبایل ' . ( $full_mob ? '✓' : '✗ ناقص' ) . ( $stop ? ' | ⚠ حجم موبایل خیلی کمتر از دسکتاپ' : '' ) );
$abp_gran = array();
foreach ( array( 'single_featured_cover', 'music_lyrics_box', 'dl_links_box', 'dl_linksv_box', 'music_box_player' ) as $gk ) {
	$abp_gran[] = $gk . ':' . substr_count( (string) $des, $gk ) . '/' . substr_count( (string) $mob, $gk );
}
abp_out( '  [ج] اجزای بلوک (دسکتاپ/موبایل): ' . implode( '  ', $abp_gran ) );
abp_out( '  [ث] CSS ضدّکشِ هدر در موبایل   : ' . ( $css_inline ? '✓ موجود ⇒ header.phpِ جدید روی هاست است' : '✗ نبود ⇒ header.php هنوز نسخهٔ قدیمی است' ) );
abp_ln();
if ( ! $mob && ! $des ) {
	abp_out( '  نتیجه: رندر داخلی روی این هاست ممکن نشد (loopback مسدود).' );
	abp_out( '         تصمیم را از روی [الف] و بخش ۳ بگیر: اگر [الف] ✗ است، اول فایل‌ها را جایگزین کن.' );
} elseif ( ! $content_in_des ) {
	abp_out( '  نتیجه: در هیچ‌کدام از دو رندر بلوک محتوا نیست ⇒ این URL صفحهٔ نوشته نیست (کش/بازتهدی/۴۰۴).' );
	abp_out( '         با ?u=/نامک-نوشته/ دوباره اجرا کن.' );
} elseif ( $content_in_des && ! $content_in_mob ) {
	abp_out( '  ⇒ علت سمت PHP است: محتوا در HTMLِ موبایل تولید نمی‌شود.' );
	if ( ! $full_mob ) {
		abp_out( '     الف) HTML موبایل ناتمام است ⇒ خطای کشنده در همان نقطه؛ پیام خطا و «انتها»ی بخش ۶ را بفرست.' );
	}
	abp_out( '     ب) ' . ( $resc ? 'یک فیلترِ «نجات محتوا» روی the_content فعال است (نسخهٔ ۳.۰.۵) ⇒ همین را حذف/به‌روز کن؛ v3.1.0 هیچ فیلتری روی the_content ندارد.' : 'فیلترِ نجاتِ محتوا ثبت نیست که یعنی رفتارِ v3.1.0 درست است؛ اگر پلاگین/فیلترِ موبایل‌محور the_content را خالی می‌کند، پلاگین‌ها را یکی‌یکی غیرفعال کن و بخش ۳ را مقایسه کن.' ) );
} elseif ( ! $full_mob || ! $full_des ) {
	abp_out( '  ⇒ رندر در یکی از حالت‌ها قطع شده ⇒ خطای PHP؛ دو خطِ آخرِ بخش ۶ (پیام خطا و «انتها») را بفرست.' );
} elseif ( $same ) {
	abp_out( '  ⇒ HTMLِ دو حالت کاملاً یکسان است ⇒ سرور با موبایل کاری ندارد؛ علت در CSS/کشِ سمتِ گوشی است:' );
	abp_out( '     ۱) اگر [الف] ✗ است: فایل‌های drop-in را اعمال کن (به‌ویژه header.php و style.css).' );
	abp_out( '     ۲) کش افزونهٔ کش + CDN را پاک کن و در گوشی صفحه را با ?ab_nocache=1 تازه کن.' );
	abp_out( '     ۳) اگر [ث] ✓ ولی [ب] ⚠ بود، یعنی فایل درست است و کش قدیمی سرو می‌شود.' );
} else {
	abp_out( '  ⇒ بلوک محتوا در موبایل تولید می‌شود ولی HTML با دسکتاپ فرق دارد ⇒ مسئلهٔ «نمایش» است نه «رندر»:' );
	abp_out( '     «اولین اختلاف» در بخش ۷ را ببین؛ اگر همان‌جا بلوکی کم/زیاد شده، پلاگینِ موبایل‌ساز متهم است.' );
}
if ( $mob && $content_in_mob ) {
	abp_out( '  • هدر موبایل در رندرِ موبایل: ' . ( $has_mob_header ? '✓ رندر شده' : '✗ رندر نشده' ) . ' | کلاس ab-has-mh: ' . ( $body_mobile ? '✓' : '✗' ) . ( ( $has_mob_header && $body_mobile ) ? ' ⇒ هدرِ موبایل اعمال شده؛ اگر با این حال صفحه خالی است، بلوک محتوا با CSS پنهان شده' : '' ) );
}

/* ---------------- ۸.۵) نکتهٔ پایانی: آزمودنِ فرضِ CSS در موبایل (بدون FTP) */
abp_ln();
abp_out( '۸.۵) اگر نتیجه شد «سمت CSS»، این دو قانون را موقتاً در' );
abp_out( '     «مظهر → سفارشی‌سازی → CSS اضافی» بگذار تا معلوم شود چه چیزی بلوک را می‌بُرد:' );
abp_out( '       .tarlanweb_center{ width:auto !important; max-width:100% !important; }' );
abp_out( '       #centers_box,.single_content_box{ min-height:1px !important; overflow:visible !important; }' );
abp_out( '     اگر محتوا ظاهر شد ⇒ علت، عرضِ ثابت ۲۵۰ تا ۳۵۰ پیکسلی قالب در موبایل است' );
abp_out( '     (قاعده‌های @media در style.css که فقط ۹۰٪ عرض را می‌دهد و body با overflow-x:hidden)' );
abp_out( '     و باید style.css نسخهٔ جدید روی هاست اعمال شود؛ همین قاعده‌ها داخلش هست.' );
/* ------------------------------------------------ ۱۰) بایتِ سرعت (لایۀ perf) */
abp_ln();
abp_out( '۱۰) بایتِ سرعت — لایۀ inc/ab-perf.php (v3.1.3)' );
if ( function_exists( 'javan_seda_perf_report' ) ) {
	$abp_perf = (array) javan_seda_perf_report();
	foreach ( $abp_perf as $abp_pl ) {
		abp_out( '  ' . $abp_pl );
	}
} else {
	abp_out( '  ✗ تابع javan_seda_perf_report موجود نیست ⇒ فایل inc/ab-perf.php روی هاست نیست' );
	abp_out( '    یا require آن از myfunctions.php حذف شده. (افت سرعت را با PageSpeed بسنجید.)' );
}
/* ------------------------------------------------- ذخیرهٔ اختیاری HTMLها */
if ( ! empty( $_GET['save'] ) ) {
	$dir = ( function_exists( 'get_temp_dir' ) ? rtrim( get_temp_dir(), '/\\' ) : sys_get_temp_dir() ) . '/ahangban_probe';
	if ( ! is_dir( $dir ) ) {
		@mkdir( $dir, 0755, true );
	}
	$saved = array();
	foreach ( $res as $label => $r ) {
		if ( empty( $r['html'] ) ) {
			continue;
		}
		$f = $dir . '/ahangban-probe-' . date( 'Ymd-His' ) . '-' . ( false !== mb_strpos( $label, 'موبایل' ) ? 'mobile' : ( false !== mb_strpos( $label, 'دسکتاپ' ) ? 'desktop' : 'bot' ) ) . '.html';
		if ( @file_put_contents( $f, $r['html'] ) ) {
			$saved[] = $f;
		}
	}
	abp_ln();
	abp_out( '۹) فایل‌های ذخیره‌شده (برای فرستادن برای پشتیبانی / ضمیمه‌کردن):' );
	foreach ( $saved as $f ) {
		abp_out( '   ' . $f . '  (' . abp_kb( @filesize( $f ) ) . ')' );
	}
	abp_out( '   دانلود آخرین فایل: ' . home_url( '/ab-mobile-probe.php?dl=1' ) . '  |  پاک‌کردن همه: ?rm=1' );
	$old = (array) glob( $dir . '/ahangban-probe-*.html' );
	if ( count( $old ) > $GLOBALS['ABP_KEEP'] ) {
		$rmove = array_slice( $old, 0, count( $old ) - $GLOBALS['ABP_KEEP'] );
		foreach ( $rmove as $f ) {
			if ( preg_match( '/ahangban-probe-[0-9]{8}-[0-9]{6}-[a-z]+\.html$/', $f ) ) {
				@unlink( $f );
			}
		}
	}
}

abp_ln();
abp_out( 'راهنمای ارسال برای پشتیبانی: همین صفحه را در مرورگرِ گوشی باز کنید و از بخش‌های ۱، ۶، ۷ و ۸ اسکرین‌شات بگیرید.' );
abp_out( 'برای دیدن در حالت خوش‌خوان: آدرس را با ?html=1 تمام کنید. بعد از اتمام، فایل ab-mobile-probe.php را از هاست پاک کنید.');

if ( $abp_html_mode ) {
	echo implode( "\n", $abp_lines );
	echo '</pre></body></html>';
} else {
	echo implode( "\n", $abp_lines );
}
