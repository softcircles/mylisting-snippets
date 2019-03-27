<?php

add_action( 'wp_print_footer_scripts', 'check_job_description_length' );

function check_job_description_length() {  ?>

    <script type="text/javascript">

        var visual_editor_char_limit = 50;

        window.onload = function () {

            var visual = ( typeof tinyMCE != "undefined") && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden() ? true : false;

            if ( visual ) {

                jQuery('.mce-statusbar').append('<span class="char_count"></span>');

                var allowedKeys = [8, 37, 38, 39, 40, 46];
                tinyMCE.activeEditor.on('keydown', function( e ) {
                    editor_content = this.getContent().replace(/(<[a-zA-Z\/][^<>]*>|\[([^\]]+)\])|(\s+)/ig,'');

                    if (allowedKeys.indexOf(e.keyCode) != -1) return true;

                    console.log("tinymce editor current #chars: "+editor_content.length);

                    if (tinymce_getContentLength() + 1 > visual_editor_char_limit ) {
                        e.preventDefault();
                        e.stopPropagation();
                        alert("This Title fild can take a maximum of 50 characters");
                        return false;
                    }

                    return true;
                });

                tinyMCE.activeEditor.on('keyup', function (e) {
                    tinymce_updateCharCounter(this, tinymce_getContentLength());
                });
            }

            function tinymce_updateCharCounter(el, len) {
                jQuery('#' + el.id).prev().find('.char_count').text(len + '/' + visual_editor_char_limit);
            }

            function tinymce_getContentLength() {
                return tinymce.get(tinymce.activeEditor.id).contentDocument.body.innerText.length;
            }
        }
    </script>

    <?php

}
