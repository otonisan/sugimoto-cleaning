<?php
/**
 * 画像の仮枠「仮｜{撮影仕様}」。spc_image_or_placeholder() から呼ぶ。
 *
 * @var array{label:string,ratio:string} $args
 */

defined( 'ABSPATH' ) || exit;

$spc_text  = '仮｜' . ( isset( $args['label'] ) ? (string) $args['label'] : '' );
$spc_ratio = isset( $args['ratio'] ) ? trim( (string) $args['ratio'] ) : '';
$spc_style = '';

// 縦横比は「4/5」「16 / 10」の形だけ受け付ける。
if ( preg_match( '#^(\d+(?:\.\d+)?)\s*/\s*(\d+(?:\.\d+)?)$#', $spc_ratio, $spc_m ) ) {
	$spc_style = 'aspect-ratio: ' . $spc_m[1] . ' / ' . $spc_m[2] . ';';
}
?>
<div class="spc-ph-image" role="img" aria-label="<?php echo esc_attr( $spc_text ); ?>"<?php echo '' !== $spc_style ? ' style="' . esc_attr( $spc_style ) . '"' : ''; ?>><span class="spc-ph-image__label"><?php echo esc_html( $spc_text ); ?></span></div>
