<?php
/**
 * LP 2：悩みと、当店の考え方（白地）。カードにせず、悩み → 応答の2段で書く（DESIGN.md §4）。
 */

defined( 'ABSPATH' ) || exit;

// TODO(仮): 文言は依頼元確認後に確定（見出し・悩みの3文）
$spc_concerns = array(
	__( '他店で断られた', 'spc' ),
	__( '自分で処理したら広がってしまった', 'spc' ),
	__( '失敗できない大切な一着', 'spc' ),
);
?>
<section class="spc-section spc-concerns" aria-labelledby="spc-concerns-title">
	<div class="spc-container spc-concerns__inner">
		<?php // 折り返しは「、」の後ろだけ（wbr と word-break: keep-all）。 ?>
		<h2 id="spc-concerns-title" class="spc-concerns__title"><?php esc_html_e( '他店で落ちなかったシミ、', 'spc' ); ?><wbr><?php esc_html_e( 'ご相談ください。', 'spc' ); ?></h2>
		<ul class="spc-concerns__list">
			<?php foreach ( $spc_concerns as $spc_concern ) : ?>
				<li class="spc-concerns__item"><?php echo esc_html( $spc_concern ); ?></li>
			<?php endforeach; ?>
		</ul>
		<p class="spc-concerns__stance"><?php esc_html_e( '必ず落とすとは言いません。職人が状態を見て、可能性とリスクを正直にお伝えします。', 'spc' ); ?></p>
	</div>
</section>
