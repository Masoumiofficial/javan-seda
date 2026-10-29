<?php
/**
 * ترانه یار — حسابِ کاربری، ورودِ پیامکی و اتصال به ربات (نسخهٔ ۳.۱۰.۰)
 * =========================================================================
 * این فایل «مغز» است و مارکاپ در `inc/tara-account-ui.php` است. کاری که می‌کند:
 *
 *   ۱) ورود/ثبت‌نام با شمارهٔ موبایل و کدِ یک‌بارمصرف (OTP).
 *   ۲) ارسالِ پیامک با «ملی‌پیامک» (REST)، وب‌هوکِ دلخواه یا حالتِ آزمایشی.
 *   ۳) حسابِ کاربریِ سبک روی همان جدولِ کاربرانِ وردپرس (بدونِ جدولِ تازه).
 *   ۴) داده‌های کاربر: علاقه‌مندی‌ها، پلی‌لیست‌ها، دنبال‌کردنِ خواننده‌ها،
 *      تاریخچهٔ شنیدن و پیام‌ها — همه در user_meta.
 *   ۵) REST برای رباتِ «آهنگ‌بان» با همان قراردادِ Bearer token.
 *
 * چرا جدولِ تازه ساخته نمی‌شود؟ چون هر جدولِ تازه یعنی نسخه‌بندی، مهاجرت و
 * پشتیبان‌گیریِ جدا. user_meta برای ده‌ها هزار کاربرِ یک سایتِ موسیقی کافی
 * است و با حذفِ کاربر، همه‌چیز خودش پاک می‌شود.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* این ثابت‌ها در وردپرس همیشه هستند؛ این گارد فقط برای محیطِ آزمون است. */
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

/* -------------------------------------------------------------------------
 * ۱) تنظیمات — همه با پیشوندِ `account_` در همان option_tree
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_defaults' ) ) {
	/**
	 * پیش‌فرضِ همهٔ تنظیماتِ حساب.
	 *
	 * @return array
	 */
	function tara_acc_defaults() {
		$defaults = array(
			'on'             => 'on',            // کلیدِ اصلی
			'allow_password' => 'on',            // ورود با نام‌کاربری/رمزِ وردپرس هم ممکن باشد
			'code_ttl'       => 180,             // عمرِ کد (ثانیه)
			'resend'         => 60,              // فاصلهٔ ارسالِ دوباره (ثانیه)
			'tries'          => 6,               // حداکثر تلاشِ اشتباه
			'per_phone'      => 5,               // ارسال در ساعت برای هر شماره
			'per_ip'         => 12,              // ارسال در ساعت برای هر IP
			'sms'            => 'dev',           // dev | melipayamak | webhook
			'sms_user'       => '',              // نام‌کاربریِ ملی‌پیامک
			'sms_pass'       => '',              // رمزِ ملی‌پیامک
			'sms_from'       => '',              // شمارهٔ فرستنده
			'sms_pattern'    => '',              // کدِ پترن (اختیاری)
			'sms_hook'       => '',              // آدرس وب‌هوک برای پنل‌های دیگر
			'sms_hook_auth'  => '',              // هدر Authorization اختیاری
			'sms_text'       => 'کدِ ورود شما به {site}: {code}
			این کد را به هیچ‌کس ندهید.',
			'dev_show'       => 'on',            // در حالتِ آزمایشی کد روی صفحه دیده شود
			'welcome'        => 'on',            // پیامِ خوش‌آمد بعد از ثبت‌نام
			'likes'          => 'on',
			'playlists'      => 'on',
			'follows'        => 'on',
			'history'        => 'on',
			'notifs'         => 'on',
			'avatar'         => 'on',            // آپلودِ تصویرِ پروفایل
			'max_likes'      => 500,
			'max_playlists'  => 30,
			'max_tracks'     => 300,
			'max_follows'    => 500,
			'max_history'    => 100,
			'redirect'       => '',              // بعد از ورود کجا برود (خالی = حساب من)
			'tg_bot'         => '',              // نام‌کاربریِ ربات بدونِ @
			'tg_token'       => '',              // توکنِ ۶۴رقمیِ REST برای ربات
			'tg_code_ttl'    => 600,             // عمرِ کدِ اتصال به ربات
			'tg_song'        => 'on',            // دکمهٔ «مشاهده در ربات» زیرِ لینک‌های دانلود
		);

		return apply_filters( 'tara_acc_defaults', $defaults );
	}
}

if ( ! function_exists( 'tara_acc_opt' ) ) {
	/**
	 * یک تنظیم با پیش‌فرض.
	 *
	 * @param string $key     کلید بدونِ پیشوند.
	 * @param mixed  $default پیش‌فرضِ دستی (اختیاری).
	 * @return mixed
	 */
	function tara_acc_opt( $key, $default = null ) {
		$defaults = tara_acc_defaults();
		if ( null === $default ) {
			$default = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
		}
		return ot_get_option( 'account_' . $key, $default );
	}
}

if ( ! function_exists( 'tara_acc_on' ) ) {
	/**
	 * آیا حسابِ کاربری روشن است؟
	 *
	 * @return bool
	 */
	function tara_acc_on() {
		return 'on' === (string) tara_acc_opt( 'on', 'on' );
	}
}

if ( ! function_exists( 'tara_acc_feature' ) ) {
	/**
	 * آیا یک قابلیتِ مشخص (likes/playlists/follows/history/notifs) روشن است؟
	 *
	 * @param string $name نام.
	 * @return bool
	 */
	function tara_acc_feature( $name ) {
		if ( ! tara_acc_on() ) {
			return false;
		}
		$allowed = array( 'likes', 'playlists', 'follows', 'history', 'notifs', 'avatar' );
		if ( ! in_array( (string) $name, $allowed, true ) ) {
			return false;
		}
		return 'on' === (string) tara_acc_opt( $name, 'on' );
	}
}

if ( ! function_exists( 'tara_acc_limit' ) ) {
	/**
	 * سقفِ یک فهرست (با کف و سقفِ منطقی).
	 *
	 * @param string $key کلید.
	 * @return int
	 */
	function tara_acc_limit( $key ) {
		$map = array(
			'max_likes'     => array( 50, 2000 ),
			'max_playlists' => array( 3, 100 ),
			'max_tracks'    => array( 20, 1000 ),
			'max_follows'   => array( 20, 2000 ),
			'max_history'   => array( 20, 500 ),
		);
		$defaults = tara_acc_defaults();
		$value    = (int) tara_acc_opt( $key, isset( $defaults[ $key ] ) ? $defaults[ $key ] : 100 );
		$range    = isset( $map[ $key ] ) ? $map[ $key ] : array( 1, 1000 );

		return max( $range[0], min( $range[1], $value ) );
	}
}

/* -------------------------------------------------------------------------
 * ۲) شمارهٔ موبایل
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_digits' ) ) {
	/**
	 * رقم‌های فارسی/عربی را به لاتین برمی‌گرداند و هر چیزِ دیگر را دور می‌ریزد.
	 *
	 * @param string $raw ورودی.
	 * @return string
	 */
	function tara_acc_digits( $raw ) {
		$raw = (string) $raw;
		$map = array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
			'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
			'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		);
		$raw = strtr( $raw, $map );

		return preg_replace( '/[^0-9]/', '', $raw );
	}
}

if ( ! function_exists( 'tara_acc_phone' ) ) {
	/**
	 * شماره را به شکلِ استانداردِ ۰۹xxxxxxxxx برمی‌گرداند.
	 *
	 * ورودی‌های پذیرفته‌شده: 09121234567 · 9121234567 · 989121234567 ·
	 * +989121234567 · 00989121234567 (با فاصله/خط تیره/رقمِ فارسی هم می‌شود).
	 *
	 * @param string $raw ورودیِ کاربر.
	 * @return string شمارهٔ ۱۱رقمی یا رشتهٔ خالی.
	 */
	function tara_acc_phone( $raw ) {
		$d = tara_acc_digits( $raw );
		if ( '' === $d ) {
			return '';
		}

		/* ۰۰۹۸… → ۹۸… */
		if ( 0 === strpos( $d, '0098' ) ) {
			$d = substr( $d, 4 );
		}
		/* ۹۸۹۱۲… → ۹۱۲… */
		if ( 0 === strpos( $d, '98' ) && strlen( $d ) > 11 ) {
			$d = substr( $d, 2 );
		}
		/* ۹۱۲… → ۰۹۱۲… */
		if ( 10 === strlen( $d ) && 0 === strpos( $d, '9' ) ) {
			$d = '0' . $d;
		}
		if ( 11 !== strlen( $d ) ) {
			return '';
		}
		if ( '0' !== $d[0] || '9' !== $d[1] ) {
			return '';
		}

		return $d;
	}
}

if ( ! function_exists( 'tara_acc_phone_pretty' ) ) {
	/**
	 * نمایشِ خوانا: ۰۹۱۲ ۱۲۳ ۴۵۶۷
	 *
	 * @param string $phone شمارهٔ استاندارد.
	 * @return string
	 */
	function tara_acc_phone_pretty( $phone ) {
		$phone = tara_acc_phone( $phone );
		if ( '' === $phone ) {
			return '';
		}
		$out = substr( $phone, 0, 4 ) . ' ' . substr( $phone, 4, 3 ) . ' ' . substr( $phone, 7 );
		if ( function_exists( 'tara_fa_number' ) ) {
			$out = tara_fa_number( $out );
		}
		return $out;
	}
}

if ( ! function_exists( 'tara_acc_phone_mask' ) ) {
	/**
	 * پوشاندنِ وسطِ شماره برای نمایشِ عمومی (۰۹۱۲***۴۵۶۷).
	 *
	 * @param string $phone شماره.
	 * @return string
	 */
	function tara_acc_phone_mask( $phone ) {
		$phone = tara_acc_phone( $phone );
		if ( '' === $phone ) {
			return '';
		}
		$out = substr( $phone, 0, 4 ) . '***' . substr( $phone, -4 );
		return function_exists( 'tara_fa_number' ) ? tara_fa_number( $out ) : $out;
	}
}

if ( ! function_exists( 'tara_acc_client_ip' ) ) {
	/**
	 * IPِ مشتری با نگاهِ محتاطانه به پراکسی (X-Forwarded-For فقط اولین مقدار).
	 *
	 * @return string
	 */
	function tara_acc_client_ip() {
		$raw = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$raw = (string) $_SERVER['REMOTE_ADDR'];
		}
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$parts = explode( ',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$first = trim( (string) $parts[0] );
			if ( '' !== $first ) {
				$raw = $first;
			}
		}
		$raw = preg_replace( '/[^0-9a-fA-F:.]/', '', $raw );
		return '' !== $raw ? $raw : '0.0.0.0';
	}
}

/* -------------------------------------------------------------------------
 * ۳) سقفِ نرخ (throttle) و کدِ یک‌بارمصرف
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_throttle' ) ) {
	/**
	 * شمارندهٔ سادهٔ پنجرهٔ ثابت.
	 *
	 * @param string $key   کلیدِ یگانه (بدونِ دادهٔ حساس؛ هش می‌شود).
	 * @param int    $max   حداکثر در پنجره.
	 * @param int    $ttl   طولِ پنجره (ثانیه).
	 * @return bool true = اجازه دارد (و یکی به شمارنده اضافه شد).
	 */
	function tara_acc_throttle( $key, $max, $ttl ) {
		$key   = 'tara_thr_' . md5( (string) $key );
		$count = (int) get_transient( $key );
		if ( $count >= (int) $max ) {
			return false;
		}
		set_transient( $key, $count + 1, (int) $ttl );
		return true;
	}
}

if ( ! function_exists( 'tara_acc_otp_key' ) ) {
	/** کلیدِ ترنزینتِ کدِ یک شماره. */
	function tara_acc_otp_key( $phone ) {
		return 'tara_otp_' . md5( (string) $phone );
	}
}

if ( ! function_exists( 'tara_acc_otp_code' ) ) {
	/**
	 * تولیدِ کدِ ۶رقمیِ یک‌بارمصرف.
	 *
	 * @return string
	 */
	function tara_acc_otp_code() {
		if ( function_exists( 'random_int' ) ) {
			return str_pad( (string) random_int( 100000, 999999 ), 6, '0', STR_PAD_LEFT );
		}
		return str_pad( (string) wp_rand( 100000, 999999 ), 6, '0', STR_PAD_LEFT );
	}
}

