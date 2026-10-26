<?php
/**
 * LP 6：職人と施工母体（墨地）。LP内で唯一のダークセクション（DESIGN.md §2・§4）。
 * 職人の顔写真・名前（仮）と、施工母体（有限会社杉本クリーニング商会・創業1954年）。
 * 静かに見せるため、ここにはCTAを置かない。
 */

defined( 'ABSPATH' ) || exit;

$spc_staff = spc_lp_query( 'spc_staff', array( 'posts_per_page' => 4 ) );
?>
<section class="spc-section spc-section--sumi spc-craftsmen" aria-labelledby="spc-craftsmen-title">
	<div class="spc-container">
		<h2 id="spc-craftsmen-title" class="spc-craftsmen__title"><?php esc_html_e( '施工する職人と、施工母体', 'spc' ); ?></h2>
		<?php // TODO(仮): 文言は依頼元確認後に確定 ?>
		<p class="spc-craftsmen__lead"><?php esc_html_e( 'お預かりした衣類は、自社の作業場で職人が一点ずつ確認し、施工します。', 'spc' ); ?></p>

		<div class="spc-craftsmen__body">
			<?php if ( $spc_staff ) : ?>
				<ul class="spc-craftsmen__staff" role="list">
					<?php
					while ( $spc_staff->have_posts() ) :
						$spc_staff->the_post();
						$spc_role = trim( (string) get_post_meta( get_the_ID(), 'spc_role', true ) );
						$spc_bio  = trim( (string) get_post_meta( get_the_ID(), 'spc_bio', true ) );
						?>
						<li class="spc-craftsmen__person">
							<div class="spc-craftsmen__photo">
								<?php
								// TODO(仮): 職人本人の実写（4:5）に差し替え。ストックフォト・AI生成画像は使わない（DESIGN.md §5）。
								spc_image_or_placeholder( (int) get_post_thumbnail_id(), 'spc-portrait', '職人の顔写真 4:5', '4/5' );
								?>
							</div>
							<div class="spc-craftsmen__profile">
								<div class="spc-craftsmen__name-row">
									<h3 class="spc-craftsmen__name"><?php echo esc_html( wp_strip_all_tags( get_the_title() ) ); ?></h3>
									<?php spc_placeholder_badge(); ?>
								</div>
								<?php if ( '' !== $spc_role ) : ?>
									<p class="spc-craftsmen__role"><?php echo esc_html( $spc_role ); ?></p>
								<?php endif; ?>
								<?php if ( '' !== $spc_bio ) : ?>
									<p class="spc-craftsmen__bio"><?php echo nl2br( esc_html( $spc_bio ) ); ?></p>
								<?php endif; ?>
							</div>
						</li>
						<?php
					endwhile;
					wp_reset_postdata();
					?>
				</ul>
			<?php endif; ?>

			<div class="spc-craftsmen__base">
				<div class="spc-craftsmen__workshop">
					<?php
					// TODO(仮): 作業場・作業風景の実写（3:2）に差し替え
					spc_image_or_placeholder( 0, 'spc-menu', '作業風景の実写 3:2', '3/2' );
					?>
				</div>
				<h3 class="spc-craftsmen__base-title"><?php esc_html_e( '施工母体', 'spc' ); ?></h3>
				<?php // TODO(仮): 文言は依頼元確認後に確定 ?>
				<dl class="spc-craftsmen__facts">
					<div class="spc-craftsmen__fact">
						<dt><?php esc_html_e( '運営', 'spc' ); ?></dt>
						<dd><?php esc_html_e( '有限会社杉本クリーニング商会', 'spc' ); ?></dd>
					</div>
					<div class="spc-craftsmen__fact">
						<dt><?php esc_html_e( '創業', 'spc' ); ?></dt>
						<dd class="spc-num"><?php esc_html_e( '1954年', 'spc' ); ?></dd>
					</div>
					<div class="spc-craftsmen__fact">
						<dt><?php esc_html_e( '洗い方', 'spc' ); ?></dt>
						<dd><?php esc_html_e( 'ドライクリーニングとウェットクリーニングを素材に合わせて使い分けています。', 'spc' ); ?></dd>
					</div>
				</dl>
				<p class="spc-craftsmen__more"><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( '職人・運営会社について', 'spc' ); ?></a></p>
			</div>
		</div>
	</div>
</section>
