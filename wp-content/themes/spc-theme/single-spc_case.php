<?php
/**
 * 施工事例の詳細（/cases/<slug>/）。
 * 並びは DESIGN.md §4：ページ見出し（h1は事例タイトル）→ 診断票（白。PCでは写真を左、項目を右）→ 前後の事例へのリンク → 相談ブロック。
 * 診断票の「No.」は p（h1とは別に見出しを増やさない）。ページの途中にCTAは置かない。
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$spc_post_id  = (int) get_the_ID();
	$spc_previous = spc_adjacent_case( $spc_post_id, true );
	$spc_next     = spc_adjacent_case( $spc_post_id, false );

	get_template_part(
		'template-parts/page-header',
		null,
		array(
			'title'   => get_the_title(),
			'post_id' => $spc_post_id,
		)
	);
	?>
	<section id="post-<?php the_ID(); ?>" <?php post_class( 'spc-section spc-case-single' ); ?> aria-label="<?php esc_attr_e( '施工事例の詳細', 'spc' ); ?>">
		<div class="spc-container">
			<?php
			get_template_part(
				'template-parts/diagnosis-card',
				null,
				array(
					'post_id' => $spc_post_id,
					'heading' => 'p',
					'wide'    => true,
				)
			);
			?>
			<nav class="spc-case-nav" aria-label="<?php esc_attr_e( '前後の施工事例', 'spc' ); ?>">
				<ul class="spc-case-nav__list" role="list">
					<?php if ( $spc_previous ) : ?>
						<li class="spc-case-nav__item spc-case-nav__item--prev"><a href="<?php echo esc_url( get_permalink( $spc_previous ) ); ?>" rel="prev"><?php esc_html_e( '前の事例', 'spc' ); ?></a></li>
					<?php endif; ?>
					<li class="spc-case-nav__item spc-case-nav__item--archive"><a href="<?php echo esc_url( get_post_type_archive_link( 'spc_case' ) ); ?>"><?php esc_html_e( '施工事例の一覧へ', 'spc' ); ?></a></li>
					<?php if ( $spc_next ) : ?>
						<li class="spc-case-nav__item spc-case-nav__item--next"><a href="<?php echo esc_url( get_permalink( $spc_next ) ); ?>" rel="next"><?php esc_html_e( '次の事例', 'spc' ); ?></a></li>
					<?php endif; ?>
				</ul>
			</nav>
		</div>
	</section>
	<?php
endwhile;

spc_cta_block();

get_footer();
