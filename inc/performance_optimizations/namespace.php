<?php

namespace Altis\Cloud\Performance_Optimizations;

function bootstrap() {
	if ( strpos( $_SERVER['REQUEST_URI'], '/wp-admin/async-upload.php' ) !== false ) {
		increase_set_time_limit_on_async_upload();
	}

	// add_action( 'wp_footer', __NAMESPACE__ . '\\prefetch_links' );
}

/**
 * Set the execution time out when uploading images.
 *
 * async-upload.php / uploading an attachment does not change the execution time limit
 * in WordPress Core when you upload files. If the site has a lot of image sizes, this
 * can lead to max execution fatal errors.
 *
 */
function increase_set_time_limit_on_async_upload() {
	if ( ini_get( 'max_execution_time' ) < 120 ) {
		set_time_limit( 120 );
	}
}

function prefetch_links() {
	?>
	<script>
		(function(d){
			var links = [];
			addEventListener && d.body.addEventListener( 'click', function ( event ) {
				if (
					event.target.nodeName !== 'A' ||
					links.indexOf( event.target.href ) >= 0 ||
					event.target.href.indexOf( 'http' ) < 0
				) {
					return;
				}
				links.push( event.target.href );
				var link = d.createElement('link');
				link.rel = 'prefetch';
				link.href = event.target.href;
				d.head.appendChild(link);
			} );
		})(document);
	</script>
	<?php
}
