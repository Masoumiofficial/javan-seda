<?php
/**
 * dump-391.php — چاپِ مارکاپِ واقعیِ حساب برای ساختنِ پیش‌نمایش
 * =========================================================================
 * آزمون نیست: با همان دادهٔ ساختگیِ `run-391-fixtures.php`، مارکاپِ اصلیِ
 * مودال، برگهٔ پلی‌لیست، پنل (همهٔ تب‌ها) و دکمه‌های حساب را چاپ می‌کند تا
 * `docs/preview-3.10.0.html` از خودِ کدِ قالب ساخته شود، نه از نسخهٔ دست‌ساز.
 *
 * اجرا: node tools/php-test.mjs tests/dump-391.php > /tmp/dump.html
 *
 * @package Taranehyar
 */

/* تاریخِ فارسی برای پیش‌نمایش. باید پیش از fixtureها تعریف شود، چون آن‌ها
   نسخهٔ سادهٔ خودشان را فقط در صورتِ «نبودن» می‌گذارند. در وردپرسِ واقعی
   همین `date_i18n` هست. */
if ( ! function_exists( 'date_i18n' ) ) {
	function date_i18n( $format, $ts = null ) {
		$ts    = $ts ? (int) $ts : time();
		$month = array(
			1  => 'ژانویه',
			'فوریه',
			'مارس',
			'آوریل',
			'مه',
			'ژوئن',
			'ژوئیه',
			'اوت',
			'سپتامبر',
			'اکتبر',
			'نوامبر',
			'دسامبر',
		);
		$out   = date( 'j', $ts ) . ' ' . $month[ (int) date( 'n', $ts ) ] . ' ' . date( 'Y', $ts );
		return str_replace(
			array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
			array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
			$out
		);
	}
}

require __DIR__ . '/run-391-fixtures.php';

/* ---------- کاربرِ نمایشی و دادهٔ پنل ---------- */

$GLOBALS['__current'] = 0;
$tara_demo            = tara_acc_make_user( '09121234567' );
$GLOBALS['__demo_uid'] = $tara_demo;

update_user_meta( $tara_demo, 'nickname', 'شنوندهٔ ۴۵۶۷' );
update_user_meta( $tara_demo, 'tara_joined', time() - 86400 * 40 );
update_user_meta( $tara_demo, 'tara_tg_id', 12345678 );
update_user_meta( $tara_demo, 'tara_tg_at', time() - 86400 * 3 );
update_user_meta( $tara_demo, 'tara_likes', array( 701, 702, 703 ) );
update_user_meta( $tara_demo, 'tara_follows', array( 41, 42 ) );
update_user_meta(
	$tara_demo,
	'tara_playlists',
	array(
		array( 'k' => 'aaa111', 'n' => 'شب‌های آرام', 't' => array( 701, 702 ), 'p' => 1, 'c' => time() - 86400 * 12 ),
		array( 'k' => 'bbb222', 'n' => 'مسیرِ صبح', 't' => array( 703 ), 'p' => 0, 'c' => time() - 86400 * 5 ),
		array( 'k' => 'ccc333', 'n' => 'بهترین‌های ۱۴۰۴', 't' => array( 701, 702, 703, 704 ), 'p' => 1, 'c' => time() - 86400 * 2 ),
	)
);
update_user_meta(
	$tara_demo,
	'tara_history',
	array(
		array( 'i' => 703, 't' => time() - 3600 ),
		array( 'i' => 701, 't' => time() - 7200 ),
		array( 'i' => 702, 't' => time() - 9000 ),
	)
);
update_user_meta(
	$tara_demo,
	'tara_notifs',
	array(
		array( 'k' => 'welcome', 'title' => 'خوش آمدید! از پنلِ «حساب من» آهنگ‌های موردعلاقه‌تان را ذخیره کنید.', 'url' => '', 't' => time() - 86400 * 40, 'r' => 1 ),
		array( 'k' => 'tg', 'title' => 'تلگرامِ شما به حساب وصل شد؛ از این به بعد پیام‌های ربات هم به همین حساب می‌رسد.', 'url' => '', 't' => time() - 86400 * 3, 'r' => 0 ),
		array( 'k' => 'new', 'title' => 'آهنگِ تازه از خوانندهٔ الف منتشر شد.', 'url' => 'https://ex/701', 't' => time() - 7200, 'r' => 0 ),
	)
);
update_term_meta( 41, 'tara_followers', 128 );
update_term_meta( 42, 'tara_followers', 64 );

