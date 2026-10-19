<?php
/**
 * جاوه‌سدا — چراغ‌قوهٔ عیب‌یابی (javan-seda-check.php)
 * ---------------------------------------------------------------------------
 * این فایل بخشی از پوسته نیست و هیچ چیزی را عوض نمی‌کند. کاری که می‌کند:
 *   ۱) اطلاعاتِ محیط (نسخۀ PHP، حافظه، wp-content، فایل‌های پوسته) را چاپ می‌کند
 *   ۲) لاگ‌های موجود را از دیسک می‌خواند و دامنۀ آخرشان را نشان می‌دهد
 *      (debug.log حتی اگر با .htaccess بسته شده باشد، از اینجا خوانده می‌شود)
 *   ۳) وردپرس را بالا می‌آورد؛ اگر همان‌جا خطای مرگباری باشد، فایل/سطر/پیامش را
 *      قبل از مرگِ صفحه چاپ می‌کند ⇒ همان چیزی که لازم داریم.
 *
 * روش:
 *   این فایل را در wp-content/ بگذارید، بعد در مرورگر باز کنید:
 *     https://سایت شما/wp-content/javan-seda-check.php            ← حالت عادی
 *     https://سایت شما/wp-content/javan-seda-check.php?admin=1    ← حالت پیشخوان
 *     https://سایت شما/wp-content/javan-seda-check.php?admin=1&full=1     ← اجرای واقعیِ داشبورد
 *   خروجی را برای ما بفرستید و سپس همین فایل را پاک کنید.
 *
 * بعد از اتمام کار پاکش کنید (این فایل عمداً هیچ دادهٔ حساسی چاپ نمی‌کند:
 * نه رمز، نه نام کاربری دیتابیس — فقط نسخه‌ها، مسیرها و متنِ خطاها).
 */

if ( ! function_exists( 'javan_seda_check_out' ) ) {
	/** یک بلوکِ خروجی، بلافاصله flush می‌شود تا اگر بعدش fatal داد، متن نپرد */
	function javan_seda_check_out( $title, $body = '' ) {
		echo '<h2>' . htmlspecialchars( $title ) . '</h2>';
		if ( '' !== $body ) {
			echo '<pre>' . htmlspecialchars( $body ) . '</pre>';
		}
		@flush();
		if ( function_exists( 'ob_flush' ) ) {
			@ob_flush();
		}
	}
}

if ( ! function_exists( 'javan_seda_check_tail' ) ) {
	/** چند سطرِ آخرِ یک فایل (برای لاگ‌های بزرگ) */
	function javan_seda_check_tail( $file, $lines = 40 ) {
		if ( ! is_file( $file ) || ! is_readable( $file ) ) {
			return null;
		}
		$fp = @fopen( $file, 'rb' );
		if ( ! $fp ) {
			return '(خوانده نشد)';
		}
		$out = array();
		$data = '';
		$size = (int) filesize( $file );
		$max  = 262144; // فقط ۲۵۶KB آخر
		if ( $size > $max ) {
			fseek( $fp, $size - $max, SEEK_SET );
		}
		while ( ! feof( $fp ) ) {
			$chunk = fread( $fp, 8192 );
			if ( false === $chunk ) {
				break;
			}
			$data .= $chunk;
		}
		fclose( $fp );
		$all   = preg_split( '/\r\n|\r|\n/', $data );
		$all   = array_slice( $all, -1 * (int) $lines );
		$out[] = '(حجم فایل: ' . $size . ' بایت — ' . count( $all ) . ' سطرِ آخر)';
		foreach ( $all as $l ) {
			if ( '' === trim( $l ) ) {
				continue;
			}
			$out[] = mb_cut( $l, 400 );
		}
		return implode( "\n", $out );
	}
}

if ( ! function_exists( 'mb_cut' ) ) {
	/** برشِ امنِ رشته بدون وابستگی به mbstring */
	function mb_cut( $s, $n ) {
		$s = (string) $s;
		if ( strlen( $s ) <= $n ) {
			return $s;
		}
		return substr( $s, 0, $n ) . ' …';
	}
}

