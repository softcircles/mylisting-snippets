<?php

add_action( 'save_post', 'update_listing_slug_callback', 10, 2 );

function update_listing_slug_callback( $post_id, $post ) {

    // verify post is not a revision
    if ( ! wp_is_post_revision( $post_id ) ) {

        // unhook this function to prevent infinite looping
        remove_action( 'save_post', 'update_listing_slug_callback', 10, 2 );

        // update the post slug
        wp_update_post( array(
            'ID' => $post_id,
            'post_name' => $post->post_title.'-'.$post_id // do your thing here
        ));

        // re-hook this function
        add_action( 'save_post', 'update_listing_slug_callback', 10, 2 );
    }
}
