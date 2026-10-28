<?php
/**
 * Template Name: هنرمندان
 *
 * صفحهٔ «همهٔ هنرمندان» — آدرسِ خودِ این برگه از javan_seda_artists_index_url() هم خوانده می‌شود.
 *
 * این فایل فقط «نمایش» است: نه تاکسونومی ثبت می‌کند، نه rewrite را تغییر می‌دهد،
 * نه در جایی از سایت دخالت می‌کند. اگر این فایل را بردارید، قالب قبلی دقیقاً مثل
 * قبل کار می‌کند. فقط روی همان برگه‌ای اثر دارد که قالبش «هنرمندان» انتخاب شده است.
 *
 * قابلیت‌ها:
 *   • ترتیب واقعی حروف الفبای فارسی (ی/ي و ک/ك و آ/ا و حرکات یکسان‌سازی می‌شوند)
 *   • نوار حروف الفبا + بخش‌بندی گروهی (هر حرف یک بخش با لنگر #letter-…)
 *   • جست‌وجوی زندهٔ نام هنرمند (بدون درخواست به سرور، با نرمال‌سازی فارسی)
 *   • ترتیب بر اساس «بیشترین آهنگ» در کنار ترتیب الفبایی (?sort=songs)
 *   • فیلتر حرف با آدرس مخصوص خودش (?letter=ب) برای اشتراک‌گذاری و ایندکس شدن
 *   • تعداد آهنگ هر هنرمند روی کارت + خط آمار کل + ساختار داده ItemList برای گوگل
 *   • تصویر تنبل‌بار (lazy) با srcset، دکمهٔ «اول صفحه»، پیام حالت خالی و حالت شب
 *
 * تنظیم‌ها و همه‌چیز از طریق فیلتر «javanseda_artists_config» قابل تغییر است.
 *
 * گزارش تشخیصی (فقط مدیر): همین آدرس + ?ab_artists_diag=1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==================================================================================
   ۰) تنظیم‌ها — اگر خواستید چیزی را عوض کنید، فقط همین جدول
   ================================================================================== */
$ab_cfg = array(
	'taxonomy'    => javan_seda_artist_tax(), // نامک فعالِ تاکسونومی هنرمند (artist یا singer)
	'hide_empty'  => true,     // هنرمندانِ بدون آهنگ پنهان بمانند
	'max_terms'   => 1200,     // بیشتر از این تعداد → حالت صفحه‌بندی (برای امنیت سرعت)
	'per_page'    => 60,       // فقط در حالت صفحه‌بندی
	'show_counts' => true,     // تعداد آهنگ روی هر کارت
	'show_az'     => true,     // نوار حروف الفبا
	'show_search' => true,     // جعبهٔ جست‌وجوی زنده
	'show_sort'   => true,     // لینک‌های ترتیب (الفبا / بیشترین آهنگ)
	'show_latin'  => true,     // نام لاتین/انگلیسی هنرمند زیر نام فارسی
	'show_top'    => true,     // ردیف «پرکارترین هنرمندان»
	'top_limit'   => 12,       // چند هنرمند در آن ردیف
	'json_ld_max' => 200,      // حداکثر تعداد آیتم در داده‌ساختاریافته
);
$ab_cfg = apply_filters( 'javanseda_artists_config', $ab_cfg );

$ab_taxonomy = isset( $ab_cfg['taxonomy'] ) ? (string) $ab_cfg['taxonomy'] : javan_seda_artist_tax();
$ab_total    = 0;

/* ==================================================================================
   ۱) توابع کمکی — نرمال‌سازی فارسی، کلید مرتب‌سازی الفبایی، تصویر کارت
   ================================================================================== */
if ( ! function_exists( 'javanseda_artists_chars' ) ) {
	/** رشته را به حروف تک‌تایی UTF-8 می‌شکند (بدون نیاز به mbstring) */
	function javanseda_artists_chars( $text ) {
		$chars = preg_split( '//u', (string) $text, -1, PREG_SPLIT_NO_EMPTY );
		return is_array( $chars ) ? $chars : array();
	}
}

if ( ! function_exists( 'javanseda_artists_normalize' ) ) {
	/** ي/ك عربی، ة/ۀ، آ/أ/إ، حرکات، کشیده و نیم‌فاصله را یکسان می‌کند */
	function javanseda_artists_normalize( $text ) {
		$text = (string) $text;
		$text = str_replace(
			array( "\xE2\x80\x8C", "\xE2\x80\x8D", 'ي', 'ك', 'ة', 'ۀ', 'ؤ', 'أ', 'إ', 'آ', 'ٱ', 'ئ', 'ى', 'ك' ),
			array( ' ', ' ', 'ی', 'ک', 'ه', 'ه', 'و', 'ا', 'ا', 'ا', 'ا', 'ی', 'ی', 'ک' ),
			$text
		);
		$text = preg_replace( '/[\x{064B}-\x{0652}\x{0640}\x{0670}]/u', '', $text ); // حرکات + کشیده
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( (string) $text );
	}
}

