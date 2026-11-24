# تغییرات نسخۀ 3.4.1 — «فاصلۀ پایین پلیر» + «کنترل رابط» در تنظیمات قالب

تاریخ: ۲۷ مهر ۱۴۰۵ · 2026-10-18 · بر پایۀ 3.4.0 (`af97508` + `c2183d5`)

دو خواسته در این دور انجام شد:

1. «پایین پلیر چسبیده به پایین صفحه خیلی کم است و آن چیزهایی که زیرش هست کاملاً مشخص است.»
2. «هر چیزی که لازم است از تنظیمات قالب قابل کنترل باشد — چیزهای الکی نه؛ مثلاً منوی فوتر خیلی لازمه.»

نتیجه: ۱ اصلاح ظاهریِ واقعی + **۱۹ کنترلر تازه** که هر ۱۹ تا در سورس مصرف دارند (جدول ۲ و ۳) و
هیچ‌کدام فقط تزئینی نیست. هیچ کلید تازه‌ای هم برای «تمیزی» اضافه نشد.

---

## ۱) فاصلۀ پایین پلیر — علت ریشه‌ای و رفع

سه علت با هم آن حس «چسبیدگی/بریدگی» را می‌ساختند (هیچ‌کدام با «کم‌بودن عدد margin» حل نمی‌شد):

| # | علت | کجا | رفع |
|---|------|------|------|
| ۱ | `.play-list` با مارجین منفی `margin:14px -16px **-14px**` تا لبۀ پایینیِ کارت می‌کشید و ردیف آخر له می‌شد | `style.css` بلوک ۳۳ §۷ | مارجین منفی پایین حذف شد؛ لیست **داخل** padding کارت می‌نشیند: `margin: var(--ab-player-list-gap,14px) 0 0` و `padding:10px 0 2px` |
| ۲ | کارت `overflow:hidden` داشت ⇒ هر چیزی که از لبۀ پایین بیرون می‌زد **بریده** می‌شد (نه اینکه فاصله کم باشد) | `style.css:3272` | `overflow:visible` — چون لیست دیگر بیرون نمی‌زند، گوشۀ گردِ کارت هم سالم می‌ماند |
| ۳ | قالب مادر هنوز `.music_box_player{height:80px}` دارد (`style.css:928`)؛ بلوک ۳۰ آن را جبران می‌کرد ولی اگر یک بلوک کم می‌شد، کارت در ۸۰px له می‌شد | `style.css:928` | بلوک ۳۳ حالا صریح `height:auto;min-height:0` می‌گذارد ⇒ مستقل از وجود/نبودِ بلوک‌های دیگر |

و فاصلۀ پایین «استاندارد» و **توکنی** شد:

```css
.music_box_player{ margin: 0 0 var(--ab-player-gap, 30px); }
#index_music_player{ margin: 0 0 var(--ab-player-gap, 30px); }        /* بلوک ۳۰: 26px بود */
.single_content_box .music_box_player{ margin: 26px 0 var(--ab-player-gap, 30px); }
```

پیش‌فرض `--ab-player-gap` برابر **۳۰px** است (در موبایل تا ۲۶px). از این پس همان یک عدد در
تنظیمات قالب قابل تغییر است، پس اگر باز هم کم/زیاد بود، ویرایش سورس لازم نیست.

نکته: نوار چسبانِ پایین صفحه (`.ab-sticky`، فقط در صفحۀ تکیِ آهنگ) جدا از این مورد است؛
آن `padding-bottom: env(safe-area-inset-bottom)` و `body.ab-sticky-on{padding-bottom:60px}` را
از قبل دارد و دست‌نخورده ماند. اگر «دو باکس» را در آن نوار می‌بینید:
`define( 'JAVANSEDA_STICKY_PLAYER', false )` یا خاموش‌کردن «نوار چسبان پخش» در تنظیمات قالب.

---

## ۲) منطق انتخاب: `define()` ← تنظیمات قالب ← پیش‌فرض

