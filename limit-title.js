jQuery(document).ready(function($) {
    $("#job_title").keyup(function() {
        var maxChars = 20;
        if ($(this).val().length > maxChars) {
            $(this).val($(this).val().substr(0, maxChars));
            
            //Take action, alert or whatever suits
            alert("This Title fild can take a maximum of 20 characters");
        }
    });
});
