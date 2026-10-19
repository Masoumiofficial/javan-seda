<?php
/**
 * «هنرمند» — نامکِ یکتا برای تاکسونومی خواننده‌ها (singer → artist)
 * ---------------------------------------------------------------------------
 * چرا این فایل هست؟
 *   قالب مادر تاکسونومی `singer` را ثبت می‌کند (رجیستر اصلی داخل functions.php
 *   رمزنگاری‌شده است)، ولی بقیۀ نام‌ها در همین پروژه «artist» است: فیلد ACF
 *   `artist`، کلاس‌های `ab-artist__*`، و فایل `artist-template.php`. این دوگانی
 *   منبع باگ است. این لایه نامِ فعال را به `artist` می‌برد و کاری می‌کند که
 *   هیچ‌چیز در دیتابیس یا URL ها بی‌صاحب نماند.
 *
 * چه کار می‌کند (و چه کار نمی‌کند):
 *   ۱) مهاجرت: فقط یک ستون عوض می‌شود — `wp_term_taxonomy.taxonomy` مقدار
 *      'singer' را به 'artist' تغییر می‌دهد. term_id ها، رابطه‌ها
 *      (wp_term_relationships)، termmeta، شمارش‌ها و اسلاگ‌ها دست نمی‌خورند ⇒
 *      لینک‌های داخلی و لینک‌های منو سالم می‌مانند. با تنظیم «بازگشت» همان
 *      UPDATE برعکس اجرا می‌شود؛ نیاز به SQL دستی نیست.
 *   ۲) رجیستر: اگر تاکسونومیِ مادر ثبت شده باشد، آرگومان‌های واقعیِ خودش را
 *      برمی‌داریم (public/show_ui/show_in_rest/query_var/hierarchical/…) و فقط
 *      نامک + برچسب + rewrite را عوض می‌کنیم. اگر ثبت نشده باشد، خودمان ثبت
 *      می‌کنیم (حالتی که قبلاً با JAVANSEDA_SINGER_FIX دستی انجام می‌شد).
 *   ۳) سازگاری: نامک قدیمی (`singer`) به‌صورت یک تاکسونومیِ **پنهان و بدون
 *      آدرس** ثبت می‌ماند؛ پس اگر کد رمزنگاری‌شدۀ مادر یا افزونه‌ای
 *      `get_the_terms( $id, 'singer' )` را بخواند، آرایهٔ خالی می‌گیرد نه
 *      خطای fatal.
 *   ۴) آدرس قدیمی: `/singer/<slug>` روی `/artist/<slug>` ریدایرکت ۳۰۱ می‌خورد
 *      (صفحه‌بندی هم پشتِ همان آدرس می‌نشیند).
 *   ۵) هیچ چیزی در این فایل نوشته‌های جدید نمی‌سازد، برچسب/متا را تغییر نمی‌
 *      دهد، و منطق بازدید (views/kksr) را نمی‌بیند.
 *
 * کنترل در تنظیمات: «مظهر ← تنظیمات پوسته ← کنترل رابط ← هنرمند»
 *   ab_artist_tax   (on-off) = artist فعال باشد (خاموش ⇒ همان singer قبلی)
 *   ab_artist_slug  (text)   = نامک آدرس، پیش‌فرض artist
 * خاموش‌کردن کاملِ این لایه (بدون مهاجرت): در wp-config.php
 *   define( 'JAVANSEDA_ARTIST_TAX', false );
 *
 * @package javanseda
 * @since   3.4.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'javan_seda_artist_tax_enabled' ) ) {
	/** آیا نامک artist فعال است؟ (define → تنظیمات پوسته → پیش‌فرض on) */
	function javan_seda_artist_tax_enabled() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}
		if ( defined( 'JAVANSEDA_ARTIST_TAX' ) ) {
			$on = (bool) JAVANSEDA_ARTIST_TAX;
		} else {
			$opt = function_exists( 'ot_get_option' ) ? ot_get_option( 'ab_artist_tax', 'on' ) : 'on';
			$on  = ( 'off' !== $opt );
		}
		$cache = $on;
		return $cache;
	}
}

if ( ! function_exists( 'javan_seda_artist_tax' ) ) {
	/** نامکِ فعالِ تاکسونومی هنرمند: 'artist' یا 'singer' */
	function javan_seda_artist_tax() {
		static $key = null;
		if ( null !== $key ) {
			return $key;
		}
		$key = javan_seda_artist_tax_enabled() ? 'artist' : 'singer';
		/** با فیلتر هم می‌شود بازنویسی کرد: add_filter( 'javan_seda_artist_tax', ... ) */
		if ( has_filter( 'javan_seda_artist_tax' ) ) {
			$filtered = sanitize_key( (string) apply_filters( 'javan_seda_artist_tax', $key ) );
			if ( in_array( $filtered, array( 'artist', 'singer' ), true ) ) {
				$key = $filtered;
			}
		}
		return $key;
	}
}

if ( ! function_exists( 'javan_seda_artist_tax_other' ) ) {
	/** نامکی که در این لحظه کنار گذاشته شده (برای لایۀ سازگاری و مهاجرت) */
	function javan_seda_artist_tax_other( $tax = '' ) {
		$tax = ( '' === $tax ) ? javan_seda_artist_tax() : (string) $tax;
		return ( 'artist' === $tax ) ? 'singer' : 'artist';
	}
}

if ( ! function_exists( 'javan_seda_artist_slug' ) ) {
	/** نامک آدرس (rewrite slug) — از تنظیمات پوسته */
	function javan_seda_artist_slug() {
		static $slug = null;
		if ( null !== $slug ) {
			return $slug;
		}
		$raw = defined( 'JAVANSEDA_ARTIST_SLUG' ) ? JAVANSEDA_ARTIST_SLUG : '';
		if ( '' === $raw && function_exists( 'ot_get_option' ) ) {
			$raw = ot_get_option( 'ab_artist_slug', 'artist' );
		}
		$raw  = (string) $raw;
		$slug = ( '' === trim( $raw ) ) ? 'artist' : trim( $raw );
		// نامک نباید با اسلاگِ صفحه‌ها قاطی شود و نباید خالی/عدد/اسلش باشد
		$slug = trim( sanitize_title( $slug ), '-/' );
		if ( '' === $slug ) {
			$slug = 'artist';
		}
		return $slug;
	}
}

if ( ! function_exists( 'javan_seda_artist_term_link' ) ) {
	/**
	 * لینک ترمِ هنرمند — جایگزین آدرس‌های دست‌نویسِ /singer/<slug>
	 * اگر ترمی نبود، رشتهٔ خالی می‌دهد (چیزی نمی‌سازد و ۴۰۴ نمی‌سازد).
	 */
	function javan_seda_artist_term_link( $term ) {
		if ( ! $term ) {
			return '';
		}
		if ( ! is_object( $term ) ) {
			$term = get_term_by( 'slug', (string) $term, javan_seda_artist_tax() );
		}
		if ( ! $term || is_wp_error( $term ) ) {
			return '';
		}
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) && ! empty( $link ) ) {
			return $link;
		}
		/* لینک نداشت (مثلاً قوانین پیوند یکتا هنوز بازسازی نشده‌اند) ⇒ همان آدرسِ
		   استاندارد را از نامکِ آرشیو می‌سازیم؛ صفحۀ خالی/۴۰۴ نمی‌سازیم. */
		if ( ! empty( $term->slug ) ) {
			return home_url( '/' . javan_seda_artist_slug() . '/' . $term->slug . '/' );
		}
		return '';
	}
}

