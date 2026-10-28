<?php
/**
 * جوان‌صدا ۳.۵.۴ — دیدگاه‌ها: صفحه‌بندی، سرتیتر و ناوبریِ حرفه‌ای
 * =============================================================================
 * مسئله: با زیادشدنِ دیدگاه‌ها، صفحۀ نوشته بی‌نهایت بلند می‌شد؛ چون وردپرس به‌طور
 * پیش‌فرض صفحه‌بندیِ دیدگاه را خاموش دارد (`page_comments = 0`) و قالب هم فقط دو
 * لینکِ سادهٔ «قبلی/بعدی» داشت که هیچ‌وقت دیده نمی‌شد.
 *
 * راه‌حل (بدون دست‌زدن به هسته و بدون هیچ تغییری در دیتابیس):
 *   ۱) صفحه‌بندیِ استانداردِ خودِ وردپرس روی نمای فرانت روشن می‌شود
 *      (`page_comments` + `comments_per_page`)، پس همان مکانیزمِ آزمودهٔ هسته
 *      (cpage، comment-page-N، پیوندهای یکتا، شمارش) کار می‌کند.
 *   ۲) تعداد دیدگاه در هر صفحه از «تنظیمات قالب → کنترل رابط» می‌آید (پیش‌فرض ۲۰).
 *   ۳) ناوبریِ تازه: شمارۀ صفحه‌ها با `aria-current`، «قدیمی‌تر/جدیدتر»، `rel`،
 *      شمارۀ فارسی و یک خط خلاصه («نمایش دیدگاه‌های ۲۱ تا ۴۰ از ۱۲۳ — صفحهٔ ۲ از ۷»).
 *   ۴) همه‌چیز با JS خاموش هم کار می‌کند؛ هیچ محتوایی حذف/بازنویسی نمی‌شود.
 *
 * چرا فیلترِ `pre_option_*` و نه تغییرِ دیتابیس؟
 *   چون نوشتن در تنظیماتِ وردپرس بدون اجنازهٔ مدیر انجام نمی‌شود. این لایه فقط
 *   «مقدارِ خوانده‌شده» را در فرانت جایگزین می‌کند و در پیشخوان/آجاکس/REST کاری
 *   نمی‌کند، پس صفحۀ «تنظیمات → گفتگو» همیشه مقدارِ واقعیِ دیتابیس را نشان می‌دهد.
 *
 * خاموش‌کردنِ کامل (بدون تغییر کد):
 *   add_filter( 'javanseda_comment_paging', '__return_false' );
 *   یا در wp-config.php:  define( 'JAVANSEDA_COMMENT_PAGING', false );
 * تغییرِ تعداد در کد:  add_filter( 'javanseda_comments_per_page', function(){ return 30; } );
 * =============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'JAVANSEDA_COMMENTS' ) && false === JAVANSEDA_COMMENTS ) {
	return;
}

if ( ! function_exists( 'javan_seda_comment_paging_on' ) ) {
	/**
	 * آیا صفحه‌بندیِ دیدگاه‌ها روشن است؟
	 *
	 * اولویت: define() در wp-config → تنظیمات قالب → پیش‌فرضِ روشن.
	 * فیلترِ `javanseda_comment_paging` آخرین حرف را می‌زند.
	 *
	 * @return bool
	 */
	function javan_seda_comment_paging_on() {
		$on = true;
		if ( function_exists( 'javan_seda_ui_on' ) ) {
			$on = (bool) javan_seda_ui_on( 'ab_comments_paging', true, 'JAVANSEDA_COMMENT_PAGING' );
		} elseif ( defined( 'JAVANSEDA_COMMENT_PAGING' ) ) {
			$on = (bool) JAVANSEDA_COMMENT_PAGING;
		}
		return (bool) apply_filters( 'javanseda_comment_paging', $on );
	}
}

if ( ! function_exists( 'javan_seda_comments_per_page' ) ) {
	/**
	 * تعداد دیدگاه در هر صفحه (۵ تا ۱۰۰؛ پیش‌فرض ۲۰).
	 *
	 * @return int
	 */
	function javan_seda_comments_per_page() {
		static $busy = false;

		/* گاردِ بازگشت: خواندنِ تنظیمات قالب خودش get_option صدا می‌زند و همین فیلتر
		   را دوباره فعال می‌کند ⇒ در بازگشت باید مقدارِ امن بدهیم، نه حلقهٔ بی‌پایان.
		   (عمداً کشِ مقدار نداریم تا فیلترها همیشه اثر کنند؛ خودِ ot_get_option کش است.) */
		if ( $busy ) {
			return 20;
		}
		$busy = true;

		$value = 20;
		if ( function_exists( 'javan_seda_ui_num' ) ) {
			$value = (int) javan_seda_ui_num( 'ab_comments_per_page', 20, 5, 100 );
		}
		$value = (int) apply_filters( 'javanseda_comments_per_page', $value );
		$value = max( 5, min( 200, $value ) );

		$busy = false;
		return $value;
	}
}

