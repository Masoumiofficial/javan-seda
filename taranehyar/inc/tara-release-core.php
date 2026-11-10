<?php
/** Foundations for release requests. Deliberately not loaded until the workflow is integrated. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Prices are in toman; international prices are indicative, not a payment quote. */
function tara_release_catalog() {
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
	return $services;
}

function tara_release_statuses() {
	return array( 'new' => 'درخواست جدید', 'review' => 'در انتظار بررسی', 'documents' => 'نیازمند تکمیل مدارک', 'approved' => 'تأییدشده', 'awaiting_payment' => 'در انتظار پرداخت', 'paid' => 'پرداخت‌شده', 'preparing' => 'آماده‌سازی', 'published' => 'منتشرشده', 'rejected' => 'ردشده', 'cancelled' => 'لغوشده' );
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
		if ( function_exists( 'mb_strlen' ) && mb_strlen( $clean, 'UTF-8' ) > $max ) { return new WP_Error( 'release_length', 'یکی از فیلدها بیش از حد طولانی است.' ); }
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
	$catalog = tara_release_catalog();
	if ( ! isset( $row['service'] ) || ! is_string( $row['service'] ) || ! isset( $catalog[ $row['service'] ] ) || empty( $row['id'] ) || empty( $row['quote_revision'] ) ) { return false; }
	if ( 'international' === $row['service'] ) {
		foreach ( array( 'rights_checked', 'geographic_checked', 'distribution_legal_confirmed', 'identity_checked', 'settlement_confirmed' ) as $check ) {
			if ( ! isset( $row[ $check ] ) || true !== $row[ $check ] ) { return false; }
		}
	}
	return isset( $row['accepted_quote_hash'] ) && is_string( $row['accepted_quote_hash'] ) && hash_equals( tara_release_quote_hash( $row ), $row['accepted_quote_hash'] );
}
