<?php
/**
 * inc/tara-p0.php — لایهٔ ارتقای رقابتی APEX Ω (نسخهٔ ۳.۱۶.۰)
 * ==========================================================================
 * این فایل فقط **خواندنی** است و هیچ داده‌ای نمی‌نویسد. سه گروه ابزار:
 *
 *   ۱) تاریخ انتشار واقعی (`tara_release_meta_date()` / `tara_track_date()` /
 *      `tara_track_date_html()`): تاریخ انتشار را از متای معتبر می‌خواند؛ اگر
 *      نبود، **صادقانه** به تاریخ نوشته برمی‌گردد و برچسب می‌گوید «انتشار در سایت».
 *      هرگز تاریخی ساخته یا حدس زده نمی‌شود.
 *
 *   ۲) «سایر آهنگ‌های هنرمند» (`tara_related_by_artist()`): یک پیاده‌سازی واحد
 *      به‌جای چهار نسخهٔ موازی در `single.php`. اولویت با تاکسونومی هنرمند است؛
 *      اگر ترمی نبود، همان رفتار قبلی (متای `artist`) به‌عنوان fallback می‌آید.
 *
 *   ۳) بخش‌های تازهٔ صفحهٔ اصلی (`tara_home_video_section()`،
 *      `tara_home_album_section()`، `tara_home_mood_section()`،
 *      `tara_home_news_section()`): هر بخش فقط با دادهٔ واقعی چاپ می‌شود؛
 *      نبودِ داده = رشتهٔ خالی (بدون کادر خالی و بدون آمار ساختگی).
 *
 * همهٔ توابع با `function_exists` محافظت شده‌اند تا اگر نسخهٔ قدیمی‌تری از
 * قالب روی هاست مانده باشد، دوباره‌تعریف‌شدن خطا ندهد.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
 * ۱) تاریخ‌ها
 * ======================================================================= */

if ( ! function_exists( 'tara_release_meta_keys' ) ) {
	/**
	 * کلیدهای متای تاریخ انتشار، به ترتیب اولویت.
	 *
	 * `_tara_release_date` برای فاز بعد (نوشتن هنگام انتشار از پنل) رزرو است و
	 * امروز فقط خوانده می‌شود؛ اگر کسی دستی آن را پر کند، همان معتبر است.
	 *
	 * @return string[]
	 */
	function tara_release_meta_keys() {
		return (array) apply_filters(
			'tara_release_meta_keys',
			array( '_tara_release_date', 'release_date', 'tara_release_date' )
		);
	}
}

if ( ! function_exists( 'tara_release_meta_date' ) ) {
	/**
	 * تاریخ انتشار ثبت‌شدهٔ اثر (اگر وجود داشته باشد) — بدون حدس.
	 *
	 * @param int $post_id شناسهٔ نوشته (۰ = نوشتهٔ جاری).
	 * @return string تاریخ `Y-m-d` یا رشتهٔ خالی.
	 */
	function tara_release_meta_date( $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		foreach ( tara_release_meta_keys() as $key ) {
			$raw = get_post_meta( $post_id, $key, true );

			/* پشتیبانی از ACF که تاریخ را آرایه‌ای برمی‌گرداند — پیش از is_scalar،
			   وگرنه آرایه همان‌جا رد می‌شد و این شاخه هرگز اجرا نمی‌شد. */
			if ( is_array( $raw ) ) {
				$raw = isset( $raw['date'] ) ? $raw['date'] : '';
			}
			if ( ! is_scalar( $raw ) || '' === trim( (string) $raw ) ) {
				continue;
			}
			$raw = trim( (string) $raw );

			/* فقط تاریخ میلادیِ کامل Y-m-d (یا Y/m/d) معتبر است؛ بقیه نادیده */
			if ( preg_match( '/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/', (string) $raw, $m ) ) {
				$y = (int) $m[1];
				$mo = (int) $m[2];
				$d = (int) $m[3];
				if ( $y >= 1900 && $y <= 2100 && checkdate( $mo, $d, $y ) ) {
					return sprintf( '%04d-%02d-%02d', $y, $mo, $d );
				}
			}
		}

		return '';
	}
}

