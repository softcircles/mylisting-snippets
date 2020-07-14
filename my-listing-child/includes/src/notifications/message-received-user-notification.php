<?php

namespace MyListing\Src\Notifications;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Message_Received_User_Notification extends Base_Notification {

	public $message;

	public static function hook() {
		// listing updated by user
		add_action( 'mylisting/messages/send-notification', function( $message ) {
			return new self( [ 'message' => $message ] );
		} );
	}

	public static function settings() {
		return [
			'name' => _x( 'Notify users when they receive a new private message', 'Notifications', 'my-listing' ),
			'description' => _x( 'Send an email to the user when they receive new private messages.', 'Notifications', 'my-listing' ),
		];
	}

	/**
	 * Validate and prepare notifcation arguments.
	 *
	 * @since 2.1
	 */
	public function prepare( $args ) {
		if ( empty( $args['message'] ) || ! $args['message'] instanceof \MyListing\Ext\Messages\Messages ) {
			throw new \Exception( 'Invalid message provided.' );
		}

		$this->message = $args['message'];
	}

	public function get_mailto() {
		return $this->message->receiver->user_email;
	}

	public function get_subject() {
		return sprintf(
			_x( 'New message receieved from %s.', 'Notifications', 'my-listing' ),
			wp_kses_post( $this->message->sender->display_name )
		);
	}

	public function get_message() {
		$template = new Notification_Template;

		$template->add_paragraph( sprintf(
			_x( 'Hi %s,', 'Notifications', 'my-listing' ),
			esc_html( $this->message->receiver->first_name )
		) );

		$template->add_paragraph( sprintf(
			_x( 'You have received a new private message from user <strong>%s</strong>:', 'Notifications', 'my-listing' ),
			esc_html( $this->message->sender->display_name  )
		) );

		$template->add_paragraph( '<em>'.wp_kses( stripslashes( $this->message->message ), ['br', 'a'] ).'</em>' );

		$template
			->add_break()
			->add_primary_button( sprintf(
				_x( 'Open %s', 'Notifications', 'my-listing' ),
				get_bloginfo('name')
			), home_url('/') );

		return $template->get_body();
	}

}
