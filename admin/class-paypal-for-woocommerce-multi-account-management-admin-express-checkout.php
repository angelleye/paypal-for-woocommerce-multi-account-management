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
class Paypal_For_Woocommerce_Multi_Account_Management_Admin_Express_Checkout {

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
        if (!isset($gateways->testmode)) {
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
        if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_express') {
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
        if ($total_posts > 0) {
            foreach ($result as $key => $value) {
                $passed_rules = array();
                if (!empty($value->ID)) {
                    $currency_code = get_post_meta($value->ID, 'currency_code', true);
                    if (!empty($currency_code)) {
                        $store_currency = get_woocommerce_currency();
                        if ($store_currency != $currency_code) {
                            unset($result[$key]);
                            unset($passed_rules);
                            continue;
                        }
                    }
                    $buyer_countries = get_post_meta($value->ID, 'buyer_countries', true);
                    if (!empty($buyer_countries)) {
                        foreach ($buyer_countries as $buyer_countries_key => $buyer_countries_value) {
                            if (!empty($order_id) && $order_id > 0) {
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
                    if ( isset(WC()->cart) && WC()->cart->is_empty()) {
                        foreach ($order->get_items() as $cart_item_key => $values) {
                            $product = $order->get_product_from_item($values);
                            $product_id = $product->get_id();
                            $woo_product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
                            $product_categories = get_post_meta($value->ID, 'product_categories', true);
                            if (!empty($product_categories)) {
                                if (!array_intersect($product_categories, $woo_product_categories)) {
                                    unset($result[$key]);
                                    unset($passed_rules);
                                    continue;
                                }
                            }
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
                    } else {
                        if (isset(WC()->cart) && sizeof(WC()->cart->get_cart()) > 0) {
                            $cart_loop_pass = 0;
                            $cart_loop_not_pass = 0;
                            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                                $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                                $woo_product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
                                $product_categories = get_post_meta($value->ID, 'product_categories', true);
                                if (!empty($product_categories)) {
                                    if (!array_intersect($product_categories, $woo_product_categories)) {
                                        //unset($result[$key]);
                                        //unset($passed_rules);
                                        $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                        continue;
                                    }
                                }
                                $woo_product_tag = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'ids'));
                                $product_tags = get_post_meta($value->ID, 'product_tags', true);
                                if (!empty($product_tags)) {
                                    if (!array_intersect($product_tags, $woo_product_tag)) {
                                        //unset($result[$key]);
                                        //unset($passed_rules);
                                        $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                        continue;
                                    }
                                }
                                $product_ids = get_post_meta($value->ID, 'woocommerce_paypal_express_api_product_ids', true);
                                if (!empty($product_ids)) {
                                    if (!array_intersect((array) $product_id, $product_ids)) {
                                       // unset($result[$key]);
                                       // unset($passed_rules);
                                        $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                        continue;
                                    }
                                }
                                $cart_loop_pass = $cart_loop_pass + 1;
                            }
                            if (isset(WC()->cart) && sizeof(WC()->cart->get_cart()) > 0) {
                                if($cart_loop_pass == 0) {
                                    unset($result[$key]);
                                    unset($passed_rules);
                                }
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
                    if (!empty($microprocessing_array['woocommerce_paypal_express_api_condition_sign'][0]) && isset($microprocessing_array['woocommerce_paypal_express_api_condition_value'][0])) {
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
        if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_express') {
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
            for ($i = 1; $i < $size; $i++) {
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

    public function angelleye_paypal_for_woocommerce_multi_account_api_paypal_express($gateways, $current = null, $order_id = null, $request = null) {
        if ($current == null) {
            $gateway_setting = $gateways;
        } else {
            $gateway_setting = $current;
        }
        if (!isset($gateways) || !isset($gateways->testmode)) {
            return false;
        }
        if ($order_id == null) {
            if (is_null(WC()->cart)) {
                return;
            }
            if (isset(WC()->cart) && WC()->cart->is_empty()) {
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
                if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_express') {
                    if (!empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_username']) && !empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_password']) && !empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_signature'])) {
                        $gateway_setting->api_username = $microprocessing_value['woocommerce_paypal_express_sandbox_api_username'];
                        $gateway_setting->api_password = $microprocessing_value['woocommerce_paypal_express_sandbox_api_password'];
                        $gateway_setting->api_signature = $microprocessing_value['woocommerce_paypal_express_sandbox_api_signature'];
                        if (isset($current->api_username)) {
                            $current->api_username = $microprocessing_value['woocommerce_paypal_express_sandbox_api_username'];
                            $current->api_password = $microprocessing_value['woocommerce_paypal_express_sandbox_api_password'];
                            $current->api_signature = $microprocessing_value['woocommerce_paypal_express_sandbox_api_signature'];
                        }
                        if (class_exists('WooCommerce') && WC()->session) {
                            WC()->session->set('multi_account_api_username', $gateway_setting->api_username);
                        }
                        return;
                    }
                }
            } else {
                if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_express') {
                    if (!empty($microprocessing_value['woocommerce_paypal_express_api_username']) && !empty($microprocessing_value['woocommerce_paypal_express_api_password']) && !empty($microprocessing_value['woocommerce_paypal_express_api_signature'])) {
                        $gateway_setting->api_username = $microprocessing_value['woocommerce_paypal_express_api_username'];
                        $gateway_setting->api_password = $microprocessing_value['woocommerce_paypal_express_api_password'];
                        $gateway_setting->api_signature = $microprocessing_value['woocommerce_paypal_express_api_signature'];
                        if (isset($current->api_username)) {
                            $current->api_username = $microprocessing_value['woocommerce_paypal_express_api_username'];
                            $current->api_password = $microprocessing_value['woocommerce_paypal_express_api_password'];
                            $current->api_signature = $microprocessing_value['woocommerce_paypal_express_api_signature'];
                        }
                        if (class_exists('WooCommerce') && WC()->session) {
                            WC()->session->set('multi_account_api_username', $gateway_setting->api_username);
                        }
                        return;
                    }
                }
            }
        }
    }

    public function angelleye_get_multi_account_details_by_api_user_name($gateway_setting, $_multi_account_api_username) {
        $microprocessing = array();
        if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_express') {
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
        if (!empty($args)) {
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

}
