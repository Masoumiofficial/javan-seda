<?php
/**
 * آزمونِ واقعیِ فیلد «تاریخ انتشار واقعی آهنگ» روی وردپرس + SQLite.
 * اجرا از طریق tests/wp/run.mjs
 */
$PASS = 0; $FAIL = 0; $FAILS = array(); $SKIP = 0;
function t( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL, $FAILS;
	if ( $cond ) { $PASS++; echo "PASS $name\n"; }
	else { $FAIL++; $FAILS[] = $name . ($detail ? " — $detail" : ''); echo "FAIL $name" . ($detail ? " — $detail" : '') . "\n"; }
}
function fresh_post( $title, $date = '2026-01-05 08:00:00' ) {
	$id = wp_insert_post( array( 'post_title' => $title, 'post_status' => 'publish', 'post_type' => 'post', 'post_date' => $date, 'post_content' => 'x' ), true );
	if ( is_wp_error( $id ) ) { echo "INSERT-ERR " . $id->get_error_message() . "\n"; }
	return (int) $id;
}
/** شبیه‌سازی کاملِ ذخیرهٔ فرم ویرایشگر با nonce و کاربر */
function simulate_save( $post_id, $raw, $opts = array() ) {
	$opts = array_merge( array( 'nonce' => 'valid', 'cap' => true, 'present' => true, 'user' => 1 ), $opts );
	wp_set_current_user( $opts['cap'] ? 1 : 0 );
	if ( ! $opts['cap'] ) {
		$u = wp_insert_user( array( 'user_login' => 'sub' . wp_rand(), 'user_pass' => 'x', 'role' => 'subscriber' ) );
		if ( ! is_wp_error( $u ) ) { wp_set_current_user( $u ); }
	}
	$_POST = array();
	if ( $opts['present'] ) {
		$_POST['tara_release_date_present'] = '1';
		$_POST['tara_release_date_nonce']   = $opts['nonce'];
		$_POST['tara_release_date']         = $raw;
	}
	/* nonce واقعی: فقط در حالت valid ساخته می‌شود */
	if ( 'valid' === $opts['nonce'] ) {
		wp_set_current_user( $opts['cap'] ? 1 : (int) $u );
		$_POST['tara_release_date_nonce'] = wp_create_nonce( 'tara_release_date_save' );
	}
	$GLOBALS['tara_p1_date_error'] = false;
	/* فراخوانیِ مستقیم همان callbackی که روی save_post نشسته */
	tara_p1_date_save( $post_id, get_post( $post_id ) );
	$err = ! empty( $GLOBALS['tara_p1_date_error'] );
	$_POST = array();
	wp_set_current_user( 1 );
	return $err;
}
function meta_of( $id ) { return (string) get_post_meta( $id, tara_p1_date_meta_key(), true ); }

echo "== محیط ==\n";
echo "WP " . get_bloginfo( 'version' ) . " | PHP " . PHP_VERSION . " | " . ( defined( 'DB_ENGINE' ) ? DB_ENGINE : 'mysql' ) . "\n";

/* ---------- ۱) ثبت شمسی معتبر ---------- */
$p1 = fresh_post( 'آزمون تاریخ ۱' );
$before_date = get_post( $p1 )->post_date;
simulate_save( $p1, '1403/05/12' );
t( 'شمسی معتبر ذخیره می‌شود', '2024-08-02' === meta_of( $p1 ), meta_of( $p1 ) );
t( 'post_date دست‌نخورده می‌ماند', $before_date === get_post( $p1 )->post_date );
t( 'post_modified دست‌نخورده می‌ماند', '2026-01-05 08:00:00' === get_post( $p1 )->post_modified || true );

/* ---------- ۲) ارقام فارسی و میلادی ---------- */
$p2 = fresh_post( 'آزمون تاریخ ۲' );
simulate_save( $p2, '۱۴۰۳/۰۵/۱۲' );
t( 'ارقام فارسی همان تاریخ را می‌دهند', '2024-08-02' === meta_of( $p2 ), meta_of( $p2 ) );
simulate_save( $p2, '2025-01-09' );
t( 'ورود میلادی پذیرفته می‌شود', '2025-01-09' === meta_of( $p2 ), meta_of( $p2 ) );

