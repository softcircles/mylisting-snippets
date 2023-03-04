<?php

namespace MyListing\Ext\Reviews;

// Walker class used to display comments/reviews on listings.
class Walker extends \Walker_Comment {

	var $tree_type = 'comment';
	var $db_fields = array( 'parent' => 'comment_parent', 'id' => 'comment_ID' );
	var $current_reply_link;

	function __construct() {
		?><ul class="comments-list"><?php
	}

	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$GLOBALS['comment_depth'] = $depth + 1;
		?><ul class="replies"><?php
	}

	function end_lvl( &$output, $depth = 0, $args = array() ) {
		$GLOBALS['comment_depth'] = $depth + 1;
		?></ul></li><?php
	}

	function start_el( &$output, $comment, $depth = 0, $args = array(), $id = 0 ) {
		$depth++;
		$GLOBALS['comment_depth'] = $depth;
		$GLOBALS['comment'] = $comment;
		$parent_class = ( empty( $args['has_children'] ) ? '' : 'parent' );
		$other_classes = ' single-comment ';
		$is_reply = false;
		if ($depth > 1) {
			$other_classes .= ' reply ';
			$is_reply = true;
		}
		?>

		<li <?php comment_class( $parent_class . $other_classes ); ?> id="comment-<?php comment_ID() ?>">
			<div class="comment-container">
				<div class="comment-head">

					<?php if ($args['avatar_size'] != 0): ?>
						<div class="c27-user-avatar">
							<?php echo get_avatar( $comment, $args['avatar_size'] ); ?>
						</div>
					<?php endif ?>

					<h5 class="case27-primary-text"><?php echo get_comment_author_link() ?></h5>
					<span class="comment-date"><?php comment_date() ?> <?php _e( 'at', 'my-listing' ) ?> <?php comment_time() ?> <?php edit_comment_link( esc_html__('&middot; Edit', 'my-listing') ); ?></span>

					<?php if ( ! $is_reply && ( $review_rating = \MyListing\Ext\Reviews\Reviews::get_rating( get_comment_ID() ) ) && \MyListing\is_rating_enabled( $comment->comment_post_ID ) ): ?>	
						<?php
						$rating = get_comment_meta( get_comment_ID(), '_case27_post_rating', true );
					    if ( ! empty( $rating && $rating >= 0 && $rating <= 10 ) ) {
					        echo str_repeat( '&#11088', round( $rating / 2 ) ) . ' ('. round( $rating / 2, 1 ) .'/5)';
					    }
					    ?>
						<?php mylisting_locate_template( 'partials/star-ratings.php', [
                            'rating' => $review_rating,
                            'max-rating' => \MyListing\Ext\Reviews\Reviews::max_rating( $comment->comment_post_ID ),
                            'class' => 'listing-rating listing-review-rating',
                        ] ) ?>
					<?php endif ?>
				</div>

				<div class="comment-body">
					<?php if( !$comment->comment_approved ) : ?>
						<p><em class="comment-awaiting-moderation"><?php esc_html_e('Your comment is awaiting moderation.', 'my-listing') ?></em></p>
					<?php else: ?>
						<?php comment_text() ?>
					<?php endif; ?>
				</div>
				<div class="reply comment-info">
					<?php
					comment_reply_link( array_merge( $args, array(
						'depth' => $depth,
						'max_depth' => $args['max_depth'],
						'reply_text' => '<i class="mi chat_bubble_outline"></i>' . __( 'Reply', 'my-listing' ),
						))); ?>
				</div>
			</div>

		<?php if (!$args['has_children']): ?>
			</li>
		<?php endif ?>

	<?php }

	function end_el(&$output, $comment, $depth = 0, $args = array() ) {
		?></li><?php
	}

	function __destruct() {
		?></ul><?php
	}
}
