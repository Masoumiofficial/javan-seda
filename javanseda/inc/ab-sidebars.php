<?php
/**
 * جوان‌صدا (آهنگ‌بان) — لایهٔ ایمنی «سایدبار / ابزارک» — نسخهٔ 3.1.0 (بازمهندسی‌شده)
 * امضای نسخه برای تشخیص خودکار کاوشگر: JAVANSEDA_SIDE_LAYER = 3.1.0 (کمینه؛ بدون هوکِ رندر) — بستهٔ 3.1.2
 * ==================================================================================
 * چرا این بازنویسی؟  نسخهٔ ۳.۰.۵ روی هاستِ شما باعث خرابیِ صفحه شد و شما کلاً
 * محتوای فایل را حذف کردید. آن نسخه سه کار «پرخطر» می‌کرد که در خطِ رندرِ صفحه
 * دخالت می‌کردند و همین، همان «باکس خالی در موبایل» را می‌ساخت:
 *
 *   ✗ add_filter( 'the_content', ... )        → بازنویسی/دوباره‌سازیِ متن نوشته
 *   ✗ add_filter( 'template_include', ... )×۲ → مسیرِ قالب را در همهٔ درخواست‌ها می‌گرفت
 *   ✗ require_once 'inc/widgets.php'          → بارگذاری دستی فایل ابزارک‌ها در widgets_init
 *
 * این نسخه «هیچ‌کدام» از آن‌ها را نمی‌کند. فهرست کامل کارها:
 *   ۱) اگر ناحیه‌های left_side / left_side2 / left_side1 ثبت نشده باشند، ثبتشان می‌کند
 *      (فقط در widgets_init، فقط در صورت نبود، با نام‌های دست‌نخورده).
 *   ۲) تابع رندر امن javan_seda_sidebar() که single.php / page.php صدا می‌زنند؛
 *      اگراگر داخلش خطایی رخ دهد، صفحه نمی‌میرد (try/catch) و فقط یک کامنت HTML می‌ماند.
 *   ۳) گزارش تشخیصیِ فقط‌خواندنی به‌صورت «آرایهٔ خطوط»؛ خروجی را هیچ‌گاه با
 *      template_include چاپ نمی‌کند — فقط فایل ab-mobile-probe.php آن را صدا می‌زند.
 *   ۴) (اختیاری و خاموش‌به‌مروز) بازگردانی ابزارک‌های افتاده در «ابزارک‌های غیرفعال»
 *      با JAVANSEDA_SIDEBAR_RECOVERY = true. تا وقتی این define نشود، هیچ فیلتری
 *      روی دادهٔ ابزارک‌ها اعمال نمی‌شود.
 *
 * هرگز انجام نمی‌دهد:  the_content · the_title · template_redirect · pre_get_posts ·
 *   template_include · wp_head/wp_footer · نوشتن در دیتابیس · include کردن فایل‌های قالب.
 *
 * کلید خاموشیِ کامل (اگر یک روز دوباره مشکوک شدید، در wp-config.php بنویسید):
 *   define( 'JAVANSEDA_SIDE', false );
 *
 * قوانین همیشگی: بدون دست‌زدن به تاکسونومی/هوک سراسری، بدون گرادینت،
 * پالت سازمانی #FFFFFF / #185ADB / #111111.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* هیچ کاری نکن — کلید خاموشی */
if ( defined( 'JAVANSEDA_SIDE' ) && false === JAVANSEDA_SIDE ) {
	return;
}

if ( ! defined( 'JAVANSEDA_SIDEBAR_RECOVERY' ) ) {
	define( 'JAVANSEDA_SIDEBAR_RECOVERY', false ); // خاموش‌به‌مروز (تصمیم آگاهانهٔ v3.1.0)
}
if ( ! defined( 'JAVANSEDA_SIDEBAR_MARKERS' ) ) {
	define( 'JAVANSEDA_SIDEBAR_MARKERS', true );     // کامنت تشخیصی وقتی ناحیه خالی است
}

