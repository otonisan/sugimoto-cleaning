<?php
/**
 * 固定ページ・個別投稿の本文（見出し・仮バッジ・本文）。ループ内で呼ぶ。
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'spc-page' ); ?>>
	<header class="spc-page__header">
		<div class="spc-container spc-page__title-row">
			<h1 class="spc-page__title"><?php the_title(); ?></h1>
			<?php spc_placeholder_badge(); ?>
		</div>
	</header>
	<div class="spc-page__body">
		<div class="spc-container">
			<div class="spc-prose">
				<?php the_content(); ?>
			</div>
		</div>
	</div>
</article>
