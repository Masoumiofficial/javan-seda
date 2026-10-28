<?php
/**
 * اسلایدر ویژهٔ صفحهٔ اصلی — نسخهٔ ۳.۷.۰
 * ==========================================================================
 * از «دو اسلاید کنار هم» به **یک اسلاید بزرگ** تبدیل شد: تصویر تمام‌عرض با
 * لایهٔ تیره، نام هنرمند/آهنگ درشت، ژانر و دکمهٔ «شنیدن».
 *
 * چرا؟ در عرض دسکتاپ دو کارت کوچک هم‌زمان دیده می‌شد و هر دو ریز بودند؛ یک
 * اسلاید بزرگ‌تر هم جذاب‌تر است و هم روی موبایل همان چیزی را می‌دهد.
 *
 * تنظیمات: تنظیمات قالب → صفحهٔ اصلی
 *   • «اسلایدر ویژه» (fixbvip)  روشن/خاموش
 *   • «تعداد اسلایدها» (vip_num) ۱ تا ۵ (برای سبکی، سقف ۵)
 *
 * @package Taranehyar
 */

/* v3.7.4 — خواندنِ یکسانِ کلید روشن/خاموش (on/1/true = روشن، off/0/خالی = خاموش) */
$tara_fixbvip = function_exists( 'tara_opt_on' ) ? tara_opt_on( 'fixbvip' ) : ( 'off' !== ot_get_option( 'fixbvip' ) );

if ( $tara_fixbvip ) {

	$tara_vip_num = ot_get_option( 'vip_num' );
	$tara_vip_num = ! empty( $tara_vip_num ) ? (int) $tara_vip_num : 5;
	if ( $tara_vip_num <= 0 || $tara_vip_num > 5 ) {
		$tara_vip_num = 5; // سقف ۵ اسلاید در DOM: سبک‌تر برای موبایل
	}

	$tara_vip_query = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $tara_vip_num,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'vip_song',
					'value'   => '1',
					'compare' => 'LIKE',
				),
			),
		)
	);

	if ( $tara_vip_query->have_posts() ) {
		?>
		<div class="ty-hero-slider ab-vip-slider-box">
			<div class="vip_top_slider owl-carousel owl-theme" data-ab-vip-slider>
				<?php
				$tara_slide = 0;
				while ( $tara_vip_query->have_posts() ) :
					$tara_vip_query->the_post();
					$tara_slide++;

					$tara_custom = function_exists( 'get_field' ) ? get_field( 'vip_img' ) : '';
					$tara_image  = '';
					if ( is_string( $tara_custom ) && '' !== trim( $tara_custom ) ) {
						$tara_image = $tara_custom;
					} elseif ( has_post_thumbnail() ) {
						$tara_image = get_the_post_thumbnail_url( get_the_ID(), 'thumb1' );
					}
					if ( ! $tara_image ) {
						$tara_image = get_template_directory_uri() . '/images/vip_img.jpg';
					}

					$tara_vip_song   = function_exists( 'get_field' ) ? (string) get_field( 'song' ) : '';
					$tara_priority   = ( 1 === $tara_slide ) ? 'high' : 'auto';
					$tara_loading    = ( 1 === $tara_slide ) ? 'eager' : 'lazy';
					?>
					<article class="ty-hero-slide">
						<a class="ty-hero-slide__media" href="<?php the_permalink(); ?>">
							<img src="<?php echo esc_url( $tara_image ); ?>"
								width="1280" height="620"
								alt="<?php echo esc_attr( '' !== $tara_vip_song ? $tara_vip_song : get_the_title() ); ?>"
								loading="<?php echo esc_attr( $tara_loading ); ?>"
								fetchpriority="<?php echo esc_attr( $tara_priority ); ?>">
						</a>

						<?php
						/* v3.7.5 — به درخواست کاربر، تمام متن‌های روی اسلاید حذف شد:
						   برچسب «پیشنهاد ویژه»، عنوان/نام خواننده و دکمهٔ «شنیدن و دانلود».
						   اسلاید حالا فقط کاورِ تمام‌عرض است و کل کارت را می‌پوشاند؛
						   کلیک روی تصویر همان لینک صفحهٔ آهنگ است. */
						?>
					</article>
					<?php
				endwhile;
				?>
			</div>
		</div>
		<?php
	}

	wp_reset_postdata();
}
