<?php
/**
 * سربرگِ غنیِ آرشیو «ژانر» و «حال‌وهوا» (نسخهٔ ۳.۹.۰)
 * ===========================================================================
 * پیش از این، صفحهٔ هر ژانر/حال‌وهوا فقط یک تیتر و یک شمارنده داشت. این فایل
 * همان صفحه را به یک «صفحهٔ فرود» تبدیل می‌کند:
 *
 *   • سربرگِ تصویری با کاورِ سه آهنگِ تازه (بدونِ درخواستِ اضافه)
 *   • آمارِ واقعیِ همان ترم: آهنگ، خواننده، مجموع بازدید، تازه‌ترین اثر
 *   • چیپ‌های ترم‌های هم‌خانواده + ترم‌های متقاطع (ژانر ↔ حال‌وهوا)
 *   • «هنرمندانِ این بخش» با لینک به صفحهٔ هر هنرمند
 *   • متنِ معرفیِ یکتا برای همین ترم (سئوی محتوا، نه متنِ تکراری)
 *   • داده‌ساختاریِ CollectionPage + MusicPlaylist
 *
 * همهٔ محاسبه‌ها در یک transient شش‌ساعته کش می‌شوند؛ کلیدِ کش شمارندهٔ ترم را
 * هم دارد، پس با انتشارِ آهنگِ تازه (که count را عوض می‌کند) خودبه‌خود نو
 * می‌شود. هیچ داده‌ای نوشته نمی‌شود.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_tax_is_music' ) ) {
	/**
	 * آیا این تاکسونومی «ژانر/حال‌وهوا» است؟ (همان دو ترمی که صفحهٔ خفن می‌گیرند)
	 *
	 * @param string $taxonomy نامک.
	 * @return bool
	 */
	function tara_tax_is_music( $taxonomy ) {
		return in_array( (string) $taxonomy, array( 'tara_genre', 'tara_mood' ), true );
	}
}

if ( ! function_exists( 'tara_tax_short_number' ) ) {
	/**
	 * عددِ کوتاهِ فارسی: ۹۸۰ / ۱۲.۴ هزار / ۳.۲ میلیون.
	 *
	 * @param int $number عدد.
	 * @return string
	 */
	function tara_tax_short_number( $number ) {
		$number = (int) $number;

		if ( $number >= 1000000 ) {
			$text = rtrim( rtrim( number_format( $number / 1000000, 1, '.', '' ), '0' ), '.' ) . ' میلیون';
		} elseif ( $number >= 1000 ) {
			$text = rtrim( rtrim( number_format( $number / 1000, 1, '.', '' ), '0' ), '.' ) . ' هزار';
		} else {
			$text = (string) $number;
		}

		return function_exists( 'tara_fa_number' ) ? tara_fa_number( $text ) : $text;
	}
}

if ( ! function_exists( 'tara_tax_music_ids' ) ) {
	/**
	 * شناسهٔ آخرین آهنگ‌های یک ترم (یک کوئری، برای آمار و کاورها).
	 *
	 * @param WP_Term $term  ترم.
	 * @param int     $limit سقف.
	 * @return int[]
	 */
	function tara_tax_music_ids( $term, $limit = 240 ) {
		if ( ! $term instanceof WP_Term ) {
			return array();
		}

		$ids = get_posts(
			array(
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'posts_per_page'         => max( 1, min( 300, (int) $limit ) ),
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy' => $term->taxonomy,
						'field'    => 'term_id',
						'terms'    => (int) $term->term_id,
					),
				),
			)
		);

		return array_values( array_map( 'absint', (array) $ids ) );
	}
}

