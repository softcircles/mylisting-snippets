<?php

add_action('pre_get_posts','alter_query');

function alter_query($query){
    if ($query->is_main_query() && is_blog() ) {
        $query->set('orderby', 'rand'); //Set the order to random
    }
}

function is_blog(){
    if ( is_front_page() && is_home() ) {
        return false;
    } elseif ( is_front_page() ) {
        return false;
    } elseif ( is_home() ) {
        return get_option( 'page_for_posts' ); // Returns blog page ID
    } else {
        return false;
    }
}
