<?php
/**
 * LP 7：料金の目安（白地）。表は横罫線だけ、金額は右揃え・桁揃え（DESIGN.md §6）。
 * 「写真で概算 → 現物を拝見して正式見積 → ご承認後に施工」の仕組みを先に見せる。
 * 金額はすべて仮（料金CPTの仮データ）。0件のときはセクションごと出さない。
 */

defined( 'ABSPATH' ) || exit;

$spc_prices = spc_lp_query( 'spc_price', array( 'posts_per_page' => 50 ) );
if ( ! $spc_prices ) {
	return;
}

// 区分ごとに分ける。品目の表は stain → yellowing → down → coat の順、送料・手数料は別の表。
$spc_item_groups = array( 'stain', 'yellowing', 'down', 'coat' );
$spc_by_group    = array_fill_keys( array_merge( $spc_item_groups, array( 'shipping' ) ), array() );
foreach ( $spc_prices->posts as $spc_price_post ) {
	$spc_group = (string) get_post_meta( $spc_price_post->ID, 'spc_price_group', true );
	if ( isset( $spc_by_group[ $spc_group ] ) ) {
		$spc_by_group[ $spc_group ][] = $spc_price_post;
	}
}
$spc_item_rows = array_merge( ...array_map( static fn( string $group ): array => $spc_by_group[ $group ], $spc_item_groups ) );

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
			'rows'    => $spc_by_group['shipping'],
		),
	),
	static fn( array $table ): bool => (bool) $table['rows']
);
if ( ! $spc_tables ) {
	return;
}

// TODO(仮): 文言は依頼元確認後に確定（3段階の説明文）
$spc_price_steps = array(
	array( __( '写真で概算', 'spc' ), __( '写真から分かる範囲で、料金の目安をお伝えします。', 'spc' ) ),
	array( __( '現物を拝見して正式見積', 'spc' ), __( 'お送りいただいた衣類を職人が確認し、正式な金額をお伝えします。', 'spc' ) ),
	array( __( 'ご承認後に施工', 'spc' ), __( '金額と仕上がりの見込みにご納得いただいてから、施工を始めます。', 'spc' ) ),
);
?>
<section class="spc-section spc-price" aria-labelledby="spc-price-title">
	<div class="spc-container">
		<h2 id="spc-price-title" class="spc-price__title"><?php esc_html_e( '料金の目安', 'spc' ); ?></h2>
		<ol class="spc-price__steps" role="list">
			<?php foreach ( $spc_price_steps as $spc_i => $spc_step ) : ?>
				<li class="spc-price__step">
					<span class="spc-price__step-num" aria-hidden="true"><?php echo esc_html( (string) ( $spc_i + 1 ) ); ?></span>
					<p class="spc-price__step-label"><?php echo esc_html( $spc_step[0] ); ?></p>
					<p class="spc-price__step-text"><?php echo esc_html( $spc_step[1] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>

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
							$spc_amount = (int) get_post_meta( $spc_row->ID, 'spc_amount', true );
							$spc_from   = '1' === (string) get_post_meta( $spc_row->ID, 'spc_from', true );
							$spc_note   = trim( (string) get_post_meta( $spc_row->ID, 'spc_note', true ) );
							?>
							<tr>
								<th scope="row" class="spc-price__name"><?php echo esc_html( wp_strip_all_tags( get_the_title( $spc_row ) ) ); ?></th>
								<td class="spc-price__amount"><span class="spc-price__value"><?php echo esc_html( spc_format_price( $spc_amount, $spc_from ) ); ?></span><?php spc_placeholder_badge( $spc_row->ID ); ?></td>
								<td class="spc-price__note"><?php echo esc_html( $spc_note ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>
		</div>

		<p class="spc-price__disclaimer"><?php esc_html_e( '表示は目安です。素材や状態によって変わります。正式な金額は現物を拝見してからお伝えします。', 'spc' ); ?></p>
		<p class="spc-price__more"><a href="<?php echo esc_url( home_url( '/price/' ) ); ?>"><?php esc_html_e( '料金・送料の詳細', 'spc' ); ?></a></p>
	</div>
</section>
