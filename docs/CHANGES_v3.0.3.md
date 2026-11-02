# مستند نسخهٔ 3.0.3 — بازگردانی هدر موبایل (و قاعدهٔ «هیچ‌وقت بدون هدر نمان»)

مخزن: `Masoumiofficial/javan-seda` · شاخهٔ کاری: `arena/aa59ef50-javan-seda` · تگ: `v3.0.3`

---

## ۱. علت دقیق: یک خط حذف‌شده، نه CSS و نه هوک

فایل نهاییِ هدر که روی سایت گذاشته شده (`header.php` در ریشهٔ مخزن، کامیت `ce1e16fb` با عنوان
«Add files via upload») با `javanseda/header.php` نسخهٔ ۳.۰.۱ **فقط در یک خط** فرق دارد:

```diff
  </header>
  <?php } ?>
- <?php get_template_part('inc/mobiles_header'); ?>   ← این خط حذف شده بود
  <main>
```

و چون در همان فایل، هدر دسکتاپ مشروط است:

```php
<?php if(wp_is_mobile()){} else{ ?> <header id="header"> … </header> <?php } ?>
```

نتیجه روی موبایل: هدر دسکتاپ **رندر نمی‌شود** (چون `wp_is_mobile()` true است) و هدر موبایل هم
**دیگر هیچ‌جا صدا زده نمی‌شد** ⇒ صفحه «بدون هدر» می‌ماند. ضمناً `<style>body{padding-top:60px}</style>`
همان ۶۰ پیکسل فضای خالی بالای صفحه را روی موبایل جا می‌گذارد — چیزی که احتمالاً دیده‌اید.

> قانون ۵ می‌گفت «`mobiles_header` فقط روی موبایل رندر شود»؛ اجرای درستِ آن **رندر مشروط** است،
> نه حذف کردنِ خط. این نسخه همان را انجام می‌دهد.

## ۲. تغییرات این نسخه

### الف) `header.php` (دو هاک، بقیهٔ فایل کلمه‌به‌کلمه مثل فایل شما)
```php
/* v3.0.3 — تک‌منبع تشخیص موبایل */
$ab_mobile_ua = wp_is_mobile();
$darks = ot_get_option('dark_modes');
if ( 'off' != $darks ) { ?>
<body <?php body_class( $ab_mobile_ua ? 'night ab-has-mh' : 'night' ); ?>>
<?php } else { ?>
<body <?php body_class( $ab_mobile_ua ? 'ab-has-mh' : '' ); ?>>
<?php } ?>
<?php if ( ! $ab_mobile_ua ) { ?> … <header id="header"> … <?php } ?>
<?php if ( $ab_mobile_ua ) { get_template_part( 'inc/mobiles_header' ); } ?>
<main>
```
* هدر موبایل **برگشت** و فقط روی موبایل رندر می‌شود (قانون ۵، این‌بار درست).
* کلاس `.ab-has-mh` روی `<body>` نشانهٔ «هدر موبایل رندر شد» است و CSS بر همین اساس تصمیم می‌گیرد.
* هدر دسکتاپ همان `#header` قبلی است؛ ساختار، منو، جست‌وجو و سوییچ شب/روزدست‌نخورده.
* صفر بایت اضافه پیش از `<!DOCTYPE html>`؛ `wp_head()` و جای آن دست‌نخورده.

### ب) `style.css` — بلوک ۲۰ و ۲۱ (فقط این دو بلوک به نسخهٔ ۳.۰.۲ افزوده/اصلاح شد)
```css
body.ab-has-mh #mobiles_header{display:block !important;visibility:visible !important;height:65px !important;overflow:visible !important;}
body:not(.ab-has-mh) #mobiles_header{display:none !important;visibility:hidden !important;height:0 !important;overflow:hidden !important;}
body.ab-has-mh{padding-top:60px;}
body:not(.ab-has-mh){padding-top:0 !important;}
@media (max-width: 800px){
	body:not(.ab-has-mh) #header{display:block !important;padding:14px 0 !important;}
	body:not(.ab-has-mh) #header .menu_right{max-width:60% !important;}
}
#header{margin-bottom:28px !important;}
body.ab-has-mh #header{display:none !important;}
```
چه چیزی را نجات می‌دهد:
1. **موبایل واقعی** ← `.ab-has-mh` فعال است: هدر موبایل در هر عرضی دیده می‌شود (قبلاً فقط زیر ۸۰۰px و اگر رندر می‌شد).
2. **کاربر دسکتاپ با پنجرهٔ باریک** یا **موبایل با «نسخهٔ دسکتاپ»** ← هدر موبایل رندر نمی‌شود؛
   قاعدهٔ قدیمیِ قالب (`@media(max-width:800px){#header{display:none}}`) عملاً خنثی می‌شود و
   **هدر دسکتاپ باقی می‌ماند**. با این قانون، سایت هیچ‌وقت بدون هدر نمی‌ماند.