if ( ! function_exists( 'javan_seda_artist_ver' ) ) {
	/** شمارۀ قالب — برای کلیدِ کش؛ با هر آپدیت خودبه‌خود بازمی‌گردد */
	function javan_seda_artist_ver() {
		static $v = null;
		if ( null === $v ) {
			$v = wp_get_theme()->get( 'Version' );
			$v = ( '' === (string) $v ) ? '0' : preg_replace( '@[^A-Za-z0-9._-]@', '-', (string) $v );
		}
		return $v;
	}
}

if ( ! function_exists( 'javan_seda_artist_url_cache_key' ) ) {
	function javan_seda_artist_url_cache_key() {
		return 'javan_seda_artist_dir_' . javan_seda_artist_ver();
	}
}

if ( ! function_exists( 'javan_seda_artist_flush_url_cache' ) ) {
	/**
	 * با هر تغییرِ اسلاگ/برگه/تنظیم، کشِ آدرسِ فهرست پاک می‌شود.
	 * (باگِ نسخهٔ قبل: کشِ ۱۲ ساعته باعث شد بعد از singer→artists شدنِ اسلاگِ برگه،
	 *  لینکِ «بازگشت به فهرست» در صفحۀ هنرمند همان آدرسِ کهنه را نشان بدهد.)
	 */
	function javan_seda_artist_flush_url_cache() {
		if ( function_exists( 'delete_transient' ) ) {
			delete_transient( javan_seda_artist_url_cache_key() );
			delete_transient( 'javanseda_artist_directory_url' ); // کلیدِ کهنهٔ 3.4.7
			delete_transient( 'javan_seda_artists_index' );
			// (کشِ بازدیدِ هر ترم کلیدِ نسخه‌دار دارد و با save_post پاک می‌شود)
		}
	}
}
foreach ( array( 'save_post', 'deleted_post', 'switch_theme', 'updated_option' ) as $jv_hook ) {
	add_action( $jv_hook, 'javan_seda_artist_flush_url_cache', 50 );
}

/* =========================================================================
   ۰-پ) فیلدهای ACFِ «singer» را به ترمِ «artist» هم وصل می‌کند
   -------------------------------------------------------------------------
   چرا فیلدهای «ویرایش هنرمند» (تصویر، شبکه‌های اجتماعی، …) از پیشخوان غیب شدند؟
   چون گروه فیلدها در ACF با قاعدۀ «Taxonomy is equal to singer» تعریف شده‌اند و
   مهاجرتِ 3.4.7، نامک را به artist عوض کرد ⇒ پایگاه‌داده هیچ چیزی را از دست نداد
   (متا سر جایش است و get_field همان را می‌خواند) ولی ACF دیگر روی صفحۀ «artist»
   قاعده را match نمی‌کند و فرم را نشان نمی‌دهد. این فیلتر، یک قاعدۀ *موازی* با
   نامکِ فعال اضافه می‌کند (قاعدۀ قدیمی هم می‌ماند تا با خاموش‌کردنِ کلید، همان
   فرم برنگردد). برای ACF 5 و 6 همان 'acf/get_field_groups' کار می‌کند.
   ========================================================================= */
if ( ! function_exists( 'javan_seda_artist_acf_rules' ) ) {
	function javan_seda_artist_acf_rules( $groups ) {
		if ( ! is_array( $groups ) ) {
			return $groups;
		}
		$to   = javan_seda_artist_tax();
		$from = javan_seda_artist_tax_other( $to );
		if ( $from === $to ) {
			return $groups;
		}
		foreach ( $groups as $k => $group ) {
			if ( empty( $group['location'] ) || ! is_array( $group['location'] ) ) {
				continue;
			}
			$extra = array();
			foreach ( $group['location'] as $rules ) {
				if ( ! is_array( $rules ) ) {
					continue;
				}
				$copy = $rules;
				$hit  = false;
				foreach ( $rules as $i => $rule ) {
					if ( ! is_array( $rule ) || ! isset( $rule['param'], $rule['value'] ) ) {
						continue;
					}
					if ( in_array( $rule['param'], array( 'taxonomy', 'post_type' ), true ) && (string) $rule['value'] === (string) $from ) {
						$copy[ $i ]['value'] = $to;
						$hit                 = true;
					}
				}
				if ( $hit ) {
					$extra[] = $copy;
				}
			}
			if ( $extra ) {
				$groups[ $k ]['location'] = array_merge( $group['location'], $extra );
			}
		}
		return $groups;
	}
	/* 3.4.10: اگر ACF ساختارِ گروه‌ها را غیرمنتظره فرستاد، فیلتر بی‌تغییر برمی‌گردد تا
	   صفحه‌های پیشخوان/ویرایش بسته نشود. */
	foreach ( array( 'acf/get_field_groups', 'acf/local_fields' ) as $jv_acf_hook ) {
		add_filter( $jv_acf_hook, function ( $groups ) {
			static $jv_busy = false;
			if ( $jv_busy || ! is_array( $groups ) ) {
				return $groups; // محافظِ بازگشتِ بی‌نهایت
			}
			/* خاموش‌کردنِ دستی برای عیب‌یابی (در wp-config.php):
			   define( 'JAVANSEDA_ARTIST_ACF_RULES', false );  یا
			   define( 'JAVANSEDA_TERM_FIELDS', false );  — کل لایۀ فیلدهای ترم */
			if ( defined( 'JAVANSEDA_ARTIST_ACF_RULES' ) && ! JAVANSEDA_ARTIST_ACF_RULES ) {
				return $groups;
			}
			if ( defined( 'JAVANSEDA_TERM_FIELDS' ) && ! JAVANSEDA_TERM_FIELDS ) {
				return $groups;
			}
			$jv_busy = true;
			if ( function_exists( 'javan_seda_safe' ) ) {
				$jv_out = javan_seda_safe( 'javan_seda_artist_acf_rules', array( $groups ), $groups );
			} else {
				$jv_out = javan_seda_artist_acf_rules( $groups );
			}
			$jv_busy = false;
			return is_array( $jv_out ) ? $jv_out : $groups;
		}, 20 );
	}
}

/* =========================================================================
   ۰-ب) شبکه‌های اجتماعیِ هنرمند (کلیدِ متا → شبکه) — یک‌جا، برای همهٔ قالب‌ها
   ========================================================================= */
