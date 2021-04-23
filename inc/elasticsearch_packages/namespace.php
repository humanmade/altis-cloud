<?php
/**
 * AES Package File Integration.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Elasticsearch_Packages;

use Altis;
use Aws\ElasticsearchService\ElasticsearchServiceClient;
use Exception;
use WP_Error;

// Package deletion status constants.
const DELETED = 'DELETED';
const DELETE_SCHEDULED = 'DELETE_SCHEDULED';

/**
 * Hook into search module package support.
 *
 * @return void
 */
function bootstrap() : void {
	// Check the current environment and bail if it isn't AWS.
	if ( ! in_array( Altis\get_environment_architecture(), [ 'ec2', 'ecs' ], true ) ) {
		return;
	}

	// Add a short time out cron hook.
	// phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
	add_filter( 'cron_schedules', __NAMESPACE__ . '\\cron_schedules' );

	// Hook into search module.
	add_filter( 'altis.search.packages_dir', __NAMESPACE__ . '\\packages_dir', 9 );
	add_filter( 'altis.search.create_package_id', __NAMESPACE__ . '\\create_package_id', 10, 4 );
	add_filter( 'altis.search.get_package_id', __NAMESPACE__ . '\\get_package_id', 10, 2 );
	add_action( 'altis.search.check_package_status', __NAMESPACE__ . '\\on_check_package_status', 10, 3 );
	add_action( 'altis.search.delete_package', __NAMESPACE__ . '\\delete_package', 10, 2 );
	add_action( 'altis.search.deleted_package', __NAMESPACE__ . '\\on_deleted_package', 10, 4 );
	add_action( 'altis.search.updated_all_index_settings', __NAMESPACE__ . '\\on_update_index_settings' );

	// Remove default packages updated hook so we don't try to update indexes until
	// packages have been associated with the domain.
	add_action( 'plugins_loaded', function () {
		remove_action( 'altis.search.updated_packages', 'Altis\\Enhanced_Search\\Packages\\on_updated_packages', 10, 2 );
	}, 20 );
}

/**
 * Filter the cron schedules list.
 *
 * @param array $schedules The list of available cron schedules.
 * @return array
 */
function cron_schedules( array $schedules ) : array {
	$schedules['minutely'] = [
		'display' => __( 'Every minute', 'altis' ),
		'interval' => 60,
	];
	return $schedules;
}

/**
 * Set the package upload directory to an S3 directory.
 *
 * @param string $path The directory to store packages in.
 * @return string
 */
function packages_dir( string $path ) : string {
	if ( ! defined( 'S3_UPLOADS_BUCKET' ) || empty( S3_UPLOADS_BUCKET ) ) {
		return $path;
	}

	return sprintf( 's3://%s/es-packages', S3_UPLOADS_BUCKET );
}

/**
 * Get Elasticsearch Service Client.
 *
 * @return ElasticsearchServiceClient
 */
function get_es_client() : ElasticsearchServiceClient {
	return Altis\get_aws_sdk()->createElasticsearchService( [
		'version' => '2015-01-01',
	] );
}

/**
 * Get the domain ID portion of the Elasticsearch Service host name.
 *
 * @return string
 */
function get_elasticsearch_domain() : string {
	return preg_replace( '#^search-([a-z][a-z0-9\-]+)-[a-z0-9]+\..*$#', '$1', ELASTICSEARCH_HOST );
}

/**
 * Create the package on AES and return the package ID string.
 *
 * @param string|null $package_id The package file path for ES mappings.
 * @param string $slug The package slug.
 * @param string $file The package file path.
 * @param bool $for_network Whether this is a network level package or not.
 * @return string|WP_Error
 */
