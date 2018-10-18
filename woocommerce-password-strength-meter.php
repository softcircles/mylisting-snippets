<?php

add_filter( 'woocommerce_min_password_strength', 'mylisting_change_password_strength' );
 
function mylisting_change_password_strength( $strength ) {
	 return 12;
}
