<?php
/**
 * جوان‌صدا — لایۀ «امتیاز» (رأی ۱ تا ۵ + aggregateRating) — نسخهٔ 3.1.5
 * =============================================================================
 * چرا؟ از آنالیز: هیچ راهی برای رأی دادن کاربر وجود نداشت ⇒ (۱) سیگنال محبوبیت برای
 * چارت/مرتب‌سازی نداشتیم (۲) در JSON-LD هم `aggregateRating` نبود، یعنی ستاره‌های
 * گوگل در نتایج جست‌وجو (بالاترین CTR در نتایج موبایل) از دست می‌رفت.
 *
 * طراحی (بی‌نیاز از لاگین، بی‌نیاز از JS، سازگار با کش):
 *   • بلاک رأی = یک <form method="post"> روی همان نشانیِ نوشته؛ هندل در `init` (پیش از هر خروجی)
 *     و بعد 302 → همان صفحه با #ab-rate. درخواست POST هیچ‌وقت از کش سرو نمی‌شود، پس با
 *     LiteSpeed/WP Super Cache هم درست کار می‌کند (برخلاف شمارشِ GET).
 *   • ضد هرزنامه: honeypot (فیلد ab_hp باید خالی بماند) + nonce + سقف «یک رأی به‌ازای هر IP
 *     برای هر نوشته» (transient، JAVANSEDA_RATE_IP_DAYS روز) + بازۀ ۱..۵.
 *   • میانگین با دقت دو رقم نگه داشته می‌شود (rating_avg) + تعداد (rating_count).
 *   • aggregateRating در یک بلوک JSON-LD جدا با @type=WebPage و @id=نشانیِ نوشته — عمداً
 *     MusicRecordingِ خودِ قالب دست‌نخورده می‌ماند تا دو موجودیتِ متناقض نسازیم.
 *
 * جاگذاری: خودِ single.php این دو تابع را صدا می‌زند (نسخۀ 3.1.5)؛ اگر single.php را
 * به‌روز نکنید، شورت‌کد [ab_rating] در انتهای نوشته کار را راه می‌اندازد.
 *
 * تنظیم‌ها:
 *   define( 'JAVANSEDA_RATINGS', false );     // کل لایه خاموش
 *   define( 'JAVANSEDA_RATE_IP_DAYS', 30 );   // پنجرۀ تکرار رأی (روز)
 *   define( 'JAVANSEDA_RATE_LD', true );      // بلوک aggregateRating
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'JAVANSEDA_RATINGS' ) && false === JAVANSEDA_RATINGS ) {
	return;
}

if ( ! defined( 'JAVANSEDA_RATE_IP_DAYS' ) ) {
	define( 'JAVANSEDA_RATE_IP_DAYS', 30 );
}
if ( ! defined( 'JAVANSEDA_RATE_LD' ) ) {
	define( 'JAVANSEDA_RATE_LD', true );
}

if ( ! function_exists( 'javan_seda_rate_ip_key' ) ) {
	function javan_seda_rate_ip_key( $post_id ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = (string) $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		return 'abr_' . (int) $post_id . '_' . substr( sha1( $ip . '|javan-seda-rate' ), 0, 24 );
	}
}

if ( ! function_exists( 'javan_seda_rate_stars_html' ) ) {
	/** ستاره‌های نمایشی (۰..۵، با نیم‌پله) */
	function javan_seda_rate_stars_html( $avg, $echo = true ) {
		$avg  = (float) $avg;
		$fill = max( 0, min( 5, $avg ) );
		$pct  = round( ( $fill / 5 ) * 100, 1 );
		$out  = '<span class="ab-rate__stars" role="img" aria-label="امتیاز ' . esc_attr( (string) $fill ) . ' از ۵">'
			. '<span class="ab-rate__stars-bg" aria-hidden="true">★★★★★</span>'
			. '<span class="ab-rate__stars-fg" aria-hidden="true" style="width:' . esc_attr( (string) $pct ) . '%">★★★★★</span>'
			. '</span>';
		if ( $echo ) {
			echo $out;
		}
		return $out;
	}
}

if ( ! function_exists( 'javan_seda_rate_state' ) ) {
	/** وضعیت رأیِ این IP روی این نوشته + اعداد ذخیره‌شده */
	function javan_seda_rate_state( $post_id ) {
		$post_id = (int) $post_id;
		return array(
			'avg'   => (float) get_post_meta( $post_id, 'rating_avg', true ),
			'count' => (int) get_post_meta( $post_id, 'rating_count', true ),
			'voted' => (bool) get_transient( javan_seda_rate_ip_key( $post_id ) ),
		);
	}
}

