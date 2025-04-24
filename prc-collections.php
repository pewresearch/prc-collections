<?php
/**
 * PRC Collections
 *
 * @package PRC\Platform\Collections
 */

namespace PRC\Platform\Collections;

/**
 * PRC Collections
 *
 * @package           PRC_Collections
 * @author            Seth Rubenstein
 * @copyright         2024 Pew Research Center
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       PRC Collections
 * Plugin URI:        https://github.com/pewresearch/prc-collections
 * Description:       Collections is a hybrid post and taxonomy type that allows for curation of Pew Research Center content that share common themes, topics, research initiatives, special projects or grants, purpose built for PRC Platform.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.2
 * Author:            Seth Rubenstein
 * Author URI:        https://pewresearch.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       prc-collections
 * Requires Plugins:  prc-platform-core
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PRC_COLLECTIONS_FILE', __FILE__ );
define( 'PRC_COLLECTIONS_DIR', __DIR__ );
define( 'PRC_COLLECTIONS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-prc-collections-activator.php
 */
function activate_prc_collections() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-prc-collections-activator.php';
	PRC_Collections_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-prc-collections-deactivator.php
 */
function deactivate_prc_collections() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-prc-collections-deactivator.php';
	PRC_Collections_Deactivator::deactivate();
}

register_activation_hook( __FILE__, '\PRC\Platform\Collections\activate_prc_collections' );
register_deactivation_hook( __FILE__, '\PRC\Platform\Collections\deactivate_prc_collections' );

/**
 * Helper utilities
 */
require plugin_dir_path( __FILE__ ) . 'includes/utils.php';

/**
 * The core plugin class that is used to define the hooks that initialize the various components.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-plugin.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_prc_collections() {
	$plugin = new Plugin();
	$plugin->run();
}
run_prc_collections();
