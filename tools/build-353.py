#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
build-353.py — ساختِ دو بستهٔ نسخهٔ ۳.۵.۳

۱) artifacts/javanseda-3.5.3.zip   «بستهٔ کاملِ فایل‌های قالب» **بدونِ فایل‌های ICB0**
   (functions.php و RTL_License_*.php). این دو فایل روی سرور با ionCube رمز شده‌اند و
   جایگزینیِ نادرستشان همان خطای «The file … is corrupted» را می‌دهد؛ پس هیچ‌وقت داخل
   بستهٔ تحویلی نمی‌آیند و ابزار این را با assert بررسی می‌کند.

۲) artifacts/drop-in-v3.5.3.zip    فقط ۹ فایلی که در این نسخه عوض شده‌اند. این همان
   بسته‌ای است که روی نصبِ فعلی باز می‌شود (Extract/Overwrite) و به فایل‌های لایسنس
   دست نمی‌زند.

هر دو بسته: CRC سالم، هیچ فایلِ توسعه/آزمون، و همهٔ ورودی‌ها زیر javanseda/.

استفاده: python3 tools/build-353.py [--version 3.5.3]
"""

import argparse
import hashlib
import io
import os
import sys
import zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
THEME = 'taranehyar'
THEME_ABS = os.path.join(ROOT, THEME)
LICENSE_FILES = ('functions.php', 'RTL_License_41d6c5f704e3a746.php')

EXCLUDE_DIRS = {'.git', 'node_modules', '.svn', '.idea', '__pycache__', 'tests'}
EXCLUDE_FILES = {'.DS_Store', 'Thumbs.db', '.gitignore'}

# فایل‌هایی که در ۳.۵.۳ عوض/اضافه شده‌اند (همان فهرستِ drop-in)
CHANGED = [
    'css/ab-352.css',
    'header.php',
    'inc/ab-artist-cards.php',
    'inc/ab-home-meta.php',
    'inc/ab-seo.php',
    'inc/artist_slider.php',
    'inc/theme-options.php',
    'myfunctions.php',
    'style.css',
]

FULL_README = """جوان‌صدا ۳.۵.۳ — بستهٔ کاملِ فایل‌های قالب (بدونِ فایل‌های لایسنس)
==========================================================
⚠ این بسته برای «نصب روی هاست» ساخته نشده است. دو فایلِ زیر عمداً داخل آن نیستند:

    functions.php
    RTL_License_41d6c5f704e3a746.php

این دو فایل روی سرور با ionCube رمز شده‌اند و اگر با نسخهٔ دیگری جایگزین شوند،
سایت با خطای «The file … is corrupted» از کار می‌افتد. بنابراین هیچ بستهٔ تحویلی
این دو فایل را حمل نمی‌کند.

برای به‌روزرسانیِ نصبِ فعلی از «drop-in-v3.5.3.zip» استفاده کنید؛ آن بسته ۹ فایلِ
تغییرکرده را روی پوشهٔ فعلی می‌ریزد و به فایل‌های لایسنس دست نمی‌زند.

این بستهٔ کامل فقط برای مرور/آرشیوِ سورس است: همهٔ فایل‌های قالب به‌جز دو فایل بالا.
"""

DROPIN_README = """جوان‌صدا ۳.۵.۳ — بستهٔ به‌روزرسانیِ ۹ فایلی (drop-in)
=================================================
این بسته فقط فایل‌های تغییرکرده/تازهٔ ۳.۵.۳ است و هیچ فایل لایسنس (ICB0) ندارد؛
پس با Extract/Overwrite روی پوشهٔ فعلیِ قالب، لایسنسِ سرور شما دست‌نخورده می‌ماند.

نصب:
  ۱) محتوای این zip را در wp-content/themes/javanseda/ باز کنید (Overwrite).
  ۲) کشِ سایت (و اگر افزونهٔ کش دارید، Purge All) را پاک کنید.
  ۳) نسخه: style.css باید «Version: 3.5.3» بدهد.
  ۴) عنوان/توضیحات متا: پیشخوان → نمایش → تنظیمات قالب → تب «صفحه اصلی».

فایل‌های این بسته:
  style.css · myfunctions.php · header.php · inc/theme-options.php · inc/ab-seo.php
  inc/artist_slider.php · inc/ab-home-meta.php (تازه) · inc/ab-artist-cards.php (تازه)
  css/ab-352.css

