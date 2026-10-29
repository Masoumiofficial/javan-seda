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

define( 'TARANEHYAR_VERSION', '3.7.3' );
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
require '/tmp/theme/inc/tara-critical.php';

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
t( 'پلیر: فهرست پخش داخل کارت نیست', false === strpos( $html, 'ty-player__playlist' ) );
t( 'پلیر: دکمهٔ بازکردن پاپ‌آپ فهرست', false !== strpos( $html, 'data-tara-list' ) && false !== strpos( $html, 'aria-controls="tara-playlist-modal"' ) );
t( 'پلیر: پاپ‌آپ فهرست یک‌بار در صفحه', 1 === substr_count( $html, 'id="tara-playlist-modal"' ) );
t( 'پلیر: پیام خطای صوتی (hidden) داریم', false !== strpos( $html, 'data-tara-error' ) );
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

/* ---------- صف بخش‌ها، دکمهٔ کارت و ردیف فهرست (v3.7.3) ---------- */
$qattrs = tara_queue_attrs( $playlist, 'بخش تست' );
t( 'صف: data-tara-queue ساخته شد', false !== strpos( $qattrs, 'data-tara-queue=' ) && false !== strpos( $qattrs, '&quot;' ) );
t( 'صف: عنوان بخش همراه صف است', false !== strpos( $qattrs, 'data-tara-title="بخش تست"' ) );
t( 'صف: فهرست خالی، صفر خروجی', '' === tara_queue_attrs( array(), 'x' ) );
preg_match( '/data-tara-queue="([^"]+)"/', $qattrs, $qm );
$decoded = isset( $qm[1] ) ? json_decode( html_entity_decode( $qm[1], ENT_QUOTES, 'UTF-8' ), true ) : null;
t( 'صف: JSON قابل‌خواندن و شامل شناسه', is_array( $decoded ) && 2 === count( $decoded ) && 501 === (int) $decoded[0]['id'] );

$cardplay = tara_card_play( 501 );
t( 'کارت: دکمهٔ پخش با شناسهٔ نوشته', false !== strpos( $cardplay, 'data-tara-track="501"' ) );
t( 'کارت: آرایهٔ صوتی، دکمه', '' === tara_card_play( 503 ) );

$list = tara_track_list( array( 501, 502, 503 ), array( 'title' => 'فهرست تست' ) );
t( 'فهرست: صف روی خودِ ul', false !== strpos( $list, '<ul class="ty-tracklist" data-tara-queue=' ) );
t( 'فهرست: سه ردیف', 3 === substr_count( $list, '<li>' ) );
t( 'فهرست: ردیف بدون فایل هم می‌آید', false !== strpos( $list, 'بی‌صدا' ) && 2 === substr_count( $list, 'data-tara-play=' ) );
t( 'فهرست: خالی ⇒ رشته خالی', '' === tara_track_list( array() ) );

/* ======================================================================
   ۴.۵) شناسنامه و کارت شخص (v3.7.3)
   ====================================================================== */
require_once '/tmp/theme/inc/ab-credits.php';

$GLOBALS['__terms'][77] = new WP_Term( 77, 'حامد برادران', 'tara_songwriter', 'hamed-b' );
$GLOBALS['__terms'][78] = new WP_Term( 78, 'حامد برادران', 'tara_arranger', 'hamed-b', 3 );
$GLOBALS['__terms'][79] = new WP_Term( 79, 'هنرمند دیگر', 'artist', 'other-artist' );

$roles = tara_person_roles( $GLOBALS['__terms'][77] );
t( 'شخص: نقش تکراری پیدا شد', count( $roles ) >= 2 );
t( 'شخص: نقش جاری علامت می‌خورد', ! empty( $roles[0]['current'] ) && 'tara_songwriter' === $roles[0]['tax'] );

ob_start();
tara_person_card( $GLOBALS['__terms'][77] );
$person = ob_get_clean();
t( 'شخص: کارت رندر شد', false !== strpos( $person, 'class="ty-person"' ) );
t( 'شخص: لینک rel=me به نقش دیگر', false !== strpos( $person, 'rel="me"' ) && false !== strpos( $person, 'hamed-b' ) );
t( 'شخص: داده‌ساختاریافتهٔ Person', false !== strpos( $person, '"@type":"Person"' ) && false !== strpos( $person, '"sameAs"' ) );

$solo = new WP_Term( 90, 'تنها', 'tara_songwriter', 'solo' );
t( 'شخص: یک نقش ⇒ کارت خالی', '' === tara_person_card( $solo, false ) );

t( 'شناسنامه: ترتیب فیلدها پابرجاست', in_array( 'lyricist', array_keys( tara_credits_fields() ), true ) );

/* ---------- کلیدهای روشن/خاموش: یک قاعده برای همه (v3.7.4) ---------- */
require_once '/tmp/theme/inc/ab-controls.php';

$on_cases = array(
	array( 'on', true ),
	array( 'off', false ),
	array( '1', true ),
	array( '0', false ),
	array( 1, true ),
	array( 0, false ),
	array( 'true', true ),
	array( 'false', false ),
	array( 'خاموش', false ),
	array( 'روشن', true ),
);
$on_ok = true;
foreach ( $on_cases as $case ) {
	$GLOBALS['__options']['option_tree'] = array( 'zz_toggle' => $case[0] );
	if ( tara_opt_on( 'zz_toggle' ) !== $case[1] ) {
		$on_ok = false;
	}
}
t( 'کلیدها: on/off/۱/۰/true/false/فارسی همه یکسان خوانده می‌شوند', $on_ok );

$GLOBALS['__options']['option_tree'] = array();
t( 'کلیدها: نبودِ گزینه ⇒ پیش‌فرض', true === tara_opt_on( 'zz_missing', true ) && false === tara_opt_on( 'zz_missing', false ) );

/* ---------- نوار/پاپ‌آپِ پشتیبان (صفحهٔ اصلی بدون کارت پلیر) ---------- */
$GLOBALS['tara_has_queue']    = true;
$GLOBALS['tara_dock_printed'] = false;
$GLOBALS['tara_modal_printed'] = false;
ob_start();
tara_runtime_footer();
$runtime = ob_get_clean();
t( 'پشتیبان: نوار چسبان ساخته شد', false !== strpos( $runtime, 'data-tara-dock' ) );
t( 'پشتیبان: پاپ‌آپ فهرست ساخته شد', false !== strpos( $runtime, 'id="tara-playlist-modal"' ) );
t( 'پشتیبان: تصویر جانشین برای نوار', false !== strpos( $runtime, 'nomusics.jpg' ) );
$GLOBALS['tara_dock_printed']  = true;
$GLOBALS['tara_modal_printed'] = true;
ob_start();
tara_runtime_footer();
t( 'پشتیبان: اگر قبلاً چاپ شده، چیزی اضافه نمی‌کند', '' === ob_get_clean() );
unset( $GLOBALS['tara_has_queue'] );

/* ======================================================================
   ۵) پوستهٔ صفحهٔ تنظیمات
   ====================================================================== */
require '/tmp/theme/inc/admin-settings-shell.php';

t( 'تنظیمات: شناسهٔ صفحهٔ درست', 'taranehyar_page_ot-theme-options' === tara_settings_shell_screen() );
t( 'تنظیمات: پوسته به‌طور پیش‌فرض روشن است', true === tara_settings_shell_on() );

/* v3.7.4 — گزینهٔ قدیمی دیگر پوسته را خاموش نمی‌کند (تا کاربر فکر نکند آپدیت نشده) */
$GLOBALS['__options']['option_tree'] = array( 'ab_admin_ui' => 'off' );
t( 'تنظیمات: گزینهٔ قدیمی پوست را خاموش نمی‌کند', true === tara_settings_shell_on() );
$GLOBALS['__options']['option_tree'] = array( 'ab_admin_ui' => 'on' );

/* body class با رشته (رفتار واقعی وردپرس) — باگ کشندهٔ ۳.۷.۰–۳.۷.۳ */
$GLOBALS['__screen_id'] = 'taranehyar_page_ot-theme-options';
$body_string = tara_settings_shell_body_class( 'wp-admin wp-core-ui' );
t( 'تنظیمات: کلاس body روی رشته هم کار می‌کند', is_string( $body_string ) && false !== strpos( $body_string, 'tara-settings-screen' ), is_string( $body_string ) ? $body_string : gettype( $body_string ) );
$body_twice = tara_settings_shell_body_class( $body_string );
t( 'تنظیمات: کلاس body دوبار تکرار نمی‌شود', 1 === substr_count( $body_twice, 'tara-settings-screen' ) );

/* تشخیص صفحه با پارامتر آدرس، حتی اگر شناسهٔ صفحه عوض شود */
$GLOBALS['__screen_id'] = 'something_else';
$_GET['page']            = 'ot-theme-options';
t( 'تنظیمات: تشخیص صفحه با ?page=ot-theme-options', tara_settings_shell_is_page() && true === tara_settings_shell_on() );
unset( $_GET['page'] );

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

