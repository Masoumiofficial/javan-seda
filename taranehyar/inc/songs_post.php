<?php
/* v3.3.7 — آمارِ اثر روی کاور: «تعداد بازدید» (متای views) و «میانگین رأی» (kksr_avg /
   kksr_ratings ÷ kksr_casts). چرا این‌جا؟ چون همین فایل کارتِ همهٔ ریل‌هاست (خانۀ سایت،
   دسته‌ها، جست‌وجو، صفحۀ هنرمند) ⇒ با یک نقطۀ افزودن، همه یکسان می‌شوند.
   خاموشی کامل:  define( 'TARA_CARD_STATS', false );   یا   add_filter( 'tara_card_stats', '__return_false' );
   بدون JS، بدون کوئری اضافه (get_post_meta از کشِ متای همان درخواست می‌خواند). */
if ( ! function_exists( 'tara_card_stats' ) ) {
	function tara_card_stats( $ab_post_id = 0 ) {
		static $on = null;
		if ( null === $on ) {
			$on = ( ! defined( 'TARA_CARD_STATS' ) || TARA_CARD_STATS )
				&& ( ! function_exists( 'tara_ui_on' ) || tara_ui_on( 'ab_card_stats', true, 'TARA_CARD_STATS' ) ) /* v3.4.1 — تنظیمات قالب */
				&& apply_filters( 'tara_card_stats', true );
		}
		if ( ! $on ) {
			return;
		}
		$id    = $ab_post_id ? (int) $ab_post_id : (int) get_the_ID();
		$views = (int) get_post_meta( $id, 'views', true );
		$casts = (int) get_post_meta( $id, 'kksr_casts', true );
		$avg   = (float) get_post_meta( $id, 'kksr_avg', true );
		if ( $avg <= 0 ) {
			$sum = (float) get_post_meta( $id, 'kksr_ratings', true );
			$avg = ( $casts > 0 && $sum > 0 ) ? $sum / $casts : 0.0;
		}
		$avg = max( 0.0, min( 5.0, (float) $avg ) );
		if ( $views < 1 && $avg < 0.05 ) {
			return; /* هیچ عددی ثبت نشده ⇒ کارۀ خالی نسازیم */
		}
		$out  = '<div class="ab-card-stats"><div class="ab-card-stats__in">';
		if ( $views > 0 ) {
			$out .= '<span class="ab-cs-num"><i class="icofont-eye-alt" aria-hidden="true"></i>' . esc_html( number_format_i18n( $views ) )
			      . '<span class="screen-reader-text">بازدید این اثر</span></span>';
		}
		if ( $avg > 0 ) {
			$pct = round( $avg / 5 * 100, 1 );
			$out .= '<span class="ab-cs-num ab-cs-num--rate"><span class="ab-cs-stars" aria-hidden="true"><span>' . str_repeat( '☆', 5 )
			      . '</span><b style="width:' . esc_attr( $pct ) . '%">' . str_repeat( '★', 5 ) . '</b></span><i>'
			      . esc_html( number_format_i18n( $avg, 1 ) )
			      . '<span class="screen-reader-text"> از ۵</span></i>';
			if ( $casts > 0 ) {
				$out .= '<em>' . esc_html( number_format_i18n( $casts ) ) . '<span class="screen-reader-text"> رأی</span></em>';
			}
			$out .= '</span>';
		}
		echo $out . '</div></div>';
	}
}
?>
<article class="songs_post">
<header>
<figure><a href="<?php the_permalink(); ?>">
<?php /* v3.1.4 — یک سایز برای همهٔ دستگاه‌ها (thumb2 = 190×190) + lazy/async؛ دیگر wp_is_mobile() تصمیم نمی‌گیرد
      (تصمیمِ UA‌محور با کش می‌جنگید و در موبایل تصویر ۱۵۰px در کادر ۱۹۰px تار می‌شد).
      rel="nofollow" هم از لینکِ داخلی برداشته شد؛ این ویژگی فقط برای لینک خروجی لازم است. */
$ab_img_attr = array( 'alt' => get_the_title(), 'title' => get_the_title(), 'loading' => 'lazy', 'decoding' => 'async' );
if ( has_post_thumbnail() ) { /* v3.3.8 — دو سایز به WP بدهید تا خودکار srcset/sizes بسازد (۱۹۰ و ۳۸۰ ⇒ شارپ روی DPR2) */ the_post_thumbnail( array( 'thumb2', 'thumb2x' ), $ab_img_attr ); } else { ?>
<img src="<?php echo esc_url( get_template_directory_uri() . '/images/nomusics.jpg' ); ?>" width="190" height="190" alt="<?php echo esc_attr( get_the_title() ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" decoding="async"><?php } ?></a><?php tara_card_stats(); ?></figure>
<h2><a href="<?php echo esc_url( get_permalink() ); ?>"><?php if(get_field('artist') || get_field('song')){ ?> <?php echo esc_html( get_field('artist') ); ?><span><?php echo esc_html( get_field('song') ); ?></span><?php } else{ the_title(''); } ?></a></h2>
</header>
<footer>
<?php $field=get_field_object('musics_type'); if($field){
$value=$field['value']; $label=$field['choices'][$value]; ?>
<span class="ft_box_format"><?php echo esc_attr($value); ?></span><?php } ?>
</footer>
</article>