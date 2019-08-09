<?php

namespace MyListing;

class Bookmarks {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		add_action( 'wp_ajax_bookmark_listing', [ $this, 'bookmark_listing' ] );
		add_action( 'wp_ajax_nopriv_bookmark_listing', [ $this, 'bookmark_listing' ] );

		add_action('template_redirect', function() {
			// Handle actions.
			if ( isset( $_GET['action'] ) && $_GET['action'] && is_wc_endpoint_url( 'my-bookmarks' ) ) {
				$this->handle_action( sanitize_text_field( $_GET['action'] ) );
			}
		}, 500);

		mylisting()->woocommerce()->add_dashboard_page( [
			'endpoint' => 'my-bookmarks',
			'title' => __( 'Bookmarks', 'my-listing' ),
			'template' => locate_template( 'templates/dashboard/bookmarks.php' ),
			'show_in_menu' => true,
			'order' => 4,
		] );
	}

	public function handle_action( $action = '' )
	{
		$listingID = isset($_GET['listing_id']) && $_GET['listing_id'] ? absint( $_GET['listing_id'] ) : false;

		switch ($action) {
			case 'remove_bookmark':
				if ($listingID && $this->remove_bookmark( $listingID )) {
					wc_add_notice( __( 'Listing removed from your bookmarks.', 'my-listing' ), 'success' );
					wp_safe_redirect( wc_get_endpoint_url( 'my-bookmarks' ) );
					exit;
				}
			break;
		}
	}

	public function remove_bookmark( $listingID )
	{
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$listing = get_post( $listingID );

		if ( ! $listing || get_post_type( $listing ) !== 'job_listing') {
			return false;
		}

		$user_meta = get_user_meta( get_current_user_id(), '_case27_user_bookmarks', true ) ? : [];
		$listing_meta = get_post_meta( $listing->ID, '_case27_listing_bookmarks', true ) ? : [];

		// Remove bookmarks.
		$listing_meta = array_diff( $listing_meta, [ get_current_user_id() ] );
		$user_meta = array_diff( $user_meta, [ $listing->ID ] );

		// Update meta.
		update_post_meta( $listing->ID, '_case27_listing_bookmarks', $listing_meta );
		update_user_meta( get_current_user_id(), '_case27_user_bookmarks', $user_meta );

		return true;
	}


	public function is_bookmarked( $listing_id, $user_id )
	{
		if ( ! $listing_id || !$user_id ) return false;

		$user_meta = get_user_meta( $user_id, '_case27_user_bookmarks', true ) ? : [];

		return in_array( $listing_id, $user_meta );
	}


	public function bookmark_listing()
	{
		// Security nonce.
		check_ajax_referer( 'c27_bookmark_nonce', 'c27_bookmark_nonce' );

		$listing_id = isset( $_POST['listing_id'] ) && $_POST['listing_id'] ? (int) $_POST['listing_id'] : false;
		$user_id = get_current_user_id();

		// Check if current user is authorized to perform this action.
		if ( ! is_user_logged_in() || ! $user_id || ! $listing_id ) {
			echo json_encode( ['status' => 'unauthorized'] ); die;
		}

		$listing = get_post($listing_id);

		if (!$listing || get_post_type($listing) !== 'job_listing') {
			echo json_encode( ['status' => 'invalid_request'] ); die;
		}

		// Validation completed. Proceed with updating user and listing meta.
		$listing_meta = get_post_meta($listing->ID, '_case27_listing_bookmarks', true) ? : [];
		$user_meta = get_user_meta($user_id, '_case27_user_bookmarks', true) ? : [];
		$is_currently_bookmarked = false;
		$is_bookmarked = false;

		if (in_array($listing->ID, $user_meta)) {
			$is_currently_bookmarked = true;
		}

		// Add bookmark if it doesn't exist.
		if (!$is_currently_bookmarked) {
			$listing_meta[] = $user_id;
			$user_meta[] = $listing->ID;
			$is_bookmarked = true;

			 /**
	         * Triggered when a new listing bookmark has been successfully.
	         *
	         * @since 2.1.6
	         */
	        do_action( 'mylisting/bookmark:new-bookmark', $listing->ID, $user_meta, $listing_meta, $listing );
		}

		// Remove bookmark if it exists.
		if ($is_currently_bookmarked) {
			// Remove the user id from this listing's bookmarks meta,
			// and the listing id from this user's bookmarks meta.
			$listing_meta = array_diff($listing_meta, [$user_id]);
			$user_meta = array_diff($user_meta, [$listing->ID]);
			$is_bookmarked = false;
		}

		// Update meta.
		update_post_meta($listing->ID, '_case27_listing_bookmarks', $listing_meta);
		update_user_meta($user_id, '_case27_user_bookmarks', $user_meta);

		echo json_encode( [
			'status' => 'OK',
			'is_bookmarked' => $is_bookmarked,
		] );
		die;
	}

}

mylisting()->register( 'bookmarks', Bookmarks::instance() );
