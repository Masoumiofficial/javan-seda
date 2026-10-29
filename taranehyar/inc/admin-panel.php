<?php
/**
 * پنل مدیریت «ترانه یار» — منطق
 *
 * چرا این فایل هست؟
 * قالب قدیمی تنظیماتش را در «نمایش ← تنظیمات قالب» پنهان می‌کرد و کاربر برای
 * هر تغییر باید دنبال منو می‌گشت. این پنل یک منوی مستقلِ فارسی در نوار اصلی
 * پیشخوان می‌سازد و همان صفحهٔ تنظیمات OptionTree را زیر آن می‌آورد.
 *
 * قواعدی که شکسته نشده‌اند:
 *   • محل ذخیرهٔ داده‌ها همان `option_tree` است؛ هیچ مهاجرت یا کلید تازه‌ای نیست.
 *   • صفحهٔ OptionTree با فیلترهای رسمیِ خودش جابه‌جا می‌شود، نه با دست‌کاری هسته.
 *   • همهٔ بررسی‌ها فقط «خواندن» هستند؛ تنها نوشتن‌ها: پاک‌کردن حافظهٔ آمارِ خودمان.
 *
 * ساختار فایل:
 *   ۱) منو و زیرمنوها
 *   ۲) جابه‌جایی صفحهٔ تنظیمات OptionTree
 *   ۳) دارایی‌های پیشخوان
 *   ۴) ابزارک پیشخوان وردپرس
 *   ۵) کمکی‌ها: آمار، سلامت، بخش‌ها، گزارش سیستم
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * ۱) منو و زیرمنوها
 * ---------------------------------------------------------------------- */

/**
 * نامکِ منو. در همه‌جا از همین تابع استفاده می‌شود تا یک منبع حقیقت باشد.
 *
 * @return string
 */
function taranehyar_panel_slug() {
	return 'taranehyar';
}

/**
 * سطح دسترسی. همان سطحی که OptionTree و صفحهٔ تنظیمات قالب لازم دارند.
 *
 * @param string $cap پیش‌فرض.
 * @return string
 */
function taranehyar_panel_capability( $cap = 'edit_theme_options' ) {
	return (string) apply_filters( 'taranehyar_panel_capability', $cap );
}

/**
 * آیکون منو: یک نت/پخش‌کنندهٔ ساده به‌صورت SVG درون‌خطی.
 * رنگ خاکستریِ استاندارد پیشخوان تا با تم‌های رنگی وردپرس هماهنگ بماند.
 *
 * @return string
 */
function taranehyar_panel_icon() {
	/* v3.7.4 — آیکون «نُت موسیقی»ی ساده و خوانا در منوی ۲۰px پیشخوان */
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">'
		. '<circle cx="6.4" cy="14.6" r="3.1" fill="#a7aaad"/>'
		. '<rect x="8.6" y="3.4" width="1.9" height="11.6" rx=".9" fill="#a7aaad"/>'
		. '<path d="M10.5 3.4c2.9.6 4.9 2.6 5.2 5.6" fill="none" stroke="#a7aaad" stroke-width="1.9" stroke-linecap="round"/>'
		. '</svg>';

	return 'data:image/svg+xml;base64,' . base64_encode( $svg );
}

/**
 * ثبت منو و زیرمنوها.
 *
 * اولویت ۹: قبل از OptionTree (که در اولویت ۱۰ زیرمنویش را اضافه می‌کند)،
 * چون زیرمنو بدون وجود منوی پدر ساخته نمی‌شود.
 */
