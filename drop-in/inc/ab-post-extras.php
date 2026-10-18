<?php
/**
 * ab-post-extras.php — دو افزودۀ کوچک روی صفحۀ پست/آهنگ (3.2.0 · در 3.2.4 نوار پخش ارتقا یافت)
 * -----------------------------------------------------------------------------------------
 *  ۱) javan_seda_social_box() : باکس «شبکه‌های اجتماعی» (تلگرام · اینستاگرام · بله · روبیکا)
 *     که در single.php دقیقاً بالای لینک‌های دانلود چاپ می‌شود. لینک‌ها و متنِ دکمه‌ها از
 *     تنظیمات قالب → بخش «شبکه‌های اجتماعی» می‌آیند. اگر هیچ لینکی پر نباشد، باکس اصلاً
 *     رندر نمی‌شود (جای خالی نمی‌ماند). با گزینهٔ «باکس شبکه‌های اجتماعی در صفحهٔ پست» هم
 *     می‌شود روشن/خاموشش کرد (کلید fixbtn).
 *  ۲) javan_seda_sticky_player() : نوارِ چسبانِ پخش در پایین صفحه که همان عنصر <audio>
 *     خودِ قالب را کنترل می‌کند (پخش/توقف، میلهٔ پیشرفت، زمان، بستن). پلیرِ قالب سرِ جایش
 *     می‌ماند؛ این فقط یک کنترلِ دوم است برای وقتی کاربر اسکرول می‌کند.
 *     از نسخۀ 3.2.4 : رنگ‌ها از متغیرهای قالب (پس در حالت شب تیره است) · ارتفاع کمتر ·
 *     دکمۀ «قبلی/بعدی» (بین قطعه‌های آلبوم، یا بین آهنگ‌های سایت) · پخش روی صفحۀ اصلی
 *     با پلیر مستقل. خاموشی‌ها: JAVANSEDA_STICKY_PLAYER=false · JAVANSEDA_STICKY_ON_HOME=false
 *
 * خاموشی کامل این فایل:  define( 'JAVANSEDA_POST_EXTRAS', false );   در wp-config.php
 * خاموشی فقط نوارِ پخش :  define( 'JAVANSEDA_STICKY_PLAYER', false );
 *
 * قاعدۀ پروژه: هیچ فیلتری روی the_content / template_include نیست. باکس اجتماعی با یک
 * فراخوانی محافظت‌شدۀ تابع در single.php چاپ می‌شود و نوارِ پخش فقط روی wp_footer می‌نشیند.
 * همه ساختارها با PHP 5.6 سازگارند (بدون بستۀ static، بدون ??، بدون fn()).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'JAVANSEDA_POST_EXTRAS' ) ) {
	define( 'JAVANSEDA_POST_EXTRAS', true );
}
if ( ! defined( 'JAVANSEDA_STICKY_PLAYER' ) ) {
	define( 'JAVANSEDA_STICKY_PLAYER', true );
}

if ( ! function_exists( 'javan_seda_x_on' ) ) {
	function javan_seda_x_on() {
		return ( defined( 'JAVANSEDA_POST_EXTRAS' ) && false === JAVANSEDA_POST_EXTRAS ) ? false : true;
	}
}

if ( ! function_exists( 'javan_seda_x_option' ) ) {
	/** ot_get_option با احتیاط (اگر option-tree جایی فعال نبود، رشتهٔ خالی می‌دهیم نه خطا) */
	function javan_seda_x_option( $key, $default = '' ) {
		static $cache = array();
		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}
		$val = '';
		if ( function_exists( 'ot_get_option' ) ) {
			$val = ot_get_option( $key, $default );
		} elseif ( function_exists( 'get_option' ) ) {
			$all  = get_option( 'options' );
			$all  = is_array( $all ) ? $all : array();
			$val  = isset( $all[ $key ] ) ? $all[ $key ] : $default;
		}
		if ( ! is_scalar( $val ) ) {
			$val = '';
		}
		$cache[ $key ] = trim( (string) $val );
		return $cache[ $key ];
	}
}

if ( ! function_exists( 'javan_seda_x_field' ) ) {
	/** فیلد ACF را به‌شکل رشتهٔ امن برمی‌گرداند (لینک دانلود/پخش) */
	function javan_seda_x_field( $key, $post_id = 0 ) {
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}
		$v = $post_id ? get_field( $key, $post_id ) : get_field( $key );
		if ( is_array( $v ) ) {
			if ( isset( $v['url'] ) && is_scalar( $v['url'] ) ) {
				$v = $v['url'];
			} else {
				$v = '';
			}
		}
		return is_scalar( $v ) ? trim( (string) $v ) : '';
	}
}

