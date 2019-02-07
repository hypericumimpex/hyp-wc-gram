<?php
/**
 * API Functions
 *
 * @package WC_Instagram/Functions
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gets if the user is connected or not.
 *
 * @since 2.0.0
 *
 * @return bool
 */
function wc_instagram_is_connected() {
	return ! empty( wc_instagram_get_setting( 'access_token' ) );
}

/**
 * Gets if the user has an Instagram Business account or not.
 *
 * @since 2.0.0
 *
 * @return bool
 */
function wc_instagram_has_business_account() {
	return ( wc_instagram_is_connected() && ! empty( wc_instagram_get_setting( 'instagram_business_account' ) ) );
}

/**
 * Adds the Instagram access credentials.
 *
 * @since 2.0.0
 *
 * @param string $access_token The access token.
 * @return bool
 */
function wc_instagram_connect( $access_token ) {
	$api = wc_instagram()->api();

	// Set the access token on the fly.
	$api->set_access_token( $access_token );

	// Fetch the user info including the user_id parameter.
	$user = $api->user()->me( array( 'id', 'name' ) );

	if ( is_wp_error( $user ) ) {
		return false;
	}

	$settings = array(
		'access_token' => $access_token,
		'user_id'      => ( ! empty( $user['id'] ) ? $user['id'] : '' ),
		'user_name'    => ( ! empty( $user['name'] ) ? $user['name'] : '' ),
	);

	return update_option( 'wc_instagram_settings', $settings );
}

/**
 * Removes the Instagram access credentials.
 *
 * @since 2.0.0
 *
 * @return bool
 */
function wc_instagram_disconnect() {
	return delete_option( 'wc_instagram_settings' );
}

/**
 * Gets the Instagram authentication URL.
 *
 * @since 2.0.0
 */
function wc_instagram_get_auth_url() {
	return add_query_arg(
		array(
			'nonce'    => wp_create_nonce( 'wc_instagram_auth' ),
			'redirect' => rawurlencode( wc_instagram_get_settings_url() ),
		),
		'https://connect.themesquad.com/facebook/login/'
	);
}

/**
 * Gets the user pages.
 *
 * @since 2.0.0
 *
 * @return array
 */
function wc_instagram_get_user_pages() {
	$accounts = wc_instagram()->api()->user()->accounts();

	return ( is_wp_error( $accounts ) ? array() : $accounts['data'] );
}

/**
 * Gets the user pages choices to use them in a select field.
 *
 * @since 2.0.0
 *
 * @return array
 */
function wc_instagram_get_user_pages_choices() {
	$accounts = wc_instagram_get_user_pages();

	$choices = wp_list_pluck( $accounts, 'name', 'id' );

	// Don't use array_merge to avoid reindexing.
	$choices = array( '' => _x( 'Choose a page', 'Facebook page setting placeholder', 'woocommerce-instagram' ) ) + $choices;

	return $choices;
}

/**
 * Gets the Instagram Business Account associated to the specified Facebook Page ID.
 *
 * @since 2.0.0
 *
 * @param int $page_id The Facebook Page ID.
 * @return false|array An array with the account info. False otherwise.
 */
function wc_instagram_get_business_account_from_page( $page_id ) {
	$data = wc_instagram()->api()->page()->get( $page_id, array( 'instagram_business_account' ) );

	return ( ! is_wp_error( $data ) && ! empty( $data['instagram_business_account'] ) ? $data['instagram_business_account'] : false );
}

/**
 * Searches an Instagram hashtag by name.
 *
 * @since 2.0.0
 *
 * @param string $hashtag The hashtag name.
 * @return int The hashtag ID. False otherwise.
 */
function wc_instagram_search_hashtag( $hashtag ) {
	$hashtags = get_option( 'wc_instagram_hashtags', array() );

	// Fetch from cache.
	if ( ! empty( $hashtags[ $hashtag ] ) ) {
		$hashtag_id = intval( $hashtags[ $hashtag ] );
	} else {
		$hashtag_id = wc_instagram()->api()->hashtag()->search( $hashtag );

		// The request failed or the hashtag was not found.
		if ( ! is_int( $hashtag_id ) ) {
			return false;
		}

		$hashtags[ $hashtag ] = $hashtag_id;

		// Cache the hashtag.
		update_option( 'wc_instagram_hashtags', $hashtags );
	}

	return $hashtag_id;
}

/**
 * Gets the media objects tagged with the specified hashtag.
 *
 * @since 2.0.0
 *
 * @param mixed $hashtag The hashtag name or ID.
 * @param array $args    Optional. Additional arguments.
 * @return array|false An array with the images. False on failure.
 */
