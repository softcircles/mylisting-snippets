<?php

function ml_valid_email_domain( $user_login, $user_email, $errors ) {

	$blacklist = preg_split('/\r\n|[\r\n]/', 'ru' );

	$blacklist = array_map('strtolower', $blacklist);

	$email_parts = explode( '.', $user_email );

	$domain = strtolower( trim( array_pop( $email_parts ) ) );
	
	if ( in_array( $domain, $blacklist ) ) {
        $errors->add( 'bad_email_domain', __( '<strong>ERROR</strong>: This email domain is not allowed' ));
    }
}

add_action('register_post', 'ml_valid_email_domain', 10,3 );
add_action('woocommerce_register_post', 'ml_valid_email_domain', 10,3 );