if ( ! function_exists( 'tara_tax_post_terms' ) ) {
	/**
	 * ترم‌های یک تاکسونومی برای مجموعه‌ای از نوشته‌ها — با یک کوئری.
	 *
	 * چرا `get_the_terms` و نه `wp_get_object_terms`؟ هستهٔ وردپرس با
	 * `update_object_term_cache()` همهٔ نسبت‌ها را در یک کوئری می‌آورد و بعد
	 * `get_the_terms` فقط از کش می‌خواند؛ هم سریع‌تر است و هم برای شمارشِ
	 * «چند آهنگ از این خواننده» ساده‌تر (تکرارها عمداً نگه داشته می‌شوند).
	 *
	 * @param int[]  $ids      شناسهٔ نوشته‌ها.
	 * @param string $taxonomy نامک.
	 * @return WP_Term[]
	 */
	function tara_tax_post_terms( $ids, $taxonomy ) {
		$ids = array_map( 'absint', (array) $ids );
		if ( empty( $ids ) || ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		if ( function_exists( 'update_object_term_cache' ) ) {
			update_object_term_cache( $ids, 'post' );
		}

		$out = array();
		foreach ( $ids as $post_id ) {
			$terms = get_the_terms( $post_id, $taxonomy );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( $term instanceof WP_Term ) {
						$out[] = $term;
					}
				}
			}
		}

		return $out;
	}
}

if ( ! function_exists( 'tara_tax_stats' ) ) {
	/**
	 * آمارِ یک ترم: آهنگ، خواننده، بازدید، تازه‌ترین، کاورها.
	 *
	 * @param WP_Term $term ترم.
	 * @return array
	 */
	function tara_tax_stats( $term ) {
		$empty = array(
			'tracks'   => 0,
			'artists'  => 0,
			'views'    => 0,
			'latest'   => '',
			'covers'   => array(),
			'has_new'  => false,
		);

		if ( ! $term instanceof WP_Term ) {
			return $empty;
		}

		$key    = 'tara_tax_' . $term->taxonomy . '_' . $term->term_id . '_' . (int) $term->count;
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return wp_parse_args( $cached, $empty );
		}

		$ids   = tara_tax_music_ids( $term, 240 );
		$stats = $empty;
		$stats['tracks'] = (int) $term->count;

		if ( ! empty( $ids ) ) {
			if ( function_exists( 'update_meta_cache' ) ) {
				update_meta_cache( 'post', $ids );
			}

			$latest_ts = 0;
			$latest_id = 0;

			foreach ( $ids as $post_id ) {
				$stats['views'] += (int) get_post_meta( $post_id, 'views', true );

				$ts = (int) get_post_time( 'U', true, $post_id );
				if ( $ts > $latest_ts ) {
					$latest_ts = $ts;
					$latest_id = (int) $post_id;
				}

				if ( count( $stats['covers'] ) < 3 && function_exists( 'tara_track_cover' ) ) {
					$cover = tara_track_cover( $post_id, 'thumb2' );
					if ( '' !== (string) $cover ) {
						$stats['covers'][] = (string) $cover;
					}
				}
			}

			if ( $latest_id ) {
				/* «۳ روز پیش» از «۱۹۷۰-۰۱-۰۱» خواناتر است و به تقویمِ سایت
				   وابسته نیست (date_i18n در وردپرسِ فارسی هم همیشه شمسی
				   برنمی‌گرداند). human_time_diff خودش ترجمه می‌شود. */
				if ( function_exists( 'human_time_diff' ) ) {
					$diff = human_time_diff( $latest_ts, time() );
					$diff = function_exists( 'tara_fa_number' ) ? tara_fa_number( $diff ) : $diff;
					/* translators: %s: فاصلهٔ زمانی، مثل «۳ روز» */
					$stats['latest'] = sprintf( '%s پیش', $diff );
				} else {
					$date            = function_exists( 'date_i18n' ) ? date_i18n( 'j F Y', $latest_ts ) : gmdate( 'Y-m-d', $latest_ts );
					$stats['latest'] = function_exists( 'tara_fa_number' ) ? tara_fa_number( $date ) : $date;
				}
				$stats['has_new'] = ( time() - $latest_ts ) < 45 * DAY_IN_SECONDS;
			}

			$artist_tax = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
			$artist_ids = array();
			foreach ( tara_tax_post_terms( $ids, $artist_tax ) as $artist_term ) {
				$artist_ids[ (int) $artist_term->term_id ] = true;
			}
			$stats['artists'] = count( $artist_ids );
		}

		set_transient( $key, $stats, 6 * HOUR_IN_SECONDS );

		return $stats;
	}
}

