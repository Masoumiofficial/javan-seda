# بایتِ سرعت — گزارش و اعمال بهینۀ قالب جوان‌صدا (v3.1.3)

> درخواست مالک: «قالب رو هم از بابت سرعت بررسی کن و بهینه‌اش کن.»
> این سند دو بخش دارد: **۱) چیزی که خودکار در قالب اعمال شد** (فایل `inc/ab-perf.php`)
> و **۲) چیزهایی که فقط با دسترسی هاست/پیشخوان شدنی است** و باید دستی انجام شوند.

---

## ۰) خط‌مبنا

لایۀ perf عمداً **هیچ کاری روی «محتوای نوشته» و «مسیر قالب» نمی‌کند** — چون در نسخۀ ۳.۰.۵
فیلتر روی `the_content` باعث شد باکس مطلب در موبایل خالی شود (علتِ قطعیِ باگ، بعداً حذف شد).
همچنین `functions.php` قالب با ionCube رمزنگاری است و هیچ enqueue‌ای در فایل‌های قابل‌ویرایش
قالب وجود ندارد ⇒ تنها راهِ مجاز، **فیلترهای استاندارد وردپرس** است، نه دست‌زدن به سورس رمزنگاری‌شده.

## ۱) آنچه در v3.1.3 اعمال شد — `inc/ab-perf.php`

| # | بهینۀ اعمال‌شده | هوک/فیلتر | سود تقریبی |
|---|------------------|-----------|-------------|
| ۱ | `defer` روی اسکریپت‌های غیرضروری (owl.carousel، scripts.js، icofont، comment-reply …) | `script_loader_tag` (اولویت ۲۰) | حذف رندر‑بلاکینگ JS (معمولاً −۰٫۲ تا −۰٫۸ ثانیه FCP در موبایل) |
| ۲ | حذف بارِ بی‌فایدهٔ `<head>`: generator، shortlink، RSD، wlwmanifest، oEmbed discovery، feed‑links اضافه، اسکریپت/استایل ایموجی | `init` (اولویت ۹۹) | ۳ تا ۶ ریکوئست کمتر |
| ۳ | `preload` برای `fonts/vazir-fd.woff` و `fonts/icofont.woff` با `crossorigin` | `wp_head` (اولویت ۱) | فونت‌ها ۱ ریکوئست زودتر شروع می‌شوند (متنِ LCP سریع‌تر دیده می‌شود) |
| ۴ | تزریق `font-display: swap` برای ۵ `@font-face` قالب (Vazir ×۴ وزن + IcoFont) | `<style id="ab-perf-fonts">` در `wp_head` | حذف «متن نامرئی» تا ۳ ثانیه؛ LCP و CLS بهتر |
| ۵ | `loading="lazy"` + `decoding="async"` + `width/height` روی `<img>`های بی‌بعد در متن/چکیده | `the_content` / `the_excerpt` (اولویت ۵۰، فقط افزودن صفت) | ↓ بایتِ اولیه و ↓ CLS |
| ۶ | گزارشِ «بایتِ سرعت» (اندازۀ فایل‌ها، صف JS/CSS، وضعیت OPcache/gzip، srcset، پیشنهادها) | تابع `javan_seda_perf_report()` | اندازه‌گیری قبل/بعد، بدون حدس‌زدن |

**خاموش‌کردن کل لایه (در `wp-config.php`، قبل از خط `That's all, stop editing!`):**

```php
define( 'JAVANSEDA_PERF', false );          // همه‌چیز آف
```

و ریز‌تر:

```php
define( 'JAVANSEDA_PERF_DEFER', false );        // فقط defer
define( 'JAVANSEDA_PERF_PRELOAD_FONTS', false );// فقط preload فونت
define( 'JAVANSEDA_PERF_STRIP_HEAD', false );   // فقط پاک‌سازی <head>
define( 'JAVANSEDA_PERF_LAZY_IMG', false );     // فقط lazy تصاویر
define( 'JAVANSEDA_PERF_IMG_DIMS', true );      // + افزودن width/height حدسی (پیش‌فرض خاموش: آیکن‌های کوچک بزرگ می‌شوند)
define( 'JAVANSEDA_PERF_KEEP_EMBED', true );    // اگر پست‌های دیگر را با oEmbed جاسازی می‌کنید
define( 'JAVANSEDA_PERF_KEEP_EMOJI', true );    // اگر ایموجی‌های رنگی وردپرس را می‌خواهید
```

اگر اسکریپتی بعد از defer به‌هم ریخت (مثلاً افزونه‌ای که قبل از DOM‑ready اجرا می‌شود)،
همان handle را از defer مستثنا کنید:

```php
// در myfunctions.php یا افزونۀ code-snippets:
add_filter( 'javanseda_perf_skip_defer', function ( $a ) { $a[] = 'my-slider-js'; return $a; } );
```

