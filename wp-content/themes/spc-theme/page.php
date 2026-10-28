<?php
/**
 * 固定ページ（汎用）：見出し部品（パンくず・h1）→ 本文（.spc-prose）→ 相談ブロック。
 * 専用テンプレートのない固定ページ（規約の子ページなど）が使う。お知らせ・補足の欄は出さない。
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/content', 'page' );
endwhile;

spc_cta_block();

get_footer();
