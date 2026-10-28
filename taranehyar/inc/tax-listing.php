<?php
/**
 * فهرست آهنگ‌های یک ترم — قالب مشترکِ تاکسونومی‌های موسیقی
 * ==========================================================================
 * `taxonomy-tara_genre.php`، `taxonomy-tara_mood.php`، `taxonomy-tara_songwriter.php`،
 * `taxonomy-tara_composer.php` و `taxonomy-tara_arranger.php` همه همین فایل را
 * بار می‌کنند؛ پس ظاهرشان یکی است و نگه‌داری‌شان یک‌جاست.
 *
 * چیدمان: سربرگ ترم + پلیر رادیو (اگر آهنگی فایل صوتی داشته باشد) + فهرست کامل.
 *
 * @package Taranehyar
 */

get_header();

$tara_term = get_queried_object();
$tara_tax  = ( $tara_term instanceof WP_Term ) ? $tara_term->taxonomy : '';
$tara_name = ( $tara_term instanceof WP_Term ) ? $tara_term->name : 'آرشیو';
$tara_desc = get_the_archive_description();
$tara_icon = 'icofont-music-note';

if ( '' !== $tara_tax && function_exists( 'tara_music_taxonomies' ) ) {
	foreach ( tara_music_taxonomies() as $tara_cfg ) {
		if ( $tara_cfg['taxonomy'] === $tara_tax ) {
			$tara_icon = 'icofont-tags';
			break;
		}
	}
}
?>
<div class="ty-archive tarlanweb_center" dir="rtl">

	<header class="ty-archive__head">
		<span class="ty-archive__eyebrow">
			<i class="<?php echo esc_attr( $tara_icon ); ?>" aria-hidden="true"></i>
			<?php echo esc_html( function_exists( 'tara_music_taxonomy_label' ) && '' !== $tara_tax ? tara_music_taxonomy_label( $tara_tax, 'plural' ) : 'آرشیو' ); ?>
		</span>
		<h1><?php echo esc_html( $tara_name ); ?></h1>

		<?php if ( ! empty( $tara_desc ) ) : ?>
			<div class="ty-archive__desc"><?php echo wp_kses_post( $tara_desc ); ?></div>
		<?php endif; ?>

		<p class="ty-archive__count">
			<?php echo esc_html( sprintf( '%s آهنگ', function_exists( 'tara_number_fa' ) ? tara_number_fa( (int) $GLOBALS['wp_query']->found_posts ) : (int) $GLOBALS['wp_query']->found_posts ) ); ?>
		</p>
	</header>

	<?php
	/* v3.7.3 — «کارت شخص»: همین نام در نقش‌های دیگر (SEO: ادغام موجودیت) */
	if ( function_exists( 'tara_person_role_taxes' ) ) {
		$tara_roles = tara_person_role_taxes();
		if ( isset( $tara_roles[ $tara_tax ] ) && function_exists( 'tara_person_card' ) ) {
			tara_person_card( $tara_term );
		}
	}
	?>

	<?php
	if ( function_exists( 'tara_player' ) && have_posts() ) {
		$tara_archive_tracks = tara_track_playlist( $GLOBALS['wp_query']->posts );
		if ( ! empty( $tara_archive_tracks ) ) {
			echo tara_player( // phpcs:ignore WordPress.Security.EscapeOutput -- خروجی همین قالب
				array(
					'tracks'  => $tara_archive_tracks,
					'title'   => 'پخش آنلاین',
					'context' => 'archive',
				)
			);
		}
	}
	?>

	<?php if ( have_posts() ) : ?>
		<ul class="ty-tracklist ty-archive__list">
			<?php
			$tara_i = 0;
			while ( have_posts() ) :
				the_post();
				echo '<li>' . tara_track_row( get_the_ID(), array( 'index' => $tara_i, 'show_date' => true ) ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
				$tara_i++;
			endwhile;
			?>
		</ul>

		<?php
		if ( function_exists( 'pagination' ) ) {
			pagination( $GLOBALS['wp_query']->max_num_pages );
		}
		?>
	<?php else : ?>
		<section class="ty-archive__empty">
			<h2>هنوز آهنگی در این بخش نیست</h2>
			<p>وقتی در ویرایش نوشته‌ها این بخش را انتخاب کنید، آهنگ‌ها همین‌جا فهرست می‌شوند.</p>
		</section>
	<?php endif; ?>

</div>

<?php
get_footer();
