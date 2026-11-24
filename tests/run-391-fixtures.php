<?php
/**
 * آزمونِ نسخهٔ ۳.۱۰.۰ — حسابِ کاربری، ورودِ پیامکی، پنل و اتصال به ربات
 * =========================================================================
 * اجرا: node tools/php-test.mjs tests/run-391.php
 *
 * این آزمون دادهٔ ساختگی دارد و رفتارِ کد را در شرایطِ لبه می‌سنجد:
 *   • کدِ یک‌بارمصرف: عمر، سقفِ تلاش، فاصلهٔ ارسالِ دوباره و سقفِ نرخ
 *   • ساختِ خودکارِ حساب با شمارهٔ موبایل و ورودِ بدونِ رمز
 *   • سقف‌های علاقه‌مندی/پلی‌لیست/دنبال‌کردن و شمارندهٔ واقعی
 *   • کدِ هشت‌حرفیِ اتصال به ربات، یک‌بارمصرف بودن و جابه‌جاییِ حساب
 *   • احراز هویتِ REST (Bearer، توکنِ غلط، نبودِ توکن) و شکلِ پاسخ‌ها
 *   • مارکاپِ بدونِ JS (مودال، دکمهٔ قلب، دکمهٔ دنبال‌کردن، تب‌ها)
 *
 * @package Taranehyar
 */

require __DIR__ . '/stubs-wp.php';

if ( ! defined( 'TARANEHYAR_VERSION' ) ) {
	define( 'TARANEHYAR_VERSION', '3.10.0' );
}
if ( ! defined( 'TARANEHYAR_DIR' ) ) {
	define( 'TARANEHYAR_DIR', '/tmp/theme' );
}
if ( ! defined( 'TARANEHYAR_URI' ) ) {
	define( 'TARANEHYAR_URI', 'https://ex/theme' );
}
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wp/' );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

$GLOBALS['__options']   = array( 'option_tree' => array() );
$GLOBALS['__trans']     = array();
$GLOBALS['__postmeta']  = array();
$GLOBALS['__posts']     = array();
$GLOBALS['__terms']     = array();
$GLOBALS['__postterms'] = array();
$GLOBALS['__styles']    = array();
$GLOBALS['__scripts']   = array();
$GLOBALS['__localized'] = array();
$GLOBALS['__hooks']     = array();

/* ---------- دادهٔ ساختگی ---------- */

class T391_Term extends WP_Term {
	public $description = '';
}

function t391_setup_data() {
	$GLOBALS['__blog']['name'] = 'آهنگ‌بان';

	$titles = array(
		701 => 'آهنگِ یک',
		702 => 'آهنگِ دو',
		703 => 'آهنگِ سه',
		704 => 'آهنگِ بی‌فایل',
	);
	foreach ( $titles as $id => $title ) {
		$GLOBALS['__posts'][ $id ]              = new WP_Post( $id, $title );
		$GLOBALS['__postmeta'][ $id ]['song']   = $title;
		$GLOBALS['__postmeta'][ $id ]['artist'] = 'خوانندهٔ الف';
		$GLOBALS['__thumb'][ $id ]              = 'https://ex/c-' . $id . '.jpg';
		$GLOBALS['__postmeta'][ $id ]['views']  = 10;
	}
	$GLOBALS['__postmeta'][701]['music320'] = 'https://ex/701.mp3';
	$GLOBALS['__postmeta'][702]['music320'] = 'https://ex/702.mp3';
	$GLOBALS['__postmeta'][703]['music320'] = 'https://ex/703.mp3';

	$GLOBALS['__fake_ids'] = array( 703, 702, 701, 704 );

	$GLOBALS['__terms'][41] = new T391_Term( 41, 'خوانندهٔ الف', 'artist', 'singer-a', 3 );
	$GLOBALS['__terms'][42] = new T391_Term( 42, 'خوانندهٔ ب', 'artist', 'singer-b', 1 );

	$GLOBALS['__postterms'][701]['artist'] = array( $GLOBALS['__terms'][41] );
	$GLOBALS['__postterms'][702]['artist'] = array( $GLOBALS['__terms'][41] );
	$GLOBALS['__postterms'][703]['artist'] = array( $GLOBALS['__terms'][42] );

	$GLOBALS['__taxonomies']['artist'] = (object) array( 'name' => 'artist' );
}

t391_setup_data();

/* ---------- شبیه‌سازی‌های کوچک ---------- */

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = '' ) {
		return 'n0nce-' . $action;
	}
}

