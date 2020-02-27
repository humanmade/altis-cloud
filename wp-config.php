<?php

/**
 * On Altis, all configuration of DB constants (etc) will be put
 * in to a wp-config-production.php in the web root.
 */

use const Altis\ROOT_DIR;

if ( file_exists( ROOT_DIR . '/wp-config-production.php' ) ) {
	require_once ROOT_DIR . '/wp-config-production.php';
}
