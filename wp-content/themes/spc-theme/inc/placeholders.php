<?php
/**
 * テンプレートに直接置いている写真（仮枠）と仮の文章の一覧と、公開前チェックへの登録。
 * 一覧はここが唯一の定義。テンプレートは spc_theme_image_id() で画像IDを受け取り、
 * 画像がない間は仮枠を出す。同じ一覧を spc-core の公開前チェック（wp spc launch-check・サイトヘルス）にも出す。
 * 仮の文章は、テンプレートの「TODO(仮)」コメントの末尾に [copy:キー] を付け、spc_theme_placeholder_copy() に登録する。
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
		'stain_work'     => array(
			'attachment_id' => 0,
			'label'         => __( '特殊シミ抜き「手元・作業」の写真', 'spc' ),
			'file'          => 'page-stain-removal.php',
		),
		'stain_yellowing' => array(
			'attachment_id' => 0,
			'label'         => __( '特殊シミ抜き「黄ばみ・汗汚れ」の写真', 'spc' ),
			'file'          => 'page-stain-removal.php',
		),
		'down_detail'    => array(
			'attachment_id' => 0,
			'label'         => __( '高級ダウン「表地・縫い目の接写」の写真', 'spc' ),
			'file'          => 'page-down-coat.php',
		),
		'coat_detail'    => array(
			'attachment_id' => 0,
			'label'         => __( 'コート・ジャケット「生地・ボタンまわり」の写真', 'spc' ),
			'file'          => 'page-down-coat.php',
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

/**
 * テンプレートに直接書いている仮の文章の一覧（キー => 説明・ファイル・確定済みか）。
 * キーはテンプレートの「TODO(仮)」コメント末尾の [copy:キー] と対応する（同じファイルの複数か所は1キー）。
 * 依頼元の確認が済んだら、文章を差し替えて confirmed を true にする（コメントも消してよい）。
 *
 * @return array<string,array{label:string,file:string,confirmed:bool}>
 */
