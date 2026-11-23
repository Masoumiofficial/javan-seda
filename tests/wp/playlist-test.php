<?php
require dirname( __FILE__ ) . '/fixtures.php';
$PASS = 0; $FAIL = 0; $FAILS = array();
function t( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL, $FAILS;
	if ( $cond ) { $PASS++; echo "PASS $name\n"; } else { $FAIL++; $FAILS[] = $name . ( $detail ? " — $detail" : '' ); echo "FAIL $name" . ( $detail ? " — $detail" : '' ) . "\n"; }
}
function audio_meta( $t = 'آهنگ' ) { return array( 'musics_type' => $t, 'music128' => 'https://cdn.example/a.mp3' ); }

wp_fixture_reset();
$art1 = wp_fixture_artist( 'هنرمند پلی', 'artist-play', array() );
$art2 = wp_fixture_artist( 'هنرمند دوم', 'artist-play-2', array() );

$t1 = wp_fixture_track( 'ترک اول', array( $art1 ), audio_meta(), array(), '2024-01-01 10:00:00' );
$t2 = wp_fixture_track( 'ترک دوم', array( $art2 ), audio_meta(), array(), '2024-02-01 10:00:00' );
$t3 = wp_fixture_track( 'ترک سوم', array( $art1 ), audio_meta(), array(), '2024-03-01 10:00:00' );

/* پلی‌لیست کامل */
$pl_full = wp_insert_post( array( 'post_title' => 'پلی‌لیست کامل', 'post_type' => 'tara_playlist', 'post_status' => 'publish', 'post_content' => 'مجموعه‌ای برای شب‌های بارانی.' ) );
update_post_meta( $pl_full, '_tara_playlist_tracks', array( $t1, $t2, $t3 ) );
/* پلی‌لیست خالی */
$pl_empty = wp_insert_post( array( 'post_title' => 'پلی‌لیست خالی', 'post_type' => 'tara_playlist', 'post_status' => 'publish' ) );
update_post_meta( $pl_empty, '_tara_playlist_tracks', array() );
/* پلی‌لیست پیش‌نویس */
$pl_draft = wp_insert_post( array( 'post_title' => 'پیش‌نویس', 'post_type' => 'tara_playlist', 'post_status' => 'draft' ) );
update_post_meta( $pl_draft, '_tara_playlist_tracks', array( $t1 ) );
/* پلی‌لیست با آهنگ غیرقابل‌پخش */
$t_silent = wp_fixture_track( 'ترک بی‌فایل', array( $art1 ), array( 'musics_type' => 'آهنگ' ), array(), '2024-04-01 10:00:00' );
$pl_silent = wp_insert_post( array( 'post_title' => 'پلی‌لیست بی‌صدا', 'post_type' => 'tara_playlist', 'post_status' => 'publish' ) );
update_post_meta( $pl_silent, '_tara_playlist_tracks', array( $t_silent ) );

echo "== شناسه‌ها: full=$pl_full empty=$pl_empty draft=$pl_draft silent=$pl_silent tracks=$t1,$t2,$t3,$t_silent ==\n";

/* ---------- شرط عمومی ---------- */
t( 'کامل: عمومی است', true === tara_playlist_is_public( $pl_full ) );
t( 'خالی: عمومی نیست', false === tara_playlist_is_public( $pl_empty ) );
t( 'پیش‌نویس: عمومی نیست', false === tara_playlist_is_public( $pl_draft ) );
t( 'بدون آهنگِ قابل‌پخش: عمومی نیست', false === tara_playlist_is_public( $pl_silent ) );

/* ---------- فهرست ---------- */
$ids = tara_playlist_ids( array( 'limit' => 50 ) );
t( 'فهرست فقط پلی‌لیست‌های واقعی را دارد', array( $pl_full ) === array_values( array_map( 'intval', $ids ) ), wp_json_encode( $ids ) );

/* ---------- شمارش واقعی ---------- */
$tracks = tara_playlist_tracks( $pl_full );
t( 'شمارش آهنگ‌ها = دادهٔ واقعی (۳)', 3 === count( $tracks ), (string) count( $tracks ) );
t( 'ترتیب آهنگ‌ها حفظ می‌شود', array( $t1, $t2, $t3 ) === wp_list_pluck( $tracks, 'id' ), wp_json_encode( wp_list_pluck( $tracks, 'id' ) ) );

