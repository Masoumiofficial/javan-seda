# نقشۀ تغییرات — نسخۀ 3.2.2 (روی 3.2.1)

تاریخ: 2026-10-17 · زنجیره: `v3.1.4` → `v3.2.0` → `v3.2.1` → **`v3.2.2`** (3.1.5–3.1.8 هنوز لغو)

این دور «قابلیت تازه» نیست؛ **اصلاح مسیر دیدِ همان صفحۀ پست** است: SEO / AIO / GEO، دادهٔ ساختاریافته و چند مینِ PHP. تحلیل از `single.php` شروع شد و کل قالب خوانده شد (نقشۀ وابستگی در §۳).

## ۱) چه چیزی عوض شد (۵ مورد، همه با شاهد)

| # | مشکل پیداشده (کجا بود؟) | رفع |
|---|---|---|
| **S1** | `MusicRecording`/`MusicComposition`/`WebPage` **درون** شرط «متن ترانه پر است» در `single.php:180` نشسته بود ⇒ هر آهنگی که `music_text` خالی دارد **هیچ schema** نمی‌گرفت (بیشتر نوشته‌های سایت) | schema به `inc/ab-credits.php` منتقل شد و روی `wp_head` (اولویت ۶) برای **همهٔ** نوشته‌ها چاپ می‌شود؛ بلوک قدیمی در `single.php` به‌عنوان fallback ماند و فقط وقتی فایل لایه نباشد اجرا می‌شود (`&& ! function_exists( 'javan_seda_music_schema' )`) ⇒ بدون چاپ دوگانه |
| **S2** | در همان schema، `lyricist` با **نام خواننده** پر می‌شد (دادهٔ غلط به گوگل) | `lyricist` از فیلد `lyricist`، `composer` از `composer`، `producer` از `producer` (همان داده‌های شناسنامۀ 3.2.1). اگر خالی بود، **کلید حذف می‌شود** — دروغ نمی‌سازیم |
| **S3** | `duration` نبود (فیلد ACF‌اش هم ساخته نشده) | اگر هر یک از `duration` / `music_duration` / `song_duration` وجود داشت، به ISO‑8601 تبدیل و در `AudioObject.duration` + `MusicRecording.duration` می‌نشیند؛ «۳:۴۵»، «215»، «01:12:30»، «۳ دقیقه»، «45 sec» فهمیده می‌شود؛ `1:75` یا `99:99` باطل ⇒ حذف (نه تبدیل غلط) |
| **S4** | `byArtist.url` وقتی آرشیو خواننده پیدا نمی‌شد به **خودِ صفحۀ آهنگ** اشاره می‌کرد (لینک گمراه‌کننده) و `image`/`keywords` نبود | `url` فقط وقتی آرشیو واقعی وجود دارد؛ گرهٔ `image` از تصویر شاخص؛ `keywords` از عنوان + شناسنامه؛ گرهٔ `WebSite` + `SearchAction` فقط وقتی افزونۀ سئو فعال **نباشد** (تا `#website` دوتایی نشود) |
| **S5** | `javan_seda_seo_head()` و `javan_seda_seo_breadcrumbs()` **هیچ گاردی** برای افزونۀ سئو نداشتند ⇒ به‌محض روشن‌کردن **All in One SEO / Yoast / Rank Math / SEOPress**، `meta description`، `og:*`، `twitter:*` و `BreadcrumbList` دو بار چاپ می‌شد | `javan_seda_seo_has_plugin()` اضافه شد (نسخۀ ۵ افزونه + کلاس‌ها)؛ لایه در آن حالت از meta/OG/BreadcrumbList عقب می‌کشد و فقط schema موسیقی (که افزونه‌ها نمی‌سازند) می‌ماند. برگشت: `define('JAVANSEDA_SEO_OVERRIDE_PLUGIN', true)` |

### ۱-الف) مین‌های PHP که در لایه‌های خودی پیدا و رفع شد

| محل | مشکل | رفع |
|---|---|---|
| `inc/ab-seo.php:270` | `$u = (string) get_term_link( $t );` و بعد `is_wp_error($u)` ⇒ در PHP 8 تبدیل `WP_Error` به رشته **کشنده** است (و این مسیر روی همۀ آرشیوهای تاکسونومی می‌رود؛ دقیقاً همان سناریوی «singer ۴۰۴») | `javan_seda_seo_term_url()` — داخل تابع `is_wp_error` چک می‌شود |
| `inc/ab-seo.php:296,303` | همان کست روی `get_category_link()` / `get_term_link()` در `BreadcrumbList` (روی **هر صفحۀ پست**) | `javan_seda_seo_cat_url()` + `javan_seda_seo_term_url()` |
| `inc/ab-seo.php:400`، `inc/ab-perf.php:198,231` | `static function () {}` = فقط PHP ≥ 7.4 ⇒ روی PHP 5.6/7.0‑7.3 **parse error** در فایلی که بی‌گارد `require` می‌شود = کل سایت پایین | تبدیل به closure ساده (بدون `static`) |

