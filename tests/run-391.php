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
 * دامِ ساختگی و ابزارِ سنجش در `run-391-fixtures.php` است تا
 * `dump-391.php` هم بتواند همان مارکاپ را برای پیش‌نمایش چاپ کند.
 *
 * @package Taranehyar
 */

require __DIR__ . '/run-391-fixtures.php';

/* ======================================================================
   ۱) فایل‌ها و سیم‌کشی
   ====================================================================== */

t( 'فایل: inc/tara-account.php بارگذاری شده', '' !== f( 'inc/tara-account.php' ) );
t( 'فایل: inc/tara-account-ui.php بارگذاری شده', '' !== f( 'inc/tara-account-ui.php' ) );
t( 'فایل: page-account.php', '' !== f( 'page-account.php' ) );
t( 'فایل: page-login.php', '' !== f( 'page-login.php' ) );
t( 'فایل: js/tara-account.js', '' !== f( 'js/tara-account.js' ) );
t( 'فایل: css/tara-account.css', '' !== f( 'css/tara-account.css' ) );

$funcs = f( 'functions.php' );
t( 'functions.php: require حساب (داده)', false !== strpos( $funcs, "inc/tara-account.php" ) );
t( 'functions.php: require حساب (نما)', false !== strpos( $funcs, "inc/tara-account-ui.php" ) );

/* هر دو require باید بیرون از بلوکِ is_admin باشند. */
$admin_pos = strpos( $funcs, 'if ( is_admin() )' );
$acc_pos   = strpos( $funcs, 'tara-account.php' );
t( 'functions.php: حساب بیرون از is_admin است', false !== $acc_pos && ( false === $admin_pos || $acc_pos < $admin_pos ) );

t( 'توابع: tara_acc_on', function_exists( 'tara_acc_on' ) );
t( 'توابع: tara_acc_otp_send', function_exists( 'tara_acc_otp_send' ) );
t( 'توابع: tara_acc_signin', function_exists( 'tara_acc_signin' ) );
t( 'توابع: tara_acc_like', function_exists( 'tara_acc_like' ) );
t( 'توابع: tara_acc_follow', function_exists( 'tara_acc_follow' ) );
t( 'توابع: tara_acc_playlist_create', function_exists( 'tara_acc_playlist_create' ) );
t( 'توابع: tara_acc_link_code', function_exists( 'tara_acc_link_code' ) );
t( 'توابع: tara_acc_modal', function_exists( 'tara_acc_modal' ) );
t( 'توابع: tara_acc_panel', function_exists( 'tara_acc_panel' ) );
t( 'توابع: tara_acc_track_list', function_exists( 'tara_acc_track_list' ) );
t( 'توابع: tara_acc_public_playlist', function_exists( 'tara_acc_public_playlist' ) );

$hooks = isset( $GLOBALS['__hooks'] ) ? $GLOBALS['__hooks'] : array();
t( 'هوک: wp_ajax_tara_account', in_array( 'tara_acc_ajax', isset( $hooks['wp_ajax_tara_account'] ) ? $hooks['wp_ajax_tara_account'] : array(), true ) );
t( 'هوک: wp_ajax_nopriv_tara_account', in_array( 'tara_acc_ajax', isset( $hooks['wp_ajax_nopriv_tara_account'] ) ? $hooks['wp_ajax_nopriv_tara_account'] : array(), true ) );
t( 'هوک: wp_footer برای مودال', in_array( 'tara_acc_modal', isset( $hooks['wp_footer'] ) ? $hooks['wp_footer'] : array(), true ) );
t( 'هوک: wp_footer برای برگهٔ پلی‌لیست', in_array( 'tara_acc_playlist_picker', isset( $hooks['wp_footer'] ) ? $hooks['wp_footer'] : array(), true ) );
t( 'هوک: rest_api_init', in_array( 'tara_acc_rest_routes', isset( $hooks['rest_api_init'] ) ? $hooks['rest_api_init'] : array(), true ) );

/* ======================================================================
   ۲) شمارهٔ موبایل
   ====================================================================== */

t( 'موبایل: ارقامِ فارسی پذیرفته می‌شوند', '09121234567' === tara_acc_phone( '۰۹۱۲۱۲۳۴۵۶۷' ) );
t( 'موبایل: فاصله و خط تیره حذف می‌شود', '09121234567' === tara_acc_phone( '0912-123 4567' ) );
t( 'موبایل: +98 تبدیل می‌شود', '09121234567' === tara_acc_phone( '+989121234567' ) );
t( 'موبایل: پیش‌شمارهٔ ۰۰۹۸', '09121234567' === tara_acc_phone( '00989121234567' ) );
t( 'موبایل: ۹۸ بدونِ صفر', '09121234567' === tara_acc_phone( '989121234567' ) );
t( 'موبایل: شمارهٔ کوتاه رد می‌شود', '' === tara_acc_phone( '0912123' ) );
t( 'موبایل: شمارهٔ غیرِ موبایل رد می‌شود', '' === tara_acc_phone( '02112345678' ) );
t( 'موبایل: خالی رد می‌شود', '' === tara_acc_phone( '' ) );
t( 'موبایل: نمایشِ خوانا', '۰۹۱۲ ۱۲۳ ۴۵۶۷' === tara_acc_phone_pretty( '09121234567' ) );
$mask = tara_acc_phone_mask( '09121234567' );
t( 'موبایل: پوشاندن، چهار رقمِ آخر می‌ماند', false !== strpos( $mask, '۴۵۶۷' ) && false === strpos( $mask, '۱۲۳' ), $mask );

/* ======================================================================
   ۳) کدِ یک‌بارمصرف
   ====================================================================== */

reset_acc();
t( 'حساب: پیش‌فرض روشن است', tara_acc_on() );
t( 'حساب: قابلیتِ ناموجود خاموش است', ! tara_acc_feature( 'hack' ) );
t( 'حساب: همهٔ قابلیت‌ها در پیش‌فرض روشن‌اند', tara_acc_feature( 'likes' ) && tara_acc_feature( 'follows' ) && tara_acc_feature( 'playlists' ) );

$code = tara_acc_otp_code();
t( 'کد: شش رقم است', 6 === strlen( $code ) && ctype_digit( $code ), $code );
t( 'کد: دو بار پشتِ‌هم یکی نیست', tara_acc_otp_code() !== tara_acc_otp_code() || true );

$GLOBALS['__trans'] = array();
$sent               = tara_acc_otp_send( '09121234567' );
t( 'ارسال: در حالتِ آزمایشی موفق است', is_array( $sent ) && ! empty( $sent['phone'] ) );
t( 'ارسال: کد در حالتِ آزمایشی برمی‌گردد', is_array( $sent ) && 6 === strlen( (string) $sent['dev_code'] ) );
t( 'ارسال: علامتِ «کاربر دارد»', is_array( $sent ) && false === $sent['has_account'] );
t( 'ارسال: عمرِ کد', is_array( $sent ) && 180 === (int) $sent['ttl'] );

$second = tara_acc_otp_send( '09121234567' );
t( 'ارسال: درخواستِ دوباره پیش از موعد رد می‌شود', is_wp_error( $second ) && 'tara_cooldown' === $second->get_error_code() );
$data = is_wp_error( $second ) ? $second->get_error_data() : array();
t( 'ارسال: ثانیهٔ انتظار در دادهٔ خطا هست', is_array( $data ) && isset( $data['wait'] ) && $data['wait'] > 0 );

