<?php $index_pv = ot_get_option('index_pv'); if('off' != $index_pv){ ?>
<!-- song_boxs -->
<div class="song_boxs">
<?php /* v3.1.5 — اگر لایۀ «محبوبیت» (inc/ab-views.php) نصب باشد، ترتیب بر پایهٔ بازدیدِ همین
   ماه (views_m) است و کل کاتالوگ شمرده می‌شود؛ اگر نباشد یا دادهٔ ماهانه کم باشد،
   دقیقاً رفتار قبلی برمی‌گردد: بازدیدِ کل، فقط آثارِ منتشرشده در ۳۰ روز اخیر. */
$ab_mkey = function_exists('javan_seda_views_month_key') ? javan_seda_views_month_key() : 'views';
$today = getdate();
$arms = array(
'post_type'=>'post','posts_per_page'=>(int) ot_get_option('index_nm',6),'offset'=>0,
'meta_key'=>$ab_mkey,'orderby'=>'meta_value_num','post_status'=>'publish','ignore_sticky_posts'=>true);
if ('views_m' !== $ab_mkey) {
$arms['date_query']= array(array('column' =>'post_date_gmt','after' =>'1 month ago'));
}
$the_query = new WP_Query( $arms ); ?>
<?php if ($the_query->have_posts()) : ?>
<div class="songs_title_box">
<h3><?php echo esc_html( ot_get_option('index_pt') ); ?></h3> 
<div class="clear"></div>
<div class="liner"></div>
</div>
<div class="song_boxs_posts">
<ul>
<?php while($the_query->have_posts()) : $the_query->the_post(); ?>
<li><?php get_template_part('inc/songs_post'); ?></li>
<?php endwhile; wp_reset_postdata(); ?>
</ul>
<div class="clear"></div>
</div>
<?php endif; ?>
<div class="clear"></div>
</div>
<!-- song_boxs -->
<?php } ?>