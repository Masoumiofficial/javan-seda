<?php
/**
 * جوان‌صدا — لایۀ «دیده‌شدن» (SEO بدون افزونه) — نسخهٔ 3.1.4
 * =============================================================================
 * از آنالیز (docs/AUDIT_2026-10.md) — سه سوراخ بزرگ:
 *   ۱) هیچ meta description در کل قالب چاپ نمی‌شود؛
 *   ۲) هیچ og:/twitter: نیست ⇒ اشتراک در تلگرام/اینستا بدون تصویر و توضیح می‌افتد
 *      (درحالی‌که منبع اصلی ترافیک سایت‌های موزیک، تلگرام است)؛
 *   ۳) sitemap در robots.txt به ۴۰۴ می‌رود و `/page/N/` کپیِ صفحهٔ اول است ⇒ محتوای
 *      تکراریِ this‌ایندکس‌شونده.
 * این لایه ۱ و ۲ را کامل و ۳ را «با noindex» بی‌خطر می‌کند (رفع ساختاریِ ۳ در home.php انجام شده).
 *
 * چه می‌کند؟
 *   • meta description هوشمند: چکیده ← اولین پاراگراف تمیز؛ برای آرشیوها توضیحِ دسته/هنرمند
 *   • Open Graph + Twitter Card (با og:image از تصویر شاخص، سایز thumb1 640×360 و fallback)
 *   • og:audio / og:audio:secure_url وقتی فیلد music128 پر است (پیش‌نمایشِ قابل‌پخش در شبکه‌ها)
 *   • article:published_time / og:updated_time (تازگی = سیگنال رتبه برای آهنگ‌های تازه)
 *   • JSON-LD BreadcrumbList (فایل inc/breadcrumbs.php فقط HTML می‌ساخت؛ ساختار داده نداشت)
 *   • «صفحۀ N» به title صفحاتِ صفحه‌بندی‌شده اضافه می‌شود (عنوان یکتا = رفع تکرار)
 *   • wp_robots: noindex+follow برای ۱) صفحۀ اولِ صفحه‌بندی‌شده ۲) آرشیو تگ با کم از ۲ نوشته
 *     ۳) آرشیو نویسنده ۴) جست‌وجو. هیچ‌جا follow بسته نمی‌شود، پس اعتبار لینک نمی‌سوزد.
 *
 * چه نمی‌کند؟ (عمداً)
 *   • هیچ افزونه‌ای را جایگزینِ «نقشۀ سایت» نمی‌کند؛ sitemap را وردپرس/افزونه می‌سازد
 *     (راهنمای اصلاح در AUDIT). فقط اگر canonical نبود، آن را اضافه می‌کند.
 *   • هیچ فیلتری روی the_content / template_include ندارد (قواعد پروژه).
 *   • title موجود را بازنویسی نمی‌کند (الان عنوان‌ها قابل قبول‌اند)؛ فقط بخش «صفحۀ N».
 *
 * خاموش‌کردن:  define( 'JAVANSEDA_SEO', false );
 * ریز:         JAVANSEDA_SEO_DESC (طول توضیح) · JAVANSEDA_SEO_OG · JAVANSEDA_SEO_ROBOTS
 *              JAVANSEDA_SEO_CRUMBS · JAVANSEDA_SEO_IMAGE (سایز تصویر og)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'JAVANSEDA_SEO' ) && false === JAVANSEDA_SEO ) {
	return;
}

if ( ! defined( 'JAVANSEDA_SEO_DESC' ) ) {
	define( 'JAVANSEDA_SEO_DESC', 155 );
}
if ( ! defined( 'JAVANSEDA_SEO_OG' ) ) {
	define( 'JAVANSEDA_SEO_OG', true );
}
if ( ! defined( 'JAVANSEDA_SEO_ROBOTS' ) ) {
	define( 'JAVANSEDA_SEO_ROBOTS', true );
}
if ( ! defined( 'JAVANSEDA_SEO_CRUMBS' ) ) {
	define( 'JAVANSEDA_SEO_CRUMBS', true );
}
if ( ! defined( 'JAVANSEDA_SEO_IMAGE' ) ) {
	define( 'JAVANSEDA_SEO_IMAGE', 'thumb1' );
}

/* -------------------------------------------------------------------------
 * ۲-الف) آیا افزونۀ سئو فعال است؟ (AIO / Yoast / Rank Math / SEOPress)
 *      اگر بله، لایه از چاپ meta/OG/canonical/BreadcrumbList دست می‌کشد تا
 *      تکراری نشود؛ فقط داده ساختاریافتهٔ موسیقی (که افزونه‌ها نمی‌سازند) می‌ماند.
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_seo_has_plugin' ) ) {
	function javan_seda_seo_has_plugin() {
		$const = array( 'WPSEO_VERSION', 'RANK_MATH_VERSION', 'AIOSEO_VERSION', 'SEOPRESS_VERSION', 'SEOPRO_VERSION' );
		foreach ( $const as $name ) {
			if ( defined( $name ) ) {
				return (string) constant( $name );
			}
		}
		$classes = array(
			'AIOSEO\\Plugin\\AIOSEO',
			'Yoast\\WPSEO\\Main',
			'RankMath\\Main',
			'Seopress',
		);
		foreach ( $classes as $cls ) {
			if ( class_exists( $cls ) ) {
				return $cls;
			}
		}
		return '';
	}
}

/** آدرسِ امنِ یک ترم/دسته — هیچ‌وقت (string) روی WP_Error نمی‌شود (PHP 8: کشنده) */
if ( ! function_exists( 'javan_seda_seo_term_url' ) ) {
	function javan_seda_seo_term_url( $term ) {
		if ( ! $term ) {
			return '';
		}
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			return '';
		}
		return (string) $link;
	}
}

