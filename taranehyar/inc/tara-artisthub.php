<?php
/**
 * پنلِ هنرمند، ارتقا، پلن‌های پخش و تبلیغ — v3.12.0
 * =========================================================================
 * مسیرِ کاملِ «شنونده → هنرمند»:
 *   ۱) کاربر از تبِ «پنل هنرمند» درخواستِ ارتقا می‌دهد.
 *   ۲) مدیر در «درخواست‌های هنرمندان» آن را بررسی و به یک ترمِ هنرمند وصل
 *      می‌کند (همان لحظه tara_artist_term / tara_artist_user ست می‌شود).
 *   ۳) هنرمندِ وصل‌شده آثارِ خودش را می‌بیند، اثر تازه می‌فرستد (پیش‌نویس)،
 *      برای ویرایشِ متنِ ترانه/مشخصات درخواست می‌دهد و پلن می‌خرد.
 *
 * پرداخت:
 *   • سفارش و مبلغ سمت سرور در ووکامرس ساخته و بعد از تأیید پرداخت ثبت می‌شود.
 *
 * @package Taranehyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_hub_opt' ) ) {
	/** خواندنِ تنظیماتِ پنلِ هنرمند. */
	function tara_hub_opt( $key, $default = '' ) {
		return function_exists( 'ot_get_option' ) ? ot_get_option( $key, $default ) : $default;
	}
}

if ( ! function_exists( 'tara_hub_on' ) ) {
	/** پنلِ هنرمند روشن است؟ */
	function tara_hub_on() {
		return 'off' !== (string) tara_hub_opt( 'artisthub_on', 'on' );
	}
}

if ( ! function_exists( 'tara_hub_money' ) ) {
	/** نمایشِ مبلغ با واحدِ تومان. */
	function tara_hub_money( $amount ) {
		$amount = max( 0, (int) $amount );
		if ( 0 === $amount ) {
			return 'رایگان';
		}
		$num = function_exists( 'tara_number_fa' ) ? tara_number_fa( number_format( $amount ) ) : number_format_i18n( $amount );
		return $num . ' تومان';
	}
}

if ( ! function_exists( 'tara_hub_plans' ) ) {
	/**
	 * پلن‌های قابلِ خرید.
	 *
	 * @return array<string,array>
	 */
	function tara_hub_plans() {
		$plans = array(
			'std'   => array(
				'name'  => 'پخش عادی',
				'price' => (int) tara_hub_opt( 'artisthub_std_price', 0 ),
				'desc'  => 'انتشارِ اثر در سایت، صفحهٔ هنرمند، رادیو و ربات تلگرام.',
				'feats' => array( 'صفحهٔ اختصاصی هنرمند', 'نمایش در جست‌وجو و رادیو', 'آمار بازدیدِ آثار' ),
			),
			'vip'   => array(
				'name'  => 'پخش VIP',
				'price' => (int) tara_hub_opt( 'artisthub_vip_price', 490000 ),
				'desc'  => 'همهٔ امکاناتِ پخش عادی + جایگاهِ ویژه و معرفی در صفحهٔ اصلی.',
				'feats' => array( 'جایگاهِ ویژه در صفحهٔ اصلی', 'بنر و معرفیِ ویژه', 'اولویت در رادیو و پیشنهادها' ),
			),
			'promo' => array(
				'name'  => 'تبلیغ و افزایش بازدید',
				'price' => (int) tara_hub_opt( 'artisthub_promo_price', 290000 ),
				'desc'  => 'کمپینِ تبلیغِ یک اثر؛ هر ۱۰۰۰ بازدیدِ واقعی از مبلغ کم می‌کند.',
				'feats' => array( 'نمایش در بخش‌های پرترافیک', 'گزارشِ بازدیدِ کمپین', 'تخفیف بر اساس بازدیدِ واقعی' ),
			),
		);
		return apply_filters( 'tara_hub_plans', $plans );
	}
}

if ( ! function_exists( 'tara_hub_wc_ready' ) ) {
	/** سفارش با ووکامرس و واحد پول شناخته‌شده. */
	function tara_hub_wc_ready() {
		return function_exists( 'wc_create_order' ) && function_exists( 'get_woocommerce_currency' )
			&& class_exists( 'WC_Order_Item_Fee' ) && in_array( get_woocommerce_currency(), array( 'IRT', 'IRR' ), true );
	}
}

/** تبدیل قیمت‌های تنظیمات (تومان) به واحد فروشگاه؛ سایر ارزها عمداً مسدودند. */
function tara_hub_wc_amount( $toman ) {
	$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
	if ( ! in_array( $currency, array( 'IRT', 'IRR' ), true ) ) {
		return null;
	}
	return max( 0, (int) $toman ) * ( 'IRR' === $currency ? 10 : 1 );
}

