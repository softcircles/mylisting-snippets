add_filter( 'pre_get_document_title', function( $title ) {

    global $post;

    if ( is_singular( 'job_listing' ) && $listing = new \MyListing\Src\Listing( $post ) ) {

        $listing_type = $listing->type();

        $listing_type_name = $listing_type->get_name();

        if ( empty( $listing_type_name ) ) {
            return $title;
        }

        $wpseo_titles = get_option('wpseo_titles');

        $sep_options = WPSEO_Option_Titles::get_instance()->get_separator_options();

        if ( isset( $wpseo_titles['separator'] ) && isset( $sep_options[ $wpseo_titles['separator'] ] ) ) {
            $sep = $sep_options[ $wpseo_titles['separator'] ];
        } else {
            $sep = '-'; //setting default separator if Admin didn't set it from backed
        }

        $site_title = get_bloginfo('name');

        $listing_name = $listing->get_name();

        return sprintf( '%s %s Thoroughbred %s for sale %s %s', $listing_name, $sep, $listing_type_name, $sep, $site_title );
    }

    return $title;
}, 99 );
