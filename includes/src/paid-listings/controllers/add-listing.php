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

		// if the lsiting id isn't available yet, e.g. in add listing form step handler, then retrieve the listing type from request
		} elseif ( $listing_type && ( $listing_type_obj = \MyListing\Ext\Listing_Types\Listing_Type::get_by_name( $listing_type ) ) ) {
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

		$steps['wc-choose-package'] = [
			'name'     => __( 'Choose a package', 'my-listing' ),
			'view'     => [ $this, 'choose_package' ],
			'handler'  => [ $this, 'choose_package_handler' ],
			'priority' => 5,
		];

		$steps['wc-process-package'] = [
			'name'     => '',
			'view'     => false,
			'handler'  => [ $this, 'process_package_handler' ],
			'priority' => 25,
		];

		return $steps;
	}

	/**
	 * Choose Package View
	 *
	 * @since 1.0
	 */
	public function choose_package() {
		if ( empty( $_REQUEST['listing_type'] ) || ! ( $type = \MyListing\Ext\Listing_Types\Listing_Type::get_by_name( $_REQUEST['listing_type'] ) ) ) {
			return;
		}

		$form = \MyListing\Src\Forms\Add_Listing_Form::instance();
		$tree = \MyListing\Src\Paid_Listings\Util::get_package_tree_for_listing_type( $type );

		$listing_id = ! empty( $_GET['job_id'] ) ? absint( $_GET['job_id'] ) : $form->get_job_id();
		?>
		<section class="i-section c27-packages">
			<div class="container">
				<?php if ( mylisting_get_setting( 'submission_requires_account' ) && ! is_user_logged_in() ) : ?>
					<div class="row section-title">
						<h2 class="case27-primary-text"><?php _e( 'Choose a Package', 'my-listing' ) ?></h2>
					</div>
				<?php endif;?>

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
			if ( empty( $_POST['listing_package'] ) || empty( $_REQUEST['listing_type'] ) ) {
				throw new \Exception( _x( 'No package selected.', 'Listing submission', 'my-listing' ) );
			}

			if ( ! \MyListing\Src\Paid_Listings\Util::validate_package( $_POST['listing_package'], $_REQUEST['listing_type'] ) ) {
				throw new \Exception( _x( 'Chosen package is not valid.', 'Listing submission', 'my-listing' ) );
			}

			// Package is valid.
			$package = get_post( $_POST['listing_package'] );

			// Store selection in cookie.
			wc_setcookie( 'chosen_package_id', absint( $package->ID ) );

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
			if ( ! ( $listing && $listing->editable_by_current_user() && $package && in_array( $package->post_type, [ 'product', 'case27_user_package' ] ) ) ) {
				throw new \Exception( _x( 'Invalid request.', 'Listing submission', 'my-listing' ) );
			}

			// use available package
			if ( $package->post_type === 'case27_user_package' ) {
				do_action( 'mylisting/payments/submission/use-available-package', $listing, \MyListing\Src\Package::get( $package ) );
			}

			// buy new product
			if ( $package->post_type === 'product' ) {
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

		// redirect to checkout page
		wp_redirect( get_permalink( wc_get_page_id( 'checkout' ) ) );
		exit;
	}

	/**
	 * Fires after the subscription has been activated, and the payment package
	 * has been created. Update listing status and assign the package.
	 *
	 * @since 2.1.6
	 */
	public function subscription_processed( $listing, $package ) {
		wp_update_post( [
			'ID' => $listing->get_id(),
			'post_status' => mylisting_get_setting( 'submission_requires_approval' ) ? 'pending' : 'publish',
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
		wp_update_post( [
			'ID' => $listing->get_id(),
			'post_status' => mylisting_get_setting( 'submission_requires_approval' ) ? 'pending' : 'publish',
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
		wp_update_post( [
			'ID' => $listing->get_id(),
			'post_status' => mylisting_get_setting( 'submission_requires_approval' ) ? 'pending' : 'publish',
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
			'order_id'       => false,
		] );

		if ( ! $package ) {
			throw new \Exception( _x( 'Couldn\'t create package.', 'Listing submission', 'my-listing' ) );
		}

		wp_update_post( [
			'ID' => $listing->get_id(),
			'post_status' => mylisting_get_setting( 'submission_requires_approval' ) ? 'pending' : 'publish',
		] );

		$package->assign_to_listing( $listing->get_id() );
	}
}
