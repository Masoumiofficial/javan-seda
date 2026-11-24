<?php
/**
 * ترانه‌یار — کاوشگرِ ریدایرکت (v3.17.5)
 *
 * چرا؟
 *   سایت شما ۶۰۶۷ آهنگ دارد و آدرس‌ها با اسلاگ فارسی ثبت شده‌اند.
 *   اگر «آدرسِ درخواستی» با «آدرسِ متعارف» یکی نباشد، redirect_canonical
 *   درخواست را ریدایرکت می‌کند. اگر آن ریدایرکت به خودش برگردد،
 *   مرورگر در حلقهٔ بی‌پایان می‌مانَد و صفحه هیچ‌وقت باز نمی‌شود —
 *   دقیقاً همان «لودینگ ابدی» که می‌بینید.
 *
 * این فایل آن را با چند آزمونِ مستقل بررسی می‌کند.
 *
 * استفاده:
 *     https://ahangban.ir/tara-redirect-probe.php?key=taranehyar
 *
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
while ( ob_get_level() > 0 ) {
	@ob_end_flush();
}
ob_implicit_flush( true );

header( 'Content-Type: text/plain; charset=utf-8' );
header( 'X-Robots-Tag: noindex, nofollow' );
header( 'Cache-Control: no-store' );

$T0 = microtime( true );
function say( $m ) {
	global $T0;
	echo '[' . str_pad( round( ( microtime( true ) - $T0 ) * 1000, 1 ), 8, ' ', STR_PAD_LEFT ) . 'ms] ' . $m . "\n";
	@flush();
}

say( '=== کاوشگرِ ریدایرکت ===' );
say( 'PHP ' . PHP_VERSION );
say( '' );

$root = dirname( __FILE__ );
define( 'WP_USE_THEMES', false );
require_once $root . '/wp-load.php';

say( '--- تنظیماتِ آدرس ---' );
say( 'home       : ' . home_url( '/' ) );
say( 'siteurl    : ' . site_url( '/' ) );
say( 'HOME ثابت  : ' . ( defined( 'WP_HOME' ) ? WP_HOME : '(تعریف نشده)' ) );
say( 'SITEURL ثابت: ' . ( defined( 'WP_SITEURL' ) ? WP_SITEURL : '(تعریف نشده)' ) );
say( 'permalink  : ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : '(خالی — ساده)' ) );
say( 'is_multisite: ' . ( is_multisite() ? 'بله' : 'نه' ) );
$front = get_option( 'show_on_front' );
say( 'show_on_front: ' . $front . ' | page_on_front=' . (int) get_option( 'page_on_front' ) . ' | page_for_posts=' . (int) get_option( 'page_for_posts' ) );
say( '' );

say( '--- فایل‌های .htaccess / nginx ---' );
$ht = $root . '/.htaccess';
if ( file_exists( $ht ) ) {
	$c = (string) @file_get_contents( $ht );
	say( 'اندازهٔ .htaccess: ' . strlen( $c ) . ' بایت' );
	say( 'شاملِ «RewriteBase /»: ' . ( false !== stripos( $c, 'RewriteBase' ) ? 'بله' : 'نه' ) );
	say( 'شاملِ «https»: ' . ( false !== stripos( $c, 'https' ) ? 'بله' : 'نه' ) );
	say( 'شاملِ «www»: ' . ( false !== stripos( $c, 'www' ) ? 'بله' : 'نه' ) );
	if ( false !== stripos( $c, 'www' ) && false === stripos( home_url(), 'www' ) ) {
		say( '  ⚠ .htaccess به www اشاره می‌کند ولی home_url بدون www است ← می‌تواند حلقه بسازد' );
	}
	/* سه خطِ آخر را عملاً نادیده بگیر، فقط شمارش RewriteRule */
	$nr = preg_match_all( '/^\s*RewriteRule/mi', $c );
	say( 'تعداد RewriteRule: ' . $nr );
} else {
	say( '.htaccess نیست!' );
}
say( '' );

