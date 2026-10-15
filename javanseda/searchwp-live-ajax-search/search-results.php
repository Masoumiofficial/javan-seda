<?php
/*
 *
 * Wordpress Theme Design By Reza Kianoosh
 * mail: rezakianoosh@gmail.com
 * phone: 09158856205
 * site: tarlanweb.ir - rkianoosh.ir
 * 
 */
?>
<?php if ( have_posts() ) : ?>
<?php while ( have_posts() ) : the_post(); ?>
<?php $post_type = get_post_type_object( get_post_type() ); ?>
<div class="searchwp-live-search-result" role="option" id="" aria-selected="false">
<p><a href="<?php echo esc_url( get_permalink() ); ?>">
<?php if(has_post_thumbnail()){the_post_thumbnail('thumb4');} ?>
<?php if(get_field('artist') || get_field('song')){ ?> <?php the_field('artist'); ?><span><?php the_field('song'); ?></span><?php } else{ the_title(''); } ?>
</a></p>
</div>
<?php endwhile; ?>
<?php else : ?>
<p class="searchwp-live-search-no-results" role="option">
<em>نتیجه ای یافت نشد!</em>
</p>
<?php endif; ?>