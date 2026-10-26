<?php
/**
 * 共通フッター：運営会社・フッターメニュー・コピーライト。
 */

defined( 'ABSPATH' ) || exit;
?>
</main>
<footer class="spc-footer">
	<div class="spc-container spc-footer__inner">
		<div class="spc-footer__about">
			<p class="spc-footer__brand"><?php echo esc_html( spc_brand_name() ); ?></p>
			<p class="spc-footer__operator"><?php esc_html_e( '運営：有限会社杉本クリーニング商会（創業1954年）', 'spc' ); ?></p>
		</div>
		<nav class="spc-footer__nav spc-nav" aria-label="<?php esc_attr_e( 'フッターメニュー', 'spc' ); ?>">
			<?php spc_nav_menu( 'footer' ); ?>
		</nav>
		<p class="spc-footer__copy"><small>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( spc_brand_name() ); ?></small></p>
	</div>
</footer>
<?php get_template_part( 'template-parts/sticky-cta' ); ?>
<?php get_template_part( 'template-parts/qr-modal' ); ?>
<?php wp_footer(); ?>
</body>
</html>
