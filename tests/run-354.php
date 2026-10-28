<?php
/**
 * آزمونِ واحدِ منطقِ نسخهٔ ۳.۵.۴ — صفحه‌بندیِ دیدگاه‌ها
 * اجرا:  node tools/php-test.mjs tests/run-354.php
 *
 * آزموده می‌شود (روی خودِ توابع قالب، نه کپیِ آن‌ها):
 *   ۱) روشن/خاموش‌بودنِ صفحه‌بندی و اولویتِ define/تنظیمات/فیلتر
 *   ۲) تعدادِ دیدگاه در هر صفحه و کریدرِ ۵..۱۰۰
 *   ۳) فیلترهای pre_option: در فرانت اعمال می‌شوند، در پیشخوان و آجاکس نه
 *   ۴) آمار: کل، صفحه‌ها، صفحهٔ جاری، بازهٔ ۱ تا ۲۰ / ۲۱ تا ۴۰
 *   ۵) خروجیِ ناوبری: nav + aria-label + خلاصهٔ فارسی + شماره‌ها + rel + aria-current
 *   ۶) سرتیتر و شمارشِ فارسی
 *   ۷) comments.php: استفاده از لایهٔ تازه + باقی‌ماندنِ مسیرِ قبلی (سازگاری)
 *
 * آزموده نمی‌شود: MySQL، رندرِ واقعیِ وردپرس و مرورگر.
 */

require __DIR__ . '/stubs-wp.php';

require '/tmp/theme/inc/ab-controls.php';   /* javan_seda_ui_on / javan_seda_ui_num */
require '/tmp/theme/inc/ab-artist-fields.php'; /* javan_seda_number_fa */
require '/tmp/theme/inc/ab-comments.php';

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
	$GLOBALS['__options']             = array();
	$GLOBALS['__ot']                  = array();
	$GLOBALS['__is_admin']            = false;
	$GLOBALS['__doing_ajax']          = false;
	$GLOBALS['__query_vars']          = array();
	$GLOBALS['__comments_total']      = 0;
	$GLOBALS['__comment_total_pages'] = 1;
}

/* فیلترها با include ثبت شده‌اند؛ برای آزمونِ حالتِ خاموش از فیلتر استفاده می‌کنیم */
$GLOBALS['__hooks'] = array();

/* ======================================================================
   ۱) روشن/خاموش و تعداد
   ====================================================================== */
reset_state();
t( 'پیش‌فرض: صفحه‌بندی روشن است', javan_seda_comment_paging_on() === true );
t( 'پیش‌فرض: ۲۰ دیدگاه در هر صفحه', 20 === javan_seda_comments_per_page() );

/* ===== از این‌جا به بعد با فیلترها کار می‌کنیم ===== */
add_filter( 'javanseda_comments_per_page', function () { return 30; } );
t( 'فیلتر تعداد: ۳۰', 30 === javan_seda_comments_per_page() );

/* ثبتِ دوبارهٔ فیلترها (چون __hooks پاک شد) */
add_filter( 'pre_option_page_comments', 'javan_seda_comments_force_paging' );
add_filter( 'pre_option_comments_per_page', 'javan_seda_comments_force_per_page' );

t( 'فرانت: page_comments اجباراً روشن', '1' === apply_filters( 'pre_option_page_comments', false ) );
t( 'فرانت: comments_per_page = ۳۰', '30' === apply_filters( 'pre_option_comments_per_page', false ) );

$GLOBALS['__is_admin'] = true;
t( 'پیشخوان: page_comments دست‌نخورده', false === apply_filters( 'pre_option_page_comments', false ) );
t( 'پیشخوان: comments_per_page دست‌نخورده', false === apply_filters( 'pre_option_comments_per_page', false ) );
$GLOBALS['__is_admin'] = false;

$GLOBALS['__doing_ajax'] = true;
t( 'آجاکس: مقدار دست‌نخورده', false === apply_filters( 'pre_option_page_comments', false ) );
$GLOBALS['__doing_ajax'] = false;

/* خاموشیِ کامل با فیلتر */
add_filter( 'javanseda_comment_paging', '__return_false' );
t( 'خاموشیِ کامل: صفحه‌بندی off', javan_seda_comment_paging_on() === false );
t( 'خاموشیِ کامل: هیچ اجباری روی گزینه', false === apply_filters( 'pre_option_page_comments', false ) );
$GLOBALS['__hooks']['javanseda_comment_paging'] = array();

/* کریدر: مقدار بیرون از بازه به محدوده برمی‌گردد */
$GLOBALS['__hooks']['javanseda_comments_per_page'] = array( function () { return 500; } );
t( 'کریدر بالا: ۵۰۰ ⇒ ۲۰۰', 200 === javan_seda_comments_per_page(), (string) javan_seda_comments_per_page() );

