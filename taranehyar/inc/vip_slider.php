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
			<?php
			/* v3.7.8 — اسلایدر خانه دیگر به owl وابسته نیست.
			   چرا؟ owl برای هر اسلاید یک `.owl-item` با عرض ۳۰۰٪ می‌ساخت و اگر
			   یکی از فایل‌ها دیر می‌رسید یا خطایی می‌داد، ناحیهٔ سفیدِ سه‌برابرِ
			   عرض اسلاید می‌ماند و هیچ تصویری دیده نمی‌شد. حالا یک اسلایدِ
			   ساده (cross-fade + نشانگر) با چند خط جاوااسکریپت بدون وابستگی.
			   مزیت جانبی: DOM سبک‌تر، بدون پرش ارتفاع، بدون درخواست اضافه. */
			?>
			<div class="vip_top_slider ty-hero-track" data-ty-hero data-interval="5200" role="region" aria-roledescription="carousel" aria-label="آثار ویژه">
				<?php
				$tara_slide = 0;
				/* v3.7.9 — عدد کل برای برچسب «n از m» روی هر اسلاید (دسترس‌پذیری). */
				$tara_slide_total = (int) $tara_vip_query->post_count;
				while ( $tara_vip_query->have_posts() ) :
					$tara_vip_query->the_post();
					$tara_slide++;

					/* v3.8.0 — بنر اسلایدر دیگر به «thumb1» (۶۴۰×۳۶۰) محدود نیست
					   و شکلِ برگشتیِ فیلد هم مهم نیست:
					     • آرایه (ACF تازه: ID/url/sizes) → شناسه یا آدرس
					     • عدد → شناسهٔ پیوست
					     • رشته → آدرس دستی
					   اگر شناسه داشته باشیم، `srcset` هم ساخته می‌شود تا گوشی
					   نسخهٔ سبک و دسکتاپ نسخهٔ بزرگ را بگیرد (LCP و حجم). */
					/* v3.8.3 — اولویت با بنرِ تازهٔ ۱۹۲۰×۸۴۰ است (صفحهٔ ویرایش نوشته،
					   متای `_tara_vip_banner`). اگر انتخاب نشده باشد، همان مسیرِ
					   قدیمیِ ACF (`vip_img`) و بعد کاور آهنگ. */
					$tara_image  = '';
					$tara_srcset = '';
					$tara_img_id = function_exists( 'tara_vip_banner_id' ) ? (int) tara_vip_banner_id( get_the_ID() ) : 0;

					$tara_custom = ( $tara_img_id || ! function_exists( 'get_field' ) ) ? '' : get_field( 'vip_img' );

					if ( $tara_img_id ) {
						$tara_image = (string) wp_get_attachment_image_url( $tara_img_id, 'full' );
					} elseif ( is_array( $tara_custom ) ) {
						foreach ( array( 'ID', 'id', 'attachment_id' ) as $tara_id_key ) {
							if ( ! empty( $tara_custom[ $tara_id_key ] ) ) {
								$tara_img_id = (int) $tara_custom[ $tara_id_key ];
								break;
							}
						}
						if ( ! $tara_img_id && ! empty( $tara_custom['url'] ) && is_string( $tara_custom['url'] ) ) {
							$tara_image = trim( $tara_custom['url'] );
						}
					} elseif ( is_numeric( $tara_custom ) ) {
						$tara_img_id = (int) $tara_custom;
					} elseif ( is_string( $tara_custom ) && '' !== trim( $tara_custom ) ) {
						$tara_image = trim( $tara_custom );
					}

					if ( ! $tara_img_id && '' === $tara_image && has_post_thumbnail() ) {
						$tara_img_id = (int) get_post_thumbnail_id();
					}

					if ( $tara_img_id && function_exists( 'wp_get_attachment_image_url' ) ) {
						$tara_full = wp_get_attachment_image_url( $tara_img_id, 'full' );
						if ( $tara_full ) {
							$tara_image = $tara_full;
							if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
								/* v3.8.3 — سایز `tara-vip` (۱۹۲۰×۸۴۰) هم در فهرست است تا
								   مرورگر از نسخهٔ درستِ همان نسبت انتخاب کند. */
								$tara_srcset = (string) wp_get_attachment_image_srcset( $tara_img_id, 'full' );
							}
						}
					}

					if ( ! $tara_image ) {
						$tara_image = get_template_directory_uri() . '/images/vip_img.jpg';
					}

					$tara_vip_song   = function_exists( 'get_field' ) ? (string) get_field( 'song' ) : '';
					$tara_priority   = ( 1 === $tara_slide ) ? 'high' : 'auto';
					$tara_loading    = ( 1 === $tara_slide ) ? 'eager' : 'lazy';
					?>
					<article class="ty-hero-slide<?php echo ( 1 === $tara_slide ) ? ' is-active' : ''; ?>"
						data-ty-slide
						aria-roledescription="slide"
						aria-label="<?php echo esc_attr( $tara_slide . ' از ' . $tara_slide_total ); ?>"
						aria-hidden="<?php echo ( 1 === $tara_slide ) ? 'false' : 'true'; ?>">
						<a class="ty-hero-slide__media" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( '' !== $tara_vip_song ? $tara_vip_song : get_the_title() ); ?>">
							<?php
							/* v3.8.2 — یک تصویر، نه دو.
							   از ۳.۷.۱۰ این‌جا دو <img> با یک آدرس بود: یکی بلورشده در
							   پس‌زمینه و یکی `contain` در رو. کارِ لایهٔ پشت پر کردنِ
							   نوارهای خالیِ contain بود؛ ولی همان نوارها همان «حاشیهٔ
							   ماتِ» ناخوشایندی بودند که کاربر دید. حالا کادر دقیقاً ۱۶:۷
							   است (نسبتِ استانداردِ بنر) و تصویر با `cover` تمامِ کادر را
							   پر می‌کند؛ پس لایهٔ دوم هم بی‌مصرف شد و حذف شد (DOM
							   سبک‌تر، یک درخواست کمتر برای مرورگرهای بدون کش). */
							/* v3.8.3 — ابعادِ درستِ همان نسبت ۱۶:۷ (۱۹۲۰×۸۴۰) تا CLS نباشد؛
							   CSS با `width:100%;height:auto` ظرف را پر می‌کند. */
							$tara_img_attrs = 'width="1920" height="840"';
							?>
							<img class="ty-hero-slide__img" src="<?php echo esc_url( $tara_image ); ?>"
								<?php if ( '' !== $tara_srcset ) { ?>srcset="<?php echo esc_attr( $tara_srcset ); ?>" sizes="(max-width: 782px) 100vw, (max-width: 1340px) 94vw, 1240px"<?php } ?>
								<?php echo $tara_img_attrs; // phpcs:ignore WordPress.Security.EscapeOutput -- ثابت ?>
								alt="<?php echo esc_attr( '' !== $tara_vip_song ? $tara_vip_song : get_the_title() ); ?>"
								loading="<?php echo esc_attr( $tara_loading ); ?>"
								decoding="async"
								draggable="false"
								fetchpriority="<?php echo esc_attr( $tara_priority ); ?>">
						</a>

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
