<?php
/**
 * WooCommerce Instagram Integration
 *
 * @package WC_Instagram/Admin
 * @since   2.0.0
 */
class WC_Instagram_Integration extends WC_Integration {

	/**
	 * The plugin ID. Used for option names.
	 *
	 * @var string
	 */
	public $plugin_id = 'wc_';

	/**
	 * The posted settings data. When empty, $_POST data will be used.
	 *
	 * Backward compatibility with WC 2.5.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->id                 = 'instagram';
		$this->method_title       = _x( 'Instagram', 'settings page title', 'woocommerce-instagram' );
		$this->method_description = _x( 'Connect your Instagram Business account.', 'settings page description', 'woocommerce-instagram' );

		add_action( "woocommerce_update_options_integration_{$this->id}", array( $this, 'process_admin_options' ) );
	}

	/**
	 * Gets the name of the option in the WP DB.
	 *
	 * Backward compatibility with WC 2.5 and lower.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_option_key() {
		return $this->plugin_id . $this->id . '_settings';
	}

	/**
	 * Gets a form field by key.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key The field key.
	 * @return array|false An array with the form field data. False otherwise.
	 */
	public function get_form_field( $key ) {
		if ( ! empty( $this->form_fields ) ) {
			$this->init_form_fields();
		}

		$fields = $this->get_form_fields();

		return ( ! empty( $fields[ $key ] ) ? $fields[ $key ] : false );
	}

	/**
	 * Gets the field key for settings.
	 *
	 * @since 2.0.0
	 *
	 * @param  string $key Field key.
	 * @return string
	 */
	public function get_field_key( $key ) {
		return $key;
	}

	/**
	 * Gets a fields type. Defaults to "text" if not set.
	 *
	 * Backward compatibility with WC 2.5 and lower.
	 *
	 * @since 2.0.0
	 *
	 * @param  array $field Field key.
	 * @return string
	 */
	public function get_field_type( $field ) {
		return empty( $field['type'] ) ? 'text' : $field['type'];
	}

	/**
	 * Gets a fields default value. Defaults to "" if not set.
	 *
	 * Backward compatibility with WC 2.5 and lower.
	 *
	 * @param  array $field Field key.
	 * @return string
	 */
	public function get_field_default( $field ) {
		return empty( $field['default'] ) ? '' : $field['default'];
	}

	/**
	 * Gets a field's posted and validated value.
	 *
	 * Backward compatibility with WC 2.5 and lower.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key Field key.
	 * @param array  $field Field array.
	 * @param array  $post_data Posted data.
	 * @return string
	 */
	public function get_field_value( $key, $field, $post_data = array() ) {
		$type      = $this->get_field_type( $field );
		$field_key = $this->get_field_key( $key );
		$post_data = empty( $post_data ) ? $_POST : $post_data; // WPCS: CSRF ok, input var ok.
		$value     = isset( $post_data[ $field_key ] ) ? $post_data[ $field_key ] : null;

		if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
			return call_user_func( $field['sanitize_callback'], $value );
		}

		// Look for a validate_FIELDID_field method for special handling.
		if ( is_callable( array( $this, 'validate_' . $key . '_field' ) ) ) {
			return $this->{'validate_' . $key . '_field'}( $key, $value );
		}

		// Look for a validate_FIELDTYPE_field method.
		if ( is_callable( array( $this, 'validate_' . $type . '_field' ) ) ) {
			return $this->{'validate_' . $type . '_field'}( $key, $value );
		}

