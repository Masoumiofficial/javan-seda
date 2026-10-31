# گزارش آزمون نسخهٔ ۳.۷.۶

تاریخ: ۲۰۲۶‑۱۰‑۲۸ · محیط: PHP 8.4.1 (WASM) با شبیه‌ساز وردپرس

## خلاصه

| آزمون | سنجه | نتیجه |
|---|---|---|
| `tests/run-352.php` | ۶۸ | ✅ pass |
| `tests/run-353.php` | ۴۷ | ✅ pass |
| `tests/run-354.php` | ۵۰ | ✅ pass |
| `tests/run-360.php` | ۳۴ | ✅ pass |
| `tests/run-360-panel.php` | ۶۸ | ✅ pass |
| `tests/run-370.php` | ۱۶۲ | ✅ pass |
| **مجموع** | **۴۲۹** | **۰ خطا** |

سینتکس PHP: `node tools/php-lint.mjs taranehyar` ⇒ `files=76 ok=76 bad=0`
اثر انگشت فایل‌ها: `python3 tools/expected-hashes.py` ⇒ `21 مطابق، 0 نیازمند بازنویسی`

## سنجه‌های تازهٔ این نسخه (۱۱ مورد)

**پلیر و پروتکل**
- `http://` روی سایت HTTPS به `https://` ارتقا می‌یابد
- آدرس `//host/...` به پروتکل همان سایت تبدیل می‌شود
- روی HTTP دست‌نخورده می‌ماند

**لایهٔ تضمینی**
- بلوک CSS درون‌خطی روی `wp_head`
- مود شب صریح (`body.night` + `html[data-tara-night]`)
- نگهبان CSS (`wp_style_is`) و نگهبان JS (`wp_script_is`)

**جست‌وجو**
- ثبت نقطهٔ پایانی AJAX و اسکریپت درون‌خطی
- سازگاری با افزونهٔ SearchWP (`window.SearchWP`)
- استایل کادر نتایج (`#tara-search-results` + `is-open`)

**تنظیمات**
- چیدمان دوستونی: `grid-template-columns: minmax(170px, 232px) minmax(0, 1fr)`

## آزمون رفتاری (jsdom، خارج از مخزن)

- تایپ در `#lsds` ⇒ دراپ‌داون باز می‌شود، ۱ نتیجه + «همهٔ نتایج» رندر می‌شود،
  کادر داخل `#searches` قرار می‌گیرد.
- مود شب: کلیک روی آیکون ماه ⇒ `body.night` + `localStorage`؛ کلیک دوم ⇒ برگشت.
- دکمهٔ بالا: اسکرول ⇒ `.is-shown`؛ کلیک ⇒ `scrollTo(0)`.
- تنظیمات: تب‌ها، آکاردئون و «باز/بستن همه» درست کار می‌کنند.
