<?php
/**
 * قالبِ نتایجِ زندهٔ جست‌وجو (افزونهٔ SearchWP Live Ajax Search)
 * -----------------------------------------------------------------------------
 * اگر افزونه فعال باشد، همین فایل نتیجهٔ هر جست‌وجو را می‌سازد؛ اگر نباشد،
 * هیچ‌جا بارگذاری نمی‌شود. مارکاپ با کلاس‌های خودِ افزونه یکی است تا رفتار
 * جست‌وجو (کلیدهای جهت، انتخاب با Enter، `role="option"`) دست‌نخورده بماند.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( have_posts() ) : ?>
	<?php while ( have_posts() ) : the_post(); ?>
		<div class="searchwp-live-search-result" role="option" id="" aria-selected="false">
			<p><a href="<?php echo esc_url( get_permalink() ); ?>">
				<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'thumb4' ); } ?>
				<?php
				$tara_artist = function_exists( 'get_field' ) ? get_field( 'artist' ) : '';
				$tara_song   = function_exists( 'get_field' ) ? get_field( 'song' ) : '';
				if ( $tara_artist || $tara_song ) {
					echo esc_html( (string) $tara_artist );
					echo '<span>' . esc_html( (string) $tara_song ) . '</span>';
				} else {
					the_title( '' );
				}
				?>
			</a></p>
		</div>
	<?php endwhile; ?>
<?php else : ?>
	<p class="searchwp-live-search-no-results" role="option">
		<em>نتیجه ای یافت نشد!</em>
	</p>
<?php endif; ?>
