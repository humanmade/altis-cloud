<?php
/**
 * Altis Cloud Performance Optimizations.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Performance_Optimizations;

/**
 * Set up optimizations.
 *
 * @return void
 */
function bootstrap() {
	increase_set_time_limit_on_async_upload();
}

/**
 * Set the execution time out when uploading images.
 *
 * "async-upload.php" / uploading an attachment does not change the execution time limit
 * in WordPress Core when you upload files. If the site has a lot of image sizes, this
 * can lead to max execution fatal errors.
 */
function increase_set_time_limit_on_async_upload() {
	if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	$is_accepted_method = in_array( $_SERVER['REQUEST_METHOD'], [ 'PATCH', 'POST', 'PUT' ], true );
	$is_async_upload = strpos( $_SERVER['REQUEST_URI'], '/wp-admin/async-upload.php' ) !== false;
	$is_rest_api_upload = strpos( $_SERVER['REQUEST_URI'], '/wp/v2/media' ) !== false;
	if ( ! $is_accepted_method ) {
		return;
	}
	if ( ! $is_async_upload && ! $is_rest_api_upload ) {
		return;
	}
	$time = ini_get( 'max_execution_time' );
	if ( $time === 0 || $time >= 120 ) {
		return;
	}
	set_time_limit( 120 );
}
