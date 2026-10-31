<?php
/**
 * Template Name: حساب من
 * ===========================================================================
 * پنلِ کاربریِ ترانه یار: علاقه‌مندی‌ها، پلی‌لیست‌ها، خواننده‌های دنبال‌شده،
 * تاریخچه، پیام‌ها، اتصال به ربات و مشخصات.
 *
 * یک کارِ دیگر هم می‌کند: اگر آدرس پارامتر `?share=KEY` داشته باشد، همان
 * پلی‌لیست را به‌صورتِ **عمومی و بدونِ ورود** نشان می‌دهد؛ لینکی که کاربر با
 * دوستش به اشتراک می‌گذارد. پلی‌لیستِ خصوصی هیچ‌وقت با این پارامتر باز
 * نمی‌شود.
 *
 * @package Taranehyar
 */

get_header();

$tara_acc_share = isset( $_GET['share'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['share'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
?>
<div class="ty-archive tarlanweb_center" dir="rtl">
	<?php
	if ( '' !== $tara_acc_share && function_exists( 'tara_acc_public_playlist' ) ) {
		echo tara_acc_public_playlist( $tara_acc_share ); // phpcs:ignore WordPress.Security.EscapeOutput -- مارکاپِ همین قالب
	} elseif ( function_exists( 'tara_acc_panel' ) ) {
		echo tara_acc_panel(); // phpcs:ignore WordPress.Security.EscapeOutput -- مارکاپِ همین قالب
	} else {
		?>
		<div class="ty-acct-guest">
			<h1 class="ty-acct-guest__title">حسابِ کاربری در دسترس نیست</h1>
			<p class="ty-acct-guest__sub">فایل‌های قالب ناقص به‌نظر می‌رسند؛ لطفاً بستهٔ کامل را دوباره نصب کنید.</p>
		</div>
		<?php
	}
	?>
</div>
<?php
get_footer();
