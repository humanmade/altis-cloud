<?php

namespace HM\Platform\Cloud\Audit_Log_To_CloudWatch;

use function HM\Platform\get_environment_architecture;

/**
 * Bootstrap the audit log to cloudwatch integration.
 */
function bootstrap() {
	add_filter( 'wp_stream_db_driver', __NAMESPACE__ . '\\set_stream_db_driver' );
}

/**
 * Set the Stream DB Driver to the CloudWatch integration.
 *
 * This is only done when we we are deemed to have a connection to CloudWatch available.
 *
 * @param string $db_driver
 * @return string
 */
function set_stream_db_driver( string $db_driver ) : string {
	require_once __DIR__ . '/class-cloudwatch-driver.php';
	return __NAMESPACE__ . '\\CloudWatch_Driver';
}
