jQuery( document ).ready(function() {
    jQuery('.swatch-img').on('click', function(){
        var imageUrl = jQuery(this).attr('src');
        var imageUrlLength = imageUrl.length;

        var newImageUrl = imageUrl.substr(0, imageUrlLength-10)+'.jpg';
        
        //alert(newImageUrl+'.jpg');

        jQuery('.owl-item.active .item').attr('href', newImageUrl);
        jQuery('.owl-item.active .item img').attr('src', newImageUrl);

        var imageHeight = jQuery('.owl-item.active .item img').height();
        //alert(imageHeight);
        jQuery('.owl-height').height(imageHeight);
    });
});
