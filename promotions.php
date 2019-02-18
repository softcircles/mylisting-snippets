<?php

namespace MyListing\Ext\Promotions;

class Promotions {

	use \MyListing\Src\Traits\Instantiatable;

	public
		$active,		  // Indicate whether promos v2 have been loaded.
		$package,         // Instance of \MyListing\Ext\Promotions\Package.
		$woocommerce;     // Instance of \MyListing\Ext\Promotions\WooCommerce.

	public function __construct() {
		// Setup ACF settings page.
		add_action( 'acf/init', [ $this, 'setup_options_page' ] );

		// Initialize promotions.
		add_action( 'after_setup_theme', [ $this, 'initialize' ] );

		// Insert settings for listing priority in listing edit page in wp-admin.
		add_action( 'mylisting/admin/listing/sidebar-settings', [ $this, 'priority_settings' ], 30, 1 );
		add_action( 'save_post', [ $this, 'save_priority_settings' ], 99, 2 );
		add_filter( 'mylisting/admin-tips', [ $this, 'priority_docs' ] );

		// Display 'Promoted' badge.
		add_filter( 'mylisting/preview-card/show-badge', [ $this, 'show_promoted_badge' ], 30, 3 );

		// Flush listings cache on theme activation.
		add_action( 'after_switch_theme', [ $this, 'flush_listings_cache' ] );
	}

	/**
	 * Initialize promotions.
	 *
	 * @since 1.7.0
	 */
	public function initialize() {
		// Don't proceed if Promotions are disabled in WP Admin.
		if ( c27()->get_setting( 'promotions_version', '2.0' ) === 'none' ) {
			return false;
		}

		// Bail early if WooCommerce or WPJM is not active.
		if ( ! class_exists( '\WooCommerce' ) || ! class_exists( '\WP_Job_Manager' ) ) {
			return false;
		}

		/*** Proceed with Promotions v2 ***/
		$this->active = true;

		// Handle promotion packages.
		$this->package = Package::instance();

		// Handle WooCommerce integration.
		$this->woocommerce = WooCommerce::instance();

		// Handle 'Buy Promotion' action in Promotions modal.
		add_action( 'wp_ajax_cts_promotions', [ $this, 'handle_promotion_request' ] );

		// Schedule event to check for and trash expired packages.
		if ( ! wp_next_scheduled( 'mylisting/promotions/handle-expired-packages' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'mylisting/promotions/handle-expired-packages' );
		}

		// Add compatibility with 'Listing Behavior' setting in Listing Feed Elementor widget.
		add_filter( 'mylisting/sections/listing-feed/args', [ $this, 'listing_feed_args' ], 30, 2 );
	}

	/**
	 * Get the \MyListing\Ext\Promotions\Package instance.
	 *
	 * @since 1.7.0
	 */
	public function package() {
		return $this->package;
	}

	/**
	 * Get the \MyListing\Ext\Promotions\WooCommerce instance.
	 *
	 * @since 1.7.0
	 */
	public function wc() {
		return $this->woocommerce;
	}

	/**
	 * Insert basic settings for listing priority in
	 * listing edit page in wp-admin. This will be
	 * overridden by promotions v2 if it's active.
	 *
	 * @since 1.7.0
	 */
	public function priority_settings( $listing ) {
		require locate_template( 'templates/dashboard/promotions/admin/priority-settings.php' );
	}

	/**
	 * Add docs on priority.
	 *
	 * @since 1.7.0
	 */
	public function priority_docs( $tips ) {
		$tips['priority-docs'] = locate_template( 'templates/dashboard/promotions/admin/priority-docs.php' );
		return $tips;
	}

	/**
	 * Display the promotion badge in listing preview card.
	 *
	 * @since 1.7.0
	 *
	 * @param bool                   $show_badge Whether to display the badge.
	 * @param \MyListing\Src\Listing $listing    Listing object
	 * @param array                  $data       Preview card settings array.
	 */
	public function show_promoted_badge( $show_badge, $listing, $data ) {
		return $listing->get_priority() >= 1;
	}

	/**
	 * Save listing priority settings.
	 *
	 * @since 1.7.0
	 *
	 * @param int      $post_id Listing ID.
	 * @param \WP_Post $post    Post Object
	 */
	public function save_priority_settings( $post_id, $post = null ) {
		if ( ! $post || 'job_listing' !== $post->post_type || ! isset( $_POST['cts-listing-priority'] ) ) {
			return false;
		}

		$priority = $_POST['cts-listing-priority'];

		// Save custom priority value.
		if ( $priority === 'custom' ) {
			$custom_priority = ! empty( $_POST['cts-listing-custom-priority'] ) ? absint( $_POST['cts-listing-custom-priority'] ) : false;

			if ( $custom_priority >= 0 ) {
				update_post_meta( $post_id, '_featured', $custom_priority );
			}

			return true;
		}

		// Save listing priority.
		if ( absint( $priority ) >= 0 ) {
			update_post_meta( $post_id, '_featured', absint( $priority ) );
		}
	}

