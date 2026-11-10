# نقشۀ تغییرات — نسخۀ 3.2.3 (روی 3.2.2)

تاریخ: 2026-10-17 · زنجیره: `3.1.4` → `3.2.0` → `3.2.1` → `3.2.2` → **`3.2.3`**

خواستۀ شما: باکس «شناسنامۀ آهنگ» **افقی** شود، **حالت باکس‌باکسِ تفکیک‌شده** بگیرد و **زیاد فضا نگیرد**.

## ۱) چه چیزی عوض شد (فقط ۲ فایل — بدون فایل تازه، بدون تغییر داده)

| فایل | تغییر | نتیجۀ قابل مشاهده |
|---|---|---|
| `inc/ab-credits.php` | نشانه‌گذاری باکس: از `<header> + <dl>/<dt>/<dd>` (ستونی) به **تیتر درون‌خطی + ردیف چیپ** تبدیل شد. هر ردیف حالا `<span class="ab-credit">` است که داخلش: آیکن + `<b class="ab-credit__k">برچسب</b>` + `<span class="ab-credit__v">مقدار(ها)</span>` (لینک همان‌جا و با همان منطق ترم `singer`) | ارتفاع باکس از ≈ ۲۱۰px (۶ خط) به ≈ ۴۴px (یک خط) رسید |
| `style.css` | **بلوک ۲۵ بازنویسی شد** (قواعد قدیمی `.ab-credits__head/.ab-credits__rows/.ab-credit dt/.ab-credit dd` حذف؛ چیزی جا نمی‌ماند) · `Version: 3.2.3` | تیتر + چیپ‌ها در یک خط flex با `flex-wrap`؛ چیپ = کادر سفید با `border-radius:999px` روی نوار کم‌رنگ `#f7f9fd`؛ هاور: `border-color:#185ADB` + سایهٔ آبی ملایم |

```css
.ab-credits{display:flex;align-items:center;flex-wrap:wrap;gap:6px 8px;padding:8px 11px;
            background:#f7f9fd;border:1px solid #eef1f6;border-radius:14px}
.ab-credit{display:inline-flex;align-items:center;padding:4px 10px;background:#FFFFFF;
           border:1px solid #e6ebf3;border-radius:999px;box-shadow:0 1px 1px rgba(17,17,17,.03)}
```

* پالت عوض نشد: `#FFFFFF · #f7f9fd · #e6ebf3 · #8a92a6 · #111111 · #185ADB` — **صفر گرادینت**.
* `@media (max-width:767px)`: تیتر به خط اول می‌رود (`width:100%`) و چیپ‌ها می‌شکنند ⇒ بدون اسکرول افقی، بدون سرریز متن.
* `@media print{.ab-credits{display:none}}` و override آیکن `icofont-microphone-alt` دست‌نخورده ماندند.

## ۲) چیزی که عوض **نشد** (و چرا)

* داده/ذخیره‌سازی: همان ۵ کلید (`composer · lyricist · arranger · mixer · producer`) + همان metabox و گزینه‌های تنظیمات قالب ⇒ هیچ مهاجرت داده لازم نیست و نوشته‌های واردشدهٔ قبلی همان‌جا هستند.
* منطق لینک‌سازی (`javan_seda_credit_url`)، منطق جداکردن چند نفر، باکس اجتماعی، نوار چسبان، schema، `single.php`، `footer.php`، `inc/ab-seo.php`، `inc/ab-perf.php` ⇒ بی‌تغییر.
* `<h2>` تیتر حذف نشد؛ فقط درون‌خطی شد (ساختار heading صفحه به‌هم نمی‌خورد).

## ۳) پیش‌نمایش قبل از نصب

`preview/credits.html` (همین مخزن) — با CSS واقعیِ همین نسخه رندر می‌شود؛ چهار حالت را کنار هم نشان می‌دهد: پر، فقط-خواننده، چند‌نفره/بدون‌آرشیو، و مقایسه با نسخۀ ۳.۲.۱. برای دیدن رفتار موبایل، پنجره را باریک کنید.

## ۴) نصب / بازگشت

* فایل‌ها (جایگزینی در `wp-content/themes/javanseda/`):
  * https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.3/drop-in/inc/ab-credits.php
  * https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.3/drop-in/style.css
* زیپ: `artifacts/drop-in-v3.2.3.zip` · بازگشت: `artifacts/rollback-v3.2.3.zip` (دو فایل `*.3.2.2`) · کل قالب: `artifacts/javanseda-v3.2.3-theme.zip`

## ۵) بررسی‌ها

* `tools/check.js` → `OK` روی `inc/ab-credits.php` (۲۹ گرهٔ سطح‌بالا) و `single.php`
* `tools/compat-check.py javanseda` → **`0 hit`** (کل قالب، کف PHP 5.6)
* `style.css`: صفر `gradient(` · براکت‌ها بالانس · صفر بازماندۀ `dt/dd` یا `__head/__rows` در CSS · بلوک ۲۴ (اجتماعی) و override آیکن سالم
* `drop-in/` با `javanseda/` هم‌sha (۹ فایل چک شد)
* اجرا **نشدہ**: رندر واقعی وردپرس (در این سندباکس PHP/MySQL نیست) ⇒ ظاهر نهایی را روی هاست ببینید؛ پیش‌نمایش محلی بالا همان CSS را استفاده می‌کند.
