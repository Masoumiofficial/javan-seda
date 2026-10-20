جوان‌صدا ۳.۵.۱ — نصب (»فقط Extract روی پوشهٔ موجود«)
==============================================================

⛔ این zip را در پیشخوان وردپرس آپلود نکنید
   (نمایش → پوسته‌ها → افزودن → بارگذاری پوسته). وردپرس در این مسیر **پوشهٔ قبلی را
   پاک می‌کند** و چون دو فایلِ رمزگذاری‌شدهٔ لایسنس (functions.php و
   RTL_License_41d6c5f704e3a746.php) عمداً در این بسته نیستند، با هم پاک می‌شوند.
   نتیجه‌اش این خطاست:
     Fatal error: Call to undefined function ot_get_option() in …/header.php

✅ راهِ درست
   ۱) از wp-content/themes/javanseda/ بکاپ بگیرید (فقط پوشه را zip کنید).
   ۲) در File Manager هاست (یا FTP) وارد wp-content/themes/javanseda/ شوید و
      محتوای همین zip را همان‌جا Extract/Overwrite کنید. فایل‌های لایسنس
      چون در بسته نیستند، دست‌نخورده می‌مانند.
   ۳) Purge All در افزونهٔ کش + Purge در CDN.
   ۴) style.css را باز کنید؛ باید «Version: 3.5.1» باشد.

🔴 اگر بدتر شد (صفحه‌ها سفید شدند)
   یعنی functions.php پاک شده است. دو کار:
     الف) functions.php و RTL_License_41d6c5f704e3a746.php را از دانلودِ اصلیِ
          rtl-theme.com (یا از بکاپِ قبل از نصب) برگردانید — رفعِ قطعی و ۲ دقیقه‌ای.
     ب) تا آن موقع: فایلِ emergency/functions.rescue.php را در ریشهٔ پوسته بگذارید
          و نامش را functions.php بگذارید. سایت بالا می‌آید و در پیشخوان هشدار
          می‌دهد. بعد از برگرداندنِ فایلِ اصلی، همین فایلِ نجات را حذف کنید.

کلیدِ اضطراریِ قالب‌ها (بدونِ پاک‌کردنِ هیچ فایل) — در wp-config.php:
      define( 'JAVANSEDA_ORIGINAL_TEMPLATES', true );
   ⇒ هر ۱۳ قالبی که ما بازنویسی کرده بودیم، مثل نسخهٔ اصلیِ فروشنده رندر می‌شوند.
   فایل‌های اصلی در javanseda/original/ و بایت‌به‌بایت برابر با 805e28c هستند.

کلیدهای خاموش‌کردنِ لایه‌ها (اختیاری):
      define( 'JAVANSEDA_TERM_FIELDS', false );
      define( 'JAVANSEDA_ARTIST_TAX', false );
      define( 'JAVANSEDA_ARTIST_ACF_RULES', false );

⚠ این بسته «نصبِ از صفر» نیست: license فایل لازم است (همان دو فایل بالا).