فایل تازهٔ **`inc/ab-controls.php`** تنها درِ ورودیِ این گزینه‌هاست و از `myfunctions.php`
*پیش از همهٔ لایه‌ها* require می‌شود. اولویت:

1. اگر در `wp-config.php` با `define()` ست شده باشد ⇒ همان، قطعی (برای سرور/CI/مهاجرت).
2. وگرنه مقدار «تنظیمات قالب» (Option Tree) ⇒ کلید `ab_*`.
3. وگرنه پیش‌فرضِ همان فایل ⇒ رفتار نسخهٔ 3.4.0.

خروجی PHP فقط دو چیز است، پس DOM پلیر (که `js/scripts.js` به ترتیب فرزندهایش وابسته است) دست
نخورده می‌ماند:

* کلاس روی `<body>`: `ab-player-compact` · `ab-player-legacy` · `ab-player-list-open` ·
  `ab-player-list-hide` · `ab-player-novol`
* یک `<style id="ab-ui-controls">` در انتهای `wp_head` (پس از همه استایل‌ها ⇒ با همان ویژگی
  برنده می‌شود، **بدون یک `!important` تازه**) که فقط ۱۲ توکن `--ab-player-*` را ست می‌کند.

تأییدشدۀ مکانیکی: ۱۲ توکنی که PHP چاپ می‌کند = ۱۲ توکنی که CSS می‌خواند (۰ بی‌مصرف، ۰ بی‌مقدار)
و ۱۹ کلید تازه همه در سورس مصرف دارند (بازرسی با اسکریپت؛ نتیجه در بخش ۵).

> اگر `inc/ab-controls.php` نصب **نباشد** (drop-in ناقص)، همهٔ نقاط مصرف با
> `function_exists()` به رفتار قبلی برمی‌گردند و `footer.php` هم مقدارهای پیش‌فرض خودش را
> دارد ⇒ سایت نمی‌شکند، فقط کنترلرها بی‌اثر می‌شوند.

---

## ۳) کنترلرهای تازه (۱۹ تا)

### بخش «کنترل رابط» (`ab_ui`) — ۱۳ کلید

| کلید | نوع | پیش‌فرض | چه کاری می‌کند / کجا مصرف می‌شود |
|------|------|------|-----------------------------------|
| `ab_glass_ui` | on-off | on | لایۀ شیشه‌ای (`css/ab-glass.css`) — `inc/ab-glass.php` (همان `JAVANSEDA_GLASS_UI`) |
| `ab_ui_guard` | on-off | on | گاردِ لایۀ CSS: اگر مارکر `ab-ui-layer` از `style.css` برود، `css/ab-ui.css` لود می‌شود — `inc/ab-ui-guard.php` |
| `ab_card_stats` | on-off | on | بازدید و میانگین رأی روی کاور کارت‌ها — `inc/songs_post.php` |
| `ab_sticky_player` | on-off | on | نوار چسبان پخش در صفحۀ تکی؛ خاموش = هیچ DOM/اسکریپتی چاپ نمی‌شود — `inc/ab-post-extras.php` |
| `ab_skip_link` | on-off | on | لینک «پرش به محتوای اصلی» — `header.php` |
| `ab_quickbar` | on-off | on | نوار دسترسی سریع زیر هدرِ صفحۀ اصلی — `home.php` |
| `ab_player_mode` | select | `card` | `card` / `compact` (کارت سبک‌تر) / `legacy` (بدون کادر و سایه، جعبۀ سفیدِ خودِ قالب) |
| `ab_player_list` | select | `btn` | فهرست آلبوم: `btn` (دکمۀ «نمایش آلبوم») / `open` (همیشه باز) / `hide` (فهرست و دکمه حذف) |
| `ab_player_volume` | on-off | on | اسلایدر صدا در پلیر |
| `ab_player_gap` | text | `30` | فاصلۀ پایین پلیر به px (۰ تا ۹۶، با کریدر در PHP) |
| `ab_player_radius` | text | `20` | گردی گوشۀ کارت پلیر (۰ تا ۴۰) |
| `ab_player_border` | on-off | on | خط دور کارت پلیر |
| `ab_player_shadow` | on-off | on | سایۀ کارت پلیر |

