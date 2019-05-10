<?php

namespace Altis\Cloud;

use const Altis\ROOT_DIR;
use function Altis\get_config;
use function Altis\get_environment_architecture;
use function Altis\register_module;
use Altis\XRay;

require_once __DIR__ . '/inc/namespace.php';

// Don't self-initialize if this is not an Altis execution.
if ( ! function_exists( 'add_action' ) ) {
	return;
}

add_action( 'altis.modules.init', function () {
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

	register_module( 'cloud', __DIR__, 'Cloud', $default_settings, function () {
		$config = get_config()['modules']['cloud'];

		if (
			$config['xray']
			&& function_exists( 'xhprof_sample_enable' )
			&& ( ! defined( 'WP_CLI' ) || ! WP_CLI )
			&& ! class_exists( 'HM\\Cavalcade\\Runner\\Runner' )
		) {
			require_once ROOT_DIR . '/vendor/humanmade/aws-xray/inc/namespace.php';
			require_once ROOT_DIR . '/vendor/humanmade/aws-xray/plugin.php';
			XRay\bootstrap();
		}

		if ( $config['batcache'] && ! defined( 'WP_CACHE' ) ) {
			define( 'WP_CACHE', true );
		}

		add_filter( 'wp_mail_from', function ( string $email ) use ( $config ) : string {
			return $config['email-from-address'];
		}, 1 );

		// Load the platform as soon as WP is loaded.
		add_action( 'enable_wp_debug_mode_checks', __NAMESPACE__ . '\\bootstrap' );

		if ( class_exists( 'HM\\Cavalcade\\Runner\\Runner' ) && $config['cavalcade'] ) {
			boostrap_cavalcade_runner();
		}
	} );
} );


