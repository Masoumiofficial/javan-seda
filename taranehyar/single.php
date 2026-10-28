<?php get_header(''); ?>
<?php get_template_part('inc/breadcrumbs'); ?>
<?php if((ot_get_option('ads_bt') !="") || (ot_get_option('adsjs_bt') !="")){ ?>
<div id="ads_top">
<?php echo ot_get_option('ads_bt'); ?><?php echo ot_get_option('adsjs_bt'); ?>
<div class="clear"></div>
</div>
<?php } ?>
<!-- centers_box -->
<div class="tarlanweb_center">
<div id="centers_box">

<!-- single_posts_right -->
<div class="single_posts_right">
<?php if(have_posts()) : while(have_posts()) : the_post(); ?>
<article class="single_posts tarlanweb_post_box">
<header>
<h1><?php the_title(); ?></h1> 
<div class="stars_ratings_box">
<?php /* v3.1.0 — محافظ خطای کشنده: include و توابع اداری فقط در صورت وجود؛ ستاره‌دهی هم فقط اگر تابعش موجود باشد (قبلاً همین خط می‌توانست کل بلوک محتوا را نصفه‌کاره بکشد) */
if ( ! function_exists( 'is_plugin_active' ) ) {
	$ab_plug_api = ABSPATH . 'wp-admin/includes/plugin.php';
	if ( is_readable( $ab_plug_api ) ) {
		include_once $ab_plug_api;
	}
	unset( $ab_plug_api );
}
if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'kk-star-ratings/index.php' ) && function_exists( 'kk_star_ratings' ) ) { echo kk_star_ratings(); } ?>
</div>
<div class="liner clear"></div>
<div class="tarlanweb_post_ft"><i class="icofont-folder-open"></i> <?php the_category(' , '); ?></div>
<?php if(function_exists('the_views')){ ?>
<div class="tarlanweb_post_ft"><i class="icofont-eye-alt"></i> <?php the_views(); ?></div><?php } ?>
<div class="tarlanweb_post_ft"><i class="icofont-comment"></i> <?php comments_number('0','1','%'); ?></div><?php edit_post_link('ویرایش مطلب'); ?>
<div class="clear"></div>

<?php
/* نسخهٔ ۳.۷.۰ — نوار شناسهٔ آهنگ زیر تیتر:
   ژانر · حال‌وهوا · ترانه‌سرا · آهنگساز · تنظیم. همه از تاکسونومی‌های تازه
   می‌آیند و هر چیپ به آرشیو همان ترم لینک است. اگر ترمی ثبت نشده باشد،
   چیزی چاپ نمی‌شود (بدون ردیف خالی). */
/* v3.7.3 — ترانه‌سرا / آهنگساز / تنظیم از این نوار حذف شدند: این سه فقط
   در «شناسنامهٔ اثر» می‌آیند (درخواست صریح: یک‌جا، نه دو جا). ژانر و حال‌وهوا
   در شناسنامه نیستند، پس همان‌جا می‌مانند. */
$tara_meta_rows = array(
	'tara_genre' => array( 'label' => 'ژانر' ),
	'tara_mood'  => array( 'label' => 'حال‌وهوا' ),
);
$tara_meta_html = '';
foreach ( $tara_meta_rows as $tara_tax => $tara_conf ) {
	if ( function_exists( 'tara_term_chips' ) ) {
		$tara_meta_html .= tara_term_chips( $tara_tax, get_the_ID(), $tara_conf['label'] );
	}
}
if ( '' !== trim( wp_strip_all_tags( $tara_meta_html ) ) ) {
	echo '<div class="ty-single-meta">' . $tara_meta_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- در تابع escape شده است
}
?>
</header>

<div class="single_content_box">

<?php
/* ---------- نام آهنگ / هنرمند / لینک صفحهٔ هنرمند (مشترک بین باکس متن ترانه و تیتر دانلود) ----------
   لینک هنرمند روی تاکسونومی هنرمند ساخته می‌شود؛ نامک فعال را tara_artist_tax() می‌دهد
   مثال: «محسن یگانه» → https://site/artist/mohsen-yeganeh
   ترتیب جست‌وجو: فیلد اختیاری artist_link روی خود نوشته ← ترم هنرمند با همان نام ← با اسلاگ ←
   جست‌وجوی تقریبی با نرمال‌سازی ي/ك/ة و نیم‌فاصله ← برچسب هم‌نام.
   در پایان، فیلتر taranehyar_artist_url می‌تواند نتیجه را بازنویسی کند.
------------------------------------------------------------------------------------------------- */
$taranehyar_song   = function_exists( 'get_field' ) ? trim( (string) get_field( 'song' ) ) : '';
$taranehyar_artist = function_exists( 'get_field' ) ? trim( (string) get_field( 'artist' ) ) : '';
if ( '' === $taranehyar_song ) {
	$taranehyar_song = get_the_title();
}

/* برچسب نوع (آهنگ/آلبوم/موزیک ویدیو) از فیلد musics_type؛ پیش‌فرض «آهنگ» */
$taranehyar_type_raw = function_exists( 'get_field' ) ? get_field( 'musics_type' ) : '';
$taranehyar_type     = is_scalar( $taranehyar_type_raw ) ? trim( (string) $taranehyar_type_raw ) : '';
if ( '' === $taranehyar_type ) {
	$taranehyar_type = 'آهنگ';
}