/** سفارش فقط با مبلغِ محاسبه‌شده در سرور ساخته می‌شود، نه مبلغ ارسالی مرورگر. */
function tara_hub_wc_order( $user_id, $request_id, $plan, $amount ) {
	if ( ! tara_hub_wc_ready() ) {
		return new WP_Error( 'tara_wc_unavailable', 'پرداخت فعال نیست؛ ووکامرس و واحد پول تومان (IRT) یا ریال (IRR) را بررسی کنید.' );
	}
	$plans = tara_hub_plans();
	if ( ! isset( $plans[ $plan ] ) || ! $user_id || '' === $request_id ) {
		return new WP_Error( 'tara_wc_invalid', 'اطلاعات سفارش نامعتبر است.' );
	}
	$total = tara_hub_wc_amount( $amount );
	try {
		$order = wc_create_order( array( 'customer_id' => (int) $user_id, 'created_via' => 'tara_artist_hub' ) );
		if ( is_wp_error( $order ) ) { return $order; }
		$item = new WC_Order_Item_Fee();
		$item->set_name( 'پلن هنرمند: ' . $plans[ $plan ]['name'] );
		$item->set_tax_status( 'none' );
		$item->set_amount( $total );
		$item->set_total( $total );
		$order->add_item( $item );
		$order->update_meta_data( '_tara_hub_request', $request_id );
		$order->update_meta_data( '_tara_hub_user', (int) $user_id );
		$order->update_meta_data( '_tara_hub_plan', $plan );
		$order->update_meta_data( '_tara_hub_expected', (string) $total );
		$order->update_meta_data( '_tara_hub_currency', get_woocommerce_currency() );
		$order->calculate_totals( false );
		$order->save();
		return $order;
	} catch ( Throwable $e ) {
		return new WP_Error( 'tara_wc_error', 'ساخت سفارش ناموفق بود؛ با مدیر تماس بگیرید.' );
	}
}

/** هیچ بازگشتی از مرورگر به‌تنهایی پرداخت را تأیید نمی‌کند. */
function tara_hub_wc_paid( $order_id ) {
	if ( ! function_exists( 'wc_get_order' ) ) { return; }
	$order = wc_get_order( $order_id );
	if ( ! $order || ! $order->is_paid() ) { return; }
	$user_id = (int) $order->get_meta( '_tara_hub_user' );
	$request_id = (string) $order->get_meta( '_tara_hub_request' );
	$expected = (string) $order->get_meta( '_tara_hub_expected' );
	$currency = (string) $order->get_meta( '_tara_hub_currency' );
	if ( $user_id <= 0 || $user_id !== (int) $order->get_customer_id() || '' === $request_id || '' === $expected || $currency !== $order->get_currency() || abs( (float) $order->get_total() - (float) $expected ) > 0.001 ) { return; }
	$rows = tara_hub_requests( $user_id );
	foreach ( $rows as &$row ) {
		if ( isset( $row['id'], $row['order_id'], $row['type'] ) && 'plan' === $row['type'] && $row['id'] === $request_id && (int) $row['order_id'] === (int) $order_id && 'pending' === $row['status'] ) {
			$row['status'] = 'paid';
		}
	}
	unset( $row );
	update_user_meta( $user_id, 'tara_artist_requests', $rows );
}
/** تغییر وضعیت بعد از استرداد کامل، بدون اتکا به بازگشت مرورگر. */
function tara_hub_wc_refunded( $order_id ) {
	if ( ! function_exists( 'wc_get_order' ) ) { return; }
	$order = wc_get_order( $order_id );
	if ( ! $order || ! $order->has_status( 'refunded' ) ) { return; }
	$user_id = (int) $order->get_meta( '_tara_hub_user' );
	$request_id = (string) $order->get_meta( '_tara_hub_request' );
	if ( $user_id <= 0 || $user_id !== (int) $order->get_customer_id() || '' === $request_id ) { return; }
	$rows = tara_hub_requests( $user_id );
	foreach ( $rows as &$row ) {
		if ( isset( $row['id'], $row['order_id'], $row['type'] ) && 'plan' === $row['type'] && $row['id'] === $request_id && (int) $row['order_id'] === (int) $order_id ) {
			$row['status'] = 'refunded';
		}
	}
	unset( $row );
	update_user_meta( $user_id, 'tara_artist_requests', $rows );
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'woocommerce_order_status_refunded', 'tara_hub_wc_refunded' );
	add_action( 'woocommerce_payment_complete', 'tara_hub_wc_paid' );
	add_action( 'woocommerce_order_status_processing', 'tara_hub_wc_paid' );
	add_action( 'woocommerce_order_status_completed', 'tara_hub_wc_paid' );
}

if ( ! function_exists( 'tara_hub_user_term' ) ) {
	/** ترمِ هنرمندِ این کاربر (۰ = هنرمند نیست). */
	function tara_hub_user_term( $user_id = 0 ) {
		return function_exists( 'tara_artist_term_of_user' ) ? (int) tara_artist_term_of_user( (int) $user_id ) : 0;
	}
}

if ( ! function_exists( 'tara_hub_requests' ) ) {
	/**
	 * درخواست‌های یک کاربر.
	 *
	 * @param int $user_id کاربر.
	 * @return array[]
	 */
	function tara_hub_requests( $user_id = 0 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return array();
		}
		$rows = get_user_meta( $user_id, 'tara_artist_requests', true );
		return is_array( $rows ) ? $rows : array();
	}
}

