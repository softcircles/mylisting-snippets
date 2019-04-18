<?php
/**
 * Paid Listings Functions
 *
 * @since 1.0.0
 */

/**
 * Get Paid Listing Products
 *
 * @since 1.0.0
 *
 * @param array $args Query Args.
 * @return array
 */
function case27_paid_listing_get_products( $args = [] ) {
	$terms = [ 'job_package' ];
	if ( class_exists( '\WC_Subscriptions' ) ) {
		$terms[] = 'job_package_subscription';
	}
	$defaults = [
		'post_type'        => 'product',
		'posts_per_page'   => -1,
		'post__in'         => [],
		'order'            => 'asc',
		'orderby'          => 'post__in',
		'suppress_filters' => false,
		'fields'           => 'ids',
		'product_objects'  => false,
		'tax_query'        => [
			'relation' => 'AND',
			[
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => $terms,
				'operator' => 'IN',
			],
		],
	];

	$args = wp_parse_args( $args, $defaults );

	$items = get_posts( $args );
	if ( $args['product_objects'] !== true || $args['fields'] !== 'ids' ) {
		return $items;
	}

	// Get WC Products.
	$products = [];
	foreach ( (array) $items as $product_id ) {
		$products[ $product_id ] = wc_get_product( $product_id );
	}

	return $products;
}

/**
 * Get products selected in a listing type.
 *
 * @since 1.0.0
 *
 * @param string $listing_type Listing type slug.
 * @return array
 */
function case27_paid_listing_get_listing_type_products( $listing_type ) {
	if ( ! $listing_type ) {
		return array();
	}

	$listing_type = get_page_by_path( $listing_type, OBJECT, 'case27_listing_type' );
	if ( ! $listing_type ) {
		return array();
	}

	$type = new \MyListing\Ext\Listing_Types\Listing_Type( $listing_type );
	$product_ids = array_column( $type->get_packages(), 'package' );
	if ( $product_ids && is_array( $product_ids ) ) {
		return $product_ids;
	}

	return array();
}

/**
 * Get User Packages
 *
 * @since 1.0.0
 *
 * @param array $args Get packages args.
 * @return array
 */
function case27_paid_listing_get_user_packages( $args = array() ) {
	$defaults = array(
		'post_type'        => 'case27_user_package',
		'post_status'      => [ 'publish', 'case27_full' ],
		'posts_per_page'   => -1,
		'post__in'         => array(),
		'order'            => 'asc',
		'orderby'          => 'post__in',
		'suppress_filters' => false,
		'fields'           => 'ids',
	);
	$args = wp_parse_args( $args, $defaults );

	return get_posts( $args );
}

/**
 * Get User Package Object From ID
 *
 * @since 1.0.0
 *
 * @param int $package_id User Package Post ID.
 * @return \MyListing\Ext\Paid_Listings\Package
 */
function case27_paid_listing_get_package( $package_id ) {
	return new \MyListing\Ext\Paid_Listings\Package( $package_id );
}

/**
 * Get User Package Post Statuses.
 *
 * @since 1.0.0
 *
 * @return array
 */
function case27_paid_listing_get_statuses() {
	$statuses = array(
		'publish'          => esc_html__( 'Active', 'my-listing' ),
		'draft'            => esc_html__( 'Inactive', 'my-listing' ),
		'case27_full'      => esc_html__( 'Full', 'my-listing' ), // Fully Used.
		'case27_cancelled' => esc_html__( 'Order Cancelled', 'my-listing' ),
	);
	return $statuses;
}

/**
 * Get Proper Post Status
 * This will get post status based on limit/count and order status.
 *
 * @since 1.0.0
 *
 * @param int|WP_Post $post_id Post ID or WP Post Object.
 * @return string|false
 */
