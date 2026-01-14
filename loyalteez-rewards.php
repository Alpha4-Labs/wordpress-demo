<?php
/**
 * Plugin Name: Loyalteez Rewards
 * Plugin URI:  https://loyalteez.app
 * Description: Integrate Loyalteez rewards into your WordPress site. Reward comments, signups, and more.
 * Version:     1.0.0
 * Author:      Loyalteez
 * Author URI:  https://loyalteez.app
 * License:     GPL-2.0+
 * Text Domain: loyalteez-rewards
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('LOYALTEEZ_VERSION', '1.0.0');
define('LOYALTEEZ_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include classes
require_once LOYALTEEZ_PLUGIN_DIR . 'includes/class-admin.php';
require_once LOYALTEEZ_PLUGIN_DIR . 'includes/class-hooks.php';
require_once LOYALTEEZ_PLUGIN_DIR . 'includes/class-api.php';
require_once LOYALTEEZ_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once LOYALTEEZ_PLUGIN_DIR . 'src/shortcodes/dashboard.php';
require_once LOYALTEEZ_PLUGIN_DIR . 'src/shortcodes/balance.php';
require_once LOYALTEEZ_PLUGIN_DIR . 'src/shortcodes/streak.php';
require_once LOYALTEEZ_PLUGIN_DIR . 'src/shortcodes/leaderboard.php';
require_once LOYALTEEZ_PLUGIN_DIR . 'src/shortcodes/perks.php';
require_once LOYALTEEZ_PLUGIN_DIR . 'src/rest-api/leaderboard.php';

/**
 * Begins execution of the plugin.
 */
function run_loyalteez_rewards() {
    $plugin_admin = new Loyalteez_Admin();
    $plugin_admin->init();

    $plugin_hooks = new Loyalteez_Hooks();
    $plugin_hooks->init();

    $rest_api = new Loyalteez_REST_API();
}

run_loyalteez_rewards();

