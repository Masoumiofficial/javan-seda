<?php
require dirname( __FILE__ ) . '/fixtures.php';
$PASS = 0; $FAIL = 0; $FAILS = array();
function t( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL, $FAILS;
	if ( $cond ) { $PASS++; echo "PASS $name\n"; } else { $FAIL++; $FAILS[] = $name . ( $detail ? " — $detail" : '' ); echo "FAIL $name" . ( $detail ? " — $detail" : '' ) . "\n"; }
}

wp_fixture_reset();
$tax = tara_artist_tax();

/* ---------- سناریو ۱: هنرمند کامل ---------- */
$full = wp_fixture_artist(
	'هنرمند کامل',
	'artist-full',
	array(
		'artist_en'    => 'Full Artist',
		'afacebook'    => 'https://facebook.com/full',
		'atelegram'    => 'https://t.me/full',
		'ainstagram'   => 'javascript:alert(1)',
		'ab_bio_short' => 'بیوگرافی کوتاهِ واقعی برای آزمون.',
		'aimg2'        => 'https://cdn.example/full.jpg',
		'tara_verified' => '1',
	)
);
$GLOBALS['__last_term'] = $full;

$g1 = wp_insert_term( 'پاپ', 'tara_genre' );
$m1 = wp_insert_term( 'غمگین', 'tara_mood' );
$g1 = is_array( $g1 ) ? (int) $g1['term_id'] : (int) $g1;
$m1 = is_array( $m1 ) ? (int) $m1['term_id'] : (int) $m1;

/* تاریخِ نوشتن عمداً قدیمی‌تر از تاریخ انتشار واقعی است تا ترجیحِ
   «تاریخ انتشار واقعی» سنجیده شود. */
$a1 = wp_fixture_track( 'آهنگ کامل یک', array( $full ), array( 'musics_type' => 'آهنگ', '_tara_release_date' => '2025-05-01', 'song' => 'یک' ), array( 'tara_genre' => array( $g1 ), 'tara_mood' => array( $m1 ) ), '2020-01-10 10:00:00' );
$a2 = wp_fixture_track( 'آهنگ کامل دو', array( $full ), array( 'musics_type' => 'آهنگ', 'song' => 'دو' ), array( 'tara_genre' => array( $g1 ) ), '2020-02-10 10:00:00' );
$a3 = wp_fixture_track( 'آلبوم کامل', array( $full ), array( 'musics_type' => 'آلبوم' ), array(), '2020-03-10 10:00:00' );
$a4 = wp_fixture_track( 'ویدیوی کامل', array( $full ), array( 'musics_type' => 'موزیک‌ویدیو' ), array(), '2020-04-10 10:00:00' );

/* ---------- سناریو ۲: هنرمند دارای همکار (برای همکاری‌ها) ---------- */
$collab = wp_fixture_artist( 'هنرمند همراه', 'artist-collab', array() );
wp_set_object_terms( $a2, array( $full, $collab ), $tax );

/* ---------- سناریو ۳: هنرمند ناقص ---------- */
$partial = wp_fixture_artist( 'هنرمند ناقص', 'artist-partial', array() );
$b1 = wp_fixture_track( 'آهنگ ناقص', array( $partial ), array( 'musics_type' => 'آهنگ' ), array(), '2021-06-01 10:00:00' );

/* ---------- سناریو ۴: هنرمند بدون اثر/بیوگرافی ---------- */
$empty = wp_fixture_artist( 'هنرمند خالی', 'artist-empty', array() );

echo "== داده‌ها ==\n";
echo "full=$full partial=$partial empty=$empty collab=$collab tracks=$a1,$a2,$a3,$a4,$b1\n";

/* ---------- سربرگ و بیوگرافی ---------- */
$term = get_term( $full, $tax );
t( 'نام هنرمند', 'هنرمند کامل' === $term->name );
t( 'نام لاتین از متای ترم', 'Full Artist' === tara_artist_field( $term, 'artist_en' ) );
t( 'بیوگرافی کوتاه', false !== strpos( tara_artist_bio_short( $term, false ), 'بیوگرافی کوتاهِ واقعی' ) );
t( 'تصویر هنرمند', 'https://cdn.example/full.jpg' === tara_artist_image_url( $term, 'thumbnail' ), (string) tara_artist_image_url( $term, 'thumbnail' ) );

