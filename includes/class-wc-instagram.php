<?php
/**
 * WooCommerce Instagram setup
 *
 * @package WC_Instagram
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Singleton pattern.
 */
if ( ! class_exists( 'WC_Instagram_Singleton' ) ) {
	require_once 'class-wc-instagram-singleton.php';
}

/**
 * WooCommerce Instagram Class.
 *
 * @class WC_Instagram
 */
final class WC_Instagram extends WC_Instagram_Singleton {

	/**
	 * The plugin version.
	 *
	 * @var string
	 */
	public $version = '2.0.1';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @access protected
	 */
	protected function __construct() {
		parent::__construct();

		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define constants.
	 *
	 * @since 2.0.0
	 */
	public function define_constants() {
		$this->define( 'WC_INSTAGRAM_VERSION', $this->version );
		$this->define( 'WC_INSTAGRAM_PATH', plugin_dir_path( WC_INSTAGRAM_FILE ) );
		$this->define( 'WC_INSTAGRAM_URL', plugin_dir_url( WC_INSTAGRAM_FILE ) );
		$this->define( 'WC_INSTAGRAM_BASENAME', plugin_basename( WC_INSTAGRAM_FILE ) );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @param string      $name  The constant name.
	 * @param string|bool $value The constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Includes the necessary files.
	 *
	 * @since 2.0.0
	 */
	public function includes() {
		include_once WC_INSTAGRAM_PATH . 'includes/class-wc-instagram-autoloader.php';
		include_once WC_INSTAGRAM_PATH . 'includes/wc-instagram-functions.php';
		include_once WC_INSTAGRAM_PATH . 'includes/class-wc-instagram-install.php';

		if ( is_admin() ) {
			include_once WC_INSTAGRAM_PATH . 'includes/admin/class-wc-instagram-admin.php';
		}

		// It's frontend.
		if ( ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! defined( 'REST_REQUEST' ) ) {
			include_once WC_INSTAGRAM_PATH . 'includes/wc-instagram-template-hooks.php';
		}
	}

	/**
	 * Includes the Template Functions - This makes them pluggable by plugins and themes.
	 *
	 * @since 2.0.0
	 */
	public function include_template_functions() {
		include_once WC_INSTAGRAM_PATH . 'includes/wc-instagram-template-functions.php';
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.0.0
	 */
	private function init_hooks() {
		register_activation_hook( WC_INSTAGRAM_FILE, array( 'WC_Instagram_Install', 'install' ) );

		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 15 );
	}

	/**
	 * Init plugin.
	 *
	 * @since 2.0.0
	 */
	public function init() {
		// Load text domain.
		load_plugin_textdomain( 'woocommerce-instagram', false, dirname( WC_INSTAGRAM_BASENAME ) . '/languages' );

		add_filter( 'woocommerce_integrations', array( $this, 'register_integration' ) );
	}

	/**
	 * Registers the integration.
	 *
	 * @since 2.0.0
	 *
	 * @param array $integrations Array of integration instances.
	 * @return array
	 */
	public function register_integration( $integrations ) {
		include_once WC_INSTAGRAM_PATH . 'includes/class-wc-instagram-integration.php';

		$integrations[] = 'WC_Instagram_Integration';

		return $integrations;
	}

	/**
	 * Gets the WooCommerce Instagram API.
	 *
	 * @since 2.0.0
	 *
	 * @return WC_Instagram_API
	 */
	public function api() {
		return WC_Instagram_API::instance();
	}
}
