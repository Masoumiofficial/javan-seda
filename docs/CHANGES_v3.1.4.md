# نقشۀ تغییرات — نسخهٔ 3.1.4 (دور ۱۱ · «لایۀ دیده‌شدن»)

مخزن: `Masoumiofficial/javan-seda` · PR `#3` · تگ `v3.1.4` · preceded by `v3.1.3` (هدر/اخبار/perf)
مبنای این دور: `docs/AUDIT_2026-10.md` (آنالیز زندۀ سایت) — جدول «وضعیت اجرا» در انتهای همان سند است.

---

## ۱) چه چیزی از آنالیز رفع شد

| کمبود (شمارهٔ بند AUDIT) | وضعیت در 3.1.4 |
|---------------------------|------------------|
| نبودِ `meta description` در کل سایت | ✅ خودکار، بدون افزونه |
| نبودِ `og:` / `twitter:` ⇒ اشتراکِ لنگ در تلگرام/اینستا | ✅ `og:title/description/url/image(± ابعاد)/updated_time/type` + `twitter:card` + `og:audio` |
| نبودِ `BreadcrumbList` | ✅ JSON-LD از دسته + `singer` + عنوان |
| `/page/2/` و… = کپیِ صفحهٔ اول (HTTP 200) | ✅ واگذاری به لوپ استاندارد + `noindex,follow` موقت |
| عنوانِ تکراری در صفحات شماره‌دار | ✅ افزودن «صفحۀ N» به title |
| 404 با ریدایرکت خودکار (soft-404) + JS معیوب | ✅ صفحۀ ۴۰۴ِ تازه با جست‌وجو، ۴ مقصد، ۶ آهنگ تازه — بدون هیچ script |
| `printf(the_search_query())` در `search.php` | ✅ `esc_html( get_search_query() )` + شمارش نتایج |
| لینک‌های فایل بدون `esc_url` (۱۱ مورد) | ✅ `single.php` (۸) + `inc/tarlanweb_player.php` + `inc/songs_post.php` (با `esc_html` روی `the_field`) |
| `if($rkian = 1)` (انتساب به‌جای مقایسه ⇒ پلیرِ خالی هم چاپ می‌شد) | ✅ `$rkian > 1` |
| `rel="nofollow"` روی لینک داخلی + سایز تصویر با `wp_is_mobile()` | ✅ حذف nofollow / یک سایز `thumb2` برای همه + `loading=lazy` `decoding=async` |
| تگ‌های نازک (۱–۲ نوشته) به‌عنوان محتوای تکراری | ✅ `noindex,follow` خودکار (آستانه با فیلتر `javanseda_seo_thin_tag_min` قابل تغییر) |
| آرشیو نویسنده/تاریخِ بی‌محتوا | ✅ `noindex,follow` |

**باقی‌مانده (دستی یا دور بعد):** sitemap و پرچمِ `www/http` (با `wp search-replace`) · شمارشگر بازدید + امتیاز + چارت (دور ۱۲) · گزارش لینک خراب و DMCA (دور ۱۳) · فیلد `duration` برای غنی‌ترکردن schema · پرکردن جایگاه تبلیغ · اصلاح آدرس کانال تلگرام/اینستا از تنظیمات قالب · ادغام اصطلاحات دوبلِ `singer`.

---

## ۲) فایل تازهٔ اصلی — `inc/ab-seo.php` (22,290 بایت)

