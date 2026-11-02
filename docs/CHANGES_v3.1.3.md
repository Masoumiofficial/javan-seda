# نقشۀ تغییرات — نسخهٔ 3.1.3 (دور ۱۰)

مخزن: `Masoumiofficial/javan-seda` · PR: `#3` · تگ: `v3.1.3`
بسته‌ها: `artifacts/drop-in-v3.1.3.zip` · `artifacts/javanseda-v3.1.3-theme.zip` · `artifacts/all-in-one-bundle-v3.1.3.zip` · `artifacts/rollback-v3.1.3.zip`

---

## ۱) درخواست مالک و وضعیت هر مورد

| # | درخواست | وضعیت | کجا |
|---|----------|--------|------|
| الف | «بعد از پچِ هدر، سایت کلاً خراب شد — هدر در دسکتاپ و موبایل سالم شود» | ✅ رفع شد (علت قطعی پیدا و اصلاح شد) | `header.php` v3.1.3 + `patches/header.original-patched.php` (نسخۀ ۳) |
| ب | «در موبایل فاصلۀ محتوا با هدر بیشتر شود» | ✅ ۶۵px → **۹۰px** (نوار ۷۰ + ۲۰ فاصله) | بلوک ۲۱ `style.css` + پچ |
| ج | «نوار اخبار **فقط** از دستۀ `news` تغذیه کند و در بقیۀ صفحه‌ها news نباشد» | ✅ حالت سخت‌گیر (پیش‌فرض) + فهرست slug | `inc/ab-news-ticker.php` |
| د | «بهینۀ سرعت قالب» | ✅ لایۀ تازه | `inc/ab-perf.php` + `docs/PERFORMANCE.md` |
| ه | «در صفحۀ هنرمند، بلوک پروفایل و سطر «۱۲ اثر در این صفحه • آخرین به‌روزرسانی: ۴ بهمن ۱۳۹۹» به تیتر «۱ موزیک ویدیو» می‌چسبد» | ✅ فاصله + جداکننده | انتهای بلوک ۲۲ `style.css` |

---

## ۲) چرا سایت به‌هم ریخت؟ (تشخیصِ قطعی — برای این‌که تکرار نشود)

در نسخۀ 3.1.2 دو خطای جدا وجود داشت:

1. **CSSِ شرطیِ مخفی‌کننده:** قواعد `#header{display:none}` و `.menu_right / #searches / .dark_light_mode{display:none}`
   داخل بلوک PHPِ `if ( wp_is_mobile() )` نوشته شده بود. هاست شما (کشِ بر پایهٔ User-Agent / پروکسی)
   آن شرط را برای دسکتاپ هم `true` کرد ⇒ **هدر در دسکتاپ حذف شد** و چیدمان موبایل هم به‌هم ریخت.
2. **کامنتِ نشکسته:** یک جملهٔ فارسی بیرون از `/* … */` داخل `<head>` افتاده بود ⇒ آن متن **در صفحه چاپ شد**
   و کل ساختار را خراب کرد.

**اصلاح (قاعدۀ جدید این پروژه):**

- نمایش/مخفی‌سازی هدر **فقط** با `@media (max-width: 800px)` انجام می‌شود؛ هیچ قاعده‌ای به `wp_is_mobile()` یا
  User-Agent گره نمی‌خورد ⇒ **cache-safe** (دسکتاپ و موبایل با یک فایل، بدون تفکیکِ کش).
- هدر موبایل (`inc/mobiles_header`) **همیشه رندر می‌شود** و CSS تصمیم می‌گیرد کِی دیده شود (نبودش = نداشتن هدر در گوشی).
- در `<head>` هیچ متنی بیرون از کامنت چاپ نمی‌شود؛ فاصلۀ بین `</style>` و `</head>` فقط یک `\n` است (تأیید شد).
- `body.ab-has-mh` (کلاس نسخۀ قبل) همچنان پشتیبانی می‌شود تا اگر فایل‌ها نصفه‌نیمه به‌روز شدند، صفحه نشکند.

> `header.php` این نسخه **نسخۀ مرجع** است. برای سایت شما مسیرِ پیشنهادی **پچ تک‌فایلی کمینه** است
> (`patches/header.original-patched.php`) که فقط ۱۸ سطر به `header.php` اصلی اضافه می‌کند و **هیچ خطی را حذف/ویرایش نمی‌کند**
> (با diff روی `git show 805e28c:javanseda/header.php` تأیید شده). هر دو را با هم نصب نکنید.