if ( ! function_exists( 'javanseda_artists_sort_key' ) ) {
	/** کلید مرتب‌سازی: هر حرف فارسی → شمارهٔ جای آن در الفبا */
	function javanseda_artists_sort_key( $text, $index ) {
		$key = '';
		foreach ( javanseda_artists_chars( javanseda_artists_normalize( $text ) ) as $char ) {
			if ( isset( $index[ $char ] ) ) {
				$key .= sprintf( '%02d', $index[ $char ] );
			} elseif ( preg_match( '/[0-9]/', $char ) ) {
				$key .= '80' . $char;
			} elseif ( preg_match( '/[A-Za-z]/', $char ) ) {
				$key .= '85' . strtolower( $char );
			} elseif ( ' ' === $char ) {
				$key .= '98';
			} else {
				$key .= '97';
			}
		}
		return $key;
	}
}

if ( ! function_exists( 'javanseda_artists_image' ) ) {
	/** تصویر هنرمند از فیلد ACF «aimg2» (شناسه/آرایه/آدرس) + تصویر پیش‌فرض قالب */
	function javanseda_artists_image( $term, $size = 'medium' ) {
		/* v3.5.2 — همان ترتیبِ بقیهٔ قالب: ACF → term meta. تصویرِ ثبت‌شده در
		   پیشخوانِ هنرمند (بدون ACF) هم همین‌جا نمایش داده می‌شود. */
		$image = '';
		if ( function_exists( 'javan_seda_artist_field' ) ) {
			$image = javan_seda_artist_field( $term, 'aimg2' );
			if ( '' === $image ) {
				$maybe_id = absint( javan_seda_artist_field( $term, 'aimg2_id' ) );
				if ( $maybe_id ) {
					$image = $maybe_id;
				}
			}
		} elseif ( function_exists( 'get_field' ) ) {
			$image = get_field( 'aimg2', $term );
		}
		$id    = 0;
		$url   = '';

		if ( is_array( $image ) ) {
			if ( ! empty( $image['ID'] ) ) {
				$id = (int) $image['ID'];
			} elseif ( ! empty( $image['id'] ) ) {
				$id = (int) $image['id'];
			} elseif ( ! empty( $image['url'] ) ) {
				$url = (string) $image['url'];
			}
		} elseif ( is_numeric( $image ) ) {
			$id = (int) $image;
		} elseif ( is_string( $image ) && '' !== trim( $image ) ) {
			$url = trim( $image );
		}

		$alt = $term instanceof WP_Term ? $term->name : '';

		if ( $id ) {
			$html = wp_get_attachment_image(
				$id,
				$size,
				false,
				array(
					'alt'      => $alt,
					'title'    => $alt,
					'loading'  => 'lazy',
					'decoding' => 'async',
					'class'    => 'ab-artist__img',
				)
			);
			if ( $html ) {
				return $html;
			}
		}

		if ( '' === $url ) {
			$url = function_exists( 'javan_seda_artist_default_image' ) ? javan_seda_artist_default_image() : get_template_directory_uri() . '/images/def-singer.webp';
		}

		return '<img class="ab-artist__img" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" title="' . esc_attr( $alt ) . '" loading="lazy" decoding="async" width="200" height="200">';
	}
}

if ( ! function_exists( 'javanseda_artists_latin_name' ) ) {
	/** نام لاتین هنرمند: اول فیلتر، بعد چند فیلد رایج ترم، در پایان از اسلاگ انگلیسی */
	function javanseda_artists_latin_name( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return '';
		}

		$custom = apply_filters( 'javanseda_artists_latin_name', '', $term );
		if ( is_string( $custom ) && '' !== trim( $custom ) ) {
			return trim( wp_strip_all_tags( $custom ) );
		}

		$keys = array( 'artist_en', 'en_name', 'english_name', 'singer_en', 'latin_name', 'name_en', 'en_title', 'artist_latin' );
		foreach ( $keys as $key ) {
			$value = get_term_meta( $term->term_id, $key, true );
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return trim( wp_strip_all_tags( $value ) );
			}
		}

		/* اسلاگ انگلیسی مثل mohsen-yeganeh → Mohsen Yeganeh */
		$slug = (string) $term->slug;
		if ( '' !== $slug && preg_match( '/^[a-z0-9\-]+$/', $slug ) && preg_match( '/[a-z]/', $slug ) ) {
			return ucwords( trim( str_replace( array( '-', '_' ), ' ', $slug ) ) );
		}

		return '';
	}
}

/* ==================================================================================
   ۲) الفبای فارسی و ورودی‌های آدرس (?letter= و ?sort= و ?q=)
   ================================================================================== */
$ab_alphabet = 'ابپتثجچحخدذرزژسشصضطظعغفقکگلمنوهی';
$ab_index    = array();
$ab_pos      = 0;
foreach ( javanseda_artists_chars( $ab_alphabet ) as $ab_char ) {
	$ab_index[ $ab_char ] = $ab_pos;
	$ab_pos++;
}

$ab_letter = isset( $_GET['letter'] ) ? sanitize_text_field( wp_unslash( $_GET['letter'] ) ) : '';
$ab_letter = javanseda_artists_normalize( $ab_letter );
if ( '' !== $ab_letter && ! isset( $ab_index[ $ab_letter ] ) && '#' !== $ab_letter ) {
	$ab_letter = '';
}

$ab_sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'name';
if ( ! in_array( $ab_sort, array( 'name', 'songs' ), true ) ) {
	$ab_sort = 'name';
}

$ab_search      = isset( $_GET['q'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['q'] ) ) ) : '';
$ab_search_norm = javanseda_artists_normalize( $ab_search );

