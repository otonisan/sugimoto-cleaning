<?php
// wp-env の tests 環境が WP_TESTS_DIR を提供する。
$spc_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $spc_tests_dir || ! file_exists( $spc_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "WP_TESTS_DIR が見つかりません。\n" );
	exit( 1 );
}
require_once dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
require_once $spc_tests_dir . '/includes/functions.php';

// mu-plugin ローダーを読み込む。
tests_add_filter( 'muplugins_loaded', static function () {
	require_once dirname( __DIR__, 2 ) . '/spc-core.php';
} );

require $spc_tests_dir . '/includes/bootstrap.php';