if ( ! function_exists( 'sanitize_html_class' ) ) {
	function sanitize_html_class( $class, $fallback = '' ) {
		$class = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $class );
		return '' === $class ? $fallback : $class;
	}
}

if ( ! function_exists( 'date_i18n' ) ) {
	function date_i18n( $format, $timestamp = null ) {
		return gmdate( 'Y-m-d H:i', $timestamp ? (int) $timestamp : time() );
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	function get_posts( $args = array() ) {
		$ids = isset( $GLOBALS['__fake_ids'] ) ? (array) $GLOBALS['__fake_ids'] : array();
		$ids = array_map( 'intval', $ids );
		if ( ! empty( $args['post__in'] ) ) {
			$ids = array_values( array_intersect( $ids, array_map( 'intval', (array) $args['post__in'] ) ) );
		}
		if ( ! empty( $args['post__not_in'] ) ) {
			$ids = array_values( array_diff( $ids, array_map( 'intval', (array) $args['post__not_in'] ) ) );
		}
		if ( isset( $args['posts_per_page'] ) && (int) $args['posts_per_page'] > 0 ) {
			$ids = array_slice( $ids, 0, (int) $args['posts_per_page'] );
		}
		return array_values( $ids );
	}
}

if ( ! function_exists( 'get_pages' ) ) {
	function get_pages( $args = array() ) {
		return array();
	}
}

if ( ! function_exists( 'shortcode_atts' ) ) {
	function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
		$atts = (array) $atts;
		$out  = array();
		foreach ( (array) $pairs as $name => $default ) {
			$out[ $name ] = array_key_exists( $name, $atts ) ? $atts[ $name ] : $default;
		}
		return $out;
	}
}

if ( ! function_exists( 'add_shortcode' ) ) {
	function add_shortcode( $tag, $callback ) {
		$GLOBALS['__shortcodes'][ $tag ] = $callback;
		return true;
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'https://ex/wp-admin/' . ltrim( (string) $path, '/' );
	}
}

if ( ! function_exists( 'tara_artist_archive_url' ) ) {
	function tara_artist_archive_url( $term = 0 ) {
		return 'https://ex/artist/' . (int) $term;
	}
}

if ( ! function_exists( 'tara_artist_image_url' ) ) {
	function tara_artist_image_url( $term, $size = 'medium' ) {
		return 'https://ex/artist-' . (int) $term->term_id . '.jpg';
	}
}

if ( ! function_exists( 'tara_fa_number' ) ) {
	function tara_fa_number( $number ) {
		$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		return str_replace( range( 0, 9 ), $fa, (string) $number );
	}
}

if ( ! function_exists( 'tara_css_rel' ) ) {
	function tara_css_rel( $rel ) {
		return $rel;
	}
}

if ( ! function_exists( 'tara_radio_artist_id' ) ) {
	function tara_radio_artist_id( $post_id ) {
		return isset( $GLOBALS['__postterms'][ $post_id ]['artist'][0] ) ? (int) $GLOBALS['__postterms'][ $post_id ]['artist'][0]->term_id : 0;
	}
}

/* ---------- بارگذاریِ فایل‌های قالب ---------- */

require '/tmp/theme/inc/tara-ui.php';
require '/tmp/theme/inc/tara-account.php';
require '/tmp/theme/inc/tara-account-ui.php';

/* ---------- ابزارِ آزمون ---------- */

$PASS  = 0;
$FAIL  = 0;
$FAILS = array();

function t( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL, $FAILS;
	if ( $cond ) {
		$PASS++;
		echo "ok   $name\n";
	} else {
		$FAIL++;
		$FAILS[] = $name . ( $detail ? " — $detail" : '' );
		echo "FAIL $name" . ( $detail ? " — $detail" : '' ) . "\n";
	}
}

function f( $path ) {
	return (string) @file_get_contents( '/tmp/theme/' . ltrim( $path, '/' ) );
}

/** خروجیِ یک تابعِ چاپ‌کننده را رشته می‌کند. */
function grab( $fn, $args = array() ) {
	ob_start();
	call_user_func_array( $fn, (array) $args );
	return (string) ob_get_clean();
}

/** ست‌کردنِ یک تنظیمِ حساب. */
function set_acc( $key, $value ) {
	$GLOBALS['__options']['option_tree']['account_' . $key] = $value;
}

/** پاک‌کردنِ همهٔ تنظیم‌های حساب. */
function reset_acc() {
	$GLOBALS['__options']['option_tree'] = array();
}