function spc_theme_placeholder_copy(): array {
	$copy = array(
		'lp.concerns'      => array(
			'label'     => __( 'トップ「悩みと、当店の考え方」', 'spc' ),
			'file'      => 'template-parts/lp/concerns.php',
			'confirmed' => false,
		),
		'lp.steps'         => array(
			'label'     => __( 'トップ「写真相談の3ステップ」', 'spc' ),
			'file'      => 'template-parts/lp/steps.php',
			'confirmed' => false,
		),
		'lp.menu'          => array(
			'label'     => __( 'トップ「対応メニュー」', 'spc' ),
			'file'      => 'template-parts/lp/menu.php',
			'confirmed' => false,
		),
		'lp.cases'         => array(
			'label'     => __( '施工事例のセクション（導入文）', 'spc' ),
			'file'      => 'template-parts/sections/cases.php',
			'confirmed' => false,
		),
		'lp.craftsmen'     => array(
			'label'     => __( 'トップ「職人と施工母体」', 'spc' ),
			'file'      => 'template-parts/lp/craftsmen.php',
			'confirmed' => false,
		),
		'lp.price-steps'   => array(
			'label'     => __( '料金の仕組み（3段階の説明）', 'spc' ),
			'file'      => 'inc/template-tags.php',
			'confirmed' => false,
		),
		'lp.flow'          => array(
			'label'     => __( '「全国宅配の流れ」', 'spc' ),
			'file'      => 'template-parts/sections/flow.php',
			'confirmed' => false,
		),
		'lp.limits'        => array(
			'label'     => __( '「お引き受けできない場合」', 'spc' ),
			'file'      => 'template-parts/sections/limits.php',
			'confirmed' => false,
		),
		'lp.final-cta'     => array(
			'label'     => __( 'トップ「最終CTA」', 'spc' ),
			'file'      => 'template-parts/lp/final-cta.php',
			'confirmed' => false,
		),
		'common.cta-block' => array(
			'label'     => __( 'ページ下部の相談ブロック', 'spc' ),
			'file'      => 'inc/template-tags.php',
			'confirmed' => false,
		),
		'stain.lead'       => array(
			'label'     => __( '特殊シミ抜き「リード文」', 'spc' ),
			'file'      => 'page-stain-removal.php',
			'confirmed' => false,
		),
		'stain.examples'   => array(
			'label'     => __( '特殊シミ抜き「相談できるシミの例」', 'spc' ),
			'file'      => 'page-stain-removal.php',
			'confirmed' => false,
		),
		'stain.factors'    => array(
			'label'     => __( '特殊シミ抜き「落ちやすさを左右するもの」', 'spc' ),
			'file'      => 'page-stain-removal.php',
			'confirmed' => false,
		),
		'stain.schema'     => array(
			'label'     => __( '特殊シミ抜き「構造化データの説明文」', 'spc' ),
			'file'      => 'inc/schema.php',
			'confirmed' => false,
		),
		'stain.yellowing'  => array(
			'label'     => __( '特殊シミ抜き「黄ばみ・汗汚れ」', 'spc' ),
			'file'      => 'page-stain-removal.php',
			'confirmed' => false,
		),
		'down.lead' => array(
			'label'     => __( '高級ダウン・コート「リード文」', 'spc' ),
			'file'      => 'page-down-coat.php',
			'confirmed' => false,
		),
		'down.checkpoints' => array(
			'label'     => __( '高級ダウン・コート「品目ごとの確認点」', 'spc' ),
			'file'      => 'page-down-coat.php',
			'confirmed' => false,
		),
		'down.down' => array(
			'label'     => __( '高級ダウン・コート「高級ダウン」', 'spc' ),
			'file'      => 'page-down-coat.php',
			'confirmed' => false,
		),
		'down.coat' => array(
			'label'     => __( '高級ダウン・コート「コート・ジャケット」', 'spc' ),
			'file'      => 'page-down-coat.php',
			'confirmed' => false,
		),
		'down.before-sending' => array(
			'label'     => __( '高級ダウン・コート「お送りいただく前に」', 'spc' ),
			'file'      => 'page-down-coat.php',
			'confirmed' => false,
		),
		'down.schema' => array(
			'label'     => __( '高級ダウン・コート「構造化データの説明文」', 'spc' ),
			'file'      => 'inc/schema.php',
			'confirmed' => false,
		),
		'price.lead' => array(
			'label'     => __( '料金・送料「リード文」', 'spc' ),
			'file'      => 'page-price.php',
			'confirmed' => false,
		),
		'price.shipping-note' => array(
			'label'     => __( '料金・送料「送料・手数料の注記」', 'spc' ),
			'file'      => 'page-price.php',
			'confirmed' => false,
		),
		'price.payment' => array(
			'label'     => __( '料金・送料「お支払いとキャンセル」', 'spc' ),
			'file'      => 'page-price.php',
			'confirmed' => false,
		),
	);

	/**
	 * 仮の文章の一覧を差し替える（子テーマ・テスト用）。
	 *
	 * @param array<string,array{label:string,file:string,confirmed:bool}> $copy 仮の文章の一覧。
	 */
	return (array) apply_filters( 'spc_theme_placeholder_copy', $copy );
}

/**
 * 公開前チェック（spc-core のフィルタ spc_placeholder_report）に、仮のままの文章を足す。
 * 管理画面では直せない（テーマのファイルを直す）ため、edit_url は空にする。
 *
 * @param mixed $items 公開前チェックの項目。
 * @return array<int,array{type:string,label:string,edit_url:string}>
 */
function spc_theme_placeholder_copy_report( $items ): array {
	$items = is_array( $items ) ? $items : array();
	foreach ( spc_theme_placeholder_copy() as $copy ) {
		if ( ! empty( $copy['confirmed'] ) ) {
			continue;
		}
		$items[] = array(
			'type'     => 'theme_copy',
			/* translators: 1: 文章の場所 2: テーマのファイル */
			'label'    => sprintf( __( '%1$sの文章が仮のままです（制作側でテーマを差し替え：%2$s）', 'spc' ), (string) $copy['label'], (string) $copy['file'] ),
			'edit_url' => '',
		);
	}
	return $items;
}
add_filter( 'spc_placeholder_report', 'spc_theme_placeholder_copy_report' );
