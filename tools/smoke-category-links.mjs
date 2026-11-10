/** jsdom tests of collapse state; no real layout or browser rendering. */
import { JSDOM } from 'jsdom';
import fs from 'node:fs';
import assert from 'node:assert/strict';
const js=fs.readFileSync('taranehyar/js/ab-cat-desc.js','utf8');
const css=fs.readFileSync('taranehyar/css/ab-352.css','utf8');
const bodyRule=css.match(/\.ab-cat-desc__body\s*\{([^}]*)\}/)[1];
const clampRule=css.match(/\.ab-cat-desc__body\.is-clamped\s*\{([^}]*)\}/)[1];
assert(/max-height:\s*none/.test(bodyRule) && /overflow:\s*visible/.test(bodyRule));
assert(/overflow:\s*hidden/.test(clampRule));
assert(/\.ab-cat-desc__body a\[href\][\s\S]*?text-decoration:\s*underline/.test(css));
assert(/\.night \.ab-cat-desc__body a\[href\]/.test(css));
function setup(scroll,visible){
 const dom=new JSDOM('<section data-ab-desc><div data-ab-desc-body><p>متن <a href="/music/">موسیقی</a></p></div><button hidden data-ab-desc-toggle aria-expanded="true">نمایش بیشتر</button></section>',{url:'https://example.org/',runScripts:'outside-only'});
 const {window:w}=dom; const body=w.document.querySelector('[data-ab-desc-body]');
 Object.defineProperty(body,'scrollHeight',{get:()=>scroll}); Object.defineProperty(body,'clientHeight',{get:()=>visible});
 w.requestAnimationFrame=cb=>cb(); w.eval(js); w.document.dispatchEvent(new w.Event('DOMContentLoaded'));
 return {dom,w,body,btn:w.document.querySelector('button')};
}
{
 const {dom,body,btn}=setup(4000,190);
 assert(body.classList.contains('is-clamped')); assert.equal(btn.hidden,false); assert.equal(btn.getAttribute('aria-expanded'),'false');
 btn.click(); assert(!body.classList.contains('is-clamped')); assert.equal(btn.getAttribute('aria-expanded'),'true'); assert.equal(body.querySelector('a').getAttribute('href'),'/music/');
 btn.click(); assert(body.classList.contains('is-clamped')); body.querySelector('a').focus(); assert(!body.classList.contains('is-clamped'),'keyboard focus expands clipped content');
 dom.window.close();
}
{
 const {dom,body,btn}=setup(180,150); assert(body.classList.contains('is-clamped')); assert.equal(btn.hidden,false,'mobile 150px threshold must expose toggle'); dom.window.close();
}
{
 const {dom,body,btn}=setup(120,120); assert(!body.classList.contains('is-clamped')); assert.equal(btn.hidden,true); dom.window.close();
}
console.log('category links, expanded height, mobile and keyboard behavior: passed');