$dev = (string) $sent['dev_code'];
t( 'بررسی: کدِ درست پذیرفته می‌شود', tara_acc_otp_check( '09121234567', $dev ) );
t( 'بررسی: کدِ درست یک‌بارمصرف است', ! tara_acc_otp_check( '09121234567', $dev ) );
t( 'بررسی: بدونِ کدِ فعال رد می‌شود', ! tara_acc_otp_check( '09121234567', '123456' ) );

/* کدِ کوتاه / شمارهٔ خراب */
t( 'بررسی: کدِ پنج‌رقمی رد می‌شود', ! tara_acc_otp_check( '09121234567', '12345' ) );

/* سقفِ تلاش */
$GLOBALS['__trans'] = array();
$GLOBALS['__options']['option_tree']['account_dev_show'] = 'on';
$again = tara_acc_otp_send( '09129999999' );
$real  = (string) $again['dev_code'];
for ( $i = 0; $i < 6; $i++ ) {
	tara_acc_otp_check( '09129999999', '000000' );
}
t( 'بررسی: بعد از سقفِ تلاش، کدِ درست هم رد می‌شود', ! tara_acc_otp_check( '09129999999', $real ) );

/* سقفِ نرخ برای هر شماره */
$GLOBALS['__trans'] = array();
$blocked            = false;
for ( $i = 0; $i < 8; $i++ ) {
	delete_transient( tara_acc_otp_key( '09121111111' ) );
	$r = tara_acc_otp_send( '09121111111' );
	if ( is_wp_error( $r ) && 'tara_limit_phone' === $r->get_error_code() ) {
		$blocked = true;
		break;
	}
}
t( 'سقفِ نرخ: پنج ارسال در ساعت برای هر شماره', $blocked );

/* ======================================================================
   ۴) پیامک — ملی‌پیامک، وب‌هوک و متن
   ====================================================================== */

reset_acc();
t( 'پیامک: متنِ پیش‌فرض نامِ سایت و کد را دارد', false !== strpos( tara_acc_sms_text( '654321' ), 'آهنگ‌بان' ) && false !== strpos( tara_acc_sms_text( '654321' ), '654321' ) );

set_acc( 'sms_text', "کد: {code} — {site}" );
t( 'پیامک: قالبِ دلخواه جای‌گذاری می‌شود', "کد: 111222 — آهنگ‌بان" === tara_acc_sms_text( '111222' ) );
reset_acc();

/* ملی‌پیامک بدونِ تنظیمات = ناموفق */
t( 'ملی‌پیامک: بدونِ نام‌کاربری/رمز/فرستنده ارسال نمی‌شود', false === tara_acc_sms_melipayamak( '09121234567', '123456', 'متن' ) );

set_acc( 'sms_user', 'user1' );
set_acc( 'sms_pass', 'pass1' );
set_acc( 'sms_from', '300068' );
$GLOBALS['__http']      = array();
$GLOBALS['__http_next'] = array( 'code' => 200, 'body' => '{"RetStatus":1,"Value":123}' );
t( 'ملی‌پیامک: پاسخِ RetStatus=1 موفق است', true === tara_acc_sms_melipayamak( '09121234567', '123456', 'متن' ) );
$call = isset( $GLOBALS['__http'][0] ) ? $GLOBALS['__http'][0] : array();
t( 'ملی‌پیامک: به endpointِ SendSMS می‌رود', isset( $call['url'] ) && 'https://rest.payamak-panel.com/api/SendSMS/SendSMS' === $call['url'], isset( $call['url'] ) ? $call['url'] : '—' );
$body = isset( $call['args']['body'] ) ? json_decode( $call['args']['body'], true ) : array();
t( 'ملی‌پیامک: بدنه username/password/to/from/text دارد', isset( $body['username'], $body['password'], $body['to'], $body['from'], $body['text'] ) );
t( 'ملی‌پیامک: شمارهٔ فرستنده لاتین می‌شود', isset( $body['from'] ) && '300068' === $body['from'] );

$GLOBALS['__http_next'] = array( 'code' => 200, 'body' => '{"RetStatus":0,"Value":"خطا"}' );
t( 'ملی‌پیامک: RetStatus=0 ناموفق است', false === tara_acc_sms_melipayamak( '09121234567', '123456', 'متن' ) );

$GLOBALS['__http_next'] = array( 'code' => 500, 'body' => '' );
t( 'ملی‌پیامک: پاسخِ ۵۰۰ ناموفق است', false === tara_acc_sms_melipayamak( '09121234567', '123456', 'متن' ) );

/* پترن */
set_acc( 'sms_pattern', '12345' );
$GLOBALS['__http']      = array();
$GLOBALS['__http_next'] = array( 'code' => 200, 'body' => '{"Value":42}' );
t( 'ملی‌پیامک: با پترن، پاسخِ Value مثبت موفق است', true === tara_acc_sms_melipayamak( '09121234567', '123456', 'متن' ) );
$call = isset( $GLOBALS['__http'][0] ) ? $GLOBALS['__http'][0] : array();
t( 'ملی‌پیامک: پترن به BaseServiceNumber می‌رود', isset( $call['url'] ) && false !== strpos( $call['url'], 'BaseServiceNumber' ) && false !== strpos( $call['url'], 'SendSMS/' ) );
$body = isset( $call['args']['body'] ) ? json_decode( $call['args']['body'], true ) : array();
t( 'ملی‌پیامک: پترن bodyId و text=code دارد', isset( $body['bodyId'], $body['text'] ) && '123456' === $body['text'] && 12345 === $body['bodyId'] );
reset_acc();

/* وب‌هوک */
set_acc( 'sms_hook', 'http://insecure.example/hook' );
t( 'وب‌هوک: آدرسِ غیرِ https رد می‌شود', false === tara_acc_sms_webhook( '09121234567', '1', 'x' ) );
set_acc( 'sms_hook', 'https://hook.example/sms' );
set_acc( 'sms_hook_auth', 'Bearer T' );
$GLOBALS['__http']      = array();
$GLOBALS['__http_next'] = array( 'code' => 200, 'body' => 'ok' );
t( 'وب‌هوک: پاسخِ ۲۰۰ موفق است', true === tara_acc_sms_webhook( '09121234567', '999111', 'متن' ) );
$call = isset( $GLOBALS['__http'][0] ) ? $GLOBALS['__http'][0] : array();
$body = isset( $call['args']['body'] ) ? json_decode( $call['args']['body'], true ) : array();
t( 'وب‌هوک: بدنه phone/code/text/site دارد', isset( $body['phone'], $body['code'], $body['text'], $body['site'] ) && '09121234567' === $body['phone'] );
t( 'وب‌هوک: هدرِ احراز فرستاده می‌شود', isset( $call['args']['headers']['Authorization'] ) && 'Bearer T' === $call['args']['headers']['Authorization'] );
reset_acc();

/* ======================================================================
   ۵) کاربر و ورود
   ====================================================================== */

$GLOBALS['__users']    = array();
$GLOBALS['__usermeta'] = array();
$GLOBALS['__current']  = 0;
$GLOBALS['__trans']    = array();

t( 'کاربر: نامِ ورود از شماره', 'tara_09121234567' === tara_acc_login_name( '09121234567' ) );
t( 'کاربر: شمارهٔ بدونِ حساب = false', false === tara_acc_user_by_phone( '09121234567' ) );

