<?php
/**
 * 高級ダウン・コート（/down-coat/）。固定ページ down-coat 専用。
 * 並びと地は DESIGN.md §4：品目ごとの確認点（白）→ 高級ダウン（灰白）→ コート・ジャケット #coat（白）
 * → お送りいただく前に（灰白）→ 料金の目安（白）→ 関連する施工事例（灰白）→ よくある質問（白）
 * → お知らせ・補足 → 相談ブロック。ページの途中にCTAは置かない。
 * 固定ページの本文は「お知らせ・補足」の欄に出す（依頼元が入力したときだけ）。
 * ブランド名・ロゴは文章にも写真枠のラベルにも書かない。
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$spc_post_id = (int) get_the_ID();

	// TODO(仮): 文言は依頼元確認後に確定（リード文） [copy:down.lead]
	$spc_lead = __( '素材・装飾・洗濯表示を確認してから、お手入れの方法をご提案します。', 'spc' );

	// TODO(仮): 文言は依頼元確認後に確定（品目ごとの確認点） [copy:down.checkpoints]
	$spc_checkpoints = array(
		array( __( '素材（表地・中わた・裏地）', 'spc' ), __( '表地、中わた、裏地それぞれの素材を見て、使える方法を考えます。', 'spc' ) ),
		array( __( '装飾（ファー・金具・ワッペン・ボタン）', 'spc' ), __( '取り外せるか、洗えるか、傷みやすい箇所がないかを確認します。', 'spc' ) ),
		array( __( '洗濯表示', 'spc' ), __( '表示は、お手入れの方法を決める大切な手がかりです。タグは切らずにお送りください。', 'spc' ) ),
	);

	// TODO(仮): 文言は依頼元確認後に確定（高級ダウン） [copy:down.down]
	$spc_down_text  = array(
		__( 'ダウンは、表地の素材や縫い目の状態、中わたの傾きなどによって、向いているお手入れが変わります。', 'spc' ),
		__( 'まずは写真を送っていただき、拝見したうえでお伝えします。', 'spc' ),
	);
	$spc_down_check = array(
		__( '表地の素材と、ほつれや傷みの有無', 'spc' ),
		__( '縫い目まわりの状態', 'spc' ),
		__( '中わたの偏りやへたり', 'spc' ),
		__( '洗濯表示の内容', 'spc' ),
	);

	// TODO(仮): 文言は依頼元確認後に確定（コート・ジャケット） [copy:down.coat]
	$spc_coat_text = array(
		__( 'コートやジャケットは、表地だけでなく、裏地や芯地、ボタンなどの装飾も含めて確認します。', 'spc' ),
		__( '素材や状態によって使える方法が変わるため、写真を拝見してからお伝えします。気になる箇所があれば、あわせてお知らせください。', 'spc' ),
	);

	// TODO(仮): 文言は依頼元確認後に確定（お送りいただく前に） [copy:down.before-sending]
	$spc_before = array(
		__( 'ポケットの中を空にしてください', 'spc' ),
		__( '取り外せる付属品は、事前にご相談ください', 'spc' ),
		__( '洗濯表示のタグは、切らずに残してください', 'spc' ),
		__( '気になる箇所は、写真相談のときにお知らせください', 'spc' ),
	);

	get_template_part(
		'template-parts/page-header',
		null,
		array(
			'title'   => get_the_title(),
			'lead'    => $spc_lead,
			'post_id' => $spc_post_id,
		)
	);
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'spc-page spc-page--down' ); ?>>
		<section class="spc-section spc-down-checkpoints" aria-labelledby="spc-down-checkpoints-title">
			<div class="spc-container spc-split">
				<div class="spc-split__head">
					<h2 id="spc-down-checkpoints-title"><?php esc_html_e( '品目ごとの確認点', 'spc' ); ?></h2>
				</div>
				<div class="spc-split__body">
					<dl class="spc-down-checkpoints__list">
						<?php foreach ( $spc_checkpoints as $spc_checkpoint ) : ?>
							<div class="spc-down-checkpoints__item">
								<dt><?php echo esc_html( $spc_checkpoint[0] ); ?></dt>
								<dd><?php echo esc_html( $spc_checkpoint[1] ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				</div>
			</div>
		</section>

		<section class="spc-section spc-section--mist spc-down-down" aria-labelledby="spc-down-down-title">
			<div class="spc-container spc-split">
				<div class="spc-split__head">
					<h2 id="spc-down-down-title"><?php esc_html_e( '高級ダウン', 'spc' ); ?></h2>
				</div>
				<div class="spc-split__body">
					<div class="spc-down__text">
						<?php foreach ( $spc_down_text as $spc_paragraph ) : ?>
							<p><?php echo esc_html( $spc_paragraph ); ?></p>
						<?php endforeach; ?>
					</div>
					<ul class="spc-down-list" role="list">
						<?php foreach ( $spc_down_check as $spc_item ) : ?>
							<li class="spc-down-list__item"><?php echo esc_html( $spc_item ); ?></li>
						<?php endforeach; ?>
					</ul>
					<div class="spc-subpage__photo">
						<?php
						// TODO(仮): ダウンの表地・縫い目の接写（3:2）に差し替え。画像IDは inc/placeholders.php の 'down_detail' に入れる。
						spc_image_or_placeholder( spc_theme_image_id( 'down_detail' ), 'spc-menu', 'ダウンの表地・縫い目の接写 3:2', '3/2' );
						?>
					</div>
				</div>
			</div>
		</section>

		<section id="coat" class="spc-section spc-down-coat" aria-labelledby="spc-down-coat-title">
			<div class="spc-container spc-split">
				<div class="spc-split__head">
					<h2 id="spc-down-coat-title"><?php esc_html_e( 'コート・ジャケット', 'spc' ); ?></h2>
				</div>
				<div class="spc-split__body">
					<div class="spc-down__text">
						<?php foreach ( $spc_coat_text as $spc_paragraph ) : ?>
							<p><?php echo esc_html( $spc_paragraph ); ?></p>
						<?php endforeach; ?>
					</div>
					<div class="spc-subpage__photo">
						<?php
						// TODO(仮): コートの生地・ボタンまわり（3:2）に差し替え。画像IDは inc/placeholders.php の 'coat_detail' に入れる。
						spc_image_or_placeholder( spc_theme_image_id( 'coat_detail' ), 'spc-menu', 'コートの生地・ボタンまわり 3:2', '3/2' );
						?>
					</div>
				</div>
			</div>
		</section>

		<section class="spc-section spc-section--mist spc-down-before" aria-labelledby="spc-down-before-title">
			<div class="spc-container spc-split">
				<div class="spc-split__head">
					<h2 id="spc-down-before-title"><?php esc_html_e( 'お送りいただく前に', 'spc' ); ?></h2>
				</div>
				<div class="spc-split__body">
					<ul class="spc-down-list" role="list">
						<?php foreach ( $spc_before as $spc_item ) : ?>
							<li class="spc-down-list__item"><?php echo esc_html( $spc_item ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</section>
	</article>
	<?php
	get_template_part(
		'template-parts/sections/price',
		null,
		array(
			'tone'       => 'paper',
			'heading_id' => 'spc-down-price-title',
			'groups'     => array( 'down', 'coat' ),
			'show_steps' => false,
		)
	);

	// 該当する事例・FAQ が0件のときは null（部品に渡すと既定のクエリに戻ってしまうため、呼ばない）。
	$spc_related_cases = spc_lp_query(
		'spc_case',
		array(
			'posts_per_page' => 3,
			'meta_query'     => array(
				array(
					'key'     => 'spc_case_menu',
					'value'   => array( 'down', 'coat' ),
					'compare' => 'IN',
				),
			),
		)
	);
	if ( $spc_related_cases ) {
		get_template_part(
			'template-parts/sections/cases',
			null,
			array(
				'tone'       => 'mist',
				'heading'    => __( '関連する施工事例', 'spc' ),
				'heading_id' => 'spc-down-cases-title',
				'show_cta'   => false,
				'query'      => $spc_related_cases,
			)
		);
	}

	$spc_down_faqs = spc_faq_query( array( 'down' ) );
	if ( $spc_down_faqs ) {
		get_template_part(
			'template-parts/sections/faq',
			null,
			array(
				'tone'       => 'paper',
				'id'         => 'faq',
				'heading_id' => 'spc-down-faq-title',
				'query'      => $spc_down_faqs,
			)
		);
	}

	get_template_part( 'template-parts/page-notes', null, array( 'post_id' => $spc_post_id ) );
endwhile;

spc_cta_block();

get_footer();