@ini_set( 'display_errors', '1' );
@error_reporting( E_ALL );
header( 'Content-Type: text/html; charset=utf-8' );
header( 'Cache-Control: no-store' );
echo '<!doctype html><html dir="ltr"><head><meta charset="utf-8"><title>javan-seda check</title>'
	. '<style>body{font:14px/1.7 tahoma,sans-serif;max-width:900px;margin:24px auto;padding:0 14px}'
	. 'h1{font-size:19px}h2{font-size:15px;margin:22px 0 6px}pre{background:#f4f4f4;padding:10px;'
	. 'white-space:pre-wrap;word-break:break-word;border-left:3px solid #185ADB;font:12px/1.6 monospace;direction:ltr}'
	. '.bad{color:#b00}.ok{color:#080}</style></head><body>';
echo '<h1>جوان‌سدا — چراغ‌قوهٔ عیب‌یابی</h1>';

$admin_mode = ( isset( $_GET['admin'] ) && '1' === (string) $_GET['admin'] );

/* ---------- ۱) ناظرِ fatal: هر خطای مرگباری که از این به بعد رخ بدهد چاپ می‌شود ---------- */
$GLOBALS['javan_seda_check_dead'] = false;
if ( ! function_exists( 'javan_seda_check_goodbye' ) ) {
	function javan_seda_check_goodbye() {
		if ( ! empty( $GLOBALS['javan_seda_check_dead'] ) ) {
			return;
		}
		$GLOBALS['javan_seda_check_dead'] = true;
		$e = error_get_last();
		if ( is_array( $e ) && ! empty( $e['message'] ) ) {
			echo '<h2 class="bad">خطای مرگبار گرفته شد</h2><pre>'
				. htmlspecialchars( 'نوع: ' . ( isset( $e['type'] ) ? $e['type'] : '?' ) . "\n" )
				. htmlspecialchars( 'پیام: ' . $e['message'] . "\n" )
				. htmlspecialchars( 'فایل: ' . ( isset( $e['file'] ) ? $e['file'] : '?' ) . ':' . ( isset( $e['line'] ) ? $e['line'] : '?' ) . "\n" )
				. '</pre>';
		} else {
			echo '<h2>اسکریپت نصفه تمام شد (خطای مرگباری ثبت نشده بود)</h2>'
				. '<pre>احتمالاً خروجی کامل چاپ شده و فقط PHP موقع بستنِ صفحه قطع کرده است.</pre>';
		}
		echo '<p><strong>این صفحه را برای ما بفرستید، سپس فایلِ javan-seda-check.php را پاک کنید.</strong></p></body></html>';
	}
	register_shutdown_function( 'javan_seda_check_goodbye' );
}

/* ---------- ۲) محیط ---------- */
$root = dirname( dirname( __FILE__ ) ); // اگر فایل در wp-content/ باشد ← ریشۀ وردپرس
if ( ! is_file( $root . '/wp-load.php' ) ) {
	$root = dirname( __FILE__ );
}
$theme = $root . '/wp-content/themes/javanseda';

javan_seda_check_out(
	'محیط',
	'PHP: ' . PHP_VERSION . "\n"
	. 'memory_limit: ' . ini_get( 'memory_limit' ) . '  · _used: ' . round( memory_get_usage( true ) / 1048576, 1 ) . " MB\n"
	. 'error_log: ' . ( ini_get( 'error_log' ) ? ini_get( 'error_log' ) : '(تنظیم نشده ← لاگِ سرور)' ) . "\n"
	. 'display_errors: ' . var_export( (bool) ini_get( 'display_errors' ), true ) . "\n"
	. 'ABSPATH (از پیش): ' . ( defined( 'ABSPATH' ) ? 'define شده' : 'نه' ) . "\n"
	. 'ریشۀ وردپرس: ' . $root . "\n"
	. 'پوشۀ پوسته: ' . ( is_dir( $theme ) ? $theme : 'پیدا نشد!' ) . "\n"
	. 'حالت: ' . ( $admin_mode ? 'پیشخوان (admin=1)' : 'عادی' )
);

