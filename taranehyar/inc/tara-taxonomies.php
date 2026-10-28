<?php
/**
 * تاکسونومی‌های موسیقی — ترانه‌سرا، آهنگساز، تنظیم، ژانر و حال‌وهوا
 * ==========================================================================
 * چرا تاکسونومی و نه فیلد متنی؟
 *   • «ترانه‌سرا» و «آهنگساز» در سایت‌های موسیقی معمولاً چند آهنگ دارند؛ با
 *     تاکسونومی می‌شود صفحهٔ هر کدام را داشت (`/songwriter/...`).
 *   • «ژانر» درختی است (پاپ ← پاپ ایرانی) و برای فیلتر و سئو لازم است.
 *   • با `show_in_rest` همه در ویرایشگر بلوک و REST API هم دیده می‌شوند.
 *
 * هیچ داده‌ای مهاجرت نمی‌کند و هیچ فیلد قبلی دست نمی‌خورد: فیلدهای متنیِ
 * شناسنامه (inc/ab-credits.php) سر جایشان هستند؛ این‌ها فقط لایهٔ فیلتر و
 * آرشیو اضافه می‌کنند.
 *
 * خاموش‌کردن هر تاکسونومی:
 *   add_filter( 'tara_music_taxonomies', function ( $list ) {
 *       unset( $list['mood'] );
 *       return $list;
 *   } );
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_music_taxonomies' ) ) {
	/**
	 * تعریف تاکسونومی‌ها در یک جا.
	 *
	 * @return array
	 */
	function tara_music_taxonomies() {
		$taxonomies = array(
			'genre'      => array(
				'taxonomy'    => 'tara_genre',
				'slug'        => 'genre',
				'singular'    => 'ژانر',
				'plural'      => 'ژانرها',
				'hierarchical' => true,
				'icon'        => 'dashicons-playlist-audio',
				'description' => 'سبک موسیقی: پاپ، رپ، سنتی، تلفیقی…',
			),
			'mood'       => array(
				'taxonomy'    => 'tara_mood',
				'slug'        => 'mood',
				'singular'    => 'حال‌وهوا',
				'plural'      => 'حال‌وهواها',
				'hierarchical' => false,
				'icon'        => 'dashicons-heart',
				'description' => 'حس آهنگ: عاشقانه، انگیزشی، غمگین، شاد…',
			),
			'songwriter' => array(
				'taxonomy'    => 'tara_songwriter',
				'slug'        => 'songwriter',
				'singular'    => 'ترانه‌سرا',
				'plural'      => 'ترانه‌سراها',
				'hierarchical' => false,
				'icon'        => 'dashicons-edit',
				'description' => 'نویسندهٔ متن ترانه.',
			),
			'composer'   => array(
				'taxonomy'    => 'tara_composer',
				'slug'        => 'composer',
				'singular'    => 'آهنگساز',
				'plural'      => 'آهنگسازها',
				'hierarchical' => false,
				'icon'        => 'dashicons-format-audio',
				'description' => 'سازندهٔ ملودی.',
			),
			'arranger'   => array(
				'taxonomy'    => 'tara_arranger',
				'slug'        => 'arranger',
				'singular'    => 'تنظیم‌کننده',
				'plural'      => 'تنظیم‌کننده‌ها',
				'hierarchical' => false,
				'icon'        => 'dashicons-controls-volumeon',
				'description' => 'تنظیم و میکس آهنگ.',
			),
		);

		return apply_filters( 'tara_music_taxonomies', $taxonomies );
	}
}

if ( ! function_exists( 'tara_music_taxonomy_ids' ) ) {
	/**
	 * فقط نامکِ تاکسونومی‌ها (برای حلقه‌ها و بررسی `taxonomy_exists`).
	 *
	 * @return string[]
	 */
	function tara_music_taxonomy_ids() {
		return array_values( array_map( static function ( $item ) { return $item['taxonomy']; }, tara_music_taxonomies() ) );
	}
}

if ( ! function_exists( 'tara_register_music_taxonomies' ) ) {
	/**
	 * ثبت همهٔ تاکسونومی‌ها روی نوشته‌ها.
	 */
	function tara_register_music_taxonomies() {
		foreach ( tara_music_taxonomies() as $key => $config ) {
			$taxonomy = (string) $config['taxonomy'];

			/* اگر افزونه یا قالب فرزند خودش ثبت کرده، دست نمی‌زنیم. */
			if ( taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$labels = array(
				'name'                       => $config['plural'],
				'singular_name'              => $config['singular'],
				'search_items'               => 'جست‌وجوی ' . $config['singular'],
				'all_items'                  => 'همهٔ ' . $config['plural'],
				'parent_item'                => 'والد (' . $config['singular'] . ')',
				'parent_item_colon'          => 'والد:',
				'edit_item'                  => 'ویرایش ' . $config['singular'],
				'update_item'                => 'به‌روزرسانی ' . $config['singular'],
				'add_new_item'               => 'افزودن ' . $config['singular'] . ' تازه',
				'new_item_name'              => 'نام ' . $config['singular'] . ' تازه',
				'not_found'                  => $config['singular'] . 'ی پیدا نشد',
				'no_terms'                   => 'بدون ' . $config['singular'],
				'items_list'                 => 'فهرست ' . $config['plural'],
				'items_list_navigation'      => 'پیمایش فهرست ' . $config['plural'],
				'back_to_items'              => 'بازگشت به ' . $config['plural'],
				'menu_name'                  => $config['plural'],
				'filter_by_item'             => 'فیلتر بر اساس ' . $config['singular'],
				'popular_items'              => $config['plural'] . ' پرکاربرد',
				'separate_items_with_commas' => 'با کاما جدا کنید',
				'add_or_remove_items'        => 'افزودن یا حذف ' . $config['singular'],
				'choose_from_most_used'      => 'از پرکاربردها انتخاب کنید',
			);

			$args = array(
				'labels'            => $labels,
				'public'            => true,
				'publicly_queryable' => true,
				'hierarchical'      => (bool) $config['hierarchical'],
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'rest_base'         => $config['slug'],
				'show_tagcloud'     => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'       => $config['slug'],
					'with_front' => false,
				),
				'description'       => $config['description'],
				'capabilities'      => array(
					'manage_terms' => 'manage_categories',
					'edit_terms'   => 'manage_categories',
					'delete_terms' => 'manage_categories',
					'assign_terms' => 'edit_posts',
				),
			);

			register_taxonomy( $taxonomy, array( 'post' ), $args );
		}
	}
}
add_action( 'init', 'tara_register_music_taxonomies', 12 );

