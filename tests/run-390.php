<?php
/**
 * آزمونِ نسخهٔ ۳.۹.۰ — رادیو/دی‌جی، صفحهٔ ژانر و حال‌وهوا، تنظیمات و سئوی ترم
 * ===========================================================================
 * اجرا: node tools/php-test.mjs tests/run-390.php
 *
 * این آزمون عمداً از دادهٔ ساختگی استفاده می‌کند (بدونِ وردپرسِ واقعی) تا
 * رفتارِ کد در شرایطِ لبه هم سنجیده شود:
 *   • آهنگِ بدونِ فایل صوتی نباید وارد صف شود
 *   • سقفِ تعداد و استثناها (exclude) باید رعایت شوند
 *   • کشِ شش‌ساعتهٔ آمار نباید جای منطق را بگیرد
 *   • مارکاپ باید بدونِ JS هم کامل باشد
 *
 * @package Taranehyar
 */

require __DIR__ . '/stubs-wp.php';

if ( ! defined( 'TARANEHYAR_VERSION' ) ) {
	define( 'TARANEHYAR_VERSION', '3.9.0' );
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
$GLOBALS['__is_tax']    = array();
$GLOBALS['__styles']    = array();
$GLOBALS['__scripts']   = array();
$GLOBALS['__localized'] = array();

/* ---------- دادهٔ ساختگی ---------- */

class T390_Term extends WP_Term {
	public $description = '';
}

function t390_setup_data() {
	$GLOBALS['__blog']['name'] = 'ترانه یار';

	/* نوشته‌ها: ۶۰۱ تا ۶۰۶ */
	$titles = array(
		601 => 'آهنگ اول',
		602 => 'آهنگ دوم',
		603 => 'آهنگ بدون فایل',
		604 => 'آهنگ چهارم',
		605 => 'آهنگ پنجم',
		606 => 'آهنگ ششم',
	);
	foreach ( $titles as $id => $title ) {
		$GLOBALS['__posts'][ $id ] = new WP_Post( $id, $title );
		$GLOBALS['__postmeta'][ $id ]['song']   = $title;
		$GLOBALS['__postmeta'][ $id ]['artist'] = 'خوانندهٔ ' . $id;
		$GLOBALS['__thumb'][ $id ]              = 'https://ex/cover-' . $id . '.jpg';
		$GLOBALS['__postmeta'][ $id ]['views']  = 100;
	}

	$GLOBALS['__postmeta'][601]['music320'] = 'https://ex/601.mp3';
	$GLOBALS['__postmeta'][601]['duration'] = 215;
	$GLOBALS['__postmeta'][601]['views']    = 500;

	$GLOBALS['__postmeta'][602]['music128'] = 'https://ex/602.mp3';
	$GLOBALS['__postmeta'][602]['duration'] = '3:40';
	$GLOBALS['__postmeta'][602]['views']    = 900;

	/* ۶۰۳ عمداً فایل صوتی ندارد */
	$GLOBALS['__postmeta'][603]['views'] = 100;

	$GLOBALS['__postmeta'][604]['music']    = 'https://ex/604.mp3';
	$GLOBALS['__postmeta'][604]['views']    = 300;
	$GLOBALS['__postmeta'][604]['duration'] = 198;

	$GLOBALS['__postmeta'][605]['music320'] = 'https://ex/605.mp3';
	$GLOBALS['__postmeta'][605]['views']    = 50;
	$GLOBALS['__postmeta'][605]['duration'] = 240;

	$GLOBALS['__postmeta'][606]['music320'] = 'https://ex/606.mp3';
	$GLOBALS['__postmeta'][606]['views']    = 700;
	$GLOBALS['__postmeta'][606]['duration'] = 205;

	/* ترتیبِ «جدیدترین» (کوئریِ ما تصور می‌کند همین است) */
	$GLOBALS['__fake_ids']        = array( 606, 605, 604, 603, 602, 601 );
	$GLOBALS['__fake_term_posts'] = array(
		21 => array( 606, 605, 604, 603, 602, 601 ),
		22 => array( 601, 602 ),
	);

	/* ترم‌ها */
	$genre = new T390_Term( 21, 'پاپ', 'tara_genre', 'pop', 5 );
	$genre->description = 'آهنگ‌های پاپ فارسی برای پخش و دانلود.';
	$GLOBALS['__terms'][21] = $genre;

	$mood = new T390_Term( 22, 'عاشقانه', 'tara_mood', 'romantic', 2 );
	$GLOBALS['__terms'][22] = $mood;

	$GLOBALS['__terms'][31] = new T390_Term( 31, 'خوانندهٔ الف', 'artist', 'singer-a', 3 );
	$GLOBALS['__terms'][32] = new T390_Term( 32, 'خوانندهٔ ب', 'artist', 'singer-b', 3 );
	$GLOBALS['__terms'][33] = new T390_Term( 33, 'خوانندهٔ ج', 'artist', 'singer-c', 1 );

	$GLOBALS['__postterms'][601]['artist'] = array( $GLOBALS['__terms'][31] );
	$GLOBALS['__postterms'][602]['artist'] = array( $GLOBALS['__terms'][31] );
	$GLOBALS['__postterms'][604]['artist'] = array( $GLOBALS['__terms'][32] );
	$GLOBALS['__postterms'][605]['artist'] = array( $GLOBALS['__terms'][31] );
	$GLOBALS['__postterms'][606]['artist'] = array( $GLOBALS['__terms'][33] );

	$GLOBALS['__postterms'][601]['tara_mood'] = array( $GLOBALS['__terms'][22] );
	$GLOBALS['__postterms'][602]['tara_mood'] = array( $GLOBALS['__terms'][22] );
}

t390_setup_data();

$GLOBALS['__taxonomies']['tara_genre'] = (object) array( 'name' => 'tara_genre' );
$GLOBALS['__taxonomies']['tara_mood']  = (object) array( 'name' => 'tara_mood' );

/* ---------- شبیه‌سازیِ توابعِ وردپرس که در stubs نیستند ---------- */

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
		return gmdate( 'Y-m-d', $timestamp ? (int) $timestamp : time() );
	}
}

