/** Regression checks for page-specific responsive CSS and artist badges. */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { JSDOM } from 'jsdom';
const css = fs.readFileSync('taranehyar/css/tara-radio.css', 'utf8');
const doc = new JSDOM('<!doctype html><style></style>').window.document;
doc.querySelector('style').textContent = css;
const rules = Array.from(doc.styleSheets[0].cssRules);
const desktopIndex = rules.findIndex(r => r.selectorText === '.ty-radio--page .ty-radio__deck');
assert(desktopIndex >= 0, 'page desktop rule exists');
const responsive = rules.slice(desktopIndex + 1).find(r => r.conditionText === '(max-width: 900px)' && Array.from(r.cssRules).some(x => x.selectorText === '.ty-radio--page .ty-radio__deck' && x.style.gridTemplateColumns === 'minmax(0, 1fr)'));
assert(responsive, 'page rule overrides desktop at 900px and below');
assert(rules.some(r => r.selectorText === '.ty-radio--compact .ty-radio__deck'), 'home compact rule remains');
const artist = fs.readFileSync('taranehyar/taxonomy-artist.php', 'utf8');
assert(artist.includes('ty-artist__banner-avatar'), 'profile photo remains visible with banner');
assert(artist.includes('ty-artist__portrait-verified'), 'portrait badge exists');
assert(artist.includes('ty-artist__name') && artist.includes("tara_artist_verified_badge( $taranehyar_term"), 'name badge exists');
console.log('radio responsive CSS and artist badge structure: passed');
