<?php
/**
 * ترانه‌یار — کاوشگرِ ریزِ دو بخشِ کند (v3.17.9)
 *
 * دادهٔ واقعی از سایت ahangban.ir:
 *
 *   apex         79,516.5ms   32 کوئری   ⚠⚠⚠
 *   vip_slider   23,511.1ms   55 کوئری   ⚠⚠⚠
 *   hty             520.0ms  248 کوئری   ⚠⚠
 *   mostviews       186.6ms  126 کوئری   ⚠
 *
 * نکتهٔ مهم: mostviews با ۱۲۶ کوئری در ۱۸۶ms تمام شد، ولی apex با
 * ۳۲ کوئری ۷۹ ثانیه گرفت. یعنی کندی از **تعداد کوئری** نیست؛ از
 * چیزی است که در PHP رخ می‌دهد.
 *
 * این فایل آن دو بخش را به کوچک‌ترین واحدها می‌شکند.
 *
 *   ?key=taranehyar&target=apex     (پیش‌فرض)
 *   ?key=taranehyar&target=vip
 *   ?key=taranehyar&target=hty
 *   ?key=taranehyar&target=db     ← ایندکس‌ها و EXPLAIN (اثباتِ قطعی)
 *
 * با **مرورگر** باز کنید. فایل را پس از تشخیص پاک کنید.
 */

$SECRET = 'taranehyar';
if ( ! isset( $_GET['key'] ) || $SECRET !== (string) $_GET['key'] ) {
	header( 'HTTP/1.1 404 Not Found' );
	echo 'Not found.';
	exit;
}

@ini_set( 'output_buffering', '0' );
@ini_set( 'zlib.output_compression', '0' );
@ini_set( 'implicit_flush', '1' );
@ini_set( 'max_execution_time', '600' );
@ini_set( 'memory_limit', '512M' );
@set_time_limit( 600 );
while ( ob_get_level() > 0 ) {
	@ob_end_flush();
}
ob_implicit_flush( true );

header( 'Content-Type: text/plain; charset=utf-8' );
header( 'X-Robots-Tag: noindex, nofollow' );
header( 'Cache-Control: no-store' );
header( 'X-Accel-Buffering: no' );

$T0     = microtime( true );
$LAST   = '(شروع)';
$TARGET = isset( $_GET['target'] ) ? (string) $_GET['target'] : 'apex';

function say( $m ) {
	global $T0, $LAST;
	$LAST = $m;
	echo '[' . str_pad( round( ( microtime( true ) - $T0 ) * 1000, 1 ), 10, ' ', STR_PAD_LEFT ) . 'ms] ' . $m . "\n";
	@flush();
}
function el( $t ) {
	return round( ( microtime( true ) - $t ) * 1000, 1 );
}

register_shutdown_function( function () {
	global $T0, $LAST;
	$e = error_get_last();
	echo "\n==================================================\n";
	echo 'آخرین مرحله: ' . $LAST . "\n";
	echo 'زمان کل: ' . round( ( microtime( true ) - $T0 ) * 1000, 1 ) . "ms\n";
	echo 'حافظهٔ اوج: ' . round( memory_get_peak_usage( true ) / 1048576, 1 ) . "MB\n";
	if ( $e ) {
		echo 'خطای PHP: ' . $e['message'] . "\n";
		echo '  در ' . basename( (string) $e['file'] ) . ':' . $e['line'] . "\n";
	}
	echo "==================================================\n";
} );

say( '=== کاوشگرِ ریز / target=' . $TARGET . ' ===' );
say( 'PHP ' . PHP_VERSION );

$root = dirname( __FILE__ );
define( 'WP_USE_THEMES', true );
$t = microtime( true );
require_once $root . '/wp-load.php';
say( 'بوت: ' . el( $t ) . 'ms | افزونه: ' . count( (array) get_option( 'active_plugins', array() ) ) );

global $wpdb;
say( 'پرسش تا اینجا: ' . (int) $wpdb->num_queries );
say( '' );

