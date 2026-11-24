<?php $index_hj = function_exists( 'tara_opt_on' ) ? tara_opt_on( 'index_hj' ) : ( 'off' != ot_get_option( 'index_hj' ) ); if ( $index_hj ) { ?>
<!-- song_boxs -->
<div class="song_boxs">
<?php if(have_posts()) : ?>
<div class="songs_title_box">
<h3><?php echo ot_get_option('index_ct'); ?></h3> 
<div class="clear"></div>
<div class="liner"></div>
</div>
<div class="song_boxs_posts">
<?php
/* صف پخش همین بخش: کارت اصلی و نوار چسبان از همین فهرست تغذیه می‌شوند. */
$tara_sec_tracks = function_exists( 'tara_track_playlist' ) ? tara_track_playlist( $GLOBALS['wp_query']->posts ) : array();
?>
<ul<?php echo function_exists( 'tara_queue_attrs' ) ? tara_queue_attrs( $tara_sec_tracks, ot_get_option( 'index_ct' ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
<?php $GLOBALS['tara_card_play'] = true; ?>
<?php while(have_posts()) : the_post(); ?>
<li><?php get_template_part('inc/songs_post'); ?></li>
<?php endwhile; ?>
<?php $GLOBALS['tara_card_play'] = false; ?>
</ul>
<div class="clear"></div>
</div>
<?php endif; ?>
<div class="clear"></div>
</div>
<!-- song_boxs -->
<?php get_template_part('inc/pagination'); } ?>