if ( ! function_exists( 'javan_seda_artist_socials' ) ) {
	function javan_seda_artist_socials() {
		$map = array(
			'afacebook'  => 'facebook',
			'atelegram'  => 'telegram',
			'ainstagram' => 'instagram',
			'atwitter'   => 'twitter',
			'ayoutube'   => 'youtube',
		);
		return (array) apply_filters( 'javan_seda_artist_socials', $map );
	}
}

/* =========================================================================
   ۰-ج) مجموعِ بازدیدِ آثارِ یک هنرمند — فقط خواندنی
   -------------------------------------------------------------------------
   دقیقاً همان متای «views» که در کارت‌های صفحۀ اصلی و بلوک «پربازدیدترین‌ها»
   به کار می‌رود را جمع می‌زند. هیچ نوشتنی ندارد و به شمارندۀ بازدید/رأی
   (views / kksr) دست نمی‌زند. نتیجه ۶ ساعت کش می‌شود و با ذخیرۀ همان
   نوشته، بی‌درنگ برای ترم‌هایش پاک می‌شود.
   ========================================================================= */
if ( ! function_exists( 'javan_seda_artist_views_cache_key' ) ) {
	function javan_seda_artist_views_cache_key( $term_id ) {
		return 'javan_seda_views_' . (int) $term_id . '_v' . javan_seda_artist_ver();
	}
}

if ( ! function_exists( 'javan_seda_artist_views_flush_for' ) ) {
	/** هنگام save_post: کشِ بازدیدِ ترم‌های همان نوشته پاک می‌شود */
	function javan_seda_artist_views_flush_for( $post_id ) {
		$terms = get_the_terms( (int) $post_id, javan_seda_artist_tax() );
		if ( ! is_array( $terms ) ) {
			return;
		}
		foreach ( $terms as $term ) {
			if ( ! empty( $term->term_id ) ) {
				delete_transient( javan_seda_artist_views_cache_key( (int) $term->term_id ) );
			}
		}
	}
	add_action( 'save_post', 'javan_seda_artist_views_flush_for', 20, 1 );
	add_action( 'delete_post', 'javan_seda_artist_views_flush_for', 20, 1 );
}

if ( ! function_exists( 'javan_seda_artist_views_total' ) ) {
	/** دوربستِ امن (3.4.10) */
	function javan_seda_artist_views_total( $term = 0 ) {
		if ( ! function_exists( 'javan_seda_safe' ) ) {
			return javan_seda_artist_views_total_impl( $term );
		}
		return javan_seda_safe( 'javan_seda_artist_views_total_impl', array( $term ), 0 );
	}
}
if ( ! function_exists( 'javan_seda_artist_views_total_impl' ) ) {
	/**
	 * مجموعِ بازدیدِ همهٔ آثارِ یک هنرمند.
	 * $term: WP_Term | term_id.  آرایۀ خالی/ترمِ نامعتبر ⇒ ۰
	 */
	function javan_seda_artist_views_total_impl( $term = 0 ) {
		static $local = array();

		$term_id = 0;
		if ( $term instanceof WP_Term ) {
			$term_id = (int) $term->term_id;
		} elseif ( is_numeric( $term ) ) {
			$term_id = (int) $term;
		}
		if ( ! $term_id ) {
			return 0;
		}
		if ( isset( $local[ $term_id ] ) ) {
			return $local[ $term_id ];
		}

		$max = (int) apply_filters( 'javan_seda_artist_views_max_posts', 250 );
		$key = javan_seda_artist_views_cache_key( $term_id );
		$val = ( function_exists( 'get_transient' ) && apply_filters( 'javan_seda_artist_views_cache', true ) ) ? get_transient( $key ) : false;

		if ( false === $val || '' === $val ) {
			$ids = get_posts(
				array(
					'post_type'           => 'any',
					'post_status'         => 'publish',
					'fields'              => 'ids',
					'numberposts'         => $max,
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
					'suppress_filters'    => true,
					'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => javan_seda_artist_tax(),
							'field'    => 'term_id',
							'terms'    => array( $term_id ),
						),
					),
				)
			);
			$val = 0;
			if ( is_array( $ids ) && $ids && function_exists( 'update_postmeta_cache' ) ) {
				update_postmeta_cache( $ids );
				foreach ( $ids as $id ) {
					$val += (int) get_post_meta( (int) $id, 'views', true );
				}
			}
			if ( function_exists( 'set_transient' ) ) {
				set_transient( $key, (int) $val, 6 * HOUR_IN_SECONDS );
			}
		}

		$local[ $term_id ] = (int) $val;
		return $local[ $term_id ];
	}
}

if ( ! function_exists( 'javan_seda_fa_digits' ) ) {
	/** رقم‌های لاتین → فارسی، روی هر رشته (بی‌نیاز از mbstring) */
	function javan_seda_fa_digits( $text ) {
		/* 3.4.11 — رفعِ رقم‌های به‌هم‌ریخته روی صفحۀ هنرمند:
		   ۱) جایگزین‌ها «\u{06F0}» داخل نقل‌قولِ تکی بودند و در هیچ نسخۀ PHP به
		      کاراکتر تبدیل نمی‌شوند، پس همان متنِ خام چاپ می‌شد.
		   ۲) str_replace ده بار پشتِ هم اجرا می‌شد و رقم‌های داخلِ متنِ تازه
		      جابه‌جا شده را هم دوباره عوض می‌کرد (نتیجه: «\u{0\u{06F6}F4}…»).
		   حالا خودِ رقمِ فارسی در فایل است و strtr تک‌گذر است. */
		return strtr(
			(string) $text,
			array(
				'0' => '۰',
				'1' => '۱',
				'2' => '۲',
				'3' => '۳',
				'4' => '۴',
				'5' => '۵',
				'6' => '۶',
				'7' => '۷',
				'8' => '۸',
				'9' => '۹',
			)
		);
	}
}

if ( ! function_exists( 'javan_seda_artist_views_html' ) ) {
	/** خطِ «مجموع بازدید» — اگر صفر باشد چیزی چاپ نمی‌شود (نه صفرِ الکی) */
	function javan_seda_artist_views_html( $term = 0, $class = 'ab-artist-views' ) {
		$n = (int) javan_seda_artist_views_total( $term );
		if ( $n <= 0 ) {
			return '';
		}
		$text = sprintf( '%s بازدید از آثار این هنرمند', number_format_i18n( $n ) );
		$text = javan_seda_fa_digits( $text );
		return '<span class="' . esc_attr( $class ) . '"><i class="icofont-eye-alt" aria-hidden="true"></i> ' . esc_html( $text ) . '</span>';
	}
}

/* =========================================================================
   ۰-د) ردیفِ «هنرمندانِ صفحۀ اصلی» — یک منبع برای عکس، لینک و ترتیب
   -------------------------------------------------------------------------
   پیش از این، عکس و لینک این ردیف در «تنظیمات پوسته ← هنرمندانِ صفحۀ اصلی»
   دستی پر می‌شد (siing_t) و هیچ ربطی به خودِ هنرمند نداشت. از این نسخه:
     · هر ردیف با «نام هنرمند» (siing_n) یا با آدرسِ صفحۀ هنرمند (siing_u)
       به ترم وصل می‌شود؛ عکسِ ترم و لینکِ ترم خودش جای خالی را پر می‌کند.
     · گزینه‌های «ab_home_artists_*» ردیف را از خودِ ترم‌ها می‌سازد
       (ترتیب: پربازدیدترین / پرکارترین / تازه‌ترین / الفبایی).
     · «ثابت در بالا» دو جا دارد: تیکِ «ab_home_pin» در صفحۀ ویرایش هنرمند،
       و لیستِ اسامیِ ab_home_artists_pin در تنظیمات.
   ========================================================================= */
