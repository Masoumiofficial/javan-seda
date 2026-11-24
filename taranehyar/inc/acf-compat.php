<?php
/**
 * ترانه یار — لایهٔ سازگاری ACF (نسخهٔ 3.1.0)
 * ==========================================================================
 * علت وجودی:  single.php برای «کاور/متن ترانه/لینک‌های دانلود» مستقیماً
 * tara_compat_get_field() / tara_compat_the_field() / tara_compat_have_rows() را صدا می‌زند. اگر افزونهٔ
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
 * wp_postmeta باشند؛ در غیر این صورت tara_compat_have_rows() «false» برمی‌گرداند و باکس
 * آلبوم به‌سادگی چاپ نمی‌شود (نه خطا).
 *
 * پالت سازمانی/تاکسونومی/هوک سراسری: دست‌نخورده. بدون گرادینت.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* اگر ACF فعال است، بدنهٔ fallback را تعریف نمی‌کنیم — ولی پوستهٔ
   tara_* را همیشه تعریف می‌کنیم (v3.17.13).

   دلیل: در نسخه‌های پیش از ۳.۱۷.۱۳ اینجا `return;` بود و با فعال‌شدن
   ACF، tara_get_field/tara_acf_call/tara_compat_* اصلاً تعریف نمی‌شدند.
   اما ۲۴ فراخوانیِ بی‌گاردِ tara_get_field() در قالبِ باقی فایل‌ها هست
   (artist-template.php، tara-ui.php، ab-seo.php، …). نتیجه:

       Fatal error: Call to undefined function tara_get_field()

   یعنی قالب با فعال‌کردن ACF **سایت را سفید می‌کرد**. این دقیقاً همان
   چیزی است که کاربر گزارش کرده. */
$tara_acf_native = defined( 'ACF_VERSION' ) || class_exists( 'ACF' ) || function_exists( 'acf_get_value' );

