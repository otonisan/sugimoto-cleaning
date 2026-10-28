<?php
/**
 * トップ / LP。セクションは docs/sitemap.md §3 の順に並べる。
 * LPのコピー（見出し・考え方・注意書き）は誇大表現を防ぐためテーマに固定する（sitemap.md §2）。
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'template-parts/lp/hero' );     // 1 ファーストビュー（唯一の見せ場）
get_template_part( 'template-parts/lp/concerns' ); // 2 悩みと、当店の考え方
get_template_part( 'template-parts/lp/steps' );    // 3 写真相談の3ステップ
get_template_part( 'template-parts/lp/menu' );     // 4 対応メニュー
get_template_part( 'template-parts/sections/cases' );         // 5 施工事例（0件なら出さない）
get_template_part( 'template-parts/lp/craftsmen' );     // 6 職人と施工母体（LPで唯一の墨地）
get_template_part( 'template-parts/sections/price' );         // 7 料金の目安（0件なら出さない）
get_template_part( 'template-parts/sections/flow' ); // 8 全国宅配の流れ
get_template_part( 'template-parts/sections/limits' );        // 9 お引き受けできない場合
get_template_part( 'template-parts/sections/faq' );           // 10 FAQ抜粋（0件なら出さない）
get_template_part( 'template-parts/lp/final-cta' );     // 11 最終CTA

get_footer();
