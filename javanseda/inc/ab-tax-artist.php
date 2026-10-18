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

if ( ! function_exists( 'javan_seda_artists_index_url' ) ) {
	/**
	 * آدرس صفحۀ «همهٔ هنرمندان».
	 * قالب مادر این را یک برگه می‌داند (اسلاگ فعلی: singers). اگر بعداً اسلاگ را
	 * به artists عوض کردید، همان اولتش پیدا می‌شود؛ اگر هیچ‌کدام نبود، آدرسِ
	 * آرشیوِ خودِ تاکسونومی را می‌دهیم تا لینک ۴۰۴ نشود.
	 */
	function javan_seda_artists_index_url() {
		static $url = null;
		if ( null !== $url ) {
			return $url;
		}
		$url    = '';
		$slugs  = array( 'artists', 'artist', 'singers', 'singer' );
		$slugs  = (array) apply_filters( 'javan_seda_artists_index_slugs', $slugs );
		foreach ( $slugs as $s ) {
			$page = get_page_by_path( sanitize_title( (string) $s ) );
			if ( $page ) {
				$link = get_permalink( (int) $page->ID );
				if ( $link && ! is_wp_error( $link ) ) {
					$url = $link;
					break;
				}
			}
		}
		if ( '' === $url ) {
			$tax = get_taxonomy( javan_seda_artist_tax() );
			if ( $tax && ! empty( $tax->rewrite ) && is_array( $tax->rewrite ) && ! empty( $tax->rewrite['slug'] ) ) {
				$url = home_url( '/' . trim( (string) $tax->rewrite['slug'], '/' ) . '/' );
			}
		}
		if ( '' === $url ) {
			$url = home_url( '/singers/' );
		}
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
