<?php
/**
 * آزمونِ نسخهٔ ۳.۷.۰ — تاکسونومی‌های موسیقی، پلیر «رادیو»، صفحهٔ هنرمند و پوستهٔ تنظیمات
 * اجرا:  node tools/php-test.mjs tests/run-370.php
 *
 * چه چیزی سنجیده می‌شود؟
 *   • پنج تاکسونومی تازه با نامک/برچسب/سلسله‌مراتب درست و slug آرشیو درست
 *   • توابع داده‌ای آهنگ (src/title/artist/cover/playlist) با fallbackها
 *   • مارکاپ ردیف آهنگ و چیپ‌ها + escape شدن نام‌ها
 *   • مارکاپ پلیر: JSON معتبر، فهرست پخش، نوار چسبان، اعداد فارسی
 *   • صفحهٔ تنظیمات: شناسهٔ صفحه، کلاس body، بارگذاری دارایی فقط در همان صفحه
 *   • متن «دربارهٔ سایت» در فهرست تنظیمات (برای پنل و پانوشت)
 *   • نبودِ کد/مارکاپ پلیر قدیمی و نبودِ نام برند قبلی در قالب
 *
 * آزموده نمی‌شود: رندر مرورگر، پخش واقعی صدا، MySQL.
 */

require __DIR__ . '/stubs-wp.php';

define( 'TARANEHYAR_VERSION', '3.7.0' );
define( 'TARANEHYAR_DIR', '/tmp/theme' );
define( 'TARANEHYAR_URI', 'https://ex/theme' );
define( 'ABSPATH', '/tmp/wp/' );

$GLOBALS['__options'] = array();
$GLOBALS['__postmeta'] = array();
$GLOBALS['__posts']    = array();

