<?php
/**
 * نشانِ تأییدِ هنرمند + مدیریتِ تعدادِ دنبال‌کننده‌ها (v3.12.0)
 * =========================================================================
 * دو چیز در این فایل است و هر دو جای دیگری هم استفاده می‌شوند:
 *
 * ۱) تیکِ آبیِ هنرمندِ تأییدشده
 *    • منبعِ حقیقت: ترم‌متای `tara_verified` روی ترمِ هنرمند.
 *    • هر جا نامِ هنرمند چاپ می‌شود (سربرگِ صفحهٔ هنرمند، کارت‌ها، ردیفِ
 *      آهنگ‌ها، دیدگاه‌ها) همان تابع `tara_verified_badge()` صدا زده می‌شود
 *      تا نشان یک‌شکل باشد.
 *    • در دیدگاه‌ها: اگر نویسندهٔ دیدگاه کاربری باشد که به یک ترمِ تأییدشده
 *      وصل است (`tara_artist_term`)، نشان کنارِ نامش می‌آید؛ یعنی مخاطب
 *      مطمئن می‌شود «این همان اکانتِ خودِ هنرمند است».
 *
 * ۲) تعدادِ دنبال‌کننده‌ی قابلِ‌تنظیم + تاریخچه + هشدارِ مدیر
 *    • عددِ نمایشی در `tara_followers` است و مدیر می‌تواند از پیشخوانِ ترم
 *      عوضش کند (مثلِ همان چیزی که اینستاگرام به کسب‌وکارها می‌دهد).
 *    • هر تغییر در `tara_follower_history` ثبت می‌شود (حداکثر ۳۰ مورد) و
 *      بالاترین عدد در `tara_follower_peak` نگه داشته می‌شود.
 *    • اگر عددی از رکوردِ همیشگی پایین‌تر بیاید، به مدیر هشدار داده می‌شود
 *      («ممکن است اشتباه تایپی باشد») — همان چیزی که خواسته شد.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * ۱) نشانِ تأیید
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_trust_on' ) ) {
	/** آیا نشانِ تأیید در سایت فعال است؟ (تنظیماتِ قالب → هنرمندان) */
	function tara_trust_on() {
		if ( ! function_exists( 'ot_get_option' ) ) {
			return true;
		}
		return 'off' !== (string) ot_get_option( 'artist_verified_on', 'on' );
	}
}

if ( ! function_exists( 'tara_artist_is_verified' ) ) {
	/**
	 * آیا این هنرمند تأیید شده است؟
	 *
	 * @param int|WP_Term $term ترم یا شناسه.
	 * @return bool
	 */
	function tara_artist_is_verified( $term ) {
		if ( ! function_exists( 'get_term_meta' ) ) {
			return false;
		}
		$term_id = is_object( $term ) && isset( $term->term_id ) ? (int) $term->term_id : (int) $term;
		if ( $term_id <= 0 ) {
			return false;
		}
		return '1' === (string) get_term_meta( $term_id, 'tara_verified', true );
	}
}

if ( ! function_exists( 'tara_artist_verify' ) ) {
	/**
	 * ثبت/برداشتنِ تأییدِ هنرمند.
	 *
	 * @param int  $term_id شناسهٔ ترم.
	 * @param bool $on      وضعیت.
	 * @return bool
	 */
	function tara_artist_verify( $term_id, $on = true ) {
		$term_id = (int) $term_id;
		if ( $term_id <= 0 || ! function_exists( 'update_term_meta' ) ) {
			return false;
		}
		if ( $on ) {
			update_term_meta( $term_id, 'tara_verified', '1' );
			update_term_meta( $term_id, 'tara_verified_at', time() );
		} else {
			delete_term_meta( $term_id, 'tara_verified' );
			delete_term_meta( $term_id, 'tara_verified_at' );
		}
		return true;
	}
}