/* ---------- لینک هنرمند ---------- */
$by_id = array();
foreach ( $tracks as $tr ) { $by_id[ $tr['id'] ] = $tr; }
t( 'لینک هنرمند ترک اول درست است', get_term_link( get_term( $art1, tara_artist_tax() ) ) === $by_id[ $t1 ]['artist_url'], (string) $by_id[ $t1 ]['artist_url'] );
t( 'نام هنرمند درست است', 'هنرمند پلی' === $by_id[ $t1 ]['artist'], (string) $by_id[ $t1 ]['artist'] );
t( 'لینک آهنگ معتبر است', get_permalink( $t1 ) === $by_id[ $t1 ]['permalink'] );

/* ---------- پوشش ---------- */
/* سیاست قالب: در نبود کاور واقعی، «جانشین»ِ همراهِ قالب برمی‌گردد
   (همان تصویری که کارت آهنگ هم استفاده می‌کند)؛ داده یا URL ساختگی نیست. */
$cover = tara_playlist_cover( $pl_full );
$placeholder = get_template_directory_uri() . '/images/nomusics.jpg';
t( 'در نبود کاور، جانشینِ موجودِ قالب برمی‌گردد نه URL ساختگی', $placeholder === $cover['url'], wp_json_encode( $cover ) );
t( 'فایل جانشین واقعاً در قالب هست', file_exists( get_template_directory() . '/images/nomusics.jpg' ) );
$thumb = wp_fixture_attachment( 'کاور پلی', get_template_directory() . '/images/nomusics.jpg' );
set_post_thumbnail( $pl_full, $thumb );
$cover2 = tara_playlist_cover( $pl_full );
t( 'با تصویر شاخص، کاور واقعی ساخت می‌شود', false !== strpos( $cover2['url'], 'nomusics' ) && 'thumbnail' === $cover2['source'], wp_json_encode( $cover2 ) );

/* ---------- توضیح ---------- */
t( 'توضیح از متن واقعی', false !== strpos( tara_playlist_description( $pl_full ), 'بارانی' ) );
t( 'بدون متن ⇒ توضیح خالی (نه متن ساختگی)', '' === tara_playlist_description( $pl_empty ) );

/* ---------- مارک‌آپ ---------- */
$html = tara_playlist_tracks_html( $pl_full );
t( 'مارک‌آپ: هر سه آهنگ', 3 === substr_count( $html, '<li class="ty-playlists__track">' ), (string) substr_count( $html, '<li class="ty-playlists__track">' ) );
t( 'مارک‌آپ: لینک هنرمند', false !== strpos( $html, 'mohsen' ) || false !== strpos( $html, 'artist-play' ) );
t( 'مارک‌آپ: هیچ اسکریپت/آدرس مخربی نیست', false === stripos( $html, '<script' ) && false === stripos( $html, 'javascript:' ) );
t( 'کارت پلی‌لیست خالی ساخته نمی‌شود', '' === tara_playlist_card_html( $pl_empty, 'http://localhost/?page_id=1' ), (string) strlen( tara_playlist_card_html( $pl_empty, 'http://localhost/?page_id=1' ) ) );

/* ---------- رابطهٔ صفحهٔ آهنگ ---------- */
t( 'آهنگ داخل پلی‌لیست، رابطه می‌دهد', array( $pl_full ) === array_values( tara_playlist_related_to_track( $t1 ) ), wp_json_encode( tara_playlist_related_to_track( $t1 ) ) );
t( 'آهنگ بیرون پلی‌لیست رابطه ندارد', array() === tara_playlist_related_to_track( $t_silent ) );

/* ---------- LIKE که ۱۲ را در ۱۱۲ نگیرد ---------- */
$t112 = wp_fixture_track( 'ترک ۱۱۲', array( $art1 ), audio_meta(), array(), '2024-05-01 10:00:00' );
t( 'شناسهٔ ۱۲ داخل ۱۱۲ تطبیق داده نمی‌شود', ! in_array( (int) $t112, array_map( 'absint', tara_playlist_audio_ids( array( $t1 ) ) ), true ) );

/* ---------- شمارش کارت ---------- */
$card = tara_playlist_card_html( $pl_full, 'http://localhost/?page_id=1' );
t( 'کارت: شمارش واقعی روی کارت', false !== strpos( wp_strip_all_tags( $card ), '۳' ), wp_strip_all_tags( $card ) );
t( 'کارت: لینک خزش‌پذیر با list=', false !== strpos( $card, 'list=' . $pl_full ) );

echo "\nRESULT pass={$PASS} fail={$FAIL}\n";
if ( $FAIL ) { echo "FAILED:\n - " . implode( "\n - ", $FAILS ) . "\n"; }