/* routing خانه */
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['SCRIPT_NAME']    = '/index.php';
$_SERVER['PHP_SELF']       = '/index.php';
$_SERVER['QUERY_STRING']   = '';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET                      = array();

global $wp;
$wp = new WP();
$wp->init();
$wp->parse_request();
$wp->send_headers();
$wp->query_posts();
$wp->handle_404();
$wp->register_globals();
say( 'routing: is_home=' . ( is_home() ? '1' : '0' ) . ' نتایج=' . (int) $GLOBALS['wp_query']->found_posts );
say( '' );

/**
 * یک واحد را اجرا و زمان‌گیری کن.
 */
function probe( $label, callable $fn ) {
	global $wpdb;
	$t   = microtime( true );
	$q0  = (int) $wpdb->num_queries;
	$lvl = ob_get_level();
	say( '▶ ' . $label );
	ob_start();
	try {
		$r = $fn();
	} catch ( Throwable $e ) {
		while ( ob_get_level() > $lvl ) {
			ob_end_clean();
		}
		say( '  ✗ خطا: ' . $e->getMessage() );
		return null;
	}
	$out = ob_get_clean();
	$ms  = el( $t );
	$q   = (int) $wpdb->num_queries - $q0;
	$flag = '';
	if ( $ms > 5000 ) {
		$flag = '  ⚠⚠⚠ خیلی کند';
	} elseif ( $ms > 500 ) {
		$flag = '  ⚠⚠';
	} elseif ( $ms > 100 ) {
		$flag = '  ⚠';
	}
	say( '  ✔ ' . $ms . 'ms | ' . strlen( $out ) . ' بایت | ' . $q . ' کوئری' . $flag . ( null !== $r ? ' | بازگشت: ' . substr( var_export( $r, true ), 0, 60 ) : '' ) );
	return $r;
}

/* ==================================================================
 * target=apex
 * ================================================================== */