/* ---------------------------------------------------------------------------
 * ۰) ابزار کمکی: اجرای محافظت‌شده (خطای PHP داخل کار ما، صفحه را نکشد)
 * ------------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_safe_run' ) ) {
	function javan_seda_safe_run( $fn ) {
		try {
			$fn();
		} catch ( Exception $e ) {
			// PHP 5.6: بی‌تفاوت از خطای ما رد می‌شویم
		} catch ( Throwable $e ) {
			// PHP 7+: همان‌جا هم بی‌تفاوت رد می‌شویم
		}
	}
}

/* ---------------------------------------------------------------------------
 * ۱) تعریف ناحیه‌ها — شناسه‌ها هرگز تغییر نمی‌کنند
 * ------------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_widget_id_bases' ) ) {
	function javan_seda_widget_id_bases() {
		return array( 'tarlanweb_ir_newp', 'tarlanweb_ir_popular', 'tarlanweb_ir_rand' );
	}
}

if ( ! function_exists( 'javan_seda_sidebar_defs' ) ) {
	function javan_seda_sidebar_defs() {
		$shared = array(
			'before_widget' => '<aside id="%1$s" class="related_posts %2$s">',
			'after_widget'  => '<div class="clear"></div></div></aside>',
			'before_title'  => '<h3 class="tarlanweb_side_titles">',
			'after_title'   => '</h3><div class="liner clear"></div><div class="tarlanweb_side_box">',
		);

		return array(
			'left_side'  => array_merge( $shared, array(
				'id'          => 'left_side',
				'class'       => 'leftside',
				'name'        => 'سایدبار نوشته‌ها — بالا (single.php)',
				'description' => 'بالای ستون چپ صفحهٔ نوشته/آهنگ. شناسه: left_side',
			) ),
			'left_side2' => array_merge( $shared, array(
				'id'          => 'left_side2',
				'class'       => 'leftside2',
				'name'        => 'سایدبار نوشته‌ها — پایین (single.php)',
				'description' => 'پایین ستون چپ صفحهٔ نوشته/آهنگ. شناسه: left_side2',
			) ),
			'left_side1' => array_merge( $shared, array(
				'id'          => 'left_side1',
				'class'       => 'leftside1',
				'name'        => 'سایدبار برگه‌ها (page.php)',
				'description' => 'ستون چپ «برگه‌ها» — روی صفحهٔ نوشته نمایش داده نمی‌شود. شناسه: left_side1',
			) ),
		);
	}
}

if ( ! function_exists( 'javan_seda_sidebar_registered' ) ) {
	function javan_seda_sidebar_registered( $id ) {
		global $wp_registered_sidebars;
		return is_array( $wp_registered_sidebars ) && isset( $wp_registered_sidebars[ $id ] );
	}
}

/** ثبت ناحیه‌های جاافتاده — idempotent و بدون بازنویسی ناحیه‌های موجود. */
if ( ! function_exists( 'javan_seda_register_missing_sidebars' ) ) {
	function javan_seda_register_missing_sidebars() {
		foreach ( javan_seda_sidebar_defs() as $id => $args ) {
			if ( ! javan_seda_sidebar_registered( $id ) && function_exists( 'register_sidebar' ) ) {
				register_sidebar( $args );
			}
		}
	}
}
add_action( 'widgets_init', 'javan_seda_register_missing_sidebars', 20 );

/**
 * (اختیاری و خاموش) بازگردانی ابزارک از «ابزارک‌های غیرفعال».
 * فقط با define( 'JAVANSEDA_SIDEBAR_RECOVERY', true ) فعال می‌شود؛ به‌طور پیش‌فرض
 * هیچ فیلتری روی sidebars_widgets نصب نمی‌شود (حذفِ ریسکِ بازگشتی در v3.1.0).
 */
