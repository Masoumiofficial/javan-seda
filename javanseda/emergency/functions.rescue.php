<?php
/**
 * نجاتِ موقتِ پوستهٔ جوان‌صدا — نسخهٔ ۳.۵.۱
 *
 * این فایل جای فایلِ اصلیِ functions.php نیست. فقط برای حالتی است که فایلِ
 * رمزگذاری‌شدهٔ فروشندهٔ قالب روی سرور گم شده و سایت با این خطا سفید افتاده:
 *
 *   PHP Fatal error: Uncaught Error: Call to undefined function ot_get_option()
 *   in …/wp-content/themes/javanseda/header.php
 *
 * چرا؟ وردپرس از پوسته فقط و فقط functions.php را لود می‌کند؛ آن فایلِ رمزدار
 * هم OptionTree، myfunctions.php و بقیهٔ لایه‌ها را می‌آورد. اگر پاک/جایگزین شود،
 * هیچ‌کدام از آن‌ها لود نمی‌شوند و هر تمپلیت روی ot_get_option() می‌میرد.
 *
 * کاربرد (۲ دقیقه):
 *   ۱) این فایل را در ریشهٔ پوسته بگذارید و نامش را functions.php بگذارید.
 *   ۲) یک بار هر صفحه را باز کنید (سایت بالا می‌آید، با هشدار در پیشخوان).
 *   ۳) در اولین فرصت functions.php و RTL_License_41d6c5f704e3a746.php اصلی را از
 *      دانلودِ خودِ rtl-theme.com برگردانید و همین فایل را حذف کنید.
 *
 * این فایل چیزی را decode یا لایسنس را دور نمی‌زند؛ فقط بوت‌استرپِ گم‌شده را
 * بازمی‌سازد تا سایت تا رسیدنِ فایلِ اصلی پایین نماند.
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$jv_dir = get_template_directory();

/* ۱) OptionTree — منبعِ ot_get_option() و پنلِ تنظیمات قالب */
if ( is_readable( $jv_dir . '/inc/option-tree/ot-loader.php' ) ) {
	require_once $jv_dir . '/inc/option-tree/ot-loader.php';
}

/* ۲) تعریفِ گزینه‌های همین قالب */
if ( is_readable( $jv_dir . '/inc/theme-options.php' ) ) {
	require_once $jv_dir . '/inc/theme-options.php';
}

/* ۳) لایه‌های خودمان (صفحه‌ها، سایدبارها، فیلدها، اسلایدرها…) */
if ( is_readable( $jv_dir . '/myfunctions.php' ) ) {
	require_once $jv_dir . '/myfunctions.php';
}

/* ۴) اگر OptionTree بار نشد، حداقل تابعِ گزینه‌ها را بساز تا قالب سفید نیفتد */
if ( ! function_exists( 'ot_get_option' ) ) {
	function ot_get_option( $option_id, $default = '' ) {
		$opts = get_option( 'option_tree' );
		if ( is_array( $opts ) && isset( $opts[ $option_id ] ) && '' !== $opts[ $option_id ] ) {
			return $opts[ $option_id ];
		}
		return $default;
	}
}

/* ۵) هشدارِ همیشگی در پیشخوان تا این فایل فراموش نشود */
if ( ! function_exists( 'javan_seda_rescue_notice' ) ) {
	function javan_seda_rescue_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>جوان‌صدا:</strong> '
			. 'فایلِ اصلیِ <code>functions.php</code> (رمزگذاری‌شده) روی سرور نیست و این فایلِ نجاتِ موقت جایش نشسته. '
			. 'برای بازگشتِ کاملِ قالب، <code>functions.php</code> و <code>RTL_License_41d6c5f704e3a746.php</code> را '
			. 'از دانلودِ اصلیِ rtl-theme.com برگردانید و همین فایل را حذف کنید.'
			. '</p></div>';
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'admin_notices', 'javan_seda_rescue_notice' );
}
