<header id="mobiles_header">
<div class="logo_mobiles"><figure>
<a href="<?php echo esc_url( home_url() ); ?>"><img src="<?php echo esc_url( ot_get_option('logo') ); ?>" alt="<?php echo esc_attr( ot_get_option('head_h1') ); ?>" title="<?php echo ot_get_option('head_h1'); ?>" width="150" height="40"></a>
</figure>
</div>
<div class="navicon"><i class="responsive-menu-button-inner"></i></div> 
<div class="circle_hlink search_mobile_box"><i class="icofont-search"></i></div>
<form class="searches_mobile" method="get" action="<?php echo esc_url( home_url() ); ?>">
<input id="lsds_mobile" class="meta searchfield" name="s" data-swplive="true" placeholder="کلمه مورد نظر ..." type="text">
<button><i class="icofont-search"></i></button>
</form>
<div class="mobile_box">
<div class="bodydeactive"></div>
<div class="mobiles_menu">
<div class="top_logo_title">
<a href="<?php echo esc_url( home_url() ); ?>"><img src="<?php echo esc_url( ot_get_option('logo') ); ?>" alt="<?php echo esc_attr( ot_get_option('head_h1') ); ?>" title="get" width="150" height="40"></a>
<div class="clear"></div>
</div>
<?php
/* v3.3.3 — کشوی منوی موبایل هیچ‌وقت خالی نمی‌ماند.
   قبل از این فقط یک wp_nav_menu با theme_location='mobiles_menu' بود؛ اگر آن مکان در
   پیشخوان به منویی وصل نباشد، وردپرس به fallback «فهرست برگه‌ها» می‌رود و در سایتی که
   فقط «آهنگ» دارد یعنی کشوی خالی (آنچه مالک دید: فقط شبکه‌های اجتماعی).
   ترتیب: منوی موبایل ← منوی اصلی (همان که در دسکتاپ است) ← برگه‌ها ← دو پیوند همیشگی.
   کلاسِ walker هم محافظت‌شده است؛ اگر تعریف نشده بود new CSS_Menu_Maker_Walker خطای
   کشنده می‌داد و کل صفحه سفید می‌شد. */
$ab_mm_args = array( 'container' => 'div', 'container_id' => 'cssmenu', 'depth' => 3, 'fallback_cb' => false );
if ( class_exists( 'CSS_Menu_Maker_Walker' ) ) {
	$ab_mm_args['walker'] = new CSS_Menu_Maker_Walker();
}
$ab_mm_done = false;
if ( ! function_exists( 'wp_nav_menu' ) ) {
	$ab_mm_done = true; // بدون هستۀ منو، فهرست پیش‌فرض پایین ساخته می‌شود
}
foreach ( array( 'mobiles_menu', 'top_menu' ) as $ab_mm_loc ) {
	if ( $ab_mm_done ) { continue; }
	if ( function_exists( 'has_nav_menu' ) && has_nav_menu( $ab_mm_loc ) ) {
		$ab_mm_args['theme_location'] = $ab_mm_loc;
		wp_nav_menu( $ab_mm_args );
		$ab_mm_done = true;
		break;
	}
}
if ( ! $ab_mm_done ) {
	echo '<div id="cssmenu"><ul class="ab-mobile-menu-fallback">';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '"><i class="icofont-home" aria-hidden="true"></i> صفحۀ اصلی</a></li>';
	echo '<li><a href="' . esc_url( javan_seda_artists_index_url() ) . '"><i class="icofont-microphone-alt" aria-hidden="true"></i> همهٔ هنرمندان</a></li>';
	if ( function_exists( 'wp_list_pages' ) ) { wp_list_pages( array( 'title_li' => '', 'depth' => 1, 'number' => 6 ) ); }
	echo '</ul></div>';
}
?>
<div class="clear"></div>
<div class="socials_box">
<?php if(ot_get_option('facebook') !=""){ ?>
<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url( ot_get_option('facebook') ); ?>" aria-label="facebook" class="icofont-facebook"></a><?php } if(ot_get_option('instagram') !=""){ ?>
<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url( ot_get_option('instagram') ); ?>" aria-label="instagram" class="icofont-instagram"></a><?php } if(ot_get_option('telegram') !=""){ ?>
<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url( ot_get_option('telegram') ); ?>" aria-label="twitter" class="icofont-paper-plane"></a><?php } if(ot_get_option('twitter') !=""){ ?>
<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url( ot_get_option('twitter') ); ?>" aria-label="twitter" class="icofont-twitter"></a><?php } if(ot_get_option('youtube') !=""){ ?>
<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url( ot_get_option('youtube') ); ?>" aria-label="youtube" class="icofont-youtube-play"></a><?php } if(ot_get_option('aparat') !=""){ ?>
<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url( ot_get_option('aparat') ); ?>" aria-label="aparat" class="icofont-movie"></a><?php } ?>
<div class="clear"></div>
</div>
<div class="clear"></div>
</div>
</div>
</header>