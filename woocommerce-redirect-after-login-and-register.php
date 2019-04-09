<?php

/**
 * Redirect to shop after login.
 *
 * @param $redirect
 * @param $user
 *
 * @return false|string
 */
function iconic_login_redirect( $redirect, $user ) {
    return wc_get_page_permalink( 'shop' );
}

add_filter( 'woocommerce_login_redirect', 'iconic_login_redirect' );


/**
 * Redirect after registration.
 *
 * @param $redirect
 *
 * @return string
 */
function iconic_register_redirect( $redirect ) {
	return wc_get_page_permalink( 'shop' );
}

add_filter( 'woocommerce_registration_redirect', 'iconic_register_redirect' );