if ( ! function_exists( 'tara_hub_request_add' ) ) {
	/**
	 * ثبتِ درخواست.
	 *
	 * @param int   $user_id کاربر.
	 * @param array $data    type, plan, title, body, post_id, price.
	 * @return string شناسهٔ درخواست.
	 */
	function tara_hub_request_add( $user_id, $data ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return '';
		}
		$id   = 'r' . time() . wp_rand( 100, 999 );
		$rows = tara_hub_requests( $user_id );
		array_unshift(
			$rows,
			array(
				'id'      => $id,
				'type'    => isset( $data['type'] ) ? sanitize_key( $data['type'] ) : 'support',
				'plan'    => isset( $data['plan'] ) ? sanitize_key( $data['plan'] ) : '',
				'title'   => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
				'body'    => isset( $data['body'] ) ? sanitize_textarea_field( $data['body'] ) : '',
				'post_id' => isset( $data['post_id'] ) ? (int) $data['post_id'] : 0,
				'price'   => isset( $data['price'] ) ? (int) $data['price'] : 0,
				'views'   => isset( $data['views'] ) ? (int) $data['views'] : 0,
				'status'  => 'pending',
				'created' => time(),
				'note'    => '',
			)
		);
		if ( false === update_user_meta( $user_id, 'tara_artist_requests', $rows ) ) { return ''; }
		return $id;
	}
}

if ( ! function_exists( 'tara_hub_tracks' ) ) {
	/**
	 * آثارِ یک هنرمند.
	 *
	 * @param int $term_id ترم.
	 * @param int $limit   تعداد.
	 * @return WP_Post[]
	 */
	function tara_hub_tracks( $term_id, $limit = 100 ) {
		$term_id = (int) $term_id;
		if ( $term_id <= 0 || ! function_exists( 'get_posts' ) ) {
			return array();
		}
		$tax = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
		return get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'posts_per_page' => max( 1, (int) $limit ),
				'orderby'        => 'date',
				'order'          => 'DESC',
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy' => $tax,
						'field'    => 'term_id',
						'terms'    => $term_id,
					),
				),
			)
		);
	}
}

if ( ! function_exists( 'tara_hub_total_views' ) ) {
	/** مجموعِ بازدیدِ آثارِ هنرمند. */
	function tara_hub_total_views( $term_id ) {
		$total = 0;
		foreach ( tara_hub_tracks( $term_id, 200 ) as $post ) {
			$total += (int) get_post_meta( $post->ID, 'views', true );
		}
		return $total;
	}
}

if ( ! function_exists( 'tara_hub_promo_quote' ) ) {
	/**
	 * پیش‌فاکتورِ تبلیغ: تخفیف بر اساس بازدید.
	 *
	 * قانون (قابلِ تنظیم در قالب): به‌ازای هر ۱۰۰۰ بازدیدِ واقعیِ اثر،
	 * مبلغِ `artisthub_promo_discount` تومان از قیمتِ پلنِ تبلیغ کم می‌شود
	 * (تا سقفِ خودِ قیمت).
	 *
	 * @param int $views بازدیدِ اثر.
	 * @return array{price:int,discount:int,final:int,units:int}
	 */
	function tara_hub_promo_quote( $views ) {
		$plans    = tara_hub_plans();
		$price    = isset( $plans['promo']['price'] ) ? (int) $plans['promo']['price'] : 0;
		$per      = max( 0, (int) tara_hub_opt( 'artisthub_promo_discount', 10000 ) );
		$units    = (int) floor( max( 0, (int) $views ) / 1000 );
		$discount = min( $price, $units * $per );
		return array(
			'price'    => $price,
			'discount' => $discount,
			'final'    => max( 0, $price - $discount ),
			'units'    => $units,
		);
	}
}

