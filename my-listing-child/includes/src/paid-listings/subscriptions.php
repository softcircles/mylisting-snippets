<?php

namespace MyListing\Src\Paid_Listings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Subscriptions {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		// WC Subscriptions must be enabled to use this feature.
		if ( ! class_exists( '\WC_Subscriptions' ) ) {
			return;
		}

		// Add listing as valid subscription.
		add_filter( 'woocommerce_is_subscription', [ $this, 'woocommerce_is_subscription' ], 10, 2 );

		// Add product type.
		add_filter( 'woocommerce_subscription_product_types', [ $this, 'add_subscription_product_types' ] );
		add_filter( 'product_type_selector', [ $this, 'add_product_type_selector' ] );

		// Product Class.
		add_filter( 'woocommerce_product_class' , [ $this, 'set_product_class' ], 10, 3 );

		// Add to cart.
		add_action( 'woocommerce_job_package_subscription_add_to_cart', '\WC_Subscriptions::subscription_add_to_cart', 30 );

		/* PAYMENTS */

		// Subscription Synchronisation.
		// activate sync (process meta) for listing package.
		if ( class_exists( 'WC_Subscriptions_Synchroniser' ) && method_exists( '\WC_Subscriptions_Synchroniser', 'save_subscription_meta' ) ) {
			add_action( 'woocommerce_process_product_meta_job_package_subscription', '\WC_Subscriptions_Synchroniser::save_subscription_meta', 10 );
		}

		// Prevent listing linked to product(subs) never expire automatically.
		add_action( 'added_post_meta', [ $this, 'updated_post_meta' ], 10, 4 );
		add_action( 'updated_post_meta', [ $this, 'updated_post_meta' ], 10, 4 );

		// When listing expires, adjust user package usage and delete package & user package meta in listing.
		add_action( 'publish_to_expired', [ $this, 'check_expired_listing' ] );

		// Change user package usage when trash/untrash listing.
		add_action( 'wp_trash_post', [ $this, 'wp_trash_post' ] );
		add_action( 'untrash_post', [ $this, 'untrash_post' ] );

		/* === SUBS ENDED. === */

		// Subscription Paused (on Hold).
		add_action( 'woocommerce_subscription_status_on-hold', [ $this, 'subscription_ended' ] );

		// Subscription Ended.
		add_action( 'woocommerce_scheduled_subscription_expiration', [ $this, 'subscription_ended' ] );

		// When a subscription ends after remaining unpaid.
		add_action( 'woocommerce_scheduled_subscription_end_of_prepaid_term', [ $this, 'subscription_ended' ] );

		// When the subscription status changes to cancelled.
		add_action( 'woocommerce_subscription_status_cancelled', [ $this, 'subscription_ended' ] );

		// Subscription is expired.
		add_action( 'woocommerce_subscription_status_expired', [ $this, 'subscription_ended' ] );

		/* === SUBS STARTS. === */

		// Subscription starts ( status changes to active ).
		add_action( 'woocommerce_subscription_status_active', [ $this, 'subscription_activated' ] );

		/* === SUBS RENEWED. === */

		// When the subscription is renewed.
		add_action( 'woocommerce_subscription_renewal_payment_complete', [ $this, 'subscription_renewed' ] );

		add_filter( 'woocommerce_subscriptions_is_renewal_order', [ $this, 'subscription_is_renewal' ], 99, 2 );

		// When the subscription is switched and only the item is changed.
		add_action( 'woocommerce_subscription_item_switched', [ $this, 'subscription_item_switched' ], 10, 4 );

		add_action( 'woocommerce_my_subscriptions_actions', [ $this, 'display_subscription_listings_in_table' ] );
		add_action( 'woocommerce_subscription_before_actions', [ $this, 'display_subscription_listings' ] );

		add_filter( 'post_type_link', [ $this, 'add_switch_query_arg_post_link' ], 30, 2 );

