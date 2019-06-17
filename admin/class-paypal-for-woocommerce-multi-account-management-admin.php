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
        $selected_role = '';
        $microprocessing = get_post_meta($_GET['ID']);
        echo '<br/><div class="angelleye_multi_account_left"><form method="post" id="mainform" action="" enctype="multipart/form-data"><table class="form-table">
        <tbody class="angelleye_micro_account_body">';
        $gateway_list = array('paypal_express' => __('PayPal Express Checkout', ''), 'paypal_pro_payflow' => __('PayPal Payments Pro 2.0 (PayFlow)', ''));
        if (!empty($microprocessing['angelleye_multi_account_choose_payment_gateway'])) {
            $gateway_key_index = $microprocessing['angelleye_multi_account_choose_payment_gateway'];
            if (!empty($gateway_key_index[0])) {
                $this->gateway_key = $gateway_key = $gateway_key_index[0];
                if (!empty($gateway_list[$gateway_key])) {
                    $gateway_value = $gateway_list[$gateway_key];
                    $gateway_option_Selected = "<option value='$gateway_key'>$gateway_value</option>";
                    echo sprintf('<tr><th>%1$s</th><td><select class="angelleye_multi_account_choose_payment_gateway" name="angelleye_multi_account_choose_payment_gateway">%2$s</select></td></tr>', __('Select Payment Gateway', ''), $gateway_option_Selected);
                }
            }
        } else {
            $gateway_option_Selected = "<option value='paypal_express'>PayPal Express Checkout</option>";
            echo sprintf('<tr><th>%1$s</th><td><select class="angelleye_multi_account_choose_payment_gateway" name="angelleye_multi_account_choose_payment_gateway">%2$s</select></td></tr>', __('Select Payment Gateway', ''), $gateway_option_Selected);
        }

        if ($this->gateway_key == 'paypal_express') {

            foreach ($microprocessing as $microprocessing_key => $microprocessing_value) {
                switch ($microprocessing_key) {
                    case 'woocommerce_paypal_express_enable':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_enable">%1$s</label></th><td class="forminp"><fieldset><label for="woocommerce_paypal_express_enable"><input class="woocommerce_paypal_express_enable" name="woocommerce_paypal_express_enable" %2$s id="woocommerce_paypal_express_enable" type="checkbox"> %3$s</label><br></fieldset></td></tr>', __('Enable / Disable', 'paypal-for-woocommerce-multi-account-management'), ($microprocessing_value[0] == 'on') ? 'checked' : '', __('Enable Account', 'paypal-for-woocommerce-multi-account-management'));
                        break;
                    case 'woocommerce_paypal_express_testmode':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_testmode">%1$s</label></th><td class="forminp"><fieldset><label for="woocommerce_paypal_express_testmode_microprocessing"><input class="woocommerce_paypal_express_testmode width460" name="woocommerce_paypal_express_testmode" %2$s id="woocommerce_paypal_express_testmode_microprocessing" type="checkbox"> %3$s</label><br></fieldset></td></tr>', __('PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'), ($microprocessing_value[0] == 'on') ? 'checked' : '', __('Enable PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'));
                        break;
                    case 'woocommerce_paypal_express_account_name':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_account_name_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_account_name" value="%2$s" id="woocommerce_paypal_express_account_name_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('Account Nickname', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_sandbox_api_username':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_username_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_username" value="%2$s" id="woocommerce_paypal_express_sandbox_api_username_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('Sandbox API Username', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_sandbox_api_password':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_password" value="%2$s" id="woocommerce_paypal_express_sandbox_api_password_microprocessing" style="" placeholder="" type="password"></fieldset></td></tr>', __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_sandbox_api_signature':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_signature_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_signature" value="%2$s" id="woocommerce_paypal_express_sandbox_api_signature_microprocessing" style="" placeholder="" type="password"></fieldset></td></tr>', __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_api_username':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_username_microprocessing">%1$s</label><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_api_username" value="%2$s" id="woocommerce_paypal_express_api_username_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('API Username', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_api_password':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_api_password" value="%2$s" id="woocommerce_paypal_express_api_password_microprocessing" style="" placeholder="" type="password"></fieldset></td></tr>', __('API Password', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                        break;
                    case 'woocommerce_paypal_express_api_signature':
                        echo sprintf('<tr><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_signature_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_api_signature" value="%2$s" id="woocommerce_paypal_express_api_signature_microprocessing" style="" placeholder="" type="password"></fieldset></td></tr>', __('API Signature', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
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
        } else {
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
        $option_two = '<option value="transaction_amount">Transaction Amount</option>';
        $option_three_array = array('equalto' => __('Equal to', 'paypal-for-woocommerce-multi-account-management'), 'lessthan' => __('Less than', 'paypal-for-woocommerce-multi-account-management'), 'greaterthan' => __('Greater than', 'paypal-for-woocommerce-multi-account-management'));
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
        $option_five .= '<select class="smart_forwarding_field" name="woocommerce_paypal_express_api_user_role">';
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
        $option_ten .= '<select class="smart_forwarding_field" name="woocommerce_priority">';
        for ($x = 0; $x <= 100; $x++) {
            if ($woocommerce_priority == $x) {
                $option_ten .= "<option selected='selected' value='" . $x . "'>$x</option>";
            } else {
                $option_ten .= "<option value='" . $x . "'>$x</option>";
            }
        }
        $option_ten .= '</select>';
        $product_ids = array();
        if (isset($microprocessing['woocommerce_paypal_express_api_product_ids'][0])) {
            $product_ids = maybe_unserialize($microprocessing['woocommerce_paypal_express_api_product_ids'][0]);
        }
        $option_seven = '<p class="description">' . __('Buyer country', 'paypal-for-woocommerce-multi-account-management') . '</p>';
        $option_seven .= '<select id="buyer_countries" name="buyer_countries[]" style="width: 78%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="' . __("All countries", "woocommerce") . '">';
        $countries = WC()->countries->get_countries();
        if ($countries) {
            foreach ($countries as $country_key => $country_full_name) {
                $option_seven .= '<option value="' . esc_attr($country_key) . '"' . wc_selected($country_key, $buyer_countries) . '>' . esc_html($country_full_name) . '</option>';
            }
        }
        $option_seven .= '</select>';
        $option_fourteen = '<p class="description">' . __('Store country', 'paypal-for-woocommerce-multi-account-management') . '</p>';
        $option_fourteen .= '<select id="store_countries" name="store_countries" style="width: 78%;"  class="wc-enhanced-select" data-placeholder="' . __("All countries", "woocommerce") . '">';
        if ($countries) {
            $store_countries = !empty($store_countries) ? $store_countries : '';
            $option_fourteen .= '<option value="0">All countries</option>';
            foreach ($countries as $country_key => $country_full_name) {
                $option_fourteen .= '<option value="' . esc_attr($country_key) . '"' . wc_selected($country_key, $store_countries) . '>' . esc_html($country_full_name) . '</option>';
            }
        }
        $option_fourteen .= '</select>';
        $option_eight = '<p class="description"> ' . __('Product categories', 'paypal-for-woocommerce-multi-account-management') . '</p>';
        $option_eight .= '<select id="product_categories" name="product_categories[]" style="width: 78%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="' . __('Any category', 'woocommerce') . '">';
        $categories = get_terms('product_cat', 'orderby=name&hide_empty=0');
        if ($categories) {
            foreach ($categories as $cat) {
                $option_eight .= '<option value="' . esc_attr($cat->term_id) . '"' . wc_selected($cat->term_id, $product_categories) . '>' . esc_html($cat->name) . '</option>';
            }
        }
        $option_eight .= '</select>';
        $option_nine = '<p class="description">' . __('Product tags', 'paypal-for-woocommerce-multi-account-management') . '</p>';
        $option_nine .= '<select id="product_tags" name="product_tags[]" style="width: 78%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="' . __('Any tag', 'woocommerce') . '">';
        $tags = get_terms('product_tag', 'orderby=name&hide_empty=0');
        if ($tags) {
            foreach ($tags as $tag) {
                $option_nine .= '<option value="' . esc_attr($tag->term_id) . '"' . wc_selected($tag->term_id, $product_tags) . '>' . esc_html($tag->name) . '</option>';
            }
        }
        $option_nine .= '</select>';
        $option_six = '<p class="description">' . __('Products', 'woocommerce') . '</p>';
        $option_six .= '<select id="product_list" class="product-search wc-enhanced-select" multiple="multiple" style="width: 78%;" name="woocommerce_paypal_express_api_product_ids[]" data-placeholder="' . esc_attr__('Any Product&hellip;', 'woocommerce') . '">';
        if (!empty($product_ids)) {
            foreach ($product_ids as $product_id) {
                $product = wc_get_product($product_id);
                if (is_object($product)) {
                    $option_six .= '<option value="' . esc_attr($product_id) . '"' . selected(true, true, false) . '>' . wp_kses_post($product->get_formatted_name()) . '</option>';
                }
            }
        }
        $option_thirteen = '<p class="description">' . __('Currency Code', 'paypal-for-woocommerce-multi-account-management') . '</p>';
        $option_thirteen .= '<select class="currency_code" name="currency_code">';
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
            $option_twelve .= '<select class="card_type" name="card_type">';
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
        $option_six .= '</select><p class="description"></p>';
        echo sprintf('<tr><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_trigger_conditions">%1$s</label></th><td class="forminp"><fieldset>%5$s %6$s  %8$s %13$s %9$s %10$s %7$s <select class="smart_forwarding_field" name="woocommerce_paypal_express_api_condition_field">%2$s</select><select class="smart_forwarding_field" name="woocommerce_paypal_express_api_condition_sign">%3$s</select><input class="input-text regular-input" name="woocommerce_paypal_express_api_condition_value" id="woocommerce_paypal_express_api_condition_value" type="number" min="0" max="1000" step="0.01" value="%4$s">%11$s %12$s</fieldset></td></tr>', $option_one, $option_two, $option_three, $option_four, $option_ten, $option_five, $option_six, $option_seven, $option_eight, $option_nine, $option_twelve, $option_thirteen, $option_fourteen);
        echo sprintf('<tr style="display: table-row;" valign="top">
                                <th scope="row" class="titledesc">
                                    <input name="is_edit" class="button-primary woocommerce-save-button" type="hidden" value="%1$s" />
                                    <input name="microprocessing_save" class="button-primary woocommerce-save-button" type="submit" value="%2$s" />
                                    <a href="?page=paypal-for-woocommerce&tab=general_settings&gateway=paypal_for_wooCommerce_for_multi_account_management" class="button-primary button">%3$s</a>
                                    %4$s
                                </th>
                            </tr>', $_GET['ID'], __('Save Changes', 'paypal-for-woocommerce-multi-account-management'), __('Cancel', 'paypal-for-woocommerce-multi-account-management'), wp_nonce_field('microprocessing_save'));
        echo '</tbody></table></form></div>';
        $this->angelleye_multi_account_tooltip_box();
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
        if (!empty($_POST['angelleye_multi_account_choose_payment_gateway']) && $_POST['angelleye_multi_account_choose_payment_gateway'] == 'paypal_pro_payflow') {
            $this->angelleye_save_multi_account_data_paypal_pro_payflow();
        }
        if (!empty($_POST['angelleye_multi_account_choose_payment_gateway']) && $_POST['angelleye_multi_account_choose_payment_gateway'] == 'paypal_express') {
            $this->angelleye_save_multi_account_data();
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
                            <?php echo $this->angelleye_multi_account_condition_ui(); ?>
                            <tr style="display: table-row;" valign="top">
                                <th scope="row" class="titledesc">
                                    <input name="microprocessing_save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e('Save Changes', 'paypal-for-woocommerce-multi-account-management'); ?>" />
                                    <a href="?page=paypal-for-woocommerce&tab=general_settings&gateway=paypal_for_wooCommerce_for_multi_account_management" class="button-primary button"><?php esc_attr_e('Cancel', 'paypal-for-woocommerce-multi-account-management'); ?></a>
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
            if (!empty($_POST['woocommerce_paypal_express_testmode']) && $_POST['woocommerce_paypal_express_testmode'] == 'on') {
                if (empty($_POST['woocommerce_paypal_express_sandbox_api_username']) && empty($_POST['woocommerce_paypal_express_api_password']) && empty($_POST['woocommerce_paypal_express_sandbox_api_signature'])) {
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php _e('Sandbox API Username or Sandbox API Password or Sandbox API Signature empty!', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    </div>
                    <?php
                    return false;
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
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php _e('API Username or API Password or API Signature empty!', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    </div>
                    <?php
                    return false;
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
            $microprocessing_key_array = array('woocommerce_paypal_express_enable', 'woocommerce_paypal_express_testmode', 'woocommerce_paypal_express_account_name', 'woocommerce_paypal_express_sandbox_api_username', 'woocommerce_paypal_express_sandbox_api_password', 'woocommerce_paypal_express_sandbox_api_signature', 'woocommerce_paypal_express_api_username', 'woocommerce_paypal_express_api_password', 'woocommerce_paypal_express_api_signature', 'woocommerce_paypal_express_api_condition_field', 'woocommerce_paypal_express_api_condition_sign', 'woocommerce_paypal_express_api_condition_value', 'woocommerce_paypal_express_api_user_role', 'woocommerce_paypal_express_api_product_ids', 'product_categories', 'product_tags', 'buyer_countries', 'woocommerce_priority', 'angelleye_multi_account_choose_payment_gateway', 'store_countries', 'currency_code');
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
                        if($microprocessing_key == 'woocommerce_paypal_express_api_condition_value') {
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
                'custip' => AngellEYE_Utility::get_user_ip(),
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
                        if($microprocessing_key == 'woocommerce_paypal_express_api_condition_value') {
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

    public function angelleye_get_multi_account_by_order_total_latest($gateways, $gateway_setting, $order_id) {
        global $user_ID;
        $current_user_roles = array();
        if(!isset($gateways->testmode)) {
            return;
        }
        if (is_user_logged_in()) {
            $user = new WP_User($user_ID);
            if (!empty($user->roles) && is_array($user->roles)) {
                $current_user_roles = $user->roles;
                $current_user_roles[] = 'all';
            }
        }
        $this->final_associate_account = array();
        $order_total = $this->angelleye_get_total($order_id);
        if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_pro_payflow') {
            $args = array(
                'post_type' => 'microprocessing',
                'order' => 'DESC',
                'orderby' => 'order_clause',
                'meta_key' => 'woocommerce_priority',
                'meta_query' => array(
                    'order_clause' => array(
                        'key' => 'woocommerce_priority',
                        'type' => 'NUMERIC' // unless the field is not a number
                    ),
                    'relation' => 'AND',
                    array(
                        'key' => 'woocommerce_paypal_pro_payflow_enable',
                        'value' => 'on',
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'woocommerce_paypal_pro_payflow_testmode',
                        'value' => ($gateways->testmode == true) ? 'on' : '',
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'woocommerce_priority',
                        'compare' => 'EXISTS'
                    )
                )
            );
        } elseif (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_express') {
            $args = array(
                'post_type' => 'microprocessing',
                'order' => 'DESC',
                'orderby' => 'order_clause',
                'meta_key' => 'woocommerce_priority',
                'meta_query' => array(
                    'order_clause' => array(
                        'key' => 'woocommerce_priority',
                        'type' => 'NUMERIC' // unless the field is not a number
                    ),
                    'relation' => 'AND',
                    array(
                        'key' => 'woocommerce_paypal_express_enable',
                        'value' => 'on',
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'woocommerce_paypal_express_testmode',
                        'value' => ($gateways->testmode == true) ? 'on' : '',
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'woocommerce_priority',
                        'compare' => 'EXISTS'
                    )
                )
            );
        }

        $query = new WP_Query();
        $result = $query->query($args);
        $total_posts = $query->found_posts;
        // exclude multi account record base on first four condition

        if ($total_posts > 0) {
            foreach ($result as $key => $value) {
                $passed_rules = array();
                if (!empty($value->ID)) {
                    // Card Type
                    if ($gateway_setting->id == 'paypal_pro_payflow') {
                        $card_type = get_post_meta($value->ID, 'card_type', true);
                        if (!empty($card_type)) {
                            $card_number = isset($_POST['paypal_pro_payflow-card-number']) ? wc_clean($_POST['paypal_pro_payflow-card-number']) : '';
                            $card_value = $this->card_type_from_account_number($card_number);
                            if ($card_value != $card_type) {
                                unset($result[$key]);
                                unset($passed_rules);
                                continue;
                            }
                        }
                    }
                    // Currency Code
                    $currency_code = get_post_meta($value->ID, 'currency_code', true);
                    if (!empty($currency_code)) {
                        $store_currency = get_woocommerce_currency();
                        if ($store_currency != $currency_code) {
                            unset($result[$key]);
                            unset($passed_rules);
                            continue;
                        }
                    }
                    // Base Country
                    $buyer_countries = get_post_meta($value->ID, 'buyer_countries', true);
                    if (!empty($buyer_countries)) {
                        foreach ($buyer_countries as $buyer_countries_key => $buyer_countries_value) {
                            if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_pro_payflow') {
                                if (!empty($order_id) && $order_id > 0) {
                                    $order = wc_get_order($order_id);
                                    $billing_country = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_country : $order->get_billing_country();
                                    if (!empty($billing_country) && $billing_country == $buyer_countries_value) {
                                        $passed_rules['buyer_countries'] = true;
                                    }
                                }
                            } elseif (!empty($order_id) && $order_id > 0) {
                                $order = wc_get_order($order_id);
                                $billing_country = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_country : $order->get_billing_country();
                                if (!empty($billing_country) && $billing_country == $buyer_countries_value) {
                                    $passed_rules['buyer_countries'] = true;
                                }
                            } else {
                                $post_checkout_data = WC()->session->get('post_data');
                                if (empty($post_checkout_data)) {
                                    $billing_country = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_country() : WC()->customer->get_billing_country();
                                    if (empty($billing_country)) {
                                        $billing_country = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_country() : WC()->customer->get_shipping_country();
                                    }
                                    if (!empty($billing_country)) {
                                        if ($billing_country == $buyer_countries_value) {
                                            $passed_rules['buyer_countries'] = true;
                                        }
                                    }
                                } else {
                                    if (!empty($post_checkout_data['billing_country']) && $post_checkout_data['billing_country'] == $buyer_countries_value) {
                                        $passed_rules['buyer_countries'] = true;
                                    }
                                }
                            }
                        }
                    } else {
                        $passed_rules['buyer_countries'] = true;
                    }
                    if (empty($passed_rules['buyer_countries'])) {
                        unset($result[$key]);
                        unset($passed_rules);
                        continue;
                    }


                    $store_countries = get_post_meta($value->ID, 'store_countries', true);
                    if (!empty($store_countries)) {
                        if (WC()->countries->get_base_country() != $store_countries) {
                            unset($result[$key]);
                            unset($passed_rules);
                            continue;
                        }
                    }

                    // User Role
                    $woocommerce_paypal_express_api_user_role = get_post_meta($value->ID, 'woocommerce_paypal_express_api_user_role', true);
                    if (!empty($woocommerce_paypal_express_api_user_role)) {
                        if (is_user_logged_in()) {
                            if (in_array($woocommerce_paypal_express_api_user_role, (array) $user->roles, true) || $woocommerce_paypal_express_api_user_role == 'all') {
                                $passed_rules['woocommerce_paypal_express_api_user_role'] = true;
                            } else {
                                unset($result[$key]);
                                unset($passed_rules);
                                continue;
                            }
                        }
                    }
                    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                        $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                        $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                        // Categories
                        $woo_product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
                        $product_categories = get_post_meta($value->ID, 'product_categories', true);
                        if (!empty($product_categories)) {
                            if (!array_intersect($product_categories, $woo_product_categories)) {
                                unset($result[$key]);
                                unset($passed_rules);
                                continue;
                            }
                        }
                        // Tags
                        $woo_product_tag = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'ids'));
                        $product_tags = get_post_meta($value->ID, 'product_tags', true);
                        if (!empty($product_tags)) {
                            if (!array_intersect($product_tags, $woo_product_tag)) {
                                unset($result[$key]);
                                unset($passed_rules);
                                continue;
                            }
                        }
                        $product_ids = get_post_meta($value->ID, 'woocommerce_paypal_express_api_product_ids', true);
                        if (!empty($product_ids)) {
                            if (!array_intersect((array) $product_id, $product_ids)) {
                                unset($result[$key]);
                                unset($passed_rules);
                                continue;
                            }
                        }
                    }
                }
                unset($passed_rules);
            }
        }
        $total_posts = $query->found_posts;
        $loop = 0;
        if (count($result) > 0) {
            foreach ($result as $key => $value) {
                if (!empty($value->ID)) {
                    $microprocessing_array = get_post_meta($value->ID);
                    if (!empty($microprocessing_array['woocommerce_paypal_express_api_condition_sign'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_api_condition_value'][0])) {
                        switch ($microprocessing_array['woocommerce_paypal_express_api_condition_sign'][0]) {
                            case 'equalto':
                                if ($order_total == $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0]) {
                                    foreach ($microprocessing_array as $key_sub => $value_sub) {
                                        $this->final_associate_account[$loop][$key_sub] = $value_sub[0];
                                    }
                                    $loop = $loop + 1;
                                }
                                break;
                            case 'lessthan':
                                if ($order_total < $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0]) {
                                    foreach ($microprocessing_array as $key_sub => $value_sub) {
                                        $this->final_associate_account[$loop][$key_sub] = $value_sub[0];
                                    }
                                    $loop = $loop + 1;
                                }
                                break;
                            case 'greaterthan':
                                if ($order_total > $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0]) {
                                    foreach ($microprocessing_array as $key_sub => $value_sub) {
                                        $this->final_associate_account[$loop][$key_sub] = $value_sub[0];
                                    }
                                    $loop = $loop + 1;
                                }
                                break;
                        }
                    }
                }
            }
            if (count($this->final_associate_account) == 1) {
                return $this->final_associate_account[0];
            } elseif (count($this->final_associate_account) == 0) {
                return $this->final_associate_account;
            } else {
                return $this->angelleye_get_closest_amount($this->final_associate_account, $order_total);
            }
        }
    }

    public function angelleye_get_multi_account_by_order_total($gateways, $gateway_setting, $order_id) {
        global $user_ID;
        $current_user_roles = array();
        if (is_user_logged_in()) {
            $user = new WP_User($user_ID);
            if (!empty($user->roles) && is_array($user->roles)) {
                $current_user_roles = $user->roles;
                $current_user_roles[] = 'all';
            }
        }
        $this->final_associate_account = array();
        $order_total = $this->angelleye_get_total($order_id);
        if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_pro_payflow') {
            $args = array(
                'post_type' => 'microprocessing',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'woocommerce_paypal_pro_payflow_enable',
                        'value' => 'on',
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'woocommerce_paypal_pro_payflow_testmode',
                        'value' => ($gateways->testmode == true) ? 'on' : '',
                        'compare' => 'LIKE'
                    )
                )
            );
        } elseif (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_express') {
            $args = array(
                'post_type' => 'microprocessing',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'woocommerce_paypal_express_enable',
                        'value' => 'on',
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'woocommerce_paypal_express_testmode',
                        'value' => ($gateways->testmode == true) ? 'on' : '',
                        'compare' => 'LIKE'
                    )
                )
            );
        }
        $query = new WP_Query();
        $result = $query->query($args);
        $total_posts = $query->found_posts;
        $loop = 0;
        if ($total_posts > 0) {
            foreach ($result as $key => $value) {
                if (!empty($value->ID)) {
                    $microprocessing_array = get_post_meta($value->ID);
                    if (!isset($microprocessing_array['woocommerce_paypal_express_api_user_role'][0]) || in_array($microprocessing_array['woocommerce_paypal_express_api_user_role'][0], $current_user_roles)) {
                        if (!empty($microprocessing_array['woocommerce_paypal_express_api_condition_sign'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_api_condition_value'][0])) {
                            switch ($microprocessing_array['woocommerce_paypal_express_api_condition_sign'][0]) {
                                case 'equalto':
                                    if ($order_total == $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0]) {
                                        foreach ($microprocessing_array as $key_sub => $value_sub) {
                                            $this->final_associate_account[$loop][$key_sub] = $value_sub[0];
                                        }
                                        $loop = $loop + 1;
                                    }
                                    break;
                                case 'lessthan':
                                    if ($order_total < $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0]) {
                                        foreach ($microprocessing_array as $key_sub => $value_sub) {
                                            $this->final_associate_account[$loop][$key_sub] = $value_sub[0];
                                        }
                                        $loop = $loop + 1;
                                    }
                                    break;
                                case 'greaterthan':
                                    if ($order_total > $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0]) {
                                        foreach ($microprocessing_array as $key_sub => $value_sub) {
                                            $this->final_associate_account[$loop][$key_sub] = $value_sub[0];
                                        }
                                        $loop = $loop + 1;
                                    }
                                    break;
                            }
                        }
                    }
                }
            }
            if (count($this->final_associate_account) == 1) {
                return $this->final_associate_account[0];
            } elseif (count($this->final_associate_account) == 0) {
                return $this->final_associate_account;
            } else {
                return $this->angelleye_get_closest_amount($this->final_associate_account, $order_total);
            }
        }
    }

    public function angelleye_get_closest_amount($array, $value) {
        $size = count($array);
        $index_key = 0;
        if ($size > 0) {
            $diff = abs($array[0]['woocommerce_paypal_express_api_condition_value'] - $value);
            $ret = $array[0]['woocommerce_paypal_express_api_condition_value'];
            $index_key = 0;
            for ($i = 1; $i < $size; $i) {
                $temp = abs($array[$i]['woocommerce_paypal_express_api_condition_value'] - $value);
                if ($temp < $diff) {
                    $diff = $temp;
                    $ret = $array[$i]['woocommerce_paypal_express_api_condition_value'];
                    $index_key = $i;
                }
            }
            return $array[$index_key];
        } else {
            return array();
        }
    }

    public function angelleye_paypal_for_woocommerce_multi_account_api_paypal_express($gateways, $request = null, $order_id = null) {
        if ($request == null) {
            $gateway_setting = $gateways;
        } else {
            $gateway_setting = $request;
        }
        if( !isset($gateways) || !isset($gateways->testmode)) {
            return false;
        }
        if ($order_id == null) {
            if (is_null(WC()->cart)) {
                return;
            }
            if (WC()->cart->is_empty()) {
                return false;
            }
        }
        if ($this->is_angelleye_multi_account_used($order_id)) {
            $_multi_account_api_username = $this->angelleye_get_multi_account_api_user_name($order_id);
            $microprocessing_value = $this->angelleye_get_multi_account_details_by_api_user_name($gateway_setting, $_multi_account_api_username);
        } elseif (!empty($_GET['pp_action']) && $_GET['pp_action'] == 'set_express_checkout') {
            if (version_compare(PFWMA_VERSION, '1.0.2', '>')) {
                $microprocessing_value = $this->angelleye_get_multi_account_by_order_total_latest($gateways, $gateway_setting, $order_id);
            } else {
                $microprocessing_value = $this->angelleye_get_multi_account_by_order_total($gateways, $gateway_setting, $order_id);
            }
        }
        if (!empty($microprocessing_value)) {
            if ($gateways->testmode == true) {
                if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_pro_payflow') {
                    if (!empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_password']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor'] && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner']))) {
                        $gateway_setting->paypal_user = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user'];
                        $gateway_setting->paypal_password = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_password'];
                        $gateway_setting->paypal_vendor = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor'];
                        $gateway_setting->paypal_partner = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner'];
                        WC()->session->set('multi_account_api_username', $gateway_setting->paypal_user);
                        return;
                    }
                } elseif (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_express') {
                    if (!empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_username']) && !empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_password']) && !empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_signature'])) {
                        $gateway_setting->api_username = $microprocessing_value['woocommerce_paypal_express_sandbox_api_username'];
                        $gateway_setting->api_password = $microprocessing_value['woocommerce_paypal_express_sandbox_api_password'];
                        $gateway_setting->api_signature = $microprocessing_value['woocommerce_paypal_express_sandbox_api_signature'];
                        WC()->session->set('multi_account_api_username', $gateway_setting->api_username);
                        return;
                    }
                }
            } else {
                if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_pro_payflow') {
                    if (!empty($microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_user']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_api_password']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_vendor']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner'])) {
                        $gateway_setting->api_username = $microprocessing_value['woocommerce_paypal_express_api_username'];
                        $gateway_setting->api_password = $microprocessing_value['woocommerce_paypal_express_api_password'];
                        $gateway_setting->api_signature = $microprocessing_value['woocommerce_paypal_express_api_signature'];
                        WC()->session->set('multi_account_api_username', $gateway_setting->api_username);
                        return;
                    }
                } elseif (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_express') {
                    if (!empty($microprocessing_value['woocommerce_paypal_express_api_username']) && !empty($microprocessing_value['woocommerce_paypal_express_api_password']) && !empty($microprocessing_value['woocommerce_paypal_express_api_signature'])) {
                        $gateway_setting->api_username = $microprocessing_value['woocommerce_paypal_express_api_username'];
                        $gateway_setting->api_password = $microprocessing_value['woocommerce_paypal_express_api_password'];
                        $gateway_setting->api_signature = $microprocessing_value['woocommerce_paypal_express_api_signature'];
                        WC()->session->set('multi_account_api_username', $gateway_setting->api_username);
                        return;
                    }
                }
            }
        }
    }

    public function angelleye_paypal_for_woocommerce_multi_account_api_paypal_payflow($gateways, $request = null, $order_id = null) {
        if ($request == null) {
            $gateway_setting = $gateways;
        } else {
            $gateway_setting = $request;
        }

        if ($order_id == null) {
            if (is_null(WC()->cart)) {
                return;
            }
            if (WC()->cart->is_empty()) {
                return false;
            }
        }
        if ($this->is_angelleye_multi_account_used($order_id)) {
            $_multi_account_api_username = $this->angelleye_get_multi_account_api_user_name($order_id);
            $microprocessing_value = $this->angelleye_get_multi_account_details_by_api_user_name($gateway_setting, $_multi_account_api_username);
        } elseif (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_pro_payflow') {
            if (version_compare(PFWMA_VERSION, '1.0.2', '>')) {
                $microprocessing_value = $this->angelleye_get_multi_account_by_order_total_latest($gateways, $gateway_setting, $order_id);
            } else {
                $microprocessing_value = $this->angelleye_get_multi_account_by_order_total($gateways, $gateway_setting, $order_id);
            }
        }
        if (!empty($microprocessing_value)) {
            if ($gateway_setting->testmode == true) {
                if (!empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_password']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor'] && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner']))) {
                    $gateway_setting->paypal_user = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user'];
                    $gateway_setting->paypal_password = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_password'];
                    $gateway_setting->paypal_vendor = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor'];
                    $gateway_setting->paypal_partner = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner'];
                    WC()->session->set('multi_account_api_username', $gateway_setting->paypal_user);
                    return;
                }
            } else {
                if (!empty($microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_user']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_api_password']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_vendor']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner'])) {
                    $gateway_setting->paypal_user = $microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_user'];
                    $gateway_setting->paypal_password = $microprocessing_value['woocommerce_paypal_pro_payflow_api_password'];
                    $gateway_setting->paypal_vendor = $microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_vendor'];
                    $gateway_setting->paypal_partner = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner'];
                    WC()->session->set('multi_account_api_username', $gateway_setting->paypal_user);
                    return;
                }
            }
        }
    }

    public function angelleye_get_multi_account_details_by_api_user_name($gateway_setting, $_multi_account_api_username) {
        $microprocessing = array();
        if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_pro_payflow') {
            $args = array(
                'post_type' => 'microprocessing',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'woocommerce_paypal_pro_payflow_sandbox_api_paypal_user',
                        'value' => $_multi_account_api_username,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'woocommerce_paypal_pro_payflow_api_paypal_user',
                        'value' => $_multi_account_api_username,
                        'compare' => 'LIKE'
                    )
                )
            );
        } elseif (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_express') {
            $args = array(
                'post_type' => 'microprocessing',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'woocommerce_paypal_express_sandbox_api_username',
                        'value' => $_multi_account_api_username,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'woocommerce_paypal_express_api_username',
                        'value' => $_multi_account_api_username,
                        'compare' => 'LIKE'
                    )
                )
            );
        }
        $query = new WP_Query();
        $result = $query->query($args);
        $total_posts = $query->found_posts;
        if ($total_posts > 0) {
            foreach ($result as $key => $value) {
                if (!empty($value->ID)) {
                    $microprocessing_array = get_post_meta($value->ID);
                    foreach ($microprocessing_array as $key => $value) {
                        $microprocessing[$key] = $value[0];
                    }
                }
            }
        }
        return $microprocessing;
    }

    public function angelleye_get_total($order_id) {
        if ($order_id > 0) {
            $order = new WC_Order($order_id);
            $cart_contents_total = $order->get_total();
        } else {
            if (!defined('WOOCOMMERCE_CART')) {
                define('WOOCOMMERCE_CART', true);
            }
            WC()->cart->calculate_totals();
            WC()->cart->calculate_shipping();
            if (version_compare(WC_VERSION, '3.0', '<')) {
                WC()->customer->calculated_shipping(true);
            } else {
                WC()->customer->set_calculated_shipping(true);
            }
            if (wc_prices_include_tax()) {
                $cart_contents_total = WC()->cart->total;
            } else {
                $cart_contents_total = WC()->cart->total;
            }
        }
        return $cart_contents_total;
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
        ?>
        <a href="?page=paypal-for-woocommerce&tab=general_settings&gateway=paypal_for_wooCommerce_for_multi_account_management" class="nav-tab <?php echo $gateway == 'paypal_for_wooCommerce_for_multi_account_management' ? 'nav-tab-active' : ''; ?>"><?php echo __('Multi-Account Management', 'paypal-for-woocommerce'); ?></a> <?php
    }

    public function angelleye_paypal_for_woocommerce_general_settings_tab_content() {
        $gateway = isset($_GET['gateway']) ? $_GET['gateway'] : 'paypal_payment_gateway_products';
        if ($gateway == 'paypal_for_wooCommerce_for_multi_account_management') {
            wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION);
            wp_enqueue_script('selectWoo');
            wp_enqueue_style('select2');
            wp_enqueue_script('wc-enhanced-select');
            $this->angelleye_multi_account_ui();
        }
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
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'terms' => $_POST['categories_list'],
                    'operator' => 'IN',
                )
            )
        );
        $loop = new WP_Query($args);
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
            'post_type' => 'product',
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
        }
        $loop = new WP_Query($args);
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
                <label for="woocommerce_paypal_express_testmode"><?php echo __('PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'); ?></label>
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
                <label for="woocommerce_paypal_express_sandbox_api_username_microprocessing"><?php echo __('Sandbox API Username', 'paypal-for-woocommerce-multi-account-management'); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo __('Sandbox API Username', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_username" id="woocommerce_paypal_express_sandbox_api_username_microprocessing" style="" placeholder="" type="text">
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
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_password" id="woocommerce_paypal_express_sandbox_api_password_microprocessing" style="" placeholder="" type="password">
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
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_signature" id="woocommerce_paypal_express_sandbox_api_signature_microprocessing" style="" placeholder="" type="password">
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
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_api_username" id="woocommerce_paypal_express_api_username_microprocessing" style="" placeholder="" type="text">
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
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_api_password" id="woocommerce_paypal_express_api_password_microprocessing" style="" placeholder="" type="password">
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
                    <input class="input-text regular-input width460" name="woocommerce_paypal_express_api_signature" id="woocommerce_paypal_express_api_signature_microprocessing" style="" placeholder="" type="password">
                </fieldset>
            </td>
        </tr>
        <?php
    }

    public function angelleye_multi_account_choose_payment_gateway() {
        ?>
        <tr>
            <th><?php _e('Select Payment Gateway', 'paypal-for-woocommerce-multi-account-management'); ?></th>
            <td>

                <select class="angelleye_multi_account_choose_payment_gateway" name="angelleye_multi_account_choose_payment_gateway">
                    <?php
                    $gateway_list = array('paypal_express' => __('PayPal Express Checkout', ''), 'paypal_pro_payflow' => __('PayPal Payments Pro 2.0 (PayFlow)', ''));
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
                    <select class="smart_forwarding_field" name="woocommerce_priority">
                        <?php
                        for ($x = 0; $x <= 100; $x++) {
                            echo "The number is: $x <br>";
                            echo "\n\t<option value='" . $x . "'>$x</option>";
                        }
                        ?>
                    </select>
                    <p class="description"><?php _e('Select User Role', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select class="smart_forwarding_field" name="woocommerce_paypal_express_api_user_role">
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
                    <select id="buyer_countries" name="buyer_countries[]" style="width: 78%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e('All countries', 'woocommerce'); ?>">
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
                    <select id="store_countries" name="store_countries" style="width: 78%;"  class="wc-enhanced-select" data-placeholder="<?php esc_attr_e('All countries', 'woocommerce'); ?>">
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
                    <p class="description"><?php _e('Product categories', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select id="product_categories" name="product_categories[]" style="width: 78%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e('Any category', 'woocommerce'); ?>">
                        <?php
                        $category_ids = array();
                        $categories = get_terms('product_cat', 'orderby=name&hide_empty=0');

                        if ($categories) {
                            foreach ($categories as $cat) {
                                echo '<option value="' . esc_attr($cat->term_id) . '"' . wc_selected($cat->term_id, $category_ids) . '>' . esc_html($cat->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <?php ?>
                    <p class="description"><?php _e('Product tags', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select id="product_tags" name="product_tags[]" style="width: 78%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e('Any tag', 'woocommerce'); ?>">
                        <?php
                        $category_ids = array();
                        $tags = get_terms('product_tag', 'orderby=name&hide_empty=0');
                        if ($tags) {
                            foreach ($tags as $tag) {
                                echo '<option value="' . esc_attr($tag->term_id) . '"' . wc_selected($tag->term_id, $category_ids) . '>' . esc_html($tag->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <p class="description"><?php _e('Products', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select id="product_list" class="product-search wc-enhanced-select" multiple="multiple" style="width: 78%;" name="woocommerce_paypal_express_api_product_ids[]" data-placeholder="<?php esc_attr_e('Any Product&hellip;', 'woocommerce'); ?>" data-action="woocommerce_json_search_products_and_variations">
                        <?php
                        $args = array(
                            'post_type' => 'product',
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
                    <p class="description"><?php _e('Transaction', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    <select class="smart_forwarding_field" name="woocommerce_paypal_express_api_condition_field"><option value="transaction_amount"><?php echo __('Transaction Amount', 'paypal-for-woocommerce-multi-account-management'); ?></option></select>
                    <select class="smart_forwarding_field" name="woocommerce_paypal_express_api_condition_sign"><option value="greaterthan"><?php echo __('Greater than', 'paypal-for-woocommerce-multi-account-management'); ?></option><option value="lessthan"><?php echo __('Less than', 'paypal-for-woocommerce-multi-account-management'); ?></option><option value="equalto"><?php echo __('Equal to', 'paypal-for-woocommerce-multi-account-management'); ?></option></select>
                    <input class="input-text regular-input" name="woocommerce_paypal_express_api_condition_value" id="woocommerce_paypal_express_api_condition_value" type="number" min="0" max="1000" step="0.01" value="0">
                    <div class="angelleye_multi_account_paypal_pro_payflow_field">
                        <p class="description"><?php _e('Card Type', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                        <select class="card_type" name="card_type">
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
                    <select class="currency_code" name="currency_code">
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
        if (false === ( $response = get_transient('angelleye_push_notification_result') )) {
            $response = $this->angelleye_get_push_notifications();
            if(is_object($response)) {
                set_transient('angelleye_push_notification_result', $response, 12 * HOUR_IN_SECONDS);
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
        echo '<div class="notice notice-success angelleye-notice" style="display:none;" id="'.$response_data->id.'">'
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

}
