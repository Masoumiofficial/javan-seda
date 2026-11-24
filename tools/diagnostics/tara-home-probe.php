<?php
/**
 * ترانه‌یار — کاوشگرِ صفحهٔ اصلی (v3.17.7)
 *
 * چرا نسخهٔ قبلی جواب نداد؟
 *   کاوشگرِ قبلی خودش را روی آدرس /tara-lock-probe.php اجرا می‌کرد،
 *   پس وردپرس آن را یک آدرس ناشناس می‌دید:
 *
 *       is_home=0   is_front_page=0   is_404=1
 *
 *   و به‌جای صفحهٔ اصلی، **صفحهٔ ۴۰۴** را رندر می‌کرد. برای همین
 *   همه‌چیز «سالم» به نظر می‌رسید (۱۳۲KB / ۱۸۰ms) ولی دربارهٔ خانه
 *   هیچ نمی‌گفت.
 *
 * این نسخه چه می‌کند؟
 *   REQUEST_URI را به '/' عوض می‌کند، دوباره routing می‌کند، و
 *   مسیرِ واقعیِ خانه را می‌رود. اگر جایی قفل کرد، آخرین خط مقصر است.
 *
 * ── حالت‌ها ──
 *
 *   ?key=taranehyar&mode=home
 *       صفحهٔ اصلی، همهٔ هوک‌ها با زمان.  ← این را اول بزنید
 *
 *   ?key=taranehyar&mode=steps
 *       هر مرحله را جدا: بوت ← parse ← query ← head ← body ← footer
 *       با flush واقعی. اگر صفحه وسطِ کار ایستاد، همان‌جا مقصر است.
 *
 *   ?key=taranehyar&mode=plugins
 *       هر افزونه را جدا بار می‌کند و زمان می‌گیرد. کندترین را
 *       نشان می‌دهد — بدون حدس.
 *
 *   ?key=taranehyar&mode=slow
 *       کوئری‌های کندِ صفحهٔ اصلی با SAVEQUERIES.
 *
 * با **مرورگر** باز کنید، نه curl: flush واقعی لازم است.
 * فایل را پس از تشخیص پاک کنید.
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
@ini_set( 'max_execution_time', '180' );
@ini_set( 'memory_limit', '512M' );
@set_time_limit( 180 );
while ( ob_get_level() > 0 ) {
	@ob_end_flush();
}
ob_implicit_flush( true );

header( 'Content-Type: text/plain; charset=utf-8' );
header( 'X-Robots-Tag: noindex, nofollow' );
header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
header( 'X-Accel-Buffering: no' );

$T0   = microtime( true );
$LAST = '(شروع)';

$MODE = isset( $_GET['mode'] ) ? (string) $_GET['mode'] : 'home';

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
	while ( ob_get_level() > 0 ) {
		@ob_end_flush();
	}
	$e = error_get_last();
	echo "\n==================================================\n";
	echo 'آخرین مرحلهٔ کامل‌شده: ' . $LAST . "\n";
	echo 'زمان کل: ' . round( ( microtime( true ) - $T0 ) * 1000, 1 ) . "ms\n";
	echo 'حافظهٔ اوج: ' . round( memory_get_peak_usage( true ) / 1048576, 1 ) . "MB\n";
	if ( $e ) {
		echo 'خطای PHP: ' . $e['message'] . "\n";
		echo '  در ' . basename( (string) $e['file'] ) . ':' . $e['line'] . "\n";
	}
	echo "==================================================\n";
} );

say( '=== کاوشگرِ صفحهٔ اصلی / mode=' . $MODE . ' ===' );
say( 'PHP ' . PHP_VERSION . ' | ' . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '?' ) );

/* ------------------------------------------------------------------
 * محاسبهٔ آدرسِ خانه **بدون** بوتِ وردپرس — تا قبل از هر کوئری بدانیم
 * دنبال چه می‌گردیم.
 * ------------------------------------------------------------------ */
$root = dirname( __FILE__ );
if ( ! file_exists( $root . '/wp-load.php' ) ) {
	say( 'wp-load.php نیست.' );
	exit;
}

/* ------------------------------------------------------------------
 * حالت plugins: هر افزونه جدا، بدون بوتِ کامل
 * ------------------------------------------------------------------ */
