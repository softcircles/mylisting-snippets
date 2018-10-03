<?php

/*
* Add WC Password Strength Meter Support
*/
add_action( 'wp_enqueue_scripts', function() {
 
    if ( is_user_logged_in() ) {
        return false;
    }
 
    wp_enqueue_script( 'wc-password-strength-meter' );
}, 99 );
