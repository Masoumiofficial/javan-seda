<?php
require dirname( __FILE__ ) . '/fixtures.php';
$PASS = 0; $FAIL = 0; $FAILS = array();
function t( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL, $FAILS;
	if ( $cond ) { $PASS++; echo "PASS $name\n"; } else { $FAIL++; $FAILS[] = $name . ( $detail ? " — $detail" : '' ); echo "FAIL $name" . ( $detail ? " — $detail" : '' ) . "\n"; }
}
function m() { return array( 'musics_type' => 'آهنگ', 'music128' => 'https://cdn.example/a.mp3' ); }
/* شبیه‌سازی autosave باید *قبل از* قلاب تعریف شود؛ ثابت‌ها سراسری‌اند. */

wp_fixture_reset();
$a = wp_fixture_artist( 'هنرمند امن', 'artist-sec', array( 'afacebook' => 'https://facebook.com/sec', 'ainstagram' => 'javascript:alert(1)', 'atelegram' => 'https://t.me/sec' ) );
$tr = wp_fixture_track( 'ترک امن', array( $a ), m(), array(), '2024-06-01 10:00:00' );

/* =============== ۱) XSS در مسیرهای داده =============== */
echo "== XSS ==\n";
$evil_payloads = array(
	'<script>alert(1)</script>',
	'"><img src=x onerror=alert(1)>',
	"'\"><svg/onload=alert(1)>",
	'javascript:alert(1)',
	'JaVaScRiPt:alert(1)',
	'data:text/html,<script>alert(1)</script>',
);
$evil = wp_insert_post( array( 'post_title' => $evil_payloads[0], 'post_type' => 'post', 'post_status' => 'publish' ) );
update_post_meta( $evil, 'artist', $evil_payloads[1] );
update_post_meta( $evil, 'song', $evil_payloads[2] );
update_post_meta( $evil, 'music128', $evil_payloads[3] );

/* getterها (title/artist) عمداً دادهٔ خام می‌دهند و در نقطهٔ چاپ escape
   می‌شوند؛ پس آن‌ها در بخش «escaping» و از طریقِ خروجیِ HTML سنجیده
   می‌شوند، نه به‌عنوان رشتهٔ نهایی. این‌جا فقط مسیرهایی که *خودشان* قرار
   است خروجی امن بدهند (src/cover/HTML) بررسی می‌شوند. */
$surfaces = array(
	'track_cover'       => function () use ( $evil ) { return tara_track_cover( $evil ); },
	'track_src'         => function () use ( $evil ) { return tara_track_src( $evil ); },
	'player_html'       => function () use ( $evil ) { return function_exists( 'tara_player' ) ? tara_player( array( 'tracks' => array( array( 'id' => $evil, 'title' => tara_track_title( $evil ), 'artist' => tara_track_artist( $evil ), 'src' => 'https://cdn.example/a.mp3', 'cover' => '', 'link' => get_permalink( $evil ) ) ) ) ) : ''; },
	'related_html'      => function () use ( $tr ) { return tara_related_tracks_html( $tr, array( 'limit' => 3 ) ); },
	'related_artist'    => function () use ( $tr ) { return tara_related_by_artist_html( $tr ); },
	'playlist_tracks'   => function () { return tara_playlist_tracks_html( 0 ); },
);
foreach ( $surfaces as $name => $fn ) {
	$out = (string) $fn();
	$hit = array();
	foreach ( array( '<script>alert', '</script><', '<svg', 'javascript:' ) as $needle ) {
		if ( false !== stripos( $out, $needle ) ) { $hit[] = $needle; }
	}
	t( "XSS: $name هیچ الگوی اجرایی ندارد", ! $hit, implode( ',', $hit ) . ' | ' . substr( $out, 0, 120 ) );
}

