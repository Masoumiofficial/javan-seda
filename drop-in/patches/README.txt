پچ‌های اضطراری — نسخهٔ 3.1.2 (برای هاستی که فایل‌هایش هنوز «اصلی/۳.۰.۲» است)
================================================================================
چرا این پوشه؟  نشانه‌ها (مانیفستِ فایل ab-sidebars.php شما) نشان داد هاست هنوز
header.php / style.css / single.php «اصلی» را اجرا می‌کند و فقط یک فایل جایگزین شده.
در header.php اصلیِ قالب:
  • خط ۱۶ : `if(wp_is_mobile()){ ?><style>body{padding-top: 60px;}`  (کم به‌ازای هدر ۶۵px)
  • خط ۲۳ : `if(wp_is_mobile()){}else{ ?> <header id="header">`  ⇒ در موبایل هیچ هدری نیست
  • خط ۵۰ : `get_template_part('inc/mobiles_header');` بدون شرط ⇒ رندرِ دوبل در دسکتاپ

فایل‌ها:
  header.original-patched.php  = همان header.php اصلیِ شما + ۱۹ سطرِ افزوده (بدون هیچ حذف/ویرایش؛
      با diff تأیید شده). بلوک <style id="ab-mobile-patch"> داخل همان شرطِ wp_is_mobile()
      می‌نشیند، پس دسکتاپ دقیقاً مثل الان می‌ماند. چه می‌کند:
        body{padding-top:90px !important}            ← ۷۰px نوار + ۲۰px فاصله (درخواست v3.1.2)
        #mobiles_header{height:70px !important}     ← کمی بلندتر، همان‌طور که خواستید
        #header/.menu_right/#searches{display:none} ← رفع رندرِ دوبل در موبایل
        .tarlanweb_center{width:auto;max-width:100%} ← رفع بریده‌شدن محتوا در عرض باریکِ RTL
        .ab-news,.ab-quickbar{margin-top:20px}       ← فاصلهٔ نوار «اخبار روز» از هدر

نصب:  کپی روی wp-content/themes/javanseda/header.php (اول از فایل فعلی بکاپ بگیرید)
      و بعد Purge All در افزونهٔ کش + CDN.

قاعدهٔ انتخاب (مهم):
  • اگر در header.php هاستتان عبارت «ab-has-mh» هست ⇒ نسخهٔ ۳.۰.۴+ نصب است؛ این پچ را
    نصب نکنید (فقط style.css / home.php / myfunctions.php / inc/ab-news-ticker.php را بگذارید).
  • اگر «ab-has-mh» نیست و خط ۱۶ همان `padding-top: 60px` است ⇒ همین پچ را نصب کنید.
  • هر دو را با هم نصب نکنید.

توصیهٔ اصلی: به‌جای پچ، بستهٔ drop-in-v3.1.2.zip را کامل اعمال کنید — چون single.php روی
هاست شما هنوز نسخهٔ اصلی است و هیچ‌کدام از محافظ‌های ACF/plugin.php را ندارد.
