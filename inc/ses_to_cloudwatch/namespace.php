<?php

namespace Altis\Cloud\SES_To_CloudWatch;

use Exception;
use function Altis\Cloud\CloudWatch_Logs\send_events_to_stream;

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
 * @param  AWS\Result $result
 * @param  array $message
 */
function on_sent_message( $result, $message ) {
	send_events_to_stream(
		[
			[
				'timestamp' => time() * 1000,
				'message'   => json_encode( $message ),
			],
		],
		HM_ENV . '/ses',
		'Sent'
	);
}

/**
 * Called when the AWS SES plugin has an error sending mail.
 *
 * @param  AWS\Result $result
 * @param  array $message
 */
function on_error_sending_message( Exception $e, $message ) {
	send_events_to_stream(
		[
			[
				'timestamp' => time() * 1000,
				'message'   => json_encode( [
					'error'     => [
						'class'   => get_class( $e ),
						'message' => $e->getMessage(),
					],
					'message' => $message,
				] ),
			],
		],
		HM_ENV . '/ses',
		'Failed'
	);
}