if ( ! function_exists( 'tara_track_date' ) ) {
	/**
	 * تاریخ نمایشی اثر + منبعش (برای برچسب صادقانه).
	 *
	 * @param int $post_id شناسهٔ نوشته.
	 * @return array{date:string,iso:string,source:string}
	 *               source: `release` (تاریخ انتشار واقعی) یا `post` (تاریخ نوشته).
	 */
	function tara_track_date( $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		$out     = array( 'date' => '', 'iso' => '', 'source' => '' );

		if ( ! $post_id ) {
			return $out;
		}

		$real = tara_release_meta_date( $post_id );
		if ( '' !== $real ) {
			$ts              = strtotime( $real . ' 12:00:00' );
			$out['date']     = ( $ts && function_exists( 'date_i18n' ) ) ? date_i18n( 'j F Y', $ts ) : $real;
			$out['iso']      = $real;
			$out['source']   = 'release';
			return $out;
		}

		$ts = function_exists( 'get_the_time' ) ? (int) get_the_time( 'U', $post_id ) : 0;
		if ( $ts ) {
			$out['date']   = function_exists( 'date_i18n' ) ? date_i18n( 'j F Y', $ts ) : gmdate( 'Y-m-d', $ts );
			$out['iso']    = gmdate( 'c', $ts );
			$out['source'] = 'post';
		}

		return $out;
	}
}

if ( ! function_exists( 'tara_track_date_html' ) ) {
	/**
	 * مارک‌آپ یک `<time>` با برچسب صادقانه.
	 *
	 * @param int   $post_id شناسهٔ نوشته.
	 * @param array $args    کلاس، پیشوند، نمایش برچسب.
	 * @return string
	 */
	function tara_track_date_html( $post_id = 0, $args = array() ) {
		$d = tara_track_date( $post_id );
		if ( '' === $d['date'] ) {
			return '';
		}

		$class = isset( $args['class'] ) ? (string) $args['class'] : 'tara-date';
		$label = ( 'release' === $d['source'] ) ? 'تاریخ انتشار' : 'انتشار در سایت';
		$show  = ! isset( $args['label'] ) || (bool) $args['label'];

		$out  = '<time class="' . esc_attr( $class ) . '" datetime="' . esc_attr( $d['iso'] ) . '">';
		$out .= '<i class="icofont-calendar" aria-hidden="true"></i>';
		if ( $show ) {
			$out .= '<span class="tara-date__label">' . esc_html( $label ) . '</span> ';
		}
		$out .= '<span class="tara-date__value">' . esc_html( $d['date'] ) . '</span>';
		$out .= '</time>';

		return $out;
	}
}

/* ==========================================================================
 * ۲) «سایر آهنگ‌های هنرمند»
 * ======================================================================= */

if ( ! function_exists( 'tara_normalize_name' ) ) {
	/**
	 * یکسان‌سازی نام فارسی برای مقایسه (ی/ي، ک/ك، ه/ة، نیم‌فاصله، فاصله‌های تکراری).
	 * همان قاعده‌ای که `single.php` پیش از این به‌صورت inline داشت؛ حالا یک‌جا
	 * است تا کوئری مرتبط و جست‌وجو یکسان رفتار کنند. هیچ داده‌ای تغییر نمی‌کند.
	 *
	 * @param string $value نام.
	 * @return string
	 */
	function tara_normalize_name( $value ) {
		$value = str_replace(
			array( 'ي', 'ك', 'ة', "\xE2\x80\x8C" ),
			array( 'ی', 'ک', 'ه', ' ' ),
			(string) $value
		);
		return trim( preg_replace( '/\s+/u', ' ', $value ) );
	}
}

