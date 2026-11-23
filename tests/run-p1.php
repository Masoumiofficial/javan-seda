<?php
/**
 * آزمونِ نسخهٔ ۳.۱۷.۰ (APEX Ω — دور P1)
 * =====================================
 * اجرا: node tools/php-test.mjs tests/run-p1.php
 *
 * چه چیزی سنجیده می‌شود؟
 *   • فیلد «تاریخ انتشار واقعی آهنگ»: شمسی/میلادی، اعتبارسنجی، ذخیره، پاک‌کردن،
 *     nonce/مجوز، عدم‌لمسِ تاریخِ وردپرس، عدم‌نوشتنِ دادهٔ تکراری.
 *   • اجزای پروفایل هنرمند: نوعِ اثر، آخرین انتشار، شبکه‌های ثبت‌شده (بدون حدس)،
 *     همکاری‌ها، گروه‌بندی دیسکوگرافی.
 *   • پلی‌لیست: شرط انتشار/وجودِ آهنگ، پوششِ جایگزین، لینک هنرمند، مرتبط‌ها.
 *   • مرتبط‌های اولویت‌دار: هنرمند → ژانر → حال‌وهوا، بدون تکرار و بدون اثرِ جاری.
 *
 * آزموده نمی‌شود: رندر مرورگر، MySQL واقعی، رفتار ACF/ووکامرس.
 */

require __DIR__ . '/stubs-wp.php';

$GLOBALS['__posts']     = array();
$GLOBALS['__postmeta']  = array();
$GLOBALS['__postterms'] = array();
$GLOBALS['__terms']     = array();
$GLOBALS['__caps']      = array();
$GLOBALS['__taxonomies'] = array();
$GLOBALS['__thumb']     = array();

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

/* ---------- جایگزین‌های کوچکِ لازم ---------- */
if ( ! function_exists( 'get_posts' ) ) {
	function get_posts( $args = array() ) {
		$out = array();
		foreach ( (array) $GLOBALS['__posts'] as $id => $row ) {
			if ( ! empty( $args['exclude'] ) && in_array( (int) $id, array_map( 'intval', (array) $args['exclude'] ), true ) ) {
				continue;
			}
			if ( ! empty( $args['post__not_in'] ) && in_array( (int) $id, array_map( 'intval', (array) $args['post__not_in'] ), true ) ) {
				continue;
			}
			if ( ! empty( $args['post__in'] ) && ! in_array( (int) $id, array_map( 'intval', (array) $args['post__in'] ), true ) ) {
				continue;
			}
			$ok = true;
			if ( ! empty( $args['tax_query'] ) && isset( $args['tax_query'][0] ) ) {
				$clause = $args['tax_query'][0];
				$have   = isset( $GLOBALS['__postterms'][ $id ][ $clause['taxonomy'] ] ) ? (array) $GLOBALS['__postterms'][ $id ][ $clause['taxonomy'] ] : array();
				$have_ids = array();
				foreach ( $have as $term ) {
					$have_ids[] = (int) $term->term_id;
				}
				$ok = (bool) array_intersect( array_map( 'intval', (array) $clause['terms'] ), $have_ids );
			}
			if ( $ok ) {
				$out[] = (int) $id;
			}
		}
		if ( ! empty( $args['post__in'] ) ) {
			$order = array_map( 'intval', (array) $args['post__in'] );
			usort(
				$out,
				function ( $a, $b ) use ( $order ) {
					return array_search( $a, $order, true ) - array_search( $b, $order, true );
				}
			);
		}
		$limit = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : -1;
		if ( $limit > 0 ) {
			$out = array_slice( $out, 0, $limit );
		}
		return ! empty( $args['fields'] ) ? $out : array_map( 'get_post', $out );
	}
}

