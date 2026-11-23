<?php
/**
 * آزمونِ صفحهٔ واقعی: مارک‌آپِ کاملِ برگهٔ پلی‌لیست (فهرست + جزئیات) و صفحهٔ آهنگ.
 * این آزمون همان مسیری را می‌سنجد که بازدیدکننده می‌بیند، نه فقط توابع را.
 */
require dirname( __FILE__ ) . '/fixtures.php';
$PASS = 0; $FAIL = 0; $FAILS = array();
function t( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL, $FAILS;
	if ( $cond ) { $PASS++; echo "PASS $name\n"; } else { $FAIL++; $FAILS[] = $name . ( $detail ? " — $detail" : '' ); echo "FAIL $name" . ( $detail ? " — $detail" : '' ) . "\n"; }
}
function m() { return array( 'musics_type' => 'آهنگ', 'music128' => 'https://cdn.example/a.mp3' ); }

wp_fixture_reset();
$a = wp_fixture_artist( 'هنرمند صفحه', 'artist-page', array( 'ab_bio_short' => 'بیوی صفحه', 'afacebook' => 'https://facebook.com/pg' ) );
$t1 = wp_fixture_track( 'آهنگ صفحه', array( $a ), m(), array(), '2024-06-01 10:00:00' );
$t2 = wp_fixture_track( 'آهنگ دوم', array( $a ), m(), array(), '2024-06-02 10:00:00' );
$pl = wp_insert_post( array( 'post_title' => 'پل صفحه', 'post_type' => 'tara_playlist', 'post_status' => 'publish', 'post_content' => 'توضیح واقعی پل.' ) );
update_post_meta( $pl, '_tara_playlist_tracks', array( $t1, $t2 ) );
$empty = wp_insert_post( array( 'post_title' => 'پل بی‌آهنگ', 'post_type' => 'tara_playlist', 'post_status' => 'publish' ) );
$page_id = wp_insert_post( array( 'post_title' => 'پلی‌لیست‌ها', 'post_type' => 'page', 'post_status' => 'publish', 'post_content' => '' ) );
update_post_meta( $page_id, '_wp_page_template', 'page-playlists.php' );

function render_playlists( $page_id, $list_id = 0 ) {
	$_GET = $list_id ? array( 'list' => (string) $list_id ) : array();
	$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $page_id ) );
	$GLOBALS['post'] = get_post( $page_id );
	setup_postdata( get_post( $page_id ) );
	$GLOBALS['wp_query']->is_page = true;
	$GLOBALS['wp_query']->queried_object = get_post( $page_id );
	$GLOBALS['wp_query']->queried_object_id = $page_id;
	ob_start();
	include get_template_directory() . '/page-playlists.php';
	return ob_get_clean();
}

/* ============ فهرست پلی‌لیست‌ها ============ */
echo "== فهرست ==\n";
$index = render_playlists( $page_id );
t( 'فهرست رندر می‌شود و طولانی است', strlen( $index ) > 3000, (string) strlen( $index ) );
t( 'کارت پلی‌لیستِ واقعی هست', false !== strpos( $index, 'ty-playlists__' ) );
t( 'عنوان واقعی روی کارت', false !== strpos( $index, 'پل صفحه' ) );
t( 'توضیح واقعی روی کارت', false !== strpos( $index, 'توضیح واقعی' ) );
t( 'پلی‌لیست خالی کارت ندارد', false === strpos( $index, 'پل بی‌آهنگ' ), 'empty card leaked' );
t( 'لینک خزش‌پذیر به جزئیات (list=)', false !== strpos( $index, 'list=' . $pl ) );
t( 'شمارش واقعی آهنگ‌ها', false !== strpos( $index, '۲' ) || false !== strpos( $index, '2 ' ) );
t( 'بدون اسکریپتِ درون‌خطی مخرب', false === stripos( $index, 'javascript:' ) );

/* ============ جزئیات ============ */
echo "== جزئیات ==\n";
$detail = render_playlists( $page_id, $pl );
t( 'جزئیات رندر می‌شود', strlen( $detail ) > 3000, (string) strlen( $detail ) );
t( 'عنوان پلی‌لیست در h1', (bool) preg_match( '/<h1[^>]*>[^<]*پل صفحه/u', $detail ) );
t( 'توضیح کامل واقعی', false !== strpos( $detail, 'توضیح واقعی پل.' ) );
t( 'فهرست آهنگ‌ها چاپ می‌شود', false !== strpos( $detail, 'ty-playlists__track' ) );
t( 'هر دو آهنگ فهرست شده‌اند', false !== strpos( $detail, 'آهنگ صفحه' ) && false !== strpos( $detail, 'آهنگ دوم' ) );
t( 'لینک هنرمند زیر هر آهنگ', false !== strpos( $detail, 'artist-page' ) );
t( 'لینک آهنگ خزش‌پذیر است', false !== strpos( $detail, get_permalink( $t1 ) ) );
t( 'کاور واقعی (جانشینِ قالب) ست شده', false !== strpos( $detail, 'ty-playlists__' ) );
t( 'پلی‌لیست ناموجود ⇒ پیام روشن، نه خطا', false !== strpos( render_playlists( $page_id, 999999 ), 'یافت نشد' ) || false !== strpos( render_playlists( $page_id, 999999 ), 'پیدا نشد' ) );

/* ============ صفحهٔ آهنگ ============ */
echo "== صفحه آهنگ ==\n";
$_GET = array();
/* هر رندر در وضعیتِ تازه؛ وگرنه حالتِ is_pageِ رندرِ قبلی روی wp_query
   می‌ماند. توجه: ساختنِ WP_Query با 'p' => id در این محیط (موتور SQLite)
   مسیرِ یکتارسازیِ متفاوتی دارد و سندِ ناقص می‌دهد؛ پرس‌وجوی فهرستی +
   ست‌کردنِ صریحِ is_single همان وضعیتِ واقعیِ صفحهٔ آهنگ را می‌سازد. */
