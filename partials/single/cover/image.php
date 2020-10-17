<?php
/**
 * Parallax cover image template for single listing page.
 *
 * @since 1.6.0
 */

// Use the empty template if listing cover image isn't available.
if ( ! ( $image = $listing->get_cover_image( 'full' ) ) ) {

	if ( ! $image = $listing->get_field('gallery') ) {
	    return require locate_template( 'partials/single/cover/none.php' );
    }

    $image = $image[0];
}

// Overlay options.
$overlay_opacity = c27()->get_setting( 'single_listing_cover_overlay_opacity', '0.5' );
$overlay_color   = c27()->get_setting( 'single_listing_cover_overlay_color', '#242429' );
if ( $cover_padding = c27()->get_setting( 'single_listing_cover_height', 35 ) ) {
	$padding = absint( $cover_padding );
}
?>

<section class="featured-section profile-cover profile-cover-image hide-until-load"
	style="background-image: url('<?php echo esc_url( $image ) ?>'); padding-bottom: <?php echo !empty($padding)?$padding:35; ?>%;">
    <div class="overlay"
         style="background-color: <?php echo esc_attr( $overlay_color ); ?>;
                opacity: <?php echo esc_attr( $overlay_opacity ); ?>;"
        >
    </div>
<!-- Omit the closing </section> tag -->
