<article class="songs_post">
<header>
<figure><a href="<?php the_permalink(); ?>">
<?php /* v3.1.4 — یک سایز برای همهٔ دستگاه‌ها (thumb2 = 190×190) + lazy/async؛ دیگر wp_is_mobile() تصمیم نمی‌گیرد
      (تصمیمِ UA‌محور با کش می‌جنگید و در موبایل تصویر ۱۵۰px در کادر ۱۹۰px تار می‌شد).
      rel="nofollow" هم از لینکِ داخلی برداشته شد؛ این ویژگی فقط برای لینک خروجی لازم است. */
$ab_img_attr = array( 'alt' => get_the_title(), 'title' => get_the_title(), 'loading' => 'lazy', 'decoding' => 'async' );
if ( has_post_thumbnail() ) { the_post_thumbnail( 'thumb2', $ab_img_attr ); } else { ?>
<img src="<?php echo esc_url( get_template_directory_uri() . '/images/nomusics.jpg' ); ?>" width="190" height="190" alt="<?php echo esc_attr( get_the_title() ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" decoding="async"><?php } ?></a></figure>
<h2><a href="<?php echo esc_url( get_permalink() ); ?>"><?php if(get_field('artist') || get_field('song')){ ?> <?php echo esc_html( get_field('artist') ); ?><span><?php echo esc_html( get_field('song') ); ?></span><?php } else{ the_title(''); } ?></a></h2>
</header>
<footer>
<?php $field=get_field_object('musics_type'); if($field){
$value=$field['value']; $label=$field['choices'][$value]; ?>
<span class="ft_box_format"><?php echo esc_attr($value); ?></span><?php } ?>
</footer>
</article>