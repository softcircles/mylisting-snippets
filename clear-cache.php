<?php

add_action( 'save_post', 'mylisting_clear_custom_cache', 10,3 );
 
function mylisting_clear_custom_cache( $post_id, $post, $update ) {

    if ( 'job_listing' !== $post->post_type ) {
        return;
    }
    
    // Some Super Cache Stuff
    global $blog_cache_dir;

    prune_super_cache( $blog_cache_dir, true );
    prune_super_cache( get_supercache_dir(), true );
}
