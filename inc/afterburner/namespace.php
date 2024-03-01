<?php
/**
 * Altis Afterburner namespace bootstrap file.
 */

namespace Altis\Cloud\Afterburner;

/**
 * Bootstrap function.
 */
function bootstrap() : void {
	add_filter( 'qm/outputter/html', __NAMESPACE__ . '\\register_qm_output_html' );
}

/**
 * Register the HTML outputter for the Xray panel
 *
 * @param array $output The HTML output for the collector
 * @return array
 */
function register_qm_output_html( array $output ) : array {
	require_once __DIR__ . '/class-qm-output-html.php';
	require_once __DIR__ . '/class-qm-collector.php';

	$output['afterburner'] = new QM_Output_Html( new QM_Collector() );

	return $output;
}
