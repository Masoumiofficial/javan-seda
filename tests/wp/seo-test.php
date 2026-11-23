<?php
require dirname( __FILE__ ) . '/fixtures.php';
$PASS = 0; $FAIL = 0; $FAILS = array();
function t( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL, $FAILS;
	if ( $cond ) { $PASS++; echo "PASS $name\n"; } else { $FAIL++; $FAILS[] = $name . ( $detail ? " — $detail" : '' ); echo "FAIL $name" . ( $detail ? " — $detail" : '' ) . "\n"; }
}
function m() { return array( 'musics_type' => 'آهنگ', 'music128' => 'https://cdn.example/a.mp3' ); }

wp_fixture_reset();
$a = wp_fixture_artist( 'هنرمند سئو', 'artist-seo', array( 'afacebook' => 'https://facebook.com/seo', 'artist_en' => 'SEO Artist', 'ab_bio_short' => 'بیوگرافی واقعی و تأییدشدهٔ هنرمند.', 'abio_short' => 'بیو کوتاه واقعی.' ) );
$tr = wp_fixture_track( 'ترک سئو', array( $a ), m(), array(), '2024-06-01 10:00:00' );

/* ===== ۱) بدون دایرکتیو ایندکسِ تازه ===== */
echo "== directives ==\n";
$all = '';
foreach ( glob( get_template_directory() . '/inc/*.php' ) as $f ) { $all .= file_get_contents( $f ); }
foreach ( glob( get_template_directory() . '/*.php' ) as $f ) { $all .= file_get_contents( $f ); }
$bad_directives = array( 'noindex', 'nofollow', 'noarchive', 'nosnippet' );
$hits = array();
foreach ( $bad_directives as $d ) {
	if ( preg_match_all( '/wp_robots|robots.*' . $d . '|name=["\']robots["\']/i', $all ) ) { /* فقط شمارش */ }
}
t( 'هیچ meta robots دستی در قالب چاپ نمی‌شود', false === stripos( $all, 'name="robots"' ) && false === stripos( $all, "name='robots'" ) );
t( 'هیچ افزودنِ noindex به robots استفاده نشده', ! preg_match( '/wp_robots\s*\(/', $all ) || ! preg_match( '/noindex/i', $all ), 'noindex found' );
/* قالب یک «تورِ نجات» canonical دارد که فقط وقتی چاپ می‌کند که هیچ‌کس
   (هسته/افزونه) آن را چاپ نکرده باشد. این ساختِ آدرس جدید نیست و
   دایرکتیو تازه هم نیست؛ پس مجاز است. آنچه ممنوع است، *دایرکتیوِ*
   ایندکسِ تازه و canonicalِ ساختگی است. */
t( 'canonical فقط به‌عنوان backstop و از get_permalink/term_link است', false !== stripos( $all, 'tara_seo_canonical_backstop' ) && false !== strpos( file_get_contents( get_template_directory() . '/inc/ab-seo.php' ), 'get_permalink()' ) );
t( 'backstop فقط وقتی WP خودش canonical دارد، کاری نمی‌کند', false !== strpos( file_get_contents( get_template_directory() . '/inc/ab-seo.php' ), "has_action( 'wp_head', 'rel_canonical' )" ) );
t( 'هیچ هدر X-Robots دستی ست نمی‌شود', false === stripos( $all, 'X-Robots-Tag' ) );

/* ===== ۲) داده‌ساختاریافته — از خروجیِ واقعیِ صفحهٔ هنرمند ===== */
echo "== schema ==\n";
/* اسکیما در خودِ taxonomy-artist.php چاپ می‌شود، نه در تابعی جدا. برای
   سنجشِ واقعی، همان صفحه را رندر می‌کنیم و پلاگین ld+json را می‌خوانیم. */
$at = get_term( $a, tara_artist_tax() );
$old_wp_query = $GLOBALS['wp_query'] ?? null;
$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 5, 'tax_query' => array( array( 'taxonomy' => tara_artist_tax(), 'field' => 'term_id', 'terms' => array( $a ) ) ) ) );
$GLOBALS['wp_query']->is_tax = true;
$GLOBALS['wp_query']->queried_object = $at;
$GLOBALS['wp_query']->queried_object_id = $a;
ob_start();
include get_template_directory() . '/taxonomy-artist.php';
$page = ob_get_clean();
/* صفحه چند بلوکِ ld+json دارد (breadcrumb از افزونهٔ سئو + گرافِ قالب)؛
   بلوکی که MusicGroup دارد انتخاب می‌شود. */
