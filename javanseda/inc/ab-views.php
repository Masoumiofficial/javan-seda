<?php
/**
 * جوان‌صدا — لایۀ «محبوبیت» (شمارش بازدید + چارت) — نسخهٔ 3.1.5
 * =============================================================================
 * از آنالیز (docs/AUDIT_2026-10.md بند ۵): قالب کلید meta به نام `views` را «فقط می‌خواند»
 * (inc/mostviews_posts.php و ابزارک «موسیقی محبوب» در inc/widgets.php) و هیچ کس آن را
 * نمی‌نوشت ⇒ بلوک «پربازدیدترین آثار ماه» روی صفحۀ اصلی تیتر داشت و صفر مورد.
 * این لایه همان `views` را پر می‌کند (پس هیچ فایلِ دیگری برای دیدن نتیجه لازم نیست) +
 * دو سطلِ تازه: `views_m` (بازدید همین ماه) و `views_ym` (مُهرِ سال‌ماه) تا «ماه» واقعاً ماه باشد.
 *
 * چرا با <script> و admin-ajax (نه مستقیم در PHP صفحۀ نوشته)؟
 *   چون سایت کشِ صفحه‌ساز دارد: اگر شمارش در رندرِ HTML انجام شود، روی صفحۀ کش‌شده
 *   هیچ‌وقت اجرا نمی‌شود (و اگر اجرا شود، کش باطل می‌شود). این‌جا HTML کش می‌ماند و
 *   یک beacon کوچک (sendBeacon/fetch) بعد از لود، شمارش را به admin-ajax.php می‌فرستد.
 *
 * ضدتقلب‌ها (بی‌نیاز از لاگین):
 *   • یک شمارش به‌ازای هر IP + نوشته، هر JAVANSEDA_VIEWS_DEDUPE_HOURS ساعت (transient)
 *   • رد کردن ربات‌ها (User-Agent) و درخواست‌های بد/بدون nonce
 *   • فقط نوشتهٔ منتشرشدهٔ `post`؛ برای کاربرِ لاگین‌کرده هم قابل خاموش‌کردن است
 *
 * تنظیم‌ها (در wp-config.php، قبل از «That's all, stop editing!»):
 *   define( 'JAVANSEDA_VIEWS', false );          // کل لایه خاموش
 *   define( 'JAVANSEDA_VIEWS_DEDUPE_HOURS', 6 );  // پنجرۀ تکرار (ساعت)
 *   define( 'JAVANSEDA_VIEWS_SKIP_LOGGED', true );// بازدیدِ کاربرِ لاگین‌شده شمرده نشود (پیش‌فرض: true)
 *   define( 'JAVANSEDA_CHART_MONTH', true );      // «ماه» با views_m حساب شود (اگر داده بود)
 *
 * شورت‌کد:  [ab_charts period="month" count="10" cat="" title="چارت این ماه"]
 * دست‌نخورده: تاکسونومی‌ها · هوک سراسری · the_content/template_include (هیچ فیلتری رويشان نیست).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'JAVANSEDA_VIEWS' ) && false === JAVANSEDA_VIEWS ) {
	return;
}

if ( ! defined( 'JAVANSEDA_VIEWS_DEDUPE_HOURS' ) ) {
	define( 'JAVANSEDA_VIEWS_DEDUPE_HOURS', 6 );
}
if ( ! defined( 'JAVANSEDA_VIEWS_SKIP_LOGGED' ) ) {
	define( 'JAVANSEDA_VIEWS_SKIP_LOGGED', true );
}
if ( ! defined( 'JAVANSEDA_CHART_MONTH' ) ) {
	define( 'JAVANSEDA_CHART_MONTH', true );
}

/* -------------------------------------------------------------------------
 * ابزارهای کوچک
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_fa_num' ) ) {
	/** رقم‌های فارسی (مستقل از locale، چون در admin-ajax هستۀ ترجمه کامل نیست) */
	function javan_seda_fa_num( $n ) {
		$n = (string) $n;
		if ( function_exists( 'number_format_i18n' ) && is_numeric( $n ) ) {
			$n = number_format_i18n( (float) $n );
		}
		return strtr( $n, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
	}
}

