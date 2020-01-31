<?php

namespace Altis\Cloud\Environment_Indicator;

use function Altis\get_environment_name;
use function Altis\get_environment_type;

function bootstrap() {
	add_action( 'wp_before_admin_bar_render', __NAMESPACE__ . '\\add_admin_bar_env_info' );
}

/**
 * Add menu item to admin bar to display environment info:
 * - environment type
 * - environment name
 * - URL to Altis dashboard for the environment
 */
function add_admin_bar_env_info() {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	// Specify capabilities for which environment indicator is shown in the admin bar.
	$capability = apply_filters( 'altis_env_indicator_capability', 'manage_options' );
	if ( ! current_user_can( $capability ) ) {
		return;
	}

	global $wp_admin_bar;
	// Specify admin bar menu item info and styling.
	$indicator_colour = 'red';
	$indicator_colour = apply_filters( 'altis_env_indicator_colour', $indicator_colour, get_environment_type() );

	$indicator_text = strtoupper( get_environment_type() );
	$indicator_text = apply_filters( 'altis_env_indicator_text', $indicator_text, get_environment_type() );

	$bar_item_style = '<style>
#wpadminbar #wp-admin-bar-altis-env-indicator>.ab-item:before {
	content: "\f547";
}
#wpadminbar #wp-admin-bar-altis-env-indicator,
#wpadminbar #wp-admin-bar-altis-env-indicator>.ab-item:hover,
#wpadminbar #wp-admin-bar-altis-env-indicator>.ab-item:focus,
#wpadminbar #wp-admin-bar-altis-env-indicator>.ab-item:visited {
	background: ' . $indicator_colour . ' ! important;
}
</style>';
	$bar_item_style = apply_filters( 'altis_env_indicator_style', $bar_item_style, get_environment_type() );

	// Add menu items to admin bar.
	$wp_admin_bar->add_menu( [
		'id'    => 'altis-env-indicator',
		'title' => $bar_item_style . $indicator_text,
	] );

	// Construct Altis dashboard URL for the environment.
	$altis_dashboard_url = '';
	if ( defined( 'HM_ENV_REGION' ) && get_environment_type() !== 'local' ) {
		$altis_dashboard_url = sprintf( 'https://dashboard.altis-dxp.com/#/%s/%s', esc_url( HM_ENV_REGION ), esc_url( get_environment_name() ) );
	}

	$wp_admin_bar->add_menu( [
		'id'     => 'altis-env-stack-url',
		'title'  => sprintf( __( 'Altis dashboard: %s', 'altis' ), esc_html( get_environment_name() ) ),
		'parent' => 'altis-env-indicator',
		'href'   => $altis_dashboard_url,
		'meta'   => [
			'target' => '__blank',
		],
	] );
}