/* صفحه‌های ?sort= و ?q= ارزش ایندکس‌شدن ندارند (محتوای تکراری/نتیجهٔ جست‌وجو) */
if ( ( '' !== $ab_sort && 'name' !== $ab_sort ) || '' !== $ab_search ) {
	add_filter(
		'wp_robots',
		function ( $robots ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			return $robots;
		}
	);
}

/* ==================================================================================
   ۳) گزارش تشخیصی (فقط مدیر، متن ساده) — همین آدرس + ?ab_artists_diag=1
   ================================================================================== */
if ( isset( $_GET['ab_artists_diag'] ) && current_user_can( 'manage_options' ) ) {
	$ab_diag_terms = taxonomy_exists( $ab_taxonomy )
		? get_terms( array( 'taxonomy' => $ab_taxonomy, 'hide_empty' => false, 'number' => 0 ) )
		: new WP_Error( 'no_tax', 'تاکسونومی ' . $ab_taxonomy . ' ثبت نشده است.' );

	$ab_diag_total = is_wp_error( $ab_diag_terms ) ? 0 : count( $ab_diag_terms );
	$ab_diag_lines = array();
	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );

	$ab_diag_lines[] = '=== گزارش «صفحهٔ همهٔ هنرمندان» ===';
	$ab_diag_lines[] = 'فایل قالب در حال اجرا: ' . __FILE__;
	$ab_diag_lines[] = 'قالب ثبت‌شدهٔ این برگه (page template): ' . (string) get_page_template_slug( get_queried_object_id() );
	$ab_diag_lines[] = 'queried_object: ' . ( is_object( get_queried_object() ) ? get_class( get_queried_object() ) . ( get_queried_object() instanceof WP_Post ? ' → ' . get_queried_object()->post_name : '' ) : gettype( get_queried_object() ) );
	$ab_diag_lines[] = 'permalink_structure: ' . (string) get_option( 'permalink_structure' );
	$ab_diag_lines[] = 'taxonomy_exists(' . $ab_taxonomy . '): ' . ( taxonomy_exists( $ab_taxonomy ) ? 'YES' : 'NO' );
	$ab_diag_lines[] = 'post_type_exists(' . $ab_taxonomy . '): ' . ( post_type_exists( $ab_taxonomy ) ? 'YES (هر دو هم‌نام هستند — احتمال تداخل آدرس)' : 'NO' );
	$ab_diag_lines[] = 'hide_empty: ' . ( $ab_cfg['hide_empty'] ? 'true' : 'false' ) . ' | max_terms: ' . (int) $ab_cfg['max_terms'] . ' | per_page: ' . (int) $ab_cfg['per_page'];
	$ab_diag_lines[] = '';

	if ( is_wp_error( $ab_diag_terms ) ) {
		$ab_diag_lines[] = 'خطا: ' . $ab_diag_terms->get_error_message();
		$ab_diag_lines[] = 'یعنی تاکسونومی هنرمندان ثبت نشده است؛ صفحه نمی‌تواند هیچ هنرمندی نشان دهد.';
	} else {
		$ab_diag_lines[] = '--- شمارش ترم‌ها ---';
		$ab_diag_lines[] = 'کل ترم‌ها (با احتساب خالی‌ها): ' . $ab_diag_total;
		$ab_diag_lines[] = 'ترم‌های دارای آهنگ: ' . (int) wp_count_terms( array( 'taxonomy' => $ab_taxonomy, 'hide_empty' => true ) );

		$ab_diag_letters = array();
		foreach ( $ab_diag_terms as $ab_diag_term ) {
			if ( ! $ab_diag_term instanceof WP_Term ) {
				continue;
			}
			$ab_chars = javanseda_artists_chars( javanseda_artists_normalize( $ab_diag_term->name ) );
			$ab_first = $ab_chars ? $ab_chars[0] : '#';
			$ab_key   = isset( $ab_index[ $ab_first ] ) ? $ab_first : '#';
			if ( ! isset( $ab_diag_letters[ $ab_key ] ) ) {
				$ab_diag_letters[ $ab_key ] = 0;
			}
			$ab_diag_letters[ $ab_key ]++;
		}
		$ab_diag_lines[] = '';
		$ab_diag_lines[] = '--- تعداد در هر حرف ---';
		foreach ( $ab_diag_letters as $ab_letter_key => $ab_letter_count ) {
			$ab_diag_lines[] = '   ' . $ab_letter_key . ' : ' . $ab_letter_count;
		}

		$ab_diag_lines[] = '';
		$ab_diag_lines[] = '--- ۱۰ ترم اول (الفبایی جدید / ترتیب فعلی) ---';
		$ab_diag_sample = array_slice( $ab_diag_terms, 0, 10 );
		foreach ( $ab_diag_sample as $ab_diag_term ) {
			$ab_diag_link    = get_term_link( $ab_diag_term );
			$ab_diag_lines[] = '   - ' . $ab_diag_term->name . ' | slug: ' . $ab_diag_term->slug . ' | count: ' . $ab_diag_term->count . ' | ' . ( is_wp_error( $ab_diag_link ) ? 'لینک خطا: ' . $ab_diag_link->get_error_message() : $ab_diag_link );
		}
	}

	$ab_diag_lines[] = '';
	$ab_diag_lines[] = '--- نکته ---';
	$ab_diag_lines[] = 'اگر «فایل قالب در حال اجرا» همین فایل نبود، یعنی این برگه قالب «هنرمندان» را انتخاب نکرده است.';
	echo esc_html( implode( "\n", $ab_diag_lines ) );
	exit;
}

