<?php
/**
 * `Send Email` quick action.
 *
 * @since 2.0
 */

if ( ! ( $email = $listing->get_field('email') ) ) {
	return;
}

$link = sprintf( 'mailto:%s?cc=info@fertilitysolutions.co.za&subject=Fertility%20Solutions', $email );
?>

<li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
    <a href="<?php echo esc_url( $link ) ?>" rel="nofollow">
    	<?php echo c27()->get_icon_markup( $action['icon'] ) ?>
    	<span><?php echo $action['label'] ?></span>
    </a>
</li>
