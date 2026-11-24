<?php
/**
 * tara-assets.php — انتخابِ نسخهٔ فشردهٔ CSS (نسخهٔ ۳.۸.۳)
 * ==========================================================
 * «فشرده‌سازی، بدون ترکیب»: هر فایل CSS جدا می‌ماند (ترتیب و کشِ
 * صفحه‌به‌صفحه دست‌نخورده) ولی همان فایل با نسخهٔ کم‌حجم‌تر سرو می‌شود.
 * فشرده‌ها با `tools/minify-css.py` ساخته می‌شوند.
 *
 * اگر فایل `.min.css` نبود یا از منبع قدیمی‌تر بود، همان فایل اصلی
 * برمی‌گردد — یعنی فراموشیِ اجرای ابزار هرگز CSS کهنه را سرو نمی‌کند.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_css_rel' ) ) {
	/**
	 * اگر نسخهٔ فشردهٔ یک CSS موجود و به‌روز باشد، مسیر آن را برمی‌گرداند.
	 *
	 * v3.8.3 — «فشرده‌سازی، بدون ترکیب»: هر فایل جدا می‌ماند (ترتیب و کشِ
	 * صفحه‌به‌صفحه دست‌نخورده) ولی همان فایل با نسخهٔ کم‌حجم‌تر سرو می‌شود.
	 * فشرده‌ها با `tools/minify-css.py` ساخته می‌شوند. اگر فایل `.min.css`
	 * نبود یا از منبع قدیمی‌تر بود، همان فایل اصلی برمی‌گردد — یعنی فراموشیِ
	 * اجرای ابزار هرگز CSS کهنه را سرو نمی‌کند.
	 *
	 * @param string $rel مسیر نسبی، مثل `/css/tara-ui.css`.
	 * @return string
	 */
	function tara_css_rel( $rel ) {
		if ( ! is_string( $rel ) || '' === $rel || '.css' !== substr( $rel, -4 ) ) {
			return $rel;
		}

		/* v3.17.14 — نتیجه در حافظهٔ همان درخواست کش می‌شود.
		   این تابع در حلقهٔ استایل‌ها چند بار با همان مسیر صدا زده
		   می‌شود و هر بار ۲×is_file + ۲×filemtime هزینه دارد.
		   کشِ استاتیکِ محلی، بدونِ اثرِ ماندگار، سیستم را دست‌نخورده
		   نگه می‌دارد ولی syscallِ تکراری حذف می‌شود. */
		static $memo = array();
		if ( isset( $memo[ $rel ] ) ) {
			return $memo[ $rel ];
		}

		$min = substr( $rel, 0, -4 ) . '.min.css';
		$src = TARANEHYAR_DIR . $rel;
		$dst = TARANEHYAR_DIR . $min;
		if ( ! is_file( $dst ) || ! is_file( $src ) ) {
			$memo[ $rel ] = $rel;
			return $rel;
		}
		$src_time = @filemtime( $src );
		$dst_time = @filemtime( $dst );
		if ( ! $src_time || ! $dst_time || $dst_time < $src_time ) {
			$memo[ $rel ] = $rel;
			return $rel;
		}

		$memo[ $rel ] = $min;
		return $min;
	}
}
