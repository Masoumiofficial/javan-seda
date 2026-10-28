#!/usr/bin/env python3
"""javanseda/inc/ab-sidebars.php → javan_seda_expected_hashes() را با disk هم‌سازی می‌کند.

چرا این اسکریپت هست: مقدار هر کلید = sha256 محتوای فایل **بعد از** نرمال‌سازی
\r\n و \r به \n — دقیقاً همان کاری که javan_seda_file_hash() در PHP می‌کند.
اگر دستی و روی بایت خام حساب شود، هر فایل CRLF همیشه «ناسازگار» گزارش می‌شود.

    python3 tools/expected-hashes.py --check     # گزارش
    python3 tools/expected-hashes.py --write      # بازنویسی خط‌ها
"""
import hashlib, io, os, re, sys

root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
php = os.path.join(root, 'taranehyar', 'inc', 'ab-sidebars.php')
theme = os.path.join(root, 'taranehyar')

def disk_hash(rel):
    p = os.path.join(theme, rel.replace('/', os.sep))
    if not os.path.isfile(p):
        return 'MISSING'
    b = open(p, 'rb').read()
    return hashlib.sha256(b.replace(b'\r\n', b'\n').replace(b'\r', b'\n')).hexdigest()

src = io.open(php, encoding='utf-8', newline='').read()
EOL = '\r\n' if '\r\n' in src else '\n'
start = src.find('function javan_seda_expected_hashes')
end = src.find("\n}\n", start)
assert start > 0 and end > start, 'بلوک expected_hashes پیدا نشد'
block = src[start:end]
row = re.compile(r"^(\t+)'([^']+)'\s*=>\s*'([0-9a-f]{64})',?$")
lines = block.split(EOL)
out, ok, bad = [], 0, 0
for L in lines:
    m = row.match(L)
    if not m:
        out.append(L)
        continue
    key, old = m.group(2), m.group(3)
    new = disk_hash(key)
    if new == 'MISSING':
        print('  ? %-28s فایل روی disk نیست' % key)
        bad += 1
        out.append(L)
        continue
    if old == new:
        ok += 1
    else:
        bad += 1
    pad = max(1, 28 - len(key) - 2)
    out.append("\t\t'%s'%s=> '%s'," % (key, ' ' * pad, new))
if '--write' in sys.argv:
    io.open(php, 'w', encoding='utf-8', newline='').write(src[:start] + EOL.join(out) + src[end:])
print('%s: %d مطابق، %d نیازمند بازنویسی (کل %d کلید)' % ('write' if '--write' in sys.argv else 'check', ok, bad, ok + bad))
sys.exit(1 if bad and '--write' not in sys.argv else 0)