if ( ! function_exists( 'tara_acc_otp_send' ) ) {
	/**
	 * ساخت و ارسالِ کد.
	 *
	 * @param string $phone شمارهٔ استاندارد.
	 * @return array|WP_Error
	 */
	function tara_acc_otp_send( $phone ) {
		$phone = tara_acc_phone( $phone );
		if ( '' === $phone ) {
			return new WP_Error( 'tara_bad_phone', 'شمارهٔ موبایل را درست وارد کنید (مثلِ ۰۹۱۲۱۲۳۴۵۶۷).' );
		}

		$ttl      = max( 60, min( 600, (int) tara_acc_opt( 'code_ttl', 180 ) ) );
		$resend   = max( 20, min( 300, (int) tara_acc_opt( 'resend', 60 ) ) );
		$key      = tara_acc_otp_key( $phone );
		$existing = get_transient( $key );

		if ( is_array( $existing ) && ! empty( $existing['sent'] ) ) {
			$wait = (int) $existing['sent'] + $resend - time();
			if ( $wait > 0 ) {
				return new WP_Error(
					'tara_cooldown',
					sprintf( 'کدِ قبلی هنوز معتبر است؛ %s ثانیهٔ دیگر می‌توانید دوباره درخواست کنید.', tara_acc_fa( $wait ) ),
					array( 'wait' => $wait )
				);
			}
		}

		if ( ! tara_acc_throttle( 'otp_phone_' . $phone, (int) tara_acc_opt( 'per_phone', 5 ), HOUR_IN_SECONDS ) ) {
			return new WP_Error( 'tara_limit_phone', 'برای این شماره تعدادِ درخواست‌ها زیاد شد؛ یک ساعتِ دیگر تلاش کنید.' );
		}
		if ( ! tara_acc_throttle( 'otp_ip_' . tara_acc_client_ip(), (int) tara_acc_opt( 'per_ip', 12 ), HOUR_IN_SECONDS ) ) {
			return new WP_Error( 'tara_limit_ip', 'از این اتصال تعدادِ درخواست‌ها زیاد شد؛ کمی بعد تلاش کنید.' );
		}

		$code = tara_acc_otp_code();
		set_transient(
			$key,
			array(
				'hash'   => wp_hash( $code ),
				'tries'  => 0,
				'sent'   => time(),
				'expire' => time() + $ttl,
			),
			$ttl
		);

		$sent  = tara_acc_sms_send( $phone, $code );
		$debug = ( 'dev' === (string) tara_acc_opt( 'sms', 'dev' ) && 'on' === (string) tara_acc_opt( 'dev_show', 'on' ) );

		if ( ! $sent && ! $debug ) {
			delete_transient( $key );
			return new WP_Error( 'tara_sms_failed', 'ارسالِ پیامک انجام نشد. لطفاً کمی بعد دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.' );
		}

		$out = array(
			'phone'       => tara_acc_phone_pretty( $phone ),
			'ttl'         => $ttl,
			'resend'      => $resend,
			'dev'         => $debug,
			'dev_code'    => $debug ? $code : '',
			'sms'         => (string) tara_acc_opt( 'sms', 'dev' ),
			'has_account' => (bool) tara_acc_user_by_phone( $phone ),
		);

		return $out;
	}
}

if ( ! function_exists( 'tara_acc_otp_check' ) ) {
	/**
	 * بررسیِ کد.
	 *
	 * @param string $phone شمارهٔ استاندارد.
	 * @param string $code  کدِ واردشده.
	 * @return bool
	 */
	function tara_acc_otp_check( $phone, $code ) {
		$phone = tara_acc_phone( $phone );
		$code  = tara_acc_digits( $code );
		if ( '' === $phone || 6 !== strlen( $code ) ) {
			return false;
		}

		$key     = tara_acc_otp_key( $phone );
		$payload = get_transient( $key );
		if ( ! is_array( $payload ) || empty( $payload['hash'] ) ) {
			return false;
		}

		$max   = max( 3, min( 10, (int) tara_acc_opt( 'tries', 6 ) ) );
		$tries = isset( $payload['tries'] ) ? (int) $payload['tries'] : 0;
		if ( $tries >= $max ) {
			delete_transient( $key );
			return false;
		}

		if ( ! hash_equals( (string) $payload['hash'], (string) wp_hash( $code ) ) ) {
			$payload['tries'] = $tries + 1;
			$expire           = isset( $payload['expire'] ) ? (int) $payload['expire'] : time() + 60;
			$left             = max( 1, $expire - time() );
			set_transient( $key, $payload, $left );
			return false;
		}

		delete_transient( $key );
		return true;
	}
}

if ( ! function_exists( 'tara_acc_fa' ) ) {
	/**
	 * عددِ فارسی با پشتیبان.
	 *
	 * @param int|float $n عدد.
	 * @return string
	 */
	function tara_acc_fa( $n ) {
		if ( function_exists( 'tara_fa_number' ) ) {
			return tara_fa_number( $n );
		}
		return (string) $n;
	}
}

/* -------------------------------------------------------------------------
 * ۴) پیامک — ملی‌پیامک، وب‌هوک یا حالتِ آزمایشی
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_sms_text' ) ) {
	/**
	 * متنِ پیامک با جای‌گذاریِ نامِ سایت و کد.
	 *
	 * @param string $code کد.
	 * @return string
	 */
	function tara_acc_sms_text( $code ) {
		$text = (string) tara_acc_opt( 'sms_text' );
		if ( '' === trim( $text ) ) {
			$text = 'کدِ ورود شما به {site}: {code}';
		}
		$text = str_replace(
			array( '{site}', '{code}' ),
			array( (string) get_bloginfo( 'name' ), (string) $code ),
			$text
		);

		return trim( preg_replace( "/[ \t]+/", ' ', str_replace( "\r", '', $text ) ) );
	}
}

if ( ! function_exists( 'tara_acc_sms_send' ) ) {
	/**
	 * ارسالِ پیامک.
	 *
	 * @param string $phone شمارهٔ استاندارد.
	 * @param string $code  کد.
	 * @return bool
	 */
	function tara_acc_sms_send( $phone, $code ) {
		$provider = (string) tara_acc_opt( 'sms', 'dev' );
		$text     = tara_acc_sms_text( $code );

		$result = false;
		switch ( $provider ) {
			case 'melipayamak':
				$result = tara_acc_sms_melipayamak( $phone, $code, $text );
				break;
			case 'webhook':
				$result = tara_acc_sms_webhook( $phone, $code, $text );
				break;
			case 'dev':
			default:
				/* حالتِ آزمایشی: پیامکی نمی‌رود؛ کد در پاسخِ AJAX برمی‌گردد
				   (فقط وقتی «نمایشِ کد» روشن باشد). */
				$result = true;
				break;
		}

		return (bool) apply_filters( 'tara_acc_sms_sent', $result, $phone, $code, $provider );
	}
}

if ( ! function_exists( 'tara_acc_http' ) ) {
	/**
	 * POSTِ JSON با `wp_remote_post` — با محافظ در برابر نبودِ تابع.
	 *
	 * @param string $url     آدرس.
	 * @param array  $body    بدنه.
	 * @param array  $headers هدرها.
	 * @return array{ok:bool,body:string,code:int}
	 */
	function tara_acc_http( $url, $body, $headers = array() ) {
		if ( ! function_exists( 'wp_remote_post' ) || '' === trim( (string) $url ) ) {
			return array( 'ok' => false, 'body' => '', 'code' => 0 );
		}
		$res = wp_remote_post(
			$url,
			array(
				'timeout'     => 10,
				'redirection' => 0,
				'headers'     => array_merge( array( 'Content-Type' => 'application/json; charset=utf-8' ), (array) $headers ),
				'body'        => wp_json_encode( $body ),
				'data_format' => 'body',
			)
		);
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) {
			return array( 'ok' => false, 'body' => '', 'code' => 0 );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$raw  = (string) wp_remote_retrieve_body( $res );

		return array(
			'ok'   => $code >= 200 && $code < 300,
			'body' => $raw,
			'code' => $code,
		);
	}
}

if ( ! function_exists( 'tara_acc_sms_melipayamak' ) ) {
	/**
	 * ملی‌پیامک (rest.payamak-panel.com).
	 *
	 * اگر «کدِ پترن» پر شده باشد از سرویسِ پترن (BaseServiceNumber) استفاده
	 * می‌شود که ارزان‌تر و سریع‌تر است؛ وگرنه ارسالِ متنیِ معمولی.
	 *
	 * @param string $phone شماره.
	 * @param string $code  کد.
	 * @param string $text  متن.
	 * @return bool
	 */
	function tara_acc_sms_melipayamak( $phone, $code, $text ) {
		$user = trim( (string) tara_acc_opt( 'sms_user' ) );
		$pass = (string) tara_acc_opt( 'sms_pass' );
		$from = tara_acc_digits( (string) tara_acc_opt( 'sms_from' ) );
		$body = trim( (string) tara_acc_opt( 'sms_pattern' ) );

		if ( '' === $user || '' === $pass || '' === $from ) {
			return false;
		}

		if ( '' !== $body ) {
			/* سرویسِ پترن: متنِ پترن همان {code} است؛ پارامترها با , جدا می‌شوند. */
			$url = 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber';
			$res = tara_acc_http(
				$url,
				array(
					'username' => $user,
					'password' => $pass,
					'text'     => $code,
					'to'       => $phone,
					'bodyId'   => (int) $body,
				)
			);
		} else {
			$url = 'https://rest.payamak-panel.com/api/SendSMS/SendSMS';
			$res = tara_acc_http(
				$url,
				array(
					'username' => $user,
					'password' => $pass,
					'to'       => $phone,
					'from'     => $from,
					'text'     => $text,
					'isflash'  => false,
				)
			);
		}

		if ( ! $res['ok'] ) {
			return false;
		}

		$json = json_decode( $res['body'], true );
		if ( is_array( $json ) ) {
			if ( isset( $json['RetStatus'] ) ) {
				return 1 === (int) $json['RetStatus'];
			}
			if ( isset( $json['Value'] ) && is_numeric( $json['Value'] ) ) {
				return (int) $json['Value'] > 0;
			}
			if ( isset( $json['recId'] ) ) {
				return (int) $json['recId'] > 0;
			}
		}

		return true;
	}
}

if ( ! function_exists( 'tara_acc_sms_webhook' ) ) {
	/**
	 * وب‌هوکِ عمومی — برای هر پنلِ پیامکیِ دیگر (کاوه‌نگار، SMS.ir، اسکریپتِ خودتان).
	 *
	 * بدنهٔ JSON: {phone, code, text, site, name}
	 * اگر «هدرِ احراز» پر شده باشد با همان مقدار فرستاده می‌شود.
	 *
	 * @param string $phone شماره.
	 * @param string $code  کد.
	 * @param string $text  متن.
	 * @return bool
	 */
	function tara_acc_sms_webhook( $phone, $code, $text ) {
		$url = trim( (string) tara_acc_opt( 'sms_hook' ) );
		if ( '' === $url || ! preg_match( '#^https://#i', $url ) ) {
			return false;
		}
		$headers = array();
		$auth    = trim( (string) tara_acc_opt( 'sms_hook_auth' ) );
		if ( '' !== $auth ) {
			$headers['Authorization'] = $auth;
		}
		$res = tara_acc_http(
			$url,
			array(
				'phone' => $phone,
				'code'  => $code,
				'text'  => $text,
				'site'  => (string) get_bloginfo( 'name' ),
				'name'  => (string) get_bloginfo( 'name' ),
			),
			$headers
		);

		return (bool) $res['ok'];
	}
}

/* -------------------------------------------------------------------------
 * ۵) کاربر — پیدا کردن، ساختن و ورود
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_login_name' ) ) {
	/**
	 * نام‌کاربریِ ساخته‌شده از شماره.
	 *
	 * @param string $phone شمارهٔ استاندارد.
	 * @return string
	 */
	function tara_acc_login_name( $phone ) {
		return 'tara_' . tara_acc_phone( $phone );
	}
}

