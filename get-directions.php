<?php

add_filter( 'gettext', 'mylisting_translate_strings', 20, 3 );

function mylisting_translate_strings( $translated_text, $original_text, $domain ) {

    if ( 'my-listing' != $domain ) {
        return $translated_text;
    }

    switch ( $translated_text ) {

        case 'Get Directions' :

            $translated_text = 'Μάθε πως θα φτάσεις εκεί';
            break;
    }

    return $translated_text;
}
