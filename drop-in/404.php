<?php
/**
 * 404.php — صفحۀ «پیدا نشد» (نسخۀ 3.3.0)
 * ---------------------------------------------------------------
 *  • پاسخ HTTP همچنان 404 واقعی است (این فایل فقط قالب است؛ status_header چیزی
 *    دست نمی‌زند ⇒ Soft 404 ساخته نمی‌شود).
 *  • چیدمان: نشانِ بزرگ ۴۰۴ + متن + جست‌وجوی واقعی + بازگشت به صفحۀ اصلی +
 *    میان‌بُرهای مفید + شش آینۀ تازه از داده‌های واقعی وردپرس.
 *  • ظاهر شیشه‌ای از css/ab-glass.css می‌آید و تراز/فاصله‌ها از بلوک ۲۶ style.css؛
 *    اگر آن فایل‌ها حذف شوند، همین نشانه‌گذاری ساده و قابل‌استفاده می‌ماند.
 *  • هیچ محتوای ساختگی/دموی درج نشده؛ همه از home_url() و WP_Query واقعی.
 */
get_header('');
?>
<?php get_template_part('inc/breadcrumbs'); ?>
<!-- centers_box -->
<div class="tarlanweb_center">
<div id="centers_box" class="ab-404">

<section class="ab-404-hero ab-404-panel">
<span class="ab-404-badge" aria-hidden="true">۴۰۴</span>
<div class="ab-404-hero__body">
<h1>این صفحه پیدا نشد (۴۰۴)</h1>
<div class="archive_txt_box">
<p>ممکن است آهنگ حذف شده باشد، نشانی را اشتباه تایپ کرده باشید، یا لینک از جای دیگری شکسته باشد. نام آهنگ یا هنرمند را جست‌وجو کنید — یا از فهرستِ زیر استفاده کنید.</p>
<div class="clear"></div>
</div>

<form role="search" method="get" class="ab-404-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
<label class="screen-reader-text" for="ab-404-q">جست‌وجوی آهنگ یا هنرمند</label>
<input id="ab-404-q" class="meta searchfield" type="search" name="s" placeholder="نام آهنگ یا هنرمند …" aria-label="جست‌وجوی آهنگ">
<button type="submit"><i class="icofont-search" aria-hidden="true"></i> جست‌وجو</button>
</form>

<div class="ab-404-actions">
<a class="ab-404-home" href="<?php echo esc_url( home_url( '/' ) ); ?>"><i class="icofont-home" aria-hidden="true"></i>بازگشت به صفحۀ اصلی</a>
<?php $ab_ref = ( function_exists( 'wp_get_referer' ) ) ? wp_get_referer() : '' ?>
<a class="ab-404-back" href="<?php echo esc_url( $ab_ref ? $ab_ref : home_url( '/' ) ); ?>"><i class="icofont-long-arrow-right" aria-hidden="true"></i>صفحۀ قبلی</a>
</div>
</div>
</section>

<ul class="ab-404-links">
<li><a href="<?php echo esc_url( home_url( '/music/' ) ); ?>"><i class="icofont-music-note" aria-hidden="true"></i> تازه‌ترین آهنگ‌ها</a></li>
<li><a href="<?php echo esc_url( home_url( '/music-video/' ) ); ?>"><i class="icofont-video" aria-hidden="true"></i> موزیک‌ویدیوها</a></li>
<li><a href="<?php echo esc_url( home_url( '/album/' ) ); ?>"><i class="icofont-music-disk" aria-hidden="true"></i> آلبوم‌ها</a></li>
<li><a href="<?php echo esc_url( javan_seda_artists_index_url() ); ?>"><i class="icofont-microphone-alt" aria-hidden="true"></i> همهٔ هنرمندان</a></li>
</ul>

<?php
$ab_latest = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 6, 'ignore_sticky_posts' => true ) );
if ( $ab_latest->have_posts() ) : ?>
<div class="song_boxs">
<div class="songs_title_box">
<h3>شاید این‌ها را بخواهید</h3>
<div class="clear"></div>
<div class="liner"></div>
</div>
<div class="song_boxs_posts">
<ul>
<?php while ( $ab_latest->have_posts() ) : $ab_latest->the_post(); ?>
<li><?php get_template_part('inc/songs_post'); ?></li>
<?php endwhile; ?>
</ul>
<div class="clear"></div>
</div>
<div class="clear"></div>
</div>
<?php
wp_reset_postdata();
endif;
?>

<div class="clear"></div>
</div>

<div class="clear2"></div>
</div>
<!-- centers_box -->
<?php get_footer(''); ?>
