<?php $fixbvip = ot_get_option('fixbvip'); if('off' != $fixbvip){

$vip_np_raw = ot_get_option('vip_num');
$vip_np = !empty($vip_np_raw) ? intval($vip_np_raw) : 5;
if ($vip_np <= 0 || $vip_np > 5) {
	$vip_np = 5; // حداکثر ۵ اسلاید در DOM جهت سبکی و عدم افت کارایی
}

$my_custom_query = new WP_Query(array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => $vip_np,
	'meta_query'     => array(
		array(
			'key'     => 'vip_song',
			'value'   => '1',
			'compare' => 'LIKE',
		),
	),
));

if($my_custom_query->have_posts()) : ?>

<!-- vip_img_box -->

<div class="vip_img_box ab-vip-slider-box">

<div class="vip_top_slider owl-carousel owl-theme" data-ab-vip-slider>

<?php $rkian = 1; while($my_custom_query->have_posts()) : $my_custom_query->the_post();?>

<article class="vip_post ab-vip-post">

<header>

<figure><a href="<?php the_permalink(); ?>">

<?php
$ab_vip_custom_img = get_field('vip_img');
$ab_img_loading    = ($rkian === 1) ? 'eager' : 'lazy';
$ab_fetchpriority  = ($rkian === 1) ? 'high' : 'auto';

if (!empty($ab_vip_custom_img)){ ?>

<img src="<?php echo esc_url($ab_vip_custom_img); ?>" width="640" height="360" alt="<?php the_title_attribute(); ?>" title="<?php the_title_attribute(); ?>" loading="<?php echo esc_attr($ab_img_loading); ?>" fetchpriority="<?php echo esc_attr($ab_fetchpriority); ?>" class="ab-vip-img">

<?php } else {
	if(has_post_thumbnail()){
		the_post_thumbnail('thumb1', array(
			'alt'           => get_the_title(),
			'title'         => get_the_title(),
			'loading'       => $ab_img_loading,
			'fetchpriority' => $ab_fetchpriority,
			'class'         => 'ab-vip-img',
			'width'         => 640,
			'height'        => 360,
		));
	} else { ?>

<img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/vip_img.jpg" width="640" height="360" alt="تصویر پیدا نشد" title="تصویر پیدا نشد" loading="<?php echo esc_attr($ab_img_loading); ?>" class="ab-vip-img">

<?php } } ?></a></figure>

<h2><a href="<?php the_permalink(); ?>"><?php
$ab_artist_field = get_field('artist');
$ab_song_field   = get_field('song');
if(!empty($ab_artist_field) || !empty($ab_song_field)){
	echo esc_html($ab_artist_field); ?> - <span><?php echo esc_html($ab_song_field); ?></span><?php
} else {
	the_title('');
} ?></a></h2>

</header>	

<footer>

<?php
$field = function_exists('get_field_object') ? get_field_object('musics_type') : null;
if($field && !empty($field['value'])){
	$value = $field['value'];
	$label = isset($field['choices'][$value]) ? $field['choices'][$value] : $value;
?>
<span class="ft_box_format ab-format-badge"><?php echo esc_html($label); ?></span><?php } ?>

</footer>

<div class="clear"></div>

</article>

<?php $rkian++; endwhile; ?>

</div>

<div class="clear"></div>

</div>

<!-- vip_img_box -->

<?php endif; wp_reset_query(); } ?>
