<?php
/**
 * Altis Cloud Page Cache Configuration.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Page_Cache;

use Altis;
use Altis\Cloud;

/**
 * Configure page cache from config.
 *
 * @return void
 */
function bootstrap() {
	global $batcache;
	$batcache = $batcache ?? [
		'ignored_query_string_params' => [],
		'unique' => [],
	];

	$ignore_query_params = array_merge(
		$batcache['ignored_query_string_params'] ?? [],
		get_ignore_query_params()
	);

	$ignore_query_params = array_unique( $ignore_query_params );

	// Ignore common query params.
	$batcache['ignored_query_string_params'] = $ignore_query_params;

	// Add unique key / values that should vary the page cache.
	$unique = array_merge(
		$batcache['unique'] ?? [],
		get_unique_headers(),
		get_unique_cookies()
	);
	$unique = array_filter( $unique );
	$unique = array_unique( $unique );
	$batcache['unique'] = $unique;

	// Cache redirects.
	$batcache['cache_redirects'] = true;

	// No-priv AJAX requests are public and should be cached.
	add_action( 'admin_init', __NAMESPACE__ . '\\disable_no_cache_headers_on_admin_ajax_nopriv' );

	// Load Batcache.
	require Altis\ROOT_DIR . '/vendor/humanmade/batcache/advanced-cache.php';
}

/**
 * Returns an array of query string parameters that should not be used to
 * vary the page cache.
 *
 * @return array
 */
function get_ignore_query_params() : array {
	$config = Cloud\get_config();
	$ignored_query_string_params = $config['page-cache']['ignored-query-string-params'] ?? [];
	return $ignored_query_string_params;
}

/**
 * Returns a list of headers that should result in a varied page cache.
 * Useful if alternative output or processing is required based on
 * 'CloudFront-Viewer-*' headers for example.
 *
 * @return array
 */
function get_unique_headers() : array {
	$config = Cloud\get_config();
	$unique_headers = $config['page-cache']['unique-headers'] ?? [];
	$unique_keys = [];

	foreach ( $unique_headers as $header ) {
		$header_key = 'HTTP_' . str_replace( '-', '_', strtoupper( $header ) );
		if ( isset( $_SERVER[ $header_key ] ) ) {
			// Add header to batcache vary keys.
			$unique_keys[ $header ] = $_SERVER[ $header_key ];
		}
	}

	return $unique_keys;
}

/**
 * Returns a list of cookies that should result in a varied page cache.
 *
 * @return array
 */
function get_unique_cookies() : array {
	$config = Cloud\get_config();
	$unique_cookies = $config['page-cache']['unique-cookies'] ?? [];
	$unique_keys = [];

	foreach ( $unique_cookies as $cookie ) {
		if ( ! empty( $_COOKIE[ $cookie ] ) ) {
			// Add header to batcache vary keys.
			$unique_keys[ $cookie ] = $_COOKIE[ $cookie ];
		}
	}

	return $unique_keys;
}

/**
 * Remove the "no cache" headers that are sent on logged out admin-ajax.php requests.
 *
 * These requests can be cached, as they don't include private data.
 */
function disable_no_cache_headers_on_admin_ajax_nopriv() {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX || is_user_logged_in() ) {
		return;
	}
	array_map( 'header_remove', array_keys( wp_get_nocache_headers() ) );
}
