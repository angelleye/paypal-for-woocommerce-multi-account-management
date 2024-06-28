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
 * Plugin URI:        https://www.angelleye.com/product/paypal-woocommerce-multi-account-management/
 * Description:       Send WooCommerce order payments to different PayPal accounts based on rules provided.
 * Version:           4.0.2
 * Author:            Angell EYE
 * Author URI:        http://www.angelleye.com/
 * License:           GPLv3 or later
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       paypal-for-woocommerce-multi-account-management
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Tested up to: 6.5.5
 * Requires Plugins: woocommerce, paypal-for-woocommerce
 * WC requires at least: 3.0.0
 * WC tested up to: 9.0.2
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('PFWMA_VERSION', '4.0.2');

/**
 * define plugin basename
 */
if (!defined('PFWMA_PLUGIN_BASENAME')) {
    define('PFWMA_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

if (!defined('PFWMA_PLUGIN_DIR')) {
    define('PFWMA_PLUGIN_DIR', dirname(__FILE__));
}

if (!defined('AEU_ZIP_URL')) {
    define('AEU_ZIP_URL', 'https://updates.angelleye.com/ae-updater/angelleye-updater/angelleye-updater.zip');
}

if (!defined('PAYPAL_FOR_WOOCOMMERCE_PUSH_NOTIFICATION_WEB_URL')) {
    define('PAYPAL_FOR_WOOCOMMERCE_PUSH_NOTIFICATION_WEB_URL', 'https://www.angelleye.com/');
}

if (!defined('MULTI_ACCOUNT_REFUND_NOTICE')) {
    define('MULTI_ACCOUNT_REFUND_NOTICE', __('Partial refunds are not available for parallel payments orders.', 'paypal-for-woocommerce-multi-account-management'));
}


/**
 * Required functions
 */
if (!function_exists('angelleye_queue_update')) {
    require_once( 'includes/angelleye-functions.php' );
}

require_once( 'includes/angelleye-multi-account-function.php' );

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

add_action('plugins_loaded', 'load_angelleye_woo_paypal_for_woo_multi_account');
add_action('admin_notices', 'admin_notices_required_plugin');

function admin_notices_required_plugin() {
    if (function_exists('WC') && class_exists('AngellEYE_Gateway_Paypal')) {
        
    } else {
        if (!function_exists('WC')) {
            $slug = 'woocommerce';
            $install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $slug), 'install-plugin_' . $slug);
            $activate_url = 'plugins.php?action=activate&plugin=' . urlencode('woocommerce/woocommerce.php') . '&plugin_status=all&paged=1&s&_wpnonce=' . urlencode(wp_create_nonce('activate-plugin_woocommerce/woocommerce.php'));
            $message = '<a href="' . esc_url($install_url) . '">Install the WooCommerce plugin</a>.';
            $is_downloaded = false;
            $plugins = array_keys(get_plugins());
            foreach ($plugins as $plugin) {
                if (strpos($plugin, 'woocommerce.php') !== false) {
                    $is_downloaded = true;
                    $message = '<a href="' . esc_url(admin_url($activate_url)) . '"> Activate the WooCommerce plugin</a>.';
                }
            }
            echo "<div class='notice notice-error'><p>" . sprintf(__('%1$sPayPal for WooCommerce Multi-Account Management is not functional. %2$s The %3$sWooCommerce%4$s plugin must be active for PayPal for WooCommerce Multi-Account Management to work. Please %5$s', 'paypal-for-woocommerce-multi-account-management'), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', $message) . '</p></div>';
        }
        if (!class_exists('AngellEYE_Gateway_Paypal')) {
            $slug = 'paypal-for-woocommerce';
            $install_url = 'https://www.angelleye.com/product/woocommerce-paypal-plugin/?utm_source=pfwma&utm_medium=admin_notice';
            $activate_url = 'plugins.php?action=activate&plugin=' . urlencode('paypal-for-woocommerce/paypal-for-woocommerce.php') . '&plugin_status=all&paged=1&s&_wpnonce=' . urlencode(wp_create_nonce('activate-plugin_paypal-for-woocommerce/paypal-for-woocommerce.php'));
            $message = '<a href="' . esc_url($install_url) . '">Install the PayPal for WooCommerce plugin</a>.';
            $is_downloaded = false;
            $plugins = array_keys(get_plugins());
            foreach ($plugins as $plugin) {
                if (strpos($plugin, 'paypal-for-woocommerce.php') !== false) {
                    $is_downloaded = true;
                    $message = '<a href="' . esc_url(admin_url($activate_url)) . '"> Activate the PayPal for WooCommerce plugin</a>.';
                }
            }
            echo "<div class='notice notice-error'><p>" . sprintf(__('%1$s PayPal for WooCommerce Multi-Account Management is not functional. %2$s The %3$s PayPal for WooCommerce%4$s plugin must be active for PayPal for WooCommerce Multi-Account Management to work. Please %5$s', 'paypal-for-woocommerce-multi-account-management'), '<strong>', '</strong>', '<a href="https://www.angelleye.com/product/woocommerce-paypal-plugin/?utm_source=pfwma&utm_medium=admin_notice">', '</a>', $message) . '</p></div>';
        }
    }
}

function load_angelleye_woo_paypal_for_woo_multi_account() {
    try {
        if (function_exists('WC') && class_exists('AngellEYE_Gateway_Paypal')) {
            run_paypal_for_woocommerce_multi_account_management();
        }
        
    } catch (Exception $ex) {
        
    }
}

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
