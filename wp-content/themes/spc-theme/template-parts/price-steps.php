<?php
/**
 * 料金が決まるまでの3段（写真で概算 → 現物を拝見して正式見積 → ご承認後に施工）。
 * LP の料金セクション（sections/price）と料金ページで共用する。文言は spc_price_steps()。
 */

defined( 'ABSPATH' ) || exit;
?>
<ol class="spc-price__steps" role="list">
	<?php foreach ( spc_price_steps() as $spc_i => $spc_step ) : ?>
		<li class="spc-price__step">
			<span class="spc-price__step-num" aria-hidden="true"><?php echo esc_html( (string) ( $spc_i + 1 ) ); ?></span>
			<p class="spc-price__step-label"><?php echo esc_html( $spc_step[0] ); ?></p>
			<p class="spc-price__step-text"><?php echo esc_html( $spc_step[1] ); ?></p>
		</li>
	<?php endforeach; ?>
</ol>
