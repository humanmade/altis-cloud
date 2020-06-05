<?php
/**
 * Altis Cloud Audit Log to CloudWatch.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Audit_Log_To_CloudWatch;

/**
 * Bootstrap the audit log to cloudwatch integration.
 */
function bootstrap() {
	add_filter( 'wp_stream_db_driver', __NAMESPACE__ . '\\set_stream_db_driver' );
	add_action( 'wp_stream_after_list_table', __NAMESPACE__ . '\\display_error' );
}

/**
 * Set the Stream DB Driver to the CloudWatch integration.
 *
 * This is only done when we we are deemed to have a connection to CloudWatch available.
 *
 * @param string $db_driver The current DB driver.
 * @return string
 */
function set_stream_db_driver( string $db_driver ) : string {
	require_once __DIR__ . '/class-cloudwatch-driver.php';

	return __NAMESPACE__ . '\\CloudWatch_Driver';
}

/**
 * Display error message.
 *
 * @return void
 */
function display_error() {
	if ( empty( CloudWatch_Driver::$error ) ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p><?php echo esc_html( CloudWatch_Driver::$error ); ?></p>
	</div>
	<?php
}
