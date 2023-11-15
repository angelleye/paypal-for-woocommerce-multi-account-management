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
    public $map_item_with_account;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->key = 'paypal';
        $this->map_item_with_account = array();
        add_filter('woocommerce_payment_gateways', array($this, 'angelleye_add_paypal_gateway'), 1000);
    }

    public function angelleye_add_paypal_gateway($methods) {
        foreach ($methods as $key => $method) {
            if (in_array($method, array('WC_Gateway_Paypal'))) {
                unset($methods[$key]);
                $methods[] = 'WC_Gateway_Paypal_Multi_Account_Management';
                break;
            }
        }
        return $methods;
    }

    public function angelleye_woocommerce_paypal_args($request, $order) {
        global $user_ID;
        $this->gateway = $this->get_wc_gateway();
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
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
        $order_total = $order->get_total();

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
                    'compare' => '='
                ),
                array(
                    'key' => 'woocommerce_paypal_testmode',
                    'value' => ($this->gateway->testmode == true) ? 'on' : '',
                    'compare' => '='
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
                    $checkout_custom_fields = angelleye_display_checkout_custom_field();
                    if (!empty($checkout_custom_fields)) {
                        foreach ($checkout_custom_fields as $field_key => $field_data) {
                            $custom_field_value = get_post_meta($value->ID, $field_key, true);
                            if (!empty($custom_field_value)) {
                                if (!empty($order_id) && $order_id > 0) {
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
                    $buyer_countries = get_post_meta($value->ID, 'buyer_countries', true);
                    if (!empty($buyer_countries)) {
                        foreach ($buyer_countries as $buyer_countries_key => $buyer_countries_value) {
                            if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal') {
                                if (!empty($order_id) && $order_id > 0) {
                                    $order = wc_get_order($order_id);
                                    $billing_country = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_country : $order->get_billing_country();
                                    $shipping_country = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country();
                                    if (!empty($billing_country) && $billing_country == $buyer_countries_value) {
                                        $passed_rules['buyer_countries'] = true;
                                        break;
                                    } elseif(!empty($shipping_country) && $shipping_country == $buyer_countries_value) {
                                        $passed_rules['buyer_countries'] = true;
                                        break;
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
                            if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal') {
                                if (!empty($order_id) && $order_id > 0) {
                                    $order = wc_get_order($order_id);
                                    $billing_state = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_state : $order->get_billing_state();
                                    $shipping_state = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state();
                                    if (!empty($billing_state) && $billing_state == $buyer_states_value) {
                                        $passed_rules['buyer_states'] = true;
                                        break;
                                    } elseif(!empty($shipping_state) && $shipping_state == $buyer_states_value) {
                                        $passed_rules['buyer_states'] = true;
                                        break;
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
                            if (!empty($gateway_setting->id) && $gateway_setting->id == 'paypal') {
                                if (!empty($order_id) && $order_id > 0) {
                                    $order = wc_get_order($order_id);
                                    $billing_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_postcode : $order->get_billing_postcode();
                                    $shipping_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_postcode : $order->get_shipping_postcode();
                                    if (!empty($billing_postcode) && $billing_postcode == $postcode_value) {
                                        $passed_rules['postcode'] = true;
                                        break;
                                    } elseif(!empty($shipping_postcode) && $shipping_postcode == $postcode_value) {
                                        $passed_rules['postcode'] = true;
                                        break;
                                    }
                                    
                                }
                            }
                        }
                    } else {
                        $passed_rules['billing_postcode'] = true;
                    }
                    if (empty($passed_rules['billing_postcode'])) {
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
                    
                    foreach ($order->get_items() as $cart_item_key => $values) {
                        $line_item = $values->get_data();
                        $product = version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_product_from_item( $values ) : $values->get_product();
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
                        $post_author_id = get_post_field( 'post_author', $product_id );
                        $woocommerce_paypal_express_api_user = get_post_meta($value->ID, 'woocommerce_paypal_express_api_user', true);
                        if (!empty($woocommerce_paypal_express_api_user) && $woocommerce_paypal_express_api_user != 'all' ) {
                            if($post_author_id != $woocommerce_paypal_express_api_user) {
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
                                        $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                        continue;
                                    }
                                }
                            }
                        }
                                
                        $product_shipping_class = $product->get_shipping_class_id();
                        $shipping_class = get_post_meta($value->ID, 'shipping_class', true);
                        if (!empty($shipping_class) && $shipping_class != 'all' ) {
                            if($product_shipping_class != $shipping_class) {
                                $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                continue;
                            }
                        }
                    }
                }
                unset($passed_rules);
            }
        }

        if (count($result) > 0) {
            foreach ($result as $key => $value) {
                if (!empty($value->ID)) {
                    $microprocessing_array = get_post_meta($value->ID);
                    if (!empty($microprocessing_array['woocommerce_paypal_express_api_condition_sign'][0]) && isset($microprocessing_array['woocommerce_paypal_express_api_condition_value'][0])) {
                        switch ($microprocessing_array['woocommerce_paypal_express_api_condition_sign'][0]) {
                            case 'equalto':
                                if ($order_total == $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0]) {
                                    if ($this->gateway->testmode == true) {
                                        $this->final_associate_account[$value->ID]['email'] = $microprocessing_array['woocommerce_paypal_sandbox_email'][0];
                                    } else {
                                        $this->final_associate_account[$value->ID]['email'] = $microprocessing_array['woocommerce_paypal_email'][0];
                                    }
                                    if ($this->angelleye_is_multi_account_api_set($microprocessing_array, $this->gateway)) {
                                        $this->final_associate_account[$value->ID]['is_api_set'] = true;
                                    } else {
                                        $this->final_associate_account[$value->ID]['is_api_set'] = false;
                                    }
                                    $this->final_associate_account[$value->ID]['is_sandbox'] = ($this->gateway->testmode == true) ? 'on' : 'off';
                                    $this->final_associate_account[$value->ID]['multi_account_id'] = $value->ID;
                                }
                                break;
                            case 'lessthan':
                                if ($order_total < $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0]) {
                                    if ($this->gateway->testmode == true) {
                                        $this->final_associate_account[$value->ID]['email'] = $microprocessing_array['woocommerce_paypal_sandbox_email'][0];
                                    } else {
                                        $this->final_associate_account[$value->ID]['email'] = $microprocessing_array['woocommerce_paypal_email'][0];
                                    }
                                    if ($this->angelleye_is_multi_account_api_set($microprocessing_array, $this->gateway)) {
                                        $this->final_associate_account[$value->ID]['is_api_set'] = true;
                                    } else {
                                        $this->final_associate_account[$value->ID]['is_api_set'] = false;
                                    }
                                    $this->final_associate_account[$value->ID]['is_sandbox'] = ($this->gateway->testmode == true) ? 'on' : 'off';
                                    $this->final_associate_account[$value->ID]['multi_account_id'] = $value->ID;
                                }
                                break;
                            case 'greaterthan':
                                if ($order_total > $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0]) {
                                    if ($this->gateway->testmode == true) {
                                        $this->final_associate_account[$value->ID]['email'] = $microprocessing_array['woocommerce_paypal_sandbox_email'][0];
                                    } else {
                                        $this->final_associate_account[$value->ID]['email'] = $microprocessing_array['woocommerce_paypal_email'][0];
                                    }
                                    if ($this->angelleye_is_multi_account_api_set($microprocessing_array, $this->gateway)) {
                                        $this->final_associate_account[$value->ID]['is_api_set'] = true;
                                    } else {
                                        $this->final_associate_account[$value->ID]['is_api_set'] = false;
                                    }
                                    $this->final_associate_account[$value->ID]['is_sandbox'] = ($this->gateway->testmode == true) ? 'on' : 'off';
                                    $this->final_associate_account[$value->ID]['multi_account_id'] = $value->ID;
                                }
                                break;
                        }
                    }
                }
            }
            if (count($this->final_associate_account) > 0) {
                return $this->angelleye_paypal_multi_account_map_data($request, $order_id, $this->final_associate_account);
            } else {
                return $request;
            }
        }
        return $request;
    }

    public function get_wc_gateway() {
        global $woocommerce;
        $this->gateway = $woocommerce->payment_gateways->payment_gateways();
        return $this->gateway[$this->key];
    }

    public function angelleye_is_multi_account_api_set($microprocessing_array, $gateway) {
        if ($gateway->testmode) {
            if (!empty($microprocessing_array['woocommerce_paypal_sandbox_api_username'][0]) && !empty($microprocessing_array['woocommerce_paypal_sandbox_api_password'][0]) && !empty($microprocessing_array['woocommerce_paypal_sandbox_api_signature'][0])) {
                return true;
            }
        } else {
            if (!empty($microprocessing_array['woocommerce_paypal_api_username'][0]) && !empty($microprocessing_array['woocommerce_paypal_api_signature'][0]) && !empty($microprocessing_array['woocommerce_paypal_api_password'][0])) {
                return true;
            }
        }
        return false;
    }

    public function angelleye_paypal_multi_account_map_data($request, $order_id, $final_associate_account) {
        if(!empty($order_id)) {
            $order = wc_get_order($order_id);
        }
        foreach ($final_associate_account as $key => $value) {
            if(!empty($value['email'])) {
                $request['business'] = $value['email'];
                $order->update_meta_data('_angelleye_multi_account_ec_parallel_data_map', $value);
                $order->save_meta_data();
                return $request;
            }
        }
        return $request;
    }

}

