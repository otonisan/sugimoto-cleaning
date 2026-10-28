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
		<?php // TODO(仮): 文言は依頼元確認後に確定 [copy:lp.craftsmen] ?>
		<p class="spc-craftsmen__lead"><?php esc_html_e( 'お預かりした衣類は、自社の作業場で職人が一点ずつ確認し、施工します。', 'spc' ); ?></p>

		<div class="spc-craftsmen__body">
			<?php if ( $spc_staff ) : ?>
				<?php get_template_part( 'template-parts/staff-list', null, array( 'query' => $spc_staff ) ); ?>
			<?php endif; ?>

			<div class="spc-craftsmen__base">
				<div class="spc-craftsmen__workshop">
					<?php
					// TODO(仮): 作業場・作業風景の実写（3:2）に差し替え。画像IDは inc/placeholders.php の 'workshop' に入れる。
					spc_image_or_placeholder( spc_theme_image_id( 'workshop' ), 'spc-menu', '作業風景の実写 3:2', '3/2' );
					?>
				</div>
				<h3 class="spc-craftsmen__base-title"><?php esc_html_e( '施工母体', 'spc' ); ?></h3>
				<?php // TODO(仮): 文言は依頼元確認後に確定 [copy:lp.craftsmen] ?>
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
