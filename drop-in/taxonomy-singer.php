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
