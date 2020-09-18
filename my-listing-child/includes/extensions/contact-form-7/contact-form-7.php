<?php

namespace MyListing\Ext\Contact_Form_7;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Contact_Form_7 {

	public static function boot() {
		new self;
	}

	public function __construct() {
		add_filter( 'wpcf7_form_hidden_fields', [ $this, 'add_custom_hidden_fields' ] );
		add_filter( 'wpcf7_mail_components', [ $this, 'add_mail_recipients' ], 100, 3 );
	}

	/**
     * Add custom hidden fields to the form html markup. This is needed for
     * listing contact forms, where we need the post id to make sure it's a 'job_listing',
     * and need to add a placeholder for the list of email recipients, which in each listing will
     * be replaced by unqiue email(s) for each different listing.
     *
     * @since 1.0
	 */
	public function add_custom_hidden_fields( $fields ) {
		$fields['_case27_recipients'] = '%case27_recipients%';
		$fields['_case27_post_id'] = get_the_ID();

		return $fields;
	}


	/**
     * For 'job_listing' contact forms, update the 'recipient' component with
     * the email(s) of the requested listing.
     *
     * @since 1.0
	 */
	public function add_mail_recipients( $components, $form, $obj ) {
		if ( $obj->name() !== 'mail' ) {
			return $components;
		}

		if ( empty( $_POST['_case27_post_id'] ) || empty( $_POST['_case27_recipients'] ) ) {
			return $components;
		}

		$postid = $_POST['_case27_post_id'];
		$recipients = explode( '|', $_POST['_case27_recipients'] );

		if ( ! ( $listing = \MyListing\Src\Listing::get( $postid ) ) || ! is_array( $recipients ) ) {
			return $components;
		}

		$emails = [];

		foreach ( $recipients as $field_key ) {
			if ( ! $listing->has_field( $field_key ) || ! is_email( $listing->get_field( $field_key ) ) ) {
				continue;
			}

			$emails[] = $listing->get_field( $field_key );
		}

		if ( count( $emails ) ) {
			if ( isset( $components['recipient'] ) && is_string( $components['recipient'] ) && ! empty( $components['recipient'] ) ) {
				$components['recipient'] .= ',' . join( ',', $emails );
			} else {
				$components['recipient'] = join( ',', $emails );
			}
		}

		return $components;
	}
}
