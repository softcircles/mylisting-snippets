<?php
/**
 * Template for rendering an `author` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

$author = $listing->get_author();
if ( ! ( $author instanceof \MyListing\Src\User && $author->exists() ) ) {
	return;
}
$author_name = $author->get_name();

if( !empty( $author->user_firstname ) && !empty( $author->user_lastname )){
	$author_name = $author->user_firstname . ' ' . ucfirst($author->user_lastname[0]);
}


if( empty( $author->user_firstname ) && !empty( $author->user_lastname )){
	$author_name = $author->user_lastname;
}

?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element related-listing-block">
		<div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>
		</div>
		<div class="pf-body">
			<div class="event-host">
				<a href="<?php echo esc_url( $author->get_link() ) ?>">
					<div class="avatar">
						<img src="<?php echo esc_url( $author->get_avatar() ) ?>">
					</div>
					<span class="host-name"><?php echo esc_html( $author_name ) ?></span>
				</a>
			</div>
		</div>
	</div>
</div>
