<?php
/**
 * حداقلِ شبیه‌ساز وردپرس برای آزمونِ واحدِ منطقِ خودمان (بدون دیتابیس واقعی).
 * فقط چیزهایی را دارد که فایل‌های تحت آزمون صدا می‌زنند؛ هیچ رفتار اضافه‌ای ندارد.
 */

define( 'ABSPATH', '/tmp/wp/' );
define( 'MINUTE_IN_SECONDS', 60 );

$GLOBALS['__terms']    = array();
$GLOBALS['__termmeta'] = array();
$GLOBALS['__posts']    = array();
$GLOBALS['__postmeta'] = array();
$GLOBALS['__options']  = array();
$GLOBALS['__trans']    = array();
$GLOBALS['__caps']     = array();
$GLOBALS['__db_sum']   = 0;
$GLOBALS['__db_queue'] = array();
$GLOBALS['__acf']      = array();
$GLOBALS['__db_ids']   = array();
$GLOBALS['__calls']    = array();
$GLOBALS['__objterms'] = array();
$GLOBALS['__hooks']    = array();
$GLOBALS['__is_cat']   = false;
$GLOBALS['__query_obj'] = null;

class WP_Term {
	public $term_id;
	public $name;
	public $slug;
	public $taxonomy;
	public $count = 0;
	public $term_taxonomy_id = 0;
	public function __construct( $id, $name, $taxonomy, $slug = '', $count = 0 ) {
		$this->term_id           = (int) $id;
		$this->term_taxonomy_id  = (int) $id;
		$this->name              = $name;
		$this->taxonomy          = $taxonomy;
		$this->slug              = $slug ? $slug : sanitize_title( $name );
		$this->count             = (int) $count;
	}
}

class WP_Error {
	public $code;
	public $message;
	public function __construct( $code = '', $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}
}

class WP_Post {
	public $ID;
	public $post_title;
	public $post_name;
	public $post_status;
	public $post_type;
	public $post_date;
	public $post_content = '';
	public function __construct( $id, $title, $status = 'publish', $type = 'post', $date = '2026-01-01 10:00:00' ) {
		$this->ID          = (int) $id;
		$this->post_title  = $title;
		$this->post_name   = sanitize_title( $title );
		$this->post_status = $status;
		$this->post_type   = $type;
		$this->post_date   = $date;
	}
}

/* ---------- هوک‌ها ---------- */
function add_action( $tag, $fn, $prio = 10, $args = 1 ) {
	$GLOBALS['__hooks'][ $tag ][] = $fn;
	return true;
}
function add_filter( $tag, $fn, $prio = 10, $args = 1 ) {
	$GLOBALS['__hooks'][ $tag ][] = $fn;
	return true;
}
function apply_filters( $tag, $value ) {
	if ( empty( $GLOBALS['__hooks'][ $tag ] ) ) {
		return $value;
	}
	$args = func_get_args();
	array_shift( $args );
	foreach ( $GLOBALS['__hooks'][ $tag ] as $fn ) {
		$args[0] = call_user_func_array( $fn, $args );
	}
	return $args[0];
}
function has_filter( $tag, $fn = false ) {
	return ! empty( $GLOBALS['__hooks'][ $tag ] );
}
function do_action( $tag ) {}

/* ---------- قالب ---------- */
function get_template_directory() {
	return '/tmp/theme';
}
function get_template_directory_uri() {
	return 'https://ex/theme';
}
function trailingslashit( $s ) {
	return rtrim( $s, '/\\' ) . '/';
}
function wp_get_theme() {
	return new class() {
		public function get( $k ) {
			return '3.5.2';
		}
	};
}

