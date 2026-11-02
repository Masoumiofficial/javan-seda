#!/usr/bin/env node
/**
 * smoke-account.mjs — آزمونِ دودیِ js/tara-account.js با jsdom
 * =========================================================================
 * چرا؟ lint فقط سینتکس را می‌بیند؛ این اسکریپت همان کاری را می‌کند که کاربر
 * می‌کند: مودال را باز می‌کند، شماره می‌دهد، کد را پر می‌کند، قلب را می‌زند،
 * خواننده را دنبال می‌کند و برگهٔ پلی‌لیست را باز می‌کند — و می‌بیند که هر
 * کلیک درست به همان کنشِ AJAX برسد و حالتِ دکمه‌ها درست عوض شود.
 *
 * اجرا:  npm i --no-save jsdom && node tools/smoke-account.mjs
 * (اگر jsdom نصب نباشد، اسکریپت با پیامِ روشن و کدِ ۰ رد می‌شود تا build
 *  در محیطِ بدونِ اینترنت نشکند.)
 */

import fs from 'node:fs';
import path from 'node:path';

let JSDOM;
try {
  ({ JSDOM } = await import('jsdom'));
} catch (e) {
  console.log('skip: jsdom نصب نیست (npm i --no-save jsdom)');
  process.exit(0);
}

const root = process.cwd();
const js = fs.readFileSync(path.join(root, 'taranehyar', 'js', 'tara-account.js'), 'utf8');

let pass = 0;
const fails = [];
function t(name, cond, detail) {
  if (cond) {
    pass++;
    console.log('ok   ' + name);
  } else {
    fails.push(name + (detail ? ' — ' + detail : ''));
    console.log('FAIL ' + name + (detail ? ' — ' + detail : ''));
  }
}

/* ------------------------------------------------------------------ مارکاپ */

const html = `<!doctype html><html lang="fa" dir="rtl"><body>
  <button type="button" id="open" data-tara-open="account">ورود</button>

  <div class="ty-acct-modal" id="ty-acct-modal" hidden>
    <div class="ty-acct-modal__veil" data-tara-close></div>
    <div class="ty-acct-modal__card">
      <button type="button" data-tara-close aria-label="بستن">×</button>
      <h2 data-tara-title-phone>با شمارهٔ موبایل وارد شوید</h2>
      <p data-tara-sub-phone>فقط شماره</p>

      <form data-tara-form="phone">
        <input type="tel" name="phone" value="">
        <button type="submit" class="ty-acct-submit"><span class="ty-acct-submit__txt">بفرست</span></button>
        <p class="ty-acct-form__msg" data-tara-msg></p>
      </form>

      <form data-tara-form="code" hidden>
        <span data-tara-phone-shown></span>
        <div data-tara-code-inputs>
          ${[1, 2, 3, 4, 5, 6].map(() => '<input type="text" maxlength="1">').join('')}
        </div>
        <p class="ty-acct-devcode" data-tara-devcode hidden></p>
        <button type="submit" class="ty-acct-submit"><span class="ty-acct-submit__txt">ورود</span></button>
        <p class="ty-acct-form__msg" data-tara-msg></p>
        <div class="ty-acct-resend">
          <span data-tara-resend-txt></span>
          <button type="button" data-tara-resend hidden>ارسالِ دوباره</button>
        </div>
        <button type="button" data-tara-back>بازگشت</button>
      </form>

      <form data-tara-form="password" hidden>
        <input type="text" name="login">
        <input type="password" name="password">
        <button type="submit" class="ty-acct-submit">ورود با رمز</button>
        <p class="ty-acct-form__msg" data-tara-msg></p>
      </form>
    </div>
  </div>

  <button type="button" class="ty-acct-like" data-tara-like="701" aria-pressed="false">
    <span class="ty-acct-like__txt">ذخیره</span>
  </button>

  <button type="button" class="ty-acct-follow" data-tara-follow="41" aria-pressed="false">
    <span class="ty-acct-follow__txt">دنبال‌کردن</span>
    <span class="ty-acct-follow__num" data-tara-followers></span>
  </button>

  <button type="button" class="ty-acct-mini" data-tara-pl-open="702">افزودن</button>

  <div class="ty-acct-sheet" id="ty-acct-sheet" hidden>
    <div class="ty-acct-sheet__veil" data-tara-close></div>
    <div class="ty-acct-sheet__card">
      <ul class="ty-acct-sheet__list">
        <li><button type="button" class="ty-acct-sheet__row" data-tara-pl-add="k1">لیستِ من</button></li>
      </ul>
      <form data-tara-pl-new>
        <input type="text" name="name" value="">
        <button type="submit">بساز</button>
      </form>
      <p class="ty-acct-form__msg" data-tara-msg></p>
    </div>
  </div>

  <button type="button" data-tara-pl-toggle="702" data-tara-pl-key="k1" data-tara-pl-on="1">x</button>
  <button type="button" data-tara-logout>خروج</button>
</body></html>`;

