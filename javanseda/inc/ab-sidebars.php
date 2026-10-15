<?php
/**
 * جوان‌صدا (آهنگ‌بان) — لایهٔ ایمنی و بازرسی «سایدبار / ابزارک» نسخهٔ 3.0.2
 * ---------------------------------------------------------------------------
 * هدف: ابزارک‌های ستون چپِ صفحهٔ نوشته (single.php) باید همیشه نمایش داده
 * شوند، حتی اگر یکی از حلقه‌های ثبت قالب قطع شده باشد.
 *
 * این فایل کارهای زیر را می‌کند (و فقط همین‌ها را):
 *  ۱) اگر کلاس‌های ابزارک اختصاصی قالب ثبت نشده باشند، inc/widgets.php را
 *     یک‌بار بارگذاری و register_widget می‌کند (جلوگیری از خروجی خالی).
 *  ۲) اگر هر یک از سه ناحیهٔ left_side / left_side2 / left_side1 ثبت نشده
 *     باشد، آن‌ها را عیناً با همین شناسه‌ها ثبت می‌کند (net ایمن).
 *  ۳) یک تابع رندر امن (javan_seda_sidebar) که ابزارک را چاپ می‌کند و اگر
 *     ناحیه خالی باشد، به‌جای خروجی خالی، یک «کامنت تشخیصی» در HTML می‌گذارد.
 *  ۴) بازگرداندن غیرمخرب ابزارک‌های افتاده در «ابزارک‌های غیرفعال»:
 *     فقط در زمان خواندن (فیلتر sidebars_widgets) و بدون هیچ نوشتن در دیتابیس.
 *  ۵) گزارش تشخیصی فقط‌خواندنی:  ?ab_sidebars_diag=1  (فقط برای مدیر)
 *
 * قوانین رعایت‌شده:
 *  - هیچ دست‌کاری در تاکسونومی‌ها (از جمله singer) و هیچ هوک سراسری جدیدی.
 *  - تنها هوک‌ها: widgets_init (اولویت ۵ و ۲۰، فقط در صورت نبود)،
 *    فیلتر sidebars_widgets (خواندنی) و فیلتر template_include برای گزارش.
 *  - بدون گرادینت، بدون رنگ جدید؛ پالت: #FFFFFF / #185ADB / #111111
 *
 * ⚠ توضیح عمدی: تعریف سه ناحیه در این فایل، مشابه myfunctions.php است.
 *   این تکرار عمداً انجام شده تا «شبیه‌ safety net » حتی اگر myfunctions.php
 *   بارگذاری نشده باشد هم کار کند.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------------------
 * ۰) تنظیم‌ها (پیش‌فرض‌ها امن‌اند؛ برای تغییر، قبل از بارگذاری قالب تعریف کنید
 *    یا در wp-config.php بنویسید:  define( 'JAVANSEDA_...', false ); )
 * ------------------------------------------------------------------------- */
if ( ! defined( 'JAVANSEDA_SIDEBAR_RECOVERY' ) ) {
	define( 'JAVANSEDA_SIDEBAR_RECOVERY', true );      // بازگردانی از ابزارک‌های غیرفعال
}
if ( ! defined( 'JAVANSEDA_SIDEBAR_MIRROR_PAGES' ) ) {
	define( 'JAVANSEDA_SIDEBAR_MIRROR_PAGES', false ); // اگر ابزارک فقط در ناحیهٔ «برگه‌ها» باشد
}
if ( ! defined( 'JAVANSEDA_SIDEBAR_MARKERS' ) ) {
	define( 'JAVANSEDA_SIDEBAR_MARKERS', true );       // کامنت تشخیصی وقتی ناحیه خالی است
}

/** شناسهٔ ابزارک‌های اختصاصی قالب (هر چه در inc/widgets.php ثبت می‌شود). */
function javan_seda_widget_id_bases() {
	return array( 'tarlanweb_ir_newp', 'tarlanweb_ir_popular', 'tarlanweb_ir_rand' );
}