### بخش «پانوشت» (`footer`) — ۶ کلید

| کلید | نوع | پیش‌فرض | چه کاری می‌کند |
|------|------|------|----------------|
| `ab_footer_cols` | checkbox | هر چهار ستون | ستون‌های نمایش‌داده‌شده: درباره ما / لینک‌های مفید / دسته‌های پرطرفدار / شبکهٔ اجتماعی. ستونِ نخورده **اصلاً چاپ نمی‌شود** (نه `display:none`)؛ `footer.php` شمارۀ ستون‌های روشن را در `data-ab-cols` می‌نویسد و بلوک ۳۴ همان را به `grid-template-columns` تبدیل می‌کند ⇒ شبکه‌ی چهارستونه جای خالی نمی‌اندازد (در موبایل مثل قبل ۲/۱ ستون) |
| `ab_footer_pinned` | on-off | on | «همهٔ خواننده‌ها» و «تازه‌ترین آهنگ‌ها» در ستون لینک‌های مفید (همان دو لینکی که در 3.4.0 منتقل شدند) |
| `ab_footer_links` | textarea | خالی | لینک‌های دستیِ شما در همان ستون؛ هر خط: `عنوان|آدرس`. خطِ شروع‌شده با `#` نادیده گرفته می‌شود؛ اگر `https://` نداشته باشد افزوده می‌شود؛ خروجی با `esc_url`/`esc_html` چاپ می‌شود |
| `ab_footer_search` | on-off | on | فرم جست‌وجوی فوتر |
| `ab_footer_cats_num` | text | `8` | تعداد رده‌های ستون «دسته‌های پرطرفدار» (۰ = همه) — `get_categories(number:…)` |

### کلیدهایی که عمداً اضافه نشد (چون از قبل در تنظیمات قالب بودند)

`indpl` (پلیر ایندکس) · `v_num` (تعداد آهنگ‌ها) · `fixbvip`/`vip_num` (اسلایدر ویژه) ·
`fixbtn` (باکس شبکهٔ اجتماعیِ پست) · `upb` (دکمۀ بالارونده) · `copyright` ·
`rules_url`/`ads_url`/`contact_url` (لینک‌های سیاست‌ها) · `hty`/`arti_off`/`arti_title` (ریل هنرمندان) ·
`dark_modes`/`sun_moon` · `credits*`/`lyrics_*`. اضافه‌کردن نسخهٔ دوم این‌ها دو منبع حقیقت می‌ساخت.

**یک نکتهٔ ضروری:** بخش «کنترل رابط» پس از آپلود فایل‌ها یک‌بار به‌طور خودکار به فهرست
تنظیمات قالب اضافه می‌شود (Option Tree در `admin_init` آرایۀ تنظیمات را با مقدار سورس مقایسه و
به‌روزرسانی می‌کند). اگر بخش را ندیدید، یک‌بار «تنظیمات قالب» را باز کنید و صفحه را رفرش کنید.
مقدار هیچ کلیدی تا وقتی خودتان چیزی انتخاب نکرده‌اید در دیتابیس ذخیره نمی‌شود؛ همان
پیش‌فرض‌های جدول بالا اجرا می‌شوند.

---

## ۴) اصلاح باگِ گزارش تشخیصی (فایل `inc/ab-sidebars.php`)

`javan_seda_expected_hashes()` مقدارهای sha256 **خامِ بایت‌ها** را نگه می‌داشت، ولی
`javan_seda_file_hash()` پیش از هاش‌گرفتن خط‌پایان را نرمال می‌کند (`\r\n → \n`).
نتیجه: برای هر فایلِ CRLF ( `single.php` · `page.php` · `search.php` · `inc/tarlanweb_player.php`
و …) پروبِ عیب‌یابی از نسخهٔ 3.3.9 تا حالا «ناسازگار» گزارش می‌کرد، درحالی‌که فایل‌ها سالم بودند.

