jQuery(document).ready(function( $ ) {
    var width = window.innerWidth;

    if ( $('section.contact-map .c27-map.mapboxgl-map').length ) {
        var mapZoom = setInterval( function() {
            if ( MyListing.Maps.instances.length ) {
                if (width <= 1200){
                    MyListing.Maps.options["draggable"] = false;
                    MyListing.Maps.instances.forEach( ( instance ) => {
                        instance.map.doubleClickZoom.enable();
                        instance.map.dragPan.disable();
                    });
                    clearInterval(mapZoom);
                } else {
                    MyListing.Maps.options["draggable"] = true;
                    MyListing.Maps.instances.forEach( ( instance ) => {
                        instance.map.doubleClickZoom.disable();
                        instance.map.scrollZoom.enable();
                    });
                    clearInterval(mapZoom);
                }
            }
        }, 500 );
    }
});