if ( ! function_exists( 'tara_verified_badge' ) ) {
	/**
	 * مارکاپِ تیکِ آبی.
	 *
	 * @param array $args size (sm|md|lg)، class، title.
	 * @return string خالی اگر تأیید غیرفعال باشد.
	 */
	function tara_verified_badge( $args = array() ) {
		if ( ! tara_trust_on() ) {
			return '';
		}
		$args = wp_parse_args(
			$args,
			array(
				'size'  => 'md',
				'class' => '',
				'title' => 'هنرمندِ تأییدشده',
			)
		);

		$class = 'ty-verified ty-verified--' . sanitize_html_class( (string) $args['size'] );
		if ( '' !== (string) $args['class'] ) {
			$class .= ' ' . sanitize_html_class( (string) $args['class'] );
		}

		// Google Material Icons "verified" (Apache-2.0); notice and license in licenses/.
		return '<span class="' . esc_attr( $class ) . '" role="img" aria-label="' . esc_attr( $args['title'] ) . '" title="' . esc_attr( $args['title'] ) . '">'
			. '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">'
			. '<path fill="currentColor" d="M23 12l-2.44-2.79.34-3.69-3.61-.82L15.4 1.5 12 2.96 8.6 1.5 6.71 4.69 3.1 5.5l.34 3.7L1 12l2.44 2.79-.34 3.7 3.61.82L8.6 22.5l3.4-1.47 3.4 1.46 1.89-3.19 3.61-.82-.34-3.69z"/>'
			. '<path d="m7.6 12.4 3 3 5.9-6" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>'
			. '</svg></span>';

	}
}

if ( ! function_exists( 'tara_artist_verified_badge' ) ) {
	/**
	 * نشانِ تأیید برای یک ترم — خالی اگر تأیید نشده باشد.
	 *
	 * @param int|WP_Term $term ترم.
	 * @param array       $args تنظیم‌ها.
	 * @return string
	 */
	function tara_artist_verified_badge( $term, $args = array() ) {
		if ( ! tara_artist_is_verified( $term ) ) {
			return '';
		}
		return tara_verified_badge( $args );
	}
}

if ( ! function_exists( 'tara_artist_term_of_user' ) ) {
	/**
	 * ترمِ هنرمندی که این کاربر مالکش است (۰ = هیچ).
	 *
	 * @param int $user_id شناسهٔ کاربر.
	 * @return int
	 */
	function tara_artist_term_of_user( $user_id = 0 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		if ( $user_id <= 0 || ! function_exists( 'get_user_meta' ) ) {
			return 0;
		}
		$term_id = max( 0, (int) get_user_meta( $user_id, 'tara_artist_term', true ) );
		return $term_id && (int) get_term_meta( $term_id, 'tara_artist_user', true ) === $user_id ? $term_id : 0;
	}
}

if ( ! function_exists( 'tara_artist_term_user' ) ) {
	/**
	 * کاربرِ متصل به یک ترمِ هنرمند (۰ = هیچ).
	 *
	 * @param int $term_id شناسهٔ ترم.
	 * @return int
	 */
	function tara_artist_term_user( $term_id ) {
		$term_id = (int) $term_id;
		if ( $term_id <= 0 || ! function_exists( 'get_term_meta' ) ) {
			return 0;
		}
		$direct = (int) get_term_meta( $term_id, 'tara_artist_user', true );
		if ( $direct > 0 ) {
			return $direct;
		}
		// مسیرِ پشتیبان: اولین کاربری که ترم را به نامِ خود ثبت کرده است.
		if ( function_exists( 'get_users' ) ) {
			$users = get_users(
				array(
					'meta_key'   => 'tara_artist_term',
					'meta_value' => $term_id,
					'number'     => 1,
					'fields'     => 'ID',
				)
			);
			if ( ! empty( $users ) ) {
				return (int) $users[0];
			}
		}
		return 0;
	}
}

if ( ! function_exists( 'tara_user_verified_badge' ) ) {
	/**
	 * نشانِ تأیید برای کاربر (اگر صاحبِ یک هنرمندِ تأییدشده باشد).
	 *
	 * @param int   $user_id شناسهٔ کاربر.
	 * @param array $args    تنظیم‌ها.
	 * @return string
	 */
	function tara_user_verified_badge( $user_id = 0, $args = array() ) {
		$term_id = tara_artist_term_of_user( (int) $user_id );
		if ( $term_id <= 0 ) {
			return '';
		}
		return tara_artist_verified_badge( $term_id, $args );
	}
}

