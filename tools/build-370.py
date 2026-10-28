#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
build-370.py — بستهٔ کاملِ نسخهٔ ۳.۷.x «ترانه یار»

تفاوت با build-360.py:
  • فقط بستهٔ کامل ساخته می‌شود. در ۳.۷.۰ حدود نیمی از فایل‌های قالب عوض شده‌اند،
    پس «drop-in» چندفایلی گمراه‌کننده است؛ کاربر یک zip می‌گیرد و همان را
    روی پوشهٔ قالب Overwrite می‌کند.
  • فایل README داخل بسته، نسخه و مسیرها را از همان آرگومان می‌خواند.

خروجی: artifacts/taranehyar-<version>.zip  (پیش‌فرض 3.7.2)

بررسی‌های خودکار پیش از بستن:
  • هیچ فایل آزمون/ابزار/مستندات/لایسنس/رمزگذاری‌شده داخل بسته نباشد
  • هیچ ارجاعی به نام‌های قدیمی در فایل‌های متنی نمانده باشد
  • CRC هر ورودی سالم باشد

استفاده: python3 tools/build-370.py [--version 3.7.2]
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

EXCLUDE_DIRS = {'.git', 'node_modules', '.svn', '.idea', '__pycache__', 'tests', 'docs', 'artifacts', '.github'}
EXCLUDE_NAMES = {'.DS_Store', 'Thumbs.db', '.gitignore', '.gitattributes'}

# نام‌هایی که نباید در بستهٔ تحویلی دیده شوند
FORBIDDEN_NAME = re.compile(r'^(RTL_License|icb|ICB0)', re.I)
FORBIDDEN_BRAND = re.compile(r'جوان‌صدا|جوان صدا|javan[_-]?seda|JAVANSEDA|kianoosh|کیانوش', re.I)
TEXT_EXT = {'.php', '.css', '.js', '.json', '.txt', '.md', '.html', '.svg'}

README = """ترانه یار {ver} — بستهٔ کامل قالب
=================================

این بسته همهٔ فایل‌های قالب را دارد: functions.php خوانا، پوستهٔ تازهٔ صفحهٔ
تنظیمات، پلیر «رادیو»، صفحهٔ هنرمندِ بازنویسی‌شده و پنج تاکسونومی موسیقی
(ژانر، حال‌وهوا، ترانه‌سرا، آهنگساز، تنظیم).

هیچ فایل رمزگذاری‌شده و هیچ فایل لایسنس در آن نیست و قالب به هیچ سرور بیرونی
وصل نمی‌شود.

نصب تازه:
  ۱) محتوای zip را در wp-content/themes/ باز کنید؛ پوشهٔ {theme}/ ساخته می‌شود.
  ۲) پیشخوان → نمایش → پوسته‌ها → «ترانه یار» را فعال کنید.
     پس از فعال‌سازی، قوانین پیوند یکتا یک‌بار خودکار بازسازی می‌شوند.
  ۳) پیشخوان → منوی «ترانه یار» → تنظیمات قالب.

به‌روزرسانی از ۳.۶.۰ یا پیش‌تر:
  ۱) محتوای این zip را روی پوشهٔ wp-content/themes/{theme}/ باز کنید (Overwrite).
  ۲) اگر پوشهٔ قدیمی قالب یا فایل RTL_License_*.php مانده، پاکش کنید.
  ۳) کش سایت (و افزونهٔ کش) را خالی کنید.
  ۴) یک‌بار «تنظیمات → پیوندهای یکتا → ذخیره» بزنید تا آدرس‌های /genre/… و
     /songwriter/… درست شوند (فعال‌سازی دوبارهٔ قالب هم همین کار را می‌کند).

دیتابیس:
  هیچ جدول یا کلید تازه‌ای لازم نیست. تنظیمات همان option_tree است و همهٔ
  گزینه‌های نسخه‌های پیشین سر جایشان می‌مانند. گزینه‌های تازهٔ «دربارهٔ سایت»
  فقط وقتی پر شوند رفتار جدید می‌دهند؛ تا آن موقع همان متن قبلیِ پانوشت
  نمایش داده می‌شود.

بازگشت به ۳.۶.۰:
  محتوای taranehyar-3.6.0.zip را روی همین پوشه Overwrite کنید. داده‌ای عوض
  نشده است؛ تاکسونومی‌های تازه فقط ثبت می‌شوند و با برگشت، آرشیوهایشان
  404 می‌شوند ولی ترم‌ها در دیتابیس می‌مانند.
"""


