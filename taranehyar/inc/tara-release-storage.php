<?php
/** Adapter to the existing artist request store. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Caller must authenticate the user, validate the nonce and unslash input.
 * All writes to tara_artist_requests must share tara_hub_request_lock.
 * Returns the existing id on a retry with the same token, never appends twice.
 */
function tara_release_save_request( $user_id, $input, $token, $now, $files = array() ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 || ! defined( 'TARA_RELEASE_INTEGRATED' ) || ! TARA_RELEASE_INTEGRATED || ! function_exists( 'tara_hub_request_lock' ) || ! tara_hub_request_lock( $user_id ) ) { return new WP_Error( 'release_lock', 'ثبت درخواست در حال حاضر امکان‌پذیر نیست.' ); }
	if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{32,64}$/D', $token ) ) { return new WP_Error( 'release_token', 'شناسهٔ درخواست نامعتبر است.' ); }
	$rows = tara_hub_requests( $user_id );
	$existing = tara_release_find_submission( $rows, $token );
	if ( null !== $existing ) { return $existing['id']; }
	if ( ! is_array( $files ) ) { return new WP_Error( 'release_files', 'فایل‌ها نامعتبرند.' ); }
	$request = tara_release_build_request( $input, $token, $now, isset( $files['work'] ) );
	if ( is_wp_error( $request ) ) { return $request; }
	$request['files'] = $files;
	array_unshift( $rows, $request );
	if ( false === update_user_meta( $user_id, 'tara_artist_requests', $rows ) ) { return new WP_Error( 'release_store', 'ذخیرهٔ درخواست انجام نشد.' ); }
	return $request['id'];
}

/** Update one release record only; callback receives an immutable copy of its current value.
 * Caller authenticates, checks nonce/ownership or manager capability, then locks and persists.
 */
function tara_release_change_request( $user_id, $request_id, $callback ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 || ! defined( 'TARA_RELEASE_INTEGRATED' ) || ! TARA_RELEASE_INTEGRATED || ! function_exists( 'tara_hub_request_lock' ) || ! tara_hub_request_lock( $user_id ) ) { return new WP_Error( 'release_lock', 'ویرایش درخواست در حال حاضر امکان‌پذیر نیست.' ); }
	if ( ! is_string( $request_id ) || ! preg_match( '/^release_[a-f0-9]{32,64}$/D', $request_id ) || ! is_callable( $callback ) ) { return new WP_Error( 'release_id', 'شناسهٔ درخواست نامعتبر است.' ); }
	$rows = tara_hub_requests( $user_id );
	foreach ( $rows as $index => $row ) {
		if ( ! is_array( $row ) || ! isset( $row['type'], $row['id'] ) || 'release' !== $row['type'] || $request_id !== $row['id'] ) { continue; }
		$changed = call_user_func( $callback, $row );
		if ( is_wp_error( $changed ) ) { return $changed; }
		if ( ! is_array( $changed ) || ! isset( $changed['type'], $changed['id'] ) || 'release' !== $changed['type'] || $request_id !== $changed['id'] ) { return new WP_Error( 'release_update', 'تغییر درخواست نامعتبر است.' ); }
		if ( $changed === $row ) { return $row; }
		$rows[ $index ] = $changed;
		if ( false === update_user_meta( $user_id, 'tara_artist_requests', $rows ) ) { return new WP_Error( 'release_store', 'ذخیرهٔ تغییرات انجام نشد.' ); }
		return $changed;
	}
	return new WP_Error( 'release_missing', 'درخواست پیدا نشد.' );
}