/** آدرسِ امنِ دسته */
if ( ! function_exists( 'javan_seda_seo_cat_url' ) ) {
	function javan_seda_seo_cat_url( $cat_id ) {
		$link = get_category_link( (int) $cat_id );
		if ( is_wp_error( $link ) ) {
			return '';
		}
		return (string) $link;
	}
}
/** آیا در این درخواست خروجی SEO لازم است؟ */
if ( ! function_exists( 'javan_seda_seo_active' ) ) {
	function javan_seda_seo_active() {
		if ( is_admin() || is_feed() || is_trackback() || is_robots() || is_favicon() ) {
			return false;
		}
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return false;
		}
		if ( post_password_required() ) {
			return false;
		}
		return true;
	}
}

/** برچسب‌های «خواننده» و «آهنگ» از ACF (یا از عنوان)، برای ساختن توضیح یکدست */
if ( ! function_exists( 'javan_seda_seo_song_labels' ) ) {
	function javan_seda_seo_song_labels( $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : get_the_ID();
		$artist  = '';
		$song    = '';
		if ( function_exists( 'get_field' ) ) {
			$artist = (string) get_field( 'artist', $post_id );
			$song   = (string) get_field( 'song', $post_id );
		}
		$title = wp_strip_all_tags( get_the_title( $post_id ) );
		if ( '' === $song ) {
			$song = $title;
		}
		if ( '' === $artist ) {
			$terms = get_the_terms( $post_id, javan_seda_artist_tax() );
			if ( is_array( $terms ) && ! is_wp_error( $terms ) ) {
				$artist = (string) $terms[0]->name;
			}
		}
		return array( trim( $artist ), trim( $song ) );
	}
}