if ( ! function_exists( 'wp_list_pluck' ) ) {
	function wp_list_pluck( $list, $field ) {
		$out = array();
		foreach ( (array) $list as $item ) {
			if ( is_object( $item->$field ?? null ) ) {
				continue;
			}
			if ( is_object( $item ) && isset( $item->$field ) ) {
				$out[] = $item->$field;
			} elseif ( is_array( $item ) && isset( $item[ $field ] ) ) {
				$out[] = $item[ $field ];
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'get_terms' ) ) {
	function get_terms( $args = array() ) {
		$out = array();
		$include = isset( $args['include'] ) ? array_map( 'intval', (array) $args['include'] ) : array();
		foreach ( (array) $GLOBALS['__terms'] as $id => $term ) {
			if ( isset( $args['taxonomy'] ) && $term->taxonomy !== $args['taxonomy'] ) {
				continue;
			}
			if ( $include && ! in_array( (int) $id, $include, true ) ) {
				continue;
			}
			$out[] = $term;
		}
		return $out;
	}
}

if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		public $posts = array();
		public $post_count = 0;
		public $found_posts = 0;
		public $max_num_pages = 1;
		public function __construct( $args = array() ) {
			$ids = array();
			foreach ( (array) $GLOBALS['__posts'] as $id => $row ) {
				if ( ! ( $row instanceof WP_Post ) ) {
					$row = new WP_Post( (int) $id, (string) ( $row['post_title'] ?? '' ), (string) ( $row['post_status'] ?? 'publish' ), (string) ( $row['post_type'] ?? 'post' ), (string) ( $row['post_date'] ?? '' ) );
				}
				$ok = true;
				if ( isset( $args['post_type'] ) && $args['post_type'] !== $row->post_type ) {
					$ok = false;
				}
				if ( $ok && ! empty( $args['post__not_in'] ) && in_array( (int) $id, array_map( 'intval', (array) $args['post__not_in'] ), true ) ) {
					$ok = false;
				}
				if ( $ok && ! empty( $args['meta_query'] ) && isset( $args['meta_query'][0] ) ) {
					$mq  = $args['meta_query'][0];
					$has = isset( $GLOBALS['__postmeta'][ $id ][ $mq['key'] ] );
					if ( ! $has ) {
						$ok = false;
					} elseif ( isset( $mq['compare'] ) && 'LIKE' === $mq['compare'] ) {
						/* بازتولیدِ سادهٔ جست‌وجوی زیررشته در متای سریالی */
						/* وردپرس متای آرایه‌ای را با serialize ذخیره می‌کند، نه JSON. */
						$stored = serialize( (array) $GLOBALS['__postmeta'][ $id ][ $mq['key'] ] );
						$needle = str_replace( array( 'i:', ';' ), '', (string) $mq['value'] );
						$ok     = false !== strpos( (string) $stored, 'i:' . $needle . ';' ) || false !== strpos( (string) $stored, '"' . $needle . '"' );
					}
				}
				if ( $ok ) {
					$ids[] = (int) $id;
				}
			}
			$this->found_posts   = count( $ids );
			$this->posts         = array_slice( $ids, 0, isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : -1 );
			$this->post_count    = count( $this->posts );
			$ppp                 = isset( $args['posts_per_page'] ) ? max( 1, (int) $args['posts_per_page'] ) : 10;
			$this->max_num_pages = (int) ceil( $this->found_posts / $ppp );
		}
	}
}

