<?php

namespace MyListing\Src\Notifications;

if ( ! defined('ABSPATH') ) {
	exit;
}

abstract class Base_Notification {

	public function __construct( $args = [] ) {
		try {
			$this->prepare( $args );

			// @todo: implement notification system
			// $this->send_notification();

			// send email
			if ( $this->should_send_email() ) {
				$this->send_email();
				mlog()->note( 'Notification email sent: '.c27()->class2file( static::class ) );
			}
		} catch ( \Exception $e ) {
			mlog()->warn( 'Email failed: '.$e->getMessage() );
			return;
		}
	}

	/**
	 * Validate and prepare notifcation arguments.
	 *
	 * @since 2.1
	 */
	abstract public function prepare( $args );

	/**
	 * Get the notification subject to be used as the
	 * notification title, and email subject.
	 *
	 * @since 2.1
	 */
	abstract public function get_subject();

	/**
	 * Get the notification contents to be used as the
	 * notification message, and email body.
	 *
	 * @since 2.1
	 */
	abstract public function get_message();

	/**
	 * Get the email address this notification should be sent to.
	 *
	 * @since 2.1
	 */
	abstract public function get_mailto();

	/**
	 * Validate and send the notification email.
	 *
	 * @since 2.1
	 */
	public function send_email() {
		$args = [
			'to' => $this->get_mailto(),
			'subject' => sprintf( '[%s] %s', get_bloginfo('name'), $this->get_subject() ),
			'message' => $this->get_email_template(),
			'headers' => [
				'Content-type: text/html; charset: '.get_bloginfo( 'charset' ),
			],
		];

		if ( ! ( is_email( $args['to'] ) && $args['subject'] ) ) {
			throw new \Exception( 'Missing email parameters.' );
		}

		return wp_mail( sanitize_email( $args['to'] ), $args['subject'], $args['message'], $args['headers'] );
	}

	/**
	 * Retrieve the template to be used by the notification.
	 *
	 * @since 2.1
	 */
	public function get_email_template() {
		// determine which template file to use
		$template_file = locate_template( sprintf( 'templates/emails/%s.php', c27()->class2file( static::class ) ) );
		if ( ! $template_file ) {
			$template_file = locate_template( 'templates/emails/default.php' );
		}

		// load template
		ob_start();
		require $template_file;
		$template = ob_get_clean();

		// inline styles
		try {
			$emogrifier = new \MyListing\Utils\Emogrifier( $template );
			$rendered_template = $emogrifier->emogrify();
		} catch ( \Exception $e ) {
			// if inline styles can't be applied, use the original markup as the message body
			$rendered_template = $template;
		}

		return $rendered_template;
	}

	/**
	 * Determine whether the requested email has been enabled by the site owner.
	 *
	 * @since 2.1
	 */
	public function should_send_email() {
		$options = get_option( 'mylisting_notifications', [] );
		$notification = c27()->class2file( static::class );
		$should_send = true;

		if ( isset( $options[ $notification ], $options[ $notification ]['send_email'] ) && $options[ $notification ]['send_email'] === 'disabled' ) {
			$should_send = false;
		}

		return apply_filters( sprintf( 'mylisting/emails/%s:enabled', $notification ), $should_send );
	}
}
