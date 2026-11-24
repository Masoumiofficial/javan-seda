<?php
/**
 * دایرکتِ مستقیم بین شنونده و هنرمند (v3.12.0)
 * =========================================================================
 * همان چیزی که مالک خواست: «مخاطب‌ها بتوانند به هنرمند پیام مستقیم بدهند و
 * خودِ هنرمند ببیند و جواب بدهد؛ مثلِ اینستاگرام».
 *
 * طراحی:
 *  • یک جدولِ سبک `{prefix}tara_dm` — نه پست، نه کاربرِ تازه، نه افزونه.
 *    چون پیام‌ها زیاد می‌شوند و متای ترم برای این حجم مناسب نیست.
 *  • «رشته» با جفتِ (artist_id = ترمِ هنرمند، fan_id = کاربرِ شنونده) شناخته
 *    می‌شود؛ پس هر شنونده با هر هنرمند یک گفت‌وگوی جدا دارد.
 *  • فرستنده با `sender_id` ذخیره می‌شود (هنرمند یا شنونده) تا هر دو طرف
 *    بتوانند حباب‌های خودشان را ببینند.
 *  • خوانده‌شدن با `seen` (۰/۱) و شمارندهٔ «نخوانده» برای هر دو طرف.
 *
 * امنیت:
 *  • فقط کاربرِ واردشده می‌تواند بفرستد/بخواند.
 *  • شنونده فقط رشتهٔ خودش را می‌بیند، هنرمند فقط رشته‌های ترمِ خودش را.
 *  • هر درخواست نانس دارد و متن با `sanitize_textarea_field` پاک می‌شود.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_dm_table' ) ) {
	/** نامِ جدولِ پیام‌ها. */
	function tara_dm_table() {
		global $wpdb;
		$prefix = isset( $wpdb->prefix ) ? $wpdb->prefix : 'wp_';
		return $prefix . 'tara_dm';
	}
}

if ( ! function_exists( 'tara_dm_install' ) ) {
	/**
	 * ساختِ جدول (یک‌بار).
	 *
	 * @return void
	 */
	function tara_dm_install() {
		global $wpdb;
		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return;
		}
		$table   = tara_dm_table();
		$charset = method_exists( $wpdb, 'get_charset_collate' ) ? $wpdb->get_charset_collate() : '';
		$sql     = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			artist_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			fan_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			sender_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			body LONGTEXT NULL,
			created DATETIME NULL,
			seen TINYINT(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY thread (artist_id, fan_id),
			KEY fan (fan_id),
			KEY artist (artist_id)
		) {$charset};";

		require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) {
			update_option( 'tara_dm_version', '1' );
		}
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'after_switch_theme', 'tara_dm_install' );
}

if ( ! function_exists( 'tara_dm_ready' ) ) {
	/**
	 * آیا جدول ساخته شده است؟ (اگر نبود، همین‌جا می‌سازد — یک‌بار)
	 *
	 * @return bool
	 */
	function tara_dm_ready() {
		static $ready = null;
		if ( null !== $ready ) {
			return $ready;
		}
		global $wpdb;
		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_var' ) ) {
			$ready = false;
			return $ready;
		}
		if ( '1' !== (string) get_option( 'tara_dm_version', '' ) ) {
			tara_dm_install();
		}
		$table = tara_dm_table();
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
		$ready = ( $found === $table );
		return $ready;
	}
}

if ( ! function_exists( 'tara_dm_on' ) ) {
	/** آیا دایرکت در تنظیماتِ قالب روشن است؟ */
	function tara_dm_on() {
		if ( ! function_exists( 'ot_get_option' ) ) {
			return false;
		}
		return 'on' === (string) ot_get_option( 'artist_dm_on', 'on' );
	}
}

if ( ! function_exists( 'tara_dm_can_message' ) ) {
	/**
	 * آیا این کاربر می‌تواند به این هنرمند پیام بدهد؟
	 *
	 * @param int $artist_term ترمِ هنرمند.
	 * @param int $user_id     کاربر (۰ = جاری).
	 * @return bool
	 */
	function tara_dm_can_message( $artist_term, $user_id = 0 ) {
		$artist_term = (int) $artist_term;
		$user_id     = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		if ( ! tara_dm_on() || $artist_term <= 0 || $user_id <= 0 ) {
			return false;
		}
		$owner = function_exists( 'tara_artist_term_user' ) ? tara_artist_term_user( $artist_term ) : 0;
		if ( $owner <= 0 || $owner === $user_id || ( function_exists( 'tara_artist_is_verified' ) && ! tara_artist_is_verified( $artist_term ) ) ) {
			return false; // بی‌معنی است به خودت پیام بدهی
		}
		return true;
	}
}