if ( ! function_exists( 'tara_acc_user_by_phone' ) ) {
	/**
	 * کاربرِ متعلق به یک شماره.
	 *
	 * @param string $phone شماره.
	 * @return WP_User|false
	 */
	function tara_acc_user_by_phone( $phone ) {
		$phone = tara_acc_phone( $phone );
		if ( '' === $phone || ! function_exists( 'get_users' ) ) {
			return false;
		}

		$users = get_users(
			array(
				'meta_key'     => 'tara_phone', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'   => $phone,       // phpcs:ignore WordPress.DB.SlowDBQuery
				'number'       => 1,
				'count_total'  => false,
			)
		);
		if ( is_array( $users ) && ! empty( $users[0] ) ) {
			return $users[0];
		}

		/* سازگاری با حساب‌های قدیمی‌تر که فقط user_login داشتند. */
		if ( function_exists( 'get_user_by' ) ) {
			$user = get_user_by( 'login', tara_acc_login_name( $phone ) );
			if ( $user && ! is_wp_error( $user ) ) {
				return $user;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'tara_acc_user_phone' ) ) {
	/**
	 * شمارهٔ یک کاربر (خالی = کاربرِ رمزدارِ وردپرس).
	 *
	 * @param int $user_id شناسه.
	 * @return string
	 */
	function tara_acc_user_phone( $user_id = 0 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return '';
		}
		return tara_acc_phone( (string) get_user_meta( $user_id, 'tara_phone', true ) );
	}
}

if ( ! function_exists( 'tara_acc_make_user' ) ) {
	/**
	 * ساختنِ حساب برای شماره‌ای که تازه تأیید شده است.
	 *
	 * @param string $phone شمارهٔ تأییدشده.
	 * @return int|WP_Error شناسهٔ کاربر.
	 */
	function tara_acc_make_user( $phone ) {
		$phone = tara_acc_phone( $phone );
		if ( '' === $phone ) {
			return new WP_Error( 'tara_bad_phone', 'شمارهٔ نامعتبر.' );
		}
		if ( ! function_exists( 'wp_insert_user' ) ) {
			return new WP_Error( 'tara_no_wp', 'ساختِ حساب در این محیط ممکن نیست.' );
		}

		$login = tara_acc_login_name( $phone );
		$n     = 1;
		while ( function_exists( 'username_exists' ) && username_exists( $login ) ) {
			$n++;
			$login = tara_acc_login_name( $phone ) . '_' . $n;
			if ( $n > 5 ) {
				$login = tara_acc_login_name( $phone ) . '_' . wp_rand( 1000, 9999 );
				break;
			}
		}

		$last4 = substr( $phone, -4 );
		$name  = 'شنوندهٔ ' . ( function_exists( 'tara_fa_number' ) ? tara_fa_number( $last4 ) : $last4 );

		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_pass'    => wp_generate_password( 32, true, true ),
				'user_email'   => '',
				'display_name' => $name,
				'nickname'     => $name,
				'role'         => 'subscriber',
				'description'  => '',
			)
		);
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$user_id = (int) $user_id;
		update_user_meta( $user_id, 'tara_phone', $phone );
		update_user_meta( $user_id, 'tara_phone_verified', time() );
		update_user_meta( $user_id, 'tara_joined', time() );

		/* پیامِ خوش‌آمد — در همان فهرستِ پیام‌های پنل. */
		if ( 'on' === (string) tara_acc_opt( 'welcome', 'on' ) ) {
			tara_acc_notify(
				$user_id,
				array(
					'k'     => 'welcome',
					'title' => 'خوش آمدید به ' . get_bloginfo( 'name' ) . '! از پنلِ «حساب من» آهنگ‌های موردعلاقه‌تان را ذخیره کنید.',
					'url'   => tara_acc_profile_url( 'favorites' ),
				)
			);
		}

		do_action( 'tara_acc_user_created', $user_id, $phone );

		return $user_id;
	}
}

if ( ! function_exists( 'tara_acc_signin' ) ) {
	/**
	 * ورود به حساب (و ساختِ خودکار اگر نبود).
	 *
	 * @param string $phone   شمارهٔ تأییدشده.
	 * @param bool   $remember مرا به خاطر بسپار.
	 * @return int|WP_Error
	 */
	function tara_acc_signin( $phone, $remember = true ) {
		$user = tara_acc_user_by_phone( $phone );
		if ( $user ) {
			$user_id = (int) $user->ID;
		} else {
			$user_id = tara_acc_make_user( $phone );
			if ( function_exists( 'is_wp_error' ) && is_wp_error( $user_id ) ) {
				return $user_id;
			}
			$user_id = (int) $user_id;
		}

		if ( $user_id <= 0 ) {
			return new WP_Error( 'tara_no_user', 'ساختِ حساب انجام نشد.' );
		}

		if ( function_exists( 'wp_set_current_user' ) ) {
			wp_set_current_user( $user_id );
		}
		if ( function_exists( 'wp_set_auth_cookie' ) ) {
			wp_set_auth_cookie( $user_id, (bool) $remember, is_ssl() );
		}
		if ( function_exists( 'get_user_by' ) ) {
			$full = get_user_by( 'id', $user_id );
			if ( $full && ! is_wp_error( $full ) ) {
				do_action( 'wp_login', $full->user_login, $full );
			}
		}

		update_user_meta( $user_id, 'tara_last_login', time() );

		return $user_id;
	}
}

if ( ! function_exists( 'tara_acc_avatar_url' ) ) {
	/**
	 * تصویرِ پروفایل: آپلودِ کاربر ← گراواتار ← تصویرِ پیش‌فرضِ قالب.
	 *
	 * @param int $user_id شناسه.
	 * @param int $size    اندازه.
	 * @return string
	 */
	function tara_acc_avatar_url( $user_id = 0, $size = 96 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return '';
		}
		$direct = esc_url_raw( (string) get_user_meta( $user_id, 'tara_avatar_url', true ) );
		if ( '' !== $direct ) {
			return $direct;
		}
		$id = (int) get_user_meta( $user_id, 'tara_avatar', true );
		if ( $id > 0 ) {
			$url = wp_get_attachment_image_url( $id, array( (int) $size, (int) $size ) );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}
		if ( function_exists( 'get_avatar_url' ) ) {
			$url = get_avatar_url( $user_id, array( 'size' => (int) $size ) );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}
		return get_template_directory_uri() . '/images/nomusics.jpg';
	}
}

if ( ! function_exists( 'tara_acc_profile_url' ) ) {
	/**
	 * آدرسِ پنلِ کاربری (با تبِ دلخواه).
	 *
	 * @param string $tab تب.
	 * @return string
	 */
	function tara_acc_profile_url( $tab = '' ) {
		$base = '';
		if ( function_exists( 'tara_acc_page_url' ) ) {
			$base = tara_acc_page_url( 'page-account.php' );
		}
		if ( '' === $base ) {
			$base = home_url( '/' );
		}
		if ( '' === (string) $tab ) {
			return $base;
		}
		return $base . ( false === strpos( $base, '?' ) ? '?' : '&' ) . 'tab=' . rawurlencode( (string) $tab );
	}
}

if ( ! function_exists( 'tara_acc_page_url' ) ) {
	/**
	 * آدرسِ صفحه‌ای که یک قالبِ مشخص دارد (با کشِ یک‌ساعته).
	 *
	 * @param string $template نامِ فایلِ قالب.
	 * @return string
	 */
	function tara_acc_page_url( $template ) {
		$key    = 'tara_page_url_' . md5( (string) $template );
		$cached = get_transient( $key );
		if ( is_string( $cached ) ) {
			return $cached;
		}
		$url = '';
		if ( function_exists( 'get_pages' ) ) {
			$pages = get_pages(
				array(
					'meta_key'    => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value'  => (string) $template,  // phpcs:ignore WordPress.DB.SlowDBQuery
					'number'      => 1,
					'post_status' => 'publish',
				)
			);
			if ( is_array( $pages ) && ! empty( $pages ) ) {
				$url = (string) get_permalink( $pages[0] );
			}
		}
		/* برای کاربرِ مهمان، کشِ خالی دردسر می‌سازد اگر مدیر صفحه را تازه
		   ساخته باشد؛ پس خالی را کوتاه‌تر کش می‌کنیم. */
		set_transient( $key, $url, '' === $url ? 5 * MINUTE_IN_SECONDS : HOUR_IN_SECONDS );

		return $url;
	}
}

/* -------------------------------------------------------------------------
 * ۶) دادهٔ کاربر — علاقه‌مندی، پلی‌لیست، دنبال‌کردن، تاریخچه، پیام
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_meta' ) ) {
	/**
	 * خواندنِ یک فهرست از user_meta با تضمینِ آرایه بودن.
	 *
	 * @param int    $user_id شناسهٔ کاربر.
	 * @param string $key     کلید بدونِ پیشوند.
	 * @param mixed  $default مقدار پیش‌فرض.
	 * @return mixed
	 */
	function tara_acc_meta( $user_id, $key, $default = array() ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return $default;
		}
		$value = get_user_meta( $user_id, 'tara_' . (string) $key, true );
		if ( '' === $value || null === $value || false === $value ) {
			return $default;
		}
		return $value;
	}
}

if ( ! function_exists( 'tara_acc_uid' ) ) {
	/**
	 * شناسهٔ کاربر با پیش‌فرضِ «کاربرِ جاری».
	 *
	 * چند تابع (پلی‌لیست، تاریخچه، پیام) بدونِ شناسه هم صدا زده می‌شوند؛
	 * بدونِ این کمک، صدا زدنِ سادهٔ `tara_acc_playlists()` فهرستِ کاربرِ
	 * صفر را می‌خواند و خالی برمی‌گشت. مقدارِ ۰ یعنی «کاربرِ جاری».
	 *
	 * @param int $user_id شناسه (۰ = جاری).
	 * @return int
	 */
	function tara_acc_uid( $user_id = 0 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		return max( 0, $user_id );
	}
}

if ( ! function_exists( 'tara_acc_save' ) ) {
	/**
	 * نوشتنِ یک فهرست در user_meta.
	 *
	 * @param int    $user_id شناسه.
	 * @param string $key     کلید.
	 * @param mixed  $value   مقدار.
	 * @return void
	 */
	function tara_acc_save( $user_id, $key, $value ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return;
		}
		update_user_meta( $user_id, 'tara_' . (string) $key, $value );
		/* کشِ سبکِ «تعدادها» را باطل می‌کنیم تا داشبورد کهنه نماند. */
		if ( function_exists( 'delete_transient' ) ) {
			delete_transient( 'tara_acc_counts_' . $user_id );
		}
	}
}

/* ---- ۶-۱) علاقه‌مندی‌ها ---- */

if ( ! function_exists( 'tara_acc_likes' ) ) {
	/**
	 * شناسهٔ آهنگ‌های ذخیره‌شده (تازه‌ترین اول).
	 *
	 * @param int $user_id شناسه.
	 * @return int[]
	 */
	function tara_acc_likes( $user_id = 0 ) {
		$list = tara_acc_meta( tara_acc_uid( $user_id ), 'likes', array() );
		if ( ! is_array( $list ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'absint', $list ) ) );
	}
}

if ( ! function_exists( 'tara_acc_liked' ) ) {
	/** آیا کاربر این آهنگ را ذخیره کرده است؟ */
	function tara_acc_liked( $post_id, $user_id = 0 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		$post_id = (int) $post_id;
		if ( $user_id <= 0 || $post_id <= 0 ) {
			return false;
		}
		return in_array( $post_id, tara_acc_likes( $user_id ), true );
	}
}

if ( ! function_exists( 'tara_acc_like' ) ) {
	/**
	 * افزودن/برداشتنِ علاقه‌مندی.
	 *
	 * @param int  $post_id شناسهٔ آهنگ.
	 * @param int  $user_id کاربر (۰ = کاربرِ جاری).
	 * @param bool $on      true=افزودن، false=برداشتن، null=وارونه.
	 * @return array{liked:bool,count:int}|WP_Error
	 */
	function tara_acc_like( $post_id, $user_id = 0, $on = null ) {
		$post_id = (int) $post_id;
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return new WP_Error( 'tara_guest', 'برای ذخیرهٔ آهنگ باید وارد شوید.' );
		}
		if ( $post_id <= 0 || ( function_exists( 'get_post' ) && ! get_post( $post_id ) ) ) {
			return new WP_Error( 'tara_no_post', 'آهنگ پیدا نشد.' );
		}

		$list   = tara_acc_likes( $user_id );
		$has    = in_array( $post_id, $list, true );
		$target = ( null === $on ) ? ! $has : (bool) $on;

		if ( $target && ! $has ) {
			if ( count( $list ) >= tara_acc_limit( 'max_likes' ) ) {
				return new WP_Error( 'tara_likes_full', 'فهرستِ علاقه‌مندی‌ها پر شده است؛ چند آهنگ را بردارید.' );
			}
			array_unshift( $list, $post_id );
		} elseif ( ! $target && $has ) {
			$list = array_values(
				array_filter(
					$list,
					function ( $id ) use ( $post_id ) {
						return (int) $id !== $post_id;
					}
				)
			);
		}

		tara_acc_save( $user_id, 'likes', array_slice( $list, 0, tara_acc_limit( 'max_likes' ) ) );

		/* شمارندهٔ نمایشی (اصلِ حقیقت نیست) */
		$count = (int) get_post_meta( $post_id, 'tara_like_count', true );
		$count += $target && ! $has ? 1 : 0;
		$count -= ( ! $target && $has ) ? 1 : 0;
		update_post_meta( $post_id, 'tara_like_count', max( 0, $count ) );

		return array(
			'liked' => $target,
			'count' => max( 0, $count ),
		);
	}
}

