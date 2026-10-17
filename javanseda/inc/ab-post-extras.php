<?php
/**
 * ab-post-extras.php — دو افزودۀ کوچک روی صفحۀ پست/آهنگ (نسخۀ 3.2.0 · ساخته‌شدۀ روی 3.1.4)
 * -----------------------------------------------------------------------------------------
 *  ۱) javan_seda_social_box() : باکس «شبکه‌های اجتماعی» (تلگرام · اینستاگرام · بله · روبیکا)
 *     که در single.php دقیقاً بالای لینک‌های دانلود چاپ می‌شود. لینک‌ها و متنِ دکمه‌ها از
 *     تنظیمات قالب → بخش «شبکه‌های اجتماعی» می‌آیند. اگر هیچ لینکی پر نباشد، باکس اصلاً
 *     رندر نمی‌شود (جای خالی نمی‌ماند). با گزینهٔ «باکس شبکه‌های اجتماعی در صفحهٔ پست» هم
 *     می‌شود روشن/خاموشش کرد (کلید fixbtn).
 *  ۲) javan_seda_sticky_player() : نوارِ چسبانِ پخش در پایین صفحه که همان عنصر <audio>
 *     خودِ قالب را کنترل می‌کند (پخش/توقف، میلهٔ پیشرفت، زمان، بستن). پلیرِ قالب سرِ جایش
 *     می‌ماند؛ این فقط یک کنترلِ دوم است برای وقتی کاربر اسکرول می‌کند.
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
 * ۲) نوارِ چسبانِ پخش (mini-player) — پایین صفحه، همان <audio> قالب
 * ---------------------------------------------------------------------- */
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

if ( ! function_exists( 'javan_seda_sticky_player' ) ) {
	function javan_seda_sticky_player() {
		if ( ! javan_seda_x_on() || ! JAVANSEDA_STICKY_PLAYER ) {
			return;
		}
		if ( ! function_exists( 'is_singular' ) || ! is_singular( 'post' ) ) {
			return;
		}
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return;
		}
		$post_id = (int) get_the_ID();
		$src     = javan_seda_sticky_player_src( $post_id );
		if ( ! $post_id || '' === $src ) {
			return; /* این نوشته پخش آنلاین ندارد ⇒ نوار تهی چاپ نکنیم */
		}
		$song   = javan_seda_x_field( 'song', $post_id );
		$artist = javan_seda_x_field( 'artist', $post_id );
		if ( '' === $song ) {
			$song = (string) get_the_title( $post_id );
		}
		$thumb = '';
		if ( function_exists( 'get_the_post_thumbnail' ) && has_post_thumbnail( $post_id ) ) {
			$thumb = get_the_post_thumbnail( $post_id, 'thumb3', array( 'alt' => wp_strip_all_tags( get_the_title( $post_id ) ), 'loading' => 'lazy' ) );
		}
		if ( '' === $thumb ) {
			$thumb = '<span class="ab-sp-thumb ab-sp-thumb--none" aria-hidden="true"><i class="icofont-music-note"></i></span>';
		} else {
			$thumb = '<span class="ab-sp-thumb">' . $thumb . '</span>';
		}
		echo '<div id="ab-sticky-player" class="ab-sticky" role="region" aria-label="پخش آهنگ">'
			. '<div class="ab-sp__in">'
			. $thumb
			. '<button type="button" class="ab-sp-play" id="ab-sp-play" aria-label="پخش"><i class="icofont-play-alt-2" aria-hidden="true"></i></button>'
			. '<div class="ab-sp__meta">'
			. '<span class="ab-sp__title">' . esc_html( $song ) . '</span>'
			. ( '' !== $artist ? '<span class="ab-sp__artist">' . esc_html( $artist ) . '</span>' : '' )
			. '<span class="ab-sp__bar"><input type="range" class="ab-sp-range" id="ab-sp-range" min="0" max="1000" value="0" step="1" aria-label="محل پخش" dir="ltr" />'
			. '<span class="ab-sp-time"><b class="ab-sp-cur">۰۰:۰۰</b> / <b class="ab-sp-tot">--:--</b></span></span>'
			. '</div>'
			. '<a class="ab-sp__dl" href="' . esc_url( $src ) . '" target="_blank" rel="noreferrer noopener"><i class="icofont-download" aria-hidden="true"></i><span>دانلود</span></a>'
			. '<button type="button" class="ab-sp-close" id="ab-sp-close" aria-label="بستن نوار پخش"><i class="icofont-close-line" aria-hidden="true"></i></button>'
			. '</div>'
			. '</div>' . "\n";
		?>
<script id="ab-sticky-player-js">
(function(){
	var bar = document.getElementById('ab-sticky-player');
	if (!bar) { return; }
	var a = document.getElementById('audio');
	if (!a || String(a.tagName).toLowerCase() !== 'audio') { a = document.querySelector('audio'); }
	if (!a) { if (bar.parentNode) { bar.parentNode.removeChild(bar); } return; }
	var play = document.getElementById('ab-sp-play'),
		rng  = document.getElementById('ab-sp-range'),
		cur = bar.querySelector('.ab-sp-cur'),
		tot = bar.querySelector('.ab-sp-tot');
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
	function ico(name){
		if (!play) { return; }
		play.innerHTML = '<i class="' + name + '" aria-hidden="true"></i>';
		play.setAttribute('aria-label', name === 'icofont-pause' ? 'توقف' : 'پخش');
	}
	function sync(){
		var d = (a.duration && isFinite(a.duration)) ? a.duration : 0;
		if (tot) { tot.innerHTML = d ? fa(clock(d)) : '--:--'; }
		if (rng) { rng.disabled = !d; }
	}
	a.addEventListener('play', function(){ ico('icofont-pause'); on(); sync(); });
	a.addEventListener('pause', function(){ ico('icofont-play-alt-2'); });
	a.addEventListener('ended', function(){ ico('icofont-play-alt-2'); if (rng) { rng.value = 0; } });
	a.addEventListener('loadedmetadata', sync);
	a.addEventListener('durationchange', sync);
	a.addEventListener('timeupdate', function(){
		var d = (a.duration && isFinite(a.duration)) ? a.duration : 0;
		if (rng && document.activeElement !== rng) { rng.value = d ? Math.round(a.currentTime / d * 1000) : 0; }
		if (cur) { cur.innerHTML = fa(clock(a.currentTime)); }
	});
	if (rng) {
		rng.addEventListener('input', function(){
			var d = (a.duration && isFinite(a.duration)) ? a.duration : 0;
			if (d) { try { a.currentTime = d * (parseInt(rng.value, 10) / 1000); } catch (e) {} }
		});
	}
	if (play) {
		play.addEventListener('click', function(){
			if (a.paused) { var p = a.play(); if (p && p.catch) { p.catch(function(){}); } } else { a.pause(); }
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
		}
	}
	ico(a.paused ? 'icofont-play-alt-2' : 'icofont-pause');
})();
</script>
<?php
	}
}

if ( function_exists( 'add_action' ) && function_exists( 'javan_seda_sticky_player' ) ) {
	add_action( 'wp_footer', 'javan_seda_sticky_player', 30 );
}
