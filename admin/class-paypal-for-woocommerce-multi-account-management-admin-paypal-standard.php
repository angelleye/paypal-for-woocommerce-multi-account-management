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
class Paypal_For_Woocommerce_Multi_Account_Management_Admin_PayPal_Standard {

    private $plugin_name;
    private $version;
    public $gateway;
    public $key;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->key = 'paypal';
    }

    public function angelleye_woocommerce_paypal_args($request, $order) {
        global $user_ID;
        $this->gateway = $this->get_wc_gateway();
        $current_user_roles = array();
        if (!isset($this->gateway->testmode)) {
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

        $args = array(
            'post_type' => 'microprocessing',
            'order' => 'DESC',
            'orderby' => 'order_clause',
            'meta_key' => 'woocommerce_priority',
            'meta_query' => array(
                'order_clause' => array(
                    'key' => 'woocommerce_priority',
                    'type' => 'NUMERIC'
                ),
                'relation' => 'AND',
                array(
                    'key' => 'woocommerce_paypal_enable',
                    'value' => 'on',
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'woocommerce_paypal_testmode',
                    'value' => ($gateways->testmode == true) ? 'on' : '',
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'woocommerce_priority',
                    'compare' => 'EXISTS'
                )
            )
        );

        $query = new WP_Query();
        $result = $query->query($args);
        $total_posts = $query->found_posts;
        if ($total_posts > 0) {
            foreach ($result as $key => $value) {
                $passed_rules = array();
                $cart_loop_pass = 0;
                $cart_loop_not_pass = 0;
                $this->angelleye_is_taxable = 0;
                $this->angelleye_needs_shipping = 0;
                if (!empty($value->ID)) {
                    $microprocessing_array = get_post_meta($value->ID);
                    if (!empty($microprocessing_array['woocommerce_paypal_api_condition_sign'][0]) && isset($microprocessing_array['woocommerce_paypal_api_condition_value'][0])) {
                        switch ($microprocessing_array['woocommerce_paypal_api_condition_sign'][0]) {
                            case 'equalto':
                                if ($order_total == $microprocessing_array['woocommerce_paypal_api_condition_value'][0]) {
                                    
                                } else {
                                    unset($result[$key]);
                                    unset($passed_rules);
                                }
                                break;
                            case 'lessthan':
                                if ($order_total < $microprocessing_array['woocommerce_paypal_api_condition_value'][0]) {
                                    
                                } else {
                                    unset($result[$key]);
                                    unset($passed_rules);
                                }
                                break;
                            case 'greaterthan':
                                if ($order_total > $microprocessing_array['woocommerce_paypal_api_condition_value'][0]) {
                                    
                                } else {
                                    unset($result[$key]);
                                    unset($passed_rules);
                                }
                                break;
                        }
                    }
                    if (!isset($result[$key])) {
                        continue;
                    }
                    $currency_code = get_post_meta($value->ID, 'currency_code', true);
                    if (!empty($currency_code)) {
                        $store_currency = get_woocommerce_currency();
                        if ($store_currency != $currency_code) {
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
                        continue;
                    }
                    $store_countries = get_post_meta($value->ID, 'store_countries', true);
                    if (!empty($store_countries)) {
                        if (WC()->countries->get_base_country() != $store_countries) {
                            continue;
                        }
                    }
                    $woocommerce_paypal_api_user_role = get_post_meta($value->ID, 'woocommerce_paypal_api_user_role', true);
                    if (!empty($woocommerce_paypal_api_user_role)) {
                        if (is_user_logged_in()) {
                            if (in_array($woocommerce_paypal_api_user_role, (array) $user->roles, true) || $woocommerce_paypal_api_user_role == 'all') {
                                $passed_rules['woocommerce_paypal_api_user_role'] = true;
                            } else {
                                unset($result[$key]);
                                unset($passed_rules);
                                continue;
                            }
                        }
                    }

                    foreach ($order->get_items() as $cart_item_key => $values) {
                        $product = $order->get_product_from_item($values);
                        $product_id = $product->get_id();
                        $this->map_item_with_account[$product_id]['product_id'] = $product_id;
                        $this->map_item_with_account[$product_id]['order_item_id'] = $cart_item_key;
                        if ($product->is_taxable()) {
                            $this->map_item_with_account[$product_id]['is_taxable'] = true;
                            $this->angelleye_is_taxable = $this->angelleye_is_taxable + 1;
                        }
                        if ($product->needs_shipping()) {
                            $this->map_item_with_account[$product_id]['needs_shipping'] = true;
                            $this->angelleye_needs_shipping = $this->angelleye_needs_shipping + 1;
                        }
                        if (isset($this->map_item_with_account[$product_id]['multi_account_id']) && $this->map_item_with_account[$product_id]['multi_account_id'] != 'default') {
                            continue;
                        }
                        if (!isset($this->map_item_with_account[$product_id]['multi_account_id'])) {
                            $this->map_item_with_account[$product_id]['multi_account_id'] = 'default';
                        }
                        $woo_product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
                        $product_categories = get_post_meta($value->ID, 'product_categories', true);
                        if (!empty($product_categories)) {
                            if (!array_intersect($product_categories, $woo_product_categories)) {
                                $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                continue;
                            }
                        }
                        $woo_product_tag = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'ids'));
                        $product_tags = get_post_meta($value->ID, 'product_tags', true);
                        if (!empty($product_tags)) {
                            if (!array_intersect($product_tags, $woo_product_tag)) {
                                $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                continue;
                            }
                        }
                        $product_ids = get_post_meta($value->ID, 'woocommerce_paypal_api_product_ids', true);
                        if (!empty($product_ids)) {
                            if (!array_intersect((array) $product_id, $product_ids)) {
                                $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                continue;
                            }
                        }
                        $this->map_item_with_account[$product_id]['multi_account_id'] = $value->ID;
                        if ($gateways->testmode == true) {
                            if (isset($microprocessing_array['woocommerce_paypal_sandbox_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_sandbox_email'][0])) {
                                $this->map_item_with_account[$product_id]['email'] = $microprocessing_array['woocommerce_paypal_sandbox_email'][0];
                            } elseif (isset($microprocessing_array['woocommerce_paypal_sandbox_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_paypal_sandbox_merchant_id'][0])) {
                                $this->map_item_with_account[$product_id]['email'] = $microprocessing_array['woocommerce_paypal_sandbox_merchant_id'][0];
                            } else {
                                $this->map_item_with_account[$product_id]['email'] = $this->angelleye_get_email_address_for_multi($value->ID, $microprocessing_array, $gateways);
                            }
                            if ($this->angelleye_is_multi_account_api_set($microprocessing_array, $gateways)) {
                                $this->map_item_with_account[$product_id]['is_api_set'] = true;
                            } else {
                                $this->map_item_with_account[$product_id]['is_api_set'] = false;
                            }
                        } else {
                            if (isset($microprocessing_array['woocommerce_paypal_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_email'][0])) {
                                $this->map_item_with_account[$product_id]['email'] = $microprocessing_array['woocommerce_paypal_email'][0];
                            } elseif (isset($microprocessing_array['woocommerce_paypal_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_paypal_merchant_id'][0])) {
                                $this->map_item_with_account[$product_id]['email'] = $microprocessing_array['woocommerce_paypal_merchant_id'][0];
                            } else {
                                $this->map_item_with_account[$product_id]['email'] = $this->angelleye_get_email_address_for_multi($value->ID, $microprocessing_array, $gateways);
                            }
                            if ($this->angelleye_is_multi_account_api_set($microprocessing_array, $gateways)) {
                                $this->map_item_with_account[$product_id]['is_api_set'] = true;
                            } else {
                                $this->map_item_with_account[$product_id]['is_api_set'] = false;
                            }
                        }
                        $cart_loop_pass = $cart_loop_pass + 1;
                    }
                }
                unset($passed_rules);
            }
            if ((isset($result) && count($result)) > 0 && (isset($this->map_item_with_account) && count($this->map_item_with_account))) {
                return $this->angelleye_modified_ec_parallel_parameter($request, $gateways, $order_id);
            }
        }
        return $request;
    }

    public function get_wc_gateway() {
        global $woocommerce;
        $gateways = $woocommerce->payment_gateways->payment_gateways();
        return $gateways[$this->key];
    }

}
