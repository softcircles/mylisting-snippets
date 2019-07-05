<?php 

add_shortcode( '27-listing-logo', function( $atts = [] ) {

    $atts = shortcode_atts( [
        'id'           => '',
    ], $atts, '27-listing-logo');

    if ( empty( $atts['id'] ) ) {
        global $post;

        $atts['id'] = $post->ID;
    }

    $listing = MyListing\Src\Listing::get( $atts['id'] );

    $listing_logo = $listing->get_logo( 'medium' );

    if ( $listing_logo ) :
        return sprintf(
                '<img src="%1$s">',
                $listing_logo
            );
    endif;
} );
