<?php
/**
 * آزمونِ چیدمانِ سربرگِ ژانر (v3.17.2)
 * ------------------------------------------------------------------
 * باگِ واقعی: کلاژِ کاورها `position:absolute` بود و تا 232px می‌رفت،
 * ولی بدنهٔ متن فقط 150px حاشیه داشت؛ در نتیجه <h1> و بج و آمار زیرِ
 * عکس‌ها می‌افتادند و عنوانِ صفحه دیده نمی‌شد.
 * این آزمون همان شرط را می‌سنجد: کلاژ باید ستونِ مستقلِ گرید باشد.
 */
$PASS = 0; $FAIL = 0; $FAILS = array();
function t( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL, $FAILS;
	if ( $cond ) { $PASS++; echo "PASS $name\n"; } else { $FAIL++; $FAILS[] = $name . ( $detail ? " — $detail" : '' ); echo "FAIL $name" . ( $detail ? " — $detail" : '' ) . "\n"; }
}

$css = (string) file_get_contents( get_template_directory() . '/css/tara-tax.css' );
function css_block( $css, $sel ) {
	$i = strpos( $css, $sel . ' {' );
	if ( false === $i ) { return ''; }
	return substr( $css, $i, strpos( $css, '}', $i ) - $i );
}
$hero    = css_block( $css, '.ty-taxhero' );
$collage = css_block( $css, '.ty-taxhero__collage' );
$body    = css_block( $css, '.ty-taxhero__body' );
$chips   = css_block( $css, '.ty-taxhero__chips' );

t( 'هیرو گرید است', false !== strpos( $hero, 'display: grid' ) || false !== strpos( $hero, 'display:grid' ) );
t( 'گرید دو ستونی دارد', (bool) preg_match( '/grid-template-columns:[^;]*1fr[^;]*auto/', $hero ) );
t( 'ناحیهٔ body/collage/chips تعریف شده', false !== strpos( $hero, '"body collage"' ) && false !== strpos( $hero, '"chips chips"' ) );
t( 'کلاژ دیگر absolute نیست', false === strpos( $collage, 'position: absolute' ), trim( $collage ) );
t( 'کلاژ ناحیهٔ گرید خودش را دارد', false !== strpos( $collage, 'grid-area: collage' ) );
t( 'بدنه حاشیهٔ جعلیِ 150px ندارد', false === strpos( $body, '150px' ), trim( $body ) );
t( 'chips هر دو ستون را می‌گیرد', false !== strpos( $chips, 'grid-area: chips' ) );

/* هندسه: برای چند عرض، ستونِ متن باید به‌قدر کافی بزرگ باشد و
   مجموعِ ستون‌ها از عرضِ ظرف بیرون نزند. */
$rows = array();
$bad  = array();
foreach ( array( 1440, 1200, 1000, 800, 761 ) as $vw ) {
	$pad      = min( max( 20, 0.03 * $vw ), 34 );
	$inner    = $vw - 2 * $pad;
	$collageW = min( max( 180, 0.20 * $vw ), 232 );
	$gap      = $vw <= 1000 ? 18 : min( max( 18, 0.03 * $vw ), 40 );
	$textW    = $inner - $collageW - $gap;
	$rows[]   = "vw={$vw} text=" . round( $textW );
	if ( $textW < 420 || $textW <= 0 ) { $bad[] = $vw; }
}
t( 'در هیچ عرضی ستونِ متن کوچک/منفی نمی‌شود', array() === $bad, implode( ',', $bad ) . ' | ' . implode( ' • ', $rows ) );

