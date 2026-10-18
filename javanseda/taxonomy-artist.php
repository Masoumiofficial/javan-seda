<?php
/**
 * صفحهٔ اختصاصی هر هنرمند (taxonomy-artist.php) — نسخهٔ ۳
 * نامکِ فعالِ آرشیو را javan_seda_artist_tax() تعیین می‌کند؛ taxonomy-singer.php هم
 * به‌عنوان سازگاری باقی مانده و همین فایل را بارگذاری می‌کند.
 *
 * این فایل فقط «نمایش» است: نه تاکسونومی ثبت می‌کند، نه rewrite را دست می‌زند و نه
 * هوک سراسری اضافه می‌کند. اگر برداشته شود، قالب قبلی دقیقاً مثل قبل کار می‌کند.
 *
 * چه چیزی نسبت به نسخهٔ قبل اضافه شد؟
 *   • سرتیتر پروفایل کامل‌تر: تصویر + نام + شمارش دسته‌ها + «آخرین به‌روزرسانی» + متن معرفی
 *   • لینک بازگشت به فهرست همهٔ هنرمندان و به همان حرف در فهرست الفبایی (/singers/?letter=…)
 *   • مرتب‌سازی آهنگ‌ها با آدرس مستقل: ?sort=new (پیش‌فرض) | ?sort=old | ?sort=title
 *   • «هنرمندانِ مشابه» بر اساس هنرمندانِ مشترک در نوشته‌ها
 *   • داده‌ساختاریافتهٔ MusicGroup + ItemList برای گوگل
 *   • گزارش تشخیصی مدیر: ?ab_artist_diag=1
 *   • حفظ همان کلاس‌های CSS قبلی (artist_box_tarlanweb، artist_resume_box، song_boxs، …)
 *
 * قابل تنظیم با فیلترها:
 *   javanseda_artist_page_config    تنظیم‌های این صفحه (جدول پایین‌تر)
 *   javanseda_artist_type_map       نگاشت مقادیر musics_type به دسته‌ها
 *   javanseda_artist_type_labels    برچسب دسته‌ها
 *   javanseda_artist_directory_cache خاموش‌کردن کش ۱۲ساعتهٔ آدرس برگهٔ فهرست هنرمندان
 *   javanseda_artist_resume_labels  برچسب کوتاه نشان‌های سرتیتر (آهنگ/آلبوم/…)
 *   javanseda_artist_posts_limit    سقف تعداد نوشته‌های هنرمند (پیش‌فرض -۱ = همه)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==================================================================================
   ۰) تنظیم‌ها
   ================================================================================== */
$taranehyar_cfg = apply_filters(
	'javanseda_artist_page_config',
	array(
		'show_sort'      => true,  // نوار «ترتیب: جدیدترین / قدیمی‌ترین / الفبایی»
		'show_related'   => true,  // بخش «هنرمندانِ مشابه»
		'related_limit'  => 8,     // حداکثر تعداد هنرمندِ مشابه
		'show_meta'      => true,  // شمارش دسته‌ها و تاریخ آخرین به‌روزرسانی
		'show_dir_links' => true,  // لینک‌های بازگشت به فهرست الفبایی
		'json_ld_max'    => 100,   // حداکثر آیتم در داده‌ساختاریافته
	)
);

/* ==================================================================================
   ۱) توابع کمکی (نام‌ها با javanseda_artist_ شروع می‌شوند تا با فایل فهرست هنرمندان تداخل نکنند)
   ================================================================================== */
if ( ! function_exists( 'javanseda_artist_digits' ) ) {
	/** تبدیل رقم‌های لاتین به رقم‌های فارسی («12» → «۱۲») */
	function javanseda_artist_digits( $text ) {
		return str_replace(
			array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
			array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
			(string) $text
		);
	}
}

if ( ! function_exists( 'javanseda_artist_fa_date' ) ) {
	/** تاریخ خوانا برای سرتیتر: «بهمن 4, 1399» → «۴ بهمن ۱۳۹۹» */
	function javanseda_artist_fa_date( $date ) {
		$date = trim( (string) $date );
		if ( preg_match( '/^(\S+)\s+([0-9]{1,2})\s*[،,]\s*([0-9]{4})$/u', $date, $m ) ) {
			$date = $m[2] . ' ' . $m[1] . ' ' . $m[3];
		}
		return javanseda_artist_digits( $date );
	}
}

