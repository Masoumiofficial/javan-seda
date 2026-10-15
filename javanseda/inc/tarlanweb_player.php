<?php $indpl = ot_get_option('indpl'); if('off' != $indpl){ ?>
<!-- index_music_player -->
<div id="index_music_player">
<div class="music_box_player">
<?php $vip_np=ot_get_option('v_num'); $my_custom_query= new WP_Query(
array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>$vip_np,'orderby'=>'meta_value',
        'order'=>'DESC','meta_query'=>array(array('key'=>'plym','value'=>'1')) ));
if($my_custom_query->have_posts()) : ?>
<audio id="audio" preload="none" tabindex="0">
<?php $rkian=1; while($my_custom_query->have_posts()) : $my_custom_query->the_post();?>
<source src="<?php the_field('music128'); ?>" data-track-number="<?php echo $rkian; ?>" />
<?php $rkian++; endwhile; ?>  
Your browser does not support HTML5 audio.
</audio>
<?php if($rkian = 1){ ?> 
<div class="player">
<div class="player_controls">
<i class="previous-track-btn disabled"><span class="screen-reader-text">Previous track button</span></i>
<div class="large-toggle-btn"><i class="icofont-play-alt-1"><span class="screen-reader-text">Large toggle button</span></i></div>
<div class="album_show_hide"><i class="icofont-navigation-menu"></i></div>
<i class="next-track-btn"><span class="screen-reader-text">Next track button</span></i>
</div>
    <div class="info-box">
      <div class="track-info-box">
        <div class="track-title-text"> <?php the_field('artist') ?> - <?php the_field('song'); ?> </div>
        <div class="audio-time">
        	<span class="duration">00:00</span> /
          <span class="current-time">00:00</span> 
        </div>
      </div>
      <!-- /.info-box -->
      <div class="progress-box">
        <div class="progress-cell">
          <div class="progress">
            <div class="progress-buffer"></div>
            <div class="progress-indicator"></div>
          </div>
        </div>
<div class="volume_box">
<i class="icofont-volume-bar"></i>
<input type="range" id="volume-bar" min="0" max="100" step="5" value="100">
</div>
</div>
</div>
<!-- /.progress-box -->
</div>
<!-- /.player -->
<?php } ?>
<?php endif; wp_reset_query(); ?>

<?php $vip_np=ot_get_option('v_num'); $my_custom_query= new WP_Query(
array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>$vip_np,'orderby'=>'meta_value',
        'order'=>'DESC','meta_query'=>array(array('key'=>'plym','value'=>'1')) ));
if($my_custom_query->have_posts()) : ?>
<div class="play-list">
<?php $rkian=1; while($my_custom_query->have_posts()) : $my_custom_query->the_post();?>
    <div class="play-list-row" data-track-row="<?php echo $rkian; ?>">
      <div class="small-toggle-btn">
        <i class="small-play-btn"><span class="screen-reader-text">Small toggle button</span></i>
      </div>
      <div class="track-number">
        <?php echo $rkian; ?>. 
      </div>
      <div class="track-title">
        <a class="playlist-track" href="#" data-play-track="<?php echo $rkian; ?>"><?php the_field('artist') ?> - <?php the_field('song'); ?></a>
      </div>
    </div>
<?php $rkian++; endwhile; ?> 
</div>
<?php endif; wp_reset_query(); ?>

</div>
</div>
<!-- index_music_player -->
<?php } ?>