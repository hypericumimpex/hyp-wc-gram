<?php
/**
 * Useful functions for the plugin
 *
 * @package WC_Instagram/Functions
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

// Include core functions.
require 'wc-instagram-compatibility-functions.php';
require 'wc-instagram-api-functions.php';
require 'wc-instagram-product-functions.php';

/**
 * Gets a string (hash) that uniquely identifies the specified data.
 *
 * @since 2.0.0
 *
 * @param mixed $data The data used to generate the hash.
 * @return string
 */
function wc_instagram_get_hash( $data ) {
	if ( is_array( $data ) || is_object( $data ) ) {
		$data = wp_json_encode( $data );
	}

	return md5( $data );
}

/**
 * Gets the suffix for the script filenames.
 *
 * @since 2.0.0
 *
 * @return string The scripts suffix.
 */
function wc_instagram_get_scripts_suffix() {
	return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' );
}

/**
 * Gets templates passing attributes and including the file.
 *
 * @since 2.0.0
 *
 * @param string $template_name The template name.
 * @param array  $args          Optional. The template arguments.
 */
function wc_instagram_get_template( $template_name, $args = array() ) {
	wc_get_template( $template_name, $args, WC()->template_path(), WC_INSTAGRAM_PATH . 'templates/' );
}

/**
 * Gets the plugin settings.
 *
 * @since 2.0.0
 *
 * @return array An array with the plugin settings.
 */
function wc_instagram_get_settings() {
	return get_option( 'wc_instagram_settings', array() );
}

/**
 * Gets a setting value.
 *
 * @since 2.0.0
 *
 * @param string $name    The setting name.
 * @param mixed  $default Optional. The default value.
 * @return mixed The setting value.
 */
function wc_instagram_get_setting( $name, $default = null ) {
	$settings = wc_instagram_get_settings();

	return ( isset( $settings[ $name ] ) ? $settings[ $name ] : $default );
}

/**
 * Gets the expiration time for the transient used to cache the API requests.
 *
 * @since 2.0.0
 *
 * @param string $context The context.
 * @return int
 */
function wc_instragram_get_transient_expiration_time( $context = '' ) {
	// Backward compatibility.
	$expiration = apply_filters( 'woocommerce_instagram_transient_expire_time', DAY_IN_SECONDS );

	/**
	 * Filters the expiration time for the transient used to cache the API requests.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $expiration Time until expiration in seconds.
	 * @param string $context    The context.
	 */
	return apply_filters( 'wc_instagram_transient_expiration_time', $expiration, $context );
}

/**
 * Gets the number of columns to use in a media grid.
 *
 * @since 2.0.0
 *
 * @param string $context The context.
 * @return int
 */
function wc_instagram_get_columns( $context = '' ) {
	$columns = 4;

	// Use the setting value if exists.
	if ( $context ) {
		$columns = wc_instagram_get_setting( "{$context}_columns", $columns );
	}

	// Backward compatibility.
	$columns = apply_filters( 'woocommerce_instagram_columns', $columns );

	/**
	 * Filters the columns to use in a media grid.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $columns The number of columns.
	 * @param string $context The context.
	 */
	$columns = apply_filters( 'wc_instagram_get_columns', $columns, $context );

	return intval( $columns );
}

/**
 * Gets the number of images to display in a media grid.
 *
 * @since 2.0.0
 *
 * @param string $context The context.
 * @return int
 */
function wc_instagram_get_images_number( $context = '' ) {
	$number = 8;

	// Use the setting value if exists.
	if ( $context ) {
		$number = wc_instagram_get_setting( "{$context}_images", $number );
	}

	// Backward compatibility.
	$number = apply_filters( 'woocommerce_instagram_images', $number );

	/**
	 * Filters the number of images to display in a media grid.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $number  The number of images.
	 * @param string $context The context.
	 */
	$number = apply_filters( 'wc_instagram_get_images_number', $number, $context );

	return intval( $number );
}