function wc_instagram_get_hashtag_media( $hashtag, $args = array() ) {
	$hashtag_id = ( is_int( $hashtag ) ? $hashtag : wc_instagram_search_hashtag( $hashtag ) );

	if ( ! $hashtag_id ) {
		return false;
	}

	$defaults = array(
		'hashtag_id' => $hashtag_id, // The hashtag ID.
		'edge'       => 'recent',    // The hashtag media edge. Allowed values: 'recent', 'top'.
		'type'       => '',          // Filter media objects by type. Accept an array with multiple media types.
		'count'      => 8,           // The number of media objects to retrieve.
		'fields'     => array(       // The media fields to retrieve.
			'media_type',
			'caption',
			'permalink',
			'media_url',
			'like_count',
		),
	);

	$args = wp_parse_args( $args, $defaults );

	// Convert the 'type' argument to array.
	$args['type'] = ( is_array( $args['type'] ) ? $args['type'] : array( $args['type'] ) );

	// Remove empty values and convert to uppercase the media types.
	$args['type'] = array_map( 'strtoupper', array_filter( $args['type'] ) );

	// Sanitize value.
	$args['count'] = absint( $args['count'] );

	/**
	 * Filters the arguments used to fetch the media objects.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args The arguments.
	 */
	$args = apply_filters( 'wc_instagram_get_hashtag_media_args', $args );

	// Generate an unique identifier for this query.
	$args_hash = wc_instagram_get_hash( $args );

	$hashtag_transient = "wc_instagram_hashtag_media_{$args_hash}";

	// Get cached result.
	$media = get_transient( $hashtag_transient );

	// Fetch media if the transient has expired.
	if ( false === $media ) {
		$media          = array();
		$filter_by_type = ! empty( $args['type'] );
		$after          = ''; // After cursor. Allow pagination.
		$end_loop       = false;

		do {
			$node = wc_instagram()->api()->hashtag();

			// Trigger the request.
			$response = call_user_func(
				array( $node, "{$args['edge']}_media" ),
				$hashtag_id,
				array(
					'fields' => $args['fields'],
					'after'  => $after,
				)
			);

			if ( is_wp_error( $response ) || empty( $response['data'] ) ) {
				$end_loop = true;
				continue;
			}

			foreach ( $response['data'] as $media_object ) {
				$valid = true;

				// Not valid media type.
				if ( $filter_by_type && ! in_array( $media_object['media_type'], $args['type'], true ) ) {
					$valid = false;
				}

				/**
				 * Filters if the media object is valid or not.
				 *
				 * @since 2.0.0
				 *
				 * @param bool  $valid        True if the media object is valid. False otherwise.
				 * @param array $media_object The media object.
				 * @param array $args         The arguments used for the query.
				 */
				$valid = apply_filters( 'wc_instagram_is_valid_hashtag_media', $valid, $media_object, $args );

				if ( $valid ) {
					$media[] = $media_object;

					// We've enough media objects.
					if ( count( $media ) === $args['count'] ) {
						$end_loop = true;
						break;
					}
				}
			}

			// Set the cursor.
			$after = ( isset( $response['paging'], $response['paging']['cursors'], $response['paging']['cursors']['after'] ) ? $response['paging']['cursors']['after'] : '' );

			// There is no more pages.
			if ( ! $after ) {
				$end_loop = true;
			}
		} while ( ! $end_loop );

		// Cache the result.
		set_transient( $hashtag_transient, $media, wc_instragram_get_transient_expiration_time() );
	}

	/**
	 * Filters the media objects tagged with the specified hashtag.
	 *
	 * @since 2.0.0
	 *
	 * @param array $media      An array with the media objects.
	 * @param int   $hashtag_id The hashtag ID.
	 * @param array $args       The arguments used for the query.
	 */
	return apply_filters( 'wc_instagram_get_hashtag_media', $media, $hashtag_id, $args );
}

/**
 * Logs an API error.
 *
 * Logs the error and return a `WP_Error` object.
 *
 * @since 2.0.0
 *
 * @param mixed  $error  The error to log. It can be an string or a WP_Error object.
 * @param array  $params Optional. The error arguments. Only if the first parameter is a string.
 * @param string $tag    Optional. The error tag.
 * @return WP_Error
 */
function wc_instagram_log_api_error( $error, $params = array(), $tag = 'API Error' ) {
	if ( ! is_wp_error( $error ) ) {
		$error = new WP_Error( 'wc_instagram_api', $error, $params );
	}

	$error_code = $error->get_error_code();
	$error_data = $error->get_error_data();

	$error_log = sprintf(
		'[%1$s]%2$s %3$s %4$s',
		$tag,
		( 'wc_instagram_api' !== $error_code ? " {$error_code}:" : '' ),
		$error->get_error_message(),
		( is_array( $error_data ) ? print_r( $error_data, true ) : '' )
	);

	wc_instagram_log( $error_log, 'error', 'wc_instagram_api' );

	return $error;
}
