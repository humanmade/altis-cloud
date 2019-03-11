<?php

namespace HM\Platform\Cloud;

if ( ! defined( 'WP_CACHE' ) ) {
	define( 'WP_CACHE', true );
}

require_once __DIR__ . '/inc/namespace.php';

// Load the platform as soon as WP is loaded.
add_action( 'enable_wp_debug_mode_checks', __NAMESPACE__ . '\\bootstrap' );

if ( class_exists( 'HM\\Cavalcade\\Runner\\Runner' ) && get_config()['cavalcade'] ) {
	boostrap_cavalcade_runner();
}
