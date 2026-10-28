<?php
/**
 * جوان‌صدا ۳.۵.۲ — لایهٔ دادهٔ هنرمند (تک‌منبعِ حقیقت)
 * ==========================================================================
 * چرا این فایل؟
 *   در نسخهٔ مبنا (۳.۴.۸) قالب‌ها مقدارها را مستقیم با get_field() می‌خواندند.
 *   نتیجه: تا وقتی Advanced Custom Fields فعال است همه‌چیز کار می‌کند، ولی
 *     • اگر ACF غیرفعال/حذف شود ⇒ تصویر و شبکه‌های اجتماعی هنرمند «خالی» می‌شوند
 *       (در tag.php و taxonomy-artist.php هیچ fallback ی وجود نداشت)،
 *     • و اگر گروه فیلد در دیتابیس نباشد، مدیر هیچ راهی برای ثبت این مقدارها ندارد.
 *
 *   این فایل همان کلیدهای موجود را می‌خواند، ولی همیشه:
 *     ۱) اول get_field() (اگر ACF هست)،
 *     ۲) بعد get_term_meta() با همان کلید (داده‌های ثبت‌شده با فرم خودمان یا
 *        داده‌هایی که ACF ذخیره کرده ولی دیگر گروه فیلدش نیست).
 *   هیچ کلید تازه‌ای جایگزین کلید قدیمی نمی‌شود؛ فقط خواندن یکدست و مقاوم شد.
 *
 * کلیدهای رسمی (همان کلیدهای نسخهٔ مبنا):
 *   aimg2 · aimg2_id · artist_en · afacebook · atelegram · ainstagram · atwitter · ayoutube
 *   ab_bio_short · ab_bio_post
 *
 * این فایل هیچ هوکی برای ذخیره‌سازی ثبت نمی‌کند (ذخیره در inc/ab-terms-admin.php است)
 * و هیچ منطق شمارش بازدیدی نمی‌سازد؛ فقط «views»ِ موجود را جمع می‌زند.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==================================================================================
   ۱) کلیدها و خواندن مقدار
   ================================================================================== */

if ( ! function_exists( 'javan_seda_artist_field_keys' ) ) {
	/**
	 * فهرست کلیدهای رسمی + برچسب فارسی + نوع پاک‌سازی.
	 * نوع‌ها: url | text | textarea | id
	 */
	function javan_seda_artist_field_keys() {
		return apply_filters(
			'javan_seda_artist_field_keys',
			array(
				'aimg2'       => array( 'label' => 'تصویر پروفایل', 'type' => 'url' ),
				'aimg2_id'    => array( 'label' => 'شناسهٔ تصویر پروفایل', 'type' => 'id' ),
				'artist_en'   => array( 'label' => 'نام لاتین', 'type' => 'text' ),
				'afacebook'   => array( 'label' => 'فیسبوک', 'type' => 'url' ),
				'atelegram'   => array( 'label' => 'تلگرام', 'type' => 'url' ),
				'ainstagram'  => array( 'label' => 'اینستاگرام', 'type' => 'url' ),
				'atwitter'    => array( 'label' => 'توییتر / X', 'type' => 'url' ),
				'ayoutube'    => array( 'label' => 'یوتیوب', 'type' => 'url' ),
				'ab_bio_short' => array( 'label' => 'بیوگرافی کوتاه', 'type' => 'textarea' ),
				'ab_bio_post' => array( 'label' => 'نوشتهٔ بیوگرافی کامل', 'type' => 'id' ),
			)
		);
	}
}

if ( ! function_exists( 'javan_seda_artist_social_keys' ) ) {
	/** شبکه‌های اجتماعی با آیکون IcoFont قالب (همان آیکون‌های نسخهٔ مبنا) */
	function javan_seda_artist_social_keys() {
		return apply_filters(
			'javan_seda_artist_social_keys',
			array(
				'afacebook'  => array( 'label' => 'فیسبوک', 'icon' => 'icofont-facebook' ),
				'atelegram'  => array( 'label' => 'تلگرام', 'icon' => 'icofont-paper-plane' ),
				'ainstagram' => array( 'label' => 'اینستاگرام', 'icon' => 'icofont-instagram' ),
				'atwitter'   => array( 'label' => 'توییتر', 'icon' => 'icofont-twitter' ),
				'ayoutube'   => array( 'label' => 'یوتیوب', 'icon' => 'icofont-youtube-play' ),
			)
		);
	}
}