/* پایان آزمون‌های تعداد: فیلتر پاک می‌شود تا بخش‌های بعدی با پیش‌فرض ۲۰ کار کنند */
$GLOBALS['__hooks']['javanseda_comments_per_page'] = array();
t( 'بازگشت به پیش‌فرض پس از پاک‌کردن فیلتر', 20 === javan_seda_comments_per_page(), (string) javan_seda_comments_per_page() );

/* ======================================================================
   ۲) آمار صفحه‌بندی (۱۲۳ دیدگاه، ۲۰ در هر صفحه)
   ====================================================================== */
/* برای این بخش یک نمونهٔ تازه می‌سازیم: تابع کش دارد، پس با post_id متفاوت صدا می‌زنیم */
reset_state();
$GLOBALS['__comments_total'] = 123;
$GLOBALS['__query_vars']['cpage'] = 2;

$stats = javan_seda_comments_stats( 501 );
t( 'آمار: کل = ۱۲۳', 123 === $stats['total'], json_encode( $stats ) );
t( 'آمار: ۷ صفحه', 7 === $stats['pages'] );
t( 'آمار: صفحهٔ جاری = ۲', 2 === $stats['current'] );
t( 'آمار: بازهٔ ۲۱ تا ۴۰', 21 === $stats['from'] && 40 === $stats['to'] );

$GLOBALS['__query_vars']['cpage'] = 7;
$stats_last = javan_seda_comments_stats( 502 );
t( 'آمار: صفحۀ آخر تا ۱۲۳', 121 === $stats_last['from'] && 123 === $stats_last['to'], json_encode( $stats_last ) );

/* بدون cpage و با default_comments_page=newest ⇒ آخرین صفحه */
$GLOBALS['__query_vars'] = array();
$GLOBALS['__options']['default_comments_page'] = 'newest';
$stats_auto = javan_seda_comments_stats( 503 );
t( 'آمار: بدون cpage ⇒ صفحۀ آخر (newest)', 7 === $stats_auto['current'] );

$GLOBALS['__options']['default_comments_page'] = 'oldest';
$stats_old = javan_seda_comments_stats( 504 );
t( 'آمار: بدون cpage ⇒ صفحۀ اول (oldest)', 1 === $stats_old['current'] && 1 === $stats_old['from'] && 20 === $stats_old['to'] );

/* ======================================================================
   ۳) خلاصه
   ====================================================================== */
$s = javan_seda_comments_summary( array( 'total' => 123, 'pages' => 7, 'current' => 2, 'from' => 21, 'to' => 40 ) );
t( 'خلاصه: بازه و شمارهٔ فارسی', false !== strpos( $s, '۲۱' ) && false !== strpos( $s, '۴۰' ) && false !== strpos( $s, '۱۲۳' ) && false !== strpos( $s, '۲' ) && false !== strpos( $s, '۷' ), $s );
t( 'خلاصه: بدون رقم لاتین', ! preg_match( '/[0-9]/', $s ), $s );
t( 'خلاصه: تک‌صفحه‌ای‌بودن', javan_seda_comments_summary( array( 'total' => 12, 'pages' => 1, 'current' => 1, 'from' => 1, 'to' => 12 ) ) === '۱۲ دیدگاه' );
t( 'خلاصه: بدون دیدگاه ⇒ خالی', javan_seda_comments_summary( array( 'total' => 0 ) ) === '' );

/* ======================================================================
   ۴) خروجیِ ناوبری
   ====================================================================== */
$GLOBALS['__query_vars']['cpage'] = 2;
$GLOBALS['__comment_total_pages']  = 7;
$stats = javan_seda_comments_stats( 501 );
$html  = javan_seda_comments_pager( array( 'position' => 'top' ) );

t( 'ناوبری: nav با aria-label', false !== strpos( $html, '<nav class="ab-cpager ab-cpager--top"' ) && false !== strpos( $html, 'aria-label="صفحه‌بندی دیدگاه‌ها"' ) );
t( 'ناوبری: کلاسِ بالا/پایین', false !== strpos( $html, 'ab-cpager--top' ) && false !== strpos( javan_seda_comments_pager( array( 'position' => 'bottom' ) ), 'ab-cpager--bottom' ) );
t( 'ناوبری: خلاصه چاپ می‌شود', false !== strpos( $html, '<p class="ab-cpager__summary">' ) );
t( 'ناوبری: هر آیتم داخل li', substr_count( $html, '<li class="ab-cpager__item">' ) >= 3 );
t( 'ناوبری: شمارهٔ صفحهٔ جاری با aria-current', false !== strpos( $html, 'aria-current="page"' ) );
t( 'ناوبری: دکمهٔ قدیمی‌تر با rel=prev', false !== strpos( $html, 'rel="prev"' ) );
t( 'ناوبری: دکمهٔ جدیدتر با rel=next', false !== strpos( $html, 'rel="next"' ) );
t( 'ناوبری: aria-label فارسی برای شماره‌ها', false !== strpos( $html, 'aria-label="صفحهٔ ۳"' ), $html );
t( 'ناوبری: اعداد لاتین باقی نمانده', ! preg_match( '/>[0-9]+</', $html ), $html );

