import { site } from '../../wpsite.mjs';
import fs from 'node:fs';
import { resolve as pathResolve } from 'node:path';
import { syncTheme } from './ensure-theme-link.mjs';
const file = process.argv[2];
const s = await site(71);
/* فایل آزمون در همان ریشهٔ سایت نوشته می‌شود و با require اجرا می‌گردد؛
   این‌طور از evalِ کدِ چندخطی و <?php تودرتو دور می‌مانیم. */
/* فایل با همان مسیر نسبی کپی می‌شود (tests/wp/...) تا requireهای
   همسایه (fixtures.php) و dirname(__FILE__) درست کار کنند. */
const abs = file.startsWith('/') ? file : pathResolve(file);
fs.mkdirSync('/tmp/wpcore/staging/wp-tests', { recursive: true });
syncTheme();
const target = '/tmp/wpcore/staging/wp-tests/' + abs.split('/').pop();
fs.copyFileSync(abs, target);
fs.copyFileSync('/home/user/javan-seda/tests/wp/fixtures.php', '/tmp/wpcore/staging/wp-tests/fixtures.php');
const code = `<?php error_reporting(E_ALL); define('WP_DEBUG_DISPLAY', true); require '${target}';`;
const r = await s.run(code);
process.stdout.write(r.text || '');
if (r.errors) process.stderr.write('\nSTDERR:\n' + r.errors);
process.exit(0);
