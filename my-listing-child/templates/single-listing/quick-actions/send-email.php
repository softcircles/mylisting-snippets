<?php
/**
 * `Send Email` quick action.
 *
 * @since 2.0
 */

if ( ! ( $email = $listing->get_field('email') ) ) {
	return;
}

$link = sprintf( 'mailto:%s?cc=test@test.com&subject=The%20subject%20of%20the%20email&body=The%20body%20of%20the%20email', $email );
?>

<li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
    <a href="<?php echo esc_url( $link ) ?>" rel="nofollow">
    	<?php echo c27()->get_icon_markup( $action['icon'] ) ?>
    	<span><?php echo $action['label'] ?></span>
    </a>
</li>
