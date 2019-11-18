<?php

add_action( 'mylisting/admin/save-listing-data', function( $listing_id ) {

	if ( isset( $_POST['job_location__latitude'] ) && empty( $_POST['job_location__latitude'] ) ) {
		update_post_meta( $listing_id, 'geolocation_lat', '' );
	} elseif ( isset( $_POST['job_location__latitude'] ) && ! empty( $_POST['job_location__latitude'] ) ) {
		update_post_meta( $listing_id, 'geolocation_lat', $_POST['job_location__latitude'] );
	}

	if ( isset( $_POST['job_location__longitude'] ) && empty( $_POST['job_location__longitude'] ) ) {
		update_post_meta( $listing_id, 'geolocation_long', '' );
	} elseif ( isset( $_POST['job_location__longitude'] ) && ! empty( $_POST['job_location__longitude'] ) ) {
		update_post_meta( $listing_id, 'geolocation_long', $_POST['job_location__longitude'] );
	}

} );