if ( ! function_exists( 'javan_seda_comment_paging_context' ) ) {
	/**
	 * آیا در بستری هستیم که باید صفحه‌بندیِ فرانت اعمال شود؟
	 * (پیشخوان، آجاکس، REST و کرون دست‌نخورده می‌مانند.)
	 *
	 * @return bool
	 */
	function javan_seda_comment_paging_context() {
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return false;
		}
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return false;
		}
		if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
			return false;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'javan_seda_comments_force_paging' ) ) {
	/** فیلترِ pre_option_page_comments: فقط در فرانت و فقط اگر گزینهٔ قالب روشن باشد. */
	function javan_seda_comments_force_paging( $pre ) {
		if ( ! javan_seda_comment_paging_context() || ! javan_seda_comment_paging_on() ) {
			return $pre;
		}
		return '1';
	}
	add_filter( 'pre_option_page_comments', 'javan_seda_comments_force_paging' );
}

if ( ! function_exists( 'javan_seda_comments_force_per_page' ) ) {
	/** فیلترِ pre_option_comments_per_page: همان عددی که در تنظیمات قالب است. */
	function javan_seda_comments_force_per_page( $pre ) {
		if ( ! javan_seda_comment_paging_context() || ! javan_seda_comment_paging_on() ) {
			return $pre;
		}
		return (string) javan_seda_comments_per_page();
	}
	add_filter( 'pre_option_comments_per_page', 'javan_seda_comments_force_per_page' );
}

if ( ! function_exists( 'javan_seda_comments_stats' ) ) {
	/**
	 * آمارِ صفحه‌بندیِ دیدگاه‌های یک نوشته.
	 *
	 * @param int $post_id شناسهٔ نوشته (۰ = نوشتهٔ جاری).
	 * @return array total, per_page, pages, current, from, to
	 */
	function javan_seda_comments_stats( $post_id = 0 ) {
		static $memo = array();

		$post_id = $post_id ? (int) $post_id : ( function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0 );

		$per   = javan_seda_comments_per_page();
		$total = function_exists( 'get_comments_number' ) ? max( 0, (int) get_comments_number( $post_id ) ) : 0;
		$pages = max( 1, (int) ceil( $total / max( 1, $per ) ) );

		/* کلیدِ حافظه شاملِ عددِ کل و صفحه است تا اگر در همان درخواست چیزی عوض شد
		   (مثلاً یک افزونه دیدگاه تازه شمرد) عددِ کهنه تحویل نشود. */
		$cache_key = $post_id . '|' . $total . '|' . $per;
		if ( isset( $memo[ $cache_key ] ) ) {
			return $memo[ $cache_key ];
		}

		$current = function_exists( 'get_query_var' ) ? (int) get_query_var( 'cpage' ) : 0;
		if ( $current < 1 ) {
			$default = function_exists( 'get_option' ) ? (string) get_option( 'default_comments_page', 'newest' ) : 'newest';
			$current = ( 'oldest' === $default ) ? 1 : $pages;
		}
		$current = max( 1, min( $pages, $current ) );

		$from = $total > 0 ? ( ( $current - 1 ) * $per ) + 1 : 0;
		$to   = min( $total, $current * $per );

		$memo[ $cache_key ] = array(
			'total'    => $total,
			'per_page' => $per,
			'pages'    => $pages,
			'current'  => $current,
			'from'     => $from,
			'to'       => $to,
		);
		return $memo[ $cache_key ];
	}
}

if ( ! function_exists( 'javan_seda_comment_number' ) ) {
	/** عددِ فارسی با جداکنندهٔ هزارگان (مستقل از بقیهٔ لایه‌ها). */
	function javan_seda_comment_number( $number ) {
		$number = max( 0, (int) $number );
		if ( function_exists( 'javan_seda_number_fa' ) ) {
			return javan_seda_number_fa( $number );
		}
		$text = function_exists( 'number_format_i18n' ) ? number_format_i18n( $number ) : number_format( $number );
		if ( function_exists( 'javan_seda_digits' ) ) {
			return javan_seda_digits( $text );
		}
		return str_replace(
			array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
			array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
			$text
		);
	}
}

if ( ! function_exists( 'javan_seda_comments_summary' ) ) {
	/**
	 * خطِ خلاصهٔ بالای ناوبری.
	 *
	 * @param array $stats خروجیِ javan_seda_comments_stats().
	 * @return string متنِ ساده (بدون HTML).
	 */
	function javan_seda_comments_summary( $stats ) {
		if ( ! is_array( $stats ) || empty( $stats['total'] ) ) {
			return '';
		}
		if ( (int) $stats['pages'] <= 1 ) {
			return javan_seda_comment_number( $stats['total'] ) . ' دیدگاه';
		}
		return sprintf(
			'نمایش دیدگاه‌های %s تا %s از %s — صفحهٔ %s از %s',
			javan_seda_comment_number( $stats['from'] ),
			javan_seda_comment_number( $stats['to'] ),
			javan_seda_comment_number( $stats['total'] ),
			javan_seda_comment_number( $stats['current'] ),
			javan_seda_comment_number( $stats['pages'] )
		);
	}
}

