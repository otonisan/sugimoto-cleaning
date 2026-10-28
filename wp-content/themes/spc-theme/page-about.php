<?php
/**
 * 職人・運営会社（/about/）。固定ページ about 専用。
 * 並びと地は DESIGN.md §4：施工する職人（墨・このページ唯一のダーク）→ 作業場（白）→ 会社概要（灰白）→ 相談ブロック。
 * 会社概要は固定ページの本文（表）を出す。本文が空ならセクションごと出さない。このページに「お知らせ・補足」は置かない。
 * 職人の顔写真は本人の実写のみ（ストックフォト・AI生成画像は使わない。DESIGN.md §5）。
 * ブランド名・ロゴは文章にも写真枠のラベルにも書かない。
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$spc_post_id = (int) get_the_ID();

	// TODO(仮): 文言は依頼元確認後に確定（リード文） [copy:about.lead]
	$spc_lead = __( '創業1954年。自社の作業場で、職人が一点ずつ確認しています。', 'spc' );

	// TODO(仮): 文言は依頼元確認後に確定（施工する職人の導入文） [copy:about.staff]
	$spc_staff_lead = __( 'お預かりした衣類は、施工する職人が状態を見て、一点ずつ丁寧に扱います。', 'spc' );

	// TODO(仮): 文言は依頼元確認後に確定（作業場） [copy:about.workshop]
	$spc_workshop_text = array(
		__( '自社の作業場で、衣類の素材や状態に合わせて、ドライクリーニングとウェットクリーニングを使い分けています。', 'spc' ),
		__( '洗い方は、洗濯表示と素材を確認したうえで決めます。気になる点は、写真相談のときにお知らせください。', 'spc' ),
	);

	// 写真枠（画像IDは inc/placeholders.php の 'about_workshop' 'about_hands' 'about_equipment' に入れる）。
	// TODO(仮): 作業場・手元・設備の実写（各3:2）に差し替え。キャプションは写真を決めたあとに確定 [copy:about.workshop]
	$spc_workshop_photos = array(
		array( 'about_workshop', __( '作業場の全景', 'spc' ), '作業場の全景 3:2' ),
		array( 'about_hands', __( '手元の作業', 'spc' ), '手元の作業 3:2' ),
		array( 'about_equipment', __( '設備', 'spc' ), '設備 3:2' ),
	);

	// 職人は全員。0件ならセクションごと出さない。
	$spc_staff = spc_lp_query( 'spc_staff', array( 'posts_per_page' => -1 ) );

	// 会社概要（固定ページの本文）。空判定と出力は同じ関数から。
	$spc_company = spc_page_notes_html( $spc_post_id );

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
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'spc-page spc-page--about' ); ?>>
		<?php if ( $spc_staff ) : ?>
			<section class="spc-section spc-section--sumi spc-about-craftsmen" aria-labelledby="spc-about-craftsmen-title">
				<div class="spc-container">
					<h2 id="spc-about-craftsmen-title"><?php esc_html_e( '施工する職人', 'spc' ); ?></h2>
					<p class="spc-about-craftsmen__lead"><?php echo esc_html( $spc_staff_lead ); ?></p>
					<div class="spc-about-craftsmen__list">
						<?php
						get_template_part(
							'template-parts/staff-list',
							null,
							array(
								'query'   => $spc_staff,
								'heading' => 'h3',
							)
						);
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="spc-section spc-about-workshop" aria-labelledby="spc-about-workshop-title">
			<div class="spc-container">
				<h2 id="spc-about-workshop-title"><?php esc_html_e( '作業場', 'spc' ); ?></h2>
				<ul class="spc-about-workshop__photos" role="list">
					<?php foreach ( $spc_workshop_photos as $spc_photo ) : ?>
						<li>
							<figure class="spc-about-workshop__figure">
								<?php spc_image_or_placeholder( spc_theme_image_id( $spc_photo[0] ), 'spc-menu', $spc_photo[2], '3/2' ); ?>
								<figcaption><?php echo esc_html( $spc_photo[1] ); ?></figcaption>
							</figure>
						</li>
					<?php endforeach; ?>
				</ul>
				<div class="spc-subpage__text spc-about-workshop__text">
					<?php foreach ( $spc_workshop_text as $spc_paragraph ) : ?>
						<p><?php echo esc_html( $spc_paragraph ); ?></p>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<?php if ( '' !== $spc_company ) : ?>
			<section class="spc-section spc-section--mist spc-about-company" aria-labelledby="spc-about-company-title">
				<div class="spc-container">
					<h2 id="spc-about-company-title"><?php esc_html_e( '会社概要', 'spc' ); ?></h2>
					<div class="spc-table-content spc-about-company__body">
						<?php echo wp_kses_post( $spc_company ); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</article>
	<?php
endwhile;

spc_cta_block();

get_footer();