/** ساخت توضیحِ متا (بدون برچسب، بدون کدکوتاه، با حدِ طول) */
if ( ! function_exists( 'javan_seda_seo_description' ) ) {
	function javan_seda_seo_description() {
		$max = (int) JAVANSEDA_SEO_DESC;
		$txt = '';

		if ( is_singular() ) {
			$post_id = get_the_ID();
			$exc     = get_post_meta( $post_id, '_javan_seda_description', true ); // دستی (اختیاری)
			if ( '' !== (string) $exc ) {
				$txt = (string) $exc;
			} else {
				$raw = get_the_excerpt( $post_id );
				if ( '' === trim( (string) $raw ) ) {
					$p = get_post( $post_id );
					$raw = $p ? strip_shortcodes( (string) $p->post_content ) : '';
				}
				$txt = (string) $raw;
			}
			list( $artist, $song ) = javan_seda_seo_song_labels( $post_id );
			$txt = preg_replace( '/\s+/u', ' ', wp_strip_all_tags( str_replace( '&nbsp;', ' ', $txt ) ) );
			$txt = trim( $txt );
			/* اگر متن کوتاه/بی‌فایده بود، جملهٔ استانداردِ خودِ قالب را بساز */
			if ( mb_strlen( $txt, 'UTF-8' ) < 40 && '' !== $song ) {
				$txt = 'دانلود آهنگ ' . $song . ( '' !== $artist ? ' از ' . $artist : '' ) . ' با کیفیت ۱۲۸ و ۳۲۰ + متن ترانه و پخش آنلاین';
			}
		} elseif ( is_category() || is_tag() ) {
			$term = get_queried_object();
			$d    = $term ? (string) $term->description : '';
			$d    = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $d ) ) );
			$n    = ( isset( $GLOBALS['wp_query']->found_posts ) ) ? (int) $GLOBALS['wp_query']->found_posts : 0;
			if ( '' === $d ) {
				$d = 'آرشیو ' . ( $term ? $term->name : '' ) . ' — ' . $n . ' اثر برای دانلود با کیفیت ۱۲۸ و ۳۲۰';
			}
			$txt = $d;
		} elseif ( is_tax( javan_seda_artist_tax() ) ) {
			$term = get_queried_object();
			$n    = ( isset( $GLOBALS['wp_query']->found_posts ) ) ? (int) $GLOBALS['wp_query']->found_posts : 0;
			$txt  = 'صفحهٔ اختصاصی ' . ( $term ? $term->name : 'هنرمند' ) . ' — دانلود ' . $n . ' آهنگ و موزیک‌ویدیو، متن ترانه و شبکه‌های اجتماعی';
		} elseif ( is_search() ) {
			$txt = 'نتایج جست‌وجو برای «' . get_search_query() . '» در ' . get_bloginfo( 'name' );
		} elseif ( is_home() || is_front_page() ) {
			$txt = get_bloginfo( 'description' );
			if ( '' === trim( (string) $txt ) ) {
				$txt = 'تازه‌ترین آهنگ‌های ایرانی، موزیک‌ویدیو و آلبوم‌ها — پخش آنلاین و دانلود با کیفیت ۱۲۸ و ۳۲۰';
			}
		}

		$txt = html_entity_decode( (string) $txt, ENT_QUOTES, 'UTF-8' );
		$txt = str_replace( array( '"', "'", '[', ']', ']]>' ), ' ', $txt );
		$txt = trim( preg_replace( '/\s+/u', ' ', $txt ) );
		if ( function_exists( 'mb_strimwidth' ) ) {
			$txt = mb_strimwidth( $txt, 0, $max, '…', 'UTF-8' );
		} else {
			$txt = mb_substr( $txt, 0, $max, 'UTF-8' );
		}
		return $txt;
	}
}

