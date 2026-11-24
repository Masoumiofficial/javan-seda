# v3.5.1 — فرار از «سفیدشدنِ کاملِ سایت» بعد از جایگزینیِ پوشهٔ پوسته

تاریخ: ۲۰۲۶-۱۰-۲۰ · پیش‌نیاز: هر ۳.۴.x یا ۳.۵.۰

## خطای واقعی (از debug.log کاربر)
```
PHP Fatal error: Uncaught Error: Call to undefined function ot_get_option()
in …/wp-content/themes/javanseda/header.php:27
```
وردپرس از هر پوسته **فقط `functions.php`** را لود می‌کند. در این قالب، `functions.php`
رمزگذاری‌شده است و خودش OptionTree (`ot_get_option`)، `myfunctions.php` و بقیهٔ لایه‌ها را
می‌آورد. اگر پوشهٔ پوسته با «آپلود پوسته → جایگزینی» در پیشخوان عوض شود، وردپرس پوشهٔ قبلی را
پاک می‌کند؛ چون بستهٔ ۳.۵.۰ عمداً `functions.php` و `RTL_License_41d6c5f704e3a746.php` را
ندارد، آن دو **کاملاً حذف** می‌شوند ⇒ هیچ کدِ پوسته لود نمی‌شود ⇒ هر صفحه سفید.

## تازه‌ها
1. **`javanseda/emergency/functions.rescue.php`** — فایلِ نجات: تغییر نام به `functions.php`
   ⇒ OptionTree + `theme-options.php` + `myfunctions.php` لود می‌شوند، سایت بالا می‌آید،
   و یک هشدارِ قرمز در پیشخوان یادآوری می‌کند که فایلِ اصلی برگردد. (اگر OptionTree نبود،
   یک `ot_get_option()` حداقلی از `option_tree` می‌سازد تا قالب سفید نیفتد.)
2. **`javanseda/emergency/README.txt`** — همان دستور در دو خط.
3. **`README-INSTALL-3.5.1.txt`** — بالای متن، هشدارِ ⛔: این zip را در پیشخوان آپلود نکنید؛
   فقط با File Manager/FTP روی پوشهٔ موجود Extract کنید.
4. **پروب** — اگر `functions.php` یا فایلِ لایسنس نبود، حالا صریح می‌نویسد که همین علتِ
   `ot_get_option() undefined` است و راهِ رفع را چاپ می‌کند.
5. `style.css` → `Version: 3.5.1`.

## چه چیزی عوض **نشد**
هیچ فایلِ قالب/لایه تغییر نکرد؛ این نسخه فقط ابزارِ نجات + مستندسازی + نسخه است.

## بسته‌ها
- `javanseda-v3.5.1-theme.zip` — کل کدِ پوسته بدونِ فایل‌های `ICB0` (assert شده)
- `drop-in-v3.5.1.zip` — فایل‌های تغییریافته + README
- `rollback-v3.5.1.zip` — همان فایل‌ها با نسخهٔ ۳.۴.۱۲
- `rescue-v3.5.1.zip` — فقط فایلِ نجات + README (برای حالتی که سایت همین حالا سفید است)