if ( ! function_exists( 'javan_seda_lc' ) ) {
	/**
	 * کوچک‌کردنِ حروف با حفظِ فارسی/عربی. اگر افزونۀ mbstring روی هاست نصب نباشد
	 * strtolower برای ASCII کافی است و حروفِ فارسی دست‌نخورده می‌مانند.
	 */
	function javan_seda_lc( $text ) {
		$text = (string) $text;
		if ( function_exists( 'mb_strtolower' ) ) {
			return mb_strtolower( $text, 'UTF-8' );
		}
		return strtolower( $text );
	}
}
if ( ! function_exists( 'javan_seda_artist_normalize_name' ) ) {
	function javan_seda_artist_normalize_name( $text ) {
		$text = javan_seda_lc( trim( (string) $text ) );
		/* 3.4.11: بایت‌های UTF-8، نه «u{…» — آن شکلِ escape فقط در PHP 7.0+ معنا
		   می‌شود و روی PHP 5.6 رشتهٔ خامِ «\u{200c}» جایگزین می‌شد؛ یعنی نیم‌فاصله هیچ‌وقت
		   از نامِ هنرمند پاک نمی‌شد و تطبیقِ نام در /artist/ سخت‌تر انجام می‌شد.
		   نیم‌فاصله E2 80 8C · ZWSP E2 80 8B · LRM E2 80 8E */
		$text = str_replace( array( "\xE2\x80x8C", "\xE2\x80x8B", "\xE2\x80x8E" ), ' ', $text );
		$text = preg_replace( '/[\x{064B}-\x{0658}\x{0670}]/u', '', $text );
		$text = str_replace( array( 'ي', 'ك', '­' ), array( 'ی', 'ک', '' ), $text );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( (string) $text );
	}
}

if ( ! function_exists( 'javan_seda_artist_home_settings' ) ) {
	/** دوربستِ امن (3.4.10) */
	function javan_seda_artist_home_settings(  ) {
		if ( ! function_exists( 'javan_seda_safe' ) ) {
			return javan_seda_artist_home_settings_impl(  );
		}
		return javan_seda_safe( 'javan_seda_artist_home_settings_impl', array(  ), array() );
	}
}
if ( ! function_exists( 'javan_seda_artist_home_settings_impl' ) ) {
	function javan_seda_artist_home_settings_impl() {
		$has = function_exists( 'ot_get_option' );
		$set = array(
			'auto'  => ( 'off' !== (string) ( $has ? ot_get_option( 'ab_home_artists_auto', 'off' ) : 'off' ) ),
			'limit' => (int) ( $has ? ot_get_option( 'ab_home_artists_n', 12 ) : 12 ),
			'sort'  => (string) ( $has ? ot_get_option( 'ab_home_artists_sort', 'views' ) : 'views' ),
			'pin'   => (string) ( $has ? ot_get_option( 'ab_home_artists_pin', '' ) : '' ),
			'views' => ( 'off' !== (string) ( $has ? ot_get_option( 'ab_home_artists_views', 'on' ) : 'on' ) ),
		);
		if ( ! in_array( $set['sort'], array( 'views', 'count', 'new', 'name' ), true ) ) {
			$set['sort'] = 'views';
		}
		$set['limit']  = max( 1, min( 30, (int) $set['limit'] ) );
		$set['pins'] = array_values( array_filter( array_map( 'trim', preg_split( '/[,،\r\n|]+/u', (string) $set['pin'] ) ) ) );
		return (array) apply_filters( 'javan_seda_artist_home_settings', $set );
	}
}

if ( ! function_exists( 'javan_seda_artist_term_from' ) ) {
	/** دوربستِ امن (3.4.10) */
	function javan_seda_artist_term_from( $needle ) {
		if ( ! function_exists( 'javan_seda_safe' ) ) {
			return javan_seda_artist_term_from_impl( $needle );
		}
		return javan_seda_safe( 'javan_seda_artist_term_from_impl', array( $needle ), null );
	}
}
if ( ! function_exists( 'javan_seda_artist_term_from_impl' ) ) {
	/** از «نام» یا «آدرس» → ترمِ هنرمند (یا null) */
	function javan_seda_artist_term_from_impl( $needle ) {
		$needle = trim( (string) $needle );
		if ( '' === $needle ) {
			return null;
		}
		$tax = javan_seda_artist_tax();

		// آدرس؟ اسلاگِ آخرِ مسیر را برمی‌داریم: /artist/mohsen-yeganeh/ → mohsen-yeganeh
		if ( preg_match( '#^https?://#i', $needle ) ) {
			$path = trim( (string) wp_parse_url( $needle, PHP_URL_PATH ), '/' );
			if ( '' !== $path ) {
				$slug = (string) preg_replace( '#^.*(/' . preg_quote( javan_seda_artist_slug(), '#' ) . '/)#u', '', $path );
				$slug = trim( (string) preg_replace( '@/page(/\d+)?$@', '', $slug ), '/' );
				if ( '' === $slug ) {
					$parts = explode( '/', $path );
					$slug  = (string) end( $parts );
				}
				if ( '' !== $slug ) {
					$term = get_term_by( 'slug', sanitize_title( $slug ), $tax );
					if ( $term instanceof WP_Term ) {
						return $term;
					}
				}
			}
		}

		$term = get_term_by( 'name', $needle, $tax );
		if ( $term instanceof WP_Term ) {
			return $term;
		}
		$term = get_term_by( 'slug', sanitize_title( $needle ), $tax );
		if ( $term instanceof WP_Term ) {
			return $term;
		}

		// تطبیقِ نرمال‌شده (فاصله، نیم‌فاصله، «هـ/ه») روی همهٔ ترم‌ها
		$all = get_terms(
			array(
				'taxonomy'   => $tax,
				'hide_empty' => false,
			)
		);
		if ( is_array( $all ) ) {
			$want = javan_seda_artist_normalize_name( $needle );
			foreach ( $all as $t ) {
				if ( javan_seda_artist_normalize_name( $t->name ) === $want ) {
					return $t;
				}
			}
		}
		return null;
	}
}

