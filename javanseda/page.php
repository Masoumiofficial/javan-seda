<?php
/* ─────────────── جوان‌صدا ۳.۵.۰ — قفلِ بازگشتِ امن ───────────────
   اگر این قالب (یا هر چیزی که لایۀ ما به آن افزوده) روی این سرور خطا بدهد،
   بدون پاک‌کردن هیچ فایلی همین صفحه را به نسخۀ «اصلیِ فروشنده» برگردانید:
       define( 'JAVANSEDA_ORIGINAL_TEMPLATES', true );   // در wp-config.php
   فایلِ اصلی همین مسیر: original/page.php
   ─────────────────────────────────────────────────────────────── */
if ( defined( 'JAVANSEDA_ORIGINAL_TEMPLATES' ) && JAVANSEDA_ORIGINAL_TEMPLATES ) {
	$jv_orig = dirname( __FILE__ ) . '/original/page.php';
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
<!-- single_posts_right -->
<div class="single_posts_right">
<?php if(have_posts()) : while(have_posts()) : the_post(); ?>
<article class="single_posts tarlanweb_post_box">
<header>
<h1><?php the_title(); ?></h1>
<div class="liner clear"></div>
<?php if(function_exists('the_views')){ ?>
<div class="tarlanweb_post_ft"><i class="icofont-eye-alt"></i> <?php the_views(); ?></div><?php } ?>
<div class="clear"></div>
</header>
<div class="single_content_box">
<?php the_content(''); ?>
<div class="clear"></div>
</div>
<footer>
</footer>
<div class="clear"></div>
</article>
<?php endwhile; endif; ?>
<div class="clear"></div>
</div>
<!-- single_posts_right -->
<div id="left_side">
<?php
/* v3.0.2 — رندر امن ابزارک‌ها: در صورت نبودِ فایل کمکی، دقیقاً رفتار قبلی اعمال می‌شود */
if ( function_exists( 'javan_seda_sidebar' ) ) {
	javan_seda_sidebar( 'left_side1' );
} else {
	if ( ! function_exists( 'dynamic_sidebar' ) || ! dynamic_sidebar( 'left_side1' ) ) :
	endif;
}
?>
<div class="clear"></div>
</div>
<div class="clear"></div>
</div>
</div>
<!-- centers_box -->
<?php get_footer(''); ?>