function case27_paid_listing_get_proper_status( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'case27_user_package' !== $post->post_type ) {
		return false;
	}

	// Get post status.
	$status = $post->post_status;
	if ( 'trash' === $status ) {
		return $status;
	}

	// Set to full/active.
	if ( $post->_limit ) {
		if ( absint( $post->_count ) >= absint( $post->_limit ) ) {
			$status = 'case27_full';
		} elseif ( 'case27_full' === $status ) {
			$status = 'publish';
		}
	}

	// Always set to active for unlimited package.
	if ( ! $post->_limit && 'case27_full' === $post->post_status ) {
		$status = 'publish';
	}

	// Check order.
	if ( $post->_order_id ) {
		$order = wc_get_order( $post->_order_id );
		if ( $order ) {
			if ( 'cancelled' === $order->get_status() ) {
				$status = 'case27_cancelled';
			} elseif ( 'case27_cancelled' === $post->post_status ) {
				$status = 'publish';
			}
		}
	}

	return $status;
}

/**
 * Add user package.
 *
 * @since 1.0.0
 *
 * @param array $args Add package args.
 * @return int|false
 */
function case27_paid_listing_add_package( $args = array() ) {
	$defaults = array(
		'user_id'           => get_current_user_id(),
		'product_id'        => false,
		'order_id'          => false,
		'featured'          => false,
		'limit'             => false,
		'count'             => false,
		'duration'          => false,
		'use_for_claims'    => false,
	);
	$args = wp_parse_args( $args, $defaults );

	$post_id = wp_insert_post( array(
		'post_type'   => 'case27_user_package',
		'post_status' => 'publish',
		'meta_input'  => array(
			'_user_id'            => $args['user_id'] ? absint( $args['user_id'] ) : '',
			'_product_id'         => $args['product_id'] ? absint( $args['product_id'] ) : '',
			'_order_id'           => $args['order_id'] ? absint( $args['order_id'] ) : '',
			'_featured'           => $args['featured'] ? 1 : '',
			'_use_for_claims'     => $args['use_for_claims'] ? 1 : '',
			'_limit'              => $args['limit'] ? absint( $args['limit'] ) : '',
			'_count'              => $args['count'] ? absint( $args['limit'] ) : '',
			'_duration'           => $args['duration'] ? absint( $args['duration'] ) : '',
		),
	) );

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		return $post_id;
	}

	return false;
}

/**
 * Delete Packages
 *
 * @since 1.0.0
 *
 * @param array $args Delete package args.
 * @return array
 */
function case27_paid_listing_delete_user_packages( $args = array() ) {
	// Get packages.
	$packages = case27_paid_listing_get_user_packages( $args );

	// Delete all packages.
	$deleted = array();
	foreach ( $packages as $package_id ) {
		$post = wp_delete_post( $package_id, false ); // Move to trash.
		if ( $post ) {
			$deleted[ $package_id ] = $post;
		}
	}

	return $deleted;
}

/**
 * Get Listings in User Package
 *
 * @since 1.0.0
 *
 * @param int $package_id User Package Post ID.
 * @param int $limit      Limit. Set -1 for all.
 * @return array Listings IDs.
 */
function case27_paid_listing_get_listings_in_package( $package_id, $limit = -1 ) {
	$args = array(
		'post_type'         => 'job_listing',
		'post_status'       => [ 'publish', 'pending' ],
		'fields'            => 'ids',
		'posts_per_page'    => $limit,
		'meta_key'          => '_user_package_id',
		'meta_value'        => $package_id,
	);
	return get_posts( $args );
}

/**
 * Set User Package to a Listing
 *
 * @since 1.0.0
 *
 * @param int  $listing_id   Listing ID.
 * @param int  $package_id   User Package ID.
 * @return bool
 */
function case27_paid_listing_set_listing_package( $listing_id, $package_id ) {
	// Delete listing data in old user packages.
	$old_user_package = get_post_meta( $listing_id, '_user_package_id', true );
	if ( $old_user_package ) {
		delete_post_meta( $package_id, '_listing_ids', $old_user_package );
	}

	// Set package in listing.
	update_post_meta( $listing_id, '_user_package_id', $package_id );

	// Set listing ID to package.
	$listings = get_post_meta( $package_id, '_listing_ids', false );
	if ( $listings && is_array( $listings ) && ! in_array( $listing_id, $listings ) ) {
		add_post_meta( $package_id, '_listing_ids', $listing_id );
	}
}