if ( JAVANSEDA_SIDEBAR_RECOVERY && ! function_exists( 'javan_seda_recover_sidebars' ) ) {
	function javan_seda_is_theme_widget_id( $widget_id ) {
		$widget_id = (string) $widget_id;
		if ( '' === $widget_id ) {
			return false;
		}
		if ( 0 === strpos( $widget_id, 'block-' ) ) {
			return true;
		}
		foreach ( javan_seda_widget_id_bases() as $base ) {
			if ( 0 === strpos( $widget_id, $base . '-' ) || 0 === strpos( $widget_id, $base . '__' ) ) {
				return true;
			}
		}
		return false;
	}

	function javan_seda_recover_sidebars( $sidebars_widgets ) {
		return javan_seda_safe_run( function () use ( &$sidebars_widgets ) {
			if ( ! is_array( $sidebars_widgets ) || is_admin() ) {
				return;
			}
			foreach ( array( 'left_side', 'left_side2' ) as $area ) {
				if ( ! empty( $sidebars_widgets[ $area ] ) ) {
					return; // چیزی برای ترمیم نیست
				}
			}
			$pool = array();
			foreach ( array_keys( $sidebars_widgets ) as $key ) {
				if ( ( 'wp_inactive_widgets' === $key || 0 === strpos( $key, 'orphaned_widgets' ) ) && is_array( $sidebars_widgets[ $key ] ) ) {
					$pool = array_merge( $pool, $sidebars_widgets[ $key ] );
				}
			}
			if ( ! $pool ) {
				return;
			}
			$mine = array();
			$keep = array();
			foreach ( $pool as $wid ) {
				if ( javan_seda_is_theme_widget_id( $wid ) ) {
					$mine[] = $wid;
				} else {
					$keep[] = $wid;
				}
			}
			if ( ! $mine ) {
				return;
			}
			$sidebars_widgets['left_side'] = $mine;
			if ( isset( $sidebars_widgets['wp_inactive_widgets'] ) ) {
				$sidebars_widgets['wp_inactive_widgets'] = $keep;
			}
			$GLOBALS['javan_seda_sidebar_recovered'] = $mine;
		} );

		return $sidebars_widgets;
	}
	add_filter( 'sidebars_widgets', 'javan_seda_recover_sidebars', 20 );
}

/* ---------------------------------------------------------------------------
 * ۲) رندر امن یک ناحیه (جایگزین dynamic_sidebar در قالب)
 *    - اگر ناحیه ثبت نشده باشد، اول ثبتش می‌کند
 *    - اگر dynamic_sidebar خطا بدهد، صفحه نمی‌میرد
 *    - وضعیتِ حلقهٔ جهانی را برمی‌گرداند (ابزارک‌های این قالب $wp_query را عوض می‌کنند)
 * ------------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_sidebar' ) ) {
	function javan_seda_sidebar( $index ) {
		$index = preg_replace( '/[^a-z0-9_\-]/i', '', (string) $index );
		if ( '' === $index ) {
			return;
		}

		if ( ! javan_seda_sidebar_registered( $index ) ) {
			javan_seda_register_missing_sidebars();
		}

		$snap_post    = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		$snap_wpquery = isset( $GLOBALS['wp_query'] ) ? $GLOBALS['wp_query'] : null;

		$state = 'rendered';
		if ( ! function_exists( 'dynamic_sidebar' ) ) {
			$state = 'no-dynamic_sidebar';
		} elseif ( ! javan_seda_sidebar_registered( $index ) ) {
			$state = 'not-registered';
		} elseif ( function_exists( 'is_active_sidebar' ) && ! is_active_sidebar( $index ) ) {
			$state = 'no-active-widget';
		} else {
			$ok = false;
			javan_seda_safe_run( function () use ( $index, &$ok ) {
				$ok = dynamic_sidebar( $index );
			} );
			if ( ! $ok ) {
				$state = 'empty-or-error';
			}
		}

		if ( $snap_post ) {
			$GLOBALS['post'] = $snap_post;
		}
		if ( $snap_wpquery ) {
			$GLOBALS['wp_query'] = $snap_wpquery;
		}

		if ( 'rendered' !== $state && JAVANSEDA_SIDEBAR_MARKERS ) {
			echo '<!-- javan_seda: sidebar ' . esc_html( $index ) . ' => ' . esc_html( $state ) . ' -->';
		}
	}
}

/* ---------------------------------------------------------------------------
 * ۳) گزارش تشخیصی — فقط‌خواندنی، فقط وقتی از «ab-mobile-probe.php» صدا زده شود.
 *    (در این نسخه هیچ مسیری در template_include ندارد؛ پس در رندرِ عادی، صفر است.)
 * ------------------------------------------------------------------------- */
