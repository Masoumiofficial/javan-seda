<?php
/**
 * ab-rescue.php — گزارش نجات (v3.1.6) · بدون وابستگی به وردپرس
 * -------------------------------------------------------------
 * وقتی سایت صفحۀ «یک خطای مهم در این وب سایت رخ داده است» می‌دهد، کاوشگرِ معمولی
 * (ab-mobile-probe.php) هم باز نمی‌شود — چون آن فایل wp-load.php را صدا می‌زند و
 * بارگذاری قالب دوباره روی همان خطا می‌میرد. این فایل هیچ چیزی از وردپرس load
 * نمی‌کند: فقط همین پوشه را می‌خواند. پس در حالت «سایت خوابیده» هم کار می‌کند.
 *
 * نشانی:  https://example.com/wp-content/themes/javanseda/ab-rescue.php
 * افزودن: ?scan=1  ⇒ برای هر فایل لایه، خطِ مشکوک را هم نشان می‌دهد.
 *
 * ایمنی:  کاملاً read-only است (هیچ فایل را عوض/حذف نمی‌کند و چیزی نوشته نمی‌شود)،
 * هیچ داده‌ای از دیتابیس نمی‌خواند، و فقط فایل‌های داخل همین پوشه را فهرست می‌کند.
 * بعد از حل مشکل این فایل را پاک کنید (مثل ab-mobile-probe.php).
 *
 * درمان سریع: هر لایه در myfunctions.php با is_readable() صدا زده می‌شود ⇒ اگر فایل
 *problematic را با پسوند .off تغییر نام دهید، سایت بالا می‌آید و فقط همان قابلیت خاموش می‌شود:
 *        inc/ab-perf.php  →  inc/ab-perf.php.off
 */

@ini_set( 'display_errors', '1' );
error_reporting( E_ALL );
header( 'Content-Type: text/plain; charset=utf-8' );
header( 'X-Robots-Tag: noindex, nofollow' );

$base = dirname( __FILE__ );

/** فهرست فایل‌هایی که ما به قالب اضافه کرده‌ایم (لایه‌ها) — اگر نبودند خطا نمی‌دهد. */
$layers = array(
	'myfunctions.php'           => 'خودِ فایل includeکننده (تغییرش ندهید مگر نسخهٔ تازه)',
	'inc/ab-sidebars.php'       => 'لایۀ سایدبار/ابزارک (v3.0.2)',
	'inc/acf-compat.php'        => 'سازگاری ACF (v3.1.0)',
	'inc/ab-news-ticker.php'    => 'اخبار روز (v3.1.2)',
	'inc/ab-perf.php'           => 'بایتِ سرعت (v3.1.3)',
	'inc/ab-seo.php'            => 'دیده‌شدن/سئو (v3.1.4)',
	'inc/ab-views.php'          => 'شمارش بازدید + چارت (v3.1.5)',
	'inc/ab-ratings.php'        => 'امتیاز ۵ستاره (v3.1.5)',
	'inc/mostviews_posts.php'   => 'بلوک «پربازدیدترین آثار ماه» (v3.1.5)',
	'single.php'                => 'صفحۀ آهنگ (پچ v3.1.1 تا v3.1.5)',
	'header.php'                => 'هدر (پچ v3.1.3 · فقط در صورت نیاز)',
	'style.css'                 => 'استایل (Version)',
	'ab-mobile-probe.php'       => 'کاوشگر (باید بعد از بررسی پاک شود)',
);

/* ساختارهایی که روی PHP قدیمی «خطای پارس» می‌دهند. رشته/کامنت حذف می‌شود تا خودِ
   الگوها گزارش را آلوده نکنند. */
$modern = array(
	'static closure → PHP 7.4+'       => '/\bstatic\s+function\s*\(/',
	'fn() → PHP 7.4+'                 => '/(?:^|[^\$\w>])fn\s*\(/',
	'??= → PHP 7.4+'                  => '/\?\?=/',
	'… spread → PHP 7.4+'             => '/=>\s*\.\.\.\$/',
	'?? → PHP 7.0+'                   => '/\?\?/',
	'?-> → PHP 8.0+'                  => '/\?->/',
	'match() → PHP 8.0+'              => '/\bmatch\s*\(/',
	'catch union → PHP 7.1+'          => '/catch\s*\(\s*\w+\s*\|/',
	'return type → PHP 7.0+'          => '/\)\s*:\s*(?:void|bool|string|int|float|array|self|mixed)\s*[\{;]/',
);

/* ---------------------------------------------------------------------------
   abr_split() — تفکیک دقیق کد/رشته/کامنت با token_get_all() (پشتکار: preg)
   چرا tokenizer؟ چون یک آپوستروف داخل کامنت (مثل «define( 'JAVANSEDA_SEO', false );»)
   اگر با preg متن را بشکافد، نصف فایل «ناپدید» می‌شود و توابع سالم «تعریف‌نشده»
   گزارش می‌شدند. آرایۀ بازگشتی: 0 => کد + رشته‌ها (برای دیدن نام callbackها)
   و 1 => کدِ خالصِ بدون‌رشته (برای دیدن ساختارهای نسخه‌بسته).
   ------------------------------------------------------------------------- */
