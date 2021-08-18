<?php
/**
 * Altis Cloud Healthchecks.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Healthcheck;

use Altis\Cloud;
use WP_CLI;
use WP_Error;

/**
 * Handle healthcheck requests.
 *
 * @return void
 */
function bootstrap() {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once __DIR__ . '/class-cli-command.php';
		WP_CLI::add_command( 'healthcheck', __NAMESPACE__ . '\\CLI_Command' );
	}

	if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	// Defer app level healthchecks until after plugins have laoded.
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_healthcheck', 100 );

	// Run instance healthcheck immediately.
	if ( strpos( $_SERVER['REQUEST_URI'], '/__instance_healthcheck' ) !== false ) {
		output_page( run_instance_checks() );
	}
}

/**
 * Loads the application healthcheck.
 *
 * @return void
 */
function load_healthcheck() {
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		return;
	}

	Cavalcade\bootstrap();

	if ( strpos( $_SERVER['REQUEST_URI'], '/__healthcheck' ) !== false ) {
		output_page( run_checks() );
		return;
	}
}

/**
 * Check if a value is an error.
 *
 * @param mixed $value The variable to check.
 * @return boolean
 */
function is_error( $value ) : bool {
	if ( function_exists( 'is_wp_error' ) ) {
		return is_wp_error( $value );
	}
	return $value !== true;
}

/**
 * Generate healthcheck HTML page.
 *
 * NOTE: Do not use any WP specific functions in this function, the
 * instance healthcheck loads _before_ WP is bootstrap.
 *
 * @param array $checks The checks to output.
 * @return void
 */
function output_page( array $checks ) {
	$passed = true;
	foreach ( $checks as $check ) {
		if ( is_error( $check ) ) {
			$passed = false;
			break;
		}
	}

	if ( ! $passed ) {
		http_response_code( 500 );
	} else {
		http_response_code( 200 );
	}

	if ( ! headers_sent() ) {
		header_remove( 'Last-Modified' );
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', true );
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0', true );
	}

	$format = 'html';
	if ( ! empty( $_SERVER['HTTP_ACCEPT'] ) && $_SERVER['HTTP_ACCEPT'] === 'application/json' ) {
		$format = 'json';
	}
	if ( isset( $_GET['_accept'] ) && $_GET['_accept'] === 'json' ) {
		$format = 'json';
	}

	$json_response = [
		'status' => $passed ? 'ok' : 'failed',
		'checks' => $checks,
	];

	if ( $format === 'json' ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		echo json_encode( $json_response );
		exit;
	}
	?>
	<html>
		<head>
			<title>Status: <?php echo $passed ? 'OK' : 'Failure!' ?></title>
		</head>
		<img src="https://humanmade.github.io/hm-pattern-library/assets/images/logos/logo-small-red.svg" style="height: 30px; vertical-align: middle" />
		<table>
			<?php foreach ( $checks as $check => $status ) : ?>
				<tr>
					<td>
						<?php echo htmlspecialchars( $check ) ?>
					</td>
					<td>
						<?php echo is_error( $status ) ? sprintf( '%s (code: %s)', esc_html( $status->get_error_message() ), esc_html( $status->get_error_code() ) ) : 'OK' ?>
					</td>
				</tr>
			<?php endforeach ?>
		</table>
	</html>
	<?php
	exit;
}

/**
 * Run all health checks.
 *
 * @return array
 */
function run_checks() : array {
	$checks = [
		'mysql' => run_mysql_healthcheck(),
		'object-cache' => run_object_cache_healthcheck(),
		'cron-waiting' => run_cron_healthcheck(),
		'cron-canary' => Cavalcade\check_health(),
		'elasticsearch' => run_elasticsearch_healthcheck(),
	];
	$checks = apply_filters( 'altis_healthchecks', $checks );

	return $checks;
}

/**
 * Run all instance health checks.
 *
 * @return array
 */
function run_instance_checks() : array {
	$checks = [
		'php' => true,
	];

	/**
	 * Filters Instance Healthchecks response.
	 *
	 * @param array $checks List of instance checks.
	 */
	return apply_filters( 'altis_instance_healthchecks', $checks );
}

