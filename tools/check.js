#!/usr/bin/env node
/*
 * check.js — احراز سینتکس PHP بدون نصب PHP (با php-parser)
 * استفاده: node tools/check.js <file.php> [more.php ...]
 * مثال (وقتی php-parser در /home/user/lint نصب است):
 *   PHP_PARSER_PATH=/home/user/lint/node_modules/php-parser node tools/check.js taranehyar/single.php
 *  • خطای سینتکس  → NON-ZERO exit + شمارهٔ خط
 *  • ساختارهای جدیدتر از PHP 5.6  → هشدار (و exit غیرصفر، چون کفِ این قالب 5.6 است)
 */
const fs = require('fs');
const path = require('path');
/* مسیر php-parser: یا در پروژه npm install php-parser، یا با PHP_PARSER_PATH بدهید */
const PARSER_MOD = process.env.PHP_PARSER_PATH || 'php-parser';
const Parser = require(PARSER_MOD);

/* نیازمند: npm i php-parser  ·  اگر globally نصب نیست: PHP_PARSER_PATH=/path/to/php-parser node tools/check.js <file.php> */
const opts = { parser: { debug: false, suppressErrors: false, extractDoc: false }, ast: { withPositions: true } };
if (process.env.PHP_VERSION) { opts.parser.phpVersion = process.env.PHP_VERSION; }
const parser = new Parser(opts);

/* گره‌ها/ویژگی‌هایی که در PHP 5.6 مجاز نیستند */
const BAD_NODES = {
  nullsafeprop: '?-> (PHP 8.0+)',
  match: 'match() (PHP 8.0+)',
  enum: 'enum (PHP 8.1+)',
  readonly: 'readonly (PHP 8.1+)',
  arrowfunc: 'fn() arrow function (PHP 7.4+)',
  namedargument: 'named arguments (PHP 8.0+)',
  firstclasscallable: 'first-class callable (PHP 8.1+)',
};

function walk(node, out, file, ctx) {
  if (!node || typeof node !== 'object') return;
  if (Array.isArray(node)) { node.forEach((n) => walk(n, out, file, ctx)); return; }
  const kind = node.kind;
  if (kind && BAD_NODES[kind]) out.push({ line: node.loc && node.loc.start ? node.loc.start.line : 0, what: BAD_NODES[kind] });
  if (kind === 'bin' && ['??', '?:'].indexOf(node.operator) >= 0) out.push({ line: node.loc && node.loc.start ? node.loc.start.line : 0, what: node.operator + ' (PHP 7+/8+)' });
  if (kind === 'bin' && node.operator === '**') out.push({ line: node.loc && node.loc.start ? node.loc.start.line : 0, what: '** pow (PHP 5.6 OK — ignored)' });
  if (kind === 'closure' && node.isStatic) out.push({ line: node.loc && node.loc.start ? node.loc.start.line : 0, what: 'static closure (PHP 7.4+)' });
  if (kind === 'operation' && /=>\s*\(?\s*static\s+function/i.test(String(node.raw || ''))) out.push({ line: 0, what: 'static closure (PHP 7.4+)' });
  /* typed params / return types */
  if (kind === 'parameter' && node.type) out.push({ line: node.loc && node.loc.start ? node.loc.start.line : 0, what: 'typed parameter (PHP 7.0+)' });
  if (kind === 'decl' && node.name && ctx) { /* noop */ }
  if (node.returnType && node.returnType !== null && (node.kind === 'function' || node.kind === 'method')) {
    out.push({ line: node.loc && node.loc.start ? node.loc.start.line : 0, what: 'return type (PHP 7.0+)' });
  }
  if (kind === 'class' && node.extends === undefined && node.implements === undefined && node.isFinal) { /* final class مجاز */ }
  if (kind === 'propertydeclaration' && node.type) out.push({ line: node.loc && node.loc.start ? node.loc.start.line : 0, what: 'typed property (PHP 7.4+)' });
  for (const k of Object.keys(node)) {
    if (k === 'loc' || k === 'locStart' || k === 'locEnd' || k === 'raw' && typeof node.raw === 'string') continue;
    walk(node[k], out, file, ctx);
  }
}

let bad = 0;
for (const f of process.argv.slice(2)) {
  const src = fs.readFileSync(f, 'utf8');
  let ast;
  try {
    ast = parser.parseCode(src, path.basename(f));
  } catch (e) {
    bad++;
    const m = String(e.message || e).split('\n')[0];
    console.log('FAIL ' + path.basename(f) + '  →  ' + m);
    continue;
  }
  const children = (ast.children || []);
  const issues = [];
  walk(children, issues, f, true);
  /* حذف مورد بی‌فایدهٔ ** (که در 5.6 مجاز است) */
  const real = issues.filter((i) => i.what.indexOf('ignored') < 0);
  const uniq = [];
  const seen = new Set();
  for (const i of real) { const k = i.what + '@' + i.line; if (!seen.has(k)) { seen.add(k); uniq.push(i); } }
  if (uniq.length) {
    bad++;
    console.log('WARN ' + path.basename(f) + '  (' + uniq.length + ' ساختارِ بالاتر از PHP 5.6)');
    uniq.slice(0, 12).forEach((i) => console.log('      line ' + i.line + '  ' + i.what));
  } else {
    console.log('OK   ' + f + '  (top nodes: ' + children.length + ')');
  }
}
process.exit(bad ? 1 : 0);
