<?php

add_action( 'wp_head', 'filter_page_tags_title', 5 );
 
function filter_page_tags_title( $title ) {
 
    if ( is_singular( 'job_listing' ) ) {
 
        $cover_url = get_post_meta( get_the_ID(), '_job_cover', true );
 
        if ( ! empty( $cover_url ) ) {
            printf( "<meta property=\"twitter:image\" content=\"%s\" />n", esc_url( $cover_url ) );
        }
    }
}
