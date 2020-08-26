<?php
/**
 * Altis Cloud Module.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud;

use Altis;
use Aws\CloudFront\CloudFrontClient;
use Aws\Credentials;
use Aws\Credentials\CredentialProvider;
use Aws\Signature\SignatureV4;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use HM\Platform\XRay;
use Psr\Http\Message\RequestInterface;
use S3_Uploads;

/**
 * CloudFront static paths invalidation limit.
 *
 * We can only invalidate 3000 total at once, so 2000 gives us some wiggle room.
 *
 * @see purge_cdn_paths()
 */
const PATHS_INVALIDATION_LIMIT = 2000;

/**
 * CloudFront wildcard invalidation limit.
 *
 * We can only invalidate 15 total at once, so 10 gives us some wiggle room.
 *
 * @see purge_cdn_paths()
 */
const WILDCARD_INVALIDATION_LIMIT = 10;

/**
 * Set up the Cloud Module.
 */
function bootstrap() {
	$config = get_config();

	if (
		$config['xray']
		&& function_exists( 'xhprof_sample_enable' )
		&& php_sapi_name() !== 'cli'
		&& ! class_exists( 'HM\\Cavalcade\\Runner\\Runner' )
	) {
		require_once Altis\ROOT_DIR . '/vendor/humanmade/aws-xray/inc/namespace.php';
		require_once Altis\ROOT_DIR . '/vendor/humanmade/aws-xray/plugin.php';
		add_filter( 'aws_xray.redact_metadata', __NAMESPACE__ . '\\remove_xray_metadata' );
		if ( in_array( Altis\get_environment_architecture(), [ 'ec2', 'ecs' ], true ) ) {
			add_filter( 'aws_xray.trace_to_daemon', __NAMESPACE__ . '\\add_ec2_instance_data_to_xray' );
		}
		XRay\bootstrap();
	}

	if ( $config['batcache'] && ! defined( 'WP_CACHE' ) ) {
		define( 'WP_CACHE', true );
	}

	add_filter( 'wp_mail_from', function ( string $email ) use ( $config ) : string {
		return filter_var(
			$config['email-from-address'],
			FILTER_VALIDATE_EMAIL,
			FILTER_NULL_ON_FAILURE
		) ?? $email;
	}, 1 );

	// Load the platform as soon as WP is loaded.
	add_action( 'enable_wp_debug_mode_checks', __NAMESPACE__ . '\\load_platform' );

	if ( class_exists( 'HM\\Cavalcade\\Runner\\Runner' ) && $config['cavalcade'] ) {
		boostrap_cavalcade_runner();
	}

	// We use install.php as the health check at the infrastructure level, so requests must
	// succeed to /wp-admin/install.php. This is mostly because of legacy reasons. There's
	// an issue with loading /wp-admin/install.php on a domain that doesn't exist when
	// SUBDOMAIN_INSTALL is defined as true. The requests to the health check are made with
	// the server's IP address, so naturally it doesn't recognize the site as being installed.
	// That coupled with the SUBDOMAIN_INSTALL declaration will cause a 500 error, breaking the
	// health check.
	//
	// We define SUBDOMAIN_INSTALL to false early, so even if client code from .config/load.php
	// is configuring the network to be an subdomain install, we'll override that functionality
	// just for the requests to wp-admin/install.php.
	//
	// To avoid warnings, client code should check if SUBDOMAIN_INSTALL is already defined before
	// defining it.
	if (
		$config['healthcheck'] &&
		isset( $_SERVER['REQUEST_URI'] ) &&
		$_SERVER['REQUEST_URI'] === '/wp-admin/install.php'
	) {
		define( 'MULTISITE', false );
		define( 'SUBDOMAIN_INSTALL', false );
	}

	// Display environment details in admin sidebar.
	Environment_Indicator\bootstrap();

	// Sign ElasticSearch HTTP requests and log errors.
	add_action( 'http_api_debug', __NAMESPACE__ . '\\log_elasticsearch_request_errors', 10, 5 );
	add_filter( 'http_request_args', __NAMESPACE__ . '\\on_http_request_args', 11, 2 );
}

