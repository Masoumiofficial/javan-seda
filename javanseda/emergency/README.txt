فایلِ نجات — فقط وقتی functions.php اصلی گم شده باشد
=====================================================

نشانه: هر صفحه با «خطای مهم» سفید است و در debug.log این خط هست:

  Uncaught Error: Call to undefined function ot_get_option()
  in …/wp-content/themes/javanseda/header.php:27

علت: وردپرس از پوسته فقط functions.php را لود می‌کند. فایلِ اصلیِ functions.php
رمزگذاری‌شده است و OptionTree (ot_get_option)، myfunctions.php و بقیهٔ لایه‌ها را
می‌آورد. اگر با «آپلود پوسته / جایگزینی» در پیشخوان پاک شود، همهٔ آن‌ها هم می‌روند.

رفعِ قطعی (بهتر):
  functions.php و RTL_License_41d6c5f704e3a746.php را از دانلودِ اصلیِ rtl-theme.com
  به wp-content/themes/javanseda/ برگردانید. (از بکاپِ خودِ همان پوشه هم می‌شود.)
  بعد style.css را چک کنید: باید Version: 3.5.1 باشد.

رفعِ موقت (تا رسیدنِ فایلِ اصلی):
  ۱) functions.rescue.php را به functions.php تغییر نام دهید و در ریشهٔ پوسته بگذارید.
  ۲) سایت بالا می‌آید؛ در پیشخوان یک هشدارِ قرمز می‌بینید.
  ۳) هر وقت فایلِ اصلی برگشت، functions.php را با آن جایگزین کنید.

توجه: این فایل جایگزینِ لایسنس نیست؛ در حالتِ عادی از آن استفاده نکنید.
