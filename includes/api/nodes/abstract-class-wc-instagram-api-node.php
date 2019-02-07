<?php
/**
 * WooCommerce Instagram API Node
 *
 * @package WC_Instagram/API/Nodes
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Instagram_API_Node Class.
 */
abstract class WC_Instagram_API_Node {

	/**
	 * The access token.
	 *
	 * @var string
	 */
	protected $access_token;

	/**
	 * The allowed actions for this node.
	 *
	 * @var array
	 */
	protected $actions = array(
		'get',
	);

	/**
	 * The available edges for this node.
	 *
	 * @var array
	 */
	protected $edges = array();

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param string $access_token The access token.
	 */
	public function __construct( $access_token ) {
		$this->access_token = $access_token;
	}

	/**
	 * Magic __call method.
	 *
	 * @since 2.0.0
	 *
	 * @param string $method     Method.
	 * @param mixed  $parameters Parameters.
	 * @return bool|mixed
	 */
	public function __call( $method, $parameters ) {
		if ( in_array( $method, $this->get_allowed_methods(), true ) ) {
			$callable = array( $this, "_{$method}" );

			if ( is_callable( $callable ) ) {
				return call_user_func_array( $callable, $parameters );
			}
		}

		return null;
	}

	/**
	 * Gets the allowed methods to call for this node.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_allowed_methods() {
		return array_merge( $this->actions, $this->edges );
	}

	/**
	 * Gets a node by ID.
	 *
	 * @since 2.0.0
	 *
	 * @param int   $node_id The node ID.
	 * @param array $fields  Optional. The fields to retrieve.
	 * @return mixed An array with the response data. WP_Error on failure.
	 */
	protected function _get( $node_id, $fields = array() ) {
		$args = array();

		if ( ! empty( $fields ) ) {
			$args['fields'] = join( ',', $fields );
		}

		return $this->request_endpoint( $node_id, $args );
	}

	/**
	 * Makes a request to the Instagram Graph API endpoint.
	 *
	 * @since 2.0.0
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array  $args     The request arguments.
	 * @param string $method   Optional. The request method.
	 * @return mixed The request response. WP_Error on failure.
	 */
	protected function request_endpoint( $endpoint, $args = array(), $method = 'get' ) {
		$url = 'https://graph.facebook.com/v3.2/' . wp_unslash( $endpoint );

		return $this->trigger_request( $url, $args, $method );
	}

	/**
	 * Makes a request to the Instagram Graph API.
	 *
	 * @since 2.0.0
	 *
	 * @param string $url    The request URL.
	 * @param array  $args   The request arguments.
	 * @param string $method Optional. The request method.
	 * @return mixed The request response. WP_Error on failure.
	 */
	protected function trigger_request( $url, $args = array(), $method = 'get' ) {
		// No access token.
		if ( ! $this->access_token ) {
			return wc_instagram_log_api_error( _x( 'Access token not found.', 'API error message', 'woocommerce-instagram' ) );
		}

		// Include the 'access_token' in all the requests.
		$args = wp_parse_args( $args, array( 'access_token' => $this->access_token ) );

		// Clean arguments with a falsy value.
		$args = array_filter( $args );

		$request_method = 'wp_remote_' . strtolower( $method );

		// Invalid HTTP method.
		if ( ! function_exists( $request_method ) ) {
			return wc_instagram_log_api_error(
				/* translators: %s: the used HTTP method */
				sprintf( _x( 'Invalid HTTP method: %s.', 'API error message', 'woocommerce-instagram' ), esc_attr( $method ) )
			);
		}

		// Backward compatibility with WP 4.5 and lower.
		if ( 'get' === $method ) {
			$url  = add_query_arg( $args, $url );
			$args = null;
		}

		$response = call_user_func( $request_method, $url, array( 'body' => $args ) );

		// Request error.
		if ( is_wp_error( $response ) ) {
			return wc_instagram_log_api_error( $response );
		}

		$data = json_decode( $response['body'], true );

		// API error response.
		if ( is_array( $data ) && ! empty( $data['error'] ) ) {
			return wc_instagram_log_api_error( 'The API returned an error:', $data['error'], 'API Error Response' );
		}

		return $data;
	}
}
