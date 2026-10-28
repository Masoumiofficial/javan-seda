<?php
/**
 * آزمونِ پنل «ترانه یار» — نسخهٔ ۳.۶.۰
 * اجرا:  node tools/php-test.mjs tests/run-360-panel.php
 *
 * چه چیزی سنجیده می‌شود؟
 *   • منو در نوار اصلی پیشخوان ثبت می‌شود (نه زیر «نمایش»)
 *   • صفحهٔ OptionTree با فیلترهای رسمی به زیر همین منو می‌آید
 *   • آمار، بخش‌ها، بررسی سلامت و گزارش سیستم بدون خطا ساخته می‌شوند
 *   • نماها خروجی HTML سالم می‌دهند و هیچ دادهٔ کاربری خام چاپ نمی‌کنند
 *   • استایل/اسکریپت فقط روی صفحه‌های همین پنل بار می‌شوند
 */

define( 'TY_TEST_REAL_OT', true );
require __DIR__ . '/stubs-wp.php';

define( 'TARANEHYAR_VERSION', '3.6.0' );
define( 'TARANEHYAR_DIR', '/tmp/theme' );
define( 'TARANEHYAR_URI', 'https://ex/theme' );
define( 'ABSPATH', '/tmp/wp/' );
define( 'MINUTE_IN_SECONDS', 60 );

$GLOBALS['wp_version'] = '6.8.2';

/* دیتابیس ساختگی: فقط همان دو متدی که پنل صدا می‌زند */
class TY_Fake_WPDB {
	public $postmeta = 'wp_postmeta';
	public function get_var( $query ) {
		$GLOBALS['__last_query'] = $query;
		return 4321;
	}
	public function db_version() {
		return '8.0.32';
	}
}
$GLOBALS['wpdb'] = new TY_Fake_WPDB();

/* این آزمون پنل را می‌سنجد نه هسته؛ هسته در tests/run-360.php آزموده می‌شود.
   پس فقط کوچک‌ترین نمایندهٔ آن (کلید تنظیمات + نشانه‌های حیات) اینجا هست. */
if ( ! function_exists( 'taranehyar_options_id' ) ) {
	function taranehyar_options_id() {
		return (string) apply_filters( 'ot_options_id', 'option_tree' );
	}
	function taranehyar_setup() {}
	function taranehyar_assets() {}
}

require '/tmp/theme/inc/admin-panel.php';

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

$GLOBALS['__caps'] = array( 'edit_theme_options' => true );

/* ======================================================================
   ۱) منو
   ====================================================================== */
taranehyar_admin_menu();

$menu = isset( $GLOBALS['__admin_menus']['taranehyar'] ) ? $GLOBALS['__admin_menus']['taranehyar'] : null;
t( 'منو: در نوار اصلی ثبت شد (slug=taranehyar)', null !== $menu );
t( 'منو: عنوان «ترانه یار»', $menu && 'ترانه یار' === $menu['menu_title'], $menu ? $menu['menu_title'] : '' );
t( 'منو: سطح دسترسی edit_theme_options', $menu && 'edit_theme_options' === $menu['capability'] );
t( 'منو: آیکون SVG درون‌خطی', $menu && 0 === strpos( (string) $menu['icon'], 'data:image/svg+xml;base64,' ) );
t( 'منو: جایگاه ۵۹', $menu && 59 === (int) $menu['position'] );
t( 'منو: callback پیشخوان', $menu && 'taranehyar_render_dashboard' === $menu['callback'] );

$subs = isset( $GLOBALS['__admin_submenus']['taranehyar'] ) ? $GLOBALS['__admin_submenus']['taranehyar'] : array();
t( 'زیرمنو: پیشخوان', isset( $subs['taranehyar'] ) );
t( 'زیرمنو: ابزارها', isset( $subs['taranehyar-tools'] ) );
t( 'زیرمنو: درباره', isset( $subs['taranehyar-about'] ) );
t( 'زیرمنو: میان‌بُر منوها', isset( $subs['nav-menus.php'] ) );
t( 'زیرمنو: میان‌بُر ابزارک‌ها', isset( $subs['widgets.php'] ) );
t( 'زیرمنو: میان‌بُر سربرگ', isset( $subs['customize.php'] ) );

/* ترتیب پس از افزودن صفحهٔ OptionTree (که در اولویت ۱۰ می‌آید) */
$GLOBALS['submenu']['taranehyar'][] = array( 'تنظیمات قالب', 'edit_theme_options', 'ot-theme-options', 'تنظیمات ترانه یار' );
taranehyar_panel_order_submenu();
$ordered = array();
foreach ( $GLOBALS['submenu']['taranehyar'] as $row ) {
	$ordered[] = $row[2];
}
t( 'ترتیب: تنظیمات بلافاصله بعد از پیشخوان', isset( $ordered[1] ) && 'ot-theme-options' === $ordered[1], implode( ',', $ordered ) );

/* ======================================================================
   ۲) انتقال صفحهٔ OptionTree
   ====================================================================== */
