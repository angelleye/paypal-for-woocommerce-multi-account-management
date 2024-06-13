<?php

/**
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Paypal_For_Woocommerce_Multi_Account_Management
 * @subpackage Paypal_For_Woocommerce_Multi_Account_Management/includes
 * @author     Angell EYE <service@angelleye.com>
 */
class Paypal_For_Woocommerce_Multi_Account_Management_Vendor {

    private $plugin_name;
    private $version;
    public $testmode;
    public $is_ppcp_enable;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $global_automatic_rule_creation_testmode = get_option('global_automatic_rule_creation_testmode', '');
        if ($global_automatic_rule_creation_testmode == 'on') {
            $this->testmode = 'on';
        } else {
            $this->testmode = '';
        }
        $ppcp_gateway_ppcpsettings = get_option('woocommerce_angelleye_ppcp_settings');
        $this->is_ppcp_enable = isset($ppcp_gateway_ppcpsettings['enabled']) && $ppcp_gateway_ppcpsettings['enabled'] === 'yes';
    }

    public function angelleye_paypal_for_woocommerce_multi_account_rule_save_dokan($vendor_id) {
        try {
            if (function_exists('dokan')) {
                if (!dokan_is_user_seller($vendor_id)) {
                    return;
                }
                $post_id = angelleye_is_vendor_account_exist($vendor_id);
                if ($post_id != false) {
                    $user = get_user_by('id', $vendor_id);
                    $dokan_profile_settings = get_user_meta($vendor_id, 'dokan_profile_settings', true);
                    if (!empty($dokan_profile_settings['payment']['paypal']['email'])) {
                        $email = $dokan_profile_settings['payment']['paypal']['email'];
                    }
                    if (empty($email)) {
                        $email = get_user_meta($vendor_id, 'billing_email', true);
                    }
                    if (empty($email)) {
                        $email = $user->user_email;
                    }
                    if (!empty($email)) {
                        update_post_meta($post_id, 'woocommerce_paypal_express_sandbox_email', $email);
                        update_post_meta($post_id, 'woocommerce_paypal_express_email', $email);
                    }
                    $user_string = sprintf(
                            esc_html__('%1$s (#%2$s   %3$s)', 'woocommerce'), $user->display_name, absint($user->ID), $user->user_email
                    );
                    $woocommerce_paypal_express_account_name = get_post_meta($post_id, 'woocommerce_paypal_express_account_name', true);
                    if (empty($woocommerce_paypal_express_account_name)) {
                        update_post_meta($post_id, 'woocommerce_paypal_express_account_name', $user_string);
                    }
                } else {
                    $user = get_user_by('id', $vendor_id);
                    $user_string = sprintf(
                            esc_html__('%1$s (#%2$s   %3$s)', 'woocommerce'), $user->display_name, absint($user->ID), $user->user_email
                    );
                    $my_post = array(
                        'post_title' => $user_string,
                        'post_content' => '',
                        'post_status' => 'publish',
                        'post_author' => $vendor_id,
                        'post_type' => 'microprocessing'
                    );
                    $post_id = wp_insert_post($my_post);
                    do_action('update_angelleye_multi_account', $post_id);
                    $dokan_profile_settings = get_user_meta($vendor_id, 'dokan_profile_settings', true);
                    if (!empty($dokan_profile_settings['payment']['paypal']['email'])) {
                        $email = $dokan_profile_settings['payment']['paypal']['email'];
                    }
                    if (empty($email)) {
                        $email = get_user_meta($vendor_id, 'billing_email', true);
                    }
                    if (empty($email)) {
                        $user = get_user_by('id', $vendor_id);
                        $email = $user->user_email;
                    }
                    $microprocessing_key_array = array(
                        'woocommerce_paypal_express_enable' => 'on',
                        'woocommerce_paypal_express_testmode' => $this->testmode,
                        'woocommerce_paypal_express_account_name' => $user_string,
                        'woocommerce_paypal_express_sandbox_email' => $email,
                        'woocommerce_paypal_express_sandbox_merchant_id' => '',
                        'woocommerce_paypal_express_sandbox_api_username' => '',
                        'woocommerce_paypal_express_sandbox_api_password' => '',
                        'woocommerce_paypal_express_sandbox_api_signature' => '',
                        'woocommerce_paypal_express_email' => $email,
                        'woocommerce_paypal_express_merchant_id' => '',
                        'woocommerce_paypal_express_api_username' => '',
                        'woocommerce_paypal_express_api_password' => '',
                        'woocommerce_paypal_express_api_signature' => '',
                        'woocommerce_paypal_express_api_condition_field' => 'transaction_amount',
                        'woocommerce_paypal_express_api_condition_sign' => 'greaterthan',
                        'woocommerce_paypal_express_api_condition_value' => '0',
                        'woocommerce_paypal_express_api_user_role' => 'all',
                        'woocommerce_paypal_express_api_user' => $vendor_id,
                        'woocommerce_paypal_express_api_product_ids' => array(),
                        'product_categories' => '',
                        'product_tags' => '',
                        'buyer_countries' => '',
                        'buyer_states' => '',
                        'woocommerce_priority' => '',
                        'angelleye_multi_account_choose_payment_gateway' => 'paypal_express',
                        'store_countries' => '',
                        'shipping_class' => 'all',
                        'currency_code' => '',
                        'shipping_zone' => '',
                        'ec_site_owner_commission' => '',
                        'ec_site_owner_commission_label' => ''
                    );
                    foreach ($microprocessing_key_array as $key => $value) {
                        update_post_meta($post_id, $key, $value);
                    }
                    update_post_meta($post_id, 'vendor_id', $vendor_id);
                    angelleye_pfwma_log('New vendor rule created for vendor id ' . $vendor_id);
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_paypal_for_woocommerce_multi_account_rule_save_wc_vendor($vendor_id) {
        try {
            if (class_exists('WCV_Vendors')) {
                if (WCV_Vendors::is_pending($vendor_id) || !WCV_Vendors::is_vendor($vendor_id)) {
                    return;
                }
                $post_id = angelleye_is_vendor_account_exist($vendor_id);
                if ($post_id != false) {
                    $user = get_user_by('id', $vendor_id);
                    $email = get_user_meta($vendor_id, 'pv_paypal', true);
                    if (empty($email)) {
                        $email = get_user_meta($vendor_id, 'billing_email', true);
                    }
                    if (empty($email)) {
                        $email = $user->user_email;
                    }
                    if (!empty($email)) {
                        update_post_meta($post_id, 'woocommerce_paypal_express_sandbox_email', $email);
                        update_post_meta($post_id, 'woocommerce_paypal_express_email', $email);
                    }
                    $user_string = sprintf(
                            esc_html__('%1$s (#%2$s   %3$s)', 'woocommerce'), $user->display_name, absint($user->ID), $user->user_email
                    );
                    $woocommerce_paypal_express_account_name = get_post_meta($post_id, 'woocommerce_paypal_express_account_name', true);
                    if (empty($woocommerce_paypal_express_account_name)) {
                        update_post_meta($post_id, 'woocommerce_paypal_express_account_name', $user_string);
                    }
                } else {
                    $user = get_user_by('id', $vendor_id);
                    $user_string = sprintf(
                            esc_html__('%1$s (#%2$s   %3$s)', 'woocommerce'), $user->display_name, absint($user->ID), $user->user_email
                    );
                    $my_post = array(
                        'post_title' => $user_string,
                        'post_content' => '',
                        'post_status' => 'publish',
                        'post_author' => $vendor_id,
                        'post_type' => 'microprocessing'
                    );
                    $post_id = wp_insert_post($my_post);
                    do_action('update_angelleye_multi_account', $post_id);
                    $email = get_user_meta($vendor_id, 'pv_paypal', true);
                    if (empty($email)) {
                        $email = get_user_meta($vendor_id, 'billing_email', true);
                    }
                    if (empty($email)) {
                        $user = get_user_by('id', $vendor_id);
                        $email = $user->user_email;
                    }
                    $microprocessing_key_array = array(
                        'woocommerce_paypal_express_enable' => 'on',
                        'woocommerce_paypal_express_testmode' => $this->testmode,
                        'woocommerce_paypal_express_account_name' => $user_string,
                        'woocommerce_paypal_express_sandbox_email' => $email,
                        'woocommerce_paypal_express_sandbox_merchant_id' => '',
                        'woocommerce_paypal_express_sandbox_api_username' => '',
                        'woocommerce_paypal_express_sandbox_api_password' => '',
                        'woocommerce_paypal_express_sandbox_api_signature' => '',
                        'woocommerce_paypal_express_email' => $email,
                        'woocommerce_paypal_express_merchant_id' => '',
                        'woocommerce_paypal_express_api_username' => '',
                        'woocommerce_paypal_express_api_password' => '',
                        'woocommerce_paypal_express_api_signature' => '',
                        'woocommerce_paypal_express_api_condition_field' => 'transaction_amount',
                        'woocommerce_paypal_express_api_condition_sign' => 'greaterthan',
                        'woocommerce_paypal_express_api_condition_value' => '0',
                        'woocommerce_paypal_express_api_user_role' => 'all',
                        'woocommerce_paypal_express_api_user' => $vendor_id,
                        'woocommerce_paypal_express_api_product_ids' => array(),
                        'product_categories' => '',
                        'product_tags' => '',
                        'buyer_countries' => '',
                        'buyer_states' => '',
                        'woocommerce_priority' => '',
                        'angelleye_multi_account_choose_payment_gateway' => 'paypal_express',
                        'store_countries' => '',
                        'shipping_class' => 'all',
                        'currency_code' => '',
                        'shipping_zone' => '',
                        'ec_site_owner_commission' => '',
                        'ec_site_owner_commission_label' => ''
                    );
                    foreach ($microprocessing_key_array as $key => $value) {
                        update_post_meta($post_id, $key, $value);
                    }
                    update_post_meta($post_id, 'vendor_id', $vendor_id);
                    angelleye_pfwma_log('New vendor rule created for vendor id ' . $vendor_id);
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_paypal_for_woocommerce_multi_account_rule_save($vendor_id) {
        try {
            if (is_object($vendor_id) && isset($vendor_id->ID)) {
                $vendor_id = $vendor_id->ID;
            } elseif (is_object($vendor_id) && isset($vendor_id->store_id)) {
                $vendor_id = $vendor_id->store_id;
            }
            $dokan_profile_settings = get_user_meta($vendor_id, 'dokan_profile_settings', true);
            if (!empty($dokan_profile_settings)) {
                if ($this->is_ppcp_enable) {
                    $this->angelleye_paypal_for_woocommerce_multi_account_rule_save_dokan_ppcp($vendor_id);
                } else {
                    $this->angelleye_paypal_for_woocommerce_multi_account_rule_save_dokan($vendor_id);
                }
            } else {
                if ($this->is_ppcp_enable) {
                    $this->angelleye_paypal_for_woocommerce_multi_account_rule_save_wc_vendor_ppcp($vendor_id);
                } else {
                    $this->angelleye_paypal_for_woocommerce_multi_account_rule_save_wc_vendor($vendor_id);
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_paypal_for_woocommerce_multi_account_rule_save_dokan_ppcp($vendor_id) {
        try {
            if (function_exists('dokan')) {
                if (!dokan_is_user_seller($vendor_id)) {
                    return;
                }
                $post_id = angelleye_is_vendor_account_exist($vendor_id);
                if ($post_id != false) {
                    $user = get_user_by('id', $vendor_id);
                    $dokan_profile_settings = get_user_meta($vendor_id, 'dokan_profile_settings', true);
                    if (!empty($dokan_profile_settings['payment']['paypal']['email'])) {
                        $email = $dokan_profile_settings['payment']['paypal']['email'];
                    }
                    if (empty($email)) {
                        $email = get_user_meta($vendor_id, 'billing_email', true);
                    }
                    if (empty($email)) {
                        $email = $user->user_email;
                    }
                    if (!empty($email)) {
                        update_post_meta($post_id, 'woocommerce_angelleye_ppcp_sandbox_email_address', $email);
                        update_post_meta($post_id, 'woocommerce_angelleye_ppcp_email_address', $email);
                    }
                    $user_string = sprintf(
                            esc_html__('%1$s (#%2$s   %3$s)', 'woocommerce'), $user->display_name, absint($user->ID), $user->user_email
                    );
                    $woocommerce_angelleye_ppcp_account_name = get_post_meta($post_id, 'woocommerce_angelleye_ppcp_account_name', true);
                    if (empty($woocommerce_angelleye_ppcp_account_name)) {
                        update_post_meta($post_id, 'woocommerce_angelleye_ppcp_account_name', $user_string);
                    }
                } else {
                    $user = get_user_by('id', $vendor_id);
                    $user_string = sprintf(
                            esc_html__('%1$s (#%2$s   %3$s)', 'woocommerce'), $user->display_name, absint($user->ID), $user->user_email
                    );
                    $my_post = array(
                        'post_title' => $user_string,
                        'post_content' => '',
                        'post_status' => 'publish',
                        'post_author' => $vendor_id,
                        'post_type' => 'microprocessing'
                    );
                    $post_id = wp_insert_post($my_post);
                    do_action('update_angelleye_multi_account', $post_id);
                    $dokan_profile_settings = get_user_meta($vendor_id, 'dokan_profile_settings', true);
                    if (!empty($dokan_profile_settings['payment']['paypal']['email'])) {
                        $email = $dokan_profile_settings['payment']['paypal']['email'];
                    }
                    if (empty($email)) {
                        $email = get_user_meta($vendor_id, 'billing_email', true);
                    }
                    if (empty($email)) {
                        $user = get_user_by('id', $vendor_id);
                        $email = $user->user_email;
                    }
                    $microprocessing_key_array = array(
                        'woocommerce_angelleye_ppcp_enable' => 'on',
                        'woocommerce_angelleye_ppcp_testmode' => $this->testmode,
                        'woocommerce_angelleye_ppcp_account_name' => $user_string,
                        'woocommerce_angelleye_ppcp_sandbox_email_address' => $email,
                        'woocommerce_angelleye_ppcp_sandbox_merchant_id' => '',
                        'woocommerce_angelleye_ppcp_email_address' => $email,
                        'woocommerce_angelleye_ppcp_merchant_id' => '',
                        'woocommerce_paypal_express_api_condition_field' => 'transaction_amount',
                        'woocommerce_paypal_express_api_condition_sign' => 'greaterthan',
                        'woocommerce_paypal_express_api_condition_value' => '0',
                        'woocommerce_paypal_express_api_user_role' => 'all',
                        'woocommerce_paypal_express_api_user' => $vendor_id,
                        'woocommerce_paypal_express_api_product_ids' => array(),
                        'product_categories' => '',
                        'product_tags' => '',
                        'buyer_countries' => '',
                        'buyer_states' => '',
                        'woocommerce_priority' => '',
                        'angelleye_multi_account_choose_payment_gateway' => 'angelleye_ppcp',
                        'store_countries' => '',
                        'shipping_class' => 'all',
                        'currency_code' => '',
                        'shipping_zone' => '',
                        'ppcp_site_owner_commission' => '',
                        'ppcp_site_owner_commission_label' => '',
                     );
                    foreach ($microprocessing_key_array as $key => $value) {
                        update_post_meta($post_id, $key, $value);
                    }
                    update_post_meta($post_id, 'vendor_id', $vendor_id);
                    angelleye_pfwma_log('New vendor rule created for vendor id ' . $vendor_id);
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_paypal_for_woocommerce_multi_account_rule_save_wc_vendor_ppcp($vendor_id) {
        try {
            if (class_exists('WCV_Vendors')) {
                if (WCV_Vendors::is_pending($vendor_id) || !WCV_Vendors::is_vendor($vendor_id)) {
                    return;
                }
                $post_id = angelleye_is_vendor_account_exist($vendor_id);
                if ($post_id != false) {
                    $user = get_user_by('id', $vendor_id);
                    $email = get_user_meta($vendor_id, 'pv_paypal', true);
                    if (empty($email)) {
                        $email = get_user_meta($vendor_id, 'billing_email', true);
                    }
                    if (empty($email)) {
                        $email = $user->user_email;
                    }
                    if (!empty($email)) {
                        update_post_meta($post_id, 'woocommerce_angelleye_ppcp_sandbox_email_address', $email);
                        update_post_meta($post_id, 'woocommerce_angelleye_ppcp_email_address', $email);
                    }
                    $user_string = sprintf(
                            esc_html__('%1$s (#%2$s   %3$s)', 'woocommerce'), $user->display_name, absint($user->ID), $user->user_email
                    );
                    $woocommerce_angelleye_ppcp_account_name = get_post_meta($post_id, 'woocommerce_angelleye_ppcp_account_name', true);
                    if (empty($woocommerce_angelleye_ppcp_account_name)) {
                        update_post_meta($post_id, 'woocommerce_angelleye_ppcp_account_name', $user_string);
                    }
                } else {
                    $user = get_user_by('id', $vendor_id);
                    $user_string = sprintf(
                            esc_html__('%1$s (#%2$s   %3$s)', 'woocommerce'), $user->display_name, absint($user->ID), $user->user_email
                    );
                    $my_post = array(
                        'post_title' => $user_string,
                        'post_content' => '',
                        'post_status' => 'publish',
                        'post_author' => $vendor_id,
                        'post_type' => 'microprocessing'
                    );
                    $post_id = wp_insert_post($my_post);
                    do_action('update_angelleye_multi_account', $post_id);
                    $email = get_user_meta($vendor_id, 'pv_paypal', true);
                    if (empty($email)) {
                        $email = get_user_meta($vendor_id, 'billing_email', true);
                    }
                    if (empty($email)) {
                        $user = get_user_by('id', $vendor_id);
                        $email = $user->user_email;
                    }
                    $microprocessing_key_array = array(
                        'woocommerce_angelleye_ppcp_enable' => 'on',
                        'woocommerce_angelleye_ppcp_testmode' => $this->testmode,
                        'woocommerce_angelleye_ppcp_account_name' => $user_string,
                        'woocommerce_angelleye_ppcp_sandbox_email_address' => $email,
                        'woocommerce_angelleye_ppcp_sandbox_merchant_id' => '',
                        'woocommerce_angelleye_ppcp_email_address' => $email,
                        'woocommerce_angelleye_ppcp_merchant_id' => '',
                        'woocommerce_paypal_express_api_condition_field' => 'transaction_amount',
                        'woocommerce_paypal_express_api_condition_sign' => 'greaterthan',
                        'woocommerce_paypal_express_api_condition_value' => '0',
                        'woocommerce_paypal_express_api_user_role' => 'all',
                        'woocommerce_paypal_express_api_user' => $vendor_id,
                        'woocommerce_paypal_express_api_product_ids' => array(),
                        'product_categories' => '',
                        'product_tags' => '',
                        'buyer_countries' => '',
                        'buyer_states' => '',
                        'woocommerce_priority' => '',
                        'angelleye_multi_account_choose_payment_gateway' => 'angelleye_ppcp',
                        'store_countries' => '',
                        'shipping_class' => 'all',
                        'currency_code' => '',
                        'shipping_zone' => '',
                        'ppcp_site_owner_commission' => '',
                        'ppcp_site_owner_commission_label' => '',
                    );
                    foreach ($microprocessing_key_array as $key => $value) {
                        update_post_meta($post_id, $key, $value);
                    }
                    update_post_meta($post_id, 'vendor_id', $vendor_id);
                    angelleye_pfwma_log('New vendor rule created for vendor id ' . $vendor_id);
                }
            }
        } catch (Exception $ex) {
            
        }
    }
}
