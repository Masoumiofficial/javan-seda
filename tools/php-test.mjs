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
files.push({ parent: '/tmp/theme/inc', name: 'tarlanweb_player.php', url: 'file://' + path.join(theme, 'inc', 'tarlanweb_player.php') });
for (const name of ['admin-panel.php', 'admin-panel-views.php', 'ab-admin-ui.php']) {
  files.push({ parent: '/tmp/theme/inc', name, url: 'file://' + path.join(theme, 'inc', name) });
}
for (const css of ['admin-panel.css']) {
  files.push({ parent: '/tmp/theme/css', name: css, url: 'file://' + path.join(theme, 'css', css) });
}
for (const js of ['admin-panel.js']) {
  files.push({ parent: '/tmp/theme/js', name: js, url: 'file://' + path.join(theme, 'js', js) });
}
/* فایل‌های تازهٔ ۳.۷.۰ — آزمون run-370 */
for (const name of ['tara-taxonomies.php', 'tara-ui.php', 'tara-critical.php', 'tara-brand.php', 'admin-settings-shell.php', 'tax-listing.php', 'ab-credits.php', 'vip_slider.php', 'ab-seo.php', 'ab-logo-setting.php', 'ab-post-extras.php', 'ab-vip-image.php', 'tara-assets.php', 'ab-glass.php', 'ab-ui-guard.php']) {
  files.push({ parent: '/tmp/theme/inc', name, url: 'file://' + path.join(theme, 'inc', name) });
}
for (const name of ['tara-ui.css', 'tara-artist.css', 'tara-home.css', 'admin-settings.css', 'ab-glass.css', 'ab-ui.css', 'ab-vip-image.css', 'tara-ui.min.css', 'style.min.css', 'ab-glass.min.css']) {
  files.push({ parent: '/tmp/theme/css', name, url: 'file://' + path.join(theme, 'css', name) });
}
for (const name of ['tara-player.js', 'hero-slider.js', 'admin-settings.js', 'ab-vip-image.js']) {
  files.push({ parent: '/tmp/theme/js', name, url: 'file://' + path.join(theme, 'js', name) });
}
for (const name of ['single.php', 'category.php', 'footer.php', '404.php', 'taxonomy-artist.php', 'taxonomy-tara_genre.php', 'home.php', 'search.php', 'inc/songs_post.php', 'inc/mobiles_header.php', 'inc/no-content.php']) {
  const parent = name.includes('/') ? '/tmp/theme/inc' : '/tmp/theme';
  const base = name.includes('/') ? name.split('/')[1] : name;
  files.push({ parent, name: base, url: 'file://' + path.join(theme, name) });
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
