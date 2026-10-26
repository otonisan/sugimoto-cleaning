<?php
/**
 * ページが見つからないとき。
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="spc-page">
	<header class="spc-page__header">
		<div class="spc-container">
			<h1 class="spc-page__title"><?php esc_html_e( 'ページが見つかりませんでした。', 'spc' ); ?></h1>
		</div>
	</header>
	<div class="spc-page__body">
		<div class="spc-container spc-prose">
			<p><?php esc_html_e( 'お探しのページは、移動したか削除された可能性があります。', 'spc' ); ?></p>
			<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'トップページへ戻る', 'spc' ); ?></a></p>
		</div>
	</div>
</section>
<?php
spc_cta_block();

get_footer();