if ( 'plugins' === $MODE ) {
	say( '' );
	say( '--- mode=plugins: زمانِ هر افزونه ---' );
	/* فقط wpdb و ثابت‌ها لازم است؛ پس SHORTINIT */
	define( 'SHORTINIT', true );
	require_once $root . '/wp-load.php';

	$active = (array) get_option( 'active_plugins', array() );
	$dir    = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : $root . '/wp-content/plugins';
	say( 'افزونهٔ فعال: ' . count( $active ) );
	say( '' );

	$times = array();
	foreach ( $active as $rel ) {
		$file = $dir . '/' . $rel;
		$t    = microtime( true );
		$err  = '';
		if ( ! file_exists( $file ) ) {
			$times[ $rel ] = -1;
			say( '  ✗ ' . $rel . ' — فایل نیست' );
			continue;
		}
		/* include در همان پروسه — خطا را می‌گیریم */
		$before = error_reporting();
		try {
			include_once $file;
		} catch ( Throwable $e ) {
			$err = get_class( $e ) . ': ' . $e->getMessage();
		}
		error_reporting( $before );
		$ms = el( $t );
		$times[ $rel ] = $ms;
		say( '  ' . ( $ms > 100 ? '⚠' : ' ' ) . ' ' . str_pad( number_format( $ms, 1 ) . 'ms', 10 ) . $rel . ( '' !== $err ? '  خطا: ' . $err : '' ) );
	}

	arsort( $times );
	say( '' );
	say( '--- ۵ کندترین ---' );
	$i = 0;
	foreach ( $times as $rel => $ms ) {
		if ( $ms < 0 ) {
			continue;
		}
		say( '  ' . str_pad( number_format( $ms, 1 ) . 'ms', 10 ) . $rel );
		if ( ++$i >= 5 ) {
			break;
		}
	}
	say( '' );
	say( 'توجه: collect_plugins شبیه‌سازی است؛ ترتیبِ واقعی همان ترتیبِ آرایه است.' );
	say( '=== پایان mode=plugins ===' );
	exit;
}

/* ==================================================================
 * بوت
 * ================================================================== */
say( '' );
say( '--- بوتِ وردپرس ---' );
$t_boot = microtime( true );
define( 'WP_USE_THEMES', true );

$php_errors = array();
set_error_handler( function ( $no, $str, $file, $line ) use ( &$php_errors ) {
	$php_errors[] = 'PHP[' . $no . '] ' . $str . ' @ ' . basename( $file ) . ':' . $line;
	return false;
} );
try {
	require_once $root . '/wp-load.php';
} catch ( Throwable $e ) {
	say( 'FATAL در بوت: ' . $e->getMessage() );
	say( '  در ' . basename( $e->getFile() ) . ':' . $e->getLine() );
	exit;
}
restore_error_handler();
say( 'بوت شد: ' . el( $t_boot ) . 'ms' );

global $wpdb;
say( 'افزونهٔ فعال: ' . count( (array) get_option( 'active_plugins', array() ) ) );
say( 'پرسش تا اینجا: ' . (int) $wpdb->num_queries );
say( 'خانه: ' . home_url( '/' ) . ' | siteurl: ' . site_url( '/' ) );
if ( $php_errors ) {
	say( 'اخطارهای PHP: ' . count( $php_errors ) );
	foreach ( array_slice( $php_errors, 0, 5 ) as $pe ) {
		say( '  ' . $pe );
	}
}
say( '' );

/* ==================================================================
 * حالت steps: هر مرحله جدا با flush
 * ================================================================== */
