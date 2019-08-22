add_action( 'pre_get_document_title', function() {

    $taxonomies = [
        [ 'taxonomy' => 'job_listing_category', 'query_var' => 'explore_category', 'name_filter' => 'single_cat_title' ],
        [ 'taxonomy' => 'case27_job_listing_tags', 'query_var' => 'explore_tag', 'name_filter' => 'single_tag_title' ],
        [ 'taxonomy' => 'region', 'query_var' => 'explore_region' ],
    ];

    foreach( mylisting_custom_taxonomies() as $key => $label ) {
        $taxonomies[] = [
            'taxonomy' => $key,
            'query_var' => 'explore_'.$key,
        ];
    }

    foreach ( $taxonomies as $tax ) {

        if ( ! get_query_var( $tax['query_var'] ) ) {
            continue;
        }

        $term = \MyListing\Src\Term::get_by_slug( get_query_var( $tax['query_var'] ), $tax['taxonomy'] );

        if ( ! $term ) {
            continue;
        }

        /* we're on single listing term page */
        $image = $term->get_image();
        $page_title = apply_filters( isset( $tax['name_filter'] ) ? $tax['name_filter'] : 'single_term_title', $term->get_name() );
        $page_title .= ' ' . apply_filters( 'document_title_separator', '-' ) . ' ';
        $page_title .= get_bloginfo( 'name', 'display' );
        $page_title = capital_P_dangit( esc_html( convert_chars( wptexturize( $page_title ) ) ) );

        $cfg = new \stdClass;
        $cfg->title = $page_title;
        $cfg->description = $term->get_description();
        $cfg->link = $term->get_link();
        $cfg->image = is_array( $image ) && ! empty( $image ) ? $image['sizes']['medium_large'] : false;

        add_filter( 'pre_get_document_title', function() use ( $cfg ) { return $cfg->title; }, 10e3 );

        add_filter( 'seopress_social_og_title', function() use ( $cfg ) { 
            return sprintf( '<meta property="og:title" content="%s"/>'."\n", esc_attr( $cfg->title ) );
        }, 10e3 );

        add_filter( 'seopress_titles_canonical', function() use ( $cfg ) {
            return sprintf( '<link rel="canonical" href="%s"/>'."\n", esc_attr( $cfg->link ) );
        } );

        add_filter( 'seopress_social_twitter_card_title', function() use ( $cfg ) {
            return sprintf( '<meta name="twitter:title" content="%s"/>'."\n", esc_attr( $cfg->title ) );
        } );
    }
} );
