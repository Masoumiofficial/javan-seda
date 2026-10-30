<?php
/**
 * رادیو ترانه یار — مارکاپ (نسخهٔ ۳.۹.۰)
 * =========================================================================
 * همهٔ عناصر ظاهریِ رادیو/دی‌جی این‌جاست تا داده (`inc/tara-radio.php`) و
 * نمایش از هم جدا بمانند. سه جا از این مارکاپ استفاده می‌کنند:
 *
 *   ۱) صفحهٔ اصلی            → tara_radio_home_section()
 *   ۲) آرشیو ژانر/حال‌وهوا   → tara_radio_box( array( 'context' => 'tax' ) )
 *   ۳) صفحهٔ «رادیو» و شورت‌کد → tara_radio_box( array( 'context' => 'page' ) )
 *
 * نکتهٔ مهم دربارهٔ دسترس‌پذیری: همهٔ دکمه‌ها `type="button"` دارند، هر
 * کنترل برچسب دارد و فهرست صف پیش از اجرای JS هم پر است؛ اگر جاوااسکریپت
 * نباشد، متن و فهرست آهنگ‌ها همچنان دیده می‌شوند.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_radio_uid' ) ) {
	/** شناسهٔ یگانه برای هر نمونه از کارت رادیو. */
	function tara_radio_uid() {
		static $n = 0;
		$n++;
		return 'ty-radio-' . $n;
	}
}

if ( ! function_exists( 'tara_radio_fa' ) ) {
	/**
	 * عددِ فارسی با پشتیبان.
	 *
	 * @param int|float $n عدد.
	 * @return string
	 */
	function tara_radio_fa( $n ) {
		if ( function_exists( 'tara_fa_number' ) ) {
			return tara_fa_number( $n );
		}
		if ( function_exists( 'tara_number_fa' ) ) {
			return tara_number_fa( $n );
		}
		return (string) $n;
	}
}

if ( ! function_exists( 'tara_radio_clock' ) ) {
	/**
	 * ثانیه → «م:ثث» با رقم فارسی.
	 *
	 * @param int $seconds ثانیه.
	 * @return string
	 */
	function tara_radio_clock( $seconds ) {
		$seconds = max( 0, (int) $seconds );
		$m       = (int) floor( $seconds / 60 );
		$s       = $seconds % 60;
		return tara_radio_fa( $m ) . ':' . str_pad( (string) $s, 2, '0', STR_PAD_LEFT );
	}
}

if ( ! function_exists( 'tara_radio_context' ) ) {
	/**
	 * زمینهٔ جاری را از صفحه تشخیص می‌دهد.
	 *
	 * @return array{taxonomy:string,term_id:int,term_name:string}
	 */
	function tara_radio_context() {
		$out = array(
			'taxonomy'  => '',
			'term_id'   => 0,
			'term_name' => '',
		);

		if ( function_exists( 'is_tax' ) && ( is_tax( 'tara_genre' ) || is_tax( 'tara_mood' ) ) ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$out['taxonomy']  = (string) $term->taxonomy;
				$out['term_id']   = (int) $term->term_id;
				$out['term_name'] = (string) $term->name;
			}
		}

		return $out;
	}
}

if ( ! function_exists( 'tara_radio_queue_rows' ) ) {
	/**
	 * ردیف‌های صف — نسخهٔ سمت سرور (پیش از اجرای JS).
	 *
	 * @param array $tracks آهنگ‌ها.
	 * @param int   $limit  سقف.
	 * @return string
	 */
	function tara_radio_queue_rows( $tracks, $limit = 8 ) {
		$out = '';
		$i   = 0;

		foreach ( array_slice( (array) $tracks, 0, max( 1, (int) $limit ) ) as $track ) {
			if ( empty( $track['src'] ) ) {
				continue;
			}
			$i++;
			$out .= '<li class="ty-radio__row" data-radio-id="' . esc_attr( (int) $track['id'] ) . '">';
			$out .= '<button type="button" class="ty-radio__rowplay" data-radio-play="' . esc_attr( (int) $track['id'] ) . '" aria-label="پخش ' . esc_attr( (string) $track['title'] ) . '">';
			$out .= '<i class="icofont-play-alt-1" aria-hidden="true"></i></button>';
			$out .= '<img class="ty-radio__rowart" src="' . esc_url( (string) $track['cover'] ) . '" width="44" height="44" alt="" loading="lazy">';
			$out .= '<span class="ty-radio__rowmeta"><strong>' . esc_html( (string) $track['title'] ) . '</strong><em>' . esc_html( (string) $track['artist'] ) . '</em></span>';
			$out .= '<span class="ty-radio__rownum">' . esc_html( tara_radio_fa( $i ) ) . '</span>';
			$out .= '</li>';
		}

		return $out;
	}
}