if ( ! function_exists( 'tara_dm_send' ) ) {
	/**
	 * فرستادنِ پیام.
	 *
	 * @param int    $artist_term ترمِ هنرمند.
	 * @param int    $from_user   فرستنده.
	 * @param string $body        متن.
	 * @param int    $fan_id      وقتی هنرمند جواب می‌دهد، شنوندهٔ مقصد.
	 * @return int|WP_Error شناسهٔ پیام.
	 */
	function tara_dm_send( $artist_term, $from_user, $body, $fan_id = 0 ) {
		global $wpdb;
		$artist_term = (int) $artist_term;
		$from_user   = (int) $from_user;
		$fan_id      = (int) $fan_id;
		$body        = trim( (string) $body );

		if ( ! tara_dm_ready() ) {
			return new WP_Error( 'tara_dm_env', 'جدولِ پیام‌ها آماده نیست.' );
		}
		if ( ! tara_dm_on() ) {
			return new WP_Error( 'tara_dm_off', 'دایرکت در این سایت خاموش است.' );
		}
		if ( $artist_term <= 0 || $from_user <= 0 ) {
			return new WP_Error( 'tara_dm_args', 'اطلاعاتِ پیام کامل نیست.' );
		}
		if ( '' === $body ) {
			return new WP_Error( 'tara_dm_empty', 'متنِ پیام خالی است.' );
		}
		$max = (int) apply_filters( 'tara_dm_max_chars', 1200 );
		if ( $max < 1 ) { $max = 1200; }
		if ( function_exists( 'mb_substr' ) ) {
			$body = mb_substr( $body, 0, $max );
		} else {
			$characters = preg_split( '//u', $body, -1, PREG_SPLIT_NO_EMPTY );
			$body = is_array( $characters ) ? implode( '', array_slice( $characters, 0, $max ) ) : '';
		}

		$owner = function_exists( 'tara_artist_term_user' ) ? (int) tara_artist_term_user( $artist_term ) : 0;
		if ( $owner <= 0 ) {
			return new WP_Error( 'tara_dm_no_owner', 'این هنرمند هنوز حسابِ کاربری ندارد.' );
		}

		if ( $owner === $from_user ) {
			if ( $fan_id <= 0 ) {
				return new WP_Error( 'tara_dm_no_fan', 'مخاطبِ پیام مشخص نیست.' );
			}
		} else {
			if ( ! tara_dm_can_message( $artist_term, $from_user ) ) {
				return new WP_Error( 'tara_dm_access', 'امکان ارسال پیام به این هنرمند وجود ندارد.' );
			}
			$fan_id = $from_user;
		}

		if ( $fan_id === $owner ) {
			return new WP_Error( 'tara_dm_self', 'به خودت پیام نمی‌دهی.' );
		}
		if ( ! function_exists( 'tara_dm_throttle' ) ) {
			// حداقلِ فاصلهٔ ارسال، ساده و بی‌وابستگی
			$last = (int) get_transient( 'tara_dm_last_' . $from_user );
			if ( $last > 0 && ( time() - $last ) < 2 ) {
				return new WP_Error( 'tara_dm_fast', 'کمی آرام‌تر؛ چند ثانیه صبر کنید.' );
			}
			set_transient( 'tara_dm_last_' . $from_user, time(), 10 );
		}

		$ok = $wpdb->insert(
			tara_dm_table(),
			array(
				'artist_id' => $artist_term,
				'fan_id'    => $fan_id,
				'sender_id' => $from_user,
				'body'      => $body,
				'created'   => function_exists( 'current_time' ) ? current_time( 'mysql' ) : gmdate( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
				'seen'      => 0,
			),
			array( '%d', '%d', '%d', '%s', '%s', '%d' )
		);
		if ( ! $ok ) {
			return new WP_Error( 'tara_dm_db', 'پیام ذخیره نشد.' );
		}

		$id = (int) $wpdb->insert_id;

		/**
		 * بعد از ثبتِ هر پیام. رباتِ تلگرام یا اعلانِ ایمیلی می‌تواند به همین
		 * قلاب وصل شود.
		 *
		 * @param int $id      شناسهٔ پیام.
		 * @param int $term    ترمِ هنرمند.
		 * @param int $fan_id  شنونده.
		 * @param int $from    فرستنده.
		 */
		do_action( 'tara_dm_sent', $id, $artist_term, $fan_id, $from_user );

		return $id;
	}
}

if ( ! function_exists( 'tara_dm_thread' ) ) {
	/**
	 * گفت‌وگوی یک شنونده با یک هنرمند.
	 *
	 * @param int  $artist_term ترمِ هنرمند.
	 * @param int  $fan_id      شنونده.
	 * @param int  $limit       تعداد.
	 * @param bool $mark_seen   پیام‌های طرفِ مقابل خوانده شود؟
	 * @return array[]
	 */
	function tara_dm_thread( $artist_term, $fan_id, $limit = 60, $mark_seen = true ) {
		global $wpdb;
		$artist_term = (int) $artist_term;
		$fan_id      = (int) $fan_id;
		$limit       = max( 1, min( 200, (int) $limit ) );
		if ( ! tara_dm_ready() || $artist_term <= 0 || $fan_id <= 0 ) {
			return array();
		}
		$table = tara_dm_table();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, sender_id, body, created, seen FROM {$table} WHERE artist_id = %d AND fan_id = %d ORDER BY id DESC LIMIT %d",
				$artist_term,
				$fan_id,
				$limit
			),
			ARRAY_A
		);
		$rows = array_reverse( is_array( $rows ) ? $rows : array() );

		if ( $mark_seen && ! empty( $rows ) && function_exists( 'get_current_user_id' ) ) {
			$me = (int) get_current_user_id();
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET seen = 1 WHERE artist_id = %d AND fan_id = %d AND sender_id <> %d AND seen = 0",
					$artist_term,
					$fan_id,
					$me
				)
			);
		}

		return $rows;
	}
}

