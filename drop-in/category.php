<?php get_header(''); ?>
<?php get_template_part('inc/breadcrumbs'); ?>
<!-- centers_box -->
<div class="tarlanweb_center">
<div id="centers_box">
<!-- song_boxs -->
<div class="song_boxs">
<?php if(have_posts()) : ?>
<div class="songs_title_box">
<h1><?php echo single_cat_title(''); ?></h1>
<?php /* v3.3.5 — اگر خانۀ دسته توضیح نداشته باشد، div خالی چاپ نشود:
   همان «کادر سفیدِ خالی» که زیر عنوان دیده می‌شد (قواعد شیشه به .archive_txt_box
   پدینگ و مرز می‌دهد، پس یک div بی‌محتوا = یک کادر خالی). */
/* v3.4.9 — «توضیح کوتاه» (بلوکِ جوان‌صدا در پیشخوان ← ویرایش دسته) جایش را
   می‌گیرد؛ اگر پر نشده باشد همان توضیحِ همیشگی نمایش داده می‌شود. */
$ab_cat_desc = function_exists( 'javan_seda_term_desc_top' )
	? trim( (string) javan_seda_term_desc_top( get_queried_object() ) )
	: trim( (string) category_description() );
if ( '' !== wp_strip_all_tags( $ab_cat_desc ) ) : ?>
<div class="archive_txt_box">
<?php echo $ab_cat_desc; ?>
<div class="clear"></div>
</div>
<?php endif; ?>
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
<?php /* v3.4.9 — توضیحِ کاملِ دسته: پایینِ همین باکس، بسته و با دکمۀ «بیشتر/کمتر» */
$ab_cat_full = function_exists( 'javan_seda_term_desc_bottom' ) ? javan_seda_term_desc_bottom( get_queried_object() ) : '';
if ( '' !== trim( wp_strip_all_tags( (string) $ab_cat_full ) ) ) : ?>
<div class="archive_txt_box ab-term-more__wrap">
<?php echo $ab_cat_full; // phpcs:ignore WordPress.Security.EscapeOutput -- داخلِ جاوا_seda escape شده ?>
<div class="clear"></div>
</div>
<?php endif; ?>
<!-- song_boxs -->
<?php get_template_part('inc/pagination'); ?>
<div class="clear"></div>
</div>
</div>
<!-- centers_box -->
<?php get_footer(''); ?>