/* ---------- ۳) فایل‌های پوسته ---------- */
$watch = array(
	'style.css',
	'myfunctions.php',
	'functions.php',
	'inc/ab-terms-ui.php',
	'inc/ab-tax-artist.php',
	'inc/ab-sidebars.php',
	'inc/ab-controls.php',
	'inc/theme-options.php',
	'css/ab-ui.css',
	'taxonomy-artist.php',
);
$lines = array();
$ver   = '(ناخوانا)';
if ( is_file( $theme . '/style.css' ) ) {
	$head = @file_get_contents( $theme . '/style.css', false, null, 0, 1200 );
	if ( $head && preg_match( '/Version:\s*([0-9.]+)/', $head, $m ) ) {
		$ver = $m[1];
	}
}
$lines[] = 'Version در style.css: ' . $ver;
foreach ( $watch as $rel ) {
	$f = $theme . '/' . $rel;
	$lines[] = sprintf(
		'%-26s %s %10s',
		$rel,
		is_file( $f ) ? 'هست  ' . date( 'Y-m-d H:i', (int) filemtime( $f ) ) : 'نیست',
		is_file( $f ) ? filesize( $f ) . 'B' : '-'
	);
}
$lines[] = 'wp-content/uploads: ' . ( is_dir( $root . '/wp-content/uploads' ) ? 'نوشته‌پذیر? ' . ( is_writable( $root . '/wp-content/uploads' ) ? 'بله' : 'نه' ) : 'نیست' );
$lines[] = 'wp-content نوشتنی: ' . ( is_writable( $root . '/wp-content' ) ? 'بله' : 'نه' );
javan_seda_check_out( 'فایل‌های پوسته (نسخهٔ واقعیِ روی سرور)', implode( "\n", $lines ) );

/* ---------- ۴) لاگ‌های موجود ---------- */
$logs = array(
	$wp_log = $root . '/wp-content/debug.log',
	$root . '/wp-content/javan-seda-error.log',
);
if ( ( $ini = ini_get( 'error_log' ) ) && '' !== $ini && is_file( $ini ) ) {
	$logs[] = $ini;
}
foreach ( $logs as $log ) {
	$body = javan_seda_check_tail( $log, 45 );
	if ( null === $body ) {
		echo '<h3>' . htmlspecialchars( basename( $log ) ) . '</h3>';
		echo '<p>— ' . htmlspecialchars( $log ) . ' : وجود ندارد یا خوانده نمی‌شود.</p>';
		continue;
	}
	javan_seda_check_out( 'آخرِ ' . basename( $log ), $body );
}

/* ---------- ۵) بالا آوردنِ وردپرس (این‌جا fatalِ واقعی لو می‌رود) ---------- */
if ( ! is_file( $root . '/wp-load.php' ) ) {
	javan_seda_check_out( 'wp-load.php پیدا نشد', 'مسیرِ آزمایش‌شده: ' . $root . '/wp-load.php — فایل را داخل wp-content/ بگذارید.' );
	$GLOBALS['javan_seda_check_dead'] = true;
	echo '</body></html>';
	return;
}
echo '<h2>بالا آوردنِ وردپرس…</h2>';
if ( $admin_mode ) {
	/** وانمود می‌کنیم در پیشخوان هستیم تا همان کدِ اجراییِ پیشخوان اجرا شود */
	define( 'WP_ADMIN', true );
	$GLOBALS['pagenow'] = 'index.php';
}
@ini_set( 'html_errors', '0' );
require $root . '/wp-load.php';
echo '<pre class="ok">وردپرس بالا آمد ✔</pre>';
if ( $admin_mode && function_exists( 'wp_set_current_user' ) ) {
	$ids = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
			'fields' => 'ID',
		)
	);
	if ( $ids ) {
		wp_set_current_user( (int) $ids[0] );
		echo '<pre>کاربرِ مدیرِ نمونه برای شبیه‌سازی بارگذاری شد (ID ' . (int) $ids[0] . ') — هیچ نوشته/گزینه‌ای تغییر نمی‌کند.</pre>';
	}
}
javan_seda_check_out(
	'وضعیت پوسته از دیدِ وردپرس',
	'stylesheet: ' . ( function_exists( 'get_stylesheet' ) ? get_stylesheet() : '?' ) . "\n"
	. 'template: ' . ( function_exists( 'get_template' ) ? get_template() : '?' ) . "\n"
	. 'Version: ' . ( function_exists( 'wp_get_theme' ) ? wp_get_theme()->get( 'Version' ) : '?' ) . "\n"
	. 'is_admin(): ' . ( function_exists( 'is_admin' ) && is_admin() ? 'true' : 'false' ) . "\n"
	. 'javan_seda_safe(): ' . ( function_exists( 'javan_seda_safe' ) ? 'هست' : 'نیست (پوستهٔ نو نصب نشده)' ) . "\n"
	. 'javan_seda_term_bio(): ' . ( function_exists( 'javan_seda_term_bio' ) ? 'هست' : 'نیست' ) . "\n"
	. 'javan_seda_artist_home_rows(): ' . ( function_exists( 'javan_seda_artist_home_rows' ) ? 'هست' : 'نیست' ) . "\n"
	. 'taxonomy artist: ' . ( taxonomy_exists( 'artist' ) ? 'ثبت شده' : 'نیست' ) . "\n"
	. 'taxonomy singer: ' . ( taxonomy_exists( 'singer' ) ? 'ثبت شده' : 'نیست' ) . "\n"
	. 'ACF (get_field): ' . ( function_exists( 'get_field' ) ? 'فعال' : 'غیرفعال' ) . "\n"
	. 'OptionTree (ot_get_option): ' . ( function_exists( 'ot_get_option' ) ? 'فعال' : 'غیرفعال' ) . "\n"
	. 'حافظۀ مصرفی در پایان: ' . round( memory_get_usage( true ) / 1048576, 1 ) . ' MB / ' . ini_get( 'memory_limit' )
);

