<?php
/**
 * Plugin Name: آهنگ‌بان — شمارشگرِ «محبوبیت» (mu-plugin)
 * Description: یک‌فایله و مستقل از قالب. کلید meta به نام <code>views</code> را که ابزارک
 *              «(اختصاصی) موسیقی محبوب (بازدید)» و بلوک «پربازدیدترین آثار ماه» می‌خوانند،
 *              پر می‌کند: شمارش واقعی با beacon (سازگار با کش) + یک بار seed تا باکس از همین
 *              امروز خالی نماند. برای حذف کافی است همین یک فایل را پاک کنید (هیچ فایل قالب
 *              تغییر نکرده است). نسخه: 1.0 (v3.1.8)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------------------
 * تنظیم‌ها — همه اختیاری‌اند؛ برای تغییر، این خط‌ها را در wp-config.php بگذارید
 * -------------------------------------------------------------------------- */
if ( ! defined( 'AHANGBAN_POPULAR' ) )            define( 'AHANGBAN_POPULAR', true );             // false ⇒ کاملاً خاموش
if ( ! defined( 'AHANGBAN_POPULAR_SEED' ) )       define( 'AHANGBAN_POPULAR_SEED', true );        // false ⇒ پرکردنِ اولیه انجام نشود
if ( ! defined( 'AHANGBAN_POPULAR_SEED_MIN' ) )   define( 'AHANGBAN_POPULAR_SEED_MIN', 1 );       // مقدار اولیهٔ views برای نوشته‌های بی‌بازدید
if ( ! defined( 'AHANGBAN_POPULAR_DEDUPE_HOURS' ) ) define( 'AHANGBAN_POPULAR_DEDUPE_HOURS', 6 ); // پنجرۀ تکرار (به‌ازای IP+نوشته)
if ( ! defined( 'AHANGBAN_POPULAR_SKIP_LOGGED' ) )  define( 'AHANGBAN_POPULAR_SKIP_LOGGED', true );// بازدیدِ خودِ شما شمرده نشود

/* ===========================================================================
 * ۱) ابزارهای کوچک (همه با function_exists محافظت‌شده · بدون ساختار PHP 7.4+)
 * ======================================================================== */
if ( ! function_exists( 'ahangban_popular_on' ) ) {
	function ahangban_popular_on() {
		return ( defined( 'AHANGBAN_POPULAR' ) && false === AHANGBAN_POPULAR ) ? false : true;
	}
}

