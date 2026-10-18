<?php
/**
 * ab-controls.php — «کنترل رابط» (از نسخۀ 3.4.1)
 *
 * هدف: هر چیزی که در دورهای اخیر در سورس قالب عوض شد و مالک ممکن است بخواهد
 * آن را خاموش/روشن یا تنظیم کند، از این فایل خوانده شود و در تنظیمات قالب
 * (Option Tree → بخش «کنترل رابط») یک کنترلر داشته باشد.
 *
 * اولویت تشخیص (به همین ترتیب):
 *   ۱) اگر در wp-config.php با define() ست شده باشد   → همان، قطعی (برای سرور/CI)
 *   ۲) وگرنه مقدار تنظیمات قالب (Option Tree)          → انتخاب شما از پیشخوان
 *   ۳) وگرنه مقدار پیش‌فرض همین فایل
 *
 * هیچ گزینه‌ای «فقط تزئینی» نیست: هر کلید جایی واقعاً مصرف می‌شود؛ مصرف‌ها:
 *   ab_glass_ui      → inc/ab-glass.php (لایۀ css/ab-glass.css)
 *   ab_ui_guard      → inc/ab-ui-guard.php (کپی لایه در css/ab-ui.css)
 *   ab_card_stats    → inc/songs_post.php (بازدید/رأی روی کاور)
 *   ab_sticky_player → inc/ab-post-extras.php (نوار چسبان پخش)
 *   ab_skip_link     → header.php (لینک «پرش به محتوای اصلی»)
 *   ab_quickbar      → home.php (نوار دسترسی بالای صفحۀ اصلی)
 *   ab_player_*      → این فایل (CSS درون‌خطی) + بلوک ۳۳ style.css (توکن‌ها)
 *   ab_footer_*      → footer.php (ستون‌ها، لینک‌های میخکوب، لینک‌های دستی، جست‌وجو)
 *
 * کلیدهای تعریف‌شدنی (معادل هر گزینه، برای wp-config.php):
 *   JAVANSEDA_GLASS_UI · JAVANSEDA_UI_GUARD · JAVANSEDA_CARD_STATS ·
 *   JAVANSEDA_STICKY_PLAYER · JAVANSEDA_SKIP_LINK · JAVANSEDA_POST_EXTRAS
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'javan_seda_ui_define' ) ) {
	/** مقدار یک constantِ اختیاری به‌صورت bool/null (null = تعریف نشده). */
	function javan_seda_ui_define( $name ) {
		if ( ! defined( $name ) ) {
			return null;
		}
		$v = constant( $name );
		if ( is_bool( $v ) ) {
			return $v;
		}
		return ( 'off' !== strtolower( (string) $v ) && '' !== (string) $v && 0 !== $v );
	}
}

if ( ! function_exists( 'javan_seda_ui_raw' ) ) {
	/** خام‌ترین خواندن از Option Tree، بدون فرض و پیش‌فرض. */
	function javan_seda_ui_raw( $key, $default = '' ) {
		static $cache = array();
		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}
		$v = $default;
		if ( function_exists( 'ot_get_option' ) ) {
			$got = ot_get_option( $key );
			if ( null !== $got && '' !== $got && false !== $got ) {
				$v = $got;
			}
		}
		$cache[ $key ] = $v;
		return $v;
	}
}

if ( ! function_exists( 'javan_seda_ui_on' ) ) {
	/**
	 * آیا یک روشن/خاموش فعال است؟
	 *
	 * @param string $key     کلید گزینه در تنظیمات قالب.
	 * @param bool   $default پیش‌فرض اگر گزینه ست نشده بود.
	 * @param string $define    نام constant اختیاری در wp-config.php.
	 */
	function javan_seda_ui_on( $key, $default = true, $define = '' ) {
		if ( '' !== $define ) {
			$d = javan_seda_ui_define( $define );
			if ( null !== $d ) {
				return $d;
			}
		}
		$v = javan_seda_ui_raw( $key, $default ? 'on' : 'off' );
		if ( is_bool( $v ) ) {
			return $v;
		}
		return 'off' !== (string) $v;
	}
}

