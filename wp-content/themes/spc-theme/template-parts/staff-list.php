<?php
/**
 * 職人の一覧（顔写真・名前・役職・紹介文）。トップの職人セクションと /about/ で共用する。
 * $args：query（WP_Query。spc_staff）、heading（名前の見出しタグ。h2〜h6、既定 h3）。
 * 顔写真は職人本人の実写のみ（ストックフォト・AI生成画像は使わない。DESIGN.md §5）。
 */

defined( 'ABSPATH' ) || exit;

$spc_staff   = isset( $args['query'] ) && $args['query'] instanceof WP_Query ? $args['query'] : null;
$spc_heading = isset( $args['heading'] ) && in_array( $args['heading'], array( 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $args['heading'] : 'h3';

if ( ! $spc_staff ) {
	return;
}
?>
<ul class="spc-craftsmen__staff" role="list">
	<?php
	while ( $spc_staff->have_posts() ) :
		$spc_staff->the_post();
		$spc_role = trim( (string) get_post_meta( get_the_ID(), 'spc_role', true ) );
		$spc_bio  = trim( (string) get_post_meta( get_the_ID(), 'spc_bio', true ) );
		?>
		<li class="spc-craftsmen__person">
			<div class="spc-craftsmen__photo">
				<?php
				// TODO(仮): 職人本人の実写（4:5）に差し替え。ストックフォト・AI生成画像は使わない（DESIGN.md §5）。
				spc_image_or_placeholder( (int) get_post_thumbnail_id(), 'spc-portrait', '職人の顔写真 4:5', '4/5' );
				?>
			</div>
			<div class="spc-craftsmen__profile">
				<div class="spc-craftsmen__name-row">
					<<?php echo esc_attr( $spc_heading ); ?> class="spc-craftsmen__name"><?php echo esc_html( wp_strip_all_tags( get_the_title() ) ); ?></<?php echo esc_attr( $spc_heading ); ?>>
					<?php spc_placeholder_badge(); ?>
				</div>
				<?php if ( '' !== $spc_role ) : ?>
					<p class="spc-craftsmen__role"><?php echo esc_html( $spc_role ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $spc_bio ) : ?>
					<p class="spc-craftsmen__bio"><?php echo nl2br( esc_html( $spc_bio ) ); ?></p>
				<?php endif; ?>
			</div>
		</li>
		<?php
	endwhile;
	wp_reset_postdata();
	?>
</ul>