t( 'تنظیمات: نقشهٔ یازده بخش', 11 === count( tara_settings_shell_sections() ) );
t( 'تنظیمات: بخش «رادیو و دی‌جی» در نقشه هست', isset( tara_settings_shell_sections()['radio'] ) );
t( 'تنظیمات: هر بخش عنوان/آیکون/راهنما دارد', ( function () {
	foreach ( tara_settings_shell_sections() as $id => $item ) {
		if ( empty( $item['title'] ) || empty( $item['icon'] ) || ! isset( $item['hint'] ) ) {
			return false;
		}
	}
	return true;
} )() );
t( 'تنظیمات: بخش «درباره سایت» در نقشه هست', isset( tara_settings_shell_sections()['about'] ) );
t( 'تنظیمات: بخش «رنگ‌ها و هویت» در نقشه هست', isset( tara_settings_shell_sections()['brand'] ) );
t( 'تنظیمات: بخش «توسعه دهنده» در نقشه هست', isset( tara_settings_shell_sections()['dev'] ) );

/* سربرگ فقط روی همان صفحه چاپ می‌شود */
$GLOBALS['__screen_id'] = 'taranehyar_page_ot-theme-options';
ob_start();
tara_settings_shell_title();
$hero = ob_get_clean();
t( 'تنظیمات: سربرگ با کلاس پنل', false !== strpos( $hero, 'tara-settings-hero' ) );
t( 'تنظیمات: سربرگ دکمهٔ ذخیره دارد', false !== strpos( $hero, 'data-tara-save' ) );
t( 'تنظیمات: سربرگ لینک بازگشت به پنل', false !== strpos( $hero, 'admin.php?page=taranehyar' ) );

$GLOBALS['__screen_id'] = 'edit.php';
ob_start();
tara_settings_shell_title();
$hero_off = ob_get_clean();
t( 'تنظیمات: سربرگ در صفحهٔ دیگر چاپ نمی‌شود', '' === trim( $hero_off ) );
$GLOBALS['__screen_id'] = 'taranehyar_page_ot-theme-options';

t( 'تنظیمات: فایل CSS موجود', is_readable( '/tmp/theme/css/admin-settings.css' ) );
t( 'تنظیمات: فایل JS موجود', is_readable( '/tmp/theme/js/admin-settings.js' ) );
$settings_css = (string) @file_get_contents( '/tmp/theme/css/admin-settings.css' );
$settings_js  = (string) @file_get_contents( '/tmp/theme/js/admin-settings.js' );
t( 'تنظیمات: CSS فقط در body.tara-settings-screen', false !== strpos( $settings_css, 'body.tara-settings-screen' ) );
t( 'تنظیمات: چیدمان دوستونی فهرست/محتوا', false !== strpos( $settings_css, 'grid-template-columns: 252px minmax(0, 1fr)' ) );
t( 'تنظیمات: سربرگ هم‌رنگ پنل (بنفش)', false !== strpos( $settings_css, '#4c1d95' ) && false !== strpos( $settings_css, '#6d28d9' ) );
t( 'تنظیمات: CSS قدیمی (کارت آبی) نمانده', false === strpos( $settings_css, 'tara-settings-intro' ) );
t( 'تنظیمات: JS دکمهٔ ذخیره و جست‌وجو', false !== strpos( $settings_js, 'data-tara-save' ) && false !== strpos( $settings_js, 'runSearch' ) );
t( 'تنظیمات: JS بازکردن بخش با ?section=', false !== strpos( $settings_js, 'openSection' ) );

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
t( 'صفحهٔ آهنگ: نوار شناسه (ژانر/حال‌وهوا) دارد', false !== strpos( $single, 'tara_term_chips' ) );
t( 'صفحهٔ آهنگ: ترانه‌سرا/آهنگساز/تنظیم از نوار بالا حذف شد', false === strpos( $single, "'tara_songwriter' => array( 'label' => 'ترانه‌سرا' )" ) );
t( 'صفحهٔ آهنگ: ویدیو با پلیر تازه (tara_video)', false !== strpos( $single, 'tara_video(' ) );
t( 'صفحهٔ آهنگ: بلوک ویدیوی قدیمی نماند', false === strpos( $single, '<video width="640"' ) );

$home_css = (string) @file_get_contents( '/tmp/theme/css/tara-home.css' );
/* v3.7.5 — اسلاید فقط کاور است: بدون برچسب/عنوان/دکمه، تصویر کل کارت را می‌پوشاند */
t( 'اسلایدر: کادر متن روی کاور حذف شده', false === strpos( $home_css, '.ty-hero-slide__body' ) && false === strpos( $home_css, '.ty-hero-slide__play' ) );
t( 'اسلایدر: تصویر با نسبت تصویر کل کارت را می‌پوشاند', false !== strpos( $home_css, 'aspect-ratio: 16 / 7' ) && false !== strpos( $home_css, 'object-fit: cover' ) );
/* v3.7.8 — اسلایدر خانه بدون owl: یک اسلاید در جریان + cross-fade */
t( 'اسلایدر: بدون owl و فقط اسلاید فعال در جریان', false !== strpos( $home_css, '.ty-hero-track' ) && false !== strpos( $home_css, '.ty-hero-slide.is-active' ) && false === strpos( $home_css, 'owl-loaded' ) );
$vip_php = (string) @file_get_contents( '/tmp/theme/inc/vip_slider.php' );
t( 'اسلایدر: برچسب/دکمه از مارک‌آپ حذف شده', false === strpos( $vip_php, 'ty-hero-slide__play' ) && false === strpos( $vip_php, 'پیشنهاد ویژه' ) );

$ui_css = (string) @file_get_contents( '/tmp/theme/css/tara-ui.css' );
t( 'کارت: دکمهٔ پخش وسط کاور', false !== strpos( $ui_css, '.ty-cardplay {' ) && false !== strpos( $ui_css, 'left: 50%;' ) );
/* v3.7.8 — «مشخصات اثر» به کارتِ برچسب/مقدار بازطراحی شد */
t( 'شناسنامه: کارتِ شبکه‌ای برچسب/مقدار', false !== strpos( $ui_css, 'minmax(150px, 1fr)' ) && false !== strpos( $ui_css, '.ab-credits.ab-credits .ab-credit__k' ) );
t( 'هدر: کادر جست‌وجو روشن با متن تیره', false !== strpos( $ui_css, '#lsds' ) && false !== strpos( $ui_css, 'color: #16121f !important' ) );
t( 'صفحهٔ آهنگ: ستون کناری چسبان', false !== strpos( $ui_css, '#left_side' ) && false !== strpos( $ui_css, 'position: sticky' ) );

t( 'شناسنامه: کادر سفید پشت نام‌ها حذف شد', false !== strpos( $ui_css, 'background: transparent !important' ) && false === strpos( $ui_css, '.single_content_box .ab-credits{background' ) );
t( 'دکمهٔ بالا: کلاس is-shown و نمایش بدون jQuery', false !== strpos( $ui_css, '.top_page.is-shown' ) );

$ui_php = (string) @file_get_contents( '/tmp/theme/inc/tara-ui.php' );
t( 'پلیر: اسکریپت درون‌خطیِ مود/بالا', false !== strpos( $ui_php, 'tara-runtime-inline' ) && false !== strpos( $ui_php, "'tara-night'" ) );
t( 'پلیر: خودترمیمیِ بارگذاری فایل پلیر', false !== strpos( $ui_php, 'tara-ready' ) && false !== strpos( $ui_php, 'appendChild' ) );
t( 'پلیر: «صفحهٔ آهنگ» در همان صفحه پنهان می‌شود', false !== strpos( $ui_php, '$tara_same_page' ) );
t( 'پلیر: فهرست پخش برای یک قطعه پنهان می‌شود', false !== strpos( $ui_php, '$tara_total > 1' ) );

$home_player = (string) @file_get_contents( '/tmp/theme/inc/tarlanweb_player.php' );
t( 'پلیر صفحهٔ اصلی: پشتیبان برای نوشته‌های دارای فایل صوتی', false !== strpos( $home_player, 'music320' ) && false !== strpos( $home_player, 'music128' ) );

$settings_php = (string) @file_get_contents( '/tmp/theme/inc/admin-settings-shell.php' );
t( 'تنظیمات: جابه‌جایی بخش‌ها درون‌خطی است', false !== strpos( $settings_php, 'tara-tabs-ready' ) && false !== strpos( $settings_php, 'tara-settings-core-js' ) );
t( 'تنظیمات: هر گزینه جمع‌شونده است', false !== strpos( $settings_php, 'has-label' ) && false !== strpos( $settings_php, 'is-open' ) );
t( 'تنظیمات: دکمهٔ باز/بستن همه', false !== strpos( $settings_php, 'باز کردن همه' ) && false !== strpos( $settings_php, 'بستن همه' ) );

