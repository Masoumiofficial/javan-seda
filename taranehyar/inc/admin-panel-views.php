<?php
/**
 * پنل مدیریت «ترانه یار» — نماها
 *
 * هر تابع یک صفحهٔ کامل داخل `<div class="wrap ty-panel" dir="rtl">` می‌سازد.
 * منطق و آمار در `inc/admin-panel.php` است؛ اینجا فقط چیدمان و متن.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * عدد فارسی برای نمایش در پیشخوان.
 *
 * @param int|float $number عدد.
 * @return string
 */
function taranehyar_panel_number( $number ) {
	if ( function_exists( 'tara_number_fa' ) ) {
		return tara_number_fa( $number );
	}

	return number_format_i18n( $number );
}

/**
 * لینک مستقیم به یک تب از صفحهٔ تنظیمات OptionTree.
 *
 * @param string $section شناسهٔ بخش.
 * @return string
 */
function taranehyar_panel_settings_url( $section = '' ) {
	$url = 'admin.php?page=ot-theme-options';

	if ( '' !== $section ) {
		$url .= '#section_' . $section;
	}

	return esc_url( admin_url( $url ) );
}

/* -------------------------------------------------------------------------
 * پیشخوان
 * ---------------------------------------------------------------------- */
function taranehyar_render_dashboard() {
	if ( ! current_user_can( taranehyar_panel_capability() ) ) {
		return;
	}

	$stats    = taranehyar_panel_stats();
	$sections = taranehyar_panel_sections();
	$health   = taranehyar_panel_health();

	$ok_count = 0;
	foreach ( $health as $check ) {
		if ( ! empty( $check['ok'] ) ) {
			$ok_count++;
		}
	}
	?>
	<div class="wrap ty-panel" dir="rtl">

		<div class="ty-hero">
			<div class="ty-hero__main">
				<span class="ty-badge">نسخهٔ <?php echo esc_html( TARANEHYAR_VERSION ); ?></span>
				<h1>ترانه یار</h1>
				<p>پنل اختصاصی قالب. تنظیم‌ها همان‌جایی هستند که بودند — فقط از «نمایش» به منوی اصلی آمده‌اند و داده‌های سایت دست نخورده‌اند.</p>
				<div class="ty-hero__actions">
					<a class="ty-btn ty-btn--primary" href="<?php echo esc_url( taranehyar_panel_settings_url() ); ?>">
						<span class="dashicons dashicons-admin-generic"></span>
						بازکردن تنظیمات قالب
					</a>
					<a class="ty-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">
						<span class="dashicons dashicons-visibility"></span>
						دیدن سایت
					</a>
					<a class="ty-btn" href="<?php echo esc_url( taranehyar_panel_url( 'taranehyar-tools' ) ); ?>">
						<span class="dashicons dashicons-performance"></span>
						گزارش سیستم
					</a>
				</div>
			</div>
			<div class="ty-hero__art" aria-hidden="true">
				<span class="dashicons dashicons-format-audio"></span>
			</div>
		</div>

		<div class="ty-stats">
			<div class="ty-stat">
				<span class="dashicons dashicons-format-audio"></span>
				<strong><?php echo esc_html( taranehyar_panel_number( $stats['posts'] ) ); ?></strong>
				<span>آهنگ منتشرشده</span>
			</div>
			<div class="ty-stat">
				<span class="dashicons dashicons-chart-bar"></span>
				<strong><?php echo esc_html( taranehyar_panel_number( $stats['views'] ) ); ?></strong>
				<span>بازدید کل</span>
			</div>
			<div class="ty-stat">
				<span class="dashicons dashicons-admin-comments"></span>
				<strong><?php echo esc_html( taranehyar_panel_number( $stats['comments'] ) ); ?></strong>
				<span>دیدگاه تأییدشده</span>
			</div>
			<div class="ty-stat">
				<span class="dashicons dashicons-groups"></span>
				<strong><?php echo esc_html( taranehyar_panel_number( $stats['users'] ) ); ?></strong>
				<span>کاربر سایت</span>
			</div>
		</div>

		<h2 class="ty-heading">
			تنظیمات، دسته‌بندی‌شده
			<span class="ty-heading__hint">هر کارت مستقیم همان تب را باز می‌کند</span>
		</h2>

		<div class="ty-grid">
			<?php foreach ( $sections as $section ) : ?>
				<a class="ty-card ty-card--link" href="<?php echo taranehyar_panel_settings_url( $section['id'] ); ?>">
					<span class="ty-card__icon dashicons <?php echo esc_attr( $section['icon'] ); ?>"></span>
					<span class="ty-card__body">
						<span class="ty-card__title"><?php echo esc_html( $section['title'] ); ?></span>
						<span class="ty-card__desc"><?php echo esc_html( $section['desc'] ); ?></span>
					</span>
					<span class="ty-card__meta"><?php echo esc_html( taranehyar_panel_number( $section['count'] ) ); ?> گزینه</span>
				</a>
			<?php endforeach; ?>
		</div>

		<?php $tara_taxonomies = function_exists( 'taranehyar_panel_taxonomies' ) ? taranehyar_panel_taxonomies() : array(); ?>
		<?php if ( ! empty( $tara_taxonomies ) ) : ?>
			<h2 class="ty-heading">
				تاکسونومی‌های موسیقی
				<span class="ty-heading__hint">ژانر، حال‌وهوا، ترانه‌سرا، آهنگساز، تنظیم</span>
			</h2>
			<div class="ty-taxgrid">
				<?php foreach ( $tara_taxonomies as $tara_tax ) : ?>
					<a class="ty-tax" href="<?php echo esc_url( $tara_tax['url'] ); ?>">
						<span class="dashicons <?php echo esc_attr( $tara_tax['icon'] ); ?>"></span>
						<span class="ty-tax__body">
							<strong><?php echo esc_html( $tara_tax['title'] ); ?></strong>
							<em><?php echo esc_html( taranehyar_panel_number( $tara_tax['count'] ) ); ?> مورد ثبت‌شده</em>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="ty-columns">
			<section class="ty-card ty-card--wide">
				<h3>
					بررسی سلامت
					<span class="ty-pill <?php echo ( $ok_count === count( $health ) ) ? 'ty-pill--ok' : 'ty-pill--warn'; ?>">
						<?php echo esc_html( taranehyar_panel_number( $ok_count ) ); ?> از <?php echo esc_html( taranehyar_panel_number( count( $health ) ) ); ?>
					</span>
				</h3>
				<ul class="ty-checklist">
					<?php foreach ( $health as $check ) : ?>
						<li class="<?php echo ! empty( $check['ok'] ) ? 'is-ok' : 'is-warn'; ?>">
							<span class="dashicons <?php echo ! empty( $check['ok'] ) ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
							<span>
								<strong><?php echo esc_html( $check['label'] ); ?></strong>
								<em><?php echo esc_html( $check['note'] ); ?></em>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

			<section class="ty-card ty-card--wide">
				<h3>میان‌بُرها</h3>
				<ul class="ty-links">
					<li><a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><span class="dashicons dashicons-menu"></span> منوها</a></li>
					<li><a href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>"><span class="dashicons dashicons-screenoptions"></span> ابزارک‌ها</a></li>
					<li><a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>"><span class="dashicons dashicons-admin-customizer"></span> سربرگ سایت</a></li>
					<li><a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>"><span class="dashicons dashicons-format-image"></span> کتابخانهٔ رسانه</a></li>
					<li><a href="<?php echo esc_url( admin_url( 'edit-comments.php' ) ); ?>"><span class="dashicons dashicons-admin-comments"></span> دیدگاه‌ها</a></li>
					<li><a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>"><span class="dashicons dashicons-admin-plugins"></span> افزونه‌ها</a></li>
				</ul>
				<p class="ty-note">
					تنظیمات در کلید <code><?php echo esc_html( taranehyar_options_id() ); ?></code> ذخیره می‌شود — همان کلیدی که نسخه‌های ۳.۵.x استفاده می‌کردند؛ پس هیچ تنظیمی گم نمی‌شود.
				</p>
			</section>
		</div>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * ابزارها و گزارش سیستم
 * ---------------------------------------------------------------------- */
function taranehyar_render_tools() {
	if ( ! current_user_can( taranehyar_panel_capability() ) ) {
		return;
	}

	$report = taranehyar_system_report();
	?>
	<div class="wrap ty-panel" dir="rtl">
		<div class="ty-hero ty-hero--slim">
			<div class="ty-hero__main">
				<h1>ابزارها</h1>
				<p>گزارش وضعیت سایت برای عیب‌یابی و پشتیبانی. هیچ داده‌ای تغییر نمی‌کند.</p>
			</div>
		</div>

		<?php if ( isset( $_GET['cleared'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>حافظهٔ آمار پاک شد؛ دفعهٔ بعد اعداد از نو محاسبه می‌شوند.</p>
			</div>
		<?php endif; ?>

		<div class="ty-columns">
			<section class="ty-card ty-card--wide">
				<h3>گزارش سیستم</h3>
				<p class="ty-note">این متن را می‌توانید کپی کنید و در تیکت پشتیبانی بگذارید.</p>
				<textarea id="ty-report" class="ty-report" readonly rows="16"><?php echo esc_textarea( $report ); ?></textarea>
				<p>
					<button type="button" class="ty-btn ty-btn--primary" data-ty-copy="#ty-report">
						<span class="dashicons dashicons-clipboard"></span>
						کپی گزارش
					</button>
				</p>
			</section>

			<section class="ty-card ty-card--wide">
				<h3>نگهداری</h3>
				<p class="ty-note">آمار بالای پیشخوان ۱۵ دقیقه در حافظه می‌ماند تا هر بار بازکردن پنل، دیتابیس را نگردد. اگر آمار تازه‌تری خواستید، اینجا پاکش کنید.</p>
				<form method="post" action="">
					<?php wp_nonce_field( 'taranehyar_clear_stats' ); ?>
					<button type="submit" name="taranehyar_clear_stats" value="1" class="ty-btn">
						<span class="dashicons dashicons-update"></span>
						پاک‌کردن حافظهٔ آمار
					</button>
				</form>
				<p class="ty-note">
					تنظیمات قالب در این صفحه نیست؛
					<a href="<?php echo esc_url( taranehyar_panel_settings_url() ); ?>">به تنظیمات بروید</a>.
					متن «دربارهٔ سایت» هم در تب
					<a href="<?php echo esc_url( taranehyar_panel_settings_url( 'about' ) ); ?>">دربارهٔ سایت</a> است.
				</p>
			</section>
		</div>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * درباره
 * ---------------------------------------------------------------------- */
function taranehyar_render_about() {
	if ( ! current_user_can( taranehyar_panel_capability() ) ) {
		return;
	}

	$theme = wp_get_theme();
	?>
	<div class="wrap ty-panel" dir="rtl">
		<div class="ty-hero">
			<div class="ty-hero__main">
				<span class="ty-badge">نسخهٔ <?php echo esc_html( TARANEHYAR_VERSION ); ?></span>
				<h1>ترانه یار</h1>
				<p>قالب اختصاصی سایت موسیقی؛ راست‌چین، سبک و بدون وابستگی به هیچ سرویس لایسنس.</p>
			</div>
			<div class="ty-hero__art" aria-hidden="true">
				<span class="dashicons dashicons-format-audio"></span>
			</div>
		</div>

		<div class="ty-columns">
			<section class="ty-card ty-card--wide">
				<h3>در نسخهٔ <?php echo esc_html( TARANEHYAR_VERSION ); ?> چه شد؟</h3>
				<ul class="ty-bullets">
					<li>نام قالب به «ترانه یار» تغییر کرد و پوشه به <code>taranehyar</code> آمد.</li>
					<li>فایل لایسنس رمزگذاری‌شده و هستهٔ رمزگذاری‌شده حذف شدند؛ هیچ ارتباطی با سرور بیرونی نیست.</li>
					<li>هسته از نو و خوانا نوشته شد: راه‌اندازی، دارایی‌ها، شمارش بازدید، نمایش بازدید و صفحه‌بندی.</li>
					<li>پنل تنظیمات به منوی اصلی پیشخوان آمد؛ ذخیره‌سازی همان <code>option_tree</code> ماند.</li>
					<li>صفحهٔ تنظیمات ظاهر تازه گرفت: تب‌های قرصی، کارت هر بخش و جست‌وجوی فیلد.</li>
					<li>پلیر «رادیو» تازه: یک کارت بزرگ با فهرست پخش و نوار چسبانِ پایین صفحه.</li>
					<li>صفحهٔ هنرمند مثل یک ایستگاه رادیو از نو ساخته شد (سربرگ، رادیو، آلبوم/ویدیو، مشابه‌ها).</li>
					<li>اسلایدر صفحهٔ اصلی تک‌اسلاید و تمام‌عرض شد.</li>
					<li>پنج تاکسونومی تازه: ژانر، حال‌وهوا، ترانه‌سرا، آهنگساز، تنظیم.</li>
					<li>متن «دربارهٔ سایت» از تنظیمات قابل ویرایش شد (پانوشت + صفحهٔ هنرمند).</li>
				</ul>
			</section>

			<section class="ty-card ty-card--wide">
				<h3>نقشهٔ فایل‌ها</h3>
				<ul class="ty-bullets">
					<li><code>functions.php</code> — هستهٔ خوانا: ثابت‌ها، راه‌اندازی، دارایی‌ها، بازدید، صفحه‌بندی.</li>
					<li><code>myfunctions.php</code> و <code>inc/</code> — لایهٔ اصلی: ابزارک، سئو، دیدگاه‌ها و کنترل رابط.</li>
					<li><code>inc/theme-options.php</code> — فهرست فیلدهای OptionTree.</li>
					<li><code>inc/admin-panel.php</code> — همین پنل (منو، آمار، سلامت).</li>
					<li><code>inc/option-tree/</code> — موتور تنظیمات (GPLv3).</li>
				</ul>
				<p class="ty-note">
					قدردانی: OptionTree، IcoFont و Owl Carousel در این قالب استفاده شده‌اند و هر یک مجوز خودشان را دارند.
				</p>
			</section>
		</div>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * ابزارک پیشخوان وردپرس
 * ---------------------------------------------------------------------- */
function taranehyar_render_dashboard_widget() {
	if ( ! current_user_can( taranehyar_panel_capability() ) ) {
		return;
	}

	$stats = taranehyar_panel_stats();
	?>
	<div class="ty-widget" dir="rtl">
		<ul class="ty-widget__stats">
			<li><strong><?php echo esc_html( taranehyar_panel_number( $stats['posts'] ) ); ?></strong> آهنگ</li>
			<li><strong><?php echo esc_html( taranehyar_panel_number( $stats['views'] ) ); ?></strong> بازدید</li>
			<li><strong><?php echo esc_html( taranehyar_panel_number( $stats['comments'] ) ); ?></strong> دیدگاه</li>
		</ul>
		<p class="ty-widget__links">
			<a href="<?php echo esc_url( taranehyar_panel_settings_url() ); ?>">تنظیمات قالب</a>
			<span aria-hidden="true">·</span>
			<a href="<?php echo esc_url( taranehyar_panel_url() ); ?>">پنل ترانه یار</a>
		</p>
	</div>
	<?php
}