---

## ۳) فایل‌ها (drop-in) + امضا

پایۀ مقصد در هاست: `wp-content/themes/javanseda/` — لینک مستقیم هر فایل (بعد از ساخت تگ معتبر است):

`https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.1.3/drop-in/<مسیر>`

| فایل | بایت | sha256 (۱۶ رقم اول) | نقش |
|------|------|---------------------|-----|
| `header.php` | 4859 | `9807921341cccde4` | نسخۀ مرجع هدر (viewport-driven، بدون `wp_is_mobile`) |
| `style.css` | 108330 | `8a42a305e10243c2` | `Version: 3.1.3` · بلوک ۲۱ (هدر) · بلوک ۲۲ (اخبار + فاصلۀ هنرمند) |
| `myfunctions.php` | 25142 | `11751e91d9752b7b` | بارگذاری محافظت‌شدۀ `inc/ab-perf.php` (کنار سه لایۀ قبلی) |
| `home.php` | 3162 | `1cf3bb8670f6683d` | تیکر اخبار + fallback (منطق بدون تغییر) |
| `single.php` | 34280 | `11c677e1a7a61e52` | تغییر نکرد — برای بازرسیِ مانیفست |
| `page.php` | 1347 | `b83d394588a1cbce` | تغییر نکرد |
| `footer.php` | 7301 | `0e8e4b8d56a75ecf` | تغییر نکرد (سه مورد انتهایی حفظ: `wp_footer` · `</body>` · `</html>`) |
| `inc/ab-news-ticker.php` | 8804 | `b9eebd2bc03497b9` | **تغییر مهم:** slugها + حالت سخت‌گیر + بندانگشتیِ خبر |
| `inc/ab-perf.php` | 19439 | `d79b274b181e2011` | **فایل تازه:** لایۀ «بایتِ سرعت» |
| `inc/ab-sidebars.php` | 21876 | `058746b425c35a41` | مانیفست ۱۰ فایل، بازمحاسبه‌شده |
| `inc/acf-compat.php` | 6402 | `a80e945dcfc128fa` | تغییر نکرد |
| `inc/vip_slider.php` | 2890 | `03b6e6cbf63ee7e4` | تغییر نکرد |
| `patches/header.original-patched.php` | 3991 | `7a540791f21526aa` | **نسخۀ ۳ پچ** (۱۸ سطر افزوده، ۰ حذف) |
| `patches/README.txt` | 4758 | `951c403e4839f80c` | توضیح پچ + قاعدۀ انتخاب + تست سریع |
| `ab-mobile-probe.php` | 50464 | `3892913af05bcff0` | کاوشگر: §۴.۱ اخبار (بازنویسی) + §۱۰ بهینگی (تازه) |

**مسیر الف — کمینۀ پیشنهادی (برای ahangban.ir):**
1. `patches/header.original-patched.php` → روی `header.php` هاست (اول `cp header.php header.php.bak-312`)
2. `style.css` · `inc/ab-news-ticker.php` · `inc/ab-perf.php` · `myfunctions.php`
3. Purge All در افزونۀ کش + Purge در CDN

**مسیر ب — کامل:** `drop-in-v3.1.3.zip` (۱۵ فایل؛ `patches/` یا `header.php` — یکی، نه هر دو) یا `javanseda-v3.1.3-theme.zip` (قالب کامل برای «ظاهر → آپلود قالب»).

هر دو مسیر به **یک `style.css`** می‌رسند؛ بلوک ۲۱ همان قواعد را دارد، پس اگر `header.php` عوض نشود هم فاصلۀ موبایل و هم فاصلۀ هنرمند درست می‌شود.

---

## ۴) اخبار روز — فقط دستۀ `news` (درخواست «ج»)

- ترتیب پیداکردن دسته: اول `JAVANSEDA_NEWS_CATEGORY` (id یا slug)؛ اگر تنظیم نشده بود، slugهای `news`, `akhbar`, `akhbar-musiqi`, `music-news`, `اخبار` (نتیجه در static cache می‌نشیند).
- **حالت سخت‌گیر (= پیش‌فرض):** اگر دسته پیدا نشد، **هیچ** خبری چاپ نمی‌شود. برای بازگشت به «آخرین نوشته‌ها»:
  `define( 'JAVANSEDA_NEWS_STRICT', false );`
