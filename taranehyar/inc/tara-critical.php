<?php
/**
 * inc/tara-critical.php — «لایهٔ تضمینی» ظاهر و جست‌وجوی زنده (v3.7.6)
 * ==========================================================================
 * چرا این فایل؟ در سایت کاربر، فایل‌های `css/tara-ui.css` و `js/tara-player.js`
 * به صفحه نمی‌رسیدند (کش/ترکیب/فیلتر دارایی‌ها) و در نتیجه: کادر جست‌وجو بد
 * دیده می‌شد، شناسنامه همان کادر سفید قبلی بود، چیپ‌های ژانر/حال‌وهوا استایل
 * نداشتند، مود شب ظاهر صفحه را عوض نمی‌کرد و پلیر کار نمی‌کرد. در همهٔ این‌ها
 * فقط کدِ **درون‌خطی** اثر می‌گذاشت.
 *
 * این فایل سه کار می‌کند:
 *   ۱) یک بلوک CSS کوچک و ضروری را در `wp_head` چاپ می‌کند (همیشه اثر دارد).
 *   ۲) اگر فایل‌های CSS/JS قالب چاپ نشده باشند، خودش تگ <link>/<script> را
 *      مستقیم می‌نویسد (بدون تکرار اگر قبلاً چاپ شده باشند).
 *   ۳) جست‌وجوی زندهٔ AJAX را (بدون نیاز به افزونه) راه می‌اندازد؛ اگر افزونهٔ
 *      SearchWP فعال باشد، دست به کارش نمی‌شود.
 *
 * همهٔ این‌ها فقط در بخش عمومی سایت اجرا می‌شوند.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * ۱) CSS ضروری — درون‌خطی
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'tara_critical_css' ) ) {
	function tara_critical_css() {
		$ui = defined( 'TARANEHYAR_URI' ) ? TARANEHYAR_URI : get_template_directory_uri();
		?>
		<style id="tara-critical-css">
		/* ---------- مود شب (مستقل از متغیرهای پوسته) ---------- */
		body.night, html[data-tara-night="1"] body { background: #101014 !important; color: #ecebf3 !important; }
		body.night #header, html[data-tara-night="1"] #header { background: #17171d !important; }
		body.night #footer, html[data-tara-night="1"] #footer { background: #0b0b0e !important; }
		body.night a, html[data-tara-night="1"] a { color: #d9d6ee; }
		body.night .single_posts, body.night .songs_post, body.night .artist_box_tarlanweb,
		body.night .related_posts, body.night .single_content_box, body.night .music_box_player,
		body.night .tarlanweb_side_box, body.night .song_boxs,
		html[data-tara-night="1"] .single_posts, html[data-tara-night="1"] .songs_post,
		html[data-tara-night="1"] .artist_box_tarlanweb, html[data-tara-night="1"] .single_content_box,
		html[data-tara-night="1"] .tarlanweb_side_box, html[data-tara-night="1"] .song_boxs {
			background: #191920 !important; color: #ecebf3 !important; box-shadow: none !important;
		}
		body.night .liner, html[data-tara-night="1"] .liner { border-color: rgba(255, 255, 255, .12) !important; }
		body.night h1, body.night h2, body.night h3, body.night .songs_title_box a,
		html[data-tara-night="1"] h1, html[data-tara-night="1"] h2, html[data-tara-night="1"] h3 {
			color: #f4f2ff !important;
		}

		/* ---------- کادر جست‌وجوی سربرگ + نتایج زنده ---------- */
		/* v3.7.7 — کادر جست‌وجو: ساده‌تر و «خاص‌تر»، و هم‌رنگ با مود روشن/تاریک.
		   قبلاً کادرِ سفیدِ ثابت با دکمهٔ بنفشِ تمام‌قد بود؛ در مود شب سفید
		   می‌ماند و با سربرگ تیره تضاد داشت. حالا یک کپسول نرم است با دکمهٔ
		   دایره‌ایِ کوچک و حالت‌های رنگ جدا برای روز و شب. */
		#searches { position: relative !important; display: flex !important; align-items: center;
			gap: 2px; width: 224px; height: 36px; margin: 3px 12px 0 0; padding: 0 3px 0 0;
			border-radius: 999px; border: 1px solid rgba(17, 24, 39, .10);
			background: #f2f1f7; overflow: visible !important;
			box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7);
			transition: border-color .16s ease, background .16s ease, box-shadow .16s ease; }
		#searches:focus-within { background: #fff; border-color: rgba(109, 40, 217, .55);
			box-shadow: 0 0 0 3px rgba(109, 40, 217, .13); }
		#searches #lsds, #searches .searchfield { position: static !important; flex: 1 1 auto;
			width: auto !important; min-width: 0; height: 34px; padding: 0 14px !important;
			border: 0 !important; border-radius: 0; background: transparent !important;
			color: #16121f !important; font-size: 12.5px; text-indent: 0 !important;
			text-align: right; direction: rtl; box-shadow: none !important; }
		#searches #lsds::placeholder, #searches .searchfield::placeholder {
			color: #8b8899 !important; opacity: 1; font-weight: 500; }
		#searches button { flex: 0 0 30px; width: 30px; height: 30px; line-height: 30px;
			float: none; border: 0; border-radius: 50%;
			background: linear-gradient(135deg, #4c1d95, #6d28d9) !important; color: #fff !important;
			font-size: 13px; cursor: pointer; box-shadow: 0 4px 10px -6px rgba(76, 29, 149, .9); }
		#searches button:hover { background: #4c1d95 !important; }
		/* مود شب: شیشهٔ نیمه‌شفاف روی سربرگ تیره */
		body.night #searches, html[data-tara-night="1"] #searches {
			border-color: rgba(255, 255, 255, .14) !important;
			background: rgba(255, 255, 255, .07) !important;
			box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
		body.night #searches:focus-within, html[data-tara-night="1"] #searches:focus-within {
			background: rgba(255, 255, 255, .12) !important;
			border-color: rgba(196, 181, 253, .6) !important;
			box-shadow: 0 0 0 3px rgba(167, 139, 250, .16); }
		body.night #searches #lsds, body.night #searches .searchfield,
		html[data-tara-night="1"] #searches #lsds, html[data-tara-night="1"] #searches .searchfield {
			background: transparent !important; color: #f4f2ff !important; }
		body.night #searches #lsds::placeholder, body.night #searches .searchfield::placeholder,
		html[data-tara-night="1"] #searches #lsds::placeholder,
		html[data-tara-night="1"] #searches .searchfield::placeholder {
			color: rgba(255, 255, 255, .55) !important; }
		@media (max-width: 600px) { #searches { width: 176px; } }

		#tara-search-results { position: absolute; z-index: 100000; top: calc(100% + 10px);
			inset-inline-start: 0; inset-inline-end: auto; width: 330px; max-width: 88vw;
			display: none; overflow: hidden; border: 1px solid #e6e4ef;
			border-radius: 16px; background: #fff; box-shadow: 0 24px 44px -22px rgba(17, 16, 28, .55);
			direction: rtl; text-align: right; }
		body.night #tara-search-results, html[data-tara-night="1"] #tara-search-results {
			border-color: rgba(255, 255, 255, .12); background: #1c1b24;
			box-shadow: 0 24px 44px -22px rgba(0, 0, 0, .8); }
		body.night #tara-search-results .tara-sr__head,
		html[data-tara-night="1"] #tara-search-results .tara-sr__head {
			border-color: rgba(255, 255, 255, .08); color: rgba(255, 255, 255, .6); }
		body.night #tara-search-results a.tara-sr__row,
		html[data-tara-night="1"] #tara-search-results a.tara-sr__row { color: #eee9ff; }
		body.night #tara-search-results a.tara-sr__row:hover,
		html[data-tara-night="1"] #tara-search-results a.tara-sr__row:hover {
			background: rgba(255, 255, 255, .06); }
		#tara-search-results.is-open { display: block; }
		#tara-search-results .tara-sr__head { padding: 8px 12px; border-bottom: 1px solid #f1eff8;
			font-size: 11px; font-weight: 700; color: #6b6a7b; }
		#tara-search-results a.tara-sr__row { display: flex; gap: 9px; align-items: center;
			padding: 8px 10px; text-decoration: none; color: #1d1b29; }
		#tara-search-results a.tara-sr__row:hover { background: #faf8ff; }
		#tara-search-results .tara-sr__row img { width: 40px; height: 40px; border-radius: 10px;
			object-fit: cover; flex: 0 0 40px; background: #f1ecff; }
		#tara-search-results .tara-sr__t { display: block; font-size: 12.5px; font-weight: 700;
			line-height: 1.6; }
		#tara-search-results .tara-sr__a { display: block; font-size: 11px; color: #7a7790; }
		#tara-search-results .tara-sr__empty { padding: 12px; font-size: 12px; color: #56536b; }
		#tara-search-results a.tara-sr__row.is-active,
		#tara-search-results a.tara-sr__row:hover { background: #f4f1ff; }
		body.night #tara-search-results a.tara-sr__row.is-active,
		html[data-tara-night="1"] #tara-search-results a.tara-sr__row.is-active { background: rgba(255, 255, 255, .10); }
		#tara-search-results .tara-sr__a { color: #615e78; }
		body.night #tara-search-results .tara-sr__a,
		body.night #tara-search-results .tara-sr__empty,
		html[data-tara-night="1"] #tara-search-results .tara-sr__a,
		html[data-tara-night="1"] #tara-search-results .tara-sr__empty { color: #b9b6cd; }

		/* v3.8.3 — اسکلتِ لودینگ: تا رسیدنِ پاسخ، ردیف‌های خاکستریِ متحرک
		   با همان ارتفاعِ نتیجهٔ واقعی، تا کادر نپرد و کاربر بداند چیزی در راه است.
		   با prefers-reduced-motion انیمیشن خاموش می‌شود. */
		#tara-search-results .tara-sr__row.is-skeleton { pointer-events: none; }
		#tara-search-results .tara-sr__sk-lines { display: grid; gap: 7px; flex: 1 1 auto; }
		#tara-search-results .tara-sr__sk { display: block; height: 11px; border-radius: 6px;
			background: linear-gradient(90deg, #eeeaf6 0%, #f7f5fc 40%, #eeeaf6 80%);
			background-size: 220% 100%; animation: tara-sk 1.15s linear infinite; }
		#tara-search-results .tara-sr__sk--img { width: 40px; height: 40px; flex: 0 0 40px; border-radius: 10px; }
		#tara-search-results .tara-sr__sk--sm { width: 55%; height: 9px; }
		body.night #tara-search-results .tara-sr__sk,
		html[data-tara-night="1"] #tara-search-results .tara-sr__sk {
			background: linear-gradient(90deg, rgba(255,255,255,.08) 0%, rgba(255,255,255,.16) 40%, rgba(255,255,255,.08) 80%);
			background-size: 220% 100%; }
		@keyframes tara-sk { from { background-position: 120% 0; } to { background-position: -120% 0; } }
		@media (prefers-reduced-motion: reduce) {
			#tara-search-results .tara-sr__sk { animation: none !important; }
		}

		/* ---------- شناسنامهٔ آهنگ: بدون کادر سفید ---------- */
		.ab-credits.ab-credits { border: 0 !important; border-radius: 0 !important;
			background: transparent !important; box-shadow: none !important; padding: 0 !important; }
		.ab-credits.ab-credits .ab-credit { background: transparent !important; }
		.ab-credits.ab-credits .ab-credit__k { color: #6b7280; font-size: 11px; }
		.ab-credits.ab-credits .ab-credit__v { color: #16121f; }
		body.night .ab-credits.ab-credits .ab-credit__v { color: #f2f0ff; }
		body.night .ab-credits.ab-credits .ab-credit__k { color: rgba(255, 255, 255, .62); }

		/* ---------- چیپ‌های ژانر/حال‌وهوا و ردیف شناسه ---------- */
		.ty-single-meta { display: flex !important; flex-wrap: wrap; gap: 10px 18px;
			align-items: center; margin: 10px 0 2px; padding: 0 !important;
			border: 0 !important; background: transparent !important; }
		.ty-chips { display: inline-flex; flex-wrap: wrap; align-items: center; gap: 6px; }
		.ty-chip { display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px;
			border-radius: 999px; border: 1px solid #e3dcf7; background: #f7f5ff;
			color: #4c1d95 !important; font-size: 11.5px; font-weight: 700;
			line-height: 1.7; text-decoration: none !important; }
		.ty-chip:hover { background: #efe9ff; }
		body.night .ty-chip { border-color: #3a3550; background: rgba(255, 255, 255, .07); color: #d9d3ff !important; }
		.ty-single-meta .ty-chip { font-size: 12px; padding: 5px 13px; }
		.tarlanweb_post_ft { float: none !important; display: inline-flex; align-items: center;
			gap: 5px; height: auto; line-height: 1.9; margin: 0 0 0 16px; font-size: 12px; }
		header .tarlanweb_post_ft i { font-size: 14px; color: #6d28d9; }

		/* ---------- دکمهٔ «بالا» ---------- */
		.top_page { display: flex !important; align-items: center; justify-content: center;
			opacity: 0; visibility: hidden; transform: translateY(6px);
			transition: opacity .2s ease, transform .2s ease, visibility .2s; }
		.top_page.is-shown { opacity: 1; visibility: visible; transform: none; }

		/* ---------- پلیر: پیام خطای خوانا ---------- */
		.ty-player__error { color: #b91c1c; font-size: 12px; }
		</style>
		<?php
	}
	add_action( 'wp_head', 'tara_critical_css', 100 );
}

/* -------------------------------------------------------------------------
 * ۲) نگهبان دارایی‌ها — اگر enqueue چاپ نشد، مستقیم می‌نویسیم
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'tara_assets_guard_css' ) ) {
	function tara_assets_guard_css() {
		if ( is_admin() ) {
			return;
		}

		/* v3.7.8 — همان فهرستِ وابسته‌به‌صفحهٔ `tara_asset_styles()` تا نگهبان با
		   enqueue هم‌گام بماند؛ وگرنه CSSهایی که عمداً بار نمی‌شوند، همین‌جا
		   دوباره چاپ می‌شدند و بهینه‌سازی بی‌اثر می‌ماند. */
		$css = function_exists( 'tara_asset_styles' )
			? tara_asset_styles()
			: array(
				'taranehyar-ui'     => '/css/tara-ui.css',
				'taranehyar-home'   => '/css/tara-home.css',
				'taranehyar-artist' => '/css/tara-artist.css',
			);

		foreach ( $css as $handle => $rel ) {
			if ( wp_style_is( $handle, 'done' ) ) {
				continue;
			}
			$file = TARANEHYAR_DIR . $rel;
			if ( ! file_exists( $file ) ) {
				continue;
			}
			$ver = TARANEHYAR_VERSION . '.' . filemtime( $file );
			echo '<link rel="stylesheet" id="' . esc_attr( $handle . '-direct' ) . '" href="'
				. esc_url( TARANEHYAR_URI . $rel . '?ver=' . $ver ) . '" media="all" />' . "\n";
		}
	}
	add_action( 'wp_head', 'tara_assets_guard_css', 98 );
}

if ( ! function_exists( 'tara_assets_guard_js' ) ) {
	function tara_assets_guard_js() {
		if ( is_admin() || wp_script_is( 'taranehyar-player', 'done' ) ) {
			return;
		}
		$file = TARANEHYAR_DIR . '/js/tara-player.js';
		if ( ! file_exists( $file ) ) {
			return;
		}
		$ver = TARANEHYAR_VERSION . '.' . filemtime( $file );
		echo '<script id="taranehyar-player-direct" src="'
			. esc_url( TARANEHYAR_URI . '/js/tara-player.js?ver=' . $ver ) . '"></script>' . "\n";
	}
	add_action( 'wp_footer', 'tara_assets_guard_js', 97 );
}

/* -------------------------------------------------------------------------
 * ۳) جست‌وجوی زندهٔ AJAX (بدون افزونه)
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'tara_ajax_search' ) ) {
	/**
	 * خروجی JSON جست‌وجو برای دراپ‌داون سربرگ.
	 */
	function tara_ajax_search() {
		check_ajax_referer( 'tara_search', 'nonce', false );

		$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$term = trim( $term );

		if ( mb_strlen( $term ) < 2 ) {
			wp_send_json_success( array( 'items' => array(), 'more' => '' ) );
		}

		$query = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				's'                   => $term,
				'posts_per_page'      => 8,
				'no_found_rows'       => false,
				'ignore_sticky_posts' => true,
			)
		);

		$items = array();
		foreach ( (array) $query->posts as $post ) {
			$cover = function_exists( 'tara_track_cover' ) ? tara_track_cover( $post->ID, 'thumb3' ) : '';
			$artist = '';
			if ( function_exists( 'tara_music_field' ) ) {
				$artist = (string) tara_music_field( 'artist', $post->ID );
			}
			$items[] = array(
				'title'  => get_the_title( $post ),
				'url'    => get_permalink( $post ),
				'artist' => $artist,
				'cover'  => $cover,
			);
		}

		wp_send_json_success(
			array(
				'items' => $items,
				'more'  => get_search_link( $term ),
			)
		);
	}
	add_action( 'wp_ajax_tara_search', 'tara_ajax_search' );
	add_action( 'wp_ajax_nopriv_tara_search', 'tara_ajax_search' );
}