if ( ! function_exists( 'javan_seda_ui_choice' ) ) {
	/** یک مقدار متنی که باید داخل فهرست مجاز باشد. */
	function javan_seda_ui_choice( $key, $allowed, $default = '' ) {
		$v = (string) javan_seda_ui_raw( $key, $default );
		return in_array( $v, $allowed, true ) ? $v : $default;
	}
}

if ( ! function_exists( 'javan_seda_ui_num' ) ) {
	/** عدد صحیح با کریدر (برای فاصله‌ها و تعدادها). */
	function javan_seda_ui_num( $key, $default = 0, $min = 0, $max = 999 ) {
		$raw = javan_seda_ui_raw( $key, '' );
		if ( is_array( $raw ) || is_object( $raw ) ) {
			return (int) $default;
		}
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return (int) $default;
		}
		$v = (int) preg_replace( '/[^0-9-]/', '', $raw );
		return max( $min, min( $max, $v ) );
	}
}

if ( ! function_exists( 'javan_seda_ui_player_mode' ) ) {
	function javan_seda_ui_player_mode() {
		return javan_seda_ui_choice( 'ab_player_mode', array( 'card', 'compact', 'legacy' ), 'card' );
	}
}

/**
 * v3.4.5 — کاورِ صفحۀ پست: اندازه (پیکسل) + شکل (مربع یا نسبت اصلی).
 * تنها چیزی که به بیرون داده می‌شود یک توکن (--ab-post-img-size) و یک کلاس بدنه
 * (ab-post-img-square) است؛ قواعدش در بلوک ۳۷ِ style.css است.
 */
if ( ! function_exists( 'javan_seda_ui_post_img_size' ) ) {
	/** عدد صحیح داخل بازۀ اسلایدر (۲۴۰ تا ۹۰۰، پیش‌فرض ۵۰۰). */
	function javan_seda_ui_post_img_size() {
		return javan_seda_ui_num( 'ab_post_img_size', 500, 240, 900 );
	}
}

if ( ! function_exists( 'javan_seda_post_cover_size' ) ) {
	/**
	 * کدام سایز وردپرس درخواست شود؟
	 *
	 * ab_cover (۵۰۰×۵۰۰ کات‌شده) فقط برای تصویرهای شاخصی فایل دارد که بعد از
	 * 3.4.5 بارگذاری شده‌اند؛ برای بقیه همان 'large' قبلی می‌ماند تا نه چیزی خراب
	 * شود و نه فایل بی‌جهت بزرگ‌تر دانلود شود. نبود این فایل = رفتار پیشین.
	 *
	 * @return string
	 */
	function javan_seda_post_cover_size() {
		if ( ! function_exists( 'wp_get_attachment_image_src' ) ) {
			return 'large';
		}
		$id = function_exists( 'get_post_thumbnail_id' ) ? get_post_thumbnail_id() : 0;
		if ( ! $id ) {
			return 'large';
		}
		$src = wp_get_attachment_image_src( $id, 'ab_cover' );
		if ( is_array( $src ) && isset( $src[1], $src[2] ) && 500 === (int) $src[1] && 500 === (int) $src[2] ) {
			return 'ab_cover';
		}
		return 'large';
	}
}

if ( ! function_exists( 'javan_seda_post_cover_args' ) ) {
	/**
	 * alt/title + «sizes» درست: تا srcset نزدیک‌ترین فایل را به همان عرضی بردارد
	 * که در تنظیمات انتخاب شده (بی‌این، مرورگر فایل ۱۰۲۴ پیکسلی large را می‌خواست).
	 *
	 * @return array
	 */
	function javan_seda_post_cover_args() {
		$px = javan_seda_ui_post_img_size();
		return array(
			'alt'   => get_the_title(),
			'title' => get_the_title(),
			'sizes' => sprintf( '(max-width: %1$dpx) 100vw, %1$dpx', $px ),
		);
	}
}

