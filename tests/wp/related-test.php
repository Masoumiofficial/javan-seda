<?php
require dirname( __FILE__ ) . '/fixtures.php';
$PASS = 0; $FAIL = 0; $FAILS = array();
function t( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL, $FAILS;
	if ( $cond ) { $PASS++; echo "PASS $name\n"; } else { $FAIL++; $FAILS[] = $name . ( $detail ? " — $detail" : '' ); echo "FAIL $name" . ( $detail ? " — $detail" : '' ) . "\n"; }
}
function m( $t = 'آهنگ' ) { return array( 'musics_type' => $t, 'music128' => 'https://cdn.example/a.mp3' ); }

wp_fixture_reset();

/* چهار هنرمند مستقل تا هر مرحله جدا سنجیده شود. */
$A = wp_fixture_artist( 'هنرمند الف', 'artist-a', array() );
$B = wp_fixture_artist( 'هنرمند ب', 'artist-b', array() );
$C = wp_fixture_artist( 'هنرمند ج', 'artist-c', array() );
$D = wp_fixture_artist( 'هنرمند د', 'artist-d', array() );

$g1 = wp_insert_term( 'ژانر یک', 'tara_genre' ); $g1 = (int) $g1['term_id'];
$g2 = wp_insert_term( 'ژانر دو', 'tara_genre' ); $g2 = (int) $g2['term_id'];
$mo1 = wp_insert_term( 'حال یک', 'tara_mood' ); $mo1 = (int) $mo1['term_id'];

$main  = wp_fixture_track( 'اثر اصلی', array( $A ), m(), array( 'tara_genre' => array( $g1 ), 'tara_mood' => array( $mo1 ) ), '2024-05-01 10:00:00' );
$same  = wp_fixture_track( 'همان هنرمند', array( $A ), m(), array( 'tara_genre' => array( $g2 ) ), '2024-05-02 10:00:00' );
$genre = wp_fixture_track( 'همان ژانر', array( $B ), m(), array( 'tara_genre' => array( $g1 ) ), '2024-05-03 10:00:00' );
$mood  = wp_fixture_track( 'همان حال', array( $C ), m(), array( 'tara_mood' => array( $mo1 ) ), '2024-05-04 10:00:00' );
$sib   = wp_fixture_track( 'هم‌پلی‌لیست', array( $D ), m(), array(), '2024-05-05 10:00:00' );
$unrel = wp_fixture_track( 'بی‌ربط', array( $D ), m(), array(), '2024-05-06 10:00:00' );

$pl = wp_insert_post( array( 'post_title' => 'پلی‌لیست مشترک', 'post_type' => 'tara_playlist', 'post_status' => 'publish' ) );
update_post_meta( $pl, '_tara_playlist_tracks', array( $main, $sib ) );

echo "== main=$main same=$same genre=$genre mood=$mood sib=$sib unrel=$unrel pl=$pl ==\n";

/* ---------- ترتیب دقیق مرحله‌ها ---------- */
$r = tara_related_tracks( $main, 5 );
t( '۱) هنرمند اول می‌آید', $same === $r[0], wp_json_encode( $r ) );
t( '۲) سپس ژانر مشترک', $genre === $r[1], wp_json_encode( $r ) );
t( '۳) سپس حال‌وهوای مشترک', $mood === $r[2], wp_json_encode( $r ) );
t( '۴) سپس هم‌نشینِ پلی‌لیست', $sib === $r[3], wp_json_encode( $r ) );
t( 'محتوای بی‌ربط برای پرکردن اضافه نمی‌شود', ! in_array( $unrel, $r, true ), wp_json_encode( $r ) );
t( 'خودِ اثر در خروجی نیست', ! in_array( $main, $r, true ) );

/* ---------- بدون تکرار ---------- */
t( 'بین مرحله‌ها تکرار نیست', count( $r ) === count( array_unique( $r ) ), wp_json_encode( $r ) );

/* ---------- سقف ---------- */
$r2 = tara_related_tracks( $main, 2 );
t( 'سقف رعایت می‌شود (۲)', 2 === count( $r2 ), wp_json_encode( $r2 ) );
t( 'سقف: اولویت با هنرمند است حتی با سقف کم', $same === $r2[0], wp_json_encode( $r2 ) );

/* ---------- خاموش‌کردن مرحله‌ها ---------- */
$no_pl = tara_related_tracks( $main, 10, array(), array( 'playlists' => false, 'editorial' => false ) );
t( 'با خاموش‌کردن پلی‌لیست، هم‌نشین نمی‌آید', ! in_array( $sib, $no_pl, true ), wp_json_encode( $no_pl ) );

