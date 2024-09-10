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
	function open( string $path, string $name ) : bool {
		trigger_error( 'PHP Sessions requires Redis integration, which is currently not activated.', E_USER_WARNING );
		return false;
	}
	function close() : bool {
		trigger_error( 'PHP Sessions requires Redis integration, which is currently not activated.', E_USER_WARNING );
		return false;
	}
	function destroy( string $id ) : bool {
		trigger_error( 'PHP Sessions requires Redis integration, which is currently not activated.', E_USER_WARNING );
		return false;
	}
	/**
	 * @return string|false
	 */
	function read( string $id ) : string|false {
		trigger_error( 'PHP Sessions requires Redis integration, which is currently not activated.', E_USER_WARNING );
		return false;
	}
	function write( string $id, string $data ) : bool {
		trigger_error( 'PHP Sessions requires Redis integration, which is currently not activated.', E_USER_WARNING );
		return false;
	}
	/**
	 * @return int|false
	 */
	function gc( int $max_lifetime ) : int|false {
		trigger_error( 'PHP Sessions requires Redis integration, which is currently not activated.', E_USER_WARNING );
		return false;
	}
}