/**
 * Load the Cavalcade Runner CloudWatch extension.
 * This is loaded on the Cavalcade-Runner, not WordPress, crazy I know.
 */
function boostrap_cavalcade_runner() {
	if ( defined( 'HM_ENV' ) && HM_ENV ) {
		require_once __DIR__ . '/cavalcade_runner_to_cloudwatch/namespace.php';
		Cavalcade_Runner_To_CloudWatch\bootstrap();
	}
}

/**
 * Bootstrap the altis pieces.
 *
 * This function is hooked into to enable_wp_debug_mode_checks so we have to return the value
 * that was passed in at the end of the function.
 *
 * @param bool $wp_debug_enabled True if WP_DEBUG is defined and set to true.
 * @return bool
 */
function load_platform( $wp_debug_enabled ) {
	$config = get_config();

	/**
	 * In Cloud, the User Agent is not available via the headers, as it is stripped at the CDN level. This is to
	 * preserve cache-key generation, as it's not possible to get access to headers that will cause a highly
	 * unique cache key.
	 *
	 * The $_SERVER['HTTP_USER_AGENT'] must still be set, because WordPress and other things will test against
	 * the user agent to enable things like the visual editor.
	 */
	if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) || $_SERVER['HTTP_USER_AGENT'] === 'Amazon CloudFront' ) {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.86 Safari/537.36';
	}

	if ( $config['memcached'] ) {
		load_object_cache_memcached();
	} elseif ( $config['redis'] ) {
		load_object_cache_redis();
	}

	if ( $config['ludicrousdb'] ) {
		load_db();
	}

	if ( $config['cdn-media-purge'] ) {
		load_cdn_media_purge();
	}

	global $wp_version;
	if ( version_compare( '4.6', $wp_version, '>' ) ) {
		die( 'Altis is only supported on WordPress 4.6+.' );
	}

	// Disable indexing when not in production.
	$disable_indexing = (
		( ! defined( 'HM_ENV_TYPE' ) || HM_ENV_TYPE !== 'production' )
		&&
		( ! defined( 'HM_DISABLE_INDEXING' ) || HM_DISABLE_INDEXING )
	);
	if ( $disable_indexing ) {
		add_action( 'pre_option_blog_public', '__return_zero' );
	}

	// Reflect CloudFront headers pre-batcache.
	reflect_cloudfront_headers();

	// Load Batcache.
	add_filter( 'enable_loading_advanced_cache_dropin', __NAMESPACE__ . '\\load_advanced_cache', 10, 1 );

	// Load infrastructure plugins.
	add_action( 'muplugins_loaded', __NAMESPACE__ . '\\load_plugins', 0 );

	// Remove plugin install / update caps on AWS.
	if ( in_array( Altis\get_environment_architecture(), [ 'ec2', 'ecs' ], true ) ) {
		add_filter( 'map_meta_cap', __NAMESPACE__ . '\\disable_install_capability', 10, 2 );
	}

	// Load logging features.
	require_once __DIR__ . '/ses_to_cloudwatch/namespace.php';
	require_once __DIR__ . '/performance_optimizations/namespace.php';
	require_once __DIR__ . '/cloudwatch_logs/namespace.php';

	SES_To_CloudWatch\bootstrap();
	CloudWatch_Logs\bootstrap();
	Performance_Optimizations\bootstrap();

	if ( $config['php-errors-to-cloudwatch'] ) {
		require_once __DIR__ . '/cloudwatch_error_handler/namespace.php';
		CloudWatch_Error_Handler\bootstrap();
	}

	if ( $config['audit-log-to-cloudwatch'] ) {
		require_once __DIR__ . '/audit_log_to_cloudwatch/namespace.php';
		Audit_Log_To_CloudWatch\bootstrap();
	}

	return $wp_debug_enabled;
}

/**
 * Get the config for altis for which features to enable.
 *
 * @return array
 */
