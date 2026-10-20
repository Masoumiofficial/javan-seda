#!/usr/bin/env python3
"""
package.py — سازندۀ بسته‌های انتشار جوان‌سدا (v3.4.12 به بعد)

چرا این اسکریپت هست؟ تا دورِ ۳.۴.۱۱ «قالبِ کامل» را با فایل‌های
functions.php و RTL_License_*.php می‌ساختیم؛ آن دو فایل با ionCube رمزگذاری شده‌اند
و به‌هم‌رفتنِ حتی یک بایتشان (یا جایگزینی با نسخۀ مخزن) باعث می‌شود لودرِ لایسنس
این سطر را چاپ کند و کل سایت بمیرد:

    The file /…/themes/javanseda/functions.php is corrupted.

پس از این نسخه، هر فایلِ دارای سرآمدِ «ICB0» به‌طور خودکار از همهٔ بسته‌ها
بیرون نگه داشته می‌شود (نه فقط این دو فایل). فایل‌های بیرون‌گذاشته‌شده در
«README.txt» و در خروجیِ ابزار اعلام می‌شوند تا نصب‌کننده بداند چرا.

استفاده:
    python3 tools/package.py --ver 3.4.12 --prev 3.4.11 [--readme docs/dropin-README-v3.4.12.txt]

خروجی در artifacts/:
    javanseda-v<VER>-theme.zip      کل پوسته، بدون فایل‌های رمزگذاری‌شده
    drop-in-v<VER>.zip              فقط فایل‌های تغییریافته نسبت به <PREV> + README.txt
    rollback-v<VER>.zip             نسخۀ <PREV> از همان فایل‌ها (با پسوند .<PREV>)
"""

import argparse
import hashlib
import io
import os
import subprocess
import sys
import zipfile

THEME = 'javanseda'
EXCLUDE_NAMES = ('style.css.map',)   # نام‌هایی که همیشه بیرون می‌مانند
ICB0_MARK = 'ICB0'                  # سرآمدِ فایل‌های رمزگذاری‌شده با ionCube


def read(path):
    return open(path, 'rb').read()


def is_encoded(path):
    """آیا این فایل با ionCube رمزگذاری شده است؟ (سرآمدِ ICB0 در ۱۲۰ بایت اول)"""
    try:
        with open(path, 'rb') as fh:
            return ICB0_MARK.encode() in fh.read(120)
    except OSError:
        return False


def theme_files():
    out = []
    for root, dirs, fns in os.walk(THEME):
        dirs[:] = [d for d in dirs if d not in ('.git', 'node_modules', '.svn')]
        for fn in fns:
            if fn.endswith(EXCLUDE_NAMES):
                continue
            p = os.path.join(root, fn)
            out.append(p)
    return sorted(out)


def git(*args):
    return subprocess.run(('git',) + args, capture_output=True, text=True)


def changed_vs(ref):
    r = git('diff', '--name-only', ref, '--', THEME)
    files = []
    for line in r.stdout.split():
        if line.startswith(THEME + '/'):
            files.append(line.split('/', 1)[1])
    return sorted(set(files))


