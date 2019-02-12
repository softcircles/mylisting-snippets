<?php

add_filter( 'wpcf7_upload_file_name', function( $filename, $file_name, $tag ) {
    
    $name = $tag->name;
    $id = $tag->get_id_option();
    
    $file = isset( $_FILES[$name] ) ? $_FILES[$name] : null;
    if ( isset( $_POST['_case27_post_id'] ) && ! empty( $_POST['_case27_post_id'] ) && $file ) {
        $path = pathinfo( $file[ 'name' ] );
        
        $name = explode( '.', $file[ 'name' ] );
        
        $filename = 'twwork' . $name[0] . '.' . $path[ 'extension' ];

    }
    return $filename;
}, 99, 3 );