class WC_Gateway_Paypal_Multi_Account_Management extends WC_Gateway_Paypal {

    private static $instance;

    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {

        add_filter('woocommerce_payment_gateway_supports', array($this, 'own_woocommerce_payment_gateway_supports'), 99, 3);
        add_filter('woocommerce_paypal_refund_request', array($this, 'angelleye_woocommerce_paypal_refund_request'), 10, 4);
        parent::__construct();
    }

    public function angelleye_woocommerce_paypal_refund_request($request, $order, $amount, $reason) {
        $request['TRANSACTIONID'] = $order->get_transaction_id();
        $angelleye_multi_account_ec_parallel_data_map = $order->get_mata('_angelleye_multi_account_paypal_data_map', true);
        if (!empty($angelleye_multi_account_ec_parallel_data_map)) {
            if (empty($angelleye_multi_account_ec_parallel_data_map['is_api_set']) || apply_filters('angelleye_pfwma_is_api_set', $angelleye_multi_account_ec_parallel_data_map['is_api_set'], $angelleye_multi_account_ec_parallel_data_map) === false) {
                return new WP_Error('invalid_refund', __('You can not refund this order, as the credentials are not present for the order', 'paypal-for-woocommerce-multi-account-management'));
            } else {
                 return $this->angelleye_set_api_details_refund($request, $angelleye_multi_account_ec_parallel_data_map);
            }
        } 
        return $request;
    }

