<?php
/**
 * LP 5：施工事例（灰白地）。診断票 ×3（DESIGN.md §4）。0件のときはセクションごと出さない。
 */

defined( 'ABSPATH' ) || exit;

$spc_cases = spc_lp_query( 'spc_case', array( 'posts_per_page' => 3 ) );
if ( ! $spc_cases ) {
	return;
}
$spc_cases_archive = get_post_type_archive_link( 'spc_case' );
?>
<section class="spc-section spc-section--mist spc-cases" aria-labelledby="spc-cases-title">
	<div class="spc-container">
		<h2 id="spc-cases-title" class="spc-cases__title"><?php esc_html_e( '施工事例', 'spc' ); ?></h2>
		<?php // TODO(仮): 文言は依頼元確認後に確定 ?>
		<p class="spc-cases__lead"><?php esc_html_e( '落としきれなかった場合も、仕上がりの欄にそのまま記載しています。', 'spc' ); ?></p>
		<ul class="spc-cases__list" role="list">
			<?php
			while ( $spc_cases->have_posts() ) :
				$spc_cases->the_post();
				?>
				<li class="spc-cases__item">
					<?php get_template_part( 'template-parts/diagnosis-card', null, array( 'post_id' => get_the_ID() ) ); ?>
				</li>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</ul>
		<div class="spc-cases__footer">
			<?php spc_cta( 'cases' ); ?>
			<?php if ( $spc_cases_archive ) : ?>
				<p class="spc-cases__more"><a href="<?php echo esc_url( $spc_cases_archive ); ?>"><?php esc_html_e( '施工事例をもっと見る', 'spc' ); ?></a></p>
			<?php endif; ?>
		</div>
	</div>
</section>
