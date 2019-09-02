<?php

add_action( 'init', function() {
		$terms = get_terms( [
			'taxonomy'		=> 'region',
			'hide_empty'	=> false,
		] );

		if ( is_wp_error( $terms ) ) {
			return;
		}

		foreach ( $terms as $term ) {
			$term = new MyListing\Src\Term( $term );
			$image = $term->get_image();

			if ( ! $image ) {
				update_term_meta( $term->get_id(), 'image', 3689 );
			}
		}
} );
