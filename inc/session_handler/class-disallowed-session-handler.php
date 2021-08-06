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
class Disallowed_Session_Handler implements \SessionHandlerInterface {
	/**
	 * @return bool
	 */
	function open( $save_path, $session_name ) {
		error_log( 'Sessions are not enabled in Altis config.' );
		return false;
	}
	/**
	 * @return void
	 */
	function close() {}
	/**
	 * @return void
	 */
	function destroy( $id ) {}
	/**
	 * @return void
	 */
	function read( $id ) {}
	/**
	 * @return void
	 */
	function write( $id, $data ) {}
	/**
	 * @return void
	 */
	function gc( $maxlifetime ) {}
}
