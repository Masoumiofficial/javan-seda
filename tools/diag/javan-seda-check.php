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
 *     https://سایت شما/wp-content/javan-seda-check.php?admin=1&route=/artists/   ← اجرای یک صفحۀ مشخص و چاپِ خطای همان صفحه
 *   خروجی را برای ما بفرستید و سپس همین فایل را پاک کنید.
 *
 * بعد از اتمام کار پاکش کنید (این فایل عمداً هیچ دادهٔ حساسی چاپ نمی‌کند:
 * نه رمز، نه نام کاربری دیتابیس — فقط نسخه‌ها، مسیرها و متنِ خطاها).
 */

if ( ! function_exists( 'javan_seda_check_out' ) ) {
	/** یک بلوکِ خروجی، بلافاصله flush می‌شود تا اگر بعدش fatal داد، متن نپرد */
	function javan_seda_check_out( $title, $body = '' ) {
		if ( ! empty( $GLOBALS['javan_seda_check_raw'] ) ) {
			echo '== ' . $title . ' ==' . "\n" . ( '' !== $body ? $body . "\n" : '' );
			return;
		}
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

$GLOBALS['javan_seda_check_raw'] = ( isset( $_GET['raw'] ) && '1' === (string) $_GET['raw'] );

@ini_set( 'display_errors', '1' );
@error_reporting( E_ALL );
header( 'Content-Type: text/html; charset=utf-8' );
header( 'Cache-Control: no-store' );
if ( empty( $GLOBALS['javan_seda_check_raw'] ) ) {
echo '<!doctype html><html dir="ltr"><head><meta charset="utf-8"><title>javan-seda check</title>'
	. '<style>body{font:14px/1.7 tahoma,sans-serif;max-width:900px;margin:24px auto;padding:0 14px}'
	. 'h1{font-size:19px}h2{font-size:15px;margin:22px 0 6px}pre{background:#f4f4f4;padding:10px;'
	. 'white-space:pre-wrap;word-break:break-word;border-left:3px solid #185ADB;font:12px/1.6 monospace;direction:ltr}'
	. '.bad{color:#b00}.ok{color:#080}</style></head><body>';
echo '<h1>جوان‌سدا — چراغ‌قوهٔ عیب‌یابی</h1>';
}

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

/* ---------- ۱-پ) PHP و لودرِ لایسنس (مهم‌ترین بخش وقتی سایت با «corrupted» می‌میرد) ---------- */
$lic = array();
$lic[] = 'PHP: ' . PHP_VERSION . '  (PHP_VERSION_ID=' . PHP_VERSION_ID . ')  ·  ' . php_uname( 's' );
$lic[] = 'ionCube Loader: ' . ( extension_loaded( 'ionCube Loader' )
	? 'بارگذاری شده · نسخه ' . ( phpversion( 'ionCube Loader' ) ? phpversion( 'ionCube Loader' ) : '?' )
	: 'بارگذاری نشده ✗' );
$jv_sim = array();
$jv_ext = function_exists( 'get_loaded_extensions' ) ? @get_loaded_extensions() : array();
if ( is_array( $jv_ext ) ) {
	foreach ( $jv_ext as $jv_e ) {
		if ( false !== stripos( $jv_e, 'ioncube' ) || false !== stripos( $jv_e, 'ixed' ) || false !== stripos( $jv_e, 'guard' ) ) {
			$jv_sim[] = $jv_e . ( phpversion( $jv_e ) ? ' ' . phpversion( $jv_e ) : '' );
		}
	}
}
$lic[] = 'افزونۀ رمزگشا: ' . ( $jv_sim ? implode( ' · ', $jv_sim ) : 'هیچ (نه ionCube، نه ixed، نه sourceGuard)' );
$lic[] = 'php.ini: ' . ( php_ini_loaded_file() ? php_ini_loaded_file() : '?' );
/* خودِ سطرِ اولِ فایلِ رمزدار این معنی را می‌دهد:
 *   if(extension_loaded('ionCube Loader')){die('The file … is corrupted.');}
 * پس «corrupted» فقط وقتی ظاهر می‌شود که لودر **حاضر** است ولی فایل را decode
 * نکرده — یعنی بایت‌های فایلِ روی سرور با چیزی که لودر انتظار دارد نمی‌خواند
 * (بازنویسیِ خط‌شکن یا truncate‌شدن در transfer، یا buildِ نامتقابل با نسخهٔ PHP).
 * اگر لودر اصلاً نصب نبود، پیامِ دیگری می‌دادید: «ionCube Loader … needs to be installed». */
$jv_ref = array(
	'functions.php'                      => array( 95498, 'd99663ca6df0b6d8' ),
	'RTL_License_41d6c5f704e3a746.php'    => array( 64615, '8e4e2975b4c9df88' ),
);
foreach ( $jv_ref as $jv_lf => $jv_expect ) {
	$jv_p = ( isset( $theme ) && $theme ? $theme : dirname( __FILE__ ) ) . '/' . $jv_lf;
	if ( ! is_file( $jv_p ) ) {
		$lic[] = $jv_lf . ': پیدا نشد ⚠';
		continue;
	}
	$jv_raw = (string) @file_get_contents( $jv_p );
	$jv_sz  = strlen( $jv_raw );
	$jv_sha = (string) @hash( 'sha256', $jv_raw );
	$jv_crlf = substr_count( $jv_raw, "\r\n" );
	$jv_lf_n = substr_count( $jv_raw, "\n" );
	$jv_enc  = ( $jv_crlf === $jv_lf_n && $jv_crlf > 0 ) ? 'CRLF × ' . $jv_crlf
		: ( ( 0 === $jv_crlf ) ? 'LF × ' . $jv_lf_n : '⚠ خط‌شکنِ مخلوط (' . $jv_crlf . ' CRLF / ' . $jv_lf_n . " LF)" );
	$lic[] = sprintf(
		"%s\n    اندازه %d B · sha256 %s…\n    هدرِ رمز: %s · انتها: %s · خط‌شکن: %s\n    نسبت به کپیِ مخزن: %s",
		$jv_lf,
		$jv_sz,
		substr( $jv_sha, 0, 20 ),
		( false !== strpos( substr( $jv_raw, 0, 200 ), 'ICB0' ) ) ? 'ICB0 ✓' : 'ICB0 ✗ (رمزگذاری‌نشده!)',
		( "\n" === substr( $jv_raw, -1 ) ? 'newline دارد' : 'بدونِ newlineِ انتهایی' ),
		$jv_enc,
		( $jv_sz === $jv_expect[0] && 0 === strpos( $jv_sha, $jv_expect[1] ) )
			? 'دقیقاً همان بایت‌های مخزن (يعنی بسته‌های کاملِ قالب جایگزینش کرده‌اند)'
			: '✦ با کپیِ مخزن فرق دارد ← ' . $jv_expect[0] . ' B / ' . $jv_expect[1] . '… (مخزن)'
	);
}
$lic[] = '⚠ نکتهٔ مهم: کپیِ «مخزن» دلیلِ سلامت نیست. این فایل از یک بارگذاریِ دستی وارد گیت‌هاب شده و ما هیچ‌وقت آن را با فایلِ اصلیِ rtl-theme.com مقایسه نکرده‌ایم. تا نسخۀ ۳.۴.۸ پوسته فقط به‌صورت drop-in نصب می‌شد (بدونِ functions.php) و فایلِ لایسنسِ اصلیِ سرور دست‌نخورده می‌ماند؛ از ۳.۴.۹ بستهٔ کاملِ قالب این فایل را هم بازنویسی کرد. اگر «corrupted» از همان اولِ نصبِ بستهٔ کامل شروع شد، درمانش **برگرداندنِ فایلِ اصلیِ خودِ فروشنده** است (از فایلِ دانلودیِ rtl-theme.com، نه از بکاپِ بعد از ۳.۴.۹) و از این پس فقط drop-in نصب شود.';

if ( PHP_VERSION_ID >= 80300 ) {
	$lic[] = 'نتیجه (۱): PHP 8.3/8.4 است. لودرِ ionCube نسخه‌به‌نسخه است؛ اگر برای این نسخۀ PHP لودرِ سازگار نصب نباشد، decode انجام نمی‌شود و همان die() اجرا می‌شود. ⇒ یا PHP را روی 8.1/8.2 بگذارید، یا لودرِ ionCubeِ همان نسخه را از هاست بخواهید.';
	$lic[] = 'نتیجه (۲): اگر نسخۀ PHP را عوض کردید و خطا همان‌جا ماند، علتش بایت‌های فایل است نه PHP — بخشِ بالا را ببینید.';
	$lic[] = 'نتیجه (۳): هیچ دو سطر بالا را با هم عوض نکنید؛ یکی‌یکی تست کنید تا معلوم شود کدام رفع کرده است.';
} elseif ( PHP_VERSION_ID >= 80000 ) {
	$lic[] = 'نتیجه: PHP 8.0/8.1 — معمولاً همین بازه برای این فایل‌ها درست کار می‌کند. اگر همچنان «corrupted» است، یا لودرِ همسان فعال نیست یا فایلِ روی سرور با فایلِ لایسنسِ خودِ سرور هم‌خوانی ندارد (بکاپِ همان سرور را برگردانید، نه کپیِ مخزن).';
} else {
	$lic[] = 'نتیجه: PHP 5/7 — بازۀ امنِ فایل‌های لایسنسِ این قالب.';
}
javan_seda_check_out( 'PHP، لودرِ لایسنس و فایل‌های رمزگذاری‌شده', implode( "
", $lic ) );

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

/* ---------- ۶-پ) اجرای یک مسیرِ واقعی (?admin=1&route=/artists/) ----------
 * تا وقتی خطا با `die()` تمام شود، هیچ لاگی روی سرور نمی‌ماند. این حالت همان
 * مسیری که شما در مرورگر می‌زنید را روی همین سرور اجرا می‌کند (با همان PHP،
 * همان افزونه‌ها، همان پوسته) و فقط «چرا نمرندر شد» را چاپ می‌کند: متنِ خطای
 * مرگبار با فایل و شمارهٔ سطر، وضعیتِ شرطی‌ها، و نتیجهٔ کوئری.
 * نمونه:  javan-seda-check.php?admin=1&route=/artists/
 *          javan-seda-check.php?admin=1&route=/artist/mohsen-yeganeh/&raw=1
 * -------------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_route_report' ) ) {
	/** گزارشِ حالتِ «مسیر» — هم از پایانِ اسکریپت و هم از shutdown صدا زده می‌شود */
	function javan_seda_route_report() {
		if ( ! empty( $GLOBALS['javan_seda_route_done'] ) ) {
			return;
		}
		$GLOBALS['javan_seda_route_done'] = true;

		$lines = array();
		$e     = function_exists( 'error_get_last' ) ? error_get_last() : null;
		if ( is_array( $e ) && in_array( (int) $e['type'], array( 1, 2, 4, 8, 16, 32, 64, 4096, 8192 ), true ) ) {
			$lines[] = 'خطا (' . (int) $e['type'] . '): ' . $e['message'];
			$lines[] = 'در: ' . ( isset( $e['file'] ) ? $e['file'] : '?' ) . ' : سطر ' . ( isset( $e['line'] ) ? (int) $e['line'] : '?' );
			$f = isset( $e['file'] ) ? $e['file'] : '';
			if ( '' !== $f && is_readable( $f ) && ! empty( $e['line'] ) ) {
				$head = @file_get_contents( $f, false, null, 0, 400 );
				if ( false === $head || false === strpos( $head, 'ICB0' ) ) { // از فایلِ رمزدار سطر چاپ نمی‌کنیم
					$all   = file( $f );
					$from  = max( 0, (int) $e['line'] - 3 );
					$to    = min( count( $all ), $from + 5 );
					$snip  = array();
					for ( $i = $from; $i < $to; $i++ ) {
						$snip[] = '    ' . ( $i + 1 ) . ' | ' . trim( (string) $all[ $i ] );
					}
					$lines[] = "سطرهای اطرافِ خطا:\n" . implode( "\n", $snip );
				}
			}
			if ( preg_match( '#public_html/wp-content/([^:"]+)#', $e['message'], $m ) ) {
				$lines[] = 'مسیرِ مقصر (نسبی): wp-content/' . $m[1];
			}
		} else {
			$lines[] = 'هیچ خطای مرگباری برای این مسیر ثبت نشد.';
		}
		if ( ! empty( $GLOBALS['javan_seda_route_lines'] ) ) {
			$lines = array_merge( $lines, (array) $GLOBALS['javan_seda_route_lines'] );
		}
		$lines[] = '—— اگر «خطا» در فایل‌های خودِ پوسته (inc/ab-*.php یا myfunctions.php) است، مسیر و شمارهٔ سطر را برای ما بفرستید. ——';

		javan_seda_check_out( 'نتیجهٔ اجرای مسیر: ' . ( isset( $GLOBALS['javan_seda_route'] ) ? $GLOBALS['javan_seda_route'] : '?' ), implode( "\n", $lines ) );

		if ( empty( $GLOBALS['javan_seda_check_raw'] ) ) {
			echo '</body></html>';
		}
	}
}

if ( $admin_mode && isset( $_GET['route'] ) && '' !== trim( (string) $_GET['route'] ) ) {

	$route = trim( (string) $_GET['route'] );
	if ( '/' !== substr( $route, 0, 1 ) ) {
		$route = '/' . $route;
	}

	$GLOBALS['javan_seda_route']       = $route;
	$GLOBALS['javan_seda_route_lines'] = array();
	$GLOBALS['javan_seda_check_dead']  = true; // گزارشِ این بخش خودش چاپ می‌شود
	register_shutdown_function( 'javan_seda_route_report' );

	javan_seda_check_out( 'اجرای مسیر: ' . $route, 'در حال بالا آوردنِ همان درخواست… (بدنۀ HTML چاپ نمی‌شود)' );

	$qpos                    = strpos( $route, '?' );
	$_SERVER['REQUEST_URI']  = $route;
	$_SERVER['QUERY_STRING'] = ( false === $qpos ) ? '' : substr( $route, $qpos + 1 );

	/* پارامترهای خودِ پروب نباید به درخواستِ واقعی برسند */
	foreach ( array( 'route', 'admin', 'full', 'raw', 'p', 'page_id', 'post_type', 'taxonomy', 'term', 'name' ) as $k ) {
		unset( $_GET[ $k ], $_POST[ $k ] );
	}

	ob_start();
	if ( function_exists( 'wp' ) ) {
		@wp();
	}
	$loader = $root . '/wp-includes/template-loader.php';
	if ( is_readable( $loader ) ) {
		@require_once $loader;   // خودِ وردپرس do_action('template_redirect') را اینجا می‌زند
	}
	$buf = ob_get_contents();
	ob_end_clean();

	$len           = strlen( (string) $buf );
	$rl            = &$GLOBALS['javan_seda_route_lines'];
	$rl[]          = 'طول بدنهٔ پاسخ: ' . $len . ' بایت' . ( $len < 700 ? '  ← صفحه عملاً رندر نشده' : '' );
	if ( false !== stripos( (string) $buf, 'is corrupted' ) ) {
		$rl[] = 'در بدنه: «is corrupted» ⇒ functions.php هنوز decode نمی‌شود؛ نسخۀ PHP هاست را پایین بیاورید (راجع به این فایل، بخشِ «PHP و لودرِ لایسنس» را ببینید).';
	}
	if ( false !== stripos( (string) $buf, 'There has been a critical error' ) || false !== stripos( (string) $buf, 'خطای مهم' ) ) {
		$rl[] = 'در بدنه: پیامِ «خطای مهم وردپرس» — یعنی WP_Recovery_Mode آن را گرفته؛ متنِ اصلی در «خطا»ی همین گزارش است.';
	}
	if ( function_exists( 'is_404' ) ) {
		$rl[] = 'شرطی‌ها: is_404=' . ( is_404() ? 'بله' : 'نه' ) . ' · is_archive=' . ( is_archive() ? 'بله' : 'نه' )
			. ' · is_tax=' . ( is_tax() ? 'بله' : 'نه' ) . ' · is_singular=' . ( is_singular() ? 'بله' : 'نه' )
			. ' · is_home=' . ( is_home() ? 'بله' : 'نه' ) . ' · is_search=' . ( is_search() ? 'بله' : 'نه' );
	}
	if ( function_exists( 'get_queried_object' ) ) {
		$o = get_queried_object();
		if ( is_object( $o ) && isset( $o->taxonomy ) ) {
			$rl[] = 'ترمِ درخواستی: ' . $o->taxonomy . '/' . ( isset( $o->slug ) ? $o->slug : '?' ) . ' (id ' . ( isset( $o->term_id ) ? (int) $o->term_id : 0 ) . ')'
				. ' · count=' . ( isset( $o->count ) ? (int) $o->count : '?' );
		} elseif ( is_object( $o ) && isset( $o->post_type ) ) {
			$rl[] = 'نوشتهٔ درخواستی: ' . $o->post_type . '/' . ( isset( $o->post_name ) ? $o->post_name : '?' );
		} else {
			$rl[] = 'شیءِ درخواستی: هیچ (یعنی مسیر به هیچ نوشته/ترمی نگاشت نشد ←_rewrite/فلشِ پیوندها)';
		}
	}
	if ( isset( $GLOBALS['wp_query'] ) && is_object( $GLOBALS['wp_query'] ) ) {
		$q = $GLOBALS['wp_query'];
		$rl[] = 'کوئری: found_posts=' . (int) $q->found_posts . ' · posts=' . count( (array) $q->posts )
			. ' · max_num_pages=' . (int) $q->max_num_pages . ' · is_main=' . ( $q->is_main_query() ? 'بله' : 'نه' );
		if ( ! empty( $q->request ) ) {
			$rl[] = 'SQL: ' . mb_cut( preg_replace( '/\s+/', ' ', (string) $q->request ), 320 );
		}
	}
	$rl[] = 'حافظۀ مصرفی: ' . round( memory_get_usage( true ) / 1048576, 1 ) . ' MB از ' . ini_get( 'memory_limit' );

	javan_seda_route_report();
	return;
}

/* ---------- ۷) اجرای تمام‌قدِ داشبورد (?admin=1&full=1) ---------- */
if ( $admin_mode && isset( $_GET['full'] ) && '1' === (string) $_GET['full'] && is_file( $root . '/wp-admin/admin.php' ) ) {
	javan_seda_check_out( 'اجرای واقعیِ wp-admin/admin.php (داشبورد)…', 'اگر خطا همین‌جا رخ بدهد، دقیقاً همان خطای پیشخوان است.' );
	require $root . '/wp-admin/admin.php';
	echo '<pre class="ok">داشبورد هم بدون خطا اجرا شد ✔ — پس خطا یا به صفحۀ دیگری از پیشخوان است یا به یک افزونه.</pre>';
}

$GLOBALS['javan_seda_check_dead'] = true;
echo '<p><strong>هیچ خطای مرگباری رخ نداد.</strong> اگر در حالت <code>?admin=1</code> هم همین پیام را دیدید، یعنی خطا در کدِ پوسته نیست (احتمالاً یک افزونه یا صفحۀ خاصِ پیشخوان) — در این صورت بگویید کدام صفحۀ پیشخوان خطا می‌دهد.</p>';
echo '<p>خروجی را برای ما بفرستید و این فایل را پاک کنید.</p></body></html>';
