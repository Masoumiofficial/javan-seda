<?php
/**
 * Template Name: رادیو ترانه یار
 * ===========================================================================
 * یک صفحهٔ مستقل برای «رادیو»: کارتِ رادیو در حالتِ کامل (با انتخابِ منبع)،
 * توضیحِ کوتاهِ اینکه میکس چطور ساخته می‌شود و فهرستِ ژانرها/حال‌وهواها برای
 * ادامهٔ شنیدن. برای ساختنش: «برگه‌ها ← افزودن» و در «ویژگی‌های برگه» قالبِ
 * «رادیو ترانه یار» را انتخاب کنید.
 *
 * اگر می‌خواهید همین کارت داخل یک برگهٔ معمولی بیاید، از شورت‌کد
 * `[tara_radio]` استفاده کنید؛ هر دو یک مارکاپ دارند.
 *
 * @package Taranehyar
 */

get_header();

$tara_radio_page_title = get_the_title();
$radio_text = static function ( $key, $default ) {
    $value = function_exists( 'ot_get_option' ) ? ot_get_option( 'radio_page_' . $key, $default ) : $default;
    return is_scalar( $value ) ? (string) $value : $default;
};

?>
<div class="ty-archive tarlanweb_center" dir="rtl">

	<header class="ty-radio-page__head">
		<?php if ( 'on' === $radio_text( 'eyebrow_on', 'on' ) ) : ?>
		<span class="ty-radio-page__eyebrow"><i class="icofont-sound-wave-alt" aria-hidden="true"></i> <?php echo esc_html( $radio_text( 'eyebrow', 'رادیو آهنگ' ) ); ?></span>
		<?php endif; ?>
		<h1 class="ty-radio-page__title"><?php echo esc_html( '' !== $tara_radio_page_title ? $tara_radio_page_title : 'رادیو ترانه یار' ); ?></h1>
		<?php if ( 'on' === $radio_text( 'intro_on', 'on' ) ) : ?>
		<p class="ty-radio-page__sub"><?php echo esc_html( $radio_text( 'intro', 'منبع دلخواهتان را انتخاب کنید و به آهنگ‌ها گوش دهید.' ) ); ?></p>
		<?php endif; ?>
	</header>

	<?php
	if ( function_exists( 'tara_radio_box' ) && function_exists( 'tara_radio_on' ) && tara_radio_on() ) {
		echo tara_radio_box( // phpcs:ignore WordPress.Security.EscapeOutput -- مارکاپِ همین قالب
			array(
				'context'  => 'page',
				'title'    => $radio_text( 'player_title', 'رادیو ' . get_bloginfo( 'name' ) ),
				'subtitle' => 'on' === $radio_text( 'player_sub_on', 'on' ) ? $radio_text( 'player_sub', 'منبع را انتخاب کنید و فهرست خودتان را بسازید.' ) : '',
                'show_subtitle' => 'on' === $radio_text( 'player_sub_on', 'on' ),
				'limit'    => 24,
				'sources'  => true,
			)
		);
	} else {
		?>
		<div class="ty-radio-page__off">
			<p>رادیو در تنظیمات قالب خاموش است. برای روشن‌کردنش به «تنظیمات قالب ← رادیو و دی‌جی» بروید.</p>
		</div>
		<?php
	}
	?>

	<?php if ( 'on' === $radio_text( 'how_on', 'off' ) ) : ?>
	<section class="ty-radio-page__how" aria-labelledby="ty-radio-how">
		<h2 id="ty-radio-how"><?php echo esc_html( $radio_text( 'how_title', 'راهنمای رادیو' ) ); ?></h2>
		<ol class="ty-radio-page__steps">
		<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
			<?php if ( 'on' === $radio_text( 'step_' . $i . '_on', 'off' ) ) : ?>
			<li><span class="ty-radio-page__num"><?php echo esc_html( function_exists( 'tara_fa_number' ) ? tara_fa_number( $i ) : $i ); ?></span><strong><?php echo esc_html( $radio_text( 'step_' . $i . '_title', '' ) ); ?></strong><p><?php echo esc_html( $radio_text( 'step_' . $i . '_text', '' ) ); ?></p></li>
			<?php endif; ?>
		<?php endfor; ?>
		</ol>
	</section>
	<?php endif; ?>

	<?php
	$tara_radio_taxes = array();
	if ( function_exists( 'get_terms' ) ) {
		foreach ( array( 'tara_genre', 'tara_mood' ) as $tara_taxonomy ) {
			if ( ! taxonomy_exists( $tara_taxonomy ) ) {
				continue;
			}
			$terms = get_terms(
				array(
					'taxonomy'   => $tara_taxonomy,
					'hide_empty' => true,
					'orderby'    => 'count',
					'order'      => 'DESC',
					'number'     => 12,
				)
			);
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$tara_radio_taxes[ $tara_taxonomy ] = $terms;
			}
		}
	}
	?>

	<?php if ( ! empty( $tara_radio_taxes ) ) : ?>
		<nav class="ty-radio-page__taxes" aria-label="ادامهٔ شنیدن بر اساس ژانر و حال‌وهوا">
			<?php foreach ( $tara_radio_taxes as $tara_taxonomy => $tara_terms ) : ?>
				<div class="ty-radio-page__taxgroup">
					<h2><?php echo esc_html( function_exists( 'tara_music_taxonomy_label' ) ? tara_music_taxonomy_label( $tara_taxonomy, 'plural' ) : $tara_taxonomy ); ?></h2>
					<div class="ty-radio-page__taxlinks">
						<?php foreach ( $tara_terms as $tara_term ) : ?>
							<?php
							$tara_link = get_term_link( $tara_term );
							if ( is_wp_error( $tara_link ) ) {
								continue;
							}
							?>
							<a class="ty-taxchip" href="<?php echo esc_url( $tara_link ); ?>">
								<?php echo esc_html( $tara_term->name ); ?>
								<b><?php echo esc_html( function_exists( 'tara_fa_number' ) ? tara_fa_number( (int) $tara_term->count ) : (string) $tara_term->count ); ?></b>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php
	/* متنِ برگه (اگر نوشته شده باشد) هم چاپ می‌شود تا صفحهٔ رادیو خالی نماند. */
	while ( have_posts() ) :
		the_post();
		$tara_content = trim( (string) get_the_content() );
		if ( '' !== $tara_content ) :
			?>
			<section class="ty-taxabout ty-radio-page__content"><?php the_content(); ?></section>
			<?php
		endif;
	endwhile;
	?>

</div>
<?php
get_footer();
