<?php
/**
 * Altis Cloud Audit Log to CloudWatch Driver.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Audit_Log_To_CloudWatch;

use Altis;
use Altis\Cloud\CloudWatch_Logs;
use Aws\CloudWatch\CloudWatchClient;
use Exception;
use WP_Stream\DB_Driver as DB_Driver_Interface;

/**
 * Audit Log Database Driver.
 *
 * @package altis/cloud
 */
class CloudWatch_Driver implements DB_Driver_Interface {
	/**
	 * Holds Query class.
	 *
	 * @var Query
	 */
	protected $query;

	/**
	 * Holds error message.
	 *
	 * @var string
	 */
	public static $error = '';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		wp_cache_add_global_groups( 'stream_column_values' );
	}

	/**
	 * Insert a record.
	 *
	 * @param array $data Data to insert.
	 *
	 * @return bool
	 */
	public function insert_record( $data ) {
		if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
			return false;
		}

		// Track the timestamp in an integer so we can do range queries for it.
		$data['created_timestamp'] = strtotime( $data['created'] ) * 1000;

		$result = CloudWatch_Logs\send_events_to_stream(
			[
				[
					'timestamp' => time() * 1000,
					// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
					'message' => json_encode( $data ),
				],
			],
			Altis\get_environment_name() . '/audit-log',
			'items'
		);

		if ( ! $result ) {
			return false;
		}
		// Add the values to the column values caches if they exist.
		foreach ( $data as $column => $value ) {
			$cache = wp_cache_get( $column, 'stream_column_values' );
			if ( $cache === false || in_array( $value, $cache, true ) ) {
				continue;
			}
			$cache[] = $value;
			wp_cache_set( $column, $cache, 'stream_column_values' );
		}

		return true;
	}

	/**
	 * Retrieve records.
	 *
	 * @param array $args Search query arguments.
	 * @return array
	 */
	public function get_records( $args ) {
		$fields = [
			'blog_id',
			'site_id',
			'user_id',
			'user_role',
			'connector',
			'context',
			'action',
			'ip',
			'object_id',
		];

		$field_where = [];
		foreach ( $fields as $field ) {
			if ( $args[ $field ] || $args[ $field ] === '0' ) {
				$field_where[] = sprintf( '%s = "%s" ', $field, esc_sql( $args[ $field ] ) );
			}
		}

		if ( $args['search'] ) {
			$field_where[] = sprintf( '%s like "%s" ', $args['search_field'], esc_sql( $args['search'] ) );
		}

		if ( $args['date_to'] ) {
			// Because date_to is in the format 2019/04/17, we want to really say the create date is less then 2019/04/17 23:59:59.
			$field_where[] = sprintf( 'created_timestamp < %d ', ( strtotime( $args['date_to'] ) + DAY_IN_SECONDS ) * 1000 );
		}

		if ( $args['date_from'] ) {
			$field_where[] = sprintf( 'created_timestamp >= %d ', strtotime( $args['date_from'] ) * 1000 );
		}

		if ( $field_where ) {
			$where = sprintf( '| filter ( %s )', implode( ' and ', $field_where ) );
		}

		$limit = $args['records_per_page'] + ( absint( $args['paged'] - 1 ) * $args['records_per_page'] );
		$offset = absint( $args['paged'] - 1 ) * $args['records_per_page'];
		$order = $args['order'];

		$query = "fields @message $where | sort created_timestamp $order";

		$params = [
			'logGroupName'   => Altis\get_environment_name() . '/audit-log',
			'limit'          => $limit,
			'endTime'        => time() * 1000,
			'startTime'      => 0,
			'queryString'    => $query,
		];

		try {
			$query = CloudWatch_Logs\cloudwatch_logs_client()->startQuery( $params );
		} catch ( Exception $e ) {
			self::$error = $e->getMessage();
			return [];
		}

		$results = [ 'status' => 'Running' ];
		$start_searching_time = time();
		while ( ! in_array( $results['status'], [ 'Failed', 'Cancelled', 'Complete' ], true ) ) {

			// Limit how fast we poll CloudWatch via calls to getQueryResults.
			// Queries take at a minimum 1 second, so we `sleep` before even
			// making the first call.
			sleep( 1 );
			$results = CloudWatch_Logs\cloudwatch_logs_client()->getQueryResults([
				'queryId' => $query['queryId'],
			] );

			$time_taken = time() - $start_searching_time;
			// If we are looking for most recent results, we can short-circuit once we have enough
			// in the results array, even if the query has not completed searching all records.
			// We only trigger this behaviour at a minimum of 15 seconds, which gives the query time to
			// complete as usual, which will mean that the total found results is correctly set.
			// Short-circuiting the query early means the total counts will not be accurate, so we fix to 10k.
			if ( $order === 'desc' && $results['status'] === 'Running' && count( $results['results'] ) === $limit && $time_taken > 15 ) {
				$results['statistics']['recordsMatched'] = 10000;

				try {
					// Stop the running query, as we won't be reading from it again.
					CloudWatch_Logs\cloudwatch_logs_client()->stopQuery( [
						'queryId' => $query['queryId'],
					] );
				} catch ( Exception $e ) {
					trigger_error( sprintf( 'Error stopping CloudWatch Logs query: %s', $e->getMessage() ), E_USER_WARNING );
				}
				break;
			}
		}

		/**
		 * Future optimization: currently we have to pull all results up until the offset.
		 * There's no way in the CloudWatch Insights API to supply a number offset.
		 */
		$items = [];
		$count = 0;
		foreach ( $results['results'] as $result ) {
			$count++;
			if ( $count <= $offset ) {
				continue;
			}
			// Sometimes the AWS API returns more results than asked for.
			if ( $count > $limit ) {
				break;
			}
			$object = json_decode( $result[0]['value'] );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			$object->meta = json_decode( json_encode( $object->meta ), true );
			$items[] = $object;
		}

		return [
			'items' => $items,
			'count' => min( $results['statistics']['recordsMatched'], 10000 ),
		];
	}

	/**
	 * Returns array of existing values for requested column.
	 * Used to fill search filters with only used items, instead of all items.
	 *
	 * @param string $column The audit log table column name.
	 * @return array
	 */
	public function get_column_values( $column ) {
		$values = wp_cache_get( $column, 'stream_column_values' );
		if ( $values === false ) {
			$query = "stats distinct( $column ) by $column";

			$params = [
				'logGroupName'   => Altis\get_environment_name() . '/audit-log',
				'endTime'        => time() * 1000,
				'startTime'      => 0,
				'queryString'    => $query,
			];

			try {
				$query = CloudWatch_Logs\cloudwatch_logs_client()->startQuery( $params );
			} catch ( Exception $e ) {
				return [];
			}

			$results = [ 'status' => 'Running' ];
			while ( ! in_array( $results['status'], [ 'Failed', 'Cancelled', 'Complete' ], true ) ) {
				// Limit how fast we poll CloudWatch via calls to getQueryResults.
				// Queries take at a minimum 1 second, so we `sleep` before even
				// making the first call.
				sleep( 1 );
				$results = CloudWatch_Logs\cloudwatch_logs_client()->getQueryResults([
					'queryId' => $query['queryId'],
				] );
			}

			foreach ( $results['results'] as $result ) {
				$values[] = $result[0]['value'];
			}

			wp_cache_set( $column, $values, 'stream_column_values' );
		}

		$values = array_map( function ( $value ) : array {
			return [ 'cell' => $value ];
		}, $values );

		return $values;
	}

	/**
	 * Public getter to return table names
	 *
	 * @return array
	 */
	public function get_table_names() {
		return [];
	}

	/**
	 * Init storage.
	 *
	 * @param \WP_Stream\Plugin $plugin Instance of the plugin.
	 * @return null
	 */
	public function setup_storage( $plugin ) {
		// no op.
		return;
	}

	/**
	 * Purge storage.
	 *
	 * @param \WP_Stream\Plugin $plugin Instance of the plugin.
	 * @return null
	 */
	public function purge_storage( $plugin ) {
		// no op.
		return;
	}

	/**
	 * Undocumented function
	 *
	 * @return CloudWatchClient
	 */
	protected function get_cloudwatch_client() : CloudWatchClient {
		$client = Altis\get_aws_sdk()->createCloudWatch();
		return $client;
	}
}