t( 'انتقال: پدرِ تنظیمات = منوی ترانه یار', 'taranehyar' === apply_filters( 'ot_theme_options_parent_slug', 'themes.php' ) );
t( 'انتقال: عنوان صفحه', 'تنظیمات ترانه یار' === apply_filters( 'ot_theme_options_page_title', 'x' ) );
t( 'انتقال: عنوان منو', 'تنظیمات قالب' === apply_filters( 'ot_theme_options_menu_title', 'x' ) );
t( 'انتقال: نامک صفحه دست نخورده (سازگاری لینک‌ها)', 'ot-theme-options' === apply_filters( 'ot_theme_options_menu_slug', 'ot-theme-options' ) );

/* ======================================================================
   ۳) آمار
   ====================================================================== */
$GLOBALS['__count_posts']    = 1234;
$GLOBALS['__count_comments'] = 56;
$GLOBALS['__count_users']    = 7;

$stats = taranehyar_panel_stats( true );
t( 'آمار: تعداد آهنگ', 1234 === $stats['posts'], (string) $stats['posts'] );
t( 'آمار: بازدید از جمع متا', 4321 === $stats['views'], (string) $stats['views'] );
t( 'آمار: دیدگاه تأییدشده', 56 === $stats['comments'] );
t( 'آمار: کاربران', 7 === $stats['users'] );
t( 'آمار: پرس‌وجو فقط خواندن است', isset( $GLOBALS['__last_query'] ) && 0 === stripos( trim( $GLOBALS['__last_query'] ), 'SELECT SUM' ), isset( $GLOBALS['__last_query'] ) ? $GLOBALS['__last_query'] : '' );

$cached = taranehyar_panel_stats();
t( 'آمار: از حافظه خوانده می‌شود', is_array( $cached ) && 1234 === $cached['posts'] );

/* ======================================================================
   ۴) بخش‌ها و سلامت
   ====================================================================== */
update_option(
	'option_tree_settings',
	array(
		'settings' => array(
			array( 'id' => 'a', 'section' => 'general' ),
			array( 'id' => 'b', 'section' => 'general' ),
			array( 'id' => 'c', 'section' => 'ab_ui' ),
			array( 'id' => 'd', 'section' => 'footer' ),
		),
	)
);
update_option( 'option_tree', array( 'logo' => 'x', 'index_ct' => 'y' ) );

$sections = taranehyar_panel_sections();
t( 'بخش‌ها: هشت بخش (با «دربارهٔ سایت»)', 8 === count( $sections ), (string) count( $sections ) );

$by_id = array();
foreach ( $sections as $section ) {
	$by_id[ $section['id'] ] = $section;
}
t( 'بخش‌ها: شناسه‌های هم‌نام با فهرست تنظیمات', array( 'general', 'homes', 'dl_pages', 'ab_ui', 'footer', 'socials', 'ads1', 'about' ) === array_keys( $by_id ) );
t( 'بخش‌ها: شمارش گزینه‌های «همگانی»', 2 === $by_id['general']['count'], (string) $by_id['general']['count'] );
t( 'بخش‌ها: بخش بدون گزینه صفر است', 0 === $by_id['ads1']['count'] );

$GLOBALS['__nav_menu_locations']['top_menu'] = 3;
$health                                      = taranehyar_panel_health();
t( 'سلامت: هفت بررسی', 7 === count( $health ) );

$health_by_label = array();
foreach ( $health as $check ) {
	$health_by_label[ $check['label'] ] = $check;
}
t( 'سلامت: هستهٔ خوانا سبز', ! empty( $health_by_label['هستهٔ خوانا']['ok'] ) );
t( 'سلامت: فایل لایسنس نیست', ! empty( $health_by_label['فایل لایسنس']['ok'] ) );
t( 'سلامت: حافظهٔ تنظیمات پر است', ! empty( $health_by_label['حافظهٔ تنظیمات']['ok'] ) );
t( 'سلامت: منوی اصلی تخصیص یافته', ! empty( $health_by_label['منوی اصلی']['ok'] ) );
t( 'سلامت: توضیح حافظه کلید option_tree را می‌گوید', false !== strpos( $health_by_label['حافظهٔ تنظیمات']['note'], 'option_tree' ) );

/* ======================================================================
   ۵) گزارش سیستم
   ====================================================================== */
$report = taranehyar_system_report();
t( 'گزارش: نسخهٔ قالب', false !== strpos( $report, '3.6.0' ) );
t( 'گزارش: نسخهٔ وردپرس', false !== strpos( $report, '6.8.2' ) );
t( 'گزارش: نسخهٔ PHP', false !== strpos( $report, PHP_VERSION ) );
t( 'گزارش: نسخهٔ MySQL از wpdb', false !== strpos( $report, '8.0.32' ) );
t( 'گزارش: کلید تنظیمات', false !== strpos( $report, 'option_tree' ) );
t( 'گزارش: راست‌چین', false !== strpos( $report, 'راست‌چین: بله' ) );

/* ======================================================================
   ۶) نماها
   ====================================================================== */
ob_start();
taranehyar_render_dashboard();
$html = ob_get_clean();