- **توضیح متا:** از `_javan_seda_description` (اگر دستی بنویسید) ← چکیده ← اولین پاراگراف تمیزِ محتوا؛ اگر کم بود جملهٔ استاندارد «دانلود آهنگ {song} از {artist} با کیفیت ۱۲۸ و ۳۲۰ + متن ترانه و پخش آنلاین». طول پیش‌فرض ۱۵۵ کاراکتر (`JAVANSEDA_SEO_DESC`)، بدون برچسب/کدکوتاه/ناهمتی.
- **برای آرشیوها:** توضیحِ دسته/برچسب، و برای `singer` جمله‌ای با تعداد آثارِ همان صفحه.
- **Open Graph/Twitter:** تصویر از تصویر شاخص با سایز `thumb1` (قابل تغییر با `JAVANSEDA_SEO_IMAGE`؛ مثلاً `'full'` یا سایز ۱۲۰۰×۶۳۰ که بسازید) و fallback به `images/nomusics.jpg` تا اشتراک بدون تصویر نماند. ابعاد هم چاپ می‌شود (`og:image:width/height`).
- **`og:audio`:** اگر فیلد `music128` با `https://` شروع شود اضافه می‌شود (تا لینکِ قابل‌پخش در شبکه از دست نرود؛ `http://` عمداً رد می‌شود تا mixed-content ندهد).
- **Canonical backstop:** اگر canonical توسط هسته/افزونه چاپ نشده باشد، خودمان می‌گذاریم (و اگر بود، دوباره چاپ نمی‌کنیم ⇒ هیچ تازدوجی نیست).
* «صفحۀ N» در title: از کلید `page` استفاده می‌شود (کلیدهای ناشناخته در عنوان چاپ نمی‌شوند) و اگر هستۀ وردپرس خودش `page` را پر کرده باشد، دوباره‌کاری نمی‌کنیم.
- **`wp_robots`:** فقط `noindex` + همیشه `follow` ⇒ اعتبار لینک‌ها حفظ می‌شود. صفحه‌های هدف: خانۀ شماره‌دار، تگ نازک، آرشیو نویسنده، بایگانی تاریخ.
- **گزارش نظارت:** `javan_seda_seo_report()` ⇒ در **بخش ۱۱ کاوشگر** چاپ می‌شود: شناسایی افزونۀ سئو، تست زندهٔ سه مسیر sitemap، تست خط `Sitemap:` داخل robots.txt، شمردن `http://www.` و `og:`/`description`/`canonical` در HTMLِ زندۀ صفحهٔ اصلی، و شمردن لینک‌های عمومی تلگرام/اینستا.

