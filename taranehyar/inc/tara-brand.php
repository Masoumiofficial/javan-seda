<?php
/**
 * inc/tara-brand.php — رنگ‌های سایت و رنگ منو/جست‌وجوی موبایل (v3.7.7)
 * ==========================================================================
 * چرا این فایل؟
 *   رنگ اصلی و فرعی سایت، رنگ نوار و کشوی منوی موبایل و رنگ کادر جست‌وجوی
 *   موبایل تا امروز فقط داخل فایل‌های CSS ثابت بودند؛ برای عوض‌کردنشان باید
 *   کد ویرایش می‌شد. اکنون همه از «تنظیمات قالب ← رنگ‌ها و هویت» کنترل می‌شوند
 *   و به شکل متغیرهای CSS در `wp_head` چاپ می‌شوند (بدون دست‌زدن به فایل‌ها).
 *
 * نکتهٔ مهم: هیچ‌کدام از این‌ها داده یا ساختار صفحه را عوض نمی‌کنند؛ فقط
 * چند متغیر CSS و چند قاعدهٔ رنگ تولید می‌شود. اگر همه خالی بمانند، همان
 * رنگ‌های پیش‌فرض قبلی استفاده می‌شود (سایت دست‌نخورده).
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_brand_option' ) ) {
	/**
	 * خواندن یک تنظیم رنگ با پیش‌فرض.
	 *
	 * @param string $key     شناسهٔ تنظیم.
	 * @param string $default پیش‌فرض.
	 * @return string
	 */
	function tara_brand_option( $key, $default = '' ) {
		$value = function_exists( 'ot_get_option' ) ? ot_get_option( $key ) : '';
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( '' === $value ) {
			return $default;
		}

		/* فقط رنگ معتبر (hex / rgb / hsl) پذیرفته می‌شود تا CSS خراب نشود */
		if ( ! preg_match( '/^(#[0-9a-f]{3,8}|rgba?\([^)]*\)|hsla?\([^)]*\))$/i', $value ) ) {
			return $default;
		}

		return $value;
	}
}

if ( ! function_exists( 'tara_hex_rgb' ) ) {
	/**
	 * تبدیل hex به آرایهٔ RGB (برای ساختن نسخهٔ روشن/تیره).
	 *
	 * @param string $hex رنگ.
	 * @return array{0:int,1:int,2:int}
	 */
	function tara_hex_rgb( $hex ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 8 === strlen( $hex ) ) {
			$hex = substr( $hex, 0, 6 );
		}

		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return array( 24, 90, 219 ); // رنگ پیش‌فرض قالب
		}

		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	}
}

if ( ! function_exists( 'tara_tone' ) ) {
	/**
	 * نسخهٔ روشن‌تر/تیره‌تر یک رنگ hex.
	 *
	 * @param string $hex   رنگ.
	 * @param int    $delta مثبت = روشن‌تر، منفی = تیره‌تر (۰ تا ۱۰۰).
	 * @return string
	 */
	function tara_tone( $hex, $delta = 0 ) {
		list( $r, $g, $b ) = tara_hex_rgb( $hex );

		$adjust = static function ( $c ) use ( $delta ) {
			$c += (int) ( $delta * 2.55 ); // -100..100 → -255..255
			return max( 0, min( 255, (int) round( $c ) ) );
		};

		return sprintf( '#%02x%02x%02x', $adjust( $r ), $adjust( $g ), $adjust( $b ) );
	}
}

if ( ! function_exists( 'tara_rgba' ) ) {
	/**
	 * نسخهٔ شفافِ یک رنگ hex.
	 *
	 * @param string $hex   رنگ.
	 * @param float  $alpha شفافیت ۰ تا ۱.
	 * @return string
	 */
	function tara_rgba( $hex, $alpha = 1 ) {
		list( $r, $g, $b ) = tara_hex_rgb( $hex );
		return sprintf( 'rgba(%d,%d,%d,%.3F)', $r, $g, $b, max( 0, min( 1, (float) $alpha ) ) );
	}
}

if ( ! function_exists( 'tara_brand_palette' ) ) {
	/**
	 * پالت نهایی سایت (با پیش‌فرض‌های همان قالب).
	 *
	 * @return array
	 */
	function tara_brand_palette() {
		$primary   = tara_brand_option( 'brand_primary', '#185ADB' );
		$secondary = tara_brand_option( 'brand_secondary', '#6d28d9' );

		return apply_filters(
			'tara_brand_palette',
			array(
				'primary'         => $primary,
				'primary_dark'    => tara_tone( $primary, -16 ),
				'primary_soft'    => tara_rgba( $primary, 0.10 ),
				'secondary'       => $secondary,
				'secondary_dark'  => tara_tone( $secondary, -14 ),
				'mob_bar_bg'      => tara_brand_option( 'tara_mob_bar_bg', '#ffffff' ),
				'mob_btn_bg'      => tara_brand_option( 'tara_mob_btn_bg', '#111111' ),
				'mob_bar_text'    => tara_brand_option( 'tara_mob_bar_text', '#ffffff' ),
				'mob_menu_bg'     => tara_brand_option( 'tara_mob_menu_bg', '#222222' ),
				'mob_menu_text'   => tara_brand_option( 'tara_mob_menu_text', '#ffffff' ),
				'mob_search_bg'   => tara_brand_option( 'tara_mob_search_bg', '#ffffff' ),
				'mob_search_text' => tara_brand_option( 'tara_mob_search_text', '#333333' ),
			)
		);
	}
}

