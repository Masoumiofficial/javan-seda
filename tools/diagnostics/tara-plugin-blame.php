<?php
/**
 * ترانه‌یار — «کدام افزونه؟» (v3.17.9)
 *
 * چرا این فایل: اندازه‌گیریِ سایتِ واقعی نشان داد
 *   apex        79,516ms  تنها با ۳۲ کوئری
 *   vip_slider  23,511ms  تنها با ۵۵ کوئری
 * ولی mostviews با ۱۲۶ کوئری در ۱۸۶ms تمام شد.
 *
 * این یعنی گلوگاه SQL نیست — چیزی در PHP در حالِ کار است.
 * قالب هیچ تماسِ شبکه‌ای در این دو بخش ندارد (grep شد).
 * پس مظنون بعدی: **یک افزونه** که روی کوئری/تصویر/ترنزینت قلاب زده.
 *
 * این فایل همان صفحهٔ خانه را چند بار اجرا می‌کند — هر بار با یک
 * افزونهٔ کم‌شده — و زمان هر بار را می‌گوید. اگر یک افزونه مقصر باشد،
 * زمانِ آن دور به‌شدت پایین می‌آید.
 *
 *   ?key=taranehyar              ← فقط Basline + همهٔ افزونه‌ها
 *   ?key=taranehyar&skip=1       ← هر افزونه را جدا بیرون بگذار
 *   ?key=taranehyar&kill=slug    ← فقط همان یکی را بیرون بگذار
 *
 * ⚠ این فایل وضعیت افزونه‌ها را در دیتابیس تغییر می‌دهد و **برمی‌گرداند**.
 *    اگر وسطِ کار قطع شود، `?restore=1` وضعیت را برمی‌گرداند.
 * بعد از تشخیص حذفش کنید.
 */

$SECRET = 'taranehyar';
$restore_only = isset( $_GET['restore'] );
if ( ! isset( $_GET['key'] ) || $SECRET !== (string) $_GET['key'] ) {
	header( 'HTTP/1.1 404 Not Found' );
	echo 'Not found.';
	exit;
}

@ini_set( 'display_errors', '0' );
@ini_set( 'max_execution_time', '900' );
@set_time_limit( 900 );
@ini_set( 'memory_limit', '512M' );
while ( ob_get_level() > 0 ) {
	@ob_end_flush();
}
header( 'Content-Type: text/plain; charset=utf-8' );
header( 'X-Robots-Tag: noindex, nofollow' );
header( 'Cache-Control: no-store' );
header( 'X-Accel-Buffering: no' );

$T0 = microtime( true );
function say( $m ) {
	global $T0;
	echo '[' . str_pad( round( ( microtime( true ) - $T0 ) * 1000, 0 ), 8, ' ', STR_PAD_LEFT ) . 'ms] ' . $m . "\n";
	@flush();
}

$root = dirname( __FILE__ );
require_once $root . '/wp-load.php';

global $wpdb;

$key_state = 'tara_blame_state';
$snap      = get_option( $key_state, array() );

/* شبکهٔ ایمنی: اگر جایی خطای مرگبار/تایم‌اوت خورد، افزونه‌ها برمی‌گردند. */
$GLOBALS['tara_pb_restore'] = isset( $snap['active'] ) ? (array) $snap['active'] : null;
$GLOBALS['tara_pb_net']     = isset( $snap['network'] ) ? (array) $snap['network'] : array();
register_shutdown_function(
	function () {
		if ( empty( $GLOBALS['tara_pb_restore'] ) || empty( $GLOBALS['tara_pb_armed'] ) ) {
			return;
		}
		update_option( 'active_plugins', (array) $GLOBALS['tara_pb_restore'] );
		if ( is_multisite() && ! empty( $GLOBALS['tara_pb_net'] ) ) {
			update_site_option( 'active_sitewide_plugins', (array) $GLOBALS['tara_pb_net'] );
		}
		echo "\n[شبکهٔ ایمنی] افزونه‌ها به وضعیت اصلی برگردانده شدند (" . count( (array) $GLOBALS['tara_pb_restore'] ) . ").\n";
	}
);

/* ---------- restore ---------- */
if ( $restore_only || isset( $_GET['restore'] ) ) {
	if ( is_array( $snap ) && isset( $snap['active'], $snap['network'] ) ) {
		update_option( 'active_plugins', $snap['active'] );
		if ( is_multisite() ) {
			update_site_option( 'active_sitewide_plugins', $snap['network'] );
		}
		delete_option( $key_state );
		say( '✓ وضعیت افزونه‌ها برگردانده شد (' . count( (array) $snap['active'] ) . ' فعال).' );
	} else {
		say( '— چیزی برای برگرداندن نبود.' );
	}
	exit;
}

say( '=== کدام افزونه؟ v3.17.9 ===' );
say( 'PHP ' . PHP_VERSION . ' | ' . $wpdb->db_server_info() );

$active_now = (array) get_option( 'active_plugins', array() );
say( 'افزونه‌های فعال: ' . count( $active_now ) );
foreach ( $active_now as $p ) {
	say( '  • ' . $p );
}
say( '' );

/* snapshot یک‌بار */
if ( ! is_array( $snap ) || ! isset( $snap['active'] ) ) {
	update_option(
		$key_state,
		array(
			'active'  => $active_now,
			'network' => is_multisite() ? (array) get_site_option( 'active_sitewide_plugins', array() ) : array(),
		),
		false
	);
	say( '✓ وضعیت اصلی ذخیره شد (برای ?restore=1).' );
	say( '' );
}

