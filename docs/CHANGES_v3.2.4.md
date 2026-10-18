# نقشۀ تغییرات — نسخۀ 3.2.4 (روی 3.2.3)

تاریخ: 2026-10-18 · زنجیره: `3.2.0` → `3.2.1` → `3.2.2` → `3.2.3` → **`3.2.4`**

خواستۀ شما دربارهٔ **نوار چسبانِ پخش** (پلیر پایین صفحه):
۱) در حالت نایت‌مود هنوز سفید است ⇒ تیره شود · ۲) ارتفاعش کمتر شود · ۳) به صفحۀ اصلی هم بیاید · ۴) دکمۀ «قبلی/بعدی» داشته باشد (برای آلبوم و برای صفحۀ اصلی) · ۵) یکم جذاب‌تر.

## ۱) فایل‌های تغییریافته (۲ فایل — بدون فایل تازه در قالب، بدون مهاجرت داده)

| فایل | وضعیت | دلیل | نتیجۀ قابل تأیید |
|---|---|---|---|
| `inc/ab-post-extras.php` | ✏️ بازنویسی بخش ۲ (نوار پخش) | فهرست قطعه‌ها در PHP ساخته می‌شود و نشانه‌گذاری/اسکریپت نوار ارتقا یافت | `view-source:` ⇒ `data-ab-sp-mode` + `data-ab-sp-index` + `#ab-sp-audio` + `script#ab-sp-tracks` + دو `.ab-sp-nav` |
| `style.css` | ✏️ بازنویسی قواعد `.ab-sp*` در بلوک ۲۴ · `Version: 3.2.4` | رنگ‌های هارد‌کد (`#FFFFFF`, `#111111`, `#e6ebf3`…) با متغیرهای قالب عوض شدند | `grep -n "ab-sp" style.css \| grep -E "#(FFF\|111\|8a92a6)"` ⇒ **هیچ** · صفر `gradient(` · براکت‌ها بالانس |
| `preview/sticky-player.html` | ➕ دمو (نصب نمی‌شود) | «قبل از نصب ببینم» — با CSS واقعی همین نسخه | سه فریم: روشن، شب، موبایل — دکمۀ پخش/قبلی/بعدی در دمو کار می‌کند |

**دست‌نخورده‌ها:** `single.php` · `footer.php` · `js/scripts.js` (پلیر اصلی قالب) · `inc/ab-credits.php` (باکس شناسنامۀ 3.2.3) · `inc/ab-seo.php` · `inc/ab-perf.php` · `functions.php` (قفل ionCube) · باکس اجتماعی (همان بلوک ۲۴، فقط قواعد نوار عوض شد).

## ۲) رفع نایت‌مود (علت سفید ماندن)

نوار با رنگ‌های ثابت نوشته شده بود (`background:#FFFFFF` و…). سوییچ شبِ قالب کلاس `night` را روی `<body>` می‌گذارد و **همان متغیرها** را در `style.css` (بلوک `:root` در مقابل `.night`) بازنویسی می‌کند. حالا نوار فقط از متغیرها می‌خواند:

```css
.ab-sticky{background:var(--ab-surface);border-top:1px solid var(--ab-line);…}
.ab-sp__title{color:var(--ab-text)}  .ab-sp__artist{color:var(--ab-muted)}
.ab-sp-play{background:var(--ab-surface);border:1px solid var(--ab-line);color:var(--ab-blue-accent)}
```
سه مورد که متغیر نداشتند (سایه، خطِ پیشرفت، زمینۀ تصویر) با قاعدۀ `.night` صریح تنظیم شدند. ⇒ بدون هوک تازه، بدون کلاس تازه، همان سوییچِ خودِ قالب.

## ۳) ارتفاع کمتر

| | قبل | حالا |
|---|---|---|
| دسکتاپ | `padding:8px 12px` · تصویر ۴۶px · دکمۀ پخش ۳۸px · `body.ab-sticky-on{padding-bottom:84px}` | `padding:4px 12px` · تصویر ۳۴px · پخش ۳۲px · **۶۰px** |
| موبایل | تصویر ۳۸px · `padding-bottom:72px` | تصویر ۳۰px · پخش ۲۸px · **۵۰px** |

## ۴) قبلی / بعدی (آلبوم و صفحۀ اصلی)

سه حالت، همه با همان تابع `javan_seda_sticky_player()` روی `wp_footer` (بدون فیلتر روی `the_content` / `template_include`):

