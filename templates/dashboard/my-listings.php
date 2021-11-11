<?php
/**
 * User listings dashboard page.
 *
 * @since 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! \MyListing\Src\User_Roles\user_can_add_listings() ) {
	printf(
		'<div class="element col-sm-6 text-center col-sm-offset-3">%s</div>',
		__( 'You cannot access this page.' )
	);
	return;
}

$endpoint = wc_get_account_endpoint_url( \MyListing\my_listings_endpoint_slug() );
?>

<?php do_action( 'mylisting/user-listings/before' ) ?>

<div class="row my-listings-tab-con">
	<div class="col-md-6 mlduo-welcome-message">
		<h1><?php _ex( 'Your listings', 'Dashboard welcome message', 'my-listing' ) ?></h1>
	</div>
	<?php require locate_template( 'templates/dashboard/partials/filter-by-type-dropdown.php' ) ?>
	<div class="col-md-3">
		<select class="custom-select filter-listings-select" required="required" onchange="window.location.href=this.value;">
			<option value="<?php echo esc_url( $endpoint ) ?>" <?php selected( $active_status === 'all' ) ?>>
				<?php _ex( 'All Listings', 'User dashboard', 'my-listing' ) ?>
			</option>

			<option value="<?php echo esc_url( add_query_arg( 'status', 'publish', $endpoint ) ) ?>" <?php selected( $active_status === 'publish' ) ?>>
				<?php _ex( 'Published', 'User dashboard', 'my-listing' ) ?>
			</option>

			<option value="<?php echo esc_url( add_query_arg( 'status', 'pending', $endpoint ) ) ?>" <?php selected( $active_status === 'pending' ) ?>>
				<?php _ex( 'Pending Approval', 'User dashboard', 'my-listing' ) ?>
			</option>

			<option value="<?php echo esc_url( add_query_arg( 'status', 'expired', $endpoint ) ) ?>" <?php selected( $active_status === 'expired' ) ?>>
				<?php _ex( 'Expired', 'User dashboard', 'my-listing' ) ?>
			</option>

			<optgroup>
				<option value="<?php echo esc_url( add_query_arg( 'status', 'pending_payment', $endpoint ) ) ?>" <?php selected( $active_status === 'pending_payment' ) ?>>
					<?php _ex( 'Pending Payment', 'User dashboard', 'my-listing' ) ?>
				</option>

				<option value="<?php echo esc_url( add_query_arg( 'status', 'preview', $endpoint ) ) ?>" <?php selected( $active_status === 'preview' ) ?>>
					<?php _ex( 'Preview', 'User dashboard', 'my-listing' ) ?>
				</option>
			</optgroup>

			<?php if ( mylisting_get_setting( 'claims_enabled' ) ):
				$claims = get_posts( [
					'post_type' => 'claim',
					'post_status' => 'publish',
					'posts_per_page' => 1,
					'meta_key' => '_user_id',
					'meta_value' => get_current_user_id(),
					'fields' => 'ids',
				] ); ?>
				<?php if ( ! empty( $claims ) ): ?>
					<optgroup>
						<option value="<?php echo esc_url( wc_get_account_endpoint_url( _x( 'claim-requests', 'Claims user dashboard page slug', 'my-listing' ) ) ) ?>">
							<?php _ex( 'Claim requests', 'User dashboard', 'my-listing' ) ?>
						</option>
					</optgroup>
				<?php endif ?>
			<?php endif ?>
		</select>
	</div>
</div>

<div class="row my-listings-stat-box">
	<?php
	mylisting_locate_template( 'templates/dashboard/stats/card.php', [
		'icon' => 'icon-window',
		'value' => number_format_i18n( absint( $stats->get( 'listings.published' ) ) ),
		'description' => _x( 'Published', 'Dashboard stats', 'my-listing' ),
		'background' => mylisting()->get( 'stats.color1' ),
	] );

	// Pending listing count (pending_approval + pending_payment).
	mylisting_locate_template( 'templates/dashboard/stats/card.php', [
		'icon' => 'mi info_outline',
		'value' => number_format_i18n( absint( $stats->get( 'listings.pending_approval' ) ) ),
		'description' => _x( 'Pending Approval', 'Dashboard stats', 'my-listing' ),
		'background' => mylisting()->get( 'stats.color2' ),
	] );

	// Promoted listing count.
	mylisting_locate_template( 'templates/dashboard/stats/card.php', [
		'icon' => 'mi info_outline',
		'value' => number_format_i18n( absint( $stats->get( 'listings.pending_payment' ) ) ),
		'description' => _x( 'Pending Payment', 'Dashboard stats', 'my-listing' ),
		'background' => mylisting()->get( 'stats.color3' ),
	] );

	// Recent views card.
	mylisting_locate_template( 'templates/dashboard/stats/card.php', [
		'icon' => 'mi timer',
		'value' => number_format_i18n( absint( $stats->get( 'listings.expired' ) ) ),
		'description' => _x( 'Expired', 'Dashboard stats', 'my-listing' ),
		'background' => mylisting()->get( 'stats.color4' ),
	] );
	?>
</div>

<div id="job-manager-job-dashboard">
	<?php if ( ! $listings ) : ?>
		<div class="no-listings">
			<i class="no-results-icon material-icons">mood_bad</i>
			<?php _e( 'You do not have any active listings.', 'my-listing' ); ?>
		</div>
	<?php else : ?>
		<section class="i-section listing-feed">
			<div class="container-fluid">
				<div class="row section-body grid">
					<?php foreach ( $listings as $listing ): ?>
						<div class="col-lg-4 col-md-3 col-sm-6 col-xs-12">
							<?php echo \MyListing\get_preview_card( $listing->get_id() ); ?>
							<div class="listing-actions">
								<ul class="job-dashboard-actions">
									<?php if ( $listing->get_status() === 'pending_payment' ): ?>
										<?php if ( ! empty( $pending_orders[ $listing->get_id() ] ) && ( $order = wc_get_order( $pending_orders[ $listing->get_id() ] ) ) ): ?>
											<li class="cts-listing-action-view-order">
												<a href="<?php echo esc_url( $order->get_view_order_url() ) ?>">
													<?php _ex( 'Order details', 'User dashboard', 'my-listing' ) ?>
												</a>
											</li>

											<?php if ( $order->needs_payment() ): ?>
												<li class="cts-listing-action-checkout">
													<a href="<?php echo esc_url( $order->get_checkout_payment_url() ) ?>">
														<?php _ex( 'Pay Now', 'User dashboard', 'my-listing' ) ?>
													</a>
												</li>
											<?php endif ?>
										<?php endif ?>
									<?php endif ?>

									<?php do_action( 'mylisting/user-listings/actions', $listing ) ?>
									<?php /* @deprecated */ do_action( 'mylisting/dashboard/listing-actions', $listing ) ?>
								</ul>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif ?>

	<nav class="job-manager-pagination">
		<?php echo paginate_links( [
			'format'    => '?pg=%#%',
			'current'   => ! empty( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 1,
			'total'     => $query->max_num_pages,
			'type'      => 'list',
			'end_size'  => 3,
			'mid_size'  => 3
		 ] ) ?>
	</nav>
</div>
