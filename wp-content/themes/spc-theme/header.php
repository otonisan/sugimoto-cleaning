<?php
/**
 * 共通ヘッダー：ロゴ（ブランド名）・ナビ・小さいCTA。
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="spc-skip-link" href="#spc-main"><?php esc_html_e( '本文へ移動', 'spc' ); ?></a>
<header class="spc-header">
	<div class="spc-container spc-header__inner">
		<a class="spc-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( spc_brand_name() ); ?></a>
		<nav class="spc-header__nav spc-nav" aria-label="<?php esc_attr_e( 'メインメニュー', 'spc' ); ?>">
			<?php spc_nav_menu( 'primary' ); ?>
		</nav>
		<div class="spc-header__cta">
			<?php spc_cta( 'header', 'compact' ); ?>
		</div>
	</div>
</header>
<main id="spc-main" class="spc-main">
