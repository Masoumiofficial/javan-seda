<?php
/** Artist hub integration for release requests. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'woocommerce_order_needs_payment', 'tara_release_order_needs_payment', 10, 2 );
add_action( 'wp_ajax_tara_release_submit', 'tara_release_ajax_submit' );
add_action( 'wp_ajax_tara_release_checkout', 'tara_release_ajax_checkout' );
add_action( 'admin_post_tara_release_download', 'tara_release_download' );
add_action( 'woocommerce_payment_complete', 'tara_release_wc_paid' );
add_action( 'woocommerce_order_status_processing', 'tara_release_wc_paid' );
add_action( 'woocommerce_order_status_completed', 'tara_release_wc_paid' );
add_action( 'tara_release_retry_paid', 'tara_release_wc_paid' );
add_action( 'woocommerce_order_status_refunded', 'tara_release_wc_refunded' );
add_action( 'tara_release_retry_refunded', 'tara_release_wc_refunded' );

function tara_release_assets() {
	if ( ! is_user_logged_in() ) { return; }
	$css = '/css/tara-release.css'; $js = '/js/tara-release.js';
	wp_enqueue_style( 'tara-release', TARANEHYAR_URI . $css, array(), filemtime( TARANEHYAR_DIR . $css ) );
	wp_enqueue_script( 'tara-release', TARANEHYAR_URI . $js, array( 'taranehyar-account' ), filemtime( TARANEHYAR_DIR . $js ), true );
}
add_action( 'wp_enqueue_scripts', 'tara_release_assets', 35 );

function tara_release_admin_menu() {
	add_submenu_page( 'tara-hub', 'درخواست‌های انتشار', 'درخواست‌های انتشار', 'manage_options', 'tara-release', 'tara_release_admin_page' );
}
add_action( 'admin_menu', 'tara_release_admin_menu', 20 );

function tara_release_admin_post() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'tara_release_admin' );
	$input = wp_unslash( $_POST );
	$user_id = isset( $input['user_id'] ) ? (int) $input['user_id'] : 0;
	$id = isset( $input['request_id'] ) && is_string( $input['request_id'] ) ? $input['request_id'] : '';
	unset( $input['quote_revision'] );
	if ( isset( $input['save_quote'] ) ) { $input['quote_revision'] = str_replace( '-', '', wp_generate_uuid4() ); }
	$result = tara_release_admin_update( $user_id, $id, $input, time() );
	if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), 'خطا', array( 'response' => 400, 'back_link' => true ) ); }
	if ( function_exists( 'tara_acc_notify' ) ) { tara_acc_notify( $user_id, array( 'title' => 'درخواست انتشار شما به‌روزرسانی شد.', 'url' => add_query_arg( 'tab', 'artist', tara_acc_page_url( 'page-account.php' ) ) ) ); }
	wp_safe_redirect( admin_url( 'admin.php?page=tara-release&updated=1' ) ); exit;
}
add_action( 'admin_post_tara_release_manage', 'tara_release_admin_post' );

function tara_release_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$page = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
	$users = get_users( array( 'meta_key' => 'tara_artist_requests', 'number' => 50, 'offset' => ( $page - 1 ) * 50, 'orderby' => 'ID', 'order' => 'ASC' ) );
	$statuses = tara_release_statuses();
	?> <div class="wrap"><h1>درخواست‌های انتشار</h1><p>فهرست بر اساس کاربران صفحه‌بندی شده است. پرداخت بین‌المللی فعال نیست.</p>
	<?php if ( isset( $_GET['updated'] ) ) : ?><div class="notice notice-success"><p>تغییرات ذخیره شد.</p></div><?php endif; ?>
	<?php foreach ( $users as $user ) : foreach ( tara_hub_requests( $user->ID ) as $row ) : if ( ! isset( $row['type'], $row['id'], $row['status'] ) || 'release' !== $row['type'] ) { continue; } ?>
	<div class="postbox" style="padding:16px;margin:16px 0"><h2><?php echo esc_html( $row['title'] ); ?> — <?php echo esc_html( $user->display_name ); ?> (#<?php echo esc_html( (string) $user->ID ); ?>)</h2>
	<p>شناسه: <?php echo esc_html( $row['id'] ); ?> | وضعیت: <?php echo esc_html( isset( $statuses[ $row['status'] ] ) ? $statuses[ $row['status'] ] : $row['status'] ); ?> | تعرفه اولیه: <?php echo esc_html( number_format_i18n( (int) $row['price'] ) ); ?> تومان</p>
	<details><summary>جزئیات درخواست</summary><?php foreach ( (array) $row['details'] as $key => $value ) : if ( ! is_scalar( $value ) ) { continue; } ?><p><strong><?php echo esc_html( $key ); ?>:</strong> <?php echo esc_html( is_bool( $value ) ? ( $value ? 'بله' : 'خیر' ) : $value ); ?></p><?php endforeach; ?></details>
	<?php foreach ( isset( $row['files'] ) ? (array) $row['files'] : array() as $key => $file ) : if ( ! in_array( $key, array( 'work', 'cover', 'document' ), true ) ) { continue; } $url = add_query_arg( array( 'action' => 'tara_release_download', 'user_id' => (int) $user->ID, 'request_id' => $row['id'], 'file' => $key ), admin_url( 'admin-post.php' ) ); $url = wp_nonce_url( $url, 'tara_release_file_' . $user->ID . '_' . $row['id'] . '_' . $key ); ?><p><a href="<?php echo esc_url( $url ); ?>">دریافت فایل <?php echo esc_html( $key ); ?></a></p><?php endforeach; ?>
	<details><summary>تاریخچه</summary><?php foreach ( isset( $row['history'] ) ? (array) $row['history'] : array() as $history ) : ?><p><?php echo esc_html( isset( $statuses[ $history['status'] ] ) ? $statuses[ $history['status'] ] : $history['status'] ); ?> — <?php echo esc_html( date_i18n( 'Y/m/d H:i', (int) $history['time'] ) ); ?><?php if ( isset( $history['event'] ) ) : ?> — <?php echo esc_html( $history['event'] ); ?><?php endif; ?><?php if ( isset( $history['final_toman'] ) ) : ?> — <?php echo esc_html( number_format_i18n( (int) $history['final_toman'] ) ); ?> تومان؛ هزینه‌ها: <?php echo esc_html( $history['extra_costs'] ); ?>؛ شرایط: <?php echo esc_html( $history['refund_terms'] ); ?><?php endif; ?><?php if ( ! empty( $history['note'] ) ) : ?> — <?php echo esc_html( $history['note'] ); ?><?php endif; ?></p><?php endforeach; ?></details>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="tara_release_manage"><input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $user->ID ); ?>"><input type="hidden" name="request_id" value="<?php echo esc_attr( $row['id'] ); ?>"><?php wp_nonce_field( 'tara_release_admin' ); ?>
	<p><label>وضعیت <select name="status"><option value="">بدون تغییر</option><?php foreach ( $statuses as $key => $label ) : if ( ! tara_release_transition_allowed( $row['status'], $key, 'admin' ) ) { continue; } ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label></p>
	<p><label>یادداشت مدیر<br><textarea name="note" rows="3" style="width:100%;max-width:600px"><?php echo esc_textarea( isset( $row['note'] ) ? $row['note'] : '' ); ?></textarea></label></p>
	<p><label>لینک انتشار <input type="url" dir="ltr" name="publication_url" value="<?php echo esc_attr( isset( $row['publication_url'] ) ? $row['publication_url'] : '' ); ?>"></label></p>
	<?php if ( in_array( $row['status'], array( 'approved', 'awaiting_payment' ), true ) && empty( $row['order_id'] ) ) : ?><fieldset><legend><b>پیش‌فاکتور اختصاصی</b></legend><p><label>مبلغ نهایی به تومان <input type="number" min="1" max="2000000000" name="final_toman" value="<?php echo esc_attr( isset( $row['final_toman'] ) ? (string) $row['final_toman'] : '' ); ?>"></label></p><p><label>هزینه‌های جانبی (اگر ندارد، بنویسید ندارد)<br><textarea name="extra_costs" rows="2" style="width:100%;max-width:600px"><?php echo esc_textarea( isset( $row['extra_costs'] ) ? $row['extra_costs'] : '' ); ?></textarea></label></p><p><label>شرایط دقیق لغو و بازپرداخت<br><textarea name="refund_terms" rows="3" style="width:100%;max-width:600px"><?php echo esc_textarea( isset( $row['refund_terms'] ) ? $row['refund_terms'] : '' ); ?></textarea></label></p><label><input type="checkbox" name="save_quote" value="1"> ذخیرهٔ پیش‌فاکتور جدید و ابطال پذیرش قبلی</label></fieldset><?php endif; ?>
	<p><button type="submit" class="button button-primary">ذخیره</button></p></form></div>
	<?php endforeach; endforeach; ?>
	<p><?php if ( $page > 1 ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'tara-release', 'paged' => $page - 1 ), admin_url( 'admin.php' ) ) ); ?>">قبلی</a><?php endif; ?> <?php if ( count( $users ) === 50 ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'tara-release', 'paged' => $page + 1 ), admin_url( 'admin.php' ) ) ); ?>">بعدی</a><?php endif; ?></p></div><?php
}

/** Never index the private account template; public marketing pages remain independent. */
function tara_release_account_robots( $robots ) {
	if ( function_exists( 'is_page_template' ) && is_page_template( 'page-account.php' ) ) { $robots['noindex'] = true; unset( $robots['index'] ); }
	return $robots;
}
add_filter( 'wp_robots', 'tara_release_account_robots' );

