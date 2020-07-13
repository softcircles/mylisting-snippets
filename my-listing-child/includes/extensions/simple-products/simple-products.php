<?php

namespace MyListing\Ext\Simple_Products;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Simple_Products {
	use \MyListing\Src\Traits\Instantiatable;

	public static function boot() {
		new self;
	}

	public function __construct() {
		// not enabled in theme options
		if ( ! get_option( 'options_product_vendors_enable' ) ) {
			return;
		}

		if ( ! class_exists( '\\WC_REST_Products_Controller' ) ) {
			return;
		}

		// User Dashboard Pages.
		$this->dashboard_pages();

		// Handle product insertion form.
		add_action( 'template_redirect', [ $this, 'add_product' ], 500 );
	}

	public function add_product() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) || ! is_user_logged_in() ) {
			return;
		}

		if ( empty( $_POST['action'] ) || 'c27_add_product' !== $_POST['action'] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'c27_add_product' ) ) {
			return;
		}

		// Check if it's an Edit request.
		$editing_product = false;
		if ( ! empty( $_POST['c27_edit_product'] ) && is_numeric( $_POST['c27_edit_product'] ) ) {
			$product_id = absint( $_POST['c27_edit_product'] );
			$product_query = get_posts( [
				'author' => get_current_user_id(),
				'post_type' => 'product',
				'post_status' => [ 'publish', 'pending' ],
				'include' => [ $product_id ],
			] );

			if ( ! empty( $product_query ) ) {
				$editing_product = $product_query[0];
			}
		}

		$errors = new \WP_Error();
		$required_fields = [
			'product_name' => __( 'Product Name', 'my-listing' ),
			'product_description' => __( 'Product Description', 'my-listing' ),
			'_regular_price' => __( 'Product Price', 'my-listing' ),
		];

		foreach ( $required_fields as $field_key => $field_name ) {
			if ( empty( $_POST[ $field_key ] ) ) {
				$errors->add( 'required_fields', sprintf( __( '%s is a required field.', 'my-listing' ), '<strong>' . esc_html( $field_name ) . '</strong>' ) );
			}
		}

		if ( ! is_numeric( $_POST['_regular_price'] ) ) {
			$errors->add( '_regular_price__not_numeric', __( 'Price must be numeric.', 'my-listing' ) );
		}

		// if ( ( empty( $_FILES['product_featured_image'] ) || empty( $_FILES['product_featured_image']['tmp_name'] ) ) && ! $editing_product ) {
		// 	$errors->add( 'product_featured_image', __( 'Featured Image is a required field.', 'my-listing' ) );
		// }

		if ( $errors->get_error_messages() ) {
			foreach ( $errors->get_error_messages() as $error ) {
				wc_add_notice( $error, 'error' );
			}
		}

		if ( wc_notice_count( 'error' ) !== 0 ) {
			return;
		}

		// Upload featured image.
		$update_featured_image = false;
		if ( ! empty( $_FILES['product_featured_image'] ) && ! empty( $_FILES['product_featured_image']['tmp_name'] ) ) {
			$featured_image = c27()->upload_file( $_FILES['product_featured_image'], [ 'image/jpeg', 'image/png' ] );
			$update_featured_image = true;
			if ( is_wp_error( $featured_image ) ) {
				wc_add_notice( $featured_image->get_error_message(), 'error' );
				return;
			}
		}

		// Upload gallery images.
		$update_gallery_images = false;
		if ( ! empty( $_FILES['product_gallery_images'] ) && ! empty( $_FILES['product_gallery_images']['tmp_name'] ) ) {
			$gallery_file_arr = [];
			$gallery_file_count = count( $_FILES['product_gallery_images']['name'] );
			$gallery_file_keys = array_keys( $_FILES['product_gallery_images'] );

			for ( $i = 0; $i < $gallery_file_count; $i++ ) {
				foreach ( $gallery_file_keys as $file_key ) {
					$gallery_file_arr[ $i ][ $file_key ] = $_FILES['product_gallery_images'][ $file_key ][ $i ];
				}
			}

			if ( ! empty( $gallery_file_arr ) ) {
				$gallery_images = [];

				foreach ($gallery_file_arr as $gallery_file) {
					$gallery_image = c27()->upload_file( $gallery_file, [ 'image/jpeg', 'image/png' ] );
					if ( ! is_wp_error( $gallery_image ) ) {
						$gallery_images[] = absint( $gallery_image );
					}
				}

				if ( ! empty( array_filter( $gallery_images ) ) ) {
					$update_gallery_images = true;
				}
			}
		}

		// Get fields.
		$product_name = ! empty( $_POST['product_name'] ) ? wc_clean( $_POST['product_name'] ) : '';
		$product_description = ! empty( $_POST['product_description'] ) ? wc_clean( $_POST['product_description'] ) : '';
		$product_excerpt = ! empty( $_POST['product_excerpt'] ) ? wc_clean( $_POST['product_excerpt'] ) : '';

		// Validity.
		$_backorders__valid = isset( $_POST['_backorders'] ) && in_array( $_POST['_backorders'], [ 'no', 'notify', 'yes' ] );
		$_stock_status__valid = isset( $_POST['_stock_status'] ) && in_array( $_POST['_stock_status'], [ 'instock', 'outofstock' ] );
		$_sale_price__valid = isset( $_POST['_sale_price'] ) && is_numeric( $_POST['_sale_price'] );

		// Terms.
		$tags = [];
		$categories = [];
		$shipping_class = [];

		$categories = isset($_POST['product_cat']) && is_array($_POST['product_cat']) ? array_map('absint', $_POST['product_cat']) : [];
		$tags = isset($_POST['product_tag']) && is_array($_POST['product_tag']) ? array_map('absint', $_POST['product_tag']) : [];
		$shipping_class = isset($_POST['product_shipping_class']) ? [ absint($_POST['product_shipping_class']) ] : [];

		$product_data = [
			'name' => $product_name,
			'type' => 'simple',
			'description' => $product_description,
			'short_description' => $product_excerpt,
			'status' => 'pending',

			'regular_price' => sanitize_text_field( $_POST['_regular_price'] ),
			'in_stock' => $_stock_status__valid ? $_POST['_stock_status'] == 'instock' : false,
			'sale_price' => $_sale_price__valid ? sanitize_text_field( $_POST['_sale_price'] ) : '',
			'manage_stock' => isset($_POST['_manage_stock']) && $_POST['_manage_stock'] == 'yes',
			'backorders' => $_backorders__valid ? sanitize_text_field($_POST['_backorders']) : 'no',
			'stock_quantity' => isset($_POST['_stock']) && is_numeric($_POST['_stock']) ? sanitize_text_field($_POST['_stock']) : '',
			'virtual' => isset($_POST['_virtual']) && $_POST['_virtual'] == 'yes',
			'meta_data' => [],
		];

		if ( $editing_product ) {
			$product_data['id'] = $editing_product->ID;
		}

		$product_controller = new \MyListing\Ext\Simple_Products\Product_Controller;
		if ( $product = $product_controller->c27_create_product( $product_data ) ) {
			if ( $update_featured_image ) {
				if ( $old_image_id = absint( $product->get_image_id() ) ) {
					wp_delete_attachment( $old_image_id, true );
				}

				$product->set_image_id( $featured_image );
			}

			if ( $update_gallery_images ) {
				if ( $old_gallery_image_ids = (array) $product->get_gallery_image_ids() ) {
					foreach ($old_gallery_image_ids as $old_gallery_image_id) {
						wp_delete_attachment( $old_gallery_image_id, true );
					}
				}

				$product->set_gallery_image_ids( $gallery_images );
			}

			$product->save();
			wp_update_post( [
				'ID' => $product->get_id(),
				'post_author' => get_current_user_id(),
			] );

			wp_set_object_terms( $product->get_id(), $categories, 'product_cat' );
			wp_set_object_terms( $product->get_id(), $tags, 'product_tag' );
			wp_set_object_terms( $product->get_id(), $shipping_class, 'product_shipping_class' );

			wc_add_notice( __( 'Product submitted successfully.', 'my-listing' ) );
			wp_safe_redirect( add_query_arg( 'product_id', $product->get_id(), wc_get_endpoint_url( 'add-product' ) ) );
			exit;
		}

		return false;
	}

	public function dashboard_pages() {
		// My Products page.
		\MyListing\add_dashboard_page( [
			'endpoint' => 'my-products',
			'title' => __( 'My Products', 'my-listing' ),
			'template' => locate_template( 'includes/extensions/simple-products/views/my-products.php' ),
			'show_in_menu' => true,
			'order' => 5,
		] );

		// Add a Product page.
		\MyListing\add_dashboard_page( [
			'endpoint' => 'add-product',
			'title' => __( 'Add a Product', 'my-listing' ),
			'template' => locate_template( 'includes/extensions/simple-products/views/add-product.php' ),
			'show_in_menu' => false,
		] );
	}
}
