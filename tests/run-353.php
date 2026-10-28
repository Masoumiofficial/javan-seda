<?php
/**
 * آزمونِ واحدِ منطقِ نسخهٔ ۳.۵.۳ — با شبیه‌سازِ حداقلیِ وردپرس (tests/stubs-wp.php).
 * اجرا:  node tools/php-test.mjs tests/run-353.php
 *
 * چه چیزی آزموده می‌شود؟
 *   ۱) عنوان/توضیحات متای صفحهٔ اصلی (فیلد تنظیمات، مقدار پیش‌فرض، پاک‌سازی، فیلتر عنوان)
 *   ۲) اینکه <title> دستی به header.php برنگشته باشد (گاردِ رگرسیون) و نسخه ۳.۵.۳ باشد
 *   ۳) کارتِ هنرمند: پیدا کردنِ ترم از نامک/نام، مجموع بازدید، متنِ فارسی، نام زیر عکس،
 *      خاموشیِ آمار، ایمنیِ خروجی و رفتارِ آیتمِ ناقص
 *   ۴) خودِ inc/artist_slider.php (یکپارچگیِ واقعی، نه بازنویسی)
 *
 * آزموده نمی‌شود: MySQL واقعی، رندرِ مرورگر، افزونهٔ سئو و OptionTree زنده.
 */

require __DIR__ . '/stubs-wp.php';

/* فایل‌های خودِ قالب */
require '/tmp/theme/inc/ab-artist-fields.php';
require '/tmp/theme/inc/ab-artist-cards.php';
require '/tmp/theme/inc/ab-home-meta.php';

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

function reset_state() {
	$GLOBALS['__termmeta'] = array();
	$GLOBALS['__postmeta'] = array();
	$GLOBALS['__options']  = array();
	$GLOBALS['__trans']    = array();
	$GLOBALS['__caps']     = array();
	$GLOBALS['__calls']    = array();
	$GLOBALS['__objterms'] = array();
	$GLOBALS['__posts']    = array();
	$GLOBALS['__terms']    = array();
	$GLOBALS['__acf']      = array();
	$GLOBALS['__db_sum']   = 0;
	$GLOBALS['__db_ids']   = array();
	$GLOBALS['__ot']       = array();
	$GLOBALS['__is_home']  = false;
	$GLOBALS['__is_front'] = false;
	$GLOBALS['__is_feed']  = false;
	$GLOBALS['__is_admin'] = false;
	$_POST                 = array();
}

/* ======================================================================
   ۱) متای صفحهٔ اصلی
   ====================================================================== */
reset_state();

t( 'متا: بدون تنظیم، عنوان خالی است', tara_home_meta( 'title' ) === '' );
t( 'متا: کلیدِ ناشناخته خالی است', tara_home_meta( 'unknown' ) === '' );
t( 'عنوان: بدون تنظیم ⇒ همان نام سایت (رفتار ۳.۵.۲)', tara_home_title( 'آهنگ‌بان' ) === 'آهنگ‌بان' );
t( 'توضیح: بدون تنظیم ⇒ همان توضیح کوتاه (رفتار ۳.۵.۲)', tara_home_description( 'توضیح سایت' ) === 'توضیح سایت' );

$GLOBALS['__ot']['home_meta_title'] = '  دانلود آهنگ جدید | آهنگ‌بان  ';
t( 'عنوان: مقدار تنظیمات قالب و حذف فاصله‌های اضافه', tara_home_title( 'x' ) === 'دانلود آهنگ جدید | آهنگ‌بان' );

$GLOBALS['__ot']['home_meta_title'] = 'a <b>b</b> <script>alert(1)</script>';
$title_clean = tara_home_meta( 'title' );
t( 'عنوان: تگ‌ها حذف می‌شوند', strpos( $title_clean, '<' ) === false, $title_clean );

