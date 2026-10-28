#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
build-dropin-352.py — بستهٔ به‌روزرسانیِ ۳.۵.۲ (فقط فایل‌های تغییرکرده/تازه)

چرا drop-in جدا؟
  اگر مدیر ترجیح می‌دهد پوشهٔ فعلی را دست نزند (یا نصبِ کامل را ریسک نمی‌داند)،
  همین ۱۴ فایل را روی پوشهٔ موجود کپی می‌کند. فایل‌های لایسنس عمداً در آن نیستند
  تا هیچ‌وقت بازنویسی نشوند.

استفاده: python3 tools/build-dropin-352.py
"""

import hashlib
import os
import sys
import zipfile

CHANGED = [
    'artist-template.php',
    'category.php',
    'myfunctions.php',
    'style.css',
    'tag.php',
    'taxonomy-artist.php',
]
ADDED = [
    'css/ab-352.css',
    'css/ab-admin-352.css',
    'inc/ab-artist-fields.php',
    'inc/ab-cat-desc.php',
    'inc/ab-post-artist.php',
    'inc/ab-terms-admin.php',
    'js/ab-admin-picker.js',
    'js/ab-cat-desc.js',
]

README = """جوان‌صدا ۳.۵.۲ — drop-in (فقط فایل‌های تغییرکرده/تازه)
======================================================
۱۴ فایل. دو فایلِ لایسنس (functions.php و RTL_License_41d6c5f704e3a746.php) عمداً در این
بسته نیستند: لایسنسِ روی سرور شما باید دست‌نخورده بماند.

نصب: محتوای بسته را در wp-content/themes/taranehyar/ باز کنید (Extract/Overwrite).
سپس Purge All در افزونهٔ کش. style.css باید Version: 3.5.2 بدهد.

برای نصبِ کامل، javanseda-3.5.2.zip را با File Manager در wp-content/themes/ باز کنید.
"""


def main():
    root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    theme = os.path.join(root, 'taranehyar')
    out = os.path.join(root, 'artifacts', 'drop-in-v3.5.2.zip')
    os.makedirs(os.path.dirname(out), exist_ok=True)
    if os.path.exists(out):
        os.remove(out)

    files = CHANGED + ADDED
    missing = [f for f in files if not os.path.isfile(os.path.join(theme, f))]
    if missing:
        sys.exit('✗ فایل‌های غایب: %s' % ', '.join(missing))

    with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for rel in files:
            z.write(os.path.join(theme, rel), rel)
        z.writestr('README.txt', README.replace('\n', '\r\n'))

    with zipfile.ZipFile(out) as z:
        names = z.namelist()
        if z.testzip() is not None:
            sys.exit('✗ CRC خراب')
        for n in names:
            if n == 'README.txt':
                continue
            disk = open(os.path.join(theme, n), 'rb').read()
            if hashlib.sha256(z.read(n)).hexdigest() != hashlib.sha256(disk).hexdigest():
                sys.exit('✗ %s در بسته با فایلِ منبع یکی نیست' % n)
        licenses = [n for n in names if n in ('functions.php', 'RTL_License_41d6c5f704e3a746.php')]
        if licenses:
            sys.exit('✗ فایل لایسنس داخل drop-in است: %s' % licenses)
        print('✓ %s — %d entry · %d B · CRC سالم · بدون فایل لایسنس' % (
            os.path.relpath(out, root), len(names), os.path.getsize(out)))


if __name__ == '__main__':
    main()
