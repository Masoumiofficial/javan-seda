<?php
/** Curated playlists and bounded, published audio queues. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tara_playlist_register() {
	register_post_type( 'tara_playlist', array(
		'labels' => array( 'name' => 'پلی‌لیست‌های منتخب', 'singular_name' => 'پلی‌لیست منتخب', 'add_new_item' => 'افزودن پلی‌لیست', 'edit_item' => 'ویرایش پلی‌لیست' ),
		'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'show_in_rest' => false,
		'menu_icon' => 'dashicons-playlist-audio', 'supports' => array( 'title', 'editor', 'thumbnail' ),
	) );
}
add_action( 'init', 'tara_playlist_register', 15 );

/**
 * Only published audio posts with a usable source; never expose drafts.
 *
 * v3.17.0 — پرهیز از N+1: پیش از این برای هر شناسه `get_post` صدا زده
 * می‌شد و در پلی‌لیستِ ۲۰تایی ۲۰ کوئری اضافه می‌ساخت. حالا کلِ فهرست با
 * یک کوئری یکجا واکشی و همان قواعد روی نتیجه اعمال می‌شود؛ ترتیب و
 * رفتار (حذف پیش‌نویس/رمزدار/غیرصوتی/بدون منبع) دقیقاً همان قبلی است.
 */
function tara_playlist_audio_ids( $ids ) {
	$out  = array();
	$list = array();
	foreach ( array_slice( (array) $ids, 0, 1000 ) as $raw ) {
		if ( ! is_scalar( $raw ) ) { continue; }
		$id = absint( $raw );
		if ( ! $id || in_array( $id, $list, true ) ) { continue; }
		$list[] = $id;
	}
	if ( ! $list ) {
		return $out;
	}
	/* واکشیِ دسته‌ای: هر شناسه فقط یک بار (کشِ داخلی WP هم آن را پر می‌کند). */
	$posts_cache = array();
	foreach ( array_chunk( $list, 200 ) as $chunk ) {
		foreach ( (array) get_posts( array(
			'post__in'            => $chunk,
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => count( $chunk ),
			'orderby'             => 'post__in',
			'fields'              => 'all',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'suppress_filters'    => false,
		) ) as $p ) {
			$posts_cache[ (int) $p->ID ] = $p;
		}
	}
	foreach ( $list as $id ) {
		$post = isset( $posts_cache[ $id ] ) ? $posts_cache[ $id ] : null;
		if ( ! $post || ! empty( $post->post_password ) ) { continue; }
		if ( 'post' !== $post->post_type || 'publish' !== $post->post_status || ! function_exists( 'tara_track_src' ) || '' === tara_track_src( $id ) ) { continue; }
		$raw_type = get_post_meta( $id, 'musics_type', true );
		$type = is_scalar( $raw_type ) ? strtolower( trim( (string) $raw_type ) ) : '';
		$map = apply_filters( 'tara_artist_type_map', array( '' => 'audio', 'mp3' => 'audio', 'audio' => 'audio', 'ahang' => 'audio', 'آهنگ' => 'audio', 'آهنگ جدید' => 'audio', 'song' => 'audio', 'mp4' => 'video', 'video' => 'video', 'musicvideo' => 'video', 'music-video' => 'video', 'موزیک ویدیو' => 'video', 'موزیک‌ویدیو' => 'video', 'کلیپ' => 'video', 'album' => 'album', 'آلبوم' => 'album' ) );
		if ( ! isset( $map[ $type ] ) || 'audio' !== $map[ $type ] ) { continue; }
		$out[] = $id;
	}
	return $out;
}