/* ---- ۶-۲) دنبال‌کردنِ خواننده‌ها ---- */

if ( ! function_exists( 'tara_acc_follows' ) ) {
	/**
	 * شناسهٔ ترم‌های «خواننده» که کاربر دنبال می‌کند.
	 *
	 * @param int $user_id شناسه.
	 * @return int[]
	 */
	function tara_acc_follows( $user_id = 0 ) {
		$list = tara_acc_meta( tara_acc_uid( $user_id ), 'follows', array() );
		if ( ! is_array( $list ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'absint', $list ) ) );
	}
}

if ( ! function_exists( 'tara_acc_following' ) ) {
	/** آیا کاربر این خواننده را دنبال می‌کند؟ */
	function tara_acc_following( $term_id, $user_id = 0 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		$term_id = (int) $term_id;
		if ( $user_id <= 0 || $term_id <= 0 ) {
			return false;
		}
		return in_array( $term_id, tara_acc_follows( $user_id ), true );
	}
}

if ( ! function_exists( 'tara_acc_follower_count' ) ) {
	/**
	 * تعدادِ دنبال‌کننده‌های یک خواننده (شمارندهٔ term_meta).
	 *
	 * @param int $term_id شناسه.
	 * @return int
	 */
	function tara_acc_follower_count( $term_id ) {
		return max( 0, (int) get_term_meta( (int) $term_id, 'tara_followers', true ) );
	}
}

if ( ! function_exists( 'tara_acc_follow' ) ) {
	/**
	 * دنبال‌کردن/رهاکردنِ یک خواننده.
	 *
	 * @param int  $term_id شناسهٔ ترمِ خواننده.
	 * @param int  $user_id کاربر (۰ = جاری).
	 * @param bool $on      true/false/null.
	 * @return array{following:bool,count:int}|WP_Error
	 */
	function tara_acc_follow( $term_id, $user_id = 0, $on = null ) {
		$term_id = (int) $term_id;
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return new WP_Error( 'tara_guest', 'برای دنبال‌کردنِ خواننده باید وارد شوید.' );
		}
		if ( $term_id <= 0 ) {
			return new WP_Error( 'tara_no_term', 'خواننده پیدا نشد.' );
		}
		$tax  = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
		$term = function_exists( 'get_term' ) ? get_term( $term_id, $tax ) : null;
		if ( ! $term || ( function_exists( 'is_wp_error' ) && is_wp_error( $term ) ) ) {
			return new WP_Error( 'tara_no_term', 'خواننده پیدا نشد.' );
		}

		$list   = tara_acc_follows( $user_id );
		$has    = in_array( $term_id, $list, true );
		$target = ( null === $on ) ? ! $has : (bool) $on;

		if ( $target && ! $has ) {
			if ( count( $list ) >= tara_acc_limit( 'max_follows' ) ) {
				return new WP_Error( 'tara_follows_full', 'تعدادِ خواننده‌های دنبال‌شده به سقف رسید.' );
			}
			array_unshift( $list, $term_id );
		} elseif ( ! $target && $has ) {
			$list = array_values(
				array_filter(
					$list,
					function ( $id ) use ( $term_id ) {
						return (int) $id !== $term_id;
					}
				)
			);
		}

		tara_acc_save( $user_id, 'follows', array_slice( $list, 0, tara_acc_limit( 'max_follows' ) ) );

		$count = tara_acc_follower_count( $term_id );
		$count += ( $target && ! $has ) ? 1 : 0;
		$count -= ( ! $target && $has ) ? 1 : 0;
		update_term_meta( $term_id, 'tara_followers', max( 0, $count ) );

		return array(
			'following' => $target,
			'count'     => max( 0, $count ),
		);
	}
}

/* ---- ۶-۳) پلی‌لیست‌ها ---- */

if ( ! function_exists( 'tara_acc_playlists' ) ) {
	/**
	 * همهٔ پلی‌لیست‌های کاربر.
	 *
	 * @param int $user_id شناسه.
	 * @return array
	 */
	function tara_acc_playlists( $user_id = 0 ) {
		$list = tara_acc_meta( tara_acc_uid( $user_id ), 'playlists', array() );
		if ( ! is_array( $list ) ) {
			return array();
		}
		$out = array();
		foreach ( $list as $pl ) {
			if ( ! is_array( $pl ) || empty( $pl['k'] ) ) {
				continue;
			}
			$tracks = isset( $pl['t'] ) && is_array( $pl['t'] ) ? array_values( array_filter( array_map( 'absint', $pl['t'] ) ) ) : array();
			$out[]  = array(
				'k' => (string) $pl['k'],
				'n' => isset( $pl['n'] ) ? (string) $pl['n'] : 'پلی‌لیست',
				't' => $tracks,
				'p' => ! empty( $pl['p'] ) ? 1 : 0,
				'c' => isset( $pl['c'] ) ? (int) $pl['c'] : 0,
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'tara_acc_playlist' ) ) {
	/**
	 * یک پلی‌لیست با کلید.
	 *
	 * @param string $key     کلید.
	 * @param int    $user_id شناسه.
	 * @return array|null
	 */
	function tara_acc_playlist( $key, $user_id = 0 ) {
		$key = (string) $key;
		foreach ( tara_acc_playlists( $user_id ) as $pl ) {
			if ( $pl['k'] === $key ) {
				return $pl;
			}
		}
		return null;
	}
}

if ( ! function_exists( 'tara_acc_playlist_key' ) ) {
	/** کلیدِ تصادفی و کوتاه برای پلی‌لیست. */
	function tara_acc_playlist_key() {
		$seed = function_exists( 'wp_generate_password' ) ? wp_generate_password( 8, false, false ) : (string) wp_rand( 100000, 999999 );
		return strtolower( substr( preg_replace( '/[^a-zA-Z0-9]/', '', (string) $seed ), 0, 8 ) );
	}
}

if ( ! function_exists( 'tara_acc_playlist_save' ) ) {
	/**
	 * ذخیرهٔ کلِ فهرستِ پلی‌لیست‌ها (سقف‌دار).
	 *
	 * @param int   $user_id شناسه.
	 * @param array $list    فهرست.
	 * @return void
	 */
	function tara_acc_playlist_save( $user_id, $list ) {
		$list = array_slice( array_values( (array) $list ), 0, tara_acc_limit( 'max_playlists' ) );
		tara_acc_save( $user_id, 'playlists', $list );
	}
}

if ( ! function_exists( 'tara_acc_playlist_create' ) ) {
	/**
	 * ساختِ پلی‌لیستِ تازه.
	 *
	 * @param string $name    نام.
	 * @param int    $user_id شناسه.
	 * @return array|WP_Error
	 */
	function tara_acc_playlist_create( $name, $user_id = 0 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return new WP_Error( 'tara_guest', 'برای ساختِ پلی‌لیست باید وارد شوید.' );
		}

		$name = trim( sanitize_text_field( (string) $name ) );
		if ( '' === $name ) {
			$name = 'پلی‌لیستِ تازه';
		}
		if ( function_exists( 'mb_substr' ) ) {
			$name = mb_substr( $name, 0, 60 );
		}

		$list = tara_acc_playlists( $user_id );
		if ( count( $list ) >= tara_acc_limit( 'max_playlists' ) ) {
			return new WP_Error( 'tara_pl_full', 'تعدادِ پلی‌لیست‌ها به سقف رسید.' );
		}

		$key = tara_acc_playlist_key();
		while ( tara_acc_playlist( $key, $user_id ) ) {
			$key = tara_acc_playlist_key();
		}

		$item = array(
			'k' => $key,
			'n' => $name,
			't' => array(),
			'p' => 0,
			'c' => time(),
		);
		array_unshift( $list, $item );
		tara_acc_playlist_save( $user_id, $list );

		return $item;
	}
}

if ( ! function_exists( 'tara_acc_playlist_update' ) ) {
	/**
	 * تغییرِ نام/عمومی‌بودنِ پلی‌لیست.
	 *
	 * @param string $key     کلید.
	 * @param array  $args    name/public.
	 * @param int    $user_id شناسه.
	 * @return bool
	 */
	function tara_acc_playlist_update( $key, $args, $user_id = 0 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return false;
		}
		$list = tara_acc_playlists( $user_id );
		$hit  = false;
		foreach ( $list as $i => $pl ) {
			if ( $pl['k'] !== (string) $key ) {
				continue;
			}
			if ( isset( $args['name'] ) ) {
				$name = trim( sanitize_text_field( (string) $args['name'] ) );
				if ( '' !== $name ) {
					$list[ $i ]['n'] = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 60 ) : substr( $name, 0, 60 );
				}
			}
			if ( isset( $args['public'] ) ) {
				$list[ $i ]['p'] = ! empty( $args['public'] ) ? 1 : 0;
			}
			$hit = true;
			break;
		}
		if ( $hit ) {
			tara_acc_playlist_save( $user_id, $list );
		}
		return $hit;
	}
}

if ( ! function_exists( 'tara_acc_playlist_delete' ) ) {
	/**
	 * حذفِ پلی‌لیست.
	 *
	 * @param string $key     کلید.
	 * @param int    $user_id شناسه.
	 * @return bool
	 */
	function tara_acc_playlist_delete( $key, $user_id = 0 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return false;
		}
		$list  = tara_acc_playlists( $user_id );
		$after = array_values(
			array_filter(
				$list,
				function ( $pl ) use ( $key ) {
					return $pl['k'] !== (string) $key;
				}
			)
		);
		if ( count( $after ) === count( $list ) ) {
			return false;
		}
		tara_acc_playlist_save( $user_id, $after );
		return true;
	}
}

if ( ! function_exists( 'tara_acc_playlist_toggle' ) ) {
	/**
	 * افزودن/برداشتنِ یک آهنگ در پلی‌لیست.
	 *
	 * @param string $key     کلید.
	 * @param int    $post_id آهنگ.
	 * @param int    $user_id کاربر.
	 * @param bool   $on      true/false/null.
	 * @return array{added:bool,count:int}|WP_Error
	 */
	function tara_acc_playlist_toggle( $key, $post_id, $user_id = 0, $on = null ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return new WP_Error( 'tara_guest', 'برای ذخیره باید وارد شوید.' );
		}
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return new WP_Error( 'tara_no_post', 'آهنگ پیدا نشد.' );
		}

		$list   = tara_acc_playlists( $user_id );
		$target = null;
		foreach ( $list as $i => $pl ) {
			if ( $pl['k'] !== (string) $key ) {
				continue;
			}
			$tracks = $pl['t'];
			$has    = in_array( $post_id, $tracks, true );
			$want   = ( null === $on ) ? ! $has : (bool) $on;
			if ( $want && ! $has ) {
				if ( count( $tracks ) >= tara_acc_limit( 'max_tracks' ) ) {
					return new WP_Error( 'tara_tracks_full', 'این پلی‌لیست پر شده است.' );
				}
				array_unshift( $tracks, $post_id );
			} elseif ( ! $want && $has ) {
				$tracks = array_values(
					array_filter(
						$tracks,
						function ( $id ) use ( $post_id ) {
							return (int) $id !== $post_id;
						}
					)
				);
			}
			$list[ $i ]['t'] = array_values( array_unique( $tracks ) );
			$target          = array(
				'added' => $want,
				'count' => count( $list[ $i ]['t'] ),
			);
			break;
		}

		if ( null === $target ) {
			return new WP_Error( 'tara_no_playlist', 'پلی‌لیست پیدا نشد.' );
		}

		tara_acc_playlist_save( $user_id, $list );
		return $target;
	}
}

