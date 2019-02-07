<?php
/**
 * Backward compatibility functions
 *
 * @package WC_Instagram/Functions
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gets a property from the product.
 *
 * @since 1.1.0
 *
 * @param mixed  $the_product Post object or post ID of the product.
 * @param string $key         Name of prop to get.
 * @return mixed|null The prop value. Null on failure.
 */
function wc_instagram_get_product_prop( $the_product, $key ) {
	$product = ( $the_product instanceof WC_Product ? $the_product : wc_get_product( $the_product ) );

	if ( ! $product ) {
		return null;
	}

	$callable = array( $product, "get_{$key}" );

	return ( is_callable( $callable ) ? call_user_func( $callable ) : $product->$key );
}

/**
 * Gets the logger instance.
 *
 * @since 2.0.0
 *
 * @return WC_Logger
 */
function wc_instagram_get_logger() {
	return ( function_exists( 'wc_get_logger' ) ? wc_get_logger() : new WC_Logger() );
}

/**
 * Logs a message.
 *
 * @since 2.0.0
 *
 * @param string         $message The message to log.
 * @param string         $level   The level.
 * @param string         $handle  Optional. The log handlers.
 * @param WC_Logger|null $logger  Optional. The logger instance.
 */
function wc_instagram_log( $message, $level = 'notice', $handle = 'wc_instagram', $logger = null ) {
	if ( ! $logger ) {
		$logger = wc_instagram_get_logger();
	}

	if ( method_exists( $logger, $level ) ) {
		call_user_func( array( $logger, $level ), $message, array( 'source' => $handle ) );
	} else {
		$logger->add( $handle, $message );
	}
}