def add_tree(z, base, arc_prefix=''):
    n = 0
    for root, dirs, fns in os.walk(base):
        dirs.sort()
        for fn in sorted(fns):
            fp = os.path.join(root, fn)
            arc = arc_prefix + os.path.relpath(fp, base).replace(os.sep, '/')
            z.write(fp, arc)
            n += 1
    return n


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--ver', required=True)
    ap.add_argument('--prev', required=True)
    ap.add_argument('--readme', default='')
    ap.add_argument('--outdir', default='artifacts')
    a = ap.parse_args()

    skip = [p for p in theme_files() if is_encoded(p)]
    skip_rel = [p.split('/', 1)[1] for p in skip]
    print('بیرون‌گذاشته‌شده (رمزگذاری‌شدۀ ionCube): %s' % (', '.join(skip_rel) or '—'))

    os.makedirs(a.outdir, exist_ok=True)
    tmp = '/tmp/pkg-%s' % a.ver
    subprocess.run(('rm', '-rf', tmp))

    # ۱) قالب کامل (بدون فایل‌های لایسنس)
    full = os.path.join(a.outdir, 'javanseda-v%s-theme.zip' % a.ver)
    if os.path.exists(full):
        os.remove(full)
    with zipfile.ZipFile(full, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for p in theme_files():
            if is_encoded(p):
                continue
            z.write(p, p.replace(os.sep, '/'))
    print_theme_version(full)

    # ۲) drop-in
    ch = [f for f in changed_vs('v' + a.prev) if f not in skip_rel]
    if not ch:
        print('⚠ هیچ فایل تغییریافته‌ای نسبت به v%s پیدا نشد؛ dropin ساخته نشد.' % a.prev)
    else:
        stage = os.path.join(tmp, 'dropin')
        os.makedirs(stage, exist_ok=True)
        for f in ch:
            src = os.path.join(THEME, f)
            dst = os.path.join(stage, f)
            os.makedirs(os.path.dirname(dst), exist_ok=True)
            with open(src, 'rb') as fh, open(dst, 'wb') as wh:
                wh.write(fh.read())
            if len(read(src)) != len(read(dst)):
                sys.exit('✗ کپیِ % همسان نشد' % f)
        if a.readme:
            with open(a.readme, 'rb') as fh, open(os.path.join(stage, 'README.txt'), 'wb') as wh:
                wh.write(fh.read())
        else:
            io.open(os.path.join(stage, 'README.txt'), 'w', encoding='utf-8', newline='\r\n').write(
                'جوان‌سدا — نسخۀ %s (drop-in)\n\n'
                'فایل‌ها را با همان مسیر در wp-content/themes/%s/ جایگزین کنید.\n'
                'فایل‌های functions.php و RTL_License_*.php عمداً در این بسته نیستند: '
                'رمزگذاری‌شدۀ ionCube هستند و جایگزینی‌شان = «The file … is corrupted».\n'
                % (a.ver, THEME))
        drop = os.path.join(a.outdir, 'drop-in-v%s.zip' % a.ver)
        if os.path.exists(drop):
            os.remove(drop)
        with zipfile.ZipFile(drop, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
            add_tree(z, stage)
        # راستی‌آزماییِ بایتی
        with zipfile.ZipFile(drop) as z:
            for n in z.namelist():
                if n == 'README.txt':
                    continue
                src = os.path.join(THEME, n)
                if hashlib.sha256(z.read(n)).hexdigest() != hashlib.sha256(read(src)).hexdigest():
                    sys.exit('✗ هشِ %s در بسته با فایلِ درخت یکی نیست' % n)
            print('drop-in: %d entry · %d B · فایل‌ها: %s' % (
                len(z.namelist()), sum(i.file_size for i in z.infolist()), ', '.join(ch)))

    # ۳) rollback
    rb_dir = os.path.join(tmp, 'rollback')
    os.makedirs(rb_dir, exist_ok=True)
    wrote = 0
    for f in ch:
        r = subprocess.run(('git', 'show', 'v%s:%s/%s' % (a.prev, THEME, f)), capture_output=True)
        if r.returncode != 0 or not r.stdout:
            continue
        dst = os.path.join(rb_dir, '%s.%s' % (f, a.prev))
        os.makedirs(os.path.dirname(dst), exist_ok=True)
        with open(dst, 'wb') as wh:
            wh.write(r.stdout)
        wrote += 1
    io.open(os.path.join(rb_dir, 'READ-FIRST-%s.txt' % a.prev), 'w', encoding='utf-8', newline='\r\n').write(
        'بازگشت از %s به %s\n%s\nبرای هر فایل، پسوند .%s را بردارید و در همان مسیر جایگزین کنید.\n'
        'فایل‌های رمزگذاری‌شدۀ لایسنس (functions.php، RTL_License_*.php) در هیچ بسته‌ای نیستند؛ '
        'آنها را هرگز روی سرور جایگزین نکنید.\n' % (a.ver, a.prev, '=' * 34, a.prev))
    roll = os.path.join(a.outdir, 'rollback-v%s.zip' % a.ver)
    if os.path.exists(roll):
        os.remove(roll)
    with zipfile.ZipFile(roll, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        add_tree(z, rb_dir)
    print('rollback: %d فایلِ دور %s + راهنما' % (wrote, a.prev))

    for p in (full, os.path.join(a.outdir, 'drop-in-v%s.zip' % a.ver), roll):
        if not os.path.exists(p):
            continue
        with zipfile.ZipFile(p) as z:
            bad = z.testzip()
            print('%-42s %3d entry · %8d B · %s' % (
                os.path.basename(p), len(z.namelist()), sum(i.file_size for i in z.infolist()),
                'crc ok' if bad is None else 'BAD: %s' % bad))
            if bad:
                sys.exit('✗ بستهٔ خراب: %s' % p)
    print('⚑ یادآوری: فایل‌های لایسنس عمداً بیرون ماندند؛ برای نصبِ تازه، اول پوستۀ '
          'اورجینالِ خریده‌شده را نصب کنید و بعد همین drop-in را.')


def print_theme_version(zpath):
    with zipfile.ZipFile(zpath) as z:
        names = z.namelist()
        for need in ('%s/functions.php' % THEME,):
            if need in names:
                sys.exit('✗ فایل لایسنس (%s) در بسته ماند!' % need)
        css = z.read('%s/style.css' % THEME).decode('utf-8', 'replace')
        for line in css.split('\n')[:12]:
            if line.startswith('Version:'):
                print('بستهٔ کامل: %s' % line.strip())
                return
    sys.exit('✗ سطر Version در style.css پیدا نشد')


if __name__ == '__main__':
    main()
