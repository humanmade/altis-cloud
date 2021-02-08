<?php
/**
 * Altis Cloud Fluent Bit logger.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Fluent_Bit;

/**
 * Check if required constants have been defined
 *
 * @return boolean
 */
function is_available() {
	return defined( 'FLUENT_HOST' ) && defined( 'FLUENT_PORT' );
}
