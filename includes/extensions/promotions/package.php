<?php
/**
 * Promotion Packages (Custom Post Type).
 *
 * @since 1.7.0
 */

namespace MyListing\Ext\Promotions;

class Package {

	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {

		// Register user package post type.
		add_action( 'init', [ $this, 'register_post_type' ] );

		// Add this menu to Users.
		add_action( 'admin_menu',  array( $this, 'set_menu_location' ), 52 );

		// Register custom post statuses.
		add_action( 'init', [ $this, 'register_post_statuses' ] );
		foreach ( ['post', 'post-new', 'edit'] as $hook ) {
			add_action( "admin_footer-{$hook}.php", [ $this, 'display_custom_post_statuses' ] );
		}

		// Add title.
		add_filter( 'the_title', [ $this, 'promotion_package_title' ], 10, 2 );
		add_action( 'edit_form_after_title', [ $this, 'display_package_id_edit_screen' ] );

		// Admin columns.
		add_filter( 'manage_cts_promo_package_posts_columns',  [ $this, 'promotion_posts_columns' ] );
		add_action( 'manage_cts_promo_package_posts_custom_column',  [ $this, 'promotions_custom_column' ], 5, 2 );
		add_filter( 'post_row_actions', [ $this, 'remove_promotion_quick_edit' ], 10, 2 );
		add_filter( 'bulk_actions-edit-cts_promo_package', [ $this, 'remove_promotion_bulk_action_edit' ] );

		// Delete packages with user.
		add_action( 'deleted_user', [ $this, 'delete_promotions_with_user' ], 10, 2 );

		// Save post action.
		add_action( 'save_post', [ $this, 'save_package' ], 99, 2 );

		// Check for and trash expired packages.
		add_action( 'mylisting/schedule:twicedaily', [ $this, 'handle_expired_packages' ], 30 );
	}

	/**
	 * Register Post Type for Promotion Packages.
	 *
	 * @since 1.7.0
	 * @link  https://codex.wordpress.org/Function_Reference/register_post_type
	 */
	public function register_post_type() {
		$args = [
			'description'         => '',
			'public'              => false,
			'publicly_queryable'  => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'exclude_from_search' => true, // Need this for WP_Query.
			'show_ui'             => true,
			'show_in_menu'        => false,
			'menu_position'       => 3,
			'menu_icon'           => 'dashicons-screenoptions',
			'can_export'          => true,
			'delete_with_user'    => false,
			'hierarchical'        => false,
			'has_archive'         => false,
			'query_var'           => true,
			'rewrite'             => false,
			'capability_type'     => 'page',
			'supports'            => [''],
			'labels'              => [
				'name'               => __( 'Promotion Packages', 'my-listing' ),
				'singular_name'      => __( 'Promotion Package', 'my-listing' ),
				'add_new'            => __( 'Promote a Listing', 'my-listing' ),
				'add_new_item'       => __( 'Add New Package', 'my-listing' ),
				'edit_item'          => __( 'Edit Package', 'my-listing' ),
				'new_item'           => __( 'New Package', 'my-listing' ),
				'all_items'          => __( 'All Packages', 'my-listing' ),
				'view_item'          => __( 'View Package', 'my-listing' ),
				'search_items'       => __( 'Search Packages', 'my-listing' ),
				'not_found'          => __( 'Not Found', 'my-listing' ),
				'not_found_in_trash' => __( 'Not Found in Trash', 'my-listing' ),
				'menu_name'          => __( 'Promotion Packages', 'my-listing' ),
			],
		];

		register_post_type( 'cts_promo_package', apply_filters( 'mylisting/promotions/package/register_post_type', $args ) );
	}

	/**
	 * Add Listing Packages as Listings Submenu.
	 *
	 * @since 1.7.0
	 * @link https://shellcreeper.com/how-to-add-wordpress-cpt-admin-menu-as-sub-menu/
	 */
	public function set_menu_location() {
		$cpt_obj = get_post_type_object( 'cts_promo_package' );
		add_submenu_page(
			'users.php',                              // Parent slug.
			$cpt_obj->labels->name,                   // Page title.
			$cpt_obj->labels->menu_name,              // Menu title.
			$cpt_obj->cap->edit_posts,                // Capability.
			'edit.php?post_type=cts_promo_package'    // Menu slug.
		);
	}

