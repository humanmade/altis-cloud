<?php
/**
 * Altis Cloud Session handler.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Session_Handler;

// phpcs:disable Generic.Commenting.DocComment.MissingShort
// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
// phpcs:disable Squiz.Commenting.FunctionComment.Missing

/**
 * Disallowed Session handler class.
 *
 * Disables creation of sessions via short-circuiting session_open() calls.
 */
class Disallowed_Session_Handler implements \SessionHandlerInterface {
	function open( $save_path, $session_name ) {
		error_log( 'Sessions are not enabled in Altis config.' );
		return false;
	}
	function close() {}
	function destroy( $id ) {}
	function read( $id ) {}
	function write( $id, $data ) {}
	function gc( $maxlifetime ) {}
}
