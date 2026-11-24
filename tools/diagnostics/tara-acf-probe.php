<?php
/**
 * ترانه‌یار — چرا ACF Pro فعال نمی‌شود؟ (v3.17.13)
 *
 *   ?key=taranehyar
 *
 * این فایل بدونِ لود کردنِ قالب اجرا می‌شود و می‌گوید:
 *   • ACF نصب هست یا نه، و کدام نسخه
 *   • چه چیزی از قالب با ACF تضاد دارد (نام توابع/کلاس‌ها/هوک‌ها)
 *   • فیلترهای acf/* که قالب یا افزونه‌ها ثبت کرده‌اند
 *   • وضعیت acf-json و فیلدهای ذخیره‌شده
 *   • خطاهای اخیرِ PHP مربوط به ACF
 *
 * با مرورگر باز کنید. بعد از تشخیص پاکش کنید.
 */

$SECRET = 'taranehyar';
if ( ! isset( $_GET['key'] ) || $SECRET !== (string) $_GET['key'] ) {
	header( 'HTTP/1.1 404 Not Found' );
	echo 'Not found.';
	exit;
}

@ini_set( 'display_errors', '1' );
@ini_set( 'error_reporting', E_ALL );
@ini_set( 'max_execution_time', '300' );
@set_time_limit( 300 );
while ( ob_get_level() > 0 ) {
	@ob_end_flush();
}
header( 'Content-Type: text/plain; charset=utf-8' );
header( 'X-Robots-Tag: noindex, nofollow' );
header( 'Cache-Control: no-store' );
header( 'X-Accel-Buffering: no' );
ob_implicit_flush( true );

$T0 = microtime( true );
function s( $m ) {
	global $T0;
	echo '[' . str_pad( round( ( microtime( true ) - $T0 ) * 1000 ), 7, ' ', STR_PAD_LEFT ) . 'ms] ' . $m . "\n";
	@flush();
}

s( '=== کاوشگرِ ACF Pro — v3.17.13 ===' );
s( 'PHP ' . PHP_VERSION . ' | ' . PHP_SAPI );
s( '' );

$root = dirname( __FILE__ );
require_once $root . '/wp-load.php';

global $wpdb;

/* ---------- ۱) وضعیت ACF ---------- */
s( '--- ۱) وضعیت ACF ---' );
s( 'ACF_VERSION تعریف شده؟ ' . ( defined( 'ACF_VERSION' ) ? 'بله → ' . ACF_VERSION : 'خیر' ) );
s( 'کلاس ACF وجود دارد؟ ' . ( class_exists( 'ACF' ) ? 'بله' : 'خیر' ) );
s( 'تابع get_field وجود دارد؟ ' . ( function_exists( 'get_field' ) ? 'بله' : 'خیر' ) );
s( 'تابع acf_get_field وجود دارد؟ ' . ( function_exists( 'acf_get_field' ) ? 'بله' : 'خیر' ) );
s( '' );

/* ---------- ۲) افزونه‌های ACF روی دیسک ---------- */
s( '--- ۲) پوشه‌های ACF روی دیسک ---' );
$plugdir = WP_PLUGIN_DIR;
$found   = array();
foreach ( (array) @scandir( $plugdir ) as $d ) {
	if ( '.' === $d || '..' === $d ) {
		continue;
	}
	if ( false !== stripos( $d, 'acf' ) || false !== stripos( $d, 'advanced-custom' ) ) {
		$main = $plugdir . '/' . $d . '/' . $d . '.php';
		$hdr  = '';
		if ( is_readable( $main ) ) {
			$src = (string) @file_get_contents( $main, false, null, 0, 4096 );
			if ( preg_match( '/Version:\s*([0-9.]+)/i', $src, $m ) ) {
				$hdr = ' | Version: ' . $m[1];
			}
			if ( preg_match( '/Requires PHP:\s*([0-9.]+)/i', $src, $m2 ) ) {
				$hdr .= ' | Requires PHP: ' . $m2[1];
			}
			if ( preg_match( '/Requires at least:\s*([0-9.]+)/i', $src, $m3 ) ) {
				$hdr .= ' | Requires WP: ' . $m3[1];
			}
		}
		$found[] = $d . ( $hdr ? $hdr : ' (فایل اصلی پیدا نشد)' );
	}
}
s( $found ? '  ' . implode( "\n  ", $found ) : '  هیچ پوشهٔ ACF پیدا نشد.' );
s( '' );

/* ---------- ۳) وضعیت فعال بودن ---------- */
s( '--- ۳) فعال/غیرفعال ---' );
$active  = (array) get_option( 'active_plugins', array() );
$network = is_multisite() ? (array) get_site_option( 'active_sitewide_plugins', array() ) : array();
$acf_active = array();
foreach ( $active as $p ) {
	if ( false !== stripos( $p, 'acf' ) || false !== stripos( $p, 'advanced-custom' ) ) {
		$acf_active[] = $p;
	}
}
s( 'در active_plugins: ' . ( $acf_active ? implode( ', ', $acf_active ) : 'هیچ' ) );
s( 'شبکه‌ای: ' . ( $network ? implode( ', ', array_keys( $network ) ) : 'هیچ' ) );
s( 'کل افزونه‌های فعال: ' . count( $active ) );
s( '' );

