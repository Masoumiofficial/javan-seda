<!DOCTYPE html>
<html dir="rtl" lang="fa-IR">
<head>
<title><?php if(is_front_page() || is_home()){echo get_bloginfo('name');} else{echo wp_title('');}?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta property="dc:creator" content="tarlanweb.ir Design Group - call by 09158856205" />
<meta name="theme-color" content="#000000">
<meta name="apple-mobile-web-app-status-bar-style" content="#000000">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
<link rel="alternate" href="<?php bloginfo('url'); ?>/feed" type="application/rss+xml" title="RSS 2.0">
<link rel="shortcut icon" type="image/x-icon" href="<?php echo ot_get_option('favicon'); ?>">
<link rel="apple-touch-icon" href="<?php echo ot_get_option('logo'); ?>">
<?php wp_enqueue_script("jquery"); wp_head('');
if (is_singular() && get_option('thread_comments')) wp_enqueue_script('comment-reply'); ?>
<?php if(wp_is_mobile('')){ ?><style>body{padding-top: 60px;}</style><?php } ?>
<?php /* PATCH-MOBILE-3 (v3.1.3) — افزوده‌شده روی header.php «اصلیِ» قالب.
   نسخهٔ ۱ و ۲ این پچ سه خط داشت که هدر دسکتاپ را می‌کشت (#header{display:none} بدون media query)؛
   در این نسخه هیچ قاعده‌ای بیرون از media query نیست و چیزی «حذف» نمی‌شود.
   درخواست مالک: ۱) هدر در دسکتاپ و موبایل سالم باشد ۲) در موبایل فاصله با هدر بیشتر شود. */ ?>
<style id="ab-mobile-patch">
/* دسکتاپ: دست‌نخورده (همان که الان کار می‌کند) */
/* موبایل/تبلت: جبران ۶۰→۷۰px + فاصلهٔ بیشتر (۲۰px) + ضدّ بریده‌شدن محتوا در RTL */
@media (max-width: 800px){
body{padding-top:90px !important;}
#mobiles_header{height:70px !important;box-sizing:border-box;}
#centers_box,.single_content_box{overflow:visible;}
.tarlanweb_center{width:auto !important;max-width:100% !important;margin-right:12px;margin-left:12px;}
.single_posts_right,#left_side{width:100% !important;float:none !important;}
.single_featured_image,.single_content_box img,.single_content_box iframe{max-width:100% !important;height:auto;}
.ab-news,.ab-quickbar{margin-top:20px !important;margin-bottom:18px !important;}
#breadcrumbs_container{margin-top:14px;}
}
</style>
</head>
<?php $darks = ot_get_option('dark_modes'); if('off' != $darks){ ?>
<body <?php body_class('night'); ?>>
<?php } else{ ?> 
<body <?php body_class(''); ?>>
<?php } ?>
<?php if(wp_is_mobile()){} else{ ?> 
<header id="header">
<div class="tarlanweb_center">
<?php if(is_home()){ ?> <h1 class="h1_hidden_home"><?php echo ot_get_option('head_h1'); ?></h1> <?php } ?>
<div class="logo_top"><figure><a href="<?php bloginfo('url'); ?>"><img src="<?php echo ot_get_option('logo'); ?>" width="150" height="40" alt="<?php echo ot_get_option('head_h1'); ?>" title="<?php echo ot_get_option('head_h1'); ?>"></a></figure></div>
<nav class="menu_right">
<?php wp_nav_menu(array('theme_location'=>'top_menu','menu_id'=>'rkianoosh_ir','container'=>'')); ?>
<div class="clear"></div>
</nav>
<form id="searches" method="get" action="<?php bloginfo('url'); ?>">
<input id="lsds" class="meta searchfield" name="s" placeholder="جستجو  ..." type="text" data-swplive="true">
<button><i class="icofont-search"></i></button>
</form>

<?php $enablepop = ot_get_option('sun_moon'); if('off' != $enablepop){ ?>
<div class="dark_light_mode<?php $darks = ot_get_option('dark_modes'); if('off' != $darks){echo ' dark_active'; } ?>">
<i aria-label="moon" class="icofont-moon"></i>
<div class="toggle_modes"></div>
<i aria-label="sun" class="icofont-sun"></i>
</div>
<?php } ?>


<div class="clear"></div>
</div>
</header>
<?php } ?>
<?php get_template_part('inc/mobiles_header'); ?>
<main>