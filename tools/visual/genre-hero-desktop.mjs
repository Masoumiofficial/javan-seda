/**
 * اسکرین‌شاتِ نهاییِ هیرو — @napi-rs/canvas
 * چیدمان دقیقاً از CSS قالب، متن و کاورها از رندرِ واقعیِ وردپرس.
 *
 * پیش‌نیاز:  node tests/wp/render-hero.mjs
 * (HTMLِ واقعیِ صفحهٔ ژانر را در /tmp/wpcore/staging/hero-render.html می‌نویسد)
 * اجرا:      node tools/visual/genre-hero-desktop.mjs
 */
import fs from 'node:fs';
import { createCanvas, GlobalFonts, loadImage } from '@napi-rs/canvas';

for (const w of [400, 500, 700, 800]) GlobalFonts.registerFromPath(`/tmp/vazir${w}.ttf`, `Vazir${w}`);
const F = (w) => `Vazir${w}`;
/* فقط خروجیِ واقعیِ وردپرس — نه HTMLِ پیش‌نمایش که CSS تزریق‌شده دارد
   و پارسرِ متنی را آلوده می‌کند. */
const html = fs.readFileSync('/tmp/wpcore/staging/hero-render.html', 'utf8');

const clean = (s) => (s || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
const title = clean((html.match(/<h1[^>]*ty-taxhero__title[^>]*>([\s\S]*?)<\/h1>/) || [])[1]) || 'پاپ';
const eyebrow = clean((html.match(/<span class="ty-taxhero__eyebrow">([\s\S]*?)<\/span>/) || [])[1]) || 'ژانر';
const desc = clean((html.match(/<div class="ty-taxhero__desc">([\s\S]*?)<\/div>/) || [])[1]);
const stats = [...html.matchAll(/<li>\s*<strong>([^<]+)<\/strong>\s*<span>([^<]+)<\/span>\s*<\/li>/g)].map(m => [m[1].trim(), m[2].trim()]);
const actions = [...html.matchAll(/ty-taxhero__(?:play|ghost)"[^>]*>([\s\S]{0,260}?)<\/(?:button|a)>/g)]
  .map(m => clean(m[1])).filter(Boolean);
const crumbs = (() => {
  const seg = (html.match(/<nav class="ty-taxhero__crumbs"[\s\S]*?<\/nav>/) || [''])[0];
  const parts = [...seg.matchAll(/<(?:a|span)\b[^>]*>([^<]*?)<\/(?:a|span)>/g)]
    .map(m => m[1].trim()).filter(t => t && t !== '/');
  return parts.join('  /  ');
})();
/* classe واقعی چیپ‌ها ty-taxchip است (بررسی‌شده در خروجیِ رندر). */
const chips = [...html.matchAll(/<a[^>]*class="[^"]*\bty-taxchip\b[^"]*"[^>]*>([\s\S]*?)<\/a>/g)]
  .map(m => m[1].replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim())
  .filter(t => t && !/^[()\d\s]+$/.test(t));
const coverFiles = [...html.matchAll(/ty-taxhero__collage[\s\S]{0,1200}?<\/span>/g)]
  .flatMap(m => [...m[0].matchAll(/src="([^"]+)"/g)].map(x => x[1])) .slice(0, 3)
  .map(u => u.replace(/^https?:\/\/localhost/, '/tmp/wpcore/staging'));

const W = 1568, pad = Math.min(Math.max(20, 0.03 * W), 34), inner = W - 2 * pad;
const coll = Math.min(Math.max(180, 0.20 * W), 232);
const gap = Math.min(Math.max(18, 0.03 * W), 40);
const textW = inner - coll - gap;
const hasChips = chips.length > 0;
const H = hasChips ? 620 : 540;
const c = createCanvas(W, H); const x = c.getContext('2d');
x.direction = 'rtl';

function rr(ctx, rx, ry, w, h, r) {
  ctx.beginPath();
  ctx.moveTo(rx + r, ry); ctx.lineTo(rx + w - r, ry); ctx.quadraticCurveTo(rx + w, ry, rx + w, ry + r);
  ctx.lineTo(rx + w, ry + h - r); ctx.quadraticCurveTo(rx + w, ry + h, rx + w - r, ry + h);
  ctx.lineTo(rx + r, ry + h); ctx.quadraticCurveTo(rx, ry + h, rx, ry + h - r);
  ctx.lineTo(rx, ry + r); ctx.quadraticCurveTo(rx, ry, rx + r, ry); ctx.closePath();
}
x.fillStyle = '#f1f5f9'; x.fillRect(0, 0, W, H);
x.fillStyle = '#fff'; rr(x, pad, 42, W - 2 * pad, H - 80, 26); x.fill();
x.strokeStyle = '#e5e7eb'; x.lineWidth = 1.5; x.stroke();

const R = W - pad - 34, L = R - textW;
let y = 92;
x.textAlign = 'right'; x.font = `13px ${F(400)}`; x.fillStyle = '#94a3b8';
x.fillText(crumbs, R, y);
y += 26;
x.font = `13px ${F(500)}`;
const ew = x.measureText(eyebrow).width + 34;
x.fillStyle = '#f5f3ff'; rr(x, R - ew, y - 21, ew, 30, 15); x.fill();
x.strokeStyle = '#ddd6fe'; x.lineWidth = 1; x.stroke();
x.fillStyle = '#6d28d9'; x.fillText(eyebrow, R - 17, y);
y += 70;
x.textAlign = 'right'; x.font = `62px ${F(800)}`; x.fillStyle = '#0f172a';
x.fillText(title, R, y);
y += 32;
if (desc) {
  x.font = `17px ${F(400)}`; x.fillStyle = '#64748b';
  x.fillText(desc.length > 80 ? desc.slice(0, 78) + '…' : desc, R, y);
  y += 40;
} else { y += 0; }
const n = stats.length || 4, bw = (textW - (n - 1) * 12) / n;
let bx = R;
for (let i = 0; i < n; i++) {
  const [num, lab] = stats[i] || ['—', ''];
  bx -= bw;
  x.fillStyle = '#fafafa'; rr(x, bx, y, bw, 78, 18); x.fill();
  x.strokeStyle = '#eef2f7'; x.lineWidth = 1.2; x.stroke();
  x.textAlign = 'center'; x.font = `26px ${F(700)}`; x.fillStyle = '#0f172a';
  x.fillText(num, bx + bw / 2, y + 38);
  x.font = `13px ${F(400)}`; x.fillStyle = '#94a3b8';
  x.fillText(lab, bx + bw / 2, y + 62);
  bx -= 12;
}
y += 110; bx = R;
const pal = [['#6d28d9', '#fff'], ['#fff', '#6d28d9'], ['#fff', '#db2777']];
for (let i = 0; i < Math.min(actions.length, 3); i++) {
  x.font = `15px ${F(700)}`;
  const w = x.measureText(actions[i]).width + 46;
  bx -= w;
  const [bg, fg] = pal[i];
  x.fillStyle = bg; rr(x, bx, y, w, 46, 23); x.fill();
  if (bg === '#fff') { x.strokeStyle = fg; x.lineWidth = 1.6; x.stroke(); }
  x.textAlign = 'center'; x.font = `15px ${F(700)}`; x.fillStyle = fg;
  x.fillText(actions[i], bx + w / 2, y + 30);
  bx -= 12;
}
const chipsY = hasChips ? y + 92 : y + 56;
x.strokeStyle = '#f1f5f9'; x.lineWidth = 1;
if (hasChips) { x.beginPath(); x.moveTo(L, chipsY - 28); x.lineTo(R, chipsY - 28); x.stroke(); }
bx = R;
for (const ch of (hasChips ? chips.slice(0, 6) : [])) {
  x.font = `14px ${F(500)}`;
  const w = x.measureText(ch).width + 38;
  bx -= w;
  x.fillStyle = '#f5f3ff'; rr(x, bx, chipsY - 19, w, 38, 19); x.fill();
  x.strokeStyle = '#ede9fe'; x.lineWidth = 1; x.stroke();
  x.textAlign = 'center'; x.fillStyle = '#6d28d9'; x.fillText(ch, bx + w / 2, chipsY + 6);
  bx -= 10;
}

/* کلاژ با کاورهای واقعی، هم‌ارتفاع با ستون متن */
/* کلاژ مربع است و در ستون وسط‌چین می‌شود (هم‌خوان با CSS: height ثابت + align-self:center) */
const cH = Math.max(180, Math.min(0.20 * W, 232));
const bodyTop = 92, bodyBottom = chipsY + (hasChips ? 20 : -8);
const cL = pad + 34, cT = Math.round(bodyTop + Math.max(0, (bodyBottom - bodyTop - cH) / 2));
x.fillStyle = '#eef2ff'; rr(x, cL - 10, cT - 10, coll + 20, cH + 20, 26); x.fill();
const cw = (coll - 8) / 2, chh = (cH - 8) / 2;
const NC = Math.min(coverFiles.length, 3);
/* هم‌خوان با CSS: ۱ کاور = کل قاب، ۲ کاور = دو ردیفِ تمام‌عرض، ۳ کاور = ستون بلند + دو خانه. */
const boxes = NC === 1 ? [[cL, cT, coll, cH]]
  : NC === 2 ? [[cL, cT, coll, chh], [cL, cT + chh + 8, coll, chh]]
  : [[cL, cT, cw, cH], [cL + cw + 8, cT, cw, chh], [cL + cw + 8, cT + chh + 8, cw, chh]];
const grads = ['#c4b5fd', '#f9a8d4', '#93c5fd'];
boxes.forEach(([bxx, byy, bw2, bh2], i) => {
  x.save(); rr(x, bxx, byy, bw2, bh2, 16); x.clip();
  x.fillStyle = grads[i]; x.fillRect(bxx, byy, bw2, bh2);
  x.restore();
});
for (let i = 0; i < Math.min(coverFiles.length, 3); i++) {
  try {
    const im = await loadImage(coverFiles[i]);
    const [bxx, byy, bw2, bh2] = boxes[i];
    x.save(); rr(x, bxx, byy, bw2, bh2, 16); x.clip();
    const s = Math.max(bw2 / im.width, bh2 / im.height);
    const dw = im.width * s, dh = im.height * s;
    x.drawImage(im, bxx + (bw2 - dw) / 2, byy + (bh2 - dh) / 2, dw, dh);
    x.restore();
    x.save(); rr(x, bxx, byy, bw2, bh2, 16); x.strokeStyle = 'rgba(255,255,255,.92)';
    x.lineWidth = 3; x.stroke(); x.restore();
  } catch (e) { console.log('cover fail', i, e.message.slice(0, 60)); }
}
x.strokeStyle = '#10b981'; x.lineWidth = 2.5;
x.beginPath(); x.moveTo(cL + coll + gap / 2, cT - 6); x.lineTo(cL + coll + gap / 2, cT + cH + 6); x.stroke();
x.textAlign = 'center'; x.font = `12px ${F(700)}`; x.fillStyle = '#10b981';
x.fillText(`${gap}px`, cL + coll + gap / 2, cT + cH + 30);

x.textAlign = 'left'; x.font = `15px ${F(800)}`; x.fillStyle = '#0f172a'; x.fillText('TY', pad, 24);
x.font = `13px ${F(400)}`; x.fillStyle = '#64748b';
x.fillText('genre hero — RTL — 1568px viewport', pad + 30, 24);
x.textAlign = 'right'; x.fillStyle = '#10b981'; x.font = `13px ${F(700)}`;
x.fillText(`text ${Math.round(textW)}px · collage ${Math.round(coll)}px × ${Math.round(cH)}px`, W - pad, 24);

/* برشِ نهایی: ارتفاعِ کارت را از پایین‌ترین عنصرِ کشیده‌شده می‌گیریم تا
   فضای خالیِ ساختگیِ بوم، تصویر را گمراه‌کننده نکند. */
const bottom = Math.ceil(Math.max(cT + cH, y + 46, chipsY + (hasChips ? 24 : 0))) + 40;
const out = createCanvas(W, Math.min(bottom, H));
out.getContext('2d').drawImage(c, 0, 0);
fs.writeFileSync('/tmp/hero-final.png', out.toBuffer('image/png'));
console.log('title:', title, '| stats:', stats.length, '| actions:', actions.length, '| chips:', chips.length, '| covers:', coverFiles.length);
console.log('saved', W + 'x' + out.height, '| chips:', chips.length);