/* ---------- تحریریه = چسبانِ واقعی وردپرس ---------- */
update_option( 'sticky_posts', array( $unrel ) );
$r3 = tara_related_tracks( $main, 10 );
t( '۵) انتخاب تحریریه با چسبانِ واقعی می‌آید', in_array( $unrel, $r3, true ), wp_json_encode( $r3 ) );
t( 'تحریریه آخرِ زنجیره است', end( $r3 ) === $unrel, wp_json_encode( $r3 ) );
update_option( 'sticky_posts', array() );
t( 'بدون چسبان، تحریریه چیزی اضافه نمی‌کند', ! in_array( $unrel, tara_related_tracks( $main, 10 ), true ) );

/* چسبانِ پیش‌نویس پذیرفته نمی‌شود */
$draft = wp_insert_post( array( 'post_title' => 'چسبانِ پیش‌نویس', 'post_type' => 'post', 'post_status' => 'draft' ) );
update_option( 'sticky_posts', array( $draft ) );
t( 'چسبانِ منتشرنشده وارد نمی‌شود', ! in_array( (int) $draft, tara_related_tracks( $main, 10 ), true ) );
update_option( 'sticky_posts', array() );

/* ---------- اثر بی‌رابطه ---------- */
/* «بی‌ربط» هنوز با هنرمند D مشترک است، پس لزوماً خالی نیست؛ اثری که
   هیچ رابطه‌ای ندارد باید هنرمندِ تازه و بدون ژانر/پل/چسبان داشته باشد. */
$Z      = wp_fixture_artist( 'هنرمند تنها', 'artist-z', array() );
$lonely = wp_fixture_track( 'اثر تنها', array( $Z ), m(), array(), '2024-05-07 10:00:00' );
t( 'اثر بدون هیچ رابطه‌ای خروجی خالی دارد', array() === tara_related_tracks( $lonely, 8 ), wp_json_encode( tara_related_tracks( $lonely, 8 ) ) );
t( 'artifact: برای اثر تنها بلوک چاپ نمی‌شود', '' === tara_related_tracks_html( $lonely, array( 'limit' => 4 ) ) );
/* زنجیره برای $sib: اول هم‌هنرمند ($unrel)، بعد هم‌نشینِ پلی‌لیست ($main). */
t( 'زنجیرهٔ کامل: هم‌هنرمند بعد هم‌نشینِ پلی‌لیست', array( $unrel, $main ) === tara_related_tracks( $sib, 8 ), wp_json_encode( tara_related_tracks( $sib, 8 ) ) );

/* ---------- امنیت و خزش‌پذیری مارک‌آپ ---------- */
$html = tara_related_tracks_html( $main, array( 'limit' => 6 ) );
t( 'مارک‌آپ: لینک‌ها HTML خزش‌پذیر', false !== strpos( $html, get_permalink( $sib ) ) );
t( 'مارک‌آپ: بدون رویداد inline/AJAX', false === stripos( $html, '<script' ) && false === stripos( $html, 'onclick' ) );
t( 'مارک‌آپ: خودِ اثر لینک نشده', false === strpos( $html, get_permalink( $main ) ) );
/* بلوک «پیشنهادها» عمداً آثارِ همان هنرمند را چاپ نمی‌کند (آن‌ها در بلوک
   «سایر آهنگ‌های هنرمند» هستند)؛ پس تکرارِ بین دو بلوک هم رخ نمی‌دهد. */
t( 'مارک‌آپ: بلوک پیشنهادها اثرِ هم‌هنرمند را تکرار نمی‌کند', false === strpos( $html, 'href="' . get_permalink( $same ) . '"' ) );
preg_match_all( '/href="([^"]+)"/', $html, $mm );
t( 'مارک‌آپ: هیچ لینکی در بلوک دوبار نیست', count( $mm[1] ) === count( array_unique( $mm[1] ) ), wp_json_encode( $mm[1] ) );
t( 'بلوک «سایر آهنگ‌های هنرمند» همان اثر را دارد', false !== strpos( tara_related_by_artist_html( $main ), 'href="' . get_permalink( $same ) . '"' ) );

/* ---------- نام مخرب ---------- */
$evil = wp_insert_post( array( 'post_title' => 'بد', 'post_type' => 'post', 'post_status' => 'publish' ) );
update_post_meta( $evil, 'artist', '<script>alert(1)</script>' );
$html2 = tara_related_tracks_html( $main, array( 'limit' => 8 ) );
t( 'نام/عنوان مخرب escape می‌شود', false === strpos( $html2, '<script>alert(1)</script>' ) );

echo "\nRESULT pass={$PASS} fail={$FAIL}\n";
if ( $FAIL ) { echo "FAILED:\n - " . implode( "\n - ", $FAILS ) . "\n"; }
