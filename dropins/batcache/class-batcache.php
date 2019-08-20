<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PEAR.NamingConventions.ValidClassName.StartWithCapital
// phpcs:disable HM.Functions.NamespacedFunctions.MissingNamespace

class batcache {
	// This is the base configuration. You can edit these variables or move them into your wp-config.php file.
	var $max_age = 300; // Expire batcache items aged this many seconds (zero to disable batcache)

	var $remote = 0; // Zero disables sending buffers to remote datacenters (req/sec is never sent)

	var $times   = 1; // Only batcache a page after it is accessed this many times... (two or more)
	var $seconds = 120; // ...in this many seconds (zero to ignore this and use batcache immediately)

	var $group = 'batcache'; // Name of memcached group. You can simulate a cache flush by changing this.

	var $unique = []; // If you conditionally serve different content, put the variable values here.

	var $vary = []; // Array of functions for create_function. The return value is added to $unique above.

	var $headers = []; // Add headers here as name=>value or name=>array(values). These will be sent with every response from the cache.

	var $cache_redirects = false; // Set true to enable redirect caching.
	var $redirect_status = false; // This is set to the response code during a redirect.
	var $redirect_location = false; // This is set to the redirect location.

	var $use_stale        = true; // Is it ok to return stale cached response when updating the cache?
	var $uncached_headers = [ 'transfer-encoding' ]; // These headers will never be cached. Apply strtolower.

	var $debug = true; // Set false to hide the batcache info <!-- comment -->

	var $cache_control = true; // Set false to disable Last-Modified and Cache-Control headers

	var $cancel = false; // Change this to cancel the output buffer. Use batcache_cancel();

	var $noskip_cookies = [ 'wordpress_test_cookie' ]; // Names of cookies - if they exist and the cache would normally be bypassed, don't bypass it

	var $add_hit_status_header = true; // Add X-Batache HTTP header for "HIT" "BYPASS" "MISS" etc
	var $ignored_query_string_params = [];
	var $query = '';
	var $genlock = false;
	var $do = false;
	var $status_header = null;

	function __construct( $settings ) {
		if ( is_array( $settings ) ) {
			foreach ( $settings as $k => $v ) {
				$this->$k = $v;
			}
		}
	}