if ( ! function_exists( 'javan_seda_term' ) ) {
	/** ورودی می‌تواند WP_Term، شناسه، slug یا هیچ‌کدام باشد. */
	function javan_seda_term( $term, $taxonomy = '' ) {
		if ( $term instanceof WP_Term ) {
			return $term;
		}
		if ( is_numeric( $term ) ) {
			$t = get_term( (int) $term, $taxonomy ? $taxonomy : '' );
			return ( $t && ! is_wp_error( $t ) ) ? $t : null;
		}
		if ( is_string( $term ) && '' !== $term && $taxonomy ) {
			$t = get_term_by( 'slug', $term, $taxonomy );
			return ( $t && ! is_wp_error( $t ) ) ? $t : null;
		}
		return null;
	}
}

if ( ! function_exists( 'javan_seda_artist_field' ) ) {
	/**
	 * خواندن یک فیلد هنرمند با ترتیب: ACF → term meta → پیش‌فرض.
	 * مقدار برگشتی همیشه اسکالر (رشته) است تا در قالب‌ها بی‌خطر باشد.
	 */
	function javan_seda_artist_field( $term, $key, $default = '' ) {
		$term = javan_seda_term( $term );
		if ( ! $term ) {
			return $default;
		}

		$value = '';

		/* ۱) ACF — اگر نصب و فعال است. برای ترم، WP_Term معتبرترین ورودی است
		   (ACF 5.5+). برای نسخه‌های قدیمی‌تر، شکل رشتگی «term_<id>» هم امتحان می‌شود. */
		if ( function_exists( 'get_field' ) ) {
			$acf = get_field( $key, $term );
			if ( ( false === $acf || null === $acf || '' === $acf || array() === $acf ) && isset( $term->term_id ) ) {
				$acf2 = get_field( $key, $term->taxonomy . '_' . $term->term_id );
				if ( false !== $acf2 && null !== $acf2 && '' !== $acf2 && array() !== $acf2 ) {
					$acf = $acf2;
				}
			}
			if ( is_array( $acf ) ) {
				if ( isset( $acf['url'] ) ) {
					$acf = $acf['url'];
				} elseif ( isset( $acf['ID'] ) ) {
					$acf = $acf['ID'];
				} else {
					$acf = '';
				}
			}
			if ( is_scalar( $acf ) && '' !== trim( (string) $acf ) ) {
				$value = (string) $acf;
			}
		}

		/* ۲) term meta — همان کلید. منبعِ داده‌های فرمِ خودمان و مقدارهای بی‌گروه. */
		if ( '' === $value ) {
			$meta = get_term_meta( $term->term_id, $key, true );
			if ( is_array( $meta ) ) {
				if ( isset( $meta['url'] ) ) {
					$meta = $meta['url'];
				} elseif ( isset( $meta['ID'] ) ) {
					$meta = $meta['ID'];
				} else {
					$meta = '';
				}
			}
			if ( is_scalar( $meta ) && '' !== trim( (string) $meta ) ) {
				$value = (string) $meta;
			}
		}

		if ( '' === $value ) {
			return $default;
		}

		$defs = javan_seda_artist_field_keys();
		$type = isset( $defs[ $key ]['type'] ) ? $defs[ $key ]['type'] : 'text';
		switch ( $type ) {
			case 'url':
				$clean = esc_url_raw( trim( $value ) );
				/* اگر ACF فیلد را «تصویر» تعریف کرده باشد، مقدار = شناسهٔ پیوست است.
				   اینجا نگه می‌داریم تا javan_seda_image_url_from_value آن را به URL بدل کند. */
				if ( '' === $clean && ctype_digit( trim( $value ) ) ) {
					$clean = trim( $value );
				}
				$value = $clean;
				break;
			case 'id':
				$value = (string) absint( $value );
				break;
			case 'textarea':
				$value = trim( wp_strip_all_tags( $value ) );
				break;
			default:
				$value = trim( wp_strip_all_tags( $value ) );
		}

		return ( '' === $value ) ? $default : $value;
	}
}

/* ==================================================================================
   ۲) تصویر — یک منبع، چند نمایش
   ================================================================================== */

