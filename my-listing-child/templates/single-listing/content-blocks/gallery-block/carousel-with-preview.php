<?php
/**
 * Template for rendering the `carousel-with-preview` template for gallery block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

$items_per_row = min( 3, count( $gallery_items ) );
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element slider-padding gallery-block">
		<div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>
		</div>

		<div class="pf-body">
			<div class="gallerySlider car-slider">
				<div class="owl-carousel galleryPreview photoswipe-gallery">
					<?php foreach ( $gallery_items as $item ): ?>
						<a class="item photoswipe-item" href="<?php echo esc_url( $item['full_size_url'] ) ?>">
						   <img src="<?php echo esc_url( $item['url'] ) ?>" alt="<?php echo esc_attr( $item['alt'] ) ?>">
						</a>
					<?php endforeach ?>
				</div>

				<?php if ( count( $gallery_items ) > 1 ): ?>
					<div class="gallery-thumb owl-carousel photoswipe-gallery" data-items="<?php echo absint( $items_per_row ) ?>"
						data-items-mobile="<?php echo absint( $items_per_row ) ?>">
						<?php foreach ( $gallery_items as $key => $item ): ?>
							<a
								class="item slide-thumb photoswipe-item"
								data-slide-no="<?php echo esc_attr( $key ) ?>"
								href="<?php echo esc_url( $item['url'] ) ?>"
								style="background-image: url('<?php echo esc_url( $item['url'] ) ?>')"
							></a>
						<?php endforeach ?>
					</div>
				<?php endif ?>

				<?php if ( count( $gallery_items ) > 3 ): ?>
					<div class="gallery-nav">
						<ul>
							<li><a href="#" class="gallery-prev-btn"><i class="mi keyboard_arrow_left"></i></a></li>
							<li><a href="#" class="gallery-next-btn"><i class="mi keyboard_arrow_right"></i></a></li>
						</ul>
					</div>
				<?php endif ?>
			</div>
		</div>
	</div>
</div>