if ( 'apex' === $TARGET ) {
	say( '--- apex: پنج زیربخش ---' );
	say( '' );

	/* ۰) تنظیماتِ روشن/خاموش */
	say( '--- تنظیماتِ روشن/خاموش ---' );
	if ( function_exists( 'tara_home_section_on' ) ) {
		foreach ( array( 'home_sec_releases', 'home_sec_video', 'home_sec_album', 'home_sec_mood', 'home_sec_news' ) as $k ) {
			$t = microtime( true );
			$on = tara_home_section_on( $k );
			say( '  ' . str_pad( $k, 20 ) . ( $on ? 'روشن' : 'خاموش' ) . ' (' . el( $t ) . 'ms)' );
		}
	} else {
		say( '  تابع tara_home_section_on نیست.' );
	}
	say( '' );

	/* ۱) انتشارها */
	say( '--- ۱) tara_latest_releases_ids ---' );
	if ( function_exists( 'tara_release_meta_keys' ) ) {
		$keys = tara_release_meta_keys();
		say( '  کلیدهای تاریخ: ' . implode( ', ', (array) $keys ) . ' (' . count( (array) $keys ) . ' کلید)' );
		/* هر کلید جدا: اینجا احتمالاً فاجعه است */
		foreach ( (array) $keys as $key ) {
			probe(
				'get_posts meta_key=' . $key,
				function () use ( $key ) {
					return get_posts(
						array(
							'post_type'           => 'post',
							'post_status'         => 'publish',
							'posts_per_page'      => 8,
							'fields'              => 'ids',
							'no_found_rows'       => true,
							'ignore_sticky_posts' => true,
							'meta_key'            => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
							'orderby'             => 'meta_value',
							'order'               => 'DESC',
						)
					);
				}
			);
		}
		/* و کلِ تابع */
		probe( 'tara_latest_releases_ids(8)', function () { return count( (array) tara_latest_releases_ids( 8 ) ); } );
		probe( 'tara_latest_releases_ids(8) — بار دوم (کش؟)', function () { return count( (array) tara_latest_releases_ids( 8 ) ); } );
	}
	say( '' );

	/* ۲) موزیک‌ویدیو */
	say( '--- ۲) tara_home_video_section ---' );
	probe( 'tara_home_query (video720/video1080)', function () {
		return count( (array) tara_home_query( array(
			'posts_per_page' => 8,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'OR',
				array( 'key' => 'video720', 'compare' => 'EXISTS' ),
				array( 'key' => 'video1080', 'compare' => 'EXISTS' ),
			),
		) ) );
	} );
	probe( 'همان بار دوم (کش؟)', function () {
		return count( (array) tara_home_query( array(
			'posts_per_page' => 8,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'OR',
				array( 'key' => 'video720', 'compare' => 'EXISTS' ),
				array( 'key' => 'video1080', 'compare' => 'EXISTS' ),
			),
		) ) );
	} );
	probe( 'tara_home_video_section()', function () { return strlen( (string) tara_home_video_section() ); } );
	say( '' );

	/* ۳) آلبوم */
	say( '--- ۳) tara_home_album_section ---' );
	probe( 'tara_home_query (musics_type IN)', function () {
		return count( (array) tara_home_query( array(
			'posts_per_page' => 8,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => 'musics_type', 'value' => array( 'آلبوم', 'album', 'Album' ), 'compare' => 'IN' ),
			),
		) ) );
	} );
	probe( 'tara_home_album_section()', function () { return strlen( (string) tara_home_album_section() ); } );
	say( '' );

	/* ۴) حال‌وهوا */
	say( '--- ۴) tara_home_mood_section ---' );
	probe( 'get_terms tara_mood', function () {
		$t = get_terms( array( 'taxonomy' => 'tara_mood', 'hide_empty' => true, 'number' => 12, 'orderby' => 'count', 'order' => 'DESC' ) );
		return is_wp_error( $t ) ? 'WP_Error' : count( $t );
	} );
	probe( 'tara_home_mood_section()', function () { return strlen( (string) tara_home_mood_section() ); } );
	say( '' );

	/* ۵) اخبار */
	say( '--- ۵) tara_home_news_section ---' );
	probe( 'tara_home_news_section()', function () { return strlen( (string) tara_home_news_section() ); } );
	say( '' );

	/* ۶) کارت‌ها — آیا tara_post_card کند است؟ */
	say( '--- ۶) tara_post_card × ۲۰ ---' );
	$ids = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 20, 'fields' => 'ids', 'no_found_rows' => true ) );
	say( '  ' . count( $ids ) . ' شناسه' );
	$GLOBALS['tara_card_play'] = true;
	probe( 'tara_post_card × ۲۰', function () use ( $ids ) {
		$n = 0;
		foreach ( $ids as $id ) {
			$n += strlen( (string) tara_post_card( $id ) );
		}
		return $n;
	} );
	$GLOBALS['tara_card_play'] = false;
	say( '' );

	/* ۷) و کلِ apex */
	probe( 'tara_home_apex_sections() — کل', function () { return strlen( (string) tara_home_apex_sections() ); } );
}

/* ==================================================================
 * target=vip
 * ================================================================== */
if ( 'vip' === $TARGET ) {
	say( '--- vip_slider: گام‌به‌گام ---' );
	say( '' );

	probe( 'ot_get_option fixbvip', function () { return var_export( function_exists( 'ot_get_option' ) ? ot_get_option( 'fixbvip' ) : 'no ot', true ); } );
	probe( 'ot_get_option hty', function () {
		$a = function_exists( 'ot_get_option' ) ? ot_get_option( 'hty', array() ) : array();
		return is_array( $a ) ? 'آرایه با ' . count( $a ) . ' عضو' : gettype( $a );
	} );
	probe( 'پروندهٔ قالب: inc/vip_slider', function () {
		get_template_part( 'inc/vip_slider' );
		return '';
	} );
	say( '' );
	say( 'اگر همین یک مورد کند بود، داخل خودِ فایل است.' );
	say( 'بزرگ‌ترین مظنون: wp_get_attachment_image_srcset روی تصاویری که' );
	say( 'فایلشان نیست — WordPress برای هر سایز دنبال پرونده می‌گردد.' );
}