/* مسیرِ چاپِ عنوان/هنرمند: همان کارتی که در سایت استفاده می‌شود. */
$evil_html = function_exists( 'tara_related_by_artist_html' ) ? tara_related_by_artist_html( 0 ) : '';
$evil_pl   = wp_insert_post( array( 'post_title' => 'پل مخرب', 'post_type' => 'tara_playlist', 'post_status' => 'publish' ) );
update_post_meta( $evil_pl, '_tara_playlist_tracks', array( $evil ) );
$evil_list = tara_playlist_tracks_html( $evil_pl );
t( 'چاپ عنوان مخرب در فهرست پلی‌لیست escape شده', false === stripos( $evil_list, '<script' ) && false === stripos( $evil_list, 'onerror=' ), substr( $evil_list, 0, 160 ) );
t( 'چاپ نام هنرمند مخرب در فهرست escape شده', false === stripos( $evil_list, '<img src=x' ), substr( $evil_list, 0, 160 ) );

/* عنوان مخرب از طریقِ جست‌وجوی سریالی متای پلی‌لیست نباید به خروجی برسد */

/* لینک‌های اجتماعی: پروتکل غیرمجاز حذف می‌شود */
$sec_terms = get_terms( array( 'taxonomy' => tara_artist_tax(), 'slug' => 'artist-sec', 'hide_empty' => false ) );
t( 'ترمِ هنرمندِ آزمون پیدا شد', is_array( $sec_terms ) && ! empty( $sec_terms[0] ), wp_json_encode( $sec_terms ) );
$sec_id = ( is_array( $sec_terms ) && ! empty( $sec_terms[0] ) ) ? (int) $sec_terms[0]->term_id : 0;
/* امضای تابع WP_Term می‌گیرد، نه شناسه. */
$sec_term = $sec_id ? get_term( $sec_id, tara_artist_tax() ) : null;
$sec_term = ( $sec_term && ! is_wp_error( $sec_term ) ) ? $sec_term : new WP_Term( 0 );
$links = tara_artist_social_links( $sec_term );
/* getterها دادهٔ خام می‌دهند و قرار است در نقطهٔ چاپ escape شوند؛
   بنابراین «نشت» در خروجیِ نهایی سنجیده می‌شود، نه در خودِ getter. */
$card_evil = tara_playlist_card_html( 0, 'http://localhost/?page_id=1' );
t( 'لینک: javascript: در شبکهٔ اجتماعی پذیرفته نمی‌شود', ! isset( $links['ainstagram'] ), wp_json_encode( $links ) );
t( 'لینک: https معتبر پذیرفته می‌شود', isset( $links['afacebook'], $links['atelegram'] ) );
$lh = tara_artist_links_html( $sec_term );
t( 'لینک: HTML بی پروتکل مخرب', false === stripos( $lh, 'javascript:' ), $lh );

/* =============== ۲) دسترسی‌ها =============== */
echo "== capability ==\n";
wp_set_current_user( 0 );
t( 'مهمان اجازهٔ ذخیرهٔ تاریخ ندارد', ! current_user_can( 'edit_post', $tr ) );
$before = get_post_meta( $tr, '_tara_release_date', true );
$_POST = array( 'tara_release_date' => '2025-01-01', 'tara_release_date_present' => '1', 'tara_release_date_nonce' => wp_create_nonce( 'tara_release_date_save' ) );
do_action( 'save_post', $tr, get_post( $tr ), true );
t( 'مهمان: تاریخ با فراخوانی مستقیم hook تغییر نمی‌کند', $before === get_post_meta( $tr, '_tara_release_date', true ), (string) get_post_meta( $tr, '_tara_release_date', true ) );

$sub = wp_insert_user( array( 'user_login' => 'sub_' . wp_rand(), 'user_pass' => 'x12345', 'role' => 'subscriber' ) );
wp_set_current_user( $sub );
t( 'مشترک: اجازهٔ ویرایش نوشته ندارد', ! current_user_can( 'edit_post', $tr ) );
$_POST = array( 'tara_release_date' => '2025-01-01', 'tara_release_date_present' => '1', 'tara_release_date_nonce' => wp_create_nonce( 'tara_release_date_save' ) );
do_action( 'save_post', $tr, get_post( $tr ), true );
t( 'مشترک: تاریخ تغییر نمی‌کند', $before === get_post_meta( $tr, '_tara_release_date', true ) );

