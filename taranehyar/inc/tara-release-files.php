<?php
/** Private file handling for release requests. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** A resolved directory nested within a publicly served directory is never acceptable. */
function tara_release_path_within( $path, $root ) {
	$path = rtrim( str_replace( '\\', '/', $path ), '/' );
	$root = rtrim( str_replace( '\\', '/', $root ), '/' );
	if ( '' === $root ) { return true; }
	return $path === $root || 0 === strpos( $path, $root . '/' );
}

/**
 * The operator must configure this directory outside the site/document roots and
 * confirm that no server alias exposes it. Failure is closed; no upload fallback.
 */
function tara_release_private_dir() {
	if ( ! defined( 'TARA_RELEASE_PRIVATE_DIR' ) || ! is_string( TARA_RELEASE_PRIVATE_DIR ) || '' === TARA_RELEASE_PRIVATE_DIR ) { return new WP_Error( 'release_storage', 'فضای خصوصی فایل تنظیم نشده است.' ); }
	$dir = realpath( TARA_RELEASE_PRIVATE_DIR );
	if ( false === $dir || ! is_dir( $dir ) || ! is_writable( $dir ) || is_link( TARA_RELEASE_PRIVATE_DIR ) ) { return new WP_Error( 'release_storage', 'فضای خصوصی فایل در دسترس نیست.' ); }
	$roots = array( ABSPATH );
	if ( defined( 'WP_CONTENT_DIR' ) ) { $roots[] = WP_CONTENT_DIR; }
	if ( isset( $_SERVER['DOCUMENT_ROOT'] ) && is_string( $_SERVER['DOCUMENT_ROOT'] ) && '' !== $_SERVER['DOCUMENT_ROOT'] ) { $roots[] = $_SERVER['DOCUMENT_ROOT']; }
	foreach ( $roots as $root ) {
		$resolved = realpath( $root );
		if ( false === $resolved || tara_release_path_within( $dir, $resolved ) ) { return new WP_Error( 'release_storage', 'فضای فایل باید خارج از مسیرهای عمومی سایت باشد.' ); }
	}
	return $dir;
}

/** Fixed slot identifiers are safe for lookup and never become file paths. */
function tara_release_file_key_valid( $key ) {
	return is_string( $key ) && ( in_array( $key, array( 'work', 'cover', 'document' ), true ) || 1 === preg_match( '/^work_([2-9]|10)$/D', $key ) );
}

/** Flatten a PHP multi-file field without trusting its array shape or declared size. */
function tara_release_file_inputs( $files ) {
	if ( ! is_array( $files ) ) { return new WP_Error( 'release_file_input', 'فایل‌های ارسالی نامعتبرند.' ); }
	$result = array();
	$max = function_exists( 'ot_get_option' ) ? ot_get_option( 'release_max_tracks', '10' ) : '10';
	$max = is_scalar( $max ) && preg_match( '/^(?:[1-9]|10)$/D', (string) $max ) ? (int) $max : 10;
	if ( isset( $files['work_file']['error'] ) && UPLOAD_ERR_NO_FILE !== $files['work_file']['error'] ) { $result['work'] = $files['work_file']; }
	if ( isset( $files['work_files'] ) ) {
		$group = $files['work_files'];
		if ( ! is_array( $group ) || ! isset( $group['name'], $group['tmp_name'], $group['error'], $group['size'] ) || ! is_array( $group['name'] ) || ! is_array( $group['tmp_name'] ) || ! is_array( $group['error'] ) || ! is_array( $group['size'] ) || count( $group['name'] ) > $max || count( $group['name'] ) !== count( $group['tmp_name'] ) || count( $group['name'] ) !== count( $group['error'] ) || count( $group['name'] ) !== count( $group['size'] ) ) { return new WP_Error( 'release_file_input', 'تعداد یا ساختار فایل‌های اثر مجاز نیست.' ); }
		foreach ( $group['name'] as $i => $name ) {
			if ( ! isset( $group['tmp_name'][ $i ], $group['error'][ $i ], $group['size'][ $i ] ) || ! is_string( $name ) || ! is_string( $group['tmp_name'][ $i ] ) || ! is_int( $group['error'][ $i ] ) || ! is_numeric( $group['size'][ $i ] ) ) { return new WP_Error( 'release_file_input', 'اطلاعات فایل نامعتبر است.' ); }
			if ( UPLOAD_ERR_NO_FILE === $group['error'][ $i ] ) { continue; }
			$n = count( $result ) + 1;
			if ( $n > $max ) { return new WP_Error( 'release_file_input', 'تعداد فایل‌های اثر بیش از حد مجاز است.' ); }
			$result[ 1 === $n ? 'work' : 'work_' . $n ] = array( 'name' => $name, 'tmp_name' => $group['tmp_name'][ $i ], 'error' => $group['error'][ $i ], 'size' => $group['size'][ $i ] );
		}
	}
	foreach ( array( 'cover_file' => 'cover', 'document_file' => 'document' ) as $field => $slot ) {
		if ( isset( $files[ $field ]['error'] ) && UPLOAD_ERR_NO_FILE !== $files[ $field ]['error'] ) { $result[ $slot ] = $files[ $field ]; }
	}
	return $result;
}

