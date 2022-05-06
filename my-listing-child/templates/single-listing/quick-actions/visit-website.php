<?php
/**
 * `Vist Website` quick action.
 *
 * @since 2.0
 */

if ( ! ( $website = $listing->get_field('website') ) ) {
	return;
}

$rel = '';

if ( $listing->get_product_id() && $listing->get_product_id() == 123 ) {
	$rel = 'rel="nofollow"';
}
?>

<li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
    <a href="<?php echo esc_url( $website ) ?>" target="_blank" <?php echo $rel; ?>>
    	<?php echo c27()->get_icon_markup( $action['icon'] ) ?>
    	<span><?php echo $action['label'] ?></span>
    </a>
</li>
