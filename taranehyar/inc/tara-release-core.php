<?php
/** Foundations for release requests. Deliberately not loaded until the workflow is integrated. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Prices are in toman; international prices are indicative, not a payment quote. */
function tara_release_catalog( $include_disabled = false ) {
	$services = array(
		'song' => array( 'label' => 'آهنگ', 'amount' => 1500000 ),
		'video' => array( 'label' => 'موزیک‌ویدیو', 'amount' => 2500000 ),
		'album' => array( 'label' => 'آلبوم', 'amount' => 4500000 ),
		'remix' => array( 'label' => 'ریمیکس', 'amount' => 1800000 ),
		'podcast' => array( 'label' => 'پادکست', 'amount' => 1000000 ),
		'international' => array( 'label' => 'بررسی انتشار بین‌المللی', 'amount' => 0 ),
	);
	foreach ( $services as $key => &$service ) {
		if ( 'international' === $key ) { continue; }
		$price = function_exists( 'ot_get_option' ) ? ot_get_option( 'release_price_' . $key, $service['amount'] ) : $service['amount'];
		if ( is_scalar( $price ) && preg_match( '/^[0-9]{1,10}$/D', (string) $price ) && (float) $price <= 2000000000 ) { $service['amount'] = (int) $price; }
	}
	unset( $service );
	foreach ( array_keys( $services ) as $key ) {
		if ( ! $include_disabled && function_exists( 'ot_get_option' ) && 'off' === (string) ot_get_option( 'release_service_' . $key, 'on' ) ) { unset( $services[ $key ] ); }
	}
	return $services;
}

function tara_release_statuses() {
	return array( 'new' => 'درخواست جدید', 'review' => 'در انتظار بررسی', 'documents' => 'نیازمند تکمیل مدارک', 'approved' => 'تأییدشده', 'awaiting_payment' => 'در انتظار پرداخت', 'paid' => 'پرداخت‌شده', 'preparing' => 'آماده‌سازی', 'published' => 'منتشرشده', 'refunded' => 'بازپرداخت‌شده', 'rejected' => 'ردشده', 'cancelled' => 'لغوشده' );
}

/** Allowed workflow transitions; a paid state must only come from a verified gateway event. */
function tara_release_transition_allowed( $from, $to, $source = 'admin' ) {
	$transitions = array(
		'new' => array( 'review', 'documents', 'rejected', 'cancelled' ),
		'review' => array( 'documents', 'approved', 'rejected', 'cancelled' ),
		'documents' => array( 'review', 'rejected', 'cancelled' ),
		'approved' => array( 'review', 'awaiting_payment', 'rejected', 'cancelled' ),
		'awaiting_payment' => array( 'review', 'cancelled', 'paid' ),
		'paid' => array( 'preparing' ),
		'preparing' => array( 'published' ),
		'published' => array(), 'rejected' => array(), 'cancelled' => array(),
	);
	if ( ! isset( $transitions[ $from ] ) || ! in_array( $to, $transitions[ $from ], true ) ) { return false; }
	if ( 'paid' === $to ) { return 'verified_gateway' === $source; }
	return 'admin' === $source;
}

