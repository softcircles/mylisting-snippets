<?php

add_action('template_redirect', 'wc_custom_redirect_after_purchase');

function wc_custom_redirect_after_purchase() {
    global $wp;

    if (is_checkout() && !empty($wp->query_vars['order-received'])) {

        $order = new WC_Order($wp->query_vars['order-received']);

        $quantity = 0;
        if (count($order->get_items()) > 0) {
            foreach ($order->get_items() as $item) {
				
                if ( ! empty( $item ) ) {
					$product        = $item->get_product();
					$product_id 	= $product->get_id();
					
					if ( $product_id == 46 ) {
						wp_redirect('http://www.example.com/'); // Example Site
						break;
					} elseif ( $product_id == 45 ) {
						wp_redirect('http://www.example.com/'); // Example Site
						break;
					}
                }
            }
        }
		
		exit();
    }
}
