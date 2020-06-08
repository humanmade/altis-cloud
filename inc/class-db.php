<?php
/**
 * Altis Cloud Database Dropin.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud;

use HM\Platform\XRay;
use LudicrousDB;
use QM_Backtrace;
use WP_Error;

/**
 * Ludicrous DB integration class.
 *
 * @package altis/cloud
 */
class DB extends LudicrousDB {
	/**
	 * Whether to check TCP responsiveness.
	 *
	 * @var bool
	 */
	public $check_tcp_responsiveness = false;

	/**
	 * Whether to use mysql_pconnect instead of mysql_connect
	 *
	 * @var bool
	 */
	public $persistent = true;

	/**
	 * Track total time waiting for database responses;
	 *
	 * @var integer
	 */
	public $time_spent = 0;

	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * @see wpdb::query()
	 *
	 * @param string $query Database query.
	 * @return int|false Number of rows affected/selected or false on error.
	 */
	public function query( $query ) {
		$start = microtime( true );
		$has_qm = class_exists( '\\QM_Backtrace' );

		if ( $has_qm && $this->show_errors ) {
			$this->hide_errors();
		}

		$result = parent::query( $query );

		$end = microtime( true );
		if ( function_exists( 'HM\\Platform\\XRay\\trace_wpdb_query' ) ) {
			$host = $this->current_host ?: $this->last_connection['host'];
			// Host gets the port number applied, which we don't want to add.
			$host = strtok( $host, ':' );
			XRay\trace_wpdb_query( $query, $start, $end, $result === false ? $this->last_error : null, $host );
		}
		$this->time_spent += $end - $start;

		if ( ! $has_qm || ! SAVEQUERIES ) {
			return $result;
		}

		$i = count( $this->queries ) - 1;
		$this->queries[ $i ]['trace'] = new QM_Backtrace( [
			'ignore_frames' => 1,
		] );

		if ( ! isset( $this->queries[ $i ][3] ) ) {
			$this->queries[ $i ][3] = $this->time_start;
		}

		if ( $this->last_error ) {
			$code = 'qmdb';
			if ( $this->use_mysqli ) {
				if ( $this->dbh instanceof mysqli ) {
					// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_errno
					$code = mysqli_errno( $this->dbh );
				}
			} else {
				if ( is_resource( $this->dbh ) ) {
					// Please do not report this code as a PHP 7 incompatibility. Observe the surrounding logic.
					// phpcs:ignore
					$code = mysql_errno( $this->dbh );
				}
			}
			$this->queries[ $i ]['result'] = new WP_Error( $code, $this->last_error );
		} else {
			$this->queries[ $i ]['result'] = $result;
		}

		return $result;
	}

	/**
	 * Determines the best charset and collation to use given a charset and collation.
	 *
	 * For example, when able, utf8mb4 should be used instead of utf8.
	 *
	 * @param string $charset The character set to check.
	 * @param string $collate The collation to check.
	 * @return array The most appropriate character set and collation to use.
	 */
	public function determine_charset( $charset, $collate ) {
		$charset_collate = parent::determine_charset( $charset, $collate );
		$charset = $charset_collate['charset'];
		$collate = $charset_collate['collate'];

		if ( 'utf8mb4' === $charset ) {
			// _general_ is outdated, so we can upgrade it to _unicode_, instead.
			if ( ! $collate || 'utf8_general_ci' === $collate ) {
				$collate = 'utf8mb4_unicode_ci';
			} else {
				$collate = str_replace( 'utf8_', 'utf8mb4_', $collate );
			}
		}
		return compact( 'charset', 'collate' );
	}

	/**
	 * Add the connection parameters for a database.
	 *
	 * This overrides the parent method in LudicrousDB, as we want to
	 * populte the dbname, dbuser etc instance vairables for compatibilty with
	 * wpdb, and code that relies on those variables being set.
	 *
	 * @param array $db The database connection details.
	 */
	public function add_database( array $db = [] ) {
		if ( ! empty( $db['write'] ) ) {
			$this->dbname     = $db['name'];
			$this->dbpassword = $db['password'];
			$this->dbuser     = $db['user'];
			$this->dbhost     = $db['host'];
		}
		parent::add_database( $db );
	}

	/**
	 * Override the wpdb bail handler.
	 *
	 * We always want to bail and exit the script execution, not rely on $this->show_errors.
	 * This function can not use any WordPress functions that read from the database, as that
	 * will risk a recursion call.
	 *
	 * @param string $message The Error message.
	 * @param string $error_code Optional. A Computer readable string to identify the error.
	 */
	public function bail( $message, $error_code = '500' ) {
		header( 'Content-Type: text/html; charset=utf-8' );
		status_header( 500 );
		nocache_headers();
		?>
		<h1>Database Connection Error</h1>
		<p><pre><?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></pre></p>
		<?php if ( $error_code ) : ?>
			<p>Code: <pre><?php echo $error_code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></pre></p>
		<?php endif ?>
		<?php
		exit;
	}
}
