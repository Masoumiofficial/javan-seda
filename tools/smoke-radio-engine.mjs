/** Radio engine regression tests with controlled Audio and AJAX doubles, not a real browser/audio device. */
import { JSDOM } from 'jsdom';
import fs from 'node:fs';
import assert from 'node:assert/strict';
const code = fs.readFileSync('taranehyar/js/tara-radio.js', 'utf8');
const songs = [1,2,3].map(id => ({id,title:'track '+id,src:`https://example.org/${id}.mp3`,link:`https://example.org/song/${id}`}));
const markup = (tracks=songs) => `<section data-tara-radio data-radio-mode="mixed"><script class="ty-radio__data" type="application/json">${JSON.stringify({tracks})}</script><div data-radio-audio></div><div data-radio-queue></div><span data-radio-state></span><span data-radio-toast></span><button data-radio-toggle></button></section>`;
async function setup(html=markup(), beforeBoot=()=>{}) {
 const dom = new JSDOM(`<!doctype html><body>${html}</body>`, {url:'https://example.org/',runScripts:'outside-only'});
 const w=dom.window; let rafId=0;
 w.requestAnimationFrame=()=>++rafId; w.cancelAnimationFrame=()=>{};
 w.TaraRadio={api:'/ajax',nonce:'n',config:{xfade:4}};
 w.Audio=function(){const el=w.document.createElement('audio');el._paused=true;el._reject=false;el.pause=function(){this._paused=true;};el.load=function(){};el.play=function(){if(this._reject){this._reject=false;return Promise.reject(new Error('blocked'));} this._paused=false;return Promise.resolve();};return el;};
 w.fetch=()=>Promise.resolve({json:()=>Promise.resolve({success:true,data:{tracks:[]}})});
 beforeBoot(w); w.eval(code); w.document.dispatchEvent(new w.Event('DOMContentLoaded')); return {w,dom,radio:w.TaraRadioEngine.instances[0]};
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
{
 const html = markup().replace('<button data-radio-toggle></button>', `<button data-radio-toggle></button><details class="ty-radio__adv"><summary>تنظیمات دی‌جی</summary><div class="ty-radio__advbody"><label><span data-radio-xfade-val></span><input type="range" min="0" max="16" value="6" data-radio-xfade></label><div data-radio-power-group><button data-radio-power="calm"></button><button data-radio-power="warm"></button><button data-radio-power="club"></button></div></div></details>`);
 const {w,dom,radio:r}=await setup(html);
 const details=r.root.querySelector('details'); assert.equal(details.parentNode,r.root); details.open=true; assert(details.open);
 const slider=r.root.querySelector('[data-radio-xfade]'); slider.value='9'; slider.dispatchEvent(new w.Event('input',{bubbles:true}));
 assert.equal(r.xfade,9); assert.equal(r.root.querySelector('[data-radio-xfade-val]').textContent,'۹ ثانیه');
 const power=r.root.querySelector('[data-radio-power=club]'); power.click();
 assert.equal(r.xfade,12); assert.equal(power.getAttribute('aria-pressed'),'true');
 dom.window.close();
}
{
 const artist=markup().replace('data-radio-mode="mixed"','data-radio-context="artist" data-radio-tax="artist" data-radio-term="31" data-radio-mode="new"');
 const foreign={id:88,title:'other artist',src:'https://example.org/88.mp3'};
 const {w,dom,radio:r}=await setup(artist,w=>w.localStorage.setItem('tara:radio:mine',JSON.stringify([foreign])));
 assert.deepEqual(Array.from(r.queue,t=>t.id),[1,2,3],'artist radio ignores global personal list');
 assert.equal(r.addToQueue(foreign),false,'global add cannot inject a foreign track');
 let requested=''; w.fetch=url=>{requested=url; return Promise.resolve({json:()=>Promise.resolve({success:true,data:{tracks:[songs[1],songs[0]]}})});};
 r.history=[songs[0]]; r.setMode('views'); await flush(); await flush();
 assert(requested.includes('tax=artist') && requested.includes('term=31') && requested.includes('mode=views'));
 assert(!requested.includes('exclude=1'),'mode change may include previously heard tracks');
 assert.deepEqual(Array.from(r.queue,t=>t.id),[2,1]);
 dom.window.close();
}
{
 const html=markup([]).replace('data-radio-mode="mixed"','data-radio-context="artist" data-radio-tax="artist" data-radio-term="32" data-radio-mode="new"');
 const {dom,radio:r}=await setup(html); assert.equal(r.queue.length,0); assert.equal(r.addToQueue(songs[0]),false); dom.window.close();
}
{
 const html=markup().replace('data-radio-mode="mixed"','data-radio-context="playlist" data-radio-mode="mixed"');
 const foreign={id:88,title:'foreign',src:'https://example.org/88.mp3'};
 const {w,dom,radio:r}=await setup(html,w=>w.localStorage.setItem('tara:radio:mine',JSON.stringify([foreign])));
 let requests=0; w.fetch=()=>{requests++; throw new Error('fixed radio must not fetch');};
 assert.deepEqual(Array.from(r.queue,t=>t.id),[1,2,3]);
 assert.equal(r.addToQueue(foreign),false); r.play(foreign); assert.equal(r.nowId,null);
 r.setMode('views'); assert.deepEqual(Array.from(r.queue,t=>t.id),[1,2,3]);
 r.play(); await flush(); r.cursor=2; r.advance('ended',0); await flush();
 assert.equal(requests,0); assert.deepEqual(Array.from(r.queue,t=>t.id),[1,2,3]); assert.equal(r.nowId,1);
 dom.window.close();
}
{
 const {w,dom,radio:r}=await setup();
 const legacy=w.document.createElement('audio'); legacy._paused=false; legacy.pause=function(){this._paused=true;}; w.document.body.appendChild(legacy);
 r.play(); await flush(); assert.equal(legacy._paused,true,'DJ stops ordinary player');
 legacy.dispatchEvent(new w.Event('play',{bubbles:true})); assert.equal(r.state,'paused','ordinary player stops DJ');
 dom.window.close();
}
{
 const {dom,radio:r}=await setup(); r.play(); await flush();
 assert.equal(r.current().volume,1,'initial playback is audible even if animation frames are suspended');
 dom.window.close();
}
{
 const {dom,radio:r}=await setup(markup(),w=>{w.TaraRadio.config.fx=true; w.AudioContext=function(){this.close=()=>{};this.createGain=()=>{throw Error('unsupported graph');};};});
 r.play(); await flush(); assert.equal(r.nowId,1); assert.equal(r.forcedPlain,true,'graph setup failures fall back to plain audio'); assert.equal(r.state,'playing'); dom.window.close();
}
{
 const {dom,radio:r}=await setup(); r.play(); await flush();
 r.graph={ctx:{close(){}},nodes:[]}; const old=r.current();
 old.dispatchEvent(new dom.window.Event('error')); await flush();
 assert.equal(r.forcedPlain,true); assert.notEqual(r.current(),old); assert.equal(r.nowId,1); assert.equal(r.bad['1'],undefined,'CORS error retries the same track without graph');
 dom.window.close();
}
{
 const {dom,radio:r}=await setup();
 r.current().play=function(){const e=new Error('bad format');e.name='NotSupportedError';return Promise.reject(e);};
 r.play(); await new Promise(resolve=>setTimeout(resolve,400)); await flush();
 assert.equal(r.bad['1'],true); assert.equal(r.nowId,2,'unsupported first track advances instead of pausing'); dom.window.close();
}
{
 const {dom,radio:r}=await setup(); r.play(); await flush(); const incoming=r.decks[1-r.active];
 incoming.play=function(){const e=new Error('mobile restriction');e.name='NotAllowedError';return Promise.reject(e);};
 r.advance('user',4); await flush(); assert.equal(r.nowId,2); assert.equal(r.current(),r.decks[0]); assert.equal(r.state,'playing','second deck restriction falls back to active deck'); dom.window.close();
}
{
 const {dom,radio:r}=await setup(); r.play(); await flush();
 r.graph={ctx:{close(){}},nodes:[]}; r.destroyGraph(); r.pause();
 await new Promise(resolve=>setTimeout(resolve,100));
 assert.equal(r.state,'paused','late graph fallback does not restart after pause'); dom.window.close();
}
{
 const {dom,radio:r}=await setup(); r.play(); await flush();
 r.graph={ctx:{close(){}},nodes:[]}; let accept;
 const incoming=r.decks[1-r.active]; incoming.play=()=>new Promise(resolve=>{accept=resolve;});
 r.advance('user',4); incoming.dispatchEvent(new dom.window.Event('error')); await flush();
 assert.equal(r.forcedPlain,true); assert.equal(r.nowId,2); assert.equal(r.bad['2'],undefined,'incoming graph error retries without CORS');
 accept(); await flush(); assert.equal(r.nowId,2,'stale incoming promise is ignored'); dom.window.close();
}
{
 const {dom,radio:r}=await setup(); r.play(); await flush();
 const incoming=r.decks[1-r.active]; incoming.dispatchEvent(new dom.window.Event('error'));
 assert.equal(r.bad['2'],undefined,'preload failure alone does not permanently blacklist a track');
 dom.window.close();
}
{
 const {dom,radio:r}=await setup(); r.play(); await flush(); const current=r.current();
 Object.defineProperty(current,'ended',{value:true,configurable:true});
 const incoming=r.decks[1-r.active]; incoming.play=()=>new Promise(()=>{});
 r.advance('user',4); incoming.dispatchEvent(new dom.window.Event('error')); await flush();
 assert.equal(r.nowId,3,'when outgoing song has ended, failed incoming deck skips to the next track'); dom.window.close();
}
{
 const {dom,radio:r}=await setup(); r.play(); await flush();
 Object.defineProperty(dom.window.document,'hidden',{value:true,configurable:true});
 r.duckTo(0); assert.equal(r.current().volume,0); r.unduck(); assert.equal(r.current().volume,1,'voice duck is released without animation frames in background'); dom.window.close();
}
{
 const data=[{...songs[0],bpm:120},{...songs[1],bpm:125}];
 const {dom,radio:r}=await setup(markup(data),w=>{w.TaraRadio.config.tempo=true;});
 r.play(); await flush(); r.advance('user',4); await flush();
 assert.equal(r.fade.tempoRate,120/125,'tempo based on real provided BPM');
 assert.equal(r.decks[1-r.active].playbackRate,120/125); r.cancelFade();
 assert.equal(r.decks[1-r.active].playbackRate,1,'cancel resets speed'); dom.window.close();
}
{
 const {dom,radio:r}=await setup(markup([{...songs[0],bpm:80},{...songs[1],bpm:150}]),w=>{w.TaraRadio.config.tempo=true;});
 r.play(); await flush(); r.advance('user',4); await flush(); assert.equal(r.fade.tempoRate,1,'large BPM difference leaves tempo unchanged'); dom.window.close();
}
{
 const {dom,radio:r}=await setup(markup(),w=>{w.TaraRadio.config.tempo=true;});
 r.play(); await flush(); r.advance('user',4); await flush(); assert.equal(r.fade.tempoRate,1,'missing BPM does not invent tempo'); dom.window.close();
}
{
 const files=[1,2,3,4,5].map(i=>'https://example.org/v'+i+'.mp3');
 const {dom,radio:r}=await setup(markup(),w=>{w.TaraRadio.config.station={on:true,files};});
 r.state='playing'; let picked=[]; r.playVoiceFile=src=>picked.push(src); r.paintSay=()=>{}; r.chime=()=>{};
 for(let i=0;i<6;i++) r.speak('station',songs[0],null);
 assert.deepEqual(picked,[...files,files[0]],'five voice files rotate and wrap');
 r.voiceFailed[files[1]]=true; r.speak('station',songs[0],null); assert.equal(picked.at(-1),files[2], 'failed file skipped');
 dom.window.close();
}
{
 const files=['https://example.org/v1.mp3','https://example.org/v2.mp3'];
 const {dom,radio:r}=await setup(markup(),w=>{w.TaraRadio.config.station={on:true,files};w.TaraRadio.config.duck=0;});
 r.state='playing'; r.paintSay=()=>{}; r.chime=()=>{};
 r.speak('station',songs[0],null); assert.equal(r.duckTarget,0,'zero duck value is honored');
 r.voice.onerror(); assert.equal(r.voiceFailed[files[0]],true,'broken file isolated');
 r.speak('station',songs[0],null); assert.equal(r.voice.src,files[1],'later announcement uses remaining file');
 r.pause(); assert.equal(r.duckTarget,1,'pause releases duck'); dom.window.close();
}
console.log('radio engine queue, crossfade, pause, source race and CDN fallback: passed');
