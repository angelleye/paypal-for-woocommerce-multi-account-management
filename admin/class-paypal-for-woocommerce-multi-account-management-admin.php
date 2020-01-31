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
    public function enqueue_scripts() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        wp_register_script('jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array('jquery'), '2.70', true);
        wp_enqueue_script('jquery-blockui');
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/paypal-for-woocommerce-multi-account-management-admin.js', array('jquery'), $this->version, true);
    }

    public function angelleye_post_exists($id) {
        return is_string(get_post_status($id));
    }

    public function angelleye_display_multi_account_list() {
        $this->gateway_key = 'paypal_express';
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
        echo '<br/><div class="angelleye_multi_account_left"><form method="post" id="mainform" action="" enctype="multipart/form-data"><table class="form-table">
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
                    echo sprintf('<tr><th>%1$s</th><td><select class="angelleye_multi_account_choose_payment_gateway wc-enhanced-select" name="angelleye_multi_account_choose_payment_gateway">%2$s</select></td></tr>', __('Select Payment Gateway', ''), $gateway_option_Selected);
                }
            }
        } else {
            $gateway_option_Selected = "<option value='paypal_express'>PayPal Express Checkout</option>";
            echo sprintf('<tr><th>%1$s</th><td><select class="wc-enhanced-select angelleye_multi_account_choose_payment_gateway" name="angelleye_multi_account_choose_payment_gateway">%2$s</select></td></tr>', __('Select Payment Gateway', ''), $gateway_option_Selected);
        }

        if ($this->gateway_key == 'paypal_express') {
            $microprocessing_new = array();
            $microprocessing_key_array = array('woocommerce_paypal_express_enable', 'woocommerce_paypal_express_testmode', 'woocommerce_paypal_express_account_name', 'woocommerce_paypal_express_sandbox_email', 'woocommerce_paypal_express_sandbox_merchant_id', 'woocommerce_paypal_express_sandbox_api_username', 'woocommerce_paypal_express_sandbox_api_password', 'woocommerce_paypal_express_sandbox_api_signature', 'woocommerce_paypal_express_email', 'woocommerce_paypal_express_merchant_id', 'woocommerce_paypal_express_api_username', 'woocommerce_paypal_express_api_password', 'woocommerce_paypal_express_api_signature', 'woocommerce_paypal_express_api_condition_field', 'woocommerce_paypal_express_api_condition_sign', 'woocommerce_paypal_express_api_condition_value', 'woocommerce_paypal_express_api_user_role', 'woocommerce_paypal_express_api_product_ids', 'product_categories', 'product_tags', 'buyer_countries', 'woocommerce_priority', 'angelleye_multi_account_choose_payment_gateway', 'store_countries', 'currency_code', 'ec_site_owner_commission', 'ec_site_owner_commission_label');
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
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_merchant_id_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_merchant_id" value="%2$s" id="woocommerce_paypal_express_sandbox_merchant_id_microprocessing" style="" placeholder="" type="text" readonly></fieldset></td></tr>', __('Merchant Account ID', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
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
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_merchant_id_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_merchant_id" value="%2$s" id="woocommerce_paypal_express_merchant_id_microprocessing" style="" placeholder="" type="text" readonly></fieldset></td></tr>', __('Merchant Account ID', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
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
                        echo sprintf('<tr><th scope="row" class="titledesc"><label for="ec_site_owner_commission">%1$s</label></th><td class="forminp"><fieldset><input type="number" placeholder="0" name="ec_site_owner_commission" min="0" max="100" step="0.01" value="%2$s" id="ec_site_owner_commission"></fieldset></td></tr>', __('Site Owner Commission %', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                     case 'ec_site_owner_commission_label':
                        echo sprintf('<tr><th scope="row" class="titledesc"><label for="ec_site_owner_commission_label">%1$s</label></th><td class="forminp"><fieldset><input type="text" class="input-text regular-input width460" name="ec_site_owner_commission_label" value="%2$s" id="ec_site_owner_commission_label" placeholder="Commission"></fieldset></td></tr>', __('Site Owner Commission Item Label', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_api_user_role':
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
                }
            }
        }
        $option_one = __('Trigger Conditions', 'paypal-for-woocommerce-multi-account-management');
        $option_two = 'transaction_amount';
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
        $option_five = '<p class="description">' . __('Select User Role', 'paypal-for-woocommerce-multi-account-management') . '</p>';
        $option_five .= '<select class="wc-enhanced-select smart_forwarding_field" name="woocommerce_paypal_express_api_user_role">';
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
        $option_ten = '<p class="description">' . __('Select Priority', 'paypal-for-woocommerce-multi-account-management') . '</p>';
        $option_ten .= '<select class="wc-enhanced-select smart_forwarding_field" name="woocommerce_priority">';
        for ($x = 0; $x <= 100; $x++) {
            if($x == 0) {
                $woocommerce_priority_text = $x .' - Lowest';
            } elseif($x == 100) {
                $woocommerce_priority_text = $x .' - Highest';
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
        if(!isset($buyer_countries)) {
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
        $option_eight = '<p class="description"> ' . apply_filters('angelleye_multi_account_display_category_label',__('Product categories', 'paypal-for-woocommerce-multi-account-management')) . '</p>';
        $option_eight .= '<select id="product_categories" name="product_categories[]" style="width: 78%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="' . __('Any category', 'paypal-for-woocommerce-multi-account-management') . '">';
        $categories = get_terms(apply_filters('angelleye_get_product_categories', 
                                    array('product_cat')), array(
					'hide_empty' => 1,
					'orderby'    => 'name',
				));
        if(!isset($product_categories)) {
            $product_categories = array();
        }
        if ($categories) {
            foreach ($categories as $cat) {
                $category_lable = '';
                $taxonomy_obj = get_taxonomy( $cat->taxonomy );
                if(isset($taxonomy_obj->label) & !empty($taxonomy_obj->label)) {
                    $category_lable = $cat->name . ' (' . $taxonomy_obj->label . ')';
                } else {
                    $category_lable = $cat->name;
                }
                $option_eight .= '<option value="' . esc_attr($cat->term_id) . '"' . wc_selected($cat->term_id, $product_categories) . '>' . esc_html($category_lable) . '</option>';
            }
        }
        $option_eight .= '</select>';
        $option_nine = '<p class="description">' . __('Product tags', 'paypal-for-woocommerce-multi-account-management') . '</p>';
        $option_nine .= '<select id="product_tags" name="product_tags[]" style="width: 78%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="' . __('Any tag', 'paypal-for-woocommerce-multi-account-management') . '">';
        if( empty($product_categories) ) {
            $tags = get_terms('product_tag', 'orderby=name&hide_empty=1');
            if ($tags) {
                foreach ($tags as $tag) {
                    $option_nine .= '<option value="' . esc_attr($tag->term_id) . '"' . wc_selected($tag->term_id, $product_tags) . '>' . esc_html($tag->name) . '</option>';
                }
            }
        } else {
            if( !empty($product_tags)) {
                foreach ($product_tags as $key => $value) {
                    $term_object = get_term_by('id', $value, 'product_tag');
                    if(!empty($term_object->name)) {
                        $option_nine .= '<option value="' . esc_attr($value) . '" selected>' . esc_html($term_object->name) . '</option>';
                    }
                }
            }
        }
        if(!isset($product_categories)) {
            $product_categories = array();
        }
        $option_nine .= '</select>';
        $option_six = '<p class="description">' . apply_filters('angelleye_multi_account_display_products_label', __('Products', 'paypal-for-woocommerce-multi-account-management')) . '</p>';
        $option_six .= '<select id="product_list" class="product-search wc-enhanced-select" multiple="multiple" style="width: 78%;" name="woocommerce_paypal_express_api_product_ids[]" data-placeholder="' . esc_attr__('Any Product&hellip;', 'paypal-for-woocommerce-multi-account-management') . '">';
        $product_list = $this->angelleye_get_list_product_using_tag_cat($product_tags, $product_categories);
        if (!empty($product_list)) {
            foreach ($product_list as $product_list_id => $product_list_name) {
                $product = wc_get_product($product_list_id);
                if (is_object($product)) {
                    $option_six .= '<option value="' . esc_attr($product_list_id) . '"' . wc_selected($product_list_id, $product_ids) . '>' . wp_kses_post($product->get_formatted_name()) . '</option>';
                }
            }
        }
        $option_thirteen = '<p class="description">' . __('Currency Code', 'paypal-for-woocommerce-multi-account-management') . '</p>';
        $option_thirteen .= '<select class="wc-enhanced-select currency_code" name="currency_code">';
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
            $option_twelve .= '<select class="wc-enhanced-select card_type" name="card_type">';
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
        echo sprintf('<tr><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_trigger_conditions">%1$s</label></th><td class="forminp"><fieldset>%5$s %6$s  %8$s %13$s %9$s %10$s %7$s <input type="hidden" name="woocommerce_paypal_express_api_condition_field" value="%2$s"><select class="smart_forwarding_field" name="woocommerce_paypal_express_api_condition_sign">%3$s</select>&nbsp;<input class="input-text regular-input" name="woocommerce_paypal_express_api_condition_value" id="woocommerce_paypal_express_api_condition_value" type="number" min="0" max="1000" step="0.01" value="%4$s">%11$s %12$s</fieldset></td></tr>', $option_one, $option_two, $option_three, $option_four, $option_ten, $option_five, $option_six, $option_seven, $option_eight, $option_nine, $option_twelve, $option_thirteen, $option_fourteen);
        echo sprintf('<tr style="display: table-row;" valign="top">
                                <th scope="row" class="titledesc">
                                    <input name="is_edit" class="button-primary woocommerce-save-button" type="hidden" value="%1$s" />
                                    <input name="microprocessing_save" class="button-primary woocommerce-save-button" type="submit" value="%2$s" />
                                    <a href="?page=wc-settings&tab=multi_account_management" class="button-primary button">%3$s</a>
                                    %4$s
                                </th>
                            </tr>', $_GET['ID'], __('Save Changes', 'paypal-for-woocommerce-multi-account-management'), __('Cancel', 'paypal-for-woocommerce-multi-account-management'), wp_nonce_field('microprocessing_save'));
        echo '</tbody></table></form></div>';
        $this->angelleye_multi_account_tooltip_box();
    }

    public function angelleye_multi_account_tooltip_box() {
        $global_ec_site_owner_commission = get_option('global_ec_site_owner_commission', '');
        $global_ec_site_owner_commission_label = get_option('global_ec_site_owner_commission_label', '');
        ?>
        <div class="angelleye_multi_global_commission_right angelleye_multi_account_paypal_express_field">
            <form method="post" id="mainform" action="" enctype="multipart/form-data">
                <table class="form-table">
                <tr class="angelleye_multi_account_paypal_express_field">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_api_signature_microprocessing" class="commission"><?php echo __('Global Site Owner Commission %', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <input type="number" name="global_ec_site_owner_commission" min="0" max="100" step="0.01" placeholder="0" class="commission" value="<?php echo !empty($global_ec_site_owner_commission) ? $global_ec_site_owner_commission : ''; ?>">
                        </fieldset>
                    </td>
                </tr>
                <tr class="angelleye_multi_account_paypal_express_field">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_api_signature_microprocessing" class="commission"><?php echo __('Global Site Owner Commission Item Label', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <input type="text" class="input-text regular-input commission" name="global_ec_site_owner_commission_label" placeholder="Commission" value="<?php echo !empty($global_ec_site_owner_commission_label) ? $global_ec_site_owner_commission_label : ''; ?>">
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row" class="titledesc">
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <input name="global_commission_microprocessing_save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e('Save Changes', 'paypal-for-woocommerce-multi-account-management'); ?>" />
                            <a href="?page=wc-settings&tab=multi_account_management" class="button-primary button"><?php esc_attr_e('Cancel', 'paypal-for-woocommerce-multi-account-management'); ?></a>
                            <?php wp_nonce_field('microprocessing_save'); ?>
                        </fieldset>
                    </td>
                </tr>
            </table>
            </form>
        </div>
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
        if (!empty($_POST['global_commission_microprocessing_save'])) {
            update_option('global_ec_site_owner_commission', wc_clean($_POST['global_ec_site_owner_commission']));
            update_option('global_ec_site_owner_commission_label', wc_clean($_POST['global_ec_site_owner_commission_label']));
            echo sprintf('<div class="notice notice-success is-dismissible"><p>%1$s</p></div>', __('Your settings have been saved.', 'paypal-for-woocommerce-multi-account-management'));
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
            if (!empty($_GET['success'])) {
                echo sprintf('<div class=" notice notice-success is-dismissible"><p>%1$s</p></div>', __('Your settings have been saved.', 'paypal-for-woocommerce-multi-account-management'));
            }
            if (!empty($_GET['deleted'])) {
                echo sprintf('<div class=" notice notice-success is-dismissible"><p>%1$s</p></div>', __('Account permanently deleted.', 'paypal-for-woocommerce-multi-account-management'));
            }
            ?>
            <br/>
            <div class="angelleye_multi_account_left">
                <form method="post" id="mainform" action="" enctype="multipart/form-data">
                    <table class="form-table" id="micro_account_fields" >
                        <tbody class="angelleye_micro_account_body">
                            <?php echo $this->angelleye_multi_account_choose_payment_gateway(); ?>
                            <?php echo $this->angelleye_multi_account_api_field_ui() ?>
                            <?php echo $this->angelleye_multi_account_paypal_pro_payflow_api_field_ui(); ?>
                            <?php echo $this->angelleye_multi_account_api_paypal_field_ui(); ?>
                            <?php echo $this->angelleye_multi_account_condition_ui(); ?>
                            <tr style="display: table-row;" valign="top">
                                <th scope="row" class="titledesc">
                                    <input name="microprocessing_save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e('Save Changes', 'paypal-for-woocommerce-multi-account-management'); ?>" />
                                    <a href="?page=wc-settings&tab=multi_account_management" class="button-primary button"><?php esc_attr_e('Cancel', 'paypal-for-woocommerce-multi-account-management'); ?></a>
                                    <?php wp_nonce_field('microprocessing_save'); ?>
                                </th>
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
            <?php
            $this->angelleye_multi_account_list();
            ?>
        </div>
        <?php
        $woocommerce_paypal_express_settings = get_option('woocommerce_paypal_express_settings');
        if (!empty($woocommerce_paypal_express_settings['microprocessing'])) {
            $this->angelleye_display_multi_account_list($woocommerce_paypal_express_settings['microprocessing']);
        }
    }

    public function angelleye_multi_account_list() {
        if (class_exists('Paypal_For_Woocommerce_Multi_Account_Management_List_Data')) {
            $table = new Paypal_For_Woocommerce_Multi_Account_Management_List_Data();
            $table->prepare_items();
            echo '<form id="account-filter" method="post">';
            echo sprintf('<input type="hidden" name="page" value="%1$s" />', $_REQUEST['page']);
            $table->display();
            echo '</form>';
        }
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
            $microprocessing_key_array = array('woocommerce_paypal_express_enable', 'woocommerce_paypal_express_testmode', 'woocommerce_paypal_express_account_name', 'woocommerce_paypal_express_sandbox_email', 'woocommerce_paypal_express_sandbox_api_username', 'woocommerce_paypal_express_sandbox_api_password', 'woocommerce_paypal_express_sandbox_api_signature', 'woocommerce_paypal_express_email', 'woocommerce_paypal_express_api_username', 'woocommerce_paypal_express_api_password', 'woocommerce_paypal_express_api_signature', 'woocommerce_paypal_express_api_condition_field', 'woocommerce_paypal_express_api_condition_sign', 'woocommerce_paypal_express_api_condition_value', 'woocommerce_paypal_express_api_user_role', 'woocommerce_paypal_express_api_product_ids', 'product_categories', 'product_tags', 'buyer_countries', 'woocommerce_priority', 'angelleye_multi_account_choose_payment_gateway', 'store_countries', 'currency_code', 'ec_site_owner_commission', 'ec_site_owner_commission_label');
            if (empty($_POST['is_edit'])) {
                $my_post = array(
                    'post_title' => wp_strip_all_tags($_POST['woocommerce_paypal_express_account_name']),
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id(),
                    'post_type' => 'microprocessing'
                );
                $post_id = wp_insert_post($my_post);
            } else {
                $my_post = array(
                    'ID' => $_POST['is_edit'],
                    'post_title' => wp_strip_all_tags($_POST['woocommerce_paypal_express_account_name']),
                    'post_content' => '',
                );
                wp_update_post($my_post);
                $post_id = $_POST['is_edit'];
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
                $redirect_url = remove_query_arg(array('action', 'ID'));
                wp_redirect(add_query_arg('success', true, $redirect_url));
                exit();
            } else {
                echo sprintf('<div class=" notice notice-success is-dismissible"><p>%1$s</p></div>', __('Your settings have been saved.', 'paypal-for-woocommerce-multi-account-management'));
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
            $microprocessing_key_array = array('woocommerce_paypal_pro_payflow_enable', 'woocommerce_paypal_pro_payflow_testmode', 'woocommerce_paypal_pro_payflow_account_name', 'woocommerce_paypal_pro_payflow_api_paypal_partner', 'woocommerce_paypal_pro_payflow_api_paypal_vendor', 'woocommerce_paypal_pro_payflow_api_paypal_user', 'woocommerce_paypal_pro_payflow_api_password', 'woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner', 'woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor', 'woocommerce_paypal_pro_payflow_sandbox_api_paypal_user', 'woocommerce_paypal_pro_payflow_sandbox_api_password', 'woocommerce_paypal_express_api_condition_field', 'woocommerce_paypal_express_api_condition_sign', 'woocommerce_paypal_express_api_condition_value', 'woocommerce_paypal_express_api_user_role', 'woocommerce_paypal_express_api_product_ids', 'product_categories', 'product_tags', 'buyer_countries', 'woocommerce_priority', 'angelleye_multi_account_choose_payment_gateway', 'card_type', 'currency_code', 'store_countries');
            if (empty($_POST['is_edit'])) {
                $my_post = array(
                    'post_title' => wp_strip_all_tags($_POST['woocommerce_paypal_pro_payflow_account_name']),
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id(),
                    'post_type' => 'microprocessing'
                );
                $post_id = wp_insert_post($my_post);
            } else {
                $my_post = array(
                    'ID' => $_POST['is_edit'],
                    'post_title' => wp_strip_all_tags($_POST['woocommerce_paypal_pro_payflow_account_name']),
                    'post_content' => '',
                );
                wp_update_post($my_post);
                $post_id = $_POST['is_edit'];
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
                echo sprintf('<div class=" notice notice-success is-dismissible"><p>%1$s</p></div>', __('Your settings have been saved.', 'paypal-for-woocommerce-multi-account-management'));
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

    public function angelleye_paypal_for_woocommerce_general_settings_tab_content() {
        wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION);
        wp_enqueue_script('selectWoo');
        wp_enqueue_style('select2');
        wp_enqueue_script('wc-enhanced-select');
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
    }

    public function angelleye_get_product_tag_by_product_cat() {
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
        
        if( !empty($_POST['categories_list'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'terms' => $_POST['categories_list'],
                'operator' => 'IN',
            );
        }
        $loop = new WP_Query(apply_filters('angelleye_get_products_and_tags_by_product_cat', $args));
        $all_tags = array();
        $all_products = array();
        if (!empty($loop->posts)) {
            foreach ($loop->posts as $key => $value) {
                $all_products[$value] = get_the_title($value);
                $terms = get_the_terms($value, 'product_tag');
                if (!empty($terms)) {
                    foreach ($terms as $terms_key => $terms_value) {
                        if ($terms_value->count > 0) {
                            $all_tags[$terms_value->term_id] = $terms_value->name;
                        }
                    }
                }
            }
        }
        wp_send_json_success(
                array(
                    'all_tags' => $all_tags,
                    'all_products' => $all_products
                )
        );
    }

    public function angelleye_get_product_by_product_tags() {
        $args = array(
            'post_type' => apply_filters('angelleye_multi_account_post_type', array('product')),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish',
        );
        if (!empty($_POST['tag_list']) || !empty($_POST['categories_list'])) {
            $args['tax_query'] = array();
            if (!empty($_POST['tag_list'])) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_tag',
                    'terms' => $_POST['tag_list'],
                    'operator' => 'IN'
                );
            }
            if (!empty($_POST['categories_list'])) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_cat',
                    'terms' => $_POST['categories_list'],
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
        wp_send_json_success(
                array(
                    'all_products' => $all_products,
                )
        );
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
            <th><?php _e('Select Payment Gateway', 'paypal-for-woocommerce-multi-account-management'); ?></th>
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
                <fieldset>
                    <p class="description"><?php _e('Select Priority', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select class="wc-enhanced-select smart_forwarding_field" name="woocommerce_priority">
                        <?php
                        for ($x = 0; $x <= 100; $x++) {
                            if($x == 0) {
                                $woocommerce_priority_text = $x .' - Lowest';
                            } elseif($x == 100) {
                                $woocommerce_priority_text = $x .' - Highest';
                            } else {
                                $woocommerce_priority_text = $x;
                            }
                            echo "\n\t<option value='" . $x . "'>$woocommerce_priority_text</option>";
                        }
                        ?>
                    </select>
                    <p class="description"><?php _e('Select User Role', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select class="wc-enhanced-select smart_forwarding_field" name="woocommerce_paypal_express_api_user_role">
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
                    <select id="buyer_countries" name="buyer_countries[]" style="width: 78%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e('All countries', 'paypal-for-woocommerce-multi-account-management'); ?>">
                        <?php
                        $category_ids = array();
                        $countries = WC()->countries->get_countries();

                        if ($countries) {
                            foreach ($countries as $country_key => $country_full_name) {
                                echo '<option value="' . esc_attr($country_key) . '"' . wc_selected($country_key, $category_ids) . '>' . esc_html($country_full_name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <p class="description"><?php _e('Store country', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select id="store_countries" name="store_countries" style="width: 78%;"  class="wc-enhanced-select" data-placeholder="<?php esc_attr_e('All countries', 'paypal-for-woocommerce-multi-account-management'); ?>">
                        <?php
                        $category_ids = array();
                        $countries = WC()->countries->get_countries();
                        echo '<option value="0">All countries</option>';
                        if ($countries) {
                            foreach ($countries as $country_key => $country_full_name) {
                                echo '<option value="' . esc_attr($country_key) . '"' . wc_selected($country_key, $category_ids) . '>' . esc_html($country_full_name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <p class="description"><?php echo apply_filters('angelleye_multi_account_display_category_label', __('Product categories', 'paypal-for-woocommerce-multi-account-management')); ?></p>
                    <select id="product_categories" name="product_categories[]" style="width: 78%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e('Any category', 'paypal-for-woocommerce-multi-account-management'); ?>">
                        <?php
                        $category_ids = array();
                        $categories = get_terms(apply_filters('angelleye_get_product_categories', array('product_cat')), 
                                            array(
                                                'hide_empty' => 1,
                                                'orderby'    => 'name',
                                            ));
                        if ($categories) {
                            foreach ($categories as $cat) {
                                $category_lable = '';
                                $taxonomy_obj = get_taxonomy( $cat->taxonomy );
                                if(isset($taxonomy_obj->label) & !empty($taxonomy_obj->label)) {
                                    $category_lable = $cat->name . ' (' . $taxonomy_obj->label . ')';
                                } else {
                                    $category_lable = $cat->name;
                                }
                                echo '<option value="' . esc_attr($cat->term_id) . '"' . wc_selected($cat->term_id, $category_ids) . '>' . esc_html($category_lable) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <?php ?>
                    <p class="description"><?php _e('Product tags', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select id="product_tags" name="product_tags[]" style="width: 78%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e('Any tag', 'paypal-for-woocommerce-multi-account-management'); ?>">
                        <?php
                        $category_ids = array();
                        $tags = get_terms(array('product_tag'), 'orderby=name&hide_empty=1');
                        if ($tags) {
                            foreach ($tags as $tag) {
                                echo '<option value="' . esc_attr($tag->term_id) . '"' . wc_selected($tag->term_id, $category_ids) . '>' . esc_html($tag->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <p class="description"><?php echo apply_filters('angelleye_multi_account_display_products_label', __('Products', 'paypal-for-woocommerce-multi-account-management')); ?></p>
                    <select id="product_list" class="product-search wc-enhanced-select" multiple="multiple" style="width: 78%;" name="woocommerce_paypal_express_api_product_ids[]" data-placeholder="<?php esc_attr_e('Any Product&hellip;', 'paypal-for-woocommerce-multi-account-management'); ?>" data-action="woocommerce_json_search_products_and_variations">
                        <?php
                        $args = array(
                            'post_type' => apply_filters('angelleye_multi_account_post_type', array('product')),
                            'posts_per_page' => -1,
                            'fields' => 'ids',
                            'post_status' => 'publish',
                        );
                        $loop = new WP_Query($args);
                        if (!empty($loop->posts)) {
                            foreach ($loop->posts as $key => $value) {
                                echo '<option value="' . $value . '">' . get_the_title($value) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <p class="description"><?php _e('Transaction Amount', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <input type="hidden" name="woocommerce_paypal_express_api_condition_field" value="transaction_amount">
                    <select class="smart_forwarding_field" name="woocommerce_paypal_express_api_condition_sign"><option value="greaterthan"><?php echo __('Greater than', 'paypal-for-woocommerce-multi-account-management'); ?></option><option value="lessthan"><?php echo __('Less than', 'paypal-for-woocommerce-multi-account-management'); ?></option><option value="equalto"><?php echo __('Equal to', 'paypal-for-woocommerce-multi-account-management'); ?></option></select>
                    <input class="input-text regular-input" name="woocommerce_paypal_express_api_condition_value" id="woocommerce_paypal_express_api_condition_value" type="number" min="0" max="1000" step="0.01" value="0">
                    <div class="angelleye_multi_account_paypal_pro_payflow_field">
                        <p class="description"><?php _e('Card Type', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                        <select class="wc-enhanced-select card_type" name="card_type">
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
                    <select class="wc-enhanced-select currency_code" name="currency_code">
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
            $microprocessing_key_array = array('woocommerce_paypal_enable', 'woocommerce_paypal_testmode', 'woocommerce_paypal_account_name', 'woocommerce_paypal_sandbox_email', 'woocommerce_paypal_sandbox_api_username', 'woocommerce_paypal_sandbox_api_password', 'woocommerce_paypal_sandbox_api_signature', 'woocommerce_paypal_email', 'woocommerce_paypal_api_username', 'woocommerce_paypal_api_password', 'woocommerce_paypal_api_signature', 'woocommerce_paypal_express_api_condition_field', 'woocommerce_paypal_express_api_condition_sign', 'woocommerce_paypal_express_api_condition_value', 'woocommerce_paypal_express_api_user_role', 'woocommerce_paypal_express_api_product_ids', 'product_categories', 'product_tags', 'buyer_countries', 'woocommerce_priority', 'angelleye_multi_account_choose_payment_gateway', 'store_countries', 'currency_code');
            if (empty($_POST['is_edit'])) {
                $my_post = array(
                    'post_title' => wp_strip_all_tags($_POST['woocommerce_paypal_account_name']),
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id(),
                    'post_type' => 'microprocessing'
                );
                $post_id = wp_insert_post($my_post);
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
                echo sprintf('<div class=" notice notice-success is-dismissible"><p>%1$s</p></div>', __('Your settings have been saved.', 'paypal-for-woocommerce-multi-account-management'));
            }
        }
    }

}
