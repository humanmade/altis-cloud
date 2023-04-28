<?php
/**
 * Altis Cloud Session handler.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Session_Handler;

use SessionHandlerInterface;

/**
 * WP Cache Session Handler.
 *
 * Uses the WordPress object cache to store and retrieve session data.
 */
class WP_Cache_Session_Handler implements SessionHandlerInterface {
	protected int $ttl;

	/**
	 * Initialize the session handler.
	 */
	public function __construct() {
		wp_cache_add_global_groups( 'sessions' );
		$this->ttl = ini_get( 'session.gc_maxlifetime' );
	}

	/**
	 * Initialize a session.
	 *
	 * No-op in this session handler as there is nothing to
	 * initialize, the object cache takes care of all that.
	 *
	 * @param string $path
	 * @param string $name
	 *
	 * @return bool
	 */
	public function open( string $path, string $name ) : bool {
		return true;
	}

	/**
	 * Close the session.
	 *
	 * No-op as there is nothing to close.
	 *
	 * @return bool
	 */
	public function close() : bool {
		return true;
	}

	/**
	 * Destroy a session.
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function destroy( string $id ) : bool {
		return wp_cache_delete( $id, 'sessions' );
	}

	/**
	 * Read session data.
	 *
	 * @param string $id
	 *
	 * @return string|false
	 */
	public function read( string $id ) : string | false {
		$data = wp_cache_get( $id, 'sessions' );
		if ( ! $data ) {
			return '';
		}

		return $data;
	}

	/**
	 * Write session data.
	 *
	 * @param string $id
	 * @param string $data
	 *
	 * @return bool
	 */
	public function write( string $id, string $data ) : bool {
		return wp_cache_set( $id, $data, 'sessions', $this->ttl );
	}

	/**
	 * Clean up old essions.
	 *
	 * No-op as session expiration is taken care of
	 * in Redis with our set TTLs.
	 *
	 * @return int|bool
	 */
	public function gc( int $maxlifetime ) : int | false {
		return 0;
	}
}
