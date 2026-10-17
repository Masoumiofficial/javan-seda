<?php
/**
 * v3.2.1 — «شناسنامۀ آهنگ» (مانند ahaang.com)
 * ---------------------------------------------------------------------------
 * ردیف‌های: خواننده · آهنگساز · ترانه‌سرا · تنظیم · میکس و مستر · تولید
 *
 * • مقدارها از فیلدهای ACF خوانده می‌شوند؛ اگر افزونۀ ACF نباشد، فایل
 *   inc/acf-compat.php همان کلیدها را از post_meta می‌خواند، پس هر دو حالت کار می‌کند.
 * • برای هر ردیف یک «مربوط‌شدنِ نام» به تاکسونومی singer انجام می‌شود: اگر نام با
 *   یک ترمِ خواننده یکی باشد، لینک آرشیو همان خواننده ساخته می‌شود؛ اگر نباشد
 *   فقط متن چاپ می‌شود (هیچ لینک ۴۰۴‌ساز، هیچ تاکسونومی تازه، هیچ هوک سراسری).
 * • ورودی دادن: یک metabox ساده در ویرایش نوشته. اگر ACF را دوست دارید، کافی است
 *   فیلدهایی با همین کلیدها بسازید و خطِ زیر را در wp-config.php بگذارید:
 *        define( 'JAVANSEDA_CREDITS_METABOX', false );
 *
 * کلیدهای متا: composer · lyricist · arranger · mixer · producer
 *
 * خاموش‌کردن (هرکدام یک خط، همه برگشت‌پذیر):
 *   define( 'JAVANSEDA_CREDITS', false );        → کل لایه (باکس + metabox)
 *   تنظیمات قالب → «صفحه دانلود آهنگ» → «شناسنامۀ آهنگ» → خاموش
 *   حذف همین فایل                                 → بازگشت کامل، بدون خطا
 *
 * این فایل هیچ فیلتری روی the_content یا template_include اضافه نمی‌کند و
 * تنها از طریق دو فراخوانِ function_exists در single.php صدا زده می‌شود.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'JAVANSEDA_CREDITS' ) ) {
	define( 'JAVANSEDA_CREDITS', true );
}
if ( ! defined( 'JAVANSEDA_CREDITS_METABOX' ) ) {
	define( 'JAVANSEDA_CREDITS_METABOX', true );
}
if ( ! defined( 'JAVANSEDA_CREDITS_TAX' ) ) {
	define( 'JAVANSEDA_CREDITS_TAX', 'singer' ); /* تاکسونومی خواننده‌ها — فقط خوانده می‌شود، ثبت/تغییر نمی‌کند */
}

/* ==========================================================================
   ۱) جدول ردیف‌ها — تنها جایی که برای افزودن ردیف تازه لازم است عوض شود
   ========================================================================== */
if ( ! function_exists( 'javan_seda_credits_fields' ) ) {
	/**
	 * @return array کلیدِ متا => برچسب + آیکن (آیکن‌ها همه در css/icofont.min.css موجودند)
	 */
	function javan_seda_credits_fields() {
		$fields = array(
			'composer' => array( 'label' => 'آهنگساز', 'icon' => 'icofont-music-alt' ),
			'lyricist' => array( 'label' => 'ترانه‌سرا', 'icon' => 'icofont-edit' ),
			'arranger' => array( 'label' => 'تنظیم', 'icon' => 'icofont-headphone' ),
			'mixer'    => array( 'label' => 'میکس و مستر', 'icon' => 'icofont-record' ),
			'producer' => array( 'label' => 'تولید', 'icon' => 'icofont-camera' ),
		);
		return apply_filters( 'javanseda_credits_fields', $fields );
	}
}

/* ==========================================================================
   ۲) خواندن گزینه‌ها (Option Tree، با fallback — اگر Option Tree نبود نمی‌ترکد)
   ========================================================================== */
if ( ! function_exists( 'javan_seda_credits_option' ) ) {
	function javan_seda_credits_option( $key, $default = '' ) {
		$value = $default;
		if ( function_exists( 'ot_get_option' ) ) {
			$got = ot_get_option( $key, $default );
			if ( is_scalar( $got ) ) {
				$value = (string) $got;
			}
		}
		return $value;
	}
}