if ( ! function_exists( 'javanseda_artist_normalize' ) ) {
	/** نرمال‌سازی فارسی برای مرتب‌سازی و مقایسه */
	function javanseda_artist_normalize( $text ) {
		$text = (string) $text;
		$text = str_replace(
			array( "\xE2\x80\x8C", "\xE2\x80\x8D", 'ي', 'ك', 'ة', 'ۀ', 'ؤ', 'أ', 'إ', 'آ', 'ٱ', 'ئ', 'ى' ),
			array( ' ', ' ', 'ی', 'ک', 'ه', 'ه', 'و', 'ا', 'ا', 'ا', 'ا', 'ی', 'ی' ),
			$text
		);
		$text = preg_replace( '/[\x{064B}-\x{0652}\x{0640}\x{0670}]/u', '', $text );
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}
}

if ( ! function_exists( 'javanseda_artist_sort_key' ) ) {
	/** کلید مرتب‌سازی الفبایی فارسی (هر حرف → شمارهٔ جای آن در الفبا) */
	function javanseda_artist_sort_key( $text ) {
		$alphabet = 'ابپتثجچحخدذرزژسشصضطظعغفقکگلمنوهی';
		$index    = array();
		$chars    = preg_split( '//u', $alphabet, -1, PREG_SPLIT_NO_EMPTY );
		foreach ( (array) $chars as $i => $char ) {
			$index[ $char ] = $i;
		}

		$key   = '';
		$parts = preg_split( '//u', javanseda_artist_normalize( $text ), -1, PREG_SPLIT_NO_EMPTY );
		foreach ( (array) $parts as $char ) {
			if ( isset( $index[ $char ] ) ) {
				$key .= sprintf( '%02d', $index[ $char ] );
			} elseif ( preg_match( '/[A-Za-z]/', $char ) ) {
				$key .= '85' . strtolower( $char );
			} elseif ( preg_match( '/[0-9]/', $char ) ) {
				$key .= '80' . $char;
			} elseif ( ' ' === $char ) {
				$key .= '98';
			} else {
				$key .= '97';
			}
		}
		return $key;
	}
}

if ( ! function_exists( 'javanseda_artist_directory_url' ) ) {
	/**
	 * آدرسِ صفحۀ «همهٔ هنرمندان» — از 3.4.8 فقط یک رابطِ سازگار است.
	 * منطقِ واقعی در javan_seda_artists_index_url() (لایۀ inc/ab-tax-artist.php) است:
	 * arti_url ← اسلاگِ برگه (artists → artist → singers) ← قالبِ artist-template.php ← آرشیوِ تاکسونومی,
	 * و کلیدِ کشش با شمارۀ قالب می‌سازد، پس بعدِ هر آپدیت دیگر مقدارِ کهنه سرو نمی‌شود.
	 */
	function javanseda_artist_directory_url() {
		static $url = null;
		if ( null !== $url ) {
			return $url;
		}
		if ( function_exists( 'javan_seda_artists_index_url' ) ) {
			$url = javan_seda_artists_index_url();
		} else {
			/* لایه نصب نشده؟ همان رفتارِ سادهٔ قبلی، بدونِ کش */
			$page = get_page_by_path( 'artists', 'OBJECT', 'page' );
			if ( ! $page ) {
				$page = get_page_by_path( 'singers', 'OBJECT', 'page' );
			}
			$url = $page ? get_permalink( (int) $page->ID ) : '';
		}
		if ( ! $url || is_wp_error( $url ) ) {
			$url = home_url( '/singers/' );
		}
		return apply_filters( 'javanseda_artist_directory_url', $url );
	}
}


/* ==================================================================================
   ۲) ورودی‌های آدرس (?sort=)
   ================================================================================== */
$taranehyar_sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'new';
if ( ! in_array( $taranehyar_sort, array( 'new', 'old', 'title' ), true ) ) {
	$taranehyar_sort = 'new';
}

/* صفحه‌های مرتب‌شده محتوای تکراری‌اند → noindex (باید قبل از get_header ثبت شود) */
if ( 'new' !== $taranehyar_sort ) {
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
   ۳) گزارش تشخیصی مدیر — همین آدرس + ?ab_artist_diag=1
   ================================================================================== */
