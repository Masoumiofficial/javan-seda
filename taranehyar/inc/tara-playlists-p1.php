<?php
/**
 * inc/tara-playlists-p1.php — پلی‌لیست‌های منتخب: لایهٔ نمایشِ ۳.۱۷.۰
 * =====================================================================
 * سامانهٔ پلی‌لیست از قبل وجود داشت (`inc/tara-playlists.php`): پست‌تایپ
 * `tara_playlist`، متای `_tara_playlist_tracks` و برگهٔ «پلی‌لیست‌های ترانه یار».
 * پس **سامانهٔ دوم ساخته نشد**؛ فقط لایهٔ نمایشی که کم بود اضافه شد:
 *
 *   • هر پلی‌لیست فقط وقتی لینک/کارت می‌گیرد که منتشر شده و **حداقل یک آهنگِ
 *     قابل‌پخش** داشته باشد → صفحهٔ خالی و محتوای نازک ساخته نمی‌شود.
 *   • فهرست آهنگ‌ها با لینک هنرمند (خزش‌پذیر، نه AJAX) و ترتیبِ واقعی.
 *   • «پلی‌لیست‌های مرتبط» برای هر آهنگ، فقط از روی عضویتِ واقعی در متا.
 *   • توصیف و شمارشِ واقعی؛ حالت خالیِ روشن.
 *
 * هیچ جدول/متای تازه‌ای ساخته نمی‌شود و هیچ پلی‌لیستی خودکار ساخته نمی‌شود.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_playlist_is_public' ) ) {
	/**
	 * آیا این پلی‌لیست اجازهٔ نمایش و لینک دارد؟
	 *
	 * شرط‌ها: نوع درست + منتشرشده + بدون رمز + حداقل یک آهنگِ قابل‌پخش.
	 *
	 * @param int|WP_Post $playlist پلی‌لیست.
	 * @return bool
	 */
	function tara_playlist_is_public( $playlist ) {
		$post = $playlist instanceof WP_Post ? $playlist : get_post( (int) $playlist );
		if ( ! $post || 'tara_playlist' !== $post->post_type || 'publish' !== $post->post_status || ! empty( $post->post_password ) ) {
			return false;
		}
		if ( ! function_exists( 'tara_playlist_audio_ids' ) ) {
			return false;
		}
		$ids = tara_playlist_audio_ids( get_post_meta( $post->ID, '_tara_playlist_tracks', true ) );
		return is_array( $ids ) && ! empty( $ids );
	}
}

