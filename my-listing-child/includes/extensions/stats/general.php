<?php

namespace MyListing\Ext\Stats;

class General {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		add_filter( 'mylisting/stats/user', [ $this, 'set_count_stats' ], 10, 2 );
		add_filter( 'mylisting/stats/user', [ $this, 'set_promotion_stats' ], 10, 2 );
		add_filter( 'mylisting/stats/user', [ $this, 'set_track_stats' ], 10, 2 );
		add_filter( 'mylisting/stats/admin', [ $this, 'set_admin_count_stats' ], 10, 2 );
		add_filter( 'mylisting/stats/admin', [ $this, 'set_track_stats' ], 10, 2 );
	}

	public function set_count_stats( $stats, $user_id ) {
		if ( ! isset( $stats['listings'] ) ) {
			$stats['listings'] = [];
		}

		$stats['listings']['published'] = $this->query_listing_count( $user_id, 'publish' );
		$stats['listings']['pending_approval'] = $this->query_listing_count( $user_id, 'pending' );
		$stats['listings']['pending_payment'] = $this->query_listing_count( $user_id, 'pending_payment' );
		$stats['listings']['preview'] = $this->query_listing_count( $user_id, 'preview' );
		$stats['listings']['expired'] = $this->query_listing_count( $user_id, 'expired' );
		$stats['listings']['draft'] = $this->query_listing_count( $user_id, 'draft' );
		$stats['listings']['unpublish'] = $this->query_listing_count( $user_id, 'unpublish' );
		$stats['listings']['pending'] = ( absint( $stats['listings']['pending_approval'] ) ?: 0 ) + ( absint( $stats['listings']['pending_payment'] ) ?: 0 );

		return $stats;
	}

	public function set_admin_count_stats( $stats, $user_id = false ) {
		if ( ! isset( $stats['listings'] ) ) {
			$stats['listings'] = [];
		}

		$stats['listings']['published'] = $this->query_listing_count( $user_id, 'publish' );
		$stats['listings']['pending_approval'] = $this->query_listing_count( $user_id, 'pending' );
		$stats['listings']['pending_payment'] = $this->query_listing_count( $user_id, 'pending_payment' );
		$stats['listings']['preview'] = $this->query_listing_count( $user_id, 'preview' );
		$stats['listings']['expired'] = $this->query_listing_count( $user_id, 'expired' );
		$stats['listings']['draft'] = $this->query_listing_count( $user_id, 'draft' );
		$stats['listings']['unpublish'] = $this->query_listing_count( $user_id, 'unpublish' );
		$stats['listings']['pending'] = ( absint( $stats['listings']['pending_approval'] ) ?: 0 ) + ( absint( $stats['listings']['pending_payment'] ) ?: 0 );

		return $stats;
	}

	public function set_promotion_stats( $stats, $user_id ) {
		if ( ! isset( $stats['promotions'] ) ) {
			$stats['promotions'] = [];
		}

		$stats['promotions']['count'] = $this->query_promotion_count( $user_id );
		return $stats;
	}

	public function set_track_stats( $stats, $user_id = false ) {
	    global $wpdb;

	    $query_args = array(
	        'meta_key' => '__track_stats',
	    );

	    $where = '';

	    if ( $user_id ) {
	        $where .= $wpdb->prepare( " AND posts.post_author = %d", $user_id );
	    }

	    $results = $wpdb->get_results(
	        $wpdb->prepare(
	            "
	            SELECT pm1.meta_value AS tracks, posts.ID AS listing_id, pm2.meta_value AS listing_type
	                FROM {$wpdb->postmeta} AS pm1
	                LEFT JOIN {$wpdb->posts} AS posts ON pm1.post_id = posts.ID
	                LEFT JOIN {$wpdb->postmeta} AS pm2 ON (
	                    posts.ID = pm2.post_id and pm2.meta_key = '_case27_listing_type'
	                )
	            WHERE pm1.meta_key = %s
	            AND posts.post_type = 'job_listing'
	            AND posts.post_status = 'publish'
	            {$where}
	            ",
	            $query_args['meta_key'] // Add an additional placeholder for the meta_key argument
	        ),
	        ARRAY_A
	    );

	    $list = [];
	    foreach ( $results as $result ) {
	        $listing_type = \MyListing\Src\Listing_Type::get_by_name( $result['listing_type'] );
	        if ( ! $listing_type ) {
	            continue;
	        }

	        if ( ! isset( $list[ $listing_type->get_slug() ] ) ) {
	            $list[ $listing_type->get_slug() ] = [
	                'label' => $listing_type->get_plural_name(),
	                'tracks' => [],
	            ];
	        }

	        $tracks = \MyListing\get_tracks(
	            $result['listing_id'] ?? null,
	            (array) json_decode( $result['tracks'] ?? '', ARRAY_A )
	        );

	        if ( ! empty( $tracks ) ) {
	            foreach ( $tracks as $id => $track ) {
	                if ( ! isset( $list[ $listing_type->get_slug() ]['tracks'][ $id ] ) ) {
	                    $list[ $listing_type->get_slug() ]['tracks'][ $id ] = [
	                        'name' => $track['name'],
	                        'count' => 0,
	                    ];
	                }

	                $list[ $listing_type->get_slug() ]['tracks'][ $id ]['count'] += $track['count'];
	            }
	        }
	    }

	    $stats['tracks'] = $list;
	    return $stats;
	}

	public function query_listing_count( $user_id, $status = 'publish' ) {
		 global $wpdb;

	    $where = "WHERE post_type = 'job_listing' AND post_status = %s";
	    $params = array($status);

	    if ($user_id) {
	        $where .= " AND post_author = %d";
	        $params[] = $user_id;
	    }

	    $sql = $wpdb->prepare("SELECT COUNT(*) AS count FROM {$wpdb->posts} $where", $params);

	    $sql = apply_filters( 'mylisting/stats/query-listing-count/sql', $sql, $user_id, $status );
	    $query = $wpdb->get_row( $sql, OBJECT );

	    return is_object( $query ) && ! empty( $query->count ) ? (int) $query->count : 0;
	}

	public function query_promotion_count( $user_id ) {
		global $wpdb;
		$sql = $wpdb->prepare( "
			SELECT COUNT( * ) AS count
			FROM {$wpdb->posts}
			INNER JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )
			WHERE
				post_type = 'cts_promo_package'
				AND post_status = 'publish'
				AND {$wpdb->postmeta}.meta_key = '_user_id'
				AND {$wpdb->postmeta}.meta_value = %d
		", $user_id );

		$query = $wpdb->get_row( $sql, OBJECT );

		return is_object( $query ) && ! empty( $query->count ) ? (int) $query->count : 0;
	}
}
