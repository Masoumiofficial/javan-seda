<?php
/**
 * Template Name: ورود و ثبت‌نام
 * ===========================================================================
 * v3.11.0 — برگهٔ ورود/ثبت‌نام مستقل از پنلِ حساب.
 *
 * چه چیزی عوض شد و چرا:
 *   • ورود و ثبت‌نام از هم جدا شدند: دو تبِ روشن («ورود» و «ثبت‌نام») که هر
 *     کدام متنِ خودش را دارد، نه یک کارتِ مبهمِ «ورود / ثبت‌نام».
 *   • هیچ لینک یا جمله‌ای که کاربرِ عادی را به مسیرِ مدیریتیِ وردپرس
 *     بفرستد در این برگه نیست (درخواستِ صریحِ مالک).
 *   • لینکِ راهنما از تنظیمات قالب می‌آید، نه یک آدرسِ ثابت.
 *
 * @package Taranehyar
 */

if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() && function_exists( 'tara_acc_profile_url' ) ) {
	wp_safe_redirect( tara_acc_profile_url() );
	exit;
}

$tara_login_mode = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( (string) $_GET['mode'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification
$tara_login_mode = 'register' === $tara_login_mode ? 'register' : 'login';
$tara_login_self = get_permalink();

get_header();
?>
<div class="ty-login-page tarlanweb_center" dir="rtl">
	<?php if ( function_exists( 'tara_acc_on' ) && tara_acc_on() ) : ?>
		<nav class="ty-login-tabs" aria-label="ورود یا ثبت‌نام">
			<a class="ty-login-tabs__tab<?php echo 'login' === $tara_login_mode ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( add_query_arg( 'mode', 'login', $tara_login_self ) ); ?>"
				<?php echo 'login' === $tara_login_mode ? 'aria-current="page"' : ''; ?>>
				ورود
			</a>
			<a class="ty-login-tabs__tab<?php echo 'register' === $tara_login_mode ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( add_query_arg( 'mode', 'register', $tara_login_self ) ); ?>"
				<?php echo 'register' === $tara_login_mode ? 'aria-current="page"' : ''; ?>>
				ثبت‌نام
			</a>
		</nav>
	<?php endif; ?>

	<?php
	if ( function_exists( 'tara_acc_login_card' ) ) {
		echo tara_acc_login_card( array( 'mode' => $tara_login_mode ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- مارکاپِ همین قالب
	} else {
		?>
		<div class="ty-acct-guest">
			<h1 class="ty-acct-guest__title">ورود</h1>
			<p class="ty-acct-guest__sub">حسابِ کاربری در دسترس نیست.</p>
		</div>
		<?php
	}
	?>
</div>
<?php
get_footer();
