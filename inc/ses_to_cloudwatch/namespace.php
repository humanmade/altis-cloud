<?php
/**
 * Altis Cloud SES Logs.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\SES_To_CloudWatch;

use Altis\Cloud;
use Exception;

/**
 * Set up actions.
 */
function bootstrap() {
	add_action( 'aws_ses_wp_mail_ses_sent_message', __NAMESPACE__ . '\\on_sent_message', 10, 2 );
	add_action( 'aws_ses_wp_mail_ses_error_sending_message', __NAMESPACE__ . '\\on_error_sending_message', 10, 2 );
}

/**
 * Called when the AWS SES plugin has sent an email.
 *
 * @param AWS\Result $result SES email delivery result.
 * @param array $message The response message.
 */
function on_sent_message( $result, $message ) {
	// Truncate the size of message array item to max 5KB.
	array_walk_recursive( $message, function ( $value, $key ) {
		return truncate_string( $value );
	} );

	Cloud\get_logger( 'ses', 'Sent' )->info( json_encode( $message ) );
}

/**
 * Called when the AWS SES plugin has an error sending mail.
 *
 * @param Exception $error The SES email delivery error.
 * @param array $message The error message.
 */
function on_error_sending_message( Exception $error, $message ) {
	// Truncate the size of message array item to max 5KB.
	array_walk_recursive( $message, function ( $value, $key ) {
		return truncate_string( $value );
	} );

	Cloud\get_logger( 'ses', 'Failed' )->error( json_encode( [
		'error' => [
			'class' => get_class( $error ),
			'message' => $error->getMessage(),
		],
		'message' => $message,
	] ) );
}

/**
 * Truncate string to given maximum size.
 *
 * @param string $string      String message to truncate.
 * @param int    $max_size    Maximum size in bytes, default 5KB.
 * @param string $replacement String replacement.
 *
 * @return string
 */
function truncate_string( string $string, int $max_size = 5 * 1024, string $replacement = '…' ) : string {
	if ( strlen( $string ) < $max_size ) {
		return $string;
	}

	// Truncate string in the middle.
	return substr_replace( $string, $replacement, $max_size / 2, strlen( $string ) - $max_size + strlen( $replacement ) );
}
