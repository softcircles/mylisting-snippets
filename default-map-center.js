jQuery(document).ready(function($) {
    if ( $('.finder-map .c27-map').length ) {
        var mapZoom = setInterval( function() {
            if ( MyListing.Maps.instances.length ) {
                MyListing.Maps.instances.forEach( ( instance ) => {
                    instance.map.setCenter(['-74.50', '40']); // starting position [long, lat]
                    instance.map.setZoom(15);
                });
                clearInterval(mapZoom);
            }
        }, 500 );
    }
});
