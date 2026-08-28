<?php
/**
 * Altis Support temporary login.
 *
 * Provides a dedicated, temporarily-provisioned user account ("altis-support")
 * for the Altis Support team to access a site when needed.
 *
 * Support enables access by setting the `_ALTIS_SUPPORT_TOKEN` platform secret,
 * which is used as the signing key for time-limited login tokens. A token is
 * presented directly to wp-login.php:
 *
 *     wp-login.php?action=altis-support-login&token=<random>:<expiration>:<hmac>
 *
 * where `hmac = HMAC-SHA256( "<random>:<expiration>", _ALTIS_SUPPORT_TOKEN )`.
 *
 * On a valid token the account is provisioned in the database if necessary and
 * logged in. Administrator role and super admin status are granted dynamically
 * via filters rather than being persisted to the database, and are only granted
 * while the account is active. When the token's expiration is reached the
 * account is disabled via the disable-accounts mechanism.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Support_Login;

use Altis;
use WP_User;

/**
 * Username of the support account.
 */
const USERNAME = 'altis-support';

/**
 * Email address of the support account.
 */
const EMAIL = 'support@altis-dxp.com';

/**
 * wp-login.php action used to trigger a support login.
 */
const LOGIN_ACTION = 'altis-support-login';

/**
 * Name of the platform secret used as the token signing key.
 */
const SECRET_NAME = '_ALTIS_SUPPORT_TOKEN';

/**
 * User meta key storing the current access window expiration (Unix timestamp).
 */
const EXPIRATION_META_KEY = '_altis_support_expiration';

/**
 * Cron hook used to disable the account once its access window expires.
 */
const DISABLE_CRON_HOOK = 'altis.cloud.support_login.disable_expired';

/**
 * Set up hooks.
 *
 * @return void
 */
function bootstrap() : void {
	// Handle the dedicated support login action on wp-login.php.
	add_action( 'login_form_' . LOGIN_ACTION, __NAMESPACE__ . '\\handle_login' );

	// Grant the administrator role in-memory; never persisted to the database.
	add_filter( 'get_user_metadata', __NAMESPACE__ . '\\grant_administrator_role', 10, 4 );

	// Grant super admin status in-memory; never persisted to the database.
	add_filter( 'site_option_site_admins', __NAMESPACE__ . '\\grant_super_admin' );

	// The signed token is itself a strong authentication factor, so exempt the
	// support account from forced two-factor authentication.
	add_filter( 'two_factor_universally_forced', __NAMESPACE__ . '\\bypass_forced_2fa_universal', 99 );
	add_filter( 'two_factor_forced_user_roles', __NAMESPACE__ . '\\bypass_forced_2fa_roles', 99 );

	// Disable the account once its access window expires.
	add_action( DISABLE_CRON_HOOK, __NAMESPACE__ . '\\disable_expired_account' );
}

/**
 * Handle a request to the support login action.
 *
 * Validates the presented token and, if valid, provisions (if necessary) and
 * logs in the support account before redirecting to the admin.
 *
 * @return void
 */