if ( ! function_exists( 'javan_seda_ui_body_class' ) ) {
	/** کلاس‌هایی که بلوک ۳۳ِ style.css روی اساسشان کار می‌کند. */
	function javan_seda_ui_body_class( $classes ) {
		$mode = javan_seda_ui_player_mode();
		if ( 'card' !== $mode ) {
			$classes[] = 'ab-player-' . $mode;
		}
		if ( ! javan_seda_ui_on( 'ab_player_volume', true ) ) {
			$classes[] = 'ab-player-novol';
		}
		if ( javan_seda_ui_on( 'ab_post_img_crop', true ) ) {
			$classes[] = 'ab-post-img-square';
		}
		$list = javan_seda_ui_choice( 'ab_player_list', array( 'btn', 'open', 'hide' ), 'btn' );
		if ( 'btn' !== $list ) {
			$classes[] = 'ab-player-list-' . $list;
		}
		return $classes;
	}
	add_filter( 'body_class', 'javan_seda_ui_body_class' );
}

/**
 * فقط «مقدار»ها را در :root می‌نویسد (توکن‌های --ab-player-*). قواعدِ رفتاریِ این
 * حالت‌ها در بلوک ۳۴ِ style.css است؛ این تکه در انتهای wp_head چاپ می‌شود، یعنی
 * بعد از همه استایل‌های enqueue‌شده ⇒ با همان ویژگی برنده می‌شود و !important لازم نیست.
 */
if ( ! function_exists( 'javan_seda_ui_head_css' ) ) {
	function javan_seda_ui_head_css() {
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return;
		}
		$mode = javan_seda_ui_player_mode();
		$root = array();
		$root[] = '--ab-player-gap:' . javan_seda_ui_num( 'ab_player_gap', 30, 0, 96 ) . 'px';
		$root[] = '--ab-player-radius:' . javan_seda_ui_num( 'ab_player_radius', 20, 0, 40 ) . 'px';
		$root[] = '--ab-post-img-size:' . javan_seda_ui_post_img_size() . 'px';
		if ( ! javan_seda_ui_on( 'ab_player_border', true ) ) {
			$root[] = '--ab-player-border:0';
		}
		if ( ! javan_seda_ui_on( 'ab_player_shadow', true ) ) {
			$root[] = '--ab-player-shadow:none';
		}
		if ( 'compact' === $mode ) {
			/* همان کارت، فقط سبک‌تر: جای بیشتر برای فهرست و تیتر */
			$root[] = '--ab-player-pad:10px 12px 11px';
			$root[] = '--ab-player-row-gap:10px';
			$root[] = '--ab-player-btn:36px';
			$root[] = '--ab-player-play:44px';
			$root[] = '--ab-player-list-gap:10px';
		} elseif ( 'legacy' === $mode ) {
			/* «ساده (قالب مادر)»: صفرکردن توکن‌ها = بدون کادر، بدون گردی، بدون سایه */
			$root[] = '--ab-player-bg:transparent';
			$root[] = '--ab-player-border:0';
			$root[] = '--ab-player-radius:0';
			$root[] = '--ab-player-shadow:none';
			$root[] = '--ab-player-pad:15px';
			$root[] = '--ab-player-list-bg:transparent';
			$root[] = '--ab-player-list-sep:0';
		}
		echo '<style id="ab-ui-controls">:root{' . implode( ';', $root ) . ';}</style>' . "\n";
	}
	add_action( 'wp_head', 'javan_seda_ui_head_css', 99 );
}