say( '--- آیا سایت روی خودش به https ریدایرکت می‌کند؟ ---' );
say( 'HTTPS این درخواست: ' . ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ? 'روشن' : 'خاموش' ) );
if ( empty( $_SERVER['HTTPS'] ) || 'off' === $_SERVER['HTTPS'] ) {
	say( '  ⚠ این درخواست HTTP است ولی home_url با https — می‌تواند حلقهٔ ریدایرکت بسازد' );
}
say( '' );

/* ============================================================
 * آزمونِ اصلی: دقیقاً همان آدرسی را که یک بازدیدکننده می‌زند،
 * بگیر و ببین redirect_canonical چه تصمیمی می‌گیرد.
 * ============================================================ */
say( '--- آزمونِ redirect_canonical (بدون ریدایرکتِ واقعی) ---' );

/* wp_redirect را می‌بلعیم تا فقط ببینیم چه آدرسی انتخاب می‌شد */
$redirects = array();
add_filter( 'wp_redirect', function ( $location, $status ) use ( &$redirects ) {
	$redirects[] = array( 'to' => $location, 'status' => $status );
	return false; /* نگذار واقعاً ریدایرکت کند */
}, 1, 2 );

$targets = array(
	'/'                 => 'صفحهٔ خانه',
	'/page/2/'          => 'صفحهٔ دومِ آرشیو',
);

foreach ( $targets as $path => $label ) {
	$_SERVER['REQUEST_URI']    = $path;
	$_SERVER['HTTP_HOST']      = wp_parse_url( home_url(), PHP_URL_HOST );
	$_SERVER['SERVER_NAME']    = $_SERVER['HTTP_HOST'];
	$_SERVER['REQUEST_METHOD'] = 'GET';
	$_SERVER['HTTPS']          = ( 0 === strpos( home_url(), 'https' ) ) ? 'on' : '';

	$before = count( $redirects );
	$t      = microtime( true );
	$html   = '';

	/* یک بوتِ تازه لازم است تا query واقعاً دوباره اجرا شود؛ ولی
	   دوباره require کردن wp-load ممکن نیست. پس فقط main query را
	   دوباره می‌زنیم و template_redirect را مستقیم صدا می‌زنیم. */
	global $wp, $wp_query, $wp_the_query;
	$wp->parse_request( $wp->request );
	$wp->send_headers();
	$wp->query_posts();
	$wp->handle_404();
	$wp->register_globals();

	$qms = round( ( microtime( true ) - $t ) * 1000, 1 );

	$lvl = ob_get_level();
	ob_start();
	try {
		do_action( 'template_redirect' );
	} catch ( Throwable $e ) {
		while ( ob_get_level() > $lvl ) {
			ob_end_clean();
		}
		say( '  خطا در template_redirect: ' . $e->getMessage() );
	}
	$body = ob_get_clean();

	$n = count( $redirects ) - $before;
	say( $label . ' (' . $path . '):' );
	say( '  کوئری: ' . $qms . 'ms | بدنه: ' . strlen( $body ) . ' بایت | ریدایرکت: ' . $n );
	if ( $n > 0 ) {
		foreach ( array_slice( $redirects, $before ) as $r ) {
			say( '    → ' . $r['status'] . ' ' . $r['to'] );
			/* حلقه؟ */
			$to_path = (string) wp_parse_url( $r['to'], PHP_URL_PATH );
			if ( $to_path === $path ) {
				say( '    ⚠⚠ حلقه! به همان آدرس برمی‌گردد ← علتِ لودینگ ابدی همین است' );
			}
		}
	}
}

say( '' );
say( '--- آیا قالب چیزی روی wp_redirect دارد؟ ---' );
global $wp_filter;
if ( isset( $wp_filter['wp_redirect'] ) ) {
	$n = 0;
	foreach ( $wp_filter['wp_redirect']->callbacks as $prio => $cbs ) {
		foreach ( $cbs as $name => $cb ) {
			$f = $cb['function'];
			$lbl = is_string( $f ) ? $f : ( is_array( $f ) ? implode( '::', array_map( 'strval', $f ) ) : 'closure' );
			say( '  [' . $prio . '] ' . $lbl );
			$n++;
		}
	}
	if ( ! $n ) {
		say( '  (هیچ)' );
	}
}

say( '' );
say( '=== پایان ===' );
say( 'زمان کل: ' . round( ( microtime( true ) - $T0 ) * 1000, 1 ) . 'ms' );