function taranehyar_admin_menu() {
	$cap  = taranehyar_panel_capability();
	$slug = taranehyar_panel_slug();

	$hook = add_menu_page(
		'ترانه یار',
		'ترانه یار',
		$cap,
		$slug,
		'taranehyar_render_dashboard',
		taranehyar_panel_icon(),
		59
	);
	if ( $hook ) {
		$GLOBALS['taranehyar_panel_hooks'][] = $hook;
	}

	/* پیشخوان: همان منو را به «پیشخوان» تغییر نام می‌دهد و اولین زیرمنو می‌شود. */
	$hook = add_submenu_page( $slug, 'پیشخوان ترانه یار', 'پیشخوان', $cap, $slug, 'taranehyar_render_dashboard' );
	if ( $hook ) {
		$GLOBALS['taranehyar_panel_hooks'][] = $hook;
	}

	/* صفحهٔ تنظیمات را OptionTree خودش در اولویت ۱۰ ثبت می‌کند
	   (به‌لطف فیلتر پایین در بخش ۲) — اینجا دوباره ساخته نمی‌شود. */

	$hook = add_submenu_page( $slug, 'ابزارها و گزارش سیستم', 'ابزارها', $cap, 'taranehyar-tools', 'taranehyar_render_tools' );
	if ( $hook ) {
		$GLOBALS['taranehyar_panel_hooks'][] = $hook;
	}

	$hook = add_submenu_page( $slug, 'دربارهٔ ترانه یار', 'درباره', $cap, 'taranehyar-about', 'taranehyar_render_about' );
	if ( $hook ) {
		$GLOBALS['taranehyar_panel_hooks'][] = $hook;
	}

	/* میان‌بُرها: صفحه‌های موجود وردپرس که معمولاً همراه تنظیمات لازم می‌شوند. */
	add_submenu_page( $slug, 'منوها', 'منوها', $cap, 'nav-menus.php' );
	add_submenu_page( $slug, 'ابزارک‌ها', 'ابزارک‌ها', $cap, 'widgets.php' );
	add_submenu_page( $slug, 'سربرگ سایت', 'سربرگ سایت', $cap, 'customize.php' );
}
add_action( 'admin_menu', 'taranehyar_admin_menu', 9 );

/**
 * چیدن زیرمنوها به ترتیبی که برای کاربر منطقی است:
 * پیشخوان ← تنظیمات قالب ← ابزارها ← درباره ← میان‌بُرها.
 */
