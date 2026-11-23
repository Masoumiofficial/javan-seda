<?php
/**
 * inc/tara-related-p1.php — مرتبط‌ها با اولویتِ روشن (دور P1، ۳.۱۷.۰)
 * ====================================================================
 * `inc/tara-p0.php` در ۳.۱۶.۰ «سایر آهنگ‌های هنرمند» را یک‌پارچه کرد و همین
 * حالا هم منبعِ رسمیِ آن بلوک در `single.php` است. این فایل آن را **جایگزین
 * نمی‌کند**؛ فقط یک لایهٔ تکمیلی برای بلوکِ «مرتبط‌های تازه» می‌آورد.
 *
 * اولویت (هر مرحله فقط وقتی اجرا می‌شود که مرحلهٔ قبل به سقف نرسیده باشد):
 *   ۱) همان هنرمند        (تاکسونومی، بعد متای `artist`)
 *   ۲) همان ژانر اصلیِ اثر (فقط اگر هنرمند کافی نداد)
 *   ۳) همان حال‌وهوا       (فقط اگر هر دو بالا کافی ندادند)
 *
 * قواعد سخت:
 *   • هیچ شناسهٔ تکراری در خروجی نیست (نه بین مراحل، نه داخلِ یک مرحله).
 *   • خودِ اثر هرگز برنمی‌گردد.
 *   • هیچ محتوای بی‌ربطی برای «پرکردن» کارت‌ها اضافه نمی‌شود: اگر مرحله‌ای
 *     داده نداشت، همان‌جا متوقف می‌شویم و خروجی کوتاه‌تر (یا خالی) است.
 *   • همهٔ لینک‌ها HTML خزش‌پذیرند؛ هیچ رندرِ AJAX لازم نیست.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_related_tracks' ) ) {
	/**
	 * شناسه‌های مرتبط با اولویتِ هنرمند → ژانر → حال‌وهوا.
	 *
	 * @param int   $post_id شناسهٔ اثر.
	 * @param int   $limit   تعداد (۱..۳۰).
	 * @param array $exclude شناسه‌های اضافی برای استثنا.
	 * @return int[]
	 */
	function tara_related_tracks( $post_id = 0, $limit = 8, $exclude = array() ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		$limit   = max( 1, min( 30, (int) $limit ) );
		if ( ! $post_id ) {
			return array();
		}

		$exclude   = array_map( 'absint', (array) $exclude );
		$exclude[] = $post_id;
		$exclude   = array_values( array_unique( array_filter( $exclude ) ) );

		$ids = array();

		/* ۱) هنرمند — با تابعِ رسمیِ ۳.۱۶.۰ تا رفتار قبلی دست‌نخورده بماند. */
		if ( function_exists( 'tara_related_by_artist' ) ) {
			foreach ( (array) tara_related_by_artist( $post_id, $limit, $exclude ) as $id ) {
				$id = (int) $id;
				if ( $id && ! in_array( $id, $ids, true ) && ! in_array( $id, $exclude, true ) ) {
					$ids[] = $id;
				}
			}
		}

		/* ۲) ژانر، ۳) حال‌وهوا — فقط برای تکمیلِ کمبود. */
		foreach ( array( 'tara_genre', 'tara_mood' ) as $tax ) {
			if ( count( $ids ) >= $limit ) {
				break;
			}
			if ( ! taxonomy_exists( $tax ) || ! function_exists( 'get_the_terms' ) ) {
				continue;
			}
			$terms = get_the_terms( $post_id, $tax );
			if ( ! is_array( $terms ) || empty( $terms ) ) {
				continue;
			}
			$term_ids = array();
			foreach ( $terms as $t ) {
				if ( $t instanceof WP_Term ) {
					$term_ids[] = (int) $t->term_id;
				}
			}
			if ( ! $term_ids ) {
				continue;
			}

			$extra = get_posts(
				array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => $limit,
					'fields'              => 'ids',
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
					'has_password'        => false,
					'post__not_in'        => array_values( array_unique( array_merge( $exclude, $ids ) ) ),
					'orderby'             => 'date',
					'order'               => 'DESC',
					'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => $tax,
							'field'    => 'term_id',
							'terms'    => $term_ids,
						),
					),
				)
			);

			foreach ( (array) $extra as $id ) {
				$id = (int) $id;
				if ( ! $id || in_array( $id, $ids, true ) || in_array( $id, $exclude, true ) ) {
					continue;
				}
				$ids[] = $id;
				if ( count( $ids ) >= $limit ) {
					break 2;
				}
			}
		}

		return array_values( array_unique( $ids ) );
	}
}