/** Caller must authenticate, verify nonce and unslash before invoking. */
function tara_release_validate( $input, $has_file = false ) {
	if ( ! is_array( $input ) ) { return new WP_Error( 'release_input', 'اطلاعات درخواست نامعتبر است.' ); }
	$fields = array( 'full_name'=>120, 'artist_name'=>120, 'mobile'=>30, 'email'=>254, 'country'=>100, 'instagram'=>500, 'website'=>500, 'title'=>180, 'performer'=>120, 'composer'=>120, 'lyricist'=>120, 'arranger'=>120, 'description'=>4000, 'release_date'=>10, 'file_url'=>1000, 'cover_url'=>1000, 'legal'=>4000 );
	$data = array();
	foreach ( $fields as $field => $max ) {
		$raw = isset( $input[ $field ] ) ? $input[ $field ] : '';
		if ( ! is_string( $raw ) || strlen( $raw ) > $max * 4 ) { return new WP_Error( 'release_field', 'یکی از فیلدها نامعتبر یا بیش از حد طولانی است.' ); }
		$clean = in_array( $field, array( 'description', 'legal' ), true ) ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $clean, 'UTF-8' ) : preg_match_all( '/./us', $clean, $unused );
		if ( false === $length || $length > $max ) { return new WP_Error( 'release_length', 'یکی از فیلدها بیش از حد طولانی است.' ); }
		$data[ $field ] = $clean;
	}
	$service = isset( $input['service'] ) && is_string( $input['service'] ) ? sanitize_key( $input['service'] ) : '';
	$catalog = tara_release_catalog();
	if ( ! isset( $catalog[ $service ] ) ) { return new WP_Error( 'release_service', 'نوع خدمت نامعتبر است.' ); }
	foreach ( array( 'full_name', 'artist_name', 'mobile', 'email', 'country', 'title', 'performer' ) as $field ) {
		if ( '' === trim( $data[ $field ] ) ) { return new WP_Error( 'release_required', 'فیلدهای ضروری را کامل کنید.' ); }
	}
	if ( ! is_email( $data['email'] ) ) { return new WP_Error( 'release_email', 'ایمیل معتبر وارد کنید.' ); }
	$data['mobile'] = strtr( $data['mobile'], array( '۰'=>'0', '۱'=>'1', '۲'=>'2', '۳'=>'3', '۴'=>'4', '۵'=>'5', '۶'=>'6', '۷'=>'7', '۸'=>'8', '۹'=>'9', '٠'=>'0', '١'=>'1', '٢'=>'2', '٣'=>'3', '٤'=>'4', '٥'=>'5', '٦'=>'6', '٧'=>'7', '٨'=>'8', '٩'=>'9' ) );
	if ( ! preg_match( '/^\+?[0-9 ()-]{7,25}$/D', $data['mobile'] ) ) { return new WP_Error( 'release_mobile', 'شماره تماس معتبر وارد کنید.' ); }
	foreach ( array( 'instagram', 'website', 'file_url', 'cover_url' ) as $field ) {
		if ( '' === $data[ $field ] ) { continue; }
		$parts = wp_parse_url( $data[ $field ] );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), array( 'https', 'http' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) { return new WP_Error( 'release_url', 'پیوند معتبر HTTP یا HTTPS وارد کنید.' ); }
		$data[ $field ] = esc_url_raw( $data[ $field ], array( 'http', 'https' ) );
		if ( '' === $data[ $field ] ) { return new WP_Error( 'release_url', 'پیوند نامعتبر است.' ); }
	}
	if ( '' === $data['file_url'] && ! $has_file ) { return new WP_Error( 'release_file', 'لینک یا فایل اثر لازم است.' ); }
	if ( '' !== $data['release_date'] ) {
		$date = DateTime::createFromFormat( '!Y-m-d', $data['release_date'] );
		if ( ! $date || $date->format( 'Y-m-d' ) !== $data['release_date'] ) { return new WP_Error( 'release_date', 'تاریخ معتبر وارد کنید.' ); }
	}
	if ( ! isset( $input['rights'], $input['terms'] ) || '1' !== $input['rights'] || '1' !== $input['terms'] ) { return new WP_Error( 'release_consent', 'تأیید مالکیت و شرایط الزامی است.' ); }
	$data['service'] = $service;
	$data['rights'] = true;
	$data['terms'] = true;
	return $data;
}

