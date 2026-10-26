<?php
/**
 * Google Tag Manager。コンテナIDが「ブランド設定」にあるときだけ出力する。
 */

defined( 'ABSPATH' ) || exit;

/**
 * 設定済みのGTM ID（未設定・不正な形式は空文字）。
 */
function spc_gtm_id(): string {
	$id = function_exists( 'spc_get_setting' ) ? trim( (string) spc_get_setting( 'gtm_id' ) ) : '';
	return 1 === preg_match( '/^GTM-[A-Z0-9]+$/', $id ) ? $id : '';
}

/**
 * <head> の GTM スニペット（dataLayer はスニペットより前に初期化する）。
 */
function spc_gtm_head(): void {
	$id = spc_gtm_id();
	if ( '' === $id ) {
		return;
	}
	?>
<script>
window.dataLayer = window.dataLayer || [];
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo esc_js( $id ); ?>');
</script>
	<?php
}
add_action( 'wp_head', 'spc_gtm_head', 1 );

/**
 * <body> 直後の noscript（JSが無効な環境向け）。
 */
function spc_gtm_body(): void {
	$id = spc_gtm_id();
	if ( '' === $id ) {
		return;
	}
	?>
<noscript><iframe src="<?php echo esc_url( 'https://www.googletagmanager.com/ns.html?id=' . $id ); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<?php
}
add_action( 'wp_body_open', 'spc_gtm_body' );
