<?php

/*
* GPS Address Update on add manually Cooradinates
*/

function load_wp_admin_scripts() {

    wp_add_inline_script( 'theme-script-main',
        "jQuery(document).ready(function($) {

            if ( $('.location-field-wrapper' ).length ) {

                var r = $('.latitude-input'),
                o = $('.longitude-input'),
                s = $('.address-input');

                $('.latitude-input, .longitude-input').on( 'change', function() {
                    var geocoder = new google.maps.Geocoder;
                    var latlng = {lat: parseFloat(r.val()), lng: parseFloat(o.val())};
                    geocoder.geocode({'location': latlng}, function(results, status) {
                        if ( status === 'OK') {
                            if (results[0]) {
                              s.val(results[0].formatted_address);
                            } else {
                              window.alert('No results found');
                            }
                        } else {
                            s.val('');
                            window.alert('Geocoder failed due to: ' + status);
                        }
                    });
                });
            }
        });"
    );
}

add_action( 'admin_enqueue_scripts', 'load_wp_admin_scripts', 99 );
