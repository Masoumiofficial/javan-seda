import { JSDOM } from 'jsdom';
import fs from 'node:fs';
import assert from 'node:assert/strict';
const dom = new JSDOM(`<form data-release-wizard><p data-release-progress></p>
<fieldset data-release-step><input name="full_name" required></fieldset>
<fieldset data-release-step><select name="service"><option value="song" data-price="1500000">آهنگ</option></select></fieldset>
<fieldset data-release-step></fieldset><fieldset data-release-step></fieldset>
<fieldset data-release-step><p data-release-summary></p></fieldset>
<button type="button" data-release-prev>قبلی</button><button type="button" data-release-next>بعدی</button><button type="submit" data-release-submit disabled>ثبت</button></form>`, {runScripts:'outside-only'});
dom.window.eval(fs.readFileSync('taranehyar/js/tara-release.js','utf8'));
const doc=dom.window.document, form=doc.querySelector('form'), steps=[...form.querySelectorAll('fieldset')], next=form.querySelector('[data-release-next]'), prev=form.querySelector('[data-release-prev]');
assert.equal(steps.filter(s=>!s.hidden).length,1); assert.equal(steps[0].hidden,false);
next.click(); assert.equal(steps[0].hidden,false,'required field blocks progression');
form.querySelector('input').value='نام'; next.click(); assert.equal(steps[1].hidden,false);
prev.click(); assert.equal(steps[0].hidden,false); assert.equal(form.querySelector('input').value,'نام');
for(let i=0;i<4;i++) next.click();
assert.equal(steps[4].hidden,false); assert.match(form.querySelector('[data-release-summary]').textContent,/آهنگ/);
assert.equal(form.querySelector('[data-release-submit]').disabled,true);
assert.equal(form.dispatchEvent(new dom.window.Event('submit',{bubbles:true,cancelable:true})),false);
console.log('release wizard smoke: ok');