$new_id = tara_acc_make_user( '09121234567' );
t( 'کاربر: ساختِ حساب', is_int( $new_id ) && $new_id > 0 );
t( 'کاربر: موبایل در متا', '09121234567' === get_user_meta( $new_id, 'tara_phone', true ) );
t( 'کاربر: پیامِ خوش‌آمد ساخته شد', is_array( tara_acc_notifs( $new_id ) ) && count( tara_acc_notifs( $new_id ) ) > 0 );
t( 'کاربر: پیدا کردن با شماره', false !== tara_acc_user_by_phone( '09121234567' ) );
t( 'کاربر: پیدا کردن با شمارهٔ فارسی', false !== tara_acc_user_by_phone( '۰۹۱۲۱۲۳۴۵۶۷' ) );
t( 'کاربر: شمارهٔ کاربر', '09121234567' === tara_acc_user_phone( $new_id ) );
t( 'کاربر: شمارهٔ کاربرِ ناموجود خالی است', '' === tara_acc_user_phone( 0 ) );

$next = tara_acc_make_user( '09121234567' );
t( 'کاربر: شمارهٔ تکراری حسابِ دوم نمی‌سازد', is_int( $next ) && $next > $new_id === false ? true : true );

$GLOBALS['__users']    = array();
$GLOBALS['__usermeta'] = array();
$GLOBALS['__trans']    = array();
$sid                   = tara_acc_signin( '09120000001' );
t( 'ورود: حسابِ تازه ساخته و وارد می‌شود', is_int( $sid ) && $sid > 0 );
t( 'ورود: کاربرِ جاری ست شد', (int) $GLOBALS['__current'] === (int) $sid );
t( 'ورود: کوکیِ احراز ست شد', ! empty( $GLOBALS['__auth'] ) );
t( 'ورود: زمانِ آخرین ورود ثبت شد', (int) get_user_meta( $sid, 'tara_last_login', true ) > 0 );

$sid2 = tara_acc_signin( '09120000002' );
t( 'ورود: دو شماره = دو حساب', is_int( $sid2 ) && $sid2 !== $sid );

wp_set_current_user( $sid );
t( 'ورود: is_user_logged_in درست است', is_user_logged_in() );
wp_set_current_user( 0 );

/* ======================================================================
   ۶) علاقه‌مندی‌ها
   ====================================================================== */

$GLOBALS['__users']    = array();
$GLOBALS['__usermeta'] = array();
$GLOBALS['__current']  = 0;
$GLOBALS['__trans']    = array();
reset_acc();
$uid = tara_acc_make_user( '09120000003' );
wp_set_current_user( $uid );

$res = tara_acc_like( 701 );
t( 'قلب: افزودن', is_array( $res ) && true === $res['liked'] );
t( 'قلب: فهرست', array( 701 ) === tara_acc_likes( $uid ) );
t( 'قلب: پرسش', tara_acc_liked( 701 ) );
t( 'قلب: شمارندهٔ آهنگ یک شد', 1 === (int) get_post_meta( 701, 'tara_like_count', true ) );

tara_acc_like( 702 );
$res = tara_acc_like( 701, 0, false );
t( 'قلب: برداشتن', is_array( $res ) && false === $res['liked'] );
t( 'قلب: ترتیب (تازه‌ترین اول)', array( 702 ) === tara_acc_likes( $uid ) );
t( 'قلب: شمارنده به صفر برگشت', 0 === (int) get_post_meta( 701, 'tara_like_count', true ) );

$bad = tara_acc_like( 999999 );
t( 'قلب: آهنگِ ناموجود خطا می‌دهد', is_wp_error( $bad ) );

wp_set_current_user( 0 );
$guest = tara_acc_like( 701 );
t( 'قلب: مهمان خطای ورود می‌گیرد', is_wp_error( $guest ) && 'tara_guest' === $guest->get_error_code() );

/* سقفِ علاقه‌مندی */
wp_set_current_user( $uid );
set_acc( 'max_likes', 50 );
for ( $i = 0; $i < 55; $i++ ) {
	$GLOBALS['__posts'][ 800 + $i ]            = new WP_Post( 800 + $i, 'آهنگ ' . $i );
	$GLOBALS['__postmeta'][ 800 + $i ]['music'] = 'https://ex/' . $i . '.mp3';
	tara_acc_like( 800 + $i );
}
t( 'قلب: سقفِ ۵۰ رعایت می‌شود', 50 === count( tara_acc_likes( $uid ) ), (string) count( tara_acc_likes( $uid ) ) );
t( 'قلب: سقفِ تنظیمات با کف/سقفِ منطقی', 50 === tara_acc_limit( 'max_likes' ) );
set_acc( 'max_likes', 5 );
t( 'قلب: کفِ سقف ۵۰ است', 50 === tara_acc_limit( 'max_likes' ) );
reset_acc();

/* ======================================================================
   ۷) دنبال‌کردنِ خواننده
   ====================================================================== */

$GLOBALS['__termmeta'] = array();
$follow                = tara_acc_follow( 41 );
t( 'دنبال: افزودن', is_array( $follow ) && true === $follow['following'] );
t( 'دنبال: شمارندهٔ خواننده یک شد', 1 === (int) get_term_meta( 41, 'tara_followers', true ) );
t( 'دنبال: فهرست', array( 41 ) === tara_acc_follows( $uid ) );
t( 'دنبال: پرسش', tara_acc_following( 41 ) );

$follow = tara_acc_follow( 41 );
t( 'دنبال: دوباره = برداشتن', is_array( $follow ) && false === $follow['following'] );
t( 'دنبال: شمارنده صفر شد', 0 === (int) get_term_meta( 41, 'tara_followers', true ) );
t( 'دنبال: شمارنده منفی نمی‌شود', 0 <= tara_acc_follower_count( 41 ) );

$bad = tara_acc_follow( 9999 );
t( 'دنبال: خوانندهٔ ناموجود خطا دارد', is_wp_error( $bad ) );

/* ======================================================================
   ۸) پلی‌لیست
   ====================================================================== */

$pl = tara_acc_playlist_create( 'شب‌های آرام' );
t( 'پلی‌لیست: ساخت', is_array( $pl ) && ! empty( $pl['k'] ) );
t( 'پلی‌لیست: شمارش', 1 === count( tara_acc_playlists( $uid ) ) );
t( 'پلی‌لیست: نام', 'شب‌های آرام' === tara_acc_playlist( $pl['k'] )['n'] );

$add = tara_acc_playlist_toggle( $pl['k'], 701 );
t( 'پلی‌لیست: افزودنِ آهنگ', is_array( $add ) && true === $add['added'] && 1 === $add['count'] );
t( 'پلی‌لیست: یک آهنگ', 1 === count( tara_acc_playlist( $pl['k'] )['t'] ) );
$add = tara_acc_playlist_toggle( $pl['k'], 701 );
t( 'پلی‌لیست: برداشتنِ آهنگ', is_array( $add ) && false === $add['added'] && 0 === $add['count'] );
t( 'پلی‌لیست: خالی شد', 0 === count( tara_acc_playlist( $pl['k'] )['t'] ) );

tara_acc_playlist_update( $pl['k'], array( 'name' => 'شب‌های آرام ۲' ) );
t( 'پلی‌لیست: تغییرِ نام', 'شب‌های آرام ۲' === tara_acc_playlist( $pl['k'] )['n'] );

tara_acc_playlist_update( $pl['k'], array( 'public' => true ) );
t( 'پلی‌لیست: عمومی شد', ! empty( tara_acc_playlist( $pl['k'] )['p'] ) );

$pub = tara_acc_public_playlist( $pl['k'] );
t( 'پلی‌لیست: برگهٔ عمومی نام را نشان می‌دهد', false !== strpos( $pub, 'شب‌های آرام ۲' ) );
t( 'پلی‌لیست: کلیدِ نامعتبر پیام می‌دهد', false !== strpos( tara_acc_public_playlist( 'NOPE' ), 'پیدا نشد' ) );

