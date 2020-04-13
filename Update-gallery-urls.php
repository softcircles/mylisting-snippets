add_action( 'init', function() {
	global $wpdb;
    if ( empty( $_GET['update_gallery'] ) || ! current_user_can( 'administrator' ) ) {
        return;
    }

    $next_data = 50;
    $offset = 0;

    do {
        $listings = (array) get_posts( [
            'post_type' => 'job_listing',
            'offset'   => $offset,
            'posts_per_page' => $next_data,
            'post_status' => [ 'any' ],
            'meta_query' => [
                [ 'key' => '_job_gallery', 'compare' => 'EXISTS' ],
            ],
        ] );

        printf(
            "Fetching Gallery data from listing %d to %d <br />",
            $offset + 1,
            $offset + $next_data
        );

        flush();
        ob_flush();

        foreach ( $listings as $listing ) {

        	$post_meta_query = sprintf( "SELECT * FROM $wpdb->postmeta WHERE post_id = %s AND meta_key = '%s'", $listing->ID, '_job_gallery' );

        	$items = $wpdb->get_results( $post_meta_query );

        	foreach( $items as $item ) {
				$value = $item->meta_value;
				if ( trim( $value ) == '' ) {
					printf( '<p style="color: red;">Failed to update gallery listing #%d</p>', $listing->ID );
	                continue;
				}

				$new_value = ml_unserialize_data_replace( 'https://cos.cxfile.cn/uploads/', 'https://www.chuxin365.com/wp-content/uploads/', $value );

				if ( $new_value == $value ) {
					printf( '<p style="color: red;">Failed to Update Gallery this gallery is already updated listing #%d</p>', $listing->ID );
				}

				$updated_query = $wpdb->query( "UPDATE $wpdb->postmeta SET meta_value = '". $new_value ."' WHERE meta_id = ". $item->meta_id );
				printf( '<p style="color: green;">Gallery successful update for listing #%d</p>', $listing->ID );
			}
        }

        $offset = ( ! $offset ) ? $next_data : $offset + $next_data;
    } while( ! empty( $listings ) );

    flush_rewrite_rules();
    
    exit('All listings are updated, you can close this window.');
}, 250 );