if ( ! function_exists( 'javan_seda_credits_on' ) ) {
	function javan_seda_credits_on() {
		if ( ! JAVANSEDA_CREDITS ) {
			return false;
		}
		if ( 'off' === javan_seda_credits_option( 'credits_box', 'on' ) ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'javan_seda_credits_position' ) ) {
	/** 'after_download' (پیش‌فرض) یا 'before_player' */
	function javan_seda_credits_position() {
		$pos = 'after_download';
		if ( 'on' === javan_seda_credits_option( 'credits_before_player', 'off' ) ) {
			$pos = 'before_player';
		}
		return apply_filters( 'javanseda_credits_position', $pos );
	}
}

if ( ! function_exists( 'javan_seda_credits_title' ) ) {
	function javan_seda_credits_title() {
		$title = trim( (string) javan_seda_credits_option( 'credits_title', '' ) );
		if ( '' === $title ) {
			$title = 'شناسنامۀ آهنگ';
		}
		return $title;
	}
}

/* ==========================================================================
   ۳) مقدار یک ردیف + نرمال‌سازی نام + لینک آرشیو خواننده
   ========================================================================== */
if ( ! function_exists( 'javan_seda_credit_value' ) ) {
	/** اول ACF (get_field)، بعد post_meta خام — تا هر دو مسیر داده کار کند */
	function javan_seda_credit_value( $key, $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		$val = '';
		if ( function_exists( 'get_field' ) ) {
			$got = get_field( $key, $post_id );
			if ( is_scalar( $got ) ) {
				$val = trim( (string) $got );
			} elseif ( is_array( $got ) ) {
				$flat = array();
				foreach ( $got as $one ) {
					if ( is_scalar( $one ) ) {
						$flat[] = trim( (string) $one );
					}
				}
				$val = trim( implode( ' / ', $flat ) );
			}
		}
		if ( '' === $val ) {
			$got = get_post_meta( $post_id, $key, true );
			if ( is_scalar( $got ) ) {
				$val = trim( (string) $got );
			}
		}
		return $val;
	}
}

if ( ! function_exists( 'javan_seda_credit_norm' ) ) {
	/** ي/ي، ك/ك، ة/ه، نیم‌فاصله و فاصله‌های اضافی یکسان می‌شوند */
	function javan_seda_credit_norm( $text ) {
		$text = str_replace( array( 'ي', 'ك', 'ة', "\xE2\x80\x8C", "\xC2\xA0" ), array( 'ی', 'ک', 'ه', ' ', ' ' ), (string) $text );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( (string) $text );
	}
}

if ( ! function_exists( 'javan_seda_credit_terms' ) ) {
	/** یک‌بار در هر درخواست، فهرست خواننده‌ها گرفته و نرمال می‌شود (سایت ≈ ۳۲ ترم) */
	function javan_seda_credit_terms() {
		static $map = null;
		if ( null !== $map ) {
			return $map;
		}
		$map = array();
		if ( ! taxonomy_exists( JAVANSEDA_CREDITS_TAX ) ) {
			return $map;
		}
		$terms = get_terms(
			array(
				'taxonomy'   => JAVANSEDA_CREDITS_TAX,
				'hide_empty' => false,
				'number'     => 500,
				'orderby'    => 'name',
			)
		);
		if ( ! is_array( $terms ) ) {
			return $map;
		}
		foreach ( $terms as $term ) {
			if ( ! isset( $term->name ) ) {
				continue;
			}
			$norm = javan_seda_credit_norm( $term->name );
			if ( '' === $norm || isset( $map[ $norm ] ) ) {
				continue; /* اولین ترمِ هم‌نام کافی است؛ ترم دوبل را ما انتخاب نمی‌کنیم */
			}
			$link = get_term_link( $term, JAVANSEDA_CREDITS_TAX );
			if ( is_wp_error( $link ) || '' === (string) $link ) {
				continue;
			}
			$map[ $norm ] = (string) $link;
		}
		return $map;
	}
}

if ( ! function_exists( 'javan_seda_credit_url' ) ) {
	/** آدرس آرشیو خواننده برای یک نام؛ اگر نبود '' برمی‌گرداند (لینک مرده نمی‌سازیم) */
	function javan_seda_credit_url( $name ) {
		$name = javan_seda_credit_norm( $name );
		if ( '' === $name ) {
			return '';
		}
		$terms = javan_seda_credit_terms();
		$url   = isset( $terms[ $name ] ) ? $terms[ $name ] : '';
		/** همان فیلترِ موجودِ قالب (single.php) تا اگر کسی لینک دستی دارد، همین‌جا هم اعمال شود */
		$url = apply_filters( 'taranehyar_artist_url', $url, $name );
		return is_string( $url ) ? $url : '';
	}
}

if ( ! function_exists( 'javan_seda_credit_names' ) ) {
	/**
	 * یک رشته مثل «آصف آریا / معین حبیبی» → آرایهٔ نام‌ها.
	 * « و » فقط وقتی جداکننده حساب می‌شود که هر دو طرف نامِ هر دو ترم خواننده باشد،
	 * تا اسم‌هایی که خودشان «و» دارند خراب نشوند.
	 */
	function javan_seda_credit_names( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return array();
		}
		$parts = preg_split( '#\s*[/|،,;؛]+\s*#u', $value );
		$out   = array();
		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( '' === $part ) {
				continue;
			}
			if ( false !== strpos( $part, ' و ' ) ) {
				$two = preg_split( '#\s+و\s+#u', $part );
				if ( 2 === count( $two ) && '' !== javan_seda_credit_url( $two[0] ) && '' !== javan_seda_credit_url( $two[1] ) ) {
					$part = '';
					foreach ( $two as $one ) {
						$out[] = trim( $one );
					}
				}
			}
			if ( '' !== $part ) {
				$out[] = $part;
			}
		}
		return array_values( array_unique( $out ) );
	}
}

