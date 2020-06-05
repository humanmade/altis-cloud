<?php
/**
 * Altis Cloud Healthcheck CLI.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Healthcheck;

use cli;
use WP_CLI;
use WP_CLI_Command;

/**
 * CLI COmmand to run Altis Healthchecks.
 */
class CLI_Command extends WP_CLI_Command {

	/**
	 * Run health checks.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format e.g. yaml, json
	 * default: table
	 *
	 * ## EXAMPLES
	 *
	 * wp healthcheck run
	 *
	 * wp healthcheck run --format=json
	 *
	 * @param array $args Positional arguments array.
	 * @param array $args_assoc Named arguments array.
	 */
	function run( array $args, array $args_assoc ) {
		$args_assoc = wp_parse_args( $args_assoc, [
			'format' => 'table',
		]);
		$checks = run_checks();

		$data = [];
		$passed = true;
		foreach ( $checks as $check => $result ) {
			if ( is_wp_error( $result ) ) {
				$passed = false;
			}
			$data[] = [
				'check' => $check,
				'status' => is_wp_error( $result ) ? cli\Colors::colorize( '%R' . $result->get_error_message() . '%n' ) : cli\Colors::colorize( '%GPassed%n' ),
			];
		}
		WP_CLI\utils\format_items( $args_assoc['format'], $data, [ 'check', 'status' ] );

		// Send exit code.
		WP_CLI::halt( $passed ? 0 : 1 );
	}

	/**
	 * Run instance health checks.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format e.g. yaml, json
	 * default: table
	 *
	 * ## EXAMPLES
	 *
	 * wp healthcheck instance
	 *
	 * wp healthcheck instance --format=json
	 *
	 * @param array $args Positional arguments array.
	 * @param array $args_assoc Named arguments array.
	 */
	function instance( array $args, array $args_assoc ) {
		$args_assoc = wp_parse_args( $args_assoc, [
			'format' => 'table',
		]);

		$checks = run_instance_checks();

		$data   = [];
		$passed = true;

		foreach ( $checks as $check => $result ) {
			if ( is_wp_error( $result ) ) {
				$passed = false;
			}
			$data[] = [
				'check' => $check,
				'status' => is_wp_error( $result ) ? cli\Colors::colorize( '%R' . $result->get_error_message() . '%n' ) : cli\Colors::colorize( '%GPassed%n' ),
			];
		}
		WP_CLI\utils\format_items( $args_assoc['format'], $data, [ 'check', 'status' ] );

		// Send exit code.
		WP_CLI::halt( $passed ? 0 : 1 );
	}
}