/**
 * Check mysql health.
 */
function run_mysql_healthcheck() {
	global $wpdb;

	if ( ! empty( $wpdb->last_error ) ) {
		return new WP_Error( 'mysql-has-error', $wpdb->last_error );
	}

	$process_list = $wpdb->get_results( 'show full processlist' );
	if ( ! $process_list ) {
		return new WP_Error( 'mysql-processlist-failied', 'Unable to get process list. ' . $wpdb->last_error );
	}

	return true;
}

/**
 * Check object cache health.
 */
function run_object_cache_healthcheck() {
	global $wp_object_cache, $wpdb;

	if ( method_exists( $wp_object_cache, 'getStats' ) && ! empty( $wp_object_cache->getStats() ) ) {
		return new WP_Error( 'memcached-no-stats', 'Unable to get memcached stats.' );
	}

	if ( method_exists( $wp_object_cache, 'stats' ) ) {
		ob_start();
		$result = (string) $wp_object_cache->stats();
		$result .= ob_get_clean();
		if ( empty( $result ) ) {
			return new WP_Error( 'redis-no-stats', 'Unable to get redis stats.' );
		}
	}

	$set = wp_cache_set( 'test', 1 );
	if ( ! $set ) {
		return new WP_Error( 'object-cache-unable-to-set', 'Unable to set object cache value.' );
	}

	$value = wp_cache_get( 'test' );
	if ( $value !== 1 ) {
		return new WP_Error( 'object-cache-unable-to-get', 'Unable to get object cache value.' );
	}

	// Check alloptions are not out of sync.
	$alloptions_db = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE autoload = 'yes'" );
	$alloptions = [];
	foreach ( $alloptions_db as $o ) {
		$alloptions[ $o->option_name ] = $o->option_value;
	}

	$alloptions_cache = wp_cache_get( 'alloptions', 'options' );

	foreach ( $alloptions as $option => $value ) {
		if ( ! array_key_exists( $option, $alloptions_cache ) ) {
			return new WP_Error( 'object-cache-alloptions-out-of-sync', sprintf( '%s option not found in cache', $option ) );
		}
		// Values that are stored in the cache can be any scalar type, but scalar values retrieved from the database will always be string.
		// When a cache value is populated via update / add option, it will be stored in the cache as a scalar type, but then a string in the
		// database. We convert all non-string scalars to strings to be able to do the appropriate comparison.
		$cache_value = $alloptions_cache[ $option ];
		if ( is_scalar( $cache_value ) && ! is_string( $cache_value ) ) {
			$cache_value = (string) $cache_value;
		}
		if ( $cache_value !== $value ) {
			return new WP_Error( 'object-cache-alloptions-out-of-sync', sprintf( '%s option not the same in the cache and DB', $option ) );
		}
	}

	return true;
}

/**
 * Run healthcheck on cron jobs.
 */
function run_cron_healthcheck() {
	$cron = _get_cron_array();

	$jobs = 0;
	$passed_due = 0;
	foreach ( $cron as $timestamp => $hooks ) {
		$jobs += count( $hooks );
		// Only consider jobs past 60 seconds are passed due.
		if ( $timestamp + 60 < time() ) {
			$passed_due++;
		}
	}

	if ( $jobs === 0 ) {
		return new WP_Error( 'cron-no-jobs', 'Unable to find any cron jobs.' );
	}

	if ( $passed_due ) {
		return new WP_Error( 'cron-passed-due', sprintf( '%d jobs passed their run date.', $passed_due ) );
	}

	return true;
}

/**
 * Run ElasticSearch health check.
 */
function run_elasticsearch_healthcheck() {
	$host = Cloud\get_elasticsearch_url();

	// If no host is set then ElasticSearch not in use.
	if ( empty( $host ) ) {
		return true;
	}

	$response = wp_remote_get( $host . '/_cluster/health' );
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'elasticsearch-unhealthy', $response->get_error_message() );
	}

	$body = wp_remote_retrieve_body( $response );
	if ( is_wp_error( $body ) ) {
		return new WP_Error( 'elasticsearch-unhealthy', $body->get_error_message() );
	}

	return true;
}
