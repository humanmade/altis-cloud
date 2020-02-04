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

	// Environment types text to be displayed in the admin bar.
	$envs = [
		'local'       => esc_html_x( 'local', 'Server environment type', 'altis' ),
		'development' => sprintf( '<abbr title="%s">%s</abbr>',
			esc_attr_x( 'development', 'Server environment type - full form', 'altis' ),
			esc_html_x( 'dev', 'Server environment type - abbreviated', 'altis' )
		),
		'staging'     => esc_html_x( 'staging', 'Server environment type', 'altis' ),
		'production'  => sprintf( '<abbr title="%s">%s</abbr>',
			esc_attr_x( 'production', 'Server environment type - full form', 'altis' ),
			esc_html_x( 'prod', 'Server environment type - abbreviated', 'altis' )
		),
	];

	// Environment type sub-menu item.
	$wp_admin_bar->add_menu( [
		'id'     => 'altis-env-stack-type',
		'title'  => $envs[ get_environment_type() ],
		'parent' => 'altis-env-indicator',
	] );

	// Stop - no Altis dashboard URL is available.
	if ( ! defined( 'HM_ENV_REGION' ) || get_environment_type() === 'local' ) {
		return;
	}

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

/**
 * Enqueue the environment indicator styles.
 */
function enqueue_admin_scripts() {
	wp_enqueue_style( 'altis-env-indicator', plugin_dir_url( dirname( __FILE__, 2 ) ) . 'assets/environment-indicator.css', [], '2020-02-03-1' );
}
