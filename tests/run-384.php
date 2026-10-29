<?php
/**
 * آزمونِ نسخهٔ ۳.۸.۴ — نمایشِ درستِ لوگوی روز/شب و کارتِ لوگو در صفحهٔ
 * «تنظیمات قالب» (به‌همراه واکنش‌گرایی و اندازهٔ کادر).
 *
 * اجرا: node tools/php-test.mjs tests/run-384.php
 */

require __DIR__ . '/stubs-wp.php';

define( 'TARANEHYAR_VERSION', '3.8.4' );
define( 'TARANEHYAR_DIR', '/tmp/theme' );
define( 'TARANEHYAR_URI', 'https://ex/theme' );
define( 'ABSPATH', '/tmp/wp/' );

$GLOBALS['__options']  = array( 'option_tree' => array() );
$GLOBALS['__postmeta'] = array();
$GLOBALS['__posts']    = array();

if ( ! function_exists( 'ot_get_option' ) ) {
	function ot_get_option( $key, $default = '' ) {
		$opts = isset( $GLOBALS['__options']['option_tree'] ) ? $GLOBALS['__options']['option_tree'] : array();
		if ( is_array( $opts ) && isset( $opts[ $key ] ) && '' !== $opts[ $key ] ) {
			return $opts[ $key ];
		}
		return $default;
	}
}

require '/tmp/theme/inc/tara-ui.php';

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

$ui      = f( 'inc/tara-ui.php' );
$logo    = f( 'inc/ab-logo-setting.php' );
$cssui   = f( 'css/tara-ui.css' );
$style   = f( 'style.css' );
$card    = f( 'css/ab-logo-card.css' );
$panel   = f( 'css/admin-panel.css' );
$head    = f( 'header.php' );
$mob     = f( 'inc/mobiles_header.php' );

/* ======================================================================
   ۱) ریشهٔ باگ: `#header .logo_top img { display:block !important }`
      در style.css بر `.ab-logo__img--night { display:none }` می‌برد و
      لوگوی شب «زیر» لوگوی روز چاپ می‌شد. حالا قاعده با شناسه + !important
      نوشته شده تا همیشه برنده باشد.
   ====================================================================== */
t(
	'لوگو: قاعدهٔ پنهان‌کردنِ لوگوی شب با شناسه و !important',
	false !== strpos( $cssui, '#header .logo_top img.ab-logo__img--night,' )
		&& false !== strpos( $cssui, 'img.ab-logo__img--night { display: none !important; }' )
		&& false !== strpos( $style, '#header .logo_top img.ab-logo__img--night,' )
);

t(
	'لوگو: همان قاعده برای کشوی گوشی هم هست',
	false !== strpos( $cssui, '#mobiles_header .logo_mobiles img.ab-logo__img--night' )
		&& false !== strpos( $style, '#mobiles_header .logo_mobiles img.ab-logo__img--night' )
);

t(
	'لوگو: در حالت شب، روز پنهان و شب نمایان می‌شود',
	false !== strpos( $cssui, 'body.night #header .logo_top img.ab-logo__img--day' )
		&& false !== strpos( $cssui, 'display: block !important;' )
		&& false !== strpos( $cssui, 'html[data-tara-night="1"] #mobiles_header .logo_mobiles img.ab-logo__img--night' )
);

t(
	'لوگو: قاعدهٔ قالب (display:block !important) هنوز سر جایش است',
	false !== strpos( $style, '#header .logo_top img{' )
		&& false !== strpos( $style, 'display:block !important;' )
);

/* ======================================================================
   ۲) مارک‌آپ: تصویر دوم فقط وقتی لوگوی شب تنظیم شده باشد
   ====================================================================== */
$GLOBALS['__options']['option_tree'] = array( 'logo' => 'https://ex/day.png', 'logo_night' => 'https://ex/night.png' );
$both = tara_logo_img( array( 'alt' => 'ترانه یار' ) );
t(
	'لوگو: با هر دو لوگو، دو تصویر با کلاسِ مشخص چاپ می‌شود',
	2 === substr_count( $both, '<img' )
		&& false !== strpos( $both, 'ab-logo__img--day' )
		&& false !== strpos( $both, 'ab-logo__img--night' )
);

$GLOBALS['__options']['option_tree'] = array( 'logo' => 'https://ex/day.png' );
t(
	'لوگو: با لوگوی روز تنها، فقط یک <img> در صفحه است (چیزی برای افتادن زیرِ هم نیست)',
	1 === substr_count( tara_logo_img(), '<img' )
);

t(
	'لوگو: سربرگ و کشوی گوشی همان تابع را صدا می‌زنند',
	false !== strpos( $head, 'tara_logo_img(' ) && false !== strpos( $mob, 'tara_logo_img(' )
);

/* ======================================================================
   ۳) کارتِ لوگو در صفحهٔ «تنظیمات قالب»
   ====================================================================== */
t(
	'تنظیمات قالب: صفحه‌شناسی با page=ot-theme-options',
	false !== strpos( $logo, 'function tara_logo_is_settings_screen()' )
		&& false !== strpos( $logo, "'ot-theme-options' === sanitize_key" )
);