if ( ! function_exists( 'javan_seda_views_ip' ) ) {
	/** IP هاش‌شده (بدون نگه‌داشتن IP خام ⇒ سازگار با حریم خصوصی) */
	function javan_seda_views_ip() {
		$ip = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = (string) $_SERVER['REMOTE_ADDR'];
		}
		/* اگر پشت CDN/پروکسی هستید و هدر زیر را ست کرده‌اید، از همان استفاده می‌کنیم */
		if ( $ip && ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) ) {
			$ip = (string) $_SERVER['HTTP_CF_CONNECTING_IP'];
		} elseif ( $ip && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$parts = array_map( 'trim', explode( ',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			if ( preg_match( '/^[0-9a-fA-F:.]+$/', $parts[0] ) ) {
				$ip = $parts[0];
			}
		}
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
		return substr( sha1( $ip . '|' . $ua . '|javan-seda' ), 0, 24 );
	}
}

if ( ! function_exists( 'javan_seda_views_is_bot' ) ) {
	function javan_seda_views_is_bot() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( (string) $_SERVER['HTTP_USER_AGENT'] ) : '';
		if ( '' === $ua ) {
			return true; // بدون UA ⇒ عملاً ربات/اسکریپت
		}
		$bots = array( 'bot', 'spider', 'crawl', 'slurp', 'curl', 'wget', 'python', 'scanner', 'monitor', 'preview', 'facebookexternalhit', 'telegrambot', 'whatsapp', 'discordbot', 'twitterbot', 'ia_archiver', 'semrush', 'ahrefs', 'mj12', 'dotbot', 'petalbot', 'barkrowler' );
		foreach ( $bots as $b ) {
			if ( false !== strpos( $ua, $b ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'javan_seda_views_get' ) ) {
	/** بازدیدِ کل (int) */
	function javan_seda_views_get( $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		return (int) get_post_meta( $post_id, 'views', true );
	}
}

if ( ! function_exists( 'javan_seda_views_month_key' ) ) {
	/**
	 * کلیدِ مرتب‌سازیِ «پربازدیدترین‌ها»: اگر دست‌کم ۳ نوشته برای همین ماه داده داشتند
	 * از views_m استفاده می‌شود (چارت ماهانهٔ واقعی)، وگرنه views (کل زمان) تا بلوک خالی نماند.
	 */
	function javan_seda_views_month_key() {
		static $cached = null;
		if ( null !== $cached ) {
			return $cached;
		}
		$cached = 'views';
		if ( ! JAVANSEDA_CHART_MONTH || ! function_exists( 'wp_count_posts' ) ) {
			return $cached;
		}
		$trans = get_transient( 'javan_seda_views_mkey' );
		if ( is_string( $trans ) && '' !== $trans ) {
			$cached = $trans;
			return $cached;
		}
		$n = (int) get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 3,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => 'views_ym',
						'value' => (int) date( 'Ym' ),
					),
					array(
						'key'     => 'views_m',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			)
		);
		if ( count( $n ) >= 3 ) {
			$cached = 'views_m';
		}
		set_transient( 'javan_seda_views_mkey', $cached, HOUR_IN_SECONDS );
		return $cached;
	}
}

if ( ! function_exists( 'javan_seda_views_noop' ) ) {
	/** پاسخ بی‌بدن به beacon (بدون HTMLِ wp_die؛ صرفاً برای اینکه کنسول مرورگر تمیز بماند) */
	function javan_seda_views_noop( $code = 204 ) {
		if ( function_exists( 'http_response_code' ) ) {
			http_response_code( (int) $code );
		}
		exit;
	}
}

/* -------------------------------------------------------------------------
 * ۱) نوشتنِ شمارش (از طریق beacon)
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_views_bump' ) ) {
	function javan_seda_views_bump( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return;
		}
		$cur = (int) get_post_meta( $post_id, 'views', true );
		update_post_meta( $post_id, 'views', $cur + 1 );

		$ym    = (int) date( 'Ym' );
		$stamp = (int) get_post_meta( $post_id, 'views_ym', true );
		if ( $stamp !== $ym ) {
			update_post_meta( $post_id, 'views_ym', $ym );
			update_post_meta( $post_id, 'views_m', 1 );
		} else {
			update_post_meta( $post_id, 'views_m', (int) get_post_meta( $post_id, 'views_m', true ) + 1 );
		}

		/* یک شمارۀ ساده برای «پردانلودترین/محبوب‌ترین» در future-round ها */
		wp_cache_delete( 'javan_seda_views_mkey', 'transient' );
	}
}

