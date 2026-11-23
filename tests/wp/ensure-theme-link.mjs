/**
 * قالبِ سایتِ آزمون باید *همان* درختِ مخزن باشد، نه یک کپیِ کهنه.
 * اگر staging موجود باشد، دایرکتوریِ قالب با symlink به مخزن جایگزین
 * می‌شود؛ در نتیجه هر ویرایشی در repo بلافاصله در WP دیده می‌شود.
 */
import fs from 'node:fs';

const STAGING = '/tmp/wpcore/staging';
const THEME_DIR = `${STAGING}/wp-content/themes/taranehyar`;
const REPO_THEME = '/home/user/javan-seda/taranehyar';

export function syncTheme() {
  if (!fs.existsSync(STAGING)) return false;
  let st = null;
  try { st = fs.lstatSync(THEME_DIR); } catch { /* نیست */ }
  if (st && st.isSymbolicLink() && fs.readlinkSync(THEME_DIR) === REPO_THEME) return true;
  if (st) {
    const aside = `${THEME_DIR}.bak-${Date.now()}`;
    fs.renameSync(THEME_DIR, aside);
    console.log(`[theme] کپی کهنه به ${aside} منتقل شد`);
  }
  fs.symlinkSync(REPO_THEME, THEME_DIR, 'dir');
  console.log('[theme] قالب به درخت مخزن symlink شد');
  return true;
}

if (import.meta.url === `file://${process.argv[1]}`) {
  process.exit(syncTheme() ? 0 : 1);
}
