<?php

function homepage_template_redirect()
{
    if ( is_user_logged_in() ) {
        // For Logged in User
        $page = get_page_by_title( 'Explore' );
        update_option( 'page_on_front', $page->ID );
        update_option( 'show_on_front', 'page' );
    } else {
        // For Non Logged in User
        $page = get_page_by_title( 'Home' );
        update_option( 'page_on_front', $page->ID );
        update_option( 'show_on_front', 'page' );
    }
}
add_action( 'init', 'homepage_template_redirect' );

function custom_page_template_redirect()
{
	if ( is_page( 'Explore' ) && ! is_user_logged_in() ) {
    	wp_redirect(site_url('/') );
        exit();
    }
}
add_action( 'template_redirect', 'custom_page_template_redirect' );