/* ---------- متن و پاک‌سازی ---------- */
function wp_strip_all_tags( $s ) {
	return trim( strip_tags( (string) $s ) );
}
function esc_html( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_textarea( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_url_raw( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	if ( preg_match( '#^javascript:#i', $url ) ) {
		return '';
	}
	if ( ! preg_match( '#^(https?:)?//#i', $url ) && 0 !== strpos( $url, '/' ) ) {
		return '';
	}
	return $url;
}
function esc_url( $url ) {
	return esc_url_raw( $url );
}
function wp_kses_post( $html ) {
	$html = (string) $html;
	$html = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', $html );
	$html = preg_replace( '#<style\b[^>]*>.*?</style>#is', '', $html );
	return $html;
}
function sanitize_text_field( $s ) {
	return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $s ) ) );
}
function sanitize_textarea_field( $s ) {
	return trim( strip_tags( (string) $s ) );
}
function sanitize_key( $s ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) );
}
function sanitize_title( $s ) {
	$s = strtolower( trim( (string) $s ) );
	$s = preg_replace( '/[^a-z0-9\x{0600}-\x{06FF}]+/u', '-', $s );
	return trim( $s, '-' );
}
function wp_unslash( $v ) {
	return $v;
}
function absint( $v ) {
	return abs( (int) $v );
}
function is_wp_error( $v ) {
	return $v instanceof WP_Error;
}
function number_format_i18n( $n ) {
	return number_format( (int) $n );
}
/* توجه: این‌ها فقط اگر هستهٔ mbstring نباشد تعریف می‌شوند؛ در غیر این صورت
   تابعِ خودِ PHP استفاده می‌شود (وگرنه شبیه‌ساز خودش را صدا می‌زند = بازگشتِ بی‌پایان). */
if ( ! extension_loaded( 'mbstring' ) ) {
	function mb_strlen( $s, $enc = null ) {
		return strlen( (string) $s );
	}
	function mb_substr( $s, $start, $len = null ) {
		return substr( (string) $s, $start, $len );
	}
}

/* ---------- گزینه/ترنزینت ---------- */
function get_option( $k, $d = false ) {
	return array_key_exists( $k, $GLOBALS['__options'] ) ? $GLOBALS['__options'][ $k ] : $d;
}
function update_option( $k, $v, $auto = true ) {
	$GLOBALS['__options'][ $k ] = $v;
	return true;
}
function delete_option( $k ) {
	unset( $GLOBALS['__options'][ $k ] );
	return true;
}
function get_transient( $k ) {
	return array_key_exists( $k, $GLOBALS['__trans'] ) ? $GLOBALS['__trans'][ $k ] : false;
}
function set_transient( $k, $v, $ttl = 0 ) {
	$GLOBALS['__trans'][ $k ] = $v;
	return true;
}
function delete_transient( $k ) {
	unset( $GLOBALS['__trans'][ $k ] );
	return true;
}

/* ---------- ترم ---------- */
function get_term( $id, $tax = '' ) {
	if ( isset( $GLOBALS['__terms'][ (int) $id ] ) ) {
		return $GLOBALS['__terms'][ (int) $id ];
	}
	return null;
}
function get_term_by( $field, $value, $tax = '' ) {
	foreach ( $GLOBALS['__terms'] as $t ) {
		if ( 'slug' === $field && $t->slug === $value ) {
			return $t;
		}
	}
	return null;
}
function get_term_meta( $id, $key, $single = false ) {
	return isset( $GLOBALS['__termmeta'][ (int) $id ][ $key ] ) ? $GLOBALS['__termmeta'][ (int) $id ][ $key ] : '';
}
function update_term_meta( $id, $key, $value ) {
	$GLOBALS['__termmeta'][ (int) $id ][ $key ] = $value;
	return true;
}
function delete_term_meta( $id, $key ) {
	unset( $GLOBALS['__termmeta'][ (int) $id ][ $key ] );
	return true;
}
function term_description( $id, $tax = '' ) {
	return isset( $GLOBALS['__termdesc'][ (int) $id ] ) ? $GLOBALS['__termdesc'][ (int) $id ] : '';
}
function taxonomy_exists( $tax ) {
	return in_array( $tax, array( 'artist', 'singer', 'category', 'post_tag' ), true );
}
function get_taxonomy( $tax ) {
	if ( ! taxonomy_exists( $tax ) ) {
		return false;
	}
	$o               = new stdClass();
	$o->name         = $tax;
	$o->object_type  = array( 'post' );
	$o->cap          = new stdClass();
	$o->cap->edit_terms   = 'manage_categories';
	$o->cap->manage_terms = 'manage_categories';
	return $o;
}
function register_term_meta( $tax, $key, $args ) {
	return true;
}
function wp_get_object_terms( $id, $tax, $args = array() ) {
	return isset( $GLOBALS['__objterms'][ (int) $id ] ) ? $GLOBALS['__objterms'][ (int) $id ] : array();
}
function wp_set_object_terms( $id, $terms, $tax, $append = false ) {
	$GLOBALS['__objterms'][ (int) $id ] = array_map( 'intval', (array) $terms );
	$GLOBALS['__calls'][] = array( 'set_object_terms', (int) $id, $terms, $tax, $append );
	return array();
}
function javan_seda_artist_tax() {
	return 'artist';
}

