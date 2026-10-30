<?php
/**
 * ترانه یار ۳.۵.۳ — کارتِ هنرمند در ریل «هنرمندان منتخب» صفحهٔ اصلی
 * =============================================================================
 * درخواست: هنرمندانِ آخرِ صفحهٔ اصلی دقیقاً مثل کارتِ آهنگ‌ها رفتار کنند —
 * روی تصویر «مات» شوند و «مجموع بازدید آثار» روی عکس بیاید و نام زیرِ عکس بماند.
 *
 * چرا فایل جدا؟
 *   `inc/artist_slider.php` مارکاپِ ثابتِ قالب مادر است؛ اگر همان‌جا شرط و لایه
 *   اضافه کنیم، هر تغییرِ آیندهٔ اسلایدر دوباره همه‌چیز را به‌هم می‌ریزد. این لایه
 *   فقط یک تابعِ خروجی می‌دهد و اسلایدر اگر این تابع نبود، مارکاپِ قدیمی را چاپ
 *   می‌کند ⇒ سازگاریِ کامل با نصب‌های نیمه‌کاره.
 *
 * داده‌ها:
 *   • نام/آدرس/تصویر از همان تنظیماتِ قبلی می‌آید (`siing_t` در تنظیمات قالب).
 *     هیچ کلید تازه‌ای اضافه نمی‌شود و چیزی جابه‌جا نمی‌شود.
 *   • مجموع بازدید از ترمِ هنرمندِ متناظر خوانده می‌شود (تابعِ آزمون‌شدهٔ
 *     `tara_artist_total_views`). تطبیق اول با نامکِ آدرس، بعد با نامِ هنرمند؛
 *     اگر هیچ ترمی پیدا نشد، کارت بدون شماره چاپ می‌شود (هیچ عددِ حدسی نمی‌سازیم).
 *   • شمارنده همان متای `views` است؛ این فایل هیچ‌وقت چیزی نمی‌نویسد.
 *
 * خاموشیِ شماره‌ها:  add_filter( 'tara_artist_card_stats', '__return_false' );
 * =============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_artist_card_term' ) ) {
	/**
	 * پیدا کردنِ ترمِ هنرمند برای یک آیتمِ ریل.
	 *
	 * ترتیب: نامکِ انتهای آدرس → نامِ دقیق → نامکِ ساخته‌شده از نام.
	 *
	 * @param string $title نامِ نمایشی.
	 * @param string $url   آدرسِ صفحهٔ هنرمند.
	 * @return WP_Term|null
	 */
	function tara_artist_card_term( $title, $url = '' ) {
		$taxonomy = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
		if ( ! $taxonomy || ! function_exists( 'get_term_by' ) ) {
			return null;
		}

		$title = trim( wp_strip_all_tags( (string) $title ) );
		$url   = trim( (string) $url );

		/* ۱) نامک از آدرس: /artist/mohsen-yeganeh/ → mohsen-yeganeh */
		$slug = '';
		if ( '' !== $url ) {
			$path = function_exists( 'wp_parse_url' ) ? wp_parse_url( $url, PHP_URL_PATH ) : parse_url( $url, PHP_URL_PATH );
			if ( is_string( $path ) && '' !== $path ) {
				$parts = array_values( array_filter( explode( '/', trim( $path, '/' ) ), 'strlen' ) );
				if ( ! empty( $parts ) ) {
					$slug = (string) end( $parts );
					/* اگر آدرس با query یا پسوند تمام شده باشد، آخرین بخش را تمیز می‌کنیم */
					$slug = preg_replace( '/\.[a-z0-9]{2,5}$/i', '', $slug );
				}
			}
		}
		if ( '' !== $slug ) {
			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term;
			}
		}

		/* ۲) نامِ دقیق */
		if ( '' !== $title ) {
			$term = get_term_by( 'name', $title, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term;
			}
			/* ۳) نامکِ ساخته‌شده از نام (وقتی آدرس دستی/کوتاه است) */
			if ( function_exists( 'sanitize_title' ) ) {
				$term = get_term_by( 'slug', sanitize_title( $title ), $taxonomy );
				if ( $term && ! is_wp_error( $term ) ) {
					return $term;
				}
			}
		}

		return null;
	}
}

