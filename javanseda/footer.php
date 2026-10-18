<div class="clear"></div>
</main>
<?php $upbs = ot_get_option('upb'); if('off' != $upbs){ ?>
<div class="top_page boxes_shadows"><i class="icofont-thin-up"></i></div>
<?php } ?>
<?php /* v3.2.0 — «باکس شناور پایین-راست» (تلگرام/اینستاگرام) از پایین صفحه حذف شد.
   لینک‌های اجتماعی حالا در باکس «ما را دنبال کنید» بالای لینک‌های دانلودِ همان صفحهٔ پست
   نمایش داده می‌شوند (inc/ab-post-extras.php). گزینهٔ fixbtn در تنظیمات قالب هم به همان باکس
   وصل شده است؛ برای همین این بلوک دیگر لازم نیست. اگر خواستید برگردد، همین ۷ خط را
   از نسخهٔ 3.1.4 جای‌گذاری کنید. */ ?>

<?php
/* v3.4.1 — کنترل فوتر از «تنظیمات قالب ← کنترل رابط». اگر فایل inc/ab-controls.php
   نصب نباشد، مقدارهای زیر دقیقاً رفتار نسخهٔ قبل را بازتولید می‌کنند. */
$ab_footer_cats_num = 8;
if ( function_exists( 'javan_seda_ui_footer_cats' ) ) {
	$ab_footer_cats_num = javan_seda_ui_footer_cats();
}
$ab_footer_links = array(
	array( 'label' => 'همهٔ خواننده‌ها', 'url' => home_url('/singers/'), 'icon' => 'icofont-microphone-alt' ),
	array( 'label' => 'تازه‌ترین آهنگ‌ها', 'url' => home_url('/#new-songs'), 'icon' => 'icofont-music-notes' ),
);
if ( function_exists( 'javan_seda_ui_footer_links' ) ) {
	$ab_footer_links = javan_seda_ui_footer_links();
}
?>
<footer id="footer" class="ab-footer" role="contentinfo">
<div class="tarlanweb_center">
	<div class="ab-footer__grid">
		<!-- ستون ۱: درباره ما -->
		<?php if ( ! function_exists( 'javan_seda_ui_footer_col' ) || javan_seda_ui_footer_col( 'about' ) ) { ?>
		<div class="ab-footer__col ab-footer__col--about">
			<h4 class="ab-footer__title">درباره ما</h4>
			<div class="ab-footer__content">
				<?php
				$ab_about = ot_get_option('footer_about');
				if (empty($ab_about)) {
					$ab_about = ot_get_option('navar_txt');
				}
				if (empty($ab_about)) {
					$ab_about = get_bloginfo('description');
				}
				if (empty($ab_about)) {
					$ab_about = ot_get_option('copyright');
				}
				if (!empty($ab_about)) {
					echo '<p class="ab-footer__text">' . esc_html($ab_about) . '</p>';
				}
				?>
			</div>
		</div>
		<?php } ?>

		<!-- ستون ۲: لینک‌های مفید -->
		<?php if ( ! function_exists( 'javan_seda_ui_footer_col' ) || javan_seda_ui_footer_col( 'links' ) ) { ?>
		<div class="ab-footer__col ab-footer__col--links">
			<h4 class="ab-footer__title">لینک‌های مفید</h4>
			<nav class="ab-footer__nav" aria-label="لینک‌های مفید فوتر">
				<?php /* v3.4.0 — دو پیوند اصلی (همهٔ خواننده‌ها / تازه‌ترین آهنگ‌ها) به‌جای ستون «دسته‌های پرطرفدار» اینجا می‌آیند: فوتر کوتاه‌تر و لینک‌ها در یک ستون.
				   v3.4.1 — همان دو پیوند + لینک‌های دستیِ شما، از تنظیمات قالب (ab_footer_pinned / ab_footer_links). */ ?>
				<?php if (!empty($ab_footer_links)) { ?>
				<ul class="ab-footer__menu ab-footer__menu--pinned">
					<?php foreach ($ab_footer_links as $ab_fl) { ?>
					<li><a href="<?php echo esc_url($ab_fl['url']); ?>" class="ab-footer__cat-featured"><i class="<?php echo esc_attr($ab_fl['icon']); ?>" aria-hidden="true"></i> <?php echo esc_html($ab_fl['label']); ?></a></li>
					<?php } ?>
				</ul>
				<?php } ?>
				<?php
				if (has_nav_menu('ft_menu')) {
					wp_nav_menu(array(
						'theme_location' => 'ft_menu',
						'menu_id'        => 'tarlanweb_ir',
						'container'      => false,
						'menu_class'     => 'ab-footer__menu',
						'depth'          => 1,
						'fallback_cb'    => false,
					));
				} else {
					echo '<ul id="tarlanweb_ir" class="ab-footer__menu">';
					wp_list_pages(array('title_li' => '', 'depth' => 1, 'number' => 6));
					echo '</ul>';
				}
				?>
			</nav>
		</div>
		<?php } ?>

		<!-- ستون ۳: دسته‌های پرطرفدار -->
		<?php if ( ! function_exists( 'javan_seda_ui_footer_col' ) || javan_seda_ui_footer_col( 'cats' ) ) { ?>
		<div class="ab-footer__col ab-footer__col--cats">
			<h4 class="ab-footer__title">دسته‌های پرطرفدار</h4>
			<ul class="ab-footer__cats">
				<?php
				$ab_categories = get_categories(array(
					'orderby'    => 'count',
					'order'      => 'DESC',
					'hide_empty' => true,
					'number'     => $ab_footer_cats_num,
				));
				if (!empty($ab_categories) && !is_wp_error($ab_categories)) {
					foreach ($ab_categories as $ab_cat) {
						echo '<li><a href="' . esc_url(get_category_link($ab_cat->term_id)) . '">' . esc_html($ab_cat->name) . ' <span class="ab-footer__count">(' . esc_html(number_format_i18n($ab_cat->count)) . ')</span></a></li>';
					}
				}
				?>
			</ul>
		</div>
		<?php } ?>

		<!-- ستون ۴: ما را دنبال کنید -->
		<?php if ( ! function_exists( 'javan_seda_ui_footer_col' ) || javan_seda_ui_footer_col( 'social' ) ) { ?>
		<div class="ab-footer__col ab-footer__col--social">
			<h4 class="ab-footer__title">ما را دنبال کنید</h4>
			<div class="socials_box ab-footer__socials">
				<?php if(ot_get_option('facebook') !=""){ ?>
				<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url(ot_get_option('facebook')); ?>" aria-label="facebook" class="icofont-facebook"></a><?php } if(ot_get_option('instagram') !=""){ ?>
				<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url(ot_get_option('instagram')); ?>" aria-label="instagram" class="icofont-instagram"></a><?php } if(ot_get_option('telegram') !=""){ ?>
				<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url(ot_get_option('telegram')); ?>" aria-label="telegram" class="icofont-paper-plane"></a><?php } if(ot_get_option('twitter') !=""){ ?>
				<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url(ot_get_option('twitter')); ?>" aria-label="twitter" class="icofont-twitter"></a><?php } if(ot_get_option('youtube') !=""){ ?>
				<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url(ot_get_option('youtube')); ?>" aria-label="youtube" class="icofont-youtube-play"></a><?php } if(ot_get_option('aparat') !=""){ ?>
				<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url(ot_get_option('aparat')); ?>" aria-label="aparat" class="icofont-movie"></a><?php } ?>
				<?php /* v3.2.0 — دو پیام‌رسان تازه (کلیدهایشان در تنظیمات قالب → «شبکه‌های اجتماعی» اضافه شده) */ if(ot_get_option('bale') !=""){ ?>
				<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url(ot_get_option('bale')); ?>" aria-label="bale" class="icofont-chat"></a><?php } if(ot_get_option('rubika') !=""){ ?>
				<a target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url(ot_get_option('rubika')); ?>" aria-label="rubika" class="icofont-comment"></a><?php } ?>
				<div class="clear"></div>
			</div>

			<?php if ( ! function_exists( 'javan_seda_ui_footer_search' ) || javan_seda_ui_footer_search() ) { ?>
			<form class="ab-footer__search" method="get" action="<?php echo esc_url(home_url('/')); ?>" role="search">
				<label for="ab-footer-search-input" class="screen-reader-text">جستجو در سایت</label>
				<input id="ab-footer-search-input" class="ab-footer__search-input" name="s" placeholder="جستجوی ترانه، خواننده..." type="text" required>
				<button type="submit" class="ab-footer__search-btn" aria-label="جستجو"><i class="icofont-search"></i></button>
			</form>
			<?php } ?>
		</div>
		<?php } ?>
	</div>

	<!-- ردیف پایین: کپی‌رایت + لینک‌های اختیاری سیاست‌ها + داده ساختاریافته Organization -->
	<div class="ab-footer__bottom">
		<div class="ab-footer__copyright">
			<p><?php echo esc_html(ot_get_option('copyright')); ?></p>
		</div>
		<?php
		$ab_rules_url   = ot_get_option('rules_url');
		$ab_ads_url     = ot_get_option('ads_url');
		$ab_contact_url = ot_get_option('contact_url');
		if (!empty($ab_rules_url) || !empty($ab_ads_url) || !empty($ab_contact_url)) {
			echo '<div class="ab-footer__policy-links">';
			if (!empty($ab_rules_url)) {
				echo '<a href="' . esc_url($ab_rules_url) . '">قوانین و مقررات</a>';
			}
			if (!empty($ab_ads_url)) {
				echo '<a href="' . esc_url($ab_ads_url) . '">تبلیغات</a>';
			}
			if (!empty($ab_contact_url)) {
				echo '<a href="' . esc_url($ab_contact_url) . '">تماس با ما</a>';
			}
			echo '</div>';
		}
		?>
		<div class="clear"></div>
	</div>

	<?php
	$ab_org_name = get_bloginfo('name');
	$ab_org_url  = home_url('/');
	$ab_org_logo = ot_get_option('logo');
	$ab_same_as  = array();
	foreach (array('telegram', 'instagram', 'facebook', 'twitter', 'youtube', 'aparat') as $soc_key) {
		$s_val = ot_get_option($soc_key);
		if (!empty($s_val)) {
			$ab_same_as[] = esc_url($s_val);
		}
	}
	$ab_schema = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Organization',
		'name'     => $ab_org_name,
		'url'      => $ab_org_url,
	);
	if (!empty($ab_org_logo)) {
		$ab_schema['logo'] = esc_url($ab_org_logo);
	}
	if (!empty($ab_same_as)) {
		$ab_schema['sameAs'] = $ab_same_as;
	}
	?>
	<script type="application/ld+json">
	<?php echo wp_json_encode($ab_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
	</script>
	<div class="clear"></div>
</div>
</footer>
<?php wp_footer(''); ?>
</body>
</html>
