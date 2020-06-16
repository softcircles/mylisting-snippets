<?php

namespace MyListing\Src\Forms;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Forms {

	private $pending_orders = [];

	public static function boot() {
		new self;
	}

	public function __construct() {
		// load posted form class for processing
		add_action( 'init', [ $this, 'load_posted_form' ] );

		// add 'my-listings' dashboard page
		add_action( 'mylisting/dashboard/endpoints-init', [ $this, 'user_listings_page' ] );

		// handle listing actions
		add_action( 'wp', [ $this, 'handle_listing_actions' ] );
		add_action( 'mylisting/user-listings/handle-action:delete', [ $this, 'handle_delete_action' ] );
		add_action( 'mylisting/user-listings/handle-view:edit', [ $this, 'render_edit_listing_form' ] );

		// display listing actions
		add_action( 'mylisting/user-listings/actions', [ $this, 'display_edit_action' ], 20 );
		add_action( 'mylisting/user-listings/actions', [ $this, 'display_delete_action' ], 90 );
		add_action( 'mylisting/user-listings/actions', [ $this, 'display_pending_payment_actions' ], 80 );
		add_filter( 'woocommerce_my_account_my_orders_query', [ $this, 'dashboard_orders_query' ] );
	}

	public function load_posted_form() {
		if ( ! empty( $_POST['job_manager_form'] ) ) {
			$form = $_POST['job_manager_form'];

			if ( $form === 'submit-listing' ) {
				Add_Listing_Form::instance();
			}

			if ( $form === 'edit-listing' ) {
				Edit_Listing_Form::instance();
			}
		}
	}

	public function user_listings_page( $wc_endpoints ) {
		$wc_endpoints->add_page( [
			'endpoint' => 'my-listings',
			'title' => __( 'My Listings', 'my-listing' ),
			'template' => [ $this, 'user_listings_page_content' ],
			'show_in_menu' => true,
			'order' => 2,
		] );
	}

