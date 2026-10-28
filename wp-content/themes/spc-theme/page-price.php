<?php
/**
 * 料金・送料（/price/）。固定ページ price 専用。
 * 並びと地は DESIGN.md §4：料金が決まるまで（白）→ 品目別の目安（灰白）→ 送料・手数料（白）
 * → お支払いとキャンセル（灰白）→ お知らせ・補足 → 相談ブロック。ページの途中にCTAは置かない。
 * 料金の表は sections/price（料金CPT）を使い、0件のときはそのセクションごと出さない。
 * 固定ページの本文は「お知らせ・補足」の欄に出す（依頼元が入力したときだけ）。
 * ブランド名・ロゴは文章に書かない。
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$spc_post_id = (int) get_the_ID();

	// TODO(仮): 文言は依頼元確認後に確定（リード文） [copy:price.lead]
	$spc_lead = __( '写真で目安をお伝えし、現物を拝見してから正式にお見積りします。', 'spc' );

	// TODO(仮): 文言は依頼元確認後に確定（送料・手数料の注記） [copy:price.shipping-note]
	$spc_shipping_note = __( '離島など一部の地域は、送料が異なる場合があります。', 'spc' );

	// 「送料・手数料」の表は料金が0件だと出ない。表が無いときは、表を指す一文を出さない。
	$spc_has_shipping = null !== spc_lp_query(
		'spc_price',
		array(
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'   => 'spc_price_group',
					'value' => 'shipping',
				),
			),
		)
	);

	// TODO(仮): 文言は依頼元確認後に確定（お支払いとキャンセル） [copy:price.payment]
	$spc_payment = array(
		array( __( 'お支払いの時期', 'spc' ), __( '正式なお見積りをご承認いただいたあとです。', 'spc' ) ),
		array( __( 'お支払い方法', 'spc' ), __( '決まりしだい掲載します。', 'spc' ) ),
		array( __( 'お見積りのあとにお断りいただいた場合', 'spc' ), __( '施工はせずにお返しします。', 'spc' ) . ( $spc_has_shipping ? __( '返送料は「送料・手数料」の表のとおりです。', 'spc' ) : __( '返送料は正式なお見積りのときにお伝えします。', 'spc' ) ) ),
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
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'spc-page spc-page--price' ); ?>>
		<section class="spc-section spc-price-page-steps" aria-labelledby="spc-price-page-steps-title">
			<div class="spc-container">
				<h2 id="spc-price-page-steps-title"><?php esc_html_e( '料金が決まるまで', 'spc' ); ?></h2>
				<?php get_template_part( 'template-parts/price-steps' ); ?>
			</div>
		</section>
	</article>
	<?php
	// 表の caption は見出しと同じ文字なので、読み上げ専用にする。0件のときは何も出ない。
	get_template_part(
		'template-parts/sections/price',
		null,
		array(
			'tone'           => 'mist',
			'heading'        => __( '品目別の目安', 'spc' ),
			'heading_id'     => 'spc-price-page-items-title',
			'extra_class'    => 'spc-price-page-items',
			'groups'         => array( 'stain', 'yellowing', 'down', 'coat' ),
			'show_steps'     => false,
			'more_link'      => false,
			'caption_hidden' => true,
		)
	);

	get_template_part(
		'template-parts/sections/price',
		null,
		array(
			'tone'            => 'paper',
			'heading'         => __( '送料・手数料', 'spc' ),
			'heading_id'      => 'spc-price-page-shipping-title',
			'extra_class'     => 'spc-price-page-shipping',
			'groups'          => array( 'shipping' ),
			'show_steps'      => false,
			'more_link'       => false,
			'show_disclaimer' => false,
			'caption_hidden'  => true,
			'footnote'        => $spc_shipping_note,
		)
	);
	?>
	<section class="spc-section spc-section--mist spc-price-page-payment" aria-labelledby="spc-price-page-payment-title">
		<div class="spc-container spc-split">
			<div class="spc-split__head">
				<h2 id="spc-price-page-payment-title"><?php esc_html_e( 'お支払いとキャンセル', 'spc' ); ?></h2>
			</div>
			<div class="spc-split__body">
				<dl class="spc-subpage__defs">
					<?php foreach ( $spc_payment as $spc_item ) : ?>
						<div class="spc-subpage__def">
							<dt><?php echo esc_html( $spc_item[0] ); ?></dt>
							<dd><?php echo esc_html( $spc_item[1] ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			</div>
		</div>
	</section>
	<?php
	get_template_part( 'template-parts/page-notes', null, array( 'post_id' => $spc_post_id ) );
endwhile;

spc_cta_block();

get_footer();
