<?php
/** HTTP and WooCommerce operations for artist release requests. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** The logged-in owner may submit, but cannot select a user id or price. */
function tara_release_ajax_submit() {
	check_ajax_referer( 'tara_account', 'nonce' );
	$user_id = (int) get_current_user_id();
	if ( $user_id <= 0 ) { wp_send_json_error( array( 'message' => 'ابتدا وارد حساب خود شوید.' ), 403 ); }
	if ( ! defined( 'TARA_RELEASE_INTEGRATED' ) || ! TARA_RELEASE_INTEGRATED || ! function_exists( 'ot_get_option' ) || 'on' !== ot_get_option( 'release_enabled', 'off' ) ) { wp_send_json_error( array( 'message' => 'ثبت درخواست فعال نیست.' ), 403 ); }
	$input = wp_unslash( $_POST );
	$token = isset( $input['submission_token'] ) && is_string( $input['submission_token'] ) ? $input['submission_token'] : '';
	if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{32,64}$/D', $token ) || ! tara_hub_request_lock( $user_id ) ) { wp_send_json_error( array( 'message' => 'شناسه نامعتبر است یا درخواست دیگری در حال پردازش است.' ), 400 ); }
	$existing = tara_release_find_submission( tara_hub_requests( $user_id ), $token );
	if ( $existing ) { wp_send_json_success( array( 'id' => $existing['id'], 'message' => 'درخواست پیش‌تر ثبت شده است.' ) ); }
	$uploads = tara_release_file_inputs( $_FILES );
	if ( is_wp_error( $uploads ) ) { wp_send_json_error( array( 'message' => $uploads->get_error_message() ), 400 ); }
	$valid = tara_release_validate( $input, isset( $uploads['work'] ) );
	if ( is_wp_error( $valid ) ) { wp_send_json_error( array( 'message' => $valid->get_error_message() ), 400 ); }
	$stored = array();
	foreach ( $uploads as $key => $upload ) {
		if ( 'on' !== ot_get_option( 'release_uploads_on', 'on' ) ) { $file = new WP_Error( 'release_upload', 'آپلود خصوصی فعال نیست.' ); }
		else { $extension = isset( $upload['name'] ) && is_string( $upload['name'] ) ? strtolower( pathinfo( $upload['name'], PATHINFO_EXTENSION ) ) : ''; $is_work = 'work' === $key || 0 === strpos( $key, 'work_' ); $kind = $is_work ? ( 'mp4' === $extension ? 'video' : 'audio' ) : $key; $file = tara_release_store_upload( $upload, $kind ); }
		if ( is_wp_error( $file ) ) {
			$dir = tara_release_private_dir(); if ( ! is_wp_error( $dir ) ) { foreach ( $stored as $old ) { @unlink( $dir . DIRECTORY_SEPARATOR . $old['token'] . '.bin' ); } }
			wp_send_json_error( array( 'message' => $file->get_error_message() ), 400 );
		}
		$stored[ $key ] = $file;
	}
	$result = tara_release_save_request( $user_id, $input, $token, time(), $stored );
	if ( is_wp_error( $result ) ) {
		$dir = tara_release_private_dir(); if ( ! is_wp_error( $dir ) ) { foreach ( $stored as $old ) { @unlink( $dir . DIRECTORY_SEPARATOR . $old['token'] . '.bin' ); } }
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
	}
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
		if ( $changed !== $row ) {
			if ( ! isset( $changed['history'] ) || ! is_array( $changed['history'] ) ) { $changed['history'] = array(); }
			$event = array( 'status' => $changed['status'], 'time' => $now, 'actor' => 'admin', 'event' => 'details_updated', 'note' => isset( $changed['note'] ) ? $changed['note'] : '' );
			if ( ! empty( $input['quote_revision'] ) ) { foreach ( array( 'final_toman', 'extra_costs', 'refund_terms', 'quote_revision' ) as $field ) { $event[ $field ] = $changed[ $field ]; } }
			$changed['history'][] = $event;
		}
		if ( isset( $input['status'] ) && is_string( $input['status'] ) && '' !== $input['status'] && $input['status'] !== $changed['status'] ) {
			$changed = tara_release_transition( $changed, $input['status'], 'admin', $now );
			if ( is_wp_error( $changed ) ) { return $changed; }
			if ( 'published' === $changed['status'] && function_exists( 'tara_release_sms_queue' ) ) { $changed = tara_release_sms_queue( $changed, 'published' ); }
		}
		return $changed;
	} );
}