/** تعریف سه ناحیهٔ سایدبار — شناسه‌ها به‌هیچ‌وجه تغییر نمی‌کنند. */
function javan_seda_sidebar_defs() {
	$shared = array(
		'before_widget' => '<aside id="%1$s" class="related_posts %2$s">',
		'after_widget'  => '<div class="clear"></div></div></aside>',
		'before_title'  => '<h3 class="tarlanweb_side_titles">',
		'after_title'   => '</h3><div class="liner clear"></div><div class="tarlanweb_side_box">',
	);

	return array(
		'left_side'  => array_merge( $shared, array(
			'id'          => 'left_side',
			'class'       => 'leftside',
			'name'        => 'سایدبار نوشته‌ها — بالا (single.php)',
			'description' => 'بالای ستون چپ صفحهٔ نوشته/آهنگ. شناسه: left_side — ابزارک‌های اختصاصی قالب: جدیدترین موسیقی‌ها / موسیقی محبوب / مطالب تصادفی.',
		) ),
		'left_side2' => array_merge( $shared, array(
			'id'          => 'left_side2',
			'class'       => 'leftside2',
			'name'        => 'سایدبار نوشته‌ها — پایین (single.php)',
			'description' => 'پایین ستون چپ صفحهٔ نوشته/آهنگ، بعد از نوشته‌های مرتبط. شناسه: left_side2',
		) ),
		'left_side1' => array_merge( $shared, array(
			'id'          => 'left_side1',
			'class'       => 'leftside1',
			'name'        => 'سایدبار برگه‌ها (page.php)',
			'description' => 'ستون چپ «برگه‌ها» — روی صفحهٔ نوشته‌ها نمایش داده نمی‌شود! شناسه: left_side1',
		) ),
	);
}

/** آیا ناحیه‌ای با این شناسه ثبت شده است؟ (بدون وابستگی به نسخهٔ وردپرس) */
function javan_seda_sidebar_registered( $id ) {
	global $wp_registered_sidebars;
	return is_array( $wp_registered_sidebars ) && isset( $wp_registered_sidebars[ $id ] );
}

/** ثبت ناحیه‌های جاافتاده — هم در widgets_init و هم در زمان رندر قابل فراخوانی. */
function javan_seda_register_missing_sidebars() {
	foreach ( javan_seda_sidebar_defs() as $id => $args ) {
		// idempotent: ناحیه‌ای که قبلاً ثبت شده را بازنویسی نمی‌کنیم
		if ( ! javan_seda_sidebar_registered( $id ) && function_exists( 'register_sidebar' ) ) {
			register_sidebar( $args );
		}
	}
}
add_action( 'widgets_init', 'javan_seda_register_missing_sidebars', 20 );

/**
 * بارگذاری ابزارک‌های اختصاصی قالب، اگر هیچ‌کجا register نشده باشند.
 * (نسخهٔ رمزنگاری‌شدهٔ functions.php معمولاً inc/widgets.php را include می‌کند؛
 *  اگر آن include گم/حذف شده باشد، خروجی هر ابزارک قدیمی در سایدبار «خالی» است.)
 */
function javan_seda_ensure_widget_classes() {
	$missing = array();
	foreach ( javan_seda_widget_id_bases() as $cls ) {
		if ( ! class_exists( $cls ) ) {
			$missing[] = $cls;
		}
	}
	if ( ! $missing ) {
		return;
	}
	$file = get_template_directory() . '/inc/widgets.php';
	if ( is_readable( $file ) ) {
		require_once $file;
	}
	foreach ( $missing as $cls ) {
		if ( class_exists( $cls ) && function_exists( 'register_widget' ) ) {
			register_widget( $cls ); // بی‌ضرر: register_widget بر اساس نام کلاس بازنویسی می‌کند
		}
	}
}
add_action( 'widgets_init', 'javan_seda_ensure_widget_classes', 5 );

/** آیا این شناسهٔ ابزارک متعلق به قالب است؟ (برای بازگردانی از ناحیهٔ غیرفعال) */
function javan_seda_is_theme_widget_id( $widget_id ) {
	$widget_id = (string) $widget_id;
	if ( '' === $widget_id ) {
		return false;
	}
	if ( 0 === strpos( $widget_id, 'block-' ) ) {
		return true; // ابزارک ذخیره‌شده به‌صورت بلوک در همین قالب
	}
	foreach ( javan_seda_widget_id_bases() as $base ) {
		if ( 0 === strpos( $widget_id, $base . '-' ) || 0 === strpos( $widget_id, $base . '__' ) ) {
			return true;
		}
	}
	return false;
}