	public function user_listings_page_content() {
		// If doing an action, show conditional content if needed
		if ( ! empty( $_REQUEST['action'] ) ) {
			$action = sanitize_title( $_REQUEST['action'] );
			if ( has_action( 'mylisting/user-listings/handle-view:' . $action ) ) {
				return do_action( 'mylisting/user-listings/handle-view:' . $action );
			}
		}

		$allowed_statuses = [ 'publish', 'pending', 'pending_payment', 'expired', 'preview' ];
		$active_status = ! empty( $_GET['status'] ) && in_array( $_GET['status'], $allowed_statuses, true ) ? $_GET['status'] : 'all';

		$pending_orders = [];
		if ( $active_status === 'pending_payment' ) {
			global $wpdb;
			$pending_order_list = $wpdb->get_results( $wpdb->prepare( "
				SELECT
				    {$wpdb->posts}.ID AS order_id,
				    {$wpdb->prefix}woocommerce_order_itemmeta.order_item_id AS order_item_id,
				    {$wpdb->prefix}woocommerce_order_itemmeta.meta_value AS listing_id
				FROM `{$wpdb->posts}`
				JOIN {$wpdb->prefix}woocommerce_order_items ON {$wpdb->prefix}woocommerce_order_items.order_id = {$wpdb->posts}.ID
				JOIN {$wpdb->prefix}woocommerce_order_itemmeta
					ON (
						{$wpdb->prefix}woocommerce_order_itemmeta.order_item_id = {$wpdb->prefix}woocommerce_order_items.order_item_id
						AND {$wpdb->prefix}woocommerce_order_itemmeta.meta_key = '_job_id'
					)
				WHERE {$wpdb->posts}.post_status IN ( 'wc-pending', 'wc-on-hold' )
				AND {$wpdb->posts}.post_author = %d
				ORDER BY {$wpdb->posts}.post_date DESC
			", get_current_user_id() ), ARRAY_A );

			foreach ( (array) $pending_order_list as $order_details ) {
				if ( ! isset( $pending_orders[ $order_details['listing_id'] ] ) ) {
					$pending_orders[ $order_details['listing_id'] ] = [];
				}

				$pending_orders[ $order_details['listing_id'] ][] = $order_details['order_id'];
			}

			$this->pending_orders = $pending_orders;
		}
		
		// get user listings
		$query = new \WP_Query;
		$query_args = [
			'post_type' => 'job_listing',
			'post_status' => $active_status === 'all' ? 'any' : $active_status,
			'ignore_sticky_posts' => 1,
			'posts_per_page' => 12,
			'paged' => ! empty( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 1,
			'orderby' => 'date',
			'order' => 'DESC',
			'author' => get_current_user_id(),
		];

		$listings = array_filter( array_map( function( $item ) {
			return \MyListing\Src\Listing::get( $item );
		}, $query->query( $query_args ) ) );

		$stats = mylisting()->stats()->get_user_stats( get_current_user_id() );

		mylisting_locate_template( 'templates/dashboard/my-listings.php', compact( 'query', 'listings', 'stats', 'active_status' ) );
	}

	public function handle_listing_actions() {
		if ( ! class_exists( '\WooCommerce' ) || ! is_wc_endpoint_url( 'my-listings' ) || empty( $_REQUEST['action'] ) || empty( $_REQUEST['job_id'] ) ) {
			return;
		}

		try {
			if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'mylisting_dashboard_actions' ) ) {
				throw new \Exception( _x( 'Invalid request.', 'User Dashboard > Listings > Actions', 'my-listing' ) );
			}

			$action = sanitize_title( $_REQUEST['action'] );
			$listing = \MyListing\Src\Listing::get( $_REQUEST['job_id'] );
			if ( ! ( $listing && $listing->editable_by_current_user() ) ) {
				throw new \Exception( _x( 'Invalid listing.', 'User Dashboard > Listings > Actions', 'my-listing' ) );
			}

			do_action( 'mylisting/user-listings/handle-action:'.$action, $listing );
		} catch ( \Exception $e ) {
			$this->add_action_message( $e->getMessage() );
		}
	}

	public function handle_delete_action( $listing ) {
		wp_trash_post( $listing->get_id() );
		$this->add_action_message( sprintf( _x( '%s has been deleted', 'User Dashboard > Listings > Actions', 'my-listing' ), $listing->get_name() ), 'error' );
	}

	public function render_edit_listing_form() {
		Edit_Listing_Form::instance()->render();
	}

	public function add_action_message( $message, $type = 'message' ) {
		add_action( 'mylisting/user-listings/before', function() use ( $message, $type ) {
			printf( '<div class="job-manager-%s">%s</div>', esc_attr( $type ), $message );
		} );
	}

	/**
	 * Display the `Edit` action for listings in in User Dashboard > My Listings.
	 *
	 * @since 1.0
	 */
	public function display_edit_action( $listing ) {
		$status = $listing->get_status();
		$can_edit_listing = $status === 'publish' || ( mylisting_get_setting( 'user_can_edit_pending_submissions' ) && $status === 'pending' );

		// only display for published, or pending listings if editing is allowed
		if ( ! $can_edit_listing ) {
			return;
		}

		$edit_url = add_query_arg( [
			'action' => 'edit',
			'job_id' => $listing->get_id()
		], wc_get_account_endpoint_url( 'my-listings' ) );

		printf(
			'<li class="cts-listing-action-edit">
				<a href="%s" class="job-dashboard-action-edit">%s</a>
			</li>',
			esc_url( $edit_url ),
			__( 'Edit', 'my-listing' )
		);
	}

	/**
	 * Display the `Delete` action for listings in in User Dashboard > My Listings.
	 *
	 * @since 1.0
	 */
	public function display_delete_action( $listing ) {
		if ( $listing->get_status() === 'pending_payment' && ! empty( $this->pending_orders[ $listing->get_id() ] ) ) {
			return;
		}

		$delete_url = add_query_arg( [
			'action' => 'delete',
			'job_id' => $listing->get_id()
		], wc_get_account_endpoint_url( 'my-listings' ) );

		printf(
			'<li class="cts-listing-action-delete">
				<a href="%s" class="job-dashboard-action-delete">%s</a>
			</li>',
			esc_url( wp_nonce_url( $delete_url, 'mylisting_dashboard_actions' ) ),
			__( 'Delete', 'my-listing' )
		);
	}

	public function display_pending_payment_actions( $listing ) {
		$add_listing_page = c27()->get_setting( 'general_add_listing_page' );

		if ( ! in_array( $listing->get_status(), [ 'pending_payment', 'preview' ], true ) ) {
			return;
		}

		if ( $listing->get_status() === 'pending_payment' && ! empty( $this->pending_orders[ $listing->get_id() ] ) ) { ?>
			<li class="cts-listing-action-view-order">
				<a href="<?php echo esc_url( add_query_arg( 'order_in', $this->pending_orders[ $listing->get_id() ], wc_get_account_endpoint_url( 'orders' ) ) ) ?>">
					<?php _ex( 'Order details', 'User dashboard', 'my-listing' ) ?>
				</a>
			</li>
		<?php } else {
			$resume_url = add_query_arg( [
				'listing_type' => $listing->type->get_slug(),
				'job_id' => $listing->get_id(),
			], $add_listing_page );

			printf(
				'<li class="cts-listing-action-resume">
					<a href="%s">%s</a>
				</li>',
				esc_url( $resume_url ),
				_x( 'Resume Submission', 'User listings dashboard', 'my-listing' )
			);
		}

	}

	public function dashboard_orders_query( $args ) {
		if ( ! empty( $_GET['show_pending'] ) ) {
			$args['status'] = [ 'wc-pending', 'wc-on-hold' ];
		}

		if ( ! empty( $_GET['order_in'] ) ) {
			$args['post__in'] = array_map( 'absint', (array) $_GET['order_in'] );
		}

		return $args;
	}

}
