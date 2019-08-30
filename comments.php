<?php
/**
 * The template for displaying reviews.
 *
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */

if ( post_password_required() ) {
    return;
}

?>

<div class="container">
    <div class="row reveal">
        <div class="col-md-<?php echo comments_open() ? '7' : '12' ?>">

            <?php if (!comments_open()): ?>
                <div class="no-results-wrapper">
                    <i class="no-results-icon material-icons">mood_bad</i>
                    <li class="no_job_listings_found"><?php _e( 'Comments are closed.', 'my-listing' ) ?></li>
                </div>
            <?php else: ?>
                <?php if (!have_comments()): ?>
                    <div class="no-results-wrapper">
                        <i class="no-results-icon material-icons">mood_bad</i>
                        <li class="no_job_listings_found"><?php _e( 'No comments yet.', 'my-listing' ) ?></li>
                    </div>
                <?php else: ?>
                    <?php
                    wp_list_comments( [
                        'walker' => new MyListing\Ext\Reviews\Walker,
                        'type' => 'all',
                    ] );
                    ?>
                <?php endif ?>
            <?php endif ?>

            <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ): ?>
                <nav id="comment-nav-below" class="navigation comment-navigation" role="navigation">
                    <h2 class="screen-reader-text"><?php esc_html_e( 'Comment navigation', 'my-listing' ); ?></h2>
                    <div class="nav-links">
                        <div class="nav-previous"><?php previous_comments_link( esc_html__( 'Older Comments', 'my-listing' ) ); ?></div>
                        <div class="nav-next"><?php next_comments_link( esc_html__( 'Newer Comments', 'my-listing' ) ); ?></div>
                    </div>
                </nav>
            <?php endif; ?>
        </div>

        <?php if ( comments_open() ): ?>
            <div class="col-md-5">
                <div>
                    <div class="element">
                        <div class="pf-head">
                            <div class="title-style-1">
                                <i class="material-icons">chat</i>
                                <?php if (is_singular('job_listing') && $GLOBALS['case27_reviews_allow_rating']): ?>
                                    <h5><?php _e( 'Add a review', 'my-listing' ) ?></h5>
                                <?php else: ?>
                                    <h5><?php _e( 'Add a comment', 'my-listing' ) ?></h5>
                                <?php endif ?>
                            </div>
                        </div>
                        <div class="pf-body">

                            <!-- Rating Field -->
                            <?php $rating_field = MyListing\Ext\Reviews\Reviews::get_ratings_field( false, get_the_ID() ); ?>

                            <!-- Gallery Field -->
                            <?php $gallery_field = MyListing\Ext\Reviews\Reviews::get_gallery_field( false, get_the_ID() ); ?>

                            <!-- Author Field -->
                            <?php ob_start(); ?>
                            <div class="form-group">
                                <label><?php _e( 'Name', 'my-listing' ) ?></label>
                                <input name="author" type="text" value="<?php echo esc_attr( $commenter['comment_author'] ) ?>" required="required" placeholder="<?php echo esc_html__('Your Name', 'my-listing') ?>">
                            </div>
                            <?php $author_field = ob_get_clean(); ?>

                            <!-- Email Field -->
                            <?php ob_start(); ?>
                            <div class="form-group">
                                <label><?php _e( 'Email', 'my-listing' ) ?></label>
                                <input name="email" type="text" value="<?php echo esc_attr( $commenter['comment_author_email'] ) ?>" required="required" placeholder="<?php echo esc_html__('Your Email', 'my-listing') ?>">
                            </div>
                            <?php $email_field = ob_get_clean(); ?>

                            <!-- Message Field -->
                            <?php ob_start(); ?>
                            <div class="form-group">
                                <label><?php _e( 'Your Message', 'my-listing' ) ?></label>
                                <textarea rows="5" name="comment" required="required" placeholder="<?php echo esc_html__('Enter message...', 'my-listing') ?>"></textarea>
                            </div>
                            <?php $message_field = ob_get_clean(); ?>

                            <!-- Cookies Field -->
                            <?php ob_start(); ?>
                            <div class="comment-form-cookies-consent md-checkbox">
                                <input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes" <?php echo empty( $commenter['comment_author_email'] ) ? '' : ' checked="checked"' ?>>
                                <label for="wp-comment-cookies-consent"><?php _e( 'Save my name, email, and website in this browser for the next time I comment.', 'my-listing' ) ?></label>
                            </div>
                            <?php $cookies_field = ob_get_clean(); ?>

                            <!-- Cookies Field -->
                            <?php ob_start();
                            $privacy_policy = __( 'Privacy Policy', 'my-listing' );
                            $link = '<a href="#" target="_blank" class="comment-form-policy__see-more-link">' . esc_html( $privacy_policy ) . '</a>';
                            ?>

                            <div class="comment-form-cookies-consent md-checkbox">

                                <input id="wp-comment-policy" name="wp-comment-policy" value="policy-key" class="comment-form-policy__input" type="checkbox" aria-req="true">
                                <label for="wp-comment-policy">
                                    <?php echo sprintf(
                                        /* translators: %s: Privacy Policy page link */
                                        __( 'I have read and accepted the %s', 'my-listing' ),
                                        $link
                                    ); ?>
                                </label>
                            </div>
                            <?php $policy_field = ob_get_clean(); ?>

                            <!-- Submit Field -->
                            <?php ob_start(); ?>
                                <?php if (is_singular('job_listing') && $GLOBALS['case27_reviews_allow_rating']): ?>
                                    <button name="submit" type="submit" class="buttons button-2 button-animated">
                                        <?php echo esc_html__('Submit review', 'my-listing') ?>
                                    </button>
                                <?php else: ?>
                                    <button name="submit" type="submit" class="buttons button-2 button-animated submit-comment">
                                        <?php echo esc_html__('Submit comment', 'my-listing') ?>
                                    </button>
                                <?php endif ?>
                            <?php $submit_field = ob_get_clean(); ?>

                            <?php
                            $args = array(
                                'comment_field'       => $rating_field . $gallery_field . $message_field . $policy_field . $submit_field,
                                'class_submit'        => 'hide',
                                'cancel_reply_before' => ' &middot; <span>',
                                'cancel_reply_after'  => '</span>',
                            );

                            $user_review = MyListing\Ext\Reviews\Reviews::has_user_reviewed( get_current_user_id(), get_the_ID() );

                            if ( ! is_user_logged_in() ) {
                                $args['comment_field'] = '';
                                $args['fields'] = array(
                                    'rating_field'  => $rating_field,
                                    'gallery_field' => $gallery_field,
                                    'author'        => $author_field,
                                    'email'         => $email_field,
                                    'comment_field' => $message_field,
                                    'cookies'       => $cookies_field,
                                    'policy'        => $policy_field,
                                    'submit'        => $submit_field,
                                );
                            } elseif ( $user_review ) {
                                $args['comment_field'] = $message_field . $policy_field . $submit_field;
                            }
                            ?>

                            <div class="sidebar-comment-form">
                                <?php
                                $hide_comment_form = false;
                                if ( $user_review && is_singular('job_listing') ) : $hide_comment_form = true; ?>
                                    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" enctype="multipart/form-data">
                                        <?php echo MyListing\Ext\Reviews\Reviews::get_ratings_field( $user_review, get_the_ID() ); ?>
                                        <?php echo MyListing\Ext\Reviews\Reviews::get_gallery_field( $user_review, get_the_ID() ); ?>
                                        <div class="form-group">
                                            <label><?php _e( 'Your Message', 'my-listing' ) ?></label>
                                            <textarea rows="5" name="comment" required="required" placeholder="<?php echo esc_html__('Enter message...', 'my-listing') ?>"><?php echo get_comment_text( $user_review ) ?></textarea>
                                        </div>
                                        <input type="hidden" name="action" value="update_review">
                                        <input type="hidden" name="listing_id" value="<?php echo esc_attr( get_the_ID() ) ?>">
                                        <?php if ($GLOBALS['case27_reviews_allow_rating']): ?>
                                            <button type="submit" class="buttons button-2 button-animated"><?php echo esc_html__('Update review', 'my-listing') ?></button>
                                        <?php else: ?>
                                            <button type="submit" class="buttons button-2 button-animated"><?php echo esc_html__('Update comment', 'my-listing') ?></button>
                                        <?php endif ?>
                                    </form>
                                <?php endif ?>

                                <div class="<?php echo $hide_comment_form ? 'hide' : '' ?>">
                                    <?php
                                        // This action prints a <script> tag which triggers a Vue template warning.
                                        remove_action( 'comment_form', 'wp_comment_form_unfiltered_html_nonce');
                                        comment_form( $args );
                                        add_action( 'comment_form', 'wp_comment_form_unfiltered_html_nonce');
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>

    </div>
</div>