نتیجه: `python3 tools/compat-check.py javanseda` روی **کل قالب** از `6 hit` → **`0 hit`**.

## ۲) فایل‌ها (۵ تا، بدون تغییر در باقی)

```
javanseda/inc/ab-credits.php | +310        (بخش schema + سه تابع کمکی)
javanseda/inc/ab-seo.php     | +65 / -5    (گارد افزونه، ۲ helperِ آدرس، رفع کست‌ها و static closure)
javanseda/inc/ab-perf.php    | +2 / -2     (static closure → closure)
javanseda/single.php         | +3 / -1     (یک شرط fallback + دو خط توضیح)
javanseda/style.css          | +1 / -1     (Version: 3.2.2 — هیچ CSS تازه‌ای لازم نشد)
```
هیچ فایل CSS/JS تازه‌ای لازم نشد و ساخته هم نشد؛ `myfunctions.php` هم دست‌نخورده ماند (هر دو لایه از دور قبل require می‌شدند).

## ۳) نقشۀ وابستگی صفحۀ پست (خروجی تحلیل، برای دفعات بعد)

| داده | منبع | پردازش | خروجی HTML | استایل/رفتار |
|---|---|---|---|---|
| عنوان/متن/ترانه | DB → `the_content()` | `myfunctions.php:268` (auto-cover، پیش‌فرض **خاموش** ⇒ کاور تکراری نمی‌شود) | `single.php:131` | `style.css` |
| فیلدها (`music128/320`, `album*`, `video*`, `song`, `artist`, `music_text`, `musics_type`, `composer…producer`) | ACF یا `post_meta` | `inc/acf-compat.php` (`get_field`, `get_field_object`) | `single.php` (پلیر ۲۹۲‑۴۰۷، `download_box`، `dl_links_box`) | `style.css` + `js/scripts.js` (پلیر: `#audio`, `#volume-bar`) |
| خواننده → لینک آرشیو | ترم `singer` | `single.php:76‑130` + `javan_seda_credit_url()` (در `ab-credits.php`) | همان بالا + `.ab-credit dd a` | `style.css` بلوک ۲۵ |
| شبکه‌های اجتماعی | Option Tree (`telegram/instagram/bale/rubika`) | `inc/ab-post-extras.php` | `javan_seda_social_box()` قبل از پلیر | بلوک ۲۴ |
| نوار چسبان پخش | همان `<audio id="audio">` | `javan_seda_sticky_player()` روی `wp_footer` | `#ab-sticky-player` | بلوک ۲۴ + `JAVANSEDA_STICKY_PLAYER` |
| meta/OG/canonical/Breadcrumb/noindex/تایتل صفحه‌بندی | `is_singular` + فیلدها | `inc/ab-seo.php` روی `wp_head` (۴/۵/۸) + `wp_robots` | `<head>` | — |
| Schema موسیقی | فیلدها + شناسنامه | **تازه:** `javan_seda_music_schema()` روی `wp_head` ۶ | `<head>` | — |
| بقیه (سایز تصاویر، lazy، defer، preload فونت) | — | `inc/ab-perf.php` (`the_content` ۵۰، `script_loader_tag`) | سراسری | — |
| سایدبار/ابزرک‌ها | `inc/ab-sidebars.php` + `inc/widgets.php` | `dynamic_sidebar('left_side'…)` در `single.php:566,636` | `<aside>` | — |
| ناحیۀ «هنرمند» | `taxonomy-singer.php` + `artist-template.php` | جدا از صفحۀ پست | — | — |
| حالت تیره | فقط دکمه/کلاس `darkHeader` روی `#header` (`header.php:66`، `js/scripts.js:76`) — تیره‌شدن سراسری در `style.css` نیست ⇒ باکس‌های سفیدِ 3.2.0/3.2.1 تداخلی ندارند | — | — | — |

**مرز مسئولیت:** افزونۀ سئو (اگر فعال شود) حالا از طریق `javan_seda_seo_has_plugin()` شناسایی می‌شود؛ `inc/breadcrumbs.php` هم از قبل فقط وقتی Yoast/RankMath فعال است کرَمب چاپ می‌کند (تعارض با ما نداشت). `functions.php` **رمزنگاری ionCube** است و قابل تحلیل نبود ⇒ هر چیزی که در آن باشد فقط با `?diag=1`/`?ab_singer_diag=1` و مشاهدهٔ خروجی زنده قابل راستی‌آزمایی است.

## ۴) لینک‌های نصب (raw — برچسب `v3.2.2`)

| فایل مقصد در `wp-content/themes/javanseda/` | لینک |
|---|---|
| `inc/ab-credits.php` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.2/drop-in/inc/ab-credits.php |
| `inc/ab-seo.php` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.2/drop-in/inc/ab-seo.php |
| `inc/ab-perf.php` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.2/drop-in/inc/ab-perf.php |
| `single.php` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.2/drop-in/single.php |
| `style.css` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.2/drop-in/style.css |

