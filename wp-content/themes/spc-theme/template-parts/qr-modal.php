<?php
/**
 * PC用のQRモーダル。CTAをクリックするとJSが showModal() で開く。
 */

defined( 'ABSPATH' ) || exit;

$spc_qr_id = function_exists( 'spc_get_setting' ) ? (int) spc_get_setting( 'line_qr_id' ) : 0;
?>
<dialog id="spc-qr-modal" class="spc-qr-modal" aria-labelledby="spc-qr-modal-title">
	<h2 id="spc-qr-modal-title" class="spc-qr-modal__title"><?php esc_html_e( 'スマートフォンでLINEを開いてください', 'spc' ); ?></h2>
	<div class="spc-qr-modal__qr">
		<?php
		spc_image_or_placeholder( $spc_qr_id, 'medium', 'LINEのQRコード', '1/1' );
		if ( ! spc_has_image( $spc_qr_id ) ) {
			spc_placeholder_badge( null, true ); // TODO(仮): QR画像は管理画面の「ブランド設定」で登録
		}
		?>
	</div>
	<p class="spc-qr-modal__text"><?php esc_html_e( 'スマートフォンのカメラで読み取ってください', 'spc' ); ?></p>
	<div class="spc-qr-modal__pc">
		<p class="spc-qr-modal__sub"><?php esc_html_e( 'このパソコンでLINEを開く場合', 'spc' ); ?></p>
		<?php spc_cta( 'qr_modal', 'compact' ); ?>
	</div>
	<button type="button" class="spc-qr-modal__close" data-qr-close><?php esc_html_e( '閉じる', 'spc' ); ?></button>
</dialog>