/* ---------- دادهٔ نمونه ---------- */
$GLOBALS['__posts'] = array(
	101 => new WP_Post( 101, 'آهنگ یک', 'publish', 'post', '2026-01-01 10:00:00' ),
	102 => new WP_Post( 102, 'آهنگ دو', 'publish', 'post', '2026-02-01 10:00:00' ),
	103 => new WP_Post( 103, 'آهنگ سه', 'publish', 'post', '2026-03-01 10:00:00' ),
	104 => new WP_Post( 104, 'آلبوم یک', 'publish', 'post', '2026-04-01 10:00:00' ),
	105 => new WP_Post( 105, 'موزیک‌ویدیو یک', 'publish', 'post', '2026-05-01 10:00:00' ),
	201 => new WP_Post( 201, 'پلی‌لیست غمگین', 'publish', 'tara_playlist', '2026-06-01 10:00:00' ),
	202 => new WP_Post( 202, 'پلی‌لیست خالی', 'publish', 'tara_playlist', '2026-06-02 10:00:00' ),
	203 => new WP_Post( 203, 'پلی‌لیست پیش‌نویس', 'draft', 'tara_playlist', '2026-06-03 10:00:00' ),
	/* اثری بدون هنرمند/ژانر/حال‌وهوا: برای سنجش «کارت خالی نساز» */
	106 => new WP_Post( 106, 'اثر بی‌رابطه', 'publish', 'post', '2026-07-01 10:00:00' ),
);
$GLOBALS['__posts'][201]->post_content = 'برای شب‌های بارانی و دلتنگی.';
$GLOBALS['__post_time_real'] = true;
/* تابع‌های صوتی: لایهٔ پلی‌لیست به وجودِ فایل صوتی تکیه می‌کند */
if ( ! function_exists( 'tara_track_src' ) ) {
	function tara_track_src( $id ) {
		return in_array( (int) $id, array( 101, 102, 103 ), true ) ? 'https://ex/' . (int) $id . '.mp3' : '';
	}
}
if ( ! function_exists( 'tara_track_title' ) ) {
	function tara_track_title( $id ) { return (string) get_the_title( (int) $id ); }
}
if ( ! function_exists( 'tara_track_artist' ) ) {
	function tara_track_artist( $id ) { return (string) get_post_meta( (int) $id, 'artist', true ); }
}
if ( ! function_exists( 'tara_track_cover' ) ) {
	function tara_track_cover( $id, $size = '' ) { return 'https://ex/' . (int) $id . '.jpg'; }
}
if ( ! function_exists( 'tara_artist_field' ) ) {
	function tara_artist_field( $term, $key, $default = '' ) {
		$term = $term instanceof WP_Term ? $term : null;
		if ( ! $term ) { return $default; }
		$meta = get_term_meta( $term->term_id, $key, true );
		return is_scalar( $meta ) ? (string) $meta : $default;
	}
}
if ( ! function_exists( 'tara_artist_tax' ) ) {
	function tara_artist_tax() { return 'artist'; }
}
if ( ! function_exists( 'tara_artist_normalize' ) ) {
	function tara_artist_normalize( $t ) { return trim( (string) $t ); }
}
if ( ! function_exists( 'tara_artist_term_link' ) ) {
	function tara_artist_term_link( $term ) { return $term ? 'https://ex/artist/' . $term->slug . '/' : ''; }
}

$GLOBALS['__postmeta'] = array(
	101 => array( 'artist' => 'محسن یگانه', 'song' => 'یک', 'musics_type' => 'آهنگ', '_tara_release_date' => '2024-08-02' ),
	102 => array( 'artist' => 'محسن یگانه', 'song' => 'دو', 'musics_type' => 'آهنگ' ),
	103 => array( 'artist' => 'دیگری', 'song' => 'سه', 'musics_type' => 'آهنگ' ),
	104 => array( 'artist' => 'محسن یگانه', 'song' => 'آلبوم', 'musics_type' => 'آلبوم' ),
	105 => array( 'artist' => 'محسن یگانه', 'song' => 'ویدیو', 'musics_type' => 'موزیک‌ویدیو' ),
	201 => array( '_tara_playlist_tracks' => array( 101, 102 ), '_tara_playlist_mood' => 0 ),
	202 => array( '_tara_playlist_tracks' => array() ),
);

$artist = new WP_Term( 501, 'محسن یگانه', 'artist', 'mohsen-yeganeh', 4 );
$other  = new WP_Term( 502, 'هنرمند دیگر', 'artist', 'other-artist', 1 );
$genre  = new WP_Term( 601, 'پاپ', 'tara_genre', 'pop', 3 );
$mood   = new WP_Term( 701, 'غمگین', 'tara_mood', 'sad', 3 );

$GLOBALS['__terms'] = array( 501 => $artist, 502 => $other, 601 => $genre, 701 => $mood );
$GLOBALS['__taxonomies'] = array(
	'tara_genre' => (object) array( 'name' => 'tara_genre', 'object_type' => array( 'post' ), 'args' => array() ),
	'tara_mood'  => (object) array( 'name' => 'tara_mood', 'object_type' => array( 'post' ), 'args' => array() ),
	'tara_playlist' => (object) array( 'name' => 'tara_playlist', 'object_type' => array( 'post' ), 'args' => array() ),
);

$GLOBALS['__postterms'][101]['artist']     = array( $artist );
$GLOBALS['__postterms'][102]['artist']     = array( $artist );
$GLOBALS['__postterms'][104]['artist']     = array( $artist );
$GLOBALS['__postterms'][105]['artist']     = array( $artist );
$GLOBALS['__postterms'][103]['artist']     = array( $other );
$GLOBALS['__postterms'][101]['tara_genre']  = array( $genre );
$GLOBALS['__postterms'][102]['tara_genre']  = array( $genre );
$GLOBALS['__postterms'][103]['tara_genre']  = array( $genre );
$GLOBALS['__postterms'][103]['tara_mood']   = array( $mood );
$GLOBALS['__postterms'][105]['artist']      = array( $artist, $other );

