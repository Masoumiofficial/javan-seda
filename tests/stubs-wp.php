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
$GLOBALS['__is_home']  = false;
$GLOBALS['__is_front'] = false;
$GLOBALS['__is_feed']  = false;
$GLOBALS['__is_admin'] = false;
$GLOBALS['__ot']       = array();
$GLOBALS['__query_vars']        = array();
$GLOBALS['__comments_total']    = 0;
$GLOBALS['__comment_total_pages'] = 1;
$GLOBALS['__blog']     = array( 'name' => 'آهنگ‌بان', 'description' => 'توضیح کوتاه سایت' );
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

class WP_Widget {
	public $id_base = '';
	public $name      = '';
	public $widget_options = array();
	public $control_options = array();
	public function __construct( $id_base = '', $name = '', $widget_options = array(), $control_options = array() ) {
		$this->id_base         = $id_base;
		$this->name            = $name;
		$this->widget_options  = (array) $widget_options;
		$this->control_options = (array) $control_options;
	}
	public function get_field_id( $field ) {
		return 'widget-' . $this->id_base . '-' . $field;
	}
	public function get_field_name( $field ) {
		return 'widget-' . $this->id_base . '[' . $field . ']';
	}
	public function widget( $args, $instance ) {}
	public function form( $instance ) {}
	public function update( $new, $old ) {
		return $new;
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

/* ---------- قالب/دارایی ---------- */
$GLOBALS['__nav_menus']   = array();
$GLOBALS['__supports']    = array();
$GLOBALS['__img_sizes']   = array();
$GLOBALS['__styles']      = array();
$GLOBALS['__scripts']     = array();
$GLOBALS['__queried_id']  = 0;

function register_nav_menus( $locations ) {
	$GLOBALS['__nav_menus'] = array_merge( $GLOBALS['__nav_menus'], (array) $locations );
	return true;
}
function add_theme_support( $feature, $args = null ) {
	$GLOBALS['__supports'][ $feature ] = $args;
	return true;
}
function current_theme_supports( $feature ) {
	return array_key_exists( $feature, $GLOBALS['__supports'] );
}
function add_image_size( $name, $w = 0, $h = 0, $crop = false ) {
	$GLOBALS['__img_sizes'][ $name ] = array( $w, $h, $crop );
	return true;
}
function load_theme_textdomain( $domain, $path = '' ) {
	return true;
}
function get_stylesheet_uri() {
	return 'https://ex/wp-content/themes/taranehyar/style.css';
}
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	$GLOBALS['__styles'][ $handle ] = array( $src, $deps, $ver );
	return true;
}
function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $footer = false ) {
	$GLOBALS['__scripts'][ $handle ] = array( $src, $deps, $ver, $footer );
	return true;
}
function wp_localize_script( $handle, $object_name, $l10n ) {
	$GLOBALS['__localized'][ $handle ][ $object_name ] = $l10n;
	return true;
}
function current_action() {
	return isset( $GLOBALS['__current_action'] ) ? (string) $GLOBALS['__current_action'] : '';
}
function wp_register_style( $handle, $src = '', $deps = array(), $ver = false ) {
	$GLOBALS['__styles'][ $handle ] = array( $src, $deps, $ver );
	return true;
}
function wp_register_script( $handle, $src = '', $deps = array(), $ver = false, $footer = false ) {
	$GLOBALS['__scripts'][ $handle ] = array( $src, $deps, $ver, $footer );
	return true;
}
function wp_script_is( $handle, $list = 'enqueued' ) {
	return isset( $GLOBALS['__scripts'][ $handle ] );
}
function wp_style_is( $handle, $list = 'enqueued' ) {
	return isset( $GLOBALS['__styles'][ $handle ] );
}
function get_pagenum_link( $pagenum = 1 ) {
	return 'https://ex/page/' . (int) $pagenum . '/';
}
function get_queried_object_id() {
	return (int) $GLOBALS['__queried_id'];
}
function is_preview() {
	return ! empty( $GLOBALS['__is_preview'] );
}
function is_robots() {
	return false;
}
function set_query_var( $k, $v ) {
	$GLOBALS['__query_vars'][ $k ] = $v;
}
function get_search_query() {
	return 'تست';
}
function comments_open() {
	return true;
}
function post_type_supports( $type, $feature ) {
	return true;
}
function get_post_type() {
	return 'post';
}
function is_page() {
	return false;
}
function have_comments() {
	return ! empty( $GLOBALS['__have_comments'] );
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
function __return_false() {
	return false;
}
function __return_true() {
	return true;
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
			return '3.6.0';
		}
	};
}