/* -------------------------------------------------------------------------
 * ۱) meta description + ۲) Open Graph / Twitter + ۳) og:audio
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_seo_head' ) ) {
	function javan_seda_seo_head() {
		if ( ! javan_seda_seo_active() ) {
			return;
		}
		/* اگر افزونۀ سئو فعال است، توضیح/OG را او می‌سازد؛ دو بار چاپ نکنیم */
		if ( '' !== javan_seda_seo_has_plugin() && ! ( defined( 'JAVANSEDA_SEO_OVERRIDE_PLUGIN' ) && JAVANSEDA_SEO_OVERRIDE_PLUGIN ) ) {
			return;
		}
		$desc = javan_seda_seo_description();
		if ( '' !== $desc ) {
			echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		if ( ! JAVANSEDA_SEO_OG ) {
			return;
		}

		$title = wp_get_document_title();
		$url   = is_singular() ? get_permalink() : ( is_paged() ? '' : home_url( add_query_arg( array(), $GLOBALS['wp']->request ) ) );
		if ( '' === (string) $url ) {
			$url = (string) ( isset( $_SERVER['REQUEST_URI'] ) ? home_url( (string) $_SERVER['REQUEST_URI'] ) : home_url( '/' ) );
		}

		$img     = '';
		$img_w   = 0;
		$img_h   = 0;
		$audio   = '';
		$post_id = is_singular() ? get_the_ID() : 0;
		if ( $post_id ) {
			list( $artist, $song ) = javan_seda_seo_song_labels( $post_id );
			if ( '' === $artist && '' === $song ) {
				$artist = '';
				$song   = wp_strip_all_tags( get_the_title( $post_id ) );
			}
			$title = 'دانلود ' . trim( $song . ' از ' . $artist ) . ' | ' . get_bloginfo( 'name' );
			if ( has_post_thumbnail( $post_id ) ) {
				$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), JAVANSEDA_SEO_IMAGE );
				if ( $src ) {
					$img   = (string) $src[0];
					$img_w = (int) $src[1];
					$img_h = (int) $src[2];
				}
			}
			if ( '' === $img ) {
				$img = get_template_directory_uri() . '/images/nomusics.jpg';
			}
			if ( function_exists( 'get_field' ) ) {
				$audio = (string) get_field( 'music128', $post_id );
			}
		}

		$type = is_singular( 'post' ) ? 'article' : 'website';
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
		echo '<meta property="og:locale" content="fa_IR">' . "\n";
		echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( wp_strip_all_tags( (string) $title ) ) . '">' . "\n";
		if ( '' !== $desc ) {
			echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		if ( '' !== $img ) {
			echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
			if ( $img_w && $img_h ) {
				echo '<meta property="og:image:width" content="' . (int) $img_w . '"><meta property="og:image:height" content="' . (int) $img_h . '">' . "\n";
			}
			echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
			echo '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n";
		} else {
			echo '<meta name="twitter:card" content="summary">' . "\n";
		}
		echo '<meta name="twitter:title" content="' . esc_attr( wp_strip_all_tags( (string) $title ) ) . '">' . "\n";

		if ( $post_id ) {
			$pub = get_post_time( 'c', true, $post_id );
			$up  = get_post_modified_time( 'c', true, $post_id );
			if ( $pub ) {
				echo '<meta property="article:published_time" content="' . esc_attr( $pub ) . '">' . "\n";
			}
			if ( $up ) {
				echo '<meta property="og:updated_time" content="' . esc_attr( $up ) . '">' . "\n";
			}
			/* پیش‌نمایشِ قابل‌پخش در تلگرام/تلگرام‌وب (فقط وقتی آدرس فایل https باشد) */
			if ( '' !== $audio ) {
				$audio = trim( (string) $audio );
				if ( 0 === strpos( $audio, 'https://' ) ) {
					echo '<meta property="og:audio" content="' . esc_url( $audio ) . '">' . "\n";
					echo '<meta property="og:audio:type" content="audio/mpeg">' . "\n";
				}
			}
		}
	}
	add_action( 'wp_head', 'javan_seda_seo_head', 4 );
}

