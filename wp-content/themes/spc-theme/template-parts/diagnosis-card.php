<?php
/**
 * 診断票（施工事例のカード）。クリーニング店の受付票をもとにした情報カード（DESIGN.md §4）。
 * 施工前／施工後は1つの4:5の枠で、ボタンを押したときだけ切り替える（before-after.js）。
 * JSが無いときは、2枚を縦に並べて両方見せる。
 * 掲載許諾ID（spc_permission_id）は内部の記録用のため、ここでは読まない。
 *
 * @var array{post_id:int} $args
 */

defined( 'ABSPATH' ) || exit;

$spc_post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : (int) get_the_ID();
if ( $spc_post_id <= 0 ) {
	return;
}

$spc_meta = static function ( string $key ) use ( $spc_post_id ): string {
	return trim( (string) get_post_meta( $spc_post_id, $key, true ) );
};

$spc_case_no = $spc_meta( 'spc_case_no' );
$spc_item    = $spc_meta( 'spc_item' );
$spc_stain   = $spc_meta( 'spc_stain' );
$spc_days    = (int) $spc_meta( 'spc_days' );
$spc_title   = wp_strip_all_tags( get_the_title( $spc_post_id ) );

// 行の一覧：array( 項目名, 値, 2行目の補足 )。
// 空の項目は行ごと出さない（「—」で埋めると、データがあるように見えるため）。
$spc_rows = array(
	array( __( '品目', 'spc' ), $spc_item, '' ),
	array( __( '素材', 'spc' ), $spc_meta( 'spc_material' ), '' ),
	array( __( 'シミ', 'spc' ), $spc_stain, $spc_meta( 'spc_elapsed' ) ), // 補足は付着からの期間
	array( __( '他店処理', 'spc' ), $spc_meta( 'spc_prior_treatment' ), '' ),
	array( __( '処置', 'spc' ), $spc_meta( 'spc_treatment' ), '' ),
	array( __( '仕上がり', 'spc' ), $spc_meta( 'spc_result' ), '' ),
	/* translators: %d: 日数 */
	array( __( '所要日数', 'spc' ), $spc_days > 0 ? sprintf( __( '%d日', 'spc' ), $spc_days ) : '', '' ),
);
$spc_rows = array_filter( $spc_rows, static fn( array $row ): bool => '' !== $row[1] );

// 施工前・施工後。
$spc_panes = array(
	'before' => array(
		'id'    => absint( get_post_meta( $spc_post_id, 'spc_before_id', true ) ),
		'label' => __( '施工前', 'spc' ),
	),
	'after'  => array(
		'id'    => absint( get_post_meta( $spc_post_id, 'spc_after_id', true ) ),
		'label' => __( '施工後', 'spc' ),
	),
);
// 代替テキストが未入力のときは、品目とシミから具体的に作る（DESIGN.md §5）。
$spc_subject = '' !== $spc_item ? $spc_item . ( '' !== $spc_stain ? 'の' . $spc_stain : '' ) : $spc_title;
?>
<article class="spc-diagnosis">
	<header class="spc-diagnosis__head">
		<h3 class="spc-diagnosis__no">
			<?php if ( '' !== $spc_case_no ) : ?>
				No. <span class="spc-num"><?php echo esc_html( $spc_case_no ); ?></span><span class="screen-reader-text"><?php echo esc_html( '　' . $spc_title ); ?></span>
			<?php else : ?>
				<?php echo esc_html( $spc_title ); ?>
			<?php endif; ?>
		</h3>
		<?php spc_placeholder_badge( $spc_post_id ); ?>
	</header>

	<div class="spc-diagnosis__photos" data-spc-ba>
		<div class="spc-diagnosis__frame">
			<?php foreach ( $spc_panes as $spc_key => $spc_pane ) : ?>
				<figure class="spc-diagnosis__pane" id="<?php echo esc_attr( 'spc-ba-' . $spc_post_id . '-' . $spc_key ); ?>" data-spc-ba-pane="<?php echo esc_attr( $spc_key ); ?>">
					<?php
					$spc_attr = array();
					if ( spc_has_image( $spc_pane['id'] ) && '' === trim( (string) get_post_meta( $spc_pane['id'], '_wp_attachment_image_alt', true ) ) ) {
						$spc_attr['alt'] = $spc_subject . '、' . $spc_pane['label'];
					}
					spc_image_or_placeholder( $spc_pane['id'], 'spc-portrait', $spc_pane['label'] . 'の写真 4:5', '4/5', $spc_attr );
					?>
					<figcaption class="spc-diagnosis__caption"><?php echo esc_html( $spc_pane['label'] ); ?></figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php // JSが動いたときだけ表示する（before-after.js が hidden を外す）。 ?>
		<div class="spc-diagnosis__toggle" role="group" aria-label="<?php esc_attr_e( '表示する写真', 'spc' ); ?>" data-spc-ba-toggle hidden>
			<?php foreach ( $spc_panes as $spc_key => $spc_pane ) : ?>
				<button type="button" class="spc-diagnosis__switch" aria-pressed="<?php echo 'before' === $spc_key ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( 'spc-ba-' . $spc_post_id . '-' . $spc_key ); ?>" data-spc-ba-show="<?php echo esc_attr( $spc_key ); ?>"><?php echo esc_html( $spc_pane['label'] ); ?></button>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if ( $spc_rows ) : ?>
		<dl class="spc-diagnosis__rows">
			<?php foreach ( $spc_rows as $spc_row ) : ?>
				<div class="spc-diagnosis__row">
					<dt><?php echo esc_html( $spc_row[0] ); ?></dt>
					<dd><?php echo esc_html( $spc_row[1] ); ?><?php if ( '' !== $spc_row[2] ) : ?><span class="spc-diagnosis__sub"><?php echo esc_html( $spc_row[2] ); ?></span><?php endif; ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>
</article>