if ( ! function_exists( 'abr_split' ) ) {
	function abr_split( $src ) {
		if ( function_exists( 'token_get_all' ) ) {
			$nc   = '';
			$bare = '';
			foreach ( (array) @token_get_all( $src ) as $tok ) {
				if ( is_array( $tok ) ) {
					if ( T_COMMENT === $tok[0] || T_DOC_COMMENT === $tok[0] ) {
						$nc   .= "\n";
						$bare .= "\n";
						continue;
					}
					$nc    .= $tok[1];
					$bare  .= ( T_CONSTANT_ENCAPSED_STRING === $tok[0] || T_ENCAPSED_AND_WHITESPACE === $tok[0] ) ? "''" : $tok[1];
					continue;
				}
				$nc   .= (string) $tok;
				$bare .= (string) $tok;
		}
			return array( $nc, $bare );
		}
		$nc   = (string) preg_replace( '/\/\*.*?\*\//s', ' ', $src );
		$nc   = (string) preg_replace( '/(^|\n)[ \t]*(\/\/|#)[^\n]*/', '$1', $nc );
		$bare = (string) preg_replace( '/"(?:\\\\.|[^"\\\\])*"/', '""', $nc );
		$bare = (string) preg_replace( "/'(?:\\\\.|[^'\\\\])*'/", "''", $bare );
		return array( $nc, $bare );
	}
}

if ( ! function_exists( 'abr_blind' ) ) {
	function abr_blind( $src ) {
		$pair = abr_split( $src );
		return $pair[1];
	}
}

if ( ! function_exists( 'abr_no_comments' ) ) {
	function abr_no_comments( $src ) {
		$pair = abr_split( $src );
		return $pair[0];
	}
}


/** فهرست ثبت‌های هوک در یک فایل + هر کدام که تابعش در همان فایل تعریف نشده باشد.
    «dangling callback» = در PHP 8 خطای کشندهٔ call_user_func_array() ⇒ کل سایت پایین. */
function abr_hooks( $src ) {
	$nc = abr_no_comments( $src );
	if ( ! preg_match_all( '/add_(?:action|filter|shortcode)\(\s*\'[^\']+\'\s*,\s*\'(javan_seda_[A-Za-z0-9_]+)\'/', $nc, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		return array();
	}
	$bare = preg_replace( '/"(?:\\\\.|[^"\\\\])*"/', '""', $nc );
	$bare = preg_replace( "/'(?:\\\\.|[^'\\\\])*'/", "''", $bare );
	$bad  = array();
	foreach ( $mm as $one ) {
		$cb  = isset( $one[1][0] ) ? (string) $one[1][0] : '';
		$pos = isset( $one[1][1] ) ? (int) $one[1][1] : 0;
		if ( '' === $cb ) {
			continue;
		}
		if ( preg_match( '/function\s+' . preg_quote( $cb, '/' ) . '\s*\(/', $bare ) ) {
			continue; /* تابع در همین فایل هست ⇒ امن */
		}
		if ( false !== strpos( $bare, "function_exists( '" . $cb . "' )" ) ) {
			continue; /* ثبت با function_exists پیچیده شده ⇒ امن */
		}
		$bad[] = array( $cb, (int) substr_count( substr( $bare, 0, $pos ), "\n" ) + 1 );
	}
	return $bad;
}

echo "=== ab-rescue · گزارش نجات (v3.1.7) ===\n\n";

echo "PHP            : " . PHP_VERSION . "   (ID " . ( defined( 'PHP_VERSION_ID' ) ? PHP_VERSION_ID : '?' ) . ")\n";
echo "server software: " . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '?' ) . "\n";
echo "php.ini        : display_errors=" . ( function_exists( 'ini_get' ) ? ( ini_get( 'display_errors' ) ? 'on' : 'off' ) : '?' )
	. " · memory_limit=" . ( function_exists( 'ini_get' ) ? ini_get( 'memory_limit' ) : '?' ) . "\n";
echo "loaders        : ionCube=" . ( extension_loaded( 'ionCube Loader' ) ? '✓ ' . ( function_exists( 'ioncube_loader_version' ) ? ioncube_loader_version() : 'فعال' ) : '✗ نصب نیست' )
	. " · Zend OPcache=" . ( extension_loaded( 'Zend OPcache' ) ? '✓' : '✗' ) . "\n";
if ( function_exists( 'php_ini_loaded_file' ) ) {
	echo "php.ini path   : " . ( php_ini_loaded_file() ? php_ini_loaded_file() : '(پیش‌فرض)' ) . "\n";
}
echo "پوشۀ قالب     : " . $base . "\n";
echo "قابل‌نوشتن     : " . ( is_writable( $base ) ? '✓ (برای تغییر نام فایل‌ها دسترسی دارید)' : '✗' ) . "\n";

