<?php
/**
 * WP-CLI：wp spc seed / wp spc launch-check
 */

defined( 'ABSPATH' ) || exit;

/**
 * launch-check の表示用の行（種類・内容・編集URL）。
 * 種類は spc_placeholder_report() の type（post / line_url / line_qr / brand_name / gtm_id / reply_time_note / page_missing / theme_image など）。
 * 編集URLが空の行（theme_image）は管理画面では直せず、テーマのファイルを差し替える。
 *
 * @return array<int,array{種類:string,内容:string,編集URL:string}>
 */
function spc_launch_check_rows(): array {
	$rows = array();
	foreach ( spc_placeholder_report() as $item ) {
		$rows[] = array(
			'種類'   => $item['type'],
			'内容'   => $item['label'],
			'編集URL' => $item['edit_url'],
		);
	}
	return $rows;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * 仮データを投入する（本番では実行不可。何度実行しても重複しない）。
	 */
	function spc_cli_seed(): void {
		if ( spc_is_production() ) {
			WP_CLI::error( '本番環境では仮データを投入できません。' );
		}
		$result = spc_seed_run();
		WP_CLI::success( sprintf( '仮データを投入しました（作成 %d件、既存のためスキップ %d件、既存の仮データを更新 %d件）。', $result['created'], $result['skipped'], $result['updated'] ) );
	}

	/**
	 * 公開前チェック。仮データなどが残っていれば一覧を出して失敗（終了コード1）にする。
	 */
	function spc_cli_launch_check(): void {
		$rows = spc_launch_check_rows();
		if ( ! $rows ) {
			WP_CLI::success( '公開前チェックに合格しました。' );
			return;
		}
		WP_CLI\Utils\format_items( 'table', $rows, array( '種類', '内容', '編集URL' ) );
		WP_CLI::error( sprintf( '公開前チェックに失敗しました（%d件）。', count( $rows ) ) );
	}

	WP_CLI::add_command( 'spc seed', 'spc_cli_seed' );
	WP_CLI::add_command( 'spc launch-check', 'spc_cli_launch_check' );
}
