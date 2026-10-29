#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""minify-css.py — نسخهٔ فشردهٔ CSSهای قالب ترانه یار

چرا این ابزار؟
  فایل‌های CSS قالب (tara-ui ~۱۰۰KB، ab-ui ~۹۰KB، style.css) بدون فشرده‌سازی
  به مرورگر می‌روند. «ترکیب» کردنشان عمداً انجام نمی‌شود (وابستگیِ ترتیب و
  کشِ صفحه‌به‌صفحه به‌هم می‌خورد)؛ فقط همان فایل‌ها فشرده می‌شوند.

خروجی:
  taranehyar/css/<name>.min.css   برای هر فایل در taranehyar/css/
  taranehyar/css/style.min.css     برای taranehyar/style.css (که در ریشه است)

ایمنی:
  • کامنت‌ها حذف می‌شوند (جز /*! … */).
  • فاصله‌های اضافی جمع می‌شوند؛ فاصلهٔ اطراف + و - در calc() دست نمی‌خورد.
  • رشته‌های داخل "…" و '…' و url(…) دست‌نخورده می‌مانند.
  • پس از تولید، تعداد {} و @media و url( با فایل اصلی مقایسه می‌شود؛
    اگر برابر نبود، فایل نوشته نمی‌شود و خطا برمی‌گردد.

استفاده:
    python3 tools/minify-css.py            # همه
    python3 tools/minify-css.py --check    # فقط بررسیِ تازه‌بودن
"""

import argparse
import io
import os
import re
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
THEME = os.path.join(ROOT, 'taranehyar')


def minify(css):
    """فشرده‌سازیِ محافظه‌کارانه. ورودی/خروجی: str."""
    out = []
    i, n = 0, len(css)
    while i < n:
        ch = css[i]

        # رشته‌ها
        if ch in ('"', "'"):
            j = i + 1
            while j < n:
                if css[j] == '\\':
                    j += 2
                    continue
                if css[j] == ch:
                    j += 1
                    break
                j += 1
            out.append(css[i:j])
            i = j
            continue

        # کامنت
        if ch == '/' and i + 1 < n and css[i + 1] == '*':
            end = css.find('*/', i + 2)
            end = n if end == -1 else end + 2
            if css.startswith('/*!', i):
                out.append(css[i:end])
            i = end
            continue

        # url(…)
        if css[i:i + 4].lower() == 'url(':
            j = i + 4
            depth = 1
            while j < n and depth:
                if css[j] == '(':
                    depth += 1
                elif css[j] == ')':
                    depth -= 1
                j += 1
            out.append(css[i:j])
            i = j
            continue

        # فاصله
        if ch in ' \t\r\n\f':
            j = i
            while j < n and css[j] in ' \t\r\n\f':
                j += 1
            prev = out[-1][-1] if out else ''
            nxt = css[j] if j < n else ''
            keep = bool(out) and bool(nxt) and prev not in '{};,>' and nxt not in '{};,>'
            if keep:
                out.append(' ')
            i = j
            continue

        out.append(ch)
        i += 1

    data = ''.join(out)
    data = data.replace(';}', '}')
    data = re.sub(r'\s*>\s*', '>', data)
    data = re.sub(r'\s*\{\s*', '{', data)
    data = re.sub(r'\s*\}\s*', '}', data)
    data = re.sub(r'\s*;\s*', ';', data)
    data = re.sub(r'\s*,\s*', ',', data)
    return data.strip()


def strip_comments(css):
    return re.sub(r'/\*.*?\*/', '', css, flags=re.S)


def counts(css):
    css = strip_comments(css)
    return (
        css.count('{'),
        css.count('}'),
        len(re.findall(r'@media\b', css)),
        len(re.findall(r'url\(', css, re.I)),
    )


# فقط CSSهایی که واقعاً به مرورگرِ کاربر می‌روند. CSS پیشخوان فشرده نمی‌شود
# (در پنل، حجم مهم نیست و فایلِ کمتر یعنی احتمالِ جاافتادن کمتر).
FRONT_CSS = ('tara-ui.css', 'tara-home.css', 'tara-artist.css', 'ab-ui.css', 'ab-glass.css', 'tara-radio.css', 'tara-tax.css')


def targets():
    files = [os.path.join(THEME, 'css', n) for n in FRONT_CSS]
    files = [f for f in files if os.path.isfile(f)]
    root_style = os.path.join(THEME, 'style.css')
    if os.path.isfile(root_style):
        files.append(root_style)
    return files


def out_path(src):
    if os.path.basename(src) == 'style.css':
        return os.path.join(THEME, 'css', 'style.min.css')
    return src[:-4] + '.min.css'


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--check', action='store_true', help='فقط گزارشِ تازه‌بودن')
    args = ap.parse_args()

    ok = stale = 0
    for src in targets():
        dst = out_path(src)
        src_m = os.path.getmtime(src)
        if args.check:
            if os.path.isfile(dst) and os.path.getmtime(dst) >= src_m:
                ok += 1
            else:
                stale += 1
                print('کهنه/نبود: %s' % os.path.relpath(dst, ROOT))
            continue

        css = io.open(src, encoding='utf-8').read()
        mini = minify(css)
        if counts(css) != counts(mini):
            print('رد شد (ساختار عوض شد): %s  %s != %s' % (os.path.relpath(src, ROOT), counts(css), counts(mini)))
            return 1
        io.open(dst, 'w', encoding='utf-8', newline='').write(mini + '\n')
        ok += 1

    if args.check:
        print('check: %d تازه، %d کهنه' % (ok, stale))
        return 0 if stale == 0 else 1

    total_src = total_dst = 0
    for src in targets():
        dst = out_path(src)
        if os.path.isfile(dst):
            total_src += os.path.getsize(src)
            total_dst += os.path.getsize(dst)
    saving = 0 if not total_src else int(round(100 - (total_dst * 100.0 / total_src)))
    print('نوشته شد: %d فایل' % ok)
    print('حجم: %d → %d بایت  (−%d%%)' % (total_src, total_dst, saving))
    return 0


if __name__ == '__main__':
    sys.exit(main())
