<?php

add_action( 'init', function() {
    global $wpdb;

	// Change status to expired.
	$job_ids = $wpdb->get_col(
		$wpdb->prepare( "
			SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
			LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
			WHERE postmeta.meta_key = '_sluttdato'
			AND postmeta.meta_value > 0
			AND postmeta.meta_value < %s
			AND posts.post_status = 'publish'
			AND posts.post_type = 'job_listing'",
			date( 'Y-m-d', current_time( 'timestamp' ) )
		)
	);

	if ( $job_ids ) {
		foreach ( $job_ids as $job_id ) {
			$job_data                = array();
			$job_data['ID']          = $job_id;
			$job_data['post_status'] = 'expired';
			wp_update_post( $job_data );
		}
	}

	// Delete old expired jobs.
	if ( apply_filters( 'job_manager_delete_expired_jobs', false ) ) {
		$job_ids = $wpdb->get_col(
			$wpdb->prepare( "
				SELECT posts.ID FROM {$wpdb->posts} as posts
				WHERE posts.post_type = 'job_listing'
				AND posts.post_modified < %s
				AND posts.post_status = 'expired'",
				date( 'Y-m-d', strtotime( '-' . apply_filters( 'job_manager_delete_expired_jobs_days', 30 ) . ' days', current_time( 'timestamp' ) ) )
			)
		);

		if ( $job_ids ) {
			foreach ( $job_ids as $job_id ) {
				wp_trash_post( $job_id );
			}
		}
	}
} );
