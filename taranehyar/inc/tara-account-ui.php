<?php
/**
 * ترانه یار — رابطِ حسابِ کاربری (نسخهٔ ۳.۱۰.۰)
 * =========================================================================
 * همهٔ مارکاپِ حساب این‌جاست: دکمهٔ هدر، مودالِ ورودِ پیامکی و پنلِ کاربری.
 * داده و منطق در `inc/tara-account.php` است.
 *
 * این فایل عمداً آیکون‌ها را SVGِ درون‌خطی می‌سازد (نه فونتِ آیکون):
 *   • هیچ وابستگی به فایلِ فونت و هیچ آیکونِ ناموجودی وجود ندارد
 *   • رنگِ آیکون از `currentColor` می‌آید و در حالتِ شب هم درست است
 *   • چند کیلوبایت برای ۲۰ آیکون کافی است و درخواستِ شبکه اضافه نمی‌کند
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * ۱) آیکون‌های SVG
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_icon' ) ) {
	/**
	 * آیکونِ SVGِ درون‌خطی.
	 *
	 * @param string $name نامِ آیکون.
	 * @param string $class کلاسِ اضافه.
	 * @return string
	 */
	function tara_acc_icon( $name, $class = '' ) {
		$paths = array(
			'heart'    => '<path d="M12 20s-7-4.35-7-9.5A4.5 4.5 0 0 1 12 7a4.5 4.5 0 0 1 7 3.5C19 15.65 12 20 12 20Z"/>',
			'heart-fill' => '<path d="M12 20s-7-4.35-7-9.5A4.5 4.5 0 0 1 12 7a4.5 4.5 0 0 1 7 3.5C19 15.65 12 20 12 20Z" fill="currentColor"/>',
			'plus'     => '<path d="M12 5v14M5 12h14"/>',
			'check'    => '<path d="m4 12 5 5L20 6"/>',
			'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
			'phone'    => '<path d="M7 3h3l2 5-2.5 1.5a11 11 0 0 0 5 5L16 12l5 2v3a2 2 0 0 1-2.2 2A16 16 0 0 1 5 5.2A2 2 0 0 1 7 3Z"/>',
			'lock'     => '<rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
			'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
			'telegram' => '<path d="m21 4-3 16-6-4-3 3v-5L4 11Z"/><path d="m8 11 9-5"/>',
			'music'    => '<circle cx="7" cy="18" r="3"/><circle cx="18" cy="16" r="3"/><path d="M10 18V6l11-2v12"/>',
			'list'     => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
			'bell'     => '<path d="M18 16H6l1.5-2V9a4.5 4.5 0 0 1 9 0v5Z"/><path d="M10 19a2 2 0 0 0 4 0"/>',
			'clock'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
			'chart'    => '<path d="M4 20V9M10 20V4M16 20v-7M22 20H2"/>',
			'edit'     => '<path d="M4 20h4l10-10-4-4L4 16Z"/><path d="m14 6 4 4"/>',
			'share'    => '<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="m8.4 10.8 7.2-3.6M8.4 13.2l7.2 3.6"/>',
			'trash'    => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
			'arrow'    => '<path d="M14 6l-6 6 6 6"/>',
			'logout'   => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 8l-4 4 4 4M6 12h9"/>',
			'play'     => '<path d="M8 5v14l11-7Z"/>',
			'spark'    => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M18 6l-2.5 2.5M8.5 15.5 6 18"/>',
			'link'     => '<path d="M10 13a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7L11.5 5.5"/><path d="M14 11a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7L12.5 18.5"/>',
			'copy'     => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/>',
		);
		$path = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['music'];
		$cls  = 'ty-acct__svg' . ( '' !== (string) $class ? ' ' . $class : '' );

		return '<svg class="' . esc_attr( $cls ) . '" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
	}
}

/* -------------------------------------------------------------------------
 * ۲) تنظیماتِ جاوااسکریپت و دارایی‌ها
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_config' ) ) {
	/**
	 * داده‌ای که JS لازم دارد (هرگز رمز یا توکن در آن نیست).
	 *
	 * @return array
	 */
	function tara_acc_config() {
		$user_id = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
		$user    = array();
		if ( $user_id > 0 ) {
			$user = array(
				'id'     => $user_id,
				'name'   => (string) get_user_meta( $user_id, 'nickname', true ),
				'avatar' => tara_acc_avatar_url( $user_id, 64 ),
				'phone'  => tara_acc_phone_mask( tara_acc_user_phone( $user_id ) ),
				'url'    => tara_acc_profile_url(),
			);
		}

		return array(
			'api'      => function_exists( 'admin_url' ) ? admin_url( 'admin-ajax.php' ) : '',
			'nonce'    => function_exists( 'wp_create_nonce' ) ? wp_create_nonce( 'tara_account' ) : '',
			'logged'   => $user_id > 0,
			'user'     => $user,
			'url'      => array(
				'account' => tara_acc_profile_url(),
				'login'   => function_exists( 'tara_acc_page_url' ) ? tara_acc_page_url( 'page-login.php' ) : '',
				'home'    => function_exists( 'home_url' ) ? home_url( '/' ) : '',
			),
			'features' => array(
				'likes'     => tara_acc_feature( 'likes' ),
				'playlists' => tara_acc_feature( 'playlists' ),
				'follows'   => tara_acc_feature( 'follows' ),
				'history'   => tara_acc_feature( 'history' ),
				'notifs'    => tara_acc_feature( 'notifs' ),
			),
			'allow_password' => 'on' === (string) tara_acc_opt( 'allow_password', 'on' ),
			'bot'      => preg_replace( '/[^A-Za-z0-9_]/', '', (string) tara_acc_opt( 'tg_bot' ) ),
			'labels'   => array(
				'sending'  => 'در حال ارسال…',
				'verifying' => 'در حال بررسی…',
				'saved'    => 'ذخیره شد.',
				'copied'   => 'کپی شد.',
				'error'    => 'خطایی رخ داد؛ دوباره تلاش کنید.',
				'needLogin' => 'برای این کار باید وارد شوید.',
				'codeSent' => 'کد برای شما پیامک شد.',
				'resendIn' => 'ارسالِ دوباره تا %s ثانیه',
				'resend'   => 'ارسالِ دوبارهٔ کد',
				'added'    => 'به پلی‌لیست اضافه شد.',
				'removed'  => 'از پلی‌لیست برداشته شد.',
				'newList'  => 'نامِ پلی‌لیستِ تازه',
				'confirmDelete' => 'این پلی‌لیست پاک شود؟',
			),
		);
	}
}

if ( ! function_exists( 'tara_acc_assets' ) ) {
	/**
	 * بارگذاریِ CSS/JS — سبک، فقط وقتی لازم است.
	 *
	 * @return void
	 */
	function tara_acc_assets() {
		if ( ! tara_acc_on() || ! function_exists( 'wp_enqueue_style' ) ) {
			return;
		}

		$css = '/css/tara-account.css';
		if ( function_exists( 'tara_css_rel' ) ) {
			$css = tara_css_rel( $css );
		}
		$ver = function_exists( 'taranehyar_asset_ver' ) ? taranehyar_asset_ver( '/css/tara-account.css' ) : TARANEHYAR_VERSION;

		wp_enqueue_style( 'taranehyar-account', TARANEHYAR_URI . $css, array(), $ver );

		if ( ! function_exists( 'wp_enqueue_script' ) ) {
			return;
		}
		wp_enqueue_script(
			'taranehyar-account',
			TARANEHYAR_URI . '/js/tara-account.js',
			array(),
			function_exists( 'taranehyar_asset_ver' ) ? taranehyar_asset_ver( '/js/tara-account.js' ) : TARANEHYAR_VERSION,
			true
		);
		if ( function_exists( 'wp_localize_script' ) ) {
			wp_localize_script( 'taranehyar-account', 'TaraAccount', tara_acc_config() );
		}
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_enqueue_scripts', 'tara_acc_assets', 31 );
}

/* -------------------------------------------------------------------------
 * ۳) دکمهٔ هدر و مودال
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_button' ) ) {
	/**
	 * دکمهٔ حساب برای هدر (دسکتاپ/موبایل).
	 *
	 * @param string $context محلِ نمایش.
	 * @return string
	 */
	function tara_acc_button( $context = 'desktop' ) {
		if ( ! tara_acc_on() ) {
			return '';
		}

		$user_id = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
		$class   = 'ty-acct-btn ty-acct-btn--' . sanitize_html_class( (string) $context );

		if ( $user_id > 0 ) {
			$name   = (string) get_user_meta( $user_id, 'nickname', true );
			$name   = '' !== $name ? $name : 'حساب من';
			$unread = tara_acc_unread( $user_id );
			$html   = '<a class="' . esc_attr( $class ) . ' is-in" href="' . esc_url( tara_acc_profile_url() ) . '" aria-label="' . esc_attr( 'حساب کاربریِ ' . $name ) . '">';
			$html  .= '<img class="ty-acct-btn__img" src="' . esc_url( tara_acc_avatar_url( $user_id, 64 ) ) . '" width="32" height="32" alt="" loading="lazy">';
			$html  .= '<span class="ty-acct-btn__txt">' . esc_html( $name ) . '</span>';
			if ( $unread > 0 ) {
				$html .= '<span class="ty-acct-btn__dot" aria-label="' . esc_attr( tara_acc_fa( $unread ) . ' پیامِ تازه' ) . '">' . esc_html( tara_acc_fa( $unread ) ) . '</span>';
			}
			$html .= '</a>';
			return $html;
		}

		$html  = '<button type="button" class="' . esc_attr( $class ) . '" data-tara-open="account" aria-haspopup="dialog">';
		$html .= tara_acc_icon( 'user' );
		$html .= '<span class="ty-acct-btn__txt">ورود / ثبت‌نام</span>';
		$html .= '</button>';

		return $html;
	}
}

