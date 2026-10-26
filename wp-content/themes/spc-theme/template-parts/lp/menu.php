<?php
/**
 * LP 4：対応メニュー（白地）。4品目、実写＋短い説明。カードの影・ホバー演出は付けない（DESIGN.md §1・§4）。
 */

defined( 'ABSPATH' ) || exit;

// TODO(仮): 文言は依頼元確認後に確定（各品目の説明文）
$spc_menu_items = array(
	array(
		'label' => __( '特殊シミ抜き', 'spc' ),
		'path'  => '/stain-removal/',
		'text'  => __( 'ワインやコーヒー、時間がたったシミなど、他店で落ちなかったシミのご相談も承ります。', 'spc' ),
	),
	array(
		'label' => __( '黄ばみ・汗汚れ', 'spc' ),
		'path'  => '/stain-removal/#yellowing',
		'text'  => __( '襟元や脇の黄ばみ、しまっている間に浮き出た汗じみの状態を確認します。', 'spc' ),
	),
	array(
		'label' => __( '高級ダウン', 'spc' ),
		'path'  => '/down-coat/',
		'text'  => __( '表地の素材と羽毛の状態を確かめてから、洗い方を決めます。', 'spc' ),
	),
	array(
		'label' => __( 'コート・ジャケット', 'spc' ),
		'path'  => '/down-coat/#coat',
		'text'  => __( 'カシミヤやウールのコート、ジャケットを、裏地や装飾も含めて確認します。', 'spc' ),
	),
);
?>
<section class="spc-section spc-menu" aria-labelledby="spc-menu-title">
	<div class="spc-container">
		<h2 id="spc-menu-title" class="spc-menu__title"><?php esc_html_e( 'ご相談いただける衣類と汚れ', 'spc' ); ?></h2>
		<ul class="spc-menu__list" role="list">
			<?php foreach ( $spc_menu_items as $spc_item ) : ?>
				<li class="spc-menu__item">
					<div class="spc-menu__media">
						<?php
						// TODO(仮): 実写（衣類全体・白か灰白の背景、DESIGN.md §5）に差し替え
						spc_image_or_placeholder( 0, 'spc-menu', $spc_item['label'] . 'の実写 3:2', '3/2' );
						?>
					</div>
					<h3 class="spc-menu__label"><a class="spc-menu__link" href="<?php echo esc_url( home_url( $spc_item['path'] ) ); ?>"><?php echo esc_html( $spc_item['label'] ); ?></a></h3>
					<p class="spc-menu__text"><?php echo esc_html( $spc_item['text'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
