<?php
/**
 * Altis Cloud Module Loader.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud; // phpcs:ignore

use Altis;

add_action( 'altis.modules.init', function () {
	$is_cloud = is_cloud();
	$default_settings = [
		'enabled' => true,
		'cavalcade' => true,
		's3-uploads' => true,
		'aws-ses-wp-mail' => $is_cloud,
		'batcache' => $is_cloud,
		'redis' => true,
		'sessions' => false,
		'memcached' => Altis\get_environment_architecture() === 'ec2',
		'ludicrousdb' => true,
		'healthcheck' => true,
		'xray' => true,
		'email-from-address' => 'no-reply@humanmade.com',
		'email-region' => false,
		'audit-log-to-cloudwatch' => $is_cloud,
		'php-errors-to-cloudwatch' => $is_cloud,
		'cdn-media-purge' => false,
		'page-cache' => [
			'ignored-query-string-params' => [
				'utm_campaign',
				'utm_medium',
				'utm_source',
				'utm_term',
				'utm_content',
				'mc_cid',
				'mc_eid',
				'fbclid',
				'_ga',
			],
			'unique-headers' => [
				'cloudfront-viewer-country',
			],
			'unique-cookies' => [],
		],
	];

	Altis\register_module( 'cloud', __DIR__, 'Cloud', $default_settings, __NAMESPACE__ . '\\bootstrap' );
} );

// Early hook for logging AWS SDK HTTP requests.
add_filter( 'altis.aws_sdk.params', __NAMESPACE__ . '\\add_aws_sdk_xray_callback' );
add_filter( 's3_uploads_s3_client_params', __NAMESPACE__ . '\\add_aws_sdk_xray_callback' );
add_filter( 'aws_ses_wp_mail_ses_client_params', __NAMESPACE__ . '\\add_aws_sdk_xray_callback' );

// Ensure debug display is off in cloud environments.
add_action( 'altis.loaded_autoloader', __NAMESPACE__ . '\\set_wp_debug_constants', 0 );