/**
 * ترمیم خواندنی: اگر هر دو ناحیهٔ صفحهٔ نوشته خالی بودند، ابزارک‌های قالب را
 * از «ابزارک‌های غیرفعال» (و کلیدهای یتیم) به همان ناحیه‌ها برمی‌گردانیم.
 * هیچ مقدار جدیدی در دیتابیس ذخیره نمی‌شود؛ در نتیجه با یک «ذخیره» در پیشخوان،
 * وضعیت اصلیِ دیتابیس دست‌نخورده باقی می‌ماند.
 */
function javan_seda_recover_sidebars( $sidebars_widgets ) {
	if ( ! is_array( $sidebars_widgets ) || is_admin() || ! JAVANSEDA_SIDEBAR_RECOVERY ) {
		return $sidebars_widgets;
	}

	$single_areas = array( 'left_side', 'left_side2' );
	foreach ( $single_areas as $area ) {
		if ( ! empty( $sidebars_widgets[ $area ] ) ) {
			return $sidebars_widgets; // چیزی برای ترمیم نیست
		}
	}

	$pool = array();
	foreach ( array_keys( $sidebars_widgets ) as $key ) {
		if ( 'wp_inactive_widgets' === $key || 0 === strpos( $key, 'orphaned_widgets' ) ) {
			if ( is_array( $sidebars_widgets[ $key ] ) ) {
				$pool = array_merge( $pool, $sidebars_widgets[ $key ] );
			}
		}
	}

	$from_pages = false;
	if ( ! $pool && JAVANSEDA_SIDEBAR_MIRROR_PAGES && ! empty( $sidebars_widgets['left_side1'] ) ) {
		$pool        = (array) $sidebars_widgets['left_side1'];
		$from_pages = true;
	}
	if ( ! $pool ) {
		return $sidebars_widgets;
	}

	$mine = array();
	$keep = array();
	foreach ( $pool as $wid ) {
		if ( javan_seda_is_theme_widget_id( $wid ) ) {
			$mine[] = $wid;
		} elseif ( ! $from_pages ) {
			$keep[] = $wid;
		} else {
			$mine[] = $wid; // در حالت آینه، همهٔ ابزارک‌های ناحیهٔ برگه قابل استفاده‌اند
		}
	}
	if ( ! $mine ) {
		return $sidebars_widgets;
	}

	// همه در «بالای ستون چپ نوشته‌ها» (پیش‌بینی‌پذیرترین رفتار؛ ناحیهٔ پایین را دست نمی‌گیریم)
	$sidebars_widgets['left_side'] = $mine;
	if ( isset( $sidebars_widgets['wp_inactive_widgets'] ) && ! $from_pages ) {
		$sidebars_widgets['wp_inactive_widgets'] = $keep;
	}
	$GLOBALS['javan_seda_sidebar_recovered'] = $mine;

	return $sidebars_widgets;
}
add_filter( 'sidebars_widgets', 'javan_seda_recover_sidebars', 20 );

/**
 * رندر امن یک ناحیهٔ ابزارک — جایگزین خط قدیمی dynamic_sidebar() در قالب.
 * اگر ناحیه خالی بود، جای آن یک کامنت تشخیصی چاپ می‌شود تا با «View source»
 * قابل تشخیص باشد که مشکل از «دیتای ابزارک» است یا از «خودِ قالب».
 */
