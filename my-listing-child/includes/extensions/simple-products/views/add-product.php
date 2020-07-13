<?php

$product = false;
$wc_product = false;
if (isset($_GET['product_id']) && $_GET['product_id']) {
	$product_id = absint((int) $_GET['product_id']);

	$product_query = get_posts([
		'author' => get_current_user_id(),
		'post_type' => 'product',
		'post_status' => ['publish', 'pending'],
		'include' => [$product_id],
		]);

	if (!empty($product_query)) {
		$product = $product_query[0];
		$product_meta = get_post_meta($product->ID);
		$wc_product = new WC_Product( $product->ID );
	}
}

$form_data = [
	'product_name' => $product ? $product->post_title : (isset($_POST['product_name']) ? $_POST['product_name'] : ''),
	'product_description' => $product ? $product->post_content : (isset($_POST['product_description']) ? $_POST['product_description'] : ''),
	'product_excerpt' => $product ? $product->post_excerpt : (isset($_POST['product_excerpt']) ? $_POST['product_excerpt'] : ''),
	'product_featured_image' => $product ? c27()->featured_image($product->ID, 'thumbnail') : false,

	'_regular_price' => $product ? $product_meta['_regular_price'][0] : (isset($_POST['_regular_price']) ? $_POST['_regular_price'] : ''),
	'_sale_price' => $product ? $product_meta['_sale_price'][0] : (isset($_POST['_sale_price']) ? $_POST['_sale_price'] : ''),
	'_sale_price_dates_from' => $product ? $product_meta['_sale_price_dates_from'][0] : (isset($_POST['_sale_price_dates_from']) ? $_POST['_sale_price_dates_from'] : ''),
	'_sale_price_dates_to' => $product ? $product_meta['_sale_price_dates_to'][0] : (isset($_POST['_sale_price_dates_to']) ? $_POST['_sale_price_dates_to'] : ''),

	'_manage_stock' => $product ? $product_meta['_manage_stock'][0] : (isset($_POST['_manage_stock']) ? $_POST['_manage_stock'] : ''),
	'_stock' => $product ? $product_meta['_stock'][0] : (isset($_POST['_stock']) ? $_POST['_stock'] : ''),
	'_backorders' => $product ? $product_meta['_backorders'][0] : (isset($_POST['_backorders']) ? $_POST['_backorders'] : 'no'),
	'_stock_status' => $product ? $product_meta['_stock_status'][0] : (isset($_POST['_stock_status']) ? $_POST['_stock_status'] : 'instock'),
	'_virtual' => $product ? $product_meta['_virtual'][0] : (isset($_POST['_virtual']) ? $_POST['_virtual'] : ''),

	'product_shipping_class' => (isset($_POST['product_shipping_class']) ? $_POST['product_shipping_class'] : ''),
	'product_cat' => $product ? array_column( wp_get_post_terms($product->ID, 'product_cat'), 'term_id' ) : (isset($_POST['product_cat']) ? $_POST['product_cat'] : []),
	'product_tag' => $product ? array_column( wp_get_post_terms($product->ID, 'product_tag'), 'term_id' ) : (isset($_POST['product_tag']) ? $_POST['product_tag'] : []),
];

do_action( 'case27_woocommerce_account_add_product_before', [
	'title' => ($product ? sprintf( __( 'Edit product "%s"', 'my-listing' ), $product->post_title) : __( 'Add a Product', 'my-listing' )),
	]);
?>

<h4 class="woocommerce-heading">
	<?php _e( 'On submission, product will go on pending status until it\'s reviewed and approved by the site admins.', 'my-listing' ) ?>
</h4>