t( 'نما: پوشش wrap و dir=rtl', false !== strpos( $html, 'class="wrap ty-panel" dir="rtl"' ), substr( $html, 0, 120 ) );
t( 'نما: نام قالب در سربرگ', false !== strpos( $html, '<h1>ترانه یار</h1>' ) );
t( 'نما: نسخه در نشان', false !== strpos( $html, 'نسخهٔ 3.6.0' ) );
t( 'نما: چهار کارت آمار', 4 === substr_count( $html, 'class="ty-stat"' ) );
t( 'نما: هشت کارت تنظیمات', 8 === substr_count( $html, 'ty-card--link' ), (string) substr_count( $html, 'ty-card--link' ) );
t( 'نما: لینک تب‌های OptionTree', false !== strpos( $html, 'page=ot-theme-options&section=ab_ui#section_ab_ui' ) );
t( 'نما: بررسی سلامت', false !== strpos( $html, 'بررسی سلامت' ) );
t( 'نما: میان‌بُرها', false !== strpos( $html, 'میان‌بُرها' ) );
t( 'نما: ذکر نگه‌داشتن کلید option_tree', false !== strpos( $html, '<code>option_tree</code>' ) );
t( 'نما: بدون هشدار PHP', false === stripos( $html, 'warning:' ) && false === stripos( $html, 'notice:' ) );

ob_start();
taranehyar_render_tools();
$tools = ob_get_clean();
t( 'ابزارها: گزارش در textarea', false !== strpos( $tools, 'id="ty-report"' ) && false !== strpos( $tools, '<textarea' ) );
t( 'ابزارها: فرم پاک‌کردن حافظه با نانس', false !== strpos( $tools, 'taranehyar_clear_stats' ) && false !== strpos( $tools, '_wpnonce' ) );
t( 'ابزارها: بدون هشدار PHP', false === stripos( $tools, 'warning:' ) );

ob_start();
taranehyar_render_about();
$about = ob_get_clean();
t( 'درباره: نام و نسخه', false !== strpos( $about, 'ترانه یار' ) && false !== strpos( $about, '3.6.0' ) );
t( 'درباره: توضیح حذف لایسنس', false !== strpos( $about, 'لایسنس' ) );
t( 'درباره: بدون هشدار PHP', false === stripos( $about, 'warning:' ) );

/* ابزارک پیشخوان */
taranehyar_panel_dashboard_widget();
t( 'ابزارک: در پیشخوان ثبت شد', isset( $GLOBALS['__dashboard_widgets']['taranehyar_panel_widget'] ) );
ob_start();
call_user_func( $GLOBALS['__dashboard_widgets']['taranehyar_panel_widget']['callback'] );
$widget = ob_get_clean();
t( 'ابزارک: خروجی کوتاه و RTL', false !== strpos( $widget, 'dir="rtl"' ) && false !== strpos( $widget, 'تنظیمات قالب' ) );

/* ======================================================================
   ۶.۵) دکمهٔ پنجرهٔ رسانه در صفحهٔ تنظیمات
   ====================================================================== */
$_GET['page'] = 'ot-theme-options';
t( 'رسانه: در صفحهٔ تنظیمات ترجمه می‌شود', 'درج در تنظیمات' === apply_filters( 'gettext', 'Insert into post', 'Insert into post', 'default' ) );
$_GET['page'] = 'edit.php';
t( 'رسانه: در بقیهٔ صفحه‌ها دست نمی‌خورد', 'Insert into post' === apply_filters( 'gettext', 'Insert into post', 'Insert into post', 'default' ) );
unset( $_GET['page'] );

/* ======================================================================
   ۷) دارایی‌ها
   ====================================================================== */
$GLOBALS['taranehyar_panel_hooks'] = array( 'toplevel_page_taranehyar' );
taranehyar_panel_assets( 'toplevel_page_taranehyar' );
t( 'دارایی: CSS پنل بار شد', isset( $GLOBALS['__styles']['taranehyar-panel'] ) );
t( 'دارایی: نسخهٔ CSS = نسخهٔ قالب', isset( $GLOBALS['__styles']['taranehyar-panel'] ) && '3.6.0' === $GLOBALS['__styles']['taranehyar-panel'][2] );
t( 'دارایی: JS پنل در پانوشت', isset( $GLOBALS['__scripts']['taranehyar-panel'] ) && true === $GLOBALS['__scripts']['taranehyar-panel'][3] );

$before = array_keys( $GLOBALS['__styles'] );
taranehyar_panel_assets( 'edit.php' );
t( 'دارایی: در صفحه‌های دیگر بار نمی‌شود', array_keys( $GLOBALS['__styles'] ) === $before );

/* ======================================================================
   ۸) فایل‌های همراه
   ====================================================================== */
$css = '/tmp/theme/css/admin-panel.css';
$js  = '/tmp/theme/js/admin-panel.js';
t( 'فایل: CSS موجود', is_readable( $css ) );
t( 'فایل: JS موجود', is_readable( $js ) );
t( 'CSS: همه‌چیز زیر .ty-panel', false !== strpos( (string) @file_get_contents( $css ), '.ty-panel {' ) );
t( 'JS: بدون وابستگی بیرونی', false === strpos( (string) @file_get_contents( $js ), 'http' ) );

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