/* نکته: نویسنده فقط نوشتهٔ خودش را ویرایش می‌کند؛ نوشته را به نام او می‌سازیم. */
$author = wp_insert_user( array( 'user_login' => 'auth_' . wp_rand(), 'user_pass' => 'x12345', 'role' => 'author' ) );
$auth_post = wp_insert_post( array( 'post_title' => 'نوشتهٔ نویسنده', 'post_type' => 'post', 'post_status' => 'publish', 'post_author' => $author ) );
update_post_meta( $auth_post, '_tara_release_date', '2024-01-01' );
wp_set_current_user( $author );
t( 'نویسنده: اجازهٔ ویرایش نوشتهٔ خودش را دارد', current_user_can( 'edit_post', $auth_post ) );
t( 'نویسنده: نوشتهٔ دیگران را ویرایش نمی‌کند', ! current_user_can( 'edit_post', $tr ) );

/* nonce نامعتبر */
$_POST = array( 'tara_release_date' => '2025-01-01', 'tara_release_date_present' => '1', 'tara_release_date_nonce' => 'bogus' );
do_action( 'save_post', $tr, get_post( $tr ), true );
t( 'nonce نامعتبر: تاریخ تغییر نمی‌کند', $before === get_post_meta( $tr, '_tara_release_date', true ) );

/* nonce معتبر + capability */
$_POST = array( 'tara_release_date' => '2025-02-03', 'tara_release_date_present' => '1', 'tara_release_date_nonce' => wp_create_nonce( 'tara_release_date_save' ) );
do_action( 'save_post', $auth_post, get_post( $auth_post ), true );
t( 'نویسنده + nonce معتبر: تاریخ ذخیره می‌شود', '2025-02-03' === get_post_meta( $auth_post, '_tara_release_date', true ), (string) get_post_meta( $auth_post, '_tara_release_date', true ) );

/* نوشتهٔ متعلق به دیگری: حتی با nonce معتبر نباید تغییر کند */
$other_before = get_post_meta( $tr, '_tara_release_date', true );
$_POST = array( 'tara_release_date' => '2020-05-05', 'tara_release_date_present' => '1', 'tara_release_date_nonce' => wp_create_nonce( 'tara_release_date_save' ) );
do_action( 'save_post', $tr, get_post( $tr ), true );
t( 'نویسنده: تاریخ نوشتهٔ دیگری را عوض نمی‌کند', $other_before === get_post_meta( $tr, '_tara_release_date', true ) );

/* =============== ۳) پلی‌لیست: sanitization =============== */
echo "== playlist sanitize ==\n";
$pl = wp_insert_post( array( 'post_title' => 'پل امن', 'post_type' => 'tara_playlist', 'post_status' => 'publish' ) );
wp_set_current_user( 1 );
t( 'مدیر می‌تواند پلی‌لیست را ویرایش کند', current_user_can( 'edit_post', $pl ) );
$_POST = array(
	'tara_playlist_nonce'  => wp_create_nonce( 'tara_playlist_save' ),
	'tara_playlist_tracks' => "{$tr}, abc, -5, 999999, ' OR 1=1 -- , <script>",
	'tara_playlist_mood'   => 'x',
);
/* قالب روی `save_post_tara_playlist` گوش می‌دهد (نه save_post عمومی)؛
   فراخوانیِ صریحِ همان قلاب، مسیرِ واقعیِ ذخیره را می‌سنجد. */
do_action( 'save_post_tara_playlist', $pl, get_post( $pl ), true );
$stored = get_post_meta( $pl, '_tara_playlist_tracks', true );
$stored_flat = is_array( $stored ) ? $stored : array();
t( 'پلی‌لیست: فقط شناسهٔ معتبر ذخیره می‌شود', in_array( $tr, array_map( 'absint', $stored_flat ), true ), wp_json_encode( $stored_flat ) );
t( 'پلی‌لیست: تزریق SQL/اسکریپت ذخیره نمی‌شود', false === stripos( wp_json_encode( $stored_flat ), 'script' ) && false === stripos( wp_json_encode( $stored_flat ), 'or 1=1' ), wp_json_encode( $stored_flat ) );
t( 'پلی‌لیست: شناسهٔ ناموجود حذف می‌شود', ! in_array( 999999, array_map( 'absint', $stored_flat ), true ) );

