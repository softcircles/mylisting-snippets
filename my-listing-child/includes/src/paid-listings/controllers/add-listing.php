<?php
/**
 * Add Listing form payments handler.
 *
 * @since 1.6
 */

namespace MyListing\Src\Paid_Listings\Controllers;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Add_Listing {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		// account is required, because $0 package will skip order flow
		add_filter( 'mylisting/settings/submission_requires_account', '__return_true' );

		// add package processing steps in Add Listing form
		add_filter( 'mylisting/submission-steps', [ $this, 'submit_listing_steps' ], 20 );

		// fires after the subscription has been activated, and the payment package has been created
		add_action( 'mylisting/subscriptions/submission/order-processed', [ $this, 'subscription_processed' ], 10, 2 );

		// fires after the order has been paid and processed and the payment package has been created
		add_action( 'mylisting/payments/submission/order-processed', [ $this, 'order_processed' ], 10, 2 );

		// fires after the listing preview, before the user is redirected to checkout
		add_action( 'mylisting/payments/submission/product-selected', [ $this, 'product_selected' ], 10, 2 );

		// if the users is submitting a listing with a pre-owned pacakge, assign it to the listing
		add_action( 'mylisting/payments/submission/use-available-package', [ $this, 'use_available_package' ], 10, 2 );

