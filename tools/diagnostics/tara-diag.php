<?php
/**
 * ترانه‌یار — کاوشگرِ مستقلِ سلامت (v3.17.4)
 *
 * این فایل عمداً هیچ چیزی از وردپرس را بار نمی‌کند. اگر صفحهٔ سایت باز
 * نمی‌شود، این فایل باید به‌تنهایی باز شود. نتیجه‌اش می‌گوید مشکل از
 * «وردپرس/دیتابیس/هاست» است یا از «قالب».
 *
 * نصب: همین فایل را در ریشهٔ سایت بگذارید و باز کنید:
 *     https://ahangban.ir/tara-diag.php
 * پس از تشخیص، فایل را پاک کنید.
 */

/* ------------------------------------------------------------------
 * محافظت: این فایل اطلاعاتِ حساس (مسیرها، نام جدول‌ها، خطاها) را
 * چاپ می‌کند. اگر TARA_DIAG_KEY را در wp-config.php گذاشته باشید،
 * فقط با ?key=… باز می‌شود. اگر نگذاشته باشید، بدون کلید باز می‌شود
 * ولی در خروجی هشدار می‌دهد که باید پس از تشخیص پاک شود.
 * ------------------------------------------------------------------ */
if ( defined( 'TARA_DIAG_KEY' ) && '' !== (string) TARA_DIAG_KEY ) {
	$ty_k = isset( $_GET['key'] ) ? (string) $_GET['key'] : '';
	if ( ! hash_equals( (string) TARA_DIAG_KEY, $ty_k ) ) {
		header( 'HTTP/1.1 404 Not Found' );
		echo 'Not found.';
		exit;
	}
	unset( $ty_k );
}

header( 'Content-Type: text/plain; charset=utf-8' );
header( 'X-Robots-Tag: noindex, nofollow' );
header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );

$ok   = "\xE2\x9C\x93"; // check
$bad  = "\xE2\x9C\x97"; // cross
$warn = "!";
$out  = array();

$line = function ( $mark, $label, $value = '' ) use ( &$out ) {
	$out[] = $mark . ' ' . str_pad( $label, 34 ) . ' : ' . $value;
};

$out[] = '=== کاوشگر ترانه‌یار / ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC ===';
if ( ! defined( 'TARA_DIAG_KEY' ) || '' === (string) TARA_DIAG_KEY ) {
	$out[] = '⚠ این فایل بدون کلید محافظت باز است. پس از تشخیص حتماً پاکش کنید.';
	$out[] = '   برای محافظت، در wp-config.php بگذارید:';
	$out[] = "       define( 'TARA_DIAG_KEY', 'یک-رمز-دلخواه' );";
}
$out[] = '';

/* ---------------------------------------------------------------- 1) PHP */
$out[] = '--- PHP ---';
$line( $ok, 'نسخهٔ PHP', PHP_VERSION );
$line( PHP_VERSION_ID >= 70400 ? $ok : $bad, 'حداقل ۷.۴', PHP_VERSION_ID >= 70400 ? 'بله' : 'نه — قالب نیاز دارد' );
$line( $ok, 'memory_limit', (string) ini_get( 'memory_limit' ) );
$line( $ok, 'max_execution_time', (string) ini_get( 'max_execution_time' ) );
$line( $ok, 'output_buffering', (string) ini_get( 'output_buffering' ) );
$line( $ok, 'zlib.output_compression', (string) ini_get( 'zlib.output_compression' ) );
$line( function_exists( 'ob_start' ) ? $ok : $bad, 'ob_start موجود', function_exists( 'ob_start' ) ? 'بله' : 'نه' );
$line( function_exists( 'opcache_get_status' ) ? $warn : $ok, 'OPcache', function_exists( 'opcache_get_status' ) ? 'فعال است (اگر کد قدیمی سرو می‌شود، ریستش کنید)' : 'فعال نیست' );
$line( class_exists( 'PDO' ) ? $ok : $warn, 'PDO', class_exists( 'PDO' ) ? 'بله' : 'نه' );
$line( function_exists( 'mysqli_connect' ) ? $ok : $warn, 'mysqli', function_exists( 'mysqli_connect' ) ? 'بله' : 'نه' );
$out[] = '';