/* خودِ functions.php رمزنگاری‌شده است؛ سرآمدش نسخه‌های PHP مجاز را اعلام می‌کند. */
$fns = $base . '/functions.php';
if ( file_exists( $fns ) ) {
	$head = (string) file_get_contents( $fns, false, null, 0, 120 );
	if ( preg_match( '/ICB0[^\r\n]*/', $head, $m ) ) {
		echo "\nسرآمدِ functions.php (یون‌کیوب): " . trim( $m[0] ) . "\n";
		echo "  ⇒ این کد فقط با PHPهای اعلام‌شده اجرا می‌شود؛ اگر PHP میزبان چیز دیگری باشد،\n";
		echo "    خطای «Incompatible file format» همان «خطای مهم» است و ربطی به لایه‌های ما ندارد.\n";
	}
}

echo "\n--- فایل‌های لایه در این پوشه -------------------------------------\n";
$found = 0;
$warn  = 0;
$scan  = ! empty( $_GET['scan'] );
foreach ( $layers as $rel => $what ) {
	$p = $base . '/' . $rel;
	if ( ! file_exists( $p ) ) {
		echo "  –  " . str_pad( $rel, 26 ) . " نیست  · " . $what . "\n";
		continue;
	}
	$found++;
	$src   = (string) file_get_contents( $p );
	$bytes = strlen( $src );
	$mtime = date( 'Y-m-d H:i', filemtime( $p ) );
	$bad   = array();
	if ( preg_match( '/\.php$/', $rel ) ) {
		$blind = abr_blind( $src );
		foreach ( $modern as $label => $re ) {
			if ( preg_match_all( $re, $blind, $mm, PREG_OFFSET_CAPTURE ) ) {
				$lines = array();
				foreach ( $mm[0] as $one ) {
					$lines[] = (int) ( substr_count( substr( $blind, 0, $one[1] ), "\n" ) + 1 );
				}
				$bad[ $label ] = array_slice( array_unique( $lines ), 0, 6 );
			}
		}
	}
	$hooks = abr_hooks( $src );
	if ( $hooks ) {
		foreach ( $hooks as $h ) {
			$bad[ 'ارجاع به تابع تعریف‌نشده ⇒ در PHP 8 خطای کشنده (این دقیقاً علت خطای 3.1.5 بود): ' . $h[0] . ' در خط ' . $h[1] ] = array();
		}
	}
	if ( $bad ) {
		$warn++;
		echo "  ⚠  " . str_pad( $rel, 26 ) . " " . $bytes . "B · " . $mtime . " · " . $what . "\n";
		foreach ( $bad as $label => $lines ) {
			echo "       ⚠ " . $label . " در خط(های) " . implode( ', ', $lines ) . "\n";
			if ( $scan ) {
				$all = explode( "\n", $src );
				foreach ( $lines as $ln ) {
					if ( isset( $all[ $ln - 1 ] ) ) {
						echo "          " . trim( substr( str_replace( "\r", '', $all[ $ln - 1 ] ), 0, 150 ) ) . "\n";
					}
				}
			}
		}
		echo "       ⇒ درمان: این فایل را به " . basename( $rel ) . ".off تغییر نام دهید یا نسخۀ v3.1.6 را بگذارید.\n";
	} else {
		echo "  ✓  " . str_pad( $rel, 26 ) . " " . $bytes . "B · " . $mtime . " · " . $what . "\n";
	}
}
echo "\n  فایل‌های یافت‌شده: {$found} · با ساختارِ نیازمند PHP جدیدer: {$warn}\n";
if ( $scan ) {
	echo "  (برای دیدن خودِ خط‌ها ?scan=1 را نگه دارید.)\n";
} else {
	echo "  برای دیدن خودِ خط‌های مشکوک:  ?scan=1\n";
}

echo "\n--- قدم‌های بعدی ---------------------------------------------------\n";
echo "۱) اگر ⚠ دیدید ⇒ همان فایل را موقتاً .off کنید (سایت بالا می‌آید، فقط آن قابلیت خاموش می‌شود).\n";
echo "۲) اگر ⚠ ندیدید ⇒ خطای واقعی در لاگ است. در wp-config.php این سه خط را بگذارید و دوباره صفحه را باز کنید:\n";
echo "     define('WP_DEBUG', true); define('WP_DEBUG_LOG', true); define('WP_DEBUG_DISPLAY', false);\n";
echo "     سپس 10 خط آخر wp-content/debug.log را برای ما بفرستید.\n";
echo "۳) اگر پیام «Incompatible file format» دیدید ⇒ PHP میزبان را روی همان نسخه‌ای بگذارید که\n";
echo "     functions.php اعلام می‌کند (معمولاً 7.4 یا 8.1)؛ این مورد از افزونه/قالب‌ساز بیرون است.\n";
echo "۴) پس از رفع، هر دو فایل ab-rescue.php و ab-mobile-probe.php را پاک کنید.\n\n";
echo "(پایان گزارش — ab-rescue.php هیچ چیزی را تغییر نمی‌دهد.)\n";