/** Metadata checks must also be followed by a server-side MIME check. */
function tara_release_file_rule( $name, $size, $kind ) {
	$rules = array(
		'audio' => array( 'max' => 104857600, 'extensions' => array( 'mp3', 'wav' ), 'mimes' => array( 'audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/wave', 'audio/vnd.wave' ) ),
		'cover' => array( 'max' => 10485760, 'extensions' => array( 'jpg', 'jpeg', 'png' ), 'mimes' => array( 'image/jpeg', 'image/png' ) ),
		'document' => array( 'max' => 10485760, 'extensions' => array( 'pdf', 'jpg', 'jpeg', 'png' ), 'mimes' => array( 'application/pdf', 'image/jpeg', 'image/png' ) ),
		'video' => array( 'max' => 524288000, 'extensions' => array( 'mp4' ), 'mimes' => array( 'video/mp4' ) ),
	);
	if ( ! isset( $rules[ $kind ] ) || ! is_string( $name ) || ! is_numeric( $size ) || (int) $size < 1 ) { return false; }
	$cap_mb = (int) ( $rules[ $kind ]['max'] / 1048576 );
	$configured = function_exists( 'ot_get_option' ) ? ot_get_option( 'release_max_' . $kind . '_mb', (string) $cap_mb ) : (string) $cap_mb;
	if ( is_scalar( $configured ) && preg_match( '/^[1-9][0-9]{0,3}$/D', (string) $configured ) ) { $rules[ $kind ]['max'] = min( $cap_mb, (int) $configured ) * 1048576; }
	if ( (int) $size > $rules[ $kind ]['max'] ) { return false; }
	$ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, $rules[ $kind ]['extensions'], true ) ) { return false; }
	return $rules[ $kind ];
}

/**
 * Save one actual PHP upload under an opaque random name. Never use its original
 * name as a path or expose the resulting path to users. Caller must delete the
 * file if its subsequent database commit fails and authorize every later read.
 */
function tara_release_store_upload( $upload, $kind ) {
	$dir = tara_release_private_dir();
	if ( is_wp_error( $dir ) ) { return $dir; }
	if ( ! is_array( $upload ) || ! isset( $upload['error'], $upload['tmp_name'], $upload['name'], $upload['size'] ) || UPLOAD_ERR_OK !== $upload['error'] || ! is_string( $upload['tmp_name'] ) || ! is_uploaded_file( $upload['tmp_name'] ) ) { return new WP_Error( 'release_upload', 'آپلود معتبر نیست یا ناقص است.' ); }
	$size = filesize( $upload['tmp_name'] );
	$rule = tara_release_file_rule( $upload['name'], $size, $kind );
	if ( false === $rule || ! function_exists( 'finfo_open' ) ) { return new WP_Error( 'release_file_type', 'نوع یا اندازهٔ فایل مجاز نیست.' ); }
	$finfo = finfo_open( FILEINFO_MIME_TYPE );
	if ( false === $finfo ) { return new WP_Error( 'release_file_type', 'تشخیص نوع فایل در دسترس نیست.' ); }
	$mime = finfo_file( $finfo, $upload['tmp_name'] );
	finfo_close( $finfo );
	if ( ! in_array( $mime, $rule['mimes'], true ) ) { return new WP_Error( 'release_file_type', 'محتوای فایل با نوع مجاز مطابقت ندارد.' ); }
	$ext = strtolower( pathinfo( $upload['name'], PATHINFO_EXTENSION ) );
	$expected = array( 'jpg' => array( 'image/jpeg' ), 'jpeg' => array( 'image/jpeg' ), 'png' => array( 'image/png' ), 'pdf' => array( 'application/pdf' ), 'mp3' => array( 'audio/mpeg' ), 'wav' => array( 'audio/wav', 'audio/x-wav', 'audio/wave', 'audio/vnd.wave' ), 'mp4' => array( 'video/mp4' ) );
	if ( ! isset( $expected[ $ext ] ) || ! in_array( $mime, $expected[ $ext ], true ) ) { return new WP_Error( 'release_file_type', 'پسوند با محتوای فایل مطابقت ندارد.' ); }
	try { $token = bin2hex( random_bytes( 24 ) ); } catch ( Exception $e ) { return new WP_Error( 'release_storage', 'ساخت نام امن فایل ناموفق بود.' ); }
	$destination = $dir . DIRECTORY_SEPARATOR . $token . '.bin';
	if ( file_exists( $destination ) ) { return new WP_Error( 'release_storage', 'ذخیره فایل انجام نشد.' ); }
	$old_umask = umask( 0077 );
	$moved = move_uploaded_file( $upload['tmp_name'], $destination );
	umask( $old_umask );
	if ( ! $moved ) { return new WP_Error( 'release_storage', 'ذخیره فایل انجام نشد.' ); }
	if ( ! chmod( $destination, 0600 ) ) { unlink( $destination ); return new WP_Error( 'release_storage', 'محافظت فایل انجام نشد.' ); }
	return array( 'token' => $token, 'mime' => $mime, 'size' => (int) $size, 'extension' => $ext );
}
