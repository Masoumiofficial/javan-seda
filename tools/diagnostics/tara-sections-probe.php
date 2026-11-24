<?php
/**
 * ترانه‌یار — کاوشگرِ بخش‌های صفحهٔ اصلی (v3.17.8)
 *
 * چرا؟
 *   کاوشگرِ ۳.۱۷.۷ گفت:
 *       قدم ۶: header.php  ✔ 82.9ms | 42436 بایت
 *       قدم ۷: بدنه (home.php) …
 *                              ← هیچ ✔ نیامد (timeout)
 *
 *   یعنی header سالم است و **بدنهٔ home.php قفل می‌کند**.
 *   این فایل همان بدنه را بخش‌به‌بخش اجرا می‌کند تا مقصر پیدا شود.
 *
 * ── حالت‌ها ──
 *   ?key=taranehyar            همهٔ بخش‌ها، یکی‌یکی
 *   ?key=taranehyar&only=NAME  فقط یک بخش
 *
 * بخش‌ها به همان ترتیبی که home.php اجرا می‌کند:
 *   quickbar, vip_slider, player, radio, playlists,
 *   new_posts, mostviews, apex, hty(دسته‌ها), artist_slider
 *
 * هر بخش در بافرِ خودش اجرا می‌شود؛ خروجی‌اش دور ریخته می‌شود و
 * فقط زمان + حجم چاپ می‌شود. اگر جایی قفل کرد، آخرین خط مقصر است.
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
header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
header( 'X-Accel-Buffering: no' );
header( 'Connection: keep-alive' );

$T0   = microtime( true );
$LAST = '(شروع)';
$ONLY = isset( $_GET['only'] ) ? trim( (string) $_GET['only'] ) : '';

function say( $m ) {
	global $T0, $LAST;
	$LAST = $m;
	echo '[' . str_pad( round( ( microtime( true ) - $T0 ) * 1000, 1 ), 9, ' ', STR_PAD_LEFT ) . 'ms] ' . $m . "\n";
	@flush();
}
function el( $t ) {
	return round( ( microtime( true ) - $t ) * 1000, 1 );
}

register_shutdown_function( function () {
	global $T0, $LAST;
	$e = error_get_last();
	echo "\n==================================================\n";
	echo 'آخرین بخشِ کامل‌شده: ' . $LAST . "\n";
	echo 'زمان کل: ' . round( ( microtime( true ) - $T0 ) * 1000, 1 ) . "ms\n";
	echo 'حافظهٔ اوج: ' . round( memory_get_peak_usage( true ) / 1048576, 1 ) . "MB\n";
	if ( $e ) {
		echo 'خطای PHP: ' . $e['message'] . "\n";
		echo '  در ' . basename( (string) $e['file'] ) . ':' . $e['line'] . "\n";
		echo '  نوع: ' . $e['type'] . ' (1=E_ERROR، 2=WARNING، 4=PARSE، 8=NOTICE، 16=FATAL، 64=COMPILE، 256=USER_ERROR، 2048=STRICT، 8192=DEPRECATED)' . "\n";
	}
	echo "==================================================\n";
} );

say( '=== کاوشگرِ بخش‌های صفحهٔ اصلی ===' );
say( 'PHP ' . PHP_VERSION . ' | ' . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '?' ) );
say( 'max_execution_time=' . ini_get( 'max_execution_time' ) . ' | memory_limit=' . ini_get( 'memory_limit' ) );
say( 'only=' . ( '' !== $ONLY ? $ONLY : '(همه)' ) );
say( '' );

$root = dirname( __FILE__ );
if ( ! file_exists( $root . '/wp-load.php' ) ) {
	say( 'wp-load.php نیست.' );
	exit;
}

/* --- بوت --- */
say( '--- بوت ---' );
$t_boot = microtime( true );
define( 'WP_USE_THEMES', true );
try {
	require_once $root . '/wp-load.php';
} catch ( Throwable $e ) {
	say( 'FATAL در بوت: ' . $e->getMessage() );
	exit;
}
say( 'بوت: ' . el( $t_boot ) . 'ms | افزونه: ' . count( (array) get_option( 'active_plugins', array() ) ) );
global $wpdb;
say( 'پرسش تا اینجا: ' . (int) $wpdb->num_queries );
say( '' );

/* --- routing مثل بازدیدکننده --- */
say( '--- routingِ صفحهٔ اصلی ---' );
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['SCRIPT_NAME']    = '/index.php';
$_SERVER['PHP_SELF']       = '/index.php';
$_SERVER['QUERY_STRING']   = '';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET                      = array();

global $wp;
$wp = new WP();
$wp->init();
$t = microtime( true );
$wp->parse_request();
say( 'parse_request: ' . el( $t ) . 'ms' );
$t = microtime( true );
$wp->send_headers();
say( 'send_headers: ' . el( $t ) . 'ms' );
$t = microtime( true );
$wp->query_posts();
say( 'query_posts: ' . el( $t ) . 'ms | نتایج=' . (int) $GLOBALS['wp_query']->found_posts );
$wp->handle_404();
$wp->register_globals();
say( 'is_home=' . ( is_home() ? '1' : '0' ) . ' is_404=' . ( is_404() ? '1' : '0' ) );
say( '' );

