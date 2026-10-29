<?php
/**
 * رادیو ترانه یار — داده، تنظیمات و نقطهٔ پایانیِ AJAX (نسخهٔ ۳.۹.۰)
 * =========================================================================
 * چه می‌کند؟
 *   • یک «ایستگاه» می‌سازد که آهنگ‌ها را پشت سر هم و **با میکس** پخش می‌کند.
 *   • منبعِ آهنگ‌ها را از قواعدِ روشن می‌گیرد:
 *       جدیدترین‌ها | پربازدیدترین‌ها | تصادفی | ترکیبیِ هوشمند
 *     و می‌تواند به یک ژانر/حال‌وهوا محدود شود.
 *   • همان صف با AJAX هم قابلِ گرفتن است (برای «بعدی»، «شافل» و بزرگ‌کردنِ
 *     صف وقتی به آخرش رسیدیم).
 *
 * چرا جدا از پلیر اصلی؟
 *   پلیرِ «رادیو» در inc/tara-ui.php یک کارتِ تک‌قطعه‌ای است و موتورِ آن
 *   (js/tara-player.js) برای پخشِ خطیِ یک فهرست نوشته شده. «میکسِ زنده»
 *   به دو المان صوتیِ هم‌زمان، کراس‌فیدِ زمان‌دار و صفِ خودتجدیدشونده نیاز
 *   دارد؛ پس موتورِ مستقل (js/tara-radio.js) دارد و کنارِ پلیرِ اصلی
 *   می‌نشیند، نه روی آن. هیچ کلاس یا شناسهٔ پلیرِ قبلی عوض نشده است.
 *
 * هیچ داده‌ای نوشته نمی‌شود، هیچ جدولی ساخته نمی‌شود و همهٔ ورودی‌های
 * AJAX اعتبارسنجی می‌شوند.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * ۰) پیش‌فرض‌ها و خواندنِ تنظیمات
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_radio_defaults' ) ) {
	/**
	 * پیش‌فرضِ همهٔ تنظیم‌های رادیو در یک جا.
	 *
	 * عدد‌ها عمداً ملایم انتخاب شده‌اند: کراس‌فید ۶ ثانیه‌ای برای بیشتر
	 * آهنگ‌های فارسی (که معمولاً ۳ تا ۴ دقیقه‌اند) حسِ «دی‌جی» می‌دهد ولی
	 * آهنگ را خفه نمی‌کند.
	 *
	 * @return array
	 */
	function tara_radio_defaults() {
		$defaults = array(
			'on'             => 'on',      // کلیدِ اصلی
			'mode'           => 'mixed',   // منبعِ پیش‌فرض: mixed|new|views|random|hot
			'xfade'          => 6,         // ثانیهٔ کراس‌فید
			'tempo'          => 'on',      // هم‌ترازیِ نرمِ سرعت (دی‌جی)
			'fx'             => 'on',      // افکتِ فیلتر/اکو در گذر (اگر CORS اجازه بدهد)
			'cue'            => 0,         // پرش از ابتدای قطعه (ثانیه)
			'limit'          => 20,        // طولِ صفِ هر نوبت
			'duck'           => 35,        // صدای موسیقی هنگامِ وکالِ ایستگاه (درصد)
			'station'        => 'on',      // وکالِ معرفیِ سایت
			'station_every'  => 3,         // هر چند آهنگ یک‌بار
			'station_text'   => 'رادیو {site}؛ پخشِ پیوستهٔ آهنگ‌های فارسی. گوش کنید.',
			'station_file'   => '',        // فایلِ صوتیِ آمادهٔ وکال (اختیاری)
			'station_names'  => 'on',      // نامیدنِ آهنگِ بعدی
			'station_next'   => 'بعدی: {title} از {artist}.',
			'autostart'      => '',        // پخشِ خودکار در صفحهٔ رادیو (مرورگرها اغلب می‌بندند)
			'home'           => 'on',      // کارتِ رادیو در صفحهٔ اصلی
			'archives'       => 'on',      // دی‌جی‌باکس در آرشیو ژانر/حال‌وهوا
		);

		return apply_filters( 'tara_radio_defaults', $defaults );
	}
}