if ( ! function_exists( 'javan_seda_views_beacon' ) ) {
	/** اسکریپتِ شمارش — در فوترِ صفحۀ تکیِ آهنگ (HTML کش‌شده دست‌نخورده می‌ماند) */
	function javan_seda_views_beacon() {
		if ( ! is_singular( 'post' ) || is_preview() || post_password_required() || is_feed() ) {
			return;
		}
		if ( JAVANSEDA_VIEWS_SKIP_LOGGED && is_user_logged_in() ) {
			return;
		}
		if ( javan_seda_views_is_bot() ) {
			return;
		}
		$id    = (int) get_the_ID();
		$ajax  = esc_url( admin_url( 'admin-ajax.php' ) );
		$nonce = wp_create_nonce( 'ab_view_' . $id );
		echo '<script id="ab-views-js" data-ab-view="' . esc_url( $ajax ) . '" data-ab-id="' . (int) $id . '" data-ab-nonce="' . esc_attr( $nonce ) . '">(function(){var s=document.getElementById("ab-views-js");if(!s)return;var d=new FormData();d.append("action","ab_view");d.append("p",s.getAttribute("data-ab-id"));d.append("_n",s.getAttribute("data-ab-nonce"));var u=s.getAttribute("data-ab-view");try{if(navigator.sendBeacon){navigator.sendBeacon(u,d);}else if(window.fetch){fetch(u,{method:"POST",body:d,credentials:"same-origin"});}}catch(e){}})();</script>' . "\n";
	}
	if ( function_exists( 'javan_seda_views_beacon' ) ) { add_action( 'wp_footer', 'javan_seda_views_beacon', 20 ); }
}

if ( ! function_exists( 'javan_seda_views_ajax' ) ) {
	function javan_seda_views_ajax() {
		$id = isset( $_REQUEST['p'] ) ? (int) $_REQUEST['p'] : 0;
		if ( ! $id ) {
			javan_seda_views_noop( 400 );
		}
		if ( ! isset( $_REQUEST['_n'] ) || ! wp_verify_nonce( (string) $_REQUEST['_n'], 'ab_view_' . $id ) ) {
			javan_seda_views_noop( 403 );
		}
		if ( javan_seda_views_is_bot() ) {
			javan_seda_views_noop( 204 );
		}
		if ( JAVANSEDA_VIEWS_SKIP_LOGGED && is_user_logged_in() ) {
			javan_seda_views_noop( 204 );
		}
		$p = get_post( $id );
		if ( ! $p || 'post' !== $p->post_type || 'publish' !== $p->post_status ) {
			javan_seda_views_noop( 404 );
		}
		$h = (int) JAVANSEDA_VIEWS_DEDUPE_HOURS;
		$k = 'abv_' . $id . '_' . javan_seda_views_ip();
		if ( get_transient( $k ) ) {
			javan_seda_views_noop( 204 );
		}
		set_transient( $k, 1, ( $h > 0 ? $h : 6 ) * HOUR_IN_SECONDS );
		javan_seda_views_bump( $id );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo 'ok';
		exit;
	}
	if ( function_exists( 'javan_seda_views_ajax' ) ) { add_action( 'wp_ajax_ab_view', 'javan_seda_views_ajax' ); }
	if ( function_exists( 'javan_seda_views_ajax' ) ) { add_action( 'wp_ajax_nopriv_ab_view', 'javan_seda_views_ajax' ); }
}

