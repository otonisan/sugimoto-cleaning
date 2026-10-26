<?php
/**
 * LP 3：写真相談の3ステップ（灰白地）。手順なので番号を付ける（DESIGN.md §1-2）。
 * 送ってほしい写真の例（全体・接写・洗濯表示）は、画像ではなく枠と文字で図示する。
 */

defined( 'ABSPATH' ) || exit;

$spc_reply_note = spc_reply_time_note();

// 送ってほしい写真の例。shape は図の中身（CSSで描く）。
$spc_photo_examples = array(
	array(
		'shape' => 'whole',
		'label' => __( '衣類の全体', 'spc' ),
	),
	array(
		'shape' => 'closeup',
		'label' => __( 'シミの接写', 'spc' ),
	),
	array(
		'shape' => 'care',
		'label' => __( '洗濯表示', 'spc' ),
	),
);
?>
<section class="spc-section spc-section--mist spc-steps" aria-labelledby="spc-steps-title">
	<div class="spc-container">
		<h2 id="spc-steps-title" class="spc-steps__title"><?php esc_html_e( '写真で相談する3つのステップ', 'spc' ); ?></h2>
		<?php // TODO(仮): 文言は依頼元確認後に確定（各ステップの説明文） ?>
		<ol class="spc-steps__list">
			<li class="spc-steps__item">
				<span class="spc-steps__num" aria-hidden="true">1</span>
				<h3 class="spc-steps__heading"><?php esc_html_e( 'LINEで友だち追加', 'spc' ); ?></h3>
				<p class="spc-steps__text"><?php esc_html_e( '下のボタンからLINEを開き、友だち追加をしてください。', 'spc' ); ?></p>
			</li>
			<li class="spc-steps__item">
				<span class="spc-steps__num" aria-hidden="true">2</span>
				<h3 class="spc-steps__heading"><?php esc_html_e( '衣類全体・シミの接写・洗濯表示の写真を送る', 'spc' ); ?></h3>
				<p class="spc-steps__text"><?php esc_html_e( '明るい場所で撮影してください。シミが付いてからの期間や、ご自身で処理したかどうかも書き添えていただくと、判断の助けになります。', 'spc' ); ?></p>
				<ul class="spc-steps__photos" aria-label="<?php esc_attr_e( '送っていただく写真の例', 'spc' ); ?>">
					<?php foreach ( $spc_photo_examples as $spc_example ) : ?>
						<li class="spc-steps__photo">
							<span class="spc-steps__frame spc-steps__frame--<?php echo esc_attr( $spc_example['shape'] ); ?>" aria-hidden="true"><span></span></span>
							<span class="spc-steps__photo-label"><?php echo esc_html( $spc_example['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</li>
			<li class="spc-steps__item">
				<span class="spc-steps__num" aria-hidden="true">3</span>
				<h3 class="spc-steps__heading"><?php esc_html_e( '職人から一次回答', 'spc' ); ?></h3>
				<p class="spc-steps__text"><?php esc_html_e( '写真から分かる範囲で、落とせる可能性とリスク、料金の目安をお伝えします。正式なお見積りは、現物を拝見してからになります。', 'spc' ); ?></p>
				<?php if ( '' !== $spc_reply_note ) : ?>
					<p class="spc-steps__note">
						<?php
						/* translators: %s: 一次返信の目安（ブランド設定） */
						echo esc_html( sprintf( __( '返信の目安：%s', 'spc' ), $spc_reply_note ) );
						?>
					</p>
				<?php endif; ?>
			</li>
		</ol>
		<div class="spc-steps__cta">
			<?php spc_cta( 'steps' ); ?>
		</div>
	</div>
</section>
