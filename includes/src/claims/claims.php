<?php

namespace MyListing\Src\Claims;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Claims {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		// register post type
		add_action( 'init', [ $this, 'register_claim_post_type' ] );

		// Add title.
		add_filter( 'the_title', [ $this, 'claim_title' ], 10, 2 );

		if ( is_user_logged_in() ) {

			$user = wp_get_current_user();
			if ( in_array( 'business', (array) $user->roles ) ) {
				add_action( 'mylisting/single/quick-actions/claim-listing', [ $this, 'display_claim_quick_action' ], 30, 2 );	
			}
		} else {
			add_action( 'mylisting/single/quick-actions/claim-listing', [ $this, 'display_claim_quick_action' ], 30, 2 );
		}

		// Cover button output.

		// Claim shortcode.
		add_action( 'init', function() {
			add_shortcode( 'claim_listing', [ $this, 'claim_listing_shortcode' ] );
		} );

		// Load claim form.
		add_action( 'template_redirect', function() {
			$page_id = mylisting_get_setting( 'claims_page_id' );
			if ( $page_id && is_page( $page_id ) ) {
				do_action( 'case27_claim_form_init' );
			}
		} );

		// render form
		add_action( 'case27_claim_form_init', [ $this, 'claim_form_init' ], 5 );
		add_action( 'case27_claim_form_output', [ $this, 'claim_form_output' ] );