/* -------------------------------------------------------------------------
 * ۴) canonical — اگر هستۀ وردپرس/افزونه‌ای آن را حذف کرده بود، جبران شود
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_seo_canonical_backstop' ) ) {
	function javan_seda_seo_canonical_backstop() {
		if ( ! javan_seda_seo_active() ) {
			return;
		}
		/* وردپرس با rel_canonical آن را چاپ می‌کند؛ این فقط یک تورِ نجات است:
		   اگر افزونۀ سئوی شما canonical را برداشته، لینک جفت‌وشوردها بسته می‌شود. */
		if ( has_action( 'wp_head', 'rel_canonical' ) ) {
			return;
		}
		$u = '';
		if ( is_singular() ) {
			$u = (string) get_permalink();
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$t = get_queried_object();
			if ( $t ) {
				$u = javan_seda_seo_term_url( $t );
			}
		}
		if ( $u && ! is_wp_error( $u ) ) {
			echo '<link rel="canonical" href="' . esc_url( $u ) . '">' . "\n";
		}
	}
	add_action( 'wp_head', 'javan_seda_seo_canonical_backstop', 5 );
}

/* -------------------------------------------------------------------------
 * ۵) BreadcrumbList (JSON-LD)
 * ---------------------------------------------------------------------- */
if ( JAVANSEDA_SEO_CRUMBS && ! function_exists( 'javan_seda_seo_breadcrumbs' ) ) {
	function javan_seda_seo_breadcrumbs() {
		if ( ! javan_seda_seo_active() || ! ( is_singular() || is_category() || is_tag() || is_tax() ) ) {
			return;
		}
		/* افزونۀ سئو خودش BreadcrumbList می‌سازد ⇒ تکراری نشود */
		if ( '' !== javan_seda_seo_has_plugin() && ! ( defined( 'JAVANSEDA_SEO_OVERRIDE_PLUGIN' ) && JAVANSEDA_SEO_OVERRIDE_PLUGIN ) ) {
			return;
		}
		$items = array(
			array( 'name' => get_bloginfo( 'name' ), 'item' => home_url( '/' ) ),
		);
		if ( is_singular() ) {
			$cats = get_the_category();
			if ( is_array( $cats ) && $cats ) {
				$items[] = array(
					'name' => wp_strip_all_tags( $cats[0]->name ),
					'item' => javan_seda_seo_cat_url( $cats[0]->term_id ),
				);
			}
			$singers = get_the_terms( get_the_ID(), javan_seda_artist_tax() );
			if ( is_array( $singers ) && $singers ) {
				$items[] = array(
					'name' => wp_strip_all_tags( $singers[0]->name ),
					'item' => javan_seda_seo_term_url( $singers[0] ),
				);
			}
			$items[] = array( 'name' => wp_strip_all_tags( get_the_title() ), 'item' => '' );
		} else {
			$t = get_queried_object();
			$items[] = array(
				'name' => $t ? wp_strip_all_tags( $t->name ) : '',
				'item' => '',
			);
		}
		$list  = array();
		$count = count( $items );
		foreach ( $items as $i => $it ) {
			$one = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => (string) $it['name'],
			);
			if ( '' !== (string) $it['item'] && $i + 1 < $count ) {
				$one['item'] = (string) $it['item'];
			}
			$list[] = $one;
		}
		echo '<script type="application/ld+json">'
			. wp_json_encode(
				array(
					'@context'    => 'https://schema.org',
					'@type'       => 'BreadcrumbList',
					'itemListElement' => $list,
				),
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			)
			. '</script>' . "\n";
	}
	add_action( 'wp_head', 'javan_seda_seo_breadcrumbs', 8 );
}

/* -------------------------------------------------------------------------
 * ۶) title یکتا برای صفحاتِ صفحه‌بندی‌شده
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_seo_title_parts' ) ) {
	function javan_seda_seo_title_parts( $parts ) {
		/* هستۀ وردپرس خودش برای خیلی از نماها کلید 'page' را پر می‌کند (در فارسی: «صفحه ۲»);
		   اگر پر بود دست نمی‌زنیم تا دوباره نشود. کلیدهای ناشناخته در title چاپ نمی‌شوند،
		   پس از همان کلید 'page' استفاده می‌کنیم ⇒ عنوانِ یکتا برای /page/N/ و بقیۀ صفحه‌های شماره‌دار. */
		if ( is_paged() && empty( $parts['page'] ) ) {
			$n             = (int) max( get_query_var( 'paged' ), get_query_var( 'page' ) );
			$parts['page'] = 'صفحۀ ' . $n;
		}
		return $parts;
	}
	add_filter( 'document_title_parts', 'javan_seda_seo_title_parts', 20 );
}

