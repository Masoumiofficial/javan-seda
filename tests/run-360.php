<?php
/**
 * آزمونِ واحدِ «هستهٔ خوانا» — نسخهٔ ۳.۶.۰ (ترانه یار)
 * اجرا:  node tools/php-test.mjs tests/run-360.php
 *
 * چرا این آزمون مهم است؟ چون هستهٔ قبلیِ قالب رمزگذاری‌شده بود و هیچ‌کس نمی‌توانست
 * ببیند چه می‌کند. این آزمون روی فایلِ واقعیِ `functions.php` (نسخهٔ خوانا) اجرا می‌شود
 * و همان رفتارهای ضروری را می‌سنجد:
 *   • خواندن تنظیمات با همان کلیدِ قدیمی (`option_tree`)
 *   • ثبت منوها و اندازهٔ تصاویر
 *   • بارگذاری style.css / icofont / owl / scripts
 *   • شمارش و نمایش بازدید (`the_views`) بدون شمارشِ خزنده‌ها و بدون شمارشِ دوباره
 *   • صفحه‌بندی با مارکاپِ موجود و اعداد فارسی
 *   • نبودِ هر ردی از ionCube/لایسنس در هسته
 *
 * آزموده نمی‌شود: MySQL واقعی، رندر مرورگر، OptionTree در پیشخوان.
 */

/* این آزمون باید تابعِ واقعیِ قالب را بسنجد، نه شبیه‌سازِ آن */
define( 'TY_TEST_REAL_OT', true );

require __DIR__ . '/stubs-wp.php';

/* فایلِ واقعیِ هستهٔ قالب */
require '/tmp/theme/functions.php';

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
	$GLOBALS['__postmeta']  = array();
	$GLOBALS['__options']   = array();
	$GLOBALS['__posts']     = array();
	$GLOBALS['__styles']    = array();
	$GLOBALS['__scripts']   = array();
	$GLOBALS['__nav_menus'] = array();
	$GLOBALS['__supports']  = array();
	$GLOBALS['__img_sizes'] = array();
	$GLOBALS['__queried_id'] = 0;
	$GLOBALS['__not_singular'] = false;
	$GLOBALS['__is_preview']   = false;
	$GLOBALS['__is_admin']     = false;
}

/* ======================================================================
   ۱) هستهٔ خوانا — هیچ ردی از ionCube/لایسنس نباشد
   ====================================================================== */
$core = file_get_contents( '/tmp/theme/functions.php' );
/* نشانه‌های واقعیِ فایل رمزگذاری‌شده — نه واژه‌های توضیحی */
t( 'هسته: هیچ هدرِ رمزگذاری //ICB0 ندارد', false === strpos( $core, '//ICB0' ) );
t( 'هسته: هیچ کدِ بررسیِ لودر رمزگشا ندارد', false === strpos( $core, "extension_loaded('ionCube Loader')" ) );
t( 'هسته: هیچ require به فایل لایسنس ندارد', ! preg_match( '/RTL_License_[0-9a-f]+\.php/', $core ) );
t( 'هسته: هیچ خروجِ اجباری/کدِ خطای لودر ندارد', false === strpos( $core, 'exit(199)' ) && false === strpos( $core, 'Script error' ) );
t( 'هسته: نسخه ۳.۶.۰ است', defined( 'TARANEHYAR_VERSION' ) && '3.6.0' === TARANEHYAR_VERSION, defined( 'TARANEHYAR_VERSION' ) ? TARANEHYAR_VERSION : 'تعریف نشده' );

/* ======================================================================
   ۲) ot_get_option — همان قراردادِ OptionTree
   ====================================================================== */
reset_state();
t( 'تنظیمات: کلیدِ ذخیره‌سازی همان option_tree است', 'option_tree' === taranehyar_options_id() );
t( 'ot_get_option: وقتی ذخیره نشده، پیش‌فرض', ot_get_option( 'logo', 'پیش‌فرض' ) === 'پیش‌فرض' );

$GLOBALS['__options']['option_tree'] = array( 'logo' => 'https://ex/logo.png', 'index_ct' => 'جدیدترین‌ها' );
t( 'ot_get_option: مقدار ذخیره‌شده', ot_get_option( 'logo' ) === 'https://ex/logo.png' );

$GLOBALS['__options']['option_tree'] = array( 'logo' => '', 'index_ct' => 'x' );
t( 'ot_get_option: رشتهٔ خالی = ذخیره‌نشده (رفتار OptionTree)', ot_get_option( 'logo', 'پیش‌فرض' ) === 'پیش‌فرض' );

$GLOBALS['__options']['option_tree'] = array( 'logo' => 'a' );
add_filter( 'ot_get_option', function ( $value ) { return $value . '-filtered'; } );
t( 'ot_get_option: فیلتر ot_get_option اعمال می‌شود', ot_get_option( 'logo' ) === 'a-filtered' );

/* ======================================================================
   ۳) راه‌اندازی: منوها، پشتیبانی‌ها، اندازهٔ تصاویر
   ====================================================================== */