if ( 'steps' === $MODE ) {
	say( '--- mode=steps ---' );

	/* قدم ۱: parse_request */
	say( 'قدم ۱: parse_request…' );
	$t = microtime( true );
	global $wp;
	$wp->init();
	$wp->parse_request();
	say( '  ✔ ' . el( $t ) . 'ms | request=' . var_export( ( isset( $wp->request ) ? $wp->request : '' ), true ) );

	/* قدم ۲: send_headers */
	say( 'قدم ۲: send_headers…' );
	$t = microtime( true );
	$wp->send_headers();
	say( '  ✔ ' . el( $t ) . 'ms' );

	/* قدم ۳: query_posts */
	say( 'قدم ۳: query_posts…' );
	$t = microtime( true );
	$wp->query_posts();
	say( '  ✔ ' . el( $t ) . 'ms | نتایج=' . (int) $GLOBALS['wp_query']->found_posts );

	/* قدم ۴: handle_404 */
	say( 'قدم ۴: handle_404…' );
	$t = microtime( true );
	$wp->handle_404();
	say( '  ✔ ' . el( $t ) . 'ms | is_404=' . ( is_404() ? '1' : '0' ) );

	/* قدم ۵: register_globals */
	$wp->register_globals();
	say( 'قدم ۵: register_globals ✔' );
	say( '' );

	/* قدم ۶: header.php */
	say( 'قدم ۶: header.php…' );
	$t = microtime( true );
	$lvl = ob_get_level();
	ob_start();
	try {
		locate_template( 'header.php', true, true );
	} catch ( Throwable $e ) {
		while ( ob_get_level() > $lvl ) {
			ob_end_clean();
		}
		say( '  FATAL: ' . $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine() );
		exit;
	}
	$head = ob_get_clean();
	say( '  ✔ ' . el( $t ) . 'ms | ' . strlen( $head ) . ' بایت' );

	/* قدم ۷: بدنه (home.php) */
	say( 'قدم ۷: بدنه (home.php)…' );
	$t = microtime( true );
	ob_start();
	try {
		$tpl = locate_template( array( 'home.php', 'index.php' ), false, false );
		if ( $tpl ) {
			include $tpl;
		}
	} catch ( Throwable $e ) {
		while ( ob_get_level() > $lvl ) {
			ob_end_clean();
		}
		say( '  FATAL: ' . $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine() );
		exit;
	}
	$body = ob_get_clean();
	say( '  ✔ ' . el( $t ) . 'ms | ' . strlen( $body ) . ' بایت | قالب=' . basename( (string) $tpl ) );

	say( '' );
	say( '--- خلاصه ---' );
	say( 'بوت:     ' . el( $t_boot ) . 'ms' );
	say( 'پرسش:    ' . (int) $wpdb->num_queries );
	say( 'کل:      ' . el( $T0 ) . 'ms' );
	say( '=== پایان mode=steps ===' );
	exit;
}

/* ==================================================================
 * حالت slow: کوئری‌های کند
 * ================================================================== */
if ( 'slow' === $MODE ) {
	say( '--- mode=slow: کوئری‌های کند ---' );
	/* SAVEQUERIES باید قبل از بوت تعریف شود؛ اینجا دیر است، پس
	   از $wpdb->queries استفاده می‌کنیم فقط اگر موجود باشد. */
	global $wpdb;
	say( 'توجه: SAVEQUERIES در wp-config لازم است. اگر ۰ نشان داد،' );
	say( '       در wp-config.php بگذارید: define( "SAVEQUERIES", true );' );
	say( '' );
}

/* ==================================================================
 * حالت home: صفحهٔ اصلی با عوض‌کردنِ REQUEST_URI
 * ================================================================== */
say( '--- mode=' . $MODE . ': صفحهٔ اصلی ---' );

/* اینجا کلیدِ ماجراست: آدرس را به / عوض می‌کنیم و همه‌چیز را
   از نو می‌سازیم. وگرنه کاوشگر خودش یک ۴۰۴ می‌شود. */
$_SERVER['REQUEST_URI']     = '/';
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';
$_SERVER['QUERY_STRING']    = '';
$_SERVER['REQUEST_METHOD']  = 'GET';
$_GET                       = array();

say( 'REQUEST_URI به / عوض شد. routingِ دوباره…' );
say( '' );

$wp = new WP();
$wp->init();
$t = microtime( true );
$wp->parse_request();
say( 'parse_request: ' . el( $t ) . 'ms | request=' . var_export( ( isset( $wp->request ) ? $wp->request : '' ), true ) );

$t = microtime( true );
$wp->send_headers();
say( 'send_headers: ' . el( $t ) . 'ms' );

$t = microtime( true );
$wp->query_posts();
say( 'query_posts: ' . el( $t ) . 'ms' );

$t = microtime( true );
$wp->handle_404();
say( 'handle_404: ' . el( $t ) . 'ms' );

$wp->register_globals();

global $wp_query;
say( '' );
say( '★ main query: ' . ( $wp_query ? (int) $wp_query->found_posts : '?' ) . ' نتیجه' );
say( '★ is_home=' . ( is_home() ? '1' : '0' ) . ' is_front_page=' . ( is_front_page() ? '1' : '0' ) . ' is_404=' . ( is_404() ? '1' : '0' ) . ' is_singular=' . ( is_singular() ? '1' : '0' ) );
say( '' );

