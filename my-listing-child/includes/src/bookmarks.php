<?php
/**
 * Adds `Bookmark Listing` functionality.
 *
 * @since 1.0
 */

namespace MyListing\Src;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Bookmarks {

	public static function boot() {
		add_action( 'mylisting_ajax_bookmark_listing', [ __CLASS__, 'handle_bookmark_request' ] );
		add_action( 'template_redirect', [ __CLASS__, 'handle_dashboard_actions' ] );

		\MyListing\add_dashboard_page( [
			'endpoint' => \MyListing\bookmarks_endpoint_slug(),
			'title' => __( 'Bookmarks', 'my-listing' ),
			'template' => locate_template( 'templates/dashboard/bookmarks.php' ),
			'show_in_menu' => true,
			'order' => 4,
		] );
	}

	/**
	 * Handle `bookmark_listing` AJAX request.
	 *
	 * @since 1.0
	 */
	public static function handle_bookmark_request() {
		$listing_id = ! empty( $_GET['listing_id'] ) ? absint( $_GET['listing_id'] ) : false;
		$user_id = get_current_user_id();

		// validate request
		if ( ! ( $user_id && $listing_id ) ) {
			return;
		}

		// validate listing
		$listing = \MyListing\Src\Listing::get( $listing_id );
		if ( ! $listing ) {
			return;
		}

		// if bookmark between user and listing already exists, remove it; otherwise, add it
		if ( self::exists( $listing_id, $user_id ) ) {
			self::remove( $listing_id, $user_id );
			$bookmarked = false;
		} else {

			do_action( 'mylisting/bookmark:new-bookmark', $listing_id, $user_id, $listing );
			
			self::add( $listing_id, $user_id );
			$bookmarked = true;
		}

		// send json response
		return wp_send_json( [
			'status' => true,
			'bookmarked' => $bookmarked,
		] );
	}

	/**
	 * Handle actions in User Dashboard > Bookmarks page.
	 *
	 * @since 1.0
	 */
	public static function handle_dashboard_actions() {
		if ( ! ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( \MyListing\bookmarks_endpoint_slug() ) && is_user_logged_in() ) ) {
			return;
		}

		$listing_id = ! empty( $_GET['listing_id'] ) ? absint( $_GET['listing_id'] ) : false;
		$action = ! empty( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

		// `remove_bookmark` action
		if ( $action === 'remove_bookmark' && $listing_id ) {
			self::remove( $listing_id, get_current_user_id() );
			wc_add_notice( __( 'Listing removed from your bookmarks.', 'my-listing' ), 'success' );
			wp_safe_redirect( wc_get_endpoint_url( \MyListing\bookmarks_endpoint_slug() ) );
			exit;
		}
	}

	/**
	 * Save the given listing to the given user's bookmarks.
	 *
	 * @since 2.3.3
	 */
	public static function add( $listing_id, $user_id ) {
		$listing_id = absint( $listing_id );
		$user_id = absint( $user_id );

		// add user to listing bookmarks
		$listing_bookmarks = self::get_by_listing( $listing_id );
		if ( ! in_array( $user_id, $listing_bookmarks, true ) ) {
			$listing_bookmarks[] = $user_id;
		}

		// add listing to user bookmarks
		$user_bookmarks = self::get_by_user( $user_id );
		if ( ! in_array( $listing_id, $user_bookmarks ) ) {
			$user_bookmarks[] = $listing_id;
		}

		// update meta
		update_post_meta( $listing_id, '_case27_listing_bookmarks', $listing_bookmarks );
		update_user_meta( $user_id, '_case27_user_bookmarks', $user_bookmarks );
	}

	/**
	 * Remove the given listing from the given user's bookmarks.
	 *
	 * @since 2.3.3
	 */
	public static function remove( $listing_id, $user_id ) {
		$listing_id = absint( $listing_id );
		$user_id = absint( $user_id );

		// remove user from listing bookmarks
		$listing_bookmarks = array_diff( self::get_by_listing( $listing_id ), [ $user_id ] );

		// remove listing from user bookmarks
		$user_bookmarks = array_diff( self::get_by_user( $user_id ), [ $listing_id ] );

		// update meta
		update_post_meta( $listing_id, '_case27_listing_bookmarks', $listing_bookmarks );
		update_user_meta( $user_id, '_case27_user_bookmarks', $user_bookmarks );
	}

	/**
	 * Get users who have bookmarked the given listing.
	 *
	 * @since 2.3.3
	 */
	public static function get_by_listing( $listing_id ) {
		$bookmarks = get_post_meta( $listing_id, '_case27_listing_bookmarks', true );
		if ( ! is_array( $bookmarks ) || empty( $bookmarks ) ) {
			$bookmarks = [];
		}

		// remove empty values and cast all values to int
		$bookmarks = array_map( 'absint', array_filter( $bookmarks ) );
		return array_unique( $bookmarks );
	}

	/**
	 * Get given user's bookmarked listings.
	 *
	 * @since 2.3.3
	 */
	public static function get_by_user( $user_id ) {
		$bookmarks = get_user_meta( $user_id, '_case27_user_bookmarks', true );
		if ( ! is_array( $bookmarks ) || empty( $bookmarks ) ) {
			$bookmarks = [];
		}

		// remove empty values and cast all values to int
		$bookmarks = array_map( 'absint', array_filter( $bookmarks ) );
		return array_unique( $bookmarks );
	}

	/**
	 * Check if the give listing is bookmarked by the given user.
	 *
	 * @since 2.3.3
	 */
	public static function exists( $listing_id, $user_id ) {
		if ( ! ( is_int( $listing_id ) && is_int( $user_id ) ) ) {
			return false;
		}

		$user_bookmarks = self::get_by_user( $user_id );
		return in_array( absint( $listing_id ), $user_bookmarks, true );
	}
}
