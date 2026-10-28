<?php
/**
 * ご利用の流れ・よくある質問（/guide/）。固定ページ guide 専用。
 * 並びと地は DESIGN.md §4：ご利用の流れ #flow（白）→ 写真相談で送っていただくもの（灰白）
 * → 送り方・梱包（白）→ よくある質問 #faq（灰白・分類ごとの h3）→ お知らせ・補足 → 相談ブロック。
 * ページの途中にCTAは置かない。固定ページの本文は「お知らせ・補足」の欄に出す（依頼元が入力したときだけ）。
 * FAQ は全件。構造化データ（inc/schema.php の FAQPage）も同じクエリを使う。
 * ブランド名・ロゴは文章にも写真枠のラベルにも書かない。
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$spc_post_id = (int) get_the_ID();

	// TODO(仮): 文言は依頼元確認後に確定（リード文） [copy:guide.lead]
	$spc_lead = __( '写真でのご相談から、お手元に戻るまでの流れです。', 'spc' );

	// TODO(仮): 文言は依頼元確認後に確定（写真相談で送っていただくもの） [copy:guide.photos]
	$spc_photos      = array(
		array( __( '全体', 'spc' ), __( '衣類全体の形や色がわかる写真をお送りください。', 'spc' ) ),
		array( __( 'シミの接写', 'spc' ), __( 'シミの大きさや色がわかるよう、近くから撮った写真をお送りください。', 'spc' ) ),
		array( __( '洗濯表示のタグ', 'spc' ), __( 'お手入れの方法を決める手がかりになります。タグ全体が読めるように撮ってください。', 'spc' ) ),
	);
	$spc_photos_note = __( '明るい場所で、ピントを合わせて撮っていただくと、状態を確認しやすくなります。', 'spc' );

	// TODO(仮): 文言は依頼元確認後に確定（送り方・梱包） [copy:guide.packing]
	$spc_packing = array(
		__( '衣類は、たたんで袋に入れてから、箱または丈夫な袋に入れてください', 'spc' ),
		__( 'ポケットの中は、空にしてください', 'spc' ),
		__( '洗濯表示のタグは、切らずに残してください', 'spc' ),
		__( '梱包に迷われたときは、写真相談のときにご相談ください', 'spc' ),
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
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'spc-page spc-page--guide' ); ?>>
		<?php
		get_template_part(
			'template-parts/sections/flow',
			null,
			array(
				'tone'       => 'paper',
				'id'         => 'flow',
				'heading'    => __( 'ご利用の流れ', 'spc' ),
				'heading_id' => 'spc-guide-flow-title',
			)
		);
		?>

		<section class="spc-section spc-section--mist spc-guide-photos" aria-labelledby="spc-guide-photos-title">
			<div class="spc-container spc-split">
				<div class="spc-split__head">
					<h2 id="spc-guide-photos-title"><?php esc_html_e( '写真相談で送っていただくもの', 'spc' ); ?></h2>
				</div>
				<div class="spc-split__body">
					<dl class="spc-subpage__defs">
						<?php foreach ( $spc_photos as $spc_photo ) : ?>
							<div class="spc-subpage__def">
								<dt><?php echo esc_html( $spc_photo[0] ); ?></dt>
								<dd><?php echo esc_html( $spc_photo[1] ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
					<p class="spc-subpage__note"><?php echo esc_html( $spc_photos_note ); ?></p>
				</div>
			</div>
		</section>

		<section class="spc-section spc-guide-packing" aria-labelledby="spc-guide-packing-title">
			<div class="spc-container spc-split">
				<div class="spc-split__head">
					<h2 id="spc-guide-packing-title"><?php esc_html_e( '送り方・梱包', 'spc' ); ?></h2>
				</div>
				<div class="spc-split__body">
					<ul class="spc-subpage__list" role="list">
						<?php foreach ( $spc_packing as $spc_item ) : ?>
							<li class="spc-subpage__item"><?php echo esc_html( $spc_item ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</section>
	</article>
	<?php
	// FAQ が0件のときは null（部品に渡すと既定のクエリに戻ってしまうため、呼ばない）。
	$spc_guide_faqs = spc_faq_query();
	if ( $spc_guide_faqs ) {
		get_template_part(
			'template-parts/sections/faq',
			null,
			array(
				'tone'       => 'mist',
				'id'         => 'faq',
				'heading_id' => 'spc-guide-faq-title',
				'query'      => $spc_guide_faqs,
				'grouped'    => true,
				'more_link'  => false,
			)
		);
	}

	get_template_part( 'template-parts/page-notes', null, array( 'post_id' => $spc_post_id ) );
endwhile;

spc_cta_block();

get_footer();
