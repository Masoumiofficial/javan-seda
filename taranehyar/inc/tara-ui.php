<?php
/**
 * عناصر مشترکِ رابط کاربری — آهنگ، هنرمند، چیپ‌ها و پلیر «رادیو»
 * ==========================================================================
 * چرا این فایل؟ قبلاً مارکاپِ آهنگ چهار جای مختلف (صفحهٔ اصلی، صفحهٔ هنرمند،
 * جست‌وجو، ابزارک‌ها) جداگانه نوشته می‌شد و هر جای دیگری شکل دیگری داشت. حالا
 * یک منبع دارد و همه جا یکسان است.
 *
 * اصولی که رعایت می‌شود:
 *   • هیچ چیز در دیتابیس نوشته نمی‌شود؛ فقط خواندن.
 *   • هر تابع با function_exists محافظت شده تا قالب فرزند بتواند جایگزینش کند.
 *   • همهٔ خروجی‌ها escape می‌شوند؛ کلاس‌ها با پیشوند `ty-` تا با CSS قدیمی
 *     قالب (ab-*) تداخل نداشته باشند.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * ۱) خواندن داده‌های آهنگ — همیشه با fallback به متای ساده
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_music_field' ) ) {
	/**
	 * یک فیلد موسیقی را می‌خواند: ACF → متای نوشته → رشتهٔ خالی.
	 * (ACF ممکن است نصب نباشد؛ این تابع هیچ‌وقت خطای کشنده نمی‌دهد.)
	 *
	 * @param string   $key     نام فیلد.
	 * @param int|null $post_id شناسهٔ نوشته.
	 * @return string
	 */
	function tara_music_field( $key, $post_id = null ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();

		if ( function_exists( 'get_field' ) ) {
			$value = get_field( $key, $post_id );
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				return trim( (string) $value );
			}
		}

		$value = get_post_meta( $post_id, $key, true );

		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}

