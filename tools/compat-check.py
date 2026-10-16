#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
compat-check.py — نگهبان سازگاری نسخهٔ PHP (معرفی‌شده در v3.1.6)

چرا؟ یک `static function () {}` (بستۀ static، فقط PHP 7.4+) در inc/ab-perf.php باعث شد کل سایت
با «خطای کشندۀ PHP» بخوابد — و php-parser (check.js) آن را گزارش نکرد، چون gramerها خطای
«نسخه» نمی‌دهند. این اسکریپت قبل از build اجرا می‌شود و هر ساختاری که تازه‌تر از کف پروژه
(PHP 5.6) باشد را در فایل‌های قالب گزارش می‌کند.

استفاده:  python3 tools/compat-check.py javanseda
خروجی:   فایل:خط [برچسب] · کد خروج ۱ اگر چیزی پیدا شد.
"""
import io, os, re, sys

NEEDLES = [
    ('static closure (PHP 7.4+)',            r'\bstatic\s+function\s*\('),
    ('arrow fn() (PHP 7.4+)',                r'(?:^|[^\$\w>])fn\s*\('),
    ('??= (PHP 7.4+)',                       r'\?\?='),
    ('spread in array literal (PHP 7.4+)',   r'=>\s*\.\.\.\$'),
    ('null-safe ?-> (PHP 8.0+)',             r'\?->'),
    ('match() (PHP 8.0+)',                   r'\bmatch\s*\('),
    ('named args (PHP 8.0+)',                r'\(\s*[a-z_][a-z0-9_]*\s*:\s*(?:\$|\'|")'),
    ('str_contains/starts/ends (PHP 8.0+)',  r'\bstr_(contains|starts_with|ends_with)\s*\('),
    ('array_key_first/last (PHP 7.3+)',      r'\barray_key_(first|last)\s*\('),
    ('get_debug_type (PHP 8.0+)',            r'\bget_debug_type\s*\('),
    ('catch union (PHP 7.1+)',               r'catch\s*\(\s*\w+\s*\|'),
    ('typed param (PHP 7.0+)',               r'function\s+\w+\s*\(\s*(?:int|float|string|bool|array|callable|iterable|\?)\s+\$'),
    ('return type (PHP 7.0+)',               r'\)\s*:\s*(?:void|bool|string|int|float|array|self|static|iterable|mixed|\?)\s*[\{;]'),
    ('?? coalesce (PHP 7.0+)',               r'\?\?[^=]'),
    ('$x{0} (removed in PHP 8.0)',          r'\$\w+\s*\{\s*[0-9$]'),
    ('each()/create_function (removed 8.0)', r'\b(each|create_function)\s*\('),
    ('WP_Error cast (fatal in PHP 8)',       r'\(string\)\s*get_(?:term|category|tag)_link'),
]

def code_only(src):
    """حرف‌های داخل رشته/کامنت را با فاصله جابه‌جا می‌کند تا الگوها فقط «کد» را ببینند
    (وگرنه خودِ regular expressionهای محافظ، خروجی را آلوده می‌کنند)."""
    out = list(src); i = 0; n = len(src)
    while i < n:
        c = src[i]
        if c in "'\"`":
            q = c; i += 1
            while i < n:
                if src[i] == '\\':
                    out[i] = ' '
                    if i + 1 < n:
                        out[i + 1] = ' '
                    i += 2
                    continue
                if src[i] == q:
                    i += 1
                    break
                out[i] = ' '
                i += 1
            continue
        if src.startswith('//', i) or src.startswith('#', i) or src.startswith('/*', i):
            j = src.find("\n", i) if src[i] == '#' or src.startswith('//', i) else src.find('*/', i + 2)
            j = n if j < 0 else (j if src[i] == '#' or src.startswith('//', i) else j + 2)
            for k in range(i, j):
                if src[k] not in "\r\n":
                    out[k] = ' '
            i = j
            continue
        i += 1
    return ''.join(out)


def files_of(arg):
    if os.path.isfile(arg):
        return [arg]
    out = []
    for dp, dn, fn in os.walk(arg):
        if 'option-tree' in dp:
            continue
        for f in fn:
            if f.endswith('.php'):
                out.append(os.path.join(dp, f))
    return sorted(out)

hits = 0
mine = re.compile(r'(^|/)(inc/ab-|ab-mobile-probe|myfunctions|functions|single|404|search|home|header|footer|page)\.php$|patches/')
for arg in (sys.argv[1:] or ['javanseda']):
    for path in files_of(arg):
        src = io.open(path, encoding='utf-8', errors='ignore').read()
        scan = code_only(src)
        for label, pat in NEEDLES:
            for m in re.finditer(pat, scan):
                ln = src[:m.start()].count('\n') + 1
                frag = ' '.join(src[max(0, m.start() - 34):m.start() + 46].split())
                tag = 'OURS' if mine.search(path) else 'theme'
                print('[%s] %s:%d  %s  …%s…' % (tag, path, ln, label, frag))
                hits += 1
print('compat-check: %d hit(s)' % hits)
sys.exit(1 if hits else 0)