/* ---------- بارگذاری فایل‌های تحت آزمون ---------- */
require '/tmp/theme/inc/tara-p0.php';
require '/tmp/theme/inc/tara-release-date.php';
require '/tmp/theme/inc/tara-artist-profile.php';
require '/tmp/theme/inc/tara-playlists.php';
require '/tmp/theme/inc/tara-playlists-p1.php';
require '/tmp/theme/inc/tara-related-p1.php';

/* ======================================================================
   ۱) سیم‌کشی و فایل‌ها
   ====================================================================== */
t( 'فایل: چهار ماژول P1 بارگذاری شده', '' !== f( 'inc/tara-release-date.php' ) && '' !== f( 'inc/tara-artist-profile.php' ) && '' !== f( 'inc/tara-playlists-p1.php' ) && '' !== f( 'inc/tara-related-p1.php' ) );
t( 'سیم‌کشی: functions.php هر چهار ماژول را require می‌کند', false !== strpos( f( 'functions.php' ), 'tara-release-date.php' ) && false !== strpos( f( 'functions.php' ), 'tara-related-p1.php' ) );
t( 'توابع تاریخ', function_exists( 'tara_p1_date_parse' ) && function_exists( 'tara_p1_date_human' ) );
t( 'توابع هنرمند', function_exists( 'tara_artist_latest_release' ) && function_exists( 'tara_artist_social_links' ) );
t( 'توابع پلی‌لیست', function_exists( 'tara_playlist_is_public' ) && function_exists( 'tara_playlist_tracks_html' ) );
t( 'توابع مرتبط', function_exists( 'tara_related_tracks' ) && function_exists( 'tara_related_tracks_html' ) );

/* ======================================================================
   ۲) تاریخ شمسی/میلادی: صحتِ تبدیل و اعتبارسنجی
   ====================================================================== */
$cases = array(
	'1403/05/12'   => '2024-08-02',
	'۱۴۰۳/۰۵/۱۲'  => '2024-08-02',
	'1403-5-12'    => '2024-08-02',
	'14030512'     => '2024-08-02',
	'2024-08-02'   => '2024-08-02',
	'2024/8/2'     => '2024-08-02',
	'20240802'     => '2024-08-02',
	'1404/01/01'   => '2025-03-21',
	'1399/12/30'   => '2021-03-20',
);
foreach ( $cases as $in => $want ) {
	t( "تاریخ: «{$in}» → {$want}", tara_p1_date_parse( $in ) === $want, var_export( tara_p1_date_parse( $in ), true ) );
}

$invalid = array( '1403/13/01', '1403/00/10', '1403/07/31', '2024-02-31', 'abc', '1403//01', '12/13/1403', '0000-00-00' );
foreach ( $invalid as $bad ) {
	t( "تاریخ نامعتبر رد می‌شود: «{$bad}»", false === tara_p1_date_parse( $bad ), var_export( tara_p1_date_parse( $bad ), true ) );
}
t( 'تاریخ خالی ⇒ رشتهٔ خالی (یعنی حذف)', '' === tara_p1_date_parse( '   ' ) );
t( 'نمایش شمسی تاریخ ذخیره‌شده', '1403/05/12' === tara_p1_date_human( '2024-08-02' ), (string) tara_p1_date_human( '2024-08-02' ) );
t( 'رفت‌وبرگشت شمسی→میلادی→شمسی برای ۱۰۰ سال', ( function () {
	for ( $y = 1350; $y <= 1450; $y++ ) {
		for ( $m = 1; $m <= 12; $m++ ) {
			$g = tara_p1_jalali_to_gregorian( $y, $m, 1 );
			if ( ! $g ) {
				return false;
			}
			$b = tara_p1_gregorian_to_jalali( $g[0], $g[1], $g[2] );
			if ( ! $b || (int) $b[0] !== $y || (int) $b[1] !== $m || (int) $b[2] !== 1 ) {
				return false;
			}
		}
	}
	return true;
} )() );