if ( isset( $_GET['ab_artist_diag'] ) && current_user_can( 'manage_options' ) ) {
	$taranehyar_diag_term = get_queried_object();
	$taranehyar_lines     = array();

	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );

	$taranehyar_lines[] = '=== گزارش تشخیص «صفحهٔ هنرمند» — ترانه‌یار ===';
	$taranehyar_lines[] = 'فایل قالب در حال اجرا: ' . __FILE__;
	$taranehyar_lines[] = 'queried_object: ' . ( is_object( $taranehyar_diag_term ) ? get_class( $taranehyar_diag_term ) : gettype( $taranehyar_diag_term ) );
	$taranehyar_lines[] = 'taxonomy_exists(' . javan_seda_artist_tax() . '): ' . ( taxonomy_exists( javan_seda_artist_tax() ) ? 'YES' : 'NO' );
	$taranehyar_lines[] = 'sort: ' . $taranehyar_sort . ' | posts_limit: ' . (int) apply_filters( 'javanseda_artist_posts_limit', -1 );
	$taranehyar_lines[] = '';

	if ( ! $taranehyar_diag_term instanceof WP_Term ) {
		$taranehyar_lines[] = 'این درخواست یک ترم تاکسونومی نیست؛ یعنی وردپرس این آدرس را «آرشیو هنرمند» نمی‌بیند.';
		$taranehyar_lines[] = 'راهنما: ?ab_artist_diag=1 (نسخۀ 3.4.7 به بعد؛ ?ab_singer_diag هم همان گزارش را می‌دهد)';
	} else {
		$taranehyar_lines[] = '--- ترم ---';
		$taranehyar_lines[] = 'name: ' . $taranehyar_diag_term->name . ' | slug: ' . $taranehyar_diag_term->slug . ' | term_id: ' . $taranehyar_diag_term->term_id;
		$taranehyar_lines[] = 'count ترم در دیتابیس: ' . $taranehyar_diag_term->count;
		$taranehyar_lines[] = 'term_link: ' . ( is_wp_error( get_term_link( $taranehyar_diag_term ) ) ? 'خطا: ' . get_term_link( $taranehyar_diag_term )->get_error_message() : get_term_link( $taranehyar_diag_term ) );
		$taranehyar_lines[] = 'تصویر aimg2: ' . ( '' !== (string) ( function_exists( 'get_field' ) ? get_field( 'aimg2', $taranehyar_diag_term ) : '' ) ? 'دارد' : 'ندارد (تصویر پیش‌فرض قالب)' );
		$taranehyar_lines[] = 'توضیحات ترم (بیوگرافی): ' . ( '' !== trim( wp_strip_all_tags( (string) get_the_archive_description() ) ) ? 'دارد' : 'خالی است' );
		$taranehyar_lines[] = '';
		$taranehyar_lines[] = '--- نوشته‌های این هنرمند و مقدار musics_type آن‌ها (۲۵ مورد اول) ---';

		$taranehyar_diag_query = new WP_Query(
			array(
				'post_type'        => 'post',
				'post_status'      => 'publish',
				'posts_per_page'   => 25,
				'fields'           => 'ids',
				'no_found_rows'    => false,
				'suppress_filters' => false,
				'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => javan_seda_artist_tax(),
						'field'    => 'term_id',
						'terms'    => $taranehyar_diag_term->term_id,
					),
				),
			)
		);

		$taranehyar_lines[] = 'پیدا شد: ' . (int) $taranehyar_diag_query->found_posts . ' نوشته';
		if ( $taranehyar_diag_query->posts ) {
			update_postmeta_cache( $taranehyar_diag_query->posts );
			foreach ( $taranehyar_diag_query->posts as $taranehyar_diag_id ) {
				$taranehyar_raw_type = get_post_meta( $taranehyar_diag_id, 'musics_type', true );
				$taranehyar_lines[]  = '   - ' . get_the_title( $taranehyar_diag_id ) . ' | musics_type: ' . ( is_scalar( $taranehyar_raw_type ) && '' !== (string) $taranehyar_raw_type ? (string) $taranehyar_raw_type : '(خالی)' ) . ' | song: ' . ( function_exists( 'get_field' ) ? (string) get_field( 'song', $taranehyar_diag_id ) : '' );
			}
		}
	}

	$taranehyar_lines[] = '';
	$taranehyar_lines[] = '--- لینک‌ها ---';
	$taranehyar_lines[] = 'فهرست همهٔ هنرمندان: ' . javanseda_artist_directory_url();

	echo esc_html( implode( "\n", $taranehyar_lines ) );
	exit;
}

get_header();
get_template_part( 'inc/breadcrumbs' );

$taranehyar_term = get_queried_object();

