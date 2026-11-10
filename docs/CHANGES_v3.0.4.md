# مستند نسخهٔ 3.0.4 — رفع قطعی «نمایش هدر در موبایل» (ضدّ کش)

شاخه: `arena/aa59ef50-javan-seda` · تگ: `v3.0.4` · PR #3

---

## ۱. شهادت از روی هاست (چرا با رغم ۳.۰.۳ هنوز هدر موبایل خراب بود)

فایل `style.css` روی `ahangban.ir` مستقیماً خوانده شد. آخرین قاعدهٔ فایل، بلوک
«تنظیم دقیق هدر دسکتاپ» (`#header .dark_light_mode…`) است؛ یعنی فایل روی سرور همان
**نسخهٔ 3.0.1** است:

| آنچه روی هاست هست | آنچه باید می‌آمد |
| :--- | :--- |
| `Version: 3.0.1` | `Version: 3.0.4` |
| ندارد | بلوک ۲۰ (پایداری سایدبار/ابزارک) |
| ندارد | بلوک ۲۱ (قاعدهٔ نمایش هدر موبایل با کلاس `ab-has-mh`) |

و چون رفعِ نسخهٔ ۳.۰.۳ِ هدر **فقط در CSS** بود، تا آن CSS نرسد، هدر موبایل درست نمی‌شود.
دو حالت محتمل: فایل `style.css` آپلود نشده، یا CDN/LiteSpeed نسخهٔ کهنهٔ فایل استاتیک را
سرو می‌کند (پاک‌سازی کش استاتیک با پاک‌سازی کشِ HTML فرق دارد).

## ۲. دو باگ واقعی که در ۳.۰.۳ باقی بود

1. **شکنندگی نسبت به کش:** رفع در `style.css` بود؛ با یک فایل CSS کهنه، کل رفع بی‌اثر می‌شود.
2. **۵ پیکسل پوشیده‌شدن محتوا:** نوار هدر موبایل `height:65px` است ولی `body{padding-top:60px}`
   (خط inline قدیمیِ `header.php`) فقط ۶۰ پیکسل فاصله می‌داد ⇒ ۵px از بالای محتوا زیر نوار
   `fixed` می‌رفت و در بسیاری قالب‌ها «هدر روی محتوا افتاده» به‌نظر می‌رسید.

## ۳. رفع در ۳.۰.۴

### الف) `header.php` — CSS حیاتی به‌صورت inline در `<head>`
```html
<style id="ab-header-critical">
body{padding-top:0;}
body.ab-has-mh{padding-top:65px;}                                   /* = ارتفاع نوار (رفع ۵px) */
body.ab-has-mh #mobiles_header{display:block!important;visibility:visible!important;
                               height:65px!important;overflow:visible!important;}
body:not(.ab-has-mh) #mobiles_header{display:none!important;}
body.ab-has-mh #header{display:none!important;}
#header{margin-bottom:28px!important;}                              /* قانون ۵: ≥ ۲۸px */
@media (min-width:801px){ body.ab-has-mh #mobiles_header{display:block!important;} }
@media (max-width:800px){ body:not(.ab-has-mh) #header{display:block!important;padding:12px 0!important;} }
</style>
```
* این بلوک **بعد از** `wp_head()` چاپ می‌شود ⇒ بعد از لینک `style.css` در سند می‌آید و با
  همان `!important`ها بر هر نسخهٔ کهنه‌ای از استایل قالب غلبه می‌کند. حتی اگر `style.css`
  هرگز آپلود نشود، هدر موبایل درست نمایش داده می‌شود.
* خط قدیمی `<?php if(wp_is_mobile('')){ ?><style>body{padding-top:60px}</style><?php } ?>`
  حذف و جای خود را به همین بلوک داد (تک‌منبع، بدون تزاحم).
* ساختار رندر همان ۳.۰.۳ است: `#header` روی دسکتاپ، `inc/mobiles_header` فقط روی موبایل،
  و کلاس `ab-has-mh` روی `<body>` وقتی هدر موبایل رندر شده. ⇒ قانون ۵ (رندر فقط روی موبایل) حفظ است.

### ب) `style.css` — بلوک ۲۱ هم‌سان‌سازی شد
`padding-top:60px` → `65px` و یادداشت اینکه همین قاعده‌ها به‌صورت inline در `header.php` هم
هستند. `Version: 3.0.4`. براکت ۹۵۰/۹۵۰، **گرادینت ۰**.

