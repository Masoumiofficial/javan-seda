<?php
/**
 * inc/tara-artist-profile.php — اجزای مشترک پروفایل هنرمند (دور P1، ۳.۱۷.۰)
 * =========================================================================
 * این فایل فقط «خواندن» است. هدف: یک منبعِ واحد برای اجزایی که تا امروز
 * چند نسخهٔ نزدیک‌به‌هم در `taxonomy-artist.php` و ویجت‌ها داشتند.
 *
 * اصولی که عمداً رعایت شده:
 *   • هیچ اطلاعاتی ساخته نمی‌شود: نه شبکهٔ اجتماعی حدسی، نه آمار ساختگی،
 *     نه «عضویت گروه» از روی حدس. هر بخش فقط از دادهٔ واقعیِ ثبت‌شده می‌آید.
 *   • هیچ اسلاگی عوض نمی‌شود و URL تکراری ساخته نمی‌شود؛ لینکِ هنرمند
 *     همیشه از `tara_artist_term_link()` می‌آید (همان لینکِ رسمی).
 *   • بیوگرافی دوبار چاپ نمی‌شود: هر جزء فقط وقتی خروجی می‌دهد که داده دارد،
 *     و در قالب اصلی یک‌بار صدا زده می‌شود.
 *   • کوئری‌ها کوچک و شمارش‌شده‌اند (`no_found_rows`، `fields=ids`) و خروجی
 *     هر تابع در یک درخواست کش می‌شود.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_artist_track_type' ) ) {
	/**
	 * نوعِ اثر: `audio` / `video` / `album` / `other`.
	 *
	 * از همان نگاشتِ رسمیِ `tara_artist_type_map` استفاده می‌کند که صفحهٔ
	 * هنرمند و پلی‌لیست‌ها به کار می‌برند؛ مقدار ناشناخته/خالی `other` است،
	 * نه «آهنگ». هیچ حدسی زده نمی‌شود.
	 *
	 * @param int $post_id شناسه.
	 * @return string
	 */
	function tara_artist_track_type( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return 'other';
		}
		$raw = function_exists( 'tara_music_field' ) ? (string) tara_music_field( 'musics_type', $post_id ) : (string) get_post_meta( $post_id, 'musics_type', true );
		$raw = function_exists( 'tara_artist_normalize' ) ? tara_artist_normalize( $raw ) : $raw;
		$key = strtolower( trim( $raw ) );

		$map = (array) apply_filters(
			'tara_artist_type_map',
			array(
				'audio'      => 'audio',
				'mp3'        => 'audio',
				'ahang'      => 'audio',
				'آهنگ'       => 'audio',
				'آهنگ جدید'  => 'audio',
				'song'       => 'audio',
				'mp4'        => 'video',
				'video'      => 'video',
				'musicvideo' => 'video',
				'music-video' => 'video',
				'موزیک ویدیو' => 'video',
				'موزیک‌ویدیو' => 'video',
				'کلیپ'       => 'video',
				'album'      => 'album',
				'آلبوم'      => 'album',
			)
		);

		/* کلیدهای لاتین باید با حروف کوچک سنجیده شوند؛ کلیدهای فارسی همان‌اند. */
		foreach ( $map as $needle => $bucket ) {
			$n = function_exists( 'tara_artist_normalize' ) ? tara_artist_normalize( $needle ) : $needle;
			if ( strtolower( trim( (string) $n ) ) === $key ) {
				return (string) $bucket;
			}
		}

		return 'other';
	}
}