/* ==================================================================
 * تعریفِ بخش‌ها — هر کدام عیناً همان چیزی که home.php صدا می‌زند
 * ================================================================== */

$sections = array();

$sections['quickbar'] = function () {
	if ( function_exists( 'tara_news_ticker' ) ) {
		tara_news_ticker();
	}
};

$sections['vip_slider'] = function () {
	get_template_part( 'inc/vip_slider' );
};

$sections['player'] = function () {
	get_template_part( 'inc/tarlanweb_player' );
};

$sections['radio'] = function () {
	if ( function_exists( 'tara_radio_home_section' ) ) {
		echo tara_radio_home_section(); // phpcs:ignore
	}
};

$sections['playlists'] = function () {
	if ( function_exists( 'tara_playlists_home' ) ) {
		echo tara_playlists_home(); // phpcs:ignore
	}
};

$sections['new_posts'] = function () {
	get_template_part( 'inc/new_posts' );
};

$sections['mostviews'] = function () {
	get_template_part( 'inc/mostviews_posts' );
};

$sections['apex'] = function () {
	if ( function_exists( 'tara_home_apex_sections' ) ) {
		echo tara_home_apex_sections(); // phpcs:ignore
	}
};

$sections['hty'] = function () {
	$artista = function_exists( 'ot_get_option' ) ? ot_get_option( 'hty', array() ) : array();
	if ( ! is_array( $artista ) || ! $artista ) {
		echo '<!-- hty خالی -->';
		return;
	}
	foreach ( $artista as $artise ) {
		$cat_ft1 = isset( $artise['hty-cat'] ) ? $artise['hty-cat'] : '';
		$num_ft1 = isset( $artise['hty-t'] ) ? $artise['hty-t'] : '';
		$q       = new WP_Query(
			array(
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'cat'                    => $cat_ft1,
				'orderby'                => 'date',
				'posts_per_page'         => $num_ft1,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
			)
		);
		if ( $q->have_posts() ) {
			while ( $q->have_posts() ) {
				$q->the_post();
				get_template_part( 'inc/songs_post' );
			}
		}
		wp_reset_postdata();
	}
};

$sections['artist_slider'] = function () {
	get_template_part( 'inc/artist_slider' );
};

/* ==================================================================
 * اجرا
 * ================================================================== */
say( '--- اجرای بخش‌ها ---' );
say( '(هر بخش در بافرِ خودش؛ خروجی دور ریخته می‌شود)' );
say( '' );

$results = array();
foreach ( $sections as $name => $fn ) {
	if ( '' !== $ONLY && $ONLY !== $name ) {
		continue;
	}
	$t   = microtime( true );
	$lvl = ob_get_level();
	$q0  = (int) $wpdb->num_queries;

	say( '▶ ' . $name . ' …' );
	ob_start();
	try {
		$fn();
	} catch ( Throwable $e ) {
		while ( ob_get_level() > $lvl ) {
			ob_end_clean();
		}
		$ms = el( $t );
		say( '  ✗ خطا در ' . $name . ': ' . get_class( $e ) . ': ' . $e->getMessage() );
		say( '    در ' . basename( $e->getFile() ) . ':' . $e->getLine() );
		$results[ $name ] = array( 'ms' => $ms, 'bytes' => 0, 'queries' => 0, 'err' => true );
		continue;
	}
	$out = ob_get_clean();
	$ms  = el( $t );
	$q   = (int) $wpdb->num_queries - $q0;

	$results[ $name ] = array( 'ms' => $ms, 'bytes' => strlen( $out ), 'queries' => $q, 'err' => false );

	$flag = '';
	if ( $ms > 2000 ) {
		$flag = '   ⚠⚠⚠ خیلی کند';
	} elseif ( $ms > 500 ) {
		$flag = '   ⚠⚠ کند';
	} elseif ( $ms > 150 ) {
		$flag = '   ⚠';
	}
	say( '  ✔ ' . $ms . 'ms | ' . strlen( $out ) . ' بایت | ' . $q . ' کوئری' . $flag );
}

say( '' );
say( '--- خلاصه (کندترین اول) ---' );
$sorted = $results;
uasort(
	$sorted,
	function ( $a, $b ) {
		return $b['ms'] <=> $a['ms'];
	}
);
foreach ( $sorted as $name => $r ) {
	say(
		'  ' . str_pad( $name, 16 ) .
		str_pad( $r['ms'] . 'ms', 12 ) .
		str_pad( $r['bytes'] . ' بایت', 14 ) .
		$r['queries'] . ' کوئری' .
		( $r['err'] ? '   ✗' : '' )
	);
}

say( '' );
say( 'کل: ' . el( $T0 ) . 'ms | کوئری: ' . (int) $wpdb->num_queries . ' | حافظه: ' . round( memory_get_peak_usage( true ) / 1048576, 1 ) . 'MB' );
say( '' );
say( '=== پایان ===' );
say( 'اگر بخشی در فهرست بالا نیست، همان‌جا قفل کرده است.' );
