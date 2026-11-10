<?php
/** In-progress HTTP adapter; deliberately not hooked into the theme yet. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** The logged-in owner may submit, but cannot select a user id or price. */
function tara_release_ajax_submit() {
	check_ajax_referer( 'tara_account', 'nonce' );
	$user_id = (int) get_current_user_id();
	if ( $user_id <= 0 ) { wp_send_json_error( array( 'message' => 'ابتدا وارد حساب خود شوید.' ), 403 ); }
	if ( ! defined( 'TARA_RELEASE_INTEGRATED' ) || ! TARA_RELEASE_INTEGRATED || ! function_exists( 'ot_get_option' ) || 'on' !== ot_get_option( 'release_enabled', 'off' ) ) { wp_send_json_error( array( 'message' => 'ثبت درخواست فعال نیست.' ), 403 ); }
	$input = wp_unslash( $_POST );
	$token = isset( $input['submission_token'] ) && is_string( $input['submission_token'] ) ? $input['submission_token'] : '';
	$result = tara_release_save_request( $user_id, $input, $token, time() );
	if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 ); }
	wp_send_json_success( array( 'id' => $result, 'message' => 'درخواست ثبت شد و در انتظار بررسی است.' ) );
}

/**
 * Manager operation; only explicit allowed transitions and quote data are accepted.
 * This never marks a request as paid. Payment comes from a verified WooCommerce event.
 */
function tara_release_admin_update( $user_id, $request_id, $input, $now ) {
	if ( ! current_user_can( 'manage_options' ) || ! is_array( $input ) ) { return new WP_Error( 'release_forbidden', 'دسترسی مجاز نیست.' ); }
	return tara_release_change_request( $user_id, $request_id, function( $row ) use ( $input, $now ) {
		$changed = $row;
		if ( isset( $input['note'] ) ) {
			if ( ! is_string( $input['note'] ) || strlen( $input['note'] ) > 16000 ) { return new WP_Error( 'release_note', 'یادداشت نامعتبر است.' ); }
			$changed['note'] = sanitize_textarea_field( $input['note'] );
		}
		if ( isset( $input['publication_url'] ) ) {
			if ( ! is_string( $input['publication_url'] ) || strlen( $input['publication_url'] ) > 1000 ) { return new WP_Error( 'release_url', 'لینک انتشار نامعتبر است.' ); }
			$url = $input['publication_url'];
			if ( '' !== $url ) {
				$parts = wp_parse_url( $url );
				if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) { return new WP_Error( 'release_url', 'لینک انتشار معتبر نیست.' ); }
				$url = esc_url_raw( $url, array( 'http', 'https' ) );
				if ( '' === $url ) { return new WP_Error( 'release_url', 'لینک انتشار معتبر نیست.' ); }
			}
			$changed['publication_url'] = $url;
		}
		if ( ! empty( $input['quote_revision'] ) ) {
			$changed = tara_release_set_quote( $changed, isset( $input['final_toman'] ) ? $input['final_toman'] : '', isset( $input['extra_costs'] ) ? $input['extra_costs'] : '', isset( $input['refund_terms'] ) ? $input['refund_terms'] : '', $input['quote_revision'] );
			if ( is_wp_error( $changed ) ) { return $changed; }
		}
		if ( isset( $input['status'] ) && is_string( $input['status'] ) && '' !== $input['status'] && $input['status'] !== $changed['status'] ) {
			$changed = tara_release_transition( $changed, $input['status'], 'admin', $now );
			if ( is_wp_error( $changed ) ) { return $changed; }
		}
		return $changed;
	} );
}
