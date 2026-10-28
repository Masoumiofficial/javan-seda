<?php $index_pv = ot_get_option('index_pv'); if('off' != $index_pv){ ?>
<!-- song_boxs -->
<div class="song_boxs">
<?php $today = getdate();
$arms = array(
'post_type'=>'post','posts_per_page'=>ot_get_option('index_nm'),'offset'=>0,
'meta_key'=>'views','orderby'=>'meta_value_num','post_status'=>'publish',
'no_found_rows'=>true,'ignore_sticky_posts'=>true,
'date_query'=> array(array('column' =>'post_date_gmt','after' =>'1 month ago')));
$the_query = new WP_Query( $arms ); ?>
<?php if ($the_query->have_posts()) : ?>
<div class="songs_title_box">
<h3><?php echo esc_html( ot_get_option('index_pt') ); ?></h3> 
<div class="clear"></div>
<div class="liner"></div>
</div>
<div class="song_boxs_posts">
<?php
$tara_mv_tracks = function_exists( 'tara_track_playlist' ) ? tara_track_playlist( $the_query->posts ) : array();
?>
<ul<?php echo function_exists( 'tara_queue_attrs' ) ? tara_queue_attrs( $tara_mv_tracks, ot_get_option( 'index_pt' ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
<?php $GLOBALS['tara_card_play'] = true; ?>
<?php while($the_query->have_posts()) : $the_query->the_post(); ?>
<li><?php get_template_part('inc/songs_post'); ?></li>
<?php endwhile; ?>
<?php $GLOBALS['tara_card_play'] = false; ?>
<?php wp_reset_postdata(); ?>
</ul>
<div class="clear"></div>
</div>
<?php endif; ?>
<div class="clear"></div>
</div>
<!-- song_boxs -->
<?php } ?>