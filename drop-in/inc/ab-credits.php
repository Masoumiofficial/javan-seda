<?php
/**
 * v3.2.1 — «شناسنامۀ آهنگ» (مانند ahaang.com)
 * ---------------------------------------------------------------------------
 * ردیف‌های: خواننده · آهنگساز · ترانه‌سرا · تنظیم · میکس و مستر · تولید
 *
 * • مقدارها از فیلدهای ACF خوانده می‌شوند؛ اگر افزونۀ ACF نباشد، فایل
 *   inc/acf-compat.php همان کلیدها را از post_meta می‌خواند، پس هر دو حالت کار می‌کند.
 * • برای هر ردیف یک «مربوط‌شدنِ نام» به تاکسونومی هنرمند (javan_seda_artist_tax(): artist یا singer) انجام می‌شود: اگر نام با
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
	/* v3.4.7 — نامکِ فعال از لایۀ «هنرمند» خوانده می‌شود (artist یا singer)؛ اگر لایه نبود مثل قبل. */
	define( 'JAVANSEDA_CREDITS_TAX', function_exists( 'javan_seda_artist_tax' ) ? javan_seda_artist_tax() : 'singer' ); /* فقط خوانده می‌شود؛ ثبت/تغییر نمی‌کند */
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
   ۴) ردیف خواننده (از فیلد artist یا ترم‌های هنرمندِ خودِ نوشته)
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
/* 3.4.8 — نامِ تازه با همان رفتار؛ خوانندۀ آهنگ «خواننده» برچسب می‌گیرد، نه «هنرمند»،
   چون نقشِ فرد در این آهنگ است (برای تغییرش: فیلترِ javanseda_credit_singer_label) */
if ( ! function_exists( 'javan_seda_credit_artist' ) ) {
	function javan_seda_credit_artist( $post_id = 0 ) {
		return javan_seda_credit_singer( $post_id );
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
			$rows[] = array( 'label' => (string) apply_filters( 'javanseda_credit_singer_label', 'خواننده', $post_id ), 'icon' => 'icofont-radio-mic', 'value' => $singer );
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
			/* یک «چیپ»: آیکن + برچسب + مقدار — همه در یک خط (نسخۀ 3.2.3: چیدمان افقی) */
			$html .= '<span class="ab-credit">'
				. '<i class="' . esc_attr( $row['icon'] ) . '" aria-hidden="true"></i>'
				. '<b class="ab-credit__k">' . esc_html( $row['label'] ) . '</b>'
				. '<span class="ab-credit__v">' . implode( '<span class="ab-credit__sep">،</span>', $cells ) . '</span>'
				. '</span>';
		}
		if ( '' === $html ) {
			return '';
		}

		$html = '<section class="ab-credits" aria-label="' . esc_attr( javan_seda_credits_title() ) . '">'
			. '<h2 class="ab-credits__title"><i class="icofont-info-circle" aria-hidden="true"></i>' . esc_html( javan_seda_credits_title() ) . '</h2>'
			. '<div class="ab-credits__row">' . $html . '</div>'
			. '<div class="clear"></div>'
			. '</section>';

		if ( $echo ) {
			echo $html; // phpcs:ignore -- خروجی از پیش escape شده
		}
		return $html;
	}
}

/* ==========================================================================
   ۷) دادهٔ ساختاریافته (JSON-LD) — MusicRecording + MusicComposition + WebPage
   --------------------------------------------------------------------------
   چرا این‌جا؟ چون «شناسنامه» همان منبع حقیقتِ نام‌هاست. در 3.2.1 دیتای schema
   داخل بلوکِ «متن ترانه»ی single.php نشسته بود و سه اشکال داشت:
     ۱) اگر music_text خالی بود، هیچ دادهٔ ساختاریافته‌ای چاپ نمی‌شد (بیشتر نوشته‌ها!)
     ۲) «lyricist» با نامِ خواننده پر می‌شد (دادهٔ غلط برای گوگل)
     ۳) اگر آرشیو خواننده نبود، urlِ خواننده به خودِ صفحهٔ آهنگ اشاره می‌کرد
   این نسخه هر سه را درست می‌کند و duration / image / producer / keywords را هم می‌دهد.
   خاموشی:  define( 'JAVANSEDA_MUSIC_SCHEMA', false );
   ========================================================================== */
if ( ! defined( 'JAVANSEDA_MUSIC_SCHEMA' ) ) {
	define( 'JAVANSEDA_MUSIC_SCHEMA', true );
}