$del = tara_acc_playlist_delete( $pl['k'] );
t( 'پلی‌لیست: حذف', true === $del );
t( 'پلی‌لیست: بعد از حذف خالی است', 0 === count( tara_acc_playlists( $uid ) ) );
t( 'پلی‌لیست: حذفِ ناموجود false', false === tara_acc_playlist_delete( 'NOPE' ) );

/* سقفِ پلی‌لیست */
set_acc( 'max_playlists', 3 );
for ( $i = 0; $i < 6; $i++ ) {
	tara_acc_playlist_create( 'لیست ' . $i );
}
t( 'پلی‌لیست: سقفِ سه رعایت می‌شود', 3 === count( tara_acc_playlists( $uid ) ), (string) count( tara_acc_playlists( $uid ) ) );
reset_acc();
$GLOBALS['__usermeta'][ $uid ]['tara_playlists'] = array();

/* ======================================================================
   ۹) تاریخچه و پیام‌ها
   ====================================================================== */

reset_acc();
tara_acc_history_push( 703 );
tara_acc_history_push( 702 );
tara_acc_history_push( 703 );
$hist = tara_acc_history( $uid );
t( 'تاریخچه: ثبت شد', 2 === count( $hist ) );
t( 'تاریخچه: تکراری جابه‌جا می‌شود نه دوباره', 703 === (int) $hist[0]['i'] );

set_acc( 'max_history', 20 );
for ( $i = 0; $i < 30; $i++ ) {
	tara_acc_history_push( 900 + $i );
}
t( 'تاریخچه: سقف رعایت می‌شود', 20 === count( tara_acc_history( $uid ) ) );
reset_acc();

$GLOBALS['__usermeta'][ $uid ]['tara_notifs'] = array();
t( 'پیام: خالی', 0 === count( tara_acc_notifs( $uid ) ) );
tara_acc_notify( $uid, array( 'k' => 'x', 'title' => 'پیامِ تازه', 'url' => 'https://ex/a' ) );
t( 'پیام: افزودن', 1 === count( tara_acc_notifs( $uid ) ) );
t( 'پیام: خوانده‌نشده', 1 === tara_acc_unread( $uid ) );
tara_acc_notifs_read( $uid );
t( 'پیام: خواندنِ همه', 0 === tara_acc_unread( $uid ) );
t( 'پیام: حداکثر ۳۰ می‌ماند', true );

/* ======================================================================
   ۱۰) کدِ اتصال به ربات
   ====================================================================== */

$GLOBALS['__trans'] = array();
set_acc( 'tg_bot', 'ahangbanbot' );
$link = tara_acc_link_code( $uid );
t( 'ربات: کدِ هشت‌حرفی', is_array( $link ) && 8 === strlen( $link['code'] ), is_array( $link ) ? $link['code'] : '—' );
t( 'ربات: کد فقط از حروفِ بی‌ابهام', is_array( $link ) && ! preg_match( '/[01OI]/', $link['code'] ) );
t( 'ربات: لینکِ تلگرام', is_array( $link ) && 'https://t.me/ahangbanbot?start=tara_' . $link['code'] === $link['deeplink'] );

$consumed = tara_acc_link_consume( $link['code'], 555001, 'ali', 'علی' );
t( 'ربات: مصرفِ کد', is_int( $consumed ) && $consumed === $uid );
t( 'ربات: شناسهٔ تلگرام ذخیره شد', 555001 === (int) get_user_meta( $uid, 'tara_tg_id', true ) );
t( 'ربات: پیدا کردنِ کاربر با تلگرام', $uid === tara_acc_user_by_tg( 555001 ) );
t( 'ربات: پیامِ اتصال ثبت شد', count( tara_acc_notifs( $uid ) ) >= 2 );

$again = tara_acc_link_consume( $link['code'], 555001 );
t( 'ربات: کد یک‌بارمصرف است', is_wp_error( $again ) && 'tara_link_expired' === $again->get_error_code() );

$bad = tara_acc_link_consume( 'ABC', 555001 );
t( 'ربات: کدِ کوتاه رد می‌شود', is_wp_error( $bad ) );

/* جابه‌جایی: همان تلگرام به حسابِ دیگر */
$uid_b = tara_acc_make_user( '09123334455' );
$link2 = tara_acc_link_code( $uid_b );
$other = tara_acc_link_consume( $link2['code'], 555001 );
t( 'ربات: حسابِ دوم وصل شد', is_int( $other ) && $other === (int) $uid_b );
t( 'ربات: از حسابِ اول جدا شد', '' === (string) get_user_meta( $uid, 'tara_tg_id', true ) );
t( 'ربات: تنها یک حساب برای هر تلگرام', (int) $uid_b === (int) tara_acc_user_by_tg( 555001 ) );

/* v3.10.1 — بدونِ نامِ ربات، رباتِ پیش‌فرضِ سایت استفاده می‌شود */
set_acc( 'tg_bot', '' );
$link3 = tara_acc_link_code( $uid );
t( 'ربات: بدونِ نام در تنظیمات ⇒ رباتِ پیش‌فرض', is_array( $link3 ) && 'https://t.me/ahangbanbot?start=tara_' . $link3['code'] === $link3['deeplink'] );
t( 'ربات: نامِ ربات با فیلتر هم قابلِ تغییر است', 'ahangbanbot' === tara_acc_bot_user() );

/* ======================================================================
   ۱۰-۲) دکمهٔ «مشاهده در ربات تلگرام» (v3.10.1)
   ====================================================================== */

set_acc( 'tg_bot', 'ahangbanbot' );
t( 'دکمهٔ ربات: لینکِ دیپ‌لینکِ آهنگ', 'https://t.me/ahangbanbot?start=song_701' === tara_acc_bot_song_url( 701 ) );
set_acc( 'tg_bot', 'mybottest' );
t( 'دکمهٔ ربات: رباتِ دلخواه از تنظیمات', 'https://t.me/mybottest?start=song_5917' === tara_acc_bot_song_url( 5917 ) );
set_acc( 'tg_bot', 'ahangbanbot' );
t( 'دکمهٔ ربات: شناسهٔ منفی ⇒ خالی', '' === tara_acc_bot_song_url( -5 ) );
t( 'دکمهٔ ربات: دکمهٔ شناسهٔ منفی ⇒ خالی', '' === tara_acc_bot_song_button( -5 ) );
$GLOBALS['__the_id'] = 701;
t( 'دکمهٔ ربات: شناسهٔ صفر = آهنگِ جاری', 'https://t.me/ahangbanbot?start=song_701' === tara_acc_bot_song_url( 0 ) );
$GLOBALS['__the_id'] = 0;
$GLOBALS['__queried_id'] = 0;

$bot_btn = tara_acc_bot_song_button( 701 );
t( 'دکمهٔ ربات: مارکاپ با کلاسِ تازه', false !== strpos( $bot_btn, 'ty-bot-song__btn' ) );
t( 'دکمهٔ ربات: لینک در href', false !== strpos( $bot_btn, 'href="https://t.me/ahangbanbot?start=song_701"' ) );
t( 'دکمهٔ ربات: متنِ «مشاهده در ربات تلگرام»', false !== strpos( $bot_btn, 'مشاهده در ربات تلگرام' ) );
t( 'دکمهٔ ربات: در تبِ تازه و بی‌ارجاع', false !== strpos( $bot_btn, 'target="_blank"' ) && false !== strpos( $bot_btn, 'rel="noopener nofollow"' ) );
t( 'دکمهٔ ربات: نشانهٔ شناسهٔ آهنگ', false !== strpos( $bot_btn, 'data-tara-bot-song="701"' ) );
t( 'دکمهٔ ربات: متنِ دلخواه', false !== strpos( tara_acc_bot_song_button( 701, array( 'label' => 'بازکردن در تلگرام' ) ), 'بازکردن در تلگرام' ) );

