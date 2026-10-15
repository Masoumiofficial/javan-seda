<?php $fixbvip = ot_get_option('fixbvip'); if('off' != $fixbvip){
$vip_np=ot_get_option('vip_num'); $my_custom_query= new WP_Query(
array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>$vip_np,
'meta_query'=>array(array('key'=>'vip_song','value'=>'1','compare'=>'LIKE')) ));
if($my_custom_query->have_posts()) : ?>
<!-- vip_img_box -->
<div class="vip_img_box">
<div class="vip_top_slider owl-carousel owl-theme">
<?php $rkian=1; while($my_custom_query->have_posts()) : $my_custom_query->the_post();?>
<article class="vip_post">
<header>
<figure><a href="<?php the_permalink(); ?>">
<?php if (get_field('vip_img')){ ?>
<img src="<?php the_field('vip_img'); ?>" width="640" height="360" alt="<?php the_title(''); ?>" title="<?php the_title(''); ?>">
<?php } else{if(has_post_thumbnail()){
the_post_thumbnail('thumb1',array('alt'=>''.get_the_title(),'title'=>''.get_the_title().'')); } else { ?>
<img src="<?php bloginfo('template_url');?>/images/vip_img.jpg" width="640" height="360" alt="تصویر پیدا نشد" title="تصویر پیدا نشد"><?php } } ?></a></figure>
<h2><a href="<?php the_permalink(); ?>"><?php if(get_field('artist') || get_field('song')){ ?> <?php the_field('artist'); ?> - <span><?php the_field('song'); ?></span><?php } else{ the_title(''); } ?></a></h2>
</header>	
<footer>
<?php $field=get_field_object('musics_type'); if($field){
$value=$field['value']; $label=$field['choices'][$value]; ?>
<span class="ft_box_format"><?php echo esc_attr($value); ?></span><?php } ?>
</footer>
<div class="clear"></div>
</article>
<?php $rkian++; endwhile; ?>
</div>
<div class="clear"></div>
</div>
<!-- vip_img_box -->
<?php endif; wp_reset_query(); } ?>