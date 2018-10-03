<?php

/**
 * Add Yoast Seo Support for categories, regions and tags
 */
 
add_action( 'wpseo_opengraph', 'remove_yoast_og_tags' );
add_action( 'add_meta_boxes', 'remove_yoast_region_metabox' );
add_action( 'wp_head', 'filter_page_tags_title', 5 );

function remove_yoast_og_tags() {

    $taxonomies = [
        ['tax' => 'region',                  'query_var' => 'explore_region',   'name_filter' => 'single_term_title'],
        ['tax' => 'job_listing_category',    'query_var' => 'explore_category', 'name_filter' => 'single_cat_title'],
        ['tax' => 'case27_job_listing_tags', 'query_var' => 'explore_tag',      'name_filter' => 'single_tag_title'],
    ];

    foreach ( $taxonomies as $tax ) {

        if ( get_query_var( $tax['query_var'] ) && ( $term = get_term_by( 'slug', sanitize_title( get_query_var( $tax['query_var'] ) ), $tax['tax'] ) ) ) {

            add_filter( 'wpseo_og_og_title',       '__return_false', 50 );
            add_filter( 'wpseo_og_og_description', '__return_false', 50 );
        }
    }
}

function remove_yoast_region_metabox() {

    $taxonomies = [
        ['tax' => 'region',                  'query_var' => 'explore_region',   'name_filter' => 'single_term_title'],
        ['tax' => 'job_listing_category',    'query_var' => 'explore_category', 'name_filter' => 'single_cat_title'],
        ['tax' => 'case27_job_listing_tags', 'query_var' => 'explore_tag',      'name_filter' => 'single_tag_title'],
    ];

    foreach ( $taxonomies as $tax ) {
        if ( get_query_var( $tax['query_var'] ) && ( $term = get_term_by( 'slug', sanitize_title( get_query_var( $tax['query_var'] ) ), $tax['tax'] ) ) ) {

            if ( ! apply_filters( 'mylisting/edit/hide_yoast_metabox', false ) ) {
                return false;
            }

            remove_meta_box( 'wpseo_meta', 'job_listing', 'normal');
        }
    }
}

function filter_page_tags_title( $title ) {
    $taxonomies = [
        ['tax' => 'region',                  'query_var' => 'explore_region',   'name_filter' => 'single_term_title'],
        ['tax' => 'job_listing_category',    'query_var' => 'explore_category', 'name_filter' => 'single_cat_title'],
        ['tax' => 'case27_job_listing_tags', 'query_var' => 'explore_tag',      'name_filter' => 'single_tag_title'],
    ];

    foreach ( $taxonomies as $tax ) {
        if ( get_query_var( $tax['query_var'] ) && ( $term = get_term_by( 'slug', sanitize_title( get_query_var( $tax['query_var'] ) ), $tax['tax'] ) ) ) {

            $url = get_term_link( $term->term_id, $term->taxonomy );

            $default_title = apply_filters( 'single_term_title', $term->name );

            $title = get_bloginfo( 'name', 'display' );

            $meta = get_option( 'wpseo_taxonomy_meta' );

            $description = '';

            if ( isset( $meta[$term->taxonomy][$term->term_id]['wpseo_desc'] ) ) {
                $description = $meta[$term->taxonomy][$term->term_id]['wpseo_desc'];
            }

            $title =  sprintf( '%s, Cornwall – Find Amazing Things In %s - %s', $default_title, $default_title, $title );

            printf( "<meta property=\"og:title\" content=\"%s\" />\n", esc_attr( $title ) );
            printf( "<meta property=\"og:description\" content=\"%s\" />\n", esc_attr( $description ) );
            printf( "<meta property=\"og:url\" content=\"%s\" />\n", esc_url( $url ) );

        }
    }
}