t(
	'تنظیمات قالب: کارت با admin_notices بالای فرمِ تنظیمات چاپ می‌شود',
	false !== strpos( $logo, 'add_action( \'admin_notices\', \'tara_logo_settings_notice\' );' )
		&& false !== strpos( $logo, "tara_logo_card( 'settings' )" )
);

t(
	'تنظیمات قالب: بعد از ذخیره، به همان صفحهٔ تنظیمات برمی‌گردد',
	false !== strpos( $logo, "sanitize_key( wp_unslash( (string) \$_POST['tara_logo_from'] ) )" )
		&& false !== strpos( $logo, "'settings' === \$from" )
		&& false !== strpos( $logo, "\$page = 'ot-theme-options';" )
		&& false !== strpos( $logo, 'name="tara_logo_from"' )
);

t(
	'تنظیمات قالب: پیامِ نتیجه هم در همان صفحه دیده می‌شود',
	false !== strpos( $logo, 'tara_logo_notice();' )
);

t(
	'تنظیمات قالب: فقط کاربرِ manage_options می‌بیند',
	false !== strpos( $logo, "! current_user_can( 'manage_options' ) || ! tara_logo_is_settings_screen()" )
);

t(
	'کارت: هنوز در پیشخوانِ پنل هم هست (جابه‌جا نشد، اضافه شد)',
	false !== strpos( f( 'inc/admin-panel-views.php' ), 'tara_logo_card();' )
);

/* ======================================================================
   ۴) استایلِ مستقل، واکنش‌گرا و اندازهٔ کادر
   ====================================================================== */
t(
	'کارت: استایلش در فایلِ مستقل است (صفحهٔ تنظیمات CSS پنل را لود نمی‌کند)',
	false !== strpos( $card, '.ty-logo-card {' )
		&& false !== strpos( $card, '.ty-logo-card--settings' )
);

t(
	'کارت: متغیرهای رنگش را خودش دارد (بدون اتکا به CSS پنل)',
	preg_match( '/\.ty-logo-card\s*\{[^}]*--logo-line:\s*#/', $card )
		&& false !== strpos( $card, 'var(--logo-line)' )
);

t(
	'کارت: کادرِ پیش‌نمایش ارتفاعِ ثابت دارد (اندازه تثبیت شد)',
	preg_match( '/\.ty-logo-card__preview\s*\{[^}]*height:\s*76px;/s', $card )
		&& preg_match( '/@media \(max-width: 600px\)\s*\{.*?\.ty-logo-card__preview \{ height: 64px;/s', $card )
);

t(
	'کارت: تصویر داخل کادر با contain و بدون بیرون‌زدگی',
	preg_match( '/\.ty-logo-card__preview img\s*\{[^}]*max-height:\s*100%;[^}]*object-fit:\s*contain;/s', $card )
);

t(
	'کارت: واکنش‌گرا (auto-fit + شکست در ۹۶۰ و ۶۰۰)',
	false !== strpos( $card, 'repeat(auto-fit, minmax(272px, 1fr))' )
		&& false !== strpos( $card, '@media (max-width: 960px)' )
		&& false !== strpos( $card, '@media (max-width: 600px)' )
);

t(
	'کارت: در صفحهٔ تنظیمات دکمه‌ها استایلِ مستقل دارند',
	false !== strpos( $card, '.ty-logo-card--settings .ty-logo-card__actions button {' )
);

t(
	'کارت: استایل قدیمی از admin-panel.css جمع شد (بدون تکرار)',
	false === strpos( $panel, '.ty-logo-card__preview' )
);

t(
	'کارت: CSS فقط در دو صفحهٔ لازم لود می‌شود',
	false !== strpos( $logo, "'ot-theme-options' !== \$page && 'taranehyar' !== \$page" )
		&& false !== strpos( $logo, "'tara-logo-card'" )
);

/* ======================================================================
   ۵) نسخه
   ====================================================================== */
t( 'نسخه: style.css روی ۳.۸.۴ است', (bool) preg_match( '/Version: 3\.(8\.[4-9]|9\.\d+|1[01]\.\d+)/', $style ) );
t( 'نسخه: fallback در functions.php روی ۳.۸.۴ است', (bool) preg_match( "/: '3\.(8\.[4-9]|9\.\d+|1[01]\.\d+)' \)/", f( 'functions.php' ) ) );
t( 'نسخه: نشانهٔ پلیر روی ۳.۸.۴ است', (bool) preg_match( "/'data-tara-ready', '3\.(8\.[4-9]|9\.\d+|1[01]\.\d+)'/", f( 'js/tara-player.js' ) ) );

echo "\n";
echo 'RESULT pass=' . $PASS . ' fail=' . $FAIL . "\n";
if ( $FAIL ) {
	echo "FAILED:\n";
	foreach ( $FAILS as $f ) {
		echo ' - ' . $f . "\n";
	}
}
exit( $FAIL ? 1 : 0 );