function get_config() {
	global $hm_platform;

	$defaults = Altis\get_config()['modules']['cloud'];

	return array_merge( $defaults, $hm_platform ? $hm_platform : [] );
}

/**
 * Get the URL to the elasticsearch cluster.
 *
 * The URL will have no trailing slash.
 *
 * @return string|null
 */
function get_elasticsearch_url() : ?string {
	if ( ! defined( 'ELASTICSEARCH_HOST' ) ) {
		return null;
	}
	$host = sprintf( '%s://%s:%d', ELASTICSEARCH_PORT === 443 ? 'https' : 'http', ELASTICSEARCH_HOST, ELASTICSEARCH_PORT );
	return $host;
}

/**
 * Process HTTP request arguments.
 *
 * @param array $args Request arguments.
 * @param string $url Request URL.
 * @return array
 */
function on_http_request_args( array $args, string $url ) : array {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
	$host = parse_url( $url, PHP_URL_HOST );

	if ( ! defined( 'ELASTICSEARCH_HOST' ) || ELASTICSEARCH_HOST !== $host ) {
		return $args;
	}

	if ( Altis\get_environment_type() === 'local' || ! in_array( Altis\get_environment_architecture(), [ 'ec2', 'ecs' ], true ) ) {
		return $args;
	}

	// Request already signed.
	// Note that this is here for back compat with the search module's request signing code.
	if ( isset( $args['headers']['Authorization'] ) ) {
		return $args;
	}

	return sign_wp_request( $args, $url );
}

/**
 * Sign requests made to Elasticsearch.
 *
 * @param array $args Request arguments.
 * @param string $url Request URL.
 * @return array
 */
function sign_wp_request( array $args, string $url ) : array {
	if ( isset( $args['headers']['Host'] ) ) {
		unset( $args['headers']['Host'] );
	}
	if ( is_array( $args['body'] ) ) {
		$args['body'] = http_build_query( $args['body'], null, '&' );
	}
	$request = new Request( $args['method'], $url, $args['headers'], $args['body'] );
	$signed_request = sign_psr7_request( $request );
	$args['headers']['Authorization'] = $signed_request->getHeader( 'Authorization' )[0];
	$args['headers']['X-Amz-Date'] = $signed_request->getHeader( 'X-Amz-Date' )[0];
	if ( $signed_request->getHeader( 'X-Amz-Security-Token' ) ) {
		$args['headers']['X-Amz-Security-Token'] = $signed_request->getHeader( 'X-Amz-Security-Token' )[0];
	}
	return $args;
}

/**
 * Sign a request object with authentication headers for sending to Elasticsearch.
 *
 * @param RequestInterface $request The request object to sign.
 * @return RequestInterface
 */
function sign_psr7_request( RequestInterface $request ) : RequestInterface {
	if ( Altis\get_environment_type() === 'local' ) {
		return $request;
	}

	$signer = new SignatureV4( 'es', HM_ENV_REGION );
	if ( defined( 'ELASTICSEARCH_AWS_KEY' ) ) {
		$credentials = new Credentials\Credentials( ELASTICSEARCH_AWS_KEY, ELASTICSEARCH_AWS_SECRET );
	} else {
		$provider = CredentialProvider::defaultProvider();
		$credentials = call_user_func( $provider )->wait();
	}
	$signed_request = $signer->signRequest( $request, $credentials );

	return $signed_request;
}

/**
 * Log ElasticSearch request errors.
 *
 * @param array|WP_Error $response Response data.
 * @param string $context The http_api_debug action context.
 * @param string $class The HTTP transport class name.
 * @param array $parsed_args The request arguments.
 * @param string $url The request URL.
 * @return void
 */
