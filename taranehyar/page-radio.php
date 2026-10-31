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
?>
<div class="ty-archive tarlanweb_center" dir="rtl">

	<header class="ty-radio-page__head">
		<span class="ty-radio-page__eyebrow"><i class="icofont-sound-wave-alt" aria-hidden="true"></i> پخشِ پیوسته · بدونِ توقف</span>
		<h1 class="ty-radio-page__title"><?php echo esc_html( '' !== $tara_radio_page_title ? $tara_radio_page_title : 'رادیو ترانه یار' ); ?></h1>
		<p class="ty-radio-page__sub">
			رادیو خودش آهنگ انتخاب می‌کند، آن‌ها را با کراس‌فید به هم می‌چسباند و بینشان سایت را معرفی می‌کند.
			هر چه بخواهید به صف اضافه کنید، در همین مرورگر ذخیره می‌شود.
		</p>
	</header>

	<?php
	if ( function_exists( 'tara_radio_box' ) && function_exists( 'tara_radio_on' ) && tara_radio_on() ) {
		echo tara_radio_box( // phpcs:ignore WordPress.Security.EscapeOutput -- مارکاپِ همین قالب
			array(
				'context'  => 'page',
				'title'    => 'ایستگاهِ زندهٔ ' . get_bloginfo( 'name' ),
				'subtitle' => 'منبع را عوض کنید، طولِ میکس را بکشید و فهرستِ خودتان را بسازید.',
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

	<section class="ty-radio-page__how" aria-labelledby="ty-radio-how">
		<h2 id="ty-radio-how">میکس چطور ساخته می‌شود؟</h2>
		<ol class="ty-radio-page__steps">
			<li>
				<span class="ty-radio-page__num">۱</span>
				<strong>دو دِک، یک خروجی</strong>
				<p>دو پخش‌کنندهٔ هم‌زمان در مرورگرِ شما کار می‌کنند؛ یکی کم‌صدا می‌شود و دیگری هم‌زمان بلند می‌شود. هیچ‌وقت سکوتِ بین دو آهنگ نمی‌شنوید.</p>
			</li>
			<li>
				<span class="ty-radio-page__num">۲</span>
				<strong>تنظیمِ سرعت و باس</strong>
				<p>آهنگِ تازه کمی کندتر وارد می‌شود و باسش اول کم است؛ درست مثل دی‌جی که پیچ را می‌چرخاند. بعد از چند ثانیه همه‌چیز سر جای خودش می‌نشیند.</p>
			</li>
			<li>
				<span class="ty-radio-page__num">۳</span>
				<strong>معرفیِ صوتی</strong>
				<p>هر چند آهنگ، یک جملهٔ کوتاه دربارهٔ سایت پخش می‌شود و موسیقی زیرش کم می‌شود تا حرف شنیده شود.</p>
			</li>
			<li>
				<span class="ty-radio-page__num">۴</span>
				<strong>هیچ‌وقت تمام نمی‌شود</strong>
				<p>وقتی صف به آخر برسد، رادیو از سرور آهنگ‌های تازه می‌گیرد؛ منبع را هم می‌توانید بین «جدیدترین»، «پربازدیدترین»، «داغ» و «تصادفی» عوض کنید.</p>
			</li>
		</ol>
	</section>

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
