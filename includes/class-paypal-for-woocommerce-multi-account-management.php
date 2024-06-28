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
    protected $plugin_screen_hook_suffix = null;
    public $plugin_admin;
    public $message;

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
            $this->version = '4.0.2';
        }
        $this->plugin_name = 'paypal-for-woocommerce-multi-account-management';
        $this->load_dependencies();
        if (function_exists('WC') && class_exists('AngellEYE_Gateway_Paypal')) {
            $this->set_locale();
            $this->define_admin_hooks();
        } elseif (function_exists('WC')) {
            $this->define_admin_hooks();
        }
        $prefix = is_network_admin() ? 'network_admin_' : '';
        add_filter("{$prefix}plugin_action_links_" . PFWMA_PLUGIN_BASENAME, array($this, 'paypal_for_woocommerce_multi_account_management_action_links'), 10, 4);
        add_filter('woocommerce_settings_tabs_array', array($this, 'angelleye_woocommerce_settings_tabs_array'), 50, 1);
        add_action('woocommerce_settings_tabs_multi_account_management', array($this, 'display_plugin_admin_page'));
        add_filter('angelleye_pfwma_is_api_set', array($this, 'angelleye_pfwma_is_api_set'), 10, 2);
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
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-pfwma-payments-history-list.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-paypal-for-woocommerce-multi-account-management-payment-hooks.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-paypal-for-woocommerce-multi-account-management-vendor.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-paypal-for-woocommerce-multi-account-management-payment-load-balancer.php';



        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-paypal-for-woocommerce-multi-account-management-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-paypal-for-woocommerce-multi-account-management-admin-paypal-payflow.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-paypal-for-woocommerce-multi-account-management-admin-express-checkout.php';
        //require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-paypal-for-woocommerce-multi-account-management-admin-paypal-standard.php';
        require_once plugin_dir_path(dirname(__FILE__)) . '/template/sidebar-process.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-paypal-for-woocommerce-multi-account-management-admin-ppcp.php';

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
        $this->plugin_admin = $plugin_admin = new Paypal_For_Woocommerce_Multi_Account_Management_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('woocommerce_checkout_update_order_meta', $plugin_admin, 'angelleye_woocommerce_checkout_update_order_meta', 11, 2);
        $this->loader->add_action('before_save_payment_token', $plugin_admin, 'angelleye_woocommerce_payment_successful_result', 11, 1);
        //$this->loader->add_action('angelleye_paypal_for_woocommerce_general_settings_tab', $plugin_admin, 'angelleye_paypal_for_woocommerce_general_settings_tab', 10);
        //$this->loader->add_action('angelleye_paypal_for_woocommerce_general_settings_tab_content', $plugin_admin, 'angelleye_paypal_for_woocommerce_general_settings_tab_content', 10);
        $this->loader->add_action('woocommerce_cart_item_removed', $plugin_admin, 'update_session_data', 999);
        $this->loader->add_action('woocommerce_after_cart_item_quantity_update', $plugin_admin, 'update_session_data', 999);
        $this->loader->add_action('woocommerce_add_to_cart', $plugin_admin, 'update_session_data', 999);
        $this->loader->add_action('woocommerce_cart_emptied', $plugin_admin, 'remove_session_data', 9999);
        $this->loader->add_action('wp_ajax_angelleye_pfwma_get_product_tags', $plugin_admin, 'angelleye_pfwma_get_product_tags', 10);
        $this->loader->add_action('wp_ajax_angelleye_pfwma_get_products', $plugin_admin, 'angelleye_pfwma_get_products', 10);
        $this->loader->add_action('wp_ajax_angelleye_paypal_for_woocommerce_multi_account_adismiss_notice', $plugin_admin, 'angelleye_paypal_for_woocommerce_multi_account_adismiss_notice', 10);
        $this->loader->add_action('admin_notices', $plugin_admin, 'angelleye_paypal_for_woocommerce_multi_account_display_push_notification', 10);
        $this->loader->add_action('angelleye_set_multi_account', $plugin_admin, 'angelleye_set_multi_account', 10, 2);
        $this->loader->add_filter('set-screen-option', $plugin_admin, 'angelleye_set_screen_option', 10, 3);
        $this->loader->add_action('load-settings_page_paypal-for-woocommerce', $plugin_admin, 'angelleye_add_screen_option', 10);
        $order_summary = new Paypal_For_Woocommerce_Multi_Account_Management_Payment_Hooks($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('add_meta_boxes', $order_summary, 'setupMetaBoxes', 11, 3);
        $express_checkout = new Paypal_For_Woocommerce_Multi_Account_Management_Admin_Express_Checkout($this->get_plugin_name(), $this->get_version());
        $paypal_payflow = new Paypal_For_Woocommerce_Multi_Account_Management_Admin_PayPal_Payflow($this->get_plugin_name(), $this->get_version());
        $angelleye_ppcp = new Paypal_For_Woocommerce_Multi_Account_Management_Admin_PPCP($this->get_plugin_name(), $this->get_version());
        $this->loader->add_filter('angelleye_ppcp_merchant_id', $angelleye_ppcp, 'angelleye_ppcp_get_merchant_id', 10, 1);
        $this->loader->add_filter('angelleye_ppcp_request_args', $angelleye_ppcp, 'angelleye_ppcp_request_multi_account', 10, 3);
        $this->loader->add_action('angelleye_ppcp_order_data', $angelleye_ppcp, 'own_angelleye_ppcp_order_data', 10, 2);
        $this->loader->add_filter('angelleye_is_ppcp_parallel_payment_not_used', $angelleye_ppcp, 'own_angelleye_is_ppcp_parallel_payment_not_used', 10, 2);
        $this->loader->add_filter('angelleye_is_ppcp_parallel_payment_handle', $angelleye_ppcp, 'own_angelleye_is_ppcp_parallel_payment_handle', 10, 3);
        $this->loader->add_action('woocommerce_order_item_add_action_buttons', $angelleye_ppcp, 'own_woocommerce_order_item_add_action_buttons', 10, 1);
        $this->loader->add_action('woocommerce_order_refunded', $angelleye_ppcp, 'own_woocommerce_order_fully_refunded', 10, 2);
        //$this->loader->add_filter('woocommerce_paypal_args', $paypal, 'angelleye_woocommerce_paypal_args', 10, 2);
        $this->loader->add_action('woocommerce_create_refund', $angelleye_ppcp, 'own_woocommerce_create_refund', 10, 2);
        $this->loader->add_filter('angelleye_multi_account_need_shipping', $angelleye_ppcp, 'own_angelleye_multi_account_need_shipping', 10, 3);
        $this->loader->add_action( 'dokan_refund_approve_before_insert', $angelleye_ppcp, 'angelleye_multi_account_dokan_refund_approve', 10, 3 );
        $this->loader->add_filter('angelleye_ppcp_is_account_ready_to_paid', $angelleye_ppcp, 'angelleye_ppcp_is_account_ready_to_paid', 10, 2);
        $this->loader->add_filter('angelleye_ppcp_pfwma_is_api_set', $angelleye_ppcp, 'angelleye_is_multi_account_api_set', 10, 2);
        $this->loader->add_filter('woocommerce_payment_gateway_supports', $angelleye_ppcp, 'own_woocommerce_ppcp_payment_gateway_supports', 99, 3);
        //$paypal = new Paypal_For_Woocommerce_Multi_Account_Management_Admin_PayPal_Standard($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('angelleye_paypal_for_woocommerce_multi_account_api_paypal_payflow', $paypal_payflow, 'angelleye_paypal_for_woocommerce_multi_account_api_paypal_payflow', 11, 3);
        $this->loader->add_filter('angelleye_paypal_pro_payflow_amex_ca_usd', $paypal_payflow, 'angelleye_paypal_pro_payflow_amex_ca_usd', 10, 2);
        $this->loader->add_filter('angelleye_is_account_ready_to_paid', $express_checkout, 'angelleye_is_account_ready_to_paid', 10, 2);
        $this->loader->add_filter('angelleye_woocommerce_express_checkout_set_express_checkout_request_args', $express_checkout, 'angelleye_paypal_for_woocommerce_multi_account_api_paypal_express', 11, 5);
        $this->loader->add_filter('angelleye_woocommerce_express_checkout_do_express_checkout_payment_request_args', $express_checkout, 'angelleye_paypal_for_woocommerce_multi_account_api_paypal_express', 11, 5);
        $this->loader->add_action('angelleye_express_checkout_order_data', $express_checkout, 'own_angelleye_express_checkout_order_data', 10, 2);
        $this->loader->add_filter('woocommerce_payment_gateway_supports', $express_checkout, 'own_woocommerce_payment_gateway_supports', 10, 3);
        $this->loader->add_filter('angelleye_is_express_checkout_parallel_payment_not_used', $express_checkout, 'own_angelleye_is_express_checkout_parallel_payment_not_used', 10, 2);
        $this->loader->add_filter('angelleye_is_express_checkout_parallel_payment_handle', $express_checkout, 'own_angelleye_is_express_checkout_parallel_payment_handle', 10, 3);
        $this->loader->add_action('woocommerce_order_item_add_action_buttons', $express_checkout, 'own_woocommerce_order_item_add_action_buttons', 10, 1);
        $this->loader->add_action('woocommerce_order_refunded', $express_checkout, 'own_woocommerce_order_fully_refunded', 10, 2);
        //$this->loader->add_filter('woocommerce_paypal_args', $paypal, 'angelleye_woocommerce_paypal_args', 10, 2);
        $this->loader->add_action('woocommerce_create_refund', $express_checkout, 'own_woocommerce_create_refund', 10, 2);
        $this->loader->add_filter('angelleye_multi_account_need_shipping', $express_checkout, 'own_angelleye_multi_account_need_shipping', 10, 3);
        $this->loader->add_action( 'dokan_refund_approve_before_insert', $express_checkout, 'angelleye_multi_account_dokan_refund_approve', 10, 3 );
        $global_automatic_rule_creation_enable = get_option('global_automatic_rule_creation_enable', '');
        if( $global_automatic_rule_creation_enable == 'on' ) {
            $vendor = new Paypal_For_Woocommerce_Multi_Account_Management_Vendor($this->get_plugin_name(), $this->get_version());
            $this->loader->add_action('personal_options_update', $vendor, 'angelleye_paypal_for_woocommerce_multi_account_rule_save');
            $this->loader->add_action('edit_user_profile_update', $vendor, 'angelleye_paypal_for_woocommerce_multi_account_rule_save');
            $this->loader->add_action('user_register', $vendor, 'angelleye_paypal_for_woocommerce_multi_account_rule_save');
            $this->loader->add_action('profile_update', $vendor, 'angelleye_paypal_for_woocommerce_multi_account_rule_save');
            $this->loader->add_action('wcv_pro_store_settings_saved', $vendor, 'angelleye_paypal_for_woocommerce_multi_account_rule_save');
            $this->loader->add_action('dokan_new_seller_created', $vendor, 'angelleye_paypal_for_woocommerce_multi_account_rule_save');
            $this->loader->add_action('dokan_store_profile_saved', $vendor, 'angelleye_paypal_for_woocommerce_multi_account_rule_save');
            $this->loader->add_action('wcvendors_approve_vendor', $vendor, 'angelleye_paypal_for_woocommerce_multi_account_rule_save');
            $this->loader->add_action('wcvendors_shop_settings_saved', $vendor, 'angelleye_paypal_for_woocommerce_multi_account_rule_save');
            $this->loader->add_action('wcvendors_shop_settings_admin_saved', $vendor, 'angelleye_paypal_for_woocommerce_multi_account_rule_save');
            $this->loader->add_action('dokan_process_seller_meta_fields', $vendor, 'angelleye_paypal_for_woocommerce_multi_account_rule_save');
            $this->loader->add_action('dokan_new_vendor', $vendor, 'angelleye_paypal_for_woocommerce_multi_account_rule_save');
            $this->loader->add_action('dokan_seller_wizard_payment_field_save', $vendor, 'angelleye_paypal_for_woocommerce_multi_account_rule_save');
            
        }
        $this->loader->add_action('wp_ajax_pfwma_disable_all_vendor_rules', $plugin_admin, 'angelleye_pfwma_disable_all_vendor_rules');
        $this->loader->add_action('wp_ajax_pfwma_enable_all_vendor_rules', $plugin_admin, 'angelleye_pfwma_enable_all_vendor_rules');
        $this->loader->add_action('wp_ajax_pfwma_create_all_vendor_rules', $plugin_admin, 'angelleye_pfwma_create_all_vendor_rules');
        $this->loader->add_action( 'admin_init', $plugin_admin, 'angelleye_pfwma_display_notice');
        $this->loader->add_action( 'admin_footer', $plugin_admin, 'angelleye_pfwma_add_deactivation_form');
        $this->loader->add_action( 'wp_ajax_angelleye_send_deactivation_pfwma', $plugin_admin, 'angelleye_pfwma_plugin_deactivation_request');
        $angelleye_payment_load_balancer = get_option('angelleye_payment_load_balancer', '');
        if($angelleye_payment_load_balancer != '') {
            $load_balancer = new Paypal_For_Woocommerce_Multi_Account_Management_Payment_Load_Balancer($this->get_plugin_name(), $this->get_version());
            $this->loader->add_action( 'init', $load_balancer, 'angelleye_synce_express_checkout_account');
            $this->loader->add_action( 'init', $load_balancer, 'angelleye_synce_payflow_account');
            $this->loader->add_action( 'init', $load_balancer, 'angelleye_synce_ppcp_account');
        }
        $this->loader->add_filter('angelleye_is_express_checkout_parallel_payment_not_used', $express_checkout, 'own_angelleye_is_payment_load_balancer_not_used', 12, 2);
        $this->loader->add_filter('angelleye_is_express_checkout_parallel_payment_handle', $express_checkout, 'own_angelleye_is_express_checkout_payment_load_balancer_handle', 12, 3);
        $this->loader->add_action('update_angelleye_multi_account', $plugin_admin, 'own_update_angelleye_multi_account', 10);
        $this->loader->add_action('delete_user', $plugin_admin, 'angelleye_delete_multi_account', 10, 1);
        $this->loader->add_action('wp_ajax_angelleye_pfwma_get_products', $plugin_admin, 'angelleye_pfwma_get_products', 10);
        $this->loader->add_filter('wp_ajax_angelleye_pfwma_get_categories', $plugin_admin, 'angelleye_pfwma_get_categories', 10, 1);
        $this->loader->add_filter('wp_ajax_angelleye_pfwma_get_buyer_states', $plugin_admin, 'angelleye_pfwma_get_buyer_states', 10, 1);
        
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
            'configure' => sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=wc-settings&tab=multi_account_management'), __('Configure', 'paypal-for-woocommerce-multi-account-management')),
            'docs' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://www.angelleye.com/category/docs/paypal-for-woocommerce-multi-account-management-documentation/?utm_source=paypal-for-woocommerce-multi-account&utm_medium=docs_link&utm_campaign=plugin', __('Docs', 'paypal-for-woocommerce-multi-account-management')),
            'support' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://www.angelleye.com/support/?utm_source=paypal-for-woocommerce-multi-account&utm_medium=support_link&utm_campaign=plugin', __('Support', 'paypal-for-woocommerce-multi-account-management')),
            'review' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://www.angelleye.com/product/paypal-for-woocommerce-multi-account-management?utm_source=paypal-for-woocommerce-multi-account&utm_medium=review_link&utm_campaign=plugin', __('Write a Review', 'paypal-for-woocommerce-multi-account-management')),
        );
        return array_merge($custom_actions, $actions);
    }

    public function display_plugin_admin_page() {
        wp_dequeue_style('woocommerce_admin_styles');
        $this->display_plugin_admin_page_submenu();
        echo '<style> .button-primary.woocommerce-save-button { display: none; } </style>';
        $this->plugin_admin->display_admin_notice();
        $section = !empty($_GET['section']) ? $_GET['section'] : '';
        switch ($section) {
            case 'add_edit_account':
                $this->plugin_admin->angelleye_paypal_for_woocommerce_general_settings_tab_content();
                break;
            case 'settings':
                $this->plugin_admin->angelleye_multi_account_settings_fields();
                break;
            case 'total_payments':
                $this->plugin_admin->angelleye_multi_account_total_payments();
                break;
            default:
                $this->plugin_admin->angelleye_multi_account_list();
        }
    }

    public function display_plugin_admin_page_submenu() {
        ?>
        </form>
        <div class="wrap">
            <ul class="subsubsub">
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=multi_account_management')); ?>" class="<?php
                    if (empty($_GET['section'])) {
                        echo 'current';
                    }
                    ?>"><?php echo __('All PayPal Accounts', 'angelleye-paypal-shipment-tracking-woocommerce'); ?></a> |</li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=multi_account_management&section=add_edit_account')); ?>" class="<?php
                    if (!empty($_GET['section']) && $_GET['section'] == 'add_edit_account') {
                        echo 'current';
                    }
                    ?>"><?php echo __('Add / Edit Accounts', 'angelleye-paypal-shipment-tracking-woocommerce'); ?></a> | </li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=multi_account_management&section=settings')); ?>" class="<?php
                    if (!empty($_GET['section']) && $_GET['section'] == 'settings') {
                        echo 'current';
                    }
                    ?>"><?php echo __('Settings', 'angelleye-paypal-shipment-tracking-woocommerce'); ?></a> | </li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=multi_account_management&section=total_payments')); ?>" class="<?php
                    if (!empty($_GET['section']) && $_GET['section'] == 'total_payments') {
                        echo 'current';
                    }
                    ?>"><?php echo __('Account Payments Log', 'angelleye-paypal-shipment-tracking-woocommerce'); ?></a>  </li>
            </ul>
            <br class="clear">
            <?php
            if (!empty($this->message)) {
                echo '<div id="message" class="updated inline is-dismissible"><p><strong>' . esc_html($this->message) . '</strong></p></div>';
            }
            ?>
        </div>
        <?php
    }

    public function angelleye_woocommerce_settings_tabs_array($settings_tabs) {
        $settings_tabs['multi_account_management'] = __('PayPal Multi-Account Setup', 'paypal-for-woocommerce-multi-account-management');
        return $settings_tabs;
    }
    
    public function angelleye_pfwma_is_api_set($is_api_set, $value) {
        if( $value['multi_account_id'] === 'default' ) {
            return $is_api_set;
        }
        $microprocessing_array = get_post_meta($value['multi_account_id']);
        if (!empty($microprocessing_array['woocommerce_paypal_express_testmode']) && $microprocessing_array['woocommerce_paypal_express_testmode'][0] == 'on') {
            $testmode = true;
        } else {
            $testmode = false;
        }
        if ($testmode) {
            if( !empty($microprocessing_array['woocommerce_paypal_express_sandbox_api_username'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_api_password'][0] && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_api_signature'][0]))) {
                $is_api_set = true;
            } else {
                $is_api_set = false;
            }
        } else {
            if( !empty($microprocessing_array['woocommerce_paypal_express_api_username'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_api_signature'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_api_password'][0])) {
                $is_api_set = true;
            } else {
                $is_api_set = false;
            }
        }
        return $is_api_set;
    }

}
