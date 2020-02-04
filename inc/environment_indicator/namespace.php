<?php

namespace Altis\Cloud\Environment_Indicator;

use function Altis\get_environment_name;
use function Altis\get_environment_type;
use WP_Admin_Bar;

function bootstrap() {
	add_action( 'admin_bar_init', __NAMESPACE__ . '\\enqueue_admin_scripts' );
	add_action( 'admin_bar_menu', __NAMESPACE__ . '\\add_admin_bar_env_info' );
}

/**
 * Add menu item to admin bar to display environment info:
 * - environment type
 * - environment name
 * - URL to Altis dashboard for the environment
 *
 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference.
 */
function add_admin_bar_env_info( WP_Admin_Bar $wp_admin_bar ) {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	// Specify capabilities for which environment indicator is shown in the admin bar.
	$capability = apply_filters( 'altis_env_indicator_capability', 'manage_options' );
	if ( ! current_user_can( $capability ) ) {
		return;
	}

	// Add environment menu item to the admin bar.
	$wp_admin_bar->add_menu( [
		'id'   => 'altis-env-indicator',
		'meta' => [
			'class' => 'altis-env-indicator--' . get_environment_type(),
		],
	] );

	// Environment type sub-menu item.
	$env_type_text = strtoupper( get_environment_type() );
	$env_type_text = apply_filters( 'altis_env_indicator_text', $env_type_text, get_environment_type() );

	$wp_admin_bar->add_menu( [
		'id'     => 'altis-env-stack-type',
		'title'  => $env_type_text,
		'parent' => 'altis-env-indicator',
	] );

	// Display environment info for non-local stacks.
	if ( defined( 'HM_ENV_REGION' ) && get_environment_type() !== 'local' ) {
		// Environment name sub-menu item.
		$wp_admin_bar->add_menu( [
			'id'     => 'altis-env-stack-name',
			'title'  => esc_html( get_environment_name() ),
			'parent' => 'altis-env-indicator',
		] );

		// Environment's Altis dashboard URL sub-menu item.
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
