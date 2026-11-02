#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""بررسیِ ساختاریِ inc/theme-options.php (ردیف‌های OptionTree) — بدونِ PHP.

هر «id=>…» که پیش از idِ بعدی «type=>…» داشته باشد، یک ردیف شمرده می‌شود
(دقیقاً همان شماری که پیش‌تر با AST به دست می‌آمد). سپس:
  · select/checkbox/radio باید choices داشته باشد
  · list-item باید settings (یا choices) داشته باشد
  · کلیدِ تکراری نباید باشد
  · هر ردیف باید label یا desc داشته باشد (ردیف‌های راهنما label خالی مجاز است)
اجرا: python3 tools/verify-ot.py [path]
"""
import io, os, re, sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PATH = sys.argv[1] if len(sys.argv) > 1 else os.path.join(ROOT, 'taranehyar', 'inc', 'theme-options.php')
src = io.open(PATH, encoding='utf-8', newline='').read()

ids = [(m.group(1), m.start()) for m in re.finditer(r"'id'\s*=>\s*'([a-zA-Z0-9_]+)'", src)]
rows, problems = [], []
for k, (rid, pos) in enumerate(ids):
    end = ids[k + 1][1] if k + 1 < len(ids) else len(src)
    body = src[pos:end]
    t = re.search(r"'type'\s*=>\s*'([a-z\-]+)'", body)
    if not t:
        if "'title'=>'" in body.replace(' ', '') and "'label'" not in body:
            continue  # تعریفِ خودِ تب/section، نه ردیفِ تنظیمات
        problems.append('%s: بی type' % rid)
        continue
    rows.append((rid, t.group(1), body))

seen = set()
for rid, rtype, body in rows:
    if rid in seen:
        problems.append('%s: کلیدِ تکراری' % rid)
    seen.add(rid)
    if rtype in ('select', 'checkbox', 'radio') and "'choices'=>array(" not in body.replace(' ', ''):
        problems.append('%s: %s بدون choices' % (rid, rtype))
    if rtype == 'list-item' and 'settings' not in body and 'choices' not in body:
        problems.append('%s: list-item بدون settings' % rid)
    if rtype not in ('textblock',) and "'label'" not in body:
        problems.append('%s: بدون label' % rid)

sections = {}
for rid, rtype, body in rows:
    m = re.search(r"'section'\s*=>\s*'([a-z0-9_]+)'", body)
    key = m.group(1) if m else '(بدون section)'
    sections[key] = sections.get(key, 0) + 1

print('ردیف‌ها: %d | section‌ها: %s' % (len(rows), ' '.join('%s:%d' % kv for kv in sorted(sections.items()))))
print('problems: %d' % len(problems))
for p in problems:
    print('  ! ' + p)
bad = [p for p in problems if 'تکراری' in p or 'بی type' in p]
sys.exit(1 if bad else 0)
