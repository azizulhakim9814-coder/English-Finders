<?php
/**
 * Plugin Name:       English Finders Account
 * Plugin URI:        https://englishfinders.com
 * Description:       Registration, login, profile, privacy and billing UI for English Finders. Replaces the broken /my-account/ page. Streak, XP, and the full My Account UI land in later phases.
 * Version:           0.20.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Requires Plugins:  english-finders-core
 * Author:            English Finders
 * Author URI:        https://englishfinders.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       english-finders-account
 * Domain Path:       /languages
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Version constants.
 *
 * EFA_VERSION tracks the plugin release. No EFA_DB_VERSION yet -- this
 * phase introduces no database tables (see a1-account-foundation.md:
 * profile fields live in usermeta, and a real Schema/Migrator pair is
 * added only when Phase A2 gives it something real to migrate). Mirrors
 * the EFC_VERSION / EFC_DB_VERSION split in English Finders Core, once
 * there is a second version to split.
 */
define( 'EFA_VERSION', '0.20.0' );
define( 'EFA_FILE', __FILE__ );
define( 'EFA_PATH', plugin_dir_path( __FILE__ ) );
define( 'EFA_URL', plugin_dir_url( __FILE__ ) );
define( 'EFA_BASENAME', plugin_basename( __FILE__ ) );

require_once EFA_PATH . 'src/Core/Autoloader.php';

EnglishFindersAccount\Core\Autoloader::register();

register_activation_hook( __FILE__, array( EnglishFindersAccount\Core\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( EnglishFindersAccount\Core\Deactivator::class, 'deactivate' ) );

/*
 * Default priority (10), same as Word Games Pro and for the same reason:
 * Core boots at priority 5, and Phase A5's Membership code needs Core
 * already booted (EntitlementRepository, PaddlePortalClient, TransactionLog)
 * -- unlike Phase A1, which had no dependency on load order.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		EnglishFindersAccount\Plugin::instance()->boot();
	}
);