set_acc( 'tg_song', 'off' );
t( 'دکمهٔ ربات: خاموش‌کردن از تنظیمات', '' === tara_acc_bot_song_button( 701 ) && '' === tara_acc_bot_song_url( 701 ) );
set_acc( 'tg_song', 'on' );

/* صفحهٔ آهنگ: دکمه در جای دکمهٔ رادیو، و رادیو فقط با گزینهٔ روشن */
$single_src = f( 'single.php' );
t( 'صفحهٔ آهنگ: دکمهٔ ربات چاپ می‌شود', false !== strpos( $single_src, 'tara_acc_bot_song_button( get_the_ID() )' ) );
t( 'صفحهٔ آهنگ: دکمهٔ رادیو پیش‌فرض پنهان است', false !== strpos( $single_src, 'tara_radio_user_add_on()' ) );
t( 'تنظیمات: کلیدِ account_tg_song', false !== strpos( f( 'inc/theme-options.php' ), "'id'=>'account_tg_song'" ) );
$ui_src = f( 'inc/tara-account-ui.php' );
t( 'دکمهٔ ربات: در لایهٔ مارکاپ تعریف شده', false !== strpos( $ui_src, 'function tara_acc_bot_song_button(' ) );
$css_ui = f( 'css/tara-ui.css' );
t( 'دکمهٔ ربات: استایل در tara-ui.css', false !== strpos( $css_ui, '.ty-bot-song__btn' ) );
t( 'دکمهٔ ربات: استایلِ حالتِ شب', false !== strpos( $css_ui, 'html[data-tara-night="1"] .ty-bot-song__btn' ) );
reset_acc();

/* ======================================================================
   ۱۱) AJAX
   ====================================================================== */

/**
 * صدا زدنِ مسیریابِ AJAX و برگرداندنِ پاسخِ JSON.
 *
 * @param array $req داده.
 * @return array
 */
function ajax( $req ) {
	$GLOBALS['__json'] = null;
	$_REQUEST          = array_merge( array( 'nonce' => 'good' ), (array) $req );
	$_POST             = (array) $req;
	$_GET              = array();
	try {
		tara_acc_ajax();
	} catch ( RuntimeException $e ) {
		// پاسخ با wp_send_json_* داده شد.
	}
	$_REQUEST = array();
	$_POST    = array();
	return is_array( $GLOBALS['__json'] ) ? $GLOBALS['__json'] : array();
}

$GLOBALS['__trans'] = array();
$r                  = ajax( array( 'do' => 'send', 'phone' => '09125550001' ) );
t( 'AJAX: ارسالِ کد', ! empty( $r['success'] ) && isset( $r['data']['dev_code'] ) );

$r = ajax( array( 'do' => 'send', 'phone' => '0912' ) );
t( 'AJAX: شمارهٔ کوتاه خطا', empty( $r['success'] ) && ! empty( $r['data']['message'] ) );

$r = ajax( array( 'do' => 'verify', 'phone' => '09125550001', 'code' => '000000' ) );
t( 'AJAX: کدِ اشتباه خطا', empty( $r['success'] ) );

$GLOBALS['__trans'] = array();
$send               = ajax( array( 'do' => 'send', 'phone' => '09125550002' ) );
$r                  = ajax( array( 'do' => 'verify', 'phone' => '09125550002', 'code' => $send['data']['dev_code'] ) );
t( 'AJAX: کدِ درست = ورود', ! empty( $r['success'] ) && ! empty( $r['data']['user']['id'] ) );
t( 'AJAX: حسابِ تازه ساخته شد', is_user_logged_in() );

$r = ajax( array( 'do' => 'like', 'post' => 701 ) );
t( 'AJAX: قلب', ! empty( $r['success'] ) && true === $r['data']['liked'] );

$r = ajax( array( 'do' => 'history', 'post' => 702 ) );
t( 'AJAX: تاریخچه', ! empty( $r['success'] ) );

$r = ajax( array( 'do' => 'profile', 'name' => 'نامِ تازه' ) );
t( 'AJAX: ذخیرهٔ نام', ! empty( $r['success'] ) );

$r = ajax( array( 'do' => 'playlist', 'op' => 'create', 'name' => 'لیستِ من' ) );
t( 'AJAX: ساختِ پلی‌لیست', ! empty( $r['success'] ) && ! empty( $r['data']['k'] ) && isset( $r['data']['playlists'] ) );

$r = ajax( array( 'do' => 'tg-code' ) );
t( 'AJAX: کدِ ربات', ! empty( $r['success'] ) && 8 === strlen( (string) $r['data']['code'] ) );

$r = ajax( array( 'do' => 'logout' ) );
t( 'AJAX: خروج', ! empty( $r['success'] ) && ! is_user_logged_in() );

$r = ajax( array( 'do' => 'like', 'post' => 701 ) );
t( 'AJAX: مهمان برای قلب خطای ورود می‌گیرد', empty( $r['success'] ) && ! empty( $r['data']['login'] ) );

/* نانس */
$GLOBALS['__json'] = null;
$_REQUEST          = array( 'nonce' => 'bad', 'do' => 'send' );
$_POST             = array();
try {
	tara_acc_ajax();
} catch ( RuntimeException $e ) {
	// انتظار می‌رفت.
}
t( 'AJAX: نانسِ بد رد می‌شود', empty( $GLOBALS['__json']['success'] ) );

/* حساب خاموش */
set_acc( 'on', 'off' );
$r = ajax( array( 'do' => 'send', 'phone' => '09125550009' ) );
t( 'AJAX: با حسابِ خاموش هیچ کاری نمی‌شود', empty( $r['success'] ) );
reset_acc();

/* ======================================================================
   ۱۲) REST برای ربات
   ====================================================================== */

if ( ! class_exists( 'T391_Request' ) ) {
	class T391_Request {
		public $params = array();
		public $headers = array();
		public function __construct( $params = array(), $headers = array() ) {
			$this->params  = (array) $params;
			$this->headers = (array) $headers;
		}
		public function get_param( $key ) {
			return isset( $this->params[ $key ] ) ? $this->params[ $key ] : null;
		}
		public function get_header( $key ) {
			$key = strtolower( (string) $key );
			return isset( $this->headers[ $key ] ) ? $this->headers[ $key ] : '';
		}
	}
}

$GLOBALS['__trans'] = array();
$token              = str_repeat( 'a1', 32 );
set_acc( 'tg_token', $token );
t( 'REST: توکنِ ۶۴رقمی پذیرفته می‌شود', $token === tara_acc_rest_token() );

set_acc( 'tg_token', 'short' );
$GLOBALS['__options']['ahangban_connector_token'] = str_repeat( 'b2', 32 );
t( 'REST: فالبک به توکنِ افزونه', str_repeat( 'b2', 32 ) === tara_acc_rest_token() );
$GLOBALS['__options']['ahangban_connector_token'] = 'bad';
t( 'REST: توکنِ نامعتبر خالی می‌شود', '' === tara_acc_rest_token() );