		// if `skip-checkout` has been configured for a free package, bypass the cart and create the payment package
		add_action( 'mylisting/payments/submission/use-free-package', [ $this, 'use_free_package' ], 10, 2 );
	}

	/**
	 * Submit listing steps.
	 *
	 * @since 1.6
	 */
	public function submit_listing_steps( $steps ) {
		// retrieve and sanitize active listing and type ids if available
		$listing_id = ! empty( $_REQUEST['job_id'] ) ? absint( $_REQUEST['job_id'] ) : false;
		$listing_type = ! empty( $_REQUEST['listing_type'] ) ? $_REQUEST['listing_type'] : false;

		// if a listing id is available and valid, get the listing type instance from it (e.g. on prevew step handler)
		if ( $listing_id && ( $listing = \MyListing\Src\Listing::get( $listing_id ) ) && $listing->type ) {
			$type = $listing->type;
			// mlog( 'Type ID retrieved from given listing: '.$listing->get_id() );

		// if the listing id isn't available yet, e.g. in add listing form step handler, then retrieve the listing type from request
		} elseif ( $listing_type && ( $listing_type_obj = \MyListing\Src\Listing_Type::get_by_name( $listing_type ) ) ) {
			$type = $listing_type_obj;
			// mlog( 'Type ID retrieved from request.' );

		// otherwise, invalid listing type
		} else {
			$type = false;
			// mlog( 'No listing type was found.' );
		}

		// Check if paid listings are disabled for the active listing type.
		if ( $type && $type->settings['packages']['enabled'] === false ) {
			return $steps;
		}

		if ( ! ( ! empty( $_REQUEST['listing_package'] ) && ! empty( $_REQUEST['skip_selection'] ) ) ) {
			$steps['wc-choose-package'] = [
				'name'     => __( 'Choose a package', 'my-listing' ),
				'view'     => [ $this, 'choose_package' ],
				'handler'  => [ $this, 'choose_package_handler' ],
				'priority' => 5,
			];
		}

		if ( ( isset( $_REQUEST['submit_job'] ) && $_REQUEST['submit_job'] !== 'save--no-preview' ) 
			|| ( isset( $_REQUEST['continue'] ) && ! empty( $_REQUEST['continue'] ) )
		) {
			$steps['wc-process-package'] = [
				'name'     => '',
				'view'     => false,
				'handler'  => [ $this, 'process_package_handler' ],
				'priority' => 25,
			];
		}

		return $steps;
	}

	/**
	 * Choose Package View
	 *
	 * @since 1.0
	 */
	public function choose_package() {
		if ( empty( $_REQUEST['listing_type'] ) || ! ( $type = \MyListing\Src\Listing_Type::get_by_name( $_REQUEST['listing_type'] ) ) ) {
			return;
		}

		$form = \MyListing\Src\Forms\Add_Listing_Form::instance();
		$tree = \MyListing\Src\Paid_Listings\Util::get_package_tree_for_listing_type( $type, 'add-listing' );
		$listing_id = ! empty( $_GET['job_id'] ) ? absint( $_GET['job_id'] ) : $form->get_job_id();
		?>
		<section class="i-section c27-packages">
			<div class="container">
				<div class="row section-title">
					<h2 class="case27-primary-text"><?php _e( 'Choose a Package', 'my-listing' ) ?></h2>
				</div>
				<form method="post" id="job_package_selection">
					<div class="job_listing_packages">

						<?php require locate_template( 'templates/add-listing/choose-package.php' ) ?>

						<div class="hidden">
							<input type="hidden" name="job_id" value="<?php echo esc_attr( $listing_id ) ?>">
							<input type="hidden" name="step" value="<?php echo esc_attr( $form->get_step() ) ?>">
							<input type="hidden" name="job_manager_form" value="<?php echo esc_attr( $form->form_name ) ?>">
							<?php if ( ! empty( $_REQUEST['listing_type'] ) ): ?>
								<input type="hidden" name="listing_type" value="<?php echo esc_attr( $_REQUEST['listing_type'] ) ?>">
							<?php endif ?>
						</div>
					</div>
				</form>
			</div>
		</section>
		<?php
	}

	/**
	 * Choose package step handler.
	 *
	 * @since 1.0.0
	 */
	public function choose_package_handler() {
		$form = \MyListing\Src\Forms\Add_Listing_Form::instance();
		try {
			if ( empty( $_REQUEST['listing_package'] ) || empty( $_REQUEST['listing_type'] ) ) {
				throw new \Exception( _x( 'No package selected.', 'Listing submission', 'my-listing' ) );
			}

			if ( ! \MyListing\Src\Paid_Listings\Util::validate_package( $_REQUEST['listing_package'], $_REQUEST['listing_type'] ) ) {
				throw new \Exception( _x( 'Chosen package is not valid.', 'Listing submission', 'my-listing' ) );
			}

			// Validate claim packages that are hidden from add listing
			if ( ! empty( $_REQUEST['listing_type'] ) && $type = \MyListing\Src\Listing_Type::get_by_name( $_REQUEST['listing_type'] ) ) {
				if ( get_post( $product_id = $_REQUEST['listing_package'] )->post_type === 'product' ) {
					$package = \MyListing\Src\Paid_Listings\Util::get_package_tree_for_listing_type( $type, 'add-listing', $product_id );
					if (empty($package)) {
						throw new \Exception( _x( 'Chosen package is not valid.', 'Listing submission', 'my-listing' ) );
					}

				} elseif ( get_post( $product_id = $_REQUEST['listing_package'] )->post_type === 'case27_user_package' && is_user_logged_in() ) {
					$product_id = ( get_post($product_id)->post_type === 'case27_user_package' ) ? get_post_meta( get_post($product_id)->ID, '_product_id', true ) : $package->ID;
					$package = \MyListing\Src\Paid_Listings\Util::get_package_tree_for_listing_type( $type, 'add-listing', $product_id );
					if (empty($package)) {
						throw new \Exception( _x( 'Chosen package is not valid.', 'Listing submission', 'my-listing' ) );
					}
				}
			}

			// Package is valid.
			$package = get_post( $_REQUEST['listing_package'] );

			// Store selection in cookie.
			wc_setcookie( 'chosen_package_id', absint( $package->ID ) );

			if ( mylisting_get_setting( 'submission_requires_account' ) && ! is_user_logged_in() ) {
				$redirect = add_query_arg( [
					'listing_package' => $package->ID,
					'skip_selection' => 1,
				], \MyListing\get_current_url() );

				return wp_safe_redirect( add_query_arg(
					'redirect',
					urlencode( $redirect ),
					\MyListing\get_login_url()
				) );
			}

			// Go to next step.
			$form->next_step();
		} catch (\Exception $e) {
			// Log error message and reset form step.
			$form->add_error( $e->getMessage() );
			$form->set_step( array_search( 'wc-choose-package', array_keys( $form->get_steps() ) ) );
		}
	}

	/**
	 * Process package step handler.
	 *
	 * @since 1.0
	 */
	public function process_package_handler() {
		$form = \MyListing\Src\Forms\Add_Listing_Form::instance();
		$listing_id = $form->get_job_id();
		
		try {
			if ( empty( $_COOKIE['chosen_package_id'] ) || ! $listing_id ) {
				throw new \Exception( _x( 'Couldn\'t process package.', 'Listing submission', 'my-listing' ) );
			}

			$listing = \MyListing\Src\Listing::get( $listing_id );
			$package = get_post( $_COOKIE['chosen_package_id'] );
			if ( ! ( $listing && $listing->type && $listing->editable_by_current_user() && $package && in_array( $package->post_type, [ 'product', 'case27_user_package' ] ) ) ) {
				throw new \Exception( _x( 'Invalid request.', 'Listing submission', 'my-listing' ) );
			}

			if ( empty( $_REQUEST['listing_package'] ) || absint( $_REQUEST['listing_package'] ) !== absint( $package->ID ) ) {
				throw new \Exception( _x( 'Invalid package.', 'Listing submission', 'my-listing' ) );
			}

			if ( ! \MyListing\Src\Paid_Listings\Util::validate_package( $_REQUEST['listing_package'], $listing->type->get_slug() ) ) {
				throw new \Exception( _x( 'You are aleady reached the Package limit.', 'Listing submission', 'my-listing' ) );
			}

			$allowed_product_ids = array_map( 'absint', array_column(
				$listing->type->get_packages(),
				'package'
			) );

			// use available package
			if ( $package->post_type === 'case27_user_package' ) {
				$pkg = \MyListing\Src\Package::get( $package );
				if ( ! empty( $allowed_product_ids ) && ! in_array( absint( $pkg->get_product_id() ), $allowed_product_ids, true ) ) {
					throw new \Exception( _x( 'Invalid package.', 'Listing submission', 'my-listing' ) );
				}

				do_action( 'mylisting/payments/submission/use-available-package', $listing, $pkg );
			}

			// buy new product
			if ( $package->post_type === 'product' ) {
				if ( ! empty( $allowed_product_ids ) && ! in_array( absint( $package->ID ), $allowed_product_ids, true ) ) {
					throw new \Exception( _x( 'Invalid package.', 'Listing submission', 'my-listing' ) );
				}

				$product = wc_get_product( $package->ID );
				if ( ! ( $product && $product->is_type( [ 'job_package', 'job_package_subscription' ] ) ) ) {
					throw new \Exception( _x( 'Invalid product.', 'Listing submission', 'my-listing' ) );
				}

				$skip_checkout = apply_filters( 'mylisting\packages\free\skip-checkout', true ) === true;

				// if `skip-checkout` setting is enabled for free products, create the user package and assign it to the listing
				if ( $product->get_price() == 0 && $skip_checkout && $product->get_meta( '_disable_repeat_purchase' ) !== 'yes' ) {
					do_action( 'mylisting/payments/submission/use-free-package', $listing, $product );
				} else {
					// proceed to checkout
					do_action( 'mylisting/payments/submission/product-selected', $listing, $product );
				}
			}

			// Go to next step.
			$form->next_step();
		} catch (\Exception $e) {
			// Log error message.
			$form->add_error( $e->getMessage() );
		}
	}

	/**
	 * Fires after the listing preview, before the user is redirected to checkout. Update
	 * listing status to `pending_payment`, listing duration, priority, verified
	 * status, etc. and redirect to checkout.
	 *
	 * @since 2.1.6
	 */
	public function product_selected( $listing, $product ) {
		// otherwise, apply product attributes
		update_post_meta( $listing->get_id(), '_job_duration', $product->get_duration() );
		update_post_meta( $listing->get_id(), '_featured', $product->is_listing_featured() ? 1 : 0 );
		update_post_meta( $listing->get_id(), '_package_id', $product->get_id() );
		update_post_meta( $listing->get_id(), '_claimed', $product->mark_verified() ? 1 : 0 );

		if ( isset( $_REQUEST['submit_job'] ) && $_REQUEST['submit_job'] == 'save--no-preview' ) {

			// clear cookie
			wc_setcookie( 'chosen_package_id', '', time() - HOUR_IN_SECONDS );

			$redirect_url = add_query_arg( [
				'listing_type' => $listing->type->get_slug(),
				'job_id' => $listing->get_id(),
			], wc_get_account_endpoint_url( \MyListing\my_listings_endpoint_slug() ) );

			// redirect to dashboard page successfully
			wp_redirect( $redirect_url );
			exit;

		} else {
			// update status from `preview` to `pending_payment`
			wp_update_post( [
				'ID' => $listing->get_id(),
				'post_status' => 'pending_payment',
				'post_date' => current_time( 'mysql' ),
				'post_date_gmt' => current_time( 'mysql', 1 ),
				'post_author' => get_current_user_id(),
			] );

			// add package to cart, and redirect
			$data = [
				'job_id' => $listing->get_id(),
				'assignment_type' => 'submission',
			];

			WC()->cart->add_to_cart( $product->get_id(), 1, '', '', $data );

			// clear cookie
			wc_setcookie( 'chosen_package_id', '', time() - HOUR_IN_SECONDS );

			// if the user has other items in their cart, redirect to cart page instead
			// to avoid any accidental purchases
			$redirect_url = WC()->cart->get_cart_contents_count() > 1
				? wc_get_cart_url()
				: wc_get_checkout_url();

			// redirect to checkout page
			wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Fires after the subscription has been activated, and the payment package
	 * has been created. Update listing status and assign the package.
	 *
	 * @since 2.1.6
	 */
	public function subscription_processed( $listing, $package ) {
		$listing_status = mylisting_get_setting( 'submission_requires_approval' ) ? 'pending' : 'publish';
		if ( $listing->get_status() === 'draft' ) {
			$listing_status = 'draft';
		}

		wp_update_post( [
			'ID' => $listing->get_id(),
			'post_status' => $listing_status,
		] );

		$package->assign_to_listing( $listing->get_id() );
	}

	/**
	 * After the order has been paid and processed and the payment package is
	 * created, update the listing package and status.
	 *
	 * @since 2.1.6
	 */
	public function order_processed( $listing, $package ) {

		if ( isset( $_REQUEST['submit_job'] ) && $_REQUEST['submit_job'] == 'save--no-preview' ) {
			$listing_status = 'draft';
		} else {
			$listing_status = mylisting_get_setting( 'submission_requires_approval' ) ? 'pending' : 'publish';
		}

		wp_update_post( [
			'ID' => $listing->get_id(),
			'post_status' => $listing_status,
		] );

		$package->assign_to_listing( $listing->get_id() );
	}

	/**
	 * If the users is submitting a listing with a pre-owned
	 * pacakge, assign it to the listing immediately.
	 *
	 * @since 2.1.6
	 */
	public function use_available_package( $listing, $package ) {
		if ( ! $package->belongs_to_current_user() ) {
			throw new \Exception( _x( 'Couldn\'t process package.', 'Listing submission', 'my-listing' ) );
		}

		if ( isset( $_REQUEST['submit_job'] ) && $_REQUEST['submit_job'] == 'save--no-preview' ) {
			$listing_status = 'draft';
		} else {
			$listing_status = mylisting_get_setting( 'submission_requires_approval' ) ? 'pending' : 'publish';
		}

		wp_update_post( [
			'ID' => $listing->get_id(),
			'post_status' => $listing_status,
		] );

		$package->assign_to_listing( $listing->get_id() );
	}

	/**
	 * If `skip-checkout` has been configured for a free package,
	 * bypass the cart and create the payment package.
	 *
	 * @since 2.1.6
	 */
	public function use_free_package( $listing, $product ) {
		$package = \MyListing\Src\Package::create( [
			'user_id'        => get_current_user_id(),
			'product_id'     => $product->get_id(),
			'duration'       => $product->get_duration(),
			'limit'          => $product->get_limit(),
			'featured'       => $product->is_listing_featured(),
			'mark_verified'  => $product->mark_verified(),
			'use_for_claims' => $product->use_for_claims(),
			'is_claimable' => $product->is_claimable(),
			'hide_in_add_listing' => $product->hide_in_add_listing(),
			'order_id'       => false,
		] );

		if ( ! $package ) {
			throw new \Exception( _x( 'Couldn\'t create package.', 'Listing submission', 'my-listing' ) );
		}

		if ( isset( $_REQUEST['submit_job'] ) && $_REQUEST['submit_job'] == 'save--no-preview' ) {
			$listing_status = 'draft';
		} else {
			$listing_status = mylisting_get_setting( 'submission_requires_approval' ) ? 'pending' : 'publish';
		}

		wp_update_post( [
			'ID' => $listing->get_id(),
			'post_status' => $listing_status,
		] );

		$package->assign_to_listing( $listing->get_id() );
	}
}
