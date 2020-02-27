<?php

namespace Altis\Cloud; // @codingStandardsIgnoreLine

use const Altis\ROOT_DIR;
use function Altis\get_environment_architecture;
use function Altis\register_module;

add_action( 'altis.modules.init', function () {
	$is_cloud = in_array( get_environment_architecture(), [ 'ec2', 'ecs' ], true );
	$default_settings = [
		'enabled' => true,
		'cavalcade' => true,
		's3-uploads' => true,
		'aws-ses-wp-mail' => $is_cloud,
		'batcache' => $is_cloud,
		'redis' => true,
		'memcached' => get_environment_architecture() === 'ec2',
		'ludicrousdb' => true,
		'healthcheck' => true,
		'xray' => true,
		'email-from-address' => 'no-reply@humanmade.com',
		'audit-log-to-cloudwatch' => $is_cloud,
		'php-errors-to-cloudwatch' => $is_cloud,
		'page-cache' => [
			'ignored-query-string-params' => [
				'utm_campaign',
				'utm_medium',
				'utm_source',
				'utm_content',
				'fbclid',
				'_ga',
			],
			'unique-headers' => [
				'cloudfront-viewer-country',
			],
			'unique-cookies' => [],
		],
	];

	register_module( 'cloud', __DIR__, 'Cloud', $default_settings, __NAMESPACE__ . '\\bootstrap' );
} );

// Early hook for logging AWS SDK HTTP requests.
add_filter( 'altis.aws_sdk.params', __NAMESPACE__ . '\\add_aws_sdk_xray_callback' );
add_filter( 's3_uploads_s3_client_params', __NAMESPACE__ . '\\add_aws_sdk_xray_callback' );
add_filter( 'aws_ses_wp_mail_ses_client_params', __NAMESPACE__ . '\\add_aws_sdk_xray_callback' );

/**
 * On Altis, all configuration of DB constants (etc) will be put
 * in to a wp-config-production.php in the web root.
 *
 * Load wp-config-production.php on priority 11 after .config/load.php
 * to allow overrides.
 */
add_action( 'altis.loaded_autoloader', function () {
	if ( file_exists( ROOT_DIR . '/wp-config-production.php' ) ) {
		require_once ROOT_DIR . '/wp-config-production.php';
	}
}, 11 );
