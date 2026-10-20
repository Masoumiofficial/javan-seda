جوان‌صدا ۳.۵.۰ — بستۀ «قالبِ کاملِ سالم» (install-note)
================================================================

این بسته = همۀ کدهای پوسته (۳۵۰+ فایل) به‌جز دو فایلِ رمزگذاری‌شدۀ لایسنس:
  • javanseda/functions.php
  • javanseda/RTL_License_41d6c5f704e3a746.php
آن دو فایل عمداً در بسته نیستند، چون تنها کپیِ سالمشان همان است که روی سرور شما
(از دانلودِ اصلیِ rtl-theme.com) هست؛ هر جایگزینی‌ای = پیامِ
«The file …/javanseda/functions.php is corrupted.» و از کار افتادنِ کل سایت.

شیوۀ نصب (توصیه‌شده) — به‌روزرسانیِ همان پوشۀ موجود
  ۱) از wp-content/themes/javanseda/ بکاپ بگیرید (فقط پوشه را zip کنید).
  ۲) محتوای این بسته را روی همان مسیر بازکنید (File Manager → Extract / FTP).
     چون فایل‌های لایسنس داخلِ بسته نیستند، بازنویسی نمی‌شوند.
  ۳) حتماً افزونهٔ کش/CDN را Purge کنید (Purge All + Cloudflare → Purge Everything).
  ۴) style.css را باز کنید؛ باید «Version: 3.5.0» باشد.

نصبِ از صفر (سرورِ تازه)
  این بسته به‌تنهایی سایت را بالا نمی‌آورد (functions.phpِ لایسنس که myfunctions.php و
  OptionTree را بار می‌کند در آن نیست). پس: اول نسخۀ خریداری‌شدۀ اصلی را از
  rtl-theme.com نصب کنید، بعد محتوای این بسته را رویش بریزید.

کلیدِ اضطراری — اگر صفحه‌ای خراب شد
  در wp-config.php این خط را اضافه کنید (بالای «That's all, stop editing»):
      define( 'JAVANSEDA_ORIGINAL_TEMPLATES', true );
  ⇒ همۀ قالب‌هایی که ما بازنویسی کرده بودیم (header, footer, home, single, page,
  category, tag, search, 404, comments, artist-template, taxonomy-singer و
  taxonomy-artist) دقیقاً مثل نسخۀ اصلیِ فروشنده رندر می‌شوند — بدون پاک‌کردن هیچ فایلی.
  فایل‌های اصلی در پوشۀ javanseda/original/ هستند (همان بایت‌های تحویلِ اولیه، تأییدشده).
  برای برگشتن به حالت ۳.۵.۰ فقط همان یک خط را پاک کنید.

کلیدهای موجود برای خاموش‌کردنِ بخش‌های تکی (در wp-config.php)
      define( 'JAVANSEDA_TERM_FIELDS', false );      // فیلدهای اضافیِ دسته/هنرمند
      define( 'JAVANSEDA_ARTIST_TAX', false );       // نامکِ artist → برمی‌گردد به singer
      define( 'JAVANSEDA_ARTIST_ACF_RULES', false ); // قواعدِ ACF
  هر لایه با function_exists/is_readable محافظت شده؛ نبودنش سایت را نمی‌اندازد.

اگر صفحه‌ای «خطای مهم» داد
  فایل tools/diag/javan-seda-check.php را در wp-content/ بگذارید و باز کنید:
      …/wp-content/javan-seda-check.php?admin=1&route=/آدرسِ-همان-صفحه&raw=1
  پیام، فایل و شمارۀ سطرِ خطا + وضعیتِ is_404/is_archive/is_tax و کوئریِ وردپرس را چاپ می‌کند.