if ( ! function_exists( 'javan_seda_comment_page_num' ) ) {
	/** شمارهٔ صفحه با رقمِ فارسی، بدون جداکنندهٔ هزارگان (۱ … ۱۲). */
	function javan_seda_comment_page_num( $number ) {
		$text = (string) max( 0, (int) $number );
		if ( function_exists( 'javan_seda_digits' ) ) {
			return javan_seda_digits( $text );
		}
		return str_replace(
			array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
			array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
			$text
		);
	}
}

if ( ! function_exists( 'javan_seda_comment_pager_link' ) ) {
	/**
	 * تزئینِ یک آیتمِ خروجیِ `paginate_comments_links`:
	 * `rel="prev"/"next"` برای پیمایش، و `aria-label` فارسی برای شماره‌ها.
	 * اگر الگو پیدا نشود، رشته دست‌نخورده برمی‌گردد (هیچ‌وقت چیزی را نمی‌شکند).
	 *
	 * @param string $link یک آیتم (a/span) از `paginate_links`.
	 * @return string
	 */
	function javan_seda_comment_pager_link( $link ) {
		$link = (string) $link;

		if ( false !== strpos( $link, 'class="prev page-numbers"' ) ) {
			$link = str_replace( 'class="prev page-numbers"', 'class="prev page-numbers ab-cpager__step" rel="prev"', $link );
		} elseif ( false !== strpos( $link, 'class="next page-numbers"' ) ) {
			$link = str_replace( 'class="next page-numbers"', 'class="next page-numbers ab-cpager__step" rel="next"', $link );
		}

		$link = preg_replace_callback(
			'#<a class="page-numbers" href="([^"]*)">(\d+)</a>#',
			function ( $m ) {
				return '<a class="page-numbers" href="' . $m[1] . '" aria-label="صفحهٔ ' . javan_seda_comment_page_num( $m[2] ) . '">' . javan_seda_comment_page_num( $m[2] ) . '</a>';
			},
			$link
		);

		/* رقمِ فارسی برای متنِ آیتم‌هایی که الگوی بالا نگرفتند (مثلاً span صفحۀ جاری) */
		$link = preg_replace_callback(
			'#>([0-9]+)</(span|a)>#',
			function ( $m ) {
				return '>' . javan_seda_comment_page_num( $m[1] ) . '</' . $m[2] . '>';
			},
			$link
		);

		return (string) $link;
	}
}

if ( ! function_exists( 'javan_seda_comments_pager' ) ) {
	/**
	 * ناوبریِ صفحه‌بندیِ دیدگاه‌ها (بالا و پایینِ فهرست).
	 *
	 * @param array $args position (top|bottom) — فقط برای کلاس/داده‌ی ظاهری.
	 * @return string HTML امن (همهٔ اجزا escape شده‌اند).
	 */
	function javan_seda_comments_pager( $args = array() ) {
		$args = function_exists( 'wp_parse_args' )
			? wp_parse_args( $args, array( 'position' => 'bottom' ) )
			: array_merge( array( 'position' => 'bottom' ), (array) $args );

		$position = ( 'top' === $args['position'] ) ? 'top' : 'bottom';
		$stats    = javan_seda_comments_stats();

		if ( empty( $stats['total'] ) ) {
			return '';
		}

		$links = array();
		if ( (int) $stats['pages'] > 1 && function_exists( 'paginate_comments_links' ) ) {
			$links = paginate_comments_links(
				array(
					'echo'      => false,
					'type'      => 'array',
					'prev_text' => 'قدیمی‌تر',
					'next_text' => 'جدیدتر',
					'mid_size'  => 1,
					'end_size'  => 1,
				)
			);
			if ( ! is_array( $links ) ) {
				$links = array();
			}
		}

		$out  = '<nav class="ab-cpager ab-cpager--' . esc_attr( $position ) . '" aria-label="صفحه‌بندی دیدگاه‌ها" data-ab-cpager>';
		$out .= '<p class="ab-cpager__summary">' . esc_html( javan_seda_comments_summary( $stats ) ) . '</p>';

		if ( ! empty( $links ) ) {
			$out .= '<ul class="ab-cpager__list">';
			foreach ( $links as $link ) {
				$out .= '<li class="ab-cpager__item">' . javan_seda_comment_pager_link( $link ) . '</li>';
			}
			$out .= '</ul>';
		}

		$out .= '</nav>';
		return $out;
	}
}

if ( ! function_exists( 'javan_seda_comments_heading' ) ) {
	/**
	 * سرتیترِ فهرستِ دیدگاه‌ها («دیدگاه‌ها (۱۲۳)»).
	 *
	 * @param int $post_id شناسهٔ نوشته.
	 * @return string
	 */
	function javan_seda_comments_heading( $post_id = 0 ) {
		$stats = javan_seda_comments_stats( $post_id );
		if ( empty( $stats['total'] ) ) {
			return '';
		}
		return '<h2 class="comments-title ab-comments-title" id="ab-comments-title">'
			. 'دیدگاه‌ها <span class="ab-comments-title__count">(' . esc_html( javan_seda_comment_number( $stats['total'] ) ) . ')</span>'
			. '</h2>';
	}
}