		// add claims page in user dashboard
		mylisting()->woocommerce()->add_dashboard_page( [
			'endpoint' => _x( 'claim-requests', 'Claims user dashboard page slug', 'my-listing' ),
			'title' => _x( 'Claim Requests', 'Claims user dashboard page title', 'my-listing' ),
			'template' => locate_template( 'templates/dashboard/claim-requests.php' ),
			'show_in_menu' => false,
		] );
	}

	/**
	 * Register `Claim` post type.
	 *
	 * @since 1.6
	 */
	public function register_claim_post_type() {
		register_post_type( 'claim', [
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => false,
			'capability_type'    => 'page',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => [''],
			'labels'             => [
				'name'               => __( 'Claims', 'my-listing' ),
				'singular_name'      => __( 'Claim', 'my-listing' ),
				'menu_name'          => __( 'Claim Entries', 'my-listing' ),
				'name_admin_bar'     => __( 'Claims', 'my-listing' ),
				'add_new'            => __( 'Add New', 'my-listing' ),
				'add_new_item'       => __( 'Add New Claim', 'my-listing' ),
				'new_item'           => __( 'New Claim', 'my-listing' ),
				'edit_item'          => __( 'Edit Claim', 'my-listing' ),
				'view_item'          => __( 'View Claim', 'my-listing' ),
				'all_items'          => __( 'All Claims', 'my-listing' ),
				'search_items'       => __( 'Search Claims', 'my-listing' ),
				'parent_item_colon'  => __( 'Parent Claims:', 'my-listing' ),
				'not_found'          => __( 'No Claims found.', 'my-listing' ),
				'not_found_in_trash' => __( 'No Claims found in Trash.', 'my-listing' ),
			],
		] );
	}

	/**
	 * Claim Title.
	 *
	 * @since 1.6
	 */
	public function claim_title( $title, $id = null ) {
		if ( ! $id || 'claim' !== get_post_type( $id ) ) {
			return $title;
		}

		$status = static::get_claim_status( $id );
		return "#{$id} - {$status}";
	}

	/**
	 * Display `Claim Listing` as a listing quick action or cover detail.
	 *
	 * @since 2.0
	 */
	public function display_claim_quick_action( $action, $listing ) {
		$claim_url = static::get_claim_url( $listing->get_id() );
		if ( ! $listing->is_claimable() || empty( trim( $claim_url ) ) ) {
			return;
		}
		?>
		<li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
		    <a href="<?php echo esc_url( $claim_url ) ?>">
		    	<?php echo c27()->get_icon_markup( $action['icon'] ) ?>
		    	<span><?php echo $action['label'] ?></span>
		    </a>
		</li>
		<?php
	}

	/**
	 * Claim Listing Form Shortcode
	 *
	 * @since 1.6
	 */
	public function claim_listing_shortcode() {
		$listing_id = absint( ! empty( $_GET['listing_id'] ) ? $_GET['listing_id'] : null );
		$post = get_post( $listing_id );
		if ( 'job_listing' !== $post->post_type ) {
			echo wpautop( __( 'Listing invalid or cannot be claimed.', 'my-listing' ) );
		} else {
			do_action( 'case27_claim_form_output' );
		}
		return ob_get_clean();
	}

	/**
	 * Claim Form Init
	 * To setup and process the data. This is loaded only on claim page.
	 *
	 * @since 1.6
	 */
	public function claim_form_init() {
		// Make sure registration enabled and account required in claim page.
		add_filter( 'mylisting/settings/submission_requires_account', '__return_true' );
		\MyListing\Src\Claims\Claim_Listing_Form::instance()->process();
	}

	/**
	 * Load Claim Form
	 *
	 * @since 1.6
	 */
	public function claim_form_output() {
		\MyListing\Src\Claims\Claim_Listing_Form::instance()->render();
	}

	/**
	 * Retrieve the `Claim Listing` url for given listing id.
	 *
	 * @since 2.1
	 */
	public static function get_claim_url( $listing_id ) {
		$listing = \MyListing\Src\Listing::get( $listing_id );
		$page_id = mylisting_get_setting( 'claims_page_id' );
		$page_url = $page_id ? get_permalink( $page_id ) : '';

		// validate
		if ( ! ( $listing && $page_url ) ) {
			return '';
		}

		return esc_url( add_query_arg( 'listing_id', $listing->get_id(), $page_url ) );
	}

	/**
	 * Get a valid post type status for Claims post type.
	 *
	 * @since 2.1
	 */
	public static function get_claim_status( $claim_id ) {
		$statuses = static::get_valid_statuses();
		$status = get_post_meta( $claim_id, '_status', true );
		return $status && isset( $statuses[ $status ] ) ? $statuses[ $status ] : $statuses['pending'];
	}

	/**
	 * Get listing of valid post stauses for Claims post type.
	 *
	 * @since 2.1
	 */
	public static function get_valid_statuses() {
		return [
			'pending'  => esc_html__( 'Pending', 'my-listing' ),
			'approved' => esc_html__( 'Approved', 'my-listing' ),
			'declined' => esc_html__( 'Declined', 'my-listing' ),
		];
	}

	/**
	 * Create a new claim.
	 *
	 * @since 2.1
	 */
	public static function create( $args = [] ) {
		$args = wp_parse_args( $args, [
			'listing_id'       => false,
			'user_id'          => get_current_user_id(),
			'user_package_id'  => false,
			'status'           => mylisting_get_setting( 'claims_require_approval' ) ? 'pending' : 'approved',
		] );

		// validate
		if ( empty( $args['listing_id'] ) || empty( $args['user_id'] ) ) {
			return false;
		}

		// check if claim already exists for this user
		$existing_claim = static::get_user_claim( $args['user_id'], $args['listing_id'] );
		if ( $existing_claim !== false ) {
			return $existing_claim;
		}

		// create new claim
		$claim_id = wp_insert_post( [
			'post_author'  => 0,
			'post_title'   => '',
			'post_type'    => 'claim',
			'post_status'  => 'publish',
		] );

		// validate
		if ( ! $claim_id || is_wp_error( $claim_id ) ) {
			return false;
		}

		// success, set claim metadata
		update_post_meta( $claim_id, '_status', $args['status'] );
		update_post_meta( $claim_id, '_listing_id', absint( $args['listing_id'] ) );
		update_post_meta( $claim_id, '_user_id', absint( $args['user_id'] ) );
		update_post_meta( $claim_id, '_user_package_id', absint( $args['user_package_id'] ) );

		// send claim status email
		if ( 'approved' === $args['status'] ) {
			\MyListing\Src\Claims\Claims::approve( $claim_id );
		}

		do_action( 'mylisting/claim:submitted', $claim_id );

		return $claim_id;
	}

	/**
	 * Approve a claim.
	 *
	 * @since 1.6
	 */
	public static function approve( $claim_id ) {
		$claim = get_post( $claim_id );
		if ( ! $claim || 'claim' !== $claim->post_type || ! $claim->_listing_id ) {
			return false;
		}

		$package = \MyListing\Src\Package::get( $claim->_user_package_id );

		// apply user package, and set listing to approved/publish
		if ( $package ) {
			wp_update_post( [
				'ID' => $claim->_listing_id,
				'post_status' => 'publish',
			] );

			$package->assign_to_listing( $claim->_listing_id );
		}

		// update verified status
		if ( mylisting_get_setting( 'mylisting_claims_mark_verified' ) ) {
			update_post_meta( absint( $claim->_listing_id ), '_claimed', 1 );
		}

		// update listing author
		if ( $claim->_user_id ) {
			wp_update_post( [
				'ID'          => absint( $claim->_listing_id ),
				'post_author' => absint( $claim->_user_id ),
			] );
		}
	}

	/**
	 * Get the user claim ID for given listing.
	 *
	 * @since 2.1
	 */
	public static function get_user_claim( $user_id, $listing_id ) {
		$claim = get_posts( [
			'post_type' => 'claim',
			'posts_per_page' => 1,
			'fields' => 'ids',
			'meta_query' => [
				'relation' => 'AND',
				[ 'key' => '_user_id', 'value' => absint( $user_id ) ],
				[ 'key' => '_listing_id', 'value' => absint( $listing_id ) ],
			],
		] );

		if ( ! empty( $claim ) ) {
			return absint( reset( $claim ) );
		}

		return false;
	}
}
