<?php
/**
 * 規約・法定表示（/legal/）。固定ページ legal 専用。
 * 並びは DESIGN.md §4：ページ見出し（リード文1文）→ 子ページへのリンク一覧（menu_order 順）→ 相談ブロック。
 * 固定ページの本文は使わない。子ページが0件（本番で仮データを除外した場合など）なら一覧の欄ごと出さない。
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$spc_post_id = (int) get_the_ID();

	// TODO(仮): 文言は依頼元確認後に確定 [copy:legal.lead]
	$spc_lead = __( '利用規約、特定商取引法に基づく表記、個人情報・相談写真の取扱いをまとめています。', 'spc' );

	// 子ページ（利用規約・特商法・個人情報）。menu_order 順。0件なら null。
	$spc_children = spc_lp_query(
		'page',
		array(
			'post_parent'    => $spc_post_id,
			'posts_per_page' => -1,
		)
	);

	get_template_part(
		'template-parts/page-header',
		null,
		array(
			'title'   => get_the_title(),
			'lead'    => $spc_lead,
			'post_id' => $spc_post_id,
		)
	);
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'spc-page spc-page--legal' ); ?>>
		<?php if ( $spc_children ) : ?>
			<section class="spc-section spc-legal" aria-label="<?php esc_attr_e( '規約・法定表示の一覧', 'spc' ); ?>">
				<div class="spc-container">
					<ul class="spc-subpage__list spc-legal-list" role="list">
						<?php
						while ( $spc_children->have_posts() ) :
							$spc_children->the_post();
							?>
							<li class="spc-subpage__item">
								<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
							</li>
						<?php endwhile; ?>
					</ul>
				</div>
			</section>
			<?php wp_reset_postdata(); ?>
		<?php endif; ?>
	</article>
	<?php
endwhile;

spc_cta_block();

get_footer();