if ( ! function_exists( 'tara_artist_ids_for_term' ) ) {
	/**
	 * شناسه‌های آثار یک ترم، با فیلترهای کوچک و قابل‌کش.
	 *
	 * @param WP_Term $term  ترم.
	 * @param array   $args  limit, offset, type (audio|video|album|other|''), exclude.
	 * @return int[]
	 */
	function tara_artist_ids_for_term( $term, $args = array() ) {
		if ( ! $term instanceof WP_Term ) {
			return array();
		}
		$args = array_merge(
			array(
				'limit'   => 12,
				'offset'  => 0,
				'type'    => '',
				'exclude' => array(),
			),
			(array) $args
		);

		$query_args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, min( 100, (int) $args['limit'] ) ),
			'offset'              => max( 0, (int) $args['offset'] ),
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'has_password'        => false,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => $term->taxonomy,
					'field'    => 'term_id',
					'terms'    => (int) $term->term_id,
				),
			),
		);

		if ( ! empty( $args['exclude'] ) ) {
			$query_args['post__not_in'] = array_values( array_unique( array_map( 'absint', (array) $args['exclude'] ) ) );
		}

		$ids = array_values( array_map( 'absint', (array) get_posts( $query_args ) ) );

		if ( '' === (string) $args['type'] ) {
			return $ids;
		}

		$out = array();
		foreach ( $ids as $id ) {
			if ( tara_artist_track_type( $id ) === (string) $args['type'] ) {
				$out[] = $id;
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'tara_artist_latest_release' ) ) {
	/**
	 * آخرین انتشارِ واقعیِ هنرمند.
	 *
	 * منبعِ تاریخ: `tara_release_meta_date()` (متای معتبر) و در نبودش تاریخ
	 * نوشتنِ مطلب — با برچسبِ صریح، دقیقاً مثل بقیهٔ سایت. مقدارِ ساختگی
	 * هیچ‌وقت ساخته نمی‌شود.
	 *
	 * @param WP_Term $term ترم.
	 * @return array{id:int,date:string,label:string}|null
	 */
	function tara_artist_latest_release( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return null;
		}
		$ids = tara_artist_ids_for_term( $term, array( 'limit' => 8 ) );
		if ( ! $ids ) {
			return null;
		}
		$best = null;
		foreach ( $ids as $id ) {
			$real  = function_exists( 'tara_release_meta_date' ) ? (string) tara_release_meta_date( $id ) : '';
			$date  = '' !== $real ? $real : (string) get_post_time( 'Y-m-d', false, $id );
			$label = '' !== $real ? 'تاریخ انتشار' : 'انتشار در سایت';
			if ( '' !== $date && ( null === $best || $date > $best['date'] ) ) {
				$best = array( 'id' => (int) $id, 'date' => $date, 'label' => $label );
			}
		}
		return $best;
	}
}

