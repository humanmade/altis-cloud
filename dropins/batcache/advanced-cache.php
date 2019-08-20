<?php

if ( is_readable( dirname( __FILE__ ) . '/batcache-stats.php' ) ) {
	require_once dirname( __FILE__ ) . '/batcache-stats.php';
}

// nananananananananananananananana BATCACHE!!!
require_once dirname( __FILE__ ) . '/class-batcache.php';
require_once dirname( __FILE__ ) . '/inc/advanced-cache.php';

global $batcache;
// Pass in the global variable which may be an array of settings to override defaults.
$batcache = new batcache( $batcache );

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	return;
}

// Never batcache interactive scripts or API endpoints.
if ( in_array(
	basename( $_SERVER['SCRIPT_FILENAME'] ),
	[
		'wp-app.php',
		'xmlrpc.php',
		'wp-cron.php',
	]
) ) {
	if ( $batcache->cache_control ) {
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	}
	if ( $batcache->add_hit_status_header ) {
		header( 'X-Batcache: BYPASS' );
		header( 'X-Batcache-Reason: Filename' );
	}
	return;
}

// Never batcache WP javascript generators
if ( strstr( $_SERVER['SCRIPT_FILENAME'], 'wp-includes/js' ) ) {
	if ( $batcache->cache_control ) {
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	}
	if ( $batcache->add_hit_status_header ) {
		header( 'X-Batcache: BYPASS' );
		header( 'X-Batcache-Reason: JS Generator' );
	}

	return;
}

// Never batcache a POST request.
if ( ! empty( $GLOBALS['HTTP_RAW_POST_DATA'] ) || ! empty( $_POST ) || ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) ) {

	if ( $batcache->cache_control ) {
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	}
	if ( $batcache->add_hit_status_header ) {
		header( 'X-Batcache: BYPASS' );
		header( 'X-Batcache-Reason: POST Request' );
	}

	return;
}

// Never cache Basic Auth'ed requests.
if ( ! empty( $_SERVER['PHP_AUTH_USER'] ) ) {
	if ( $batcache->cache_control ) {
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	}
	if ( $batcache->add_hit_status_header ) {
		header( 'X-Batcache: BYPASS' );
		header( 'X-Batcache-Reason: Basic Auth Request' );
	}

	return;
}

if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {

	if ( $batcache->add_hit_status_header ) {
		header( 'X-Batcache: BYPASS' );
		header( 'X-Batcache-Reason: Auth Request' );
	}

	return;
}

// Only cache HEAD and GET requests.
if ( ( isset( $_SERVER['REQUEST_METHOD'] ) && ! in_array( $_SERVER['REQUEST_METHOD'], [ 'GET', 'HEAD' ] ) ) ) {
	return;
}

// Never batcache when cookies indicate a cache-exempt visitor.
if ( is_array( $_COOKIE ) && ! empty( $_COOKIE ) ) {
	foreach ( array_keys( $_COOKIE ) as $batcache->cookie ) {
		if ( ! in_array( $batcache->cookie, $batcache->noskip_cookies ) && ( substr( $batcache->cookie, 0, 2 ) == 'wp' || substr( $batcache->cookie, 0, 9 ) == 'WordPress' || substr( $batcache->cookie, 0, 14 ) == 'comment_author' ) ) {
			batcache_stats( 'batcache', 'cookie_skip' );
			if ( $batcache->cache_control ) {
				header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
			}
			if ( $batcache->add_hit_status_header ) {
				header( 'X-Batcache: BYPASS' );
				header( 'X-Batcache-Reason: Cookies' );
			}

			return;
		}
	}
}

if ( ! function_exists( 'wp_cache_init' ) && ! include_once( WP_CONTENT_DIR . '/object-cache.php' ) ) {

	if ( $batcache->add_hit_status_header ) {
		header( 'X-Batcache: DOWN' );
	}

	return;
}

wp_cache_init(); // Note: wp-settings.php calls wp_cache_init() which clobbers the object made here.

global $wp_object_cache;
if ( ! is_object( $wp_object_cache ) ) {

	if ( $batcache->add_hit_status_header ) {
		header( 'X-Batcache: DOWN' );
	}

	return;
}

// Now that the defaults are set, you might want to use different settings under certain conditions.

/* Example: if your documents have a mobile variant (a different document served by the same URL) you must tell batcache about the variance. Otherwise you might accidentally cache the mobile version and serve it to desktop users, or vice versa.
$batcache->unique['mobile'] = is_mobile_user_agent();
*/