/* ---------- لینک‌های رسمی ---------- */
$links = tara_artist_social_links( $term );
t( 'فقط شبکه‌های http(s) پذیرفته می‌شوند', isset( $links['afacebook'], $links['atelegram'] ) && ! isset( $links['ainstagram'] ), wp_json_encode( array_keys( $links ) ) );
$links_html = tara_artist_links_html( $term );
t( 'لینک‌ها escape شده و rel امن دارند', false !== strpos( $links_html, 'rel="noopener nofollow me"' ) && false === strpos( $links_html, 'javascript:' ) );
t( 'آدرس ساختگی تولید نمی‌شود', false === strpos( $links_html, 'twitter.com' ) && false === strpos( $links_html, 'youtube.com' ) );

/* ---------- آخرین انتشار ---------- */
$latest = tara_artist_latest_release( $term );
t( 'آخرین انتشار با تاریخ واقعی', is_array( $latest ) && $a1 === $latest['id'] && '2025-05-01' === $latest['date'] && 'تاریخ انتشار' === $latest['label'], wp_json_encode( $latest ) );

/* ---------- نوع/دیسکوگرافی ---------- */
t( 'نوع audio', 'audio' === tara_artist_track_type( $a1 ) );
t( 'نوع album', 'album' === tara_artist_track_type( $a3 ) );
t( 'نوع video', 'video' === tara_artist_track_type( $a4 ) );
$groups = tara_artist_discography_groups( $term, 20 );
t( 'گروه‌های دیسکوگرافی درست‌اند', isset( $groups['audio'] ) && 2 === count( $groups['audio'] ) && isset( $groups['album'], $groups['video'] ), wp_json_encode( array_map( 'count', $groups ) ) );
t( 'گروه خالی ساخته نمی‌شود', ! isset( $groups['other'] ) );

/* ---------- همکاری‌ها ---------- */
$collabs = tara_artist_collaborators( $term, 10 );
$names   = wp_list_pluck( $collabs, 'name' );
t( 'همکاری از رابطهٔ واقعی', in_array( 'هنرمند همراه', $names, true ), wp_json_encode( $names ) );
t( 'خود هنرمند در همکاری‌ها نیست', ! in_array( 'هنرمند کامل', $names, true ) );

/* ---------- هنرمند ناقص ---------- */
$pt = get_term( $partial, $tax );
t( 'ناقص: بیوگرافی خالی', '' === tara_artist_bio_short( $pt, false ) );
t( 'ناقص: شبکه خالی', array() === tara_artist_social_links( $pt ) );
t( 'ناقص: آخرین انتشار = تاریخ نوشته با برچسب سایت', ( function () use ( $pt ) { $l = tara_artist_latest_release( $pt ); return is_array( $l ) && 'انتشار در سایت' === $l['label']; } )() );
t( 'ناقص: همکارهایش خالی است', array() === tara_artist_collaborators( $pt, 10 ) );
t( 'ناقص: گروه‌بندی فقط audio', array( 'audio' ) === array_keys( tara_artist_discography_groups( $pt, 10 ) ) );

/* ---------- هنرمند بدون اثر ---------- */
$et = get_term( $empty, $tax );
t( 'خالی: آخرین انتشار null', null === tara_artist_latest_release( $et ) );
t( 'خالی: دیسکوگرافی خالی', array() === tara_artist_discography_groups( $et, 10 ) );
t( 'خالی: لینک‌ها خالی', '' === tara_artist_links_html( $et ) );
t( 'خالی: همکار خالی', array() === tara_artist_collaborators( $et, 10 ) );

/* ---------- لینک داخلی و URL تکراری ---------- */
$link = tara_artist_term_link( $term );
t( 'لینک هنرمند یکتاست', '' !== $link && $link === get_term_link( $term ) );
$page1 = get_permalink( $a1 );
$page2 = get_permalink( $a2 );
t( 'هر اثر لینک یکتای خودش را دارد', $page1 !== $page2 && false !== strpos( $page1, (string) $a1 ) );

/* ---------- Schema ---------- */
$schema_src = file_get_contents( get_template_directory() . '/taxonomy-artist.php' );
t( 'sameAs از لینک‌های ثبت‌شده ساخته می‌شود', false !== strpos( $schema_src, "'sameAs'" ) );
t( 'description اسکیما از بیوگرافی واقعی', false !== strpos( $schema_src, 'tara_artist_bio_short' ) );

/* ---------- XSS در نام هنرمند ---------- */
$xss = wp_fixture_artist( 'هنرمند <script>alert(1)</script>', 'artist-xss', array() );
$xss_link = tara_artist_term_link( get_term( $xss, $tax ) );
$html = tara_artist_links_html( get_term( $xss, $tax ) );
t( 'نام مخرب در لینک‌های هنرمند escape می‌شود', false === strpos( $html, '<script>' ) );

echo "\nRESULT pass={$PASS} fail={$FAIL}\n";
if ( $FAIL ) { echo "FAILED:\n - " . implode( "\n - ", $FAILS ) . "\n"; }
