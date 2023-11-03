<?php
/**
 * Altis Afterburner data output for HTML pages in the Query Monitor plugin.
 *
 * @package altis/dev-tools
 */

namespace Altis\Cloud\Afterburner;

use Afterburner;
use QM_Collector;

/**
 * Altis Afterburner QM Panel Class.
 *
 * @package altis/dev-tools
 */
class QM_Output_Html extends \QM_Output_Html {

	/**
	 * Altis Config QM Panel Constructor.
	 */
	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', [ $this, 'admin_menu' ] );
	}

	/**
	 * Panel name.
	 *
	 * @return string
	 */
	public function name() {
		return esc_html_x( 'Afterburner', 'Menu item name for the Query Monitor plugin', 'altis' );
	}

	/**
	 * Panel content.
	 *
	 * @return void
	 */
	public function output() {
		$stats = Afterburner\ObjectCache\getStats();
		?>
		<?php $this->before_tabular_output(); ?>

		<tbody>
			<tr>
				<td><?php echo esc_html__( 'Afterburner connection string', 'altis' ); ?></td>
				<td><code><?php echo esc_html( ini_get( 'afterburner.redis_server_info' ) ) ?></code></td>
			</tr>
			<tr>
				<td><?php echo esc_html__( 'redis_skip_server_check', 'altis' ); ?></td>
				<td><code><?php echo esc_html( ini_get( 'afterburner.redis_skip_server_check' ) ) ?></code></td>
			</tr>
			<tr>
				<td><?php echo esc_html__( 'Max items in LRU cache', 'altis' ); ?></td>
				<td><code><?php echo esc_html( ini_get( 'afterburner.lru_cache_max_items' ) ) ?></code></td>
			</tr>
			<tr>
				<td><?php echo esc_html__( 'Items in LRU cache for this PHP worker', 'altis' ); ?></td>
				<td><code><?php echo esc_html( $stats->lru_cache_items ) ?></code></td>
			</tr>
			<tr>
				<td><?php echo esc_html__( 'Total memory used in LRU cache for this PHP worker', 'altis' ); ?></td>
				<td><code><?php echo esc_html( size_format( $stats->lru_cache_size ) ) ?></code></td>
			</tr>
			<tr>
				<td><?php echo esc_html__( 'Redis total time (ms) for this PHP worker', 'altis' ); ?></td>
				<td><code><?php echo esc_html( round( $stats->redis_total_time / 1000 ) ) ?>ms</code></td>
			</tr>
			<tr>
			<td><?php echo esc_html__( 'All stats for this PHP worker', 'altis' ); ?></td>
				<td><pre><?php echo esc_html( print_r( $stats, true ) ) ?></pre></td>
		</tbody>

		<?php
		$this->after_tabular_output();
	}
}
