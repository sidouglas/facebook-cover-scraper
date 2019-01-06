<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              simondouglas.com
 * @since             1.0.0
 * @package           Facebook_Public_Cover
 *
 * @wordpress-plugin
 * Plugin Name:       Facebook Public Cover
 * Plugin URI:        facebook-public-cover
 * Description:       A simple means to simply get the public cover image without all the pain of FB APIs.
 * Version:           1.0.0
 * Author:            Simon Douglas
 * Author URI:        si@simondouglas.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       facebook-public-cover
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'FACEBOOK_PUBLIC_COVER_PLUGIN_NAME_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/facebook-public-cover-activator.php
 */
function activate_facebook_public_cover() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/facebook-public-cover-activator.php';
	Facebook_Public_Cover_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/facebook-public-cover-deactivator.php
 */
function deactivate_facebook_public_cover() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/facebook-public-cover-deactivator.php';
	Facebook_Public_Cover_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_facebook_public_cover' );
register_deactivation_hook( __FILE__, 'deactivate_facebook_public_cover' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/facebook-public-cover.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_facebook_public_cover() {

	$plugin = new Facebook_Public_Cover();
	$plugin->run();

}
run_facebook_public_cover();