/** Prepare at most one WooCommerce order after the owner accepts the exact quote. */
function tara_release_prepare_checkout( $user_id, $id, $hash ) {
	if ( ! function_exists( 'ot_get_option' ) || 'on' !== ot_get_option( 'release_payment_on', 'on' ) || ! tara_hub_wc_ready() ) { return new WP_Error( 'release_disabled', 'پرداخت در حال حاضر فعال نیست.' ); }
	if ( (int) $user_id <= 0 || ! is_string( $id ) || ! is_string( $hash ) || ! tara_hub_request_lock( $user_id ) ) { return new WP_Error( 'release_lock', 'درخواست دیگری در حال پردازش است؛ دوباره تلاش کنید.' ); }
	$rows = tara_hub_requests( $user_id ); $row = null;
	foreach ( $rows as $candidate ) { if ( isset( $candidate['id'], $candidate['type'] ) && 'release' === $candidate['type'] && $id === $candidate['id'] ) { $row = $candidate; break; } }
	if ( ! $row || ! isset( $row['service'] ) || 'international' === $row['service'] ) { return new WP_Error( 'release_service', 'پرداخت این درخواست در دسترس نیست؛ درخواست بین‌المللی تنها برای بررسی است.' ); }
	if ( ! empty( $row['order_id'] ) ) {
		$order = wc_get_order( (int) $row['order_id'] );
		$currency = $order ? $order->get_currency() : '';
		$expected = in_array( $currency, array( 'IRT', 'IRR' ), true ) && isset( $row['final_toman'] ) ? (int) $row['final_toman'] * ( 'IRR' === $currency ? 10 : 1 ) : -1;
		if ( $order && (int) $order->get_customer_id() === (int) $user_id && $order->needs_payment() && isset( $row['accepted_quote_hash'] ) && hash_equals( $row['accepted_quote_hash'], $hash ) && tara_release_quote_ready( $row ) && (string) $order->get_meta( '_tara_release_quote', true ) === $hash && (string) $order->get_meta( '_tara_release_request', true ) === $id && (int) $order->get_meta( '_tara_release_user', true ) === (int) $user_id && (string) $order->get_meta( '_tara_release_currency', true ) === $currency && (string) $order->get_meta( '_tara_release_expected', true ) === (string) $expected && abs( (float) $order->get_total() - $expected ) < 0.001 ) { return $order->get_checkout_payment_url(); }
		return new WP_Error( 'release_order', 'برای بررسی سفارش قبلی با مدیر تماس بگیرید.' );
	}
	$accepted = tara_release_accept_quote( $row, $hash, time() );
	if ( is_wp_error( $accepted ) || ! tara_release_quote_ready( $accepted ) ) { return new WP_Error( 'release_quote', 'پیش‌فاکتور معتبر نیست یا تغییر کرده است.' ); }
	$amount = tara_hub_wc_amount( $accepted['final_toman'] ); $order = null;
	try {
		$order = wc_create_order( array( 'customer_id' => (int) $user_id, 'created_via' => 'tara_artist_release' ) );
		if ( is_wp_error( $order ) ) { return new WP_Error( 'release_order', 'ساخت سفارش ناموفق بود.' ); }
		$item = new WC_Order_Item_Fee(); $item->set_name( 'همکاری و انتشار آثار: ' . $accepted['title'] );
		$item->set_tax_status( 'none' ); $item->set_amount( $amount ); $item->set_total( $amount ); $order->add_item( $item );
		$order->update_meta_data( '_tara_release_request', $id ); $order->update_meta_data( '_tara_release_user', (int) $user_id ); $order->update_meta_data( '_tara_release_quote', $hash );
		$order->update_meta_data( '_tara_release_currency', get_woocommerce_currency() );
		$order->update_meta_data( '_tara_release_expected', (string) $amount );
		$order->update_meta_data( '_tara_release_refund_terms', $accepted['refund_terms'] );
		$order->update_meta_data( '_tara_release_extra_costs', $accepted['extra_costs'] );
		$order->update_meta_data( '_tara_release_accepted_at', $accepted['accepted_at'] );
		$order->calculate_totals( false ); $order->save();
		if ( abs( (float) $order->get_total() - (float) $amount ) > 0.001 || $order->get_currency() !== get_woocommerce_currency() ) { $order->update_status( 'cancelled', 'مبلغ یا واحد پول معتبر نیست.' ); return new WP_Error( 'release_amount', 'مبلغ سفارش معتبر نیست.' ); }
		$accepted['order_id'] = (int) $order->get_id();
		$saved = tara_release_change_request( $user_id, $id, function () use ( $accepted ) { return $accepted; } );
		if ( is_wp_error( $saved ) ) { $order->update_status( 'cancelled', 'ثبت ارتباط درخواست ناموفق بود.' ); return $saved; }
		return $order->get_checkout_payment_url();
	} catch ( Throwable $e ) {
		if ( is_object( $order ) && ! is_wp_error( $order ) ) { $order->update_status( 'cancelled', 'خطا در ساخت سفارش.' ); }
		return new WP_Error( 'release_order', 'ساخت سفارش ناموفق بود؛ با مدیر تماس بگیرید.' );
	}
}

