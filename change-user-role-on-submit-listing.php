<?php

function update_roles()
{
 
   global $wpdb;
 
   // Get the author
   $author = wp_get_current_user();
   // Get post by author
   $posts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_author = " . $author->ID );
 
   $numPost = count($posts);
 
   // Do the checks to see if they have the roles and if not update them.
   if($numPost > 0 && current_user_can('subscriber'))
   {
       // Add role
       $author->add_role( 'editor' );
        
       // Remove role
       $author->remove_role( 'subscriber' );
 
   }
}
 
add_action('mylisting/submission/save-listing-data', 'update_roles');
