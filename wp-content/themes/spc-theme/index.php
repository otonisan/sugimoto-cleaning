<?php
/**
 * 汎用の表示（ほかのテンプレートが無いときの代わり）。
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( is_singular() ) :
	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/content', 'page' );
	endwhile;
else :
	if ( is_archive() ) {
		$spc_heading = get_the_archive_title();
	} elseif ( is_search() ) {
		$spc_heading = __( '検索結果', 'spc' );
	} else {
		$spc_heading = spc_brand_name();
	}
	?>
	<section class="spc-page">
		<header class="spc-page__header">
			<div class="spc-container">
				<h1 class="spc-page__title"><?php echo wp_kses_post( $spc_heading ); ?></h1>
			</div>
		</header>
		<div class="spc-page__body">
			<div class="spc-container">
				<div class="spc-prose">
					<?php if ( have_posts() ) : ?>
						<ul class="spc-post-list">
							<?php
							while ( have_posts() ) :
								the_post();
								?>
								<li class="spc-post-list__item">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
									<?php spc_placeholder_badge(); ?>
								</li>
							<?php endwhile; ?>
						</ul>
						<?php the_posts_pagination(); ?>
					<?php else : ?>
						<p><?php esc_html_e( '該当する記事はありません。', 'spc' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
	<?php
endif;

spc_cta_block();

get_footer();