if ( ! function_exists( 'tara_radio_box' ) ) {
	/**
	 * کارتِ اصلیِ رادیو/دی‌جی.
	 *
	 * چیدمانِ تازه (۳.۹.۱) — «کم‌حجم در نگاهِ اول، کامل در صورتِ نیاز»:
	 *   • همیشه دیده می‌شود: کاور، نام آهنگ/خواننده، نوارِ زمان، سه دکمهٔ
	 *     اصلی (قبلی/پخش/بعدی) و دکمهٔ «تنظیماتِ دی‌جی».
	 *   • داخلِ «تنظیماتِ دی‌جی» (details): طولِ میکس، شدت، منبعِ آهنگ‌ها،
	 *     فهرستِ من و خروجیِ جلسه. در صفحهٔ رادیو پیش‌فرض باز است.
	 *   • صفحهٔ اصلی: حالتِ `compact` — سربرگِ کوتاه، بدونِ آمار و بدونِ
	 *     متنِ توضیح؛ صف هم کوتاه‌تر است تا اسکرولِ گوشی کم شود.
	 *
	 * @param array $args context|title|subtitle|taxonomy|term_id|mode|tracks|limit|class|sources|compact|featured.
	 * @return string
	 */
	function tara_radio_box( $args = array() ) {
		if ( ! tara_radio_on() ) {
			return '';
		}

		$ctx  = tara_radio_context();
		$args = wp_parse_args(
			$args,
			array(
				'context'  => 'page',
				'title'    => '',
				'subtitle' => '',
				'taxonomy' => $ctx['taxonomy'],
				'term_id'  => $ctx['term_id'],
				'mode'     => (string) tara_radio_option( 'mode', 'mixed' ),
				'tracks'   => array(),
				'limit'    => (int) tara_radio_option( 'limit', 20 ),
				'class'      => '',
				'sources'    => false,
				'hide_empty' => false,
				'compact'    => null,
				'featured'   => false,
				'adv_open'   => null,
			)
		);

		if ( 'artist' === $args['context'] ) {
			$artist_tax = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : '';
			$artist_term = ( $artist_tax && $args['taxonomy'] === $artist_tax && (int) $args['term_id'] > 0 ) ? get_term( (int) $args['term_id'], $artist_tax ) : null;
			if ( ! $artist_term || is_wp_error( $artist_term ) || $artist_term->taxonomy !== $artist_tax ) { return ''; }
		}

		$modes = tara_radio_modes();
		if ( 'artist' === $args['context'] ) {
			$modes = array_intersect_key( $modes, array_flip( array( 'new', 'views', 'random' ) ) );
		}
		if ( ! isset( $modes[ $args['mode'] ] ) ) {
			$args['mode'] = 'artist' === $args['context'] ? 'new' : 'mixed';
		}

		/* صفحهٔ اصلی به‌طورِ پیش‌فرض فشرده است؛ صفحهٔ رادیو کامل. */
		$compact = ( null === $args['compact'] ) ? ( 'home' === $args['context'] ) : (bool) $args['compact'];

		/* دکمهٔ «تنظیماتِ دی‌جی» فقط در صفحهٔ رادیو باز است. */
		$adv_open = ( null === $args['adv_open'] ) ? ( 'page' === $args['context'] ) : (bool) $args['adv_open'];

		if ( empty( $args['tracks'] ) ) {
			$args['tracks'] = tara_radio_tracks(
				array(
					'taxonomy' => (string) $args['taxonomy'],
					'term_id'  => (int) $args['term_id'],
					'mode'     => (string) $args['mode'],
					'limit'    => (int) $args['limit'],
					'pool'     => ! empty( $args['featured'] ) ? 'featured' : '',
				)
			);
		}

		$tracks = array_values( array_filter( (array) $args['tracks'] ) );
		if ( empty( $tracks ) && ! empty( $args['hide_empty'] ) ) {
			return '';
		}
		$uid    = tara_radio_uid();
		$first  = ! empty( $tracks ) ? $tracks[0] : array();
		$cover  = ! empty( $first['cover'] ) ? (string) $first['cover'] : get_template_directory_uri() . '/images/nomusics.jpg';
		$site   = (string) get_bloginfo( 'name' );

		$title = '' !== $args['title'] ? $args['title'] : ( '' !== $ctx['term_name'] ? 'میکس زندهٔ «' . $ctx['term_name'] . '»' : 'رادیو ' . $site );
		$sub   = '' !== $args['subtitle'] ? $args['subtitle'] : 'دی‌جی خودکارِ ' . $site . ': آهنگ‌ها با کراس‌فید و هم‌ترازیِ سرعت به هم می‌چسبند؛ بینشان هم سایت معرفی می‌شود.';

		$payload = array(
			'tracks'   => $tracks,
			'mode'     => (string) $args['mode'],
			'tax'      => (string) $args['taxonomy'],
			'term'     => (int) $args['term_id'],
			'limit'    => max( 4, min( 40, (int) $args['limit'] ) ),
			'featured' => ! empty( $args['featured'] ),
		);
		$json = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP );
		if ( ! is_string( $json ) ) {
			$json = '{}';
		}

		$classes = 'ty-radio ty-radio--' . sanitize_html_class( (string) $args['context'] );
		if ( $compact ) {
			$classes .= ' ty-radio--compact';
		}
		if ( '' !== (string) $args['class'] ) {
			$classes .= ' ' . sanitize_html_class( (string) $args['class'] );
		}

		$xfade = (int) tara_radio_option( 'xfade', 6 );
		$xfade = max( 0, min( 16, $xfade ) );

		$queue_rows = $compact ? 5 : 8;

		ob_start();
		?>
		<section class="<?php echo esc_attr( $classes ); ?>" id="<?php echo esc_attr( $uid ); ?>"
			data-tara-radio
			data-radio-context="<?php echo esc_attr( (string) $args['context'] ); ?>"
			data-radio-tax="<?php echo esc_attr( (string) $args['taxonomy'] ); ?>"
			data-radio-term="<?php echo esc_attr( (int) $args['term_id'] ); ?>"
			data-radio-mode="<?php echo esc_attr( (string) $args['mode'] ); ?>"
			data-radio-featured="<?php echo ! empty( $args['featured'] ) ? '1' : '0'; ?>"
			dir="rtl" <?php echo $compact ? 'aria-label="رادیو ' . esc_attr( $site ) . '"' : 'aria-labelledby="' . esc_attr( $uid ) . '-title"'; ?>>

			<script type="application/json" class="ty-radio__data"><?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput -- JSONِ ساختهٔ خودمان، HEX_TAG شده ?></script>

			<span class="ty-radio__aura" aria-hidden="true"></span>
			<span class="ty-radio__scan" aria-hidden="true"></span>

			<?php
			/* در حالتِ فشرده (صفحهٔ اصلی) سربرگِ داخلی چاپ نمی‌شود: سربرگِ
			   بیرونیِ بخش («رادیو …» + نشانِ «منتخبِ ویژه») همین کار را می‌کند
			   و دو <h2> پشتِ‌سرِ‌هم هم برای خوانندهٔ صفحه و هم برای سئو بد بود. */
			if ( ! $compact ) :
				?>
				<header class="ty-radio__head">
					<div class="ty-radio__ident">
						<span class="ty-radio__live">
							<span class="ty-radio__pulse" aria-hidden="true"></span>
							رادیو <?php echo esc_html( $site ); ?>
							<em>زنده</em>
						</span>
						<h2 class="ty-radio__title" id="<?php echo esc_attr( $uid ); ?>-title"><?php echo esc_html( $title ); ?></h2>
					</div>
					<ul class="ty-radio__meter">
						<li><span>جلسه</span><strong data-radio-session>۰:۰۰</strong></li>
						<li><span>میکس</span><strong data-radio-mixed>۰</strong></li>
						<li><span>در صف</span><strong data-radio-queued><?php echo esc_html( tara_radio_fa( count( $tracks ) ) ); ?></strong></li>
					</ul>
				</header>
			<?php endif; ?>

			<?php if ( ! $compact ) : ?>
				<p class="ty-radio__sub"><?php echo esc_html( $sub ); ?></p>
			<?php endif; ?>

			<div class="ty-radio__deck">
				<div class="ty-radio__artwrap">
					<span class="ty-radio__halo" aria-hidden="true"></span>
					<img class="ty-radio__art" src="<?php echo esc_url( $cover ); ?>" width="320" height="320" alt="" data-radio-cover>
					<span class="ty-radio__disc" aria-hidden="true"></span>
					<span class="ty-radio__sheen" aria-hidden="true"></span>
					<div class="ty-radio__vu" data-radio-vu aria-hidden="true">
						<?php for ( $b = 0; $b < 22; $b++ ) : ?>
							<i style="--i:<?php echo esc_attr( (string) $b ); ?>"></i>
						<?php endfor; ?>
					</div>
					<button type="button" class="ty-radio__start" data-radio-toggle aria-label="شروع پخش رادیو">
						<span class="ty-radio__start-ico"><i class="icofont-play-alt-1" aria-hidden="true"></i><i class="icofont-pause" aria-hidden="true"></i></span>
						<span class="ty-radio__start-txt" data-radio-starttxt>پخش زنده</span>
					</button>
				</div>

				<div class="ty-radio__console">
					<div class="ty-radio__now">
						<span class="ty-radio__kicker" data-radio-state>آمادهٔ پخش</span>
						<a class="ty-radio__song" href="<?php echo esc_url( ! empty( $first['link'] ) ? (string) $first['link'] : home_url( '/' ) ); ?>" data-radio-link>
							<span data-radio-title><?php echo esc_html( ! empty( $first['title'] ) ? (string) $first['title'] : 'آهنگ‌ها آماده‌اند' ); ?></span>
						</a>
						<span class="ty-radio__artist" data-radio-artist><?php echo esc_html( ! empty( $first['artist'] ) ? (string) $first['artist'] : '' ); ?></span>
					</div>

					<?php
					/* نوارِ زمان: در راست‌به‌چپ هم «خط پر شدن» و هم «دستهٔ» نوار
					   از راست به چپ حرکت می‌کنند (dir="rtl" روی خودِ range). */
					?>
					<div class="ty-radio__seek">
						<div class="ty-radio__rail" aria-hidden="true">
							<span class="ty-radio__buffer" data-radio-buffer></span>
							<span class="ty-radio__fill" data-radio-fill></span>
						</div>
						<input type="range" class="ty-radio__range ty-radio__range--seek" data-radio-seek min="0" max="1000" step="1" value="0" aria-label="جای پخش" dir="rtl">
						<span class="ty-radio__time" data-radio-time dir="ltr">۰:۰۰ / ۰:۰۰</span>
					</div>

					<div class="ty-radio__transport">
						<button type="button" class="ty-radio__tbtn" data-radio-prev aria-label="قطعهٔ قبل"><i class="icofont-caret-right" aria-hidden="true"></i></button>
						<button type="button" class="ty-radio__tbtn ty-radio__tbtn--main" data-radio-toggle aria-label="پخش / توقف">
							<i class="icofont-play-alt-1" aria-hidden="true"></i>
							<i class="icofont-pause" aria-hidden="true"></i>
							<span class="ty-radio__spin" aria-hidden="true"></span>
						</button>
						<button type="button" class="ty-radio__tbtn" data-radio-next aria-label="قطعهٔ بعد"><i class="icofont-caret-left" aria-hidden="true"></i></button>
					</div>

					<p class="ty-radio__say" data-radio-say aria-live="polite" hidden></p>

				</div>
			</div>

			<details class="ty-radio__adv" data-radio-adv<?php echo $adv_open ? ' open' : ''; ?>>
				<summary class="ty-radio__more">
					<i class="icofont-options" aria-hidden="true"></i>
					<span>تنظیمات دی‌جی</span>
					<em>طول میکس، منبع و ابزارها</em>
				</summary>

				<div class="ty-radio__advbody">
					<div class="ty-radio__mixer">
						<label class="ty-radio__ctrl" for="<?php echo esc_attr( $uid ); ?>-xfade">
							<span class="ty-radio__ctrl-label">طول میکس</span>
							<strong class="ty-radio__ctrl-val" data-radio-xfade-val><?php echo esc_html( tara_radio_fa( $xfade ) . ' ثانیه' ); ?></strong>
							<input type="range" id="<?php echo esc_attr( $uid ); ?>-xfade" class="ty-radio__range ty-radio__range--mix" data-radio-xfade min="0" max="16" step="1" value="<?php echo esc_attr( (string) $xfade ); ?>" dir="ltr">
						</label>
						<div class="ty-radio__power" role="group" aria-label="شدت میکس">
							<button type="button" data-radio-power="calm" aria-pressed="false">آرام</button>
							<button type="button" data-radio-power="warm" aria-pressed="true">دی‌جی</button>
							<button type="button" data-radio-power="club" aria-pressed="false">باشگاهی</button>
						</div>
					</div>

					<?php if ( ! empty( $args['sources'] ) ) : ?>
						<div class="ty-radio__sources" role="group" aria-label="منبع آهنگ‌های رادیو">
							<?php foreach ( $modes as $mode_key => $mode_label ) : ?>
								<button type="button" class="ty-radio__source<?php echo ( $mode_key === $args['mode'] ) ? ' is-active' : ''; ?>"
									data-radio-source="<?php echo esc_attr( $mode_key ); ?>" aria-pressed="<?php echo ( $mode_key === $args['mode'] ) ? 'true' : 'false'; ?>">
									<?php echo esc_html( $mode_label ); ?>
								</button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<div class="ty-radio__tools">
						<?php
						/* v3.10.1 — «فهرست من» فقط وقتی معنی دارد که دکمهٔ
						   «افزودن به رادیو» هم به کاربر نشان داده شود. */
						if ( 'artist' !== $args['context'] && ( ! function_exists( 'tara_radio_user_add_on' ) || tara_radio_user_add_on() ) ) :
							?>
						<button type="button" class="ty-radio__tool" data-radio-mine aria-expanded="false" aria-controls="<?php echo esc_attr( $uid ); ?>-mine">
							<i class="icofont-heart-alt" aria-hidden="true"></i>
							فهرست من <b data-radio-mine-count>۰</b>
						</button>
						<?php endif; ?>
						<button type="button" class="ty-radio__tool" data-radio-shuffle>
							<i class="icofont-random" aria-hidden="true"></i>
							شافل
						</button>
						<button type="button" class="ty-radio__tool" data-radio-export>
							<i class="icofont-download" aria-hidden="true"></i>
							فهرست جلسه
						</button>
					</div>
				</div>
			</details>

			<div class="ty-radio__timeline">
				<header class="ty-radio__tlhead">
					<h3>صف میکس</h3>
					<span class="ty-radio__tlmeta" data-radio-queue-meta><?php echo esc_html( tara_radio_fa( count( $tracks ) ) . ' آهنگ در نوبت' ); ?></span>
				</header>
				<ol class="ty-radio__queue ty-list--collapsed" data-radio-queue>
					<?php echo tara_radio_queue_rows( $tracks, $queue_rows ); // phpcs:ignore WordPress.Security.EscapeOutput -- خروجی همین تابع، escape شده ?>
				</ol>
				<button type="button" class="ty-list-more" data-tara-radio-more aria-expanded="false" hidden>نمایش بیشتر</button>
				<p class="ty-radio__empty" data-radio-empty<?php echo empty( $tracks ) ? '' : ' hidden'; ?>>
					برای این بخش هنوز آهنگی با فایل صوتی ثبت نشده است.
				</p>
			</div>

			<div class="ty-radio__mine" id="<?php echo esc_attr( $uid ); ?>-mine" data-radio-mine-panel hidden>
				<header class="ty-radio__minehead">
					<h3>فهرست من</h3>
					<button type="button" class="ty-radio__mineclear" data-radio-mine-clear>پاک‌کردن</button>
				</header>
				<ol class="ty-radio__minelist" data-radio-mine-list></ol>
				<p class="ty-radio__mineempty" data-radio-mine-empty>هنوز چیزی اضافه نکرده‌اید. روی دکمهٔ «افزودن به رادیو» در صفحهٔ هر آهنگ بزنید.</p>
			</div>

			<div class="ty-radio__audio" data-radio-audio hidden aria-hidden="true"></div>

			<p class="ty-radio__toast" data-radio-toast role="status" aria-live="polite" hidden></p>
		</section>
		<?php

		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'tara_radio_home_section' ) ) {
	/**
	 * بخشِ «رادیو» در صفحهٔ اصلی.
	 *
	 * از ۳.۹.۱: فقط آهنگ‌هایی که در «بخش ویژه» (اسلایدرِ ویژه) گذاشته شده‌اند
	 * وارد صفِ خانه می‌شوند و کارت فشرده است (بدونِ آمار و متنِ بلند) تا
	 * در گوشی فضای کمی بگیرد. اگر هیچ آهنگِ ویژه‌ای نبود، رادیو با همان
	 * منبعِ عمومی کار می‌کند تا بخش خالی نماند.
	 *
	 * @return string
	 */
	function tara_radio_home_section() {
		if ( ! tara_radio_on() || 'on' !== (string) tara_radio_option( 'home', 'on' ) ) {
			return '';
		}

		$featured_only = ( 'on' === (string) tara_radio_option( 'home_featured', 'on' ) );
		$pool          = $featured_only ? 'featured' : '';
		$mode          = (string) tara_radio_option( 'mode', 'mixed' );

		$tracks = tara_radio_tracks(
			array(
				'mode'  => $mode,
				'limit' => 12,
				'pool'  => $pool,
			)
		);
		if ( empty( $tracks ) ) {
			return '';
		}

		$page = tara_radio_page_url();

		ob_start();
		?>
		<section class="ty-radio-home tarlanweb_center" dir="rtl">
			<div class="ty-radio-home__head">
				<h2 class="ty-radio-home__title">
					<i class="icofont-sound-wave-alt" aria-hidden="true"></i>
					رادیو <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
					<span class="ty-radio-home__flag"><?php echo $featured_only ? 'منتخبِ ویژه' : 'زنده'; ?></span>
				</h2>
				<span class="ty-radio-home__live" aria-hidden="true"><span class="ty-radio__pulse"></span>زنده</span>
				<?php if ( '' !== $page ) : ?>
					<a class="ty-radio-home__more" href="<?php echo esc_url( $page ); ?>">صفحهٔ رادیو <i class="icofont-thin-left" aria-hidden="true"></i></a>
				<?php endif; ?>
			</div>
			<?php
			echo tara_radio_box( // phpcs:ignore WordPress.Security.EscapeOutput -- مارکاپِ همین قالب
				array(
					'context'    => 'home',
					'title'      => 'ایستگاهِ همیشه‌روشن',
					'limit'      => 12,
					'tracks'     => $tracks,
					'compact'    => true,
					'featured'   => (bool) $pool,
					'adv_open'   => false,
					'hide_empty' => true,
				)
			);
			?>
		</section>
		<?php

		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'tara_radio_page_url' ) ) {
	/**
	 * آدرس صفحه‌ای که قالبِ «رادیو» دارد (اگر ساخته شده باشد).
	 *
	 * @return string
	 */
	function tara_radio_page_url() {
		$cached = get_transient( 'tara_radio_page_url' );
		if ( is_string( $cached ) ) {
			return $cached;
		}

		$url = '';
		if ( ! function_exists( 'get_pages' ) ) {
			return '';
		}

		$pages = get_pages(
			array(
				'meta_key'    => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'  => 'page-radio.php', // phpcs:ignore WordPress.DB.SlowDBQuery
				'number'      => 1,
				'post_status' => 'publish',
			)
		);
		if ( is_array( $pages ) && ! empty( $pages ) ) {
			$url = (string) get_permalink( $pages[0] );
		}

		set_transient( 'tara_radio_page_url', $url, HOUR_IN_SECONDS );

		return $url;
	}
}

