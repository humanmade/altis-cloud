<?php
/**
 * Altis Cloud Media Purge from CDN.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Cloudfront_Media_Purge;

use Altis\Cloud;

/**
 * Set up action hooks.
 *
 * @return void
 */
function bootstrap() {
	add_action( 'delete_attachment', __NAMESPACE__ . '\\purge_media_file_cache', 100 );
}

/**
 * Purge an attachment file cache from CloudFront.
 *
 * It sends a request to invalidate all sizes given for a media file.
 *
 * @param int $media_id Attachment ID.
 * @return bool
 */
function purge_media_file_cache( int $media_id ) {
	$upload_url       = wp_get_attachment_url( $media_id );
	$upload_path      = wp_parse_url( $upload_url, PHP_URL_PATH );
	$upload_path_info = pathinfo( $upload_path );
	$items            = [];
	$items[]          = $upload_path_info['dirname'] . '/' . $upload_path_info['filename'] . '*';
	if ( function_exists( 'tachyon_url' ) ) {
		$tachyon_url       = tachyon_url( $upload_url );
		$tachyon_path      = wp_parse_url( $tachyon_url, PHP_URL_PATH );
		$tachyon_path_info = pathinfo( $tachyon_path );
		$items[]           = $tachyon_path_info['dirname'] . '/' . $tachyon_path_info['filename'] . '*';
	}

	return Cloud\purge_cdn_paths( $items );
}
