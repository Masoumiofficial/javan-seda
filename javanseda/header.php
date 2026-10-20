<?php
/* ─────────────── جوان‌صدا ۳.۵.۰ — قفلِ بازگشتِ امن ───────────────
   اگر این قالب (یا هر چیزی که لایۀ ما به آن افزوده) روی این سرور خطا بدهد،
   بدون پاک‌کردن هیچ فایلی همین صفحه را به نسخۀ «اصلیِ فروشنده» برگردانید:
       define( 'JAVANSEDA_ORIGINAL_TEMPLATES', true );   // در wp-config.php
   فایلِ اصلی همین مسیر: original/header.php
   ─────────────────────────────────────────────────────────────── */
if ( defined( 'JAVANSEDA_ORIGINAL_TEMPLATES' ) && JAVANSEDA_ORIGINAL_TEMPLATES ) {
	$jv_orig = dirname( __FILE__ ) . '/original/header.php';
	if ( is_readable( $jv_orig ) ) {
		include $jv_orig;
		return;
	}
}
?>
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
<link rel="alternate" href="<?php echo esc_url( home_url() ); ?>/feed" type="application/rss+xml" title="RSS 2.0">
<link rel="shortcut icon" type="image/x-icon" href="<?php echo esc_url( ot_get_option('favicon') ); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url( ot_get_option('logo') ); ?>">
<?php wp_enqueue_script("jquery"); wp_head('');
if (is_singular() && get_option('thread_comments')) wp_enqueue_script('comment-reply'); ?>
<?php
/* v3.1.3 — CSS حیاتیِ هدر، inline و «وابسته به عرضِ صفحه» (نه کاربرگزار).
   دو درسِ این نسخه:
   ۱) در v3.1.2 یک جملهٔ فارسی بیرون از بلوک کامنت افتاده بود و داخل <head> چاپ می‌شد
      (علتِ «سایت کلاً خراب شد»). این‌جا همه‌چیز داخل کامنت است و هیچ متنی print نمی‌شود.
   ۲) نمایش/عدم‌نمایش هدر موبایل دیگر به wp_is_mobile() وابسته نیست؛ فقط با media query
      انجام می‌شود، پس کشِ «نسخهٔ موبایل/دسکتاپ» و فیلترهای wp_is_mobile دیگر نمی‌توانند
      هدر را در یک دستگاه خراب کنند. مارک‌آپ در همهٔ دستگاه‌ها یکسان است (cache-friendly).
   ارتفاع هدر موبایل: ۷۰px | فاصلهٔ بالای صفحه در موبایل: ۹۰px (۷۰ + ۲۰). */
?>
<style id="ab-header-critical">
body{padding-top:0;}
/* هدر موبایل به‌صورت پیش‌فرض پنهان است و فقط در عرضِ موبایل/تبلت ظاهر می‌شود */
#mobiles_header{display:none;}
#header{margin-bottom:28px !important;}
@media (max-width: 800px){
body{padding-top:90px !important;}
#mobiles_header{display:block !important;visibility:visible !important;height:70px !important;overflow:visible !important;}
#header{display:none !important;}
#header .menu_right{max-width:100% !important;}
.tarlanweb_center{width:auto !important;max-width:100% !important;margin-right:12px;margin-left:12px;}
.single_posts_right,.single_posts_left{width:100% !important;float:none !important;}
.single_featured_image,.single_content_box img,.single_content_box iframe{max-width:100% !important;height:auto;}
#centers_box,.single_content_box{overflow:visible;}
.tarlanweb_center{max-width:100% !important;}
}
/* سازگاری با نسخه‌های قبلی که کلاس ab-has-mh را روی <body> می‌گذاشتند (بی‌اثر اگر نبود) */
body.ab-has-mh{padding-top:90px;}
@media (min-width: 801px){body.ab-has-mh #mobiles_header{display:none !important;}}
</style>
</head>
<?php $darks = ot_get_option('dark_modes'); if ( 'off' != $darks ) { ?>
<body <?php body_class('night'); ?>>
<?php } else { ?>
<body <?php body_class(''); ?>>
<?php } ?>
<?php /* v3.3.8 — هوک استاندارد بدنه (پلاگین‌ها/اسکیپ‌لینک از همین‌جا درج می‌شوند) */ ?>
<?php if ( function_exists( 'wp_body_open' ) ) { wp_body_open(); } ?>
<?php /* v3.4.2 — متنِ راهنما عمداً داخل بلوک PHP است (در 3.4.1 بعد از ?> رفته و چاپ می‌شد).
   خاموشیِ این لینک: «تنظیمات قالب ← کنترل رابط ← لینک پرش به محتوا»
   یا define( 'JAVANSEDA_SKIP_LINK', false ); */ ?>
<?php if ( ( ! defined( 'JAVANSEDA_SKIP_LINK' ) || JAVANSEDA_SKIP_LINK ) && ( ! function_exists( 'javan_seda_ui_on' ) || javan_seda_ui_on( 'ab_skip_link', true, 'JAVANSEDA_SKIP_LINK' ) ) ) { ?>
<a class="ab-skip-link" href="#centers_box">پرش به محتوای اصلی</a>
<?php } /* JAVANSEDA_SKIP_LINK */ ?>
<header id="header">
<div class="tarlanweb_center">
<?php if(is_home()){ ?> <h1 class="h1_hidden_home"><?php echo esc_html( ot_get_option('head_h1') ); ?></h1> <?php } ?>
<div class="logo_top"><figure><a href="<?php echo esc_url( home_url() ); ?>"><img src="<?php echo esc_url( ot_get_option('logo') ); ?>" width="150" height="40" alt="<?php echo esc_attr( ot_get_option('head_h1') ); ?>" title="<?php echo ot_get_option('head_h1'); ?>"></a></figure></div>
<nav class="menu_right">
<?php wp_nav_menu(array('theme_location'=>'top_menu','menu_id'=>'rkianoosh_ir','container'=>'')); ?>
<div class="clear"></div>
</nav>
<form id="searches" method="get" action="<?php echo esc_url( home_url() ); ?>">
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
<?php
/* هدر موبایل همیشه رندر می‌شود؛ CSSِ بالا تصمیم می‌گیرد کِی دیده شود.
   این خط هرگز نباید حذف شود — نبودش = نداشتن هدر در گوشی. */
get_template_part('inc/mobiles_header');
?>
<main>
