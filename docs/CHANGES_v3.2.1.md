# نقشۀ تغییرات — نسخۀ 3.2.1 (روی 3.2.0)

تاریخ: 2026-10-17 · مرجع: `v3.2.0` (خودbuilt روی `v3.1.4`) · 3.1.5–3.1.8 همچنان **لغو** هستند.

خواسته: در صفحۀ پست، مثل ahaang.com، ردیف‌های زير اضافه شود:
**خواننده · آهنگساز · تنظیم · ترانه‌سرا · میکس و مستر · تولید** — هرکدام با لینک به صفحۀ همان نفر.

نظر/تصمیم اجرا (چون «صفحۀ هنرمند» در سایت شما همان آرشیو `singer` است):

| نکته | تصمیم |
|---|---|
| لینک هر نام | اگر نام با یک ترمِ `singer` (یا برچسب هم‌نام در مسیر موجود قالب) یکی باشد → لینک آرشیو همان خواننده؛ اگر نباشد → **فقط متن**. هیچ لینک ۴۰۴‌ساز نمی‌سازیم و **هیچ تاکسونومی تازه‌ای ثبت نمی‌شود** |
| داده از کجا می‌آید؟ | همان `get_field()` که قالب استفاده می‌کند: با ACF واقعی یا با `inc/acf-compat.php` (که post_meta را می‌خواند). پس اگر بعداً فیلدهای ACF بسازید، **بدون تغییر کد** وصل می‌شود |
| ورودی راحت | یک **metabox** در ویرایش نوشته با همین ۵ فیلد (تا ACF نسازید هم کار کنید). اگر ACF را ترجیح می‌دهید: `define( 'JAVANSEDA_CREDITS_METABOX', false );` |
| جای باکس | پیش‌فرض **بعد از لینک‌های دانلود** (مثل ahaang). با گزینهٔ «شناسنامه بالای پلیر» به بالای باکس پلیر منتقل می‌شود |
| باکس خالی | تا هیچ ردیفی داده نداشته باشد، باکس اصلاً چاپ نمی‌شود |

---

## ۱) فایل‌های قابل دانلود (raw — از برچسب `v3.2.1`)

جایگزینی در `wp-content/themes/javanseda/`:

| فایل مقصد | لینک raw | نوع |
|---|---|---|
| `inc/ab-credits.php` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.1/drop-in/inc/ab-credits.php | **تازه** |
| `single.php` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.1/drop-in/single.php | جایگزینی |
| `myfunctions.php` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.1/drop-in/myfunctions.php | جایگزینی |
| `inc/theme-options.php` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.1/drop-in/inc/theme-options.php | جایگزینی |
| `style.css` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.1/drop-in/style.css | جایگزینی |

فایل‌های `footer.php` و `inc/ab-post-extras.php` در این دور **تغییر نکردند** (همان v3.2.0؛ اگر نصبشان کرده‌اید دوباره لازم نیست).

زیپ‌ها: `artifacts/drop-in-v3.2.1.zip` · `artifacts/rollback-v3.2.1.zip` · `artifacts/javanseda-v3.2.1-theme.zip`

## ۲) قدم‌های نصب (۵ دقیقه)

1. `inc/ab-credits.php` را در `wp-content/themes/javanseda/inc/` کپی کنید.
2. چهار فایل بالا را جایگزین کنید.
3. هر نوشتهٔ آهنگ را باز کنید → کادر **«شناسنامۀ آهنگ (تهیه‌کنندگان)»** در پایین ویرایش: نام‌ها را وارد و ذخیره کنید.
   - برای چند نفر با `/` جدا کنید: `آصف آریا / معین حبیبی`
   - اگر می‌خواهید نام لینک بشود، باید دقیقاً همان نامِ ترمِ خواننده باشد (یا در `singer` آن ترم را بسازید).
4. (اختیاری) تنظیمات قالب → **صفحه دانلود آهنگ** → «شناسنامۀ آهنگ» / «تیتر باکس شناسنامۀ آهنگ» / «شناسنامه بالای پلیر».
5. کش را خالی کنید.

## ۳) کلیدهای داده (برای ACF یا `update_post_meta`)

| کلید | برچسب در باکس | آیکن |
|---|---|---|
| `artist` (موجود) | خواننده | `icofont-radio-mic` |
| `composer` | آهنگساز | `icofont-music-alt` |
| `lyricist` | ترانه‌سرا | `icofont-edit` |
| `arranger` | تنظیم | `icofont-headphone` |
| `mixer` | میکس و مستر | `icofont-record` |
| `producer` | تولید | `icofont-camera` |

اگر `artist` خالی باشد، ردیف خواننده از ترم‌های `singer`ِ خودِ نوشته پر می‌شود (پس روی سایتی که `artist` پر است، همان نمایش داده می‌شود).

## ۴) فیلدهای تازه در تنظیمات قالب

همه در بخش **`dl_pages`** («صفحه دانلود آهنگ») — همان‌جا که `share_off`/`tag_off`/`cm_off` هستند:

| کلید | نوع | پیش‌فرض | کار |
|---|---|---|---|
| `credits_box` | on-off | `on` | روشن/خاموشِ کل باکس |
| `credits_title` | text | خالی («شناسنامۀ آهنگ») | تیتر باکس |
| `credits_before_player` | on-off | `off` | `on` = بالای پلیر، `off` = بعد از لینک‌های دانلود |