* همهٔ ۱۵ مقدار با فرمول درست (`normalized`) بازمحاسبه و به‌روز شد؛
* دو کلید `inc/ab-controls.php` و `inc/theme-options.php` افزوده شد ⇒ ۱۷ مورد؛
* خودسنجی روی درخت جاری: **۱۷ سازگار / ۰ ناسازگار**.

---

## ۵) اعتبارسنجی انجام‌شده

| بررسی | ابزار | نتیجه |
|--------|--------|--------|
| پارس PHP تمام فایل‌ها | `tools/check.js` (php-parser) | **57 OK / 0 FAIL** (شامل `inc/ab-controls.php` تازه) |
| پارس CSS | css-tree | `style.css` ✓ · `css/ab-ui.css` ✓ · `css/ab-glass.css` ✓ |
| حش‌ها/توکن‌های محدودکنندۀ سازگاری | `tools/compat-check.py` | **0 hit** |
| گرادینت | `grep -c 'gradient('` | **0** |
| `!important` در بلوک‌های تازهٔ ۳۳/۳۴ | regex روی ناحیه | **0** (تنها `display:none !important` موروثی خط 1459 دست‌نخورده است) |
| همۀ کلیدهای تازه مصرف دارند | اسکریپت تطبیق `theme-options.php` ⇄ `inc/*` ⇄ قالب | **19/19**، ۰ بی‌مصرف |
| تقارن توکن‌ها | `--ab-player-*` چاپیِ PHP ⇄ خواندنیِ CSS | **12 ⇄ 12**، هیچ‌کدام بی‌مصرف/بی‌مقدار نیست |
| همۀ توکن‌های پایه حفظ شدند | بررسی ۲۰ توکن `--ab-*` | ✓ |
| تراز تگ‌های `footer.php` | شمارش `<div>`/`</div>`، `<ul>` | ۱۶/۱۶ و ۳/۳ ✓ |
| تراز تگ‌های صفحۀ دمو | شمارش تگ‌های جفت‌شونده | **317/317**، `div` ۱۴۲/۱۴۲ ✓ |
| `css/ab-ui.css` هم‌خوان با `style.css` | `tools/ab-ui-sync.py` | 73,685 بایت، نسخۀ 3.4.1 ✓ |
| بایت‌به‌بایت بودن drop-in | مقایسۀ `drop-in/*` با `javanseda/*` | ✓ |
| `\r\r\n` یا `\"` سرایند در فایل‌های ویرایش‌شده | grep | **0** |

**آنچه در این سندباکس تست‌ناپذیر بود و باید روی هاست چشمی ببینید** (صریح):
رندر واقعی مرورگر (فاصلۀ ۳۰px، لیست بریدهنشده، `accent-color` اسلایدر صدا در Safari)،
رفتار `slideToggle` پس از انتخاب «فهرست: باز»، رندر `select`/`checkbox` در فرم Option Tree،
و کشِ بهینه‌ساز/CDN (پس از آپلود یک‌بار hard-refresh و پاک‌کردن کش لازم است).

---

## ۶) فایل‌ها

