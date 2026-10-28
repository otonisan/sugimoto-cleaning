<?php
/**
 * 特殊シミ抜き（/stain-removal/）。固定ページ stain-removal 専用。
 * 並びと地は DESIGN.md §4：相談できるシミの例（白）→ 落ちやすさを左右するもの（灰白）→ 黄ばみ・汗汚れ（白）
 * → お引き受けできない場合（灰白）→ 料金の目安（白）→ 関連する施工事例（灰白）→ よくある質問（白）
 * → お知らせ・補足 → 相談ブロック。ページの途中にCTAは置かない。
 * 固定ページの本文は「お知らせ・補足」の欄に出す（依頼元が入力したときだけ）。
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$spc_post_id = (int) get_the_ID();

	// TODO(仮): 文言は依頼元確認後に確定（リード文） [copy:stain.lead]
	$spc_lead = __( '他店で落ちなかったシミや、時間がたったシミのご相談も承ります。まずは写真を送っていただき、拝見してからお伝えします。', 'spc' );

	// TODO(仮): 文言は依頼元確認後に確定（シミの例の一覧と注記） [copy:stain.examples]
	$spc_examples = array(
		__( '食べこぼし・飲みこぼし', 'spc' ),
		__( 'ファンデーション・口紅', 'spc' ),
		__( '皮脂・汗', 'spc' ),
		__( 'インク・ボールペン', 'spc' ),
		__( '血液', 'spc' ),
		__( 'カビ', 'spc' ),
		__( '時間がたったシミ', 'spc' ),
		__( '他店で落ちなかったシミ', 'spc' ),
	);

	// TODO(仮): 文言は依頼元確認後に確定（落ちやすさを左右するもの） [copy:stain.factors]
	$spc_factors = array(
		array( __( '素材と染色', 'spc' ), __( '生地の種類や染めの状態によって、使える方法が変わります。', 'spc' ) ),
		array( __( '付着してからの時間', 'spc' ), __( '時間がたつほど、繊維になじんで落ちにくくなる傾向があります。', 'spc' ) ),
		array( __( 'それまでの処理（ご自身・他店）', 'spc' ), __( 'これまでに行った処理は、写真と一緒にお知らせください。判断の手がかりになります。', 'spc' ) ),
		array( __( 'シミの成分', 'spc' ), __( '油分・水分・色素など、成分によって向き不向きがあります。', 'spc' ) ),
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
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'spc-page spc-page--stain' ); ?>>
		<section class="spc-section spc-stain-examples" aria-labelledby="spc-stain-examples-title">
			<div class="spc-container spc-split">
				<div class="spc-split__head">
					<h2 id="spc-stain-examples-title"><?php esc_html_e( '相談できるシミの例', 'spc' ); ?></h2>
				</div>
				<div class="spc-split__body">
					<ul class="spc-subpage__list" role="list">
						<?php foreach ( $spc_examples as $spc_example ) : ?>
							<li class="spc-subpage__item"><?php echo esc_html( $spc_example ); ?></li>
						<?php endforeach; ?>
					</ul>
					<p class="spc-subpage__note"><?php esc_html_e( '写真を拝見して、落とせる可能性をお伝えします。', 'spc' ); ?></p>
				</div>
			</div>
		</section>

		<section class="spc-section spc-section--mist spc-stain-factors" aria-labelledby="spc-stain-factors-title">
			<div class="spc-container spc-split">
				<div class="spc-split__head">
					<h2 id="spc-stain-factors-title"><?php esc_html_e( '落ちやすさを左右するもの', 'spc' ); ?></h2>
				</div>
				<div class="spc-split__body">
					<dl class="spc-subpage__defs">
						<?php foreach ( $spc_factors as $spc_factor ) : ?>
							<div class="spc-subpage__def">
								<dt><?php echo esc_html( $spc_factor[0] ); ?></dt>
								<dd><?php echo esc_html( $spc_factor[1] ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
					<div class="spc-subpage__photo">
						<?php
						// TODO(仮): 手元・作業の実写（3:2）に差し替え。画像IDは inc/placeholders.php の 'stain_work' に入れる。
						spc_image_or_placeholder( spc_theme_image_id( 'stain_work' ), 'spc-menu', '手元・作業の写真 3:2', '3/2' );
						?>
					</div>
				</div>
			</div>
		</section>

		<section id="yellowing" class="spc-section spc-stain-yellowing" aria-labelledby="spc-stain-yellowing-title">
			<div class="spc-container spc-split">
				<div class="spc-split__head">
					<h2 id="spc-stain-yellowing-title"><?php esc_html_e( '黄ばみ・汗汚れ', 'spc' ); ?></h2>
				</div>
				<div class="spc-split__body">
					<?php // TODO(仮): 文言は依頼元確認後に確定 [copy:stain.yellowing] ?>
					<div class="spc-subpage__text">
						<p><?php esc_html_e( '襟や脇、袖口などに出る黄ばみや汗汚れも、特殊シミ抜きの対象としてご相談いただけます。', 'spc' ); ?></p>
						<p><?php esc_html_e( '時間がたつと生地になじみ、色が戻りにくくなることがあります。気になりはじめた段階で、一度写真をお送りください。', 'spc' ); ?></p>
						<p><?php esc_html_e( '落とせる範囲は素材や状態によって変わるため、写真を拝見したうえでお伝えします。', 'spc' ); ?></p>
					</div>
					<div class="spc-subpage__photo">
						<?php
						// TODO(仮): 黄ばみ・汗汚れの実写（3:2）に差し替え。画像IDは inc/placeholders.php の 'stain_yellowing' に入れる。
						spc_image_or_placeholder( spc_theme_image_id( 'stain_yellowing' ), 'spc-menu', '黄ばみ・汗汚れの写真 3:2', '3/2' );
						?>
					</div>
				</div>
			</div>
		</section>
	</article>
	<?php
	get_template_part(
		'template-parts/sections/limits',
		null,
		array(
			'tone'       => 'mist',
			'heading_id' => 'spc-stain-limits-title',
		)
	);

	get_template_part(
		'template-parts/sections/price',
		null,
		array(
			'tone'       => 'paper',
			'heading_id' => 'spc-stain-price-title',
			'groups'     => array( 'stain', 'yellowing' ),
			'show_steps' => false,
		)
	);

	// 該当する事例・FAQ が0件のときは null（部品に渡すと既定のクエリに戻ってしまうため、呼ばない）。
	$spc_related_cases = spc_related_cases_query( array( 'stain', 'yellowing' ) );
	if ( $spc_related_cases ) {
		get_template_part(
			'template-parts/sections/cases',
			null,
			array(
				'tone'       => 'mist',
				'heading'    => __( '関連する施工事例', 'spc' ),
				'heading_id' => 'spc-stain-cases-title',
				'show_cta'   => false,
				'query'      => $spc_related_cases,
			)
		);
	}

	$spc_stain_faqs = spc_faq_query( array( 'stain' ) );
	if ( $spc_stain_faqs ) {
		get_template_part(
			'template-parts/sections/faq',
			null,
			array(
				'tone'       => 'paper',
				'id'         => 'faq',
				'heading_id' => 'spc-stain-faq-title',
				'query'      => $spc_stain_faqs,
			)
		);
	}

	get_template_part( 'template-parts/page-notes', null, array( 'post_id' => $spc_post_id ) );
endwhile;

spc_cta_block();

get_footer();