	/**
	 * Register Promotion Package Statuses
	 *
	 * @since 1.7.0
	 */
	public function register_post_statuses() {
		register_post_status( 'promotion_cancelled', [
			'label'                     => esc_html__( 'Cancelled', 'my-listing' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			// translators: %s is label count.
			'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'my-listing' ),
		] );
	}

	/**
	 * Get possible post statuses for promotion packages.
	 *
	 * @since 1.7.0
	 * @return array
	 */
	public function get_statuses() {
		return [
			'publish'             => esc_html__( 'Active', 'my-listing' ),
			'draft'               => esc_html__( 'Inactive', 'my-listing' ),
			'trash'               => esc_html__( 'Expired', 'my-listing' ), // Fully Used.
			'promotion_cancelled' => esc_html__( 'Cancelled', 'my-listing' ),
		];
	}

	/**
	 * Get proper package status.
	 *
	 * @since  1.7.0
	 *
	 * @param  int|WP_Post $post_id Post ID or WP Post Object.
	 * @return string|false
	 */
	function get_proper_status( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'cts_promo_package' !== $post->post_type ) {
			return false;
		}

		// Get post status.
		$status = $post->post_status;
		if ( 'trash' === $status ) {
			return $status;
		}

		// Check order.
		if ( $post->_order_id && ( $order = wc_get_order( $post->_order_id ) ) ) {
			if ( $order->get_status() === 'cancelled' ) {
				return 'promotion_cancelled';
			} elseif ( 'promotion_cancelled' === $post->post_status ) {
				$status = 'publish';
			}
		}

		// Check if listing has expired.
		if ( ( $expiry = get_post_meta( $post->ID, '_expires', true ) ) && ( $expiry_time = strtotime( $expiry, current_time( 'timestamp' ) ) ) ) {
			if ( $expiry_time < current_time( 'timestamp' ) ) {
				$status = 'trash';
			} elseif ( $post->post_status === 'trash' ) {
				$status = 'publish';
			}
		}

		return $status;
	}