$GLOBALS['__ot']['home_meta_desc'] = "خط اول\nخط دوم   با فاصله";
t( 'توضیح: چندخطی ⇒ یک‌خطی', tara_home_meta( 'desc' ) === 'خط اول خط دوم با فاصله', tara_home_meta( 'desc' ) );
t( 'توضیح: مقدار دستی بر توضیح سایت اولویت دارد', tara_home_description( 'سایت' ) === 'خط اول خط دوم با فاصله' );

/* فیلتر عنوان — فقط صفحهٔ اصلی */
$GLOBALS['__ot'] = array( 'home_meta_title' => 'آهنگ‌بان | دانلود آهنگ' );
$GLOBALS['__is_home'] = true;
$parts = tara_home_title_parts( array( 'title' => 'آهنگ‌بان', 'tagline' => 'توضیح کوتاه', 'site' => 'آهنگ‌بان' ) );
t( 'عنوان: صفحهٔ اصلی ⇒ فیلد دستی', isset( $parts['title'] ) && $parts['title'] === 'آهنگ‌بان | دانلود آهنگ', json_encode( $parts ) );
t( 'عنوان: تکرارِ نام سایت حذف می‌شود', '' === $parts['tagline'] && '' === $parts['site'] );
t( 'عنوان: فیلتر document_title_parts ثبت شده', has_filter( 'document_title_parts', 'tara_home_title_parts' ) );

$GLOBALS['__ot'] = array();
$parts = tara_home_title_parts( array( 'title' => 'آهنگ‌بان', 'tagline' => 'توضیح', 'site' => 'آهنگ‌بان' ) );
t( 'عنوان: فیلد خالی ⇒ فقط نام سایت (بدون تکرار)', $parts['title'] === 'آهنگ‌بان' && '' === $parts['tagline'] && '' === $parts['site'] );

$GLOBALS['__is_home'] = false;
$plain = array( 'title' => 'یک آهنگ', 'site' => 'آهنگ‌بان' );
t( 'عنوان: صفحه‌های دیگر دست‌نخورده می‌مانند', tara_home_title_parts( $plain ) === $plain );

$GLOBALS['__is_admin'] = true;
$GLOBALS['__is_home']  = true;
t( 'عنوان: در پیشخوان هیچ تغییری نمی‌دهد', tara_home_title_parts( $plain ) === $plain );
$GLOBALS['__is_admin'] = false;
$GLOBALS['__is_home']  = false;

/* ======================================================================
   ۲) گاردهای رگرسیون — عنوان دستی برنگشته، نسخه درست است
   ====================================================================== */
$header_php = file_get_contents( '/tmp/theme/header.php' );
t( 'header.php: <title> دستیِ قدیمی حذف شده', false === strpos( $header_php, "else{echo wp_title" ) );
t( 'header.php: گاردِ title-tag هست', false !== strpos( $header_php, "current_theme_supports( 'title-tag' )" ) );

$myf = file_get_contents( '/tmp/theme/myfunctions.php' );
t( 'myfunctions.php: title-tag فعال است', false !== strpos( $myf, "add_theme_support( 'title-tag' )" ) );
t( 'myfunctions.php: لایهٔ متای صفحهٔ اصلی require شده', false !== strpos( $myf, 'inc/ab-home-meta.php' ) );
t( 'myfunctions.php: کارتِ هنرمند require شده', false !== strpos( $myf, 'inc/ab-artist-cards.php' ) );

$style_css = file_get_contents( '/tmp/theme/style.css' );
t( 'style.css: نسخه ۳.۵.x یا بالاتر', (bool) preg_match( '/Version:\s*3\.(5|6|7|8|9)\.[0-9]+/', $style_css ), substr( $style_css, 0, 200 ) );

$css = file_get_contents( '/tmp/theme/css/ab-352.css' );
t( 'CSS: بلوک کارتِ هنرمند', false !== strpos( $css, '.artist_boxs a.ab-artist-card' ) );
t( 'CSS: لایهٔ آمار دایره‌ای و بی‌کلیک', false !== strpos( $css, 'pointer-events: none' ) && false !== strpos( $css, 'border-radius: 50%' ) );

