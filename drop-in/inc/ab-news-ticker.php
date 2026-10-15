<?php
/**
 * جوان‌صدا — «اخبار روز» (جایگزین نوار دسترسی سریع زیر هدر) — نسخهٔ 3.1.2
 * =========================================================================
 * این فایل تنها «تولید» می‌کند؛ هیچ هوکی ثبت نمی‌کند، چیزی ذخیره نمی‌کند و
 * در خطِ رندرِ صفحاتِ دیگر دخالت ندارد. فراخوانی آن اختیاری است:
 *   - اگر myfunctions.php اعمال نشده، خودِ home.php هم همین فایل را require می‌کند.
 *   - اگر فایل نبود، home.php به همان نوارِ سه‌تاییِ قدیمی برمی‌گردد (بدون خطا).
 *
 * منبع داده: تازه‌ترین نوشته‌های «دستهٔ اخبار» با slug = news (یا id عددی).
 *   قابل تنظیم با define در wp-config.php (یا بالای functions.php):
 *     define( 'JAVANSEDA_NEWS_CATEGORY', 'news' );  // slug یا id دسته
 *     define( 'JAVANSEDA_NEWS_COUNT', 8 );          // تعداد خبر در نوار
 *     define( 'JAVANSEDA_NEWS_CACHE', 300 );        // ثانیه؛ ۰ = بدون کش
 *   اگر دسته‌ای با آن slug/id پیدا نشود، به‌جای خطا، «تازه‌ترین نوشته‌ها» نمایش داده
 *   می‌شود و در کامنت HTML تذکر داده می‌شود.
 *
 * پالت سازمانی: #FFFFFF / #185ADB / #111111 — بدون هیچ گرادینت.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'javan_seda_news_category_id' ) ) {
	/** شناسهٔ گویای دستهٔ اخبار (slug یا id). @return int|0 */
	function javan_seda_news_category_id() {
		$raw = defined( 'JAVANSEDA_NEWS_CATEGORY' ) ? JAVANSEDA_NEWS_CATEGORY : 'news';
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return 0;
		}
		if ( ctype_digit( $raw ) ) {
			$term = get_term( (int) $raw, 'category' );
			return ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
		}
		$term = get_term_by( 'slug', $raw, 'category' );
		if ( ! $term ) {
			$term = get_term_by( 'name', $raw, 'category' );
		}
		return ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
	}
}

if ( ! function_exists( 'javan_seda_news_posts' ) ) {
	/**
	 * تازه‌ترین خبرها.
	 * @return array list( array( 'id','title','url','date' ) ) + کلیدهای meta
	 */
	function javan_seda_news_posts( $limit = 0 ) {
		$limit = $limit > 0 ? (int) $limit : ( defined( 'JAVANSEDA_NEWS_COUNT' ) ? (int) JAVANSEDA_NEWS_COUNT : 8 );
		$limit = max( 1, min( 20, $limit ) );
		$cat   = javan_seda_news_category_id();
		$ttl   = defined( 'JAVANSEDA_NEWS_CACHE' ) ? (int) JAVANSEDA_NEWS_CACHE : 300;

		$ck = 'javan_seda_news_' . $cat . '_' . $limit;
		if ( $ttl > 0 ) {
			$hit = get_transient( $ck );
			if ( is_array( $hit ) ) {
				return $hit;
			}
		}

		$args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'orderby'             => 'date',
			'order'               => 'DESC',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		if ( $cat ) {
			$args['cat'] = $cat;
		}

		$q     = new WP_Query( $args );
		$items = array(
			'items'   => array(),
			'cat_id'  => $cat,
			'fallback'=> ( $cat ? 0 : 1 ), // ۱ = دستهٔ اخبار پیدا نشد
		);
		if ( $q->have_posts() ) {
			while ( $q->have_posts() ) {
				$q->the_post();
				$items['items'][] = array(
					'id'    => get_the_ID(),
					'title' => get_the_title(),
					'url'   => get_permalink(),
					'date'  => get_the_date( 'j F' ),
				);
			}
		}
		wp_reset_postdata();

		// اگر دستهٔ اخبار مطلبی نداشت، آخرین نوشته‌ها نمایش داده می‌شود
		if ( $cat && ! $items['items'] ) {
			unset( $args['cat'] );
			$q2 = new WP_Query( $args );
			while ( $q2->have_posts() ) {
				$q2->the_post();
				$items['items'][] = array(
					'id'    => get_the_ID(),
					'title' => get_the_title(),
					'url'   => get_permalink(),
					'date'  => get_the_date( 'j F' ),
				);
			}
			wp_reset_postdata();
			$items['fallback'] = 2;
		}

		if ( $ttl > 0 && $items['items'] ) {
			set_transient( $ck, $items, $ttl );
		}
		return $items;
	}
}