if ( ! function_exists( 'tara_acc_modal' ) ) {
	/**
	 * مودالِ ورود/ثبت‌نام (یک‌بار در پاصفحه).
	 *
	 * @return void
	 */
	function tara_acc_modal() {
		if ( ! tara_acc_on() || is_user_logged_in() ) {
			return;
		}

		$allow_password = 'on' === (string) tara_acc_opt( 'allow_password', 'on' );
		$site           = (string) get_bloginfo( 'name' );
		?>
		<div class="ty-acct-modal" id="ty-acct-modal" data-tara-account-modal hidden>
			<div class="ty-acct-modal__veil" data-tara-close></div>
			<div class="ty-acct-modal__card" role="dialog" aria-modal="true" aria-labelledby="ty-acct-modal-title">
				<button type="button" class="ty-acct-modal__x" data-tara-close aria-label="بستن">
					<?php echo tara_acc_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</button>

				<div class="ty-acct-modal__art" aria-hidden="true">
					<span class="ty-acct-modal__ring"></span>
					<span class="ty-acct-modal__ring ty-acct-modal__ring--2"></span>
					<span class="ty-acct-modal__disc"><?php echo tara_acc_icon( 'music' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="ty-acct-modal__eq"><i></i><i></i><i></i><i></i><i></i></span>
				</div>

				<div class="ty-acct-modal__body">
					<p class="ty-acct-modal__eyebrow"><span class="ty-acct-modal__pulse"></span><?php echo esc_html( $site ); ?> · ورودِ سریع</p>
					<h2 class="ty-acct-modal__title" id="ty-acct-modal-title">
						<span data-tara-title-phone>با شمارهٔ موبایل وارد شوید</span>
					</h2>
					<p class="ty-acct-modal__sub" data-tara-sub-phone>
						فقط شماره‌تان را بدهید؛ یک کدِ ۶رقمی پیامک می‌شود. حساب اگر نباشد، همین‌جا ساخته می‌شود — بدونِ رمز و بدونِ ایمیل.
					</p>

					<form class="ty-acct-form" data-tara-form="phone" novalidate>
						<label class="ty-acct-field">
							<span class="ty-acct-field__label">شمارهٔ موبایل</span>
							<span class="ty-acct-field__box">
								<span class="ty-acct-field__pre">+۹۸</span>
								<input type="tel" inputmode="numeric" autocomplete="tel-national" name="phone" dir="ltr"
									placeholder="09121234567" maxlength="13" aria-describedby="ty-acct-phone-hint" required>
								<?php echo tara_acc_icon( 'phone', 'ty-acct-field__ico' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</span>
							<span class="ty-acct-field__hint" id="ty-acct-phone-hint">با ۰۹ شروع کنید؛ پیامک فقط به همین شماره می‌رود.</span>
						</label>

						<button type="submit" class="ty-acct-submit">
							<span class="ty-acct-submit__txt">دریافتِ کد</span>
							<span class="ty-acct-submit__spin" aria-hidden="true"></span>
						</button>

						<p class="ty-acct-form__msg" role="status" aria-live="polite" data-tara-msg></p>
					</form>

					<form class="ty-acct-form" data-tara-form="code" hidden novalidate>
						<div class="ty-acct-code__head">
							<button type="button" class="ty-acct-back" data-tara-back aria-label="تغییرِ شماره">
								<?php echo tara_acc_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</button>
							<p class="ty-acct-code__sent">کد به <b data-tara-phone-shown></b> پیامک شد.</p>
						</div>

						<div class="ty-acct-code" data-tara-code-inputs aria-label="کدِ ۶رقمی">
							<?php for ( $i = 0; $i < 6; $i++ ) : ?>
								<input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1" dir="ltr"
									aria-label="<?php echo esc_attr( 'رقمِ ' . ( $i + 1 ) . ' کد' ); ?>">
							<?php endfor; ?>
						</div>

						<p class="ty-acct-devcode" data-tara-devcode hidden></p>

						<button type="submit" class="ty-acct-submit">
							<span class="ty-acct-submit__txt">ورود به حساب</span>
							<span class="ty-acct-submit__spin" aria-hidden="true"></span>
						</button>

						<p class="ty-acct-form__msg" role="status" aria-live="polite" data-tara-msg></p>

						<div class="ty-acct-resend">
							<span data-tara-resend-txt>می‌توانید کد را دوباره بگیرید.</span>
							<button type="button" class="ty-acct-link" data-tara-resend hidden>ارسالِ دوبارهٔ کد</button>
						</div>
					</form>

					<?php if ( $allow_password ) : ?>
						<form class="ty-acct-form" data-tara-form="password" hidden novalidate>
							<label class="ty-acct-field">
								<span class="ty-acct-field__label">نام‌کاربری یا ایمیل</span>
								<span class="ty-acct-field__box">
									<input type="text" name="login" autocomplete="username" dir="ltr" required>
									<?php echo tara_acc_icon( 'user', 'ty-acct-field__ico' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</span>
							</label>
							<label class="ty-acct-field">
								<span class="ty-acct-field__label">رمز عبور</span>
								<span class="ty-acct-field__box">
									<input type="password" name="password" autocomplete="current-password" dir="ltr" required>
									<?php echo tara_acc_icon( 'lock', 'ty-acct-field__ico' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</span>
							</label>
							<button type="submit" class="ty-acct-submit">
								<span class="ty-acct-submit__txt">ورود</span>
								<span class="ty-acct-submit__spin" aria-hidden="true"></span>
							</button>
							<p class="ty-acct-form__msg" role="status" aria-live="polite" data-tara-msg></p>
						</form>
					<?php endif; ?>

					<div class="ty-acct-modal__foot">
						<button type="button" class="ty-acct-link" data-tara-mode="password" <?php echo $allow_password ? '' : 'hidden'; ?>>ورود با رمزِ عبور</button>
						<button type="button" class="ty-acct-link" data-tara-mode="phone" hidden>ورود با پیامک</button>
						<a class="ty-acct-modal__help" href="<?php echo esc_url( home_url( '/?s=' ) ); ?>">راهنما</a>
					</div>

					<p class="ty-acct-modal__terms">با ورود، قواعدِ سایت و نگه‌داریِ شماره برای ورود را می‌پذیرید. شماره هیچ‌جا عمومی نمایش داده نمی‌شود.</p>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_footer', 'tara_acc_modal', 40 );
}

/* -------------------------------------------------------------------------
 * ۴) دکمه‌های کوچک (علاقه‌مندی، پلی‌لیست، دنبال‌کردن)
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_like_button' ) ) {
	/**
	 * قلبِ «ذخیره» برای یک آهنگ.
	 *
	 * @param int   $post_id شناسه.
	 * @param array $args    class/size/label.
	 * @return string
	 */
	function tara_acc_like_button( $post_id = 0, $args = array() ) {
		if ( ! tara_acc_feature( 'likes' ) ) {
			return '';
		}
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return '';
		}
		$args = wp_parse_args(
			$args,
			array(
				'class' => '',
				'label' => true,
			)
		);

		$user_id = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
		$liked   = $user_id > 0 ? tara_acc_liked( $post_id, $user_id ) : false;

		$class = 'ty-acct-like' . ( $liked ? ' is-on' : '' ) . ( '' !== (string) $args['class'] ? ' ' . $args['class'] : '' );

		$html  = '<button type="button" class="' . esc_attr( $class ) . '" data-tara-like="' . esc_attr( $post_id ) . '"'
			. ' aria-pressed="' . ( $liked ? 'true' : 'false' ) . '"'
			. ' aria-label="' . esc_attr( $liked ? 'برداشتن از علاقه‌مندی‌ها' : 'ذخیره در علاقه‌مندی‌ها' ) . '">';
		$html .= '<span class="ty-acct-like__off">' . tara_acc_icon( 'heart' ) . '</span>';
		$html .= '<span class="ty-acct-like__on">' . tara_acc_icon( 'heart-fill' ) . '</span>';
		if ( $args['label'] ) {
			$html .= '<span class="ty-acct-like__txt">' . esc_html( $liked ? 'ذخیره شد' : 'ذخیره' ) . '</span>';
		}
		$html .= '</button>';

		return $html;
	}
}

if ( ! function_exists( 'tara_acc_follow_button' ) ) {
	/**
	 * دکمهٔ «دنبال‌کردن» برای صفحهٔ خواننده.
	 *
	 * @param int   $term_id شناسهٔ ترم.
	 * @param array $args    class.
	 * @return string
	 */
	function tara_acc_follow_button( $term_id = 0, $args = array() ) {
		if ( ! tara_acc_feature( 'follows' ) ) {
			return '';
		}
		$term_id = (int) $term_id;
		if ( $term_id <= 0 ) {
			return '';
		}
		$args = wp_parse_args( $args, array( 'class' => '' ) );

		$user_id   = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
		$following = $user_id > 0 ? tara_acc_following( $term_id, $user_id ) : false;
		$count     = tara_acc_follower_count( $term_id );

		$class = 'ty-acct-follow' . ( $following ? ' is-on' : '' ) . ( '' !== (string) $args['class'] ? ' ' . $args['class'] : '' );

		$html  = '<button type="button" class="' . esc_attr( $class ) . '" data-tara-follow="' . esc_attr( $term_id ) . '"'
			. ' aria-pressed="' . ( $following ? 'true' : 'false' ) . '">';
		$html .= '<span class="ty-acct-follow__ico">' . tara_acc_icon( $following ? 'check' : 'plus' ) . '</span>';
		$html .= '<span class="ty-acct-follow__txt">' . esc_html( $following ? 'دنبال می‌کنید' : 'دنبال‌کردن' ) . '</span>';
		$html .= '<span class="ty-acct-follow__num" data-tara-followers>' . esc_html( $count > 0 ? tara_acc_fa( $count ) : '' ) . '</span>';
		$html .= '</button>';

		return $html;
	}
}

if ( ! function_exists( 'tara_acc_row_actions' ) ) {
	/**
	 * دکمه‌های کنارِ هر ردیف در پنل.
	 *
	 * @param int    $post_id شناسهٔ آهنگ.
	 * @param string $mode    favorites | playlist | history | feed.
	 * @param string $key     کلیدِ پلی‌لیست (برای mode=playlist).
	 * @return string
	 */
	function tara_acc_row_actions( $post_id = 0, $mode = 'favorites', $key = '' ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return '';
		}

		$html = '';
		if ( 'playlist' === $mode ) {
			$html .= '<button type="button" class="ty-acct-mini ty-acct-mini--x" data-tara-pl-toggle="' . esc_attr( $post_id ) . '" data-tara-pl-key="' . esc_attr( (string) $key ) . '" data-tara-pl-on="0" aria-label="برداشتن از این پلی‌لیست">' . tara_acc_icon( 'close' ) . '</button>';
			return $html;
		}

		if ( tara_acc_feature( 'likes' ) ) {
			$html .= '<button type="button" class="ty-acct-mini' . ( tara_acc_liked( $post_id ) ? ' is-on' : '' ) . '" data-tara-like="' . esc_attr( $post_id ) . '" aria-pressed="' . ( tara_acc_liked( $post_id ) ? 'true' : 'false' ) . '" aria-label="ذخیره در علاقه‌مندی‌ها">' . tara_acc_icon( 'heart' ) . '</button>';
		}
		if ( tara_acc_feature( 'playlists' ) ) {
			$html .= '<button type="button" class="ty-acct-mini" data-tara-pl-open="' . esc_attr( $post_id ) . '" aria-label="افزودن به پلی‌لیست" aria-haspopup="dialog">' . tara_acc_icon( 'plus' ) . '</button>';
		}

		return $html;
	}
}

/* -------------------------------------------------------------------------
 * ۵) فهرستِ آهنگِ پنل (با صفِ پخشِ همان فهرست)
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_track_list' ) ) {
	/**
	 * فهرستِ آهنگ با دکمه‌های حساب.
	 *
	 * @param int[]  $ids  شناسه‌ها.
	 * @param array  $args mode/key/title/empty.
	 * @return string
	 */
	function tara_acc_track_list( $ids, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'mode'  => 'favorites',
				'key'   => '',
				'title' => '',
				'empty' => 'هنوز چیزی این‌جا نیست.',
			)
		);

		$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
		if ( empty( $ids ) ) {
			return '<p class="ty-acct-empty">' . esc_html( $args['empty'] ) . '</p>';
		}

		$queue = array();
		$rows  = '';

		foreach ( $ids as $post_id ) {
			$src   = function_exists( 'tara_track_src' ) ? (string) tara_track_src( $post_id ) : '';
			$index = -1;
			if ( '' !== $src ) {
				$index   = count( $queue );
				$queue[] = array(
					'id'     => $post_id,
					'title'  => tara_track_title( $post_id ),
					'artist' => tara_track_artist( $post_id ),
					'src'    => $src,
					'cover'  => tara_track_cover( $post_id, 'thumb2' ),
					'link'   => get_permalink( $post_id ),
				);
			}
			$rows .= '<li>' . tara_track_row(
				$post_id,
				array(
					'index'   => $index,
					'actions' => tara_acc_row_actions( $post_id, (string) $args['mode'], (string) $args['key'] ),
				)
			) . '</li>';
		}

		$attrs = '';
		if ( ! empty( $queue ) ) {
			$json = wp_json_encode( $queue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			if ( is_string( $json ) ) {
				$attrs .= ' data-tara-queue="' . esc_attr( $json ) . '"';
			}
			if ( '' !== (string) $args['title'] ) {
				$attrs .= ' data-tara-title="' . esc_attr( (string) $args['title'] ) . '"';
			}
		}

		return '<ul class="ty-tracklist ty-acct-tracks"' . $attrs . '>' . $rows . '</ul>';
	}
}

