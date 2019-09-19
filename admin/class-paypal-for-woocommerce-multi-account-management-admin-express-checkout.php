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
    public $map_item_with_account;
    public $angelleye_is_taxable;
    public $angelleye_needs_shipping;

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
        $this->map_item_with_account = array();
        $this->angelleye_is_taxable = 0;
        $this->angelleye_needs_shipping = 0;
    }

    public function angelleye_get_account_for_ec_parallel_payments($gateways, $gateway_setting, $order_id, $request) {
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
                        'type' => 'NUMERIC'
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
                $cart_loop_pass = 0;
                $cart_loop_not_pass = 0;
                if (!empty($value->ID)) {
                    $microprocessing_array = get_post_meta($value->ID);
                    if (!empty($microprocessing_array['woocommerce_paypal_express_api_condition_sign'][0]) && isset($microprocessing_array['woocommerce_paypal_express_api_condition_value'][0])) {
                        switch ($microprocessing_array['woocommerce_paypal_express_api_condition_sign'][0]) {
                            case 'equalto':
                                if ($order_total == $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0]) {
                                    
                                } else {
                                    unset($result[$key]);
                                    unset($passed_rules);
                                }
                                break;
                            case 'lessthan':
                                if ($order_total < $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0]) {
                                    
                                } else {
                                    unset($result[$key]);
                                    unset($passed_rules);
                                }
                                break;
                            case 'greaterthan':
                                if ($order_total > $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0]) {
                                    
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
                            $product = $order->get_product_from_item($values);
                            $product_id = $product->get_id();
                            $this->map_item_with_account[$cart_item_key]['multi_account_id'] = 'default';
                            if ($product->is_taxable()) {
                                $this->map_item_with_account[$cart_item_key]['is_taxable'] = true;
                                $this->angelleye_is_taxable = $this->angelleye_is_taxable + 1;
                            }
                            if ($product->needs_shipping()) {
                                $this->map_item_with_account[$cart_item_key]['needs_shipping'] = true;
                                $this->angelleye_needs_shipping = $this->angelleye_needs_shipping + 1;
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
                            $product_ids = get_post_meta($value->ID, 'woocommerce_paypal_express_api_product_ids', true);
                            if (!empty($product_ids)) {
                                if (!array_intersect((array) $product_id, $product_ids)) {
                                    $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                    continue;
                                }
                            }
                            $this->map_item_with_account[$cart_item_key]['multi_account_id'] = $value->ID;
                            $cart_loop_pass = $cart_loop_pass + 1;
                        }
                    } else {
                        if (isset(WC()->cart) && sizeof(WC()->cart->get_cart()) > 0) {
                            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                                $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                                $product = wc_get_product($product_id);
                                $this->map_item_with_account[$cart_item_key]['multi_account_id'] = 'default';

                                if ($product->is_taxable()) {
                                    $this->map_item_with_account[$cart_item_key]['is_taxable'] = true;
                                    $this->angelleye_is_taxable = $this->angelleye_is_taxable + 1;
                                }
                                if ($product->needs_shipping()) {
                                    $this->map_item_with_account[$cart_item_key]['needs_shipping'] = true;
                                    $this->angelleye_needs_shipping = $this->angelleye_needs_shipping + 1;
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
                                $product_ids = get_post_meta($value->ID, 'woocommerce_paypal_express_api_product_ids', true);
                                if (!empty($product_ids)) {
                                    if (!array_intersect((array) $product_id, $product_ids)) {
                                        $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                        continue;
                                    }
                                }
                                $this->map_item_with_account[$cart_item_key]['multi_account_id'] = $value->ID;
                                if ($gateways->testmode == true) {
                                    if (isset($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0])) {
                                        $this->map_item_with_account[$cart_item_key]['email'] = $microprocessing_array['woocommerce_paypal_express_sandbox_email'][0];
                                    } elseif (isset($microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0])) {
                                        $this->map_item_with_account[$cart_item_key]['email'] = $microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0];
                                    }
                                } else {
                                    if (isset($microprocessing_array['woocommerce_paypal_express_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_email'][0])) {
                                        $this->map_item_with_account[$cart_item_key]['email'] = $microprocessing_array['woocommerce_paypal_express_email'][0];
                                    } elseif (isset($microprocessing_array['woocommerce_paypal_express_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_merchant_id'][0])) {
                                        $this->map_item_with_account[$cart_item_key]['email'] = $microprocessing_array['woocommerce_paypal_express_merchant_id'][0];
                                    }
                                }
                                $cart_loop_pass = $cart_loop_pass + 1;
                            }
                        }
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

    public function angelleye_paypal_for_woocommerce_multi_account_api_paypal_express($gateways, $current = null, $order_id = null, $request = null) {
        if (empty($request)) {
            return;
        }
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
        if (!empty($_GET['pp_action']) && $_GET['pp_action'] == 'set_express_checkout') {
            return $this->angelleye_get_account_for_ec_parallel_payments($gateways, $gateway_setting, $order_id, $request);
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

    public function angelleye_modified_ec_parallel_parameter($request, $gateways, $order_id) {
        $new_payments = array();
        if (!empty($request['Payments'])) {
            $old_payments = $request['Payments'];
            unset($request['Payments']);
        } else {
            $old_payments = array();
        }
        $this->angelleye_is_taxable = 0;
        $this->angelleye_needs_shipping = 0;
        if (isset(WC()->cart) && sizeof(WC()->cart->get_cart()) > 0) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                if (array_key_exists($cart_item_key, $this->map_item_with_account)) {
                    $multi_account_info = $this->map_item_with_account[$cart_item_key];
                    $Payment = array(
                        'amt' => '10.00',
                        'currencycode' => '',
                        'itemamt' => '7.00',
                        'shippingamt' => '3.00',
                        'taxamt' => '',
                        'custom' => isset($old_payments[0]['custom']) ? $old_payments[0]['custom'] : '',
                        'invnum' => isset($old_payments[0]['invnum']) ? $old_payments[0]['invnum'] : '',
                        'notifyurl' => isset($old_payments[0]['notifyurl']) ? $old_payments[0]['notifyurl'] : '',
                        'shiptoname' => isset($old_payments[0]['shiptoname']) ? $old_payments[0]['shiptoname'] : '',
                        'shiptostreet' => isset($old_payments[0]['shiptostreet']) ? $old_payments[0]['shiptostreet'] : '',
                        'shiptostreet2' => isset($old_payments[0]['shiptostreet2']) ? $old_payments[0]['shiptostreet2'] : '',
                        'shiptocity' => isset($old_payments[0]['shiptocity']) ? $old_payments[0]['shiptocity'] : '',
                        'shiptostate' => isset($old_payments[0]['shiptostate']) ? $old_payments[0]['shiptostate'] : '',
                        'shiptozip' => isset($old_payments[0]['shiptozip']) ? $old_payments[0]['shiptozip'] : '',
                        'shiptocountrycode' => isset($old_payments[0]['shiptocountrycode']) ? $old_payments[0]['shiptocountrycode'] : '',
                        'shiptophonenum' => isset($old_payments[0]['shiptophonenum']) ? $old_payments[0]['shiptophonenum'] : '',
                        'notetext' => isset($old_payments[0]['notetext']) ? $old_payments[0]['notetext'] : '',
                        'paymentaction' => isset($old_payments[0]['paymentaction']) ? $old_payments[0]['paymentaction'] : '',
                        'sellerpaypalaccountid' => 'paypal-facilitator@angelleye.com',
                    );
                    $PaymentOrderItems = array();
                    $Item = array(
                        'name' => 'Payment #1 Widget',
                        'desc' => 'Payment #1 Widget',
                        'amt' => '7.00',
                        'number' => '',
                        'qty' => '1',
                        'taxamt' => '',
                    );
                    array_push($PaymentOrderItems, $Item);
                    $Payment['order_items'] = $PaymentOrderItems;
                    array_push($new_payments, $Payment);
                } else {
                    $Payment = array(
                        'amt' => '10.00',
                        'currencycode' => '',
                        'itemamt' => '7.00',
                        'shippingamt' => '3.00',
                        'taxamt' => '',
                        'custom' => isset($old_payments[0]['custom']) ? $old_payments[0]['custom'] : '',
                        'invnum' => isset($old_payments[0]['invnum']) ? $old_payments[0]['invnum'] : '',
                        'notifyurl' => isset($old_payments[0]['notifyurl']) ? $old_payments[0]['notifyurl'] : '',
                        'shiptoname' => isset($old_payments[0]['shiptoname']) ? $old_payments[0]['shiptoname'] : '',
                        'shiptostreet' => isset($old_payments[0]['shiptostreet']) ? $old_payments[0]['shiptostreet'] : '',
                        'shiptostreet2' => isset($old_payments[0]['shiptostreet2']) ? $old_payments[0]['shiptostreet2'] : '',
                        'shiptocity' => isset($old_payments[0]['shiptocity']) ? $old_payments[0]['shiptocity'] : '',
                        'shiptostate' => isset($old_payments[0]['shiptostate']) ? $old_payments[0]['shiptostate'] : '',
                        'shiptozip' => isset($old_payments[0]['shiptozip']) ? $old_payments[0]['shiptozip'] : '',
                        'shiptocountrycode' => isset($old_payments[0]['shiptocountrycode']) ? $old_payments[0]['shiptocountrycode'] : '',
                        'shiptophonenum' => isset($old_payments[0]['shiptophonenum']) ? $old_payments[0]['shiptophonenum'] : '',
                        'notetext' => isset($old_payments[0]['notetext']) ? $old_payments[0]['notetext'] : '',
                        'paymentaction' => isset($old_payments[0]['paymentaction']) ? $old_payments[0]['paymentaction'] : '',
                        'sellerpaypalaccountid' => 'paypal-facilitator@angelleye.com',
                    );
                    $PaymentOrderItems = array();
                    $Item = array(
                        'name' => 'Payment #1 Widget',
                        'desc' => 'Payment #1 Widget',
                        'amt' => '7.00',
                        'number' => '',
                        'qty' => '1',
                        'taxamt' => '',
                    );
                    array_push($PaymentOrderItems, $Item);
                    $Payment['order_items'] = $PaymentOrderItems;
                    array_push($new_payments, $Payment);
                }
            }
        } else {
            if (isset(WC()->cart) && WC()->cart->is_empty()) {
                if (!empty($order_id) && $order_id > 0) {
                    $order = wc_get_order($order_id);
                    foreach ($order->get_items() as $cart_item_key => $values) {
                        if (array_key_exists($cart_item_key, $this->map_item_with_account)) {
                            $Payment = array(
                                'amt' => '10.00',
                                'currencycode' => '',
                                'itemamt' => '7.00',
                                'shippingamt' => '3.00',
                                'taxamt' => '',
                                'custom' => isset($old_payments[0]['custom']) ? $old_payments[0]['custom'] : '',
                                'invnum' => isset($old_payments[0]['invnum']) ? $old_payments[0]['invnum'] : '',
                                'notifyurl' => isset($old_payments[0]['notifyurl']) ? $old_payments[0]['notifyurl'] : '',
                                'shiptoname' => isset($old_payments[0]['shiptoname']) ? $old_payments[0]['shiptoname'] : '',
                                'shiptostreet' => isset($old_payments[0]['shiptostreet']) ? $old_payments[0]['shiptostreet'] : '',
                                'shiptostreet2' => isset($old_payments[0]['shiptostreet2']) ? $old_payments[0]['shiptostreet2'] : '',
                                'shiptocity' => isset($old_payments[0]['shiptocity']) ? $old_payments[0]['shiptocity'] : '',
                                'shiptostate' => isset($old_payments[0]['shiptostate']) ? $old_payments[0]['shiptostate'] : '',
                                'shiptozip' => isset($old_payments[0]['shiptozip']) ? $old_payments[0]['shiptozip'] : '',
                                'shiptocountrycode' => isset($old_payments[0]['shiptocountrycode']) ? $old_payments[0]['shiptocountrycode'] : '',
                                'shiptophonenum' => isset($old_payments[0]['shiptophonenum']) ? $old_payments[0]['shiptophonenum'] : '',
                                'notetext' => isset($old_payments[0]['notetext']) ? $old_payments[0]['notetext'] : '',
                                'paymentaction' => isset($old_payments[0]['paymentaction']) ? $old_payments[0]['paymentaction'] : '',
                                'sellerpaypalaccountid' => 'paypal-facilitator@angelleye.com',
                            );

                            $PaymentOrderItems = array();
                            $Item = array(
                                'name' => 'Payment #1 Widget',
                                'desc' => 'Payment #1 Widget',
                                'amt' => '7.00',
                                'number' => '',
                                'qty' => '1',
                                'taxamt' => '',
                            );
                            array_push($PaymentOrderItems, $Item);
                            $Payment['order_items'] = $PaymentOrderItems;
                            array_push($new_payments, $Payment);
                        } else {
                            $Payment = array(
                                'amt' => '10.00',
                                'currencycode' => '',
                                'itemamt' => '7.00',
                                'shippingamt' => '3.00',
                                'taxamt' => '',
                                'custom' => isset($old_payments[0]['custom']) ? $old_payments[0]['custom'] : '',
                                'invnum' => isset($old_payments[0]['invnum']) ? $old_payments[0]['invnum'] : '',
                                'notifyurl' => isset($old_payments[0]['notifyurl']) ? $old_payments[0]['notifyurl'] : '',
                                'shiptoname' => isset($old_payments[0]['shiptoname']) ? $old_payments[0]['shiptoname'] : '',
                                'shiptostreet' => isset($old_payments[0]['shiptostreet']) ? $old_payments[0]['shiptostreet'] : '',
                                'shiptostreet2' => isset($old_payments[0]['shiptostreet2']) ? $old_payments[0]['shiptostreet2'] : '',
                                'shiptocity' => isset($old_payments[0]['shiptocity']) ? $old_payments[0]['shiptocity'] : '',
                                'shiptostate' => isset($old_payments[0]['shiptostate']) ? $old_payments[0]['shiptostate'] : '',
                                'shiptozip' => isset($old_payments[0]['shiptozip']) ? $old_payments[0]['shiptozip'] : '',
                                'shiptocountrycode' => isset($old_payments[0]['shiptocountrycode']) ? $old_payments[0]['shiptocountrycode'] : '',
                                'shiptophonenum' => isset($old_payments[0]['shiptophonenum']) ? $old_payments[0]['shiptophonenum'] : '',
                                'notetext' => isset($old_payments[0]['notetext']) ? $old_payments[0]['notetext'] : '',
                                'paymentaction' => isset($old_payments[0]['paymentaction']) ? $old_payments[0]['paymentaction'] : '',
                                'sellerpaypalaccountid' => 'paypal-facilitator@angelleye.com',
                            );

                            $PaymentOrderItems = array();
                            $Item = array(
                                'name' => 'Payment #1 Widget',
                                'desc' => 'Payment #1 Widget',
                                'amt' => '7.00',
                                'number' => '',
                                'qty' => '1',
                                'taxamt' => '',
                            );
                            array_push($PaymentOrderItems, $Item);
                            $Payment['order_items'] = $PaymentOrderItems;
                            array_push($new_payments, $Payment);
                        }
                    }
                }
            }
        }
        if (!empty($new_payments)) {
            $request['Payments'] = $new_payments;
        } else {
            $request['Payments'] = $old_payments;
        }
        return $request;
    }

    public function angelleye_get_email_address() {
        
    }

}