	/**
	 * Add the chosen promotion package to cart,
	 * and proceed to checkout.
	 *
	 * @since 1.7.0
	 */
	public function handle_promotion_request() {
		check_ajax_referer( 'c27_ajax_nonce', 'security' );

		$process = ! empty( $_POST['process'] ) ? $_POST['process'] : 'buy-package';

		// Add promotion package to cart.
		if ( $process === 'buy-package' ) {
			try {
				// Validate request.
				if ( ! is_user_logged_in() || empty( $_POST['listing_id'] ) || empty( $_POST['package_id'] ) ) {
	            	throw new \Exception( _x( 'Couldn\'t process request.', 'Promotions: buy package', 'my-listing' ) );
				}

				$listing = get_post( absint( $_POST['listing_id'] ) );
				$product = wc_get_product( absint( $_POST['package_id'] ) );

				// Verify it's a published listing.
				if ( ! $listing || $listing->post_type !== 'job_listing' || $listing->post_status !== 'publish' ) {
	            	throw new \Exception( _x( 'Invalid listing.', 'Promotions: buy package', 'my-listing' ) );
				}

				// Verify the listing belongs to the logged in user.
				if ( ! current_user_can( 'edit_others_posts', $listing->ID ) && absint( $listing->post_author ) !== absint( get_current_user_id() ) ) {
	            	throw new \Exception( _x( 'No permission.', 'Promotions: buy package', 'my-listing' ) );
				}

				// Verify product.
				if ( ! $product || ! $product->is_type( 'promotion_package' ) || ! $product->is_purchasable() ) {
	            	throw new \Exception( _x( 'Invalid package.', 'Promotions: buy package', 'my-listing' ) );
				}

				// Remove old promotion packages for this listing from the cart, if any.
				if ( is_array( WC()->cart->cart_contents ) ) {
					foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
						if ( empty( $cart_item['listing_id'] ) || empty( $cart_item['data'] ) || $cart_item['data']->get_type() !== 'promotion_package' ) {
							continue;
						}

						// Remove promotion package if it belongs to the listing currently being promoted.
						if ( absint( $cart_item['listing_id'] ) === absint( $listing->ID ) ) {
							WC()->cart->remove_cart_item( $cart_item_key );
						}
					}
				}

				// Pass listing ID to the promotion item we're adding to cart.
				$cart_item_data = [ 'listing_id' => $listing->ID ];

				// Add product to cart.
				WC()->cart->add_to_cart(
					$product->get_id(), // Product ID.
					1,                  // Quantity.
					'',                 // Variation ID.
					'',                 // Variation attribute values.
					$cart_item_data     // Extra cart item data.
				);

				$current_priority = get_post_meta( $listing->ID, '_featured', true );

				// if ( absint( $current_priority ) >= 0 ) {
					update_post_meta( $listing->ID, '_promo_package_old_priority', $current_priority );
				// }

	            return wp_send_json( [
	                'status'  => 'success',
	                'redirect' => add_query_arg( 't', time(), get_permalink( wc_get_page_id( 'checkout' ) ) ),
	            ] );
			} catch ( \Exception $e ) {
	            return wp_send_json( [
	                'status'  => 'error',
	                'message' => sprintf( _x( 'Promotion failed: %s', 'Promotions: buy package', 'my-listing' ), $e->getMessage() ),
	            ] );
			}
		}