if ( ! function_exists( 'javan_seda_rating_block' ) ) {
	/** بلاک کامل (میانگین + فرم) — همان چیزی که single.php صدا می‌زند */
	function javan_seda_rating_block( $post_id = 0, $echo = true ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		if ( ! $post_id ) {
			return '';
		}
		$st   = javan_seda_rate_state( $post_id );
		$msg  = '';
		if ( isset( $_GET['ab_rate'] ) ) {
			$g  = sanitize_key( (string) $_GET['ab_rate'] );
			$msg = 'ok' === $g ? 'ممنون! رأی شما ثبت شد.' : ( 'dup' === $g ? 'شما قبلاً به این اثر رأی داده‌اید.' : ( 'bad' === $g ? 'رأی معتبر نبود (۱ تا ۵).' : '' ) );
		}
		$f = get_permalink( $post_id ) . '#ab-rate';

		$out = '<div class="ab-rate" id="ab-rate">'
			. '<div class="ab-rate__score">'
			. '<span class="ab-rate__num">' . esc_html( number_format_i18n( $st['avg'], 1 ) ) . '</span>'
			. javan_seda_rate_stars_html( $st['avg'], false )
			. '<span class="ab-rate__n">' . ( $st['count'] > 0 ? esc_html( javan_seda_fa_num_safe( $st['count'] ) ) . ' رأی' : 'هنوز رأیی ثبت نشده' ) . '</span>'
			. '</div>';

		if ( $st['voted'] ) {
			$out .= '<p class="ab-rate__done"><i class="icofont-check-circled" aria-hidden="true"></i> رأی شما ثبت شده است — ممنون.</p>';
		} else {
			$out .= '<form class="ab-rate__form" method="post" action="' . esc_url( $f ) . '">'
				. '<input type="hidden" name="ab_rate_pid" value="' . (int) $post_id . '">'
				. '<input type="hidden" name="ab_rate_nonce" value="' . esc_attr( wp_create_nonce( 'ab_rate_' . $post_id ) ) . '">'
				. '<p class="ab-rate__q" aria-hidden="true">'
				. '<input type="text" name="ab_hp" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0"></p>'
				. '<fieldset class="ab-rate__opts"><legend>به این اثر امتیاز دهید</legend>';
			for ( $i = 5; $i >= 1; $i-- ) {
				$out .= '<label class="ab-rate__opt ab-rate__opt--' . (int) $i . '">'
					. '<input type="radio" name="ab_rate_v" value="' . (int) $i . '" required>'
					. '<span aria-hidden="true">★</span><em>' . esc_html( javan_seda_fa_num_safe( $i ) ) . '</em></label>';
			}
			$out .= '</fieldset>'
				. '<button type="submit" class="ab-rate__btn"><i class="icofont-star" aria-hidden="true"></i> ثبت امتیاز</button>'
				. '</form>';
		}
		$out .= ( '' !== $msg ? '<p class="ab-rate__msg" role="status">' . esc_html( $msg ) . '</p>' : '' )
			. '</div>';

		if ( $echo ) {
			echo $out;
		}
		return $out;
	}
}

/** رقم فارسی (اگر لایۀ بازدید نبود، خودمان نسخهٔ کمینه را داریم) */
if ( ! function_exists( 'javan_seda_fa_num_safe' ) ) {
	function javan_seda_fa_num_safe( $n ) {
		if ( function_exists( 'javan_seda_fa_num' ) ) {
			return javan_seda_fa_num( $n );
		}
		return strtr( (string) $n, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
	}
}

if ( ! function_exists( 'javan_seda_rate_handle' ) ) {
	/** ثبت رأی — در init و پیش از هر خروجی تا redirect تمیز باشد */
	function javan_seda_rate_handle() {
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
			return;
		}
		if ( empty( $_POST['ab_rate_pid'] ) ) {
			return;
		}
		$pid   = (int) $_POST['ab_rate_pid'];
		$back  = get_permalink( $pid ) . '#ab-rate';
		$nonce = isset( $_POST['ab_rate_nonce'] ) ? (string) $_POST['ab_rate_nonce'] : '';
		$honeypot = isset( $_POST['ab_hp'] ) ? trim( (string) $_POST['ab_hp'] ) : '';

		if ( '' !== $honeypot ) {
			/* ربات: وانمود می‌کنیم ثبت شد تا الگوریتمش را نسازد */
			wp_safe_redirect( add_query_arg( 'ab_rate', 'ok', $back ) );
			exit;
		}
		if ( ! $pid || ! wp_verify_nonce( $nonce, 'ab_rate_' . $pid ) ) {
			wp_safe_redirect( add_query_arg( 'ab_rate', 'bad', $back ) );
			exit;
		}
		$p = get_post( $pid );
		if ( ! $p || 'publish' !== $p->post_status ) {
			wp_safe_redirect( add_query_arg( 'ab_rate', 'bad', home_url( '/' ) ) );
			exit;
		}
		$v = isset( $_POST['ab_rate_v'] ) ? (int) $_POST['ab_rate_v'] : 0;
		if ( $v < 1 || $v > 5 ) {
			wp_safe_redirect( add_query_arg( 'ab_rate', 'bad', $back ) );
			exit;
		}
		$key = javan_seda_rate_ip_key( $pid );
		if ( get_transient( $key ) ) {
			wp_safe_redirect( add_query_arg( 'ab_rate', 'dup', $back ) );
			exit;
		}
		$days = max( 1, (int) JAVANSEDA_RATE_IP_DAYS );
		set_transient( $key, $v, $days * DAY_IN_SECONDS );

		$st  = javan_seda_rate_state( $pid );
		$sum = ( $st['avg'] * $st['count'] ) + $v;
		$n   = $st['count'] + 1;
		update_post_meta( $pid, 'rating_count', $n );
		update_post_meta( $pid, 'rating_avg', round( $sum / $n, 2 ) );

		wp_safe_redirect( add_query_arg( 'ab_rate', 'ok', $back ) );
		exit;
	}
	if ( function_exists( 'javan_seda_rate_handle' ) ) { add_action( 'init', 'javan_seda_rate_handle', 2 ); }
}

