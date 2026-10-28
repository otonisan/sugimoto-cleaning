<?php
/**
 * テーマの読み込み口。機能は inc/ に分ける。
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'inc/setup.php' );
require_once get_theme_file_path( 'inc/enqueue.php' );
require_once get_theme_file_path( 'inc/template-tags.php' );
require_once get_theme_file_path( 'inc/breadcrumbs.php' );
require_once get_theme_file_path( 'inc/placeholders.php' );
require_once get_theme_file_path( 'inc/schema.php' );
require_once get_theme_file_path( 'inc/gtm.php' );
