<?php
/**
 * User listings dashboard page.
 *
 * @since 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$endpoint = wc_get_account_endpoint_url( 'my-listings' );
?>

<?php do_action( 'mylisting/user-listings/before' ) ?>

<div class="row my-listings-tab-con">
	<div class="col-md-9 mlduo-welcome-message">
		<h1><?php _ex( 'Your listings', 'Dashboard welcome message', 'my-listing' ) ?></h1>
	</div>
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
		<table class="job-manager-jobs">
			<tbody>
			<?php foreach ( $listings as $listing ): ?>
				<tr class="item-id-<?php echo $listing->get_id() ?> item-product-<?php echo $listing->get_product_id()?:'na' ?> item-type-<?php echo $listing->type?$listing->type->get_slug():'na' ?>">
					<td class="l-type">
						<div class="info listing-type">
							<div class="value">
								<?php echo $listing->type ? $listing->type->get_singular_name() : '&ndash;'; ?>
							</div>
						</div>
					</td>
					<td class="c27_listing_logo">
						<?php if ( $gallery = $listing->get_field('gallery') ) : ?>
							<img src="<?php echo esc_url( $gallery[0] ); ?>">
						<?php else : ?>
							<img src="<?php echo $listing->get_logo('thumbnail') ?: c27()->image( 'marker.jpg' ) ?>">
						<?php endif; ?>
					</td>
					<td class="job_title">
						<?php if ( $listing->get_data('post_status') === 'publish' ) : ?>
							<a href="<?php echo esc_url( $listing->get_link() ) ?>"><?php echo esc_html( $listing->get_name() ) ?></a>
						<?php else : ?>
							<?php echo esc_html( $listing->get_name() ) ?><small>(<?php echo $listing->get_status_label() ?>)</small>
						<?php endif; ?>
					</td>
					<td class="listing-actions">
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
					</td>
					<td class="listing-info">
						<?php if ( $package = $listing->get_product() ): ?>
							<div class="info listing-package">
								<div class="label"><?php _ex( 'Package:', 'User listings dashboard', 'my-listing' ) ?></div>
								<div class="value"><?php echo esc_html( $package->get_name() ) ?></div>
							</div>
						<?php endif ?>
						<div class="info created-at">
							<div class="label"><?php _ex( 'Created:', 'User listings dashboard', 'my-listing' ) ?></div>
							<div class="value"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $listing->get_data('post_date') ) ) ?></div>
						</div>
						<div class="info expires-at">
							<div class="label"><?php _ex( 'Expires:', 'User listings dashboard', 'my-listing' ) ?></div>
							<div class="value">
								<?php echo $listing->get_data('_job_expires') ? date_i18n( get_option( 'date_format' ), strtotime( $listing->get_data('_job_expires') ) ) : '&ndash;'; ?>
							</div>
						</div>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
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
