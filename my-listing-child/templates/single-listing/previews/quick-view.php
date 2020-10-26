<?php
/**
 * Listing "Quick View" template.
 *
 * @var   \MyListing\Src\Listing $listing
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

if ( ! ( $listing && $listing->type ) ) {
    return;
}

// preview/quick-view card options
$options = $listing->type->get_preview_options();
$is_caching = false;

$categories = $listing->get_field( 'category' );
$listing_thumbnail = $listing->get_logo( 'thumbnail' ) ?: c27()->image( 'marker.jpg' );
$quick_view_template = $options['quick_view']['template'];
if ( ! ( $listing->get_data('geolocation_lat') && $listing->get_data('geolocation_long') ) ) {
	$quick_view_template = 'alternate';
}

if ( $listing->get_priority() >= 2 ) {
    $promotion_tooltip = _x( 'Promoted', 'Listing Preview Card: Promoted Tooltip Title', 'my-listing' );
} elseif ( $listing->get_priority() === 1 ) {
    $promotion_tooltip = _x( 'Featured', 'Listing Preview Card: Promoted Tooltip Title', 'my-listing' );
} else {
    $promotion_tooltip = '';
} ?>

<div class="listing-quick-view-container listing-preview <?php echo esc_attr( "quick-view-{$quick_view_template} quick-view type-{$listing->type->get_slug()} tpl-{$options['template']}" ) ?>">
	<div class="mc-left">
		<div class="lf-item-container">
			<div class="lf-item">
			    <a href="<?php echo esc_url( $listing->get_link() ) ?>">
		            <div class="overlay"></div>

		            <?php if ($options['background']['type'] == 'gallery' && ( $gallery = $listing->get_field( 'gallery' ) ) ): ?>
	                    <div class="owl-carousel lf-background-carousel">
		                    <?php foreach ($gallery as $gallery_image): ?>
		                        <div class="item">
		                            <div
		                                class="lf-background"
		                                style="background-image: url('<?php echo esc_url( c27()->get_resized_image( $gallery_image, 'large' ) ) ?>');">
		                            </div>
		                        </div>
		                    <?php endforeach ?>
	                    </div>
            		<?php else: $options['background']['type'] = 'image'; endif; // Fallback to cover image if no gallery images are present ?>

		            <?php if ($options['background']['type'] == 'image' && ( $cover = $listing->get_cover_image( 'large' ) ) ): ?>
		                <div
		                    class="lf-background"
		                    style="background-image: url('<?php echo esc_url( $cover ) ?>');">
		                </div>
		            <?php endif ?>

		           	<div class="lf-item-info">
		           	    <h4><?php echo apply_filters( 'the_title', $listing->get_name(), $listing->get_id() ) ?></h4>

			            <?php
			            /**
			             * Include info fields template.
			             *
			             * @since 1.0
			             */
			            require locate_template( 'templates/single-listing/previews/partials/info-fields.php' ) ?>
		           	</div>

			        <?php
			        /**
			         * Include head buttons template.
			         *
			         * @since 1.0
			         */
			        require locate_template( 'templates/single-listing/previews/partials/head-buttons.php' ) ?>
		        </a>

		        <?php if ( $options['background']['type'] === 'gallery' ): ?>
					<?php require locate_template( 'templates/single-listing/previews/partials/gallery-nav.php' ) ?>
		        <?php endif ?>
			</div>
		</div>
		<div class="grid-item">
			<div class="element min-scroll">
				<div class="pf-head">
					<div class="title-style-1">
						<i class="material-icons">view_headline</i>
						<h5><?php _e( 'Description', 'my-listing' ) ?></h5>
					</div>
				</div>
				<div class="pf-body">
					<p>
						<?php echo wp_kses( nl2br( $listing->get_field( 'description' ) ), ['br' => []] ) ?>
					</p>
				</div>
			</div>
		</div>
		<div class="grid-item">
			<?php if ( $categories ) : ?>
				<div class="element min-scroll">
					<div class="pf-head">
						<div class="title-style-1">
							<i class="material-icons">view_module</i>
							<h5><?php _e( 'Categories', 'my-listing' ) ?></h5>
						</div>
					</div>
					<div class="pf-body">
						<div class="listing-details">
							<ul>
								<?php foreach ($categories as $category):
									$term = new MyListing\Src\Term( $category );
									?>
									<li>
										<a href="<?php echo esc_url( $term->get_link() ) ?>">
											<span class="cat-icon" style="background-color: <?php echo esc_attr ($term->get_color() ) ?>;">
	                                        	<?php echo $term->get_icon([ 'background' => false ]) ?>
											</span>
											<span class="category-name"><?php echo esc_html( $term->get_name() ) ?></span>
										</a>
									</li>
								<?php endforeach ?>
							</ul>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<div class="mc-right">
		<div class="block-map c27-map" data-options="<?php echo esc_attr( wp_json_encode( [
			'items_type' => 'custom-locations',
			'zoom' => 12,
			'skin' => $options['quick_view']['map_skin'],
			'marker_type' => 'basic',
			'locations' => [[
				'marker_lat' => (float) $listing->get_data('geolocation_lat'),
				'marker_lng' => (float) $listing->get_data('geolocation_long'),
				'marker_image' => ['url' => $listing_thumbnail],
			]],
		] ) ) ?>">
		</div>
	</div>
</div>