/* -------------------------------------------------------------------------
 * ۱) باکس شبکه‌های اجتماعی — بالای لینک‌های دانلود
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_social_box' ) ) {
	/**
	 * @param int  $post_id
	 * @param bool $echo true ⇒ چاپ کن، false ⇒ رشته برگردان
	 * @return string
	 */
	function javan_seda_social_box( $post_id = 0, $echo = true ) {
		$html = '';
		if ( ! javan_seda_x_on() ) {
			return $html;
		}
		if ( 'off' === javan_seda_x_option( 'fixbtn', 'on' ) ) {
			return $html; /* کاربر خودش از تنظیمات قالب خاموش کرده */
		}
		/* ترتیب کلیدها: option لینک · option متن دکمه · متن پیش‌فرض · کلاسِ رنگ · آیکن (همه در css/icofont.min.css موجودند) */
		$networks = array(
			array( 'telegram',  'teltxt',    'کانال تلگرام',       'telegram',  'icofont-paper-plane' ),
			array( 'instagram', 'instxt',    'صفحهٔ اینستاگرام',   'instagram', 'icofont-instagram' ),
			array( 'bale',      'baletxt',   'کانال بله',          'bale',      'icofont-chat' ),
			array( 'rubika',    'rubikatxt', 'کانال روبیکا',       'rubika',    'icofont-comment' ),
		);
		$items = array();
		foreach ( $networks as $net ) {
			$url = javan_seda_x_option( $net[0], '' );
			if ( '' === $url ) {
				continue;
			}
			$label = javan_seda_x_option( $net[1], '' );
			if ( '' === $label ) {
				$label = $net[2];
			}
			$host = trim( preg_replace( '#/+$#', '', preg_replace( '/^https?:\/\//', '', preg_replace( '/\?.*$/', '', (string) $url ) ) ) );
			$items[] = '<a class="ab-social ab-social--' . esc_attr( $net[3] ) . '" href="' . esc_url( $url ) . '"'
				. ' target="_blank" rel="noreferrer noopener nofollow">'
				. '<span class="ab-social__icon"><i class="' . esc_attr( $net[4] ) . '" aria-hidden="true"></i></span>'
				. '<span class="ab-social__txt">'
				. '<b>' . esc_html( strip_tags( $label ) ) . '</b>'
				. '<em>' . esc_html( $host ) . '</em>'
				. '</span>'
				. '<i class="icofont-thin-left ab-social__arrow" aria-hidden="true"></i>'
				. '</a>';
		}
		if ( ! $items ) {
			return $html; /* هیچ لینکی در تنظیمات قالب پر نیست ⇒ باکس خالی چاپ نکنیم */
		}
		$html .= '<div class="ab-social-box">'
			. '<div class="ab-social-box__head">'
			. '<i class="icofont-share-alt" aria-hidden="true"></i>'
			. '<span class="ab-social-box__title">ما را دنبال کنید</span>'
			. '<span class="ab-social-box__hint">آهنگ‌های تازه را در پیام‌رسان تحویل بگیرید</span>'
			. '</div>'
			. '<div class="ab-social-box__row">' . implode( '', $items ) . '</div>'
			. '<div class="clear"></div>'
			. '</div>';
		if ( $echo ) {
			echo $html; // phpcs:ignore -- خروجی از پیش escape شده
		}
		return $html;
	}
}

/* -------------------------------------------------------------------------
 * ۲) نوارِ چسبانِ پخش (mini-player) — پایین صفحه
 *     نسخۀ 3.2.4 — سه چیزی که خواسته شد:
 *       الف) رنگ‌ها از متغیرهای خودِ قالب می‌آیند (--ab-surface/--ab-text/--ab-line…)
 *          ⇒ در حالت شب (بدنۀ صفحه کلاس `night` می‌گیرد) نوار هم تیره می‌شود، نه سفید.
 *       ب) ارتفاع کمتر: از ≈ ۸۴px به ≈ ۶۰px (دسکتاپ) و ≈ ۵۰px در موبایل.
 *       ج) دکمۀ «قبلی / بعدی» + پخش روی صفحۀ اصلی:
 *            - صفحۀ آلبوم  : بین قطعه‌های همان آلبوم می‌رود؛ کار با کلیک روی ردیفِ
 *                            خودِ پلیرِ قالب انجام می‌شود (منطق پلیر کپی نشده، فقط
 *                            هدایت می‌شود) ⇒ نوارِ پایین و پلیرِ صفحه همیشه هم‌خوان‌اند.
 *            - تک‌آهنگ     : آهنگ قبلی / بعدیِ همان دسته.
 *            - صفحۀ اصلی   : تازه‌ترین آهنگ‌های سایت با پلیر مستقل (#ab-sp-audio).
 *          `href` دکمه‌ها آدرس واقعی نوشتۀ مقصد است ⇒ اگر جاوااسکریپت اجرا نشود،
 *          دکمه‌ها مثل لینکِ معمولی کار می‌کنند (چیزی نمی‌شکند).
 *     پخش/توقف، میلهٔ پیشرفت و زمان همان عنصر <audio id="audio"> قالب را کنترل می‌کند؛
 *     اگر پلیرِ صفحه نبود، نوار از پلیر مستقل خودش استفاده می‌کند.
 *
 *   خاموشی کل نوار            :  define( 'JAVANSEDA_STICKY_PLAYER', false );
 *   خاموشی فقط روی صفحۀ اصلی  :  define( 'JAVANSEDA_STICKY_ON_HOME', false );
 *   تعداد آهنگ صفحۀ اصلی      :  define( 'JAVANSEDA_STICKY_HOME_LIMIT', 8 );
 * ---------------------------------------------------------------------- */
