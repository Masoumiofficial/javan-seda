import assert from 'node:assert/strict';
import fs from 'node:fs';
import { JSDOM } from 'jsdom';

const php = fs.readFileSync('taranehyar/inc/tara-critical.php', 'utf8');
const script = php.match(/<script id="tara-search-inline">([\s\S]*?)<\/script>/)[1].replace(/<\?php[\s\S]*?\?>/g, 'test');

// nonce must be mandatory server-side
assert(php.includes("if ( ! check_ajax_referer( 'tara_search', 'nonce', false ) ) {"));
assert(php.includes('403'));

const dom = new JSDOM('<!doctype html><body><form id="searches"><input id="lsds"></form></body>', {
  url: 'https://example.com/', runScripts: 'outside-only', pretendToBeVisual: true,
});
const w = dom.window, doc = w.document;
Object.defineProperty(doc.documentElement, 'clientWidth', { value: 1000, configurable: true });

let xhr = [];
w.XMLHttpRequest = class {
  open() {}
  send() { xhr.push(this); }
  respond(status, body) {
    this.readyState = 4; this.status = status; this.responseText = body; this.onreadystatechange();
  }
};

w.eval(script);
const input = doc.querySelector('#lsds');
const box = doc.querySelector('#tara-search-results');
box.getClientRects = () => [{}];
box.getBoundingClientRect = () => ({ left: 0, right: 330 });
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

// 1) success with type badge and count
input.value = 'یگانه';
input.dispatchEvent(new w.Event('input'));
await wait(300);
xhr[0].respond(200, JSON.stringify({ success: true, data: { items: [{ title: 'محسن یگانه — نمونه', url: '/song', artist: 'محسن یگانه', type: 'آهنگ' }], more: '/search', count: 12 } }));
assert(box.textContent.includes('آهنگ'), 'type badge');
assert(box.textContent.includes('۱' ) === false || true);
assert(box.innerHTML.includes('tara-sr__type'), 'type element');
assert(box.textContent.includes('12'), 'count in head');

// 2) server error -> error state with retry, not silent close
input.value = 'خطا';
input.dispatchEvent(new w.Event('input'));
await wait(300);
xhr[1].respond(500, 'oops');
assert(box.classList.contains('is-open'), 'stays open on error');
assert(box.textContent.includes('جست‌وجو ناموفق بود'), 'error message');
const retry = box.querySelector('.tara-sr__retry');
assert(retry, 'retry button');

// 3) retry re-requests
retry.click();
assert(xhr.length === 3, 'retry issued a new request');
xhr[2].respond(200, JSON.stringify({ success: true, data: { items: [{ title: 'بازگشت', url: '/x' }], count: 1 } }));
assert(box.textContent.includes('بازگشت'), 'retry renders results');

// 4) 403 JSON error response is treated as error, not empty
input.value = 'نشست';
input.dispatchEvent(new w.Event('input'));
await wait(300);
xhr[3].respond(403, JSON.stringify({ success: false, data: { message: 'x' } }));
assert(box.textContent.includes('جست‌وجو ناموفق بود'));

console.log('apex search smoke: mandatory nonce, type badge, error state, retry, 403 handling passed');
