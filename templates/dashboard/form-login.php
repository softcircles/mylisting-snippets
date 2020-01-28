<section class="i-section no-modal">
	<div class="container">
		<div class="row">
			<div class="col-md-8 col-md-offset-2">
				<?php wc_print_notices(); ?>
				<?php if ( ! empty( $_GET['notice'] ) ): ?>
					<?php if ( $_GET['notice'] == 'login-required-claim' ): ?>
						<?php wc_print_notice( __( sprintf( "You must login in order to claim %s listing", \MyListing\Src\Listing::get( substr($_GET['redirect_to'], strpos( $_GET['redirect_to'], '=') + 1 ) )->get_name() ) , 'my-listing' ), 'notice' ); ?>

					<?php elseif ( $_GET['notice'] == 'login-required' ): ?>
						<?php wc_print_notice( __( 'You must be logged in to perform this action.', 'my-listing' ), 'notice' ); ?>
					<?php endif ?>
				<?php endif ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4  col-md-offset-2 <?php echo ! $show_register_form ? 'col-md-push-2' : '' ?>">
				<?php c27()->get_partial('account/login-form') ?>
			</div>

			<?php if ( $show_register_form ): ?>
				<div class="col-md-4">
					<?php c27()->get_partial('account/register-form') ?>
				</div>
			<?php endif ?>
		</div>
	</div>
</section>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
