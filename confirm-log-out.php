<?php

function wc_bypass_logout_confirmation() {
    global $wp;
  
    if ( isset( $wp->query_vars['customer-logout'] ) ) {
        wp_logout();
        wp_safe_redirect( str_replace( ['&#038;', '&amp;'], '&', wp_logout_url( wc_get_page_permalink( 'myaccount' ) ) ) );
        exit;
    }
}
  
add_action( 'template_redirect', 'wc_bypass_logout_confirmation' );
