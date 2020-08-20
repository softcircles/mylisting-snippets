<?php

function yoast_seo_breadcrumb_append_link( $links ) {
    global $post;

    if ( is_singular(  'job_listing' ) ) {
        $links = array_insert_after( $links, 2, array( array(
            'url'   => '#',
            'text'  => get_post_meta( $post->ID, '_case27_listing_type', true ),
        ) ) );
    }

    return $links;
}

function array_insert_after( array $array, $key, array $new ) {
    $keys = array_keys( $array );
    $index = array_search( $key, $keys );
    $pos = false === $index ? count( $array ) : $index + 1;

    return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
}
