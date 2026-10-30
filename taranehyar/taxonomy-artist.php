<?php
/**
 * صفحهٔ اختصاصی هر هنرمند (taxonomy-artist.php) — نسخهٔ ۳
 * نامکِ فعالِ آرشیو را tara_artist_tax() تعیین می‌کند؛ taxonomy-singer.php هم
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
 *   tara_artist_page_config    تنظیم‌های این صفحه (جدول پایین‌تر)
 *   tara_artist_type_map       نگاشت مقادیر musics_type به دسته‌ها
 *   tara_artist_type_labels    برچسب دسته‌ها
 *   tara_artist_directory_cache خاموش‌کردن کش ۱۲ساعتهٔ آدرس برگهٔ فهرست هنرمندان
 *   tara_artist_resume_labels  برچسب کوتاه نشان‌های سرتیتر (آهنگ/آلبوم/…)
 *   tara_artist_posts_limit    سقف تعداد نوشته‌های هنرمند (پیش‌فرض -۱ = همه)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==================================================================================
   ۰) تنظیم‌ها
   ================================================================================== */
$taranehyar_cfg = apply_filters(
	'tara_artist_page_config',
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
   ۱) توابع کمکی (نام‌ها با tara_artist_ شروع می‌شوند تا با فایل فهرست هنرمندان تداخل نکنند)
   ================================================================================== */
if ( ! function_exists( 'tara_artist_digits' ) ) {
	/** تبدیل رقم‌های لاتین به رقم‌های فارسی («12» → «۱۲») */
	function tara_artist_digits( $text ) {
		return str_replace(
			array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
			array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
			(string) $text
		);
	}
}

if ( ! function_exists( 'tara_artist_fa_date' ) ) {
	/** تاریخ خوانا برای سرتیتر: «بهمن 4, 1399» → «۴ بهمن ۱۳۹۹» */
	function tara_artist_fa_date( $date ) {
		$date = trim( (string) $date );
		if ( preg_match( '/^(\S+)\s+([0-9]{1,2})\s*[،,]\s*([0-9]{4})$/u', $date, $m ) ) {
			$date = $m[2] . ' ' . $m[1] . ' ' . $m[3];
		}
		return tara_artist_digits( $date );
	}
}

