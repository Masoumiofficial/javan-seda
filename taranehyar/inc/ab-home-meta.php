<?php
/**
 * ترانه یار ۳.۵.۳ — عنوان و توضیحات متای «صفحهٔ اصلی»
 * =============================================================================
 * مسئله: صفحهٔ اصلی این قالب یک «برگه» نیست؛ فهرستِ نوشته‌هاست (home.php). پس در
 * «برگه‌ها» چیزی برای ویرایش عنوان/توضیح متا وجود ندارد و در تنظیمات قالب هم فیلدی
 * برایش نبود. تا ۳.۵.۲ عنوانِ صفحهٔ اصلی در header.php دستی چاپ می‌شد و توضیح هم فقط
 * از «تنظیمات → همگانی → توضیح کوتاه» می‌آمد.
 *
 * کاری که این فایل می‌کند (فقط دو چیز، بدون دست‌زدن به بقیهٔ صفحات):
 *   ۱) پشتیبانی استانداردِ `title-tag` فعال می‌شود (در myfunctions.php) و عنوانِ
 *      صفحهٔ اصلی از فیلدِ «تنظیمات قالب → صفحه اصلی → عنوان متا» می‌آید.
 *      اگر فیلد خالی باشد، دقیقاً مثل قبل فقط نام سایت چاپ می‌شود.
 *   ۲) توضیح متا (meta description و og:description) از فیلدِ «توضیحات متا» می‌آید؛
 *      اگر خالی باشد، مثل قبل «توضیح کوتاه» سایت.
 *
 * چرا `document_title_parts` و نه بازنویسی عنوان؟
 *   چون هستهٔ وردپرس همین فیلتر را برای Yoast / Rank Math / AIOSEO هم صدا می‌زند؛
 *   اگر افزونهٔ سئو فعال باشد، آن افزونه خروجیِ نهایی را می‌گیرد و چیز تکراری چاپ
 *   نمی‌شود. (فیلدهای ما در آن حالت بی‌اثرند و همین را در راهنما نوشته‌ایم.)
 *
 * خاموشی کامل (بدون تغییر کد):  define( 'TARA_HOME_META', false );
 * =============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'TARA_HOME_META' ) && false === TARA_HOME_META ) {
	return;
}

if ( ! function_exists( 'tara_home_meta' ) ) {
	/**
	 * خواندنِ فیلدهای صفحهٔ اصلی از تنظیمات قالب.
	 *
	 * @param string $key 'title' یا 'desc'.
	 * @return string مقدارِ پاک‌شده یا رشتهٔ خالی.
	 */
	function tara_home_meta( $key ) {
		$map = array(
			'title' => 'home_meta_title',
			'desc'  => 'home_meta_desc',
		);
		if ( ! isset( $map[ $key ] ) || ! function_exists( 'ot_get_option' ) ) {
			return '';
		}
		$value = ot_get_option( $map[ $key ], '' );
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}
		/* یک‌خطی‌کردن و حذف تگ‌ها؛ مقدار ممکن است از textarea بیاید */
		$value = wp_strip_all_tags( (string) $value );
		$value = preg_replace( '/\s+/u', ' ', $value );
		return trim( (string) $value );
	}
}

if ( ! function_exists( 'tara_home_title' ) ) {
	/**
	 * عنوانِ صفحهٔ اصلی: فیلدِ دستی، وگرنه نام سایت (رفتار ۳.۵.۲).
	 *
	 * @param string $default نام سایت.
	 * @return string
	 */
	function tara_home_title( $default = '' ) {
		$custom = tara_home_meta( 'title' );
		return '' !== $custom ? $custom : (string) $default;
	}
}

if ( ! function_exists( 'tara_home_description' ) ) {
	/**
	 * توضیحِ متای صفحهٔ اصلی: فیلدِ دستی، وگرنه همان مقداری که قبلاً استفاده می‌شد.
	 *
	 * @param string $default توضیح کوتاهِ سایت.
	 * @return string
	 */
	function tara_home_description( $default = '' ) {
		$custom = tara_home_meta( 'desc' );
		return '' !== $custom ? $custom : (string) $default;
	}
}

if ( ! function_exists( 'tara_home_is_front' ) ) {
	/** آیا این درخواست «صفحهٔ اصلی» است؟ (بدون وابستگی به is_home در پیشخوان) */
	function tara_home_is_front() {
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return false;
		}
		if ( function_exists( 'is_feed' ) && is_feed() ) {
			return false;
		}
		if ( function_exists( 'is_front_page' ) && is_front_page() ) {
			return true;
		}
		return function_exists( 'is_home' ) && is_home();
	}
}

if ( ! function_exists( 'tara_home_title_parts' ) ) {
	/**
	 * جایگزینیِ بخش‌های عنوان فقط برای صفحهٔ اصلی.
	 *
	 * `tagline` و `site` عمداً خالی می‌شوند تا عنوان دقیقاً همان چیزی باشد که مدیر
	 * نوشته است (بدون « – نام سایت» تکراری). برای صفحه‌های دیگر هیچ تغییری نمی‌دهیم.
	 *
	 * @param array $parts بخش‌های عنوان.
	 * @return array
	 */
	function tara_home_title_parts( $parts ) {
		if ( ! is_array( $parts ) || ! tara_home_is_front() ) {
			return $parts;
		}
		$default = isset( $parts['title'] ) ? (string) $parts['title'] : '';
		if ( '' === $default && function_exists( 'get_bloginfo' ) ) {
			$default = (string) get_bloginfo( 'name' );
		}
		$parts['title']   = tara_home_title( $default );
		$parts['tagline'] = '';
		$parts['site']    = '';
		return $parts;
	}
	add_filter( 'document_title_parts', 'tara_home_title_parts', 15 );
}