function tara_release_ajax_checkout() {
	check_ajax_referer( 'tara_account', 'nonce' );
	$user_id = (int) get_current_user_id();
	if ( $user_id <= 0 ) { wp_send_json_error( array( 'message' => 'ابتدا وارد حساب خود شوید.' ), 403 ); }
	$input = wp_unslash( $_POST );
	$id = isset( $input['request_id'] ) && is_string( $input['request_id'] ) ? $input['request_id'] : '';
	$hash = isset( $input['quote_hash'] ) && is_string( $input['quote_hash'] ) ? $input['quote_hash'] : '';
	if ( ! isset( $input['accept_quote'] ) || '1' !== $input['accept_quote'] ) { wp_send_json_error( array( 'message' => 'پذیرش مبلغ و شرایط اختصاصی الزامی است.' ), 400 ); }
	$result = tara_release_prepare_checkout( $user_id, $id, $hash );
	if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 ); }
	wp_send_json_success( array( 'checkout' => $result ) );
}

/** Server-side WooCommerce payment callback, not the browser return. */
function tara_release_wc_paid( $order_id ) {
	if ( ! function_exists( 'wc_get_order' ) ) { return; }
	$order = wc_get_order( $order_id );
	if ( ! $order || ! $order->is_paid() ) { return; }
	$user_id = (int) $order->get_meta( '_tara_release_user', true );
	$id = (string) $order->get_meta( '_tara_release_request', true );
	if ( $user_id <= 0 || '' === $id ) { return; }
	$updated = false;
	$result = tara_release_change_request( $user_id, $id, function ( $row ) use ( $order, $user_id, &$updated ) {
		if ( 'paid' === $row['status'] ) { return $row; }
		if ( ! tara_release_order_matches_paid( $row, $order, $user_id ) ) { return new WP_Error( 'release_payment', 'پرداخت قابل تأیید نیست.' ); }
		$updated = true;
		$paid = tara_release_transition( $row, 'paid', 'verified_gateway', time() );
		return ! is_wp_error( $paid ) && function_exists( 'tara_release_sms_queue' ) ? tara_release_sms_queue( $paid, 'paid' ) : $paid;
	} );
	if ( is_wp_error( $result ) && 'release_lock' === $result->get_error_code() && function_exists( 'wp_schedule_single_event' ) ) { wp_schedule_single_event( time() + 60, 'tara_release_retry_paid', array( (int) $order_id ) ); }
	if ( ! is_wp_error( $result ) && isset( $result['sms_events']['paid']['status'] ) && 'pending' === $result['sms_events']['paid']['status'] && function_exists( 'tara_release_sms_schedule' ) ) { tara_release_sms_schedule( $user_id, $id, 'paid' ); }
	if ( $updated && ! is_wp_error( $result ) && function_exists( 'tara_acc_notify' ) ) { tara_acc_notify( $user_id, array( 'title' => 'پرداخت درخواست انتشار تأیید شد.' ) ); }
}

/** Authorized forced download; the token is looked up in the owner's request, never accepted as a path. */
function tara_release_download() {
	$user_id = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : 0;
	$id = isset( $_GET['request_id'] ) && is_string( $_GET['request_id'] ) ? sanitize_text_field( wp_unslash( $_GET['request_id'] ) ) : '';
	$key = isset( $_GET['file'] ) && is_string( $_GET['file'] ) ? sanitize_key( wp_unslash( $_GET['file'] ) ) : '';
	if ( ! is_user_logged_in() || $user_id <= 0 || ( get_current_user_id() !== $user_id && ! current_user_can( 'manage_options' ) ) || ! tara_release_file_key_valid( $key ) ) { wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'tara_release_file_' . $user_id . '_' . $id . '_' . $key );
	$file = null;
	foreach ( tara_hub_requests( $user_id ) as $row ) {
		if ( isset( $row['type'], $row['id'], $row['files'][ $key ] ) && 'release' === $row['type'] && $id === $row['id'] ) { $file = $row['files'][ $key ]; break; }
	}
	$dir = tara_release_private_dir();
	if ( is_wp_error( $dir ) || ! is_array( $file ) || ! isset( $file['token'], $file['extension'] ) || ! is_string( $file['token'] ) || ! preg_match( '/^[a-f0-9]{48}$/D', $file['token'] ) || ! in_array( $file['extension'], array( 'mp3', 'wav', 'mp4', 'jpg', 'jpeg', 'png', 'pdf' ), true ) ) { wp_die( 'فایل موجود نیست.', '', array( 'response' => 404 ) ); }
	$path = $dir . DIRECTORY_SEPARATOR . $file['token'] . '.bin';
	if ( ! is_file( $path ) || is_link( $path ) || ! is_readable( $path ) ) { wp_die( 'فایل موجود نیست.', '', array( 'response' => 404 ) ); }
	nocache_headers(); header( 'Content-Type: application/octet-stream' ); header( 'X-Content-Type-Options: nosniff' ); header( 'Content-Disposition: attachment; filename="release-file.' . $file['extension'] . '"' ); header( 'Content-Length: ' . filesize( $path ) );
	readfile( $path ); exit;
}