	/**
	 * Adds post status to the "submitdiv" Meta Box and post type WP List Table screens.
	 * Based on code by franz-josef-kaiser
	 *
	 * @since 1.7.0
	 * @link  https://gist.github.com/franz-josef-kaiser/2930190
	 */
	public function display_custom_post_statuses() {
		global $post, $post_type;

		// Abort if we're on the wrong post type, but only if we got a restriction
		if ( 'cts_promo_package' !== $post_type ) {
			return;
		}

		$statuses = $this->get_statuses();

		// Get all non-builtin post status and add them as <option>
		$options = $display = '';
		if ( $post instanceof \WP_Post ) {
			foreach ( $statuses as $status => $name ) {
				$selected = selected( $post->post_status, $status, false );

				// If one of our custom post statuses is selected, remember it.
				$selected AND $display = $name;

				// Build the options
				$options .= "<option{$selected} value='{$status}'>{$name}</option>";
			}
		}
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function($) {
				<?php if ( ! empty( $display ) ) : ?>
					jQuery( '#post-status-display' ).html( '<?php echo $display; ?>' );
				<?php endif; ?>

				var select = jQuery( '#post-status-select' ).find( 'select' );
				jQuery( select ).html( "<?php echo $options; ?>" );

				if ( $('body.post-type-cts_promo_package .subsubsub .trash a').length ) {
					var counter = $('body.post-type-cts_promo_package .subsubsub .trash a span').detach();
					$('body.post-type-cts_promo_package .subsubsub .trash a').html(
						'<?php echo esc_attr( _x( 'Expired', 'Admin view promotions - Expired Packages', 'my-listing' ) ) ?> '
					).append( counter );
				}
			} );
		</script>
		<?php
	}

	/**
	 * Genearate a promotion package title.
	 *
	 * @since 1.7.0
	 *
	 * @param  string $title The title string.
	 * @param  int    $id    Post ID.
	 * @return string
	 */
	public function promotion_package_title( $title, $id = null ) {
		if ( ! $id || 'cts_promo_package' !== get_post_type( $id ) ) {
			return $title;
		}

		$title = sprintf( '#%s', $id );
		$listing_id = get_post_meta( $id, '_listing_id', true );

		// Append listing name to promotion package title.
		if ( absint( $listing_id ) && ( $listing_title = get_the_title( $listing_id ) ) ) {
			$title = sprintf( '#%s &mdash; %s', $id, $listing_title );
		}

		return $title;
	}

	/**
	 * Display Package ID in Edit Screen
	 *
	 * @since 1.7.0
	 */
	public function display_package_id_edit_screen( $post ) {
		if ( $post && $post->ID && 'cts_promo_package' === $post->post_type && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) { ?>
			<h1 class="wp-heading-inline-package">
				<?php printf( __( 'Edit Package #%d', 'my-listing' ), $post->ID ); ?>
				<a href="<?php echo esc_url( add_query_arg( 'post_type','cts_promo_package', admin_url( 'post-new.php' ) ) ); ?>" class="page-title-action">
					<?php esc_html_e( 'Add New', 'my-listing' ); ?>
				</a>
			</h1>
			<style>.wrap h1.wp-heading-inline {display:none;} .wrap > .page-title-action {display:none;} #poststuff {margin-top: 30px;}</style>
		<?php }
	}

	/**
	 * Package columns.
	 *
	 * @since  1.7.0
	 *
	 * @param  array $columns Post Columns.
	 * @return array
	 */
	public function promotion_posts_columns( $columns ) {
		unset( $columns['date'] );
		$columns['title']    = esc_html__( 'Package ID', 'my-listing' );
		$columns['user']     = esc_html__( 'User', 'my-listing' );
		$columns['duration'] = esc_html__( 'Promoted Until', 'my-listing' );
		$columns['product']  = esc_html__( 'Product', 'my-listing' );
		$columns['order']    = esc_html__( 'Order ID', 'my-listing' );

		return $columns;
	}

	/**
	 * Cutom package columns.
	 *
	 * @since 1.7.0
	 *
	 * @param string $column  Column ID.
	 * @param int    $post_id Post ID.
	 */
	public function promotions_custom_column(  $column, $post_id  ) {
		switch ( $column ) {
			case 'user':
				$title = esc_html__( 'n/a', 'my-listing' );
				$user_id = absint( get_post_meta( $post_id, '_user_id', true ) );
				if ( $user_id ) {
					$user = get_userdata( $user_id );
					if ( $user ) {
						$title = '<a target="_blank" href="' . esc_url( get_edit_user_link( $user_id ) ) . '">';
						$title .= $user->user_login;
						$title .= '</a>';
					}
				}

				echo $title;
			break;

			case 'duration':
				$expires = get_post_meta( $post_id, '_expires', true );
				$expiry_time = strtotime( $expires, current_time( 'timestamp' ) );
				echo $expiry_time ? date_i18n( 'F j, Y g:i a', $expiry_time ) : '&ndash;';
			break;

			case 'product':
				$link = esc_html__( 'n/a', 'my-listing' );
				$product_id = get_post_meta( $post_id, '_product_id', true );
				if ( $product_id ) {
					$product = wc_get_product( $product_id );
					if ( $product ) {
						$link = '<a target="_blank" href="' . esc_url( get_edit_post_link( $product_id ) ) . '">' . $product->get_name() . '</a>';
					}
				}
				echo $link;
			break;

			case 'order':
				$link = esc_html__( 'n/a', 'my-listing' );
				$order_id = absint( get_post_meta( $post_id, '_order_id', true ) );
				if ( $order_id ) {
					$link = '<a target="_blank" href="' . esc_url( get_edit_post_link( $order_id ) ) . '">#' . $order_id . '</a>';
				}
				echo $link;
			break;
		}
	}

	/**
	 * Remove quick edit link
	 *
	 * @since  1.7.0
	 *
	 * @param  array   $actions Row Actions.
	 * @param  WP_Post $post    Post Object.
	 * @return array
	 */
	public function remove_promotion_quick_edit( $actions, $post ) {
		if ( 'cts_promo_package' === $post->post_type ) {
			$actions['edit'] = sprintf( '<a href="%s">%s</a>', get_edit_post_link( $post ), _x( 'Edit Package', 'Promotions list in wp-admin', 'my-listing' ) );

			if ( $listing_id = absint( $post->_listing_id ) ) {
				$actions['inline hide-if-no-js'] = sprintf( '<a href="%s">%s</a>', get_edit_post_link( $listing_id ), _x( 'Edit Listing', 'Promotions list in wp-admin', 'my-listing' ) );
			} else {
				unset( $actions['inline hide-if-no-js'] );
			}
		}
		return $actions;
	}

	/**
	 * Remove User Packages Edit Bulk Actions
	 *
	 * @since  1.7.0
	 *
	 * @param  array $actions Actions list.
	 * @return array
	 */
	public function remove_promotion_bulk_action_edit( $actions ) {
		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * Check whether the given listing is promoted, and
	 * return the resulting promotion package object if found.
	 *
	 * @since  1.7.0
	 *
	 * @param  int                $listing_id    ID of listing to check.
	 * @param  string             $return_format Whether to return the package ID or package post object.
	 * @return int|\WP_Post|false $package       Package id/object if found, otherwise false.
	 */
	public function get_listing_package( $listing_id, $return_format = 'ids' ) {
		if ( ! ( $listing = \MyListing\Src\Listing::get( $listing_id ) ) ) {
			return false;
		}

		if ( ! ( $package_id = $listing->get_data( '_promo_package_id' ) ) ) {
			return false;
		}

		$package = get_posts( [
			'post_type'        => 'cts_promo_package',
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'post__in'         => [ absint( $package_id ) ],
			'fields'           => $return_format,
			'meta_query'       => [[
				'key'   => '_listing_id',
				'value' => $listing_id,
			]]
		] );

		return ! empty( $package ) ? reset( $package ) : false;
	}

	/**
	 * Delete promotions when user is deleted.
	 *
	 * @since 1.7.0
	 *
	 * @param int      $id       ID of the deleted user.
	 * @param int|null $reassign ID of the user to reassign posts and links to.
	 */
	public function delete_promotions_with_user( $id, $reassign ) {
		// Get packages.
		$promotions = get_posts( [
			'post_type'        => 'cts_promo_package',
			'post_status'      => 'any',
			'posts_per_page'   => -1,
			'post__in'         => [],
			'order'            => 'asc',
			'orderby'          => 'post__in',
			'suppress_filters' => false,
			'fields'           => 'ids',
			'meta_query' => [
				'relation' => 'AND',
				[
					'key'     => '_user_id',
					'value'   => $id,
					'compare' => 'IN',
				],
			],
		] );

		// Delete packages.
		$deleted = [];
		foreach ( $promotions as $package_id ) {
			$post = wp_delete_post( $package_id, false ); // Move to trash.
			if ( $post ) {
				$deleted[ $package_id ] = $post;
			}
		}

		return $deleted;
	}

	/**
	 * Get promotion packages.
	 *
	 * @since 1.7.0
	 * @param array $args \WP_Query args list.
	 */
	public function get_packages( $args = [] ) {
		$args = c27()->merge_options( [
			'post_type'        => 'cts_promo_package',
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'suppress_filters' => false,
			'meta_query'       => [
				'relation'       => 'AND',
				'user_query'     => [
					'key'          => '_user_id',
					'value'        => get_current_user_id(),
				],
			],
		], $args );

		return get_posts( $args );
	}

	/**
	 * Save package action.
	 *
	 * @param int      $post_id Package ID.
	 * @param \WP_Post $post    Post Object
	 */
	public function save_package( $post_id, $post = null ) {
		if ( ! $post || 'cts_promo_package' !== $post->post_type || defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		// Current listing expiry date.
		$expiry_date = get_post_meta( $post_id, '_expires', true );

		// Get proper post status.
		$status = $this->get_proper_status( $post );
		$original_status = ! empty( $_POST['original_post_status'] ) ? $_POST['original_post_status'] : false;
		$action = ! empty( $_GET['action'] ) ? $_GET['action'] : false;

		// Update post status.
		if ( $status && $original_status !== $status ) {
			remove_action( 'save_post', [ $this, __FUNCTION__ ], 99, 2 );

			// Update meta data on status change.
			if ( $status === 'trash' && $action !== 'untrash' ) {
				mylisting()
					->promotions()
					->expire_package( $post->ID );
			} elseif ( $status === 'publish' || $action === 'untrash' ) {
				mylisting()
					->promotions()
					->activate_package( $post->ID );

				// Since activate_package() will calculate expiry date based on package duration,
				// Revert to the expiry date set by the user, to make it possible to have custom dates.
				if ( $expiry_date && strtotime( $expiry_date, current_time( 'timestamp' ) ) ) {
					update_post_meta( $post->ID, '_expires', $expiry_date );
				}
			} else {
				wp_update_post( [
					'ID'          => $post_id,
					'post_status' => $status,
				] );
			}

			add_action( 'save_post', [ $this, __FUNCTION__ ], 99, 2 );
		}
	}

	/**
	 * Get the permalink to the front-end package edit page.
	 * If $package is null, return promotions endpoint url.
	 *
	 * @since 1.7.0
	 * @param int $package Promotion package ID
	 */
	public function get_edit_link( $package = null ) {
		$base_url = wc_get_account_endpoint_url( 'promotions' );

		if ( ! $package ) {
			return $base_url;
		}

		return add_query_arg( 'package', absint( $package ), $base_url );
	}

	/**
	 * Check for, and trash expired promotion packages.
	 *
	 * @since 1.7.0
	 */
	public function handle_expired_packages() {
		global $wpdb;

		// Get package ids.
		$package_ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
			LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
			WHERE postmeta.meta_key = '_expires'
			AND postmeta.meta_value > 0
			AND postmeta.meta_value < %s
			AND posts.post_status = 'publish'
			AND posts.post_type = 'cts_promo_package'
		", date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) ) );

		// Expire found packages.
		foreach ( (array) $package_ids as $package_id ) {
			mylisting()
				->promotions()
				->expire_package( $package_id );
		}
	}

}