if ( ! $taranehyar_term instanceof WP_Term ) {
	// تاکسونومی/ترم به‌درستی بارگذاری نشده است؛ به‌جای صفحهٔ خالی، پیام روشن بده.
	?>
	<div class="tarlanweb_center">
		<div class="artist_box_tarlanweb">
			<div class="artist_box_detss">
				<h1 class="artist_name_head">صفحهٔ این هنرمند در دسترس نیست</h1>
				<div class="artist_txt_box">
					<p>آدرس این صفحه با هیچ هنرمند ثبت‌شده‌ای مطابقت ندارد. اگر قبلاً این صفحه کار می‌کرد، احتمالاً قوانین پیوند یکتا (Permalink) نیاز به بازسازی دارد: پیشخوان ← تنظیمات ← پیوندهای یکتا ← ذخیرهٔ تغییرات.</p>
					<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>">بازگشت به صفحهٔ اصلی</a></p>
					<div class="clear"></div>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
	<?php
	get_footer();
	return;
}

$taranehyar_term_field = function ( $key ) use ( $taranehyar_term ) {
	return function_exists( 'get_field' ) ? get_field( $key, $taranehyar_term ) : '';
};

$taranehyar_image     = $taranehyar_term_field( 'aimg2' );
$taranehyar_image_url = '';
if ( is_array( $taranehyar_image ) && isset( $taranehyar_image['url'] ) ) {
	$taranehyar_image_url = $taranehyar_image['url'];
} elseif ( is_string( $taranehyar_image ) ) {
	$taranehyar_image_url = $taranehyar_image;
}

$taranehyar_socials = array(
	'afacebook'  => 'facebook',
	'atelegram'  => 'telegram',
	'ainstagram' => 'instagram',
	'atwitter'   => 'twitter',
	'ayoutube'   => 'youtube',
);

/* ------------------------------------------------------------------
   نگاشت نوع محتوا: هر مقدار ناشناخته در «سایر موارد» می‌افتد (پنهان نمی‌شود)
------------------------------------------------------------------- */
$taranehyar_type_map = apply_filters(
	'javanseda_artist_type_map',
	array(
		/* صوتی */
		'mp3'         => 'audio',
		'audio'       => 'audio',
		'ahang'       => 'audio',
		'آهنگ'        => 'audio',
		'آهنگ جدید'   => 'audio',
		'song'        => 'audio',
		/* تصویری */
		'mp4'         => 'video',
		'video'       => 'video',
		'musicvideo'  => 'video',
		'music-video' => 'video',
		'موزیک ویدیو' => 'video',
		'موزیک‌ویدیو' => 'video',
		'کلیپ'        => 'video',
		/* آلبوم */
		'album'       => 'album',
		'آلبوم'       => 'album',
	)
);

$taranehyar_labels = apply_filters(
	'javanseda_artist_type_labels',
	array(
		'album' => 'آلبوم ها',
		'audio' => 'آهنگ ها',
		'video' => 'موزیک ویدیو ها',
		'other' => 'سایر موارد',
	)
);

/* برچسب کوتاه نشان‌های سرتیتر: «۶ آهنگ» به‌جای «۶ آهنگ ها» */
$taranehyar_resume_labels = apply_filters(
	'javanseda_artist_resume_labels',
	array(
		'album' => 'آلبوم',
		'audio' => 'آهنگ',
		'video' => 'موزیک ویدیو',
		'other' => 'سایر',
	)
);

$taranehyar_limit = (int) apply_filters( 'javanseda_artist_posts_limit', -1 );

/* ترتیب کوئری بر اساس ?sort= (مرتب‌سازی الفبایی بعد از کوئری و در PHP انجام می‌شود) */
$taranehyar_orderby = ( 'old' === $taranehyar_sort ) ? 'ASC' : 'DESC';

$taranehyar_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $taranehyar_limit,
		'orderby'             => 'date',
		'order'               => $taranehyar_orderby,
		'ignore_sticky_posts' => true,
		'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => javan_seda_artist_tax(),
				'field'    => 'slug',
				'terms'    => $taranehyar_term->slug,
				'operator' => 'IN',
			),
		),
	)
);

$taranehyar_buckets = array( 'album' => array(), 'audio' => array(), 'video' => array(), 'other' => array() );
$taranehyar_found   = 0;
$taranehyar_unknown = array();

