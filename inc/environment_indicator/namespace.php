<?php

namespace Altis\Cloud\Environment_Indicator;

use function Altis\get_environment_name;
use function Altis\get_environment_type;
use WP_Admin_Bar;

function bootstrap() {
	add_action( 'admin_bar_init', __NAMESPACE__ . '\\enqueue_admin_scripts' );
	// Allow other modules to add sub-menu items to Altis admin bar menu first.
	add_action( 'admin_bar_menu', __NAMESPACE__ . '\\add_admin_bar_env_info', 15 );
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

	// Add environment indicator to the Altis logo menu item in the admin bar.
	$node = $wp_admin_bar->get_node( 'altis' );
	$node->meta = [
		'html' => sprintf( '<span class="altis-env-indicator">%s</span>', $envs[ get_environment_type() ] ),
	];
	$wp_admin_bar->add_menu( $node );

	// Stop - no Altis dashboard URL is available. No need to add sub-menu item.
	if ( ! defined( 'HM_ENV_REGION' ) || get_environment_type() === 'local' ) {
		return;
	}

	// Add environment's Altis dashboard URL as a sub-menu item to Altis logo menu in the admin bar.
	$wp_admin_bar->add_menu( [
		'parent' => 'altis',
		'id'     => 'altis-env-stack-url',
		'title'  => __( 'Open in Altis Dashboard', 'altis' ),
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
