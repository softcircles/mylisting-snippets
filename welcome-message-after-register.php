<?php

add_filter( 'woocommerce_registration_redirect', function( $redirect ) {
	return add_query_arg( array( 'success_message' => get_current_user_id() ), wc_get_page_permalink( 'myaccount' ) );
} );

add_action( 'wp', function() {

	if ( is_admin() ) {
		return;
	}
	
	$registration_message = 'Thanks for signup';
	
	if ( false === wc_has_notice( $registration_message, 'notice' ) && is_account_page() && isset( $_GET['success_message'] ) ) {
		wc_add_notice( $registration_message, 'notice' );
	}
} );