/** Public services page only; avoid overriding active SEO plugins. */
function tara_release_owns_seo() {
	return function_exists( 'is_page' ) && is_page( 'works' ) && ( ! function_exists( 'tara_seo_has_plugin' ) || '' === tara_seo_has_plugin() ) && ! defined( 'WPSEO_VERSION' ) && ! defined( 'RANK_MATH_VERSION' ) && ! defined( 'AIOSEO_VERSION' ) && ! defined( 'SEOPRESS_VERSION' ) && ! defined( 'THE_SEO_FRAMEWORK_VERSION' );
}
function tara_release_public_title( $title ) {
	return tara_release_owns_seo() ? 'همکاری و انتشار آثار موسیقی | آهنگ بان' : $title;
}
add_filter( 'pre_get_document_title', 'tara_release_public_title', 20 );
function tara_release_public_description() {
	$default = 'درخواست همکاری و انتشار آثار موسیقی در آهنگ بان؛ بررسی خدمات، تعرفه‌های پایه، حقوق اثر و شرایط انتشار داخلی و بین‌المللی.';
	$value = function_exists( 'ot_get_option' ) ? ot_get_option( 'release_seo_description', $default ) : $default;
	return is_string( $value ) && '' !== trim( $value ) ? sanitize_text_field( $value ) : $default;
}
function tara_release_public_schema() {
	if ( ! tara_release_owns_seo() ) { return; }
	$description = tara_release_public_description();
	$schema = array( '@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'همکاری و انتشار آثار موسیقی', 'description' => $description, 'url' => get_permalink(), 'provider' => array( '@type' => 'Organization', 'name' => 'آهنگ بان', 'url' => home_url( '/' ) ) );
	if ( ! function_exists( 'tara_seo_active' ) || ! tara_seo_active() ) { echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n"; }
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with hex flags
}
add_action( 'wp_head', 'tara_release_public_schema', 5 );