if ( ! function_exists( 'tara_dm_threads_for_artist' ) ) {
	/**
	 * فهرستِ گفت‌وگوهای یک هنرمند (هر شنونده یک ردیف، با آخرین پیام).
	 *
	 * @param int $artist_term ترمِ هنرمند.
	 * @param int $limit       تعداد.
	 * @return array[]
	 */
	function tara_dm_threads_for_artist( $artist_term, $limit = 40 ) {
		global $wpdb;
		$artist_term = (int) $artist_term;
		$limit       = max( 1, min( 100, (int) $limit ) );
		if ( ! tara_dm_ready() || $artist_term <= 0 ) {
			return array();
		}
		$table = tara_dm_table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT fan_id, MAX(id) AS last_id, COUNT(*) AS total,
					SUM(CASE WHEN seen = 0 AND sender_id <> %d THEN 1 ELSE 0 END) AS unread
				 FROM {$table} WHERE artist_id = %d GROUP BY fan_id ORDER BY last_id DESC LIMIT %d",
			(int) tara_artist_term_user( $artist_term ),
				$artist_term,
				$limit
			),
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$out = array();
		foreach ( $rows as $row ) {
			$last = $wpdb->get_row(
				$wpdb->prepare( "SELECT body, created, sender_id FROM {$table} WHERE id = %d", (int) $row['last_id'] ),
				ARRAY_A
			);
			$out[] = array(
				'fan_id' => (int) $row['fan_id'],
				'name'   => tara_dm_user_name( (int) $row['fan_id'] ),
				'total'  => (int) $row['total'],
				'unread' => max( 0, (int) $row['unread'] ),
				'last'   => is_array( $last ) ? (string) $last['body'] : '',
				'when'   => is_array( $last ) ? (string) $last['created'] : '',
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'tara_dm_threads_for_user' ) ) {
	/**
	 * گفت‌وگوهای یک شنونده با هنرمندان.
	 *
	 * @param int $user_id کاربر.
	 * @param int $limit   تعداد.
	 * @return array[]
	 */
	function tara_dm_threads_for_user( $user_id = 0, $limit = 30 ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		$limit = max( 1, min( 100, (int) $limit ) );
		if ( ! tara_dm_ready() || $user_id <= 0 ) {
			return array();
		}
		$table = tara_dm_table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT artist_id, MAX(id) AS last_id, COUNT(*) AS total,
					SUM(CASE WHEN seen = 0 AND sender_id <> %d THEN 1 ELSE 0 END) AS unread
				 FROM {$table} WHERE fan_id = %d GROUP BY artist_id ORDER BY last_id DESC LIMIT %d",
				$user_id,
				$user_id,
				$limit
			),
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$tax = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
		$out = array();
		foreach ( $rows as $row ) {
			$term = function_exists( 'get_term' ) ? get_term( (int) $row['artist_id'], $tax ) : null;
			if ( ! $term || ( function_exists( 'is_wp_error' ) && is_wp_error( $term ) ) ) {
				continue;
			}
			$link = function_exists( 'get_term_link' ) ? get_term_link( $term, $tax ) : '';
			if ( function_exists( 'is_wp_error' ) && is_wp_error( $link ) ) {
				$link = '';
			}
			$last = $wpdb->get_row(
				$wpdb->prepare( "SELECT body, created FROM {$table} WHERE id = %d", (int) $row['last_id'] ),
				ARRAY_A
			);
			$out[] = array(
				'term_id' => (int) $row['artist_id'],
				'name'    => $term->name,
				'url'     => (string) $link,
				'image'   => function_exists( 'tara_artist_image_url' ) ? tara_artist_image_url( $term, 'thumb2' ) : '',
				'unread'  => max( 0, (int) $row['unread'] ),
				'last'    => is_array( $last ) ? (string) $last['body'] : '',
				'when'    => is_array( $last ) ? (string) $last['created'] : '',
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'tara_dm_unread_total' ) ) {
	/**
	 * شمارندهٔ پیام‌های خوانده‌نشده.
	 *
	 * @param int $user_id کاربر.
	 * @return int
	 */
	function tara_dm_unread_total( $user_id = 0 ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		if ( $user_id <= 0 || ! tara_dm_ready() ) {
			return 0;
		}
		$table  = tara_dm_table();
		$own    = function_exists( 'tara_artist_term_of_user' ) ? (int) tara_artist_term_of_user( $user_id ) : 0;
		$direct = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE fan_id = %d AND sender_id <> %d AND seen = 0", $user_id, $user_id )
		);
		if ( $own > 0 ) {
			$direct += (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE artist_id = %d AND sender_id <> %d AND seen = 0", $own, $user_id )
			);
		}
		return max( 0, $direct );
	}
}

if ( ! function_exists( 'tara_dm_user_name' ) ) {
	/**
	 * نامِ نمایشیِ کاربر برای نمایش در فهرست.
	 *
	 * @param int $user_id کاربر.
	 * @return string
	 */
	function tara_dm_user_name( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return 'شنونده';
		}
		$name = (string) get_user_meta( $user_id, 'nickname', true );
		if ( '' === trim( $name ) && function_exists( 'get_userdata' ) ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$name = (string) $user->display_name;
			}
		}
		return '' !== trim( $name ) ? $name : 'شنونده';
	}
}