/* ==================================================================================
   ۴) داده — یک کوئری، سپس مرتب‌سازی الفبایی فارسی در PHP
   ================================================================================== */
$ab_error = null;
$ab_items = array();
$ab_terms = array();

if ( ! taxonomy_exists( $ab_taxonomy ) ) {
	$ab_error = new WP_Error( 'no_tax', 'تاکسونومی «' . $ab_taxonomy . '» روی این سایت ثبت نشده است.' );
} else {
	$ab_terms = get_terms(
		array(
			'taxonomy'   => $ab_taxonomy,
			'hide_empty' => ! empty( $ab_cfg['hide_empty'] ),
			'orderby'    => 'name',
			'order'      => 'ASC',
			'number'     => 0,
		)
	);

	if ( is_wp_error( $ab_terms ) ) {
		$ab_error = $ab_terms;
		$ab_terms = array();
	}
}

if ( ! $ab_error && $ab_terms ) {
	/* همهٔ متای ترم‌ها یک‌جا خوانده شود (جلوگیری از صدها کوئری برای تصویرها) */
	update_termmeta_cache( wp_list_pluck( $ab_terms, 'term_id' ) );

	foreach ( $ab_terms as $ab_term ) {
		if ( ! $ab_term instanceof WP_Term ) {
			continue;
		}
		$ab_link = get_term_link( $ab_term );
		if ( is_wp_error( $ab_link ) ) {
			continue;
		}
		$ab_norm  = javanseda_artists_normalize( $ab_term->name );
		$ab_chars = javanseda_artists_chars( $ab_norm );
		$ab_first = $ab_chars ? $ab_chars[0] : '#';

		$ab_items[] = array(
			'term'   => $ab_term,
			'name'   => $ab_term->name,
			'latin'  => javanseda_artists_latin_name( $ab_term ),
			'norm'   => $ab_norm,
			'link'   => $ab_link,
			'count'  => (int) $ab_term->count,
			'letter' => isset( $ab_index[ $ab_first ] ) ? $ab_first : '#',
			'key'    => javanseda_artists_sort_key( $ab_term->name, $ab_index ) . '|' . $ab_norm,
		);
	}
}

if ( $ab_items ) {
	usort(
		$ab_items,
		function ( $a, $b ) use ( $ab_sort ) {
			if ( 'songs' === $ab_sort && $a['count'] !== $b['count'] ) {
				return ( $b['count'] - $a['count'] );
			}
			return strcmp( $a['key'], $b['key'] );
		}
	);
}

$ab_total = count( $ab_items );

/* تعداد هنرمندان در هر حرف (برای نوار الفبا، پیش از اعمال فیلتر حرف) */
$ab_letter_counts = array();
foreach ( $ab_items as $ab_item ) {
	$ab_letter_counts[ $ab_item['letter'] ] = isset( $ab_letter_counts[ $ab_item['letter'] ] )
		? $ab_letter_counts[ $ab_item['letter'] ] + 1
		: 1;
}

/* پرکارترین هنرمندان — ردیف لینک‌های سریع (قبل از اعمال فیلترها) */
$ab_top = array();
if ( ! empty( $ab_cfg['show_top'] ) ) {
	$ab_by_count = $ab_items;
	usort(
		$ab_by_count,
		function ( $a, $b ) {
			if ( $a['count'] === $b['count'] ) {
				return strcmp( $a['key'], $b['key'] );
			}
			return ( $b['count'] - $a['count'] );
		}
	);
	$ab_top = array_slice( $ab_by_count, 0, max( 1, (int) $ab_cfg['top_limit'] ) );
}

/* فیلتر حرف (?letter=) */
if ( '' !== $ab_letter ) {
	$ab_items = array_values(
		array_filter(
			$ab_items,
			function ( $item ) use ( $ab_letter ) {
				return $item['letter'] === $ab_letter;
			}
		)
	);
}

/* جست‌وجوی سروری (?q=) — برای لینک‌های اشتراکی و کارکرد بدون جاوااسکریپت */
if ( '' !== $ab_search_norm ) {
	$ab_items = array_values(
		array_filter(
			$ab_items,
			function ( $item ) use ( $ab_search_norm, $ab_search ) {
				if ( '' !== $item['norm'] && false !== strpos( $item['norm'], $ab_search_norm ) ) {
					return true;
				}
				if ( '' !== $item['latin'] && '' !== $ab_search && false !== stripos( $item['latin'], $ab_search ) ) {
					return true;
				}
				return false;
			}
		)
	);
}

/* تعداد نمای فعلی (بعد از فیلترها) */
$ab_view_total = count( $ab_items );

/* گروه‌بندی بر اساس حرف (فقط در نمای کامل) */
$ab_groups = array();
foreach ( $ab_items as $ab_item ) {
	$ab_groups[ $ab_item['letter'] ][] = $ab_item;
}
uksort(
	$ab_groups,
	function ( $a, $b ) use ( $ab_index ) {
		$ai = isset( $ab_index[ $a ] ) ? $ab_index[ $a ] : 999;
		$bi = isset( $ab_index[ $b ] ) ? $ab_index[ $b ] : 999;
		if ( $ai === $bi ) {
			return strcmp( $a, $b );
		}
		return ( $ai < $bi ) ? -1 : 1;
	}
);