مستثناهای از پیش فعال: `jquery`، `jquery-core`، `jquery-migrate`، `modernizr`، `html5shiv`،
`mediaqueries` (این سه آخر نباید defer شوند چون برای IE/ساختار HTML هستند).

## ۲) یافته‌های قطعی روی هاست/سورس (از بررسی همین نسخه)

| مورد | عدد | توضیح |
|------|-----|-------|
| `style.css` قالب | **۱۰۷KB**، مینیفای‌نشده | ۲۲ بلوک توضیحیِ فارسی دارد؛ خودِ CSS ~۸۵KB است.کشِ طولانی‌مدت (بخش ۳/۲) مهم‌ترین درمان است. |
| `css/icofont.min.css` | **۲۳KB** | صدها قاعدۀ glyph دارد، ولی در کل phpهای قالب فقط ~۲۵ آیکن واقعی استفاده می‌شود (`microphone-alt`, `music-notes`, `facebook`, `instagram`, `paper-plane`, `twitter`, `youtube-play`, `movie`, `search`, `caret-down`, `thin-up`, …). ساختن نسخۀ «گزینش‌شده» ⇒ CSS به ~۳KB و فونت به <۲۰KB می‌رسد. |
| `fonts/` | **۲۹۸KB، فقط `.woff`** | `icofont.woff` ۱۰۲KB، `vazir-fd` ۴۴KB، `vazir-bold-fd` ۴۷KB، `vazir-medium-fd` ۴۷KB، `vazir-light-fd` ۵۱KB. تبدیل به **woff2** ≈ −۳۰٪؛ حذف وزن ۳۰۰/۵۰۰ (اگر در CSS استفاده نشود) ≈ −۱۰۰KB. |
| `js/` | `jquery.js` ۸۹KB، `owl.carousel.js` ۹۳KB، `mediaqueries.js` ۱۵KB، `html5shiv.js` ۱۱KB، `scripts.js` | `js/jquery.js` اختصاصی است؛ اگر هم‌زمان با jQuery هستۀ وردپرس لود شود، **۸۹KB دوباره‌کاری**. بخش «jQuery در صف» کاوشگر را ببینید. |
**دو فایل بی‌مصرف که هنوز دانلود می‌شوند:** `js/mediaqueries.js` (۱۵KB) و `js/html5shiv.js` (۱۱KB) — هر دو فقط برای IE9↔‌اند و در هیچ‌کدام از فایل‌های قابل‌ویرایشِ قالب ارجاعی ندارند (لودشان داخل `functions.php`ِ رمزنگاری است). اگر خواستید حذفشان کنید، به‌جای پاک‌کردن فایل روی هاست، در `myfunctions.php` بگذارید:

```php
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_script( 'mediaqueries' ); // اگر handle فرق داشت، نام دقیق را از تب Network بردارید
	wp_dequeue_script( 'html5shiv' );
}, 99 );
```

**دو نکتهٔ مهمِ دیگر:**

- `js/jquery.js` (۸۹KB) نسخۀ اختصاصی است؛ اگر هم‌زمان با jQuery هستۀ وردپرس لود شود، ۸۹KB دوباره‌کاری است. کاوشگر (بخش ۱۰) می‌گوید jQuery چند بار در صف است.
- تابع `meks_disable_srcset` در `myfunctions.php` فیلتر `wp_calculate_image_srcset` را **کلاً خاموش** می‌کند ⇒ موبایل هم همان فایل بزرگ تصویر را می‌گیرد (~۳۰٪ بایتِ تصویر اضافه). اگر چیدمان به‌هم نریخت، حذف آن دو خط سود زیادی دارد؛ ما عمداً دست نزدیم چون رفتار طراحی قالب را عوض می‌کند.
- `functions.php` قالب با ionCube رمزنگاری است ⇒ هیچ `wp_enqueue_*` درونش قابل ویرایش نیست؛ فیلترهای استاندارد وردپرس (کاری که `inc/ab-perf.php` می‌کند) تنها راهِ امن‌اند.

## ۳) کاری که باید روی هاست بکنید (به ترتیبِ سود/هزینه)

**۱) کشِ صفحه (بزرگ‌ترین برنده).** یک افزونۀ معتبر: LiteSpeed Cache (اگر وب‌سرور LiteSpeed است) یا WP Super Cache / WP Fastest Cache.
تنظیمات لازم:
- «موبایل: یکی‌سازیِ کش» (Serve same cache for mobile) یا «کش موبایل: خاموش» — تا نسخۀ کهنۀ موبایل سرو نشود؛
- Purge پس از هر بار اعمال فایل قالب؛
- Cache-Control برای `/wp-content/themes/*` روی **۱ هفته** (همین که نبودۀ `style.css` روی هاست نسخهٔ قدیمی نشان می‌داد، نشانهٔ کشِ بلندِ اشتباه یا اعمال‌نشدن فایل است).