if ( ! defined( 'JAVANSEDA_STICKY_ON_HOME' ) ) {
	define( 'JAVANSEDA_STICKY_ON_HOME', true );
}
if ( ! defined( 'JAVANSEDA_STICKY_HOME_LIMIT' ) ) {
	define( 'JAVANSEDA_STICKY_HOME_LIMIT', 8 );
}

if ( ! function_exists( 'javan_seda_sticky_player_src' ) ) {
	/** نشانی فایل صوتی این نوشته (برای اینکه بدون پلیر، نوار تهی چاپ نشود) */
	function javan_seda_sticky_player_src( $post_id = 0 ) {
		foreach ( array( 'music320', 'music128', 'album320', 'album128' ) as $k ) {
			$v = javan_seda_x_field( $k, $post_id );
			if ( '' !== $v ) {
				return $v;
			}
		}
		return '';
	}
}

if ( ! function_exists( 'javan_seda_sticky_fa_num' ) ) {
	/** رقم لاتین ⇒ رقم فارسی (برای شمارۀ قطعه) */
	function javan_seda_sticky_fa_num( $n ) {
		return str_replace(
			array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
			array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
			(string) $n
		);
	}
}

if ( ! function_exists( 'javan_seda_x_sub' ) ) {
	/** get_sub_field با احتیاط (اگر نه ACF بود و نه لایۀ سازگاری، رشتهٔ خالی) */
	function javan_seda_x_sub( $key ) {
		if ( ! function_exists( 'get_sub_field' ) ) {
			return '';
		}
		$v = get_sub_field( $key );
		if ( is_array( $v ) ) {
			$v = ( isset( $v['url'] ) && is_scalar( $v['url'] ) ) ? $v['url'] : '';
		}
		if ( ! is_scalar( $v ) ) {
			return '';
		}
		return trim( wp_strip_all_tags( (string) $v ) );
	}
}

if ( ! function_exists( 'javan_seda_sticky_track' ) ) {
	/** یک «قطعه» از روی یک نوشته: عنوان · خواننده · فایل پخش · آدرس نوشته · تصویر */
	function javan_seda_sticky_track( $post_id = 0 ) {
		$post_id = (int) $post_id;
		$src     = javan_seda_sticky_player_src( $post_id );
		if ( ! $post_id || '' === $src ) {
			return array();
		}
		$song   = javan_seda_x_field( 'song', $post_id );
		$artist = javan_seda_x_field( 'artist', $post_id );
		$title  = ( '' !== $song ) ? $song : (string) get_the_title( $post_id );
		$img    = '';
		if ( function_exists( 'get_the_post_thumbnail_url' ) && has_post_thumbnail( $post_id ) ) {
			$img = get_the_post_thumbnail_url( $post_id, 'thumb3' );
			$img = is_scalar( $img ) ? trim( (string) $img ) : '';
		}
		$url = ( function_exists( 'get_permalink' ) ) ? get_permalink( $post_id ) : '';
		return array(
			'id'  => $post_id,
			't'   => trim( wp_strip_all_tags( $title ) ),
			'a'   => trim( wp_strip_all_tags( $artist ) ),
			's'   => $src,
			'u'   => ( is_scalar( $url ) ) ? (string) $url : '',
			'img' => $img,
		);
	}
}

