<?php

namespace MyListing\Ext\Promotions;

class WooCommerce {

	use \MyListing\Src\Traits\Instantiatable;

	public
		$endpoint,    // WooCommerce endpoint name.
		$packages,    // Promotion packages.
		$package,     // Instance of \MyListing\Ext\Promotions\Package.
		$promotions;  // Instance of \MyListing\Ext\Promotions\Promotions.

	public function __construct() {
		$this->promotions = mylisting()->promotions();
		$this->package    = mylisting()->promotions()->package();

		// Add user dashboard endpoint.
		$this->setup_endpoint();

		/*** WooCommerce Product Settings ***/
		// Add WooCommerce custom product type.
		add_filter( 'product_type_selector', [ $this, 'add_product_type' ], 40 );

		// Set the custom product class to use.
		add_filter( 'woocommerce_product_class' , [ $this, 'set_product_class' ], 10, 3 );

		// Product backend settings.
		add_action( 'woocommerce_product_options_general_product_data', [ $this, 'promotion_settings' ] );

		// Save Product Data.
		add_filter( 'woocommerce_process_product_meta_promotion_package', [ $this, 'save_product_settings' ] );

		/*** Cart Settings ***/
		// Use simple add to cart.
		add_action( 'woocommerce_promotion_package_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );

		// Save listing on checkout.
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'checkout_create_order_line_item' ], 10, 4 );

		// Display listing in cart.
		add_filter( 'woocommerce_get_item_data', [ $this, 'get_listing_in_cart' ], 10, 2 );

		// Disable guest checkout when purchasing listing and enable checkout signup.
		add_filter( 'option_woocommerce_enable_signup_and_login_from_checkout', [ $this, 'enable_signup_and_login_from_checkout' ] );
		add_filter( 'option_woocommerce_enable_guest_checkout', [ $this, 'enable_guest_checkout' ] );

		/*** Order Settings ***/
		// Thank you page.
		add_action( 'woocommerce_thankyou', [ $this, 'woocommerce_thankyou' ], 5 );

		// Process order.
		add_action( 'woocommerce_order_status_processing', [ $this, 'order_paid' ] );
		add_action( 'woocommerce_order_status_completed', [ $this, 'order_paid' ] );
		add_action( 'woocommerce_order_status_cancelled', [ $this, 'order_cancelled' ] );

		/*** My Listings page in user dashboard ***/
		// Add 'Promote' link in listing actions.
		add_filter( 'mylisting/user-listings/actions', [ $this, 'display_promote_listing_action' ], 10 );

		// Include 'promote-listing' modal to footer.
		add_action( 'mylisting/get-footer', [ $this, 'get_promotions_modal' ] );