/* حالت صفحه‌بندی — فقط اگر تعداد از سقف تنظیم‌شده بیشتر باشد */
$ab_use_pagination = ( '' === $ab_letter && '' === $ab_search_norm && $ab_total > (int) $ab_cfg['max_terms'] );
$ab_per_page       = max( 1, (int) $ab_cfg['per_page'] );
$ab_paged          = (int) get_query_var( 'paged' );
if ( $ab_paged < 1 ) {
	$ab_paged = (int) get_query_var( 'page' );
}
$ab_paged       = max( 1, $ab_paged );
$ab_total_pages = $ab_use_pagination ? (int) ceil( $ab_total / $ab_per_page ) : 1;

if ( $ab_use_pagination ) {
	$ab_items  = array_slice( $ab_items, ( $ab_paged - 1 ) * $ab_per_page, $ab_per_page );
	$ab_groups = array();
	foreach ( $ab_items as $ab_item ) {
		$ab_groups[ $ab_item['letter'] ][] = $ab_item;
	}
}

/* آمار نمای فعلی: چند هنرمند و چند آهنگ در همین صفحه دیده می‌شود */
$ab_stat_artists = count( $ab_items );
$ab_stat_songs   = 0;
foreach ( $ab_items as $ab_item ) {
	$ab_stat_songs += $ab_item['count'];
}

/* ==================================================================================
   ۵) سرتیتر صفحه (h1) — یکتا برای هر آدرس
   ================================================================================== */
$ab_page_id    = (int) get_queried_object_id();
$ab_page_title = '';
if ( $ab_page_id ) {
	$ab_page_title = trim( (string) get_the_title( $ab_page_id ) );
}
if ( '' === $ab_page_title ) {
	$ab_page_title = 'لیست هنرمندان';
}

/* v3.4.8 — تیترِ این صفحه را می‌توان از تنظیمات قالب عوض کرد، بدونِ دست‌زدن به عنوانِ برگه:
   «تنظیمات پوسته ← رابط کاربری ← تیترِ صفحۀ هنرمندان» */
$ab_title_opt = function_exists( 'ot_get_option' ) ? trim( (string) ot_get_option( 'ab_artists_title', '' ) ) : '';
if ( '' !== $ab_title_opt ) {
	$ab_page_title = $ab_title_opt;
	add_filter( 'document_title_parts', function ( $parts ) use ( $ab_title_opt ) {
		if ( is_array( $parts ) && isset( $parts['title'] ) ) {
			$parts['title'] = $ab_title_opt;
		}
		return $parts;
	}, 20 );
}

if ( '' !== $ab_search ) {
	$ab_h1 = $ab_page_title . ' — جست‌وجو برای «' . $ab_search . '»';
} elseif ( '' !== $ab_letter ) {
	$ab_h1 = $ab_page_title . ' — حرف «' . $ab_letter . '»';
} elseif ( 'songs' === $ab_sort ) {
	$ab_h1 = $ab_page_title . ' — پرکارترین هنرمندان';
} else {
	$ab_h1 = $ab_page_title;
}

$ab_base_url = $ab_page_id ? get_permalink( $ab_page_id ) : '';
if ( ! $ab_base_url ) {
	$ab_base_url = home_url( '/' );
}

$ab_url_all    = $ab_base_url;
$ab_url_songs  = add_query_arg( 'sort', 'songs', $ab_base_url );
$ab_url_name   = $ab_base_url;
$ab_url_letter = array();
foreach ( javanseda_artists_chars( $ab_alphabet ) as $ab_char ) {
	$ab_url_letter[ $ab_char ] = add_query_arg(
		array(
			'letter' => $ab_char,
			'sort'   => ( 'name' !== $ab_sort ? $ab_sort : null ),
		),
		$ab_base_url
	);
}

