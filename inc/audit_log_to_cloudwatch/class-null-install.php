<?php
/**
 * Altis Cloud Audit Log to CloudWatch: Stream install stub.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Audit_Log_To_CloudWatch;

use WP_Stream\Install;

/**
 * Install stub returned by the CloudWatch driver's setup_storage().
 *
 * Stream 4.5+ requires setup_storage() to return an Install instance on admin
 * and WP-CLI requests. The CloudWatch driver keeps no records in the database,
 * so this stub skips the table creation and schema upgrades that Install runs.
 */
class Null_Install extends Install {
	/**
	 * Class constructor.
	 *
	 * Does not call the parent: no database version lookup, no schema check
	 * and no activation hook.
	 *
	 * @param \WP_Stream\Plugin $plugin Instance of the plugin.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Skip the database version check.
	 *
	 * @return void
	 */
	public function check() {
		// No op: there are no Stream tables to create or upgrade.
	}

	/**
	 * Skip creating the Stream tables.
	 *
	 * Stream's reset action calls this directly.
	 *
	 * @param string $current_version Current database version.
	 * @return string
	 */
	public function install( $current_version ) {
		return (string) $current_version;
	}
}