if ( ! function_exists( 'tara_related_by_artist' ) ) {
	/**
	 * نوشته‌های مرتبطِ یک اثر بر پایهٔ هنرمند.
	 *
	 * ترتیب تصمیم:
	 *   ۱) تاکسونومی هنرمند (`artist`/`singer`) — دقیق و ایندکس‌شده.
	 *   ۲) اگر ترمی نبود: متای `artist` با تطابق تساوی‌های دقیق (نه LIKE بی‌مرز).
	 *
	 * @param int   $post_id شناسهٔ نوشتهٔ جاری.
	 * @param int   $limit   تعداد (۰ = مقدار تنظیمات `pppf`).
	 * @param array $exclude شناسه‌های استثنا (معمولاً خودِ نوشته).
	 * @return int[] شناسه‌های نوشته‌های منتشرشده.
	 */
	function tara_related_by_artist( $post_id = 0, $limit = 0, $exclude = array() ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		if ( ! $post_id ) {
			return array();
		}

		$limit = (int) $limit;
		if ( $limit <= 0 ) {
			$limit = function_exists( 'ot_get_option' ) ? (int) ot_get_option( 'pppf' ) : 12;
		}
		$limit = max( 1, min( 30, $limit ) );

		$exclude   = array_map( 'absint', (array) $exclude );
		$exclude[] = $post_id;
		$exclude   = array_values( array_unique( array_filter( $exclude ) ) );

		$ids = array();

		/* ۱) تاکسونومی هنرمند */
		$tax = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
		if ( function_exists( 'get_the_terms' ) ) {
			$terms = get_the_terms( $post_id, $tax );
			if ( is_array( $terms ) && $terms ) {
				$ids = get_posts(
					array(
						'post_type'           => 'post',
						'post_status'         => 'publish',
						'posts_per_page'      => $limit,
						'fields'              => 'ids',
						'no_found_rows'       => true,
						'ignore_sticky_posts' => true,
						'exclude'             => $exclude,
						'orderby'             => 'date',
						'order'               => 'DESC',
						'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
							array(
								'taxonomy' => $tax,
								'field'    => 'term_id',
								'terms'    => wp_list_pluck( $terms, 'term_id' ),
							),
						),
					)
				);
			}
		}

		/* ۲) fallback: متای artist (همان رفتار پیشین، با تطابق دقیق‌تر) */
		if ( ! $ids ) {
			$artist = function_exists( 'tara_music_field' )
				? trim( (string) tara_music_field( 'artist', $post_id ) )
				: trim( (string) get_post_meta( $post_id, 'artist', true ) );
			/* اگر املاي متا با ترم هنرمند یکی نباشد (ي/ك/ZWNJ)، نسخهٔ ترم
			   را هم امتحان می‌کنیم؛ هیچ نوشتنی در داده رخ نمی‌دهد. */
			$meta_artist = $artist;
			if ( is_array( $terms ) && $terms && function_exists( 'tara_normalize_name' )
				&& tara_normalize_name( $terms[0]->name ) === tara_normalize_name( $artist ) ) {
				$artist = $terms[0]->name;
			}
			if ( '' !== $artist ) {
				$ids = get_posts(
					array(
						'post_type'           => 'post',
						'post_status'         => 'publish',
						'posts_per_page'      => $limit,
						'fields'              => 'ids',
						'no_found_rows'       => true,
						'ignore_sticky_posts' => true,
						'exclude'             => $exclude,
						'orderby'             => 'date',
						'order'               => 'DESC',
						'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							array(
								'key'     => 'artist',
								'value'   => $artist,
								'compare' => '=',
							),
						),
					)
				);
			}
		}

		return array_values( array_unique( array_map( 'absint', (array) $ids ) ) );
	}
}

