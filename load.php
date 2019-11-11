<?php

namespace Altis\Cloud; // @codingStandardsIgnoreLine

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
