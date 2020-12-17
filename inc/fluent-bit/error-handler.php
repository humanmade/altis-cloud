<?php
/**
 * PHP Error Handler to send all PHP errors to CloudWatch
 *
 * We do this to get better structured data about the errors, and also tie XRay trace ids to the errors.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\FluentBit\Error_Handler;

use Altis\Cloud\FluentBit;
// use Altis;
// use Altis\Cloud\CloudWatch_Logs;

/**
 * Set up shutdown function error handler to send to CloudWatch.
 *
 * @return void
 */
function bootstrap() {
	$GLOBALS['altis_cloudwatch_error_handler_errors'] = [];
	$GLOBALS['altis_cloudwatch_error_handler_error_count'] = 0;

	// If there is already an error handler set, we want to make sure it's not lost.
	$current_errror_handler = set_error_handler( function () use ( &$current_errror_handler ) { // @codingStandardsIgnoreLine
		call_user_func_array( __NAMESPACE__ . '\\error_handler', func_get_args() );
		if ( $current_errror_handler ) {
			return call_user_func_array( $current_errror_handler, func_get_args() );
		}
		return false;
	} );

	// Hook into Query Monitor error handler in case the above is overridden.
	add_action( 'qm/collect/new_php_error', __NAMESPACE__ . '\\error_handler', 10, 5 );

	// Register shutdown function.
	// Nesting the function registration ensures it is the last shutdown
	// function to run, required as we call fastcgi_finish_request()
	// which ends the request before Query Monitor's output.
	// TODO do we need this?
	// register_shutdown_function( function () {
		// register_shutdown_function( __NAMESPACE__ . '\\send_buffered_errors_on_shutdown' );
	// } );
}

/**
 * Shutdown function error handler callback.
 *
 * @param integer $errno Error code.
 * @param string $errstr Error message.
 * @param string $errfile The file where the error occurred.
 * @param integer $errline The line on which the error occurred.
 * @return boolean
 */
function error_handler( int $errno, string $errstr, string $errfile = null, int $errline = null ) : bool {
	global $altis_cloudwatch_error_handler_errors, $altis_cloudwatch_error_handler_error_count;
	// Limit the amount of errors to hold in memory. Flush every 100.
	// TODO is this necessary?
	// if ( $altis_cloudwatch_error_handler_error_count > 100 ) {
		// send_buffered_errors();
	// }

	$error = [
		'type'    => get_error_type_for_error_number( $errno ),
		'message' => $errstr,
		'file'    => str_replace( dirname( ABSPATH ), '', $errfile ),
		'line'    => $errline,
	];
	$error = apply_filters( 'altis_cloudwatch_error_handler_error', $error );
	$json = json_encode( $error );

	$logger = FluentBit\get_logger( 'app.php-structured.' . $error['type'] );
	$logger->error( $json );

	$altis_cloudwatch_error_handler_errors[ $errno ][] = [
		'timestamp' => time() * 1000,
		'message'   => $json, // @codingStandardsIgnoreLine
	];
	$altis_cloudwatch_error_handler_error_count++;
	return false;
}

/**
 * Map error code to string value.
 *
 * @param integer $type The error type constant or integer.
 * @return string
 */
function get_error_type_for_error_number( int $type ) : string {
	switch ( $type ) {
		case E_ERROR:
			return 'E_ERROR';
		case E_WARNING:
			return 'E_WARNING';
		case E_PARSE:
			return 'E_PARSE';
		case E_NOTICE:
			return 'E_NOTICE';
		case E_CORE_ERROR:
			return 'E_CORE_ERROR';
		case E_CORE_WARNING:
			return 'E_CORE_WARNING';
		case E_COMPILE_ERROR:
			return 'E_COMPILE_ERROR';
		case E_COMPILE_WARNING:
			return 'E_COMPILE_WARNING';
		case E_USER_ERROR:
			return 'E_USER_ERROR';
		case E_USER_WARNING:
			return 'E_USER_WARNING';
		case E_USER_NOTICE:
			return 'E_USER_NOTICE';
		case E_STRICT:
			return 'E_STRICT';
		case E_RECOVERABLE_ERROR:
			return 'E_RECOVERABLE_ERROR';
		case E_DEPRECATED:
			return 'E_DEPRECATED';
		case E_USER_DEPRECATED:
			return 'E_USER_DEPRECATED';
		default:
			return 'UNKNOWN';
	}
	return '';
}
