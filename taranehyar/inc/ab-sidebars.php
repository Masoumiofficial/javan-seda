<?php
/**
 * ترانه یار (آهنگ‌بان) — لایهٔ ایمنی «سایدبار / ابزارک» — نسخهٔ 3.1.0 (بازمهندسی‌شده)
 * امضای نسخه برای تشخیص خودکار کاوشگر: TARA_SIDE_LAYER = 3.1.0 (کمینه؛ بدون هوکِ رندر) — بستهٔ 3.1.2
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
 *   ۲) تابع رندر امن tara_sidebar() که single.php / page.php صدا می‌زنند؛
 *      اگراگر داخلش خطایی رخ دهد، صفحه نمی‌میرد (try/catch) و فقط یک کامنت HTML می‌ماند.
 *   ۳) گزارش تشخیصیِ فقط‌خواندنی به‌صورت «آرایهٔ خطوط»؛ خروجی را هیچ‌گاه با
 *      template_include چاپ نمی‌کند — فقط فایل ab-mobile-probe.php آن را صدا می‌زند.
 *   ۴) (اختیاری و خاموش‌به‌مروز) بازگردانی ابزارک‌های افتاده در «ابزارک‌های غیرفعال»
 *      با TARA_SIDEBAR_RECOVERY = true. تا وقتی این define نشود، هیچ فیلتری
 *      روی دادهٔ ابزارک‌ها اعمال نمی‌شود.
 *
 * هرگز انجام نمی‌دهد:  the_content · the_title · template_redirect · pre_get_posts ·
 *   template_include · wp_head/wp_footer · نوشتن در دیتابیس · include کردن فایل‌های قالب.
 *
 * کلید خاموشیِ کامل (اگر یک روز دوباره مشکوک شدید، در wp-config.php بنویسید):
 *   define( 'TARA_SIDE', false );
 *
 * قوانین همیشگی: بدون دست‌زدن به تاکسونومی/هوک سراسری، بدون گرادینت،
 * پالت سازمانی #FFFFFF / #185ADB / #111111.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* هیچ کاری نکن — کلید خاموشی */
if ( defined( 'TARA_SIDE' ) && false === TARA_SIDE ) {
	return;
}

if ( ! defined( 'TARA_SIDEBAR_RECOVERY' ) ) {
	define( 'TARA_SIDEBAR_RECOVERY', false ); // خاموش‌به‌مروز (تصمیم آگاهانهٔ v3.1.0)
}
if ( ! defined( 'TARA_SIDEBAR_MARKERS' ) ) {
	define( 'TARA_SIDEBAR_MARKERS', true );     // کامنت تشخیصی وقتی ناحیه خالی است
}

/* ---------------------------------------------------------------------------
 * ۰) ابزار کمکی: اجرای محافظت‌شده (خطای PHP داخل کار ما، صفحه را نکشد)
 * ------------------------------------------------------------------------- */