/* ---------- هستهٔ قالب برای ot_get_option / tara_number_fa ---------- */
if ( ! function_exists( 'tara_number_fa' ) ) {
	function tara_number_fa( $number ) {
		return strtr( (string) $number, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
	}
}
if ( ! function_exists( 'tara_options_id' ) ) {
	function tara_options_id() {
		return 'option_tree';
	}
}
if ( ! function_exists( 'ot_get_option' ) ) {
	function ot_get_option( $key, $default = '' ) {
		$opts = isset( $GLOBALS['__options']['option_tree'] ) ? $GLOBALS['__options']['option_tree'] : array();
		if ( is_array( $opts ) && isset( $opts[ $key ] ) && '' !== $opts[ $key ] ) {
			return $opts[ $key ];
		}
		return $default;
	}
}

require '/tmp/theme/inc/tara-taxonomies.php';
require '/tmp/theme/inc/tara-ui.php';

$PASS = 0;
$FAIL = 0;
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

/* ======================================================================
   ۱) تاکسونومی‌ها
   ====================================================================== */
t( 'تاکسونومی: پنج مورد تعریف شده', 5 === count( tara_music_taxonomies() ), (string) count( tara_music_taxonomies() ) );

tara_register_music_taxonomies();

$taxes = $GLOBALS['__taxonomies'];
foreach ( array( 'tara_genre', 'tara_mood', 'tara_songwriter', 'tara_composer', 'tara_arranger' ) as $name ) {
	t( "تاکسونومی: $name ثبت شد", isset( $taxes[ $name ] ) );
}

t( 'تاکسونومی: ژانر درختی است', ! empty( $taxes['tara_genre']->args['hierarchical'] ) );
t( 'تاکسونومی: حال‌وهوا غیردرختی است', empty( $taxes['tara_mood']->args['hierarchical'] ) );
t( 'تاکسونومی: آدرس ژانر = genre', 'genre' === $taxes['tara_genre']->args['rewrite']['slug'] );
t( 'تاکسونومی: آدرس حال‌وهوا = mood', 'mood' === $taxes['tara_mood']->args['rewrite']['slug'] );
t( 'تاکسونومی: آدرس ترانه‌سرا = songwriter', 'songwriter' === $taxes['tara_songwriter']->args['rewrite']['slug'] );
t( 'تاکسونومی: آدرس آهنگساز = composer', 'composer' === $taxes['tara_composer']->args['rewrite']['slug'] );
t( 'تاکسونومی: آدرس تنظیم = arranger', 'arranger' === $taxes['tara_arranger']->args['rewrite']['slug'] );
t( 'تاکسونومی: REST base همان نامک انگلیسی', 'songwriter' === $taxes['tara_songwriter']->args['rest_base'] );
t( 'تاکسونومی: مهر بازسازی پیوند یکتا ثبت شد', function_exists( 'tara_music_taxonomies_stamp' ) );

/* مهر: بار اول بازسازی می‌کند، بار دوم نه */
unset( $GLOBALS['__options']['taranehyar_music_rewrite_stamp'] );
$GLOBALS['__flushed'] = 0;
tara_music_taxonomies_stamp();
t( 'تاکسونومی: بار اول مهر، قوانین بازسازی شد', 1 === $GLOBALS['__flushed'], (string) $GLOBALS['__flushed'] );
tara_music_taxonomies_stamp();
t( 'تاکسونومی: بار دوم مهر، دیگر بازسازی نمی‌کند', 1 === $GLOBALS['__flushed'], (string) $GLOBALS['__flushed'] );
$GLOBALS['__options']['taranehyar_music_rewrite_stamp'] = 'قدیمی';
tara_music_taxonomies_stamp();
t( 'تاکسونومی: مهر عوض‌شده دوباره بازسازی می‌کند', 2 === $GLOBALS['__flushed'], (string) $GLOBALS['__flushed'] );
t( 'تاکسونومی: در REST دیده می‌شوند', ! empty( $taxes['tara_genre']->args['show_in_rest'] ) );
t( 'تاکسونومی: ستون پیشخوان دارد', ! empty( $taxes['tara_mood']->args['show_admin_column'] ) );
t( 'تاکسونومی: برچسب مفرد فارسی', 'ژانر' === tara_music_taxonomy_label( 'tara_genre' ) );
t( 'تاکسونومی: برچسب جمع فارسی', 'ترانه‌سراها' === tara_music_taxonomy_label( 'tara_songwriter', 'plural' ) );
t( 'تاکسونومی: فهرست نامک‌ها', array( 'tara_genre', 'tara_mood', 'tara_songwriter', 'tara_composer', 'tara_arranger' ) === tara_music_taxonomy_ids() );

/* ثبت دوباره نباید خطا بدهد (idempotent) */
tara_register_music_taxonomies();
t( 'تاکسونومی: ثبت دوباره بی‌خطر است', true );

/* ======================================================================
   ۲) داده‌های آهنگ
   ====================================================================== */
$GLOBALS['__posts'][501] = new WP_Post( 501, 'آهنگ یک' );
$GLOBALS['__posts'][502] = new WP_Post( 502, 'آهنگ دو' );
$GLOBALS['__posts'][503] = new WP_Post( 503, 'بدون فایل' );

$GLOBALS['__postmeta'][501] = array( 'song' => 'شب', 'artist' => 'هنرمند الف', 'music320' => 'https://ex/501-320.mp3' );
$GLOBALS['__postmeta'][502] = array( 'song' => 'روز', 'artist' => 'هنرمند ب', 'music128' => 'https://ex/502-128.mp3', 'views' => 10 );
$GLOBALS['__postmeta'][503] = array( 'song' => 'بی‌صدا' );
$GLOBALS['__thumb'][501]      = 'https://ex/501.jpg';

t( 'داده: ۳۲۰ ترجیح دارد', 'https://ex/501-320.mp3' === tara_track_src( 501 ) );
t( 'داده: اگر ۳۲۰ نبود، ۱۲۸', 'https://ex/502-128.mp3' === tara_track_src( 502 ) );
t( 'داده: بدون فایل، رشتهٔ خالی', '' === tara_track_src( 503 ) );
t( 'داده: نام آهنگ از فیلد song', 'شب' === tara_track_title( 501 ) );
t( 'داده: هنرمند از فیلد artist', 'هنرمند الف' === tara_track_artist( 501 ) );
t( 'داده: کاور سفارشی', 'https://ex/501.jpg' === tara_track_cover( 501 ) );
t( 'داده: کاور پیش‌فرض', false !== strpos( tara_track_cover( 502 ), 'nomusics.jpg' ) );

$playlist = tara_track_playlist( array( 501, 502, 503 ) );
t( 'داده: فهرست پخش فقط آهنگ‌های دارای فایل', 2 === count( $playlist ), (string) count( $playlist ) );
t( 'داده: ساختار فهرست پخش کامل است', isset( $playlist[0]['src'], $playlist[0]['title'], $playlist[0]['artist'], $playlist[0]['cover'], $playlist[0]['link'] ) );
t( 'داده: شناسهٔ آهنگ حفظ شده', 501 === (int) $playlist[0]['id'] );

/* ======================================================================
   ۳) مارکاپ ردیف و چیپ
   ====================================================================== */
$row = tara_track_row( 501, array( 'index' => 0, 'badge' => 'آهنگ' ) );
t( 'ردیف: کلاس پایه', false !== strpos( $row, 'class="ty-track"' ) );
t( 'ردیف: دکمهٔ پخش با ایندکس', false !== strpos( $row, 'data-tara-play="0"' ) );
t( 'ردیف: عنوان و هنرمند', false !== strpos( $row, 'شب' ) && false !== strpos( $row, 'هنرمند الف' ) );
t( 'ردیف: نشان نوع', false !== strpos( $row, 'ty-track__badge' ) );
t( 'ردیف: لینک به نوشته', false !== strpos( $row, 'href="https://ex/' ) || false !== strpos( $row, '<a class="ty-track__title"' ) );

$row_no_audio = tara_track_row( 503 );
t( 'ردیف: آهنگ بدون فایل، دکمهٔ پخش ندارد', false === strpos( $row_no_audio, 'data-tara-play' ) );
t( 'ردیف: آهنگ بدون فایل، آیکون نت دارد', false !== strpos( $row_no_audio, 'ty-track__play--none' ) );

$term            = new stdClass();
$term->term_id   = 7;
$term->name      = 'پاپ <script>';
$term->slug      = 'pop';
$term->taxonomy  = 'tara_genre';
$GLOBALS['__postterms'][501]['tara_genre'] = array( $term );
$chips = tara_term_chips( 'tara_genre', 501, 'ژانر' );
t( 'چیپ: لینک ترم ساخته شد', false !== strpos( $chips, 'https://ex/tara_genre/pop/' ) );
t( 'چیپ: نام خطرناک escape شد', false === strpos( $chips, '<script>' ) && false !== strpos( $chips, '&lt;script&gt;' ) );
t( 'چیپ: برچسب فارسی', false !== strpos( $chips, 'ژانر' ) );
t( 'چیپ: تاکسونومی بدون ترم خروجی ندارد', '' === tara_term_chips( 'tara_mood', 501 ) );

/* ======================================================================
   ۴) پلیر
   ====================================================================== */
$html = tara_player( array( 'tracks' => $playlist, 'title' => 'رادیو تست', 'context' => 'artist' ) );
t( 'پلیر: کارت ساخته شد', false !== strpos( $html, 'data-tara-player="artist"' ) );
t( 'پلیر: عنوان کارت', false !== strpos( $html, 'رادیو تست' ) );
t( 'پلیر: element صوتی', false !== strpos( $html, 'data-tara-audio' ) );

preg_match( '#<script type="application/json" class="ty-player__data">(.*?)</script>#s', $html, $m );
$data_ok = isset( $m[1] ) && is_array( json_decode( $m[1], true ) ) && 2 === count( json_decode( $m[1], true ) );
t( 'پلیر: JSON فهرست پخش معتبر است', $data_ok );
t( 'پلیر: کنتر‌های اصلی', false !== strpos( $html, 'data-tara-toggle' ) && false !== strpos( $html, 'data-tara-seek' ) && false !== strpos( $html, 'data-tara-volume' ) );
t( 'پلیر: فهرست پخش', 2 === substr_count( $html, 'data-tara-play=' ), (string) substr_count( $html, 'data-tara-play=' ) );
t( 'پلیر: نوار چسبان', false !== strpos( $html, 'data-tara-dock' ) && false !== strpos( $html, 'hidden' ) );
t( 'پلیر: اعداد فارسی در شمارش', false !== strpos( $html, '۲ قطعه' ) );

/* دو پلیر در یک صفحه: نوار چسبان نباید دوباره رندر شود */
$second = tara_player( array( 'tracks' => $playlist, 'title' => 'دوم', 'context' => 'archive' ) );
t( 'پلیر: پلیر دوم هم کامل ساخته می‌شود', false !== strpos( $second, 'data-tara-player="archive"' ) );
t( 'پلیر: نوار چسبان فقط یک‌بار در صفحه',
	1 === substr_count( $html, 'class="ty-dock"' ) && 0 === substr_count( $second, 'class="ty-dock"' ),
	substr_count( $html, 'class="ty-dock"' ) . '/' . substr_count( $second, 'class="ty-dock"' ) );
t( 'پلیر: RTL', false !== strpos( $html, 'dir="rtl"' ) );
t( 'پلیر: بدون فهرست خالی', '' === tara_player( array( 'tracks' => array() ) ) );

/* ======================================================================
   ۵) پوستهٔ صفحهٔ تنظیمات
   ====================================================================== */
require '/tmp/theme/inc/admin-settings-shell.php';

t( 'تنظیمات: شناسهٔ صفحهٔ درست', 'taranehyar_page_ot-theme-options' === tara_settings_shell_screen() );
t( 'تنظیمات: پوسته به‌طور پیش‌فرض روشن است', true === tara_settings_shell_on() );

$GLOBALS['__options']['option_tree'] = array( 'ab_admin_ui' => 'off' );
t( 'تنظیمات: با ab_admin_ui=off خاموش می‌شود', false === tara_settings_shell_on() );
$GLOBALS['__options']['option_tree'] = array( 'ab_admin_ui' => 'on' );

$GLOBALS['__screen_id'] = 'taranehyar_page_ot-theme-options';
$classes                = apply_filters( 'admin_body_class', array() );
t( 'تنظیمات: کلاس body روی همین صفحه', in_array( 'tara-settings-screen', $classes, true ) );

$GLOBALS['__screen_id'] = 'edit.php';
t( 'تنظیمات: کلاس body روی صفحهٔ دیگر نه', array() === apply_filters( 'admin_body_class', array() ) );

$GLOBALS['__styles']  = array();
$GLOBALS['__scripts'] = array();
tara_settings_shell_assets( 'taranehyar_page_ot-theme-options' );
t( 'تنظیمات: CSS پوسته بار شد', isset( $GLOBALS['__styles']['taranehyar-settings'] ) );
t( 'تنظیمات: JS پوسته بار شد', isset( $GLOBALS['__scripts']['taranehyar-settings'] ) );

$GLOBALS['__styles']  = array();
$GLOBALS['__scripts'] = array();
tara_settings_shell_assets( 'edit.php' );
t( 'تنظیمات: در صفحهٔ دیگر CSS بار نمی‌شود', array() === $GLOBALS['__styles'] );
t( 'تنظیمات: در صفحهٔ دیگر JS بار نمی‌شود', array() === $GLOBALS['__scripts'] );

tara_settings_shell_assets( 'taranehyar_page_ot-theme-options' );
t( 'تنظیمات: دوباره روی صفحهٔ درست بار می‌شود', isset( $GLOBALS['__styles']['taranehyar-settings'] ) );
t( 'تنظیمات: پیکربندی JS به صفحه تزریق شد', isset( $GLOBALS['__localized']['taranehyar-settings']['taraSettings'] ) );

t( 'تنظیمات: فایل CSS موجود', is_readable( '/tmp/theme/css/admin-settings.css' ) );
t( 'تنظیمات: فایل JS موجود', is_readable( '/tmp/theme/js/admin-settings.js' ) );
t( 'تنظیمات: CSS فقط در body.tara-settings-screen', false !== strpos( (string) @file_get_contents( '/tmp/theme/css/admin-settings.css' ), 'body.tara-settings-screen' ) );

/* ======================================================================
   ۶) تنظیمات «دربارهٔ سایت»
   ====================================================================== */
$options_src = (string) @file_get_contents( '/tmp/theme/inc/theme-options.php' );
t( 'درباره: بخش about ثبت شده', false !== strpos( $options_src, "'id'=>'about'" ) );
t( 'درباره: فیلد متن', false !== strpos( $options_src, "'id'=>'about_site_text'" ) );
t( 'درباره: فیلد تیتر', false !== strpos( $options_src, "'id'=>'about_site_title'" ) );
t( 'درباره: کلید نمایش در پانوشت', false !== strpos( $options_src, "'id'=>'about_show_footer'" ) );
t( 'درباره: کلید نمایش در صفحهٔ هنرمند', false !== strpos( $options_src, "'id'=>'about_show_artist'" ) );

/* ======================================================================
   ۷) پاکی برند و نبود پلیر قدیمی
   ====================================================================== */
/* فقط فایل‌های سوارشده روی FS آزمون (php-test.mjs) اسکن می‌شوند. */
$theme_files = array_merge(
	(array) glob( '/tmp/theme/*.php' ),
	(array) glob( '/tmp/theme/inc/*.php' ),
	(array) glob( '/tmp/theme/css/*.css' ),
	(array) glob( '/tmp/theme/js/*.js' )
);

$brand_hits  = array();
$legacy_hits = array();
foreach ( $theme_files as $path ) {
	$src = (string) @file_get_contents( $path );
	if ( preg_match( '/جوان‌صدا|جوان صدا|جوانصدا|kianoosh|JAVANSEDA|javan_seda/u', $src ) ) {
		$brand_hits[] = str_replace( '/tmp/theme/', '', $path );
	}
	if ( false !== strpos( $src, 'music_box_player taktarcks' ) || false !== strpos( $src, 'audioPlayer = function' ) ) {
		$legacy_hits[] = str_replace( '/tmp/theme/', '', $path );
	}
}

t( 'برند: هیچ ردی از نام قبلی در فایل‌های قالب نیست', empty( $brand_hits ), implode( ', ', $brand_hits ) );
t( 'پلیر: هیچ ردی از پلیر قدیمی نمانده', empty( $legacy_hits ), implode( ', ', $legacy_hits ) );

$single = (string) @file_get_contents( '/tmp/theme/single.php' );
t( 'صفحهٔ آهنگ: از پلیر تازه استفاده می‌کند', false !== strpos( $single, 'tara_player(' ) );
t( 'صفحهٔ آهنگ: نوار شناسه (ژانر/ترانه‌سرا) دارد', false !== strpos( $single, 'tara_term_chips' ) );

$scripts = (string) @file_get_contents( '/tmp/theme/js/scripts.js' );
t( 'اسلایدر: تک‌اسلاید شده', false !== strpos( $scripts, "0:{items:1}" ) && false === strpos( $scripts, '700:{items:2}' ) );

t( 'تاکسونومی: قالب آرشیو برای ژانر موجود', is_readable( '/tmp/theme/taxonomy-tara_genre.php' ) );
t( 'تاکسونومی: قالب مشترک آرشیو موجود', is_readable( '/tmp/theme/inc/tax-listing.php' ) );

/* ======================================================================
   خلاصه
   ====================================================================== */
echo "\n";
echo 'RESULT pass=' . $PASS . ' fail=' . $FAIL . "\n";
if ( $FAIL ) {
	echo "FAILED:\n";
	foreach ( $FAILS as $f ) {
		echo ' - ' . $f . "\n";
	}
}
exit( $FAIL ? 1 : 0 );