$taranehyar_artist_url = '';
if ( '' !== $taranehyar_artist ) {
	$taranehyar_custom_link = function_exists( 'get_field' ) ? get_field( 'artist_link' ) : '';
	if ( is_string( $taranehyar_custom_link ) && '' !== trim( $taranehyar_custom_link ) ) {
		$taranehyar_artist_url = trim( $taranehyar_custom_link );
	} else {
		$taranehyar_lookups = array(
			array( 'name', $taranehyar_artist, tara_artist_tax() ),
			array( 'slug', sanitize_title( $taranehyar_artist ), tara_artist_tax() ),
		);
		foreach ( $taranehyar_lookups as $taranehyar_lookup ) {
			if ( '' === $taranehyar_lookup[1] ) {
				continue;
			}
			$taranehyar_term = get_term_by( $taranehyar_lookup[0], $taranehyar_lookup[1], $taranehyar_lookup[2] );
			if ( $taranehyar_term && ! is_wp_error( $taranehyar_term ) ) {
				$taranehyar_term_link = get_term_link( $taranehyar_term );
				if ( ! is_wp_error( $taranehyar_term_link ) ) {
					$taranehyar_artist_url = $taranehyar_term_link;
					break;
				}
			}
		}
		if ( '' === $taranehyar_artist_url ) {
			/* جست‌وجوی تقریبی: ی/ي، ک/ك، ه/ة و نیم‌فاصله یکسان‌سازی می‌شوند */
			$taranehyar_normalize = function ( $value ) {
				$value = str_replace( array( 'ي', 'ك', 'ة', "\xE2\x80\x8C" ), array( 'ی', 'ک', 'ه', ' ' ), (string) $value ); /* آخری = فاصلهٔ مجازی (ZWNJ) */
				return trim( preg_replace( '/\s+/u', ' ', $value ) );
			};
			$taranehyar_needle = $taranehyar_normalize( $taranehyar_artist );
			foreach ( array( tara_artist_tax(), 'post_tag' ) as $taranehyar_tax ) {
				$taranehyar_matches = get_terms(
					array(
						'taxonomy'   => $taranehyar_tax,
						'hide_empty' => false,
						'number'     => 20,
						'name__like' => $taranehyar_needle,
					)
				);
				if ( is_array( $taranehyar_matches ) ) {
					foreach ( $taranehyar_matches as $taranehyar_match ) {
						if ( $taranehyar_normalize( $taranehyar_match->name ) === $taranehyar_needle ) {
							$taranehyar_term_link = get_term_link( $taranehyar_match );
							if ( ! is_wp_error( $taranehyar_term_link ) ) {
								$taranehyar_artist_url = $taranehyar_term_link;
								break 2;
							}
						}
					}
				}
			}
		}
	}
	$taranehyar_artist_url = apply_filters( 'taranehyar_artist_url', $taranehyar_artist_url, $taranehyar_artist );
}
?>

<?php
/* ==================================================================================
   v3.7.8 — ترتیب تازهٔ صفحهٔ آهنگ (درخواست صریح):
     پلیر (جای کاور بزرگ) → «ما را دنبال کنید» → توضیحات پست →
     مشخصات اثر → ویدیو → لینک دانلود → متن ترانه
   ----------------------------------------------------------------------------------
   کاور بزرگ بالای صفحه حذف شد؛ همان تصویر حالا داخل خود پلیر است و یک آیکون
   کوچک دوربین روی آن، نسخهٔ اصلی و باکیفیت را در پاپ‌آپ نشان می‌دهد
   (`data-tara-zoom`). هیچ بخشی از DOM حذف نشده، فقط جابه‌جا شده است.
   ================================================================================== */
?>

<?php
/* ==================================================================================
   پلیر «رادیو» — نسخهٔ ۳.۷.۰
   ----------------------------------------------------------------------------------
   جای دو بلوک قدیمی (پلیر تک‌آهنگ و پلیر آلبوم) یک کارت واحد نشسته است:
     • آلبوم: هر قطعهٔ تکرارشونده یک ردیف در فهرست پخش می‌شود.
     • تک‌آهنگ: همان یک قطعه با کاور بزرگ.
   مارکاپ و کنترل‌ها در inc/tara-ui.php ساخته می‌شوند و js/tara-player.js آن را
   می‌گرداند؛ هیچ وابستگی به jQuery ندارد. اگر تابع در دسترس نبود (مثلاً فایل
   حذف شده باشد) هیچ خروجی و هیچ خطایی نداریم.
   ================================================================================== */
$tara_tracks     = array();
$tara_cover_big  = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'thumb2x' ) : '';
/* v3.7.8 — نسخهٔ اصلی تصویر برای پاپ‌آپ دوربین روی کاور پلیر */
$tara_cover_full = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'full' ) : '';
$tara_artist_big = $taranehyar_artist;
$tara_song_big   = $taranehyar_song;
$tara_link_big   = get_permalink();

if ( function_exists( 'have_rows' ) && have_rows( 'album' ) ) {
	$tara_album_index = 0;
	while ( have_rows( 'album' ) ) {
		the_row();
		$tara_album_index++;
		$tara_album_src = get_sub_field( 'albumlink320' );
		if ( ! $tara_album_src ) {
			$tara_album_src = get_sub_field( 'albumlink128' );
		}
		$tara_album_name = get_sub_field( 'song_names' );
		if ( ! $tara_album_src ) {
			continue;
		}
		$tara_tracks[] = array(
			'id'     => get_the_ID() . '-' . $tara_album_index,
			'title'  => $tara_album_name ? $tara_album_name : ( $tara_song_big . ' — قطعهٔ ' . $tara_album_index ),
			'artist' => $tara_artist_big,
			'src'    => $tara_album_src,
			'cover'  => $tara_cover_big ? $tara_cover_big : tara_track_cover( get_the_ID(), 'thumb2' ),
			'link'   => $tara_link_big,
		);
	}
}

if ( empty( $tara_tracks ) && function_exists( 'tara_track_src' ) ) {
	$tara_single_src = tara_track_src( get_the_ID() );
	if ( '' !== $tara_single_src ) {
		$tara_tracks[] = array(
			'id'     => get_the_ID(),
			'title'  => $tara_song_big,
			'artist' => $tara_artist_big,
			'src'    => $tara_single_src,
			'cover'  => $tara_cover_big ? $tara_cover_big : tara_track_cover( get_the_ID(), 'thumb2' ),
			'link'   => $tara_link_big,
		);
	}
}

