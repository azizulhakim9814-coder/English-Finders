<?php
/**
 * Plugin Name:       English Finders Core
 * Plugin URI:        https://englishfinders.com
 * Description:       Shared data layer and service foundation for the English Finders plugin suite. Owns the dictionary tables and provides word data, provider connections, and entitlement services to Word Games Pro and Learning Toolkit Pro.
 * Version:           1.15.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            English Finders
 * Author URI:        https://englishfinders.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       english-finders-core
 * Domain Path:       /languages
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Version constants.
 *
 * EFC_VERSION tracks the plugin release. EFC_DB_VERSION tracks the schema and
 * is deliberately separate: the plugin can ship several releases without any
 * schema change, and conflating the two forces needless migration checks.
 * This mirrors the WUC_VERSION / WUC_DB_VERSION split already used by
 * Word Games Pro.
 */
define( 'EFC_VERSION', '1.15.0' );
define( 'EFC_DB_VERSION', '1.15.0' );
define( 'EFC_FILE', __FILE__ );
define( 'EFC_PATH', plugin_dir_path( __FILE__ ) );
define( 'EFC_URL', plugin_dir_url( __FILE__ ) );
define( 'EFC_BASENAME', plugin_basename( __FILE__ ) );

require_once EFC_PATH . 'src/Core/Autoloader.php';

EnglishFindersCore\Core\Autoloader::register();

register_activation_hook( __FILE__, array( EnglishFindersCore\Core\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( EnglishFindersCore\Core\Deactivator::class, 'deactivate' ) );

/*
 * Boot at priority 5.
 *
 * Word Games Pro boots on the default plugins_loaded priority (10). Core must
 * be fully initialised before any consumer plugin asks it for services, so it
 * deliberately boots earlier rather than relying on plugin load order, which
 * is alphabetical by directory and therefore not a guarantee.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		EnglishFindersCore\Plugin::instance()->boot();
	},
	5
);
