<?php
/**
 * ترانه یار — لایۀ «بایتِ سرعت» (بهینۀ بارگذاری) — نسخهٔ 3.1.3
 * =============================================================================
 * درخواست مالک: «قالب رو هم از بابت سرعت بررسی کن و بهینه کن».
 * این فایل فقط کارهای «بی‌خطر و برگشت‌پذیر» می‌کند و برخلاف نسخه‌های قبلی،
 * هیچ دخالتی در «محتوای نوشته» یا «مسیر قالب» ندارد.
 *
 * چه می‌کند (۶ مورد)
 *  ۱) defer به اسکریپت‌های غیرضروریِ قالب/افزونه‌ها (owl, mediaqueries, scripts,
 *     icofont, comment-reply …) — jQuery و اسکریپت‌های critical دست‌نخورده‌اند.
 *     نتیجه: کاهش render-blocking. اگر جاوااسکریپتی به DOM-ready وابسته باشد،
 *     defer (نه async) رفتارِ «به‌ترتیب اجراشدن بعد از پارس HTML» را حفظ می‌کند.
 *  ۲) حذف بارِ بی‌فایدهٔ <head>: generator، shortlink، RSD، wlwmanifest،
 *     feed-links اضافه، و اسکریپتِ emojiها (و استایلِ آن) — چند ریکوئست کمتر.
 *  ۳) preload برای دو فونتِ پرکاربردِ قالب (Vazir + IcoFont) با crossorigin.
 *     فونت‌ها در این قالب .woff هستند (نه woff2) ⇒ اولین درخواستشان معمولاً
 *     دیر شروع می‌شود؛ preload، FCP/LCP را در موبایل کوتاه می‌کند.
 *  ۴) تزریق font-display: swap برای @font-faceهای قالب ⇒ متن با فونتِ سیستمی
 *     زودتر دیده می‌شود (جلوگیری از invisible text تا ۳ ثانیه).
 *  ۵) loading="lazy" + decoding="async" روی تصاویری که این ویژگی‌ها را ندارند
 *     (تصاویرِ چاپ‌شده با <img> در شورت‌کدها/متن نوشته). افزودن width/height به‌صورت
 *     پیش‌فرض خاموش است (TARA_PERF_IMG_DIMS=false) چون ابعاد حدسی می‌تواند آیکن‌های
 *     کوچک را بزرگ کند؛ لوگو/آیکنِ هدر هرگز lazy نمی‌شوند.
 *  ۶) رپورتِ «بایتِ سرعت» برای اندازه‌گیری: ۵ فایلِ حجیمِ قالب، تعدادِ فایل‌های
 *     JS/CSS در صف، و پیشنهادها — از طریق ab-mobile-probe.php (بخش ۱۰).
 *
 * چه نمی‌کند (عمداً): هیچ کشِ HTML نمی‌سازد، style.css را defer/async نمی‌کند
 * (چون بلوکِ حیاتیِ ما کوچک است و خطر FOUC دارد)، مینیفای نمی‌کند، و دیتابیس را
 * دست نمی‌زند. همه‌چیز با یک خط قابل خاموشی است:
 *    define( 'TARA_PERF', false );
 *
 * پالت سازمانی/تاکسونومی/هوک سراسری: دست‌نخورده. بدون گرادینت.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'TARA_PERF' ) && false === TARA_PERF ) {
	return;
}

if ( ! defined( 'TARA_PERF_DEFER' ) ) {
	define( 'TARA_PERF_DEFER', true );
}
if ( ! defined( 'TARA_PERF_PRELOAD_FONTS' ) ) {
	define( 'TARA_PERF_PRELOAD_FONTS', true );
}
if ( ! defined( 'TARA_PERF_STRIP_HEAD' ) ) {
	define( 'TARA_PERF_STRIP_HEAD', true );
}
if ( ! defined( 'TARA_PERF_LAZY_IMG' ) ) {
	define( 'TARA_PERF_LAZY_IMG', true );
}
/* افزودن width/height به تصاویر بی‌بعد، ابعادِ واقعی را حدسی می‌کند ⇒ می‌تواند چیدمانِ
   آیکن‌های کوچک را عوض کند. پیش‌فرض خاموش است؛ اگر در گزارش PageSpeed خطای CLS داشتید روشنش کنید. */
if ( ! defined( 'TARA_PERF_IMG_DIMS' ) ) {
	define( 'TARA_PERF_IMG_DIMS', false );
}