if ( ! empty( $tara_tracks ) && function_exists( 'tara_player' ) ) {
	echo tara_player( // phpcs:ignore WordPress.Security.EscapeOutput -- خروجی همین قالب
		array(
			'tracks'  => $tara_tracks,
			'title'   => ( count( $tara_tracks ) > 1 ) ? 'آلبوم · ' . $tara_song_big : $taranehyar_type,
			'cover'   => $tara_cover_big,
			'zoom'    => $tara_cover_full,
			'context' => 'single',
		)
	);
}
?>

<?php
/* v3.7.8 — اگر این آهنگ هیچ فایل صوتی نداشته باشد، پلیر چیزی چاپ نمی‌کند؛
   در آن حالت همان کاور (با دکمهٔ دوربین) می‌ماند تا صفحه بدون تصویر نشود. */
if ( empty( $tara_tracks ) && has_post_thumbnail() ) {
	$tara_fallback_full = get_the_post_thumbnail_url( get_the_ID(), 'full' );
	?>
	<figure class="ty-cover-fallback">
		<?php the_post_thumbnail( 'thumb2x', array( 'alt' => get_the_title(), 'title' => get_the_title() ) ); ?>
		<?php if ( $tara_fallback_full ) : ?>
			<button type="button" class="ty-player__zoom ty-cover-fallback__zoom" data-tara-zoom="<?php echo esc_url( $tara_fallback_full ); ?>" aria-label="نمایش کاور در اندازهٔ بزرگ" title="نمایش کاور در اندازهٔ بزرگ">
				<i class="icofont-camera" aria-hidden="true"></i>
			</button>
		<?php endif; ?>
	</figure>
	<?php
}
?>

<?php /* «ما را دنبال کنید» — v3.7.8: بلافاصله بعد از پلیر و پیش از توضیحات.
   تابع در inc/ab-post-extras.php است و با function_exists محافظت شده؛
   نبودن فایل ⇒ صفر خروجی و صفر خطا. لینک‌ها و متن دکمه‌ها از تنظیمات قالب →
   «شبکه‌های اجتماعی» می‌آیند و با همان گزینهٔ fixbtn روشن/خاموش می‌شوند. */
if ( function_exists( 'tara_social_box' ) ) { tara_social_box( get_the_ID() ); }
?>


<?php the_content(''); ?>

<?php
/* v3.7.8 — «مشخصات اثر» دقیقاً یک‌جا و بعد از توضیحات می‌آید.
   خودِ پلیر بالای صفحه است، پس تنظیم قدیمی «شناسنامه بالای پلیر» دیگر
   کاربردی ندارد؛ جای دیگر با فیلتر tara_credits_position ممکن است. */
if ( function_exists( 'tara_credits_box' ) ) { tara_credits_box( get_the_ID() ); }
?>

<?php
/* ============================ باکس «متن ترانه» (متن آهنگ) ============================
   منبع متن: فیلد music_text  (ویرایشگر/WYSIWYG یا متن ساده — هر دو پشتیبانی می‌شود)
   تنظیم‌ها (سه خط زیر؛ جایگاه همچنین از تنظیمات قالب قابل تغییر است):
     $taranehyar_lyrics_position : 'here' = همین‌جا بعد از توضیحات | 'end' = بعد از لینک‌های دانلود
     از نسخۀ 3.3.2 پیش‌فرض 'end' است و از تنظیمات قالب → «صفحة دانلود آهنگ» →
     «متن ترانه زیر لینک دانلود» قابل برگرداندن است (بدون ویرایش فایل).
     $taranehyar_lyrics_schema   : true = چاپ داده ساختاریافته (MusicRecording + MusicComposition)
     $taranehyar_artist_type     : 'Person' هنرمندِ تک‌خوان | 'MusicGroup' گروه
   ==================================================================================== */
/* v3.3.2 — ترتیب جدید: پلیر و لینک‌های دانلود اول، متن ترانه بعد.
   دلیل: «شنیدن/دانلود» قصد اصلی بازدیدکننده است و روی موبایل متن بلند،
   دکمۀ پخش را از دید پنهان می‌کرد. متن از DOM حذف نشده (فقط جابه‌جا) و
   schema هم مثل قبل روی wp_head چاپ می‌شود، پس سئو نمی‌شکند.
   خاموشی: تنظیمات قالب → «متن ترانه زیر لینک دانلود» = خاموش
   هوک: add_filter( 'taranehyar_lyrics_position', function( $p ) { return 'here'; } ); */
$taranehyar_lyrics_position = 'end';
if ( function_exists( 'ot_get_option' ) && 'off' === ot_get_option( 'lyrics_after_download' ) ) {
	$taranehyar_lyrics_position = 'here';
}
$taranehyar_lyrics_position = apply_filters( 'taranehyar_lyrics_position', $taranehyar_lyrics_position );
$taranehyar_lyrics_schema   = true;
$taranehyar_artist_type     = 'Person';

