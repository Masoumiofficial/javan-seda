<?php
/**
 * inc/tara-release-date.php — «تاریخ انتشار واقعی آهنگ»: فیلد ویرایشگر + ذخیرهٔ امن
 * ============================================================================
 * پیش از ۳.۱۷.۰، `inc/tara-p0.php` متای تاریخ انتشار را **می‌خواند** ولی هیچ
 * راهی برای ثبت آن در پیشخوان نبود. نتیجه: برچسبِ صادقانه همیشه «انتشار در سایت»
 * می‌شد، مگر کسی با ابزار بیرونی متا را دستی می‌نوشت.
 *
 * این فایل همان شکاف را می‌بندد و هیچ داده‌ی دومِ ناسازگاری نمی‌سازد:
 *   • همان کلیدِ `_tara_release_date` که از ۳.۱۶.۰ اولویتِ اولِ خواندن است.
 *   • قالبِ ذخیره **همیشه** `YYYY-MM-DD` میلادی (ماشین‌خوان؛ مثل `2024-08-02`).
 *   • ورودیِ کاربر می‌تواند شمسی باشد (`1403/05/12`) و همین‌جا به میلادی
 *     تبدیل می‌شود؛ پس نمایش/داده‌ساختاریافته (ISO 8601) همیشه درست است.
 *   • خالی‌کردن فیلد ⇒ `delete_post_meta` (نه نوشتن رشتهٔ خالی).
 *   • ورودیِ نامعتبر ⇒ متای قبلی **دست‌نخورده** می‌ماند و پیام خطا می‌آید.
 *   • `post_date`، `post_modified` و تاریخ انتشار وردپرس هرگز لمس نمی‌شوند.
 *
 * چرا تاریخ شمسی «بی‌کتابخانه»؟ این‌جا فقط یک تبدیلِ حسابی لازم است؛ آوردن
 * یک کتابخانهٔ تقویم برای ۴۰ خط کد، وزنِ بی‌دلیل است. الگوریتم همان تبدیلِ
 * استاندارد ژولینی است و با آزمونِ رفت‌وبرگشت سنجیده می‌شود.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_p1_digits_to_latin' ) ) {
	/**
	 * رقم‌های فارسی/عربی → لاتین.
	 *
	 * @param string $text ورودی.
	 * @return string
	 */
	function tara_p1_digits_to_latin( $text ) {
		return str_replace(
			array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ),
			array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
			(string) $text
		);
	}
}

