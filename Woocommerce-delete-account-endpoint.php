<?php

function wc_delete_account_endpoint() {
    add_rewrite_endpoint( 'delete-account', EP_ROOT | EP_PAGES );
}
  
add_action( 'init', 'wc_delete_account_endpoint' );

function wc_delete_account_query_vars( $vars ) {
    $vars[] = 'delete-account';
    return $vars;
}

add_filter( 'query_vars', 'wc_delete_account_query_vars', 0 );

function bbloomer_add_premium_support_link_my_account( $items ) {
    $items['delete-account'] = __( 'Delete My Account' );
    return $items;
}
  
add_filter( 'woocommerce_account_menu_items', 'bbloomer_add_premium_support_link_my_account' );

function wc_delete_enpoint_redirect() {
	global $wp_query, $wp;

	if ( is_user_logged_in() ) {

		// Logout.
		if ( isset( $wp->query_vars['delete-account'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'delete-account' ) ) { 
			wp_delete_user( get_current_user_id() );
			wp_safe_redirect( str_replace( '&amp;', '&', wc_get_page_permalink( 'myaccount' ) ) );
			exit;
		}
		
		if ( isset( $wp->query_vars['delete-account'] ) ) {
			wp_safe_redirect( esc_url_raw( wc_delete_account_url() ) );
			exit;
		}
	}
}
  
add_action( 'template_redirect', 'wc_delete_enpoint_redirect' );

function wc_delete_account_url( $redirect = '' ) {
	$redirect = $redirect ? $redirect : wc_get_page_permalink( 'myaccount' );

	return wp_nonce_url( wc_get_endpoint_url( 'delete-account', '', $redirect ), 'delete-account' );
}
