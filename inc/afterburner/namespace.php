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
	add_filter( 'pre_load_textdomain', __NAMESPACE__ . '\\pre_load_textdomain', 10, 4 );
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

/**
 * Short-circuit load_textdomain to make sure Afterburner's MO is used.
 *
 * @param bool|null $load Return non-null to short-circuit.
 * @param string $domain The text domain being loaded.
 * @param string $mofile The path the the .mo file being loaded.
 * @param string|null $locale The local being loaded.
 *
 * @return null|bool True if loaded successfully, false if could not load, null to continue core's routine.
 */
function pre_load_textdomain( $load, $domain, $mofile, $locale ) {
	global $wp_textdomain_registry, $l10n;

	if ( version_compare( $GLOBALS['wp_version'], '6.5.0', '<' ) ) {
		return $load;
	}

	if ( (bool) apply_filters( 'override_load_textdomain', false, $domain, $mofile, $locale ) ) {
		return true;
	}

	do_action( 'load_textdomain', $domain, $mofile );

	$mofile = apply_filters( 'load_textdomain_mofile', $mofile, $domain );

	if ( ! is_readable( $mofile ) ) {
		return false;
	}

	if ( ! $locale ) {
		$locale = determine_locale();
	}

	$mo = new \MO();
	if ( ! $mo->import_from_file( $mofile ) ) {
		$wp_textdomain_registry->set( $domain, $locale, false );

		return false;
	}

	if ( isset( $l10n[ $domain ] ) ) {
		$mo->merge_with( $l10n[ $domain ] );
	}

	$l10n[ $domain ] = &$mo;
	$wp_textdomain_registry->set( $domain, $locale, dirname( $mofile ) );
	return true;
}

