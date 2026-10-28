<?php get_header(''); ?>
<?php get_template_part('inc/breadcrumbs'); ?>
<?php $term = get_queried_object(); ?>

<?php
/* v3.5.2 — تصویر و شبکه‌های اجتماعی از لایهٔ دادهٔ هنرمند خوانده می‌شوند:
   اول ACF، بعد term meta. قبلاً فقط get_field() بود و اگر ACF نبود همه‌چیز خالی می‌شد. */
$ab_tag_image  = function_exists( 'javan_seda_artist_image_url' ) ? javan_seda_artist_image_url( $term, 'medium' ) : '';
$ab_tag_has    = function_exists( 'javan_seda_artist_has_image' ) ? javan_seda_artist_has_image( $term ) : false;
$ab_tag_social = function_exists( 'javan_seda_artist_socials' ) ? javan_seda_artist_socials( $term ) : array();
$ab_tag_latin  = function_exists( 'javan_seda_artist_field' ) ? javan_seda_artist_field( $term, 'artist_en' ) : '';
?>

<div class="tarlanweb_center">
<div class="artist_box_tarlanweb">
<?php if ( $ab_tag_has || ! empty( $ab_tag_social ) ) { ?>
<div class="artists_imsf">
<?php if ( $ab_tag_has && '' !== $ab_tag_image ) { ?>
<img src="<?php echo esc_url( $ab_tag_image ); ?>" width="200" height="200" alt="<?php echo esc_attr( $term->name ); ?>" title="<?php echo esc_attr( $term->name ); ?>">
<?php } ?>
<div class="socials_box">
<span>صفحات مجازی هنرمند</span>
<?php foreach ( $ab_tag_social as $ab_tag_key => $ab_tag_item ) : ?>
<a href="<?php echo esc_url( $ab_tag_item['url'] ); ?>" class="<?php echo esc_attr( $ab_tag_item['icon'] ); ?>" aria-label="<?php echo esc_attr( $ab_tag_item['label'] ); ?>"></a>
<?php endforeach; ?>
<div class="clear"></div>
</div><div class="clear"></div>
</div>
<?php } ?>
<div class="artist_box_detss">
<h1 class="artist_name_head"><?php single_term_title(); ?></h1>
<?php if ( '' !== $ab_tag_latin ) : ?>
<p class="ab-artist-latin" lang="en" dir="ltr"><?php echo esc_html( $ab_tag_latin ); ?></p>
<?php endif; ?>
<div class="clear"></div>
<?php if ( '' !== trim( wp_strip_all_tags( (string) term_description( $term->term_id, $term->taxonomy ) ) ) ) { ?>
<div class="artist_txt_box">
<?php echo wp_kses_post( term_description( $term->term_id, $term->taxonomy ) ); ?>
<div class="clear"></div>
</div>
<?php } ?>
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

<?php get_template_part('inc/pagination'); ?>
<div class="clear"></div>
</div>
</div>
<!-- centers_box -->
</div>

<?php get_footer(''); ?>