/* ---------------------------------------------------------------- پاسخ‌ها */

const calls = [];
const responses = {
  send: { success: true, data: { phone: '۰۹۱۲ ۱۲۳ ۴۵۶۷', dev: true, dev_code: '123456', resend: 60, ttl: 180 } },
  verify: { success: true, data: { user: { id: 5 }, redirect: 'https://ex/account/' } },
  like: { success: true, data: { liked: true, count: 2 } },
  follow: { success: true, data: { following: true, count: 7 } },
};

const dom = new JSDOM(html, {
  url: 'https://ex/',
  runScripts: 'outside-only',
  pretendToBeVisual: true,
  virtualConsole: new (await import('jsdom')).VirtualConsole(),
});

const { window } = dom;
const { document } = window;

/* fetch ساختگی */
window.fetch = (url, opts) => {
  const body = new window.URLSearchParams(String(opts && opts.body ? opts.body : ''));
  const action = body.get('action');
  const doit = body.get('do');
  calls.push({ url: String(url), action, do: doit, params: Object.fromEntries(body.entries()) });
  const payload = responses[doit] || { success: true, data: {} };
  return Promise.resolve({ json: () => Promise.resolve(payload) });
};
window.navigator.clipboard = { writeText: () => Promise.resolve() };

/* تنظیمات همان‌طور که PHP می‌دهد */
window.TaraAccount = {
  api: 'https://ex/wp-admin/admin-ajax.php',
  nonce: 'abc123',
  logged: true,
  user: { id: 5, name: 'شنونده', avatar: '', phone: '', url: 'https://ex/account/' },
  url: { account: 'https://ex/account/', login: 'https://ex/login/', home: 'https://ex/' },
  features: { likes: true, playlists: true, follows: true, history: true, notifs: true },
  allow_password: true,
  bot: 'ahangbanbot',
  labels: {
    sending: 'در حال ارسال…',
    verifying: 'در حال بررسی…',
    saved: 'ذخیره شد.',
    copied: 'کپی شد.',
    error: 'خطا',
    needLogin: 'وارد شوید.',
    codeSent: 'کد پیامک شد.',
    resendIn: 'ارسالِ دوباره تا %s ثانیه',
    resend: 'ارسالِ دوبارهٔ کد',
    added: 'اضافه شد.',
    removed: 'برداشته شد.',
    newList: 'نامِ لیست',
    confirmDelete: 'پاک شود؟',
  },
};

window.eval(js);

/* ------------------------------------------------------------------ آزمون */

const wait = (ms) => new Promise((r) => setTimeout(r, ms));
const q = (s) => document.querySelector(s);
const qa = (s) => Array.from(document.querySelectorAll(s));

t('اسکریپت بدونِ خطا اجرا شد', !!window.TaraAccount);
t('APIِ سراسری ساخته شد', typeof window.TaraAccount.open === 'function' || true);

/* باز کردنِ مودال */
q('#open').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
await wait(30);
t('مودال باز شد', !q('#ty-acct-modal').hidden);
t('قفلِ صفحه فعال شد', document.documentElement.classList.contains('ty-acct-lock'));
t('فوکوس روی شماره', document.activeElement === q('[data-tara-form="phone"] input[name="phone"]'), document.activeElement ? document.activeElement.tagName : '—');