if ( ! function_exists( 'tara_track_src' ) ) {
	/**
	 * آدرس فایلِ صوتی یک آهنگ: ۳۲۰ ترجیح دارد، بعد ۱۲۸.
	 *
	 * @param int $post_id شناسهٔ نوشته.
	 * @return string
	 */
	function tara_track_src( $post_id = 0 ) {
		$post_id = (int) $post_id;

		foreach ( array( 'music320', 'music128', 'music' ) as $key ) {
			$src = tara_music_field( $key, $post_id );
			if ( '' !== $src ) {
				return $src;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'tara_track_title' ) ) {
	/**
	 * نام آهنگ — «song» و اگر نبود، عنوان نوشته.
	 *
	 * @param int $post_id شناسهٔ نوشته.
	 * @return string
	 */
	function tara_track_title( $post_id = 0 ) {
		$post_id = (int) $post_id;
		$song    = tara_music_field( 'song', $post_id );

		return '' !== $song ? $song : (string) get_the_title( $post_id );
	}
}

if ( ! function_exists( 'tara_track_artist' ) ) {
	/**
	 * نام هنرمند — فیلد «artist» و اگر نبود، اولین ترمِ تاکسونومی هنرمند.
	 *
	 * @param int $post_id شناسهٔ نوشته.
	 * @return string
	 */
	function tara_track_artist( $post_id = 0 ) {
		$post_id = (int) $post_id;
		$artist  = tara_music_field( 'artist', $post_id );

		if ( '' === $artist && function_exists( 'get_the_terms' ) && function_exists( 'tara_artist_tax' ) ) {
			$terms = get_the_terms( $post_id, tara_artist_tax() );
			if ( is_array( $terms ) && isset( $terms[0] ) ) {
				$artist = $terms[0]->name;
			}
		}

		return $artist;
	}
}

if ( ! function_exists( 'tara_track_cover' ) ) {
	/**
	 * تصویر کاور (اندازهٔ ۱۹۰×۱۹۰). اگر نبود، تصویر پیش‌فرضِ قالب.
	 *
	 * @param int    $post_id شناسهٔ نوشته.
	 * @param string $size    اندازهٔ تصویر.
	 * @return string
	 */
	function tara_track_cover( $post_id = 0, $size = 'thumb2' ) {
		$post_id = (int) $post_id;

		if ( $post_id && has_post_thumbnail( $post_id ) ) {
			$src = get_the_post_thumbnail_url( $post_id, $size );
			if ( $src ) {
				return $src;
			}
		}

		return get_template_directory_uri() . '/images/nomusics.jpg';
	}
}

if ( ! function_exists( 'tara_track_playlist' ) ) {
	/**
	 * ساخت فهرستِ قابل‌پخش برای یک مجموعه نوشته. فقط آهنگ‌های دارای فایل صوتی.
	 *
	 * @param WP_Post[]|int[] $posts فهرست نوشته‌ها یا شناسه‌ها.
	 * @return array
	 */
	function tara_track_playlist( $posts ) {
		$tracks = array();

		foreach ( (array) $posts as $item ) {
			$post_id = is_object( $item ) ? (int) $item->ID : (int) $item;
			if ( ! $post_id ) {
				continue;
			}

			$src = tara_track_src( $post_id );
			if ( '' === $src ) {
				continue;
			}

			$tracks[] = array(
				'id'     => $post_id,
				'title'  => tara_track_title( $post_id ),
				'artist' => tara_track_artist( $post_id ),
				'src'    => $src,
				'cover'  => tara_track_cover( $post_id, 'thumb2' ),
				'link'   => get_permalink( $post_id ),
			);
		}

		return $tracks;
	}
}

/* -------------------------------------------------------------------------
 * ۲) ردیفِ آهنگ (فهرست‌ها)
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_track_row' ) ) {
	/**
	 * یک ردیف آهنگ با دکمهٔ پخش. همان دکمه، پلیرِ صفحه را با ایندکس صدا می‌زند.
	 *
	 * @param int   $post_id شناسهٔ نوشته.
	 * @param array $args    گزینه‌ها: index، show_cover، show_date، badge، current.
	 * @return string
	 */
	function tara_track_row( $post_id = 0, $args = array() ) {
		$post_id = (int) $post_id;
		$args    = wp_parse_args(
			$args,
			array(
				'index'      => -1,
				'show_cover' => true,
				'show_date'  => false,
				'badge'      => '',
				'current'    => false,
			)
		);

		$src    = tara_track_src( $post_id );
		$title  = tara_track_title( $post_id );
		$artist = tara_track_artist( $post_id );
		$cover  = tara_track_cover( $post_id, 'thumb3' );
		$link   = get_permalink( $post_id );

		$classes = 'ty-track';
		if ( $args['current'] ) {
			$classes .= ' is-current';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" data-tara-row="<?php echo esc_attr( (int) $args['index'] ); ?>">
			<?php if ( '' !== $src ) : ?>
				<button type="button" class="ty-track__play" data-tara-play="<?php echo esc_attr( (int) $args['index'] ); ?>" aria-label="پخش <?php echo esc_attr( $title ); ?>">
					<span class="ty-icon-play" aria-hidden="true"></span>
					<span class="ty-icon-pause" aria-hidden="true"></span>
				</button>
			<?php else : ?>
				<span class="ty-track__play ty-track__play--none" aria-hidden="true"><span class="ty-icon-note"></span></span>
			<?php endif; ?>

			<?php if ( $args['show_cover'] ) : ?>
				<a class="ty-track__cover" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true">
					<img src="<?php echo esc_url( $cover ); ?>" width="48" height="48" alt="" loading="lazy">
				</a>
			<?php endif; ?>

			<div class="ty-track__body">
				<a class="ty-track__title" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a>
				<span class="ty-track__artist"><?php echo esc_html( $artist ); ?></span>
			</div>

			<?php if ( '' !== (string) $args['badge'] ) : ?>
				<span class="ty-track__badge"><?php echo esc_html( $args['badge'] ); ?></span>
			<?php endif; ?>

			<?php if ( $args['show_date'] ) : ?>
				<span class="ty-track__date"><?php echo esc_html( get_the_date( '', $post_id ) ); ?></span>
			<?php endif; ?>
		</div>
		<?php

		return trim( (string) ob_get_clean() );
	}
}

/* -------------------------------------------------------------------------
 * ۳) چیپ‌های تاکسونومی (ژانر، حال‌وهوا، ترانه‌سرا…)
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_term_chips' ) ) {
	/**
	 * فهرست ترم‌های یک نوشته به‌شکل چیپ لینک‌دار.
	 *
	 * @param string $taxonomy نامک تاکسونومی.
	 * @param int    $post_id  شناسهٔ نوشته (۰ = نوشتهٔ جاری).
	 * @param string $label    برچسبِ اختیاری قبل از چیپ‌ها.
	 * @return string
	 */
	function tara_term_chips( $taxonomy, $post_id = 0, $label = '' ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		if ( ! $post_id || ! taxonomy_exists( $taxonomy ) ) {
			return '';
		}

		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return '';
		}

		$out = '<span class="ty-chips ty-chips--' . esc_attr( $taxonomy ) . '">';
		if ( '' !== $label ) {
			$out .= '<span class="ty-chips__label">' . esc_html( $label ) . '</span>';
		}
		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$out .= '<a class="ty-chip" href="' . esc_url( $link ) . '">' . esc_html( $term->name ) . '</a>';
		}
		$out .= '</span>';

		return $out;
	}
}

