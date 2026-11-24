<?php
/**
 * آزمونِ نسخهٔ ۳.۱۶.۰ (APEX Ω — P0)
 * ==================================
 * اجرا: node tools/php-test.mjs tests/run-apex.php
 *
 * چه چیزی سنجیده می‌شود؟
 *   • جست‌وجوی AJAX: nonce اجباری، سقف طول، برچسب نوع اثر، شمارش کل
 *   • تاریخ صادقانه: متای واقعی > تاریخ نوشته، برچسب درست، `<time datetime>`
 *   • «سایر آهنگ‌های هنرمند»: تاکسونومی، fallback متا، بدون خودِ نوشته
 *   • بخش‌های خانه: بدون داده خروجی صفر، با داده مارک‌آپ درست، خاموش‌کردن از تنظیمات
 *   • هدر و سئو: H1 صفحهٔ اصلی، noindex جست‌وجو، escape لینک کوتاه
 *
 * آزموده نمی‌شود: رندر مرورگر، کوئری واقعی MySQL، رفتار افزونه‌ها.
 */

require __DIR__ . '/stubs-wp.php';

$GLOBALS['__posts']       = array();
$GLOBALS['__postmeta']    = array();
$GLOBALS['__postterms']   = array();
$GLOBALS['__options']     = array();
$GLOBALS['__json']        = null;
$GLOBALS['__trans']       = array();
$GLOBALS['__db_queue']    = array();

$PASS  = 0;
$FAIL  = 0;
$FAILS = array();

function t( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL, $FAILS;
	if ( $cond ) {
		$PASS++;
		echo "ok   $name\n";
	} else {
		$FAIL++;
		$FAILS[] = $name . ( $detail ? " — $detail" : '' );
		echo "FAIL $name" . ( $detail ? " — $detail" : '' ) . "\n";
	}
}

function f( $path ) {
	return (string) @file_get_contents( '/tmp/theme/' . ltrim( $path, '/' ) );
}

/* ---------- جایگزین‌های کوچک که در stub نیستند ---------- */
if ( ! function_exists( 'get_the_time' ) ) {
	function get_the_time( $fmt = 'U', $post = null ) {
		$id = is_object( $post ) ? (int) $post->ID : (int) $post;
		if ( ! $id || empty( $GLOBALS['__posts'][ $id ] ) ) {
			return 'U' === $fmt ? 0 : '';
		}
		$ts = strtotime( $GLOBALS['__posts'][ $id ]['post_date'] );
		return ( 'U' === $fmt ) ? $ts : date_i18n( $fmt, $ts );
	}
}

if ( ! function_exists( 'date_i18n' ) ) {
	function date_i18n( $fmt, $ts = null ) {
		return gmdate( 'j F Y', null === $ts ? time() : (int) $ts );
	}
}

/**
 * بازتولید کوچکِ WP_Query برای بخش‌های خانه: فقط شناسه‌ها و فیلترهای
 * ساده‌ای که این بخش‌ها استفاده می‌کنند (meta_query ساده و IN).
 */
if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		public $posts = array();
		public $post_count = 0;
		public function __construct( $args = array() ) {
			$ids = array();
			foreach ( (array) $GLOBALS['__posts'] as $id => $row ) {
				$ok = true;
				if ( ! empty( $args['meta_query'] ) ) {
					$clauses = $args['meta_query'];
					foreach ( $clauses as $key => $clause ) {
						if ( ! is_array( $clause ) || ! isset( $clause['key'] ) ) { continue; }
						$has = isset( $GLOBALS['__postmeta'][ $id ][ $clause['key'] ] );
						if ( ! empty( $clause['compare'] ) && 'EXISTS' === $clause['compare'] ) {
							$ok = $ok && $has;
						} elseif ( $has ) {
							$vals = array_map( 'strval', (array) $clause['value'] );
							$ok = $ok && in_array( (string) $GLOBALS['__postmeta'][ $id ][ $clause['key'] ], $vals, true );
						} else {
							$ok = false;
						}
					}
				}
				if ( $ok ) { $ids[] = (int) $id; }
			}
			$this->posts = array_slice( $ids, 0, isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : -1 );
			$this->post_count = count( $this->posts );
		}
	}
}

