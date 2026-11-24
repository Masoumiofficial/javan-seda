<?php
/**
 * ترانه‌یار — کاوشگرِ قفلِ صفحهٔ اصلی (v3.17.5)
 *
 * این فایل برای یک مسئلهٔ مشخص نوشته شده:
 *
 *   در سایت ahangban.ir:
 *     - اجرای مستقیم index.php سالم است  (۲۱۰ms / ۸۹KB)
 *     - کاوشگر وردپرس هم سالم است        (۶۴۳ms)
 *     - ولی درخواست واقعی به / صفر بایت می‌دهد
 *
 * یعنی مشکل *داخلِ* کد قالب نیست؛ در چیزی است که فقط در مسیر
 * واقعیِ HTTP رخ می‌دهد. این فایل همان مسیر را گام‌به‌گام با
 * flushِ واقعی طی می‌کند تا آخرین گامِ سالم معلوم شود.
 *
 * ── دو حالت دارد ──
 *
 *   ?key=taranehyar&mode=a
 *       مسیر واقعی: WP::main() + template-loader  (شبیه بازدیدکننده)
 *       هر کلوزر روی هوک‌ها با زمان و «قبل/بعد» چاپ می‌شود.
 *       اگر جایی قفل کرد، آخرین خط می‌گوید کدام.
 *
 *   ?key=taranehyar&mode=b
 *       فقط بوت + اندازه‌گیریِ چیزهایی که می‌توانند کند باشند:
 *       options autoload، transient ها، جدول‌های بزرگ، object cache.
 *
 * ── نکته ──
 * این فایل خروجی را با flush واقعی می‌فرستد. با مرورگر باز کنید،
 * نه با curl. اگر صفحه وسطِ کار ایستاد، تا همان‌جا اسکرول کنید:
 * آن خط، مقصر است.
 *
 * فایل را پس از تشخیص پاک کنید.
 */

$SECRET = 'taranehyar';
if ( ! isset( $_GET['key'] ) || $SECRET !== (string) $_GET['key'] ) {
	header( 'HTTP/1.1 404 Not Found' );
	echo 'Not found.';
	exit;
}

/* ------------------------------------------------------------------
 * خروجی را از همان اول آزاد کن. هر بافری که هاست گذاشته برداشته
 * می‌شود، تا اگر جایی قفل کردیم مرورگر تا همان‌جا را نشان دهد.
 * ------------------------------------------------------------------ */
@ini_set( 'output_buffering', '0' );
@ini_set( 'zlib.output_compression', '0' );
@ini_set( 'implicit_flush', '1' );
@ini_set( 'max_execution_time', '120' );
@set_time_limit( 120 );
while ( ob_get_level() > 0 ) {
	@ob_end_flush();
}
ob_implicit_flush( true );

header( 'Content-Type: text/plain; charset=utf-8' );
header( 'X-Robots-Tag: noindex, nofollow' );
header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
header( 'X-Accel-Buffering: no' );

$T0    = microtime( true );
$LAST  = '(شروع)';
$MODE  = isset( $_GET['mode'] ) ? (string) $_GET['mode'] : 'a';

function say( $m ) {
	global $T0, $LAST;
	$LAST = $m;
	echo '[' . str_pad( round( ( microtime( true ) - $T0 ) * 1000, 1 ), 9, ' ', STR_PAD_LEFT ) . 'ms] ' . $m . "\n";
	@flush();
}

function el( $t ) {
	return round( ( microtime( true ) - $t ) * 1000, 1 );
}

/* اگر جایی مرد، بگو کجا — این مهم‌ترین بخش است */
register_shutdown_function( function () {
	global $T0, $LAST;
	$e = error_get_last();
	echo "\n";
	echo "==================================================\n";
	echo 'آخرین مرحلهٔ کامل‌شده: ' . $LAST . "\n";
	echo 'زمان کل: ' . round( ( microtime( true ) - $T0 ) * 1000, 1 ) . "ms\n";
	echo 'حداکثر حافظه: ' . round( memory_get_peak_usage( true ) / 1048576, 1 ) . "MB\n";
	if ( $e ) {
		echo 'خطای PHP: ' . $e['message'] . "\n";
		echo '  در ' . basename( (string) $e['file'] ) . ':' . $e['line'] . "\n";
	}
	if ( function_exists( 'error_get_last' ) && ! $e ) {
		echo "بدون خطای PHP.\n";
	}
	echo "اگر این خطوط را می‌بینید، صفحه تا آخر رسید.\n";
	echo "==================================================\n";
} );

