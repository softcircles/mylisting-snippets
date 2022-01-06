<?php

namespace MyListing\Src\Admin;

if ( ! defined('ABSPATH') ) {
	exit;
}

class View_Listings_Screen {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		// columns
		add_filter( 'manage_edit-job_listing_columns', [ $this, 'columns' ] );
		add_action( 'manage_job_listing_posts_custom_column', [ $this, 'column_contents' ], 2 );
		add_filter( 'list_table_primary_column', [ $this, 'primary_column' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'post_row_actions' ], 10, 2 );
		add_action( 'view_mode_post_types', [ $this, 'disable_view_mode' ] );

		// sortable columns
		add_filter( 'manage_edit-job_listing_sortable_columns', [ $this, 'sortable_columns' ] );
		add_filter( 'request', [ $this, 'sort_columns' ] );

		// search listings
		add_action( 'request', [ $this, 'add_keyword_search' ] );
		add_action( 'restrict_manage_posts', [ $this, 'add_filters' ] );

		// messages
		add_filter( 'post_updated_messages', [ $this, 'post_updated_messages' ] );

		// bulk actions
		add_action( 'bulk_actions-edit-job_listing', [ $this, 'add_bulk_actions' ] );
		add_action( 'handle_bulk_actions-edit-job_listing', [ $this, 'handle_bulk_actions' ], 100, 3 );

		// filter listings by listing type
		add_filter( 'parse_query', [ $this, 'filter_listings_by_type' ] );