if ( ! function_exists( 'javan_seda_image_url_from_value' ) ) {
	/**
	 * مقدارهای رایجِ فیلد تصویر را به URL تبدیل می‌کند:
	 * شناسهٔ پیوست، آرایهٔ ACF (url/ID/sizes)، رشتهٔ عددی، URL کامل یا مسیر نسبی.
	 */
	function javan_seda_image_url_from_value( $value, $size = 'medium' ) {
		if ( is_array( $value ) ) {
			if ( ! empty( $value['sizes'][ $size ] ) ) {
				return esc_url_raw( $value['sizes'][ $size ] );
			}
			if ( ! empty( $value['url'] ) ) {
				return esc_url_raw( $value['url'] );
			}
			$value = isset( $value['ID'] ) ? $value['ID'] : ( isset( $value['id'] ) ? $value['id'] : '' );
		}

		if ( is_object( $value ) && isset( $value->ID ) ) {
			$value = $value->ID;
		}

		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '#^(https?:)?//#i', $value ) || 0 === strpos( $value, '/' ) ) {
			return esc_url_raw( $value );
		}

		if ( ctype_digit( $value ) && function_exists( 'wp_get_attachment_image_url' ) ) {
			$url = wp_get_attachment_image_url( (int) $value, $size );
			if ( $url ) {
				return esc_url_raw( $url );
			}
			$full = wp_get_attachment_url( (int) $value );
			return $full ? esc_url_raw( $full ) : '';
		}

		return '';
	}
}

if ( ! function_exists( 'javan_seda_artist_default_image_url' ) ) {
	/** تصویر پیش‌فرض هنرمند — همان منطق نسخهٔ مبنا (def-artist.webp، بعد def-singer.webp). */
	function javan_seda_artist_default_image_url() {
		if ( function_exists( 'javan_seda_artist_default_image' ) ) {
			return javan_seda_artist_default_image();
		}
		$dir = function_exists( 'get_template_directory' ) ? get_template_directory() : '';
		$uri = function_exists( 'get_template_directory_uri' ) ? get_template_directory_uri() : '';
		if ( $dir && is_readable( trailingslashit( $dir ) . 'images/def-artist.webp' ) ) {
			return trailingslashit( $uri ) . 'images/def-artist.webp';
		}
		return trailingslashit( $uri ) . 'images/def-singer.webp';
	}
}

if ( ! function_exists( 'javan_seda_artist_image_url' ) ) {
	/** URL تصویر هنرمند؛ اگر نبود، تصویر پیش‌فرض قالب. */
	function javan_seda_artist_image_url( $term, $size = 'medium' ) {
		$term = javan_seda_term( $term );
		$url  = '';
		if ( $term ) {
			$url = javan_seda_image_url_from_value( javan_seda_artist_field( $term, 'aimg2' ), $size );
			if ( '' === $url ) {
				$id  = absint( javan_seda_artist_field( $term, 'aimg2_id' ) );
				$url = $id ? javan_seda_image_url_from_value( $id, $size ) : '';
			}
		}
		return ( '' !== $url ) ? $url : javan_seda_artist_default_image_url();
	}
}

if ( ! function_exists( 'javan_seda_artist_has_image' ) ) {
	/** آیا هنرمند تصویرِ ثبت‌شده دارد؟ (برای پیام «تصویری ثبت نشده» در پیشخوان) */
	function javan_seda_artist_has_image( $term ) {
		$term = javan_seda_term( $term );
		if ( ! $term ) {
			return false;
		}
		if ( '' !== javan_seda_image_url_from_value( javan_seda_artist_field( $term, 'aimg2' ) ) ) {
			return true;
		}
		$id = absint( javan_seda_artist_field( $term, 'aimg2_id' ) );
		return (bool) ( $id && javan_seda_image_url_from_value( $id ) );
	}
}

/* ==================================================================================
   ۳) شبکه‌های اجتماعی
   ================================================================================== */

if ( ! function_exists( 'javan_seda_artist_socials' ) ) {
	/** فهرست [key,label,icon,url] فقط برای مواردی که مقدار دارند. */
	function javan_seda_artist_socials( $term ) {
		$term = javan_seda_term( $term );
		$out  = array();
		if ( ! $term ) {
			return $out;
		}
		foreach ( javan_seda_artist_social_keys() as $key => $meta ) {
			$url = javan_seda_artist_field( $term, $key );
			if ( '' === $url ) {
				continue;
			}
			$out[ $key ] = array(
				'label' => $meta['label'],
				'icon'  => $meta['icon'],
				'url'   => $url,
			);
		}
		return $out;
	}
}

/* ==================================================================================
   ۴) بیوگرافی کوتاه و لینک بیوگرافی کامل
   ================================================================================== */