- **صفحۀ آلبوم** (`data-ab-sp-mode="album"`): فهرست از همان حلقۀ `have_rows('album')` (زیرفیلدهای `song_names` · `albumlink128` → `albumlink320`). قبلی/بعدی **روی ردیفِ خودِ پلیرِ قالب کلیک می‌کند** (`.playlist-track` و در نبودش `.small-toggle-btn`) ⇒ منطق پخش کپی نشده، فقط هدایت می‌شود؛ نوار و پلیر صفحه همیشه هم‌خوان می‌مانند (نوبت: `.active-track`). اگر خودتان با پلیرِ صفحه قطعه عوض کنید، نوار هم به‌روز می‌شود (`mirror()`).
- **تک‌آهنگ**: فهرست = [آهنگ قبلی، همین، آهنگ بعدی] از `get_previous_post(true)` / `get_next_post(true)` (فقط نوشته‌هایی که فایل پخش دارند). کلیک ⇒ `src` همان فایل روی `#audio` قالب + به‌روزرسانی عنوان/خواننده/تصویر/لینک دانلود، بدون بارگذاری دوبارهٔ صفحه.
- **صفحۀ اصلی** (`is_home() || is_front_page()` و نه صفحه‌بندی‌شده): فهرست = تا ۸ آهنگِ تازه که پخش آنلاین دارند (`JAVANSEDA_STICKY_HOME_LIMIT`)؛ چون آنجا `<audio id="audio">` وجود ندارد، نوار از **پلیر مستقل `#ab-sp-audio`** استفاده می‌کند (فقط `preload="none"`، تا کلیک نکنید چیزی دانلود نمی‌شود).

**بدون جاوااسکریپت هم کار می‌کند:** در حالت آلبوم دکمه‌ها `<button>` و در دو حالت دیگر `<a href="{permalink آهنگ مقصد}">` هستند؛ اگر JS نپرداختد، لینک معمولی عمل می‌کند (و `href` با هر تعویض قطعه به‌روز می‌شود).

## ۵) جذاب‌تر (بدون گرادینت، همان پالت #FFFFFF · #185ADB · #111111)

- خط ۲px آبی بالای نوار فقط وقتی **در حال پخش** است ظاهر می‌شود (خودِ نوار کلاس `ab-sp--playing` می‌گیرد).
- اکولایزر سه‌میله‌ای کوچک گوشۀ تصویر حین پخش (keyframes؛ با `prefers-reduced-motion` خاموش می‌شود).
- نوار پیشرفت حالا **پرشدۀ آبی** دارد (پوستۀ `.ab-sp-track` + `.ab-sp-fill` که JS عرضش را ست می‌کند — بدون گرادینت) و هندلِ آن با حلقۀ سفید/تیره حول خودش.
- شمارۀ قطعه در چیپ گرد: `۳/۹`.
- هاور/فوکوس همهٔ دکمه‌ها با لهجۀ آبی + `:active` روی دکمۀ پخش + `focus-visible` روی اسلایدر (دسترس‌پذیری با کیبورد).

## ۶) کلیدهای خاموشی (همه با یک `define()` در `wp-config.php`)

```php
define( 'JAVANSEDA_STICKY_PLAYER', false );    // کل نوار چسبان
define( 'JAVANSEDA_STICKY_ON_HOME', false );   // فقط روی صفحۀ اصلی خاموش
define( 'JAVANSEDA_STICKY_HOME_LIMIT', 5 );    // تعداد آهنگ‌های صفحۀ اصلی
define( 'JAVANSEDA_POST_EXTRAS', false );      // کل فایل (باکس اجتماعی + نوار)
```

## ۷) نصب / بازگشت

- `inc/ab-post-extras.php` → `wp-content/themes/javanseda/inc/ab-post-extras.php`
- `style.css` → `wp-content/themes/javanseda/style.css`
- زیپ: `artifacts/drop-in-v3.2.4.zip` · بازگشت: `artifacts/rollback-v3.2.4.zip` (دو فایل `*.3.2.3`) · کل قالب: `artifacts/javanseda-v3.2.4-theme.zip`

## ۸) بررسی‌ها

* `tools/check.js` ⇒ `OK` روی `inc/ab-post-extras.php` · `javanseda/style.css`: براکت‌ها بالانس، صفر `gradient(`، صفر رنگ صریحِ روشن در قواعد `.ab-sp*`
* `node -e "new Function(js)"` روی اسکریپت درون‌خطیِ نوار ⇒ **parse OK** (ES5؛ بدون `let/const/=>`)
* `tools/compat-check.py javanseda` ⇒ **`0 hit`** روی کل قالب (کف PHP 5.6 — بدون بستۀ `static`، بدون `??`)
* ساختار تگ‌های نوار با HTMLParser بالانس است؛ `preview/sticky-player.html` و `preview/credits.html` هم بالانس
* `drop-in/` با فایل‌های قالب هم‌sha (۹ فایل) · فایل‌های این دور LF مانده‌اند (`single.php` CRLF دست‌نخورده)
* **اجرا نشد:** رندر واقعی وردپرس و پخش واقعی فایل صوتی (این سندباکس PHP/MySQL/MP3 ندارد) ⇒ چشمتان روی هاست ملاک است؛ دمو فقط ظاهر و تعامل UI را نشان می‌دهد.