/* --------------------------------------------------------- 2) مسیر و فایل */
$out[] = '--- فایل‌ها ---';
$root = __DIR__;
$line( $ok, 'مسیر این فایل', $root );
$line( is_writable( $root ) ? $warn : $ok, 'ریشهٔ سایت قابل نوشتن', is_writable( $root ) ? 'بله (برای امنیت بهتر است نه)' : 'نه (خوب است)' );

$wp_files = array( 'wp-load.php', 'wp-config.php', 'wp-settings.php', 'index.php' );
foreach ( $wp_files as $f ) {
	$line( file_exists( $root . '/' . $f ) ? $ok : $bad, $f, file_exists( $root . '/' . $f ) ? 'هست' : 'نیست' );
}

$theme = $root . '/wp-content/themes/taranehyar';
$line( is_dir( $theme ) ? $ok : $bad, 'پوشهٔ قالب taranehyar', is_dir( $theme ) ? 'هست' : 'نیست' );
if ( is_dir( $theme ) ) {
	$tf = array( 'style.css', 'functions.php', 'index.php', 'header.php', 'footer.php', 'myfunctions.php' );
	foreach ( $tf as $f ) {
		$line( file_exists( $theme . '/' . $f ) ? $ok : $bad, '  ' . $f, file_exists( $theme . '/' . $f ) ? 'هست' : 'نیست' );
	}
	/* فایل‌هایی که باید صفر بایت نباشند */
	foreach ( array( 'functions.php', 'myfunctions.php' ) as $f ) {
		if ( file_exists( $theme . '/' . $f ) ) {
			$sz = (int) filesize( $theme . '/' . $f );
			$line( $sz > 100 ? $ok : $bad, '  حجم ' . $f, $sz . ' بایت' );
		}
	}
}
$out[] = '';