/* ---------- متن و پاک‌سازی ---------- */
function wp_strip_all_tags( $s ) {
	return trim( strip_tags( (string) $s ) );
}
function esc_html_e( $text, $domain = 'default' ) {
	echo esc_html( $text );
}
function esc_html__( $text, $domain = 'default' ) {
	return esc_html( $text );
}
function esc_attr_e( $text, $domain = 'default' ) {
	echo esc_attr( $text );
}
function esc_attr__( $text, $domain = 'default' ) {
	return esc_attr( $text );
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
	/* رفتارِ نزدیک به وردپرس: کاراکترهایی که می‌توانند از attribute خارج شوند کد می‌شوند */
	$url = esc_url_raw( $url );
	return str_replace( array( '"', "'", '<', '>' ), array( '%22', '%27', '%3C', '%3E' ), $url );
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
function flush_rewrite_rules( $hard = true ) {
	$GLOBALS['__flushed'] = isset( $GLOBALS['__flushed'] ) ? $GLOBALS['__flushed'] + 1 : 1;
	return true;
}
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
		if ( 'name' === $field && $t->name === $value ) {
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
function register_taxonomy( $tax, $objects, $args = array() ) {
	$object                = (object) array(
		'name'        => $tax,
		'object_type' => (array) $objects,
		'args'        => (array) $args,
	);
	$GLOBALS['__taxonomies'][ $tax ] = $object;
	return $object;
}
function taxonomy_exists( $tax ) {
	return in_array( $tax, array( 'artist', 'singer', 'category', 'post_tag' ), true ) || isset( $GLOBALS['__taxonomies'][ $tax ] );
}
function get_the_terms( $post_id, $tax ) {
	return isset( $GLOBALS['__postterms'][ (int) $post_id ][ $tax ] ) ? $GLOBALS['__postterms'][ (int) $post_id ][ $tax ] : false;
}
function get_term_link( $term, $tax = '' ) {
	$t = is_object( $term ) ? $term : null;
	if ( ! $t ) {
		return new WP_Error( 'invalid_term' );
	}
	return 'https://ex/' . rawurlencode( $t->taxonomy ) . '/' . $t->slug . '/';
}
function get_edit_term_link( $id, $tax = '', $object = '' ) {
	return 'https://ex/wp-admin/term.php?taxonomy=' . $tax . '&tag_ID=' . (int) $id;
}
function wp_count_terms( $args = array() ) {
	$tax = is_array( $args ) && isset( $args['taxonomy'] ) ? $args['taxonomy'] : '';
	return isset( $GLOBALS['__termcount'][ $tax ] ) ? (int) $GLOBALS['__termcount'][ $tax ] : 0;
}
function wp_json_encode( $data, $flags = 0 ) {
	return json_encode( $data, $flags );
}
function wp_unique_id( $prefix = '' ) {
	$GLOBALS['__uid'] = isset( $GLOBALS['__uid'] ) ? $GLOBALS['__uid'] + 1 : 1;
	return $prefix . $GLOBALS['__uid'];
}
function wpautop( $text ) {
	return '<p>' . str_replace( "\n\n", '</p>\n<p>', trim( (string) $text ) ) . '</p>';
}
function has_post_thumbnail( $post_id = 0 ) {
	return ! empty( $GLOBALS['__thumb'][ (int) $post_id ] );
}
function get_the_post_thumbnail_url( $post_id = 0, $size = '' ) {
	return has_post_thumbnail( $post_id ) ? $GLOBALS['__thumb'][ (int) $post_id ] : false;
}
function get_the_post_thumbnail( $post_id = 0, $size = '', $attr = array() ) {
	return has_post_thumbnail( $post_id ) ? '<img src="' . esc_url( $GLOBALS['__thumb'][ (int) $post_id ] ) . '" alt="">' : '';
}
function get_taxonomy( $tax ) {
	if ( ! taxonomy_exists( $tax ) ) {
		return false;
	}
	if ( isset( $GLOBALS['__taxonomies'][ $tax ] ) ) {
		return $GLOBALS['__taxonomies'][ $tax ];
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
function tara_artist_tax() {
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
	return ( 'good' === $nonce && 'tara_save_term' === $action ) || ( 'good-post' === $nonce && 'tara_post_artist' === $action );
}
function check_ajax_referer( $action, $name ) {
	return true;
}
function wp_nonce_field( $action, $name = '_wpnonce' ) {
	echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="good">';
}

/* ---------- پیشخوان (منو، آمار، ابزارک) ---------- */
function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '', $position = null ) {
	$GLOBALS['__admin_menus'][ $menu_slug ] = array(
		'page_title' => $page_title,
		'menu_title' => $menu_title,
		'capability' => $capability,
		'callback'   => $callback,
		'icon'       => $icon_url,
		'position'   => $position,
	);
	return 'toplevel_page_' . $menu_slug;
}
function add_submenu_page( $parent, $page_title, $menu_title, $capability, $menu_slug, $callback = '' ) {
	$GLOBALS['__admin_submenus'][ $parent ][ $menu_slug ] = array(
		'page_title' => $page_title,
		'menu_title' => $menu_title,
		'capability' => $capability,
		'callback'   => $callback,
	);
	$GLOBALS['submenu'][ $parent ][] = array( $menu_title, $capability, $menu_slug, $page_title );
	return ( $parent === $menu_slug ) ? 'toplevel_page_' . $parent : $parent . '_page_' . $menu_slug;
}
function admin_url( $path = '', $scheme = 'admin' ) {
	return 'https://ex/wp-admin/' . ltrim( (string) $path, '/' );
}
function home_url( $path = '' ) {
	return 'https://ex/' . ltrim( (string) $path, '/' );
}
function is_rtl() {
	return true;
}
function has_nav_menu( $location ) {
	return ! empty( $GLOBALS['__nav_menu_locations'][ $location ] );
}
function is_admin_bar_showing() {
	return true;
}
function wp_add_dashboard_widget( $widget_id, $widget_name, $callback ) {
	$GLOBALS['__dashboard_widgets'][ $widget_id ] = array( 'name' => $widget_name, 'callback' => $callback );
}
function wp_count_posts( $type = 'post' ) {
	return (object) array( 'publish' => isset( $GLOBALS['__count_posts'] ) ? (int) $GLOBALS['__count_posts'] : 0 );
}
function wp_count_comments( $post_id = 0 ) {
	return (object) array( 'approved' => isset( $GLOBALS['__count_comments'] ) ? (int) $GLOBALS['__count_comments'] : 0 );
}
function count_users() {
	return array( 'total_users' => isset( $GLOBALS['__count_users'] ) ? (int) $GLOBALS['__count_users'] : 0 );
}
function get_current_screen() {
	return (object) array( 'id' => isset( $GLOBALS['__screen_id'] ) ? (string) $GLOBALS['__screen_id'] : '' );
}
function wp_safe_redirect( $url ) {
	$GLOBALS['__redirect'] = $url;
}
function check_admin_referer( $action, $name = '_wpnonce' ) {
	return true;
}

/* ---------- وضعیت صفحه ---------- */
function is_category() {
	return ! empty( $GLOBALS['__is_cat'] );
}
function is_home() {
	return ! empty( $GLOBALS['__is_home'] );
}
function is_front_page() {
	return ! empty( $GLOBALS['__is_front'] );
}
function is_feed() {
	return ! empty( $GLOBALS['__is_feed'] );
}
function get_bloginfo( $what = '' ) {
	$b = isset( $GLOBALS['__blog'] ) ? (array) $GLOBALS['__blog'] : array();
	return isset( $b[ $what ] ) ? $b[ $what ] : '';
}
/* اگر آزمون بخواهد تابعِ واقعیِ قالب را بسنجد، TY_TEST_REAL_OT را تعریف می‌کند */
if ( ! defined( 'TY_TEST_REAL_OT' ) ) {
	function ot_get_option( $key, $default = '' ) {
		if ( array_key_exists( $key, $GLOBALS['__ot'] ) ) {
			return $GLOBALS['__ot'][ $key ];
		}
		$opts = isset( $GLOBALS['__options']['option_tree'] ) ? $GLOBALS['__options']['option_tree'] : array();
		if ( is_array( $opts ) && array_key_exists( $key, $opts ) ) {
			return $opts[ $key ];
		}
		return array_key_exists( $key, $GLOBALS['__options'] ) ? $GLOBALS['__options'][ $key ] : $default;
	}
}
function get_queried_object() {
	return $GLOBALS['__query_obj'];
}
function is_admin() {
	return ! empty( $GLOBALS['__is_admin'] );
}

/* ---------- دیدگاه‌ها ---------- */
function get_query_var( $var, $default = '' ) {
	return isset( $GLOBALS['__query_vars'][ $var ] ) ? $GLOBALS['__query_vars'][ $var ] : $default;
}
function get_comments_number( $post_id = 0 ) {
	return (int) $GLOBALS['__comments_total'];
}
function get_comment_pages_count() {
	return (int) $GLOBALS['__comment_total_pages'];
}
function get_comments_pagenum_link( $pagenum = 1, $max_page = 0 ) {
	return 'https://ex/?p=10&cpage=' . (int) $pagenum . '#comments';
}
/* شبیه‌سازیِ نزدیک به هسته: آرایه‌ای از لینک‌ها/برچسب‌ها، با aria-current برای صفحۀ جاری */
function paginate_comments_links( $args = array() ) {
	$args = wp_parse_args( (array) $args, array(
		'echo'      => true,
		'type'      => 'plain',
		'prev_text' => '«',
		'next_text' => '»',
	) );
	$total   = max( 1, (int) $GLOBALS['__comment_total_pages'] );
	$current = max( 1, min( $total, (int) get_query_var( 'cpage' ) ) );
	$out     = array();
	if ( $current > 1 ) {
		$out[] = '<a class="prev page-numbers" href="' . get_comments_pagenum_link( $current - 1 ) . '">' . $args['prev_text'] . '</a>';
	}
	for ( $i = 1; $i <= $total; $i++ ) {
		if ( $i === $current ) {
			$out[] = '<span aria-current="page" class="page-numbers current">' . $i . '</span>';
		} else {
			$out[] = '<a class="page-numbers" href="' . get_comments_pagenum_link( $i ) . '">' . $i . '</a>';
		}
	}
	if ( $current < $total ) {
		$out[] = '<a class="next page-numbers" href="' . get_comments_pagenum_link( $current + 1 ) . '">' . $args['next_text'] . '</a>';
	}
	if ( 'array' === $args['type'] ) {
		return $out;
	}
	$html = implode( '\n', $out );
	if ( $args['echo'] ) {
		echo $html;
		return;
	}
	return $html;
}
function wp_parse_args( $args, $defaults = array() ) {
	if ( is_object( $args ) ) {
		$args = get_object_vars( $args );
	} elseif ( ! is_array( $args ) ) {
		$args = array();
	}
	return array_merge( (array) $defaults, $args );
}
function is_singular( $types = '' ) {
	if ( ! empty( $GLOBALS['__not_singular'] ) ) {
		return false;
	}
	if ( '' === $types ) {
		return true;
	}
	$p = get_post( isset( $GLOBALS['__queried_id'] ) ? (int) $GLOBALS['__queried_id'] : 0 );
	$type = $p ? $p->post_type : 'post';
	return in_array( $type, (array) $types, true );
}
function get_the_ID() {
	if ( ! empty( $GLOBALS['__the_id'] ) ) {
		return (int) $GLOBALS['__the_id'];
	}
	return ! empty( $GLOBALS['__queried_id'] ) ? (int) $GLOBALS['__queried_id'] : 10;
}
function wp_doing_ajax() {
	return ! empty( $GLOBALS['__doing_ajax'] );
}
function wp_doing_cron() {
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
