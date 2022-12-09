<?php
/**
 * Altis Cloud Performance Optimizations.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Performance_Optimizations;

/**
 * Set up optimizations.
 *
 * @return void
 */
function bootstrap() {
	increase_set_time_limit_on_async_upload();

	// Avoid DB requests to Cavalcade on the front end.
	add_filter( 'pre_get_scheduled_event', __NAMESPACE__ . '\\schedule_events_in_admin', 1, 2 );
}

/**
 * Set the execution time out when uploading images.
 *
 * "async-upload.php" / uploading an attachment does not change the execution time limit
 * in WordPress Core when you upload files. If the site has a lot of image sizes, this
 * can lead to max execution fatal errors.
 */
function increase_set_time_limit_on_async_upload() {
	if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	$is_accepted_method = in_array( $_SERVER['REQUEST_METHOD'], [ 'PATCH', 'POST', 'PUT' ], true );
	$is_async_upload = strpos( $_SERVER['REQUEST_URI'], '/wp-admin/async-upload.php' ) !== false;
	$is_rest_api_upload = strpos( $_SERVER['REQUEST_URI'], '/wp/v2/media' ) !== false;
	if ( ! $is_accepted_method ) {
		return;
	}
	if ( ! $is_async_upload && ! $is_rest_api_upload ) {
		return;
	}
	$time = ini_get( 'max_execution_time' );
	if ( $time === 0 || $time >= 120 ) {
		return;
	}
	set_time_limit( 120 );
}

/**
 * Only schedule known events in the admin to avoid extra db requests on front end.
 *
 * @param null|false|object $pre Value to return instead. Default null to continue retrieving the event.
 * @param string $hook Action hook of the event.
 * @return null|false|object Value to return instead. Default null to continue retrieving the event.
 */
function schedule_events_in_admin( $pre, string $hook ) {
	$admin_only_hooks = [
		'wp_site_health_scheduled_check',
		'wp_privacy_delete_old_export_files',
		'wp_https_detection',
	];

	/**
	 * Filter the scheduled event hooks to only fire in the admin context. This
	 * is useful for avoiding database lookups on front end requests that are not
	 * needed.
	 *
	 * @param array $backend_only_hooks The hook names to only run in the admin context.
	 */
	$admin_only_hooks = apply_filters( 'altis.cloud.admin_only_events', $admin_only_hooks );

	if ( ! in_array( $hook, $admin_only_hooks, true ) || is_admin() ) {
		return $pre;
	}

	// Non-empty filter return values are expected by wp_next_scheduled to have
	// a numeric ->timestamp property.
	return (object) [
		'timestamp' => time() + HOUR_IN_SECONDS,
	];
}