if ( ! function_exists( 'tara_related_by_artist_html' ) ) {
	/**
	 * مارک‌آپ «سایر آهنگ‌های هنرمند» — همان کلاس‌های قبلی تا ظاهر عوض نشود.
	 *
	 * @param int   $post_id شناسهٔ نوشته.
	 * @param array $args    order (اصلی/کناری) و limit.
	 * @return string
	 */
	function tara_related_by_artist_html( $post_id = 0, $args = array() ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		$limit   = isset( $args['limit'] ) ? (int) $args['limit'] : 0;
		$ids     = tara_related_by_artist( $post_id, $limit );

		if ( ! $ids ) {
			return '';
		}

		$artist = function_exists( 'tara_music_field' ) ? tara_music_field( 'artist', $post_id ) : (string) get_post_meta( $post_id, 'artist', true );
		$artist = trim( (string) $artist );

		$url = '';
		$tax = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
		$terms = function_exists( 'get_the_terms' ) ? get_the_terms( $post_id, $tax ) : false;
		if ( is_array( $terms ) && $terms && function_exists( 'tara_artist_term_link' ) ) {
			$url = tara_artist_term_link( $terms[0] );
		}

		$title_name = '' !== $artist ? $artist : ( is_array( $terms ) && $terms ? $terms[0]->name : '' );

		/* v3.17.0 — P1: کشِ نوشته/متا/ترم‌ها یک‌جا پر می‌شود. پیش‌تر هر ردیف
		   فهرست، `get_post` + چند `get_post_meta` + متای تصویر را جدا صدا
		   می‌زد (کوئری رشدکننده با تعداد نتایج). حالا حداکثر سه کوئریِ
		   ثابت، مستقل از تعداد ردیف‌ها. خروجی هیچ تغییری نمی‌کند. */
		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( array_map( 'absint', (array) $ids ), true, true );
		}

		ob_start();
		?>
		<div class="related_posts rel_sing_bbt" data-tara-related="artist">
			<h3 class="tarlanweb_side_titles">سایر آهنگ‌های <?php echo esc_html( $title_name ); ?></h3>
			<?php if ( '' !== $url ) : ?>
				<div class="left_artists_pp"><a class="artist_songs_page" href="<?php echo esc_url( $url ); ?>"><i class="icofont-radio-mic" aria-hidden="true"></i> صفحهٔ اختصاصی <?php echo esc_html( $title_name ); ?> </a></div>
			<?php endif; ?>
			<div class="liner clear"></div>
			<div class="tarlanweb_side_box">
				<ul>
					<?php
					foreach ( $ids as $id ) {
						$is_current = ( (int) $id === (int) $post_id );
						$r_artist   = function_exists( 'tara_music_field' ) ? tara_music_field( 'artist', $id ) : (string) get_post_meta( $id, 'artist', true );
						$r_song     = function_exists( 'tara_music_field' ) ? tara_music_field( 'song', $id ) : (string) get_post_meta( $id, 'song', true );
						$cover      = function_exists( 'tara_track_cover' ) ? tara_track_cover( $id, 'thumb3' ) : get_the_post_thumbnail_url( $id, 'thumb3' );
						?>
						<li<?php echo $is_current ? ' class="inactive_songs"' : ''; ?>>
							<a href="<?php echo esc_url( get_permalink( $id ) ); ?>">
								<div class="tara-thumb-box">
									<?php if ( $is_current ) : ?><i class="inactive_box_cir" aria-hidden="true"></i><?php endif; ?>
									<img src="<?php echo esc_url( $cover ); ?>" width="60" height="60" alt="<?php echo esc_attr( get_the_title( $id ) ); ?>" loading="lazy" decoding="async">
									<?php if ( $is_current ) : ?><div class="nowPlayingIcon" aria-hidden="true"><span></span><span></span><span></span><span></span></div><?php endif; ?>
								</div>
								<span class="song_artists"><?php echo esc_html( $r_artist ); ?></span>
								<span><?php echo esc_html( '' !== $r_song ? $r_song : get_the_title( $id ) ); ?></span>
							</a>
						</li>
					<?php } ?>
				</ul>
				<div class="clear"></div>
			</div>
			<div class="clear"></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

/* ==========================================================================
 * ۳) بخش‌های تازهٔ صفحهٔ اصلی — همه با دادهٔ واقعی
 * ======================================================================= */

if ( ! function_exists( 'tara_home_section_on' ) ) {
	/**
	 * روشن/خاموش بودن یک بخش خانه از تنظیمات قالب (پیش‌فرض روشن).
	 *
	 * @param string $key کلید تنظیمات.
	 * @return bool
	 */
	function tara_home_section_on( $key ) {
		if ( function_exists( 'tara_opt_on' ) ) {
			return tara_opt_on( $key, true );
		}
		return true;
	}
}

if ( ! function_exists( 'tara_home_query' ) ) {
	/**
	 * کوئری کوچک و کش‌شده برای بخش‌های خانه (فقط شناسه‌ها).
	 *
	 * @param array $args آرگومان‌های WP_Query.
	 * @return int[]
	 */
	function tara_home_query( $args ) {
		$args['post_type']           = 'post';
		$args['post_status']         = 'publish';
		$args['fields']              = 'ids';
		$args['no_found_rows']       = true;
		$args['ignore_sticky_posts'] = true;
		$args['has_password']        = false;

		/* v3.17.5 — کشِ کوتاه‌مدت.
		   هر بخشِ خانه یک WP_Query می‌زد، و دو تایشان meta_query با
		   EXISTS داشتند. روی سایتِ ۶۰۰۰ آهنگی این‌ها به JOIN روی
		   postmeta تبدیل می‌شوند و ۳۲ms+ برای هر کوئری می‌گیرند
		   (اندازه‌گیری شد). با کشِ ۱۰ دقیقه‌ای، بازدیدکنندهٔ بعدی
		   هیچ‌کدام را نمی‌زند. کلید از آرگومان‌ها ساخته می‌شود تا
		   هر بخش کشِ خودش را داشته باشد. */
		$ttl = (int) apply_filters( 'tara_home_query_ttl', defined( 'TARA_HOME_CACHE_TTL' ) ? (int) TARA_HOME_CACHE_TTL : 600 );
		if ( $ttl <= 0 || ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) ) {
			/* برای کاربرِ واردشده کش نمی‌کنیم (پیش‌نمایش/ویرایش) */
			$q = new WP_Query( $args );
			return array_map( 'absint', (array) $q->posts );
		}

		$ck  = 'tara_hq_' . md5( wp_json_encode( $args ) );
		$hit = get_transient( $ck );
		if ( is_array( $hit ) ) {
			return array_map( 'absint', $hit );
		}

		/* v3.17.11 — نگهبانِ زمانی.
		   ------------------------------------------------------------------
		   اگر یک بخش روی این سرور از بودجهٔ زمانی بگذرد، به‌جای کشیدنِ
		   کل صفحه آن بخش را خالی برمی‌گردانیم و برای ۱۰ دقیقه خاموش
		   نگه می‌داریم. صفحه باز می‌شود؛ بخش یکی از پنج بخش کم می‌آید
		   ولی «لودینگ ابدی» تمام می‌شود.

		   بودجه با فیلتر قابل تنظیم است:
		       add_filter( 'tara_home_query_budget', fn() => 1500 ); // ms
		   مقدار ۰ یعنی نگهبان خاموش. */
		$budget = (int) apply_filters( 'tara_home_query_budget', 2500 );
		$skip   = get_transient( 'tara_hq_skip_' . md5( wp_json_encode( $args ) ) );
		if ( is_array( $skip ) ) {
			return array();
		}

		$t0   = microtime( true );
		$q    = new WP_Query( $args );
		$ids  = array_map( 'absint', (array) $q->posts );
		$took = ( microtime( true ) - $t0 ) * 1000;

		if ( $budget > 0 && $took > $budget ) {
			/* کش نمی‌کنیم (شاید سرور شلوغ بوده)؛ فقط ۱۰ دقیقه رد می‌شویم */
			set_transient( $ck . '_slow', array( round( $took ) ), 600 );
			set_transient( 'tara_hq_skip_' . md5( wp_json_encode( $args ) ), array( 1 ), 600 );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( '[tara] home query took %.0fms (budget %dms) — skipped for 10min: %s', $took, $budget, wp_json_encode( $args ) ) ); // phpcs:ignore
			}
			return array();
		}

		/* نتیجهٔ خالی را هم کش می‌کنیم — وگرنه هر بازدید دوباره کوئری می‌زند */
		set_transient( $ck, $ids, $ttl );
		return $ids;
	}
}