/* تک‌صفحه‌ای: بدون فهرستِ شماره‌ها، فقط خلاصه */
$GLOBALS['__comments_total']      = 5;
$GLOBALS['__comment_total_pages'] = 1;
$GLOBALS['__query_vars']          = array();
$one = javan_seda_comments_pager();
t( 'ناوبری: تک‌صفحه‌ای ⇒ بدون ul', false === strpos( $one, 'ab-cpager__list' ) && false !== strpos( $one, 'ab-cpager__summary' ) );

/* بدون دیدگاه: هیچ خروجی‌ای */
$GLOBALS['__comments_total'] = 0;
t( 'ناوبری: بدون دیدگاه ⇒ رشتهٔ خالی', javan_seda_comments_pager() === '' );

/* ======================================================================
   ۵) سرتیتر
   ====================================================================== */
$GLOBALS['__comments_total'] = 123;
$head = javan_seda_comments_heading( 601 );
t( 'سرتیتر: عنوان و شمارش', false !== strpos( $head, '<h2 class="comments-title ab-comments-title"' ) && false !== strpos( $head, '(۱۲۳)' ), $head );
t( 'سرتیتر: شناسهٔ یکتا برای انکر', false !== strpos( $head, 'id="ab-comments-title"' ) );

$GLOBALS['__comments_total'] = 0;
t( 'سرتیتر: بدون دیدگاه ⇒ خالی', javan_seda_comments_heading( 602 ) === '' );

/* ======================================================================
   ۶) امنیت خروجی
   ====================================================================== */
$GLOBALS['__comments_total'] = 10;
$GLOBALS['__query_vars']     = array();
$GLOBALS['__hooks']['javanseda_comment_paging'] = array();
$html2 = javan_seda_comments_pager();
t( 'امنیت: هیچ ورودی کاربر در ناوبری نیست', false === strpos( $html2, '<script' ) );

/* ======================================================================
   ۷) خودِ comments.php (یکپارچگی)
   ====================================================================== */
$tpl = file_get_contents( '/tmp/theme/comments.php' );
t( 'قالب: از لایهٔ تازه استفاده می‌کند', false !== strpos( $tpl, 'javan_seda_comments_pager' ) );
t( 'قالب: سرتیتر چاپ می‌شود', false !== strpos( $tpl, 'javan_seda_comments_heading' ) );
t( 'قالب: ناوبری بالا و پایین', substr_count( $tpl, 'javan_seda_comments_pager(' ) >= 2 );
t( 'قالب: مسیرِ قدیمی برای نصبِ نیمه‌کاره مانده', false !== strpos( $tpl, 'comment-nav-above' ) );
t( 'قالب: فهرست همچنان wp_list_comments است', false !== strpos( $tpl, 'wp_list_comments' ) );

$myf = file_get_contents( '/tmp/theme/myfunctions.php' );
t( 'myfunctions: لایهٔ دیدگاه‌ها require شده', false !== strpos( $myf, 'inc/ab-comments.php' ) );

$opts = file_get_contents( '/tmp/theme/inc/theme-options.php' );
t( 'تنظیمات: کلید صفحه‌بندی هست', false !== strpos( $opts, "'id'=>'ab_comments_paging'" ) && false !== strpos( $opts, "'section'=>'ab_ui'" ) );
t( 'تنظیمات: کلید تعداد هست', false !== strpos( $opts, "'id'=>'ab_comments_per_page'" ) && false !== strpos( $opts, "'min_max_step'=>'5,100,5'" ) );

$css = file_get_contents( '/tmp/theme/css/ab-352.css' );
t( 'CSS: بلوک ناوبری', false !== strpos( $css, '.ab-cpager .page-numbers' ) && false !== strpos( $css, '.ab-cpager .page-numbers.current' ) );
t( 'CSS: حالت شب', false !== strpos( $css, '.night .ab-cpager .page-numbers' ) );
t( 'CSS: فوکوس قابل‌دیدن', false !== strpos( $css, '.ab-cpager .page-numbers:focus-visible' ) );

$style = file_get_contents( '/tmp/theme/style.css' );
t( 'style.css: نسخه ۳.۵.۴ یا بالاتر', (bool) preg_match( '/Version:\s*3\.(5|6|7|8|9)\.[0-9]+/', $style ) );

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