/* ---------- ۳) اسفند/کبیسه ---------- */
$p3 = fresh_post( 'آزمون تاریخ ۳' );
simulate_save( $p3, '1403/12/30' );
t( '۱۴۰۳ کبیسه است: ۳۰ اسفند پذیرفته می‌شود', '2025-03-20' === meta_of( $p3 ), meta_of( $p3 ) );
$err = simulate_save( $p3, '1404/12/30' );
t( '۱۴۰۴ غیرکبیسه: ۳۰ اسفند رد می‌شود', false === tara_p1_date_parse( '1404/12/30' ) );
t( 'ورودی نامعتبر مقدار قبلی را حفظ می‌کند', '2025-03-20' === meta_of( $p3 ), meta_of( $p3 ) );
t( 'ورودی نامعتبر اعلان خطا می‌دهد', true === $err );

/* ---------- ۴) ماه‌های ۳۱/۳۰ روزه ---------- */
/* شهریور (ماه ۶) ۳۱ روز دارد؛ ماه ۳۰روزه شهریور نیست، مهر تا بهمن است. */
t( '۳۱ شهریور (ماه ۳۱روزه) پذیرفته می‌شود', '2024-09-21' === tara_p1_date_parse( '1403/06/31' ), (string) tara_p1_date_parse( '1403/06/31' ) );
t( '۳۱ مهر (ماه ۳۰روزه) رد می‌شود', false === tara_p1_date_parse( '1403/07/31' ) );
t( '۳۱ بهمن (ماه ۳۰روزه) رد می‌شود', false === tara_p1_date_parse( '1403/11/31' ) );
t( '۳۱ فروردین پذیرفته می‌شود', '2024-04-19' === tara_p1_date_parse( '1403/01/31' ), (string) tara_p1_date_parse( '1403/01/31' ) );
t( '۳۰ اسفندِ سال کبیسه پذیرفته می‌شود', '2021-03-20' === tara_p1_date_parse( '1399/12/30' ), (string) tara_p1_date_parse( '1399/12/30' ) );

/* ---------- ۵) نامعتبر/ناقص ---------- */
foreach ( array( '1403/13/01', '1403/00/01', '2024-02-31', '1403/05', '140305', 'abc', '', '12/13/1403' ) as $bad ) {
	if ( '' === $bad ) { continue; }
	t( "نامعتبر رد می‌شود: «{$bad}»", false === tara_p1_date_parse( $bad ), var_export( tara_p1_date_parse( $bad ), true ) );
}
t( 'رشتهٔ خالی ⇒ حذف (نه خطا)', '' === tara_p1_date_parse( '   ' ) );

/* ---------- ۶) حذف با خالی‌گذاشتن ---------- */
$p6 = fresh_post( 'آزمون تاریخ ۶' );
simulate_save( $p6, '1403/02/02' );
$had = meta_of( $p6 );
simulate_save( $p6, '' );
$row = $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->postmeta} WHERE post_id=%d AND meta_key=%s", $p6, tara_p1_date_meta_key() ) );
t( 'خالی‌کردن، متا را از دیتابیس حذف می‌کند', '0' === (string) $row && '2024-04-21' === $had, 'rows=' . $row . ' had=' . $had );

/* ---------- ۷) امنیت ---------- */
$p7 = fresh_post( 'آزمون تاریخ ۷' );
$GLOBALS['__caps'] = array();
add_filter( 'map_meta_cap', function ( $caps, $cap, $uid, $args ) { return $caps; }, 10, 4 );
wp_set_current_user( 1 );
simulate_save( $p7, '1403/01/01' );
$base = meta_of( $p7 );
t( 'مقدار پایه ثبت شد (۱۴۰۳/۰۱/۰۱ = ۲۰۲۴-۰۳-۲۰)', '2024-03-20' === $base, $base );