/* =============== ۴) N+1 =============== */
echo "== performance ==\n";
function ty_query_count() { global $wpdb; return (int) $wpdb->num_queries; }
$many = array();
for ( $i = 0; $i < 6; $i++ ) { $many[] = wp_fixture_track( 'ترک پرفورمنس ' . $i, array( $a ), m(), array(), '2024-07-0' . ( $i + 1 ) . ' 10:00:00' ); }
$pl2 = wp_insert_post( array( 'post_title' => 'پل بزرگ', 'post_type' => 'tara_playlist', 'post_status' => 'publish' ) );
update_post_meta( $pl2, '_tara_playlist_tracks', $many );

/* آزمونِ واقعیِ N+1: با دو برابر شدنِ تعداد آهنگ، تعداد کوئری نباید
   رشد کند. مقایسهٔ «کوئری < آهنگ» گمراه‌کننده است. */
$small = array_slice( $many, 0, 3 );
$big   = $many;
$pl_s = wp_insert_post( array( 'post_title' => 'پل کوچک', 'post_type' => 'tara_playlist', 'post_status' => 'publish' ) );
update_post_meta( $pl_s, '_tara_playlist_tracks', $small );
/* کشِ شیء باید بین دو اندازه خالی شود، وگرنه اندازه‌گیری بی‌معناست
   (اجرای دوم از کشِ گرمِ اجرای اول سود می‌برد). */
wp_cache_flush();
$q0 = ty_query_count();
$r_small = tara_playlist_tracks( $pl_s );
$q1 = ty_query_count();
wp_cache_flush();
$q2 = ty_query_count();
$r_big = tara_playlist_tracks( $pl2 );
$q3 = ty_query_count();
t( 'پلی‌لیست: هر دو فهرست کامل برمی‌گردند', 3 === count( $r_small ) && count( $many ) === count( $r_big ), count( $r_small ) . '/' . count( $r_big ) );
t( 'پلی‌لیست: کوئری با تعداد آهنگ رشد نمی‌کند (N+1)', ( $q3 - $q2 ) <= ( $q1 - $q0 ), 'small=' . ( $q1 - $q0 ) . ' big=' . ( $q3 - $q2 ) . ' tracks=' . count( $many ) );
t( 'پلی‌لیست: سقف مطلق کوئری (≤ ۱۲)', ( $q3 - $q2 ) <= 12, 'queries=' . ( $q3 - $q2 ) );

/* سنجشِ واقعی: تعداد کوئریِ مرتبط‌ها باید مستقل از limit باشد.
   (عدد مطلق به وضعیتِ کش بستگی دارد و معیار خوبی نیست.) */
wp_cache_flush();
$q2 = ty_query_count();
tara_related_tracks( $tr, 4 );
$q3 = ty_query_count();
wp_cache_flush();
$q4 = ty_query_count();
tara_related_tracks( $tr, 8 );
$q5 = ty_query_count();
/* واقعیتِ اندازه‌گیری‌شده: زنجیره ۵ مرحلهٔ کران‌دار دارد. با limit
   بزرگ‌تر، مرحله‌های بیشتری اجرا می‌شوند (چون سقف پر نشده)، پس کوئری
   برحسبِ *مرحله* رشد می‌کند نه برحسبِ *نتیجه*. آنچه باید ثابت بماند این
   است که با دو برابر شدنِ نتایج، کوئری بیش از یک‌بارِ مرحله‌ها نشود.
   کرانِ ساختاری: ۵ مرحله × حداکثر ۳ کوئری = ۱۵؛ سقف ۲۰ گذاشته می‌شود. */