if ( ! function_exists( 'update_meta_cache' ) ) {
	function update_meta_cache( $type, $ids ) {
		return true;
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	/**
	 * شبیه‌سازِ سبکِ get_posts: فقط چیزهایی که خودِ قالب استفاده می‌کند.
	 */
	function get_posts( $args = array() ) {
		$ids = isset( $GLOBALS['__fake_ids'] ) ? $GLOBALS['__fake_ids'] : array();

		if ( ! empty( $args['tax_query'][0]['terms'] ) ) {
			$term = (int) $args['tax_query'][0]['terms'];
			$ids  = isset( $GLOBALS['__fake_term_posts'][ $term ] ) ? $GLOBALS['__fake_term_posts'][ $term ] : array();
		}
		if ( ! empty( $args['meta_query'][0]['key'] ) && 'vip_song' === $args['meta_query'][0]['key'] ) {
			$featured = isset( $GLOBALS['__featured_ids'] ) ? (array) $GLOBALS['__featured_ids'] : array();
			$ids      = array_values( array_intersect( $ids, array_map( 'intval', $featured ) ) );
		}
		if ( ! empty( $args['post__not_in'] ) ) {
			$ids = array_values( array_diff( $ids, array_map( 'intval', (array) $args['post__not_in'] ) ) );
		}
		if ( isset( $args['orderby'] ) && 'meta_value_num' === $args['orderby'] ) {
			usort(
				$ids,
				function ( $a, $b ) {
					$va = isset( $GLOBALS['__postmeta'][ $a ]['views'] ) ? (int) $GLOBALS['__postmeta'][ $a ]['views'] : 0;
					$vb = isset( $GLOBALS['__postmeta'][ $b ]['views'] ) ? (int) $GLOBALS['__postmeta'][ $b ]['views'] : 0;
					return $vb - $va;
				}
			);
		}
		if ( isset( $args['orderby'] ) && 'rand' === $args['orderby'] ) {
			$ids = array_reverse( $ids );
		}
		if ( isset( $args['posts_per_page'] ) && (int) $args['posts_per_page'] > 0 ) {
			$ids = array_slice( $ids, 0, (int) $args['posts_per_page'] );
		}

		return array_values( $ids );
	}
}

if ( ! function_exists( 'get_terms' ) ) {
	function get_terms( $args = array() ) {
		$tax  = isset( $args['taxonomy'] ) ? $args['taxonomy'] : '';
		$out  = array();
		$skip = isset( $args['exclude'] ) ? array_map( 'intval', (array) $args['exclude'] ) : array();

		foreach ( $GLOBALS['__terms'] as $term ) {
			if ( $tax && $term->taxonomy !== $tax ) {
				continue;
			}
			if ( in_array( (int) $term->term_id, $skip, true ) ) {
				continue;
			}
			$out[] = $term;
		}

		return $out;
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

if ( ! function_exists( 'has_shortcode' ) ) {
	function has_shortcode( $content, $tag ) {
		return false !== strpos( (string) $content, '[' . $tag );
	}
}

if ( ! function_exists( 'is_tax' ) ) {
	function is_tax( $taxonomy = '', $term = '' ) {
		$wanted = array_filter( (array) $taxonomy );
		$have   = (array) $GLOBALS['__is_tax'];
		if ( empty( $wanted ) ) {
			return ! empty( $have );
		}
		return (bool) array_intersect( $wanted, $have );
	}
}

if ( ! function_exists( 'is_page_template' ) ) {
	function is_page_template( $template = '' ) {
		return isset( $GLOBALS['__page_template'] ) && $GLOBALS['__page_template'] === $template;
	}
}

if ( ! function_exists( 'tara_page_is_home' ) ) {
	function tara_page_is_home() {
		return ! empty( $GLOBALS['__tara_home'] );
	}
}

if ( ! function_exists( 'tara_music_taxonomy_ids' ) ) {
	function tara_music_taxonomy_ids() {
		return array( 'tara_genre', 'tara_mood' );
	}
}

if ( ! function_exists( 'tara_music_taxonomy_label' ) ) {
	function tara_music_taxonomy_label( $taxonomy, $which = 'singular' ) {
		$map = array(
			'tara_genre' => array( 'ژانر', 'ژانرها' ),
			'tara_mood'  => array( 'حال‌وهوا', 'حال‌وهواها' ),
		);
		if ( ! isset( $map[ $taxonomy ] ) ) {
			return $taxonomy;
		}
		return 'plural' === $which ? $map[ $taxonomy ][1] : $map[ $taxonomy ][0];
	}
}

if ( ! function_exists( 'tara_artist_image_url' ) ) {
	function tara_artist_image_url( $term, $size = 'medium' ) {
		return 'https://ex/artist-' . (int) $term->term_id . '.jpg';
	}
}

/* ---------- بارگذاریِ فایل‌های قالب ---------- */

require '/tmp/theme/inc/tara-ui.php';
require '/tmp/theme/inc/tara-radio.php';
require '/tmp/theme/inc/tara-radio-ui.php';
require '/tmp/theme/inc/tara-tax-hero.php';

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

/**
 * شناسهٔ ترم‌ها از فهرستِ WP_Term.
 *
 * @param array $terms ترم‌ها.
 * @return int[]
 */
function wp_list_pluck_simple( $terms ) {
	$out = array();
	foreach ( (array) $terms as $term ) {
		$out[] = (int) $term->term_id;
	}
	return $out;
}

/* ======================================================================
   ۱) فایل‌ها و سیم‌کشی
   ====================================================================== */
t( 'فایل: inc/tara-radio.php موجود است', is_readable( '/tmp/theme/inc/tara-radio.php' ) );
t( 'فایل: inc/tara-radio-ui.php موجود است', is_readable( '/tmp/theme/inc/tara-radio-ui.php' ) );
t( 'فایل: inc/tara-tax-hero.php موجود است', is_readable( '/tmp/theme/inc/tara-tax-hero.php' ) );
t( 'فایل: js/tara-radio.js موجود است', is_readable( '/tmp/theme/js/tara-radio.js' ) );
t( 'فایل: css/tara-radio.css موجود است', is_readable( '/tmp/theme/css/tara-radio.css' ) );
t( 'فایل: css/tara-tax.css موجود است', is_readable( '/tmp/theme/css/tara-tax.css' ) );
t( 'فایل: page-radio.php موجود است', is_readable( '/tmp/theme/page-radio.php' ) );

$func = f( 'functions.php' );
t( 'سیم‌کشی: functions.php رادیو را بار می‌کند', false !== strpos( $func, 'inc/tara-radio.php' ) );
t( 'سیم‌کشی: functions.php مارکاپِ رادیو را بار می‌کند', false !== strpos( $func, 'inc/tara-radio-ui.php' ) );
t( 'سیم‌کشی: functions.php سربرگِ ترم را بار می‌کند', false !== strpos( $func, 'inc/tara-tax-hero.php' ) );
t( 'سیم‌کشی: CSS ترم فقط برای ژانر/حال‌وهوا', false !== strpos( $func, "taranehyar-tax" ) && false !== strpos( $func, "'/css/tara-tax.css'" ) && false !== strpos( $func, 'tara_radio_is_tax_page()' ) );

$listing = f( 'inc/tax-listing.php' );
t( 'آرشیو: از سربرگِ غنی استفاده می‌کند', false !== strpos( $listing, 'tara_tax_hero(' ) );
t( 'آرشیو: کارتِ رادیو را چاپ می‌کند', false !== strpos( $listing, 'tara_radio_box(' ) );
t( 'آرشیو: متنِ معرفی در انتها', false !== strpos( $listing, 'tara_tax_about(' ) );
t( 'آرشیو: لنگرِ فهرست برای دکمهٔ «فهرست کامل»', false !== strpos( $listing, 'id="ty-tax-list"' ) );

$home = f( 'home.php' );
t( 'صفحهٔ اصلی: بخشِ رادیو صدا زده می‌شود', false !== strpos( $home, 'tara_radio_home_section()' ) );
t( 'صفحهٔ اصلی: رادیو بعد از پلیرِ اصلی می‌آید', strpos( $home, 'tara_radio_home_section()' ) > strpos( $home, "inc/tarlanweb_player" ) );

t( 'صفحهٔ آهنگ: دکمهٔ افزودن به رادیو', false !== strpos( f( 'single.php' ), 'tara_radio_add_button(' ) );
t( 'ردیف آهنگ: دکمهٔ افزودن به رادیو', false !== strpos( f( 'inc/tara-ui.php' ), 'ty-radio-add--row' ) );
t( 'قالب صفحهٔ رادیو: نام قالب ثبت شده', false !== strpos( f( 'page-radio.php' ), 'Template Name: رادیو ترانه یار' ) );
t( 'قالب صفحهٔ رادیو: کارت را می‌سازد', false !== strpos( f( 'page-radio.php' ), 'tara_radio_box(' ) );

/* ======================================================================
   ۲) تنظیمات
   ====================================================================== */
$opts = f( 'inc/theme-options.php' );
t( 'تنظیمات: بخشِ «radio» ثبت شده', false !== strpos( $opts, "array('id'=>'radio','title'=>'رادیو و دی‌جی')" ) );

$radio_keys = array(
	'radio_on', 'radio_home', 'radio_archives', 'radio_mode', 'radio_limit',
	'radio_xfade', 'radio_tempo', 'radio_fx', 'radio_cue', 'radio_duck',
	'radio_station', 'radio_station_file', 'radio_station_text', 'radio_station_names',
	'radio_station_next', 'radio_station_every', 'radio_station_file', 'radio_autostart',
);
$missing = array();
foreach ( array_unique( $radio_keys ) as $key ) {
	if ( false === strpos( $opts, "'id'=>'" . $key . "'" ) ) {
		$missing[] = $key;
	}
}
t( 'تنظیمات: همهٔ کلیدهای رادیو هست', empty( $missing ), implode( ',', $missing ) );

$shell = f( 'inc/admin-settings-shell.php' );
t( 'تنظیمات: بخش رادیو در پوستهٔ تنظیمات', false !== strpos( $shell, "'radio'   => array(" ) );
$panel = f( 'inc/admin-panel.php' );
t( 'تنظیمات: بخش رادیو در پنل مدیریت', false !== strpos( $panel, "'id'    => 'radio'" ) );

$defaults = tara_radio_defaults();
t( 'تنظیمات: پیش‌فرض‌ها آرایه‌اند', is_array( $defaults ) && ! empty( $defaults ) );
t( 'تنظیمات: پیش‌فرضِ کراس‌فید ۶ ثانیه است', 6 === (int) $defaults['xfade'] );
t( 'تنظیمات: وکالِ معرفی پیش‌فرض روشن است', 'on' === $defaults['station'] );
t( 'منابع: پنج منبع تعریف شده', 5 === count( tara_radio_modes() ) );
t( 'منابع: نامِ فارسیِ «تصادفی»', isset( tara_radio_modes()['random'] ) && false !== strpos( tara_radio_modes()['random'], 'تصادفی' ) );

t( 'خواندنِ تنظیم: مقدار خالی ⇒ پیش‌فرض', 6 === (int) tara_radio_option( 'xfade' ) );
$GLOBALS['__options']['option_tree']['radio_xfade'] = 14;
t( 'خواندنِ تنظیم: مقدار ذخیره‌شده خوانده می‌شود', 14 === (int) tara_radio_option( 'xfade' ) );
unset( $GLOBALS['__options']['option_tree']['radio_xfade'] );

t( 'کلیدِ رادیو: پیش‌فرض روشن', true === tara_radio_on() );
$GLOBALS['__options']['option_tree']['radio_on'] = 'off';
t( 'کلیدِ رادیو: خاموش‌شدن را می‌فهمد', false === tara_radio_on() );
unset( $GLOBALS['__options']['option_tree']['radio_on'] );

$settings = tara_radio_settings();
t( 'پیکربندی: محدودهٔ کراس‌فید ۰..۱۶', $settings['xfade'] >= 0 && $settings['xfade'] <= 16 );
t( 'پیکربندی: duck نسبت است (۰..۱)', $settings['duck'] > 0 && $settings['duck'] <= 1 );
t( 'پیکربندی: نامِ سایت در پیکربندی', 'ترانه یار' === $settings['site'] );
t( 'پیکربندی: متنِ وکال جای‌نگهدارِ {site} دارد', false !== strpos( $settings['station']['text'], '{site}' ) );

/* ======================================================================
   ۳) استخراجِ آهنگ‌ها
   ====================================================================== */
t( 'مدت: متای عددی', 215 === tara_radio_duration( 601 ) );
t( 'مدت: قالب «۳:۴۰»', 220 === tara_radio_duration( 602 ) );
t( 'مدت: نامعلوم ⇒ صفر', 0 === tara_radio_duration( 603 ) );

t( 'آهنگ: بدونِ فایل صوتی ⇒ null', null === tara_radio_track( 603 ) );

$track = tara_radio_track( 601 );
t( 'آهنگ: ساختارِ کامل', is_array( $track ) && isset( $track['id'], $track['title'], $track['artist'], $track['src'], $track['cover'], $track['link'], $track['duration'] ) );
t( 'آهنگ: منبع ۳۲۰ ترجیح دارد', 'https://ex/601.mp3' === $track['src'] );
t( 'آهنگ: کاور از تصویر شاخص', 'https://ex/cover-601.jpg' === $track['cover'] );
t( 'آهنگ: لینک صفحه', false !== strpos( $track['link'], '601' ) );

$ids_new = tara_radio_ids( array( 'mode' => 'new', 'limit' => 10 ) );
t( 'صف: آهنگ‌های بدونِ صدا حذف می‌شوند', ! in_array( 603, $ids_new, true ) );
t( 'صف: همهٔ آهنگ‌های معتبر می‌آیند', 5 === count( $ids_new ), implode( ',', $ids_new ) );

$ids_ex = tara_radio_ids( array( 'mode' => 'new', 'limit' => 10, 'exclude' => array( 606, 605 ) ) );
t( 'صف: استثناها رعایت می‌شوند', ! in_array( 606, $ids_ex, true ) && ! in_array( 605, $ids_ex, true ) );

$ids_lim = tara_radio_ids( array( 'mode' => 'new', 'limit' => 2 ) );
t( 'صف: سقفِ تعداد', 2 === count( $ids_lim ) );

$GLOBALS['__trans'] = array();
$ids_cached        = tara_radio_ids( array( 'mode' => 'views', 'limit' => 4 ) );
t( 'صف: نتیجه در ترنزینت ذخیره می‌شود', ! empty( $GLOBALS['__trans'] ) );
t( 'صف: پربازدیدترین اول است', 602 === (int) $ids_cached[0], implode( ',', $ids_cached ) );

$GLOBALS['__trans'] = array();
$mixed              = tara_radio_ids( array( 'mode' => 'mixed', 'limit' => 6 ) );
t( 'صفِ ترکیبی: از هر دو منبع می‌آورد', in_array( 606, $mixed, true ) && in_array( 602, $mixed, true ) );
t( 'صفِ ترکیبی: تکراری ندارد', count( $mixed ) === count( array_unique( $mixed ) ) );

$tracks = tara_radio_tracks( array( 'mode' => 'new', 'limit' => 4 ) );
t( 'صف: خروجی آمادهٔ پخش', is_array( $tracks ) && count( $tracks ) >= 3 && ! empty( $tracks[0]['src'] ) );

/* ======================================================================
   ۴) نقطهٔ پایانیِ AJAX
   ====================================================================== */
$ajax_rejected = false;
$_GET          = array( 'nonce' => 'x', 'limit' => '2' );
t( 'AJAX: nonce بررسی می‌شود', false !== strpos( f( 'inc/tara-radio.php' ), "check_ajax_referer( 'tara_radio', 'nonce', false )" ) );

$_GET = array(
	'nonce' => 'n0nce-tara_radio',
	'tax'   => 'tara_genre',
	'term'  => '21',
	'mode'  => 'new',
	'limit' => '999',
	'exclude' => '606',
);
$payload = null;
try {
	tara_radio_ajax();
} catch ( RuntimeException $e ) {
	$payload = isset( $GLOBALS['__json'] ) ? $GLOBALS['__json'] : null;
}
t( 'AJAX: پاسخ JSON موفق', is_array( $payload ) && ! empty( $payload['success'] ) );
t( 'AJAX: آهنگ‌ها برگشتند', ! empty( $payload['data']['tracks'] ) );
t( 'AJAX: استثنای درخواستی حذف شد', 4 === count( $payload['data']['tracks'] ) && ! in_array( 606, array_map( 'intval', $payload['data']['tracks'] ), true ) );
t( 'AJAX: تاکسونومی و ترم در پاسخ', 'tara_genre' === $payload['data']['tax'] && 21 === (int) $payload['data']['term'] );

$_GET = array(
	'nonce' => 'n0nce-tara_radio',
	'tax'   => 'evil_tax',
	'term'  => '999',
	'mode'  => 'nope',
	'limit' => '1000',
);
$payload2 = null;
try {
	tara_radio_ajax();
} catch ( RuntimeException $e ) {
	$payload2 = isset( $GLOBALS['__json'] ) ? $GLOBALS['__json'] : null;
}
t( 'AJAX: تاکسونومیِ ناشناس رد می‌شود', '' === $payload2['data']['tax'] );
t( 'AJAX: ترمِ ناموجود رد می‌شود', 0 === (int) $payload2['data']['term'] );
t( 'AJAX: منبعِ ناشناس ⇒ mixed', 'mixed' === $payload2['data']['mode'] );

/* ======================================================================
   ۵) مارکاپِ رادیو
   ====================================================================== */
$box = tara_radio_box(
	array(
		'context'  => 'tax',
		'taxonomy' => 'tara_genre',
		'term_id'  => 21,
		'tracks'   => $tracks,
		'title'    => 'میکس تست',
	)
);

t( 'مارکاپ: ظرفِ رادیو', false !== strpos( $box, 'data-tara-radio' ) );
t( 'مارکاپ: زمینهٔ تاکسونومی', false !== strpos( $box, 'data-radio-tax="tara_genre"' ) && false !== strpos( $box, 'data-radio-term="21"' ) );
t( 'مارکاپ: دادهٔ صف به‌صورت JSON', false !== strpos( $box, 'class="ty-radio__data"' ) );
t( 'مارکاپ: بدونِ </script> تزریقی در JSON', 0 === preg_match( '/ty-radio__data">.*?<\/script>.*?<\/script>/s', $box ) );
t( 'مارکاپ: دکمهٔ پخش', false !== strpos( $box, 'data-radio-toggle' ) );
t( 'مارکاپ: بعدی و قبلی', false !== strpos( $box, 'data-radio-next' ) && false !== strpos( $box, 'data-radio-prev' ) );
t( 'مارکاپ: اسلایدرِ میکس', false !== strpos( $box, 'data-radio-xfade' ) );
t( 'مارکاپ: سه حالتِ شدت', 3 === substr_count( $box, 'data-radio-power=' ) );
t( 'مارکاپ: فهرست من', false !== strpos( $box, 'data-radio-mine' ) && false !== strpos( $box, 'data-radio-mine-list' ) );
t( 'مارکاپ: خروجیِ فهرست جلسه', false !== strpos( $box, 'data-radio-export' ) );
t( 'مارکاپ: نوار زمان و صدا سنج', false !== strpos( $box, 'data-radio-seek' ) && false !== strpos( $box, 'data-radio-vu' ) );
t( 'مارکاپ: ۲۲ میلهٔ صدا سنج', 22 === substr_count( $box, '<i style="--i:' ) );
t( 'مارکاپ: ردیف‌های صف از سمت سرور', false !== strpos( $box, 'ty-radio__row' ) );
t( 'مارکاپ: عنوانِ دلخواه', false !== strpos( $box, 'میکس تست' ) );
t( 'مارکاپ: بدونِ منبع، دکمهٔ منبع ندارد', false === strpos( $box, 'data-radio-source=' ) );

$box_src = tara_radio_box(
	array(
		'context' => 'page',
		'tracks'  => $tracks,
		'sources' => true,
	)
);
t( 'مارکاپ: پنج دکمهٔ منبع در صفحهٔ رادیو', 5 === substr_count( $box_src, 'data-radio-source=' ) );

/* با دادهٔ موجود، «صف خالی» خودش با کوئری پر می‌شود (رفتارِ درست) */
t( 'مارکاپ: صفِ خالی با کوئریِ خودکار پر می‌شود', false !== strpos( tara_radio_box( array( 'tracks' => array(), 'limit' => 3 ) ), 'data-tara-radio' ) );

/* حالا داده را برمی‌داریم تا «هیچ آهنگی نیست» را بسنجیم */
$keep_ids   = $GLOBALS['__fake_ids'];
$keep_terms = $GLOBALS['__fake_term_posts'];
$GLOBALS['__fake_ids']        = array();
$GLOBALS['__fake_term_posts'] = array();
$GLOBALS['__trans']           = array();
t( 'مارکاپ: بی‌آهنگ + hide_empty ⇒ رشتهٔ خالی', '' === tara_radio_box( array( 'tracks' => array(), 'hide_empty' => true ) ) );
t( 'مارکاپ: بی‌آهنگ بدونِ hide_empty ⇒ پیام خالی', false !== strpos( tara_radio_box( array( 'tracks' => array() ) ), 'data-radio-empty' ) );
t( 'بخشِ صفحهٔ اصلی: بی‌آهنگ ⇒ چیزی چاپ نمی‌شود', '' === tara_radio_home_section() );
$GLOBALS['__fake_ids']        = $keep_ids;
$GLOBALS['__fake_term_posts'] = $keep_terms;
$GLOBALS['__trans']           = array();

$GLOBALS['__options']['option_tree']['radio_on'] = 'off';
t( 'مارکاپ: رادیو خاموش ⇒ خروجی خالی', '' === tara_radio_box( array( 'tracks' => $tracks ) ) );
unset( $GLOBALS['__options']['option_tree']['radio_on'] );

/* دکمهٔ افزودن — v3.10.1: پیش‌فرض پنهان است (کاربر دکمه‌ای نمی‌بیند) */
t( 'دکمهٔ افزودن: پیش‌فرض خاموش (کاربر نمی‌بیند)', '' === tara_radio_add_button( 601 ) );
t( 'دکمهٔ افزودن: کلیدِ user_add پیش‌فرض off', 'off' === (string) tara_radio_option( 'user_add' ) );
t( 'دکمهٔ افزودن: صحنهٔ آهنگ وقتی خاموش است دکمه ندارد', false === strpos( tara_radio_box( array( 'tracks' => $tracks ) ), 'data-tara-radio-add' ) );

/* روشن‌کردنِ گزینه، دکمه را برمی‌گرداند */
$GLOBALS['__options']['option_tree']['radio_user_add'] = 'on';
$add = tara_radio_add_button( 601 );
t( 'دکمهٔ افزودن (روشن): data-tara-radio-add', false !== strpos( $add, 'data-tara-radio-add' ) );
t( 'دکمهٔ افزودن (روشن): دادهٔ آهنگ جاسازی شده', false !== strpos( $add, 'data-radio-track=' ) && false !== strpos( $add, '601.mp3' ) );
t( 'دکمهٔ افزودن (روشن): آهنگِ بدونِ صدا ⇒ خالی', '' === tara_radio_add_button( 603 ) );
t( 'دکمهٔ افزودن (روشن): label دسترس‌پذیر', false !== strpos( $add, 'aria-label=' ) );
unset( $GLOBALS['__options']['option_tree']['radio_user_add'] );

/* تنظیمات قالب: کلیدِ تازه ثبت شده است */
$radio_opts = f( 'inc/theme-options.php' );
t( 'تنظیمات: کلیدِ radio_user_add', false !== strpos( $radio_opts, "'id'=>'radio_user_add'" ) );
t( 'تنظیمات: پیش‌فرضِ radio_user_add خاموش است', (bool) preg_match( "/'id'=>'radio_user_add'.{0,1200}?'std'=>'off'/s", $radio_opts ) );
t( 'رادیو: help تبِ «فهرست من» با دکمهٔ خاموش هم چاپ می‌شود بدونِ خطا', '' !== tara_radio_box( array( 'tracks' => $tracks ) ) );

/* ======================================================================
   ۶) سربرگِ ترم و سئو
   ====================================================================== */
$genre = $GLOBALS['__terms'][21];
$hero  = tara_tax_hero( $genre, '<p>توضیح دسته</p>' );

t( 'سربرگ: عنوانِ ترم', false !== strpos( $hero, '<h1 class="ty-taxhero__title">پاپ</h1>' ) );
t( 'سربرگ: مسیر صفحه', false !== strpos( $hero, 'ty-taxhero__crumbs' ) );
t( 'سربرگ: آمارِ آهنگ', false !== strpos( $hero, 'ty-taxhero__stats' ) );
t( 'سربرگ: شمارشِ خواننده‌ها', false !== strpos( $hero, 'خواننده' ) );
t( 'سربرگ: دکمهٔ پخشِ میکس', false !== strpos( $hero, 'data-ty-taxplay' ) );
t( 'سربرگ: دکمهٔ کپیِ نشانی', false !== strpos( $hero, 'data-ty-taxcopy' ) );
t( 'سربرگ: چیپ‌های ترم‌های مرتبط', false !== strpos( $hero, 'ty-taxchip' ) );
t( 'سربرگ: هنرمندانِ بخش', false !== strpos( $hero, 'ty-taxspot' ) );
t( 'سربرگ: کاورِ آهنگ‌ها در پس‌زمینه', false !== strpos( $hero, 'ty-taxhero__collage' ) );
t( 'سربرگ: برای تاکسونومیِ غیرِ موسیقی خالی است', '' === tara_tax_hero( new WP_Term( 31, 'خواننده', 'artist', 'x', 3 ) ) );

$about = tara_tax_about( $genre );
t( 'معرفی: پاراگراف‌ها ساخته شدند', false !== strpos( $about, 'ty-taxabout' ) && false !== strpos( $about, 'دانلودِ ۱۲۸ و ۳۲۰' ) );
t( 'معرفی: نامِ خواننده‌ها در متن', false !== strpos( $about, 'خوانندهٔ الف' ) );

t( 'عدد کوتاه: ۱۲.۵ هزار', '۱۲.۵ هزار' === tara_tax_short_number( 12500 ) );
t( 'عدد کوتاه: میلیون', false !== strpos( tara_tax_short_number( 2500000 ), 'میلیون' ) );

$spot = tara_tax_spotlight( $genre, 6 );
t( 'هنرمندان: مرتب بر اساس تعداد آهنگ', ! empty( $spot ) && 31 === (int) $spot[0]['term']->term_id );

$cross = tara_tax_cross_terms( $genre, 8 );
t( 'متقاطع: حال‌وهواهای همین ژانر', ! empty( $cross ) && 'tara_mood' === $cross[0]['term']->taxonomy );

$sibs = tara_tax_siblings( $genre, 6 );
t( 'هم‌خانواده: ترمِ جاری حذف می‌شود', ! in_array( 21, array_map( 'intval', wp_list_pluck_simple( $sibs ) ), true ) );

$schema_out = '';
ob_start();
tara_tax_schema( $genre, $tracks );
$schema_out = ob_get_clean();
$schema_arr = json_decode( trim( str_replace( array( '<script type="application/ld+json">', '</script>' ), '', $schema_out ) ), true );
t( 'ساختار: CollectionPage', is_array( $schema_arr ) && 'CollectionPage' === $schema_arr['@type'] );
t( 'ساختار: MusicPlaylist', isset( $schema_arr['mainEntity']['@type'] ) && 'MusicPlaylist' === $schema_arr['mainEntity']['@type'] );
t( 'ساختار: شمارشِ آهنگ‌ها', 5 === (int) $schema_arr['mainEntity']['numTracks'] );
t( 'ساختار: آهنگ‌ها با مدت', ! empty( $schema_arr['mainEntity']['track'][0]['item']['duration'] ) );

$seo = f( 'inc/ab-seo.php' );
t( 'سئو: شاخهٔ توضیحِ ژانر/حال‌وهوا', false !== strpos( $seo, "is_tax( 'tara_genre' ) || is_tax( 'tara_mood' )" ) );
$robots_pos = strpos( $seo, 'function tara_seo_robots' );
$robots     = false === $robots_pos ? '' : substr( $seo, $robots_pos, 2200 );
t( 'سئو: تاکسونومیِ ژانر در فهرستِ noindex نیست', false === strpos( $robots, 'tara_genre' ) && false === strpos( $robots, 'tara_mood' ) );

/* ======================================================================
   ۶-ب) رادیوی «بهترین دی‌جی» — نسخهٔ ۳.۹.۱
   ====================================================================== */
/* --- نوارِ زمان راست‌به‌چپ --- */
$box_rtl = tara_radio_box( array( 'context' => 'tax', 'tracks' => $tracks ) );
t( 'نوارِ زمان: جهتِ راست‌به‌چپ دارد', false !== strpos( $box_rtl, 'data-radio-seek' ) && false !== strpos( $box_rtl, 'class="ty-radio__range ty-radio__range--seek" data-radio-seek' ) && (bool) preg_match( '/data-radio-seek[^>]*dir="rtl"/', $box_rtl ) );
t( 'نوارِ زمان: دیگر dir=ltr روی seek نیست', ! preg_match( '/data-radio-seek[^>]*dir="ltr"/', $box_rtl ) );
$css_radio = f( 'css/tara-radio.css' );
t( 'نوارِ زمان: CSS هم rtl است', false !== strpos( $css_radio, '.ty-radio__range--seek { direction: rtl; }' ) );
t( 'نوارِ زمان: خطِ پرشدن از راست رشد می‌کند', false !== strpos( $css_radio, 'linear-gradient(to left' ) && false !== strpos( $css_radio, 'inset-inline-start: 0;' ) );
$js_txt = f( 'js/tara-radio.js' );
t( 'نوارِ زمان: JS عرضِ fill را می‌نویسد', false !== strpos( $js_txt, "rail: pick('[data-radio-fill]')" ) && false !== strpos( $js_txt, 'refs.rail.style.width' ) );

/* --- فشرده‌سازی و پنلِ تنظیماتِ دی‌جی --- */
t( 'کارتِ صفحهٔ اصلی فشرده است', false !== strpos( tara_radio_box( array( 'context' => 'home', 'tracks' => $tracks ) ), 'ty-radio--compact' ) );
t( 'کارتِ آرشیو فشرده نیست', false === strpos( $box_rtl, 'ty-radio--compact' ) );
t( 'کنترل‌های حرفه‌ای داخلِ <details> هستند', false !== strpos( $box_rtl, 'class="ty-radio__adv" data-radio-adv' ) && false !== strpos( $box_rtl, '<summary class="ty-radio__more">' ) );
$home_box = tara_radio_box( array( 'context' => 'home', 'tracks' => $tracks ) );
$page_box = tara_radio_box( array( 'context' => 'page', 'tracks' => $tracks, 'sources' => true ) );
foreach ( array( 'خانه' => $home_box, 'صفحه رادیو' => $page_box, 'آرشیو' => $box_rtl ) as $context => $box ) {
    t( 'پنل دی‌جی بعد از دک و تمام‌عرض: ' . $context, (bool) preg_match( '/class="ty-radio__say"[^>]*><\/p>\s*<\/div>\s*<\/div>\s*<details class="ty-radio__adv"/s', $box ) );
    t( 'کنترل‌های دی‌جی حفظ شده‌اند: ' . $context, false !== strpos($box, 'data-radio-xfade') && false !== strpos($box, 'data-radio-power="warm"') && false !== strpos($box, 'data-radio-shuffle') );
}
t( 'تنظیماتِ دی‌جی در آرشیو بسته است', false === strpos( $box_rtl, 'data-radio-adv open' ) );
t( 'تنظیماتِ دی‌جی در صفحهٔ رادیو باز است', false !== strpos( tara_radio_box( array( 'context' => 'page', 'tracks' => $tracks, 'sources' => true ) ), 'data-radio-adv open' ) );
t( 'فشرده: آمار پنهان می‌شود', false === strpos( tara_radio_box( array( 'context' => 'home', 'tracks' => $tracks ) ), 'ty-radio__meter' ) );
t( 'فشرده: متنِ بلندِ توضیح چاپ نمی‌شود', false === strpos( tara_radio_box( array( 'context' => 'home', 'tracks' => $tracks ) ), 'class="ty-radio__sub"' ) );
t( 'شفاف‌سازی: شافل به پنل رفت', false !== strpos( $box_rtl, 'data-radio-shuffle' ) && false === strpos( preg_replace( '/<details.*?<\/details>/s', '', $box_rtl ), 'data-radio-shuffle' ) );
t( 'کپشنِ معرفی در مارکاپ هست', false !== strpos( $box_rtl, 'data-radio-say' ) );

/* --- نامِ سایت در معرفیِ صوتی --- */
$defaults_390 = tara_radio_defaults();
t( 'وکال: متنِ پیش‌فرض {site} دارد', false !== strpos( (string) $defaults_390['station_text'], '{site}' ) );
t( 'وکال: متنِ «آهنگِ بعدی» هم {site} دارد', false !== strpos( (string) $defaults_390['station_next'], '{site}' ) );
t( 'وکال: JS در نبودِ {site} خودش نام را می‌گذارد', false !== strpos( $js_txt, "line.indexOf('{site}') === -1" ) && false !== strpos( $js_txt, "'رادیو {site}؛ ' + line" ) );
t( 'وکال: نوشتهٔ معرفی روی کارت چاپ می‌شود', false !== strpos( $js_txt, 'paintSay' ) );
t( 'وکال: نُتِ ایستگاه (chime) هست', false !== strpos( $js_txt, 'Radio.prototype.chime' ) && isset( tara_radio_settings()['chime'] ) );
t( 'وکال: حلقهٔ فایلِ خراب بسته شد', false !== strpos( $js_txt, 'voiceBroken' ) );

/* --- کیفیتِ میکس --- */
t( 'میکس: ducking نرم (هدف) دارد', false !== strpos( $js_txt, 'duckTarget' ) );
t( 'میکس: آماده‌سازیِ آهنگِ بعدی زودتر', false !== strpos( $js_txt, 'preloadNext' ) );
t( 'میکس: پیچِ وینیل در هم‌ترازیِ سرعت', false !== strpos( $js_txt, 'preservesPitch' ) );
t( 'میکس: تابعِ چیدمانِ ضدِ تکرار موجود است', function_exists( 'tara_radio_interleave' ) );
$order = tara_radio_interleave( array( 601, 602, 604 ) );
t( 'میکس: خوانندهٔ یکسان پشتِ‌سرِهم نمی‌آید', array( 601, 604, 602 ) === array_map( 'intval', $order ), implode( ',', $order ) );

/* --- بخش ویژه --- */
$GLOBALS['__featured_ids'] = array( 606, 604 );
$GLOBALS['__trans']        = array();
$feat = tara_radio_tracks( array( 'pool' => 'featured', 'limit' => 10 ) );
$feat_ids = array_map( 'intval', wp_list_pluck_simple( array_map( function ( $t ) { return (object) array( 'term_id' => $t['id'] ); }, $feat ) ) );
t( 'ویژه: فقط آهنگ‌های بخش ویژه می‌آیند', ! empty( $feat ) && ! array_diff( $feat_ids, array( 606, 604 ) ) );
$GLOBALS['__featured_ids'] = array();
$GLOBALS['__trans']        = array();
$fallback = tara_radio_tracks( array( 'pool' => 'featured', 'limit' => 10 ) );
t( 'ویژه: بی‌آهنگِ ویژه ⇒ برگشت به همهٔ آهنگ‌ها', count( $fallback ) >= 4 );
$home_html = tara_radio_home_section();
t( 'صفحهٔ اصلی: نشانِ «منتخبِ ویژه»', false !== strpos( $home_html, 'منتخبِ ویژه' ) );
t( 'صفحهٔ اصلی: بخش فشرده است', false !== strpos( $home_html, 'ty-radio--compact' ) );
t( 'صفحهٔ اصلی: فقط یک <h2> (بدونِ سربرگِ تکراری)', 1 === preg_match_all( '/<h2\b/', $home_html ) );
t( 'صفحهٔ اصلی: نشانِ «زنده» در سربرگِ بیرونی', false !== strpos( $home_html, 'ty-radio-home__live' ) );
t( 'صفحهٔ اصلی: کارت برچسبِ دسترس‌پذیری دارد', (bool) preg_match( '/ty-radio--compact[^>]*aria-label="رادیو /s', $home_html ) );
$GLOBALS['__featured_ids'] = array( 606, 604 );

/* --- تنظیمات تازه --- */
$opts_390 = f( 'inc/theme-options.php' );
t( 'تنظیمات: کلید «فقط ویژه در خانه»', false !== strpos( $opts_390, "'id'=>'radio_home_featured'" ) );
t( 'تنظیمات: کلید «نوای ایستگاه»', false !== strpos( $opts_390, "'id'=>'radio_chime'" ) );
t( 'تنظیمات: پیش‌فرضِ نوای ایستگاه روشن', 'on' === tara_radio_defaults()['chime'] );
t( 'تنظیمات: پیش‌فرضِ «فقط ویژه» روشن', 'on' === tara_radio_defaults()['home_featured'] );

/* --- ردیفِ آهنگ با دکمهٔ رادیو --- */
t( 'ردیف: کلاس چیدمانِ شش‌ستونه', false !== strpos( f( 'inc/tara-ui.php' ), "' ty-track--radio'" ) && false !== strpos( $css_radio, '.ty-track--radio' ) );

/* ======================================================================
   ۷) دارایی‌ها
   ====================================================================== */
$GLOBALS['__styles']    = array();
$GLOBALS['__scripts']   = array();
$GLOBALS['__localized'] = array();
$GLOBALS['__is_tax']    = array( 'tara_genre' );
tara_radio_assets();
t( 'دارایی: CSS رادیو در آرشیو ژانر', isset( $GLOBALS['__styles']['taranehyar-radio'] ) );
t( 'دارایی: JS رادیو در آرشیو ژانر', isset( $GLOBALS['__scripts']['taranehyar-radio'] ) );
t( 'دارایی: JS در پانوشت بار می‌شود', ! empty( $GLOBALS['__scripts']['taranehyar-radio'][3] ) );
t( 'دارایی: پیکربندی تزریق شد', isset( $GLOBALS['__localized']['taranehyar-radio']['TaraRadio']['config'] ) );
t( 'دارایی: nonce و آدرس AJAX', ! empty( $GLOBALS['__localized']['taranehyar-radio']['TaraRadio']['nonce'] ) && false !== strpos( $GLOBALS['__localized']['taranehyar-radio']['TaraRadio']['api'], 'admin-ajax.php' ) );

$GLOBALS['__styles']  = array();
$GLOBALS['__scripts'] = array();
$GLOBALS['__is_tax']  = array( 'tara_mood' );
$GLOBALS['__page_template'] = 'page-radio.php';
tara_radio_assets();
t( 'دارایی: قالبِ صفحهٔ رادیو هم دارایی می‌گیرد', isset( $GLOBALS['__scripts']['taranehyar-radio'] ) );

$GLOBALS['__styles']        = array();
$GLOBALS['__scripts']       = array();
$GLOBALS['__is_tax']        = array();
$GLOBALS['__page_template'] = '';
$GLOBALS['__tara_home']     = false;
tara_radio_assets();
t( 'دارایی: صفحهٔ بی‌ربط چیزی نمی‌گیرد', ! isset( $GLOBALS['__scripts']['taranehyar-radio'] ) );

$GLOBALS['__options']['option_tree']['radio_on'] = 'off';
$GLOBALS['__is_tax'] = array( 'tara_genre' );
tara_radio_assets();
t( 'دارایی: رادیو خاموش ⇒ هیچ فایلی', ! isset( $GLOBALS['__scripts']['taranehyar-radio'] ) );
unset( $GLOBALS['__options']['option_tree']['radio_on'] );
$GLOBALS['__is_tax'] = array();

/* ======================================================================
   ۸) CSS و JS
   ====================================================================== */
$css = f( 'css/tara-radio.css' );
t( 'CSS: کارتِ رادیو', false !== strpos( $css, '.ty-radio {' ) );
t( 'CSS: صف و ردیف‌ها', false !== strpos( $css, '.ty-radio__queue' ) && false !== strpos( $css, '.ty-radio__row' ) );
t( 'CSS: صدا سنج', false !== strpos( $css, '.ty-radio__vu i' ) );
t( 'CSS: حالتِ پخش', false !== strpos( $css, '.ty-radio.is-playing' ) );
t( 'CSS: واکنش‌گرایی ۶۴۰px', false !== strpos( $css, '@media (max-width: 640px)' ) );
t( 'CSS: کم‌کردن حرکت', false !== strpos( $css, 'prefers-reduced-motion' ) );
t( 'CSS: دکمهٔ افزودن', false !== strpos( $css, '.ty-radio-add' ) );
t( 'CSS: صفحهٔ رادیو', false !== strpos( $css, '.ty-radio-page__head' ) );
t( 'CSS: پیامِ شناور', false !== strpos( $css, '.ty-radio-global-toast' ) );
t( 'CSS: هیچ رنگِ ناقصی نمانده', false === strpos( $css, 'striped' ) );

$taxcss = f( 'css/tara-tax.css' );
t( 'CSS ترم: سربرگ', false !== strpos( $taxcss, '.ty-taxhero' ) );
t( 'CSS ترم: چیپ‌ها', false !== strpos( $taxcss, '.ty-taxchip' ) );
t( 'CSS ترم: حالت شب', false !== strpos( $taxcss, 'body.night .ty-taxhero' ) );
t( 'CSS ترم: واکنش‌گرایی', false !== strpos( $taxcss, '@media (max-width: 760px)' ) );

$js = f( 'js/tara-radio.js' );
t( 'JS: بدونِ jQuery', false === strpos( $js, 'jQuery(' ) && false === strpos( $js, '$(document)' ) && false === strpos( $js, 'wp-emoji' ) );
t( 'JS: دو دِکِ صوتی', false !== strpos( $js, 'new window.Audio()' ) && false !== strpos( $js, 'decks' ) );
t( 'JS: کراس‌فیدِ برابر-توان', false !== strpos( $js, 'Math.sin' ) && false !== strpos( $js, 'Math.cos' ) );
t( 'JS: هم‌ترازیِ سرعت', false !== strpos( $js, 'playbackRate' ) );
t( 'JS: گرافِ Web Audio فقط با crossOrigin', false !== strpos( $js, "crossOrigin = 'anonymous'" ) );
t( 'JS: بازگشت به پخشِ ساده در خطا', false !== strpos( $js, 'destroyGraph' ) );
t( 'JS: وکال با گفتارِ مرورگر', false !== strpos( $js, 'speechSynthesis' ) );
t( 'JS: کم‌کردنِ موسیقی هنگامِ وکال', false !== strpos( $js, 'duckTo' ) );
t( 'JS: صفِ خودتجدیدشونده با AJAX', false !== strpos( $js, 'action=tara_radio' ) && false !== strpos( $js, 'exclude=' ) );
t( 'JS: فهرستِ شخصی در localStorage', false !== strpos( $js, 'tara:radio:mine' ) );
t( 'JS: کنترلِ رسانهٔ سیستم', false !== strpos( $js, 'mediaSession' ) );
t( 'JS: توقفِ پلیرهای دیگر', false !== strpos( $js, 'pauseOthers' ) );
t( 'JS: فایل بدونِ بلاکِ خروجیِ آخر', false === strpos( $js, 'document.write' ) );

/* ======================================================================
   ۹) هم‌خوانی با نسخه
   ====================================================================== */
t( 'نسخه: style.css روی ۳.۹.۰', (bool) preg_match( '/Version: 3\.(9|10|11)\.\d+/', f( 'style.css' ) ) );
t( 'نسخه: fallback در functions.php', (bool) preg_match( "/: '3\.(9|10|11)\.\d+' \\)/", $func ) );

/* ======================================================================
   نتیجه
   ====================================================================== */
echo "\nRESULT pass={$PASS} fail={$FAIL}\n";
if ( $FAIL ) {
	echo "FAILED:\n";
	foreach ( $FAILS as $one ) {
		echo ' - ' . $one . "\n";
	}
}