if ( ! function_exists( 'tara_radio_option' ) ) {
	/**
	 * یک تنظیمِ رادیو با پیش‌فرض.
	 *
	 * @param string $key     شناسه.
	 * @param mixed  $default پیش‌فرضِ دلخواه (اختیاری).
	 * @return mixed
	 */
	function tara_radio_option( $key, $default = null ) {
		$defaults = tara_radio_defaults();
		if ( null === $default ) {
			$default = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
		}

		$value = function_exists( 'ot_get_option' ) ? ot_get_option( 'radio_' . $key, $default ) : $default;
		if ( is_array( $value ) ) {
			$value = '';
		}
		if ( '' === $value || null === $value ) {
			return $default;
		}

		return $value;
	}
}

if ( ! function_exists( 'tara_radio_on' ) ) {
	/**
	 * آیا رادیو روشن است؟ (کلیدِ on/off را مثل بقیهٔ قالب می‌فهمد)
	 *
	 * @return bool
	 */
	function tara_radio_on() {
		$value = tara_radio_option( 'on', 'on' );
		if ( function_exists( 'tara_opt_on' ) ) {
			return (bool) tara_opt_on( 'radio_on', true );
		}

		return ! in_array( strtolower( (string) $value ), array( 'off', '0', 'false', 'no' ), true );
	}
}

if ( ! function_exists( 'tara_radio_modes' ) ) {
	/**
	 * نامِ فارسیِ منبع‌ها — هم برای رادیو و هم برای پنل.
	 *
	 * @return array<string,string>
	 */
	function tara_radio_modes() {
		return array(
			'mixed'  => 'ترکیبیِ هوشمند',
			'new'    => 'جدیدترین‌ها',
			'views'  => 'پربازدیدترین‌ها',
			'random' => 'تصادفی',
			'hot'    => 'داغِ این فصل',
		);
	}
}

if ( ! function_exists( 'tara_radio_settings' ) ) {
	/**
	 * تنظیم‌های مؤثرِ رادیو برای مرورگر (JSON).
	 *
	 * @return array
	 */
	function tara_radio_settings() {
		$modes = tara_radio_modes();
		$mode  = (string) tara_radio_option( 'mode', 'mixed' );
		if ( ! isset( $modes[ $mode ] ) ) {
			$mode = 'mixed';
		}

		$xfade = max( 0, min( 20, (int) tara_radio_option( 'xfade', 6 ) ) );
		$limit = max( 4, min( 40, (int) tara_radio_option( 'limit', 20 ) ) );

		$settings = array(
			'mode'      => $mode,
			'xfade'     => $xfade,
			'tempo'     => ( 'on' === (string) tara_radio_option( 'tempo', 'on' ) ),
			'fx'        => ( 'on' === (string) tara_radio_option( 'fx', 'on' ) ),
			'cue'       => max( 0, min( 30, (int) tara_radio_option( 'cue', 0 ) ) ),
			'limit'     => $limit,
			'duck'      => max( 0, min( 90, (int) tara_radio_option( 'duck', 35 ) ) ) / 100,
			'autostart' => ( 'on' === (string) tara_radio_option( 'autostart', '' ) ),
			'site'      => (string) get_bloginfo( 'name' ),
			'station'   => array(
				'on'    => ( 'on' === (string) tara_radio_option( 'station', 'on' ) ),
				'every' => max( 1, min( 12, (int) tara_radio_option( 'station_every', 3 ) ) ),
				'text'  => (string) tara_radio_option( 'station_text' ),
				'next'  => (string) tara_radio_option( 'station_next' ),
				'names' => ( 'on' === (string) tara_radio_option( 'station_names', 'on' ) ),
				'file'  => esc_url_raw( (string) tara_radio_option( 'station_file', '' ) ),
			),
			'labels'    => array(
				'play'   => 'پخش رادیو',
				'pause'  => 'توقف',
				'buffer' => 'در حال آماده‌سازی…',
				'empty'  => 'آهنگی برای این بخش پیدا نشد.',
				'added'  => 'به رادیو اضافه شد.',
				'inMine' => 'از قبل در فهرست شما بود.',
				'mine'   => 'فهرست من',
				'station' => 'رادیو ترانه یار',
			),
		);

		return apply_filters( 'tara_radio_settings', $settings );
	}
}

