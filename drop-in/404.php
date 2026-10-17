<?php get_header(''); ?>
<?php get_template_part('inc/breadcrumbs'); ?>
<!-- centers_box -->
<div class="tarlanweb_center">
<div id="centers_box">

<div class="song_boxs">
<div class="songs_title_box">
<h1>این صفحه پیدا نشد (۴۰۴)</h1>
<div class="archive_txt_box">
<p>ممکن است آهنگ حذف شده باشد، نشانی را اشتباه تایپ کرده باشید، یا لینک از جای دیگری شکسته باشد. نام آهنگ یا خواننده را جست‌وجو کنید — یا از فهرستِ زیر استفاده کنید.</p>
<div class="clear"></div>
</div>
<div class="clear"></div>
<div class="liner"></div>
</div>

<form role="search" method="get" class="ab-404-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
<input class="meta searchfield" type="search" name="s" placeholder="نام آهنگ یا خواننده …" aria-label="جست‌وجوی آهنگ">
<button type="submit"><i class="icofont-search" aria-hidden="true"></i> جست‌وجو</button>
</form>

<ul class="ab-404-links">
<li><a href="<?php echo esc_url( home_url( '/music/' ) ); ?>"><i class="icofont-music-note" aria-hidden="true"></i> تازه‌ترین آهنگ‌ها</a></li>
<li><a href="<?php echo esc_url( home_url( '/music-video/' ) ); ?>"><i class="icofont-video" aria-hidden="true"></i> موزیک‌ویدیوها</a></li>
<li><a href="<?php echo esc_url( home_url( '/album/' ) ); ?>"><i class="icofont-music-disk" aria-hidden="true"></i> آلبوم‌ها</a></li>
<li><a href="<?php echo esc_url( home_url( '/singers/' ) ); ?>"><i class="icofont-microphone-alt" aria-hidden="true"></i> همهٔ خواننده‌ها</a></li>
</ul>

<img class="ab-404-art" src="<?php echo esc_url( get_template_directory_uri() . '/images/error404.jpg' ); ?>" width="626" height="496" alt="صفحه پیدا نشد" loading="lazy" decoding="async">

<?php $ab_latest = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 6, 'ignore_sticky_posts' => true ) );
if ( $ab_latest->have_posts() ) : ?>
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
<?php endif; wp_reset_postdata(); ?>

<div class="clear"></div>
</div>

<div class="clear2"></div>
</div>
</div>
<!-- centers_box -->
<?php get_footer(''); ?>