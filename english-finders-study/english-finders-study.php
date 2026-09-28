<?php
/**
 * Plugin Name:       English Finders Study
 * Plugin URI:        https://englishfinders.com
 * Description:       Practice tools for English learners and teachers — vocabulary, grammar, reading, writing, spelling and pronunciation. Reads the shared dictionary from English Finders Core.
 * Version:           1.16.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Requires Plugins:  english-finders-core
 * Author:            English Finders
 * Author URI:        https://englishfinders.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       english-finders-study
 * Domain Path:       /languages
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Version constants.
 *
 * EFS_VERSION tracks the plugin release; EFS_DB_VERSION tracks the schema.
 * Kept separate because most releases change no tables, and conflating them
 * would run migration checks on every update for nothing. Mirrors the split in
 * Core and Word Games Pro.
 */
define( 'EFS_VERSION', '1.16.0' );
define( 'EFS_DB_VERSION', '1.0.0' );

/**
 * Minimum Core version this plugin needs.
 *
 * The `Requires Plugins` header enforces that Core is *active*, but WordPress
 * does not check versions — so the runtime guard below closes that gap.
 */
define( 'EFS_REQUIRES_CORE', '1.4.1' );

define( 'EFS_FILE', __FILE__ );
define( 'EFS_PATH', plugin_dir_path( __FILE__ ) );
define( 'EFS_URL', plugin_dir_url( __FILE__ ) );
define( 'EFS_BASENAME', plugin_basename( __FILE__ ) );

require_once EFS_PATH . 'src/Core/Autoloader.php';

EnglishFindersStudy\Core\Autoloader::register();

register_activation_hook( __FILE__, array( EnglishFindersStudy\Core\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( EnglishFindersStudy\Core\Deactivator::class, 'deactivate' ) );

/*
 * Boot at priority 15.
 *
 * Core boots at 5 and Word Games Pro at the default 10, so this runs after both
 * and can rely on Core's services being registered. Explicit rather than
 * relying on plugin load order, which is alphabetical by directory and
 * therefore not a guarantee.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		EnglishFindersStudy\Plugin::instance()->boot();
	},
	15
);
