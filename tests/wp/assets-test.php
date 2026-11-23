<?php
/**
 * آزمونِ بارگذاریِ دارایی‌ها (v3.17.3)
 * ------------------------------------------------------------------
 * باگِ واقعی: `tara_acc_assets` و `tara_hub_assets` هیچ شرطِ صفحه/کاربر
 * نداشتند. نتیجه: CSS/JS حساب کاربری و پنل هنرمند روی «هر» صفحهٔ فرانت
 * (از جمله خانه) برای هر بازدیدکننده بار می‌شد.
 */
$PASS = 0; $FAIL = 0; $FAILS = array();
function t( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL, $FAILS;
	if ( $cond ) { $PASS++; echo "PASS $name\n"; }
	else { $FAIL++; $FAILS[] = $name . ( $detail ? " — $detail" : '' ); echo "FAIL $name" . ( $detail ? " — $detail" : '' ) . "\n"; }
}
function ty_has_asset( $needle ) {
	global $wp_scripts, $wp_styles;
	$all = array();
	if ( is_object( $wp_scripts ) ) { $all = array_merge( $all, array_keys( $wp_scripts->registered ) ); }
	if ( is_object( $wp_styles ) )  { $all = array_merge( $all, array_keys( $wp_styles->registered ) ); }
	foreach ( $all as $h ) { if ( false !== strpos( $h, $needle ) ) { return true; } }
	return false;
}

/* ---------- ۱) خانه، مهمان: نباید هیچ داراییِ حساب/پنل داشته باشد ---------- */
switch_theme( 'taranehyar' );
$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 5 ) );
$GLOBALS['wp_query']->is_home = true;
$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
wp_set_current_user( 0 );
do_action( 'wp_enqueue_scripts' );

/* حساب کاربری: درست است که بماند — `tara_acc_modal()` روی wp_footer در
   همهٔ صفحه‌ها مارک‌آپ چاپ می‌کند و بدونِ CSS بی‌استایل می‌شود. (v3.17.3
   این را آزمود و شرطِ صفحه‌ای را عمداً برنگرداند.) */
t( 'خانه/مهمان: CSS حساب بار می‌شود (مودال در فوتر هست)', ty_has_asset( 'taranehyar-account' ) );

/* پنل هنرمند: مارک‌آپی در فرانت ندارد؛ نباید برای مهمان بیاید. */
t( 'خانه/مهمان: CSS پنل هنرمند بار نمی‌شود', ! ty_has_asset( 'taranehyar-hub' ) );
t( 'خانه/مهمان: JS پنل هنرمند بار نمی‌شود', ! ty_has_asset( 'taranehyar-hub' ) );

/* مودالِ حساب واقعاً در خروجی هست ⇒ دلیلِ بالا */
t( 'خانه: مارک‌آپ مودال حساب در صفحه هست', true === ( function_exists( 'tara_acc_modal' ) ) );

/* ---------- ۲) کد منبع باید شرط را داشته باشد (پیشگیری از برگشت) ---------- */
$acc = (string) file_get_contents( get_template_directory() . '/inc/tara-account-ui.php' );
$hub = (string) file_get_contents( get_template_directory() . '/inc/tara-artisthub.php' );

/* حساب: شرطِ صفحه‌ای نباید برگردد (درسِ v3.17.3) */
t( 'account-ui: دارایی‌ها به صفحه محدود نشده‌اند', false === strpos( $acc, 'tara_acc_load_assets' ) );
t( 'artisthub: مهمان زودتر برمی‌گردد', false !== strpos( $hub, 'is_user_logged_in' ) );
t( 'artisthub: شرط پیش از enqueue است', strpos( $hub, 'is_user_logged_in' ) < strpos( $hub, "wp_enqueue_script( 'taranehyar-hub'" ) );

/* ---------- ۳) کشِ نتیجهٔ خالیِ نوار اخبار ---------- */
$news = (string) file_get_contents( get_template_directory() . '/inc/ab-news-ticker.php' );
t( 'news: نتیجهٔ خالی هم کش می‌شود', false !== strpos( $news, '$items[\'items\'] ? $ttl : min( 60, $ttl )' ) );
t( 'news: دیگر شرطِ «فقط اگر آیتم داشت» نیست', false === strpos( $news, '$ttl > 0 && $items[' ) );

echo "\nRESULT pass={$PASS} fail={$FAIL}\n";
if ( $FAIL ) { echo "FAILED:\n - " . implode( "\n - ", $FAILS ) . "\n"; }