/* ---------- v3.7.7: رنگ‌های سایت، نوار چسبان تک‌ردیفه/دوردیفه، ویدیو ---------- */
t( 'رنگ‌ها: فایل tara-brand.php وجود دارد', is_readable( TARANEHYAR_DIR . '/inc/tara-brand.php' ) );
require_once TARANEHYAR_DIR . '/inc/tara-brand.php';
t( 'رنگ‌ها: رنگ اصلی از تنظیمات خوانده می‌شود', ( function () {
	$GLOBALS['__options']['option_tree'] = array( 'brand_primary' => '#aa33cc' );
	return '#aa33cc' === tara_brand_option( 'brand_primary', '#185ADB' );
} )() );
t( 'رنگ‌ها: مقدار نامعتبر رد می‌شود', ( function () {
	$GLOBALS['__options']['option_tree'] = array( 'brand_primary' => 'red; } body{display:none' );
	return '#185ADB' === tara_brand_option( 'brand_primary', '#185ADB' );
} )() );
t( 'رنگ‌ها: پالت پیش‌فرض درست است', ( function () {
	$GLOBALS['__options']['option_tree'] = array();
	$p = tara_brand_palette();
	return '#185ADB' === $p['primary'] && '#6d28d9' === $p['secondary'];
} )() );
t( 'رنگ‌ها: نسخهٔ روشن/تیره ساخته می‌شود', '#ffffff' === tara_tone( '#ffffff', 40 ) && 'rgba(24,90,219,0.100)' === tara_rgba( '#185ADB', 0.1 ) );

/* ---------- v3.7.6: پروتکل فایل صوتی، لایهٔ تضمینی، جست‌وجوی زنده ---------- */
$GLOBALS['__is_ssl'] = true;
t( 'پلیر: آدرس http روی سایت https به https ارتقا می‌یابد', 'https://ex/a.mp3' === tara_media_fix_scheme( 'http://ex/a.mp3' ) );
t( 'پلیر: آدرس // به پروتکل سایت تبدیل می‌شود', 'https://ex/b.mp3' === tara_media_fix_scheme( '//ex/b.mp3' ) );
$GLOBALS['__is_ssl'] = false;
t( 'پلیر: روی http دست‌نخورده می‌ماند', 'http://ex/a.mp3' === tara_media_fix_scheme( 'http://ex/a.mp3' ) );

$critical = (string) @file_get_contents( '/tmp/theme/inc/tara-critical.php' );
t( 'تضمینی: بلوک CSS درون‌خطی چاپ می‌شود', false !== strpos( $critical, 'tara_critical_css' ) && false !== strpos( $critical, 'wp_head' ) );
t( 'تضمینی: مود شب صریح و بی‌وابسته به متغیرها', false !== strpos( $critical, 'body.night' ) && false !== strpos( $critical, 'data-tara-night' ) );
t( 'تضمینی: اگر CSS چاپ نشد مستقیم لینک می‌شود', false !== strpos( $critical, 'tara_assets_guard_css' ) && false !== strpos( $critical, 'wp_style_is' ) );
t( 'تضمینی: اگر JS چاپ نشد مستقیم بارگذاری می‌شود', false !== strpos( $critical, 'tara_assets_guard_js' ) && false !== strpos( $critical, 'wp_script_is' ) );
t( 'جست‌وجو: نقطهٔ پایانی AJAX ثبت شده', function_exists( 'tara_ajax_search' ) && function_exists( 'tara_search_inline_js' ) );
t( 'جست‌وجو: با افزونهٔ SearchWP تعارض ندارد', false !== strpos( $critical, 'window.SearchWP' ) );
t( 'جست‌وجو: کادر نتایج درون‌خطی استایل دارد', false !== strpos( $critical, '#tara-search-results' ) && false !== strpos( $critical, 'is-open' ) );

$settings_two = (string) @file_get_contents( '/tmp/theme/inc/admin-settings-shell.php' );
t( 'تنظیمات: چیدمان دوستونی (محتوا کنار فهرست، نه زیرش)', false !== strpos( $settings_two, 'grid-template-columns: minmax(170px, 232px) minmax(0, 1fr)' ) );

$player_js = (string) @file_get_contents( '/tmp/theme/js/tara-player.js' );
t( 'کلید شب: هندلر مستقل در JS', false !== strpos( $player_js, 'tara-night' ) && false !== strpos( $player_js, 'dark_light_mode' ) );
t( 'پلیر: نشانهٔ راه‌اندازی data-tara-ready', false !== strpos( $player_js, 'data-tara-ready' ) );
t( 'پلیر: پشتیبان صف از data-tara-queue', false !== strpos( $player_js, "querySelector('[data-tara-queue]')" ) );

$settings_css = (string) @file_get_contents( '/tmp/theme/css/admin-settings.css' );
t( 'تنظیمات: نشان وضعیت فایل‌ها', false !== strpos( $settings_css, '.tara-settings-status' ) );
t( 'تنظیمات: سوییچ روشن/خاموش خوانا', false !== strpos( $settings_css, '.on-off-switch' ) );
t( 'تنظیمات: ریشهٔ Option Tree هم پوشش دارد', false !== strpos( $settings_css, '#option-tree-settings-api' ) );

$scripts = (string) @file_get_contents( '/tmp/theme/js/scripts.js' );
/* v3.7.8 — owl برای اسلایدر خانه حذف شد؛ scripts.js هم دیگر بی‌قید آن را صدا نمی‌زند */
t( 'اسلایدر: owl برای خانه حذف شده', false === strpos( $scripts, 'vip_top_slider' ) && false !== strpos( $scripts, '$.fn.owlCarousel' ) );

t( 'تاکسونومی: قالب آرشیو برای ژانر موجود', is_readable( '/tmp/theme/taxonomy-tara_genre.php' ) );
t( 'تاکسونومی: قالب مشترک آرشیو موجود', is_readable( '/tmp/theme/inc/tax-listing.php' ) );

/* ======================================================================
   v3.7.7 — بازبینی خرابی‌های گزارش‌شده و تنظیمات تازه
   ====================================================================== */
t( 'نسخه: style.css روی ۳.۸.۲ یا بالاتر است', (bool) preg_match( '/Version: 3\.(8\.[2-9]|9\.\d+)/', (string) @file_get_contents( '/tmp/theme/style.css' ) ) );

$opts = (string) @file_get_contents( '/tmp/theme/inc/theme-options.php' );
t( 'تنظیمات: بخش رنگ‌ها اضافه شده', false !== strpos( $opts, "'id'=>'brand'" ) );
t( 'تنظیمات: بخش توسعه دهنده اضافه شده', false !== strpos( $opts, "'id'=>'dev'" ) );
t( 'تنظیمات: رنگ اصلی و فرعی قابل تنظیم‌اند', false !== strpos( $opts, "'id'=>'brand_primary'" ) && false !== strpos( $opts, "'id'=>'brand_secondary'" ) );
t( 'تنظیمات: رنگ منو و جست‌وجوی گوشی قابل تنظیم است', false !== strpos( $opts, "'id'=>'tara_mob_menu_bg'" ) && false !== strpos( $opts, "'id'=>'tara_mob_search_bg'" ) );
t( 'تنظیمات: گزینه‌های ریز به بخش توسعه دهنده رفته‌اند', false !== strpos( $opts, "'id'=>'ab_post_img_size'" ) && preg_match( "/'id'=>'ab_post_img_size'.{0,400}'section'=>'dev'/s", $opts ) );
t( 'تنظیمات: نوار چسبان قدیمی پیش‌فرض خاموش است', (bool) preg_match( "/'id'=>'ab_sticky_player'.{0,700}'std'=>'off'/s", $opts ) );

