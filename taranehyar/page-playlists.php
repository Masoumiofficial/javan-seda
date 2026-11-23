<?php
/** Template Name: پلی‌لیست‌های ترانه یار */
get_header();
$page_url = get_permalink();
$selected_id = isset( $_GET['list'] ) && is_scalar( $_GET['list'] ) ? absint( $_GET['list'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$mood_id = isset( $_GET['mood'] ) && is_scalar( $_GET['mood'] ) ? absint( $_GET['mood'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$number = isset( $_GET['list_page'] ) && is_scalar( $_GET['list_page'] ) ? max( 1, absint( $_GET['list_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
$list = $selected_id ? get_post( $selected_id ) : null;
if ( $list && ( 'tara_playlist' !== $list->post_type || 'publish' !== $list->post_status || ! empty( $list->post_password ) ) ) { $list = null; }
?>
<main class="ty-playlists tarlanweb_center" dir="rtl">
	<header class="ty-playlists__hero">
		<p class="ty-playlists__eyebrow">موسیقی برای هر لحظه</p>
		<h1><?php echo esc_html( $list ? $list->post_title : 'پلی‌لیست‌ها' ); ?></h1>
		<p><?php echo esc_html( $list ? 'مجموعه‌ای از آهنگ‌های منتخب برای شنیدن و میکس.' : 'پلی‌لیست‌های منتخب را بر اساس حال‌وهوایت پیدا کن یا فهرست شخصی خودت را بساز.' ); ?></p>
		<?php if ( function_exists( 'tara_acc_on' ) && tara_acc_on() && function_exists( 'tara_acc_feature' ) && tara_acc_feature( 'playlists' ) && function_exists( 'tara_acc_profile_url' ) ) : ?>
			<a class="ty-playlists__button" href="<?php echo esc_url( tara_acc_profile_url( 'playlists' ) ); ?>">پلی‌لیست‌های من</a>
		<?php endif; ?>
	</header>
	<?php if ( $selected_id && ! $list ) : ?>
		<p class="ty-playlists__empty">این پلی‌لیست پیدا نشد یا منتشر نشده است.</p>
		<a href="<?php echo esc_url( $page_url ); ?>">بازگشت به پلی‌لیست‌ها</a>
	<?php elseif ( $list ) : ?>
		<a class="ty-playlists__back" href="<?php echo esc_url( $page_url ); ?>">← همهٔ پلی‌لیست‌ها</a>
		<?php
		/* v3.17.0 — پوشش: تصویر شاخص، وگرنه کاورِ اولین آهنگِ قابل‌پخش.
		   برای پلی‌لیستِ بدون آهنگ، اصلاً به این شاخه نمی‌رسیم (پایین‌تر
		   «منتشرنشده» یا «خالی» پیام می‌گیرد). */
		$list_cover = function_exists( 'tara_playlist_cover' ) ? tara_playlist_cover( $list->ID, 'medium' ) : array( 'url' => '', 'alt' => '', 'source' => '' );
		$list_ids   = function_exists( 'tara_playlist_audio_ids' ) ? tara_playlist_audio_ids( get_post_meta( $list->ID, '_tara_playlist_tracks', true ) ) : array();
		$list_count = count( (array) $list_ids );
		?>
		<div class="ty-playlists__intro<?php echo '' === $list_cover['url'] ? ' ty-playlists__intro--nocover' : ''; ?>">
			<?php if ( '' !== $list_cover['url'] ) : ?>
				<img class="ty-playlists__cover" src="<?php echo esc_url( $list_cover['url'] ); ?>" width="150" height="150"
					alt="<?php echo esc_attr( $list_cover['alt'] ); ?>" loading="eager" decoding="async">
			<?php endif; ?>
			<div class="ty-playlists__intro-body">
				<?php if ( '' !== trim( (string) $list->post_content ) ) : ?>
					<div class="ty-playlists__desc-full"><?php echo wp_kses_post( wpautop( $list->post_content ) ); ?></div>
				<?php endif; ?>
				<p class="ty-playlists__meta">
					<span><?php echo esc_html( sprintf( '%s آهنگ', function_exists( 'tara_number_fa' ) ? tara_number_fa( $list_count ) : (string) $list_count ) ); ?></span>
					<?php
					$list_mood = (int) get_post_meta( $list->ID, '_tara_playlist_mood', true );
					$list_term = $list_mood ? get_term( $list_mood, 'tara_mood' ) : null;
					if ( $list_term && ! is_wp_error( $list_term ) ) :
						$list_mood_link = get_term_link( $list_term );
						?>
						<span class="ty-playlists__mood">
							<a href="<?php echo esc_url( is_wp_error( $list_mood_link ) ? add_query_arg( 'mood', $list_mood, $page_url ) : $list_mood_link ); ?>"><?php echo esc_html( $list_term->name ); ?></a>
						</span>
					<?php endif; ?>
				</p>
			</div>
		</div>
		<?php echo tara_playlist_radio( $list_ids, $list->post_title ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<section class="ty-playlists__tracks" id="tracks"><h2>آهنگ‌های این پلی‌لیست</h2>
		<?php
		/* v3.17.0 — فهرستِ استانداردِ ردیفِ آهنگ با لینکِ هنرمند (خزش‌پذیر).
		   اگر آهنگ نبود، پیام حالت خالی. اگر تابع تازه در دسترس نبود،
		   همان فهرست حساب کاربری (رفتار ۳.۱۶.۰) حفظ می‌شود. */
		if ( function_exists( 'tara_playlist_tracks_html' ) ) {
			echo tara_playlist_tracks_html( $list->ID ); // phpcs:ignore WordPress.Security.EscapeOutput -- در تابع escape شده
		} elseif ( empty( $list_ids ) ) {
			echo '<p class="ty-playlists__empty">هنوز آهنگ قابل پخشی در این پلی‌لیست نیست.</p>';
		} elseif ( function_exists( 'tara_acc_track_list' ) ) {
			echo tara_acc_track_list( $list_ids, array( 'mode' => 'public', 'title' => $list->post_title ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		?></section>
	<?php else : ?>
		<?php $moods = get_terms( array( 'taxonomy' => 'tara_mood', 'hide_empty' => false, 'orderby' => 'name' ) ); ?>
		<nav class="ty-playlists__filters" aria-label="فیلتر حال‌وهوا">
			<a href="<?php echo esc_url( $page_url ); ?>"<?php echo ! $mood_id ? ' aria-current="page"' : ''; ?>>همه</a>
			<?php if ( ! is_wp_error( $moods ) ) : foreach ( $moods as $mood ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'mood', (int) $mood->term_id, $page_url ) ); ?>"<?php echo $mood_id === (int) $mood->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $mood->name ); ?></a>
			<?php endforeach; endif; ?>
		</nav>
		<?php
		$args = array( 'post_type' => 'tara_playlist', 'post_status' => 'publish', 'posts_per_page' => 12, 'paged' => $number, 'ignore_sticky_posts' => true, 'has_password' => false );
		if ( $mood_id ) { $args['meta_query'] = array( array( 'key' => '_tara_playlist_mood', 'value' => $mood_id, 'compare' => '=' ) ); }
		$query = new WP_Query( $args );
		/* v3.17.0 — نمایشِ تعداد واقعی روی همان برگه؛ عددِ ساختگی نیست. */
		$total_playlists = function_exists( 'tara_playlist_ids' ) ? count( tara_playlist_ids( array( 'limit' => 50 ) ) ) : (int) $query->found_posts;
		if ( ! $mood_id && $total_playlists > 0 ) :
			?>
			<p class="ty-playlists__count-line"><?php echo esc_html( sprintf( '%s پلی‌لیست منتخب', function_exists( 'tara_number_fa' ) ? tara_number_fa( $total_playlists ) : (string) $total_playlists ) ); ?></p>
		<?php endif; ?>
		<div class="ty-playlists__grid">
			<?php
			/* v3.17.0 — کارت از جزءِ مشترک: پوشش (شاخص یا اولین آهنگ)،
			   توضیحِ واقعی و شمارشِ آهنگ. پلی‌لیست بی‌آهنگ کارت نمی‌گیرد
			   تا صفحهٔ نازک ساخته نشود. */
			foreach ( $query->posts as $item ) {
				if ( function_exists( 'tara_playlist_card_html' ) ) {
					echo tara_playlist_card_html( $item->ID, $page_url ); // phpcs:ignore WordPress.Security.EscapeOutput -- در تابع escape شده
					continue;
				}
				if ( ! function_exists( 'tara_playlist_audio_ids' ) || ! tara_playlist_audio_ids( get_post_meta( $item->ID, '_tara_playlist_tracks', true ) ) ) {
					continue;
				}
				?>
				<a class="ty-playlists__card" href="<?php echo esc_url( add_query_arg( 'list', $item->ID, $page_url ) ); ?>">
					<?php if ( has_post_thumbnail( $item->ID ) ) : echo get_the_post_thumbnail( $item->ID, 'medium', array( 'alt' => '', 'loading' => 'lazy' ) ); else : ?><span class="ty-playlists__placeholder" aria-hidden="true">♫</span><?php endif; ?>
					<strong><?php echo esc_html( $item->post_title ); ?></strong>
					<span><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $item->post_content ), 16, '…' ) ); ?></span>
				</a>
				<?php
			}
			?>
		</div>
		<?php if ( empty( $query->posts ) ) : ?>
			<p class="ty-playlists__empty"><?php echo $mood_id ? 'برای این حال‌وهوا هنوز پلی‌لیستی منتشر نشده است.' : 'هنوز پلی‌لیستی منتشر نشده است.'; ?></p>
			<?php if ( ! $mood_id ) : ?>
				<p class="ty-playlists__empty-hint">پلی‌لیست‌ها در پیشخوان ← «پلی‌لیست‌های منتخب» ساخته می‌شوند. تا وقتی پلی‌لیستی آهنگِ قابل‌پخش نداشته باشد، این‌جا نمایش داده نمی‌شود.</p>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( $query->max_num_pages > 1 ) : ?>
			<nav class="ty-playlists__pagination" aria-label="صفحات پلی‌لیست">
				<?php if ( $number > 1 ) : ?><a href="<?php echo esc_url( add_query_arg( array( 'mood' => $mood_id, 'list_page' => $number - 1 ), $page_url ) ); ?>">صفحهٔ قبل</a><?php endif; ?>
				<span><?php echo esc_html( $number ); ?> / <?php echo esc_html( $query->max_num_pages ); ?></span>
				<?php if ( $number < $query->max_num_pages ) : ?><a href="<?php echo esc_url( add_query_arg( array( 'mood' => $mood_id, 'list_page' => $number + 1 ), $page_url ) ); ?>">صفحهٔ بعد</a><?php endif; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