if ( ! function_exists( 'tara_safe_run' ) ) {
	function tara_safe_run( $fn ) {
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
if ( ! function_exists( 'tara_widget_id_bases' ) ) {
	function tara_widget_id_bases() {
		return array( 'tarlanweb_ir_newp', 'tarlanweb_ir_popular', 'tarlanweb_ir_rand' );
	}
}

if ( ! function_exists( 'tara_sidebar_defs' ) ) {
	function tara_sidebar_defs() {
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

if ( ! function_exists( 'tara_sidebar_registered' ) ) {
	function tara_sidebar_registered( $id ) {
		global $wp_registered_sidebars;
		return is_array( $wp_registered_sidebars ) && isset( $wp_registered_sidebars[ $id ] );
	}
}

/** ثبت ناحیه‌های جاافتاده — idempotent و بدون بازنویسی ناحیه‌های موجود. */
if ( ! function_exists( 'tara_register_missing_sidebars' ) ) {
	function tara_register_missing_sidebars() {
		foreach ( tara_sidebar_defs() as $id => $args ) {
			if ( ! tara_sidebar_registered( $id ) && function_exists( 'register_sidebar' ) ) {
				register_sidebar( $args );
			}
		}
	}
}
add_action( 'widgets_init', 'tara_register_missing_sidebars', 20 );

/**
 * (اختیاری و خاموش) بازگردانی ابزارک از «ابزارک‌های غیرفعال».
 * فقط با define( 'TARA_SIDEBAR_RECOVERY', true ) فعال می‌شود؛ به‌طور پیش‌فرض
 * هیچ فیلتری روی sidebars_widgets نصب نمی‌شود (حذفِ ریسکِ بازگشتی در v3.1.0).
 */
if ( TARA_SIDEBAR_RECOVERY && ! function_exists( 'tara_recover_sidebars' ) ) {
	function tara_is_theme_widget_id( $widget_id ) {
		$widget_id = (string) $widget_id;
		if ( '' === $widget_id ) {
			return false;
		}
		if ( 0 === strpos( $widget_id, 'block-' ) ) {
			return true;
		}
		foreach ( tara_widget_id_bases() as $base ) {
			if ( 0 === strpos( $widget_id, $base . '-' ) || 0 === strpos( $widget_id, $base . '__' ) ) {
				return true;
			}
		}
		return false;
	}

	function tara_recover_sidebars( $sidebars_widgets ) {
		return tara_safe_run( function () use ( &$sidebars_widgets ) {
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
				if ( tara_is_theme_widget_id( $wid ) ) {
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
			$GLOBALS['tara_sidebar_recovered'] = $mine;
		} );

		return $sidebars_widgets;
	}
	add_filter( 'sidebars_widgets', 'tara_recover_sidebars', 20 );
}

/* ---------------------------------------------------------------------------
 * ۲) رندر امن یک ناحیه (جایگزین dynamic_sidebar در قالب)
 *    - اگر ناحیه ثبت نشده باشد، اول ثبتش می‌کند
 *    - اگر dynamic_sidebar خطا بدهد، صفحه نمی‌میرد
 *    - وضعیتِ حلقهٔ جهانی را برمی‌گرداند (ابزارک‌های این قالب $wp_query را عوض می‌کنند)
 * ------------------------------------------------------------------------- */
if ( ! function_exists( 'tara_sidebar' ) ) {
	function tara_sidebar( $index ) {
		$index = preg_replace( '/[^a-z0-9_\-]/i', '', (string) $index );
		if ( '' === $index ) {
			return;
		}

		if ( ! tara_sidebar_registered( $index ) ) {
			tara_register_missing_sidebars();
		}

		$snap_post    = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		$snap_wpquery = isset( $GLOBALS['wp_query'] ) ? $GLOBALS['wp_query'] : null;

		$state = 'rendered';
		if ( ! function_exists( 'dynamic_sidebar' ) ) {
			$state = 'no-dynamic_sidebar';
		} elseif ( ! tara_sidebar_registered( $index ) ) {
			$state = 'not-registered';
		} elseif ( function_exists( 'is_active_sidebar' ) && ! is_active_sidebar( $index ) ) {
			$state = 'no-active-widget';
		} else {
			$ok = false;
			tara_safe_run( function () use ( $index, &$ok ) {
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

		if ( 'rendered' !== $state && TARA_SIDEBAR_MARKERS ) {
			echo '<!-- taranehyar: sidebar ' . esc_html( $index ) . ' => ' . esc_html( $state ) . ' -->';
		}
	}
}

/* ---------------------------------------------------------------------------
 * ۳) گزارش تشخیصی — فقط‌خواندنی، فقط وقتی از «ab-mobile-probe.php» صدا زده شود.
 *    (در این نسخه هیچ مسیری در template_include ندارد؛ پس در رندرِ عادی، صفر است.)
 * ------------------------------------------------------------------------- */
if ( ! function_exists( 'tara_expected_hashes' ) ) {
	function tara_expected_hashes() {
	return array(
		'single.php'                => 'eecf202c837c2d492d2905500ca6cd699fb10b0e8be1b6d07d4b0b2dab9fcc0c',
		'page.php'                  => 'eed2370a3205d53f58bf0d5721275ae8896daf6c32e862403d9fe3a2982aac50',
		'myfunctions.php'           => '96312a49c0ceec52ae659282f63d51c8c04f8c25a78829d7478fe7c141e672a9',
		'style.css'                 => '492200783c8154c742eb3073d92079f21afa768c7bb34de3ec71a30c6d5d9fdb',
		'footer.php'                => '504028e8c50795974d855ffd42b5ac6c8ff952958d466aaf82cc46707cf00c56',
		'header.php'                => '7fcc07844102ca92c04fab1c60105b878c08ecb67ac552e9838f400de4538f49',
		'home.php'                  => 'a8280d8c5be6580c49fc174dc9e21a9451c91f989381a10f2b66c5607a733f47',
		'404.php'                   => '41762e52e2282ae06d2facf64ab04633cc61faaf802745f385f1e0fa59a07afe',
		'search.php'                => 'fd1fe11640f8fcb7544a5d362dea37cfd1fdb4df1390bd4fb7f29d34979853b2',
		'inc/vip_slider.php'        => '4e0277fdfa418bc1e84607088cfa8dedc2dce729e0f5394a27efb9bc0039834c',
		'inc/songs_post.php'        => '1863cb35808b0915c52553a411be994ffc64cefdf6586deae3a8e4f0a46f430f',
		'inc/tarlanweb_player.php'  => 'b255117a82a5bea5e59c2cf06ddd72b2884044042538084388d56c3195adfe14',
		'inc/ab-news-ticker.php'    => 'e75c2a1dd86cc632c01d253a6f199935c8fb674b508c3986ba1855ae751a15fc',
		'inc/ab-perf.php'           => 'dc305da6590c6b4002214f7db71412c0d11965f9a024c3e77ea35517f85234f4',
		'inc/ab-seo.php'            => 'f3f3e129231d61a73479eb2a0f86ca83595de5653869903efee80927068c5402',
		'inc/ab-controls.php'       => 'eed236181fe745999f0c150e4cec8c2e8c6b6daf3b52bf4e98a2347f60851176',
		'inc/theme-options.php'     => '5304fd57ec3304f652783bc441ffe005b425718f990d4f77dcdae7b1a1761101',
		'taxonomy-artist.php'       => '1d5cb89d0a7bbeab3a923a91a122f6e19a032fc070326211bca812a9c75212c4',
		'taxonomy-singer.php'       => '38ed6b25f0146a900e1c580a85d64698497caf73c561abbadc0e9e4d245f9fce',
		'inc/ab-tax-artist.php'     => '8aaa995ae6658ecbb7a2dd1ea32afc5c881edfaf1abc1594e89ee3a4c1bba9b0',
		'inc/ab-credits.php'        => 'e61a7a795a9ce15db3c56bfdd4a2e16e7f23e3831518f7a789fb39c9daddbe3c',
	);
}
}

if ( ! function_exists( 'tara_file_hash' ) ) {
	function tara_file_hash( $path ) {
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
if ( ! function_exists( 'tara_diag_lines' ) ) {
	function tara_diag_lines( $template = '' ) {
		global $wp_registered_sidebars, $wp_widget_factory;

		$L    = array();
		$line = function ( $s = '' ) use ( &$L ) {
			$L[] = $s;
		};

		$theme     = wp_get_theme();
		$tdir      = get_template_directory();
		$expected  = tara_expected_hashes();
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
			'sidebars_widgets'=> 'add_filter(sidebars_widgets) ← فقط با TARA_SIDEBAR_RECOVERY=true',
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
						if ( isset( $f['function'] ) && is_string( $f['function'] ) && 0 === strpos( $f['function'], 'tara_' ) ) {
							$mine[] = $prio . '→' . $f['function'];
						}
					}
				}
			}
			$line( sprintf( '  %-28s %s', $desc, $mine ? '⚠ فعال: ' . implode( ', ', $mine ) : '✓ ندارد' ) );
		}
		$line( '  TARA_SIDE                     : ' . ( defined( 'TARA_SIDE' ) ? var_export( TARA_SIDE, true ) : 'تعریف‌نشده (پیش‌فرض = روشن)' ) );
		$line( '  TARA_SIDEBAR_RECOVERY         : ' . ( defined( 'TARA_SIDEBAR_RECOVERY' ) ? var_export( (bool) TARA_SIDEBAR_RECOVERY, true ) : 'پیش‌فرض = false' ) );
		$line( '  فایل ab-sidebars.php روی هاست      : ' . ( is_readable( $tdir . '/inc/ab-sidebars.php' ) ? 'حاضر (' . size_format( (int) filesize( $tdir . '/inc/ab-sidebars.php' ) ) . '، آخرین تغییر ' . date_i18n( 'Y-m-d H:i', (int) filemtime( $tdir . '/inc/ab-sidebars.php' ) ) . ')' : 'نیست (قالب بدون آن هم کار می‌کند؛ فقط ناحیه‌ها در صورت نبود ثبت نمی‌شوند)' ) );
		$line( '  نسخهٔ لایهٔ ایمنی (امضای داخل فایل) : ' . ( is_readable( $tdir . '/inc/ab-sidebars.php' ) && false !== strpos( (string) file_get_contents( $tdir . '/inc/ab-sidebars.php' ), '3.1.0' ) ? '3.1.0 (کمینه) ✓' : '⚠ نسخهٔ قدیمی/ناشناس — همان نسخه‌ای که صفحه را می‌شکاند' ) );
		$line( '' );

		$line( '--- ۲) بارگذاری فایل‌های مسئول ---' );
		$line( 'myfunctions.php بارگذاری شد؟ : ' . ( function_exists( 'leftsides' ) ? 'بله' : 'نه  <<< ناحیه‌های ابزارک ثبت نشده‌اند' ) );
		$line( 'ab-sidebars بارگذاری شد؟     : ' . ( function_exists( 'tara_sidebar' ) ? 'بله' : 'نه' ) );
		foreach ( tara_widget_id_bases() as $cls ) {
			$has = class_exists( $cls, false );
			$reg = $has && is_object( $wp_widget_factory ) && isset( $wp_widget_factory->widgets[ $cls ] );
			$line( sprintf( 'کلاس ابزارک %-22s : class_exists=%s | register_widget=%s', $cls, $has ? 'yes' : 'NO', $reg ? 'yes' : 'NO' ) );
		}
		$line( '' );

		$line( '--- ۳) ناحیه‌های ابزارک ---' );
		foreach ( tara_sidebar_defs() as $id => $args ) {
			$registered = tara_sidebar_registered( $id );
			$active     = function_exists( 'is_active_sidebar' ) ? ( is_active_sidebar( $id ) ? 'بله' : 'نه' ) : '؟';
			$line( sprintf( '  %-12s ثبت‌شده=%-5s ابزارک‌فعال=%s | نام: %s', $id, $registered ? 'بله' : 'NO', $active, $args['name'] ) );
		}
		$sbw = function_exists( 'wp_get_sidebars_widgets' ) ? wp_get_sidebars_widgets( false ) : array();
		foreach ( array( 'left_side', 'left_side2', 'left_side1', 'wp_inactive_widgets' ) as $id ) {
			$line( '  ' . str_pad( $id, 22 ) . ': ' . ( ! empty( $sbw[ $id ] ) ? implode( ', ', (array) $sbw[ $id ] ) : '(خالی)' ) );
		}
		if ( ! empty( $GLOBALS['tara_sidebar_recovered'] ) ) {
			$line( '  ⚠ بازگردانی رخ داده: ' . implode( ', ', (array) $GLOBALS['tara_sidebar_recovered'] ) );
		}
		$line( '' );

		$line( '--- ۴) هم‌خوانی فایل‌های روی سرور با بستهٔ مخزن ---' );
		foreach ( $expected as $rel => $hash ) {
			$now = tara_file_hash( $tdir . '/' . $rel );
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
		} elseif ( ! function_exists( 'tara_sidebar' ) ) {
			$line( '  ۲) لایهٔ ایمنی نصب نیست (یا TARA_SIDE=false). single.php به dynamic_sidebarِ اصلی برمی‌گردد؛ این حالت سالم است.' );
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