/* ---------- ۴) تضادِ نام‌ها ---------- */
s( '--- ۴) تضادِ نام (مهم‌ترین بخش) ---' );
$names = array( 'get_field', 'the_field', 'get_sub_field', 'the_sub_field', 'have_rows', 'the_row',
	'get_row_index', 'the_repeater_field', 'the_sub_repeater_field', 'get_fields', 'acf_get_field' );
foreach ( $names as $fn ) {
	if ( ! function_exists( $fn ) ) {
		continue;
	}
	try {
		$ref = new ReflectionFunction( $fn );
		$src = $ref->getFileName() . ':' . $ref->getStartLine();
		$is_ours = ( false !== strpos( (string) $ref->getFileName(), '/themes/' ) );
		s( sprintf( '  %-24s %s%s', $fn, $src, $is_ours ? '   ⚠ از قالب!' : '' ) );
	} catch ( Throwable $e ) {
		s( sprintf( '  %-24s (خطا در بازتاب)', $fn ) );
	}
}
s( '' );

/* ---------- ۵) هوک‌های acf/* ---------- */
s( '--- ۵) فیلترها/اکشن‌های acf/* ثبت‌شده ---' );
global $wp_filter;
$acf_hooks = 0;
foreach ( (array) $wp_filter as $tag => $obj ) {
	if ( 0 !== strpos( (string) $tag, 'acf' ) ) {
		continue;
	}
	$n = 0;
	foreach ( (array) $obj->callbacks as $cbs ) {
		$n += count( (array) $cbs );
	}
	s( sprintf( '  %-46s %d callback', $tag, $n ) );
	$acf_hooks++;
}
if ( ! $acf_hooks ) {
	s( '  هیچ هوکی با پیشوند acf ثبت نشده — یعنی ACF اصلاً لود نشده.' );
}
s( '' );

/* ---------- ۶) acf-json و فیلدهای ذخیره‌شده ---------- */
s( '--- ۶) acf-json و فیلدها ---' );
$theme_dir = get_template_directory();
$json_dir  = $theme_dir . '/acf-json';
s( 'acf-json در قالب: ' . ( is_dir( $json_dir ) ? 'هست (' . count( (array) glob( $json_dir . '/*.json' ) ) . ' فایل)' : 'نیست' ) );
$fp = get_stylesheet_directory() . '/acf-json';
if ( $fp !== $json_dir ) {
	s( 'acf-json در چایلد: ' . ( is_dir( $fp ) ? 'هست' : 'نیست' ) );
}

$groups = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'acf-field-group'" ); // phpcs:ignore
$fields = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'acf-field'" ); // phpcs:ignore
s( 'گروه‌های فیلد در دیتابیس: ' . (int) $groups );
s( 'فیلدهای تعریف‌شده: ' . (int) $fields );
if ( (int) $groups > 0 ) {
	$titles = $wpdb->get_col( "SELECT post_title FROM {$wpdb->posts} WHERE post_type = 'acf-field-group' ORDER BY post_title LIMIT 10" ); // phpcs:ignore
	s( '  نمونه: ' . implode( ' · ', array_map( 'strval', (array) $titles ) ) );
}
s( '' );

/* ---------- ۷) فیلدهای موردانتظارِ قالب ---------- */
s( '--- ۷) فیلدهایی که قالب انتظار دارد ---' );
$expect = array( 'artist', 'song', 'music128', 'musics_type', 'video720', 'vip_img', 'artist_image', 'artist_link', 'music_text', 'aimg2' );
foreach ( $expect as $k ) {
	$n = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value <> ''", $k ) ); // phpcs:ignore
	s( sprintf( '  %-16s %6d نوشته مقدار دارد', $k, (int) $n ) );
}
s( '' );

/* ---------- ۸) خطاهای اخیر ---------- */
s( '--- ۸) خطاهای PHP مربوط به ACF ---' );
$log = ini_get( 'error_log' );
if ( $log && is_readable( $log ) ) {
	$tail = @file( $log );
	if ( $tail ) {
		$tail  = array_slice( $tail, -300 );
		$hits  = array();
		foreach ( $tail as $line ) {
			if ( false !== stripos( $line, 'acf' ) || false !== stripos( $line, 'get_field' ) ) {
				$hits[] = trim( $line );
			}
		}
		s( $hits ? "  " . implode( "\n  ", array_slice( $hits, -12 ) ) : '  چیزی مربوط به ACF در لاگ نبود.' );
	} else {
		s( '  لاگ خوانده نشد.' );
	}
} else {
	s( '  error_log تنظیم نشده یا خواندنی نیست (' . ( $log ? $log : 'خالی' ) . ')' );
}
s( '' );

/* ---------- ۹) جمع‌بندی ---------- */
s( '=== جمع‌بندی ===' );
if ( ! defined( 'ACF_VERSION' ) && ! class_exists( 'ACF' ) ) {
	s( 'ACF لود نشده است. اگر انتظار دارید فعال باشد:' );
	s( '  ۱) در پیشخوان ← افزونه‌ها، خطای دقیق کنار ACF را ببینید.' );
	s( '  ۲) اگر پیام «مشکلی جدی» بود، معمولاً یک Fatal error قبل از آن رخ داده.' );
	s( '  ۳) error_log هاست را در cPanel ← Errors ببینید.' );
} else {
	s( 'ACF لود شده است. اگر جای دیگری مشکل دارید، این خروجی را بفرستید.' );
}
s( 'پس از تشخیص این فایل را پاک کنید.' );
