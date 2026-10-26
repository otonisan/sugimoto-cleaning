<?php
/**
 * LP 10：よくある質問（白地）。「トップに表示」付きを最大5件。<details> で開閉（JS不要、DESIGN.md §6）。
 * 構造化データ（inc/schema.php の FAQPage）も同じクエリを使う。0件のときはセクションごと出さない。
 */

defined( 'ABSPATH' ) || exit;

$spc_faqs = spc_top_faq_query();
if ( ! $spc_faqs ) {
	return;
}
?>
<section class="spc-section spc-faq" aria-labelledby="spc-faq-title">
	<div class="spc-container spc-split">
		<div class="spc-split__head">
			<h2 id="spc-faq-title" class="spc-faq__title"><?php esc_html_e( 'よくある質問', 'spc' ); ?></h2>
		</div>
		<div class="spc-split__body">
			<div class="spc-faq__list">
				<?php
				while ( $spc_faqs->have_posts() ) :
					$spc_faqs->the_post();
					?>
					<details class="spc-faq__item">
						<summary class="spc-faq__summary"><span class="spc-faq__q"><?php echo esc_html( wp_strip_all_tags( get_the_title() ) ); ?></span><?php spc_placeholder_badge(); ?></summary>
						<div class="spc-faq__a"><?php echo wp_kses_post( wpautop( get_post_field( 'post_content', get_the_ID() ) ) ); ?></div>
					</details>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>
			<p class="spc-faq__more"><a href="<?php echo esc_url( home_url( '/guide/#faq' ) ); ?>"><?php esc_html_e( 'よくある質問をすべて見る', 'spc' ); ?></a></p>
		</div>
	</div>
</section>
