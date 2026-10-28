<?php
/**
 * ab-ui-guard.php — نگهبان «لایۀ اختصاصی آهنگ‌بان» (از نسخۀ 3.3.8)
 *
 * مشکل: کل CSS اختصاصی قالب (توکن‌ها، ریل‌ها، پلیر، شناسنامه، کارت‌ها، شب‌رنگ…)
 * در انتهای `style.css` زندگی می‌کند. اگر روزی «بروزرسانی قالب» از پیشخوان یا
 * یک zip مادر، `style.css` را بازنویسی کند، آن لایه کامل می‌رود و سایت برهنه
 * می‌شود — و چون فایل‌ها در آپلودر WordPress بازنویسی می‌شوند، «فقط style.css را
 * برگردانید» هم همیشه ممکن نیست.
 *
 * راه‌حل این فایل: کپیِ همان ناحیه در `css/ab-ui.css` نگه داشته شده و این گارد
 * *فقط وقتی* آن را enqueue می‌کند که مارکر `ab-ui-layer` در `style.css` پیدا نشود.
 * در حالت عادی هیچ خروجی/هیچ درخواست اضافه‌ای ندارد (نتایج بررسی در یک ترنزینت
 * ۱۲ ساعته می‌نشیند، نه در هر بار رندر).
 *
 * نکتهٔ دلخواه (که عمداً در این نسخه انجام نشده): می‌توانید بلوک‌های این ناحیه را
 * از style.css حذف کنید و کلاً به ab-ui.css منتقل کنید تا style.css به اندازۀ
 * قالب اصلی برگردد. چون گارد بر اساس «نبودنِ مارکر» کار می‌کند، بعد از آن کار
 * هم همه‌چیز درست می‌ماند — ولی این کار را فقط وقتی انجام دهید که هر دو فایل را
 * با هم آپلود می‌کنید (اگر یکی جا بیفتد، سایت بدون استایل می‌شود).
 *
 * خاموشی:  define( 'JAVANSEDA_UI_GUARD', false );
 * بررسی فوری (فقط مدیر):  آدرس سایت + ?ab_uiguard=1  ⇒ کش وضعیت پاک می‌شود.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( defined( 'JAVANSEDA_UI_GUARD' ) && ! JAVANSEDA_UI_GUARD ) {
	return;
}
/* v3.4.1 — خاموش‌کردن گارد از تنظیمات قالب (کنترل رابط ← ab_ui_guard) */
if ( function_exists( 'javan_seda_ui_on' ) && ! javan_seda_ui_on( 'ab_ui_guard', true, 'JAVANSEDA_UI_GUARD' ) ) {
	return;
}

if ( ! function_exists( 'javan_seda_ui_layer_cache_key' ) ) {
	function javan_seda_ui_layer_cache_key() {
		return 'ab_ui_layer_v1';
	}
}

if ( ! function_exists( 'javan_seda_ui_layer_flush' ) ) {
	function javan_seda_ui_layer_flush() {
		if ( function_exists( 'delete_transient' ) ) {
			delete_transient( javan_seda_ui_layer_cache_key() );
		}
	}
}

if ( ! function_exists( 'javan_seda_ui_layer_marker_present' ) ) {
	/**
	 * آیا لایه هنوز داخل style.css هست؟
	 *
	 * @param bool $force خواندن دوباره از دیسک و تازه‌کردن کش.
	 * @return bool true = style.css مارکر را دارد (گارد لازم نیست).
	 */
	function javan_seda_ui_layer_marker_present( $force = false ) {
		$key = javan_seda_ui_layer_cache_key();
		if ( ! $force && function_exists( 'get_transient' ) ) {
			$cached = get_transient( $key );
			if ( 'yes' === $cached || 'no' === $cached ) {
				return ( 'yes' === $cached );
			}
		}
		$file = trailingslashit( get_stylesheet_directory() ) . 'style.css';
		if ( ! is_readable( $file ) ) {
			$file = trailingslashit( get_template_directory() ) . 'style.css';
		}
		$present = false;
		if ( is_readable( $file ) ) {
			$css     = @file_get_contents( $file );
			$present = ( is_string( $css ) && false !== strpos( $css, 'ab-ui-layer' ) );
		}
		if ( function_exists( 'set_transient' ) ) {
			set_transient( $key, $present ? 'yes' : 'no', 12 * HOUR_IN_SECONDS );
		}
		return $present;
	}
}

if ( ! function_exists( 'javan_seda_ui_layer_enqueue' ) ) {
	function javan_seda_ui_layer_enqueue() {
		if ( javan_seda_ui_layer_marker_present() ) {
			return; /* style.css سالم است؛ هیچ چیزی اضافه نمی‌شود */
		}
		$dir = trailingslashit( get_stylesheet_directory() ) . 'css/ab-ui.css';
		if ( ! is_readable( $dir ) ) {
			$dir = trailingslashit( get_template_directory() ) . 'css/ab-ui.css';
		}
		if ( ! is_readable( $dir ) ) {
			return; /* فایل کپی وجود ندارد — کاری از دستمان برنمی‌آید */
		}
		$dep = array();
		if ( function_exists( 'wp_style_is' ) && ( wp_style_is( 'javanSedaMainStyle', 'registered' ) || wp_style_is( 'javanSedaMainStyle', 'enqueued' ) ) ) {
			$dep = array( 'javanSedaMainStyle' );
		}
		$theme = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
		$ver   = ( $theme && $theme->get( 'Version' ) ) ? $theme->get( 'Version' ) : false;
		$url   = trailingslashit( get_stylesheet_directory_uri() ) . 'css/ab-ui.css';
		if ( 0 !== strpos( $url, 'http' ) && function_exists( 'get_template_directory_uri' ) ) {
			$url = trailingslashit( get_template_directory_uri() ) . 'css/ab-ui.css';
		}
		wp_enqueue_style( 'ab-ui-layer', $url, $dep, $ver );
	}
	add_action( 'wp_enqueue_scripts', 'javan_seda_ui_layer_enqueue', 25 );
	add_action( 'after_switch_theme', 'javan_seda_ui_layer_flush' );
	add_action( 'upgrader_process_complete', 'javan_seda_ui_layer_flush' );
}

if ( ! function_exists( 'javan_seda_ui_layer_nocache' ) ) {
	function javan_seda_ui_layer_nocache() {
		if ( isset( $_GET['ab_uiguard'] ) && current_user_can( 'manage_options' ) ) {
			javan_seda_ui_layer_flush();
			javan_seda_ui_layer_marker_present( true );
		}
	}
	add_action( 'init', 'javan_seda_ui_layer_nocache' );
}