/* -------------------------------------------------------------------------
 * ۱) استخراجِ آهنگ‌ها
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_radio_duration' ) ) {
	/**
	 * مدتِ آهنگ به ثانیه (۰ = نامعلوم).
	 *
	 * @param int $post_id شناسه.
	 * @return int
	 */
	function tara_radio_duration( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return 0;
		}

		foreach ( array( 'duration', 'music_duration', 'song_duration' ) as $key ) {
			$raw = get_post_meta( $post_id, $key, true );
			if ( is_numeric( $raw ) ) {
				return max( 0, (int) $raw );
			}
			if ( is_string( $raw ) && preg_match( '/^(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?$/', trim( $raw ), $m ) ) {
				$parts = array_reverse( array_slice( $m, 1 ) );
				$sec   = 0;
				foreach ( $parts as $i => $piece ) {
					$sec += (int) $piece * pow( 60, $i );
				}
				return $sec;
			}
		}

		return 0;
	}
}

if ( ! function_exists( 'tara_radio_track' ) ) {
	/**
	 * یک آهنگ به شکلِ آمادهٔ JSON.
	 *
	 * @param int $post_id شناسه.
	 * @return array|null
	 */
	function tara_radio_track( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id || ! function_exists( 'tara_track_src' ) ) {
			return null;
		}

		$src = tara_track_src( $post_id );
		if ( '' === $src ) {
			return null;
		}

		return array(
			'id'       => $post_id,
			'title'    => (string) tara_track_title( $post_id ),
			'artist'   => (string) tara_track_artist( $post_id ),
			'src'      => (string) $src,
			'cover'    => (string) tara_track_cover( $post_id, 'thumb2' ),
			'link'     => (string) get_permalink( $post_id ),
			'duration' => (int) tara_radio_duration( $post_id ),
		);
	}
}

if ( ! function_exists( 'tara_radio_query_ids' ) ) {
	/**
	 * شناسه‌های یک «منبع» را برمی‌گرداند (بدونِ فیلترِ فایل صوتی).
	 *
	 * @param string $mode    new|views|random|hot.
	 * @param array  $args    taxonomy/term_id/exclude/limit.
	 * @return int[]
	 */
	function tara_radio_query_ids( $mode, $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'taxonomy' => '',
				'term_id'  => 0,
				'exclude'  => array(),
				'limit'    => 20,
			)
		);

		$limit = max( 1, min( 60, (int) $args['limit'] ) );
		$query = array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
			'post__not_in'           => array_map( 'absint', (array) $args['exclude'] ),
		);

		if ( '' !== $args['taxonomy'] && (int) $args['term_id'] > 0 && taxonomy_exists( $args['taxonomy'] ) ) {
			$query['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery -- لازم است
				array(
					'taxonomy' => $args['taxonomy'],
					'field'    => 'term_id',
					'terms'    => (int) $args['term_id'],
				),
			);
		}

		switch ( $mode ) {
			case 'views':
				$query['meta_key'] = 'views'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$query['orderby']  = 'meta_value_num';
				$query['order']    = 'DESC';
				break;

			case 'random':
				$query['orderby'] = 'rand';
				break;

			case 'hot':
				/* «داغِ این فصل» = پربازدید در ۱۸۰ روزِ گذشته. اگر آن‌قدر
				   آهنگِ تازه نداشته باشیم، پرکردنِ صف با جدیدترین‌ها انجام
				   می‌شود (در تابعِ اصلی). */
				$query['meta_key']   = 'views'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$query['orderby']    = 'meta_value_num';
				$query['order']      = 'DESC';
				$query['date_query'] = array(
					array(
						'after'     => '180 days ago',
						'inclusive' => true,
					),
				);
				break;

			case 'new':
			default:
				$query['orderby'] = 'date';
				$query['order']   = 'DESC';
				break;
		}

		$ids = get_posts( $query );

		return array_values( array_map( 'absint', (array) $ids ) );
	}
}

if ( ! function_exists( 'tara_radio_cache_key' ) ) {
	/** کلیدِ کشِ سبکِ نتایج (فقط برای منبع‌های غیرِ تصادفی). */
	function tara_radio_cache_key( $args ) {
		ksort( $args );
		return 'tara_radio_' . md5( (string) wp_json_encode( $args ) );
	}
}

