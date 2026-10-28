#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
build-352.py — ساخت و راستی‌آزماییِ بستهٔ نصبِ قالب ۳.۵.۲

تفاوت با package.py (نسخه‌های ۳.۴.۱۲ به بعد):
  آن ابزار عمداً فایل‌های رمزگذاری‌شدهٔ ionCube را از بسته بیرون می‌گذاشت، چون
  در ۳.۴.۹ تا ۳.۴.۱۱ همان فایل‌ها با کپیِ مخزن بازنویسی شده بودند و سایت مرده بود.
  این نسخه از **نسخهٔ مبنای تأییدشدهٔ کاربر** ساخته می‌شود و همان دو فایل را
  بایت‌به‌بایت (با هشِ SHA-256) از همان بسته برمی‌دارد، تا zip مستقیماً از
  «نمایش → پوسته‌ها → افزودن» قابل نصب باشد.

قاعدهٔ سخت: هر بایتی از functions.php یا RTL_License_*.php که با مبنا فرق کند
⇒ ساخت متوقف می‌شود (چون یک بایت اختلاف = «The file … is corrupted»).

استفاده:
    python3 tools/build-352.py --version 3.5.2
"""

import argparse
import hashlib
import io
import os
import sys
import zipfile

THEME = 'taranehyar'
THEME_ABS = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), THEME)

# هشِ فایل‌های لایسنس در نسخهٔ مبنای تأییدشده (javanseda (1).zip در main)
BASELINE_SHA = {
    'functions.php': 'dbdfd22e26545bb5',
    'RTL_License_41d6c5f704e3a746.php': '2cf2f63b148fb9ef',
}
# فایل/پوشه‌هایی که هرگز نباید در بستهٔ نصب باشند
EXCLUDE_DIRS = {'.git', 'node_modules', '.svn', '.idea', '__pycache__', 'tests'}
EXCLUDE_FILES = {'.DS_Store', 'Thumbs.db', '.gitignore'}


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


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--version', default='3.5.2')
    ap.add_argument('--outdir', default='artifacts')
    args = ap.parse_args()

    if not os.path.isdir(THEME_ABS):
        sys.exit('✗ پوشهٔ قالب پیدا نشد: %s' % THEME_ABS)

    # ۱) بررسیِ نسخه و فایل‌های لایسنس پیش از بستنِ zip
    style = io.open(os.path.join(THEME_ABS, 'style.css'), encoding='utf-8', newline='').read(2000)
    if ('Version: %s' % args.version) not in style:
        sys.exit('✗ style.css نسخهٔ %s نیست' % args.version)

    for name, expect in BASELINE_SHA.items():
        path = os.path.join(THEME_ABS, name)
        if not os.path.isfile(path):
            sys.exit('✗ فایل لایسنس نیست: %s' % name)
        data = open(path, 'rb').read()
        got = sha256(data)
        if not got.startswith(expect):
            sys.exit('✗ %s با مبنای تأییدشده فرق دارد!\n  انتظار: %s…\n  واقعی : %s…' % (name, expect, got[:16]))
        print('✓ %-34s %8d B  sha256=%s… (همان بایت‌های مبنا)' % (name, len(data), got[:16]))

    # ۲) ساخت
    files = collect(THEME_ABS)
    os.makedirs(args.outdir, exist_ok=True)
    out = os.path.join(args.outdir, 'javanseda-%s.zip' % args.version)
    if os.path.exists(out):
        os.remove(out)

    with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for rel, path in files:
            z.write(path, '%s/%s' % (THEME, rel))

    # ۳) راستی‌آزماییِ ساختار
    with zipfile.ZipFile(out) as z:
        names = z.namelist()
        bad = z.testzip()
        if bad:
            sys.exit('✗ CRC خراب: %s' % bad)
        if not all(n.startswith(THEME + '/') for n in names):
            sys.exit('✗ مسیر داخلی zip درست نیست (باید همه با %s/ شروع شوند)' % THEME)
        if '%s/style.css' % THEME not in names:
            sys.exit('✗ style.css در بسته نیست')
        if '%s/functions.php' % THEME not in names:
            sys.exit('✗ functions.php در بسته نیست ⇒ نصبِ استاندارد کار نمی‌کند')
        for name, expect in BASELINE_SHA.items():
            got = sha256(z.read('%s/%s' % (THEME, name)))
            if not got.startswith(expect):
                sys.exit('✗ %s داخل zip با مبنا فرق دارد' % name)
        ver = [l for l in z.read('%s/style.css' % THEME).decode('utf-8', 'replace').splitlines() if l.startswith('Version:')]
        size = os.path.getsize(out)
        print('\n✓ بسته ساخته شد: %s' % out)
        print('  فایل‌ها: %d · حجم zip: %d B · CRC سالم' % (len(names), size))
        print('  نسخه در style.css: %s' % (ver[0] if ver else 'نامشخص'))
        print('  پوشهٔ داخلی: %s/' % THEME)
        print('  فایل‌های لایسنس: هر دو در بسته، بایت‌به‌بایت برابرِ مبنا')
    return 0


if __name__ == '__main__':
    sys.exit(main())
