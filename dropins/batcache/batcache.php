<?php
/*
Plugin name: Batcache Manager
Plugin URI: http://wordpress.org/extend/plugins/batcache/
Description: This optional plugin improves Batcache.
Author: Andy Skelton
Author URI: http://andyskelton.com/
Version: 1.2
*/

// Do not load if our advanced-cache.php isn't loaded
if ( ! isset( $batcache ) || ! is_object( $batcache ) || ! method_exists( $wp_object_cache, 'incr' ) ) {
	return;
}

$batcache->configure_groups();

require_once dirname( __FILE__ ) . '/inc/plugin.php';

// Regen home and permalink on posts and pages
add_action( 'clean_post_cache', 'batcache_post', 10, 2 );

// Regen permalink on comments (TODO)
//add_action('comment_post',          'batcache_comment');
//add_action('wp_set_comment_status', 'batcache_comment');
//add_action('edit_comment',          'batcache_comment');
