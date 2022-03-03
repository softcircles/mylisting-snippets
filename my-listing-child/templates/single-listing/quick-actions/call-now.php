<?php
/**
 * `Call now` quick action.
 *
 * @since 2.0
 */

if ( ! ( $phone = $listing->get_field('phone') ) ) {
	return;
}

$field = $listing->get_field_object('phone', true );

$content_lock = $field->get_prop('content_lock');

if ( $content_lock ) : ?>
    <li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
        <a href="#" class="c27-display-button" data-listing-id="<?php echo esc_attr( $listing->get_id() ); ?>" data-field-id="<?php echo esc_attr( $field->get_key() ); ?>">
            <?php echo c27()->get_icon_markup( $action['icon'] ) ?>
            <span><?php echo $action['label'] ?></span>
        </a>
    </li>
<?php else :
    $link = sprintf( 'tel:%s', $phone );
?>

<li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
    <a href="<?php echo esc_url( $link ) ?>" rel="nofollow">
    	<?php echo c27()->get_icon_markup( $action['icon'] ) ?>
    	<span><?php echo $action['label'] ?></span>
    </a>
</li>
<?php
endif;
?>
