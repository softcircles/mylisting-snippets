<?php
/**
 * `Leave Review` quick action.
 *
 * @since 2.0
 */

if ( is_user_logged_in() && $listing->get_author_id() == get_current_user_id() ) {
	return;
}

?>

<li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
    <a href="#" class="show-review-form">
    	<?php echo c27()->get_icon_markup( $action['icon'] ) ?>
    	<span><?php echo $action['label'] ?></span>
    </a>
</li>