/* -------------------------------------------------------------------------
 * AJAX — ثبتِ درخواست/اثر تازه
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_hub_ajax' ) ) {
	/** نقطهٔ پایانیِ AJAXِ پنلِ هنرمند. */
	function tara_hub_ajax() {
		check_ajax_referer( 'tara_account', 'nonce' );
		$user_id = (int) get_current_user_id();
		if ( $user_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'برای این کار باید وارد شوید.', 'login' => 1 ) );
		}
		if ( ! tara_hub_on() ) {
			wp_send_json_error( array( 'message' => 'پنلِ هنرمند در این سایت خاموش است.' ) );
		}

		$do   = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( (string) $_POST['do'] ) ) : '';
		$term = tara_hub_user_term( $user_id );

		switch ( $do ) {
			case 'claim':
				$name = isset( $_POST['artist_name'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['artist_name'] ) ) : '';
				$body = isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['body'] ) ) : '';
				if ( '' === $name ) {
					wp_send_json_error( array( 'message' => 'نامِ هنری را بنویسید.' ) );
				}
				$id = tara_hub_request_add(
					$user_id,
					array(
						'type'  => 'upgrade',
						'title' => 'درخواستِ ارتقا — ' . $name,
						'body'  => $body,
					)
				);
				wp_send_json_success( array( 'id' => $id, 'message' => 'درخواستِ ارتقا ثبت شد؛ بعد از بررسیِ مدیر، پنلِ هنرمند برایتان باز می‌شود.' ) );
				break;

			case 'buy':
				$plan = isset( $_POST['plan'] ) ? sanitize_key( wp_unslash( (string) $_POST['plan'] ) ) : '';
				$plans = tara_hub_plans();
				if ( $term <= 0 || ! tara_artist_is_verified( $term ) ) {
					wp_send_json_error( array( 'message' => 'خرید پلن برای هنرمند تأییدشده فعال است.' ) );
				}
				if ( ! isset( $plans[ $plan ] ) ) { wp_send_json_error( array( 'message' => 'پلن پیدا نشد.' ) ); }
				$views = tara_hub_total_views( $term );
				$quote = 'promo' === $plan ? tara_hub_promo_quote( $views ) : array( 'final' => max( 0, (int) $plans[ $plan ]['price'] ) );
				if ( $quote['final'] > 0 && ! tara_hub_wc_ready() ) {
					wp_send_json_error( array( 'message' => 'پرداخت فعال نیست؛ ووکامرس و واحد پول تومان (IRT) یا ریال (IRR) را بررسی کنید.' ) );
				}
				$id = tara_hub_request_add( $user_id, array( 'type' => 'plan', 'plan' => $plan, 'title' => 'پلن ' . $plans[ $plan ]['name'], 'price' => $quote['final'], 'views' => $views ) );
				if ( '' === $id ) { wp_send_json_error( array( 'message' => 'ثبت درخواست ناموفق بود.' ) ); }
				if ( $quote['final'] <= 0 ) {
					wp_send_json_success( array( 'id' => $id, 'message' => 'درخواست رایگان ثبت شد و در انتظار بررسی است.' ) );
				}
				$order = tara_hub_wc_order( $user_id, $id, $plan, $quote['final'] );
				if ( is_wp_error( $order ) ) {
					$rows = tara_hub_requests( $user_id );
					foreach ( $rows as &$row ) { if ( $row['id'] === $id ) { $row['status'] = 'rejected'; $row['note'] = 'خطا در ایجاد سفارش ووکامرس'; break; } }
					unset( $row );
					update_user_meta( $user_id, 'tara_artist_requests', $rows );
					wp_send_json_error( array( 'message' => $order->get_error_message() ) );
				}
				$rows = tara_hub_requests( $user_id );
				foreach ( $rows as &$row ) {
					if ( $row['id'] === $id ) { $row['order_id'] = (int) $order->get_id(); break; }
				}
				unset( $row );
				if ( false === update_user_meta( $user_id, 'tara_artist_requests', $rows ) ) {
					$order->update_status( 'cancelled', 'ثبت ارتباط سفارش و درخواست ناموفق بود.' );
					wp_send_json_error( array( 'message' => 'ذخیره سفارش ناموفق بود؛ دوباره تلاش کنید.' ) );
				}
				wp_send_json_success( array( 'id' => $id, 'checkout' => $order->get_checkout_payment_url(), 'message' => 'در حال انتقال به پرداخت…' ) );
				break;

			case 'submit_track':
				if ( $term <= 0 ) {
					wp_send_json_error( array( 'message' => 'هنوز حسابِ هنرمندیِ شما تأیید نشده است.' ) );
				}
				$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['title'] ) ) : '';
				$audio = isset( $_POST['audio'] ) ? esc_url_raw( wp_unslash( (string) $_POST['audio'] ) ) : '';
				$body  = isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['body'] ) ) : '';
				if ( '' === $title || '' === $audio ) {
					wp_send_json_error( array( 'message' => 'نامِ اثر و آدرسِ فایلِ صوتی لازم است.' ) );
				}
				$post_id = wp_insert_post(
					array(
						'post_type'    => 'post',
						'post_status'  => 'draft',
						'post_title'   => $title,
						'post_content' => $body,
						'post_author'  => $user_id,
					),
					true
				);
				if ( is_wp_error( $post_id ) || ! $post_id ) {
					wp_send_json_error( array( 'message' => 'اثر ثبت نشد؛ دوباره تلاش کنید.' ) );
				}
				update_post_meta( (int) $post_id, 'music320', $audio );
				$tax = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
				wp_set_object_terms( (int) $post_id, array( $term ), $tax );
				tara_hub_request_add(
					$user_id,
					array(
						'type'    => 'track',
						'title'   => 'اثرِ تازه: ' . $title,
						'body'    => 'به‌صورتِ پیش‌نویس ساخته شد و منتظرِ بررسی و انتشار است.',
						'post_id' => (int) $post_id,
					)
				);
				wp_send_json_success( array( 'post' => (int) $post_id, 'message' => 'اثر با موفقیت ارسال شد و بعد از بررسی منتشر می‌شود.' ) );
				break;

			case 'request':
				if ( $term <= 0 ) {
					wp_send_json_error( array( 'message' => 'هنوز حسابِ هنرمندیِ شما تأیید نشده است.' ) );
				}
				$type  = isset( $_POST['rtype'] ) ? sanitize_key( wp_unslash( (string) $_POST['rtype'] ) ) : 'support';
				$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['title'] ) ) : '';
				$body  = isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['body'] ) ) : '';
				if ( '' === $title && '' === $body ) {
					wp_send_json_error( array( 'message' => 'متنِ درخواست خالی است.' ) );
				}
				$id = tara_hub_request_add(
					$user_id,
					array(
						'type'    => in_array( $type, array( 'lyrics', 'edit', 'promo', 'support' ), true ) ? $type : 'support',
						'title'   => '' !== $title ? $title : 'درخواستِ هنرمند',
						'body'    => $body,
						'post_id' => isset( $_POST['post'] ) ? (int) $_POST['post'] : 0,
					)
				);
				wp_send_json_success( array( 'id' => $id, 'message' => 'درخواست ثبت شد؛ پاسخ را همین‌جا می‌بینید.' ) );
				break;
		}

		wp_send_json_error( array( 'message' => 'درخواستِ نامشخص.' ) );
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_ajax_tara_hub', 'tara_hub_ajax' );
}

