<?php
/**
 * Template for rendering a `location` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// get the field instance
$field = $listing->get_field_object( $block->get_prop( 'show_field' ) );
if ( ! ( $field && $field->get_value() ) ) {
	return;
}

// use the listing logo for the marker image, with fallback to a marker icon
if ( ! ( $marker_image = $listing->get_logo( 'thumbnail' ) ) ) {
    $marker_image = c27()->image( 'marker.jpg' );
}

 $all_terms = $listing->get_field( 'category' );

 if ( ! empty( $all_terms ) || ! is_wp_error( $all_terms ) ) {
	$terms = array_filter( array_map( function( $term ) {
		if ( ! $term = \MyListing\Src\Term::get( $term ) ) {
			return false;
		}

		return [
			'link' => $term->get_link(),
			'name' => $term->get_name(),
			'color' => $term->get_color(),
			// 'icon' => $term->get_icon( [ 'background' => false ] ),
			'icon' => $term->get_icon_image_url(),
		];
	}, $all_terms ) );
}

 // print_r([$terms[0]['icon'], $terms]); exit();
// use the listing address to display the marker, which would then get geocoded by the map service
$location_arr = [
    'address' => $field->get_value(),
    'marker_image' => [ 'url' =>  ( !empty($terms[0]['icon']) ) ? $terms[0]['icon'] : $marker_image ],
];

// if we're displaying the location field, we can directly retrieve the coordinates from database
if ( $block->get_prop('show_field') === 'job_location' && ( $lat = $listing->get_data('geolocation_lat') ) && ( $lng = $listing->get_data('geolocation_long') ) ) {
    $location_arr = [
        'marker_lat' => $lat,
        'marker_lng' => $lng,
        'marker_image' => [ 'url' => ( !empty($terms[0]['icon']) ) ? $terms[0]['icon'] : $marker_image ],
    ];
}

$mapargs = [
	'items_type' => 'custom-locations',
	'marker_type' => 'basic',
	'locations' => [ $location_arr ],
	'skin' => $block->get_prop('map_skin'),
	'zoom' => 11,
	'draggable' => true,
];
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element map-block">
		<div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>

			<?php if ( ! empty( $mapargs['locations'] )  ): ?>
				<?php do_action( 'mylisting/sections/map-block/actions', $mapargs['locations'][0] ) ?>
			<?php endif ?>
		</div>
		<div class="pf-body contact-map">
			<div class="c27-map map" data-options="<?php echo c27()->encode_attr( $mapargs ) ?>"></div>
			<div class="c27-map-listings hide"></div>
		</div>
	</div>
</div>
