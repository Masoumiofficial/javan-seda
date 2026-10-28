#!/usr/bin/env python3
"""css/ab-ui.css را از مارکر «ab-ui-layer» در javanseda/style.css بازسازی می‌کند.

هر بار که بلوک تازه‌ای به انتهای style.css اضافه می‌کنید این را اجرا کنید تا
کپیِ گارد (javanseda/css/ab-ui.css) هم‌زمان بماند:
    python3 tools/ab-ui-sync.py
"""
import io, os, re, sys

root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
style = os.path.join(root, 'taranehyar', 'style.css')
out = os.path.join(root, 'taranehyar', 'css', 'ab-ui.css')

s = io.open(style, encoding='utf-8', newline='').read()
i = s.find('/* ── ab-ui-layer ─')
if i < 0:
    sys.exit('مارکر ab-ui-layer در style.css پیدا نشد — چیزی نوشته نشد.')
EOL = '\r\n' if '\r\n' in s else '\n'
body = s[i:].rstrip('\r\n') + EOL
ver = '3.3.8'
m = re.search(r'Version:\s*([\d.]+)', s)
if m:
    ver = m.group(1)
head = io.open(out, encoding='utf-8', newline='').read()
start = head.find('/* ── ab-ui-layer ─')
if start < 0:
    sys.exit('فایل خروجی ساختارش را از دست داده — چیزی نوشته نشد.')
io.open(out, 'w', encoding='utf-8', newline='').write(head[:start] + body)
print('css/ab-ui.css ← style.css (نسخه %s) · %d بایت' % (ver, os.path.getsize(out)))
