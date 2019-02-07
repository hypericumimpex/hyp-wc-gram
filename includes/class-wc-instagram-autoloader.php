<?php
/**
 * Plugin Class Autoloader
 *
 * @package WC_Instagram
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Instagram_Autoloader' ) ) {
	/**
	 * Loads the classes on demand.
	 *
	 * @since 2.0.0
	 */
	class WC_Instagram_Autoloader {

		/**
		 * Constructor.
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			if ( function_exists( '__autoload' ) ) {
				spl_autoload_register( '__autoload' );
			}

			spl_autoload_register( array( $this, 'autoload' ) );
		}

		/**
		 * Auto-load classes on demand to reduce memory consumption.
		 *
		 * @since 2.0.0
		 *
		 * @param string $class The class to load.
		 */
		public function autoload( $class ) {
			$class = strtolower( $class );
			$file  = $this->get_file_name_from_class( $class );

			/**
			 * Filters the autoload classes.
			 *
			 * @since 2.0.0
			 *
			 * @param array $autoload An array with pairs ( pattern => $path ).
			 */
			$autoload = apply_filters(
				'wc_instagram_autoload',
				array(
					'wc_instagram_api_node' => WC_INSTAGRAM_PATH . 'includes/api/nodes/',
					'wc_instagram_api'      => WC_INSTAGRAM_PATH . 'includes/api/',
					'wc_instagram_'         => WC_INSTAGRAM_PATH . 'includes/',
				)
			);

			foreach ( $autoload as $prefix => $path ) {
				if ( 0 === strpos( $class, $prefix ) ) {
					$this->load_file( $path . $file );
				}
			}
		}

		/**
		 * Take a class name and turn it into a file name.
		 *
		 * @since 2.0.0
		 *
		 * @param  string $class The class name.
		 * @return string The file name.
		 */
		private function get_file_name_from_class( $class ) {
			return 'class-' . str_replace( '_', '-', $class ) . '.php';
		}

		/**
		 * Include a class file.
		 *
		 * @since 2.0.0
		 *
		 * @param  string $path The file path.
		 * @return bool successful or not
		 */
		private function load_file( $path ) {
			if ( $path && is_readable( $path ) ) {
				include_once $path;

				return true;
			}

			return false;
		}

	}
}

new WC_Instagram_Autoloader();
