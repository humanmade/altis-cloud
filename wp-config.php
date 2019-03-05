<?php

/**
 * On HM Cloud, all configuration of DB constants (etc) will be put
 * in to a wp-config-production.php in the web root.
 */

if ( file_exists( dirname( ABSPATH ) . '/wp-config-production.php' ) ) {
	require_once dirname( ABSPATH ) . '/wp-config-production.php';
}
