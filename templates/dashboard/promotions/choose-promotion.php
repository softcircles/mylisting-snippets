<?php
/**
 * 'Promote Listing' dialog.
 *
 * @since 1.7.0
 * @var   array $products List of WooCommerce products of type promotion package, ordered by price.
 * @var   array $packages List of promotion packages owned by the user.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<!-- Modal - PROMOTIONS-->
<div id="promo-modal" class="modal modal-27" role="dialog">
    <div class="modal-dialog modal-md">

	    <!-- Modal Content -->
	    <div class="modal-content">
	        <div class="sign-in-box">

				<?php if ( ! empty( $products ) ): ?>
		            <!-- Title -->
		            <div class="title-style-1">
		                <span class="icon-box-add"></span>
		                <h5><?php _ex( 'Promote Listing', 'Choose promotion modal title', 'my-listing' ) ?> &mdash; <em class="listing-name"></em></h5>
		            </div>

		        	<!-- Packages-->
		            <ul class="promo-product-list buy-package">
						<?php foreach ( (array) $products as $product ):
							if ( ! $product->is_type( 'promotion_package' ) || ! $product->is_purchasable() || $product->get_duration() <= 0 ) {
								continue;
							}
							?>

			                <li class="promo-product-item" data-package-id="<?php echo esc_attr( $product->get_id() ) ?>" data-process="buy-package">
			                    <a href="#promote">
			                        <div class="promo-item-icon">
			                            <i class="icon-flash"></i>
			                        </div>
			                        <div class="promo-item-details">
			                            <h5><?php echo $product->get_name() ?></h5>
			                            <p>
			                            	<span><?php echo $product->get_price_html() ?></span>
			                            	<?php _ex(
			                            		sprintf( _x( 'Promotion lasts for %s days', 'Choose promotion modal', 'my-listing' ), number_format_i18n( $product->get_duration() ) ),
			                            		'Choose promotion modal - product description',
			                            		'my-listing'
			                            	) ?>
			                            </p>
						    <a href="#" class="read-more-description"><?php esc_html_e( 'Read More', 'my-listing' ); ?></a>
						    <p class="short-description" style="display: none;"><?php echo $product->get_short_description() ?></p>
			                        </div>
			                    </a>
			                </li>
						<?php endforeach ?>
	            	</ul>
				<?php endif ?>

				<?php if ( ! empty( $packages ) ): ?>
		            <!-- Owned Packages Title -->
		            <div class="title-style-1 available-promo-title">
		                <span class="icon-box-check"></span>
		                <h5><?php _ex( 'Owned packages', 'Choose promotion modal - owned packages', 'my-listing' ) ?></h5>
		            </div>

	            	<!-- Owned Packages -->
		            <ul class="promo-product-list use-package">
						<?php foreach ( (array) $packages as $package ):
							$duration = absint( get_post_meta( $package->ID, '_duration', true ) );
							$product_id  = absint( get_post_meta( $package->ID, '_product_id', true ) );

							if ( $duration <= 0 || ! ( $product = wc_get_product( $product_id ) ) || ! $product->is_type( 'promotion_package' ) ) {
								continue;
							}
							?>

			                <li class="promo-product-item" data-package-id="<?php echo esc_attr( $package->ID ) ?>" data-process="use-package">
			                    <a href="#promote">
			                        <div class="promo-item-icon">
			                            <i class="icon-flash"></i>
			                        </div>
			                        <div class="promo-item-details">
			                            <h5><?php echo $product->get_name() ?></h5>
			                            <p>
			                            	<?php _ex(
			                            		sprintf( _x( 'Promotion lasts for %s days', 'Choose promotion modal', 'my-listing' ), number_format_i18n( $duration ) ),
			                            		'Choose promotion modal - package description',
			                            		'my-listing'
			                            	) ?>
			                            </p>
			                        </div>
			                    </a>
			                </li>
						<?php endforeach ?>
	            	</ul>
				<?php endif ?>

				<?php if ( empty( $products ) && empty( $packages ) ): ?>
					<em><?php _ex( 'There aren\'t any promotion packages available at the moment.', 'Choose promotion modal no packages', 'my-listing' ) ?></em>
				<?php endif ?>
	        </div>
	    </div>
	</div>
</div>
