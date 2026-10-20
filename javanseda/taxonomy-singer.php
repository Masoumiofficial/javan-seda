<?php
/* ─────────────── جوان‌صدا ۳.۵.۰ — قفلِ بازگشتِ امن ───────────────
   اگر این قالب (یا هر چیزی که لایۀ ما به آن افزوده) روی این سرور خطا بدهد،
   بدون پاک‌کردن هیچ فایلی همین صفحه را به نسخۀ «اصلیِ فروشنده» برگردانید:
       define( 'JAVANSEDA_ORIGINAL_TEMPLATES', true );   // در wp-config.php
   فایلِ اصلی همین مسیر: original/taxonomy-singer.php
   ─────────────────────────────────────────────────────────────── */
if ( defined( 'JAVANSEDA_ORIGINAL_TEMPLATES' ) && JAVANSEDA_ORIGINAL_TEMPLATES ) {
	$jv_orig = dirname( __FILE__ ) . '/original/taxonomy-singer.php';
	if ( is_readable( $jv_orig ) ) {
		include $jv_orig;
		return;
	}
}
?>
<?php
/**
 * سازگاری (v3.4.7) — صفحۀ اختصاصی هنرمند به taxonomy-artist.php رفته است.
 *
 * تا وقتی نامک فعال «singer» باشد (لایۀ «هنرمند» خاموش باشد یا مهاجرت هنوز
 * اجرا نشده باشد) وردپرس همین فایل را پیدا می‌کند؛ پس فقط فایل اصلی را
 * بارگذاری می‌کند و هیچ چیزی خودش رندر نمی‌کند. اگر فایل اصلی نبود،
 * به آرشیوی برمی‌گردد که وردپرس خودش می‌سازد (نه صفحۀ سفید).
 */
$javan_seda_artist_tpl = locate_template( array( 'taxonomy-artist.php' ) );
if ( $javan_seda_artist_tpl && is_readable( $javan_seda_artist_tpl ) ) {
	require $javan_seda_artist_tpl;
} else {
	get_header();
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	get_footer();
}
