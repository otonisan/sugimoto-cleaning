<?php
/**
 * 施工事例の一覧（/cases/）。
 * 並びは DESIGN.md §4：ページ見出し → 診断票を並べる（灰白。SP 1列・768px〜 2列・1024px〜 3列）→ ページ送り → 相談ブロック。
 * 1ページの件数は spc_case_archive_query（inc/setup.php）。0件のときは案内文と相談ブロックだけを出す（ステータスは200）。
 * ページの途中にCTAは置かない。
 */

defined( 'ABSPATH' ) || exit;

get_header();

// TODO(仮): 文言は依頼元確認後に確定（リード文） [copy:cases.lead]
$spc_lead = __( '落としきれなかった場合も、仕上がりの欄にそのまま記載しています。', 'spc' );

get_template_part(
	'template-parts/page-header',
	null,
	array(
		'title' => post_type_archive_title( '', false ),
		'lead'  => $spc_lead,
	)
);

if ( have_posts() ) :
	?>
	<section class="spc-section spc-section--mist spc-cases spc-cases--archive" aria-label="<?php esc_attr_e( '施工事例の一覧', 'spc' ); ?>">
		<div class="spc-container">
			<ul class="spc-cases__list" role="list">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<li class="spc-cases__item">
						<?php
						get_template_part(
							'template-parts/diagnosis-card',
							null,
							array(
								'post_id' => get_the_ID(),
								'heading' => 'h2',
								'link'    => true,
							)
						);
						?>
					</li>
				<?php endwhile; ?>
			</ul>
			<?php
			the_posts_pagination(
				array(
					'prev_text'          => __( '前へ', 'spc' ),
					'next_text'          => __( '次へ', 'spc' ),
					'screen_reader_text' => __( '施工事例のページ送り', 'spc' ),
				)
			);
			?>
		</div>
	</section>
<?php else : ?>
	<section class="spc-section spc-cases spc-cases--empty">
		<div class="spc-container">
			<?php // TODO(仮): 文言は依頼元確認後に確定（0件のときの案内文） [copy:cases.empty] ?>
			<p class="spc-subpage__text"><?php esc_html_e( '掲載できる施工事例を準備しています。写真でのご相談は受け付けています。', 'spc' ); ?></p>
		</div>
	</section>
	<?php
endif;

spc_cta_block();

get_footer();