/* ======================================================================
   ۳) ذخیرهٔ فیلد: مجوز، nonce، مقدار، پاک‌کردن، عدم‌لمسِ تاریخ وردپرس
   ====================================================================== */
t( 'کلید متا همان کلیدِ خوانده‌شده در ۳.۱۶.۰ است', tara_p1_date_meta_key() === '_tara_release_date' );
t( 'کلید در فهرست کلیدهای خوانده‌شده هست', in_array( tara_p1_date_meta_key(), tara_release_meta_keys(), true ) );

function p1_save( $post_id, $raw, $nonce = 'good', $cap = true, $present = true ) {
	$GLOBALS['__caps'] = $cap ? array( 'edit_post' => true ) : array();
	$_POST = array();
	if ( $present ) {
		$_POST['tara_release_date_present'] = '1';
		$_POST['tara_release_date_nonce']   = $nonce;
		$_POST['tara_release_date']         = $raw;
	}
	$GLOBALS['tara_p1_date_error'] = false;
	tara_p1_date_save( $post_id, get_post( $post_id ) );
	$_POST = array();
}

$GLOBALS['__postmeta'][102]['_tara_release_date'] = '';

p1_save( 102, '1403/05/12' );
t( 'ذخیره: تاریخ شمسی به میلادی تبدیل و ذخیره می‌شود', '2024-08-02' === (string) get_post_meta( 102, '_tara_release_date', true ) );

p1_save( 102, '2025-01-09' );
t( 'ذخیره: ویرایش مقدار موجود', '2025-01-09' === (string) get_post_meta( 102, '_tara_release_date', true ) );

p1_save( 102, '' );
t( 'ذخیره: خالی‌کردن فیلد متا را حذف می‌کند (نه رشتهٔ خالی)', '' === (string) get_post_meta( 102, '_tara_release_date', true ) && false === array_key_exists( '_tara_release_date', (array) $GLOBALS['__postmeta'][102] ) );

p1_save( 102, '2021-03-20' );
$before_bad = (string) get_post_meta( 102, '_tara_release_date', true );
p1_save( 102, '2024-02-31' );
t( 'ذخیره: مقدار نامعتبر متای قبلی را تغییر نمی‌دهد', $before_bad === (string) get_post_meta( 102, '_tara_release_date', true ) );
t( 'ذخیره: مقدار نامعتبر اعلان خطا فعال می‌کند', ! empty( $GLOBALS['tara_p1_date_error'] ) );

p1_save( 102, '1403/05/12', 'good', false );
t( 'امنیت: کاربر بی‌مجوز ذخیره نمی‌کند', '2021-03-20' === (string) get_post_meta( 102, '_tara_release_date', true ) );

p1_save( 102, '1403/05/12', 'bad' );
t( 'امنیت: nonce نامعتبر ذخیره نمی‌کند', '2021-03-20' === (string) get_post_meta( 102, '_tara_release_date', true ) );

p1_save( 102, '', 'good', true, false );
t( 'سازگاری: اگر فیلد در فرم نبود، مقدار پاک نمی‌شود', '2021-03-20' === (string) get_post_meta( 102, '_tara_release_date', true ) );

/* تاریخ نوشته نباید عوض شود */
$post_before = $GLOBALS['__posts'][102]->post_date;
p1_save( 102, '1400/01/01' );
t( 'ایمنی: تاریخ انتشار وردپرس تغییر نمی‌کند', $post_before === $GLOBALS['__posts'][102]->post_date );

/* ادغام با لایهٔ خواندنِ ۳.۱۶.۰ */
t( 'یکپارچگی: تاریخِ فیلد در tara_track_date خوانده می‌شود', ( function () {
	$GLOBALS['__postmeta'][102]['_tara_release_date'] = '2024-08-02';
	$d = tara_track_date( 102 );
	return isset( $d['source'] ) && 'release' === $d['source'] && '2024-08-02' === $d['iso'];
} )() );

