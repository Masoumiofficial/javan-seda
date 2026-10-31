<?php
/**
 * آزمونِ نسخهٔ ۳.۸.۳ — لوگوی روز/شب، بنرِ ویژهٔ ۱۹۲۰×۸۴۰، آیکونِ تمام‌صفحه،
 * فشرده‌سازی CSS، جست‌وجوی کیبوردی، ادامهٔ پخش، کاهش حرکت و خوانایی شب.
 *
 * اجرا: node tools/php-test.mjs tests/run-380.php
 */

require __DIR__ . '/stubs-wp.php';

define( 'TARANEHYAR_VERSION', '3.8.3' );
define( 'TARANEHYAR_DIR', '/tmp/theme' );
define( 'TARANEHYAR_URI', 'https://ex/theme' );
define( 'ABSPATH', '/tmp/wp/' );

$GLOBALS['__options']  = array( 'option_tree' => array() );
$GLOBALS['__postmeta'] = array();
$GLOBALS['__posts']    = array();

if ( ! function_exists( 'tara_number_fa' ) ) {
	function tara_number_fa( $number ) {
		return strtr( (string) $number, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
	}
}
if ( ! function_exists( 'ot_get_option' ) ) {
	function ot_get_option( $key, $default = '' ) {
		$opts = isset( $GLOBALS['__options']['option_tree'] ) ? $GLOBALS['__options']['option_tree'] : array();
		if ( is_array( $opts ) && isset( $opts[ $key ] ) && '' !== $opts[ $key ] ) {
			return $opts[ $key ];
		}
		return $default;
	}
}

require '/tmp/theme/inc/tara-assets.php';
require '/tmp/theme/inc/tara-ui.php';
require '/tmp/theme/inc/ab-vip-image.php';
require '/tmp/theme/inc/tara-critical.php';

$PASS  = 0;
$FAIL  = 0;
$FAILS = array();

function t( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL, $FAILS;
	if ( $cond ) {
		$PASS++;
		echo "ok   $name\n";
	} else {
		$FAIL++;
		$FAILS[] = $name . ( $detail ? " — $detail" : '' );
		echo "FAIL $name" . ( $detail ? " — $detail" : '' ) . "\n";
	}
}

function f( $path ) {
	return (string) @file_get_contents( '/tmp/theme/' . ltrim( $path, '/' ) );
}

/* ======================================================================
   ۱) لوگوی روز و شب
   ====================================================================== */
$ui    = f( 'inc/tara-ui.php' );
$logo  = f( 'inc/ab-logo-setting.php' );
$head  = f( 'header.php' );
$mob   = f( 'inc/mobiles_header.php' );
$opts  = f( 'inc/theme-options.php' );
$cssui = f( 'css/tara-ui.css' );
$style = f( 'style.css' );

t(
	'لوگو: تابعِ tara_logo_night_url و tara_logo_img تعریف شده',
	false !== strpos( $ui, "function tara_logo_night_url()" )
		&& false !== strpos( $ui, "function tara_logo_img(" )
		&& false !== strpos( $ui, "function tara_logo_raw(" )
);

$GLOBALS['__options']['option_tree'] = array( 'logo' => 'https://ex/day.png', 'logo_night' => '' );
t(
	'لوگو: اگر لوگوی شب خالی باشد، همان لوگوی روز برمی‌گردد',
	'https://ex/day.png' === tara_logo_night_url(),
	tara_logo_night_url()
);

$GLOBALS['__options']['option_tree'] = array( 'logo' => 'https://ex/day.png', 'logo_night' => 'https://ex/night.png' );
t(
	'لوگو: لوگوی شب سفارشی خوانده می‌شود',
	'https://ex/night.png' === tara_logo_night_url(),
	tara_logo_night_url()
);

$html = tara_logo_img( array( 'class' => 'ab-logo__img', 'alt' => 'ترانه یار' ) );
t(
	'لوگو: هر دو تصویر (روز و شب) در مارک‌آپ می‌آیند',
	false !== strpos( $html, 'ab-logo__img--day' )
		&& false !== strpos( $html, 'ab-logo__img--night' )
		&& false !== strpos( $html, 'https://ex/night.png' )
);

$GLOBALS['__options']['option_tree'] = array( 'logo' => 'https://ex/day.png' );
t(
	'لوگو: بدونِ لوگوی شب، تصویر دوم ساخته نمی‌شود',
	false === strpos( tara_logo_img(), 'ab-logo__img--night' )
);

t(
	'لوگو: سربرگ و کشوی گوشی از tara_logo_img استفاده می‌کنند',
	false !== strpos( $head, 'tara_logo_img(' )
		&& false !== strpos( $mob, 'tara_logo_img(' )
);

t(
	'لوگو: کارت پنل دو حالته است (روز/شب)',
	false !== strpos( $logo, "tara_logo_card_variant( 'day'" )
		&& false !== strpos( $logo, "tara_logo_card_variant( 'night'" )
		&& false !== strpos( $logo, 'name="tara_logo_target"' )
		&& false !== strpos( $logo, "'logo_night'" )
);

t(
	'لوگو: گزینهٔ logo_night به تنظیمات قالب اضافه شد',
	false !== strpos( $opts, "'id'=>'logo_night'" )
);

t(
	'لوگو: قاعدهٔ تعویض روز/شب در هر دو فایل CSS هست',
	false !== strpos( $cssui, 'img.ab-logo__img--night { display: none !important; }' )
		&& false !== strpos( $cssui, 'html[data-tara-night="1"] #header .logo_top img.ab-logo__img--night' )
		&& false !== strpos( $style, 'html[data-tara-night="1"] #header .logo_top img.ab-logo__img--night' )
);

/* ======================================================================
   ۲) بنر اسلایدر ویژه — ۱۹۲۰×۸۴۰
   ====================================================================== */
$vip      = f( 'inc/vip_slider.php' );
$vipbox   = f( 'inc/ab-vip-image.php' );
$vipjs    = f( 'js/ab-vip-image.js' );
$vipcss   = f( 'css/ab-vip-image.css' );
$func     = f( 'functions.php' );
$myfunc   = f( 'myfunctions.php' );

t(
	'بنر: سایز ۱۹۲۰×۸۴۰ ثبت شد (هم در functions و هم myfunctions)',
	false !== strpos( $func, "add_image_size( 'tara-vip', 1920, 840, true )" )
		&& false !== strpos( $myfunc, "add_image_size( 'tara-vip', 1920, 840, true)" )
);

t(
	'بنر: متای تازه با nonce و capability ذخیره می‌شود',
	false !== strpos( $vipbox, "function tara_vip_meta_key()" )
		&& false !== strpos( $vipbox, "'_tara_vip_banner'" )
		&& false !== strpos( $vipbox, "wp_nonce_field( 'tara_vip_banner'" )
		&& false !== strpos( $vipbox, "current_user_can( 'edit_post', \$post_id )" )
);

t(
	'بنر: جعبهٔ ویرایش نوشته با راهنمای ۱۹۲۰ × ۸۴۰',
	false !== strpos( $vipbox, "'tara-vip-banner'" )
		&& false !== strpos( $vipbox, '۱۹۲۰ × ۸۴۰' )
		&& false !== strpos( $vipbox, 'برای دیده‌شدن در اسلایدر ویژه' )
);

t(
	'بنر: انتخاب‌گر با wp.media (بدون کتابخانهٔ بیرونی)',
	false !== strpos( $vipjs, 'window.wp.media' )
		&& false !== strpos( $vipjs, 'data-tara-vip-pick' )
		&& false !== strpos( $vipjs, 'data-tara-vip-clear' )
);

t(
	'بنر: استایل جعبهٔ پیشخوان موجود است',
	false !== strpos( $vipcss, '.tara-vip-box' )
		&& false !== strpos( $vipcss, 'aspect-ratio: 16 / 7' )
);

t(
	'بنر: اسلایدر اول این متا را می‌خواند، بعد ACF قدیمی',
	false !== strpos( $vip, 'tara_vip_banner_id( get_the_ID() )' )
		&& false !== strpos( $vip, "get_field( 'vip_img' )" )
		&& false !== strpos( $vip, 'width="1920" height="840"' )
);

$GLOBALS['__postmeta'][321]['_tara_vip_banner'] = 987;
t(
	'بنر: شناسهٔ ذخیره‌شده خوانده می‌شود',
	987 === tara_vip_banner_id( 321 ),
	(string) tara_vip_banner_id( 321 )
);
t(
	'بنر: نوشتهٔ بدونِ بنر صفر می‌دهد (بدونِ هشدار)',
	0 === tara_vip_banner_id( 322 )
);

/* ======================================================================
   ۳) آیکون استانداردِ تمام‌صفحه
   ====================================================================== */
$playerjs = f( 'js/tara-player.js' );

t(
	'تمام‌صفحه: دو آیکونِ SVG (بیرون/داخل) در دکمه',
	false !== strpos( $ui, 'ty-video__ico--expand' )
		&& false !== strpos( $ui, 'ty-video__ico--collapse' )
		&& false !== strpos( $ui, 'M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5' )
		&& false !== strpos( $ui, 'M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5' )
);

t(
	'تمام‌صفحه: دکمه aria-pressed دارد',
	false !== strpos( $ui, 'aria-pressed="false"' )
);

t(
	'تمام‌صفحه: CSS آیکون را با وضعیت عوض می‌کند',
	false !== strpos( $cssui, '.ty-video__ico--collapse { display: none; }' )
		&& false !== strpos( $cssui, '.ty-video.is-fullscreen [data-tara-video-fs] .ty-video__ico--collapse' )
);

t(
	'تمام‌صفحه: برچسب/وضعیت در JS هم‌گام می‌شود',
	false !== strpos( $playerjs, 'function syncFsLabels()' )
		&& false !== strpos( $playerjs, "'خروج از تمام‌صفحه'" )
);

/* ======================================================================
   ۴) ادامهٔ پخش (localStorage)
   ====================================================================== */
t(
	'ادامهٔ پخش: کلید و منطق در پلیر هست',
	false !== strpos( $playerjs, "'tara:resume'" )
		&& false !== strpos( $playerjs, 'function Resume()' )
		&& false !== strpos( $playerjs, 'saved.time > 15' )
		&& false !== strpos( $playerjs, 'Resume();' )
);

/* ======================================================================
   ۵) فشرده‌سازی CSS (فقط فشرده، بدون ترکیب)
   ====================================================================== */
t(
	'CSS: تابعِ tara_css_rel در فایلِ مستقل تعریف شد',
	false !== strpos( f( 'inc/tara-assets.php' ), 'function tara_css_rel(' )
		&& false !== strpos( f( 'inc/tara-assets.php' ), "substr( \$rel, 0, -4 ) . '.min.css'" )
		&& false !== strpos( $func, "'/inc/tara-assets.php'" )
);

/* تازگی با mtime سنجیده می‌شود؛ در /tmp ترتیب نوشتن قطعی نیست، پس این‌جا
   خودِ زمان‌ها را صریح می‌کنیم تا آزمون به تصادف وابسته نباشد. */
@touch( '/tmp/theme/css/tara-ui.min.css', time() + 5 );
t(
	'CSS: مسیر فایلِ فشردهٔ تازه انتخاب می‌شود',
	'/css/tara-ui.min.css' === tara_css_rel( '/css/tara-ui.css' ),
	tara_css_rel( '/css/tara-ui.css' )
);

@touch( '/tmp/theme/css/tara-ui.css', time() + 30 );
t(
	'CSS: اگر فشرده از منبع قدیمی‌تر باشد، همان منبع سرو می‌شود',
	'/css/tara-ui.css' === tara_css_rel( '/css/tara-ui.css' ),
	tara_css_rel( '/css/tara-ui.css' )
);
@touch( '/tmp/theme/css/tara-ui.css', time() - 30 );
@touch( '/tmp/theme/css/tara-ui.min.css', time() + 40 );

t(
	'CSS: برای فایلی که نسخهٔ فشرده ندارد، همان مسیر اصلی می‌ماند',
	'/css/admin-panel.css' === tara_css_rel( '/css/admin-panel.css' )
);

$src = (int) @filesize( '/tmp/theme/css/tara-ui.css' );
$min = (int) @filesize( '/tmp/theme/css/tara-ui.min.css' );
t(
	'CSS: نسخهٔ فشردهٔ tara-ui وجود دارد و کوچک‌تر است',
	$min > 0 && $min < $src,
	$min . ' vs ' . $src
);

t(
	'CSS: style.min.css هم ساخته شده و از style.css کوچک‌تر است',
	(int) @filesize( '/tmp/theme/css/style.min.css' ) > 0
		&& (int) @filesize( '/tmp/theme/css/style.min.css' ) < (int) @filesize( '/tmp/theme/style.css' )
);

t(
	'CSS: style.css اصلی سر جایش است (هدر قالب)',
	false !== strpos( f( 'style.css' ), 'Theme Name:' )
);

t(
	'CSS: enqueueِ لایهٔ شیشه هم فشرده را ترجیح می‌دهد',
	false !== strpos( f( 'inc/ab-glass.php' ), "tara_css_rel( '/css/ab-glass.css' )" )
);

/* ======================================================================
   ۶) جست‌وجوی زنده: کیبورد، aria و اسکلت
   ====================================================================== */
$crit = f( 'inc/tara-critical.php' );

t(
	'جست‌وجو: نقشِ combobox/listbox و aria-expanded',
	false !== strpos( $crit, "'role', 'combobox'" )
		&& false !== strpos( $crit, "'aria-expanded', 'false'" )
		&& false !== strpos( $crit, "'aria-controls', 'tara-search-results'" )
);

t(
	'جست‌وجو: ناوبری با کلید بالا/پایین و Enter',
	false !== strpos( $crit, "'ArrowDown'" )
		&& false !== strpos( $crit, "'ArrowUp'" )
		&& false !== strpos( $crit, "aria-activedescendant" )
		&& false !== strpos( $crit, "window.location.href = rows[active]" )
);

t(
	'جست‌وجو: debounce روی ۲۵۰ms است',
	(bool) preg_match( '/\}, 250\);/', $crit )
);

t(
	'جست‌وجو: نتیجهٔ هر عبارت کش می‌شود',
	false !== strpos( $crit, 'var cache = {};' )
		&& false !== strpos( $crit, 'if (cache[q])' )
);

t(
	'جست‌وجو: اسکلتِ لودینگ هم در JS و هم در CSS هست',
	false !== strpos( $crit, 'tara-sr__row is-skeleton' )
		&& false !== strpos( $crit, '@keyframes tara-sk' )
);

t(
	'جست‌وجو: کنتراستِ زیرنویس در شب بالا رفت',
	false !== strpos( $crit, '#b9b6cd' )
);

/* ======================================================================
   ۷) کاهش حرکت و خوانایی شب
   ====================================================================== */
t(
	'دسترسی: prefers-reduced-motion پویایی را خاموش می‌کند',
	false !== strpos( $cssui, '@media (prefers-reduced-motion: reduce)' )
		&& false !== strpos( $cssui, 'transition-duration: .001ms !important' )
		&& false !== strpos( $cssui, '.ty-hero-track { scroll-behavior: auto !important; }' )
);

t(
	'شب: رنگِ متن‌های کم‌رنگ روشن‌تر شد (tara-ui)',
	false !== strpos( $cssui, '--ty-muted: #c2c1cf;' )
);

t(
	'شب: رنگِ متن‌های کم‌رنگ روشن‌تر شد (style.css)',
	false !== strpos( $style, '--ab-muted: #C6C6D3;' )
		&& false !== strpos( $style, '.night .disabled { color: #9A9AA8 !important; }' )
);

/* ======================================================================
   ۸) OptionTree زیرِ منوی «ترانه یار»
   ====================================================================== */
$panel = f( 'inc/admin-panel.php' );
t(
	'تنظیمات: OptionTree زیرِ منوی پنل است (نه «نمایش»)',
	false !== strpos( $panel, "add_filter( 'ot_theme_options_parent_slug', 'taranehyar_ot_parent_slug' )" )
		&& false !== strpos( $panel, 'return taranehyar_panel_slug();' )
		&& false !== strpos( $panel, "return 'تنظیمات قالب';" )
);

/* ======================================================================
   ۹) صفحهٔ ۴۰۴ (پیشنهادِ آهنگ)
   ====================================================================== */
$p404 = f( '404.php' );
t(
	'۴۰۴: پیشنهادِ آهنگ از دادهٔ واقعی هست (نه دکمهٔ خالی)',
	false !== strpos( $p404, 'شاید این‌ها را بخواهید' )
		&& false !== strpos( $p404, "new WP_Query(" )
);

/* ======================================================================
   ۱۰) نسخه
   ====================================================================== */
t(
	'نسخه: style.css روی ۳.۸.۳ است',
	(bool) preg_match( '/Version: 3\.(8\.[3-9]|9\.\d+|1[01]\.\d+)/', $style )
);
t(
	'نسخه: fallback در functions.php روی ۳.۸.۳ است',
	(bool) preg_match( "/: '3\\.(8\\.[3-9]|9\\.\\d+|1[01]\\.\\d+)' \\)/", $func )
);
t(
	'نسخه: نشانهٔ پلیر روی ۳.۸.۳ است',
	(bool) preg_match( "/'data-tara-ready', '3\\.(8\\.[3-9]|9\\.\\d+|1[01]\\.\\d+)'/", $playerjs )
);

/* ======================================================================
   خلاصه
   ====================================================================== */
echo "\n";
echo 'RESULT pass=' . $PASS . ' fail=' . $FAIL . "\n";
if ( $FAIL ) {
	echo "FAILED:\n";
	foreach ( $FAILS as $f ) {
		echo ' - ' . $f . "\n";
	}
}
exit( $FAIL ? 1 : 0 );