if ( ! function_exists( 'tara_comment_author_verified' ) ) {
	/**
	 * تیکِ آبیِ نویسندهٔ دیدگاه — روی فیلترِ `get_comment_author_link`.
	 *
	 * فقط وقتی نشان داده می‌شود که دیدگاه به یک کاربرِ ثبت‌نام‌شده تعلق
	 * داشته باشد و آن کاربر صاحبِ هنرمندِ تأییدشده باشد. دیدگاه‌های مهمان
	 * هیچ نشان نمی‌گیرند (وگرنه قابلِ جعل بود).
	 *
	 * @param string $link پیوندِ نویسنده.
	 * @param int    $comment_id شناسهٔ دیدگاه.
	 * @return string
	 */
	function tara_comment_author_verified( $link, $comment_id = 0 ) {
		if ( ! tara_trust_on() ) {
			return $link;
		}
		$comment = function_exists( 'get_comment' ) ? get_comment( $comment_id ) : null;
		if ( ! $comment || empty( $comment->user_id ) ) {
			return $link;
		}
		$badge = tara_user_verified_badge( (int) $comment->user_id, array( 'size' => 'sm' ) );
		if ( '' === $badge ) {
			return $link;
		}
		return $link . $badge;
	}
}

if ( ! function_exists( 'tara_trust_filters' ) ) {
	/** سیم‌کشیِ فیلترهای وردپرس. */
	function tara_trust_filters() {
		add_filter( 'get_comment_author_link', 'tara_comment_author_verified', 10, 2 );
	}
}
tara_trust_filters();

/* -------------------------------------------------------------------------
 * ۲) تعدادِ دنبال‌کننده‌ها: مقدارِ نمایشی، تاریخچه و رکورد
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_artist_followers' ) ) {
	/**
	 * تعدادِ نمایشیِ دنبال‌کننده‌ها.
	 *
	 * @param int|WP_Term $term ترم.
	 * @return int
	 */
	function tara_artist_followers( $term ) {
		$term_id = is_object( $term ) && isset( $term->term_id ) ? (int) $term->term_id : (int) $term;
		if ( $term_id <= 0 || ! function_exists( 'get_term_meta' ) ) {
			return 0;
		}
		return max( 0, (int) get_term_meta( $term_id, 'tara_followers', true ) );
	}
}

if ( ! function_exists( 'tara_artist_follower_peak' ) ) {
	/**
	 * بالاترین عددی که این هنرمند تا حالا داشته است.
	 *
	 * @param int $term_id شناسهٔ ترم.
	 * @return int
	 */
	function tara_artist_follower_peak( $term_id ) {
		$term_id = (int) $term_id;
		if ( $term_id <= 0 || ! function_exists( 'get_term_meta' ) ) {
			return 0;
		}
		$peak = (int) get_term_meta( $term_id, 'tara_follower_peak', true );
		return max( $peak, tara_artist_followers( $term_id ) );
	}
}

if ( ! function_exists( 'tara_artist_follower_history' ) ) {
	/**
	 * تاریخچهٔ تغییرهای تعدادِ دنبال‌کننده (تازه‌ترین اول).
	 *
	 * @param int $term_id شناسهٔ ترم.
	 * @return array[] هر آیتم: t (زمان)، n (عدد)، src (منبع)، note.
	 */
	function tara_artist_follower_history( $term_id ) {
		$term_id = (int) $term_id;
		if ( $term_id <= 0 || ! function_exists( 'get_term_meta' ) ) {
			return array();
		}
		$rows = get_term_meta( $term_id, 'tara_follower_history', true );
		return is_array( $rows ) ? $rows : array();
	}
}