$taranehyar_lyrics_box = '';
$taranehyar_lyrics_raw = function_exists( 'get_field' ) ? get_field( 'music_text' ) : '';
if ( is_string( $taranehyar_lyrics_raw ) && '' !== trim( $taranehyar_lyrics_raw ) ) {

	/* خروجی ایمن: اگر مقدار HTML باشد با wp_kses_post، وگرنه با nl2br + esc_html */
	if ( false !== strpos( $taranehyar_lyrics_raw, '<' ) ) {
		$taranehyar_lyrics_html = wp_kses_post( $taranehyar_lyrics_raw );
	} else {
		$taranehyar_lyrics_html = nl2br( esc_html( $taranehyar_lyrics_raw ) );
	}

	/* نسخهٔ متن ساده برای داده ساختاریافته (خط‌ها و بندها حفظ می‌شوند) */
	$taranehyar_lyrics_plain = wp_strip_all_tags( str_replace( array( '</p>', '<br />', '<br>', '<br/>' ), "\n", $taranehyar_lyrics_html ) );
	$taranehyar_lyrics_plain = trim( preg_replace( '/[ \t]+/u', ' ', $taranehyar_lyrics_plain ) );
	$taranehyar_lyrics_plain = preg_replace( "/\n{3,}/u", "\n\n", $taranehyar_lyrics_plain );

	ob_start();
	?>
	<section class="music_lyrics_box" aria-labelledby="taranehyar_lyrics_title" lang="fa" dir="rtl">
		<div class="music_lyrics_head">
			<h2 class="music_lyrics_title" id="taranehyar_lyrics_title">
				<i class="icofont-quote-right" aria-hidden="true"></i>
				متن آهنگ <span class="music_lyrics_song"><?php echo esc_html( $taranehyar_song ); ?></span><?php if ( '' !== $taranehyar_artist ) { ?> از <span class="music_lyrics_artist"><?php echo esc_html( $taranehyar_artist ); ?></span><?php } ?>
			</h2>
			<div class="music_lyrics_actions">
				<?php if ( '' !== $taranehyar_artist_url ) { ?>
				<a class="music_lyrics_artist_link" href="<?php echo esc_url( $taranehyar_artist_url ); ?>"><i class="icofont-radio-mic" aria-hidden="true"></i>سایر آهنگ‌های <?php echo esc_html( $taranehyar_artist ); ?></a>
				<?php } ?>
				<button type="button" class="music_lyrics_copy" id="taranehyar_lyrics_copy" aria-label="کپی متن ترانه"><i class="icofont-quote-left" aria-hidden="true"></i><span class="lbl">کپی متن ترانه</span></button>
			</div>
		</div>
		<div class="music_lyrics_text" id="taranehyar_lyrics_text"><?php echo $taranehyar_lyrics_html; // خروجی با wp_kses_post یا esc_html پاک‌سازی شده است. ?></div>
		<?php
		if ( $taranehyar_lyrics_schema && ! function_exists( 'tara_music_schema' ) ) {
		/* v3.2.2 — اگر inc/ab-credits.php نصب باشد، schema از همان فایل و در wp_head چاپ می‌شود
		   (برای همهٔ نوشته‌ها، حتی وقتی متن ترانه خالی است) و این بلوکِ قدیمی اجرا نمی‌شود تا دو بار چاپ نشود. */
			$taranehyar_permalink = get_permalink();
			$taranehyar_audio     = '';
			foreach ( array( 'music320', 'music128' ) as $taranehyar_audio_key ) {
				$taranehyar_audio_val = get_field( $taranehyar_audio_key );
				if ( is_string( $taranehyar_audio_val ) && '' !== trim( $taranehyar_audio_val ) ) {
					$taranehyar_audio = $taranehyar_audio_val;
					break;
				}
			}
			$taranehyar_graph = array(
				'@context' => 'https://schema.org',
				'@graph'   => array(
					array(
						'@type'            => 'MusicRecording',
						'@id'              => $taranehyar_permalink . '#recording',
						'name'             => $taranehyar_song,
						'url'              => $taranehyar_permalink,
						'inLanguage'       => 'fa-IR',
						'byArtist'         => array(
							'@type' => $taranehyar_artist_type,
							'name'  => $taranehyar_artist,
							'url'   => '' !== $taranehyar_artist_url ? $taranehyar_artist_url : $taranehyar_permalink,
						),
						'datePublished'    => get_the_date( 'c' ),
						'dateModified'     => get_the_modified_date( 'c' ),
						'recordingOf'      => array( '@id' => $taranehyar_permalink . '#composition' ),
						'mainEntityOfPage' => array( '@id' => $taranehyar_permalink . '#webpage' ),
					),
					array(
						'@type'      => 'MusicComposition',
						'@id'        => $taranehyar_permalink . '#composition',
						'name'       => $taranehyar_song,
						'inLanguage' => 'fa-IR',
						'lyricist'   => array(
							'@type' => 'Person',
							'name'  => $taranehyar_artist,
						),
						'lyrics'     => array(
							'@type'      => 'CreativeWork',
							'text'       => $taranehyar_lyrics_plain,
							'inLanguage' => 'fa-IR',
						),
						'recordedAs' => array( '@id' => $taranehyar_permalink . '#recording' ),
					),
					array(
						'@type'      => 'WebPage',
						'@id'        => $taranehyar_permalink . '#webpage',
						'url'        => $taranehyar_permalink,
						'name'       => get_the_title(),
						'inLanguage' => 'fa-IR',
						'mainEntity' => array( '@id' => $taranehyar_permalink . '#recording' ),
					),
				),
			);
			if ( '' !== $taranehyar_audio ) {
				$taranehyar_graph['@graph'][0]['audio'] = array(
					'@type'          => 'AudioObject',
					'contentUrl'     => $taranehyar_audio,
					'encodingFormat' => 'audio/mpeg',
				);
			}
			/* wp_json_encode پیش‌فرض «/» را \/ می‌کند تا محتوای متن نتواند از تگ script خارج شود. */
			?>
			<script type="application/ld+json"><?php echo wp_json_encode( $taranehyar_graph, JSON_UNESCAPED_UNICODE ); ?></script>
			<?php
		}
		?>
	</section>
	<script>
	(function () {
		var btn = document.getElementById('taranehyar_lyrics_copy');
		var box = document.getElementById('taranehyar_lyrics_text');
		if (!btn || !box) { return; }
		if (!navigator.clipboard) { btn.style.display = 'none'; return; }
		btn.addEventListener('click', function () {
			navigator.clipboard.writeText(box.innerText).then(function () {
				var lbl = btn.querySelector('.lbl');
				btn.classList.add('is-done');
				if (lbl) { lbl.textContent = 'کپی شد'; }
				window.setTimeout(function () {
					btn.classList.remove('is-done');
					if (lbl) { lbl.textContent = 'کپی متن ترانه'; }
				}, 2200);
			});
		});
	})();
	</script>
	<?php
	$taranehyar_lyrics_box = ob_get_clean();
}