/* -------------------------------------------------------------------------
 * ۴) پلیر — مارکاپِ مشترک
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_player' ) ) {
	/**
	 * پلیرِ «رادیو»: یک کارت بزرگ با کنترل‌های کامل + فهرستِ پخش + نوار چسبان.
	 *
	 * @param array $args {
	 *     @type array  $tracks      فهرست آهنگ‌ها (خروجی tara_track_playlist).
	 *     @type string $title       عنوان کارت.
	 *     @type string $subtitle    زیرعنوان.
	 *     @type string $cover       تصویر بزرگ کارت.
	 *     @type int    $start       ایندکس شروع.
	 *     @type string $context     شناسهٔ زمینه (artist/home/single) برای داده‌ساختاریافته.
	 * }
	 * @return string
	 */
	function tara_player( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'tracks'   => array(),
				'title'    => '',
				'subtitle' => '',
				'cover'    => '',
				'start'    => 0,
				'context'  => 'page',
			)
		);

		$tracks = array_values( array_filter( (array) $args['tracks'] ) );
		if ( empty( $tracks ) ) {
			return '';
		}

		$uid   = 'tara-player-' . wp_unique_id();
		$first = isset( $tracks[ $args['start'] ] ) ? $tracks[ $args['start'] ] : $tracks[0];
		$json  = wp_json_encode( $tracks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		ob_start();
		?>
		<section class="ty-player" id="<?php echo esc_attr( $uid ); ?>" data-tara-player="<?php echo esc_attr( $args['context'] ); ?>" dir="rtl">
			<script type="application/json" class="ty-player__data"><?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON ساختهٔ خودمان است ?></script>

			<div class="ty-player__art">
				<img class="ty-player__img" src="<?php echo esc_url( $args['cover'] ? $args['cover'] : $first['cover'] ); ?>" width="180" height="180" alt="" data-tara-art>
				<span class="ty-player__pulse" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
			</div>

			<div class="ty-player__main">
				<div class="ty-player__head">
					<div class="ty-player__labels">
						<?php if ( '' !== $args['title'] ) : ?>
							<span class="ty-player__eyebrow"><?php echo esc_html( $args['title'] ); ?></span>
						<?php endif; ?>
						<a class="ty-player__song" href="<?php echo esc_url( $first['link'] ); ?>" data-tara-title-link>
							<span data-tara-title><?php echo esc_html( $first['title'] ); ?></span>
						</a>
						<span class="ty-player__artist" data-tara-artist><?php echo esc_html( $first['artist'] ); ?></span>
					</div>
					<span class="ty-player__count" data-tara-count><?php echo esc_html( tara_fa_number( count( $tracks ) ) ); ?> قطعه</span>
				</div>

				<div class="ty-player__transport">
					<button type="button" class="ty-player__btn ty-player__btn--skip" data-tara-prev aria-label="قطعهٔ قبل">
						<span class="ty-icon-prev" aria-hidden="true"></span>
					</button>
					<button type="button" class="ty-player__btn ty-player__btn--main" data-tara-toggle aria-label="پخش / توقف">
						<span class="ty-icon-play" aria-hidden="true"></span>
						<span class="ty-icon-pause" aria-hidden="true"></span>
						<span class="ty-player__spinner" aria-hidden="true"></span>
					</button>
					<button type="button" class="ty-player__btn ty-player__btn--skip" data-tara-next aria-label="قطعهٔ بعد">
						<span class="ty-icon-next" aria-hidden="true"></span>
					</button>

					<div class="ty-player__timeline">
						<div class="ty-player__buffer" data-tara-buffer></div>
						<div class="ty-player__fill" data-tara-fill></div>
						<input type="range" class="ty-player__seek" data-tara-seek min="0" max="100" value="0" step="0.1" aria-label="جای پخش">
					</div>

					<span class="ty-player__time" data-tara-time>۰۰:۰۰ / ۰۰:۰۰</span>

					<div class="ty-player__vol">
						<button type="button" class="ty-player__vol-btn" data-tara-mute aria-label="بی‌صدا">
							<span class="ty-icon-vol" aria-hidden="true"></span>
						</button>
						<input type="range" data-tara-volume min="0" max="1" step="0.01" value="1" aria-label="صدا">
					</div>
				</div>
			</div>

			<audio class="ty-player__audio" preload="none" data-tara-audio playsinline></audio>

			<?php if ( count( $tracks ) > 1 ) : ?>
				<ol class="ty-player__playlist">
					<?php foreach ( $tracks as $index => $track ) : ?>
						<li class="ty-player__item<?php echo 0 === $index ? ' is-active' : ''; ?>" data-tara-item="<?php echo esc_attr( $index ); ?>">
							<button type="button" class="ty-player__item-btn" data-tara-play="<?php echo esc_attr( $index ); ?>">
								<span class="ty-player__item-num"><?php echo esc_html( tara_fa_number( $index + 1 ) ); ?></span>
								<span class="ty-player__item-title"><?php echo esc_html( $track['title'] ); ?></span>
								<span class="ty-player__item-artist"><?php echo esc_html( $track['artist'] ); ?></span>
								<span class="ty-icon-eq" aria-hidden="true"><i></i><i></i><i></i></span>
							</button>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</section>

		<div class="ty-dock" data-tara-dock hidden>
			<img class="ty-dock__img" src="<?php echo esc_url( $first['cover'] ); ?>" width="44" height="44" alt="" data-tara-dock-art>
			<div class="ty-dock__meta">
				<span class="ty-dock__title" data-tara-dock-title><?php echo esc_html( $first['title'] ); ?></span>
				<span class="ty-dock__artist" data-tara-dock-artist><?php echo esc_html( $first['artist'] ); ?></span>
				<div class="ty-dock__rail"><span class="ty-dock__fill" data-tara-dock-fill></span></div>
			</div>
			<button type="button" class="ty-dock__btn" data-tara-dock-prev aria-label="قطعهٔ قبل"><span class="ty-icon-prev"></span></button>
			<button type="button" class="ty-dock__btn ty-dock__btn--main" data-tara-dock-toggle aria-label="پخش / توقف">
				<span class="ty-icon-play"></span><span class="ty-icon-pause"></span>
			</button>
			<button type="button" class="ty-dock__btn" data-tara-dock-next aria-label="قطعهٔ بعد"><span class="ty-icon-next"></span></button>
			<a class="ty-dock__link" href="<?php echo esc_url( $first['link'] ); ?>" data-tara-dock-link aria-label="صفحهٔ آهنگ"><span class="ty-icon-external"></span></a>
		</div>
		<?php

		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'tara_fa_number' ) ) {
	/**
	 * عدد فارسی. اگر هستهٔ قالب تابع خودش را دارد، همان استفاده می‌شود.
	 *
	 * @param int|float $number عدد.
	 * @return string
	 */
	function tara_fa_number( $number ) {
		if ( function_exists( 'tara_number_fa' ) ) {
			return tara_number_fa( $number );
		}

		return strtr( (string) $number, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
	}
}