- تنها منبع داده، همان یک دسته است؛ هیچ هوک سراسری اضافه نشده و هیچ خبری «تولید» نمی‌شود — در بقیۀ صفحه‌ها هم چیزی جز نوشته‌های همان دسته نمی‌آید.
- تنظیم‌ها: `JAVANSEDA_NEWS_COUNT` (۱–۲۰، پیش‌فرض ۸) · `JAVANSEDA_NEWS_CACHE` (ثانیه، ۰ = بدون کش) · `JAVANSEDA_NEWS_CATEGORY` · `JAVANSEDA_NEWS_STRICT`.
- شورت‌کد: `[ab_news count="6"]`.
- کامنتِ تشخیص در output: `<!-- javan_seda: news ticker | cat=12 | 8 خبر | strict=on -->` (`cat=NONE` یعنی دسته وجود ندارد).

> ⚠ **هشدار مهم:** `https://ahangban.ir/news/` در حال حاضر **۴۰۴** برمی‌گرداند ⇒ دسته‌ای با نامک `news` روی سایت شما وجود ندارد
> (یا archive‌اش غیرفعال است). در حالت سخت‌گیر، تا وقتی این درست نشود، نوار اخبار **خالی** می‌ماند. راه‌حل‌ها:
> ۱) در «نوشته‌ها › دسته‌ها» دسته‌ای با نامک `news` بسازید و نوشته‌های خبری را داخلش بگذارید؛
> ۲) یا id/slug موجود را تنظیم کنید: `define( 'JAVANSEDA_NEWS_CATEGORY', 'akhbar' );`
> ۳) یا موقتاً `define( 'JAVANSEDA_NEWS_STRICT', false );` تا آخرین نوشته‌ها بیایند.
> کاوشگر (بخش ۴.۱) وضعیت HTTP آرشیو دسته را همین حالا تست می‌کند.

---

## ۵) فاصلۀ صفحۀ هنرمند (درخواست «ه» — دسکتاپ)

علت: در `taxonomy-singer.php` بلوک پروفایل (`artist_box_tarlanweb`) و سطرِ متا فقط با `float` کنار هم می‌آمدند و هیچ فاصلهٔ عمودی بینشان نبود ⇒ تیتر «۱ موزیک ویدیو» به بلوک می‌چسبید.
قاعده‌ها (انتهای بلوک ۲۲ `style.css`):

```css
.artist_box_tarlanweb{margin-bottom:34px !important;padding-bottom:26px !important;}
.artists_imsf .socials_box{margin-top:14px !important;padding-top:12px !important;border-top:1px solid var(--ab-line);}
.ab-artist-page__meta{margin:16px 0 0 !important;padding-top:12px !important;border-top:1px solid var(--ab-line);}
.artist_box_detss .clear2{padding:14px 0 !important;}
#artist_box > .song_boxs:first-of-type,.ab-artist-page__sortbox + .song_boxs,#centers_box > .song_boxs:first-of-type{margin-top:26px !important;}
.song_boxs .songs_title_box{margin-bottom:12px !important;}
@media (max-width: 800px){
	.artist_box_tarlanweb{margin-bottom:22px !important;padding-bottom:16px !important;}
	.ab-artist-page__meta{margin-top:12px !important;}
}
```

جداکننده‌ها با `var(--ab-line)` (همان خط خاکستری خودِ قالب) کشیده شده‌اند: **بدون گرادینت**، پالت سازمانی `#FFFFFF / #185ADB / #111111`.
تاکسونومی `singer` و ساختار `taxonomy-singer.php` دست‌نخورده‌اند (تغییر فقط CSS است).

---

## ۶) بایتِ سرعت (درخواست «د») — `inc/ab-perf.php` (فایل تازه)

۱) `defer` به JSهای غیرضروری (jQuery/migrate مستثنا) · ۲) پاک‌سازی `<head>` (generator · shortlink · RSD · wlwmanifest · oEmbed · feedهای اضافه · ایموجی) · ۳) `preload` برای `fonts/vazir-fd.woff` و `fonts/icofont.woff` · ۴) تزریق `font-display: swap` · ۵) `loading="lazy"` + `decoding="async"` روی `<img>`های بی‌بعد در متن/چکیده (افزودن `width/height` حدسی پشت کلید `JAVANSEDA_PERF_IMG_DIMS` است و پیش‌فرض خاموش ⇒ آیکن‌های کوچک بزرگ نمی‌شوند) · ۵.۵) `wp_deregister_script( 'wp-embed' )` و حذف feedهای اضافه · ۶) `javan_seda_perf_report()` که در بخش ۱۰ کاوشگر چاپ می‌شود.

