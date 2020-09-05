<?php

do_action( 'case27_woocommerce_account_products_before' );

do_action( 'case27_woocommerce_account_products_published_before' );

$published_products_page = isset( $_GET['published_products_page'] ) ? (int) $_GET['published_products_page'] : 1;
$pending_products_page = isset( $_GET['pending_products_page'] ) ? (int) $_GET['pending_products_page'] : 1;

$published_products = new WP_Query([
	'post_type' => 'product',
	'posts_per_page' => 5,
	'post_status' => 'publish',
	'author' => get_current_user_id(),
	'paged' => $published_products_page,
	]);
?>

<a href="<?php echo esc_url( wc_get_account_endpoint_url('add-product') ) ?>" class="buttons button-5 button-animated pull-right">
	<?php _e('Add a Product', 'my-listing') ?> <i class="mi keyboard_arrow_right"></i>
</a>

<?php if ( $published_products->have_posts() ) : ?>
	<p><?php _e( 'Your published products are shown in the table below.', 'my-listing' ) ?></p>
	<table class="c27-products-table shop_table">
		<thead>
			<tr>
				<th class="product-photo"><i class="mi photo"></i></th>
				<th class="product-title"><?php _e( 'Name', 'my-listing' ) ?></th>
				<th class="product-stock-status"><?php _e( 'Stock', 'my-listing' ) ?></th>
				<th class="product-date"><?php _e( 'Last Modified', 'my-listing' ) ?></th>
				<th class="product-date"><?php _e( 'Sales', 'my-listing' ) ?></th>
			</tr>
		</thead>
		<tbody>
			<?php while ($published_products->have_posts()): $published_products->the_post(); $product = wc_get_product( get_the_ID() ); ?>
				<tr>
					<td class="product-photo"><?php echo $product->get_image('thumbnail') ?></td>
					<td class="product-title">
						<h5>
							<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
								<?php echo esc_html( $product->get_name() ) ?>
							</a>
						</h5>
						<ul class="job-dashboard-actions">
							<li><a href="<?php echo esc_url( add_query_arg( 'product_id', get_the_ID(), wc_get_account_endpoint_url('add-product') ) ) ?>"><?php _e( 'Edit', 'my-listing' ) ?></a></li>

							<?php
							$delete_url = add_query_arg( [
								'action' 	 => 'delete',
								'product_id' => get_the_ID()
							], wc_get_account_endpoint_url( 'my-products' ) );

							printf(
								'<li>
									<a href="%s">%s</a>
								</li>',
								esc_url( wp_nonce_url( $delete_url, 'mylisting_delete_product' ) ),
								__( 'Delete', 'my-listing' )
							);
							?>
						</ul>
					</td>
					<td class="product-stock-status"><?php echo $product->is_in_stock() ? __( 'In stock', 'my-listing' ) : __( 'Out of stock', 'my-listing' ) ?></td>
					<td><?php echo date('M j Y \a\t H:i', strtotime( $product->get_date_modified() ) ) ?></td>
					<td><?php echo esc_html( $product->get_total_sales() ) ?></td>
				</tr>
			<?php endwhile ?>
		</tbody>
	</table>

	<div class="pagination center-button">
		<?php echo paginate_links([
			'format'  => '?published_products_page=%#%',
			'current' => $published_products_page,
			'total'   => $published_products->max_num_pages,
			'add_args' => ['pending_products_page' => $pending_products_page],
			]);
			wp_reset_postdata(); ?>
	</div>
<?php else: ?>
	<?php _e( 'No products found', 'my-listing' ) ?>
<?php endif ?>

<?php do_action( 'case27_woocommerce_account_products_published_after' ) ?>

<br>

<?php do_action( 'case27_woocommerce_account_products_pending_before' ) ?>

<?php
$pending_products = new WP_Query([
	'post_type' => 'product',
	'posts_per_page' => 5,
	'post_status' => 'pending',
	'author' => get_current_user_id(),
	'paged' => $pending_products_page,
	]);

if ( $pending_products->have_posts() ): ?>
	<p><?php _e( 'Your pending products are shown in the table below.', 'my-listing' ) ?></p>
	<table class="c27-products-table shop_table">
		<thead>
			<tr>
				<th class="product-photo"><i class="mi photo"></i></th>
				<th class="product-title"><?php _e( 'Name', 'my-listing' ) ?></th>
				<th class="product-stock-status"><?php _e( 'Stock', 'my-listing' ) ?></th>
				<th class="product-date"><?php _e( 'Last Modified', 'my-listing' ) ?></th>
			</tr>
		</thead>
		<tbody>
			<?php while ($pending_products->have_posts()): $pending_products->the_post(); $product = wc_get_product( get_the_ID() ); ?>
				<tr>
					<td class="product-photo"><?php echo $product->get_image('thumbnail') ?></td>
					<td class="product-title">
						<h5>
							<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
								<?php echo esc_html( $product->get_name() ) ?>
							</a>
						</h5>
						<ul class="job-dashboard-actions">
							<li><a href="<?php echo esc_url( add_query_arg( 'product_id', get_the_ID(), wc_get_account_endpoint_url('add-product') ) ) ?>"><?php _e( 'Edit', 'my-listing' ) ?></a></li>
						</ul>
					</td>
					<td class="product-stock-status"><?php echo $product->is_in_stock() ? __( 'In stock', 'my-listing' ) : __( 'Out of stock', 'my-listing' ) ?></td>
					<td><?php echo date('M j Y \a\t H:i', strtotime( $product->get_date_modified() ) ) ?></td>
				</tr>
			<?php endwhile ?>
		</tbody>
	</table>

	<div class="pagination center-button">
		<?php echo paginate_links([
			'format'  => '?pending_products_page=%#%',
			'current' => $pending_products_page,
			'total'   => $pending_products->max_num_pages,
			'add_args' => ['published_products_page' => $published_products_page],
			]);
			wp_reset_postdata(); ?>
	</div>
<?php else: ?>
	<?php _e( 'No products found', 'my-listing' ) ?>
<?php endif ?>

<?php do_action( 'case27_woocommerce_account_products_pending_after' ) ?>

<?php do_action( 'case27_woocommerce_account_products_after' ) ?>
