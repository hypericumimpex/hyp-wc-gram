<?php
/**
 * Product Data - Instagram
 *
 * @package WC_Instagram/Admin/Meta Boxes
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="instagram_data" class="panel woocommerce_options_panel">
	<?php
	woocommerce_wp_text_input(
		array(
			'id'          => '_instagram_hashtag',
			'class'       => 'short',
			'label'       => _x( 'Hashtag', 'product data setting title', 'woocommerce-instagram' ),
			'description' => _x( 'Display images for a given hashtag.', 'product data setting desc', 'woocommerce-instagram' ),
			'desc_tip'    => true,
			'type'        => 'text',
		)
	);
	?>
</div>
