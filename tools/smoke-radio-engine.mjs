/** Radio engine regression tests with controlled Audio and AJAX doubles, not a real browser/audio device. */
import { JSDOM } from 'jsdom';
import fs from 'node:fs';
import assert from 'node:assert/strict';
const code = fs.readFileSync('taranehyar/js/tara-radio.js', 'utf8');
const songs = [1,2,3].map(id => ({id,title:'track '+id,src:`https://example.org/${id}.mp3`,link:`https://example.org/song/${id}`}));
const markup = (tracks=songs) => `<section data-tara-radio data-radio-mode="mixed"><script class="ty-radio__data" type="application/json">${JSON.stringify({tracks})}</script><div data-radio-audio></div><div data-radio-queue></div><span data-radio-state></span><span data-radio-toast></span><button data-radio-toggle></button></section>`;
async function setup(html=markup()) {
 const dom = new JSDOM(`<!doctype html><body>${html}</body>`, {url:'https://example.org/',runScripts:'outside-only'});
 const w=dom.window; let rafId=0;
 w.requestAnimationFrame=()=>++rafId; w.cancelAnimationFrame=()=>{};
 w.TaraRadio={api:'/ajax',nonce:'n',config:{xfade:4}};
 w.Audio=function(){const el=w.document.createElement('audio');el._paused=true;el._reject=false;el.pause=function(){this._paused=true;};el.load=function(){};el.play=function(){if(this._reject){this._reject=false;return Promise.reject(new Error('blocked'));} this._paused=false;return Promise.resolve();};return el;};
 w.fetch=()=>Promise.resolve({json:()=>Promise.resolve({success:true,data:{tracks:[]}})});
 w.eval(code); w.document.dispatchEvent(new w.Event('DOMContentLoaded')); return {w,dom,radio:w.TaraRadioEngine.instances[0]};
}
const flush=()=>new Promise(r=>setTimeout(r,0));
{
 const {w,dom,radio:r}=await setup();
 r.play(r.queue[1]); await flush();
 assert.equal(r.queue.length,3,'direct track does not duplicate queue'); assert.equal(r.cursor,1); assert.equal(r.nowId,2);
 r.advance('user',4); assert.equal(r.state,'fading'); assert.equal(r.cursor,2);
 const incoming=r.decks[1-r.active]; assert.equal(incoming._paused,false);
 r.pause(); assert.equal(r.state,'paused'); assert(r.decks.every(a=>a._paused)); assert.equal(incoming.volume,0); assert.equal(r.cursor,1); assert.equal(r.rafPending,false);
 r.resume(); await flush(); assert.equal(r.state,'playing'); assert.equal(r.rafPending,true);
 r.pause(); r.current()._reject=true; r.resume(); await flush(); assert.equal(r.state,'paused','failed resume does not pretend to play');
 dom.window.close();
}
{
 const {dom,radio:r}=await setup();
 r.play(); await flush(); const outgoing=r.current(); const incoming=r.decks[1-r.active]; incoming._reject=true;
 r.advance('user',4); await flush(); assert.equal(r.state,'playing','failed crossfade restores old deck'); assert.equal(r.current(),outgoing); assert(incoming._paused); assert.equal(r.nowId,1); assert.equal(r.cursor,0);
 r.stop(); assert(r.decks.every(a=>a._paused)); dom.window.close();
}
{
 const {dom,radio:r}=await setup();
 r.play(); await flush(); const incoming=r.decks[1-r.active]; let accept;
 incoming.play=()=>new Promise(resolve=>{accept=resolve;});
 r.advance('user',4); assert.equal(r.fade.waiting,true);
 r.applyGains(); assert.equal(incoming.volume,0,'no fade until incoming audio starts');
 accept(); await flush(); assert.equal(r.fade.waiting,false);
 r.pause(); assert(r.decks.every(a=>a._paused)); dom.window.close();
}
{
 const {dom,radio:r}=await setup();
 r.bad['1']=true; r.cursor=0; r.play(); await flush(); assert.equal(r.nowId,2,'bad current track skips forward'); assert.equal(r.queue.length,3);
 dom.window.close();
}
{
 const {w,dom,radio:r}=await setup(); const pending=[];
 w.fetch=url=>new Promise(resolve=>pending.push({url,resolve}));
 r.setMode('new'); r.setMode('hot'); assert.equal(pending.length,2);
 pending[1].resolve({json:()=>Promise.resolve({success:true,data:{tracks:[{id:9,title:'hot',src:'https://example.org/9.mp3'}]}})});
 await flush(); await flush(); assert.equal(r.nowId,9); assert.equal(r.queue.length,1);
 pending[0].resolve({json:()=>Promise.resolve({success:true,data:{tracks:[{id:8,title:'old',src:'https://example.org/8.mp3'}]}})});
 await flush(); await flush(); assert.equal(r.nowId,9); assert.equal(r.queue[0].id,9,'stale source response ignored');
 dom.window.close();
}
{
 const {w,dom,radio:r}=await setup();
 w.fetch=()=>Promise.resolve({json:()=>Promise.resolve({success:true,data:{tracks:[]}})});
 r.setMode('views'); await flush(); await flush();
 assert.equal(r.mode,'mixed'); assert.deepEqual(Array.from(r.queue, t=>t.id),[1,2,3], 'failed source change restores prior queue');
 dom.window.close();
}
{
 const {w,dom,radio:r}=await setup(markup([songs[0]])); let resolveFetch; let requests=0;
 w.fetch=()=>{ requests++; return new Promise(resolve=>{resolveFetch=resolve;}); };
 r.play(); await flush(); assert.equal(requests,1,'last track proactively requests refill');
 r.advance('ended',0); assert.equal(requests,1,'end shares in-flight refill');
 resolveFetch({json:()=>Promise.resolve({success:true,data:{tracks:[songs[1],songs[2],{id:4,title:'track 4',src:'https://example.org/4.mp3'}]}})});
 await flush(); await flush(); assert.equal(r.nowId,2,'playback advances when shared refill completes'); assert.equal(r.queue.length,4);
 dom.window.close();
}
{
 const {dom,radio:r}=await setup();
 r.play(); await flush();
 r.graph={ctx:{close(){}},nodes:[]};
 const before=r.decks[0]; r.play({id:7,title:'cdn',src:'https://cdn.example.net/a.mp3'}); await flush();
 assert.equal(r.graph,null); assert.equal(r.forcedPlain,true); assert.notEqual(r.decks[0],before); assert.equal(r.nowId,7,'cross-origin playback falls back to plain audio');
 before.dispatchEvent(new dom.window.Event('error')); assert.equal(r.bad['7'],undefined,'detached deck errors cannot corrupt current playback');
 dom.window.close();
}
{
 const {w,dom,radio:r}=await setup(markup()+markup()); const other=w.TaraRadioEngine.instances[1];
 r.play(); await flush(); r.graph={ctx:{close(){}},nodes:[]};
 r.play({id:12,title:'cdn',src:'https://cdn.example.net/a.mp3'}); await flush();
 other.decks[0].dispatchEvent(new w.Event('play'));
 assert.equal(r.state,'playing','resetting one radio preserves the other radio deck registry');
 r.pause(); r.current().dispatchEvent(new w.Event('playing'));
 assert(!r.root.classList.contains('is-playing'),'late playing event does not override pause');
 dom.window.close();
}
console.log('radio engine queue, crossfade, pause, source race and CDN fallback: passed');
