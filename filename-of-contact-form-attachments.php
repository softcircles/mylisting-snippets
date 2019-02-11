<?php

add_filter( 'wpcf7_upload_file_name', function( $filename, $file_name, $tag ) {

    $name = $tag->name;
    $id = $tag->get_id_option();

    $file = isset( $_FILES[$name] ) ? $_FILES[$name] : null;

    if ( isset( $_POST['_case27_post_id'] ) && ! empty( $_POST['_case27_post_id'] ) && $file ) {
        $path = pathinfo( $file[ 'name' ] );
        $file[ 'name' ] = sanitize_title( get_the_title( $_POST['_case27_post_id'] ) ) . '.' . $path[ 'extension' ];
        $filename = $file['name'];
    }

    return $filename;

}, 99, 3 );
