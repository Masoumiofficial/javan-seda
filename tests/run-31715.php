<?php
/**
 * آزمونِ ۳.۱۷.۱۵ — چیدمانِ چندستونهٔ آهنگ‌ها + دکمهٔ بیشتر/کمتر
 * ==========================================================================
 * سه چیزی که کاربر گزارش کرد و این‌جا سنجیده می‌شوند:
 *   ۱) «ردیف آهنگ‌ها تک‌ستونه است و فضا گرفته»  → باید گریدِ auto-fill باشد
 *   ۲) «توضیحات کامل باید بیشتر/کمتر بخوانید داشته باشد»  → مارک‌آپ + JS
 *   ۳) «بد نمایش داده می‌شود»  → CSS صفحهٔ تاکسونومی در دسته هم بار شود
 *
 * اجرا: node tools/php-test.mjs tests/run-31715.php
 */

require __DIR__ . '/stubs-wp.php';

define( 'TARANEHYAR_VERSION', '3.17.15' );
define( 'TARANEHYAR_DIR', '/tmp/theme' );
define( 'TARANEHYAR_URI', 'https://ex/theme' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'WEEK_IN_SECONDS', 604800 );
define( 'MONTH_IN_SECONDS', 2592000 );
define( 'YEAR_IN_SECONDS', 31536000 );

$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;

function t( $label, $cond ) {
	if ( $cond ) {
		$GLOBALS['__pass']++;
		echo "  ✓ {$label}\n";
	} else {
		$GLOBALS['__fail']++;
		echo "  ✗ {$label}\n";
	}
}

function css( $name ) {
	return (string) @file_get_contents( '/tmp/theme/css/' . $name );
}
function phpfile( $path ) {
	return (string) @file_get_contents( '/tmp/theme/' . $path );
}

/* ======================================================================
   ۱) گریدِ چندستونه
   ====================================================================== */
echo "=== ۱) فهرستِ آهنگ‌ها چندستونه است ===\n";

$ui = css( 'tara-ui.css' );

t( 'ty-tracklist گرید است', false !== strpos( $ui, '.ty-tracklist {' ) && preg_match( '/\.ty-tracklist\s*\{[^}]*display:\s*grid/s', $ui ) );
t(
	'ستون‌ها auto-fill با حداقل ۳۲۰px',
	(bool) preg_match( '/\.ty-tracklist\s*\{[^}]*grid-template-columns:\s*repeat\(auto-fill,\s*minmax\(min\(100%,\s*320px\),\s*1fr\)\)/s', $ui )
);
t(
	'تک‌ستونهٔ اجباری حذف شد (دیگر grid-template-columns: 1fr بی‌قید نیست)',
	! preg_match( '/\.ty-tracklist\s*\{\s*display:\s*grid;\s*gap:\s*4px;\s*margin:\s*0;\s*padding:\s*0;\s*list-style:\s*none;\s*\}/s', $ui )
);
t( 'موبایل تک‌ستونه می‌ماند (breakpoint 560px)', (bool) preg_match( '/@media \(max-width: 560px\) \{\s*\.ty-tracklist \{\s*grid-template-columns: 1fr;/s', $ui ) );
t( 'کارت‌ها min-width: 0 دارند (جلوگیری از سرریز)', (bool) preg_match( '/\.ty-track\s*\{[^}]*min-width:\s*0/s', $ui ) );
t( 'li ها min-width: 0 دارند', (bool) preg_match( '/\.ty-tracklist > li\s*\{[^}]*min-width:\s*0/s', $ui ) );

/* ======================================================================
   ۲) دکمهٔ بیشتر/کمتر
   ====================================================================== */
echo "\n=== ۲) دکمهٔ «نمایش بیشتر / کمتر» ===\n";

$hero  = phpfile( 'inc/tara-tax-hero.php' );
$taxcss = css( 'tara-tax.css' );

t( 'tara_tax_about از tara_category_desc_full استفاده می‌کند', false !== strpos( $hero, 'tara_category_desc_full(' ) );
t( 'دیگر HTML خام چاپ نمی‌شود', false === strpos( $hero, 'tara_category_desc_full_html( $term )' ) );
t( 'پوشش ty-taxabout-wrap دارد', false !== strpos( $hero, 'ty-taxabout-wrap' ) );

t( 'CSS دکمه وجود دارد', false !== strpos( $taxcss, '.ab-cat-desc__toggle' ) );
t( 'CSS حالت پنهانِ دکمه را احترام می‌گذارد', false !== strpos( $taxcss, '.ab-cat-desc__toggle[hidden]' ) );
t( 'گرادیانِ محو‌شدن با --th-surface هم‌رنگ است', false !== strpos( $taxcss, 'linear-gradient(to bottom, transparent, var(--th-surface))' ) );

$js = phpfile( 'js/ab-cat-desc.js' );
t( 'JS متن «نمایش بیشتر» را می‌نویسد', false !== strpos( $js, "'نمایش بیشتر'" ) );
t( 'JS متن «نمایش کمتر» را می‌نویسد', false !== strpos( $js, "'نمایش کمتر'" ) );
t( 'JS فقط وقتی بلندتر از دید بود دکمه را نشان می‌دهد', false !== strpos( $js, 'full <= body.clientHeight + 1' ) );

/* ======================================================================
   ۳) بارگذاریِ دارایی‌ها در صفحهٔ دسته
   ====================================================================== */
echo "\n=== ۳) استایلِ صفحهٔ تاکسونومی در دسته هم بار می‌شود ===\n";

$radio = phpfile( 'inc/tara-radio.php' );
t( 'tara_radio_is_tax_page دسته را می‌شناسد', (bool) preg_match( '/is_category\(\).*?tara_tax_is_music\( \'category\' \)/s', $radio ) || false !== strpos( $radio, "tara_tax_is_music( 'category' )" ) );

$artf = phpfile( 'inc/ab-artist-fields.php' );
t( 'JS دکمه برای تاکسونومی‌های سربرگ‌دار بار می‌شود', false !== strpos( $artf, 'tara_tax_is_music' ) && false !== strpos( $artf, 'tara_enqueue_desc_toggle' ) );

t( 'فاصلهٔ فهرست در tara-tax.css تعریف شده', false !== strpos( $taxcss, '.ty-archive__list {' ) );

/* ======================================================================
   ۴) چیدمانِ صفحهٔ هنرمند نشکسته
   ====================================================================== */
echo "\n=== ۴) صفحهٔ هنرمند (رگرسیون) ===\n";
$artistcss = css( 'tara-artist.css' );
t(
	'ty-artist چیدمانِ ستونیِ خودش را دارد (اولویت بالاتر)',
	false !== strpos( $artistcss, '.ty-artist .ty-tracklist {' )
);

echo "\n" . str_repeat( '─', 60 ) . "\n";
printf( "نتیجه: %d موفق، %d ناموفق\n", $GLOBALS['__pass'], $GLOBALS['__fail'] );
exit( $GLOBALS['__fail'] > 0 ? 1 : 0 );
