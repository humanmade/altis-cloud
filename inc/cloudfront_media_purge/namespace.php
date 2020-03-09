<?php

namespace Altis\Cloud\Cloudfront_Media_Purge;

use Exception;
use function Altis\get_aws_sdk;

function bootstrap() {
	add_action( 'delete_attachment', __NAMESPACE__ . '\\purge_media_file_cache', 100 );
}

/**
 * Return an AWS CloudFront Client instance
 *
 * @return \Aws\CloudFront\CloudFrontClient
 */
function get_aws_client() {
	return get_aws_sdk()->createCloudFront( [
		'version' => 'latest',
	] );
}

/**
 * Purge an attachment file cache from CloudFront
 *
 * It sends a request to invalidate all sizes given for a media file
 *
 * @param int $media_id Attachment ID.
 *
 * @return bool
 */
function purge_media_file_cache( $media_id ) {
	$meta = wp_get_attachment_metadata( $media_id );
	if ( ! $meta ) {
		return false;
	}
	$baseurl = '/tachyon/';
	$basedir = trailingslashit( dirname( $meta['file'] ) );
	$items   = [ $baseurl . $meta['file'] . '*' ];

	if ( ! empty( $meta['sizes'] ) ) {
		foreach ( $meta['sizes'] as $size => $size_meta ) {
			$items[] = $baseurl . $basedir . $size_meta['file'] . '*';
		}
	}

	$client = Altis\Cloud\Cloudfront_Media_Purge\get_aws_client();

	$distribution_id = apply_filters( 'altis_cloudfront_media_purge_distribution_id', defined( 'CLOUDFRONT_DISTRIBUTION_ID' ) ? CLOUDFRONT_DISTRIBUTION_ID : '' );

	if ( empty( $distribution_id ) ) {
		trigger_error( 'Empty cloudfront distribution id for media purge request.', E_USER_WARNING );
		return false;
	}

	try {
		$client->createInvalidation( [
			'DistributionId'    => $distribution_id,
			'InvalidationBatch' => [
				'CallerReference' => current_time( 'timestamp' ),
				'Paths'           => [
					'Items'    => $items,
					'Quantity' => count( $items ),
				],
			],
		] );
	} catch ( Exception $e ) {
		trigger_error( sprintf( 'Media URLs failed to be purged from CloudFront, error %s (%s)', $e->getMessage(), $e->getCode() ), E_USER_WARNING );
		return false;
	}

	return true;
}