$ui_css2 = (string) @file_get_contents( '/tmp/theme/css/tara-ui.css' );
t( 'کارت‌پخش: در قاعدهٔ اصلی فقط یک translate(-50%,-50%) دارد', 1 === preg_match( '/\\.ty-cardplay\\s*\\{[^}]*transform:\\s*translate\\(-50%,\\s*-50%\\)\\s*scale\\(0\\.92\\)/', $ui_css2 ) );
t( 'کارت‌پخش: translateY سرگردان حذف شده', false === strpos( $ui_css2, "opacity: 0;
	transform: translateY(6px);" ) );
t( 'نوار چسبان: شبکهٔ نه‌ستونه با نام ناحیه‌ها', false !== strpos( $ui_css2, 'grid-template-areas: "bars art meta prev toggle next vol list close"' ) );
t( 'نوار چسبان: کنترل صدا استایل دارد', false !== strpos( $ui_css2, '.ty-dock__volrange' ) && false !== strpos( $ui_css2, '.ty-dock__btn--vol' ) );
/* v3.7.10 — چیدمان تازهٔ گوشی: فلکس با شکست خط، بدون کنترل صدا، فهرست کنار بستن */
t(
	'نوار چسبان: گوشی فلکس دو ردیفی با ترتیب صریح',
	false !== strpos( $ui_css2, '.ty-dock::after {' )
		&& false !== strpos( $ui_css2, 'flex: 0 0 100%;' )
		&& (bool) preg_match( '/\[data-tara-dock-list\] \{ order: 3; \}/', $ui_css2 )
		&& (bool) preg_match( '/\[data-tara-dock-close\] \{ order: 4; \}/', $ui_css2 )
		&& (bool) preg_match( '/\[data-tara-dock-toggle\] \{ order: 7; \}/', $ui_css2 )
		&& (bool) preg_match( '/order: 5;\n\t\tflex: 0 0 100%;/', $ui_css2 )
);
t( 'دکمهٔ بالا: با نوار چسبان جابه‌جا می‌شود', false !== strpos( $ui_css2, 'body.has-tara-dock .top_page' ) );
t( 'ویدیو: حالت شبه‌تمام‌صفحه و چرخش دارد', false !== strpos( $ui_css2, '.ty-video.is-pseudofs' ) && false !== strpos( $ui_css2, 'is-rotated' ) );
t( 'شناسنامه: بدون پس‌زمینهٔ سفید', false !== strpos( $ui_css2, '.ab-credits.ab-credits .ab-credit {' ) );
t( 'جست‌وجو: حالت شبِ کادر تعریف شده', false !== strpos( $ui_css2, 'body.night #searches' ) );

$critical2 = (string) @file_get_contents( '/tmp/theme/inc/tara-critical.php' );
t( 'تضمینی: جست‌وجو در مود شب رنگ عوض می‌کند', false !== strpos( $critical2, 'body.night #searches' ) );
t( 'تضمینی: نتایج جست‌وجو در مود شب تیره است', false !== strpos( $critical2, 'body.night #tara-search-results' ) );

$artist_css = (string) @file_get_contents( '/tmp/theme/css/tara-artist.css' );
t( 'هنرمند: فهرست آهنگ‌ها چندستونه شده', false !== strpos( $artist_css, 'repeat(3, minmax(0, 1fr))' ) && false !== strpos( $artist_css, 'repeat(4, minmax(0, 1fr))' ) );

$home_css = (string) @file_get_contents( '/tmp/theme/css/tara-home.css' );
t( 'بنر: زوم نرم و کوتاه (بدون جهش)', false === strpos( $home_css, 'scale(1.05)' ) && false !== strpos( $home_css, 'scale(1.012)' ) );
t( 'بنر: نشانگرهای تازهٔ اسلایدر', false !== strpos( $home_css, '.ty-hero-dots' ) && false !== strpos( $home_css, '.ty-hero-dot.is-active' ) );

$shell = (string) @file_get_contents( '/tmp/theme/inc/admin-settings-shell.php' );
t( 'تنظیمات: ردیف‌ها پیش‌فرض باز هستند', false !== strpos( $shell, "classList.add('has-label', 'is-open')" ) );
$admin_js = (string) @file_get_contents( '/tmp/theme/js/admin-settings.js' );
t( 'تنظیمات: زیربخش‌ها به details تبدیل می‌شوند', false !== strpos( $admin_js, "box.className = 'tara-fold'" ) && false !== strpos( $admin_js, 'groupAll' ) );
$settings_css2 = (string) @file_get_contents( '/tmp/theme/css/admin-settings.css' );
t( 'تنظیمات: استایل زیربخش باز/بسته', false !== strpos( $settings_css2, '.tara-fold[open]' ) );

$dock_php = (string) @file_get_contents( '/tmp/theme/inc/tara-ui.php' );
t( 'نوار چسبان: مارکاپ صدا دارد', false !== strpos( $dock_php, 'data-tara-dock-vol' ) && false !== strpos( $dock_php, 'data-tara-dock-mute' ) );
$player_js2 = (string) @file_get_contents( '/tmp/theme/js/tara-player.js' );
t( 'ویدیو: خروج با Esc و تغییر جهت', false !== strpos( $player_js2, "'orientationchange'" ) && false !== strpos( $player_js2, "e.key !== 'Escape'" ) );
t( 'پلیر: نشانهٔ نسخه ۳.۸.۲ یا بالاتر', (bool) preg_match( "/'data-tara-ready', '3\\.(8\\.[2-9]|9\\.\\d+)'/", $player_js2 ) );

/* ======================================================================
   ۳.۷.۸ — دور ۶۶: ترتیب صفحهٔ آهنگ، اسلایدر تازه، سربرگ سه‌ستونهٔ هنرمند،
   رنگ کاور در پلیر، «مشخصات اثر»، حذف badge فرمت، لینک‌های رنگی
   ====================================================================== */
$single78 = (string) @file_get_contents( '/tmp/theme/single.php' );

/* ترتیب تازه (v3.7.9): پلیر → ما را دنبال کنید → توضیحات → دانلود → مشخصات اثر → متن ترانه */
$pos_player  = strpos( $single78, 'tara_player(' );
$pos_social  = strpos( $single78, 'tara_social_box' );
$pos_content = strpos( $single78, "the_content('')" );
$pos_credits = strpos( $single78, "tara_credits_box( get_the_ID() )" );
$pos_dl      = strpos( $single78, 'dl_links_box' );
$pos_lyrics  = strpos( $single78, "'end' === $taranehyar_lyrics_position" );
t(
	'صفحهٔ آهنگ: پلیر → دنبال‌کردن → توضیحات → دانلود → مشخصات → متن ترانه',
	false !== $pos_player && false !== $pos_social && false !== $pos_content
		&& false !== $pos_credits && false !== $pos_dl && false !== $pos_lyrics
		&& $pos_player < $pos_social && $pos_social < $pos_content
		&& $pos_content < $pos_dl && $pos_dl < $pos_credits && $pos_credits < $pos_lyrics
);
t( 'صفحهٔ آهنگ: کاور بزرگ بالای صفحه حذف شد', false === strpos( $single78, 'single_featured_cover' ) );
t( 'صفحهٔ آهنگ: نسخهٔ اصلی تصویر برای پاپ‌آپ دوربین', false !== strpos( $single78, "'zoom'    => $tara_cover_full" ) );

$ui78 = (string) @file_get_contents( '/tmp/theme/css/tara-ui.css' );
t( 'پلیر: دکمهٔ دوربین روی کاور', false !== strpos( (string) @file_get_contents( '/tmp/theme/inc/tara-ui.php' ), 'data-tara-zoom' ) && false !== strpos( $ui78, '.ty-player__zoom' ) );
t( 'پلیر: پاپ‌آپ تصویر سبک و بدون کتابخانه', false !== strpos( $ui78, '.ty-lightbox' ) );
t( 'پلیر: گرادیانتِ سبک از رنگ کاور', false !== strpos( $ui78, '--ty-cover-1' ) && false !== strpos( $player_js2, 'tintFromCover' ) );
t( 'پلیر: آیکون بی‌صدا در BEM پلیر', false !== strpos( $ui78, '.ty-player.is-muted .ty-icon-muted' ) );
t( 'پلیر: موبایلِ جمع‌وجور (کاور کنار سرتیتر)', false !== strpos( $ui78, '.ty-player__main { display: contents; }' ) );

$artist78 = (string) @file_get_contents( '/tmp/theme/css/tara-artist.css' );
t( 'هنرمند: سربرگ سه‌ستونه', false !== strpos( $artist78, 'minmax(0, 1.05fr) minmax(0, 1fr) 300px' ) );
t( 'هنرمند: ستون بنر با نسبت ۱۶:۵', false !== strpos( $artist78, '.ty-artist__portrait--banner img' ) && false !== strpos( $artist78, 'aspect-ratio: 16 / 5' ) );
$artist_tpl = (string) @file_get_contents( '/tmp/theme/taxonomy-artist.php' );
t( 'هنرمند: دکمهٔ بیوگرافی زیر بیو', false !== strpos( $artist_tpl, 'ty-artist__bio-btn' ) && false !== strpos( $artist_tpl, 'ty-artist__about' ) );
t( 'هنرمند: فیلد بنر در فرم ترم', false !== strpos( (string) @file_get_contents( '/tmp/theme/inc/ab-artist-fields.php' ), "'ab_artist_banner'" ) );
$admin78 = (string) @file_get_contents( '/tmp/theme/inc/ab-terms-admin.php' );
t( 'هنرمند: سایز استاندارد بنر در تنظیمات نوشته شده', false !== strpos( $admin78, '۱۲۰۰×۴۰۰' ) && false !== strpos( $admin78, 'tara_artist_banner_group' ) );

$cat78 = (string) @file_get_contents( '/tmp/theme/category.php' );
t( 'دسته: «دربارهٔ …» دقیقاً زیر صفحه‌بندی', strpos( $cat78, "get_template_part('inc/pagination')" ) < strpos( $cat78, 'tara_category_desc_full()' ) );
t( 'دسته: تیتر پویا «دربارهٔ نام دسته»', false !== strpos( (string) @file_get_contents( '/tmp/theme/inc/ab-cat-desc.php' ), "'دربارهٔ ' . $term_obj->name" ) );

$songs78 = (string) @file_get_contents( '/tmp/theme/inc/songs_post.php' );
t( 'کارت: badge فرمت (mp3/mp4) حذف شد', false === strpos( $songs78, 'ft_box_format' ) );

t( 'لینک‌ها: رنگ اصلی سایت در متن', false !== strpos( $ui78, '.archive_txt_box a:not([class])' ) && false !== strpos( $ui78, 'var(--ab-blue, #185adb)' ) );
t( 'هدر: فاصلهٔ محتوا هنگام نبود تبلیغ', false !== strpos( (string) @file_get_contents( '/tmp/theme/style.css' ), '#centers_box > :first-child{margin-top: 0 !important;}' ) );
t( 'دکمهٔ صفحهٔ اختصاصی: چیدمان flex با فلش سمت راست', false !== strpos( $ui78, '.rel_sing_bbt .title_tarlanweb_box' ) && false !== strpos( $ui78, 'order: -1' ) );
t( 'پخش صفحهٔ هنرمند: صف فهرست آهنگ‌ها', false !== strpos( $artist_tpl, 'tara_queue_attrs( $taranehyar_audio_tracks' ) );
t( 'پخش: دکمه‌ها دادهٔ پشتیبان دارند', false !== strpos( (string) @file_get_contents( '/tmp/theme/inc/tara-ui.php' ), 'data-tara-src=' ) );
t( 'پخش: پرش به آهنگ اول رفع شد', false !== strpos( $player_js2, 'root.__taraIndex' ) );
t( 'پخش: صفِ نایافته ⇒ تک‌قطعه‌ای', false !== strpos( $player_js2, 'singleCard' ) );
t( 'پلیر دوم قدیمی خاموش شد', false === strpos( (string) @file_get_contents( '/tmp/theme/inc/ab-post-extras.php' ), "add_action( 'wp_footer', 'tara_sticky_player', 30 );\n}" ) );
t( 'خانه: عنوان «آثار منتخب»', false !== strpos( (string) @file_get_contents( '/tmp/theme/inc/tarlanweb_player.php' ), 'آثار منتخب' ) );
t( 'اسلایدر: فایل مستقل بدون وابستگی', is_readable( '/tmp/theme/js/hero-slider.js' ) && false !== strpos( (string) @file_get_contents( '/tmp/theme/js/hero-slider.js' ), 'data-ty-hero' ) );

/* ======================================================================
   ۳.۷.۹ — دور ۶۷: باگ اسلایدر، جای «مشخصات اثر»، نشانهٔ ویدیو،
   نوار چسبان گوشی و پنل تنظیمات
   ====================================================================== */
$home79   = (string) @file_get_contents( '/tmp/theme/css/tara-home.css' );
$style79  = (string) @file_get_contents( '/tmp/theme/style.css' );
$abui79   = (string) @file_get_contents( '/tmp/theme/css/ab-ui.css' );
$hero_js79 = (string) @file_get_contents( '/tmp/theme/js/hero-slider.js' );
$ui79     = (string) @file_get_contents( '/tmp/theme/inc/tara-ui.php' );
$shell79  = (string) @file_get_contents( '/tmp/theme/inc/admin-settings-shell.php' );

/* ریشهٔ باگ: گاردِ دوران owl همهٔ اسلایدها جز اولی را پنهان می‌کرد */
t(
	'اسلایدر: گاردِ قدیمی owl اسلایدهای خانه را پنهان نمی‌کند',
	false === strpos( $style79, '.vip_top_slider:not(.owl-loaded)>:nth-child(n+2)' )
		&& false === strpos( $abui79, '.vip_top_slider:not(.owl-loaded)>:nth-child(n+2)' )
);
t( 'اسلایدر: گارد ریل هنرمندان حفظ شده', false !== strpos( $style79, '.artists_slider:not(.owl-loaded)>:nth-child(n+2)' ) );
t(
	'اسلایدر: ریل بومی با scroll-snap',
	false !== strpos( $home79, 'scroll-snap-type: x mandatory' )
		&& false !== strpos( $home79, 'flex: 0 0 100%' )
		&& false === strpos( $home79, 'visibility: hidden' )
);
t( 'اسلایدر: اسلایدها پشت‌سرهم در flow (بدون position:absolute)', false !== strpos( $home79, '.ty-hero-slide {' ) && false === strpos( $home79, 'inset: 0;\n\tdisplay: block;\n\tmargin: 0;' ) );
t(
	/* v3.8.2 — شاخص از هندسهٔ واقعی گرفته می‌شود، نه از فرمولِ مدلِ RTL */
	'اسلایدر: شاخص و پرش از هندسهٔ واقعی (getBoundingClientRect)',
	false !== strpos( $hero_js79, 'function offsetOf(' )
		&& false !== strpos( $hero_js79, 'getBoundingClientRect().left' )
		&& false !== strpos( $hero_js79, 'scrollToIndex' )
		&& false === strpos( $hero_js79, 'classList.remove(\'is-active\')' )
);
t( 'اسلایدر: نشانگرها فیزیکی وسط‌چین می‌شوند', false !== strpos( $home79, 'transform: translateX(-50%)' ) );
t( 'اسلایدر: هر اسلاید برچسب «n از m» دارد', false !== strpos( (string) @file_get_contents( '/tmp/theme/inc/vip_slider.php' ), 'aria-roledescription="slide"' ) );

/* مشخصات اثر پایین لینک دانلود */
t(
	'صفحهٔ آهنگ: مشخصات اثر بعد از باکس دانلود',
	strpos( $single78, 'dl_links_box' ) < strpos( $single78, 'tara_credits_box( get_the_ID() )' )
);

/* نشانهٔ موزیک ویدیو */
t(
	'ویدیو: تابع تشخیص و نشانه',
	false !== strpos( $ui79, 'function tara_post_has_video' )
		&& false !== strpos( $ui79, 'function tara_video_badge' )
		&& false !== strpos( $ui79, 'video1080' )
);
t( 'ویدیو: نشانه روی کارت', false !== strpos( (string) @file_get_contents( '/tmp/theme/inc/songs_post.php' ), 'tara_media_badge' ) );
t( 'ویدیو: نشانه کنار تیتر ردیف', false !== strpos( $ui79, "tara_video_badge( \$post_id, 'row' )" ) );
t( 'ویدیو: استایل نشانه', false !== strpos( $ui78, '.ab-card-video' ) && false !== strpos( $ui78, '.ty-track__vid' ) );

/* نوار چسبان گوشی */
t(
	'نوار چسبان: کنترل صدا در گوشی پنهان است',
	(bool) preg_match( '/\.ty-dock__bars,\s*\n\t\.ty-dock__vol,\s*\n\t\.ty-dock__link \{ display: none; \}/', $ui_css2 )
);
t( 'نوار چسبان: ارتفاع رزروشده با دو ردیف هم‌خوان است', false !== strpos( $ui_css2, 'calc(112px + env(safe-area-inset-bottom))' ) );
t( 'پلیر: ترابری گوشی دو ردیفی', false !== strpos( $ui_css2, '.ty-player__vol input[type="range"] { display: none; }' ) );

/* پنل تنظیمات */
t(
	'تنظیمات: آیتم‌های فهرستی باز/بسته می‌شوند',
	false !== strpos( $shell79, 'tara-li-open' )
		&& false !== strpos( $shell79, 'function setItem' )
		&& false !== strpos( $shell79, "querySelectorAll('.option-tree-setting')" )
);
t( 'تنظیمات: نوار ابزار بالای محتوا', false !== strpos( $shell79, 'poststuff.insertBefore(bar, poststuff.firstChild)' ) );

/* فاصلهٔ هدر تا محتوا */
t(
	'هدر: padding تبلیغ یک‌دست ۱۲px',
	1 === substr_count( $style79, '#ads_top,#ads_top_dl{width: 100%;height: auto;padding: 12px 0;text-align: center;}' )
);

/* ======================================================================
   ۳.۷.۱۰ — کاور اسلاید بدون برش، بازچینش پلیر گوشی، برچسب نوع اثر
   ====================================================================== */
$home710  = (string) @file_get_contents( '/tmp/theme/css/tara-home.css' );
$vip710   = (string) @file_get_contents( '/tmp/theme/inc/vip_slider.php' );
$ui710    = (string) @file_get_contents( '/tmp/theme/css/tara-ui.css' );
$single710 = (string) @file_get_contents( '/tmp/theme/single.php' );
$shell710 = (string) @file_get_contents( '/tmp/theme/inc/admin-settings-shell.php' );

t(
	'اسلایدر: یک تصویر با cover و بدون لایهٔ بلورِ تکراری',
	false !== strpos( $vip710, 'ty-hero-slide__img' )
		&& false === strpos( $vip710, 'ty-hero-slide__bg' )
		&& false !== strpos( $home710, 'object-fit: cover' )
		&& false === strpos( $home710, 'object-fit: contain' )
		&& false === strpos( $home710, 'filter: blur(18px)' )
);
t(
	'اسلایدر: کادرِ لینک‌دار (هر اسلاید به صفحهٔ آهنگ)',
	false !== strpos( $vip710, 'class="ty-hero-slide__media" href="<?php the_permalink(); ?>"' )
		&& false !== strpos( $home710, '.ty-hero-slide__media:focus-visible' )
);
t(
	'اسلایدر: نسبت واحد ۱۶:۷ در همهٔ دستگاه‌ها',
	false !== strpos( $home710, 'aspect-ratio: 16 / 7;' )
		&& false === strpos( $home710, 'aspect-ratio: 16 / 9' )
		&& false === strpos( $home710, 'aspect-ratio: 5 / 4' )
);
t( 'اسلایدر: نشانگرهای گوشی ۳۰px (ناحیهٔ لمس کافی)', false !== strpos( $home710, '.ty-hero-dot { width: 30px; height: 30px; padding: 11px; }' ) );

t(
	'پلیر: چیدمان شبکه‌ای صریح در گوشی (کاور+سرتیتر / ترابری)',
	false !== strpos( $ui710, '"art head"' )
		&& false !== strpos( $ui710, '"transport transport"' )
);
t( 'پلیر: ترابری دو خط با ترتیب صریح', false !== strpos( $ui710, '.ty-player__transport [data-tara-prev] { order: 1;' ) && false !== strpos( $ui710, '.ty-player__timeline {' ) );
t( 'پلیر: اسلایدر صدا در گوشی پنهان (دکمهٔ بی‌صدا می‌ماند)', false !== strpos( $ui710, '.ty-player__vol input[type="range"] { display: none; }' ) );
t( 'پلیر: نام آهنگ حداکثر دو خط', false !== strpos( $ui710, '-webkit-line-clamp: 2' ) );

t(
	'صفحهٔ آهنگ: برچسب فرمت (mp3) به «پخش آنلاین» تبدیل می‌شود',
	false !== strpos( $single710, '$tara_type_label' )
		&& false !== strpos( $single710, "\$tara_type_label = 'پخش آنلاین';" )
		&& false !== strpos( $single710, 'mp3|mp4|m4a' )
);

t(
	'تنظیمات: پشتیبان CSS برای بسته‌بودن آیتم‌های فهرستی',
	false !== strpos( $shell710, '.option-tree-setting:not(.tara-li-open) > .option-tree-setting-body { display: none; }' )
		&& false !== strpos( $shell710, 'مورد بدون عنوان' )
);


/* ======================================================================
   ۳.۸.۰ — نشانهٔ نوع اثر، اسلایدر راست‌چین، کاور بزرگ‌تر و بازبینی عمومی
   ====================================================================== */
$uiphp80   = (string) @file_get_contents( '/tmp/theme/inc/tara-ui.php' );
$ui80      = (string) @file_get_contents( '/tmp/theme/css/tara-ui.css' );
$home80    = (string) @file_get_contents( '/tmp/theme/css/tara-home.css' );
$herojs80  = (string) @file_get_contents( '/tmp/theme/js/hero-slider.js' );
$vip80     = (string) @file_get_contents( '/tmp/theme/inc/vip_slider.php' );
$head80    = (string) @file_get_contents( '/tmp/theme/header.php' );
$mob80     = (string) @file_get_contents( '/tmp/theme/inc/mobiles_header.php' );
$foot80    = (string) @file_get_contents( '/tmp/theme/footer.php' );
$single80  = (string) @file_get_contents( '/tmp/theme/single.php' );
$none80    = (string) @file_get_contents( '/tmp/theme/inc/no-content.php' );
$shell80   = (string) @file_get_contents( '/tmp/theme/inc/admin-settings-shell.php' );
$search80  = (string) @file_get_contents( '/tmp/theme/search.php' );
$songs80   = (string) @file_get_contents( '/tmp/theme/inc/songs_post.php' );
$chrome80  = $uiphp80;

/* --- نشانهٔ نوع اثر --- */
t(
	'نشانه: تابع قرینهٔ صوتی اضافه شد',
	false !== strpos( $uiphp80, 'function tara_post_has_audio' )
		&& false !== strpos( $uiphp80, 'function tara_media_badge' )
);
t(
	'نشانه: کارت‌ها از تابع تازه استفاده می‌کنند',
	false !== strpos( $songs80, 'tara_media_badge' )
);
t(
	'نشانه: هر دو پیل (ویدیو / آهنگ) در CSS هست',
	false !== strpos( $ui80, '.ab-card-video,' )
		&& false !== strpos( $ui80, '.ab-card-audio {' )
);
t(
	'نشانه: در گوشهٔ راست نشسته (inset-inline-start)',
	false !== strpos( $ui80, 'inset-inline-start: 7px; /* در RTL = راست-بالا */' )
);
t(
	'نشانه: دیگر در گوشهٔ چپ نیست',
	false === strpos( $ui80, 'inset-inline-end: 7px' )
);

/* --- اسلایدر راست‌چین --- */
t(
	'اسلایدر: ریل راست‌چین شد',
	false !== strpos( $home80, 'direction: rtl; /* v3.8.0' )
		&& false === strpos( $home80, 'direction: ltr; /* فقط برای ریاضیات' )
);
t(
	'اسلایدر: بدون پروبِ شکنندهٔ scrollLeft (مدلِ RTL حدس زده نمی‌شود)',
	false === strpos( $herojs80, 'rtlNegative' )
		&& false === strpos( $herojs80, 'root.scrollLeft = -1' )
		&& false !== strpos( $herojs80, 'root.scrollLeft + offsetOf(i)' )
);
t(
	'اسلایدر: کلید جهت در RTL برعکس می‌شود',
	false !== strpos( $herojs80, "event.key === 'ArrowRight' ? -1 : 1" )
);
t(
	'اسلایدر: اسلاید پنهان با inert از مسیر Tab بیرون است',
	false !== strpos( $herojs80, "'inert' in slides[n]" )
);

/* --- کاور بزرگ‌تر پلیر گوشی --- */
t(
	'پلیر موبایل: کاور ۷۶ ⇒ ۱۰۴ پیکسل',
	false !== strpos( $ui80, 'grid-template-columns: 104px minmax(0, 1fr);' )
		&& false === strpos( $ui80, 'grid-template-columns: 76px minmax(0, 1fr);' )
);
t( 'پلیر موبایل: گوشی باریک ۱۰۴px', false !== strpos( $ui80, 'width: 104px; height: 104px;' ) );

/* --- بنر --- */
t(
	'بنر: فیلد آرایه/شناسه/آدرس هر سه پذیرفته می‌شوند',
	false !== strpos( $vip80, 'is_array( $tara_custom )' )
		&& false !== strpos( $vip80, 'is_numeric( $tara_custom )' )
);
t(
	'بنر: srcset و ابعادِ ۱۶:۷ (از ۳.۸.۳ روی ۱۹۲۰×۸۴۰)',
	false !== strpos( $vip80, 'wp_get_attachment_image_srcset' )
		&& preg_match( '/width="(1280|1920)" height="(560|840)"/', $vip80 )
);
t(
	'بنر: راهنمای اندازه در تنظیمات',
	false !== strpos( $shell80, 'tara-settings-sizes' )
		&& false !== strpos( $shell80, '۱۹۲۰×۸۴۰' )
);

/* --- دسترس‌پذیری سربرگ --- */
t(
	'سربرگ: فرم جست‌وجو نقش و برچسب دارد',
	false !== strpos( $head80, 'role="search"' )
		&& false !== strpos( $head80, 'for="lsds"' )
);
t(
	'سربرگ: کلید شب/روز نقش switch و aria-checked دارد',
	false !== strpos( $head80, 'role="switch"' )
		&& false !== strpos( $head80, 'aria-checked=' )
		&& false !== strpos( $uiphp80, "'aria-checked'" )
);
t( 'سربرگ: متای بی‌معنی dc:creator حذف شد', false === strpos( $head80, 'property="dc:creator"' ) );

t(
	'موبایل: دکمهٔ منو <button> با aria-expanded و aria-controls',
	false !== strpos( $mob80, 'class="navicon" aria-label="منوی سایت" aria-expanded="false"' )
		&& false !== strpos( $mob80, 'aria-controls="ab-mobile-drawer"' )
);
t( 'موبایل: لینک تلگرام دیگر «twitter» خوانده نمی‌شود', false === strpos( $mob80, 'aria-label="twitter" class="icofont-paper-plane"' ) );
t( 'موبایل: Escape کشو را می‌بندد', false !== strpos( $uiphp80, "ev.key !== 'Escape'" ) );

/* --- پانوشت --- */
t(
	'پانوشت: آدرس‌های بی‌مسیر از sameAs حذف می‌شوند',
	false !== strpos( $foot80, "'facebook.com', 'youtube.com', 'aparat.com'" )
		&& false !== strpos( $foot80, 'set_url_scheme($s_val, \'https\')' )
);
t( 'پانوشت: لوگوی schema با https', false !== strpos( $foot80, 'set_url_scheme($ab_org_logo, \'https\')' ) );

/* --- صفحهٔ آهنگ --- */
t(
	'صفحهٔ آهنگ: نام هنرمند escape می‌شود',
	false !== strpos( $single80, '<?php echo esc_html( $artist ); ?>' )
		&& false === strpos( $single80, '<?php echo $artist; ?>' )
);
t(
	'صفحهٔ آهنگ: لینک‌های اشتراک‌گذاری درست و امن',
	false !== strpos( $single80, 'https://www.facebook.com/sharer/sharer.php?u=' )
		&& false !== strpos( $single80, 'https://twitter.com/intent/tweet?url=' )
		&& false === strpos( $single80, 'http://www.facebook.com/share.php' )
);
t( 'صفحهٔ آهنگ: لینک دانلود در تب تازه با noopener', false !== strpos( $single80, 'target="_blank" rel="noopener"' ) );

/* --- «چیزی پیدا نشد» --- */
t(
	'بی‌نتیجه: ریدایرکت خودکار و اسکریپت خطادار حذف شد',
	false === strpos( $none80, 'go2NewUrl' )
		&& false === strpos( $none80, '<script' )
);
t(
	'بی‌نتیجه: فرم جست‌وجو و میان‌بُرها',
	false !== strpos( $none80, 'role="search"' )
		&& false !== strpos( $none80, 'ab-empty__links' )
);
t( 'جست‌وجو: شمارش با ارقام فارسی', false !== strpos( $search80, 'tara_number_fa' ) );

/* --- عنوان ۴۰۴، تصویر شبکه‌های اجتماعی و دکمهٔ بالا --- */
$seo80 = (string) @file_get_contents( '/tmp/theme/inc/ab-seo.php' );
$func80 = (string) @file_get_contents( '/tmp/theme/functions.php' );
t(
	'۴۰۴: عنوان فارسی شد (نه «Page Not Found»)',
	false !== strpos( $seo80, "\$parts['title'] = 'صفحه پیدا نشد';" )
		&& false !== strpos( $seo80, 'is_404()' )
);
t(
	'شبکه‌های اجتماعی: سایز ۱۲۰۰×۶۳۰ برای og:image',
	false !== strpos( $func80, "add_image_size( 'tara-og', 1200, 630, true )" )
		&& false !== strpos( $seo80, "wp_get_attachment_image_src( \$tara_thumb_id, 'tara-og' )" )
);
t(
	'دکمهٔ بالا: نقش دکمه و ترتیب کلید',
	false !== strpos( $foot80, 'class="top_page boxes_shadows" role="button" tabindex="0"' )
		&& false !== strpos( $uiphp80, "el.classList.contains('top_page')" )
);

/* ======================================================================
   ۳.۸.۱ — دور ۷۰
   فلش‌ها و کشیدنِ اسلایدر روی دسکتاپ، نقطه‌های اسلایدر روی گوشی،
   صفحۀ ۴۰۴ وسط‌چین و بدون تصویر، نوار موبایل، منوی شیشه‌ای، اموجی شب،
   شفافیت هدر، کاور بزرگ‌ترِ پلیر و امن‌سازی جعبۀ دانلود.
   ====================================================================== */
$home81   = (string) @file_get_contents( '/tmp/theme/css/tara-home.css' );
$ui81     = (string) @file_get_contents( '/tmp/theme/css/tara-ui.css' );
$glass81  = (string) @file_get_contents( '/tmp/theme/css/ab-glass.css' );
$abu81    = (string) @file_get_contents( '/tmp/theme/css/ab-ui.css' );
$style81  = (string) @file_get_contents( '/tmp/theme/style.css' );
$hero81   = (string) @file_get_contents( '/tmp/theme/js/hero-slider.js' );
$mob81    = (string) @file_get_contents( '/tmp/theme/inc/mobiles_header.php' );
$p404_81  = (string) @file_get_contents( '/tmp/theme/404.php' );
$single81 = (string) @file_get_contents( '/tmp/theme/single.php' );

/* --- اسلایدر: فلش، کشیدن، نقطه‌ها --- */
t(
	'اسلایدر: فلش چپ/راست ساخته و فقط در دسکتاپ دیده می‌شود',
	false !== strpos( $hero81, "prevBtn.className = 'ty-hero-arrow ty-hero-arrow--prev'" )
		&& false !== strpos( $hero81, "nextBtn.className = 'ty-hero-arrow ty-hero-arrow--next'" )
		&& false !== strpos( $hero81, "prevBtn.setAttribute('aria-label', 'اسلاید قبلی')" )
		&& false !== strpos( $home81, '.ty-hero-arrow {' )
		&& false !== strpos( $home81, '@media (min-width: 783px) {' )
);
t(
	'اسلایدر: فلش‌ها دور می‌زنند (آخر ← اول)',
	false !== strpos( $hero81, 'if (target < 0) { target = total - 1; }' )
		&& false !== strpos( $hero81, 'if (target > total - 1) { target = 0; }' )
);
t(
	'اسلایدر: کشیدن با ماوس، بدون قفل‌کردنِ کلیکِ ساده (لینک‌ها کار کنند)',
	false !== strpos( $hero81, "event.pointerType !== 'mouse'" )
		&& false !== strpos( $hero81, 'if (Math.abs(dx) < 6) {' )
		&& false !== strpos( $hero81, 'root.setPointerCapture(event.pointerId)' )
		&& false !== strpos( $hero81, 'setScroll(drag.start - dx)' )
		&& false !== strpos( $hero81, 'suppressClick' )
		&& false !== strpos( $home81, '.ty-hero-track.is-dragging' )
		&& false !== strpos( $home81, 'cursor: grab' )
);
t(
	'اسلایدر: pointerdown دیگر پیش از کشیدن capture نمی‌گیرد',
	false !== strpos( $hero81, '/* هیچ capture‌ای این‌جا گرفته نمی‌شود؛ وگرنه یک کلیکِ ساده هم' )
);
t(
	'اسلایدر: snap-stop حذف شد (پرشِ چند اسلایدی گیر نمی‌کند)',
	false === strpos( $home81, 'scroll-snap-stop: always' )
);
t(
	'اسلایدر: نقطهٔ دورِ فعلی با انیمیشن، نقطهٔ دور پرش بی‌انیمیشن',
	false !== strpos( $hero81, 'scrollToIndex(target, Math.abs(target - index()) < 2);' )
);
t(
	'اسلایدر: نقطه‌های گوشی ۳۰px و ریل بدون snap-stop',
	false !== strpos( $home81, '.ty-hero-dot { width: 30px; height: 30px; padding: 11px; }' )
);
t(
	'اسلایدر: از اسلاید اول شروع می‌کند (نه از انتها در RTL)',
	false !== strpos( $hero81, 'scrollToIndex(0, false);' )
);

/* --- ۴۰۴ --- */
t(
	'۴۰۴: تصویر error404 حذف شد',
	false === strpos( $p404_81, 'error404.jpg' )
		&& false === strpos( $p404_81, 'ab-404-art' )
);
t(
	'۴۰۴: تمام‌وسط‌چین در همهٔ عرض‌ها',
	false !== strpos( $abu81, 'justify-items: center;text-align: center' )
		&& false !== strpos( $style81, 'justify-items: center;text-align: center' )
		&& false !== strpos( $abu81, '.ab-404-links{margin: var(--ab-space-4) 0 0;padding: 0;list-style: none;display: flex;flex-wrap: wrap;gap: 8px;justify-content: center}' )
);
t(
	'۴۰۴: لایۀ پشتیبان ab-ui.css با style.css هم‌گام است',
	false !== strpos( $style81, '.ab-404-hero{display: grid;grid-template-columns: minmax(0,1fr)' )
		&& false !== strpos( $abu81, '.ab-404-hero{display: grid;grid-template-columns: minmax(0,1fr)' )
);

/* --- نوار بالای موبایل، منو، اموجی شب --- */
t(
	'موبایل: دکمه‌های منو/جست‌وجو دایرهٔ ۴۶px و وسط‌چین',
	false !== strpos( $ui81, 'width: 46px;' )
		&& false !== strpos( $ui81, 'top: 50%;' )
		&& false !== strpos( $ui81, '#mobiles_header .search_mobile_box { left: 64px; }' )
		&& false !== strpos( $ui81, 'border-radius: 50%;' )
		&& false !== strpos( $ui81, 'linear-gradient(160deg, #23262f 0%, #0a0c11 100%)' )
);
t(
	'موبایل: لوگو هم وسطِ عمودی نوار',
	false !== strpos( $ui81, '#mobiles_header .logo_mobiles {' )
		&& false !== strpos( $ui81, 'transform: translateY(-50%);' )
);
t(
	'منو: از زغالی به شیشۀ روشن',
	false !== strpos( $ui81, '.mobiles_menu {' )
		&& false !== strpos( $ui81, 'background: var(--ab-glass-bg-strong, #fff);' )
		&& false !== strpos( $ui81, 'backdrop-filter: blur(18px) saturate(1.35);' )
		&& false !== strpos( $ui81, '.ab-mobile-menu-fallback li a {' )
);
t(
	'منو: کلید شب/روز با اموجی (خورشید/ماه) در مارک‌آپ',
	false !== strpos( $mob81, 'ty-night-toggle__emoji--sun' )
		&& false !== strpos( $mob81, 'ty-night-toggle__emoji--moon' )
		&& false !== strpos( $mob81, 'role="switch"' )
		&& false !== strpos( $mob81, 'dark_light_mode' )
		&& false !== strpos( $ui81, '.ty-night-toggle.dark_active .ty-night-toggle__emoji--moon' )
);
t(
	'منو: کلید شب/روز از همان گزینۀ مدیریتی sun_moon پیروی می‌کند',
	false !== strpos( $mob81, "tara_opt_on( 'sun_moon' )" )
);

/* --- شفافیت هدر --- */
t(
	'هدر: توکن مشترک با نوار چسبان پایین (نه شفافِ بیش‌ازحد)',
	false !== strpos( $glass81, '--ab-head-bg: rgba(255, 255, 255, .92);' )
		&& false !== strpos( $glass81, '--ab-head-bg: rgba(30, 30, 32, .92);' )
		&& false !== strpos( $glass81, "background: var(--ab-head-bg);" )
		&& false !== strpos( $style81, 'var(--ab-head-bg, var(--ab-charcoal))' )
);
t(
	'هدر موبایل: همان سطح، با مرز/سایهٔ نوار پایین',
	false !== strpos( $glass81, '#mobiles_header::before{' )
		&& false !== strpos( $glass81, '.night #mobiles_header{background: transparent !important}' )
);

/* --- کاور پلیر و جعبۀ دانلود --- */
t(
	'پلیر موبایل: کاور ۱۲۰px (بزرگ‌تر از ۱۰۴)',
	false !== strpos( $ui81, 'grid-template-columns: 120px minmax(0, 1fr);' )
);
t(
	'دانلود: بدون get_field خام (بدون ACF هم فاتال نمی‌شود)',
	false !== strpos( $single81, 'tara_music_value( $tara_dl_key )' )
		&& false === strpos( $single81, "get_field('music128')" )
		&& false === strpos( $single81, "get_field('video720')" )
);
t(
	'دانلود: کلاس‌های لینک (۱۲۸/۳۲۰) دست‌نخورده',
	false !== strpos( $single81, "'music128' => array( 'دانلود  آهنگ با کیفیت 128', 'dl_link_128' )" )
		&& false !== strpos( $single81, "'music320' => array( ' دانلود آهنگ با کیفیت 320', 'dl_links_320' )" )
);

/* ======================================================================
   ۳.۸.۲ — دور ۷۱
   اسلایدر (هندسهٔ واقعی، لینک، ۱۶:۷)، همبرگرِ وسطِ دایره، «ما را دنبال
   کنید» آیکونی در گوشی، پلیر ویدیوی استاندارد، کاور بزرگ‌ترِ دسکتاپ،
   راست‌چینیِ پلیر و کارتِ لوگو در پنل.
   ====================================================================== */
$home82  = (string) @file_get_contents( '/tmp/theme/css/tara-home.css' );
$ui82    = (string) @file_get_contents( '/tmp/theme/css/tara-ui.css' );
$abu82   = (string) @file_get_contents( '/tmp/theme/css/ab-ui.css' );
$style82 = (string) @file_get_contents( '/tmp/theme/style.css' );
$hero82  = (string) @file_get_contents( '/tmp/theme/js/hero-slider.js' );
$vip82   = (string) @file_get_contents( '/tmp/theme/inc/vip_slider.php' );
$logo82  = (string) @file_get_contents( '/tmp/theme/inc/ab-logo-setting.php' );
$view82  = (string) @file_get_contents( '/tmp/theme/inc/admin-panel-views.php' );
$post82  = (string) @file_get_contents( '/tmp/theme/inc/ab-post-extras.php' );
$panel82 = (string) @file_get_contents( '/tmp/theme/css/admin-panel.css' );

/* --- اسلایدر: هندسه، لینک، نسبت --- */
t(
	'اسلایدر: شاخص = نزدیک‌ترین اسلاید (بدون فرمولِ RTL)',
	false !== strpos( $hero82, 'function index() {' )
		&& false !== strpos( $hero82, 'var distance = Math.abs(offsetOf(i));' )
		&& false === strpos( $hero82, 'Math.round(root.scrollLeft / w)' )
);
t(
	'اسلایدر: پرش = scrollLeft + فاصلهٔ واقعی اسلاید',
	false !== strpos( $hero82, 'var left = root.scrollLeft + offsetOf(i);' )
);
t(
	'اسلایدر: capture فقط بعد از ۶px کشیدن گرفته می‌شود',
	false !== strpos( $hero82, 'if (Math.abs(dx) < 6) {' )
		&& false !== strpos( $hero82, 'drag.moved = true;' )
);
t(
	'اسلایدر: هر اسلاید یک لینک واقعی به صفحهٔ آهنگ',
	false !== strpos( $vip82, '<a class="ty-hero-slide__media" href="<?php the_permalink(); ?>"' )
		&& false !== strpos( $vip82, 'aria-label="<?php echo esc_attr(' )
);
t(
	'اسلایدر: کادر ۱۶:۷ و تصویر cover (بدون حاشیهٔ مات)',
	false !== strpos( $home82, 'aspect-ratio: 16 / 7;' )
		&& false !== strpos( $home82, 'object-fit: cover;' )
		&& preg_match( '/width="(1280|1920)" height="(560|840)"/', $vip82 )
);
t(
	'اسلایدر: نسبت ۱۶:۹ گوشی حذف شد (یک نسبت در همه‌جا)',
	false === strpos( $home82, '16 / 9' )
);

/* --- همبرگرِ وسطِ دایره --- */
t(
	'موبایل: خط‌های همبرگر با inset/margin:auto در مرکز دایره',
	preg_match( '/#mobiles_header \.responsive-menu-button-inner\s*\{[^}]*inset:\s*0;[^}]*margin:\s*auto;/s', $ui82 )
		&& false !== strpos( $ui82, 'right: auto; left: 0; }' )
);
t(
	'موبایل: دکمه‌ها دایره‌اند (نه مربعِ گوشه‌گرد)',
	false !== strpos( $ui82, 'border-radius: 50%;' )
);

/* --- ما را دنبال کنید --- */
t(
	'دنبال‌کردن: در گوشی فقط آیکونِ گرد ۴۴px',
	false !== strpos( $abu82, '.ab-social{' )
		&& false !== strpos( $abu82, 'width:44px;height:44px;margin:0;padding:0;border-radius:50%;' )
		&& false !== strpos( $abu82, '.ab-social__txt,.ab-social__arrow{position:absolute;' )
		&& false !== strpos( $style82, '.ab-social__txt,.ab-social__arrow{position:absolute;' )
);
t(
	'دنبال‌کردن: لینک‌ها aria-label گرفتند (آیکونِ بی‌نام نه)',
	false !== strpos( $post82, "aria-label=\"' . esc_attr( \$label ) . '\"" )
);

/* --- پلیر ویدیو و آهنگ --- */
t(
	'ویدیو: حداکثر عرض ۸۶۰px و وسط‌چین (کاور کل صفحه را نمی‌گیرد)',
	false !== strpos( $ui82, 'max-width: 860px;' )
		&& false !== strpos( $ui82, 'margin: 22px auto;' )
);
t(
	'ویدیو: پوستر کادر را پر می‌کند (بدون نوارِ خالی)',
	preg_match( '/\.ty-video__poster\s*\{[^}]*object-fit:\s*cover;[^}]*object-position:\s*center;[^}]*opacity:\s*\.9;/s', $ui82 )
);
t(
	'ویدیو: حالت تمام‌صفحه از سقفِ ۸۶۰px آزاد می‌شود',
	preg_match( '/\.ty-video\.is-fullscreen,[^{]*\{[^}]*max-width:\s*none;/s', $ui82 )
);
t(
	'پلیر: کاور دسکتاپ ۱۹۲px',
	false !== strpos( $ui82, 'width: 192px;' )
);
t(
	'پلیر: راست‌چینیِ صریح برای کارت/سرتیتر/ترابری/نوار ویدیو',
	preg_match( '/\.ty-player__transport,\s*\.ty-video__bar\s*\{\s*text-align:\s*right;/s', $ui82 )
);

/* --- کارت لوگو --- */
t(
	'لوگو: کارت آپلود/آدرس/بازگردانی در پنل',
	(bool) preg_match( '/function tara_logo_card\(\s*\$context/', $logo82 )
		&& false !== strpos( $logo82, 'name="tara_logo_file"' )
		&& false !== strpos( $logo82, 'name="tara_logo_url"' )
		&& false !== strpos( $logo82, 'name="tara_logo_reset"' )
);
t(
	'لوگو: ذخیره در همان option_tree که ot_get_option می‌خواند',
	false !== strpos( $logo82, 'tara_logo_option_key( $variant )' )
		&& false !== strpos( $logo82, 'update_option( $option_id, $options )' )
);
t(
	'لوگو: امنیت (nonce + manage_options + media_handle_upload)',
	false !== strpos( $logo82, "check_admin_referer( 'tara_save_logo' )" )
		&& false !== strpos( $logo82, "current_user_can( 'manage_options' )" )
		&& false !== strpos( $logo82, "media_handle_upload( 'tara_logo_file', 0 )" )
);
t(
	'لوگو: کارت در پیشخوان پنل رندر می‌شود',
	false !== strpos( $view82, 'tara_logo_card()' )
		&& false !== strpos( $view82, 'tara_logo_notice()' )
);
t(
	'لوگو: استایل کارت در فایلِ اختصاصیِ خودش است (v3.8.4)',
	false !== strpos( (string) @file_get_contents( '/tmp/theme/css/ab-logo-card.css' ), '.ty-logo-card {' )
		&& false !== strpos( (string) @file_get_contents( '/tmp/theme/css/ab-logo-card.css' ), '.ty-logo-card__preview' )
);

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
