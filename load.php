<?php

namespace Altis\Cloud; // @codingStandardsIgnoreLine

use function Altis\get_environment_architecture;
use function Altis\register_module;

require_once __DIR__ . '/inc/namespace.php';

function register() {
	$is_cloud = in_array( get_environment_architecture(), [ 'ec2', 'ecs' ], true );
	$default_settings = [
		'enabled'                  => true,
		'cavalcade'                => true,
		's3-uploads'               => true,
		'aws-ses-wp-mail'          => $is_cloud,
		'batcache'                 => $is_cloud,
		'redis'                    => true,
		'memcached'                => get_environment_architecture() === 'ec2',
		'ludicrousdb'              => true,
		'healthcheck'              => true,
		'xray'                     => $is_cloud,
		'email-from-address'       => 'no-reply@humanmade.com',
		'audit-log-to-cloudwatch'  => $is_cloud,
		'php-errors-to-cloudwatch' => $is_cloud,
	];

	register_module( 'cloud', __DIR__, 'Cloud', $default_settings, __NAMESPACE__ . '\\bootstrap' );
}

// Don't self-initialize if this is not an Altis execution.
if ( ! function_exists( 'add_action' ) ) {
	return;
}

add_action( 'altis.modules.init', __NAMESPACE__ . '\\register' );
