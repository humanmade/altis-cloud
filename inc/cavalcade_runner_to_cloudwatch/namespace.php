<?php
/**
 * Send Cavalcade logs to CloudWatch.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Cavalcade_Runner_To_CloudWatch;

use Altis;
use Altis\Cloud;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Aws\CloudWatch\CloudWatchClient;
use Exception;
use HM\Cavalcade\Runner\Job;
use HM\Cavalcade\Runner\Runner;
use HM\Cavalcade\Runner\Worker;
use ReflectionClass;

/**
 * Register Cavalcade Hooks.
 */
function bootstrap() {
	Runner::instance()->hooks->register( 'Runner.run_job.started', __NAMESPACE__ . '\\on_job_started' );
	Runner::instance()->hooks->register( 'Runner.check_workers.job_failed', __NAMESPACE__ . '\\on_job_failed' );
	Runner::instance()->hooks->register( 'Runner.check_workers.job_completed', __NAMESPACE__ . '\\on_job_completed' );
}

/**
 * Called when a new job is started via Cavalcade, and sends an Invocation metric to CloudWatch.
 *
 * @param Worker $worker The Cavalcade runner process.
 * @param Job $job The current Cavalcade cron job.
 */
function on_job_started( Worker $worker, Job $job ) {
	global $job_start_times;
	$job_start_times[ $job->id ] = microtime( true );
}

/**
 * Called when a job is failed via Cavalcade, and sends an Completed metric to CloudWatch.
 *
 * @param Worker $worker The Cavalcade runner process.
 * @param Job $job The current Cavalcade cron job.
 */
function on_job_failed( Worker $worker, Job $job ) {
	put_metric_data( 'Failed', 1 );
	on_end_job( $worker, $job, 'fail' );
}

/**
 * Called when a job is completed via Cavalcade, and sends an Completed metric to CloudWatch.
 *
 * @param Worker $worker The Cavalcade runner process.
 * @param Job $job The current Cavalcade cron job.
 */
function on_job_completed( Worker $worker, Job $job ) {
	on_end_job( $worker, $job, 'success' );
}

/**
 * Called when a job completed or failed via Cavalcade, and sends a Duraction metric to CloudWatch.
 *
 * @param Worker $worker The Cavalcade runner process.
 * @param Job $job The current Cavalcade cron job.
 * @param 'success'|'fail' $status The job status.
 */
function on_end_job( Worker $worker, Job $job, string $status ) {
	global $job_start_times;
	$duration = microtime( true ) - $job_start_times[ $job->id ];
	unset( $job_start_times[ $job->id ] );

	$status_metric = $status === 'success' ? 'Completed' : 'Failed';

	// Batch all the metrics together to avoid many API calls.
	$data = [];
	$data[] = [
		'MetricName' => $status_metric,
		'Dimensions' => [
			[
				'Name' => 'Application',
				'Value' => HM_ENV,
			],
		],
		'Value' => 1,
	];
	$data[] = [
		'MetricName' => $status_metric,
		'Value' => 1,
	];

	$data[] = [
		'MetricName' => 'Invocations',
		'Dimensions' => [
			[
				'Name' => 'Application',
				'Value' => HM_ENV,
			],
		],
		'Value' => 1,
	];
	$data[] = [
		'MetricName' => 'Invocations',
		'Value' => 1,
	];
	$data[] = [
		'MetricName' => 'Duration',
		'Unit' => 'Seconds',
		'Dimensions' => [
			[
				'Name' => 'Application',
				'Value' => HM_ENV,
			],
		],
		'Value' => $duration,
	];
	$data[] = [
		'MetricName' => 'Duration',
		'Unit' => 'Seconds',
		'Value' => $duration,
	];
	put_metric_data_multiple( $data );

	// Workaround to get the stdout / stderr for the job.
	$reflection = new ReflectionClass( $worker );
	$output_property = $reflection->getProperty( 'output' );
	$output_property->setAccessible( true );
	$output = $output_property->getValue( $worker );

	$error_output_property = $reflection->getProperty( 'error_output' );
	$error_output_property->setAccessible( true );
	$error_output = $error_output_property->getValue( $worker );

	Cloud\get_logger( 'cavalcade', $status, 1 )->info( json_encode( [
		'hook'     => $job->hook,
		'id'       => $job->id,
		'site_url' => $job->get_site_url(),
		'site_id'  => $job->site,
		'args'     => unserialize( $job->args ),
		'status'   => $status,
		'stdout'   => $output,
		'stderr'   => $error_output,
		'duration' => $duration,
	] ));
}