function log_elasticsearch_request_errors( $response, string $context, string $class, array $parsed_args, string $url ) {
	if ( $context !== 'response' ) {
		return;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
	$host = parse_url( $url, PHP_URL_HOST );
	if ( ! defined( 'ELASTICSEARCH_HOST' ) || ELASTICSEARCH_HOST !== $host ) {
		return;
	}

	$request_response_code = (int) wp_remote_retrieve_response_code( $response );
	$is_valid_res = ( $request_response_code >= 200 && $request_response_code <= 299 );

	if ( is_wp_error( $response ) ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( sprintf( 'Error in ElasticSearch request: %s (%s)', $response->get_error_message(), $response->get_error_code() ), E_USER_WARNING );
	} elseif ( ! $is_valid_res ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( sprintf( 'Error in ElasticSearch request: %s (%s)', wp_remote_retrieve_body( $response ), $request_response_code ), E_USER_WARNING );
	}
}

/**
 * Load the object cache.
 *
 * Check the object caching configuration and load either memcached
 * or redis as appropriate.
 *
 * @deprecated 1.0.1 Object caching setup moved to dedicated functions.
 */
function load_object_cache() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		trigger_error(
			sprintf(
				'%1$s is deprecated since version %2$s! Use %3$s instead.',
				__FUNCTION__,
				'1.0.1',
				'load_object_cache_*()'
			)
		);
	}
	$config = get_config();

	if ( $config['memcached'] ) {
		load_object_cache_memcached();
	} elseif ( $config['redis'] ) {
		load_object_cache_redis();
	}
}

/**
 * Load the Memcached Object Cache dropin.
 */
function load_object_cache_memcached() {
	wp_using_ext_object_cache( true );
	require Altis\ROOT_DIR . '/vendor/humanmade/wordpress-pecl-memcached-object-cache/object-cache.php';

	// cache must be initted once it's included, else we'll get a fatal.
	wp_cache_init();
}

/**
 * Load cloudfront media purge.
 */
function load_cdn_media_purge() {
	Cloudfront_Media_Purge\bootstrap();
}

/**
 * Load the Redis Object Cache dropin.
 */
function load_object_cache_redis() {
	wp_using_ext_object_cache( true );
	require __DIR__ . '/alloptions_fix/namespace.php';
	if ( ! defined( 'WP_REDIS_DISABLE_FAILBACK_FLUSH' ) ) {
		define( 'WP_REDIS_DISABLE_FAILBACK_FLUSH', true );
	}

	Alloptions_Fix\bootstrap();
	\WP_Predis\add_filters();

	require Altis\ROOT_DIR . '/vendor/humanmade/wp-redis/object-cache.php';

	// cache must be initted once it's included, else we'll get a fatal.
	wp_cache_init();
}

/**
 * Load the advanced-cache dropin.
 *
 * @param bool $should_load If true WordPress will load the advanced cache dropin.
 * @return bool
 */
function load_advanced_cache( $should_load ) {
	$config = get_config();

	if ( ! $should_load || ! $config['batcache'] ) {
		return $should_load;
	}

	// Load and configure Batcache.
	require __DIR__ . '/page_cache/namespace.php';
	Page_Cache\bootstrap();
}

/**
 * Load the ludicrousdb dropin.
 */
function load_db() {
	require_once ABSPATH . WPINC . '/wp-db.php';
	require_once Altis\ROOT_DIR . '/vendor/stuttter/ludicrousdb/ludicrousdb/includes/class-ludicrousdb.php';
	require_once __DIR__ . '/class-db.php';
	if ( ! defined( 'DB_CHARSET' ) ) {
		define( 'DB_CHARSET', 'utf8mb4' );
	}
	if ( ! defined( 'DB_COLLATE' ) ) {
		define( 'DB_COLLATE', 'utf8mb4_unicode_520_ci' );
	}
	global $wpdb;
	$wpdb = new DB();
	$wpdb->add_database( [
		'read' => 2,
		'write' => true,
		'host' => DB_HOST,
		'name' => DB_NAME,
		'user' => DB_USER,
		'password' => DB_PASSWORD,
	] );

	if ( defined( 'DB_READ_REPLICA_HOST' ) && DB_READ_REPLICA_HOST ) {
		$wpdb->add_database( [
			'read' => 1,
			'write' => false,
			'host' => DB_READ_REPLICA_HOST,
			'name' => DB_NAME,
			'user' => DB_USER,
			'password' => DB_PASSWORD,
		] );
	}
}