/* ------------------------------------------------------ 3) بارگذاری وردپرس */
$out[] = '--- بارگذاری وردپرس ---';
if ( ! file_exists( $root . '/wp-load.php' ) ) {
	$line( $bad, 'wp-load.php', 'نیست — وردپرس ناقص است' );
} else {
	$t0 = microtime( true );
	$err_before = error_reporting( E_ALL );
	$load_ok = false;
	try {
		/* عمداً بدون SHORTINIT تا ببینیم بوتِ کامل چقدر طول می‌کشد */
		define( 'WP_USE_THEMES', false );
		require_once $root . '/wp-load.php';
		$load_ok = true;
	} catch ( Throwable $e ) {
		$line( $bad, 'خطای بوت', get_class( $e ) . ': ' . $e->getMessage() );
		$line( $warn, 'فایل', $e->getFile() . ':' . $e->getLine() );
	}
	$boot_ms = (int) round( ( microtime( true ) - $t0 ) * 1000 );
	$line( $load_ok ? ( $boot_ms < 3000 ? $ok : $warn ) : $bad, 'بوت وردپرس', $load_ok ? ( $boot_ms . ' میلی‌ثانیه' ) : 'ناموفق' );

	if ( $load_ok ) {
		global $wpdb;
		$line( isset( $wpdb ) && $wpdb ? $ok : $bad, 'اتصال دیتابیس', ( isset( $wpdb ) && $wpdb ) ? 'برقرار' : 'ندارد' );

		if ( isset( $wpdb ) && $wpdb ) {
			$q0  = microtime( true );
			$one = $wpdb->get_var( 'SELECT 1' );
			$qms = (int) round( ( microtime( true ) - $q0 ) * 1000 );
			$line( '1' === (string) $one ? ( $qms < 500 ? $ok : $warn ) : $bad, 'پرسش آزمایشی', 'SELECT 1 → ' . var_export( $one, true ) . ' در ' . $qms . 'ms' );

			$posts = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type='post'" );
			$line( $ok, 'تعداد نوشتهٔ منتشرشده', (string) $posts );

			$tables = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()' );
			if ( $tables > 0 ) {
				$line( $ok, 'تعداد جدول‌ها', (string) $tables );
			}
			$line( $ok, 'پیشوند جدول', $wpdb->prefix );
			$db_err = $wpdb->last_error;
			$line( '' === (string) $db_err ? $ok : $bad, 'آخرین خطای دیتابیس', '' === (string) $db_err ? 'ندارد' : $db_err );
		}

		$line( function_exists( 'wp_get_theme' ) ? $ok : $bad, 'قالب فعال', function_exists( 'wp_get_theme' ) ? (string) wp_get_theme()->get( 'Name' ) . ' / ' . (string) wp_get_theme()->get( 'Version' ) : '?' );
		$line( $ok, 'آدرس سایت', function_exists( 'home_url' ) ? home_url( '/' ) : '?' );

		if ( function_exists( 'get_option' ) ) {
			$aps = (array) get_option( 'active_plugins', array() );
			$line( count( $aps ) ? $warn : $ok, 'افزونه‌های فعال', count( $aps ) ? ( count( $aps ) . ' — ' . implode( ', ', $aps ) ) : 'صفر' );
			$line( $ok, 'permalink_structure', (string) get_option( 'permalink_structure' ) );
			$line( $ok, 'template', (string) get_option( 'template' ) );
			$line( $ok, 'stylesheet', (string) get_option( 'stylesheet' ) );
		}

		/* ----------------------------------------------------------------
		 * سه آزمونِ رندر، به ترتيبِ سخت‌گیری:
		 *   الف) جست‌وجوی مستقیم در دیتابیس (بدون قالب)
		 *   ب) بارگذاری دستیِ فایل قالب (بدون HTTP)
		 *   ج) درخواست HTTP داخلی به خودِ سایت (شبیه بازدیدکننده)
		 * ---------------------------------------------------------------- */
		$out[] = '';
		$out[] = '--- آزمون رندرِ صفحهٔ اصلی ---';

		/* الف) دیتابیس */
		if ( isset( $wpdb ) && $wpdb ) {
			$n = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type='post'" );
			$line( $n > 0 ? $ok : $warn, 'آهنگ منتشرشده', $n . ' مورد' );
			$sql_ms = 0;
			$qq = $wpdb->get_results( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_status='publish' AND post_type='post' ORDER BY post_date DESC LIMIT 10" );
			$line( is_array( $qq ) ? $ok : $bad, 'خواندن ۱۰ آهنگ آخر', is_array( $qq ) ? ( count( $qq ) . ' ردیف' ) : 'خطا' );
		}

		/* ب) فایل قالب، بدون HTTP */
		$tpl0 = microtime( true );
		$tpl_html = '';
		$tpl_err  = '';
		$lvl_b    = ob_get_level();
		try {
			/* پروندهٔ قالب را در همان پروسه اجرا می‌کنیم؛ اگر fatal بدهد، اینجا می‌بینیم */
			$tpl_file = $root . '/wp-content/themes/taranehyar/index.php';
			if ( is_readable( $tpl_file ) ) {
				ob_start();
				/* متغیرهای محیطی که قالب انتظار دارد */
				if ( ! defined( 'TARA_DIAG_RENDER' ) ) {
					define( 'TARA_DIAG_RENDER', true );
				}
				include $tpl_file;
				$tpl_html = ob_get_clean();
			} else {
				$tpl_err = 'index.php خواندنی نیست';
			}
		} catch ( Throwable $e ) {
			while ( ob_get_level() > $lvl_b ) {
				ob_end_clean();
			}
			$tpl_err = get_class( $e ) . ': ' . $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
		}
		$tpl_ms = (int) round( ( microtime( true ) - $tpl0 ) * 1000 );
		$line( '' === $tpl_err ? ( strlen( $tpl_html ) > 2000 ? $ok : $bad ) : $bad, 'اجرای مستقیم index.php', '' === $tpl_err ? ( $tpl_ms . 'ms — ' . strlen( $tpl_html ) . ' بایت' ) : ( 'خطا: ' . $tpl_err ) );
		if ( '' === $tpl_err && strlen( $tpl_html ) > 0 ) {
			$line( false !== stripos( $tpl_html, '</html>' ) ? $ok : $bad, '  HTML کامل', false !== stripos( $tpl_html, '</html>' ) ? 'بله' : 'نه' );
			$line( false !== stripos( $tpl_html, '<header' ) ? $ok : $bad, '  هدر', false !== stripos( $tpl_html, '<header' ) ? 'هست' : 'نیست' );
			$line( false !== stripos( $tpl_html, 'song_boxs' ) ? $ok : $bad, '  فهرست آهنگ', false !== stripos( $tpl_html, 'song_boxs' ) ? 'هست' : 'نیست' );
		}

		/* ج) HTTP داخلی */
		$r0 = microtime( true );
		$rendered = '';
		$render_err = '';
		$lvl0 = ob_get_level();
		try {
			ob_start();
			/* درخواست داخلی به خودِ خانه */
			$url  = home_url( '/' );
			$resp = wp_remote_get(
				$url,
				array(
					'timeout'     => 25,
					'sslverify'   => false,
					'redirection' => 3,
					'user-agent'  => 'taranehyar-diag/3.17.4',
					/* روی بعضی هاست‌ها loopback به خود بسته است؛ */
					/* اگر شکست خورد، خطا را گزارش می‌کنیم نه اینکه بترسیم */
				)
			);
			$rendered = ob_get_clean();
			if ( is_wp_error( $resp ) ) {
				$render_err = $resp->get_error_message();
			} else {
				$code = (int) wp_remote_retrieve_response_code( $resp );
				$body = (string) wp_remote_retrieve_body( $resp );
				$line( 200 === $code ? $ok : $bad, 'HTTP خانه (از داخل)', (string) $code . ' — ' . strlen( $body ) . ' بایت' );
				$line( strlen( $body ) > 2000 ? $ok : $bad, 'حجم HTML', strlen( $body ) . ' بایت' . ( strlen( $body ) <= 2000 ? '  ← خیلی کم؛ صفحه خالی است' : '' ) );
				$line( false !== stripos( $body, '</html>' ) ? $ok : $bad, 'HTML کامل است', false !== stripos( $body, '</html>' ) ? 'بله' : 'نه — رندر نیمه‌کاره' );
				$line( false !== stripos( $body, '<header' ) ? $ok : $bad, 'هدر رندر شد', false !== stripos( $body, '<header' ) ? 'بله' : 'نه' );
				$line( false !== stripos( $body, 'song_boxs' ) ? $ok : $bad, 'فهرست آهنگ‌ها', false !== stripos( $body, 'song_boxs' ) ? 'بله' : 'نه' );
				$line( false !== stripos( $body, '</footer>' ) || false !== stripos( $body, '<footer' ) ? $ok : $bad, 'فوتر رندر شد', ( false !== stripos( $body, '<footer' ) ) ? 'بله' : 'نه' );
			}
		} catch ( Throwable $e ) {
			while ( ob_get_level() > $lvl0 ) {
				ob_end_clean();
			}
			$render_err = get_class( $e ) . ': ' . $e->getMessage();
		}
		$rms = (int) round( ( microtime( true ) - $r0 ) * 1000 );
		$line( '' === $render_err ? ( $rms < 8000 ? $ok : $warn ) : $bad, 'زمان رندر خانه', $rms . ' میلی‌ثانیه' . ( '' !== $render_err ? ' — خطا: ' . $render_err : '' ) );

		/* سطح بافر در پایان */
		$out[] = '';
		$out[] = '--- بافر خروجی ---';
		$line( $lvl0 === ob_get_level() ? $ok : $bad, 'سطح بافر (باید برابر شروع باشد)', 'شروع=' . $lvl0 . ' پایان=' . ob_get_level() );

		/* قالب چه فایل‌هایی را در بوت خوانده؟ */
		$out[] = '';
		$out[] = '--- فایل‌های قالبِ بارگذاری‌شده ---';
		if ( function_exists( 'get_included_files' ) ) {
			$n = 0;
			foreach ( get_included_files() as $inc ) {
				if ( false !== strpos( $inc, '/themes/taranehyar/' ) ) {
					$out[] = '  ' . str_replace( $theme . '/', '', $inc );
					$n++;
					if ( $n > 60 ) {
						$out[] = '  ...';
						break;
					}
				}
			}
			$line( $n ? $ok : $bad, 'تعداد فایل قالب', (string) $n );
		}

		/* خطاهای PHP ثبت‌شده */
		$out[] = '';
		$out[] = '--- خطاهای PHP ---';
		$elog = ini_get( 'error_log' );
		$line( $ok, 'error_log', '' !== (string) $elog ? (string) $elog : '(پیش‌فرض هاست)' );
		$line( $ok, 'display_errors', (string) ini_get( 'display_errors' ) );
		$line( $ok, 'WP_DEBUG', defined( 'WP_DEBUG' ) ? ( WP_DEBUG ? 'true' : 'false' ) : 'تعریف نشده' );
	}

	error_reporting( $err_before );
}

$out[] = '';
$out[] = '=== پایان. این فایل را پس از تشخیص پاک کنید. ===';

echo implode( "\n", $out ) . "\n";