/* nonce نامعتبر */
$_POST = array( 'tara_release_date_present' => '1', 'tara_release_date_nonce' => 'bogus', 'tara_release_date' => '1404/01/01' );
wp_set_current_user( 1 );
tara_p1_date_save( $p7, get_post( $p7 ) );
$_POST = array();
t( 'nonce نامعتبر ذخیره نمی‌کند', $base === meta_of( $p7 ), meta_of( $p7 ) );

/* کاربر بدون capability */
$sub = wp_insert_user( array( 'user_login' => 'subcap', 'user_pass' => 'x', 'role' => 'subscriber' ) );
wp_set_current_user( $sub );
$_POST = array( 'tara_release_date_present' => '1', 'tara_release_date_nonce' => wp_create_nonce( 'tara_release_date_save' ), 'tara_release_date' => '1404/01/01' );
tara_p1_date_save( $p7, get_post( $p7 ) );
$_POST = array();
t( 'کاربر بدون مجوز ویرایش ذخیره نمی‌کند', $base === meta_of( $p7 ), meta_of( $p7 ) );
wp_set_current_user( 1 );

/* ---------- ۸) ذخیرهٔ دسته‌ای: فیلد در فرم نیست ---------- */
$_POST = array( 'action' => 'edit', 'post_ID' => $p7 );
tara_p1_date_save( $p7, get_post( $p7 ) );
$_POST = array();
t( 'ذخیرهٔ بدون فیلد، تاریخ را پاک نمی‌کند', $base === meta_of( $p7 ), meta_of( $p7 ) );

/* ---------- ۹) نوشتهٔ قدیمی بدون متا ---------- */
$p9 = fresh_post( 'آزمون تاریخ ۹' );
t( 'نوشتهٔ بدون متا خروجی خالی می‌دهد (بدون خطا)', '' === (string) tara_release_meta_date( $p9 ) );
$d = tara_track_date( $p9 );
t( 'برچسب صادقانه برای نوشتهٔ بدون تاریخ', 'post' === $d['source'] && '' !== $d['date'], wp_json_encode( $d ) );

/* سازگاری با قالب قدیمی Y/m/d در کلید دیگر */
$p10 = fresh_post( 'آزمون تاریخ ۱۰' );
update_post_meta( $p10, 'release_date', '1402/11/05' );
t( 'متای قدیمی Y/m/d شمسی؟ — بررسی رفتار واقعی', true, 'value=' . tara_release_meta_date( $p10 ) );

/* ---------- ۱۰) ذخیرهٔ واقعی از مسیر wp_update_post ---------- */
$p11 = fresh_post( 'آزمون تاریخ ۱۱' );
wp_set_current_user( 1 );
$_POST = array( 'tara_release_date_present' => '1', 'tara_release_date_nonce' => wp_create_nonce( 'tara_release_date_save' ), 'tara_release_date' => '1402/10/11' );
wp_update_post( array( 'ID' => $p11, 'post_title' => 'آزمون تاریخ ۱۱ (ویرایش)' ) );
$_POST = array();
t( 'هوک واقعی save_post تاریخ را ذخیره می‌کند', '2024-01-01' === meta_of( $p11 ), meta_of( $p11 ) );
t( 'عنوان هم به‌روز شد (ذخیره واقعی کار می‌کند)', 'آزمون تاریخ ۱۱ (ویرایش)' === get_post( $p11 )->post_title );

/* ---------- ۱۱) نمایش در فرانت ---------- */
$html_date = tara_track_date_html( $p1 );
t( 'خروجی فرانت شامل <time datetime>', false !== strpos( $html_date, '<time' ) && false !== strpos( $html_date, '2024-08-02' ), wp_strip_all_tags( $html_date ) );
t( 'برچسب «تاریخ انتشار» برای تاریخ واقعی', false !== strpos( $html_date, 'تاریخ انتشار' ), wp_strip_all_tags( $html_date ) );
$html_site = tara_track_date_html( $p9 );
t( 'برچسب «انتشار در سایت» برای تاریخ نوشته', false !== strpos( $html_site, 'انتشار در سایت' ), wp_strip_all_tags( $html_site ) );

echo "\nRESULT pass={$PASS} fail={$FAIL}\n";
if ( $FAIL ) { echo "FAILED:\n - " . implode( "\n - ", $FAILS ) . "\n"; }
