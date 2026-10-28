<?php
/**
 * パンくずリスト（DESIGN.md §4 下層ページ）。項目は spc_breadcrumb_items()。
 * 今のページはリンクにせず aria-current="page"。区切りの「／」は CSS で付ける。
 */

defined( 'ABSPATH' ) || exit;

$spc_crumbs = array_values( spc_breadcrumb_items() );
if ( count( $spc_crumbs ) < 2 ) {
	return;
}
$spc_last = count( $spc_crumbs ) - 1;
?>
<nav class="spc-breadcrumbs" aria-label="<?php esc_attr_e( 'パンくずリスト', 'spc' ); ?>">
	<ol class="spc-breadcrumbs__list" role="list">
		<?php foreach ( $spc_crumbs as $spc_i => $spc_crumb ) : ?>
			<li class="spc-breadcrumbs__item">
				<?php if ( $spc_i === $spc_last ) : ?>
					<span aria-current="page"><?php echo esc_html( $spc_crumb['label'] ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( $spc_crumb['url'] ); ?>"><?php echo esc_html( $spc_crumb['label'] ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