/* --- موبایل: تک‌ستونی و بدون هم‌پوشانی --- */
$mob = '';
$i   = strpos( $css, '@media (max-width: 760px)' );
if ( false !== $i ) { $mob = substr( $css, $i ); }
$mob_hero = ( '' !== $mob ) ? css_block( $mob, ".ty-taxhero" ) : '';
t( 'موبایل: هیرو تک‌ستونی است', '' !== $mob_hero && ( false !== strpos( $mob_hero, 'grid-template-columns: minmax(0, 1fr)' ) || (bool) preg_match( '/grid-template-columns:\s*(minmax\(0,\s*1fr\)|1fr)/', $mob_hero ) ), trim( $mob_hero ) );
t( 'موبایل: ناحیهٔ collage/body/chips هست', false !== strpos( $mob_hero, '"collage"' ) && false !== strpos( $mob_hero, '"body"' ) );
$mob_coll = ( '' !== $mob ) ? css_block( $mob, '.ty-taxhero__collage' ) : '';
t( 'موبایل: کلاژ به نوارِ افقی تبدیل می‌شود', false !== strpos( $mob_coll, 'display: flex' ) && false !== strpos( $mob_coll, 'height: auto' ), trim( $mob_coll ) );
t( 'موبایل: کلاژ عرضِ ثابتِ دسکتاپ را نگه نمی‌دارد', false === strpos( $mob_coll, 'clamp(180px' ) );

/* مارک‌آپ: کلاژ قبل از بدنه و بیرونِ آن است */
/* ترم باید مستقلاً ساخته شود؛ اتکا به گزینهٔ ذخیره‌شده شکننده است
   (در اجرای دوم، شناسهٔ قدیمی دیگر وجود ندارد و آزمون SKIP می‌شد). */
$term     = null;
$term_id  = (int) get_option( 'ty_test_genre', 0 );
if ( $term_id ) {
	$maybe = get_term( $term_id, 'tara_genre' );
	if ( $maybe && ! is_wp_error( $maybe ) ) { $term = $maybe; }
}
if ( ! $term ) {
	$name = 'ژانر آزمون هیرو';
	$got  = term_exists( $name, 'tara_genre' );
	if ( ! $got ) { $got = wp_insert_term( $name, 'tara_genre' ); }
	$term_id = is_array( $got ) ? (int) $got['term_id'] : (int) $got;
	$term    = get_term( $term_id, 'tara_genre' );
	update_option( 'ty_test_genre', $term_id );
}
if ( ! $term || is_wp_error( $term ) ) { echo "SKIP: term\n"; }
else {
	$p = wp_insert_post( array( 'post_title' => 'اثر هیرو', 'post_type' => 'post', 'post_status' => 'publish' ) );
	wp_set_object_terms( $p, array( $term_id ), 'tara_genre' );
	update_post_meta( $p, 'musics_type', 'آهنگ' );
	update_post_meta( $p, 'music128', 'https://cdn.example/h.mp3' );
	clean_term_cache( $term_id, 'tara_genre' );

	$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 5, 'tax_query' => array( array( 'taxonomy' => 'tara_genre', 'field' => 'term_id', 'terms' => array( $term_id ) ) ) ) );
	$GLOBALS['wp_query']->is_tax = true;
	$GLOBALS['wp_query']->queried_object = $term;
	$GLOBALS['wp_query']->queried_object_id = $term_id;
	ob_start();
	include get_template_directory() . '/taxonomy-tara_genre.php';
	$html = ob_get_clean();

	t( 'صفحهٔ ژانر رندر می‌شود', strlen( $html ) > 3000, (string) strlen( $html ) );
	t( 'عنوان <h1> در صفحه هست', (bool) preg_match( '/<h1[^>]*ty-taxhero__title[^>]*>[^<]+/u', $html ) );
	t( 'کلاژ در مارک‌آپ هست', false !== strpos( $html, 'ty-taxhero__collage' ) );
	$ci = strpos( $html, 'ty-taxhero__collage' );
	$bi = strpos( $html, 'ty-taxhero__body' );
	t( 'کلاژ در DOM قبل از بدنه است', $ci !== false && $bi !== false && $ci < $bi, "collage@$ci body@$bi" );
	/* کلاژ نباید داخل بدنه باشد */
	$body_start = $bi;
	$body_end   = strpos( $html, 'ty-taxhero__chips', $body_start );
	$in_body    = $body_end ? substr( $html, $body_start, $body_end - $body_start ) : '';
	t( 'کلاژ داخل بدنهٔ متن نیست', false === strpos( $in_body, 'ty-taxhero__collage' ) );
	t( 'بدون warning/notice در خروجی', false === stripos( $html, 'Warning:' ) && false === stripos( $html, 'Notice:' ) );
}

echo "\nRESULT pass={$PASS} fail={$FAIL}\n";
if ( $FAIL ) { echo "FAILED:\n - " . implode( "\n - ", $FAILS ) . "\n"; }