/* ---------- ۶) آزمایشِ خودِ لایه‌ها (اگر اینجا ترکاند، معلوم است) ---------- */
$tests = array();
foreach ( array( 'javan_seda_term_tax_list', 'javan_seda_term_field_defs', 'javan_seda_artist_acf_rules', 'javan_seda_artist_home_settings' ) as $fn ) {
	if ( ! function_exists( $fn ) ) {
		$tests[] = $fn . ': تابع وجود ندارد';
		continue;
	}
	$args = ( 'javan_seda_artist_acf_rules' === $fn ) ? array( array() ) : array();
	ob_start();
	$ret    = call_user_func_array( $fn, $args );
	$err    = ob_get_clean();
	$tests[] = $fn . ' → ' . gettype( $ret ) . ( '' !== trim( $err ) ? ' (خروجیِ چاپی: ' . mb_cut( $err, 60 ) . ')' : '' );
}
if ( function_exists( 'get_terms' ) ) {
	$t = get_terms( array( 'taxonomy' => ( taxonomy_exists( 'artist' ) ? 'artist' : 'singer' ), 'hide_empty' => false, 'number' => 1 ) );
	$tests[] = 'get_terms → ' . ( is_wp_error( $t ) ? 'WP_Error: ' . $t->get_error_message() : (int) count( $t ) . ' ترم' );
}
javan_seda_check_out( 'آزمایشِ تابع‌های حساس', implode( "\n", $tests ) );

/* ---------- ۷) اجرای تمام‌قدِ داشبورد (?admin=1&full=1) ---------- */
if ( $admin_mode && isset( $_GET['full'] ) && '1' === (string) $_GET['full'] && is_file( $root . '/wp-admin/admin.php' ) ) {
	javan_seda_check_out( 'اجرای واقعیِ wp-admin/admin.php (داشبورد)…', 'اگر خطا همین‌جا رخ بدهد، دقیقاً همان خطای پیشخوان است.' );
	require $root . '/wp-admin/admin.php';
	echo '<pre class="ok">داشبورد هم بدون خطا اجرا شد ✔ — پس خطا یا به صفحۀ دیگری از پیشخوان است یا به یک افزونه.</pre>';
}

$GLOBALS['javan_seda_check_dead'] = true;
echo '<p><strong>هیچ خطای مرگباری رخ نداد.</strong> اگر در حالت <code>?admin=1</code> هم همین پیام را دیدید، یعنی خطا در کدِ پوسته نیست (احتمالاً یک افزونه یا صفحۀ خاصِ پیشخوان) — در این صورت بگویید کدام صفحۀ پیشخوان خطا می‌دهد.</p>';
echo '<p>خروجی را برای ما بفرستید و این فایل را پاک کنید.</p></body></html>';
