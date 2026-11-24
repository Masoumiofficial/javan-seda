<?php
/** Account UI for the release workflow. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tara_release_render_wizard( $user_id = 0, $view = 'all' ) {
	$catalog = tara_release_catalog();
	$opt = function ( $key, $default ) { $value = function_exists( 'ot_get_option' ) ? ot_get_option( $key, $default ) : $default; return is_string( $value ) ? wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) : $default; };
	$allow_new = 'on' === $opt( 'release_enabled', 'off' );
	if ( ! $allow_new ) {
		$has_release = false;
		if ( $user_id > 0 && function_exists( 'tara_hub_requests' ) ) { foreach ( tara_hub_requests( $user_id ) as $request ) { if ( isset( $request['type'] ) && 'release' === $request['type'] ) { $has_release = true; break; } } }
		if ( ! $has_release && 'requests' !== $view ) { return ''; }
	}
	$rules_url = esc_url( $opt( 'release_rules_url', 'https://ahangban.ir/releases/' ), array( 'http', 'https' ) );
	$payment_ready = 'on' === $opt( 'release_payment_on', 'on' ) && function_exists( 'tara_hub_wc_ready' ) && tara_hub_wc_ready();
	ob_start(); ?>
	<section class="tara-release" dir="rtl">
		<?php if ( $allow_new && 'requests' !== $view ) : ?>
		<header><h2><?php echo esc_html( $opt( 'release_title', 'آثارت را به مخاطبان بیشتری معرفی کن' ) ); ?></h2><p><?php echo esc_html( $opt( 'release_subtitle', 'نوع اثر و پلن را انتخاب کن، فایل‌ها را بفرست و همان‌جا پرداخت کن.' ) ); ?></p></header>
		<div class="tara-release__notice"><p>پیش از ثبت، قوانین و جزئیات پلن‌ها را از <a href="<?php echo $rules_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url above ?>" target="_blank" rel="noopener noreferrer">صفحه انتشار و تعرفه‌ها</a> با دقت مطالعه کنید.</p></div>
		<div class="tara-release__prices" aria-label="تعرفه‌های ثابت">
			<?php foreach ( $catalog as $key => $service ) : if ( 'international' === $key ) { continue; } ?>
				<div><strong><?php echo esc_html( $service['label'] ); ?></strong><span><?php echo esc_html( number_format_i18n( $service['amount'] ) ); ?> تومان</span></div>
			<?php endforeach; ?>
		</div>
		<form class="tara-release__form" data-release-wizard method="post" enctype="multipart/form-data" data-max-audio="31457280" data-max-cover="5242880">
			<input type="hidden" name="submission_token" value="<?php echo esc_attr( str_replace( '-', '', wp_generate_uuid4() ) ); ?>">
			<input type="hidden" name="wizard_version" value="2">
			<div class="tara-release__progress"><p data-release-progress aria-live="polite"></p><div class="tara-release__progress-track" aria-hidden="true"><span data-release-progress-bar></span></div></div>
			<fieldset data-release-step><legend>نوع اثر و پلن</legend>
			<label>پلن انتشار *<select name="service" required><option value="">انتخاب کنید</option><?php foreach ( $catalog as $key => $service ) : if ( 'international' === $key ) { continue; } ?><option value="<?php echo esc_attr( $key ); ?>" data-price="<?php echo esc_attr( (string) $service['amount'] ); ?>"><?php echo esc_html( $service['label'] . ' — ' . number_format_i18n( $service['amount'] ) . ' تومان' ); ?></option><?php endforeach; ?></select></label>
			<label>نام اثر فارسی *<input name="title" maxlength="180" required></label><label>نام اثر انگلیسی *<input name="title_en" dir="ltr" maxlength="180" required></label>
			<label>نام هنرمند فارسی *<input name="performer" maxlength="120" required></label><label>نام هنرمند انگلیسی *<input name="performer_en" dir="ltr" maxlength="120" required></label>
			<p class="tara-release__hint">تعرفه‌ها ثابت‌اند؛ پس از ثبت درخواست، فاکتور با همین مبلغ صادر و شما به پرداخت منتقل می‌شوید.</p></fieldset>
			<fieldset data-release-step><legend>فایل‌ها و ترانه</legend>
			<?php if ( 'on' === $opt( 'release_uploads_on', 'on' ) ) : ?>
			<label>فایل MP3 کیفیت ۱۲۸<input type="file" name="audio128" accept=".mp3,audio/mpeg"></label><label>فایل MP3 کیفیت ۳۲۰<input type="file" name="audio320" accept=".mp3,audio/mpeg"></label>
			<label>کاور JPG یا PNG<input type="file" name="cover_file" accept=".jpg,.jpeg,.png"></label><label>مدرک مالکیت (اختیاری)<input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png"></label>
			<?php if ( is_wp_error( tara_release_private_dir() ) ) : ?><p class="tara-release__warning">فضای خصوصی آپلود هنوز آماده نیست؛ تا تنظیم آن، ثبت درخواست با فایل ممکن نیست. با مدیر تماس بگیرید.</p><?php endif; ?>
			<?php else : ?><p class="tara-release__warning">آپلود خصوصی خاموش است؛ برای ارسال فایل با مدیر تماس بگیرید.</p><?php endif; ?>
			<p class="tara-release__hint">حداقل یک فایل MP3 کیفیت ۱۲۸ یا ۳۲۰؛ هر صوت تا ۳۰ و کاور تا ۵ مگابایت.</p>
			<label>متن ترانه<textarea name="lyrics" rows="5" maxlength="20000"></textarea></label><label>ترانه‌سرا<input name="lyricist" maxlength="120"></label><label>آهنگساز<input name="composer" maxlength="120"></label><label>تنظیم‌کننده<input name="arranger" maxlength="120"></label><label>میکس و مستر<input name="mixer" maxlength="120"></label><label>تهیه‌کننده<input name="producer" maxlength="120"></label><label>توضیحات و مدارک<textarea name="legal" maxlength="4000"></textarea></label></fieldset>
			<fieldset data-release-step><legend>تایید و پرداخت</legend>
			<div class="tara-release__review"><p data-release-summary></p></div>
			<label class="tara-release__check"><input type="checkbox" name="rights" value="1" required> صاحب حقوق اثر هستم یا مجوز لازم را دارم.</label>
			<label class="tara-release__check"><input type="checkbox" name="terms" value="1" required> <a href="<?php echo $rules_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url above ?>" target="_blank" rel="noopener noreferrer">قوانین و جزئیات پلن‌ها</a> را با دقت مطالعه کردم و می‌پذیرم.</label>
			<p class="tara-release__hint">با ثبت درخواست، فاکتور به مبلغ پلن انتخابی صادر می‌شود و به درگاه پرداخت منتقل می‌شوید.</p>
			<?php if ( ! $payment_ready ) : ?><p class="tara-release__warning">پرداخت آنلاین موقتاً فعال نیست؛ درخواست شما ثبت می‌شود و مدیر برای هماهنگی پرداخت پیگیری می‌کند.</p><?php endif; ?>
			</fieldset>
			<div class="tara-release__actions"><button type="button" data-release-prev>قبلی</button><button type="button" data-release-next>بعدی</button><button type="submit" data-release-submit>ثبت درخواست و پرداخت</button></div>
			<p data-release-message role="status" aria-live="polite"></p>
		</form>
		<?php endif; ?>
		<?php if ( $user_id > 0 && 'form' !== $view ) : ?><h2>درخواست‌های پخش آثار</h2><?php $found_release = false; $statuses = tara_release_statuses(); foreach ( tara_hub_requests( $user_id ) as $row ) : if ( ! isset( $row['type'], $row['id'], $row['status'] ) || 'release' !== $row['type'] ) { continue; } $found_release = true; ?>
		<article class="tara-release__request"><h3><?php echo esc_html( $row['title'] ); ?></h3><p>کد رهگیری: <span dir="ltr"><?php echo esc_html( $row['id'] ); ?></span></p><p>وضعیت: <?php echo esc_html( isset( $statuses[ $row['status'] ] ) ? $statuses[ $row['status'] ] : $row['status'] ); ?><?php if ( ! empty( $row['order_id'] ) ) : ?> · شماره سفارش: <?php echo esc_html( (string) $row['order_id'] ); ?><?php endif; ?></p>
		<?php $pct = array( 'new' => 10, 'review' => 25, 'documents' => 35, 'approved' => 50, 'awaiting_payment' => 60, 'paid' => 75, 'preparing' => 85, 'published' => 100, 'refunded' => 100, 'rejected' => 100, 'cancelled' => 100 ); $progress = isset( $pct[ $row['status'] ] ) ? $pct[ $row['status'] ] : 0; ?>
		<div class="tara-release__request-progress" aria-label="پیشرفت <?php echo esc_attr( (string) $progress ); ?> درصد"><span style="width:<?php echo esc_attr( (string) $progress ); ?>%"></span></div><small><?php echo esc_html( number_format_i18n( $progress ) ); ?>٪</small>
		<?php if ( ! empty( $row['note'] ) ) : ?><p>یادداشت مدیر: <?php echo esc_html( $row['note'] ); ?></p><?php endif; ?>
		<?php if ( ! empty( $row['publication_url'] ) ) : ?><p><a href="<?php echo esc_url( $row['publication_url'] ); ?>" target="_blank" rel="noopener noreferrer">لینک انتشار</a></p><?php endif; ?>
		<?php if ( ! empty( $row['history'] ) && is_array( $row['history'] ) ) : ?><details><summary>تاریخچه وضعیت</summary><?php foreach ( $row['history'] as $history ) : if ( ! isset( $history['status'], $history['time'] ) ) { continue; } ?><p><?php echo esc_html( isset( $statuses[ $history['status'] ] ) ? $statuses[ $history['status'] ] : $history['status'] ); ?> — <?php echo esc_html( date_i18n( 'Y/m/d H:i', (int) $history['time'] ) ); ?></p><?php endforeach; ?></details><?php endif; ?>
		<?php if ( ! empty( $row['order_id'] ) && function_exists( 'wc_get_order' ) ) : $order = wc_get_order( (int) $row['order_id'] ); if ( $order && (int) $order->get_customer_id() === (int) $user_id ) : ?>
			<p>سفارش #<?php echo esc_html( (string) $row['order_id'] ); ?><?php if ( isset( $row['final_toman'] ) ) : ?> — مبلغ: <?php echo esc_html( number_format_i18n( (int) $row['final_toman'] ) ); ?> تومان<?php endif; ?><?php if ( $order->needs_payment() ) : ?> — <a class="tara-release__pay" href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>">پرداخت سفارش</a><?php elseif ( $order->is_paid() ) : ?> — پرداخت‌شده<?php endif; ?></p>
		<?php endif; endif; ?>
		<?php if ( 'awaiting_payment' === $row['status'] && empty( $row['order_id'] ) && isset( $row['final_toman'], $row['extra_costs'], $row['refund_terms'], $row['quote_revision'] ) ) : ?>
			<p>مبلغ نهایی: <?php echo esc_html( number_format_i18n( (int) $row['final_toman'] ) ); ?> تومان</p><p>هزینه‌های جانبی: <?php echo esc_html( $row['extra_costs'] ); ?></p><p>شرایط لغو و بازپرداخت: <?php echo esc_html( $row['refund_terms'] ); ?></p>
			<?php if ( 'international' !== $row['service'] && 'on' === $opt( 'release_payment_on', 'on' ) ) : ?><form data-release-checkout><input type="hidden" name="request_id" value="<?php echo esc_attr( $row['id'] ); ?>"><input type="hidden" name="quote_hash" value="<?php echo esc_attr( tara_release_quote_hash( $row ) ); ?>"><label><input type="checkbox" name="accept_quote" value="1" required> مبلغ، هزینه‌ها و شرایط لغو و بازپرداخت این سفارش را می‌پذیرم.</label><button type="submit">ادامه پرداخت</button><p role="status" data-release-message></p></form><?php endif; ?>
		<?php endif; ?></article>
		<?php endforeach; if ( ! $found_release ) : ?><p>هنوز درخواست پخشی ثبت نشده است.</p><?php endif; endif; ?>
	</section>
	<?php return ob_get_clean();
}
