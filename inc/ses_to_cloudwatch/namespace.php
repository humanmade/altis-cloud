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
	Cloud\log_to_cloud( 'ses', 'Sent', json_encode( $message ) );
}

/**
 * Called when the AWS SES plugin has an error sending mail.
 *
 * @param Exception $error The SES email delivery error.
 * @param array $message The error message.
 */
function on_error_sending_message( Exception $error, $message ) {
	Cloud\log_to_cloud('ses', 'Failed', json_encode( [
		'error'     => [
			'class'   => get_class( $error ),
			'message' => $error->getMessage(),
		],
		'message' => $message,
	] ), 'error' );
}
