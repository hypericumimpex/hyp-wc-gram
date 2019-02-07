<?php
/**
 * Notice - Updating
 *
 * @package WC_Instagram/Admin/Notices
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

$force_update_url = wp_nonce_url(
	add_query_arg( 'force_update_wc_instagram', 'true', wc_instagram_get_settings_url() ),
	'wc_instagram_force_db_update',
	'wc_instagram_force_db_update_nonce'
);

?>
<div id="message" class="updated woocommerce-message wc-connect">
	<p>
		<a href="<?php echo esc_url( $force_update_url ); ?>">
		<strong><?php esc_html_e( 'WooCommerce Instagram', 'woocommerce-instagram' ); ?></strong> &#8211; <?php echo esc_html_x( 'Your database is being updated in the background.', 'admin notice', 'woocommerce-instagram' ); ?>
			<?php esc_html_e( 'Taking a while? Click here to run it now.', 'woocommerce-instagram' ); ?>
		</a>
	</p>
</div>
