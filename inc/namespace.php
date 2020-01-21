<?php

namespace Altis\Cloud;

use const Altis\ROOT_DIR;
use function Altis\get_config as get_platform_config;
use function Altis\get_environment_architecture;
use HM\Platform\XRay;

/**
 * Set up the Cloud Module.
 */
function bootstrap() {
	$config = get_config();

	if (
		$config['xray']
		&& function_exists( 'xhprof_sample_enable' )
		&& ( ! defined( 'WP_CLI' ) || ! WP_CLI )
		&& ! class_exists( 'HM\\Cavalcade\\Runner\\Runner' )
	) {
		require_once ROOT_DIR . '/vendor/humanmade/aws-xray/inc/namespace.php';
		require_once ROOT_DIR . '/vendor/humanmade/aws-xray/plugin.php';
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
}

// Load the Cavalcade Runner CloudWatch extension.
// This is loaded on the Cavalcade-Runner, not WordPress, crazy I know.
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

	global $wp_version;
	if ( version_compare( '4.6', $wp_version, '>' ) ) {
		die( 'Altis is only supported on WordPress 4.6+.' );
	}

	// Disable indexing when not in production
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
	if ( in_array( get_environment_architecture(), [ 'ec2', 'ecs' ], true ) ) {
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

	$defaults = get_platform_config()['modules']['cloud'];

	return array_merge( $defaults, $hm_platform ? $hm_platform : [] );
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
	require ROOT_DIR . '/vendor/humanmade/wordpress-pecl-memcached-object-cache/object-cache.php';

	// cache must be initted once it's included, else we'll get a fatal.
	wp_cache_init();
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
	if ( ! defined( 'WP_REDIS_USE_CACHE_GROUPS' ) ) {
		define( 'WP_REDIS_USE_CACHE_GROUPS', true );
	}

	Alloptions_Fix\bootstrap();
	\WP_Predis\add_filters();

	require ROOT_DIR . '/vendor/humanmade/wp-redis/object-cache.php';

	// cache must be initted once it's included, else we'll get a fatal.
	wp_cache_init();
}

/**
 * Load the advanced-cache dropin.
 *
 * @param  bool $should_load
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

/**
 * Load the ludicrousdb dropin.
 */
function load_db() {
	require_once ABSPATH . WPINC . '/wp-db.php';
	require_once ROOT_DIR . '/vendor/stuttter/ludicrousdb/ludicrousdb/includes/class-ludicrousdb.php';
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
 * Load the plugins in altis.
 */
function load_plugins() {
	$config = get_config();

	if ( $config['cavalcade'] ) {
		// Force DISABLE_WP_CRON for Cavalcade.
		if ( ! defined( 'DISABLE_WP_CRON' ) ) {
			define( 'DISABLE_WP_CRON', true );
		}
		require_once ROOT_DIR . '/vendor/humanmade/cavalcade/plugin.php';
		// Wait until tables have been created to bootstrap cavalcade during install.
		if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
			remove_action( 'plugins_loaded', 'HM\\Cavalcade\\Plugin\\bootstrap' );
			add_action( 'populate_options', 'HM\\Cavalcade\\Plugin\\bootstrap' );
		}
	}

	// Define TACHYON_URL, as in the Cloud environment is "always on"
	// but the constant is not defined at the infra. level as we want
	// it to be the network primary domain which isn't available
	// at the infra level current.
	if ( ! defined( 'TACHYON_URL' ) ) {
		define( 'TACHYON_URL', get_site_url( get_main_site_id( get_main_network_id() ), '/tachyon' ) );
	}

	if ( $config['s3-uploads'] ) {
		require_once ROOT_DIR . '/vendor/humanmade/s3-uploads/s3-uploads.php';
	}

	if ( $config['redis'] ) {
		require_once ROOT_DIR . '/vendor/humanmade/wp-redis/wp-redis.php';
	}

	if ( $config['aws-ses-wp-mail'] ) {
		require_once ROOT_DIR . '/vendor/humanmade/aws-ses-wp-mail/aws-ses-wp-mail.php';
	}

	if ( $config['healthcheck'] ) {
		add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_healthcheck' );
	}
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
