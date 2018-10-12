<?php

/*
* Overwirte Woocommerce Template Edit Account File
*/
add_filter( 'woocommerce_locate_template', function( $template, $template_name, $template_path ) {

    if ( 'myaccount/form-edit-account.php' != $template_name ) {
        return $template;
    }

    $new_template = get_stylesheet_directory() . '/includes/integrations/woocommerce/templates/myaccount/form-edit-account.php';

    if ( ! file_exists( $new_template ) ) {
        return $template;
    }

    return $new_template;

}, 99, 3 );