if ( ! function_exists( 'tara_related_tracks_html' ) ) {
	/**
	 * بلوک مارک‌آپِ مرتبط‌ها با همان کلاس‌های صفحهٔ آهنگ.
	 *
	 * چرا این بلوک جدا از بلوکِ «سایر آهنگ‌های هنرمند» است؟ آن یکی عنوانش
	 * به هنرمند گره خورده و نباید با محتوای ژانر/حال‌وهوا مخلوط شود. این
	 * بلوک فقط وقتی چاپ می‌شود که **افزون بر** آثار همان هنرمند، اثرِ مرتبطِ
	 * تازه‌ای از ژانر/حال‌وهوا وجود داشته باشد؛ وگرنه رشتهٔ خالی برمی‌گرداند
	 * تا همان تکراری‌شدنِ قبلی تکرار نشود.
	 *
	 * @param int   $post_id شناسه.
	 * @param array $args    limit، title.
	 * @return string
	 */
	function tara_related_tracks_html( $post_id = 0, $args = array() ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		$args    = array_merge( array( 'limit' => 6, 'title' => 'پیشنهادهای بیشتر' ), (array) $args );
		if ( ! $post_id ) {
			return '';
		}

		$by_artist = function_exists( 'tara_related_by_artist' ) ? array_map( 'absint', (array) tara_related_by_artist( $post_id, 30 ) ) : array();
		$all       = tara_related_tracks( $post_id, max( (int) $args['limit'], 1 ) + count( $by_artist ) );
		$extras    = array_values( array_diff( $all, $by_artist ) );

		if ( ! $extras ) {
			return '';
		}
		$extras = array_slice( $extras, 0, max( 1, (int) $args['limit'] ) );

		ob_start();
		?>
		<div class="related_posts rel_sing_bbt" data-tara-related="taxonomy">
			<h3 class="tarlanweb_side_titles"><?php echo esc_html( (string) $args['title'] ); ?></h3>
			<div class="liner clear"></div>
			<div class="tarlanweb_side_box">
				<ul>
					<?php foreach ( $extras as $id ) : ?>
						<?php
						$r_artist = function_exists( 'tara_track_artist' ) ? (string) tara_track_artist( $id ) : (string) get_post_meta( $id, 'artist', true );
						$r_song   = function_exists( 'tara_track_title' ) ? (string) tara_track_title( $id ) : (string) get_the_title( $id );
						$cover    = function_exists( 'tara_track_cover' ) ? (string) tara_track_cover( $id, 'thumb3' ) : (string) get_the_post_thumbnail_url( $id, 'thumb3' );
						?>
						<li>
							<a href="<?php echo esc_url( get_permalink( $id ) ); ?>">
								<div class="tara-thumb-box">
									<img src="<?php echo esc_url( $cover ); ?>" width="60" height="60"
										alt="<?php echo esc_attr( get_the_title( $id ) ); ?>" loading="lazy" decoding="async">
								</div>
								<span class="song_artists"><?php echo esc_html( $r_artist ); ?></span>
								<span><?php echo esc_html( $r_song ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
				<div class="clear"></div>
			</div>
			<div class="clear"></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