/* ---- ۶-۴) تاریخچهٔ شنیدن ---- */

if ( ! function_exists( 'tara_acc_history' ) ) {
	/**
	 * تاریخچهٔ شنیدن (تازه‌ترین اول).
	 *
	 * @param int $user_id شناسه.
	 * @return array<int,array{i:int,t:int}>
	 */
	function tara_acc_history( $user_id = 0 ) {
		$list = tara_acc_meta( tara_acc_uid( $user_id ), 'history', array() );
		if ( ! is_array( $list ) ) {
			return array();
		}
		$out = array();
		foreach ( $list as $row ) {
			if ( is_array( $row ) && ! empty( $row['i'] ) ) {
				$out[] = array( 'i' => (int) $row['i'], 't' => isset( $row['t'] ) ? (int) $row['t'] : 0 );
			} elseif ( is_numeric( $row ) ) {
				$out[] = array( 'i' => (int) $row, 't' => 0 );
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'tara_acc_history_push' ) ) {
	/**
	 * ثبتِ یک آهنگ در تاریخچه (بدونِ تکرار؛ سقف‌دار).
	 *
	 * @param int $post_id آهنگ.
	 * @param int $user_id کاربر.
	 * @return bool
	 */
	function tara_acc_history_push( $post_id, $user_id = 0 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		$post_id = (int) $post_id;
		if ( $user_id <= 0 || $post_id <= 0 ) {
			return false;
		}

		$list = array_values(
			array_filter(
				tara_acc_history( $user_id ),
				function ( $row ) use ( $post_id ) {
					return (int) $row['i'] !== $post_id;
				}
			)
		);
		array_unshift( $list, array( 'i' => $post_id, 't' => time() ) );
		tara_acc_save( $user_id, 'history', array_slice( $list, 0, tara_acc_limit( 'max_history' ) ) );

		return true;
	}
}

/* ---- ۶-۵) پیام‌ها / اعلان‌ها ---- */

if ( ! function_exists( 'tara_acc_notify' ) ) {
	/**
	 * افزودنِ یک پیام به فهرستِ پیام‌های کاربر.
	 *
	 * @param int   $user_id شناسه.
	 * @param array $item    k/n/u (کلید، متن، آدرس).
	 * @return bool
	 */
	function tara_acc_notify( $user_id, $item ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 || empty( $item['title'] ) ) {
			return false;
		}
		$list = tara_acc_meta( $user_id, 'notifs', array() );
		$list = is_array( $list ) ? $list : array();
		array_unshift(
			$list,
			array(
				'k' => isset( $item['k'] ) ? (string) $item['k'] : '',
				'n' => (string) $item['title'],
				'u' => isset( $item['url'] ) ? (string) $item['url'] : '',
				't' => time(),
				'r' => 0,
			)
		);
		tara_acc_save( $user_id, 'notifs', array_slice( $list, 0, 40 ) );

		return true;
	}
}

if ( ! function_exists( 'tara_acc_notifs' ) ) {
	/**
	 * فهرستِ پیام‌ها.
	 *
	 * @param int $user_id شناسه.
	 * @return array
	 */
	function tara_acc_notifs( $user_id = 0 ) {
		$list = tara_acc_meta( tara_acc_uid( $user_id ), 'notifs', array() );
		return is_array( $list ) ? array_values( $list ) : array();
	}
}

if ( ! function_exists( 'tara_acc_unread' ) ) {
	/**
	 * تعدادِ پیامِ خوانده‌نشده + آهنگ‌های تازه از خواننده‌های دنبال‌شده.
	 *
	 * @param int $user_id شناسه.
	 * @return int
	 */
	function tara_acc_unread( $user_id = 0 ) {
		$user_id = tara_acc_uid( $user_id );
		if ( $user_id <= 0 ) {
			return 0;
		}
		$count = 0;
		foreach ( tara_acc_notifs( $user_id ) as $row ) {
			if ( empty( $row['r'] ) ) {
				$count++;
			}
		}
		if ( tara_acc_feature( 'notifs' ) ) {
			$count += count( tara_acc_feed( $user_id, array( 'since' => (int) tara_acc_meta( $user_id, 'feed_seen', 0 ), 'limit' => 20 ) ) );
		}
		return max( 0, $count );
	}
}

if ( ! function_exists( 'tara_acc_notifs_read' ) ) {
	/**
	 * خوانده‌شدنِ همهٔ پیام‌ها + به‌روزرسانیِ نشانگرِ فید.
	 *
	 * @param int $user_id شناسه.
	 * @return void
	 */
	function tara_acc_notifs_read( $user_id = 0 ) {
		$user_id = tara_acc_uid( $user_id );
		if ( $user_id <= 0 ) {
			return;
		}
		$list = tara_acc_notifs( $user_id );
		foreach ( $list as $i => $row ) {
			$list[ $i ]['r'] = 1;
		}
		tara_acc_save( $user_id, 'notifs', $list );
		tara_acc_save( $user_id, 'feed_seen', time() );
	}
}

/* ---- ۶-۶) فیدِ «از خواننده‌هایی که دنبال می‌کنید» ---- */

if ( ! function_exists( 'tara_acc_feed' ) ) {
	/**
	 * تازه‌ترین آهنگ‌های خواننده‌های دنبال‌شده.
	 *
	 * @param int   $user_id شناسه.
	 * @param array $args    since/limit/exclude.
	 * @return array[] هر آیتم: id,title,artist,artist_id,cover,link,src,date
	 */
	function tara_acc_feed( $user_id = 0, $args = array() ) {
		$user_id = tara_acc_uid( $user_id );
		$args    = wp_parse_args(
			$args,
			array(
				'since'   => 0,
				'limit'   => 12,
				'exclude' => array(),
			)
		);
		$terms = tara_acc_follows( $user_id );
		if ( empty( $terms ) || ! tara_acc_feature( 'follows' ) ) {
			return array();
		}

		$tax = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
		$q   = array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'posts_per_page'   => max( 1, min( 40, (int) $args['limit'] ) ),
			'ignore_sticky_posts' => true,
			'no_found_rows'    => true,
			'fields'           => 'ids',
			'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => $tax,
					'field'    => 'term_id',
					'terms'    => array_map( 'absint', $terms ),
				),
			),
		);
		if ( (int) $args['since'] > 0 ) {
			$q['date_query'] = array(
				array(
					'after'     => gmdate( 'Y-m-d H:i:s', (int) $args['since'] ),
					'inclusive' => false,
					'column'    => 'post_date_gmt',
				),
			);
		}
		$exclude = array_values( array_filter( array_map( 'absint', (array) $args['exclude'] ) ) );
		if ( ! empty( $exclude ) ) {
			$q['post__not_in'] = $exclude; // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		$ids = get_posts( $q );
		if ( ! is_array( $ids ) ) {
			return array();
		}

		$out = array();
		foreach ( $ids as $post_id ) {
			$post_id = (int) $post_id;
			$src     = function_exists( 'tara_track_src' ) ? (string) tara_track_src( $post_id ) : '';
			if ( '' === $src ) {
				continue;
			}
			$term_id = function_exists( 'tara_radio_artist_id' ) ? (int) tara_radio_artist_id( $post_id ) : 0;
			$out[]   = array(
				'id'        => $post_id,
				'title'     => function_exists( 'tara_track_title' ) ? (string) tara_track_title( $post_id ) : get_the_title( $post_id ),
				'artist'    => function_exists( 'tara_track_artist' ) ? (string) tara_track_artist( $post_id ) : '',
				'artist_id' => $term_id,
				'cover'     => function_exists( 'tara_track_cover' ) ? (string) tara_track_cover( $post_id, 'thumb3' ) : '',
				'link'      => (string) get_permalink( $post_id ),
				'src'       => $src,
				'date'      => (int) get_post_time( 'U', true, $post_id ),
			);
		}

		return $out;
	}
}

if ( ! function_exists( 'tara_acc_counts' ) ) {
	/**
	 * عددهای داشبورد (با کشِ کوتاه).
	 *
	 * @param int $user_id شناسه.
	 * @return array
	 */
	function tara_acc_counts( $user_id = 0 ) {
		$user_id = tara_acc_uid( $user_id );
		if ( $user_id <= 0 ) {
			return array( 'likes' => 0, 'playlists' => 0, 'follows' => 0, 'history' => 0, 'unread' => 0, 'tracks' => 0 );
		}
		$cached = get_transient( 'tara_acc_counts_' . $user_id );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$playlists = tara_acc_playlists( $user_id );
		$tracks    = 0;
		foreach ( $playlists as $pl ) {
			$tracks += count( $pl['t'] );
		}
		$counts = array(
			'likes'     => count( tara_acc_likes( $user_id ) ),
			'playlists' => count( $playlists ),
			'follows'   => count( tara_acc_follows( $user_id ) ),
			'history'   => count( tara_acc_history( $user_id ) ),
			'unread'    => tara_acc_unread( $user_id ),
			'tracks'    => $tracks,
		);
		set_transient( 'tara_acc_counts_' . $user_id, $counts, 5 * MINUTE_IN_SECONDS );

		return $counts;
	}
}

/* -------------------------------------------------------------------------
 * ۷) اتصال به رباتِ تلگرام — کدِ یک‌بارمصرف و لینک
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_user_by_tg' ) ) {
	/**
	 * کاربری که این شناسهٔ تلگرام به آن وصل است.
	 *
	 * @param int $tg_id شناسهٔ تلگرام.
	 * @return int شناسهٔ کاربر (۰ = هیچ).
	 */
	function tara_acc_user_by_tg( $tg_id ) {
		$tg_id = (int) $tg_id;
		if ( $tg_id <= 0 || ! function_exists( 'get_users' ) ) {
			return 0;
		}
		$users = get_users(
			array(
				'meta_key'    => 'tara_tg_id', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'  => $tg_id,       // phpcs:ignore WordPress.DB.SlowDBQuery
				'number'      => 1,
				'count_total' => false,
			)
		);
		if ( is_array( $users ) && ! empty( $users[0] ) ) {
			return (int) $users[0]->ID;
		}
		return 0;
	}
}

if ( ! function_exists( 'tara_acc_bot_user' ) ) {
	/**
	 * نامِ کاربریِ رباتِ تلگرام (بدونِ @).
	 *
	 * اگر در تنظیمات خالی باشد، رباتِ رسمیِ همین سایت (`ahangbanbot`) برمی‌گردد
	 * تا دکمهٔ «مشاهده در ربات» و لینکِ اتصال از روزِ اول کار کنند؛ مدیر با
	 * گذاشتنِ نامِ دلخواه در تنظیمات قالب، آن را عوض می‌کند.
	 *
	 * @return string
	 */
	function tara_acc_bot_user() {
		$bot = preg_replace( '/[^A-Za-z0-9_]/', '', (string) tara_acc_opt( 'tg_bot' ) );
		if ( '' === $bot ) {
			$bot = 'ahangbanbot';
		}

		return (string) apply_filters( 'tara_acc_bot_user', $bot );
	}
}

if ( ! function_exists( 'tara_acc_bot_song_url' ) ) {
	/**
	 * لینکِ دیپ‌لینکِ آهنگ در ربات تلگرام.
	 *
	 * قالبِ قراردادِ ربات: `start=song_<شناسهٔ نوشته>` — همان چیزی که
	 * کانال/ربات برای هر آهنگ می‌سازد و در `handlers/start.py` با
	 * `song_([1-9][0-9]*)` خوانده می‌شود.
	 *
	 * @param int $post_id شناسهٔ آهنگ.
	 * @return string خالی اگر دکمه در تنظیمات خاموش باشد یا شناسه نامعتبر باشد.
	 */
	function tara_acc_bot_song_url( $post_id = 0 ) {
		$post_id = (int) $post_id;
		if ( $post_id < 0 ) {
			return '';
		}
		if ( 0 === $post_id ) {
			$post_id = (int) get_the_ID();
		}
		if ( $post_id <= 0 ) {
			return '';
		}
		if ( ! in_array( strtolower( (string) tara_acc_opt( 'tg_song', 'on' ) ), array( 'on', '1', 'true', 'yes' ), true ) ) {
			return '';
		}
		$bot = tara_acc_bot_user();
		if ( '' === $bot ) {
			return '';
		}

		return 'https://t.me/' . $bot . '?start=song_' . $post_id;
	}
}