/**
 * Return the site URL for the main site on the network.
 *
 * @param string $path Optional path to append to URL.
 * @return string
 */
function get_main_site_url( string $path = '' ) : string {
	return get_site_url( get_main_site_id( get_main_network_id() ), $path );
}

/**
 * Load the plugins in altis.
 */
function load_plugins() {
	$config = get_config();

	if ( $config['cavalcade'] ) {
		// Force DISABLE_WP_CRON for Cavalcade.
		if ( ! defined( 'DISABLE_WP_CRON' ) ) {
			define( 'DISABLE_WP_CRON', true );
		}
		require_once Altis\ROOT_DIR . '/vendor/humanmade/cavalcade/plugin.php';
	}

	// Define TACHYON_URL, as in the Cloud environment is "always on"
	// but the constant is not defined at the infra. level as we want
	// it to default to the network primary domain which isn't available
	// at the infra level currently.
	if ( ! defined( 'TACHYON_URL' ) ) {
		// Override the default host name for Tachyon to match the current site.
		add_filter( 'tachyon_url', __NAMESPACE__ . '\\set_tachyon_hostname', 20 );
		define( 'TACHYON_URL', get_main_site_url( '/tachyon' ) );
	}

	if ( $config['s3-uploads'] ) {
		if ( in_array( Altis\get_environment_architecture(), [ 'ec2', 'ecs' ], true ) ) {
			add_filter( 'upload_dir', __NAMESPACE__ . '\\set_s3_uploads_bucket_url_hostname', 20 );
		}
		require_once Altis\ROOT_DIR . '/vendor/humanmade/s3-uploads/s3-uploads.php';
	}

	if ( $config['redis'] ) {
		require_once Altis\ROOT_DIR . '/vendor/humanmade/wp-redis/wp-redis.php';
	}

	if ( $config['aws-ses-wp-mail'] ) {
		add_filter( 'aws_ses_wp_mail_ses_client_params', __NAMESPACE__ . '\\configure_aws_ses_client' );
		require_once Altis\ROOT_DIR . '/vendor/humanmade/aws-ses-wp-mail/aws-ses-wp-mail.php';
	}

	if ( $config['healthcheck'] ) {
		add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_healthcheck' );
	}
}

/**
 * Ensure Tachyon URL is using the current site hostname.
 *
 * @param string $tachyon_url The current tachyon URL.
 * @return string The updated Tachyon URL.
 */
function set_tachyon_hostname( string $tachyon_url ) : string {
	$tachyon_host = wp_parse_url( $tachyon_url, PHP_URL_HOST );
	$current_host = wp_parse_url( site_url(), PHP_URL_HOST );

	if ( ! $tachyon_host ) {
		trigger_error( sprintf( 'Error parsing Tachyon URL: %s', esc_url_raw( $tachyon_url ) ), E_USER_WARNING );
		return $tachyon_url;
	}

	if ( ! $current_host ) {
		trigger_error( sprintf( 'Error parsing current site URL: %s', esc_url_raw( site_url() ) ), E_USER_WARNING );
		return $tachyon_url;
	}

	// Only do the replacement if the host name is not a subdomain of the Tachyon host.
	if ( substr( $current_host, -1 * strlen( $tachyon_host ) ) !== $tachyon_host ) {
		return str_replace( "://{$tachyon_host}", "://{$current_host}", $tachyon_url );
	}

	return $tachyon_url;
}

/**
 * Ensure the S3 Uploads Bucket URL matches the current site hostname.
 *
 * @param array $dirs Uploads directories array.
 * @return array
 */
