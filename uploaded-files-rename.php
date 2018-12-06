<?php

add_action( 'add_attachment', function( $postid ) {

    if ( ! wp_attachment_is_image( $postid ) || ! ( $ancestors = get_post_ancestors( $postid ) ) ) {
        return;
    }

    if ( ! is_array( $ancestors ) || ! ( $listing = \MyListing\Src\Listing::get( $ancestors[0] ) ) ) {
        return;
    }

    $region = $listing->get_field('region');

    $region_name = '';

    if ( ! is_wp_error( $region ) && isset( $region[0] ) ) {
        $region_name = $region[0]->name;
    }

    $title_array = [ $region_name, $listing->get_name(), $listing->get_id() ];

    $title = [];

    foreach ( $title_array as $key ) {

        if ( ! $key ) {
            continue;
        }

        $title[] = $key;
    }

    if ( ! $title ) {
        $title = $listing->get_name();
    }

    $post_title = implode( '-', $title );

    wp_update_post( [
        'ID'            => $postid,
        'post_title'    => $post_title,
    ] );
} );