/**
 * Save metric data to CloudWatch.
 *
 * @param string $metric_name The CloudWatch metric name.
 * @param float $value The metric value.
 * @param array $dimensions Additional data for the metric.
 * @param string $unit Units the metric is in.
 */
function put_metric_data( $metric_name, $value, $dimensions = [], $unit = 'None' ) {
	try {
		cloudwatch_client()->putMetricData([
			'MetricData' => [
				[
					'Dimensions' => array_map(
						function ( $name, $value ) {
							return [
								'Name' => $name,
								'Value' => $value,
							];
						},
						array_keys( $dimensions ),
						$dimensions
					),
					'MetricName' => $metric_name,
					'Unit' => $unit,
					'Value' => $value,
				],
			],
			'Namespace' => 'Cavalcade',
		]);
	} catch ( Exception $e ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( sprintf( 'Error from CloudWatch API: %s', $e->getMessage() ), E_USER_WARNING );
	}
}

/**
 * Save metric data to CloudWatch.
 *
 * @param array{ MetricName: string, ?Unit: string, ?Dimensions: { Name: string, value: int}[], Value: float }[] $data The metric data to save.
 */
function put_metric_data_multiple( array $data ) {
	try {
		cloudwatch_client()->putMetricData([
			'MetricData' => $data,
			'Namespace' => 'Cavalcade',
		]);
	} catch ( Exception $e ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( sprintf( 'Error from CloudWatch API: %s', $e->getMessage() ), E_USER_WARNING );
	}
}

/**
 * Get a configured CloudWatchClient client.
 *
 * @return CloudWatchClient
 */
function cloudwatch_client() : CloudWatchClient {
	static $client;
	if ( ! $client ) {
		$client = Altis\get_aws_sdk()->createCloudWatch( [
			'version'     => '2010-08-01',
			'http'        => [
				'synchronous' => false,
			],
		] );
	}

	return $client;
}

/**
 * Get a configured CloudWatch Logs Client.
 *
 * @return CloudWatchLogsClient
 */
function cloudwatch_logs_client() : CloudWatchLogsClient {
	static $client;
	if ( ! $client ) {
		$client = Altis\get_aws_sdk()->createCloudWatchLogs( [
			'version'     => '2014-03-28',
			'http'        => [
				'synchronous' => true,
			],
		] );
	}
	return $client;
}

/**
 * Save an event to a cloudwatch logs stream
 *
 * @param array $event The event data.
 * @param string $group The group name.
 * @param string $stream The stream name.
 */
function send_event_to_stream( array $event, string $group, string $stream ) {
	try {
		// Check if there's already a log stream existing.
		$streams = cloudwatch_logs_client()->describeLogStreams([
			'logGroupName'        => $group,
			'logStreamNamePrefix' => $stream,
		])['logStreams'];

		// Create a new log stream if none are found.
		if ( empty( $streams ) ) {
			$result = cloudwatch_logs_client()->createLogStream([
				'logGroupName'  => $group,
				'logStreamName' => $stream,
			]);
		} else {
			$next_token = $streams[0]['uploadSequenceToken'];
		}
	} catch ( Exception $e ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( $e->getMessage(), E_USER_WARNING );
	}
	$params = [
		'logEvents'     => [ $event ],
		'logGroupName'  => $group,
		'logStreamName' => $stream,
	];
	if ( isset( $next_token ) ) {
		$params['sequenceToken'] = $next_token;
	}
	try {
		$result = cloudwatch_logs_client()->putLogEvents( $params );
	} catch ( Exception $e ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( $e->getMessage(), E_USER_WARNING );
	}
}
