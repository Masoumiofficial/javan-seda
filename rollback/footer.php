<div class="clear"></div>
</main>
<?php get_template_part('inc/mobiles_header'); ?>

<?php $upbs = ot_get_option('upb'); if('off' != $upbs){ ?>
<div class="top_page boxes_shadows"><i class="icofont-thin-up"></i></div>
<?php } ?>
<?php if(!wp_is_mobile()){ $fixbtns = ot_get_option('fixbtn'); if('off' != $fixbtns){ ?>
<div class="fix_bottom_right_box">
<?php if(ot_get_option('telegram') !=""){ ?>
<a class="telegrams_ch" target="_blank" rel="noreferrer noopener nofollow" href="<?php echo ot_get_option('telegram'); ?>"><?php echo ot_get_option('teltxt'); ?></a><?php } if(ot_get_option('instagram') !=""){ ?>
<a class="instagrms_ch" target="_blank" rel="noreferrer noopener nofollow" href="<?php echo ot_get_option('instagram'); ?>"><?php echo ot_get_option('instxt'); ?></a><?php } ?>
</div>
<?php } } ?>

<footer id="footer">
<div class="tarlanweb_center">
<nav>
<?php wp_nav_menu(array('theme_location'=>'ft_menu','menu_id'=>'tarlanweb_ir','container'=>'')); ?>
<div class="clear"></div>
</nav>
<div class="socials_box">
<?php if(ot_get_option('facebook') !=""){ ?>
<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo ot_get_option('facebook'); ?>" aria-label="facebook" class="icofont-facebook"></a><?php } if(ot_get_option('instagram') !=""){ ?>
<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo ot_get_option('instagram'); ?>" aria-label="instagram" class="icofont-instagram"></a><?php } if(ot_get_option('telegram') !=""){ ?>
<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo ot_get_option('telegram'); ?>" aria-label="twitter" class="icofont-paper-plane"></a><?php } if(ot_get_option('twitter') !=""){ ?>
<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo ot_get_option('twitter'); ?>" aria-label="twitter" class="icofont-twitter"></a><?php } if(ot_get_option('youtube') !=""){ ?>
<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo ot_get_option('youtube'); ?>" aria-label="youtube" class="icofont-youtube-play"></a><?php } if(ot_get_option('aparat') !=""){ ?>
<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo ot_get_option('aparat'); ?>" aria-label="aparat" class="icofont-movie"></a><?php } ?>
<div class="clear"></div>
</div>
<p><?php echo ot_get_option('copyright'); ?></p>
<div class="clear"></div>
</div>
</footer>
<?php wp_footer(''); ?>
</body>
</html>