<?php

add_action( 'mylisting/admin/save-listing-data', function( $listing_id, $listing ) {

    $cover_image = $listing->get_cover_image('full');

    if ( $cover_image ) {

        $attachment_id = mylisting_get_attachment_image_id( $cover_image );

        if ( $old_thumbnail_id != $attachment_id ) {
            set_post_thumbnail( $listing_id, $attachment_id );
        }
    }

}, 99, 2 );

function mylisting_get_attachment_image_id($image_url) {
    global $wpdb;
    $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url ) );
        return $attachment[0];
}

add_action( 'mylisting/submission/save-listing-data', function( $listing_id ) {

    $listing = \MyListing\Src\Listing::get( $listing_id );

    $cover_image = $listing->get_cover_image('full');

    if ( $cover_image ) {

        $attachment_id = mylisting_get_attachment_image_id( $cover_image );

        if ( $old_thumbnail_id != $attachment_id ) {
            set_post_thumbnail( $listing_id, $attachment_id );
        }
    }

}, 99 );