if ( ! function_exists( 'javan_seda_credit_person_list' ) ) {
	/** «آصف آریا / معین حبیبی» → آرایهٔ Person؛ url فقط وقتی آرشیوِ همان نام وجود دارد */
	function javan_seda_credit_person_list( $value, $type = 'Person' ) {
		$names = javan_seda_credit_names( $value );
		if ( ! $names ) {
			$one = trim( wp_strip_all_tags( (string) $value ) );
			if ( '' === $one ) {
				return array();
			}
			$names = array( $one );
		}
		$out = array();
		foreach ( $names as $name ) {
			$person = array(
				'@type' => $type,
				'name'  => $name,
			);
			$url = javan_seda_credit_url( $name );
			if ( '' !== $url ) {
				$person['url'] = $url;
			}
			$out[] = $person;
		}
		return $out;
	}
}

if ( ! function_exists( 'javan_seda_credit_one_or_many' ) ) {
	/** یک نفر → شیء، چند نفر → آرایه (هر دو در schema.org مجازند) */
	function javan_seda_credit_one_or_many( $list ) {
		if ( ! is_array( $list ) || ! $list ) {
			return array();
		}
		if ( 1 === count( $list ) ) {
			return $list[0];
		}
		return $list;
	}
}

if ( ! function_exists( 'javan_seda_credit_iso_duration' ) ) {
	/** «۳:۴۵» / «215» / «01:12:30» / «3 دقیقه» → PT3M45S — اگر نشد بتواند '' */
	function javan_seda_credit_iso_duration( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return '';
		}
		$raw = str_replace(
			array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ),
			array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
			$raw
		);
		$has_min = (bool) preg_match( '/دقیقه| دقیقه|minute|minutes|\bmin\b/iu', $raw );
		$has_sec = (bool) preg_match( '/ثانیه|second|seconds|\bsec\b/iu', $raw );
		$has_hr  = (bool) preg_match( '/ساعت|hour|hours|\bhr\b/iu', $raw );
		$raw = preg_replace( '/[^0-9.:]/u', '', $raw ); /* فقط رقم، دونقطه و ممیز */
		$raw = trim( (string) $raw );
		$sec = -1;
		if ( preg_match( '/^\d+(\.\d+)?$/', $raw ) ) {
			$sec = (int) round( (float) $raw );
			if ( $has_min ) {
				$sec *= 60;
			} elseif ( $has_hr ) {
				$sec *= 3600;
			}
		} elseif ( preg_match( '/^(\d{1,3}):(\d{1,2}):(\d{1,2})$/', $raw, $m ) ) {
			if ( (int) $m[2] < 60 && (int) $m[3] < 60 ) {
				$sec = ( (int) $m[1] * 3600 ) + ( (int) $m[2] * 60 ) + (int) $m[3];
			}
		} elseif ( preg_match( '/^(\d{1,3}):(\d{1,2})$/', $raw, $m ) ) {
			if ( (int) $m[2] < 60 ) {
				$sec = ( (int) $m[1] * 60 ) + (int) $m[2];
			}
		}
		if ( $sec < 1 || $sec > 36000 ) {
			return ''; /* نامعتبر/غیرمنطقی ⇒ هیچ durationی در schema نمی‌آید، نه دادهٔ غلط */
		}
		$h = (int) floor( $sec / 3600 );
		$i = (int) floor( ( $sec % 3600 ) / 60 );
		$s = $sec % 60;
		$out = 'PT';
		if ( $h > 0 ) {
			$out .= $h . 'H';
		}
		if ( $i > 0 ) {
			$out .= $i . 'M';
		}
		if ( $s > 0 ) {
			$out .= $s . 'S';
		}
		if ( 'PT' === $out ) {
			$out = 'PT' . $sec . 'S';
		}
		return $out;
	}
}