if ( ! function_exists( 'tara_artist_social_links' ) ) {
	/**
	 * شبکه‌های اجتماعیِ ثبت‌شدهٔ هنرمند — بدون حدس.
	 *
	 * فقط کلیدهای رسمی (`tara_artist_social_keys()`) و فقط مقدارهایی که با
	 * `http(s)://` شروع می‌شوند. هیچ آدرسی از نام/شناسه ساخته نمی‌شود.
	 *
	 * @param WP_Term $term ترم.
	 * @return array<string,array{label:string,icon:string,url:string}>
	 */
	function tara_artist_social_links( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return array();
		}
		$out  = array();
		$keys = function_exists( 'tara_artist_social_keys' ) ? tara_artist_social_keys() : array();
		foreach ( $keys as $key => $meta ) {
			$url = function_exists( 'tara_artist_field' ) ? trim( (string) tara_artist_field( $term, $key ) ) : '';
			if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
				continue;
			}
			$out[ $key ] = array(
				'label' => isset( $meta['label'] ) ? (string) $meta['label'] : $key,
				'icon'  => isset( $meta['icon'] ) ? (string) $meta['icon'] : 'icofont-link',
				'url'   => $url,
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'tara_artist_links_html' ) ) {
	/**
	 * ردیف لینک‌های هنرمند (شبکه‌ها + بیوگرافی کاملِ منتشرشده).
	 *
	 * @param WP_Term $term ترم.
	 * @param array   $args class، show_bio.
	 * @return string
	 */
	function tara_artist_links_html( $term, $args = array() ) {
		$args  = array_merge( array( 'class' => '', 'show_bio' => true ), (array) $args );
		$links = tara_artist_social_links( $term );
		$bio   = ( $args['show_bio'] && function_exists( 'tara_artist_bio_link' ) ) ? (string) tara_artist_bio_link( $term ) : '';

		if ( ! $links && '' === $bio ) {
			return '';
		}

		ob_start();
		?>
		<div class="tara-artist-links<?php echo '' !== (string) $args['class'] ? ' ' . esc_attr( (string) $args['class'] ) : ''; ?>">
			<?php foreach ( $links as $link ) : ?>
				<a class="tara-artist-links__item" href="<?php echo esc_url( $link['url'] ); ?>"
					target="_blank" rel="noopener nofollow me">
					<i class="<?php echo esc_attr( $link['icon'] ); ?>" aria-hidden="true"></i>
					<span><?php echo esc_html( $link['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
			<?php if ( '' !== $bio ) : ?>
				<a class="tara-artist-links__item tara-artist-links__item--bio" href="<?php echo esc_url( $bio ); ?>">
					<i class="icofont-book-alt" aria-hidden="true"></i>
					<span>بیوگرافی کامل</span>
				</a>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'tara_artist_collaborators' ) ) {
	/**
	 * «همکاری‌ها»: هنرمندانی که در نوشته‌های همین هنرمند **واقعاً** کنارش
	 * ثبت شده‌اند (تاکسونومی مشترک). این ادعای «عضویت گروه» نیست؛ عبارتِ
	 * نمایشی هم همین را می‌گوید.
	 *
	 * @param WP_Term $term  ترم.
	 * @param int     $limit سقف.
	 * @return WP_Term[]
	 */
	function tara_artist_collaborators( $term, $limit = 8 ) {
		if ( ! $term instanceof WP_Term ) {
			return array();
		}
		$ids = tara_artist_ids_for_term( $term, array( 'limit' => 20 ) );
		if ( ! $ids ) {
			return array();
		}
		$counts = array();
		foreach ( $ids as $id ) {
			$terms = get_the_terms( $id, $term->taxonomy );
			if ( ! is_array( $terms ) ) {
				continue;
			}
			foreach ( $terms as $t ) {
				if ( ! $t instanceof WP_Term || (int) $t->term_id === (int) $term->term_id ) {
					continue;
				}
				$counts[ (int) $t->term_id ] = isset( $counts[ (int) $t->term_id ] ) ? $counts[ (int) $t->term_id ] + 1 : 1;
			}
		}
		if ( ! $counts ) {
			return array();
		}
		arsort( $counts );
		$counts = array_slice( $counts, 0, max( 1, (int) $limit ), true );
		$terms  = get_terms(
			array(
				'taxonomy'   => $term->taxonomy,
				'include'    => array_keys( $counts ),
				'hide_empty' => true,
				'orderby'    => 'include',
			)
		);
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}
		return $terms;
	}
}

if ( ! function_exists( 'tara_artist_discography_groups' ) ) {
	/**
	 * «کامل‌ترین فهرست آثار» را به گروه‌های واقعی می‌شکند: آهنگ، آلبوم،
	 * موزیک‌ویدیو، سایر — هر گروه فقط اگر عضو داشته باشد.
	 *
	 * @param WP_Term $term  ترم.
	 * @param int     $limit سقف هر گروه.
	 * @return array<string,int[]>
	 */
	function tara_artist_discography_groups( $term, $limit = 24 ) {
		$groups = array();
		foreach ( array( 'audio', 'album', 'video', 'other' ) as $bucket ) {
			$ids = tara_artist_ids_for_term(
				$term,
				array(
					'limit' => $limit,
					'type'  => $bucket,
				)
			);
			if ( $ids ) {
				$groups[ $bucket ] = $ids;
			}
		}
		return $groups;
	}
}
