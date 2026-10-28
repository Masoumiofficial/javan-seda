/**
 * php-test.mjs — اجرای آزمونِ PHP با PHPِ واقعیِ WASM و شبیه‌سازِ وردپرس
 * استفاده: node tools/php-test.mjs tests/run-352.php
 */
import { PhpNode } from 'php-wasm/PhpNode';
import fs from 'node:fs';
import path from 'node:path';

const file = process.argv[2] || 'tests/run-352.php';
const root = process.cwd();
const theme = path.join(root, 'taranehyar');

const incFiles = [
  'ab-artist-fields.php',
  'ab-cat-desc.php',
  'ab-terms-admin.php',
  'ab-post-artist.php',
  'ab-home-meta.php',
  'ab-artist-cards.php',
  'artist_slider.php',
  'ab-comments.php',
];
const files = incFiles.map((name) => ({
  parent: '/tmp/theme/inc',
  name,
  url: 'file://' + path.join(theme, 'inc', name),
}));
/* فایل‌های ریشهٔ قالب (آزمونِ سرچشمه: اینکه <title> دستی برنگشته باشد) */
for (const name of ['functions.php', 'header.php', 'myfunctions.php', 'style.css', 'comments.php']) {
  files.push({ parent: '/tmp/theme', name, url: 'file://' + path.join(theme, name) });
}
files.push({ parent: '/tmp/theme/inc', name: 'theme-options.php', url: 'file://' + path.join(theme, 'inc', 'theme-options.php') });
files.push({ parent: '/tmp/theme/inc', name: 'ab-controls.php', url: 'file://' + path.join(theme, 'inc', 'ab-controls.php') });
files.push({ parent: '/tmp/theme/css', name: 'ab-352.css', url: 'file://' + path.join(theme, 'css', 'ab-352.css') });
files.push({ parent: '/tmp/theme/css', name: 'icofont.min.css', url: 'file://' + path.join(theme, 'css', 'icofont.min.css') });
for (const js of ['owl.carousel.js', 'scripts.js']) {
  files.push({ parent: '/tmp/theme/js', name: js, url: 'file://' + path.join(theme, 'js', js) });
}
files.push({ parent: '/tmp/theme/inc', name: 'widgets.php', url: 'file://' + path.join(theme, 'inc', 'widgets.php') });
for (const name of ['admin-panel.php', 'admin-panel-views.php', 'ab-admin-ui.php']) {
  files.push({ parent: '/tmp/theme/inc', name, url: 'file://' + path.join(theme, 'inc', name) });
}
for (const css of ['admin-panel.css']) {
  files.push({ parent: '/tmp/theme/css', name: css, url: 'file://' + path.join(theme, 'css', css) });
}
for (const js of ['admin-panel.js']) {
  files.push({ parent: '/tmp/theme/js', name: js, url: 'file://' + path.join(theme, 'js', js) });
}
files.push({ parent: '/preload/tests', name: 'stubs-wp.php', url: 'file://' + path.join(root, 'tests', 'stubs-wp.php') });
files.push({ parent: '/preload/tests', name: path.basename(file), url: 'file://' + path.resolve(file) });

const php = new PhpNode({ files });
let out = '';
php.onoutput = (t) => { out += t && t.detail !== undefined ? t.detail : String(t); };
let err='';
php.onerror = (e) => { err += e && e.detail !== undefined ? e.detail : String(e); };
const code = '<?php require "/preload/tests/' + path.basename(file) + '";';
const rc = await php.run(code);
process.stdout.write(out);
if (err) process.stderr.write('\n--- stderr ---\n' + err + '\n');
const ok = /RESULT pass=\d+ fail=0/.test(out);
process.exit(ok ? 0 : 1);
