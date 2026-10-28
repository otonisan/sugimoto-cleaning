<?php
/**
 * 施工事例（既定は灰白地）。診断票を並べる（DESIGN.md §4）。0件のときはセクションごと出さない。
 * LP 5 と下層ページで共用する。引数を省略するとLPと同じ出力になる。
 *
 * @var array{tone?:string,id?:string,heading?:string,heading_id?:string,query?:?WP_Query,lead?:?string,show_cta?:bool,more_link?:bool,card_heading?:string} $args
 */

defined( 'ABSPATH' ) || exit;

$spc_args = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'tone'         => 'mist',
		'id'           => '',
		'heading'      => __( '施工事例', 'spc' ),
		'heading_id'   => 'spc-cases-title',
		'query'        => null,
		'lead'         => null,
		'show_cta'     => true,
		'more_link'    => true,
		'card_heading' => 'h3',
	)
);

$spc_cases = $spc_args['query'] instanceof WP_Query ? $spc_args['query'] : spc_lp_query( 'spc_case', array( 'posts_per_page' => 3 ) );
if ( ! $spc_cases || ! $spc_cases->have_posts() ) {
	return;
}
$spc_cases_archive = $spc_args['more_link'] ? get_post_type_archive_link( 'spc_case' ) : false;
$spc_section_id    = '' !== (string) $spc_args['id'] ? ' id="' . esc_attr( (string) $spc_args['id'] ) . '"' : '';
$spc_tone_class    = 'mist' === $spc_args['tone'] ? ' spc-section--mist' : '';
?>
<section<?php echo $spc_section_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 上でエスケープ済み ?> class="spc-section<?php echo esc_attr( $spc_tone_class ); ?> spc-cases" aria-labelledby="<?php echo esc_attr( (string) $spc_args['heading_id'] ); ?>">
	<div class="spc-container">
		<h2 id="<?php echo esc_attr( (string) $spc_args['heading_id'] ); ?>" class="spc-cases__title"><?php echo esc_html( (string) $spc_args['heading'] ); ?></h2>
		<?php if ( null === $spc_args['lead'] ) : ?>
			<?php // TODO(仮): 文言は依頼元確認後に確定 ?>
			<p class="spc-cases__lead"><?php esc_html_e( '落としきれなかった場合も、仕上がりの欄にそのまま記載しています。', 'spc' ); ?></p>
		<?php elseif ( '' !== (string) $spc_args['lead'] ) : ?>
			<p class="spc-cases__lead"><?php echo esc_html( (string) $spc_args['lead'] ); ?></p>
		<?php endif; ?>
		<ul class="spc-cases__list" role="list">
			<?php
			while ( $spc_cases->have_posts() ) :
				$spc_cases->the_post();
				?>
				<li class="spc-cases__item">
					<?php
					get_template_part(
						'template-parts/diagnosis-card',
						null,
						array(
							'post_id' => get_the_ID(),
							'heading' => (string) $spc_args['card_heading'],
						)
					);
					?>
				</li>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</ul>
		<?php if ( $spc_args['show_cta'] || $spc_cases_archive ) : ?>
			<div class="spc-cases__footer">
				<?php
				if ( $spc_args['show_cta'] ) {
					spc_cta( 'cases' );
				}
				?>
				<?php if ( $spc_cases_archive ) : ?>
					<p class="spc-cases__more"><a href="<?php echo esc_url( $spc_cases_archive ); ?>"><?php esc_html_e( '施工事例をもっと見る', 'spc' ); ?></a></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
