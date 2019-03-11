<?php
/**
 * Template for displaying regular Explore page with map.
 *
 * @var   $data
 * @var   $explore
 * @since 2.0
 */

$data['listing-wrap'] = 'col-md-12 grid-item';
?>
<div class="cts-explore finder-container fc-type-1 <?php echo esc_attr( $data['finder_columns'] ) ?> <?php echo $data['finder_columns'] == 'finder-three-columns' ? 'fc-type-1-no-map' : '' ?>" id="c27-explore-listings">
    <div class="mobile-explore-head">
        <a type="button" class="toggle-mobile-search" data-toggle="collapse" data-target="#finderSearch"><i class="material-icons sm-icon">sort</i><?php _e( 'Search Filters', 'my-listing' ) ?></a>
    </div>

    <?php if ( $data['types_template'] === 'topbar' ): ?>
        <?php require locate_template( 'templates/explore/partials/topbar.php' ) ?>
    <?php endif ?>

    <div class="<?php echo $data['template'] == 'explore-2' ? 'fc-one-column' : 'fc-default' ?>">
        <div class="finder-search" id="finderSearch" :class="( state.mobileTab === 'filters' ? '' : 'visible-lg' )">
            <div class="finder-tabs-wrapper">
                <?php require locate_template( 'templates/explore/partials/sidebar.php' ) ?>
            </div>
        </div>

        <div class="finder-listings" id="finderListings" :class="( state.mobileTab === 'results' ? '' : 'visible-lg' )">
            <div class="fl-head">
                <div class="col-xs-4 sort-results showing-filter" v-cloak>
                    <?php foreach ( $explore->store['listing_types'] as $type ): ?>
                        <?php require locate_template('partials/facets/order.php') ?>
                    <?php endforeach ?>
                </div>

                <div class="col-xs-4 text-center">
                    <span href="#" class="fl-results-no text-left" v-cloak>
                        <span></span>
                    </span>
                </div>

                <?php if ( $data['finder_columns'] != 'finder-three-columns' ): ?>
                    <div class="col-xs-4 map-toggle-button">
                        <a href="#" class=""><?php _e( 'Map view', 'my-listing' ) ?><i class="material-icons sm-icon">map</i></a>
                    </div>

                    <div class="col-xs-4 column-switch">
                        <a href="#" class="col-switch switch-one <?php echo $data['finder_columns'] == 'finder-one-columns' ? 'active' : '' ?>" data-no="finder-one-columns">
                            <i class="material-icons">view_stream</i>
                        </a>
                        <a href="#" class="col-switch switch-two <?php echo $data['finder_columns'] == 'finder-two-columns' ? 'active' : '' ?>" data-no="finder-two-columns">
                            <i class="material-icons">view_module</i>
                        </a>
                        <a href="#" class="col-switch switch-three <?php echo $data['finder_columns'] == 'finder-three-columns' ? 'active' : '' ?>" data-no="finder-three-columns">
                            <i class="material-icons">view_comfy</i>
                        </a>
                    </div>
                <?php endif ?>
            </div>
            <div class="results-view grid" v-show="!state.loading"></div>
            <div class="loader-bg" v-show="state.loading">
                <?php c27()->get_partial( 'spinner', [
                    'color' => '#777',
                    'classes' => 'center-vh',
                    'size' => 28,
                    'width' => 3,
                ] ) ?>
            </div>
            <div class="col-md-12 center-button pagination c27-explore-pagination" v-show="!state.loading"></div>
        </div>
    </div>

    <?php if ( $data['finder_columns'] != 'finder-three-columns' ): ?>
        <div class="finder-map" id="finderMap" :class="( state.mobileTab === 'map' ? '' : 'visible-lg' )">

            <div class="map c27-map mylisting-map-loading" id="<?php echo esc_attr( 'map__' . uniqid() ) ?>" data-options="<?php echo htmlspecialchars(json_encode([
                'skin' => $data['map_skin'],
                'scrollwheel' => $data['scroll_wheel'],
                'zoom' => 10,
                'minZoom' => 2,
                 'cluster_markers' => false,
            ]), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>
    <?php endif ?>

    <?php require locate_template( 'templates/explore/partials/mobile-nav.php' ) ?>
</div>
