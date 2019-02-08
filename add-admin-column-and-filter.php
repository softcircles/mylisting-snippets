<?php

add_filter( 'manage_job_listing_posts_columns', 'manage_job_listing_columns' );

function manage_job_listing_columns( $columns ) {
    
    $columns['listing_type'] = __( 'Listing Type' );
    
    return $columns;
}

add_action( 'manage_job_listing_posts_custom_column', 'job_listing_admin_column', 10, 2);
function job_listing_admin_column( $column ) {
    global $post; 
    
    if ( 'listing_type' === $column ) {
       $listing = \MyListing\Src\Listing::get( $post );
        if ( ! $listing ) {
          _e( 'n/a' );  
        } else {
          echo esc_html( $listing->type->get_singular_name() );
        }
   }
}

add_action( 'restrict_manage_posts', 'job_listing_filter_by' );

function job_listing_filter_by() {
    $type = 'post';
    if (isset($_GET['post_type'])) {
        $type = $_GET['post_type'];
    }

    //only add filter to post type you want
    if ('job_listing' == $type ) {
        
        $query = get_posts( [
            'post_type'     => 'case27_listing_type',
            'post_status'   => 'publish',
            'posts_per_page'=> '25'
        ]);
        
        if ( $query ) : ?>
            <select name="filter_by_type">
            <option value=""><?php _e('Filter By Listing Types ', 'my-listing'); ?></option>
            <?php
                $current_v = isset($_GET['filter_by_type'])? $_GET['filter_by_type']:'';
                
                foreach ( $query as $listing_obj ) {

                    if ( empty( $listing_obj ) ) {
                        continue;
                    }
                    
                    printf(
                        '<option value="%s"%s>%s</option>',
                        $listing_obj->post_name,
                        $listing_obj->post_name == $current_v? ' selected="selected"':'',
                        $listing_obj->post_title
                    );
                }
            ?>
            </select>
        <?php
        endif;
    }
}
