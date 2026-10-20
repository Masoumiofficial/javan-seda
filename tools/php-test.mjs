/**
 * php-test.mjs — اجرای آزمونِ PHP با PHPِ واقعیِ WASM و شبیه‌سازِ وردپرس
 * استفاده: node tools/php-test.mjs tests/run-352.php
 */
import { PhpNode } from 'php-wasm/PhpNode';
import fs from 'node:fs';
import path from 'node:path';

const file = process.argv[2] || 'tests/run-352.php';
const root = process.cwd();
const theme = path.join(root, 'javanseda');

const incFiles = ['ab-artist-fields.php', 'ab-cat-desc.php', 'ab-terms-admin.php', 'ab-post-artist.php'];
const files = incFiles.map((name) => ({
  parent: '/tmp/theme/inc',
  name,
  url: 'file://' + path.join(theme, 'inc', name),
}));
files.push({ parent: '/preload/tests', name: 'stubs-wp.php', url: 'file://' + path.join(root, 'tests', 'stubs-wp.php') });
files.push({ parent: '/preload/tests', name: 'run-352.php', url: 'file://' + path.resolve(file) });

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