/* ---------- نوشته ---------- */
function get_post( $id ) {
	return isset( $GLOBALS['__posts'][ (int) $id ] ) ? $GLOBALS['__posts'][ (int) $id ] : null;
}
function get_the_title( $post ) {
	if ( $post instanceof WP_Post ) {
		return $post->post_title;
	}
	$p = get_post( $post );
	return $p ? $p->post_title : '';
}
function get_permalink( $post ) {
	$id = $post instanceof WP_Post ? $post->ID : (int) $post;
	return 'https://ex/?p=' . $id;
}
function get_the_date( $fmt, $post = null ) {
	return '2026-01-01';
}
function get_post_meta( $id, $key, $single = false ) {
	return isset( $GLOBALS['__postmeta'][ (int) $id ][ $key ] ) ? $GLOBALS['__postmeta'][ (int) $id ][ $key ] : '';
}
function update_post_meta( $id, $key, $value ) {
	$GLOBALS['__postmeta'][ (int) $id ][ $key ] = $value;
	return true;
}
function delete_post_meta( $id, $key ) {
	unset( $GLOBALS['__postmeta'][ (int) $id ][ $key ] );
	return true;
}
function wp_get_attachment_image_url( $id, $size = 'thumbnail' ) {
	return isset( $GLOBALS['__attachments'][ (int) $id ] ) ? 'https://ex/' . $id . '-' . $size . '.jpg' : false;
}
function wp_get_attachment_url( $id ) {
	return isset( $GLOBALS['__attachments'][ (int) $id ] ) ? 'https://ex/' . $id . '-full.jpg' : false;
}

/* ---------- دسترسی/امنیت ---------- */
function current_user_can( $cap, $arg = null ) {
	return ! empty( $GLOBALS['__caps'][ $cap ] );
}
function wp_verify_nonce( $nonce, $action ) {
	return ( 'good' === $nonce && 'javan_seda_save_term' === $action ) || ( 'good-post' === $nonce && 'javan_seda_post_artist' === $action );
}
function check_ajax_referer( $action, $name ) {
	return true;
}
function wp_nonce_field( $action, $name ) {
	echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="good">';
}

/* ---------- وضعیت صفحه ---------- */
function is_category() {
	return ! empty( $GLOBALS['__is_cat'] );
}
function get_queried_object() {
	return $GLOBALS['__query_obj'];
}
function is_admin() {
	return false;
}

/* ---------- $wpdb ---------- */
class JV_Fake_WPDB {
	public $postmeta     = 'wp_postmeta';
	public $posts        = 'wp_posts';
	public $term_relationships = 'wp_term_relationships';
	public $term_taxonomy      = 'wp_term_taxonomy';
	public $last_sql     = '';
	public function prepare( $sql, ...$args ) {
		$this->last_sql = $sql;
		$i = 0;
		return preg_replace_callback(
			'/%[sd]/',
			function ( $m ) use ( &$i, $args ) {
				$v = isset( $args[ $i ] ) ? $args[ $i ] : '';
				$i++;
				return ( '%d' === $m[0] ) ? (string) (int) $v : "'" . addslashes( (string) $v ) . "'";
			},
			$sql
		);
	}
	public function get_var( $sql ) {
		if ( ! empty( $GLOBALS['__db_queue'] ) ) {
			return array_shift( $GLOBALS['__db_queue'] );
		}
		return $GLOBALS['__db_sum'];
	}
	public function get_col( $sql ) {
		return $GLOBALS['__db_ids'];
	}
	public function esc_like( $s ) {
		return addcslashes( (string) $s, '_%\\' );
	}
}
$GLOBALS['wpdb'] = new JV_Fake_WPDB();

/* ---------- ACF (شبیه‌سازی) ---------- */
function get_field( $key, $term = null, $format = true ) {
	return array_key_exists( $key, $GLOBALS['__acf'] ) ? $GLOBALS['__acf'][ $key ] : false;
}

/* ---------- خروجی JSON ---------- */
function wp_send_json_success( $data = null ) {
	$GLOBALS['__json'] = array( 'success' => true, 'data' => $data );
	throw new RuntimeException( 'json_success' );
}
function wp_send_json_error( $data = null, $code = 200 ) {
	$GLOBALS['__json'] = array( 'success' => false, 'data' => $data, 'code' => $code );
	throw new RuntimeException( 'json_error' );
}