/**
 * Check if Old Database Exists.
 *
 * @since 1.0.0
 *
 * @return bool True if migration needed.
 */
function case27_paid_listing_need_migration() {
	// Filter to force enable/disable migration.
	$enable_migration = apply_filters( 'case27_paid_listing_force_enable_migration', null );
	if ( true === $enable_migration || false === $enable_migration ) {

		// If enabled. Remove migration complete option.
		if ( true === $enable_migration && get_option( 'case27_paid_listing_migration_completed' ) ) {
			delete_option( 'case27_paid_listing_migration_completed' );
		}
		return $enable_migration;
	}

	// Option. Set if migration completed.
	if ( get_option( 'case27_paid_listing_migration_completed' ) ) {
		return false;
	}

	global $wpdb;

	// Check if old database version. Bail if not needed.
	$wpcl_version = get_option( 'wcpl_db_version', 0 );
	if ( ! $wpcl_version || version_compare( get_option( 'wcpl_db_version', 0 ), '2.1.2', '<' ) ) {
		return false;
	}

	// Check if database exists.
	$table_name = "{$wpdb->prefix}wcpl_user_packages";
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
		return false;
	}

	// Loop single database to determine if migration needed.
	$packages = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wcpl_user_packages WHERE package_type = %s;", 'job_listing' ), OBJECT_K );
	if ( ! $packages || ! is_array( $packages ) ) {
		return false;
	}

	return true;
}

/**
 * Migrate All WPCL User Packages to New Database.
 *
 * @since 1.0.0
 *
 * @return array Migrated Data. New Package ID as key, Old package ID as value.
 */
function case27_paid_listing_migrate_wpcl_user_packages() {
	global $wpdb;

	// Bail if migration is not needed.
	if ( ! case27_paid_listing_need_migration() ) {
		return array();
	}

	// Check if database exists.
	$table_name = "{$wpdb->prefix}wcpl_user_packages";
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
		return array();
	}

	// Get all wpcl user packages DB that need to be migrated.
	$packages = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wcpl_user_packages WHERE package_type = %s;", 'job_listing' ), OBJECT_K );

	// Bail if no package found.
	if ( ! $packages || ! is_array( $packages ) ) {
		return array();
	}

	// Count packages before migration.
	$before = count( $packages );

	// Migrated packages.
	$migrated = array();

	// Foreach packages, migrate.
	foreach ( $packages as $package ) {

		// Check if it's already imported.
		if ( $package->id ) {
			$args = array(
				'post_type'        => 'case27_user_package',
				'posts_per_page'   => 1,
				'post_status'      => 'any',
				'suppress_filters' => false,
				'fields'           => 'ids',
				'meta_key'         => '_wpjmpl_package_id',
				'meta_value'       => $package->id,
			);
			$user_packages = get_posts( $args );

			// If found, skip.
			if ( $user_packages ) {
				continue;
			}
		}

		// Insert new user packages.
		$post_id = wp_insert_post( array(
			'post_type'   => 'case27_user_package',
			'post_status' => 'publish',
			'meta_input'  => array(
				'_user_id'           => absint( $package->user_id ),
				'_product_id'        => absint( $package->product_id ),
				'_order_id'          => absint( $package->order_id ),
				'_featured'          => $package->package_featured ? 1 : 0,
				'_duration'          => absint( $package->package_duration ),
				'_limit'             => absint( $package->package_limit ),
				'_count'             => absint( $package->package_count ),
				'_wpjmpl_package_id' => absint( $package->id ),
			),
		) );

		// Success. Delete old package.
		if ( $post_id ) {

			// Track migrated.
			$migrated[ $post_id ] = $package->id;

			// Delete old DB.
			if ( apply_filters( 'case27_paid_listing_migrate_wpcl_delete_old_db', false ) ) {
				$wpdb->delete( "{$wpdb->prefix}wcpl_user_packages", array(
					'id' => $package->id,
				) );
			}

			// Replace User Package ID in Listing to new one.
			$data = array(
				'meta_key' => '_user_package_id',
				'meta_value' => $post_id,
			);
			$where = array(
				'meta_key' => '_user_package_id',
				'meta_value' => $package->id,
			);
			$updated = $wpdb->update( $wpdb->postmeta, $data, $where );

			// Set post status.
			$status = case27_paid_listing_get_proper_status( $post_id );
			if ( $status !== 'publish' ) {
				wp_update_post( array(
					'ID'          => $post_id,
					'post_status' => $status,
				) );
			}
		}
	}

	// Migrated.
	$after = count( $migrated );

	// Set to completed only if all data migrated.
	if ( $before === $after ) {
		update_option( 'case27_paid_listing_migration_completed', 1 );
	}

	return $migrated;
}