function taranehyar_panel_order_submenu() {
	global $submenu;

	$parent = taranehyar_panel_slug();
	if ( empty( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) {
		return;
	}

	$order = array(
		$parent,
		'ot-theme-options',
		'taranehyar-tools',
		'taranehyar-about',
		'nav-menus.php',
		'widgets.php',
		'customize.php',
	);
	$rank  = array_flip( $order );

	usort(
		$submenu[ $parent ],
		function ( $a, $b ) use ( $rank ) {
			$ra = isset( $a[2], $rank[ $a[2] ] ) ? $rank[ $a[2] ] : 99;
			$rb = isset( $b[2], $rank[ $b[2] ] ) ? $rank[ $b[2] ] : 99;
			return $ra - $rb;
		}
	);
}
add_action( 'admin_menu', 'taranehyar_panel_order_submenu', 99 );

/* -------------------------------------------------------------------------
 * ۲) جابه‌جایی صفحهٔ تنظیمات OptionTree زیر همین منو
 * ---------------------------------------------------------------------- */

/**
 * پدرِ صفحهٔ تنظیمات را عوض می‌کند. OptionTree خودش این را با فیلتر
 * `ot_theme_options_parent_slug` اجازه داده است؛ slug صفحه دست نمی‌خورد
 * (`ot-theme-options`) تا لینک‌ها، فرم‌ها و ذخیره‌سازی سالم بمانند.
 *
 * @param string $parent پدر پیش‌فرض (`themes.php`).
 * @return string
 */
function taranehyar_ot_parent_slug( $parent ) {
	unset( $parent );
	return taranehyar_panel_slug();
}
add_filter( 'ot_theme_options_parent_slug', 'taranehyar_ot_parent_slug' );

/* عنوان‌ها هم فارسی‌تر و روشن‌تر می‌شوند. */
add_filter(
	'ot_theme_options_page_title',
	function ( $title ) {
		unset( $title );
		return 'تنظیمات ترانه یار';
	}
);

add_filter(
	'ot_theme_options_menu_title',
	function ( $title ) {
		unset( $title );
		return 'تنظیمات قالب';
	}
);

/**
 * دکمهٔ پنجرهٔ رسانه در صفحهٔ تنظیمات.
 *
 * OptionTree متنِ «Insert into post» را فقط وقتی عوض می‌کرد که صفحه زیر
 * «نمایش» باشد (`$pagenow == themes.php`). حالا صفحه زیر منوی «ترانه یار» است،
 * پس خودمان و فقط در همان صفحه ترجمه‌اش می‌کنیم.
 *
 * @param string $translation ترجمهٔ فعلی.
 * @param string $text        متن اصلی.
 * @param string $domain      دامنهٔ ترجمه.
 * @return string
 */
function taranehyar_panel_media_button( $translation, $text, $domain ) {
	if ( 'default' !== $domain || 'Insert into post' !== $text ) {
		return $translation;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'ot-theme-options' !== $page ) {
		return $translation;
	}

	return 'درج در تنظیمات';
}
add_filter( 'gettext', 'taranehyar_panel_media_button', 20, 3 );

/* -------------------------------------------------------------------------
 * ۳) دارایی‌های پیشخوان (فقط در صفحه‌های همین پنل)
 * ---------------------------------------------------------------------- */

/**
 * استایل و اسکریپت پنل را فقط در صفحه‌های خودش سوار می‌کند.
 *
 * @param string $hook شناسهٔ صفحهٔ جاری.
 */
function taranehyar_panel_assets( $hook ) {
	$hooks = isset( $GLOBALS['taranehyar_panel_hooks'] ) ? (array) $GLOBALS['taranehyar_panel_hooks'] : array();

	if ( ! in_array( $hook, $hooks, true ) ) {
		return;
	}

	wp_enqueue_style(
		'taranehyar-panel',
		TARANEHYAR_URI . '/css/admin-panel.css',
		array( 'dashicons' ),
		TARANEHYAR_VERSION
	);

	wp_enqueue_script(
		'taranehyar-panel',
		TARANEHYAR_URI . '/js/admin-panel.js',
		array(),
		TARANEHYAR_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'taranehyar_panel_assets' );

/**
 * میان‌بُر «تنظیمات ترانه یار» در نوار مدیریت سایت.
 *
 * @param WP_Admin_Bar $bar نوار مدیریت.
 */
function taranehyar_panel_admin_bar( $bar ) {
	if ( ! is_admin_bar_showing() || ! current_user_can( taranehyar_panel_capability() ) ) {
		return;
	}

	$bar->add_node(
		array(
			'id'    => 'taranehyar-panel',
			'title' => 'ترانه یار',
			'href'  => admin_url( 'admin.php?page=' . taranehyar_panel_slug() ),
		)
	);
}
add_action( 'admin_bar_menu', 'taranehyar_panel_admin_bar', 90 );

/* -------------------------------------------------------------------------
 * ۴) ابزارک پیشخوان وردپرس
 * ---------------------------------------------------------------------- */

function taranehyar_panel_dashboard_widget() {
	if ( ! current_user_can( taranehyar_panel_capability() ) ) {
		return;
	}

	wp_add_dashboard_widget( 'taranehyar_panel_widget', 'ترانه یار — نگاه سریع', 'taranehyar_render_dashboard_widget' );
}
add_action( 'wp_dashboard_setup', 'taranehyar_panel_dashboard_widget' );

/* -------------------------------------------------------------------------
 * ۵) کمکی‌ها
 * ---------------------------------------------------------------------- */

/**
 * شناسهٔ منوها و صفحه‌ها برای ساخت لینک.
 *
 * @param string $page نامک صفحه.
 * @return string
 */
function taranehyar_panel_url( $page = '' ) {
	$url = admin_url( 'admin.php?page=' . ( '' === $page ? taranehyar_panel_slug() : $page ) );

	return esc_url( $url );
}

/**
 * جمع بازدید همهٔ نوشته‌ها (فقط خواندن).
 *
 * @return int
 */
function taranehyar_total_views() {
	global $wpdb;

	if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || empty( $wpdb->postmeta ) ) {
		return 0;
	}

	/* CAST تا اگر مقداری غیرعددی در متا بود، محاسبه نشکند. */
	$sum = $wpdb->get_var( "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = 'views'" );

	return max( 0, (int) $sum );
}

/**
 * آمار کارت‌های بالای پیشخوان. ۱۵ دقیقه حافظه‌گذاری می‌شود تا هر بار
 * بازکردن پنل، دیتابیس را برای جمعِ بازدید نگردد.
 *
 * @param bool $fresh نادیده‌گرفتن حافظه.
 * @return array
 */
function taranehyar_panel_stats( $fresh = false ) {
	$key = 'taranehyar_panel_stats';

	if ( ! $fresh ) {
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$posts = wp_count_posts( 'post' );
	$cmmts = wp_count_comments();
	$users = count_users();

	$stats = array(
		'posts'    => isset( $posts->publish ) ? (int) $posts->publish : 0,
		'views'    => taranehyar_total_views(),
		'comments' => isset( $cmmts->approved ) ? (int) $cmmts->approved : 0,
		'users'    => isset( $users['total_users'] ) ? (int) $users['total_users'] : 0,
	);

	$ttl = defined( 'MINUTE_IN_SECONDS' ) ? 15 * MINUTE_IN_SECONDS : 900;
	set_transient( $key, $stats, $ttl );

	return $stats;
}

/**
 * بخش‌های صفحهٔ تنظیمات به‌همراه تعداد گزینه‌های هر بخش.
 * شناسه‌ها باید با `inc/theme-options.php` یکی باشند.
 *
 * @return array
 */
function taranehyar_panel_sections() {
	$saved    = get_option( 'option_tree_settings', array() );
	$settings = ( is_array( $saved ) && isset( $saved['settings'] ) && is_array( $saved['settings'] ) ) ? $saved['settings'] : array();

	$counts = array();
	foreach ( $settings as $setting ) {
		if ( isset( $setting['section'] ) ) {
			$id            = (string) $setting['section'];
			$counts[ $id ] = isset( $counts[ $id ] ) ? $counts[ $id ] + 1 : 1;
		}
	}

	$sections = array(
		array(
			'id'    => 'general',
			'title' => 'همگانی',
			'icon'  => 'dashicons-admin-generic',
			'desc'  => 'نشانک، لوگو، تم تاریک و عنوانِ جایگزینِ سربرگ.',
		),
		array(
			'id'    => 'brand',
			'title' => 'رنگ‌ها و هویت',
			'icon'  => 'dashicons-art',
			'desc'  => 'رنگ اصلی و فرعی سایت و رنگ منو/جست‌وجوی گوشی.',
		),
		array(
			'id'    => 'homes',
			'title' => 'صفحهٔ اصلی',
			'icon'  => 'dashicons-admin-home',
			'desc'  => 'اسلایدر، باکس‌های مطالب، هنرمندان و عنوانِ بخش‌ها.',
		),
		array(
			'id'    => 'dl_pages',
			'title' => 'صفحهٔ دانلود آهنگ',
			'icon'  => 'dashicons-download',
			'desc'  => 'چیدمان صفحهٔ آهنگ، پخش‌کننده و متن‌های کنار آن.',
		),
		array(
			'id'    => 'ab_ui',
			'title' => 'ظاهر و رفتار ترانه یار',
			'icon'  => 'dashicons-layout',
			'desc'  => 'پلیر، کارت‌ها، حالت شب و جزئیات ظاهریِ همین قالب.',
		),
		array(
			'id'    => 'radio',
			'title' => 'رادیو و دی‌جی',
			'icon'  => 'dashicons-controls-volumeon',
			'desc'  => 'میکسِ خودکار، وکالِ معرفیِ سایت، منبعِ آهنگ‌ها و طولِ کراس‌فید.',
		),
		array(
			'id'    => 'footer',
			'title' => 'پانوشت',
			'icon'  => 'dashicons-editor-insertmore',
			'desc'  => 'ستون‌های پانوشت، دسته‌های پرطرفدار و متنِ پایانی.',
		),
		array(
			'id'    => 'socials',
			'title' => 'شبکه‌های اجتماعی',
			'icon'  => 'dashicons-share',
			'desc'  => 'آدرسِ شبکه‌های اجتماعی برای آیکون‌های سربرگ و پانوشت.',
		),
		array(
			'id'    => 'ads1',
			'title' => 'تبلیغات',
			'icon'  => 'dashicons-megaphone',
			'desc'  => 'کدهای بنر و جایگاه‌های تبلیغاتیِ قالب.',
		),
		array(
			'id'    => 'about',
			'title' => 'دربارهٔ سایت',
			'icon'  => 'dashicons-info',
			'desc'  => 'متن «درباره ما» برای پانوشت و صفحهٔ هنرمند — یک‌جا ویرایش می‌شود.',
		),
		array(
			'id'    => 'dev',
			'title' => 'توسعه دهنده',
			'icon'  => 'dashicons-editor-code',
			'desc'  => 'تنظیمات فنی و ریز؛ بیشترشان پیش‌فرض خوبی دارند.',
		),
	);

	foreach ( $sections as $index => $section ) {
		$sections[ $index ]['count'] = isset( $counts[ $section['id'] ] ) ? (int) $counts[ $section['id'] ] : 0;
	}

	return $sections;
}


/**
 * تاکسونومی‌های موسیقی + شمارش ترم‌ها (فقط خواندن).
 *
 * @return array
 */
function taranehyar_panel_taxonomies() {
	$list = array();

	if ( ! function_exists( 'tara_music_taxonomies' ) ) {
		return $list;
	}

	foreach ( tara_music_taxonomies() as $config ) {
		$taxonomy = (string) $config['taxonomy'];
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$count = wp_count_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );

		$list[] = array(
			'taxonomy' => $taxonomy,
			'title'    => $config['plural'],
			'singular' => $config['singular'],
			'icon'     => $config['icon'],
			'count'    => is_wp_error( $count ) ? 0 : (int) $count,
			'url'      => admin_url( 'edit-tags.php?taxonomy=' . $taxonomy . '&post_type=post' ),
		);
	}

	return $list;
}

/**
 * آیا فایل لایسنس/رمزگذاری‌شده‌ای از نسخهٔ قدیمی باقی مانده است؟
 *
 * نسخهٔ قدیمیِ قالب یک فایل `RTL_License_*.php` داشت که برای فعال‌سازی به
 * سرور سازنده وصل می‌شد. در ۳.۷.x هیچ‌کدام از آن فایل‌ها لازم نیست و هیچ
 * فایلی هم آن‌ها را صدا نمی‌زند؛ اگر نسخهٔ قدیمی روی هاست مانده باشد فقط
 * فضا می‌گیرد. این تابع فقط «وجود» را گزارش می‌کند.
 *
 * @return string مسیر فایلِ باقی‌مانده، یا رشتهٔ خالی.
 */
function taranehyar_license_leftover() {
	$patterns = array(
		TARANEHYAR_DIR . '/RTL_License_*.php',
		TARANEHYAR_DIR . '/rtl_license_*.php',
		TARANEHYAR_DIR . '/inc/RTL_License_*.php',
	);
	foreach ( $patterns as $pattern ) {
		$found = glob( $pattern );
		if ( ! empty( $found ) ) {
			return (string) $found[0];
		}
	}
	return '';
}

/**
 * پاک‌سازی یک‌بارهٔ فایل‌های لایسنسِ باقی‌مانده (v3.7.5)
 *
 * کاربر صریحاً خواست «آن لایسنس کلاً از بین برود». این کار فقط روی همان
 * الگوی شناخته‌شده انجام می‌شود، یک‌بار در عمر نصب اجرا می‌شود و اگر
 * فایل قابل حذف نبود، چیزی خراب نمی‌کند.
 */
function taranehyar_purge_license() {
	if ( get_option( 'taranehyar_license_purged' ) ) {
		return;
	}

	$patterns = array(
		TARANEHYAR_DIR . '/RTL_License_*.php',
		TARANEHYAR_DIR . '/rtl_license_*.php',
		TARANEHYAR_DIR . '/inc/RTL_License_*.php',
	);
	foreach ( $patterns as $pattern ) {
		$found = glob( $pattern );
		foreach ( (array) $found as $file ) {
			if ( is_writable( $file ) ) {
				@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- حذف اختیاری
			}
		}
	}

	update_option( 'taranehyar_license_purged', 1 );
}
add_action( 'admin_init', 'taranehyar_purge_license' );

/**
 * بررسی‌های سلامتِ فقط‌خواندنی. هر مورد: عنوان، وضعیت و توضیح کوتاه.
 *
 * @return array
 */
function taranehyar_panel_health() {
	$options_key = taranehyar_options_id();
	$options     = get_option( $options_key );
	$count       = is_array( $options ) ? count( $options ) : 0;

	$checks = array(
		array(
			'label' => 'هستهٔ خوانا',
			'ok'    => function_exists( 'taranehyar_setup' ) && function_exists( 'taranehyar_assets' ),
			'note'  => 'functions.php از کد رمزگذاری‌شده به کد باز تبدیل شده است.',
		),
		array(
			'label' => 'فایل‌های اضافی',
			'ok'    => ! taranehyar_license_leftover(),
			'note'  => 'هیچ فایل لایسنس/رمزگذاری‌شده‌ای در پوشهٔ قالب نیست؛ قالب به هیچ سرور بیرونی وصل نمی‌شود.',
		),
		array(
			'label' => 'OptionTree',
			'ok'    => function_exists( 'ot_get_option' ) && class_exists( 'OT_Loader' ),
			'note'  => 'موتور تنظیمات، همراهِ خودِ قالب بارگذاری می‌شود.',
		),
		array(
			'label' => 'حافظهٔ تنظیمات',
			'ok'    => $count > 0,
			'note'  => $count > 0
				? sprintf( 'کلید «%s» با %s گزینه ذخیره‌شده.', $options_key, number_format_i18n( $count ) )
				: sprintf( 'کلید «%s» هنوز خالی است؛ اولین ذخیره آن را می‌سازد.', $options_key ),
		),
		array(
			'label' => 'تصاویر شاخص',
			'ok'    => current_theme_supports( 'post-thumbnails' ),
			'note'  => 'برای کارت‌های آهنگ و آواتارِ هنرمندان لازم است.',
		),
		array(
			'label' => 'منوی اصلی',
			'ok'    => function_exists( 'has_nav_menu' ) && has_nav_menu( 'top_menu' ),
			'note'  => 'منوی «بالای سایت» باید به یک منو تخصیص داده شود.',
		),
		array(
			'label' => 'نسخهٔ PHP',
			'ok'    => version_compare( PHP_VERSION, '7.4', '>=' ),
			'note'  => 'نسخهٔ فعلی ' . PHP_VERSION . ' — کمینهٔ لازم ۷٫۴.',
		),
	);

	return $checks;
}

/**
 * گزارش متنی سیستم برای پشتیبانی. فقط خواندن.
 *
 * @return string
 */
function taranehyar_system_report() {
	global $wpdb, $wp_version;

	$theme   = wp_get_theme();
	$options = get_option( taranehyar_options_id() );
	$lines   = array();

	$lines[] = '— ترانه یار — گزارش سیستم —';
	$lines[] = 'نسخهٔ قالب: ' . TARANEHYAR_VERSION;
	$lines[] = 'نسخهٔ وردپرس: ' . $wp_version;
	$lines[] = 'نسخهٔ PHP: ' . PHP_VERSION;
	$lines[] = 'نسخهٔ MySQL: ' . ( isset( $wpdb ) && is_object( $wpdb ) ? $wpdb->db_version() : '—' );
	$lines[] = 'آدرس سایت: ' . home_url( '/' );
	$lines[] = 'زبان: ' . get_bloginfo( 'language' );
	$lines[] = 'راست‌چین: ' . ( is_rtl() ? 'بله' : 'خیر' );
	$lines[] = 'قالب فعال: ' . $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' );
	$lines[] = 'کلید تنظیمات: ' . taranehyar_options_id();
	$lines[] = 'تعداد گزینه‌های ذخیره‌شده: ' . ( is_array( $options ) ? count( $options ) : 0 );
	$lines[] = 'نوشته‌های منتشرشده: ' . (int) wp_count_posts( 'post' )->publish;
	$lines[] = 'ساختار پیوند: ' . get_option( 'permalink_structure' );
	$lines[] = '';

	$lines[] = 'افزونه‌های فعال:';
	$plugins = (array) get_option( 'active_plugins', array() );
	if ( empty( $plugins ) ) {
		$lines[] = '  (هیچ)';
	} else {
		foreach ( $plugins as $plugin ) {
			$lines[] = '  - ' . $plugin;
		}
	}

	return implode( "\n", $lines );
}

/**
 * پاک‌کردن حافظهٔ آمار (تنها نوشتنِ پنل و فقط برای ترنزینت خودش).
 */
function taranehyar_panel_clear_stats() {
	if ( empty( $_POST['taranehyar_clear_stats'] ) ) {
		return;
	}
	if ( ! current_user_can( taranehyar_panel_capability() ) ) {
		return;
	}

	check_admin_referer( 'taranehyar_clear_stats' );
	delete_transient( 'taranehyar_panel_stats' );

	wp_safe_redirect( admin_url( 'admin.php?page=taranehyar-tools&cleared=1' ) );
	exit;
}
add_action( 'admin_init', 'taranehyar_panel_clear_stats' );

/* نمای صفحه‌ها جدا نگه داشته شده تا این فایل خوانا بماند. */
require_once TARANEHYAR_DIR . '/inc/admin-panel-views.php';