### ج) `inc/ab-sidebars.php` — گزارش تشخیصی
بخش تازهٔ **«۸) هدر (دسکتاپ / موبایل)»** به `?ab_sidebars_diag=1` اضافه شد که چهار چیز را
می‌سنجد و نتیجه را فارسی می‌گوید:
* مقدار `wp_is_mobile()` برای همان درخواست؛
* آیا `header.php` روی هاست خطِ رندر `mobiles_header` را دارد؛
* آیا `header.php` بلوک `ab-header-critical` را دارد (نسخهٔ ضدّ‌کش نصب است یا نه)؛
* نسخهٔ `style.css` روی هاست + آیا بلوک `ab-has-mh` را دارد + `mtime` هر دو فایل.

به‌علاوه مانیفست هش ۳.۰.۴ به‌روز شد (تا «متفاوت از بسته» یعنی دقیقاً همین که فایل روی هاست
کهنه است). منطق سایدبار/ابزارک تغییری نکرد؛ هوک‌ها همان سه مورد قبلی‌اند
(`widgets_init`، `sidebars_widgets`، `template_include`).

## ۴. نصب — حداقلِ لازم فقط یک فایل است

| اولویت | فایل | لینک |
| :---: | :--- | :--- |
| ۱ | `wp-content/themes/javanseda/header.php` | `drop-in/header.php` |
| ۲ (توصیه‌شده) | `wp-content/themes/javanseda/style.css` | `drop-in/style.css` |
| ۲ (اگر ۳.۰.۲ را نزده‌اید) | `inc/ab-sidebars.php` + `myfunctions.php` + `single.php` + `page.php` | `drop-in-v3.0.4.zip` |

مراحل: آپلود ← پاک‌سازی **کش استاتیک + CDN** (نه فقط کش HTML) ← بازکردن سایت با عرض موبایل.

### چک‌لیست تأیید (دو دقیقه)
1. در موبایل (یا DevTools ← device mode) صفحه را باز کنید؛ هدر باید نوار ۶۵ پیکسلی با
   آیکون منو و جست‌وجو باشد و محتوا از زیر آن شروع شود (نه ۵px زیر نوار برود).
2. `View source` ← باید هر دو مورد را ببینید: `<body class="… ab-has-mh">` و `<header id="mobiles_header">`.
   روی دسکتاپ برعکس: `ab-has-mh` نیست و `#header` هست (قانون ۵).
3. `View source` ← `ab-header-critical` باید در `<head>` پیدا شود؛ اگر نبود، `header.php` عوض نشده است.
4. آدرس هر صفحه + `?ab_sidebars_diag=1` ← بخش ۸ باید چهار «بله» بدهد.

رول‌بک: `artifacts/rollback-v3.0.4.zip` (نسخهٔ ۳.۰.۳ و همچنین `header.php` خودِ شما بدون تغییر).

## ۵. کنترل‌ها

| کنترل | نتیجه |
| :--- | :--- |
| php-parser | `header.php` · `inc/ab-sidebars.php` (و `javanseda/` و `drop-in/`) → **PARSE OK** |
| تقارن تگ‌ها در `header.php` | `header` ۱/۱ · `div` ۶/۶ · `nav` ۱/۱ · `form` ۱/۱ · `style` ۱/۱ |
| صفر بایت پیش از خروجی | بلوک inline داخل `<head>` است؛ پیش از `<!DOCTYPE html>` هیچ چیزی افزوده نشد |
| سه مورد فوتر | `footer.php` دست‌نخورده |
| گرادینت / براکت `style.css` | ۰ گرادینت · ۹۵۰/۹۵۰ |
| پالت | `#FFFFFF` / `#185ADB` / `#111111` فقط |
| تاکسونومی `singer` و هوک‌های سراسری | بدون تغییر |
| هم‌سانی `javanseda/` و `drop-in/` | ۸ فایل = یکسان (هش تأیید شد) |

## ۶. توصیهٔ جانبی
فایل `header.php` در **ریشهٔ مخزن** (روی `main`) همان نسخهٔ ناقص است و وردپرس هرگز آن را نمی‌خواند.
بعد از ادغام PR، آن را با `drop-in/header.php` جایگزین یا حذف کنید تا منبعِ گیج‌کنندهٔ دوم باقی نماند.
