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
class Paypal_For_Woocommerce_Multi_Account_Management_Admin_PayPal_Payflow {

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
            $order = wc_get_order($order_id);
            $_multi_account_api_username = $order->get_meta('_multi_account_api_username', true);
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
            $order = wc_get_order($order_id);
            $multi_account_api_username = $order->get_meta('_multi_account_api_username', true);
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
                        'compare' => '='
                    ),
                    array(
                        'key' => 'woocommerce_paypal_pro_payflow_testmode',
                        'value' => ($gateways->testmode == true) ? 'on' : '',
                        'compare' => '='
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

                    $checkout_custom_fields = angelleye_display_checkout_custom_field();
                    if (!empty($checkout_custom_fields)) {
                        foreach ($checkout_custom_fields as $field_key => $field_data) {
                            $custom_field_value = get_post_meta($value->ID, $field_key, true);
                            if (!empty($custom_field_value)) {
                                if (!empty($order_id) && $order_id > 0) {
                                    $order = wc_get_order($order_id);
                                    $field_order_value = $order->get_meta($field_key, true);
                                    if (empty($field_order_value)) {
                                        $passed_rules['custom_fields'] = true;
                                    } elseif (!empty($field_order_value) && $field_order_value == $custom_field_value) {
                                        $passed_rules['custom_fields'] = true;
                                    } else {
                                        $passed_rules['custom_fields'] = '';
                                        break;
                                    }
                                }
                            } else {
                                $passed_rules['custom_fields'] = true;
                            }
                        }
                    } else {
                        $passed_rules['custom_fields'] = true;
                    }
                    if (empty($passed_rules['custom_fields'])) {
                        continue;
                    }

                    // Base Country
                    $buyer_countries = get_post_meta($value->ID, 'buyer_countries', true);
                    if (!empty($buyer_countries)) {
                        foreach ($buyer_countries as $buyer_countries_key => $buyer_countries_value) {
                            if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_pro_payflow') {
                                if (!empty($order_id) && $order_id > 0) {
                                    $order = wc_get_order($order_id);
                                    $billing_country = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_country : $order->get_billing_country();
                                    $shipping_country = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country();
                                    if (!empty($billing_country) && $billing_country == $buyer_countries_value) {
                                        $passed_rules['buyer_countries'] = true;
                                    } elseif (!empty($shipping_country) && $shipping_country == $buyer_countries_value) {
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
                    
                    
                    $buyer_states = get_post_meta($value->ID, 'buyer_states', true);
                    if (!empty($buyer_states)) {
                        foreach ($buyer_states as $buyer_states_key => $buyer_states_value) {
                            if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_pro_payflow') {
                                if (!empty($order_id) && $order_id > 0) {
                                    $order = wc_get_order($order_id);
                                    $billing_state = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_state : $order->get_billing_state();
                                    $shipping_state = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state();
                                    if (!empty($billing_state) && $billing_state == $buyer_states_value) {
                                        $passed_rules['buyer_states'] = true;
                                    } elseif (!empty($shipping_state) && $shipping_state == $buyer_states_value) {
                                        $passed_rules['buyer_states'] = true;
                                    }
                                }
                            }
                        }
                    } else {
                        $passed_rules['buyer_states'] = true;
                    }
                    if (empty($passed_rules['buyer_states'])) {
                        unset($result[$key]);
                        unset($passed_rules);
                        continue;
                    }

                    $postcode_string = get_post_meta($value->ID, 'postcode', true);
                    if (!empty($postcode_string)) {
                        $postcode = explode(',', $postcode_string);
                        foreach ($postcode as $postcode_key => $postcode_value) {
                            if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal_pro_payflow') {
                                if (!empty($order_id) && $order_id > 0) {
                                    $order = wc_get_order($order_id);
                                    $billing_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_postcode : $order->get_billing_postcode();
                                    $shipping_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_postcode : $order->get_shipping_postcode();
                                    if (!empty($billing_postcode) && $billing_postcode == $postcode_value) {
                                        $passed_rules['postcode'] = true;
                                        break;
                                    } elseif (!empty($shipping_postcode) && $shipping_postcode == $postcode_value) {
                                        $passed_rules['postcode'] = true;
                                        break;
                                    }
                                }
                            }
                        }
                    } else {
                        $passed_rules['postcode'] = true;
                    }
                    if (empty($passed_rules['postcode'])) {
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

                    if (isset(WC()->cart) && WC()->cart->is_empty()) {
                        foreach ($order->get_items() as $cart_item_key => $values) {
                            $line_item = $values->get_data();
                            $product = version_compare(WC_VERSION, '3.0', '<') ? $order->get_product_from_item($values) : $values->get_product();
                            $product_exists = is_object($product);
                            if ($product_exists == false) {
                                $product_id = apply_filters('angelleye_multi_account_get_product_id', '', $cart_item_key);
                                if (!empty($product_id)) {
                                    $product = wc_get_product($product_id);
                                } else {
                                    continue;
                                }
                            }
                            $product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
                            // Categories
                            $woo_product_categories = wp_get_post_terms($product_id, apply_filters('angelleye_get_product_categories', array('product_cat')), array('fields' => 'ids'));
                            $woo_product_categories = angelleye_get_product_cat($woo_product_categories);
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
                            $cart_products_id = array();
                            if(isset($line_item['variation_id'])) {
                                $cart_products_id[] = $cart_item['variation_id'];
                            }
                            $cart_products_id[] = $product_id;
                            if (!empty($product_ids)) {
                                if (!array_intersect((array) $cart_products_id, $product_ids)) {
                                    unset($result[$key]);
                                    unset($passed_rules);
                                    continue;
                                }
                            }
                        }
                    } else {
                        if (isset(WC()->cart) && sizeof(WC()->cart->get_cart()) > 0) {
                            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                                $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                                $product = wc_get_product($product_id);
                                // Categories
                                $woo_product_categories = wp_get_post_terms($product_id, apply_filters('angelleye_get_product_categories', array('product_cat')), array('fields' => 'ids'));
                                $woo_product_categories = angelleye_get_product_cat($woo_product_categories);
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
                                $cart_products_id = array();
                                if(isset($cart_item['variation_id'])) {
                                    $cart_products_id[] = $cart_item['variation_id'];
                                }
                                $cart_products_id[] = $product_id;
                                if (!empty($product_ids)) {
                                    if (!array_intersect((array) $cart_products_id, $product_ids)) {
                                        unset($result[$key]);
                                        unset($passed_rules);
                                        continue;
                                    }
                                }

                                $post_author_id = get_post_field('post_author', $product_id);
                                $woocommerce_paypal_express_api_user = get_post_meta($value->ID, 'woocommerce_paypal_express_api_user', true);
                                if (!empty($woocommerce_paypal_express_api_user) && $woocommerce_paypal_express_api_user != 'all') {
                                    if ($post_author_id != $woocommerce_paypal_express_api_user) {
                                        unset($result[$key]);
                                        unset($passed_rules);
                                        continue;
                                    }
                                }
                                
                                if (isset(WC()->cart) && sizeof(WC()->cart->get_cart()) > 0) {
                                    $mul_shipping_zone = get_post_meta($value->ID, 'shipping_zone', true);
                                    if (!empty($mul_shipping_zone) && $mul_shipping_zone != 'all') {
                                        $shipping_packages =  WC()->cart->get_shipping_packages();
                                        if( !empty($shipping_packages) ) {
                                            $woo_shipping_zone = wc_get_shipping_zone( reset( $shipping_packages ) );
                                            $zone_id = $woo_shipping_zone->get_id();
                                            if ($zone_id != $mul_shipping_zone) {
                                                unset($result[$key]);
                                                unset($passed_rules);
                                                continue;
                                            }
                                        }
                                    }
                                }

                                $product_shipping_class = $product->get_shipping_class_id();
                                $shipping_class = get_post_meta($value->ID, 'shipping_class', true);
                                if (!empty($shipping_class) && $shipping_class != 'all') {
                                    if ($product_shipping_class != $shipping_class) {
                                        $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                        unset($result[$key]);
                                        unset($passed_rules);
                                        continue;
                                    }
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
                return angelleye_get_closest_amount($this->final_associate_account, $order_total);
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
                        'compare' => '='
                    ),
                    array(
                        'key' => 'woocommerce_paypal_pro_payflow_testmode',
                        'value' => ($gateways->testmode == true) ? 'on' : '',
                        'compare' => '='
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
                        'compare' => '='
                    ),
                    array(
                        'key' => 'woocommerce_paypal_express_testmode',
                        'value' => ($gateways->testmode == true) ? 'on' : '',
                        'compare' => '='
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
                return angelleye_get_closest_amount($this->final_associate_account, $order_total);
            }
        }
    }

    public function angelleye_paypal_for_woocommerce_multi_account_api_paypal_payflow($gateways, $request = null, $order_id = null) {
        if ($request == null) {
            $gateway_setting = $gateways;
        } elseif ($gateways == null) {
            $gateways = $request;
            $gateway_setting = $gateways;
        } else {
            $gateway_setting = $gateways;
        }

        if ($order_id == null) {
            if (is_null(WC()->cart)) {
                return;
            }
            if (isset(WC()->cart) && WC()->cart->is_empty()) {
                return false;
            }
        }
        $angelleye_payment_load_balancer = get_option('angelleye_payment_load_balancer', '');
        if (!empty($angelleye_payment_load_balancer)) {
            $microprocessing_value = $this->angelleye_get_account_for_payflow_payment_load_balancer($gateways, $gateway_setting, $order_id);
        } elseif ($this->is_angelleye_multi_account_used($order_id)) {
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
                    if (isset($request->paypal_user)) {
                        $request->paypal_user = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user'];
                        $request->paypal_password = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_password'];
                        $request->paypal_vendor = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor'];
                        $request->paypal_partner = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner'];
                    }
                    if (class_exists('WooCommerce') && WC()->session) {
                        WC()->session->set('multi_account_api_username', $gateway_setting->paypal_user);
                    }
                    return;
                }
            } else {
                if (!empty($microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_user']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_api_password']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_vendor']) && !empty($microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner'])) {
                    $gateway_setting->paypal_user = $microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_user'];
                    $gateway_setting->paypal_password = $microprocessing_value['woocommerce_paypal_pro_payflow_api_password'];
                    $gateway_setting->paypal_vendor = $microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_vendor'];
                    $gateway_setting->paypal_partner = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner'];
                    if (isset($request->paypal_user)) {
                        $request->paypal_user = $microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_user'];
                        $request->paypal_password = $microprocessing_value['woocommerce_paypal_pro_payflow_api_password'];
                        $request->paypal_vendor = $microprocessing_value['woocommerce_paypal_pro_payflow_api_paypal_vendor'];
                        $request->paypal_partner = $microprocessing_value['woocommerce_paypal_pro_payflow_sandbox_api_paypal_partner'];
                    }
                    if (class_exists('WooCommerce') && WC()->session) {
                        WC()->session->set('multi_account_api_username', $gateway_setting->paypal_user);
                    }
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
                        'compare' => '='
                    ),
                    array(
                        'key' => 'woocommerce_paypal_pro_payflow_api_paypal_user',
                        'value' => $_multi_account_api_username,
                        'compare' => '='
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

    public function angelleye_get_account_for_payflow_payment_load_balancer($gateways, $gateway_setting, $order_id) {
        if (!isset($gateways->testmode)) {
            return;
        }
        if(!empty($order_id)) {
            $order = wc_get_order($order_id);
        }
        if ($gateways->testmode == true) {
            $option_key = 'angelleye_multi_payflow_payment_load_balancer_sandbox';
        } else {
            $option_key = 'angelleye_multi_payflow_payment_load_balancer';
        }
        $is_account_found = false;
        $order = wc_get_order($order_id);
        $used_account = $order->get_meta('_multi_account_api_username_load_balancer', true);
        if ($used_account == 'default') {
            return;
        }
        if (empty($used_account)) {
            $payflow_accounts = get_option($option_key);
            if (!empty($payflow_accounts)) {
                foreach ($payflow_accounts as $key => $account) {
                    if (empty($account['is_used'])) {
                        if ($key != 'default' && false === get_post_status($key)) {
                            unset($payflow_accounts[$key]);
                        } else {
                            $account['is_used'] = 'yes';
                            $is_account_found = true;
                            $payflow_accounts[$key] = $account;
                            $used_account = $account['multi_account_id'];
                            $order->update_meta_data('_multi_account_api_username_load_balancer', $used_account);
                            $order->save_meta_data();
                            update_option($option_key, $payflow_accounts);
                            break;
                        }
                    }
                }
                if ($is_account_found == false) {
                    foreach ($payflow_accounts as $key => $account) {
                        $account['is_used'] = '';
                        $payflow_accounts[$key] = $account;
                    }
                    foreach ($payflow_accounts as $key => $account) {
                        if ($key != 'default' && false === get_post_status($key)) {
                            unset($payflow_accounts[$key]);
                        } else {
                            $account['is_used'] = 'yes';
                            $payflow_accounts[$key] = $account;
                            $used_account = $account['multi_account_id'];
                            $order->update_meta_data('_multi_account_api_username_load_balancer', $used_account);
                            $order->save_meta_data();
                            update_option($option_key, $payflow_accounts);
                            break;
                        }
                    }
                }
            }
        }
        if ($used_account == 'default') {
            return;
        }
        if (!empty($used_account)) {
            $post_meta = get_post_meta($used_account);
            if (!empty($post_meta)) {
                $microprocessing_value = array();
                foreach ($post_meta as $key => $value) {
                    $microprocessing_value[$key] = isset($value[0]) ? $value[0] : '';
                }
                return $microprocessing_value;
            }
        }
    }

}