## ۵) نقشۀ تغییرات فایل‌به‌فایل

* **`inc/ab-credits.php` (جدید، ≈ ۱۳KB)** — کل منطق: `javan_seda_credits_fields()` (جدول ردیف‌ها؛ با فیلتر `javanseda_credits_fields` قابل بازشدن)، `javan_seda_credit_value()` (ACF ← post_meta)، `javan_seda_credit_terms()` (یک‌بار در هر درخواست، `get_terms` روی `singer` با `number=500`)، `javan_seda_credit_url()` (فقط `get_term_link` بعد از `is_wp_error` — همان درسی که از PHP 8 گرفتیم)، `javan_seda_credit_names()` (جداکردن چند نفر؛ « و » فقط وقتی جدا می‌شود که هر دو طرف ترمِ واقعی باشند)، `javan_seda_credits_box()`، metabox + `save_post` با nonce/`current_user_can`/`DOING_AUTOSAVE`.
  * هوک‌ها: `add_meta_boxes` (۰ پارامتر) و `save_post` — **هیچ** روی `the_content` / `template_include` / `init`. هر دو ثبت با `function_exists` پیچیده شده‌اند.
  * رندر با `<dl>` / `<dt>` / `<dd>` (ساختار معنایی برای سئو) و پالت سازمانی.
* **`single.php`** — دو خط `if ( function_exists( 'javan_seda_credits_box' ) )` : یکی داخل همان بلوکِ باکس اجتماعی (بالای پلیر) و یکی بعد از `<?php if ( '' !== $taranehyar_lyrics_box && 'end' === ... ) ... ?>` (بعد از باکس دانلود). هیچ خطی حذف نشد؛ CRLF حفظ (۶۷۹ CR).
* **`myfunctions.php`** — یک بلوک `require` با الگوی `is_readable` بعد از بلوک `ab-post-extras`.
* **`inc/theme-options.php`** — سه فیلد §۴ قبل از بستۀ `settings` (بخش `dl_pages`).
* **`style.css`** — **بلوک ۲۵** (بعد از «انتۀای بلوک ۲۴»)، `Version: 3.2.1`، صفر `gradient(`، براکت‌ها بالانس.
  * **یک باگ قدیمیِ قالب هم همین‌جا رفع شد:** `icofont-microphone-alt` در `home.php:39` و `404.php:27` استفاده شده ولی این گلیف در `css/icofont.min.css` این نسخه **وجود ندارد** (آیکن ناقص دیده می‌شد). با گلیف موجود `icofont-radio-mic` (`\ea4e`) در قالب یک خط override وصل شد. اگر خواستید، همان یک خط را بردارید.

## ۶) خاموش‌کردن‌ها

| کلید | اثر |
|---|---|
| `define( 'JAVANSEDA_CREDITS', false );` | باکس + metabox (کل لایه) |
| `define( 'JAVANSEDA_CREDITS_METABOX', false );` | فقط کادر ویرایش می‌رود؛ نمایش در سایت می‌ماند (برای کسانی که ACF دارند) |
| تنظیمات قالب → `credits_box = off` | فقط نمایش در صفحهٔ پست |
| حذف `inc/ab-credits.php` | بازگشت کامل بدون خطا |
| `JAVANSEDA_CREDITS_TAX` | پیش‌فرض `singer`؛ اگر روزی نام تاکسونومی عوض شد، همین یک ثابت |

## ۷) بررسی‌های اجراشده

* `node tools/check.js` (احراز سینتکس با `php-parser`، بدون نصب PHP روی سرور) → **OK** برای `inc/ab-credits.php` · `single.php` · `myfunctions.php` · `inc/theme-options.php`
* `python3 tools/compat-check.py` روی فایل تازه و فایل‌های دست‌خورده → **0 hit** (کف PHP 5.6؛ بدون `??`/`fn()`/`static closure`/typed)
* منطق جداکردن نام‌ها با ۷ نمونهٔ فارسی تست شد (چندنفره، «،»، «/»، «|»، نامِ دارای «و»)
* همهٔ آیکن‌های تازه در `css/icofont.min.css` موجودند (`music-alt` · `edit` · `headphone` · `record` · `camera` · `radio-mic` · `info-circle`)
* `gradient` در `style.css` = ۰ · `{`/`}` بالانس · سه سطر انتهایی `footer.php` دست‌نخورده (اصلاً تغییر نکرد)
* `inc/ab-sidebars.php`، `inc/widgets.php`، `functions.php`، `index.php`، `header.php` **بدون تغییر**

## ۸) دو پیشنهاد بعدی (اجرا نشده — بگویید تا بسازم)

1. **داده ساختاریافته**: همین شش ردیف را به `MusicRecording` در `inc/ab-seo.php` اضافه کنیم (`composer`، `lyricist` و … در schema گوگل معادل دارند) — یک لایۀ کوچکِ جدا با فیلتر `javanseda_seo_music_schema` بدون دست‌زدن به هسته.
2. **صفحۀ «آهنگسازها/تنظیم‌کننده‌ها»**: الان فقط آرشیو `singer` دارید. اگر بخواهید نام‌های غیرخواننده هم آرشیو داشته باشند، باید یک `register_taxonomy` تازه ثبت شود (تصمیمش با شماست، چون دقیقاً همان جایی است که گفته‌اید دست نخورَد؛ تا شما نگوئید من سمتش نمی‌روم و لینک‌ها همان «متن ساده» می‌مانند).