if ( $taranehyar_query->have_posts() ) {
	$taranehyar_found = (int) $taranehyar_query->found_posts;

	/* برای مرتب‌سازی الفبایی و نمایش نام آهنگ، متای همهٔ نوشته‌ها را یک‌جا می‌خوانیم */
	update_postmeta_cache( wp_list_pluck( $taranehyar_query->posts, 'ID' ) );

	if ( 'title' === $taranehyar_sort ) {
		usort(
			$taranehyar_query->posts,
			function ( $a, $b ) {
				$title_a = (string) get_post_meta( $a->ID, 'song', true );
				$title_b = (string) get_post_meta( $b->ID, 'song', true );
				if ( '' === $title_a ) {
					$title_a = $a->post_title;
				}
				if ( '' === $title_b ) {
					$title_b = $b->post_title;
				}
				return strcmp( javanseda_artist_sort_key( $title_a ), javanseda_artist_sort_key( $title_b ) );
			}
		);
	}

	foreach ( $taranehyar_query->posts as $taranehyar_post ) {
		$taranehyar_raw = get_post_meta( $taranehyar_post->ID, 'musics_type', true );
		if ( ! is_scalar( $taranehyar_raw ) ) {
			$taranehyar_raw = '';
		}
		$taranehyar_key = strtolower( trim( (string) $taranehyar_raw ) );
		if ( isset( $taranehyar_type_map[ $taranehyar_key ] ) ) {
			$taranehyar_bucket = $taranehyar_type_map[ $taranehyar_key ];
		} elseif ( isset( $taranehyar_type_map[ (string) $taranehyar_raw ] ) ) {
			$taranehyar_bucket = $taranehyar_type_map[ (string) $taranehyar_raw ];
		} else {
			$taranehyar_bucket = 'other';
			if ( '' !== $taranehyar_key ) {
				$taranehyar_unknown[ $taranehyar_key ] = true;
			}
		}
		$taranehyar_buckets[ $taranehyar_bucket ][] = $taranehyar_post;
	}
}
wp_reset_postdata();

/* ------------------------------------------------------------------
   هنرمندانِ مشابه: بر اساس بیشترین اشتراک در نوشته‌های همین هنرمند
------------------------------------------------------------------- */
$taranehyar_related = array();
if ( ! empty( $taranehyar_cfg['show_related'] ) && ! empty( $taranehyar_query->posts ) ) {
	$taranehyar_ids   = wp_list_pluck( $taranehyar_query->posts, 'ID' );
	$taranehyar_terms = wp_get_object_terms( $taranehyar_ids, javan_seda_artist_tax(), array( 'fields' => 'all_with_object_id' ) );

	if ( ! is_wp_error( $taranehyar_terms ) && $taranehyar_terms ) {
		$taranehyar_scores = array();
		foreach ( $taranehyar_terms as $taranehyar_shared ) {
			if ( (int) $taranehyar_shared->term_id === (int) $taranehyar_term->term_id ) {
				continue;
			}
			$taranehyar_scores[ $taranehyar_shared->term_id ] = isset( $taranehyar_scores[ $taranehyar_shared->term_id ] )
				? $taranehyar_scores[ $taranehyar_shared->term_id ] + 1
				: 1;
		}

		if ( $taranehyar_scores ) {
			arsort( $taranehyar_scores );
			$taranehyar_scores = array_slice( $taranehyar_scores, 0, max( 1, (int) $taranehyar_cfg['related_limit'] ), true );
			$taranehyar_related = get_terms(
				array(
					'taxonomy'   => javan_seda_artist_tax(),
					'include'    => array_keys( $taranehyar_scores ),
					'hide_empty' => true,
					'orderby'    => 'include',
				)
			);
			if ( is_wp_error( $taranehyar_related ) ) {
				$taranehyar_related = array();
			}
			// ترتیب نهایی: بیشترین اشتراک
			usort(
				$taranehyar_related,
				function ( $a, $b ) use ( $taranehyar_scores ) {
					$sa = isset( $taranehyar_scores[ $a->term_id ] ) ? $taranehyar_scores[ $a->term_id ] : 0;
					$sb = isset( $taranehyar_scores[ $b->term_id ] ) ? $taranehyar_scores[ $b->term_id ] : 0;
					return ( $sb - $sa );
				}
			);
		}
	}
}

/* ------------------------------------------------------------------
   سرتیتر پروفایل: تاریخ آخرین به‌روزرسانی + لینک‌های فهرست
------------------------------------------------------------------- */
$taranehyar_latest_post = ! empty( $taranehyar_query->posts ) ? $taranehyar_query->posts[0] : null;
if ( 'old' === $taranehyar_sort && ! empty( $taranehyar_query->posts ) ) {
	foreach ( $taranehyar_query->posts as $taranehyar_candidate ) {
		if ( ! $taranehyar_latest_post || strtotime( $taranehyar_candidate->post_date ) > strtotime( $taranehyar_latest_post->post_date ) ) {
			$taranehyar_latest_post = $taranehyar_candidate;
		}
	}
}
$taranehyar_latest_date = $taranehyar_latest_post ? get_the_date( get_option( 'date_format' ), $taranehyar_latest_post->ID ) : '';

