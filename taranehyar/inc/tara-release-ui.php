<?php
/** UI for the in-progress release workflow. Not hooked into the account yet. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tara_release_render_wizard( $user_id = 0 ) {
	$catalog = tara_release_catalog();
	$condition_defaults = array( 1 => 'ارائه‌دهنده باید مالک حقوق اثر باشد یا مجوزهای لازم را داشته باشد.', 2 => 'صحت اطلاعات هویتی و مدارک بر عهده درخواست‌دهنده است.', 3 => 'محتوا و مدارک پیش از پذیرش بررسی می‌شوند.', 4 => 'ارسال درخواست تضمینی برای انتشار، زمان انتشار یا درآمد نیست.', 5 => 'کیفیت فنی فایل و کاور باید پس از بررسی قابل قبول باشد.', 6 => 'تعرفه پایه قطعی نیست؛ مبلغ و هزینه‌های جانبی پیش از پرداخت اعلام می‌شوند.', 7 => 'شرایط دقیق لغو و بازپرداخت هر سفارش پیش از پرداخت برای پذیرش ارائه می‌شود.', 8 => 'انتشار بین‌المللی تنها پس از بررسی قانونی، هویت و امکان تسویه قابل تصمیم‌گیری است.' );
	$opt = function ( $key, $default ) { $value = function_exists( 'ot_get_option' ) ? ot_get_option( $key, $default ) : $default; return is_string( $value ) ? $value : $default; };
	$international_single = $opt( 'release_int_single_price', '8900000' );
	$international_album = $opt( 'release_int_album_price', '19900000' );
	if ( ! preg_match( '/^[0-9]{1,10}$/D', $international_single ) || (float) $international_single > 2000000000 ) { $international_single = '8900000'; }
	if ( ! preg_match( '/^[0-9]{1,10}$/D', $international_album ) || (float) $international_album > 2000000000 ) { $international_album = '19900000'; }
	$allow_new = 'on' === $opt( 'release_enabled', 'off' );
	if ( ! $allow_new ) {
		$has_release = false;
		if ( $user_id > 0 && function_exists( 'tara_hub_requests' ) ) { foreach ( tara_hub_requests( $user_id ) as $request ) { if ( isset( $request['type'] ) && 'release' === $request['type'] ) { $has_release = true; break; } } }
		if ( ! $has_release ) { return ''; }
	}
	$rules_url = esc_url( $opt( 'release_rules_url', 'https://ahangban.ir/works/' ), array( 'http', 'https' ) );
	ob_start(); ?>
	<section class="tara-release" dir="rtl">
		<?php if ( $allow_new ) : ?>
		<header><h2><?php echo esc_html( $opt( 'release_title', 'آثارت را به مخاطبان بیشتری معرفی کن' ) ); ?></h2><p><?php echo esc_html( $opt( 'release_subtitle', 'درخواست همکاری و انتشار آثار خود را برای بررسی ثبت کنید.' ) ); ?></p></header>
		<p><?php echo esc_html( $opt( 'release_intro', 'مبلغ نهایی و هزینه‌های احتمالی پس از بررسی اعلام می‌شود. ثبت درخواست به معنی تضمین انتشار یا درآمد نیست.' ) ); ?></p><p>ثبت درخواست تضمینی برای انتشار یا درآمد نیست.</p>
		<div class="tara-release__prices" aria-label="تعرفه‌های پایه">
			<?php foreach ( $catalog as $key => $service ) : if ( 'international' === $key ) { continue; } ?>
				<div><strong><?php echo esc_html( $service['label'] ); ?></strong><span><?php echo esc_html( number_format_i18n( $service['amount'] ) ); ?> تومان</span></div>
			<?php endforeach; ?>
		</div>
		<?php if ( isset( $catalog['international'] ) ) : ?><aside><h3>انتشار بین‌المللی: فقط بررسی</h3><p>ارزیابی رایگان؛ تک‌آهنگ از <?php echo esc_html( number_format_i18n( (int) $international_single ) ); ?> تومان، آلبوم از <?php echo esc_html( number_format_i18n( (int) $international_album ) ); ?> تومان، ویدیو <?php echo esc_html( $opt( 'release_int_video_text', 'استعلامی' ) ); ?> و چندهنرمند یا لیبل <?php echo esc_html( $opt( 'release_int_label_text', 'توافقی' ) ); ?>. این ارقام قطعی نیستند.</p><p><?php echo esc_html( $opt( 'release_international_platforms', 'Spotify، Apple Music، YouTube Music، Deezer، Amazon Music، Tidal و سایر فروشگاه‌ها' ) ); ?> صرفاً نامزد بررسی هستند. تأیید حقوق، محدودیت‌های قانونی و جغرافیایی، هویت و تسویه الزامی است. اطلاعات جعلی و دور زدن تحریم مجاز نیست.</p><p><?php echo esc_html( $opt( 'release_international_text', '' ) ); ?></p></aside><?php endif; ?>
		<?php for ( $i = 1; $i <= 8; $i++ ) : $condition = $opt( 'release_condition_' . $i . '_text', $condition_defaults[ $i ] ); if ( 'off' === $opt( 'release_condition_' . $i . '_on', 'on' ) || '' === trim( $condition ) ) { continue; } ?><details><summary><?php echo esc_html( $opt( 'release_condition_' . $i . '_title', 'شرط ' . $i ) ); ?></summary><p><?php echo esc_html( $condition ); ?></p></details><?php endfor; ?>
		<form class="tara-release__form" data-release-wizard method="post" enctype="multipart/form-data">
			<input type="hidden" name="submission_token" value="<?php echo esc_attr( str_replace( '-', '', wp_generate_uuid4() ) ); ?>">
			<p data-release-progress aria-live="polite"></p>
			<fieldset data-release-step><legend>۱. اطلاعات هویتی و تماس</legend>
				<label>نام و نام خانوادگی *<input name="full_name" maxlength="120" required></label>
				<label>نام هنری *<input name="artist_name" maxlength="120" required></label>
				<label>شماره تماس *<input name="mobile" type="tel" dir="ltr" maxlength="30" required></label>
				<label>ایمیل *<input name="email" type="email" dir="ltr" maxlength="254" required></label>
				<label>کشور محل سکونت *<input name="country" maxlength="100" required></label>
				<label>اینستاگرام<input name="instagram" type="url" dir="ltr" maxlength="500"></label>
				<label>وب‌سایت<input name="website" type="url" dir="ltr" maxlength="500"></label>
			</fieldset>
			<fieldset data-release-step><legend>۲. انتخاب خدمت</legend>
				<label>نوع خدمت *<select name="service" required><option value="">انتخاب کنید</option><?php foreach ( $catalog as $key => $service ) : ?><option value="<?php echo esc_attr( $key ); ?>" data-price="<?php echo esc_attr( (string) $service['amount'] ); ?>"><?php echo esc_html( $service['label'] ); ?></option><?php endforeach; ?></select></label>
			</fieldset>
			<fieldset data-release-step><legend>۳. مشخصات اثر</legend>
				<label>نام اثر *<input name="title" maxlength="180" required></label>
				<label>اجراکننده *<input name="performer" maxlength="120" required></label>
				<label>آهنگساز<input name="composer" maxlength="120"></label>
				<label>ترانه‌سرا<input name="lyricist" maxlength="120"></label>
				<label>تنظیم‌کننده<input name="arranger" maxlength="120"></label>
				<label>تاریخ پیشنهادی انتشار<input name="release_date" type="date"></label>
				<label>لینک فایل اثر<input name="file_url" type="url" dir="ltr" maxlength="1000"></label>
				<label>لینک کاور<input name="cover_url" type="url" dir="ltr" maxlength="1000"></label>
				<label>توضیحات<textarea name="description" maxlength="4000"></textarea></label>
				<?php if ( 'on' === $opt( 'release_uploads_on', 'off' ) && ! is_wp_error( tara_release_private_dir() ) ) : ?>
				<label>فایل اثر (MP3، WAV یا MP4)<input type="file" name="work_file" accept=".mp3,.wav,.mp4"></label>
				<label>فایل کاور (JPG یا PNG)<input type="file" name="cover_file" accept=".jpg,.jpeg,.png"></label>
				<label>مدرک مالکیت (PDF، JPG یا PNG)<input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png"></label>
				<?php else : ?><p>آپلود خصوصی فعال نیست؛ لینک اثر را وارد کنید.</p><?php endif; ?>
			</fieldset>
			<fieldset data-release-step><legend>۴. حقوق اثر و قوانین</legend>
				<label>توضیحات مالکیت و مدارک<textarea name="legal" maxlength="4000"></textarea></label>
				<label><input type="checkbox" name="rights" value="1" required> صاحب حقوق اثر هستم یا مجوز لازم را دارم. *</label>
				<label><input type="checkbox" name="terms" value="1" required> <a href="<?php echo $rules_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url above ?>" target="_blank" rel="noopener noreferrer">شرایط خدمات</a> را خوانده و می‌پذیرم. *</label>
			</fieldset>
			<fieldset data-release-step><legend>۵. خلاصه و بررسی</legend>
				<p data-release-summary></p><p><?php echo esc_html( $opt( 'release_extra_text', 'هزینهٔ توزیع، مالیات، ارز و کارمزد در صورت وجود جداگانه بررسی و پیش از پرداخت مشخص می‌شوند. مبلغ نهایی و شرایط لغو و بازپرداخت پس از بررسی اعلام و برای تأیید ارائه خواهند شد.' ) ); ?></p>
			</fieldset>
			<div class="tara-release__actions"><button type="button" data-release-prev>قبلی</button><button type="button" data-release-next>بعدی</button><button type="submit" data-release-submit>ثبت درخواست</button></div>
			<p data-release-message role="status" aria-live="polite"></p>
		</form>
		<?php endif; ?>
		<?php if ( $user_id > 0 ) : $statuses = tara_release_statuses(); foreach ( tara_hub_requests( $user_id ) as $row ) : if ( ! isset( $row['type'], $row['id'], $row['status'] ) || 'release' !== $row['type'] ) { continue; } ?>
		<article class="tara-release__request"><h3><?php echo esc_html( $row['title'] ); ?></h3><p>وضعیت: <?php echo esc_html( isset( $statuses[ $row['status'] ] ) ? $statuses[ $row['status'] ] : $row['status'] ); ?></p>
		<?php if ( ! empty( $row['note'] ) ) : ?><p>یادداشت مدیر: <?php echo esc_html( $row['note'] ); ?></p><?php endif; ?>
		<?php if ( ! empty( $row['publication_url'] ) ) : ?><p><a href="<?php echo esc_url( $row['publication_url'] ); ?>" target="_blank" rel="noopener noreferrer">لینک انتشار</a></p><?php endif; ?>
		<?php if ( ! empty( $row['history'] ) && is_array( $row['history'] ) ) : ?><details><summary>تاریخچه وضعیت</summary><?php foreach ( $row['history'] as $history ) : if ( ! isset( $history['status'], $history['time'] ) ) { continue; } ?><p><?php echo esc_html( isset( $statuses[ $history['status'] ] ) ? $statuses[ $history['status'] ] : $history['status'] ); ?> — <?php echo esc_html( date_i18n( 'Y/m/d H:i', (int) $history['time'] ) ); ?></p><?php endforeach; ?></details><?php endif; ?>
		<?php if ( 'awaiting_payment' === $row['status'] && isset( $row['final_toman'], $row['extra_costs'], $row['refund_terms'], $row['quote_revision'] ) ) : ?>
			<p>مبلغ نهایی: <?php echo esc_html( number_format_i18n( (int) $row['final_toman'] ) ); ?> تومان</p><p>هزینه‌های جانبی: <?php echo esc_html( $row['extra_costs'] ); ?></p><p>شرایط لغو و بازپرداخت: <?php echo esc_html( $row['refund_terms'] ); ?></p>
			<?php if ( 'international' !== $row['service'] && 'on' === $opt( 'release_payment_on', 'off' ) ) : ?><form data-release-checkout><input type="hidden" name="request_id" value="<?php echo esc_attr( $row['id'] ); ?>"><input type="hidden" name="quote_hash" value="<?php echo esc_attr( tara_release_quote_hash( $row ) ); ?>"><label><input type="checkbox" name="accept_quote" value="1" required> مبلغ، هزینه‌ها و شرایط لغو و بازپرداخت این سفارش را می‌پذیرم.</label><button type="submit">ادامه پرداخت</button><p role="status" data-release-message></p></form><?php endif; ?>
		<?php endif; ?></article>
		<?php endforeach; endif; ?>
	</section>
	<?php return ob_get_clean();
}
