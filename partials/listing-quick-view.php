<?php
// Listing preview default options.
$defaults = [
    'background' => ['type' => 'gallery'],
    'buttons' => [],
    'info_fields' => [],
    'quick_view' => ['template' => 'default', 'map_skin' => 'skin1'],
];

$data = c27()->merge_options([
        'listing' => '',
        'options' => [],
        'wrap_in' => '',
    ], $data);

// If the listing object isn't provided, return empty.
if ( ! ( $data['listing'] && $data['listing']->type ) ) {
    return;
}

$listing = $data['listing'];

// Get the preview template options for the listing type of the current listing.
$options = $listing->get_preview_options();

// Merge with the default options, in case the listing type options meta returns null.
$options = c27()->merge_options( $defaults, (array) $options );

// Finally, in case custom options have been provided through the c27()->get_partial() method,
// then give those the highest priority, by overwriting the listing type options with those.
$options = c27()->merge_options( $options, (array) $data['options'] );

// Categories.
$categories = $listing->get_field( 'category' );

$listing_thumbnail = ( get_avatar_url( $listing->get_author() ) ? : $listing->get_logo('thumbnail') ) ?: c27()->image( 'marker.jpg' );

$quick_view_template = $options['quick_view']['template'];

if ( ! $listing->get_data('geolocation_lat') || ! $listing->get_data('geolocation_long') ) {
	$quick_view_template = 'alternate';
}
?>

<div class="listing-quick-view-container listing-preview <?php echo esc_attr( "quick-view-{$quick_view_template} quick-view type-{$listing->type->get_slug()} tpl-{$options['template']}" ) ?>">
	<div class="mc-left">
		<div class="lf-item-container">
			<div class="lf-item">
			    <a href="<?php echo esc_url( $listing->get_link() ) ?>">
		            <div class="overlay"></div>

		            <!-- BACKGROUND GALLERY -->
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

		            <!-- BACKGROUND IMAGE -->
		            <?php if ($options['background']['type'] == 'image' && ( $cover = $listing->get_cover_image( 'large' ) ) ): ?>
		                <div
		                    class="lf-background"
		                    style="background-image: url('<?php echo esc_url( $cover ) ?>');">
		                </div>
		            <?php endif ?>

		            <!-- DEFAULT TITLE TEMPLATE -->
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

		        <!-- BACKGROUND GALLERY NAVIGATION BUTTONS -->
		        <?php if ($options['background']['type'] == 'gallery'): ?>
		        	<div class="gallery-nav">
		        		<ul>
		        			<li>
		        				<a href="#" class="lf-item-prev-btn">
		        					<i class="material-icons">keyboard_arrow_left</i>
		        				</a>
		        			</li>
		        			<li>
		        				<a href="#" class="lf-item-next-btn">
		        					<i class="material-icons">keyboard_arrow_right</i>
		        				</a>
		        			</li>
		        		</ul>
		        	</div>
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
						<?php echo wp_kses( nl2br( apply_filters( 'the_content', $listing->get_field( 'description' ) ) ), ['br' => []] ) ?>
					</p>
				</div>
			</div>
		</div>
		<div class="grid-item">
			<div class="element min-scroll">
				<div class="pf-head">
					<div class="title-style-1">
						<i class="material-icons">view_module</i>
						<h5><?php _e( 'Categories', 'my-listing' ) ?></h5>
					</div>
				</div>
				<div class="pf-body">
					<div class="listing-details">
						<?php $category_img_url = ''; $count = 1; ?>
						<ul>
							<?php foreach ($categories as $category):
								$term = new MyListing\Src\Term( $category );
								if( $count == 1 ) {
									$category_img_url = $term->get_icon_image_url();
									$count++;
								}
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
		</div>
	</div>
	<div class="mc-right">
		<div class="block-map c27-map" data-options="<?php echo htmlspecialchars(json_encode([
			'items_type' => 'custom-locations',
			'zoom' => 12,
			'skin' => $options['quick_view']['map_skin'],
			'marker_type' => 'basic',
			'locations' => [[
				'marker_lat' => (float) $listing->get_data('geolocation_lat'),
				'marker_lng' => (float) $listing->get_data('geolocation_long'),
				'marker_image' => ['url' => ( ! empty( $category_img_url ) ) ? $category_img_url : $listing_thumbnail ],
			]],
		]), ENT_QUOTES, 'UTF-8'); ?>">
		</div>
	</div>
</div>
