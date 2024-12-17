<div class="container c27-listing-submitted-notice">
	<div class="row">
		<div class="col-md-10 col-md-push-1">

			<div class="element submit-l-message">
				<div class="pf-head">
					<div class="title-style-1">
						<h5>
							<i class="material-icons">check_circle_outline</i>
							<?php
							switch ( $listing->get_status() ) :
								case 'publish' :
									printf( __( 'Listing listed successfully. To view your listing <a href="%s">click here</a>.', 'my-listing' ), $listing->get_link() );
								break;
								case 'pending' :
									echo __( 'Listing submitted successfully. Your listing will be visible once approved.', 'my-listing' );
								break;
								case 'draft' :
									printf( __( 'Listing saved successfully. To view your listing <a href="%s">click here</a>.', 'my-listing' ), $listing->get_link() );
								break;
								default :
								break;
							endswitch;
							?>
						</h5>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	// prevent form resubmission
	if ( window.history.replaceState ) {
		window.history.replaceState( null, null, window.location.href );
	}
</script>