if ( ! function_exists( 'javan_seda_artist_home_row_for' ) ) {
	/** ردیفِ دستیِ مربوطِ به یک ترم (برای آنکه عکسِ ردیفِ خانه، عکسِ هنرمند هم باشد) */
	function javan_seda_artist_home_row_for( $term ) {
		static $map = null;
		if ( null === $map ) {
			$map = array();
			$rows = function_exists( 'ot_get_option' ) ? (array) ot_get_option( 'siing_t', array() ) : array();
			foreach ( $rows as $r ) {
				if ( ! is_array( $r ) ) {
					continue;
				}
				$key = '';
				if ( ! empty( $r['siing_n'] ) ) {
					$key = javan_seda_artist_normalize_name( $r['siing_n'] );
				}
				if ( '' === $key && ! empty( $r['siing_u'] ) ) {
					$t = javan_seda_artist_term_from( $r['siing_u'] );
					if ( $t instanceof WP_Term ) {
						$key = javan_seda_artist_normalize_name( $t->name );
					}
				}
				if ( '' !== $key ) {
					$map[ $key ] = $r;
				}
			}
		}
		$name = '';
		if ( $term instanceof WP_Term ) {
			$name = javan_seda_artist_normalize_name( $term->name );
		}
		if ( '' === $name || ! isset( $map[ $name ] ) ) {
			return array();
		}
		return (array) $map[ $name ];
	}
}

if ( ! function_exists( 'javan_seda_artist_home_rows' ) ) {
	/** دوربستِ امن (3.4.10) */
	function javan_seda_artist_home_rows( $args = array() ) {
		if ( ! function_exists( 'javan_seda_safe' ) ) {
			return javan_seda_artist_home_rows_impl( $args );
		}
		return javan_seda_safe( 'javan_seda_artist_home_rows_impl', array( $args ), array() );
	}
}
if ( ! function_exists( 'javan_seda_artist_home_rows_impl' ) ) {
	/**
	 * ردیف‌های «محبوبترین هنرمندان». خروجی: آرایه‌ای از
	 * array( 'term','name','url','img','views','count','pinned' )
	 *
	 * حالت دستی (siing_t) => همان ردیف‌ها، ولی عکس/لینک از ترم کامل می‌شوند.
	 * حالت خودکار (ab_home_artists_auto) => از خودِ ترم‌ها، با ترتیبِ انتخابی
	 * و ثابت‌هایِ بالا (تیکِ «ab_home_pin» یا لیستِ ab_home_artists_pin).
	 */
	function javan_seda_artist_home_rows_impl( $args = array() ) {
		$args = (array) wp_parse_args(
			$args,
			javan_seda_artist_home_settings()
		);
		$tax   = javan_seda_artist_tax();
		$limit = max( 1, (int) $args['limit'] );
		$rows  = array();
		$seen  = array();

		$push = function ( $row ) use ( &$rows, &$seen, $limit ) {
			if ( ! is_array( $row ) || empty( $row['term'] ) ) {
				return;
			}
			$id = (int) $row['term']->term_id;
			if ( isset( $seen[ $id ] ) || count( $rows ) >= $limit ) {
				return;
			}
			$seen[ $id ] = true;
			$rows[ $id ]  = $row;
		};

		// ۱) ثابت‌ها (تیکِ ترم یا لیستِ اسامی)
		$pins = array();
		foreach ( (array) $args['pins'] as $pin ) {
			$t = javan_seda_artist_term_from( $pin );
			if ( $t instanceof WP_Term ) {
				$pins[ (int) $t->term_id ] = $t;
			}
		}
		$flagged = get_terms(
			array(
				'taxonomy'   => $tax,
				'hide_empty' => true,
				'number'     => 40,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => 'ab_home_pin',
						'value' => 'on',
					),
				),
			)
		);
		if ( is_array( $flagged ) ) {
			foreach ( $flagged as $t ) {
				$pins[ (int) $t->term_id ] = $t;
			}
		}
		foreach ( $pins as $id => $t ) {
			$push( javan_seda_artist_home_row( $t, true, $args ) );
		}

		// ۲) ردیف‌های دستی (در حالت خودکار، فقط اگر به ترمی وصل شوند پذیرفته‌اند)
		if ( empty( $args['auto'] ) ) {
			$manual = function_exists( 'ot_get_option' ) ? (array) ot_get_option( 'siing_t', array() ) : array();
			foreach ( $manual as $r ) {
				if ( ! is_array( $r ) ) {
					continue;
				}
				$needle = ! empty( $r['siing_n'] ) ? $r['siing_n'] : ( ! empty( $r['siing_u'] ) ? $r['siing_u'] : '' );
				$t      = javan_seda_artist_term_from( $needle );
				if ( $t instanceof WP_Term ) {
					$row = javan_seda_artist_home_row( $t, false, $args );
					if ( ! empty( $r['siing_img'] ) ) {
						$row['img'] = (string) $r['siing_img'];
					}
					if ( ! empty( $r['siing_u'] ) ) {
						$row['url'] = (string) $r['siing_u'];
					}
					$push( $row );
				} else {
					// ردیفِ دستیِ بی‌ربط (لینکِ هر جای دیگر) ⇒ همان‌طور که بود چاپ می‌شود
					if ( count( $rows ) < $limit && ! empty( $r['siing_img'] ) ) {
						$rows[ 'm' . count( $rows ) ] = array(
							'term'   => null,
							'name'   => ! empty( $r['siing_n'] ) ? $r['siing_n'] : '',
							'url'    => ! empty( $r['siing_u'] ) ? $r['siing_u'] : '',
							'img'    => (string) $r['siing_img'],
							'views'  => 0,
							'count'  => 0,
							'pinned' => false,
						);
					}
				}
			}
		}

		// ۳) بقیه از خودِ ترم‌ها
		if ( count( $rows ) < $limit ) {
			$jv_sort = (string) $args['sort'];
			$jv_ob   = 'count';
			if ( 'name' === $jv_sort ) {
				$jv_ob = 'name';
			} elseif ( 'new' === $jv_sort ) {
				$jv_ob = 'term_id'; // تازه‌ساخته‌شده‌ها بالاتر
			}
			$terms = get_terms(
				array(
					'taxonomy'   => $tax,
					'hide_empty' => true,
					'number'     => min( 200, $limit * 4 + 24 ),
					'orderby'    => $jv_ob,
					'order'      => ( 'name' === $jv_sort ) ? 'ASC' : 'DESC',
				)
			);
			if ( is_array( $terms ) ) {
				/* «مجموعِ بازدید» برای هر ترم یک کوئری دارد؛ پس اول با «تعدادِ اثر» یک
				   صفِ کوتاه می‌سازیم (۲× تعدادِ لازم) و بعد فقط همان‌ها را با بازدید
				   مرتب می‌کنیم. برای فهرست‌های کوتاه (مثل همین سایت) نتیجه دقیقاً
				   همان «پربازدیدترین» است؛ در فهرست‌های خیلی بلند، هنرمندی که آثارِ
				   کم ولی بازدیدِ نجومی دارد ممکن است از ۲× لازم رد نشود — که با
				   فیلترِ javan_seda_artist_home_pool_size قابل افزایش است. */
				$pool_size = (int) apply_filters( 'javan_seda_artist_home_pool_size', max( $limit * 2, $limit + 6 ), $args );
				$pool      = array();
				foreach ( $terms as $t ) {
					if ( isset( $seen[ (int) $t->term_id ] ) ) {
						continue;
					}
					if ( 'on' === (string) get_term_meta( (int) $t->term_id, 'ab_home_exclude', true ) ) {
						continue;
					}
					$no_views     = $args;
					$no_views['views'] = false;
					$pool[] = javan_seda_artist_home_row( $t, false, $no_views );
					if ( count( $pool ) >= $pool_size ) {
						break;
					}
				}
				if ( 'views' === $jv_sort && ! empty( $args['views'] ) ) {
					foreach ( $pool as $i => $row ) {
						if ( ! empty( $row['term'] ) ) {
							$pool[ $i ]['views'] = (int) javan_seda_artist_views_total( $row['term'] );
						}
					}
					usort( $pool, 'javan_seda_artist_home_cmp_views' );
				}
				foreach ( $pool as $row ) {
					$push( $row );
				}
			}
		}

		$out = array_slice( array_values( $rows ), 0, $limit );
		return (array) apply_filters( 'javan_seda_artist_home_rows', $out, $args );
	}
}

