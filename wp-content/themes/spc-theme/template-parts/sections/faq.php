<?php
/**
 * よくある質問（既定は白地）。既定は「トップに表示」付きを最大5件（LP 10）。下層ページからは query を渡して使う。<details> で開閉（JS不要、DESIGN.md §6）。
 * 構造化データ（inc/schema.php の FAQPage）も同じクエリを使う。0件のときはセクションごと出さない。
 * grouped を true にすると、分類ごとに h3 を立てて並べる（/guide/）。引数を省略するとLPと同じ出力になる。
 *
 * @var array{tone?:string,id?:string,heading?:string,heading_id?:string,query?:?WP_Query,more_link?:bool,grouped?:bool} $args
 */

defined( 'ABSPATH' ) || exit;

$spc_args = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'tone'       => 'paper',
		'id'         => '',
		'heading'    => __( 'よくある質問', 'spc' ),
		'heading_id' => 'spc-faq-title',
		'query'      => null,
		'more_link'  => true,
		'grouped'    => false,
	)
);
$spc_section_id = '' !== (string) $spc_args['id'] ? ' id="' . esc_attr( (string) $spc_args['id'] ) . '"' : '';
$spc_tone_class = 'mist' === $spc_args['tone'] ? ' spc-section--mist' : '';

$spc_faqs = $spc_args['query'] instanceof WP_Query ? $spc_args['query'] : spc_top_faq_query();
if ( ! $spc_faqs || ! $spc_faqs->have_posts() ) {
	return;
}
?>
<section<?php echo $spc_section_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 上でエスケープ済み ?> class="spc-section<?php echo esc_attr( $spc_tone_class ); ?> spc-faq" aria-labelledby="<?php echo esc_attr( (string) $spc_args['heading_id'] ); ?>">
	<div class="spc-container spc-split">
		<div class="spc-split__head">
			<h2 id="<?php echo esc_attr( (string) $spc_args['heading_id'] ); ?>" class="spc-faq__title"><?php echo esc_html( (string) $spc_args['heading'] ); ?></h2>
		</div>
		<div class="spc-split__body">
			<div class="spc-faq__list">
<?php if ( $spc_args['grouped'] ) : ?>
					<?php
					$spc_group_labels = spc_faq_group_labels();
					foreach ( spc_group_faqs( $spc_faqs->posts ) as $spc_group_key => $spc_group_posts ) :
						?>
						<div class="spc-faq__group">
							<h3 class="spc-faq__group-title"><?php echo esc_html( $spc_group_labels[ $spc_group_key ] ?? (string) $spc_group_key ); ?></h3>
							<?php
							foreach ( $spc_group_posts as $spc_faq_post ) :
								setup_postdata( $GLOBALS['post'] = $spc_faq_post ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
								?>
								<details class="spc-faq__item">
									<summary class="spc-faq__summary"><span class="spc-faq__q"><?php echo esc_html( wp_strip_all_tags( get_the_title() ) ); ?></span><?php spc_placeholder_badge(); ?></summary>
									<div class="spc-faq__a"><?php echo wp_kses_post( wpautop( get_post_field( 'post_content', get_the_ID() ) ) ); ?></div>
								</details>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
				<?php
				while ( $spc_faqs->have_posts() ) :
					$spc_faqs->the_post();
					?>
					<details class="spc-faq__item">
						<summary class="spc-faq__summary"><span class="spc-faq__q"><?php echo esc_html( wp_strip_all_tags( get_the_title() ) ); ?></span><?php spc_placeholder_badge(); ?></summary>
						<div class="spc-faq__a"><?php echo wp_kses_post( wpautop( get_post_field( 'post_content', get_the_ID() ) ) ); ?></div>
					</details>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
<?php endif; ?>
			</div>
			<?php if ( $spc_args['more_link'] ) : ?>
				<p class="spc-faq__more"><a href="<?php echo esc_url( home_url( '/guide/#faq' ) ); ?>"><?php esc_html_e( 'よくある質問をすべて見る', 'spc' ); ?></a></p>
			<?php endif; ?>
		</div>
	</div>
</section>