function handle_login() : void {
	$token = isset( $_GET['token'] ) && is_string( $_GET['token'] )
		? wp_unslash( $_GET['token'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The token is validated cryptographically below.
		: '';

	$expiration = validate_token( $token );
	if ( $expiration === null ) {
		/**
		 * Fires when a support login attempt fails validation.
		 */
		do_action( 'altis.cloud.support_login.failed' );

		wp_die(
			esc_html__( 'Invalid or expired support login token.', 'altis' ),
			esc_html__( 'Support Login', 'altis' ),
			[ 'response' => 403 ]
		);
	}

	$user = get_or_create_support_user();
	if ( ! $user ) {
		wp_die(
			esc_html__( 'Unable to provision the support account.', 'altis' ),
			esc_html__( 'Support Login', 'altis' ),
			[ 'response' => 500 ]
		);
	}

	activate_account( $user, $expiration );

	// Log the account in directly; the token is the authentication factor.
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, false, is_ssl() );

	// Fire the standard login action so the audit log and other integrations
	// record the support login.
	do_action( 'wp_login', $user->user_login, $user );

	/**
	 * Fires after the support account has successfully logged in.
	 *
	 * @param WP_User $user The support account.
	 * @param int $expiration Access window expiration as a Unix timestamp.
	 */
	do_action( 'altis.cloud.support_login.success', $user, $expiration );

	wp_safe_redirect( admin_url() );
	exit;
}

/**
 * Validate a support login token.
 *
 * The token has the format `<random>:<expiration>:<hmac>`, where the HMAC is
 * computed over `<random>:<expiration>` using the platform secret as the key.
 *
 * @param string $token Token presented in the request.
 * @return int|null The expiration timestamp if the token is valid, null otherwise.
 */
function validate_token( string $token ) : ?int {
	if ( ! function_exists( 'Altis\\get_variable' ) ) {
		return null;
	}

	$secret = Altis\get_variable( SECRET_NAME );
	if ( empty( $secret ) ) {
		// No secret configured: the feature is toggled off.
		return null;
	}

	if ( empty( $token ) ) {
		return null;
	}

	$parts = explode( ':', $token );
	if ( count( $parts ) !== 3 ) {
		return null;
	}

	[ $random, $expiration_raw, $provided_hmac ] = $parts;

	// The expiration must be a positive integer timestamp. Comparing the raw
	// string avoids canonicalisation issues (e.g. leading zeros) when
	// recomputing the signature.
	if ( $random === '' || ! ctype_digit( $expiration_raw ) ) {
		return null;
	}

	// Verify the signature using a constant-time comparison.
	$expected_hmac = hash_hmac( 'sha256', $random . ':' . $expiration_raw, $secret );
	if ( ! hash_equals( $expected_hmac, $provided_hmac ) ) {
		return null;
	}

	// Reject expired tokens.
	$expiration = (int) $expiration_raw;
	if ( $expiration < time() ) {
		return null;
	}

	return $expiration;
}

/**
 * Get the support account, provisioning it in the database if necessary.
 *
 * @return WP_User|null The support account, or null if it could not be created.
 */
function get_or_create_support_user() : ?WP_User {
	$user_id = get_support_user_id();
	if ( $user_id ) {
		return get_user_by( 'id', $user_id );
	}

	// Provision the account with no role. Administrator role and super admin
	// status are granted dynamically via filters rather than persisted here.
	$user_id = wp_insert_user( [
		'user_login' => USERNAME,
		'user_email' => EMAIL,
		'user_pass' => wp_generate_password( 64, true, true ),
		'display_name' => 'Altis Support',
		'role' => '',
	] );

	if ( is_wp_error( $user_id ) ) {
		trigger_error(
			// phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped
			'Altis Support: unable to provision support account: ' . $user_id->get_error_message(),
			E_USER_WARNING
		);
		return null;
	}

	// Ensure subsequent lookups within this request find the new account.
	get_support_user_id( true );

	return get_user_by( 'id', $user_id );
}

/**
 * Activate the support account for a given access window.
 *
 * Stores the expiration, re-enables the account if it was previously disabled,
 * and schedules the disable event for when the window expires.
 *
 * @param WP_User $user The support account.
 * @param int $expiration Access window expiration as a Unix timestamp.
 * @return void
 */
function activate_account( WP_User $user, int $expiration ) : void {
	update_user_meta( $user->ID, EXPIRATION_META_KEY, $expiration );

	// If the account was previously disabled, re-enable it for this window.
	if ( function_exists( 'DisableAccounts\\is_disabled' ) && \DisableAccounts\is_disabled( $user ) ) {
		\DisableAccounts\reenable_user( $user );
	}

	// (Re)schedule the disable event for the expiration time.
	wp_clear_scheduled_hook( DISABLE_CRON_HOOK );
	wp_schedule_single_event( $expiration, DISABLE_CRON_HOOK );
}

/**
 * Disable the support account once its access window has expired.
 *
 * Runs on the scheduled cron event and uses the disable-accounts mechanism to
 * revoke access (reset password, destroy sessions and wipe capabilities).
 *
 * @return void
 */
function disable_expired_account() : void {
	$user_id = get_support_user_id();
	if ( ! $user_id ) {
		return;
	}

	$user = get_user_by( 'id', $user_id );
	if ( ! $user ) {
		return;
	}

	// If the window was extended since this event was scheduled, reschedule
	// rather than disabling early.
	$expiration = (int) get_user_meta( $user_id, EXPIRATION_META_KEY, true );
	if ( $expiration > time() ) {
		wp_schedule_single_event( $expiration, DISABLE_CRON_HOOK );
		return;
	}

	if ( function_exists( 'DisableAccounts\\disable_user' ) ) {
		\DisableAccounts\disable_user( $user );
	}

	// Clear the expiration so the account is no longer considered active.
	delete_user_meta( $user_id, EXPIRATION_META_KEY );
}

/**
 * Get the support account's user ID.
 *
 * Resolved via a direct query (rather than get_user_by()) to avoid recursion
 * from the get_user_metadata filter, and cached for the request.
 *
 * @param bool $refresh Whether to bypass and reset the cached value.
 * @return int|null The user ID, or null if the account does not exist.
 */
function get_support_user_id( bool $refresh = false ) : ?int {
	static $cache = false;

	if ( $refresh ) {
		$cache = false;
	}

	if ( $cache !== false ) {
		return $cache;
	}

	global $wpdb;
	$id = $wpdb->get_var( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->users} WHERE user_login = %s LIMIT 1",
		USERNAME
	) );

	$cache = $id ? (int) $id : null;

	return $cache;
}