if ( ! function_exists( 'javan_seda_artist_home_row' ) ) {
	function javan_seda_artist_home_row( $term, $pinned = false, $args = array() ) {
		if ( ! $term instanceof WP_Term ) {
			return array();
		}
		$link = javan_seda_artist_term_link( $term );
		$img  = '';
		if ( function_exists( 'javan_seda_term_image' ) ) {
			$image = javan_seda_term_image( $term, 'medium' );
			$img   = ! empty( $image['url'] ) ? (string) $image['url'] : '';
		}
		$views = empty( $args['views'] ) ? 0 : (int) javan_seda_artist_views_total( $term );
		return array(
			'term'   => $term,
			'name'   => (string) $term->name,
			'url'    => ( $link && ! is_wp_error( $link ) ) ? $link : '',
			'img'    => $img,
			'views'  => $views,
			'count'  => (int) $term->count,
			'pinned' => (bool) $pinned,
			'date'   => '',
		);
	}
}

if ( ! function_exists( 'javan_seda_artist_home_cmp_views' ) ) {
	function javan_seda_artist_home_cmp_views( $a, $b ) {
		$a = isset( $a['views'] ) ? (int) $a['views'] : 0;
		$b = isset( $b['views'] ) ? (int) $b['views'] : 0;
		if ( $a === $b ) {
			return 0;
		}
		return ( $a < $b ) ? 1 : -1;
	}
}

if ( ! function_exists( 'javan_seda_artist_default_image' ) ) {
	/**
	 * تصویرِ پیش‌فرضِ هنرمند. از 3.4.8 فایلِ def-artist.webp اولویت دارد و
	 * def-singer.webp فقط به‌عنوانِ فالبک نگه داشته می‌شود (تا نصبِ ناقص، کارت‌ها بی‌تصویر نشوند).
	 */
	function javan_seda_artist_default_image() {
		$dir = get_template_directory();
		foreach ( array( 'def-artist.webp', 'def-singer.webp' ) as $f ) {
			if ( file_exists( $dir . '/images/' . $f ) ) {
				return get_template_directory_uri() . '/images/' . $f;
			}
		}
		return get_template_directory_uri() . '/images/def-singer.webp';
	}
}

if ( ! function_exists( 'javan_seda_artists_index_url' ) ) {
	/**
	 * آدرسِ یک‌جا‌و‌تنهای صفحۀ «همهٔ هنرمندان». ترتیب حل‌کردن:
	 *   ۰) تنظیمات مادر: arti_url (ردیف «صفحه هنرمندان») — اگر پر باشد، حرفِ آخر با شماست
	 *   ۱) برگه با اسلاگ artists → artist → singers → singer (get_page_by_path: ارزان، بدون کش)
	 *   ۲) برگه‌ای که قالبش artist-template.php است (کوئریِ سنگین → کشِ ۱۲ ساعتهٔ نسخه‌دار)
	 *   ۳) آرشیوِ خودِ تاکسونومی، و در پایان /singers/ تا هیچ لینکی مرده نشود
	 */
	function javan_seda_artists_index_url() {
		static $url = null;
		if ( null !== $url ) {
			return $url;
		}

		// ۰)_overrideِ مدیر_سایت
		$opt = function_exists( 'ot_get_option' ) ? (string) ot_get_option( 'arti_url', '' ) : '';
		$opt = trim( $opt );
		if ( '' !== $opt && preg_match( '#^https?://#i', $opt ) ) {
			$url = user_trailingslashit( $opt );
			return $url;
		}

		// ۱) برگه بر پایهٔ اسلاگ
		$slugs = array( 'artists', 'artist', 'singers', 'singer' );
		$slugs = (array) apply_filters( 'javan_seda_artists_index_slugs', $slugs );
		foreach ( $slugs as $s ) {
			$page = get_page_by_path( sanitize_title( (string) $s ), 'OBJECT', 'page' );
			if ( $page ) {
				$link = get_permalink( (int) $page->ID );
				if ( $link && ! is_wp_error( $link ) ) {
					$url = $link;
					return $url;
				}
			}
		}

		// ۲) برگه‌ای که قالبش artist-template.php است (کلیدِ کش با شمارۀ قالب ⇒ با آپدیت معتبر نمی‌ماند)
		$key     = javan_seda_artist_url_cache_key();
		$cache  = ( function_exists( 'get_transient' ) && apply_filters( 'javan_seda_artist_url_cache', true ) ) ? get_transient( $key ) : '';
		if ( is_string( $cache ) && '' !== $cache ) {
			$url = $cache;
			return $url;
		}
		$pages = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'meta_key'         => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => 'artist-template.php', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		if ( $pages && ! empty( $pages[0] ) ) {
			$link = get_permalink( (int) $pages[0] );
			if ( $link && ! is_wp_error( $link ) ) {
				$url = $link;
				if ( function_exists( 'set_transient' ) ) {
					set_transient( $key, $url, 12 * HOUR_IN_SECONDS );
				}
				return $url;
			}
		}

		// ۳) آرشیوِ تاکسونومی
		$tax = get_taxonomy( javan_seda_artist_tax() );
		if ( $tax && ! empty( $tax->rewrite ) && is_array( $tax->rewrite ) && ! empty( $tax->rewrite['slug'] ) ) {
			$url = home_url( '/' . trim( (string) $tax->rewrite['slug'], '/' ) . '/' );
			return $url;
		}
		$url = home_url( '/singers/' );
		return $url;
	}
}
/* -------------------------------------------------------------------------
   ۱) مهاجرتِ ستون taxonomy در wp_term_taxonomy (یک‌باره، نسخه‌دار)
   ------------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_artist_tax_migrate' ) ) {
	function javan_seda_artist_tax_migrate() {
		global $wpdb;
		if ( is_admin() && function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
			return; // در کرانِ برنامه‌ریزی‌شده مهاجرت نمی‌کنیم
		}
		$to   = javan_seda_artist_tax();
		$from = javan_seda_artist_tax_other( $to );
		$slug = javan_seda_artist_slug();

		$ver   = 'javan_seda_artist_tax_ver';
		$stamp = $to . '|' . $from . '|' . $slug . '|1';
		if ( (string) get_option( $ver, '' ) === $stamp ) {
			return; // قبلاً اعمال شده
		}

		$tbl   = $wpdb->term_taxonomy;
		$n_from = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl} WHERE taxonomy = %s", $from ) ); // phpcs:ignore
		$n_to   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl} WHERE taxonomy = %s", $to ) );   // phpcs:ignore

		$moved = false;
		if ( $n_from > 0 ) {
			if ( $n_to > 0 ) {
				// هر دو نامک ردیف دارند: ادغام نمی‌کنیم (ریسکِ از‌دست‌رفتنِ رابطه‌ها).
				// فقط گزارش می‌گذاریم تا مدیر بداند؛ مهاجرت دستی را ما انجام نمی‌دهیم.
				update_option( 'javan_seda_artist_tax_note', 'skip-merge: هر دو تاکسونومی «' . $from . '» و «' . $to . '» ردیف دارند (' . $n_from . ' و ' . $n_to . ').', false );
			} else {
				$ids = $wpdb->get_col( $wpdb->prepare( "SELECT term_id FROM {$tbl} WHERE taxonomy = %s", $from ) ); // phpcs:ignore
				$ok  = (int) $wpdb->query( $wpdb->prepare( "UPDATE {$tbl} SET taxonomy = %s WHERE taxonomy = %s", $to, $from ) ); // phpcs:ignore
				if ( $ok > 0 && is_array( $ids ) && $ids ) {
					// فقط کشِ همان ترم‌ها، نه کل کشِ سایت
					$ids_int = array_map( 'intval', (array) $ids );
					clean_term_cache( $ids_int, $from );
					clean_term_cache( $ids_int, $to );
					if ( function_exists( 'wp_cache_set' ) ) {
						wp_cache_set( 'javan_seda_artist_tax_moved', (int) count( $ids ) );
					}
					$moved = true;
				}
				update_option( 'javan_seda_artist_tax_log', gmdate( 'Y-m-d H:i:s' ) . ' — ' . $ok . ' ردیف: ' . $from . ' → ' . $to, false );
			}
		}

		update_option( $ver, $stamp, false );
		if ( $moved || $n_to > 0 ) {
			// قوانین پیوند یکتا لازم است بازچینی شود (نرم: بدون دست‌زدن به .htaccess)
			wp_schedule_single_event( time() + 3, 'javan_seda_artist_flush' );
		}
	}
}
/* اگر لایه دیر load شده باشد (init رد شده) همان لحظه اجرا می‌شود؛ وگرنه با هوک. */
if ( did_action( 'init' ) >= 1 ) {
	javan_seda_artist_tax_migrate();
} else {
	add_action( 'init', 'javan_seda_artist_tax_migrate', 3 );
}