	function is_ssl() {
		if ( isset( $_SERVER['HTTPS'] ) ) {
			if ( 'on' == strtolower( $_SERVER['HTTPS'] ) ) {
				return true;
			}
			if ( '1' == $_SERVER['HTTPS'] ) {
				return true;
			}
		} elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
			return true;
		}
		return false;
	}

	function status_header( $status_header, $status_code ) {
		$this->status_header = $status_header;
		$this->status_code = $status_code;

		return $status_header;
	}

	function redirect_status( $status, $location ) {
		if ( $this->cache_redirects ) {
			$this->redirect_status = $status;
			$this->redirect_location = $location;
		}

		return $status;
	}

	function do_headers( $headers1, $headers2 = [] ) {
		// Merge the arrays of headers into one
		$headers = [];
		$keys = array_unique( array_merge( array_keys( $headers1 ), array_keys( $headers2 ) ) );
		foreach ( $keys as $k ) {
			$headers[ $k ] = [];
			if ( isset( $headers1[ $k ] ) && isset( $headers2[ $k ] ) ) {
				$headers[ $k ] = array_merge( (array) $headers2[ $k ], (array) $headers1[ $k ] );
			} elseif ( isset( $headers2[ $k ] ) ) {
				$headers[ $k ] = (array) $headers2[ $k ];
			} else {
				$headers[ $k ] = (array) $headers1[ $k ];
			}
			$headers[ $k ] = array_unique( $headers[ $k ] );
		}
		// These headers take precedence over any previously sent with the same names
		foreach ( $headers as $k => $values ) {
			$clobber = true;
			foreach ( $values as $v ) {
				header( "$k: $v", $clobber );
				$clobber = false;
			}
		}
	}

	function configure_groups() {
		// Configure the memcached client
		if ( ! $this->remote ) {
			if ( function_exists( 'wp_cache_add_no_remote_groups' ) ) {
				wp_cache_add_no_remote_groups( [ $this->group ] );
			}
		}
		if ( function_exists( 'wp_cache_add_global_groups' ) ) {
			wp_cache_add_global_groups( [ $this->group ] );
		}
	}

	// Defined here because timer_stop() calls number_format_i18n()
	function timer_stop( $display = 0, $precision = 3 ) {
		global $timestart, $timeend;
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$mtime = $mtime[1] + $mtime[0];
		$timeend = $mtime;
		$timetotal = $timeend - $timestart;
		$r = number_format( $timetotal, $precision );
		if ( $display ) {
			echo $r;
		}
		return $r;
	}

	function ob( $output ) {
		if ( $this->cancel !== false ) {

			if ( $this->add_hit_status_header ) {
				if ( $this->cache_control ) {
					header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
				}
				header( 'X-Batcache: BYPASS' );
				header( 'X-Batcache-Reason: Canceled' );
			}
			wp_cache_delete( "{$this->url_key}_genlock", $this->group );

			return $output;
		}

		// PHP5 and objects disappearing before output buffers?
		wp_cache_init();

		// Remember, $wp_object_cache was clobbered in wp-settings.php so we have to repeat this.
		$this->configure_groups();

		if ( $this->cancel !== false ) {
			wp_cache_delete( "{$this->url_key}_genlock", $this->group );
			return $output;
		}

		// Do not batcache blank pages unless they are HTTP redirects
		$output = trim( $output );

		if ( $output === '' && ( ! $this->redirect_status || ! $this->redirect_location ) ) {

			if ( $this->cache_control ) {
				header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
			}

			if ( $this->add_hit_status_header ) {
				header( 'X-Batcache: BYPASS' );
				header( 'X-Batcache-Reason: No content' );
			}

			wp_cache_delete( "{$this->url_key}_genlock", $this->group );
			return;
		}

		// Do not cache 5xx responses
		if ( isset( $this->status_code ) && intval( $this->status_code / 100 ) == 5 ) {

			if ( $this->cache_control ) {
				header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
			}

			if ( $this->add_hit_status_header ) {
				header( 'X-Batcache: BYPASS' );
				header( 'X-Batcache-Reason: Bad status code' );
			}

			wp_cache_delete( "{$this->url_key}_genlock", $this->group );
			return $output;
		}

		$this->do_variants( $this->vary );
		$this->generate_keys();

		// Construct and save the batcache
		$this->cache = [
			'output' => $output,
			'time' => isset( $_SERVER['REQUEST_TIME'] ) ? $_SERVER['REQUEST_TIME'] : time(),
			'timer' => $this->timer_stop( false, 3 ),
			'headers' => [],
			'status_header' => $this->status_header,
			'redirect_status' => $this->redirect_status,
			'redirect_location' => $this->redirect_location,
			'version' => $this->url_version,
		];

		foreach ( headers_list() as $header ) {
			list($k, $v) = array_map( 'trim', explode( ':', $header, 2 ) );
			$this->cache['headers'][ $k ][] = $v;
		}

		if ( ! empty( $this->cache['headers'] ) && ! empty( $this->uncached_headers ) ) {
			foreach ( $this->uncached_headers as $header ) {
				unset( $this->cache['headers'][ $header ] );
			}
		}

		foreach ( $this->cache['headers'] as $header => $values ) {
			// Do not cache if cookies were set
			if ( strtolower( $header ) === 'set-cookie' ) {

				if ( $this->cache_control ) {
					header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
				}

				if ( $this->add_hit_status_header ) {
					header( 'X-Batcache: BYPASS' );
					header( 'X-Batcache-Reason: Set-Cookie' );
				}

				wp_cache_delete( "{$this->url_key}_genlock", $this->group );
				return $output;
			}

			foreach ( (array) $values as $value ) {
				if ( preg_match( '/^Cache-Control:.*max-?age=(\d+)/i', "$header: $value", $matches ) ) {
					$this->max_age = intval( $matches[1] );
				}
			}
		}

		$this->cache['max_age'] = $this->max_age;

		wp_cache_set( $this->key, $this->cache, $this->group, $this->max_age + $this->seconds + 30 );

		// Unlock regeneration
		wp_cache_delete( "{$this->url_key}_genlock", $this->group );

		if ( $this->cache_control ) {
			// Don't clobber Last-Modified header if already set, e.g. by WP::send_headers()
			if ( ! isset( $this->cache['headers']['Last-Modified'] ) ) {
				header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $this->cache['time'] ) . ' GMT', true );
			}
			if ( ! isset( $this->cache['headers']['Cache-Control'] ) ) {
				header( "Cache-Control: max-age=$this->max_age, must-revalidate", false );
			}
		}

		$this->do_headers( $this->headers );

		// Add some debug info just before <head
		if ( $this->debug ) {
			$this->add_debug_just_cached();
		}

		// Pass output to next ob handler
		batcache_stats( 'batcache', 'total_page_views' );

		if ( $this->add_hit_status_header ) {
			header( 'X-Batcache: MISS' );
		}

		return $this->cache['output'];
	}

	function add_variant( $function ) {
		$key = md5( $function );
		$this->vary[ $key ] = $function;
	}

	function do_variants( $dimensions = false ) {
		// This function is called without arguments early in the page load, then with arguments during the OB handler.
		if ( $dimensions === false ) {
			$dimensions = wp_cache_get( "{$this->url_key}_vary", $this->group );
		} else {
			wp_cache_set( "{$this->url_key}_vary", $dimensions, $this->group, $this->max_age + 10 );
		}

		if ( is_array( $dimensions ) ) {
			ksort( $dimensions );
			foreach ( $dimensions as $key => $function ) {
				$fun = create_function( '', $function );
				$value = $fun();
				$this->keys[ $key ] = $value;
			}
		}
	}

	function generate_keys() {
		// ksort($this->keys); // uncomment this when traffic is slow
		$this->key = md5( serialize( $this->keys ) );
		$this->req_key = $this->key . '_reqs';
	}

	function add_debug_just_cached() {
		$generation = $this->cache['timer'];
		$bytes = strlen( serialize( $this->cache ) );
		$html = <<<HTML
<!--
	generated in $generation seconds
	$bytes bytes batcached for {$this->max_age} seconds
-->

HTML;
		$this->add_debug_html_to_output( $html );
	}

	function add_debug_from_cache() {
		$seconds_ago = time() - $this->cache['time'];
		$generation = $this->cache['timer'];
		$serving = $this->timer_stop( false, 3 );
		$expires = $this->cache['max_age'] - time() + $this->cache['time'];
		$html = <<<HTML
<!--
	generated $seconds_ago seconds ago
	generated in $generation seconds
	served from batcache in $serving seconds
	expires in $expires seconds
-->

HTML;
		$this->add_debug_html_to_output( $html );
	}

	function add_debug_html_to_output( $debug_html ) {
		// Casing on the Content-Type header is inconsistent
		foreach ( [ 'Content-Type', 'Content-type' ] as $key ) {
			if ( isset( $this->cache['headers'][ $key ][0] ) && 0 !== strpos( $this->cache['headers'][ $key ][0], 'text/html' ) ) {
				return;
			}
		}

		$head_position = strpos( $this->cache['output'], '<head' );
		if ( false === $head_position ) {
			return;
		}
		$this->cache['output'] .= "\n$debug_html";
	}
}