<form action="" method="POST" enctype="multipart/form-data" encoding="multipart/form-data" class="c27-add-product-form">
	<div class="form-group">
		<label><?php _e( 'Product Name', 'my-listing' ) ?> <span class="required">*</span></label>
		<input type="text" name="product_name" value="<?php echo esc_attr( $form_data['product_name'] ) ?>">
	</div>

	<div class="form-group">
		<label><?php _e( 'Product Description', 'my-listing' ) ?> <span class="required">*</span></label>
		<textarea name="product_description" rows="5"><?php echo esc_attr( $form_data['product_description'] ) ?></textarea>
	</div>

	<div class="form-group">
		<label><?php _e( 'Product Excerpt', 'my-listing' ) ?> </label>
		<textarea name="product_excerpt" rows="3"><?php echo esc_attr( $form_data['product_excerpt'] ) ?></textarea>
	</div>

	<div class="form-group">
		<label><?php _e( 'Featured Image', 'my-listing' ) ?></label>
		<input type="file" name="product_featured_image" id="product_featured_image">

		<?php if ($form_data['product_featured_image']): ?>
			<br><label><?php _e( 'Current Featured Image', 'my-listing' ) ?></label><br>
			<img src="<?php echo esc_attr( $form_data['product_featured_image'] ) ?>" alt="<?php esc_attr_e( 'Product Featured Image', 'my-listing' ) ?>">
		<?php endif ?>
	</div>

	<div class="form-group">
		<label><?php _e( 'Gallery Images', 'my-listing' ) ?></label>
		<input type="file" name="product_gallery_images[]" id="product_gallery_images" multiple="multiple">

		<?php if ( $wc_product && $wc_product->get_gallery_image_ids() ): ?>
			<br><label><?php _e( 'Current Gallery Images', 'my-listing' ) ?></label><br>
			<?php foreach ((array) $wc_product->get_gallery_image_ids() as $gallery_image_id):
				$gallery_image = wp_get_attachment_image_src( $gallery_image_id, 'thumbnail' );
				if ( ! $gallery_image ) continue; ?>
				<img src="<?php echo esc_url( $gallery_image[0] ) ?>" alt="<?php esc_attr_e( 'Gallery Image', 'my-listing' ) ?>">
			<?php endforeach ?>
		<?php endif ?>
	</div>

	<div class="form-group">
		<label><?php _e( 'Price', 'my-listing' ) ?> <span class="required">*</span></label>
		<input type="number" step="any" min="0" name="_regular_price" value="<?php echo esc_attr( $form_data['_regular_price'] ) ?>">
	</div>

	<div class="form-group">
		<label><?php _e( 'Sale Price', 'my-listing' ) ?></label>
		<input type="number" step="any" min="0" name="_sale_price" id="_sale_price" value="<?php echo esc_attr( $form_data['_sale_price'] ) ?>">
	</div>

	<div class="form-group _sale_price_dates_from__wrapper hide">
		<label><?php _e( 'Sale From', 'my-listing' ) ?></label>
		<input type="text" name="_sale_price_dates_from" id="_sale_price_dates_from" value="<?php echo esc_attr( $form_data['_sale_price_dates_from'] ) ?>">
	</div>

	<div class="form-group _sale_price_dates_to__wrapper hide">
		<label><?php _e( 'Sale To', 'my-listing' ) ?></label>
		<input type="text" name="_sale_price_dates_to" value="<?php echo esc_attr( $form_data['_sale_price_dates_to'] ) ?>">
	</div>

	<div class="form-group">
		<div class="md-checkbox">
			<input id="_manage_stock" type="checkbox" name="_manage_stock" value="yes" <?php echo $form_data['_manage_stock'] == 'yes' ? 'checked="checked"' : '' ?>>
			<label for="_manage_stock" class=""><?php _e( 'Manage Stock', 'my-listing' ) ?></label>
		</div>
	</div>

	<div class="form-group _stock__wrapper">
		<label><?php _e( 'Stock Quantity', 'my-listing' ) ?></label>
		<input type="number" name="_stock" value="<?php echo esc_attr( $form_data['_stock'] ) ?>">
	</div>

	<div class="form-group _backorders__wrapper">
		<label><?php _e( 'Allow Backorders?', 'my-listing' ) ?></label>
		<select name="_backorders" class="custom-select">
			<option value="no" <?php echo $form_data['_backorders'] == 'no' ? 'selected="selected"' : '' ?>><?php _e( 'Do not allow', 'my-listing' ) ?></option>
			<option value="notify" <?php echo $form_data['_backorders'] == 'notify' ? 'selected="selected"' : '' ?>><?php _e( 'Allow, but notify customer', 'my-listing' ) ?></option>
			<option value="yes" <?php echo $form_data['_backorders'] == 'yes' ? 'selected="selected"' : '' ?>><?php _e( 'Allow', 'my-listing' ) ?></option>
		</select>
	</div>

	<div class="form-group">
		<label><?php _e( 'Stock Status', 'my-listing' ) ?></label>
		<select name="_stock_status" class="custom-select">
			<option value="instock" <?php echo $form_data['_stock_status'] == 'instock' ? 'selected="selected"' : '' ?>><?php _e( 'In stock', 'my-listing' ) ?></option>
			<option value="outofstock"<?php echo $form_data['_stock_status'] == 'outofstock' ? 'selected="selected"' : '' ?>><?php _e( 'Out of stock', 'my-listing' ) ?></option>
		</select>
	</div>

	<div class="form-group">
		<?php $terms = get_terms('product_cat', ['hide_empty' => false]) ?>
		<label><?php _e( 'Categories', 'my-listing' ) ?></label>
		<select name="product_cat[]" multiple="multiple" class="form-control custom-select">
			<?php foreach ((array) $terms as $term): ?>
				<option value="<?php echo esc_attr( $term->term_id ) ?>" <?php echo in_array($term->term_id, $form_data['product_cat']) ? 'selected="selected"' : '' ?>>
					<?php echo esc_html( $term->name ) ?>
				</option>
			<?php endforeach ?>
		</select>
	</div>

	<div class="form-group">
		<?php $terms = get_terms('product_tag', ['hide_empty' => false]) ?>
		<label><?php _e( 'Tags', 'my-listing' ) ?></label>
		<select name="product_tag[]" multiple="multiple" class="form-control custom-select">
			<?php foreach ((array) $terms as $term): ?>
				<option value="<?php echo esc_attr( $term->term_id ) ?>" <?php echo in_array($term->term_id, $form_data['product_tag']) ? 'selected="selected"' : '' ?>>
					<?php echo esc_html( $term->name ) ?>
				</option>
			<?php endforeach ?>
		</select>
	</div>

	<div class="form-group">
		<div class="md-checkbox">
			<input id="_virtual" type="checkbox" name="_virtual" value="yes" <?php echo $form_data['_virtual'] == 'yes' ? 'checked="checked"' : '' ?>>
			<label for="_virtual" class=""><?php _e( 'Virtual?', 'my-listing' ) ?></label>
		</div>
	</div>

	<div class="form-group product_shipping_class_wrapper">
		<?php $terms = get_terms('product_shipping_class', ['hide_empty' => false]) ?>
		<label><?php _e( 'Shipping classes', 'my-listing' ) ?></label>
		<select name="product_shipping_class" class="form-control custom-select">
			<option value=""><?php _e( 'No shipping class', 'my-listing' ) ?></option>
			<?php foreach ((array) $terms as $term): ?>
				<option value="<?php echo esc_attr( $term->term_id ) ?>" <?php echo $form_data['product_shipping_class'] == $term->term_id ? 'selected="selected"' : '' ?>>
					<?php echo esc_html( $term->name ) ?>
				</option>
			<?php endforeach ?>
		</select>
	</div>

	<div class="form-group">
		<?php wp_nonce_field('c27_add_product'); ?>
		<button type="submit" name="c27_add_product" class="buttons button-2 full-width button-animated">
			<?php _e( 'Submit', 'my-listing' ) ?><i class="material-icons">keyboard_arrow_right</i>
		</button>
		<input type="hidden" name="action" value="c27_add_product">
		<?php if ($product): ?>
			<input type="hidden" name="c27_edit_product" value="<?php echo esc_attr( $product->ID ) ?>">
		<?php endif ?>
	</div>
</form>

<?php do_action( 'case27_woocommerce_account_add_product_after' ) ?>