/* -------------------------------------------------------------------------
 * ۷) wp_robots — بستنِ صفحاتِ تکراری/نازک (فقط noindex، follow همیشه باز)
 * ---------------------------------------------------------------------- */
if ( JAVANSEDA_SEO_ROBOTS && ! function_exists( 'javan_seda_seo_robots' ) ) {
	function javan_seda_seo_robots( $robots ) {
		/* ۱) کپی‌های صفحهٔ اول: /page/2/ ، /page/3/ … (تا زمانی که ساختار بایت را عوض کنید)
		   با دور ۱۱ home.php این صفحه‌ها به لوپ استاندارد می‌روند؛ noindex هم می‌ماند
		   چون عنوان/توضیحشان با صفحهٔ ۱ مشترک است. */
		if ( ( is_home() || is_front_page() ) && is_paged() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		/* ۲) آرشیو برچسب‌های نازک (یک‌دو نوشته) ⇒ محتوای تکراری */
		if ( is_tag() ) {
			$q     = $GLOBALS['wp_query'];
			$total = ( isset( $q->found_posts ) ) ? (int) $q->found_posts : 0;
			if ( $total < (int) apply_filters( 'javanseda_seo_thin_tag_min', 2 ) ) {
				$robots['noindex'] = true;
				$robots['follow']  = true;
			}
		}
		/* ۳) آرشیو نویسنده: سایت صفحهٔ پروفایل نویسنده ندارد */
		if ( is_author() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		/* ۴) بایگانیِ تاریخ: بی‌محتوا */
		if ( is_date() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}
	add_filter( 'wp_robots', 'javan_seda_seo_robots', 20 );
}

/* -------------------------------------------------------------------------
 * ۸) «نظارتِ سئو» — فقط‌خواندنی؛ در ab-mobile-probe.php (بخش ۱۱) چاپ می‌شود
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_seo_report' ) ) {
	function javan_seda_seo_report() {
		$L = array();
		$line = function ( $s = '' ) use ( &$L ) {
			$L[] = $s;
		};
		$line( '=== لایۀ «دیده‌شدن» v3.1.4 ===' );
		$line( 'لایه                    : ' . ( ( defined( 'JAVANSEDA_SEO' ) && false === JAVANSEDA_SEO ) ? '✗ خاموش' : '✓ روشن' ) . ' | og=' . ( JAVANSEDA_SEO_OG ? 'on' : 'off' ) . ' robots=' . ( JAVANSEDA_SEO_ROBOTS ? 'on' : 'off' ) . ' crumbs=' . ( JAVANSEDA_SEO_CRUMBS ? 'on' : 'off' ) );
		$line( 'توضیحِ ساختنیِ این صفحه  : «' . mb_substr( javan_seda_seo_description(), 0, 90, 'UTF-8' ) . '»' );
		$line( 'افزونۀ سئو شناسایی‌شده   : ' . ( ( '' !== javan_seda_seo_has_plugin() ) ? javan_seda_seo_has_plugin() . '  ← لایه از چاپ meta/OG/BreadcrumbList عقب کشید' : 'هیچ  ← لایه خودش meta/OG را می‌سازد' ) );
		$line( 'core sitemap             : ' );
		foreach ( array( '/wp-sitemap.xml', '/sitemap_index.xml', '/sitemap.xml' ) as $sm ) {
			$code = '—';
			if ( function_exists( 'wp_remote_head' ) ) {
				$r = wp_remote_head( home_url( $sm ), array( 'timeout' => 4, 'sslverify' => false, 'user-agent' => 'ahangban-probe' ) );
				if ( is_array( $r ) && ! is_wp_error( $r ) ) {
					$code = (string) wp_remote_retrieve_response_code( $r );
				} elseif ( is_wp_error( $r ) ) {
					$code = 'خطا(' . $r->get_error_message() . ')';
				}
			}
			$line( '  ' . $sm . ' → ' . $code . ( '200' === $code ? ' ✓' : ' ✗' ) );
		}
		/* robots.txt و sitemap اعلامی‌اش */
		$rb = home_url( '/robots.txt' );
		if ( function_exists( 'wp_remote_get' ) ) {
			$r  = wp_remote_get( $rb, array( 'timeout' => 4, 'sslverify' => false, 'user-agent' => 'ahangban-probe' ) );
			$bd = ( is_array( $r ) && ! is_wp_error( $r ) ) ? (string) wp_remote_retrieve_body( $r ) : '';
			$line( 'robots.txt                : ' . ( '' === $bd ? 'خوانده نشد' : 'خوانده شد' ) );
			if ( preg_match_all( '/^\s*Sitemap:\s*(\S+)/mi', $bd, $mm ) ) {
				foreach ( $mm[1] as $u ) {
					$c = '—';
					if ( function_exists( 'wp_remote_head' ) ) {
						$rr = wp_remote_head( $u, array( 'timeout' => 4, 'sslverify' => false, 'user-agent' => 'ahangban-probe' ) );
						if ( is_array( $rr ) && ! is_wp_error( $rr ) ) {
							$c = (string) wp_remote_retrieve_response_code( $rr );
						}
					}
					$line( '  Sitemap اعلامی: ' . $u . ' → ' . $c . ( '200' === $c ? ' ✓' : ' ✗ یا این خط را از robots.txt بردارید، یا sitemap را بسازید' ) );
				}
			} else {
				$line( '  (هیچ خط Sitemap: در robots.txt نیست)' );
			}
		}
		/* دامنه: http/www در خروجیِ صفحهٔ اصلی؟ */
		$hp = home_url( '/' );
		if ( function_exists( 'wp_remote_get' ) ) {
			$r = wp_remote_get( $hp, array( 'timeout' => 6, 'sslverify' => false, 'user-agent' => 'Mozilla/5.0 (ahangban-probe)' ) );
			if ( is_array( $r ) && ! is_wp_error( $r ) ) {
				$b = (string) wp_remote_retrieve_body( $r );
				$n1 = preg_match_all( '#http://www\.#i', $b );
				$n2 = preg_match_all( '#="http://' . preg_quote( (string) parse_url( home_url( '/' ), PHP_URL_HOST ), '#' ) . '#i', $b );
				$line( 'لینک/تصویرِ ناامن در خانه  : http://www = ' . $n1 . ' | http بدون www = ' . $n2 . ( ( $n1 + $n2 ) ? '  ✼ با wp search-replace به ' . home_url( '/' ) . ' تبدیل کنید (راهنما: AUDIT_2026-10.md بند ۲)' : ' ✓' ) );
				$line( '  og: در HTML خانه          : ' . ( false !== strpos( $b, 'property="og:' ) ? '✓ هست' : '✗ نیست' ) . ' | description: ' . ( false !== strpos( $b, 'name="description"' ) ? '✓ هست' : '✗ نیست' ) . ' | canonical: ' . ( false !== strpos( $b, 'rel=\'canonical\'' ) || false !== strpos( $b, 'rel="canonical"' ) ? '✓' : '✗' ) );
				$tl = preg_match_all( '#https?://(web\.telegram\.org|instagram\.com)/["\']#i', $b );
				$line( '  لینک‌های تلگرام/اینستای عمومی : ' . ( $tl ? $tl . ' مورد ✗ (باید آدرس کانال شما باشد، نه صفحهٔ خودِ تلگرام)' : '✓ موردی نیست' ) );
			} else {
				$line( '  ریکوئست به ' . $hp . ' ناموفق بود (روی بعضی هاست‌ها درخواست بیرونی بسته است)' );
			}
		}
		$line( '  نکته: کاوشگر را با ?diag=1 باز کنید تا همین گزارش + جدول فایل‌ها را یک‌جا ببینید.' );
		return $L;
	}
}