/**
 * Increase User Package Count
 *
 * @since 1.0.0
 *
 * @param int $package_id User Package ID.
 * @return bool
 */
function case27_paid_listing_user_package_increase_count( $package_id ) {
	$count = absint( get_post_meta( $package_id, '_count', true ) );
	return update_post_meta( $package_id, '_count', absint( $count + 1 ) );
}

/**
 * Decrease User Package Count
 *
 * @since 1.0.0
 *
 * @param int $package_id User Package ID.
 * @return bool
 */
function case27_paid_listing_user_package_decrease_count( $package_id ) {
	$count = absint( get_post_meta( $package_id, '_count', true ) );
	return update_post_meta( $package_id, '_count', absint( $count - 1 ) );
}

/**
 * Use User Package to a Listing.
 *
 * @since 1.0.0
 *
 * @param int    $package_id User Package ID.
 * @param int    $listing_id Listing ID.
 * @param string $status     Listing status.
 */
function case27_paid_listing_use_user_package_to_listing( $package_id, $listing_id, $status = false ) {
	$user_package = case27_paid_listing_get_package( $package_id );

	// Give listing the package attributes
	update_post_meta( $listing_id, '_job_duration', $user_package->get_duration() );

	// Make sure any listing promotions aren't made inactive when switching plans.
	$priority = (int) get_post_meta( $listing_id, '_featured', true );
	if ( $priority <= 1 ) {
		update_post_meta( $listing_id, '_featured', $user_package->is_featured() ? 1 : 0 );
	}

	update_post_meta( $listing_id, '_package_id', $user_package->get_product_id() );
	update_post_meta( $listing_id, '_user_package_id', $package_id );

	// Delete expired job.
	delete_post_meta( $listing_id, '_job_expires' );

	// Update status.
	if ( ! $status ) {
		$status = get_option( 'job_manager_submission_requires_approval' ) ? 'pending' : 'publish';
	}
	$listing = array(
		'ID'            => $listing_id,
		'post_status'   => $status,
	);
	wp_update_post( $listing );

	// Increase package count.
	case27_paid_listing_user_package_increase_count( $package_id );

	// Update package status.
	$package_status = case27_paid_listing_get_proper_status( $package_id );
	if ( $package_status && $user_package->get_status() !== $package_status ) {
		wp_update_post( array(
			'ID'          => $user_package->get_id(),
			'post_status' => $package_status,
		) );
	}

	// Listing has already been published, trigger the set expiry function.
	if ( $status === 'publish' ) {
		$expires = \calculate_job_expiry( $listing_id );
		update_post_meta( $listing_id, '_job_expires', $expires );
	}
}

/**
 * Use Product to Listing
 * This will add product to cart and redirect user to checkout.
 *
 * @since 1.0.0
 *
 * @param int    $product_id Product ID.
 * @param int    $listing_id Listing ID.
 * @param bool   $is_claim   Is this a claim.
 */
