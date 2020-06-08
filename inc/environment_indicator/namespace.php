<?php
/**
 * Altis Cloud Environment Indicator.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Environment_Indicator;

use Altis;
use WP_Admin_Bar;

const SUPPORT_URL = 'https://dashboard.altis-dxp.com/#/support/new';

/**
 * Set up environment indicator hooks.
 *
 * @return void
 */
function bootstrap() {
	add_action( 'admin_bar_init', __NAMESPACE__ . '\\enqueue_admin_scripts' );
	// Allow other modules to add sub-menu items to Altis admin bar menu first.
	add_action( 'admin_bar_menu', __NAMESPACE__ . '\\add_admin_bar_env_info', 15 );
	add_action( 'admin_bar_menu', __NAMESPACE__ . '\\add_admin_bar_dashboard_link', 15 );
	add_action( 'admin_bar_menu', __NAMESPACE__ . '\\add_admin_bar_support_ticket_link', 15 );
}

/**
 * Add menu item to admin bar to display environment info:
 * - environment type
 * - URL to Altis dashboard for the environment
 *
 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference.
 */
function add_admin_bar_env_info( WP_Admin_Bar $wp_admin_bar ) {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	// Specify environments for which to show indicator in the admin bar.
	$show_indicator = in_array( Altis\get_environment_type(), [ 'local', 'development', 'staging' ], true );
	$show_indicator = apply_filters( 'altis.show_environment_indicator', $show_indicator, Altis\get_environment_type() );
	if ( ! $show_indicator ) {
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
	$node->title .= ' ' . sprintf( '<span class="altis-env-indicator">%s</span>', $envs[ Altis\get_environment_type() ] );
	$wp_admin_bar->add_menu( $node );
}

/**
 * Add menu item to admin bar to display environment info:
 * - URL to Altis dashboard for the environment
 *
 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference.
 */
function add_admin_bar_dashboard_link( WP_Admin_Bar $wp_admin_bar ) {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Stop - no Altis dashboard URL is available. No need to add sub-menu item.
	if ( ! defined( 'HM_ENV_REGION' ) ) {
		$title = __( 'Open Altis Dashboard', 'altis' );
		$url = 'https://dashboard.altis-dxp.com/';
	} else {
		$title = __( 'Open in Altis Dashboard', 'altis' );
		$url = esc_url( sprintf( 'https://dashboard.altis-dxp.com/#/%s/%s', HM_ENV_REGION, Altis\get_environment_name() ) );
	}

	// Add environment's Altis dashboard URL as a sub-menu item to Altis logo menu in the admin bar.
	$wp_admin_bar->add_menu( [
		'parent' => 'altis',
		'id' => 'altis-env-stack-url',
		'title' => $title . ' <span class="dashicons-before dashicons-external"></span>',
		'href' => $url,
		'meta' => [
			'target' => '_blank',
		],
	] );
}

/**
 * Add menu item to the Altis logo in the admin bar for creating a support ticket.
 *
 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference.
 */
function add_admin_bar_support_ticket_link( WP_Admin_Bar $wp_admin_bar ) {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	$env_name = Altis\get_environment_name();
	$support_url = SUPPORT_URL;
	if ( 'unknown' !== $env_name ) {
		$support_url .= sprintf( '?applications[]=%s', urlencode( $env_name ) );
	}

	// Add support ticket URL as a sub-menu item to the Altis logo menu in the admin bar.
	$wp_admin_bar->add_menu( [
		'parent' => 'altis',
		'id' => 'altis-support-ticket',
		'title' => __( 'Open Support Ticket', 'altis' ) . ' <span class="dashicons-before dashicons-external"></span>',
		'href' => $support_url,
		'meta' => [
			'target' => '_blank',
		],
	] );
}

/**
 * Enqueue the environment indicator styles.
 */
function enqueue_admin_scripts() {
	wp_enqueue_style( 'altis-env-indicator', plugin_dir_url( dirname( __FILE__, 2 ) ) . 'assets/environment-indicator.css', [], '2020-02-05-3' );
}