**خاموش‌کردن (در `wp-config.php` قبل از «That's all»):**
```php
define( 'JAVANSEDA_SEO', false );        // کل لایه
define( 'JAVANSEDA_SEO_OG', false );     // فقط og/twitter
define( 'JAVANSEDA_SEO_ROBOTS', false );  // فقط noindex‌ها (اگر افزونۀ سئو خودش انجام می‌دهد)
define( 'JAVANSEDA_SEO_CRUMBS', false );  // فقط BreadcrumbList
define( 'JAVANSEDA_SEO_DESC', 170 );      // طول توضیح
define( 'JAVANSEDA_SEO_IMAGE', 'full' );  // سایز تصویر اشتراک
```
> اگر افزونۀ سئو (Rank Math/Yoast) را فعال کردید، **`JAVANSEDA_SEO_OG` و `JAVANSEDA_SEO_ROBOTS` را خاموش کنید** تا دوتا نشود؛ توضیحِ متا و breadcrumb را می‌شود با همان لایه نگه داشت (افزونه‌ها معمولاً override می‌کنند، ولی چک کردن ضرر ندارد).

**توضیحِ دستیِ یک آهنگ (اختیاری):** در ویرایشگر، Custom Fields → کلید `_javan_seda_description` → متن. اولویت بر خودکار دارد.

---

## ۳) فایل‌ها (drop-in) + امضا — ۲۰ فایل (۵ فایل تازه)

پایۀ مقصد: `wp-content/themes/javanseda/` · لینک مستقیم:
`https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.1.4/drop-in/<مسیر>`

| فایل | بایت | sha256 (۱۶) |
|------|------|--------------|
| `inc/ab-seo.php` **(تازه)** | 22290 | `eee7aa3965f206bb` |
| `404.php` **(تازه در drop-in)** | 2594 | `d1be37361a792991` |
| `search.php` **(تازه در drop-in)** | 1115 | `d668da0b149b312c` |
| `inc/songs_post.php` **(تازه در drop-in)** | 1529 | `96e75daba47f8a1b` |
| `inc/tarlanweb_player.php` **(تازه در drop-in)** | 3277 | `6764721f6a569baa` |
| `style.css` | 111224 | `683eda33f0b5b532` |
| `myfunctions.php` | 25542 | `ed86a1fd96ff9436` |
| `home.php` | 3761 | `6bf766679e28f87c` |
| `single.php` | 34408 | `537d6c81f0266f7d` |
| `ab-mobile-probe.php` | 51491 | `1dd020eadd7bd83c` |
| `inc/ab-sidebars.php` | 22401 | `6a5264336ad6bbd6` |
| `inc/ab-perf.php` | 19439 | `d79b274b181e2011` |
| `inc/ab-news-ticker.php` | 8804 | `b9eebd2bc03497b9` |
| `header.php` | 4859 | `9807921341cccde4` (بدون تغییر از 3.1.3) |
| `patches/header.original-patched.php` | 3991 | `7a540791f21526aa` (بدون تغییر) |
| `patches/README.txt` | 4758 | `951c403e4839f80c` |
| `page.php` · `footer.php` · `inc/acf-compat.php` · `inc/vip_slider.php` | 1347 · 7301 · 6402 · 2890 | بدون تغییر |

**نصب کمینه (مسیر الف — همان روشی که بار قبل جواب داد):**
1. `inc/ab-seo.php` (تازه) + `myfunctions.php` (بلوک require اضافه شده)
2. `style.css` (بلوک ۲۳ + `Version: 3.1.4`)
3. `404.php` و `search.php` (اگر می‌خواهید آن دو صفحه هم درست شود)
4. `home.php` (رفع صفحۀ تکراری) — اگر `home.php` هاستتان دست‌کاری شده، فقط همین ۸ سطر اولش را اضافه کنید:

```php
if ( get_query_var( 'paged' ) ) {
	locate_template( array( 'index.php' ), true, true );
	return;
}
```
5. Purge All (افزونۀ کش) + Purge CDN

`single.php` / `inc/songs_post.php` / `inc/tarlanweb_player.php` **اختیاری‌اند** (امنیتِ escape و باگِ پلیر) — اگر نمی‌خواهید فایل‌هایتان عوض شود، بی‌خطر رها می‌شوند؛ هیچ وابستگی‌ای بینشان نیست.

---

## ۴) بلوک ۲۳ `style.css` (فقط صفحۀ ۴۰۴ و شمارش جست‌وجو)

`.ab-404-search` (فیلد + دکمۀ `#185ADB`) · `.ab-404-links` (قرص‌های دورگرد با حاشیۀ `var(--ab-line)`) · `.ab-404-art` (تصویر ۶۲۶×۴۹۶ با `max-width:100%`) · `.ab-search-count` — با حالت `.night` و `@media (max-width:800px)` (فرم ستونی می‌شود).
هیچ گرادینتی نیست (`gradient=0`)، براکت‌ها متوازن (۱۰۳۰/۱۰۳۰)، و پالت همان `#FFFFFF / #185ADB / #111111`.

## ۵) سلامت فنی

- `node php-parser` روی همهٔ فایل‌های تغییریافته/تازه: **OK** — `inc/ab-seo.php`, `404.php`, `search.php`, `home.php`, `single.php`, `inc/songs_post.php`, `inc/tarlanweb_player.php`, `myfunctions.php`, `inc/ab-sidebars.php`, `ab-mobile-probe.php`, `header.php`, `page.php`, `footer.php`, `inc/ab-perf.php`, `inc/ab-news-ticker.php`.
- `home.php`/`404.php`/`search.php`/`index.php`: صفر بایت خروجی قبل از `get_header()` (بلوکِ جدید قبل از آن است و چیزی چاپ نمی‌کند) · `footer.php` و سه مورد انتهایی دست‌نخورده · هیچ `add_filter` روی `the_content`/`template_include` اضافه نشد (`locate_template` تنها تغییرِ مسیرِ رندر است و فقط در `is_paged()`).
- مانیفست `inc/ab-sidebars.php`: ۱۰ → **۱۵ فایل** بازمحاسبه‌شده · `drop-in/` هم‌سان: **ALL MATCH (۲۰)**.
- بسته‌ها: `artifacts/drop-in-v3.1.4.zip` · `javanseda-v3.1.4-theme.zip` · `all-in-one-bundle-v3.1.4.zip` · `rollback-v3.1.4.zip` (نسخه‌های 3.1.3 + دستور حذف `inc/ab-seo.php`).

## ۶) راستی‌آزمایی ۵ دقیقه‌ای بعد از نصب

1. `curl -s https://ahangban.ir/ | grep -o 'name="description"[^>]*'` → باید یک خط برگردد (در View Source مرورگر هم دیده می‌شود).
2. یک صفحۀ آهنگ باز کنید → `property="og:image"` و `BreadcrumbList` در `<head>`.
3. یک آهنگ را در تلگرام share کنید → باید تصویر جلد بیاید.
4. `https://ahangban.ir/page/2/` → باید فهرست «جدیدترین آهنگ ها» + صفحه‌بندی باشد (نه کپیِ صفحهٔ اول) و `name="robots"` شامل `noindex` باشد.
5. `https://ahangban.ir/ab-mobile-probe.php?html=1` → بخش ۱۱ (sitemap/دامنه/og) و بخش ۱ (۱۵ فایل). بعد از بررسی فایل کاوشگر را پاک کنید.
