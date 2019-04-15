<?php

/**
 * On HM Cloud, all configuration of DB constants (etc) will be put
 * in to a wp-config-production.php in the web root.
 */

use const HM\Platform\ROOT_DIR;

// Don't self-initialize if this is not a Platform execution.
if ( ! function_exists( 'add_action' ) ) {
	return;
}

if ( file_exists( ROOT_DIR . '/wp-config-production.php' ) ) {
	require_once ROOT_DIR . '/wp-config-production.php';
}