function set_s3_uploads_bucket_url_hostname( array $dirs ) : array {
	$s3_uploads = S3_Uploads::get_instance();

	$primary_host = wp_parse_url( get_main_site_url(), PHP_URL_HOST );
	$s3_host = wp_parse_url( $s3_uploads->get_s3_url(), PHP_URL_HOST );
	$current_host = wp_parse_url( site_url(), PHP_URL_HOST );

	if ( ! $primary_host ) {
		trigger_error( sprintf( 'Error parsing main site URL: %s', esc_url_raw( get_main_site_url() ) ), E_USER_WARNING );
		return $dirs;
	}

	if ( ! $s3_host ) {
		trigger_error( sprintf( 'Error parsing S3 bucket URL: %s', esc_url_raw( $s3_uploads->get_s3_url() ) ), E_USER_WARNING );
		return $dirs;
	}

	if ( ! $current_host ) {
		trigger_error( sprintf( 'Error parsing site URL: %s', esc_url_raw( site_url() ) ), E_USER_WARNING );
		return $dirs;
	}

	// To support 3rd party CDNs leave the host names as is if the direct
	// amazonaws.com URL is in use.
	if ( strpos( $s3_host, '.amazonaws.com' ) !== false ) {
		return $dirs;
	}

	// Ensure uploads host at least matches primary site host.
	if ( $s3_host !== $primary_host ) {
		$dirs['url'] = str_replace( "://{$s3_host}", "://{$primary_host}", $dirs['url'] );
		$dirs['baseurl'] = str_replace( "://{$s3_host}", "://{$primary_host}", $dirs['baseurl'] );
	}

	// Only do the replacement if the host name is not a subdomain of the S3 host.
	if ( substr( $current_host, -1 * strlen( $primary_host ) ) !== $primary_host ) {
		$dirs['url'] = str_replace( "://{$primary_host}", "://{$current_host}", $dirs['url'] );
		$dirs['baseurl'] = str_replace( "://{$primary_host}", "://{$current_host}", $dirs['baseurl'] );
	}

	return $dirs;
}

/**
 * Configure AWS SES Sending region.
 *
 * @param array $params The AWS SES Email Client parameters.
 * @return array
 */
function configure_aws_ses_client( array $params ) : array {
	$config = get_config();

	// Set the sending region from config if provided.
	if ( $config['email-region'] ) {
		$params['region'] = $config['email-region'];
	}

	return $params;
}

/**
 * Load and run healthcheck.
 *
 * Runs the Cloud healthcheck at /healthcheck/
 */
function load_healthcheck() {
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		return;
	}
	Healthcheck\bootstrap();
	Healthcheck\Cavalcade\bootstrap();
}

/**
 * Disable the install plugins / themes capability.
 *
 * In the cloud context it's not possible to do this so this hides
 * non functional UI.
 *
 * @param array $caps The required capabilities list.
 * @param string $cap The current capability being checked.
 * @return array
 */
function disable_install_capability( array $caps, string $cap ) : array {
	if ( ! in_array( $cap, [ 'install_plugins', 'install_themes' ], true ) ) {
		return $caps;
	}

	// This is how you disable a capability via map meta cap.
	$caps[] = 'do_not_allow';
	return $caps;
}

/**
 * CloudFront can pass headers to the origin that provide information
 * useful for modifying responses or performing redirects and other
 * logic.
 *
 * This function reflects those headers back in responses for
 * client side usage.
 */
function reflect_cloudfront_headers() {
	$headers = [
		'CloudFront-Is-Desktop-Viewer',
		'CloudFront-Is-Mobile-Viewer',
		'CloudFront-Is-SmartTV-Viewer',
		'CloudFront-Is-Tablet-Viewer',
		'CloudFront-Viewer-Country',
	];

	foreach ( $headers as $header ) {
		$header_key = 'HTTP_' . str_replace( '-', '_', strtoupper( $header ) );
		if ( isset( $_SERVER[ $header_key ] ) ) {
			header( sprintf( 'X-%s: %s', $header, $_SERVER[ $header_key ] ) );
		}
	}
}

/**
 * Remove unneeded data from the XRay metadata.
 *
 * We have a lot of superfluous information in the $_SERVER super-global
 * that doesn't need to be sent to xray. It's a lot of wasted bytes to
 * send to xray on every request.
 *
 * @param array $metadata XRay request metadata.
 * @return array
 */
