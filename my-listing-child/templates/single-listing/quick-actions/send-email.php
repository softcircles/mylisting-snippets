<?php
/**
 * `Send Email` quick action.
 *
 * @since 2.0
 */

if ( ! ( $email = $listing->get_field('email') ) ) {
	return;
}

$subject = sprintf( 'Inquiry to your course on TheraHub: %s', $listing->get_name() );
$body  = sprintf( 'Hi,  I am interested in your course with the following details that are mentioned below. Course name: %s Course date: date Please reach out with an offer. Best,', $listing->get_name() );

$link = sprintf( 'mailto:%s?subject=%s&body=%s', $email, $subject, $body );

?>

<li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
    <a href="<?php echo esc_url( $link ) ?>" rel="nofollow">
    	<?php echo c27()->get_icon_markup( $action['icon'] ) ?>
    	<span><?php echo $action['label'] ?></span>
    </a>
</li>