if ( ! function_exists( 'javan_seda_artist_bio_short' ) ) {
	/**
	 * بیوگرافی کوتاه. اگر خالی بود، به توضیح ترم (همان جایی که نسخهٔ مبنا
	 * بیوگرافی را نشان می‌داد) برنمی‌گردیم؟ برمی‌گردیم — ولی فقط برای نمایشِ
	 * «معرفی»، نه برای ذخیره. این کار داده‌های قدیمی را از دست نمی‌دهد.
	 */
	function javan_seda_artist_bio_short( $term, $fallback_description = true ) {
		$term = javan_seda_term( $term );
		if ( ! $term ) {
			return '';
		}
		$bio = javan_seda_artist_field( $term, 'ab_bio_short' );
		if ( '' === $bio && $fallback_description ) {
			$bio = trim( wp_strip_all_tags( (string) term_description( $term->term_id, $term->taxonomy ) ) );
		}
		return $bio;
	}
}

if ( ! function_exists( 'javan_seda_artist_bio_post_id' ) ) {
	/** شناسهٔ نوشتهٔ «بیوگرافی کامل» — فقط عددِ معتبر. */
	function javan_seda_artist_bio_post_id( $term ) {
		return absint( javan_seda_artist_field( $term, 'ab_bio_post' ) );
	}
}

if ( ! function_exists( 'javan_seda_artist_bio_link' ) ) {
	/**
	 * لینک «بیوگرافی کامل» فقط در این حالت‌ها برمی‌گردد:
	 *   • نوشته منتشر شده باشد،
	 *   • یا منتشر نشده باشد ولی کاربرِ فعلی اجازهٔ خواندنش را داشته باشد
	 *     (پیش‌نویس/خصوصی برای مدیر و نویسنده — همان قاعدهٔ خودِ وردپرس).
	 * در غیر این صورت رشتهٔ خالی ⇒ دکمه‌ای چاپ نمی‌شود، پس لینک شکسته نداریم.
	 */
	function javan_seda_artist_bio_link( $term ) {
		$id = javan_seda_artist_bio_post_id( $term );
		if ( ! $id ) {
			return '';
		}
		$post = get_post( $id );
		if ( ! $post ) {
			return '';
		}
		if ( 'publish' === $post->post_status ) {
			$url = get_permalink( $post );
			return $url ? $url : '';
		}
		$readable = array( 'private', 'draft', 'pending', 'future' );
		if ( in_array( $post->post_status, $readable, true ) && function_exists( 'current_user_can' ) && current_user_can( 'read_post', $post->ID ) ) {
			$url = get_permalink( $post );
			return $url ? $url : '';
		}
		return '';
	}
}

/* ==================================================================================
   ۵) مجموع بازدید آثار هنرمند
   ----------------------------------------------------------------------------------
   منبعِ بازدید: همان متای «views» که افزونهٔ شمارش بازدید روی سرور می‌نویسد.
   این لایه هیچ‌وقت «views» را نمی‌نویسد/زیاد نمی‌کند؛ فقط جمع می‌زند.
   رابطهٔ اثر و هنرمند: تاکسونومی هنرمند (javan_seda_artist_tax()).
   اگر هنرمند هیچ اثری در تاکسونومی نداشت (نوشته‌های قدیمیِ فقط با متای «artist»)،
   جمع از روی نام هنرمند در همان متا محاسبه می‌شود تا آمار صفرِ نادرست ندهیم.
   ================================================================================== */

if ( ! function_exists( 'javan_seda_views_source_key' ) ) {
	/** کلید متای بازدید (همان کلید افزونهٔ سرور؛ قابل تغییر با فیلتر). */
	function javan_seda_views_source_key() {
		return (string) apply_filters( 'javan_seda_views_source_key', 'views' );
	}
}

if ( ! function_exists( 'javan_seda_artist_views_epoch' ) ) {
	/** شمارندهٔ نسخهٔ کش؛ با هر تغییرِ اثر/ترم یک واحد بالا می‌رود ⇒ همهٔ کش‌ها بی‌اعتبار. */
	function javan_seda_artist_views_epoch() {
		return (int) get_option( 'javan_seda_views_epoch', 1 );
	}
}

if ( ! function_exists( 'javan_seda_artist_views_flush' ) ) {
	function javan_seda_artist_views_flush() {
		$next = javan_seda_artist_views_epoch() + 1;
		update_option( 'javan_seda_views_epoch', $next, false );
	}
}

