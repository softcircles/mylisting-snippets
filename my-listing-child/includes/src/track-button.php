<?php
/**
 * Adds `Track Button Listing` functionality.
 *
 * @since 2.8
 */

namespace MyListing\Src;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Track_Button {

	public static function boot() {
		add_action( 'mylisting_ajax_track_listing_button', [ __CLASS__, 'handle_track_button_request' ] );
		add_action( 'mylisting_ajax_nopriv_track_listing_button', [ __CLASS__, 'handle_track_button_request' ] );
	}

	/**
	 * Handle `track_button_listing` AJAX request.
	 *
	 * @since 1.0
	 */
	public static function handle_track_button_request() {
		$listing_id = ! empty( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : false;
		$meta_key	= ! empty( $_POST['btn_id'] ) ? sanitize_text_field( $_POST['btn_id'] ) : false;
		$action_type = substr( $meta_key, 0, 4 ) === 'cta-' ? 'cover_actions' : 'quick_actions';
		$action_prefix = $action_type === 'cover_actions' ? 'cta' : 'qa';

		// validate request
		if ( ! ( $meta_key && $listing_id ) ) {
			return;
		}

		// validate listing
		$listing = \MyListing\Src\Listing::get( $listing_id );
		if ( ! $listing || ! $listing->type || $listing->get_status() !== 'publish' ) {
			return;
		}

		$layout = $listing->type->get_layout();
		$actions = $layout[ $action_type ] ?? [];

		foreach ( $actions as $action ) {
			if ( empty( $action['id'] ) ) {
				$action['id'] = sprintf( '%s-%s', $action_prefix, substr( md5( json_encode( $action ) ), 0, 6 ) );
			}

			if ( $action['id'] !== $meta_key ) {
				continue;
			}

			self::add( $listing->get_id(), sprintf( '%s-%s', $action_prefix, substr( md5(
				json_encode( [ $action['action'], $action['label'] ] )
			), 0, 6 ) ) );
		}

		self::add( $listing_id, $meta_key );

		// send json response
		return wp_send_json( [
			'status' => true,
			'tracked' => true,
		] );
	}

	/**
	 * Save the given listing to the given user's tracks.
	 *
	 * @since 2.3.3
	 */
	public static function add( $listing_id, $meta_key ) {
		$tracks = (array) json_decode( get_post_meta( $listing_id, '__track_stats', true ), ARRAY_A );
		if ( ! isset( $tracks[ $meta_key ] ) ) {
			$tracks[ $meta_key ] = 0;
		}

		$tracks[ $meta_key ]++;
		delete_post_meta( $listing_id, '__track_stats' );
		update_post_meta( $listing_id, '__track_stats', wp_json_encode( $tracks ) );
	}
}