$taranehyar_directory_url = javanseda_artist_directory_url();
$taranehyar_term_norm     = javanseda_artist_normalize( $taranehyar_term->name );
$taranehyar_term_chars    = preg_split( '//u', $taranehyar_term_norm, -1, PREG_SPLIT_NO_EMPTY );
$taranehyar_letter        = ( is_array( $taranehyar_term_chars ) && ! empty( $taranehyar_term_chars[0] ) )
	? $taranehyar_term_chars[0]
	: '';
$taranehyar_letter_url    = ( '' !== $taranehyar_letter )
	? add_query_arg( 'letter', $taranehyar_letter, $taranehyar_directory_url ) . '#letter-' . $taranehyar_letter
	: $taranehyar_directory_url;

$taranehyar_sort_urls = array(
	'new'   => remove_query_arg( 'sort' ),
	'old'   => add_query_arg( 'sort', 'old' ),
	'title' => add_query_arg( 'sort', 'title' ),
);
$taranehyar_sort_labels = array(
	'new'   => 'جدیدترین',
	'old'   => 'قدیمی‌ترین',
	'title' => 'الفبایی (نام آهنگ)',
);
?>

<div class="tarlanweb_center">
	<div class="artist_box_tarlanweb">
		<div class="artists_imsf">
			<?php if ( '' !== $taranehyar_image_url ) : ?>
				<img src="<?php echo esc_url( $taranehyar_image_url ); ?>" width="200" height="200" alt="<?php echo esc_attr( $taranehyar_term->name ); ?>" title="<?php echo esc_attr( $taranehyar_term->name ); ?>">
			<?php else : ?>
				<img src="<?php echo esc_url( function_exists( 'javan_seda_artist_default_image' ) ? javan_seda_artist_default_image() : get_template_directory_uri() . '/images/def-singer.webp' ); ?>" width="200" height="200" alt="<?php echo esc_attr( $taranehyar_term->name ); ?>" title="<?php echo esc_attr( $taranehyar_term->name ); ?>">
			<?php endif; ?>
			<div class="socials_box">
				<span>صفحات مجازی هنرمند</span>
				<?php foreach ( $taranehyar_socials as $taranehyar_field_key => $taranehyar_network ) : ?>
					<?php $taranehyar_social_url = $taranehyar_term_field( $taranehyar_field_key ); ?>
					<?php if ( ! empty( $taranehyar_social_url ) ) : ?>
						<a href="<?php echo esc_url( $taranehyar_social_url ); ?>" class="icofont-<?php echo esc_attr( 'telegram' === $taranehyar_network ? 'paper-plane' : ( 'youtube' === $taranehyar_network ? 'youtube-play' : $taranehyar_network ) ); ?>" aria-label="<?php echo esc_attr( $taranehyar_network ); ?>"></a>
					<?php endif; ?>
				<?php endforeach; ?>
				<div class="clear"></div>
			</div>
			<div class="clear"></div>
		</div>

		<div class="artist_box_detss">
			<h1 class="artist_name_head">دانلود آهنگ‌های <?php echo esc_html( $taranehyar_term->name ); ?></h1>

			<ul class="artist_resume_box">
				<?php foreach ( array( 'album', 'audio', 'video', 'other' ) as $taranehyar_key ) : ?>
					<?php $taranehyar_count = count( $taranehyar_buckets[ $taranehyar_key ] ); ?>
					<?php if ( $taranehyar_count > 0 ) : ?>
						<li><span class="ab-resume__num"><?php echo esc_html( javanseda_artist_digits( absint( $taranehyar_count ) ) ); ?></span> <?php echo esc_html( isset( $taranehyar_resume_labels[ $taranehyar_key ] ) ? $taranehyar_resume_labels[ $taranehyar_key ] : $taranehyar_key ); ?></li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>

			<?php if ( ! empty( $taranehyar_cfg['show_meta'] ) && ( $taranehyar_found > 0 || '' !== $taranehyar_latest_date ) ) : ?>
				<p class="ab-artist-page__meta">
					<?php if ( $taranehyar_found > 0 ) : ?>
						<?php echo esc_html( sprintf( '%s اثر در این صفحه', javanseda_artist_digits( number_format_i18n( $taranehyar_found ) ) ) ); ?>
					<?php endif; ?>
					<?php if ( '' !== $taranehyar_latest_date ) : ?>
						<span class="ab-artist-page__dot">•</span>
						<?php echo esc_html( 'آخرین به‌روزرسانی: ' . javanseda_artist_fa_date( $taranehyar_latest_date ) ); ?>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<div class="clear2"></div>
			<?php
			$taranehyar_description = get_the_archive_description();
			if ( ! empty( $taranehyar_description ) ) :
				?>
				<div class="artist_txt_box">
					<?php echo wp_kses_post( $taranehyar_description ); ?>
					<div class="clear"></div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $taranehyar_cfg['show_dir_links'] ) ) : ?>
				<p class="ab-artist-page__links">
					<a href="<?php echo esc_url( $taranehyar_directory_url ); ?>">همهٔ هنرمندان (فهرست الفبایی)</a>
					<?php if ( '' !== $taranehyar_letter ) : ?>
						<span class="ab-artist-page__dot">•</span>
						<a href="<?php echo esc_url( $taranehyar_letter_url ); ?>">هنرمندانِ حرف «<?php echo esc_html( $taranehyar_letter ); ?>»</a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>
		<div class="clear"></div>
	</div>
