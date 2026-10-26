<?php
/**
 * LP 1：ファーストビュー（DESIGN.md §1 の唯一の見せ場・§4 のワイヤー）。
 * 縦書きの明朝キャッチ＋繊維のマクロ写真。サブコピー・対応メニュー・CTAは写真の外に横書きで置く。
 */

defined( 'ABSPATH' ) || exit;

// TODO(仮): 実写（繊維マクロ）に差し替え。添付ファイルIDを入れると画像が出る（SPは3:4、PCは16:10で切り抜く）。
$spc_hero_image_id  = 0;
$spc_hero_has_photo = spc_has_image( $spc_hero_image_id );
?>
<section class="<?php echo esc_attr( spc_hero_classes() ); ?>" aria-labelledby="spc-hero-title" data-sticky-hide>
	<div class="hero__visual">
		<div class="hero__media<?php echo $spc_hero_has_photo ? '' : ' hero__media--placeholder'; ?>">
			<?php
			spc_image_or_placeholder(
				$spc_hero_image_id,
				'spc-hero',
				'繊維マクロ写真 16:10',
				'16/10',
				array(
					'loading'       => 'eager',
					'fetchpriority' => 'high',
				)
			);
			?>
		</div>
		<?php if ( $spc_hero_has_photo ) : ?>
			<div class="hero__overlay" aria-hidden="true"></div>
		<?php endif; ?>
		<?php // 行ごとに span で分ける（縦書きは2列、横書きは2行）。読み上げは1文のまま。 ?>
		<h1 id="spc-hero-title" class="hero__title"><span class="hero__line"><?php esc_html_e( '諦めかけた、', 'spc' ); ?></span><span class="hero__line"><?php esc_html_e( 'その一着に。', 'spc' ); ?></span></h1>
	</div>
	<div class="hero__body">
		<p class="hero__sub"><?php esc_html_e( '創業70年以上の技術を受け継ぐ職人が、衣類の状態を丁寧に確認します。', 'spc' ); ?></p>
		<p class="hero__menu"><?php esc_html_e( '特殊シミ抜き／高級ダウン・コート', 'spc' ); ?></p>
		<div class="hero__cta">
			<?php spc_cta( 'fv' ); ?>
		</div>
	</div>
</section>
