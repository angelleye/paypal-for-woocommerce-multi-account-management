<?php

/**
 * The plugin bootstrap file
 *
 *
 * @link              http://www.angelleye.com/
 * @since             1.0.0
 * @package           Paypal_For_Woocommerce_Multi_Account_Management
 *
 * @wordpress-plugin
 * Plugin Name:       PayPal for WooCommerce Multi-Account Management
 * Plugin URI:        http://www.angelleye.com/product/paypal-for-woocommerce-multi-account-management/
 * Description:       Send WooCommerce order payments to different PayPal accounts based on rules provided.
 * Version:           1.0.2
 * Author:            Angell EYE
 * Author URI:        http://www.angelleye.com/
 * License:           GPLv3 or later
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       paypal-for-woocommerce-multi-account-management
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('PFWMA_VERSION', '1.0.3');

/**
 * define plugin basename
 */
if (!defined('PFWMA_PLUGIN_BASENAME')) {
    define('PFWMA_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

 if (!defined('AEU_ZIP_URL')) {
    define('AEU_ZIP_URL', 'http://downloads.angelleye.com/ae-updater/angelleye-updater/angelleye-updater.zip');
}

/**
 * Required functions
 */
if (!function_exists('angelleye_queue_update')) {
    require_once( 'includes/angelleye-functions.php' );
}

/**
 * Plugin updates
 */
angelleye_queue_update(plugin_basename(__FILE__), '101', 'paypal-for-woocommerce-multi-account-management');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-paypal-for-woocommerce-multi-account-management-activator.php
 */
function activate_paypal_for_woocommerce_multi_account_management() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-paypal-for-woocommerce-multi-account-management-activator.php';
    Paypal_For_Woocommerce_Multi_Account_Management_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-paypal-for-woocommerce-multi-account-management-deactivator.php
 */
function deactivate_paypal_for_woocommerce_multi_account_management() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-paypal-for-woocommerce-multi-account-management-deactivator.php';
    Paypal_For_Woocommerce_Multi_Account_Management_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_paypal_for_woocommerce_multi_account_management');
register_deactivation_hook(__FILE__, 'deactivate_paypal_for_woocommerce_multi_account_management');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-paypal-for-woocommerce-multi-account-management.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_paypal_for_woocommerce_multi_account_management() {

    $plugin = new Paypal_For_Woocommerce_Multi_Account_Management();
    $plugin->run();
}

run_paypal_for_woocommerce_multi_account_management();
