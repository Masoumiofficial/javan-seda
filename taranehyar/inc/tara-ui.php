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

if ( ! function_exists( 'tara_media_url' ) ) {
	/**
	 * هر چیزی که ACF/متا برای یک فایل برمی‌گرداند → آدرس واقعی فایل.
	 *
	 * چرا لازم است؟ فیلد «فایل» در ACF می‌تواند سه شکل برگرداند:
	 *   ۱) رشتهٔ آدرس            → همان
	 *   ۲) آرایه (url / ID)       → باید استخراج شود
	 *   ۳) شناسهٔ پیوست (عدد)     → باید با wp_get_attachment_url تبدیل شود
	 * اگر شکل ۲ یا ۳ همین‌طور به `audio.src` برود، مرورگر «1234» را یک مسیر
	 * نسبی می‌فهمد، درخواست ۴۰۴ می‌شود و پلیر «کار نمی‌کند». این تابع همان
	 * باگ را می‌بندد و هیچ داده‌ای را عوض نمی‌کند.
	 *
	 * @param mixed $value مقدار خام.
	 * @return string
	 */
	function tara_media_url( $value ) {
		if ( is_string( $value ) ) {
			$value = trim( $value );

			/* متای قدیمی گاهی آرایهٔ سریال‌شده است */
			if ( '' !== $value && ( '{' === $value[0] || '[' === $value[0] ) ) {
				$maybe = maybe_unserialize( $value );
				if ( is_array( $maybe ) ) {
					return tara_media_url( $maybe );
				}
			}

			if ( '' === $value ) {
				return '';
			}

			/* عدد خالص = شناسهٔ پیوست */
			if ( ctype_digit( $value ) && function_exists( 'wp_get_attachment_url' ) ) {
				$url = wp_get_attachment_url( (int) $value );
				return $url ? (string) $url : '';
			}

			return $value;
		}

		if ( is_numeric( $value ) && function_exists( 'wp_get_attachment_url' ) ) {
			$url = wp_get_attachment_url( (int) $value );
			return $url ? (string) $url : '';
		}

		if ( is_array( $value ) ) {
			foreach ( array( 'url', 'src', 'link' ) as $key ) {
				if ( isset( $value[ $key ] ) && is_string( $value[ $key ] ) && '' !== trim( $value[ $key ] ) ) {
					return trim( $value[ $key ] );
				}
			}
			foreach ( array( 'ID', 'id', 'attachment_id' ) as $key ) {
				if ( isset( $value[ $key ] ) && function_exists( 'wp_get_attachment_url' ) ) {
					$url = wp_get_attachment_url( (int) $value[ $key ] );
					if ( $url ) {
						return (string) $url;
					}
				}
			}
			if ( isset( $value[0] ) ) {
				return tara_media_url( $value[0] );
			}
		}

		return '';
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
			$src = tara_music_value( $key, $post_id );
			if ( '' !== $src ) {
				return $src;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'tara_music_value' ) ) {
	/**
	 * مقدارِ خامِ یک فیلد موسیقی (ACF → متا) و بعد نرمال‌سازی به آدرس/رشته.
	 * برای فیلدهای متنی، همان `tara_music_field` کافی است.
	 *
	 * @param string $key     نام فیلد.
	 * @param int    $post_id شناسهٔ نوشته.
	 * @return string
	 */
	function tara_music_value( $key, $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$raw = null;
		if ( function_exists( 'get_field' ) ) {
			$raw = get_field( $key, $post_id );
		}
		/* get_field وقتی فیلد وجود ندارد، false هم برمی‌گرداند (نه فقط null). */
		if ( null === $raw || false === $raw || '' === $raw || array() === $raw ) {
			$raw = get_post_meta( $post_id, $key, true );
		}

		if ( is_string( $raw ) && '' !== trim( $raw ) && ! in_array( $key, array( 'music320', 'music128', 'music', 'video480', 'video720', 'video1080' ), true ) ) {
			return trim( $raw );
		}

		return tara_media_url( $raw );
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

		/* فیلدهای کاور اختیاریِ محتوا (اگر قالب یا افزونه‌ای داشته باشد) */
		if ( $post_id ) {
			foreach ( array( 'cover', 'poster', 'artwork' ) as $cover_key ) {
				$cover = tara_media_url( get_post_meta( $post_id, $cover_key, true ) );
				if ( '' !== $cover ) {
					return $cover;
				}
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
		<div class="<?php echo esc_attr( $classes ); ?>"
			data-tara-row="<?php echo esc_attr( (int) $args['index'] ); ?>"
			data-tara-date="<?php echo esc_attr( (string) get_post_time( 'U', true, $post_id ) ); ?>"
			data-tara-name="<?php echo esc_attr( $title ); ?>"
			data-tara-artist="<?php echo esc_attr( $artist ); ?>">
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

if ( ! function_exists( 'tara_queue_attrs' ) ) {
	/**
	 * صف پخشِ یک بخش را روی ظرف فهرست می‌نویسد.
	 *
	 * @param array  $tracks خروجی tara_track_playlist().
	 * @param string $title  عنوان بخش (برای سربرگ پاپ‌آپ).
	 * @return string attribute های آمادهٔ چاپ.
	 */
	function tara_queue_attrs( $tracks, $title = '' ) {
		$tracks = array_values( array_filter( (array) $tracks ) );
		if ( empty( $tracks ) ) {
			return '';
		}
		$json = wp_json_encode( $tracks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( ! is_string( $json ) ) {
			return '';
		}
		$GLOBALS['tara_has_queue'] = true;
		$out = ' data-tara-queue="' . esc_attr( $json ) . '"';
		if ( '' !== $title ) {
			$out .= ' data-tara-title="' . esc_attr( wp_strip_all_tags( $title ) ) . '"';
		}
		return $out;
	}
}

if ( ! function_exists( 'tara_card_play' ) ) {
	/**
	 * دکمهٔ پخشِ کوچک روی کارت آهنگ. صف از نزدیک‌ترین `[data-tara-queue]`
	 * خوانده می‌شود؛ بدون صف، ردیف در کارت اصلی پیدا می‌شود.
	 *
	 * @param int $post_id شناسهٔ نوشته.
	 * @return string
	 */
	function tara_card_play( $post_id = 0 ) {
		/* خاموشی: define( 'TARA_CARD_PLAY', false ) یا تنظیمات قالب «ab_card_play» */
		if ( ( defined( 'TARA_CARD_PLAY' ) && ! TARA_CARD_PLAY )
			|| ( function_exists( 'tara_ui_on' ) && ! tara_ui_on( 'ab_card_play', true, 'TARA_CARD_PLAY' ) ) ) {
			return '';
		}
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		if ( ! $post_id ) {
			return '';
		}
		$src = function_exists( 'tara_track_src' ) ? tara_track_src( $post_id ) : '';
		if ( '' === $src ) {
			return '';
		}
		return '<button type="button" class="ty-cardplay" data-tara-track="' . esc_attr( $post_id ) . '" aria-label="پخش ' . esc_attr( tara_track_title( $post_id ) ) . '">'
			. '<span class="ty-icon-play" aria-hidden="true"></span>'
			. '<span class="ty-icon-pause" aria-hidden="true"></span>'
			. '</button>';
	}
}

if ( ! function_exists( 'tara_track_list' ) ) {
	/**
	 * فهرست آهنگ‌ها با «صف پخش» مخصوص خودش.
	 *
	 * چرا صف روی خودِ <ul>؟ چون در یک صفحه چند فهرست داریم (آهنگ‌ها، آلبوم‌ها،
	 * آرشیو) و شمارهٔ ردیف در هر کدام از صفر شروع می‌شود. با
	 * `data-tara-queue` هر فهرست صف خودش را دارد و پلیرِ چسبان همان را پخش
	 * می‌کند — بدون هیچ کوئری یا AJAX اضافه.
	 *
	 * @param array $posts فهرست نوشته‌ها یا شناسه‌ها.
	 * @param array $args  title/badge/show_date/class/context.
	 * @return string
	 */
	function tara_track_list( $posts, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'title'     => '',
				'badge'     => '',
				'show_date' => false,
				'class'     => 'ty-tracklist',
				'context'   => '',
			)
		);

		$posts = (array) $posts;
		if ( empty( $posts ) ) {
			return '';
		}

		$queue = array();
		$rows  = array();

		foreach ( $posts as $item ) {
			$post_id = is_object( $item ) ? (int) $item->ID : (int) $item;
			if ( ! $post_id ) {
				continue;
			}

			$src   = function_exists( 'tara_track_src' ) ? tara_track_src( $post_id ) : '';
			$index = -1;

			if ( '' !== $src ) {
				$index   = count( $queue );
				$queue[] = array(
					'id'     => $post_id,
					'title'  => tara_track_title( $post_id ),
					'artist' => tara_track_artist( $post_id ),
					'src'    => $src,
					'cover'  => tara_track_cover( $post_id, 'thumb2' ),
					'link'   => get_permalink( $post_id ),
				);
			}

			$rows[] = tara_track_row(
				$post_id,
				array(
					'index'     => $index,
					'badge'     => $args['badge'],
					'show_date' => (bool) $args['show_date'],
				)
			);
		}

		if ( empty( $rows ) ) {
			return '';
		}

		$attrs = '';
		if ( ! empty( $queue ) ) {
			$json = wp_json_encode( $queue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			if ( is_string( $json ) ) {
				$attrs .= ' data-tara-queue="' . esc_attr( $json ) . '"';
			}
			if ( '' !== $args['title'] ) {
				$attrs .= ' data-tara-title="' . esc_attr( $args['title'] ) . '"';
			}
			$attrs .= ' data-tara-context="' . esc_attr( $args['context'] ) . '"';
		}

		$html = '<ul class="' . esc_attr( $args['class'] ) . '"' . $attrs . '>';
		foreach ( $rows as $row ) {
			$html .= '<li>' . $row . '</li>';
		}
		$html .= '</ul>';

		return $html;
	}
}

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

if ( ! function_exists( 'tara_dock_markup' ) ) {
	/**
	 * نوار چسبانِ پایین صفحه. یک‌بار در هر صفحه رندر می‌شود و همان صدای
	 * پلیر اصلی را کنترل می‌کند (همگام‌سازی در js/tara-player.js).
	 *
	 * @param array $first قطعهٔ اول (برای مقدار شروع).
	 * @return string
	 */
	function tara_dock_markup( $first ) {
		$first = wp_parse_args(
			(array) $first,
			array(
				'title'  => 'در حال پخش',
				'artist' => '',
				'cover'  => '',
				'link'   => '',
			)
		);

		/* آدرس خالی = درخواست به خودِ صفحه؛ پس یک تصویر جانشین می‌گذاریم */
		if ( '' === (string) $first['cover'] ) {
			$first['cover'] = get_template_directory_uri() . '/images/nomusics.jpg';
		}
		if ( '' === (string) $first['link'] ) {
			$first['link'] = home_url( '/' );
		}

		ob_start();
		?>
		<div class="ty-dock" data-tara-dock hidden dir="rtl">
			<span class="ty-dock__bars" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
			<a class="ty-dock__art" href="<?php echo esc_url( $first['link'] ); ?>" tabindex="-1" aria-hidden="true">
				<img class="ty-dock__img" src="<?php echo esc_url( $first['cover'] ); ?>" width="44" height="44" alt="" data-tara-dock-art>
			</a>
			<div class="ty-dock__meta">
				<a class="ty-dock__title" href="<?php echo esc_url( $first['link'] ); ?>" data-tara-dock-link data-tara-dock-title><?php echo esc_html( $first['title'] ); ?></a>
				<span class="ty-dock__artist" data-tara-dock-artist><?php echo esc_html( $first['artist'] ); ?></span>
				<div class="ty-dock__rail"><span class="ty-dock__fill" data-tara-dock-fill></span></div>
			</div>
			<button type="button" class="ty-dock__btn" data-tara-dock-prev aria-label="قطعهٔ قبل"><span class="ty-icon-prev" aria-hidden="true"></span></button>
			<button type="button" class="ty-dock__btn ty-dock__btn--main" data-tara-dock-toggle aria-label="پخش / توقف">
				<span class="ty-icon-play" aria-hidden="true"></span><span class="ty-icon-pause" aria-hidden="true"></span>
			</button>
			<button type="button" class="ty-dock__btn" data-tara-dock-next aria-label="قطعهٔ بعد"><span class="ty-icon-next" aria-hidden="true"></span></button>
			<button type="button" class="ty-dock__btn ty-dock__btn--list" data-tara-dock-list aria-label="فهرست پخش" aria-haspopup="dialog">
				<span class="ty-icon-list" aria-hidden="true"></span>
			</button>
			<button type="button" class="ty-dock__btn ty-dock__btn--close" data-tara-dock-close aria-label="بستن پلیر">
				<span class="ty-icon-close" aria-hidden="true"></span>
			</button>
		</div>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'tara_playlist_modal' ) ) {
	/**
	 * کادر (پاپ‌آپ) فهرست پخش — یک‌بار در هر صفحه.
	 * محتوای ردیف‌ها را جاوااسکریپت از صفِ همان پلیر می‌سازد، پس یک کادر
	 * برای همهٔ پلیرهای صفحه کافی است و صفحه با لیست‌های تکراری سنگین نمی‌شود.
	 *
	 * @return string
	 */
	function tara_playlist_modal() {
		ob_start();
		?>
		<div class="ty-modal" id="tara-playlist-modal" data-tara-modal hidden>
			<div class="ty-modal__backdrop" data-tara-modal-close></div>
			<div class="ty-modal__panel" role="dialog" aria-modal="true" aria-labelledby="tara-modal-title">
				<header class="ty-modal__head">
					<div class="ty-modal__heading">
						<span class="ty-modal__eyebrow">در حال پخش از</span>
						<h2 class="ty-modal__title" id="tara-modal-title" data-tara-modal-title>فهرست پخش</h2>
					</div>
					<span class="ty-modal__count" data-tara-modal-count></span>
					<button type="button" class="ty-modal__close" data-tara-modal-close aria-label="بستن فهرست">
						<span class="ty-icon-close" aria-hidden="true"></span>
					</button>
				</header>

				<div class="ty-modal__search">
					<span class="ty-icon-search" aria-hidden="true"></span>
					<input type="search" data-tara-modal-search placeholder="جست‌وجو در نام آهنگ یا خواننده…" aria-label="جست‌وجو در فهرست پخش" autocomplete="off">
				</div>

				<ol class="ty-modal__list" data-tara-modal-list></ol>
				<p class="ty-modal__empty" data-tara-modal-empty hidden>چیزی با این نام در فهرست نیست.</p>

				<footer class="ty-modal__foot">
					<span data-tara-modal-meta></span>
					<span class="ty-modal__hint">کلید <kbd>Esc</kbd> کادر را می‌بندد</span>
				</footer>
			</div>
		</div>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'tara_video' ) ) {
	/**
	 * پلیر ویدیو با کنترل کیفیت، سرعت پخش و تصویر شاخص.
	 *
	 * @param array $args {
	 *     @type array  $sources کیفیت => آدرس (مثلاً array( '1080' => '...', '720' => '...' )).
	 *     @type string $poster  تصویر شاخص.
	 *     @type string $title   عنوان.
	 * }
	 * @return string
	 */
	function tara_video( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'sources' => array(),
				'poster'  => '',
				'title'   => '',
			)
		);

		$sources = array();
		foreach ( (array) $args['sources'] as $quality => $src ) {
			$url = tara_media_url( $src );
			if ( '' !== $url ) {
				$sources[ (string) $quality ] = $url;
			}
		}

		if ( empty( $sources ) ) {
			return '';
		}

		/* بالاترین کیفیت، منبع پیش‌فرض */
		$qualities = array_keys( $sources );
		usort(
			$qualities,
			static function ( $a, $b ) {
				return (int) $b - (int) $a;
			}
		);
		$default = $qualities[0];

		ob_start();
		?>
		<section class="ty-video" data-tara-video dir="rtl">
			<div class="ty-video__stage">
				<video class="ty-video__el" data-tara-video-el playsinline preload="metadata"
					<?php if ( '' !== $args['poster'] ) : ?>poster="<?php echo esc_url( $args['poster'] ); ?>"<?php endif; ?>>
					<source src="<?php echo esc_url( $sources[ $default ] ); ?>" type="video/mp4">
				</video>

				<button type="button" class="ty-video__cover" data-tara-video-toggle aria-label="پخش ویدیو">
					<?php if ( '' !== $args['poster'] ) : ?>
						<img class="ty-video__poster" src="<?php echo esc_url( $args['poster'] ); ?>" alt="" loading="lazy">
					<?php endif; ?>
					<span class="ty-video__bigplay" aria-hidden="true"><span class="ty-icon-play"></span></span>
					<?php if ( '' !== $args['title'] ) : ?>
						<span class="ty-video__caption"><?php echo esc_html( $args['title'] ); ?></span>
					<?php endif; ?>
				</button>
			</div>

			<div class="ty-video__bar">
				<button type="button" class="ty-video__btn" data-tara-video-play aria-label="پخش / توقف">
					<span class="ty-icon-play" aria-hidden="true"></span><span class="ty-icon-pause" aria-hidden="true"></span>
				</button>

				<div class="ty-video__rail">
					<span class="ty-video__loaded" data-tara-video-loaded></span>
					<span class="ty-video__played" data-tara-video-played></span>
					<input type="range" class="ty-video__seek" data-tara-video-seek min="0" max="100" value="0" step="0.1" aria-label="جای پخش ویدیو">
				</div>

				<span class="ty-video__time" data-tara-video-time>۰۰:۰۰ / ۰۰:۰۰</span>

				<div class="ty-video__menu">
					<button type="button" class="ty-video__btn ty-video__btn--text" data-tara-video-settings aria-haspopup="true" aria-expanded="false">
						<span data-tara-video-quality><?php echo esc_html( $default ); ?>p</span>
						<span class="ty-icon-caret" aria-hidden="true"></span>
					</button>
					<div class="ty-video__pop" data-tara-video-pop hidden>
						<span class="ty-video__pop-title">کیفیت</span>
						<div class="ty-video__qualities">
							<?php foreach ( $qualities as $quality ) : ?>
								<button type="button" class="ty-video__quality<?php echo $quality === $default ? ' is-active' : ''; ?>" data-tara-video-src="<?php echo esc_url( $sources[ $quality ] ); ?>" data-tara-video-q="<?php echo esc_attr( $quality ); ?>"><?php echo esc_html( $quality ); ?>p</button>
							<?php endforeach; ?>
						</div>
						<span class="ty-video__pop-title">سرعت پخش</span>
						<div class="ty-video__rates">
							<?php foreach ( array( '0.75', '1', '1.25', '1.5', '2' ) as $rate ) : ?>
								<button type="button" class="ty-video__rate<?php echo '1' === $rate ? ' is-active' : ''; ?>" data-tara-video-rate="<?php echo esc_attr( $rate ); ?>"><?php echo esc_html( '1' === $rate ? 'عادی' : $rate . '×' ); ?></button>
							<?php endforeach; ?>
						</div>
						<button type="button" class="ty-video__pip" data-tara-video-pip><span class="ty-icon-pip" aria-hidden="true"></span> تصویر در تصویر</button>
					</div>
				</div>

				<button type="button" class="ty-video__btn" data-tara-video-mute aria-label="بی‌صدا">
					<span class="ty-icon-vol" aria-hidden="true"></span>
				</button>
				<button type="button" class="ty-video__btn" data-tara-video-fs aria-label="تمام‌صفحه">
					<span class="ty-icon-expand" aria-hidden="true"></span>
				</button>
			</div>
		</section>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

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
		/* نوار چسبان و کادر فهرست فقط یک‌بار در هر صفحه رندر می‌شوند. */
		static $tara_dock_done = false;
		static $tara_audio_done = false;

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
						<span class="ty-player__eyebrow"><?php echo esc_html( '' !== $args['title'] ? $args['title'] : 'پخش آنلاین' ); ?></span>
						<a class="ty-player__song" href="<?php echo esc_url( $first['link'] ); ?>" data-tara-title-link>
							<span data-tara-title><?php echo esc_html( $first['title'] ); ?></span>
						</a>
						<span class="ty-player__artist" data-tara-artist><?php echo esc_html( $first['artist'] ); ?></span>
					</div>

					<div class="ty-player__tools">
						<span class="ty-player__count" data-tara-count><?php echo esc_html( tara_fa_number( count( $tracks ) ) ); ?> قطعه</span>
						<button type="button" class="ty-player__tool" data-tara-list aria-haspopup="dialog" aria-expanded="false" aria-controls="tara-playlist-modal">
							<span class="ty-icon-list" aria-hidden="true"></span>
							<span class="ty-player__tool-label">فهرست پخش</span>
						</button>
						<a class="ty-player__tool" href="<?php echo esc_url( $first['link'] ); ?>" data-tara-title-link aria-label="رفتن به صفحهٔ این آهنگ">
							<span class="ty-icon-note" aria-hidden="true"></span>
							<span class="ty-player__tool-label">صفحهٔ آهنگ</span>
						</a>
					</div>
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

				<p class="ty-player__error" data-tara-error hidden>فایل صوتی این قطعه در دسترس نیست. قطعهٔ بعدی را امتحان کنید.</p>
			</div>

			<?php if ( ! $tara_audio_done ) : ?>
				<?php $tara_audio_done = true; ?>
				<audio class="ty-player__audio" preload="none" data-tara-audio playsinline></audio>
			<?php endif; ?>
		</section>

		<?php if ( ! $tara_dock_done ) : ?>
		<?php $tara_dock_done = true; ?>
		<?php $GLOBALS['tara_dock_printed'] = true; ?>
		<?php $GLOBALS['tara_modal_printed'] = true; ?>
		<?php echo tara_dock_markup( $first ); // phpcs:ignore WordPress.Security.EscapeOutput -- escape شده در تابع ?>
		<?php echo tara_playlist_modal(); // phpcs:ignore WordPress.Security.EscapeOutput -- escape شده در تابع ?>
		<?php endif; ?>
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

if ( ! function_exists( 'tara_runtime_footer' ) ) {
	/**
	 * تضمینِ وجود نوار چسبان و پاپ‌آپ فهرست در صفحه.
	 *
	 * چرا لازم است؟ در صفحهٔ اصلی، صف‌ها از فهرست بخش‌ها می‌آیند
	 * (`data-tara-queue`). اگر کارتِ پلیر خاموش باشد یا آهنگی برای آن پیدا
	 * نشود، هیچ‌جا `tara_player()` صدا زده نمی‌شود؛ در نتیجه پاپ‌آپ و نوار
	 * چسبان هم ساخته نمی‌شدند و دکمهٔ «فهرست پخش» روی کارت‌ها بی‌اثر بود.
	 *
	 * این تابع در `wp_footer` و فقط وقتی صفی در صفحه هست، آن دو را (مخفی)
	 * چاپ می‌کند. هیچ خروجی اضافه‌ای روی صفحه‌های بی‌صف ندارد.
	 */
	function tara_runtime_footer() {
		if ( empty( $GLOBALS['tara_has_queue'] ) ) {
			return;
		}

		$dock  = empty( $GLOBALS['tara_dock_printed'] );
		$modal = empty( $GLOBALS['tara_modal_printed'] );

		if ( ! $dock && ! $modal ) {
			return;
		}

		$placeholder = array(
			'id'     => 0,
			'title'  => '',
			'artist' => '',
			'src'    => '',
			'cover'  => '',
			'link'   => '',
		);

		if ( $dock && function_exists( 'tara_dock_markup' ) ) {
			echo tara_dock_markup( $placeholder ); // phpcs:ignore WordPress.Security.EscapeOutput -- escape شده در تابع
			$GLOBALS['tara_dock_printed'] = true;
		}
		if ( $modal && function_exists( 'tara_playlist_modal' ) ) {
			echo tara_playlist_modal(); // phpcs:ignore WordPress.Security.EscapeOutput -- escape شده در تابع
			$GLOBALS['tara_modal_printed'] = true;
		}
	}
	add_action( 'wp_footer', 'tara_runtime_footer', 30 );
}
