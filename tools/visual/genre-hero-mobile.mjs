/** نمای موبایل (۳۹۰×۸۴۴) — چیدمانِ تک‌ستونی با نوارِ افقیِ کاورها. *
 * پیش‌نیاز:  node tests/wp/render-hero.mjs
 * (HTMLِ واقعیِ صفحهٔ ژانر را در /tmp/wpcore/staging/hero-render.html می‌نویسد)
 * اجرا:      node tools/visual/genre-hero-mobile.mjs
 */
import fs from 'node:fs';
import { createCanvas, GlobalFonts, loadImage } from '@napi-rs/canvas';
for (const w of [400,500,700,800]) GlobalFonts.registerFromPath(`/tmp/vazir${w}.ttf`, `Vazir${w}`);
const F = w => `Vazir${w}`;
const html = fs.readFileSync('/tmp/wpcore/staging/hero-render.html','utf8');
const clean = s => (s||'').replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim();
const title = clean((html.match(/<h1[^>]*ty-taxhero__title[^>]*>([\s\S]*?)<\/h1>/)||[])[1]) || 'پاپ';
const eyebrow = clean((html.match(/<span class="ty-taxhero__eyebrow">([\s\S]*?)<\/span>/)||[])[1]) || 'ژانر';
const stats = [...html.matchAll(/<li>\s*<strong>([^<]+)<\/strong>\s*<span>([^<]+)<\/span>\s*<\/li>/g)].map(m=>[m[1].trim(),m[2].trim()]);
const actions = [...html.matchAll(/ty-taxhero__(?:play|ghost)"[^>]*>([\s\S]{0,260}?)<\/(?:button|a)>/g)].map(m=>clean(m[1])).filter(Boolean);
const crumbs = (()=>{const seg=(html.match(/<nav class="ty-taxhero__crumbs"[\s\S]*?<\/nav>/)||[''])[0];
  return [...seg.matchAll(/<(?:a|span)\b[^>]*>([^<]*?)<\/(?:a|span)>/g)].map(m=>m[1].trim()).filter(t=>t&&t!=='/').join(' / ');})();
const chips = [...html.matchAll(/<a[^>]*class="[^"]*\bty-taxchip\b[^"]*"[^>]*>([\s\S]*?)<\/a>/g)]
  .map(m=>m[1].replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim()).filter(t=>t&&!/^[()\d\s]+$/.test(t));
const coverFiles = [...html.matchAll(/ty-taxhero__collage[\s\S]{0,1200}?<\/span>/g)]
  .flatMap(m=>[...m[0].matchAll(/src="([^"]+)"/g)].map(x=>x[1])).slice(0,3)
  .map(u=>u.replace(/^https?:\/\/localhost/,'/tmp/wpcore/staging'));

const W=390, H=700, pad=14;
const c=createCanvas(W,H); const x=c.getContext('2d'); x.direction='rtl';
function rr(ctx,rx,ry,w,h,r){ctx.beginPath();ctx.moveTo(rx+r,ry);ctx.lineTo(rx+w-r,ry);ctx.quadraticCurveTo(rx+w,ry,rx+w,ry+r);ctx.lineTo(rx+w,ry+h-r);ctx.quadraticCurveTo(rx+w,ry+h,rx+w-r,ry+h);ctx.lineTo(rx+r,ry+h);ctx.quadraticCurveTo(rx,ry+h,rx,ry+h-r);ctx.lineTo(rx,ry+r);ctx.quadraticCurveTo(rx,ry,rx+r,ry);ctx.closePath();}
x.fillStyle='#f1f5f9'; x.fillRect(0,0,W,H);
x.fillStyle='#fff'; rr(x,pad,14,W-2*pad,660,20); x.fill();
x.strokeStyle='#e5e7eb'; x.lineWidth=1.4; x.stroke();
const R=W-pad-14, L=pad+14;
let y=48;
/* نوار افقی کاورها */
let cx=L;
for (let i=0;i<Math.min(coverFiles.length,3);i++){
  try{ const im=await loadImage(coverFiles[i]);
    x.save(); rr(x,cx,y,62,62,13); x.clip();
    const s=Math.max(62/im.width,62/im.height);
    x.drawImage(im,cx+(62-im.width*s)/2,y+(62-im.height*s)/2,im.width*s,im.height*s);
    x.restore(); x.save(); rr(x,cx,y,62,62,13); x.strokeStyle='#fff'; x.lineWidth=2; x.stroke(); x.restore();
  }catch(e){}
  cx+=69;
}
y+=86;
x.textAlign='right'; x.font=`11px ${F(400)}`; x.fillStyle='#94a3b8'; x.fillText(crumbs,R,y);
y+=22;
x.font=`11px ${F(500)}`; const ew=x.measureText(eyebrow).width+26;
x.fillStyle='#f5f3ff'; rr(x,R-ew,y-15,ew,24,12); x.fill(); x.fillStyle='#6d28d9'; x.fillText(eyebrow,R-13,y);
y+=38;
x.font=`30px ${F(800)}`; x.fillStyle='#0f172a'; x.fillText(title,R,y);
y+=26;
const n=stats.length||4, bw=(R-L-(n-1)*8)/n;
let bx=R;
for(let i=0;i<n;i++){const[nn,ll]=stats[i]||['—',''];
  bx-=bw; x.fillStyle='#fafafa'; rr(x,bx,y,bw,58,12); x.fill(); x.strokeStyle='#eef2f7'; x.lineWidth=1; x.stroke();
  x.textAlign='center'; x.font=`17px ${F(700)}`; x.fillStyle='#0f172a'; x.fillText(nn,bx+bw/2,y+27);
  x.font=`10px ${F(400)}`; x.fillStyle='#94a3b8'; x.fillText(ll,bx+bw/2,y+45); bx-=8;}
y+=78;
x.textAlign='center'; x.font=`13px ${F(700)}`;
const pw=R-L; x.fillStyle='#6d28d9'; rr(x,L,y,pw,40,20); x.fill(); x.fillStyle='#fff';
x.fillText(actions[0]||'پخش میکس این ژانر',L+pw/2,y+26);
y+=48;
const ghosts=actions.slice(1,3), gw=(pw-7)/2; bx=R;
x.font=`11px ${F(700)}`;
for(let i=0;i<ghosts.length;i++){ bx-=gw;
  x.fillStyle='#fff'; rr(x,bx,y,gw,34,17); x.fill();
  x.strokeStyle=i===0?'#6d28d9':'#db2777'; x.lineWidth=1.3; x.stroke();
  x.fillStyle=i===0?'#6d28d9':'#db2777'; x.textAlign='center'; x.fillText(ghosts[i],bx+gw/2,y+22); bx-=7;}
y+=60;
x.strokeStyle='#e5e7eb'; x.setLineDash([4,3]); x.beginPath(); x.moveTo(L,y-18); x.lineTo(R,y-18); x.stroke(); x.setLineDash([]);
bx=R; x.textAlign='center'; x.font=`11px ${F(500)}`;
for(const ch of chips.slice(0,4)){ const w=x.measureText(ch).width+26; bx-=w;
  x.fillStyle='#f5f3ff'; rr(x,bx,y,w,28,14); x.fill(); x.strokeStyle='#ede9fe'; x.lineWidth=1; x.stroke();
  x.fillStyle='#6d28d9'; x.fillText(ch,bx+w/2,y+18); bx-=6;}
x.textAlign='left'; x.font=`11px ${F(700)}`; x.fillStyle='#10b981';
x.fillText('mobile 390px — 1 column', pad+14, H-12);
fs.writeFileSync('/tmp/hero-mobile.png', c.toBuffer('image/png'));
console.log('saved mobile', W+'x'+H, '| covers', coverFiles.length);
