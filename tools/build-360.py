#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
build-360.py — ساختِ دو بستهٔ نسخهٔ ۳.۶.۰ «ترانه یار»

تفاوت مهم با build-354.py:
  • قالب دیگر هیچ فایلِ رمزگذاری‌شده‌ای ندارد. پس `functions.php` **داخل** بستهٔ
    کامل هست (نسخهٔ ۳.۵.x آن را عمداً بیرون می‌گذاشت چون با ionCube رمز شده بود).
  • فایل `RTL_License_*.php` حذف شده است؛ ابزار اگر دوباره سرکشی کند، می‌شکند.
  • نام پوشه هم `taranehyar` است.

خروجی:
  artifacts/taranehyar-3.6.0.zip     کل قالب، آمادهٔ نصب، بدون فایل لایسنس
  artifacts/drop-in-v3.6.0.zip       ۸ فایلِ عوض‌شده/تازه، برای به‌روزرسانیِ دستی

هر دو بسته: CRC سالم، بدون فایلِ آزمون/ابزار/مستندات، بدون ردی از ICB0.

استفاده: python3 tools/build-360.py [--version 3.6.0]
"""

import argparse
import hashlib
import io
import os
import re
import sys
import zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
THEME = 'taranehyar'
THEME_ABS = os.path.join(ROOT, THEME)

EXCLUDE_DIRS = {'.git', 'node_modules', '.svn', '.idea', '__pycache__', 'tests', 'docs', 'artifacts'}
EXCLUDE_FILES = {'.DS_Store', 'Thumbs.db', '.gitignore', 'README-3.6.0.txt'}

# فایل‌هایی که نسبت به ۳.۵.۴ عوض یا تازه‌اند (همان فهرستِ drop-in)
CHANGED = [
    'functions.php',
    'style.css',
    'inc/ab-admin-ui.php',
    'inc/ab-sidebars.php',
    'inc/admin-panel.php',
    'inc/admin-panel-views.php',
    'css/admin-panel.css',
    'js/admin-panel.js',
]

FULL_README = """ترانه یار ۳.۶.۰ — بستهٔ کامل قالب
=================================

این بسته کامل است و همهٔ فایل‌های قالب را دارد؛ از جمله functions.php خوانا.
هیچ فایل رمزگذاری‌شده و هیچ فایل لایسنس در آن نیست و قالب به هیچ سرور بیرونی
وصل نمی‌شود.

نصب تازه:
  ۱) محتوای zip را در wp-content/themes/ باز کنید؛ پوشهٔ taranehyar/ ساخته می‌شود.
  ۲) پیشخوان → نمایش → پوسته‌ها → «ترانه یار» را فعال کنید.
  ۳) پیشخوان → منوی «ترانه یار» → تنظیمات قالب.

به‌روزرسانی از نسخه‌های پیشین:
  ۱) پوشهٔ taranehyar/ را کنار پوشهٔ قدیمی آپلود کنید.
  ۲) قالب را از «نمایش → پوسته‌ها» به ترانه یار عوض کنید.
  ۳) فایل RTL_License_41d6c5f704e3a746.php و اگر ماند، پوشهٔ قدیمی قالب را پاک کنید.
  ۴) کش سایت (و افزونهٔ کش) را خالی کنید.

هیچ تغییری در دیتابیس لازم نیست: تنظیمات همان کلید option_tree است و همهٔ
گزینه‌های نسخه‌های ۳.۵.x سر جایشان می‌مانند.
"""

DROPIN_README = """ترانه یار ۳.۶.۰ — بستهٔ به‌روزرسانیِ ۸ فایلی (drop-in)
=====================================================

این بسته فقط فایل‌های عوض‌شده/تازهٔ ۳.۶.۰ است. هیچ فایل لایسنس یا رمزگذاری‌شده‌ای
ندارد.

