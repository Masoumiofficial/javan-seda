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
<?php
/* v3.8.0 — عددِ نتیجه با ارقام فارسی و جداکنندهٔ هزارگان، مثل بقیهٔ سایت */
global $wp_query;
$ab_n         = (int) $wp_query->found_posts;
$ab_n_display = function_exists( 'tara_number_fa' ) ? tara_number_fa( number_format_i18n( $ab_n ) ) : number_format_i18n( $ab_n );
?>
<p class="ab-search-count"><?php echo esc_html( sprintf( 'برای «%1$s» %2$s اثر پیدا شد.', get_search_query(), $ab_n_display ) ); ?>&nbsp;<a href="<?php echo esc_url( home_url('/music/') ); ?>">همۀ آهنگ‌ها</a></p>
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