/* -------------------------------------------------------------------------
 * ۶) پنلِ کاربری
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_tabs' ) ) {
	/**
	 * تب‌های پنل (کلید → برچسب/آیکون/فعال‌بودن).
	 *
	 * @return array
	 */
	function tara_acc_tabs() {
		$tabs = array(
			'overview'  => array( 'label' => 'نمای کلی', 'icon' => 'chart', 'on' => true ),
			'favorites' => array( 'label' => 'علاقه‌مندی‌ها', 'icon' => 'heart', 'on' => tara_acc_feature( 'likes' ) ),
			'playlists' => array( 'label' => 'پلی‌لیست‌ها', 'icon' => 'list', 'on' => tara_acc_feature( 'playlists' ) ),
			'follows'   => array( 'label' => 'خواننده‌ها', 'icon' => 'user', 'on' => tara_acc_feature( 'follows' ) ),
			'history'   => array( 'label' => 'تاریخچهٔ شنیدن', 'icon' => 'clock', 'on' => tara_acc_feature( 'history' ) ),
			'notifs'    => array( 'label' => 'پیام‌ها', 'icon' => 'bell', 'on' => tara_acc_feature( 'notifs' ) ),
			'telegram'  => array( 'label' => 'اتصال به ربات', 'icon' => 'telegram', 'on' => true ),
			'profile'   => array( 'label' => 'مشخصات', 'icon' => 'edit', 'on' => true ),
		);

		return apply_filters( 'tara_acc_tabs', $tabs );
	}
}

if ( ! function_exists( 'tara_acc_current_tab' ) ) {
	/**
	 * تبِ جاری از آدرس.
	 *
	 * @return string
	 */
	function tara_acc_current_tab() {
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification
		$tabs = tara_acc_tabs();
		if ( ! isset( $tabs[ $tab ] ) || empty( $tabs[ $tab ]['on'] ) ) {
			return 'overview';
		}
		return $tab;
	}
}