function javan_seda_sidebar( $index ) {
	$index = preg_replace( '/[^a-z0-9_\-]/i', '', (string) $index );
	if ( '' === $index ) {
		return;
	}

	if ( ! javan_seda_sidebar_registered( $index ) ) {
		javan_seda_register_missing_sidebars();
	}

	$snap_post    = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
	$snap_wpquery = isset( $GLOBALS['wp_query'] ) ? $GLOBALS['wp_query'] : null;
	$snap_id      = isset( $GLOBALS['id'] ) ? $GLOBALS['id'] : null;

	$state = 'rendered';
	if ( ! function_exists( 'dynamic_sidebar' ) ) {
		$state = 'no-dynamic_sidebar';
	} elseif ( ! javan_seda_sidebar_registered( $index ) ) {
		$state = 'not-registered';
	} elseif ( function_exists( 'is_active_sidebar' ) && ! is_active_sidebar( $index ) ) {
		$state = 'no-active-widget';
	} else {
		$ok = dynamic_sidebar( $index );
		if ( ! $ok ) {
			$state = 'empty-sidebar';
		}
	}

	// ابزارک‌های قدیمی این قالب داخل خودشان $wp_query را عوض می‌کنند؛ حالت حلقه را برمی‌گردانیم.
	if ( $snap_post ) {
		$GLOBALS['post'] = $snap_post;
	}
	if ( $snap_wpquery ) {
		$GLOBALS['wp_query'] = $snap_wpquery;
	}
	if ( null !== $snap_id ) {
		$GLOBALS['id'] = $snap_id;
	}

	if ( 'rendered' !== $state && JAVANSEDA_SIDEBAR_MARKERS ) {
		echo '<!-- javan_seda: sidebar ' . esc_html( $index ) . ' => ' . esc_html( $state ) . ' -->';
	}
}

/* ---------------------------------------------------------------------------
 * گزارش تشخیصی — فقط‌خواندنی، فقط برای مدیر
 * نحوهٔ استفاده: آدرس همان صفحهٔ نوشته را با ?ab_sidebars_diag=1 باز کنید.
 * ------------------------------------------------------------------------- */

/** فهرست هش (SHA-256) فایل‌های منتظره — مقایسه روی محتوای یکسان‌سازی‌شده (LF). */
function javan_seda_expected_hashes() {
	return array(
		'single.php'          => 'edfa7b63cfb648c39ea13653555ce48e0cc2100214334e07641105c7e2a04235',
		'page.php'            => 'fee8efe74cec41f61d2c0598cfb8308fd90ab8ad66b09b1c30c51bde465948ad',
		'myfunctions.php'     => '5559e9f13cab429db0eeefa2b48b18b9dfcbedf552a3d77ea8b96c7b51bc845a',
		'style.css'           => '1dfce838900e2ebd75804c6ee6a0e4d1ab0e05a2b9eebf26e06193069344f6ce',
		'footer.php'          => '0e8e4b8d56a75ecf142caa5082d65070649cf8ba6c64048ac4c26979e41c40fe',
		'header.php'          => '1b83aa32e1b10626229923ebac76fdb258932ebcc4a967ec6a9bf572850d3cbd',
		'home.php'            => '78df0d9a104c44ff22901cd07f732aaed9f9a6370ba1305c75afd7440d2527e6',
		'inc/vip_slider.php'  => '03b6e6cbf63ee7e4194bf55dda81d1312a6802d31287e1aaea61fe0ee1ad0410',
	);
}

function javan_seda_file_hash( $path ) {
	if ( ! is_readable( $path ) ) {
		return 'MISSING';
	}
	$c = file_get_contents( $path );
	if ( false === $c ) {
		return 'UNREADABLE';
	}
	$c = str_replace( array( "\r\n", "\r" ), "\n", $c );
	return hash( 'sha256', $c );
}