if ( ! function_exists( 'javan_seda_sticky_album_tracks' ) ) {
	/** قطعه‌های آلبوم — همان فیلد تکرارشوندۀ `album` که single.php با آن پلیر می‌سازد */
	function javan_seda_sticky_album_tracks( $post_id = 0 ) {
		$tracks  = array();
		$post_id = (int) $post_id;
		if ( ! $post_id || ! function_exists( 'have_rows' ) || ! function_exists( 'the_row' ) ) {
			return $tracks;
		}
		if ( ! have_rows( 'album', $post_id ) ) {
			return $tracks;
		}
		$n      = 0;
		$artist = trim( wp_strip_all_tags( javan_seda_x_field( 'artist', $post_id ) ) );
		$url    = ( function_exists( 'get_permalink' ) ) ? get_permalink( $post_id ) : '';
		$url    = ( is_scalar( $url ) ) ? (string) $url : '';
		while ( have_rows( 'album', $post_id ) ) {
			the_row();
			$n++;
			$src = javan_seda_x_sub( 'albumlink128' );
			if ( '' === $src ) {
				$src = javan_seda_x_sub( 'albumlink320' );
			}
			if ( '' === $src ) {
				continue; /* سطری که فایل پخش ندارد در فهرست نمی‌آید */
			}
			$name = javan_seda_x_sub( 'song_names' );
			$tracks[] = array(
				'id'  => $post_id,
				't'   => ( '' !== $name ) ? $name : ( 'قطعه ' . javan_seda_sticky_fa_num( $n ) ),
				'a'   => $artist,
				's'   => $src,
				'u'   => $url,
				'img' => '',
				'row' => $n,
			);
		}
		return $tracks;
	}
}

if ( ! function_exists( 'javan_seda_sticky_adjacent' ) ) {
	/** آهنگ قبلی و بعدیِ همین نوشته (در همان دسته) — هرکدام پخش آنلاین داشته باشد */
	function javan_seda_sticky_adjacent( $post_id = 0 ) {
		$out     = array( 'tracks' => array(), 'index' => 0 );
		$post_id = (int) $post_id;
		$cur     = javan_seda_sticky_track( $post_id );
		if ( ! $cur ) {
			return $out;
		}
		$list    = array();
		$prev_id = 0;
		$next_id = 0;
		if ( function_exists( 'get_previous_post' ) ) {
			$p = get_previous_post( true );
			if ( is_object( $p ) && isset( $p->ID ) ) {
				$prev_id = (int) $p->ID;
			}
		}
		if ( function_exists( 'get_next_post' ) ) {
			$n = get_next_post( true );
			if ( is_object( $n ) && isset( $n->ID ) ) {
				$next_id = (int) $n->ID;
			}
		}
		if ( $prev_id ) {
			$t = javan_seda_sticky_track( $prev_id );
			if ( $t ) {
				$list[] = $t;
			}
		}
		$out['index'] = count( $list );
		$list[]       = $cur;
		if ( $next_id ) {
			$t = javan_seda_sticky_track( $next_id );
			if ( $t ) {
				$list[] = $t;
			}
		}
		$out['tracks'] = $list;
		return $out;
	}
}

if ( ! function_exists( 'javan_seda_sticky_recent' ) ) {
	/** تازه‌ترین آهنگ‌های دارای پخش آنلاین (برای صفحۀ اصلی) — قدیمی‌ترین در ابتدا */
	function javan_seda_sticky_recent( $limit = 8 ) {
		$out   = array( 'tracks' => array(), 'index' => 0 );
		$limit = (int) $limit;
		if ( $limit < 1 ) {
			$limit = 1;
		}
		if ( ! function_exists( 'get_posts' ) ) {
			return $out;
		}
		$ids = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => (int) ( $limit * 3 ), /* تا ۳ برابر می‌گردیم تا فهرستِ پُرمصرف پر شود */
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		if ( ! $ids || ! is_array( $ids ) ) {
			return $out;
		}
		$list = array();
		foreach ( $ids as $id ) {
			if ( count( $list ) >= $limit ) {
				break;
			}
			$t = javan_seda_sticky_track( (int) $id );
			if ( $t ) {
				array_unshift( $list, $t ); /* ⇒ «بعدی» = آهنگِ تازه‌تر */
			}
		}
		$out['tracks'] = $list;
		if ( $list ) {
			$out['index'] = count( $list ) - 1; /* در صفحۀ اصلی تازه‌ترین انتخاب شود */
		}
		return $out;
	}
}

if ( ! function_exists( 'javan_seda_sticky_eq' ) ) {
	/** اکولایزر کوچک روی تصویر — فقط وقتی در حال پخش است دیده می‌شود (CSS) */
	function javan_seda_sticky_eq() {
		return '<span class="ab-sp-eq" aria-hidden="true"><i></i><i></i><i></i></span>';
	}
}

