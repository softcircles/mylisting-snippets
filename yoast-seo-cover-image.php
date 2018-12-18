<?php

add_filter( 'wpseo_opengraph_image', function( $image_url ) {
    global $post;

    if ( is_singular( 'job_listing' ) && $listing = \MyListing\Src\Listing::get( $post->ID ) ) {
        $image_url = $listing->get_cover_image( 'large' );
    }

    return $image_url;
} );


add_filter( 'wpseo_twitter_image', function( $image_url ) {
    global $post;

    if ( is_singular( 'job_listing' ) && $listing = \MyListing\Src\Listing::get( $post->ID ) ) {
        $image_url = $listing->get_cover_image( 'large' );
    }

    return $image_url;
} );
