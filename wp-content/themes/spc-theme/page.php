<?php
/**
 * 固定ページ（汎用）。下部に相談ブロックを置く。
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/content', 'page' );
endwhile;

spc_cta_block();

get_footer();