if ( ! function_exists( 'tara_radio_ids' ) ) {
	/**
	 * صفِ نهاییِ شناسه‌ها با ترکیبِ منابع.
	 *
	 * @param array $args taxonomy/term_id/mode/limit/exclude.
	 * @return int[]
	 */
	function tara_radio_ids( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'taxonomy' => '',
				'term_id'  => 0,
				'mode'     => 'mixed',
				'limit'    => 20,
				'exclude'  => array(),
			)
		);

		$modes = tara_radio_modes();
		if ( ! isset( $modes[ $args['mode'] ] ) ) {
			$args['mode'] = 'mixed';
		}

		$limit   = max( 1, min( 40, (int) $args['limit'] ) );
		$exclude = array_map( 'absint', (array) $args['exclude'] );
		$cache   = ( 'random' !== $args['mode'] );

		$cache_args = array(
			'tax'     => (string) $args['taxonomy'],
			'term'    => (int) $args['term_id'],
			'mode'    => (string) $args['mode'],
			'limit'   => $limit,
			'exclude' => $exclude,
		);

		if ( $cache ) {
			$cached = get_transient( tara_radio_cache_key( $cache_args ) );
			if ( is_array( $cached ) ) {
				return array_map( 'absint', $cached );
			}
		}

		/* سه برابر می‌گیریم، چون آهنگ‌های بدونِ فایل صوتی حذف می‌شوند و
		   باید صفِ نهایی همچنان پر باشد. */
		$fetch = min( 60, $limit * 3 );

		$pick = array();
		if ( 'mixed' === $args['mode'] ) {
			$half = (int) ceil( $fetch / 2 );
			$new  = tara_radio_query_ids( 'new', array_merge( $args, array( 'limit' => $half ) ) );
			$top  = tara_radio_query_ids( 'views', array_merge( $args, array( 'limit' => $half ) ) );
			/* چیدمانِ یک‌درمیان: نو / پربازدید / نو / پربازدید… تنوعِ صدا
			   بالا می‌رود و دو آهنگِ هم‌زمان از یک خواننده پشتِ هم نمی‌آید. */
			$max = max( count( $new ), count( $top ) );
			for ( $i = 0; $i < $max; $i++ ) {
				if ( isset( $new[ $i ] ) ) {
					$pick[] = $new[ $i ];
				}
				if ( isset( $top[ $i ] ) ) {
					$pick[] = $top[ $i ];
				}
			}
		} else {
			$pick = tara_radio_query_ids( $args['mode'], array_merge( $args, array( 'limit' => $fetch ) ) );
		}

		$pick = array_values( array_unique( array_map( 'absint', $pick ) ) );

		/* اگر «داغ» یا منبعِ انتخابی کم آورد، با جدیدترین‌ها پر می‌کنیم. */
		if ( count( $pick ) < $fetch ) {
			$fill = tara_radio_query_ids(
				'new',
				array_merge(
					$args,
					array(
						'limit'   => $fetch - count( $pick ),
						'exclude' => array_merge( $exclude, $pick ),
					)
				)
			);
			$pick = array_values( array_unique( array_merge( $pick, $fill ) ) );
		}

		/* فقط آهنگ‌هایی که واقعاً فایلِ صوتی دارند، تا سقفِ خواسته‌شده. */
		$ids = array();
		foreach ( $pick as $post_id ) {
			if ( count( $ids ) >= $limit ) {
				break;
			}
			if ( function_exists( 'tara_track_src' ) && '' !== tara_track_src( $post_id ) ) {
				$ids[] = (int) $post_id;
			}
		}

		if ( $cache ) {
			set_transient( tara_radio_cache_key( $cache_args ), $ids, 3 * MINUTE_IN_SECONDS );
		}

		return $ids;
	}
}

if ( ! function_exists( 'tara_radio_tracks' ) ) {
	/**
	 * صفِ آمادهٔ پخش (آرایهٔ آهنگ‌ها).
	 *
	 * @param array $args taxonomy/term_id/mode/limit/exclude.
	 * @return array
	 */
	function tara_radio_tracks( $args = array() ) {
		$args   = wp_parse_args(
			$args,
			array(
				'taxonomy' => '',
				'term_id'  => 0,
				'mode'     => (string) tara_radio_option( 'mode', 'mixed' ),
				'limit'    => (int) tara_radio_option( 'limit', 20 ),
				'exclude'  => array(),
			)
		);
		$tracks = array();

		foreach ( tara_radio_ids( $args ) as $post_id ) {
			$track = tara_radio_track( $post_id );
			if ( $track ) {
				$tracks[] = $track;
			}
		}

		return $tracks;
	}
}