if ( ! function_exists( 'tara_acc_login_card' ) ) {
	/**
	 * کارتِ «وارد شوید» برای کاربرِ مهمان.
	 *
	 * @return string
	 */
	function tara_acc_login_card() {
		ob_start();
		?>
		<div class="ty-acct-guest">
			<div class="ty-acct-guest__art" aria-hidden="true">
				<span class="ty-acct-guest__disc"><?php echo tara_acc_icon( 'music' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</div>
			<h1 class="ty-acct-guest__title">حسابِ شما در <?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
			<p class="ty-acct-guest__sub">
				با شمارهٔ موبایل در چند ثانیه وارد شوید: آهنگ‌ها را ذخیره کنید، پلی‌لیست بسازید،
				خواننده‌های محبوبتان را دنبال کنید و آهنگ‌های تازه‌شان را از دست ندهید.
			</p>
			<ul class="ty-acct-guest__list">
				<li><?php echo tara_acc_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>علاقه‌مندی‌ها و پلی‌لیست‌های خودتان</span></li>
				<li><?php echo tara_acc_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>دنبال‌کردنِ خواننده‌ها و فیدِ آهنگ‌های تازه</span></li>
				<li><?php echo tara_acc_icon( 'telegram' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>اتصال به رباتِ تلگرام برای اعلان‌ها</span></li>
			</ul>
			<button type="button" class="ty-acct-submit ty-acct-submit--wide" data-tara-open="account">
				<span class="ty-acct-submit__txt">ورود / ثبت‌نام با شمارهٔ موبایل</span>
			</button>
			<p class="ty-acct-guest__note">بدونِ رمز، بدونِ ایمیل، بدونِ فرمِ طولانی.</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'tara_acc_panel' ) ) {
	/**
	 * پنلِ کاملِ کاربری.
	 *
	 * @return string
	 */
	function tara_acc_panel() {
		if ( ! tara_acc_on() ) {
			return '<p class="ty-acct-empty">حسابِ کاربری در تنظیماتِ قالب خاموش است.</p>';
		}

		$user_id = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
		if ( $user_id <= 0 ) {
			return tara_acc_login_card();
		}

		$tab    = tara_acc_current_tab();
		$tabs   = tara_acc_tabs();
		$counts = tara_acc_counts( $user_id );
		$name   = (string) get_user_meta( $user_id, 'nickname', true );
		$bio    = (string) get_user_meta( $user_id, 'description', true );
		$phone  = tara_acc_user_phone( $user_id );
		$avatar = tara_acc_avatar_url( $user_id, 160 );
		$joined = (int) get_user_meta( $user_id, 'tara_joined', true );
		$tg_id  = (int) get_user_meta( $user_id, 'tara_tg_id', true );

		ob_start();
		?>
		<div class="ty-acct" dir="rtl">
			<aside class="ty-acct__side">
				<div class="ty-acct__me">
					<span class="ty-acct__ava">
						<img src="<?php echo esc_url( $avatar ); ?>" width="88" height="88" alt="" loading="lazy">
					</span>
					<h1 class="ty-acct__name"><?php echo esc_html( '' !== $name ? $name : 'شنونده' ); ?></h1>
					<?php if ( '' !== $phone ) : ?>
						<p class="ty-acct__phone" dir="ltr"><?php echo esc_html( tara_acc_phone_mask( $phone ) ); ?></p>
					<?php endif; ?>
					<?php if ( $joined > 0 ) : ?>
						<p class="ty-acct__since">عضو از <?php echo esc_html( date_i18n( 'j F Y', $joined ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions ?></p>
					<?php endif; ?>
					<?php if ( $tg_id > 0 ) : ?>
						<p class="ty-acct__linked"><?php echo tara_acc_icon( 'telegram' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>تلگرام وصل است</span></p>
					<?php endif; ?>
				</div>

				<nav class="ty-acct__nav" aria-label="بخش‌های حساب">
					<?php foreach ( $tabs as $key => $data ) : ?>
						<?php if ( empty( $data['on'] ) ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<a class="ty-acct__navlink<?php echo $key === $tab ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( tara_acc_profile_url( $key ) ); ?>"
							<?php echo $key === $tab ? 'aria-current="page"' : ''; ?>>
							<?php echo tara_acc_icon( $data['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span><?php echo esc_html( $data['label'] ); ?></span>
							<?php if ( 'notifs' === $key && $counts['unread'] > 0 ) : ?>
								<em class="ty-acct__badge"><?php echo esc_html( tara_acc_fa( $counts['unread'] ) ); ?></em>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</nav>

				<button type="button" class="ty-acct__logout" data-tara-logout>
					<?php echo tara_acc_icon( 'logout' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span>خروج از حساب</span>
				</button>
			</aside>

			<div class="ty-acct__main">
				<?php
				switch ( $tab ) {
					case 'favorites':
						echo tara_acc_tab_favorites( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput
						break;
					case 'playlists':
						echo tara_acc_tab_playlists( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput
						break;
					case 'follows':
						echo tara_acc_tab_follows( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput
						break;
					case 'history':
						echo tara_acc_tab_history( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput
						break;
					case 'notifs':
						echo tara_acc_tab_notifs( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput
						break;
					case 'telegram':
						echo tara_acc_tab_telegram( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput
						break;
					case 'profile':
						echo tara_acc_tab_profile( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput
						break;
					default:
						echo tara_acc_tab_overview( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'tara_acc_date' ) ) {
	/**
	 * تاریخِ خوانا با پشتیبان.
	 *
	 * @param int    $ts      زمان.
	 * @param string $format  قالب.
	 * @return string
	 */
	function tara_acc_date( $ts, $format = 'j F Y' ) {
		$ts = (int) $ts;
		if ( $ts <= 0 ) {
			return '';
		}
		if ( function_exists( 'date_i18n' ) ) {
			return (string) date_i18n( $format, $ts );
		}
		return (string) gmdate( 'Y-m-d', $ts );
	}
}

if ( ! function_exists( 'tara_acc_stat' ) ) {
	/**
	 * کارتِ عددیِ داشبورد.
	 *
	 * @param string $label برچسب.
	 * @param int    $num   عدد.
	 * @param string $icon  آیکون.
	 * @param string $url   لینک.
	 * @param string $unit  واحد.
	 * @return string
	 */
	function tara_acc_stat( $label, $num, $icon, $url, $unit = '' ) {
		return '<a class="ty-acct-stat" href="' . esc_url( $url ) . '">'
			. '<span class="ty-acct-stat__ico">' . tara_acc_icon( $icon ) . '</span>'
			. '<span class="ty-acct-stat__num">' . esc_html( tara_acc_fa( (int) $num ) ) . ( '' !== $unit ? '<em>' . esc_html( $unit ) . '</em>' : '' ) . '</span>'
			. '<span class="ty-acct-stat__lbl">' . esc_html( $label ) . '</span>'
			. '</a>';
	}
}

if ( ! function_exists( 'tara_acc_artist_cards' ) ) {
	/**
	 * کارت‌های خوانندهٔ دنبال‌شده.
	 *
	 * @param int[] $terms شناسهٔ ترم‌ها.
	 * @return string
	 */
	function tara_acc_artist_cards( $terms ) {
		$tax = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
		$out = '';

		foreach ( (array) $terms as $term_id ) {
			$term = function_exists( 'get_term' ) ? get_term( (int) $term_id, $tax ) : null;
			if ( ! $term || ( function_exists( 'is_wp_error' ) && is_wp_error( $term ) ) ) {
				continue;
			}
			$img = function_exists( 'get_field' ) ? (string) get_field( 'artist_image', $term ) : '';
			if ( '' === $img && function_exists( 'get_term_meta' ) ) {
				$img = (string) get_term_meta( (int) $term_id, 'artist_image', true );
			}
			/* get_term_link ممکن است WP_Error بدهد؛ بدونِ گارد، آدرسِ خراب
			   در صفحه می‌نشیند. */
			$term_url = function_exists( 'get_term_link' ) ? get_term_link( $term, $tax ) : '';
			if ( is_wp_error( $term_url ) || ! is_string( $term_url ) ) {
				$term_url = '#';
			}
			$out .= '<article class="ty-acct-artist">';
			$out .= '<a class="ty-acct-artist__art" href="' . esc_url( $term_url ) . '">';
			$out .= '' !== $img
				? '<img src="' . esc_url( $img ) . '" width="120" height="120" alt="" loading="lazy">'
				: '<span class="ty-acct-artist__ph" aria-hidden="true">' . tara_acc_icon( 'user' ) . '</span>';
			$out .= '</a>';
			$out .= '<a class="ty-acct-artist__name" href="' . esc_url( $term_url ) . '">' . esc_html( $term->name ) . '</a>';
			$out .= '<p class="ty-acct-artist__meta">' . esc_html( tara_acc_fa( (int) $term->count ) ) . ' آهنگ · ' . esc_html( tara_acc_fa( tara_acc_follower_count( (int) $term_id ) ) ) . ' دنبال‌کننده</p>';
			$out .= tara_acc_follow_button( (int) $term_id, array( 'class' => 'ty-acct-follow--sm' ) );
			$out .= '</article>';
		}

		return '' !== $out ? '<div class="ty-acct-artists">' . $out . '</div>' : '';
	}
}

/* ---- تب ۱: نمای کلی ---- */

if ( ! function_exists( 'tara_acc_tab_overview' ) ) {
	/**
	 * داشبورد.
	 *
	 * @param int $user_id شناسه.
	 * @return string
	 */
	function tara_acc_tab_overview( $user_id ) {
		$counts = tara_acc_counts( (int) $user_id );
		$name   = (string) get_user_meta( (int) $user_id, 'nickname', true );
		$likes  = array_slice( tara_acc_likes( (int) $user_id ), 0, 6 );
		$feed   = tara_acc_feed( (int) $user_id, array( 'limit' => 5 ) );
		$hour   = (int) gmdate( 'G' );
		$greet  = $hour < 12 ? 'صبح بخیر' : ( $hour < 18 ? 'وقتِ خوبِ شنیدن' : 'شب بخیر' );

		ob_start();
		?>
		<section class="ty-acct-tab ty-acct-tab--overview">
			<header class="ty-acct-head">
				<div>
					<p class="ty-acct-head__eyebrow"><?php echo esc_html( $greet ); ?></p>
					<h2 class="ty-acct-head__title"><?php echo esc_html( '' !== $name ? $name : 'شنوندهٔ ' . get_bloginfo( 'name' ) ); ?></h2>
					<p class="ty-acct-head__sub">خلاصهٔ همه‌چیزِ شما در یک نگاه.</p>
				</div>
				<a class="ty-acct-btn2" href="<?php echo esc_url( tara_acc_profile_url( 'favorites' ) ); ?>">
					<?php echo tara_acc_icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span>پخشِ علاقه‌مندی‌ها</span>
				</a>
			</header>

			<div class="ty-acct-stats">
				<?php
				echo tara_acc_stat( 'آهنگِ ذخیره‌شده', $counts['likes'], 'heart', tara_acc_profile_url( 'favorites' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo tara_acc_stat( 'پلی‌لیست', $counts['playlists'], 'list', tara_acc_profile_url( 'playlists' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo tara_acc_stat( 'خوانندهٔ دنبال‌شده', $counts['follows'], 'user', tara_acc_profile_url( 'follows' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo tara_acc_stat( 'شنیده‌شده', $counts['history'], 'clock', tara_acc_profile_url( 'history' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>

			<?php if ( ! empty( $feed ) ) : ?>
				<div class="ty-acct-block">
					<div class="ty-acct-block__head">
						<h3 class="ty-acct-block__title"><?php echo tara_acc_icon( 'spark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> تازه از خواننده‌های شما</h3>
						<a class="ty-acct-link" href="<?php echo esc_url( tara_acc_profile_url( 'follows' ) ); ?>">همه</a>
					</div>
					<?php
					echo tara_acc_track_list( // phpcs:ignore WordPress.Security.EscapeOutput
						array_map(
							function ( $row ) {
								return (int) $row['id'];
							},
							$feed
						),
						array(
							'mode'  => 'feed',
							'title' => 'تازه‌های خواننده‌های شما',
							'empty' => '',
						)
					);
					?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $likes ) ) : ?>
				<div class="ty-acct-block">
					<div class="ty-acct-block__head">
						<h3 class="ty-acct-block__title"><?php echo tara_acc_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> آخرین علاقه‌مندی‌ها</h3>
						<a class="ty-acct-link" href="<?php echo esc_url( tara_acc_profile_url( 'favorites' ) ); ?>">همه</a>
					</div>
					<div class="ty-acct-minis">
						<?php foreach ( $likes as $post_id ) : ?>
							<a class="ty-acct-mini-card" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
								<img src="<?php echo esc_url( tara_track_cover( $post_id, 'thumb3' ) ); ?>" width="72" height="72" alt="" loading="lazy">
								<span class="ty-acct-mini-card__t"><?php echo esc_html( tara_track_title( $post_id ) ); ?></span>
								<span class="ty-acct-mini-card__a"><?php echo esc_html( tara_track_artist( $post_id ) ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( empty( $feed ) && empty( $likes ) ) : ?>
				<div class="ty-acct-start">
					<span class="ty-acct-start__ico"><?php echo tara_acc_icon( 'music' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<h3>از این‌جا شروع کنید</h3>
					<p>روی هر آهنگ قلب بزنید تا این‌جا ذخیره شود، یا یک خواننده را دنبال کنید تا آهنگ‌های تازه‌اش خودکار بیاید.</p>
					<div class="ty-acct-start__btns">
						<a class="ty-acct-btn2" href="<?php echo esc_url( home_url( '/' ) ); ?>">شنیدنِ آهنگ‌های تازه</a>
						<a class="ty-acct-btn2 ty-acct-btn2--ghost" href="<?php echo esc_url( tara_acc_profile_url( 'follows' ) ); ?>">خواننده‌ها</a>
					</div>
				</div>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

/* ---- تب ۲: علاقه‌مندی‌ها ---- */

if ( ! function_exists( 'tara_acc_tab_favorites' ) ) {
	/**
	 * علاقه‌مندی‌ها.
	 *
	 * @param int $user_id شناسه.
	 * @return string
	 */
	function tara_acc_tab_favorites( $user_id ) {
		$ids = tara_acc_likes( (int) $user_id );

		ob_start();
		?>
		<section class="ty-acct-tab">
			<header class="ty-acct-head">
				<div>
					<h2 class="ty-acct-head__title">علاقه‌مندی‌ها</h2>
					<p class="ty-acct-head__sub"><?php echo esc_html( tara_acc_fa( count( $ids ) ) ); ?> آهنگ ذخیره شده — همه در یک صف قابلِ پخش.</p>
				</div>
			</header>
			<?php
			echo tara_acc_track_list( // phpcs:ignore WordPress.Security.EscapeOutput
				$ids,
				array(
					'mode'  => 'favorites',
					'title' => 'علاقه‌مندی‌های من',
					'empty' => 'هنوز آهنگی ذخیره نکرده‌اید؛ روی قلبِ هر آهنگ بزنید.',
				)
			);
			?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

/* ---- تب ۳: پلی‌لیست‌ها ---- */

if ( ! function_exists( 'tara_acc_playlist_cards' ) ) {
	/**
	 * کارت‌های پلی‌لیست.
	 *
	 * @param int   $user_id شناسه.
	 * @param array $playlists فهرست.
	 * @return string
	 */
	function tara_acc_playlist_cards( $user_id, $playlists ) {
		if ( empty( $playlists ) ) {
			return '<p class="ty-acct-empty">هنوز پلی‌لیستی نساخته‌اید. با دکمهٔ بالا یکی بسازید — مثلاً «مسیرِ صبح».</p>';
		}

		$out = '<div class="ty-acct-pls">';
		foreach ( $playlists as $pl ) {
			$count  = count( $pl['t'] );
			$first  = $count > 0 ? (int) $pl['t'][0] : 0;
			$cover  = $first > 0 && function_exists( 'tara_track_cover' ) ? tara_track_cover( $first, 'thumb2' ) : '';
			$url    = tara_acc_profile_url( 'playlists' ) . '&pl=' . rawurlencode( $pl['k'] );
			$share  = tara_acc_profile_url( 'playlists' ) . '&share=' . rawurlencode( $pl['k'] );

			$out .= '<article class="ty-acct-pl" data-tara-pl-card="' . esc_attr( $pl['k'] ) . '">';
			$out .= '<a class="ty-acct-pl__art" href="' . esc_url( $url ) . '">';
			$out .= '' !== $cover
				? '<img src="' . esc_url( $cover ) . '" width="96" height="96" alt="" loading="lazy">'
				: '<span class="ty-acct-pl__ph" aria-hidden="true">' . tara_acc_icon( 'list' ) . '</span>';
			$out .= '<span class="ty-acct-pl__play">' . tara_acc_icon( 'play' ) . '</span>';
			$out .= '</a>';
			$out .= '<div class="ty-acct-pl__body">';
			$out .= '<a class="ty-acct-pl__name" href="' . esc_url( $url ) . '">' . esc_html( $pl['n'] ) . '</a>';
			$out .= '<p class="ty-acct-pl__meta">' . esc_html( tara_acc_fa( $count ) ) . ' آهنگ' . ( $pl['p'] ? ' · عمومی' : ' · خصوصی' ) . '</p>';
			$out .= '<div class="ty-acct-pl__tools">';
			$out .= '<button type="button" class="ty-acct-mini" data-tara-pl-share="' . esc_url( $share ) . '" aria-label="کپیِ لینکِ اشتراک">' . tara_acc_icon( 'link' ) . '</button>';
			$out .= '<button type="button" class="ty-acct-mini' . ( $pl['p'] ? ' is-on' : '' ) . '" data-tara-pl-public="' . esc_attr( $pl['k'] ) . '" data-tara-on="' . ( $pl['p'] ? '1' : '0' ) . '" aria-label="عمومی‌کردنِ پلی‌لیست">' . tara_acc_icon( 'share' ) . '</button>';
			$out .= '<button type="button" class="ty-acct-mini" data-tara-pl-rename="' . esc_attr( $pl['k'] ) . '" aria-label="تغییرِ نام">' . tara_acc_icon( 'edit' ) . '</button>';
			$out .= '<button type="button" class="ty-acct-mini ty-acct-mini--danger" data-tara-pl-delete="' . esc_attr( $pl['k'] ) . '" aria-label="حذفِ پلی‌لیست">' . tara_acc_icon( 'trash' ) . '</button>';
			$out .= '</div></div></article>';
		}
		$out .= '</div>';

		return $out;
	}
}

if ( ! function_exists( 'tara_acc_tab_playlists' ) ) {
	/**
	 * پلی‌لیست‌ها + نمای یک پلی‌لیست.
	 *
	 * @param int $user_id شناسه.
	 * @return string
	 */
	function tara_acc_tab_playlists( $user_id ) {
		$playlists = tara_acc_playlists( (int) $user_id );
		$open      = isset( $_GET['pl'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['pl'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$current   = '' !== $open ? tara_acc_playlist( $open, (int) $user_id ) : null;

		ob_start();
		?>
		<section class="ty-acct-tab">
			<header class="ty-acct-head">
				<div>
					<h2 class="ty-acct-head__title">پلی‌لیست‌ها</h2>
					<p class="ty-acct-head__sub">آهنگ‌ها را دسته‌بندی کنید و لینکِ برنامه را با دوستتان به اشتراک بگذارید.</p>
				</div>
				<form class="ty-acct-newpl" data-tara-pl-new>
					<label class="screen-reader-text" for="ty-acct-newpl-name">نامِ پلی‌لیستِ تازه</label>
					<input id="ty-acct-newpl-name" type="text" name="name" placeholder="نامِ پلی‌لیستِ تازه…" maxlength="60" required>
					<button type="submit" class="ty-acct-btn2"><?php echo tara_acc_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>ساختن</span></button>
				</form>
			</header>

			<?php if ( $current ) : ?>
				<div class="ty-acct-plview">
					<div class="ty-acct-plview__head">
						<a class="ty-acct-back" href="<?php echo esc_url( tara_acc_profile_url( 'playlists' ) ); ?>" aria-label="بازگشت به پلی‌لیست‌ها"><?php echo tara_acc_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
						<div>
							<h3 class="ty-acct-plview__name" data-tara-pl-title="<?php echo esc_attr( $current['k'] ); ?>"><?php echo esc_html( $current['n'] ); ?></h3>
							<p class="ty-acct-plview__meta"><?php echo esc_html( tara_acc_fa( count( $current['t'] ) ) ); ?> آهنگ · <?php echo $current['p'] ? 'عمومی' : 'خصوصی'; ?></p>
						</div>
						<button type="button" class="ty-acct-mini" data-tara-pl-share="<?php echo esc_url( tara_acc_profile_url( 'playlists' ) . '&share=' . rawurlencode( $current['k'] ) ); ?>" aria-label="کپیِ لینکِ اشتراک"><?php echo tara_acc_icon( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					</div>
					<?php
					echo tara_acc_track_list( // phpcs:ignore WordPress.Security.EscapeOutput
						$current['t'],
						array(
							'mode'  => 'playlist',
							'key'   => $current['k'],
							'title' => $current['n'],
							'empty' => 'این پلی‌لیست خالی است؛ از تبِ «علاقه‌مندی‌ها» یا صفحهٔ هر آهنگ اضافه کنید.',
						)
					);
					?>
				</div>
			<?php else : ?>
				<?php echo tara_acc_playlist_cards( (int) $user_id, $playlists ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

/* ---- تب ۴: خواننده‌ها ---- */

if ( ! function_exists( 'tara_acc_tab_follows' ) ) {
	/**
	 * خواننده‌های دنبال‌شده + فید.
	 *
	 * @param int $user_id شناسه.
	 * @return string
	 */
	function tara_acc_tab_follows( $user_id ) {
		$terms = tara_acc_follows( (int) $user_id );
		$feed  = tara_acc_feed( (int) $user_id, array( 'limit' => 8 ) );

		ob_start();
		?>
		<section class="ty-acct-tab">
			<header class="ty-acct-head">
				<div>
					<h2 class="ty-acct-head__title">خواننده‌ها</h2>
					<p class="ty-acct-head__sub"><?php echo esc_html( tara_acc_fa( count( $terms ) ) ); ?> خواننده را دنبال می‌کنید؛ آهنگ‌های تازه‌شان خودکار این‌جا می‌آید.</p>
				</div>
				<a class="ty-acct-btn2 ty-acct-btn2--ghost" href="<?php echo esc_url( function_exists( 'tara_artist_archive_url' ) ? tara_artist_archive_url() : home_url( '/artists/' ) ); ?>">همهٔ خواننده‌ها</a>
			</header>

			<?php
			echo tara_acc_artist_cards( $terms ); // phpcs:ignore WordPress.Security.EscapeOutput
			if ( empty( $terms ) ) {
				echo '<p class="ty-acct-empty">هنوز خواننده‌ای را دنبال نمی‌کنید؛ در صفحهٔ هر خواننده دکمهٔ «دنبال‌کردن» را بزنید.</p>';
			}
			?>

			<?php if ( ! empty( $feed ) ) : ?>
				<div class="ty-acct-block">
					<div class="ty-acct-block__head"><h3 class="ty-acct-block__title">آهنگ‌های تازه</h3></div>
					<?php
					echo tara_acc_track_list( // phpcs:ignore WordPress.Security.EscapeOutput
						array_map(
							function ( $row ) {
								return (int) $row['id'];
							},
							$feed
						),
						array(
							'mode'  => 'feed',
							'title' => 'تازه‌های دنبال‌شده‌ها',
							'empty' => '',
						)
					);
					?>
				</div>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

/* ---- تب ۵: تاریخچه ---- */

if ( ! function_exists( 'tara_acc_tab_history' ) ) {
	/**
	 * تاریخچهٔ شنیدن.
	 *
	 * @param int $user_id شناسه.
	 * @return string
	 */
	function tara_acc_tab_history( $user_id ) {
		$rows = tara_acc_history( (int) $user_id );
		$ids  = array_map(
			function ( $row ) {
				return (int) $row['i'];
			},
			$rows
		);

		ob_start();
		?>
		<section class="ty-acct-tab">
			<header class="ty-acct-head">
				<div>
					<h2 class="ty-acct-head__title">تاریخچهٔ شنیدن</h2>
					<p class="ty-acct-head__sub">۱۵۰ آهنگِ آخرِ شما. فقط خودتان می‌بینید.</p>
				</div>
				<?php if ( ! empty( $ids ) ) : ?>
					<button type="button" class="ty-acct-btn2 ty-acct-btn2--ghost" data-tara-history-clear>پاک‌کردنِ تاریخچه</button>
				<?php endif; ?>
			</header>
			<?php
			echo tara_acc_track_list( // phpcs:ignore WordPress.Security.EscapeOutput
				$ids,
				array(
					'mode'  => 'history',
					'title' => 'تاریخچهٔ من',
					'empty' => 'هنوز چیزی نشنیده‌اید. رادیو یا هر آهنگ را پخش کنید تا این‌جا ثبت شود.',
				)
			);
			?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

/* ---- تب ۶: پیام‌ها ---- */

if ( ! function_exists( 'tara_acc_tab_notifs' ) ) {
	/**
	 * پیام‌ها و اعلان‌ها.
	 *
	 * @param int $user_id شناسه.
	 * @return string
	 */
	function tara_acc_tab_notifs( $user_id ) {
		$list = tara_acc_notifs( (int) $user_id );
		$feed = tara_acc_feed( (int) $user_id, array( 'limit' => 6 ) );

		ob_start();
		?>
		<section class="ty-acct-tab">
			<header class="ty-acct-head">
				<div>
					<h2 class="ty-acct-head__title">پیام‌ها</h2>
					<p class="ty-acct-head__sub">خبرهای حساب و آهنگ‌های تازهٔ خواننده‌هایی که دنبال می‌کنید.</p>
				</div>
				<?php if ( ! empty( $list ) ) : ?>
					<button type="button" class="ty-acct-btn2 ty-acct-btn2--ghost" data-tara-read-all>خوانده‌شدنِ همه</button>
				<?php endif; ?>
			</header>

			<?php if ( ! empty( $list ) ) : ?>
				<ul class="ty-acct-notifs">
					<?php foreach ( $list as $row ) : ?>
						<li class="ty-acct-notif<?php echo empty( $row['r'] ) ? ' is-new' : ''; ?>">
							<span class="ty-acct-notif__ico"><?php echo tara_acc_icon( 'bell' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<div class="ty-acct-notif__body">
								<?php if ( ! empty( $row['u'] ) ) : ?>
									<a class="ty-acct-notif__txt" href="<?php echo esc_url( (string) $row['u'] ); ?>"><?php echo esc_html( (string) $row['n'] ); ?></a>
								<?php else : ?>
									<span class="ty-acct-notif__txt"><?php echo esc_html( (string) $row['n'] ); ?></span>
								<?php endif; ?>
								<time class="ty-acct-notif__time"><?php echo esc_html( tara_acc_date( isset( $row['t'] ) ? (int) $row['t'] : 0, 'j F · H:i' ) ); ?></time>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="ty-acct-empty">پیامِ تازه‌ای ندارید.</p>
			<?php endif; ?>

			<?php if ( ! empty( $feed ) ) : ?>
				<div class="ty-acct-block">
					<div class="ty-acct-block__head"><h3 class="ty-acct-block__title">تازه‌های خواننده‌های شما</h3></div>
					<?php
					echo tara_acc_track_list( // phpcs:ignore WordPress.Security.EscapeOutput
						array_map(
							function ( $row ) {
								return (int) $row['id'];
							},
							$feed
						),
						array(
							'mode'  => 'feed',
							'title' => 'تازه‌های دنبال‌شده‌ها',
							'empty' => '',
						)
					);
					?>
				</div>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

/* ---- تب ۷: تلگرام ---- */

if ( ! function_exists( 'tara_acc_tab_telegram' ) ) {
	/**
	 * اتصال به رباتِ تلگرام.
	 *
	 * @param int $user_id شناسه.
	 * @return string
	 */
	function tara_acc_tab_telegram( $user_id ) {
		$tg_id   = (int) get_user_meta( (int) $user_id, 'tara_tg_id', true );
		$tg_user = (string) get_user_meta( (int) $user_id, 'tara_tg_user', true );
		$bot     = preg_replace( '/[^A-Za-z0-9_]/', '', (string) tara_acc_opt( 'tg_bot' ) );
		$at      = (int) get_user_meta( (int) $user_id, 'tara_tg_at', true );

		ob_start();
		?>
		<section class="ty-acct-tab">
			<header class="ty-acct-head">
				<div>
					<h2 class="ty-acct-head__title">اتصال به رباتِ تلگرام</h2>
					<p class="ty-acct-head__sub">با یک کدِ هشت‌حرفی، حسابِ سایت به تلگرامِ شما وصل می‌شود و اعلانِ آهنگ‌های تازه را همان‌جا می‌گیرید.</p>
				</div>
			</header>

			<div class="ty-acct-tg">
				<span class="ty-acct-tg__ico"><?php echo tara_acc_icon( 'telegram' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>

				<?php if ( $tg_id > 0 ) : ?>
					<h3 class="ty-acct-tg__title">وصل است ✔</h3>
					<p class="ty-acct-tg__sub">
						تلگرامِ شما به این حساب وصل است<?php echo '' !== $tg_user ? ' (<span dir="ltr">@' . esc_html( $tg_user ) . '</span>)' : ''; ?>.
						<?php if ( $at > 0 ) : ?>
							از <?php echo esc_html( tara_acc_date( $at, 'j F Y' ) ); ?>.
						<?php endif; ?>
					</p>
					<div class="ty-acct-tg__btns">
						<?php if ( '' !== $bot ) : ?>
							<a class="ty-acct-btn2" href="<?php echo esc_url( 'https://t.me/' . $bot ); ?>" target="_blank" rel="noopener nofollow">بازکردنِ ربات</a>
						<?php endif; ?>
						<button type="button" class="ty-acct-btn2 ty-acct-btn2--ghost" data-tara-tg-unlink>قطعِ اتصال</button>
					</div>
				<?php else : ?>
					<h3 class="ty-acct-tg__title">هنوز وصل نیست</h3>
					<p class="ty-acct-tg__sub">مرحله‌ها ساده است: کد را بگیرید، در ربات بفرستید، تمام.</p>
					<ol class="ty-acct-tg__steps">
						<li><span>۱</span> دکمهٔ «ساختِ کدِ اتصال» را بزنید.</li>
						<li><span>۲</span> ربات را باز کنید و همان کد را بفرستید (یا روی لینکِ مستقیم بزنید).</li>
						<li><span>۳</span> همین صفحه را تازه کنید؛ وضعیت «وصل است» می‌شود.</li>
					</ol>
					<div class="ty-acct-tg__btns">
						<button type="button" class="ty-acct-btn2" data-tara-tg-code>ساختِ کدِ اتصال</button>
						<?php if ( '' !== $bot ) : ?>
							<a class="ty-acct-btn2 ty-acct-btn2--ghost" href="<?php echo esc_url( 'https://t.me/' . $bot ); ?>" target="_blank" rel="noopener nofollow">رفتن به ربات</a>
						<?php else : ?>
							<span class="ty-acct-tg__hint">نامِ ربات هنوز در تنظیمات قالب وارد نشده است.</span>
						<?php endif; ?>
					</div>
					<div class="ty-acct-tg__code" data-tara-tg-box hidden>
						<span class="ty-acct-tg__codeval" data-tara-tg-value dir="ltr"></span>
						<button type="button" class="ty-acct-mini" data-tara-tg-copy aria-label="کپیِ کد"><?php echo tara_acc_icon( 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
						<a class="ty-acct-link" data-tara-tg-link hidden href="#" target="_blank" rel="noopener nofollow">بازکردنِ ربات با همین کد</a>
						<span class="ty-acct-tg__ttl">این کد ۱۰ دقیقه معتبر است.</span>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

/* ---- تب ۸: مشخصات ---- */

if ( ! function_exists( 'tara_acc_tab_profile' ) ) {
	/**
	 * ویرایشِ مشخصات.
	 *
	 * @param int $user_id شناسه.
	 * @return string
	 */
	function tara_acc_tab_profile( $user_id ) {
		$name   = (string) get_user_meta( (int) $user_id, 'nickname', true );
		$bio    = (string) get_user_meta( (int) $user_id, 'description', true );
		$phone  = tara_acc_user_phone( (int) $user_id );
		$avatar = tara_acc_avatar_url( (int) $user_id, 160 );

		ob_start();
		?>
		<section class="ty-acct-tab">
			<header class="ty-acct-head">
				<div>
					<h2 class="ty-acct-head__title">مشخصات</h2>
					<p class="ty-acct-head__sub">نام و توضیحِ کوتاه؛ همین‌ها کنارِ نظرات و پلی‌لیست‌های عمومی دیده می‌شود.</p>
				</div>
			</header>

			<form class="ty-acct-profile" data-tara-form="profile" novalidate>
				<div class="ty-acct-profile__top">
					<span class="ty-acct__ava ty-acct__ava--lg">
						<img src="<?php echo esc_url( $avatar ); ?>" width="96" height="96" alt="" data-tara-avatar-img>
					</span>
					<div class="ty-acct-profile__meta">
						<p class="ty-acct-profile__phone">شمارهٔ شما: <b dir="ltr"><?php echo esc_html( tara_acc_phone_pretty( $phone ) ); ?></b></p>
						<p class="ty-acct-profile__note">شماره قابلِ تغییر نیست و هیچ‌جا عمومی نشان داده نمی‌شود.</p>
						<label class="ty-acct-field ty-acct-field--inline">
							<span class="ty-acct-field__label">آدرسِ تصویرِ پروفایل</span>
							<span class="ty-acct-field__box">
								<input type="url" name="avatar" dir="ltr" placeholder="https://…" value="<?php echo esc_attr( (string) get_user_meta( (int) $user_id, 'tara_avatar_url', true ) ); ?>">
								<?php echo tara_acc_icon( 'link', 'ty-acct-field__ico' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</span>
						</label>
					</div>
				</div>

				<label class="ty-acct-field">
					<span class="ty-acct-field__label">نامِ نمایشی</span>
					<span class="ty-acct-field__box">
						<input type="text" name="name" maxlength="50" value="<?php echo esc_attr( $name ); ?>" required>
						<?php echo tara_acc_icon( 'user', 'ty-acct-field__ico' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</span>
				</label>

				<label class="ty-acct-field">
					<span class="ty-acct-field__label">دربارهٔ من <em>(اختیاری)</em></span>
					<span class="ty-acct-field__box ty-acct-field__box--area">
						<textarea name="bio" rows="4" maxlength="300" placeholder="مثلاً: عاشقِ موسیقیِ سنتی و میکس‌های شبانه…"><?php echo esc_textarea( $bio ); ?></textarea>
					</span>
				</label>

				<div class="ty-acct-form__foot">
					<button type="submit" class="ty-acct-btn2">
						<?php echo tara_acc_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span>ذخیرهٔ تغییرات</span>
					</button>
					<p class="ty-acct-form__msg" role="status" aria-live="polite" data-tara-msg></p>
				</div>
			</form>

			<div class="ty-acct-danger">
				<h3 class="ty-acct-danger__title">خروج از حساب</h3>
				<p class="ty-acct-danger__sub">روی این دستگاه از حساب خارج می‌شوید؛ داده‌هایتان می‌ماند.</p>
				<button type="button" class="ty-acct-btn2 ty-acct-btn2--ghost" data-tara-logout>خروج</button>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

/* -------------------------------------------------------------------------
 * ۷) نمای عمومیِ پلی‌لیستِ اشتراکی
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_public_playlist' ) ) {
	/**
	 * پلی‌لیستی که با لینک به اشتراک گذاشته شده (بدونِ ورود).
	 *
	 * لینک فقط زمانی کار می‌کند که سازنده پلی‌لیست را «عمومی» کرده باشد؛
	 * در غیرِ این صورت پیامِ مؤدبانه و لینکِ ورود نشان داده می‌شود.
	 *
	 * @param string $key کلید.
	 * @return string
	 */
	function tara_acc_public_playlist( $key ) {
		$key = sanitize_text_field( (string) $key );
		if ( '' === $key || ! function_exists( 'get_users' ) ) {
			return '';
		}

		$found = null;
		$owner = 0;

		/* جست‌وجو در متای کاربران — پلی‌لیست‌ها کوچک‌اند و تعدادِ کاربران
		   در سایت‌های موسیقی محدود است؛ با شرطِ متا سریع است. */
		$candidates = get_users(
			array(
				'meta_key'    => 'tara_playlists', // phpcs:ignore WordPress.DB.SlowDBQuery
				'number'      => 200,
				'count_total' => false,
				'fields'      => array( 'ID' ),
			)
		);
		foreach ( (array) $candidates as $cand ) {
			$uid = is_object( $cand ) ? (int) $cand->ID : (int) $cand;
			$pl  = tara_acc_playlist( $key, $uid );
			if ( $pl ) {
				$found = $pl;
				$owner = $uid;
				break;
			}
		}

		if ( ! $found ) {
			return '<p class="ty-acct-empty">این پلی‌لیست پیدا نشد یا حذف شده است.</p>';
		}
		if ( empty( $found['p'] ) ) {
			return '<div class="ty-acct-locked"><p>این پلی‌لیست خصوصی است.</p>'
				. '<a class="ty-acct-btn2" href="' . esc_url( tara_acc_profile_url() ) . '">ورود به حساب</a></div>';
		}

		$owner_name = (string) get_user_meta( $owner, 'nickname', true );

		ob_start();
		?>
		<section class="ty-acct-public">
			<header class="ty-acct-public__head">
				<span class="ty-acct-public__kicker"><?php echo tara_acc_icon( 'share' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> پلی‌لیستِ اشتراکی</span>
				<h1 class="ty-acct-public__title"><?php echo esc_html( $found['n'] ); ?></h1>
				<p class="ty-acct-public__meta">
					<?php echo esc_html( tara_acc_fa( count( $found['t'] ) ) ); ?> آهنگ
					<?php echo '' !== $owner_name ? ' · ساختهٔ ' . esc_html( $owner_name ) : ''; ?>
				</p>
			</header>
			<?php
			echo tara_acc_track_list( // phpcs:ignore WordPress.Security.EscapeOutput
				$found['t'],
				array(
					'mode'  => 'public',
					'title' => $found['n'],
					'empty' => 'این پلی‌لیست خالی است.',
				)
			);
			?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

/* -------------------------------------------------------------------------
 * ۸) قلاب‌ها در بقیهٔ قالب (صفحهٔ آهنگ و صفحهٔ خواننده)
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_single_actions' ) ) {
	/**
	 * مارکاپِ دکمه‌های حساب برای صفحهٔ آهنگ (از single.php صدا زده می‌شود).
	 *
	 * @param int $post_id شناسه.
	 * @return string
	 */
	function tara_acc_single_actions( $post_id = 0 ) {
		$post_id = (int) $post_id;
		if ( ! tara_acc_on() || $post_id <= 0 ) {
			return '';
		}
		$html = '<div class="ty-acct-actions">';
		$html .= tara_acc_like_button( $post_id, array( 'class' => 'ty-acct-like--lg' ) );
		if ( tara_acc_feature( 'playlists' ) ) {
			$html .= '<button type="button" class="ty-acct-like ty-acct-like--lg" data-tara-pl-open="' . esc_attr( $post_id ) . '" aria-haspopup="dialog">'
				. tara_acc_icon( 'plus' ) . '<span class="ty-acct-like__txt">پلی‌لیست</span></button>';
		}
		$html .= '</div>';

		return $html;
	}
}

if ( ! function_exists( 'tara_acc_bot_song_button' ) ) {
	/**
	 * دکمهٔ «مشاهده در ربات تلگرام» برای یک آهنگ.
	 *
	 * کاربر با یک لمس از سایت به همان آهنگ در ربات می‌رود
	 * (`https://t.me/<bot>?start=song_<id>`) و آنجا پخش، دانلود و
	 * ذخیره‌کردن در علاقه‌مندی‌ها را دارد. ربات هم با همان حسابِ تلگرامی
	 * که قبلاً به سایت وصل کرده شناخته می‌شود.
	 *
	 * @param int   $post_id شناسهٔ آهنگ (۰ = آهنگِ جاری).
	 * @param array $args    متن‌ها و کلاس.
	 * @return string
	 */
	function tara_acc_bot_song_button( $post_id = 0, $args = array() ) {
		$post_id = (int) $post_id;
		if ( 0 === $post_id ) {
			$post_id = (int) get_the_ID();
		}
		$url = function_exists( 'tara_acc_bot_song_url' ) ? tara_acc_bot_song_url( $post_id ) : '';
		if ( '' === $url ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'class' => '',
				'label' => 'مشاهده در ربات تلگرام',
				'note'  => 'پخش، دانلود و ذخیره در تلگرام — بدونِ باز کردنِ سایت',
			)
		);

		$class = 'ty-bot-song__btn';
		if ( '' !== (string) $args['class'] ) {
			$class .= ' ' . sanitize_html_class( (string) $args['class'] );
		}

		$html  = '<div class="ty-bot-song">';
		$html .= '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '" target="_blank" rel="noopener nofollow"'
			. ' data-tara-bot-song="' . esc_attr( $post_id ) . '">';
		$html .= '<span class="ty-bot-song__ico" aria-hidden="true">' . tara_acc_icon( 'telegram' ) . '</span>';
		$html .= '<span class="ty-bot-song__body">';
		$html .= '<span class="ty-bot-song__title">' . esc_html( $args['label'] ) . '</span>';
		if ( '' !== (string) $args['note'] ) {
			$html .= '<span class="ty-bot-song__note">' . esc_html( $args['note'] ) . '</span>';
		}
		$html .= '</span>';
		$html .= '<span class="ty-bot-song__arrow" aria-hidden="true">'
			. '<svg class="ty-acct__svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-6 6 6 6"/></svg>'
			. '</span>';
		$html .= '</a></div>';

		return $html;
	}
}

if ( ! function_exists( 'tara_acc_playlist_picker' ) ) {
	/**
	 * مودالِ «افزودن به پلی‌لیست» (یک‌بار در پاصفحه).
	 *
	 * @return void
	 */
	function tara_acc_playlist_picker() {
		if ( ! tara_acc_on() || ! tara_acc_feature( 'playlists' ) || ! is_user_logged_in() ) {
			return;
		}
		$playlists = tara_acc_playlists( (int) get_current_user_id() );
		?>
		<div class="ty-acct-sheet" id="ty-acct-sheet" data-tara-pl-sheet hidden>
			<div class="ty-acct-sheet__veil" data-tara-close></div>
			<div class="ty-acct-sheet__card" role="dialog" aria-modal="true" aria-labelledby="ty-acct-sheet-title">
				<header class="ty-acct-sheet__head">
					<h2 id="ty-acct-sheet-title">افزودن به پلی‌لیست</h2>
					<button type="button" class="ty-acct-modal__x" data-tara-close aria-label="بستن">
						<?php echo tara_acc_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</button>
				</header>
				<ul class="ty-acct-sheet__list" data-tara-pl-list>
					<?php if ( empty( $playlists ) ) : ?>
						<li class="ty-acct-sheet__empty">هنوز پلی‌لیستی ندارید.</li>
					<?php else : ?>
						<?php foreach ( $playlists as $pl ) : ?>
							<li>
								<button type="button" class="ty-acct-sheet__row" data-tara-pl-add="<?php echo esc_attr( $pl['k'] ); ?>">
									<span class="ty-acct-sheet__ico"><?php echo tara_acc_icon( 'list' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
									<span class="ty-acct-sheet__name"><?php echo esc_html( $pl['n'] ); ?></span>
									<span class="ty-acct-sheet__count"><?php echo esc_html( tara_acc_fa( count( $pl['t'] ) ) ); ?></span>
									<span class="ty-acct-sheet__plus"><?php echo tara_acc_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								</button>
							</li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
				<form class="ty-acct-sheet__new" data-tara-pl-new>
					<label class="screen-reader-text" for="ty-acct-sheet-name">نامِ پلی‌لیستِ تازه</label>
					<input id="ty-acct-sheet-name" type="text" name="name" placeholder="پلی‌لیستِ تازه…" maxlength="60" required>
					<button type="submit" class="ty-acct-btn2">ساختن و افزودن</button>
				</form>
				<p class="ty-acct-form__msg" role="status" aria-live="polite" data-tara-msg></p>
			</div>
		</div>
		<?php
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_footer', 'tara_acc_playlist_picker', 41 );
}

/* -------------------------------------------------------------------------
 * ۹) دکمهٔ هدر — در همهٔ صفحه‌ها (دسکتاپ و موبایل)
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_header_button' ) ) {
	/**
	 * چاپِ دکمهٔ حساب در هدر قالب.
	 *
	 * @param string $context محل.
	 * @return void
	 */
	function tara_acc_header_button( $context = 'desktop' ) {
		echo tara_acc_button( $context ); // phpcs:ignore WordPress.Security.EscapeOutput -- در تابع escape شده
	}
}

/* -------------------------------------------------------------------------
 * ۱۰) شورت‌کدها — برای هر برگه‌ای که با «برگهٔ تازه» ساخته می‌شود
 *     [tara_account]  → مهمان: کارتِ ورود، کاربر: پنلِ کامل (با ?tab=)
 *     [tara_login]    → همیشه کارتِ ورود (برای برگهٔ «ورود و ثبت‌نام»)
 *     [tara_account_button] → فقط دکمهٔ هدر/منو
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_acc_shortcode_account' ) ) {
	/**
	 * `[tara_account]` — پنلِ کاربریِ کامل یا کارتِ ورود.
	 *
	 * @param array $atts ویژگی‌ها: tab، class، title.
	 * @return string
	 */
	function tara_acc_shortcode_account( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'tab'   => '',
				'class' => '',
				'title' => '',
			),
			(array) $atts,
			'tara_account'
		);

		if ( ! tara_acc_on() ) {
			return '';
		}

		/* تبِ مشخص‌شده در شورت‌کد، اگر منطقی بود، جای `?tab=` می‌نشیند. */
		if ( '' !== $atts['tab'] ) {
			$tab  = sanitize_key( $atts['tab'] );
			$tabs = tara_acc_tabs();
			if ( isset( $tabs[ $tab ] ) && ! empty( $tabs[ $tab ]['on'] ) ) {
				$_GET['tab'] = $tab; // phpcs:ignore WordPress.Security.NonceVerification
			}
		}

		ob_start();
		if ( is_user_logged_in() ) {
			echo tara_acc_panel(); // phpcs:ignore WordPress.Security.EscapeOutput -- در تابع escape شده
		} else {
			echo tara_acc_login_card(); // phpcs:ignore WordPress.Security.EscapeOutput -- در تابع escape شده
		}
		$html = (string) ob_get_clean();

		$classes = 'ty-acct-sc ty-acct-sc--account';
		if ( '' !== $atts['class'] ) {
			$classes .= ' ' . sanitize_html_class( $atts['class'] );
		}

		$out = '';
		if ( '' !== $atts['title'] ) {
			$out .= '<h2 class="ty-acct-sc__title">' . esc_html( $atts['title'] ) . '</h2>';
		}
		$out .= '<div class="' . esc_attr( $classes ) . '">' . $html . '</div>';

		return $out;
	}
}

if ( ! function_exists( 'tara_acc_shortcode_login' ) ) {
	/**
	 * `[tara_login]` — کارتِ ورود (و اگر کاربر وارد شده باشد، پیامِ کوتاه).
	 *
	 * @param array $atts ویژگی‌ها: redirect، title.
	 * @return string
	 */
	function tara_acc_shortcode_login( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'redirect' => '',
				'title'    => '',
			),
			(array) $atts,
			'tara_login'
		);

		if ( ! tara_acc_on() ) {
			return '';
		}

		$html = '';
		if ( '' !== $atts['title'] ) {
			$html .= '<h2 class="ty-acct-sc__title">' . esc_html( $atts['title'] ) . '</h2>';
		}

		if ( is_user_logged_in() ) {
			$url   = '' !== $atts['redirect'] ? $atts['redirect'] : tara_acc_profile_url();
			$name  = function_exists( 'wp_get_current_user' ) ? (string) wp_get_current_user()->display_name : '';
			$html .= '<div class="ty-acct-guest ty-acct-guest--in">'
				. '<p class="ty-acct-guest__in">خوش آمدید' . ( '' !== $name ? '، ' . esc_html( $name ) : '' ) . '!</p>'
				. '<a class="ty-acct-guest__btn" href="' . esc_url( $url ) . '">رفتن به حسابِ من</a>'
				. '</div>';
		} else {
			$html .= '<div class="ty-acct-sc ty-acct-sc--login">' . tara_acc_login_card() . '</div>';
		}

		return $html;
	}
}

if ( ! function_exists( 'tara_acc_shortcode_button' ) ) {
	/**
	 * `[tara_account_button]` — دکمهٔ حساب برای برگه‌های دلخواه.
	 *
	 * @param array $atts ویژگی‌ها: context.
	 * @return string
	 */
	function tara_acc_shortcode_button( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'context' => 'desktop',
			),
			(array) $atts,
			'tara_account_button'
		);
		$ctx  = 'mobile' === $atts['context'] ? 'mobile' : 'desktop';
		return '<div class="ty-acct-sc ty-acct-sc--btn">' . tara_acc_button( $ctx ) . '</div>';
	}
}

if ( function_exists( 'add_shortcode' ) ) {
	add_shortcode( 'tara_account', 'tara_acc_shortcode_account' );
	add_shortcode( 'tara_login', 'tara_acc_shortcode_login' );
	add_shortcode( 'tara_account_button', 'tara_acc_shortcode_button' );
}