بازگشت: همین ۹ فایل را از نسخهٔ ۳.۵.۲ برگردانید؛ هیچ تغییری در دیتابیس انجام نشده و
هیچ کلید متایی تغییر نام نداده است.
"""



def sha256(data):
    return hashlib.sha256(data).hexdigest()


def collect(root):
    out = []
    for base, dirs, files in os.walk(root):
        dirs[:] = sorted(d for d in dirs if d not in EXCLUDE_DIRS)
        for name in sorted(files):
            if name in EXCLUDE_FILES:
                continue
            path = os.path.join(base, name)
            rel = os.path.relpath(path, root).replace(os.sep, '/')
            out.append((rel, path))
    return out


def check_crc(path):
    with zipfile.ZipFile(path) as z:
        bad = z.testzip()
        if bad:
            sys.exit('✗ CRC خراب در %s: %s' % (path, bad))
        names = z.namelist()
        if len(names) != len(set(names)):
            sys.exit('✗ نامِ تکراری در %s' % path)
        return names


def assert_no_license(names, label):
    bad = [n for n in names if os.path.basename(n) in LICENSE_FILES]
    if bad:
        sys.exit('✗ %s فایل لایسنس دارد: %s' % (label, ', '.join(bad)))


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--version', default='3.5.3')
    ap.add_argument('--outdir', default='artifacts')
    args = ap.parse_args()

    if not os.path.isdir(THEME_ABS):
        sys.exit('✗ پوشهٔ قالب پیدا نشد: %s' % THEME_ABS)

    style = io.open(os.path.join(THEME_ABS, 'style.css'), encoding='utf-8', newline='').read(2000)
    if ('Version: %s' % args.version) not in style:
        sys.exit('✗ style.css نسخهٔ %s نیست' % args.version)

    outdir = os.path.join(ROOT, args.outdir)
    os.makedirs(outdir, exist_ok=True)

    # ---------- ۱) بستهٔ کامل، بدون ICB0 ----------
    files = [(rel, path) for rel, path in collect(THEME_ABS) if rel not in LICENSE_FILES]
    full = os.path.join(outdir, 'javanseda-%s.zip' % args.version)
    if os.path.exists(full):
        os.remove(full)
    with zipfile.ZipFile(full, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for rel, path in files:
            z.write(path, '%s/%s' % (THEME, rel))
        z.writestr('%s/README-3.5.3.txt' % THEME, FULL_README)

    names = check_crc(full)
    assert_no_license(names, 'بستهٔ کامل')
    if not all(n.startswith(THEME + '/') for n in names):
        sys.exit('✗ مسیر داخلی بستهٔ کامل درست نیست')
    if '%s/style.css' % THEME not in names or '%s/index.php' % THEME not in names:
        sys.exit('✗ فایل‌های اصلی قالب در بسته نیستند')
    if '%s/functions.php' % THEME in names:
        sys.exit('✗ functions.php نباید در بسته باشد (ICB0)')

    # ---------- ۲) drop-in ----------
    missing = [f for f in CHANGED if not os.path.isfile(os.path.join(THEME_ABS, f))]
    if missing:
        sys.exit('✗ فایل‌های غایب برای drop-in: %s' % ', '.join(missing))

    dropin = os.path.join(outdir, 'drop-in-v%s.zip' % args.version)
    if os.path.exists(dropin):
        os.remove(dropin)
    with zipfile.ZipFile(dropin, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for rel in CHANGED:
            z.write(os.path.join(THEME_ABS, rel), rel)
        z.writestr('README.txt', DROPIN_README.replace('\n', '\r\n'))

    dnames = check_crc(dropin)
    assert_no_license(dnames, 'drop-in')
    for n in dnames:
        if n == 'README.txt':
            continue
        disk = open(os.path.join(THEME_ABS, n), 'rb').read()
        if sha256(zipfile.ZipFile(dropin).read(n)) != sha256(disk):
            sys.exit('✗ %s در drop-in با فایل منبع یکی نیست' % n)

    # ---------- گزارش ----------
    total_theme = sum(1 for _rel, _p in collect(THEME_ABS))
    print('✓ بستهٔ کامل (بدون ICB0): %s' % os.path.relpath(full, ROOT))
    print('    %d ورودی · %d B · CRC سالم · %d فایل قالب (۲ فایل لایسنس عمداً بیرون)' % (
        len(names), os.path.getsize(full), total_theme - len(LICENSE_FILES)))
    print('✓ drop-in: %s' % os.path.relpath(dropin, ROOT))
    print('    %d ورودی (۹ فایل + README) · %d B · CRC سالم · بدون فایل لایسنس' % (
        len(dnames), os.path.getsize(dropin)))
    print('    sha256 full  : %s' % sha256(open(full, 'rb').read()))
    print('    sha256 dropin: %s' % sha256(open(dropin, 'rb').read()))
    return 0


if __name__ == '__main__':
    sys.exit(main())
