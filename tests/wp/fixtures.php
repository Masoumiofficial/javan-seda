<?php
/** ساختِ دادهٔ واقعیِ آزمون: هنرمندان، آهنگ‌ها، پلی‌لیست‌ها */
/**
 * بازنشانیِ کاملِ وضعیتِ سراسری قبل از هر سناریو.
 * دیتابیسِ staging بین اجراها می‌ماند، پس وضعیت‌هایی مثل «چسبان‌ها»
 * (که روی get_option می‌نشینند، نه روی نوشته‌ها) هم باید پاک شوند؛
 * وگرنه سناریوی بعدی «انتخاب تحریریه» را از اجرای قبلی می‌بیند.
 */
function wp_fixture_reset() {
	global $wpdb;
	update_option( 'sticky_posts', array() );
	wp_cache_flush();
	foreach ( array( 'post', 'tara_playlist' ) as $type ) {
		foreach ( get_posts( array( 'post_type' => $type, 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids' ) ) as $id ) {
			wp_delete_post( $id, true );
		}
	}
	foreach ( get_terms( array( 'taxonomy' => array( 'artist', 'singer', 'tara_genre', 'tara_mood' ), 'hide_empty' => false ) ) as $term ) {
		if ( $term instanceof WP_Term ) {
			wp_delete_term( $term->term_id, $term->taxonomy );
		}
	}
}

function wp_fixture_artist( $name, $slug, $meta = array() ) {
	$tax  = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
	$term = term_exists( $name, $tax );
	if ( ! $term ) {
		$term = wp_insert_term( $name, $tax, array( 'slug' => $slug ) );
	}
	if ( is_wp_error( $term ) ) {
		return 0;
	}
	$id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
	foreach ( $meta as $k => $v ) {
		update_term_meta( $id, $k, $v );
	}
	return $id;
}

function wp_fixture_track( $title, $artist_term_ids, $meta = array(), $tax_terms = array(), $post_date = '' ) {
	$args = array( 'post_title' => $title, 'post_status' => 'publish', 'post_type' => 'post', 'post_content' => 'متن ' . $title );
	if ( '' !== $post_date ) {
		$args['post_date']     = $post_date;
		$args['post_date_gmt'] = get_gmt_from_date( $post_date );
	}
	$id = wp_insert_post( $args );
	foreach ( (array) $artist_term_ids as $tid ) {
		wp_set_object_terms( $id, array( (int) $tid ), tara_artist_tax() );
	}
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	foreach ( $tax_terms as $tax => $term_ids ) {
		wp_set_object_terms( $id, array_map( 'intval', (array) $term_ids ), $tax );
	}
	return $id;
}

/** ساخت پیوست واقعی از یک فایل موجود در قالب (برای سنجش تصویر شاخص). */
function wp_fixture_attachment( $title, $file ) {
	$uploads = wp_upload_dir();
	$dest    = trailingslashit( $uploads['path'] ) . basename( $file );
	if ( ! file_exists( $dest ) ) {
		@copy( $file, $dest );
	}
	$type = wp_check_filetype( $dest );
	$id   = wp_insert_attachment( array(
		'post_title'     => $title,
		'post_mime_type' => $type['type'] ? $type['type'] : 'image/jpeg',
		'post_status'    => 'inherit',
	), $dest );
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$meta = wp_generate_attachment_metadata( $id, $dest );
	if ( $meta ) {
		wp_update_attachment_metadata( $id, $meta );
	}
	return (int) $id;
}
