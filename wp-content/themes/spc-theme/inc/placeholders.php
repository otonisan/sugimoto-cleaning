<?php
/**
 * テンプレートに直接置いている写真（仮枠）の一覧と、公開前チェックへの登録。
 * 一覧はここが唯一の定義。テンプレートは spc_theme_image_id() で画像IDを受け取り、
 * 画像がない間は仮枠を出す。同じ一覧を spc-core の公開前チェック（wp spc launch-check・サイトヘルス）にも出す。
 */

defined( 'ABSPATH' ) || exit;

/**
 * テンプレートに直接置く写真の一覧。
 * attachment_id が 0、または画像でない間は「仮」の扱い（仮枠を出し、公開前チェックにも出る）。
 *
 * @return array<string,array{attachment_id:int,label:string,file:string}>
 */
function spc_theme_placeholder_images(): array {
	// TODO(仮): 実写を用意したら、メディアに登録した画像の添付ファイルIDを attachment_id に入れる（DESIGN.md §5）。
	$images = array(
		'hero'           => array(
			'attachment_id' => 0,
			'label'         => __( 'トップ画像（繊維マクロ写真）', 'spc' ),
			'file'          => 'template-parts/lp/hero.php',
		),
		'menu_stain'     => array(
			'attachment_id' => 0,
			'label'         => __( '対応メニュー「特殊シミ抜き」の写真', 'spc' ),
			'file'          => 'template-parts/lp/menu.php',
		),
		'menu_yellowing' => array(
			'attachment_id' => 0,
			'label'         => __( '対応メニュー「黄ばみ・汗汚れ」の写真', 'spc' ),
			'file'          => 'template-parts/lp/menu.php',
		),
		'menu_down'      => array(
			'attachment_id' => 0,
			'label'         => __( '対応メニュー「高級ダウン」の写真', 'spc' ),
			'file'          => 'template-parts/lp/menu.php',
		),
		'menu_coat'      => array(
			'attachment_id' => 0,
			'label'         => __( '対応メニュー「コート・ジャケット」の写真', 'spc' ),
			'file'          => 'template-parts/lp/menu.php',
		),
		'workshop'       => array(
			'attachment_id' => 0,
			'label'         => __( '職人と施工母体の作業風景の写真', 'spc' ),
			'file'          => 'template-parts/lp/craftsmen.php',
		),
	);

	/**
	 * テンプレートの写真一覧を差し替える（子テーマ・テスト用）。
	 *
	 * @param array<string,array{attachment_id:int,label:string,file:string}> $images 写真の一覧。
	 */
	return (array) apply_filters( 'spc_theme_placeholder_images', $images );
}

/**
 * 一覧のキーから画像の添付ファイルIDを返す（未設定・未知のキーは 0 ＝ 仮枠）。
 */
function spc_theme_image_id( string $key ): int {
	$images = spc_theme_placeholder_images();
	return isset( $images[ $key ]['attachment_id'] ) ? absint( $images[ $key ]['attachment_id'] ) : 0;
}

/**
 * 公開前チェック（spc-core のフィルタ spc_placeholder_report）に、仮枠のままの写真を足す。
 * 管理画面では直せない（テーマのファイルを直す）ため、edit_url は空にする。
 *
 * @param mixed $items 公開前チェックの項目。
 * @return array<int,array{type:string,label:string,edit_url:string}>
 */
function spc_theme_placeholder_report( $items ): array {
	$items = is_array( $items ) ? $items : array();
	foreach ( spc_theme_placeholder_images() as $image ) {
		$id = isset( $image['attachment_id'] ) ? absint( $image['attachment_id'] ) : 0;
		if ( $id > 0 && wp_attachment_is_image( $id ) ) {
			continue;
		}
		$items[] = array(
			'type'     => 'theme_image',
			/* translators: 1: 写真の説明 2: テーマのファイル */
			'label'    => sprintf( __( '%1$sがまだ仮の枠です（制作側でテーマを差し替え：%2$s）', 'spc' ), (string) $image['label'], (string) $image['file'] ),
			'edit_url' => '',
		);
	}
	return $items;
}
add_filter( 'spc_placeholder_report', 'spc_theme_placeholder_report' );