چرا فقط این هشت فایل کافی است؟ چون بقیهٔ فایل‌های قالب بین ۳.۵.۴ و ۳.۶.۰ بیت‌به‌بیت
یکسان‌اند (به‌جز حذف فایل لایسنس و تغییر نام پوشه).

پیش از هر چیز: نام پوشهٔ قالب باید taranehyar باشد. اگر نصب فعلی با نام پوشهٔ قدیمی است،
یا پوشه را تغییر نام دهید یا بستهٔ کامل taranehyar-3.6.0.zip را آپلود کنید.

نصب:
  ۱) محتوای این zip را روی پوشهٔ wp-content/themes/taranehyar/ باز کنید (Overwrite).
  ۲) فایل RTL_License_41d6c5f704e3a746.php را اگر هست، حذف کنید.
  ۳) اگر پوشهٔ قدیمی قالب مانده، پاکش کنید (دو قالب هم‌زمان، سردرگمی می‌سازد).
  ۴) کش سایت را خالی کنید.
  ۵) بررسی: style.css باید «Version: 3.6.0» بدهد و در نوار اصلی پیشخوان
     منوی «ترانه یار» دیده شود.

فایل‌های این بسته:
  functions.php (بازنویسی‌شده، خوانا) · style.css
  inc/ab-admin-ui.php · inc/ab-sidebars.php
  inc/admin-panel.php · inc/admin-panel-views.php (تازه)
  css/admin-panel.css · js/admin-panel.js (تازه)

