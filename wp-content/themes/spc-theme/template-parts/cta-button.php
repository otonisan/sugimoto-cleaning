<?php
/**
 * CTAボタン「写真を送って無料相談」。spc_cta() から呼ぶ（$args の値は spc_cta() で検証済み）。
 *
 * @var array{location:string,variant:string} $args
 */

defined( 'ABSPATH' ) || exit;

$spc_location = isset( $args['location'] ) ? (string) $args['location'] : 'final';
$spc_variant  = isset( $args['variant'] ) ? (string) $args['variant'] : 'default';

// LINE公式アイコンは、LINEのブランドガイドラインを確認するまで付けない（DESIGN.md §10）。
?>
<a class="spc-cta spc-cta--<?php echo esc_attr( $spc_variant ); ?>" href="<?php echo esc_url( spc_theme_line_url() ); ?>" data-spc-cta data-cta-location="<?php echo esc_attr( $spc_location ); ?>"><?php esc_html_e( '写真を送って無料相談', 'spc' ); ?></a>
<?php if ( 'compact' !== $spc_variant ) : ?>
<span class="spc-cta__note spc-cta__note--sp"><?php esc_html_e( 'LINEが開きます', 'spc' ); ?></span>
<span class="spc-cta__note spc-cta__note--pc"><?php esc_html_e( 'LINEのQRコードを表示します', 'spc' ); ?></span>
<?php endif; ?>