if ( ! function_exists( 'javan_seda_rate_shortcode' ) ) {
	function javan_seda_rate_shortcode( $atts ) {
		$a = shortcode_atts( array( 'id' => 0 ), $atts, 'ab_rating' );
		return javan_seda_rating_block( (int) $a['id'], false );
	}
	if ( function_exists( 'javan_seda_rate_add_shortcode' ) ) { add_action( 'init', 'javan_seda_rate_add_shortcode' ); }
}
if ( ! function_exists( 'javan_seda_rate_add_shortcode' ) ) {
	function javan_seda_rate_add_shortcode() {
		add_shortcode( 'ab_rating', 'javan_seda_rate_shortcode' );
	}
}

if ( ! function_exists( 'javan_seda_rate_ld' ) ) {
	/** aggregateRating — بلوک جدا روی @idِ همان صفحه (برای ستاره در نتایج گوگل) */
	function javan_seda_rate_ld() {
		if ( ! JAVANSEDA_RATE_LD || ! is_singular( 'post' ) ) {
			return;
		}
		$pid = (int) get_the_ID();
		$st  = javan_seda_rate_state( $pid );
		if ( $st['count'] < 1 ) {
			return;
		}
		$url = get_permalink( $pid );
		echo '<script type="application/ld+json">'
			. wp_json_encode(
				array(
					'@context'        => 'https://schema.org',
					'@type'           => 'WebPage',
					'@id'             => $url,
					'url'             => $url,
					'name'            => wp_strip_all_tags( get_the_title( $pid ) ),
					'inLanguage'      => 'fa-IR',
					'aggregateRating' => array(
						'@type'       => 'AggregateRating',
						'ratingValue' => (float) $st['avg'],
						'bestRating'  => 5,
						'worstRating' => 1,
						'ratingCount' => (int) $st['count'],
					),
				),
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			)
			. '</script>' . "\n";
	}
	if ( function_exists( 'javan_seda_rate_ld' ) ) { add_action( 'wp_head', 'javan_seda_rate_ld', 9 ); }
}

if ( ! function_exists( 'javan_seda_rating_report' ) ) {
	function javan_seda_rating_report() {
		$L    = array();
		$line = function ( $s = '' ) use ( &$L ) {
			$L[] = $s;
		};
		global $wpdb;
		$line( '=== لایۀ «امتیاز» v3.1.5 ===' );
		$line( 'لایه            : ' . ( ( defined( 'JAVANSEDA_RATINGS' ) && false === JAVANSEDA_RATINGS ) ? '✗ خاموش' : '✓ روشن' ) . ' | پنجرۀ IP: ' . (int) JAVANSEDA_RATE_IP_DAYS . ' روز | JSON-LD: ' . ( JAVANSEDA_RATE_LD ? 'on' : 'off' ) );
		$with = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'rating_count' AND CAST(meta_value AS UNSIGNED) > 0" );
		$best = $wpdb->get_row( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'rating_avg' ORDER BY CAST(meta_value AS DECIMAL(4,2)) DESC LIMIT 1" );
		$line( 'نوشته با رأی     : ' . $with );
		$line( 'بالاترین میانگین : ' . ( $best ? number_format_i18n( (float) $best->meta_value, 2 ) . ' (post ' . (int) $best->post_id . ')' : '—' ) );
		$line( 'جاگذاری         : ' . ( function_exists( 'javan_seda_rating_block' ) ? '✓ تابع موجود است' : '✗' ) . ' — در single.php نسخهٔ 3.1.5 خودکار چاپ می‌شود؛' );
		$line( '  اگر single.php را نصب نمی‌کنید، شورت‌کد [ab_rating] را در انتهای نوشته بگذارید.' );
		$line( 'نکتۀ کش         : ثبت رأی POST است ⇒ از کش سرو نمی‌شود و درست کار می‌کند؛ اما' );
		$line( '  نمایشِ میانگین از HTML کش‌شده می‌آید (تا کش بعدی، عددِ تازه دیده نمی‌شود — طبیعی است).' );
		return $L;
	}
}
