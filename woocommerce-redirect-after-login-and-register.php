<?php

/**
 * Redirect to home after login.
 *
 * @param $redirect
 * @param $user
 *
 * @return false|string
 */
function iconic_login_redirect( $redirect, $user ) {
    return home_url( '/' );
}

add_filter( 'woocommerce_login_redirect', 'iconic_login_redirect' );


/**
 * Redirect to home after registration.
 *
 * @param $redirect
 *
 * @return string
 */
function iconic_register_redirect( $redirect ) {
    return home_url( '/' );
}

add_filter( 'woocommerce_registration_redirect', 'iconic_register_redirect' );