/* ======================================================================
   ۳) کارتِ هنرمند
   ====================================================================== */
reset_state();
$GLOBALS['__terms'][3] = new WP_Term( 3, 'محسن یگانه', 'artist', 'mohsen-yeganeh' );
$GLOBALS['__terms'][4] = new WP_Term( 4, 'سارا', 'artist', 'sara' );
$GLOBALS['__db_sum']   = 128500;

$term = tara_artist_card_term( 'نامِ نمایشیِ دیگر', 'https://ahangban.ir/artist/mohsen-yeganeh/' );
t( 'کارت: تطبیق ترم با نامکِ آدرس', $term && 3 === (int) $term->term_id, $term ? $term->name : 'null' );

$term = tara_artist_card_term( 'سارا', '' );
t( 'کارت: تطبیق ترم با نامِ هنرمند', $term && 4 === (int) $term->term_id, $term ? $term->name : 'null' );

$term = tara_artist_card_term( 'ناشناس', 'https://ahangban.ir/artist/nobody/' );
t( 'کارت: بدون ترمِ متناظر ⇒ null (عدد حدسی نمی‌سازیم)', null === $term );

$html = tara_artist_card(
	array(
		'title'     => 'محسن یگانه',
		'siing_u'   => 'https://ahangban.ir/artist/mohsen-yeganeh/',
		'siing_img' => 'https://ahangban.ir/uploads/a.jpg',
	)
);
t( 'کارت: لینک و تصویر درست', false !== strpos( $html, 'href="https://ahangban.ir/artist/mohsen-yeganeh/"' ) && false !== strpos( $html, 'src="https://ahangban.ir/uploads/a.jpg"' ) );
t( 'کارت: کلاسِ اختصاصیِ کارت', false !== strpos( $html, 'class="ab-artist-card"' ) );
t( 'کارت: لایهٔ آمار (ab-card-stats + ab-cs-num)', false !== strpos( $html, 'ab-card-stats' ) && false !== strpos( $html, 'ab-cs-num' ) );
t( 'کارت: عددِ فارسی (بدون رقم لاتین)', false !== strpos( $html, '۱۲۸' ) && ! preg_match( '/12[0-9]/', $html ), substr( $html, 0, 300 ) );
t( 'کارت: متنِ جایگزینِ صفحه‌خوان', false !== strpos( $html, 'مجموع بازدید آثار' ) );
t( 'کارت: نام بعد از تصویر (زیرِ عکس) در DOM', strpos( $html, 'ab-artist-card__name' ) > strpos( $html, 'ab-artist-card__media' ) );
t( 'کارت: نام داخل spanِ مخصوص', false !== strpos( $html, '<span class="ab-artist-card__name">محسن یگانه</span>' ) );
t( 'کارت: تصویر lazy و با ابعاد (بدون CLS)', false !== strpos( $html, 'loading="lazy"' ) && false !== strpos( $html, 'width="150" height="150"' ) );
t( 'کارت: لینک خارجی با rel بی‌خطر', false !== strpos( $html, 'rel="noreferrer noopener"' ) );

/* بدون بازدید ⇒ بدون لایهٔ آمار */
$GLOBALS['__db_sum'] = 0;
$html_zero = tara_artist_card(
	array(
		'title'     => 'سارا',
		'siing_u'   => 'https://ahangban.ir/artist/sara/',
		'siing_img' => 'https://ahangban.ir/uploads/s.jpg',
	)
);
t( 'کارت: بازدید صفر ⇒ لایهٔ آمار چاپ نمی‌شود', false === strpos( $html_zero, 'ab-card-stats' ) );
t( 'کارت: با بازدید صفر، کارت و نام سرجایشان هستند', false !== strpos( $html_zero, 'ab-artist-card__name' ) && false !== strpos( $html_zero, '>سارا</span>' ) );