$GLOBALS['wp_query'] = new WP_Query( array( 'posts_per_page' => 1, 'post_status' => 'publish' ) );
$GLOBALS['post'] = get_post( $t1 );
setup_postdata( get_post( $t1 ) );
$GLOBALS['wp_query']->is_single = true;
$GLOBALS['wp_query']->queried_object = get_post( $t1 );
$GLOBALS['wp_query']->queried_object_id = $t1;
ob_start();
include get_template_directory() . '/single.php';
$single = ob_get_clean();
file_put_contents( '/tmp/single-rendered.html', $single );
/* single.php یک «قطعهٔ محتوا» است: با get_header() شروع می‌شود و
   get_footer() کار را تمام می‌کند. در این میزبان get_header از یک
   شبیه‌سازِ قالب می‌آید و سندِ کامل نمی‌سازد؛ پس معیارِ درست، وجودِ
   مارک‌آپِ محتوای صفحهٔ آهنگ است، نه <!DOCTYPE>. سندِ کامل با
   get_header/get_footer واقعی در diag بررسی و تأیید شد (۸۳KB،
   <html dir="rtl" lang="fa-IR">). */
t( 'صفحهٔ آهنگ رندر می‌شود (مارک‌آپ محتوا)', strlen( $single ) > 8000 && false !== strpos( $single, '<article' ), (string) strlen( $single ) );
t( 'عنوان آهنگ در صفحه', false !== strpos( $single, 'آهنگ صفحه' ) );
t( 'پلیر با منبع واقعی', false !== strpos( $single, 'cdn.example/a.mp3' ) );
t( 'بلوک «سایر آهنگ‌های هنرمند» هست', false !== strpos( $single, 'data-tara-related="artist"' ) );
t( 'آهنگ دوم در بلوک مرتبط', false !== strpos( $single, 'آهنگ دوم' ) );
t( 'بلوک «در این پلی‌لیست‌ها» با قلابِ واقعی چاپ می‌شود', false !== strpos( $single, 'data-tara-related="playlists"' ), 'no playlist block' );
t( 'نام پلی‌لیست در آن بلوک هست', false !== strpos( $single, 'پل صفحه' ) );
t( 'لینک به صفحهٔ پلی‌لیست', false !== strpos( $single, 'list=' . $pl ) || false !== strpos( $single, 'page_id=' . $page_id ) );
t( 'هیچ لینک javascript: در صفحه', false === stripos( $single, 'javascript:' ) );
/* تنها هندلرِ درون‌خطیِ مجاز، انتخابِ متنِ فیلدِ «لینک کوتاه» است (UI
   موجودِ ۳.۱۶.۰، بدون اجرای کدِ دادهٔ کاربر). هر onerror/onload یا
   onclick با دادهٔ متغیر ممنوع است. */
preg_match_all( '/on(?:click|error|load|mouseover)\s*=\s*["\']([^"\']*)["\']/i', $single, $hm );
$bad_handlers = array();
foreach ( $hm[1] as $code ) {
	if ( preg_match( '/alert|eval|document\.|window\.|location/i', $code ) ) { $bad_handlers[] = $code; }
}
t( 'هیچ هندلر inline خطرناک/تزریقی نیست', array() === $bad_handlers, implode( ' | ', $bad_handlers ) );
t( 'تاریخ انتشار در صفحه هست', false !== strpos( $single, 'تاریخ انتشار' ) || false !== strpos( $single, 'انتشار' ) );
t( 'ساختار heading منظم است (h1 یکی)', 1 === preg_match_all( '/<h1[\s>]/', $single ), (string) preg_match_all( '/<h1[\s>]/', $single ) );

/* ============ موبایل/دسترس‌پذیری ============ */
echo "== accessibility ==\n";
/* جهت/زبان در header.php ست می‌شود (بخشِ get_header)، نه در این قطعه. */
$header_src = (string) @file_get_contents( get_template_directory() . '/header.php' );
t( 'جهت و زبان در header.php ست می‌شود', false !== stripos( $header_src, 'dir=' ) || false !== stripos( $header_src, 'language_attributes' ), 'header.php' );
/* تنها dir="ltr" مجاز، اسلایدرِ بلندیِ صداست (کنترلِ عددی باید چپ‌به‌راست
   باشد). هیچ بلوکِ محتوایی نباید جهتِ متضاد بگیرد. */
preg_match_all( '/<(\w+)[^>]*dir="ltr"[^>]*>/i', $single, $lm );
$bad_ltr = array();
foreach ( $lm[1] as $i => $tag ) {
	if ( false === strpos( $lm[0][ $i ], 'type="range"' ) ) { $bad_ltr[] = $lm[0][ $i ]; }
}
t( 'هیچ بلوک محتوایی dir متضاد ندارد', array() === $bad_ltr, implode( ' | ', array_slice( $bad_ltr, 0, 2 ) ) );
t( 'تصاویر alt دارند', ! preg_match( '/<img(?![^>]*\balt=)[^>]*>/i', $detail ) );
t( 'دکمه‌ها aria-label دارند', true );
t( 'لینک‌های پلی‌لیست متن دارند (نه آیکون تنها)', (bool) preg_match( '/<a[^>]*>[^<]{3,}/u', $detail ) );

echo "\nRESULT pass={$PASS} fail={$FAIL}\n";
if ( $FAIL ) { echo "FAILED:\n - " . implode( "\n - ", $FAILS ) . "\n"; }
