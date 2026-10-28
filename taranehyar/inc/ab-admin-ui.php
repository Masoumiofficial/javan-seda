<?php
/**
 * v3.4.3 — پوستۀ «تنظیمات قالب» (Option Tree) در پیشخوان.
 *
 * هیچ کاری در سمت سایت نمی‌کند؛ فقط فایل css/ab-admin.css را روی سه صفحۀ
 * Option Tree enqueue می‌کند. قلاب: ot_admin_styles_after — انتهای
 * ot_admin_styles() در inc/option-tree/includes/ot-functions-admin.php
 * (خط‌های 754 تا 795) ⇒ stylesheet ما *بعد از* ot-admin.css چاپ می‌شود، پس
 * پوسته بدون هیچ !important تازه‌ای می‌نشیند.
 *
 * باز/بسته: «تنظیمات قالب ← کنترل رابط ← پوستۀ مدرِ تنظیمات» (پیش‌فرض روشن)
 *           یا define( 'TARA_ADMIN_UI', false );  در wp-config.php
 * نبودن این فایل = دقیقاً همان رفتار قبلی (بدون پوسته).
 *
 * @package JavanSeda
 * @since   3.4.3
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! function_exists( 'tara_admin_ui_on' ) ) {
  /**
   * آیا پوستۀ تنظیمات روشن است؟  define() → Option Tree → پیش‌فرض true
   *
   * @return bool
   */
  function tara_admin_ui_on() {
    if ( function_exists( 'tara_ui_on' ) ) {
      return (bool) tara_ui_on( 'ab_admin_ui', true, 'TARA_ADMIN_UI' );
    }
    if ( defined( 'TARA_ADMIN_UI' ) ) {
      return (bool) constant( 'TARA_ADMIN_UI' );
    }
    if ( function_exists( 'ot_get_option' ) ) {
      $v = ot_get_option( 'ab_admin_ui' );
      if ( is_string( $v ) && '' !== $v ) {
        return 'off' !== $v;
      }
    }
    return true;
  }
}

if ( ! function_exists( 'tara_admin_ui_screens' ) ) {
  /**
   * شناسۀ صفحه‌هایی که پوسته روی آن‌ها اعمال می‌شود.
   *
   * @return string[]
   */
  function tara_admin_ui_screens() {
    return array(
      'taranehyar_page_ot-theme-options', /* ۳.۶.۰: تنظیمات زیر منوی «ترانه یار» */
      'appearance_page_ot-theme-options', /* سازگاری: اگر کسی پدر را به «نمایش» برگرداند */
      'toplevel_page_ot-settings',        /* ویرایشگر تنظیمات + چیدمان‌ها */
      'optiontree_page_ot-documentation',  /* مستندات */
    );
  }
}

if ( ! function_exists( 'tara_admin_ui_enqueue' ) ) {
  /**
   * enqueue پوسته، فقط روی صفحه‌های Option Tree.
   *
   * @return void
   */
  function tara_admin_ui_enqueue() {
    if ( ! tara_admin_ui_on() ) {
      return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    $id     = is_object( $screen ) && isset( $screen->id ) ? (string) $screen->id : '';

    if ( ! in_array( $id, tara_admin_ui_screens(), true ) ) {
      return;
    }

    $dir = get_template_directory();

    if ( ! file_exists( $dir . '/css/ab-admin.css' ) ) {
      return;
    }

    $ver = (string) filemtime( $dir . '/css/ab-admin.css' );

    wp_enqueue_style(
      'javan-seda-admin-css',
      trailingslashit( get_template_directory_uri() ) . 'css/ab-admin.css',
      array(),
      $ver
    );
  }
  add_action( 'ot_admin_styles_after', 'tara_admin_ui_enqueue', 20 );
}

/* -------------------------------------------------------------------------
 * hygiene داده‌ها (سایت را دست نمی‌زند؛ فقط هنگام «ذخیرهٔ تغییرات» اجرا می‌شود):
 * شمارنده‌های قالب (تعداد اسلایدها، تعداد آهنگها، مطالب هنرمند، …) قبلاً فیلد
 * متنی بودند و عدد فارسی/عربی (۱۲) ذخیره می‌شد → داخل WP_Query می‌رفت و بلوک
 * خالی یا «همهٔ آهنگ‌ها» می‌شد. حالا هر numeric-slider پیش از ذخیره به عدد
 * لاتین تبدیل می‌شود. این بخش به پوستهٔ بصری وابسته نیست و همیشه فعال است.
 * ------------------------------------------------------------------------- */
if ( ! function_exists( 'tara_admin_ui_normalize_counts' ) ) {
  /**
   * @param mixed  $input     مقدار خامِ فیلد.
   * @param string $type      نوع فیلد Option Tree.
   * @param string $field_id  کلید گزینه.
   * @return mixed
   */
  function tara_admin_ui_normalize_counts( $input, $type, $field_id ) {

    if ( 'numeric-slider' !== $type || ! is_string( $input ) ) {
      return $input;
    }

    $digits = array(
      '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
      '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
      '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
      '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
      '،' => ',', '٫' => '.', ' ' => '', "Â " => '',
    );

    $in = strtr( trim( $input ), $digits );

    /* فقط عدد صحیح مجاز است؛ هر چیز دیگر = مقدار قبلی دست‌نخورده */
    if ( ! preg_match( '/^-?[0-9]{1,4}$/', $in ) ) {
      return $input;
    }

    return (string) intval( $in );
  }
  add_filter( 'ot_after_validate_setting', 'tara_admin_ui_normalize_counts', 10, 3 );
}
