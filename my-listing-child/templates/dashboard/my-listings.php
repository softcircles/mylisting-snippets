<?php
/**
 * User listings dashboard page.
 *
 * @since 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<?php do_action( 'mylisting/user-listings/before' ) ?>

<div class="row my-listings-stat-box">
	<?php
	mylisting_locate_template( 'templates/dashboard/stats/card.php', [
		'icon' => 'icon-window',
		'value' => number_format_i18n( absint( $stats->get( 'listings.published' ) ) ),
		'description' => _x( 'Published', 'Dashboard stats', 'my-listing' ),
		'background' => mylisting()->stats()->color_one,
	] );

	// Pending listing count (pending_approval + pending_payment).
	mylisting_locate_template( 'templates/dashboard/stats/card.php', [
		'icon' => 'mi info_outline',
		'value' => number_format_i18n( absint( $stats->get( 'listings.pending_approval' ) ) ),
		'description' => _x( 'Pending Approval', 'Dashboard stats', 'my-listing' ),
		'background' => mylisting()->stats()->color_two,
	] );

	// Promoted listing count.
	mylisting_locate_template( 'templates/dashboard/stats/card.php', [
		'icon' => 'mi info_outline',
		'value' => number_format_i18n( absint( $stats->get( 'listings.pending_payment' ) ) ),
		'description' => _x( 'Pending Payment', 'Dashboard stats', 'my-listing' ),
		'background' => mylisting()->stats()->color_three,
	] );

	// Recent views card.
	mylisting_locate_template( 'templates/dashboard/stats/card.php', [
		'icon' => 'mi timer',
		'value' => number_format_i18n( absint( $stats->get( 'listings.expired' ) ) ),
		'description' => _x( 'Expired', 'Dashboard stats', 'my-listing' ),
		'background' => mylisting()->stats()->color_four,
	] );
	?>
</div>

<div class="row more-actions">
	<div class="col-md-12">
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
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( _x( 'claim-requests', 'Claims user dashboard page slug', 'my-listing' ) ) ) ?>" class="pull-right">
					<?php _ex( 'View claim requests &rarr;', 'User dashboard', 'my-listing' ) ?>
				</a>
			<?php endif ?>
		<?php endif ?>
	</div>
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
				<tr>
					<td class="l-type">
						<div class="info listing-type">
							<div class="value">
								<?php echo $listing->type ? $listing->type->get_singular_name() : '&ndash;'; ?>
							</div>
						</div>
					</td>
					<td class="c27_listing_logo">
						<img src="<?php echo $listing->get_logo('thumbnail') ?: c27()->image( 'marker.jpg' ) ?>">
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
							<?php
								$actions = array();

								switch ( $listing->get_data('post_status') ) {
									case 'publish' :

										if ( current_user_can( 'edit_posts' ) ) {
											$actions['edit'] = array( 'label' => __( 'Edit', 'my-listing' ), 'nonce' => false );
										}
										break;
									case 'pending_payment' :
									case 'pending' :
										if ( mylisting_get_setting( 'user_can_edit_pending_submissions' ) ) {
											$actions['edit'] = array( 'label' => __( 'Edit', 'my-listing' ), 'nonce' => false );
										}
									break;
								}

								$actions['delete'] = array( 'label' => __( 'Delete', 'my-listing' ), 'nonce' => true );
								$actions           = apply_filters( 'job_manager_my_job_actions', $actions, $listing->get_data() );

								foreach ( $actions as $action => $value ) {
									$value['type'] = ! empty( $value['type'] ) ? $value['type'] : 'link';

									if ( $value['type'] === 'plain' ) {
										if ( empty( $value['content'] ) ) {
											continue;
										}

										echo $value['content'];
									} else {
										$action_url = add_query_arg( array( 'action' => $action, 'job_id' => $listing->get_id() ) );
										if ( $value['nonce'] ) {
											$action_url = wp_nonce_url( $action_url, 'mylisting_dashboard_actions' );
										}
										echo '<li class="cts-listing-action-'.esc_attr( $action ).'"><a href="' . esc_url( $action_url ) . '" class="job-dashboard-action-' . esc_attr( $action ) . '">' . esc_html( $value['label'] ) . '</a></li>';
									}
								}
							?>

							<?php do_action( 'mylisting/dashboard/listing-actions', $listing ) ?>
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