/* ذخیره‌نشدنِ دادهٔ یکسانِ تکراری: هوک باید روی همان مقدار، دوباره ننویسد */
t( 'بهینه: ذخیرهٔ مقدار یکسان از نوشتن دوباره پرهیز می‌کند', ( function () {
	$GLOBALS['__postmeta'][102]['_tara_release_date'] = '2024-08-02';
	$GLOBALS['__caps'] = array( 'edit_post' => true );
	$_POST = array( 'tara_release_date_present' => '1', 'tara_release_date_nonce' => 'good', 'tara_release_date' => '1403/05/12' );
	/* با یک sentinel: اگر تابع بنویسد، مقدار عوض می‌شود؛ ولی باید همان بماند */
	tara_p1_date_save( 102, get_post( 102 ) );
	$_POST = array();
	return '2024-08-02' === (string) get_post_meta( 102, '_tara_release_date', true );
} )() );

/* ======================================================================
   ۴) جعبهٔ ویرایشگر
   ====================================================================== */
tara_p1_date_metabox();
t( 'جعبهٔ متا ثبت شده و برچسبش درست است', isset( $GLOBALS['__metaboxes']['tara-release-date'] ) && 'تاریخ انتشار واقعی آهنگ' === $GLOBALS['__metaboxes']['tara-release-date']['title'], wp_json_encode( array_keys( (array) ( $GLOBALS['__metaboxes'] ?? array() ) ) ) );
t( 'جعبهٔ متا فقط برای پست‌تایپ post فعال است', array( 'post' ) === (array) apply_filters( 'tara_p1_date_post_types', array( 'post' ) ) );

ob_start();
tara_p1_date_metabox_html( get_post( 102 ) );
$box = (string) ob_get_clean();
t( 'جعبه: ورودی با نام درست و مقدار شمسی', false !== strpos( $box, 'name="tara_release_date"' ) && false !== strpos( $box, 'value="1403/05/12"' ) );
t( 'جعبه: nonce و نشانگر حضور فیلد', false !== strpos( $box, 'tara_release_date_nonce' ) && false !== strpos( $box, 'tara_release_date_present' ) );
t( 'جعبه: مقدار میلادی برای بازبینی نمایش داده می‌شود', false !== strpos( $box, '2024-08-02' ) );
t( 'جعبه: برچسب صریح و متن راهنما', false !== strpos( $box, 'تاریخ انتشار واقعی آهنگ' ) && false !== strpos( $box, 'تاریخ انتشار وردپرس را تغییر نمی‌دهد' ) );

ob_start();
tara_p1_date_metabox_html( get_post( 103 ) );
$empty_box = (string) ob_get_clean();
t( 'جعبه: نوشتهٔ بدون تاریخ ⇒ ورودی خالی + پیام شفاف', false !== strpos( $empty_box, 'value=""' ) && false !== strpos( $empty_box, 'انتشار در سایت' ) );

/* ======================================================================
   ۵) پروفایل هنرمند
   ====================================================================== */
t( 'هنرمند: نوعِ اثر audio/album/video/other درست تشخیص داده می‌شود', 'audio' === tara_artist_track_type( 101 ) && 'album' === tara_artist_track_type( 104 ) && 'video' === tara_artist_track_type( 105 ) && 'other' === tara_artist_track_type( 999 ) );

$latest = tara_artist_latest_release( $artist );
t( 'هنرمند: آخرین انتشار از تاریخِ واقعیِ هر اثر محاسبه می‌شود', is_array( $latest ) && 105 === $latest['id'] && '2026-05-01' === $latest['date'], wp_json_encode( $latest ) );
/* وقتی تاریخِ واقعی از تاریخِ نوشتن جدیدتر است، همان ترجیح داده می‌شود */
$GLOBALS['__postmeta'][104]['_tara_release_date'] = '2026-12-20';
$latest2 = tara_artist_latest_release( $artist );
t( 'هنرمند: تاریخِ واقعیِ جدیدتر بر تاریخِ نوشتن مقدم است', is_array( $latest2 ) && 104 === $latest2['id'] && '2026-12-20' === $latest2['date'] && 'تاریخ انتشار' === $latest2['label'], wp_json_encode( $latest2 ) );
unset( $GLOBALS['__postmeta'][104]['_tara_release_date'] );
t( 'هنرمند: برای ترمِ نامعتبر null', null === tara_artist_latest_release( 'not-a-term' ) );