/* Example: never batcache for this host
if ( $_SERVER['HTTP_HOST'] == 'do-not-batcache-me.com' )
	return;
*/

/* Example: batcache everything on this host regardless of traffic level
if ( $_SERVER['HTTP_HOST'] == 'always-batcache-me.com' )
	return;
*/

/* Example: If you sometimes serve variants dynamically (e.g. referrer search term highlighting) you probably don't want to batcache those variants. Remember this code is run very early in wp-settings.php so plugins are not yet loaded. You will get a fatal error if you try to call an undefined function. Either include your plugin now or define a test function in this file.
if ( include_once( 'plugins/searchterm-highlighter.php') && referrer_has_search_terms() )
	return;
*/

// Disabled
if ( $batcache->max_age < 1 ) {
	return;
}

// Make sure we can increment. If not, turn off the traffic sensor.
if ( ! method_exists( $GLOBALS['wp_object_cache'], 'incr' ) ) {
	$batcache->times = 0;
}

// Necessary to prevent clients using cached version after login cookies set. If this is a problem, comment it out and remove all Last-Modified headers.
header( 'Vary: Cookie', false );

// Things that define a unique page.
if ( isset( $_SERVER['QUERY_STRING'] ) ) {
	parse_str( $_SERVER['QUERY_STRING'], $batcache->query );
	$batcache->query = array_diff_key( $batcache->query, array_flip( $batcache->ignored_query_string_params ) );
}
$batcache->pos = strpos( $_SERVER['REQUEST_URI'], '?' );
$batcache->keys = [
	'host' => $_SERVER['HTTP_HOST'],
	'method' => $_SERVER['REQUEST_METHOD'],
	'path' => ( $batcache->pos ) ? substr( $_SERVER['REQUEST_URI'], 0, $batcache->pos ) : $_SERVER['REQUEST_URI'],
	'query' => $batcache->query,
	'extra' => $batcache->unique,
];

if ( $batcache->is_ssl() ) {
	$batcache->keys['ssl'] = true;
}

// Recreate the permalink from the URL
$batcache->permalink = 'http://' . $batcache->keys['host'] . $batcache->keys['path'] . ( isset( $batcache->keys['query']['p'] ) ? '?p=' . $batcache->keys['query']['p'] : '' );
$batcache->url_key = md5( $batcache->permalink );
$batcache->configure_groups();
$batcache->url_version = (int) wp_cache_get( "{$batcache->url_key}_version", $batcache->group );
$batcache->do_variants();
$batcache->generate_keys();

// Get the batcache
$batcache->cache = wp_cache_get( $batcache->key, $batcache->group );

// Are we only caching frequently-requested pages?
if ( isset( $batcache->cache['version'] ) && $batcache->cache['version'] != $batcache->url_version ) {
	// Always refresh the cache if a newer version is available.
	$batcache->do = true;
} elseif ( $batcache->seconds < 1 || $batcache->times < 1 ) {
	// Are we only caching frequently-requested pages?
	$batcache->do = true;
} else {
	// No batcache item found, or ready to sample traffic again at the end of the batcache life?
	if ( ! is_array( $batcache->cache ) || time() >= $batcache->cache['time'] + $batcache->max_age - $batcache->seconds ) {
		wp_cache_add( $batcache->req_key, 0, $batcache->group );
		$batcache->requests = wp_cache_incr( $batcache->req_key, 1, $batcache->group );

		if ( $batcache->requests >= $batcache->times &&
			time() >= $batcache->cache['time'] + $batcache->cache['max_age']
		) {
			wp_cache_delete( $batcache->req_key, $batcache->group );
			$batcache->do = true;
		} else {
			$batcache->do = false;
		}
	}
}

// Obtain cache generation lock
if ( $batcache->do ) {
	$batcache->genlock = wp_cache_add( "{$batcache->url_key}_genlock", 1, $batcache->group, 10 );
}