say( '=== کاوشگرِ قفلِ صفحهٔ اصلی / mode=' . $MODE . ' ===' );
say( 'PHP ' . PHP_VERSION . ' | ' . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '?' ) );
say( 'output_buffering=' . var_export( ini_get( 'output_buffering' ), true ) . ' | ob_level=' . ob_get_level() );
say( '' );

$root = dirname( __FILE__ );
if ( ! file_exists( $root . '/wp-load.php' ) ) {
	say( 'wp-load.php نیست.' );
	exit;
}

/* ==================================================================
 * بوت
 * ================================================================== */
say( '--- بوتِ وردپرس ---' );
$t_boot = microtime( true );
define( 'WP_USE_THEMES', true );   /* مسیرِ واقعیِ قالب */

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
if ( $php_errors ) {
	say( 'اخطارهای PHP: ' . count( $php_errors ) . ' مورد' );
	foreach ( array_slice( $php_errors, 0, 6 ) as $pe ) {
		say( '  ' . $pe );
	}
}
say( '' );

/* ==================================================================
 * حالت B: اندازه‌گیریِ چیزهایی که می‌توانند روی ۶۰۰۰ نوشته کند باشند
 * ================================================================== */
if ( 'b' === $MODE ) {
	say( '--- mode=b: کاوشِ کندی ---' );

	/* ۱) options autoload — روی سایت‌های قدیمی گاهی مگابایت می‌شود */
	$t = microtime( true );
	$n = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE autoload='yes'" );
	$sz = (int) $wpdb->get_var( "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload='yes'" );
	say( 'options autoload: ' . $n . ' ردیف، ' . number_format( $sz ) . ' بایت (' . el( $t ) . 'ms)' );
	if ( $sz > 500000 ) {
		say( '  ⚠ بیشتر از ۵۰۰KB — هر درخواست این را می‌خواند' );
		/* ۱۰ تای بزرگ */
		$big = $wpdb->get_results( "SELECT option_name, LENGTH(option_value) AS l FROM {$wpdb->options} WHERE autoload='yes' ORDER BY l DESC LIMIT 10" );
		foreach ( $big as $b ) {
			say( '    ' . str_pad( $b->option_name, 45 ) . number_format( (int) $b->l ) . ' بایت' );
		}
	}

	/* ۲) transient های منقضی‌نشده */
	$t = microtime( true );
	$tr = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%'" );
	$trs = (int) $wpdb->get_var( "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%'" );
	say( 'transient ها: ' . $tr . ' ردیف، ' . number_format( $trs ) . ' بایت (' . el( $t ) . 'ms)' );

	/* ۳) postmeta — با ۶۰۶۷ آهنگ اینجا خیلی شلوغ می‌شود */
	$t = microtime( true );
	$pm = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta}" );
	say( 'postmeta: ' . number_format( $pm ) . ' ردیف (' . el( $t ) . 'ms)' );

	/* ۴) term_relationships */
	$t = microtime( true );
	$trn = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_relationships}" );
	say( 'term_relationships: ' . number_format( $trn ) . ' ردیف (' . el( $t ) . 'ms)' );

	/* ۵) آیا جدول‌ها ایندکس دارند؟ (نبودش روی ۶۰۰۰ نوشته فاجعه است) */
	$t = microtime( true );
	$idx = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts}" );
	$keys = array();
	foreach ( $idx as $r ) {
		$keys[ $r->Key_name ] = isset( $keys[ $r->Key_name ] ) ? $keys[ $r->Key_name ] + 1 : 1;
	}
	say( 'ایندکس‌های wp_posts: ' . implode( ', ', array_keys( $keys ) ) . ' (' . el( $t ) . 'ms)' );
	if ( ! isset( $keys['type_status_date'] ) ) {
		say( '  ⚠ ایندکس type_status_date نیست — کوئریِ خانه کند می‌شود' );
	}

	/* ۶) object cache خارجی */
	say( 'object cache خارجی: ' . ( wp_using_ext_object_cache() ? 'دارد' : 'ندارد (داخلی)' ) );

	/* ۷) cron — روی بعضی سایت‌ها هر بازدید یک cron سنگین اجرا می‌کند */
	$cron = get_option( 'cron' );
	$due  = 0;
	if ( is_array( $cron ) ) {
		$now = time();
		foreach ( $cron as $ts => $hooks ) {
			if ( 'version' === $ts ) {
				continue;
			}
			if ( (int) $ts <= $now ) {
				$due += is_array( $hooks ) ? count( $hooks ) : 0;
			}
		}
		say( 'cron های سررسیده: ' . $due );
		if ( $due > 0 ) {
			say( '  ⚠ روی هر بازدید اجرا می‌شوند — می‌تواند صفحه را قفل کند' );
		}
	}

	/* ۸) جدول‌های بزرگ */
	$t = microtime( true );
	$all = $wpdb->get_col( 'SHOW TABLES' );
	$big2 = array();
	foreach ( $all as $tb ) {
		$c = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$tb`" );
		if ( $c > 20000 ) {
			$big2[ $tb ] = $c;
		}
	}
	arsort( $big2 );
	say( 'جدول‌های بالای ۲۰٬۰۰۰ ردیف (' . el( $t ) . 'ms):' );
	foreach ( array_slice( $big2, 0, 15, true ) as $tb => $c ) {
		say( '  ' . str_pad( $tb, 46 ) . number_format( $c ) );
	}
	if ( ! $big2 ) {
		say( '  (هیچ)' );
	}

	say( '' );
	say( '=== پایان mode=b ===' );
	exit;
}

/* ==================================================================
 * حالت A: مسیرِ واقعی با زمان‌سنجیِ هر کلوزر
 * ================================================================== */
say( '--- mode=a: مسیرِ واقعی ---' );

/**
 * هر کلوزر را با پوششی عوض می‌کند که *قبل* از اجرا چاپ می‌کند.
 * اگر داخل کلوزر قفل شود، آخرین خط می‌گوید کدام بوده.
 */
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
					$r    = call_user_func_array( $orig, $args );
					say( '    ✔ ' . el( $t ) . 'ms' );
					return $r;
				};
				$n++;
			}
		}
	}
	return $n;
}

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

$WATCH = array( 'parse_query', 'wp', 'template_redirect', 'wp_head', 'wp_enqueue_scripts', 'wp_footer' );
$nw    = ty_wrap( $WATCH );
say( $nw . ' کلوزر روی ' . count( $WATCH ) . ' هوک پوشش داده شد.' );
say( '' );

/* --- اجرای main --- */
say( 'WP::main() …' );
$t = microtime( true );
try {
	$wp = new WP();
	$wp->main();
	say( 'main() تمام شد: ' . el( $t ) . 'ms');
} catch ( Throwable $e ) {
	say( 'FATAL در main(): ' . $e->getMessage() );
	say( '  در ' . basename( $e->getFile() ) . ':' . $e->getLine() );
	exit;
}

global $wp_query;
say( 'main query: ' . ( $wp_query ? (int) $wp_query->found_posts : '?' ) . ' نتیجه' );
say( 'is_home=' . ( is_home() ? '1' : '0' ) . ' is_front_page=' . ( is_front_page() ? '1' : '0' ) . ' is_404=' . ( is_404() ? '1' : '0' ) );
say( 'پرسش تا اینجا: ' . (int) $wpdb->num_queries );
say( '' );

/* --- اجرای template-loader با بافرِ خودمان --- */
say( 'template-loader …' );
$t2  = microtime( true );
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
$html = ob_get_clean();

say( 'رندر تمام شد: ' . strlen( $html ) . ' بایت در ' . el( $t2 ) . 'ms' );
say( '  </html> : ' . ( false !== stripos( $html, '</html>' ) ? 'بله' : 'نه' ) );
say( '  </head> : ' . ( false !== stripos( $html, '</head>' ) ? 'بله' : 'نه' ) );
say( '  <header : ' . ( false !== stripos( $html, '<header' ) ? 'بله' : 'نه' ) );
say( '' );
say( '--- خلاصه ---' );
say( 'بوت:           ' . el( $t_boot ) . 'ms' );
say( 'کل پرسش‌ها:    ' . (int) $wpdb->num_queries );
say( 'زمان کل:       ' . el( $T0 ) . 'ms' );
say( 'حافظهٔ اوج:     ' . round( memory_get_peak_usage( true ) / 1048576, 1 ) . 'MB' );
say( '' );
say( '=== پایان mode=a ===' );
