<?php
/**
 * Parallax cover image template for single listing page.
 *
 * @since 1.6.0
 */

// Use the empty template if listing cover image isn't available.

if ( ! ( $image = $listing->get_field( 'gallery' )[0] ) ) {
    return require locate_template( 'partials/single/cover/none.php' );
}

// Overlay options.
$overlay_opacity = c27()->get_setting( 'single_listing_cover_overlay_opacity', '0.5' );
$overlay_color   = c27()->get_setting( 'single_listing_cover_overlay_color', '#242429' );
?>

<section class="featured-section profile-cover parallax-bg profile-cover-image" style="background-image: url('<?php echo esc_url( $image ) ?>');">
    <div class="overlay"
         style="background-color: <?php echo esc_attr( $overlay_color ); ?>;
                opacity: <?php echo esc_attr( $overlay_opacity ); ?>;"
        >
    </div>
<!-- Omit the closing </section> tag -->