if ( ! function_exists( 'javan_seda_news_ticker' ) ) {
	/**
	 * چاپِ نوار «اخبار روز».
	 * @param bool $echo  false = رشته بده (برای استفاده در هر قالب/بلاک/شورت‌کد)
	 * @return string
	 */
	function javan_seda_news_ticker( $echo = true, $count = 0 ) {
		$data  = javan_seda_news_posts( $count );
		$items = ! empty( $data['items'] ) ? $data['items'] : array();

		if ( ! $items ) {
			$out = '<!-- javan_seda: news ticker — مطلبی در دستهٔ اخبار نیست -->';
			if ( $echo ) {
				echo $out;
				return '';
			}
			return $out;
		}

		$cat_id = ! empty( $data['cat_id'] ) ? (int) $data['cat_id'] : 0;
		$more   = $cat_id ? get_category_link( $cat_id ) : home_url( '/' );
		$label = 'اخبار روز';
		if ( $cat_id ) {
			$t = get_term( $cat_id, 'category' );
			if ( $t && ! is_wp_error( $t ) && ! empty( $t->name ) ) {
				$label = (string) $t->name;
			}
		}

		$one = function ( $it ) {
			return '<li class="ab-quickbar__item ab-news-item">'
				. '<a class="ab-quickbar__link ab-news-link" href="' . esc_url( $it['url'] ) . '" title="' . esc_attr( $it['title'] ) . '">'
				. '<span class="ab-news-date">' . esc_html( $it['date'] ) . '</span>'
				. '<span class="ab-news-title">' . esc_html( $it['title'] ) . '</span>'
				. '</a></li>';
		};

		$track = '';
		foreach ( $items as $it ) {
			$track .= $one( $it );
		}
		/* نسخهٔ دوم فقط برای حرکتِ بی‌درز (marquee) در دسکتاپ — برای صفحه‌خوان پنهان */
		$dup = count( $items ) > 3 ? $track : '';

		$style = ' style="--ab-news-n:' . (int) count( $items ) . ';"';

		$out = '<nav class="ab-quickbar ab-news" id="ab-news" aria-label="اخبار روز"' . $style . '>'
			. '<div class="ab-quickbar__list ab-news-bar">'
			. '<span class="ab-news-title-label"><i class="icofont-newspaper" aria-hidden="true"></i> اخبار روز</span>'
			. '<div class="ab-news-viewport">'
			. '<ul class="ab-news-track">' . $track . $dup . '</ul>'
			. '</div>'
			. ( $more ? '<a class="ab-news-more" href="' . esc_url( $more ) . '">همهٔ اخبار <i class="icofont-arrow-left" aria-hidden="true"></i></a>' : '' )
			. '</div>'
			. '<!-- javan_seda: news ticker | cat=' . ( $cat_id ? $cat_id : '(هیچ→آخرین نوشته‌ها)' ) . ' | ' . count( $items ) . ' خبر -->'
			. '</nav>';

		if ( $echo ) {
			echo $out;
			return '';
		}
		return $out;
	}
}

/** شورت‌کد اختیاری: [ab_news count="6"] — برای گذاشتن اخبار در هر برگه/ابزارک. */
if ( ! function_exists( 'javan_seda_news_shortcode' ) ) {
	function javan_seda_news_shortcode( $atts = array() ) {
		$a = shortcode_atts( array( 'count' => 0 ), (array) $atts, 'ab_news' );
		return javan_seda_news_ticker( false, (int) $a['count'] );
	}
	if ( function_exists( 'add_shortcode' ) ) {
		add_shortcode( 'ab_news', 'javan_seda_news_shortcode' );
	}
}