set_acc( 'tg_bot', 'ahangbanbot' );
set_acc( 'sms', 'dev' );

/* v3.11.0 — اعلان‌های مدیر از تنظیماتِ قالب (برای دیدنِ کارتِ اعلان‌ها در پیش‌نمایش) */
$GLOBALS['__options']['option_tree']['notice_on']    = 'on';
$GLOBALS['__options']['option_tree']['notice_text']  = "نسخهٔ تازهٔ پنلِ کاربری منتشر شد؛ حالا می‌توانید عکسِ پروفایل بگذارید.\nمیکسِ تازهٔ «شب‌های آرام» در رادیو | https://ex/radio\nرباتِ تلگرام حالا آهنگ‌های ذخیره‌شده را هم نشان می‌دهد.";
$GLOBALS['__options']['option_tree']['notice_date']  = '۱۴۰۴/۰۸/۰۷';

/* ---------- چاپ ---------- */

echo "<!--TARA:MODAL-->\n";
tara_acc_modal();
echo "//TARA:MODAL\n";

echo "<!--TARA:PICKER-->\n";
wp_set_current_user( $tara_demo );
tara_acc_playlist_picker();
echo "//TARA:PICKER\n";

echo "<!--TARA:PANEL-->\n";
$_GET['tab'] = 'overview';
echo tara_acc_panel(); // phpcs:ignore
echo "//TARA:PANEL\n";

echo "<!--TARA:SNIPPETS-->\n";
echo tara_acc_single_actions( 701 ); // phpcs:ignore
echo "<!--SNIP:SEP-->\n";
echo tara_acc_header_button( 'desktop' ); // phpcs:ignore
echo "<!--SNIP:SEP-->\n";
echo tara_acc_follow_button( 41 ); // phpcs:ignore
echo "<!--SNIP:SEP-->\n";
echo tara_acc_like_button( 701 ); // phpcs:ignore
echo "<!--SNIP:SEP-->\n";
echo tara_acc_bot_song_button( 701 ); // phpcs:ignore
echo "<!--SNIP:SEP-->\n";
wp_set_current_user( 0 );
echo tara_acc_login_card( array( 'mode' => 'login' ) ); // phpcs:ignore
echo "<!--SNIP:SEP-->\n";
echo tara_acc_login_card( array( 'mode' => 'register' ) ); // phpcs:ignore
echo "<!--SNIP:SEP-->\n";
wp_set_current_user( $tara_demo );
echo tara_acc_notice_card(); // phpcs:ignore
echo "<!--SNIP:SEP-->\n";
/* تبِ «تلگرام» در حالتِ «هنوز وصل نیست» — برای دیدنِ دو گام و یک دکمه */
$tara_tg_backup = get_user_meta( $tara_demo, 'tara_tg_id', true );
delete_user_meta( $tara_demo, 'tara_tg_id' );
delete_user_meta( $tara_demo, 'tara_tg_user' );
delete_user_meta( $tara_demo, 'tara_tg_at' );
echo '<div class="ty-acct__main">' . tara_acc_tab_telegram( $tara_demo ) . '</div>'; // phpcs:ignore
if ( '' !== $tara_tg_backup ) {
	update_user_meta( $tara_demo, 'tara_tg_id', $tara_tg_backup );
}
echo "//TARA:SNIPPETS\n";

echo "<!--TARA:TABS-->\n";
foreach ( array( 'favorites', 'playlists', 'follows', 'history', 'notifs', 'telegram', 'profile' ) as $tara_tab ) {
	$_GET['tab'] = $tara_tab;
	echo '<!--TAB:' . $tara_tab . "-->\n";
	echo tara_acc_panel(); // phpcs:ignore
	echo '//TAB:' . $tara_tab . "\n";
}
echo "//TARA:TABS\n";