if ( ! function_exists( 'tara_music_taxonomies_flush' ) ) {
	/**
	 * یک‌بار پس از فعال‌سازی قالب، قوانین پیوند یکتا بازسازی می‌شوند تا
	 * آدرس‌های `/genre/...` از همان روز اول کار کنند (بدون دست‌زدن به .htaccess).
	 */
	function tara_music_taxonomies_flush() {
		tara_register_music_taxonomies();
		flush_rewrite_rules( false );
	}
}
add_action( 'after_switch_theme', 'tara_music_taxonomies_flush' );

if ( ! function_exists( 'tara_music_taxonomies_stamp' ) ) {
	/**
	 * مهرِ نسخهٔ پیوند یکتا.
	 *
	 * چرا لازم است؟ اگر فقط به `after_switch_theme` تکیه کنیم، سایتی که قالب
	 * از قبل روشن است هیچ‌وقت قوانین تازه را نمی‌گیرد و آدرس‌های
	 * `/songwriter/...` تا «ذخیرهٔ پیوندهای یکتا» ۴۰۴ می‌دهند. این تابع
	 * یک مهر در دیتابیس نگه می‌دارد؛ هر بار مقدار مهر عوض شود (مثل تغییر
	 * نامک آرشیو در ۳.۷.۱)، یک‌بار قوانین نرم بازسازی می‌شوند.
	 *
	 * فقط یک گزینهٔ سبک اضافه می‌کند و هیچ داده‌ای را جابه‌جا نمی‌کند.
	 */
	function tara_music_taxonomies_stamp() {
		$stamp = (string) apply_filters( 'tara_music_rewrite_stamp', '2026-10-28-english-slugs' );

		if ( get_option( 'taranehyar_music_rewrite_stamp' ) === $stamp ) {
			return;
		}

		update_option( 'taranehyar_music_rewrite_stamp', $stamp, false );

		/* ثبت تاکسونومی‌ها روی init(12) انجام شده؛ حالا قوانین را بازچین. */
		if ( function_exists( 'flush_rewrite_rules' ) ) {
			flush_rewrite_rules( false );
		}
	}
}
add_action( 'init', 'tara_music_taxonomies_stamp', 13 );

/* -------------------------------------------------------------------------
 * ستون‌های پیشخوان: دیدن ژانر/حال‌وهوا در فهرست نوشته‌ها
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'tara_music_columns' ) ) {
	/**
	 * @param array $columns ستون‌های فعلی.
	 * @return array
	 */
	function tara_music_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['tara_genre'] = 'ژانر';
				$new['tara_mood']  = 'حال‌وهوا';
			}
		}

		return $new;
	}
}
add_filter( 'manage_post_posts_columns', 'tara_music_columns' );

if ( ! function_exists( 'tara_music_column_content' ) ) {
	/**
	 * @param string $column  نام ستون.
	 * @param int    $post_id شناسهٔ نوشته.
	 */
	function tara_music_column_content( $column, $post_id ) {
		if ( ! in_array( $column, array( 'tara_genre', 'tara_mood' ), true ) ) {
			return;
		}

		$terms = get_the_terms( (int) $post_id, $column );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			echo '<span aria-hidden="true">—</span>';
			return;
		}

		$names = array();
		foreach ( $terms as $term ) {
			$link    = get_edit_term_link( $term->term_id, $column );
			$names[] = $link ? '<a href="' . esc_url( $link ) . '">' . esc_html( $term->name ) . '</a>' : esc_html( $term->name );
		}

		echo implode( '، ', $names ); // phpcs:ignore WordPress.Security.EscapeOutput -- هر آیتم جداگانه escape شده است
	}
}
add_action( 'manage_post_posts_custom_column', 'tara_music_column_content', 10, 2 );

/* -------------------------------------------------------------------------
 * نام‌های فارسی در REST و ادمین
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'tara_music_taxonomy_label' ) ) {
	/**
	 * برچسبِ مفرد/جمعِ یک تاکسونومی از روی نامک.
	 *
	 * @param string $taxonomy نامک.
	 * @param string $which    singular | plural.
	 * @return string
	 */
	function tara_music_taxonomy_label( $taxonomy, $which = 'singular' ) {
		foreach ( tara_music_taxonomies() as $config ) {
			if ( $config['taxonomy'] === $taxonomy ) {
				return (string) $config[ $which ];
			}
		}

		return (string) $taxonomy;
	}
}