    public function angelleye_set_api_details_refund($request, $microprocessing_array) {
        $microprocessing_array = get_post_meta($microprocessing_array['multi_account_id']);
        if (!empty($microprocessing_array['woocommerce_paypal_testmode']) && $microprocessing_array['woocommerce_paypal_testmode'][0] == 'on') {
            $testmode = true;
        } else {
            $testmode = false;
        }
        if ($testmode) {
            $request['SIGNATURE'] = $microprocessing_array['woocommerce_paypal_sandbox_api_signature'][0];
            $request['USER'] = $microprocessing_array['woocommerce_paypal_sandbox_api_username'][0];
            $request['PWD'] = $microprocessing_array['woocommerce_paypal_sandbox_api_password'][0];
        } else {
            $request['USER'] = $microprocessing_array['woocommerce_paypal_api_username'][0];
            $request['SIGNATURE'] = $microprocessing_array['woocommerce_paypal_api_signature'][0];
            $request['PWD'] = $microprocessing_array['woocommerce_paypal_api_password'][0];
        }
        return $request;
    }

    public function can_refund_order($order) {
        $angelleye_multi_account_paypal_data_map = $order->get_meta('_angelleye_multi_account_paypal_data_map', true);
        if (!empty($angelleye_multi_account_paypal_data_map)) {
            if (!empty($angelleye_multi_account_paypal_data_map['is_api_set'] && apply_filters('angelleye_pfwma_is_api_set', $angelleye_multi_account_paypal_data_map['is_api_set'], $angelleye_multi_account_paypal_data_map) === true)) {
                return true;
            } else {
                return false;
            }
        } else {
            return parent::can_refund_order($order);
        }
        return parent::can_refund_order($order);
    }
    
    public function own_woocommerce_ppcp_payment_gateway_supports($bool, $feature, $current) {
        global $theorder;
        if ( $theorder instanceof WC_Order ) {
            if ($feature === 'refunds' && $bool === true && $current->id === 'paypal') {
                $order = $theorder;
                if ($order) {
                    $angelleye_multi_account_ppcp_parallel_data_map = $order->get_meta('_angelleye_multi_account_paypal_data_map', true);
                    if (!empty($angelleye_multi_account_ppcp_parallel_data_map)) {
                        foreach ($angelleye_multi_account_ppcp_parallel_data_map as $key => $value) {
                            if (isset($value['multi_account_id']) && $value['multi_account_id'] == 'default') {
                                return true;
                            } elseif (isset($value['multi_account_id']) && $value['multi_account_id'] != 'default' && (!empty($value['is_api_set']) && $value['is_api_set'] === true)) {
                                return true;
                            }
                        }
                    } else {
                        return $bool;
                    }
                }
            }
        }
        
        return $bool;
    }

}

WC_Gateway_Paypal_Multi_Account_Management::get_instance();
