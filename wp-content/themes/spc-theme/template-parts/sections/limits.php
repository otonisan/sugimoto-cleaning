<?php
/**
 * お引き受けできない場合（既定は白地）。罫線で区切ったリスト（DESIGN.md §4）。
 * LP 9 と下層ページで共用する。引数を省略するとLPと同じ出力になる。
 * 分かること・分からないこと・リスクを同じ声の大きさで書く（DESIGN.md §1-3）。
 *
 * @var array{tone?:string,id?:string,heading?:string,heading_id?:string} $args
 */

defined( 'ABSPATH' ) || exit;

$spc_args = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'tone'       => 'paper',
		'id'         => '',
		'heading'    => __( 'お引き受けできない場合があります', 'spc' ),
		'heading_id' => 'spc-limits-title',
	)
);
$spc_section_id = '' !== (string) $spc_args['id'] ? ' id="' . esc_attr( (string) $spc_args['id'] ) . '"' : '';
$spc_tone_class = 'mist' === $spc_args['tone'] ? ' spc-section--mist' : '';

// TODO(仮): 文言は依頼元確認後に確定（導入文・判断基準・結びの文） [copy:lp.limits]
$spc_limits = array(
	__( '色落ちの可能性が高い素材・染色', 'spc' ),
	__( '縮み・風合いの変化が避けられない素材', 'spc' ),
	__( '経年による生地の劣化が進んでいるもの', 'spc' ),
	__( 'シミが繊維の奥まで定着していて、完全には落ちない可能性があるもの', 'spc' ),
);
?>
<section<?php echo $spc_section_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 上でエスケープ済み ?> class="spc-section<?php echo esc_attr( $spc_tone_class ); ?> spc-limits" aria-labelledby="<?php echo esc_attr( (string) $spc_args['heading_id'] ); ?>">
	<div class="spc-container spc-split">
		<div class="spc-split__head">
			<h2 id="<?php echo esc_attr( (string) $spc_args['heading_id'] ); ?>" class="spc-limits__title"><?php echo esc_html( (string) $spc_args['heading'] ); ?></h2>
			<p class="spc-split__lead"><?php esc_html_e( '施工することで、かえって衣類を傷めてしまうおそれがある場合は、お引き受けをお断りすることがあります。主な判断の基準は次のとおりです。', 'spc' ); ?></p>
		</div>
		<div class="spc-split__body">
			<ul class="spc-limits__list" role="list">
				<?php foreach ( $spc_limits as $spc_limit ) : ?>
					<li class="spc-limits__item"><?php echo esc_html( $spc_limit ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p class="spc-limits__closing"><?php esc_html_e( 'お引き受けできない場合も理由をご説明し、お預かり後であれば返送いたします（返送料は料金表をご覧ください）。', 'spc' ); ?></p>
		</div>
	</div>
</section>
