<?php
/** Bounded comment batches for single posts, retaining the theme comment callback. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tara_cm_batch_size() {
	return min( 20, max( 5, function_exists( 'tara_comments_per_page' ) ? (int) tara_comments_per_page() : 20 ) );
}
function tara_cm_is_post( $post ) {
	return $post && 'post' === $post->post_type && 'publish' === $post->post_status && empty( $post->post_password );
}
// Override the old front-end paging layer only for public single posts; leave admin and other templates untouched.
add_filter( 'pre_option_page_comments', function ( $pre ) {
	if ( ! is_admin() && ! wp_doing_ajax() && is_singular( 'post' ) && tara_cm_is_post( get_queried_object() ) ) { return '0'; }
	return $pre;
}, 99 );
/* Stop core fetching an unlimited comment tree before our bounded renderer runs. */
add_filter( 'comments_template_query_args', function ( $args ) {
	if ( is_singular( 'post' ) && tara_cm_is_post( get_queried_object() ) ) {
		$args['number'] = 1; $args['hierarchical'] = false; $args['parent'] = 0;
	}
	return $args;
}, 99 );

function tara_cm_order( $parent = 0 ) { return $parent ? 'ASC' : ( 'newest' === get_option( 'default_comments_page', 'newest' ) ? 'DESC' : 'ASC' ); }
function tara_cm_prepend() {
	$display = 'desc' === strtolower( (string) get_option( 'comment_order', 'asc' ) ) ? 'DESC' : 'ASC';
	return tara_cm_order() !== $display;
}
function tara_cm_cursor( $comment ) { return $comment->comment_date_gmt . '|' . (int) $comment->comment_ID; }
function tara_cm_valid_cursor( $value ) {
	return is_string( $value ) && ( '' === $value || preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\|[1-9][0-9]{0,18}$/D', $value ) );
}
/** Keyset pagination prevents shifted offsets from duplicating entries after inserts. */
function tara_cm_get_batch( $post_id, $parent, $cursor, $limit ) {
	global $wpdb;
	$order = tara_cm_order( $parent );
	$filter = null;
	if ( '' !== $cursor ) {
		list( $date, $id ) = explode( '|', $cursor, 2 );
		$operator = 'ASC' === $order ? '>' : '<';
		$filter = function ( $clauses ) use ( $wpdb, $date, $id, $operator ) {
			$clauses['where'] .= $wpdb->prepare( " AND ({$wpdb->comments}.comment_date_gmt $operator %s OR ({$wpdb->comments}.comment_date_gmt = %s AND {$wpdb->comments}.comment_ID $operator %d))", $date, $date, $id );
			return $clauses;
		};
		add_filter( 'comments_clauses', $filter, 10, 1 );
	}
	$args = array( 'post_id' => $post_id, 'parent' => $parent, 'status' => 'approve', 'hierarchical' => false, 'number' => max( 1, min( 21, $limit + 1 ) ), 'orderby' => array( 'comment_date_gmt' => $order, 'comment_ID' => $order ), 'order' => $order, 'no_found_rows' => true );
	// WP caches by query vars, not comments_clauses: isolate every cursor in its cache domain.
	$args['cache_domain'] = 'tara-comments-' . md5( $cursor );
	if ( 0 === (int) $parent && ! get_option( 'thread_comments' ) ) { unset( $args['parent'] ); }
	try { $comments = get_comments( $args ); } finally { if ( $filter ) { remove_filter( 'comments_clauses', $filter, 10 ); } }
	return is_array( $comments ) ? $comments : array();
}
function tara_cm_button( $parent, $cursor, $more, $label ) {
	if ( ! $more ) { return; }
	printf( '<div class="tara-cmore-control" data-parent="%d" data-cursor="%s"><button type="button" class="tara-cmore-button">%s</button><span class="tara-cmore-status" role="status" aria-live="polite"></span></div>', (int) $parent, esc_attr( $cursor ), esc_html( $label ) );
}
/** Recursion has a shared budget; long threads gain their own continuation controls. */
function tara_cm_render_batch( $post_id, $parent, $cursor, &$budget, $depth, $limit ) {
	$comments = tara_cm_get_batch( $post_id, $parent, $cursor, min( $limit, max( 0, $budget ) ) );
	$selected = array_slice( $comments, 0, min( $limit, max( 0, $budget ) ) );
	$last = $selected ? tara_cm_cursor( $selected[ count( $selected ) - 1 ] ) : $cursor;
	$more = count( $comments ) > count( $selected );
	if ( ! $parent && tara_cm_prepend() ) { $selected = array_reverse( $selected ); }
	$count = count( $selected ); $shown = 0;
	foreach ( $selected as $comment ) {
		--$budget; ++$shown;
		$GLOBALS['comment'] = $comment;
		$GLOBALS['comment_depth'] = $depth;
		$max_depth = get_option( 'thread_comments' ) ? max( 1, min( 10, (int) get_option( 'thread_comments_depth', 5 ) ) ) : 1;
		comment_loop_cd( $comment, array( 'max_depth' => $max_depth ), $depth );
		if ( get_option( 'thread_comments' ) && $depth < 100 ) {
			echo '<ol class="children" data-tara-children="' . (int) $comment->comment_ID . '">';
			// Reserve space for siblings already selected so cursor and DOM cannot diverge.
			$child_budget = max( 0, $budget - ( $count - $shown ) );
			$before = $child_budget;
			$child = tara_cm_render_batch( $post_id, (int) $comment->comment_ID, '', $child_budget, $depth + 1, 5 );
			$budget -= $before - $child_budget;
			echo '</ol>';
			tara_cm_button( (int) $comment->comment_ID, $child['cursor'], $child['more'], 'مشاهده پاسخ‌های بیشتر' );
		}
		echo '</li>';
	}
	return array( 'cursor' => $last, 'more' => $more );
}
function tara_comments_load_more() {
	$post_id = (int) get_queried_object_id();
	$need_login = get_option( 'comment_registration' ) && ! is_user_logged_in();
	if ( $need_login ) { echo '<p class="nopassword">لطفاً براي ارسال دیدگاه، ابتدا وارد حساب كاربري خود بشويد</p>'; }
	elseif ( function_exists( 'comment_form_cd' ) ) { comment_form_cd(); }
	if ( (int) get_comments_number( $post_id ) > 0 ) {
		if ( function_exists( 'tara_comments_heading' ) ) { echo tara_comments_heading( $post_id ); }
		$budget = 30;
		echo '<div class="tara-cmore" data-post="' . esc_attr( $post_id ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'tara_comments_' . $post_id ) ) . '" data-prepend="' . ( tara_cm_prepend() ? '1' : '0' ) . '" data-ajax="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '"><ol class="commentlist" data-tara-children="0">';
		$was_in_loop = ! empty( $GLOBALS['in_comment_loop'] );
		$GLOBALS['in_comment_loop'] = true;
		add_filter( 'comment_text', 'wp_kses_post', 999 );
		$result = tara_cm_render_batch( $post_id, 0, '', $budget, 1, tara_cm_batch_size() );
		remove_filter( 'comment_text', 'wp_kses_post', 999 );
		$GLOBALS['in_comment_loop'] = $was_in_loop;
		echo '</ol>';
		tara_cm_button( 0, $result['cursor'], $result['more'], 'مشاهده دیدگاه‌های بیشتر' );
		echo '</div>';
	} elseif ( ! comments_open() && ! is_page() && post_type_supports( get_post_type(), 'comments' ) ) { echo '<p class="nocomments">مجوز ارسال دیدگاه داده نشده است!</p>'; }
}
function tara_cm_post_input() {
	$id = isset( $_POST['post_id'] ) && is_scalar( $_POST['post_id'] ) && preg_match( '/^[1-9][0-9]{0,18}$/D', (string) $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$post = $id ? get_post( $id ) : null;
	return tara_cm_is_post( $post ) ? $post : null;
}
function tara_cm_nonce_ajax() {
	$post = tara_cm_post_input();
	if ( ! $post ) { wp_send_json_error( array( 'message' => 'نوشته پیدا نشد.' ), 400 ); }
	wp_send_json_success( array( 'nonce' => wp_create_nonce( 'tara_comments_' . $post->ID ) ) );
}
function tara_cm_ajax() {
	$post = tara_cm_post_input();
	if ( ! $post ) { wp_send_json_error( array( 'message' => 'نوشته پیدا نشد.' ), 400 ); }
	$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'tara_comments_' . $post->ID ) ) { wp_send_json_error( array( 'code' => 'nonce', 'message' => 'نشست منقضی شده است.' ), 403 ); }
	if ( ! isset( $_POST['parent'] ) || ! is_scalar( $_POST['parent'] ) || ! preg_match( '/^(?:0|[1-9][0-9]{0,18})$/D', (string) $_POST['parent'] ) ) { wp_send_json_error( array( 'message' => 'درخواست نامعتبر است.' ), 400 ); }
	$parent = absint( $_POST['parent'] );
	$cursor = isset( $_POST['cursor'] ) && is_string( $_POST['cursor'] ) ? wp_unslash( $_POST['cursor'] ) : '';
	if ( ! tara_cm_valid_cursor( $cursor ) ) { wp_send_json_error( array( 'message' => 'درخواست نامعتبر است.' ), 400 ); }
	$depth = 1; $current = $parent;
	while ( $current ) {
		$ancestor = get_comment( $current );
		if ( ! $ancestor || (int) $ancestor->comment_post_ID !== (int) $post->ID || '1' !== (string) $ancestor->comment_approved || ++$depth > 100 ) { wp_send_json_error( array( 'message' => 'پاسخ پیدا نشد.' ), 400 ); }
		$current = (int) $ancestor->comment_parent;
	}
	$GLOBALS['post'] = $post; setup_postdata( $post );
	if ( function_exists( 'tara_opt_on' ) && ! tara_opt_on( 'cm_off' ) ) { add_filter( 'comments_open', '__return_false', 99 ); }
	$budget = 30;
	ob_start();
	$GLOBALS['in_comment_loop'] = true;
	add_filter( 'comment_text', 'wp_kses_post', 999 );
	$result = tara_cm_render_batch( $post->ID, $parent, $cursor, $budget, $depth, $parent ? 5 : tara_cm_batch_size() );
	remove_filter( 'comment_text', 'wp_kses_post', 999 );
	$GLOBALS['in_comment_loop'] = false;
	$html = ob_get_clean();
	wp_send_json_success( array( 'html' => $html, 'cursor' => $result['cursor'], 'more' => $result['more'] ) );
}
add_action( 'wp_ajax_tara_comments_more', 'tara_cm_ajax' );
add_action( 'wp_ajax_nopriv_tara_comments_more', 'tara_cm_ajax' );
add_action( 'wp_ajax_tara_comments_nonce', 'tara_cm_nonce_ajax' );
add_action( 'wp_ajax_nopriv_tara_comments_nonce', 'tara_cm_nonce_ajax' );
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_singular( 'post' ) ) { return; }
	wp_enqueue_script( 'tara-comments-more', get_template_directory_uri() . '/js/tara-comments-more.js', array(), function_exists( 'taranehyar_asset_ver' ) ? taranehyar_asset_ver( '/js/tara-comments-more.js' ) : null, true );
	wp_enqueue_style( 'tara-comments-more', get_template_directory_uri() . '/css/tara-comments-more.css', array(), function_exists( 'taranehyar_asset_ver' ) ? taranehyar_asset_ver( '/css/tara-comments-more.css' ) : null );
}, 40 );
add_filter( 'get_comment_link', function ( $link, $comment ) {
	$post = get_post( $comment->comment_post_ID );
	return tara_cm_is_post( $post ) ? get_permalink( $post ) . '#comment-' . (int) $comment->comment_ID : $link;
}, 20, 2 );
add_action( 'template_redirect', function () {
	if ( ! is_singular( 'post' ) || ! tara_cm_is_post( get_queried_object() ) ) { return; }
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( ! preg_match( '~(?:^|/)comment-page-[0-9]+(?:/|$)~', $path ) && ! isset( $_GET['cpage'] ) ) { return; }
	wp_safe_redirect( get_permalink( get_queried_object_id() ), 301, 'Taranehyar' );
	exit;
}, 1 );
