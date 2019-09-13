<?php

namespace Altis\Cloud\Page_Cache;

use function Altis\Cloud\get_config;

function bootstrap() {
	global $batcache;
	$batcache = [];

	// Ignore common query params.
	ignore_query_params();

	// Forward CloudFront cache headers.
	handle_cloudfront_headers();
}

function ignore_query_params() {
	global $batcache;
	$batcache = $batcache ?? [];

	$config = get_config();
	$ignored_query_string_params = $config['cache']['ignored-query-string-params'] ?? [
		'utm_campaign',
		'utm_medium',
		'utm_source',
		'utm_content',
		'fbclid',
		'_ga',
	];

	$batcache['ignored_query_string_params'] = $ignored_query_string_params;
}

function handle_cloudfront_headers() {
	global $batcache;
	$batcache = $batcache ?? [];

	$config = get_config();
	$unique_headers = $config['cache']['unique-headers'] ?? [
		'CloudFront-Is-Desktop-Viewer',
		'CloudFront-Is-Mobile-Viewer',
		'CloudFront-Is-SmartTV-Viewer',
		'CloudFront-Is-Tablet-Viewer',
		'CloudFront-Viewer-Country',
	];

	// Set default unique values array.
	$batcache['unique'] = [];

	foreach ( $unique_headers as $header ) {
		$header_key = 'HTTP_' . str_replace( '-', '_', strtoupper( $header ) );
		if ( isset( $_SERVER[ $header_key ] ) ) {
			// Add header to batcache vary keys.
			$batcache['unique'][ $header ] = $_SERVER[ $header_key ];
			// Forward the header back in the response.
			header( sprintf( 'X-%s: %s', $header, $_SERVER[ $header_key ] ) );
		}
	}
}
