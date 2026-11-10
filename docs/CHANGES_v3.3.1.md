# نقشۀ تغییرات — نسخۀ 3.3.1 (پچ روی 3.3.0)

تاریخ: 2026-10-18 · زنجیره: `3.2.4` → `3.3.0` → **`3.3.1`** · 3.3.0 را کامل جایگزین می‌کند (همان ۵ فایل، به‌روز).

## چرا پچ؟ (خودبازبینی بعد از انتشار 3.3.0)

دو `!important` قدیمی در `style.css` باعث می‌شد لایۀ شیشه **هرگز** به آن‌ها نرسد:

| خط | قاعدۀ قدیمی | اثرش روی کار جدید |
|---|---|---|
| ≈۱۵۰۵ | `.night #header{background: var(--ab-charcoal) !important}` | هدر در حالت شب سفتِ ذغالی می‌ماند و شیشه/مرز/هایلایت نمی‌گرفت (اختلافِ specificity: `.night #header` = ‏1,1,0 بالاتر از `#header.darkHeader`) |
| ≈۲۰۸۳ | `.ab-quickbar{background…!important; border…!important; border-radius…!important}` + `.night .ab-quickbar{background:#222222 !important}` | نوار دسترسی سریع (که قرار بود سطح شیشه‌ای باشد) کاملاً در انحصار بلوک ۲۱ می‌ماند و قانون شیشه بی‌اثر می‌شد |

## راه‌حل (ریشه‌ای، بدون انباشت override)

به‌جای نوشتن یک `!important` جدیدتر، خودِ همان اعلامیه‌ها **توکنی** شدند:

```css
.night #header{background: var(--ab-glass-bg-strong, var(--ab-charcoal)) !important;}
.ab-quickbar{
	background:var(--ab-glass-bg-strong, var(--ab-blue-soft)) !important;
	border:1px solid var(--ab-glass-border, var(--ab-line)) !important;
	border-radius:var(--ab-radius-m, 12px) !important;
}
.night .ab-quickbar{
	background:var(--ab-glass-bg-strong, #222222) !important;
	border-color:var(--ab-glass-border, rgba(255,255,255,0.08)) !important;
}
```

- `ab-glass.css` **بارگذار است، نه زورگو**: اگر فعال باشد توکن‌ها به سطح شیشه‌ای resolve می‌شوند؛ با `define('JAVANSEDA_GLASS_UI', false)` یا حذف فایل، `var()` به fallback می‌افتد و نتیجه **دقیقاً** ظاهر 3.2.4 است (`--ab-blue-soft` / `--ab-line` / `12px` / `#222222`).
- قانون تکراری `.ab-quickbar` از `ab-glass.css` حذف شد و فقط `box-shadow` (عمق) آنجا ماند ⇒ **یک منبع حقیقت**، بدون رقابت دو فایل روی یک خصوصیت.
- `Version: 3.3.0` → `3.3.1` (ویرایشگر قالب وردپرس و کش مرورگر همین عدد را ملاک می‌گیرند).
- `ab-glass.css` هم `border-radius` اسلاید را با بلوک ۲۵ هماهنگ نگه می‌دارد (هر دو `12px`) — تضادی نیست، عمداً تغییر نکرد تا کمینه بماند.

## فایل‌های تغییر‌یافته

| فایل | تغییر |
|---|---|
| `javanseda/style.css` | ۶ اعلامیۀ توکنی (۱ هدر شب + ۵ نوار دسترسی سریع) + `Version: 3.3.1` · شمارندۀ `!important` بدون تغییر (‏443) — چیزی افزوده نشد، فقط مقدارها از متغیر خوانده می‌شوند |
| `javanseda/css/ab-glass.css` | حذف بلوک تکراری `.ab-quickbar` / `.night .ab-quickbar` (۲۷۹ → ۲۷۷ خط، ۱۱٬۶۴۳ بایت) |
| بقیه ( `inc/ab-glass.php` · `404.php` · `myfunctions.php` ) | **بدون تغییر** — در زیپ 3.3.1 هم همان فایل‌های 3.3.0 هستند تا نصب کامل بماند |

`git diff --numstat` برای این پچ: `style.css` +7/−7 · `css/ab-glass.css` +5/−8.

## بررسی‌ها

`css-tree` ⇒ ۰ خطای پارس در هر دو CSS · `tools/check.js` ⇒ `404.php`/`inc/ab-glass.php`/`myfunctions.php` OK (دست‌نخورده) · `compat-check.py` ⇒ `0 hit` · `grep 'gradient(' = 0` · براکت‌های CSS بالانس · `sha1sum` بین `javanseda/*` و `drop-in/*` یکسان (۵ فایل) · هماهنگی بلوک ۲۵ و شیشه روی `.ab-vip-img` چک شد.

**هنوز اجرا نشد:** رندر واقعی/Lighthouse (مرورگر و PHP+MySQL در محیط نبود). برای دیدن اثر این پچ، دمو را با CSS همان مخزن باز کنید و «حالت شب» را بزنید: `preview/glass-ui.html` — هدر و نوار دسترسی سریع حالا در شب هم سطح شیشه‌ای دارند.
