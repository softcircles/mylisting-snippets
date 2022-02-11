<?php

namespace MyListing\Src\Notifications;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Listing_Approved_User_Notification extends Base_Notification {

	public
		$listing,
		$author;

	public static function hook() {
		add_action( 'transition_post_status', function( $new_status, $old_status, $post ) {
			// validate listing
			if ( ! ( $post && $post->post_type === 'job_listing' ) ) {
				return;
			}

			// only run this when a listing gets published from the following statuses: preview, pending, pending_payment, and expired.
			if ( ! ( $new_status === 'publish' && in_array( $old_status, [ 'preview', 'pending', 'pending_payment', 'expired' ], true ) ) ) {
				return;
			}
			
			$is_listing_renewal = get_post_meta( $post->ID, 'is_listing_renewal', true );
			
			if ( $is_listing_renewal ) {
				return;
			}
			
			return new self( [ 'listing-id' => $post->ID ] );
		}, 40, 3 );
	}

	public static function settings() {
		return [
			'name' => _x( 'Notify users on listing approvals', 'Notifications', 'my-listing' ),
			'description' => _x( 'Send an email to the user whenever one of their submitted listings has been approved and published.', 'Notifications', 'my-listing' ),
		];
	}

	/**
	 * Validate and prepare notifcation arguments.
	 *
	 * @since 2.1
	 */
	public function prepare( $args ) {
		if ( empty( $args['listing-id'] ) ) {
			throw new \Exception( 'No listing ID provided.' );
		}

		$listing = \MyListing\Src\Listing::force_get( $args['listing-id'] );
		if ( ! ( $listing && $listing->get_author() && $listing->get_status() === 'publish' ) ) {
			throw new \Exception( 'Invalid listing ID: #'.$args['listing-id'] );
		}

		$this->listing = $listing;
		$this->author = $listing->get_author();
	}

	public function get_mailto() {
		return $this->author->user_email;
	}

	public function get_subject() {
		return sprintf( _x( 'Your listing "%s" has been approved', 'Notifications', 'my-listing' ), esc_html( $this->listing->get_name() ) );
	}

	public function get_message() {
		$template = new Notification_Template;

		$template->add_paragraph( sprintf(
			_x( 'Hi %s,', 'Notifications', 'my-listing' ),
			esc_html( $this->author->first_name )
		) );

		$template->add_paragraph( sprintf(
			_x( 'Your submitted listing <strong>%s</strong> has been approved and published.', 'Notifications', 'my-listing' ),
			esc_html( $this->listing->get_name() )
		) );

		$template->add_break()->add_primary_button(
			_x( 'View Listing', 'Notifications', 'my-listing' ),
			esc_url( $this->listing->get_link() )
		);

		return $template->get_body();
	}

}