if ( ! function_exists( 'javan_seda_sticky_thumb' ) ) {
	/** تصویر بندانگشتیِ نوار (یا جاینگهدارِ آیکن) + اکولایزر */
	function javan_seda_sticky_thumb( $post_id = 0, $img_url = '' ) {
		$eq = javan_seda_sticky_eq();
		if ( '' !== $img_url ) {
			return '<span class="ab-sp-thumb"><img src="' . esc_url( $img_url ) . '" alt="" loading="lazy" data-ab-sp-img />' . $eq . '</span>';
		}
		if ( $post_id && function_exists( 'has_post_thumbnail' ) && has_post_thumbnail( $post_id ) && function_exists( 'get_the_post_thumbnail' ) ) {
			return '<span class="ab-sp-thumb">' . get_the_post_thumbnail( $post_id, 'thumb3', array( 'alt' => '', 'loading' => 'lazy' ) ) . $eq . '</span>';
		}
		return '<span class="ab-sp-thumb ab-sp-thumb--none" aria-hidden="true"><i class="icofont-music-note"></i>' . $eq . '</span>';
	}
}

if ( ! function_exists( 'javan_seda_sticky_player' ) ) {
	function javan_seda_sticky_player() {
		if ( ! javan_seda_x_on() || ! JAVANSEDA_STICKY_PLAYER ) {
			return;
		}
		if ( ! function_exists( 'is_singular' ) ) {
			return;
		}
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return;
		}
		$on_home = ( defined( 'JAVANSEDA_STICKY_ON_HOME' ) && false === JAVANSEDA_STICKY_ON_HOME ) ? false : true;
		$ctx     = '';
		if ( is_singular( 'post' ) ) {
			$ctx = 'post';
		} elseif ( $on_home ) {
			$front = ( function_exists( 'is_home' ) && is_home() ) || ( function_exists( 'is_front_page' ) && is_front_page() );
			if ( $front && ( ! function_exists( 'is_paged' ) || ! is_paged() ) ) {
				$ctx = 'home';
			}
		}
		if ( '' === $ctx ) {
			return;
		}
		$post_id = (int) get_the_ID();
		$mode    = 'links';
		$tracks  = array();
		$index   = 0;
		if ( 'post' === $ctx && $post_id ) {
			$album = javan_seda_sticky_album_tracks( $post_id );
			if ( count( $album ) >= 2 ) {
				$mode   = 'album';
				$tracks = $album;
			} else {
				$built  = javan_seda_sticky_adjacent( $post_id );
				$tracks = $built['tracks'];
				$index  = $built['index'];
			}
		} else {
			$limit  = ( defined( 'JAVANSEDA_STICKY_HOME_LIMIT' ) ) ? (int) JAVANSEDA_STICKY_HOME_LIMIT : 8;
			$built  = javan_seda_sticky_recent( $limit );
			$tracks = $built['tracks'];
			$index  = $built['index'];
		}
		if ( ! $tracks ) {
			return; /* هیچ آهنگِ قابل‌پخشی نیست ⇒ نوار تهی چاپ نمی‌شود */
		}
		if ( ! isset( $tracks[ $index ] ) ) {
			$index = 0;
		}
		$cur   = $tracks[ $index ];
		$count = count( $tracks );
		/* در آلبوم تا کاربر قطعه‌ای را انتخاب نکرده، عنوانِ خودِ آلبوم نشان داده می‌شود */
		if ( 'album' === $mode && $post_id ) {
			$song = javan_seda_x_field( 'song', $post_id );
			if ( '' === $song ) {
				$song = (string) get_the_title( $post_id );
			}
			$cur['t'] = trim( wp_strip_all_tags( $song ) );
			$cur['a'] = trim( wp_strip_all_tags( javan_seda_x_field( 'artist', $post_id ) ) );
		}
		$nav  = ( $count >= 2 ) ? true : false;
		$prev = ( $index > 0 ) ? $tracks[ $index - 1 ] : $tracks[ $count - 1 ];
		$next = ( $index + 1 < $count ) ? $tracks[ $index + 1 ] : $tracks[0];
		$tag  = ( '' !== $cur['u'] ) ? 'a' : 'span';

		$json = '';
		if ( 'album' !== $mode ) {
			$rows = array();
			foreach ( $tracks as $t ) {
				$rows[] = array(
					'id'  => (int) $t['id'],
					't'   => $t['t'],
					'a'   => $t['a'],
					's'   => $t['s'],
					'u'   => $t['u'],
					'img' => $t['img'],
				);
			}
			if ( function_exists( 'wp_json_encode' ) ) {
				$json = wp_json_encode( $rows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
			}
			if ( ! is_string( $json ) || '' === $json || '[]' === $json ) {
				$json = '';
			}
		}

		if ( $nav ) {
			if ( 'album' === $mode ) {
				$nav_prev = '<button type="button" class="ab-sp-nav" data-ab-sp-go="prev" aria-label="قطیعۀ قبلی" title="قطیعۀ قبلی"><i class="icofont-forward" aria-hidden="true"></i></button>';
				$nav_next = '<button type="button" class="ab-sp-nav" data-ab-sp-go="next" aria-label="قطیعۀ بعدی" title="قطیعۀ بعدی"><i class="icofont-rewind" aria-hidden="true"></i></button>';
			} else {
				$nav_prev = '<a class="ab-sp-nav" href="' . esc_url( $prev['u'] ) . '" data-ab-sp-go="prev" aria-label="آهنگ قبلی" title="آهنگ قبلی"><i class="icofont-forward" aria-hidden="true"></i></a>';
				$nav_next = '<a class="ab-sp-nav" href="' . esc_url( $next['u'] ) . '" data-ab-sp-go="next" aria-label="آهنگ بعدی" title="آهنگ بعدی"><i class="icofont-rewind" aria-hidden="true"></i></a>';
			}
		} else {
			$nav_prev = '';
			$nav_next = '';
		}

		$html = '<div id="ab-sticky-player" class="ab-sticky" role="region" aria-label="پخش آهنگ"'
			. ' data-ab-sp-mode="' . esc_attr( $mode ) . '" data-ab-sp-index="' . (int) $index . '">'
			. '<div class="ab-sp__in">';
		/* در صفحۀ اصلی $post_id به آخرین نوشتهٔ حلقۀ لیست اشاره می‌کند ⇒ تصویر را
		   فقط از همان آیتمِ انتخابی می‌گیریم، نه از global. */
		$thumb_post = ( 'post' === $ctx ) ? $post_id : 0;
		$html .= javan_seda_sticky_thumb( $thumb_post, $cur['img'] );
		$html .= $nav_prev;
		$html .= '<button type="button" class="ab-sp-play" id="ab-sp-play" aria-label="پخش"><i class="icofont-play-alt-2" aria-hidden="true"></i></button>';
		$html .= $nav_next;
		if ( $nav ) {
			$html .= '<span class="ab-sp__no" aria-hidden="true"><b class="ab-sp__now">' . esc_html( javan_seda_sticky_fa_num( $index + 1 ) ) . '</b>/' . esc_html( javan_seda_sticky_fa_num( $count ) ) . '</span>';
		}
		$html .= '<div class="ab-sp__meta">'
			. '<' . $tag . ' class="ab-sp__title"' . ( ( 'a' === $tag ) ? ' href="' . esc_url( $cur['u'] ) . '"' : '' ) . ' data-ab-sp-link>' . esc_html( $cur['t'] ) . '</' . $tag . '>'
			. '<span class="ab-sp__artist">' . esc_html( $cur['a'] ) . '</span>'
			. '<span class="ab-sp__bar">'
			. '<span class="ab-sp-slide"><span class="ab-sp-track" aria-hidden="true"><span class="ab-sp-fill" data-ab-sp-fill></span></span>'
			. '<input type="range" class="ab-sp-range" id="ab-sp-range" min="0" max="1000" value="0" step="1" aria-label="محل پخش" dir="ltr" /></span>'
			. '<span class="ab-sp-time"><b class="ab-sp-cur">۰۰:۰۰</b>/<b class="ab-sp-tot">--:--</b></span>'
			. '</span>'
			. '</div>'
			. '<a class="ab-sp__dl" href="' . esc_url( $cur['s'] ) . '" target="_blank" rel="noreferrer noopener" data-ab-sp-dl><i class="icofont-download" aria-hidden="true"></i><span>دانلود</span></a>'
			. '<button type="button" class="ab-sp-close" id="ab-sp-close" aria-label="بستن نوار پخش"><i class="icofont-close-line" aria-hidden="true"></i></button>'
			. '<audio id="ab-sp-audio" class="ab-sp-audio" preload="none"></audio>'
			. '</div>';
		if ( '' !== $json ) {
			$html .= '<script type="application/json" id="ab-sp-tracks">' . $json . '</script>';
		}
		$html .= '</div>';

		echo $html; // phpcs:ignore -- همه‌چیز پیش از چاپ escape شده
		?>
<script id="ab-sticky-player-js">
(function(){
	var bar = document.getElementById('ab-sticky-player');
	if (!bar) { return; }
	var mode = bar.getAttribute('data-ab-sp-mode') || 'links';
	var a = document.getElementById('audio');
	var own = document.getElementById('ab-sp-audio');
	if (!a || String(a.tagName).toLowerCase() !== 'audio') { a = own; }
	if (!a) { if (bar.parentNode) { bar.parentNode.removeChild(bar); } return; }

	var play = document.getElementById('ab-sp-play'),
		rng  = document.getElementById('ab-sp-range'),
		cur  = bar.querySelector('.ab-sp-cur'),
		tot  = bar.querySelector('.ab-sp-tot'),
		fill = bar.querySelector('[data-ab-sp-fill]'),
		titl = bar.querySelector('[data-ab-sp-link]'),
		arti = bar.querySelector('.ab-sp__artist'),
		dl   = bar.querySelector('[data-ab-sp-dl]'),
		img  = bar.querySelector('[data-ab-sp-img]'),
		non  = bar.querySelector('.ab-sp__now');
	var tracks = [], idx = parseInt(bar.getAttribute('data-ab-sp-index') || '0', 10) || 0;
	var D = '۰۱۲۳۴۵۶۷۸۹';
	function fa(s){ return String(s).replace(/[0-9]/g, function(x){ return D.charAt(parseInt(x, 10)); }); }
	function clock(sec){
		sec = Math.floor(sec || 0);
		if (sec < 0) { sec = 0; }
		var m = Math.floor(sec / 60), s = sec % 60;
		return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
	}
	function on(){ if (document.body.className.indexOf('ab-sticky-on') < 0) { document.body.className = (document.body.className + ' ab-sticky-on').replace(/\s+/g, ' '); } }
	function off(){ document.body.className = document.body.className.replace(/ ?ab-sticky-on/g, ''); }
	function mark(isOn){
		var c = 'ab-sp--playing';
		if (isOn) { if (bar.className.indexOf(c) < 0) { bar.className = (bar.className + ' ' + c).replace(/\s+/g, ' '); } }
		else { bar.className = (' ' + bar.className + ' ').replace(' ' + c + ' ', ' ').replace(/^\s+|\s+$/g, ''); }
	}
	function ico(name){
		if (!play) { return; }
		play.innerHTML = '<i class="' + name + '" aria-hidden="true"></i>';
		play.setAttribute('aria-label', name === 'icofont-pause' ? 'توقف' : 'پخش');
	}
	function paint(){
		var d = (a.duration && isFinite(a.duration)) ? a.duration : 0;
		var p = d ? (a.currentTime / d) : 0;
		if (p < 0) { p = 0; }
		if (p > 1) { p = 1; }
		if (fill) { fill.style.width = (p * 100).toFixed(2) + '%'; }
		if (cur) { cur.innerHTML = fa(clock(a.currentTime)); }
	}
	function sync(){
		var d = (a.duration && isFinite(a.duration)) ? a.duration : 0;
		if (tot) { tot.innerHTML = d ? fa(clock(d)) : '--:--'; }
		if (rng) { rng.disabled = !d; }
		paint();
	}
	function rows(){ return document.querySelectorAll('.play-list-row'); }
	function activeRow(r){
		for (var i = 0; i < r.length; i++) {
			if (r[i].children[2] && String(r[i].children[2].className).indexOf('active-track') > -1) { return i; }
		}
		return -1;
	}
	function rowText(row){
		if (!row || !row.children[2]) { return ''; }
		return String(row.children[2].innerText || row.children[2].textContent || '').replace(/^\s+|\s+$/g, '');
	}
	function fromRow(row, i){
		var t = rowText(row);
		if (titl && t) { titl.innerHTML = t; }
		if (non) { non.innerHTML = fa(i + 1); }
		if (dl && row && row.querySelector) {
			var l = row.querySelector('.download_box a');
			if (l && l.getAttribute('href')) { dl.setAttribute('href', l.getAttribute('href')); }
			else if (a.currentSrc) { dl.setAttribute('href', a.currentSrc); }
		}
	}
	function mirror(){
		if (mode !== 'album') { return; }
		var r = rows();
		if (!r.length) { return; }
		var i = activeRow(r);
		if (i > -1) { idx = i; fromRow(r[i], i); }
	}
	function hrefs(i){
		if (mode === 'album' || !tracks.length) { return; }
		var last = tracks.length - 1;
		var bp = bar.querySelector('[data-ab-sp-go="prev"]'), bn = bar.querySelector('[data-ab-sp-go="next"]');
		var p = (i > 0) ? tracks[i - 1] : tracks[last];
		var n = (i < last) ? tracks[i + 1] : tracks[0];
		if (bp && p && p.u && String(bp.tagName).toLowerCase() === 'a') { bp.setAttribute('href', p.u); }
		if (bn && n && n.u && String(bn.tagName).toLowerCase() === 'a') { bn.setAttribute('href', n.u); }
	}
	function load(t){
		if (!t || !t.s) { return false; }
		try { a.setAttribute('src', t.s); a.load(); } catch (e) { return false; }
		if (titl) {
			titl.innerHTML = t.t ? t.t : '—';
			if (t.u && String(titl.tagName).toLowerCase() === 'a') { titl.setAttribute('href', t.u); }
		}
		if (arti) { arti.innerHTML = t.a ? t.a : ''; }
		if (dl) { dl.setAttribute('href', t.s); }
		if (img && t.img) { img.setAttribute('src', t.img); }
		if (non) { non.innerHTML = fa(idx + 1); }
		var p = a.play();
		if (p && p['catch']) { p['catch'](function(){}); }
		return true;
	}
	function go(dir){
		if (mode === 'album') {
			var r = rows();
			if (!r.length) { return false; }
			var i = activeRow(r);
			if (i < 0) { i = idx; }
			var n = i + dir;
			if (n < 0) { n = r.length - 1; }
			if (n >= r.length) { n = 0; }
			idx = n;
			var cell = r[n];
			var hit = cell.querySelector ? (cell.querySelector('.playlist-track') || cell.querySelector('.small-toggle-btn')) : null;
			if (!hit) { return false; }
			hit.click();
			fromRow(cell, n);
			return true;
		}
		if (!tracks.length) { return false; }
		var last = tracks.length - 1;
		var j = idx + dir;
		if (j < 0) { j = last; }
		if (j > last) { j = 0; }
		idx = j;
		if (!load(tracks[j])) { return false; }
		hrefs(j);
		return true;
	}
	var sEl = document.getElementById('ab-sp-tracks');
	if (sEl && sEl.textContent) {
		try { tracks = JSON.parse(sEl.textContent) || []; } catch (e3) { tracks = []; }
	}
	a.addEventListener('play', function(){ ico('icofont-pause'); on(); mark(true); sync(); mirror(); });
	a.addEventListener('pause', function(){ ico('icofont-play-alt-2'); mark(false); });
	a.addEventListener('ended', function(){
		ico('icofont-play-alt-2'); mark(false);
		if (rng) { rng.value = 0; }
		if (fill) { fill.style.width = '0%'; }
	});
	a.addEventListener('loadedmetadata', sync);
	a.addEventListener('durationchange', sync);
	a.addEventListener('timeupdate', function(){
		var d = (a.duration && isFinite(a.duration)) ? a.duration : 0;
		if (rng && document.activeElement !== rng) { rng.value = d ? Math.round(a.currentTime / d * 1000) : 0; }
		paint();
	});
	if (rng) {
		rng.addEventListener('input', function(){
			var d = (a.duration && isFinite(a.duration)) ? a.duration : 0;
			if (d) { try { a.currentTime = d * (parseInt(rng.value, 10) / 1000); } catch (e) {} }
			paint();
		});
	}
	if (play) {
		play.addEventListener('click', function(){
			if (a.paused) { var p = a.play(); if (p && p['catch']) { p['catch'](function(){}); } } else { a.pause(); }
		});
	}
	var nodes = bar.querySelectorAll('[data-ab-sp-go]');
	for (var i = 0; i < nodes.length; i++) {
		nodes[i].addEventListener('click', function(e){
			var dir = (this.getAttribute('data-ab-sp-go') === 'next') ? 1 : -1;
			if (go(dir)) { if (e && e.preventDefault) { e.preventDefault(); } }
		});
	}
	on();
	var x = document.getElementById('ab-sp-close');
	if (x) {
		x.addEventListener('click', function(){
			try { a.pause(); window.sessionStorage.setItem('ab_sp_off', '1'); } catch (e) {}
			if (bar.parentNode) { bar.parentNode.removeChild(bar); }
			off();
		});
		if (window.sessionStorage && window.sessionStorage.getItem('ab_sp_off') === '1') {
			if (bar.parentNode) { bar.parentNode.removeChild(bar); }
			off();
			return;
		}
	}
	ico(a.paused ? 'icofont-play-alt-2' : 'icofont-pause');
	mark(!a.paused);
	hrefs(idx);
	mirror();
})();
</script>
<?php
	}
}

if ( function_exists( 'add_action' ) && function_exists( 'javan_seda_sticky_player' ) ) {
	add_action( 'wp_footer', 'javan_seda_sticky_player', 30 );
}