if ( ! function_exists( 'tara_brand_css' ) ) {
	/**
	 * چاپ متغیرها و قاعده‌های رنگ — فقط در بخش عمومی سایت.
	 */
	function tara_brand_css() {
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return;
		}

		$c    = tara_brand_palette();
		$vars = array();

		/* رنگ اصلی/فرعی: هم متغیرهای قدیمی قالب، هم متغیرهای لایهٔ تازه */
		$vars[] = '--ab-blue:' . $c['primary'];
		$vars[] = '--ab-blue-dark:' . $c['primary_dark'];
		$vars[] = '--ab-blue-accent:' . $c['primary'];
		$vars[] = '--ab-blue-soft:' . $c['primary_soft'];
		$vars[] = '--ty-accent:' . $c['primary'];
		$vars[] = '--ty-accent-dark:' . $c['primary_dark'];
		$vars[] = '--ty-accent-soft:' . $c['primary_soft'];
		$vars[] = '--ty-accent-2:' . $c['secondary'];
		$vars[] = '--tara-brand-2:' . $c['secondary'];

		$out = '<style id="tara-brand-css">:root{' . implode( ';', $vars ) . ';}' . "\n";

		/* رنگ موبایل — همه با پیش‌فرض‌های خودِ قالب، تا ظاهر قبلی عوض نشود */
		$mob = array();

		if ( $c['mob_bar_bg'] !== '#ffffff' ) {
			$mob[] = '#mobiles_header{background:' . $c['mob_bar_bg'] . ' !important}';
		}
		if ( $c['mob_btn_bg'] !== '#111111' ) {
			$mob[] = '#mobiles_header .navicon,#mobiles_header .circle_hlink{background:' . $c['mob_btn_bg'] . ' !important}';
			$mob[] = '.searches_mobile{background:' . $c['mob_btn_bg'] . '}';
		}
		if ( $c['mob_bar_text'] !== '#ffffff' ) {
			$mob[] = '#mobiles_header .navicon,#mobiles_header .circle_hlink{color:' . $c['mob_bar_text'] . ' !important}';
			$mob[] = '#mobiles_header .search_mobile_box i{color:' . $c['mob_bar_text'] . ' !important}';
		}
		if ( $c['mob_menu_bg'] !== '#222222' ) {
			$mob[] = '.mobiles_menu{background:' . $c['mob_menu_bg'] . ' !important}';
			$mob[] = '.mobiles_menu .top_logo_title{background:transparent}';
			$mob[] = '.mobiles_menu #cssmenu ul ul{background:' . tara_rgba( '#000000', 0.22 ) . '}';
		}
		if ( $c['mob_menu_text'] !== '#ffffff' ) {
			$mob[] = '.mobiles_menu #cssmenu a,.mobiles_menu a,.mobiles_menu .mob_titles_h1,.mobiles_menu .mob_titles_h2,.mobiles_menu .ab-mobile-menu-fallback li a{color:' . $c['mob_menu_text'] . ' !important}';
			$mob[] = '.mobiles_menu .liner{border-color:' . tara_rgba( $c['mob_menu_text'], 0.16 ) . '}';
		}
		if ( $c['mob_search_bg'] !== '#ffffff' ) {
			$mob[] = '#lsds_mobile,#searches_mobile #lsds_mobile{background:' . $c['mob_search_bg'] . ' !important;border:0 !important}';
		}
		if ( $c['mob_search_text'] !== '#333333' ) {
			$mob[] = '#lsds_mobile{color:' . $c['mob_search_text'] . ' !important}';
			$mob[] = '#lsds_mobile::placeholder{color:' . $c['mob_search_text'] . ' !important;opacity:.7}';
			$mob[] = '.searches_mobile button{color:' . $c['mob_search_text'] . '}';
		}

		if ( $mob ) {
			$out .= implode( '', $mob ) . "\n";
		}

		$out .= '</style>' . "\n";

		echo $out; // phpcs:ignore WordPress.Security.EscapeOutput -- مقادیر از قبل اعتبارسنجی شده‌اند
	}
	add_action( 'wp_head', 'tara_brand_css', 101 );
}
