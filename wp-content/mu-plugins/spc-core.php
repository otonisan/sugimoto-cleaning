<?php
/**
 * Plugin Name: SPC Core
 * Description: 高級染み抜きブランドのコア機能（設定・CPT・WP-CLI など）。
 * Version: 0.1.0
 * Text Domain: spc
 */

defined( 'ABSPATH' ) || exit;

// spc-core/ ディレクトリの絶対パス。
if ( ! defined( 'SPC_CORE_DIR' ) ) {
	define( 'SPC_CORE_DIR', __DIR__ . '/spc-core' );
}

/**
 * コアが読み込まれたかの確認用。
 */
function spc_core_loaded(): bool {
	return true;
}

// includes/*.php を名前順に読み込む。
foreach ( glob( SPC_CORE_DIR . '/includes/*.php' ) ?: array() as $spc_include ) {
	require_once $spc_include;
}
unset( $spc_include );
