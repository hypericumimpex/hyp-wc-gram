<?php
/**
 * Plugin Name: HYP WC Instagram
 * Plugin URI: https://github.com/hypericumimpex/hyp-wc-gram/
 * Description: Connect your Instagram account with WooCommerce. Showcase Instagrams from all over the world, showing visitors how your customers are showcasing your products.
 * Version: 2.0.1
 * Author: Hypericum
 * Author URI: https://github.com/hypericumimpex/
 * Developer: Themesquad
 * Developer URI: https://github.com/hypericumimpex/
 * Requires at least: 4.1
 * Tested up to: 5.0
 * WC requires at least: 2.4
 * WC tested up to: 3.6
 * Woo: 260061:ecaa2080668997daf396b8f8a50d891a
 *
 * Text Domain: woocommerce-instagram
 * Domain Path: /languages/
 *
 * Copyright: © 2013-2019 Hypericum Impex.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WC_Instagram
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Required functions.
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once 'woo-includes/woo-functions.php';
}

/**
 * Plugin updates.
 */
woothemes_queue_update( plugin_basename( __FILE__ ), 'ecaa2080668997daf396b8f8a50d891a', '260061' );

/**
 * Check if WooCommerce is active and the minimum requirements are satisfied.
 */
if ( ! is_woocommerce_active() || version_compare( get_option( 'woocommerce_db_version' ), '2.4', '<' ) ) {
	add_action( 'admin_notices', 'wc_instagram_requirements_notice' );
	return;
}

/**
 * Displays an admin notice when the minimum requirements are not satisfied.
 *
 * @since 2.0.0
 */
function wc_instagram_requirements_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( is_woocommerce_active() ) {
		/* translators: %s: WooCommerce version */
		$message = sprintf( _x( '<strong>WooCommerce Instagram</strong> requires WooCommerce %s or higher.', 'admin notice', 'woocommerce-instagram' ), '2.4' );
	} else {
		$message = _x( '<strong>WooCommerce Instagram</strong> requires WooCommerce to be activated to work.', 'admin notice', 'woocommerce-instagram' );
	}

	printf( '<div class="error"><p>%s</p></div>', wp_kses_post( $message ) );
}

// Define WC_INSTAGRAM_FILE constant.
if ( ! defined( 'WC_INSTAGRAM_FILE' ) ) {
	define( 'WC_INSTAGRAM_FILE', __FILE__ );
}

// Include the main plugin class.
if ( ! class_exists( 'WC_Instagram' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-wc-instagram.php';
}

/**
 * Main instance of the plugin.
 *
 * Returns the main instance of the plugin to prevent the need to use globals.
 *
 * @since  2.0.0
 * @return WC_Instagram
 */
function wc_instagram() {
	return WC_Instagram::instance();
}

wc_instagram();
