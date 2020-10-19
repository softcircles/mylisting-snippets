<?php


add_action('woocommerce_register_form', 'woocommerce_register_form' );

function woocommerce_register_form() { ?>

    <div class="form-row form-row-wide">
        <p>
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" style="margin-left: 0; top: 6px;" name="subscribe" <?php checked( apply_filters( 'woocommerce_subscribe_is_checked_default', isset( $_POST['subscribe'] ) ), true ); ?> id="subscribe" />
                <span style="margin-left: 20px;">Some Text</span>
            </label>
            <input type="hidden" name="subscribe-field" value="1" />
        </p>
    </div>

    <?php
}

add_action('woocommerce_created_customer', 'user_register' );
function user_register( $user_id ) {

    if ( ! empty( $_POST['subscribe'] ) ) {
        update_user_meta( $user_id, 'subscribe', trim( $_POST['subscribe'] ) );
    }
}