if ( ! function_exists( 'javan_seda_expected_hashes' ) ) {
	function javan_seda_expected_hashes() {
	return array(
		'single.php'                => '74b189baef721c98b11e34365d2a37d4143ba53ee422a2682c08ddab6adfbcf2',
		'page.php'                  => 'fee8efe74cec41f61d2c0598cfb8308fd90ab8ad66b09b1c30c51bde465948ad',
		'myfunctions.php'           => 'bd185630a0c26e72a5a3f5e61cd63f89ab32b0b7407e2b92c9da897f5d1355e7',
		'style.css'                 => '9add17bac71e7a413acfc6da894fe843db9a34ff35e9ba571177da7b3eef23a0',
		'footer.php'                => '6475b69e2a86aee289102daa78806b4b0b0d9331349ed6fe69ebef5148a1479f',
		'header.php'                => 'f5174209fae4506f9952e5ae838716df21bd3f1cbea413675a42863d585306dc',
		'home.php'                  => 'a0103a063b47dd0df06b85049572bd7ed31d851c3a6239047dfa3ce8c91032f0',
		'404.php'                   => 'e8379a33fe6b36bd42b45d9672b929e65ee5e20329a1887e203e7c8b7627813e',
		'search.php'                => '4ba4d8ad7f2bb134a4b7cefd116d5e27f3a5e4a787e65cc8fbae2b96c599a288',
		'inc/vip_slider.php'        => '988b7cba33eeaab8fefae98a010e5e57ec3d94f627acc3f13601be9115379c2d',
		'inc/songs_post.php'        => '82348ad0c372f90cedc1aad7042f075a2a669119682d643c44870b7c944259e0',
		'inc/tarlanweb_player.php'  => '20a59a9df1cba22f6495a034a04be4dfcf5187168ade479fa80d692d907ac063',
		'inc/ab-news-ticker.php'    => 'b9eebd2bc03497b9011a134e8cf7325a8952a2df18eea67ba9e010422b914d0e',
		'inc/ab-perf.php'           => '03fbcbc96260de30f5919384c46a8dc4829b1429cf5f44b2f5d1efa10c786d1e',
		'inc/ab-seo.php'            => '8102491752bfd28176e6132f14a0a2637626529863b81a11e8b0a08680b8843e',
		'inc/ab-controls.php'       => '7ad040dd3691c154ea1702a4ed72be05377dbc6b7a1f14d2c53b3076519f3bd5',
		'inc/theme-options.php'     => 'c620bba375eae074b4814da2926340020d8e19d2f34a347b9935f86a8dec471a',
		'taxonomy-artist.php'       => 'a61a0cf175c3f9ee64395a3e2fec15097c1172e00941e17e009799d4b8325d99',
		'taxonomy-singer.php'       => '314121b1277eaf1afff36382ad87691fae56f1e743395cf0078240a8fcfc6807',
		'inc/ab-tax-artist.php'     => '24122ee7f0bbc964109b0187d55866c43eac50b84057e9ebbb68f86c6b033ea9',
		'inc/ab-credits.php'        => 'e86a933e1e9a4591be9cfa10de568e00730f8fa92fbb6978a13a4bb52f6fe1f8',
	);
}
}

if ( ! function_exists( 'javan_seda_file_hash' ) ) {
	function javan_seda_file_hash( $path ) {
		if ( ! is_readable( $path ) ) {
			return 'MISSING';
		}
		$c = file_get_contents( $path );
		if ( false === $c ) {
			return 'UNREADABLE';
		}
		$c = str_replace( array( "\r\n", "\r" ), "\n", $c );
		return hash( 'sha256', $c );
	}
}

