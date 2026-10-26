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

// ここに sitemap.md §3 の 5〜11 を続ける（Task 9）：
// cases（施工事例）→ craftsmen（職人と施工母体）→ price（料金の目安）→ shipping-flow（全国宅配の流れ）
// → limits（お引き受けできない場合）→ faq（FAQ抜粋）→ final-cta（最終CTA）

get_footer();