if ( ! function_exists( 'javan_seda_ui_footer_col' ) ) {
	/** آیا یک ستون فوتر نمایش داده شود؟ (چک‌باکس ab_footer_cols) */
	function javan_seda_ui_footer_col( $col ) {
		$v = javan_seda_ui_raw( 'ab_footer_cols', array( 'about', 'links', 'cats', 'social' ) );
		if ( ! is_array( $v ) ) {
			$v = array_filter( array_map( 'trim', explode( ',', (string) $v ) ) );
		}
		if ( empty( $v ) ) {
			return true; /* اگر افزونۀ Option Tree نصب نبود/چیزی ست نشده بود: مثل قبل */
		}
		return in_array( $col, $v, true );
	}
}

if ( ! function_exists( 'javan_seda_ui_footer_cols_count' ) ) {
	/** چند ستون فوتر روشن است؟ footer.php آن را در data-ab-cols می‌نویسد تا شبکه
	   به‌جای یک‌چهارم خالی، عرض را بین ستون‌های باقی‌مانده تقسیم کند (بلوک ۳۴). */
	function javan_seda_ui_footer_cols_count() {
		$n = 0;
		foreach ( array( 'about', 'links', 'cats', 'social' ) as $col ) {
			if ( javan_seda_ui_footer_col( $col ) ) {
				$n++;
			}
		}
		return $n ? $n : 4;
	}
}

if ( ! function_exists( 'javan_seda_ui_footer_links' ) ) {
	/**
	 * لینک‌های ستون «لینک‌های مفید»: دو لینک پیش‌فرض (اگر روشن باشد) + لینک‌های
	 * دستیِ مالک، هر خط به شکل  عنوان|آدرس  (یا عنوان = آدرس).
	 *
	 * @return array — هر آیتم = array( 'label' => …, 'url' => …, 'icon' => … )
	 */
	function javan_seda_ui_footer_links() {
		$out = array();
		if ( javan_seda_ui_on( 'ab_footer_pinned', true ) ) {
			$out[] = array(
				'label' => 'همهٔ خواننده‌ها',
				'url'   => home_url( '/singers/' ),
				'icon'  => 'icofont-microphone-alt',
			);
			$out[] = array(
				'label' => 'تازه‌ترین آهنگ‌ها',
				'url'   => home_url( '/#new-songs' ),
				'icon'  => 'icofont-music-notes',
			);
		}
		$raw = (string) javan_seda_ui_raw( 'ab_footer_links', '' );
		if ( '' !== trim( $raw ) ) {
			foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
				$line = trim( $line );
				if ( '' === $line || 0 === strpos( $line, '#' ) ) {
					continue;
				}
				$label = $line;
				$url   = '';
				if ( false !== strpos( $line, '|' ) ) {
					list( $label, $url ) = array_pad( explode( '|', $line, 2 ), 2, '' );
				} elseif ( preg_match( '/^(.*?)[=:]?\s*(https?:\/\/\S+)$/iu', $line, $m ) ) {
					$label = trim( $m[1] );
					$url   = trim( $m[2] );
				} else {
					preg_match( '/(https?:\/\/\S+)/i', $line, $m2 );
					if ( ! empty( $m2[1] ) ) {
						$url   = trim( $m2[1] );
						$label = trim( str_replace( $url, '', $line ) );
					}
				}
				$label = trim( $label, " \t-–—=:،," );
				$url   = trim( $url );
				if ( '' === $url || '' === $label ) {
					continue;
				}
				if ( 0 !== strpos( $url, 'http' ) && 0 !== strpos( $url, '/' ) ) {
					$url = 'https://' . $url;
				}
				$out[] = array( 'label' => $label, 'url' => $url, 'icon' => 'icofont-link' );
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'javan_seda_ui_footer_search' ) ) {
	function javan_seda_ui_footer_search() {
		return javan_seda_ui_on( 'ab_footer_search', true );
	}
}

if ( ! function_exists( 'javan_seda_ui_footer_cats' ) ) {
	function javan_seda_ui_footer_cats() {
		return javan_seda_ui_num( 'ab_footer_cats_num', 8, 0, 20 );
	}
}