if ( ! function_exists( 'tara_artist_followers_set' ) ) {
	/**
	 * ثبتِ عددِ تازهٔ دنبال‌کننده‌ها (تنها مسیرِ مجازِ نوشتن).
	 *
	 * سه کار می‌کند: ذخیرهٔ عدد، به‌روزرسانیِ رکورد، ثبتِ تاریخچه.
	 * اگر عددِ تازه از رکورد پایین‌تر باشد، پرچمِ «بررسیِ مدیر» می‌گذارد؛
	 * خودِ عدد ذخیره می‌شود ولی مدیر یک هشدارِ زرد می‌بیند تا اگر اشتباه
	 * تایپی بوده برگرداند. (خواستهٔ صریحِ مالک.)
	 *
	 * @param int    $term_id  شناسهٔ ترم.
	 * @param int    $value    عددِ تازه.
	 * @param string $note     توضیح/منبع.
	 * @param string $source   منبع: manual|follow|import.
	 * @return array{drop:bool,peak:int,value:int}
	 */
	function tara_artist_followers_set( $term_id, $value, $note = '', $source = 'manual' ) {
		$term_id = (int) $term_id;
		$value   = max( 0, (int) $value );
		$out     = array(
			'drop'  => false,
			'peak'  => $term_id > 0 ? tara_artist_follower_peak( $term_id ) : 0,
			'value' => $value,
		);
		if ( $term_id <= 0 || ! function_exists( 'update_term_meta' ) ) {
			return $out;
		}

		$current = tara_artist_followers( $term_id );
		$peak    = tara_artist_follower_peak( $term_id );

		if ( $peak > (int) get_term_meta( $term_id, 'tara_follower_peak', true ) ) {
			update_term_meta( $term_id, 'tara_follower_peak', $peak );
		}
		if ( $value > $peak ) {
			update_term_meta( $term_id, 'tara_follower_peak', $value );
			$out['peak'] = $value;
		}

		if ( $value !== $current ) {
			$history = tara_artist_follower_history( $term_id );
			array_unshift(
				$history,
				array(
					't'    => function_exists( 'current_time' ) ? (int) current_time( 'timestamp' ) : time(), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
					'n'    => $value,
					'p'    => $current,
					'src'  => sanitize_key( (string) $source ),
					'note' => (string) $note,
					'by'   => function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0,
				)
			);
			update_term_meta( $term_id, 'tara_follower_history', array_slice( $history, 0, 30 ) );
		}

		update_term_meta( $term_id, 'tara_followers', $value );

		/* هر تغییر تازه پس از تأیید هشدار قبلی باید دوباره بررسی شود. */
		if ( $value !== $current ) {
			delete_term_meta( $term_id, 'tara_follower_drop_ok' );
		}

		/* هشدار: عدد از رکورد پایین‌تر آمده است. */
		if ( $value < $peak && 0 === (int) get_term_meta( $term_id, 'tara_follower_drop_ok', true ) ) {
			$out['drop'] = true;
			if ( function_exists( 'get_option' ) && function_exists( 'update_option' ) ) {
				$warnings = get_option( 'tara_follower_warnings', array() );
				$warnings = is_array( $warnings ) ? $warnings : array();
				$warnings[ $term_id ] = array(
					't'     => time(),
					'value' => $value,
					'peak'  => $peak,
					'note'  => (string) $note,
					'by'    => function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0,
				);
				update_option( 'tara_follower_warnings', $warnings, false );
			}
		} elseif ( $value >= $peak ) {
			delete_term_meta( $term_id, 'tara_follower_drop_ok' );
			if ( function_exists( 'get_option' ) && function_exists( 'update_option' ) ) {
				$warnings = get_option( 'tara_follower_warnings', array() );
				if ( is_array( $warnings ) && isset( $warnings[ $term_id ] ) ) {
					unset( $warnings[ $term_id ] );
					update_option( 'tara_follower_warnings', $warnings, false );
				}
			}
		}

		return $out;
	}
}

if ( ! function_exists( 'tara_follower_warnings' ) ) {
	/** فهرستِ هشدارهای «افتِ تعدادِ دنبال‌کننده» برای نمایش به مدیر. */
	function tara_follower_warnings() {
		if ( ! function_exists( 'get_option' ) ) {
			return array();
		}
		$warnings = get_option( 'tara_follower_warnings', array() );
		return is_array( $warnings ) ? $warnings : array();
	}
}

