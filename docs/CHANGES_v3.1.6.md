# نقشۀ تغییرات — نسخهٔ 3.1.6 (دور ۱۲.۱ · hotfix «خطای مهم سایت»)

مخزن: `Masoumiofficial/javan-seda` · PR `#3` · تگ `v3.1.6` · بعد از `v3.1.5`
این دور فقط **ایمنی** است: هیچ قابلیت جدیدی اضافه نشده و هیچ ظاهری عوض نشده.

---

## ۱) علامتی که گزارش کردید

صفحۀ «یک خطای مهم در این وب سایت رخ داده است» = `Fatal error` در PHP.
آنچه از Console مرورگر فرستادید (`MaxListenersExceededWarning` ، `contentscript.js` ، `MozBar` ، `ObjectMultiplex - orphaned data`) **مربوط به افزونۀ خودِ مرورگر است** (متامسک / MozBar) و به سایت ربطی ندارد؛ در عیب‌یابیِ PHP کمکی نمی‌کند. خطای واقعی فقط در **ایمیل «Recovery Mode» وردپرس**، `wp-content/debug.log` یا لاگ خطای هاست نوشته می‌شود.

## ۲) سه نقص واقعی که در کد خودِ ما پیدا و برطرف شد

| # | فایل | مشکل | عاقبت روی هاست |
|---|------|------|----------------|
| ۱ | `inc/ab-perf.php` خط ۱۹۸ | `preg_replace_callback( …, **static function** ( $m ) { … } )` — «بستۀ static» از **PHP 7.4** به بعد وجود دارد؛ خودِ قالب هیچ‌جا این ساختار را ندارد | روی PHP 7.0–7.3 **خطای پارس** ⇒ کل سایت (و پیشخوان) می‌میرد؛ هیچ `define()` هم نجات نمی‌دهد، چون خطا زمان پارس است نه زمان اجرا |
| ۲ | `inc/ab-seo.php` خطوط ۲۷۰ / ۲۹۶ / ۳۰۳ | `(string) get_term_link( … )` و `(string) get_category_link( … )` — هر دو می‌توانند `WP_Error` بدهند (و شما ترم‌های دوبل/شکسته دارید) | روی **PHP 8** تبدیل `WP_Error` به رشته یک `Error` کشنده است؛ روی PHP 7 هم رشتهٔ `Object` داخل `canonical`/breadcrumb چاپ می‌شد |
| ۳ | `myfunctions.php` | هیچ محافظتی در برابر «فایل لایه‌ای که برای PHPِ این هاست تازه‌تر است» وجود نداشت | یک کاراکتر غلط در یک فایل لایه = کل سایت پایین |

### اصلاحات

- **۱)** `static` حذف شد ⇒ بستهٔ معمولی، سازگار با همهٔ نسخه‌ها.
- **۲)** تابع کمکی `javan_seda_seo_link( $term, $taxonomy = '' )` اضافه شد: `get_term_link()` را صدا می‌کند، `is_wp_error()` را چک می‌کند و در بدترین حالت `''` می‌دهد. هر سه فراخوانی به آن تبدیل شد (`get_category_link()` در هسته دقیقاً همان `get_term_link()` است، پس او هم خطر داشت).
- **۳) دروازۀ سازگاری** در ابتدای `myfunctions.php`:
  ```php
  if ( is_readable( $f ) && javan_seda_layer_loadable( $f ) ) { require_once $f; }
  ```
  `javan_seda_layer_loadable()` روی PHP 7.4+ **هیچ کاری نمی‌کند** (شرط اول کوتاه می‌آید ⇒ صفر هزینه)، ولی روی PHPهای قدیمی‌تر محتوای فایل لایه را سریع می‌خواند و اگر `static function` / `fn()` / `??=` / `?->` / `??` داشت، **همان لایه را خاموش می‌کند به‌جای این‌که سایت بمیرد** (با `WP_DEBUG` در لاگ هم می‌نویسد). نتیجه: از این پس یک اشتباۀ نسخه‌ای در یک لایه حداکثر یعنی «آن قابلیت کار نمی‌کند»، نه «سایت پایین است».

## ۳) ابزار نجاتِ تازه: `ab-rescue.php` (مستقل، read-only)

کاوشگر قبلی (`ab-mobile-probe.php`) از `wp-load.php` استفاده می‌کند ⇒ وقتی سایت fatal می‌دهد **خودش هم باز نمی‌شود**. `ab-rescue.php` هیچ چیزی از وردپرس load نمی‌کند و فقط همین پوشه را می‌خواند، پس در حالت «سایت خوابیده» هم کار می‌کند:

```
https://example.com/wp-content/themes/javanseda/ab-rescue.php
https://example.com/wp-content/themes/javanseda/ab-rescue.php?scan=1     ← خودِ خط‌های مشکوک را هم نشان می‌دهد
```

چه می‌گوید: نسخهٔ PHP · `SERVER_SOFTWARE` · `display_errors`/`memory_limit` · وضعیت **ionCube Loader** · خط `ICB0`ِ سرآمدِ `functions.php` (که اعلام می‌کند این قالبِ رمزنگاری‌شده با PHPهای ۷٫۲ / ۷٫۴ / ۸٫۱ کار می‌کند) · و برای هر ۱۳ فایل لایه: اندازه، تاریخِ تغییر و **ساختارهای تازه‌تر از PHPِ جاری** با شمارۀ خط.
نکته: `?>` ندارد، چیزی از دیتابیس نمی‌خواند و هیچ فایل را تغییر نمی‌دهد؛ فقط `text/plain` چاپ می‌کند.