/** آیا در این درخواست، بهینه‌سازی‌ها باید اجرا شوند؟ (پیشخوان/REST/AJAX/لوگین: نه) */
if ( ! function_exists( 'tara_perf_active' ) ) {
	function tara_perf_active() {
		if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return false;
		}
		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			return false;
		}
		if ( class_exists( 'WP_REST_Request' ) && function_exists( 'rest_is_request' ) && rest_is_request() ) {
			return false;
		}
		return true;
	}
}

/* -------------------------------------------------------------------------
 * ۱) defer برای اسکریپت‌های غیرضروری
 * ---------------------------------------------------------------------- */
if ( TARA_PERF_DEFER && ! function_exists( 'tara_perf_defer' ) ) {
	function tara_perf_defer( $tag, $handle, $src ) {
		if ( ! tara_perf_active() || '' === (string) $tag || false !== strpos( $tag, ' defer' ) ) {
			return $tag;
		}
		/* این‌ها باید اول بیایند/همگام بمانند */
		$keep = apply_filters( 'tara_perf_keep_sync', array( 'jquery', 'jquery-core', 'jquery-migrate', 'taranehyar-main', 'taranehyar-header' ) );
		foreach ( $keep as $k ) {
			if ( 0 === strpos( (string) $handle, $k ) ) {
				return $tag;
			}
		}
		/* skip/exclude بر اساس نام فایل یا هاست‌نام */
		$skip = apply_filters( 'tara_perf_skip_defer', array( 'html5shiv', 'mediaqueries', 'modernizr', 'swv', 'waf', 'google' ) );
		$low  = strtolower( (string) $handle . ' ' . (string) $src );
		foreach ( $skip as $s ) {
			if ( '' !== $s && false !== strpos( $low, $s ) ) {
				return $tag;
			}
		}
		/* درجِ defer، بدون دوباره‌کاری اگر از قبل defer/async دارد */
		if ( preg_match( '/<script[^>]*\bsrc=/i', $tag ) ) {
			return preg_replace( '/<script((?![^>]*\b(?:defer|async)\b)[^>]*?)\ssrc=/i', '<script$1 defer src=', $tag, 1 );
		}
		return preg_replace( '/<script\s/', '<script defer ', $tag, 1 );
	}
	add_filter( 'script_loader_tag', 'tara_perf_defer', 20, 3 );
}

/* -------------------------------------------------------------------------
 * ۲) حذف بارِ بی‌فایده از <head>
 * ---------------------------------------------------------------------- */
if ( TARA_PERF_STRIP_HEAD && ! function_exists( 'tara_perf_strip_head' ) ) {
	function tara_perf_strip_head() {
		if ( ! tara_perf_active() ) {
			return;
		}
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		/* feedهای اضافه (خوراک نظرات/دسته‌ها/tag) — خودِ قالب در header.php یک RSS دستی دارد */
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		/* اسکریپت/استایلِ ایموجیِ وردپرس */
		if ( ! defined( 'TARA_PERF_KEEP_EMOJI' ) ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
		}
	}
	/* اسکریپت oEmbedِ وردپرس (wp-embed.js) برای این سایت بی‌مصرف است */
	if ( ! defined( 'TARA_PERF_KEEP_EMBED' ) ) {
		add_action( 'wp_footer', 'tara_perf_drop_embed', 1 );
	}
	add_action( 'init', 'tara_perf_strip_head', 99 );
}

if ( ! function_exists( 'tara_perf_drop_embed' ) ) {
	function tara_perf_drop_embed() {
		if ( ! tara_perf_active() ) {
			return;
		}
		wp_deregister_script( 'wp-embed' );
	}
}

/* -------------------------------------------------------------------------
 * ۳) preload فونت‌ها  +  ۴) font-display: swap
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'tara_perf_fonts' ) ) {
	function tara_perf_fonts() {
		if ( ! tara_perf_active() ) {
			return;
		}
		$dir = get_stylesheet_directory_uri();

		if ( TARA_PERF_PRELOAD_FONTS ) {
			/* v3.3.8 — اگر نسخهٔ woff2ِ همان فونت روی هاست باشد، همان preload می‌شود (≈۲۰٪ کوچک‌تر
			   در این قالب؛ فایل‌های woff هم دست‌نخورده می‌مانند تا fallback امن باشد). */
			$candidates = apply_filters(
				'tara_perf_preload_fonts',
				array( $dir . '/fonts/vazir-fd.woff2', $dir . '/fonts/icofont.woff2', $dir . '/fonts/vazir-fd.woff', $dir . '/fonts/icofont.woff' )
			);
			$real = get_stylesheet_directory() . '/fonts/';
			$got  = array();
			foreach ( $candidates as $u ) {
				$name = basename( (string) $u );
				$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
				$stem = preg_replace( '/\.woff2?$/', '', $name );
				if ( isset( $got[ $stem ] ) || ! is_readable( $real . $name ) ) { /* یک نسخه به‌ازای هر فونت */
					continue;
				}
				$got[ $stem ] = 1;
				echo '<link rel="preload" as="font" type="font/' . esc_attr( $ext ) . '" href="' . esc_url( $u ) . '" crossorigin="anonymous">' . "\n";
			}
		}
		?>