/* -------------------------------------------------------------------------
 * AJAX — برای هر دو طرف
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_dm_ajax' ) ) {
	/**
	 * نقطهٔ پایانیِ AJAXِ دایرکت.
	 *
	 * @return void
	 */
	function tara_dm_ajax() {
		if ( ! function_exists( 'check_ajax_referer' ) ) {
			return;
		}
		check_ajax_referer( 'tara_dm', 'nonce' );

		$user_id = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
		if ( $user_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'برای ارسالِ پیام باید وارد شوید.', 'login' => 1 ) );
		}

		$do      = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( (string) $_POST['do'] ) ) : '';
		$term_id = isset( $_POST['artist'] ) ? (int) $_POST['artist'] : 0;
		$fan_id  = isset( $_POST['fan'] ) ? (int) $_POST['fan'] : 0;

		if ( ! tara_dm_on() || ! tara_dm_ready() ) {
			wp_send_json_error( array( 'message' => 'سامانهٔ پیام فعال یا آماده نیست.' ) );
		}

		switch ( $do ) {
			case 'send':
				$body = isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['body'] ) ) : '';
				$res  = tara_dm_send( $term_id, $user_id, $body, $fan_id );
				if ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) {
					wp_send_json_error( array( 'message' => $res->get_error_message() ) );
				}
				wp_send_json_success( array( 'id' => (int) $res ) );
				break;

			case 'thread':
				$owner = (int) tara_artist_term_user( $term_id );
				$fan   = ( $owner === $user_id ) ? $fan_id : $user_id;
				if ( $owner <= 0 || ( $owner !== $user_id && ! tara_dm_can_message( $term_id, $user_id ) ) ) {
					wp_send_json_error( array( 'message' => 'دسترسی به این گفت‌وگو ندارید.' ) );
				}
				$rows = tara_dm_thread( $term_id, $fan, 60, true );
				wp_send_json_success(
					array(
						'messages' => array_map(
							function ( $row ) use ( $user_id ) {
								return array(
									'id'   => (int) $row['id'],
									'body' => (string) $row['body'],
									'when' => (string) $row['created'],
									'mine' => (int) $row['sender_id'] === (int) $user_id,
								);
							},
							$rows
						),
					)
				);
				break;
		}

		wp_send_json_error( array( 'message' => 'درخواستِ نامشخص.' ) );
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_ajax_tara_dm', 'tara_dm_ajax' );
	add_action( 'wp_ajax_nopriv_tara_dm', 'tara_dm_ajax' );
}