if ( ! function_exists( 'tara_tax_spotlight' ) ) {
	/**
	 * هنرمندانِ پرتکرارِ همین ترم (برای لینک‌سازی داخلی و جذابیتِ صفحه).
	 *
	 * @param WP_Term $term  ترم.
	 * @param int     $limit تعداد.
	 * @return array<int,array{term:WP_Term,count:int,image:string,url:string}>
	 */
	function tara_tax_spotlight( $term, $limit = 6 ) {
		if ( ! $term instanceof WP_Term ) {
			return array();
		}

		$key    = 'tara_tax_spot_' . $term->taxonomy . '_' . $term->term_id . '_' . (int) $term->count;
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$ids        = tara_tax_music_ids( $term, 200 );
		$artist_tax = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
		$out        = array();

		if ( ! empty( $ids ) ) {
			$tally = array();

			foreach ( tara_tax_post_terms( $ids, $artist_tax ) as $one ) {
				$tid = (int) $one->term_id;
				if ( ! isset( $tally[ $tid ] ) ) {
					$tally[ $tid ] = array( 'term' => $one, 'count' => 0 );
				}
				$tally[ $tid ]['count']++;
			}

			uasort(
				$tally,
				function ( $a, $b ) {
					return $b['count'] - $a['count'];
				}
			);

			foreach ( array_slice( array_values( $tally ), 0, max( 1, (int) $limit ) ) as $row ) {
				$link = get_term_link( $row['term'] );
				if ( is_wp_error( $link ) ) {
					continue;
				}
				$out[] = array(
					'term'  => $row['term'],
					'count' => (int) $row['count'],
					'image' => function_exists( 'tara_artist_image_url' ) ? tara_artist_image_url( $row['term'], 'thumb2' ) : '',
					'url'   => (string) $link,
				);
			}
		}

		set_transient( $key, $out, 6 * HOUR_IN_SECONDS );

		return $out;
	}
}

if ( ! function_exists( 'tara_tax_cross_terms' ) ) {
	/**
	 * ترم‌های «آن‌سوی» همین آهنگ‌ها: در صفحهٔ ژانر، حال‌وهواها و برعکس.
	 *
	 * @param WP_Term $term ترم.
	 * @param int     $limit تعداد.
	 * @return array<int,array{term:WP_Term,count:int,url:string}>
	 */
	function tara_tax_cross_terms( $term, $limit = 8 ) {
		if ( ! $term instanceof WP_Term || ! tara_tax_is_music( $term->taxonomy ) ) {
			return array();
		}

		$other  = 'tara_genre' === $term->taxonomy ? 'tara_mood' : 'tara_genre';
		$key    = 'tara_tax_cross_' . $term->taxonomy . '_' . $term->term_id . '_' . (int) $term->count;
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$ids = tara_tax_music_ids( $term, 160 );
		$out = array();
		$tally = array();

		foreach ( tara_tax_post_terms( $ids, $other ) as $one ) {
			$tid = (int) $one->term_id;
			if ( ! isset( $tally[ $tid ] ) ) {
				$tally[ $tid ] = array( 'term' => $one, 'count' => 0 );
			}
			$tally[ $tid ]['count']++;
		}

		uasort(
			$tally,
			function ( $a, $b ) {
				return $b['count'] - $a['count'];
			}
		);

		foreach ( array_slice( array_values( $tally ), 0, max( 1, (int) $limit ) ) as $row ) {
			$link = get_term_link( $row['term'] );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$out[] = array(
				'term'  => $row['term'],
				'count' => (int) $row['count'],
				'url'   => (string) $link,
			);
		}

		set_transient( $key, $out, 6 * HOUR_IN_SECONDS );

		return $out;
	}
}