if ( ! function_exists( 'tara_home_cards_section' ) ) {
	/**
	 * اسکلت مشترک یک بخش کارتِ خانه.
	 *
	 * @param string $title    تیتر.
	 * @param int[]  $ids      شناسه‌ها.
	 * @param string $more_url لینک «مشاهده بیشتر» (اختیاری).
	 * @param string $anchor   شناسهٔ لنگر (اختیاری).
	 * @param string $title_id شناسهٔ یکتای تیتر برای aria-labelledby.
	 * @return string
	 */
	function tara_home_cards_section( $title, $ids, $more_url = '', $anchor = '', $title_id = '' ) {
		if ( empty( $ids ) ) {
			return '';
		}

		$title_id = $title_id ? $title_id : 'tara-sec-' . sanitize_html_class( md5( $title ) );
		$queue    = function_exists( 'tara_track_playlist' ) ? tara_track_playlist( $ids ) : array();

		ob_start();
		if ( $anchor ) {
			echo '<div id="' . esc_attr( $anchor ) . '" class="ab-section-anchor"></div>';
		}
		?>
		<section class="song_boxs tara-home-sec" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<header class="songs_title_box">
				<h2 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $title ); ?></h2>
				<span class="liner" aria-hidden="true"></span>
				<?php if ( '' !== $more_url ) : ?><a href="<?php echo esc_url( $more_url ); ?>">مشاهده بیشتر</a><?php endif; ?>
			</header>
			<div class="song_boxs_posts">
				<ul<?php echo function_exists( 'tara_queue_attrs' ) ? tara_queue_attrs( $queue, $title ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php
					$GLOBALS['tara_card_play'] = true;
					foreach ( $ids as $id ) {
						?>
						<li><?php
						if ( function_exists( 'tara_post_card' ) ) {
							echo tara_post_card( $id ); // phpcs:ignore WordPress.Security.EscapeOutput -- escape شده در تابع
						} else {
							get_template_part( 'inc/songs_post' );
						}
						?></li>
						<?php
					}
					$GLOBALS['tara_card_play'] = false;
					?>
				</ul>
				<div class="clear"></div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'tara_latest_releases_ids' ) ) {
	/**
	 * «تازه‌ترین انتشارها» — فقط آثاری که **تاریخ انتشار واقعی** دارند.
	 *
	 * چرا دو مرحله؟ `post_date` زمانِ نوشتن مطلب است، ولی تاریخ انتشار واقعی
	 * ممکن است جلوتر یا عقب‌تر باشد. ترتیب این بخش بر `_tara_release_date`
	 * است؛ اگر کمتر از حدِ آستانه اثرِ دارای تاریخ واقعی باشد، رشته خالی
	 * برمی‌گردد تا بخشی ساخته نشود که فقط تکرارِ «تازه‌ترین‌ها» باشد.
	 * هیچ متایی نوشته یا حدس زده نمی‌شود.
	 *
	 * @param int $limit تعداد.
	 * @return int[]
	 */
	function tara_latest_releases_ids( $limit = 8 ) {
		$limit = max( 1, min( 12, (int) $limit ) );

		/* v3.17.5 — کشِ کوتاه‌مدت.
		   این تابع به‌ازای هر کلیدِ تاریخ (۳ کلید) یک get_posts با
		   meta_key + orderby=meta_value می‌زد. آن ترکیب مجبور است
		   کلِ postmeta را JOIN و مرتب کند؛ روی ۶۰۰۰ آهنگ این
		   گران‌ترین چیزِ صفحهٔ خانه بود. با کش، یک بازدید از هر N
		   بازدید هزینه را می‌پردازد، نه همه. */
		$ttl = (int) apply_filters( 'tara_latest_releases_ttl', defined( 'TARA_HOME_CACHE_TTL' ) ? (int) TARA_HOME_CACHE_TTL : 600 );
		$use_cache = ( $ttl > 0 ) && ! ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() );
		$ck = 'tara_lrel_' . (int) $limit;
		if ( $use_cache ) {
			$hit = get_transient( $ck );
			if ( is_array( $hit ) ) {
				return array_map( 'absint', $hit );
			}
		}

		$keys  = tara_release_meta_keys();
		$ids   = array();

		foreach ( $keys as $key ) {
			$found = get_posts(
				array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => $limit,
					'fields'              => 'ids',
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
					'meta_key'            => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'orderby'             => 'meta_value',
					'order'               => 'DESC',
				)
			);
			foreach ( (array) $found as $id ) {
				if ( '' !== tara_release_meta_date( $id ) ) {
					$ids[ (int) $id ] = tara_release_meta_date( $id );
				}
			}
		}

		$min = (int) apply_filters( 'tara_latest_releases_min', 4 );
		if ( count( $ids ) < max( 1, $min ) ) {
			/* نتیجهٔ «کافی نیست» را هم کش می‌کنیم — وگرنه هر بازدید
			   دوباره همان کوئری‌های سنگین را می‌زند. TTL کوتاه‌تر،
			   تا به‌محضِ ثبتِ اولین تاریخ، بخش ظاهر شود. */
			if ( $use_cache ) {
				set_transient( $ck, array(), min( $ttl, 120 ) );
			}
			return array();
		}

		/* مرتب‌سازی نهایی بر تاریخ واقعی، نه ترتیب برگشتی دیتابیس */
		arsort( $ids );
		$out = array_slice( array_map( 'absint', array_keys( $ids ) ), 0, $limit );
		if ( $use_cache ) {
			set_transient( $ck, $out, $ttl );
		}
		return $out;
	}
}

