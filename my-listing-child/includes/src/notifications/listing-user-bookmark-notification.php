<?php

namespace MyListing\Src\Notifications;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Listing_User_Bookmark_Notification extends Base_Notification {

	public $listing;

	public static function hook() {
		add_action( 'mylisting/bookmark:new-bookmark', function( $bookmark_id ) {
			return new self( [ 'bookmark-id' => $bookmark_id ] );
		} );
	}

	public static function settings() {
		return [
			'name' => _x( 'Notify user on listing bookmark', 'Notifications', 'my-listing' ),
			'description' => _x( 'Send an email to the listing owner bookmark one of their submitted listings.', 'Notifications', 'my-listing' ),
		];
	}

	/**
	 * Validate and prepare notifcation arguments.
	 *
	 * @since 2.1
	 */
	public function prepare( $args ) {
		if ( empty( $args['bookmark-id'] ) ) {
			throw new \Exception( 'Invalid Bookmark ID' );
		}

		$listing = \MyListing\Src\Listing::force_get( $args['bookmark-id'] );

		if ( ! ( $listing && $listing->get_author() && $listing->get_status() === 'publish' ) ) {
			throw new \Exception( 'Invalid listing ID: #'.$args['bookmark-id'] );
		}

		$this->listing = $listing;
		$this->author = $listing->get_author();
	}

	public function get_mailto() {
		return $this->author->user_email;
	}

	public function get_subject() {
		return sprintf( _x( 'Your listing "%s" has been Bookmarked', 'Notifications', 'my-listing' ), esc_html( $this->listing->get_name() ) );
	}

	public function get_message() {
		$template = new Notification_Template;

		$template->add_paragraph( sprintf(
			_x( 'Hi %s,', 'Notifications', 'my-listing' ),
			esc_html( $this->author->first_name )
		) );

		$template->add_paragraph( sprintf(
			_x( 'Your listing <strong>%s</strong> has been bookmarked.', 'Notifications', 'my-listing' ),
			esc_html( $this->listing->get_name() )
		) );

		$template->add_break()->add_primary_button(
			_x( 'View Listing', 'Notifications', 'my-listing' ),
			esc_url( $this->listing->get_link() )
		);

		return $template->get_body();
	}

}