| فایل | وضعیت | دریافت |
|------|---------|--------|
| `javanseda/inc/ab-controls.php` | **تازه** — درِ ورودی گزینه‌ها (+ شمارش ستون فوتر) | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/javanseda/inc/ab-controls.php) |
| `javanseda/style.css` | بلوک ۳۳ توکنی + رفع فاصله/بریدگی + بلوک ۳۴ تازه · `Version: 3.4.1` | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/javanseda/style.css) |
| `javanseda/css/ab-ui.css` | بازتولید از style.css (گارد) | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/javanseda/css/ab-ui.css) |
| `javanseda/inc/theme-options.php` | بخش «کنترل رابط» + ۱۹ فیلد | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/javanseda/inc/theme-options.php) |
| `javanseda/myfunctions.php` | require `inc/ab-controls.php` (اول از همه) | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/javanseda/myfunctions.php) |
| `javanseda/footer.php` | ستون‌ها/لینک‌ها/جست‌وجو/تعداد رده‌ها ⇄ گزینه‌ها | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/javanseda/footer.php) |
| `javanseda/header.php` | گیتِ لینک «پرش به محتوا» | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/javanseda/header.php) |
| `javanseda/home.php` | گیتِ نوار دسترسی سریع | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/javanseda/home.php) |
| `javanseda/inc/ab-glass.php` | گیتِ لایۀ شیشه‌ای ⇄ `ab_glass_ui` | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/javanseda/inc/ab-glass.php) |
| `javanseda/inc/ab-ui-guard.php` | گیتِ گارد ⇄ `ab_ui_guard` | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/javanseda/inc/ab-ui-guard.php) |
| `javanseda/inc/songs_post.php` | گیتِ آمار کارت ⇄ `ab_card_stats` | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/javanseda/inc/songs_post.php) |
| `javanseda/inc/ab-post-extras.php` | گیتِ نوار چسبان ⇄ `ab_sticky_player` | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/javanseda/inc/ab-post-extras.php) |
| `javanseda/inc/ab-sidebars.php` | اصلاح ۱۵ هاش + ۲ کلید تازه | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/javanseda/inc/ab-sidebars.php) |
| `preview/glass-ui.html` | دکمه‌های امتحان حالت‌های پلیر (فقط دمو) | [raw](https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.4.1/preview/glass-ui.html) |

بسته‌ها: `artifacts/drop-in-v3.4.1.zip` (۱۴ فایل) · `artifacts/rollback-v3.4.1.zip`
(نسخۀ 3.4.0 همان فایل‌ها با پسوند `.3.4.0`) · `artifacts/javanseda-v3.4.1-theme.zip` (کل قالب).

## ۷) نصب و بازگشت

* **نصب:** همان روال قبل — محتویات `drop-in-v3.4.1.zip` را با همان ساختار داخل
  `wp-content/themes/javanseda/` بریزید (۱۳ فایل؛ یک فایل تازه و ۱۲ جایگزین). سپس یک‌بار
  «تنظیمات قالب» را باز کنید تا بخش «کنترل رابط» ظاهر شود.
* **بازگشت ظاهری (بدون دست‌زدن به فایل):** در «تنظیمات قالب ← کنترل رابط»،
  «شکل پلیر» = کارت، «فهرست آلبوم» = دکمه، «فاصلۀ پایین پلیر» = ۳۰، و همه روشن ⇒ دقیقاً 3.4.0.
* **بازگشت کامل:** `rollback-v3.4.1.zip` — فایل‌های با پسوند `.3.4.0` را روی نام بدون پسوند
  کپی کنید و فایل `inc/ab-controls.php` را حذف کنید (هیچ فایل دیگری حذف نمی‌شود).
* **خاموش‌کردن یک‌جای همهٔ گزینه‌ها از سرور:** برای CSS خالص، حذف «بلوک ۳۴» از انتهای
  `style.css` حالت‌ها را بی‌اثر می‌کند؛ برای PHP، `define( 'JAVANSEDA_GLASS_UI', false )` و
  برادرانش همچنان کار می‌کنند و بر تنظیمات قالب اولویت دارند.

## ۸) پیشنهاد بعدی (انجام نشده)

`select` و `checkbox` در Option Tree این قالب پشتیبانی می‌شوند (`ot_type_select` /
`ot_type_checkbox` در `inc/option-tree/includes/ot-functions-option-types.php`)، ولی رندر
فرم آن‌ها را در این سندباکس ندیده‌ام؛ اگر در پیشخوان نمایش داده نشدند، بگویید تا همان دو
گزینۀ چندحالتي را به سه کلید on-off بشکنم (بدون تغییر در منطق CSS).
