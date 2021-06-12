<?php

namespace MyListing\Src\Notifications;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Listing_Updated_Admin_Notification extends Base_Notification {

	public $listing;

	public static function hook() {
		// listing updated by user
		add_action( 'mylisting/submission/listing-updated', function( $listing_id ) {
			return new self( [ 'listing-id' => $listing_id ] );
		} );
	}

	public static function settings() {
		return [
			'name' => _x( 'Notify admin when a listing is edited', 'Notifications', 'my-listing' ),
			'description' => _x( 'Send an email to the admin whenever a listing is modified by the user.', 'Notifications', 'my-listing' ),
		];
	}

	/**
	 * Validate and prepare notifcation arguments.
	 *
	 * @since 2.1
	 */
	public function prepare( $args ) {
		if ( empty( $args['listing-id'] ) || ! ( $listing = \MyListing\Src\Listing::get( $args['listing-id'] ) ) || ! $listing->get_author() ) {
			throw new \Exception( 'Invalid listing ID: #'.$args['listing-id'] );
		}

		$this->listing = $listing;
	}

	public function get_mailto() {
		return get_option('admin_email');
	}

	public function get_subject() {
		return sprintf(
			_x( 'User "%s" edited their listing "%s".', 'Notifications', 'my-listing' ),
			esc_html( $this->get_updated_author() ),
			esc_html( $this->listing->get_name() )
		);
	}

	public function get_message() {
		$template = new Notification_Template;

		$template->add_paragraph( sprintf(
			_x( 'User <strong>%s</strong> has edited their listing <strong>%s</strong>.', 'Notifications', 'my-listing' ),
			esc_html( $this->get_updated_author() ),
			esc_html( $this->listing->get_name() )
		) );

		$template
			->add_break()
			->add_primary_button(
				_x( 'View Listing', 'Notifications', 'my-listing' ),
				esc_url( $this->listing->get_link() )
			)
			->add_button(
				_x( 'Edit Listing', 'Notifications', 'my-listing' ),
				esc_url( c27()->get_edit_post_link( $this->listing->get_id() ) )
			)
			->add_button(
				_x( 'View user profile', 'Notifications', 'my-listing' ),
				esc_url( c27()->get_edit_user_link( $this->listing->get_author_id() ) )
			);

		return $template->get_body();
	}

	function get_updated_author() {
		update_post_meta( $this->listing->get_id(), '_edit_last', get_current_user_id() );
        $last_user = get_userdata( get_current_user_id() );

        return apply_filters( 'the_modified_author', $last_user->display_name );
	}
}
