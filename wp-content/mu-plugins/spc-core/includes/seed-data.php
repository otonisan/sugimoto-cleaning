<?php
/**
 * 開発用の仮データ（wp spc seed で投入）。
 * すべて「（仮）」付き・仮データフラグ付きで、公開前チェックに必ず引っかかる。
 */

defined( 'ABSPATH' ) || exit;

/**
 * 仮データ本体。_spc_seed_key で重複投入を防ぐため、key は変更しないこと。
 *
 * @return array<int,array<string,mixed>> 各要素: key, post_type, post_title, post_content, menu_order, post_name, parent_key, meta
 */
function spc_seed_definitions(): array {
	// TODO(仮): 公開前に差し替え（以下の仮データはすべて本番では使わない）
	$defs = array();

	// 施工事例（画像なし。テーマ側で仮枠を表示する）。
	$defs[] = array(
		'key'        => 'case-001',
		'post_type'  => 'spc_case',
		'post_title' => '（仮）ウールコートの赤ワインのシミ',
		'meta'       => array(
			'spc_case_no'         => '仮-001',
			'spc_case_menu'       => 'stain',
			'spc_item'            => 'ウールコート（仮）',
			'spc_material'        => 'ウール（仮）',
			'spc_stain'           => '赤ワイン（仮）',
			'spc_elapsed'         => '付着から約3か月（仮）',
			'spc_prior_treatment' => '他店で処理あり。落ちずに返却（仮）',
			'spc_treatment'       => '生地の状態を確認し、部分的に手作業でシミ抜き（仮）',
			'spc_result'          => '薄く残る（目立たない程度）（仮）',
			'spc_days'            => '10',
		),
	);
	$defs[] = array(
		'key'        => 'case-002',
		'post_type'  => 'spc_case',
		'post_title' => '（仮）ダウンジャケットの襟の汚れ',
		'meta'       => array(
			'spc_case_no'         => '仮-002',
			'spc_case_menu'       => 'down',
			'spc_item'            => 'ダウンジャケット（仮）',
			'spc_material'        => 'ナイロン（仮）',
			'spc_stain'           => 'ファンデーション・皮脂汚れ（襟）（仮）',
			'spc_elapsed'         => '約1シーズン（仮）',
			'spc_prior_treatment' => '処置なし（仮）',
			'spc_treatment'       => '襟まわりを部分処理したあと、全体を洗浄（仮）',
			'spc_result'          => '目立たなくなった（仮）',
			'spc_days'            => '14',
		),
	);
	$defs[] = array(
		'key'        => 'case-003',
		'post_type'  => 'spc_case',
		'post_title' => '（仮）シルクのブラウスの黄ばみ',
		'meta'       => array(
			'spc_case_no'         => '仮-003',
			'spc_case_menu'       => 'yellowing',
			'spc_item'            => 'シルクのブラウス（仮）',
			'spc_material'        => 'シルク（仮）',
			'spc_stain'           => '黄ばみ（汗）（仮）',
			'spc_elapsed'         => '約2年（仮）',
			'spc_prior_treatment' => '処置なし（仮）',
			'spc_treatment'       => '生地を傷めない方法で、時間をかけて黄ばみを処理（仮）',
			'spc_result'          => '大部分は改善。わきの一部に薄く残る（仮）',
			'spc_days'            => '12',
		),
	);

	// TODO(仮): 公開前に差し替え（料金・金額は未確定）
	$prices = array(
		array( 'price-stain', '特殊シミ抜き', 'stain', 5000, '1', '状態により変動します（仮）', 1 ),
		array( 'price-yellowing', '黄ばみ・汗汚れ', 'yellowing', 3000, '1', '状態により変動します（仮）', 2 ),
		array( 'price-down', '高級ダウン', 'down', 15000, '1', '状態により変動します（仮）', 3 ),
		array( 'price-coat', 'コート・ジャケット', 'coat', 12000, '1', '状態により変動します（仮）', 4 ),
		array( 'price-shipping', '往復送料', 'shipping', 2000, '1', '地域により異なります（仮）', 5 ),
		array( 'price-diagnosis', '診断料', 'shipping', 0, '0', '金額は確定後に差し替えます（仮）', 6, '無料（仮）' ),
		array( 'price-return', '未承認時の返送料', 'shipping', 0, '0', '金額は確定後に差し替えます（仮）', 7, '実費（仮）' ),
	);
	foreach ( $prices as $p ) {
		$defs[] = array(
			'key'        => $p[0],
			'post_type'  => 'spc_price',
			'post_title' => '（仮）' . $p[1],
			'menu_order' => $p[6],
			'meta'       => array(
				'spc_price_group' => $p[2],
				'spc_amount'      => (string) $p[3],
				'spc_from'        => $p[4],
				'spc_note'        => $p[5],
			) + ( isset( $p[7] ) ? array( 'spc_display_text' => $p[7] ) : array() ),
		);
	}

	// よくある質問（最初の5件はトップに表示。下層ページ用の4件はトップに出さない）。
	$faqs = array(
		array( 'faq-shipping', 'shipping', '全国どこからでも送れますか。', '全国からお送りいただけます。往復の送料は、お送りいただく前にお見せします。離島など一部の地域は、あらためて確認します（仮）' ),
		array( 'faq-days', 'other', '仕上がりまでどのくらいかかりますか。', 'シミの種類や素材によって異なります。目安の日数は、正式なお見積りのときにお伝えします（仮）' ),
		array( 'faq-payment', 'payment', '支払い方法は何がありますか。', '現在、決めているところです。決まりしだい、こちらに記載します（仮）' ),
		array( 'faq-photo', 'photo', '送った写真はどのように扱われますか。', 'ご相談への回答のためだけに使います。お客様の許可なく公開することはありません。一定の期間がすぎたあと、削除します（仮）' ),
		array( 'faq-decline', 'other', 'お引き受けできないこともありますか。', 'あります。生地を傷めるおそれが大きい場合などは、理由をご説明してお断りすることがあります。すでに品物をお送りいただいていた場合は、お返しします。返送料は料金ページをご確認ください（仮）' ),
	);
	// 下層ページ（特殊シミ抜き・高級ダウン/コート）用。可否は断定せず、写真を見てから伝える。
	$section_faqs = array(
		array( 'faq-stain-1', 'stain', '時間がたったシミも相談できますか。', 'ご相談いただけます。写真を拝見してから、可能かどうかをお伝えします（仮）' ),
		array( 'faq-stain-2', 'stain', '自分で処理してしまったシミも相談できますか。', 'ご相談いただけます。どのような処理をされたかも、写真と一緒にお知らせください。写真を拝見してから、可能かどうかをお伝えします（仮）' ),
		array( 'faq-down-1', 'down', 'ファーや飾りが付いたダウンも出せますか。', 'まずは写真をお送りください。付属品の状態を拝見してから、可能かどうかをお伝えします（仮）' ),
		array( 'faq-down-2', 'down', '洗濯表示のタグを切ってしまった服も出せますか。', 'まずは写真をお送りください。素材や状態を拝見してから、可能かどうかをお伝えします（仮）' ),
	);
	foreach ( $faqs as $i => $f ) {
		$defs[] = array(
			'key'          => $f[0],
			'post_type'    => 'spc_faq',
			'post_title'   => '（仮）' . $f[2],
			'post_content' => $f[3],
			'menu_order'   => $i + 1,
			'meta'         => array(
				'spc_faq_group'   => $f[1],
				'spc_show_on_top' => '1',
			),
		);
	}
	foreach ( $section_faqs as $i => $f ) {
		$defs[] = array(
			'key'          => $f[0],
			'post_type'    => 'spc_faq',
			'post_title'   => '（仮）' . $f[2],
			'post_content' => $f[3],
			'menu_order'   => count( $faqs ) + $i + 1,
			'meta'         => array(
				'spc_faq_group'   => $f[1],
				'spc_show_on_top' => '0',
			),
		);
	}

	// 職人（顔写真なし）。
	foreach ( array( 1 => '職人名（仮）', 2 => '職人名（仮）2' ) as $n => $name ) {
		$defs[] = array(
			'key'        => 'staff-' . $n,
			'post_type'  => 'spc_staff',
			'post_title' => $name,
			'menu_order' => $n,
			'meta'       => array(
				'spc_role' => '役職（仮）',
				'spc_bio'  => '経歴（仮）：公開前に差し替えてください。',
			),
		);
	}

	// 固定ページ（親を先に並べる）。
	$pages = array(
		array( 'stain-removal', '特殊シミ抜き', '' ),
		array( 'down-coat', '高級ダウン・コート', '' ),
		array( 'price', '料金・送料', '' ),
		array( 'about', '職人・運営会社', '' ),
		array( 'guide', 'ご利用の流れ・よくある質問', '' ),
		array( 'legal', '規約・法定表示', '' ),
		array( 'terms', '利用規約', 'legal' ),
		array( 'tokushoho', '特定商取引法に基づく表記', 'legal' ),
		array( 'privacy', '個人情報・相談写真の取扱い', 'legal' ),
	);
	$page_contents = spc_seed_page_contents();
	$child_orders  = array(
		'terms'     => 1,
		'tokushoho' => 2,
		'privacy'   => 3,
	);
	foreach ( $pages as $pg ) {
		$defs[] = array(
			'key'          => 'page-' . $pg[0],
			'post_type'    => 'page',
			'post_title'   => $pg[1],
			'post_name'    => $pg[0],
			'post_content' => $page_contents[ 'page-' . $pg[0] ] ?? '',
			'menu_order'   => $child_orders[ $pg[0] ] ?? 0,
			'parent_key'   => '' === $pg[2] ? '' : 'page-' . $pg[2],
		);
	}

	return $defs;
}

