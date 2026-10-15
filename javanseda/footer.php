<div class="clear"></div>
</main>
<?php get_template_part('inc/mobiles_header'); ?>

<?php $upbs = ot_get_option('upb'); if('off' != $upbs){ ?>
<div class="top_page boxes_shadows"><i class="icofont-thin-up"></i></div>
<?php } ?>
<?php if(!wp_is_mobile()){ $fixbtns = ot_get_option('fixbtn'); if('off' != $fixbtns){ ?>
<div class="fix_bottom_right_box">
<?php if(ot_get_option('telegram') !=""){ ?>
<a class="telegrams_ch" target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url(ot_get_option('telegram')); ?>"><?php echo esc_html(ot_get_option('teltxt')); ?></a><?php } if(ot_get_option('instagram') !=""){ ?>
<a class="instagrms_ch" target="_blank" rel="noreferrer noopener nofollow" href="<?php echo esc_url(ot_get_option('instagram')); ?>"><?php echo esc_html(ot_get_option('instxt')); ?></a><?php } ?>
</div>
<?php } } ?>

<footer id="footer" class="ab-footer" role="contentinfo">
<div class="tarlanweb_center">
	<div class="ab-footer__grid">
		<!-- ستون ۱: درباره ما -->
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

		<!-- ستون ۲: لینک‌های مفید -->
		<div class="ab-footer__col ab-footer__col--links">
			<h4 class="ab-footer__title">لینک‌های مفید</h4>
			<nav class="ab-footer__nav" aria-label="لینک‌های مفید فوتر">
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

		<!-- ستون ۳: دسته‌های پرطرفدار -->
		<div class="ab-footer__col ab-footer__col--cats">
			<h4 class="ab-footer__title">دسته‌های پرطرفدار</h4>
			<ul class="ab-footer__cats">
				<li><a href="<?php echo esc_url(home_url('/singers/')); ?>" class="ab-footer__cat-featured"><i class="icofont-microphone-alt"></i> همهٔ خواننده‌ها</a></li>
				<li><a href="<?php echo esc_url(home_url('/#new-songs')); ?>" class="ab-footer__cat-featured"><i class="icofont-music-notes"></i> تازه‌ترین آهنگ‌ها</a></li>
				<?php
				$ab_categories = get_categories(array(
					'orderby'    => 'count',
					'order'      => 'DESC',
					'hide_empty' => true,
					'number'     => 8,
				));
				if (!empty($ab_categories) && !is_wp_error($ab_categories)) {
					foreach ($ab_categories as $ab_cat) {
						echo '<li><a href="' . esc_url(get_category_link($ab_cat->term_id)) . '">' . esc_html($ab_cat->name) . ' <span class="ab-footer__count">(' . esc_html(number_format_i18n($ab_cat->count)) . ')</span></a></li>';
					}
				}
				?>
			</ul>
		</div>

		<!-- ستون ۴: ما را دنبال کنید -->
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
				<div class="clear"></div>
			</div>

			<form class="ab-footer__search" method="get" action="<?php echo esc_url(home_url('/')); ?>" role="search">
				<label for="ab-footer-search-input" class="screen-reader-text">جستجو در سایت</label>
				<input id="ab-footer-search-input" class="ab-footer__search-input" name="s" placeholder="جستجوی ترانه، خواننده..." type="text" required>
				<button type="submit" class="ab-footer__search-btn" aria-label="جستجو"><i class="icofont-search"></i></button>
			</form>
		</div>
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
