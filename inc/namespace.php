<?php

namespace HM\Platform\Cloud;

use const HM\Platform\ROOT_DIR;
use function HM\Platform\get_environment_architecture;
use function HM\Platform\get_config as get_platform_config;

// Load the Cavalcade Runner CloudWatch extension.
// This is loaded on the Cavalcade-Runner, not WordPress, crazy I know.
function boostrap_cavalcade_runner() {
	if ( defined( 'HM_ENV' ) && HM_ENV ) {
		require_once __DIR__ . '/cavalcade-runner-to-cloudwatch/plugin.php';
	}
}

/**
 * Bootstrap the platform pieces.
 *
 * This function is hooked into to enable_wp_debug_mode_checks so we have to return the value
 * that was passed in at the end of the function.
 */
function bootstrap( $wp_debug_enabled ) {
	$config = get_config();

	if ( ! defined( 'WP_CACHE' ) ) {
		define( 'WP_CACHE', true );
	}

	/**
	 * In Cloud, the User Agent is not available via the headers, as it is stripped at the CDN level. This is to
	 * preserve cache-key generation, as it's not possible to get access to headers that will cause a highly
	 * unique cache key.
	 *
	 * The $_SERVER['HTTP_USER_AGENT'] must still be set, because WordPress and other things will test against
	 * the user agent to enable things like the visual editor.
	 */
	if ( $_SERVER['HTTP_USER_AGENT'] === 'Amazon CloudFront' ) {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.86 Safari/537.36';
	}

	load_object_cache();
	load_db();

	global $wp_version;
	if ( version_compare( '4.6', $wp_version, '>' ) ) {
		die( 'HM Platform is only supported on WordPress 4.6+.' );
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

	add_filter( 'enable_loading_advanced_cache_dropin', __NAMESPACE__ . '\\load_advanced_cache', 10, 1 );
	add_action( 'muplugins_loaded', __NAMESPACE__ . '\\load_plugins' );

	require_once __DIR__ . '/ses-to-cloudwatch/plugin.php';
	require_once __DIR__ . '/performance_optimizations/namespace.php';
	require_once __DIR__ . '/cloudwatch_logs/namespace.php';

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
 * Get the config for hm-platform for which features to enable.
 *
 * @return array
 */
function get_config() {
	global $hm_platform;

	$defaults = get_platform_config()['modules']['cloud'];

	return array_merge( $defaults, $hm_platform ? $hm_platform : [] );
}

/**
 * Load the Object Cache dropin.
 */
function load_object_cache() {
	wp_using_ext_object_cache( true );
	$config = get_config();

	if ( $config['memcached'] ) {
		require dirname( __DIR__ ) . '/dropins/wordpress-pecl-memcached-object-cache/object-cache.php';
	} else {
		require __DIR__ . '/alloptions_fix/namespace.php';
		if ( ! defined( 'WP_REDIS_DISABLE_FAILBACK_FLUSH' ) ) {
			define( 'WP_REDIS_DISABLE_FAILBACK_FLUSH', true );
		}

		Alloptions_Fix\bootstrap();
		\WP_Predis\add_filters();

		require ROOT_DIR . '/vendor/humanmade/wp-redis/object-cache.php';
	}

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

	require dirname( __DIR__ ) . '/dropins/batcache/advanced-cache.php';
}

/**
 * Load the db dropin.
 */
function load_db() {
	require_once ABSPATH . WPINC . '/wp-db.php';
	require_once dirname( __DIR__ ) . '/dropins/ludicrousdb/ludicrousdb/includes/class-ludicrousdb.php';
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
 * Get available platform plugins.
 *
 * @return array Map of plugin ID => path relative to plugins directory.
 */
function get_available_plugins() {
	return [
		'aws-ses-wp-mail' => 'aws-ses-wp-mail/aws-ses-wp-mail.php',
		'healthcheck'     => 'healthcheck/plugin.php',
	];
}

/**
 * Load the plugins in hm-platform.
 */
function load_plugins() {
	$config = get_config();

	if ( $config['cavalcade'] ) {
		// Force DISABLE_WP_CRON for Cavalcade.
		if ( ! defined( 'DISABLE_WP_CRON' ) ) {
			define( 'DISABLE_WP_CRON', true );
		}
		require_once ROOT_DIR . '/vendor/humanmade/cavalcade/plugin.php';
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

	foreach ( get_available_plugins() as $plugin => $file ) {
		if ( ! $config[ $plugin ] ) {
			continue;
		}

		require dirname( __DIR__ ) . '/plugins/' . $file;
	}
}