get_header( '' );
get_template_part( 'inc/breadcrumbs' );
?>
<div class="tarlanweb_center">
	<div id="centers_box">
		<div class="clear2"></div>
		<div class="song_boxs singers_box_tarlanweb ab-artists__grid">

			<div class="songs_title_box">
				<h1><?php echo esc_html( $ab_h1 ); ?></h1>
				<?php if ( 'songs' === $ab_sort ) : ?>
					<a href="<?php echo esc_url( $ab_url_name ); ?>">ترتیب الفبا</a>
				<?php elseif ( ! empty( $ab_cfg['show_sort'] ) ) : ?>
					<a href="<?php echo esc_url( $ab_url_songs ); ?>">پرتعدادترین</a>
				<?php endif; ?>
				<div class="clear"></div>
			</div>

			<div id="ab-artists" class="ab-artists" data-ab-artists>

				<?php if ( $ab_error || ! $ab_view_total ) : ?>

					<div class="ab-artists__notice">
						<?php if ( $ab_error ) : ?>
							<p><strong>فهرست هنرمندان فعلاً نمایش داده نمی‌شود.</strong></p>
							<?php if ( current_user_can( 'manage_options' ) ) : ?>
								<p class="ab-artists__admin">(فقط مدیر می‌بیند) دلیل: <?php echo esc_html( $ab_error->get_error_message() ); ?><br>
								برای بررسی کامل، همین آدرس را با <code>?ab_artists_diag=1</code> باز کنید.</p>
							<?php endif; ?>
						<?php elseif ( '' !== $ab_search ) : ?>
							<p>نتیجه‌ای برای «<?php echo esc_html( $ab_search ); ?>» پیدا نشد.</p>
							<p><a href="<?php echo esc_url( $ab_url_all ); ?>">نمایش همهٔ هنرمندان</a></p>
						<?php elseif ( '' !== $ab_letter ) : ?>
							<p>برای حرف «<?php echo esc_html( $ab_letter ); ?>» هنرمندی ثبت نشده است.</p>
							<p><a href="<?php echo esc_url( $ab_url_all ); ?>">نمایش همهٔ هنرمندان</a></p>
						<?php else : ?>
							<p>هنوز هیچ هنرمندی ثبت نشده است.</p>
						<?php endif; ?>
					</div>

				<?php else : ?>

					<?php if ( '' !== $ab_search ) : ?>
						<p class="ab-artists__intro">
							<?php echo esc_html( number_format_i18n( $ab_view_total ) ); ?> هنرمند برای «<?php echo esc_html( $ab_search ); ?>» پیدا شد.
							<a href="<?php echo esc_url( $ab_url_all ); ?>">پاک‌کردن جست‌وجو</a>
						</p>
					<?php elseif ( '' !== $ab_letter ) : ?>
						<p class="ab-artists__intro">
							هنرمندانی که نامشان با حرف «<?php echo esc_html( $ab_letter ); ?>» شروع می‌شود
							(<?php echo esc_html( number_format_i18n( $ab_stat_artists ) ); ?> هنرمند).
							<a href="<?php echo esc_url( $ab_url_all ); ?>">نمایش همهٔ حروف الفبا</a>
						</p>
					<?php elseif ( 'songs' !== $ab_sort ) : ?>
						<p class="ab-artists__intro">
							فهرست کامل هنرمندان به ترتیب حروف الفبای فارسی؛ برای دیدن آهنگ‌های هر هنرمند روی نام او بزنید.
							در حال حاضر <strong><?php echo esc_html( number_format_i18n( $ab_stat_artists ) ); ?></strong> هنرمند در این فهرست هستند.
						</p>
					<?php endif; ?>

					<div class="ab-artists__toolbar">
						<?php if ( ! empty( $ab_cfg['show_search'] ) ) : ?>
							<form class="ab-artists__search" method="get" action="<?php echo esc_url( $ab_base_url ); ?>" role="search" data-ab-searchbox>
								<span class="ab-artists__search-icon" aria-hidden="true">⌕</span>
								<input type="search" name="q" value="<?php echo esc_attr( $ab_search ); ?>" data-ab-search autocomplete="off" enterkeyhint="search"
									placeholder="جست‌وجوی نام هنرمند…" aria-label="جست‌وجوی نام هنرمند">
								<?php if ( '' !== $ab_letter ) : ?>
									<input type="hidden" name="letter" value="<?php echo esc_attr( $ab_letter ); ?>">
								<?php endif; ?>
								<?php if ( 'name' !== $ab_sort ) : ?>
									<input type="hidden" name="sort" value="<?php echo esc_attr( $ab_sort ); ?>">
								<?php endif; ?>
								<button type="submit" class="ab-artists__search-btn">جست‌وجو</button>
							</form>
						<?php endif; ?>
						<p class="ab-artists__stats" data-ab-stats aria-live="polite">
							<?php
							echo esc_html(
								sprintf(
									'%s هنرمند · %s آهنگ',
									number_format_i18n( $ab_stat_artists ),
									number_format_i18n( $ab_stat_songs )
								)
							);
							?>
						</p>
					</div>

					<?php if ( ! empty( $ab_cfg['show_top'] ) && $ab_top && '' === $ab_search && '' === $ab_letter && 'songs' !== $ab_sort ) : ?>
						<nav class="ab-artists__toplist" aria-label="پرکارترین هنرمندان">
							<span class="ab-artists__toplist-title">پرکارترین هنرمندان:</span>
							<?php foreach ( $ab_top as $ab_top_item ) : ?>
								<a class="ab-artists__pill" href="<?php echo esc_url( $ab_top_item['link'] ); ?>" title="<?php echo esc_attr( $ab_top_item['name'] ); ?>">
									<?php echo esc_html( $ab_top_item['name'] ); ?><span class="ab-artists__pill-count"><?php echo esc_html( number_format_i18n( $ab_top_item['count'] ) ); ?></span>
								</a>
							<?php endforeach; ?>
							<a class="ab-artists__pill ab-artists__pill--more" href="<?php echo esc_url( $ab_url_songs ); ?>">همهٔ پرتعدادها</a>
						</nav>
					<?php endif; ?>

					<?php if ( ! empty( $ab_cfg['show_az'] ) && ! $ab_use_pagination ) : ?>
						<nav class="ab-artists__az" data-ab-az aria-label="حروف الفبای فارسی">
							<?php if ( '' === $ab_letter ) : ?>
								<a class="ab-artists__chip is-active" href="#ab-artists">همه</a>
							<?php else : ?>
								<a class="ab-artists__chip" href="<?php echo esc_url( $ab_url_all ); ?>">همه</a>
							<?php endif; ?>
							<?php
							foreach ( javanseda_artists_chars( $ab_alphabet ) as $ab_char ) :
								$ab_char_count = isset( $ab_letter_counts[ $ab_char ] ) ? (int) $ab_letter_counts[ $ab_char ] : 0;
								$ab_is_active  = ( $ab_char === $ab_letter );
								?>
								<?php if ( $ab_char_count > 0 ) : ?>
									<?php if ( '' === $ab_letter ) : ?>
										<a class="ab-artists__chip" data-ab-chip="<?php echo esc_attr( $ab_char ); ?>" href="<?php echo esc_attr( '#letter-' . $ab_char ); ?>" title="<?php echo esc_attr( sprintf( '%s هنرمند', number_format_i18n( $ab_char_count ) ) ); ?>"><?php echo esc_html( $ab_char ); ?></a>
									<?php else : ?>
										<a class="ab-artists__chip<?php echo $ab_is_active ? ' is-active' : ''; ?>" data-ab-chip="<?php echo esc_attr( $ab_char ); ?>" href="<?php echo esc_url( $ab_url_letter[ $ab_char ] ); ?>" title="<?php echo esc_attr( sprintf( '%s هنرمند', number_format_i18n( $ab_char_count ) ) ); ?>"><?php echo esc_html( $ab_char ); ?></a>
									<?php endif; ?>
								<?php else : ?>
									<span class="ab-artists__chip is-empty" aria-hidden="true"><?php echo esc_html( $ab_char ); ?></span>
								<?php endif; ?>
							<?php endforeach; ?>
							<?php if ( isset( $ab_letter_counts['#'] ) ) : ?>
								<?php if ( '' === $ab_letter ) : ?>
									<a class="ab-artists__chip" data-ab-chip="#" href="#letter-other" title="<?php echo esc_attr( sprintf( '%s هنرمند', number_format_i18n( $ab_letter_counts['#'] ) ) ); ?>">#</a>
								<?php else : ?>
									<a class="ab-artists__chip<?php echo ( '#' === $ab_letter ) ? ' is-active' : ''; ?>" data-ab-chip="#" href="<?php echo esc_url( add_query_arg( 'letter', rawurlencode( '#' ), $ab_base_url ) ); ?>" title="سایر هنرمندان">#</a>
								<?php endif; ?>
							<?php endif; ?>
						</nav>
					<?php endif; ?>

					<p class="ab-artists__empty" data-ab-empty hidden>هنرمندی با این نام پیدا نشد.</p>

					<?php
					foreach ( $ab_groups as $ab_group_letter => $ab_group_items ) :
						$ab_group_id = ( '#' === $ab_group_letter ) ? 'letter-other' : 'letter-' . $ab_group_letter;
						?>
						<section class="ab-artists__group" id="<?php echo esc_attr( $ab_group_id ); ?>" data-ab-group data-ab-letter="<?php echo esc_attr( $ab_group_letter ); ?>">
							<h2 class="ab-artists__letter">
								<span class="ab-artists__letter-char"><?php echo esc_html( '#' === $ab_group_letter ? 'سایر' : $ab_group_letter ); ?></span>
								<span class="ab-artists__letter-count"><?php echo esc_html( sprintf( '%s هنرمند', number_format_i18n( count( $ab_group_items ) ) ) ); ?></span>
							</h2>
							<div class="song_boxs_posts">
								<ul>
									<?php
									foreach ( $ab_group_items as $ab_item ) :
										$ab_term = $ab_item['term'];
										?>
										<?php
										$ab_latin_attr = ( ! empty( $ab_cfg['show_latin'] ) && '' !== $ab_item['latin'] ) ? $ab_item['latin'] : '';
										?>
										<li class="ab-artist" data-ab-name="<?php echo esc_attr( $ab_item['norm'] ); ?>"<?php echo ( '' !== $ab_latin_attr ) ? ' data-ab-latin="' . esc_attr( $ab_latin_attr ) . '"' : ''; ?>>
											<a class="ab-artist__link" href="<?php echo esc_url( $ab_item['link'] ); ?>" title="<?php echo esc_attr( $ab_item['name'] ); ?>">
												<span class="ab-artist__thumb">
													<?php
												// خروجی این تابع خودمان است و داخلش همه‌چیز escape شده است.
												echo javanseda_artists_image( $ab_term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												?>
													<?php if ( ! empty( $ab_cfg['show_counts'] ) && $ab_item['count'] > 0 ) : ?>
														<span class="ab-artist__badge"><?php echo esc_html( sprintf( '%s آهنگ', number_format_i18n( $ab_item['count'] ) ) ); ?></span>
													<?php endif; ?>
												</span>
												<span class="ab-artist__name"><?php echo esc_html( $ab_item['name'] ); ?></span>
												<?php if ( '' !== $ab_latin_attr ) : ?>
													<span class="ab-artist__latin" dir="ltr" lang="en"><?php echo esc_html( $ab_latin_attr ); ?></span>
												<?php endif; ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
								<div class="clear"></div>
							</div>
						</section>
					<?php endforeach; ?>

					<?php if ( $ab_use_pagination && $ab_paged < $ab_total_pages ) : ?>
						<p class="ab-artists__loadmore">
							<a class="ab-artists__loadmore-btn" href="<?php echo esc_url( add_query_arg( 'paged', $ab_paged + 1, $ab_base_url ) ); ?>">
								مشاهده بیشتر …
								<span class="ab-artists__loadmore-hint">(صفحهٔ <?php echo esc_html( number_format_i18n( $ab_paged + 1 ) ); ?> از <?php echo esc_html( number_format_i18n( $ab_total_pages ) ); ?>)</span>
							</a>
						</p>
					<?php endif; ?>

					<?php if ( $ab_use_pagination && $ab_total_pages > 1 ) : ?>
						<div class="pagination_box_artist">
							<?php
							$ab_big = 999999999;
							echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput -- خروجی خود وردپرس است.
								array(
									'base'      => str_replace( $ab_big, '%#%', esc_url( get_pagenum_link( $ab_big ) ) ),
									'format'    => '?paged=%#%',
									'current'   => $ab_paged,
									'total'     => $ab_total_pages,
									'mid_size'  => 2,
									'prev_text' => 'قبلی',
									'next_text' => 'بعدی',
								)
							);
							?>
						</div>
					<?php endif; ?>

					<?php if ( $ab_total > 6 ) : ?>
						<p class="ab-artists__top"><a href="#ab-artists">بازگشت به بالای فهرست ↑</a></p>
					<?php endif; ?>

				<?php endif; ?>
			</div>

			<div class="clear2"></div>
		</div>
	</div>
