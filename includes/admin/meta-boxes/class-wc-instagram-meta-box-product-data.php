<?php
/**
 * Meta Box: Product Data
 *
 * Updates the Product Data meta box.
 *
 * @package WC_Instagram/Admin/Meta Boxes
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Instagram_Meta_Box_Product_Data.
 */
class WC_Instagram_Meta_Box_Product_Data {

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'product_data_tabs' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'product_data_panels' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ), 15 );
	}

	/**
	 * Adds custom tabs to the product_data meta box.
	 *
	 * @since 2.0.0
	 *
	 * @param array $tabs Array of existing tabs.
	 * @return array
	 */
	public function product_data_tabs( $tabs ) {
		$instagram_tab = array(
			'label'    => _x( 'Instagram', 'product data tab', 'woocommerce-instagram' ),
			'target'   => 'instagram_data',
			'class'    => array(),
			'priority' => 35,
		);

		$index = array_search( 'linked_product', array_keys( $tabs ), true );

		// Try to locate the 'Instagram' tab in the correct position.
		if ( false === $index ) {
			$tabs['instagram'] = $instagram_tab;
		} else {
			$tabs = array_merge(
				array_slice( $tabs, 0, $index ),
				array(
					'instagram' => $instagram_tab,
				),
				array_slice( $tabs, $index )
			);
		}

		return $tabs;
	}

	/**
	 * Outputs the custom data panels.
	 *
	 * @since 2.0.0
	 */
	public function product_data_panels() {
		include 'views/html-product-data-instagram.php';
	}

	/**
	 * Saves the product data.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_product_data( $post_id ) {
		if ( ! empty( $_POST['_instagram_hashtag'] ) ) {
			$value = wc_clean( wp_unslash( $_POST['_instagram_hashtag'] ) ); // WPCS: CSRF ok, sanitization ok.
			$value = str_replace( array( ' ', '#' ), '', $value );

			// Delete stored images on change the hashtag.
			if ( update_post_meta( $post_id, '_instagram_hashtag', $value ) ) {
				wc_instagram_delete_product_hashtag_images( $post_id );
			}
		} else {
			delete_post_meta( $post_id, '_instagram_hashtag' );
			wc_instagram_delete_product_hashtag_images( $post_id );
		}
	}
}

return new WC_Instagram_Meta_Box_Product_Data();
