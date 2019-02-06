<?php

add_action( 'init', function() {
  
    if ( ! isset( $_GET['id'] ) ) {
        return false;
    }
  
    $listing = \MyListing\Src\Listing::get( $_GET['id'] );
  
    $custom_field_keys = $listing->get_field( 'sale-price' );
  
    echo $custom_field_keys;
    exit();
} );