if ( ! function_exists( 'tara_artist_normalize' ) ) {
	/** نرمال‌سازی فارسی برای مرتب‌سازی و مقایسه */
	function tara_artist_normalize( $text ) {
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

if ( ! function_exists( 'tara_artist_sort_nav' ) ) {
	/**
	 * نوار «ترتیب» یک بخش؛ هر بخش پارامتر خودش را دارد تا روی همان بخش اثر کند.
	 *
	 * @param string $bucket  audio|album|video|other
	 * @param string $current new|old|title
	 * @param array  $labels  برچسب‌ها
	 * @param string $anchor  لنگر بازگشت
	 * @return string
	 */
	function tara_artist_sort_nav( $bucket, $current, $labels, $anchor = '' ) {
		$base = remove_query_arg( array( 'sort_' . $bucket ) );
		$out  = '<nav class="ty-artist__sort" aria-label="ترتیب نمایش ' . esc_attr( $bucket ) . '">';
		$out .= '<span>ترتیب:</span>';
		foreach ( (array) $labels as $key => $label ) {
			if ( $key === $current ) {
				$out .= '<span class="is-active" aria-current="true">' . esc_html( $label ) . '</span>';
				continue;
			}
			$url  = ( 'new' === $key ) ? $base : add_query_arg( 'sort_' . $bucket, $key, $base );
			$out .= '<a href="' . esc_url( $url . $anchor ) . '">' . esc_html( $label ) . '</a>';
		}
		$out .= '</nav>';
		return $out;
	}
}

if ( ! function_exists( 'tara_artist_sort_key' ) ) {
	/** کلید مرتب‌سازی الفبایی فارسی (هر حرف → شمارهٔ جای آن در الفبا) */
	function tara_artist_sort_key( $text ) {
		$alphabet = 'ابپتثجچحخدذرزژسشصضطظعغفقکگلمنوهی';
		$index    = array();
		$chars    = preg_split( '//u', $alphabet, -1, PREG_SPLIT_NO_EMPTY );
		foreach ( (array) $chars as $i => $char ) {
			$index[ $char ] = $i;
		}

		$key   = '';
		$parts = preg_split( '//u', tara_artist_normalize( $text ), -1, PREG_SPLIT_NO_EMPTY );
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

if ( ! function_exists( 'tara_artist_directory_url' ) ) {
	/**
	 * آدرسِ صفحۀ «همهٔ هنرمندان» — از 3.4.8 فقط یک رابطِ سازگار است.
	 * منطقِ واقعی در tara_artists_index_url() (لایۀ inc/ab-tax-artist.php) است:
	 * arti_url ← اسلاگِ برگه (artists → artist → singers) ← قالبِ artist-template.php ← آرشیوِ تاکسونومی,
	 * و کلیدِ کشش با شمارۀ قالب می‌سازد، پس بعدِ هر آپدیت دیگر مقدارِ کهنه سرو نمی‌شود.
	 */
	function tara_artist_directory_url() {
		static $url = null;
		if ( null !== $url ) {
			return $url;
		}
		if ( function_exists( 'tara_artists_index_url' ) ) {
			$url = tara_artists_index_url();
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
		return apply_filters( 'tara_artist_directory_url', $url );
	}
}


/* ==================================================================================
   ۲) ورودی‌های آدرس (?sort=)
   ================================================================================== */
$taranehyar_sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'new';
if ( ! in_array( $taranehyar_sort, array( 'new', 'old', 'title' ), true ) ) {
	$taranehyar_sort = 'new';
}

/* v3.7.3 — ترتیبِ مستقل برای هر بخش (آهنگ‌ها / آلبوم‌ها / موزیک‌ویدیوها / سایر).
   ?sort_audio=title  ?sort_album=old  ?sort_video=...  ?sort_other=...
   پارامتر سراسریِ ?sort= برای سازگاری با لینک‌های قدیمی و به‌عنوان مقدار
   پیش‌فرضِ بخش «آهنگ‌ها» باقی مانده است. */
$taranehyar_bucket_keys  = array( 'audio', 'album', 'video', 'other' );
$taranehyar_sorts        = array();
$taranehyar_sort_choices = array( 'new', 'old', 'title' );
foreach ( $taranehyar_bucket_keys as $taranehyar_bk ) {
	$taranehyar_val = 'new';
	$taranehyar_get = isset( $_GET[ 'sort_' . $taranehyar_bk ] ) ? sanitize_key( wp_unslash( $_GET[ 'sort_' . $taranehyar_bk ] ) ) : '';
	if ( in_array( $taranehyar_get, $taranehyar_sort_choices, true ) ) {
		$taranehyar_val = $taranehyar_get;
	} elseif ( 'audio' === $taranehyar_bk ) {
		$taranehyar_val = $taranehyar_sort;
	}
	$taranehyar_sorts[ $taranehyar_bk ] = $taranehyar_val;
}
$taranehyar_any_sort = ( 'new' !== $taranehyar_sort ) || in_array( 'old', $taranehyar_sorts, true ) || in_array( 'title', $taranehyar_sorts, true );

/* صفحه‌های مرتب‌شده محتوای تکراری‌اند → noindex (باید قبل از get_header ثبت شود) */
if ( $taranehyar_any_sort ) {
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
	$taranehyar_lines[] = 'taxonomy_exists(' . tara_artist_tax() . '): ' . ( taxonomy_exists( tara_artist_tax() ) ? 'YES' : 'NO' );
	$taranehyar_lines[] = 'sort: ' . $taranehyar_sort . ' | posts_limit: ' . (int) apply_filters( 'tara_artist_posts_limit', -1 );
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
						'taxonomy' => tara_artist_tax(),
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
	$taranehyar_lines[] = 'فهرست همهٔ هنرمندان: ' . tara_artist_directory_url();

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
	/* v3.5.2 — ترتیبِ خواندن: ACF → term meta (inc/ab-artist-fields.php).
	   بدون ACF هم تصویر/شبکه‌های اجتماعی/بیوگرافی نمایش داده می‌شوند. */
	if ( function_exists( 'tara_artist_field' ) ) {
		return tara_artist_field( $taranehyar_term, $key );
	}
	return function_exists( 'get_field' ) ? get_field( $key, $taranehyar_term ) : '';
};

$taranehyar_image     = $taranehyar_term_field( 'aimg2' );
$taranehyar_image_url = '';
if ( function_exists( 'tara_image_url_from_value' ) ) {
	/* v3.5.2 — شناسهٔ پیوست، آرایهٔ ACF و URL هر سه پشتیبانی می‌شوند */
	$taranehyar_image_url = tara_image_url_from_value( $taranehyar_image );
} elseif ( is_array( $taranehyar_image ) && isset( $taranehyar_image['url'] ) ) {
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
	'tara_artist_type_map',
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
	'tara_artist_type_labels',
	array(
		'album' => 'آلبوم ها',
		'audio' => 'آهنگ ها',
		'video' => 'موزیک ویدیو ها',
		'other' => 'سایر موارد',
	)
);

/* برچسب کوتاه نشان‌های سرتیتر: «۶ آهنگ» به‌جای «۶ آهنگ ها» */
$taranehyar_resume_labels = apply_filters(
	'tara_artist_resume_labels',
	array(
		'album' => 'آلبوم',
		'audio' => 'آهنگ',
		'video' => 'موزیک ویدیو',
		'other' => 'سایر',
	)
);

$taranehyar_limit = (int) apply_filters( 'tara_artist_posts_limit', -1 );

/* ترتیب کوئری بر اساس ?sort= (مرتب‌سازی الفبایی بعد از کوئری و در PHP انجام می‌شود) */
$taranehyar_orderby = ( 'old' === $taranehyar_sort ) ? 'ASC' : 'DESC';

// خبر و بیوگرافی از فهرست آثار جدا می‌شوند.
$taranehyar_editorial_categories = array();
if ( function_exists( 'get_category_by_slug' ) ) {
	foreach ( array( 'news', 'biography' ) as $taranehyar_editorial_slug ) {
		$taranehyar_editorial_term = get_category_by_slug( $taranehyar_editorial_slug );
		if ( $taranehyar_editorial_term && ! is_wp_error( $taranehyar_editorial_term ) ) {
			$taranehyar_editorial_categories[] = (int) $taranehyar_editorial_term->term_id;
		}
	}
}

$taranehyar_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $taranehyar_limit,
		'orderby'             => 'date',
		'order'               => $taranehyar_orderby,
		'ignore_sticky_posts' => true,
		'category__not_in'    => $taranehyar_editorial_categories,
		'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => tara_artist_tax(),
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
				return strcmp( tara_artist_sort_key( $title_a ), tara_artist_sort_key( $title_b ) );
			}
		);
	}

	/* v3.7.3 — سبدها همیشه به ترتیب تاریخ ساخته می‌شوند و بعد، جداگانه،
	   بر اساس انتخاب همان بخش مرتب می‌شوند (title = نام آهنگ). */
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

	/* مرتب‌سازی مستقلِ هر سبد (v3.7.3) */
	foreach ( $taranehyar_buckets as $taranehyar_bkey => $taranehyar_bitems ) {
		if ( count( $taranehyar_bitems ) < 2 ) {
			continue;
		}
		$taranehyar_mode = isset( $taranehyar_sorts[ $taranehyar_bkey ] ) ? $taranehyar_sorts[ $taranehyar_bkey ] : 'new';
		usort(
			$taranehyar_bitems,
			function ( $a, $b ) use ( $taranehyar_mode ) {
				if ( 'title' === $taranehyar_mode ) {
					$ta = (string) get_post_meta( $a->ID, 'song', true );
					$tb = (string) get_post_meta( $b->ID, 'song', true );
					if ( '' === $ta ) {
						$ta = $a->post_title;
					}
					if ( '' === $tb ) {
						$tb = $b->post_title;
					}
					$cmp = strcmp( tara_artist_sort_key( $ta ), tara_artist_sort_key( $tb ) );
					return ( 0 !== $cmp ) ? $cmp : strcmp( (string) $a->post_date, (string) $b->post_date );
				}
				$da = (int) strtotime( (string) $a->post_date );
				$db = (int) strtotime( (string) $b->post_date );
				return ( 'old' === $taranehyar_mode ) ? ( $da - $db ) : ( $db - $da );
			}
		);
		$taranehyar_buckets[ $taranehyar_bkey ] = $taranehyar_bitems;
	}
}
wp_reset_postdata();

/* ------------------------------------------------------------------
   هنرمندانِ مشابه: بر اساس بیشترین اشتراک در نوشته‌های همین هنرمند
------------------------------------------------------------------- */
$taranehyar_related = array();
if ( ! empty( $taranehyar_cfg['show_related'] ) && ! empty( $taranehyar_query->posts ) ) {
	$taranehyar_ids   = wp_list_pluck( $taranehyar_query->posts, 'ID' );
	$taranehyar_terms = wp_get_object_terms( $taranehyar_ids, tara_artist_tax(), array( 'fields' => 'all_with_object_id' ) );

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
					'taxonomy'   => tara_artist_tax(),
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

$taranehyar_directory_url = tara_artist_directory_url();
$taranehyar_term_norm     = tara_artist_normalize( $taranehyar_term->name );
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
?><?php
/* ==================================================================================
   از این‌جا فقط «نمایش» است — نسخهٔ ۳.۷.۰
   ----------------------------------------------------------------------------------
   صفحهٔ هنرمند مثل یک ایستگاه رادیو چیده شده:
     ۱) سربرگ بزرگ با تصویر، نام، آمار و دکمهٔ «پخش همه»
     ۲) پلیرِ رادیو با فهرست پخش (همهٔ آهنگ‌های صوتی همین هنرمند)
     ۳) آلبوم‌ها و موزیک‌ویدیوها به‌شکل ردیف
     ۴) بیوگرافی، دربارهٔ سایت، هنرمندان مشابه
   هیچ داده‌ای تغییر نمی‌کند؛ فقط چیدمان و مارکاپ عوض شده است.
   ================================================================================== */

/* ژانر و حال‌وهوای غالبِ این هنرمند — از خودِ آهنگ‌ها جمع می‌شود (فقط خواندن) */
$taranehyar_genre_terms = array();
$taranehyar_mood_terms  = array();
foreach ( array( 'audio', 'album', 'video', 'other' ) as $taranehyar_bucket_key ) {
	foreach ( $taranehyar_buckets[ $taranehyar_bucket_key ] as $taranehyar_bucket_post ) {
		foreach ( array( 'tara_genre' => 'genre', 'tara_mood' => 'mood' ) as $taranehyar_tax => $taranehyar_agg ) {
			if ( ! taxonomy_exists( $taranehyar_tax ) ) {
				continue;
			}
			$taranehyar_post_terms = get_the_terms( $taranehyar_bucket_post->ID, $taranehyar_tax );
			if ( ! is_array( $taranehyar_post_terms ) ) {
				continue;
			}
			foreach ( $taranehyar_post_terms as $taranehyar_post_term ) {
				if ( 'genre' === $taranehyar_agg ) {
					$taranehyar_genre_terms[ $taranehyar_post_term->term_id ] = $taranehyar_post_term;
				} else {
					$taranehyar_mood_terms[ $taranehyar_post_term->term_id ] = $taranehyar_post_term;
				}
			}
		}
	}
}

/* فقط آهنگ‌های صوتی قابل پخش‌اند */
$taranehyar_radio_tracks = function_exists( 'tara_track_playlist' ) ? tara_track_playlist( $taranehyar_buckets['audio'] ) : array();

$taranehyar_cover = '' !== $taranehyar_image_url
	? $taranehyar_image_url
	: ( function_exists( 'tara_artist_default_image' ) ? tara_artist_default_image() : get_template_directory_uri() . '/images/def-singer.webp' );

$taranehyar_bio_short = function_exists( 'tara_artist_bio_short' ) ? tara_artist_bio_short( $taranehyar_term, false ) : '';
/* v3.7.8 — بنر اختیاری سربرگ (ستون سوم). خالی ⇒ همان تصویر پروفایل. */
$taranehyar_banner = function_exists( 'tara_artist_banner_url' ) ? tara_artist_banner_url( $taranehyar_term, 'full' ) : '';
$taranehyar_bio_link  = function_exists( 'tara_artist_bio_link' ) ? tara_artist_bio_link( $taranehyar_term ) : '';
$taranehyar_about      = function_exists( 'ot_get_option' ) ? trim( (string) ot_get_option( 'about_site_text' ) ) : '';
$taranehyar_about_ttl  = function_exists( 'ot_get_option' ) ? trim( (string) ot_get_option( 'about_site_title' ) ) : '';
$taranehyar_about_on   = function_exists( 'ot_get_option' ) ? ( 'off' !== ot_get_option( 'about_show_artist' ) ) : true;
$taranehyar_about_wide = function_exists( 'ot_get_option' ) ? ( 'wide' === ot_get_option( 'about_style' ) ) : false;
if ( ! $taranehyar_about_on ) {
	$taranehyar_about = '';
}

$taranehyar_social_icons = array(
	'afacebook'  => 'icofont-facebook',
	'atelegram'  => 'icofont-paper-plane',
	'ainstagram' => 'icofont-instagram',
	'atwitter'   => 'icofont-twitter',
	'ayoutube'   => 'icofont-youtube-play',
);
?>

<div class="ty-artist tarlanweb_center" dir="rtl">

	<!-- ۱) سربرگ ------------------------------------------------------- -->
	<header class="ty-artist__hero<?php echo ( '' !== $taranehyar_banner ) ? ' has-banner' : ''; ?>">
		<div class="ty-artist__bg" aria-hidden="true">
			<img src="<?php echo esc_url( $taranehyar_cover ); ?>" alt="" loading="eager" fetchpriority="high">
		</div>

		<div class="ty-artist__identity">
			<span class="ty-artist__eyebrow"><i class="icofont-radio-mic" aria-hidden="true"></i> صفحهٔ هنرمند</span>
			<h1 class="ty-artist__name"><?php echo esc_html( $taranehyar_term->name ); ?><?php echo function_exists( 'tara_artist_verified_badge' ) ? tara_artist_verified_badge( $taranehyar_term, array( 'size' => 'lg' ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>

			<?php
			/* v3.10.0 — «دنبال‌کردن» با شمارندهٔ واقعیِ دنبال‌کننده‌ها.
			   اگر حسابِ کاربری خاموش باشد، تابع رشتهٔ خالی می‌دهد. */
			echo '<div class="ty-artist__contact">';
			if ( function_exists( 'tara_acc_follow_button' ) ) {
				echo tara_acc_follow_button( (int) $taranehyar_term->term_id, array( 'class' => 'ty-acct-follow--hero' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			if ( function_exists( 'tara_dm_button' ) ) { echo tara_dm_button( (int) $taranehyar_term->term_id ); } // phpcs:ignore WordPress.Security.EscapeOutput
			echo '</div>';
			?>

			<?php $taranehyar_latin = $taranehyar_term_field( 'artist_en' ); ?>
			<?php if ( '' !== $taranehyar_latin ) : ?>
				<p class="ty-artist__latin" lang="en" dir="ltr"><?php echo esc_html( $taranehyar_latin ); ?></p>
			<?php endif; ?>

			<?php
			/* v3.11.0 — ردیفِ آمارِ اینستاگرامی: دنبال‌کننده · بازدید · اثر.
			   مثل پروفایلِ اینستاگرام، اعداد درشت و برچسب‌ها ریز، زیرِ نام.
			   سه عددِ خواسته‌شده در یک ردیف؛ «اثر» مجموعِ صدا/آلبوم/ویدیو است. */
			$taranehyar_followers = function_exists( 'tara_acc_follower_count' ) ? (int) tara_acc_follower_count( (int) $taranehyar_term->term_id ) : 0;
			$taranehyar_views     = function_exists( 'tara_artist_total_views' ) ? (int) tara_artist_total_views( $taranehyar_term ) : 0;
			$taranehyar_works     = 0;
			foreach ( array( 'audio', 'album', 'video' ) as $taranehyar_key ) {
				$taranehyar_works += count( $taranehyar_buckets[ $taranehyar_key ] );
			}
			?>
			<ul class="ty-artist__stats ty-artist__stats--ig">
				<li>
					<strong><?php echo esc_html( tara_number_fa( $taranehyar_followers ) ); ?></strong>
					<span>دنبال‌کننده</span>
				</li>
				<li>
					<strong><?php echo esc_html( tara_number_fa( $taranehyar_views ) ); ?></strong>
					<span>بازدید</span>
				</li>
				<li>
					<strong><?php echo esc_html( tara_number_fa( $taranehyar_works ) ); ?></strong>
					<span>اثر</span>
				</li>
			</ul>

			<?php if ( ! empty( $taranehyar_genre_terms ) || ! empty( $taranehyar_mood_terms ) ) : ?>
				<div class="ty-artist__chips">
					<?php foreach ( $taranehyar_genre_terms as $taranehyar_genre_term ) : ?>
						<?php $taranehyar_genre_link = get_term_link( $taranehyar_genre_term ); ?>
						<?php if ( ! is_wp_error( $taranehyar_genre_link ) ) : ?>
							<a class="ty-chip" href="<?php echo esc_url( $taranehyar_genre_link ); ?>"><?php echo esc_html( $taranehyar_genre_term->name ); ?></a>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php foreach ( $taranehyar_mood_terms as $taranehyar_mood_term ) : ?>
						<?php $taranehyar_mood_link = get_term_link( $taranehyar_mood_term ); ?>
						<?php if ( ! is_wp_error( $taranehyar_mood_link ) ) : ?>
							<a class="ty-chip ty-chip--soft" href="<?php echo esc_url( $taranehyar_mood_link ); ?>"><?php echo esc_html( $taranehyar_mood_term->name ); ?></a>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php /* v3.7.8 — بیوگرافی و دکمهٔ «بیوگرافی کامل» به ستون دوم منتقل شدند
			   (`.ty-artist__about` پایین‌تر)، تا ستون اطلاعات فقط نام/آمار/ژانر/شبکه‌ها
			   باشد و سربرگ سه‌ستونهٔ درخواستی شکل بگیرد. */ ?>

			<div class="ty-artist__actions">
				<?php if ( ! empty( $taranehyar_radio_tracks ) ) : ?>
					<button type="button" class="ty-artist__cta" data-tara-play="0">
						<span class="ty-icon-play" aria-hidden="true"></span>
						پخش همه (رادیو)
					</button>
				<?php endif; ?>

				<?php if ( '' !== $taranehyar_directory_url ) : ?>
					<a class="ty-artist__ghost" href="<?php echo esc_url( $taranehyar_directory_url ); ?>">همهٔ هنرمندان</a>
				<?php endif; ?>

				<?php /* v3.12.0 — دایرکتِ مستقیم به هنرمندِ تأییدشده/وصل‌شده */ ?>

				<span class="ty-artist__socials">
					<?php foreach ( $taranehyar_social_icons as $taranehyar_field_key => $taranehyar_icon ) : ?>
						<?php $taranehyar_social_url = $taranehyar_term_field( $taranehyar_field_key ); ?>
						<?php if ( ! empty( $taranehyar_social_url ) ) : ?>
							<a href="<?php echo esc_url( $taranehyar_social_url ); ?>" class="<?php echo esc_attr( $taranehyar_icon ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( $taranehyar_network = str_replace( array( 'afacebook', 'atelegram', 'ainstagram', 'atwitter', 'ayoutube' ), array( 'فیسبوک', 'تلگرام', 'اینستاگرام', 'توییتر', 'یوتیوب' ), $taranehyar_field_key ) ); ?>"></a>
						<?php endif; ?>
					<?php endforeach; ?>
				</span>
			</div>
		</div>

			<?php /* ستون دوم: بیوگرافی کوتاه + دکمهٔ «بیوگرافی کامل» زیر آن */ ?>
		<div class="ty-artist__about">
			<?php if ( '' !== $taranehyar_bio_short ) : ?>
				<p class="ty-artist__bio"><?php echo esc_html( $taranehyar_bio_short ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $taranehyar_bio_link ) : ?>
				<a class="ty-artist__ghost ty-artist__bio-btn" href="<?php echo esc_url( $taranehyar_bio_link ); ?>">
					<i class="icofont-book-alt" aria-hidden="true"></i>
					بیوگرافی کامل
				</a>
			<?php endif; ?>

			<?php if ( '' === $taranehyar_bio_short && '' === $taranehyar_bio_link ) : ?>
				<p class="ty-artist__bio ty-artist__bio--empty">بیوگرافی این هنرمند هنوز ثبت نشده است.</p>
			<?php endif; ?>
		</div>

		<?php
		/* ستون سوم: بنر هنرمند اگر ثبت شده باشد؛ وگرنه همان تصویر پروفایل
		   (حالت پیش‌فرض و سازگار با نسخهٔ قبل). */
		?>
		<?php $tara_portrait_badge = function_exists( 'tara_artist_verified_badge' ) ? tara_artist_verified_badge( $taranehyar_term, array( 'size' => 'lg' ) ) : ''; ?>
		<figure class="ty-artist__portrait<?php echo ( '' !== $taranehyar_banner ) ? ' ty-artist__portrait--banner' : ''; ?>">
			<?php if ( '' !== $taranehyar_banner ) : ?>
				<img src="<?php echo esc_url( $taranehyar_banner ); ?>" width="1200" height="400" alt="" loading="eager" fetchpriority="high">
				<div class="ty-artist__banner-avatar">
					<img src="<?php echo esc_url( $taranehyar_cover ); ?>" width="120" height="120" alt="<?php echo esc_attr( $taranehyar_term->name ); ?>" loading="eager">
					<?php if ( '' !== $tara_portrait_badge ) : ?>
						<span class="ty-artist__portrait-verified" aria-hidden="true"><?php echo $tara_portrait_badge; // phpcs:ignore WordPress.Security.EscapeOutput -- خروجی داخلی قالب ?></span>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<img src="<?php echo esc_url( $taranehyar_cover ); ?>" width="240" height="240" alt="<?php echo esc_attr( $taranehyar_term->name ); ?>" loading="eager" fetchpriority="high">
				<?php if ( '' !== $tara_portrait_badge ) : ?>
					<span class="ty-artist__portrait-verified" aria-hidden="true"><?php echo $tara_portrait_badge; // phpcs:ignore WordPress.Security.EscapeOutput -- خروجی داخلی قالب ?></span>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( $taranehyar_found > 0 ) : ?>
				<figcaption><?php echo esc_html( sprintf( '%s اثر در سایت', tara_number_fa( $taranehyar_found ) ) ); ?></figcaption>
			<?php endif; ?>
		</figure>
	</header>

	<!-- ۲) رادیو ------------------------------------------------------- -->
	<?php if ( ! empty( $taranehyar_radio_tracks ) ) : ?>
		<?php
		echo tara_player( // phpcs:ignore WordPress.Security.EscapeOutput -- خروجی همین قالب، escape شده در تابع
			array(
				'tracks'   => $taranehyar_radio_tracks,
				'title'    => 'رادیو ' . $taranehyar_term->name,
				'cover'    => $taranehyar_cover,
				'context'  => 'artist',
			)
		);
		?>
	<?php endif; ?>

	<!-- ۲٫۵) فهرست آهنگ‌ها با ترتیب مستقل ------------------------------ -->
	<?php if ( ! empty( $taranehyar_buckets['audio'] ) ) : ?>
		<section class="ty-artist__section" id="artist-audio">
			<header class="ty-artist__section-head">
				<h2><?php echo esc_html( $taranehyar_labels['audio'] ); ?></h2>
				<span class="ty-artist__section-count"><?php echo esc_html( tara_number_fa( count( $taranehyar_buckets['audio'] ) ) ); ?></span>
				<?php
				/* نوار «ترتیب» همین بخش — با پارامتر sort_audio، مستقل از بخش‌های دیگر */
				if ( ! empty( $taranehyar_cfg['show_sort'] ) && count( $taranehyar_buckets['audio'] ) > 1 ) {
					echo tara_artist_sort_nav( 'audio', $taranehyar_sorts['audio'], $taranehyar_sort_labels, '#artist-audio' ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</header>
			<?php
			/* v3.7.8 — رفع باگ «دکمهٔ پخش فهرست آهنگ‌ها کار نمی‌کند»:
			   ردیف‌ها ایندکس عددی نداشتند و صفی هم روی <ul> نبود، پس JS نمی‌دانست
			   کدام قطعه را پخش کند. حالا صف همین بخش ساخته می‌شود و هر ردیف
			   ایندکس واقعیِ خودش را در همان صف می‌گیرد (شناسه‌محور، پس اگر قطعه‌ای
			   فایل صوتی نداشته باشد بقیه جابه‌جا نمی‌شوند). */
			$taranehyar_audio_tracks = function_exists( 'tara_track_playlist' )
				? tara_track_playlist( $taranehyar_buckets['audio'] )
				: array();
			$taranehyar_audio_index  = array();
			foreach ( $taranehyar_audio_tracks as $taranehyar_ti => $taranehyar_tr ) {
				$taranehyar_audio_index[ (string) $taranehyar_tr['id'] ] = $taranehyar_ti;
			}
			?>
			<ul class="ty-tracklist"<?php echo function_exists( 'tara_queue_attrs' ) ? tara_queue_attrs( $taranehyar_audio_tracks, $taranehyar_labels['audio'] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<?php foreach ( $taranehyar_buckets['audio'] as $taranehyar_post ) : ?>
					<?php $taranehyar_row_i = isset( $taranehyar_audio_index[ (string) $taranehyar_post->ID ] ) ? $taranehyar_audio_index[ (string) $taranehyar_post->ID ] : -1; ?>
					<li><?php echo tara_track_row( $taranehyar_post->ID, array( 'index' => $taranehyar_row_i, 'badge' => isset( $taranehyar_resume_labels['audio'] ) ? $taranehyar_resume_labels['audio'] : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<!-- ۳) آلبوم/ویدیو (غیرقابل پخش در پلیر) -------------------------- -->
	<?php
	$taranehyar_visual_buckets = array(
		'album' => isset( $taranehyar_labels['album'] ) ? $taranehyar_labels['album'] : 'آلبوم‌ها',
		'video' => isset( $taranehyar_labels['video'] ) ? $taranehyar_labels['video'] : 'موزیک‌ویدیوها',
		'other' => isset( $taranehyar_labels['other'] ) ? $taranehyar_labels['other'] : 'سایر',
	);
	foreach ( $taranehyar_visual_buckets as $taranehyar_key => $taranehyar_title ) :
		if ( empty( $taranehyar_buckets[ $taranehyar_key ] ) ) :
			continue;
		endif;
		?>
		<section class="ty-artist__section" id="artist-<?php echo esc_attr( $taranehyar_key ); ?>">
			<header class="ty-artist__section-head">
				<h2><?php echo esc_html( $taranehyar_title ); ?></h2>
				<span class="ty-artist__section-count"><?php echo esc_html( tara_number_fa( count( $taranehyar_buckets[ $taranehyar_key ] ) ) ); ?></span>
				<?php
				if ( ! empty( $taranehyar_cfg['show_sort'] ) && count( $taranehyar_buckets[ $taranehyar_key ] ) > 1 ) {
					echo tara_artist_sort_nav( $taranehyar_key, $taranehyar_sorts[ $taranehyar_key ], $taranehyar_sort_labels, '#artist-' . $taranehyar_key ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</header>
			<ul class="ty-tracklist">
				<?php foreach ( $taranehyar_buckets[ $taranehyar_key ] as $taranehyar_post ) : ?>
					<li><?php echo tara_track_row( $taranehyar_post->ID, array( 'badge' => isset( $taranehyar_resume_labels[ $taranehyar_key ] ) ? $taranehyar_resume_labels[ $taranehyar_key ] : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endforeach; ?>

	<?php if ( 0 === $taranehyar_found ) : ?>
		<section class="ty-artist__section ty-artist__empty">
			<h2>هنوز آهنگی برای این هنرمند ثبت نشده است</h2>
			<p>اگر آثار این هنرمند در سایت هست، در ویرایش هر نوشته فیلد «هنرمند» را به همین نام انتخاب کنید.</p>
		</section>
	<?php endif; ?>

	<!-- ۳٫۵) بیوگرافیِ کامل + تازه‌ترین خبرها/آثار ----------------------- -->
	<section class="ty-artist__section ty-artist__bio-news">
		<div class="ty-artist__bio-col">
			<header class="ty-artist__section-head"><h2>بیوگرافیِ <?php echo esc_html( $taranehyar_term->name ); ?></h2></header>
			<?php $taranehyar_full_bio = function_exists( 'get_the_archive_description' ) ? get_the_archive_description() : ''; ?>
			<?php if ( ! empty( $taranehyar_full_bio ) ) : ?>
				<div class="ty-artist__card-text ty-artist__bio-full"><?php echo wp_kses_post( $taranehyar_full_bio ); ?></div>
			<?php elseif ( '' !== $taranehyar_bio_short ) : ?>
				<div class="ty-artist__card-text"><?php echo wpautop( esc_html( $taranehyar_bio_short ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php else : ?>
				<p class="ty-artist__bio ty-artist__bio--empty">بیوگرافیِ این هنرمند هنوز ثبت نشده است؛ از پنلِ هنرمند می‌توان برای تکمیلِ آن درخواست داد.</p>
			<?php endif; ?>
			<?php if ( '' !== $taranehyar_bio_link ) : ?><a class="ty-hub__link" href="<?php echo esc_url( $taranehyar_bio_link ); ?>">مطالعهٔ بیوگرافی کامل</a><?php endif; ?>
		</div>
		<aside class="ty-artist__news-col">
			<header class="ty-artist__section-head"><h2>تازه‌ترین‌ها</h2></header>
			<?php
			$taranehyar_news = function_exists( 'get_posts' ) ? get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 3, 'orderby' => 'date', 'order' => 'DESC', 'category_name' => 'news', 'tax_query' => array( array( 'taxonomy' => $taranehyar_term->taxonomy, 'field' => 'term_id', 'terms' => (int) $taranehyar_term->term_id ) ) ) ) : array();
			?>
			<?php if ( ! empty( $taranehyar_news ) ) : ?>
				<ul class="ty-artist__news">
				<?php foreach ( $taranehyar_news as $taranehyar_news_post ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $taranehyar_news_post ) ); ?>"><?php echo esc_html( get_the_title( $taranehyar_news_post ) ); ?></a><time><?php echo esc_html( get_the_date( '', $taranehyar_news_post ) ); ?></time></li>
				<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="ty-artist__bio ty-artist__bio--empty">هنوز خبری برای این هنرمند ثبت نشده است.</p>
			<?php endif; ?>
		</aside>
	</section>

	<!-- ۴) درباره + هنرمندان مشابه ------------------------------------ -->
	<div class="ty-artist__grid">
		<?php $taranehyar_description = get_the_archive_description(); ?>
		<?php if ( ! empty( $taranehyar_description ) && empty( $taranehyar_full_bio ) ) : ?>
			<section class="ty-artist__card">
				<h2>دربارهٔ <?php echo esc_html( $taranehyar_term->name ); ?></h2>
				<div class="ty-artist__card-text"><?php echo wp_kses_post( $taranehyar_description ); ?></div>
			</section>
		<?php endif; ?>

		<?php if ( '' !== $taranehyar_about ) : ?>
			<section class="ty-artist__card<?php echo $taranehyar_about_wide ? ' ty-artist__card--wide' : ''; ?>">
				<h2><?php echo esc_html( '' !== $taranehyar_about_ttl ? $taranehyar_about_ttl : 'دربارهٔ سایت' ); ?></h2>
				<div class="ty-artist__card-text"><?php echo wp_kses_post( wpautop( $taranehyar_about ) ); ?></div>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $taranehyar_related ) ) : ?>
			<section class="ty-artist__card ty-artist__card--wide">
				<h2>هنرمندان مشابه</h2>
				<ul class="ty-artist__related">
					<?php foreach ( $taranehyar_related as $taranehyar_related_term ) : ?>
						<?php $taranehyar_related_link = get_term_link( $taranehyar_related_term ); ?>
						<?php if ( is_wp_error( $taranehyar_related_link ) ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<li>
							<a href="<?php echo esc_url( $taranehyar_related_link ); ?>">
								<span class="ty-artist__related-name"><?php echo esc_html( $taranehyar_related_term->name ); ?></span>
								<span class="ty-artist__related-count"><?php echo esc_html( tara_number_fa( (int) $taranehyar_related_term->count ) ); ?> اثر</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	</div>

</div>

<?php
/* ----------------------------------------------------------------------------------
   داده‌ساختاریافته: هنرمند (MusicGroup) + فهرست آثار
---------------------------------------------------------------------------------- */
if ( $taranehyar_found > 0 ) {
	$taranehyar_term_link = get_term_link( $taranehyar_term );
	$taranehyar_term_link = is_wp_error( $taranehyar_term_link ) ? '' : $taranehyar_term_link;

	$taranehyar_graph  = array();
	$taranehyar_artist = array(
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