/** Change any material quote field or revision and existing acceptance becomes invalid. */
function tara_release_quote_hash( $row ) {
	$fields = array( 'id', 'service', 'final_toman', 'extra_costs', 'refund_terms', 'quote_revision' );
	$quote = array();
	foreach ( $fields as $field ) { $quote[ $field ] = isset( $row[ $field ] ) && is_scalar( $row[ $field ] ) ? (string) $row[ $field ] : ''; }
	return hash( 'sha256', json_encode( $quote, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

/** This predicate is necessary but not sufficient for creating an order. */
function tara_release_quote_ready( $row ) {
	if ( ! is_array( $row ) || ! isset( $row['type'], $row['status'], $row['final_toman'], $row['refund_terms'], $row['extra_costs'] ) || 'release' !== $row['type'] || 'awaiting_payment' !== $row['status'] ) { return false; }
	if ( ! is_scalar( $row['final_toman'] ) || ! preg_match( '/^[1-9][0-9]{0,9}$/D', (string) $row['final_toman'] ) || (float) $row['final_toman'] > 2000000000 ) { return false; }
	if ( ! is_string( $row['refund_terms'] ) || '' === trim( $row['refund_terms'] ) || ! is_string( $row['extra_costs'] ) || '' === trim( $row['extra_costs'] ) ) { return false; }
	$catalog = tara_release_catalog( true );
	if ( ! isset( $row['service'] ) || ! is_string( $row['service'] ) || ! isset( $catalog[ $row['service'] ] ) || empty( $row['id'] ) || empty( $row['quote_revision'] ) ) { return false; }
	if ( 'international' === $row['service'] ) {
		foreach ( array( 'rights_checked', 'geographic_checked', 'distribution_legal_confirmed', 'identity_checked', 'settlement_confirmed' ) as $check ) {
			if ( ! isset( $row[ $check ] ) || true !== $row[ $check ] ) { return false; }
		}
	}
	return isset( $row['accepted_quote_hash'] ) && is_string( $row['accepted_quote_hash'] ) && hash_equals( tara_release_quote_hash( $row ), $row['accepted_quote_hash'] );
}

/** Construct a request for insertion into the existing artist request array.
 * Caller provides an unpredictable per-submission token and handles locking and persistence.
 */
function tara_release_build_request( $input, $token, $now, $has_file = false ) {
	if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{32,64}$/D', $token ) || ! is_int( $now ) || $now <= 0 ) { return new WP_Error( 'release_token', 'شناسهٔ درخواست نامعتبر است.' ); }
	$data = tara_release_validate( $input, $has_file );
	if ( is_wp_error( $data ) ) { return $data; }
	$catalog = tara_release_catalog();
	return array(
		'id' => 'release_' . $token, 'type' => 'release', 'service' => $data['service'],
		'title' => $data['title'], 'body' => $data['description'], 'details' => $data,
		'price' => $catalog[ $data['service'] ]['amount'], 'status' => 'new', 'created' => $now,
		'note' => '', 'history' => array( array( 'status' => 'new', 'time' => $now, 'actor' => 'customer' ) ),
	);
}

/** Find an existing submission without confusing it with a legacy artist request. */
function tara_release_find_submission( $rows, $token ) {
	if ( ! is_array( $rows ) || ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{32,64}$/D', $token ) ) { return null; }
	$id = 'release_' . $token;
	foreach ( $rows as $row ) {
		if ( is_array( $row ) && isset( $row['type'], $row['id'] ) && 'release' === $row['type'] && $id === $row['id'] ) { return $row; }
	}
	return null;
}

/** Set a new quote; changes invalidate any prior acceptance. No order may exist. */
function tara_release_set_quote( $row, $amount, $extras, $refund_terms, $revision ) {
	if ( ! is_array( $row ) || ! isset( $row['type'], $row['status'] ) || 'release' !== $row['type'] || ! in_array( $row['status'], array( 'approved', 'awaiting_payment' ), true ) || ! empty( $row['order_id'] ) ) { return new WP_Error( 'release_quote_state', 'تغییر پیش‌فاکتور در این وضعیت مجاز نیست.' ); }
	if ( ! is_scalar( $amount ) || ! preg_match( '/^[1-9][0-9]{0,9}$/D', (string) $amount ) || (float) $amount > 2000000000 || ! is_string( $extras ) || ! is_string( $refund_terms ) || ! is_string( $revision ) || ! preg_match( '/^[a-f0-9]{32,64}$/D', $revision ) ) { return new WP_Error( 'release_quote', 'اطلاعات پیش‌فاکتور نامعتبر است.' ); }
	$extras = sanitize_textarea_field( $extras );
	$refund_terms = sanitize_textarea_field( $refund_terms );
	if ( '' === trim( $extras ) || '' === trim( $refund_terms ) || strlen( $extras ) > 16000 || strlen( $refund_terms ) > 16000 ) { return new WP_Error( 'release_quote_terms', 'هزینه‌ها و شرایط لغو و بازپرداخت باید مشخص شوند.' ); }
	$row['final_toman'] = (int) $amount;
	$row['extra_costs'] = $extras;
	$row['refund_terms'] = $refund_terms;
	$row['quote_revision'] = $revision;
	unset( $row['accepted_quote_hash'], $row['accepted_at'] );
	return $row;
}

/** Called only after the authenticated owner sees and explicitly accepts this exact quote. */
function tara_release_accept_quote( $row, $submitted_hash, $now ) {
	if ( ! is_array( $row ) || ! isset( $row['type'], $row['status'], $row['quote_revision'], $row['final_toman'], $row['extra_costs'], $row['refund_terms'] ) || 'release' !== $row['type'] || 'awaiting_payment' !== $row['status'] || ! is_string( $submitted_hash ) || ! preg_match( '/^[a-f0-9]{64}$/D', $submitted_hash ) || ! hash_equals( tara_release_quote_hash( $row ), $submitted_hash ) || ! is_int( $now ) || $now <= 0 || ! empty( $row['order_id'] ) ) { return new WP_Error( 'release_accept', 'پیش‌فاکتور تغییر کرده یا قابل پذیرش نیست.' ); }
	$row['accepted_quote_hash'] = $submitted_hash;
	$row['accepted_at'] = $now;
	return $row;
}

/** State change is pure; persistence and authorization remain the caller's responsibility. */
function tara_release_transition( $row, $to, $actor, $now ) {
	if ( ! is_array( $row ) || ! isset( $row['type'], $row['status'] ) || 'release' !== $row['type'] || ! is_string( $to ) || ! is_string( $actor ) || ! is_int( $now ) || $now <= 0 || ! tara_release_transition_allowed( $row['status'], $to, $actor ) ) { return new WP_Error( 'release_transition', 'تغییر وضعیت مجاز نیست.' ); }
	if ( ! empty( $row['order_id'] ) && 'awaiting_payment' === $row['status'] && 'paid' !== $to ) { return new WP_Error( 'release_order', 'ابتدا وضعیت سفارش ووکامرس را بررسی کنید.' ); }
	if ( 'awaiting_payment' === $to && ( empty( $row['final_toman'] ) || empty( $row['quote_revision'] ) || empty( $row['extra_costs'] ) || empty( $row['refund_terms'] ) ) ) { return new WP_Error( 'release_quote', 'ابتدا مبلغ و شرایط اختصاصی سفارش را مشخص کنید.' ); }
	if ( 'published' === $to && empty( $row['publication_url'] ) ) { return new WP_Error( 'release_publication', 'لینک انتشار لازم است.' ); }
	if ( ! isset( $row['history'] ) || ! is_array( $row['history'] ) ) { $row['history'] = array(); }
	$row['status'] = $to;
	$row['history'][] = array( 'status' => $to, 'time' => $now, 'actor' => $actor );
	return $row;
}

/** Verify the server-side Woo order against the exact accepted request and amount.
 * No browser return, arbitrary order status, or order meta alone is proof of payment.
 */
function tara_release_order_matches_paid( $row, $order, $user_id ) {
	if ( ! is_array( $row ) || ! is_object( $order ) || ! tara_release_quote_ready( $row ) || ! isset( $row['order_id'] ) || (int) $row['order_id'] <= 0 || (int) $user_id <= 0 ) { return false; }
	foreach ( array( 'get_id', 'get_customer_id', 'get_meta', 'get_currency', 'get_total', 'is_paid', 'has_status' ) as $method ) {
		if ( ! is_callable( array( $order, $method ) ) ) { return false; }
	}
	if ( ! $order->is_paid() || $order->has_status( array( 'cancelled', 'failed', 'refunded' ) ) || (int) $order->get_id() !== (int) $row['order_id'] || (int) $order->get_customer_id() !== (int) $user_id ) { return false; }
	if ( (string) $order->get_meta( '_tara_release_request', true ) !== (string) $row['id'] || (int) $order->get_meta( '_tara_release_user', true ) !== (int) $user_id || (string) $order->get_meta( '_tara_release_quote', true ) !== $row['accepted_quote_hash'] ) { return false; }
	$currency = $order->get_currency();
	if ( ! in_array( $currency, array( 'IRT', 'IRR' ), true ) ) { return false; }
	$expected = (int) $row['final_toman'] * ( 'IRR' === $currency ? 10 : 1 );
	if ( (string) $order->get_meta( '_tara_release_currency', true ) !== $currency || (string) $order->get_meta( '_tara_release_expected', true ) !== (string) $expected ) { return false; }
	$total = $order->get_total();
	return is_numeric( $total ) && abs( (float) $total - (float) $expected ) < 0.001;
}