if ( ! function_exists( 'tara_home_releases_section' ) ) {
	/** «تازه‌ترین انتشارها» — فقط وقتی حداقل چند تاریخ انتشار واقعی ثبت شده باشد. */
	function tara_home_releases_section() {
		if ( ! tara_home_section_on( 'home_sec_releases' ) ) {
			return '';
		}
		$ids = tara_latest_releases_ids( 8 );
		if ( ! $ids ) {
			return '';
		}
		return tara_home_cards_section( 'تازه‌ترین انتشارها', $ids, '', 'latest-releases', 'tara-sec-releases' );
	}
}

if ( ! function_exists( 'tara_home_video_section' ) ) {
	/** «موزیک‌ویدیوهای تازه» — فقط آثاری که فایل ویدیو دارند. */
	function tara_home_video_section() {
		if ( ! tara_home_section_on( 'home_sec_video' ) ) {
			return '';
		}

		/* v3.17.11 — کوئریِ گران حذف شد.
		   ------------------------------------------------------------------
		   پیش از این یک meta_query با `relation => OR` روی دو کلید و هر
		   دو `compare => EXISTS` زده می‌شد. MySQL برای چنین کوئری‌ای دو
		   INNER JOIN روی postmeta می‌سازد، بعد GROUP BY می‌زند تا ردیف‌های
		   تکراری را جمع کند؛ با این کار ایندکسِ (meta_key, post_id) هم
		   بی‌فایده می‌شود. روی سایتِ ۶٬۰۶۹ آهنگه این کوئری بخشِ `apex`
		   را به ۷۹ ثانیه رسانده بود (اندازه‌گیری‌شده روی پروداکشن، ۳.۱۷.۸).
		   روی staging با ۳۵٬۹۳۴ ردیف متا هم ۲۷۸ms بود در حالی که همهٔ
		   کوئری‌های دیگرِ همان صفحه زیر ۴۰ms بودند — ۱۰ برابر گران‌تر.

		   راهِ تازه: جست‌وجو فقط روی «کلید» انجام می‌شود، نه JOIN. هر
		   `get_posts` تک‌کلیدی است و MySQL می‌تواند از ایندکسِ meta_key
		   استفاده کند. اتحادِ نتایج در PHP انجام می‌شود (۳ آرایهٔ حداکثر
		   ۸تایی، هزینه‌اش ناچیز است).

		   نتیجه از نظر محتوا یکی است: تازه‌ترین آثاری که فایل ویدیو دارند. */
		$ids = array();
		if ( function_exists( 'tara_post_has_video' ) ) {
			$buckets = array();
			foreach ( array( 'video720', 'video1080' ) as $tara_vkey ) {
				$buckets[] = (array) tara_home_query(
					array(
						'posts_per_page' => 8,
						'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							array( 'key' => $tara_vkey, 'compare' => 'EXISTS' ),
						),
					)
				);
			}
			/* ترتیب: بر اساس تاریخ نوشته، نزولی — مثل قبل.
			   تاریخ‌ها یک‌بار خوانده می‌شوند تا usort هیچ کوئری‌ای نزند. */
			$flat = array_values( array_unique( array_merge( ...$buckets ) ) );
			if ( $flat && count( $buckets ) > 1 ) {
				$dates = array();
				foreach ( $flat as $tara_fid ) {
					$dates[ $tara_fid ] = (string) get_post_field( 'post_date', $tara_fid );
				}
				usort(
					$flat,
					function ( $a, $b ) use ( $dates ) {
						return strcmp( (string) $dates[ $b ], (string) $dates[ $a ] );
					}
				);
			}
			$ids = array_slice( $flat, 0, 8 );
			/* فیلتر دوم روی خودِ متا: EXISTS با مقدار خالی هم می‌آید */
			$ids = array_values( array_filter( $ids, 'tara_post_has_video' ) );
		}
		if ( ! $ids ) {
			return '';
		}

		return tara_home_cards_section( 'موزیک‌ویدیوهای تازه', $ids, '', 'new-videos', 'tara-sec-videos' );
	}
}

