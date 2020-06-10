<?php
/**
 * Template for rendering a `gallery` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// get the field instance
if ( ! ( $field = $listing->get_field_object( $block->get_prop( 'show_field' ) ) ) ) {
	return;
}

// get the gallery template to display
$gallery_type = $block->get_prop('gallery_type');
$field_value = $listing->get_field( $block->get_prop('show_field') );

// validate all images and format values for use in templates
$gallery_items = [];
foreach ( (array) $field_value as $gallery_item ) {
	$image_url = c27()->get_resized_image(
		$gallery_item,
		$gallery_type === 'carousel-with-preview' ? 'full' : 'full'
	);

	if ( ! $image_url ) {
		continue;
	}

	$full_image_url = c27()->get_resized_image( $gallery_item, 'full' );
	$image_attachment_id = c27()->get_attachment_by_guid( $gallery_item );

	$gallery_items[] = [
		'url' => $image_url,
		'full_size_url' => $full_image_url ?: $image_url,
		'alt' => $image_attachment_id
			? get_post_meta( $image_attachment_id, '_wp_attachment_image_alt', true )
			: '',
	];
}

// if no valid images are found, don't display the block
if ( empty( $gallery_items ) ) {
	return;
}

if ( $gallery_type === 'carousel-with-preview' ) {
	require locate_template( 'templates/single-listing/content-blocks/gallery-block/carousel-with-preview.php' );
} elseif ( $gallery_type === 'grid' ) {
	require locate_template( 'templates/single-listing/content-blocks/gallery-block/grid.php' );
} else {
	require locate_template( 'templates/single-listing/content-blocks/gallery-block/carousel.php' );
}
