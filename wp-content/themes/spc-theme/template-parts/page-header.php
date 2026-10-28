<?php
/**
 * 下層ページの見出し：パンくず → h1（＋仮バッジ）→ リード文（DESIGN.md §4 下層ページ）。
 * h1 はページに1つ。下に 1px の罫線。
 *
 * @var array{title?:string,lead?:string,post_id?:int} $args
 */

defined( 'ABSPATH' ) || exit;

$spc_args = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'title'   => '',
		'lead'    => '',
		'post_id' => 0,
	)
);
$spc_post_id = (int) $spc_args['post_id'];
$spc_lead    = trim( (string) $spc_args['lead'] );
?>
<header class="spc-page__header">
	<div class="spc-container">
		<?php get_template_part( 'template-parts/breadcrumbs' ); ?>
		<div class="spc-page__title-row">
			<h1 class="spc-page__title"><?php echo wp_kses_post( (string) $spc_args['title'] ); ?></h1>
			<?php
			if ( $spc_post_id > 0 ) {
				spc_placeholder_badge( $spc_post_id );
			}
			?>
		</div>
		<?php if ( '' !== $spc_lead ) : ?>
			<p class="spc-page__lead spc-lead"><?php echo esc_html( $spc_lead ); ?></p>
		<?php endif; ?>
	</div>
</header>
