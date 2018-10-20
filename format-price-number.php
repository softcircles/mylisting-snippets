<?php

/*
* Price number replace the comma with the dot
*/
add_filter( 'number_format_i18n', function( $formatted ) {

    $formatted = str_replace(',', '.', $formatted );

    return $formatted;
});