if ( ! function_exists( 'tara_acc_link_code' ) ) {
	/**
	 * ساختِ کدِ اتصال به ربات (۸ کاراکترِ بی‌ابهام).
	 *
	 * @param int $user_id شناسهٔ کاربر.
	 * @return array{code:string,ttl:int,deeplink:string}|WP_Error
	 */
	function tara_acc_link_code( $user_id = 0 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return new WP_Error( 'tara_guest', 'برای اتصال به ربات باید وارد شوید.' );
		}

		$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$code     = '';
		for ( $i = 0; $i < 8; $i++ ) {
			$idx   = function_exists( 'random_int' ) ? random_int( 0, strlen( $alphabet ) - 1 ) : wp_rand( 0, strlen( $alphabet ) - 1 );
			$code .= $alphabet[ $idx ];
		}

		$ttl = max( 120, min( 1800, (int) tara_acc_opt( 'tg_code_ttl', 600 ) ) );
		set_transient( 'tara_link_' . $code, $user_id, $ttl );

		$bot = tara_acc_bot_user();

		return array(
			'code'     => $code,
			'ttl'      => $ttl,
			'deeplink' => '' !== $bot ? 'https://t.me/' . $bot . '?start=tara_' . $code : '',
			'bot'      => $bot,
		);
	}
}

if ( ! function_exists( 'tara_acc_link_consume' ) ) {
	/**
	 * مصرفِ کدِ اتصال و وصل‌کردنِ تلگرام به حساب.
	 *
	 * @param string $code     کدِ ۸کاراکتری (بدونِ پیشوندِ tara_).
	 * @param int    $tg_id    شناسهٔ تلگرام.
	 * @param string $username نام‌کاربریِ تلگرام (اختیاری).
	 * @param string $name     نامِ نمایشی در تلگرام (اختیاری).
	 * @return int|WP_Error شناسهٔ کاربر.
	 */
	function tara_acc_link_consume( $code, $tg_id, $username = '', $name = '' ) {
		$code  = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $code ) );
		$tg_id = (int) $tg_id;

		if ( 0 === strpos( $code, 'TARA' ) && strlen( $code ) > 4 ) {
			$code = substr( $code, 4 );
		}
		if ( strlen( $code ) < 6 || $tg_id <= 0 ) {
			return new WP_Error( 'tara_bad_link', 'کدِ اتصال نامعتبر است.' );
		}

		$key     = 'tara_link_' . $code;
		$user_id = (int) get_transient( $key );
		if ( $user_id <= 0 ) {
			return new WP_Error( 'tara_link_expired', 'این کد منقضی شده است؛ از پنلِ «حساب من» کدِ تازه بگیرید.' );
		}

		/* هر حسابِ دیگر که به همین تلگرام وصل بود، جدا می‌شود. */
		$other = tara_acc_user_by_tg( $tg_id );
		if ( $other > 0 && $other !== $user_id ) {
			delete_user_meta( $other, 'tara_tg_id' );
			delete_user_meta( $other, 'tara_tg_user' );
		}

		update_user_meta( $user_id, 'tara_tg_id', $tg_id );
		update_user_meta( $user_id, 'tara_tg_user', sanitize_text_field( (string) $username ) );
		update_user_meta( $user_id, 'tara_tg_name', sanitize_text_field( (string) $name ) );
		update_user_meta( $user_id, 'tara_tg_at', time() );

		delete_transient( $key );

		tara_acc_notify(
			$user_id,
			array(
				'k'     => 'tg',
				'title' => 'تلگرامِ شما به حساب وصل شد؛ از این به بعد پیام‌های ربات هم به همین حساب می‌رسد.',
				'url'   => tara_acc_profile_url( 'telegram' ),
			)
		);

		return $user_id;
	}
}

/* -------------------------------------------------------------------------
 * ۸) AJAX — همهٔ کنش‌های پنل از یک نقطه
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_json_error' ) ) {
	/**
	 * خطای AJAX با پیامِ فارسی و کدِ وضعیت.
	 *
	 * @param string $message پیام.
	 * @param array  $extra   دادهٔ اضافه.
	 * @return void
	 */
	function tara_acc_json_error( $message, $extra = array() ) {
		wp_send_json_error( array_merge( array( 'message' => (string) $message ), (array) $extra ) );
	}
}

if ( ! function_exists( 'tara_acc_require_login' ) ) {
	/**
	 * کاربر را می‌خواهد یا با خطای «نیاز به ورود» برمی‌گردد.
	 *
	 * @return int شناسهٔ کاربر (در صورتِ نبود، پاسخ داده و ۰ برمی‌گردد).
	 */
	function tara_acc_require_login() {
		$user_id = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
		if ( $user_id <= 0 ) {
			tara_acc_json_error( 'برای این کار باید وارد شوید.', array( 'login' => 1 ) );
			return 0;
		}
		return $user_id;
	}
}

if ( ! function_exists( 'tara_acc_ajax' ) ) {
	/**
	 * مسیریابِ AJAX.
	 *
	 * @return void
	 */
	function tara_acc_ajax() {
		if ( ! tara_acc_on() ) {
			tara_acc_json_error( 'حسابِ کاربری در این سایت خاموش است.' );
		}

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'tara_account' ) ) {
			tara_acc_json_error( 'نشستِ صفحه منقضی شده است؛ صفحه را یک‌بار تازه کنید.', array( 'reload' => 1 ) );
		}

		$do   = isset( $_REQUEST['do'] ) ? sanitize_key( wp_unslash( (string) $_REQUEST['do'] ) ) : '';
		$post = function ( $key, $default = '' ) {
			return isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : $default;
		};

		switch ( $do ) {
			/* ---------- ورود ---------- */
			case 'send':
				$phone = tara_acc_phone( (string) $post( 'phone' ) );
				if ( '' === $phone ) {
					tara_acc_json_error( 'شمارهٔ موبایل را کامل و درست وارد کنید.' );
				}
				$res = tara_acc_otp_send( $phone );
				if ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) {
					$code = $res->get_error_data();
					tara_acc_json_error( $res->get_error_message(), is_array( $code ) ? $code : array() );
				}
				wp_send_json_success( $res );
				break;

			case 'verify':
				$phone = tara_acc_phone( (string) $post( 'phone' ) );
				$code  = tara_acc_digits( (string) $post( 'code' ) );
				if ( '' === $phone || 6 !== strlen( $code ) ) {
					tara_acc_json_error( 'شماره یا کد را درست وارد کنید.' );
				}
				if ( ! tara_acc_throttle( 'otp_verify_' . $phone, 12, HOUR_IN_SECONDS ) ) {
					tara_acc_json_error( 'تلاشِ زیاد؛ کمی بعد دوباره امتحان کنید.' );
				}
				if ( ! tara_acc_otp_check( $phone, $code ) ) {
					tara_acc_json_error( 'کد نادرست یا منقضی است. دوباره درخواست کنید.' );
				}
				$user_id = tara_acc_signin( $phone, true );
				if ( function_exists( 'is_wp_error' ) && is_wp_error( $user_id ) ) {
					tara_acc_json_error( $user_id->get_error_message() );
				}
				$redirect = tara_acc_profile_url();
				$wanted   = (string) tara_acc_opt( 'redirect' );
				if ( '' !== trim( $wanted ) && preg_match( '#^https?://#i', $wanted ) ) {
					$redirect = $wanted;
				}
				wp_send_json_success(
					array(
						'user'     => array(
							'id'     => (int) $user_id,
							'name'   => (string) get_user_meta( (int) $user_id, 'nickname', true ),
							'avatar' => tara_acc_avatar_url( (int) $user_id, 64 ),
						),
						'redirect' => $redirect,
						'fresh'    => (bool) get_user_meta( (int) $user_id, 'tara_joined', true ) > ( time() - 30 ),
					)
				);
				break;

			case 'password':
				/* ورودِ کلاسیکِ وردپرس برای حساب‌های مدیریتی/قدیمی. فقط اگر
				   گزینه‌اش روشن باشد؛ پیامِ خطا عمداً کلی است تا نامِ کاربری
				   لو نرود. */
				if ( 'on' !== (string) tara_acc_opt( 'allow_password', 'on' ) ) {
					tara_acc_json_error( 'ورود با رمز خاموش است؛ از پیامک استفاده کنید.' );
				}
				if ( ! function_exists( 'wp_signon' ) ) {
					tara_acc_json_error( 'ورود با رمز در این محیط ممکن نیست.' );
				}
				if ( ! tara_acc_throttle( 'login_ip_' . tara_acc_client_ip(), 10, HOUR_IN_SECONDS ) ) {
					tara_acc_json_error( 'تلاشِ زیاد از این اتصال؛ یک ساعتِ دیگر تلاش کنید.' );
				}
				$login = sanitize_text_field( (string) $post( 'login' ) );
				$pass  = (string) $post( 'password' );
				if ( '' === $login || '' === $pass ) {
					tara_acc_json_error( 'نام‌کاربری و رمز را وارد کنید.' );
				}
				$user = wp_signon(
					array(
						'user_login'    => $login,
						'user_password' => $pass,
						'remember'      => true,
					),
					is_ssl()
				);
				if ( function_exists( 'is_wp_error' ) && is_wp_error( $user ) ) {
					tara_acc_json_error( 'نام‌کاربری یا رمز درست نیست.' );
				}
				wp_send_json_success(
					array(
						'user'     => array(
							'id'   => (int) $user->ID,
							'name' => (string) $user->display_name,
						),
						'redirect' => tara_acc_profile_url(),
					)
				);
				break;

			case 'logout':
				$user_id = (int) ( function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0 );
				if ( $user_id <= 0 ) {
					wp_send_json_success( array( 'redirect' => home_url( '/' ) ) );
				}
				if ( function_exists( 'wp_logout' ) ) {
					wp_logout();
				}
				wp_send_json_success( array( 'redirect' => home_url( '/' ) ) );
				break;

			/* ---------- علاقه‌مندی ---------- */
			case 'like':
				$user_id = tara_acc_require_login();
				if ( ! $user_id ) {
					return;
				}
				if ( ! tara_acc_feature( 'likes' ) ) {
					tara_acc_json_error( 'این قابلیت خاموش است.' );
				}
				$on = isset( $_POST['on'] ) ? (bool) absint( $post( 'on' ) ) : null;
				$res = tara_acc_like( (int) $post( 'post' ), $user_id, $on );
				if ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) {
					tara_acc_json_error( $res->get_error_message() );
				}
				wp_send_json_success( $res );
				break;

			/* ---------- دنبال‌کردن ---------- */
			case 'follow':
				$user_id = tara_acc_require_login();
				if ( ! $user_id ) {
					return;
				}
				if ( ! tara_acc_feature( 'follows' ) ) {
					tara_acc_json_error( 'این قابلیت خاموش است.' );
				}
				$on  = isset( $_POST['on'] ) ? (bool) absint( $post( 'on' ) ) : null;
				$res = tara_acc_follow( (int) $post( 'term' ), $user_id, $on );
				if ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) {
					tara_acc_json_error( $res->get_error_message() );
				}
				wp_send_json_success( $res );
				break;

			/* ---------- پلی‌لیست ---------- */
			case 'playlist':
				$user_id = tara_acc_require_login();
				if ( ! $user_id ) {
					return;
				}
				if ( ! tara_acc_feature( 'playlists' ) ) {
					tara_acc_json_error( 'این قابلیت خاموش است.' );
				}
				$op  = sanitize_key( (string) $post( 'op' ) );
				$key = sanitize_text_field( (string) $post( 'key' ) );

				if ( 'create' === $op ) {
					$res = tara_acc_playlist_create( (string) $post( 'name' ), $user_id );
				} elseif ( 'rename' === $op ) {
					$res = tara_acc_playlist_update( $key, array( 'name' => (string) $post( 'name' ) ), $user_id ) ? array( 'ok' => true ) : new WP_Error( 'tara_pl', 'پلی‌لیست پیدا نشد.' );
				} elseif ( 'public' === $op ) {
					$res = tara_acc_playlist_update( $key, array( 'public' => (bool) absint( $post( 'on' ) ) ), $user_id ) ? array( 'ok' => true ) : new WP_Error( 'tara_pl', 'پلی‌لیست پیدا نشد.' );
				} elseif ( 'delete' === $op ) {
					$res = tara_acc_playlist_delete( $key, $user_id ) ? array( 'ok' => true ) : new WP_Error( 'tara_pl', 'پلی‌لیست پیدا نشد.' );
				} elseif ( 'toggle' === $op ) {
					$on  = isset( $_POST['on'] ) ? (bool) absint( $post( 'on' ) ) : null;
					$res = tara_acc_playlist_toggle( $key, (int) $post( 'post' ), $user_id, $on );
				} else {
					$res = new WP_Error( 'tara_pl_op', 'درخواستِ نامعتبر.' );
				}

				if ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) {
					tara_acc_json_error( $res->get_error_message() );
				}
				$res['playlists'] = tara_acc_playlists( $user_id );
				wp_send_json_success( $res );
				break;

			/* ---------- تاریخچه ---------- */
			case 'history':
				$user_id = (int) ( function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0 );
				if ( $user_id <= 0 || ! tara_acc_feature( 'history' ) ) {
					wp_send_json_success( array( 'skipped' => true ) );
				}
				tara_acc_history_push( (int) $post( 'post' ), $user_id );
				wp_send_json_success( array( 'ok' => true ) );
				break;

			case 'history-clear':
				$user_id = tara_acc_require_login();
				if ( ! $user_id ) {
					return;
				}
				tara_acc_save( (int) $user_id, 'history', array() );
				wp_send_json_success( array( 'cleared' => true ) );
				break;

			/* ---------- پروفایل ---------- */
			case 'profile':
				$user_id = tara_acc_require_login();
				if ( ! $user_id ) {
					return;
				}
				$name = trim( sanitize_text_field( (string) $post( 'name' ) ) );
				$bio  = trim( sanitize_textarea_field( (string) $post( 'bio' ) ) );
				if ( '' === $name ) {
					tara_acc_json_error( 'نام نمی‌تواند خالی باشد.' );
				}
				if ( function_exists( 'mb_substr' ) ) {
					$name = mb_substr( $name, 0, 50 );
					$bio  = mb_substr( $bio, 0, 300 );
				}
				if ( function_exists( 'wp_update_user' ) ) {
					wp_update_user(
						array(
							'ID'           => (int) $user_id,
							'display_name' => $name,
							'nickname'     => $name,
							'description'  => $bio,
						)
					);
				} else {
					update_user_meta( (int) $user_id, 'nickname', $name );
					update_user_meta( (int) $user_id, 'description', $bio );
				}
				wp_send_json_success(
					array(
						'name'   => $name,
						'bio'    => $bio,
						'avatar' => tara_acc_avatar_url( (int) $user_id, 96 ),
					)
				);
				break;

			/* ---------- تصویرِ پروفایل ---------- */
			case 'avatar-url':
				$user_id = tara_acc_require_login();
				if ( ! $user_id ) {
					return;
				}
				$url = esc_url_raw( (string) $post( 'url' ) );
				if ( '' !== $url && ! preg_match( '#^https?://#i', $url ) ) {
					tara_acc_json_error( 'آدرسِ تصویر معتبر نیست.' );
				}
				update_user_meta( (int) $user_id, 'tara_avatar_url', $url );
				update_user_meta( (int) $user_id, 'tara_avatar', 0 );
				wp_send_json_success( array( 'url' => '' !== $url ? $url : tara_acc_avatar_url( (int) $user_id, 96 ) ) );
				break;

			/* ---------- اتصال به ربات ---------- */
			case 'tg-code':
				$user_id = tara_acc_require_login();
				if ( ! $user_id ) {
					return;
				}
				$res = tara_acc_link_code( $user_id );
				if ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) {
					tara_acc_json_error( $res->get_error_message() );
				}
				wp_send_json_success( $res );
				break;

			case 'tg-unlink':
				$user_id = tara_acc_require_login();
				if ( ! $user_id ) {
					return;
				}
				delete_user_meta( (int) $user_id, 'tara_tg_id' );
				delete_user_meta( (int) $user_id, 'tara_tg_user' );
				wp_send_json_success( array( 'unlinked' => true ) );
				break;

			/* ---------- پیام‌ها ---------- */
			case 'notifs-read':
				$user_id = tara_acc_require_login();
				if ( ! $user_id ) {
					return;
				}
				tara_acc_notifs_read( $user_id );
				wp_send_json_success( array( 'unread' => 0 ) );
				break;

			default:
				tara_acc_json_error( 'درخواستِ ناشناخته.' );
		}
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_ajax_tara_account', 'tara_acc_ajax' );
	add_action( 'wp_ajax_nopriv_tara_account', 'tara_acc_ajax' );
}

