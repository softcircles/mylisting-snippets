<?php
/**
 * `Send Email` quick action.
 *
 * @since 2.0
 */

if ( ! ( $email = $listing->get_field('email') ) ) {
	return;
}

$url = $email;
$url .= '?subject=ENTER SUBJECT'; // Enter your subject
$link = sprintf( 'mailto:%s', $url );
?>

<li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
    <a href="<?php echo esc_url( $link ) ?>" rel="nofollow">
    	<?php echo c27()->get_icon_markup( $action['icon'] ) ?>
    	<span><?php echo $action['label'] ?></span>
    </a>
</li>