if ( ! function_exists( 'tara_p1_jalali_to_gregorian' ) ) {
	/**
	 * شمسی → میلادی (الگوریتمِ استاندارد تبدیلِ روزِ ژولینی).
	 *
	 * @param int $jy سال شمسی.
	 * @param int $jm ماه شمسی.
	 * @param int $jd روز شمسی.
	 * @return array{0:int,1:int,2:int}|null
	 */
	function tara_p1_jalali_to_gregorian( $jy, $jm, $jd ) {
		$jy = (int) $jy;
		$jm = (int) $jm;
		$jd = (int) $jd;
		if ( $jy < 1200 || $jy > 1600 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > 31 ) {
			return null;
		}

		$jy  += 1595;
		$days = -355668 + ( 365 * $jy ) + ( (int) ( $jy / 33 ) * 8 ) + (int) ( ( ( $jy % 33 ) + 3 ) / 4 ) + $jd;
		$days += ( $jm < 7 ) ? ( $jm - 1 ) * 31 : ( ( $jm - 7 ) * 30 ) + 186;

		$gy   = 400 * (int) ( $days / 146097 );
		$days = $days % 146097;
		if ( $days > 36524 ) {
			$gy  += 100 * (int) ( --$days / 36524 );
			$days = $days % 36524;
			if ( $days >= 365 ) {
				$days++;
			}
		}
		$gy  += 4 * (int) ( $days / 1461 );
		$days = $days % 1461;
		if ( $days > 365 ) {
			$gy  += (int) ( ( $days - 1 ) / 365 );
			$days = ( $days - 1 ) % 365;
		}
		$gd  = $days + 1;
		$leap = ( ( $gy % 4 === 0 && $gy % 100 !== 0 ) || ( $gy % 400 === 0 ) );
		$sal  = array( 0, 31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
		$gm   = 1;
		while ( $gm <= 12 && $gd > $sal[ $gm ] ) {
			$gd -= $sal[ $gm ];
			$gm++;
		}
		if ( $gm > 12 || $gd < 1 ) {
			return null;
		}
		return array( $gy, $gm, $gd );
	}
}

if ( ! function_exists( 'tara_p1_gregorian_to_jalali' ) ) {
	/**
	 * میلادی → شمسی (برای پرکردن فیلد ویرایشگر با تاریخِ خوانا).
	 *
	 * @param int $gy سال میلادی.
	 * @param int $gm ماه میلادی.
	 * @param int $gd روز میلادی.
	 * @return array{0:int,1:int,2:int}|null
	 */
	function tara_p1_gregorian_to_jalali( $gy, $gm, $gd ) {
		$gy = (int) $gy;
		$gm = (int) $gm;
		$gd = (int) $gd;
		if ( $gy < 1800 || $gy > 2200 || $gm < 1 || $gm > 12 || $gd < 1 || $gd > 31 || ! checkdate( $gm, $gd, $gy ) ) {
			return null;
		}
		$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
		$days  = 355666 + ( 365 * $gy ) + (int) ( ( $gy2 + 3 ) / 4 ) - (int) ( ( $gy2 + 99 ) / 100 ) + (int) ( ( $gy2 + 399 ) / 400 ) + $gd + $g_d_m[ $gm - 1 ];
		$jy    = -1595 + ( 33 * (int) ( $days / 12053 ) );
		$days  = $days % 12053;
		$jy   += 4 * (int) ( $days / 1461 );
		$days  = $days % 1461;
		if ( $days > 365 ) {
			$jy  += (int) ( ( $days - 1 ) / 365 );
			$days = ( $days - 1 ) % 365;
		}
		if ( $days < 186 ) {
			$jm = 1 + (int) ( $days / 31 );
			$jd = 1 + ( $days % 31 );
		} else {
			$jm = 7 + (int) ( ( $days - 186 ) / 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}
		return array( $jy, $jm, $jd );
	}
}

if ( ! function_exists( 'tara_p1_jalali_valid' ) ) {
	/**
	 * آیا تاریخِ شمسی واقعاً وجود دارد؟ (ماه‌های ۱–۶ = ۳۱ روز، ۷–۱۱ = ۳۰ روز،
	 * اسفند ۲۹ روز و در سال کبیسه ۳۰ روز). راهِ ساده و مطمئن: تبدیل به میلادی
	 * و بازگشت به شمسی و مقایسهٔ مقدارها؛ اگر برابر بود، تاریخ معتبر است.
	 *
	 * @param int $jy سال.
	 * @param int $jm ماه.
	 * @param int $jd روز.
	 * @return bool
	 */
	function tara_p1_jalali_valid( $jy, $jm, $jd ) {
		$g = tara_p1_jalali_to_gregorian( $jy, $jm, $jd );
		if ( ! $g ) {
			return false;
		}
		$back = tara_p1_gregorian_to_jalali( $g[0], $g[1], $g[2] );
		return $back && (int) $back[0] === (int) $jy && (int) $back[1] === (int) $jm && (int) $back[2] === (int) $jd;
	}
}

if ( ! function_exists( 'tara_p1_date_parse' ) ) {
	/**
	 * تفسیر ورودیِ کاربر به `Y-m-d` میلادی.
	 *
	 * قالب‌های پذیرفته‌شده:
	 *   • `1403-05-12` / `1403/5/12` / `۱۴۰۳/۰۵/۱۲`  → شمسی
	 *   • `2024-08-02` / `2024/8/2`                  → میلادی
	 *   • `14030512` / `20240802` (هشت‌رقمی)          → شمسی/میلادی
	 *   • رشتهٔ خالی                                   → `''` (یعنی حذف)
	 *
	 * @param string $raw ورودی خام.
	 * @return string|false `Y-m-d`، `''` برای حذف، یا `false` برای نامعتبر.
	 */
	function tara_p1_date_parse( $raw ) {
		$raw = trim( tara_p1_digits_to_latin( (string) $raw ) );
		$raw = str_replace( array( '–', '—', '−', '.', '٫', chr( 92 ) ), '-', $raw );
		$raw = (string) preg_replace( '/\s+/u', '', $raw );

		if ( '' === $raw ) {
			return '';
		}

		if ( preg_match( '/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})$/u', $raw, $m ) || preg_match( '/^(\d{4})(\d{2})(\d{2})$/u', $raw, $m ) ) {
			$y  = (int) $m[1];
			$mo = (int) $m[2];
			$d  = (int) $m[3];
		} else {
			return false;
		}

		/* شمسی */
		if ( $y >= 1200 && $y <= 1599 ) {
			if ( ! tara_p1_jalali_valid( $y, $mo, $d ) ) {
				return false;
			}
			$g = tara_p1_jalali_to_gregorian( $y, $mo, $d );
			if ( ! $g || ! checkdate( $g[1], $g[2], $g[0] ) ) {
				return false;
			}
			return sprintf( '%04d-%02d-%02d', $g[0], $g[1], $g[2] );
		}

		/* میلادی */
		if ( $y >= 1900 && $y <= 2100 && checkdate( $mo, $d, $y ) ) {
			return sprintf( '%04d-%02d-%02d', $y, $mo, $d );
		}

		return false;
	}
}

if ( ! function_exists( 'tara_p1_date_meta_key' ) ) {
	/**
	 * کلیدِ متای تاریخ انتشار.
	 *
	 * همان کلیدی که `tara_release_meta_keys()` در ۳.۱۶.۰ اول می‌خواند؛ پس
	 * داده‌ی فیلد بی‌درنگ در صفحهٔ آهنگ، کارت‌ها و بخش «تازه‌ترین انتشارها»
	 * دیده می‌شود. شروع با `_` یعنی در «فیلدهای سفارشی» پیشخوان دیده نمی‌شود
	 * و فقط از راهِ همین فیلدِ اعتبارسنجی‌شده نوشته می‌شود.
	 *
	 * @return string
	 */
	function tara_p1_date_meta_key() {
		return (string) apply_filters( 'tara_p1_date_meta_key', '_tara_release_date' );
	}
}

if ( ! function_exists( 'tara_p1_date_human' ) ) {
	/**
	 * نمایش خوانا (شمسی) از تاریخِ ذخیره‌شده.
	 *
	 * @param string $iso `Y-m-d` میلادی.
	 * @return string
	 */
	function tara_p1_date_human( $iso ) {
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', (string) $iso, $m ) ) {
			return '';
		}
		$j = tara_p1_gregorian_to_jalali( (int) $m[1], (int) $m[2], (int) $m[3] );
		if ( ! $j ) {
			return '';
		}
		return sprintf( '%04d/%02d/%02d', $j[0], $j[1], $j[2] );
	}
}

if ( ! function_exists( 'tara_p1_date_field_enabled' ) ) {
	/**
	 * فیلد تاریخ برای کدام پست‌تایپ‌ها نمایش داده شود.
	 *
	 * پیش‌فرض `post` — همان جایی که تاریخ خوانده می‌شود. گسترش با فیلتر
	 * `tara_p1_date_post_types` ممکن است، ولی بدون دلیل به نوع‌های دیگر
	 * اضافه نمی‌شود تا فیلدِ بی‌مصرف ساخته نشود.
	 *
	 * @param string $post_type نوع نوشته.
	 * @return bool
	 */
	function tara_p1_date_field_enabled( $post_type ) {
		return in_array( (string) $post_type, (array) apply_filters( 'tara_p1_date_post_types', array( 'post' ) ), true );
	}
}

if ( ! function_exists( 'tara_p1_date_metabox' ) ) {
	/** ثبت جعبهٔ «تاریخ انتشار واقعی آهنگ». */
	function tara_p1_date_metabox() {
		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$post_type = ( $screen && ! empty( $screen->post_type ) ) ? $screen->post_type : 'post';
		if ( ! tara_p1_date_field_enabled( $post_type ) ) {
			return;
		}
		add_meta_box( 'tara-release-date', 'تاریخ انتشار واقعی آهنگ', 'tara_p1_date_metabox_html', $post_type, 'side', 'default' );
	}
	add_action( 'add_meta_boxes', 'tara_p1_date_metabox' );
}

if ( ! function_exists( 'tara_p1_date_metabox_html' ) ) {
	/**
	 * مارک‌آپ فیلد.
	 *
	 * مقدارِ فعلی با `tara_release_meta_date()` خوانده می‌شود (لایهٔ سازگاریِ
	 * ۳.۱۶.۰: `_tara_release_date`، `release_date`، `tara_release_date` با
	 * قالب‌های Y-m-d/Y/m/d و آرایهٔ ACF) — بدون هیچ نوشتنی. یعنی اگر مدیر
	 * قبلاً تاریخ را با کلید/قالبِ دیگری ثبت کرده، همین‌جا می‌بیندش.
	 *
	 * @param WP_Post $post نوشته.
	 */
	function tara_p1_date_metabox_html( $post ) {
		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}
		if ( function_exists( 'current_user_can' ) && ! current_user_can( 'edit_post', $post->ID ) ) {
			echo '<p>' . esc_html( 'اجازهٔ ویرایش این نوشته را ندارید.' ) . '</p>';
			return;
		}
		$stored = function_exists( 'tara_release_meta_date' ) ? (string) tara_release_meta_date( $post->ID ) : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $stored ) ) {
			$stored = '';
		}
		$human = '' !== $stored ? tara_p1_date_human( $stored ) : '';

		wp_nonce_field( 'tara_release_date_save', 'tara_release_date_nonce' );
		?>
		<div class="tara-date-field" dir="rtl">
			<p>
				<label for="tara-release-date-input"><strong>تاریخ انتشار واقعی آهنگ</strong></label>
			</p>
			<p>
				<input type="text" id="tara-release-date-input" name="tara_release_date"
					class="widefat code" inputmode="numeric" autocomplete="off"
					placeholder="۱۴۰۳/۰۵/۱۲"
					value="<?php echo esc_attr( $human ); ?>"
					aria-describedby="tara-release-date-help">
			</p>
			<p id="tara-release-date-help" class="description">
				تاریخ <strong>شمسی</strong> را وارد کنید (مثل ۱۴۰۳/۰۵/۱۲)؛ میلادی هم پذیرفته می‌شود.
				برای پاک‌کردن، فیلد را خالی کنید. این مقدار تاریخ انتشار وردپرس را تغییر نمی‌دهد.
			</p>
			<?php if ( '' !== $stored ) : ?>
				<p class="description">ذخیره‌شده (میلادی، برای موتور جست‌وجو): <code><?php echo esc_html( $stored ); ?></code></p>
			<?php else : ?>
				<p class="description">تا وقتی خالی است، سایت «انتشار در سایت» را نشان می‌دهد — نه یک تاریخ ساختگی.</p>
			<?php endif; ?>
			<input type="hidden" name="tara_release_date_present" value="1">
		</div>
		<?php
	}
}

