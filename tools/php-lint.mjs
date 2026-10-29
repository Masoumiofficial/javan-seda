/**
 * php-lint.mjs — بررسی سینتکسِ همهٔ فایل‌های PHP با PHPِ واقعی (WASM)
 * ---------------------------------------------------------------------------
 * چرا این ابزار؟ در این محیط PHP نصب نیست؛ ولی @php-wasm/node یک PHP 8.4 واقعی
 * در WebAssembly اجرا می‌کند. token_get_all($code, TOKEN_PARSE) پارسِ کامل انجام
 * می‌دهد و ParseError را پرتاب می‌کند ⇒ همان کاری که `php -l` می‌کند، بدون اجرا.
 *
 * استفاده:
 *   node tools/php-lint.mjs taranehyar [taranehyar/inc ...]
 *
 * خروجی: یک خط خلاصه + فهرست فایل‌های خطادار. کد خروج ۱ اگر خطایی باشد.
 */
import { PhpNode } from 'php-wasm/PhpNode';
import fs from 'node:fs';
import path from 'node:path';

const roots = process.argv.slice(2);
if (!roots.length) {
  console.error('usage: node tools/php-lint.mjs <dir-or-file> [...]');
  process.exit(2);
}

const collected = [];
function walk(target) {
  const st = fs.statSync(target);
  if (st.isDirectory()) {
    for (const entry of fs.readdirSync(target).sort()) {
      walk(path.join(target, entry));
    }
    return;
  }
  if (target.endsWith('.php')) collected.push(path.resolve(target));
}
for (const r of roots) walk(r);

if (!collected.length) {
  console.error('no php files found');
  process.exit(2);
}

const files = collected.map((abs, i) => ({
  parent: '/preload',
  name: 'f' + i + '.php',
  url: 'file://' + abs,
}));
const map = {};
collected.forEach((abs, i) => {
  map['f' + i + '.php'] = path.relative(process.cwd(), abs);
});

const php = new PhpNode({ files });
let out = '';
php.onoutput = (t) => {
  out += t && t.detail !== undefined ? t.detail : String(t);
};

const phpCode = `<?php
$map = json_decode($argvmap, true);
$ok = 0; $bad = array();
foreach (glob('/preload/*.php') as $f) {
    $name = basename($f);
    try { token_get_all(file_get_contents($f), TOKEN_PARSE); $ok++; }
    catch (ParseError $e) { $bad[] = ($map[$name] ?? $name) . ' :: ' . $e->getMessage() . ' (خط ' . $e->getLine() . ')'; }
}
echo "PHP=" . PHP_VERSION . "\\n";
echo "files=" . ($ok + count($bad)) . " ok=" . $ok . " bad=" . count($bad) . "\\n";
foreach ($bad as $b) { echo "FAIL " . $b . "\\n"; }
`
  .replace('$argvmap', JSON.stringify(JSON.stringify(map)))
  .replace('$argvmap', JSON.stringify(JSON.stringify(map)));

await php.run(phpCode);
process.stdout.write(out);
process.exit(/\sbad=0(\s|$)/.test(out) ? 0 : 1);