**۲) فشرده‌سازی و CDN.** brotli (اگر سرور پشتیبانی می‌کند) یا gzip سطح ۶؛ در ابرآروان/کلادفلیر «Optimize CSS/JS» را روشن کنید — با لایۀ perf ما تداخل ندارد، چون ما فقط defer/preload می‌کنیم و فایل را دست نمی‌زنیم.

**۳) OPcache.** اگر غیرفعال است فعالش کنید: `opcache.enable=1`, `validate_timestamps=1`, `revalidate_freq=60`. کاوشگر، بخش ۱۰، وضعیتش را نشان می‌دهد.

**۴) تصاویر.** سایزهای قالب: `thumb4 150²` `thumb3 60²` `thumb2 190²` `thumb1 640×360` (crop). در فهرست‌ها هیچ‌وقت تصویر full چاپ نشود؛ خروجی WebP از افزونۀ تصویر (−۴۰٪). برای اسلایدر ۱۶:۹، همان ۶۴۰×۳۶۰ کافی است.

**۵) حذفِ فایل‌های مرده** (فقط از روی هاست، اگر مطمئن شدید): `js/mediaqueries.js`، `js/html5shiv.js`. `du -sh` روی `wp-content/themes/javanseda/` قبل/بعد را ثبت کنید.

**۶) دیتابیس و cron.** `wp_options` بزرگ (مخصوصاً ردیف‌های `mod_rewrite_rules`, transientها) را پاک‌سازی کنید و cron را روی `wp-cron.php?doing_wp_cron` یا system-cron بگذارید — در shared hostingِ ایرانی معمولاً ۲۰۰–۵۰۰ms از TTFB کم می‌کند.

## ۴) چه چیزی **نکردیم** و چرا

- **مینیفای/کنکیت CSS یا JS** — چون افزونۀ بهینه‌ساز یا CDN این کار را بهتر و با hash انجام می‌دهد؛ نسخه‌دو‌شدنِ فایل‌ها در قالبِ رمزنگاری‌شده ریسکِ شکستنِ ارجاع دارد.
- **defer/async روی `style.css`** — خطر FOUC (صفحهٔ بدون استایل). به‌جایش CSS حیاتیِ هدر را `inline` در `<head>` گذاشته‌ایم (`ab-header-critical`).
- **کشِ HTML دستی با `ob_start`** — روی بعضی هاست‌ها با لاگین/سبد‌خرید/کوکی تداخل می‌کند؛ کش‌افزونه همان کار را امن می‌کند.
- **Inline کردن SVG به‌جای فونت آیکن** — تغییرِ markup در فایل‌های رمزنگاری‌شده لازم می‌کرد.
- **Dequeue کردن `owl.carousel.js`** — اسلایدر VIP و «هنرمندان» به آن وابسته‌اند؛ فقط defer می‌شود.

## ۵) راستی‌آزمایی (قبل/بعد)

1. `https://ahangban.ir/ab-mobile-probe.php?html=1` → **بخش ۱۰** (بایتِ سرعت). فایل کاوشگر را بعد از بررسی از هاست پاک کنید.
2. PageSpeed Insights روی همان URL (موبایل). شاخص‌های مورد انتظار پس از این دور:
   - «Eliminate render‑blocking resources» ⇒ باید برای JSها سبز/کمرنگ شود؛
   - «Ensure text remains visible during webfont load» ⇒ سبز (font-display swap);
   - «Properly size images» ⇒ تا زمانی که srcset خاموش است، نارنجی می‌ماند (بخش ۲/۳).
3. DevTools (دسکتاپ) → Network → «Disable cache» → F5: ترتیب درخواست‌ها؛ `defer`ها باید `Priority: Low` یا بعد از DOM بیایند.
4. اگر بعد از نصب، اسلایدر یا جستجوی AJAX از کار افتاد: `define('JAVANSEDA_PERF_DEFER', false);` و تست مجدد — اگر درست شد، handle متخاصم را به `javanseda_perf_skip_defer` اضافه کنید (به‌جای خاموش‌کردن کل defer).

## ۶) چک‌لیست نصب این دور

- [ ] `header.php` (بازنویسی v3.1.3 — بدون `wp_is_mobile()`) **یا** `patches/header.original-patched.php` (فقط اگر هدر هاست اصلی است) — یکی، نه هر دو
- [ ] `style.css` (Version 3.1.3، بلوک ۲۱ viewport-driven + بلوک ۲۲)
- [ ] `inc/ab-news-ticker.php` (دستۀ اخبار + حالت سخت‌گیر)
- [ ] `inc/ab-perf.php` + بلوک `require` در `myfunctions.php`
- [ ] `ab-mobile-probe.php` (بخش ۴.۱ اخبار + بخش ۱۰ بهینگی) → بعد از بررسی **پاک کنید**
- [ ] Purge All (افزونۀ کش) + Purge CDN، بعد `?v=` را در `/style.css?x=1` چک کنید که نسخهٔ 3.1.3 دیده شود
