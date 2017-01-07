<?php
abstract class AMP_Cache_Utilities {

	static $amp_valid_content_types = array( 'c', 'i', 'r' );

	public static function amp_add_cache_update_actions() {
		// Hooking this to the post_updated action so this will fire any time a post
		// is updated in any way (including status transitions).
		add_action( 'post_updated',  array( 'AMP_Cache_Utilities', 'post_updated' ), 10, 3 );
		
		// Hooking the call to update the cache *before* the post is updated in case we need
		// to access any metadata for future functionality.
		add_action( 'before_delete_post',  array( 'AMP_Cache_Utilities', 'do_amp_update_ping' ) );
	}

	// TODO: Implement these for use with urls that don't correspond to a post (ex. favicon, font, etc)
	// public static function get_amp_cache_url_for_resource( $url, $content_type, $scheme = null ) {

	// }

	// public static function get_amp_cache_update_url_for_resource( $url, $content_type, $scheme = null ) {

	// }

	public static function get_amp_cache_url_for_post( $post_id ) {
		$amp_cache_url_base = 'https://cdn.ampproject.org';
		$amp_cache_resource_path = self::get_amp_cache_path_for_post( $post_id );
		if ( $amp_cache_resource_path ) {
			return $amp_cache_url_base . '/' . ltrim( $amp_cache_resource_path, '/' );
		}
		return false;
	}

	public static function get_amp_cache_update_url_for_post( $post_id ) {
		$amp_cache_url_base = 'https://cdn.ampproject.org/update-ping';
		$amp_cache_resource_path = self::get_amp_cache_path_for_post( $post_id );
		if ( $amp_cache_resource_path ) {
			return $amp_cache_url_base . '/' . ltrim( $amp_cache_resource_path, '/' );
		}
		return false;
	}

	public static function get_amp_cache_path_for_post( $post_id, $content_type = null, $scheme = null ) {
		$permalink = get_permalink( $post_id );

		// If permalink couldn't be retrieved, return failure.
		if ( false === $permalink ) {
			error_log( sprintf( 'ERROR in: %s DETAIL: Couldn\'t get permalink of post with ID %s.', __METHOD__, $post_id ) );
			return false;
		}

		// determine $content_type, if not specified
		if ( null == $content_type ) {
			$post_type = get_post_type( $post_id );
			switch ( $post_type ) {
				case 'attachment':
					$content_type = 'i';
					break;
				case 'post':
					$content_type = 'c';
					break;
				default:
					// unhandled post type
					error_log( sprintf( 'WARNING in: %s DETAIL: Unhandled post type: %s.', __METHOD__, $post_type ) );
					return false;
			}
		}

		// Return the url
		return self::get_amp_cache_path_for_url( $permalink, $content_type, $scheme );
	}

	public static function get_amp_cache_path_for_url( $url, $content_type , $scheme = null ) {
		$parsed_url = wp_parse_url( $url );
		// If permalink couldn't be parsed, then return failure.
		if ( false === $parsed_url ) {
			error_log( sprintf( 'ERROR in: %s DETAIL: Couldn\'t parse permalink of post with ID %s.', __METHOD__, $post_id ) );
			return false;
		}

		// If there is no host part to this URL, return failure.
		if ( ! isset( $parsed_url['host'] ) ) {
			error_log( sprintf( 'ERROR in: %s DETAIL: No host specified in post permalink.', __METHOD__ ) );
			return false;
		}

		// If there is no scheme specified in the parameter list and this is a protocol
		// relative URL, then we can't figure out whether this should be https or http.
		if ( null == $scheme ) {
			if ( isset( $parsed_url['scheme'] ) ) {
				$scheme = $parsed_url['scheme'];
			} else {
				// no scheme
				error_log( sprintf( 'ERROR in: %s DETAIL: No scheme specified. Can\'t continue.', __METHOD__ ) );
				return false;
			}
		}
		switch ( $scheme ) {
			case 'http':
				$scheme_code = '';
				break;
			case 'https':
				$scheme_code = 's';
				break;
			default:
				// invalid scheme
				error_log( sprintf( 'ERROR in: %s DETAIL: Invalid scheme specified in post permalink.', __METHOD__ ) );
				return false;
		}

		// validate $content_type
		if ( ! in_array( $content_type, self::$amp_valid_content_types ) ) {
			error_log( sprintf( 'ERROR in: %s DETAIL: Invalid content type specified: %s.', __METHOD__, $content_type ) );
			return false;
		}

		// Start building the amp cache url
		$amp_cache_url = '/' . $content_type;

		if ( ! empty( $scheme_code ) ) {
			$amp_cache_url .= '/' . $scheme_code;
		}

		$amp_cache_url .= '/' . $parsed_url['host'];

		if ( isset( $parsed_url['port'] ) ) {
			$amp_cache_url .= ':' . strval( $parsed_url['port'] );
		}

		if ( isset( $parsed_url['path'] ) ) {
			$amp_cache_url .= $parsed_url['path'];
		}

		if ( isset( $parsed_url['query'] ) ) {
			$amp_cache_url .= '?' . $parsed_url['query'];
		}

		if ( isset( $parsed_url['fragment'] ) ) {
			$amp_cache_url .= '#' . $parsed_url['fragment'];
		}

		return $amp_cache_url;
	}

	public static function post_updated( $post_id, $post_after, $post_before ) {
		// if post_status is 'publish' or was 'publish' but now is not, update cache
		if ( ( 'publish' == $post_after->post_status ) ||
			( ( 'publish' != $post_after->post_status ) && ( 'publish' == $post_before->post_status ) ) ) {
			return self::do_amp_update_ping( $post_id );
		}
	}

	public static function do_amp_update_ping( $post_id ) {
		$update_ping_url = self::get_amp_cache_update_url_for_post( $post_id );
		if ( ! $update_ping_url ) {
			return false;
		}
		$headers = get_headers( $update_ping_url );
    	$http_response_code = substr($headers[0], 9, 3);
    	return ( 204 != $http_response_code );
	}
}
?>