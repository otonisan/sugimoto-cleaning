<?php
/**
 * 固定ページの仮の本文（会社概要の表と、規約・特商法・個人情報の骨子）。
 * 条文は書かない。値はすべて「（仮）」で、専門家の確認後に差し替える。
 */

defined( 'ABSPATH' ) || exit;

/**
 * 見出しだけの骨子（h2 ＋ 仮の一文）を作る。
 *
 * @param string[] $headings 見出し。
 */
function spc_seed_skeleton( array $headings ): string {
	$html = '';
	foreach ( $headings as $heading ) {
		$html .= '<h2>' . esc_html( $heading ) . "</h2>\n<p>（仮）本文は専門家の確認後に掲載します。</p>\n";
	}
	return $html;
}

/**
 * 2列の表（項目名・値）を作る。
 *
 * @param array<string,string> $rows 項目名 => 値。
 */
function spc_seed_table( array $rows ): string {
	$html = "<table>\n<tbody>\n";
	foreach ( $rows as $label => $value ) {
		$html .= '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . "</td></tr>\n";
	}
	return $html . "</tbody>\n</table>\n";
}

/**
 * 仮の本文。シードキー => 本文HTML（ここに無いページの本文は空）。
 *
 * @return array<string,string>
 */
function spc_seed_page_contents(): array {
	// TODO(仮): 公開前に差し替え（会社情報・規約・特商法・個人情報はすべて未確定）
	return array(
		'page-about'     => spc_seed_table(
			array(
				'会社名'  => '有限会社杉本クリーニング商会',
				'創業'   => '1954年',
				'所在地'  => '（仮）',
				'代表者'  => '（仮）',
				'事業内容' => '（仮）',
				'連絡先'  => '（仮）',
			)
		),
		'page-terms'     => spc_seed_skeleton(
			array( '適用', 'サービスの内容', 'お見積りとご承認', '料金とお支払い', 'お預かり品の取扱い', 'お引き受けできない場合', '免責', 'キャンセル', 'お問い合わせ' )
		),
		'page-tokushoho' => spc_seed_table(
			array(
				'販売業者'         => '有限会社杉本クリーニング商会',
				'運営責任者'        => '（仮）',
				'所在地'          => '（仮）',
				'電話番号'         => '（仮）',
				'メールアドレス'      => '（仮）',
				'販売価格'         => '（仮）',
				'役務の対価以外に必要な料金' => '（仮）',
				'お支払い方法'       => '（仮）',
				'お支払いの時期'      => '（仮）',
				'役務の提供時期'      => '（仮）',
				'キャンセル'        => '（仮）',
				'お申し込みの撤回'     => '（仮）',
			)
		),
		'page-privacy'   => spc_seed_skeleton(
			array(
				'取得する情報（LINEでお送りいただく写真・メッセージ・表示名）',
				'利用目的',
				'相談写真の保管期間と削除',
				'第三者への提供',
				'施工事例への掲載（ご本人の許可がある場合だけ）',
				'アクセス解析（Googleアナリティクス・Googleタグマネージャー、Cookie）',
				'開示・訂正・削除のご請求',
				'お問い合わせ窓口',
				'改定',
			)
		),
	);
}