if ( ! function_exists( 'tara_tax_siblings' ) ) {
	/**
	 * ترم‌های هم‌خانواده (همان تاکسونومی)، پرتعدادترین‌ها اول.
	 *
	 * @param WP_Term $term  ترم.
	 * @param int     $limit تعداد.
	 * @return WP_Term[]
	 */
	function tara_tax_siblings( $term, $limit = 14 ) {
		if ( ! $term instanceof WP_Term ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $term->taxonomy,
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => max( 2, (int) $limit + 1 ),
				'exclude'    => array( (int) $term->term_id ),
			)
		);

		return is_wp_error( $terms ) ? array() : array_slice( (array) $terms, 0, max( 1, (int) $limit ) );
	}
}

if ( ! function_exists( 'tara_tax_intro' ) ) {
	/**
	 * متنِ معرفیِ یکتای همین ترم (سئوی محتوا).
	 *
	 * قاعده: از داده‌های واقعیِ همین ترم ساخته می‌شود (شمارِ آهنگ، خواننده‌ها،
	 * ترم‌های متقاطع) و به هیچ عنوان متنِ ثابتِ تکراری نیست.
	 *
	 * @param WP_Term $term      ترم.
	 * @param array   $stats     خروجی tara_tax_stats().
	 * @param array   $spotlight خروجی tara_tax_spotlight().
	 * @param array   $cross     خروجی tara_tax_cross_terms().
	 * @return string[] پاراگراف‌ها.
	 */
	function tara_tax_intro( $term, $stats, $spotlight, $cross ) {
		if ( ! $term instanceof WP_Term ) {
			return array();
		}

		$label = function_exists( 'tara_music_taxonomy_label' ) ? tara_music_taxonomy_label( $term->taxonomy, 'singular' ) : 'بخش';
		$fa    = function_exists( 'tara_fa_number' ) ? 'tara_fa_number' : 'strval';
		$name  = $term->name;
		$paras = array();

		$count   = (int) $stats['tracks'];
		$artists = (int) $stats['artists'];

		$paras[] = sprintf(
			'«%1$s» یکی از %2$sهای %3$s است؛ %4$s آهنگ از %5$s خواننده در این بخش گرد آمده که همگی با پخشِ آنلاین، متنِ ترانه و دانلودِ ۱۲۸ و ۳۲۰ در دسترس‌اند.',
			$name,
			$label,
			(string) get_bloginfo( 'name' ),
			call_user_func( $fa, $count ),
			call_user_func( $fa, $artists )
		);

		if ( ! empty( $spotlight ) ) {
			$names = array();
			foreach ( array_slice( $spotlight, 0, 4 ) as $row ) {
				$names[] = $row['term']->name;
			}
			$paras[] = 'پرکارترین خواننده‌های این بخش ' . implode( '، ', $names ) . ' هستند؛ روی هر نام بزنید تا صفحهٔ اختصاصیِ همان هنرمند و همهٔ آثارش باز شود.';
		}

		if ( ! empty( $cross ) ) {
			$names = array();
			foreach ( array_slice( $cross, 0, 6 ) as $row ) {
				$names[] = $row['term']->name;
			}
			$other = 'tara_genre' === $term->taxonomy ? 'حال‌وهوا' : 'ژانر';
			$paras[] = 'آهنگ‌های «' . $name . '» در ' . $other . 'های ' . implode( '، ', $names ) . ' هم دسته‌بندی شده‌اند؛ از چیپ‌های بالای صفحه سریع‌تر می‌رسید.';
		}

		$paras[] = 'برای شنیدنِ پشتِ‌سرهم، از «رادیو ترانه یار» بالای همین صفحه استفاده کنید: میکسِ خودکار با کراس‌فید، بدونِ نیاز به انتخابِ تک‌تکِ آهنگ‌ها.';

		return apply_filters( 'tara_tax_intro_paragraphs', $paras, $term, $stats );
	}
}