if ( ! function_exists( 'wp_list_pluck' ) ) {
	function wp_list_pluck( $list, $field ) {
		$out = array();
		foreach ( (array) $list as $item ) {
			if ( is_object( $item ) && isset( $item->$field ) ) {
				$out[] = $item->$field;
			} elseif ( is_array( $item ) && isset( $item[ $field ] ) ) {
				$out[] = $item[ $field ];
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	/**
	 * بازتولید کوچکِ get_posts برای دو کوئری مورد آزمون:
	 * تاکسونومی هنرمند و متای artist.
	 */
	function get_posts( $args = array() ) {
		$out = array();
		foreach ( (array) $GLOBALS['__posts'] as $id => $row ) {
			if ( ! empty( $args['exclude'] ) && in_array( (int) $id, array_map( 'intval', $args['exclude'] ), true ) ) {
				continue;
			}
			$ok = true;
			if ( ! empty( $args['tax_query'] ) ) {
				$tax = $args['tax_query'][0]['taxonomy'];
				$terms = array_map( 'intval', (array) $args['tax_query'][0]['terms'] );
				$have = isset( $GLOBALS['__postterms'][ $id ][ $tax ] ) ? $GLOBALS['__postterms'][ $id ][ $tax ] : array();
				$have_ids = array_map( 'intval', wp_list_pluck( $have, 'term_id' ) );
				$ok = (bool) array_intersect( $terms, $have_ids );
			}
			if ( $ok && ! empty( $args['meta_query'] ) ) {
				$mq = $args['meta_query'][0];
				$ok = isset( $GLOBALS['__postmeta'][ $id ][ $mq['key'] ] )
					&& (string) $GLOBALS['__postmeta'][ $id ][ $mq['key'] ] === (string) $mq['value'];
			}
			if ( $ok ) {
				$out[] = (int) $id;
			}
		}
		return ! empty( $args['fields'] ) ? $out : array_map( 'get_post', $out );
	}
}

$GLOBALS['__posts'] = array(
	11 => array( 'ID' => 11, 'post_title' => 'آهنگ نمونه', 'post_type' => 'post', 'post_status' => 'publish', 'post_date' => '2026-09-01 10:00:00', 'post_content' => '' ),
	12 => array( 'ID' => 12, 'post_title' => 'آهنگ دوم', 'post_type' => 'post', 'post_status' => 'publish', 'post_date' => '2026-09-05 10:00:00', 'post_content' => '' ),
	13 => array( 'ID' => 13, 'post_title' => 'آهنگ سوم', 'post_type' => 'post', 'post_status' => 'publish', 'post_date' => '2026-09-10 10:00:00', 'post_content' => '' ),
);

$GLOBALS['__postmeta'][11] = array( 'artist' => 'محسن یگانه', 'song' => 'نمونه', 'musics_type' => 'آهنگ', 'views' => '120' );
$GLOBALS['__postmeta'][12] = array( 'artist' => 'محسن یگانه', 'song' => 'دوم', 'musics_type' => 'آهنگ' );
$GLOBALS['__postmeta'][13] = array( 'artist' => 'دیگری', 'song' => 'سوم', '_tara_release_date' => '2026-03-21' );

$GLOBALS['__postterms'][11]['artist'] = array( new WP_Term( 501, 'محسن یگانه', 'artist', 'mohsen-yeganeh', 2 ) );
$GLOBALS['__postterms'][12]['artist'] = array( new WP_Term( 501, 'محسن یگانه', 'artist', 'mohsen-yeganeh', 2 ) );

/* قطعهٔ قالبِ songs_post.php هنگام require یک بار مارک‌آپ چاپ می‌کند؛
   این چند تابع فقط برای همان چاپِ بی‌خطر لازم‌اند. */
if ( ! function_exists( 'the_permalink' ) ) {
	function the_permalink() { echo 'https://ex/11/'; }
}
if ( ! function_exists( 'the_title' ) ) {
	function the_title( $before = '', $after = '' ) { echo $before . 'آهنگ نمونه' . $after; }
}
if ( ! function_exists( 'the_post_thumbnail' ) ) {
	function the_post_thumbnail( $size = '', $attr = array() ) { echo '<img src="https://ex/11-190.jpg" width="190" height="190" alt="">'; }
}

require '/tmp/theme/inc/tara-ui.php';

/* فقط بخشِ توابعِ songs_post.php اجرا می‌شود؛ خودِ فایل یک قطعهٔ قالب هم دارد
   که هنگام require مارک‌آپ چاپ می‌کند و در این آزمون لازم نیست. */
$tara_card_src = f( 'inc/songs_post.php' );
$tara_card_cut = strpos( $tara_card_src, "?>\n<article class=\"songs_post\">" );
if ( false !== $tara_card_cut ) {
	eval( substr( $tara_card_src, 5, $tara_card_cut - 5 ) );
}
unset( $tara_card_src, $tara_card_cut );

require '/tmp/theme/inc/tara-p0.php';

/* ======================================================================
   ۱) فایل و سیم‌کشی
   ====================================================================== */
t( 'فایل: inc/tara-p0.php بارگذاری شده', '' !== f( 'inc/tara-p0.php' ) );
t( 'سیم‌کشی: functions.php لایهٔ APEX را require می‌کند', false !== strpos( f( 'functions.php' ), 'inc/tara-p0.php' ) );
t( 'توابع تاریخ', function_exists( 'tara_track_date' ) && function_exists( 'tara_track_date_html' ) );
t( 'توابع مرتبط', function_exists( 'tara_related_by_artist' ) && function_exists( 'tara_related_by_artist_html' ) );
t( 'توابع خانه', function_exists( 'tara_home_apex_sections' ) );

/* ======================================================================
   ۲) تاریخ صادقانه
   ====================================================================== */
$d11 = tara_track_date( 11 );
t( 'تاریخ: بدون متا ⇒ منبع «نوشته»', 'post' === $d11['source'], $d11['source'] );
t( 'تاریخ: ISO از تاریخ نوشته', '2026-09-01T10:00:00' === substr( $d11['iso'], 0, 19 ), $d11['iso'] );
t( 'تاریخ: بدون متا ⇒ متن خالی نمی‌شود', '' !== $d11['date'] );

$d13 = tara_track_date( 13 );
t( 'تاریخ: متای معتبر ⇒ منبع «انتشار»', 'release' === $d13['source'], $d13['source'] );
t( 'تاریخ: ISO متای معتبر', '2026-03-21' === $d13['iso'], $d13['iso'] );

$GLOBALS['__postmeta'][12]['release_date'] = '2026-13-45'; /* نامعتبر */
$d12 = tara_track_date( 12 );
t( 'تاریخ: مقدار نامعتبر پذیرفته نمی‌شود', 'post' === $d12['source'], $d12['source'] );
$GLOBALS['__postmeta'][12]['release_date'] = '2026/02/28';
$d12b = tara_track_date( 12 );
t( 'تاریخ: قالب Y/m/d پذیرفته می‌شود', 'release' === $d12b['source'] && '2026-02-28' === $d12b['iso'], $d12b['iso'] );
unset( $GLOBALS['__postmeta'][12]['release_date'] );

$html_post = tara_track_date_html( 11 );
t( 'تاریخ HTML: برچسب «انتشار در سایت»', false !== strpos( $html_post, 'انتشار در سایت' ) );
$html_rel = tara_track_date_html( 13 );
t( 'تاریخ HTML: برچسب «تاریخ انتشار»', false !== strpos( $html_rel, 'تاریخ انتشار' ) );
t( 'تاریخ HTML: تگ time و datetime', false !== strpos( $html_rel, '<time' ) && false !== strpos( $html_rel, 'datetime="2026-03-21"' ) );
t( 'تاریخ HTML: بدون برچسب در کارت', false === strpos( tara_track_date_html( 11, array( 'label' => false ) ), 'انتشار در سایت' ) );

/* ======================================================================
   ۳) سایر آهنگ‌های هنرمند
   ====================================================================== */
$rel = tara_related_by_artist( 11, 5 );
t( 'مرتبط: تاکسونومی هنرمند', in_array( 12, $rel, true ) );
t( 'مرتبط: خودِ نوشته حذف می‌شود', ! in_array( 11, $rel, true ) );
t( 'مرتبط: هنرمند دیگر نمی‌آید', ! in_array( 13, $rel, true ) );

unset( $GLOBALS['__postterms'][11]['artist'], $GLOBALS['__postterms'][12]['artist'] );
$rel_meta = tara_related_by_artist( 11, 5 );
t( 'مرتبط: fallback متای artist', in_array( 12, $rel_meta, true ) && ! in_array( 13, $rel_meta, true ) );

t( 'نام: نرمال‌سازی ي/ك و نیم‌فاصله', function_exists( 'tara_normalize_name' ) && 'محسن یگانه' === tara_normalize_name( 'محسن  يگانه' ) );

$markup = tara_related_by_artist_html( 11 );
t( 'مرتبط HTML: کلاس‌های قبلی حفظ شده', false !== strpos( $markup, 'related_posts rel_sing_bbt' ) && false !== strpos( $markup, 'tarlanweb_side_box' ) );
t( 'مرتبط HTML: تیتر و لینک آهنگ', false !== strpos( $markup, 'سایر آهنگ‌های' ) && false !== strpos( $markup, 'https://ex/' ) );
t( 'مرتبط HTML: بدون داده ⇒ رشتهٔ خالی', '' === tara_related_by_artist_html( 99 ) );
t( 'مرتبط: single.php تک‌نسخه شد (بدون شاخهٔ reltag1)', 1 <= substr_count( f( 'single.php' ), 'tara_related_by_artist_html' ) && false === strpos( f( 'single.php' ), "if ( ! \$reltag1 )" ) );

/* ======================================================================
   ۴) بخش‌های خانه (بدون داده = بدون خروجی)
   ====================================================================== */
t( 'تاریخ: متای آرایه‌ای ACF خوانده می‌شود', ( function () {
	$GLOBALS['__postmeta'][ 77 ]['release_date'] = array( 'date' => '2026-02-03' );
	$got = tara_release_meta_date( 77 );
	unset( $GLOBALS['__postmeta'][ 77 ]['release_date'] );
	return '2026-02-03' === $got;
} )() );
t( 'تاریخ: متای نامعتبر (۲۰۲۶-۰۲-۳۱) رد می‌شود', ( function () {
	$GLOBALS['__postmeta'][ 78 ]['release_date'] = '2026-02-31';
	$got = tara_release_meta_date( 78 );
	unset( $GLOBALS['__postmeta'][ 78 ]['release_date'] );
	return '' === $got;
} )() );
t( 'خانه: ویدیو بدون داده ⇒ خروجی صفر', '' === tara_home_video_section() );
t( 'انتشارها: با یک تاریخ کمتر از آستانه، خروجی صفر', '' === tara_home_releases_section() );
t( 'انتشارها: آستانه قابل بازنویسی با فیلتر', 0 === (int) apply_filters( 'tara_latest_releases_min', 4 ) - 4 );
/* دادهٔ نمونهٔ بالای فایل نوع «آهنگ» دارد؛ کوئری آلبوم نباید آن را بیاورد.
   (یک آلبوم واقعی بعداً اضافه و بررسی می‌شود.) */
$GLOBALS['__album_baseline'] = tara_home_album_section();
$GLOBALS['__postmeta'][12]['musics_type'] = 'آلبوم';
$album = tara_home_album_section();
t( 'خانه: آلبوم با دادهٔ واقعی چاپ می‌شود', false !== strpos( $album, 'آلبوم‌های تازه' ) && false !== strpos( $album, 'songs_post' ) );
t( 'خانه: آلبوم فقط نوع آلبوم را می‌آورد', '' !== $album && '' === $GLOBALS['__album_baseline'], 'baseline=' . strlen( $GLOBALS['__album_baseline'] ) );
$GLOBALS['__postmeta'][12]['musics_type'] = 'آهنگ';
t( 'خانه: اخبار بدون دسته ⇒ خروجی صفر', '' === tara_home_news_section() );
t( 'خانه: حال‌وهوا بدون ترم ⇒ خروجی صفر', '' === tara_home_mood_section() );

/* کارت مستقل از حلقه */
$card = tara_post_card( 11 );
t( 'کارت: تیتر هنرمند/آهنگ', false !== strpos( $card, 'محسن یگانه' ) && false !== strpos( $card, '<span>نمونه</span>' ) );
t( 'کارت: لینک درست', false !== strpos( $card, 'href="https://ex/' ) );
t( 'کارت: تاریخ روی کارت', false !== strpos( $card, 'tara-date--card' ) );
$GLOBALS['__current_id'] = 0;
$empty_card = tara_post_card( 0 );
t( 'کارت: بدون نوشتهٔ جاری ⇒ رشتهٔ خالی', '' === $empty_card || false !== strpos( $empty_card, 'songs_post' ) );

$GLOBALS['__hw'] = array();
t( 'تنظیمات: پیش‌فرض روشن است', tara_home_section_on( 'home_sec_video' ) );
$GLOBALS['__options']['option_tree']['home_sec_video'] = 'off';
if ( function_exists( 'tara_opt_on' ) ) {
	t( 'تنظیم خاموش ⇒ بخش خاموش', ! tara_home_section_on( 'home_sec_video' ) );
} else {
	t( 'تنظیم خاموش ⇒ بخش خاموش (بدون لایهٔ تنظیمات، پیش‌فرض روشن)', tara_home_section_on( 'home_sec_video' ) );
}
unset( $GLOBALS['__options']['option_tree']['home_sec_video'] );

/* ======================================================================
   ۵) جست‌وجو: nonce اجباری + سقف طول
   ====================================================================== */
$crit = f( 'inc/tara-critical.php' );
t(
	'جست‌وجو: nonce با پاسخ ۴۰۳ اجباری شد',
	false !== strpos( $crit, "if ( ! check_ajax_referer( 'tara_search', 'nonce', false ) ) {" )
		&& false !== strpos( $crit, "wp_send_json_error( array( 'message' => 'نشست شما منقضی شده است؛ صفحه را دوباره بارگذاری کنید.' ), 403 );" )
);
t( 'جست‌وجو: پیام خطای نشست', false !== strpos( $crit, 'نشست شما منقضی شده' ) && false !== strpos( $crit, '403' ) );
t( 'جست‌وجو: سقف طول ۸۰ نویسه', false !== strpos( $crit, 'mb_substr( $term, 0, 80' ) );
t( 'جست‌وجو: برچسب نوع اثر در پاسخ', false !== strpos( $crit, "'type'   => \$type" ) );
t( 'جست‌وجو: شمارش کل نتایج', false !== strpos( $crit, "'count' => (int) \$query->found_posts" ) );
t( 'جست‌وجو: حالت خطا در کلاینت', false !== strpos( $crit, 'function renderError' ) && false !== strpos( $crit, 'tara-sr__retry' ) );

/* ======================================================================
   ۶) هدر، سئو و لینک کوتاه
   ====================================================================== */
$head = f( 'header.php' );
t( 'هدر: H1 صفحهٔ اصلی برای is_front_page هم چاپ می‌شود', false !== strpos( $head, '( is_home() || is_front_page() ) && ! is_paged()' ) );
$seo = f( 'inc/ab-seo.php' );
t( 'سئو: noindex نتایج جست‌وجو با فیلتر بازگشت', false !== strpos( $seo, "is_search() && apply_filters( 'tara_seo_noindex_search', true )" ) );
t( 'سئو: follow باز می‌ماند', false !== strpos( $seo, "\$robots['noindex'] = true;" ) && false !== strpos( $seo, "tara_seo_noindex_search" ) );
$single = f( 'single.php' );
t( 'صفحهٔ آهنگ: لینک کوتاه escape شد', false !== strpos( $single, "absint( get_the_ID() )" ) && false === strpos( $single, "bloginfo('url'); ?>/?p=" ) );
t( 'صفحهٔ آهنگ: تاریخ با تابع صادقانه', false !== strpos( $single, 'tara_track_date_html( get_the_ID()' ) );
t( 'صفحهٔ آهنگ: اسکیما از تاریخ واقعی', false !== strpos( $single, '$taranehyar_date_iso' ) );
t( 'کارت مشترک: ۳.۱۶ به songs_post اضافه شد', false !== strpos( f( 'inc/songs_post.php' ), 'function tara_post_card' ) );
t( 'خانه: بخش‌های APEX صدا زده می‌شوند', false !== strpos( f( 'home.php' ), 'tara_home_apex_sections()' ) );
t( 'هیرو: fallback تازه‌ترین‌ها', false !== strpos( f( 'inc/vip_slider.php' ), 'تازه‌ترین آثار' ) );
t( 'منو: زیرمنو با focus-within باز می‌شود', false !== strpos( f( 'style.css' ), 'menu-item-has-children:focus-within > ul.sub-menu' ) );
t( 'تنظیمات: کلید بخش‌های خانه', false !== strpos( f( 'inc/theme-options.php' ), "'id'=>'home_sec_video'" ) );

echo "\nRESULT pass=$PASS fail=$FAIL\n";
if ( $FAILS ) {
	echo "FAILURES:\n - " . implode( "\n - ", $FAILS ) . "\n";
}
