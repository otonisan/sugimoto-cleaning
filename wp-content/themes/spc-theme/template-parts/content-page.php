<?php
/**
 * 固定ページ・個別投稿の本文（見出し部品 → 本文）。ループ内で呼ぶ。
 * 本文は .spc-prose（規約系の本文もこの書式）。「お知らせ・補足」（page-notes）は出さない。
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'spc-page' ); ?>>
	<?php
	get_template_part(
		'template-parts/page-header',
		null,
		array(
			'title'   => get_the_title(),
			'post_id' => (int) get_the_ID(),
		)
	);
	?>
	<div class="spc-page__body">
		<div class="spc-container">
			<div class="spc-prose spc-table-content">
				<?php the_content(); ?>
			</div>
		</div>
	</div>
</article>