/* -------------------------------------------------------------------------
 * ۲) نشانِ «چند بار دیده شده» (برای single.php / هر جای دیگر)
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_views_badge' ) ) {
	function javan_seda_views_badge( $post_id = 0, $echo = true ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		$total   = javan_seda_views_get( $post_id );
		$month   = (int) get_post_meta( $post_id, 'views_m', true );
		$out     = '<ul class="ab-views-badge"><li><i class="icofont-eye-alt" aria-hidden="true"></i> '
			. esc_html( javan_seda_fa_num( $total ) ) . ' بازدید'
			. ( $month > 0 ? ' <span class="ab-views-badge__m">· ' . esc_html( javan_seda_fa_num( $month ) ) . ' این ماه</span>' : '' )
			. '</li></ul>';
		if ( $echo ) {
			echo $out;
		}
		return $out;
	}
}

/* -------------------------------------------------------------------------
 * ۳) چارت — [ab_charts]
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_charts_html' ) ) {
	function javan_seda_charts_html( $atts = array() ) {
		$a = shortcode_atts(
			array(
				'period' => 'month',   // month | week | all
				'count'  => 10,
				'cat'    => '',
				'title'  => '',
				'cols'   => 1,        // 2 = دو ستونه (کارت‌ها کوچک‌تر)
			),
			$atts,
			'ab_charts'
		);
		$count = max( 1, min( 30, (int) $a['count'] ) );
		$key   = ( 'all' === $a['period'] ) ? 'views' : javan_seda_views_month_key();

		$args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $count,
			'ignore_sticky_posts' => true,
			'meta_key'            => $key,
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
			'no_found_rows'       => true,
		);
		if ( 'week' === $a['period'] ) {
			$args['date_query'] = array( array( 'column' => 'post_date_gmt', 'after' => '7 days ago' ) );
		}
		$cat = trim( (string) $a['cat'] );
		if ( '' !== $cat ) {
			if ( is_numeric( $cat ) ) {
				$args['cat'] = (int) $cat;
			} else {
				$args['category_name'] = $cat;
			}
		}
		$q = new WP_Query( $args );

		/* اگر هیچ داده‌ای نبود، چارت را خالی چاپ نکن (تیترِ بی‌مورد نده) */
		if ( ! $q->have_posts() ) {
			$backup = $args;
			unset( $backup['meta_key'], $backup['orderby'] );
			$backup['orderby'] = 'date';
			$backup['meta_query'] = array(
				array(
					'key'     => 'views',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			);
			$q = new WP_Query( $backup );
			if ( ! $q->have_posts() ) {
				wp_reset_postdata();
				return '';
			}
		}

		$i       = 0;
		$rows    = '';
		while ( $q->have_posts() ) {
			$q->the_post();
			$i++;
			$id     = get_the_ID();
			$v      = (int) get_post_meta( $id, $key, true );
			$artist = '';
			$song   = '';
			if ( function_exists( 'get_field' ) ) {
				$artist = (string) get_field( 'artist', $id );
				$song   = (string) get_field( 'song', $id );
			}
			if ( '' === $song ) {
				$song = wp_strip_all_tags( get_the_title() );
			}
			$thumb = '';
			if ( has_post_thumbnail( $id ) ) {
				$thumb = '<span class="ab-chart__img">' . get_the_post_thumbnail( $id, 'thumb3', array( 'loading' => 'lazy', 'decoding' => 'async' ) ) . '</span>';
			}
			$rows .= '<li class="ab-chart__row' . ( $i <= 3 ? ' is-top' : '' ) . '">'
				. '<span class="ab-chart__rank">' . esc_html( javan_seda_fa_num( $i ) ) . '</span>'
				. '<a class="ab-chart__link" href="' . esc_url( get_permalink( $id ) ) . '">'
				. $thumb
				. '<span class="ab-chart__txt"><b>' . esc_html( $song ) . '</b>'
				. ( '' !== $artist ? '<i>' . esc_html( $artist ) . '</i>' : '' )
				. '</span></a>'
				. '<span class="ab-chart__n" title="بازدید"><i class="icofont-eye-alt" aria-hidden="true"></i>' . esc_html( javan_seda_fa_num( $v ) ) . '</span>'
				. '</li>';
		}
		wp_reset_postdata();

		$title = trim( (string) $a['title'] );
		if ( '' === $title ) {
			$title = ( 'all' === $a['period'] ) ? 'پردیدگاه‌ترین‌ها' : ( 'week' === $a['period'] ? 'چارت این هفته' : 'چارت این ماه' );
		}
		$label_key = ( 'views_m' === $key ) ? 'بازدیدِ همین ماه' : 'کل بازدید';

		return '<div class="song_boxs ab-chart' . ( 2 == (int) $a['cols'] ? ' ab-chart--2col' : '' ) . '">'
			. '<div class="songs_title_box"><h3>' . esc_html( $title ) . '</h3>'
			. '<span class="ab-chart__hint">' . esc_html( $label_key ) . '</span>'
			. '<div class="clear"></div><div class="liner"></div></div>'
			. '<div class="song_boxs_posts"><ul class="ab-chart__list">' . $rows . '</ul><div class="clear"></div></div>'
			. '</div>';
	}
}