if ( ! function_exists( 'tara_follower_warning_link' ) ) {
	/**
	 * آدرسِ صفحهٔ ویرایشِ ترم (برای لینکِ هشدار).
	 *
	 * @param int $term_id شناسهٔ ترم.
	 * @return string
	 */
	function tara_follower_warning_link( $term_id ) {
		$tax = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
		return admin_url( 'term.php?taxonomy=' . rawurlencode( $tax ) . '&tag_ID=' . (int) $term_id );
	}
}

/* -------------------------------------------------------------------------
 * ۳) پیشخوانِ ترم: فیلدِ تأیید، عددِ دنبال‌کننده و تاریخچه
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_trust_term_fields' ) ) {
	/**
	 * فیلدهای تازه در فرمِ ویرایشِ هنرمند.
	 *
	 * @param WP_Term $term ترمِ جاری.
	 * @return void
	 */
	function tara_trust_term_fields( $term ) {
		if ( ! is_object( $term ) || empty( $term->term_id ) ) {
			return;
		}
		$term_id  = (int) $term->term_id;
		$verified = tara_artist_is_verified( $term_id );
		$followers = tara_artist_followers( $term_id );
		$peak      = tara_artist_follower_peak( $term_id );
		$history   = tara_artist_follower_history( $term_id );
		$owner     = tara_artist_term_user( $term_id );

		wp_nonce_field( 'tara_trust_save', 'tara_trust_nonce' );
		?>
		<tr class="form-field">
			<th scope="row"><label for="tara_followers">تعدادِ دنبال‌کننده‌ها</label></th>
			<td>
				<input type="number" min="0" step="1" name="tara_followers" id="tara_followers" value="<?php echo esc_attr( (string) $followers ); ?>" class="small-text">
				<p class="description">
					عددی که در سایت نمایش داده می‌شود. رکوردِ همیشگی: <strong><?php echo esc_html( number_format_i18n( $peak ) ); ?></strong>
					<?php if ( $followers < $peak ) : ?>
						<br><span style="color:#b45309">⚠ عددِ فعلی از رکورد پایین‌تر است؛ اگر اشتباهِ تایپی است همین‌جا اصلاح کنید.</span>
					<?php endif; ?>
				</p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">هنرمندِ تأییدشده</th>
			<td>
				<label for="tara_verified">
					<input type="checkbox" name="tara_verified" id="tara_verified" value="1" <?php checked( $verified ); ?>>
					تیکِ آبی کنارِ نامِ هنرمند (در صفحهٔ هنرمند، کارت‌ها، ردیفِ آهنگ‌ها و دیدگاه‌ها) نمایش داده شود.
				</label>
				<?php if ( $owner > 0 ) : ?>
					<p class="description">حسابِ کاربریِ متصل: <a href="<?php echo esc_url( get_edit_user_link( $owner ) ); ?>"><?php echo esc_html( get_the_author_meta( 'display_name', $owner ) ); ?></a></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php if ( ! empty( $history ) ) : ?>
			<tr class="form-field">
				<th scope="row">تاریخچهٔ تغییرها</th>
				<td>
					<table class="widefat striped" style="max-width:520px">
						<thead><tr><th>تاریخ</th><th>از</th><th>به</th><th>منبع</th></tr></thead>
						<tbody>
						<?php foreach ( array_slice( $history, 0, 10 ) as $row ) : ?>
							<tr>
								<td><?php echo esc_html( date_i18n( 'Y/m/d H:i', (int) $row['t'] ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions ?></td>
								<td><?php echo esc_html( number_format_i18n( isset( $row['p'] ) ? (int) $row['p'] : 0 ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) $row['n'] ) ); ?></td>
								<td><?php echo esc_html( isset( $row['src'] ) ? $row['src'] : 'manual' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</td>
			</tr>
		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'tara_trust_save_term' ) ) {
	/**
	 * ذخیرهٔ فیلدهای ترم.
	 *
	 * @param int $term_id شناسهٔ ترم.
	 * @return void
	 */
	function tara_trust_save_term( $term_id ) {
		$term_id = (int) $term_id;
		if ( $term_id <= 0 ) {
			return;
		}
		if ( ! isset( $_POST['tara_trust_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['tara_trust_nonce'] ) ), 'tara_trust_save' ) ) {
			return;
		}
		if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$verify = isset( $_POST['tara_verified'] ) && '1' === (string) wp_unslash( $_POST['tara_verified'] );
		tara_artist_verify( $term_id, $verify );

		if ( isset( $_POST['tara_followers'] ) ) {
			$value = (int) wp_unslash( $_POST['tara_followers'] );
			// تغییرِ آگاهانه از پیشخوان ⇒ پرچمِ هشدار پاک می‌شود تا هر بار تکرار نشود.
			delete_term_meta( $term_id, 'tara_follower_drop_ok' );
			tara_artist_followers_set( $term_id, $value, 'ویرایش از پیشخوان', 'manual' );
		}
	}
}

if ( ! function_exists( 'tara_trust_register_term_fields' ) ) {
	/** اتصالِ فیلدها به تاکسونومیِ هنرمند. */
	function tara_trust_register_term_fields() {
		$tax = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
		add_action( $tax . '_edit_form_fields', 'tara_trust_term_fields', 10, 1 );
		add_action( 'edited_' . $tax, 'tara_trust_save_term', 10, 1 );
		add_action( 'created_' . $tax, 'tara_trust_save_term', 10, 1 );
	}
}
if ( function_exists( 'add_action' ) ) {
	tara_trust_register_term_fields();
}

/* -------------------------------------------------------------------------
 * ۴) هشدارِ پیشخوان
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_trust_admin_notice' ) ) {
	/**
	 * نمایشِ هشدارهای «افتِ تعدادِ دنبال‌کننده» به مدیر.
	 *
	 * @return void
	 */
	function tara_trust_admin_notice() {
		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// تأییدِ دستی: ?tara_fw_ok=<term>&_wpnonce=…
		if ( isset( $_GET['tara_fw_ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$term_id = (int) $_GET['tara_fw_ok']; // phpcs:ignore WordPress.Security.NonceVerification
			$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';
			if ( $term_id > 0 && wp_verify_nonce( $nonce, 'tara_fw_ok_' . $term_id ) ) {
					$warnings = tara_follower_warnings();
				if ( isset( $warnings[ $term_id ] ) ) {
					unset( $warnings[ $term_id ] );
					update_option( 'tara_follower_warnings', $warnings, false );
				}
			}
		}

		$warnings = tara_follower_warnings();
		if ( empty( $warnings ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>ترانه یار:</strong> تعدادِ دنبال‌کنندهٔ بعضی هنرمندان از رکوردِ همیشگی‌شان پایین‌تر آمده است. ممکن است اشتباهِ تایپی باشد:</p><ul style="margin:6px 0 6px 18px;list-style:disc">';
		foreach ( array_slice( $warnings, 0, 5, true ) as $term_id => $row ) {
			$name = function_exists( 'get_term' ) ? get_term( (int) $term_id ) : null;
			$name = ( $name && ! is_wp_error( $name ) ) ? $name->name : '#' . (int) $term_id;
			$ok   = wp_nonce_url( admin_url( 'index.php?tara_fw_ok=' . (int) $term_id ), 'tara_fw_ok_' . (int) $term_id );
			echo '<li><a href="' . esc_url( tara_follower_warning_link( (int) $term_id ) ) . '">' . esc_html( $name ) . '</a> — عددِ فعلی '
				. esc_html( number_format_i18n( (int) $row['value'] ) ) . ' ولی رکورد '
				. esc_html( number_format_i18n( (int) $row['peak'] ) ) . ' بوده است. '
				. '<a href="' . esc_url( $ok ) . '">این عدد درست است</a></li>';
		}
		echo '</ul></div>';
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'admin_notices', 'tara_trust_admin_notice' );
}
