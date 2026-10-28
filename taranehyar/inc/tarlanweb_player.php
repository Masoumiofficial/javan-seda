<?php
/**
 * پلیر صفحهٔ اصلی — نسخهٔ ۳.۷.۰
 * ==========================================================================
 * همان پلیر «رادیو»ی صفحهٔ هنرمند و صفحهٔ آهنگ؛ فقط فهرستش از نوشته‌هایی
 * می‌آید که فیلد `plym` روی ۱ دارند.
 *
 * تغییر نسبت به نسخهٔ قبل: مارکاپ قبلی (`.player` / `.play-list` / `#audio`)
 * به یک کارت واحد تبدیل شد و اسکریپت اختصاصی خودش را دارد (`js/tara-player.js`).
 * منطق انتخاب نوشته‌ها دست‌نخورده مانده است: `plym = 1`، به ترتیب متای بازدید.
 *
 * خاموش‌کردن: تنظیمات قالب → صفحهٔ اصلی → «پلیر ایندکس» = خاموش
 *
 * @package Taranehyar
 */

$tara_indpl = ot_get_option( 'indpl' );

if ( 'off' !== $tara_indpl && function_exists( 'tara_player' ) ) {

	$tara_home_num   = ot_get_option( 'v_num' );
	$tara_home_num   = ! empty( $tara_home_num ) ? (int) $tara_home_num : 8;
	$tara_home_query = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => $tara_home_num,
			'orderby'                => 'meta_value_num',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
			'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'plym',
					'value'   => '1',
					'compare' => 'LIKE',
				),
			),
		)
	);

	if ( $tara_home_query->have_posts() ) {
		$tara_home_tracks = tara_track_playlist( $tara_home_query->posts );

		if ( ! empty( $tara_home_tracks ) ) {
			echo '<div class="ty-home-player">';
			echo tara_player( // phpcs:ignore WordPress.Security.EscapeOutput -- خروجی همین قالب
				array(
					'tracks'  => $tara_home_tracks,
					'title'   => 'پخش آنلاین — تازه‌ترین‌ها',
					'context' => 'home',
				)
			);
			echo '</div>';
		}
	}

	wp_reset_postdata();
}
