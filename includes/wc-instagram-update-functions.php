<?php
/**
 * Functions for updating data, used by the background updater
 *
 * @package WC_Instagram/Functions
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Migrates the settings from older versions.
 */
function wc_instagram_update_200_migrate_settings() {
	// This settings are no longer valid.
	delete_option( 'woocommerce-instagram-settings' );

	// Enable the API changes notice.
	add_option( 'wc_instagram_display_api_changes_notice', 'yes' );
}

/**
 * Update DB Version.
 */
function wc_instagram_update_200_db_version() {
	WC_Instagram_Install::update_db_version( '2.0.0' );
}