function create_package_id( ?string $package_id, string $slug, string $file, bool $for_network = false ) {
	// Check file is an S3 file path.
	if ( strpos( $file, 's3://' ) !== 0 ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return new WP_Error( 'package_file_missing', sprintf( 'The given package filepath %s is not a valid S3 file path.', $file ) );
	}

	// Check domain.
	if ( ! defined( 'ELASTICSEARCH_HOST' ) || ! ELASTICSEARCH_HOST ) {
		return new WP_Error( 'elasticsearch_host_not_found', 'Could not find an AWS ElasticSearch Service Domain to associate the package with.' );
	}

	// Get AES client.
	$client = get_es_client();

	// Get Domain ID from host.
	$domain = get_elasticsearch_domain();

	// Get a unique package name.
	// Must be no more than 28 chars.
	$name = substr( sprintf(
		'p%s-%s',
		hash( 'crc32', $domain . $file . time() ),
		trim( basename( $file, '.txt' ), '-' )
	), 0, 28 );
	$name = sanitize_key( $name );

	try {
		// Derive S3 bucket and path.
		if ( ! preg_match( '#^s3://([^/]+)/(.*?)$#', $file, $s3_file_parts ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return new WP_Error( 'elasticsearch_s3_path', sprintf( 'Unable to extract S3 bucket name and key from file path: %s', $file ) );
		}

		$s3_bucket = $s3_file_parts[1];
		$s3_key = $s3_file_parts[2];

		// Create a new package.
		$result = $client->createPackage( [
			'PackageName' => $name, // required.
			'PackageDescription' => $file,
			'PackageSource' => [ // required.
				'S3BucketName' => $s3_bucket,
				'S3Key' => $s3_key,
			],
			'PackageType' => 'TXT-DICTIONARY',
		] );
	} catch ( Exception $e ) {
		return new WP_Error( 'create_package_error', $e->getMessage() );
	}

	$package = $result['PackageDetails'];
	$status = $package['PackageStatus'];

	// Set package status.
	if ( $for_network ) {
		update_site_option( "altis_search_package_status_{$slug}", $status );
		update_site_option( "altis_search_package_error_{$slug}", $package['ErrorDetails']['ErrorMessage'] ?? null );
	} else {
		update_option( "altis_search_package_status_{$slug}", $status );
		update_option( "altis_search_package_error_{$slug}", $package['ErrorDetails']['ErrorMessage'] ?? null );
	}

	$package_id = sprintf( 'analyzers/%s', $package['PackageID'] );

	// Get old package ID to remove if one exists - access options directly to bypass status check.
	if ( $for_network ) {
		$existing_package_id = get_site_option( "altis_search_package_{$slug}", null );
	} else {
		$existing_package_id = get_option( "altis_search_package_{$slug}", null );
	}

	// Queue up package status check & association routine.
	if ( ! in_array( $status, [ 'AVAILABLE', 'COPYING', 'VALIDATING' ], true ) ) {
		return new WP_Error( 'create_package_error', sprintf( 'Package upload error, current status is %s', $status ) );
	}

	wp_schedule_event( time(), 'minutely', 'altis.search.check_package_status', [
		$package_id,
		$slug,
		$for_network,
		$existing_package_id,
	] );

	// Return the AES package ID string.
	return $package_id;
}

/**
 * Get the package ID.
 *
 * This callback checks whether the package has finished associating with the AES domain
 * before returning its ID.
 *
 * @param string|null $package_id The stored package ID.
 * @param string $slug The slug for the package.
 * @param boolean $for_network Whether this is a network level package.
 * @return string|null
 */
function get_package_id( ?string $package_id, string $slug, bool $for_network = false ) : ?string {
	// Check status.
	if ( $for_network ) {
		$status = get_site_option( "altis_search_package_status_{$slug}", 'COPYING' );
	} else {
		$status = get_option( "altis_search_package_status_{$slug}", 'COPYING' );
	}

	if ( $status !== 'ACTIVE' ) {
		return null;
	}

	return $package_id;
}

/**
 * Add a package ID to the list of packages to be removed.
 *
 * @param string $package_id The package ID to remove.
 * @return void
 */
function add_package_to_remove( string $package_id ) : void {
	$packages = get_site_option( 'altis_search_packages_to_remove', [] );
	$packages[] = $package_id;
	$packages = array_unique( $packages );
	update_site_option( 'altis_search_packages_to_remove', $packages );
}

/**
 * Get the list of packages to remove.
 *
 * @return array
 */
function get_packages_to_remove() : array {
	return get_site_option( 'altis_search_packages_to_remove', [] );
}

/**
 * Check and update the package association status.
 *
 * @param string $package_id The package ID to look up.
 * @param string $slug The package slug.
 * @param boolean $for_network Whether this is a network level package.
 * @param string|null $existing_package_id An existing package ID to be removed.
 * @throws Exception
 * @return void
 */
function on_check_package_status( string $package_id, string $slug, bool $for_network = false, ?string $existing_package_id = null ) : void {
	$client = get_es_client();

	$real_package_id = str_replace( 'analyzers/', '', $package_id );

	// Set scheduled event hook arguments.
	$check_status_hook_args = [ $package_id, $slug, $for_network, $existing_package_id ];
	$update_index_hook_args = [ $for_network, strpos( $slug, 'user-dictionary' ) !== false ];

	try {
		$packages = $client->listDomainsForPackage( [
			'PackageID' => $real_package_id,
		] );

		// Package may not have finished copying over yet.
		if ( empty( $packages['DomainPackageDetailsList'] ) ) {
			$packages = $client->describePackages( [
				'Filters' => [
					[
						'Name' => 'PackageID',
						'Value' => [ $real_package_id ],
					],
				],
			] );

			// We shouldn't get to this point without having run the create package step
			// but this ensures we handle that scenario in case it comes up.
			if ( empty( $packages['PackageDetailsList'] ) ) {
				throw new Exception( sprintf( 'Elasticsearch package %s not found. Try saving it again or contact support.', $package_id ) );
			}

			$package = $packages['PackageDetailsList'][0];
			$status = $package['PackageStatus'];

			// Associate the package with the ES domain.
			// NOTE: This process is async so cannot be applied immediately.
			if ( $package['PackageStatus'] === 'AVAILABLE' ) {
				$result = $client->associatePackage( [
					'DomainName' => get_elasticsearch_domain(), // required.
					'PackageID' => $real_package_id, // required.
				] );
				$package = $result['DomainPackageDetails'];
				$status = $package['DomainPackageStatus'];
			}
		} else {
			// Package is currently being associated or is active.
			$package = $packages['DomainPackageDetailsList'][0];
			$status = $package['DomainPackageStatus'];
		}

		// Update the stored status.
		if ( $for_network ) {
			update_site_option( "altis_search_package_status_{$slug}", $status );
			update_site_option( "altis_search_package_error_{$slug}", $package['ErrorDetails']['ErrorMessage'] ?? null );
		} else {
			update_option( "altis_search_package_status_{$slug}", $status );
			update_option( "altis_search_package_error_{$slug}", $package['ErrorDetails']['ErrorMessage'] ?? null );
		}

		// Queue up another check if we're still copying, associating or validating.
		if ( $status !== 'ACTIVE' ) {
			$recheck_statuses = [ 'COPYING', 'ASSOCIATING', 'VALIDATING' ];
			if ( ! in_array( $status, $recheck_statuses, true ) ) {
				$error_message = sprintf( 'Package %s has encountered an error. See the status for more details.', $package_id );
				throw new Exception( $error_message );
			}
			// We only want to continue processing if the package is now active.
			return;
		}

		// Add the existing package ID to the list of packages to remove.
		// These packages are removed after the index settings are updated.
		if ( $existing_package_id ) {
			add_package_to_remove( $existing_package_id );
		}

		// Unschedule this status check.
		$next = wp_next_scheduled( 'altis.search.check_package_status', $check_status_hook_args );
		if ( $next ) {
			wp_unschedule_event( $next, 'altis.search.check_package_status', $check_status_hook_args );
		}

		// Schedule the index settings update.
		if ( ! wp_next_scheduled( 'altis.search.update_index_settings', $update_index_hook_args ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'altis.search.update_index_settings', $update_index_hook_args );
		}
	} catch ( Exception $e ) {
		// Unschedule this hook.
		$next = wp_next_scheduled( 'altis.search.check_package_status', $check_status_hook_args );
		if ( $next ) {
			wp_unschedule_event( $next, 'altis.search.check_package_status', $check_status_hook_args );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( $e->getMessage(), E_USER_WARNING );
	}
}

/**
 * Loop over packages to remove and delete them. Called on
 * the altis.search.updated_all_index_settings hook.
 *
 * @return void
 */
function on_update_index_settings() : void {
	$packages_to_remove = get_packages_to_remove();

	foreach ( $packages_to_remove as $package_id ) {
		delete_package( $package_id );
	}

	// Empty the array of packages to be removed.
	update_site_option( 'altis_search_packages_to_remove', [] );
}

/**
 * Handle direct package deletion.
 *
 * @param string $package_id The old package ID that has been deleted.
 * @return void
 */
function on_deleted_package( string $package_id ) : void {
	delete_package( $package_id );
}

/**
 * Delete a package off Elasticsearch Service.
 *
 * Returns the status DELETED or DELETE_SCHEDULED on success, otherwise
 * a WP_Error object is returned.
 *
 * @param string $package_id The package ID to delete.
 * @param int $max_retries The maximum number of times to attempt deletion.
 * @return string|WP_Error
 */
function delete_package( string $package_id, int $max_retries = 5 ) {
	$client = get_es_client();

	$real_package_id = str_replace( 'analyzers/', '', $package_id );

	// Check if we've hit the retry limit.
	if ( $max_retries === 0 ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( sprintf( 'Error deleting package %s on Elasticsearch Service, max retries reached', $real_package_id ), E_USER_WARNING );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return new WP_Error( 'delete_package_retries', sprintf( 'Error deleting package %s on Elasticsearch Service, max retries reached', $real_package_id ) );
	}

	try {
		// First check the package is no longer associated with the domain.
		$packages = $client->listDomainsForPackage( [
			'PackageID' => $real_package_id,
		] );

		if ( ! empty( $packages['DomainPackageDetailsList'] ) ) {
			// Package is currently being associated or is active.
			$package = $packages['DomainPackageDetailsList'][0];
			$status = $package['DomainPackageStatus'];

			// Dissociate the package if it's currently active or dissociation failed.
			if ( $status === 'DISSOCIATION_FAILED' || $status === 'ACTIVE' ) {
				$client->dissociatePackage( [
					'DomainName' => get_elasticsearch_domain(), // required.
					'PackageID' => $real_package_id, // required.
				] );
			}

			// Reschedule deletion attempt.
			wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), 'altis.search.delete_package', [
				$package_id,
				$max_retries - 1,
			] );

			return DELETE_SCHEDULED;
		}

		// Package is not associated so we can safely delete it.
		$result = $client->deletePackage( [
			'PackageID' => $real_package_id,
		] );

		$package = $result['PackageDetails'];
		$status = $package['PackageStatus'];

		// Log an error if the delete failed.
		if ( $status === 'DELETE_FAILED' ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			trigger_error( $package['ErrorDetails']['ErrorMessage'] ?? sprintf( 'Error deleting package %s on Elasticsearch Service', $real_package_id ), E_USER_WARNING );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return new WP_Error( 'delete_package_failed', $package['ErrorDetails']['ErrorMessage'] ?? sprintf( 'Error deleting package %s on Elasticsearch Service', $real_package_id ) );
		}
	} catch ( Exception $e ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( 'Error deleting package on Elasticsearch Service: ' . $e->getMessage(), E_USER_WARNING );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return new WP_Error( 'delete_package_error', 'Error deleting package on Elasticsearch Service: ' . $e->getMessage() );
	}

	return DELETED;
}