add_filter( 'template_include', 'javan_seda_sidebars_diag_route', 8 );
function javan_seda_sidebars_diag_route( $template ) {
	if ( empty( $_GET['ab_sidebars_diag'] ) || ! current_user_can( 'manage_options' ) ) {
		return $template;
	}

	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );

	global $wp_registered_sidebars, $wp_widget_factory, $wp_query;

	$L    = array();
	$line = function ( $s = '' ) use ( &$L ) {
		$L[] = $s;
	};

	$theme = wp_get_theme();

	$line( '=== گزارش تشخیص «ابزارک / سایدبار نوشته‌ها» — قالب جوان‌صدا ===' );
	$line( 'WordPress: ' . get_bloginfo( 'version' ) . ' | PHP: ' . PHP_VERSION . ' | نسخهٔ قالب: ' . $theme->get( 'Version' ) );
	$line( 'stylesheet: ' . $theme->get_stylesheet() . ' | template: ' . $theme->get_template() . ( $theme->get_stylesheet() !== $theme->get_template() ? '  <<< چایلد‌تیم فعال است؛ فایل‌ها را در چایلد بررسی کنید' : '' ) );
	$line( 'قالبِ درخواست‌شده در این درخواست: ' . ( $template ? $template : '(خالی)' ) );
	$line( 'is_singular(post): ' . ( is_singular( 'post' ) ? 'yes' : 'no' ) . ' | is_page(): ' . ( is_page() ? 'yes' : 'no' ) . ' | is_admin(): ' . ( is_admin() ? 'yes' : 'no' ) );
	$line( '' );

	$line( '--- ۱) بارگذاری فایل‌های مسئول ---' );
	$line( 'myfunctions.php بارگذاری شد؟      : ' . ( function_exists( 'leftsides' ) ? 'بله' : 'نه  <<< علت اصلی: ناحیه‌های ابزارک ثبت نشده‌اند' ) );
	$line( 'inc/ab-sidebars.php بارگذاری شد؟  : ' . ( function_exists( 'javan_seda_sidebar' ) ? 'بله' : 'نه' ) );
	$widgets_ok = true;
	foreach ( javan_seda_widget_id_bases() as $cls ) {
		$has = class_exists( $cls );
		$reg = $has && is_object( $wp_widget_factory ) && isset( $wp_widget_factory->widgets[ $cls ] );
		if ( ! $reg ) {
			$widgets_ok = false;
		}
		$line( sprintf( 'کلاس ابزارک %-22s : class_exists=%s | register_widget=%s', $cls, $has ? 'yes' : 'NO', $reg ? 'yes' : 'NO' ) );
	}
	if ( ! $widgets_ok ) {
		$line( '  ⚠ اگر ابزارک‌ها در پیشخوان دیده می‌شوند ولی در سایت خالی‌اند، همین خط علت است:' );
		$line( '    در ویرایشگر بلوک، ابزارکِ «ثبت‌نشده» روی سایت رشتهٔ خالی تولید می‌کند.' );
	}
	$line( '' );

	$line( '--- ۲) ناحیه‌های ابزارک ثبت‌شده ---' );
	if ( empty( $wp_registered_sidebars ) || ! is_array( $wp_registered_sidebars ) ) {
		$line( 'هیچ ناحیه‌ای ثبت نشده است! (register_sidebar اجرا نشده)' );
	} else {
		foreach ( $wp_registered_sidebars as $sid => $s ) {
			$name = isset( $s['name'] ) ? $s['name'] : '(بی‌نام)';
			$bw   = ( isset( $s['before_widget'] ) && false !== strpos( $s['before_widget'], '%1$s' ) ) ? 'id placeholder: yes' : 'id placeholder: NO';
			$line( sprintf( '%-12s | %s | %s', $sid, $name, $bw ) );
		}
	}
	$line( '' );

	$line( '--- ۳) محتوای ناحیه‌ها در دیتابیس (گزینهٔ sidebars_widgets) ---' );
	$raw = get_option( 'sidebars_widgets' );
	if ( ! is_array( $raw ) ) {
		$line( 'گزینهٔ sidebars_widgets وجود ندارد یا آرایه نیست.' );
	} else {
		foreach ( $raw as $key => $val ) {
			$val   = is_array( $val ) ? $val : array();
			$line( sprintf( '%-22s : %d مورد  [ %s ]', $key, count( $val ), implode( ', ', array_slice( $val, 0, 8 ) ) ) );
		}
		$line( 'arrays_version: ' . ( isset( $raw['arrays_version'] ) ? $raw['arrays_version'] : '(ندارد)' ) );
	}
	$line( 'is_active_sidebar(left_side):  ' . ( function_exists( 'is_active_sidebar' ) && is_active_sidebar( 'left_side' ) ? 'true' : 'false' ) );
	$line( 'is_active_sidebar(left_side2): ' . ( function_exists( 'is_active_sidebar' ) && is_active_sidebar( 'left_side2' ) ? 'true' : 'false' ) );
	$line( 'is_active_sidebar(left_side1): ' . ( function_exists( 'is_active_sidebar' ) && is_active_sidebar( 'left_side1' ) ? 'true' : 'false' ) );
	if ( ! empty( $GLOBALS['javan_seda_sidebar_recovered'] ) ) {
		$line( '♻ ترمیم خواندنی فعال شد و این ابزارک‌ها را برگرداند: ' . implode( ', ', (array) $GLOBALS['javan_seda_sidebar_recovered'] ) );
		$line( '  (در دیتابیس چیزی ذخیره نشده — اگر خواستید دائمی شود، در پیشخوان یک‌بار ناحیه را «ذخیره» کنید.)' );
	}
	$line( '' );

	$line( '--- ۴) ویرایشگر بلوک یا کلاسیک؟ ---' );
	if ( function_exists( 'wp_use_widgets_block_editor' ) ) {
		$line( 'wp_use_widgets_block_editor(): ' . ( wp_use_widgets_block_editor() ? 'true (ویرایشگر بلوک)' : 'false (کلاسیک)' ) );
	}
	if ( isset( $raw['left_side'] ) && is_array( $raw['left_side'] ) ) {
		foreach ( $raw['left_side'] as $wid ) {
			if ( 0 === strpos( (string) $wid, 'block-' ) ) {
				$bid       = (int) str_replace( 'block-', '', $wid );
				$block_raw = get_post( $bid );
				$content   = $block_raw ? $block_raw->post_content : '(پست بلوک پیدا نشد)';
				$line( sprintf( 'بلوک #%d (id %s) => %s', $bid, $wid, preg_replace( '/\s+/', ' ', (string) $content ) ) );
			}
		}
	}
	$line( '' );

	$line( '--- ۵) گزینه‌های قالب که ستون چپ را تحت تأثیر دارند ---' );
	foreach ( array( 'reltag1', 'reltag', 'pppf', 'cm_off' ) as $k ) {
		$v = function_exists( 'ot_get_option' ) ? ot_get_option( $k ) : '(OptionTree نیست)';
		$line( sprintf( 'ot_get_option(%-8s) = %s', "'" . $k . "'", is_scalar( $v ) && '' !== (string) $v ? (string) $v : '(خالی/پیش‌فرض)' ) );
	}
	$line( 'نکته: اگر reltag1 روی «off» نباشد، «نوشته‌های مرتبط» در انتهای متن چاپ می‌شود و ستون چپ فقط ابزارک‌ها را نشان می‌دهد.' );
	$line( '' );

	$line( '--- ۶) بررسی هم‌خوانی فایل‌های روی سرور با بستهٔ 3.0.2 ---' );
	$expected = javan_seda_expected_hashes();
	$tdir     = get_template_directory();
	$mismatch = 0;
	foreach ( $expected as $rel => $hash ) {
		$now = javan_seda_file_hash( $tdir . '/' . $rel );
		$st  = '??';
		if ( 'MISSING' === $now ) {
			$st = 'فایل روی سرور نیست';
		} elseif ( 0 === strpos( $hash, '__' ) ) {
			$st = '(مقدار مرجع تنظیم نشده)';
		} elseif ( $now === $hash ) {
			$st = 'مطابق بسته';
		} else {
			$st = 'متفاوت از بسته  <<< فایل روی هاست قدیمی/ویرایش‌شده است';
		}
		if ( 0 === strpos( $st, 'متفاوت' ) || 0 === strpos( $st, 'فایل' ) ) {
			$mismatch++;
		}
		$line( sprintf( '%-22s %s', $rel, $st ) );
	}
	$ab_self = $tdir . '/inc/ab-sidebars.php';
	if ( is_readable( $ab_self ) ) {
		$line( sprintf( '%-22s حاضر (سایز %d بایت، آخرین تغییر %s)', 'inc/ab-sidebars.php', (int) filesize( $ab_self ), date_i18n( 'Y-m-d H:i', (int) filemtime( $ab_self ) ) ) );
	} else {
		$line( 'inc/ab-sidebars.php    فایل روی سرور نیست  <<< لایهٔ ایمنی غیرفعال است' );
	}
	$line( 'تعداد فایل ناهم‌خوان: ' . $mismatch );
	$line( '' );

	$line( '--- ۷) پلاگین‌های فعال (مشکلات رایج کش / سازندهٔ قالب) ---' );
	$active = (array) get_option( 'active_plugins' );
	$flag   = array( 'wp-super-cache', 'litespeed-cache', 'w3-total-cache', 'wp-rocket', 'wp-fastest-cache', 'autoptimize', 'bwp-minify', 'sg-cachepress', 'wp-smushit', 'elementor', 'jetengine', 'jetpack', 'wpml', 'sitepress' );
	if ( ! $active ) {
		$line( '(فهرست خالی — احتمالاً پلاگین‌ها از مسیر دیگری فعال‌اند / شبکهٔ چندسایتی)' );
	}
	foreach ( $active as $p ) {
		$hit = '';
		foreach ( $flag as $f ) {
			if ( false !== stripos( $p, $f ) ) {
				$hit = '   <<< نیازمند پاک‌سازی کش / بررسی بازنویسی قالب';
				break;
			}
		}
		$line( $p . $hit );
	}
	$line( '' );

	$line( '--- ۸) نتیجه‌گیری خودکار ---' );
	$verdict = 'نامشخص — خروجی را برای توسعه‌دهنده بفرستید.';
	if ( ! function_exists( 'leftsides' ) ) {
		$verdict = '۱) myfunctions.php روی سرور بارگذاری نمی‌شود (یا فایل قدیمی است). لایهٔ ایمنیِ این فایل ناحیه‌ها را ثبت می‌کند؛ فایل را بازگردانی/جایگزین کنید.';
	} elseif ( ! $widgets_ok ) {
		$verdict = '۲) کلاس ابزارک‌های قالب ثبت نیست (inc/widgets.php بارگذاری نشده). ابزارکِ ثبت‌نشده در سایدبارِ بلوکی روی سایت خالی چاپ می‌شود.';
	} elseif ( ! function_exists( 'is_active_sidebar' ) || ! is_active_sidebar( 'left_side' ) ) {
		if ( ! empty( $GLOBALS['javan_seda_sidebar_recovered'] ) ) {
			$verdict = '۳) ابزارک‌ها در «ابزارک‌های غیرفعال» افتاده بودند؛ اکنون به‌صورت خواندنی بازگردانده شدند. برای دائمی‌کردن: پیشخوان ← ابزارک‌ها ← ذخیره.';
		} else {
			$verdict = '۴) ناحیهٔ left_side هیچ ابزارک فعالی ندارد. در پیشخوان، ابزارک را در ناحیهٔ «سایدبار نوشته‌ها — بالا» بگذارید (نه «سایدبار برگه‌ها»).';
		}
	} elseif ( 0 === $mismatch && $wp_query && ! is_singular( 'post' ) ) {
		$verdict = '۵) همه‌چیز سالم است. این گزارش را روی یک «صفحهٔ نوشته» باز کنید تا همان مسیر واقعی بررسی شود.';
	} elseif ( $mismatch > 0 ) {
		$verdict = '۶) فایل‌های روی هاست با بستهٔ مخزن یکسان نیست؛ ابتدا فایل‌های drop-in را دقیقاً مطابق ترتیبِ docs نصب کنید و سپس کش را پاک کنید.';
	} else {
		$verdict = '۷) سایدبار و ابزارک در مسیر PHP سالم‌اند؛ اگر باز در مرورگر نمی‌بینید، کش (لایت‌اسپید/روکت/CDN) یا CSS یک پلاگین است: کش را پاک کنید و با ?abnc=1 دوباره ببینید.';
	}
	$line( $verdict );
	$line( '' );
	$line( 'پایان گزارش. این ابزار فقط‌خواندنی است و هیچ داده‌ای را تغییر نمی‌دهد.' );

	echo esc_html( implode( "\n", $L ) );
	exit;
}