/**
 * خطوط گزارش را به‌صورت آرایه برمی‌گرداند (بدون echo، بدون exit، بدون کش‌کردن صفحه).
 * @return array
 */
if ( ! function_exists( 'javan_seda_diag_lines' ) ) {
	function javan_seda_diag_lines( $template = '' ) {
		global $wp_registered_sidebars, $wp_widget_factory;

		$L    = array();
		$line = function ( $s = '' ) use ( &$L ) {
			$L[] = $s;
		};

		$theme     = wp_get_theme();
		$tdir      = get_template_directory();
		$expected  = javan_seda_expected_hashes();
		$mismatch  = 0;

		$line( '=== گزارش تشخیص «ابزارک / سایدبار / هدر» — لایهٔ ایمنی v3.1.4 ===' );
		$line( 'WordPress: ' . get_bloginfo( 'version' ) . ' | PHP: ' . PHP_VERSION . ' | نسخهٔ قالب: ' . ( $theme->get( 'Version' ) ? $theme->get( 'Version' ) : '?' ) );
		$line( 'stylesheet/template: ' . $theme->get_stylesheet() . ' / ' . $theme->get_template() . ( $theme->get_stylesheet() !== $theme->get_template() ? '   <<< چایلد‌تیم فعال است' : '' ) );
		$line( 'قالبِ این درخواست: ' . ( $template ? $template : '(نامعلوم — از کاوشگر مستقل فراخوانی شد)' ) );
		$line( 'is_singular(post): ' . ( is_singular( 'post' ) ? 'yes' : 'no' ) . ' | is_page(): ' . ( is_page() ? 'yes' : 'no' ) . ' | is_admin(): ' . ( is_admin() ? 'yes' : 'no' ) . ' | wp_is_mobile(): ' . ( wp_is_mobile() ? 'true' : 'false' ) );
		$line( '' );

		$line( '--- ۱) وضعیتِ خودِ لایهٔ ایمنی (باید «کمینه» باشد) ---' );
		global $wp_filter;
		$danger = array(
			'the_content'     => 'add_filter(the_content)  ← نسخهٔ ۳.۰.۵ داشت، این نسخه ندارد',
			'template_include'=> 'add_filter(template_include) ← نسخهٔ ۳.۰.۵ داشت، این نسخه ندارد',
			'sidebars_widgets'=> 'add_filter(sidebars_widgets) ← فقط با JAVANSEDA_SIDEBAR_RECOVERY=true',
		);
		foreach ( $danger as $hook => $desc ) {
			$mine = array();
			if ( isset( $wp_filter[ $hook ] ) ) {
				$cb = array();
				$h  = $wp_filter[ $hook ];
				if ( is_object( $h ) && method_exists( $h, 'get_callbacks' ) ) {
					$cb = $h->get_callbacks( PHP_INT_MAX );
				} else {
					$cb = (array) $h;
					$cb = isset( $cb['*'] ) ? $cb['*'] : $cb;
				}
				foreach ( (array) $cb as $prio => $set ) {
					foreach ( (array) $set as $f ) {
						if ( isset( $f['function'] ) && is_string( $f['function'] ) && 0 === strpos( $f['function'], 'javan_seda_' ) ) {
							$mine[] = $prio . '→' . $f['function'];
						}
					}
				}
			}
			$line( sprintf( '  %-28s %s', $desc, $mine ? '⚠ فعال: ' . implode( ', ', $mine ) : '✓ ندارد' ) );
		}
		$line( '  JAVANSEDA_SIDE                     : ' . ( defined( 'JAVANSEDA_SIDE' ) ? var_export( JAVANSEDA_SIDE, true ) : 'تعریف‌نشده (پیش‌فرض = روشن)' ) );
		$line( '  JAVANSEDA_SIDEBAR_RECOVERY         : ' . ( defined( 'JAVANSEDA_SIDEBAR_RECOVERY' ) ? var_export( (bool) JAVANSEDA_SIDEBAR_RECOVERY, true ) : 'پیش‌فرض = false' ) );
		$line( '  فایل ab-sidebars.php روی هاست      : ' . ( is_readable( $tdir . '/inc/ab-sidebars.php' ) ? 'حاضر (' . size_format( (int) filesize( $tdir . '/inc/ab-sidebars.php' ) ) . '، آخرین تغییر ' . date_i18n( 'Y-m-d H:i', (int) filemtime( $tdir . '/inc/ab-sidebars.php' ) ) . ')' : 'نیست (قالب بدون آن هم کار می‌کند؛ فقط ناحیه‌ها در صورت نبود ثبت نمی‌شوند)' ) );
		$line( '  نسخهٔ لایهٔ ایمنی (امضای داخل فایل) : ' . ( is_readable( $tdir . '/inc/ab-sidebars.php' ) && false !== strpos( (string) file_get_contents( $tdir . '/inc/ab-sidebars.php' ), '3.1.0' ) ? '3.1.0 (کمینه) ✓' : '⚠ نسخهٔ قدیمی/ناشناس — همان نسخه‌ای که صفحه را می‌شکاند' ) );
		$line( '' );

		$line( '--- ۲) بارگذاری فایل‌های مسئول ---' );
		$line( 'myfunctions.php بارگذاری شد؟ : ' . ( function_exists( 'leftsides' ) ? 'بله' : 'نه  <<< ناحیه‌های ابزارک ثبت نشده‌اند' ) );
		$line( 'ab-sidebars بارگذاری شد؟     : ' . ( function_exists( 'javan_seda_sidebar' ) ? 'بله' : 'نه' ) );
		foreach ( javan_seda_widget_id_bases() as $cls ) {
			$has = class_exists( $cls, false );
			$reg = $has && is_object( $wp_widget_factory ) && isset( $wp_widget_factory->widgets[ $cls ] );
			$line( sprintf( 'کلاس ابزارک %-22s : class_exists=%s | register_widget=%s', $cls, $has ? 'yes' : 'NO', $reg ? 'yes' : 'NO' ) );
		}
		$line( '' );

		$line( '--- ۳) ناحیه‌های ابزارک ---' );
		foreach ( javan_seda_sidebar_defs() as $id => $args ) {
			$registered = javan_seda_sidebar_registered( $id );
			$active     = function_exists( 'is_active_sidebar' ) ? ( is_active_sidebar( $id ) ? 'بله' : 'نه' ) : '؟';
			$line( sprintf( '  %-12s ثبت‌شده=%-5s ابزارک‌فعال=%s | نام: %s', $id, $registered ? 'بله' : 'NO', $active, $args['name'] ) );
		}
		$sbw = function_exists( 'wp_get_sidebars_widgets' ) ? wp_get_sidebars_widgets( false ) : array();
		foreach ( array( 'left_side', 'left_side2', 'left_side1', 'wp_inactive_widgets' ) as $id ) {
			$line( '  ' . str_pad( $id, 22 ) . ': ' . ( ! empty( $sbw[ $id ] ) ? implode( ', ', (array) $sbw[ $id ] ) : '(خالی)' ) );
		}
		if ( ! empty( $GLOBALS['javan_seda_sidebar_recovered'] ) ) {
			$line( '  ⚠ بازگردانی رخ داده: ' . implode( ', ', (array) $GLOBALS['javan_seda_sidebar_recovered'] ) );
		}
		$line( '' );

		$line( '--- ۴) هم‌خوانی فایل‌های روی سرور با بستهٔ مخزن ---' );
		foreach ( $expected as $rel => $hash ) {
			$now = javan_seda_file_hash( $tdir . '/' . $rel );
			if ( 'MISSING' === $now ) {
				$st = 'فایل روی سرور نیست';
			} elseif ( 0 === strpos( $hash, '__' ) ) {
				$st = '(مرجع تنظیم نشده)';
			} elseif ( $now === $hash ) {
				$st = 'مطابق بسته';
			} else {
				$st = 'متفاوت از بسته  <<< فایل روی هاست کهنه/دست‌کاری‌شده';
				$mismatch++;
			}
			$line( sprintf( '  %-22s %s', $rel, $st ) );
		}
		$line( '  تعداد فایل ناهم‌خوان: ' . $mismatch );
		$line( '' );

		$line( '--- ۵) هدر (دسکتاپ / موبایل) ---' );
		$hdr = is_readable( $tdir . '/header.php' ) ? (string) file_get_contents( $tdir . '/header.php' ) : '';
		$css = is_readable( $tdir . '/style.css' ) ? (string) file_get_contents( $tdir . '/style.css' ) : '';
		$ver = preg_match( '/Version:\s*([0-9.]+)/', $css, $mv ) ? $mv[1] : '(نامعلوم)';
		$line( '  header.php خط رندر mobiles_header : ' . ( false !== strpos( $hdr, 'mobiles_header' ) ? 'بله' : 'نه  ⇒ هدر موبایل رندر نمی‌شود' ) );
		$line( '  header.php بلوک ab-header-critical: ' . ( false !== strpos( $hdr, 'ab-header-critical' ) ? 'بله (CSS حیاتیِ ضدّکش نصب است)' : 'نه' ) );
		$line( '  style.css نسخه + بلوک ab-has-mh   : ' . $ver . ' / ' . ( false !== strpos( $css, 'ab-has-mh' ) ? 'دارد' : 'ندارد (کهنه)' ) );
		$line( '' );

		$line( '--- ۶) پلاگین‌های فعال (مشکلات رایج: کش / AMP / موبایل‌ساز / ACF) ---' );
		$act = (array) get_option( 'active_plugins', array() );
		if ( function_exists( 'wp_get_active_network_plugins' ) && is_multisite() ) {
			$act = array_merge( $act, (array) wp_get_active_network_plugins() );
		}
		$suspicious = array( 'litespeed', 'wp-rocket', 'w3tc', 'w3-total', 'wp-super-cache', 'breeze', 'autoptimize', 'amp', 'mobile', 'elementor', 'acf', 'advanced-custom-fields', 'wpml', 'cachify', 'swift', 'wpo3' );
		if ( ! $act ) {
			$line( '  (فهرست active_plugins خالی است — احتمالاً پلاگین‌ها در دیتابیس شبکه‌اند یا سایت کش سرو می‌کند)' );
		}
		foreach ( $act as $p ) {
			$flag = '';
			foreach ( $suspicious as $s ) {
				if ( false !== stripos( (string) $p, $s ) ) {
					$flag = '   <<< بررسیِ سازگاری/تعارض';
					break;
				}
			}
			$line( '  ' . $p . $flag );
		}
		$line( '' );

		$line( '--- ۷) نتیجه‌گیری ---' );
		if ( ! function_exists( 'leftsides' ) ) {
			$line( '  ۱) myfunctions.php بارگذاری نمی‌شود ⇒ ناحیه‌های ابزارک ثبت نیستند (لایهٔ ایمنی جبران می‌کند اگر خودش هم بارگذاری شود).' );
		} elseif ( ! function_exists( 'javan_seda_sidebar' ) ) {
			$line( '  ۲) لایهٔ ایمنی نصب نیست (یا JAVANSEDA_SIDE=false). single.php به dynamic_sidebarِ اصلی برمی‌گردد؛ این حالت سالم است.' );
		} elseif ( 0 === $mismatch ) {
			$line( '  ۳) فایل‌ها هم‌سان‌اند و لایهٔ ایمنی «کمینه» است؛ اگر ابزارک نمایش داده نمی‌شود، ناحیه در پیشخوان خالی است (بخش ۳).' );
		} else {
			$line( '  ۴) ' . $mismatch . ' فایل روی هاست با بسته فرق دارد؛ فایل‌های drop-in را دقیق اعمال کن و بعد کش را پاک کن.' );
		}
		$line( '' );
		$line( 'پایان گزارش (فقط‌خواندنی — هیچ داده‌ای تغییر نمی‌کند).' );

		return $L;
	}
}
