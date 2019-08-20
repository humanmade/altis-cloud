<?php
// phpcs:disable HM.Functions.NamespacedFunctions.MissingNamespace

if ( ! function_exists( 'batcache_stats' ) ) {
	function batcache_stats( $name, $value, $num = 1, $today = false, $hour = false ) { }
}

function batcache_cancel() {
	global $batcache;

	if ( is_object( $batcache ) ) {
		$batcache->cancel = true;
	}
}

// Variants can be set by functions which use early-set globals like $_SERVER to run simple tests.
// Functions defined in WordPress, plugins, and themes are not available and MUST NOT be used.
// Example: vary_cache_on_function('return preg_match("/feedburner/i", $_SERVER["HTTP_USER_AGENT"]);');
//          This will cause batcache to cache a variant for requests from Feedburner.
// Tips for writing $function:
//  X_X  DO NOT use any functions from your theme or plugins. Those files have not been included. Fatal error.
//  X_X  DO NOT use any WordPress functions except is_admin() and is_multisite(). Fatal error.
//  X_X  DO NOT include or require files from anywhere without consulting expensive professionals first. Fatal error.
//  X_X  DO NOT use $wpdb, $blog_id, $current_user, etc. These have not been initialized.
//  ^_^  DO understand how create_function works. This is how your code is used: create_function('', $function);
//  ^_^  DO remember to return something. The return value determines the cache variant.
function vary_cache_on_function( $function ) {
	global $batcache;

	if ( preg_match( '/include|require|echo|(?<!s)print|dump|export|open|sock|unlink|`|eval/i', $function ) ) {
		die( 'Illegal word in variant determiner.' );
	}

	if ( ! preg_match( '/\$_/', $function ) ) {
		die( 'Variant determiner should refer to at least one $_ variable.' );
	}

	$batcache->add_variant( $function );
}