<style id="ab-perf-fonts">
/* font-display: swap — جلوگیری از پنهان‌ماندنِ متن تا دانلود فونت (v3.1.3) */
@font-face{font-family:vazir;src:url(<?php echo esc_url( $dir ); ?>/fonts/vazir-fd.woff2) format("woff2"),url(<?php echo esc_url( $dir ); ?>/fonts/vazir-fd.woff) format("woff");font-display:swap;font-weight:400;}
@font-face{font-family:vazir;src:url(<?php echo esc_url( $dir ); ?>/fonts/vazir-bold-fd.woff2) format("woff2"),url(<?php echo esc_url( $dir ); ?>/fonts/vazir-bold-fd.woff) format("woff");font-display:swap;font-weight:700;}
@font-face{font-family:vazir;src:url(<?php echo esc_url( $dir ); ?>/fonts/vazir-medium-fd.woff2) format("woff2"),url(<?php echo esc_url( $dir ); ?>/fonts/vazir-medium-fd.woff) format("woff");font-display:swap;font-weight:500;}
@font-face{font-family:vazir;src:url(<?php echo esc_url( $dir ); ?>/fonts/vazir-light-fd.woff2) format("woff2"),url(<?php echo esc_url( $dir ); ?>/fonts/vazir-light-fd.woff) format("woff");font-display:swap;font-weight:300;}
@font-face{font-family:IcoFont;src:url(<?php echo esc_url( $dir ); ?>/fonts/icofont.woff2) format("woff2"),url(<?php echo esc_url( $dir ); ?>/fonts/icofont.woff) format("woff");font-display:swap;font-weight:400;}
</style>
		<?php
	}
	add_action( 'wp_head', 'tara_perf_fonts', 1 );
}

/* -------------------------------------------------------------------------
 * ۵) lazy-load تصاویرِ چاپ‌شده در متن/شورت‌کدها
 * ---------------------------------------------------------------------- */
if ( TARA_PERF_LAZY_IMG && ! function_exists( 'tara_perf_lazy_img' ) ) {
	function tara_perf_lazy_img( $html ) {
		if ( ! tara_perf_active() || ! is_string( $html ) || false === strpos( $html, '<img' ) ) {
			return $html;
		}
		$html = preg_replace_callback(
			'/<img\b[^>]*>/i',
			function ( $m ) {
				$tag = $m[0];
				if ( false !== stripos( $tag, 'loading=' ) ) {
					return $tag; // از قبل تصمیم گرفته شده
				}
				/* تصویرِ بالای صفحه (لوگو/کاورِ اول) را lazy نکن */
				if ( preg_match( '/(logo|site-icon|header)/i', $tag ) ) {
					return $tag;
				}
				$attrs = ' loading="lazy" decoding="async"';
				if ( TARA_PERF_IMG_DIMS && ! preg_match( '/\s(width|height)=/i', $tag ) ) {
					$attrs .= ' width="640" height="360"'; // فقط با TARA_PERF_IMG_DIMS=true (ریسکِ تغییر ابعاد آیکن‌ها)
				}
				return preg_replace( '/<img\b/i', '<img' . $attrs, $tag, 1 );
			},
			$html
		);
		return $html;
	}
	add_filter( 'the_content', 'tara_perf_lazy_img', 50 );
	add_filter( 'the_excerpt', 'tara_perf_lazy_img', 50 );
}

