/** Structural regression checks for the artist biography display. */
import fs from 'node:fs';
import assert from 'node:assert/strict';
const src = fs.readFileSync('taranehyar/taxonomy-artist.php', 'utf8');
assert(src.includes('class="ty-artist__about"'), 'hero biography retained');
assert(src.includes("tara_artist_bio_link( $taranehyar_term )"), 'full biography link retained');
assert(src.includes('wp_trim_words( $taranehyar_bio_description, 60'), 'description-only artist has a short fallback');
assert(!src.includes('ty-artist__bio-news'), 'repeated biography and latest block removed');
assert(!src.includes('$taranehyar_full_bio'), 'no dangling variable or duplicate lower description');
assert(src.includes('هنرمندان مشابه'), 'related artists retained');
assert(src.includes('tara_player('), 'existing artist player retained');
const playerBlock=src.slice(src.indexOf('<!-- ۲) رادیو'),src.indexOf('<!-- ۲٫۵)'));
assert(playerBlock.includes("tara_radio_option( 'artist_player', 'on' )"), 'artist player switch gates actual player');
assert(playerBlock.includes("tara_radio_option( 'artist', 'on' )"), 'artist DJ has separate switch');
const opts=fs.readFileSync('taranehyar/inc/theme-options.php','utf8');
for (const id of ['radio_home','indpl','radio_artist','radio_artist_player']) assert(opts.includes("'id'=>'"+id+"'"), 'manager option '+id);

console.log('artist biography and player structure: passed');