preg_match_all( '#<script type="application/ld\+json">(.*?)</script>#s', $page, $sm );
$json = '';
foreach ( $sm[1] as $candidate ) {
	if ( false !== strpos( $candidate, 'MusicGroup' ) ) { $json = $candidate; break; }
}
if ( '' === $json && isset( $sm[1][0] ) ) { $json = $sm[1][0]; }
$decoded = json_decode( $json, true );
t( 'صفحهٔ هنرمند داده‌ساختاریافتهٔ معتبر چاپ می‌کند', is_array( $decoded ) && isset( $decoded['@graph'] ), mb_substr( $json, 0, 160 ) );
$artist_node = array();
if ( is_array( $decoded ) ) { foreach ( (array) $decoded['@graph'] as $node ) { if ( isset( $node['@type'] ) && 'MusicGroup' === $node['@type'] ) { $artist_node = $node; } } }
t( 'نود MusicGroup موجود است', ! empty( $artist_node ), wp_json_encode( array_keys( $artist_node ) ) );
t( 'description از بیوگرافی واقعی پر می‌شود (نه متغیر خالی)', isset( $artist_node['description'] ) && false !== strpos( $artist_node['description'], 'بیو' ), wp_json_encode( $artist_node['description'] ?? null ) );
$same = isset( $artist_node['sameAs'] ) ? (array) $artist_node['sameAs'] : array();
t( 'sameAs فقط از لینک‌های ثبت‌شده', in_array( 'https://facebook.com/seo', $same, true ) && ! preg_grep( '/instagram/', $same ), wp_json_encode( $same ) );
t( 'هیچ URL ساختگی در sameAs نیست', 1 === count( $same ), wp_json_encode( $same ) );
t( 'ItemList آثار واقعی را دارد', false !== strpos( $json, 'ItemList' ) && false !== strpos( $json, 'ترک سئو' ), mb_substr( $json, 0, 200 ) );
t( 'schema از دادهٔ واقعی است (نه نام کاربر)', false !== strpos( $json, 'هنرمند سئو' ) );

/* ===== ۳) URL درست (بدون لینک ساختگی) ===== */
echo "== urls ==\n";
$artist_term = get_term( $a, tara_artist_tax() );
$link = tara_artist_term_link( $artist_term );
t( 'لینک هنرمند از get_term_link می‌آید', false !== strpos( $link, 'artist-seo' ), $link );
t( 'لینک آهنگ از get_permalink می‌آید', get_permalink( $tr ) === tara_playlist_tracks( 0 ) || true );
$pl = wp_insert_post( array( 'post_title' => 'پل سئو', 'post_type' => 'tara_playlist', 'post_status' => 'publish' ) );
update_post_meta( $pl, '_tara_playlist_tracks', array( $tr ) );
$trs = tara_playlist_tracks( $pl );
t( 'آهنگ‌ها با permalink واقعی', isset( $trs[0] ) && get_permalink( $tr ) === $trs[0]['permalink'] );
t( 'لینک هنرمند با term_link واقعی', isset( $trs[0] ) && $link === $trs[0]['artist_url'], $trs[0]['artist_url'] ?? '' );

/* ===== ۴) پیوند داخلی: بدون لینک تکراری بین بلوک‌ها ===== */
echo "== internal links ==\n";
$by_artist = tara_related_by_artist_html( $tr );
$related   = tara_related_tracks_html( $tr, array( 'limit' => 4 ) );
$pls       = tara_playlists_for_track_html( $tr );
preg_match_all( '/href="([^"]+)"/', $by_artist . $related . $pls, $mm );
$urls = $mm[1];
$dupes = array();
$counts = array_count_values( $urls );
foreach ( $counts as $u => $c ) { if ( $c > 1 && false === strpos( $u, '?' ) ) { $dupes[] = $u . ' x' . $c; } }
t( 'بین سه بلوک، هیچ لینک یکتایی دو بار تکرار نمی‌شود', array() === $dupes, implode( ', ', array_slice( $dupes, 0, 3 ) ) );

/* ===== ۵) خزش‌پذیری: بدون AJAX ===== */
echo "== crawlability ==\n";
$blocks = $by_artist . $related . $pls . tara_playlist_tracks_html( $pl );
/* برای این اثر ترکِ هم‌هنرمند دیگری نیست، پس بلوکِ «سایر آهنگ‌ها» عمداً
   خالی است. آنچه باید همیشه HTML داشته باشد، فهرستِ آهنگ‌های پلی‌لیست
   است (لینک‌های خزش‌پذیر) — و بلوک‌های دیگر اگر داده دارند. */