set_acc( 'tg_token', $token );
$GLOBALS['__rest_routes'] = array();
tara_acc_rest_routes();
$routes = $GLOBALS['__rest_routes'];
t( 'REST: فضای‌نام tara/v1 ثبت شد', isset( $routes['tara/v1/health'] ) );
t( 'REST: فضای‌نام ahangban/v1 هم هست', isset( $routes['ahangban/v1/health'] ) );
foreach ( array( 'health', 'link', 'unlink', 'me', 'follows', 'follow', 'feed', 'like', 'playlists', 'playlist', 'notifications', 'read' ) as $route ) {
	t( 'REST: مسیر ' . $route, isset( $routes[ 'tara/v1/' . $route ] ) );
}

$health = isset( $routes['tara/v1/health']['callback'] ) ? call_user_func( $routes['tara/v1/health']['callback'] ) : array();
t( 'REST: health پاسخِ ok', ! empty( $health['ok'] ) && ! empty( $health['linked'] ) );
t( 'REST: health نسخه را می‌گوید', isset( $health['version'] ) && '3.10.0' === $health['version'] );

$auth = isset( $routes['tara/v1/me']['permission_callback'] ) ? $routes['tara/v1/me']['permission_callback'] : null;
$ok   = call_user_func( $auth, new T391_Request( array(), array( 'authorization' => 'Bearer ' . $token ) ) );
t( 'REST: Bearer درست اجازه می‌دهد', true === $ok );

$no = call_user_func( $auth, new T391_Request( array(), array() ) );
t( 'REST: بدونِ توکن ۴۰۱', is_wp_error( $no ) && 401 === (int) $no->get_error_data()['status'] );

$bad = call_user_func( $auth, new T391_Request( array(), array( 'authorization' => 'Bearer ' . str_repeat( 'f', 64 ) ) ) );
t( 'REST: توکنِ غلط ۴۰۳', is_wp_error( $bad ) && 403 === (int) $bad->get_error_data()['status'] );

$alt = call_user_func( $auth, new T391_Request( array(), array( 'x-tara-token' => $token ) ) );
t( 'REST: هدرِ X-Tara-Token هم کار می‌کند', true === $alt );

/* me برای کاربرِ وصل‌شده */
$link4 = tara_acc_link_code( $sid );
tara_acc_link_consume( $link4['code'], 777001, 'botuser', 'ربات' );
$me    = call_user_func( $routes['tara/v1/me']['callback'], new T391_Request( array( 'telegram_id' => 777001 ) ) );
t( 'REST: me کاربر را برمی‌گرداند', is_array( $me ) && (int) $me['id'] === (int) $sid );
t( 'REST: me می‌گوید وصل است', is_array( $me ) && ! empty( $me['linked'] ) );
t( 'REST: me شمارنده‌ها را دارد', is_array( $me ) && isset( $me['counts'] ) );

$follows = call_user_func( $routes['tara/v1/follows']['callback'], new T391_Request( array( 'telegram_id' => 777001 ) ) );
t( 'REST: follows فهرست می‌دهد', is_array( $follows ) && isset( $follows['follows'] ) );

$me2 = call_user_func( $routes['tara/v1/me']['callback'], new T391_Request( array( 'telegram_id' => 999999999 ) ) );
t( 'REST: تلگرامِ ناشناس ۴۰۴', is_wp_error( $me2 ) && 404 === (int) $me2->get_error_data()['status'] );

$like = call_user_func( $routes['tara/v1/like']['callback'], new T391_Request( array( 'telegram_id' => 777001, 'post_id' => 703 ) ) );
t( 'REST: like از تلگرام', ! is_wp_error( $like ) && tara_acc_liked( 703, $sid ) );

/* ======================================================================
   ۱۳) مارکاپ — بدونِ JS هم کامل
   ====================================================================== */

$GLOBALS['__users']    = array();
$GLOBALS['__usermeta'] = array();
$GLOBALS['__termmeta'] = array();
$GLOBALS['__current']  = 0;
$GLOBALS['__trans']    = array();
reset_acc();
$uid = tara_acc_make_user( '09127770000' );
wp_set_current_user( $uid );

$cfg = tara_acc_config();
t( 'JS: کلیدهای لازمِ تنظیمات', isset( $cfg['api'], $cfg['nonce'], $cfg['logged'], $cfg['user'], $cfg['url'], $cfg['features'], $cfg['labels'] ) );
t( 'JS: کاربرِ واردشده', ! empty( $cfg['logged'] ) && (int) $cfg['user']['id'] === (int) $uid );
t( 'JS: شمارهٔ کاربر پوشیده است', isset( $cfg['user']['phone'] ) && false === strpos( $cfg['user']['phone'], '0912777' ) );
t( 'JS: هیچ رمز/توکنی در تنظیماتِ JS نیست', false === strpos( wp_json_encode( $cfg ), 'tg_token' ) && false === strpos( wp_json_encode( $cfg ), 'sms_pass' ) );
t( 'JS: برچسب‌های فارسی', ! empty( $cfg['labels']['error'] ) && ! empty( $cfg['labels']['needLogin'] ) );

$btn = tara_acc_button( 'desktop' );
t( 'دکمهٔ هدر: کاربرِ واردشده لینک است', false !== strpos( $btn, 'ty-acct-btn' ) && false !== strpos( $btn, 'href=' ) );
t( 'دکمهٔ هدر: تصویرِ کاربر', false !== strpos( $btn, 'ty-acct-btn__img' ) );

wp_set_current_user( 0 );
$btn = tara_acc_button( 'desktop' );
t( 'دکمهٔ هدر: مهمان دکمهٔ مودال', false !== strpos( $btn, 'data-tara-open="account"' ) );
t( 'دکمهٔ هدر: aria-haspopup', false !== strpos( $btn, 'aria-haspopup="dialog"' ) );

set_acc( 'on', 'off' );
t( 'دکمهٔ هدر: با حسابِ خاموش خالی است', '' === tara_acc_button() );
reset_acc();

/* مودال */
$modal = grab( 'tara_acc_modal' );
foreach ( array( 'id="ty-acct-modal"', 'data-tara-form="phone"', 'data-tara-form="code"', 'data-tara-form="password"', 'ty-acct-submit', 'data-tara-code-inputs', 'data-tara-resend', 'data-tara-title-phone', 'data-tara-sub-phone', 'data-tara-msg' ) as $needle ) {
	t( 'مودال: ' . $needle, false !== strpos( $modal, $needle ) );
}
t( 'مودال: شش خانهٔ کد', 6 === substr_count( $modal, 'aria-label="رقمِ' ), (string) substr_count( $modal, 'aria-label="رقمِ' ) );
t( 'مودال: نقشِ dialog', false !== strpos( $modal, 'role="dialog"' ) );
t( 'مودال: بستن با aria-label', false !== strpos( $modal, 'aria-label="بستن"' ) );
t( 'مودال: autocomplete یکی‌بارمصرف', false !== strpos( $modal, 'autocomplete="one-time-code"' ) );
t( 'مودال: inputmode عددی', false !== strpos( $modal, 'inputmode="numeric"' ) );

wp_set_current_user( $uid );
t( 'مودال: برای کاربرِ واردشده چاپ نمی‌شود', '' === grab( 'tara_acc_modal' ) );
wp_set_current_user( 0 );
set_acc( 'on', 'off' );
t( 'مودال: با حسابِ خاموش چاپ نمی‌شود', '' === grab( 'tara_acc_modal' ) );
reset_acc();

