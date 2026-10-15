<article class="songs_post">
<header>
<figure><a rel="nofollow" href="<?php the_permalink(); ?>">
<?php if(has_post_thumbnail()){ if(wp_is_mobile()){the_post_thumbnail('thumb4',array('alt'=>''.get_the_title(),'title'=>''.get_the_title().''));
}else{the_post_thumbnail('thumb2',array('alt'=>''.get_the_title(),'title'=>''.get_the_title().''));}
} else { ?><img src="<?php bloginfo('template_url');?>/images/nomusics.jpg" width="190" height="190" alt="تصویر پیدا نشد" title="تصویر پیدا نشد"><?php } ?></a></figure>
<h2><a href="<?php the_permalink(); ?>"><?php if(get_field('artist') || get_field('song')){ ?> <?php the_field('artist'); ?><span><?php the_field('song'); ?></span><?php } else{ the_title(''); } ?></a></h2>
</header>
<footer>
<?php $field=get_field_object('musics_type'); if($field){
$value=$field['value']; $label=$field['choices'][$value]; ?>
<span class="ft_box_format"><?php echo esc_attr($value); ?></span><?php } ?>
</footer>
</article>