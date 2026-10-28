<?php
/**
 * 料金の目安（既定は白地）。LP 7 と下層ページで共用する。引数を省略するとLPと同じ出力になる。
 * 表は横罫線だけ、金額は右揃え・桁揃え（DESIGN.md §6）。
 * 「写真で概算 → 現物を拝見して正式見積 → ご承認後に施工」の仕組みを先に見せる。
 * 金額はすべて仮（料金CPTの仮データ）。0件のときはセクションごと出さない。
 *
 * @var array{tone?:string,id?:string,heading?:string,heading_id?:string,groups?:string[],show_steps?:bool,more_link?:bool,show_disclaimer?:bool} $args
 */

defined( 'ABSPATH' ) || exit;

$spc_args = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'tone'            => 'paper',
		'id'              => '',
		'heading'         => __( '料金の目安', 'spc' ),
		'heading_id'      => 'spc-price-title',
		'groups'          => array( 'stain', 'yellowing', 'down', 'coat', 'shipping' ),
		'show_steps'      => true,
		'more_link'       => true,
		'show_disclaimer' => true,
	)
);
$spc_section_id = '' !== (string) $spc_args['id'] ? ' id="' . esc_attr( (string) $spc_args['id'] ) . '"' : '';
$spc_tone_class = 'mist' === $spc_args['tone'] ? ' spc-section--mist' : '';

$spc_prices = spc_lp_query( 'spc_price', array( 'posts_per_page' => 50 ) );
if ( ! $spc_prices ) {
	return;
}

// 区分ごとに分ける。品目の表は stain → yellowing → down → coat の順、送料・手数料は別の表。
$spc_groups      = (array) $spc_args['groups'];
$spc_item_groups = array_values( array_intersect( array( 'stain', 'yellowing', 'down', 'coat' ), $spc_groups ) );
$spc_by_group    = array_fill_keys( array_merge( $spc_item_groups, array( 'shipping' ) ), array() );
foreach ( $spc_prices->posts as $spc_price_post ) {
	$spc_group = (string) get_post_meta( $spc_price_post->ID, 'spc_price_group', true );
	if ( isset( $spc_by_group[ $spc_group ] ) ) {
		$spc_by_group[ $spc_group ][] = $spc_price_post;
	}
}
$spc_item_rows = array() === $spc_item_groups ? array() : array_merge( ...array_map( static fn( string $group ): array => $spc_by_group[ $group ], $spc_item_groups ) );

$spc_tables = array_filter(
	array(
		array(
			'id'      => 'spc-price-items',
			'caption' => __( '品目別の目安', 'spc' ),
			'head'    => __( '品目', 'spc' ),
			'rows'    => $spc_item_rows,
		),
		array(
			'id'      => 'spc-price-shipping',
			'caption' => __( '送料・手数料', 'spc' ),
			'head'    => __( '項目', 'spc' ),
			'rows'    => in_array( 'shipping', $spc_groups, true ) ? $spc_by_group['shipping'] : array(),
		),
	),
	static fn( array $table ): bool => (bool) $table['rows']
);
if ( ! $spc_tables ) {
	return;
}
?>
<section<?php echo $spc_section_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 上でエスケープ済み ?> class="spc-section<?php echo esc_attr( $spc_tone_class ); ?> spc-price" aria-labelledby="<?php echo esc_attr( (string) $spc_args['heading_id'] ); ?>">
	<div class="spc-container">
		<h2 id="<?php echo esc_attr( (string) $spc_args['heading_id'] ); ?>" class="spc-price__title"><?php echo esc_html( (string) $spc_args['heading'] ); ?></h2>
		<?php if ( $spc_args['show_steps'] ) : ?>
			<ol class="spc-price__steps" role="list">
				<?php foreach ( spc_price_steps() as $spc_i => $spc_step ) : ?>
					<li class="spc-price__step">
						<span class="spc-price__step-num" aria-hidden="true"><?php echo esc_html( (string) ( $spc_i + 1 ) ); ?></span>
						<p class="spc-price__step-label"><?php echo esc_html( $spc_step[0] ); ?></p>
						<p class="spc-price__step-text"><?php echo esc_html( $spc_step[1] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>

		<div class="spc-price__tables">
			<?php foreach ( $spc_tables as $spc_table ) : ?>
				<table class="spc-price__table" id="<?php echo esc_attr( $spc_table['id'] ); ?>">
					<caption class="spc-price__caption"><?php echo esc_html( $spc_table['caption'] ); ?></caption>
					<thead>
						<tr>
							<th scope="col"><?php echo esc_html( $spc_table['head'] ); ?></th>
							<th scope="col" class="spc-price__amount"><?php esc_html_e( '目安', 'spc' ); ?></th>
							<th scope="col" class="spc-price__note"><?php esc_html_e( '注記', 'spc' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $spc_table['rows'] as $spc_row ) : ?>
							<?php
							$spc_note = trim( (string) get_post_meta( $spc_row->ID, 'spc_note', true ) );
							?>
							<tr>
								<th scope="row" class="spc-price__name"><?php echo esc_html( wp_strip_all_tags( get_the_title( $spc_row ) ) ); ?></th>
								<td class="spc-price__amount"><span class="spc-price__value"><?php echo esc_html( spc_price_label( $spc_row->ID ) ); ?></span><?php spc_placeholder_badge( $spc_row->ID ); ?></td>
								<td class="spc-price__note"><?php echo esc_html( $spc_note ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>
		</div>

		<?php if ( $spc_args['show_disclaimer'] ) : ?>
			<p class="spc-price__disclaimer"><?php esc_html_e( '表示は目安です。素材や状態によって変わります。正式な金額は現物を拝見してからお伝えします。', 'spc' ); ?></p>
		<?php endif; ?>
		<?php if ( $spc_args['more_link'] ) : ?>
			<p class="spc-price__more"><a href="<?php echo esc_url( home_url( '/price/' ) ); ?>"><?php esc_html_e( '料金・送料の詳細', 'spc' ); ?></a></p>
		<?php endif; ?>
	</div>
</section>