if ( ! function_exists( 'tara_tax_hero' ) ) {
	/**
	 * سربرگِ کاملِ آرشیو موسیقی.
	 *
	 * @param WP_Term $term ترم.
	 * @param string  $desc توضیحِ دسته (از وردپرس).
	 * @return string
	 */
	function tara_tax_hero( $term, $desc = '' ) {
		if ( ! $term instanceof WP_Term || ! tara_tax_is_music( $term->taxonomy ) ) {
			return '';
		}

		$stats      = tara_tax_stats( $term );
		$spotlight  = tara_tax_spotlight( $term, 6 );
		$cross      = tara_tax_cross_terms( $term, 8 );
		$siblings   = tara_tax_siblings( $term, 14 );
		$label      = function_exists( 'tara_music_taxonomy_label' ) ? tara_music_taxonomy_label( $term->taxonomy, 'singular' ) : 'بخش';
		$label_plu  = function_exists( 'tara_music_taxonomy_label' ) ? tara_music_taxonomy_label( $term->taxonomy, 'plural' ) : 'بخش‌ها';
		$covers     = (array) $stats['covers'];
		$link       = get_term_link( $term );
		$url        = is_wp_error( $link ) ? '' : (string) $link;
		$icon       = 'tara_genre' === $term->taxonomy ? 'icofont-ui-music' : 'icofont-heart-alt';
		$icon       = 'tara_genre' === $term->taxonomy ? 'icofont-music-alt' : 'icofont-heart-alt';

		ob_start();
		?>
		<section class="ty-taxhero ty-taxhero--<?php echo esc_attr( $term->taxonomy ); ?>" data-ty-taxhero>
			<span class="ty-taxhero__glow" aria-hidden="true"></span>
			<?php if ( ! empty( $covers ) ) : ?>
				<span class="ty-taxhero__collage" aria-hidden="true">
					<?php foreach ( array_slice( $covers, 0, 3 ) as $i => $cover ) : ?>
						<img src="<?php echo esc_url( $cover ); ?>" alt="" width="180" height="180" loading="<?php echo $i ? 'lazy' : 'eager'; ?>" decoding="async">
					<?php endforeach; ?>
				</span>
			<?php endif; ?>

			<div class="ty-taxhero__body">
				<nav class="ty-taxhero__crumbs" aria-label="مسیر صفحه">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a>
					<span aria-hidden="true">/</span>
					<span><?php echo esc_html( $label_plu ); ?></span>
					<span aria-hidden="true">/</span>
					<span aria-current="page"><?php echo esc_html( $term->name ); ?></span>
				</nav>

				<span class="ty-taxhero__eyebrow">
					<i class="<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></i>
					<?php echo esc_html( $label ); ?>
					<?php if ( ! empty( $stats['has_new'] ) ) : ?>
						<em class="ty-taxhero__new">تازه</em>
					<?php endif; ?>
				</span>

				<h1 class="ty-taxhero__title"><?php echo esc_html( $term->name ); ?></h1>

				<?php if ( '' !== trim( (string) $desc ) ) : ?>
					<div class="ty-taxhero__desc"><?php echo wp_kses_post( $desc ); ?></div>
				<?php endif; ?>

				<ul class="ty-taxhero__stats">
					<li><strong><?php echo esc_html( function_exists( 'tara_fa_number' ) ? tara_fa_number( (int) $stats['tracks'] ) : (string) $stats['tracks'] ); ?></strong><span>آهنگ</span></li>
					<li><strong><?php echo esc_html( function_exists( 'tara_fa_number' ) ? tara_fa_number( (int) $stats['artists'] ) : (string) $stats['artists'] ); ?></strong><span>خواننده</span></li>
					<li><strong><?php echo esc_html( tara_tax_short_number( (int) $stats['views'] ) ); ?></strong><span>بازدید</span></li>
					<?php if ( '' !== (string) $stats['latest'] ) : ?>
						<li><strong><?php echo esc_html( (string) $stats['latest'] ); ?></strong><span>تازه‌ترین اثر</span></li>
					<?php endif; ?>
				</ul>

				<div class="ty-taxhero__actions">
					<button type="button" class="ty-taxhero__play" data-ty-taxplay>
						<i class="icofont-play-alt-1" aria-hidden="true"></i>
						پخش میکس این <?php echo esc_html( $label ); ?>
					</button>
					<a class="ty-taxhero__ghost" href="#ty-tax-list">فهرست کامل</a>
					<button type="button" class="ty-taxhero__ghost" data-ty-taxcopy data-url="<?php echo esc_url( $url ); ?>">
						<i class="icofont-link-alt" aria-hidden="true"></i>
						کپی نشانی
					</button>
				</div>
			</div>

			<?php if ( ! empty( $siblings ) || ! empty( $cross ) ) : ?>
				<div class="ty-taxhero__chips">
					<?php if ( ! empty( $siblings ) ) : ?>
						<div class="ty-taxhero__chipgroup">
							<span class="ty-taxhero__chiplabel">دیگر <?php echo esc_html( $label_plu ); ?></span>
							<?php foreach ( $siblings as $sibling ) : ?>
								<?php
								$sibling_link = get_term_link( $sibling );
								if ( is_wp_error( $sibling_link ) ) {
									continue;
								}
								?>
								<a class="ty-taxchip" href="<?php echo esc_url( $sibling_link ); ?>">
									<?php echo esc_html( $sibling->name ); ?>
									<b><?php echo esc_html( function_exists( 'tara_fa_number' ) ? tara_fa_number( (int) $sibling->count ) : (string) $sibling->count ); ?></b>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $cross ) ) : ?>
						<div class="ty-taxhero__chipgroup">
							<span class="ty-taxhero__chiplabel"><?php echo 'tara_genre' === $term->taxonomy ? 'حال‌وهواهای همین آهنگ‌ها' : 'ژانرهای همین آهنگ‌ها'; ?></span>
							<?php foreach ( $cross as $row ) : ?>
								<a class="ty-taxchip ty-taxchip--soft" href="<?php echo esc_url( $row['url'] ); ?>">
									<?php echo esc_html( $row['term']->name ); ?>
									<b><?php echo esc_html( function_exists( 'tara_fa_number' ) ? tara_fa_number( (int) $row['count'] ) : (string) $row['count'] ); ?></b>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>

		<?php if ( ! empty( $spotlight ) ) : ?>
			<section class="ty-taxspot" aria-labelledby="ty-taxspot-title">
				<header class="ty-taxspot__head">
					<h2 id="ty-taxspot-title">خواننده‌های این <?php echo esc_html( $label ); ?></h2>
					<span>پرتکرارترین هنرمندها در همین بخش</span>
				</header>
				<ul class="ty-taxspot__list">
					<?php foreach ( $spotlight as $row ) : ?>
						<li>
							<a class="ty-taxspot__card" href="<?php echo esc_url( $row['url'] ); ?>">
								<span class="ty-taxspot__media">
									<?php if ( '' !== (string) $row['image'] ) : ?>
										<img src="<?php echo esc_url( $row['image'] ); ?>" alt="<?php echo esc_attr( $row['term']->name ); ?>" width="76" height="76" loading="lazy" decoding="async">
									<?php else : ?>
										<i class="icofont-microphone-alt" aria-hidden="true"></i>
									<?php endif; ?>
								</span>
								<span class="ty-taxspot__name"><?php echo esc_html( $row['term']->name ); ?></span>
								<span class="ty-taxspot__count"><?php echo esc_html( function_exists( 'tara_fa_number' ) ? tara_fa_number( (int) $row['count'] ) : (string) $row['count'] ); ?> آهنگ</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'tara_tax_about' ) ) {
	/**
	 * بخشِ «دربارهٔ این ترم» — عمداً جدا از سربرگ تا در انتهای صفحهٔ آرشیو
	 * (پایینِ فهرست آهنگ‌ها) چاپ شود: کاربر اول فهرست را می‌بیند و متنِ
	 * معرفی هم برای سئو در همان صفحه هست.
	 *
	 * @param WP_Term $term ترم.
	 * @return string
	 */
	function tara_tax_about( $term ) {
		if ( ! $term instanceof WP_Term || ! tara_tax_is_music( $term->taxonomy ) ) {
			return '';
		}

		$stats     = tara_tax_stats( $term );
		$spotlight = tara_tax_spotlight( $term, 6 );
		$cross     = tara_tax_cross_terms( $term, 8 );
		$label     = function_exists( 'tara_music_taxonomy_label' ) ? tara_music_taxonomy_label( $term->taxonomy, 'singular' ) : 'بخش';
		$intro     = tara_tax_intro( $term, $stats, $spotlight, $cross );

		if ( empty( $intro ) ) {
			return '';
		}

		ob_start();
		?>
		<section class="ty-taxabout" aria-labelledby="ty-taxabout-title">
			<h2 id="ty-taxabout-title">دربارهٔ <?php echo esc_html( $label ); ?> «<?php echo esc_html( $term->name ); ?>»</h2>
			<?php foreach ( $intro as $para ) : ?>
				<p><?php echo esc_html( (string) $para ); ?></p>
			<?php endforeach; ?>
		</section>
		<?php

		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'tara_tax_schema' ) ) {
	/**
	 * داده‌ساختاریِ صفحهٔ ترم: CollectionPage + MusicPlaylist.
	 *
	 * چرا MusicPlaylist؟ چون همین صفحه واقعاً یک فهرستِ قابلِ‌پخش از آهنگ‌ها
	 * است و همان چیزی را نشان می‌دهد که کاربر می‌بیند. BreadcrumbList جداگانه
	 * در `inc/ab-seo.php` چاپ می‌شود، پس این‌جا تکرار نمی‌شود.
	 *
	 * @param WP_Term $term   ترم.
	 * @param array   $tracks آهنگ‌های صفحه (خروجی tara_track_playlist).
	 * @return void
	 */
	function tara_tax_schema( $term, $tracks ) {
		if ( ! $term instanceof WP_Term || ! tara_tax_is_music( $term->taxonomy ) ) {
			return;
		}

		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			return;
		}

		$stats = tara_tax_stats( $term );
		$items = array();
		$i     = 0;

		foreach ( array_slice( (array) $tracks, 0, 30 ) as $track ) {
			$i++;
			$one = array(
				'@type'    => 'ListItem',
				'position' => $i,
				'item'     => array(
					'@type'  => 'MusicRecording',
					'name'   => (string) $track['title'],
					'url'    => (string) $track['link'],
					'byArtist' => array(
						'@type' => 'MusicGroup',
						'name'  => (string) $track['artist'],
					),
				),
			);

			$duration = function_exists( 'tara_radio_duration' ) ? (int) tara_radio_duration( (int) $track['id'] ) : 0;
			if ( $duration > 0 ) {
				$one['item']['duration'] = 'PT' . (int) floor( $duration / 60 ) . 'M' . ( $duration % 60 ) . 'S';
			}

			$items[] = $one;
		}

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'CollectionPage',
			'name'        => $term->name . ' — ' . get_bloginfo( 'name' ),
			'url'         => (string) $link,
			'inLanguage'  => 'fa-IR',
			'description' => wp_strip_all_tags( (string) $term->description ),
			'isPartOf'    => array(
				'@type' => 'WebSite',
				'name'  => get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
			),
			'mainEntity'  => array(
				'@type'      => 'MusicPlaylist',
				'name'       => 'میکس ' . $term->name,
				'numTracks'  => (int) $stats['tracks'],
				'url'        => (string) $link,
				'track'      => $items,
			),
		);

		echo '<script type="application/ld+json">'
			. wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			. '</script>' . "\n";
	}
}

if ( ! function_exists( 'tara_tax_schema_footer' ) ) {
	/**
	 * چاپِ داده‌ساختاری در پانوشت، فقط در صفحهٔ ژانر/حال‌وهوا.
	 *
	 * @return void
	 */
	function tara_tax_schema_footer() {
		if ( ! function_exists( 'is_tax' ) || ! ( is_tax( 'tara_genre' ) || is_tax( 'tara_mood' ) ) ) {
			return;
		}
		$term = get_queried_object();
		if ( ! $term instanceof WP_Term ) {
			return;
		}
		$tracks = function_exists( 'tara_track_playlist' ) ? tara_track_playlist( $GLOBALS['wp_query']->posts ) : array();
		tara_tax_schema( $term, $tracks );
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_footer', 'tara_tax_schema_footer', 6 );
}