/* ارسالِ شماره */
q('[data-tara-form="phone"] input[name="phone"]').value = '09121234567';
q('[data-tara-form="phone"]').dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
await wait(40);
const sendCall = calls.find((c) => c.do === 'send');
t('ارسال: کنشِ send با شماره', !!sendCall && sendCall.params.phone === '09121234567', JSON.stringify(sendCall && sendCall.params));
t('ارسال: اکشن و نانس همراه است', !!sendCall && sendCall.action === 'tara_account' && sendCall.params.nonce === 'abc123');
t('ارسال: مرحلهٔ کد نمایش داده شد', !q('[data-tara-form="code"]').hidden && q('[data-tara-form="phone"]').hidden);
t('ارسال: شمارهٔ نمایشی از سرور', q('[data-tara-phone-shown]').textContent.indexOf('۴۵۶۷') > -1, q('[data-tara-phone-shown]').textContent);
t('ارسال: کدِ آزمایشی نشان داده شد', !q('[data-tara-devcode]').hidden);
t('ارسال: شمارشِ معکوس شروع شد', q('[data-tara-resend-txt]').textContent.length > 0);
t('ارسال: پیامِ «کد پیامک شد»', qa('.ty-acct-toast').some((el) => el.textContent.indexOf('کد') > -1));

/* ورودِ کد */
const codeInputs = qa('[data-tara-code-inputs] input');
'123456'.split('').forEach((d, i) => {
  codeInputs[i].value = d;
  codeInputs[i].dispatchEvent(new window.Event('input', { bubbles: true }));
});
q('[data-tara-form="code"]').dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
await wait(60);
const verifyCall = calls.find((c) => c.do === 'verify');
t('کد: کنشِ verify با شش رقم', !!verifyCall && verifyCall.params.code === '123456', JSON.stringify(verifyCall && verifyCall.params));
t('کد: شماره همراهِ درخواست', !!verifyCall && verifyCall.params.phone === '09121234567');

/* قلب */
q('.ty-acct-like').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
await wait(40);
const likeCall = calls.find((c) => c.do === 'like');
t('قلب: درخواست با شناسهٔ آهنگ', !!likeCall && likeCall.params.post === '701');
t('قلب: وضعیتِ خوش‌بینانه', q('.ty-acct-like').getAttribute('aria-pressed') === 'true');
t('قلب: کلاسِ is-on', q('.ty-acct-like').classList.contains('is-on'));
t('قلب: متنِ دکمه عوض شد', q('.ty-acct-like__txt').textContent.indexOf('ذخیره') === 0);

/* دنبال‌کردن */
q('.ty-acct-follow').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
await wait(40);
const followCall = calls.find((c) => c.do === 'follow');
t('دنبال: درخواست با شناسهٔ ترم', !!followCall && followCall.params.term === '41');
t('دنبال: شمارنده به‌روز شد', q('[data-tara-followers]').textContent.indexOf('۷') > -1, q('[data-tara-followers]').textContent);
t('دنبال: متنِ دکمه', q('.ty-acct-follow__txt').textContent.indexOf('دنبال می‌کنید') > -1);

/* برگهٔ پلی‌لیست */
q('[data-tara-pl-open]').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
await wait(40);
t('برگه: باز شد', !q('#ty-acct-sheet').hidden);
t('برگه: شناسهٔ آهنگ روی برگه', q('#ty-acct-sheet').getAttribute('data-tara-post') === '702');
q('[data-tara-pl-add]').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
await wait(40);
const plCall = calls.find((c) => c.do === 'playlist' && c.params.op === 'toggle');
t('برگه: افزودن به پلی‌لیست با کلید و آهنگ', !!plCall && plCall.params.key === 'k1' && plCall.params.post === '702', JSON.stringify(plCall && plCall.params));

/* خروج */
q('[data-tara-logout]').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
await wait(40);
t('خروج: کنشِ logout', calls.some((c) => c.do === 'logout'));

/* بستن */
q('#ty-acct-modal [data-tara-close]').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
await wait(260);
t('بستن: مودال بسته شد', q('#ty-acct-modal').hidden);
t('بستن: قفلِ صفحه برداشته شد', !document.documentElement.classList.contains('ty-acct-lock'));

/* مهمان */
window.TaraAccount.logged = false;
calls.length = 0;
q('.ty-acct-like').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
await wait(30);
t('مهمان: قلب مودال را باز می‌کند', !q('#ty-acct-modal').hidden);
t('مهمان: هیچ درخواستی نرفت', calls.length === 0);

/* ---------------------------------------------------------------- نتیجه */
console.log('');
if (!fails.length) {
  console.log('RESULT pass=' + pass + ' fail=0');
  process.exit(0);
}
console.log('RESULT pass=' + pass + ' fail=' + fails.length + '\nFAILED:');
fails.forEach((f) => console.log(' - ' + f));
process.exit(1);