if ( '' !== $taranehyar_lyrics_box && 'end' !== $taranehyar_lyrics_position ) {
	echo $taranehyar_lyrics_box; // خروجی همین بالا ساخته و escape شده است.
}
?>
<div class="clear"></div>

<?php
/* v3.7.3 — پلیر ویدیوی تازه (inc/tara-ui.php → tara_video): کاور، دکمهٔ بزرگ،
   نوار زمان، سرعت پخش، انتخاب کیفیت، Picture-in-Picture و تمام‌صفحه.
   اگر هیچ‌کدام از فیلدها ست نشده باشد، خروجی صفر است. */
if ( function_exists( 'tara_video' ) ) {
	$tara_video_sources = array();
	foreach ( array( '1080' => 'video1080', '720' => 'video720', '480' => 'video480' ) as $tara_q => $tara_f ) {
		$tara_v = get_field( $tara_f );
		if ( ! empty( $tara_v ) ) {
			$tara_video_sources[ $tara_q ] = $tara_v;
		}
	}
	if ( $tara_video_sources ) {
		echo tara_video( // phpcs:ignore WordPress.Security.EscapeOutput -- خروجی همین قالب
			array(
				'sources' => $tara_video_sources,
				'poster'  => get_the_post_thumbnail_url( get_the_ID(), 'large' ),
				'title'   => get_the_title(),
			)
		);
	}
}
?>

<?php if((ot_get_option('ads_sg') !="") || (ot_get_option('adsjs_sg') !="")){ ?>
<div id="ads_top_dl"><?php echo ot_get_option('ads_sg'); ?><?php echo ot_get_option('adsjs_sg'); ?><div class="clear"></div></div>
<?php } ?>


<?php
/* ---------- تیتر بخش دانلود (هدف جست‌وجوی «دانلود آهنگ X از Y») ----------
   برای خاموش کردن این تیتر، خط زیر را false کنید. */
$taranehyar_download_title = true;
if ( $taranehyar_download_title && (
	get_field( 'music128' ) || get_field( 'music320' ) || get_field( 'album128' ) || get_field( 'album320' ) ||
	get_field( 'video480' ) || get_field( 'video720' ) || get_field( 'video1080' )
) ) {
	?>
	<h2 class="dl_title">
		<i class="icofont-download" aria-hidden="true"></i>دانلود <span class="dl_title_type"><?php echo esc_html( $taranehyar_type ); ?></span> <span class="dl_title_song"><?php echo esc_html( $taranehyar_song ); ?></span><?php if ( '' !== $taranehyar_artist ) { ?> از <span class="dl_title_artist"><?php echo esc_html( $taranehyar_artist ); ?></span><?php } ?>
	</h2>
	<?php
}
?>
<div class="dl_links_box">
<?php if(get_field('music128')){ ?> 
<a class="dl_link_128" href="<?php echo esc_url( get_field('music128') ); ?>"> <i class="icofont-download"></i>دانلود  آهنگ با کیفیت 128 </a><?php } if(get_field('music320')){ ?>
<a class="dl_links_320" href="<?php echo esc_url( get_field('music320') ); ?>"> <i class="icofont-download"></i> دانلود آهنگ با کیفیت 320 </a><?php } if(get_field('album128')){ ?>	
<a class="dl_link_128" href="<?php echo esc_url( get_field('album128') ); ?>"> <i class="icofont-download"></i>دانلود  آلبوم با کیفیت 128 </a><?php } if(get_field('album320')){ ?>
<a class="dl_links_320" href="<?php echo esc_url( get_field('album320') ); ?>"> <i class="icofont-download"></i> دانلود  آلبوم با کیفیت 320 </a>	<?php } ?>
<div class="clear"></div>
</div>
<?php if((get_field('video1080')) || (get_field('video720')) || (get_field('video480'))){ ?>
<div class="dl_linksv_box">
<ul>
<?php if(get_field('video1080')){ ?> 
<li><a href="<?php echo esc_url( get_field('video1080') ); ?>"> <i class="icofont-download"></i>دانلود  موزیک ویدیو با کیفیت  1080 </a></li><?php } if(get_field('video720')){ ?>
<li><a href="<?php echo esc_url( get_field('video720') ); ?>"> <i class="icofont-download"></i>دانلود  موزیک ویدیو با کیفیت  720 </a></li><?php } if(get_field('video480')){ ?>
<li><a href="<?php echo esc_url( get_field('video480') ); ?>"> <i class="icofont-download"></i>دانلود  موزیک ویدیو با کیفیت  480 </a></li><?php } ?>
</ul>
<div class="clear"></div>
</div>
<?php } ?>
<?php if ( '' !== $taranehyar_lyrics_box && 'end' === $taranehyar_lyrics_position ) { echo $taranehyar_lyrics_box; } ?>
<?php /* v3.7.8 — «مشخصات اثر» بالاتر و بعد از توضیحات چاپ می‌شود، نه این‌جا. */ ?>
<div class="clear"></div>
</div>

<footer>
<div class="tarlanweb_post_ft"><i class="icofont-clock-time"></i> <?php the_time('j F Y'); ?></div>
<div class="post-actions-input">
<div class="post-actions-right"><i class="icofont-link"></i>لینک کوتاه</div>
<input type="url" value="<?php bloginfo('url'); ?>/?p=<?php the_id(''); ?>" readonly="" onclick="this.setSelectionRange(0, this.value.length)">
<div class="clear"></div>
</div>
</footer>
<div class="clear"></div>
</article>
<?php endwhile; endif; ?>