/* v3.17.13 — بدنهٔ fallback فقط وقتی ACF نیست. پوستهٔ tara_* پایین‌تر همیشه تعریف می‌شود. */
if ( ! $tara_acf_native ) {
	if ( ! function_exists( 'tara_acf_meta_value' ) ) {
		/** خواندن مقدار فیلد از post meta + fallback به جدول Options برای فیلدهای سراسری. */
		function tara_acf_meta_value( $key, $post_id = false, $is_array = false ) {
			if ( false === $post_id || null === $post_id || '' === $post_id ) {
				if ( function_exists( 'get_the_ID' ) ) {
					$post_id = get_the_ID();
				}
				/* v3.17.2 — در زمینهٔ آرشیوِ تاکسونومی، `get_the_ID()` می‌تواند
				   شیءِ WP_Term برگرداند (نه شناسهٔ نوشته). کستِ مستقیم به int
				   در PHP 8.2+ هشدار «Object could not be converted» می‌دهد و
				   شناسهٔ بی‌معنا می‌سازد؛ پس فقط عددِ واقعی پذیرفته می‌شود. */
				if ( is_object( $post_id ) ) {
					$post_id = 0;
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

	if ( ! function_exists( 'tara_compat_get_field' ) ) {
		function tara_compat_get_field( $key, $post_id = false, $format_value = true ) {
			unset( $format_value ); // در حالت سازگاری، پاک‌سازی لازم نیست
			return tara_acf_meta_value( $key, $post_id, false );
		}
	}

	if ( ! function_exists( 'tara_compat_get_sub_field' ) ) {
		function tara_compat_get_sub_field( $key ) {
			$ctx = isset( $GLOBALS['tara_acf_row'] ) ? $GLOBALS['tara_acf_row'] : null;
			if ( is_array( $ctx ) && array_key_exists( $key, $ctx ) && is_scalar( $ctx[ $key ] ) ) {
				return (string) $ctx[ $key ];
			}
			return '';
		}
	}

	if ( ! function_exists( 'tara_compat_the_field' ) ) {
		function tara_compat_the_field( $key, $post_id = false, $format_value = true ) {
			unset( $format_value );
			$v = tara_compat_get_field( $key, $post_id );
			echo is_string( $v ) ? $v : ''; // همان رفتار ACF: خروجی خام (HTML/URL)
		}
	}

	if ( ! function_exists( 'tara_compat_the_sub_field' ) ) {
		function tara_compat_the_sub_field( $key ) {
			$v = tara_compat_get_sub_field( $key );
			echo is_string( $v ) ? $v : '';
		}
	}

	if ( ! function_exists( 'tara_compat_have_rows' ) ) {
		/**
		 * شروع/ادامهٔ حلقهٔ سطرهای یک فیلد تکرارشونده.
		 * @return bool  true تا وقتی سطر بعدی وجود دارد
		 */
		function tara_compat_have_rows( $key, $post_id = false ) {
			static $pointer = array();
			$state_key = (string) $key . ':' . ( $post_id ? (int) $post_id : (function_exists('get_the_ID') ? (int) get_the_ID() : 0) );

			$rows = tara_acf_meta_value( $key, $post_id, true );
			if ( ! $rows ) {
				return false;
			}
			if ( ! isset( $pointer[ $state_key ] ) ) {
				$pointer[ $state_key ] = -1;
			}
			/* هر فراخوانیِ have_rows بعد از tara_compat_the_row() یک سطر جلو می‌رود */
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

	if ( ! function_exists( 'tara_compat_the_row' ) ) {
		function tara_compat_the_row() {
			if ( ! empty( $GLOBALS['tara_acf_state'] ) ) {
				$GLOBALS['tara_acf_row_end'] = $GLOBALS['tara_acf_state'];
			}
		}
	}

	if ( ! function_exists( 'tara_compat_get_row_index' ) ) {
		function tara_compat_get_row_index() {
			return 1; // در حالت سازگاری شمارهٔ سطر قابل اتکا نیست؛ صفر/یک بی‌خطر است
		}
	}

	if ( ! function_exists( 'tara_compat_the_repeater_field' ) ) {
		function tara_compat_the_repeater_field( $key, $post_id = false ) {
			return tara_compat_have_rows( $key, $post_id );
		}
	}

	if ( ! function_exists( 'tara_compat_the_sub_repeater_field' ) ) {
		function tara_compat_the_sub_repeater_field( $key ) {
			$r = tara_compat_get_sub_field( $key );
			return ! empty( $r );
		}
	}

	/* حلقهٔ سازگار با ACF v4 (the_field درون while) */
	if ( ! function_exists( 'tara_compat_has_post_meta_field' ) ) {
		function tara_compat_has_post_meta_field( $key, $post_id = false ) {
			$v = tara_acf_meta_value( $key, $post_id, false );
			return ( '' !== $v && array() !== $v );
		}
	}
}

/* ==========================================================================
   v3.17.6 — پوششِ تنبلِ ACF
   --------------------------------------------------------------------------
   باگِ کهنه: نسخه‌های پیش از ۳.۱۷.۶ تابع `get_field()` را **هنگام لودِ
   قالب** تعریف می‌کردند. وقتی کاربر ACF Pro را فعال می‌کرد، وردپرس فایل
   ACF را include می‌کرد و ACF هم همان تابع را تعریف می‌کرد ⇒

       Fatal error: Cannot redeclare function get_field()
       (previously declared in .../inc/acf-compat.php:71)

   و وردپرس پیام «به‌دلیل داشتن مشکلی جدی، افزونه فعال نشد» می‌داد.
   یعنی قالب عملاً **جلوی نصب ACF را می‌گرفت**.

   اصلاح ۳.۱۷.۶:
     • توابع سازگاری همه `tara_compat_*` شدند.
     • نام‌های عمومی (get_field و …) **دیگر هنگام لود تعریف نمی‌شوند**.
       تنها در لحظهٔ *استفاده* — یعنی وقتی یکی از فایل‌های خودِ قالب
       اجرا و نیازمند آن است — و فقط اگر ACF غایب باشد.
     • پس فعال‌سازی ACF هیچ‌وقت به تضاد نمی‌خورد.

   برای استفاده در کدِ قالب، از تابع‌های `tara_compat_*` استفاده می‌کنیم
   که هیچ‌وقت با ACF تضاد ندارند و در صورت بودنِ ACF به آن واگذار می‌کنند.
   ========================================================================== */

if ( ! function_exists( 'tara_acf_call' ) ) {
	/**
	 * فراخوانیِ یک تابع ACF اگر موجود باشد، وگرنه سازگاری داخلی.
	 *
	 * @param string $fn   نام تابع ACF (get_field, the_field, have_rows, …)
	 * @param array  $args آرگومان‌ها
	 * @return mixed
	 */
	function tara_acf_call( $fn, $args = array() ) {
		if ( function_exists( $fn ) ) {
			return call_user_func_array( $fn, (array) $args );
		}
		$map = array(
			'get_field'               => 'tara_compat_get_field',
			'the_field'               => 'tara_compat_the_field',
			'get_sub_field'           => 'tara_compat_get_sub_field',
			'the_sub_field'           => 'tara_compat_the_sub_field',
			'have_rows'               => 'tara_compat_have_rows',
			'the_row'                 => 'tara_compat_the_row',
			'get_row_index'           => 'tara_compat_get_row_index',
			'the_repeater_field'      => 'tara_compat_the_repeater_field',
			'the_sub_repeater_field'  => 'tara_compat_the_sub_repeater_field',
		);
		if ( isset( $map[ $fn ] ) && function_exists( $map[ $fn ] ) ) {
			return call_user_func_array( $map[ $fn ], (array) $args );
		}
		return null;
	}
}

/* میان‌برهای رایج — این‌ها نام‌های اختصاصیِ خودمان‌اند و تضاد نمی‌سازند. */
if ( ! function_exists( 'tara_get_field_normalize_id' ) ) {
	/**
	 * v3.17.13 — تبدیل هر شیء به شناسهٔ قابل‌قبول برای ACF.
	 *
	 * امضای رسمی ACF:
	 *     get_field( string $selector, int|string $post_id = false )
	 *
	 * اگر شیء بدهید (WP_Term، WP_User، WP_Post)، نسخه‌های قدیمی بی‌صدا
	 * رد می‌کردند ولی ACF Pro 6.x با type declaration خطای کشنده می‌دهد:
	 *
	 *     TypeError: get_field(): Argument #2 ($post_id) must be of
	 *     type int|string, WP_Term given
	 *
	 * این تابع شیء را به همان شکلی که ACF می‌فهمد تبدیل می‌کند:
	 *     WP_Term  → "{taxonomy}_{term_id}"   (مثل tara_mood_72)
	 *     WP_User  → "user_{ID}"
	 *     WP_Post  → ID عددی
	 *
	 * @param mixed $post_id ورودیِ خام.
	 * @return int|string
	 */
	function tara_get_field_normalize_id( $post_id ) {
		if ( $post_id instanceof WP_Term ) {
			return isset( $post_id->term_id, $post_id->taxonomy )
				? (string) $post_id->taxonomy . '_' . (int) $post_id->term_id
				: 0;
		}
		if ( $post_id instanceof WP_User ) {
			return 'user_' . (int) $post_id->ID;
		}
		if ( $post_id instanceof WP_Post ) {
			return (int) $post_id->ID;
		}
		/* هر شیءِ ناشناخته‌ای که ID دارد */
		if ( is_object( $post_id ) && isset( $post_id->ID ) ) {
			return (int) $post_id->ID;
		}
		if ( is_object( $post_id ) ) {
			return 0;
		}
		return $post_id;
	}
}

if ( ! function_exists( 'tara_get_field' ) ) {
	function tara_get_field( $key, $post_id = false, $format_value = true ) {
		/* v3.17.13 — هر شیء پیش از رسیدن به ACF به شناسه تبدیل می‌شود. */
		$post_id = tara_get_field_normalize_id( $post_id );
		return tara_acf_call( 'get_field', array( $key, $post_id, $format_value ) );
	}
}
if ( ! function_exists( 'tara_the_field' ) ) {
	function tara_the_field( $key, $post_id = false, $format_value = true ) {
		$v = tara_acf_call( 'get_field', array( $key, $post_id, $format_value ) );
		echo is_scalar( $v ) ? (string) $v : ''; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
if ( ! function_exists( 'tara_have_rows' ) ) {
	function tara_have_rows( $key, $post_id = false ) {
		return (bool) tara_acf_call( 'have_rows', array( $key, $post_id ) );
	}
}
if ( ! function_exists( 'tara_get_sub_field' ) ) {
	function tara_get_sub_field( $key ) {
		return tara_acf_call( 'get_sub_field', array( $key ) );
	}
}
if ( ! function_exists( 'tara_the_sub_field' ) ) {
	function tara_the_sub_field( $key ) {
		$v = tara_acf_call( 'get_sub_field', array( $key ) );
		echo is_scalar( $v ) ? (string) $v : ''; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
if ( ! function_exists( 'tara_the_row' ) ) {
	function tara_the_row() {
		return tara_acf_call( 'the_row' );
	}
}
