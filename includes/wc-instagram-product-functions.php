<?php
/**
 * Product functions
 *
 * @package WC_Instagram/Functions
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gets the hashtag for the specified product.
 *
 * @since 2.0.0
 *
 * @param int $product_id The product ID.
 * @return string|false The product hashtag. False otherwise.
 */
function wc_instagram_get_product_hashtag( $product_id ) {
	return get_post_meta( $product_id, '_instagram_hashtag', true );
}

/**
 * Gets the 'hashtag images' meta for the specified product.
 *
 * @since 2.0.0
 *
 * @param int $product_id The product ID.
 * @return array
 */
function wc_instagram_get_product_hashtag_images_meta( $product_id ) {
	$images = get_post_meta( $product_id, '_instagram_hashtag_images', true );

	if ( ! is_array( $images ) ) {
		$images = array();
	}

	return $images;
}

/**
 * Gets the transient name for the specified product and action.
 *
 * @since 2.0.0
 *
 * @param int    $product_id The product ID.
 * @param string $action     The related action.
 * @return string
 */
function wc_instagram_get_product_transient_name( $product_id, $action ) {
	$transient = "wc_instagram_product_{$action}_{$product_id}";

	/**
	 * Filters the transient name for the specified product and action.
	 *
	 * @since 2.0.0
	 *
	 * @param string $transient  The transient name.
	 * @param int    $product_id The product ID.
	 * @param string $action     The related action.
	 */
	return apply_filters( 'wc_instagram_get_product_transient_name', $transient, $product_id, $action );
}

/**
 * Sets the transient for the product hashtag images.
 *
 * @since 2.0.0
 *
 * @param int $product_id The product ID.
 */
function wc_instagram_set_product_hashtag_images_transient( $product_id ) {
	$transient = wc_instagram_get_product_transient_name( $product_id, 'hashtag_images' );

	$data = array(
		'hashtag' => wc_instagram_get_product_hashtag( $product_id ),
		'count'   => wc_instagram_get_images_number( 'product_hashtag' ),
	);

	/**
	 * Filters the data of the 'product hashtag images' transient.
	 *
	 * @since 2.0.0
	 *
	 * @param array $data The transient data.
	 * @param int $product_id The product ID.
	 */
	$data = apply_filters( 'wc_instagram_product_hashtag_images_transient_data', $data, $product_id );

	set_transient( $transient, $data, wc_instragram_get_transient_expiration_time( 'product_hashtag_images' ) );
}

/**
 * Gets if the product hashtag images are valid or not.
 *
 * True if the current images are valid. False if it's necessary to request new ones.
 *
 * @since 2.0.0
 *
 * @param int $product_id The product ID.
 * @return bool
 */
function wc_instagram_validate_product_hashtag_images( $product_id ) {
	$valid     = false;
	$transient = get_transient( wc_instagram_get_product_transient_name( $product_id, 'hashtag_images' ) );

	if (
		is_array( $transient ) && isset( $transient['hashtag'] ) && isset( $transient['count'] ) && // Transient not expired.
		wc_instagram_get_product_hashtag( $product_id ) === $transient['hashtag'] && // The hashtag matches.
		$transient['count'] >= wc_instagram_get_images_number( 'product_hashtag' ) // The count used is higher or equal than the current.
	) {
		$valid = true;
	}

	/**
	 * Filters if the product hashtag images are valid or not.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $valid      True if the images are valid. False otherwise.
	 * @param int  $product_id The product ID.
	 */
	return apply_filters( 'wc_instagram_validate_product_hashtag_images', $valid, $product_id );
}

/**
 * Gets the hashtag images for the specified product.
 *
 * @since 2.0.0
 *
 * @param int $product_id The product ID.
 * @return array
 */
function wc_instagram_get_product_hashtag_images( $product_id ) {
	// It's necessary to update the images.
	if ( ! wc_instagram_validate_product_hashtag_images( $product_id ) ) {
		wc_instagram_update_product_hashtag_images( $product_id );

		wc_instagram_set_product_hashtag_images_transient( $product_id );
	}

	$images = wc_instagram_get_product_hashtag_images_meta( $product_id );

	if ( ! empty( $images ) ) {
		$count = wc_instagram_get_images_number( 'product_hashtag' );

		// Only keep the $count most recent images.
		$min    = min( $count, count( $images ) );
		$images = array_slice( $images, 0, $min );
	}

	/**
	 * Filters the product hashtag images.
	 *
	 * @since 2.0.0
	 *
	 * @param array $images     The product hashtag images.
	 * @param int   $product_id The product ID.
	 */
	return apply_filters( 'wc_instagram_get_product_hashtag_images', $images, $product_id );
}

/**
 * Updates the hashtag images for the specified product.
 *
 * @since 2.0.0
 *
 * @param int $product_id The product ID.
 */
function wc_instagram_update_product_hashtag_images( $product_id ) {
	$hashtag = wc_instagram_get_product_hashtag( $product_id );

	if ( ! $hashtag ) {
		return;
	}

	$count = wc_instagram_get_images_number( 'product_hashtag' );

	$images = wc_instagram_get_hashtag_media(
		$hashtag,
		array(
			'edge'  => 'recent',
			'type'  => 'image',
			'count' => $count,
		)
	);

	if ( ! is_array( $images ) ) {
		$images = array();
	}

	/*
	 * The 'recent-media' edge only returns media objects published within 24 hours of query execution.
	 * We don't want to run out of images. So, if there is no enough images, we merge the new images
	 * with the older images and store the '$count' most recent.
	 */
	if ( count( $images ) < $count ) {
		$previous_images = wc_instagram_get_product_hashtag_images_meta( $product_id );

		// Merge the new images with the order images (New first).
		$images = array_merge( $images, $previous_images );

		// Remove duplicated images.
		$image_ids = array_unique( wp_list_pluck( $images, 'id' ) );
		$images    = array_intersect_key( $images, $image_ids );

		// Only keep the $count most recent images.
		$min    = min( $count, count( $images ) );
		$images = array_slice( $images, 0, $min );
	}

	// Store the images.
	update_post_meta( $product_id, '_instagram_hashtag_images', $images );
}

/**
 * Deletes the hashtag images for the specified product.
 *
 * @since 2.0.0
 *
 * @param int $product_id The product ID.
 */
function wc_instagram_delete_product_hashtag_images( $product_id ) {
	// Delete product meta.
	delete_post_meta( $product_id, '_instagram_hashtag_images' );

	// Delete transient.
	delete_transient( wc_instagram_get_product_transient_name( $product_id, 'hashtag_images' ) );
}