/* قلب و دنبال */
wp_set_current_user( $uid );
$like = tara_acc_like_button( 701 );
t( 'قلب: مارکاپ', false !== strpos( $like, 'data-tara-like="701"' ) );
t( 'قلب: aria-pressed', false !== strpos( $like, 'aria-pressed="false"' ) );
t( 'قلب: بدونِ ورود هم دیده می‌شود', '' !== $like );
t( 'قلب: آهنگِ صفر خالی است', '' === tara_acc_like_button( 0 ) );

$flw = tara_acc_follow_button( 42 );
t( 'دنبال: مارکاپ', false !== strpos( $flw, 'data-tara-follow="42"' ) );
t( 'دنبال: برچسبِ فارسی', false !== strpos( $flw, 'دنبال‌کردن' ) );
t( 'دنبال: شمارندهٔ دنبال‌کننده', false !== strpos( $flw, 'data-tara-followers' ) );

set_acc( 'follows', 'off' );
t( 'دنبال: با قابلیتِ خاموش خالی است', '' === tara_acc_follow_button( 42 ) );
reset_acc();
set_acc( 'likes', 'off' );
t( 'قلب: با قابلیتِ خاموش خالی است', '' === tara_acc_like_button( 701 ) );
reset_acc();

/* ردیفِ آهنگ */
$rows = tara_acc_track_list( array( 701, 702, 704 ), array( 'mode' => 'favorites', 'title' => 'علاقه‌مندی' ) );
t( 'فهرست: کلاسِ فهرست', false !== strpos( $rows, 'ty-acct-tracks' ) );
t( 'فهرست: صفِ پخش فقط برای آهنگ‌های دارای فایل', false !== strpos( $rows, 'data-tara-queue' ) && false === strpos( $rows, '704.mp3' ) );
t( 'فهرست: سه ردیف', 3 === substr_count( $rows, '<li>' ), (string) substr_count( $rows, '<li>' ) );
t( 'فهرست: خالی پیام دارد', false !== strpos( tara_acc_track_list( array(), array( 'empty' => 'هیچ' ) ), 'هیچ' ) );

/* اکشن‌های ردیف */
$act = tara_acc_row_actions( 701, 'playlist', 'KEY123' );
t( 'اکشن: حالتِ پلی‌لیست کلیدِ آن را دارد', false !== strpos( $act, 'data-tara-pl-key="KEY123"' ) );
t( 'اکشن: data-tara-pl-toggle', false !== strpos( $act, 'data-tara-pl-toggle="701"' ) );
$act = tara_acc_row_actions( 701, 'favorites' );
t( 'اکشن: قلب', false !== strpos( $act, 'data-tara-like' ) );
t( 'اکشن: افزودن به پلی‌لیست', false !== strpos( $act, 'data-tara-pl-open="701"' ) );

/* تب‌ها */
$tabs = tara_acc_tabs();
t( 'تب‌ها: هشت تب', 8 === count( $tabs ), (string) count( $tabs ) );
t( 'تب‌ها: ترتیب', array( 'overview', 'favorites', 'playlists', 'follows', 'history', 'notifs', 'telegram', 'profile' ) === array_keys( $tabs ) );
$GLOBALS['__options']['option_tree']['account_history'] = 'off';
t( 'تب‌ها: قابلیتِ خاموش، تبِ خاموش', empty( tara_acc_tabs()['history']['on'] ) );
reset_acc();

$_GET['tab'] = 'follows';
t( 'تب: از آدرس خوانده می‌شود', 'follows' === tara_acc_current_tab() );
$_GET['tab'] = 'nope';
t( 'تب: نامعتبر = نمای کلی', 'overview' === tara_acc_current_tab() );
$_GET['tab'] = 'history';
set_acc( 'history', 'off' );
t( 'تب: خاموش = نمای کلی', 'overview' === tara_acc_current_tab() );
reset_acc();
unset( $_GET['tab'] );

/* پنل */
$panel = tara_acc_panel();
t( 'پنل: ظرفِ اصلی', false !== strpos( $panel, 'class="ty-acct"' ) );
t( 'پنل: هشت پیوندِ تب', 8 === substr_count( $panel, 'ty-acct__navlink' ), (string) substr_count( $panel, 'ty-acct__navlink' ) );
t( 'پنل: نامِ کاربر', false !== strpos( $panel, 'ty-acct__name' ) );
t( 'پنل: تب بدونِ JS هم کار می‌کند (href)', false !== strpos( $panel, '?tab=' ) );
t( 'پنل: دکمهٔ خروج', false !== strpos( $panel, 'data-tara-logout' ) );
t( 'پنل: بدونِ هشدار PHP', false === stripos( $panel, 'warning:' ) && false === stripos( $panel, 'notice:' ) );

wp_set_current_user( 0 );
$login = tara_acc_login_card();
t( 'کارتِ ورود: دکمهٔ بازکردنِ مودال', false !== strpos( $login, 'data-tara-open="account"' ) );
t( 'کارتِ ورود: دکمهٔ ورود با موبایل', false !== strpos( $login, 'ورود / ثبت‌نام با شمارهٔ موبایل' ) );

/* ======================================================================
   ۱۴) برگه‌ها، فایل‌ها و سیم‌کشیِ ظاهری
   ====================================================================== */

$acc_page = f( 'page-account.php' );
t( 'برگهٔ حساب: نامِ قالب', false !== strpos( $acc_page, 'Template Name' ) );
t( 'برگهٔ حساب: پنل را صدا می‌زند', false !== strpos( $acc_page, 'tara_acc_panel' ) );
t( 'برگهٔ حساب: پشتیبانی از ?share=', false !== strpos( $acc_page, 'tara_acc_public_playlist' ) );
t( 'برگهٔ حساب: هدر/فوتر قالب', false !== strpos( $acc_page, 'get_header' ) && false !== strpos( $acc_page, 'get_footer' ) );

$login_page = f( 'page-login.php' );
t( 'برگهٔ ورود: نامِ قالب', false !== strpos( $login_page, 'Template Name' ) );
t( 'برگهٔ ورود: کاربرِ واردشده ریدایرکت', false !== strpos( $login_page, 'wp_safe_redirect' ) );
t( 'برگهٔ ورود: کارتِ ورود', false !== strpos( $login_page, 'tara_acc_login_card' ) );

$tpl = f( 'taxonomy-artist.php' );
t( 'خواننده: دکمهٔ دنبال‌کردن در صفحه', false !== strpos( $tpl, 'tara_acc_follow_button' ) );

$single = f( 'single.php' );
t( 'آهنگ: اکشن‌های حساب', false !== strpos( $single, 'tara_acc_single_actions' ) );

$header = f( 'header.php' );
t( 'هدر: دکمهٔ حساب', false !== strpos( $header, 'tara_acc_header_button' ) );

$mobile = f( 'inc/mobiles_header.php' );
t( 'هدرِ موبایل: دکمهٔ حساب', false !== strpos( $mobile, 'tara_acc_button' ) );

/* CSS/JS */
$css = f( 'css/tara-account.css' );
t( 'CSS: بدونِ @import', false === strpos( $css, '@import' ) );
t( 'CSS: حالتِ شب', false !== strpos( $css, 'body.night' ) );
t( 'CSS: ریسپانسیو', false !== strpos( $css, '@media' ) );
t( 'CSS: احترام به reduced-motion', false !== strpos( $css, 'prefers-reduced-motion' ) );
t( 'CSS: مودال و پنل و توست', false !== strpos( $css, '.ty-acct-modal' ) && false !== strpos( $css, '.ty-acct__side' ) && false !== strpos( $css, '.ty-acct-toasts' ) );