if ( ! function_exists( 'javan_seda_artist_flush_cb' ) ) {
	function javan_seda_artist_flush_cb() {
		flush_rewrite_rules( false );
	}
}
add_action( 'javan_seda_artist_flush', 'javan_seda_artist_flush_cb' );

/* -------------------------------------------------------------------------
   ۲) ثبتِ نامک فعال، با حفظِ آرگومان‌های واقعیِ مادر
   ------------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_artist_tax_args' ) ) {
	/** آرگومان‌ها را از تاکسونومیِ موجود برمی‌داریم تا تنظیم مادر گم نشود */
	function javan_seda_artist_tax_args( $tax ) {
		$base = get_taxonomy( $tax );
		$args = array();
		if ( $base ) {
			$carry = array(
				'public',
				'publicly_queryable',
				'hierarchical',
				'show_ui',
				'show_in_menu',
				'show_in_nav_menus',
				'show_in_quickedit',
				'show_admin_column',
				'show_in_rest',
				'rest_base',
				'rest_namespace',
				'query_var',
				'sort_order',
				'description',
				'_builtin',
				'meta_box_cb',
				'meta_box_sanitize_cb',
			);
			$vars = get_object_vars( $base );
			foreach ( $carry as $k ) {
				if ( array_key_exists( $k, $vars ) && null !== $vars[ $k ] ) {
					$args[ $k ] = $vars[ $k ];
				}
			}
			// capability های مادر به نامک قدیمی گره خورده بودند؛ بگذاریم وردپرس
			// پیش‌فرضِ درست (manage_categories / edit_posts) را بسازد.
			unset( $args['capabilities'], $args['map_meta_cap'], $args['_builtin'] );
		}
		if ( ! isset( $args['public'] ) ) {
			$args['public'] = true;
		}
		if ( ! isset( $args['hierarchical'] ) ) {
			$args['hierarchical'] = false;
		}
		foreach ( array( 'show_ui', 'show_admin_column', 'show_in_nav_menus', 'publicly_queryable' ) as $k ) {
			if ( ! isset( $args[ $k ] ) ) {
				$args[ $k ] = true;
			}
		}
		if ( ! isset( $args['show_in_rest'] ) ) {
			$args['show_in_rest'] = true;
		}
		if ( ! isset( $args['rest_base'] ) ) {
			$args['rest_base'] = $tax;
		}
		$args['rewrite'] = array(
			'slug'       => javan_seda_artist_slug(),
			'with_front' => false,
		);
		$args['labels']  = array(
			'name'               => 'هنرمندان',
			'singular_name'      => 'هنرمند',
			'search_items'       => 'جست‌وجوی هنرمند',
			'all_items'          => 'همهٔ هنرمندان',
			'edit_item'          => 'ویرایش هنرمند',
			'update_item'        => 'بروزرسانی هنرمند',
			'view_item'          => 'نمایش هنرمند',
			'add_new_item'       => 'افزودن هنرمند تازه',
			'new_item_name'      => 'نام هنرمند تازه',
			'menu_name'          => 'هنرمندان',
			'not_found'          => 'هنرمندی پیدا نشد',
			'not_found_in_trash' => 'در زباله‌دان هنرمندی نیست',
		);
		return $args;
	}
}

