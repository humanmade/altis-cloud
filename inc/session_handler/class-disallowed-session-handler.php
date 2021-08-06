<?php
/**
 * Altis Cloud Session handler.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Session_Handler;

/**
 * Disallowed Session handler class.
 *
 * Disables creation of sessions via short-circuiting session_open() calls.
 */
class DisallowedSessionHandler implements \SessionHandlerInterface {
	function open( $savePath, $sessionName ) {
		error_log( 'Sessions are not enabled in Altis config.' );
		return false;
	}
	function close() {}
	function destroy( $id ) {}
	function read( $id ) {}
	function write( $id, $data ) {}
	function gc( $maxlifetime ) {}
}