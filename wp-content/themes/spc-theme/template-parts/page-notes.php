<?php
/**
 * 「お知らせ・補足」：固定ページの本文（依頼元が入力したもの）を白地のセクションで出す（DESIGN.md §4 下層ページ）。
 * 本文が空（タグと &nbsp; を除いて文字が残らない）なら見出しごと出さない。
 *
 * @var array{post_id?:int} $args
 */

defined( 'ABSPATH' ) || exit;

$spc_post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : 0;
$spc_notes   = $spc_post_id > 0 ? spc_page_notes_html( $spc_post_id ) : '';
if ( '' === $spc_notes ) {
	return;
}
?>
<section class="spc-section spc-page-notes" aria-labelledby="spc-page-notes-title">
	<div class="spc-container">
		<h2 id="spc-page-notes-title" class="spc-page-notes__title"><?php esc_html_e( 'お知らせ・補足', 'spc' ); ?></h2>
		<div class="spc-prose spc-page-notes__body">
			<?php echo wp_kses_post( $spc_notes ); ?>
		</div>
	</div>
</section>
