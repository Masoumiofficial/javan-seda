<?php get_header(''); ?>
<?php get_template_part('inc/breadcrumbs'); ?>
<?php $term = get_queried_object(); ?>

<div class="tarlanweb_center">
<div class="artist_box_tarlanweb">
<?php /* v3.4.9 — همان تابع‌های لایه: اگر افزونۀ ACF غیرفعال باشد خطا نمی‌دهد
   (فعلِ get_field بدونِ گارد، تنها نقطۀ شکنندۀ این فایل بود) و عکسِ ردیفِ
   «هنرمندانِ صفحۀ اصلی» را هم به‌عنوان منبع می‌شناسد. */
$ab_term_img     = function_exists( 'javan_seda_term_image' ) ? javan_seda_term_image( $term, 'medium' ) : array();
$ab_term_socials  = array();
$ab_social_labels = array( 'afacebook' => 'facebook', 'atelegram' => 'paper-plane', 'ainstagram' => 'instagram', 'atwitter' => 'twitter', 'ayoutube' => 'youtube-play' );
if ( function_exists( 'javan_seda_term_field' ) ) {
	foreach ( $ab_social_labels as $ab_sk => $ab_sn ) {
		$ab_sv = trim( (string) javan_seda_term_field( $ab_sk, $term, '' ) );
		if ( '' !== $ab_sv ) { $ab_term_socials[ $ab_sk ] = $ab_sv; }
	}
}
if ( ( ! empty( $ab_term_img['url'] ) && '' !== $ab_term_img['url'] ) || $ab_term_socials ) { ?>
<div class="artists_imsf">
<?php if ( ! empty( $ab_term_img['url'] ) && '' !== $ab_term_img['url'] && ( ! empty( $ab_term_img['id'] ) || false === strpos( (string) $ab_term_img['url'], 'def-' ) ) ) : ?>
<img src="<?php echo esc_url( $ab_term_img['url'] ); ?>" width="200" height="200" alt="<?php echo esc_attr( $ab_term_img['alt'] ); ?>" title="<?php echo esc_attr( $ab_term_img['alt'] ); ?>">
<?php endif; ?>
<div class="socials_box">
<span>صفحات مجازی هنرمند</span>
<?php foreach ( $ab_term_socials as $ab_sk => $ab_sv ) : ?>
	<a href="<?php echo esc_url( $ab_sv ); ?>" class="icofont-<?php echo esc_attr( isset( $ab_social_labels[$ab_sk] ) ? $ab_social_labels[$ab_sk] : 'link' ); ?>" rel="nofollow noopener" target="_blank"></a>
<?php endforeach; ?>
<div class="clear"></div>
</div><div class="clear"></div>
</div>
<?php } ?>
<div class="artist_box_detss">
<h1 class="artist_name_head"><?php single_term_title(); ?></h1>	
<div class="clear"></div>
<?php $ab_tag_top = function_exists( 'javan_seda_term_desc_top' ) ? trim( (string) javan_seda_term_desc_top( $term ) ) : trim( (string) get_the_archive_description() ); ?>
<?php if ( '' !== wp_strip_all_tags( $ab_tag_top ) ) : ?>
<div class="artist_txt_box">
<?php echo wp_kses_post( $ab_tag_top ); ?>
<div class="clear"></div>
</div>
<?php endif; ?>
</div>

<div class="clear"></div>
</div>
</div>

<div id="artist_box">

<!-- centers_box -->
<div class="tarlanweb_center">
<div id="centers_box">

<!-- song_boxs -->
<div class="song_boxs">
<?php if(have_posts()) : ?>
<div class="song_boxs_posts">
<ul>
<?php while(have_posts()) : the_post(); ?>
<li><?php get_template_part('inc/songs_post'); ?></li>
<?php endwhile; ?>
</ul>
<div class="clear"></div>
</div>
<?php else: get_template_part('inc/no-content'); endif; ?>
<div class="clear"></div>
</div>
<!-- song_boxs -->	
	
	
	
<?php /* v3.4.9 — توضیحِ کاملِ این برچسب، بسته و با دکمۀ «بیشتر/کمتر» */
$ab_tag_full = function_exists( 'javan_seda_term_desc_bottom' ) ? javan_seda_term_desc_bottom( $term ) : '';
if ( '' !== trim( wp_strip_all_tags( (string) $ab_tag_full ) ) ) : ?>
<div class="artist_txt_box ab-term-more__wrap" style="margin-top:14px">
<?php echo $ab_tag_full; // phpcs:ignore WordPress.Security.EscapeOutput -- داخلِ تابع escape شده ?>
<div class="clear"></div>
</div>
<?php endif; ?>
<?php get_template_part('inc/pagination'); ?>
<div class="clear"></div>
</div>
</div>
<!-- centers_box -->
</div>

<?php get_footer(''); ?>