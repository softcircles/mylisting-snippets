<?php

/*
* Change Listing Package Selection Template Path
*/

add_filter( 'job_manager_locate_template', function( $template, $template_name, $template_path ) {

    if ( 'listing-package-selection' != $template_name ) {
        return $template;
    }

    $template = get_stylesheet_directory() . "/includes/integrations/wp-job-manager/templates/{$template_name}";

    if ( ! file_exists( $template ) ) {
        $template = get_template_directory() . "/includes/integrations/wp-job-manager/templates/{$template_name}";
    }

    return $template;

}, 99, 3 );
