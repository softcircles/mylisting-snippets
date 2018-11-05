jQuery(document).ready(function($) {

    var is_mobile = $( "body" ).attr( "data-elementor-device-mode" );

    if ( is_mobile == 'mobile' ) {
        if ( $('section.contact-map .c27-map.mapboxgl-map').length ) {
            var mapZoom = setInterval( function() {
                if ( MyListing.Maps.instances.length ) {
                    MyListing.Maps.instances.forEach( ( instance ) => {
                        instance.map.scrollZoom.disable();
                    });
                    clearInterval(mapZoom);
                }
            }, 500 );
        }
    }
});
