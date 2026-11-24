<?php
/**
 * آزمونِ ۳.۱۷.۱۴ — «صفحهٔ دسته مثل ژانر»
 * ==========================================================================
 * بررسی می‌کند:
 *   ۱) tara_tax_is_music('category') → true   (وگرنه هیرو رندر نمی‌شود)
 *   ۲) tara_music_taxonomy_label('category', 'singular') → «دسته» (نه «category»)
 *   ۳) tara_music_taxonomy_label('category', 'plural')   → «دسته‌ها»
 *   ۴) ژانر و حال‌وهوا هنوز true هستند (رگرسیون)
 *   ۵) tag و تاکسونومی جعلی هنوز false هستند
 *   ۶) توضیحِ کوتاهِ دسته در tax-listing خوانده می‌شود
 *   ۷) توضیحِ کاملِ دسته در tara_tax_about چاپ می‌شود
 *
 * اجرا:  node tools/php-test.mjs tests/run-31714.php
 */

require __DIR__ . '/stubs-wp.php';

if ( ! defined( 'TARANEHYAR_VERSION' ) ) {
	define( 'TARANEHYAR_VERSION', '3.17.14' );
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
if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 604800 );
}
if ( ! defined( 'MONTH_IN_SECONDS' ) ) {
	define( 'MONTH_IN_SECONDS', 2592000 );
}
if ( ! defined( 'YEAR_IN_SECONDS' ) ) {
	define( 'YEAR_IN_SECONDS', 31536000 );
}

$GLOBALS['__options']   = array( 'option_tree' => array() );
$GLOBALS['__trans']     = array();
$GLOBALS['__postmeta']  = array();
$GLOBALS['__posts']     = array();
$GLOBALS['__terms']     = array();
$GLOBALS['__postterms'] = array();
$GLOBALS['__termmeta'] = array();

/* بارگذاریِ ماژول‌های قالب */
require '/tmp/theme/inc/tara-taxonomies.php';
require '/tmp/theme/inc/ab-cat-desc.php';
require '/tmp/theme/inc/tara-tax-hero.php';

$GLOBALS['__fail'] = 0;
$GLOBALS['__pass'] = 0;

function t( $label, $got, $want ) {
	$ok = ( $got === $want );
	if ( $ok ) {
		$GLOBALS['__pass']++;
		echo "  ✓ {$label}\n";
	} else {
		$GLOBALS['__fail']++;
		echo "  ✗ {$label}\n      انتظار: " . var_export( $want, true ) . "\n      دریافت: " . var_export( $got, true ) . "\n";
	}
}

function ttrue( $label, $cond ) {
	t( $label, (bool) $cond, true );
}

echo "=== ۱) تاکسونومی‌های شناخته‌شده ===\n";
ttrue( "tara_tax_is_music('category')", tara_tax_is_music( 'category' ) );
ttrue( "tara_tax_is_music('tara_genre')", tara_tax_is_music( 'tara_genre' ) );
ttrue( "tara_tax_is_music('tara_mood')", tara_tax_is_music( 'tara_mood' ) );
t( "tara_tax_is_music('post_tag')", tara_tax_is_music( 'post_tag' ), false );
t( "tara_tax_is_music('tara_songwriter')", tara_tax_is_music( 'tara_songwriter' ), false );
t( "tara_tax_is_music('')", tara_tax_is_music( '' ), false );

echo "\n=== ۲) برچسب‌های فارسی ===\n";
t( "label('category','singular')", tara_music_taxonomy_label( 'category', 'singular' ), 'دسته' );
t( "label('category','plural')", tara_music_taxonomy_label( 'category', 'plural' ), 'دسته‌ها' );
t( "label('tara_genre','singular')", tara_music_taxonomy_label( 'tara_genre', 'singular' ), 'ژانر' );
t( "label('tara_mood','singular')", tara_music_taxonomy_label( 'tara_mood', 'singular' ), 'حال‌وهوا' );

echo "\n=== ۳) توضیح کوتاه دسته ===\n";
/* شبیه‌سازی: ترم دسته با متای ab_desc_short */
$GLOBALS['__termmeta'] = array();
$GLOBALS['__terms']     = array();

$cat = new WP_Term( 21, 'آهنگ‌های عاشقانه', 'category', 'romantic', 42 );
$GLOBALS['__terms'][21] = $cat;

$GLOBALS['__termmeta'][21]['ab_desc_short'] = 'گلچینِ آهنگ‌های عاشقانهٔ فارسی.';
$GLOBALS['__termmeta'][21]['ab_desc_full']  = '<p>این دسته شامل <strong>بهترین</strong> آهنگ‌های عاشقانه است.</p>';

t(
	"tara_category_desc_short_text",
	tara_category_desc_short_text( $cat ),
	'گلچینِ آهنگ‌های عاشقانهٔ فارسی.'
);