if ( ! function_exists( 'javan_seda_artist_total_views' ) ) {
	/**
	 * مجموع بازدید آثار منتشرشدهٔ هنرمند.
	 *
	 * @param int|WP_Term $term     هنرمند.
	 * @param bool        $use_cache پیش‌فرض true (کش ۱۵ دقیقه‌ای).
	 * @return int
	 */
	function javan_seda_artist_total_views( $term, $use_cache = true ) {
		$term = javan_seda_term( $term );
		if ( ! $term ) {
			return 0;
		}

		$ttl = (int) apply_filters( 'javan_seda_artist_views_ttl', 15 * MINUTE_IN_SECONDS, $term );
		$key = 'jv_av_' . (int) $term->term_id . '_' . javan_seda_artist_views_epoch();

		if ( $use_cache && $ttl > 0 ) {
			$cached = get_transient( $key );
			if ( false !== $cached && '' !== $cached && null !== $cached ) {
				return max( 0, (int) $cached );
			}
		}

		$total = javan_seda_artist_views_compute( $term );

		if ( $use_cache && $ttl > 0 ) {
			set_transient( $key, $total, $ttl );
		}
		return $total;
	}
}

if ( ! function_exists( 'javan_seda_artist_views_compute' ) ) {
	/** محاسبهٔ واقعی (بدون کش) — دو مسیر: تاکسونومی، بعد نامِ متای قدیمی. */
	function javan_seda_artist_views_compute( $term ) {
		global $wpdb;
		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return 0;
		}

		$views_key = javan_seda_views_source_key();
		$taxonomy  = isset( $term->taxonomy ) ? $term->taxonomy : ( function_exists( 'javan_seda_artist_tax' ) ? javan_seda_artist_tax() : 'artist' );

		/* ۱) مسیرِ دقیق: پیوندِ تاکسونومی
		   GREATEST(...,0) ⇒ مقدارهای منفی/ناخالص صفر حساب می‌شوند، نه عددِ منفی. */
		$sql = $wpdb->prepare(
			"SELECT COALESCE(SUM(GREATEST(CAST(pm.meta_value AS SIGNED), 0)), 0)
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
			 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			 WHERE pm.meta_key = %s
			   AND p.post_status = 'publish'
			   AND p.post_type = 'post'
			   AND tt.taxonomy = %s
			   AND tt.term_id = %d",
			$views_key,
			$taxonomy,
			(int) $term->term_id
		);
		$sum = max( 0, (int) $wpdb->get_var( $sql ) ); /* مقدار منفی/نامعتبر ⇒ صفر */
		if ( $sum > 0 ) {
			return $sum;
		}

		/* ۲) مسیرِ سازگاری: نوشته‌های قدیمی که فقط متای متنیِ artist دارند. */
		$name = isset( $term->name ) ? trim( (string) $term->name ) : '';
		if ( '' === $name ) {
			return 0;
		}
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT p.ID
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pa ON pa.post_id = p.ID
				 WHERE pa.meta_key = 'artist'
				   AND pa.meta_value LIKE %s
				   AND p.post_status = 'publish'
				   AND p.post_type = 'post'
				 LIMIT 5000",
				'%' . $wpdb->esc_like( $name ) . '%'
			)
		);
		if ( empty( $ids ) ) {
			return 0;
		}
		$ids = array_map( 'absint', (array) $ids );
		$ids = array_values( array_filter( array_unique( $ids ) ) );
		if ( empty( $ids ) ) {
			return 0;
		}

		$total = 0;
		foreach ( array_chunk( $ids, 500 ) as $chunk ) {
			$in  = implode( ',', $chunk );
			$sql = $wpdb->prepare(
				"SELECT COALESCE(SUM(GREATEST(CAST(meta_value AS SIGNED), 0)), 0)
				 FROM {$wpdb->postmeta}
				 WHERE meta_key = %s AND post_id IN ({$in})",
				$views_key
			);
			$total += max( 0, (int) $wpdb->get_var( $sql ) );
		}
		return max( 0, $total );
	}
}


if ( ! function_exists( 'javan_seda_strlen_utf8' ) ) {
	/** طولِ متن به نویسه (نه بایت) — مستقل از mbstring. */
	function javan_seda_strlen_utf8( $text ) {
		if ( function_exists( 'mb_strlen' ) ) {
			return (int) mb_strlen( (string) $text, 'UTF-8' );
		}
		$count = preg_match_all( '/./us', (string) $text, $m );
		return (int) $count;
	}
}