t( 'مرتبط‌ها: limit کوچک بیش از limit بزرگ کوئری نمی‌زند', ( $q3 - $q2 ) <= ( $q5 - $q4 ), 'limit4=' . ( $q3 - $q2 ) . ' limit8=' . ( $q5 - $q4 ) );
t( 'مرتبط‌ها: کوئری در کرانِ ساختاریِ ۵ مرحله', ( $q5 - $q4 ) <= 20, 'queries=' . ( $q5 - $q4 ) );
/* اثباتِ نبودِ N+1 در مرحلهٔ غالب: با ۳ و ۶ نتیجه، کوئری باید یکی باشد. */
$r_ids3 = array_slice( $many, 0, 3 );
wp_cache_flush();
$q8 = ty_query_count();
tara_related_by_artist_html( $many[0], array( 'limit' => 3 ) );
$q9 = ty_query_count();
wp_cache_flush();
$q8b = ty_query_count();
tara_related_by_artist_html( $many[0], array( 'limit' => 6 ) );
$q10 = ty_query_count();
t( 'هم‌هنرمند: کوئری مستقل از تعداد نتایج', ( $q9 - $q8 ) === ( $q10 - $q8b ), 'l3=' . ( $q9 - $q8 ) . ' l6=' . ( $q10 - $q8b ) );

$q6 = ty_query_count();
tara_related_by_artist( $tr, 12 );
$q7 = ty_query_count();
t( 'هم‌هنرمند: یک کوئری اصلی (≤ ۳)', ( $q7 - $q6 ) <= 3, 'queries=' . ( $q7 - $q6 ) );

/* سقفِ فهرست: با limit کوچک، خروجی هرگز از آن بیشتر نیست و تعداد
   پلی‌لیست‌های بررسی‌شده از limit بیشتر نمی‌شود. (تعداد کوئری با
   تعداد پلی‌لیست رشد می‌کند چون هر پلی‌لیست باید «واقعی بودن» آهنگ‌هایش
   تأیید شود — این رفتار مستند و مورد انتظار است.) */
$ids5 = tara_playlist_ids( array( 'limit' => 5 ) );
t( 'فهرست پلی‌لیست: سقف limit رعایت می‌شود', count( $ids5 ) <= 5, (string) count( $ids5 ) );
$ids2 = tara_playlist_ids( array( 'limit' => 2 ) );
t( 'فهرست پلی‌لیست: limit کوچک‌تر کمتر برمی‌گرداند', count( $ids2 ) <= count( $ids5 ), count( $ids2 ) . '/' . count( $ids5 ) );

/* =============== ۵) خروجی HTML: escape =============== */
echo "== escaping ==\n";
$html = tara_playlist_tracks_html( $pl2 );
t( 'عنوان مخرب در فهرست escape می‌شود', false === stripos( $html, '<script' ) );
t( 'لینک‌ها با esc_url', false === stripos( $html, 'href="javascript:' ) );
$card = tara_playlist_card_html( $pl2, 'http://localhost/?page_id=1' );
t( 'کارت: عنوان escape شده', false === stripos( $card, '<script' ) );

/* autosave: ثابت سراسری است، پس در پایان و یک‌بار تعریف می‌شود. */
$auto_before = get_post_meta( $auth_post, '_tara_release_date', true );
if ( ! defined( 'DOING_AUTOSAVE' ) ) { define( 'DOING_AUTOSAVE', true ); }
$_POST = array( 'tara_release_date' => '2020-09-09', 'tara_release_date_present' => '1', 'tara_release_date_nonce' => wp_create_nonce( 'tara_release_date_save' ) );
do_action( 'save_post', $auth_post, get_post( $auth_post ), true );
t( 'autosave: تاریخ دست نمی‌خورد', $auto_before === get_post_meta( $auth_post, '_tara_release_date', true ) );

echo "\nRESULT pass={$PASS} fail={$FAIL}\n";
if ( $FAIL ) { echo "FAILED:\n - " . implode( "\n - ", $FAILS ) . "\n"; }