if ( ! function_exists( 'tara_artist_card_views' ) ) {
	/**
	 * مجموع بازدیدِ یک کارت — با حافظهٔ درون‌درخواستی تا یک ترم دو بار محاسبه نشود.
	 *
	 * @param WP_Term|null $term ترمِ هنرمند.
	 * @return int
	 */
	function tara_artist_card_views( $term ) {
		static $memo = array();
		if ( ! $term || empty( $term->term_id ) || ! function_exists( 'tara_artist_total_views' ) ) {
			return 0;
		}
		$id = (int) $term->term_id;
		if ( isset( $memo[ $id ] ) ) {
			return $memo[ $id ];
		}
		$memo[ $id ] = max( 0, (int) tara_artist_total_views( $term ) );
		return $memo[ $id ];
	}
}

if ( ! function_exists( 'tara_artist_card_stats_on' ) ) {
	/** آیا شمارهٔ بازدید روی کارتِ هنرمند چاپ شود؟ (همان کلیدِ کارت‌های آهنگ) */
	function tara_artist_card_stats_on() {
		$on = true;
		if ( defined( 'TARA_CARD_STATS' ) && ! TARA_CARD_STATS ) {
			$on = false;
		}
		if ( $on && function_exists( 'tara_ui_on' ) ) {
			$on = (bool) tara_ui_on( 'ab_card_stats', true, 'TARA_CARD_STATS' );
		}
		return (bool) apply_filters( 'tara_artist_card_stats', $on );
	}
}

if ( ! function_exists( 'tara_artist_card_number' ) ) {
	/** عددِ فارسی با جداکنندهٔ هزارگان (مستقل از افزونه‌ها). */
	function tara_artist_card_number( $number ) {
		if ( function_exists( 'tara_number_fa' ) ) {
			return tara_number_fa( $number );
		}
		return number_format_i18n( (int) $number );
	}
}

if ( ! function_exists( 'tara_artist_card' ) ) {
	/**
	 * چاپِ کارتِ هنرمند (رشتهٔ HTML امن).
	 *
	 * ساختار (همان چیدمانِ قبلی + لایهٔ آمار):
	 *   <a class="ab-artist-card">
	 *     <span class="ab-artist-card__media"><img>…<span class="ab-card-stats">…</span></span>
	 *     <span class="ab-artist-card__name">نام</span>
	 *   </a>
	 * نام همیشه *بیرون* از تصویر و زیرِ آن است؛ لایهٔ آمار فقط روی عکس می‌نشیند و
	 * `pointer-events:none` دارد، پس لینکِ کارت هیچ‌وقت قربانی نمی‌شود.
	 *
	 * @param array $item آیتمِ تنظیمات (title, siing_u, siing_img).
	 * @return string
	 */
	function tara_artist_card( $item ) {
		if ( ! is_array( $item ) ) {
			return '';
		}

		$title = isset( $item['title'] ) ? trim( wp_strip_all_tags( (string) $item['title'] ) ) : '';
		$url   = isset( $item['siing_u'] ) ? trim( (string) $item['siing_u'] ) : '';
		$image = isset( $item['siing_img'] ) ? trim( (string) $item['siing_img'] ) : '';

		if ( '' === $url || '' === $image ) {
			return '';
		}

		/* تصویر: اگر در تنظیمات شناسهٔ پیوست ذخیره شده باشد هم درست کار می‌کند */
		if ( ctype_digit( $image ) && function_exists( 'tara_image_url_from_value' ) ) {
			$converted = tara_image_url_from_value( $image, 'thumb4' );
			if ( '' !== $converted ) {
				$image = $converted;
			}
		}

		$term    = tara_artist_card_term( $title, $url );
		$views   = tara_artist_card_views( $term );
		$alt     = '' !== $title ? $title : ( $term && isset( $term->name ) ? (string) $term->name : '' );
		$has_num = ( $views > 0 && tara_artist_card_stats_on() );

		$out  = '<a class="ab-artist-card" rel="noreferrer noopener" href="' . esc_url( $url ) . '">';
		$out .= '<span class="ab-artist-card__media">';
		$out .= '<img src="' . esc_url( $image ) . '" width="150" height="150" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async">';
		if ( $has_num ) {
			$out .= '<span class="ab-card-stats"><span class="ab-card-stats__in"><span class="ab-cs-num">'
				. '<i class="icofont-eye-alt" aria-hidden="true"></i>' . esc_html( tara_artist_card_number( $views ) )
				. '<span class="screen-reader-text">مجموع بازدید آثار</span></span></span></span>';
		}
		$out .= '</span>';
		$out .= '<span class="ab-artist-card__name">' . esc_html( $title ) . ( $term && function_exists( 'tara_artist_verified_badge' ) ? tara_artist_verified_badge( $term, array( 'size' => 'sm' ) ) : '' ) . '</span>';
		$out .= '</a>';

		return $out;
	}
}