</div>

<div id="artist_box">

<!-- centers_box -->
<div class="tarlanweb_center">
<div id="centers_box">

<?php if ( ! empty( $taranehyar_cfg['show_sort'] ) && $taranehyar_found > 1 ) : ?>
	<nav class="ab-artist-page__sortbox" aria-label="ترتیب نمایش آثار">
		<span class="ab-artist-page__sortlabel">ترتیب:</span>
		<?php foreach ( $taranehyar_sort_labels as $taranehyar_sort_key => $taranehyar_sort_label ) : ?>
			<?php if ( $taranehyar_sort_key === $taranehyar_sort ) : ?>
				<span class="ab-artist-page__sortitem is-active" aria-current="true"><?php echo esc_html( $taranehyar_sort_label ); ?></span>
			<?php else : ?>
				<a class="ab-artist-page__sortitem" href="<?php echo esc_url( $taranehyar_sort_urls[ $taranehyar_sort_key ] ); ?>"><?php echo esc_html( $taranehyar_sort_label ); ?></a>
			<?php endif; ?>
		<?php endforeach; ?>
	</nav>
<?php endif; ?>

<?php if ( ! empty( $taranehyar_cfg['show_related'] ) && ! empty( $taranehyar_related ) ) : ?>
	<div class="song_boxs ab-related">
		<div class="songs_title_box">
			<h3>هنرمندانِ مشابه</h3>
			<div class="clear"></div><div class="liner"></div>
		</div>
		<div class="ab-related__list">
			<?php foreach ( $taranehyar_related as $taranehyar_related_term ) : ?>
				<?php $taranehyar_related_link = get_term_link( $taranehyar_related_term ); ?>
				<?php if ( is_wp_error( $taranehyar_related_link ) ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<a class="ab-related__item" href="<?php echo esc_url( $taranehyar_related_link ); ?>">
					<?php echo esc_html( $taranehyar_related_term->name ); ?>
					<span class="ab-related__count"><?php echo esc_html( number_format_i18n( (int) $taranehyar_related_term->count ) ); ?></span>
				</a>
			<?php endforeach; ?>
			<div class="clear"></div>
		</div>
	</div>
<?php endif; ?>

<?php foreach ( array( 'album', 'audio', 'video', 'other' ) as $taranehyar_key ) : ?>
	<?php if ( empty( $taranehyar_buckets[ $taranehyar_key ] ) ) : ?>
		<?php continue; ?>
	<?php endif; ?>
	<!-- song_boxs -->
	<div class="song_boxs">
		<div class="songs_title_box">
			<h3><?php echo esc_html( isset( $taranehyar_labels[ $taranehyar_key ] ) ? $taranehyar_labels[ $taranehyar_key ] : $taranehyar_key ); ?></h3>
			<span class="ab-bucket-count"><?php echo esc_html( number_format_i18n( count( $taranehyar_buckets[ $taranehyar_key ] ) ) ); ?></span>
			<div class="clear"></div><div class="liner"></div>
		</div>
		<div class="song_boxs_posts">
			<ul>
				<?php
				foreach ( $taranehyar_buckets[ $taranehyar_key ] as $taranehyar_post ) :
					$GLOBALS['post'] = $taranehyar_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- لازم برای جزئی که به حلقهٔ اصلی تکیه دارد
					setup_postdata( $taranehyar_post );
					?>
					<li><?php get_template_part( 'inc/songs_post' ); ?></li>
					<?php
				endforeach;
				wp_reset_postdata();
				?>
			</ul>
			<div class="clear"></div>
		</div>
		<div class="clear"></div>
	</div>
	<!-- song_boxs -->
<?php endforeach; ?>

<?php if ( 0 === $taranehyar_found ) : ?>
	<div class="song_boxs">
		<div class="songs_title_box">
			<h3>آهنگی برای این هنرمند ثبت نشده است</h3>
			<div class="clear"></div><div class="liner"></div>
		</div>
		<div class="artist_txt_box">
			<p>هنوز نوشته‌ای به این هنرمند متصل نشده است. اگر آهنگ‌های این هنرمند در سایت موجودند، در نوشته‌ها فیلد «هنرمند» را به همین نام انتخاب کنید.</p>
			<p><a href="<?php echo esc_url( $taranehyar_directory_url ); ?>">دیدن همهٔ هنرمندان</a> — <a href="<?php echo esc_url( home_url( '/' ) ); ?>">بازگشت به صفحهٔ اصلی</a></p>
			<div class="clear"></div>
		</div>
	</div>
<?php elseif ( ! empty( $taranehyar_unknown ) ) : ?>
	<?php /* مقادیر ناشناخته در «سایر موارد» نمایش داده شده‌اند؛ برای تشخیص سریع، نام‌شان در کد می‌ماند. */ ?>
	<!-- musics_type values seen: <?php echo esc_html( implode( ', ', array_keys( $taranehyar_unknown ) ) ); ?> -->
<?php endif; ?>

<div class="clear"></div>
</div>
</div>
<!-- centers_box -->

</div>

<?php
/* ----------------------------------------------------------------------------------
   داده‌ساختاریافته: هنرمند (MusicGroup) + فهرست آثار
---------------------------------------------------------------------------------- */
if ( $taranehyar_found > 0 ) {
	$taranehyar_term_link = get_term_link( $taranehyar_term );
	$taranehyar_term_link = is_wp_error( $taranehyar_term_link ) ? '' : $taranehyar_term_link;

	$taranehyar_graph    = array();
	$taranehyar_artist   = array(
		'@type' => 'MusicGroup',
		'@id'   => $taranehyar_term_link . '#artist',
		'name'  => $taranehyar_term->name,
		'url'   => $taranehyar_term_link,
	);
	if ( '' !== $taranehyar_image_url ) {
		$taranehyar_artist['image'] = $taranehyar_image_url;
	}
	if ( ! empty( $taranehyar_description ) ) {
		$taranehyar_artist['description'] = trim( wp_strip_all_tags( $taranehyar_description ) );
	}

	$taranehyar_songs = array();
	$taranehyar_pos   = 0;
	foreach ( array( 'audio', 'video', 'album', 'other' ) as $taranehyar_schema_key ) {
		foreach ( $taranehyar_buckets[ $taranehyar_schema_key ] as $taranehyar_schema_post ) {
			if ( $taranehyar_pos >= max( 1, (int) $taranehyar_cfg['json_ld_max'] ) ) {
				break 2;
			}
			$taranehyar_pos++;
			$taranehyar_song_name = (string) get_post_meta( $taranehyar_schema_post->ID, 'song', true );
			$taranehyar_songs[]   = array(
				'@type'    => 'ListItem',
				'position' => $taranehyar_pos,
				'name'     => ( '' !== $taranehyar_song_name ) ? $taranehyar_song_name : get_the_title( $taranehyar_schema_post->ID ),
				'url'      => get_permalink( $taranehyar_schema_post->ID ),
			);
		}
	}

	$taranehyar_graph[] = $taranehyar_artist;
	$taranehyar_graph[] = array(
		'@type'           => 'ItemList',
		'@id'             => $taranehyar_term_link . '#songs',
		'name'            => 'آهنگ‌های ' . $taranehyar_term->name,
		'numberOfItems'   => $taranehyar_found,
		'itemListOrder'   => ( 'old' === $taranehyar_sort ) ? 'https://schema.org/ItemListOrderAscending' : 'https://schema.org/ItemListOrderDescending',
		'itemListElement' => $taranehyar_songs,
	);

	echo '<script type="application/ld+json">' . wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $taranehyar_graph,
		),
		JSON_UNESCAPED_UNICODE
	) . '</script>' . "\n";
}
?>

<?php get_footer( '' ); ?>