		// filter listings by user package
		add_filter( 'parse_query', [ $this, 'filter_listings_by_user_package' ] );
	}

	/**
	 * Add custom columns to the Listings table.
	 *
	 * @since 2.1
	 */
	public function columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			$columns = [];
		}

		unset( $columns['title'], $columns['date'], $columns['author'] );

		return [
			'cb' => $columns['cb'],
			'job_position' => _x( 'Name', 'WP Admin > All Listings Table', 'my-listing' ),
			'job_location' => '<span class="dashicons dashicons-location"></span> ' . _x( 'Location', 'WP Admin > All Listings Table', 'my-listing' ),
			'taxonomy-job_listing_category' => '<span class="dashicons dashicons-paperclip"></span> ' . _x( 'Categories', 'WP Admin > All Listings Table', 'my-listing' ),
			'taxonomy-region' => '<span class="dashicons dashicons-tag"></span> ' . _x( 'Region', 'WP Admin > All Listings Table', 'my-listing' ),
			'taxonomy-case27_job_listing_tags' => '<span class="dashicons dashicons-tag"></span> ' . _x( 'Tags', 'WP Admin > All Listings Table', 'my-listing' ),
			'comments' => '<span class="dashicons dashicons-admin-comments"></span> ' . _x( 'Reviews', 'WP Admin > All Listings Table', 'my-listing' ),
			'priority' => _x( 'Priority', 'WP Admin > All Listings Table', 'my-listing' ),
			'listing_status' => _x( 'Status', 'WP Admin > All Listings Table', 'my-listing' ),
			'posted_on' => _x( 'Posted', 'WP Admin > All Listings Table', 'my-listing' ),
			'modified_date' => _x( 'Modified Date', 'WP Admin > All Listings Table', 'my-listing' ),
			'job_expires' => '<span class="dashicons dashicons-clock"></span> ' . _x( 'Expires', 'WP Admin > All Listings Table', 'my-listing' ),
			'job_actions' => _x( 'Actions', 'WP Admin > All Listings Table', 'my-listing' ),
		];

		return $columns;
	}

	/**
	 * Set the content for custom columns.
	 *
	 * @since 2.1
	 */
	public function column_contents( $column ) {
		global $post;
		if ( ! ( $listing = \MyListing\Src\Listing::get( $post ) ) ) {
			return;
		}

		if ( $template = locate_template( sprintf( 'templates/admin/view-listings/column-%s.php', $column ) ) ) {
			require $template;
		}
	}

	/**
	 * Set primary column.
	 *
	 * @since 2.1
	 */
	public function primary_column( $column, $screen ) {
		if ( $screen === 'edit-job_listing' ) {
			$column = 'job_position';
		}

		return $column;
	}

	/**
	 * Remove post actions added by default in the primary column.
	 *
	 * @since 2.1
	 */
	public function post_row_actions( $actions, $post ) {
		if ( $post->post_type === 'job_listing' ) {
			$actions = [];
		}

		return $actions;
	}

	/**
	 * Add custom sortable columns.
	 *
	 * @since 2.1
	 */
	public function sortable_columns( $columns ) {
		$columns['job_position'] = 'title';
		$columns['job_location'] = 'location';
		$columns['job_expires'] = 'expiry_date';
		$columns['priority'] = 'priority';
		$columns['posted_on'] = 'date';

		return $columns;
	}

	/**
	 * Handle sort query for custom sortable columns.
	 *
	 * @since 2.1
	 */
	public function sort_columns( $vars ) {
		$screen = get_current_screen();
		if ( ! ( $screen && $screen->id === 'edit-job_listing' ) || empty( $vars['orderby'] ) ) {
			return $vars;
		}

		if ( $vars['orderby'] === 'expiry_date' ) {
			$vars = array_merge( $vars, [
				'meta_key' => '_job_expires',
				'orderby'  => 'meta_value',
			] );
		}

		if ( $vars['orderby'] === 'location' ) {
			$vars = array_merge( $vars, [
				'meta_key' => '_job_location',
				'orderby'  => 'meta_value',
			] );
		}

		if ( $vars['orderby'] === 'priority' ) {
			$vars = array_merge( $vars, [
				'meta_key' => '_featured',
				'orderby'  => 'meta_value',
			] );
		}

		return $vars;
	}

	/**
	 * Filter listings by keyword.
	 *
	 * @since 2.1
	 */
	public function add_keyword_search( $vars ) {
		$screen = get_current_screen();
		if ( ! ( $screen && $screen->id === 'edit-job_listing' ) || empty( $vars['s'] ) ) {
			return $vars;
		}

		// add the keyword search filter
		$GLOBALS['mylisting_search_keywords'] = sanitize_text_field( $vars['s'] );
		add_filter( 'posts_search', [ \MyListing\Src\Queries\Explore_Listings::instance(), 'keyword_search' ], 42 );

		return $vars;
	}

	/**
	 * Set custom save messages for listings.
	 *
	 * @since 2.1
	 */
	public function post_updated_messages( $messages ) {
		global $post;

		$revision = isset( $_GET['revision'] ) ? wp_post_revision_title( (int) $_GET['revision'], false ) : false;
		$view_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( get_permalink( $post->ID ) ),
			_x( 'View Listing', 'WP Admin > Listing notices', 'my-listing' )
		);
		$preview_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ),
			_x( 'Preview Listing', 'WP Admin > Listing notices', 'my-listing' )
		);

		$messages['job_listing'] = array(
			1 => _x( 'Listing updated.', 'WP Admin > Listing notices', 'my-listing' ) . $view_link,
			2 => $messages['post'][2],
			3 => $messages['post'][3],
			4 => _x( 'Listing updated.', 'WP Admin > Listing notices', 'my-listing' ),
			5 => $revision ? sprintf( _x( 'Listing restored to revision from %s', 'WP Admin > Listing notices', 'my-listing' ), $revision ) : false,
			6 => _x( 'Listing published.', 'WP Admin > Listing notices', 'my-listing' ) . $view_link,
			7 => _x( 'Listing saved.', 'WP Admin > Listing notices', 'my-listing' ),
			8 => _x( 'Listing submitted.', 'WP Admin > Listing notices', 'my-listing' ) . $preview_link,
			9 => sprintf(
				// translators: %1$s is the date the listing will be published; %2$s is the URL to preview the listing.
				_x( 'Listing scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview</a>', 'WP Admin > Listing notices', 'my-listing' ),
				date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $post->post_date ) ),
				esc_url( get_permalink( $post->ID ) )
			),
			10 => sprintf(
				_x( 'Listing draft updated. <a target="_blank" href="%1$s">Preview</a>', 'WP Admin > Listing notices', 'my-listing' ),
				esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) )
			),
		);

		return $messages;
	}

	/**
	 * Disable `View Mode` setting in Screen Options for listings.
	 *
	 * @since 2.1
	 */
	public function disable_view_mode( $post_types ) {
		unset( $post_types['job_listing'] );
		return $post_types;
	}

	/**
	 * Adds custom bulk actions for listings.
	 *
	 * @since 2.1
	 */
	public function add_bulk_actions( $bulk_actions ) {
		$bulk_actions['approve_listings'] = _x( 'Approve Listings', 'Listing bulk actions', 'my-listing' );
		$bulk_actions['expire_listings'] = _x( 'Expire Listings', 'Listing bulk actions', 'my-listing' );

		return $bulk_actions;
	}

	/**
	 * Handles custom listing bulk actions.
	 *
	 * @since 2.1
	 */
	public function handle_bulk_actions( $redirect_url, $action, $post_ids ) {
		if ( ! is_array( $post_ids ) || empty( $post_ids ) || ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		// store updated post ids
		$updated = [];

		// approve listings action
		if ( $action === 'approve_listings' ) {
			foreach ( $post_ids as $post_id ) {
				$updated[] = wp_update_post( [
					'ID' => $post_id,
					'post_status' => 'publish',
				] );
			}
		}

		// expire listings action
		if ( $action === 'expire_listings' ) {
			foreach ( $post_ids as $post_id ) {
				$updated[] = wp_update_post( [
					'ID' => $post_id,
					'post_status' => 'expired',
				] );
			}
		}

		// redirect with notice
		wp_redirect( add_query_arg(
			'updated',
			count( array_filter( $updated ) ),
			remove_query_arg( 'action', $redirect_url )
		) );

		exit;
	}

	/**
	 * Adds custom listing filters in the header section of the table.
	 *
	 * @since 2.1
	 */
	public function add_filters() {
		$screen = get_current_screen();
		if ( ! ( $screen && $screen->id === 'edit-job_listing' ) ) {
			return;
		}

		// output category filter
		$selected = ! empty( $_GET['job_listing_category'] ) ? get_term_by( 'slug', $_GET['job_listing_category'], 'job_listing_category' ) : false; ?>
		<select class="custom-select" name="job_listing_category" id="job_listing_category" data-mylisting-ajax="true" data-mylisting-ajax-url="mylisting_list_terms"
			data-mylisting-ajax-params="<?php echo c27()->encode_attr( [ 'taxonomy' => 'job_listing_category', 'term-value' => 'slug' ] ) ?>"
			placeholder="<?php echo esc_attr( _x( 'Select Category', 'WP Admin > All Listings Table', 'my-listing' ) ) ?>">
			<option></option>
			<?php if ( $selected instanceof \WP_Term ): ?>
				<option value="<?php echo esc_attr( $selected->slug ) ?>" selected="selected">
					<?php echo esc_attr( $selected->name ) ?>
				</option>
			<?php endif ?>
		</select>
		<?php

		// output region filter
		$selected = ! empty( $_GET['region'] ) ? get_term_by( 'slug', $_GET['region'], 'region' ) : false; ?>
		<select class="custom-select" name="region" id="region" data-mylisting-ajax="true" data-mylisting-ajax-url="mylisting_list_terms"
			data-mylisting-ajax-params="<?php echo c27()->encode_attr( [ 'taxonomy' => 'region', 'term-value' => 'slug' ] ) ?>"
			placeholder="<?php echo esc_attr( _x( 'Select Region', 'WP Admin > All Listings Table', 'my-listing' ) ) ?>">
			<option></option>
			<?php if ( $selected instanceof \WP_Term ): ?>
				<option value="<?php echo esc_attr( $selected->slug ) ?>" selected="selected">
					<?php echo esc_attr( $selected->name ) ?>
				</option>
			<?php endif ?>
		</select>
		<?php

		// output region filter
		$selected = ! empty( $_GET['author'] ) ? get_user_by( 'id', $_GET['author'] ) : false; ?>
		<select class="custom-select" name="author" id="author_filter" data-mylisting-ajax="true" data-mylisting-ajax-url="mylisting_list_users"
			placeholder="<?php echo esc_attr( _x( 'Select Author', 'WP Admin > All Listings Table', 'my-listing' ) ) ?>">
			<option></option>
			<?php if ( $selected instanceof \WP_User ): ?>
				<option value="<?php echo esc_attr( $selected->ID ) ?>" selected="selected">
					<?php echo esc_html( $selected->display_name ) ?>
				</option>
			<?php endif ?>
		</select>
		<?php
	}

	/**
	 * Filter listings by listing type in admin via URL.
	 *
	 * @since 2.0
	 */
	public function filter_listings_by_type( $query ) {
		global $typenow;
		if ( $typenow !== 'job_listing' || empty( $_GET['filter_by_type'] ) || ! is_admin() ) {
			return $query;
		}

		if ( ! ( $type = \MyListing\Src\Listing_Type::get_by_name( $_GET['filter_by_type'] ) ) ) {
			return $query;
		}

		$query->query_vars['meta_key']   = '_case27_listing_type';
		$query->query_vars['meta_value'] = $type->get_slug();

		// Display admin notice to inform user that they are viewing filtered listings.
		add_action( 'admin_notices', function() use ($type) {
			// Display this notice only once.
			global $_case27_filter_listings_by_type;
			if ( isset( $_case27_filter_listings_by_type ) ) {
				return;
			}
			$_case27_filter_listings_by_type = 1;

			$back_url = add_query_arg( [
				'post_type'        => 'job_listing',
			], admin_url( 'edit.php' ) );
			?>
			<div class="notice notice-info">
				<p>
					<?php printf( _x( 'Showing all %s.', 'WP Admin > Listings > Filter by type', 'my-listing' ), $type->get_plural_name() ) ?>
					<?php printf( '<a href="%s">%s</a>', esc_url( $back_url ), _x( 'Go back.', 'WP Admin > Listings > Filter by type', 'my-listing' ) ) ?>
				</p>
			</div>
			<?php
		} );

		return $query;
	}


	/**
	 * Filter Listing By User Package in Admin via URL.
	 *
	 * @since 1.6
	 */
	public function filter_listings_by_user_package( $query ) {
		global $typenow;
		if ( ! ( $typenow === 'job_listing' && ! empty( $_GET['_user_package_id'] ) && is_admin() ) ) {
			return $query;
		}

		$query->query_vars['meta_key'] = '_user_package_id';
		$query->query_vars['meta_value'] = absint( $_GET['_user_package_id'] );

		// Display admin notice to inform user that they are viewing filtered listings.
		add_action( 'admin_notices', function() {
			// Display this notice only once.
			global $_case27_filter_listings_by_user_package;
			if ( isset( $_case27_filter_listings_by_user_package ) ) {
				return;
			}
			$_case27_filter_listings_by_user_package = 1;
			?>
			<div class="notice notice-info">
				<p><?php printf( __( 'You are viewing Listings using Package %s', 'my-listing' ), '<a href="' . esc_url( get_edit_post_link( $_GET['_user_package_id'] ) ) . '">#' . absint( $_GET['_user_package_id'] ) . '</a>' ); ?></p>
			</div>
			<?php
		} );

		return $query;
	}

}