خاموش‌کردن: `define( 'JAVANSEDA_PERF', false );` — جدول کاملِ یافته‌ها و کارهای دستی در **`docs/PERFORMANCE.md`**.
خلاصه: `style.css` ۱۰۷KB مینیفای‌نشده · `css/icofont.min.css` ۲۳KB با صدها قاعدۀ glyph بلااستفاده · فونت‌ها ۲۹۸KB فقط `.woff` (بدون woff2) · `js/mediaqueries.js` و `js/html5shiv.js` در سورسِ قابل‌ویرایش هیچ ارجاعی ندارند (۲۶KB مرده) · `meks_disable_srcset` تصاویر واکنش‌گرا را خاموش کرده است.

---

## ۷) کاوشگر — `ab-mobile-probe.php`

آدرس: `https://ahangban.ir/ab-mobile-probe.php?html=1` — تغییرات این دور:

- **§۴.۱ (بازنویسی):** slugهای مجاز، id/نام/تعداد دستۀ یافت‌شدۀ `news`، لینک آرشیو + **تست HTTP زنده** (اگر ۴۰۴ باشد صریح می‌گوید)، حالت سخت‌گیر/مجاز، سه خبرِ اول، تنظیم‌ها.
- **§۱۰ (تازه):** گزارش بایتِ سرعت (اندازۀ فایل‌ها، صف JS/CSS، jQuery تکراری، OPcache، فشرده‌سازی، srcset، پیشنهادها).
- §۱ مانیفست حالا **۱۰ فایل** را مقایسه می‌کند (`inc/ab-perf.php` و `inc/ab-news-ticker.php` اضافه شدند).
- یک تایپوی قدیمی هم در همین فایل اصلاح شد (`'(闭)'` → `'(بدون‌نام)'`).

بعد از بررسی، فایل `ab-mobile-probe.php` را از هاست **پاک کنید**.

---

## ۸) سلامت فنی (تأییدشده در این دور)

- سینتکس PHP تمام فایل‌های تغییریافته با `node php-parser`: **OK** — `header.php`, `myfunctions.php`, `home.php`, `single.php`, `page.php`, `footer.php`, `inc/ab-news-ticker.php`, `inc/ab-perf.php`, `inc/ab-sidebars.php`, `ab-mobile-probe.php`.
- `style.css`: براکت **۱۰۰۸/۱۰۰۸** · `gradient` **۰** · `Version: 3.1.3`.
- `header.php`: `wp_is_mobile` فقط داخل کامنت · `get_template_part('inc/mobiles_header')` بدون شرط · متن فارسی بیرون از `<style>` صفر (جز `placeholder="جستجو ..."` که باید دیده شود).
- پچ: diff با `805e28c` = **۱۸ سطر افزوده / ۰ حذف** · هیچ `display:none` روی `#header`/منو/جست‌وجو ندارد.
- `drop-in/` هم‌سان با `javanseda/`: **ALL MATCH (۱۵ فایل)**.
- صفر بایت خروجی قبل از `get_header()` در `index.php`/`home.php`/`single.php`/`page.php` · سه مورد انتهایی `footer.php` حفظ شد ·
  هیچ `add_filter` تازه‌ای روی `template_include` اضافه نشد (روی `the_content` فقط افزودن صفت به `<img>` با اولویت ۵۰) ·
  هوک سراسری و همهٔ تاکسونومی‌ها (از جمله `singer`) دست‌نخورده.

## ۹) بازگردانی

`artifacts/rollback-v3.1.3.zip` شامل: `header.php.3.1.2` · `style.css.3.1.2` · `myfunctions.php.3.1.2` · `header.php.original` (فایل تمیز baseline قبل از همهٔ پچ‌ها) · `drop-in-v3.1.2.zip` · `README.txt`.
ساده‌ترین بازگشت: فقط حذف `inc/ab-perf.php` و `inc/ab-news-ticker.php` از هاست — قالب بدون آن‌ها هم کار می‌کند، چون `require`ها با `is_readable()` محافظت شده‌اند.
