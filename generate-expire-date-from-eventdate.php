<?php

/*
* Generate Expire Date From Eventdate by the listing from on a frontend
*/

add_action( 'job_manager_update_job_data', function( $listingID, $values ) {

    if ( get_post_meta( $listingID, '_case27_listing_type', true ) == 'event' ) {
        $eventdate = get_post_meta( $listingID, '_eventdate', true );
        $newexpire = date('Y-m-d', strtotime( "+7 days", strtotime( $eventdate ) ) );
        update_post_meta( $listingID, '_job_expires', $newexpire );
    }

}, 99, 2 );
