<?php get_header(''); ?>
<!-- centers_box -->
<div class="tarlanweb_center">
<div id="centers_box">

<!-- نوار زیر هدر — در نسخهٔ 3.1.2 به «اخبار روز» تبدیل شد (منبع: نوشته‌های دستهٔ news) -->
<?php
/* لایهٔ سازگاری: اگر myfunctions.php اعمال نشده باشد، همین‌جا فایل اخبار را بارگذاری می‌کند */
if ( ! function_exists( 'javan_seda_news_ticker' ) ) {
	$_ab_news = trailingslashit( get_template_directory() ) . 'inc/ab-news-ticker.php';
	if ( is_readable( $_ab_news ) ) {
		require_once $_ab_news;
	}
	unset( $_ab_news );
}
if ( function_exists( 'javan_seda_news_ticker' ) ) {
	javan_seda_news_ticker();   // خروجی: <nav class="ab-quickbar ab-news" id="ab-news">
} else {
?>
<!-- fallback — همان نوار دسترسی سریعِ نسخهٔ 3.0.1 (اگر فایل اخبار روی هاست نبود) -->
<nav class="ab-quickbar" aria-label="دسترسی سریع صفحه اصلی">
	<ul class="ab-quickbar__list">
		<li class="ab-quickbar__item">
			<a href="#new-songs" class="ab-quickbar__link">
				<i class="icofont-music-notes"></i>
				<span>تازه‌ترین آهنگ‌ها</span>
			</a>
		</li>
		<li class="ab-quickbar__item">
			<a href="<?php echo esc_url(home_url('/singers/')); ?>" class="ab-quickbar__link">
				<i class="icofont-microphone-alt"></i>
				<span>همهٔ خواننده‌ها</span>
			</a>
		</li>
		<li class="ab-quickbar__item">
			<a href="#popular-songs" class="ab-quickbar__link">
				<i class="icofont-fire-alt"></i>
				<span>پربازدیدترین‌ها</span>
			</a>
		</li>
	</ul>
</nav>
<?php } ?>

<?php get_template_part('inc/vip_slider'); ?>
<?php get_template_part('inc/tarlanweb_player'); ?>
<div id="new-songs" class="ab-section-anchor"></div>
<?php get_template_part('inc/new_posts'); ?>
<div id="popular-songs" class="ab-section-anchor"></div>
<?php get_template_part('inc/mostviews_posts'); ?>
<?php $artista = ot_get_option('hty',array()); foreach($artista as $artise){ ?>	
<!-- song_boxs -->
<div class="song_boxs">
<div class="songs_title_box">
<h3><?php echo $artise['title']; ?></h3>
<?php $category_id = $artise['hty-cat']; $category_link = get_category_link($category_id); ?>
<a href="<?php echo esc_url($category_link); ?>">مشاهده بیشتر</a>
<div class="clear"></div>
<div class="liner"></div>
</div>
<div class="song_boxs_posts">
<ul>
<?php $cat_ft1=$artise['hty-cat']; $num_ft1=$artise['hty-t'];
$my_custom_query= new WP_Query(array(
'post_type' => 'post',
'post_status' => 'publish',
'cat'   =>  $cat_ft1,
'orderby' => 'date',
'posts_per_page' =>$num_ft1
)); if($my_custom_query->have_posts()) : ?>
<?php while($my_custom_query->have_posts()) : $my_custom_query->the_post(); $post_id=get_the_ID(); ?>
<li><?php get_template_part('inc/songs_post'); ?></li>
<?php endwhile; endif; wp_reset_query(); ?>
</ul>
<div class="clear"></div>
</div>
<div class="clear"></div>
</div>
<!-- song_boxs -->
<?php } ?>
<?php get_template_part('inc/artist_slider'); ?>
<div class="clear"></div>
</div>
</div>
<!-- centers_box -->
<?php get_footer(''); ?>