/* -------------------------------------------------------------------------
 * ۲) نقطهٔ پایانیِ AJAX (برای بعدی/شافل/بزرگ‌کردنِ صف)
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_radio_ajax' ) ) {
	/**
	 * خروجی JSON: صفِ تازه برای همین بخش/منبع.
	 *
	 * ورودی‌ها همه اعتبارسنجی می‌شوند: نامکِ تاکسونومی فقط از فهرستِ خودِ
	 * قالب پذیرفته می‌شود، شناسهٔ ترم باید واقعاً وجود داشته باشد، منبع از
	 * پنج مقدارِ مجاز و سقفِ تعداد ۴۰ است.
	 */
	function tara_radio_ajax() {
		if ( ! check_ajax_referer( 'tara_radio', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'نشست منقضی شده است. صفحه را دوباره باز کنید.' ), 403 );
		}

		$taxonomy = isset( $_GET['tax'] ) ? sanitize_key( wp_unslash( $_GET['tax'] ) ) : '';
		$allowed  = function_exists( 'tara_music_taxonomy_ids' ) ? tara_music_taxonomy_ids() : array();
		if ( '' !== $taxonomy && ! in_array( $taxonomy, $allowed, true ) ) {
			$taxonomy = '';
		}

		$term_id = isset( $_GET['term'] ) ? absint( $_GET['term'] ) : 0;
		if ( $term_id ) {
			$term = get_term( $term_id );
			if ( ! $term || is_wp_error( $term ) || ( '' !== $taxonomy && $term->taxonomy !== $taxonomy ) ) {
				$term_id  = 0;
				$taxonomy = '';
			}
		}

		$mode   = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : 'mixed';
		$modes  = tara_radio_modes();
		if ( ! isset( $modes[ $mode ] ) ) {
			$mode = 'mixed';
		}

		$limit = isset( $_GET['limit'] ) ? absint( $_GET['limit'] ) : (int) tara_radio_option( 'limit', 20 );
		$limit = max( 4, min( 40, $limit ) );

		$exclude = array();
		if ( isset( $_GET['exclude'] ) ) {
			$raw = wp_unslash( $_GET['exclude'] ); // phpcs:ignore WordPress.Security -- پاک‌سازی پایین‌تر
			if ( is_array( $raw ) ) {
				$exclude = array_slice( array_map( 'absint', $raw ), 0, 120 );
			} elseif ( is_string( $raw ) && '' !== $raw ) {
				$exclude = array_slice( array_map( 'absint', explode( ',', $raw ) ), 0, 120 );
			}
		}

		$tracks = tara_radio_tracks(
			array(
				'taxonomy' => $taxonomy,
				'term_id'  => $term_id,
				'mode'     => $mode,
				'limit'    => $limit,
				'exclude'  => $exclude,
			)
		);

		wp_send_json_success(
			array(
				'tracks' => $tracks,
				'mode'   => $mode,
				'tax'    => $taxonomy,
				'term'   => $term_id,
				'empty'  => empty( $tracks ),
			)
		);
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_ajax_tara_radio', 'tara_radio_ajax' );
	add_action( 'wp_ajax_nopriv_tara_radio', 'tara_radio_ajax' );
}

/* -------------------------------------------------------------------------
 * ۳) آیا این صفحه رادیو دارد؟ + بارگذاریِ دارایی‌ها
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_radio_is_tax_page' ) ) {
	/** آرشیو ژانر یا حال‌وهوا؟ */
	function tara_radio_is_tax_page() {
		if ( ! function_exists( 'is_tax' ) ) {
			return false;
		}

		return is_tax( 'tara_genre' ) || is_tax( 'tara_mood' );
	}
}

