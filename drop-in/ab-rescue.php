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

/** هر چیزی داخل نقل‌قول یا کامنت را با فاصله جایگزین می‌کند. */
function abr_blind( $src ) {
	$out = str_split( $src );
	$i = 0;
	$n = strlen( $src );
	while ( $i < $n ) {
		$c = $src[ $i ];
		if ( "'" === $c || '"' === $c || '`' === $c ) {
			$i++;
			while ( $i < $n ) {
				if ( '\\' === $src[ $i ] ) {
					$out[ $i ] = ' ';
					if ( $i + 1 < $n ) {
						$out[ $i + 1 ] = ' ';
					}
					$i += 2;
					continue;
				}
				if ( $src[ $i ] === $c ) {
					$i++;
					break;
				}
				$out[ $i ] = ' ';
				$i++;
			}
			continue;
		}
		if ( '#' === $c || 0 === strpos( substr( $src, $i ), '//' ) ) {
			$j = strpos( $src, "\n", $i );
			$j = ( false === $j ) ? $n : $j;
			for ( $k = $i; $k < $j; $k++ ) {
				$out[ $k ] = ' ';
			}
			$i = $j;
			continue;
		}
		if ( 0 === strpos( substr( $src, $i ), '/*' ) ) {
			$j = strpos( $src, '*/', $i + 2 );
			$j = ( false === $j ) ? $n : $j + 2;
			for ( $k = $i; $k < $j; $k++ ) {
				if ( "\n" !== $src[ $k ] ) {
					$out[ $k ] = ' ';
				}
			}
			$i = $j;
			continue;
		}
		$i++;
	}
	return implode( '', $out );
}

echo "=== ab-rescue · گزارش نجات (v3.1.6) ===\n\n";

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
