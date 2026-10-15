# نسخهٔ 3.1.0 — «لایهٔ ایمنی، بدون دخالت در رندر»

## ۱) چه اتفاقی افتاد؟ (تشخیص نهایی)

شما درست حدس زدید که مشکل از فایل‌های **افزوده‌شدهٔ ما** بود، نه از `single.php`:
فایل `inc/ab-sidebars.php` (نسخهٔ ۳.۰.۲ تا ۳.۰.۵) روی **سه قلاب سراسری** نشسته بود که
در خطِ رندرِ هر صفحهٔ سایت دخالت می‌کردند:

| قلاب نسخهٔ قبل | کارش | چرا خطرناک بود |
|---|---|---|
| `add_filter('the_content', …, 9999)` | «نجات محتوا»: اگر متن خالی بود از `post_content` بازسازی و `do_shortcode()` می‌کرد | هر افزونه‌ای که روی `the_content` فعال باشد **دوباره** اجرا می‌شد؛ در رندرِ موبایل همین باعث خالی‌شدن/نیمه‌کاره‌شدن بلوک محتوا می‌شد |
| `add_filter('template_include', …)` ×۲ | مسیرِ قالب را برای گزارشِ `?ab_sidebars_diag=1` و کاوشگر می‌گرفت | در **هر** درخواستِ_front-end_ اجرا می‌شود؛ با قالب‌سازها/AMP/کش تداخل دارد |
| `add_filter('sidebars_widgets', …)` | بازگردانی ابزارک از «ابزارک‌های غیرفعال» | `is_active_sidebar()` داخلش دوباره همان فیلتر را صدا می‌زد → ریسک بازگشت/تأخیر |
| `require_once 'inc/widgets.php'` | بارگذاری دستی فایل ابزارک‌ها | تعارضِ ثبت دوبارهٔ کلاس‌ها با `functions.php` رمزنگاری‌شده |

حذف‌کردن فایل توسط شما ⇒ همهٔ این‌ها خاموش شد و صفحه درست شد؛ فقط «ناحیهٔ ابزارک»
دیگر بی‌مراقب ماند. **این نسخه همان فی‌الbenefit را نگه می‌دارد و همهٔ آن خطرها را حذف کرده است.**

## ۲) نسخهٔ جدید چه می‌کند / چه نمی‌کند

**انجام می‌دهد (فقط همین ۴ کار):**
1. اگر ناحیه‌های `left_side` / `left_side2` / `left_side1` ثبت نشده باشند، در `widgets_init` **با همان شناسه‌ها** ثبتشان می‌کند (idempotent — چیزی را بازنویسی نمی‌کند).
2. تابع رندر امن `javan_seda_sidebar()` که `single.php`/`page.php` صدا می‌زنند؛ با `try/catch` — اگر ابزارک خطا بدهد **صفحه نمی‌میرد** و فقط یک کامنت HTML می‌ماند.
3. برگرداندن وضعیت `$wp_query` / `$post` بعد از اجرای ابزارک‌ها (ابزارک‌های این قالب با `new WP_Query` کار می‌کنند و این بازگردانی، همان چیزی است که نبودش «صفحهٔ نصفه» می‌سازد).
4. گزارش تشخیصی به‌صورت **آرایهٔ خطوط** که فقط از فایل `ab-mobile-probe.php` چاپ می‌شود (دیگر هیچ قلابی روی `template_include` نیست).

**هرگز انجام نمی‌دهد:** `the_content` · `the_title` · `template_include` · `template_redirect` ·
`pre_get_posts` · `wp_head`/`wp_footer` · نوشتن در دیتابیس · `include` کردن فایل‌های قالب · دست‌زدن به تاکسونومی `singer`.

خاموشیِ کامل با یک خط در `wp-config.php`:
```php
define( 'JAVANSEDA_SIDE', false );
```
بازگردانیِ اختیاریِ ابزارک‌های غیرفعال (فقط اگر لازم شد): `define( 'JAVANSEDA_SIDEBAR_RECOVERY', true );` — پیش‌فرض **خاموش**.

## ۳) دو محافظِ تازه که علتِ «باکس هست، محتوا نیست» را ریشه‌کن می‌کند

1. **`inc/acf-compat.php` (تازه)** — `single.php` برای کاور/متن ترانه/لینک‌های دانلود مستقیم `get_field()` و `the_field()` را صدا می‌زند (۳۰+ فراخوانیِ بی‌محافظ). اگر افزونهٔ ACF غیرفعال/حذف شود، اولین فراخوانی = خطای کشنده، **دقیقاً وسط `<div class="single_content_box">`** ⇒ باکس خالی. این فایل وقتی ACF فعال باشد هیچ کاری نمی‌کند و وقتی نباشد، توابع را از روی `wp_postmeta` تأمین می‌کند تا صفحه نمیرد.
2. **`single.php` خط ۲۰–۲۲** — `include_once(ABSPATH.'wp-admin/includes/plugin.php')` و `is_plugin_active()` بدون هیچ محافظی بودند؛ اگر آن فایل خواندنی نبود یا تابع تعریف نشده بود، خطا **قبل از** بلوک محتوا می‌افتاد. اکنون هر دو با `function_exists()` / `is_readable()` محافظت شده‌اند.

## ۴) CSS ضدّبُردگی موبایل (بلوک ۲۱ + inline در هدر)