if ( ! function_exists( 'javan_seda_substr_utf8' ) ) {
	/** برشِ متن روی مرزِ نویسه (نه بایت) — مستقل از mbstring. */
	function javan_seda_substr_utf8( $text, $start, $length = null ) {
		$text = (string) $text;
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, (int) $start, $length, 'UTF-8' );
		}
		$chars = preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $chars ) ) {
			return (string) substr( $text, (int) $start, null === $length ? null : (int) $length );
		}
		$slice = array_slice( $chars, (int) $start, null === $length ? null : (int) $length );
		return implode( '', $slice );
	}
}

if ( ! function_exists( 'javan_seda_digits' ) ) {
	/** رقم‌های لاتین → فارسی. (نسخهٔ مستقل تا وابسته به فایل قالب نباشیم.) */
	function javan_seda_digits( $text ) {
		return str_replace(
			array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
			array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
			(string) $text
		);
	}
}

if ( ! function_exists( 'javan_seda_number_fa' ) ) {
	/** عدد با جداکنندهٔ هزارگانِ وردپرس + رقم فارسی. */
	function javan_seda_number_fa( $number ) {
		$number = max( 0, (int) $number );
		$text   = function_exists( 'number_format_i18n' ) ? number_format_i18n( $number ) : number_format( $number );
		return javan_seda_digits( $text );
	}
}

if ( ! function_exists( 'javan_seda_artist_views_html' ) ) {
	/**
	 * نشانِ «مجموع بازدید آثار» برای صفحهٔ هنرمند.
	 * اگر هنوز آماری نیست، چیزی چاپ نمی‌شود (رابط برای دادهٔ ناموجود فضا نمی‌گیرد).
	 */
	function javan_seda_artist_views_html( $term ) {
		$term = javan_seda_term( $term );
		if ( ! $term ) {
			return '';
		}
		$total = javan_seda_artist_total_views( $term );
		if ( $total < 1 ) {
			return '';
		}
		$label = apply_filters( 'javan_seda_artist_views_label', 'مجموع بازدید آثار', $term );
		return '<li class="ab-resume__views"><span class="ab-resume__num">'
			. esc_html( javan_seda_number_fa( $total ) )
			. '</span> ' . esc_html( $label ) . '</li>';
	}
}

/* ==================================================================================
   ۶) ابطال کش — با تغییرِ رابطه/انتشار/حذف
   ================================================================================== */

if ( ! function_exists( 'javan_seda_views_flush_on_terms' ) ) {
	function javan_seda_views_flush_on_terms() {
		javan_seda_artist_views_flush();
	}
}
add_action( 'set_object_terms', 'javan_seda_views_flush_on_terms' );
add_action( 'edited_term', 'javan_seda_views_flush_on_terms' );
add_action( 'delete_term', 'javan_seda_views_flush_on_terms' );

if ( ! function_exists( 'javan_seda_views_flush_on_post' ) ) {
	function javan_seda_views_flush_on_post( $post_id ) {
		$post = get_post( $post_id );
		if ( $post && 'revision' !== $post->post_type ) {
			javan_seda_artist_views_flush();
		}
	}
}
add_action( 'save_post', 'javan_seda_views_flush_on_post' );
add_action( 'deleted_post', 'javan_seda_views_flush_on_post' );
add_action( 'transition_post_status', 'javan_seda_views_flush_on_post' );

/* ==================================================================================
   ۷) دارایی‌های فرانت (CSS کوچک و مستقل؛ style.css دست‌نخورده می‌ماند)
   ================================================================================== */

if ( ! function_exists( 'javan_seda_352_assets' ) ) {
	function javan_seda_352_assets() {
		if ( ! function_exists( 'wp_enqueue_style' ) ) {
			return;
		}
		$dir = function_exists( 'get_template_directory' ) ? get_template_directory() : '';
		$uri = function_exists( 'get_template_directory_uri' ) ? get_template_directory_uri() : '';
		$ver = function_exists( 'wp_get_theme' ) ? wp_get_theme()->get( 'Version' ) : '3.5.2';

		if ( $dir && is_readable( trailingslashit( $dir ) . 'css/ab-352.css' ) ) {
			wp_enqueue_style( 'javan-seda-352', trailingslashit( $uri ) . 'css/ab-352.css', array(), $ver );
		}

		if ( function_exists( 'is_category' ) && is_category() && $dir && is_readable( trailingslashit( $dir ) . 'js/ab-cat-desc.js' ) ) {
			wp_enqueue_script( 'javan-seda-cat-desc', trailingslashit( $uri ) . 'js/ab-cat-desc.js', array(), $ver, true );
		}
	}
	add_action( 'wp_enqueue_scripts', 'javan_seda_352_assets', 30 );
}
