<?php
/**
 * AES Package File Integration.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Elasticsearch_Packages;

use Altis;
use Altis\Enhanced_Search\Packages;
use Aws\ElasticsearchService\ElasticsearchServiceClient;
use Exception;
use WP_Error;

/**
 * Hook into search module package support.
 *
 * @return void
 */
function setup() {
	// Check the current environment and bail if it isn't AWS.
	if ( ! in_array( Altis\get_environment_architecture(), [ 'ec2', 'ecs' ], true ) ) {
		return;
	}

	add_filter( 'altis.search.packages_dir', __NAMESPACE__ . '\\packages_dir', 9 );
	add_filter( 'altis.search.create_package_id', __NAMESPACE__ . '\\create_package_id', 10, 4 );
	add_filter( 'altis.search.get_package_id', __NAMESPACE__ . '\\get_package_id', 10, 2 );
	add_action( 'altis.search.check_package_status', __NAMESPACE__ . '\\on_check_package_status', 10, 3 );
	add_action( 'altis.search.dissociate_package', __NAMESPACE__ . '\\dissociate_package', 10, 2 );
	add_action( 'altis.search.delete_package', __NAMESPACE__ . '\\delete_package', 10, 2 );
	add_action( 'altis.search.deleted_package', __NAMESPACE__ . '\\on_deleted_package', 10, 4 );

	// Remove default packages updated hook so we don't try to update indexes until
	// packages have been associated with the domain.
	add_action( 'plugins_loaded', function () {
		remove_action( 'altis.search.updated_packages', 'Altis\\Enhanced_Search\\Packages\\on_updated_packages', 10, 2 );
	}, 20 );
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
		'region' => Altis\get_environment_region(),
	] );
}

/**
 * Return the domain ID portion of the Elasticsearch Service host name.
 */
function get_elasticsearch_domain() : string {
	return preg_replace( '#^search-([a-z][a-z0-9\-]+)-[a-z0-9]+\..*$#', '$1', 'search-platform-test-36c2d870-ylbu5ldvhmnb2blhnfiqxabzdq.eu-west-1.es.amazonaws.com' ); // ELASTICSEARCH_HOST );
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
function create_package_id( ?string $package_id, string $slug, string $file, bool $for_network = false ) : ?string {

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

	// Get old package ID to remove if one exists - access options directly to bypass status check.
	if ( $for_network ) {
		$existing_package_id = get_site_option( "altis_search_package_{$slug}", false );
	} else {
		$existing_package_id = get_option( "altis_search_package_{$slug}", false );
	}
	if ( $existing_package_id ) {
		// The cleanup does not have to happen straight away so postpone it for an hour
		// to let the settings updates and data indexing complete.
		wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'altis.search.dissociate_package', [
			$existing_package_id,
			$for_network,
		] );
	}

	try {
		// Derive S3 bucket and path.
		preg_match( '#^s3://([^/]+)/(.*?)$#', $file, $s3_file_parts );
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

		$package = $result['PackageDetails'];
		$status = $package['PackageStatus'];

		// Set package status.
		if ( $for_network ) {
			update_site_option( "altis_search_package_status_{$slug}", $status );
			update_site_option( "altis_search_package_error_{$slug}", $package['ErrorDetails'] ?? null );
		} else {
			update_option( "altis_search_package_status_{$slug}", $status );
			update_option( "altis_search_package_error_{$slug}", $package['ErrorDetails'] ?? null );
		}

		// Queue up package status check & association routine.
		if ( in_array( $status, [ 'AVAILABLE', 'COPYING', 'VALIDATING' ], true ) ) {
			wp_schedule_single_event( time() + 10, 'altis.search.check_package_status', [
				$package_id,
				$slug,
				$for_network,
			] );
		}
	} catch ( Exception $e ) {
		return new WP_Error( $e->getCode(), $e->getMessage() );
	}

	$package_id = sprintf( 'analyzers/%s', $package['PackageID'] );

	// Return the AES package ID string.
	return $package_id;
}