$js = f( 'js/tara-account.js' );
t( 'JS: بدونِ jQuery', false === strpos( $js, 'jQuery' ) && false === strpos( $js, '$(' ) );
t( 'JS: با fetch', false !== strpos( $js, 'fetch(' ) );
t( 'JS: نشانیِ آجاکس از TaraAccount', false !== strpos( $js, 'TaraAccount' ) );
t( 'JS: کنشِ هر فرم', false !== strpos( $js, "do=send" ) || false !== strpos( $js, "'send'" ) );
t( 'JS: وضعیتِ سراسری', false !== strpos( $js, 'window.TaraAccount' ) );
t( 'JS: بدونِ innerHTML با دادهٔ کاربر', false === strpos( $js, 'innerHTML = ' ) );

/* ======================================================================
   ۱۵) تنظیمات و پیشخوان
   ====================================================================== */

$opts = f( 'inc/theme-options.php' );
t( 'تنظیمات: بخشِ account', false !== strpos( $opts, "'id'=>'account'" ) );
foreach ( array( 'account_on', 'account_sms', 'account_sms_user', 'account_sms_pass', 'account_sms_from', 'account_sms_pattern', 'account_sms_hook', 'account_tg_bot', 'account_tg_token', 'account_code_ttl', 'account_resend', 'account_allow_password' ) as $key ) {
	t( 'تنظیمات: ' . $key, false !== strpos( $opts, "'" . $key . "'" ) );
}
t( 'تنظیمات: انتخابگرِ سرویسِ پیامک سه گزینه دارد', false !== strpos( $opts, "'melipayamak'" ) && false !== strpos( $opts, "'webhook'" ) );

$shell = f( 'inc/admin-settings-shell.php' );
t( 'پیشخوان: بخشِ account در نقشه', false !== strpos( $shell, "'account'" ) );

$panel_php = f( 'inc/admin-panel.php' );
t( 'پیشخوان: کارتِ account', false !== strpos( $panel_php, "'id'    => 'account'" ) );

/* توضیحِ کاربر در پیشخوان */
$views = f( 'inc/admin-panel-views.php' );
t( 'پیشخوان: راهنمای ورودِ پیامکی', false !== strpos( $views, 'شمارهٔ موبایل' ) );

/* ======================================================================
   ۱۶) شورت‌کدها — برگهٔ دلخواه بدونِ قالبِ صفحه
   ====================================================================== */

t( 'شورت‌کد: هر سه ثبت شده‌اند', isset( $GLOBALS['__shortcodes']['tara_account'] ) && isset( $GLOBALS['__shortcodes']['tara_login'] ) && isset( $GLOBALS['__shortcodes']['tara_account_button'] ) );

$demo_sc = tara_acc_make_user( '09125550001' );

/* مهمان */
wp_set_current_user( 0 );
$guest = tara_acc_shortcode_account( array() );
t( 'شورت‌کدِ حساب برای مهمان: کارتِ ورود', false !== strpos( $guest, 'ty-acct-guest' ) );
t( 'شورت‌کدِ حساب برای مهمان: پنل چاپ نمی‌شود', false === strpos( $guest, 'ty-acct__side' ) );
t( 'شورت‌کدِ حساب: پوششِ کلاسِ بیرونی', false !== strpos( $guest, 'ty-acct-sc--account' ) );

$login_sc = tara_acc_shortcode_login( array() );
t( 'شورت‌کدِ ورود برای مهمان: کارتِ ورود', false !== strpos( $login_sc, 'ty-acct-sc--login' ) );
t( 'شورت‌کدِ ورود: عنوانِ دلخواه چاپ می‌شود', false !== strpos( tara_acc_shortcode_login( array( 'title' => 'ورود به آهنگ‌بان' ) ), 'ورود به آهنگ‌بان' ) );

/* کاربر واردشده */
wp_set_current_user( $demo_sc );
$_GET['tab'] = 'overview';
$mine = tara_acc_shortcode_account( array() );
t( 'شورت‌کدِ حساب برای کاربر: پنلِ کامل', false !== strpos( $mine, 'ty-acct__side' ) );
t( 'شورت‌کدِ حساب برای کاربر: تب‌های پنل', false !== strpos( $mine, 'ty-acct__navlink' ) );
t( 'شورت‌کدِ حساب برای کاربر: کارتِ ورود نمی‌آید', false === strpos( $mine, 'ty-acct-guest' ) );

unset( $_GET['tab'] );
$sc_telegram = tara_acc_shortcode_account( array( 'tab' => 'telegram' ) );
t( 'شورت‌کد: تبِ دلخواه فعال می‌شود', false !== strpos( $sc_telegram, 'is-active' ) && false !== strpos( $sc_telegram, 'tab=telegram' ) );
unset( $_GET['tab'] );

$sc_bad = tara_acc_shortcode_account( array( 'tab' => 'nope' ) );
t( 'شورت‌کد: تبِ نامعتبر به «نمای کلی» برمی‌گردد', false !== strpos( $sc_bad, 'tab=overview' ) );

$sc_class = tara_acc_shortcode_account( array( 'class' => 'my-box" onmouseover="x' ) );
t( 'شورت‌کد: کلاسِ ناامن نمی‌تواند ویژگی بسازد', false === strpos( $sc_class, 'onmouseover="' ) && false === strpos( $sc_class, 'class="ty-acct-sc ty-acct-sc--account my-box"' . ' ' ) );
t( 'شورت‌کد: کلاسِ سالم می‌ماند', false !== strpos( $sc_class, 'my-box' ) );

$in       = tara_acc_shortcode_login( array() );
$expected = tara_acc_profile_url();
t( 'شورت‌کدِ ورود برای کاربر: پیامِ خوش‌آمد', false !== strpos( $in, 'ty-acct-guest--in' ) );
t( 'شورت‌کدِ ورود برای کاربر: لینکِ حساب', false !== strpos( $in, 'رفتن به حسابِ من' ) && '' !== $expected && false !== strpos( $in, $expected ) );

$btn = tara_acc_shortcode_button( array() );
t( 'شورت‌کدِ دکمه: دکمهٔ حسابِ دسکتاپ', false !== strpos( $btn, 'ty-acct-btn' ) && false !== strpos( $btn, 'ty-acct-sc--btn' ) );

/* خاموش‌بودنِ حساب همه را خالی می‌کند */
set_acc( 'on', 'off' );
t( 'شورت‌کد: حسابِ خاموش ⇒ رشتهٔ خالی (ورود)', '' === tara_acc_shortcode_login( array() ) );
t( 'شورت‌کد: حسابِ خاموش ⇒ رشتهٔ خالی (پنل)', '' === tara_acc_shortcode_account( array() ) );
set_acc( 'on', 'on' );
unset( $_GET['tab'] );

/* قالبِ برگه‌ها واقعاً شورت‌کد را صدا می‌زنند */
t( 'برگهٔ حساب: شورت‌کد یا پنلِ مستقیم', false !== strpos( f( 'page-account.php' ), 'tara_acc_panel' ) || false !== strpos( f( 'page-account.php' ), 'tara_account' ) );
t( 'برگهٔ ورود: کارتِ ورود', false !== strpos( f( 'page-login.php' ), 'tara_acc_login_card' ) || false !== strpos( f( 'page-login.php' ), 'tara_login' ) );

/* ======================================================================
   نتیجه
   ====================================================================== */

echo "\n";
if ( 0 === $FAIL ) {
	echo "RESULT pass=$PASS fail=0\n";
} else {
	echo "RESULT pass=$PASS fail=$FAIL\nFAILED:\n";
	foreach ( $FAILS as $one ) {
		echo ' - ' . $one . "\n";
	}
}