/**
 * یک بار رندرِ کاملِ صفحهٔ خانه + اندازه‌گیری.
 * از query_posts استفاده می‌کند تا همان مسیرِ home.php اجرا شود.
 */
function tara_time_home( $label ) {
	global $wpdb;

	/* کشِ بخش‌ها را پاک کن تا هر دور واقعاً محاسبه کند */
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tara_hq_%' OR option_name LIKE '_transient_tara_lrel_%' OR option_name LIKE '_transient_timeout_tara_hq_%' OR option_name LIKE '_transient_timeout_tara_lrel_%'" ); // phpcs:ignore

	wp_cache_flush();

	/* روتینگ خانه */
	$_SERVER['REQUEST_URI']    = '/';
	$_SERVER['SCRIPT_NAME']    = '/index.php';
	$_SERVER['PHP_SELF']       = '/index.php';
	$_SERVER['QUERY_STRING']   = '';
	$_SERVER['REQUEST_METHOD'] = 'GET';
	$_GET                      = array();

	$q0 = (int) $wpdb->num_queries;
	$t  = microtime( true );
	$lvl = ob_get_level();
	ob_start();
	try {
		query_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'paged' => 1 ) );
		if ( function_exists( 'tara_home_apex_sections' ) ) {
			echo tara_home_apex_sections(); // phpcs:ignore
		}
		if ( function_exists( 'tara_vip_slider' ) ) {
			tara_vip_slider();
		} elseif ( locate_template( 'inc/vip_slider.php' ) ) {
			include locate_template( 'inc/vip_slider.php' );
		}
	} catch ( Throwable $e ) {
		while ( ob_get_level() > $lvl ) {
			ob_end_clean();
		}
		return array( 'ms' => -1, 'q' => 0, 'b' => 0, 'err' => $e->getMessage() );
	}
	$out = ob_get_clean();
	$ms  = round( ( microtime( true ) - $t ) * 1000, 1 );
	$q   = (int) $wpdb->num_queries - $q0;
	wp_reset_query();

	say( sprintf( '  %-46s %10sms  %4dq  %8d بایت', $label, $ms, $q, strlen( (string) $out ) ) );
	return array( 'ms' => $ms, 'q' => $q, 'b' => strlen( (string) $out ), 'err' => '' );
}

say( '--- پایه: همهٔ افزونه‌ها فعال ---' );
$base = tara_time_home( 'همه فعال (Baseline)' );
say( '' );

if ( $base['ms'] < 3000 ) {
	say( '✓ پایه زیر ۳ ثانیه است — کندی این اجرا بازتولید نشد.' );
	say( '  (کش/OPcache یا افزونهٔ کش صفحه فعال است. کش را خاموش و دوباره اجرا کنید.)' );
	exit;
}

$mode_skip = isset( $_GET['skip'] );
$kill      = isset( $_GET['kill'] ) ? trim( (string) $_GET['kill'] ) : '';

if ( ! $mode_skip && '' === $kill ) {
	say( 'پایه کند است (' . $base['ms'] . 'ms). حالا:' );
	say( '  ?key=taranehyar&skip=1     ← هر افزونه را یکی‌یکی بیرون بگذار' );
	say( '  ?key=taranehyar&kill=SLUG  ← فقط همان یکی' );
	say( '' );
	say( 'فهرست slugها بالا چاپ شد.' );
	exit;
}

$targets = $active_now;
if ( '' !== $kill ) {
	$targets = array();
	foreach ( $active_now as $p ) {
		if ( false !== strpos( $p, $kill ) ) {
			$targets[] = $p;
		}
	}
	if ( ! $targets ) {
		say( '✗ هیچ افزونه‌ای با «' . $kill . '» پیدا نشد.' );
		exit;
	}
}

say( '--- هر دور: یک افزونه خاموش ---' );
$GLOBALS['tara_pb_armed'] = true;
$results = array();
foreach ( $targets as $p ) {
	$rest = array_values( array_diff( $active_now, array( $p ) ) );
	update_option( 'active_plugins', $rest );
	usleep( 150000 );
	$r = tara_time_home( 'بدون ' . basename( dirname( $p ) ) );
	$results[ $p ] = $r;
	update_option( 'active_plugins', $active_now );
	usleep( 150000 );
}

/* برگرداندن قطعی */
update_option( 'active_plugins', $active_now );
if ( is_multisite() ) {
	update_site_option( 'active_sitewide_plugins', (array) $snap['network'] );
}
$GLOBALS['tara_pb_armed'] = false;
say( '' );
say( '✓ همهٔ افزونه‌ها دوباره فعال شدند (' . count( $active_now ) . ') — وضعیت قبل از اجرا.' );

/* خلاصه */
$deltas = array();
foreach ( $results as $p => $r ) {
	if ( $r['ms'] < 0 ) {
		$deltas[ $p ] = -1;
		continue;
	}
	$deltas[ $p ] = round( $base['ms'] - $r['ms'], 1 );
}
arsort( $deltas );
say( '' );
say( '=== خلاصه: چقدر سریع شد وقتی افزونه خاموش بود ===' );
foreach ( $deltas as $p => $d ) {
	$label = basename( dirname( $p ) );
	$flag  = '';
	if ( $d > 5000 ) {
		$flag = '  ⚠⚠⚠ مقصر';
	} elseif ( $d > 1000 ) {
		$flag = '  ⚠⚠ اثرگذار';
	} elseif ( $d < 0 ) {
		$flag = '  (خطا/نوسان)';
	}
	say( sprintf( '  %-40s %12s ms%s', $label, $d, $flag ) );
}