/**
 * _spc_seed_key から投稿IDを探す（ゴミ箱も含める）。
 */
function spc_seed_find( string $key ): int {
	$ids = get_posts(
		array(
			'post_type'      => array_merge( spc_post_types(), array( 'page' ) ),
			'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_spc_seed_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * 既存の仮データに、あとから増えた定義を足す。仮データのまま（_spc_is_placeholder=1）の投稿だけが対象。
 * - メタ：投稿にまったく値が無いキーだけを追加する（上書きしない）。
 * - 本文：旧シードの値『準備中（仮）』と完全に一致するときだけ新しい本文に置き換える。
 *
 * @param int                 $id  投稿ID。
 * @param array<string,mixed> $def 仮データの定義。
 * @return bool 変更したか。
 */
function spc_seed_backfill( int $id, array $def ): bool {
	if ( ! spc_is_placeholder( $id ) ) {
		return false;
	}
	$changed = false;

	foreach ( $def['meta'] ?? array() as $key => $value ) {
		if ( ! metadata_exists( 'post', $id, $key ) ) {
			update_post_meta( $id, $key, $value );
			$changed = true;
		}
	}

	$new_content = (string) ( $def['post_content'] ?? '' );
	if ( '準備中（仮）' === get_post_field( 'post_content', $id ) && $new_content !== '準備中（仮）' ) {
		$updated = wp_update_post(
			array(
				'ID'           => $id,
				'post_content' => wp_slash( $new_content ),
			)
		);
		$changed = $changed || ( $updated && ! is_wp_error( $updated ) );
	}

	return $changed;
}

/**
 * 仮データを投入する。何度実行しても同じ結果になる。
 * 作成済みの仮データには、あとから増えたメタ・旧シードの本文だけを足す（updated は変更した投稿の数）。
 *
 * @return array{created:int, skipped:int, updated:int}
 */
function spc_seed_run(): array {
	$created = 0;
	$skipped = 0;
	$updated = 0;

	foreach ( spc_seed_definitions() as $def ) {
		$existing = spc_seed_find( $def['key'] );
		if ( $existing ) {
			++$skipped;
			if ( spc_seed_backfill( $existing, $def ) ) {
				++$updated;
			}
			continue;
		}
		$post = array(
			'post_type'    => $def['post_type'],
			'post_status'  => 'publish',
			'post_title'   => $def['post_title'],
			'post_content' => $def['post_content'] ?? '',
			'menu_order'   => $def['menu_order'] ?? 0,
			'meta_input'   => ( $def['meta'] ?? array() ) + array(
				'_spc_is_placeholder' => '1',
				'_spc_seed_key'       => $def['key'],
			),
		);
		if ( ! empty( $def['post_name'] ) ) {
			$post['post_name'] = $def['post_name'];
		}
		if ( ! empty( $def['parent_key'] ) ) {
			$post['post_parent'] = spc_seed_find( $def['parent_key'] );
		}
		$id = wp_insert_post( $post, true );
		if ( is_wp_error( $id ) ) {
			continue;
		}
		++$created;
	}

	// /cases/ などのURLを有効にする。
	flush_rewrite_rules( false );

	return array(
		'created' => $created,
		'skipped' => $skipped,
		'updated' => $updated,
	);
}
