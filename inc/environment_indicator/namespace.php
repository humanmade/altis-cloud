<?php

namespace Altis\Cloud\Environment_Indicator;

use function Altis\get_environment_name;
use function Altis\get_environment_type;

function bootstrap() {
	add_action( 'admin_menu', __NAMESPACE__ . '\\add_menu_page' );
}

function add_menu_page() {
	\add_menu_page(
		__NAMESPACE__ . '\\get_page_title',
		strtoupper( get_environment_type() ),
		'manage_options',
		'environment-indicator',
		__NAMESPACE__ . '\\display_environment_details',
		'dashicons-laptop',
		1
	);

	\add_submenu_page(
		'environment-indicator',
		__NAMESPACE__ . '\\get_page_title',
		get_environment_name(),
		'manage_options',
		'environment-indicator-name',
		__NAMESPACE__ . '\\display_environment_details'
	);
}

function display_environment_details() {
	?>
	<div class="wrap">
		<h1><?php echo get_page_title(); ?></h1>

		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Environment Type', 'altis' ); ?></th>
				<td><?php echo get_environment_type(); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Environment Name', 'altis' ); ?></th>
				<td>
					<?php
					// Link to the stack on the Altis dashboard.
					if ( get_environment_name() !== 'unknown' ) {
						printf( '<a href="%s">%s</a>', '#', get_environment_name() );
					}
					else {
						echo get_environment_name();
					}
					?>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
	<?php
}

function get_page_title() {
	return esc_html__( 'Environment Details', 'altis' );
}
