/**
 * render-hero.mjs — خروجیِ HTMLِ واقعیِ صفحهٔ ژانر را می‌سازد.
 *
 * چرا؟ برای بازبینیِ بصریِ سربرگِ ژانر (v3.17.2) به HTMLِ رندرشدهٔ
 * خودِ وردپرس نیاز داریم، نه مارک‌آپِ دستی. سپس shot-final.mjs /
 * shot-mobile.mjs همان HTML را با فونتِ فارسی به تصویر تبدیل می‌کنند.
 *
 * اجرا:  node tests/wp/render-hero.mjs [genre-slug]
 * خروجی: /tmp/wpcore/staging/hero-render.html
 */
import fs from 'node:fs';
import { site } from '../../wpsite.mjs';

const slug = process.argv[2] || 'پاپ';
const s = await site(604);

const r = await s.run(`<?php
$term = get_term_by('slug', ${JSON.stringify(slug)}, 'tara_genre');
if ( ! $term ) {
	$all = get_terms( array( 'taxonomy' => 'tara_genre', 'hide_empty' => false, 'number' => 1 ) );
	$term = ( $all && ! is_wp_error( $all ) ) ? $all[0] : null;
}
if ( ! $term ) { echo "NO_TERM\\n"; return; }
$GLOBALS['wp_query'] = new WP_Query( array(
	'post_type'      => 'post',
	'posts_per_page' => 12,
	'tax_query'      => array( array( 'taxonomy' => 'tara_genre', 'field' => 'term_id', 'terms' => array( $term->term_id ) ) ),
) );
$GLOBALS['wp_query']->is_tax            = true;
$GLOBALS['wp_query']->is_archive        = true;
$GLOBALS['wp_query']->queried_object    = $term;
$GLOBALS['wp_query']->queried_object_id = $term->term_id;

ob_start();
include get_template_directory() . '/taxonomy-tara_genre.php';
$html = ob_get_clean();
file_put_contents( '/tmp/wpcore/staging/hero-render.html', $html );

echo 'term=' . $term->name . ' len=' . strlen( $html )
   . ' hero=' . ( false !== strpos( $html, 'ty-taxhero' ) ? 'Y' : 'N' )
   . ' collage=' . ( false !== strpos( $html, 'ty-taxhero__collage' ) ? 'Y' : 'N' ) . "\\n";
`, { fresh: true });

console.log(r.text, (r.errors || '').toString().slice(0, 300));
process.exit(0);