## ۴) اگر همین الان سایت پایین است (به همین ترتیب)

1. `…/wp-content/themes/javanseda/ab-rescue.php` را باز کنید. اگر روی فایلی ⚠ دیدید، همان را در File Manager هاست **تغییر نام** دهید: `inc/ab-perf.php` → `inc/ab-perf.php.off` (اگر ⚠ نبود، یعنی خطا جای دیگری است و نوبت قدم ۳ است).
2. سایت باید بالا بیاید؛ بعد فایل‌های v3.1.6 را بگذارید و نام‌ها را برگردانید.
3. اگر هیچ ⚠ نبود، در `wp-config.php` بگذارید:
   ```php
   define( 'WP_DEBUG', true ); define( 'WP_DEBUG_LOG', true ); define( 'WP_DEBUG_DISPLAY', false );
   ```
   و ۱۰ خط آخر `wp-content/debug.log` را بفرستید — یا همان متنی که وردپرس در ایمیل «Recovery Mode» فرستاده (بخش «موقعیت:» و «پیام:» دقیقاً فایل و خط را می‌گوید).
4. اگر پیام `Incompatible file format` یا `the file … is corrupted` دیدید ⇒ PHP میزبان باید روی همان نسخه‌ای باشد که `functions.php` با آن رمزنگاری شده (معمولاً ۷٫۴ یا ۸٫۱)؛ این مورد ربطی به لایه‌های ما ندارد.

## ۵) فایل‌های این دور (۴ فایل لازم، بقیه اختیاری)

پایه: `wp-content/themes/javanseda/` · لینک خام: `https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.1.6/drop-in/<مسیر>`

| فایل | بایت | sha256 (۱۶) | وضعیت |
|---|---|---|---|
| `myfunctions.php` | 28574 | `5a9deee1e76b006c` | **لازم** — دروازۀ سازگاری + بارگذاری لایه‌های 3.1.5 |
| `inc/ab-perf.php` | 19589 | `a04053e34aad2ba3` | **لازم** — حذف `static` |
| `inc/ab-seo.php` | 22972 | `6fead0b0d3bdcaaf` | **لازم** — محافظت `WP_Error` |
| `style.css` | 117069 | `3057dfeeb15a1217` | **لازم** — `Version: 3.1.6` برای cache-bust |
| `ab-rescue.php` | 10003 | `e58a813c5d4851c5` | پیشنهادی — ابزار نجات |
| `ab-mobile-probe.php` | 55273 | `407c738520c85967` | اختیاری — بخش ۱۳ (وضعیت لایه‌ها) |
| `inc/ab-sidebars.php` | 22805 | `7682fc0c1758411f` | اختیاری — مانیفست ۱۹ فایل |
| بقیۀ `drop-in/` (۱۷ فایل) | — | — | بدون تغییر از v3.1.5 |

زیپ‌ها: `artifacts/drop-in-v3.1.6.zip` · `artifacts/javanseda-v3.1.6-theme.zip` · `artifacts/rollback-v3.1.6.zip` · `artifacts/drop-in-explained-v3.1.6.zip`.

## ۶) سلامت فنی

- پارسر (`node php-parser`) روی فایل‌های تغییریافته **OK**: `myfunctions.php` (۴۳ نود) · `inc/ab-perf.php` · `inc/ab-seo.php` · `ab-mobile-probe.php` (۱۷۵) · `ab-rescue.php` (۳۴) · `inc/ab-sidebars.php`.
- اسکریپت جدید `tools/compat-check.py` (فقط dev، داخل قالب نیست): کل `javanseda/` را بعد از خنثی‌کردن رشته‌ها/کامنت‌ها می‌گردد ⇒ **۰ مورد**. (همین ابزار بود که `static function` را پیدا کرد.)
- آزمون دروازۀ تازه روی هر ۷ فایل لایه: هیچ‌کدام ساختار ۷٫۰+/۷٫۴+ ندارند ⇒ روی PHP قدیمی هم اشتباهاً خاموش نمی‌شوند.
- `style.css`: براکت **۱۰۸۳/۱۰۸۳** · `gradient` **۰** · پالت `#FFFFFF / #185ADB / #111111` · بدون متن بیرون از کامنت.
- قاعدۀ پروژه رعایت شد: بدون فیلتر روی `the_content`/`template_include` · بدون دست‌زدن به تاکسونومی یا هوک سراسری · صفر بایت قبل از `get_header()` · حفظ سه مورد انتهایی footer · هدر موبایل رندر می‌شود و فقط با `@media (max-width:800px)` مخفی می‌شود.

## ۷) بازگردانی

`artifacts/rollback-v3.1.6.zip` نسخهٔ v3.1.5 را برای `myfunctions.php` · `inc/ab-perf.php` · `inc/ab-seo.php` · `style.css` · `inc/ab-sidebars.php` · `ab-mobile-probe.php` و همچنین `drop-in-v3.1.5.zip` دارد.
برای خاموش‌کردنِ موقت یک لایه کافی است همان فایل را `.off` کنید — چون `require`ها با `is_readable()` محافظت شده‌اند، نبودِ فایل هیچ خطایی نمی‌دهد. `ab-rescue.php` را هم می‌توانید بی‌ضرر حذف کنید.
