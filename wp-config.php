<?php
/**
 * On Altis, all configuration of DB constants (etc) will be put
 * in to a wp-config-production.php in the web root.
 *
 * @package altis/cloud
 */

// Don't self-initialize if this is not an Altis execution.
if ( ! function_exists( 'add_action' ) ) {
	return;
}

if ( file_exists( Altis\ROOT_DIR . '/wp-config-production.php' ) ) {
	require_once Altis\ROOT_DIR . '/wp-config-production.php';
}