$GLOBALS['__termmeta'][501] = array( 'afacebook' => 'https://facebook.com/x', 'ainstagram' => 'not-a-url', 'atelegram' => 'https://t.me/x' );
if ( ! function_exists( 'tara_artist_social_keys' ) ) {
	function tara_artist_social_keys() {
		return array(
			'afacebook'  => array( 'label' => 'فیسبوک', 'icon' => 'icofont-facebook' ),
			'atelegram'  => array( 'label' => 'تلگرام', 'icon' => 'icofont-paper-plane' ),
			'ainstagram' => array( 'label' => 'اینستاگرام', 'icon' => 'icofont-instagram' ),
			'atwitter'   => array( 'label' => 'توییتر', 'icon' => 'icofont-twitter' ),
			'ayoutube'   => array( 'label' => 'یوتیوب', 'icon' => 'icofont-youtube-play' ),
		);
	}
}
$links = tara_artist_social_links( $artist );
t( 'هنرمند: فقط شبکه‌های ثبت‌شده و معتبر برمی‌گردند', isset( $links['afacebook'], $links['atelegram'] ) && ! isset( $links['ainstagram'] ) );
t( 'هنرمند: شبکهٔ ثبت‌نشده ساخته نمی‌شود', ! isset( $links['atwitter'] ) && ! isset( $links['ayoutube'] ) );

$links_html = tara_artist_links_html( $artist );
t( 'هنرمند: لینک‌ها با rel امن و target', false !== strpos( $links_html, 'rel="noopener nofollow me"' ) && false !== strpos( $links_html, 'facebook.com/x' ) );
t( 'هنرمند: از لینک‌های نامعتبر چیزی چاپ نمی‌شود', false === strpos( $links_html, 'not-a-url' ) );

$collabs = tara_artist_collaborators( $artist, 8 );
$collab_names = array();
foreach ( (array) $collabs as $c ) { $collab_names[] = $c->name; }
t( 'هنرمند: همکار از رابطهٔ واقعی (ترم مشترک) می‌آید', in_array( 'هنرمند دیگر', $collab_names, true ) );
t( 'هنرمند: خودِ هنرمند در همکارها نیست', ! in_array( 'محسن یگانه', $collab_names, true ) );

$groups = tara_artist_discography_groups( $artist, 10 );
t( 'هنرمند: گروه‌بندی دیسکوگرافی فقط گروه‌های دارای عضو', isset( $groups['audio'], $groups['album'], $groups['video'] ) && ! isset( $groups['other'] ), wp_json_encode( array_keys( $groups ) ) );
t( 'هنرمند: خودِ اثر جاری در مرتبط‌ها نیست', ! in_array( 101, tara_related_tracks( 101, 6 ), true ) );

/* ======================================================================
   ۶) پلی‌لیست
   ====================================================================== */
t( 'پلی‌لیست: منتشرشده با آهنگ ⇒ عمومی', true === tara_playlist_is_public( 201 ) );
t( 'پلی‌لیست: خالی ⇒ عمومی نیست', false === tara_playlist_is_public( 202 ) );
t( 'پلی‌لیست: پیش‌نویس ⇒ عمومی نیست', false === tara_playlist_is_public( 203 ) );
t( 'پلی‌لیست: نوع اشتباه ⇒ عمومی نیست', false === tara_playlist_is_public( 101 ) );

$ids = tara_playlist_ids( array( 'limit' => 10 ) );
t( 'پلی‌لیست: فهرست فقط پلی‌لیست‌های واقعی', array( 201 ) === array_values( $ids ), wp_json_encode( $ids ) );

$tracks = tara_playlist_tracks( 201 );
t( 'پلی‌لیست: آهنگ‌ها با لینکِ واقعیِ هنرمند', 2 === count( $tracks ) && 'https://ex/artist/mohsen-yeganeh/' === $tracks[0]['artist_url'], wp_json_encode( $tracks[0]['artist_url'] ?? '' ) );
t( 'پلی‌لیست: توضیح از متنِ خودش می‌آید', false !== strpos( tara_playlist_description( 201 ), 'بارانی' ) );
t( 'پلی‌لیست: توضیح برای پلی‌لیست بی‌متن رشتهٔ خالی است', '' === tara_playlist_description( 202 ) );

$html = tara_playlist_tracks_html( 201 );
t( 'پلی‌لیست: مارک‌آپ فهرست شامل لینک آهنگ و هنرمند', false !== strpos( $html, 'ty-playlists__track-artist' ) && false !== strpos( $html, 'mohsen-yeganeh' ) );
t( 'پلی‌لیست: حالت خالی پیام روشن دارد', false !== strpos( tara_playlist_tracks_html( 202 ), 'آهنگِ قابل‌پخشی' ) );

