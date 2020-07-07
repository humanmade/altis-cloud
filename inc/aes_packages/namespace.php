<?php
/**
 *
 */

namespace Altis\Cloud\AES_Packages;

function setup() {

}

function create_package() {

	// Check file is an S3 file path.
	if ( strpos( $file, 's3://' ) !== 0 ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( sprintf( 'The given package filepath %s is not a valid S3 file path.', $file ), E_USER_WARNING );
		return null;
	}

	// Check domain.
	if ( ! defined( 'ELASTICSEARCH_HOST' ) || ! ELASTICSEARCH_HOST ) {
		trigger_error( 'Could not find an AWS ElasticSearch Service Domain to associate the package with.', E_USER_WARNING );
		return null;
	}

	// Get AES client.
	$client = get_aes_client();

	// Get Domain ID from host.
	$domain = preg_replace( '#^search-([a-z][a-z0-9\-]+)-[a-z0-9]+\..*$#', '$1', ELASTICSEARCH_HOST );

	// Use the filename for the package name.
	// Must be no more than 28 chars.
	$name = substr( sprintf(
		'p%s-%s-%s',
		hash( 'crc32', $domain ),
		hash( 'crc32', $file ),
		trim( basename( $file, '.txt' ), '-' )
	), 0, 28 );

	try {
		// Get old package if one exists with same name.
		$existing = $client->describePackages( [
			'Filters' => [
				[
					'Name' => 'PackageName',
					'Value' => [ $name ],
				],
			],
		] );

		// Dissociate and remove the old version of the package.
		if ( ! empty( $existing['PackageDetailsList'] ) ) {
			delete_package( $file );
			$existing_package_id = $existing['PackageDetailsList'][0]['PackageID'];
			$client->dissociatePackage( [
				'DomainName' => $domain, // required.
				'PackageID' => $existing_package_id, // required.
			] );
			$client->deletePackage( [
				'PackageID' => $existing_package_id,
			] );
		}
	} catch ( \Exception $e ) {
		trigger_error( 'Error removing package on AES: ' . $e->getMessage(), E_USER_WARNING );
		return null;
	}

	try {
		// Derive S3 bucket and path.
		preg_match( '#^s3://([^/]+)/(.*?)$#', $file, $s3_file_parts );
		$s3_bucket = $s3_file_parts[1];
		$s3_key = $s3_file_parts[2];

		// Create a new package.
		$new_package = $client->createPackage( [
			'PackageName' => $name, // required.
			'PackageDescription' => $file,
			'PackageSource' => [ // required.
				'S3BucketName' => $s3_bucket,
				'S3Key' => $s3_key,
			],
			'PackageType' => 'TXT-DICTIONARY',
		] );

		// Associate the package with the ES domain.
		$client->associatePackage( [
			'DomainName' => $domain, // required.
			'PackageID' => $new_package['PackageDetails']['PackageID'], // required.
		] );

		//
	} catch ( \Exception $e ) {
		trigger_error( 'Error creating package on AES: ' . $e->getMessage(), E_USER_WARNING );
		return null;
	}
}