if ( ! function_exists( 'tara_playlist_ids' ) ) {
	/**
	 * شناسهٔ پلی‌لیست‌های قابل‌نمایش، تازه‌ترین اول.
	 *
	 * @param array $args limit، exclude.
	 * @return int[]
	 */
	function tara_playlist_ids( $args = array() ) {
		$args = array_merge( array( 'limit' => 12, 'exclude' => array() ), (array) $args );
		$q    = new WP_Query(
			array(
				'post_type'           => 'tara_playlist',
				'post_status'         => 'publish',
				'posts_per_page'      => max( 1, min( 50, (int) $args['limit'] ) ),
				'fields'              => 'ids',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'has_password'        => false,
				'post__not_in'        => array_values( array_unique( array_map( 'absint', (array) $args['exclude'] ) ) ),
				'orderby'             => 'date',
				'order'               => 'DESC',
			)
		);
		$out = array();
		foreach ( (array) $q->posts as $id ) {
			if ( tara_playlist_is_public( $id ) ) {
				$out[] = (int) $id;
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'tara_playlist_cover' ) ) {
	/**
	 * پوشش پلی‌لیست: تصویر شاخص؛ اگر نبود، کاورِ اولین آهنگِ قابل‌پخش؛
	 * اگر آن هم نبود، رشتهٔ خالی (و قالب «هیچ تصویری» نشان نمی‌دهد).
	 *
	 * @param int    $playlist_id شناسه.
	 * @param string $size        اندازهٔ تصویر.
	 * @return array{url:string,alt:string,source:string}
	 */
	function tara_playlist_cover( $playlist_id, $size = 'medium' ) {
		$playlist_id = (int) $playlist_id;
		$post        = get_post( $playlist_id );
		if ( ! $post ) {
			return array( 'url' => '', 'alt' => '', 'source' => '' );
		}
		if ( function_exists( 'has_post_thumbnail' ) && has_post_thumbnail( $playlist_id ) ) {
			$url = get_the_post_thumbnail_url( $playlist_id, $size );
			if ( $url ) {
				return array( 'url' => (string) $url, 'alt' => (string) $post->post_title, 'source' => 'thumbnail' );
			}
		}
		$ids = function_exists( 'tara_playlist_audio_ids' ) ? tara_playlist_audio_ids( get_post_meta( $playlist_id, '_tara_playlist_tracks', true ) ) : array();
		if ( $ids && function_exists( 'tara_track_cover' ) ) {
			$url = (string) tara_track_cover( $ids[0], 'thumb3' );
			if ( '' !== $url ) {
				return array( 'url' => $url, 'alt' => (string) $post->post_title, 'source' => 'first-track' );
			}
		}
		return array( 'url' => '', 'alt' => '', 'source' => '' );
	}
}

if ( ! function_exists( 'tara_playlist_tracks' ) ) {
	/**
	 * آهنگ‌های پلی‌لیست با لینکِ واقعیِ هنرمند.
	 *
	 * @param int $playlist_id شناسه.
	 * @param int $limit       سقف.
	 * @return array<int,array{id:int,title:string,artist:string,artist_url:string,cover:string,permalink:string}>
	 */
	function tara_playlist_tracks( $playlist_id, $limit = 200 ) {
		$raw = get_post_meta( (int) $playlist_id, '_tara_playlist_tracks', true );
		$ids = function_exists( 'tara_playlist_audio_ids' ) ? tara_playlist_audio_ids( $raw ) : array();
		if ( ! $ids ) {
			return array();
		}
		$out = array();
		foreach ( array_slice( $ids, 0, max( 1, (int) $limit ) ) as $id ) {
			$id = (int) $id;
			$artist     = function_exists( 'tara_track_artist' ) ? (string) tara_track_artist( $id ) : (string) get_post_meta( $id, 'artist', true );
			$artist_url = '';
			$tax        = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
			$terms      = get_the_terms( $id, $tax );
			if ( is_array( $terms ) && ! empty( $terms[0] ) && function_exists( 'tara_artist_term_link' ) ) {
				$artist_url = (string) tara_artist_term_link( $terms[0] );
			}
			$out[] = array(
				'id'         => $id,
				'title'      => function_exists( 'tara_track_title' ) ? (string) tara_track_title( $id ) : (string) get_the_title( $id ),
				'artist'     => $artist,
				'artist_url' => $artist_url,
				'cover'      => function_exists( 'tara_track_cover' ) ? (string) tara_track_cover( $id, 'thumb3' ) : '',
				'permalink'  => (string) get_permalink( $id ),
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'tara_playlist_description' ) ) {
	/**
	 * توضیحِ واقعیِ پلی‌لیست (چکیدهٔ متن خودش). هیچ متنِ ساختگی ساخته نمی‌شود.
	 *
	 * @param int $playlist_id شناسه.
	 * @param int $words       تعداد کلمه.
	 * @return string
	 */
	function tara_playlist_description( $playlist_id, $words = 24 ) {
		$post = get_post( (int) $playlist_id );
		if ( ! $post ) {
			return '';
		}
		$text = trim( wp_strip_all_tags( (string) $post->post_content ) );
		if ( '' === $text ) {
			return '';
		}
		return wp_trim_words( $text, max( 4, (int) $words ), '…' );
	}
}

if ( ! function_exists( 'tara_playlist_tracks_html' ) ) {
	/**
	 * فهرست آهنگ‌های پلی‌لیست با لینک هنرمند.
	 *
	 * از همان ردیف استاندارد `tara_track_row()` استفاده می‌شود که کارت‌ها و
	 * صفحهٔ هنرمند هم دارند؛ پس ظاهر/پخش یکدست است و دوباره‌کاری نمی‌شود.
	 *
	 * @param int   $playlist_id شناسه.
	 * @param array $args        limit، show_artist.
	 * @return string
	 */
	function tara_playlist_tracks_html( $playlist_id, $args = array() ) {
		$args   = array_merge( array( 'limit' => 200, 'show_artist' => true ), (array) $args );
		$tracks = tara_playlist_tracks( $playlist_id, (int) $args['limit'] );

		if ( ! $tracks ) {
			return '<p class="ty-playlists__empty">آهنگِ قابل‌پخشی در این پلی‌لیست نیست؛ به‌زودی تکمیل می‌شود.</p>';
		}

		$queue = function_exists( 'tara_track_playlist' ) ? tara_track_playlist( wp_list_pluck( $tracks, 'id' ) ) : array();
		$index = array();
		foreach ( $queue as $i => $tr ) {
			$index[ (string) $tr['id'] ] = $i;
		}

		ob_start();
		?>
		<ul class="ty-tracklist"<?php echo function_exists( 'tara_queue_attrs' ) ? tara_queue_attrs( $queue, (string) get_the_title( $playlist_id ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php foreach ( $tracks as $track ) : ?>
				<li class="ty-playlists__track">
					<?php
					echo function_exists( 'tara_track_row' )
						? tara_track_row( $track['id'], array( 'index' => isset( $index[ (string) $track['id'] ] ) ? $index[ (string) $track['id'] ] : -1 ) ) // phpcs:ignore WordPress.Security.EscapeOutput
						: '<a href="' . esc_url( $track['permalink'] ) . '">' . esc_html( $track['title'] ) . '</a>';
					?>
					<?php if ( $args['show_artist'] && '' !== (string) $track['artist'] ) : ?>
						<?php if ( '' !== (string) $track['artist_url'] ) : ?>
							<a class="ty-playlists__track-artist" href="<?php echo esc_url( $track['artist_url'] ); ?>"><?php echo esc_html( $track['artist'] ); ?></a>
						<?php else : ?>
							<span class="ty-playlists__track-artist"><?php echo esc_html( $track['artist'] ); ?></span>
						<?php endif; ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'tara_playlist_related_to_track' ) ) {
	/**
	 * پلی‌لیست‌هایی که این اثر **واقعاً** در آن‌ها هست.
	 *
	 * کوئری با LIKE روی متای سریالی انجام می‌شود و سپس با `in_array` روی
	 * شناسه‌های عددی تأیید می‌شود؛ چون LIKE می‌تواند ۱۲ را در ۱۱۲ ببیند.
	 *
	 * @param int $post_id شناسهٔ اثر.
	 * @param int $limit   سقف.
	 * @return int[]
	 */
	function tara_playlist_related_to_track( $post_id, $limit = 3 ) {
		$post_id = (int) $post_id;
		$limit   = max( 1, min( 6, (int) $limit ) );
		if ( ! $post_id || ! function_exists( 'tara_playlist_audio_ids' ) ) {
			return array();
		}
		$q = new WP_Query(
			array(
				'post_type'           => 'tara_playlist',
				'post_status'         => 'publish',
				'posts_per_page'      => $limit * 4,
				'fields'              => 'ids',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'has_password'        => false,
				'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_tara_playlist_tracks',
						'value'   => 'i:' . $post_id . ';',
						'compare' => 'LIKE',
					),
				),
			)
		);
		$out = array();
		foreach ( (array) $q->posts as $id ) {
			$tracks = array_map( 'absint', tara_playlist_audio_ids( get_post_meta( $id, '_tara_playlist_tracks', true ) ) );
			if ( in_array( $post_id, $tracks, true ) ) {
				$out[] = (int) $id;
			}
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'tara_playlist_card_html' ) ) {
	/**
	 * کارتِ یک پلی‌لیست با لینکِ برگه (خزش‌پذیر، بدون AJAX) و توضیحِ واقعی.
	 *
	 * @param int    $playlist_id شناسه.
	 * @param string $page_url    آدرس برگهٔ پلی‌لیست‌ها.
	 * @return string
	 */
	function tara_playlist_card_html( $playlist_id, $page_url ) {
		$playlist_id = (int) $playlist_id;
		$post        = get_post( $playlist_id );
		if ( ! $post || ! tara_playlist_is_public( $playlist_id ) ) {
			return '';
		}
		$cover = function_exists( 'tara_playlist_cover' ) ? tara_playlist_cover( $playlist_id, 'medium' ) : array( 'url' => '', 'alt' => '', 'source' => '' );
		$count = count( (array) tara_playlist_tracks( $playlist_id, 200 ) );
		$url   = add_query_arg( 'list', $playlist_id, $page_url );

		ob_start();
		?>
		<a class="ty-playlists__card" href="<?php echo esc_url( $url ); ?>">
			<?php if ( '' !== $cover['url'] ) : ?>
				<img src="<?php echo esc_url( $cover['url'] ); ?>" width="300" height="300" alt="<?php echo esc_attr( $cover['alt'] ); ?>" loading="lazy" decoding="async">
			<?php else : ?>
				<span class="ty-playlists__placeholder" aria-hidden="true">♫</span>
			<?php endif; ?>
			<strong><?php echo esc_html( $post->post_title ); ?></strong>
			<?php $desc = tara_playlist_description( $playlist_id, 16 ); ?>
			<?php if ( '' !== $desc ) : ?>
				<span class="ty-playlists__desc"><?php echo esc_html( $desc ); ?></span>
			<?php endif; ?>
			<span class="ty-playlists__count"><?php echo esc_html( sprintf( '%s آهنگ', function_exists( 'tara_number_fa' ) ? tara_number_fa( $count ) : (string) $count ) ); ?></span>
		</a>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'tara_playlists_for_track_html' ) ) {
	/**
	 * بلوک «این آهنگ در این پلی‌لیست‌ها هست» برای صفحهٔ آهنگ.
	 * فقط اگر رابطهٔ واقعی وجود داشت؛ در غیر این صورت رشتهٔ خالی.
	 *
	 * @param int $post_id شناسهٔ اثر.
	 * @return string
	 */
	function tara_playlists_for_track_html( $post_id ) {
		$ids = tara_playlist_related_to_track( $post_id, 3 );
		if ( ! $ids ) {
			return '';
		}
		$pages = function_exists( 'tara_playlists_page_url' ) ? tara_playlists_page_url() : '';
		if ( '' === $pages ) {
			return '';
		}
		ob_start();
		?>
		<div class="tara-track-playlists" data-tara-related="playlists">
			<h3 class="tarlanweb_side_titles">در این پلی‌لیست‌ها</h3>
			<ul>
				<?php foreach ( $ids as $id ) : ?>
					<li>
						<a href="<?php echo esc_url( add_query_arg( 'list', (int) $id, $pages ) ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'tara_playlists_page_url' ) ) {
	/**
	 * آدرس برگه‌ای که قالبش «پلی‌لیست‌های ترانه یار» است (اگر منتشر شده باشد).
	 *
	 * @return string
	 */
	function tara_playlists_page_url() {
		$pages = get_pages(
			array(
				'meta_key'    => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => 'page-playlists.php', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'      => 1,
				'post_status' => 'publish',
			)
		);
		if ( empty( $pages ) || ! is_array( $pages ) || ! isset( $pages[0] ) ) {
			return '';
		}
		$url = get_permalink( $pages[0] );
		return $url ? (string) $url : '';
	}
}
