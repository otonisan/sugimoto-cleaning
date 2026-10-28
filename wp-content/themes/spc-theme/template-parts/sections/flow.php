<?php
/**
 * 全国宅配の流れ（既定は灰白地）。手順なので番号を付ける（DESIGN.md §1-2）。
 * LP 8 と下層ページで共用する。引数を省略するとLPと同じ出力になる。
 *
 * @var array{tone?:string,id?:string,heading?:string,heading_id?:string} $args
 */

defined( 'ABSPATH' ) || exit;

$spc_args = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'tone'       => 'mist',
		'id'         => '',
		'heading'    => __( '全国宅配の流れ', 'spc' ),
		'heading_id' => 'spc-flow-title',
	)
);
$spc_section_id = '' !== (string) $spc_args['id'] ? ' id="' . esc_attr( (string) $spc_args['id'] ) . '"' : '';
$spc_tone_class = 'mist' === $spc_args['tone'] ? ' spc-section--mist' : '';

// TODO(仮): 文言は依頼元確認後に確定（各手順の説明文） [copy:lp.flow]
$spc_flow = array(
	array( __( '写真で相談', 'spc' ), __( 'LINEで写真をお送りください。落とせる可能性とリスク、料金の目安をお伝えします。', 'spc' ) ),
	array( __( '発送', 'spc' ), __( 'ご納得いただけましたら、衣類を当店宛てにお送りください。', 'spc' ) ),
	array( __( '到着・検品', 'spc' ), __( '到着後、職人が現物の素材と状態を確認します。', 'spc' ) ),
	array( __( '正式なお見積り', 'spc' ), __( '現物を拝見したうえで、正式な金額と仕上がりの見込みをお伝えします。', 'spc' ) ),
	array( __( 'ご承認・お支払い', 'spc' ), __( 'お見積りの内容をご承認いただいてから、お支払いをお願いします。', 'spc' ) ),
	array( __( '施工', 'spc' ), __( '素材に合わせた方法で、職人が施工します。', 'spc' ) ),
	array( __( '返送', 'spc' ), __( '仕上がりを確認し、梱包してお返しします。', 'spc' ) ),
);
?>
<section<?php echo $spc_section_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 上でエスケープ済み ?> class="spc-section<?php echo esc_attr( $spc_tone_class ); ?> spc-flow" aria-labelledby="<?php echo esc_attr( (string) $spc_args['heading_id'] ); ?>">
	<div class="spc-container spc-split">
		<div class="spc-split__head">
			<h2 id="<?php echo esc_attr( (string) $spc_args['heading_id'] ); ?>" class="spc-flow__title"><?php echo esc_html( (string) $spc_args['heading'] ); ?></h2>
			<?php // TODO(仮): 文言は依頼元確認後に確定 [copy:lp.flow] ?>
			<p class="spc-split__lead"><?php esc_html_e( '正式なお見積りにご承認いただくまで、施工は始めません。', 'spc' ); ?></p>
		</div>
		<ol class="spc-flow__list spc-split__body" role="list">
			<?php foreach ( $spc_flow as $spc_i => $spc_step ) : ?>
				<li class="spc-flow__item">
					<span class="spc-flow__num" aria-hidden="true"><?php echo esc_html( (string) ( $spc_i + 1 ) ); ?></span>
					<h3 class="spc-flow__heading"><?php echo esc_html( $spc_step[0] ); ?></h3>
					<p class="spc-flow__text"><?php echo esc_html( $spc_step[1] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