$short_html = tara_category_desc_short( $cat );
ttrue( "tara_category_desc_short شاملِ متن", false !== strpos( $short_html, 'گلچینِ آهنگ‌های عاشقانهٔ فارسی' ) );
ttrue( "tara_category_desc_short کلاس archive_txt_box دارد", false !== strpos( $short_html, 'archive_txt_box' ) );

echo "\n=== ۴) توضیح کامل دسته (باید حفظ شود) ===\n";
$full_html = tara_category_desc_full_html( $cat );
ttrue( "tara_category_desc_full_html خالی نیست", '' !== $full_html );
ttrue( "شامل strong", false !== strpos( $full_html, '<strong>' ) );
ttrue( "شامل متن", false !== strpos( $full_html, 'بهترین' ) );

/* --- stub برای عبورِ tara_tax_hero از مسیرِ داده ---
   هیرو آمار را از get_posts/is_wp_error می‌خواند؛ این‌ها در stubs-wp
   نیستند، پس برای این بخشِ آزمون ساده‌شان می‌کنیم. */
if ( ! function_exists( 'get_posts' ) ) {
	function get_posts( $args = array() ) {
		return array( 601, 602, 604 );
	}
}
if ( ! function_exists( 'tara_tax_post_terms' ) ) {
	function tara_tax_post_terms( $ids, $tax = '' ) {
		return array();
	}
}
if ( ! function_exists( 'get_terms' ) ) {
	function get_terms( $args = array() ) {
		return array();
	}
}
if ( ! function_exists( 'get_the_ID' ) ) {
	function get_the_ID() { return 0; }
}
if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $id = 0 ) { return 'https://ex/p/' . (int) $id; }
}
if ( ! function_exists( 'get_term_link' ) ) {
	function get_term_link( $term = null, $tax = '' ) {
		$t = $term instanceof WP_Term ? $term : null;
		return $t ? 'https://ex/t/' . $t->term_id : 'https://ex/t/0';
	}
}
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) { return false; }
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $val, $ttl = 0 ) { return true; }
}
if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $key ) { return true; }
}

echo "\n=== ۵) هیرو برای دسته ساخته می‌شود ===\n";
ttrue( "function_exists tara_tax_hero", function_exists( 'tara_tax_hero' ) );
$hero = tara_tax_hero( $cat, 'گلچینِ آهنگ‌های عاشقانهٔ فارسی.' );
ttrue( "هیرو خالی نیست", '' !== trim( (string) $hero ) );
ttrue( "هیرو کلاس ty-taxhero دارد", false !== strpos( (string) $hero, 'ty-taxhero' ) );
ttrue( "هیرو نام دسته را دارد", false !== strpos( (string) $hero, 'آهنگ‌های عاشقانه' ) );
ttrue( "هیرو آیکون پوشه دارد", false !== strpos( (string) $hero, 'icofont-folder-open' ) );
/* نمایشِ eyebrow برای عیب‌یابی + بررسیِ درست */
if ( preg_match( '/ty-taxhero__eyebrow"(.*?)<\/span>/s', (string) $hero, $m_eb ) ) {
	$eb_txt = trim( preg_replace( '/^[\s>]+/', '', preg_replace( '/\s+/', ' ', wp_strip_all_tags( $m_eb[1] ) ) ) );
} else {
	$eb_txt = '';
}
ttrue( "ایبرو برچسب «دسته» دارد (متن: " . $eb_txt . ")", 'دسته' === $eb_txt );
ttrue( "هیرو «category» انگلیسی چاپ نمی‌کند", false === strpos( (string) $hero, '>category<' ) );

echo "\n=== ۶) بخش «دربارهٔ» برای دسته متن کامل را می‌آورد ===\n";
ttrue( "function_exists tara_tax_about", function_exists( 'tara_tax_about' ) );
$about = tara_tax_about( $cat );
ttrue( "درباره خالی نیست", '' !== trim( (string) $about ) );
ttrue( "درباره کلاس ty-taxabout__custom دارد", false !== strpos( (string) $about, 'ty-taxabout__custom' ) );
ttrue( "درباره متن کامل را دارد", false !== strpos( (string) $about, 'بهترین' ) );
ttrue( "درباره strong را حفظ کرده", false !== strpos( (string) $about, '<strong>' ) );

echo "\n=== ۷) ژانر هم متن کامل نمی‌گیرد (رگرسیون) ===\n";
$genre = new WP_Term( 31, 'پاپ', 'tara_genre', 'pop', 10 );
$GLOBALS['__terms'][31]  = $genre;

$about_genre = tara_tax_about( $genre );
ttrue(
	"دربارهٔ ژانر کلاس custom ندارد",
	false === strpos( (string) $about_genre, 'ty-taxabout__custom' )
);

echo "\n";
echo str_repeat( '─', 60 ) . "\n";
printf( "نتیجه: %d موفق، %d ناموفق\n", $GLOBALS['__pass'], $GLOBALS['__fail'] );
exit( $GLOBALS['__fail'] > 0 ? 1 : 0 );
