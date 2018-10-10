jQuery(document).ready(function($) {
    $('<input type="checkbox" id="select_all" />Select all').appendTo('.uitgebreid');
    $('#select_all').on('click', ( e ) => {
        e.stopPropagation();
        $('#search-form .checkboxes-filter').find('input[type=checkbox]').attr("checked", $( e.currentTarget ).is(':checked') );
    });
});