<?php $share_off = function_exists( 'tara_opt_on' ) ? tara_opt_on( 'share_off' ) : ( 'off' != ot_get_option( 'share_off' ) ); if ( $share_off ) { ?>
<div class="single_posts socials_shares_box">
<p>اشتراک گذاری مطلب</p>
<div class="socials_shares">
<a target="_blank" href="http://www.facebook.com/share.php?v=4&src=bm&u=<?php the_permalink(); ?>"><i aria-label="facebook" class="icofont-facebook"></i></a>
<a target="_blank" href="https://twitter.com/intent/tweet?text=<?php the_permalink(); ?>"><i aria-label="twitter" class="icofont-twitter"></i></a>
<a target="_blank" href="https://api.whatsapp.com/send?text=<?php the_title(); bloginfo('url'); ?>/?p=<?php the_id(''); ?>"><i aria-label="whatsapp" class="icofont-whatsapp"></i></a>
<a target="_blank" href="https://telegram.me/share/url?url=<?php the_permalink(); ?>"><i aria-label="telegram" class="icofont-paper-plane"></i></a>
</div>
<div class="clear"></div>
</div>
<?php } ?>

<?php $tag_off = function_exists( 'tara_opt_on' ) ? tara_opt_on( 'tag_off' ) : ( 'off' != ot_get_option( 'tag_off' ) ); if ( $tag_off ) { if(has_tag()){ ?>
<div class="single_posts tara-tags-box">
<span> برچسب ها :</span> <?php the_tags('','',''); ?>
<div class="clear"></div>
</div>
<?php } } ?>
	
	

	
<?php $reltag1 = function_exists( 'tara_opt_on' ) ? tara_opt_on( 'reltag1', false ) : ( 'off' != ot_get_option( 'reltag1' ) ); if ( $reltag1 ) {
$reltag = function_exists( 'tara_opt_on' ) ? tara_opt_on( 'reltag', false ) : ( 'off' != ot_get_option( 'reltag' ) ); if ( $reltag ) { ?>	
<?php $current_post_id = $post->ID; $pppr=ot_get_option('pppf'); $artist=get_field('artist');
$my_custom_query= new WP_Query(
array('post_type'=>'post','post_status'=>'publish','posts_per_page'=> $pppr,
'meta_query'=>array(array('key'=>'artist','value'=>$artist,'compare'=>'LIKE')), ));
if($my_custom_query->have_posts()) : ?>
<div class="related_posts rel_sing_bbt">
<h3 class="tarlanweb_side_titles">سایر آهنگهای <?php echo $artist; ?></h3>
<?php if(has_tag()){ if(isset($artist) && !empty($artist)){ $post_tags = get_the_tags(); foreach( $post_tags as $tag) :
if ( $tag->name === $artist ){ ?>
<div class="left_artists_pp"><a class="artist_songs_page" href="<?php bloginfo('url');?>/tag/<?php print_r($tag->slug);?>"> <i class="icofont-radio-mic"></i> صفحه اختصاصی  <?php print_r($tag->name); ?> </a></div>
<?php } endforeach; } } ?>
<div class="liner clear"></div>
<div class="tarlanweb_side_box">
<ul>
<?php while($my_custom_query->have_posts()) : $my_custom_query->the_post(); ?>
<li<?php if($current_post_id === $post->ID){ ?> class="inactive_songs"<?php } ?>><a href="<?php the_permalink(); ?>">
<div class="tara-thumb-box"><?php if($current_post_id === $post->ID){ ?><i class="inactive_box_cir"></i><?php } ?>
<?php if(has_post_thumbnail()){
the_post_thumbnail('thumb3',array('alt'=>''.get_the_title(),'title'=>''.get_the_title().'')); } else { ?>
<img src="<?php bloginfo('template_url');?>/images/nomusics.jpg" width="60" height="60" alt="تصویر پیدا نشد" title="تصویر پیدا نشد"><?php } ?>
<?php if($current_post_id === $post->ID){ ?><div class="nowPlayingIcon"><span></span><span></span><span></span><span></span></div><?php } ?></div>
<?php $artist=get_post_meta(get_the_ID(),'artist',true); $track=get_post_meta(get_the_ID(),'song',true);
if(isset($artist) && !empty($artist)){?> <span class="song_artists"> <?php echo $artist; ?> </span> <span> <?php echo $track; ?> </span> <?php } else{ the_title(''); } ?>	
</a></li>
<?php endwhile; ?>
<?php if(has_tag()){ if(isset($artist) && !empty($artist)){ $post_tags = get_the_tags(); foreach( $post_tags as $tag) :
if ( $tag->name === $artist ){ ?>
<li class="artist_songs_page_li mobile_more_artist"><a class="artist_songs_page" href="<?php bloginfo('url');?>/tag/<?php print_r($tag->slug);?>"> <i class="icofont-radio-mic"></i> صفحه اختصاصی  <?php print_r($tag->name); ?> </a></li>
<?php } endforeach; } } ?>
</ul>
<div class="clear"></div>
</div>
<div class="clear"></div>
</div>
<?php endif; wp_reset_postdata(); ?>
<?php } else{ ?>
<?php $current_post_id = $post->ID; $pppr=ot_get_option('pppf'); $terms=get_the_terms($post->ID ,tara_artist_tax(),'string'); $term_ids=wp_list_pluck($terms,'term_id');
$second_query = new WP_Query( array('post_type'=>'post',
'tax_query' => array( array('taxonomy'=>tara_artist_tax(),'field'=>'id','terms'=>$term_ids,'operator'=>'IN')),
'posts_per_page'=>$pppr,'ignore_sticky_posts'=>1,'orderby'=>'id'));
if($second_query->have_posts()) { ?>	
<div class="related_posts rel_sing_bbt">
<div class="title_tarlanweb_box">
<h3 class="tarlanweb_side_titles">سایر آهنگهای <?php $term_obj_list=get_the_terms($post->ID,tara_artist_tax());
$terms_string=join(' , ',wp_list_pluck($term_obj_list,'name')); echo $terms_string; ?></h3>
<?php $terms = get_the_terms($post->ID,tara_artist_tax()); foreach ($terms as $term){ ?>
<div class="left_artists_pp"><a class="artist_songs_page" href="<?php echo esc_url( tara_artist_term_link( $term ) ); ?>"> <i class="icofont-radio-mic"></i> صفحه اختصاصی <?php echo $term->name; ?> </a></div>
<?php } ?>
<div class="liner clear"></div>
</div>
<div class="tarlanweb_side_box">
<ul>
<?php while($second_query->have_posts()) : $second_query->the_post(); ?>
<li<?php if($current_post_id === $post->ID){ ?> class="inactive_songs"<?php } ?>><a href="<?php the_permalink(); ?>">
<div class="tara-thumb-box"><?php if($current_post_id === $post->ID){ ?><i class="inactive_box_cir"></i><?php } ?>
<?php if(has_post_thumbnail()){
the_post_thumbnail('thumb3',array('alt'=>''.get_the_title(),'title'=>''.get_the_title().'')); } else { ?>
<img src="<?php bloginfo('template_url');?>/images/nomusics.jpg" width="60" height="60" alt="تصویر پیدا نشد" title="تصویر پیدا نشد"><?php } ?>
<?php if($current_post_id === $post->ID){ ?><div class="nowPlayingIcon"><span></span><span></span><span></span><span></span></div><?php } ?></div>
<?php $artist=get_post_meta(get_the_ID(),'artist',true); $track=get_post_meta(get_the_ID(),'song',true);
if(isset($artist) && !empty($artist)){?> <span class="song_artists"> <?php echo $artist; ?> </span> <span> <?php echo $track; ?> </span> <?php } else{ the_title(''); } ?>	
</a></li>
<?php endwhile; ?> 
<?php $terms = get_the_terms($post->ID,tara_artist_tax()); foreach ($terms as $term){ ?>
<li class="artist_songs_page_li mobile_more_artist"><a class="artist_songs_page" href="<?php echo esc_url( tara_artist_term_link( $term ) ); ?>"> <i class="icofont-radio-mic"></i> صفحه اختصاصی <?php echo $term->name; ?> </a></li>
<?php } ?>
</ul>
<div class="clear"></div>
</div>
<div class="clear"></div>
</div>	
<?php } ?>
<?php } } ?>