/**
 * Check whether the package has finished associating with the AES domain
 * before returning it.
 *
 * @param string|null $package_id The stored package ID.
 * @param string $slug The slug for the package.
 * @param boolean $for_network Whether this is a network level package.
 * @return string|null
 */
function get_package_id( ?string $package_id, string $slug, bool $for_network = false ) {
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
 * Check and update the package association status.
 *
 * @param string $package_id The package ID to look up.
 * @param string $slug The package slug.
 * @param boolean $for_network Whether this is a network level package.
 * @return void
 */
function on_check_package_status( string $package_id, string $slug, bool $for_network = false ) {

	$client = get_es_client();

	$real_package_id = str_replace( 'analyzers/', '', $package_id );

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
			update_site_option( "altis_search_package_error_{$slug}", $package['ErrorDetails'] ?? null );
		} else {
			update_option( "altis_search_package_status_{$slug}", $status );
			update_option( "altis_search_package_error_{$slug}", $package['ErrorDetails'] ?? null );
		}

		// If the package is now active then resend the settings after a short delay to avoid thrashing.
		$update_index_hook_args = [ $for_network, strpos( $slug, 'user-dictionary' ) !== false ];
		if ( $status === 'ACTIVE' && ! wp_next_scheduled( 'altis.search.update_index_settings', $update_index_hook_args ) ) {
			wp_schedule_single_event( time() + 30, 'altis.search.update_index_settings', $update_index_hook_args );
		}

		// Queue up another check if we're still copying, associating or validating.
		$hook_args = [ $package_id, $slug, $for_network ];
		$recheck_statuses = [
			'COPYING',
			'ASSOCIATING',
			'VALIDATING',
		];
		if ( in_array( $status, $recheck_statuses, true ) ) {
			wp_schedule_single_event( time() + 30, 'altis.search.check_package_status', $hook_args );
		}
	} catch ( Exception $e ) {
		Packages\add_error_message( new WP_Error( $e->getCode(), $e->getMessage() ), $for_network );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( $e->getMessage(), E_USER_WARNING );
	}
}

/**
 * Dissociate and delete a package.
 *
 * @param string $package_id The existing package to remove.
 * @param bool $for_network Whether this was a network package or not.
 * @return void
 */
function dissociate_package( string $package_id, bool $for_network = false ) {

	$client = get_es_client();

	$real_package_id = str_replace( 'analyzers/', '', $package_id );

	try {
		$client->dissociatePackage( [
			'DomainName' => get_elasticsearch_domain(), // required.
			'PackageID' => $real_package_id, // required.
		] );

		// Allow 10 minutes for package to be dissociated before removing.
		wp_schedule_single_event( time() + ( 10 * MINUTE_IN_SECONDS ), 'altis.search.delete_package', [
			$package_id,
			$for_network,
		] );
	} catch ( Exception $e ) {
		Packages\add_error_message( new WP_Error( $e->getCode(), $e->getMessage() ), $for_network );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( 'Error dissociating package on Elasticsearch Service: ' . $e->getMessage(), E_USER_WARNING );
	}
}

/**
 * Delete a package off AES.
 *
 * @param string $package_id The package ID to delete.
 * @param bool $for_network Whether this was a network package or not.
 * @return void
 */
function delete_package( string $package_id, bool $for_network = false ) {

	$client = get_es_client();

	$real_package_id = str_replace( 'analyzers/', '', $package_id );

	try {
		$client->deletePackage( [
			'PackageID' => $real_package_id,
		] );
	} catch ( Exception $e ) {
		Packages\add_error_message( new WP_Error( $e->getCode(), $e->getMessage() ), $for_network );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( 'Error deleting package on Elasticsearch Service: ' . $e->getMessage(), E_USER_WARNING );
	}
}

/**
 * Hook triggered when deleting a package directly.
 *
 * @param string $package_id The old package ID that has been deleted.
 * @param string $slug The package slug.
 * @param bool $for_network Whether this was a network package or not.
 */
function on_deleted_package( string $package_id, string $slug, bool $for_network ) {
	dissociate_package( $package_id, $for_network );
}
