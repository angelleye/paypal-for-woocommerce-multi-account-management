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
    public $send_items;
    public $is_commission_enable;
    public $global_ec_site_owner_commission;
    public $global_ec_site_owner_commission_label;
    public $global_ec_include_tax_shipping_in_commission;
    public $final_payment_request_data;
    public $final_paypal_request;
    public $not_divided_shipping_cost;
    public $divided_shipping_cost;
    public $always_trigger_commission_accounts;
    public $always_trigger_commission_accounts_line_items;
    public $always_trigger_commission_total_percentage;
    public $all_commision_line_item;
    public array $final_payment_summary;

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
        $this->is_commission_enable = false;
        $this->divided_shipping_cost = 0;
        $angelleye_smart_commission = get_option('angelleye_smart_commission', '');
        $this->global_ec_site_owner_commission = 0;
        $this->global_ec_site_owner_commission_label = '';
        if (isset($angelleye_smart_commission['enable']) && $angelleye_smart_commission['enable'] == 'on') {
            if (is_user_logged_in() && !empty($angelleye_smart_commission['role'])) {
                $customer_id = get_current_user_id();
                $user = new WP_User($customer_id);
                if (!empty($user->roles) && is_array($user->roles)) {
                    foreach ($angelleye_smart_commission['role'] as $ro_key => $ro_value) {
                        if (in_array($ro_value, (array) $user->roles, true)) {
                            $this->global_ec_site_owner_commission = $angelleye_smart_commission['commission'][$ro_key];
                            $this->global_ec_site_owner_commission_label = $angelleye_smart_commission['item_label'][$ro_key];
                        }
                    }
                }
            }
            if (empty($this->global_ec_site_owner_commission_label)) {
                if (isset($angelleye_smart_commission['regular_smart_commission'])) {
                    $this->global_ec_site_owner_commission = $angelleye_smart_commission['regular_smart_commission'];
                    $this->global_ec_site_owner_commission_label = $angelleye_smart_commission['regular_smart_commission_item_label'];
                }
            }
        } else {
            $this->global_ec_site_owner_commission = get_option('global_ec_site_owner_commission', 0);
            $this->global_ec_site_owner_commission_label = get_option('global_ec_site_owner_commission_label', '');
        }
        $this->global_ec_include_tax_shipping_in_commission = get_option('global_ec_include_tax_shipping_in_commission', '');
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
                'posts_per_page' => -1,
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
                        'compare' => '='
                    ),
                    array(
                        'key' => 'woocommerce_paypal_express_testmode',
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
        $this->angelleye_is_taxable = 0;
        $this->angelleye_needs_shipping = 0;
        $this->angelleye_is_discountable = 0;
        if ($total_posts > 0) {
            foreach ($result as $key => $value) {
                $passed_rules = array();
                $cart_loop_pass = 0;
                $cart_loop_not_pass = 0;
                if (!empty($value->ID)) {
                    $microprocessing_array = get_post_meta($value->ID);
                    if (isset($microprocessing_array['woocommerce_paypal_express_always_trigger'][0]) && 'on' === $microprocessing_array['woocommerce_paypal_express_always_trigger'][0]) {
                        $this->always_trigger_commission_accounts[$value->ID]['commission_amount_percentage'] = $microprocessing_array['always_trigger_commission'][0];
                        $this->always_trigger_commission_total_percentage = $this->always_trigger_commission_total_percentage + $microprocessing_array['always_trigger_commission'][0];
                        $this->always_trigger_commission_accounts[$value->ID]['commission_item_label'] = !empty($microprocessing_array['always_trigger_commission_item_label'][0]) ? $microprocessing_array['always_trigger_commission_item_label'][0] : 'commission';
                        continue;
                    }
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
                                } else {
                                    $post_checkout_data = WC()->session->get('post_data');
                                    if (!empty($post_checkout_data)) {
                                        if (empty($post_checkout_data[$field_key])) {
                                            $passed_rules['custom_fields'] = true;
                                        } elseif (!empty($post_checkout_data[$field_key]) && $post_checkout_data[$field_key] == $custom_field_value) {
                                            $passed_rules['custom_fields'] = true;
                                        } else {
                                            $passed_rules['custom_fields'] = '';
                                            break;
                                        }
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
                            if (!empty($order_id) && $order_id > 0) {
                                $order = wc_get_order($order_id);
                                $billing_country = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_country : $order->get_billing_country();
                                $shipping_country = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country();
                                if (!empty($billing_country) && $billing_country == $buyer_countries_value) {
                                    $passed_rules['buyer_countries'] = true;
                                    break;
                                } elseif (!empty($shipping_country) && $shipping_country == $buyer_countries_value) {
                                    $passed_rules['buyer_countries'] = true;
                                    break;
                                }
                            } else {
                                $post_checkout_data = WC()->session->get('post_data');
                                if (empty($post_checkout_data)) {
                                    $billing_country = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_country() : WC()->customer->get_billing_country();
                                    $shipping_country = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_country() : WC()->customer->get_shipping_country();
                                    if (!empty($billing_country) && $billing_country == $buyer_countries_value) {
                                        $passed_rules['buyer_countries'] = true;
                                        break;
                                    } elseif (!empty($shipping_country) && $shipping_country == $buyer_countries_value) {
                                        $passed_rules['buyer_countries'] = true;
                                        break;
                                    }
                                } else {
                                    if (!empty($post_checkout_data['billing_country']) && $post_checkout_data['billing_country'] == $buyer_countries_value) {
                                        $passed_rules['buyer_countries'] = true;
                                        break;
                                    } elseif (!empty($post_checkout_data['shipping_country']) && $post_checkout_data['shipping_country'] == $buyer_countries_value) {
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
                        continue;
                    }



                    $buyer_states = get_post_meta($value->ID, 'buyer_states', true);
                    if (!empty($buyer_states)) {
                        foreach ($buyer_states as $buyer_states_key => $buyer_states_value) {
                            if (!empty($order_id) && $order_id > 0) {
                                $order = wc_get_order($order_id);
                                $billing_state = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_state : $order->get_billing_state();
                                $shipping_state = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state();
                                if (!empty($billing_state) && $billing_state == $buyer_states_value) {
                                    $passed_rules['buyer_states'] = true;
                                    break;
                                } elseif (!empty($shipping_state) && $shipping_state == $buyer_states_value) {
                                    $passed_rules['buyer_states'] = true;
                                    break;
                                }
                            } else {
                                $post_checkout_data = WC()->session->get('post_data');
                                if (empty($post_checkout_data)) {
                                    $billing_state = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_state() : WC()->customer->get_billing_state();
                                    $shipping_state = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_state() : WC()->customer->get_shipping_state();
                                    if (!empty($billing_state) && $billing_state == $buyer_states_value) {
                                        $passed_rules['buyer_states'] = true;
                                        break;
                                    } elseif (!empty($shipping_state) && $shipping_state == $buyer_states_value) {
                                        $passed_rules['buyer_states'] = true;
                                        break;
                                    }
                                } else {
                                    if (!empty($post_checkout_data['billing_state']) && $post_checkout_data['billing_state'] == $buyer_states_value) {
                                        $passed_rules['buyer_states'] = true;
                                        break;
                                    } elseif (!empty($post_checkout_data['shipping_state']) && $post_checkout_data['shipping_state'] == $buyer_states_value) {
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
                        continue;
                    }




                    $postcode_string = get_post_meta($value->ID, 'postcode', true);
                    if (!empty($postcode_string)) {
                        $postcode = explode(',', $postcode_string);
                        foreach ($postcode as $postcode_key => $postcode_value) {
                            if (!empty($order_id) && $order_id > 0) {
                                $order = wc_get_order($order_id);
                                $billing_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->billing_postcode : $order->get_billing_postcode();
                                $shipping_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode();
                                if (!empty($billing_postcode) && $billing_postcode == $postcode_value) {
                                    $passed_rules['postcode'] = true;
                                    break;
                                } elseif (!empty($shipping_postcode) && $shipping_postcode == $postcode_value) {
                                    $passed_rules['postcode'] = true;
                                    break;
                                }
                            } else {
                                $post_checkout_data = WC()->session->get('post_data');
                                if (empty($post_checkout_data)) {
                                    $billing_postcode = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_postcode() : WC()->customer->get_billing_postcode();
                                    $shipping_postcode = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_postcode() : WC()->customer->get_shipping_postcode();
                                    if (!empty($billing_postcode) && $billing_postcode == $postcode_value) {
                                        $passed_rules['postcode'] = true;
                                        break;
                                    } elseif (!empty($shipping_postcode) && $shipping_postcode == $postcode_value) {
                                        $passed_rules['postcode'] = true;
                                        break;
                                    }
                                } else {
                                    if (!empty($post_checkout_data['billing_postcode']) && $post_checkout_data['billing_postcode'] == $postcode_value) {
                                        $passed_rules['postcode'] = true;
                                        break;
                                    } elseif (!empty($post_checkout_data['shipping_postcode']) && $post_checkout_data['shipping_postcode'] == $postcode_value) {
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
                            $this->map_item_with_account[$product_id]['product_id'] = $product_id;
                            $this->map_item_with_account[$product_id]['order_item_id'] = $cart_item_key;
                            if ($product->is_taxable()) {
                                if ($this->map_item_with_account[$product_id]['is_taxable'] != true) {
                                    $this->map_item_with_account[$product_id]['is_taxable'] = true;
                                    $this->map_item_with_account[$product_id]['tax'] = $line_item['total_tax'];
                                    $this->angelleye_is_taxable = $this->angelleye_is_taxable + 1;
                                }
                            } else {
                                $this->map_item_with_account[$product_id]['is_taxable'] = false;
                            }
                            if ($product->needs_shipping() && apply_filters('angelleye_multi_account_need_shipping', true, $order_id, $product_id)) {
                                if ($this->map_item_with_account[$product_id]['needs_shipping'] != true) {
                                    $this->angelleye_needs_shipping = $this->angelleye_needs_shipping + 1;
                                    $this->map_item_with_account[$product_id]['needs_shipping'] = true;
                                }
                            } else {
                                $this->map_item_with_account[$product_id]['needs_shipping'] = false;
                            }
                            if ($order->get_total_discount() > 0) {
                                if ($line_item['subtotal'] != $line_item['total']) {
                                    $this->map_item_with_account[$product_id]['is_discountable'] = true;
                                    $this->angelleye_is_discountable = $this->angelleye_is_discountable + 1;
                                    $discount_amount = $line_item['subtotal'] - $line_item['total'];
                                    if ($discount_amount > 0) {
                                        $this->map_item_with_account[$product_id]['discount'] = $discount_amount;
                                    }
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

                            $post_author_id = get_post_field('post_author', $product_id);
                            $woocommerce_paypal_express_api_user = get_post_meta($value->ID, 'woocommerce_paypal_express_api_user', true);
                            if (!empty($woocommerce_paypal_express_api_user) && $woocommerce_paypal_express_api_user != 'all') {
                                if ($post_author_id != $woocommerce_paypal_express_api_user) {
                                    $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                    continue;
                                }
                            }

                            if (isset(WC()->cart) && sizeof(WC()->cart->get_cart()) > 0) {
                                $mul_shipping_zone = get_post_meta($value->ID, 'shipping_zone', true);
                                if (!empty($mul_shipping_zone) && $mul_shipping_zone != 'all') {
                                    $shipping_packages = WC()->cart->get_shipping_packages();
                                    if (!empty($shipping_packages)) {
                                        $woo_shipping_zone = wc_get_shipping_zone(reset($shipping_packages));
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
                            if (!empty($shipping_class) && $shipping_class != 'all') {
                                if ($product_shipping_class != $shipping_class) {
                                    $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                    continue;
                                }
                            }
                            $product_ids = get_post_meta($value->ID, 'woocommerce_paypal_express_api_product_ids', true);
                            $cart_products_id = array();
                            if (isset($line_item['variation_id'])) {
                                $cart_products_id[] = $line_item['variation_id'];
                            }
                            $cart_products_id[] = $product_id;
                            if (!empty($product_ids)) {
                                if (!array_intersect((array) $cart_products_id, $product_ids)) {
                                    $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                    continue;
                                }
                            }
                            $this->map_item_with_account[$product_id]['multi_account_id'] = $value->ID;
                            if (isset($microprocessing_array['ec_site_owner_commission'][0]) && !empty($microprocessing_array['ec_site_owner_commission'][0]) && $microprocessing_array['ec_site_owner_commission'][0] > 0) {
                                $this->map_item_with_account[$product_id]['is_commission_enable'] = true;
                                $this->is_commission_enable = true;
                                $this->map_item_with_account[$product_id]['ec_site_owner_commission_label'] = !empty($microprocessing_array['ec_site_owner_commission_label'][0]) ? $microprocessing_array['ec_site_owner_commission_label'][0] : __('Commission', 'paypal-for-woocommerce-multi-account-management');
                                $this->map_item_with_account[$product_id]['ec_site_owner_commission'] = $microprocessing_array['ec_site_owner_commission'][0];
                            } elseif ($this->global_ec_site_owner_commission > 0) {
                                $this->map_item_with_account[$product_id]['is_commission_enable'] = true;
                                $this->is_commission_enable = true;
                                $this->map_item_with_account[$product_id]['ec_site_owner_commission_label'] = !empty($this->global_ec_site_owner_commission_label) ? $this->global_ec_site_owner_commission_label : __('Commission', 'paypal-for-woocommerce-multi-account-management');
                                $this->map_item_with_account[$product_id]['ec_site_owner_commission'] = $this->global_ec_site_owner_commission;
                            } else {
                                $this->map_item_with_account[$product_id]['is_commission_enable'] = false;
                            }
                            if ($gateways->testmode == true) {
                                $paypal_email = isset($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0]) ? $microprocessing_array['woocommerce_paypal_express_sandbox_email'][0] : null;
                                if (empty($paypal_email)) {
                                    $paypal_email = isset($microprocessing_array['woocommerce_paypal_express_sandbox_api_username'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_api_username'][0]) ? $microprocessing_array['woocommerce_paypal_express_sandbox_api_username'][0] : null;
                                }
                                $this->map_item_with_account[$product_id]['multi_account_identifier'] = $paypal_email;

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
                                $paypal_email = isset($microprocessing_array['woocommerce_paypal_express_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_email'][0]) ? $microprocessing_array['woocommerce_paypal_express_email'][0] : null;
                                if (empty($paypal_email)) {
                                    $paypal_email = isset($microprocessing_array['woocommerce_paypal_express_api_username'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_api_username'][0]) ? $microprocessing_array['woocommerce_paypal_express_api_username'][0] : null;
                                }
                                $this->map_item_with_account[$product_id]['multi_account_identifier'] = $paypal_email;

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
                                    if ($this->map_item_with_account[$product_id]['is_taxable'] != true) {
                                        $this->map_item_with_account[$product_id]['is_taxable'] = true;
                                        $this->angelleye_is_taxable = $this->angelleye_is_taxable + 1;
                                    }
                                    if (!empty($cart_item['line_tax'])) {
                                        $this->map_item_with_account[$product_id]['tax'] = $cart_item['line_tax'];
                                    } else {
                                        $this->map_item_with_account[$product_id]['tax'] = $cart_item['line_subtotal_tax'];
                                    }
                                } else {
                                    $this->map_item_with_account[$product_id]['is_taxable'] = false;
                                }
                                if ($product->needs_shipping() && apply_filters('angelleye_multi_account_need_shipping', true, '', $product_id)) {
                                    if ($this->map_item_with_account[$product_id]['needs_shipping'] != true) {
                                        $this->angelleye_needs_shipping = $this->angelleye_needs_shipping + 1;
                                        $this->map_item_with_account[$product_id]['needs_shipping'] = true;
                                    }
                                } else {
                                    $this->map_item_with_account[$product_id]['needs_shipping'] = false;
                                }
                                if (WC()->cart->get_cart_discount_total() > 0) {
                                    if ($cart_item['line_subtotal'] != $cart_item['line_total']) {
                                        if ($this->map_item_with_account[$product_id]['is_discountable'] != true) {
                                            $this->angelleye_is_discountable = $this->angelleye_is_discountable + 1;
                                            $this->map_item_with_account[$product_id]['is_discountable'] = true;
                                        }
                                        $discount_amount = $cart_item['line_subtotal'] - $cart_item['line_total'];
                                        if ($discount_amount > 0) {
                                            $this->map_item_with_account[$product_id]['discount'] = $cart_item['line_subtotal'] - $cart_item['line_total'];
                                        }
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
                                $cart_products_id = array();
                                if (isset($cart_item['variation_id'])) {
                                    $cart_products_id[] = $cart_item['variation_id'];
                                }
                                $cart_products_id[] = $product_id;
                                if (!empty($product_ids)) {
                                    if (!array_intersect((array) $cart_products_id, $product_ids)) {
                                        $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                        continue;
                                    }
                                }

                                $post_author_id = get_post_field('post_author', $product_id);
                                $woocommerce_paypal_express_api_user = get_post_meta($value->ID, 'woocommerce_paypal_express_api_user', true);
                                if (!empty($woocommerce_paypal_express_api_user) && $woocommerce_paypal_express_api_user != 'all') {
                                    if ($post_author_id != $woocommerce_paypal_express_api_user) {
                                        $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                        continue;
                                    }
                                }

                                $mul_shipping_zone = get_post_meta($value->ID, 'shipping_zone', true);
                                if (!empty($mul_shipping_zone) && $mul_shipping_zone != 'all') {
                                    $shipping_packages = WC()->cart->get_shipping_packages();
                                    if (!empty($shipping_packages)) {
                                        $woo_shipping_zone = wc_get_shipping_zone(reset($shipping_packages));
                                        $zone_id = $woo_shipping_zone->get_id();
                                        if ($zone_id != $mul_shipping_zone) {
                                            $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                            continue;
                                        }
                                    }
                                }

                                $product_shipping_class = $product->get_shipping_class_id();
                                $shipping_class = get_post_meta($value->ID, 'shipping_class', true);
                                if (!empty($shipping_class) && $shipping_class != 'all') {
                                    if ($product_shipping_class != $shipping_class) {
                                        $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                        continue;
                                    }
                                }

                                $this->map_item_with_account[$product_id]['multi_account_id'] = $value->ID;
                                if (isset($microprocessing_array['ec_site_owner_commission'][0]) && !empty($microprocessing_array['ec_site_owner_commission'][0]) && $microprocessing_array['ec_site_owner_commission'][0] > 0) {
                                    $this->map_item_with_account[$product_id]['is_commission_enable'] = true;
                                    $this->is_commission_enable = true;
                                    $this->map_item_with_account[$product_id]['ec_site_owner_commission_label'] = !empty($microprocessing_array['ec_site_owner_commission_label'][0]) ? $microprocessing_array['ec_site_owner_commission_label'][0] : __('Commission', 'paypal-for-woocommerce-multi-account-management');
                                    $this->map_item_with_account[$product_id]['ec_site_owner_commission'] = $microprocessing_array['ec_site_owner_commission'][0];
                                } elseif ($this->global_ec_site_owner_commission > 0) {
                                    $this->map_item_with_account[$product_id]['is_commission_enable'] = true;
                                    $this->is_commission_enable = true;
                                    $this->map_item_with_account[$product_id]['ec_site_owner_commission_label'] = !empty($this->global_ec_site_owner_commission_label) ? $this->global_ec_site_owner_commission_label : __('Commission', 'paypal-for-woocommerce-multi-account-management');
                                    $this->map_item_with_account[$product_id]['ec_site_owner_commission'] = $this->global_ec_site_owner_commission;
                                } else {
                                    $this->map_item_with_account[$product_id]['is_commission_enable'] = false;
                                }
                                if ($gateways->testmode == true) {
                                    $paypal_email = isset($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0]) ? $microprocessing_array['woocommerce_paypal_express_sandbox_email'][0] : null;
                                    if (empty($paypal_email)) {
                                        $paypal_email = isset($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_api_username'][0]) ? $microprocessing_array['woocommerce_paypal_express_sandbox_api_username'][0] : null;
                                    }
                                    $this->map_item_with_account[$product_id]['multi_account_identifier'] = $paypal_email;
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
                                    $paypal_email = isset($microprocessing_array['woocommerce_paypal_express_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_email'][0]) ? $microprocessing_array['woocommerce_paypal_express_email'][0] : null;
                                    if (empty($paypal_email)) {
                                        $paypal_email = isset($microprocessing_array['woocommerce_paypal_express_api_username'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_api_username'][0]) ? $microprocessing_array['woocommerce_paypal_express_api_username'][0] : null;
                                    }
                                    $this->map_item_with_account[$product_id]['multi_account_identifier'] = $paypal_email;
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

            if (isset($result) && count($result) > 0 && ((isset($this->map_item_with_account) && count($this->map_item_with_account)) || (isset($this->always_trigger_commission_accounts) && count($this->always_trigger_commission_accounts)))) {
                return $this->angelleye_modified_ec_parallel_parameter($request, $gateways, $order_id);
            }
        }
        return $request;
    }

    public function angelleye_paypal_for_woocommerce_multi_account_api_paypal_express($request = null, $gateways = null, $current = null, $order_id = null, $is_force_validate = 'no') {
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
        $angelleye_payment_load_balancer = get_option('angelleye_payment_load_balancer', '');
        if ($angelleye_payment_load_balancer != '') {
            if ($is_force_validate === 'yes') {
                $this->angelleye_unset_multi_account_dataset($gateways);
            }
            return $this->angelleye_get_account_for_ec_payment_load_balancer($gateways, $gateway_setting, $order_id, $request);
        } else {
            return $this->angelleye_get_account_for_ec_parallel_payments($gateways, $gateway_setting, $order_id, $request);
        }
    }

    public function angelleye_unset_multi_account_dataset($gateways) {
        try {
            if (isset($gateways) || isset($gateways->testmode)) {
                if ($gateways->testmode == true) {
                    $session_key_account = 'angelleye_sandbox_payment_load_balancer_ec_account';
                } else {
                    $session_key_account = 'angelleye_payment_load_balancer_ec_account';
                }
                $angelleye_payment_load_balancer_account = WC()->session->get($session_key_account);
                if (!empty($angelleye_payment_load_balancer_account) && isset($angelleye_payment_load_balancer_account['multi_account_id']) && $angelleye_payment_load_balancer_account['multi_account_id'] !== 'default') {
                    update_post_meta($angelleye_payment_load_balancer_account['multi_account_id'], 'woocommerce_paypal_express_enable', '');
                }
            }
            delete_transient('angelleye_multi_ec_payment_load_balancer_synce_sandbox');
            delete_transient('angelleye_multi_ec_payment_load_balancer_synce');
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
        } catch (Exception $ex) {
            
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
                        'compare' => '='
                    ),
                    array(
                        'key' => 'woocommerce_paypal_express_api_username',
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

    public function angelleye_modified_ec_parallel_parameter($request, $gateways, $order_id) {
        $order = wc_get_order($order_id);
        $this->send_items = $gateways->send_items;
        $this->map_item_with_account = apply_filters('angelleye_ec_parallel_parameter', $this->map_item_with_account);
        $new_payments = array();
        $this->final_payment_request_data = array();
        $default_new_payments_line_item = array();
        if (!empty($request['Payments'])) {
            $old_payments = $request['Payments'];
            unset($request['Payments']);
        } else {
            $old_payments = array();
        }
        if (!empty($request['SECFields']['customerservicenumber'])) {
            unset($request['SECFields']['customerservicenumber']);
        }
        if (wc_tax_enabled()) {
            if (WC()->cart->is_empty()) {
                $this->taxamt = round($order->get_shipping_tax(), $this->decimals);
                $total_tax = $order->get_total_tax();
                if (isset($total_tax) && $total_tax > 0) {
                    $this->tax_array = $this->angelleye_get_extra_fee_array($this->taxamt, $this->angelleye_needs_shipping, 'tax');
                }
            } else {
                $this->taxamt = round(WC()->cart->get_shipping_tax() + WC()->cart->get_fee_tax(), $this->decimals);
                $total_tax = WC()->cart->get_total_tax();
                if (isset($total_tax) && $total_tax > 0) {
                    $this->tax_array = $this->angelleye_get_extra_fee_array($this->taxamt, $this->angelleye_needs_shipping, 'tax');
                }
            }
        } else {
            $this->taxamt = 0;
        }
        if (WC()->cart->is_empty()) {
            $this->shippingamt = round($order->get_shipping_total(), $this->decimals);
        } else {
            $this->shippingamt = round(WC()->cart->shipping_total, $this->decimals);
        }
        if (isset($this->shippingamt) && $this->shippingamt > 0) {
            if (!empty($this->map_item_with_account)) {
                $packages = WC()->shipping()->get_packages();
                $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
                if (( class_exists('WC_Shipping_Per_Product_Init') || function_exists('woocommerce_per_product_shipping') ) && isset($chosen_shipping_methods[0]) && 'per_product' == $chosen_shipping_methods[0]) {
                    foreach ($packages as $package_key => $package) {
                        foreach ($package['contents'] as $key => $product) {
                            $rule = '';
                            if (!empty($product['variation_id'])) {
                                $rule = woocommerce_per_product_shipping_get_matching_rule($product['variation_id'], $package);
                            }
                            if (empty($rule)) {
                                $rule = woocommerce_per_product_shipping_get_matching_rule($product['product_id'], $package);
                            }
                            if (!empty($rule)) {
                                $item_shipping_cost = 0;
                                $item_shipping_cost += $rule->rule_item_cost * $product['quantity'];
                                $item_shipping_cost += $rule->rule_cost;
                                $this->map_item_with_account[$product['product_id']]['shipping_cost'] = AngellEYE_Gateway_Paypal::number_format($item_shipping_cost);
                                $this->divided_shipping_cost = $this->divided_shipping_cost + $item_shipping_cost;
                            }
                        }
                    }
                } elseif (!empty($packages) && !empty($chosen_shipping_methods) && count($packages) > 1) {
                    foreach ($packages as $package_key => $package) {
                        if (isset($chosen_shipping_methods[$package_key], $package['rates'][$chosen_shipping_methods[$package_key]])) {
                            $shipping_rate = $package['rates'][$chosen_shipping_methods[$package_key]];
                            foreach ($package['contents'] as $key => $value) {
                                $product_id = isset($value['product_id']) ? $value['product_id'] : false;
                            }
                            if ($product_id) {
                                if (isset($this->map_item_with_account[$product_id])) {
                                    $this->map_item_with_account[$product_id]['shipping_cost'] = AngellEYE_Gateway_Paypal::number_format($shipping_rate->cost);
                                    $this->divided_shipping_cost = $this->divided_shipping_cost + $shipping_rate->cost;
                                }
                            }
                        }
                    }
                } else {
                    foreach ($packages as $package_key => $package) {
                        if (isset($chosen_shipping_methods[$package_key], $package['rates'][$chosen_shipping_methods[$package_key]])) {
                            $shipping_rate = $package['rates'][$chosen_shipping_methods[$package_key]];
                            if (isset($shipping_rate->method_id) && 'flat_rate' === $shipping_rate->method_id) {
                                $wc_shipping_flat_rate = new WC_Shipping_Flat_Rate($shipping_rate->instance_id);
                                $has_costs = false;
                                $cost = $wc_shipping_flat_rate->get_option('cost');
                                if ('' !== $cost) {
                                    $has_costs = true;
                                    $this->not_divided_shipping_cost = $this->evaluate_cost(
                                            $cost, array(
                                        'qty' => $wc_shipping_flat_rate->get_package_item_qty($package),
                                        'cost' => $package['contents_cost'],
                                            )
                                    );
                                }
                                $shipping_classes = WC()->shipping()->get_shipping_classes();
                                if (!empty($shipping_classes)) {
                                    $found_shipping_classes = $this->angelleye_find_shipping_classes($package);
                                    foreach ($found_shipping_classes as $shipping_class => $products) {
                                        $highest_class_cost = 0;
                                        $shipping_class_term = get_term_by('slug', $shipping_class, 'product_shipping_class');
                                        $class_cost_string = $shipping_class_term && $shipping_class_term->term_id ? $wc_shipping_flat_rate->get_option('class_cost_' . $shipping_class_term->term_id, $wc_shipping_flat_rate->get_option('class_cost_' . $shipping_class, '')) : $wc_shipping_flat_rate->get_option('no_class_cost', '');
                                        if ('' === $class_cost_string) {
                                            continue;
                                        }
                                        $product_id = array_sum(wp_list_pluck($products, 'product_id'));
                                        $class_cost = $this->evaluate_cost(
                                                $class_cost_string, array(
                                            'qty' => array_sum(wp_list_pluck($products, 'quantity')),
                                            'cost' => array_sum(wp_list_pluck($products, 'line_total')),
                                                )
                                        );
                                        $highest_class_cost = $class_cost > $highest_class_cost ? $class_cost : $highest_class_cost;
                                        if ($product_id) {
                                            if (isset($this->map_item_with_account[$product_id])) {
                                                $this->map_item_with_account[$product_id]['shipping_cost'] = AngellEYE_Gateway_Paypal::number_format($highest_class_cost);
                                                $this->divided_shipping_cost = $this->divided_shipping_cost + $highest_class_cost;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $pending_shipping_amount = 0;
            if ($this->divided_shipping_cost != $this->shippingamt) {
                $pending_shipping_amount = AngellEYE_Gateway_Paypal::number_format($this->shippingamt - $this->divided_shipping_cost);
            }
            $this->shipping_array = $this->angelleye_get_extra_fee_array($pending_shipping_amount, $this->angelleye_needs_shipping, 'shipping');
        }
        if (WC()->cart->is_empty()) {
            $this->discount_amount = round($order->get_discount_total(), $this->decimals);
            if (isset($this->discount_amount) && $this->discount_amount > 0) {
                $this->discount_array = $this->angelleye_get_extra_fee_array($this->discount_amount, $this->angelleye_is_discountable, 'discount');
            }
        } else {
            $this->discount_amount = round(WC()->cart->get_cart_discount_total(), $this->decimals);
            if (isset($this->discount_amount) && $this->discount_amount > 0) {
                $this->discount_array = $this->angelleye_get_extra_fee_array($this->discount_amount, $this->angelleye_is_discountable, 'discount');
            }
        }
        $loop = 1;
        $default_item_total = 0;
        $default_final_total = 0;
        $default_shippingamt = 0;
        $default_taxamt = 0;
        $default_pal_id = '';
        $is_mismatch = false;
        $product_commission = 0;
        $sub_total_commission = 0;
        $tax_commission = 0;
        $shippingamt_commission = 0;
        if (!empty($order_id)) {
            $order = wc_get_order($order_id);
            $this->final_order_grand_total = $order->get_total();
            foreach ($order->get_items() as $cart_item_key => $cart_item) {
                $is_mismatch = false;
                $product = version_compare(WC_VERSION, '3.0', '<') ? $order->get_product_from_item($cart_item) : $cart_item->get_product();
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
                        $line_item = $this->angelleye_get_line_item_from_order($order, $cart_item);
                        $item_total = AngellEYE_Gateway_Paypal::number_format($item_total + ($line_item['amt'] * $line_item['qty']), $order);
                        if (!empty($this->discount_array[$product_id])) {
                            $item_total = $item_total - $this->discount_array[$product_id];
                        }
                        $shippingamt = isset($this->shipping_array[$product_id]) ? $this->shipping_array[$product_id] : '0.00';
                        $taxamt = isset($this->tax_array[$product_id]) ? $this->tax_array[$product_id] : '0.00';
                        $final_total = AngellEYE_Gateway_Paypal::number_format($item_total + $shippingamt + $taxamt, $order);
                        $is_commission_not_enabled = false;
                        $PaymentOrderItems = array();
                        if (isset($this->map_item_with_account[$product_id]['is_commission_enable']) && $this->map_item_with_account[$product_id]['is_commission_enable'] == true) {
                            $is_commission_not_enabled = true;
                            $this->is_commission_enable = true;
                            $product_commission = AngellEYE_Gateway_Paypal::number_format($item_total / 100 * $this->map_item_with_account[$product_id]['ec_site_owner_commission'], $order);
                            $default_final_total = $default_final_total + $product_commission;
                            $item_total = AngellEYE_Gateway_Paypal::number_format($item_total - $product_commission, $order);
                            $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $product_commission, $order);
                            $default_item_total = $default_item_total + $product_commission;
                            if ($this->global_ec_include_tax_shipping_in_commission == 'on') {
                                if ($taxamt > 0) {
                                    $tax_commission = AngellEYE_Gateway_Paypal::number_format($taxamt / 100 * $this->map_item_with_account[$product_id]['ec_site_owner_commission'], 2);
                                    $default_final_total = $default_final_total + $tax_commission;
                                    $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $tax_commission);
                                    $taxamt = AngellEYE_Gateway_Paypal::number_format($taxamt - $tax_commission);
                                    $default_item_total = $default_item_total + $tax_commission;
                                }
                                if ($shippingamt > 0) {
                                    $shippingamt_commission = AngellEYE_Gateway_Paypal::number_format($shippingamt / 100 * $this->map_item_with_account[$product_id]['ec_site_owner_commission'], 2);
                                    $default_final_total = $default_final_total + $shippingamt_commission;
                                    $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $shippingamt_commission);
                                    $shippingamt = AngellEYE_Gateway_Paypal::number_format($shippingamt - $shippingamt_commission);
                                    $default_item_total = $default_item_total + $shippingamt_commission;
                                }
                                $sub_total_commission = AngellEYE_Gateway_Paypal::number_format($product_commission + $tax_commission + $shippingamt_commission);
                            } else {
                                $sub_total_commission = $product_commission;
                            }
                            $Item = array(
                                'name' => $this->map_item_with_account[$product_id]['ec_site_owner_commission_label'],
                                'desc' => $line_item['name'],
                                'amt' => $sub_total_commission,
                                'number' => '',
                                'qty' => 1
                            );
                            $default_new_payments_line_item[] = $Item;
                            if ($this->always_trigger_commission_total_percentage > 0) {
                                if (!empty($this->discount_array[$product_id])) {
                                    $commision_item_total_raw = AngellEYE_Gateway_Paypal::number_format($item_total + $this->discount_array[$product_id]);
                                } else {
                                    $commision_item_total_raw = $item_total;
                                }
                                $atc_item_total = $commision_item_total_raw + $sub_total_commission;
                                $always_trigger_commission_item_total = AngellEYE_Gateway_Paypal::number_format($atc_item_total / 100 * $this->always_trigger_commission_total_percentage, 2);
                                $item_total = AngellEYE_Gateway_Paypal::number_format($item_total - $always_trigger_commission_item_total, 2);
                                $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $always_trigger_commission_item_total, 2);
                                $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $always_trigger_commission_item_total;
                            }
                            if ($item_total / $line_item['qty'] != AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty'], $order)) {
                                $is_mismatch = true;
                            }
                            if (!empty($this->discount_array[$product_id])) {
                                $Item = array(
                                    'name' => $line_item['name'],
                                    'desc' => $line_item['desc'],
                                    'amt' => AngellEYE_Gateway_Paypal::number_format(($item_total + $this->discount_array[$product_id] ) / $line_item['qty'], $order),
                                    'number' => $line_item['number'],
                                    'qty' => $line_item['qty']
                                );
                                array_push($PaymentOrderItems, $Item);
                                $Item = array(
                                    'name' => 'Discount',
                                    'desc' => 'Discount Amount',
                                    'amt' => isset($this->discount_array[$product_id]) ? '-' . AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id], $order) : '0.00',
                                    'number' => '',
                                    'qty' => 1
                                );
                                array_push($PaymentOrderItems, $Item);
                            } else {
                                $Item = array(
                                    'name' => $line_item['name'],
                                    'desc' => $line_item['desc'],
                                    'amt' => AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty'], $order),
                                    'number' => $line_item['number'],
                                    'qty' => $line_item['qty']
                                );
                                array_push($PaymentOrderItems, $Item);
                            }
                        } else {
                            if ($this->always_trigger_commission_total_percentage > 0) {
                                $product_commission = AngellEYE_Gateway_Paypal::number_format($item_total / 100 * $this->always_trigger_commission_total_percentage, 2);
                                $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $product_commission);
                                $item_total = AngellEYE_Gateway_Paypal::number_format($item_total - $product_commission);
                                $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $product_commission;
                                if ($taxamt > 0) {
                                    $tax_commission = AngellEYE_Gateway_Paypal::number_format($taxamt / 100 * $this->always_trigger_commission_total_percentage, 2);
                                    $taxamt = AngellEYE_Gateway_Paypal::number_format($taxamt - $tax_commission);
                                    $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $tax_commission);
                                    $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] + $tax_commission;
                                }
                                if ($shippingamt > 0) {
                                    $shippingamt_commission = AngellEYE_Gateway_Paypal::number_format($shippingamt / 100 * $this->always_trigger_commission_total_percentage, 2);
                                    $shippingamt = AngellEYE_Gateway_Paypal::number_format($shippingamt - $shippingamt_commission);
                                    $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $shippingamt_commission);
                                    $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] + $shippingamt_commission;
                                }
                            }
                            if ($item_total / $line_item['qty'] != AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty'], $order)) {
                                $is_mismatch = true;
                            }
                            if (!empty($this->discount_array[$product_id])) {
                                $Item = array(
                                    'name' => $line_item['name'],
                                    'desc' => $line_item['desc'],
                                    'amt' => AngellEYE_Gateway_Paypal::number_format(($item_total + $this->discount_array[$product_id] ) / $line_item['qty'], $order),
                                    'number' => $line_item['number'],
                                    'qty' => $line_item['qty']
                                );
                                array_push($PaymentOrderItems, $Item);
                                $Item = array(
                                    'name' => 'Discount',
                                    'desc' => 'Discount Amount',
                                    'amt' => isset($this->discount_array[$product_id]) ? '-' . AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id], $order) : '0.00',
                                    'number' => '',
                                    'qty' => 1
                                );
                                array_push($PaymentOrderItems, $Item);
                            } else {
                                $Item = array(
                                    'name' => $line_item['name'],
                                    'desc' => $line_item['desc'],
                                    'amt' => AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty'], $order),
                                    'number' => $line_item['number'],
                                    'qty' => $line_item['qty']
                                );
                                array_push($PaymentOrderItems, $Item);
                            }
                        }
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
                        if (!empty($this->final_payment_request_data[$sellerpaypalaccountid]['amt'])) {
                            $this->final_payment_request_data[$sellerpaypalaccountid]['amt'] = $this->final_payment_request_data[$sellerpaypalaccountid]['amt'] + $Payment['amt'];
                        } else {
                            $this->final_payment_request_data[$sellerpaypalaccountid] = $Payment;
                        }
                        if ($this->send_items && $is_mismatch == false) {
                            $Payment['order_items'] = $PaymentOrderItems;
                            $Payment['itemamt'] = AngellEYE_Gateway_Paypal::number_format($item_total, $order);
                            $Payment['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($shippingamt, $order);
                            $Payment['taxamt'] = AngellEYE_Gateway_Paypal::number_format($taxamt, $order);
                            if (empty($this->final_payment_request_data[$sellerpaypalaccountid]['order_items'])) {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['order_items'] = array();
                            }
                            array_push($this->final_payment_request_data[$sellerpaypalaccountid]['order_items'], $PaymentOrderItems);
                            if (!empty($this->final_payment_request_data[$sellerpaypalaccountid]['itemamt'])) {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['itemamt'] = $this->final_payment_request_data[$sellerpaypalaccountid]['itemamt'] + $Payment['itemamt'];
                            } else {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['itemamt'] = $Payment['itemamt'];
                            }
                            if (!empty($this->final_payment_request_data[$sellerpaypalaccountid]['shippingamt'])) {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['shippingamt'] = $this->final_payment_request_data[$sellerpaypalaccountid]['shippingamt'] + $Payment['shippingamt'];
                            } else {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['shippingamt'] = $Payment['shippingamt'];
                            }
                            if (!empty($this->final_payment_request_data[$sellerpaypalaccountid]['taxamt'])) {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['taxamt'] = $this->final_payment_request_data[$sellerpaypalaccountid]['taxamt'] + $Payment['taxamt'];
                            } else {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['taxamt'] = $Payment['taxamt'];
                            }
                        }
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
                        $item_total = AngellEYE_Gateway_Paypal::number_format($item_total + ($line_item['amt'] * $line_item['qty']), $order);
                        if (!empty($this->discount_array[$product_id])) {
                            $item_total = $item_total - $this->discount_array[$product_id];
                        }
                        $shippingamt = isset($this->shipping_array[$product_id]) ? $this->shipping_array[$product_id] : '0.00';
                        $taxamt = isset($this->tax_array[$product_id]) ? $this->tax_array[$product_id] : '0.00';
                        if ($this->always_trigger_commission_total_percentage > 0) {
                            $product_commission = AngellEYE_Gateway_Paypal::number_format($item_total / 100 * $this->always_trigger_commission_total_percentage, 2);
                            $item_total = AngellEYE_Gateway_Paypal::number_format($item_total - $product_commission);
                            $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $product_commission;
                            if ($taxamt > 0) {
                                $tax_commission = AngellEYE_Gateway_Paypal::number_format($taxamt / 100 * $this->always_trigger_commission_total_percentage, 2);
                                $taxamt = AngellEYE_Gateway_Paypal::number_format($taxamt - $tax_commission);
                                $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] + $tax_commission;
                            }
                            if ($shippingamt > 0) {
                                $shippingamt_commission = AngellEYE_Gateway_Paypal::number_format($shippingamt / 100 * $this->always_trigger_commission_total_percentage, 2);
                                $shippingamt = AngellEYE_Gateway_Paypal::number_format($shippingamt - $shippingamt_commission);
                                $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] + $shippingamt_commission;
                            }
                        }
                        if (!empty($this->discount_array[$product_id])) {
                            $Item = array(
                                'name' => $line_item['name'],
                                'desc' => $line_item['desc'],
                                'amt' => AngellEYE_Gateway_Paypal::number_format(($item_total + $this->discount_array[$product_id] ) / $line_item['qty']),
                                'number' => $line_item['number'],
                                'qty' => $line_item['qty']
                            );
                            array_push($default_new_payments_line_item, $Item);
                            $Item = array(
                                'name' => 'Discount',
                                'desc' => 'Discount Amount',
                                'amt' => isset($this->discount_array[$product_id]) ? '-' . AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id]) : '0.00',
                                'number' => '',
                                'qty' => 1
                            );
                            array_push($default_new_payments_line_item, $Item);
                        } else {
                            $Item = array(
                                'name' => $line_item['name'],
                                'desc' => $line_item['desc'],
                                'amt' => AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty']),
                                'number' => $line_item['number'],
                                'qty' => $line_item['qty']
                            );
                            array_push($default_new_payments_line_item, $Item);
                        }
                        $paymentrequestid_value = $cart_item_key . '-' . rand();
                        $default_shippingamt = $default_shippingamt + $shippingamt;
                        $default_taxamt = $default_taxamt + $taxamt;
                        $default_final_total = $default_final_total + AngellEYE_Gateway_Paypal::number_format($item_total + $shippingamt + $taxamt, $order);
                        $default_item_total = $default_item_total + $item_total;
                        $loop = $loop + 1;
                    }
                }
            }
        } elseif (isset(WC()->cart) && sizeof(WC()->cart->get_cart()) > 0) {
            $cart_amt_total = WC()->cart->get_totals();
            $this->final_order_grand_total = $cart_amt_total['total'];
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $item_total = 0;
                $final_total = 0;
                $is_mismatch = false;
                $product_commission = 0;
                $sub_total_commission = 0;
                $tax_commission = 0;
                $shippingamt_commission = 0;
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
                        $line_item = $this->angelleye_get_line_item_from_cart($product_id, $cart_item);
                        $item_total = AngellEYE_Gateway_Paypal::number_format($item_total + ($line_item['amt'] * $line_item['qty']));
                        if (!empty($this->discount_array[$product_id])) {
                            $item_total = $item_total - $this->discount_array[$product_id];
                        }
                        $shippingamt = isset($this->shipping_array[$product_id]) ? $this->shipping_array[$product_id] : '0.00';
                        $taxamt = isset($this->tax_array[$product_id]) ? $this->tax_array[$product_id] : '0.00';
                        $final_total = AngellEYE_Gateway_Paypal::number_format($item_total + $shippingamt + $taxamt);
                        $is_commission_not_enabled = false;
                        if (isset($this->map_item_with_account[$product_id]['is_commission_enable']) && $this->map_item_with_account[$product_id]['is_commission_enable'] == true) {
                            $is_commission_not_enabled = true;
                            $this->is_commission_enable = true;
                            $product_commission = AngellEYE_Gateway_Paypal::number_format($item_total / 100 * $this->map_item_with_account[$product_id]['ec_site_owner_commission'], 2);
                            $default_final_total = $default_final_total + $product_commission;
                            $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $product_commission);
                            $item_total = AngellEYE_Gateway_Paypal::number_format($item_total - $product_commission);
                            $default_item_total = $default_item_total + $product_commission;
                            if ($this->global_ec_include_tax_shipping_in_commission == 'on') {
                                if ($taxamt > 0) {
                                    $tax_commission = AngellEYE_Gateway_Paypal::number_format($taxamt / 100 * $this->map_item_with_account[$product_id]['ec_site_owner_commission'], 2);
                                    $default_final_total = $default_final_total + $tax_commission;
                                    $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $tax_commission);
                                    $taxamt = AngellEYE_Gateway_Paypal::number_format($taxamt - $tax_commission);
                                    $default_item_total = $default_item_total + $tax_commission;
                                }
                                if ($shippingamt > 0) {
                                    $shippingamt_commission = AngellEYE_Gateway_Paypal::number_format($shippingamt / 100 * $this->map_item_with_account[$product_id]['ec_site_owner_commission'], 2);
                                    $default_final_total = $default_final_total + $shippingamt_commission;
                                    $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $shippingamt_commission);
                                    $shippingamt = AngellEYE_Gateway_Paypal::number_format($shippingamt - $shippingamt_commission);
                                    $default_item_total = $default_item_total + $shippingamt_commission;
                                }
                                $sub_total_commission = AngellEYE_Gateway_Paypal::number_format($product_commission + $tax_commission + $shippingamt_commission);
                            } else {
                                $sub_total_commission = $product_commission;
                            }
                            $Item = array(
                                'name' => $this->map_item_with_account[$product_id]['ec_site_owner_commission_label'],
                                'desc' => $line_item['name'],
                                'amt' => $sub_total_commission,
                                'number' => '',
                                'qty' => 1
                            );
                            $default_new_payments_line_item[] = $Item;
                            if ($this->always_trigger_commission_total_percentage > 0) {
                                if (!empty($this->discount_array[$product_id])) {
                                    $commision_item_total_raw = AngellEYE_Gateway_Paypal::number_format($item_total + $this->discount_array[$product_id]);
                                } else {
                                    $commision_item_total_raw = $item_total;
                                }
                                $atc_item_total = $commision_item_total_raw + $sub_total_commission;
                                $always_trigger_commission_item_total = AngellEYE_Gateway_Paypal::number_format($atc_item_total / 100 * $this->always_trigger_commission_total_percentage, 2);
                                $item_total = AngellEYE_Gateway_Paypal::number_format($item_total - $always_trigger_commission_item_total, 2);
                                $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $always_trigger_commission_item_total, 2);
                                $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $always_trigger_commission_item_total;
                            }
                            if ($item_total / $line_item['qty'] != AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty'])) {
                                $is_mismatch = true;
                            }
                            if (!empty($this->discount_array[$product_id])) {
                                $Item = array(
                                    'name' => $line_item['name'],
                                    'desc' => $line_item['desc'],
                                    'amt' => AngellEYE_Gateway_Paypal::number_format(($item_total + $this->discount_array[$product_id] ) / $line_item['qty']),
                                    'number' => $line_item['number'],
                                    'qty' => $line_item['qty']
                                );
                                array_push($PaymentOrderItems, $Item);
                                $Item = array(
                                    'name' => 'Discount',
                                    'desc' => 'Discount Amount',
                                    'amt' => isset($this->discount_array[$product_id]) ? '-' . AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id]) : '0.00',
                                    'number' => '',
                                    'qty' => 1
                                );
                                array_push($PaymentOrderItems, $Item);
                            } else {
                                $Item = array(
                                    'name' => $line_item['name'],
                                    'desc' => $line_item['desc'],
                                    'amt' => AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty']),
                                    'number' => $line_item['number'],
                                    'qty' => $line_item['qty']
                                );
                                array_push($PaymentOrderItems, $Item);
                            }
                        } else {
                            if ($this->always_trigger_commission_total_percentage > 0) {
                                $product_commission = AngellEYE_Gateway_Paypal::number_format($item_total / 100 * $this->always_trigger_commission_total_percentage, 2);
                                $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $product_commission);
                                $item_total = AngellEYE_Gateway_Paypal::number_format($item_total - $product_commission);
                                $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $product_commission;
                                if ($taxamt > 0) {
                                    $tax_commission = AngellEYE_Gateway_Paypal::number_format($taxamt / 100 * $this->always_trigger_commission_total_percentage, 2);
                                    $taxamt = AngellEYE_Gateway_Paypal::number_format($taxamt - $tax_commission);
                                    $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $tax_commission);
                                    $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] + $tax_commission;
                                }
                                if ($shippingamt > 0) {
                                    $shippingamt_commission = AngellEYE_Gateway_Paypal::number_format($shippingamt / 100 * $this->always_trigger_commission_total_percentage, 2);
                                    $shippingamt = AngellEYE_Gateway_Paypal::number_format($shippingamt - $shippingamt_commission);
                                    $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $shippingamt_commission);
                                    $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] + $shippingamt_commission;
                                }
                            }
                            if (!empty($this->discount_array[$product_id])) {
                                $Item = array(
                                    'name' => $line_item['name'],
                                    'desc' => $line_item['desc'],
                                    'amt' => AngellEYE_Gateway_Paypal::number_format(($item_total + $this->discount_array[$product_id] ) / $line_item['qty']),
                                    'number' => $line_item['number'],
                                    'qty' => $line_item['qty']
                                );
                                array_push($PaymentOrderItems, $Item);
                                $Item = array(
                                    'name' => 'Discount',
                                    'desc' => 'Discount Amount',
                                    'amt' => isset($this->discount_array[$product_id]) ? '-' . AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id]) : '0.00',
                                    'number' => '',
                                    'qty' => 1
                                );
                                array_push($PaymentOrderItems, $Item);
                            } else {
                                $Item = array(
                                    'name' => $line_item['name'],
                                    'desc' => $line_item['desc'],
                                    'amt' => AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty']),
                                    'number' => $line_item['number'],
                                    'qty' => $line_item['qty']
                                );
                                array_push($PaymentOrderItems, $Item);
                            }
                        }
                        $this->final_grand_total = $this->final_grand_total + $final_total;
                        $Payment = array(
                            'amt' => $final_total,
                            'currencycode' => isset($old_payments[0]['currencycode']) ? $old_payments[0]['currencycode'] : '',
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
                        if (!empty($this->final_payment_request_data[$sellerpaypalaccountid]['amt'])) {
                            $this->final_payment_request_data[$sellerpaypalaccountid]['amt'] = $this->final_payment_request_data[$sellerpaypalaccountid]['amt'] + $Payment['amt'];
                        } else {
                            $this->final_payment_request_data[$sellerpaypalaccountid] = $Payment;
                        }
                        if ($this->send_items && $is_mismatch == false) {
                            $Payment['order_items'] = $PaymentOrderItems;
                            $Payment['itemamt'] = AngellEYE_Gateway_Paypal::number_format($item_total);
                            $Payment['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($shippingamt);
                            $Payment['taxamt'] = AngellEYE_Gateway_Paypal::number_format($taxamt);
                            if (empty($this->final_payment_request_data[$sellerpaypalaccountid]['order_items'])) {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['order_items'] = array();
                            }
                            array_push($this->final_payment_request_data[$sellerpaypalaccountid]['order_items'], $PaymentOrderItems);
                            if (!empty($this->final_payment_request_data[$sellerpaypalaccountid]['itemamt'])) {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['itemamt'] = $this->final_payment_request_data[$sellerpaypalaccountid]['itemamt'] + $Payment['itemamt'];
                            } else {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['itemamt'] = $Payment['itemamt'];
                            }
                            if (!empty($this->final_payment_request_data[$sellerpaypalaccountid]['shippingamt'])) {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['shippingamt'] = $this->final_payment_request_data[$sellerpaypalaccountid]['shippingamt'] + $Payment['shippingamt'];
                            } else {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['shippingamt'] = $Payment['shippingamt'];
                            }
                            if (!empty($this->final_payment_request_data[$sellerpaypalaccountid]['taxamt'])) {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['taxamt'] = $this->final_payment_request_data[$sellerpaypalaccountid]['taxamt'] + $Payment['taxamt'];
                            } else {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['taxamt'] = $Payment['taxamt'];
                            }
                        }
                        if ($final_total > 0) {
                            array_push($new_payments, $Payment);
                            $loop = $loop + 1;
                        } else {
                            unset($this->final_payment_request_data[$sellerpaypalaccountid]);
                        }
                    } else {
                        if (isset($multi_account_info['email'])) {
                            $sellerpaypalaccountid = $multi_account_info['email'];
                        } else {
                            $sellerpaypalaccountid = $this->angelleye_get_email_address($this->map_item_with_account[$product_id], $gateways);
                        }
                        $default_pal_id = $sellerpaypalaccountid;
                        $line_item = $this->angelleye_get_line_item_from_cart($product_id, $cart_item);
                        $item_total = AngellEYE_Gateway_Paypal::number_format($item_total + ($line_item['amt'] * $line_item['qty']));
                        if (!empty($this->discount_array[$product_id])) {
                            $item_total = $item_total - $this->discount_array[$product_id];
                        }
                        $shippingamt = isset($this->shipping_array[$product_id]) ? $this->shipping_array[$product_id] : '0.00';
                        $taxamt = isset($this->tax_array[$product_id]) ? $this->tax_array[$product_id] : '0.00';
                        if ($this->always_trigger_commission_total_percentage > 0) {
                            $product_commission = AngellEYE_Gateway_Paypal::number_format($item_total / 100 * $this->always_trigger_commission_total_percentage, 2);
                            $item_total = AngellEYE_Gateway_Paypal::number_format($item_total - $product_commission);
                            $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $product_commission;
                            if ($taxamt > 0) {
                                $tax_commission = AngellEYE_Gateway_Paypal::number_format($taxamt / 100 * $this->always_trigger_commission_total_percentage, 2);
                                $taxamt = AngellEYE_Gateway_Paypal::number_format($taxamt - $tax_commission);
                                $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] + $tax_commission;
                            }
                            if ($shippingamt > 0) {
                                $shippingamt_commission = AngellEYE_Gateway_Paypal::number_format($shippingamt / 100 * $this->always_trigger_commission_total_percentage, 2);
                                $shippingamt = AngellEYE_Gateway_Paypal::number_format($shippingamt - $shippingamt_commission);
                                $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] = $this->always_trigger_commission_accounts_line_items[$product_id]['commission_item_total'] + $shippingamt_commission;
                            }
                        }
                        if (!empty($this->discount_array[$product_id])) {
                            $Item = array(
                                'name' => $line_item['name'],
                                'desc' => $line_item['desc'],
                                'amt' => AngellEYE_Gateway_Paypal::number_format(($item_total + $this->discount_array[$product_id] ) / $line_item['qty']),
                                'number' => $line_item['number'],
                                'qty' => $line_item['qty']
                            );
                            array_push($default_new_payments_line_item, $Item);
                            $Item = array(
                                'name' => 'Discount',
                                'desc' => 'Discount Amount',
                                'amt' => isset($this->discount_array[$product_id]) ? '-' . AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id]) : '0.00',
                                'number' => '',
                                'qty' => 1
                            );
                            array_push($default_new_payments_line_item, $Item);
                        } else {
                            $Item = array(
                                'name' => $line_item['name'],
                                'desc' => $line_item['desc'],
                                'amt' => AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty']),
                                'number' => $line_item['number'],
                                'qty' => $line_item['qty']
                            );
                            array_push($default_new_payments_line_item, $Item);
                        }
                        $default_taxamt = $default_taxamt + $taxamt;
                        $default_final_total = $default_final_total + AngellEYE_Gateway_Paypal::number_format($item_total + $shippingamt + $taxamt);
                        $default_item_total = $default_item_total + $item_total;
                        $default_shippingamt = $default_shippingamt + $shippingamt;
                        $loop = $loop + 1;
                    }
                }
            }
        }
        WC()->cart->calculate_fees();
        foreach (WC()->cart->get_fees() as $cart_item_key => $fee_values) {
            $fee_item = array(
                'name' => html_entity_decode(wc_trim_string($fee_values->name ? $fee_values->name : __('Fee', 'paypal-for-woocommerce'), 127), ENT_NOQUOTES, 'UTF-8'),
                'desc' => '',
                'qty' => 1,
                'amt' => AngellEYE_Gateway_Paypal::number_format($fee_values->amount),
                'number' => ''
            );
            $default_new_payments_line_item[] = $fee_item;
            $default_item_total += $fee_values->amount;
            $default_final_total += $fee_values->amount;
        }
        if ($default_final_total > 0) {
            if (empty($default_pal_id)) {
                $map_item_with_account_array['multi_account_id'] = 'default';
                $default_pal_id = $this->angelleye_get_email_address($map_item_with_account_array, $gateways);
            }
            $this->final_grand_total = $this->final_grand_total + $default_final_total;
            $new_default_payment = array(
                'amt' => AngellEYE_Gateway_Paypal::number_format($default_final_total),
                'currencycode' => isset($old_payments[0]['currencycode']) ? $old_payments[0]['currencycode'] : '',
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
            if (!empty($this->final_payment_request_data[$default_pal_id]['amt'])) {
                $this->final_payment_request_data[$default_pal_id]['amt'] = $this->final_payment_request_data[$default_pal_id]['amt'] + $new_default_payment['amt'];
            } else {
                $this->final_payment_request_data[$default_pal_id] = $new_default_payment;
            }
            if ($this->send_items) {
                if (!empty($default_new_payments_line_item)) {
                    $new_default_payment['order_items'] = $default_new_payments_line_item;
                    $new_default_payment['itemamt'] = AngellEYE_Gateway_Paypal::number_format($default_item_total);
                    $new_default_payment['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($default_shippingamt);
                    $new_default_payment['taxamt'] = AngellEYE_Gateway_Paypal::number_format($default_taxamt);
                    if (empty($this->final_payment_request_data[$default_pal_id]['order_items'])) {
                        $this->final_payment_request_data[$default_pal_id]['order_items'] = array();
                    }
                    array_push($this->final_payment_request_data[$default_pal_id]['order_items'], $default_new_payments_line_item);
                    if (!empty($this->final_payment_request_data[$default_pal_id]['itemamt'])) {
                        $this->final_payment_request_data[$default_pal_id]['itemamt'] = $this->final_payment_request_data[$default_pal_id]['itemamt'] + $new_default_payment['itemamt'];
                    } else {
                        $this->final_payment_request_data[$default_pal_id]['itemamt'] = $new_default_payment['itemamt'];
                    }
                    if (!empty($this->final_payment_request_data[$default_pal_id]['shippingamt'])) {
                        $this->final_payment_request_data[$default_pal_id]['shippingamt'] = $this->final_payment_request_data[$default_pal_id]['shippingamt'] + $new_default_payment['shippingamt'];
                    } else {
                        $this->final_payment_request_data[$default_pal_id]['shippingamt'] = $new_default_payment['shippingamt'];
                    }
                    if (!empty($this->final_payment_request_data[$default_pal_id]['taxamt'])) {
                        $this->final_payment_request_data[$default_pal_id]['taxamt'] = $this->final_payment_request_data[$default_pal_id]['taxamt'] + $new_default_payment['taxamt'];
                    } else {
                        $this->final_payment_request_data[$default_pal_id]['taxamt'] = $new_default_payment['taxamt'];
                    }
                }
            }
            array_push($new_payments, $new_default_payment);
        }

        if (!empty($this->final_payment_request_data)) {
            $this->final_paypal_request = array();
            $index = 0;
            foreach ($this->final_payment_request_data as $email_id => $vendor_payment) {
                if (!empty($vendor_payment['order_items'])) {
                    $this->final_paypal_request[$index] = $vendor_payment;
                    unset($this->final_paypal_request[$index]['order_items']);
                    $first_inner_index = 0;
                    foreach ($vendor_payment['order_items'] as $first_key => $first_order_item) {
                        foreach ($first_order_item as $second_key => $second_order_item) {
                            if (empty($this->final_paypal_request[$index]['order_items'])) {
                                $this->final_paypal_request[$index]['order_items'] = array();
                            }
                            $this->final_paypal_request[$index]['order_items'][$first_inner_index] = $second_order_item;
                            $first_inner_index = $first_inner_index + 1;
                        }
                    }
                } else {
                    $this->final_paypal_request[$index] = $vendor_payment;
                }
                $index = $index + 1;
            }
            $new_payments = $this->final_paypal_request;
        }
        if ($this->always_trigger_commission_total_percentage > 0 && !empty($this->always_trigger_commission_accounts)) {
            foreach ($this->always_trigger_commission_accounts as $key => $value) {
                $index = $index + 1;
                $this->final_paypal_request[$index] = $this->angelleye_add_commission_payment_data($old_payments, $gateways, $key, $value);
            }
            if (count($this->map_item_with_account) === 1 && $this->final_grand_total != $this->final_order_grand_total) {
                $Difference = round($this->final_order_grand_total - $this->final_grand_total, $this->decimals);
                if (abs($Difference) > 0.000001 && 0.0 !== (float) $Difference) {
                    $index = $index + 1;
                    $this->final_paypal_request[$index] = $this->angelleye_after_commition_remain_part_to_web_admin($old_payments, $gateways, $Difference);
                }
            }
            $new_payments = $this->final_paypal_request;
        }
        if ($this->final_grand_total != $this->final_order_grand_total) {
            $Difference = round($this->final_order_grand_total - $this->final_grand_total, $this->decimals);
            if (abs($Difference) > 0.000001 && 0.0 !== (float) $Difference) {
                if (isset($new_payments[0]['amt']) && $new_payments[0]['amt'] > 1) {
                    $new_payments[0]['amt'] = $new_payments[0]['amt'] + $Difference;
                    if (isset($new_payments[0]['taxamt']) && $new_payments[0]['taxamt'] > 1) {
                        $new_payments[0]['taxamt'] = $new_payments[0]['taxamt'] + $Difference;
                    } elseif (isset($new_payments[0]['shippingamt']) && $new_payments[0]['shippingamt'] > 1) {
                        $new_payments[0]['shippingamt'] = $new_payments[0]['shippingamt'] + $Difference;
                    } else {
                        $item_names = array();
                        if (!empty($new_payments[0]['order_items'])) {
                            $first_line_item = $new_payments[0]['order_items'];
                        }
                        if (!empty($first_line_item)) {
                            unset($new_payments[0]['order_items']);
                            $new_payments[0]['order_items'] = array();
                            $new_payments[0]['itemamt'] = $new_payments[0]['amt'];
                            foreach ($first_line_item as $key => $value) {
                                $item_names[] = $value['name'] . ' x ' . $value['qty'];
                            }
                            $item_details = implode(', ', $item_names);
                            $item_details = html_entity_decode(wc_trim_string($item_details ? wp_strip_all_tags($item_details) : __('Item', 'paypal-for-woocommerce-multi-account-management'), 127), ENT_NOQUOTES, 'UTF-8');
                            $new_payments[0]['order_items'][0] = array(
                                'name' => $item_details,
                                'desc' => '',
                                'amt' => $new_payments[0]['amt'],
                                'qty' => 1
                            );
                        }
                        unset($new_payments[0]['shippingamt']);
                        unset($new_payments[0]['taxamt']);
                    }
                }
            }
        }
        if (!empty($new_payments)) {
            $request['Payments'] = $new_payments;
            if (!empty($order_id) && !empty($this->map_item_with_account) && $this->angelleye_is_multi_account_used($this->map_item_with_account)) {
                $order->update_meta_data('_angelleye_multi_account_ec_parallel_data_map', $this->map_item_with_account);
                $this->final_payment_summary = [];
                $identifierFinder = function ($sellerId, $mapAccountList) {
                    foreach ($mapAccountList as $item) {
                        if ($item['email'] == $sellerId) {
                            return $item['multi_account_identifier'];
                        }
                    }
                    return 'default';
                };
                foreach ($this->final_payment_request_data as $paymentId => $paymentData) {
                    $this->final_payment_summary[$paymentId] = ['amount' => $paymentData['amt'], 'paid_to' => $paymentData['sellerpaypalaccountid'],
                        'mac_identifier' => $identifierFinder($paymentData['sellerpaypalaccountid'], $this->map_item_with_account)
                    ];
                }
                $order->update_meta_data('_angelleye_multi_account_ec_payment_summary', $this->final_payment_summary);
                $order->save_meta_data();
            }
        } else {
            $request['Payments'] = $old_payments;
        }
        return $request;
    }

    public function angelleye_is_multi_account_used($map_item_with_account) {
        if (!empty($map_item_with_account)) {
            if (isset($map_item_with_account['always'])) {
                return true;
            }
            foreach ($map_item_with_account as $key => $item_with_account) {
                if (isset($item_with_account['multi_account_id']) || (isset($item_with_account['multi_account_id']) && $item_with_account['multi_account_id'] != 'default')) {
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

    public function angelleye_get_line_item_from_cart($product_id, $values) {
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
        $product = version_compare(WC_VERSION, '3.0', '<') ? $order->get_product_from_item($values) : $values->get_product();
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

        $desc = '';
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
        if ($divided == 0) {
            $partition = $amount;
        } else {
            $partition = AngellEYE_Gateway_Paypal::number_format($amount / $divided);
        }
        for ($i = 1; $i <= $divided; $i++) {
            $partition_array[$i] = $partition;
            $total = $total + $partition;
        }
        $Difference = round($amount - $total, $this->decimals);
        if (abs($Difference) > 0.000001 && 0.0 !== (float) $Difference) {
            $partition_array[$divided] = $partition_array[$divided] + $Difference;
        }
        if (!empty($this->map_item_with_account)) {
            $loop = 1;
            foreach ($this->map_item_with_account as $product_id => $item_with_account) {
                switch ($type) {
                    case "tax":
                        if (!empty($item_with_account['is_taxable']) && $item_with_account['is_taxable'] === true && !empty($item_with_account['needs_shipping']) && $item_with_account['needs_shipping'] === true) {
                            $partition_array[$product_id] = round($partition_array[$loop] + $item_with_account['tax'], $this->decimals);
                            unset($partition_array[$loop]);
                            $loop = $loop + 1;
                        } elseif (!empty($item_with_account['is_taxable']) && $item_with_account['is_taxable'] === true) {
                            $partition_array[$product_id] = round($item_with_account['tax'], $this->decimals);
                        }
                        break;
                    case "shipping":
                        if (!empty($item_with_account['needs_shipping']) && $item_with_account['needs_shipping'] === true) {
                            if (isset($item_with_account['shipping_cost'])) {
                                $partition_array[$product_id] = round($partition_array[$loop] + $item_with_account['shipping_cost'], $this->decimals);
                                unset($partition_array[$loop]);
                                $loop = $loop + 1;
                            } else {
                                $partition_array[$product_id] = isset($item_with_account['shipping_cost']) ? $item_with_account['shipping_cost'] : $partition_array[$loop];
                            }
                        }
                        break;
                    case "discount":
                        if (!empty($item_with_account['is_discountable']) && $item_with_account['is_discountable'] === true) {
                            $partition_array[$product_id] = isset($item_with_account['discount']) ? $item_with_account['discount'] : $partition_array[$loop];
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

    public function update_total_amount_received_by_accounts($order, $amount, $account_email = null, $merchant_id = null) {
        global $wpdb;
        $main_identifier = empty($account_email) ? $merchant_id : $account_email;
        if (empty($main_identifier)) {
            $main_identifier = 'other';
        }
        $is_sandbox = $order->get_meta( 'is_sandbox', true);
        if ($is_sandbox == 1) {
            $main_identifier = 'sandbox-' . $main_identifier;
        }
        $total_row = $wpdb->get_row($wpdb->prepare("select * from {$wpdb->posts} where post_type = 'multi_ac_totals' and post_title = %s", $main_identifier));
        if ($total_row) {
            wp_update_post(['post_content' => floatval($total_row->post_content) + $amount, 'ID' => $total_row->ID,
                'post_status' => 'publish']);
        } else {
            wp_insert_post([
                'post_title' => $main_identifier,
                'post_type' => 'multi_ac_totals',
                'post_content' => $amount,
                'post_status' => 'publish'
            ]);
        }
    }

    public function own_angelleye_express_checkout_order_data($paypal_response, $order_id) {
        $order = wc_get_order($order_id);
        $order->add_order_note(json_encode($paypal_response));
        if (!$this->own_angelleye_is_payment_load_balancer_not_used(true, $order_id)) {
            $angelleye_payment_load_balancer_account = $order->get_meta('_angelleye_payment_load_balancer_account', true);
            $always_transaction_map = 0;
            if (!isset($angelleye_payment_load_balancer_account['multi_account_identifier'])) {
                $angelleye_payment_load_balancer_account['multi_account_identifier'] = $angelleye_payment_load_balancer_account['email'];
            }
            if ($angelleye_payment_load_balancer_account['multi_account_identifier'] == 'default') {
                $angelleye_payment_load_balancer_account['multi_account_identifier'] = $paypal_response['PAYMENTINFO_' . $always_transaction_map . '_SELLERPAYPALACCOUNTID'];
                $angelleye_payment_load_balancer_account['email'] = $paypal_response['PAYMENTINFO_' . $always_transaction_map . '_SECUREMERCHANTACCOUNTID'];
            }
            $this->final_payment_summary = [];
            $this->final_payment_summary[$paypal_response['PAYMENTINFO_' . $always_transaction_map . '_SECUREMERCHANTACCOUNTID']] = ['amount' => $paypal_response['PAYMENTINFO_' . $always_transaction_map . '_AMT'], 'paid_to' => $paypal_response['PAYMENTINFO_' . $always_transaction_map . '_SECUREMERCHANTACCOUNTID'],
                'mac_identifier' => $paypal_response['PAYMENTINFO_' . $always_transaction_map . '_SELLERPAYPALACCOUNTID']
            ];
            $order->update_meta_data('_angelleye_multi_account_ec_payment_summary', $this->final_payment_summary);
            $order->save_meta_data();
            $this->update_total_amount_received_by_accounts($order, $this->final_payment_summary[$paypal_response['PAYMENTINFO_' . $always_transaction_map . '_SECUREMERCHANTACCOUNTID']]['amount'], $this->final_payment_summary[$paypal_response['PAYMENTINFO_' . $always_transaction_map . '_SECUREMERCHANTACCOUNTID']]['mac_identifier'], $this->final_payment_summary[$paypal_response['PAYMENTINFO_' . $always_transaction_map . '_SECUREMERCHANTACCOUNTID']]['paid_to']);
            return true;
        }
        $ec_parallel_data_map = $order->get_meta('_angelleye_multi_account_ec_parallel_data_map', true);
        if (empty($ec_parallel_data_map)) {
            return false;
        }
        for ($payment = 0; $payment <= 10; $payment++) {
            if (!empty($paypal_response['PAYMENTINFO_' . $payment . '_TRANSACTIONID'])) {
                $order->add_order_note(sprintf(__('PayPal Express payment Transaction ID: %s', 'paypal-for-woocommerce-multi-account-management'), isset($paypal_response['PAYMENTINFO_' . $payment . '_TRANSACTIONID']) ? $paypal_response['PAYMENTINFO_' . $payment . '_TRANSACTIONID'] : ''));
            } elseif (!empty($paypal_response['PAYMENTINFO_' . $payment . '_ERRORCODE'])) {
                $long_message = !empty($paypal_response['PAYMENTINFO_' . $payment . '_LONGMESSAGE']) ? $paypal_response['PAYMENTINFO_' . $payment . '_LONGMESSAGE'] : '';
                if (!empty($long_message)) {
                    $order->add_order_note($long_message);
                }
            } else {
                break;
            }
        }
        $unique_transaction_data = array();
        $total_account = count($ec_parallel_data_map);
        foreach ($ec_parallel_data_map as $key => $ec_parallel_data) {
            if ($key === 'always') {
                foreach ($ec_parallel_data as $inner_key => $always_ec_parallel_data) {
                    for ($always_transaction_map = 0; $always_transaction_map <= ($total_account + count($ec_parallel_data)); $always_transaction_map++) {
                        if (!empty($paypal_response['PAYMENTINFO_' . $always_transaction_map . '_PAYMENTREQUESTID'])) {
                            $PAYMENTREQUESTID_array = $paypal_response['PAYMENTINFO_' . $always_transaction_map . '_PAYMENTREQUESTID'];
                            $request_order_item_id = explode('-', $PAYMENTREQUESTID_array);
                            if (!empty($request_order_item_id[0]) && $always_ec_parallel_data['multi_account_id'] == $request_order_item_id[1]) {
                                if (!empty($paypal_response['PAYMENTINFO_' . $always_transaction_map . '_TRANSACTIONID'])) {
                                    $this->update_total_amount_received_by_accounts($order, $paypal_response['PAYMENTINFO_' . $always_transaction_map . '_AMT'], $ec_parallel_data_map[$key][$always_ec_parallel_data['multi_account_id']]['multi_account_identifier'], $ec_parallel_data_map[$key][$always_ec_parallel_data['multi_account_id']]['sellerpaypalaccountid']);
                                    $ec_parallel_data_map[$key][$always_ec_parallel_data['multi_account_id']]['transaction_id'] = $paypal_response['PAYMENTINFO_' . $always_transaction_map . '_TRANSACTIONID'];
                                    $unique_transaction_data[] = $paypal_response['PAYMENTINFO_' . $always_transaction_map . '_TRANSACTIONID'];
                                }
                            }
                        }
                    }
                }
            } else {
                for ($transaction_map = 0; $transaction_map <= $total_account; $transaction_map++) {
                    if (!empty($paypal_response['PAYMENTINFO_' . $transaction_map . '_PAYMENTREQUESTID'])) {
                        $PAYMENTREQUESTID_array = $paypal_response['PAYMENTINFO_' . $transaction_map . '_PAYMENTREQUESTID'];
                        $request_order_item_id = explode('-', $PAYMENTREQUESTID_array);
                        if (!empty($request_order_item_id[0]) && $ec_parallel_data['order_item_id'] == $request_order_item_id[0]) {
                            if (!empty($paypal_response['PAYMENTINFO_' . $transaction_map . '_TRANSACTIONID'])) {
                                $this->update_total_amount_received_by_accounts($order, $paypal_response['PAYMENTINFO_' . $transaction_map . '_AMT'], $ec_parallel_data_map[$ec_parallel_data['product_id']]['multi_account_identifier'], $ec_parallel_data_map[$ec_parallel_data['product_id']]['sellerpaypalaccountid']);
                                $ec_parallel_data_map[$ec_parallel_data['product_id']]['transaction_id'] = $paypal_response['PAYMENTINFO_' . $transaction_map . '_TRANSACTIONID'];
                                $unique_transaction_data[] = $paypal_response['PAYMENTINFO_' . $transaction_map . '_TRANSACTIONID'];
                                wc_update_order_item_meta($ec_parallel_data['order_item_id'], '_transaction_id', $paypal_response['PAYMENTINFO_' . $transaction_map . '_TRANSACTIONID']);
                            } elseif (!empty($paypal_response['PAYMENTINFO_' . $payment . '_ERRORCODE'])) {
                                wc_update_order_item_meta($ec_parallel_data['order_item_id'], 'Payment Status', __('Not Paid', 'paypal-for-woocommerce-multi-account-management'));
                            }
                        }
                    }
                }
            }
        }
        $total_paypal_transaction = $total_account + 1;
        if (!empty($ec_parallel_data_map)) {
            for ($transaction = 0; $transaction <= count($ec_parallel_data); $transaction++) {
                if (isset($paypal_response['PAYMENTINFO_' . $transaction . '_TRANSACTIONID']) && !empty($paypal_response['PAYMENTINFO_' . $transaction . '_TRANSACTIONID'])) {
                    if (!in_array($paypal_response['PAYMENTINFO_' . $transaction . '_TRANSACTIONID'], $unique_transaction_data)) {
                        $this->update_total_amount_received_by_accounts($order, $paypal_response['PAYMENTINFO_' . $transaction . '_AMT'], '', $paypal_response['PAYMENTINFO_' . $transaction . '_SELLERPAYPALACCOUNTID']);
                        $ec_parallel_data_map['primary']['transaction_id'] = $paypal_response['PAYMENTINFO_' . $transaction . '_TRANSACTIONID'];
                        $ec_parallel_data_map['primary']['multi_account_id'] = 'default';
                    }
                }
            }
        }
        if (!empty($ec_parallel_data_map)) {
            $order->update_meta_data('_angelleye_multi_account_ec_parallel_data_map', $ec_parallel_data_map);
            $order->save_meta_data();
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
        global $theorder;
        if ( $theorder instanceof WC_Order ) {
            if ($feature === 'refunds' && $bool === true && $current->id === 'paypal_express') {
                $order = $theorder;
                if ($order) {
                    $angelleye_multi_account_ppcp_parallel_data_map = $order->get_meta('_angelleye_multi_account_ec_parallel_data_map', true);
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

    public function own_angelleye_is_express_checkout_parallel_payment_not_used($bool, $order_id) {
        $order = wc_get_order($order_id);
        $angelleye_multi_account_ec_parallel_data_map = $order->get_meta('_angelleye_multi_account_ec_parallel_data_map', true);
        if (!empty($angelleye_multi_account_ec_parallel_data_map)) {
            return false;
        }
        return $bool;
    }

    public function own_angelleye_is_express_checkout_parallel_payment_handle($bool, $order_id, $gateway) {
        try {
            $order = wc_get_order($order_id);
            $processed_transaction_id = array();
            $refund_error_message_pre = __('We can not refund this order as the Express Checkout API keys are missing! Please go to multi-account setup and add API key to process the refund', 'paypal-for-woocommerce-multi-account-management');
            $refund_error_message_after = array();
            $angelleye_multi_account_ec_parallel_data_map = $order->get_meta('_angelleye_multi_account_ec_parallel_data_map', true);
            if (!empty($angelleye_multi_account_ec_parallel_data_map)) {
                foreach ($angelleye_multi_account_ec_parallel_data_map as $key => $value) {
                    if (!empty($value['product_id']) && isset($value['is_api_set']) && apply_filters('angelleye_pfwma_is_api_set', $value['is_api_set'], $value) === false) {
                        $product = wc_get_product($value['product_id']);
                        $refund_error_message_after[] = $product->get_title();
                    } elseif ($key === 'always') {
                        foreach ($value as $inner_key => $inner_value) {
                            if (!empty($inner_value['multi_account_id']) && isset($inner_value['is_api_set']) && apply_filters('angelleye_pfwma_is_api_set', $inner_value['is_api_set'], $inner_value) === false) {
                                $refund_error_message_after[] = __('Always trigger account API keys are missing! Please go to multi-account setup and add API key to process the refund', 'paypal-for-woocommerce-multi-account-management');
                            }
                        }
                    }
                }
            }
            if (!empty($refund_error_message_after)) {
                $refund_error = $refund_error_message_pre . ' ' . implode(", ", $refund_error_message_after);
                return new WP_Error('invalid_refund', $refund_error);
            }
            if (!empty($angelleye_multi_account_ec_parallel_data_map)) {
                foreach ($angelleye_multi_account_ec_parallel_data_map as $key => $value) {
                    if ($key === 'always') {
                        foreach ($value as $inner_key => $inner_value) {
                            $this->angelleye_express_checkout_load_paypal($inner_value, $gateway, $order_id);
                            $processed_transaction_id[] = $inner_value['transaction_id'];
                            if (!empty($this->paypal_response['REFUNDTRANSACTIONID'])) {
                                $angelleye_multi_account_ec_parallel_data_map[$key][$inner_key]['REFUNDTRANSACTIONID'] = $this->paypal_response['REFUNDTRANSACTIONID'];
                                $angelleye_multi_account_ec_parallel_data_map[$key][$inner_key]['GROSSREFUNDAMT'] = $this->paypal_response['GROSSREFUNDAMT'];
                            }
                        }
                    } elseif (!in_array($value['transaction_id'], $processed_transaction_id)) {
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
                $order->update_meta_data('_angelleye_multi_account_ec_parallel_data_map', $angelleye_multi_account_ec_parallel_data_map);
                $order->update_meta_data('_multi_account_refund_amount', $this->final_refund_amt);
                $order->save_meta_data();
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
            $order->update_meta_data('Refund Transaction ID', $this->paypal_response['REFUNDTRANSACTIONID']);
            $order->save_meta_data();
        }
    }

    public function own_woocommerce_order_item_add_action_buttons($order) {
        $angelleye_multi_account_ec_parallel_data_map = $order->get_meta('_angelleye_multi_account_ec_parallel_data_map', true);
        if (!empty($angelleye_multi_account_ec_parallel_data_map)) {
            echo sprintf('<br><span class="description"><span class="woocommerce-help-tip" data-tip="%s"></span>%s</span>', MULTI_ACCOUNT_REFUND_NOTICE, MULTI_ACCOUNT_REFUND_NOTICE);
        }
    }

    public function own_woocommerce_order_fully_refunded($order_id, $refund_id) {
        if (!empty($order_id)) {
            $order = wc_get_order($order_id);
            $refund = wc_get_order($refund_id);
            if ($order->has_status(wc_get_is_paid_statuses())) {
                if ($order->get_total() == $refund->get_amount()) {
                    do_action('woocommerce_order_fully_refunded', $order_id, $refund_id);
                    $parent_status = apply_filters('woocommerce_order_fully_refunded_status', 'refunded', $order_id, $refund_id);
                    if ($parent_status) {
                        $order->update_status($parent_status);
                    }
                }
            }
        }
    }

    public function own_woocommerce_create_refund($refund, $args) {
        $order_id = $refund->get_parent_id();
        if (!empty($order_id)) {
            $order = wc_get_order($order_id);
            $payment_method = version_compare(WC_VERSION, '3.0', '<') ? $order->payment_method : $order->get_payment_method();
            $angelleye_multi_account_ec_parallel_data_map = $order->get_meta('_angelleye_multi_account_ec_parallel_data_map', true);
            if (!empty($angelleye_multi_account_ec_parallel_data_map) && $payment_method == 'paypal_express') {
                $refund->set_amount($order->get_total());
                $args['amount'] = $order->get_total();
                unset($args['line_items']);
            }
            remove_action('woocommerce_order_partially_refunded', array('WC_Emails', 'send_transactional_email'));
        }
    }

    public function own_angelleye_multi_account_need_shipping($bool, $order_id = '', $current_product_id = '') {
        $is_required = 0;
        $is_not_required = 0;
        if (!empty($order_id)) {
            $order = wc_get_order($order_id);
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
                $_no_shipping_required = get_post_meta($product_id, '_no_shipping_required', true);
                if ($_no_shipping_required == 'yes') {
                    $is_not_required = $is_not_required + 1;
                } elseif ($product->needs_shipping()) {
                    $is_required = $is_required + 1;
                } else {
                    $is_not_required = $is_not_required + 1;
                }
            }
        } else {
            if (isset(WC()->cart) && sizeof(WC()->cart->get_cart()) > 0) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                    $product = wc_get_product($product_id);
                    $_no_shipping_required = get_post_meta($product_id, '_no_shipping_required', true);
                    if ($_no_shipping_required == 'yes') {
                        $is_not_required = $is_not_required + 1;
                    } elseif ($product->needs_shipping()) {
                        $is_required = $is_required + 1;
                    } else {
                        $is_not_required = $is_not_required + 1;
                    }
                }
            }
        }

        if (!empty($current_product_id)) {
            $_no_shipping_required = get_post_meta($current_product_id, '_no_shipping_required', true);
            if ($_no_shipping_required == 'yes') {
                if ($is_required > 0) {
                    return false;
                }
            }
        }
        return $bool;
    }

    public function angelleye_get_account_for_ec_payment_load_balancer($gateways, $gateway_setting, $order_id, $request) {
        if (!isset($gateways->testmode)) {
            return;
        }
        if(!empty($order_id)) {
            $order = wc_get_order($order_id);
        }
        $found_account = false;
        $found_email = '';
        if ($gateways->testmode == true) {
            $option_key = 'angelleye_multi_ec_payment_load_balancer_sandbox';
            $session_key = 'angelleye_sandbox_payment_load_balancer_ec_email';
            $session_key_account = 'angelleye_sandbox_payment_load_balancer_ec_account';
        } else {
            $option_key = 'angelleye_multi_ec_payment_load_balancer';
            $session_key = 'angelleye_payment_load_balancer_ec_email';
            $session_key_account = 'angelleye_payment_load_balancer_ec_account';
        }
        $found_email = WC()->session->get($session_key);
        if (empty($found_email)) {
            $found_email = '';
            $express_checkout_accounts = get_option($option_key);
            if (!empty($express_checkout_accounts)) {
                foreach ($express_checkout_accounts as $key => $account) {
                    if (empty($account['is_used'])) {
                        if ($key != 'default' && false === get_post_status($key)) {
                            unset($express_checkout_accounts[$key]);
                        } else {
                            $found_email = $account['email'];
                            WC()->session->set($session_key, $account['email']);
                            $account['is_used'] = 'yes';
                            $express_checkout_accounts[$key] = $account;
                            WC()->session->set($session_key_account, $account);
                            update_option($option_key, $express_checkout_accounts);
                            $found_account = true;
                            break;
                        }
                    }
                }
                if ($found_account == false) {
                    foreach ($express_checkout_accounts as $key => $account) {
                        $account['is_used'] = '';
                        $express_checkout_accounts[$key] = $account;
                    }
                    foreach ($express_checkout_accounts as $key => $account) {
                        if ($key != 'default' && false === get_post_status($key)) {
                            unset($express_checkout_accounts[$key]);
                        } else {
                            $found_email = $account['email'];
                            WC()->session->set($session_key, $account['email']);
                            $account['is_used'] = 'yes';
                            $express_checkout_accounts[$key] = $account;
                            WC()->session->set($session_key_account, $account);
                            update_option($option_key, $express_checkout_accounts);
                            $found_account = true;
                            break;
                        }
                    }
                }
            }
        }

        if (!empty($request)) {
            if ($found_email != 'default') {
                $request['Payments'][0]['sellerpaypalaccountid'] = $found_email;
                if ($order) {
                    $angelleye_payment_load_balancer_account = WC()->session->get($session_key_account);
                    $order->update_meta_data('_angelleye_payment_load_balancer_account', $angelleye_payment_load_balancer_account);
                    $order->save_meta_data();
                }
            } else {
                if ($order) {
                    $angelleye_payment_load_balancer_account = ['multi_account_id' => 'default', 'is_used' => 'yes', 'is_api_set' => 1, 'email' => '',
                        'multi_account_identifier' => 'default'];
                    $order->update_meta_data('_angelleye_payment_load_balancer_account', $angelleye_payment_load_balancer_account);
                    $order->save_meta_data();
                }
            }
        }
        return $request;
    }

    public function own_angelleye_is_payment_load_balancer_not_used($bool, $order_id) {
        $order = wc_get_order($order_id);
        $angelleye_payment_load_balancer_account = $order->get_meta('_angelleye_payment_load_balancer_account', true);
        if (!empty($angelleye_payment_load_balancer_account)) {
            return false;
        }
        return $bool;
    }

    public function own_angelleye_is_express_checkout_payment_load_balancer_handle($bool, $order_id, $gateway) {
        try {
            $order = wc_get_order($order_id);
            $processed_transaction_id = array();
            $refund_error_message_pre = __('We can not refund this order as the Express Checkout API keys are missing! Please go to multi-account setup and add API key to process the refund', 'paypal-for-woocommerce-multi-account-management');
            $angelleye_payment_load_balancer_account = $order->get_meta('_angelleye_payment_load_balancer_account', true);
            if (!empty($angelleye_payment_load_balancer_account)) {
                if (!empty($angelleye_payment_load_balancer_account['is_api_set']) && apply_filters('angelleye_pfwma_is_api_set', $angelleye_payment_load_balancer_account['is_api_set'], $angelleye_payment_load_balancer_account) === true) {
                    $_transaction_id = $order->get_transaction_id();
                    $angelleye_payment_load_balancer_account['transaction_id'] = $_transaction_id;
                    $this->angelleye_express_checkout_load_paypal($angelleye_payment_load_balancer_account, $gateway, $order_id);
                    return true;
                } else {
                    return new WP_Error('invalid_refund', $refund_error_message_pre);
                }
            }
            return $bool;
        } catch (Exception $ex) {
            
        }
    }

    public function evaluate_cost($sum, $args = array()) {
        // Add warning for subclasses.
        if (!is_array($args) || !array_key_exists('qty', $args) || !array_key_exists('cost', $args)) {
            wc_doing_it_wrong(__FUNCTION__, '$args must contain `cost` and `qty` keys.', '4.0.1');
        }

        include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';

        // Allow 3rd parties to process shipping cost arguments.
        $args = apply_filters('woocommerce_evaluate_shipping_cost_args', $args, $sum, $this);
        $locale = localeconv();
        $decimals = array(wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',');
        $this->fee_cost = $args['cost'];

        // Expand shortcodes.
        add_shortcode('fee', array($this, 'fee'));

        $sum = do_shortcode(
                str_replace(
                        array(
                            '[qty]',
                            '[cost]',
                        ), array(
            $args['qty'],
            $args['cost'],
                        ), $sum
                )
        );

        remove_shortcode('fee', array($this, 'fee'));

        // Remove whitespace from string.
        $sum = preg_replace('/\s+/', '', $sum);

        // Remove locale from string.
        $sum = str_replace($decimals, '.', $sum);

        // Trim invalid start/end characters.
        $sum = rtrim(ltrim($sum, "\t\n\r\0\x0B+*/"), "\t\n\r\0\x0B+-*/");

        // Do the math.
        return $sum ? WC_Eval_Math::evaluate($sum) : 0;
    }

    public function angelleye_find_shipping_classes($package) {
        $found_shipping_classes = array();

        foreach ($package['contents'] as $item_id => $values) {
            if ($values['data']->needs_shipping()) {
                $found_class = $values['data']->get_shipping_class();

                if (!isset($found_shipping_classes[$found_class])) {
                    $found_shipping_classes[$found_class] = array();
                }

                $found_shipping_classes[$found_class][$item_id] = $values;
            }
        }

        return $found_shipping_classes;
    }

    public function angelleye_add_commission_payment_data($old_payments, $gateways, $account_id, $item_data) {
        $microprocessing_array = get_post_meta($account_id);
        if ($gateways->testmode == true) {
            if (isset($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0])) {
                $email = $microprocessing_array['woocommerce_paypal_express_sandbox_email'][0];
            } elseif (isset($microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0])) {
                $email = $microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0];
            } else {
                $email = $this->angelleye_get_email_address_for_multi($account_id, $microprocessing_array, $gateways);
            }
            if ($this->angelleye_is_multi_account_api_set($microprocessing_array, $gateways)) {
                $is_api_set = true;
            } else {
                $is_api_set = false;
            }
        } else {
            if (isset($microprocessing_array['woocommerce_paypal_express_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_email'][0])) {
                $email = $microprocessing_array['woocommerce_paypal_express_email'][0];
            } elseif (isset($microprocessing_array['woocommerce_paypal_express_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_merchant_id'][0])) {
                $email = $microprocessing_array['woocommerce_paypal_express_merchant_id'][0];
            } else {
                $email = $this->angelleye_get_email_address_for_multi($account_id, $microprocessing_array, $gateways);
            }
            if ($this->angelleye_is_multi_account_api_set($microprocessing_array, $gateways)) {
                $is_api_set = true;
            } else {
                $is_api_set = false;
            }
        }

        $this->map_item_with_account['always'][$account_id] = array();
        $this->map_item_with_account['always'][$account_id]['multi_account_id'] = $account_id;
        $this->map_item_with_account['always'][$account_id]['email'] = $email;
        $this->map_item_with_account['always'][$account_id]['is_api_set'] = $is_api_set;
        $this->map_item_with_account['always'][$account_id]['sellerpaypalaccountid'] = $email;

        $cart_item_key = 'always-' . $account_id;
        $this->final_order_grand_total;
        $commission_amt = AngellEYE_Gateway_Paypal::number_format($this->final_order_grand_total / 100 * $item_data['commission_amount_percentage'], 2);
        $paymentrequestid_value = $cart_item_key . '-' . rand();
        $Payment = array(
            'amt' => AngellEYE_Gateway_Paypal::number_format($commission_amt),
            'currencycode' => isset($old_payments[0]['currencycode']) ? $old_payments[0]['currencycode'] : '',
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
            'sellerpaypalaccountid' => $email,
            'paymentrequestid' => !empty($paymentrequestid_value) ? $paymentrequestid_value : uniqid(rand(), true),
            'itemamt' => AngellEYE_Gateway_Paypal::number_format($commission_amt),
            'shippingamt' => '0.00',
            'taxamt' => '0.00',
        );
        $PaymentOrderItems = array();
        $Item = array(
            'name' => $item_data['commission_item_label'],
            'desc' => '',
            'amt' => AngellEYE_Gateway_Paypal::number_format($commission_amt),
            'qty' => '1',
        );
        array_push($PaymentOrderItems, $Item);
        $Payment['order_items'] = $PaymentOrderItems;
        $this->final_grand_total = $this->final_grand_total + $commission_amt;
        return $Payment;
    }

    public function angelleye_after_commition_remain_part_to_web_admin($old_payments, $gateways, $amount) {
        $map_item_with_account_array['multi_account_id'] = 'default';
        $email = $this->angelleye_get_email_address($map_item_with_account_array, $gateways);
        $is_api_set = true;
        $this->map_item_with_account['always'][$account_id] = array();
        $this->map_item_with_account['always'][$account_id]['multi_account_id'] = 'default';
        $this->map_item_with_account['always'][$account_id]['email'] = $email;
        $this->map_item_with_account['always'][$account_id]['is_api_set'] = $is_api_set;
        $this->map_item_with_account['always'][$account_id]['sellerpaypalaccountid'] = $email;
        $cart_item_key = 'efault';
        $commission_amt = AngellEYE_Gateway_Paypal::number_format($amount, 2);
        $paymentrequestid_value = $cart_item_key . '-' . rand();
        $Payment = array(
            'amt' => AngellEYE_Gateway_Paypal::number_format($commission_amt),
            'currencycode' => isset($old_payments[0]['currencycode']) ? $old_payments[0]['currencycode'] : '',
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
            'sellerpaypalaccountid' => $email,
            'paymentrequestid' => !empty($paymentrequestid_value) ? $paymentrequestid_value : uniqid(rand(), true),
            'shippingamt' => '0.00',
            'taxamt' => '0.00',
        );

        $this->final_grand_total = $this->final_grand_total + $commission_amt;
        return $Payment;
    }

    public function angelleye_is_account_ready_to_paid($bool, $email) {
        $gateway = angelleye_wc_gateway('paypal_express');
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


        if (!class_exists('Angelleye_PayPal_WC')) {
            require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
        }
        $paypal = new Angelleye_PayPal_WC($PayPalConfig);
        $SECFields = array(
            'token' => '',
            'returnurl' => 'https://www.paypal.com/checkoutnow/error',
            'cancelurl' => 'https://www.paypal.com/checkoutnow/error',
        );
        $Payments = array();
        $Payment = array(
            'amt' => '1.00',
            'currencycode' => 'USD',
            'invnum' => time() . 'testing',
            'paymentaction' => 'Sale',
            'paymentrequestid' => time() . '-id',
            'sellerpaypalaccountid' => $email
        );
        array_push($Payments, $Payment);
        $PayPalRequest = array(
            'SECFields' => $SECFields,
            'Payments' => $Payments
        );
        $paypal_response = $paypal->SetExpressCheckout($PayPalRequest);
        if (isset($paypal_response['ACK']) && 'Success' === $paypal_response['ACK']) {
            return true;
        }
        return false;
    }

    public function angelleye_multi_account_dokan_refund_approve($refund, $args, $vendor_refund) {
        $parent_order_id = '';
        $order = wc_get_order($refund->get_order_id());
        if (!$order instanceof \WC_Order) {
            return;
        }
        if ('paypal_express' !== $order->get_payment_method()) {
            return;
        }
        $gateway_controller = WC_Payment_Gateways::instance();
        $all_gateways = $gateway_controller->payment_gateways();
        $payment_method = $order->get_payment_method();
        $gateway = isset($all_gateways[$payment_method]) ? $all_gateways[$payment_method] : false;
        if (!$gateway) {
            throw new Exception(__('The payment gateway for this order does not exist.', 'woocommerce'));
        }
        if (!$gateway->supports('refunds')) {
            throw new Exception(__('The payment gateway for this order does not support automatic refunds.', 'woocommerce'));
        }
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        if (dokan_is_sub_order($order_id)) {
            $parent_order_id = $order->get_parent_id() ? $order->get_parent_id() : $order->get_id();
        }
        $processed_transaction_id = array();
        $refund_error_message_pre = __('We can not refund this order as the Express Checkout API keys are missing! Please go to multi-account setup and add API key to process the refund', 'paypal-for-woocommerce-multi-account-management');
        $refund_error_message_after = array();
        if (!empty($parent_order_id)) {
            $parent_order = wc_get_order($parent_order_id);
            if($parent_order) {
                $angelleye_multi_account_ec_parallel_data_map = $parent_order->get_meta('_angelleye_multi_account_ec_parallel_data_map', true);
            }
        } else {
            $angelleye_multi_account_ec_parallel_data_map = $order->get_meta('_angelleye_multi_account_ec_parallel_data_map', true);
        }
        $order_item_array = $refund->get_item_qtys();
        if (!empty($order_item_array)) {
            foreach ($order_item_array as $order_item_id_key => $order_item_id_value) {
                if (!empty($angelleye_multi_account_ec_parallel_data_map)) {
                    foreach ($angelleye_multi_account_ec_parallel_data_map as $key => $value) {
                        $product_id = get_metadata('order_item', $order_item_id_key, '_product_id', true);
                        if (isset($value['product_id']) && $product_id == $value['product_id']) {
                            if (!empty($value['product_id']) && isset($value['is_api_set']) && apply_filters('angelleye_pfwma_is_api_set', $value['is_api_set'], $value) === false) {
                                $product = wc_get_product($value['product_id']);
                                $refund_error_message_after[] = $product->get_title();
                            } elseif ($key === 'always') {
                                foreach ($value as $inner_key => $inner_value) {
                                    if (!empty($inner_value['multi_account_id']) && isset($inner_value['is_api_set']) && apply_filters('angelleye_pfwma_is_api_set', $inner_value['is_api_set'], $inner_value) === false) {
                                        $refund_error_message_after[] = __('Always trigger account API keys are missing! Please go to multi-account setup and add API key to process the refund', 'paypal-for-woocommerce-multi-account-management');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!empty($refund_error_message_after)) {
            $refund_error = $refund_error_message_pre . ' ' . implode(", ", $refund_error_message_after);
            return new WP_Error('invalid_refund', $refund_error);
        }
        $order_item_array = $refund->get_item_qtys();
        if (!empty($order_item_array)) {
            foreach ($order_item_array as $order_item_id_key => $order_item_id_value) {
                if (!empty($angelleye_multi_account_ec_parallel_data_map)) {
                    foreach ($angelleye_multi_account_ec_parallel_data_map as $key => $value) {
                        $product_id = get_metadata('order_item', $order_item_id_key, '_product_id', true);
                        if (isset($value['product_id']) && $product_id == $value['product_id']) {
                            if ($key === 'always') {
                                foreach ($value as $inner_key => $inner_value) {
                                    $this->angelleye_express_checkout_load_paypal($inner_value, $gateway, $order_id);
                                    $processed_transaction_id[] = $inner_value['transaction_id'];
                                    if (!empty($this->paypal_response['REFUNDTRANSACTIONID'])) {
                                        $angelleye_multi_account_ec_parallel_data_map[$key][$inner_key]['REFUNDTRANSACTIONID'] = $this->paypal_response['REFUNDTRANSACTIONID'];
                                        $angelleye_multi_account_ec_parallel_data_map[$key][$inner_key]['GROSSREFUNDAMT'] = $this->paypal_response['GROSSREFUNDAMT'];
                                    }
                                }
                            } elseif (!in_array($value['transaction_id'], $processed_transaction_id)) {
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
                    }
                    $order->update_meta_data('_angelleye_multi_account_ec_parallel_data_map', $angelleye_multi_account_ec_parallel_data_map);
                    $order->update_meta_data('_multi_account_refund_amount', $this->final_refund_amt);
                    $order->save_meta_data();
                    return true;
                }
            }
        }
    }
}