		// Use already owned package.
		if ( $process === 'use-package' ) {
			try {
				// Validate request.
				if ( ! is_user_logged_in() || empty( $_POST['listing_id'] ) || empty( $_POST['package_id'] ) ) {
	            	throw new \Exception( _x( 'Couldn\'t process request.', 'Promotions: buy package', 'my-listing' ) );
				}

				$listing = get_post( absint( $_POST['listing_id'] ) );
				$packages = $this->package->get_packages( [
					'post_status' => 'publish',
					'posts_per_page' => 1,
					'post__in'       => [ absint( $_POST['package_id'] ) ],
					'meta_query'  => [[
				        'relation' => 'OR',
				        [
				            'key' => '_listing_id',
				            'value' => '',
				        ],
				        [
				            'key' => '_listing_id',
				            'compare' => 'NOT EXISTS',
				        ],
					]],
				] );
				$package = reset( $packages );

				// Verify it's a published listing.
				if ( ! $listing || $listing->post_type !== 'job_listing' || $listing->post_status !== 'publish' ) {
	            	throw new \Exception( _x( 'Invalid listing.', 'Promotions: buy package', 'my-listing' ) );
				}

				// Verify the listing belongs to the logged in user.
				if ( ! current_user_can( 'edit_others_posts', $listing->ID ) && absint( $listing->post_author ) !== absint( get_current_user_id() ) ) {
	            	throw new \Exception( _x( 'No permission.', 'Promotions: buy package', 'my-listing' ) );
				}

				// Verify package.
				if ( ! $package ) {
	            	throw new \Exception( _x( 'Invalid package.', 'Promotions: buy package', 'my-listing' ) );
				}

				if ( ! ( $this->activate_package( $package->ID, $listing->ID ) ) ) {
	            	throw new \Exception( _x( 'Couldn\'t activate package.', 'Promotions: buy package', 'my-listing' ) );
				}

				wc_add_notice( _x( sprintf( '"%s" has been promoted.', $listing->post_title ), 'Promotions: buy package', 'my-listing' ), 'success' );

	            return wp_send_json( [
	                'status'  => 'success',
	                'redirect' => add_query_arg( 't', time(), $this->package->get_edit_link( $package->ID ) ),
	            ] );
			} catch ( \Exception $e ) {
	            return wp_send_json( [
	                'status'  => 'error',
	                'message' => sprintf( _x( 'Promotion failed: %s', 'Promotions: buy package', 'my-listing' ), $e->getMessage() ),
	            ] );
			}
		}

