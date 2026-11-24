import assert from 'node:assert/strict';
import fs from 'node:fs';
import {JSDOM} from 'jsdom';
const source=fs.readFileSync('taranehyar/js/tara-player.js','utf8');
const track=(title)=>({title,artist:'Artist',src:'https://example.com/'+title+'.mp3',link:'https://example.com/'});
async function setup(context, tracks) {
 const d=new JSDOM(`<!doctype html><body>${context ? `<section data-tara-player="${context}"><script type="application/json" class="ty-player__data">${JSON.stringify(tracks)}</script><button data-tara-toggle></button><span data-tara-title></span></section>`:''}<div data-tara-dock hidden><span data-tara-dock-title></span><button data-tara-dock-toggle></button><button data-tara-dock-close></button><button data-tara-dock-next></button><button data-tara-dock-list></button></div></body>`,{runScripts:'outside-only',url:'https://example.com/',pretendToBeVisual:true});
 let plays=0; const w=d.window;
 w.HTMLMediaElement.prototype.load=function(){};
 w.HTMLMediaElement.prototype.play=function(){plays++;Object.defineProperty(this,'paused',{configurable:true,value:false});this.dispatchEvent(new w.Event('playing'));return Promise.resolve()};
 w.HTMLMediaElement.prototype.pause=function(){Object.defineProperty(this,'paused',{configurable:true,value:true});this.dispatchEvent(new w.Event('pause'))};
 w.eval(source); await new Promise(r=>setTimeout(r,50));return {w,doc:w.document,plays:()=>plays};
}
let t=await setup('single',[track('one'),track('two')]);
assert.equal(t.plays(),0);assert.equal(t.doc.querySelector('[data-tara-dock]').hidden,false);assert.equal(t.doc.querySelector('[data-tara-dock-title]').textContent,'one');
t.doc.querySelector('[data-tara-dock-toggle]').click();assert.equal(t.plays(),1);assert(t.doc.querySelector('[data-tara-player]').classList.contains('is-playing'));
t.doc.querySelector('[data-tara-dock-next]').click();assert.equal(t.doc.querySelector('[data-tara-dock-title]').textContent,'two');
t.doc.querySelector('[data-tara-dock-close]').click();await new Promise(r=>setTimeout(r,300));assert(t.doc.querySelector('[data-tara-dock]').hidden);
t.doc.querySelector('[data-tara-toggle]').click();assert.equal(t.doc.querySelector('[data-tara-dock]').hidden,false);
for(const ctx of ['home',null]) {t=await setup(ctx,[track('one')]);assert(t.doc.querySelector('[data-tara-dock]').hidden);assert.equal(t.plays(),0);t.w.close()}
t=await setup('single',[]);assert(t.doc.querySelector('[data-tara-dock]').hidden);
const bounds=fs.readFileSync('taranehyar/js/tara-search-bounds.js','utf8');
t=new JSDOM('<!doctype html><body><div class="searchwp-live-search-results" style="left: -20px"></div></body>',{runScripts:'outside-only',pretendToBeVisual:true});
Object.defineProperty(t.window.document.documentElement,'clientWidth',{value:320});let box=t.window.document.querySelector('div');box.getClientRects=()=>[{}];box.getBoundingClientRect=()=>{let x=parseFloat(box.style.left);return {left:x,right:x+200}};t.window.eval(bounds);await new Promise(r=>setTimeout(r,50));assert.equal(box.style.left,'8px');box.style.left='280px';await new Promise(r=>setTimeout(r,50));assert.equal(box.style.left,'112px');await new Promise(r=>setTimeout(r,50));
console.log('3.13 player and search DOM smoke: passed');
