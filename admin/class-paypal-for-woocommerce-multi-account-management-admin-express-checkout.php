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
    public $angelleye_is_discountable;
    public $angelleye_needs_shipping;
    public $zdp_currencies = array('HUF', 'JPY', 'TWD');
    public $decimals;
    public $discount_array = array();
    public $shipping_array = array();
    public $tax_array = array();
    public $taxamt;
    public $shippingamt;
    public $paypal;
    public $paypal_response;
    public $final_grand_total;
    public $final_order_grand_total;
    public $is_calculation_mismatch;
    public $final_refund_amt;

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

        $is_zdp_currency = in_array(get_woocommerce_currency(), $this->zdp_currencies);
        if ($is_zdp_currency) {
            $this->decimals = 0;
        } else {
            $this->decimals = 2;
        }
    }

    public function angelleye_get_account_for_ec_parallel_payments($gateways, $gateway_setting, $order_id, $request) {
        global $user_ID;
        if (empty($order_id)) {
            $this->is_calculation_mismatch = isset($gateway_setting->cart_param['is_calculation_mismatch']) ? $gateway_setting->cart_param['is_calculation_mismatch'] : false;
        } else {
            $this->is_calculation_mismatch = isset($gateway_setting->order_param['is_calculation_mismatch']) ? $gateway_setting->order_param['is_calculation_mismatch'] : false;
        }
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
                $this->angelleye_is_taxable = 0;
                $this->angelleye_needs_shipping = 0;
                $this->angelleye_is_discountable = 0;
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
                    if (!empty($order_id)) {
                        $order = wc_get_order($order_id);
                        foreach ($order->get_items() as $cart_item_key => $values) {
                            $line_item = $values->get_data();
                            $product = $order->get_product_from_item($values);
                            $product_exists = is_object( $product );
                            if($product_exists == false) {
                                $product_id = apply_filters('angelleye_multi_account_get_product_id', '', $cart_item_key);
                                if(!empty($product_id)) {
                                    $product = wc_get_product($product_id);
                                } else {
                                    continue;
                                }
                            } 
                            $product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
                            $this->map_item_with_account[$product_id]['product_id'] = $product_id;
                            $this->map_item_with_account[$product_id]['order_item_id'] = $cart_item_key;
                            if ($product->is_taxable()) {
                                $this->map_item_with_account[$product_id]['is_taxable'] = true;
                                $this->map_item_with_account[$product_id]['tax'] = $line_item['total_tax'];
                                $this->angelleye_is_taxable = $this->angelleye_is_taxable + 1;
                            } else {
                                $this->map_item_with_account[$product_id]['is_taxable'] = false;
                            }
                            if ($product->needs_shipping()) {
                                $this->map_item_with_account[$product_id]['needs_shipping'] = true;
                                $this->angelleye_needs_shipping = $this->angelleye_needs_shipping + 1;
                            } else {
                                $this->map_item_with_account[$product_id]['needs_shipping'] = true;
                            }
                            if($order->get_total_discount() > 0) {
                                if($line_item['subtotal'] != $line_item['total']) {
                                    $this->map_item_with_account[$product_id]['is_discountable'] = true;
                                    $this->angelleye_is_discountable = $this->angelleye_is_discountable + 1;
                                } else {
                                    $this->map_item_with_account[$product_id]['is_discountable'] = false;
                                }
                            } 
                            if (isset($this->map_item_with_account[$product_id]['multi_account_id']) && $this->map_item_with_account[$product_id]['multi_account_id'] != 'default') {
                                continue;
                            }
                            if (!isset($this->map_item_with_account[$product_id]['multi_account_id'])) {
                                $this->map_item_with_account[$product_id]['multi_account_id'] = 'default';
                            }
                            $woo_product_categories = wp_get_post_terms($product_id, apply_filters('angelleye_get_product_categories', array('product_cat')), array('fields' => 'ids'));
                            $woo_product_categories = angelleye_get_product_cat($woo_product_categories);
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
                            $this->map_item_with_account[$product_id]['multi_account_id'] = $value->ID;
                            if ($gateways->testmode == true) {
                                if (isset($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0])) {
                                    $this->map_item_with_account[$product_id]['email'] = $microprocessing_array['woocommerce_paypal_express_sandbox_email'][0];
                                } elseif (isset($microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0])) {
                                    $this->map_item_with_account[$product_id]['email'] = $microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0];
                                } else {
                                    $this->map_item_with_account[$product_id]['email'] = $this->angelleye_get_email_address_for_multi($value->ID, $microprocessing_array, $gateways);
                                }
                                if ($this->angelleye_is_multi_account_api_set($microprocessing_array, $gateways)) {
                                    $this->map_item_with_account[$product_id]['is_api_set'] = true;
                                } else {
                                    $this->map_item_with_account[$product_id]['is_api_set'] = false;
                                }
                            } else {
                                if (isset($microprocessing_array['woocommerce_paypal_express_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_email'][0])) {
                                    $this->map_item_with_account[$product_id]['email'] = $microprocessing_array['woocommerce_paypal_express_email'][0];
                                } elseif (isset($microprocessing_array['woocommerce_paypal_express_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_merchant_id'][0])) {
                                    $this->map_item_with_account[$product_id]['email'] = $microprocessing_array['woocommerce_paypal_express_merchant_id'][0];
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
                    } else {
                        if (isset(WC()->cart) && sizeof(WC()->cart->get_cart()) > 0) {
                            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                                $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                                $product = wc_get_product($product_id);
                                $this->map_item_with_account[$product_id]['product_id'] = $product_id;
                                if ($product->is_taxable()) {
                                    $this->map_item_with_account[$product_id]['is_taxable'] = true;
                                    $this->map_item_with_account[$product_id]['tax'] = $cart_item['line_subtotal_tax'];
                                    $this->angelleye_is_taxable = $this->angelleye_is_taxable + 1;
                                } else {
                                    $this->map_item_with_account[$product_id]['is_taxable'] = false;
                                }
                                if ($product->needs_shipping()) {
                                    $this->map_item_with_account[$product_id]['needs_shipping'] = true;
                                    $this->angelleye_needs_shipping = $this->angelleye_needs_shipping + 1;
                                } else {
                                    $this->map_item_with_account[$product_id]['needs_shipping'] = false;
                                }
                                if(WC()->cart->get_cart_discount_total() > 0) {
                                    if($cart_item['line_subtotal'] != $cart_item['line_total']) {
                                        $this->map_item_with_account[$product_id]['is_discountable'] = true;
                                        $this->angelleye_is_discountable = $this->angelleye_is_discountable + 1;
                                    } else {
                                        $this->map_item_with_account[$product_id]['is_discountable'] = false;
                                    }
                                }
                                if (isset($this->map_item_with_account[$product_id]['multi_account_id']) && $this->map_item_with_account[$product_id]['multi_account_id'] != 'default') {
                                    continue;
                                }
                                if (empty($this->map_item_with_account[$product_id]['multi_account_id'])) {
                                    $this->map_item_with_account[$product_id]['multi_account_id'] = 'default';
                                }
                                $woo_product_categories = wp_get_post_terms($product_id, apply_filters('angelleye_get_product_categories', array('product_cat')), array('fields' => 'ids'));
                                $woo_product_categories = angelleye_get_product_cat($woo_product_categories);
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
                                $this->map_item_with_account[$product_id]['multi_account_id'] = $value->ID;
                                if ($gateways->testmode == true) {
                                    if (isset($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0])) {
                                        $this->map_item_with_account[$product_id]['email'] = $microprocessing_array['woocommerce_paypal_express_sandbox_email'][0];
                                    } elseif (isset($microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0])) {
                                        $this->map_item_with_account[$product_id]['email'] = $microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0];
                                    } else {
                                        $this->map_item_with_account[$product_id]['email'] = $this->angelleye_get_email_address_for_multi($value->ID, $microprocessing_array, $gateways);
                                    }
                                    if ($this->angelleye_is_multi_account_api_set($microprocessing_array, $gateways)) {
                                        $this->map_item_with_account[$product_id]['is_api_set'] = true;
                                    } else {
                                        $this->map_item_with_account[$product_id]['is_api_set'] = false;
                                    }
                                } else {
                                    if (isset($microprocessing_array['woocommerce_paypal_express_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_email'][0])) {
                                        $this->map_item_with_account[$product_id]['email'] = $microprocessing_array['woocommerce_paypal_express_email'][0];
                                    } elseif (isset($microprocessing_array['woocommerce_paypal_express_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_merchant_id'][0])) {
                                        $this->map_item_with_account[$product_id]['email'] = $microprocessing_array['woocommerce_paypal_express_merchant_id'][0];
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

    public function angelleye_paypal_for_woocommerce_multi_account_api_paypal_express($request = null, $gateways, $current = null, $order_id = null) {
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
        $payment_action = array('set_express_checkout', 'get_express_checkout_details', 'do_express_checkout_payment');
        if (!empty($_GET['pp_action']) && ( in_array($_GET['pp_action'], $payment_action))) {
            return $this->angelleye_get_account_for_ec_parallel_payments($gateways, $gateway_setting, $order_id, $request);
        }
        return $request;
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
        $default_new_payments = array();
        $default_new_payments_line_item = array();
        if (!empty($request['Payments'])) {
            $old_payments = $request['Payments'];
            unset($request['Payments']);
        } else {
            $old_payments = array();
        }
        if( !empty($request['SECFields']['customerservicenumber']) ) {
            unset($request['SECFields']['customerservicenumber']);
        }
        $this->taxamt = round(WC()->cart->get_total_tax(), $this->decimals);
        $this->shippingamt = round(WC()->cart->shipping_total, $this->decimals);
        $this->discount_amount = round(WC()->cart->get_cart_discount_total(), $this->decimals);
        if (isset($this->taxamt) && $this->taxamt > 0) {
            $this->tax_array = $this->angelleye_get_extra_fee_array($this->taxamt, $this->angelleye_is_taxable, 'tax');
        }
        if (isset($this->shippingamt) && $this->shippingamt > 0) {
            $this->shipping_array = $this->angelleye_get_extra_fee_array($this->shippingamt, $this->angelleye_needs_shipping, 'shipping');
        }
        if (isset($this->discount_amount) && $this->discount_amount > 0) {
            $this->discount_array = $this->angelleye_get_extra_fee_array($this->discount_amount, $this->angelleye_is_discountable, 'discount');
        }
        $loop = 1;
        $default_item_total = 0;
        $default_final_total = 0;
        $default_shippingamt = 0;
        $default_taxamt = 0;
        $default_pal_id = '';
        if (!empty($order_id)) {
            $order = wc_get_order($order_id);
            $this->final_order_grand_total = $order->get_total();
            foreach ($order->get_items() as $cart_item_key => $cart_item) {
                $product = $order->get_product_from_item($cart_item);
                $product_exists = is_object( $product );
                if($product_exists == false) {
                    $product_id = apply_filters('angelleye_multi_account_get_product_id', '', $cart_item_key);
                    if(!empty($product_id)) {
                        $product = wc_get_product($product_id);
                    } else {
                        continue;
                    }
                } 
                $product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
                $item_total = 0;
                $final_total = 0;
                if (array_key_exists($product_id, $this->map_item_with_account)) {
                    $multi_account_info = $this->map_item_with_account[$product_id];
                    if ($multi_account_info['multi_account_id'] != 'default') {
                        if (isset($multi_account_info['email'])) {
                            $sellerpaypalaccountid = $multi_account_info['email'];
                        } else {
                            $sellerpaypalaccountid = $this->angelleye_get_email_address($this->map_item_with_account[$product_id], $gateways);
                        }
                        $this->map_item_with_account[$product_id]['sellerpaypalaccountid'] = $sellerpaypalaccountid;
                        $PaymentOrderItems = array();
                        $line_item = $this->angelleye_get_line_item_from_order($order, $cart_item);
                        $Item = array(
                            'name' => $line_item['name'],
                            'desc' => $line_item['desc'],
                            'amt' => $line_item['amt'],
                            'number' => $line_item['number'],
                            'qty' => $line_item['qty']
                        );
                        $item_total = AngellEYE_Gateway_Paypal::number_format($item_total + ($line_item['amt'] * $line_item['qty']));
                        array_push($PaymentOrderItems, $Item);
                        if (!empty($this->discount_array[$product_id])) {
                            $Item = array(
                                'name' => 'Discount',
                                'desc' => 'Discount Amount',
                                'amt' => isset($this->discount_array[$product_id]) ? '-' . AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id]) : '0.00',
                                'number' => '',
                                'qty' => 1
                            );
                            $item_total = $item_total - $this->discount_array[$product_id];
                            array_push($PaymentOrderItems, $Item);
                        }
                        $shippingamt = isset($this->shipping_array[$product_id]) ? $this->shipping_array[$product_id] : '0.00';
                        $taxamt = isset($this->tax_array[$product_id]) ? $this->tax_array[$product_id] : '0.00';
                        $final_total = AngellEYE_Gateway_Paypal::number_format($item_total + $shippingamt + $taxamt);
                        $custom_param = '';
                        if (isset($old_payments[0]['custom'])) {
                            $custom_param = json_decode($old_payments[0]['custom'], true);
                            $custom_param['order_item_id'] = $cart_item_key;
                            $custom_param = json_encode($custom_param);
                        } else {
                            $custom_param['order_item_id'] = $cart_item_key;
                            $custom_param = json_encode($custom_param);
                        }
                        $this->final_grand_total = $this->final_grand_total + $final_total;
                        $Payment = array(
                            'amt' => $final_total,
                            'currencycode' => isset($old_payments[0]['currencycode']) ? $old_payments[0]['currencycode'] : '',
                            'itemamt' => $item_total,
                            'shippingamt' => $shippingamt,
                            'taxamt' => $taxamt,
                            'custom' => $custom_param,
                            'invnum' => isset($old_payments[0]['invnum']) ? $old_payments[0]['invnum'] . '-' . $cart_item_key : '',
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
                            'paymentaction' => 'Sale',
                            'sellerpaypalaccountid' => $sellerpaypalaccountid,
                            'paymentrequestid' => $cart_item_key . '-' . rand()
                        );
                        $Payment['order_items'] = $PaymentOrderItems;
                        array_push($new_payments, $Payment);
                        $loop = $loop + 1;
                    } else {
                        if (isset($multi_account_info['email'])) {
                            $sellerpaypalaccountid = $multi_account_info['email'];
                        } else {
                            $sellerpaypalaccountid = $this->angelleye_get_email_address($this->map_item_with_account[$product_id], $gateways);
                        }
                        $default_pal_id = $sellerpaypalaccountid;
                        $this->map_item_with_account[$product_id]['sellerpaypalaccountid'] = $sellerpaypalaccountid;
                        $line_item = $this->angelleye_get_line_item_from_order($order, $cart_item);
                        $Item = array(
                            'name' => $line_item['name'],
                            'desc' => $line_item['desc'],
                            'amt' => $line_item['amt'],
                            'number' => $line_item['number'],
                            'qty' => $line_item['qty']
                        );
                        $item_total = AngellEYE_Gateway_Paypal::number_format($item_total + ($line_item['amt'] * $line_item['qty']));
                        $default_new_payments_line_item[] = $Item;
                        if (!empty($this->discount_array[$product_id])) {
                            $Item = array(
                                'name' => 'Discount',
                                'desc' => 'Discount Amount',
                                'amt' => isset($this->discount_array[$product_id]) ? '-' . AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id]) : '0.00',
                                'number' => '',
                                'qty' => 1
                            );
                            $item_total = $item_total - $this->discount_array[$product_id];
                            $default_new_payments_line_item[] = $Item;
                        }
                        $paymentrequestid_value = $cart_item_key . '-' . rand();
                        $shippingamt = isset($this->shipping_array[$product_id]) ? $this->shipping_array[$product_id] : '0.00';
                        $default_shippingamt = $default_shippingamt + $shippingamt;
                        $taxamt = isset($this->tax_array[$product_id]) ? $this->tax_array[$product_id] : '0.00';
                        $default_taxamt = $default_taxamt + $taxamt;
                        $default_final_total = $default_final_total + AngellEYE_Gateway_Paypal::number_format($item_total + $shippingamt + $taxamt);
                        $default_item_total = $default_item_total + $item_total;
                        $loop = $loop + 1;
                    }
                }
            }
        } elseif (isset(WC()->cart) && sizeof(WC()->cart->get_cart()) > 0 ) {
            $cart_amt_total = WC()->cart->get_totals();
            $this->final_order_grand_total = $cart_amt_total['total'];
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $item_total = 0;
                $final_total = 0;
                $product_id = $cart_item['product_id'];
                if (array_key_exists($product_id, $this->map_item_with_account)) {
                    $multi_account_info = $this->map_item_with_account[$product_id];
                    if ($multi_account_info['multi_account_id'] != 'default') {
                        if (isset($multi_account_info['email'])) {
                            $sellerpaypalaccountid = $multi_account_info['email'];
                        } else {
                            $sellerpaypalaccountid = $this->angelleye_get_email_address($this->map_item_with_account[$product_id], $gateways);
                        }
                        $PaymentOrderItems = array();
                        $line_item = $this->angelleye_get_line_item_from_cart($cart_item);
                        $Item = array(
                            'name' => $line_item['name'],
                            'desc' => $line_item['desc'],
                            'amt' => $line_item['amt'],
                            'number' => $line_item['number'],
                            'qty' => $line_item['qty']
                        );
                        $item_total = AngellEYE_Gateway_Paypal::number_format($item_total + ($line_item['amt'] * $line_item['qty']));
                        array_push($PaymentOrderItems, $Item);
                        if (!empty($this->discount_array[$product_id])) {
                            $Item = array(
                                'name' => 'Discount',
                                'desc' => 'Discount Amount',
                                'amt' => isset($this->discount_array[$product_id]) ? '-' . AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id]) : '0.00',
                                'number' => '',
                                'qty' => 1
                            );
                            $item_total = $item_total - $this->discount_array[$product_id];
                            array_push($PaymentOrderItems, $Item);
                        }
                        $shippingamt = isset($this->shipping_array[$product_id]) ? $this->shipping_array[$product_id] : '0.00';
                        $taxamt = isset($this->tax_array[$product_id]) ? $this->tax_array[$product_id] : '0.00';
                        $final_total = AngellEYE_Gateway_Paypal::number_format($item_total + $shippingamt + $taxamt);
                        $this->final_grand_total = $this->final_grand_total + $final_total;
                        $Payment = array(
                            'amt' => $final_total,
                            'currencycode' => isset($old_payments[0]['currencycode']) ? $old_payments[0]['currencycode'] : '',
                            'itemamt' => $item_total,
                            'shippingamt' => $shippingamt,
                            'taxamt' => $taxamt,
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
                            'paymentaction' => 'Sale',
                            'sellerpaypalaccountid' => $sellerpaypalaccountid,
                            'paymentrequestid' => isset($old_payments[0]['invnum']) ? $old_payments[0]['invnum'] : '' . $cart_item_key
                        );
                        $Payment['order_items'] = $PaymentOrderItems;
                        array_push($new_payments, $Payment);
                        $loop = $loop + 1;
                    } else {
                        if (isset($multi_account_info['email'])) {
                            $sellerpaypalaccountid = $multi_account_info['email'];
                        } else {
                            $sellerpaypalaccountid = $this->angelleye_get_email_address($this->map_item_with_account[$product_id], $gateways);
                        }
                        $default_pal_id = $sellerpaypalaccountid;
                        $line_item = $this->angelleye_get_line_item_from_cart($cart_item);
                        $Item = array(
                            'name' => $line_item['name'],
                            'desc' => $line_item['desc'],
                            'amt' => $line_item['amt'],
                            'number' => $line_item['number'],
                            'qty' => $line_item['qty']
                        );
                        $item_total = AngellEYE_Gateway_Paypal::number_format($item_total + ($line_item['amt'] * $line_item['qty']));
                        $default_new_payments_line_item[] = $Item;
                        if (!empty($this->discount_array['$product_id'])) {
                            $Item = array(
                                'name' => 'Discount',
                                'desc' => 'Discount Amount',
                                'amt' => isset($this->discount_array[$product_id]) ? '-' . AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id]) : '0.00',
                                'number' => '',
                                'qty' => 1
                            );
                            $item_total = $item_total - $this->discount_array[$product_id];
                            $default_new_payments_line_item[] = $Item;
                        }
                        $shippingamt = isset($this->shipping_array[$product_id]) ? $this->shipping_array[$product_id] : '0.00';
                        $default_shippingamt = $default_shippingamt + $shippingamt;
                        $taxamt = isset($this->tax_array[$product_id]) ? $this->tax_array[$product_id] : '0.00';
                        $default_taxamt = $default_taxamt + $taxamt;
                        $default_final_total = $default_final_total + AngellEYE_Gateway_Paypal::number_format($item_total + $shippingamt + $taxamt);
                        $default_item_total = $default_item_total + $item_total;
                        $loop = $loop + 1;
                    }
                }
            }
        } 
        if (!empty($default_new_payments_line_item)) {
            $new_default_payment = array(
                'amt' => AngellEYE_Gateway_Paypal::number_format($default_final_total),
                'currencycode' => isset($old_payments[0]['currencycode']) ? $old_payments[0]['currencycode'] : '',
                'itemamt' => AngellEYE_Gateway_Paypal::number_format($default_item_total),
                'shippingamt' => AngellEYE_Gateway_Paypal::number_format($default_shippingamt),
                'taxamt' => AngellEYE_Gateway_Paypal::number_format($default_taxamt),
                'custom' => isset($old_payments[0]['custom']) ? $old_payments[0]['custom'] : '',
                'invnum' => isset($old_payments[0]['invnum']) ? $old_payments[0]['invnum'] . '-' . $cart_item_key : '',
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
                'paymentaction' => 'Sale',
                'sellerpaypalaccountid' => $default_pal_id,
                'paymentrequestid' => !empty($paymentrequestid_value) ? $paymentrequestid_value : uniqid(rand(), true)
            );
            $this->final_grand_total = $this->final_grand_total + $default_final_total;
            $new_default_payment['order_items'] = $default_new_payments_line_item;
            array_push($new_payments, $new_default_payment);
        }
        if ($this->final_grand_total != $this->final_order_grand_total) {
            $Difference = round($this->final_order_grand_total - $this->final_grand_total, $this->decimals);
            if (abs($Difference) > 0.000001 && 0.0 !== (float) $Difference) {
                if (isset($new_payments[0]['amt']) && $new_payments[0]['amt'] > 1) {
                    $new_payments[0]['amt'] = $new_payments[0]['amt'] + $Difference;
                    unset($new_payments[0]['itemamt']);
                    unset($new_payments[0]['order_items']);
                    unset($new_payments[0]['shippingamt']);
                    unset($new_payments[0]['taxamt']);
                }
            }
        }
        if (!empty($new_payments)) {
            $request['Payments'] = $new_payments;
            if (!empty($order_id) && !empty($this->map_item_with_account) && $this->angelleye_is_multi_account_used($this->map_item_with_account)) {
                update_post_meta($order_id, '_angelleye_multi_account_ec_parallel_data_map', $this->map_item_with_account);
            }
        } else {
            $request['Payments'] = $old_payments;
        }
        return $request;
    }

    public function angelleye_is_multi_account_used($map_item_with_account) {
        if (!empty($map_item_with_account)) {
            foreach ($map_item_with_account as $key => $item_with_account) {
                if (isset($item_with_account['multi_account_id']) && $item_with_account['multi_account_id'] != 'default') {
                    return true;
                }
            }
        }
        return false;
    }

    public function angelleye_get_email_address($map_item_with_account_array, $gateways) {
        if (!empty($map_item_with_account_array['multi_account_id'])) {
            if ($map_item_with_account_array['multi_account_id'] == 'default') {
                $angelleye_express_checkout_default_pal = get_option('angelleye_express_checkout_default_pal', false);
                if (!empty($angelleye_express_checkout_default_pal)) {
                    if (isset($angelleye_express_checkout_default_pal['Sandbox']) && $angelleye_express_checkout_default_pal['Sandbox'] == $gateways->testmode && isset($angelleye_express_checkout_default_pal['APIUsername']) && $angelleye_express_checkout_default_pal['APIUsername'] == $gateways->api_username) {
                        return $angelleye_express_checkout_default_pal['PAL'];
                    }
                }
                $PayPalConfig = array(
                    'Sandbox' => $gateways->testmode,
                    'APIUsername' => $gateways->api_username,
                    'APIPassword' => $gateways->api_password,
                    'APISignature' => $gateways->api_signature
                );
                if (!class_exists('Angelleye_PayPal_WC')) {
                    require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
                }
                $PayPal = new Angelleye_PayPal_WC($PayPalConfig);
                $PayPalResult = $PayPal->GetPalDetails();
                if (isset($PayPalResult['ACK']) && $PayPalResult['ACK'] == 'Success') {
                    if (isset($PayPalResult['PAL']) && !empty($PayPalResult['PAL'])) {
                        $merchant_account_id = $PayPalResult['PAL'];
                        update_option('angelleye_express_checkout_default_pal', array('Sandbox' => $gateways->testmode, 'APIUsername' => $gateways->api_username, 'PAL' => $merchant_account_id));
                        return $merchant_account_id;
                    }
                }
            }
        }
    }

    public function angelleye_get_email_address_for_multi($account_id, $microprocessing_array, $gateways) {
        if ($gateways->testmode) {
            $PayPalConfig = array(
                'Sandbox' => $gateways->testmode,
                'APIUsername' => $microprocessing_array['woocommerce_paypal_express_sandbox_api_username'][0],
                'APIPassword' => $microprocessing_array['woocommerce_paypal_express_sandbox_api_password'][0],
                'APISignature' => $microprocessing_array['woocommerce_paypal_express_sandbox_api_signature'][0]
            );
        } else {
            $PayPalConfig = array(
                'Sandbox' => $gateways->testmode,
                'APIUsername' => $microprocessing_array['woocommerce_paypal_express_api_username'][0],
                'APIPassword' => $microprocessing_array['woocommerce_paypal_express_api_signature'][0],
                'APISignature' => $microprocessing_array['woocommerce_paypal_express_api_password'][0]
            );
        }

        if (!class_exists('Angelleye_PayPal_WC')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }
        $PayPal = new Angelleye_PayPal_WC($PayPalConfig);
        $PayPalResult = $PayPal->GetPalDetails();
        if (isset($PayPalResult['ACK']) && $PayPalResult['ACK'] == 'Success') {
            if (isset($PayPalResult['PAL']) && !empty($PayPalResult['PAL'])) {
                $merchant_account_id = $PayPalResult['PAL'];
                if ($gateways->testmode) {
                    update_post_meta($account_id, 'woocommerce_paypal_express_sandbox_merchant_id', $merchant_account_id);
                } else {
                    update_post_meta($account_id, 'woocommerce_paypal_express_merchant_id', $merchant_account_id);
                }
                return $merchant_account_id;
            }
        } else {
            return false;
        }
    }

    public function angelleye_get_line_item_from_cart($values) {
        $amount = round($values['line_subtotal'] / $values['quantity'], $this->decimals);
        if (version_compare(WC_VERSION, '3.0', '<')) {
            $product = $values['data'];
            $name = $values['data']->post->post_title;
        } else {
            $product = $values['data'];
            $name = $product->get_name();
        }
        $desc = '';
        $name = AngellEYE_Gateway_Paypal::clean_product_title($name);
        if (is_object($product)) {
            if ($product->is_type('variation') && is_a($product, 'WC_Product_Variation')) {
                if (version_compare(WC_VERSION, '3.0', '<')) {
                    $attributes = $product->get_variation_attributes();
                    if (!empty($attributes) && is_array($attributes)) {
                        foreach ($attributes as $key => $value) {
                            $key = str_replace(array('attribute_pa_', 'attribute_'), '', $key);
                            $desc .= ' ' . ucwords(str_replace('pa_', '', $key)) . ': ' . $value;
                        }
                        $desc = trim($desc);
                    }
                } else {
                    $attributes = $product->get_attributes();
                    if (!empty($attributes) && is_array($attributes)) {
                        foreach ($attributes as $key => $value) {
                            $desc .= ' ' . ucwords(str_replace('pa_', '', $key)) . ': ' . $value;
                        }
                    }
                    $desc = trim($desc);
                }
            }
        }
        $product_sku = null;
        if (is_object($product)) {
            $product_sku = $product->get_sku();
        }
        $item = array(
            'name' => html_entity_decode(wc_trim_string($name ? $name : __('Item', 'paypal-for-woocommerce-multi-account-management'), 127), ENT_NOQUOTES, 'UTF-8'),
            'desc' => html_entity_decode(wc_trim_string($desc, 127), ENT_NOQUOTES, 'UTF-8'),
            'qty' => $values['quantity'],
            'amt' => AngellEYE_Gateway_Paypal::number_format($amount),
            'number' => $product_sku
        );
        return $item;
    }

    public function angelleye_get_line_item_from_order($order, $values) {
        $product = $order->get_product_from_item($values);
        $product_sku = null;
        if (is_object($product)) {
            $product_sku = $product->get_sku();
        }
        if (empty($values['name'])) {
            $name = 'Item';
        } else {
            $name = $values['name'];
        }
        $name = AngellEYE_Gateway_Paypal::clean_product_title($name);
        $amount = round($values['line_subtotal'] / $values['qty'], $this->decimals);
        if (is_object($product)) {
            if ($product->is_type('variation') && is_a($product, 'WC_Product_Variation')) {
                $desc = '';
                if (version_compare(WC_VERSION, '3.0', '<')) {
                    $attributes = $product->get_variation_attributes();
                    if (!empty($attributes) && is_array($attributes)) {
                        foreach ($attributes as $key => $value) {
                            $key = str_replace(array('attribute_pa_', 'attribute_'), '', $key);
                            $desc .= ' ' . ucwords(str_replace('pa_', '', $key)) . ': ' . $value;
                        }
                        $desc = trim($desc);
                    }
                } else {
                    $attributes = $product->get_attributes();
                    if (!empty($attributes) && is_array($attributes)) {
                        foreach ($attributes as $key => $value) {
                            $desc .= ' ' . ucwords(str_replace('pa_', '', $key)) . ': ' . $value;
                        }
                    }
                    $desc = trim($desc);
                }
            }
        }
        $item = array(
            'name' => html_entity_decode(wc_trim_string($name ? $name : __('Item', 'paypal-for-woocommerce-multi-account-management'), 127), ENT_NOQUOTES, 'UTF-8'),
            'desc' => html_entity_decode(wc_trim_string($desc, 127), ENT_NOQUOTES, 'UTF-8'),
            'qty' => $values['qty'],
            'amt' => AngellEYE_Gateway_Paypal::number_format($amount, $order),
            'number' => $product_sku,
        );
        return $item;
    }

    public function angelleye_get_extra_fee_array($amount, $divided, $type) {
        $total = 0;
        $partition_array = array();
        $partition = AngellEYE_Gateway_Paypal::number_format($amount / $divided);
        for ($i = 1; $i <= $divided; $i++) {
            $partition_array[$i] = $partition;
            $total = $total + $partition;
        }
        $Difference = round($amount - $total, $this->decimals);
        if (abs($Difference) > 0.000001 && 0.0 !== (float) $Difference) {
            $partition_array[$divided] = $partition_array[$divided] + $Difference;
        }
        if(!empty($this->map_item_with_account)) {
            $loop = 1;
            foreach ($this->map_item_with_account as $product_id => $item_with_account) {
                switch ($type) {
                    case "tax":
                        if(!empty($item_with_account['is_taxable']) && $item_with_account['is_taxable'] === true) {
                            $partition_array[$product_id] = $partition_array[$loop];
                            unset($partition_array[$loop]);
                            $loop = $loop + 1;
                        }
                        break;
                    case "shipping":
                        if(!empty($item_with_account['needs_shipping']) && $item_with_account['needs_shipping'] === true) {
                            $partition_array[$product_id] = $partition_array[$loop];
                            unset($partition_array[$loop]);
                            $loop = $loop + 1;
                        }
                        break;
                    case "discount":
                        if(!empty($item_with_account['is_discountable']) && $item_with_account['is_discountable'] === true) {
                            $partition_array[$product_id] = $partition_array[$loop];
                            unset($partition_array[$loop]);
                            $loop = $loop + 1;
                        }
                        break;

                }
            }
        }
        return $partition_array;
    }

    public function angelleye_is_multi_account_api_set($microprocessing_array, $gateways) {
        if ($gateways->testmode) {
            if (!empty($microprocessing_array['woocommerce_paypal_express_sandbox_api_username'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_api_password'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_api_signature'][0])) {
                return true;
            }
        } else {
            if (!empty($microprocessing_array['woocommerce_paypal_express_api_username'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_api_signature'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_api_password'][0])) {
                return true;
            }
        }
        return false;
    }

    public function own_angelleye_express_checkout_order_data($paypal_response, $order_id) {
        $order = wc_get_order($order_id);
        $ec_parallel_data_map = get_post_meta($order_id, '_angelleye_multi_account_ec_parallel_data_map', true);
        if (empty($ec_parallel_data_map)) {
            return false;
        }
        for ($payment = 1; $payment <= 10; $payment++) {
            if (!empty($paypal_response['PAYMENTINFO_' . $payment . '_TRANSACTIONID'])) {
                $order->add_order_note(sprintf(__('PayPal Express payment Transaction ID: %s', 'paypal-for-woocommerce-multi-account-management'), isset($paypal_response['PAYMENTINFO_' . $payment . '_TRANSACTIONID']) ? $paypal_response['PAYMENTINFO_' . $payment . '_TRANSACTIONID'] : ''));
            } else {
                break;
            }
        }
        $total_account = count($ec_parallel_data_map);
        foreach ($ec_parallel_data_map as $key => $ec_parallel_data) {
            for ($transaction_map = 0; $transaction_map <= $total_account; $transaction_map++) {
                if (!empty($paypal_response['PAYMENTINFO_' . $transaction_map . '_PAYMENTREQUESTID'])) {
                    $PAYMENTREQUESTID_array = $paypal_response['PAYMENTINFO_' . $transaction_map . '_PAYMENTREQUESTID'];
                    $request_order_item_id = explode('-', $PAYMENTREQUESTID_array);
                    if( !empty($request_order_item_id[0]) && $ec_parallel_data['order_item_id'] == $request_order_item_id[0]) {
                        $PAYMENTREQUESTID_array = $ec_parallel_data_map[$ec_parallel_data['product_id']]['transaction_id'] = $paypal_response['PAYMENTINFO_' . $transaction_map . '_TRANSACTIONID'];
                        wc_update_order_item_meta($ec_parallel_data['order_item_id'], '_transaction_id', $paypal_response['PAYMENTINFO_' . $transaction_map . '_TRANSACTIONID']);
                    }
                }
            }
        }
        if (!empty($ec_parallel_data_map)) {
            update_post_meta($order_id, '_angelleye_multi_account_ec_parallel_data_map', $ec_parallel_data_map);
        }
    }

    public function angelleye_get_map_item_data($request_param_part_data, $ec_parallel_data_map) {
        foreach ($ec_parallel_data_map as $key => $value) {
            if ($value['order_item_id'] == $request_param_part_data) {
                return $key;
            }
        }
        return false;
    }

    public function own_woocommerce_payment_gateway_supports($bool, $feature, $current) {
        global $post;
        if ($feature === 'refunds' && $bool === true && $current->id === 'paypal_express') {
            if (!empty($post->ID)) {
                $angelleye_multi_account_ec_parallel_data_map = get_post_meta($post->ID, '_angelleye_multi_account_ec_parallel_data_map', true);
                if (!empty($angelleye_multi_account_ec_parallel_data_map)) {
                    foreach ($angelleye_multi_account_ec_parallel_data_map as $key => $value) {
                        if (isset($value['multi_account_id']) && $value['multi_account_id'] == 'default') {
                            return true;
                        } elseif (isset($value['multi_account_id']) && $value['multi_account_id'] != 'default' && (!empty($value['is_api_set']) && $value['is_api_set'] == true)) {
                            return true;
                        }
                    }
                } else {
                    return $bool;
                }
            }
        }
        return $bool;
    }

    public function own_angelleye_is_express_checkout_parallel_payment_not_used($bool, $order_id) {
        $angelleye_multi_account_ec_parallel_data_map = get_post_meta($order_id, '_angelleye_multi_account_ec_parallel_data_map', true);
        if (!empty($angelleye_multi_account_ec_parallel_data_map)) {
            return false;
        }
        return $bool;
    }

    public function own_angelleye_is_express_checkout_parallel_payment_handle($bool, $order_id, $gateway) {
        try {
            $processed_transaction_id = array();
            $refund_error_message_pre = __('We can not refund this order as the Express Checkout API keys are missing! Please go to multi-account setup and add API key to process the refund', '');
            $refund_error_message_after = array();
            $angelleye_multi_account_ec_parallel_data_map = get_post_meta($order_id, '_angelleye_multi_account_ec_parallel_data_map', true);
            foreach ($angelleye_multi_account_ec_parallel_data_map as $key => $value) {
                if (!empty($value['product_id']) && isset($value['is_api_set']) && $value['is_api_set'] == false) {
                    $product = wc_get_product($value['product_id']);
                    $refund_error_message_after[] = $product->get_title();
                }
            }
            if (!empty($refund_error_message_after)) {
                $refund_error = $refund_error_message_pre . ' ' . implode(", ", $refund_error_message_after);
                return new WP_Error('invalid_refund', $refund_error);
            }
            if (!empty($angelleye_multi_account_ec_parallel_data_map)) {
                foreach ($angelleye_multi_account_ec_parallel_data_map as $key => $value) {
                    if (!in_array($value['transaction_id'], $processed_transaction_id)) {
                        $this->angelleye_express_checkout_load_paypal($value, $gateway, $order_id);
                        $processed_transaction_id[] = $value['transaction_id'];
                        if (!empty($this->paypal_response['REFUNDTRANSACTIONID'])) {
                            $angelleye_multi_account_ec_parallel_data_map[$key]['REFUNDTRANSACTIONID'] = $this->paypal_response['REFUNDTRANSACTIONID'];
                            $angelleye_multi_account_ec_parallel_data_map[$key]['GROSSREFUNDAMT'] = $this->paypal_response['GROSSREFUNDAMT'];
                        } else {
                            $angelleye_multi_account_ec_parallel_data_map[$key]['delete_refund_item'] = 'yes';
                        }
                    }
                }
                $order = wc_get_order($order_id);
                foreach ($order->get_refunds() as $refund) {
                    foreach ($refund->get_items('line_item') as $cart_item_key => $refunded_item) {
                        wc_delete_order_item($cart_item_key);
                    }
                }
                update_post_meta($order_id, '_angelleye_multi_account_ec_parallel_data_map', $angelleye_multi_account_ec_parallel_data_map);
                update_post_meta($order_id, '_multi_account_refund_amount', $this->final_refund_amt);
                return true;
            }
            return false;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_express_checkout_load_paypal($value, $gateway, $order_id) {
        if (!empty($value['multi_account_id'])) {
            if ($value['multi_account_id'] == 'default') {
                $testmode = 'yes' === $gateway->get_option('testmode', 'yes');
                if ($testmode === true) {
                    $PayPalConfig = array(
                        'Sandbox' => $testmode,
                        'APIUsername' => $gateway->get_option('sandbox_api_username'),
                        'APIPassword' => $gateway->get_option('sandbox_api_password'),
                        'APISignature' => $gateway->get_option('sandbox_api_signature')
                    );
                } else {
                    $PayPalConfig = array(
                        'Sandbox' => $testmode,
                        'APIUsername' => $gateway->get_option('api_username'),
                        'APIPassword' => $gateway->get_option('api_password'),
                        'APISignature' => $gateway->get_option('api_signature')
                    );
                }
            } elseif ($value['is_api_set'] === true) {
                $microprocessing_array = get_post_meta($value['multi_account_id']);
                if (!empty($microprocessing_array['woocommerce_paypal_express_testmode']) && $microprocessing_array['woocommerce_paypal_express_testmode'][0] == 'on') {
                    $testmode = true;
                } else {
                    $testmode = false;
                }
                if ($testmode) {
                    $PayPalConfig = array(
                        'Sandbox' => $testmode,
                        'APIUsername' => $microprocessing_array['woocommerce_paypal_express_sandbox_api_username'][0],
                        'APIPassword' => $microprocessing_array['woocommerce_paypal_express_sandbox_api_password'][0],
                        'APISignature' => $microprocessing_array['woocommerce_paypal_express_sandbox_api_signature'][0]
                    );
                } else {
                    $PayPalConfig = array(
                        'Sandbox' => $testmode,
                        'APIUsername' => $microprocessing_array['woocommerce_paypal_express_api_username'][0],
                        'APIPassword' => $microprocessing_array['woocommerce_paypal_express_api_signature'][0],
                        'APISignature' => $microprocessing_array['woocommerce_paypal_express_api_password'][0]
                    );
                }
            }
            if (!empty($PayPalConfig)) {
                if (!class_exists('Angelleye_PayPal_WC')) {
                    require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
                }
                $this->paypal = new Angelleye_PayPal_WC($PayPalConfig);
                $this->angelleye_process_refund($order_id, $value);
            }
        }
    }

    public function angelleye_process_refund($order_id, $value) {
        $order = wc_get_order($order_id);
        WC_Gateway_PayPal_Express_AngellEYE::log('Begin Refund');
        $transaction_id = $value['transaction_id'];
        if (!$order || empty($transaction_id)) {
            return false;
        }
        WC_Gateway_PayPal_Express_AngellEYE::log('Transaction ID: ' . print_r($transaction_id, true));
        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }
            $reason = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }
        $RTFields = array(
            'transactionid' => $transaction_id,
            'refundtype' => 'Full',
            'currencycode' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),
            'note' => '',
        );
        $PayPalRequestData = array('RTFields' => $RTFields);
        WC_Gateway_PayPal_Express_AngellEYE::log('Refund Request: ' . print_r($PayPalRequestData, true));
        $this->paypal_response = $this->paypal->RefundTransaction($PayPalRequestData);
        AngellEYE_Gateway_Paypal::angelleye_paypal_for_woocommerce_curl_error_handler($this->paypal_response, $methos_name = 'RefundTransaction', $gateway = 'PayPal Express Checkout', $this->gateway->error_email_notify);
        WC_Gateway_PayPal_Express_AngellEYE::log('Test Mode: ' . $this->testmode);
        WC_Gateway_PayPal_Express_AngellEYE::log('Endpoint: ' . $this->gateway->API_Endpoint);
        $PayPalRequest = isset($this->paypal_response['RAWREQUEST']) ? $this->paypal_response['RAWREQUEST'] : '';
        $PayPalResponse = isset($this->paypal_response['RAWRESPONSE']) ? $this->paypal_response['RAWRESPONSE'] : '';
        WC_Gateway_PayPal_Express_AngellEYE::log('Request: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalRequest)), true));
        WC_Gateway_PayPal_Express_AngellEYE::log('Response: ' . print_r($this->paypal->NVPToArray($this->paypal->MaskAPIResult($PayPalResponse)), true));
        if ($this->paypal->APICallSuccessful($this->paypal_response['ACK'])) {
            $this->final_refund_amt = $this->final_refund_amt + $this->paypal_response['GROSSREFUNDAMT'];
            $order->add_order_note(sprintf(__('Refund Transaction ID: %s ,  Refund amount: %s', 'paypal-for-woocommerce-multi-account-management'), $this->paypal_response['REFUNDTRANSACTIONID'], $this->paypal_response['GROSSREFUNDAMT']));
            update_post_meta($order_id, 'Refund Transaction ID', $this->paypal_response['REFUNDTRANSACTIONID']);
        }
    }

    public function own_woocommerce_order_item_add_action_buttons($order) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $angelleye_multi_account_ec_parallel_data_map = get_post_meta($order_id, '_angelleye_multi_account_ec_parallel_data_map', true);
        if (!empty($angelleye_multi_account_ec_parallel_data_map)) {
            echo sprintf('<br><span class="description"><span class="woocommerce-help-tip" data-tip="%s"></span>%s</span>', MULTI_ACCOUNT_REFUND_NOTICE, MULTI_ACCOUNT_REFUND_NOTICE);
            //echo "<br><span class='description'><span class='woocommerce-help-tip' data-tip=" . MULTI_ACCOUNT_REFUND_NOTICE . "></span>" . MULTI_ACCOUNT_REFUND_NOTICE . "</span>";
        }
    }

    public function own_woocommerce_order_fully_refunded($order_id, $refund_id) {
        if (!empty($order_id)) {
            $order = wc_get_order($order_id);
            $refund = wc_get_order($refund_id);
            if ( $order->has_status( wc_get_is_paid_statuses() ) ) {
                if($order->get_total() == $refund->get_amount()) {
                    do_action( 'woocommerce_order_fully_refunded', $order_id, $refund_id );
                    $parent_status = apply_filters( 'woocommerce_order_fully_refunded_status', 'refunded', $order_id, $refund_id );
                    if ( $parent_status ) {
                            $order->update_status( $parent_status );
                    }
                }
            }
        }
    }
    
    public function own_woocommerce_create_refund($refund, $args) {
        $order_id = $refund->get_parent_id();
        if( !empty($order_id)) {
            $order = wc_get_order($order_id);
            $payment_method = version_compare(WC_VERSION, '3.0', '<') ? $order->payment_method : $order->get_payment_method();
            $angelleye_multi_account_ec_parallel_data_map = get_post_meta($order_id, '_angelleye_multi_account_ec_parallel_data_map', true);
            if (!empty($angelleye_multi_account_ec_parallel_data_map) && $payment_method == 'paypal_express') {
                $refund->set_amount( $order->get_total() );
                $args['amount'] = $order->get_total();
                unset($args['line_items']);
            }
            remove_action( 'woocommerce_order_partially_refunded', array( 'WC_Emails', 'send_transactional_email' ) );
        }
    }
}