/**
 * Whether a given user is the support account.
 *
 * @param int $user_id User ID to check.
 * @return bool
 */
function is_support_user( int $user_id ) : bool {
	$support_id = get_support_user_id();
	return $support_id !== null && $user_id === $support_id;
}

/**
 * Whether the support account is currently active.
 *
 * The account is active while it has an unexpired access window and has not
 * been disabled via the disable-accounts mechanism. Meta is read directly to
 * avoid recursion through the capability filters.
 *
 * @param int|null $user_id Support account user ID. Resolved automatically if omitted.
 * @return bool
 */
function is_active( ?int $user_id = null ) : bool {
	$user_id = $user_id ?? get_support_user_id();
	if ( ! $user_id ) {
		return false;
	}

	// Not active if disabled via the disable-accounts mechanism.
	$disabled_key = defined( 'DisableAccounts\\DISABLED_META_KEY' )
		? \DisableAccounts\DISABLED_META_KEY
		: '_hm_disableaccounts_disabled';
	if ( get_user_meta( $user_id, $disabled_key, true ) === 'yes' ) {
		return false;
	}

	// Not active without an unexpired access window.
	$expiration = (int) get_user_meta( $user_id, EXPIRATION_META_KEY, true );
	if ( $expiration <= 0 || $expiration < time() ) {
		return false;
	}

	return true;
}

/**
 * Whether the current user is the active support account.
 *
 * @return bool
 */
function is_current_user_support() : bool {
	$current = get_current_user_id();
	return $current > 0 && is_support_user( $current ) && is_active( $current );
}

/**
 * Grant the administrator role to the support account in-memory.
 *
 * Short-circuits the capabilities meta read so the role is never persisted to
 * the database, and only while the account is active.
 *
 * @param mixed $value The value get_metadata() should return; null by default.
 * @param int $user_id ID of the object metadata is for.
 * @param string $meta_key Meta key being requested.
 * @param bool $single Whether a single value was requested.
 * @return mixed Administrator capabilities for the support account, else $value.
 */
function grant_administrator_role( $value, $user_id, $meta_key, $single ) {
	// Respect any value already provided by another filter.
	if ( $value !== null ) {
		return $value;
	}

	// Only act on the (blog-prefixed) capabilities meta key.
	if ( substr( $meta_key, -12 ) !== 'capabilities' ) {
		return $value;
	}

	if ( ! is_support_user( (int) $user_id ) || ! is_active( (int) $user_id ) ) {
		return $value;
	}

	// Wrapped in an array as get_metadata() returns $check[0] for single reads.
	return [ [ 'administrator' => true ] ];
}

/**
 * Grant super admin status to the support account in-memory.
 *
 * Appends the account to the list returned for the `site_admins` network
 * option, but only while the account is active, so disabling the account also
 * revokes super admin status.
 *
 * @param mixed $site_admins List of super admin usernames.
 * @return mixed Updated list.
 */
function grant_super_admin( $site_admins ) {
	if ( ! is_array( $site_admins ) ) {
		return $site_admins;
	}

	if ( is_active() && ! in_array( USERNAME, $site_admins, true ) ) {
		$site_admins[] = USERNAME;
	}

	return $site_admins;
}

/**
 * Exempt the active support account from universally-forced two-factor auth.
 *
 * @param bool $forced Whether two-factor is universally forced.
 * @return bool
 */
function bypass_forced_2fa_universal( $forced ) {
	if ( is_current_user_support() ) {
		return false;
	}

	return $forced;
}

/**
 * Exempt the active support account from role-based forced two-factor auth.
 *
 * @param mixed $roles Roles for which two-factor is forced.
 * @return mixed
 */
function bypass_forced_2fa_roles( $roles ) {
	if ( is_current_user_support() ) {
		return [];
	}

	return $roles;
}
