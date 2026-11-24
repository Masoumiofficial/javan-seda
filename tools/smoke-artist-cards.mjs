/** Mobile touch/reveal and keyboard/link regression tests. */
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import fs from 'node:fs';
const dom = new JSDOM(`<!doctype html><body><div class="artist_boxs">
<a href="/a" class="ab-artist-card"><span class="ab-artist-card__media"><img><span class="ab-card-stats">10</span></span></a>
<a href="/b" class="ab-artist-card"><span class="ab-card-stats">20</span></a>
<a href="/c" class="ab-artist-card">no stats</a></div><div id="outside"></div></body>`, {url:'https://example.org', runScripts:'outside-only'});
const {window:w} = dom;
let touch = true;
w.matchMedia = q => ({matches:q === '(hover: none)' && touch});
w.eval(fs.readFileSync('taranehyar/js/tara-hub.js', 'utf8'));
const [a,b,c] = w.document.querySelectorAll('.ab-artist-card');
const click = (node, detail = 1, options = {}) => {
  const ev = new w.MouseEvent('click', {bubbles:true,cancelable:true,detail,...options});
  node.dispatchEvent(ev); return ev.defaultPrevented;
};
assert(click(a.querySelector('img'))); assert(a.classList.contains('is-revealed'));
assert(!click(a));
assert(click(b)); assert(!a.classList.contains('is-revealed')); assert(b.classList.contains('is-revealed'));
click(w.document.getElementById('outside')); assert(!b.classList.contains('is-revealed'));
assert(!click(c)); assert(!click(a,0)); assert(!click(a,1,{ctrlKey:true}));
touch = false; assert(!click(a)); assert(!a.classList.contains('is-revealed'));
w.close();
const css = fs.readFileSync('taranehyar/css/ab-352.css', 'utf8');
assert(css.includes('.ab-artist-card.is-revealed .ab-card-stats'));
assert(css.includes('.ab-artist-card__name--verified'));
assert(css.includes('.ab-artist-card__badge'));
console.log('artist cards touch, keyboard and structure: passed');
