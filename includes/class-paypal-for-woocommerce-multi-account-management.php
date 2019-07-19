<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Paypal_For_Woocommerce_Multi_Account_Management
 * @subpackage Paypal_For_Woocommerce_Multi_Account_Management/includes
 * @author     Angell EYE <service@angelleye.com>
 */
class Paypal_For_Woocommerce_Multi_Account_Management {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Paypal_For_Woocommerce_Multi_Account_Management_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('PFWMA_VERSION')) {
            $this->version = PFWMA_VERSION;
        } else {
            $this->version = '1.2.0';
        }
        $this->plugin_name = 'paypal-for-woocommerce-multi-account-management';
        $this->load_dependencies();
        if (function_exists('WC') && class_exists('AngellEYE_Gateway_Paypal')) {
            
            $this->set_locale();
            $this->define_admin_hooks();
        }
        $prefix = is_network_admin() ? 'network_admin_' : '';
        add_filter("{$prefix}plugin_action_links_" . PFWMA_PLUGIN_BASENAME, array($this, 'paypal_for_woocommerce_multi_account_management_action_links'), 10, 4);
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Paypal_For_Woocommerce_Multi_Account_Management_Loader. Orchestrates the hooks of the plugin.
     * - Paypal_For_Woocommerce_Multi_Account_Management_i18n. Defines internationalization functionality.
     * - Paypal_For_Woocommerce_Multi_Account_Management_Admin. Defines all hooks for the admin area.
     * - Paypal_For_Woocommerce_Multi_Account_Management_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-paypal-for-woocommerce-multi-account-management-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-paypal-for-woocommerce-multi-account-management-i18n.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-paypal-for-woocommerce-multi-account-management-wp-list-table.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-paypal-for-woocommerce-multi-account-management-list-data.php';



        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-paypal-for-woocommerce-multi-account-management-admin.php';

        $this->loader = new Paypal_For_Woocommerce_Multi_Account_Management_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Paypal_For_Woocommerce_Multi_Account_Management_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Paypal_For_Woocommerce_Multi_Account_Management_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Paypal_For_Woocommerce_Multi_Account_Management_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('angelleye_paypal_for_woocommerce_multi_account_api_paypal_express', $plugin_admin, 'angelleye_paypal_for_woocommerce_multi_account_api_paypal_express', 11, 3);
        $this->loader->add_action('woocommerce_checkout_update_order_meta', $plugin_admin, 'angelleye_woocommerce_checkout_update_order_meta', 11, 2);
        $this->loader->add_action('before_save_payment_token', $plugin_admin, 'angelleye_woocommerce_payment_successful_result', 11, 1);
        $this->loader->add_action('angelleye_paypal_for_woocommerce_general_settings_tab', $plugin_admin, 'angelleye_paypal_for_woocommerce_general_settings_tab', 10);
        $this->loader->add_action('angelleye_paypal_for_woocommerce_general_settings_tab_content', $plugin_admin, 'angelleye_paypal_for_woocommerce_general_settings_tab_content', 10);
        $this->loader->add_action('woocommerce_cart_item_removed', $plugin_admin, 'update_session_data', 999);
        $this->loader->add_action('woocommerce_after_cart_item_quantity_update', $plugin_admin, 'update_session_data', 999);
        $this->loader->add_action('woocommerce_add_to_cart', $plugin_admin, 'update_session_data', 999);
        $this->loader->add_action('woocommerce_cart_emptied', $plugin_admin, 'remove_session_data', 9999);
        $this->loader->add_action('wp_ajax_angelleye_get_product_tag_by_product_cat', $plugin_admin, 'angelleye_get_product_tag_by_product_cat', 10);
        $this->loader->add_action('wp_ajax_angelleye_get_product_by_product_tags', $plugin_admin, 'angelleye_get_product_by_product_tags', 10);
        $this->loader->add_action('angelleye_paypal_for_woocommerce_multi_account_api_paypal_payflow', $plugin_admin, 'angelleye_paypal_for_woocommerce_multi_account_api_paypal_payflow', 11, 3);
        $this->loader->add_filter('angelleye_paypal_pro_payflow_amex_ca_usd', $plugin_admin, 'angelleye_paypal_pro_payflow_amex_ca_usd', 10, 2);
        $this->loader->add_action('wp_ajax_angelleye_paypal_for_woocommerce_multi_account_adismiss_notice', $plugin_admin, 'angelleye_paypal_for_woocommerce_multi_account_adismiss_notice', 10);
        $this->loader->add_action('admin_notices', $plugin_admin, 'angelleye_paypal_for_woocommerce_multi_account_display_push_notification', 10);
        $this->loader->add_action('angelleye_set_multi_account', $plugin_admin, 'angelleye_set_multi_account', 10, 2);
        $this->loader->add_filter('set-screen-option', $plugin_admin, 'angelleye_set_screen_option', 10, 3);
        $this->loader->add_action('load-settings_page_paypal-for-woocommerce', $plugin_admin, 'angelleye_add_screen_option', 10);
        
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Paypal_For_Woocommerce_Multi_Account_Management_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Return the plugin action links.  This will only be called if the plugin
     * is active.
     *
     * @since 1.0.0
     * @param array $actions associative array of action names to anchor tags
     * @return array associative array of plugin action links
     */
    public function paypal_for_woocommerce_multi_account_management_action_links($actions, $plugin_file, $plugin_data, $context) {
        $custom_actions = array(
            'configure' => sprintf('<a href="%s">%s</a>', admin_url('options-general.php?page=paypal-for-woocommerce&tab=general_settings&gateway=paypal_for_wooCommerce_for_multi_account_management'), __('Configure', 'paypal-for-woocommerce-multi-account-management')),
            'docs' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://www.angelleye.com/category/docs/paypal-for-woocommerce-multi-account-management-documentation/?utm_source=paypal-for-woocommerce-multi-account&utm_medium=docs_link&utm_campaign=plugin', __('Docs', 'paypal-for-woocommerce-multi-account-management')),
            'support' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://www.angelleye.com/support/?utm_source=paypal-for-woocommerce-multi-account&utm_medium=support_link&utm_campaign=plugin', __('Support', 'paypal-for-woocommerce-multi-account-management')),
            'review' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://www.angelleye.com/product/paypal-for-woocommerce-multi-account-management?utm_source=paypal-for-woocommerce-multi-account&utm_medium=review_link&utm_campaign=plugin', __('Write a Review', 'paypal-for-woocommerce-multi-account-management')),
        );
        return array_merge($custom_actions, $actions);
    }

}