/* خاموشیِ آمار با فیلتر */
add_filter(
	'tara_artist_card_stats',
	function () {
		return false;
	}
);
$GLOBALS['__db_sum'] = 900;
$html_off = tara_artist_card(
	array(
		'title'     => 'محسن یگانه',
		'siing_u'   => 'https://ahangban.ir/artist/mohsen-yeganeh/',
		'siing_img' => 'https://ahangban.ir/uploads/a.jpg',
	)
);
t( 'کارت: فیلتر خاموشیِ آمار کار می‌کند', false === strpos( $html_off, 'ab-card-stats' ) );

/* ایمنی */
reset_state();
$GLOBALS['__terms'][4] = new WP_Term( 4, 'سارا', 'artist', 'sara' );
$html_bad = tara_artist_card(
	array(
		'title'     => '<script>alert(1)</script>',
		'siing_u'   => 'https://ahangban.ir/artist/sara/" onmouseover="x',
		'siing_img' => 'https://ahangban.ir/uploads/s.jpg',
	)
);
t( 'کارت: تزریقِ اسکریپت در نام بی‌اثر است', false === strpos( $html_bad, '<script>alert' ) );
t( 'کارت: تزریق در آدرس خنثی می‌شود', false === strpos( $html_bad, 'onmouseover="x"' ) );

t( 'کارت: آیتمِ ناقص ⇒ رشتهٔ خالی', tara_artist_card( array( 'title' => 'x' ) ) === '' );
t( 'کارت: ورودیِ غیرآرایه ⇒ رشتهٔ خالی', tara_artist_card( 'x' ) === '' );

/* ======================================================================
   ۴) یکپارچگیِ خودِ اسلایدر (فایل واقعی قالب)
   ====================================================================== */
reset_state();
/* فیلترِ «خاموشیِ آمار» مربوط به آزمونِ بالاست؛ برای این بخش پاک می‌شود */
unset( $GLOBALS['__hooks']['tara_artist_card_stats'] );
$GLOBALS['__terms'][3] = new WP_Term( 3, 'محسن یگانه', 'artist', 'mohsen-yeganeh' );
$GLOBALS['__db_sum']   = 500;
$GLOBALS['__ot']       = array(
	'arti_off'   => 'on',
	'arti_title' => 'هنرمندان منتخب',
	'arti_url'   => '',
	'siing_t'    => array(
		array(
			'title'     => 'محسن یگانه',
			'siing_u'   => 'https://ahangban.ir/artist/mohsen-yeganeh/',
			'siing_img' => 'https://ahangban.ir/uploads/a.jpg',
		),
	),
);
ob_start();
include '/tmp/theme/inc/artist_slider.php';
$slider = ob_get_clean();

t( 'اسلایدر: تیترِ بخش حفظ شده', false !== strpos( $slider, 'هنرمندان منتخب' ) );
t( 'اسلایدر: کارتِ تازه چاپ می‌شود', false !== strpos( $slider, 'ab-artist-card' ) );
t( 'اسلایدر: شمارهٔ بازدید روی کارت هست', false !== strpos( $slider, 'ab-cs-num' ) );
t( 'اسلایدر: نامِ هنرمند زیرِ تصویر', strpos( $slider, 'ab-artist-card__name' ) > strpos( $slider, 'ab-artist-card__media' ) );
t( 'اسلایدر: هیچ مارکاپِ قدیمیِ خالی نمانده', false === strpos( $slider, '<span></span>' ) );

/* خاموش‌بودنِ بخش (arti_off=off) ⇒ هیچ خروجی‌ای */
$GLOBALS['__ot']['arti_off'] = 'off';
ob_start();
include '/tmp/theme/inc/artist_slider.php';
$slider_off = ob_get_clean();
t( 'اسلایدر: با خاموش‌بودنِ بخش، خروجی خالی است', '' === trim( $slider_off ) );

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
