<?php
/**
 * LP 11：最終CTA（灰白地・中央揃え）。中央揃えはここだけ（DESIGN.md §3）。
 * 見えている間はスマホの固定CTAを隠す（data-sticky-hide）。PC（1024px以上）ではQRコードも並べる。
 */

defined( 'ABSPATH' ) || exit;

$spc_reply_note = spc_reply_time_note();
$spc_qr_id      = function_exists( 'spc_get_setting' ) ? absint( spc_get_setting( 'line_qr_id' ) ) : 0;
?>
<section class="spc-section spc-section--mist spc-final-cta" aria-labelledby="spc-final-cta-title" data-sticky-hide>
	<div class="spc-container spc-final-cta__inner">
		<h2 id="spc-final-cta-title" class="spc-final-cta__title"><?php esc_html_e( '写真を送って、まずはご相談ください', 'spc' ); ?></h2>
		<?php // TODO(仮): 文言は依頼元確認後に確定 [copy:lp.final-cta] ?>
		<p class="spc-final-cta__text"><?php esc_html_e( '他店で落ちなかったシミのご相談も承ります。落とせる可能性とリスクを、職人が写真を拝見してお伝えします。', 'spc' ); ?></p>
		<div class="spc-final-cta__action">
			<?php spc_cta( 'final' ); ?>
		</div>
		<?php if ( '' !== $spc_reply_note ) : ?>
			<p class="spc-final-cta__note">
				<?php
				/* translators: %s: 一次返信の目安（ブランド設定） */
				echo esc_html( sprintf( __( '一次返信の目安：%s', 'spc' ), $spc_reply_note ) );
				?>
			</p>
		<?php endif; ?>
		<figure class="spc-final-cta__qr">
			<div class="spc-final-cta__qr-image">
				<?php if ( spc_has_image( $spc_qr_id ) ) : ?>
					<?php echo wp_get_attachment_image( $spc_qr_id, 'medium', false, array( 'alt' => __( 'LINE公式アカウントの友だち追加用QRコード', 'spc' ) ) ); ?>
				<?php else : ?>
					<?php
					// TODO(仮): 「ブランド設定」の LINE QRコード画像を登録すると差し替わる。
					spc_image_or_placeholder( 0, 'medium', 'LINEのQRコード', '1/1' );
					spc_placeholder_badge( null, true );
					?>
				<?php endif; ?>
			</div>
			<figcaption class="spc-final-cta__qr-caption"><?php esc_html_e( 'スマートフォンのカメラで読み取ってください', 'spc' ); ?></figcaption>
		</figure>
	</div>
</section>