قالب در `@media` های زیر ۸۰۰px عرضِ **ثابت** می‌دهد (`350px`، `250px`…) در حالی که `body{overflow-x:hidden}` است؛ روی گوشی‌های ۳۲۰/۳۶۰ پیکسلی بخشی از بلوک بیرونِ برش می‌افتاد. اضافه شد (هم در `style.css` هم در CSS حیاتیِ inline تا کش بی‌اثرش نکند):
```css
@media (max-width: 800px){
	.tarlanweb_center{width:auto !important;max-width:100% !important;margin-right:12px;margin-left:12px;}
	.single_posts_right,.single_posts_left{width:100% !important;float:none !important;}
	#centers_box{width:100% !important;}
	.single_featured_image{max-width:100% !important;height:auto !important;}
	.single_content_box img,.single_content_box iframe{max-width:100% !important;height:auto;}
}
```
هدر موبایل بدون تغییر: رندرِ مشروط با `wp_is_mobile()` + کلاس `ab-has-mh` + `padding-top:65px`.

## ۵) فایل‌هایی که باید جایگزین شوند (drop-in)

| فایل | نقش | وضعیت |
|---|---|---|
| `wp-content/themes/javanseda/inc/ab-sidebars.php` | لایهٔ ایمنیِ **کمینه** (بازمهندسی‌شده) | جایگزینی — **ضروری** |
| `wp-content/themes/javanseda/inc/acf-compat.php` | سازگاری ACF (ضد خطای کشنده) | فایل تازه |
| `wp-content/themes/javanseda/single.php` | محافظ `is_plugin_active` + رندر امن سایدبار | جایگزینی |
| `wp-content/themes/javanseda/myfunctions.php` | بارگذاری `acf-compat.php` + include محافظت‌شده | جایگزینی |
| `wp-content/themes/javanseda/header.php` | CSS حیاتی inline + رندر مشروط هدر موبایل | جایگزینی |
| `wp-content/themes/javanseda/style.css` | `Version: 3.1.0` + بلوک ۲۱ (قاعدهٔ موبایل) | جایگزینی |
| `wp-content/themes/javanseda/ab-mobile-probe.php` | کاوشگرِ مستقل (فقط‌خواندنی) | فایل تازه — **اختیاری** |

`footer.php`، `page.php`، `home.php` و `inc/vip_slider.php` در این نسخه تغییری نکردند (هش‌شان در مانیفست برای بازرسی آمده است).

## ۶) بعد از آپلود (ترتیبِ الزامی)

1. فایل‌ها را جایگزین کنید (از FTP یا مدیر فایل).
2. **کش را پاک کنید:** پیشخوان ← تنظیمات کش (LiteSpeed / WP Rocket / …) ← Purge All، و اگر Cloudflare/ارزون/آپارات‌سی‌دی‌ان دارید ← Purge Cache.
3. موبایل ← Chrome ← سه‌نقطه ← «نسخهٔ دسکتاپ» را یک بار روشن/خاموش کنید تا کشِ استاتیکِ گوشی هم بشکند.
4. بازبینی: `https://ahangban.ir/<slug‑نوشته>/?ab_sidebars_diag=1`  ← اگر `ab-mobile-probe.php` را گذاشته‌اید، آدرس بهتر: `https://ahangban.ir/ab-mobile-probe.php?diag=1`
   - خط «نسخهٔ لایهٔ ایمنی» باید بنویسد `3.1.0 (کمینه) ✓`
   - خط «the_content» و «template_include» باید `✓ ندارد` باشد.
5. ابزارک: پیشخوان ← ظاهر ← ابزارک‌ها ← ناحیهٔ «سایدبار نوشته‌ها — بالا» را پر کنید. (مقادیر دیتابیس: `wp_options → sidebars_widgets`، کلید `left_side`.)
6. کاوشگرِ مستقل را **پاک کنید** (فایل `ab-mobile-probe.php`) — برای عموم قابل دسترسی است (هرچند فقط مدیر خروجی می‌بیند).

## ۷) اگر باز هم محتوا در گوشی نیامد

با همان گوشی این دو آدرس را باز کنید و **از خروجی اسکرین‌شات** بفرستید (لاگین مدیر لازم است):
```
https://ahangban.ir/ab-mobile-probe.php              ← بخش‌های ۱، ۴.۵، ۶، ۷، ۸
https://ahangban.ir/ab-mobile-probe.php?diag=1       ← وضعیت ابزارک/سایدبار/پلاگین‌ها
```
اگر `ab-mobile-probe.php` را نگذاشتید، همان صفحهٔ نوشته را با `?ab_nocache=1` روی گوشی باز کنید و در View source دنبال `single_content_box` بگردید:
- پیدا **نشد** ⇒ مشکل سمت PHP (فیلتر/پلاگین/ACF) — بخش ۳ دیاگ یا پیام خطا در `error_log` هاست را بفرستید.
- پیدا **شد** ولی در صفحه نیست ⇒ مشکل CSS/کشِ استاتیک (قاعده‌های بالا در ۴).

## ۸) سلامت فنی بسته

| آیتم | نتیجه |
|---|---|
| تجزیهٔ PHP (grammar) | PARSE OK — ۶ فایل |
| براکت‌های `style.css` | ۹۶۰ باز / ۹۶۰ بسته — متوازن |
| تعداد «gradient» در style.css | ۰ (پالت تخت: `#FFFFFF` / `#185ADB` / `#111111`) |
| بایت قبل از `get_header()` در single.php | صفر |
| سه مورد انتهایی `footer.php` | دست‌نخورده |
| شناسه‌های سایدبار | `left_side` / `left_side2` / `left_side1` (تغییرنیافته) |
| مانیفست هش | ۸ فایل، هم‌سان با `javanseda/` و `drop-in/` |