function case27_paid_listing_use_product_to_listing( $product_id, $listing_id, $is_claim = false ) {
	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_type( array( 'job_package', 'job_package_subscription' ) ) ) {
		return;
	}

	// Do not modify listing on claim submission.
	if ( ! $is_claim ) {
		$duration = $product->get_product_meta( 'job_listing_duration' );

        // Give listing the package attributes
		update_post_meta( $listing_id, '_job_duration', $product->get_product_meta( 'job_listing_duration' ) );
		$expires = \calculate_job_expiry( $listing_id );
		update_post_meta( $listing_id, '_job_expires', $expires );
		update_post_meta( $listing_id, '_featured', $product->is_listing_featured() ? 1 : 0 );
		update_post_meta( $listing_id, '_package_id', $product->get_id() );

		// Update status.
		wp_update_post( apply_filters( 'mylisting/paid-listings/process-product/listing-details', [
			'ID'            => $listing_id,
			'post_status'   => 'pending_payment',
		], $listing_id, $product ) );
	}

	// Add package to the cart
	$data = array(
		'job_id'   => $listing_id,
		'is_claim' => $is_claim,
	);
	WC()->cart->add_to_cart( $product->get_id(), 1, '', '', $data );

	// Clear cookie
	wc_setcookie( 'chosen_package_id', '', time() - HOUR_IN_SECONDS );

	// Redirect to checkout page
	wp_redirect( apply_filters( 'mylisting/paid-listings/process-product/redirect-url', get_permalink( wc_get_page_id( 'checkout' ) ), $product, $data ) );
	exit;
}

/**
 * Send Mail Helper
 *
 * @since 1.0.0
 *
 * @param array $args Send mail args.
 * @return bool
 */
function case27_paid_listing_send_mail( $args, $filter_id = '' ) {
	// Get site domain.
	$domain = strtolower( $_SERVER['SERVER_NAME'] );
	if ( substr( $domain, 0, 4 ) == 'www.' ) {
		$domain = substr( $domain, 4 );
	}

	// Allowed tags in email content.
	$allowed_tags = array(
		'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ),
		'abbr' => array( 'title' => array() ),
		'acronym' => array( 'title' => array() ),
		'code' => array(),
		'pre' => array(),
		'em' => array(),
		'strong' => array(),
		'br' => array(),
		'div' => array(),
		'p' => array(),
		'ul' => array(),
		'ol' => array(),
		'li' => array(),
		'h1' => array(),
		'h2' => array(),
		'h3' => array(),
		'h4' => array(),
		'h5' => array(),
		'h6' => array(),
		'img' => array(
			'src' => array(),
			'class' => array(),
			'alt' => array(),
		),
	);

	// Args.
	$defaults = array(
		'to'             => '',
		'from'           => 'wordpress@' . $domain,
		'from_name'      => get_bloginfo( 'name' ),
		'reply_to'       => get_bloginfo( 'admin_email' ),
		'subject'        => '',
		'message'        => '',
		'content_type'   => 'text/html',
		'charset'        => get_bloginfo( 'charset' ),
	);
	$args = wp_parse_args( $args, $defaults );
	$args = apply_filters( 'case27_paid_listing_send_mail_args', $args, $filter_id );

	// Set headers.
	$headers  = array(
		'From: "' . strip_tags( $args['from_name'] ) . '" <' . sanitize_email( $args['from'] ) . '>',
		'Reply-To: ' . $args['reply_to'],
		'Content-type: ' . $args['content_type'] . '; charset: ' . $args['charset'],
	);

	if ( $args['to'] && is_email( $args['to'] ) && $args['subject'] && $args['message'] ) {
		return wp_mail( sanitize_email( $args['to'] ), esc_attr( $args['subject'] ), wp_kses( wpautop( $args['message'] ), $allowed_tags ), $headers );
	}

	return false;
}
