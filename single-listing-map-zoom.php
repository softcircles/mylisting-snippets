<?php

/*
* Change Single Listing Map Zoom 
*/

jQuery(document).ready(function($) {
    if ( $('.single-listing .map-block').length ) {
        var mapZoom = setInterval( function() {
            if ( MyListing.Maps.instances.length ) {
                MyListing.Maps.instances[0].map.setZoom(17);
                clearInterval(mapZoom);
            }
        }, 500 );
    }
});
