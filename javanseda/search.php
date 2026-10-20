<?php
/* ─────────────── جوان‌صدا ۳.۵.۰ — قفلِ بازگشتِ امن ───────────────
   اگر این قالب (یا هر چیزی که لایۀ ما به آن افزوده) روی این سرور خطا بدهد،
   بدون پاک‌کردن هیچ فایلی همین صفحه را به نسخۀ «اصلیِ فروشنده» برگردانید:
       define( 'JAVANSEDA_ORIGINAL_TEMPLATES', true );   // در wp-config.php
   فایلِ اصلی همین مسیر: original/search.php
   ─────────────────────────────────────────────────────────────── */
if ( defined( 'JAVANSEDA_ORIGINAL_TEMPLATES' ) && JAVANSEDA_ORIGINAL_TEMPLATES ) {
	$jv_orig = dirname( __FILE__ ) . '/original/search.php';
	if ( is_readable( $jv_orig ) ) {
		include $jv_orig;
		return;
	}
}
?>
<?php get_header(''); ?>
<?php get_template_part('inc/breadcrumbs'); ?>
<!-- centers_box -->
<div class="tarlanweb_center">
<div id="centers_box">
<!-- song_boxs -->
<div class="song_boxs">
<?php if(have_posts()) : ?>
<div class="songs_title_box">
<h1>نتیجۀ جست‌وجو: «<?php echo esc_html( get_search_query() ); ?>»</h1>
<p class="ab-search-count"><?php global $wp_query; $ab_n = (int) $wp_query->found_posts; printf( _n( '%d اثر پیدا شد.', '%d اثر پیدا شد.', $ab_n, 'javan-seda' ), $ab_n ); ?>&nbsp;<a href="<?php echo esc_url( home_url('/music/') ); ?>">همۀ آهنگ‌ها</a></p>
<div class="clear"></div>
</div>
<div class="song_boxs_posts">
<ul>
<?php while(have_posts()) : the_post(); ?>
<li><?php get_template_part('inc/songs_post'); ?></li>
<?php endwhile; ?>
</ul>
<div class="clear"></div>
</div>
<?php else: get_template_part('inc/no-content'); endif; ?>
<div class="clear"></div>
</div>
<!-- song_boxs -->
<?php get_template_part('inc/pagination'); ?>
<div class="clear"></div>
</div>
</div>
<!-- centers_box -->
<?php get_footer(''); ?>