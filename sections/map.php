<?php
$data = c27()->merge_options( [
	'options' => [
		'items_type' => 'custom-locations',
		'zoom' => 12,
		'skin' => 'skin1',
		'marker_type' => 'basic',
		'locations' => [],
		'listings_query' => ['lat' => false, 'lng' => false, 'radius' => false, 'listing_type' => false, 'count' => 9],
		'cluster_markers' => true,
		'draggable' => true,
	],
	'template' => 'default',
	'icon' => '',
	'icon_style' => 1,
	'title' => '',
	'show_get_directions' => true,
    'ref' => '',
	'wrapper_class' => 'grid-item',
	'wrapper_id' => '',
	'content' => '',
    '_section_id' => 'section__' . uniqid(),
    'is_edit_mode' => false,
], $data );

$data['options']['_section_id'] = $data['_section_id'];
if ( empty( $data['wrapper_id'] ) ) {
	$data['wrapper_id'] = $data['_section_id'];
}

$mapargs = htmlspecialchars( json_encode( $data['options'] ), ENT_QUOTES, 'UTF-8' );

global $post;
$job_location = get_post_meta( $post->ID, '_job_location', true );

?>

<?php if ( ! $data['template'] || $data['template'] === 'default' ): ?>
	<section class="contact-map" id="<?php echo esc_attr( $data['_section_id'] ) ?>">
		<div class="c27-map map" data-options="<?php echo $mapargs ?>"></div>
		<div class="c27-map-listings hide"></div>
	</section>
<?php endif ?>

<?php if ($data['template'] == 'block'): ?>
	<div class="<?php echo esc_attr( $data['wrapper_class'] ) ?>" <?php echo $data['wrapper_id'] ? sprintf( 'id="%s"', $data['wrapper_id'] ) : '' ?>>
		<div class="element map-block">
			<div class="pf-head">
				<div class="title-style-1 title-style-<?php echo esc_attr( $data['icon_style'] ) ?>">
					<?php if ($data['icon_style'] != 3): ?>
						<?php echo c27()->get_icon_markup($data['icon']) ?>
					<?php endif ?>
					<h5><?php echo esc_html( $data['title'] ) ?> : <?php echo esc_html( $job_location ) ?></h5>
				</div>

				<?php if ( $data['show_get_directions'] && ! empty( $data['options']['locations'] )  ): ?>
					<?php do_action( 'mylisting/sections/map-block/actions', $data['options']['locations'][0], $data ) ?>
				<?php endif ?>
			</div>
			<div class="pf-body contact-map">
				<div class="c27-map map" data-options="<?php echo $mapargs ?>"></div>
				<div class="c27-map-listings hide"></div>
			</div>
		</div>
	</div>
<?php endif ?>

<?php if ($data['template'] == 'full_width_content'): ?>
	<section class="section-slider" id="<?php echo esc_attr( $data['_section_id'] ) ?>">
		<div class="featured-section featured-light featured-section-type-map featured-map">
			<div class="overlay"></div>
			<div class="featured-caption">
				<div class="row">
					<div class="container">
						<div class="fc-description col-md-12">
							<?php echo do_shortcode($data['content']) ?>
						</div>
					</div>
				</div>
			</div>
			<div class="c27-map map" style="position: absolute; top: 0px; left: 0px;" data-options="<?php echo $mapargs ?>"></div>
			<div class="c27-map-listings hide"></div>
		</div>
	</section>
<?php endif ?>

<?php if ($data['is_edit_mode']): ?>
    <script type="text/javascript">MyListing.Maps.init(); case27_ready_script(jQuery);</script>
<?php endif ?>