/* -------------------------------------------------------------------------
 * خروجیِ تب‌ها
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_hub_status_label' ) ) {
	/** برچسبِ فارسیِ وضعیت. */
	function tara_hub_status_label( $status ) {
		$map = array(
			'pending'  => 'در انتظار بررسی',
			'approved' => 'تأیید شده',
			'paid'     => 'پرداخت شده',
			'refunded' => 'مسترد شده',
			'done'     => 'انجام شده',
			'rejected' => 'رد شده',
		);
		return isset( $map[ $status ] ) ? $map[ $status ] : 'در انتظار بررسی';
	}
}

if ( ! function_exists( 'tara_hub_tab_artist' ) ) {
	/**
	 * تبِ «پنل هنرمند» در حسابِ کاربری.
	 *
	 * @param int $user_id کاربر.
	 * @return string
	 */
	function tara_hub_tab_artist( $user_id ) {
		if ( ! tara_hub_on() ) {
			return '<div class="ty-hub"><p class="ty-hub__empty">پنلِ هنرمند در این سایت خاموش است.</p></div>';
		}
		$term_id = tara_hub_user_term( $user_id );
		$plans   = tara_hub_plans();
		$reqs    = tara_hub_requests( $user_id );

		ob_start();
		?>
		<div class="ty-hub">
			<header class="ty-hub__head">
				<h2>پنل هنرمند</h2>
				<p>اثر منتشر کنید، آمار ببینید، پلن بخرید و برای هر تغییری درخواست بدهید.</p>
			</header>

			<?php if ( $term_id <= 0 ) : ?>
				<section class="ty-hub__card ty-hub__card--claim">
					<h3>ارتقا به حسابِ هنرمند</h3>
					<p>اگر خواننده، آهنگساز یا صاحبِ اثر هستید، درخواست بدهید. بعد از تأییدِ مدیر، صفحهٔ هنرمند به حسابِ شما وصل می‌شود، تیکِ آبی می‌گیرید و می‌توانید آثارتان را خودتان مدیریت کنید.</p>
					<form class="ty-hub__form" data-tara-hub="claim">
						<label>نامِ هنری<input type="text" name="artist_name" required maxlength="80" placeholder="مثلاً: نامِ خواننده"></label>
						<label>لینکِ نمونه‌کار / مدرکِ مالکیت<textarea name="body" rows="3" maxlength="800" placeholder="لینکِ اینستاگرام، کانال، یا هر مدرکی که نشان بدهد این صفحه مالِ شماست…"></textarea></label>
						<button type="submit" class="ty-hub__btn">ثبتِ درخواستِ ارتقا</button>
						<p class="ty-hub__msg" role="status" data-tara-hub-msg></p>
					</form>
				</section>
			<?php else : ?>
				<?php
				$term    = get_term( $term_id );
				$tracks  = tara_hub_tracks( $term_id, 100 );
				$views   = tara_hub_total_views( $term_id );
				$follows = function_exists( 'tara_artist_followers' ) ? tara_artist_followers( $term_id ) : 0;
				$quote   = tara_hub_promo_quote( $views );
				?>
				<section class="ty-hub__card">
					<h3><?php echo esc_html( $term ? $term->name : 'هنرمند' ); ?>
						<?php echo function_exists( 'tara_artist_verified_badge' ) ? tara_artist_verified_badge( $term_id ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</h3>
					<div class="ty-hub__stats">
						<div><b><?php echo esc_html( number_format_i18n( count( $tracks ) ) ); ?></b><span>اثر</span></div>
						<div><b><?php echo esc_html( number_format_i18n( $views ) ); ?></b><span>مجموع بازدید</span></div>
						<div><b><?php echo esc_html( number_format_i18n( $follows ) ); ?></b><span>دنبال‌کننده</span></div>
						<div><b><?php echo esc_html( tara_hub_money( $quote['discount'] ) ); ?></b><span>تخفیفِ تبلیغِ فعلی</span></div>
					</div>
					<?php if ( $term ) : ?>
						<a class="ty-hub__link" href="<?php echo esc_url( get_term_link( $term ) ); ?>">مشاهدهٔ صفحهٔ عمومیِ هنرمند ←</a>
					<?php endif; ?>
				</section>

				<section class="ty-hub__card">
					<h3>آثارِ من</h3>
					<?php if ( empty( $tracks ) ) : ?>
						<p class="ty-hub__empty">هنوز اثری به نامِ شما ثبت نشده است؛ اولین اثر را از فرمِ زیر بفرستید.</p>
					<?php else : ?>
						<ul class="ty-hub__tracks">
							<?php foreach ( $tracks as $post ) : ?>
								<li>
									<a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
									<span><?php echo esc_html( number_format_i18n( (int) get_post_meta( $post->ID, 'views', true ) ) ); ?> بازدید · <?php echo esc_html( get_post_status( $post ) === 'publish' ? 'منتشر شده' : 'در انتظار انتشار' ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>

				<section class="ty-hub__card">
					<h3>ارسالِ اثرِ تازه</h3>
					<form class="ty-hub__form" data-tara-hub="submit_track">
						<label>نامِ اثر<input type="text" name="title" required maxlength="120"></label>
						<label>آدرسِ فایلِ صوتی (MP3)<input type="url" name="audio" required dir="ltr" placeholder="https://…/song.mp3"></label>
						<label>متنِ ترانه / توضیح<textarea name="body" rows="4" maxlength="4000"></textarea></label>
						<button type="submit" class="ty-hub__btn">ارسال برای انتشار</button>
						<p class="ty-hub__msg" role="status" data-tara-hub-msg></p>
					</form>
				</section>

				<section class="ty-hub__card">
					<h3>درخواستِ تغییر یا پشتیبانی</h3>
					<form class="ty-hub__form" data-tara-hub="request">
						<label>نوعِ درخواست
							<select name="rtype">
								<option value="lyrics">ویرایشِ متنِ ترانه</option>
								<option value="edit">ویرایشِ مشخصاتِ اثر</option>
								<option value="promo">تبلیغِ یک اثر</option>
								<option value="support">موضوعِ دیگر</option>
							</select>
						</label>
						<label>عنوان<input type="text" name="title" maxlength="140" placeholder="مثلاً: اصلاحِ متنِ ترانهٔ «…»"></label>
						<label>توضیح<textarea name="body" rows="4" maxlength="2000" placeholder=" دقیق بنویسید چه چیزی و چطور عوض شود…"></textarea></label>
						<button type="submit" class="ty-hub__btn ty-hub__btn--ghost">ثبتِ درخواست</button>
						<p class="ty-hub__msg" role="status" data-tara-hub-msg></p>
					</form>
				</section>
			<?php endif; ?>

			<section class="ty-hub__card">
				<h3>پلن‌های انتشار و تبلیغ</h3>
				<div class="ty-hub__plans">
					<?php foreach ( $plans as $key => $plan ) : ?>
						<article class="ty-hub__plan<?php echo 'vip' === $key ? ' is-vip' : ''; ?>">
							<h4><?php echo esc_html( $plan['name'] ); ?></h4>
							<p><?php echo esc_html( $plan['desc'] ); ?></p>
							<ul>
								<?php foreach ( (array) $plan['feats'] as $feat ) : ?>
									<li><?php echo esc_html( $feat ); ?></li>
								<?php endforeach; ?>
							</ul>
							<strong class="ty-hub__price"><?php echo esc_html( tara_hub_money( (int) $plan['price'] ) ); ?></strong>
							<?php if ( 'promo' === $key && $term_id > 0 ) : ?>
								<p class="ty-hub__quote">با بازدیدِ فعلی، تخفیفِ شما: <b><?php echo esc_html( tara_hub_money( $quote['discount'] ) ); ?></b> — مبلغِ نهایی: <b><?php echo esc_html( tara_hub_money( $quote['final'] ) ); ?></b></p>
							<?php endif; ?>
							<form data-tara-hub="buy">
								<input type="hidden" name="plan" value="<?php echo esc_attr( $key ); ?>">
								<button type="submit" class="ty-hub__btn">انتخاب و پرداخت پلن</button>
								<p class="ty-hub__msg" role="status" data-tara-hub-msg></p>
							</form>
						</article>
					<?php endforeach; ?>
				</div>
				<p class="ty-hub__hint">برای پرداخت آنلاین باید ووکامرس و درگاه پرداخت فعال باشند و واحد پول فروشگاه تومان (IRT) یا ریال (IRR) باشد. درخواست پس از پرداخت برای بررسی مدیر ارسال می‌شود.</p>
			</section>

			<?php if ( ! empty( $reqs ) ) : ?>
				<section class="ty-hub__card">
					<h3>درخواست‌های من</h3>
					<ul class="ty-hub__reqs">
						<?php foreach ( $reqs as $row ) : ?>
							<li>
								<b><?php echo esc_html( isset( $row['title'] ) ? $row['title'] : 'درخواست' ); ?></b>
								<span class="ty-hub__status is-<?php echo esc_attr( isset( $row['status'] ) ? $row['status'] : 'pending' ); ?>"><?php echo esc_html( tara_hub_status_label( isset( $row['status'] ) ? $row['status'] : 'pending' ) ); ?></span>
								<small><?php echo esc_html( date_i18n( 'j F Y', isset( $row['created'] ) ? (int) $row['created'] : time() ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions ?></small>
								<?php if ( 'pending' === $row['status'] && ! empty( $row['order_id'] ) && function_exists( 'wc_get_order' ) ) :
									$order = wc_get_order( (int) $row['order_id'] );
									if ( $order && (int) $order->get_customer_id() === (int) $user_id && $order->needs_payment() ) : ?>
										<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>">ادامه پرداخت</a>
									<?php endif; ?>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'tara_dm_tab' ) ) {
	/**
	 * تبِ «دایرکت» در حسابِ کاربری.
	 *
	 * @param int $user_id کاربر.
	 * @return string
	 */
	function tara_dm_tab( $user_id ) {
		if ( ! function_exists( 'tara_dm_on' ) || ! tara_dm_on() ) {
			return '<div class="ty-hub"><p class="ty-hub__empty">دایرکت در این سایت خاموش است.</p></div>';
		}
		$term = tara_hub_user_term( $user_id );
		ob_start();
		?>
		<div class="ty-hub">
			<header class="ty-hub__head"><h2>دایرکت‌ها</h2><p>گفت‌وگوهای مستقیم با هنرمندان؛ درست مثلِ اینستاگرام.</p></header>
			<?php if ( $term > 0 && function_exists( 'tara_dm_threads_for_artist' ) ) : ?>
				<section class="ty-hub__card">
					<h3>پیام‌های طرفداران</h3>
					<?php $rows = tara_dm_threads_for_artist( $term ); ?>
					<?php if ( empty( $rows ) ) : ?>
						<p class="ty-hub__empty">هنوز پیامی برای شما نیامده است.</p>
					<?php else : ?>
						<ul class="ty-hub__threads">
							<?php foreach ( $rows as $row ) : ?>
								<li>
									<button type="button" class="ty-hub__thread" data-tara-dm="<?php echo esc_attr( (string) $term ); ?>" data-tara-dm-fan="<?php echo esc_attr( (string) $row['fan_id'] ); ?>">
										<b><?php echo esc_html( $row['name'] ); ?></b>
										<span><?php echo esc_html( wp_trim_words( (string) $row['last'], 10, '…' ) ); ?></span>
										<?php if ( (int) $row['unread'] > 0 ) : ?><em><?php echo esc_html( number_format_i18n( (int) $row['unread'] ) ); ?></em><?php endif; ?>
									</button>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<section class="ty-hub__card">
				<h3>گفت‌وگوهای من با هنرمندان</h3>
				<?php $mine = function_exists( 'tara_dm_threads_for_user' ) ? tara_dm_threads_for_user( $user_id ) : array(); ?>
				<?php if ( empty( $mine ) ) : ?>
					<p class="ty-hub__empty">هنوز به هنرمندی پیام نداده‌اید. از صفحهٔ هر هنرمندِ تأییدشده، دکمهٔ «پیام مستقیم» را بزنید.</p>
				<?php else : ?>
					<ul class="ty-hub__threads">
						<?php foreach ( $mine as $row ) : ?>
							<li>
								<button type="button" class="ty-hub__thread" data-tara-dm="<?php echo esc_attr( (string) $row['term_id'] ); ?>">
									<b><?php echo esc_html( $row['name'] ); ?></b>
									<span><?php echo esc_html( wp_trim_words( (string) $row['last'], 10, '…' ) ); ?></span>
									<?php if ( (int) $row['unread'] > 0 ) : ?><em><?php echo esc_html( number_format_i18n( (int) $row['unread'] ) ); ?></em><?php endif; ?>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

/* -------------------------------------------------------------------------
 * صفحهٔ مدیر: بررسیِ درخواست‌ها
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'tara_hub_admin_menu' ) ) {
	/** منوی مدیر. */
	function tara_hub_admin_menu() {
		add_menu_page( 'درخواست‌های هنرمندان', 'درخواست‌های هنرمندان', 'manage_options', 'tara-hub', 'tara_hub_admin_page', 'dashicons-microphone', 58 );
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'admin_menu', 'tara_hub_admin_menu' );
}

if ( ! function_exists( 'tara_hub_all_requests' ) ) {
	/** همهٔ درخواست‌ها از متای کاربران. */
	function tara_hub_all_requests() {
		$out   = array();
		$users = get_users( array( 'meta_key' => 'tara_artist_requests', 'number' => 200 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		foreach ( (array) $users as $user ) {
			foreach ( tara_hub_requests( (int) $user->ID ) as $row ) {
				$row['user_id'] = (int) $user->ID;
				$row['user']    = $user->display_name;
				$out[]          = $row;
			}
		}
		usort(
			$out,
			function ( $a, $b ) {
				return (int) $b['created'] <=> (int) $a['created'];
			}
		);
		return $out;
	}
}

if ( ! function_exists( 'tara_hub_admin_handle' ) ) {
	/** انجامِ عملیاتِ مدیر روی یک درخواست. */
	function tara_hub_admin_handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['tara_hub_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['tara_hub_nonce'] ) ), 'tara_hub_admin' ) ) {
			return;
		}
		$user_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
		$req_id  = isset( $_POST['req_id'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['req_id'] ) ) : '';
		$action  = isset( $_POST['hub_action'] ) ? sanitize_key( wp_unslash( (string) $_POST['hub_action'] ) ) : '';
		$term_id = isset( $_POST['term_id'] ) ? (int) $_POST['term_id'] : 0;
		$verify  = isset( $_POST['verify'] );
		if ( $user_id <= 0 || '' === $req_id ) {
			return;
		}
		$rows = tara_hub_requests( $user_id );
		foreach ( $rows as &$row ) {
			if ( ! isset( $row['id'] ) || $row['id'] !== $req_id ) {
				continue;
			}
			if ( 'approve' === $action ) {
				if ( 'upgrade' === $row['type'] && ( $term_id <= 0 || ! term_exists( $term_id, function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist' ) || (int) get_term_meta( $term_id, 'tara_artist_user', true ) ) ) {
					continue;
				}
				$row['status'] = 'approved';
				if ( 'upgrade' === $row['type'] && $term_id > 0 && term_exists( $term_id, function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist' ) && ! (int) get_term_meta( $term_id, 'tara_artist_user', true ) ) {
					update_user_meta( $user_id, 'tara_artist_term', $term_id );
					update_term_meta( $term_id, 'tara_artist_user', $user_id );
					if ( $verify && function_exists( 'tara_artist_verify' ) ) {
						tara_artist_verify( $term_id, true );
					}
				}
			} elseif ( 'paid' === $action ) {
				$row['status'] = 'paid';
			} elseif ( 'done' === $action ) {
				$row['status'] = 'done';
			} elseif ( 'reject' === $action ) {
				$row['status'] = 'rejected';
			}
		}
		unset( $row );
		update_user_meta( $user_id, 'tara_artist_requests', $rows );
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'admin_init', 'tara_hub_admin_handle' );
}

if ( ! function_exists( 'tara_hub_admin_page' ) ) {
	/** صفحهٔ مدیر. */
	function tara_hub_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$rows = tara_hub_all_requests();
		$tax  = function_exists( 'tara_artist_tax' ) ? tara_artist_tax() : 'artist';
		$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'number' => 500 ) );
		?>
		<div class="wrap">
			<h1>درخواست‌های هنرمندان</h1>
			<p>برای تأییدِ ارتقا: هنرمند را از فهرست انتخاب و «تأیید» را بزنید؛ حسابِ کاربر همان لحظه به صفحهٔ هنرمند وصل می‌شود.</p>
			<table class="widefat striped">
				<thead><tr><th>کاربر</th><th>درخواست</th><th>جزئیات</th><th>مبلغ</th><th>وضعیت</th><th>عملیات</th></tr></thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="6">هنوز درخواستی ثبت نشده است.</td></tr>
				<?php endif; ?>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['user'] ); ?> (#<?php echo esc_html( (string) $row['user_id'] ); ?>)</td>
						<td><b><?php echo esc_html( $row['title'] ); ?></b><br><small><?php echo esc_html( date_i18n( 'Y/m/d H:i', (int) $row['created'] ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions ?></small></td>
						<td><?php echo esc_html( wp_trim_words( (string) $row['body'], 24, '…' ) ); ?></td>
						<td><?php echo ! empty( $row['price'] ) ? esc_html( tara_hub_money( (int) $row['price'] ) ) : '—'; ?></td>
						<td><?php echo esc_html( tara_hub_status_label( $row['status'] ) ); ?></td>
						<td>
							<form method="post" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
								<?php wp_nonce_field( 'tara_hub_admin', 'tara_hub_nonce' ); ?>
								<input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $row['user_id'] ); ?>">
								<input type="hidden" name="req_id" value="<?php echo esc_attr( $row['id'] ); ?>">
								<select name="term_id">
									<option value="0">— اتصال به هنرمند —</option>
									<?php foreach ( (array) $terms as $term ) : ?>
										<option value="<?php echo esc_attr( (string) $term->term_id ); ?>"><?php echo esc_html( $term->name ); ?></option>
									<?php endforeach; ?>
								</select>
								<label><input type="checkbox" name="verify" value="1"> تیک آبی</label>
								<button class="button button-primary" name="hub_action" value="approve">تأیید</button>
								<button class="button" name="hub_action" value="paid">پرداخت شد</button>
								<button class="button" name="hub_action" value="done">انجام شد</button>
								<button class="button" name="hub_action" value="reject">رد</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}


/* -------------------------------------------------------------------------
 * دارایی‌های پنل هنرمند و دایرکت
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'tara_hub_assets' ) ) {
	/** CSS/JS پنل هنرمند و دایرکت — فقط برای کاربر واردشده. */
	function tara_hub_assets() {
		$css = '/css/tara-hub.css';
		if ( function_exists( 'tara_css_rel' ) ) {
			$css = tara_css_rel( $css );
		}
		if ( file_exists( TARANEHYAR_DIR . $css ) ) {
			wp_enqueue_style( 'taranehyar-hub', TARANEHYAR_URI . $css, array(), function_exists( 'taranehyar_asset_ver' ) ? taranehyar_asset_ver( $css ) : TARANEHYAR_VERSION );
		}
		if ( file_exists( TARANEHYAR_DIR . '/js/tara-hub.js' ) ) {
			wp_enqueue_script( 'taranehyar-hub', TARANEHYAR_URI . '/js/tara-hub.js', array(), function_exists( 'taranehyar_asset_ver' ) ? taranehyar_asset_ver( '/js/tara-hub.js' ) : TARANEHYAR_VERSION, true );
		}
	}
}
if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_enqueue_scripts', 'tara_hub_assets', 32 );
}