		// Fallback to text.
		return $this->validate_text_field( $key, $value );
	}

	/**
	 * Sets the POSTed data. This method can be used to set specific data, instead of taking it from the $_POST array.
	 *
	 * Backward compatibility with WC 2.5 and lower.
	 *
	 * @since 2.0.0
	 *
	 * @param array $data Posted data.
	 */
	public function set_post_data( $data = array() ) {
		$this->data = $data;
	}

	/**
	 * Returns the POSTed data, to be used to save the settings.
	 *
	 * Backward compatibility with WC 2.5 and lower.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_post_data() {
		if ( ! empty( $this->data ) && is_array( $this->data ) ) {
			return $this->data;
		}

		return $_POST; // WPCS: CSRF ok, input var ok.
	}

	/**
	 * Initialise form fields.
	 *
	 * @since 2.0.0
	 */
	public function init_form_fields() {
		if ( wc_instagram_is_connected() ) {
			$this->form_fields = array(
				'account'                 => array(
					'type'     => 'account',
					'title'    => _x( 'Connected as', 'setting title', 'woocommerce-instagram' ),
					'desc_tip' => _x( 'The account connected.', 'setting desc', 'woocommerce-instagram' ),
				),
				'page_id'                 => array(
					'type'     => 'select',
					'title'    => _x( 'Facebook page', 'setting title', 'woocommerce-instagram' ),
					'desc_tip' => _x( 'Choose the Facebook Page connected with your Instagram Business account.', 'setting desc', 'woocommerce-instagram' ),
					'options'  => wc_instagram_get_user_pages_choices(),
				),
				'disconnect'              => array(
					'type'     => 'authorization',
					'title'    => _x( 'Disconnect account', 'setting title', 'woocommerce-instagram' ),
					'label'    => _x( 'Disconnect', 'disconnect account button', 'woocommerce-instagram' ),
					'desc_tip' => _x( 'Revoke WooCommerce access to your Instagram account.', 'setting desc', 'woocommerce-instagram' ),
					'class'    => 'button',
					'action'   => 'disconnect',
				),
				'product_page_section'    => array(
					'type'        => 'title',
					'title'       => _x( 'Product page', 'settings section title', 'woocommerce-instagram' ),
					'description' => _x( 'Display product-related Instagram images on each individual product screen.', 'settings section desc', 'woocommerce-instagram' ),
				),
				'product_hashtag_images'  => array(
					'type'              => 'number',
					'title'             => _x( 'Number of images', 'setting title', 'woocommerce-instagram' ),
					'desc_tip'          => _x( 'This sets the number of images shown on product page.', 'setting desc', 'woocommerce-instagram' ),
					'css'               => 'width:50px;',
					'default'           => 8,
					'custom_attributes' => array(
						'min'  => 1,
						'step' => 1,
					),
				),
				'product_hashtag_columns' => array(
					'type'              => 'number',
					'title'             => _x( 'Number of columns', 'setting title', 'woocommerce-instagram' ),
					'desc_tip'          => _x( 'This sets the number of columns shown on product page.', 'setting desc', 'woocommerce-instagram' ),
					'css'               => 'width:50px;',
					'default'           => 4,
					'custom_attributes' => array(
						'min'  => 1,
						'max'  => 8,
						'step' => 1,
					),
				),
			);
		} else {
			$description = sprintf(
				/* translators: 1: documentation link, 2: arial-label */
				_x( 'We strongly recommend you to read our <a href="%1$s" aria-label="%2$s" target="_blank">documentation</a> before connecting your account.', 'setting desc', 'woocommerce-instagram' ),
				esc_url( 'https://docs.woocommerce.com/document/woocommerce-instagram/' ),
				esc_attr_x( 'View WooCommerce Instagram documentation', 'aria-label: documentation link', 'woocommerce-instagram' )
			);

			$this->form_fields = array(
				'connect' => array(
					'type'        => 'authorization',
					'title'       => _x( 'Connect Account', 'setting title', 'woocommerce-instagram' ),
					'label'       => _x( 'Login with Facebook', 'connect account button', 'woocommerce-instagram' ),
					'desc_tip'    => _x( 'Authorize WooCommerce to access your Instagram account.', 'setting desc', 'woocommerce-instagram' ),
					'description' => $description,
					'class'       => 'button button-primary',
					'action'      => 'connect',
				),
			);
		}
	}

	/**
	 * Output the settings screen.
	 *
	 * @since 2.0.0
	 *
	 * @global bool $hide_save_button Hide the save button or not.
	 */
	public function admin_options() {
		global $hide_save_button;

		if ( ! wc_instagram_is_connected() ) {
			$hide_save_button = true;
		}

		$this->init_settings();
		$this->init_form_fields();

		parent::admin_options();
	}

	/**
	 * Processes and saves options.
	 *
	 * @since 2.0.0
	 */
	public function process_admin_options() {
		$this->init_settings();
		$this->init_form_fields();

		$post_data = $this->get_post_data();

		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( ! in_array( $this->get_field_type( $field ), array( 'title', 'authorization', 'account' ), true ) ) {
				try {
					$this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}

		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
	}

	/**
	 * Add an error message for display in admin on save.
	 *
	 * Backward compatibility with WC 2.5 and lower.
	 *
	 * @since 2.0.0
	 *
	 * @param string $error Error message.
	 */
	public function add_error( $error ) {
		$this->errors[] = $error;

		WC_Admin_Settings::add_error( $error );
	}

	/**
	 * Validates a numeric field.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key   The setting key.
	 * @param mixed  $value The setting value.
	 * @return int
	 */
	public function validate_number_field( $key, $value ) {
		$field = $this->get_form_field( $key );

		if ( ! $field ) {
			return $value;
		}

		// Set up to the default value.
		if ( ! $value && ! is_int( $value ) ) { // WPCS: loose comparison ok.
			$default = $this->get_field_default( $field );

			if ( '' !== $default ) {
				$value = $default;
			}
		}

		$value = intval( $value );

		// Check range.
		if ( ! empty( $field['custom_attributes'] ) ) {
			if ( ! empty( $field['custom_attributes']['min'] ) && $field['custom_attributes']['min'] > $value ) {
				$value = $field['custom_attributes']['min'];
			} elseif ( ! empty( $field['custom_attributes']['max'] ) && $field['custom_attributes']['max'] < $value ) {
				$value = $field['custom_attributes']['max'];
			}
		}

		return $value;
	}

	/**
	 * Validates the 'page_id' Field.
	 *
	 * @since 2.0.0
	 * @throws Exception If the Facebook page doesn't have an Instagram Business Account connected to it.
	 *
	 * @param string $key Field key.
	 * @param string $value Posted Value.
	 * @return string
	 */
	public function validate_page_id_field( $key, $value ) {
		$this->settings['instagram_business_account'] = array();

		if ( $value ) {
			$account = wc_instagram_get_business_account_from_page( $value );

			if ( ! $account ) {
				throw new Exception( _x( "The selected Facebook Page doesn't have an Instagram Business Account connected to it.", 'settings error', 'woocommerce-instagram' ) );
			} elseif ( ! empty( $account ) ) {
				$this->settings['instagram_business_account'] = $account;
			}
		}

		return $value;
	}

	/**
	 * Generates the HTML for the 'authorization' field.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key  The field key.
	 * @param mixed  $data The field data.
	 * @return string
	 */
	public function generate_authorization_html( $key, $data ) {
		$defaults = array(
			'action'            => '',
			'title'             => '',
			'label'             => '',
			'class'             => 'button',
			'css'               => '',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		$field_key = $this->get_field_key( $key );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo wp_kses_post( $this->get_tooltip_html( $data ) ); ?>
			</th>

			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>

					<?php
					printf(
						'<a href="%1$s" class="%2$s" style="%3$s"4$s>%5$s</a>',
						esc_url( wc_instagram_get_settings_url( array( 'action' => $data['action'] ) ) ),
						esc_attr( $data['class'] ),
						esc_attr( $data['css'] ),
						wp_kses_post( $this->get_custom_attribute_html( $data ) ),
						esc_html( $data['label'] )
					);

					echo wp_kses_post( $this->get_description_html( $data ) );
					?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generates the HTML for the 'account' field.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key  The field key.
	 * @param mixed  $data The field data.
	 * @return string
	 */
	public function generate_account_html( $key, $data ) {
		$user_name = ( ! empty( $this->settings['user_name'] ) ? $this->settings['user_name'] : '-' );

		if ( ! $user_name ) {
			return '';
		}

		$defaults = array(
			'title'             => '',
			'label'             => '',
			'class'             => '',
			'css'               => '',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		$field_key = $this->get_field_key( $key );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo wp_kses_post( $this->get_tooltip_html( $data ) ); ?>
			</th>

			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<?php
					printf(
						'<label class="%1$s" style="%2$s"3$s>%4$s</label>',
						esc_attr( $data['class'] ),
						esc_attr( $data['css'] ),
						wp_kses_post( $this->get_custom_attribute_html( $data ) ),
						esc_html( $user_name )
					);

					echo wp_kses_post( $this->get_description_html( $data ) );
					?>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}
}
