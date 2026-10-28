<?php
/**
 * ab-glass.php — بارگذارِ «لایۀ شیشه‌ای» (نسخۀ 3.3.0)
 * -----------------------------------------------------------------------------------------
 *  این فایل تنها کارِ خوداش این است که css/ab-glass.css را (که فقط پوسته و عمق بصری دارد)
 *  در صفحۀ عمومی بارگذاری کند. هیچ کوئری، هیچ فیلتر روی the_content/template_include،
 *  هیچ اسکریپت تازه و هیچ تغییر داده‌ای ندارد.
 *
 *  خاموشی کامل لایۀ شیشه‌ای (همه‌چیز به ساختار و رنگ 3.2.4 برمی‌گردد):
 *      define( 'TARA_GLASS_UI', false );        // در wp-config.php
 *  خاموشی فقط blur (سطوح نیمه‌شفافِ مات بدون تارکردگی — برای دستگاه‌های ضعیف‌تر):
 *      define( 'TARA_GLASS_BLUR', false );
 *
 *  اگر فایل css/ab-glass.css روی هاست نباشد، این فایل سکوت می‌کند (نه خطا، نه صفحهٔ سفید).
 *  همه‌چیز با PHP 5.6 سازگار است (بدون ??، بدون static closure، بدون fn()).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TARA_GLASS_UI' ) ) {
	define( 'TARA_GLASS_UI', true );
}
if ( ! defined( 'TARA_GLASS_BLUR' ) ) {
	define( 'TARA_GLASS_BLUR', true );
}

if ( ! function_exists( 'tara_glass_enabled' ) ) {
	function tara_glass_enabled() {
		/* v3.4.1 — «لایۀ شیشۀ‌ای» در تنظیمات قالب (کنترل رابط ← ab_glass_ui).
		   define() در wp-config.php همچنان برنده است؛ اگر inc/ab-controls.php نصب
		   نباشد هم رفتار قبلی دست‌نخورده می‌ماند. */
		if ( function_exists( 'tara_ui_on' ) && ! tara_ui_on( 'ab_glass_ui', true, 'TARA_GLASS_UI' ) ) {
			return false;
		}
		if ( defined( 'TARA_GLASS_UI' ) && false === TARA_GLASS_UI ) {
			return false;
		}
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return false; /* پیشخوان مدیریت به لایۀ ظاهری قالب کار ندارد */
		}
		return true;
	}
}

if ( ! function_exists( 'tara_glass_path' ) ) {
	function tara_glass_path() {
		return trailingslashit( get_template_directory() ) . 'css/ab-glass.css';
	}
}

if ( ! function_exists( 'tara_glass_enqueue' ) ) {
	function tara_glass_enqueue() {
		if ( ! tara_glass_enabled() || ! function_exists( 'wp_enqueue_style' ) ) {
			return;
		}
		$path = tara_glass_path();
		if ( ! is_readable( $path ) ) {
			return; /* فایل نبود ⇒ بی‌صدا رد می‌شویم (ساختار در style.css دست‌نخورده می‌ماند) */
		}
		$ver = @filemtime( $path );
		if ( ! $ver ) {
			$ver = ( function_exists( 'wp_get_theme' ) ) ? wp_get_theme()->get( 'Version' ) : false;
		}
		/**
		 * اولویت ۲۰ ⇒ بعد از استایل خودِ قالب در cascade می‌نشیند؛ پس لایۀ شیشه
		 * بدون !important بر قواعد قبلی می‌برد و حذفش هم بی‌خطر است.
		 */
		wp_enqueue_style( 'tara-glass', trailingslashit( get_template_directory_uri() ) . 'css/ab-glass.css', array(), $ver );
		if ( ( defined( 'TARA_GLASS_BLUR' ) && false === TARA_GLASS_BLUR ) && function_exists( 'wp_add_inline_style' ) ) {
			wp_add_inline_style( 'tara-glass', ':root{--ab-glass-blur:0px}' );
		}
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_enqueue_scripts', 'tara_glass_enqueue', 20 );
}