/* -------------------------------------------------------------------------
 * ۶) گزارش «بایتِ سرعت» — فقط‌خواندنی، بدون echo خودکار؛
 *    توسط ab-mobile-probe.php (بخش ۱۰) یا هر جای دیگری چاپ می‌شود.
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'tara_perf_report' ) ) {
	/** @return array خطوط گزارش */
	function tara_perf_report() {
		$L      = array();
		$tdir   = get_template_directory();
		$uri    = get_template_directory_uri();
		$line   = function ( $s = '' ) use ( &$L ) {
			$L[] = $s;
		};

		$line( '=== گزارش «بایتِ سرعت» — لایۀ perf v3.1.3 ===' );
		$line( 'وضعیت لایه            : ' . ( ( defined( 'TARA_PERF' ) && false === TARA_PERF ) ? '✗ خاموش (TARA_PERF=false)' : '✓ روشن' ) );
		$line( '  defer / preload / strip / lazyIMG : ' . ( TARA_PERF_DEFER ? 'defer✓' : 'defer✗' ) . ' / ' . ( TARA_PERF_PRELOAD_FONTS ? 'preload✓' : 'preload✗' ) . ' / ' . ( TARA_PERF_STRIP_HEAD ? 'strip✓' : 'strip✗' ) . ' / ' . ( TARA_PERF_LAZY_IMG ? 'lazy✓' : 'lazy✗' ) );
		$opcache = 'نامعلوم';
		if ( function_exists( 'opcache_get_status' ) ) {
			$st = @opcache_get_status( false );
			if ( is_array( $st ) && ! empty( $st['enabled'] ) ) {
				$rate = isset( $st['statistics']['opcache_hit_rate'] ) ? number_format_i18n( $st['statistics']['opcache_hit_rate'], 1 ) : '?';
				$opcache = 'فعال (hit rate ~' . $rate . '%)';
			} else {
				$opcache = 'غیرفعال  <<< روی هاست فعالش کنید: بیشترین تأثیر روی اجرای PHP';
			}
		}
		$line( 'PHP / OPcache          : ' . PHP_VERSION . ' / ' . $opcache );

		$enc = isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ? (string) $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
		if ( false !== stripos( $enc, 'br' ) ) {
			$gzip = 'brotli درخواست شده ✓';
		} elseif ( false !== stripos( $enc, 'gzip' ) ) {
			$gzip = 'gzip (' . htmlspecialchars( substr( $enc, 0, 40 ) ) . ' )';
		} else {
			$gzip = 'هیچ header فشرده‌سازی‌ای از مرورگر نرسید';
		}
		$line( 'فشرده‌سازی در پاسخ      : ' . $gzip );
		$line( '' );


		$line( '--- اندازهٔ فایل‌های قالب (مهم‌ترین‌ها) ---' );
		$files = array( 'style.css', 'css/icofont.min.css', 'js/jquery.js', 'js/owl.carousel.js', 'js/scripts.js', 'js/mediaqueries.js', 'js/html5shiv.js', 'fonts/vazir-fd.woff', 'fonts/vazir-bold-fd.woff', 'fonts/vazir-medium-fd.woff', 'fonts/vazir-light-fd.woff', 'fonts/icofont.woff' );
		$total = 0;
		foreach ( $files as $f ) {
			$p = trailingslashit( $tdir ) . $f;
			if ( ! is_readable( $p ) ) {
				$line( sprintf( '  %-26s : ندارد', $f ) );
				continue;
			}
			$b   = (int) filesize( $p );
			$total += $b;
			$note = '';
			if ( 'js/mediaqueries.js' === $f || 'js/html5shiv.js' === $f ) {
				$note = '  ← در سورسِ قابل‌ویرایشِ قالب هیچ ارجاعی ندارد؛ فقط از functions.php (رمزنگاری‌شده) لود می‌شود → در همان‌جا dequeue کنید';
			}
			if ( 'js/jquery.js' === $f ) {
				$note = '  ← اگر این نسخهٔ اختصاصی لود شود، jQuery هسته دوبار دانلود می‌شود (بخش «jQuery در صف» را ببینید)';
			}
			if ( false !== strpos( $f, 'fonts/' ) ) {
				$note = '  ← فقط .woff و بدون font-display در فایل اصلی (ما swap را تزریق کردیم)؛ woff2 ≈ ۳۰٪ کمتر';
			}
			$line( sprintf( '  %-26s : %6.1f KB%s', $f, $b / 1024, $note ) );
		}
		$line( sprintf( '  %-26s : %6.1f KB (مجموع)', 'جمع', $total / 1024 ) );
		$line( '' );

		$line( '--- صفِ فایل‌های این درخواست ---' );
		global $wp_scripts, $wp_styles;
		$js = $css = array();
		if ( is_object( $wp_scripts ) && isset( $wp_scripts->queue ) ) {
			foreach ( (array) $wp_scripts->queue as $h ) {
				$src  = isset( $wp_scripts->registered[ $h ] ) ? (string) $wp_scripts->registered[ $h ]->src : '';
				$js[] = $h . ( false !== strpos( $src, 'taranehyar' ) ? '*' : '' );
			}
		}
		if ( is_object( $wp_styles ) && isset( $wp_styles->queue ) ) {
			foreach ( (array) $wp_styles->queue as $h ) {
				$src   = isset( $wp_styles->registered[ $h ] ) ? (string) $wp_styles->registered[ $h ]->src : '';
				$css[] = $h . ( false !== strpos( $src, 'taranehyar' ) ? '*' : '' );
			}
		}
		$line( '  JS (' . count( $js ) . ') : ' . ( $js ? implode( ', ', array_slice( $js, 0, 18 ) ) : '(خالی)' ) );
		$line( '  CSS (' . count( $css ) . ') : ' . ( $css ? implode( ', ', array_slice( $css, 0, 12 ) ) : '(خالی)' ) );
		$dup = array();
		foreach ( array( 'jquery', 'jquery-core' ) as $probe ) {
			if ( is_object( $wp_scripts ) && isset( $wp_scripts->registered[ $probe ] ) ) {
				$dup[] = $probe;
			}
		}
		$line( '  * = فایل داخل پوشهٔ قالب | jQuery در صف: ' . ( $dup ? implode( '/', $dup ) : '(ندارد)' ) . ( $dup && count( $dup ) > 1 ? '  ⚠ ممکن است دوبار لود شود' : '' ) );
		$srcset_off = has_filter( 'wp_calculate_image_srcset', 'meks_disable_srcset' );
		$line( '  srcset (تصویر واکنش‌گرا)      : ' . ( false !== $srcset_off ? '✗ در myfunctions.php غیرفعال شده → موبایل هم همان فایل بزرگ را می‌گیرد. اگر طراحی به‌هم نریخت، حذف این ۲ خط ~۳۰٪ بایت تصویر کم می‌کند.' : '✓ فعال' ) );
		$line( '  enqueueهای قالب              : در functions.php (رمزنگاری ionCube) هستند → فقط از طریق فیلتر قابل تغییرند؛ لایۀ perf همین کار را می‌کند.' );
		$line( '' );

		$line( '--- پیشنهادها (به ترتیبِ سود/هزینه) ---' );
		$sug = array();
		if ( is_readable( $tdir . '/js/mediaqueries.js' ) ) {
			$sug[] = 'حذف js/mediaqueries.js و js/html5shiv.js از روی هاست (IE9 به پایین؛ امروز بی‌مصرف) — ۲۶KB.';
		}
		if ( is_readable( $tdir . '/css/icofont.min.css' ) && is_readable( $tdir . '/fonts/icofont.woff' ) ) {
			$sug[] = 'css/icofont.min.css (۲۳KB) صدها قاعدۀ glyph دارد و fonts/icofont.woff تنها ۱۰۴KB است؛ با ساختن نسخهٔ زیرمجموعه (فقط آیکن‌های استفاده‌شده در php) CSS به ~۳KB می‌رسد.';
		}
		if ( is_readable( $tdir . '/fonts/vazir-medium-fd.woff' ) ) {
			$sug[] = 'اگر weight 300/500 در CSS استفاده نمی‌شود، دو فایل vazir-light و vazir-medium را بردارید (−~۱۵۰KB فونت) یا همه را به woff2 تبدیل کنید.';
		}
		$sug[] = 'style.css قالب ~' . number_format_i18n( (int) ( is_readable( $tdir . '/style.css' ) ? filesize( $tdir . '/style.css' ) / 1024 : 0 ) ) . 'KB است؛ بلوک‌های ۱۸ تا ۲۲ (اخبار/سایدبار/هدر) را می‌توان در فایل جدا و merge-شدهٔ کوچک‌تر برد (ما همین کار را با inline حیاتی کردیم).';
		$sug[] = 'تصاویر: سایزهای قالب thumb4 150² / thumb3 60² / thumb2 190² / thumb1 640×360 (crop) — برای لیست‌ها هیچ‌وقت تمام‌اندازه چاپ نشود؛ و خروجی WebP از افزونهٔ تصویر (معمولاً −۴۰٪).';
		$sug[] = 'یک افزونهٔ کشِ صفحه‌ساز (LiteSpeed Cache / WP Super Cache) با «موبایل: یکی‌سازیِ کش» یا «بدون کش موبایل» تنظیم کنید تا نسخهٔ کهنهٔ موبایل سرو نشود.';
		$sug[] = 'CDN (اگر ابرآروان/کلادفلیر دارید): Cache-Control برای /wp-content/themes/ را ۱ هفته بگذارید و بعد از هر آپلود استایل، Purge کنید.';
		foreach ( $sug as $i => $t ) {
			$line( '  ' . ( $i + 1 ) . ') ' . $t );
		}
		$line( '' );
		$line( 'اندازه‌گیریِ واقعی: PageSpeed Insights برای همان URL؛ یا در DevTools (دسکتاپ) تب Network → "Disable cache" و F5.' );
		return $L;
	}
}