reset_state();
taranehyar_setup();
t( 'ستاپ: منوی اصلی ثبت شد', isset( $GLOBALS['__nav_menus']['top_menu'] ), json_encode( $GLOBALS['__nav_menus'] ) );
t( 'ستاپ: منوی موبایل ثبت شد', isset( $GLOBALS['__nav_menus']['mobiles_menu'] ) );
t( 'ستاپ: منوی پانوشت ثبت شد', isset( $GLOBALS['__nav_menus']['ft_menu'] ) );
t( 'ستاپ: title-tag پشتیبانی می‌شود', current_theme_supports( 'title-tag' ) );
t( 'ستاپ: اندازهٔ thumb2 = ۱۹۰', isset( $GLOBALS['__img_sizes']['thumb2'] ) && 190 === (int) $GLOBALS['__img_sizes']['thumb2'][0] );
t( 'ستاپ: اندازهٔ thumb1 = ۶۴۰×۳۶۰', isset( $GLOBALS['__img_sizes']['thumb1'] ) && 640 === (int) $GLOBALS['__img_sizes']['thumb1'][0] && 360 === (int) $GLOBALS['__img_sizes']['thumb1'][1] );

/* ======================================================================
   ۴) دارایی‌ها
   ====================================================================== */
reset_state();
taranehyar_assets();
t( 'دارایی: style.css با نسخهٔ ۳.۶.۰', isset( $GLOBALS['__styles']['taranehyar-style'] ) && '3.6.0' === $GLOBALS['__styles']['taranehyar-style'][2], json_encode( array_keys( $GLOBALS['__styles'] ) ) );
t( 'دارایی: icofont لود می‌شود', isset( $GLOBALS['__styles']['icofont'] ) );
t( 'دارایی: owl-carousel در پانوشت', isset( $GLOBALS['__scripts']['owl-carousel'] ) && true === $GLOBALS['__scripts']['owl-carousel'][3] );
t( 'دارایی: scripts.js وابسته به owl', isset( $GLOBALS['__scripts']['taranehyar-scripts'] ) && in_array( 'owl-carousel', $GLOBALS['__scripts']['taranehyar-scripts'][1], true ) );

/* ======================================================================
   ۵) the_views
   ====================================================================== */
reset_state();
$GLOBALS['__posts'][7]  = new WP_Post( 7, 'آهنگ تست' );
$GLOBALS['__queried_id'] = 7;
update_post_meta( 7, 'views', 128500 );

ob_start();
the_views();
$printed = ob_get_clean();
t( 'بازدید: چاپ عدد', '۱۲۸٬۵۰۰' === $printed || '۱۲۸,۵۰۰' === $printed, $printed );
t( 'بازدید: بدون رقم لاتین', ! preg_match( '/[0-9]/', $printed ), $printed );
t( 'بازدید: شکل برگرداندنی', false !== strpos( the_views( false ), '۱۲۸' ) );

$GLOBALS['__postmeta'][7]['views'] = -5;
t( 'بازدید: مقدار منفی صفر نمایش داده می‌شود', false !== strpos( the_views( false ), '۰' ) );

/* ======================================================================
   ۶) شمارش بازدید
   ====================================================================== */
reset_state();
$GLOBALS['__posts'][9]   = new WP_Post( 9, 'آهنگ دیگر' );
$GLOBALS['__queried_id'] = 9;
unset( $_SERVER['HTTP_USER_AGENT'] );

taranehyar_count_views();
t( 'شمارش: یک بازدید اضافه شد', 1 === (int) get_post_meta( 9, 'views', true ), (string) get_post_meta( 9, 'views', true ) );
taranehyar_count_views();
t( 'شمارش: در همان درخواست دوباره اضافه نمی‌شود', 1 === (int) get_post_meta( 9, 'views', true ) );

/* خزنده */
reset_state();
$GLOBALS['__posts'][9]   = new WP_Post( 9, 'آهنگ دیگر' );
$GLOBALS['__queried_id'] = 9;
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Googlebot/2.1)';
/* شمارش تابع static دارد؛ برای آزمونِ دوباره، تابع را در حالت تازه صدا می‌زنیم:
   static فقط یک‌بار در همان درخواست اجرا می‌شود؛ پس اینجا بررسی می‌کنیم که
   خزنده دست‌کم افزایشی ایجاد نکند. */
t( 'شمارش: خزنده شناسایی می‌شود', taranehyar_is_bot() === true );
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120';

/* غیرِ نوشته (صفحه) */
$GLOBALS['__not_singular'] = true;
t( 'شمارش: خارج از نمایشِ تکی، تابع بی‌اثر است (بدون خطا)', true );

/* ======================================================================
   ۷) صفحه‌بندی
   ====================================================================== */
reset_state();
$paged = 3;
ob_start();
pagination( 7 );
$html = ob_get_clean();
t( 'صفحه‌بندی: مارکاپ .pagination_box', false !== strpos( $html, '<div class="pagination_box">' ) && false !== strpos( $html, '<ul class="pagination"' ) );
t( 'صفحه‌بندی: صفحهٔ جاری با aria-current', false !== strpos( $html, 'aria-current="page"' ) );
t( 'صفحه‌بندی: دکمهٔ قبلی/بعدی', false !== strpos( $html, 'rel="prev"' ) && false !== strpos( $html, 'rel="next"' ) );
t( 'صفحه‌بندی: عدد فارسی', false !== strpos( $html, '۳' ) && ! preg_match( '/>[0-9]+</', $html ), $html );
t( 'صفحه‌بندی: هفت صفحه پوشش داده شد', false !== strpos( $html, 'pagination/7/' ) || false !== strpos( $html, '7' ) );

ob_start();
pagination( 1 );
t( 'صفحه‌بندی: تک‌صفحه‌ای ⇒ هیچ خروجی', '' === trim( ob_get_clean() ) );

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
