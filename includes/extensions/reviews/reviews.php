<?php

namespace MyListing\Ext\Reviews;

/**
 * Rating Integrations
 *
 * @author C27
 * @since 1.0.0
 */

/**
 * Rating Reviews
 *
 * @since unknown
 */
class Reviews {

	public static function boot() {
		new self;
	}

	/**
	 * Class Constructor
	 *
	 * @since unknown
	 */
	public function __construct() {
		if ( is_admin() ) {
			Reviews_Admin::instance();
		}

		// Submit review.
		add_action( 'pre_comment_on_post', array( $this, 'action_pre_comment_on_post' ) );
		add_action( 'comment_post', array( $this, 'action_comment_post' ) );

		// Update review.
		add_action( "admin_post_update_review", array( $this, 'update_review' ) );

		// Delete review.
		add_action( 'trash_comment', array( $this, 'update_listing_rating_on_comment_delete' ) );
		add_action( 'delete_comment', array( $this, 'update_listing_rating_on_comment_delete' ) );

		// Change review status.
		add_action( "transition_comment_status", array( $this, 'update_listing_rating_on_comment_transition' ), 10, 3 );

		// Display rating categories after comment text.
		add_filter( 'comment_text', array( $this, 'display_review_html' ), 10, 3 );

		// Gallery action: on delete attachment and comment.
		add_action( 'delete_comment', array( $this, 'delete_gallery_on_delete_comment' ) );
		add_action( 'delete_attachment', array( $this, 'delete_gallery_data_on_delete_attachment' ) );
	}

	/* === FUNCTIONS === */

	/**
	 * Max Rating.
	 * Depends on Rating Mode. The options are 5 star or 10 star mode.
	 *
	 * @since 1.5.0
	 *
	 * @param int $listing_id Listing ID.
	 * @return int Valoid value is 5 or 10. 10 is default.
	 */
	public static function max_rating( $listing_id = null ) {
		$default = 10;

		if ( ! ( $listing = \MyListing\Src\Listing::get( $listing_id ) ) ) {
			return $default;
		}

		if ( ! $listing->type ) {
			return $default;
		}

		return 5 === intval( $listing->type->get_review_mode() ) ? 5 : 10;
	}

	/**
	 * Sanitize Rating
	 *
	 * @since 1.5.0
	 *
	 * @param int $rating Ratings.
	 * @return int
	 */
	public static function sanitize_rating( $rating ) {
		// Make sure it's numeric. Do not use absint() or intval(). It will round the value.
		$rating = is_numeric( $rating ) ? ( $rating + 0 ) : 0;

		// Need to be more than 1 and less than 10.
		if ( $rating && $rating >= 1 && $rating <= 10 ) {
			return $rating;
		}

		// Invalid. Set as zero.
		return 0;
	}

	/**
	 * Get comment rating. Only used in template to display single comment rating average.
	 *
	 * @since unknown
	 *
	 * @param int $comment_id Comment ID.
	 * @return int|false
	 */
	public static function get_rating( $comment_id ) {
		$comment = get_comment( $comment_id );
		$max_rating = self::max_rating( $comment->comment_post_ID );
		$rating = apply_filters( 'my_listing_review_rating', self::sanitize_rating( get_comment_meta( $comment_id, '_case27_post_rating', true ) ), $comment_id );
		return round( 5 === $max_rating ? $rating / 2 : $rating, 1 );
	}

	/**
	 * Get rating average post meta for faster rating output.
	 * If post meta not available, get via wpdb.
	 *
	 * @since unknown
	 *
	 * @param int $listing_id Listing Post ID.
	 * @return int
	 */
	public static function get_listing_rating_optimized( $listing_id ) {
		$rating = self::sanitize_rating( get_post_meta( $listing_id, '_case27_average_rating', true ) );
		$max_rating = self::max_rating( $listing_id );

		// No meta rating stored. Get from DB.
		if ( ! $rating ) {
			$rating = self::sanitize_rating( self::get_listing_rating( $listing_id ) );

			// Save it as post meta as cache.
			if ( $rating ) {
				update_post_meta( $listing_id, '_case27_average_rating', $rating );
			}
		}

		return round( 5 === $max_rating ? $rating / 2 : $rating, 1 );
	}

