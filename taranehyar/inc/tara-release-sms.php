<?php
/** Transactional release notifications; uses the already configured SMS provider. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Called within the locked request mutation, before it is persisted. */
function tara_release_sms_queue( $row, $event ) {
	if ( ! in_array( $event, array( 'paid', 'published' ), true ) || ! is_array( $row ) ) { return $row; }
	if ( ! isset( $row['sms_events'] ) || ! is_array( $row['sms_events'] ) ) { $row['sms_events'] = array(); }
	if ( isset( $row['sms_events'][ $event ] ) ) { return $row; }
	$row['sms_events'][ $event ] = array( 'status' => 'pending', 'time' => time(), 'url' => 'published' === $event && isset( $row['publication_url'] ) ? $row['publication_url'] : '' );
	return $row;
}

/** Schedule only a pending event. Duplicate hooks do not create extra work. */
function tara_release_sms_schedule( $user_id, $id, $event ) {
	if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_schedule_single_event' ) ) { return false; }
	$args = array( (int) $user_id, (string) $id, (string) $event );
	if ( wp_next_scheduled( 'tara_release_sms_event', $args ) ) { return true; }
	return (bool) wp_schedule_single_event( time() + 5, 'tara_release_sms_event', $args );
}

/** Unlike OTP, transactional texts must not be sent using the OTP pattern. */
function tara_release_sms_send_text( $phone, $text ) {
	if ( ! function_exists( 'tara_acc_opt' ) || ! function_exists( 'tara_acc_phone' ) || ! function_exists( 'tara_acc_http' ) ) { return false; }
	$phone = tara_acc_phone( $phone );
	if ( '' === $phone || ! is_string( $text ) || '' === trim( $text ) || strlen( $text ) > 3000 ) { return false; }
	$provider = (string) tara_acc_opt( 'sms', 'dev' );
	if ( 'webhook' === $provider ) { return function_exists( 'tara_acc_sms_webhook' ) && tara_acc_sms_webhook( $phone, '', $text ); }
	if ( 'melipayamak' !== $provider ) { return false; }
	$user = trim( (string) tara_acc_opt( 'sms_user', '' ) ); $pass = (string) tara_acc_opt( 'sms_pass', '' ); $from = tara_acc_digits( (string) tara_acc_opt( 'sms_from', '' ) );
	if ( '' === $user || '' === $pass || '' === $from ) { return false; }
	$res = tara_acc_http( 'https://rest.payamak-panel.com/api/SendSMS/SendSMS', array( 'username' => $user, 'password' => $pass, 'to' => $phone, 'from' => $from, 'text' => $text, 'isflash' => false ) );
	if ( empty( $res['ok'] ) ) { return false; }
	$json = json_decode( $res['body'], true );
	if ( ! is_array( $json ) ) { return false; }
	if ( isset( $json['RetStatus'] ) && 1 !== (int) $json['RetStatus'] ) { return false; }
	if ( isset( $json['Value'] ) ) { return is_numeric( $json['Value'] ) && (float) $json['Value'] > 0; }
	if ( isset( $json['recId'] ) ) { return is_numeric( $json['recId'] ) && (float) $json['recId'] > 0; }
	return isset( $json['RetStatus'] ) && 1 === (int) $json['RetStatus'];
}

/** A pending job is persisted as sending before the network call; uncertain sends are never auto-retried. */
function tara_release_sms_process( $user_id, $id, $event ) {
	$user_id = (int) $user_id;
	if ( ! in_array( $event, array( 'paid', 'published' ), true ) || ! function_exists( 'tara_release_change_request' ) ) { return; }
	$claimed = false;
	$row = tara_release_change_request( $user_id, $id, function ( $row ) use ( $event, &$claimed ) {
		if ( empty( $row['sms_events'][ $event ] ) || 'pending' !== $row['sms_events'][ $event ]['status'] ) { return $row; }
		$row['sms_events'][ $event ]['status'] = 'sending'; $row['sms_events'][ $event ]['attempted_at'] = time(); $claimed = true; return $row;
	} );
	if ( is_wp_error( $row ) ) { if ( 'release_lock' === $row->get_error_code() && function_exists( 'wp_schedule_single_event' ) ) { wp_schedule_single_event( time() + 60, 'tara_release_sms_event', array( $user_id, $id, $event ) ); } return; }
	if ( ! $claimed ) { return; }
	$event_data = $row['sms_events'][ $event ];
	$enabled = function_exists( 'ot_get_option' ) && 'on' === ot_get_option( 'release_sms_' . $event . '_on', 'on' );
	$phone = function_exists( 'tara_acc_user_phone' ) ? tara_acc_user_phone( $user_id ) : '';
	$valid_event = 'paid' === $event ? in_array( $row['status'], array( 'paid', 'preparing', 'published' ), true ) : ( 'published' === $row['status'] && ! empty( $event_data['url'] ) && isset( $row['publication_url'] ) && $event_data['url'] === $row['publication_url'] );
	$default = 'paid' === $event ? 'پرداخت شما تأیید و اثر ثبت شد. پس از بررسی و در صورت تأیید، درباره انتشار به شما اطلاع می‌دهیم. {site}' : 'اثر شما منتشر شد: {url} {site}';
	$template = function_exists( 'ot_get_option' ) ? ot_get_option( 'release_sms_' . $event . '_text', $default ) : $default;
	if ( ! is_string( $template ) || '' === trim( $template ) ) { $template = $default; }
	$url = 'published' === $event ? $event_data['url'] : '';
	$text = strtr( sanitize_textarea_field( $template ), array( '{site}' => get_bloginfo( 'name' ), '{url}' => $url ) );
	if ( 'published' === $event && false === strpos( $text, $url ) ) { $text .= ' ' . $url; }
	$accepted = $enabled && $valid_event && '' !== $phone && tara_release_sms_send_text( $phone, $text );
	tara_release_change_request( $user_id, $id, function ( $row ) use ( $event, $accepted ) {
		if ( isset( $row['sms_events'][ $event ]['status'] ) && 'sending' === $row['sms_events'][ $event ]['status'] ) { $row['sms_events'][ $event ]['status'] = $accepted ? 'accepted' : 'failed'; $row['sms_events'][ $event ]['finished_at'] = time(); }
		return $row;
	} );
}
