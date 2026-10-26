<?php
/**
 * スマホの固定CTAバー。JSが表示を切り替えるため、初期状態は hidden（768px以上はCSSでも非表示）。
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="spc-sticky-cta" hidden>
	<?php spc_cta( 'sticky', 'compact' ); ?>
</div>