if ( ! function_exists( 'javan_seda_artist_tax_register' ) ) {
	function javan_seda_artist_tax_register() {
		$to   = javan_seda_artist_tax();
		$from = javan_seda_artist_tax_other( $to );

		// اگر پست‌تایپی هم‌نام وجود دارد، ثبتِ تاکسونومیِ هم‌نام rewrite را می‌شکند
		// (همان درسی که از JAVANSEDA_SINGER_FIX گرفتیم).
		$blocked = post_type_exists( $to );

		/* اگر مادر نامک قدیمی را عمومی و با آدرس ثبت کرده باشد، بعد از مهاجرت همان را به
		   یک ثبتِ پنهانِ بدونِ آدرس تبدیل می‌کنیم: منوی تکراری در پیشخوان نمی‌ماند و
		   ?singer= دیگر query_var نمی‌شود. هیچ داده‌ای پاک نمی‌شود. */
		if ( ! $blocked && taxonomy_exists( $from ) ) {
			$jv_old = get_taxonomy( $from );
			if ( $jv_old && ! empty( $jv_old->public ) && ! empty( $jv_old->rewrite ) ) {
				unregister_taxonomy( $from );
			}
		}

		if ( ! $blocked && ! taxonomy_exists( $to ) ) {
			register_taxonomy( $to, array( 'post' ), javan_seda_artist_tax_args( ( 'artist' === $to ) ? $from : $to ) );
		} elseif ( $blocked ) {
			update_option( 'javan_seda_artist_tax_note', 'skip-register: یک پست‌تایپ با نامک «' . $to . '» وجود دارد؛ برای جلوگیری از ۴۰۴، رجیستر لغو شد.', false );
		}

		// لایۀ سازگاری: نامکِ کنارگذاشته‌شده ثبت می‌ماند ولی پنهان و بدون آدرس،
		// تا خواندن‌های قدیمی (مادر/افزونه‌ها) به جای fatal، آرایهٔ خالی بگیرند.
		if ( ! $blocked && $from !== $to && ! taxonomy_exists( $from ) ) {
			register_taxonomy(
				$from,
				array( 'post' ),
				array(
					'public'             => false,
					'publicly_queryable' => false,
					'hierarchical'       => false,
					'show_ui'            => false,
					'show_in_menu'       => false,
					'show_in_nav_menus'  => false,
					'show_in_rest'       => false,
					'query_var'          => false,
					'rewrite'            => false,
					'label'              => 'سازگاری (قدیمی)',
					'description'        => 'نامکِ پیشینِ «هنرمند». چیزی اینجا ذخیره نمی‌شود؛ فقط برای سازگاری ثبت شده.',
				)
			);
		}
	}
}
if ( taxonomy_exists( javan_seda_artist_tax() ) ) {
	// ثبت شده (توسط مادر یا فراخوانی قبلی)؛ فقط نامک کناری لازم است
	javan_seda_artist_tax_register();
} elseif ( did_action( 'init' ) >= 1 ) {
	javan_seda_artist_tax_register();
} else {
	add_action( 'init', 'javan_seda_artist_tax_register', 99 );
}

/* -------------------------------------------------------------------------
   ۳) ریدایرکت ۳۰۱ از آدرسِ قدیمی به آدرسِ تازه
   ------------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_artist_legacy_redirect' ) ) {
	function javan_seda_artist_legacy_redirect() {
		if ( is_admin() ) {
			return;
		}
		$from = javan_seda_artist_tax_other(); // وقتی artist فعال است، این singer است
		if ( javan_seda_artist_tax() === $from ) {
			return;
		}
		$req = isset( $GLOBALS['wp']->request ) ? (string) $GLOBALS['wp']->request : '';
		$req = trim( $req, '/' );
		if ( '' === $req ) {
			return;
		}
		$jv_404 = ( function_exists( 'is_404' ) && is_404() );
		/* آدرسِ کهنۀ صفحۀ فهرست: /singers (اسلاگِ برگه پیش از یکدست‌سازی). فقط وقتی اجرا
		   می‌شود که درخواست ۴۰۴ شده باشد، پس هیچ صفحۀ زنده‌ای را نمی‌پوشاند. */
		if ( $jv_404 && ( 'singers' === $req || 'singer' === $req ) && function_exists( 'javan_seda_artists_index_url' ) ) {
			$jv_index = javan_seda_artists_index_url();
			$jv_path  = trim( (string) wp_parse_url( $jv_index, PHP_URL_PATH ), '/' );
			if ( $jv_index && $jv_path !== $req ) {
				if ( isset( $_SERVER['QUERY_STRING'] ) && '' !== (string) $_SERVER['QUERY_STRING'] ) {
					$jv_index .= '?' . sanitize_text_field( wp_unslash( (string) $_SERVER['QUERY_STRING'] ) );
				}
				wp_redirect( $jv_index, 301 ); // phpcs:ignore
				exit;
			}
			return;
		}
		if ( ! preg_match( '#^' . preg_quote( $from, '#' ) . '/([^/]+)(?:/page/(\d+))?/?$#u', $req, $m ) ) {
			return;
		}
		$term = get_term_by( 'slug', $m[1], javan_seda_artist_tax() );
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) || '' === $link ) {
			return;
		}
		if ( ! empty( $m[2] ) ) {
			$link = user_trailingslashit( rtrim( $link, '/' ) . '/page/' . (int) $m[2] );
		}
		if ( isset( $_SERVER['QUERY_STRING'] ) && '' !== (string) $_SERVER['QUERY_STRING'] ) {
			$link .= ( false === strpos( $link, '?' ) ? '?' : '&' ) . sanitize_text_field( wp_unslash( (string) $_SERVER['QUERY_STRING'] ) );
		}
		wp_redirect( $link, 301 ); // phpcs:ignore
		exit;
	}
}
add_action( 'template_redirect', 'javan_seda_artist_legacy_redirect', 1 );

/* -------------------------------------------------------------------------
   ۴) گزارشِ تشخیصی کوچک (فقط مدیر، فقط خواندنی) — ?ab_artist_diag=1
   ------------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_artist_diag' ) ) {
	function javan_seda_artist_diag() {
		$want = isset( $_GET['ab_artist_diag'] ) ? 'ab_artist_diag' : ( isset( $_GET['ab_singer_diag'] ) ? 'ab_singer_diag' : '' );
		if ( '' === $want || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$out  = array();
		$out[] = 'نامک فعال: ' . javan_seda_artist_tax() . ' | آدرس: /' . javan_seda_artist_slug() . '/';
		$out[] = 'taxonomy_exists(artist): ' . ( taxonomy_exists( 'artist' ) ? 'YES' : 'no' );
		$out[] = 'taxonomy_exists(singer): ' . ( taxonomy_exists( 'singer' ) ? 'YES' : 'no' );
		$out[] = 'is_tax(' . javan_seda_artist_tax() . '): ' . ( is_tax( javan_seda_artist_tax() ) ? 'YES' : 'no' );
		$counts = wp_count_terms( array( 'taxonomy' => javan_seda_artist_tax(), 'hide_empty' => false ) );
		$out[]  = 'تعداد اصطلاح: ' . ( ( is_wp_error( $counts ) ) ? 'خطا' : (string) (int) $counts );
		$out[]  = 'صفحۀ فهرست: ' . esc_url( javan_seda_artists_index_url() );
		$log    = get_option( 'javan_seda_artist_tax_log', '' );
		$note   = get_option( 'javan_seda_artist_tax_note', '' );
		if ( $log ) {
			$out[] = 'مهاجرت: ' . $log;
		}
		if ( $note ) {
			$out[] = 'توجه: ' . $note;
		}
		echo '<pre style="background:#111;color:#fff;padding:12px;font:13px/1.7 monospace;direction:rtl;text-align:right">' . esc_html( implode( "\n", $out ) ) . '</pre>';
	}
}
add_action( 'wp_footer', 'javan_seda_artist_diag', 99 );