		// Cancel promotion package.
		if ( $process === 'cancel-package' ) {
			try {
				// Validate request.
				if ( ! is_user_logged_in() || empty( $_POST['listing_id'] ) ) {
	            	throw new \Exception( _x( 'Couldn\'t process request.', 'Promotions: buy package', 'my-listing' ) );
				}

				$listing = get_post( absint( $_POST['listing_id'] ) );
				$package_id = absint( get_post_meta( $listing->ID, '_promo_package_id', true ) );

				if ( ! $package_id ) {
	            	throw new \Exception( _x( 'Invalid package.', 'Promotions: buy package', 'my-listing' ) );
				}

				$packages = $this->package->get_packages( [
					'post_status' => 'publish',
					'posts_per_page' => 1,
					'post__in'       => [ $package_id ],
					'meta_query'  => [[
			            'key' => '_listing_id',
			            'value' => $listing->ID,
					]],
				] );
				$package = reset( $packages );

				// Verify it's a published listing.
				if ( ! $listing || $listing->post_type !== 'job_listing' || $listing->post_status !== 'publish' ) {
	            	throw new \Exception( _x( 'Invalid listing.', 'Promotions: buy package', 'my-listing' ) );
				}

				// Verify the listing belongs to the logged in user.
				if ( ! current_user_can( 'edit_others_posts', $listing->ID ) && absint( $listing->post_author ) !== absint( get_current_user_id() ) ) {
	            	throw new \Exception( _x( 'No permission.', 'Promotions: buy package', 'my-listing' ) );
				}

				// Verify package.
				if ( ! $package ) {
	            	throw new \Exception( _x( 'Invalid package.', 'Promotions: buy package', 'my-listing' ) );
				}

				if ( ! ( $this->expire_package( $package->ID ) ) ) {
	            	throw new \Exception( _x( 'Couldn\'t deactivate package.', 'Promotions: buy package', 'my-listing' ) );
				}

				wc_add_notice( _x( sprintf( 'Promotion cancelled for "%s".', $listing->post_title ), 'Promotions: buy package', 'my-listing' ), 'notice' );

	            return wp_send_json( [
	                'status'  => 'success',
	                'redirect' => add_query_arg( 't', time(), wc_get_account_endpoint_url( 'promotions' ) ),
	            ] );
			} catch ( \Exception $e ) {
	            return wp_send_json( [
	                'status'  => 'error',
	                'message' => sprintf( _x( 'Action failed: %s', 'Promotions: buy package', 'my-listing' ), $e->getMessage() ),
	            ] );
			}
		}
	}

	/**
	 * Activate promotion package.
	 *
	 * @since 1.7.0
	 *
	 * @param int $package_id Promotion package ID
	 * @param int $listing_id Listing to promote ID
	 */
	public function activate_package( $package_id, $listing_id = false ) {
		if ( ! ( $package = get_post( $package_id ) ) || $package->post_type !== 'cts_promo_package' ) {
			return false;
		}

		// If no listing id has been provided, see if there's one present in the package meta.
		if ( ! $listing_id ) {
			$listing_id = get_post_meta( $package_id, '_listing_id', true );
		}

		if ( ! ( $listing = get_post( $listing_id ) ) || $listing->post_type !== 'job_listing' ) {
			return false;
		}

		// Add package info to listing.
		update_post_meta( $listing->ID, '_promo_package_id', $package_id );

		// Add listing info to package.
		update_post_meta( $package_id, '_listing_id', $listing->ID );

		// Save current listing priority, for when the promotion package expires.
		if ( $current_priority = get_post_meta( $listing->ID, '_featured', true ) ) {
			update_post_meta( $listing->ID, '_promo_package_old_priority', absint( $current_priority ) );
		}

		// Get package priority, with a default value in case it's missing.
		if ( ! ( $priority = absint( get_post_meta( $package_id, '_priority', true ) ) ) ) {
			$priority = 2;
		}

		// Set new listing priority.
		update_post_meta( $listing->ID, '_featured', $priority );

		// Calculate promotion expiry date.
		$expires  = '';
		$duration = absint( get_post_meta( $package_id, '_duration', true ) );

		if ( $duration ) {
			$expires = date( 'Y-m-d H:i:s', strtotime( sprintf( '+%s days', $duration ), current_time( 'timestamp' ) ) );
		}

		// Update package status to active (published),
		// and set it's expiry date.
		wp_update_post( [
			'ID'          => $package->ID,
			'post_status' => 'publish',
			'meta_input'  => [
				'_expires' => $expires,
			],
		] );

		$this->flush_listings_cache();

		return true;
	}

	/**
	 * After a promotion package has expired, set it's
	 * status to 'Expired', and remove package related
	 * data from the listing.
	 *
	 * @since 1.7.0
	 * @param int $package_id Promotion package ID
	 */
	public function expire_package( $package_id ) {
		$package    = get_post( $package_id );
		$listing_id = get_post_meta( $package_id, '_listing_id', true );

		if ( ! $package || ! $listing_id || $package->post_type !== 'cts_promo_package' ) {
			return false;
		}

		// Get old listing priority, before it was promoted.
		if ( $listing_old_priority = get_post_meta( $listing_id, '_promo_package_old_priority', true ) ) {
			update_post_meta( $listing_id, '_featured', $listing_old_priority );
		} else {
			delete_post_meta( $listing_id, '_featured' );
		}

		// Delete other listing package meta.
		delete_post_meta( $listing_id, '_promo_package_id' );
		delete_post_meta( $listing_id, '_promo_package_old_priority' );

		// Trash package.
		wp_trash_post( $package_id );

		$this->flush_listings_cache();

		return true;
	}

	/**
	 * Flush listing explore cache, to take into account
	 * new priority settings for listings.
	 *
	 * @since 1.7.0.1
	 */
	public function flush_listings_cache() {
		if ( ! class_exists( '\WP_Job_Manager_Cache_Helper' ) ) {
			return false;
		}

		// Refresh cache.
		\WP_Job_Manager_Cache_Helper::get_transient_version( 'get_job_listings', true );
	}

	/**
	 * Filter 'listing-feed' widget args to make the
	 * 'Listing behavior' setting functional.
	 *
	 * @since 1.7.0
	 *
	 * @param array $args \WP_Query args.
	 * @param array $data Array of section settings.
	 */
	public function listing_feed_args( $args, $data ) {
		if ( $data['behavior'] === 'show_promoted_only' ) {
			$args['meta_query']['cts_promoted_only_clause'] = [
				'key' => '_featured',
				'value' => '0',
				'compare' => '>',
			];
		}

		if ( $data['behavior'] === 'hide_promoted' ) {
			$args['meta_query']['cts_hide_promoted_clause'] = [
				'relation' => 'OR',
				[
					'key' => '_featured',
					'value' => '1',
					'compare' => '<',
				],
				[
					'key' => '_featured',
					'compare' => 'NOT EXISTS',
				]
			];
		}

		return $args;
	}

	/**
	 * Setup promotions options page in WP Admin > Theme Options > Promotions.
	 *
	 * @since 2.0
	 */
	public function setup_options_page() {
		acf_add_options_sub_page( [
			'page_title' 	=> _x( 'Promotions', 'Promotions page title in WP Admin', 'my-listing' ),
			'menu_title'	=> _x( 'Promotions', 'Promotions menu title in WP Admin', 'my-listing' ),
			'menu_slug' 	=> 'theme-promotions-settings',
			'capability'	=> 'manage_options',
			'redirect'		=> false,
			'parent_slug'   => 'case27/tools.php',
		] );
	}
}

mylisting()->register( 'promotions', Promotions::instance() );
