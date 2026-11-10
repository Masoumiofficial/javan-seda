/** DOM smoke test for compact queues and direct-message nonce. */
import { JSDOM } from 'jsdom';
import fs from 'node:fs';
import assert from 'node:assert/strict';
const dom = new JSDOM(`<!doctype html><body>
<div class="ty-artist"><ul class="ty-tracklist"><li>1</li><li>2</li><li>3</li><li>4</li><li>5</li></ul></div>
<div><ol data-radio-queue class="ty-list--collapsed"><li>1</li><li>2</li><li>3</li><li>4</li><li>5</li></ol><button data-tara-radio-more hidden></button></div>
<div class="ab-footer__col--links"><h4 class="ab-footer__title">پیوندها</h4><ul><li>Link</li></ul></div>
<form data-tara-hub="submit_track"><input name="title" value="Song"><input type="file" name="audio128"><button type="submit">Send</button><p data-tara-hub-msg></p></form>
<button data-tara-dm="41">Artist</button><div id="ty-dm" hidden><div data-ty-dm-body></div><span data-ty-dm-title></span><form data-ty-dm-form><input data-ty-dm-artist><input data-ty-dm-fan><textarea></textarea></form><button data-ty-dm-close></button></div>
</body>`, { url: 'https://example.org/', runScripts: 'outside-only' });
const { window } = dom;
window.matchMedia = () => ({matches:true});
window.TaraAccount = { api:'/ajax', nonce:'account-nonce', dmNonce:'dm-nonce' };
const requests = [];
window.fetch = (url, options) => { requests.push(options.body); return Promise.resolve({json: () => Promise.resolve({success:true,data:{messages:[]}})}); };
window.eval(fs.readFileSync('taranehyar/js/tara-hub.js', 'utf8'));
const toggle = window.document.querySelector('.ty-footer-toggle');
assert.equal(toggle.getAttribute('aria-expanded'), 'false');
assert.equal(window.document.getElementById(toggle.getAttribute('aria-controls')).hidden, true);
toggle.click(); assert.equal(toggle.getAttribute('aria-expanded'), 'true');
assert.equal(window.document.getElementById(toggle.getAttribute('aria-controls')).hidden, false);
const form = window.document.querySelector('[data-tara-hub=submit_track]');
form.dispatchEvent(new window.Event('submit', {bubbles:true, cancelable:true}));
assert.equal(requests[0].get('do'), 'submit_track');
assert.equal(requests[0].get('nonce'), 'account-nonce');
assert(requests[0].get('audio128') instanceof window.File);
const list = window.document.querySelector('.ty-tracklist');
const more = list.nextElementSibling;
assert(list.classList.contains('ty-list--collapsed'));
more.click(); assert.equal(more.getAttribute('aria-expanded'), 'true'); assert(!list.classList.contains('ty-list--collapsed'));
more.click(); assert.equal(more.getAttribute('aria-expanded'), 'false');
const radio = window.document.querySelector('[data-radio-queue]');
const radioMore = window.document.querySelector('[data-tara-radio-more]');
assert.equal(radioMore.hidden, false); radioMore.click(); assert(!radio.classList.contains('ty-list--collapsed'));
window.document.querySelector('[data-tara-dm]').click();
assert.equal(requests[1].get('nonce'), 'dm-nonce');
assert.equal(requests[1].get('artist'), '41');
window.document.querySelector('[data-ty-dm-close]').click();
window.close();
console.log('hub DOM smoke: passed');