function remove_xray_metadata( array $metadata ) : array {
	if ( ! isset( $metadata['$_SERVER'] ) ) {
		return $metadata;
	}

	$metadata['$_SERVER'] = array_filter( $metadata['$_SERVER'], function ( string $key ) : bool {
		// Allow all HTTP headers.
		if ( strpos( $key, 'HTTP_' ) === 0 ) {
			return true;
		}

		$allowed_keys = [
			'REQUEST_URI',
			'SERVER_ADDR',
			'REMOTE_ADDR',
			'CONTENT_LENGTH',
			'CONTENT_TYPE',
			'REQUEST_METHOD',
			'QUERY_STRING',
			'HTTPS',
			'PHP_SELF',
			'REQUEST_TIME_FLOAT',
			'REQUEST_TIME',
		];

		return in_array( $key, $allowed_keys, true );
	}, ARRAY_FILTER_USE_KEY );

	return $metadata;
}

/**
 * Add the XRay logging callback to the AWS SDK HTTP configuration.
 *
 * @param array $params AWS SDK parameters.
 * @return array
 */
function add_aws_sdk_xray_callback( array $params ) : array {
	$params['stats'] = true;
	$params['http']['on_stats'] = __NAMESPACE__ . '\\on_request_stats';
	return $params;
}

/**
 * Callback function for GuzzleHTTP's `on_stats` param.
 *
 * This allows us to send all AWS SDK requests to xray
 *
 * @param TransferStats $stats Object of stats for a Guzzle request.
 */
function on_request_stats( TransferStats $stats ) {
	if ( ! function_exists( 'HM\\Platform\\XRay\\on_aws_guzzle_request_stats' ) ) {
		return;
	}

	XRay\on_aws_guzzle_request_stats( $stats );
}

/**
 * Get the EC2 Instance metadata.
 *
 * This will cause a remote request to the metadata service when the cache is empty.
 *
 * @return array $array(
 *    accountId: string,
 *    architecture: string,
 *    availabilityZone: string,
 *    billingProducts: string,
 *    devpayProductCodes: string,
 *    marketplaceProductCodes: string,
 *    imageId: string,
 *    instanceId: string,
 *    instanceType: string,
 *    kernelId: string,
 *    pendingTime: string,
 *    privateIp: string,
 *    ramdiskId: string,
 *    region: string,
 *    version: string,
 * )
 */
function get_ec2_instance_metadata() : array {
	$has_cache = false;
	$cache_key = 'altis.ec2_instance_metadata';
	// Use apcu_* as we only want to store the cache on the current server,
	// not across all servers (wp_cache_*).
	$cached_data = apcu_fetch( $cache_key, $has_cache );
	if ( $has_cache ) {
		return $cached_data;
	}

	$client = new Client();

	try {
		$request = $client->request( 'GET', 'http://169.254.169.254/latest/dynamic/instance-identity/document', [
			'timeout' => 1,
			'on_stats' => __NAMESPACE__ . '\\on_request_stats',
		] );
	} catch ( Exception $e ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( sprintf( 'Unable to get instance metadata. Error: %s', $e->getMessage() ), E_USER_NOTICE );
		apcu_store( $cache_key, [] );
		return [];
	}

	if ( $request->getStatusCode() !== 200 ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( sprintf( 'Unable to get instance metadata. Returned response code: %s', $request->getStatusCode() ), E_USER_NOTICE );
		apcu_store( $cache_key, [] );
		return [];
	}

	$metadata = json_decode( $request->getBody(), true );

	if ( ! $metadata ) {
		$metadata = [];
	}

	apcu_store( $cache_key, $metadata );

	return $metadata;
}

/**
 * Add the EC2 instance data to the Xray root segment.
 *
 * This function is called pre-WordPress load, so we don't have access
 * to all the WordPress functions, hence using Guzzle.
 *
 * @param array $trace EC2 trace data.
 * @return array
 */
