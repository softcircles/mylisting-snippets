jQuery( document ).ready( function( $ ) {
    var collapsed_height = 32; // in px;
    $('#finderListings .results-view.grid .grid-item .details-list li:last-child').each( function () {
        var target = $( this ),
            desc = target.find('span'),
            readmoreLink = $('<a/>', {class: "toggle-more"}).text('Read more>>');

        if ( ! target.length || target.outerHeight() <= collapsed_height ) {
            return null;
        }

        // Add CSS properties
        desc.css({
            height: collapsed_height + 'px',
            overflow: 'hidden',
            display: 'inline-block'
        });

        readmoreLink.appendTo( target ).on('click', function ( e ) {
            e.preventDefault();
            if ( desc.hasClass('toggled') ) {
                desc.removeClass('toggled');
                desc.css( 'height', collapsed_height + 'px' );
                readmoreLink.text('Read more>>');
                return null;
            }

            desc.addClass('toggled');
            desc.css( 'height', 'auto' );
            readmoreLink.text('<<Read less');

            desc.addClass('toggled');
            desc.find('span').css( 'height', 'auto' );
            readmoreLink.text('<<Read less');
        });
    });
});