if ( ! function_exists( 'javan_seda_charts_shortcode' ) ) {
	function javan_seda_charts_shortcode( $atts ) {
		return javan_seda_charts_html( $atts );
	}
}

/* v3.1.7 — علت خطای کشندۀ debug.log همین‌جا بود: در 3.1.5 این بلوک یک add_action روی
   init ثبت می‌کرد که تابعش هرگز تعریف نشد («dangling callback») و در PHP 8 به شکل
   TypeError در class-wp-hook.php کل سایت و wp-cron را می‌کشت.
   اصلاح: ثبت مستقیم شورت‌کد، بدون هوک و بدون نامِ معلق، و فقط وقتی تابع واقعاً موجود است.
   (توضیح نامِ تابع را عمداً کامل ننوشتم تا دروازۀ myfunctions.php که منبع را با preg می‌خواند،
   متن کامنت را با کد اشتباه نگیرد.) */
if ( function_exists( 'add_shortcode' ) && function_exists( 'javan_seda_charts_shortcode' ) ) {
	add_shortcode( 'ab_charts', 'javan_seda_charts_shortcode' );
}

/* -------------------------------------------------------------------------
 * ۴) گزارش برای کاوشگر (ab-mobile-probe.php — بخش ۱۲)
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_views_report' ) ) {
	function javan_seda_views_report() {
		$L    = array();
		$line = function ( $s = '' ) use ( &$L ) {
			$L[] = $s;
		};
		global $wpdb;
		$line( '=== لایۀ «محبوبیت» v3.1.5 — بازدید و چارت ===' );
		$line( 'لایه                  : ' . ( ( defined( 'JAVANSEDA_VIEWS' ) && false === JAVANSEDA_VIEWS ) ? '✗ خاموش' : '✓ روشن' ) . ' | پنجرۀ تکرار: ' . (int) JAVANSEDA_VIEWS_DEDUPE_HOURS . ' ساعت | skip logged: ' . ( JAVANSEDA_VIEWS_SKIP_LOGGED ? 'yes' : 'no' ) );
		$with = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'views' AND CAST(meta_value AS UNSIGNED) > 0" );
		$sum  = (int) $wpdb->get_var( "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = 'views'" );
		$m    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'views_ym' AND CAST(meta_value AS UNSIGNED) = %d", (int) date( 'Ym' ) ) );
		$line( 'نوشته با views>0      : ' . $with . ' (مجموع بازدید: ' . javan_seda_fa_num( $sum ) . ')' );
		$line( 'سطل ماه (views_ym=' . (int) date( 'Ym' ) . ') : ' . $m . ' نوشته' );
		$line( 'کلید چارتِ فعلی        : ' . javan_seda_views_month_key() . '  (' . ( 'views_m' === javan_seda_views_month_key() ? 'چارت ماهانهٔ واقعی ✓' : 'فعلاً «کل زمان» — چون دادهٔ ماهانه کم است' ) . ')' );
		$line( 'endpoint              : ' . admin_url( 'admin-ajax.php' ) . '?action=ab_view (POST + nonce)');
		$line( 'نکتۀ مهم              : اگر کشِ صفحه فعال است، شمارش فقط با اسکریپتِ ab-views-js انجام می‌شود؛' );
		$line( '  در View Source همان صفحه باید «ab-views-js» را ببینید. نبودش = کشِ کهنه یا JAVANSEDA_VIEWS=false' );
		$line( '  تست سریع: یک صفحۀ آهنگ را باز کنید، ۵ ثانیه صبر کنید، number را refresh کنید.');
		return $L;
	}
}