<?php
/* v3.4.6 — رفع «کامنت در دیتابیس هست ولی در فرانت نمایش داده نمی‌شود».
   ریشه: حلقۀ «سایر آهنگ‌های این هنرمند» بالا با $second_query->the_post() کار می‌کند
   و هیچ wp_reset_postdata() بعد از آن نبود ⇒ هنگام اجرای این خط، global $post و
   $GLOBALS['id'] روی آخرین آهنگِ مربوطه ست بودند و comments_template() (که همان
   $post را می‌خواند) کامنت‌های نوشتهٔ فعلی را کوئری نمی‌کرد. اول دادهٔ اصلی برگردانده
   می‌شود؛ بعد باکس دیدگاه چاپ می‌شود.
   ضمناً «نمایش دیدگاه‌ها» (cm_off) از این پس نظراتِ موجود را پنهان نمی‌کند؛ فقط فرم
   ارسال را می‌بندد (با فیلتر موقت comments_open که بی‌درنگ برداشته می‌شود).
   منطق بازدید/نظراتِ دیگر دست‌نخورده است. */
wp_reset_postdata();
$cm_off_on = function_exists( 'tara_opt_on' ) ? tara_opt_on( 'cm_off' ) : ( 'off' !== ot_get_option( 'cm_off' ) );
$cm_off    = $cm_off_on ? 'on' : 'off';
$cm_has = (int) get_comments_number() > 0;
$cm_lock = ( 'off' === $cm_off && $cm_has );
if ( $cm_lock ) { add_filter( 'comments_open', '__return_false', 99 ); }
if ( 'off' !== $cm_off || $cm_has ) { ?>
<div class="single_posts r_comment_box">
<h3 class="comm_titrs"><?php echo $cm_lock ? 'دیدگاه‌ها' : 'نظر خود را بنویسید'; ?></h3>
<?php comments_template(); ?>
<div class="clear"></div>
</div>
<?php } if ( $cm_lock ) { remove_filter( 'comments_open', '__return_false', 99 ); } ?>

<div class="clear"></div>
</div>
<!-- single_posts_right -->
	
