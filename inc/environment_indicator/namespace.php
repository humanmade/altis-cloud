<?php

namespace Altis\Cloud\Environment_Indicator;

use function Altis\get_environment_name;
use function Altis\get_environment_type;

function bootstrap() {
	add_action( 'wp_before_admin_bar_render', __NAMESPACE__ . '\\add_admin_bar_env_info' );
	add_action( 'admin_bar_init', __NAMESPACE__ . '\\enqueue_admin_scripts' );
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
	// Specify admin bar menu item info.
	$indicator_text = strtoupper( get_environment_type() );
	$indicator_text = apply_filters( 'altis_env_indicator_text', $indicator_text, get_environment_type() );

	// Add menu items to admin bar.
	$wp_admin_bar->add_menu( [
		'id'    => 'altis-env-indicator',
		'title' => $indicator_text,
		'meta'  => [
			'class' => 'altis-env-indicator--' . get_environment_type(),
		],
	] );

	// Construct Altis dashboard URL for the environment.
	if ( defined( 'HM_ENV_REGION' ) && get_environment_type() !== 'local' ) {
		$wp_admin_bar->add_menu( [
			'id'     => 'altis-env-stack-name',
			'title'  => esc_html( get_environment_name() ),
			'parent' => 'altis-env-indicator',
		] );

		$wp_admin_bar->add_menu( [
			'id'     => 'altis-env-stack-url',
			'title'  => __( 'Open in Altis Dashboard', 'altis' ),
			'parent' => 'altis-env-indicator',
			'href'   => esc_url( sprintf( 'https://dashboard.altis-dxp.com/#/%s/%s', HM_ENV_REGION, get_environment_name() ) ),
			'meta'   => [
				'target' => '_blank',
			],
		] );
	}
}

/**
 * Enqueue the environment indicator styles.
 */
function enqueue_admin_scripts() {
	wp_enqueue_style( 'altis-env-indicator', plugin_dir_url( dirname( __FILE__, 2 ) ) . 'assets/environment-indicator.css', [], '2020-02-03-1' );
}
