<?php
/** UI for the in-progress release workflow. Not hooked into the account yet. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tara_release_render_wizard() {
	$catalog = tara_release_catalog();
	$opt = function ( $key, $default ) { $value = function_exists( 'ot_get_option' ) ? ot_get_option( $key, $default ) : $default; return is_string( $value ) ? $value : $default; };
	$international_single = $opt( 'release_int_single_price', '8900000' );
	$international_album = $opt( 'release_int_album_price', '19900000' );
	if ( ! preg_match( '/^[0-9]{1,10}$/D', $international_single ) || (float) $international_single > 2000000000 ) { $international_single = '8900000'; }
	if ( ! preg_match( '/^[0-9]{1,10}$/D', $international_album ) || (float) $international_album > 2000000000 ) { $international_album = '19900000'; }
	$rules_url = esc_url( $opt( 'release_rules_url', 'https://ahangban.ir/works/' ), array( 'http', 'https' ) );
	ob_start(); ?>
	<section class="tara-release" dir="rtl">
		<header><h2><?php echo esc_html( $opt( 'release_title', 'آثارت را به مخاطبان بیشتری معرفی کن' ) ); ?></h2><p><?php echo esc_html( $opt( 'release_subtitle', 'درخواست همکاری و انتشار آثار خود را برای بررسی ثبت کنید.' ) ); ?></p></header>
		<p>مبلغ نهایی و هزینه‌های احتمالی پس از بررسی اعلام می‌شود. ثبت درخواست به معنی تضمین انتشار یا درآمد نیست.</p>
		<div class="tara-release__prices" aria-label="تعرفه‌های پایه">
			<?php foreach ( $catalog as $key => $service ) : if ( 'international' === $key ) { continue; } ?>
				<div><strong><?php echo esc_html( $service['label'] ); ?></strong><span><?php echo esc_html( number_format_i18n( $service['amount'] ) ); ?> تومان</span></div>
			<?php endforeach; ?>
		</div>
		<?php if ( isset( $catalog['international'] ) ) : ?><aside><h3>انتشار بین‌المللی: فقط بررسی</h3><p>ارزیابی رایگان؛ تک‌آهنگ از <?php echo esc_html( number_format_i18n( (int) $international_single ) ); ?> تومان، آلبوم از <?php echo esc_html( number_format_i18n( (int) $international_album ) ); ?> تومان، ویدیو استعلامی و چندهنرمند یا لیبل توافقی. این ارقام قطعی نیستند.</p><p><?php echo esc_html( $opt( 'release_international_platforms', 'Spotify، Apple Music، YouTube Music، Deezer، Amazon Music، Tidal و سایر فروشگاه‌ها' ) ); ?> صرفاً نامزد بررسی هستند. تأیید حقوق، محدودیت‌های قانونی و جغرافیایی، هویت و تسویه الزامی است. اطلاعات جعلی و دور زدن تحریم مجاز نیست.</p><p><?php echo esc_html( $opt( 'release_international_text', '' ) ); ?></p></aside><?php endif; ?>
		<?php for ( $i = 1; $i <= 8; $i++ ) : $condition = $opt( 'release_condition_' . $i . '_text', '' ); if ( 'off' === $opt( 'release_condition_' . $i . '_on', 'on' ) || '' === trim( $condition ) ) { continue; } ?><details><summary>شرط <?php echo esc_html( (string) $i ); ?></summary><p><?php echo esc_html( $condition ); ?></p></details><?php endfor; ?>
		<form class="tara-release__form" data-release-wizard method="post">
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
				<label>لینک فایل اثر *<input name="file_url" type="url" dir="ltr" maxlength="1000" required></label>
				<label>لینک کاور<input name="cover_url" type="url" dir="ltr" maxlength="1000"></label>
				<label>توضیحات<textarea name="description" maxlength="4000"></textarea></label>
				<p>آپلود خصوصی هنوز فعال نیست؛ فایل را از طریق یک لینک قابل کنترل در اختیار تیم بررسی بگذارید.</p>
			</fieldset>
			<fieldset data-release-step><legend>۴. حقوق اثر و قوانین</legend>
				<label>توضیحات مالکیت و مدارک<textarea name="legal" maxlength="4000"></textarea></label>
				<label><input type="checkbox" name="rights" value="1" required> صاحب حقوق اثر هستم یا مجوز لازم را دارم. *</label>
				<label><input type="checkbox" name="terms" value="1" required> <a href="<?php echo $rules_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url above ?>" target="_blank" rel="noopener noreferrer">شرایط خدمات</a> را خوانده و می‌پذیرم. *</label>
			</fieldset>
			<fieldset data-release-step><legend>۵. خلاصه و بررسی</legend>
				<p data-release-summary></p><p>هزینهٔ توزیع، مالیات، ارز و کارمزد در صورت وجود جداگانه بررسی و پیش از پرداخت مشخص می‌شوند. مبلغ نهایی و شرایط لغو و بازپرداخت پس از بررسی اعلام و برای تأیید ارائه خواهند شد.</p>
			</fieldset>
			<div class="tara-release__actions"><button type="button" data-release-prev>قبلی</button><button type="button" data-release-next>بعدی</button><button type="submit" data-release-submit disabled>ثبت درخواست</button></div>
			<p>ثبت درخواست هنوز فعال نشده است.</p>
		</form>
	</section>
	<?php return ob_get_clean();
}