function add_ec2_instance_data_to_xray( array $trace ) : array {
	// Only add instance information to the root trace.
	if ( ! empty( $trace['parent_id'] ) ) {
		return $trace;
	}

	$metadata = get_ec2_instance_metadata();
	if ( ! $metadata ) {
		return $trace;
	}

	$trace['aws']['ec2']['availability_zone'] = $metadata['availabilityZone'];
	$trace['aws']['instance_id'] = $metadata['instanceId'];

	if ( Altis\get_environment_architecture() === 'ecs' ) {
		$trace['aws']['ecs']['container'] = php_uname( 'n' );
		$trace['origin'] = 'AWS::ECS::Container';
	}

	return $trace;
}

/**
 * Return an AWS CloudFront Client instance.
 *
 * @return \Aws\CloudFront\CloudFrontClient
 */
function get_cloudfront_client() : CloudFrontClient {
	return Altis\get_aws_sdk()->createCloudFront( [
		'version' => '2019-03-26',
	] );
}

/**
 * Create purge request to invalidate CDN cache.
 *
 * This is limited to 10 wildcard invalidations and 2000 static path
 * invalidations due to underlying limits in the CloudFront API. Consult the
 * Altis Cloud team if you need to invalidate en masse.
 *
 * @see https://www.altis-dxp.com/resources/docs/cloud/cdn-purge/
 *
 * @param array $paths_patterns A list of the paths that you want to invalidate.
 *                              The path is relative to the CDN host, A leading / is optional.
 *                              e.g  for http://altis-dxp.com/images/image2.jpg
 *                              specify images/image2.jpg or /images/image2.jpg
 *
 *                              You can also invalidate multiple files simultaneously by using the * wildcard.
 *                              The *, which replaces 0 or more characters, must be the last character in the invalidation path.
 *                              e.g /images/* - will invalidate all files in a directory.
 *
 * @return bool Returns true if invalidation successfully created, false on failure.
 */
function purge_cdn_paths( array $paths_patterns ) : bool {
	static $invalidated_static = 0;
	static $invalidated_wild = 0;
	foreach ( $paths_patterns as $pattern ) {
		if ( strpos( $pattern, '*' ) !== false ) {
			$invalidated_wild++;
		} else {
			$invalidated_static++;
		}
	}
	if ( $invalidated_static > PATHS_INVALIDATION_LIMIT || $invalidated_wild > WILDCARD_INVALIDATION_LIMIT ) {
		trigger_error(
			sprintf(
				'Cannot invalidate more than %d wildcards or %d items per request, contact Altis support',
				WILDCARD_INVALIDATION_LIMIT,
				PATHS_INVALIDATION_LIMIT
			),
			E_USER_WARNING
		);

		return false;
	}

	$client = get_cloudfront_client();

	$distribution_id = '';

	if ( defined( 'CLOUDFRONT_DISTRIBUTION_ID' ) ) {
		$distribution_id = CLOUDFRONT_DISTRIBUTION_ID;
	}

	/**
	 * Filters the CloudFront Distribution ID used when purging CDN paths.
	 *
	 * @param string $distribution_id The ID to set.
	 */
	$distribution_id = apply_filters( 'altis.cloud.cdn_distribution_id', $distribution_id );

	if ( empty( $distribution_id ) ) {
		trigger_error( 'Empty cloudfront distribution id for purge request.', E_USER_WARNING );

		return false;
	}

	try {
		$client->createInvalidation( [
			'DistributionId'    => $distribution_id,
			'InvalidationBatch' => [
				'Paths'           => [
					'Items'    => $paths_patterns,
					'Quantity' => count( $paths_patterns ),
				],
				'CallerReference' => sha1( time() . wp_json_encode( $paths_patterns ) ),
			],
		] );
	} catch ( Exception $e ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( sprintf( 'Failed to create purge request for CloudFront, error %s (%s)', $e->getMessage(), $e->getCode() ), E_USER_WARNING );
		return false;
	}

	return true;
}
