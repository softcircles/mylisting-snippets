<?php

namespace MyListing\Src\Notifications;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Expiring_Listings_User_Notification extends Base_Notification {

	public $user, $listings;

	public static function hook() {
		add_action( 'mylisting/schedule:daily', function() {
			$expiring = get_posts( [
				'post_type' => 'job_listing',
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'meta_query' => [
					'relation' => 'AND',
					[
						'relatioin' => 'OR',
						[
							'key' => '_job_expires',
							'value' => date( 'Y-m-d', current_time( 'timestamp' ) + ( apply_filters( 'mylisting/expiring-listings/months-notice', 2 ) * MONTH_IN_SECONDS ) ),
							'compare' => '=',
							'type' => 'DATE',
						],
						[
							'key' => '_job_expires',
							'value' => date( 'Y-m-d', current_time( 'timestamp' ) + ( apply_filters( 'mylisting/expiring-listings/weeks-notice', 4 ) * WEEK_IN_SECONDS ) ),
							'compare' => '=',
							'type' => 'DATE',
						],
						[
							'key' => '_job_expires',
							'value' => date( 'Y-m-d', current_time( 'timestamp' ) + ( apply_filters( 'mylisting/expiring-listings/days-notice', 15 ) * DAY_IN_SECONDS ) ),
							'compare' => '=',
							'type' => 'DATE',
						],
					],
					[
						'key' => '_job_expires',
						'value' => date( 'Y-m-d', current_time( 'timestamp' ) ),
						'compare' => '>=',
						'type' => 'DATE',
					],
				],
			] );

			// add hook to be used by the admin expirings notification
			do_action( 'mylisting/expiring-listings/all', $expiring );

			// group by author
			$grouped = [];
			foreach ( $expiring as $listing ) {
				if ( ! isset( $grouped[ $listing->post_author ] ) ) {
					$grouped[ $listing->post_author ] = [];
				}

				$grouped[ $listing->post_author ][] = $listing;
			}

			// send notification to each user
			foreach ( $grouped as $author_id => $listings ) {
				new self( [ 'user-id' => $author_id, 'listings' => $listings ] );
			}
		} );
	}

	public static function settings() {
		return [
			'name' => _x( 'Notify users on expiring listings', 'Notifications', 'my-listing' ),
			'description' => _x( 'Send an email to the user whenever one or more of their listings are reaching their expiry date.', 'Notifications', 'my-listing' ),
		];
	}

	/**
	 * Validate and prepare notifcation arguments.
	 *
	 * @since 2.1
	 */
	public function prepare( $args ) {
		if ( empty( $args['user-id'] ) || empty( $args['listings'] ) ) {
			throw new \Exception( 'Missing arguments.' );
		}

		// validate author
		$this->user = get_userdata( $args['user-id'] );
		if ( ! $this->user ) {
			throw new \Exception( 'Invalid user ID.' );
		}

		// validate listings
		$this->listings = [];
		foreach ( $args['listings'] as $listing ) {
			if ( $listing = \MyListing\Src\Listing::get( $listing ) ) {
				$this->listings[] = $listing;
			}
		}
		if ( empty( $this->listings ) ) {
			throw new \Exception( 'Invalid listings.' );
		}
	}

	public function get_mailto() {
		return $this->user->user_email;
	}

	public function get_subject() {
		if ( count( $this->listings ) === 1 ) {
			return sprintf(
				_x( '"%s" will expire on %s.', 'Notifications', 'my-listing' ),
				esc_html( $this->listings[0]->get_name() ),
				date_i18n( get_option( 'date_format' ), strtotime( get_post_meta( $this->listings[0]->get_id(), '_job_expires', true ) ) )
			);
		}

		return sprintf(
			_x( '%s listings will expire soon.', 'Notifications', 'my-listing' ),
			number_format_i18n( count( $this->listings ) )
		);
	}

	public function get_message() {
		$template = new Notification_Template;

		$template->add_paragraph( sprintf(
			_x( 'Hi %s,', 'Notifications', 'my-listing' ),
			esc_html( $this->user->first_name )
		) );

		if ( count( $this->listings ) > 1 ) {
			$template->add_paragraph( _x( 'The following listings are set to expire soon.', 'Notifications', 'my-listing' ) );
		}

		foreach ( $this->listings as $listing ) {
			$template
				->add_paragraph( sprintf(
					_x( '<strong>%s</strong> will expire on <strong>%s</strong>', 'Notifications', 'my-listing' ),
					esc_html( $listing->get_name() ),
					date_i18n( get_option( 'date_format' ), strtotime( get_post_meta( $listing->get_id(), '_job_expires', true ) ) )
				) )
				->add_button( _x( 'View listing', 'Notifications', 'my-listing' ), esc_url( $listing->get_link() ) )
				->add_thematic_break();
		}

		$template->add_primary_button( _x( 'Go to dashboard', 'Notifications', 'my-listing' ), esc_url( wc_get_account_endpoint_url( 'my-listings' ) ) );

		return $template->get_body();
	}
}
