<?php
/**
 * `Call now` quick action.
 *
 * @since 2.0
 */

if ( ! ( $phone = $listing->get_field('phone') ) ) {
	return;
}

$link = sprintf( 'tel:%s', $phone );
?>

<li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
    <a href="<?php echo esc_url( $link ) ?>" data-vars-ga-category="TEST123">
    	<?php echo c27()->get_icon_markup( $action['icon'] ) ?>
    	<span><?php echo $action['label'] ?></span>
    </a>
</li>
