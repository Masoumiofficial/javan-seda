<header id="mobiles_header">
<div class="logo_mobiles"><figure>
<a href="<?php bloginfo('url'); ?>"><img src="<?php echo ot_get_option('logo'); ?>" alt="<?php echo ot_get_option('head_h1'); ?>" title="<?php echo ot_get_option('head_h1'); ?>" width="150" height="40"></a>
</figure>
</div>
<div class="navicon"><i class="responsive-menu-button-inner"></i></div> 
<div class="circle_hlink search_mobile_box"><i class="icofont-search"></i></div>
<form class="searches_mobile" method="get" action="<?php bloginfo('url'); ?>">
<input id="lsds_mobile" class="meta searchfield" name="s" data-swplive="true" placeholder="کلمه مورد نظر ..." type="text">
<button><i class="icofont-search"></i></button>
</form>
<div class="mobile_box">
<div class="bodydeactive"></div>
<div class="mobiles_menu">
<div class="top_logo_title">
<a href="<?php bloginfo('url'); ?>"><img src="<?php echo ot_get_option('logo'); ?>" alt="<?php echo ot_get_option('head_h1'); ?>" title="get" width="150" height="40"></a>
<div class="clear"></div>
</div>
<?php wp_nav_menu(array('theme_location'=>'mobiles_menu','container_id'=>'cssmenu','walker'=>new CSS_Menu_Maker_Walker())); ?>
<div class="clear"></div>
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
<div class="clear"></div>
</div>
</div>
</header>