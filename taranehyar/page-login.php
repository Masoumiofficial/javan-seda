<?php
/**
 * Template Name: ورود و ثبت‌نام
 * ===========================================================================
 * یک صفحهٔ اختصاصی برای ورود؛ همان کارتِ «وارد شوید» پنل به‌همراه باز شدنِ
 * خودکارِ مودال. اگر کاربر از قبل وارد شده باشد، مستقیم به «حساب من» می‌رود.
 *
 * @package Taranehyar
 */

if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() && function_exists( 'tara_acc_profile_url' ) ) {
	wp_safe_redirect( tara_acc_profile_url() );
	exit;
}

get_header();
?>
<div class="ty-archive tarlanweb_center" dir="rtl">
	<?php
	if ( function_exists( 'tara_acc_login_card' ) ) {
		echo tara_acc_login_card(); // phpcs:ignore WordPress.Security.EscapeOutput -- مارکاپِ همین قالب
	} else {
		?>
		<div class="ty-acct-guest">
			<h1 class="ty-acct-guest__title">ورود</h1>
			<p class="ty-acct-guest__sub">حسابِ کاربری در دسترس نیست.</p>
		</div>
		<?php
	}
	?>
	<p class="ty-acct-login-alt">
		اگر حسابِ مدیریتی دارید، از <a href="<?php echo esc_url( wp_login_url() ); ?>">صفحهٔ ورودِ وردپرس</a> وارد شوید.
	</p>
</div>
<?php
get_footer();