if ( ! function_exists( 'ahangban_popular_is_bot' ) ) {
	function ahangban_popular_is_bot() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( (string) $_SERVER['HTTP_USER_AGENT'] ) : '';
		if ( '' === $ua ) {
			return true; /* بدون UA ⇒ ربات/اسکریپت */
		}
		$bots = array( 'bot', 'spider', 'crawl', 'slurp', 'curl', 'wget', 'python', 'scanner', 'monitor', 'preview', 'facebookexternalhit', 'telegrambot', 'whatsapp', 'discordbot', 'twitterbot', 'semrush', 'ahrefs', 'petalbot' );
		foreach ( $bots as $b ) {
			if ( false !== strpos( $ua, $b ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'ahangban_popular_ip_key' ) ) {
	/** IP هش‌شده (IP خام نگه داشته نمی‌شود) — برای پنجرۀ تکرار */
	function ahangban_popular_ip_key( $post_id ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
		if ( $ip && isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = (string) $_SERVER['HTTP_CF_CONNECTING_IP'];
		} elseif ( $ip && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$parts = array_map( 'trim', explode( ',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			if ( isset( $parts[0] ) && preg_match( '/^[0-9a-fA-F:.]+$/', $parts[0] ) ) {
				$ip = $parts[0];
			}
		}
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
		return 'ahbp_' . (int) $post_id . '_' . substr( sha1( $ip . '|' . $ua . '|ahangban' ), 0, 24 );
	}
}

if ( ! function_exists( 'ahangban_popular_reply' ) ) {
	/** پاسخ بی‌بدن؛ صفحهٔ خطا یا HTML چاپ نمی‌شود */
	function ahangban_popular_reply( $code ) {
		if ( function_exists( 'http_response_code' ) ) {
			http_response_code( (int) $code );
		}
		exit;
	}
}

/* ===========================================================================
 * ۲) شمارش با beacon (چرا؟ چون اگر شمارش در HTML باشد، کشِ صفحه هیچ‌وقت نمی‌گذارد عدد بالا برود)
 * ======================================================================== */
if ( ! function_exists( 'ahangban_popular_beacon' ) ) {
	function ahangban_popular_beacon() {
		if ( ! ahangban_popular_on() || ! function_exists( 'is_singular' ) || ! is_singular( 'post' ) ) {
			return;
		}
		$id = (int) get_the_ID();
		if ( ! $id || ! function_exists( 'wp_create_nonce' ) || ! function_exists( 'admin_url' ) ) {
			return;
		}
		echo '<script id="ahangban-views-js" data-url="' . esc_attr( admin_url( 'admin-ajax.php' ) ) . '" data-id="' . $id . '" data-n="' . esc_attr( wp_create_nonce( 'ahangban_view_' . $id ) ) . '">'
			. '(function(){var s=document.getElementById("ahangban-views-js");if(!s)return;'
			. 'var d=new FormData();d.append("action","ahangban_view");d.append("p",s.getAttribute("data-id"));d.append("_n",s.getAttribute("data-n"));'
			. 'var u=s.getAttribute("data-url");try{if(navigator.sendBeacon){navigator.sendBeacon(u,d);}else if(window.fetch){fetch(u,{method:"POST",body:d,credentials:"same-origin"});}}catch(e){}})();'
			. '</script>' . "\n";
	}
}

if ( ! function_exists( 'ahangban_popular_count' ) ) {
	/** هندلِ POST از admin-ajax — هیچ ورودی خامی استفاده نمی‌شود */
	function ahangban_popular_count() {
		if ( ! ahangban_popular_on() ) {
			ahangban_popular_reply( 204 );
		}
		$id    = isset( $_REQUEST['p'] ) ? (int) $_REQUEST['p'] : 0;
		$nonce = isset( $_REQUEST['_n'] ) ? (string) $_REQUEST['_n'] : '';
		if ( ! $id || ! function_exists( 'wp_verify_nonce' ) || ! wp_verify_nonce( $nonce, 'ahangban_view_' . $id ) ) {
			ahangban_popular_reply( 403 );
		}
		if ( ahangban_popular_is_bot() ) {
			ahangban_popular_reply( 204 );
		}
		if ( AHANGBAN_POPULAR_SKIP_LOGGED && function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
			ahangban_popular_reply( 204 );
		}
		$post = get_post( $id );
		if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
			ahangban_popular_reply( 404 );
		}
		$key = ahangban_popular_ip_key( $id );
		if ( function_exists( 'get_transient' ) && false !== get_transient( $key ) ) {
			ahangban_popular_reply( 204 ); /* این IP در این پنجره زمانی شمارش کرده */
		}
		$views = (int) get_post_meta( $id, 'views', true );
		$views = $views + 1;
		update_post_meta( $id, 'views', $views );
		if ( function_exists( 'set_transient' ) ) {
			set_transient( $key, 1, (int) AHANGBAN_POPULAR_DEDUPE_HOURS * HOUR_IN_SECONDS );
		}
		ahangban_popular_reply( 204 );
	}
}

/* ===========================================================================
 * ۳) seed یک‌باره: نوشته‌هایی که هیچ meta به نام views ندارند، مقدار اولیه می‌گیرند.
 *    بدون این قدم، کوئریِ قالب (meta_key = views) هیچ پستی برنمی‌گرداند و باکس خالی می‌ماند
 *    تا اولین بازدیدهای واقعی شمرده شود. هر درخواست فقط یک دسته (۳۰۰تایی) کار می‌کند.
 * ======================================================================== */
if ( ! function_exists( 'ahangban_popular_seed' ) ) {
	function ahangban_popular_seed() {
		if ( ! ahangban_popular_on() || ! AHANGBAN_POPULAR_SEED ) {
			return;
		}
		if ( ! function_exists( 'get_option' ) || 'done' === get_option( 'ahangban_popular_seed', '' ) ) {
			return;
		}
		global $wpdb;
		if ( ! isset( $wpdb ) || ( function_exists( 'wp_installing' ) && wp_installing() ) ) {
			return;
		}
		$sql = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) "
			. "SELECT p.ID, 'views', %d FROM {$wpdb->posts} p "
			. "WHERE p.post_type = 'post' AND p.post_status = 'publish' "
			. "AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} m WHERE m.post_id = p.ID AND m.meta_key = 'views') "
			. "ORDER BY p.ID DESC LIMIT 300";
		$done = $wpdb->query( $wpdb->prepare( $sql, (int) AHANGBAN_POPULAR_SEED_MIN ) );
		if ( ! $done ) {
			update_option( 'ahangban_popular_seed', 'done', false ); /* چیزی برای seed نبود ⇒ تمام شد */
		}
	}
}

/* ===========================================================================
 * ۴) بررسی وضعیت:  https://example.com/?ahbp=1  (فقط برای کاربر ادمین — JSON می‌دهد)
 * ======================================================================== */
if ( ! function_exists( 'ahangban_popular_state' ) ) {
	function ahangban_popular_state() {
		if ( ! isset( $_GET['ahbp'] ) || ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		global $wpdb;
		$out = array(
			'laye'       => 'mu-plugin ahangban-popular v1.0',
			'rows_views' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'views'" ),
			'top_views'  => (int) $wpdb->get_var( "SELECT CAST(meta_value AS UNSIGNED) FROM {$wpdb->postmeta} WHERE meta_key = 'views' ORDER BY CAST(meta_value AS UNSIGNED) DESC LIMIT 1" ), /* بزرگ‌ترین شمارندۀ یک نوشته */
			'seed'       => get_option( 'ahangban_popular_seed', 'در جریان/لازم نیست' ),
			'beacon'     => 'admin-ajax.php?action=ahangban_view (POST + nonce)',
			'note'       => 'عددِ نمایشی ممکن است تا پاک‌شدن کشِ صفحه یک بازدید عقب بماند؛ شمارش واقعی انجام می‌شود.',
		);
		header( 'Content-Type: application/json; charset=utf-8' );
		echo json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		exit;
	}
}

/* ===========================================================================
 * ۵) ثبت قلاب‌ها — هر callback در همین فایل تعریف شده (بدون نامِ معلق)
 * ======================================================================== */
if ( function_exists( 'add_action' ) && function_exists( 'ahangban_popular_beacon' ) ) {
	add_action( 'wp_footer', 'ahangban_popular_beacon', 20 );
}
if ( function_exists( 'add_action' ) && function_exists( 'ahangban_popular_count' ) ) {
	add_action( 'wp_ajax_ahangban_view', 'ahangban_popular_count' );
	add_action( 'wp_ajax_nopriv_ahangban_view', 'ahangban_popular_count' );
}
if ( function_exists( 'add_action' ) && function_exists( 'ahangban_popular_seed' ) ) {
	add_action( 'init', 'ahangban_popular_seed', 1 );
}
if ( function_exists( 'add_action' ) && function_exists( 'ahangban_popular_state' ) ) {
	add_action( 'init', 'ahangban_popular_state', 2 );
}
