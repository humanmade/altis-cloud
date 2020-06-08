<?php
/**
 * Altis Cloud CloudWatch Logging Helper.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\CloudWatch_Logs;

use Altis;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Exception;

const OBJECT_CACHE_GROUP = 'cloudwatch-stream-tokens';

/**
 * Add a global CloudWatch stream token cache group.
 *
 * @return void
 */
function bootstrap() {
	if ( function_exists( 'wp_cache_add_global_groups' ) ) {
		wp_cache_add_global_groups( OBJECT_CACHE_GROUP );
	}
}

/**
 * Sends events to CloudWatch.
 *
 * @param array $events The events to send.
 * @param string $group The group name.
 * @param string $stream The stream name.
 * @return void
 */
function send_events_to_stream( array $events, string $group, string $stream ) {
	// Attempt to get the nextToken from the cache.
	$next_token = wp_cache_get( $group . $stream, OBJECT_CACHE_GROUP );
	if ( ! $next_token ) {
		try {
			// Check if there's already a log stream existing.
			$streams = cloudwatch_logs_client()->describeLogStreams([
				'logGroupName' => $group,
				'logStreamNamePrefix' => $stream,
			])['logStreams'];

			// Create a new log stream if none are found.
			if ( empty( $streams ) ) {
				$result = cloudwatch_logs_client()->createLogStream([
					'logGroupName' => $group,
					'logStreamName' => $stream,
				]);
			} else {
				$next_token = $streams[0]['uploadSequenceToken'];
			}
		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			trigger_error( $e->getMessage(), E_USER_WARNING );
		}
	}

	$params = [
		'logEvents' => $events,
		'logGroupName' => $group,
		'logStreamName' => $stream,
	];
	if ( $next_token ) {
		$params['sequenceToken'] = $next_token;
	}
	try {
		$result = cloudwatch_logs_client()->putLogEvents( $params );
		wp_cache_set( $group . $stream, $result['nextSequenceToken'], OBJECT_CACHE_GROUP );
	} catch ( Exception $e ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( $e->getMessage(), E_USER_WARNING );
		wp_cache_delete( $group . $stream, OBJECT_CACHE_GROUP );
	}
}

/**
 * Get a configured CloudWatch logs client.
 *
 * @return CloudWatchLogsClient
 */
function cloudwatch_logs_client() : CloudWatchLogsClient {
	static $cloudwatch_logs_client;
	if ( $cloudwatch_logs_client ) {
		return $cloudwatch_logs_client;
	}
	$cloudwatch_logs_client = Altis\get_aws_sdk()->createCloudWatchLogs([
		'version' => '2014-03-28',
	]);
	return $cloudwatch_logs_client;
}
