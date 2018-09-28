<?php

/**
 *
 * Use this code snippet to save Google map location data as a separate meta fields
 */
  
add_action( 'job_manager_update_job_data', 'update_job_location_frontend_data', 20, 2 );
add_action( 'job_manager_save_job_listing', 'update_job_location_backend_data', 50, 2 );

function update_job_location_frontend_data( $listing_id, $values ) {

    if ( empty( $values['job']['job_location'] ) ) {
        return;
    }

    // Locations.
    $updated_result = update_post_meta( $listing_id, '_job_location', sanitize_text_field( $values['job']['job_location'] ) );

    if ( ! $updated_result && ! WP_Job_Manager_Geocode::has_location_data( $listing_id ) ) {
        // First time generation for job location data.
        WP_Job_Manager_Geocode::generate_location_data( $listing_id, sanitize_text_field( $values['job']['job_location'] ) );
    }
}

function update_job_location_backend_data( $listing_id, $listing ) {

    if ( ! is_admin() || empty( $_POST['_job_location'] ) ) {
        return;
    }

    // Locations.
    $updated_result = update_post_meta( $listing_id, '_job_location', sanitize_text_field( $_POST['_job_location'] ) );

    if ( ! $updated_result && ! WP_Job_Manager_Geocode::has_location_data( $listing_id ) ) {
        // First time generation for job location data.
        WP_Job_Manager_Geocode::generate_location_data( $listing_id, sanitize_text_field( $_POST['_job_location'] ) );
    }
}