if ( ! function_exists( 'tara_search_inline_js' ) ) {
	/**
	 * اسکریپت درون‌خطی جست‌وجوی زنده.
	 *
	 * اگر افزونهٔ SearchWP Live Ajax Search فعال باشد (window.SearchWP)، این
	 * اسکریپت هیچ کاری نمی‌کند تا دو دراپ‌داون هم‌زمان باز نشود.
	 */
	function tara_search_inline_js() {
		if ( is_admin() ) {
			return;
		}

		$endpoint = admin_url( 'admin-ajax.php' );
		$nonce    = wp_create_nonce( 'tara_search' );
		?>
		<script id="tara-search-inline">
		(function () {
			if (window.SearchWP || window.SearchWP_Live_Search) { return; }
			var inputs = [];
			var main = document.getElementById('lsds');
			var mob = document.getElementById('lsds_mobile');
			if (main) { inputs.push(main); }
			if (mob) { inputs.push(mob); }
			if (!inputs.length) { return; }

			var box = document.createElement('div');
			box.id = 'tara-search-results';
			box.setAttribute('role', 'listbox');
			box.setAttribute('aria-label', 'نتایج جست‌وجو');
			var host = main ? main.closest('#searches') || main.parentNode : mob.parentNode;
			host.appendChild(box);

			/* v3.8.3 — وضعیت برای صفحه‌خوان‌ها؛ خودِ لیست aria-live ندارد
			   تا هر تایپ دو بار خوانده نشود. */
			var status = document.createElement('span');
			status.className = 'screen-reader-text';
			status.setAttribute('role', 'status');
			status.setAttribute('aria-live', 'polite');
			host.appendChild(status);

			var timer = null;
			var seq = 0;
			var cache = {};
			var active = -1;

			function esc(s) {
				return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
					return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
				});
			}

			function close() {
				box.classList.remove('is-open');
				box.innerHTML = '';
				box.setAttribute('aria-busy', 'false');
				box.removeAttribute('aria-activedescendant');
				active = -1;
				for (var i = 0; i < inputs.length; i++) {
					inputs[i].setAttribute('aria-expanded', 'false');
					inputs[i].removeAttribute('aria-activedescendant');
				}
			}

			/* v3.8.3 — «محمولهٔ» تازه: تا پاسخ برسد، سه ردیفِ اسکلت با
			   ارتفاعِ ثابت نشان داده می‌شود تا کادر ناگهان نپرد. */
			function skeleton() {
				box.innerHTML = '<div class="tara-sr__head">در حال جست‌وجو…</div>'
					+ '<div class="tara-sr__row is-skeleton" aria-hidden="true"><span class="tara-sr__sk tara-sr__sk--img"></span><span class="tara-sr__sk-lines"><span class="tara-sr__sk"></span><span class="tara-sr__sk tara-sr__sk--sm"></span></span></div>'
					+ '<div class="tara-sr__row is-skeleton" aria-hidden="true"><span class="tara-sr__sk tara-sr__sk--img"></span><span class="tara-sr__sk-lines"><span class="tara-sr__sk"></span><span class="tara-sr__sk tara-sr__sk--sm"></span></span></div>'
					+ '<div class="tara-sr__row is-skeleton" aria-hidden="true"><span class="tara-sr__sk tara-sr__sk--img"></span><span class="tara-sr__sk-lines"><span class="tara-sr__sk"></span><span class="tara-sr__sk tara-sr__sk--sm"></span></span></div>';
				box.classList.add('is-open');
				box.setAttribute('aria-busy', 'true');
			}

			function setActive(next) {
				var rows = box.querySelectorAll('a.tara-sr__row');
				if (!rows.length) { return; }
				if (active > -1 && rows[active]) {
					rows[active].classList.remove('is-active');
				}
				active = next;
				if (active < 0) { active = rows.length - 1; }
				if (active >= rows.length) { active = 0; }
				for (var i = 0; i < rows.length; i++) {
					var on = (i === active);
					rows[i].classList.toggle('is-active', on);
					rows[i].setAttribute('aria-selected', on ? 'true' : 'false');
				}
				var row = rows[active];
				if (row) {
					if (!row.id) { row.id = 'tara-sr-opt-' + active; }
					for (var j = 0; j < inputs.length; j++) {
						inputs[j].setAttribute('aria-activedescendant', row.id);
					}
					if (row.scrollIntoView) { row.scrollIntoView({ block: 'nearest' }); }
				}
			}

			function render(data, q) {
				var items = (data && data.items) || [];
				active = -1;
				if (!items.length) {
					box.innerHTML = '<div class="tara-sr__empty">چیزی برای «' + esc(q) + '» پیدا نشد.</div>';
					box.classList.add('is-open');
					box.setAttribute('aria-busy', 'false');
					status.textContent = 'نتیجه‌ای پیدا نشد.';
					return;
				}
				var html = '<div class="tara-sr__head">نتایج جست‌وجو</div>';
				for (var i = 0; i < items.length; i++) {
					var it = items[i];
					html += '<a class="tara-sr__row" role="option" aria-selected="false" id="tara-sr-opt-' + i + '" href="' + esc(it.url) + '">'
						+ (it.cover ? '<img src="' + esc(it.cover) + '" width="40" height="40" alt="" loading="lazy" decoding="async">' : '')
						+ '<span><span class="tara-sr__t">' + esc(it.title) + '</span>'
						+ (it.artist ? '<span class="tara-sr__a">' + esc(it.artist) + '</span>' : '')
						+ '</span></a>';
				}
				if (data && data.more) {
					html += '<a class="tara-sr__row tara-sr__row--more" role="option" aria-selected="false" href="' + esc(data.more) + '"><span class="tara-sr__t">همهٔ نتایج</span></a>';
				}
				box.innerHTML = html;
				box.classList.add('is-open');
				box.setAttribute('aria-busy', 'false');
				status.textContent = items.length + ' نتیجه پیدا شد.';
			}

			function ask(value) {
				var q = String(value || '').trim();
				if (q.length < 2) { close(); return; }

				if (cache[q]) {
					render(cache[q], q);
					return;
				}

				var mine = ++seq;
				skeleton();

				var url = '<?php echo esc_url_raw( $endpoint ); ?>?action=tara_search&nonce=<?php echo esc_js( $nonce ); ?>&q=' + encodeURIComponent(q);
				var xhr = new XMLHttpRequest();
				xhr.open('GET', url, true);
				xhr.onreadystatechange = function () {
					if (xhr.readyState !== 4 || mine !== seq) { return; }
					if (xhr.status !== 200) { close(); return; }
					var payload = null;
					try { payload = JSON.parse(xhr.responseText); } catch (e) { close(); return; }
					var data = payload && payload.data ? payload.data : { items: [] };
					cache[q] = data;
					render(data, q);
				};
				xhr.send();
			}

			inputs.forEach(function (input) {
				input.setAttribute('autocomplete', 'off');
				input.setAttribute('role', 'combobox');
				input.setAttribute('aria-autocomplete', 'list');
				input.setAttribute('aria-expanded', 'false');
				input.setAttribute('aria-controls', 'tara-search-results');
				input.setAttribute('aria-haspopup', 'listbox');

				input.addEventListener('input', function () {
					var v = input.value;
					input.setAttribute('aria-expanded', 'true');
					window.clearTimeout(timer);
					/* v3.8.3 — ۲۵۰ms: هم تایپِ سریع را دست‌نخورده می‌گذارد و
					   هم نسبت به ۲۶۰ms قبلی یک درخواست کمتر می‌فرستد. */
					timer = window.setTimeout(function () { ask(v); }, 250);
				});

				input.addEventListener('keydown', function (ev) {
					var open = box.classList.contains('is-open');
					if (ev.key === 'Escape' || ev.keyCode === 27) {
						if (open) {
							ev.preventDefault();
							close();
						}
						return;
					}
					if (!open) { return; }
					if (ev.key === 'ArrowDown' || ev.keyCode === 40) {
						ev.preventDefault();
						setActive(active + 1);
					} else if (ev.key === 'ArrowUp' || ev.keyCode === 38) {
						ev.preventDefault();
						setActive(active - 1);
					} else if (ev.key === 'Enter' || ev.keyCode === 13) {
						var rows = box.querySelectorAll('a.tara-sr__row');
						if (active > -1 && rows[active]) {
							ev.preventDefault();
							window.location.href = rows[active].getAttribute('href');
						}
					}
				});

				input.addEventListener('focus', function () {
					if (input.value.trim().length >= 2 && box.innerHTML) {
						box.classList.add('is-open');
					}
				});
			});

			document.addEventListener('click', function (ev) {
				var node = ev.target;
				if (!node || !node.closest) { return; }
				if (!box.contains(node) && !node.closest('#searches') && !node.closest('#mobiles_header')) {
					close();
				}
			});
		})();
		</script>
		<?php
	}
	add_action( 'wp_footer', 'tara_search_inline_js', 98 );
}