if ( ! function_exists( 'tara_radio_page_wanted' ) ) {
	/** آیا در این صفحه دی‌جی‌باکس/رادیو نمایش داده می‌شود؟ */
	function tara_radio_page_wanted() {
		if ( ! tara_radio_on() ) {
			return false;
		}

		if ( tara_radio_is_tax_page() && 'on' === (string) tara_radio_option( 'archives', 'on' ) ) {
			return true;
		}

		if ( function_exists( 'is_page_template' ) && is_page_template( 'page-radio.php' ) ) {
			return true;
		}

		if ( function_exists( 'tara_page_is_home' ) && tara_page_is_home() && 'on' === (string) tara_radio_option( 'home', 'on' ) ) {
			return true;
		}

		if ( function_exists( 'is_singular' ) && is_singular() && function_exists( 'has_shortcode' ) ) {
			/* `get_queried_object_id()` در صفحهٔ نوشته همان نوشته را می‌دهد و
			   در جای دیگر صفر است؛ پس این‌جا هیچ‌وقت به «نوشتهٔ جاریِ حلقه»
			   (که در آرشیوها معنای دیگری دارد) تکیه نمی‌کنیم. */
			$post_id = function_exists( 'get_queried_object_id' ) ? (int) get_queried_object_id() : 0;
			$post    = $post_id ? get_post( $post_id ) : null;
			if ( $post && has_shortcode( (string) $post->post_content, 'tara_radio' ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'tara_radio_assets' ) ) {
	/** CSS/JS رادیو — فقط در صفحه‌هایی که واقعاً لازم است. */
	function tara_radio_assets() {
		if ( ! tara_radio_page_wanted() ) {
			return;
		}

		$css = TARANEHYAR_DIR . '/css/tara-radio.css';
		if ( file_exists( $css ) ) {
			$rel = function_exists( 'tara_css_rel' ) ? tara_css_rel( '/css/tara-radio.css' ) : '/css/tara-radio.css';
			$ver = function_exists( 'taranehyar_asset_ver' ) ? taranehyar_asset_ver( $rel ) : ( defined( 'TARANEHYAR_VERSION' ) ? TARANEHYAR_VERSION : '0' );
			wp_enqueue_style(
				'taranehyar-radio',
				TARANEHYAR_URI . $rel,
				array( 'taranehyar-ui' ),
				$ver
			);
		}

		$js = TARANEHYAR_DIR . '/js/tara-radio.js';
		if ( ! file_exists( $js ) ) {
			return;
		}

		wp_enqueue_script(
			'taranehyar-radio',
			TARANEHYAR_URI . '/js/tara-radio.js',
			array(),
			function_exists( 'taranehyar_asset_ver' ) ? taranehyar_asset_ver( '/js/tara-radio.js' ) : ( defined( 'TARANEHYAR_VERSION' ) ? TARANEHYAR_VERSION : '0' ),
			true
		);

		wp_localize_script(
			'taranehyar-radio',
			'TaraRadio',
			array(
				'api'    => admin_url( 'admin-ajax.php' ),
				'nonce'  => wp_create_nonce( 'tara_radio' ),
				'config' => tara_radio_settings(),
			)
		);
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_enqueue_scripts', 'tara_radio_assets', 30 );
}

/* -------------------------------------------------------------------------
 * ۴) «افزودن به رادیو» — دکمه‌ای روی صفحهٔ آهنگ
 * ----------------------------------------------------------------------
 * فهرستِ شخصی در localStorage مرورگرِ خودِ کاربر می‌ماند (بدونِ حساب
 * کاربری، بدونِ دیتابیس). رادیو اول همین فهرست را می‌خواند و بعد ادامهٔ
 * ایستگاه را.
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_radio_add_button' ) ) {
	/**
	 * دکمهٔ «افزودن به رادیو» برای یک آهنگ.
	 *
	 * @param int   $post_id شناسه.
	 * @param array $args    class/label.
	 * @return string
	 */
	function tara_radio_add_button( $post_id = 0, $args = array() ) {
		if ( ! tara_radio_on() ) {
			return '';
		}

		$track = tara_radio_track( $post_id ? (int) $post_id : (int) get_the_ID() );
		if ( ! $track ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'class' => 'ty-radio-add',
				'label' => 'افزودن به رادیو',
			)
		);

		$json = wp_json_encode( $track, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP );
		if ( ! is_string( $json ) ) {
			return '';
		}

		return '<button type="button" class="' . esc_attr( $args['class'] ) . '" data-tara-radio-add data-radio-track="'
			. esc_attr( $json ) . '" aria-label="' . esc_attr( $args['label'] . ' — ' . $track['title'] ) . '">'
			. '<span class="ty-radio-add__ico" aria-hidden="true"></span>'
			. '<span class="ty-radio-add__txt">' . esc_html( $args['label'] ) . '</span>'
			. '</button>';
	}
}