</div>

<?php
/* ----------------------------------------------------------------------------------
   داده‌ساختاریافته: فهرست هنرمندان برای گوگل/موتورهای هوش مصنوعی
---------------------------------------------------------------------------------- */
if ( ! $ab_error && $ab_items ) {
	$ab_schema_items = array();
	$ab_position     = ( $ab_use_pagination ? ( $ab_paged - 1 ) * $ab_per_page : 0 );
	foreach ( array_slice( $ab_items, 0, max( 1, (int) $ab_cfg['json_ld_max'] ) ) as $ab_item ) {
		$ab_position++;
		$ab_schema_item = array(
			'@type'    => 'ListItem',
			'position' => $ab_position,
			'name'     => $ab_item['name'],
			'url'      => $ab_item['link'],
		);
		if ( '' !== $ab_item['latin'] ) {
			$ab_schema_item['alternateName'] = $ab_item['latin'];
		}
		$ab_schema_items[] = $ab_schema_item;
	}

	$ab_schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => $ab_h1,
		'numberOfItems'   => $ab_total,
		'itemListOrder'   => ( 'songs' === $ab_sort ) ? 'https://schema.org/ItemListOrderDescending' : 'https://schema.org/ItemListOrderAscending',
		'itemListElement' => $ab_schema_items,
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $ab_schema, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
?>

<?php if ( ! $ab_error && $ab_items && ! empty( $ab_cfg['show_search'] ) ) : ?>
<script>
/* جست‌وجوی زنده و هایلایت حرف فعال — بدون هیچ کتابخانه و بدون درخواست به سرور */
(function () {
	var root = document.querySelector('[data-ab-artists]');
	if (!root) { return; }

	var input  = root.querySelector('[data-ab-search]');
	var stats  = root.querySelector('[data-ab-stats]');
	var empty  = root.querySelector('[data-ab-empty]');
	var items  = Array.prototype.slice.call(root.querySelectorAll('[data-ab-name]'));
	var groups = Array.prototype.slice.call(root.querySelectorAll('[data-ab-group]'));

	function normalize(text) {
		return (text || '')
			.replace(/[\u200c\u200d]/g, ' ')
			.replace(/[يى]/g, 'ی')
			.replace(/ك/g, 'ک')
			.replace(/[ةۀ]/g, 'ه')
			.replace(/[أإآٱ]/g, 'ا')
			.replace(/ئ/g, 'ی')
			.replace(/[\u064B-\u0652\u0640\u0670]/g, '')
			.replace(/\s+/g, ' ')
			.toLowerCase()
			.trim();
	}

	function applyFilter() {
		var raw   = (input ? input.value : '').trim();
		var query = normalize(raw);
		var latin = raw.toLowerCase();
		var shown = 0;

		items.forEach(function (item) {
			var match = !raw
				|| normalize(item.getAttribute('data-ab-name')).indexOf(query) !== -1
				|| (item.getAttribute('data-ab-latin') || '').toLowerCase().indexOf(latin) !== -1;
			item.classList.toggle('is-hidden', !match);
			if (match) { shown++; }
		});

		groups.forEach(function (group) {
			var any = group.querySelector('[data-ab-name]:not(.is-hidden)');
			group.classList.toggle('is-hidden', !any);
			var chip = root.querySelector('[data-ab-chip="' + group.getAttribute('data-ab-letter') + '"]');
			if (chip) { chip.classList.toggle('is-muted', !any); }
		});

		if (empty) { empty.hidden = (shown !== 0); }
		if (stats) {
			stats.textContent = raw
				? shown + ' نتیجه برای «' + raw + '»'
				: stats.getAttribute('data-ab-default') || stats.textContent;
		}
	}

	if (stats && !stats.getAttribute('data-ab-default')) {
		stats.setAttribute('data-ab-default', stats.textContent);
	}

	if (input) {
		input.addEventListener('input', applyFilter);
		input.addEventListener('search', applyFilter);
		var form = input.closest('form');
		if (form) { form.addEventListener('submit', function (e) { e.preventDefault(); applyFilter(); }); }
	}

	/* هایلایت حرفی که در حال دیدنش هستیم */
	var chips = Array.prototype.slice.call(root.querySelectorAll('[data-ab-chip]'));
	if (chips.length && 'IntersectionObserver' in window) {
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) { return; }
				var letter = entry.target.getAttribute('data-ab-letter');
				chips.forEach(function (chip) {
					chip.classList.toggle('is-active', chip.getAttribute('data-ab-chip') === letter);
				});
			});
		}, { rootMargin: '-72px 0px -70% 0px' });

		groups.forEach(function (group) { observer.observe(group); });
	}
})();
</script>
<?php endif; ?>

<?php get_footer( '' ); ?>
