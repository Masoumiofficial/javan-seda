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
<?php
/* v3.0.4 — CSS حیاتیِ هدر، به‌صورت inline در <head>:
   اگر style.css روی هاست قدیمی بماند یا CDN/کش نسخهٔ کهنهٔ آن را سرو کند،
   نمایش هدر موبایل باز هم درست کار می‌کند. کلاس ab-has-mh یعنی «هدر موبایل رندر شد».
   ارتفاع هدر موبایل ۶۵px است؛ پس padding هم ۶۵px (رفع ۵ پیکسل پوشاندن محتوا). */
?>
<style id="ab-header-critical">
body{padding-top:0;}
body.ab-has-mh{padding-top:65px;}
body.ab-has-mh #mobiles_header{display:block !important;visibility:visible !important;height:65px !important;overflow:visible !important;}
body:not(.ab-has-mh) #mobiles_header{display:none !important;}
body.ab-has-mh #header{display:none !important;}
#header{margin-bottom:28px !important;}
@media (min-width: 801px){
body.ab-has-mh #mobiles_header{display:block !important;}
}
@media (max-width: 800px){
body:not(.ab-has-mh) #header{display:block !important;padding:12px 0 !important;}
body:not(.ab-has-mh) #header .menu_right{max-width:60% !important;}
}
</style>
</head>
<?php
/* v3.0.3 — تک‌منبع تشخیص موبایل: همین متغیر هم رندر را کنترل می‌کند و هم کلاس بدنه را */
$ab_mobile_ua = wp_is_mobile();
$darks = ot_get_option('dark_modes');
if ( 'off' != $darks ) { ?>
<body <?php body_class( $ab_mobile_ua ? 'night ab-has-mh' : 'night' ); ?>>
<?php } else { ?>
<body <?php body_class( $ab_mobile_ua ? 'ab-has-mh' : '' ); ?>>
<?php } ?>
<?php if ( ! $ab_mobile_ua ) { ?> 
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
<?php
/* هدر موبایل: فقط روی موبایل رندر می‌شود (قانون ۵) ولی اگر این خط حذف شود،
   سایت روی موبایل «هیچ» هدری نخواهد داشت — رندرِ مشروط، نه حذف. */
if ( $ab_mobile_ua ) {
	get_template_part( 'inc/mobiles_header' );
}
?>
<main>