if ( isset( $batcache->cache['time'] ) && // We have cache
	! $batcache->genlock && // We have not obtained cache regeneration lock
	(
		time() < $batcache->cache['time'] + $batcache->cache['max_age'] || // Batcached page that hasn't expired ||
		( $batcache->do && $batcache->use_stale )                          // Regenerating it in another request and can use stale cache
	)
) {
	// Issue redirect if cached and enabled
	if ( $batcache->cache['redirect_status'] && $batcache->cache['redirect_location'] && $batcache->cache_redirects ) {
		$status = $batcache->cache['redirect_status'];
		$location = $batcache->cache['redirect_location'];
		// From vars.php
		$is_IIS = ( strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) !== false || strpos( $_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer' ) !== false );

		$batcache->do_headers( $batcache->headers );
		if ( $is_IIS ) {
			header( "Refresh: 0;url=$location" );
		} else {
			if ( php_sapi_name() != 'cgi-fcgi' ) {
				$texts = [
					300 => 'Multiple Choices',
					301 => 'Moved Permanently',
					302 => 'Found',
					303 => 'See Other',
					304 => 'Not Modified',
					305 => 'Use Proxy',
					306 => 'Reserved',
					307 => 'Temporary Redirect',
				];
				$protocol = $_SERVER['SERVER_PROTOCOL'];
				if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol ) {
					$protocol = 'HTTP/1.0';
				}
				if ( isset( $texts[ $status ] ) ) {
					header( "$protocol $status " . $texts[ $status ] );
				} else {
					header( "$protocol 302 Found" );
				}
			}
			header( "Location: $location" );
		}

		if ( $batcache->add_hit_status_header ) {
			header( 'X-Batcache: HIT' );
		}
		exit;
	}

	// Respect ETags served with feeds.
	$three04 = false;
	if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && isset( $batcache->cache['headers']['ETag'][0] ) && $_SERVER['HTTP_IF_NONE_MATCH'] == $batcache->cache['headers']['ETag'][0] ) {
		$three04 = true;
	} elseif ( $batcache->cache_control && isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
		// Respect If-Modified-Since.
		$client_time = strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
		if ( isset( $batcache->cache['headers']['Last-Modified'][0] ) ) {
			$cache_time = strtotime( $batcache->cache['headers']['Last-Modified'][0] );
		} else {
			$cache_time = $batcache->cache['time'];
		}

		if ( $client_time >= $cache_time ) {
			$three04 = true;
		}
	}

	// Use the batcache save time for Last-Modified so we can issue "304 Not Modified" but don't clobber a cached Last-Modified header.
	if ( $batcache->cache_control && ! isset( $batcache->cache['headers']['Last-Modified'][0] ) ) {
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $batcache->cache['time'] ) . ' GMT', true );
		header( 'Cache-Control: max-age=' . ( $batcache->cache['max_age'] - time() + $batcache->cache['time'] ) . ', must-revalidate', true );
	}

	// Add some debug info just before </head>
	if ( $batcache->debug ) {
		$batcache->add_debug_from_cache();
	}

	$batcache->do_headers( $batcache->headers, $batcache->cache['headers'] );

	if ( $three04 ) {
		header( 'HTTP/1.1 304 Not Modified', true, 304 );

		if ( $batcache->add_hit_status_header ) {
			header( 'X-Batcache: HIT' );
		}

		die;
	}

	if ( ! empty( $batcache->cache['status_header'] ) ) {
		header( $batcache->cache['status_header'], true );
	}

	batcache_stats( 'batcache', 'total_cached_views' );

	if ( $batcache->add_hit_status_header ) {
		header( 'X-Batcache: HIT' );
	}

	// Have you ever heard a death rattle before?
	die( $batcache->cache['output'] );
}

// Didn't meet the minimum condition?
if ( ! $batcache->do || ! $batcache->genlock ) {
	return;
}

//WordPress 4.7 changes how filters are hooked. Since WordPress 4.6 add_filter can be used in advanced-cache.php. Previous behaviour is kept for backwards compatability with WP < 4.6
global $wp_filter;
if ( function_exists( 'add_filter' ) ) {
	add_filter( 'status_header', [ $batcache, 'status_header' ], 10, 2 );
	add_filter( 'wp_redirect_status', [ $batcache, 'redirect_status' ], 10, 2 );
} else {
	$wp_filter['status_header'][10]['batcache'] = [
		'function' => [ $batcache, 'status_header' ],
		'accepted_args' => 2,
	];
	$wp_filter['wp_redirect_status'][10]['batcache'] = [
		'function' => [ $batcache, 'redirect_status' ],
		'accepted_args' => 2,
	];
}


ob_start( [ $batcache, 'ob' ] );

// It is safer to omit the final PHP closing tag.
