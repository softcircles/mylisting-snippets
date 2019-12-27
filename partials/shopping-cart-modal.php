<?php
/**
 * WooCommerce shopping cart modal.
 *
 * @since 1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Widget_Cart' ) ) {
	return;
}

if ( apply_filters( 'woocommerce_widget_cart_is_hidden', is_cart() || is_checkout() ) ) {
	return;
}
?>

<!-- Modal - WC Cart Contents-->
<div id="wc-cart-modal" class="modal modal-27" role="dialog">
    <div class="modal-dialog modal-md">
	    <div class="modal-content">
	        <div class="sign-in-box">
				<?php $args = array(
'before_title' => '<h2 class="widgettitle">',
'after_title' => '</h2>'
);
				$instance = [];
the_widget( 'WC_Widget_Cart', $instance, $args );?>
			</div>
		</div>
	</div>
</div>
