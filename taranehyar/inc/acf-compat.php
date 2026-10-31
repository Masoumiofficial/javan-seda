<?php
/**
 * ترانه یار — لایهٔ سازگاری ACF (نسخهٔ 3.1.0)
 * ==========================================================================
 * علت وجودی:  single.php برای «کاور/متن ترانه/لینک‌های دانلود» مستقیماً
 * get_field() / the_field() / have_rows() را صدا می‌زند. اگر افزونهٔ
 * Advanced Custom Fields روی هاست غیرفعال/حذف شود، اولین فراخوانیِ بی‌محافظ
 * یک خطای کشنده (Call to undefined function) می‌سازد و صفحه دقیقاً وسط
 * <div class="single_content_box"> نیمه‌کاره می‌ماند ⇒ «باکس هست، محتوا نیست».
 *
 * این فایل:
 *   • هیچ کاری نمی‌کند اگر ACF نصب و فعال باشد (تست با ACF_VERSION / class ACF).
 *   • در غیر این صورت، توابع را با همان امضا ولی با منبع «post meta» تعریف می‌کند
 *     تا صفحه به‌جای مرگ، داده‌های ذخیره‌شده در wp_postmeta را نشان دهد.
 *   • هیچ هوکی ثبت نمی‌کند، چیزی ذخیره نمی‌کند و چیزی را بازنویسی نمی‌کند.
 *
 * محدودیت: مقادیر series (تکرارشونده) فقط در صورتی پشتیبانی می‌شوند که آرایهٔ
 * wp_postmeta باشند؛ در غیر این صورت have_rows() «false» برمی‌گرداند و باکس
 * آلبوم به‌سادگی چاپ نمی‌شود (نه خطا).
 *
 * پالت سازمانی/تاکسونومی/هوک سراسری: دست‌نخورده. بدون گرادینت.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* اگر ACF فعال است، هیچ‌چیز تعریف نمی‌کنیم. */
if ( defined( 'ACF_VERSION' ) || class_exists( 'ACF' ) || function_exists( 'acf_get_value' ) ) {
	return;
}

if ( ! function_exists( 'tara_acf_meta_value' ) ) {
	/** خواندن مقدار فیلد از post meta + fallback به جدول Options برای فیلدهای سراسری. */
	function tara_acf_meta_value( $key, $post_id = false, $is_array = false ) {
		if ( false === $post_id || null === $post_id || '' === $post_id ) {
			if ( function_exists( 'get_the_ID' ) ) {
				$post_id = get_the_ID();
			}
			if ( ! $post_id && isset( $GLOBALS['post']->ID ) ) {
				$post_id = (int) $GLOBALS['post']->ID;
			}
		}
		$val = ( $post_id && function_exists( 'get_post_meta' ) ) ? get_post_meta( (int) $post_id, (string) $key, true ) : '';

		/* همان رفتاری که ACF برای گزینهٔ «default_value / options» دارد */
		if ( ( '' === $val || array() === $val ) && function_exists( 'get_option' ) ) {
			$alt = get_option( 'acf_options_' . $key, '' );
			if ( '' !== $alt ) {
				$val = $alt;
			}
		}
		if ( $is_array ) {
			return is_array( $val ) ? $val : array();
		}
		if ( is_array( $val ) ) {
			return '';
		}
		return is_scalar( $val ) ? (string) $val : '';
	}
}

if ( ! function_exists( 'get_field' ) ) {
	function get_field( $key, $post_id = false, $format_value = true ) {
		unset( $format_value ); // در حالت سازگاری، پاک‌سازی لازم نیست
		return tara_acf_meta_value( $key, $post_id, false );
	}
}

if ( ! function_exists( 'get_sub_field' ) ) {
	function get_sub_field( $key ) {
		$ctx = isset( $GLOBALS['tara_acf_row'] ) ? $GLOBALS['tara_acf_row'] : null;
		if ( is_array( $ctx ) && array_key_exists( $key, $ctx ) && is_scalar( $ctx[ $key ] ) ) {
			return (string) $ctx[ $key ];
		}
		return '';
	}
}

if ( ! function_exists( 'the_field' ) ) {
	function the_field( $key, $post_id = false, $format_value = true ) {
		unset( $format_value );
		$v = get_field( $key, $post_id );
		echo is_string( $v ) ? $v : ''; // همان رفتار ACF: خروجی خام (HTML/URL)
	}
}

if ( ! function_exists( 'the_sub_field' ) ) {
	function the_sub_field( $key ) {
		$v = get_sub_field( $key );
		echo is_string( $v ) ? $v : '';
	}
}

if ( ! function_exists( 'have_rows' ) ) {
	/**
	 * شروع/ادامهٔ حلقهٔ سطرهای یک فیلد تکرارشونده.
	 * @return bool  true تا وقتی سطر بعدی وجود دارد
	 */
	function have_rows( $key, $post_id = false ) {
		static $pointer = array();
		$state_key = (string) $key . ':' . ( $post_id ? (int) $post_id : (function_exists('get_the_ID') ? (int) get_the_ID() : 0) );

		$rows = tara_acf_meta_value( $key, $post_id, true );
		if ( ! $rows ) {
			return false;
		}
		if ( ! isset( $pointer[ $state_key ] ) ) {
			$pointer[ $state_key ] = -1;
		}
		/* هر فراخوانیِ have_rows بعد از the_row() یک سطر جلو می‌رود */
		if ( ! empty( $GLOBALS['tara_acf_row_end'] ) && $GLOBALS['tara_acf_row_end'] === $state_key ) {
			$pointer[ $state_key ]++;
			$GLOBALS['tara_acf_row_end'] = '';
		} elseif ( -1 === $pointer[ $state_key ] && empty( $GLOBALS['tara_acf_row'] ) ) {
			$pointer[ $state_key ] = 0;
		}
		if ( ! isset( $rows[ $pointer[ $state_key ] ] ) ) {
			unset( $GLOBALS['tara_acf_row'] );
			unset( $pointer[ $state_key ] );
			return false;
		}
		$GLOBALS['tara_acf_row']   = is_array( $rows[ $pointer[ $state_key ] ] ) ? $rows[ $pointer[ $state_key ] ] : array();
		$GLOBALS['tara_acf_state'] = $state_key;
		return true;
	}
}

if ( ! function_exists( 'the_row' ) ) {
	function the_row() {
		if ( ! empty( $GLOBALS['tara_acf_state'] ) ) {
			$GLOBALS['tara_acf_row_end'] = $GLOBALS['tara_acf_state'];
		}
	}
}

if ( ! function_exists( 'get_row_index' ) ) {
	function get_row_index() {
		return 1; // در حالت سازگاری شمارهٔ سطر قابل اتکا نیست؛ صفر/یک بی‌خطر است
	}
}

if ( ! function_exists( 'the_repeater_field' ) ) {
	function the_repeater_field( $key, $post_id = false ) {
		return have_rows( $key, $post_id );
	}
}

if ( ! function_exists( 'the_sub_repeater_field' ) ) {
	function the_sub_repeater_field( $key ) {
		$r = get_sub_field( $key );
		return ! empty( $r );
	}
}

/* حلقهٔ سازگار با ACF v4 (the_field درون while) */
if ( ! function_exists( 'has_post_meta_field' ) ) {
	function has_post_meta_field( $key, $post_id = false ) {
		$v = tara_acf_meta_value( $key, $post_id, false );
		return ( '' !== $v && array() !== $v );
	}
}