/* پوششِ هوک‌ها */
function ty_lbl( $fn, $name ) {
	if ( is_string( $fn ) ) {
		return $fn . '()';
	}
	if ( is_array( $fn ) ) {
		$c = is_object( $fn[0] ) ? get_class( $fn[0] ) : (string) $fn[0];
		return $c . '::' . $fn[1];
	}
	if ( $fn instanceof Closure ) {
		try {
			$r = new ReflectionFunction( $fn );
			return 'closure(' . basename( (string) $r->getFileName() ) . ':' . $r->getStartLine() . ')';
		} catch ( Throwable $e ) {
			return 'closure';
		}
	}
	if ( is_object( $fn ) ) {
		return get_class( $fn ) . '::__invoke';
	}
	return (string) $name;
}

function ty_wrap( array $tags ) {
	global $wp_filter;
	$n = 0;
	foreach ( $tags as $tag ) {
		if ( ! isset( $wp_filter[ $tag ] ) || ! $wp_filter[ $tag ] instanceof WP_Hook ) {
			continue;
		}
		foreach ( $wp_filter[ $tag ]->callbacks as $prio => $cbs ) {
			foreach ( $cbs as $name => $cb ) {
				$orig     = $cb['function'];
				$accepted = (int) $cb['accepted_args'];
				$lbl      = ty_lbl( $orig, $name );
				$wp_filter[ $tag ]->callbacks[ $prio ][ $name ]['function'] = function () use ( $orig, $accepted, $lbl, $tag, $prio ) {
					$t = microtime( true );
					say( '  ▶ ' . $tag . '[' . $prio . '] ' . $lbl );
					$args = func_get_args();
					$args = ( $accepted > 0 ) ? array_slice( $args, 0, $accepted ) : array();
					/* خروجیِ کلوزر (HTML یا <link>/<title>) را می‌بلعیم تا
					   گزارش تمیز بمانَد؛ فقط اگر جایی exit کرد، flush می‌شود. */
					$lvl0 = ob_get_level();
					$r    = call_user_func_array( $orig, $args );
					while ( ob_get_level() > $lvl0 ) {
						ob_end_clean();
					}
					$ms = el( $t );
					say( '    ✔ ' . $ms . 'ms' . ( $ms > 500 ? '   ⚠⚠ کند' : '' ) );
					return $r;
				};
				$n++;
			}
		}
	}
	return $n;
}

$WATCH = array( 'template_redirect', 'wp_head', 'wp_enqueue_scripts', 'wp_footer' );
$nw    = ty_wrap( $WATCH );
say( $nw . ' کلوزر روی ' . count( $WATCH ) . ' هوک پوشش داده شد.' );
say( '' );

say( 'template-loader …' );
$t  = microtime( true );
$lvl = ob_get_level();
ob_start();
try {
	require_once ABSPATH . WPINC . '/template-loader.php';
} catch ( Throwable $e ) {
	while ( ob_get_level() > $lvl ) {
		ob_end_clean();
	}
	say( 'FATAL در template-loader: ' . $e->getMessage() );
	say( '  در ' . basename( $e->getFile() ) . ':' . $e->getLine() );
	exit;
}
$html = ob_get_clean();  /* HTML دور ریخته می‌شود، فقط اندازه‌اش را می‌گوییم */

say( 'رندر تمام شد: ' . strlen( $html ) . ' بایت در ' . el( $t ) . 'ms' );
say( '  </html> : ' . ( false !== stripos( $html, '</html>' ) ? 'بله' : 'نه' ) );
say( '  <header : ' . ( false !== stripos( $html, '<header' ) ? 'بله' : 'نه' ) );
say( '  song_boxs: ' . ( false !== stripos( $html, 'song_boxs' ) ? 'بله' : 'نه' ) );
say( '  با عنوانِ آهنگ: ' . ( false !== stripos( $html, 'آهنگ شماره' ) ? 'بله' : 'نه' ) );
say( '' );
say( '--- خلاصه ---' );
say( 'بوت:          ' . el( $t_boot ) . 'ms' );
say( 'کل پرسش‌ها:   ' . (int) $wpdb->num_queries );
say( 'زمان کل:      ' . el( $T0 ) . 'ms' );
say( 'حافظهٔ اوج:    ' . round( memory_get_peak_usage( true ) / 1048576, 1 ) . 'MB' );
say( '' );
say( '=== پایان mode=' . $MODE . ' ===' );