		// modify the subscription switch template
		add_action( 'template_redirect', [ $this, 'switch_subscription_screen' ], 200 );
	}

	public function subscription_is_renewal( $is_renewal, $subscription ) {
		
		if ( ! $is_renewal ) {
			return $is_renewal;
		}

		foreach ( $subscription->get_items() as $item ) {
			$listing_id = ! empty( $item['job_id'] ) ? absint( $item['job_id'] ) : false;
			if ( ! $listing_id ) {
				continue;
			}
			
			$listing = \MyListing\Src\Listing::get( $listing_id );

			if ( ! $listing ) {
				continue;
			}

			update_post_meta( $listing->get_id(), 'is_listing_renewal', true );
		}
	}

	/**
	 * Is this a subscription product?
	 *
	 * @since 1.6
	 */
	public function woocommerce_is_subscription( $is_subscription, $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product && $product->is_type( [ 'job_package_subscription' ] ) ) {
			$is_subscription = true;
		}
		return $is_subscription;
	}

	/**
	 * Types for subscriptions.
	 *
	 * @since 1.6
	 */
	public function add_subscription_product_types( $types ) {
		$types[] = 'job_package_subscription';
		return $types;
	}

	/**
	 * Add the product type selector.
	 *
	 * @since 1.6
	 */
	public function add_product_type_selector( $types ) {
		$types['job_package_subscription'] = __( 'Listing Subscription', 'my-listing' );
		return $types;
	}

	/**
	 * Set Product Class to Load.
	 *
	 * @since 1.6
	 * @param string $classname Current classname found.
	 * @param string $product_type Current product type.
	 */
	public function set_product_class( $classname, $product_type ) {
		if ( $product_type === 'job_package_subscription' ) {
			$classname = '\MyListing\Src\Paid_Listings\Product_Subscription';
		}

		return $classname;
	}

	/**
	 * Get subscription type for pacakge by ID.
	 *
	 * @since 1.6
	 */
	public function get_package_subscription_type( $product_id ) {
		$subscription_type = get_post_meta( $product_id, '_package_subscription_type', true );
		return empty( $subscription_type ) ? 'package' : $subscription_type;
	}

	/**
	 * Prevent listings linked to subscriptions from expiring.
	 *
	 * @since 1.6
	 */
	public function updated_post_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( 'job_listing' === get_post_type( $object_id ) && '' !== $meta_value && '_job_expires' === $meta_key ) {
			$_package_id = get_post_meta( $object_id, '_package_id', true );
			$package     = wc_get_product( $_package_id );

			if ( $package && 'job_package_subscription' === $package->get_type() && 'listing' === $package->get_package_subscription_type() ) {
				update_post_meta( $object_id, '_job_expires', '' ); // Never expire automatically.
			}
		}
	}

	/**
	 * When listings expire, we may have to change the listing count
	 * for the package, based on the subscription type.
	 *
	 * @since 1.6
	 */
	public function check_expired_listing( $post ) {
		$listing = \MyListing\Src\Listing::get( $post );
		if ( ! $listing ) {
			return;
		}

		$package = $listing->get_package();
		if ( ! $package ) {
			return;
		}

		$subscription_type = $this->get_package_subscription_type( $package->get_product_id() );

		/**
		 * If this is a `listing` subscription type, the user should gain back a slot
		 * after a listing expires.
		 */
		if ( $subscription_type === 'listing' ) {
			$package->decrease_count();
			delete_post_meta( $listing->get_id(), '_package_id' );
			delete_post_meta( $listing->get_id(), '_user_package_id' );
		}
	}

	/**
	 * If a listing gets trashed/deleted, the pack may need it's listing count changing.
	 *
	 * @since 1.6
	 */
	public function wp_trash_post( $post_id ) {
		$listing = \MyListing\Src\Listing::get( $post_id );
		if ( ! ( $listing && $listing->get_package() ) ) {
			return;
		}

		// pending-to-trash cases are handled by User_Packages class.
		if ( $listing->get_status() === 'pending' ) {
			return;
		}

		$package = $listing->get_package();
		$subscription_type = $this->get_package_subscription_type( $package->get_product_id() );

		/**
		 * If this is a `listing` subscription type, the user should gain back a slot
		 * on their payment package if a listing is trashed/deleted.
		 */
		if ( $subscription_type === 'listing' ) {
			$package->decrease_count();
		}
	}

	/**
	 * If a listing gets restored, the pack may need it's listing count changing.
	 *
	 * @since 1.6
	 */
	public function untrash_post( $post_id ) {
		$listing = \MyListing\Src\Listing::get( $post_id );
		if ( ! ( $listing && $listing->get_package() ) ) {
			return;
		}

		// if the status the listing will transition to after it gets untrashed is `pending`,
		// return early as that will be handled by User_Packages class.
		$post_status = get_post_meta( $listing->get_id(), '_wp_trash_meta_status', true );
		if ( $post_status === 'pending' ) {
			return;
		}

		$package = $listing->get_package();
		$subscription_type = $this->get_package_subscription_type( $package->get_product_id() );

		/**
		 * If this is a `listing` subscription type, the user should lose a slot
		 * on their payment package if a listing is restored after getting trashed/deleted.
		 */
		if ( $subscription_type === 'listing' ) {
			$package->increase_count();
		}
	}

	/**
	 * Subscription has expired - cancel listing packs.
	 *
	 * @since 1.6
	 */
	public function subscription_ended( $subscription ) {
		if ( is_int( $subscription ) ) {
			$subscription = wcs_get_subscription( $subscription );
		}

		foreach ( $subscription->get_items() as $item ) {
			$subscription_type = $this->get_package_subscription_type( $item['product_id'] );

			$packages = get_posts( [
				'post_type' => 'case27_user_package',
				'post_status' => [ 'publish', 'case27_full' ],
				'posts_per_page' => 1,
				'suppress_filters' => false,
				'fields' => 'ids',
				'meta_query' => [
					'relation' => 'AND',
					[ 'key' => '_order_id', 'value' => $subscription->get_id() ],
					[ 'key' => '_product_id', 'value' => $item['product_id'] ],
				],
			] );

			// validate package id
			if ( ! is_array( $packages ) || empty( $packages ) ) {
				continue;
			}

			$package_id = $packages[0];

			/**
			 * If this is a `listing` subscription type, the package should
			 * be deleted when the subscription ends.
			 */
			if ( $subscription_type === 'listing' ) {

				// Delete the package.
				wp_delete_post( $package_id, true ); // @todo:maybe force delete.
				$listing_ids = get_posts( [
					'post_type'      => 'job_listing',
					'post_status'    => [ 'publish', 'pending' ],
					'fields'         => 'ids',
					'posts_per_page' => -1,
					'meta_key'       => '_user_package_id',
					'meta_value'     => $package_id,
				] );

				foreach ( $listing_ids as $listing_id ) {
					$old_status = get_post_status( $listing_id );
					wp_update_post( [
						'ID' => $listing_id,
						'post_status' => 'expired',
					] );

					// Make a record of the subscription ID in case of re-activation.
					update_post_meta( $listing_id, '_expired_subscription_id', $subscription->get_id() );

					// Also save the old listing status.
					update_post_meta( $listing_id, '_expired_subscription_status', $old_status );
				}
			}

			/**
			 * Otherwise, if this is a `package` subscription type, the user gets to keep their package in
			 * it's current state. However, it's listing counts won't be renewed anymore.
			 */
			if ( $subscription_type === 'package' ) {
				// ...
			}
		}

		// delete this flag so the package can be processed again if it gets reactivated
		delete_post_meta( $subscription->get_id(), 'wc_paid_listings_subscription_packages_processed' );
		mlog('end');
	}

	/**
	 * Subscription activated.
	 *
	 * @since 1.6
	 */
	public function subscription_activated( $subscription ) {
		global $wpdb;

		if ( get_post_meta( $subscription->get_id(), 'wc_paid_listings_subscription_packages_processed', true ) ) {
			return;
		}

		foreach ( $subscription->get_items() as $item ) {
			$package = false;
			$product = wc_get_product( $item['product_id'] );
			$subscription_type = $this->get_package_subscription_type( $item['product_id'] );

			// validate subscription package
			if ( isset( $item['switched_subscription_item_id'] ) || ! ( $product->is_type( 'job_package_subscription' ) && $subscription->get_user_id() ) ) {
				continue;
			}

			// if this is a reactivation, get the previous package
			$current_package = get_posts( [
				'post_type' => 'case27_user_package',
				'post_status' => [ 'publish', 'case27_full' ],
				'posts_per_page' => 1,
				'suppress_filters' => false,
				'fields' => 'ids',
				'meta_query' => [
					'relation' => 'AND',
					[ 'key' => '_order_id', 'value' => $subscription->get_id() ],
					[ 'key' => '_product_id', 'value' => $item['product_id'] ],
				],
			] );
			$current_package_id = is_array( $current_package ) && ! empty( $current_package ) ? $current_package[0] : false;

			/**
			 * Handle `listing` subscription types. This type of subscriptions works by tying its listings
			 * expiry date to that of the subscription itself.
			 */
			if ( $subscription_type === 'listing' ) {

				/**
				 * For `listing` subscription types, this should've been deleted already when the
				 * subscription ended, but it's possible it didn't in earlier theme versions, so
				 * we do it again here to be sure (for `listing` subscription types only).
				 */
				if ( $current_package_id ) {
					wp_delete_post( $current_package_id, true );
				}

				// always create a new payment package
				$package = \MyListing\Src\Package::create( [
					'user_id'        => $subscription->get_user_id(),
					'order_id'       => $subscription->get_id(),
					'product_id'     => $product->get_id(),
					'duration'       => $product->get_duration(),
					'limit'          => $product->get_limit(),
					'featured'       => $product->is_listing_featured(),
					'mark_verified'  => $product->mark_verified(),
					'use_for_claims' => $product->use_for_claims(),
				] );

				if ( ! $package ) {
					continue;
				}

				/**
				 * If this is a re-activation of the subscription, get previously
				 * expired listing ids and re-publish them.
				 */
				$expired_ids = (array) $wpdb->get_col( $wpdb->prepare(
					"SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=%s", '_expired_subscription_id',
					$subscription->get_id()
				) );
				$expired_ids = array_unique( array_filter( array_map( 'absint', $expired_ids ) ) );
				foreach ( $expired_ids as $listing_id ) {
					if ( ! in_array( get_post_status( $listing_id ), [ 'pending_payment', 'expired' ], true ) ) {
						continue;
					}

					// get the listing status before it expired (in case it was set to pending instead of publish)
					$old_status = get_post_meta( $listing_id, '_expired_subscription_status', true );

					// remove expired subscription metadata, no longer needed
					delete_post_meta( $listing_id, '_expired_subscription_id' );
					delete_post_meta( $listing_id, '_expired_subscription_status' );

					// update the package id in the listing meta
					update_post_meta( $listing_id, '_user_package_id', $package->get_id() );

					// update expiry date (never expire for `listing` subscription type)
					update_post_meta( $listing_id, '_job_expires', '' );

					// determine the listing status upon re-activation
					$new_status = in_array( $old_status, [ 'pending', 'publish' ], true )
						? $old_status
						: ( mylisting_get_setting( 'submission_requires_approval' ) ? 'pending' : 'publish' );

					// re-activate listing
					wp_update_post( [
						'ID' => $listing_id,
						'post_status' => $new_status,
					] );

					// update package counts
					$package->increase_count();
				}
			}

			/**
			 * Handle `package` subscription types. This type of subscriptions works by creating one
			 * package, and resets it's listing count to zero on every renewal.
			 */
			if ( $subscription_type === 'package' ) {

				/**
				 * If this is a re-activation of the subscription, see if we already
				 * have created a package, and use that instead.
				 */
				$package = \MyListing\Src\Package::get( $current_package_id );

				// otherwise, create a new package
				if ( ! $package ) {
					$package = \MyListing\Src\Package::create( [
						'user_id'        => $subscription->get_user_id(),
						'order_id'       => $subscription->get_id(),
						'product_id'     => $product->get_id(),
						'duration'       => $product->get_duration(),
						'limit'          => $product->get_limit(),
						'featured'       => $product->is_listing_featured(),
						'mark_verified'  => $product->mark_verified(),
						'use_for_claims' => $product->use_for_claims(),
					] );

					if ( ! $package ) {
						continue;
					}
				}
			}

			/**
			 * If a listing id has been passed to the subscription order (through Add Listing,
			 * Claim Listing, or Switch Package forms), handle it.
			 */
			$listing_id = ! empty( $item['job_id'] ) ? absint( $item['job_id'] ) : false;
			$listing = \MyListing\Src\Listing::get( $listing_id );
			$assignment_type = ! empty( $item['assignment_type'] ) ? $item['assignment_type'] : 'submission';
			if ( ! empty( $item['is_claim'] ) ) {
				$assignment_type = 'claim'; // backwards compatibility pre v2.1.3
			}

			if ( $listing && $package ) {

				/**
				 * The submitted listing should only be processed the first time the subscription is activated. If it gets
				 * reactivated, the listing will already be handled at this point through it's `_expired_subscription_id`
				 * and `_expired_subscription_status` meta keys which are set when a subscription ends.
				 *
				 * @since 2.1.6
				 */
				if ( get_post_meta( $subscription->get_id(), 'wc_paid_listings_subscription_listing_processed', true ) ) {
					return;
				}

				// update meta key so this is only run the first time the subscription is activated
				update_post_meta( $subscription->get_id(), 'wc_paid_listings_subscription_listing_processed', true );

				/**
				 * Subscription has been activated, payment package has been created, and a valid
				 * listing has been passed to the order. This can be used by Add Listing and
				 * other forms to modify the listing information, assign the package, etc.
				 *
				 * @since 2.1.6
				 */
				do_action( sprintf( 'mylisting/subscriptions/%s/order-processed', $assignment_type ), $listing, $package );
			}
		}

		update_post_meta( $subscription->get_id(), 'wc_paid_listings_subscription_packages_processed', true );
		mlog('activate');
	}

	/**
	 * Subscription renewed - renew the listing pack.
	 *
	 * @since 1.6
	 */
	public function subscription_renewed( $subscription ) {
		global $wpdb;

		foreach ( $subscription->get_items() as $item ) {
			$package = false;
			$product = wc_get_product( $item['product_id'] );
			$subscription_type = $this->get_package_subscription_type( $item['product_id'] );

			// validate subscription package
			if ( ! ( $product->is_type( 'job_package_subscription' ) && $subscription->get_user_id() ) ) {
				continue;
			}

			$current_package = get_posts( [
				'post_type' => 'case27_user_package',
				'post_status' => [ 'publish', 'case27_full' ],
				'posts_per_page' => 1,
				'suppress_filters' => false,
				'fields' => 'ids',
				'meta_query' => [
					'relation' => 'AND',
					[ 'key' => '_order_id', 'value' => $subscription->get_id() ],
					[ 'key' => '_product_id', 'value' => $item['product_id'] ],
				],
			] );
			$current_package_id = is_array( $current_package ) && ! empty( $current_package ) ? $current_package[0] : false;

			/**
			 * On subscription renewals, `package` subscription types
			 * have the package count reset to zero.
			 */
			if ( $subscription_type === 'package' ) {

				// get the package created on subscription activation
				$package = \MyListing\Src\Package::get( $current_package_id );

				// if not available, e.g. it was deleted by the admin, re-create it
				if ( ! $package ) {
					$package = \MyListing\Src\Package::create( [
						'user_id'        => $subscription->get_user_id(),
						'order_id'       => $subscription->get_id(),
						'product_id'     => $product->get_id(),
						'duration'       => $product->get_duration(),
						'limit'          => $product->get_limit(),
						'featured'       => $product->is_listing_featured(),
						'mark_verified'  => $product->mark_verified(),
						'use_for_claims' => $product->use_for_claims(),
					] );

					if ( ! $package ) {
						continue;
					}
				}

				// reset the listing count to zero
				$package->reset_count();
			}

			/**
			 * On subscription renewals, `listing` subscription types don't have anything
			 * to process. The listing expiry date is tied to the subscription end date.
			 */
			if ( $subscription_type === 'listing' ) {
				// ...
			}
		}
		mlog('renew');
	}

	/**
	 * When switching a subscription we need to update old listings.
	 * No need to give the user a new package; that is still handled by the orders class.
	 *
	 * @since 1.6
	 *
	 * @param object $order             WC Order.
	 * @param object $subscription      WC Subscription.
	 * @param int    $new_order_item_id New order Item ID.
	 * @param int    $old_order_item_id Old order Item ID.
	 */
	public function subscription_item_switched( $order, $subscription, $new_order_item_id, $old_order_item_id ) {
		global $wpdb;

		$new_order_item = \WC_Subscriptions_Order::get_item_by_id( $new_order_item_id );
		$old_order_item = \WC_Subscriptions_Order::get_item_by_id( $old_order_item_id );

		$new_subscription = (object) [
			'id'           => $subscription->id,
			'subscription' => $subscription,
			'product_id'   => $new_order_item['product_id'],
			'product'      => wc_get_product( $new_order_item['product_id'] ),
			'type'         => $this->get_package_subscription_type( $new_order_item['product_id'] ),
		];

		$old_subscription = (object) [
			'id'           => $subscription->id,
			'subscription' => $subscription,
			'product_id'   => $old_order_item['product_id'],
			'product'      => wc_get_product( $old_order_item['product_id'] ),
			'type'         => $this->get_package_subscription_type( $old_order_item['product_id'] ),
		];

		$this->switch_package( $subscription->get_user_id(), $new_subscription, $old_subscription );
	}

	/**
	 * Handle Switch Event.
	 *
	 * @since 1.6
	 *
	 * @param int    $user_id          User ID.
	 * @param object $new_subscription New Subscription.
	 * @param object $old_subscription Old Subscription.
	 */
	public function switch_package( $user_id, $new_subscription, $old_subscription ) {
		// Get the user package.
		$user_packages = get_posts( [
			'post_type'        => 'case27_user_package',
			'post_status'      => [ 'publish', 'case27_full' ],
			'posts_per_page'   => 1,
			'suppress_filters' => false,
			'fields'           => 'ids',
			'meta_query' => [
				'relation' => 'AND',
				[ 'key' => '_order_id', 'value' => $old_subscription->id ],
				[ 'key' => '_product_id', 'value' => $old_subscription->product_id ],
			],
		] );

		$current_package_id = is_array( $user_packages ) && ! empty( $user_packages ) ? $user_packages[0] : false;
		$current_package = \MyListing\Src\Package::get( $current_package_id );
		if ( ! ( $current_package && $new_subscription->product->is_type( [ 'job_package_subscription' ] ) ) ) {
			return;
		}

		// create new package
		$new_product = wc_get_product( $new_subscription->product_id );
		$new_package = \MyListing\Src\Package::create( [
			'user_id'        => $user_id,
			'order_id'       => $new_subscription->id,
			'product_id'     => $new_subscription->product_id,
			'duration'       => $new_product->get_duration(),
			'limit'          => $new_product->get_limit(),
			'featured'       => $new_product->is_listing_featured(),
			'mark_verified'  => $new_product->mark_verified(),
			'use_for_claims' => $new_product->use_for_claims(),
		] );

		if ( ! ( $new_product && $new_package ) ) {
			return;
		}

		// get all listings from the old package and assign them the new one
		$listing_ids = get_posts( [
			'post_type'      => 'job_listing',
			'post_status'    => [ 'publish', 'pending' ],
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'meta_key'       => '_user_package_id',
			'meta_value'     => $current_package->get_id(),
		] );

		foreach ( $listing_ids as $listing_id ) {
			// if the new package count is lower and doesn't fit all previous listings,
			// expire them and notify the user
			if ( $new_package->get_limit() && $new_package->get_remaining_count() <= 0 ) {
				wp_update_post( [
					'ID' => $listing_id,
					'post_status' => 'expired',
				] );

				wc_add_notice( _x( 'No more available slots in your package. Listing "%s" could not be published.', 'Subscription switch package', 'my-listing' ), 'error' );
				continue;
			}

			$new_package->assign_to_listing( $listing_id );
		}

		// delete old package
		wp_delete_post( $current_package->get_id(), true );
	}

	public function display_subscription_listings_in_table( $subscription ) {
		foreach ( $subscription->get_items() as $item ) {
			$package_ids = get_posts( [
				'post_type' => 'case27_user_package',
				'post_status' => [ 'publish', 'case27_full' ],
				'posts_per_page' => 1,
				'suppress_filters' => false,
				'fields' => 'ids',
				'meta_query' => [
					'relation' => 'AND',
					[ 'key' => '_order_id', 'value' => $subscription->get_id() ],
					[ 'key' => '_product_id', 'value' => $item->get_product_id() ],
				],
			] );

			$package_id = is_array( $package_ids ) && ! empty( $package_ids ) ? $package_ids[0] : false;
			$package = \MyListing\Src\Package::get( $package_id );
			if ( ! $package ) {
				continue;
			}

			$count = $package->get_count();
			$count .= $package->get_limit() ? '/'.$package->get_limit() : '';
			printf( '<span style="font-size:13px;">%s</span>', sprintf( _x( '%s listing(s) posted', 'Subscription details', 'my-listing' ), $count ) );
		}
	}

	public function display_subscription_listings( $subscription ) {
		global $wpdb;

		foreach ( $subscription->get_items() as $item ) {
			$package_ids = get_posts( [
				'post_type' => 'case27_user_package',
				'post_status' => [ 'publish', 'case27_full' ],
				'posts_per_page' => 1,
				'suppress_filters' => false,
				'fields' => 'ids',
				'meta_query' => [
					'relation' => 'AND',
					[ 'key' => '_order_id', 'value' => $subscription->get_id() ],
					[ 'key' => '_product_id', 'value' => $item->get_product_id() ],
				],
			] );

			$package_id = is_array( $package_ids ) && ! empty( $package_ids ) ? $package_ids[0] : false;
			$package = \MyListing\Src\Package::get( $package_id );
			if ( ! $package ) {
				continue;
			}

			$count = $package->get_count();
			$count .= $package->get_limit() ? '/'.$package->get_limit() : '';
			printf(
				'<tr><td>%s</td><td>%s</td></tr>',
				_x( 'Package limits', 'Subscription details', 'my-listing' ),
				sprintf( _x( '%s listing(s) posted', 'Subscription details', 'my-listing' ), $count )
			);

			$listings = get_posts( [
				'post_type' => 'job_listing',
				'post_status' => [ 'publish', 'pending' ],
				'posts_per_page' => -1,
				'meta_key'       => '_user_package_id',
				'meta_value'     => $package->get_id(),
			] );

			$list = array_map( function( $listing ) {
				return sprintf( '<a href="%s">%s</a>', get_permalink( $listing ), get_the_title( $listing ) );
			}, $listings );

			printf( '<tr><td>%s</td><td>%s</td></tr>', _x( 'Active Listings', 'Subscription details', 'my-listing' ), ! empty( $list ) ? join( '', $list ) : '&mdash;' );
		}
	}

	/**
	 * Subscription switch form only handles default 'subscription' product types.
	 * For 'job_package_subscription' products, we need to replicate
	 * `WC_Subscriptions_Switcher::add_switch_query_arg_post_link` method
	 * and include support for our custom product type.
	 *
	 * @since 2.4.2
	 */
	public function add_switch_query_arg_post_link( $permalink, $post ) {
		if ( ! isset( $_GET['switch-subscription'] ) || ! is_main_query() || ! is_product() || 'product' !== $post->post_type ) {
			return $permalink;
		}

		$subscription_id = absint( $_GET['switch-subscription'] );
		$item_id = absint( $_GET['item'] );
		$product = wc_get_product( $post );
		$product_type = wcs_get_objects_property( $product, 'type' );

		$subscription_permalink = apply_filters( 'woocommerce_subscriptions_add_switch_query_args',
			add_query_arg( [
				'switch-subscription' => $subscription_id,
				'item' => $item_id,
				'_wcsnonce' => wp_create_nonce( 'wcs_switch_request' ),
			] ),
			$subscription_id,
			$item_id
		);

		if ( $product_type === 'job_package_subscription' ) {
			return $subscription_permalink;
		}

		if ( $product_type === 'grouped' ) {
			foreach ( $product->get_children() as $child ) {
				$child_type = wcs_get_objects_property( wc_get_product( $child ), 'type' );
				if ( $child_type === 'job_package_subscription' ) {
					return $subscription_permalink;
				}
			}
		}

		return $permalink;
	}

	/**
	 * @since 2.6
	 */
	public function switch_subscription_screen() {
		if ( isset( $_GET['switch-subscription'] ) && isset( $_GET['item'] ) ) {
			if ( apply_filters( 'mylisting/custom-subscriptions-switch-layout', true ) === false ) {
				return;
			}

			$subscription = wcs_get_subscription( $_GET['switch-subscription'] );
			$line_item    = wcs_get_order_item( $_GET['item'], $subscription );

			// visiting a switch link for someone elses subscription or if the switch link doesn't contain a valid nonce
			if ( ! is_object( $subscription ) || empty( $_GET['_wcsnonce'] ) || ! wp_verify_nonce( $_GET['_wcsnonce'], 'wcs_switch_request' ) || empty( $line_item ) || ! \WC_Subscriptions_Switcher::can_item_be_switched_by_user( $line_item, $subscription ) ) {

				return;
			}

			$data = $line_item->get_data();
			$config = [
				'current_plan' => $data['product_id'] ?? false,
				'current_plan_text' => _x( 'Current plan', 'Subscription switch package', 'my-listing' ),
			];

			add_action( 'wp_head', function() use ( $config ) {
				printf(
					'<script type="text/javascript">var MyListing_Switch_Config = %s;</script>',
					wp_json_encode( (object) $config )
				);
			} );
		}
	}
}