/* ==================================================================
 * target=db — چرا روی این سرور کند است؟ (اثبات با EXPLAIN)
 * ================================================================== */
if ( 'db' === $TARGET ) {
	say( '--- db: جدول‌ها، ایندکس‌ها، EXPLAIN ---' );
	say( 'درایور: ' . $wpdb->db_server_info() );
	say( '' );

	$tables = array(
		$wpdb->posts      => 'نوشته‌ها',
		$wpdb->postmeta   => 'متا',
		$wpdb->options    => 'تنظیمات',
		$wpdb->term_relationships => 'ارتباط دسته',
	);
	say( '--- اندازهٔ جدول‌ها ---' );
	foreach ( $tables as $tbl => $lbl ) {
		$t   = microtime( true );
		$n   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$tbl`" ); // phpcs:ignore
		$ms  = el( $t );
		$flag = $n > 300000 ? '  ⚠ بزرگ' : '';
		say( '  ' . str_pad( $lbl, 16 ) . str_pad( number_format( $n ), 12, ' ', STR_PAD_LEFT ) . ' ردیف  (' . $ms . 'ms)' . $flag );
	}
	say( '' );

	say( '--- ایندکس‌های postmeta (این تعیین‌کننده است) ---' );
	$prev_suppress = $wpdb->suppress_errors( true );
	$idx           = $wpdb->get_results( "SHOW INDEX FROM `{$wpdb->postmeta}`" ); // phpcs:ignore
	$wpdb->suppress_errors( $prev_suppress );
	if ( $idx ) {
		$by = array();
		foreach ( $idx as $r ) {
			$by[ $r->Key_name ][] = $r->Column_name . '(' . $r->Seq_in_index . ')';
		}
		foreach ( $by as $name => $cols ) {
			$have = ( 'meta_key' === $cols[0] || 0 === strpos( $cols[0], 'meta_key' ) );
			say( '  ' . str_pad( $name, 34 ) . implode( ', ', $cols ) . ( $have ? '  ✓ با meta_key' : '' ) );
		}
		$k = isset( $by['meta_key'] ) ? $by['meta_key'] : array();
		if ( ! $k || 'meta_key' !== $k[0] ) {
			say( '  ⚠⚠⚠ ایندکسِ `meta_key` تنها وجود ندارد! هر meta_query یک اسکن کامل است.' );
		} elseif ( 2 === count( $k ) && false !== strpos( $k[1], 'post_id' ) ) {
			say( '  ✓ meta_key+post_id هست — کوئری‌های تک‌کلیدی باید سریع باشند.' );
		}
	} else {
		say( '  ✗ SHOW INDEX خالی برگشت.' );
	}
	say( '' );

	/* --- EXPLAIN کوئری‌های واقعیِ دو بخشِ کند --- */
	$cases = array();
	if ( function_exists( 'tara_release_meta_keys' ) ) {
		$keys = (array) tara_release_meta_keys();
		if ( $keys ) {
			$k0 = reset( $keys );
			$cases['releases: meta_key+orderby=meta_value (کلیدِ ' . $k0 . ')'] = array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 8,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => $k0,
				'orderby'        => 'meta_value',
				'order'          => 'DESC',
			);
		}
	}
	$cases['video: OR + EXISTS روی ۲ کلید'] = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 8,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => array(
			'relation' => 'OR',
			array( 'key' => 'video720', 'compare' => 'EXISTS' ),
			array( 'key' => 'video1080', 'compare' => 'EXISTS' ),
		),
	);
	$cases['vip: meta_value LIKE \'%1%\''] = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 5,
		'meta_query'     => array(
			array( 'key' => 'vip_song', 'value' => '1', 'compare' => 'LIKE' ),
		),
	);
	$cases['album: musics_type IN'] = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 8,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => array(
			array( 'key' => 'musics_type', 'value' => array( 'آلبوم', 'album', 'Album' ), 'compare' => 'IN' ),
		),
	);

	foreach ( $cases as $label => $args ) {
		say( '' );
		say( '--- EXPLAIN: ' . $label . ' ---' );
		$t = microtime( true );
		$q = new WP_Query( $args );
		$ms = el( $t );
		say( '  اجرا: ' . $ms . 'ms | نتایج: ' . ( $q->post_count ? (int) $q->post_count : (int) $q->found_posts ) . ' | آرگومان post__in: ' . count( (array) $q->posts ) );
		/* SQL خام — حتی اگر EXPLAIN در دسترس نباشد، این تنها شاهدِ واقعی است */
		say( '  SQL: ' . mb_substr( preg_replace( '/\s+/', ' ', (string) $q->request ), 0, 400, 'UTF-8' ) );

		$prev_suppress = $wpdb->suppress_errors( true );
		$rows          = $wpdb->get_results( 'EXPLAIN ' . $q->request ); // phpcs:ignore
		$exp_err       = $wpdb->last_error;
		$wpdb->suppress_errors( $prev_suppress );
		if ( $rows ) {
			foreach ( $rows as $r ) {
				$r = (array) $r;
				foreach ( array( 'table', 'type', 'possible_keys', 'key', 'key_len', 'ref', 'rows', 'filtered', 'Extra' ) as $c ) {
					if ( isset( $r[ $c ] ) && '' !== (string) $r[ $c ] ) {
						say( '    ' . str_pad( $c, 14 ) . ' = ' . $r[ $c ] );
					}
				}
				say( '    ' . str_repeat( '-', 30 ) );
			}
		} else {
			/* پیام خطای wpdb می‌تواند HTML کامل باشد — پاکش کن تا گزارش خراب نشود */
			$exp_txt = trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( (string) $exp_err ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
			say( '  ✗ EXPLAIN خالی' . ( '' !== $exp_txt ? ' — ' . mb_substr( $exp_txt, 0, 140, 'UTF-8' ) : ' (سرور EXPLAIN نمی‌دهد)' ) );
		}
	}

	say( '' );
	say( '--- پیشنهاد: آیا SHOW INDEX با دادهٔ خودتان مطابق است؟ اگر meta_key تنها نبود، همین کافی است. ---' );
}

/* ==================================================================
 * target=hty
 * ================================================================== */
if ( 'hty' === $TARGET ) {
	say( '--- hty: دسته‌های تنظیمات (۲۴۸ کوئری!) ---' );
	say( '' );
	$artista = function_exists( 'ot_get_option' ) ? ot_get_option( 'hty', array() ) : array();
	if ( ! is_array( $artista ) || ! $artista ) {
		say( 'hty خالی است.' );
	} else {
		say( count( $artista ) . ' دسته در تنظیمات' );
		$i = 0;
		foreach ( $artista as $artise ) {
			$i++;
			$cat = isset( $artise['hty-cat'] ) ? $artise['hty-cat'] : '';
			$num = isset( $artise['hty-t'] ) ? $artise['hty-t'] : '';
			probe(
				'دستهٔ ' . $i . ' (cat=' . $cat . ' num=' . $num . ')',
				function () use ( $cat, $num ) {
					$q = new WP_Query( array(
						'post_type'           => 'post',
						'post_status'         => 'publish',
						'cat'                 => $cat,
						'orderby'             => 'date',
						'posts_per_page'      => $num,
						'no_found_rows'       => true,
						'ignore_sticky_posts' => true,
						'update_post_term_cache' => false,
					) );
					$n = 0;
					while ( $q->have_posts() ) {
						$q->the_post();
						$n += strlen( (string) tara_post_card( get_the_ID() ) );
					}
					wp_reset_postdata();
					return $q->post_count . ' نوشته، ' . $n . ' بایت';
				}
			);
		}
	}
}

say( '' );
say( '=== پایان / target=' . $TARGET . ' ===' );
say( 'کل: ' . el( $T0 ) . 'ms | کوئری: ' . (int) $wpdb->num_queries );
