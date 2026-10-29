#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
build-preview-391.py — ساختِ `docs/preview-3.10.0.html` از خروجیِ واقعیِ PHP

چرا: پیش‌نمایش باید همان مارکاپی باشد که قالب چاپ می‌کند. نسخهٔ دست‌ساز
طبقه‌های واقعیِ CSS را نداشت و کنار گذاشته شد.

گام‌ها:
  1) `node tools/php-test.mjs tests/dump-391.php > /tmp/dump.html`
     (مارکاپِ مودال، برگهٔ پلی‌لیست، پنل و هشت تب با نشانگرهای `<!--TARA:*`)
  2) `python3 tools/minify-css.py`  (برای فایلِ `.min.css`)
  3) `python3 tools/build-preview-391.py`

خروجی: `docs/preview-3.10.0.html` — تک‌فایل، آفلاین، با CSSِ خودِ قالب و
جاوااسکریپتِ خودِ قالب روی یک سرورِ شبیه‌سازی‌شده.
"""

import base64
import io
import os
import re
import subprocess
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DUMP_PATH = '/tmp/dump.html'
OUT = os.path.join(ROOT, 'docs', 'preview-3.10.1.html')
CSS_PATH = os.path.join(ROOT, 'taranehyar', 'css', 'tara-account.min.css')
JS_PATH = os.path.join(ROOT, 'taranehyar', 'js', 'tara-account.js')


def build_dump():
	"""اگر دامپ نیست یا کهنه است، از نو بساز."""
	need = True
	try:
		need = os.path.getmtime(DUMP_PATH) < os.path.getmtime(
			os.path.join(ROOT, 'taranehyar', 'inc', 'tara-account-ui.php')
		)
	except OSError:
		need = True
	if not need:
		return
	if subprocess.call(['python3', os.path.join(ROOT, 'tools', 'minify-css.py')]) != 0:
		sys.exit('minify-css شکست خورد')
	with open(DUMP_PATH, 'w', encoding='utf-8') as fh:
		# خروجِ اسکریپتِ دامپ عمداً غیرِصفر است (با `exit` تمام می‌شود)؛
		# مهم محتوای فایل است.
		subprocess.call(
			['node', os.path.join(ROOT, 'tools', 'php-test.mjs'), 'tests/dump-391.php'],
			cwd=ROOT, stdout=fh, timeout=180,
		)


def main():
	build_dump()
	css = io.open(CSS_PATH, encoding='utf-8').read()
	# CSSِ عمومیِ قالب (`tara-ui`) هم درون‌خطی می‌شود تا دکمه‌های تازه
	# (مثلِ «مشاهده در ربات تلگرام») با استایلِ واقعی دیده شوند.
	ui_css_path = os.path.join(ROOT, 'taranehyar', 'css', 'tara-ui.min.css')
	if os.path.isfile(ui_css_path):
		css = io.open(ui_css_path, encoding='utf-8').read() + '\n' + css
	js = io.open(JS_PATH, encoding='utf-8').read().replace('</script>', '<\\/script>')
	dump = io.open(DUMP_PATH, encoding='utf-8').read()

	def block(name):
		head = '<!--TARA:%s-->' % name
		tail = '//TARA:%s' % name
		start = dump.index(head) + len(head)
		return dump[start:dump.index(tail, start)].strip('\n')

	def tab_block(name):
		head = '<!--TAB:%s-->' % name
		tail = '//TAB:%s' % name
		start = dump.index(head) + len(head)
		return dump[start:dump.index(tail, start)].strip('\n')

	modal = block('MODAL')
	picker = block('PICKER')
	panel = block('PANEL')
	snips = [s.strip('\n') for s in block('SNIPPETS').split('<!--SNIP:SEP-->')]
	tabs = {k: tab_block(k) for k in ('favorites', 'playlists', 'follows', 'history', 'notifs', 'telegram', 'profile')}

	palette = [
		('#7c3aed', '#db2777'), ('#0ea5e9', '#22d3ee'), ('#f59e0b', '#ef4444'),
		('#10b981', '#0ea5e9'), ('#6366f1', '#a855f7'), ('#ec4899', '#f97316'),
		('#0f766e', '#22c55e'), ('#b91c1c', '#f59e0b'),
	]
	state = {'i': 0}

	def svg_uri(c1, c2, glyph):
		svg = (
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">'
			'<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
			'<stop offset="0" stop-color="%s"/><stop offset="1" stop-color="%s"/></linearGradient></defs>'
			'<rect width="120" height="120" rx="20" fill="url(#g)"/>'
			'<text x="60" y="78" font-size="46" text-anchor="middle" fill="rgba(255,255,255,.94)" '
			'font-family="Tahoma, sans-serif">%s</text></svg>' % (c1, c2, glyph)
		)
		return 'data:image/svg+xml;base64,' + base64.b64encode(svg.encode('utf-8')).decode('ascii')

	def fix_one_img(tag):
		src = re.search(r'src="([^"]*)"', tag)
		if not src or src.group(1).startswith('data:'):
			return tag
		if 'avatar' in tag:
			uri = svg_uri('#185adb', '#7c3aed', '♪')
		else:
			c1, c2 = palette[state['i'] % len(palette)]
			state['i'] += 1
			uri = svg_uri(c1, c2, '♪')
		return re.sub(r'src="[^"]*"', 'src="%s"' % uri, tag, count=1)

	def finish(seg):
		"""تصویرهای نمونه → data: (پیش‌نمایش آفلاین است) و لینک‌های نمایشی → #."""
		seg = re.sub(r'<img[^>]*>', lambda m: fix_one_img(m.group(0)), seg)
		seg = re.sub(r'https://ex/\?tab=([a-z]+)', r'?tab=\1', seg)
		return re.sub(r'https://ex/[^"\s]*', '#', seg)

	def show_modal(seg, step):
		out = seg
		if step != 'phone':
			out = out.replace(' id="ty-acct-modal"', ' id="pv-modal-code"')
			out = out.replace('aria-labelledby="ty-acct-modal-title"', 'aria-labelledby="pv-modal-code-title"')
			out = out.replace('id="ty-acct-modal-title"', 'id="pv-modal-code-title"')
		out = out.replace('data-tara-account-modal hidden>', 'data-tara-account-modal>', 1)
		out = out.replace('<div class="ty-acct-modal" ', '<div class="ty-acct-modal is-open" ', 1)
		out = out.replace('data-tara-phone-shown></b>', 'data-tara-phone-shown>۰۹۱۲ ۱۲۳ ۴۵۶۷</b>', 1)
		if step == 'code':
			out = out.replace('<form class="ty-acct-form" data-tara-form="phone" novalidate>',
							  '<form class="ty-acct-form" data-tara-form="phone" novalidate hidden>', 1)
			out = out.replace('<form class="ty-acct-form" data-tara-form="code" hidden novalidate>',
							  '<form class="ty-acct-form" data-tara-form="code" novalidate>', 1)
			out = out.replace('data-tara-devcode hidden></p>',
							  'data-tara-devcode>حالتِ آزمایشی: کدِ شما <b dir="ltr">۴۹۲۸۰۶</b> است — پیامکی ارسال نشد.</p>', 1)
			out = out.replace('<span data-tara-resend-txt>می‌توانید کد را دوباره بگیرید.</span>',
							  '<span data-tara-resend-txt>ارسالِ دوباره تا ۴۲ ثانیه…</span>', 1)
			out = out.replace('<button type="button" class="ty-acct-link" data-tara-resend hidden>',
							  '<button type="button" class="ty-acct-link" data-tara-resend>', 1)
			for d in ('4', '9', '2', '8', '0', '6'):
				out = out.replace(
					'<input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1" dir="ltr"',
					'<input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1" dir="ltr" value="%s"' % d,
					1,
				)
		return out

	tab_labels = [
		('overview', 'نمای کلی'), ('favorites', 'علاقه‌مندی‌ها'), ('playlists', 'پلی‌لیست‌ها'),
		('follows', 'خواننده‌ها'), ('history', 'تاریخچه'), ('notifs', 'پیام‌ها'),
		('telegram', 'ربات'), ('profile', 'مشخصات'),
	]

	panes = []
	for i, (key, _label) in enumerate(tab_labels):
		body = panel if key == 'overview' else tabs[key]
		if i == 0:
			panes.append('<div class="pv-frame pv-pane" data-pane="%s">%s</div>' % (key, finish(body)))
		else:
			panes.append('<div class="pv-frame pv-pane is-off" data-pane="%s">%s</div>' % (key, finish(body)))

	chips = ''.join(
		'<button type="button" class="pv-chip" data-pv-tab="%s" aria-pressed="%s">%s</button>'
		% (key, 'true' if i == 0 else 'false', label)
		for i, (key, label) in enumerate(tab_labels)
	)

	page = io.open(os.path.join(ROOT, 'tools', 'preview-391.tpl.html'), encoding='utf-8').read()
	parts = {
		'__CSS__': css,
		'__JS__': js,
		'__MODAL_PHONE__': show_modal(finish(modal), 'phone'),
		'__MODAL_CODE__': show_modal(finish(modal), 'code'),
		'__CHIPS__': chips,
		'__PANES__': '\n'.join(panes),
		'__PICKER__': finish(picker),
		'__S_ACTIONS__': finish(snips[0]),
		'__S_HEADER__': finish(snips[1]),
		'__S_FOLLOW__': finish(snips[2]),
		'__S_LIKE__': finish(snips[3]),
		'__S_BOT__': finish(snips[4]),
	}
	for key, value in parts.items():
		page = page.replace(key, value)

	io.open(OUT, 'w', encoding='utf-8').write(page)
	print('ساخته شد: %s (%d بایت)' % (os.path.relpath(OUT, ROOT), len(page)))


if __name__ == '__main__':
	main()