3. **فاصلهٔ ۶۰ پیکسلی مزاحم** فقط وقتی اعمال می‌شود که هدرِ `fixed` واقعاً وجود دارد.
4. فاصلهٔ هدر دسکتاپ از بخش زیرین: `28px` (قانون ۵) تأیید و تثبیت شد.

### ج) `inc/ab-sidebars.php`
* به‌روزرسانی مانیفست هش (تا `?ab_sidebars_diag=1` فایل‌های ۳.۰.۳ را «مطابق بسته» بشناسد) و
  عنوان نسخه. منطق سایدبار تغییر نکرد.

## ۳. نصب (فقط دو فایل لازم است)

| اولویت | فایل مقصد در هاست | فایل منبع |
| :---: | :--- | :--- |
| ۱ | `wp-content/themes/javanseda/style.css` | `drop-in/style.css` |
| ۲ | `wp-content/themes/javanseda/header.php` | `drop-in/header.php` |

اگر فایل‌های ۳.۰.۲ را هنوز نساخته‌اید، کل `drop-in-v3.0.3.zip` (۶ فایل) را با همان ترتیب سند
`CHANGES_v3.0.2.md` بریزید. **بعد از آپلود کش و CDN را کامل پاک کنید** و سایت را با عرض موبایل
(DevTools ← Toggle device) و با «نسخهٔ دسکتاپ» چک کنید.

رول‌بک: `artifacts/rollback-v3.0.3.zip` شامل نسخهٔ ۳.۰.۲ از `style.css` و **همان `header.php` خودِ شما**
(بدون تغییر) تا در صورت نیاز دقیقاً به عقب برگردید.

## ۴. نتایج کنترل

| کنترل | نتیجه |
| :--- | :--- |
| تجزیهٔ PHP (php-parser) | `header.php` · `inc/ab-sidebars.php` · `myfunctions.php` · `single.php` · `page.php` → همه **PARSE OK / ۰ خطا** |
| تقارن تگ‌ها در `header.php` | `header` ۱/۱ · `div` ۶/۶ · `nav` ۱/۱ · `form` ۱/۱ · `<main>` و `<body>` و `<html>` عمداً در `footer.php` بسته می‌شوند |
| صفر بایت پیش از خروجی | فایل با `<!DOCTYPE html>` شروع می‌شود |
| سه مورد انتهای فوتر | `footer.php` اصلاً تغییر نکرد (`wp_footer('')` · `</body>` · `</html>`) |
| براکت‌های `style.css` | ۹۵۰ باز / ۹۵۰ بسته |
| گرادینت در کل قالب | **۰** |
| پالت | `#FFFFFF` / `#185ADB` / `#111111` (+ متغیرهای موجود) |
| تاکسونومی `singer` و هوک‌های سراسری | بدون تغییر (بدون `init`، بدون `register_taxonomy` تازه) |
| مانیفست هش | ۸ فایل = مطابق، هم در `javanseda/` و هم در `drop-in/` |

## ۵. دو نکتهٔ قابل توجه

1. فایل `header.php` که در **ریشهٔ مخزن** روی شاخهٔ `main` آپلود شده، فایلِ مصرفیِ وردپرس نیست
   (وردپرس `wp-content/themes/javanseda/header.php` را می‌خواند). بعد از ادغام PR، همان کپی ریشه را
   با `drop-in/header.php` جایگزین یا حذف کنید تا دو نسخهٔ متفاوت در مخزن نماند. (در این شاخه آن را
   دست نزدم تا PR #3 با تضاد add/add روبه‌رو نشود.)
2. اگر می‌خواهید `mobiles_header` از نظر سئو/DOM هم روی دسکتاپ صفر باشد، همین نسخه آن را می‌دهد
   (روی دسکتاپ اصلاً رندر نمی‌شود) — بدون آنکه هدر موبایل حذف شود.