def sha256_bytes(data):
    return hashlib.sha256(data).hexdigest()


def collect():
    out = []
    for dirpath, dirnames, filenames in os.walk(THEME_ABS):
        dirnames[:] = sorted(d for d in dirnames if d not in EXCLUDE_DIRS and not d.startswith('.'))
        for name in sorted(filenames):
            if name in EXCLUDE_NAMES or FORBIDDEN_NAME.match(name):
                continue
            if name.endswith(('.zip', '.log', '.pyc')):
                continue
            abs_path = os.path.join(dirpath, name)
            rel = os.path.relpath(abs_path, THEME_ABS).replace(os.sep, '/')
            out.append((rel, abs_path))
    return sorted(out)


def scan_brand(entries):
    bad = []
    for rel, abs_path in entries:
        if os.path.splitext(rel)[1].lower() not in TEXT_EXT:
            continue
        try:
            data = io.open(abs_path, encoding='utf-8', errors='ignore').read()
        except OSError:
            continue
        if FORBIDDEN_BRAND.search(data):
            bad.append(rel)
    return bad


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--version', default='3.7.2')
    args = ap.parse_args()
    ver = args.version

    entries = collect()
    if not entries:
        print('هیچ فایلی برای بستن پیدا نشد', file=sys.stderr)
        return 1

    # سلامت پیش از بستن
    readme_rel = 'README-%s.txt' % ver
    if any(rel == readme_rel for rel, _ in entries):
        print('README داخل قالب مانده: ' + readme_rel, file=sys.stderr)
        return 1
    brand = scan_brand(entries)
    if brand:
        print('نام قدیمی در این فایل‌ها مانده: ' + ', '.join(brand), file=sys.stderr)
        return 1

    outdir = os.path.join(ROOT, 'artifacts')
    os.makedirs(outdir, exist_ok=True)
    target = os.path.join(outdir, 'taranehyar-%s.zip' % ver)

    readme = README.format(ver=ver, theme=THEME).encode('utf-8')

    # مهر زمانی ثابت ⇒ بستهٔ بازتولیدپذیر و sha256 پایدار
    stamp = (1980, 1, 1, 0, 0, 0)

    def add(zf, arcname, data, mode=0o644):
        info = zipfile.ZipInfo(arcname, date_time=stamp)
        info.compress_type = zipfile.ZIP_DEFLATED
        info.external_attr = (0o100000 | mode) << 16
        zf.writestr(info, data)

    with zipfile.ZipFile(target, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as zf:
        add(zf, '%s/%s' % (THEME, readme_rel), readme)
        for rel, abs_path in entries:
            with open(abs_path, 'rb') as fh:
                add(zf, '%s/%s' % (THEME, rel), fh.read())

    # بررسی پس از بستن: CRC، مسیرها، نبودِ فایل ناخواسته
    problems = []
    with zipfile.ZipFile(target) as zf:
        bad_crc = zf.testzip()
        if bad_crc:
            problems.append('CRC خراب: ' + bad_crc)
        names = zf.namelist()
        for n in names:
            if not n.startswith(THEME + '/'):
                problems.append('مسیر بیرون از پوشهٔ قالب: ' + n)
            base = os.path.basename(n)
            if FORBIDDEN_NAME.match(base) or base.endswith(('.py', '.mjs', '.pyc')):
                problems.append('فایل ناخواسته: ' + n)
            if '/tests/' in n or '/docs/' in n or '/tools/' in n:
                problems.append('فایل توسعه داخل بسته: ' + n)
            if base.startswith('run-') or base == 'stubs-wp.php':
                problems.append('فایل آزمون داخل بسته: ' + n)
        if not any(n.endswith('style.css') for n in names):
            problems.append('style.css نیست')
        if not any(n.endswith('functions.php') for n in names):
            problems.append('functions.php نیست')

    if problems:
        for p in problems:
            print(p, file=sys.stderr)
        os.remove(target)
        return 1

    raw = open(target, 'rb').read()
    print('ساخته شد: artifacts/taranehyar-%s.zip' % ver)
    print('  فایل‌ها : %d (+ README)' % len(entries))
    print('  حجم   : %s بایت' % format(len(raw), ','))
    print('  sha256: %s' % sha256_bytes(raw))
    return 0


if __name__ == '__main__':
    sys.exit(main())