/* ==========================================================================
   ۴) ردیف خواننده (از فیلد artist یا ترم‌های singerِ خودِ نوشته)
   ========================================================================== */
if ( ! function_exists( 'javan_seda_credit_singer' ) ) {
	function javan_seda_credit_singer( $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		$value = javan_seda_credit_value( 'artist', $post_id );
		if ( '' === $value && taxonomy_exists( JAVANSEDA_CREDITS_TAX ) ) {
			$terms = get_the_terms( $post_id, JAVANSEDA_CREDITS_TAX );
			if ( is_array( $terms ) && $terms ) {
				$names = array();
				foreach ( $terms as $term ) {
					$names[] = $term->name;
				}
				$value = implode( ' / ', $names );
			}
		}
		return $value;
	}
}

/* ==========================================================================
   ۵) باکس «شناسنامۀ آهنگ»
   ========================================================================== */
if ( ! function_exists( 'javan_seda_credits_box' ) ) {
	/**
	 * @param int    $post_id
	 * @param string $position  'before_player' | 'after_download' — اگر با جایگاه انتخابی یکی نباشد، چیزی چاپ نمی‌شود
	 * @param bool   $echo
	 */
	function javan_seda_credits_box( $post_id = 0, $position = '', $echo = true ) {
		$html = '';
		if ( ! javan_seda_credits_on() ) {
			return $html;
		}
		if ( '' !== $position && $position !== javan_seda_credits_position() ) {
			return $html;
		}
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$rows = array();
		$singer = javan_seda_credit_singer( $post_id );
		if ( '' !== $singer ) {
			$rows[] = array( 'label' => 'خواننده', 'icon' => 'icofont-radio-mic', 'value' => $singer );
		}
		foreach ( javan_seda_credits_fields() as $key => $cfg ) {
			$value = javan_seda_credit_value( $key, $post_id );
			if ( '' === $value ) {
				continue; /* ردیف خالی چاپ نمی‌کنیم */
			}
			$rows[] = array( 'label' => $cfg['label'], 'icon' => $cfg['icon'], 'value' => $value );
		}
		if ( ! $rows ) {
			return $html; /* هنوز هیچ شناسنامه‌ای وارد نکرده‌اید ⇒ باکس خالی نداریم */
		}

		foreach ( $rows as $row ) {
			$names = javan_seda_credit_names( $row['value'] );
			if ( ! $names ) {
				$names = array( trim( wp_strip_all_tags( $row['value'] ) ) );
			}
			$cells = array();
			foreach ( $names as $name ) {
				if ( '' === $name ) {
					continue;
				}
				$url = javan_seda_credit_url( $name );
				if ( '' !== $url ) {
					$cells[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
				} else {
					$cells[] = '<span>' . esc_html( $name ) . '</span>';
				}
			}
			if ( ! $cells ) {
				continue;
			}
			$html .= '<div class="ab-credit">'
				. '<dt><i class="' . esc_attr( $row['icon'] ) . '" aria-hidden="true"></i>' . esc_html( $row['label'] ) . '</dt>'
				. '<dd>' . implode( '<span class="ab-credit__sep">،</span>', $cells ) . '</dd>'
				. '</div>';
		}
		if ( '' === $html ) {
			return '';
		}

		$html = '<section class="ab-credits" aria-label="' . esc_attr( javan_seda_credits_title() ) . '">'
			. '<header class="ab-credits__head"><i class="icofont-info-circle" aria-hidden="true"></i><h2>' . esc_html( javan_seda_credits_title() ) . '</h2></header>'
			. '<dl class="ab-credits__rows">' . $html . '</dl>'
			. '<div class="clear"></div>'
			. '</section>';

		if ( $echo ) {
			echo $html; // phpcs:ignore -- خروجی از پیش escape شده
		}
		return $html;
	}
}

/* ==========================================================================
   ۶) metabox — ورودی دادن شناسنامه در ویرایش نوشته
   ========================================================================== */
if ( ! function_exists( 'javan_seda_credits_types' ) ) {
	function javan_seda_credits_types() {
		$types = array( 'post' );
		foreach ( $types as $i => $type ) {
			if ( ! post_type_exists( $type ) ) {
				unset( $types[ $i ] );
			}
		}
		return array_values( (array) apply_filters( 'javanseda_credits_post_types', $types ) );
	}
}

if ( ! function_exists( 'javan_seda_credits_metabox_html' ) ) {
	function javan_seda_credits_metabox_html( $post ) {
		wp_nonce_field( 'javan_seda_credits_save', 'javan_seda_credits_nonce' );
		echo '<p class="ab-cred-note" style="margin:0 0 10px;color:#646970">اگر افزونۀ ACF فیلدی با همین کلیدها دارد، همان را پر کنید و این کادر را با <code>define( \'JAVANSEDA_CREDITS_METABOX\', false )</code> بردارید. برای چند نفر با « / » جدا کنید.</p>';
		echo '<table class="form-table" style="clear:both"><tbody>';
		foreach ( javan_seda_credits_fields() as $key => $cfg ) {
			$value = javan_seda_credit_value( $key, $post->ID );
			echo '<tr><th scope="row" style="width:150px;text-align:right;padding:8px 12px 8px 0"><label for="ab_credit_' . esc_attr( $key ) . '">' . esc_html( $cfg['label'] ) . '</label><br><code style="color:#8a92a6">' . esc_html( $key ) . '</code></th>';
			echo '<td><input type="text" class="widefat" id="ab_credit_' . esc_attr( $key ) . '" name="ab_credit_' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" placeholder="مثلاً: ' . esc_attr( $cfg['label'] ) . ' — نام کامل" /></td></tr>';
		}
		echo '</tbody></table>';
	}
}

if ( ! function_exists( 'javan_seda_credits_metabox' ) ) {
	function javan_seda_credits_metabox() {
		if ( ! JAVANSEDA_CREDITS || ! JAVANSEDA_CREDITS_METABOX ) {
			return;
		}
		$types = javan_seda_credits_types();
		if ( ! $types ) {
			return;
		}
		foreach ( $types as $type ) {
			add_meta_box( 'ab_credits_box', 'شناسنامۀ آهنگ (تهیه‌کنندگان)', 'javan_seda_credits_metabox_html', $type, 'normal', 'low' );
		}
	}
}

if ( ! function_exists( 'javan_seda_credits_save' ) ) {
	function javan_seda_credits_save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['javan_seda_credits_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['javan_seda_credits_nonce'], 'javan_seda_credits_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( javan_seda_credits_fields() as $key => $cfg ) {
			$field = 'ab_credit_' . $key;
			$raw   = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
			$value = is_scalar( $raw ) ? trim( sanitize_text_field( (string) $raw ) ) : '';
			if ( '' === $value ) {
				delete_post_meta( $post_id, $key );
				continue;
			}
			update_post_meta( $post_id, $key, $value );
		}
	}
}

/* ثبت هوک‌ها — هرکدام با function_exists پیچیده شده تا نبودنِ تابع، سایت را نکشد */
if ( function_exists( 'javan_seda_credits_metabox' ) ) {
	add_action( 'add_meta_boxes', 'javan_seda_credits_metabox', 10, 0 );
}
if ( function_exists( 'javan_seda_credits_save' ) ) {
	add_action( 'save_post', 'javan_seda_credits_save', 10, 1 );
}
