<?php
/**
 * Altis Cloud Cavalcade Healthcheck.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Healthcheck\Cavalcade;

use WP_CLI;
use WP_Error;

const JOB_HOOK = 'hm-platform.healthcheck.cavalcade';
const JOB_INTERVAL = 600; // 10 mins
const JOB_SCHEDULE = 'hm-platform-healthcheck_10min';
const LAST_RUN_OPTION = 'hm-platform.healthcheck.last_run';
const HEALTHY_THRESHOLD = 900; // 15 mins

/**
 * Set up jobs/etc.
 */
function bootstrap() {
	add_action( JOB_HOOK, __NAMESPACE__ . '\\set_last_run' );
	// phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
	add_filter( 'cron_schedules', __NAMESPACE__ . '\\add_cron_schedule' );

	// Schedule on migrate command.
	add_action( 'altis.migrate', __NAMESPACE__ . '\\schedule_job' );
}

/**
 * Schedule the Cavalcade healthcheck.
 *
 * @return void
 */
function schedule_job() {
	if ( ! wp_next_scheduled( JOB_HOOK ) && ( ! is_multisite() || is_main_site() ) ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( 'Scheduling Cavalcade healthcheck...' );
		}
		wp_schedule_event( time(), JOB_SCHEDULE, JOB_HOOK );
	}
}

/**
 * Add custom cron schedule.
 *
 * @param array $schedules Existing wp-cron schedules.
 * @return array Altered schedules
 */
function add_cron_schedule( $schedules ) {
	$schedules[ JOB_SCHEDULE ] = [
		'interval' => JOB_INTERVAL,
		'display' => 'Cavalcade Healthcheck Schedule (10 mins)',
	];
	return $schedules;
}

/**
 * Set the last run time.
 */
function set_last_run() {
	update_option( LAST_RUN_OPTION, time() );
}

/**
 * Check if Cavalcade is healthy.
 *
 * @return boolean|WP_Error True if healthy, error otherwise.
 */
function check_health() {
	switch_to_blog( get_main_site_id() );
	$last_run = get_option( LAST_RUN_OPTION, 0 );

	if ( $last_run > ( time() - HEALTHY_THRESHOLD ) ) {
		return true;
	}
	restore_current_blog();

	return new WP_Error(
		'altis.healthcheck.cavalcade.not_running',
		sprintf(
			'Last job was run %d seconds ago, threshold is %d',
			time() - $last_run,
			HEALTHY_THRESHOLD
		)
	);
}
