<?php
/**
 * LP 9：お引き受けできない場合（白地）。罫線で区切ったリスト（DESIGN.md §4）。
 * 分かること・分からないこと・リスクを同じ声の大きさで書く（DESIGN.md §1-3）。
 */

defined( 'ABSPATH' ) || exit;

// TODO(仮): 文言は依頼元確認後に確定（導入文・判断基準・結びの文）
$spc_limits = array(
	__( '色落ちの可能性が高い素材・染色', 'spc' ),
	__( '縮み・風合いの変化が避けられない素材', 'spc' ),
	__( '経年による生地の劣化が進んでいるもの', 'spc' ),
	__( 'シミが繊維の奥まで定着していて、完全には落ちない可能性があるもの', 'spc' ),
);
?>
<section class="spc-section spc-limits" aria-labelledby="spc-limits-title">
	<div class="spc-container spc-split">
		<div class="spc-split__head">
			<h2 id="spc-limits-title" class="spc-limits__title"><?php esc_html_e( 'お引き受けできない場合があります', 'spc' ); ?></h2>
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
