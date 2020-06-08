<?php
/**
 * Altis Cloud All Options Fix.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Alloptions_Fix;

/**
 * Set up action hooks.
 *
 * @return void
 */
function bootstrap() {
	add_action( 'added_option', __NAMESPACE__ . '\\maybe_clear_alloptions_cache' );
	add_action( 'updated_option', __NAMESPACE__ . '\\maybe_clear_alloptions_cache' );
	add_action( 'deleted_option', __NAMESPACE__ . '\\maybe_clear_alloptions_cache' );
}

/**
 * Fix a race condition in alloptions caching.
 *
 * See https://github.com/humanmade/hm-platform/issues/132
 * See https://core.trac.wordpress.org/ticket/31245#comment:57
 *
 * @param string $option The option name.
 */
function maybe_clear_alloptions_cache( $option ) {
	if ( wp_installing() ) {
		return;
	}

	$alloptions = wp_load_alloptions(); // alloptions should be cached at this point.

	// Only if option is among alloptions.
	if ( isset( $alloptions[ $option ] ) ) {
		wp_cache_delete( 'alloptions', 'options' );
	}
}