if ( ! function_exists( 'tara_radio_shortcode' ) ) {
	/**
	 * شورت‌کد `[tara_radio]` — برای گذاشتن رادیو در هر برگه/نوشته.
	 *
	 * @param array $atts ویژگی‌ها.
	 * @return string
	 */
	function tara_radio_shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'title'    => '',
				'subtitle' => '',
				'mode'     => '',
				'limit'    => 0,
				'sources'  => 'yes',
				'compact'  => 'no',
				'featured' => 'no',
			),
			(array) $atts,
			'tara_radio'
		);

		return tara_radio_box(
			array(
				'context'  => 'shortcode',
				'title'    => (string) $atts['title'],
				'subtitle' => (string) $atts['subtitle'],
				'mode'     => '' !== (string) $atts['mode'] ? (string) $atts['mode'] : (string) tara_radio_option( 'mode', 'mixed' ),
				'limit'    => (int) $atts['limit'] ? (int) $atts['limit'] : (int) tara_radio_option( 'limit', 20 ),
				'sources'  => in_array( strtolower( (string) $atts['sources'] ), array( 'yes', '1', 'on', 'true' ), true ),
				'compact'  => in_array( strtolower( (string) $atts['compact'] ), array( 'yes', '1', 'on', 'true' ), true ),
				'featured' => in_array( strtolower( (string) $atts['featured'] ), array( 'yes', '1', 'on', 'true' ), true ),
			)
		);
	}
}

if ( function_exists( 'add_shortcode' ) ) {
	add_shortcode( 'tara_radio', 'tara_radio_shortcode' );
}