/* -------------------------------------------------------------------------
 * رابطِ کاربری: دکمهٔ «پیام مستقیم» + برگهٔ گفت‌وگو
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_dm_button' ) ) {
	/**
	 * دکمهٔ «پیام مستقیم» برای صفحهٔ هنرمند.
	 *
	 * @param int   $term_id ترمِ هنرمند.
	 * @param array $args    class/label.
	 * @return string
	 */
	function tara_dm_button( $term_id, $args = array() ) {
		$term_id = (int) $term_id;
		if ( $term_id <= 0 ) {
			return '';
		}
		$args = wp_parse_args(
			$args,
			array(
				'class' => 'ty-artist__ghost',
				'label' => 'پیام مستقیم',
			)
		);

		$owner = (int) tara_artist_term_user( $term_id );
		if ( ! tara_dm_on() || ( function_exists( 'tara_acc_on' ) && ! tara_acc_on() ) || $owner <= 0 || ( function_exists( 'tara_artist_is_verified' ) && ! tara_artist_is_verified( $term_id ) ) ) {
			return '<span class="ty-dm-availability"><button type="button" class="' . esc_attr( $args['class'] ) . ' ty-dm-open" disabled aria-describedby="ty-dm-unavailable">پیام مستقیم</button><small id="ty-dm-unavailable">پیام‌رسانی پس از فعال‌سازی و اتصال حساب تأییدشدهٔ هنرمند در دسترس است.</small></span>';
		}

		$logged = function_exists( 'is_user_logged_in' ) && is_user_logged_in();
		if ( $logged && function_exists( 'get_current_user_id' ) && (int) get_current_user_id() === $owner ) {
			return '<a class="' . esc_attr( $args['class'] ) . '" href="' . esc_url( tara_acc_profile_url( 'messages' ) ) . '">دایرکت‌های من</a>';
		}
		if ( ! $logged ) {
			return '<button type="button" class="' . esc_attr( $args['class'] ) . ' ty-dm-open" data-tara-open="account">'
				. '<i class="icofont-ui-message" aria-hidden="true"></i> ' . esc_html( $args['label'] ) . '</button>';
		}

		return '<button type="button" class="' . esc_attr( $args['class'] ) . ' ty-dm-open" data-tara-dm="' . esc_attr( (string) $term_id ) . '" aria-haspopup="dialog">'
			. '<i class="icofont-ui-message" aria-hidden="true"></i> ' . esc_html( $args['label'] ) . '</button>';
	}
}

if ( ! function_exists( 'tara_dm_sheet' ) ) {
	/**
	 * پنجرهٔ گفت‌وگو (یک‌بار در پاصفحه، فقط برای کاربرِ واردشده).
	 *
	 * @return void
	 */
	function tara_dm_sheet() {
		if ( ! tara_dm_on() || ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
			return;
		}
		?>
		<div class="ty-dm" id="ty-dm" hidden>
			<div class="ty-dm__veil" data-ty-dm-close></div>
			<div class="ty-dm__card" role="dialog" aria-modal="true" aria-labelledby="ty-dm-title">
				<header class="ty-dm__head">
					<span class="ty-dm__ava" data-ty-dm-ava></span>
					<div>
						<h2 class="ty-dm__title" id="ty-dm-title" data-ty-dm-title>پیام مستقیم</h2>
						<p class="ty-dm__sub">پیام شما فقط به خودِ هنرمند نشان داده می‌شود.</p>
					</div>
					<button type="button" class="ty-dm__x" data-ty-dm-close aria-label="بستن">&times;</button>
				</header>
				<div class="ty-dm__body" data-ty-dm-body>
					<p class="ty-dm__empty">هنوز پیامی رد و بدل نشده؛ اولین پیام را بنویسید.</p>
				</div>
				<p class="ty-dm__status" data-ty-dm-status role="status" aria-live="polite"></p>
				<form class="ty-dm__form" data-ty-dm-form>
					<input type="hidden" name="artist" value="" data-ty-dm-artist>
					<input type="hidden" name="fan" value="" data-ty-dm-fan>
					<textarea name="body" rows="2" maxlength="1200" placeholder="پیام‌تان را بنویسید…" required></textarea>
					<button type="submit" class="ty-dm__send" aria-label="ارسال">
						<i class="icofont-paper-plane" aria-hidden="true"></i>
					</button>
				</form>
			</div>
		</div>
		<?php
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_footer', 'tara_dm_sheet', 30 );
}