/* -------------------------------------------------------------------------
 * ۹) REST برای رباتِ «آهنگ‌بان»
 * ----------------------------------------------------------------------
 * همان قراردادِ افزونهٔ اتصالِ ربات: هدرِ `Authorization: Bearer <توکن>`،
 * توکنِ ۶۴رقمیِ هگز، مقایسهٔ زمان‌ثابت با hash_equals و سقفِ نرخ.
 *
 * اگر توکن در تنظیماتِ قالب نباشد، همان گزینهٔ افزونهٔ ربات
 * (`ahangban_connector_token`) خوانده می‌شود تا با نصبِ فعلی هم کار کند.
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_rest_token' ) ) {
	/**
	 * توکنِ REST.
	 *
	 * @return string
	 */
	function tara_acc_rest_token() {
		$token = trim( (string) tara_acc_opt( 'tg_token' ) );
		if ( ! preg_match( '/^[a-f0-9]{64}$/i', $token ) ) {
			$token = trim( (string) get_option( 'ahangban_connector_token', '' ) );
		}
		if ( ! preg_match( '/^[a-f0-9]{64}$/i', $token ) ) {
			$token = '';
		}
		return (string) apply_filters( 'tara_acc_rest_token', $token );
	}
}

if ( ! function_exists( 'tara_acc_rest_auth' ) ) {
	/**
	 * احرازِ هویتِ درخواست‌های ربات.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return bool|WP_Error
	 */
	function tara_acc_rest_auth( $request ) {
		$header = '';
		if ( is_object( $request ) && method_exists( $request, 'get_header' ) ) {
			$header = trim( (string) $request->get_header( 'authorization' ) );
			if ( '' === $header ) {
				$header = trim( (string) $request->get_header( 'x-tara-token' ) );
			}
			if ( '' === $header ) {
				$header = trim( (string) $request->get_header( 'x-ahangban-token' ) );
			}
		}
		$token = '';
		if ( preg_match( '/^Bearer\s+(.+)$/i', $header, $m ) ) {
			$token = trim( $m[1] );
		} elseif ( '' !== $header ) {
			$token = $header;
		}

		if ( '' === $token ) {
			return new WP_Error( 'tara_no_auth', 'هویت تأیید نشد.', array( 'status' => 401 ) );
		}

		$expected = tara_acc_rest_token();
		if ( '' === $expected ) {
			return new WP_Error( 'tara_auth_unset', 'توکنِ ربات در تنظیمات قالب تنظیم نشده است.', array( 'status' => 503 ) );
		}
		if ( ! hash_equals( $expected, $token ) ) {
			return new WP_Error( 'tara_forbidden', 'توکن نامعتبر است.', array( 'status' => 403 ) );
		}

		if ( ! tara_acc_throttle( 'rest_ip_' . tara_acc_client_ip(), 120, MINUTE_IN_SECONDS ) ) {
			return new WP_Error( 'tara_rate_ip', 'تعدادِ درخواست‌ها زیاد است.', array( 'status' => 429 ) );
		}

		return true;
	}
}

if ( ! function_exists( 'tara_acc_rest_user' ) ) {
	/**
	 * کاربرِ متناظر با شناسهٔ تلگرام.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return int|WP_Error
	 */
	function tara_acc_rest_user( $request ) {
		$tg_id = (int) $request->get_param( 'telegram_id' );
		if ( $tg_id <= 0 ) {
			return new WP_Error( 'tara_no_tg', 'شناسهٔ تلگرام لازم است.', array( 'status' => 400 ) );
		}
		if ( ! tara_acc_throttle( 'rest_tg_' . $tg_id, 80, MINUTE_IN_SECONDS ) ) {
			return new WP_Error( 'tara_rate_tg', 'تعدادِ درخواست‌ها زیاد است.', array( 'status' => 429 ) );
		}
		$user_id = tara_acc_user_by_tg( $tg_id );
		if ( $user_id <= 0 ) {
			return new WP_Error( 'tara_unlinked', 'حسابِ سایت به این تلگرام وصل نیست.', array( 'status' => 404 ) );
		}
		return $user_id;
	}
}

if ( ! function_exists( 'tara_acc_rest_summary' ) ) {
	/**
	 * خلاصهٔ کاربر برای ربات.
	 *
	 * @param int $user_id شناسه.
	 * @return array
	 */
	function tara_acc_rest_summary( $user_id ) {
		$user_id = (int) $user_id;
		$counts  = tara_acc_counts( $user_id );
		$tax     = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
		$follows = array();
		foreach ( tara_acc_follows( $user_id ) as $term_id ) {
			$term = get_term( $term_id, $tax );
			if ( ! $term || ( function_exists( 'is_wp_error' ) && is_wp_error( $term ) ) ) {
				continue;
			}
			/* get_term_link می‌تواند WP_Error برگرداند؛ castِ مستقیم روی
			   PHP 8 خطای کشنده می‌دهد. */
			$link = function_exists( 'get_term_link' ) ? get_term_link( $term, $tax ) : '';
			if ( is_wp_error( $link ) || ! is_string( $link ) ) {
				$link = '';
			}
			$follows[] = array(
				'id'    => (int) $term_id,
				'name'  => (string) $term->name,
				'url'   => (string) $link,
				'count' => (int) $term->count,
			);
		}

		return array(
			'id'       => $user_id,
			'name'     => (string) get_user_meta( $user_id, 'nickname', true ),
			'url'      => tara_acc_profile_url(),
			'avatar'   => tara_acc_avatar_url( $user_id, 96 ),
			'counts'   => $counts,
			'follows'  => $follows,
			'linked'   => (int) get_user_meta( $user_id, 'tara_tg_id', true ) > 0,
		);
	}
}