		// Enqueue script to handle the promotion modal.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_promotions_script' ], 50 );
	}

	/**
	 * Setup the Promotions endpoint in user dashboard area.
	 *
	 * @since 1.7.0
	 */
	public function setup_endpoint() {
		// Set the endpoint name for promotions in user dashboard.
		$this->endpoint = apply_filters( 'mylisting/promotions/url-endpoint', 'promotions' );

		// Add page.
		mylisting()->woocommerce()->add_dashboard_page( [
			'endpoint' => $this->endpoint,
			'title' => _x( 'Promotions', 'User Dashboard > Promotions page title', 'my-listing' ),
			'template' => locate_template( 'templates/dashboard/promotions/dashboard.php' ),
			'show_in_menu' => true,
			'order' => 3,
		] );
	}

	/**
	 * Add custom product type.
	 *
	 * @since  1.7.0
	 */
	public function add_product_type( $types ) {
		$types['promotion_package'] = esc_html__( 'Promotion Package', 'my-listing' );
		return $types;
	}

	/**
	 * Set Product Class to Load.
	 *
	 * @since 1.7.0
	 */
	public function set_product_class( $classname, $product_type ) {
		if ( 'promotion_package' === $product_type ) {
			return 'MyListing\Ext\Promotions\Product';
		}

		return $classname;
	}

	/**
	 * Add promotion product settings.
	 *
	 * @since 1.7.0
	 */
	public function promotion_settings() {
		global $post;
		$post_id = $post->ID;
		?>
		<div class="options_group show_if_promotion_package">

			<?php woocommerce_wp_text_input( array(
				'id'                => '_promotion_duration',
				'label'             => __( 'Promotion duration (in days)', 'my-listing' ),
				'description'       => __( 'The number of days that the listing will be promoted.', 'my-listing' ),
				'value'             => get_post_meta( $post_id, '_promotion_duration', true ),
				'placeholder'       => 14,
				'desc_tip'          => true,
				'type'              => 'number',
				'custom_attributes' => array(
					'min'   => '',
					'step' 	=> '1',
				),
			) ) ?>

			<?php woocommerce_wp_text_input( array(
				'id'                => '_promotion_priority',
				'label'             => __( 'Promotion priority', 'my-listing' ),
				'description'       => __( 'Higher value gives listing with this package more priority. Featured listings have priority set to 1.', 'my-listing' ),
				'value'             => get_post_meta( $post_id, '_promotion_priority', true ),
				'placeholder'       => 2,
				'desc_tip'          => true,
				'type'              => 'number',
				'custom_attributes' => array(
					'min'   => '',
					'step' 	=> '1',
				),
			) ) ?>

			<script type="text/javascript">
				jQuery( function() {
					jQuery( '.pricing' ).addClass( 'show_if_promotion_package' );
					jQuery( '._tax_status_field' ).closest( 'div' ).addClass( 'show_if_promotion_package' );
					jQuery( '#product-type' ).change();
				} );
			</script>
		</div>
		<?php
	}

	/**
	 * Save promotion product settings.
	 *
	 * @since 1.7.0
	 */
	public function save_product_settings( $post_id ) {
		// Duration.
		if ( ! empty( $_POST['_promotion_duration'] ) ) {
			update_post_meta( $post_id, '_promotion_duration', absint( $_POST['_promotion_duration'] ) );
		} else {
			delete_post_meta( $post_id, '_promotion_duration' );
		}

		// Priority.
		if ( ! empty( $_POST['_promotion_priority'] ) ) {
			update_post_meta( $post_id, '_promotion_priority', absint( $_POST['_promotion_priority'] ) );
		} else {
			delete_post_meta( $post_id, '_promotion_priority' );
		}
	}

	/**
	 * Set the order line item's meta data prior to being saved.
	 *
	 * @since 1.7.0
	 *
	 * @param WC_Order_Item_Product $order_item
	 * @param string                $cart_item_key  The hash used to identify the item in the cart
	 * @param array                 $cart_item_data The cart item's data.
	 * @param WC_Order              $order          The order or subscription object to which the line item relates
	 */
	public function checkout_create_order_line_item( $order_item, $cart_item_key, $cart_item_data, $order ) {
		if ( isset( $cart_item_data['listing_id'] ) ) {
			$order_item->update_meta_data( '_listing_id', $cart_item_data['listing_id'] );
		}
	}

	/**
	 * Output listing name in cart
	 *
	 * @since 1.7.0
	 *
	 * @param  array $data
	 * @param  array $cart_item
	 * @return array
	 */
	public function get_listing_in_cart( $data, $cart_item ) {
		if ( isset( $cart_item['listing_id'] ) ) {
			$data[] = [
				'name'  => esc_html__( 'Listing', 'my-listing' ),
				'value' => get_the_title( absint( $cart_item['listing_id'] ) ),
			];
		}
		return $data;
	}

	/**
	 * When cart contains a promotion package, always set to "yes".
	 *
	 * @since 1.7.0
	 */
	public function enable_signup_and_login_from_checkout( $value ) {
		global $woocommerce;
		$contains_package = false;
		if ( ! empty( $woocommerce->cart->cart_contents ) ) {
			foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
				$product = $cart_item['data'];
				if ( $product instanceof \WC_Product && $product->is_type( array( 'promotion_package' ) ) ) {
					$contains_package = true;
				}
			}
		}

		return $contains_package ? 'yes' : $value;
	}

	/**
	 * When cart contains a promotion package, always set to "no".
	 *
	 * @since 1.7.0
	 */
	public function enable_guest_checkout( $value ) {
		global $woocommerce;
		$contains_package = false;
		if ( ! empty( $woocommerce->cart->cart_contents ) ) {
			foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
				$product = $cart_item['data'];
				if ( $product instanceof \WC_Product && $product->is_type( array( 'promotion_package' ) ) ) {
					$contains_package = true;
				}
			}
		}

		return $contains_package ? 'no' : $value;
	}

	/**
	 * Thank you page after checkout completed.
	 *
	 * @since 1.7.0
	 */
	public function woocommerce_thankyou( $order_id ) {
		global $wp_post_types;
		$order = wc_get_order( $order_id );
		$is_paid = in_array( $order->get_status(), array( 'completed', 'processing' ) );

		foreach ( $order->get_items() as $item ) {
			if ( isset( $item['listing_id'] ) ) {
				$listing_status = get_post_status( $item['listing_id'] );

				if ( $is_paid ) {
					echo wpautop( sprintf( __( '"%s" has been promoted successfully.', 'my-listing' ), get_the_title( $item['listing_id'] ) ) );
				} else {
					echo wpautop( sprintf( __( '"%s" will be promoted once the order is verified and completed.', 'my-listing' ), get_the_title( $item['listing_id'] ) ) );
				}
			}
		}
	}

	/**
	 * Triggered when an order is paid.
	 *
	 * @since 1.7.0
	 */
	public function order_paid( $order_id ) {
		// Get the order
		$order = wc_get_order( $order_id );

		// Bail if already processed.
		if ( get_post_meta( $order_id, 'promotion_packages_processed', true ) ) {
			return false;
		}

		foreach ( $order->get_items() as $item ) {
			$product = wc_get_product( $item['product_id'] );

			if ( ! $product->is_type( array( 'promotion_package' ) ) || ! $order->get_customer_id() ) {
				continue;
			}

			// Give packages to user
			$package_id = false;
			for ( $i = 0; $i < $item['qty']; $i++ ) {
				$package_id = wp_insert_post( [
					'post_type'   => 'cts_promo_package',
					'post_status' => 'publish',
					'meta_input'  => [
						'_user_id'    => $order->get_customer_id(),
						'_product_id' => $product->get_id(),
						'_order_id'   => $order_id,
						'_duration'   => $product->get_duration(),
						'_priority'   => $product->get_priority(),
					],
				] );

				if ( ! $package_id || is_wp_error( $package_id ) || empty( $item['listing_id'] ) ) {
					continue;
				}

				mylisting()
					->promotions()
					->activate_package( $package_id, $item['listing_id'] );
			}
		}

		// Mark that this order already processed.
		update_post_meta( $order_id, 'promotion_packages_processed', true );
	}

	/**
	 * Fires when an order was cancelled. Looks for promotion packages in order and deletes the package if found.
	 *
	 * @since 1.7.0
	 */
	public function order_cancelled( $order_id ) {
		// Get packages.
		$packages = get_posts( [
			'post_type'        => 'cts_promo_package',
			'post_status'      => 'any',
			'posts_per_page'   => -1,
			'suppress_filters' => false,
			'fields'           => 'ids',
			'meta_query' => [
				'relation' => 'AND',
				[
					'key'     => '_order_id',
					'value'   => $order_id,
					'compare' => 'IN',
				],
			],
		] );

		if ( $packages && is_array( $packages ) ) {
			foreach ( $packages as $package_id ) {
				wp_update_post( array(
					'ID'          => $package_id,
					'post_status' => 'promotion_cancelled',
				) );
			}
		}
	}

	/**
	 * Add 'Promote' link in My Listings page in user dashboard.
	 *
	 * @since 1.7.0
	 */
	public function display_promote_listing_action( $listing ) {

		if ( $listing->get_status() == 'pending' || $listing->get_status() == 'pending_payment' ) {
			return printf(
			'<li class="cts-listing-action-promote" >
				<a
					class="listing-dashboard-action-promote promot_btn"
					data-toggle="modal"
					data-listing-id="%d"
					data-listing-name="%s"
				>%s</a>
			</li>',
			esc_attr( $listing->get_id() ),
			esc_attr( $listing->get_name() ),
			_x( 'Promote', 'Promote listing link name', 'my-listing' )
		);
			
		}

		if ( $listing->get_status() !== 'publish' ) {
			return;
		}

		$package_id = mylisting()->promotions()->package()->get_listing_package( $listing->get_id() );

		if ( $package_id ) {
			return printf(
				'<li class="cts-listing-action-promote listing-promoted">
					<a href="%s" class="listing-action-promoted">%s</a>
				</li>',
				$this->package->get_edit_link( $package_id ),
				_x( 'Promoted', 'Promoted listing link name', 'my-listing' )
			);
		}


		return printf(
			'<li class="cts-listing-action-promote">
				<a
					class="listing-dashboard-action-promote"
					data-toggle="modal"
					data-target="#promo-modal"
					data-listing-id="%d"
					data-listing-name="%s"
				>%s</a>
			</li>',
			esc_attr( $listing->get_id() ),
			esc_attr( $listing->get_name() ),
			_x( 'Promote', 'Promote listing link name', 'my-listing' )
		);
	}

	/**
	 * Get all 'Promotion Package' products.
	 *
	 * @since 1.7.0
	 */
	public function get_products() {
		if ( is_array( $this->packages ) ) {
			return $this->packages;
		}

		$this->packages = wc_get_products( [
			'post_type'        => 'product',
			'posts_per_page'   => -1,
			'order'            => 'ASC',
			'orderby'          => 'meta_value_num',
			'meta_key'         => '_price',
			'suppress_filters' => false,
			'tax_query'        => [
				'relation' => 'AND',
				[
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => [ 'promotion_package' ],
					'operator' => 'IN',
				],
			],
		] );

		return $this->packages;
	}

	/**
	 * Output 'Choose Promotion' modal in My Listings
	 * page in user dashboard.
	 *
	 * @since 1.7.0
	 */
	public function get_promotions_modal() {
		if ( ! is_wc_endpoint_url( 'my-listings' ) ) {
			return false;
		}

		// List of products of 'Promotion Package' type.
		$products = $this->get_products();

		// List of packages owned by the user (cts_promo_package type).
		$packages = $this->package->get_packages( [
			'post_status' => 'publish',
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

		return require locate_template( 'templates/dashboard/promotions/choose-promotion.php' );
	}

	public function enqueue_promotions_script() {
		if ( ! is_wc_endpoint_url( 'my-listings' ) && ! is_wc_endpoint_url( 'promotions' ) ) {
			return false;
		}

		ob_start(); ?>
		<script type="text/javascript">
			jQuery( function( $ ) {
				var promotions = {
					listing_id: null,
					listing_name: null,
					package_id: null,
				};
				var container = $( '#promo-modal .sign-in-box' );

				$( '.listing-dashboard-action-promote' ).click(function(e) {
					promotions.listing_id   = $(this).data('listing-id');
					promotions.listing_name = $(this).data('listing-name');
					$( '#promo-modal .listing-name' ).text( '"' + promotions.listing_name + '"' );
				});

				$( '#promo-modal .promo-product-item, .promo-product-item .process-promotion' ).click(function(e) {
					e.preventDefault();

					// Get clicked package id if available.
					if ( $(this).data('package-id') ) {
						promotions.package_id = $(this).data('package-id');
					}

					// Get clicked listing id if available.
					if ( $(this).data('listing-id') ) {
						promotions.listing_id = $(this).data('listing-id');
					}

					// process can be 'buy-package', 'use-package', or 'cancel-package'.
	                if ( $(this).data('process') ) {
	                    promotions.process = $(this).data('process');
	                } else {
	                    promotions.process = 'buy-package';
	                }

					// console.log(promotions);

					if ( promotions.process === 'cancel-package' && ! confirm( CASE27.l10n.irreversible_action ) ) {
						return false;
					}

					// Indicate that the request is being processed.
					container.addClass('cts-processing-login');

					// Make AJAX request.
                    jQuery.ajax( {
                        url: CASE27.ajax_url + '?action=cts_promotions&security=' + CASE27.ajax_nonce,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                        	listing_id: promotions.listing_id,
                        	package_id: promotions.package_id,
                        	process: promotions.process,
                        },
                        success: function( response ) {
		                    if ( typeof response === 'object' ) {
		                        if ( response.redirect ) {
		                            window.location.replace( response.redirect );
		                        }

		                        if ( response.status === 'error' && response.message ) {
		                            alert( response.message );
		                        }
		                    }
		                },
                        error: function( xhr, status, error ) { console.log('Failed', xhr, status, error); },
                        complete: function() { container.removeClass('cts-processing-login'); }
                    } );
				});
			} );
		</script>
		<?php // wp_add_inline_script() throws a warning when including <script> tags.
        wp_add_inline_script( 'c27-main', trim( preg_replace( '#<script[^>]*>(.*)</script>#is', '$1', ob_get_clean() ) ), 'before' );
	}
}