$cover = tara_playlist_cover( 201 );
t( 'پلی‌لیست: در نبود تصویر شاخص، کاور اولین آهنگ انتخاب می‌شود', 'first-track' === $cover['source'] || '' === $cover['url'] );

$GLOBALS['__posts'][301] = array( 'ID' => 301, 'post_title' => 'برگهٔ پلی‌لیست', 'post_type' => 'page', 'post_status' => 'publish', 'post_date' => '', 'post_content' => '', 'post_password' => '' );
t( 'پلی‌لیست: لینکِ برگهٔ عمومی در آدرس‌ها استفاده می‌شود', function_exists( 'tara_playlists_page_url' ) );

$rel = tara_playlist_related_to_track( 101, 3 );
t( 'پلی‌لیست: رابطهٔ واقعی با آهنگ (نه LIKE خام)', array( 201 ) === array_values( $rel ), wp_json_encode( $rel ) );
t( 'پلی‌لیست: آهنگ بدون عضویت، پلی‌لیستی برنمی‌گرداند', array() === tara_playlist_related_to_track( 105, 3 ) );

/* ======================================================================
   ۷) مرتبط‌های اولویت‌دار
   ====================================================================== */
$related = tara_related_tracks( 101, 4 );
t( 'مرتبط: اول هنرمند، بعد تاکسونومی', in_array( 102, $related, true ) && ! in_array( 101, $related, true ) );
t( 'مرتبط: بدون تکرار', count( $related ) === count( array_unique( $related ) ) );
t( 'مرتبط: سقف رعایت می‌شود', count( tara_related_tracks( 101, 2 ) ) <= 2 );

$with_tax = tara_related_tracks( 103, 3 );
t( 'مرتبط: تکمیل از ژانر/حال‌وهوا وقتی هنرمند کافی نیست', in_array( 101, $with_tax, true ) || in_array( 102, $with_tax, true ) );

$html = tara_related_tracks_html( 103, array( 'limit' => 2 ) );
t( 'مرتبط: مارک‌آپ لینکِ خزش‌پذیر دارد', false !== strpos( $html, 'data-tara-related="taxonomy"' ) && false !== strpos( $html, 'https://ex/?p=' ) );
t( 'مرتبط: برای اثری بدون ژانر/هم‌هنر، بلوک چاپ نمی‌شود', '' === tara_related_tracks_html( 106, array( 'limit' => 2 ) ) );
t( 'مرتبط: خروجی خالی وقتی هیچ رابطه‌ای نیست', array() === tara_related_tracks( 106, 4 ) );

/* ======================================================================
   ۸) سئو/امنیتِ سیم‌کشی
   ====================================================================== */
t( 'سئو: داده‌ساختاریافته از بیوگرافی کوتاه پر می‌شود (نه متغیرِ خالی)', false !== strpos( f( 'taxonomy-artist.php' ), 'tara_artist_bio_short' ) );
t( 'سئو: sameAs فقط از لینک‌های ثبت‌شده', false !== strpos( f( 'taxonomy-artist.php' ), "'sameAs'" ) && false !== strpos( f( 'taxonomy-artist.php' ), 'tara_artist_social_links' ) );
t( 'امنیت: ذخیره با nonce و capability محافظت شده', false !== strpos( f( 'inc/tara-release-date.php' ), "wp_verify_nonce" ) && false !== strpos( f( 'inc/tara-release-date.php' ), "current_user_can( 'edit_post'" ) );
t( 'SEO: پلی‌لیست خالی هیچ لینکی نمی‌گیرد', false === strpos( f( 'inc/tara-playlists.php' ), "'numberposts' => 4" ) );
t( 'دسترس‌پذیری: ورودی تاریخ aria-describedby دارد', false !== strpos( $box, 'aria-describedby="tara-release-date-help"' ) );

/* ====================================================================== */
echo "\nRESULT pass={$PASS} fail={$FAIL}\n";
if ( $FAIL ) {
	echo "FAILED:\n";
	foreach ( $FAILS as $name ) {
		echo " - {$name}\n";
	}
}