بازگشت: فایل‌های همین بسته را با نسخهٔ ۳.۵.۴ عوض کنید. هیچ تغییری در دیتابیس
انجام نشده است، پس تنظیمات و آمار بازدید دست‌نخورده می‌مانند.
"""


def sha256(data):
    return hashlib.sha256(data).hexdigest()


def collect(base):
    """همهٔ فایل‌های قابل بسته‌بندی، به‌صورت (مسیر نسبی، مسیر مطلق)."""
    out = []
    for dirpath, dirnames, filenames in os.walk(base):
        dirnames[:] = sorted(d for d in dirnames if d not in EXCLUDE_DIRS and not d.startswith('.'))
        for name in sorted(filenames):
            if name in EXCLUDE_FILES or name.startswith('.'):
                continue
            path = os.path.join(dirpath, name)
            rel = os.path.relpath(path, base).replace(os.sep, '/')
            out.append((rel, path))
    return out


def check_crc(path):
    with zipfile.ZipFile(path) as z:
        bad = z.testzip()
        if bad:
            sys.exit('✗ CRC خراب در %s: %s' % (path, bad))
        names = z.namelist()
        if len(names) != len(set(names)):
            sys.exit('✗ نام تکراری در %s' % path)
        return names


def assert_clean(names, label, allow_readme=False):
    """هیچ فایل لایسنس/آزمون/ابزار و هیچ ردی از ionCube نباشد."""
    for name in names:
        base = os.path.basename(name)
        if base == 'README.txt' or base.startswith('README-'):
            if allow_readme:
                continue
            sys.exit('✗ %s نباید README داشته باشد: %s' % (label, name))
        if re.search(r'RTL_License_[0-9a-f]+\.php$', name):
            sys.exit('✗ %s فایل لایسنس دارد: %s' % (label, name))
        if '/tests/' in name or '/docs/' in name or '/tools/' in name or '/artifacts/' in name:
            sys.exit('✗ %s فایل توسعه دارد: %s' % (label, name))


def assert_no_icb0(paths, label):
    """سرآمدِ ICB0 در هشت بایت اولِ فایل رمزگذاری‌شده است."""
    for abs_path in paths:
        if not abs_path.endswith('.php'):
            continue
        with open(abs_path, 'rb') as fh:
            head = fh.read(8)
        if b'ICB0' in head:
            sys.exit('✗ %s فایل رمزگذاری‌شده دارد: %s' % (label, abs_path))


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--version', default='3.6.0')
    ap.add_argument('--outdir', default='artifacts')
    args = ap.parse_args()

    if not os.path.isdir(THEME_ABS):
        sys.exit('✗ پوشهٔ قالب پیدا نشد: %s' % THEME_ABS)

    style = io.open(os.path.join(THEME_ABS, 'style.css'), encoding='utf-8', newline='').read(2000)
    if ('Version: %s' % args.version) not in style:
        sys.exit('✗ style.css نسخهٔ %s نیست' % args.version)
    if 'Theme Name: ترانه یار' not in style:
        sys.exit('✗ نام قالب در style.css «ترانه یار» نیست')

    # قالب دیگر نباید هیچ فایل لایسنس داشته باشد
    for rel, path in collect(THEME_ABS):
        if re.search(r'RTL_License_[0-9a-f]+\.php$', rel):
            sys.exit('✗ فایل لایسنس هنوز در قالب هست: %s' % rel)

    outdir = os.path.join(ROOT, args.outdir)
    os.makedirs(outdir, exist_ok=True)

    # ---------- ۱) بستهٔ کامل ----------
    files = collect(THEME_ABS)
    assert_no_icb0([path for _rel, path in files], 'بستهٔ کامل')

    full = os.path.join(outdir, '%s-%s.zip' % (THEME, args.version))
    if os.path.exists(full):
        os.remove(full)
    with zipfile.ZipFile(full, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for rel, path in files:
            z.write(path, '%s/%s' % (THEME, rel))
        z.writestr('%s/README-3.6.0.txt' % THEME, FULL_README)

    names = check_crc(full)
    assert_clean(names, 'بستهٔ کامل', allow_readme=True)
    if not all(n.startswith(THEME + '/') for n in names):
        sys.exit('✗ مسیر داخلی بستهٔ کامل درست نیست')
    for must in ('%s/style.css' % THEME, '%s/index.php' % THEME, '%s/functions.php' % THEME):
        if must not in names:
            sys.exit('✗ %s در بستهٔ کامل نیست' % must)

    # ---------- ۲) drop-in ----------
    missing = [f for f in CHANGED if not os.path.isfile(os.path.join(THEME_ABS, f))]
    if missing:
        sys.exit('✗ فایل‌های غایب برای drop-in: %s' % ', '.join(missing))
    assert_no_icb0([os.path.join(THEME_ABS, f) for f in CHANGED], 'drop-in')

    dropin = os.path.join(outdir, 'drop-in-v%s.zip' % args.version)
    if os.path.exists(dropin):
        os.remove(dropin)
    with zipfile.ZipFile(dropin, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for rel in CHANGED:
            z.write(os.path.join(THEME_ABS, rel), rel)
        z.writestr('README.txt', DROPIN_README.replace('\n', '\r\n'))

    dnames = check_crc(dropin)
    assert_clean(dnames, 'drop-in', allow_readme=True)
    with zipfile.ZipFile(dropin) as z:
        for name in dnames:
            if name == 'README.txt':
                continue
            if sha256(z.read(name)) != sha256(open(os.path.join(THEME_ABS, name), 'rb').read()):
                sys.exit('✗ %s در drop-in با فایل منبع یکی نیست' % name)

    # ---------- گزارش ----------
    print('✓ بستهٔ کامل: %s' % os.path.relpath(full, ROOT))
    print('    %d ورودی · %d B · CRC سالم · %d فایل قالب (functions.php داخل بسته)' % (
        len(names), os.path.getsize(full), len(files)))
    print('✓ drop-in: %s' % os.path.relpath(dropin, ROOT))
    print('    %d ورودی (%d فایل + README) · %d B · CRC سالم' % (
        len(dnames), len(CHANGED), os.path.getsize(dropin)))
    print('    sha256 full  : %s' % sha256(open(full, 'rb').read()))
    print('    sha256 dropin: %s' % sha256(open(dropin, 'rb').read()))
    return 0


if __name__ == '__main__':
    sys.exit(main())
