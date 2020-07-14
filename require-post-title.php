add_action( 'edit_form_advanced', function( $post ) {

	// List of post types that we want to require post titles for.
	$post_types = array( 'job_listing' );

	// If the current post is not one of the chosen post types, exit this function.
	if ( ! in_array( $post->post_type, $post_types ) ) {
		return;
	}

	?>
	<script type='text/javascript'>
		( function ( $ ) {	
			$( document ).ready( function () {
			//Require post title when adding/editing Project Summaries
			$( 'body' ).on( 'submit.edit-post', '#post', function () {
			// If the title isn't set
			if ( $( "#title" ).val().replace( / /g, '' ).length === 0 ) {
				// Show the alert
				if ( !$( "#title-required-msj" ).length ) {
					$( "#titlewrap" )
					.append( '<div id="title-required-msj"><em>Title is required.</em></div>' )
					.css({
						"padding": "5px",
						"margin": "5px 0",
						"background": "#ffebe8",
						"border": "1px solid #c00"
					});
				}
				// Hide the spinner
				$( '#major-publishing-actions .spinner' ).hide();
				// The buttons get "disabled" added to them on submit. Remove that class.
				$( '#major-publishing-actions' ).find( ':button, :submit, a.submitdelete, #post-preview' ).removeClass( 'disabled' );
				// Focus on the title field.
				$( "#title" ).focus();
				return false;
			}
		});
		});
		}( jQuery ) );
	</script>
	<?php 
} );