<div id="left_side">
<?php
/* v3.0.2 — رندر امن ابزارک‌ها: در صورت نبودِ فایل کمکی، دقیقاً رفتار قبلی اعمال می‌شود */
if ( function_exists( 'tara_sidebar' ) ) {
	tara_sidebar( 'left_side' );
} else {
	if ( ! function_exists( 'dynamic_sidebar' ) || ! dynamic_sidebar( 'left_side' ) ) :
	endif;
}
?>
<?php $reltag1 = function_exists( 'tara_opt_on' ) ? tara_opt_on( 'reltag1', false ) : ( 'off' != ot_get_option( 'reltag1' ) ); if ( ! $reltag1 ) {
$reltag = function_exists( 'tara_opt_on' ) ? tara_opt_on( 'reltag', false ) : ( 'off' != ot_get_option( 'reltag' ) ); if ( $reltag ) { ?>	
<?php $current_post_id = $post->ID; $pppr=ot_get_option('pppf'); $artist=get_field('artist');
$my_custom_query= new WP_Query(
array('post_type'=>'post','post_status'=>'publish','posts_per_page'=> $pppr,
'meta_query'=>array(array('key'=>'artist','value'=>$artist,'compare'=>'LIKE')), ));
if($my_custom_query->have_posts()) : ?>
<aside class="related_posts">
<h3 class="tarlanweb_side_titles">سایر آهنگهای <?php echo $artist; ?></h3>
<div class="liner clear"></div>
<div class="tarlanweb_side_box">
<ul>
<?php while($my_custom_query->have_posts()) : $my_custom_query->the_post(); ?>
<li<?php if($current_post_id === $post->ID){ ?> class="inactive_songs"<?php } ?>><a href="<?php the_permalink(); ?>">
<div class="tara-thumb-box"><?php if($current_post_id === $post->ID){ ?><i class="inactive_box_cir"></i><?php } ?>
<?php if(has_post_thumbnail()){
the_post_thumbnail('thumb3',array('alt'=>''.get_the_title(),'title'=>''.get_the_title().'')); } else { ?>
<img src="<?php bloginfo('template_url');?>/images/nomusics.jpg" width="60" height="60" alt="تصویر پیدا نشد" title="تصویر پیدا نشد"><?php } ?>
<?php if($current_post_id === $post->ID){ ?><div class="nowPlayingIcon"><span></span><span></span><span></span><span></span></div><?php } ?></div>
<?php $artist=get_post_meta(get_the_ID(),'artist',true); $track=get_post_meta(get_the_ID(),'song',true);
if(isset($artist) && !empty($artist)){?> <span class="song_artists"> <?php echo $artist; ?> </span> <span> <?php echo $track; ?> </span> <?php } else{ the_title(''); } ?>	
</a></li>
<?php endwhile; ?>
<?php if(has_tag()){ if(isset($artist) && !empty($artist)){ $post_tags = get_the_tags(); foreach( $post_tags as $tag) :
if ( $tag->name === $artist ){ ?>
<li class="artist_songs_page_li"><a class="artist_songs_page" href="<?php bloginfo('url');?>/tag/<?php print_r($tag->slug);?>"> <i class="icofont-radio-mic"></i> صفحه اختصاصی  <?php print_r($tag->name); ?> </a></li>
<?php } endforeach; } } ?>
</ul>
<div class="clear"></div>
</div>
<div class="clear"></div>
</aside>
<?php endif; wp_reset_postdata(); ?>
<?php } else{ ?>
<?php $current_post_id = $post->ID; $pppr=ot_get_option('pppf'); $terms=get_the_terms($post->ID ,tara_artist_tax(),'string'); $term_ids=wp_list_pluck($terms,'term_id');
$second_query = new WP_Query( array('post_type'=>'post',
'tax_query' => array( array('taxonomy'=>tara_artist_tax(),'field'=>'id','terms'=>$term_ids,'operator'=>'IN')),
'posts_per_page'=>$pppr,'ignore_sticky_posts'=>1,'orderby'=>'id'));
if($second_query->have_posts()) { ?>	
<aside class="related_posts">
<div class="title_tarlanweb_box">
<h3 class="tarlanweb_side_titles">سایر آهنگهای <?php $term_obj_list=get_the_terms($post->ID,tara_artist_tax());
$terms_string=join(' , ',wp_list_pluck($term_obj_list,'name')); echo $terms_string; ?></h3>
<div class="liner clear"></div>
</div>
<div class="tarlanweb_side_box">
<ul>
<?php while($second_query->have_posts()) : $second_query->the_post(); ?>
<li<?php if($current_post_id === $post->ID){ ?> class="inactive_songs"<?php } ?>><a href="<?php the_permalink(); ?>">
<div class="tara-thumb-box"><?php if($current_post_id === $post->ID){ ?><i class="inactive_box_cir"></i><?php } ?>
<?php if(has_post_thumbnail()){
the_post_thumbnail('thumb3',array('alt'=>''.get_the_title(),'title'=>''.get_the_title().'')); } else { ?>
<img src="<?php bloginfo('template_url');?>/images/nomusics.jpg" width="60" height="60" alt="تصویر پیدا نشد" title="تصویر پیدا نشد"><?php } ?>
<?php if($current_post_id === $post->ID){ ?><div class="nowPlayingIcon"><span></span><span></span><span></span><span></span></div><?php } ?></div>
<?php $artist=get_post_meta(get_the_ID(),'artist',true); $track=get_post_meta(get_the_ID(),'song',true);
if(isset($artist) && !empty($artist)){?> <span class="song_artists"> <?php echo $artist; ?> </span> <span> <?php echo $track; ?> </span> <?php } else{ the_title(''); } ?>	
</a></li>
<?php endwhile; ?>
<?php $terms = get_the_terms($post->ID,tara_artist_tax()); foreach ($terms as $term){ ?>
<li class="artist_songs_page_li"><a class="artist_songs_page" href="<?php echo esc_url( tara_artist_term_link( $term ) ); ?>"> <i class="icofont-radio-mic"></i> صفحه اختصاصی <?php echo $term->name; ?> </a></li>
<?php } ?>
</ul>
<div class="clear"></div>
</div>
<div class="clear"></div>
</aside>	
<?php } ?>
<?php }
	} ?>
<?php
/* v3.0.2 — رندر امن ابزارک‌ها: در صورت نبودِ فایل کمکی، دقیقاً رفتار قبلی اعمال می‌شود */
if ( function_exists( 'tara_sidebar' ) ) {
	tara_sidebar( 'left_side2' );
} else {
	if ( ! function_exists( 'dynamic_sidebar' ) || ! dynamic_sidebar( 'left_side2' ) ) :
	endif;
}
?>
<div class="clear"></div>
</div>	

<div class="clear"></div>
</div>
</div>
<!-- centers_box -->

<?php get_footer(''); ?>