<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Paypal_For_Woocommerce_Multi_Account_Management
 * @subpackage Paypal_For_Woocommerce_Multi_Account_Management/admin
 * @author     Angell EYE <service@angelleye.com>
 */
class Paypal_For_Woocommerce_Multi_Account_Management_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;
    public $final_associate_account;
    public $gateway_key;
    public $message;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->final_associate_account = array();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/paypal-for-woocommerce-multi-account-management-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts($hook_suffix) {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        wp_register_script('jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array('jquery'), '2.70', true);
        wp_enqueue_script('jquery-blockui');
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/paypal-for-woocommerce-multi-account-management-admin.js', array( 'jquery' ), $this->version, true);
        wp_enqueue_script($this->plugin_name .'ajax', plugin_dir_url(__FILE__) . 'js/paypal-for-woocommerce-multi-account-management-ajax.js', array( 'jquery', 'selectWoo', 'wc-enhanced-select' ), $this->version, true);
        if ('plugins.php' === $hook_suffix) {
            wp_enqueue_style('deactivation-pfwma-css', plugin_dir_url(__FILE__) . 'css/deactivation-modal.css', null, $this->version);
            wp_enqueue_script('deactivation-pfwma-js', plugin_dir_url(__FILE__) . 'js/deactivation-form-modal.js', null, $this->version, true);
            wp_localize_script('deactivation-pfwma', 'angelleye_ajax_data', array('nonce' => wp_create_nonce('angelleye-ajax')));
        }
    }

    public function angelleye_post_exists($id) {
        return is_string(get_post_status($id));
    }

    public function angelleye_display_multi_account_list() {
        $this->gateway_key = 'paypal_express';
        $angelleye_payment_load_balancer = get_option('angelleye_payment_load_balancer', '');
        if (empty($_GET['ID'])) {
            return false;
        }
        if ($this->angelleye_post_exists($_GET['ID']) == false) {
            return false;
        }
        $require_ssl = '';
        if (!is_ssl()) {
            $require_ssl = __('This image requires an SSL host.  Please upload your image to <a target="_blank" href="http://www.sslpic.com">www.sslpic.com</a> and enter the image URL here.', 'paypal-for-woocommerce-multi-account-management');
        }
        $selected_role = '';
        $ec_site_owner_commission = 0;
        $microprocessing = get_post_meta($_GET['ID']);
        echo '<br/><div class="angelleye_multi_account_left"><form method="post" id="angelleye_multi_account" action="" enctype="multipart/form-data"><table class="form-table">
        <tbody class="angelleye_micro_account_body">';
        $gateway_list = array();
        if (class_exists('AngellEYE_Gateway_Paypal')) {
            $gateway_list = array('paypal_express' => __('PayPal Express Checkout', ''), 'paypal_pro_payflow' => __('PayPal Payments Pro 2.0 (PayFlow)', ''));
        } else {
            //$gateway_list = array('paypal' => __('PayPal Standard', ''));
        }
        if (!empty($microprocessing['angelleye_multi_account_choose_payment_gateway'])) {
            $gateway_key_index = $microprocessing['angelleye_multi_account_choose_payment_gateway'];
            if (!empty($gateway_key_index[0])) {
                $this->gateway_key = $gateway_key = $gateway_key_index[0];
                if (!empty($gateway_list[$gateway_key])) {
                    $gateway_value = $gateway_list[$gateway_key];
                    $gateway_option_Selected = "<option value='$gateway_key'>$gateway_value</option>";
                    echo sprintf('<tr><th>%1$s</th><td><select class="angelleye_multi_account_choose_payment_gateway wc-enhanced-select" name="angelleye_multi_account_choose_payment_gateway">%2$s</select></td></tr>', __('Payment Gateway', ''), $gateway_option_Selected);
                }
            }
        } else {
            $gateway_option_Selected = "<option value='paypal_express'>PayPal Express Checkout</option>";
            echo sprintf('<tr><th>%1$s</th><td><select class="wc-enhanced-select angelleye_multi_account_choose_payment_gateway" name="angelleye_multi_account_choose_payment_gateway">%2$s</select></td></tr>', __('Payment Gateway', ''), $gateway_option_Selected);
        }

        if ($this->gateway_key == 'paypal_express') {
            $microprocessing_new = array();
            $microprocessing_key_array = array('woocommerce_paypal_express_enable', 'woocommerce_paypal_express_testmode', 'woocommerce_paypal_express_account_name', 'woocommerce_paypal_express_sandbox_email', 'woocommerce_paypal_express_sandbox_merchant_id', 'woocommerce_paypal_express_sandbox_api_username', 'woocommerce_paypal_express_sandbox_api_password', 'woocommerce_paypal_express_sandbox_api_signature', 'woocommerce_paypal_express_email', 'woocommerce_paypal_express_merchant_id', 'woocommerce_paypal_express_api_username', 'woocommerce_paypal_express_api_password', 'woocommerce_paypal_express_api_signature', 'woocommerce_paypal_express_api_condition_field', 'woocommerce_paypal_express_api_condition_sign', 'woocommerce_paypal_express_api_condition_value', 'woocommerce_paypal_express_api_user_role', 'woocommerce_paypal_express_api_user', 'woocommerce_paypal_express_api_product_ids', 'product_categories', 'product_tags', 'buyer_countries', 'woocommerce_priority', 'angelleye_multi_account_choose_payment_gateway', 'store_countries', 'shipping_class', 'currency_code', 'ec_site_owner_commission', 'ec_site_owner_commission_label');
            foreach ($microprocessing_key_array as $key => $value) {
                $microprocessing_new[$value] = isset($microprocessing[$value]) ? $microprocessing[$value] : array();
            }
            $microprocessing = $microprocessing_new;
            foreach ($microprocessing as $microprocessing_key => $microprocessing_value) {
                switch ($microprocessing_key) {
                    case 'woocommerce_paypal_express_enable':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_enable">%1$s</label></th><td class="forminp"><fieldset><label for="woocommerce_paypal_express_enable"><input class="woocommerce_paypal_express_enable" name="woocommerce_paypal_express_enable" %2$s id="woocommerce_paypal_express_enable" type="checkbox"> %3$s</label><br></fieldset></td></tr>', __('Enable / Disable', 'paypal-for-woocommerce-multi-account-management'), ($microprocessing_value[0] == 'on') ? 'checked' : '', __('Enable Account', 'paypal-for-woocommerce-multi-account-management'));
                        break;
                    case 'woocommerce_paypal_express_testmode':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_testmode_microprocessing">%1$s</label></th><td class="forminp"><fieldset><label for="woocommerce_paypal_express_testmode_microprocessing"><input class="woocommerce_paypal_express_testmode width460" name="woocommerce_paypal_express_testmode" %2$s id="woocommerce_paypal_express_testmode_microprocessing" type="checkbox"> %3$s</label><br></fieldset></td></tr>', __('PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'), ($microprocessing_value[0] == 'on') ? 'checked' : '', __('Enable PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'));
                        break;
                    case 'woocommerce_paypal_express_account_name':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_account_name_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_account_name" value="%2$s" id="woocommerce_paypal_express_account_name_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('Account Nickname', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_sandbox_email':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_email_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_email" value="%2$s" id="woocommerce_paypal_express_sandbox_email_microprocessing" style="" placeholder="you@youremail.com" type="email"></fieldset></td></tr>', __('PayPal Email', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_sandbox_merchant_id':
                        if (!empty($microprocessing_value[0])) {
                            echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_merchant_id_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_merchant_id" value="%2$s" id="woocommerce_paypal_express_sandbox_merchant_id_microprocessing" style="" placeholder="" type="text" readonly></fieldset></td></tr>', __('Merchant Account ID', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        }
                        break;
                    case 'woocommerce_paypal_express_sandbox_api_username':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_username_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_username" value="%2$s" id="woocommerce_paypal_express_sandbox_api_username_microprocessing" style="" placeholder="Optional" type="text"></fieldset></td></tr>', __('Sandbox API Username', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_sandbox_api_password':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_password" value="%2$s" id="woocommerce_paypal_express_sandbox_api_password_microprocessing" style="" placeholder="Optional" type="password"></fieldset></td></tr>', __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_sandbox_api_signature':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_signature_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_signature" value="%2$s" id="woocommerce_paypal_express_sandbox_api_signature_microprocessing" style="" placeholder="Optional" type="password"></fieldset></td></tr>', __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_email':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_email_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_email" value="%2$s" id="woocommerce_paypal_express_email_microprocessing" style="" placeholder="you@youremail.com" type="email"></fieldset></td></tr>', __('PayPal Email', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_merchant_id':
                        if (!empty($microprocessing_value[0])) {
                            echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_merchant_id_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_merchant_id" value="%2$s" id="woocommerce_paypal_express_merchant_id_microprocessing" style="" placeholder="" type="text" readonly></fieldset></td></tr>', __('Merchant Account ID', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        }
                        break;
                    case 'woocommerce_paypal_express_api_username':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_username_microprocessing">%1$s</label><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_api_username" value="%2$s" id="woocommerce_paypal_express_api_username_microprocessing" style="" placeholder="Optional" type="text"></fieldset></td></tr>', __('API Username', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_api_password':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_api_password" value="%2$s" id="woocommerce_paypal_express_api_password_microprocessing" style="" placeholder="Optional" type="password"></fieldset></td></tr>', __('API Password', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_api_signature':
                        echo sprintf('<tr><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_signature_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_api_signature" value="%2$s" id="woocommerce_paypal_express_api_signature_microprocessing" style="" placeholder="Optional" type="password"></fieldset></td></tr>', __('API Signature', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'ec_site_owner_commission':
                        if ($angelleye_payment_load_balancer == '') {
                            echo sprintf('<tr><th scope="row" class="titledesc"><label for="ec_site_owner_commission">%1$s</label></th><td class="forminp"><fieldset><input type="number" placeholder="0" name="ec_site_owner_commission" min="0" max="100" step="0.01" value="%2$s" id="ec_site_owner_commission"></fieldset></td></tr>', __('Site Owner Commission %', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        }
                        break;
                    case 'ec_site_owner_commission_label':
                        if ($angelleye_payment_load_balancer == '') {
                            echo sprintf('<tr><th scope="row" class="titledesc"><label for="ec_site_owner_commission_label">%1$s</label></th><td class="forminp"><fieldset><input type="text" class="input-text regular-input width460" name="ec_site_owner_commission_label" value="%2$s" id="ec_site_owner_commission_label" placeholder="Commission"></fieldset></td></tr>', __('Site Owner Commission Item Label', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        }
                        break;
                    case 'woocommerce_paypal_express_api_user_role':
                        $selected_role = $microprocessing_value[0];
                        break;
                    case 'woocommerce_paypal_express_api_user':
                        $selected_user = $microprocessing_value[0];
                        break;
                    case 'product_categories':
                        $product_categories = maybe_unserialize($microprocessing_value[0]);
                        break;
                    case 'product_tags':
                        $product_tags = maybe_unserialize($microprocessing_value[0]);
                        break;
                    case 'buyer_countries':
                        $buyer_countries = maybe_unserialize($microprocessing_value[0]);
                        break;
                    case 'woocommerce_priority':
                        $woocommerce_priority = $microprocessing_value[0];
                        break;
                    case 'store_countries':
                        $store_countries = maybe_unserialize($microprocessing_value[0]);
                        break;
                    case 'shipping_class':
                        $shipping_class = maybe_unserialize($microprocessing_value[0]);
                        break;
                    case 'currency_code':
                        $currency_code = empty($microprocessing_value[0]) ? '' : $microprocessing_value[0];
                        break;
                }
            }
        } else if ($this->gateway_key == 'paypal') {
            foreach ($microprocessing as $microprocessing_key => $microprocessing_value) {
                switch ($microprocessing_key) {
                    case 'woocommerce_paypal_enable':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_enable">%1$s</label></th><td class="forminp"><fieldset><label for="woocommerce_paypal_enable"><input class="woocommerce_paypal_enable" name="woocommerce_paypal_enable" %2$s id="woocommerce_paypal_enable" type="checkbox"> %3$s</label><br></fieldset></td></tr>', __('Enable / Disable', 'paypal-for-woocommerce-multi-account-management'), ($microprocessing_value[0] == 'on') ? 'checked' : '', __('Enable Account', 'paypal-for-woocommerce-multi-account-management'));
                        break;
                    case 'woocommerce_paypal_testmode':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_testmode">%1$s</label></th><td class="forminp"><fieldset><label for="woocommerce_paypal_testmode_microprocessing"><input class="woocommerce_paypal_testmode width460" name="woocommerce_paypal_testmode" %2$s id="woocommerce_paypal_testmode_microprocessing" type="checkbox"> %3$s</label><br></fieldset></td></tr>', __('PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'), ($microprocessing_value[0] == 'on') ? 'checked' : '', __('Enable PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'));
                        break;
                    case 'woocommerce_paypal_account_name':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_account_name_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_account_name" value="%2$s" id="woocommerce_paypal_account_name_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('Account Nickname', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_sandbox_email':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_sandbox_email_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_sandbox_email" value="%2$s" id="woocommerce_paypal_sandbox_email_microprocessing" style="" placeholder="you@youremail.com" type="email"></fieldset></td></tr>', __('PayPal Email', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_sandbox_api_username':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_sandbox_api_username_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_sandbox_api_username" value="%2$s" id="woocommerce_paypal_sandbox_api_username_microprocessing" style="" placeholder="Optional" type="text"></fieldset></td></tr>', __('Sandbox API Username', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_sandbox_api_password':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_sandbox_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_sandbox_api_password" value="%2$s" id="woocommerce_paypal_sandbox_api_password_microprocessing" style="" placeholder="Optional" type="password"></fieldset></td></tr>', __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_sandbox_api_signature':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_sandbox_api_signature_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_sandbox_api_signature" value="%2$s" id="woocommerce_paypal_sandbox_api_signature_microprocessing" style="" placeholder="Optional" type="password"></fieldset></td></tr>', __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_email':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_email_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_email" value="%2$s" id="woocommerce_paypal_email_microprocessing" style="" placeholder="you@youremail.com" type="email"></fieldset></td></tr>', __('PayPal Email', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_api_username':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_api_username_microprocessing">%1$s</label><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_api_username" value="%2$s" id="woocommerce_paypal_api_username_microprocessing" style="" placeholder="Optional" type="text"></fieldset></td></tr>', __('API Username', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_api_password':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_api_password" value="%2$s" id="woocommerce_paypal_api_password_microprocessing" style="" placeholder="Optional" type="password"></fieldset></td></tr>', __('API Password', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_api_signature':
                        echo sprintf('<tr><th scope="row" class="titledesc"><label for="woocommerce_paypal_api_signature_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_api_signature" value="%2$s" id="woocommerce_paypal_api_signature_microprocessing" style="" placeholder="Optional" type="password"></fieldset></td></tr>', __('API Signature', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_api_user_role':
                        $selected_role = $microprocessing_value[0];
                        break;
                    case 'product_categories':
                        $product_categories = maybe_unserialize($microprocessing_value[0]);
                        break;
                    case 'product_tags':
                        $product_tags = maybe_unserialize($microprocessing_value[0]);
                        break;
                    case 'buyer_countries':
                        $buyer_countries = maybe_unserialize($microprocessing_value[0]);
                        break;
                    case 'woocommerce_priority':
                        $woocommerce_priority = $microprocessing_value[0];
                        break;
                    case 'store_countries':
                        $store_countries = maybe_unserialize($microprocessing_value[0]);
                        break;
                    case 'shipping_class':
                        $shipping_class = maybe_unserialize($microprocessing_value[0]);
                        break;
                    case 'currency_code':
                        $currency_code = empty($microprocessing_value[0]) ? '' : $microprocessing_value[0];
                        break;
                }
            }
        } else if ($this->gateway_key == 'paypal_pro_payflow') {
            foreach ($microprocessing as $microprocessing_key => $microprocessing_value) {
                switch ($microprocessing_key) {
                    case 'woocommerce_paypal_pro_payflow_enable':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_pro_payflow_enable">%1$s</label></th><td class="forminp"><fieldset><label for="woocommerce_paypal_pro_payflow_enable"><input class="woocommerce_paypal_pro_payflow_enable" name="woocommerce_paypal_pro_payflow_enable" %2$s id="woocommerce_paypal_pro_payflow_enable" type="checkbox"> %3$s</label><br></fieldset></td></tr>', __('Enable / Disable', 'paypal-for-woocommerce-multi-account-management'), ($microprocessing_value[0] == 'on') ? 'checked' : '', __('Enable Account', 'paypal-for-woocommerce-multi-account-management'));
                        break;
                    case 'woocommerce_paypal_pro_payflow_testmode':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_pro_payflow_testmode">%1$s</label></th><td class="forminp"><fieldset><label for="woocommerce_paypal_pro_payflow_testmode_microprocessing"><input class="woocommerce_paypal_pro_payflow_testmode width460" name="woocommerce_paypal_pro_payflow_testmode" %2$s id="woocommerce_paypal_pro_payflow_testmode_microprocessing" type="checkbox"> %3$s</label><br></fieldset></td></tr>', __('PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'), ($microprocessing_value[0] == 'on') ? 'checked' : '', __('Enable PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'));
                        break;
                    case 'woocommerce_paypal_pro_payflow_account_name':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_pro_payflow_account_name_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_account_name" value="%2$s" id="woocommerce_paypal_pro_payflow_account_name_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('Account Nickname', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_pro_payflow_sandbox_paypal_partner_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner" value="%2$s" id="woocommerce_paypal_pro_payflow_sandbox_paypal_partner_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('Partner', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor" value="%2$s" id="woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('Vendor (Merchant Login)', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_pro_payflow_sandbox_api_paypal_user':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_pro_payflow_sandbox_api_paypal_user_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_sandbox_api_paypal_user" value="%2$s" id="woocommerce_paypal_pro_payflow_sandbox_api_paypal_user_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('User (optional)', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_pro_payflow_sandbox_api_password':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_pro_payflow_sandbox_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_sandbox_api_password" value="%2$s" id="woocommerce_paypal_pro_payflow_sandbox_api_password_microprocessing" style="" placeholder="" type="password"></fieldset></td></tr>', __('Password', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_pro_payflow_api_paypal_partner':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_pro_payflow_sandbox_paypal_partner_microprocessing">%1$s</label><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_api_paypal_partner" value="%2$s" id="woocommerce_paypal_pro_payflow_paypal_partner_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('Partner', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_pro_payflow_api_paypal_vendor':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_pro_payflow_api_paypal_vendor_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_api_paypal_vendor" value="%2$s" id="woocommerce_paypal_pro_payflow_api_paypal_vendor_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('Vendor (Merchant Login)', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_pro_payflow_api_paypal_user':
                        echo sprintf('<tr><th scope="row" class="titledesc"><label for="woocommerce_paypal_pro_payflow_api_paypal_user_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_api_paypal_user" value="%2$s" id="woocommerce_paypal_pro_payflow_api_paypal_user_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('User (optional)', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_pro_payflow_api_password':
                        echo sprintf('<tr><th scope="row" class="titledesc"><label for="woocommerce_paypal_pro_payflow_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_api_password" value="%2$s" id="woocommerce_paypal_pro_payflow_api_password_microprocessing" style="" placeholder="" type="password"></fieldset></td></tr>', __('Password', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_api_user_role':
                        $selected_role = $microprocessing_value[0];
                        break;
                    case 'woocommerce_paypal_express_api_user':
                        $selected_user = $microprocessing_value[0];
                        break;
                    case 'product_categories':
                        $product_categories = !empty(maybe_unserialize($microprocessing_value[0])) ? maybe_unserialize($microprocessing_value[0]) : '';
                        break;
                    case 'product_tags':
                        $product_tags = maybe_unserialize($microprocessing_value[0]);
                        break;
                    case 'buyer_countries':
                        $buyer_countries = !empty(maybe_unserialize($microprocessing_value[0])) ? maybe_unserialize($microprocessing_value[0]) : '';
                        break;
                    case 'woocommerce_priority':
                        $woocommerce_priority = !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '';
                        break;
                    case 'card_type':
                        $card_type = empty($microprocessing_value[0]) ? '' : $microprocessing_value[0];
                        break;
                    case 'currency_code':
                        $currency_code = empty($microprocessing_value[0]) ? '' : $microprocessing_value[0];
                        break;
                    case 'store_countries':
                        $store_countries = !empty(maybe_unserialize($microprocessing_value[0])) ? maybe_unserialize($microprocessing_value[0]) : '';
                        break;
                    case 'shipping_class':
                        $shipping_class = maybe_unserialize($microprocessing_value[0]);
                        break;
                }
            }
        }
        if ($angelleye_payment_load_balancer == '') {
            $option_three_array = array('greaterthan' => __('Greater than', 'paypal-for-woocommerce-multi-account-management'), 'lessthan' => __('Less than', 'paypal-for-woocommerce-multi-account-management'), 'equalto' => __('Equal to', 'paypal-for-woocommerce-multi-account-management'));
            $option_three = '';
            foreach ($option_three_array as $key => $value) {
                if (!empty($microprocessing['woocommerce_paypal_express_api_condition_sign'][0]) && $microprocessing['woocommerce_paypal_express_api_condition_sign'][0] == $key) {
                    $option_three .= '<option selected value=' . $key . '>' . $value . '</option>';
                } else {
                    $option_three .= '<option value=' . $key . '>' . $value . '</option>';
                }
            }
            $option_four = !empty($microprocessing['woocommerce_paypal_express_api_condition_value']) ? $microprocessing['woocommerce_paypal_express_api_condition_value'][0] : '';
            $option_five = '<p class="description">' . __('Buyer Role', 'paypal-for-woocommerce-multi-account-management') . '</p>';
            $option_five .= '<select class="wc-enhanced-select smart_forwarding_field" name="woocommerce_paypal_express_api_user_role" id="woocommerce_paypal_express_api_user_role">';
            $option_five .= '<option value="all">' . __('All', 'paypal-for-woocommerce-multi-account-management') . '</option>';
            $editable_roles = array_reverse(get_editable_roles());
            foreach ($editable_roles as $role => $details) {
                $name = translate_user_role($details['name']);
                if ($selected_role == $role) {
                    $option_five .= "<option selected='selected' value='" . esc_attr($role) . "'>$name</option>";
                } else {
                    $option_five .= "<option value='" . esc_attr($role) . "'>$name</option>";
                }
            }
            $option_five .= '</select>';

            $option_five_one = "<p class='description'>" . __('Seller/Product Author', 'paypal-for-woocommerce-multi-account-management') . "</p>";
            $option_five_one .= "<select class='wc-customer-search smart_forwarding_field' id='woocommerce_paypal_express_api_user' name='woocommerce_paypal_express_api_user' data-placeholder='" . __( 'All', 'paypal-for-woocommerce-multi-account-management'). "' data-minimum_input_length='3' data-allow_clear='true'>";
            $user_string = __( 'All', 'paypal-for-woocommerce-multi-account-management');
            if( !empty($selected_user) ) {
                $user = get_user_by( 'id', $selected_user );
                if ( ! empty( $user ) ) {
                    $user_string = sprintf(
                        esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce' ),
                        $user->display_name,
                        absint( $user->ID ),
                        $user->user_email
                    );
                    $user_string = htmlspecialchars( wp_kses_post( $user_string ) );
                }
            }
            $option_five_one .= "<option selected='selected' value='" . esc_attr( $selected_user ) . "' > $user_string </option>";
            $option_five_one .= "</select>";
            $option_ten = '<p class="description">' . __('Priority', 'paypal-for-woocommerce-multi-account-management') . '</p>';
            $option_ten .= '<select class="wc-enhanced-select smart_forwarding_field" name="woocommerce_priority" id="woocommerce_priority">';
            for ($x = 0; $x <= 100; $x++) {
                if ($x == 0) {
                    $woocommerce_priority_text = $x . ' - Lowest';
                } elseif ($x == 100) {
                    $woocommerce_priority_text = $x . ' - Highest';
                } else {
                    $woocommerce_priority_text = $x;
                }
                if (isset($woocommerce_priority) && $woocommerce_priority == $x) {
                    $option_ten .= "<option selected='selected' value='" . $x . "'>$woocommerce_priority_text</option>";
                } else {
                    $option_ten .= "<option value='" . $x . "'>$woocommerce_priority_text</option>";
                }
            }
            $option_ten .= '</select>';
            $product_ids = array();
            if (isset($microprocessing['woocommerce_paypal_express_api_product_ids'][0])) {
                $product_ids = maybe_unserialize($microprocessing['woocommerce_paypal_express_api_product_ids'][0]);
            }
            $option_seven = '<p class="description">' . __('Buyer country', 'paypal-for-woocommerce-multi-account-management') . '</p>';
            $option_seven .= '<select id="buyer_countries" name="buyer_countries[]" style="width: 78%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="' . __("All countries", "paypal-for-woocommerce-multi-account-management") . '">';
            $countries = WC()->countries->get_countries();
            if (!isset($buyer_countries)) {
                $buyer_countries = array();
            }
            if ($countries) {
                foreach ($countries as $country_key => $country_full_name) {
                    $option_seven .= '<option value="' . esc_attr($country_key) . '"' . wc_selected($country_key, $buyer_countries) . '>' . esc_html($country_full_name) . '</option>';
                }
            }
            $option_seven .= '</select>';
            $option_fourteen = '<p class="description">' . __('Store country', 'paypal-for-woocommerce-multi-account-management') . '</p>';
            $option_fourteen .= '<select id="store_countries" name="store_countries" style="width: 78%;"  class="wc-enhanced-select" data-placeholder="' . __("All countries", "paypal-for-woocommerce-multi-account-management") . '">';
            if ($countries) {
                $store_countries = !empty($store_countries) ? $store_countries : '';
                $option_fourteen .= '<option value="0">All countries</option>';
                foreach ($countries as $country_key => $country_full_name) {
                    $option_fourteen .= '<option value="' . esc_attr($country_key) . '"' . wc_selected($country_key, $store_countries) . '>' . esc_html($country_full_name) . '</option>';
                }
            }

            $option_fourteen .= '</select>';
            $option_fifteen = '';
            if (wc_shipping_enabled()) {
                $option_fifteen = '<p class="description">' . __('Shipping Class', 'paypal-for-woocommerce-multi-account-management') . '</p>';
                $option_fifteen .= '<select id="pfwst_shipping_class" name="shipping_class" style="width: 78%;"  class="wc-enhanced-select" data-placeholder="' . __("All Shipping Class", "paypal-for-woocommerce-multi-account-management") . '">';
                $classes = get_terms('product_shipping_class', array('hide_empty' => 1));
                $shipping_classes_array = !is_wp_error($classes) ? $classes : array();
                if ($shipping_classes_array) {
                    $shipping_class = !empty($shipping_class) ? $shipping_class : '';
                    $option_fifteen .= '<option value="all">All Shipping Class</option>';
                    foreach ($shipping_classes_array as $classes_key => $classes_name) {
                        $option_fifteen .= '<option value="' . esc_attr($classes_name->term_id) . '"' . wc_selected($classes_name->term_id, $shipping_class) . '>' . esc_html($classes_name->name) . '</option>';
                    }
                }
                $option_fifteen .= '</select>';
            }
            $option_eight = '<p class="description"> ' . apply_filters('angelleye_multi_account_display_category_label', __('Product categories', 'paypal-for-woocommerce-multi-account-management')) . '</p>';
            $option_eight .= '<select id="product_categories" name="product_categories[]" style="width: 78%;"  class="angelleye-category-search" multiple="multiple" data-placeholder="' . __('Any category', 'paypal-for-woocommerce-multi-account-management') . '">';
            if( !empty($product_categories) ) {
                foreach ($product_categories as $key => $value) {
                    $term = get_term($value);
                    if ( $term && ! is_wp_error( $term ) ) {
                            $option_eight .= '<option selected="selected" value="' . esc_attr($term->term_id) . '">' . esc_html($term->name) . '</option>';
                    }
                }
            }
            $option_eight .= '</select>';
            $option_nine = '<p class="description">' . __('Product tags', 'paypal-for-woocommerce-multi-account-management') . '</p>';
            $option_nine .= '<select id="product_tags" name="product_tags[]" style="width: 78%;"  class="angelleye-product-tag-search" multiple="multiple" data-action="angelleye_pfwma_get_product_tags" data-placeholder="' . __('Any tag', 'paypal-for-woocommerce-multi-account-management') . '">';
            if (!empty($product_tags)) {
                foreach ($product_tags as $key => $value) {
                    $term_object = get_term_by('id', $value, 'product_tag');
                    if (!empty($term_object->name)) {
                        $option_nine .= '<option value="' . esc_attr($value) . '" selected>' . esc_html($term_object->name) . '</option>';
                    }
                }
            }
            $option_nine .= '</select>';
            $option_six = '<p class="description">' . apply_filters('angelleye_multi_account_display_products_label', __('Products', 'paypal-for-woocommerce-multi-account-management')) . '</p>';
            $option_six .= '<select id="product_list" class="angelleye-product-search" multiple="multiple" style="width: 78%;" name="woocommerce_paypal_express_api_product_ids[]" data-action="angelleye_pfwma_get_products" data-placeholder="' . esc_attr__('Any Product&hellip;', 'paypal-for-woocommerce-multi-account-management') . '">';
            if( !empty($product_ids) ) {
                foreach ($product_ids as $key => $value) {
                    $product_title = get_the_title($value);
                    if ( $product_title && ! is_wp_error( $product_title ) ) {
                        $option_six .= '<option value="' . esc_attr($value) . '" selected>' . esc_html($product_title) . '</option>';
                    }
                }
            }
            
            $option_thirteen = '<p class="description">' . __('Currency Code', 'paypal-for-woocommerce-multi-account-management') . '</p>';
            $option_thirteen .= '<select class="wc-enhanced-select currency_code" name="currency_code" id="currency_code">';
            $option_thirteen .= "<option value=''>All</option>";
            $currency_code_options = get_woocommerce_currencies();
            foreach ($currency_code_options as $code => $name) {
                $currency_code_options[$code] = $name . ' (' . get_woocommerce_currency_symbol($code) . ')';
            }
            foreach ($currency_code_options as $currency_code_key => $currency_code_value) {
                if (isset($currency_code) && $currency_code == $currency_code_key) {
                    $option_thirteen .= "<option selected='selected' value='" . $currency_code_key . "'>$currency_code_value</option>";
                } else {
                    $option_thirteen .= "<option value='" . $currency_code_key . "'>$currency_code_value</option>";
                }
            }
            $option_thirteen .= '</select>';
            if ($this->gateway_key == 'paypal_pro_payflow') {
                $option_twelve = '<p class="description">' . __('Card Type', 'paypal-for-woocommerce-multi-account-management') . '</p>';
                $option_twelve .= '<select class="wc-enhanced-select card_type" name="card_type" id="card_type">';
                $option_twelve .= "<option value=''>All</option>";
                $card_type_array = array('visa' => 'Visa', 'amex' => 'American Express', 'mastercard' => 'MasterCard', 'discover' => 'Discover', 'maestro' => 'Maestro/Switch');
                foreach ($card_type_array as $card_key => $card_value) {
                    if ($card_type == $card_key) {
                        $option_twelve .= "<option selected='selected' value='" . $card_key . "'>$card_value</option>";
                    } else {
                        $option_twelve .= "<option value='" . $card_key . "'>$card_value</option>";
                    }
                }
                $option_twelve .= '</select>';
            } else {
                $option_twelve = '';
            }
            $option_six .= '</select><p class="description">' . __('Transaction Amount', 'paypal-for-woocommerce-multi-account-management') . '</p>';
            ?>
            <tr>
                <th scope="row" class="titledesc">
                    <label for="woocommerce_paypal_express_api_trigger_conditions"><?php echo __('Trigger Conditions', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                </th>
                <td class="forminp">
                    <fieldset class="pfwma_section_ui">
                    <legend><?php echo __('Buyer Conditions', 'paypal-for-woocommerce-multi-account-management'); ?> </legend>
                        <?php echo $option_five; ?>
                        <?php echo $option_seven; ?>
                    </fieldset>
                    <fieldset class="pfwma_section_ui">
                        <legend><?php echo __('Seller Conditions', 'paypal-for-woocommerce-multi-account-management'); ?> </legend>
                        <?php echo $option_five_one; ?>
                    </fieldset>
                    <fieldset class="pfwma_section_ui">
                        <legend><?php echo __('Common Conditions', ''); ?> </legend>
                        <?php echo $option_fourteen; ?>
                        <?php echo $option_fifteen; ?>
                        <?php echo $option_eight; ?>
                        <?php echo $option_nine; ?>
                        <?php echo $option_six; ?>
                        <input type="hidden" name="woocommerce_paypal_express_api_condition_field" value="transaction_amount">
                        <select class="smart_forwarding_field" name="woocommerce_paypal_express_api_condition_sign"><?php echo $option_three; ?></select>&nbsp;
                        <input class="input-text regular-input" name="woocommerce_paypal_express_api_condition_value" id="woocommerce_paypal_express_api_condition_value" type="number" min="0" max="1000" step="0.01" value="<?php echo $option_four; ?>">
                        <?php echo $option_twelve; ?>
                        <?php echo $option_thirteen; ?>
                        <?php echo $option_ten; ?>
                    </fieldset>
                </td>
            </tr>
            <?php
        }
        echo sprintf('<tr style="display: table-row;" valign="top">
                                    <td scope="row" class="titledesc">
                                        <input name="is_edit" class="button-primary woocommerce-save-button" type="hidden" value="%1$s" />
                                        <input id="microprocessing_save" name="microprocessing_save" class="button-primary" type="submit" value="%2$s" />
                                        <a href="?page=wc-settings&tab=multi_account_management" class="button-primary button">%3$s</a>
                                        %4$s
                                    </td>
                                </tr>', $_GET['ID'], __('Save Changes', 'paypal-for-woocommerce-multi-account-management'), __('Cancel', 'paypal-for-woocommerce-multi-account-management'), wp_nonce_field('microprocessing_save'));
        echo '</tbody></table></form></div>';
        $this->angelleye_multi_account_tooltip_box();
    }

    public function angelleye_multi_account_settings_fields() {
        if (!empty($_POST['global_commission_microprocessing_save'])) {
            update_option('global_ec_site_owner_commission', wc_clean($_POST['global_ec_site_owner_commission']));
            update_option('global_ec_site_owner_commission_label', wc_clean($_POST['global_ec_site_owner_commission_label']));
            $ec_include_tax_shipping_in_commission = !empty($_POST['global_ec_include_tax_shipping_in_commission']) ? $_POST['global_ec_include_tax_shipping_in_commission'] : '';
            update_option('global_ec_include_tax_shipping_in_commission', wc_clean($ec_include_tax_shipping_in_commission));
            if(isset($_POST['global_automatic_rule_creation_enable'])) {
                update_option('global_automatic_rule_creation_enable', wc_clean($_POST['global_automatic_rule_creation_enable']));
                update_option('global_automatic_rule_creation_testmode', wc_clean($_POST['global_automatic_rule_creation_testmode']));
            }
            $angelleye_payment_load_balancer = !empty($_POST['angelleye_payment_load_balancer']) ? $_POST['angelleye_payment_load_balancer'] : '';
            update_option('angelleye_payment_load_balancer', wc_clean($angelleye_payment_load_balancer));
            $this->message = __('Your settings have been saved.', 'paypal-for-woocommerce-multi-account-management');
        }
        if (!empty($this->message)) {
            echo '<div id="message" class="updated inline is-dismissible"><p><strong>' . esc_html($this->message) . '</strong></p></div>';
        }
        $global_ec_site_owner_commission = get_option('global_ec_site_owner_commission', '');
        $global_ec_site_owner_commission_label = get_option('global_ec_site_owner_commission_label', '');
        $global_automatic_rule_creation_enable = get_option('global_automatic_rule_creation_enable', '');
        $global_automatic_rule_creation_testmode = get_option('global_automatic_rule_creation_testmode', '');
        $angelleye_payment_load_balancer = get_option('angelleye_payment_load_balancer', '');
        $global_ec_include_tax_shipping_in_commission = get_option('global_ec_include_tax_shipping_in_commission', '');
        ?>
        <div id="angelleye_paypal_marketing_table">
        <div class="angelleye_multi_account_global_setting">
            <form method="post" action="" enctype="multipart/form-data">
                <table class="form-table">
                    <tr class="angelleye_payment_load_balancer_tr">
                        <th scope="row" class="titledesc">
                            <label for="angelleye_payment_load_balancer" class="commission"><?php echo __('Enable/Disable', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                        </th>
                        <td class="forminp">
                            <fieldset>
                                <label for="angelleye_payment_load_balancer">
                                    <input class="angelleye_payment_load_balancer" type="checkbox" name="angelleye_payment_load_balancer" id="angelleye_payment_load_balancer" <?php echo ($angelleye_payment_load_balancer == 'on') ? 'checked' : '' ?>>
                                    <?php echo __('Payment Load Balancer', 'paypal-for-woocommerce-multi-account-management'); ?>
                                </label>
                                <p class="description">
                                    <?php echo __('Cycle through a series of accounts to balance the load of payment volume across each account. <a href="https://www.angelleye.com/paypal-for-woocommerce-multi-account-management-setup-guide/#load-balancer" target="_blank">Read more</a>', 'paypal-for-woocommerce'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr class="angelleye_multi_account_paypal_express_field">
                        <th scope="row" class="titledesc">
                            <label for="global_ec_site_owner_commission" class="commission"><?php echo __('Global Site Owner Commission %', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                        </th>
                        <td class="forminp">
                            <fieldset>
                                <input type="number" name="global_ec_site_owner_commission" min="0" max="100" step="0.01" placeholder="0" class="commission" value="<?php echo!empty($global_ec_site_owner_commission) ? $global_ec_site_owner_commission : ''; ?>">
                            </fieldset>
                        </td>
                    </tr>
                    <tr class="angelleye_multi_account_paypal_express_field">
                        <th scope="row" class="titledesc">
                            <label for="global_ec_site_owner_commission_label" class="commission"><?php echo __('Global Site Owner Commission Item Label', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                        </th>
                        <td class="forminp">
                            <fieldset>
                                <input type="text" class="input-text regular-input commission" name="global_ec_site_owner_commission_label" placeholder="Commission" value="<?php echo!empty($global_ec_site_owner_commission_label) ? $global_ec_site_owner_commission_label : ''; ?>">
                            </fieldset>
                        </td>
                    </tr>
                    <tr class="angelleye_multi_account_paypal_express_field">
                        <th scope="row" class="titledesc">
                            <label for="angelleye_payment_load_balancer" class="commission"><?php echo __('Enable/Disable', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                        </th>
                        <td class="forminp">
                            <fieldset>
                                <label for="global_ec_include_tax_shipping_in_commission">
                                    <input class="global_ec_include_tax_shipping_in_commission" type="checkbox" name="global_ec_include_tax_shipping_in_commission" id="global_ec_include_tax_shipping_in_commission" <?php echo ($global_ec_include_tax_shipping_in_commission == 'on') ? 'checked' : '' ?>>
                                    <?php echo __('Include sales tax and shipping amounts in commission calculations', 'paypal-for-woocommerce-multi-account-management'); ?>
                                </label>
                                <p class="description">
                                    <?php echo __('', 'paypal-for-woocommerce'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                 <?php if (function_exists('dokan') ) {
                     echo '<h2>' . __('Dokan Settings', 'paypal-for-woocommerce-multi-account-management') . '</h2>';
                 } elseif(class_exists('WCV_Vendors')) {
                     echo '<h2>' . __('WC Vendors Settings', 'paypal-for-woocommerce-multi-account-management') . '</h2>';
                 }
                 ?>
                <table class="form-table">
                    <?php if (function_exists('dokan') || class_exists('WCV_Vendors')) { ?>
                        <tr class="angelleye_multi_account_paypal_express_field" valign="top">
                            <th scope="row" class="titledesc">
                                <label for="global_automatic_rule_creation_enable"><?php echo __('Enable / Disable', ''); ?></label>
                            </th>
                            <td class="forminp">
                                <fieldset>
                                    <label for="global_automatic_rule_creation_enable">
                                        <input class="global_automatic_rule_creation_enable" name="global_automatic_rule_creation_enable" id="global_automatic_rule_creation_enable" type="checkbox" <?php echo ($global_automatic_rule_creation_enable == 'on') ? 'checked' : '' ?> ><?php echo __('Enable Automatic Rule Creation', 'paypal-for-woocommerce-multi-account-management'); ?> </label><br>
                                </fieldset>
                            </td>
                        </tr>
                        <tr class="angelleye_multi_account_paypal_express_field" valign="top">
                            <th scope="row" class="titledesc">
                                <label for="global_automatic_rule_creation_testmode"><?php echo __('PayPal Sandbox', ''); ?></label>
                            </th>
                            <td class="forminp">
                                <fieldset>
                                    <label for="global_automatic_rule_creation_testmode">
                                        <input class="global_automatic_rule_creation_testmode" name="global_automatic_rule_creation_testmode" id="global_automatic_rule_creation_testmode" type="checkbox" <?php echo ($global_automatic_rule_creation_testmode == 'on') ? 'checked' : '' ?> ><?php echo __('Enable PayPal Sandbox for Automatic Rule Creation', 'paypal-for-woocommerce-multi-account-management'); ?></label><br>
                                </fieldset>
                            </td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <th scope="row" class="titledesc">
                        </th>
                        <td class="forminp">
                            <fieldset>
                                <input name="global_commission_microprocessing_save" class="button-primary" type="submit" value="<?php esc_attr_e('Save Changes', 'paypal-for-woocommerce-multi-account-management'); ?>" />
                                <a href="?page=wc-settings&tab=multi_account_management" class="button-primary button"><?php esc_attr_e('Cancel', 'paypal-for-woocommerce-multi-account-management'); ?></a>
                                <?php wp_nonce_field('microprocessing_save'); ?>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        </div>
        <?php
        $this->angelleye_pfwma_display_marketing_sidebar();
    }

    public function angelleye_multi_account_tooltip_box() {
        ?>

        <div class="angelleye_multi_account_right">
            <h3><?php echo __('Account Setup', 'paypal-for-woocommerce-multi-account-management'); ?></h3>
            <ul class="angelleye_pfwma_tips">
                <li><?php echo __('Add your PayPal account details and configure your Trigger Condition for the account.  Click Save Changes to save the account.', 'paypal-for-woocommerce-multi-account-management'); ?></li>
                <li><?php echo __('To modify an account, click the Edit link from the list below, make your adjustments, and then click Save Changes to apply.', 'paypal-for-woocommerce-multi-account-management'); ?></li>
                <li><?php echo __('You may add as many accounts as you like with trigger conditions set so that money goes the account you want based on the order amount.', 'paypal-for-woocommerce-multi-account-management'); ?></li>
                <li><?php echo __('You may obtain your live account credentials using', 'paypal-for-woocommerce-multi-account-management'); ?> <a href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run"><?php echo __('this link', 'paypal-for-woocommerce-multi-account-management'); ?></a>.</li>
                <li><?php echo __('Sandbox accounts/credentials can be obtained within your', 'paypal-for-woocommerce-multi-account-management'); ?> <a href="https://developer.paypal.com"><?php echo __('PayPal developer account', 'paypal-for-woocommerce-multi-account-management'); ?></a>.</li>
            </ul>
            <h3><?php echo __('Considerations', 'paypal-for-woocommerce-multi-account-management'); ?></h3>
            <ul class="angelleye_pfwma_tips">
                <li><?php echo __('Do not forget that Express Checkout Shortcut (from product pages or the cart page) will skip the WooCommerce checkout page.  If shipping and/or taxes will be applied when the buyer returns to your site you may want to factor that into the trigger condition you build for the account.', 'paypal-for-woocommerce-multi-account-management'); ?></li>
                <p><strong><?php echo __('Example', 'paypal-for-woocommerce-multi-account-management'); ?></strong></p>
                <p><?php echo __("If you want the account to be used when the order is less than 12.00, and you know you will be adding 4.00 for shipping/taxes, you may want to set the trigger condition to 7.99.", 'paypal-for-woocommerce-multi-account-management'); ?></p>
            </ul>
        </div>
        <?php
    }

    public function angelleye_multi_account_ui() {
        if (!empty($_GET['success'])) {
            $this->message = __('Your settings have been saved.', 'paypal-for-woocommerce-multi-account-management');
        }
        if (!empty($_GET['deleted'])) {
            $this->message = __('Account permanently deleted.', 'paypal-for-woocommerce-multi-account-management');
        }
        if (!empty($this->message)) {
            echo '<div id="message" class="updated inline is-dismissible"><p><strong>' . esc_html($this->message) . '</strong></p></div>';
        }
        if (!empty($_POST['angelleye_multi_account_choose_payment_gateway']) && $_POST['angelleye_multi_account_choose_payment_gateway'] == 'paypal_pro_payflow') {
            $this->angelleye_save_multi_account_data_paypal_pro_payflow();
        }
        if (!empty($_POST['angelleye_multi_account_choose_payment_gateway']) && $_POST['angelleye_multi_account_choose_payment_gateway'] == 'paypal_express') {
            $this->angelleye_save_multi_account_data();
        }
        if (!empty($_POST['angelleye_multi_account_choose_payment_gateway']) && $_POST['angelleye_multi_account_choose_payment_gateway'] == 'paypal') {
            $this->angelleye_save_multi_account_data_paypal();
        }

        if (empty($_GET['action'])) {
            ?>
            <br/>
            <div class="angelleye_multi_account_left">
                <form method="post" id="angelleye_multi_account" action="" enctype="multipart/form-data">
                    <table class="form-table" id="micro_account_fields" >
                        <tbody class="angelleye_micro_account_body">
                            <?php echo $this->angelleye_multi_account_choose_payment_gateway(); ?>
                            <?php echo $this->angelleye_multi_account_api_field_ui() ?>
                            <?php echo $this->angelleye_multi_account_paypal_pro_payflow_api_field_ui(); ?>
                            <?php echo $this->angelleye_multi_account_api_paypal_field_ui(); ?>
                            <?php
                            $angelleye_payment_load_balancer = get_option('angelleye_payment_load_balancer', '');
                            if ($angelleye_payment_load_balancer == '') {
                                echo $this->angelleye_multi_account_condition_ui();
                            }
                            ?>
                            <tr valign="top">
                                <td scope="row" class="titledesc">
                                    <input id="microprocessing_save" name="microprocessing_save" class="button-primary" type="submit" value="<?php esc_attr_e('Save Changes', 'paypal-for-woocommerce-multi-account-management'); ?>" />
                                    <a href="?page=wc-settings&tab=multi_account_management" class="button-primary button"><?php esc_attr_e('Cancel', 'paypal-for-woocommerce-multi-account-management'); ?></a>
                                    <?php wp_nonce_field('microprocessing_save'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
            <?php
            $this->angelleye_multi_account_tooltip_box();
        } elseif (!empty($_GET['action']) && $_GET['action'] == 'edit') {
            $this->angelleye_display_multi_account_list();
        }
        ?>
        <br/><br/>
        <div>

        </div>
        <?php
        $woocommerce_paypal_express_settings = get_option('woocommerce_paypal_express_settings');
        if (!empty($woocommerce_paypal_express_settings['microprocessing'])) {
            $this->angelleye_display_multi_account_list($woocommerce_paypal_express_settings['microprocessing']);
        }
    }

    public function angelleye_multi_account_list() {
        $active_count = $this->angelleye_multi_account_get_count_active_vendor();
        $deactive_count = $this->angelleye_multi_account_get_count_deactive_vendor();
        $active_rule_text = ($active_count > 1) ? 'rules' : 'rule';
        $deactive_rule_text = ($deactive_count > 1) ? 'rules' : 'rule';
        wp_localize_script('paypal-for-woocommerce-multi-account-management', 'pfwma_param', array(
            'disable_all_vendor_rules_alert_message' => sprintf(__('This will disable %s auto generated %s, Would you like to continue?', 'paypal-for-woocommerce-multi-account-management'), $active_count, $active_rule_text),
            'enable_all_vendor_rules_alert_message' => sprintf(__('This will enable %s auto generated %s, Would you like to continue?', 'paypal-for-woocommerce-multi-account-management'), $deactive_count, $deactive_rule_text)
                )
        );
        ?>
        <div id="angelleye_paypal_marketing_table">
            <br>
            <h1 class="wp-heading-inline"><?php echo __('Accounts', ''); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=multi_account_management&section=add_edit_account')); ?>" class="page-title-action"><?php echo __('Add New', 'paypal-for-woocommerce-multi-account-management'); ?></a>
            <?php
            if ($active_count !== false) {
                ?> <a class="page-title-action disable_all_vendor_rules"><?php echo __('Disable All Auto-generated Vendor Rules', 'paypal-for-woocommerce-multi-account-management'); ?></a> <?php
            }
            if ($deactive_count !== false) {
                ?> <a class="page-title-action enable_all_vendor_rules"><?php echo __('Enable All Auto-generated Vendor Rules', 'paypal-for-woocommerce-multi-account-management'); ?></a> <?php
            }
            if (class_exists('Paypal_For_Woocommerce_Multi_Account_Management_List_Data')) {
                $table = new Paypal_For_Woocommerce_Multi_Account_Management_List_Data();
                $table->prepare_items();
                echo '<form id="account-filter" method="post">';
                echo sprintf('<input type="hidden" name="page" value="%1$s" />', $_REQUEST['page']);
                $table->display();
                echo '</form>';
            }
            ?> </div> <?php
        $this->angelleye_pfwma_display_marketing_sidebar();
    }

    public function angelleye_save_multi_account_data() {
        if (!empty($_POST['microprocessing_save'])) {
            if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'microprocessing_save')) {
                die(__('Action failed. Please refresh the page and retry.', 'paypal-for-woocommerce-multi-account-management'));
            }
            $PayPalConfig = array();
            if (!empty($_POST['woocommerce_paypal_express_testmode']) && $_POST['woocommerce_paypal_express_testmode'] == 'on') {
                if (empty($_POST['woocommerce_paypal_express_sandbox_api_username']) && empty($_POST['woocommerce_paypal_express_api_password']) && empty($_POST['woocommerce_paypal_express_sandbox_api_signature'])) {
                    
                } else {
                    $PayPalConfig = array(
                        'Sandbox' => true,
                        'APIUsername' => trim($_POST['woocommerce_paypal_express_sandbox_api_username']),
                        'APIPassword' => trim($_POST['woocommerce_paypal_express_sandbox_api_password']),
                        'APISignature' => trim($_POST['woocommerce_paypal_express_sandbox_api_signature'])
                    );
                }
            } else {
                if (empty($_POST['woocommerce_paypal_express_api_username']) && empty($_POST['woocommerce_paypal_express_api_password']) && empty($_POST['woocommerce_paypal_express_api_signature'])) {
                    
                } else {
                    $PayPalConfig = array(
                        'Sandbox' => false,
                        'APIUsername' => trim($_POST['woocommerce_paypal_express_api_username']),
                        'APIPassword' => trim($_POST['woocommerce_paypal_express_api_password']),
                        'APISignature' => trim($_POST['woocommerce_paypal_express_api_signature'])
                    );
                }
            }
            if (!class_exists('Angelleye_PayPal_WC')) {
                if (defined('PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR')) {
                    require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
                } else {
                    ?><div class="notice notice-error is-dismissible">
                        <p><?php _e('PayPal library is not loaded!', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    </div>
                    <?php
                    die();
                }
            }
            if (!empty($PayPalConfig)) {
                $PayPal = new Angelleye_PayPal_WC($PayPalConfig);
                $PayPalResult = $PayPal->GetPalDetails();
                if (isset($PayPalResult['ACK']) && $PayPalResult['ACK'] == 'Success') {
                    if (isset($PayPalResult['PAL']) && !empty($PayPalResult['PAL'])) {
                        $merchant_account_id = $PayPalResult['PAL'];
                    }
                } else {
                    if (!empty($PayPalResult['L_ERRORCODE0']) && $PayPalResult['L_ERRORCODE0'] == '10002') {
                        ?>
                        <div class="notice notice-error is-dismissible">
                            <p><?php _e('The API credentials you have entered are not valid. Please double check your values and try again.  Note that sandbox and live credentials will be different, so make sure you are populating those accordingly.', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                        </div>
                        <?php
                        return false;
                    }
                    if (!empty($PayPalResult['L_LONGMESSAGE0'])) {
                        ?><div class="notice notice-error is-dismissible">
                            <p><?php _e($PayPalResult['L_LONGMESSAGE0'], 'paypal-for-woocommerce-multi-account-management'); ?></p>
                        </div>
                        <?php
                        return false;
                    } else {
                        if (!empty($PayPalResult['L_SHORTMESSAGE0'])) {
                            ?><div class="notice notice-error is-dismissible">
                                <p><?php _e($PayPalResult['L_SHORTMESSAGE0'], 'paypal-for-woocommerce-multi-account-management'); ?></p>
                            </div>
                            <?php
                            return false;
                        } else {
                            ?><div class="notice notice-error is-dismissible">
                                <p><?php _e('PayPal api credentials are incorrect.', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                            </div>
                            <?php
                            return false;
                        }
                    }
                }
            }
            $microprocessing_key_array = array('woocommerce_paypal_express_enable', 'woocommerce_paypal_express_testmode', 'woocommerce_paypal_express_account_name', 'woocommerce_paypal_express_sandbox_email', 'woocommerce_paypal_express_sandbox_api_username', 'woocommerce_paypal_express_sandbox_api_password', 'woocommerce_paypal_express_sandbox_api_signature', 'woocommerce_paypal_express_email', 'woocommerce_paypal_express_api_username', 'woocommerce_paypal_express_api_password', 'woocommerce_paypal_express_api_signature', 'woocommerce_paypal_express_api_condition_field', 'woocommerce_paypal_express_api_condition_sign', 'woocommerce_paypal_express_api_condition_value', 'woocommerce_paypal_express_api_user_role', 'woocommerce_paypal_express_api_user', 'woocommerce_paypal_express_api_product_ids', 'product_categories', 'product_tags', 'buyer_countries', 'woocommerce_priority', 'angelleye_multi_account_choose_payment_gateway', 'store_countries', 'shipping_class', 'currency_code', 'ec_site_owner_commission', 'ec_site_owner_commission_label');
            if (empty($_POST['is_edit'])) {
                $my_post = array(
                    'post_title' => wp_strip_all_tags($_POST['woocommerce_paypal_express_account_name']),
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id(),
                    'post_type' => 'microprocessing'
                );
                $post_id = wp_insert_post($my_post);
                do_action('update_angelleye_multi_account', $post_id);
            } else {
                $my_post = array(
                    'ID' => $_POST['is_edit'],
                    'post_title' => wp_strip_all_tags($_POST['woocommerce_paypal_express_account_name']),
                    'post_content' => '',
                );
                wp_update_post($my_post);
                $post_id = $_POST['is_edit'];
                do_action('update_angelleye_multi_account', $post_id);
            }
            foreach ($microprocessing_key_array as $index => $microprocessing_key) {
                if ($microprocessing_key == 'woocommerce_paypal_express_api_product_ids') {
                    $product_ids = isset($_POST['woocommerce_paypal_express_api_product_ids']) ? array_map('intval', (array) $_POST['woocommerce_paypal_express_api_product_ids']) : array();
                    update_post_meta($post_id, $microprocessing_key, $product_ids);
                } else {
                    if (!empty($_POST[$microprocessing_key])) {
                        update_post_meta($post_id, $microprocessing_key, is_array($_POST[$microprocessing_key]) ? $_POST[$microprocessing_key] : trim($_POST[$microprocessing_key]));
                    } else {
                        if ($microprocessing_key == 'woocommerce_paypal_express_api_condition_value') {
                            update_post_meta($post_id, $microprocessing_key, trim($_POST[$microprocessing_key]));
                        } else {
                            update_post_meta($post_id, $microprocessing_key, '');
                        }
                    }
                }
            }
            if (!empty($merchant_account_id)) {
                if (isset($_POST['woocommerce_paypal_express_testmode']) && 'on' == $_POST['woocommerce_paypal_express_testmode']) {
                    update_post_meta($post_id, 'woocommerce_paypal_express_sandbox_merchant_id', $merchant_account_id);
                } else {
                    update_post_meta($post_id, 'woocommerce_paypal_express_merchant_id', $merchant_account_id);
                }
            }
            ?>
            <?php
            if (!empty($_POST['is_edit'])) {
                $this->message = __('Your settings have been saved.', 'paypal-for-woocommerce-multi-account-management');
                wp_redirect(add_query_arg(array('action' => 'edit', 'ID' => $post_id, 'success' => true)));
                exit();
            } else {
                $this->message = __('Your settings have been saved.', 'paypal-for-woocommerce-multi-account-management');
                wp_redirect(add_query_arg(array('action' => 'edit', 'ID' => $post_id, 'success' => true)));
                exit();
            }
        }
    }

    public function angelleye_save_multi_account_data_paypal_pro_payflow() {
        if (!empty($_POST['microprocessing_save'])) {
            if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'microprocessing_save')) {
                die(__('Action failed. Please refresh the page and retry.', 'paypal-for-woocommerce-multi-account-management'));
            }
            if (!empty($_POST['woocommerce_paypal_pro_payflow_testmode']) && $_POST['woocommerce_paypal_pro_payflow_testmode'] == 'on') {
                if (empty($_POST['woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor']) && empty($_POST['woocommerce_paypal_pro_payflow_sandbox_api_password'])) {
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php _e('Sandbox API Username or Sandbox API Password or Sandbox API Signature empty!', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    </div>
                    <?php
                    return false;
                } else {
                    if (!empty($_POST['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user'])) {
                        $APIUsername = wc_clean(trim($_POST['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user']));
                    } else {
                        $APIUsername = wc_clean(trim($_POST['woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor']));
                    }
                    if (!empty($_POST['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner'])) {
                        $APIPartner = wc_clean(trim($_POST['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner']));
                    } else {
                        $APIPartner = 'PayPal';
                    }
                    $APIPassword = wc_clean(trim($_POST['woocommerce_paypal_pro_payflow_sandbox_api_password']));
                    $APIVendor = wc_clean(trim($_POST['woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor']));
                    $PayPalConfig = array(
                        'Sandbox' => true,
                        'APIUsername' => $APIUsername,
                        'APIPassword' => $APIPassword,
                        'APIVendor' => $APIVendor,
                        'APIPartner' => $APIPartner
                    );
                }
            } else {
                if (empty($_POST['woocommerce_paypal_pro_payflow_api_paypal_vendor']) && empty($_POST['woocommerce_paypal_pro_payflow_api_password'])) {
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php _e('API Username or API Password or API Signature empty!', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    </div>
                    <?php
                    return false;
                } else {
                    if (!empty($_POST['woocommerce_paypal_pro_payflow_api_paypal_user'])) {
                        $APIUsername = wc_clean(trim($_POST['woocommerce_paypal_pro_payflow_api_paypal_user']));
                    } else {
                        $APIUsername = wc_clean(trim($_POST['woocommerce_paypal_pro_payflow_api_paypal_vendor']));
                    }
                    if (!empty($_POST['woocommerce_paypal_pro_payflow_api_paypal_partner'])) {
                        $APIPartner = wc_clean(trim($_POST['woocommerce_paypal_pro_payflow_api_paypal_partner']));
                    } else {
                        $APIPartner = 'PayPal';
                    }
                    $APIPassword = wc_clean(trim($_POST['woocommerce_paypal_pro_payflow_api_password']));
                    $APIVendor = wc_clean(trim($_POST['woocommerce_paypal_pro_payflow_api_paypal_vendor']));
                    $PayPalConfig = array(
                        'Sandbox' => true,
                        'APIUsername' => $APIUsername,
                        'APIPassword' => $APIPassword,
                        'APIVendor' => $APIVendor,
                        'APIPartner' => $APIPartner
                    );
                }
            }
            try {
                if (!class_exists('Angelleye_PayPal_WC')) {
                    if (defined('PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR')) {
                        require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
                    } else {
                        ?><div class="notice notice-error is-dismissible">
                            <p><?php _e('PayPal library is not loaded!', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                        </div>
                        <?php
                        die();
                    }
                }
                if (!class_exists('Angelleye_PayPal_PayFlow')) {
                    require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.payflow.class.php' );
                }
                $PayPal = new Angelleye_PayPal_PayFlow($PayPalConfig);
            } catch (Exception $ex) {
                
            }

            $customer_id = get_current_user_id();
            $secure_token_id = uniqid(substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])), 0, 9), true);
            $billtofirstname = (get_user_meta($customer_id, 'billing_first_name', true)) ? get_user_meta($customer_id, 'billing_first_name', true) : get_user_meta($customer_id, 'shipping_first_name', true);
            $billtolastname = (get_user_meta($customer_id, 'billing_last_name', true)) ? get_user_meta($customer_id, 'billing_last_name', true) : get_user_meta($customer_id, 'shipping_last_name', true);
            $billtostate = (get_user_meta($customer_id, 'billing_state', true)) ? get_user_meta($customer_id, 'billing_state', true) : get_user_meta($customer_id, 'shipping_state', true);
            $billtocountry = (get_user_meta($customer_id, 'billing_country', true)) ? get_user_meta($customer_id, 'billing_country', true) : get_user_meta($customer_id, 'shipping_country', true);
            $billtozip = (get_user_meta($customer_id, 'billing_postcode', true)) ? get_user_meta($customer_id, 'billing_postcode', true) : get_user_meta($customer_id, 'shipping_postcode', true);
            $PayPalRequestData = array(
                'tender' => 'C',
                'trxtype' => 'A',
                'acct' => '',
                'expdate' => '',
                'amt' => '0.00',
                'currency' => get_woocommerce_currency(),
                'cvv2' => '',
                'orderid' => '',
                'orderdesc' => '',
                'billtoemail' => '',
                'billtophonenum' => '',
                'billtofirstname' => $billtofirstname,
                'billtomiddlename' => '',
                'billtolastname' => $billtolastname,
                'billtostreet' => '',
                'billtocity' => '',
                'billtostate' => $billtostate,
                'billtozip' => $billtozip,
                'billtocountry' => $billtocountry,
                'custref' => '',
                'custcode' => '',
                'custip' => WC_Geolocation::get_ip_address(),
                'invnum' => '',
                'ponum' => '',
                'starttime' => '',
                'endtime' => '',
                'securetoken' => '',
                'partialauth' => '',
                'authcode' => '',
                'SECURETOKENID' => $secure_token_id,
                'CREATESECURETOKEN' => 'Y',
            );
            $PayPalResult = $PayPal->ProcessTransaction($PayPalRequestData);

            if (isset($PayPalResult['RESULT']) && $PayPalResult['RESULT'] == 0) {
                
            } else {
                if (!empty($PayPalResult['L_ERRORCODE0']) && $PayPalResult['L_ERRORCODE0'] == '10002') {
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php _e('The API credentials you have entered are not valid. Please double check your values and try again.  Note that sandbox and live credentials will be different, so make sure you are populating those accordingly.', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    </div>
                    <?php
                    return false;
                }
                if (!empty($PayPalResult['L_LONGMESSAGE0'])) {
                    ?><div class="notice notice-error is-dismissible">
                        <p><?php _e($PayPalResult['L_LONGMESSAGE0'], 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    </div>
                    <?php
                    return false;
                } else {
                    if (!empty($PayPalResult['L_SHORTMESSAGE0'])) {
                        ?><div class="notice notice-error is-dismissible">
                            <p><?php _e($PayPalResult['L_SHORTMESSAGE0'], 'paypal-for-woocommerce-multi-account-management'); ?></p>
                        </div>
                        <?php
                        return false;
                    } else {
                        ?><div class="notice notice-error is-dismissible">
                            <p><?php _e('PayPal api credentials are incorrect.', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                        </div>
                        <?php
                        return false;
                    }
                }
            }
            $microprocessing_key_array = array('woocommerce_paypal_pro_payflow_enable', 'woocommerce_paypal_pro_payflow_testmode', 'woocommerce_paypal_pro_payflow_account_name', 'woocommerce_paypal_pro_payflow_api_paypal_partner', 'woocommerce_paypal_pro_payflow_api_paypal_vendor', 'woocommerce_paypal_pro_payflow_api_paypal_user', 'woocommerce_paypal_pro_payflow_api_password', 'woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner', 'woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor', 'woocommerce_paypal_pro_payflow_sandbox_api_paypal_user', 'woocommerce_paypal_pro_payflow_sandbox_api_password', 'woocommerce_paypal_express_api_condition_field', 'woocommerce_paypal_express_api_condition_sign', 'woocommerce_paypal_express_api_condition_value', 'woocommerce_paypal_express_api_user_role', 'woocommerce_paypal_express_api_user', 'woocommerce_paypal_express_api_product_ids', 'product_categories', 'product_tags', 'buyer_countries', 'woocommerce_priority', 'angelleye_multi_account_choose_payment_gateway', 'card_type', 'currency_code', 'store_countries', 'shipping_class');
            if (empty($_POST['is_edit'])) {
                $my_post = array(
                    'post_title' => wp_strip_all_tags($_POST['woocommerce_paypal_pro_payflow_account_name']),
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id(),
                    'post_type' => 'microprocessing'
                );
                $post_id = wp_insert_post($my_post);
                do_action('update_angelleye_multi_account', $post_id);
            } else {
                $my_post = array(
                    'ID' => $_POST['is_edit'],
                    'post_title' => wp_strip_all_tags($_POST['woocommerce_paypal_pro_payflow_account_name']),
                    'post_content' => '',
                );
                wp_update_post($my_post);
                $post_id = $_POST['is_edit'];
                do_action('update_angelleye_multi_account', $post_id);
            }
            foreach ($microprocessing_key_array as $index => $microprocessing_key) {
                if ($microprocessing_key == 'woocommerce_paypal_pro_payflow_api_product_ids') {
                    $product_ids = isset($_POST['woocommerce_paypal_pro_payflow_api_product_ids']) ? array_map('intval', (array) $_POST['woocommerce_paypal_pro_payflow_api_product_ids']) : array();
                    update_post_meta($post_id, $microprocessing_key, $product_ids);
                } else {
                    if (!empty($_POST[$microprocessing_key])) {
                        update_post_meta($post_id, $microprocessing_key, is_array($_POST[$microprocessing_key]) ? $_POST[$microprocessing_key] : trim($_POST[$microprocessing_key]));
                    } else {
                        if ($microprocessing_key == 'woocommerce_paypal_express_api_condition_value') {
                            update_post_meta($post_id, $microprocessing_key, trim($_POST[$microprocessing_key]));
                        } else {
                            update_post_meta($post_id, $microprocessing_key, '');
                        }
                    }
                }
            }
            ?>
            <?php
            if (!empty($_POST['is_edit'])) {
                $redirect_url = remove_query_arg(array('action', 'ID'));
                wp_redirect(add_query_arg('success', true, $redirect_url));
                exit();
            } else {
                $this->message = __('Your settings have been saved.', 'paypal-for-woocommerce-multi-account-management');
            }
        }
    }

    public function is_angelleye_multi_account_used($order_id) {
        if ($order_id > 0) {
            $_multi_account_api_username = get_post_meta($order_id, '_multi_account_api_username', true);
            if (!empty($_multi_account_api_username)) {
                return true;
            }
        }
        if (!class_exists('WooCommerce') || WC()->session == null) {
            return false;
        }
        $multi_account_api_username = WC()->session->get('multi_account_api_username');
        if (!empty($multi_account_api_username)) {
            return true;
        }

        return false;
    }

    public function angelleye_get_multi_account_api_user_name($order_id) {
        if ($order_id > 0) {
            $multi_account_api_username = get_post_meta($order_id, '_multi_account_api_username', true);
            if (!empty($multi_account_api_username)) {
                return $multi_account_api_username;
            }
        }
        $multi_account_api_username = WC()->session->get('multi_account_api_username');
        if (!empty($multi_account_api_username)) {
            return $multi_account_api_username;
        }
        return false;
    }

    public function angelleye_woocommerce_checkout_update_order_meta($order_id, $data) {
        $multi_account_api_username = WC()->session->get('multi_account_api_username');
        if (!empty($multi_account_api_username)) {
            update_post_meta($order_id, '_multi_account_api_username', $multi_account_api_username);
            unset(WC()->session->multi_account_api_username);
            WC()->session->get('multi_account_api_username', '');
            WC()->session->__unset('multi_account_api_username');
        }
    }

    public function angelleye_paypal_for_woocommerce_general_settings_tab() {
        $gateway = isset($_GET['gateway']) ? $_GET['gateway'] : 'paypal_payment_gateway_products';
        if (!class_exists('AngellEYE_Gateway_Paypal')) {
            $gateway = 'paypal_for_wooCommerce_for_multi_account_management';
        }
        ?>
        <a href="?page=wc-settings&tab=multi_account_management" class="nav-tab <?php echo $gateway == 'paypal_for_wooCommerce_for_multi_account_management' ? 'nav-tab-active' : ''; ?>"><?php echo __('Multi-Account Management', 'paypal-for-woocommerce-multi-account-management'); ?></a> <?php
    }

    public function display_admin_notice() {
        if (!empty($this->message)) {
            echo '<div id="message" class="updated inline is-dismissible"><p><strong>' . esc_html($this->message) . '</strong></p></div>';
        }
    }

    public function angelleye_paypal_for_woocommerce_general_settings_tab_content() {
        wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION);
        wp_enqueue_script('selectWoo');
        wp_enqueue_style('select2');
        wp_enqueue_script('wc-enhanced-select');
        wp_localize_script('paypal-for-woocommerce-multi-account-management', 'pfwma_param', array(
            'rule_with_no_condition_set_message' => __('You have not set any Trigger Conditions for this rule. Therefore, this rule will trigger for all orders from now on. Would you still like to continue?', 'paypal-for-woocommerce-multi-account-management'),
           )
        );       

        $this->angelleye_multi_account_ui();
    }

    public function update_session_data() {
        if (!class_exists('WooCommerce') || WC()->session == null) {
            return false;
        }
        $paypal_express_checkout = WC()->session->get('paypal_express_checkout');
        if (!isset($paypal_express_checkout)) {
            WC()->session->set('multi_account_api_username', '');
            WC()->session->__unset('multi_account_api_username');
        }
    }

    public function remove_session_data() {
        if (!class_exists('WooCommerce') || WC()->session == null) {
            return false;
        }
        WC()->session->set('multi_account_api_username', '');
        WC()->session->__unset('multi_account_api_username');
        WC()->session->set('angelleye_sandbox_payment_load_balancer_ec_email', '');
        WC()->session->__unset('angelleye_sandbox_payment_load_balancer_ec_email');
        WC()->session->set('angelleye_payment_load_balancer_ec_email', '');
        WC()->session->__unset('angelleye_payment_load_balancer_ec_email');
        WC()->session->set('angelleye_sandbox_payment_load_balancer_ec_account', '');
        WC()->session->__unset('angelleye_sandbox_payment_load_balancer_ec_account');
        WC()->session->set('angelleye_payment_load_balancer_ec_account', '');
        WC()->session->__unset('angelleye_payment_load_balancer_ec_account');

    }

    

    

    public function angelleye_multi_account_api_field_ui() {
        ?>
        <tr valign="top" class="angelleye_multi_account_paypal_express_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_express_enable"><?php echo __('Enable / Disable', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <label for="woocommerce_paypal_express_enable">
                        <input class="woocommerce_paypal_express_enable" name="woocommerce_paypal_express_enable" id="woocommerce_paypal_express_enable" type="checkbox"><?php echo __('Enable Account', 'paypal-for-woocommerce-multi-account-management'); ?> </label><br>
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_express_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_express_testmode_microprocessing"><?php echo __('PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <label for="woocommerce_paypal_express_testmode_microprocessing">
                        <input class="woocommerce_paypal_express_testmode" name="woocommerce_paypal_express_testmode" id="woocommerce_paypal_express_testmode_microprocessing" type="checkbox"><?php echo __('Enable PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'); ?> </label><br>
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_express_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_express_account_name_microprocessing"><?php echo __('Account Nickname', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Account Name/Label', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_account_name" id="woocommerce_paypal_express_account_name_microprocessing" style="" placeholder="" type="text">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_express_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_express_sandbox_email_microprocessing"><?php echo __('PayPal Email', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('PayPal Email', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_email" id="woocommerce_paypal_express_sandbox_email_microprocessing" style="" placeholder="you@youremail.com" type="email">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_express_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_express_sandbox_api_username_microprocessing"><?php echo __('Sandbox API Username', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Sandbox API Username', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_username" id="woocommerce_paypal_express_sandbox_api_username_microprocessing" style="" placeholder="Optional" type="text">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_express_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_express_sandbox_api_password_microprocessing"><?php echo __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_password" id="woocommerce_paypal_express_sandbox_api_password_microprocessing" style="" placeholder="Optional" type="password">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_express_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_express_sandbox_api_signature_microprocessing"><?php echo __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_signature" id="woocommerce_paypal_express_sandbox_api_signature_microprocessing" style="" placeholder="Optional" type="password">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_express_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_express_email_microprocessing"><?php echo __('PayPal Email', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('PayPal Email', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_email" id="woocommerce_paypal_express_email_microprocessing" style="" placeholder="you@youremail.com" type="email">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_express_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_express_api_username_microprocessing"><?php echo __('API Username', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('API Username', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_api_username" id="woocommerce_paypal_express_api_username_microprocessing" style="" placeholder="Optional" type="text">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_express_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_express_api_password_microprocessing"><?php echo __('API Password', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('API Password', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_api_password" id="woocommerce_paypal_express_api_password_microprocessing" style="" placeholder="Optional" type="password">
                </fieldset>
            </td>
        </tr>
        <tr class="angelleye_multi_account_paypal_express_field">
            <th scope="row" class="titledesc" >
                <label for="woocommerce_paypal_express_api_signature_microprocessing"><?php echo __('API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_api_signature" id="woocommerce_paypal_express_api_signature_microprocessing" style="" placeholder="Optional" type="password">
                </fieldset>
            </td>
        </tr>
        <?php
        $angelleye_payment_load_balancer = get_option('angelleye_payment_load_balancer', '');
        if ($angelleye_payment_load_balancer == '') :
            ?>

            <tr class="angelleye_multi_account_paypal_express_field">
                <th scope="row" class="titledesc" >
                    <label for="woocommerce_paypal_express_api_signature_microprocessing"><?php echo __('Site Owner Commission %', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php echo __('Site Owner Commission %', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                        <input type="number" name="ec_site_owner_commission" min="0" max="100" step="0.01" placeholder="0">
                    </fieldset>
                </td>
            </tr>
            <tr class="angelleye_multi_account_paypal_express_field">
                <th scope="row" class="titledesc" >
                    <label for="woocommerce_paypal_express_api_signature_microprocessing"><?php echo __('Site Owner Commission Item Label', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php echo __('Site Owner Commission Item Label', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                        <input type="text" class="input-text regular-input width460" name="ec_site_owner_commission_label" placeholder="Commission">
                    </fieldset>
                </td>
            </tr>
            <?php
        endif;
    }

    public function angelleye_multi_account_choose_payment_gateway() {
        $gateway_list = array();
        if (class_exists('AngellEYE_Gateway_Paypal')) {
            $gateway_list = array('paypal_express' => __('PayPal Express Checkout', ''), 'paypal_pro_payflow' => __('PayPal Payments Pro 2.0 (PayFlow)', ''));
            $angelleye_hidden = '';
        } else {
            //$gateway_list = array('paypal' => __('PayPal Standard', ''));
            $angelleye_hidden = '';
        }
        ?>
        <tr>
            <th><?php _e('Payment Gateway', 'paypal-for-woocommerce-multi-account-management'); ?></th>
            <td>

                <select class="wc-enhanced-select angelleye_multi_account_choose_payment_gateway" name="angelleye_multi_account_choose_payment_gateway" <?php echo $angelleye_hidden; ?>>
                    <?php
                    foreach ($gateway_list as $key => $details) {
                        echo "\n\t<option value='" . esc_attr($key) . "'>$details</option>";
                    }
                    ?>
                </select>
            </td>

        </tr>

        <?php
    }

    public function angelleye_multi_account_paypal_pro_payflow_api_field_ui() {
        ?>
        <tr valign="top" class="angelleye_multi_account_paypal_pro_payflow_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_pro_payflow_enable"><?php echo __('Enable / Disable', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <label for="woocommerce_paypal_pro_payflow_enable">
                        <input class="woocommerce_paypal_pro_payflow_enable" name="woocommerce_paypal_pro_payflow_enable" id="woocommerce_paypal_pro_payflow_enable" type="checkbox"><?php echo __('Enable Account', 'paypal-for-woocommerce-multi-account-management'); ?> </label><br>
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_pro_payflow_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_pro_payflow_testmode"><?php echo __('PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <label for="woocommerce_paypal_pro_payflow_testmode_microprocessing">
                        <input class="woocommerce_paypal_pro_payflow_testmode" name="woocommerce_paypal_pro_payflow_testmode" id="woocommerce_paypal_pro_payflow_testmode_microprocessing" type="checkbox"><?php echo __('Enable PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'); ?> </label><br>
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_pro_payflow_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_pro_payflow_account_name_microprocessing"><?php echo __('Account Nickname', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Account Name/Label', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_account_name" id="woocommerce_paypal_pro_payflow_account_name_microprocessing" style="" placeholder="" type="text">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_pro_payflow_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_pro_payflow_sandbox_paypal_partner_microprocessing"><?php echo __('Partner', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Partner', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner" id="woocommerce_paypal_pro_payflow_sandbox_paypal_partner_microprocessing" style="" placeholder="" type="text">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_pro_payflow_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor_microprocessing"><?php echo __('Vendor (Merchant Login)', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Vendor (Merchant Login)', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor" id="woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor_microprocessing" style="" placeholder="" type="text">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_pro_payflow_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_pro_payflow_sandbox_api_paypal_user_microprocessing"><?php echo __('User (optional)', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('User (optional)', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_sandbox_api_paypal_user" id="woocommerce_paypal_pro_payflow_sandbox_api_paypal_user_microprocessing" style="" placeholder="" type="text">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_pro_payflow_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_pro_payflow_sandbox_api_password_microprocessing"><?php echo __('Password', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Password', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_sandbox_api_password" id="woocommerce_paypal_pro_payflow_sandbox_api_password_microprocessing" style="" placeholder="" type="password">
                </fieldset>
            </td>
        </tr>

        <tr valign="top" class="angelleye_multi_account_paypal_pro_payflow_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_pro_payflow_paypal_partner_microprocessing"><?php echo __('Partner', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Partner', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_api_paypal_partner" id="woocommerce_paypal_pro_payflow_paypal_partner_microprocessing" style="" placeholder="" type="text">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_pro_payflow_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_pro_payflow_api_paypal_vendor_microprocessing"><?php echo __('Vendor (Merchant Login)', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Vendor (Merchant Login)', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_api_paypal_vendor" id="woocommerce_paypal_pro_payflow_api_paypal_vendor_microprocessing" style="" placeholder="" type="text">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_pro_payflow_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_pro_payflow_api_paypal_user_microprocessing"><?php echo __('User (optional)', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('User (optional)', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_api_paypal_user" id="woocommerce_paypal_pro_payflow_api_paypal_user_microprocessing" style="" placeholder="" type="text">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_pro_payflow_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_pro_payflow_api_password_microprocessing"><?php echo __('Password', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Password', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_pro_payflow_api_password" id="woocommerce_paypal_pro_payflow_api_password_microprocessing" style="" placeholder="" type="password">
                </fieldset>
            </td>
        </tr>

        <?php
    }

    public function angelleye_multi_account_condition_ui() {
        ?>
        <tr>
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_express_api_trigger_conditions"><?php echo __('Trigger Conditions', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset class="pfwma_section_ui">
                    <legend><?php echo __('Buyer Conditions', 'paypal-for-woocommerce-multi-account-management'); ?> </legend>
                    <p class="description"><?php _e('Buyer Role', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select class="wc-enhanced-select smart_forwarding_field" name="woocommerce_paypal_express_api_user_role" id="woocommerce_paypal_express_api_user_role">
                        <option value="all"><?php _e('All', 'paypal-for-woocommerce-multi-account-management'); ?></option>
                        <?php
                        $editable_roles = array_reverse(get_editable_roles());
                        foreach ($editable_roles as $role => $details) {
                            $name = translate_user_role($details['name']);
                            echo "\n\t<option value='" . esc_attr($role) . "'>$name</option>";
                        }
                        ?>
                    </select>
                    <p class="description"><?php _e('Buyer country', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select id="buyer_countries" name="buyer_countries[]" style="width: 78%;"  class="wc-enhanced-select" multiple="multiple"  data-placeholder="<?php esc_attr_e('All countries', 'paypal-for-woocommerce-multi-account-management'); ?>">
                        <?php
                        $countries = WC()->countries->get_countries();
                        if ($countries) {
                            foreach ($countries as $country_key => $country_full_name) {
                                echo "<option value='" . esc_attr($country_key) . "'>$country_full_name</option>";
                            }
                        }
                        ?>
                    </select>
                </fieldset>
                <fieldset class="pfwma_section_ui">
                    <legend><?php echo __('Seller Conditions', 'paypal-for-woocommerce-multi-account-management'); ?> </legend>
                    <p class="description"><?php _e('Seller/Product Author', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select id="woocommerce_paypal_express_api_user" class="wc-customer-search smart_forwarding_field" id="woocommerce_paypal_express_api_user" name="woocommerce_paypal_express_api_user" data-placeholder="<?php esc_attr_e('All', 'paypal-for-woocommerce-multi-account-management'); ?>" data-minimum_input_length="3" data-allow_clear="true">
                    </select>
                </fieldset>
                <fieldset class="pfwma_section_ui">
                    <legend><?php echo __('Common Conditions', 'paypal-for-woocommerce-multi-account-management'); ?> </legend>
                    <p class="description"><?php _e('Store country', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select id="store_countries" name="store_countries" style="width: 78%;"  class="wc-enhanced-select" data-placeholder="<?php esc_attr_e('All countries', 'paypal-for-woocommerce-multi-account-management'); ?>">
                        <?php
                        echo '<option value="0">All countries</option>';
                        if ($countries) {
                            foreach ($countries as $country_key => $country_full_name) {
                                echo '<option value="' . esc_attr($country_key) . '">' . esc_html($country_full_name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <?php if (wc_shipping_enabled()) { ?>
                        <p class="description"><?php _e('Shipping Class', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                        <select id="pfwst_shipping_class" name="shipping_class" style="width: 78%;"  class="wc-enhanced-select" data-placeholder="<?php esc_attr_e('All Shipping Class', 'paypal-for-woocommerce-multi-account-management'); ?>">
                            <?php
                            $classes = get_terms('product_shipping_class', array('hide_empty' => 1));
                            $shipping_classes = !is_wp_error($classes) ? $classes : array();
                            echo '<option value="all">All Shipping Class</option>';
                            if (!empty($shipping_classes)) {
                                foreach ($shipping_classes as $key => $shipping_classes_name) {
                                    echo '<option value="' . esc_attr($shipping_classes_name->term_id) . '">' . esc_html($shipping_classes_name->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    <?php } ?>
                    <p class="description"><?php echo apply_filters('angelleye_multi_account_display_category_label', __('Product categories', 'paypal-for-woocommerce-multi-account-management')); ?></p>
                    <select id="product_categories" name="product_categories[]" style="width: 78%;"  class="angelleye-category-search" multiple="multiple" data-placeholder="<?php esc_attr_e('Any category', 'paypal-for-woocommerce-multi-account-management'); ?>" data-allow_clear="true">
                    </select>
                    <?php ?>
                    <p class="description"><?php _e('Product tags', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select id="product_tags" name="product_tags[]" style="width: 78%;"  class="angelleye-product-tag-search" multiple="multiple" data-placeholder="<?php esc_attr_e('Any tag', 'paypal-for-woocommerce-multi-account-management'); ?>" data-action="angelleye_pfwma_get_product_tags"></select>
                    <p class="description"><?php echo apply_filters('angelleye_multi_account_display_products_label', __('Products', 'paypal-for-woocommerce-multi-account-management')); ?></p>
                    <select class="angelleye-product-search" style="width:203px;" multiple="multiple" id="product_list" name="woocommerce_paypal_express_api_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="angelleye_pfwma_get_products"></select>
                    <p class="description"><?php _e('Transaction Amount', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <input type="hidden" name="woocommerce_paypal_express_api_condition_field" value="transaction_amount">
                    <select class="smart_forwarding_field" name="woocommerce_paypal_express_api_condition_sign"><option value="greaterthan"><?php echo __('Greater than', 'paypal-for-woocommerce-multi-account-management'); ?></option><option value="lessthan"><?php echo __('Less than', 'paypal-for-woocommerce-multi-account-management'); ?></option><option value="equalto"><?php echo __('Equal to', 'paypal-for-woocommerce-multi-account-management'); ?></option></select>
                    <input class="input-text regular-input" name="woocommerce_paypal_express_api_condition_value" id="woocommerce_paypal_express_api_condition_value" type="number" min="0" max="1000" step="0.01" value="0">
                    <div class="angelleye_multi_account_paypal_pro_payflow_field">
                        <p class="description"><?php _e('Card Type', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                        <select class="wc-enhanced-select card_type" name="card_type" id="card_type">
                            <option value=""><?php _e('All', 'paypal-for-woocommerce-multi-account-management'); ?></option>
                            <?php
                            $card_type = array('visa' => 'Visa', 'amex' => 'American Express', 'mastercard' => 'MasterCard', 'discover' => 'Discover', 'maestro' => 'Maestro/Switch');
                            foreach ($card_type as $type => $card_name) {
                                echo "\n\t<option value='" . esc_attr($type) . "'>$card_name</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <p class="description"><?php _e('Currency Code', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select class="wc-enhanced-select currency_code" name="currency_code" id="currency_code">
                        <option value=""><?php _e('All', 'paypal-for-woocommerce-multi-account-management'); ?></option>
                        <?php
                        $currency_code_options = get_woocommerce_currencies();
                        foreach ($currency_code_options as $code => $name) {
                            $currency_code_options[$code] = $name . ' (' . get_woocommerce_currency_symbol($code) . ')';
                        }
                        foreach ($currency_code_options as $currency_code => $currency_code_name) {
                            echo "\n\t<option value='" . esc_attr($currency_code) . "'>$currency_code_name</option>";
                        }
                        ?>
                    </select>
                    <p class="description"><?php _e('Priority', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select class="wc-enhanced-select smart_forwarding_field" name="woocommerce_priority" id="woocommerce_priority">
                        <?php
                        for ($x = 0; $x <= 100; $x++) {
                            if ($x == 0) {
                                $woocommerce_priority_text = $x . ' - Lowest';
                            } elseif ($x == 100) {
                                $woocommerce_priority_text = $x . ' - Highest';
                            } else {
                                $woocommerce_priority_text = $x;
                            }
                            echo "\n\t<option value='" . $x . "'>$woocommerce_priority_text</option>";
                        }
                        ?>
                    </select>
                </fieldset>
            </td>
        </tr>
        <?php
    }

    public function angelleye_woocommerce_payment_successful_result($order_id) {
        $multi_account_api_username = WC()->session->get('multi_account_api_username');
        if (!empty($multi_account_api_username)) {
            update_post_meta($order_id, '_multi_account_api_username', $multi_account_api_username);
            unset(WC()->session->multi_account_api_username);
            WC()->session->get('multi_account_api_username', '');
            WC()->session->__unset('multi_account_api_username');
        }
    }

    public function angelleye_get_list_product_using_tag_cat($tag_list, $categories_list) {
        $_POST['tag_list'] = $tag_list;
        $_POST['categories_list'] = $categories_list;
        $all_products = array();
        $args = array(
            'post_type' => apply_filters('angelleye_multi_account_post_type', array('product')),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish',
        );

        if (!empty($tag_list) || !empty($categories_list)) {
            $args['tax_query'] = array();
            if (!empty($tag_list)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_tag',
                    'terms' => $tag_list,
                    'operator' => 'IN'
                );
            }
            if (!empty($categories_list)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_cat',
                    'terms' => $categories_list,
                    'operator' => 'IN',
                );
            }
            $args['tax_query'][] = array(
                'taxonomy' => 'product_type',
                'field' => 'slug',
                'terms' => array('grouped', 'external'),
                'operator' => 'NOT IN',
            );
        }

        $loop = new WP_Query(apply_filters('angelleye_get_products_by_product_cat_and_tags', $args));
        $all_products = array();
        if (!empty($loop->posts)) {
            foreach ($loop->posts as $key => $value) {
                $product_title = get_the_title($value);
                if (!empty($product_title)) {
                    $all_products[$value] = $product_title;
                }
            }
        }
        return $all_products;
    }

    public function angelleye_paypal_pro_payflow_amex_ca_usd($bool, $gateways) {
        $microprocessing_value = $this->angelleye_get_multi_account_by_order_total_latest(null, $gateways, null);
        if (count($microprocessing_value) >= 1) {
            if ($gateways->testmode == true) {
                if (!empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_password']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor'] && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner']))) {
                    $gateways->paypal_user = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user'];
                    $gateways->paypal_password = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_password'];
                    $gateways->paypal_vendor = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor'];
                    $gateways->paypal_partner = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner'];
                    WC()->session->set('multi_account_api_username', $gateways->paypal_user);
                    return false;
                }
            } else {
                if (!empty($microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_user']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_api_password']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_vendor']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner'])) {
                    $gateways->paypal_user = $microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_user'];
                    $gateways->paypal_password = $microprocessing_value['woocommerce_paypal_pro_payflow_api_password'];
                    $gateways->paypal_vendor = $microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_vendor'];
                    $gateways->paypal_partner = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner'];
                    WC()->session->set('multi_account_api_username', $gateways->paypal_user);
                    return false;
                }
            }
        } else {
            return true;
        }
    }

    public function card_type_from_account_number($account_number) {
        $types = array(
            'visa' => '/^4/',
            'mastercard' => '/^5[1-5]/',
            'amex' => '/^3[47]/',
            'discover' => '/^(6011|65|64[4-9]|622)/',
            'diners' => '/^(36|38|30[0-5])/',
            'jcb' => '/^35/',
            'maestro' => '/^(5018|5020|5038|6304|6759|676[1-3])/',
            'laser' => '/^(6706|6771|6709)/',
        );
        foreach ($types as $type => $pattern) {
            if (1 === preg_match($pattern, $account_number)) {
                return $type;
            }
        }
        return null;
    }

    public function angelleye_paypal_for_woocommerce_multi_account_display_push_notification() {
        global $current_user;
        $user_id = $current_user->ID;
        if (false === ( $response = get_transient('angelleye_multi_account_push_notification_result') )) {
            $response = $this->angelleye_get_push_notifications();
            if (is_object($response)) {
                set_transient('angelleye_multi_account_push_notification_result', $response, 12 * HOUR_IN_SECONDS);
            }
        }
        if (is_object($response)) {
            foreach ($response->data as $key => $response_data) {
                if (!get_user_meta($user_id, $response_data->id)) {
                    $this->angelleye_display_push_notification($response_data);
                }
            }
        }
    }

    public function angelleye_get_push_notifications() {
        $args = array(
            'plugin_name' => 'paypal-for-woocommerce-multi-account-management',
        );
        $api_url = PAYPAL_FOR_WOOCOMMERCE_PUSH_NOTIFICATION_WEB_URL . '?Wordpress_Plugin_Notification_Sender';
        $api_url .= '&action=angelleye_get_plugin_notification';
        $request = wp_remote_post($api_url, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('user-agent' => 'AngellEYE'),
            'body' => $args,
            'cookies' => array(),
            'sslverify' => false
        ));
        if (is_wp_error($request) or wp_remote_retrieve_response_code($request) != 200) {
            return false;
        }
        if ($request != '') {
            $response = json_decode(wp_remote_retrieve_body($request));
        } else {
            $response = false;
        }
        return $response;
    }

    public function angelleye_display_push_notification($response_data) {
        echo '<div class="notice notice-success angelleye-notice" style="display:none;" id="' . $response_data->id . '">'
        . '<div class="angelleye-notice-logo-push"><span> <img src="' . $response_data->ans_company_logo . '"> </span></div>'
        . '<div class="angelleye-notice-message">'
        . '<h3>' . $response_data->ans_message_title . '</h3>'
        . '<div class="angelleye-notice-message-inner">'
        . '<p>' . $response_data->ans_message_description . '</p>'
        . '<div class="angelleye-notice-action"><a target="_blank" href="' . $response_data->ans_button_url . '" class="button button-primary">' . $response_data->ans_button_label . '</a></div>'
        . '</div>'
        . '</div>'
        . '<div class="angelleye-notice-cta">'
        . '<button class="angelleye-notice-dismiss angelleye-dismiss-welcome" data-msg="' . $response_data->id . '">Dismiss</button>'
        . '</div>'
        . '</div>';
    }

    public function angelleye_paypal_for_woocommerce_multi_account_adismiss_notice() {
        global $current_user;
        $user_id = $current_user->ID;
        if (!empty($_POST['action']) && $_POST['action'] == 'angelleye_paypal_for_woocommerce_multi_account_adismiss_notice') {
            add_user_meta($user_id, wc_clean($_POST['data']), 'true', true);
            wp_send_json_success();
        }
    }

    public function angelleye_set_multi_account($token_id, $order_id) {
        if (!empty($token_id)) {
            $_multi_account_api_username = get_metadata('payment_token', $token_id, '_multi_account_api_username');
            if (!empty($_multi_account_api_username)) {
                if (!class_exists('WooCommerce') || WC()->session == null) {
                    update_post_meta($order_id, '_multi_account_api_username', $_multi_account_api_username);
                } else {
                    WC()->session->set('multi_account_api_username', $_multi_account_api_username);
                }
            }
        }
    }

    public function angelleye_add_screen_option() {
        $angelleye_multi_account_item_per_page_default = 10;
        $screen = get_current_screen();
        $current_user_id = get_current_user_id();
        $angelleye_multi_account_item_per_page_value = get_user_meta($current_user_id, 'angelleye_multi_account_item_per_page', true);
        if ($angelleye_multi_account_item_per_page_value) {
            $angelleye_multi_account_item_per_page_default = $angelleye_multi_account_item_per_page_value;
        }

        if (is_object($screen) && !empty($screen->id) && $screen->id == "settings_page_paypal-for-woocommerce" && !empty($_GET['gateway']) && 'paypal_for_wooCommerce_for_multi_account_management' == $_GET['gateway']) {
            $args = array(
                'label' => __('Number of items per page', 'pippin'),
                'default' => $angelleye_multi_account_item_per_page_default,
                'option' => 'angelleye_multi_account_item_per_page'
            );
            add_screen_option('per_page', $args);
        }
    }

    public function angelleye_set_screen_option($bool, $option, $value) {
        if ($option == "angelleye_multi_account_item_per_page") {
            $current_user_id = get_current_user_id();
            update_user_meta($current_user_id, 'angelleye_multi_account_item_per_page', $value);
        }
        return $bool;
    }

    public function angelleye_multi_account_api_paypal_field_ui() {
        ?>
        <tr valign="top" class="angelleye_multi_account_paypal_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_enable"><?php echo __('Enable / Disable', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <label for="woocommerce_paypal_enable">
                        <input class="woocommerce_paypal_enable" name="woocommerce_paypal_enable" id="woocommerce_paypal_enable" type="checkbox"><?php echo __('Enable Account', 'paypal-for-woocommerce-multi-account-management'); ?> </label><br>
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_testmode"><?php echo __('PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <label for="woocommerce_paypal_testmode_microprocessing">
                        <input class="woocommerce_paypal_testmode" name="woocommerce_paypal_testmode" id="woocommerce_paypal_testmode_microprocessing" type="checkbox"><?php echo __('Enable PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'); ?> </label><br>
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_account_name_microprocessing"><?php echo __('Account Nickname', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Account Name/Label', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_account_name" id="woocommerce_paypal_account_name_microprocessing" style="" placeholder="" type="text">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_sandbox_email"><?php echo __('PayPal Email', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('PayPal Email', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_sandbox_email" id="woocommerce_paypal_sandbox_email_microprocessing" style="" placeholder="you@youremail.com" type="email">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_sandbox_api_username_microprocessing"><?php echo __('Sandbox API Username', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Sandbox API Username', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_sandbox_api_username" id="woocommerce_paypal_sandbox_api_username_microprocessing" style="" placeholder="Optional" type="text">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_sandbox_api_password_microprocessing"><?php echo __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_sandbox_api_password" id="woocommerce_paypal_sandbox_api_password_microprocessing" style="" placeholder="Optional" type="password">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_sandbox_api_signature_microprocessing"><?php echo __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_sandbox_api_signature" id="woocommerce_paypal_sandbox_api_signature_microprocessing" style="" placeholder="Optional" type="password">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_email_microprocessing"><?php echo __('PayPal Email', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('PayPal Email', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_email" id="woocommerce_paypal_email_microprocessing" style="" placeholder="you@youremail.com" type="email">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_api_username_microprocessing"><?php echo __('API Username', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('API Username', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_api_username" id="woocommerce_paypal_api_username_microprocessing" style="" placeholder="Optional" type="text">
                </fieldset>
            </td>
        </tr>
        <tr valign="top" class="angelleye_multi_account_paypal_field">
            <th scope="row" class="titledesc">
                <label for="woocommerce_paypal_api_password_microprocessing"><?php echo __('API Password', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('API Password', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_api_password" id="woocommerce_paypal_api_password_microprocessing" style="" placeholder="Optional" type="password">
                </fieldset>
            </td>
        </tr>
        <tr class="angelleye_multi_account_paypal_field">
            <th scope="row" class="titledesc" >
                <label for="woocommerce_paypal_api_signature_microprocessing"><?php echo __('API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_api_signature" id="woocommerce_paypal_api_signature_microprocessing" style="" placeholder="Optional" type="password">
                </fieldset>
            </td>
        </tr>
        <?php
    }

    public function angelleye_save_multi_account_data_paypal() {
        if (!empty($_POST['microprocessing_save'])) {
            if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'microprocessing_save')) {
                die(__('Action failed. Please refresh the page and retry.', 'paypal-for-woocommerce-multi-account-management'));
            }
            $microprocessing_key_array = array('woocommerce_paypal_enable', 'woocommerce_paypal_testmode', 'woocommerce_paypal_account_name', 'woocommerce_paypal_sandbox_email', 'woocommerce_paypal_sandbox_api_username', 'woocommerce_paypal_sandbox_api_password', 'woocommerce_paypal_sandbox_api_signature', 'woocommerce_paypal_email', 'woocommerce_paypal_api_username', 'woocommerce_paypal_api_password', 'woocommerce_paypal_api_signature', 'woocommerce_paypal_express_api_condition_field', 'woocommerce_paypal_express_api_condition_sign', 'woocommerce_paypal_express_api_condition_value', 'woocommerce_paypal_express_api_user_role', 'woocommerce_paypal_express_api_user', 'woocommerce_paypal_express_api_product_ids', 'product_categories', 'product_tags', 'buyer_countries', 'woocommerce_priority', 'angelleye_multi_account_choose_payment_gateway', 'store_countries', 'shipping_class', 'currency_code');
            if (empty($_POST['is_edit'])) {
                $my_post = array(
                    'post_title' => wp_strip_all_tags($_POST['woocommerce_paypal_account_name']),
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id(),
                    'post_type' => 'microprocessing'
                );
                $post_id = wp_insert_post($my_post);
                do_action('update_angelleye_multi_account', $post_id);
            } else {
                $my_post = array(
                    'ID' => $_POST['is_edit'],
                    'post_title' => wp_strip_all_tags($_POST['woocommerce_paypal_account_name']),
                    'post_content' => '',
                );
                wp_update_post($my_post);
                $post_id = $_POST['is_edit'];
            }
            foreach ($microprocessing_key_array as $index => $microprocessing_key) {
                if ($microprocessing_key == 'woocommerce_paypal_api_product_ids') {
                    $product_ids = isset($_POST['woocommerce_paypal_api_product_ids']) ? array_map('intval', (array) $_POST['woocommerce_paypal_api_product_ids']) : array();
                    update_post_meta($post_id, $microprocessing_key, $product_ids);
                } else {
                    if (!empty($_POST[$microprocessing_key])) {
                        update_post_meta($post_id, $microprocessing_key, is_array($_POST[$microprocessing_key]) ? $_POST[$microprocessing_key] : trim($_POST[$microprocessing_key]));
                    } else {
                        if ($microprocessing_key == 'woocommerce_paypal_api_condition_value') {
                            update_post_meta($post_id, $microprocessing_key, trim($_POST[$microprocessing_key]));
                        } else {
                            update_post_meta($post_id, $microprocessing_key, '');
                        }
                    }
                }
            }
            if (!empty($merchant_account_id)) {
                if (isset($_POST['woocommerce_paypal_testmode']) && 'on' == $_POST['woocommerce_paypal_testmode']) {
                    update_post_meta($post_id, 'woocommerce_paypal_sandbox_merchant_id', $merchant_account_id);
                } else {
                    update_post_meta($post_id, 'woocommerce_paypal_merchant_id', $merchant_account_id);
                }
            }
            ?>
            <?php
            if (!empty($_POST['is_edit'])) {
                $redirect_url = remove_query_arg(array('action', 'ID'));
                wp_redirect(add_query_arg('success', true, $redirect_url));
                exit();
            } else {
                $this->message = __('Your settings have been saved.', 'paypal-for-woocommerce-multi-account-management');
            }
        }
    }

    public function angelleye_multi_account_get_count_active_vendor() {
        $args = array(
            'post_type' => 'microprocessing',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'vendor_id',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'woocommerce_paypal_express_enable',
                    'value' => 'on',
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        );
        $query = new WP_Query($args);
        if (!empty($query->found_posts) && $query->found_posts > 0) {
            return $query->found_posts;
        }
        return false;
    }

    public function angelleye_multi_account_get_count_deactive_vendor() {
        $args = array(
            'post_type' => 'microprocessing',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'vendor_id',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'woocommerce_paypal_express_enable',
                    'value' => '',
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        );
        $query = new WP_Query($args);
        $query = new WP_Query($args);
        if (!empty($query->found_posts) && $query->found_posts > 0) {
            return $query->found_posts;
        }
        return false;
    }

    public function angelleye_multi_account_disable_active_vendor_account() {
        $args = array(
            'post_type' => 'microprocessing',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'vendor_id',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'woocommerce_paypal_express_enable',
                    'value' => 'on',
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        );
        $query = new WP_Query($args);
        if (!empty($query->found_posts) && $query->found_posts > 0) {
            foreach ($query->posts as $key => $post_id) {
                update_post_meta($post_id, 'woocommerce_paypal_express_enable', '');
            }
        }
        return $query->found_posts;
    }

    public function angelleye_multi_account_enable_active_vendor_account() {
        $args = array(
            'post_type' => 'microprocessing',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'vendor_id',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'woocommerce_paypal_express_enable',
                    'value' => '',
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        );
        $query = new WP_Query($args);
        if (!empty($query->found_posts) && $query->found_posts > 0) {
            foreach ($query->posts as $key => $post_id) {
                update_post_meta($post_id, 'woocommerce_paypal_express_enable', 'on');
            }
        }
        return $query->found_posts;
    }

    public function angelleye_pfwma_disable_all_vendor_rules() {
        $update_count = $this->angelleye_multi_account_disable_active_vendor_account();
        $redirect_url = admin_url('admin.php?page=wc-settings&tab=multi_account_management&section&pfwma_processed=' . $update_count);
        echo $redirect_url;
        exit();
    }

    public function angelleye_pfwma_enable_all_vendor_rules() {
        $update_count = $this->angelleye_multi_account_enable_active_vendor_account();
        $redirect_url = admin_url('admin.php?page=wc-settings&tab=multi_account_management&section&pfwma_processed=' . $update_count);
        echo $redirect_url;
        exit();
    }

    public function angelleye_pfwma_display_notice() {
        $pfwma_processed = (isset($_GET['pfwma_processed']) ) ? $_GET['pfwma_processed'] : FALSE;
        if ($pfwma_processed) {
            $this->message = __('Action completed; ', 'paypal-for-woocommerce-multi-account-management') . sprintf(_n('%s record ', '%s records ', $pfwma_processed, 'paypal-for-woocommerce-multi-account-management'), $pfwma_processed) . __('processed.', 'paypal-for-woocommerce-multi-account-management');
        }
    }

    public function angelleye_pfwma_display_marketing_sidebar() {
        if (false === ( $html = get_transient('angelleye_dynamic_marketing_sidebar_html_pfwma') )) {
            $response = wp_remote_get('https://8aystwpoqi.execute-api.us-east-2.amazonaws.com/AngellEyeDynamicSidebar?pluginId=18');
            if (is_array($response) && !is_wp_error($response)) {
                if (!empty($response['body'])) {
                    set_transient('angelleye_dynamic_marketing_sidebar_html_pfwma', $response['body'], 24 * HOUR_IN_SECONDS);
                    echo $response['body'];
                }
            }
        } else {
            echo $html;
        }
    }

    public function angelleye_pfwma_add_deactivation_form() {
        $current_screen = get_current_screen();
        if ('plugins' !== $current_screen->id && 'plugins-network' !== $current_screen->id) {
            return;
        }
        include_once ( PFWMA_PLUGIN_DIR . '/template/deactivation-form.php');
    }

    public function angelleye_pfwma_plugin_deactivation_request() {
        $log_url = wc_clean($_SERVER['HTTP_HOST']);
        $log_plugin_id = 18;
        $web_services_url = 'http://www.angelleye.com/web-services/wordpress/update-plugin-status.php';
        $request_url = add_query_arg(array(
            'url' => $log_url,
            'plugin_id' => $log_plugin_id,
            'activation_status' => 0,
            'reason' => wc_clean($_POST['reason']),
            'reason_details' => wc_clean($_POST['reason_details']),
                ), $web_services_url);
        $response = wp_remote_request($request_url);
        update_option('angelleye_pfwma_submited_feedback', 'yes');
        if (is_wp_error($response)) {
            wp_send_json(wp_remote_retrieve_body($response));
        } else {
            wp_send_json(wp_remote_retrieve_body($response));
        }
    }

    public function own_update_angelleye_multi_account() {
        delete_transient('angelleye_multi_ec_payment_load_balancer_synce');
        delete_transient('angelleye_multi_ec_payment_load_balancer_synce_sandbox');
        delete_transient('angelleye_multi_payflow_payment_load_balancer_synce');
        delete_transient('angelleye_multi_payflow_payment_load_balancer_synce_sandbox');
    }

    public function angelleye_delete_multi_account($id) {
        global $wpdb;
        $post_types_to_delete = 'microprocessing';
        $post_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_author = %d AND post_type IN ('$post_types_to_delete')", $id));
        if ($post_ids) {
            foreach ($post_ids as $post_id) {
                wp_delete_post($post_id, true);
            }
        }
        $post_id = angelleye_is_vendor_account_exist($id);
        if ($post_id != false) {
            wp_delete_post($post_id, true);
        }
        $multi_accounts = angelleye_get_user_multi_accounts($id);
        if(!empty($multi_accounts)) {
            foreach ($multi_accounts as $key => $account_id) {
                 wp_delete_post($account_id, true);
            }
        }
        $user = get_user_by('id', $id);
        if(isset($user) && isset($user->user_email) && !empty($user->user_email)) {
            $multi_account_by_email = angelleye_get_user_multi_accounts_by_paypal_email($user->user_email);
            if(!empty($multi_account_by_email)) {
                foreach ($multi_account_by_email as $key => $account_id) {
                     wp_delete_post($account_id, true);
                }
            }
        }
        $this->own_update_angelleye_multi_account();
    }
    
    public function angelleye_pfwma_get_products() {
        ob_start();
        $args = array(
            'post_type' => apply_filters('angelleye_multi_account_post_type', array('product')),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => array('grouped', 'external'),
                    'operator' => 'NOT IN',
                )
            )
        );
        if( isset($_GET['term']) && !empty($_GET['term'])) {
            $args['s'] = wc_clean($_GET['term']);
        }
        if (isset($_GET['author']) && $_GET['author'] != 'all' && !empty($_GET['author'])) {
            $args['author'] = $_GET['author'];
        }
        if (isset($_GET['shipping_class']) && $_GET['shipping_class'] != 'all' && !empty($_GET['shipping_class'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'product_shipping_class',
                'terms' => $_GET['shipping_class'],
                'operator' => 'IN',
            );
        }
        if (!empty($_GET['tag_list']) || !empty($_GET['categories_list'])) {
            if (!empty($_GET['tag_list'])) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_tag',
                    'terms' => $_GET['tag_list'],
                    'operator' => 'IN'
                );
            }
            if (!empty($_GET['categories_list'])) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_cat',
                    'terms' => $_GET['categories_list'],
                    'operator' => 'IN',
                );
            }
        }
        $loop = new WP_Query(apply_filters('angelleye_get_products_by_product_cat_and_tags', $args));
        $all_products = array();
        if (!empty($loop->posts)) {
            foreach ($loop->posts as $key => $value) {
                $product_title = get_the_title($value);
                if (!empty($product_title)) {
                    $all_products[$value] = $product_title;
                }
            }
        }
        wp_send_json($all_products);
    }
    
    public function angelleye_pfwma_get_product_tags() {
        $deep_condition = 0;
        ob_start();
        $args = array(
            'post_type' => apply_filters('angelleye_multi_account_post_type', array('product')),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => array('grouped', 'external'),
                    'operator' => 'NOT IN',
                )
            )
        );
        if (isset($_GET['author']) && $_GET['author'] != 'all' && !empty($_GET['author'])) {
            $args['author'] = $_GET['author'];
            $deep_condition = $deep_condition + 1;
        }
        if (isset($_GET['shipping_class']) && $_GET['shipping_class'] != 'all' && !empty($_GET['shipping_class'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'product_shipping_class',
                'terms' => $_GET['shipping_class'],
                'operator' => 'IN',
            );
            $deep_condition = $deep_condition + 1;
        }
        if (!empty($_GET['categories_list'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'terms' => $_GET['categories_list'],
                'operator' => 'IN',
            );
            $deep_condition = $deep_condition + 1;
        }
        $all_tags = array();
        $search_text = isset($_GET['term']) ? wc_clean(wp_unslash($_GET['term'])) : '';
        if (!$search_text) {
            wp_die();
        }
        if($deep_condition > 0)  {
            $loop = new WP_Query(apply_filters('angelleye_get_products_and_tags_by_product_cat', $args));
            if (!empty($loop->posts)) {
                foreach ($loop->posts as $key => $value) {
                    $terms = get_the_terms($value, 'product_tag');
                    if (!empty($terms)) {
                        foreach ($terms as $terms_key => $terms_value) {
                            if ($terms_value->count > 0) {
                                if (strpos(strtolower($terms_value->name), strtolower($search_text)) !== false) {
                                    $all_tags[$terms_value->term_id] = $terms_value->name;
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $args = array(
                'taxonomy' => array('product_tag'),
                'orderby' => 'id',
                'order' => 'ASC',
                'hide_empty' => true,
                'fields' => 'all',
                'name__like' => $search_text,
            );
            $terms = get_terms($args);
            if ($terms) {
                foreach ($terms as $term) {
                    $term->formatted_name = '';
                    if ($term->parent) {
                        $ancestors = array_reverse(get_ancestors($term->term_id, 'product_tag'));
                        foreach ($ancestors as $ancestor) {
                            $ancestor_term = get_term($ancestor, 'product_cat');
                            if ($ancestor_term) {
                                $term->formatted_name .= $ancestor_term->name . ' > ';
                            }
                        }
                    }
                    $term->formatted_name .= $term->name;
                    $all_tags[$term->term_id] = $term->formatted_name;
                }
            }
        }
        
        wp_send_json($all_tags);
    }
    
    public function angelleye_pfwma_get_categories() {
        ob_start();

        check_ajax_referer('search-categories', 'security');

        if (!current_user_can('edit_products')) {
            wp_die(-1);
        }

        $search_text = isset($_GET['term']) ? wc_clean(wp_unslash($_GET['term'])) : '';

        if (!$search_text) {
            wp_die();
        }

        $found_categories = array();
        $args = array(
            'taxonomy' => array('product_cat'),
            'orderby' => 'id',
            'order' => 'ASC',
            'hide_empty' => true,
            'fields' => 'all',
            'name__like' => $search_text,
        );

        $terms = get_terms($args);

        if ($terms) {
            foreach ($terms as $term) {
                $term->formatted_name = '';

                if ($term->parent) {
                    $ancestors = array_reverse(get_ancestors($term->term_id, 'product_cat'));
                    foreach ($ancestors as $ancestor) {
                        $ancestor_term = get_term($ancestor, 'product_cat');
                        if ($ancestor_term) {
                            $term->formatted_name .= $ancestor_term->name . ' > ';
                        }
                    }
                }

                $term->formatted_name .= $term->name;
                $found_categories[$term->term_id] = $term->formatted_name;
            }
        }

        wp_send_json(apply_filters('woocommerce_json_search_found_categories', $found_categories));
    }
}