function tara_playlist_meta_box( $post ) {
	wp_nonce_field( 'tara_playlist_save', 'tara_playlist_nonce' );
	$ids = get_post_meta( $post->ID, '_tara_playlist_tracks', true );
	$mood = (int) get_post_meta( $post->ID, '_tara_playlist_mood', true );
	$terms = get_terms( array( 'taxonomy' => 'tara_mood', 'hide_empty' => false ) );
	echo '<p><label for="tara-playlist-mood">حال‌وهوا</label> <select id="tara-playlist-mood" name="tara_playlist_mood"><option value="0">بدون حال‌وهوا</option>';
	if ( ! is_wp_error( $terms ) ) { foreach ( $terms as $term ) { echo '<option value="' . esc_attr( $term->term_id ) . '"' . selected( $mood, $term->term_id, false ) . '>' . esc_html( $term->name ) . '</option>'; } }
	echo '</select></p><p><label for="tara-playlist-tracks">شناسهٔ آهنگ‌ها به ترتیب، با ویرگول جدا کنید</label></p>';
	echo '<textarea id="tara-playlist-tracks" name="tara_playlist_tracks" rows="4" class="widefat">' . esc_textarea( implode( ', ', (array) $ids ) ) . '</textarea>';
	echo '<p>فقط شناسهٔ نوشته‌های منتشرشدهٔ صوتی دارای فایل پذیرفته می‌شود؛ سقف ۲۰۰ آهنگ. کاور را در «تصویر شاخص» و توضیح را در ویرایشگر وارد کنید. حال‌وهوا از بخش نوشته‌ها ← حال‌وهواها مدیریت می‌شود.</p>';
}
function tara_playlist_meta_register() { add_meta_box( 'tara-playlist-details', 'آهنگ‌ها و حال‌وهوا', 'tara_playlist_meta_box', 'tara_playlist', 'normal', 'high' ); }
add_action( 'add_meta_boxes', 'tara_playlist_meta_register' );
function tara_playlist_meta_save( $id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $id ) ) || ! current_user_can( 'edit_post', $id ) || ! isset( $_POST['tara_playlist_nonce'] ) || ! is_string( $_POST['tara_playlist_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tara_playlist_nonce'] ) ), 'tara_playlist_save' ) ) { return; }
	if ( isset( $_POST['tara_playlist_tracks'] ) && is_string( $_POST['tara_playlist_tracks'] ) ) {
		$raw = wp_unslash( $_POST['tara_playlist_tracks'] );
		$ids = tara_playlist_audio_ids( array_slice( preg_split( '/[\s,،]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY ), 0, 200 ) );
		update_post_meta( $id, '_tara_playlist_tracks', $ids );
	}
	if ( isset( $_POST['tara_playlist_mood'] ) && is_scalar( $_POST['tara_playlist_mood'] ) ) {
		$mood = absint( $_POST['tara_playlist_mood'] );
		$term = $mood ? get_term( $mood, 'tara_mood' ) : null;
		update_post_meta( $id, '_tara_playlist_mood', $term && ! is_wp_error( $term ) && 'tara_mood' === $term->taxonomy ? $mood : 0 );
	}
}
add_action( 'save_post_tara_playlist', 'tara_playlist_meta_save' );

function tara_playlist_radio( $ids, $title, $kind = 'curated' ) {
	if ( ! function_exists( 'tara_radio_box' ) || ! function_exists( 'tara_radio_track' ) || ! function_exists( 'tara_radio_on' ) || ! tara_radio_on() ) { return ''; }
	$option = 'personal' === $kind ? 'playlist_personal' : 'playlist_curated';
	if ( function_exists( 'tara_radio_option' ) && 'on' !== tara_radio_option( $option ) ) { return ''; }
	$tracks = array();
	foreach ( tara_playlist_audio_ids( $ids ) as $id ) { $track = tara_radio_track( $id ); if ( $track ) { $tracks[] = $track; } }
	if ( empty( $tracks ) ) { return ''; }
	return tara_radio_box( array( 'context' => 'playlist', 'title' => 'رادیو ' . $title, 'subtitle' => 'پخش و میکس فقط از آهنگ‌های همین پلی‌لیست', 'tracks' => $tracks, 'sources' => false, 'limit' => 40 ) );
}

function tara_playlists_assets() {
	if ( ( function_exists( 'is_page_template' ) && is_page_template( 'page-playlists.php' ) ) || ( function_exists( 'tara_page_is_home' ) && tara_page_is_home() && ( ! function_exists( 'tara_opt_on' ) || tara_opt_on( 'playlist_home', true ) ) ) ) {
		wp_enqueue_style( 'tara-playlists', get_template_directory_uri() . '/css/tara-playlists.css', array(), defined( 'TARANEHYAR_VERSION' ) ? TARANEHYAR_VERSION : '1' );
	}
}
add_action( 'wp_enqueue_scripts', 'tara_playlists_assets', 30 );

/** A link and a small selection, only after the directory page is published. */
function tara_playlists_home() {
	if ( function_exists( 'tara_opt_on' ) && ! tara_opt_on( 'playlist_home', true ) ) { return ''; }
	$pages = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'page-playlists.php', 'number' => 1, 'post_status' => 'publish' ) );
	if ( empty( $pages ) ) { return ''; }
	$url = get_permalink( $pages[0] );
	/* v3.17.0 — فقط پلی‌لیست‌های واقعیِ دارای آهنگِ قابل‌پخش؛ کارت از جزء
	   مشترک (پوشش/توضیح/شمارش). پلی‌لیستِ خالی این‌جا هم دیده نمی‌شود. */
	$items = function_exists( 'tara_playlist_ids' ) ? tara_playlist_ids( array( 'limit' => 4 ) ) : array();
	if ( empty( $items ) ) { return ''; }
	ob_start();
	?>
	<section class="ty-playlists ty-playlists--home" dir="rtl"><header class="ty-playlists__homehead"><h2>پلی‌لیست‌های منتخب</h2><a href="<?php echo esc_url( $url ); ?>">همهٔ پلی‌لیست‌ها</a></header><div class="ty-playlists__grid">
	<?php foreach ( $items as $item_id ) : ?>
		<?php
		if ( function_exists( 'tara_playlist_card_html' ) ) {
			echo tara_playlist_card_html( $item_id, $url ); // phpcs:ignore WordPress.Security.EscapeOutput -- در تابع escape شده
			continue;
		}
		$item = get_post( $item_id );
		if ( ! $item ) { continue; }
		?>
		<a class="ty-playlists__card" href="<?php echo esc_url( add_query_arg( 'list', $item->ID, $url ) ); ?>">
			<?php if ( has_post_thumbnail( $item->ID ) ) : echo get_the_post_thumbnail( $item->ID, 'medium', array( 'alt' => '', 'loading' => 'lazy' ) ); else : ?><span class="ty-playlists__placeholder" aria-hidden="true">♫</span><?php endif; ?>
			<strong><?php echo esc_html( $item->post_title ); ?></strong><span><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $item->post_content ), 12, '…' ) ); ?></span>
		</a>
	<?php endforeach; ?>
	</div></section>
	<?php
	return (string) ob_get_clean();
}