زیپ: `artifacts/drop-in-v3.2.2.zip` · بازگشت: `artifacts/rollback-v3.2.2.zip` (نسخۀ 3.2.1 + توضیح) · کل قالب: `artifacts/javanseda-v3.2.2-theme.zip`

**نصب:** پنج فایل را جایگزین کنید (فایل تازه‌ای نیست). اگر 3.2.1 را نصب نکرده‌اید، اول همان را نصب کنید چون `inc/ab-credits.php` در این نسخه به فایل‌هایش وابسته است — ولی نسخه‌های 3.2.1 و 3.2.2 هم‌نام‌اند و اگر `drop-in-v3.2.2.zip` را روی 3.2.0 نصب کنید، فایل 3.2.1 لازم است (اگر نبود، `inc/ab-credits.php` را از برچسب `v3.2.1` بگیرید).

## ۵) راستی‌آزمایی

اجراشده (در سندباکس، بدون PHP/WordPress):
* `tools/check.js` (php-parser) → **OK** روی `ab-credits.php` · `ab-seo.php` · `ab-perf.php` · `single.php`
* `tools/compat-check.py javanseda` → **`0 hit`** روی کل قالب (قبلاً ۶)
* تحلیل AST: چهار فایل لایه در سطحِ include فقط `if`/`define`/`add_action` دارند — **صفر `echo`/`print` سطح‌بالا** ⇒ هیچ بایتی قبل از `get_header()` چاپ نمی‌شود
* تست منطق تبدیل duration با ۱۲ ورودی (ارقام فارسی/عربی، `mm:ss`، `hh:mm:ss`، واحد دار، نامعتبر) — نتایج در §۱ سطر S3
* شمارش چاپ‌کننده‌های `ld+json` در قالب: `footer.php` (Organization)، `ab-seo.php` (BreadcrumbList)، `ab-credits.php` (موسیقی)، `single.php` (fallback، حالا خاموش)، `artist-template.php` و `taxonomy-singer.php` (صفحه‌های دیگر) ⇒ روی صفحۀ پست **دقیقاً یک** گراف موسیقی
* `gradient(` در `style.css` = ۰ · براکت‌های CSS بالانس · CRLF حفظ شد (`single.php` ۶۸۱) · سه سطر انتهایی `footer.php` بدون تغییر (اصلاً تغییر نکرد)
* `header.php` متاتگ `description/og` ندارد ⇒ گارد افزونه تکراری‌سازی را می‌بندد و خودِ لایه تنها منبع است

اجرا **نشدہ** (صریح): رندر واقعی وردپرس، اعتبارسنجی Rich Results Tool، و تست فعال‌کردن AIO — چون در این سندباکس PHP/MySQL/وبسرور نیست و `curl` به دامنه‌تان مسدود است. این‌ها را باید روی هاست ببینید:
1. `view-source:` یک صفحۀ پست → `<script type="application/ld+json">` با `@graph` (حتی آهنگی که متن ترانه ندارد) و نبودِ `og:title` دوتایی.
2. `curl -s https://ahangban.ir/<post>/ | grep -c 'name="description"'` باید `1` باشد (و اگر AIO را فعال کردید هم `1`).
3. `?diag=1` (بخش ۱۱ کاوشگر) → خط «افزونۀ سئو شناسایی‌شده» حالا درست گزارش می‌کند.

## ۶) بازگشت

* `rollback-v3.2.2.zip` → پنج فایل `*.3.2.1` را جایگزین کنید (فایل تازه‌ای حذف نمی‌شود).
* یا فقط schema را خاموش کنید: `define( 'JAVANSEDA_MUSIC_SCHEMA', false );`
* یا رفتار گارد افزونه را برگردانید: `define( 'JAVANSEDA_SEO_OVERRIDE_PLUGIN', true );`
* یا کل لایۀ شناسنامه را: `define( 'JAVANSEDA_CREDITS', false );` (schema هم خاموش می‌شود؛ دقت کنید در این حالت schema کلاً نمی‌آید — پس اگر schema را نگه می‌دارید، `JAVANSEDA_MUSIC_SCHEMA` را单独 روشن بگذارید: `define('JAVANSEDA_MUSIC_SCHEMA', true)` کافی است، چون گاردش مستقل است)

## ۷) باقی‌ماندۀ دستی (از دست کد خارج)

1. فیلد `duration` (یا `music_duration`) را در ACF بسازید تا S3 فعال شود — کد آماده است.
2. `composer/lyricist/arranger/mixer/producer` را برای آهنگ‌های پربازدید پر کنید (یا از metabox).
3. `wp search-replace 'http://www.ahangban.ir' 'https://ahangban.ir'` + ریدایرکت (Kaushگر `?diag=1` تعدادش را گزارش می‌کند).
4. افزونۀ سئو + سایت‌مپ (هر دو sitemap هنوز ۴۰۴)؛ حالا با فعال‌کردنشان metaها دوتایی نمی‌شود.
5. ادغام `singer`های دوبل (لینک شناسنامه به اولین ترمِ هم‌نام می‌رود؛ `javan_seda_credit_terms()` عمداً تکراری را برنمی‌دارد).