/** A full Woo refund is reflected only after WooCommerce reports refunded. */
function tara_release_wc_refunded( $order_id ) {
	if ( ! function_exists( 'wc_get_order' ) ) { return; }
	$order = wc_get_order( $order_id );
	if ( ! $order || ! $order->has_status( 'refunded' ) ) { return; }
	$user_id = (int) $order->get_meta( '_tara_release_user', true );
	$id = (string) $order->get_meta( '_tara_release_request', true );
	if ( $user_id <= 0 || '' === $id || $user_id !== (int) $order->get_customer_id() ) { return; }
	$updated = false;
	$result = tara_release_change_request( $user_id, $id, function ( $row ) use ( $order, &$updated ) {
		if ( 'refunded' === $row['status'] ) { return $row; }
		if ( ! in_array( $row['status'], array( 'awaiting_payment', 'paid', 'preparing', 'published' ), true ) || empty( $row['order_id'] ) || (int) $row['order_id'] !== (int) $order->get_id() || empty( $row['accepted_quote_hash'] ) || ! hash_equals( $row['accepted_quote_hash'], (string) $order->get_meta( '_tara_release_quote', true ) ) ) { return new WP_Error( 'release_refund', 'بازپرداخت قابل تطبیق نیست.' ); }
		$currency = $order->get_currency();
		if ( ! in_array( $currency, array( 'IRT', 'IRR' ), true ) || empty( $row['final_toman'] ) || abs( (float) $order->get_total() - ( (int) $row['final_toman'] * ( 'IRR' === $currency ? 10 : 1 ) ) ) > 0.001 || ( 'awaiting_payment' === $row['status'] && ! $order->get_date_paid() ) ) { return new WP_Error( 'release_refund', 'مبلغ یا پرداخت سفارش قابل تأیید نیست.' ); }
		$row['status'] = 'refunded';
		if ( ! isset( $row['history'] ) || ! is_array( $row['history'] ) ) { $row['history'] = array(); }
		$row['history'][] = array( 'status' => 'refunded', 'time' => time(), 'actor' => 'verified_gateway' );
		$updated = true; return $row;
	} );
	if ( is_wp_error( $result ) && 'release_lock' === $result->get_error_code() && function_exists( 'wp_schedule_single_event' ) ) { wp_schedule_single_event( time() + 60, 'tara_release_retry_refunded', array( (int) $order_id ) ); }
	if ( $updated && ! is_wp_error( $result ) && function_exists( 'tara_acc_notify' ) ) { tara_acc_notify( $user_id, array( 'title' => 'بازپرداخت کامل سفارش انتشار ثبت شد.' ) ); }
}

/** Reject Woo's order-pay route if the amount or accepted quote changed after checkout creation. */
function tara_release_order_needs_payment( $needs_payment, $order ) {
	if ( ! $needs_payment || ! is_object( $order ) || ! is_callable( array( $order, 'get_meta' ) ) ) { return $needs_payment; }
	$id = (string) $order->get_meta( '_tara_release_request', true );
	if ( '' === $id ) { return $needs_payment; }
	if ( ! function_exists( 'ot_get_option' ) || 'on' !== ot_get_option( 'release_payment_on', 'on' ) ) { return false; }
	$user_id = (int) $order->get_meta( '_tara_release_user', true );
	if ( $user_id <= 0 || $user_id !== (int) $order->get_customer_id() ) { return false; }
	foreach ( tara_hub_requests( $user_id ) as $row ) {
		if ( ! isset( $row['id'], $row['order_id'], $row['accepted_quote_hash'] ) || $id !== $row['id'] || (int) $row['order_id'] !== (int) $order->get_id() || ! tara_release_quote_ready( $row ) ) { continue; }
		$currency = $order->get_currency();
		if ( ! in_array( $currency, array( 'IRT', 'IRR' ), true ) || 'international' === $row['service'] ) { return false; }
		$expected = (int) $row['final_toman'] * ( 'IRR' === $currency ? 10 : 1 );
		return (string) $order->get_meta( '_tara_release_quote', true ) === $row['accepted_quote_hash'] && (string) $order->get_meta( '_tara_release_currency', true ) === $currency && (string) $order->get_meta( '_tara_release_expected', true ) === (string) $expected && abs( (float) $order->get_total() - $expected ) < 0.001;
	}
	return false;
}