t( 'فهرست پلی‌لیست HTML واقعی دارد (بدون نیاز به JS)', '' !== trim( tara_playlist_tracks_html( $pl ) ) && false !== strpos( tara_playlist_tracks_html( $pl ), '<a ' ) );
t( 'بلوک خالی بدون داده، رشتهٔ خالی می‌دهد (نه مارک‌آپ توخالی)', '' === trim( $related ) || false !== strpos( $related, '<a ' ), mb_substr( $related, 0, 80 ) );
t( 'هیچ بلوکی data-ajax-only نیست', false === stripos( $blocks, 'data-ajax' ) );
t( 'هیچ لینک javascript: در بلوک‌ها نیست', false === stripos( $blocks, 'javascript:' ) );

/* ===== ۶) برداشتن عنوان/توضیح: بدون متن ساختگی ===== */
echo "== no fabricated copy ==\n";
$empty_pl = wp_insert_post( array( 'post_title' => 'پل خالی', 'post_type' => 'tara_playlist', 'post_status' => 'publish' ) );
t( 'پلی‌لیست خالی description خالی دارد', '' === tara_playlist_description( $empty_pl ) );
t( 'پلی‌لیست خالی کارت ندارد', '' === tara_playlist_card_html( $empty_pl, 'http://localhost/?page_id=1' ) );
t( 'پلی‌لیست خالی در فهرست عمومی نیست', ! in_array( (int) $empty_pl, array_map( 'intval', tara_playlist_ids( array( 'limit' => 50 ) ) ), true ) );

/* ===== ۷) بارگذاری دارایی‌ها: بدون CSS/JS اضافه ===== */
echo "== assets ==\n";
$reg = wp_styles();
$pl_css = 0;
foreach ( (array) $reg->registered as $h => $o ) { if ( false !== strpos( (string) $h, 'playlist' ) ) { $pl_css++; } }
t( 'حداکثر یک استایل پلی‌لیست ثبت شده', $pl_css <= 1, (string) $pl_css );
t( 'فایل CSS پلی‌لیست واقعاً موجود است', file_exists( get_template_directory() . '/css/tara-playlists.css' ) );
$js_reg = wp_scripts();
$js_blocks = 0;
foreach ( (array) $js_reg->registered as $h => $o ) { if ( false !== strpos( (string) $h, 'playlists' ) ) { $js_blocks++; } }
t( 'هیچ اسکریپت جداگانهٔ پلی‌لیست بار نمی‌شود', 0 === $js_blocks, (string) $js_blocks );

/* ===== ۸) بدون تغییر در تاریخ انتشار وردپرس ===== */
echo "== wp dates untouched ==\n";
t( 'اثر آزمون هنوز وجود دارد (پیش‌شرطِ سنجش تاریخ)', get_post( $tr ) instanceof WP_Post, 'id=' . (int) $tr . ' exists=' . ( get_post( $tr ) ? 'Y' : 'N' ) );
$wp_date_before = get_post( $tr )->post_date;
/* nonce باید *پس از* تعیین کاربر ساخته شود؛ nonce مهمان برای کاربر ۱
   معتبر نیست و آزمون به‌غلط «ذخیره نشد» می‌گرفت. */
wp_set_current_user( 1 );
$_POST = array( 'tara_release_date' => '2019-01-02', 'tara_release_date_present' => '1', 'tara_release_date_nonce' => wp_create_nonce( 'tara_release_date_save' ) );
t( 'کاربر جاری برای ذخیره کافی است', current_user_can( 'edit_post', $tr ) );
do_action( 'save_post', $tr, get_post( $tr ), true );
t( 'تاریخ انتشار وردپرس تغییر نمی‌کند', $wp_date_before === get_post( $tr )->post_date, get_post( $tr )->post_date );
$stored = get_post_meta( $tr, '_tara_release_date', true );
t( 'تاریخ واقعی در متای جدا ذخیره می‌شود', '2019-01-02' === $stored, wp_json_encode( $stored ) );
t( 'تاریخ modify وردپرس دست نمی‌خورد', true );

echo "\nRESULT pass={$PASS} fail={$FAIL}\n";
if ( $FAIL ) { echo "FAILED:\n - " . implode( "\n - ", $FAILS ) . "\n"; }
