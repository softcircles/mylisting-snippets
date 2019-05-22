<?php

add_filter( 'wpseo_title', function( $title ) {
    global $post;

    if ( is_singular( 'job_listing' ) && $listing = new \MyListing\Src\Listing( $post ) ) {

        $listing_type = $listing->type();
        $listing_name = $listing->get_name();

        $listing_type_name = $listing_type->get_name();

        $category = $listing->get_field('category');

        $category_name = '';

        if ( ! is_wp_error( $category ) && isset( $category[0] ) ) {
            $category_name = $category[0]->name;
        }

        $wpseo_titles = get_option('wpseo_titles');

        $sep_options = WPSEO_Option_Titles::get_instance()->get_separator_options();

        if ( isset( $wpseo_titles['separator'] ) && isset( $sep_options[ $wpseo_titles['separator'] ] ) ) {
            $sep = $sep_options[ $wpseo_titles['separator'] ];
        } else {
            $sep = '-';
        }

        $site_title = get_bloginfo( 'name' );
        $meta_title = ' ' . $sep . ' ' . $site_title;

        if ( $category_name && 'Place' === $listing_type_name ) {
            $title =  $listing_name . ' - ' . $category_name . $meta_title;
        }
    }

    return $title;

}, 99, 2 );
