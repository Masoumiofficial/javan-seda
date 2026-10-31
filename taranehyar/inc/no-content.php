<?php
/**
 * «چیزی پیدا نشد» — برای جست‌وجوی بی‌نتیجه، آرشیو خالی و دستهٔ بدون نوشته
 * =========================================================================
 * v3.8.0 — بازنویسی کامل. نسخهٔ قبلی سه ایراد جدی داشت:
 *   ۱) بعد از ۱۵ ثانیه کاربر را **به صفحهٔ اصلی می‌برد**؛ کسی که جست‌وجو
 *      کرده بود و نتیجه ندیده بود، وسط خواندنِ راهنما پرت می‌شد.
 *   ۲) اسکریپتش به `document.getElementById("count")` اشاره می‌کرد که در
 *      این مارکاپ وجود نداشت ⇒ هر ثانیه یک `TypeError` در کنسول.
 *   ۳) تصویر ۶۲۶×۴۹۶ با alt «خطای ۴۰۴» برای «نتیجهٔ خالی جست‌وجو» بود؛
 *      هم پیام غلط بود، هم ۵۰ کیلوبایت تصویر بی‌مصرف.
 *
 * حالا: یک پیام روشن + همان عبارت جست‌وجو + فرمِ جست‌وجوی تازه + چند
 * میان‌بُر واقعی. هیچ JS و هیچ ریدایرکتی در کار نیست.
 *
 * @package Taranehyar
 */

$tara_empty_msg = 'چیزی پیدا نشد.';
$tara_empty_sub = 'ممکن است نام را با املای دیگری نوشته باشید یا این بخش هنوز خالی باشد.';

if ( is_search() ) {
	$tara_empty_msg = 'برای «' . get_search_query() . '» چیزی پیدا نشد.';
	$tara_empty_sub = 'املای نام را بررسی کنید، نام هنرمند را جدا جست‌وجو کنید، یا از فهرست هنرمندان شروع کنید.';
} elseif ( is_category() || is_tag() || is_tax() ) {
	$tara_empty_msg = 'این بخش هنوز اثری ندارد.';
	$tara_empty_sub = 'به‌زودی آثار تازه این‌جا منتشر می‌شود. تا آن موقع از فهرست‌های زیر استفاده کنید.';
}
?>
<div class="fulls_post_txt p404-textc ab-empty">

	<div class="ab-empty__badge" aria-hidden="true"><i class="icofont-music-alt"></i></div>

	<h1 class="ab-empty__title"><?php echo esc_html( $tara_empty_msg ); ?></h1>
	<p class="ab-empty__sub"><?php echo esc_html( $tara_empty_sub ); ?></p>

	<form class="ab-empty__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="ab-empty-q">جست‌وجو در سایت</label>
		<input id="ab-empty-q" class="meta searchfield" type="search" name="s" placeholder="نام آهنگ یا هنرمند …" autocomplete="off" enterkeyhint="search">
		<button type="submit"><i class="icofont-search" aria-hidden="true"></i> جست‌وجو</button>
	</form>

	<div class="ab-empty__links">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><i class="icofont-home" aria-hidden="true"></i> صفحهٔ اصلی</a>
		<a href="<?php echo esc_url( home_url( '/music/' ) ); ?>"><i class="icofont-music-notes" aria-hidden="true"></i> تازه‌ترین آهنگ‌ها</a>
		<?php if ( function_exists( 'tara_artists_index_url' ) ) : ?>
			<a href="<?php echo esc_url( tara_artists_index_url() ); ?>"><i class="icofont-microphone-alt" aria-hidden="true"></i> همهٔ هنرمندان</a>
		<?php endif; ?>
	</div>

	<div class="clear"></div>
</div>