if ( ! function_exists( 'javan_seda_music_schema' ) ) {
	/**
	 * @param int  $post_id
	 * @param bool $echo
	 * @return string تگ <script> یا رشتهٔ خالی
	 */
	function javan_seda_music_schema( $post_id = 0, $echo = true ) {
		static $built = array();
		if ( ! defined( 'JAVANSEDA_MUSIC_SCHEMA' ) || ! JAVANSEDA_MUSIC_SCHEMA ) {
			return '';
		}
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		if ( ! $post_id || isset( $built[ $post_id ] ) ) {
			return '';
		}
		$permalink = get_permalink( $post_id );
		if ( is_wp_error( $permalink ) || '' === (string) $permalink ) {
			return '';
		}
		$permalink = (string) $permalink;

		$song  = javan_seda_credit_value( 'song', $post_id );
		$title = wp_strip_all_tags( (string) get_the_title( $post_id ) );
		if ( '' === $song ) {
			$song = $title;
		}

		$rec = array(
			'@type'            => 'MusicRecording',
			'@id'              => $permalink . '#recording',
			'name'             => $song,
			'url'              => $permalink,
			'inLanguage'       => 'fa-IR',
			'datePublished'    => get_the_date( 'c', $post_id ),
			'dateModified'     => get_the_modified_date( 'c', $post_id ),
			'recordingOf'      => array( '@id' => $permalink . '#composition' ),
			'mainEntityOfPage' => array( '@id' => $permalink . '#webpage' ),
		);

		/* خواننده: از فیلد artist یا ترم‌های هنرمند — اگر نبود، دروغ نمی‌سازیم */
		$by = javan_seda_credit_person_list( javan_seda_credit_singer( $post_id ) );
		if ( $by ) {
			$rec['byArtist'] = javan_seda_credit_one_or_many( $by );
		}

		if ( has_post_thumbnail( $post_id ) ) {
			$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'large' );
			if ( is_array( $src ) && ! empty( $src[0] ) ) {
				$rec['image'] = array(
					'@type'  => 'ImageObject',
					'url'    => (string) $src[0],
					'width'  => isset( $src[1] ) ? (int) $src[1] : 0,
					'height' => isset( $src[2] ) ? (int) $src[2] : 0,
				);
			}
		}

		/* فایل صوتی + مدت (اگر فیلد duration را بعداً بسازید، خودکار وارد می‌شود) */
		foreach ( array( 'music320', 'music128' ) as $audio_key ) {
			$audio_url = trim( (string) javan_seda_credit_value( $audio_key, $post_id ) );
			if ( '' === $audio_url ) {
				continue;
			}
			$audio = array(
				'@type'          => 'AudioObject',
				'contentUrl'     => $audio_url,
				'encodingFormat' => 'audio/mpeg',
			);
			foreach ( array( 'duration', 'music_duration', 'song_duration' ) as $dur_key ) {
				$dur = javan_seda_credit_iso_duration( javan_seda_credit_value( $dur_key, $post_id ) );
				if ( '' !== $dur ) {
					$audio['duration']      = $dur;
					$rec['duration']        = $dur;
					break;
				}
			}
			$rec['audio'] = $audio;
			break;
		}

		$prod = javan_seda_credit_person_list( javan_seda_credit_value( 'producer', $post_id ), 'Organization' );
		if ( $prod ) {
			$rec['producer'] = javan_seda_credit_one_or_many( $prod );
		}

		/* v3.3.8 — دو عددی که روی کارت آهنگ هم نمایش داده می‌شود: بازدید (متای views) و رأی
		   (kksr_avg یا kksr_ratings ÷ kksr_casts). فقط وقتی می‌آیند که عدد واقعی وجود داشته باشد؛
		   اگر هیچ‌کدام نبود، این بلوک کلاً چاپ نمی‌شود (اسکیما دروغ نمی‌سازیم). */
		$counters = array();
		$ab_views = (int) get_post_meta( $post_id, 'views', true );
		if ( $ab_views > 0 ) {
			$counters[] = array(
				'@type'                => 'InteractionCounter',
				'interactionType'      => 'https://schema.org/ViewAction',
				'userInteractionCount' => $ab_views,
			);
		}
		$ab_casts = (int) get_post_meta( $post_id, 'kksr_casts', true );
		$ab_avg   = (float) get_post_meta( $post_id, 'kksr_avg', true );
		if ( $ab_avg <= 0 ) {
			$ab_sum = (float) get_post_meta( $post_id, 'kksr_ratings', true );
			$ab_avg = ( $ab_casts > 0 && $ab_sum > 0 ) ? round( $ab_sum / $ab_casts, 2 ) : 0.0;
		}
		$ab_rating = array();
		if ( $ab_avg > 0 && $ab_casts > 0 ) {
			$ab_rating = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => (float) round( $ab_avg, 1 ),
				'ratingCount' => $ab_casts,
				'bestRating'  => 5,
				'worstRating' => 1,
			);
		}
		if ( $counters ) {
			$rec['interactionStatistic'] = ( 1 === count( $counters ) ) ? $counters[0] : $counters;
		}
		if ( $ab_rating ) {
			$rec['aggregateRating'] = $ab_rating;
		}


		$comp = array(
			'@type'      => 'MusicComposition',
			'@id'        => $permalink . '#composition',
			'name'       => $song,
			'inLanguage' => 'fa-IR',
			'recordedAs' => array( '@id' => $permalink . '#recording' ),
		);
		$lyr = javan_seda_credit_person_list( javan_seda_credit_value( 'lyricist', $post_id ) );
		if ( $lyr ) {
			$comp['lyricist'] = javan_seda_credit_one_or_many( $lyr );
		}
		$cmp = javan_seda_credit_person_list( javan_seda_credit_value( 'composer', $post_id ) );
		if ( $cmp ) {
			$comp['composer'] = javan_seda_credit_one_or_many( $cmp );
		}
		$lyrics_raw = (string) javan_seda_credit_value( 'music_text', $post_id );
		if ( '' !== trim( $lyrics_raw ) ) {
			$plain = wp_strip_all_tags( str_replace( array( '</p>', '<br />', '<br>', '<br/>' ), "\n", $lyrics_raw ) );
			$plain = trim( preg_replace( '/[ \t]+/u', ' ', $plain ) );
			$plain = preg_replace( "/\n{3,}/u", "\n\n", $plain );
			if ( '' !== $plain ) {
				$comp['lyrics'] = array(
					'@type'      => 'CreativeWork',
					'text'       => $plain,
					'inLanguage' => 'fa-IR',
				);
			}
		}

		$keys = array();
		foreach ( array_keys( javan_seda_credits_fields() ) as $key ) {
			$value = trim( wp_strip_all_tags( (string) javan_seda_credit_value( $key, $post_id ) ) );
			if ( '' !== $value ) {
				$keys[] = $value;
			}
		}
		if ( '' !== $title ) {
			array_unshift( $keys, $title );
		}
		/* اگر افزونۀ سئو فعال باشد، گرهٔ WebSite را او می‌سازد؛ دوتا نسازیم */
		$standalone = ! ( function_exists( 'javan_seda_seo_has_plugin' ) && '' !== javan_seda_seo_has_plugin() );

		$web = array(
			'@type'      => 'WebPage',
			'@id'        => $permalink . '#webpage',
			'url'        => $permalink,
			'name'       => $title,
			'inLanguage' => 'fa-IR',
			'mainEntity' => array( '@id' => $permalink . '#recording' ),
		);
		if ( $standalone ) {
			$web['isPartOf'] = array( '@id' => home_url( '/' ) . '#website' );
		}
		if ( $keys ) {
			$web['keywords'] = implode( ', ', array_unique( $keys ) );
		}

		$nodes = array( $rec, $comp, $web );
		if ( $standalone ) {
			array_unshift(
				$nodes,
				array(
					'@type'          => 'WebSite',
					'@id'            => home_url( '/' ) . '#website',
					'url'            => home_url( '/' ),
					'name'           => get_bloginfo( 'name' ),
					'inLanguage'     => 'fa-IR',
					'potentialAction' => array(
						'@type'       => 'SearchAction',
						'target'      => home_url( '/' ) . '?s={search_term_string}',
						'query-input' => 'required name=search_term_string',
					),
				)
			);
		}

		$graph = array(
			'@context' => 'https://schema.org',
			'@graph'   => $nodes,
		);
		$graph = apply_filters( 'javanseda_music_schema', $graph, $post_id );
		if ( ! is_array( $graph ) ) {
			return '';
		}
		$built[ $post_id ] = true;

		/* بدون JSON_UNESCAPED_SLASHES: پیش‌فرض «/» را \/ می‌کند تا متن نتواند از تگ script خارج شود */
		$json = wp_json_encode( $graph, JSON_UNESCAPED_UNICODE );
		if ( ! is_string( $json ) || '' === $json || 'null' === $json ) {
			return '';
		}
		$html = '<script type="application/ld+json">' . $json . '</script>';
		if ( $echo ) {
			echo $html . "\n"; // phpcs:ignore -- JSON از wp_json_encode می‌آید
		}
		return $html;
	}
}

if ( ! function_exists( 'javan_seda_music_schema_head' ) ) {
	/** در wp_head چاپ می‌شود تا به متن ترانه یا جای دیگری از صفحه وابسته نباشد */
	function javan_seda_music_schema_head() {
		if ( ! is_single() ) {
			return;
		}
		javan_seda_music_schema( get_queried_object_id() );
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

if ( function_exists( 'javan_seda_music_schema_head' ) ) {
	add_action( 'wp_head', 'javan_seda_music_schema_head', 6 );
}