if ( ! function_exists( 'tara_home_album_section' ) ) {
	/** «آلبوم‌های تازه» — بر پایهٔ فیلد `musics_type` (فقط دادهٔ ثبت‌شده). */
	function tara_home_album_section() {
		if ( ! tara_home_section_on( 'home_sec_album' ) ) {
			return '';
		}

		$slugs = array();
		foreach ( array( 'آلبوم', 'album' ) as $label ) {
			$slugs[] = sanitize_title( $label );
		}

		$ids = tara_home_query(
			array(
				'posts_per_page' => 8,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'musics_type',
						'value'   => array( 'آلبوم', 'album', 'Album' ),
						'compare' => 'IN',
					),
				),
			)
		);

		if ( ! $ids ) {
			return '';
		}

		return tara_home_cards_section( 'آلبوم‌های تازه', $ids, '', 'new-albums', 'tara-sec-albums' );
	}
}

if ( ! function_exists( 'tara_home_mood_section' ) ) {
	/** «حال‌وهوا» — چیپ‌های ترم‌های واقعیِ دارای نوشته. */
	function tara_home_mood_section() {
		if ( ! tara_home_section_on( 'home_sec_mood' ) || ! function_exists( 'get_terms' ) ) {
			return '';
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'tara_mood',
				'hide_empty' => true,
				'number'     => 12,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		ob_start();
		?>
		<section class="song_boxs tara-home-sec tara-home-sec--moods" aria-labelledby="tara-sec-moods">
			<header class="songs_title_box">
				<h2 id="tara-sec-moods">حال‌وهوا</h2>
				<span class="liner" aria-hidden="true"></span>
			</header>
			<ul class="tara-mood-chips">
				<?php foreach ( $terms as $term ) : $term_url = get_term_link( $term ); if ( is_wp_error( $term_url ) ) { continue; } ?>
					<li><a href="<?php echo esc_url( $term_url ); ?>">
						<span class="tara-mood-chips__name"><?php echo esc_html( $term->name ); ?></span>
						<span class="tara-mood-chips__count"><?php echo esc_html( function_exists( 'tara_number_fa' ) ? tara_number_fa( number_format_i18n( (int) $term->count ) ) : number_format_i18n( (int) $term->count ) ); ?> اثر</span>
					</a></li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'tara_home_news_section' ) ) {
	/** «تازه‌ترین اخبار» — فقط نوشته‌های دستهٔ اخبار (همان منبع نوار بالای صفحه). */
	function tara_home_news_section() {
		if ( ! tara_home_section_on( 'home_sec_news' ) ) {
			return '';
		}

		$cat = function_exists( 'tara_news_category_id' ) ? (int) tara_news_category_id() : 0;
		if ( ! $cat ) {
			return '';
		}

		$items = function_exists( 'tara_news_posts' ) ? tara_news_posts( 4 ) : array();
		if ( empty( $items['items'] ) ) {
			return '';
		}

		ob_start();
		?>
		<section class="song_boxs tara-home-sec tara-home-sec--news" aria-labelledby="tara-sec-news">
			<header class="songs_title_box">
				<h2 id="tara-sec-news">تازه‌ترین اخبار</h2>
				<span class="liner" aria-hidden="true"></span>
				<a href="<?php echo esc_url( get_category_link( $cat ) ); ?>">همهٔ اخبار</a>
			</header>
			<ul class="tara-news-list">
				<?php foreach ( $items['items'] as $item ) : ?>
					<li class="tara-news-list__item">
						<a href="<?php echo esc_url( $item['url'] ); ?>">
							<?php if ( ! empty( $item['cover'] ) ) : ?>
								<img class="tara-news-list__img" src="<?php echo esc_url( $item['cover'] ); ?>" width="96" height="96" alt="" loading="lazy" decoding="async">
							<?php endif; ?>
							<span class="tara-news-list__body">
								<strong><?php echo esc_html( $item['title'] ); ?></strong>
								<time><?php echo esc_html( $item['date'] ); ?></time>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'tara_home_apex_sections' ) ) {
	/** چاپ یک‌جای بخش‌های تازه (ترتیب: ویدیو → آلبوم → حال‌وهوا → اخبار). */
	function tara_home_apex_sections() {
		echo tara_home_releases_section(); // phpcs:ignore WordPress.Security.EscapeOutput -- مارک‌آپ همین فایل
		echo tara_home_video_section(); // phpcs:ignore WordPress.Security.EscapeOutput -- مارک‌آپ همین فایل
		echo tara_home_album_section(); // phpcs:ignore WordPress.Security.EscapeOutput
		echo tara_home_mood_section();  // phpcs:ignore WordPress.Security.EscapeOutput
		echo tara_home_news_section();  // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ==========================================================================
   v3.17.5 — پاک‌کنندهٔ کشِ بخش‌های خانه
   --------------------------------------------------------------------------
   کش‌های tara_hq_* و tara_lrel_* فقط ۱۰ دقیقه عمر دارند، ولی وقتی
   نویسنده آهنگ تازه منتشر می‌کند نباید تا ۱۰ دقیقه در خانه دیده نشود.
   این تابع به‌محض انتشار/ویرایش/حذف نوشته کش‌ها را پاک می‌کند.
   ========================================================================== */
if ( ! function_exists( 'tara_home_caches_flush' ) ) {
	function tara_home_caches_flush() {
		global $wpdb;
		/* حذفِ مستقیم از جدول options سریع‌تر از پیمایشِ کلیدهاست. */
		$like_hq = $wpdb->esc_like( '_transient_tara_hq_' ) . '%';
		$like_lr = $wpdb->esc_like( '_transient_tara_lrel_' ) . '%';
		$like_t  = $wpdb->esc_like( '_transient_timeout_tara_hq_' ) . '%';
		$like_t2 = $wpdb->esc_like( '_transient_timeout_tara_lrel_' ) . '%';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
				$like_hq,
				$like_lr,
				$like_t,
				$like_t2
			)
		);
	}
}
add_action( 'save_post', 'tara_home_caches_flush', 99 );
add_action( 'deleted_post', 'tara_home_caches_flush', 99 );
add_action( 'trashed_post', 'tara_home_caches_flush', 99 );
add_action( 'edited_term', 'tara_home_caches_flush', 99 );