if ( ! function_exists( 'tara_acc_rest_routes' ) ) {
	/**
	 * ثبتِ مسیرهای REST.
	 *
	 * @return void
	 */
	function tara_acc_rest_routes() {
		if ( ! function_exists( 'register_rest_route' ) ) {
			return;
		}

		$namespaces = array( 'tara/v1' );
		if ( ! class_exists( 'AhangBan_API' ) ) {
			/* اگر افزونهٔ ربات نصب نیست، همان مسیر را در فضای‌نامِ ربات هم
			   می‌گذاریم تا ربات بدونِ تغییرِ تنظیمات پیدایش کند. */
			$namespaces[] = 'ahangban/v1';
		}
		$namespaces = (array) apply_filters( 'tara_acc_rest_namespaces', $namespaces );

		$auth = 'tara_acc_rest_auth';

		foreach ( $namespaces as $ns ) {
			$ns = (string) $ns;

			register_rest_route(
				$ns,
				'/health',
				array(
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'callback'            => function () {
						return array(
							'ok'      => true,
							'site'    => get_bloginfo( 'name' ),
							'version' => defined( 'TARANEHYAR_VERSION' ) ? TARANEHYAR_VERSION : '',
							'account' => tara_acc_on(),
							'linked'  => '' !== tara_acc_rest_token(),
						);
					},
				)
			);

			register_rest_route(
				$ns,
				'/link',
				array(
					'methods'             => 'POST',
					'permission_callback' => $auth,
					'callback'            => function ( $request ) {
						$res = tara_acc_link_consume(
							(string) $request->get_param( 'code' ),
							(int) $request->get_param( 'telegram_id' ),
							(string) $request->get_param( 'username' ),
							(string) $request->get_param( 'first_name' )
						);
						if ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) {
							$res->add_data( array( 'status' => 400 ) );
							return $res;
						}
						return array( 'ok' => true, 'user' => tara_acc_rest_summary( (int) $res ) );
					},
					'args'                => array(
						'code'        => array( 'required' => true ),
						'telegram_id' => array( 'required' => true ),
					),
				)
			);

			register_rest_route(
				$ns,
				'/unlink',
				array(
					'methods'             => 'POST',
					'permission_callback' => $auth,
					'callback'            => function ( $request ) {
						$user_id = tara_acc_rest_user( $request );
						if ( function_exists( 'is_wp_error' ) && is_wp_error( $user_id ) ) {
							return $user_id;
						}
						delete_user_meta( (int) $user_id, 'tara_tg_id' );
						delete_user_meta( (int) $user_id, 'tara_tg_user' );
						return array( 'ok' => true );
					},
				)
			);

			register_rest_route(
				$ns,
				'/me',
				array(
					'methods'             => 'GET',
					'permission_callback' => $auth,
					'callback'            => function ( $request ) {
						$user_id = tara_acc_rest_user( $request );
						if ( function_exists( 'is_wp_error' ) && is_wp_error( $user_id ) ) {
							return $user_id;
						}
						return tara_acc_rest_summary( (int) $user_id );
					},
				)
			);

			register_rest_route(
				$ns,
				'/follows',
				array(
					'methods'             => 'GET',
					'permission_callback' => $auth,
					'callback'            => function ( $request ) {
						$user_id = tara_acc_rest_user( $request );
						if ( function_exists( 'is_wp_error' ) && is_wp_error( $user_id ) ) {
							return $user_id;
						}
						$summary = tara_acc_rest_summary( (int) $user_id );
						return array( 'follows' => $summary['follows'] );
					},
				)
			);

			register_rest_route(
				$ns,
				'/follow',
				array(
					'methods'             => 'POST',
					'permission_callback' => $auth,
					'callback'            => function ( $request ) {
						$user_id = tara_acc_rest_user( $request );
						if ( function_exists( 'is_wp_error' ) && is_wp_error( $user_id ) ) {
							return $user_id;
						}
						$term = (int) $request->get_param( 'term_id' );
						if ( $term <= 0 ) {
							$slug = sanitize_title( (string) $request->get_param( 'slug' ) );
							$tax  = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
							$obj  = '' !== $slug && function_exists( 'get_term_by' ) ? get_term_by( 'slug', $slug, $tax ) : null;
							$term = $obj ? (int) $obj->term_id : 0;
						}
						$action = sanitize_key( (string) $request->get_param( 'action' ) );
						$on     = 'remove' === $action ? false : ( 'toggle' === $action ? null : true );
						$res    = tara_acc_follow( $term, (int) $user_id, $on );
						if ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) {
							$res->add_data( array( 'status' => 400 ) );
							return $res;
						}
						$summary = tara_acc_rest_summary( (int) $user_id );
						$res['follows'] = $summary['follows'];
						return $res;
					},
				)
			);

			register_rest_route(
				$ns,
				'/feed',
				array(
					'methods'             => 'GET',
					'permission_callback' => $auth,
					'callback'            => function ( $request ) {
						$user_id = tara_acc_rest_user( $request );
						if ( function_exists( 'is_wp_error' ) && is_wp_error( $user_id ) ) {
							return $user_id;
						}
						$items = tara_acc_feed(
							(int) $user_id,
							array(
								'since' => (int) $request->get_param( 'since' ),
								'limit' => (int) ( $request->get_param( 'limit' ) ? $request->get_param( 'limit' ) : 10 ),
							)
						);
						return array( 'items' => $items, 'count' => count( $items ) );
					},
				)
			);

			register_rest_route(
				$ns,
				'/like',
				array(
					'methods'             => 'POST',
					'permission_callback' => $auth,
					'callback'            => function ( $request ) {
						$user_id = tara_acc_rest_user( $request );
						if ( function_exists( 'is_wp_error' ) && is_wp_error( $user_id ) ) {
							return $user_id;
						}
						$action = sanitize_key( (string) $request->get_param( 'action' ) );
						$on     = 'remove' === $action ? false : ( 'toggle' === $action ? null : true );
						$res    = tara_acc_like( (int) $request->get_param( 'post_id' ), (int) $user_id, $on );
						if ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) {
							$res->add_data( array( 'status' => 400 ) );
							return $res;
						}
						return $res;
					},
				)
			);

			register_rest_route(
				$ns,
				'/playlists',
				array(
					'methods'             => 'GET',
					'permission_callback' => $auth,
					'callback'            => function ( $request ) {
						$user_id = tara_acc_rest_user( $request );
						if ( function_exists( 'is_wp_error' ) && is_wp_error( $user_id ) ) {
							return $user_id;
						}
						return array( 'playlists' => array_map( 'tara_acc_rest_playlist', tara_acc_playlists( (int) $user_id ) ) );
					},
				)
			);

			register_rest_route(
				$ns,
				'/playlist',
				array(
					'methods'             => 'POST',
					'permission_callback' => $auth,
					'callback'            => function ( $request ) {
						$user_id = tara_acc_rest_user( $request );
						if ( function_exists( 'is_wp_error' ) && is_wp_error( $user_id ) ) {
							return $user_id;
						}
						$op  = sanitize_key( (string) $request->get_param( 'action' ) );
						$key = sanitize_text_field( (string) $request->get_param( 'key' ) );

						if ( 'create' === $op ) {
							$res = tara_acc_playlist_create( (string) $request->get_param( 'name' ), (int) $user_id );
						} elseif ( 'add' === $op || 'remove' === $op ) {
							$res = tara_acc_playlist_toggle( $key, (int) $request->get_param( 'post_id' ), (int) $user_id, 'add' === $op );
						} elseif ( 'delete' === $op ) {
							$res = tara_acc_playlist_delete( $key, (int) $user_id ) ? array( 'ok' => true ) : new WP_Error( 'tara_pl', 'پلی‌لیست پیدا نشد.' );
						} elseif ( 'rename' === $op ) {
							$res = tara_acc_playlist_update( $key, array( 'name' => (string) $request->get_param( 'name' ) ), (int) $user_id ) ? array( 'ok' => true ) : new WP_Error( 'tara_pl', 'پلی‌لیست پیدا نشد.' );
						} else {
							$res = new WP_Error( 'tara_pl_op', 'درخواستِ نامعتبر.', array( 'status' => 400 ) );
						}

						if ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) {
							if ( ! $res->get_error_data() ) {
								$res->add_data( array( 'status' => 400 ) );
							}
							return $res;
						}
						$res['playlists'] = array_map( 'tara_acc_rest_playlist', tara_acc_playlists( (int) $user_id ) );
						return $res;
					},
				)
			);

			register_rest_route(
				$ns,
				'/notifications',
				array(
					'methods'             => 'GET',
					'permission_callback' => $auth,
					'callback'            => function ( $request ) {
						$user_id = tara_acc_rest_user( $request );
						if ( function_exists( 'is_wp_error' ) && is_wp_error( $user_id ) ) {
							return $user_id;
						}
						$feed = tara_acc_feed( (int) $user_id, array( 'limit' => 10 ) );
						return array(
							'unread'  => tara_acc_unread( (int) $user_id ),
							'messages' => tara_acc_notifs( (int) $user_id ),
							'feed'    => $feed,
						);
					},
				)
			);

			register_rest_route(
				$ns,
				'/read',
				array(
					'methods'             => 'POST',
					'permission_callback' => $auth,
					'callback'            => function ( $request ) {
						$user_id = tara_acc_rest_user( $request );
						if ( function_exists( 'is_wp_error' ) && is_wp_error( $user_id ) ) {
							return $user_id;
						}
						tara_acc_notifs_read( (int) $user_id );
						return array( 'ok' => true, 'unread' => 0 );
					},
				)
			);
		}
	}
}

if ( ! function_exists( 'tara_acc_rest_playlist' ) ) {
	/**
	 * شکلِ سبکِ پلی‌لیست برای ربات (بدونِ شناسه‌های اضافی).
	 *
	 * @param array $pl پلی‌لیست.
	 * @return array
	 */
	function tara_acc_rest_playlist( $pl ) {
		$tracks = array();
		foreach ( array_slice( (array) $pl['t'], 0, 20 ) as $post_id ) {
			$tracks[] = array(
				'id'    => (int) $post_id,
				'title' => function_exists( 'tara_track_title' ) ? (string) tara_track_title( (int) $post_id ) : '',
				'url'   => (string) get_permalink( (int) $post_id ),
			);
		}
		return array(
			'key'    => (string) $pl['k'],
			'name'   => (string) $pl['n'],
			'count'  => count( (array) $pl['t'] ),
			'public' => ! empty( $pl['p'] ),
			'tracks' => $tracks,
		);
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'rest_api_init', 'tara_acc_rest_routes' );
}

/* -------------------------------------------------------------------------
 * ۱۰) پیشخوان — ستونِ موبایل در فهرستِ کاربران
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_users_column' ) ) {
	/** افزودنِ ستونِ «موبایل». */
	function tara_acc_users_column( $columns ) {
		$columns = (array) $columns;
		$out     = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'username' === $key ) {
				$out['tara_phone'] = 'موبایل';
			}
		}
		if ( ! isset( $out['tara_phone'] ) ) {
			$out['tara_phone'] = 'موبایل';
		}
		return $out;
	}
}

if ( ! function_exists( 'tara_acc_users_column_value' ) ) {
	/** مقدارِ ستونِ موبایل. */
	function tara_acc_users_column_value( $value, $column, $user_id ) {
		if ( 'tara_phone' !== $column ) {
			return $value;
		}
		$phone = tara_acc_user_phone( (int) $user_id );
		if ( '' === $phone ) {
			return '—';
		}
		$tg = (int) get_user_meta( (int) $user_id, 'tara_tg_id', true );
		return esc_html( tara_acc_phone_pretty( $phone ) ) . ( $tg > 0 ? ' <span title="تلگرام وصل است" aria-label="تلگرام وصل است">✔</span>' : '' );
	}
}

if ( function_exists( 'add_filter' ) ) {
	add_filter( 'manage_users_columns', 'tara_acc_users_column' );
	add_filter( 'manage_users_custom_column', 'tara_acc_users_column_value', 10, 3 );
}

/* -------------------------------------------------------------------------
 * ۱۱) ساختِ خودکارِ برگه‌های «حساب من» و «ورود» هنگام تغییرِ قالب
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_ensure_pages' ) ) {
	/**
	 * اگر صفحهٔ قالب‌دار نباشد، یکی می‌سازد؛ بعد از تغییرِ قالب یک‌بار صدا
	 * زده می‌شود تا کاربر مجبور نباشد برگه‌ها را دستی بسازد.
	 *
	 * @return void
	 */
	function tara_acc_ensure_pages() {
		if ( ! tara_acc_on() || ! function_exists( 'wp_insert_post' ) ) {
			return;
		}

		$pages = array(
			'page-account.php' => array( 'account', 'حساب من' ),
			'page-login.php'   => array( 'enter', 'ورود و ثبت‌نام' ),
		);

		foreach ( $pages as $template => $data ) {
			$existing = tara_acc_page_url( $template );
			if ( '' !== $existing ) {
				continue;
			}
			if ( function_exists( 'get_page_by_path' ) ) {
				$found = get_page_by_path( $data[0] );
				if ( $found ) {
					continue;
				}
			}
			$page_id = wp_insert_post(
				array(
					'post_title'   => $data[1],
					'post_name'    => $data[0],
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_content' => '',
				)
			);
			if ( $page_id && ! ( function_exists( 'is_wp_error' ) && is_wp_error( $page_id ) ) ) {
				update_post_meta( (int) $page_id, '_wp_page_template', $template );
			}
			if ( function_exists( 'delete_transient' ) ) {
				delete_transient( 'tara_page_url_' . md5( $template ) );
			}
		}
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'after_switch_theme', 'tara_acc_ensure_pages' );
}

/* -------------------------------------------------------------------------
 * ۱۲) هشدارِ پیشخوان وقتی پیامک تنظیم نیست
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_admin_notice' ) ) {
	/** اگر حساب روشن است ولی پیامک تنظیم نیست، مدیر بداند. */
	function tara_acc_admin_notice() {
		if ( ! tara_acc_on() || ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$provider = (string) tara_acc_opt( 'sms', 'dev' );
		$missing  = false;
		if ( 'melipayamak' === $provider ) {
			$missing = '' === trim( (string) tara_acc_opt( 'sms_user' ) ) || '' === trim( (string) tara_acc_opt( 'sms_pass' ) ) || '' === trim( (string) tara_acc_opt( 'sms_from' ) );
		} elseif ( 'webhook' === $provider ) {
			$missing = '' === trim( (string) tara_acc_opt( 'sms_hook' ) );
		}
		if ( ! $missing ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>ورودِ پیامکی:</strong> پنلِ پیامک تنظیم نشده است؛ کاربران کدِ ورود را نمی‌گیرند. به «ترانه یار ← تنظیمات قالب ← حساب کاربری» بروید.</p></div>';
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'admin_notices', 'tara_acc_admin_notice' );
}
