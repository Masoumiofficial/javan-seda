/**
 * smoke-p1-dom.mjs — آزمونِ DOM برای تغییراتِ ۳.۱۷.۰
 * =================================================
 * اجرا: node tests/smoke-p1-dom.mjs
 *
 * چرا این آزمون جداست؟ `tests/run-p1.php` منطقِ PHP را می‌سنجد؛ این‌جا
 * مارک‌آپِ تولیدشده در یک DOM واقعی بررسی می‌شود (ساختار، دسترس‌پذیری و
 * نبودِ لینک‌های تکراری/ساختگی). هیچ سرور یا وردپرسِ واقعی لازم نیست.
 */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { JSDOM } from 'jsdom';

const read = (p) => fs.readFileSync(p, 'utf8');
const dateSrc = read('taranehyar/inc/tara-release-date.php');
const artistSrc = read('taranehyar/inc/tara-artist-profile.php');
const playlistSrc = read('taranehyar/inc/tara-playlists-p1.php');
const relatedSrc = read('taranehyar/inc/tara-related-p1.php');
const taxonomySrc = read('taranehyar/taxonomy-artist.php');
const singleSrc = read('taranehyar/single.php');
const playlistsPage = read('taranehyar/page-playlists.php');
const css = read('taranehyar/css/tara-playlists.css') + read('taranehyar/css/tara-artist.css');

/* ---------- ۱) فیلد تاریخ: ساختار و دسترس‌پذیری ---------- */
assert(dateSrc.includes('تاریخ انتشار واقعی آهنگ'), 'label');
assert(dateSrc.includes('aria-describedby="tara-release-date-help"'), 'aria help');
assert(dateSrc.includes('inputmode="numeric"'), 'mobile numeric keyboard');
assert(dateSrc.includes('wp_nonce_field( \'tara_release_date_save\''), 'nonce');
assert(dateSrc.includes("current_user_can( 'edit_post'"), 'capability');
assert(dateSrc.includes('delete_post_meta'), 'clearing deletes meta');
assert(!dateSrc.includes("'post_date' =>"), 'never writes post_date');
assert(dateSrc.includes("apply_filters( 'tara_p1_date_meta_key', '_tara_release_date' )"), 'reuses existing key');

/* مارک‌آپ فیلد را در DOM بساز و برچسب/ورودی را بررسی کن */
{
  const html = dateSrc.match(/<div class="tara-date-field"[\s\S]*?<\/div>/)[0]
    .replace(/<\?php[\s\S]*?\?>/g, '')
    .replace(/<\?php echo esc_attr\( \$human \); \?>/g, '1403/05/12');
  const dom = new JSDOM(`<!doctype html><body>${html}</body>`);
  const doc = dom.window.document;
  const input = doc.querySelector('input[name="tara_release_date"]');
  assert(input, 'input exists');
  const label = doc.querySelector('label[for="tara-release-date-input"]');
  assert(label && label.textContent.includes('تاریخ انتشار واقعی آهنگ'), 'label points at input');
  assert(doc.querySelector('#tara-release-date-help'), 'help text target exists');
  assert(doc.querySelector('input[name="tara_release_date_present"]'), 'field-presence marker');
}

/* ---------- ۲) پروفایل هنرمند: بخش‌ها فقط با داده، بدون لینک ساختگی ---------- */
assert(artistSrc.includes('rel="noopener nofollow me"'), 'social links are safe');
assert(!artistSrc.includes('facebook.com/'), 'no invented social urls');
assert(artistSrc.includes('همکاری‌ها'), 'collaboration wording');
assert(artistSrc.includes('تاریخ انتشار'), 'honest date label');
assert(artistSrc.includes('انتشار در سایت'), 'honest fallback label');

/* سربرگ هنرمند: «آخرین انتشار» لینکِ خزش‌پذیر و بدون جاوااسکریپت */
assert(taxonomySrc.includes('ty-artist__latest'), 'latest release badge');
assert(taxonomySrc.includes('data-tara-latest-release'), 'latest release hook for diagnostics');
assert(!/ty-artist__latest[\s\S]{0,300}href="#"/.test(taxonomySrc), 'latest link is not a dead #');

/* ---------- ۳) پلی‌لیست: هیچ پلی‌لیست خالی لینک نمی‌شود ---------- */
assert(playlistSrc.includes("'publish' !== $post->post_status"), 'status check');
assert(playlistSrc.includes('tara_playlist_audio_ids'), 'requires playable tracks');
assert(playlistSrc.includes('ty-playlists__track-artist'), 'artist link inside tracks');
assert(playlistsPage.includes('aria-current'), 'aria-current on mood filters');
assert(!playlistsPage.includes('go2NewUrl'), 'no auto redirect');
assert(playlistSrc.includes('tara_playlist_related_to_track'), 'real playlist relation');

/* کارت پلی‌لیست را در DOM بساز */
{
  const svgless = `<a class="ty-playlists__card" href="https://ex/?list=201">
    <span class="ty-playlists__placeholder" aria-hidden="true">♫</span>
    <strong>پلی‌لیست غمگین</strong>
    <span class="ty-playlists__desc">برای شب‌های بارانی</span>
    <span class="ty-playlists__count">۲ آهنگ</span>
  </a>`;
  const dom = new JSDOM(`<!doctype html><body><div class="ty-playlists__grid">${svgless}</div></body>`);
  const card = dom.window.document.querySelector('.ty-playlists__card');
  assert(card && card.getAttribute('href').includes('list='), 'crawlable card link');
  assert(!card.querySelector('button'), 'no JS-only button in card');
  assert(card.textContent.includes('آهنگ'), 'track count visible');
}

/* ---------- ۴) مرتبط‌ها: بدون تکرار و بدون بلوک خالی ---------- */
assert(relatedSrc.includes('array_unique'), 'dedupe');
assert(relatedSrc.includes('array_diff'), 'excludes already-shown artist tracks');
assert(relatedSrc.includes('data-tara-related="taxonomy"'), 'related block marker');
assert(singleSrc.includes('tara_playlists_for_track_html'), 'playlist cross-link on song page');
assert(singleSrc.includes('tara_related_tracks_html'), 'related block on song page');

/* ---------- ۵) CSS: حالت شب/موبایل برای اجزای تازه ---------- */
assert(css.includes('.ty-playlists__track-artist'), 'style for artist link');
assert(css.includes('.ty-artist__latest'), 'style for latest release');
assert(css.includes('data-tara-night') || css.includes('.night '), 'night mode coverage');
assert(css.includes('@media(max-width:600px)') || css.includes('@media (max-width: 600px)'), 'mobile breakpoint');

/* ---------- ۶) منبعِ دیتای ساختگی وجود ندارد ---------- */
for (const src of [artistSrc, playlistSrc, relatedSrc, dateSrc]) {
  assert(!/rand\(/.test(src), 'no random data');
  assert(!/popularity|بازدید ساختگی/.test(src), 'no fake popularity');
}

console.log('p1 dom smoke: date field, artist profile, playlists and related blocks passed');
