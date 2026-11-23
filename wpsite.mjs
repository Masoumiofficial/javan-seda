/**
 * wpsite.mjs — اجرای وردپرسِ واقعی داخل PHP-WASM با پایگاه‌دادهٔ SQLite
 * استفاده: import { site } from './wpsite.mjs'
 *
 * هر `site.run()` یک «درخواست» واقعی وردپرس است؛ داده در فایل SQLite روی
 * فایل‌سیستم میزبان (mount) می‌ماند. پیش‌نیاز:
 *   /tmp/wpcore/staging  ← وردپرس ۶.۷.۱ + دراپ‌این SQLite + قالب
 * اگر موجود نباشد، `rebuildHint()` راهنمای بازسازی می‌دهد.
 *
 * نکته: در پایانِ هر اسکریپت `process.exit(0)` لازم است؛ پروسه به‌دلیل
 * mountِ فایل‌سیستم بدون آن آویزان می‌ماند.
 */
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime, useHostFilesystem } from '@php-wasm/node';
import fs from 'node:fs';

export const SITE = '/tmp/wpcore/staging';

export function rebuildHint() {
  return [
    'سایت آزمون پیدا نشد: ' + SITE,
    'بازسازی:',
    '  1) curl -L https://codeload.github.com/WordPress/WordPress/tar.gz/refs/tags/6.7.1 | tar xz',
    '  2) cp -a wordpress/* ' + SITE,
    '  3) curl -L https://codeload.github.com/WordPress/sqlite-database-integration/tar.gz/refs/heads/main | tar xz',
    '     cp sqlite-database-integration/db.copy ' + SITE + '/wp-content/db.php',
    '  4) نوشتن wp-config.php با DB_ENGINE=sqlite',
    "  5) نصب: require wp-admin/includes/upgrade.php; wp_install('ترانه‌یار تست','admin','admin@example.com',true,'','admin-pass-12345')",
  ].join('\n');
}

export async function site(processId = 51) {
  if (!fs.existsSync(SITE + '/wp-load.php')) {
    throw new Error(rebuildHint());
  }
  const id = await loadNodeRuntime('8.4', { emscriptenOptions: { processId } });
  const php = new PHP(id);
  useHostFilesystem(php);
  return {
    php,
    /**
     * اجرای کد در بوتِ کاملِ وردپرس (WP_USE_THEMES).
     * کد در فایل نوشته می‌شود (نه eval) تا مارک‌آپ/چندخطی بی‌خطر باشد.
     */
    async run(code) {
      const file = SITE + '/wp-boot-run.php';
      const head = `<?php
$_SERVER['HTTP_HOST']='localhost'; $_SERVER['REQUEST_URI']='/';
$_SERVER['SERVER_NAME']='localhost'; $_SERVER['SERVER_PORT']='80';
$_SERVER['REQUEST_METHOD']='GET'; $_SERVER['SCRIPT_NAME']='/index.php';
define('WP_USE_THEMES', true);
require '${SITE}/wp-load.php';
`;
      fs.writeFileSync(file, head + code.replace(/^<\?php\s*/, ''));
      const out = await php.run({ code: `<?php require '${file}';` });
      return { text: out.text, errors: out.errors, exitCode: out.exitCode };
    },
    /**
     * اجرای کد بدون بوتِ وردپرس (فقط برای سنجشِ خودِ PHP، مثل parse).
     */
    async raw(code) {
      const file = SITE + '/wp-raw-run.php';
      fs.writeFileSync(file, code.replace(/^<\?php\s*/, '<?php '));
      const out = await php.run({ code: `<?php require '${file}';` });
      return { text: out.text, errors: out.errors, exitCode: out.exitCode };
    },
  };
}
