<?php

function mylisting_comment_notification_text( $notify_message, $comment_id ){
    // get the current comment and post data
    $comment = get_comment( $comment_id );
    $post = get_post( $comment->comment_post_ID );
    
    // don't modify trackbacks or pingbacks
    if( '' == $comment->comment_type ){
        // build the new message text
        $notify_message = sprintf( __( 'New comment on your post "%s"' ), $post->post_title ) . "\r\n";
        $notify_message .= sprintf( __('Author : %1$s'), $comment->comment_author ) . "\r\n";
        $notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
        $notify_message .= sprintf( __('URL : %s'), $comment->comment_author_url ) . "\r\n";
        $notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
        $notify_message .= __('You can see all comments on this post here: ') . "\r\n";
        $notify_message .= get_permalink($comment->comment_post_ID) . "#comments\r\n\r\n";
        $notify_message .= sprintf( __('Permalink: %s'), get_comment_link( $comment_id ) ) . "\r\n";

        if ( user_can( $post->post_author, 'edit_comment', $comment_id ) ) {

            if ( EMPTY_TRASH_DAYS ) {
                $notify_message .= sprintf( __('Trash it: %s'), admin_url("comment.php?action=trash&c=$comment_id") ) . "\r\n";
            } else {
                $notify_message .= sprintf( __('Delete it: %s'), admin_url("comment.php?action=delete&c=$comment_id") ) . "\r\n";
            }

            $notify_message .= sprintf( __('Spam it: %s'), admin_url("comment.php?action=spam&c=$comment_id") ) . "\r\n";
        }
    }
        // return the notification text
    return $notify_message;
}

add_filter( 'comment_notification_text', 'mylisting_comment_notification_text', 20, 2 );