	/**
	 * Get rating average of a listing from DB.
	 *
	 * @since unknown
	 *
	 * @param int $listing_id Post ID.
	 * @return int
	 */
	public static function get_listing_rating( $listing_id ) {
		global $wpdb;

		$rating = (float) $wpdb->get_var( $wpdb->prepare("
			SELECT AVG(meta_value) AS avg_rating
			FROM $wpdb->commentmeta
			WHERE meta_key = '_case27_post_rating'
			AND comment_id IN (
				SELECT comment_id
				FROM $wpdb->comments
				WHERE comment_post_ID = %s
				AND comment_approved = 1
			)", $listing_id) );

		// Sanitize.
		$rating = self::sanitize_rating( $rating );

		// Count listing reviews.
		self::count_reviews( $listing_id );

		// Round it.
		if ( $rating ) {
			return round( $rating, 1 );
		}

		return 0;
	}

	/**
	 * Count the amount of listing reviews (first level comments),
	 * and store it in listing meta for more efficiency.
	 *
	 * @since 1.6.3
	 * @param int $listing_id Post ID.
	 */
	public static function count_reviews( $listing_id ) {
		global $wpdb;

		$count = (int) $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(comment_post_id) AS count
			FROM $wpdb->comments
			WHERE comment_approved = 1
			AND comment_post_ID = %s
			AND comment_parent = 0
		", $listing_id ) );

		if ( $count < 1 ) {
			$count = 0;
		}

		// Update listing meta.
		update_post_meta( $listing_id, '_case27_review_count', $count );

		return $count;
	}

	/**
	 * Check if user has reviewed a listing.
	 *
	 * @param int $user_id User ID.
	 * @param int $listing_id Listing ID.
	 *
	 * @return WP_Comment|false
	 */
	public static function has_user_reviewed( $user_id, $listing_id ) {
		// Bail, if no listing or user defined.
		if ( ! $listing_id || ! $user_id ) {
			return false;
		}

		// Query comment by user.
		$review = get_comments( array(
			'user_id' => $user_id,
			'post_id' => $listing_id,
			'parent'  => 0,
		) );

		return $review ? $review[0] : false;
	}

	/**
	 * Get Reviews Categories
	 *
	 * @since 1.5.0
	 *
	 * @param int $listing_id Post ID.
	 * @return array
	 */
	public static function get_review_categories( $listing_id ) {
		if ( ! ( $listing = \MyListing\Src\Listing::get( $listing_id ) ) ) {
			return [];
		}

		if ( ! $listing->type ) {
			return [];
		}

		return $listing->type->get_review_categories();
	}

	/**
	 * Is review gallery upload enabled.
	 *
	 * @since 1.5.0
	 *
	 * @param int $listing_id Post ID.
	 * @return bool
	 */
	public static function is_review_gallery_enabled( $listing_id ) {
		if ( ! ( $listing = \MyListing\Src\Listing::get( $listing_id ) ) ) {
			return false;
		}

		if ( ! $listing->type ) {
			return false;
		}

		return $listing->type->is_gallery_enabled();
	}

	/**
	 * Get Rating Fields HTML
	 *
	 * @since 1.5.0
	 *
	 * @param WP_Comment|false $comment False for new review.
	 * @param int|false        $post_id Listing ID.
	 * @return string
	 */
	public static function get_ratings_field( $comment = false, $post_id = false ) {
		global $case27_reviews_allow_rating;

		// Bail if rating not allowed.
		if ( ! isset( $case27_reviews_allow_rating ) || ! $case27_reviews_allow_rating ) {
			return '';
		}

		$categories = self::get_review_categories( $post_id ? $post_id : get_the_ID() );
		$ratings = array();
		if ( $comment ) {
			$ratings = get_comment_meta( $comment->comment_ID, '_case27_ratings', true );
			$ratings = is_array( $ratings ) ? $ratings : array();
		}

		// Rating options.
		$max_rating = Reviews::max_rating( $post_id );
		$rating_options = array(
			'1' => 1,
			'2' => 2,
			'3' => 3,
			'4' => 4,
			'5' => 5,
			'6' => 6,
			'7' => 7,
			'8' => 8,
			'9' => 9,
			'10' => 10,
		);
		if ( 5 === $max_rating ) {
			$rating_options = array(
				'1' => 2,
				'2' => 4,
				'3' => 6,
				'4' => 8,
				'5' => 10,
			);
		}
		$rating_options = array_reverse( $rating_options, true );

		ob_start();
		?>

		<?php if ( $categories ) : ?>
		<div class="form-group form-group-review-ratings <?php echo esc_attr( "rating-mode-{$max_rating}" );?>">
			<?php foreach( $categories as $rating => $category ) :
					$value = isset( $ratings[ $category['id'] ] ) ? self::sanitize_rating( $ratings[ $category['id'] ] ) : 0;

					// No custom rating category is set, transition from old data. use the average as "rating".
					if ( ! $value && 1 === count( $categories ) && 'rating' === $category['id'] && $comment ) {
						$value = absint( self::sanitize_rating( get_comment_meta( $comment->comment_ID, '_case27_post_rating', true ) ) );
					}

					// Rating mode 5 star.
					$value = 5 === $max_rating ? round( $value / 2 ) : $value;
			?>

				<div class="rating-category-field rating-category-field-<?php echo esc_attr( $category['id'] ); ?>">
					<div class="rating-category-label"><?php echo esc_html( $category['label'] ); ?> </div>

					<div class="rating-number form-group c27-rating-field">
						<p class="clasificacion">
							<?php foreach ( $rating_options as $k => $v ):
								$label_class = '';
								if ( $max_rating !== 5 ) {
									$label_class = ( $v % 2 === 0 ) ? 'right-half' : 'left-half';
								}
							?><input id="rating_<?php echo esc_attr( "{$category['id']}_{$v}" ); ?>" type="radio" name="<?php echo esc_attr( $category['id'] ); ?>_star_rating" value="<?php echo esc_attr( $v ); ?>" <?php checked( $k, $value );?>><!--
							 --><label for="rating_<?php echo esc_attr( "{$category['id']}_{$v}" ); ?>" class="<?php echo esc_attr( $label_class ) ?>"><i class="mi star_border <?php echo $max_rating === 5 && $v % 2 ?>"></i></label><!--
						 --><?php endforeach; ?>
						</p>
					</div><!-- .rating-number -->

				</div><!-- .rating-category-field -->

			<?php endforeach; ?>
		</div><!-- .form-group.form-group-review-ratings -->
		<?php endif; // End categories. ?>

		<?php
		return ob_get_clean();
	}

	/**
	 * Get Gallery Field
	 *
	 * @since 1.5.0
	 *
	 * @param WP_Comment|false $comment False for new review.
	 * @param int|false        $post_id Listing ID.
	 * @return string
	 */
	public static function get_gallery_field( $comment = false, $post_id = false ) {
		if ( ! Reviews::is_review_gallery_enabled( $post_id ) ) {
			return '';
		}

		ob_start();
		?>

		<div class="form-group form-group-review-gallery">

			<div class="review-gallery-label"><?php esc_html_e( 'Upload images', 'my-listing' ); ?></div>

			<div class="review-gallery-images">

				<label class="review-gallery-add"><i class="material-icons">file_upload</i><input id="review-gallery-add-input" class="review-gallery-input" name="review_gallery[]" multiple="multiple" type="file"></label>

				<?php if ( $comment ) : // Editing Review. ?>
					<?php
					$gallery = get_comment_meta( $comment->comment_ID, '_case27_review_gallery', false );
					$gallery = is_array( $gallery ) ? $gallery : array();
					?>

					<?php foreach ( $gallery as $attachment_id ) : ?>
						<div class="review-gallery-image">
							<?php echo wp_get_attachment_image( $attachment_id ); ?>
							<input type="hidden" name="review_gallery_ids[]" value="<?php echo esc_attr( $attachment_id ); ?>">
							<a class="review-gallery-image-remove" href="#"><i class="material-icons">delete</i></a>
						</div><!-- .review-gallery-image -->
					<?php endforeach; ?>

				<?php endif; ?>

				<div id="review-gallery-preview"></div>

			</div><!-- .review-gallery-images -->

		</div><!-- .form-group.form-group-review-gallery -->

		<?php
		return ob_get_clean();
	}

	/**
	 * Handle Uploads
	 *
	 * @since 1.5.0
	 *
	 * @param int $post_id    Listing ID.
	 * @param int $comment_id Review ID.
	 */
	public static function handle_uploads( $post_id, $comment_id ) {
		// Bail if not enabled or no image submitted.
		if ( ! self::is_review_gallery_enabled( $post_id ) || ! isset( $_FILES['review_gallery'] )  ) {
			return;
		}

		// Format multiple files into individual $_FILES data.
		$_files_gallery = $_FILES['review_gallery'];
		$files_data = array();
		if ( isset( $_files_gallery['name'] ) && is_array( $_files_gallery['name'] ) ) {
			$file_count = count( $_files_gallery['name'] );
			for ( $n = 0; $n < $file_count; $n++ ) {
				if( $_files_gallery['name'][$n] && $_files_gallery['type'][$n] && $_files_gallery['tmp_name'][$n] ){
					if( ! $_files_gallery['error'][$n] ){ // Check error.
						$type = wp_check_filetype( $_files_gallery['name'][$n] );

						// Only image allowed.
						if ( strpos( $type['type'], 'image' ) !== false ) {
							$files_data[] = array(
								'name'     => $_files_gallery['name'][$n],
								'type'     => $type['type'],
								'tmp_name' => $_files_gallery['tmp_name'][$n],
								'error'    => $_files_gallery['error'][$n],
								'size'     => filesize( $_files_gallery['tmp_name'][$n] ), // in byte.
							);
						}
					}
				}
			}
		} // end if().

		// Upload each file.
		foreach ( $files_data as $file_data ) {

			// Load WP Media.
			if ( ! function_exists( 'media_handle_upload' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
			}

			// Set files data to upload.
			$_FILES['review_gallery'] = $file_data;
			$attachment_id = media_handle_upload( 'review_gallery', $post_id );

			// Track using attachment/post meta.
			update_post_meta( $attachment_id, '_case27_review_gallery', $comment_id );

			// Track using comment meta (multiple).
			add_comment_meta( $comment_id, '_case27_review_gallery', $attachment_id, false );
		}
	}

	/* === SUBMIT REVIEW === */

	/**
	 * Prevent users from submitting multiple reviews.
	 *
	 * @since 1.0
	 */
	public function action_pre_comment_on_post( $listing_id ) {
		// if user is a guest or the comment is a reply, don't prevent the comment
		if ( ! is_user_logged_in() || ! empty( $_POST['comment_parent'] ) ) {
			return;
		}

		// make sure it's a listing post type review
		$listing = \MyListing\Src\Listing::get( $listing_id );
		if ( ! $listing || ! $listing->type || $listing->type->multiple_comments_allowed() ) {
			return;
		}

		// Show error message if user has already reviewed this listing.
		if ( self::has_user_reviewed( get_current_user_id(), $listing->get_id() ) ) {
			wp_die( wpautop( esc_html__( "You've already sumbitted a review on this listing.", 'my-listing' ) ), esc_html__( 'Comment Submission Failure', 'my-listing' ), array( 'back_link' => true ) );
		}

		if ( self::is_review_gallery_enabled( $listing_id ) && isset( $_FILES['review_gallery'] ) && empty( $_FILES['review_gallery']['name'][0] ) ) {
			wp_die( wpautop( esc_html__( "You need to upload atleast one image.", 'my-listing' ) ), esc_html__( 'Comment Submission Failure', 'my-listing' ), array( 'back_link' => true ) );
		}
	}

	/**
	 * Submit Comment/Review: Also store the rating.
	 *
	 * @since unknown
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function action_comment_post( $comment_id ) {
		// Do not process reply.
		if ( isset( $_POST['comment_parent'] ) && $_POST['comment_parent'] && absint( $_POST['comment_parent'] ) ) {
			return;
		}

		// Check post.
		if ( ! isset( $_POST['comment_post_ID'] ) || ! $_POST['comment_post_ID'] ) {
			return;
		}
		$post = get_post( $_POST['comment_post_ID'] );
		if ( ! $post || 'job_listing' !== $post->post_type ) {
			return;
		}
		$listing_id = $post->ID;

		// Ratings.
		$categories = self::get_review_categories( $listing_id );
		$ratings = array();
		$ratings_total = 0;
		if ( $categories ) {
			foreach( $categories as $id => $category ) {
				$submitted_rating = self::sanitize_rating( isset( $_POST[ $category['id'] . '_star_rating' ] ) ? intval( $_POST[ $category['id'] . '_star_rating' ] ) : 0 );
				if ( $submitted_rating ) {
					$ratings[ $category['id'] ] = $submitted_rating;
					$ratings_total += $submitted_rating;
				}
			}
		}
		if ( $ratings ) {
			update_comment_meta( $comment_id, '_case27_ratings', $ratings );
			update_comment_meta( $comment_id, '_case27_post_rating', self::sanitize_rating( $ratings_total / count( $ratings ) ) );
		} else {
			delete_comment_meta( $comment_id, '_case27_ratings' );
			delete_comment_meta( $comment_id, '_case27_post_rating' );
		}
		update_post_meta( $listing_id, '_case27_average_rating', self::get_listing_rating( $listing_id ) );

		// Gallery Upload.
		self::handle_uploads( $listing_id, $comment_id );

		do_action( 'mylisting/review-submitted', $comment_id, $listing_id );
	}

	/* === UPDATE REVIEW === */

	/**
	 * Update Review.
	 * This is triggered just for editing the review, not when submitting the initial review.
	 *
	 * @todo: need nonce to secure the form. also check if user logged in (maybe user caps if applicable).
	 *
	 * @since unknown
	 */
	public function update_review() {
		if ( ! is_user_logged_in() || ! isset( $_POST['comment'], $_POST['listing_id'] ) || ! $_POST['comment'] || ! $_POST['listing_id'] ) {
			return wp_die( '<p>' . __( 'Invalid request.', 'my-listing') . '</p>', __( 'Comment Submission Failure.', 'my-listing' ), array( 'back_link' => true ) );
		}

		// Check post/listing.
		$post = get_post( $_POST['listing_id'] );
		if ( ! $post || 'job_listing' !== $post->post_type ) {
			return wp_die( '<p>' . __( 'Invalid request.', 'my-listing') . '</p>', __( 'Comment Submission Failure.', 'my-listing' ), array( 'back_link' => true ) );
		}

		$listing_id = $post->ID;
		$comment_content = wp_kses_post( trim( $_POST['comment'] ) );
		$user_review = self::has_user_reviewed( get_current_user_id(), $listing_id );

		if ( ! $user_review ) {
			return wp_die( '<p>' . __( 'Invalid request.', 'my-listing') . '</p>', __( 'Comment Submission Failure.', 'my-listing' ), array( 'back_link' => true ) );
		}

		$comment_id = intval( $user_review->comment_ID );

		// Comment Content.
		wp_update_comment( array(
			'comment_ID'      => $comment_id,
			'comment_content' => $comment_content,
		) );

		// Ratings.
		$categories = self::get_review_categories( $listing_id );
		$ratings = array();
		$ratings_total = 0;
		if ( $categories ) {
			foreach( $categories as $id => $category ) {
				$submitted_rating = self::sanitize_rating( isset( $_POST[ $category['id'] . '_star_rating' ] ) ? intval( $_POST[ $category['id'] . '_star_rating' ] ) : 0 );
				if ( $submitted_rating ) {
					$ratings[ $category['id'] ] = $submitted_rating;
					$ratings_total += $submitted_rating;
				}
			}
		}
		if ( $ratings ) {
			update_comment_meta( $comment_id, '_case27_ratings', $ratings );
			update_comment_meta( $comment_id, '_case27_post_rating', self::sanitize_rating( $ratings_total / count( $ratings ) ) );
		} else {
			delete_comment_meta( $comment_id, '_case27_ratings' );
			delete_comment_meta( $comment_id, '_case27_post_rating' );
		}
		update_post_meta( $listing_id, '_case27_average_rating', self::get_listing_rating( $listing_id ) );

		// Uploaded image action.
		$gallery = get_comment_meta( $comment_id, '_case27_review_gallery', false );
		if ( $gallery && is_array( $gallery ) ) {

			// Re-index gallery.
			delete_comment_meta( $comment_id, '_case27_review_gallery' ); // Delete everything.
			$image_ids = isset( $_POST['review_gallery_ids'] ) && is_array( $_POST['review_gallery_ids'] ) ? $_POST['review_gallery_ids'] : array();
			if ( $image_ids ) {
				foreach( $image_ids as $id ) {
					add_comment_meta( $comment_id, '_case27_review_gallery', $id, false );
				}
			}

			// New gallery index.
			$new_gallery = get_comment_meta( $comment_id, '_case27_review_gallery', false );

			// Delete all attachment not in new index.
			foreach ( $gallery as $attachment_id ) {
				if ( ! in_array( $attachment_id, $new_gallery ) ) {

					// Check if the attachment is from this comment.
					$attachment_comment_id = intval( get_post_meta( $attachment_id, '_case27_review_gallery', true ) );
					if ( $attachment_comment_id === $comment_id ) {
						wp_delete_attachment( $attachment_id, true );
					}
				}
			}
		}

		// Gallery Upload.
		self::handle_uploads( $listing_id, $comment_id );

		delete_comment_meta( $comment->comment_ID, '_mylisting_notification_sent' );
		do_action( 'mylisting/review-updated', $comment_id, $listing_id );

		// Redirect back to listing page.
		if ( wp_get_referer() ) {
			return wp_safe_redirect( wp_get_referer() );
		}

		return wp_die( '<p>' . __( 'Your review has been updated.', 'my-listing') . '</p>', __( 'Success', 'my-listing' ), array( 'back_link' => true ) );
	}

	/* === DELETE REVIEW === */

	/**
	 * Delete review: Recalculate average rating.
	 *
	 * @since unknown
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function update_listing_rating_on_comment_delete( $comment_id ) {
		$comment = get_comment( $comment_id );
		if ( ! $comment || ( 'job_listing' !== get_post_type( $comment->comment_post_ID ) ) ) {
			return;
		}

		// Delete comment meta: Probably not needed.
		delete_comment_meta( $comment_id, '_case27_post_rating' );

		// Update post rating average.
		update_post_meta( $comment->comment_post_ID, '_case27_average_rating', self::get_listing_rating( $comment->comment_post_ID ) );
	}

	/* === UPDATE STATUS === */

	/**
	 * On Comment Status Change: Update/recalculate rating average.
	 *
	 * @since unknown
	 *
	 * @param string     $new_status New comment status.
	 * @param string     $old_status Old comment status.
	 * @param WP_Comment $comment    Comment object.
	 */
	public function update_listing_rating_on_comment_transition( $new_status, $old_status, $comment ) {
		if ( 'job_listing' !== get_post_type( $comment->comment_post_ID ) ) {
			return;
		}

		update_post_meta( $comment->comment_post_ID, '_case27_average_rating', self::get_listing_rating( $comment->comment_post_ID ) );
	}

	/* === DISPLAY OUTPUT === */

	/**
	 * Display Review HTML
	 *
	 * @since 1.5.0
	 *
	 * @param string $comment_text Comment text.
	 * @param WP_Comment $comment Comment object.
	 * @param array $args Comment text args.
	 * @return string
	 */
	public function display_review_html( $comment_text, $comment, $args = [] ) {
		if ( ! $comment || is_admin() ) {
			return $comment_text;
		}

		// Get post. Bail if not listing.
		$post = get_post( $comment->comment_post_ID );
		if ( !$post || 'job_listing' !== $post->post_type ) {
			return $comment_text;
		}

		// Gallery images.
		$gallery = get_comment_meta( $comment->comment_ID, '_case27_review_gallery', false );
		$gallery = is_array( $gallery ) ? $gallery : array();

		// Ratings.
		$categories = self::get_review_categories( $post->ID );
		$ratings = get_comment_meta( $comment->comment_ID, '_case27_ratings', true );
		$ratings = is_array( $ratings ) ? $ratings : array();

		// Only display category set in settings.
		foreach( $ratings as $rating => $value ) {
			if ( ! isset( $categories[ $rating ] ) || ! $value ) {
				unset( $ratings[ $rating ] );
			}
		}

		// No custom rating category is set, do not display rating in content.
		if ( $ratings && 1 === count( $categories ) && isset( $categories['rating'] ) ) {
			$ratings = array();
		}

		// Rating options.
		$max_rating = Reviews::max_rating( $post->ID );
		$rating_options = array(
			'1' => 1,
			'2' => 2,
			'3' => 3,
			'4' => 4,
			'5' => 5,
			'6' => 6,
			'7' => 7,
			'8' => 8,
			'9' => 9,
			'10' => 10,
		);
		if ( 5 === $max_rating ) {
			$rating_options = array(
				'1' => 2,
				'2' => 4,
				'3' => 6,
				'4' => 8,
				'5' => 10,
			);
		}
		$rating_options = array_reverse( $rating_options, true );

		add_filter( 'wp_get_attachment_image_attributes', [ $this, 'attachment_add_full_size_attribute' ], 50, 3 );

		ob_start();
		?>

		<?php if( $gallery ) : ?>
			<div class="review-galleries">
				<?php echo do_shortcode( '[gallery ids="' . implode( ',', $gallery ) . '" link="file"]' ); ?>
			</div><!-- .review-galleries -->
		<?php endif; ?>

		<?php if ( $ratings ) : ?>
		<div class="rating-categories <?php echo esc_attr( "rating-mode-{$max_rating}" );?>">
			<?php foreach ( $ratings as $rating => $value ) : ?>
				<div class="rating-category rating-number ratings-<?php echo esc_attr( $rating ); ?>">
					<div class="rating-category-label"><?php echo $categories[ $rating ]['label']; ?></div>
					<div class="clasificacion">
                        <?php mylisting_locate_template( 'partials/star-ratings.php', [
                            'rating' => self::sanitize_rating(  5 === $max_rating ? ( $value / 2 ) : $value, $max_rating ),
                            'max-rating' => $max_rating,
                            'class' => 'clasificacion',
                        ] ) ?>
					</div>
				</div><!-- .rating-category.rating-number -->
			<?php endforeach; ?>
		</div><!-- .rating-categories -->
		<?php endif; ?>

		<?php
		$html = ob_get_clean();

		remove_filter( 'wp_get_attachment_image_attributes', [ $this, 'attachment_add_full_size_attribute' ] );

		return $comment_text . $html;
	}

	public function attachment_add_full_size_attribute( $attr, $attachment, $size ) {
		if ( $full_size = wp_get_attachment_image_src( $attachment->ID, 'full' ) ) {
			$attr['data-full-size-src'] = $full_size[0];
			$attr['data-full-size-width'] = $full_size[1];
			$attr['data-full-size-height'] = $full_size[2];
		}

		return $attr;
	}

	/* === GALLERY ACTION === */

	/**
	 * Delete Review Gallery When Comment Deleted.
	 *
	 * @since 1.5.0
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function delete_gallery_on_delete_comment( $comment_id ) {
		$gallery = get_comment_meta( $comment_id, '_case27_review_gallery', false );
		if ( ! $gallery || ! is_array( $gallery ) ) {
			return false;
		}
		foreach ( $gallery as $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}
	}

	/**
	 * Delete Image Gallery ID in Comment When Attachment Deleted.
	 *
	 * @since 1.5.0
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function delete_gallery_data_on_delete_attachment( $attachment_id ) {
		$comment_id = get_post_meta( $attachment_id, '_case27_review_gallery', true );
		if ( ! $comment_id ) {
			return false;
		}
		delete_comment_meta( $comment_id, '_case27_review_gallery', $attachment_id );
	}
}
