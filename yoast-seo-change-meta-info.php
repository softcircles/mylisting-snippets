<?php


add_filter( 'wpseo_metadesc', function( $description ) {

    global $post;

    if ( is_singular( 'job_listing' ) && $listing = new \MyListing\Src\Listing( $post ) ) {

        $title_tag = '';
        $region_name = '';

        $region = $listing->get_field('region');

        if ( ! is_wp_error( $region ) && isset( $region[0] ) ) {

            $region_name = $region[0]->name;

            $description .= $region_name . ' - ';
        }

        $description .= $listing->get_field('description');
    }

    return $description;
} );

add_filter( 'wpseo_title', function( $title ) {

    global $post;

    if ( is_singular( 'job_listing' ) && $listing = new \MyListing\Src\Listing( $post ) ) {

        $title_tag = '';
        $region_name = '';

        $region = $listing->get_field('region');

        $region_name = '';

        if ( ! is_wp_error( $region ) && isset( $region[0] ) ) {
            $region_name = $region[0]->name;
        }

        $wpseo_titles = get_option('wpseo_titles');

        $sep_options = WPSEO_Option_Titles::get_instance()->get_separator_options();
        if ( isset( $wpseo_titles['separator'] ) && isset( $sep_options[ $wpseo_titles['separator'] ] ) ) {
            $sep = $sep_options[ $wpseo_titles['separator'] ];
        } else {
            $sep = '-'; //setting default separator if Admin didn't set it from backed
        }

        $site_title = get_bloginfo('name');

        $meta_title = 'From ' . $sep . ' ' . $site_title;

        $title_array = [ $region_name, $listing->get_name(), $meta_title ];

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

        $new_title = implode( '-', $title );

        $title = $new_title;
    }

    return $title;

}, 99, 2 );