if ( ! function_exists( 'tara_p1_date_save' ) ) {
	/**
	 * ذخیرهٔ امن و محافظه‌کارانهٔ فیلد تاریخ.
	 *
	 * ترتیب تصمیم‌ها (هر موردی که برقرار شود، همان‌جا متوقف می‌شود):
	 *   ۱) autosave/revision ⇒ هیچ.
	 *   ۲) فیلد در درخواست نبود ⇒ هیچ (نه «پاک کن»). این برای افزونه‌هایی مهم
	 *      است که فرم را بازنویسی می‌کنند یا برای ذخیرهٔ دسته‌ای (bulk edit).
	 *   ۳) nonce یا مجوز نامعتبر ⇒ هیچ.
	 *   ۴) خالی ⇒ حذف متا.
	 *   ۵) نامعتبر ⇒ متا دست‌نخورده + اعلان خطا.
	 *   ۶) معتبر ⇒ نوشتن فقط اگر با مقدار فعلی فرق داشته باشد.
	 *
	 * @param int     $post_id شناسه.
	 * @param WP_Post $post    نوشته.
	 */
	function tara_p1_date_save( $post_id, $post = null ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! $post ) {
			$post = function_exists( 'get_post' ) ? get_post( $post_id ) : null;
		}
		if ( ! $post || ! tara_p1_date_field_enabled( $post->post_type ) ) {
			return;
		}
		if ( ! isset( $_POST['tara_release_date_present'], $_POST['tara_release_date_nonce'], $_POST['tara_release_date'] ) ) {
			return;
		}
		if ( ! is_string( $_POST['tara_release_date_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tara_release_date_nonce'] ) ), 'tara_release_date_save' ) ) {
			return;
		}
		if ( function_exists( 'current_user_can' ) && ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = is_scalar( $_POST['tara_release_date'] ) ? (string) wp_unslash( $_POST['tara_release_date'] ) : '';
		$key = tara_p1_date_meta_key();
		$old = (string) get_post_meta( $post_id, $key, true );

		if ( '' === trim( $raw ) ) {
			if ( '' !== $old ) {
				delete_post_meta( $post_id, $key );
			}
			return;
		}

		$iso = tara_p1_date_parse( $raw );
		if ( false === $iso ) {
			$GLOBALS['tara_p1_date_error'] = true;
			return;
		}

		if ( $iso !== $old ) {
			update_post_meta( $post_id, $key, $iso );
		}
	}
	add_action( 'save_post', 'tara_p1_date_save', 10, 2 );
}

if ( ! function_exists( 'tara_p1_date_admin_notice' ) ) {
	/** اعلانِ «تاریخ نامعتبر بود؛ مقدار قبلی حفظ شد». */
	function tara_p1_date_admin_notice() {
		if ( empty( $GLOBALS['tara_p1_date_error'] ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>'
			. esc_html( 'تاریخ واردشده معتبر نبود؛ مقدار قبلی حفظ شد. قالب‌های پذیرفته‌شده: ۱۴۰۳/۰۵/۱۲ یا 2024-08-02.' )
			. '</p></div>';
	}
	add_action( 'admin_notices', 'tara_p1_date_admin_notice' );
}
