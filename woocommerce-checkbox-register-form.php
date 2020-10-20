<?php


add_action('woocommerce_register_form', 'woocommerce_register_form' );

function woocommerce_register_form() { ?>

    <div class="terms-and-conditions">
        <div class="md-checkbox">
            <input id="subscribe" name="subscribe" type="checkbox" value="yes">
            <label for="subscribe">I have read and agree to the website <a href="#" class="woocommerce-terms-and-conditions-link" target="_blank">terms and conditions</a></label>
        </div>
    </div>

    <?php
}

add_action('woocommerce_created_customer', 'user_register' );
function user_register( $user_id ) {

    if ( ! empty( $_POST['subscribe'] ) ) {
        update_user_meta( $user_id, 'subscribe', trim( $_POST['subscribe'] ) );
    }
}
