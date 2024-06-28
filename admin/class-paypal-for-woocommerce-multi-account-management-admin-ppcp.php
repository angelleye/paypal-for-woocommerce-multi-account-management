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
class Paypal_For_Woocommerce_Multi_Account_Management_Admin_PPCP {

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
    public $global_ppcp_site_owner_commission;
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
    public $settings;
    public $is_sandbox;
    public $invoice_prefix;
    public $client_id;
    public $secret_id;
    public $merchant_id;
    public $global_ec_site_owner_commission;
    public $sandbox_client_id;
    public $sandbox_secret_id;
    public $live_client_id;
    public $live_secret_id;
    public $sandbox_merchant_id;
    public $live_merchant_id;
    public $discount_amount;

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
        $this->always_trigger_commission_total_percentage = 0;
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
        if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
            include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
        }
        $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
        $this->is_sandbox = 'yes' === $this->settings->get('testmode', 'no');
        $this->invoice_prefix = $this->settings->get('invoice_prefix', 'WC-PPCP');
        $this->sandbox_client_id = $this->settings->get('sandbox_client_id', '');
        $this->sandbox_secret_id = $this->settings->get('sandbox_api_secret', '');
        $this->live_client_id = $this->settings->get('api_client_id', '');
        $this->live_secret_id = $this->settings->get('api_secret', '');
        $this->sandbox_merchant_id = $this->settings->get('sandbox_merchant_id', '');
        $this->live_merchant_id = $this->settings->get('live_merchant_id', '');
        if ($this->is_sandbox) {
            $this->client_id = $this->sandbox_client_id;
            $this->secret_id = $this->sandbox_secret_id;
            $this->merchant_id = $this->sandbox_merchant_id;
        } else {
            $this->client_id = $this->live_client_id;
            $this->secret_id = $this->live_secret_id;
            $this->merchant_id = $this->live_merchant_id;
        }
    }

    public function angelleye_get_account_for_ppcp_parallel_payments($request = null, $action = null, $order_id = null) {
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
                    'key' => 'woocommerce_angelleye_ppcp_enable',
                    'value' => 'on',
                    'compare' => '='
                ),
                array(
                    'key' => 'woocommerce_angelleye_ppcp_testmode',
                    'value' => ($this->is_sandbox) ? 'on' : '',
                    'compare' => '='
                ),
                array(
                    'key' => 'woocommerce_priority',
                    'compare' => 'EXISTS'
                )
            )
        );
        array_push($args['meta_query'], array(
            'key' => ($this->is_sandbox) ? 'woocommerce_angelleye_ppcp_multi_account_on_board_status_sandbox' : 'woocommerce_angelleye_ppcp_multi_account_on_board_status_live',
            'value' => 'yes',
            'compare' => '='
        ));
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
                    if (isset($microprocessing_array['woocommerce_angelleye_ppcp_always_trigger'][0]) && 'on' === $microprocessing_array['woocommerce_angelleye_ppcp_always_trigger'][0]) {
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
                                $billing_country = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_country() : WC()->customer->get_billing_country();
                                $shipping_country = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_country() : WC()->customer->get_shipping_country();
                                if (!empty($billing_country) && $billing_country == $buyer_countries_value) {
                                    $passed_rules['buyer_countries'] = true;
                                    break;
                                } elseif (!empty($shipping_country) && $shipping_country == $buyer_countries_value) {
                                    $passed_rules['buyer_countries'] = true;
                                    break;
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
                                $billing_state = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_state() : WC()->customer->get_billing_state();
                                $shipping_state = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_state() : WC()->customer->get_shipping_state();
                                if (!empty($billing_state) && $billing_state == $buyer_states_value) {
                                    $passed_rules['buyer_states'] = true;
                                    break;
                                } elseif (!empty($shipping_state) && $shipping_state == $buyer_states_value) {
                                    $passed_rules['buyer_states'] = true;
                                    break;
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
                                $billing_postcode = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_postcode() : WC()->customer->get_billing_postcode();
                                $shipping_postcode = version_compare(WC_VERSION, '3.0', '<') ? WC()->customer->get_postcode() : WC()->customer->get_shipping_postcode();
                                if (!empty($billing_postcode) && $billing_postcode == $postcode_value) {
                                    $passed_rules['postcode'] = true;
                                    break;
                                } elseif (!empty($shipping_postcode) && $shipping_postcode == $postcode_value) {
                                    $passed_rules['postcode'] = true;
                                    break;
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
                                if (!isset($this->map_item_with_account[$product_id]['is_taxable'])) {
                                    $this->map_item_with_account[$product_id]['is_taxable'] = true;
                                    $this->map_item_with_account[$product_id]['tax'] = $line_item['total_tax'];
                                    $this->angelleye_is_taxable = $this->angelleye_is_taxable + 1;
                                } elseif ($this->map_item_with_account[$product_id]['is_taxable'] != true) {
                                    $this->map_item_with_account[$product_id]['is_taxable'] = true;
                                    $this->map_item_with_account[$product_id]['tax'] = $line_item['total_tax'];
                                    $this->angelleye_is_taxable = $this->angelleye_is_taxable + 1;
                                }
                            } else {
                                $this->map_item_with_account[$product_id]['is_taxable'] = false;
                            }
                            if ($product->needs_shipping() && apply_filters('angelleye_multi_account_need_shipping', true, $order_id, $product_id)) {
                                if (!isset($this->map_item_with_account[$product_id]['needs_shipping'])) {
                                    $this->angelleye_needs_shipping = $this->angelleye_needs_shipping + 1;
                                    $this->map_item_with_account[$product_id]['needs_shipping'] = true;
                                } elseif ($this->map_item_with_account[$product_id]['needs_shipping'] != true) {
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
                            if (!empty($product_ids)) {
                                if (!array_intersect((array) $product_id, $product_ids)) {
                                    $cart_loop_not_pass = $cart_loop_not_pass + 1;
                                    continue;
                                }
                            }
                            $this->map_item_with_account[$product_id]['multi_account_id'] = $value->ID;
                            if (isset($microprocessing_array['ppcp_site_owner_commission'][0]) && !empty($microprocessing_array['ppcp_site_owner_commission'][0]) && $microprocessing_array['ppcp_site_owner_commission'][0] > 0) {
                                $this->map_item_with_account[$product_id]['is_commission_enable'] = true;
                                $this->is_commission_enable = true;
                                $this->map_item_with_account[$product_id]['ppcp_site_owner_commission_label'] = !empty($microprocessing_array['ppcp_site_owner_commission_label'][0]) ? $microprocessing_array['ppcp_site_owner_commission_label'][0] : __('Commission', 'paypal-for-woocommerce-multi-account-management');
                                $this->map_item_with_account[$product_id]['ppcp_site_owner_commission'] = $microprocessing_array['ppcp_site_owner_commission'][0];
                            } elseif ($this->global_ec_site_owner_commission > 0) {
                                $this->map_item_with_account[$product_id]['is_commission_enable'] = true;
                                $this->is_commission_enable = true;
                                $this->map_item_with_account[$product_id]['ppcp_site_owner_commission_label'] = !empty($this->global_ec_site_owner_commission_label) ? $this->global_ec_site_owner_commission_label : __('Commission', 'paypal-for-woocommerce-multi-account-management');
                                $this->map_item_with_account[$product_id]['ppcp_site_owner_commission'] = $this->global_ec_site_owner_commission;
                            } else {
                                $this->map_item_with_account[$product_id]['is_commission_enable'] = false;
                            }
                            if ($this->is_sandbox == true) {
                                if (isset($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_merchant_id'][0])) {
                                    $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_sandbox_merchant_id'][0];
                                } elseif (isset($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0])) {
                                    $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0];
                                } else {
                                    $this->map_item_with_account[$product_id]['merchant_id'] = $this->angelleye_get_merchant_id_for_multi($value->ID, $microprocessing_array);
                                }
                                if ($this->angelleye_is_multi_account_api_set($microprocessing_array)) {
                                    $this->map_item_with_account[$product_id]['is_api_set'] = true;
                                } else {
                                    $this->map_item_with_account[$product_id]['is_api_set'] = false;
                                }
                            } else {
                                if (isset($microprocessing_array['woocommerce_angelleye_ppcp_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_merchant_id'][0])) {
                                    $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_merchant_id'][0];
                                } elseif (isset($microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0])) {
                                    $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0];
                                } else {
                                    $this->map_item_with_account[$product_id]['merchant_id'] = $this->angelleye_get_merchant_id_for_multi($value->ID, $microprocessing_array);
                                }
                                if ($this->angelleye_is_multi_account_api_set($microprocessing_array)) {
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
                                    if (!isset($this->map_item_with_account[$product_id]['is_taxable'])) {
                                        $this->map_item_with_account[$product_id]['is_taxable'] = true;
                                        $this->angelleye_is_taxable = $this->angelleye_is_taxable + 1;
                                    } elseif ($this->map_item_with_account[$product_id]['is_taxable'] != true) {
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
                                    if (!isset($this->map_item_with_account[$product_id]['needs_shipping'])) {
                                        $this->angelleye_needs_shipping = $this->angelleye_needs_shipping + 1;
                                        $this->map_item_with_account[$product_id]['needs_shipping'] = true;
                                    } elseif ($this->map_item_with_account[$product_id]['needs_shipping'] != true) {
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
                                if (!empty($product_ids)) {
                                    if (!array_intersect((array) $product_id, $product_ids)) {
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
                                if (isset($microprocessing_array['ppcp_site_owner_commission'][0]) && !empty($microprocessing_array['ppcp_site_owner_commission'][0]) && $microprocessing_array['ppcp_site_owner_commission'][0] > 0) {
                                    $this->map_item_with_account[$product_id]['is_commission_enable'] = true;
                                    $this->is_commission_enable = true;
                                    $this->map_item_with_account[$product_id]['ppcp_site_owner_commission_label'] = !empty($microprocessing_array['ppcp_site_owner_commission_label'][0]) ? $microprocessing_array['ppcp_site_owner_commission_label'][0] : __('Commission', 'paypal-for-woocommerce-multi-account-management');
                                    $this->map_item_with_account[$product_id]['ppcp_site_owner_commission'] = $microprocessing_array['ppcp_site_owner_commission'][0];
                                } elseif ($this->global_ec_site_owner_commission > 0) {
                                    $this->map_item_with_account[$product_id]['is_commission_enable'] = true;
                                    $this->is_commission_enable = true;
                                    $this->map_item_with_account[$product_id]['ppcp_site_owner_commission_label'] = !empty($this->global_ec_site_owner_commission_label) ? $this->global_ec_site_owner_commission_label : __('Commission', 'paypal-for-woocommerce-multi-account-management');
                                    $this->map_item_with_account[$product_id]['ppcp_site_owner_commission'] = $this->global_ec_site_owner_commission;
                                } else {
                                    $this->map_item_with_account[$product_id]['is_commission_enable'] = false;
                                }
                                if ($this->is_sandbox == true) {
                                    if (isset($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_merchant_id'][0])) {
                                        $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_sandbox_merchant_id'][0];
                                    } elseif (isset($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0])) {
                                        $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0];
                                    } else {
                                        $this->map_item_with_account[$product_id]['merchant_id'] = $this->angelleye_get_merchant_id_for_multi($value->ID, $microprocessing_array);
                                    }
                                    if ($this->angelleye_is_multi_account_api_set($microprocessing_array)) {
                                        $this->map_item_with_account[$product_id]['is_api_set'] = true;
                                    } else {
                                        $this->map_item_with_account[$product_id]['is_api_set'] = false;
                                    }
                                } else {
                                    if (isset($microprocessing_array['woocommerce_angelleye_ppcp_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_merchant_id'][0])) {
                                        $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_merchant_id'][0];
                                    } elseif (isset($microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0])) {
                                        $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0];
                                    } else {
                                        $this->map_item_with_account[$product_id]['merchant_id'] = $this->angelleye_get_merchant_id_for_multi($value->ID, $microprocessing_array);
                                    }
                                    if ($this->angelleye_is_multi_account_api_set($microprocessing_array)) {
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
                return $this->angelleye_modified_ppcp_parallel_parameter($request, $action, $order_id);
            }
        }
        return $request;
    }

    public function angelleye_ppcp_request_multi_account($request = null, $action = null, $order_id = null) {
        $angelleye_payment_load_balancer = get_option('angelleye_payment_load_balancer', '');
        if ($angelleye_payment_load_balancer != '') {
            if ($action != 'update_order') {
                return $this->angelleye_get_account_for_ppcp_payment_load_balancer($request, $action, $order_id);
            }
        } else {
            return $this->angelleye_get_account_for_ppcp_parallel_payments($request, $action, $order_id);
        }
        return $request;
    }

    public function angelleye_unset_multi_account_dataset($gateways) {
        try {
            if (isset($gateways) || isset($this->is_sandbox)) {
                if ($this->is_sandbox == true) {
                    $session_key_account = 'angelleye_sandbox_payment_load_balancer_ppcp_account';
                } else {
                    $session_key_account = 'angelleye_payment_load_balancer_ppcp_account';
                }
                $angelleye_payment_load_balancer_account = WC()->session->get($session_key_account);
                if (!empty($angelleye_payment_load_balancer_account) && isset($angelleye_payment_load_balancer_account['multi_account_id']) && $angelleye_payment_load_balancer_account['multi_account_id'] !== 'default') {
                    update_post_meta($angelleye_payment_load_balancer_account['multi_account_id'], 'woocommerce_angelleye_ppcp_enable', '');
                }
            }
            delete_transient('angelleye_multi_ppcp_payment_load_balancer_synce_sandbox');
            delete_transient('angelleye_multi_ppcp_payment_load_balancer_synce');
            WC()->session->set('angelleye_sandbox_payment_load_balancer_ppcp_email', '');
            WC()->session->__unset('angelleye_sandbox_payment_load_balancer_ppcp_email');
            WC()->session->set('angelleye_payment_load_balancer_ppcp_email', '');
            WC()->session->__unset('angelleye_payment_load_balancer_ppcp_email');
            WC()->session->set('angelleye_sandbox_payment_load_balancer_ppcp_account', '');
            WC()->session->__unset('angelleye_sandbox_payment_load_balancer_ppcp_account');
            WC()->session->set('angelleye_payment_load_balancer_ppcp_account', '');
            WC()->session->__unset('angelleye_payment_load_balancer_ppcp_account');
        } catch (Exception $ex) {
            
        }
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

    public function angelleye_modified_ppcp_parallel_parameter($request, $action, $order_id) {
        $order = wc_get_order($order_id);
        $this->send_items = true;
        $this->map_item_with_account = apply_filters('angelleye_ppcp_parallel_parameter', $this->map_item_with_account);
        $new_payments = array();
        $this->final_payment_request_data = array();
        $default_new_payments_line_item = array();
        if (!empty($request['body']['purchase_units']['0'])) {
            $old_purchase_units = $request['body']['purchase_units']['0'];
            unset($request['body']['purchase_units']['0']);
        } else {
            $old_purchase_units = array();
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
        if (is_object($order)) {
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
        if (is_object($order)) {
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
        $default_discount = 0;
        $default_pal_id = '';
        $is_mismatch = false;
        $product_commission = 0;
        $sub_total_commission = 0;
        $tax_commission = 0;
        $default_discount = 0;
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
                $discount_amt = 0;
                if (array_key_exists($product_id, $this->map_item_with_account)) {
                    $multi_account_info = $this->map_item_with_account[$product_id];
                    if ($multi_account_info['multi_account_id'] != 'default') {
                        if (isset($multi_account_info['merchant_id'])) {
                            $sellerpaypalaccountid = $multi_account_info['merchant_id'];
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
                            $product_commission = AngellEYE_Gateway_Paypal::number_format($item_total / 100 * $this->map_item_with_account[$product_id]['ppcp_site_owner_commission'], $order);
                            $default_final_total = $default_final_total + $product_commission;
                            $item_total = AngellEYE_Gateway_Paypal::number_format($item_total - $product_commission, $order);
                            $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $product_commission, $order);
                            $default_item_total = $default_item_total + $product_commission;
                            if ($this->global_ec_include_tax_shipping_in_commission == 'on') {
                                if ($taxamt > 0) {
                                    $tax_commission = AngellEYE_Gateway_Paypal::number_format($taxamt / 100 * $this->map_item_with_account[$product_id]['ppcp_site_owner_commission'], 2);
                                    $default_final_total = $default_final_total + $tax_commission;
                                    $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $tax_commission);
                                    $taxamt = AngellEYE_Gateway_Paypal::number_format($taxamt - $tax_commission);
                                    $default_item_total = $default_item_total + $tax_commission;
                                }
                                if ($shippingamt > 0) {
                                    $shippingamt_commission = AngellEYE_Gateway_Paypal::number_format($shippingamt / 100 * $this->map_item_with_account[$product_id]['ppcp_site_owner_commission'], 2);
                                    $default_final_total = $default_final_total + $shippingamt_commission;
                                    $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $shippingamt_commission);
                                    $shippingamt = AngellEYE_Gateway_Paypal::number_format($shippingamt - $shippingamt_commission);
                                    $default_item_total = $default_item_total + $shippingamt_commission;
                                }
                                $sub_total_commission = AngellEYE_Gateway_Paypal::number_format($product_commission + $tax_commission + $shippingamt_commission, $order);
                            } else {
                                $sub_total_commission = AngellEYE_Gateway_Paypal::number_format($product_commission, $order);
                            }
                            $Item = array(
                                'name' => $this->map_item_with_account[$product_id]['ppcp_site_owner_commission_label'],
                                'desc' => $line_item['name'],
                                'amt' => AngellEYE_Gateway_Paypal::number_format($sub_total_commission,$order),
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
                                $item_total = $item_total + $this->discount_array[$product_id];
                                array_push($PaymentOrderItems, $Item);
                                $discount_amt = isset($this->discount_array[$product_id]) ? AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id], $order) : '0.00';
                            } else {
                                $Item = array(
                                    'name' => $line_item['name'],
                                    'desc' => $line_item['desc'],
                                    'amt' => AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty'], $order),
                                    'number' => $line_item['number'],
                                    'qty' => $line_item['qty']
                                );
                                array_push($PaymentOrderItems, $Item);
                                $discount_amt = 0;
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
                                $item_total = $item_total + $this->discount_array[$product_id];
                                array_push($PaymentOrderItems, $Item);
                                $discount_amt = isset($this->discount_array[$product_id]) ? AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id], $order) : '0.00';
                            } else {
                                $Item = array(
                                    'name' => $line_item['name'],
                                    'desc' => $line_item['desc'],
                                    'amt' => AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty'], $order),
                                    'number' => $line_item['number'],
                                    'qty' => $line_item['qty']
                                );
                                array_push($PaymentOrderItems, $Item);
                                $discount_amt = 0;
                            }
                        }
                        $custom_param = array();
                        if (isset($old_purchase_units['custom_id'])) {
                            $custom_param = json_decode($old_purchase_units['custom_id'], true);
                            $custom_param['order_item_id'] = $cart_item_key;
                            $custom_param = json_encode($custom_param);
                        } else {
                            $custom_param['order_item_id'] = $cart_item_key;
                            $custom_param = json_encode($custom_param);
                        }
                        $this->final_grand_total = $this->final_grand_total + $final_total;
                        $Payment = array(
                            'amt' => AngellEYE_Gateway_Paypal::number_format($final_total,$order),
                            'currencycode' => isset($old_purchase_units['amount']['currency_code']) ? $old_purchase_units['amount']['currency_code'] : '',
                            'custom_id' => $custom_param,
                            'invoice_id' => isset($old_purchase_units['invoice_id']) ? $old_purchase_units['invoice_id'] . '-' . $cart_item_key : '',
                            'reference_id' => $sellerpaypalaccountid,
                            'soft_descriptor' => isset($old_purchase_units['soft_descriptor']) ? $old_purchase_units['soft_descriptor'] : ''
                        );
                        if (is_email($sellerpaypalaccountid)) {
                            $Payment['payee'] = array('email_address' => $sellerpaypalaccountid);
                        } else {
                            $Payment['payee'] = array('merchant_id' => $sellerpaypalaccountid);
                        }
                        if (!empty($old_purchase_units['shipping']['address']['address_line_1']) && !empty($old_purchase_units['shipping']['address']['country_code'])) {
                            $Payment['shipping'] = array(
                                'name' => array(
                                    'full_name' => isset($old_purchase_units['shipping']['name']['full_name']) ? $old_purchase_units['shipping']['name']['full_name'] : ''
                                ),
                                'address' => array(
                                    'address_line_1' => isset($old_purchase_units['shipping']['address']['address_line_1']) ? $old_purchase_units['shipping']['address']['address_line_1'] : '',
                                    'admin_area_2' => isset($old_purchase_units['shipping']['address']['admin_area_2']) ? $old_purchase_units['shipping']['address']['admin_area_2'] : '',
                                    'admin_area_1' => isset($old_purchase_units['shipping']['address']['admin_area_1']) ? $old_purchase_units['shipping']['address']['admin_area_1'] : '',
                                    'postal_code' => isset($old_purchase_units['shipping']['address']['postal_code']) ? $old_purchase_units['shipping']['address']['postal_code'] : '',
                                    'country_code' => isset($old_purchase_units['shipping']['address']['country_code']) ? $old_purchase_units['shipping']['address']['country_code'] : '',
                                )
                            );
                        }
                        if (!empty($this->final_payment_request_data[$sellerpaypalaccountid]['amt'])) {
                            $this->final_payment_request_data[$sellerpaypalaccountid]['amt'] = $this->final_payment_request_data[$sellerpaypalaccountid]['amt'] + $Payment['amt'];
                        } else {
                            $this->final_payment_request_data[$sellerpaypalaccountid] = $Payment;
                        }
                        if ($this->send_items && $is_mismatch == false) {
                            $Payment['items'] = $PaymentOrderItems;
                            $Payment['itemamt'] = AngellEYE_Gateway_Paypal::number_format($item_total, $order);
                            $Payment['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($shippingamt, $order);
                            $Payment['taxamt'] = AngellEYE_Gateway_Paypal::number_format($taxamt, $order);
                            if (isset($discount_amt) && $discount_amt > 0) {
                                $Payment['discount'] = AngellEYE_Gateway_Paypal::number_format($discount_amt, $order);
                            }
                            if (empty($this->final_payment_request_data[$sellerpaypalaccountid]['items'])) {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['items'] = array();
                            }
                            array_push($this->final_payment_request_data[$sellerpaypalaccountid]['items'], $PaymentOrderItems);
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
                            if (!empty($this->final_payment_request_data[$sellerpaypalaccountid]['discount'])) {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['discount'] = $this->final_payment_request_data[$sellerpaypalaccountid]['discount'] + $Payment['discount'];
                            } else {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['discount'] = $Payment['discount'];
                            }
                        }
                        array_push($new_payments, $Payment);
                        $loop = $loop + 1;
                    } else {
                        if (isset($multi_account_info['merchant_id'])) {
                            $sellerpaypalaccountid = $multi_account_info['merchant_id'];
                        }
                        if (isset($multi_account_info['multi_account_id']) && $multi_account_info['multi_account_id'] === 'default') {
                            $default_pal_id = $this->merchant_id;
                        }
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
                            $item_total = $item_total + $this->discount_array[$product_id];
                            array_push($default_new_payments_line_item, $Item);
                            $discount_amt = isset($this->discount_array[$product_id]) ? AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id]) : '0.00';
                        } else {
                            $Item = array(
                                'name' => $line_item['name'],
                                'desc' => $line_item['desc'],
                                'amt' => AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty']),
                                'number' => $line_item['number'],
                                'qty' => $line_item['qty']
                            );
                            array_push($default_new_payments_line_item, $Item);
                            $discount_amt = 0;
                        }
                        $paymentrequestid_value = $cart_item_key . '-' . rand();
                        $default_shippingamt = $default_shippingamt + $shippingamt;
                        $default_taxamt = $default_taxamt + $taxamt;
                        $default_discount = $default_discount + $discount_amt;
                        $default_final_total = $default_final_total + AngellEYE_Gateway_Paypal::number_format($item_total + $shippingamt + $taxamt - $default_discount, $order);
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
                        if (isset($multi_account_info['merchant_id'])) {
                            $sellerpaypalaccountid = $multi_account_info['merchant_id'];
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
                            $product_commission = AngellEYE_Gateway_Paypal::number_format($item_total / 100 * $this->map_item_with_account[$product_id]['ppcp_site_owner_commission'], 2);
                            $default_final_total = $default_final_total + $product_commission;
                            $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $product_commission);
                            $item_total = AngellEYE_Gateway_Paypal::number_format($item_total - $product_commission);
                            $default_item_total = $default_item_total + $product_commission;
                            if ($this->global_ec_include_tax_shipping_in_commission == 'on') {
                                if ($taxamt > 0) {
                                    $tax_commission = AngellEYE_Gateway_Paypal::number_format($taxamt / 100 * $this->map_item_with_account[$product_id]['ppcp_site_owner_commission'], 2);
                                    $default_final_total = $default_final_total + $tax_commission;
                                    $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $tax_commission);
                                    $taxamt = AngellEYE_Gateway_Paypal::number_format($taxamt - $tax_commission);
                                    $default_item_total = $default_item_total + $tax_commission;
                                }
                                if ($shippingamt > 0) {
                                    $shippingamt_commission = AngellEYE_Gateway_Paypal::number_format($shippingamt / 100 * $this->map_item_with_account[$product_id]['ppcp_site_owner_commission'], 2);
                                    $default_final_total = $default_final_total + $shippingamt_commission;
                                    $final_total = AngellEYE_Gateway_Paypal::number_format($final_total - $shippingamt_commission);
                                    $shippingamt = AngellEYE_Gateway_Paypal::number_format($shippingamt - $shippingamt_commission);
                                    $default_item_total = $default_item_total + $shippingamt_commission;
                                }
                                $sub_total_commission = AngellEYE_Gateway_Paypal::number_format($product_commission + $tax_commission + $shippingamt_commission);
                            } else {
                                $sub_total_commission = AngellEYE_Gateway_Paypal::number_format($product_commission);
                            }
                            $Item = array(
                                'name' => $this->map_item_with_account[$product_id]['ppcp_site_owner_commission_label'],
                                'desc' => $line_item['name'],
                                'amt' => AngellEYE_Gateway_Paypal::number_format($sub_total_commission),
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
                                $item_total = $item_total + $this->discount_array[$product_id];
                                array_push($PaymentOrderItems, $Item);
                                $discount_amt = isset($this->discount_array[$product_id]) ? AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id]) : '0.00';
                            } else {
                                $Item = array(
                                    'name' => $line_item['name'],
                                    'desc' => $line_item['desc'],
                                    'amt' => AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty']),
                                    'number' => $line_item['number'],
                                    'qty' => $line_item['qty']
                                );
                                array_push($PaymentOrderItems, $Item);
                                $discount_amt = 0;
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
                                $item_total = $item_total + $this->discount_array[$product_id];
                                array_push($PaymentOrderItems, $Item);
                                $discount_amt = isset($this->discount_array[$product_id]) ? AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id]) : '0.00';
                            } else {
                                $Item = array(
                                    'name' => $line_item['name'],
                                    'desc' => $line_item['desc'],
                                    'amt' => AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty']),
                                    'number' => $line_item['number'],
                                    'qty' => $line_item['qty']
                                );
                                array_push($PaymentOrderItems, $Item);
                                $discount_amt = 0;
                            }
                        }
                        $this->final_grand_total = $this->final_grand_total + $final_total;
                        $Payment = array(
                            'amt' => AngellEYE_Gateway_Paypal::number_format($final_total),
                            'currencycode' => isset($old_purchase_units['amount']['currency_code']) ? $old_purchase_units['amount']['currency_code'] : '',
                            'custom_id' => isset($old_purchase_units['custom_id']) ? $old_purchase_units['custom_id'] : '',
                            'invoice_id' => isset($old_purchase_units['invoice_id']) ? $old_purchase_units['invoice_id'] : '',
                            'reference_id' => $sellerpaypalaccountid,
                            'soft_descriptor' => isset($old_purchase_units['soft_descriptor']) ? $old_purchase_units['soft_descriptor'] : ''
                        );
                        if (is_email($sellerpaypalaccountid)) {
                            $Payment['payee'] = array('email_address' => $sellerpaypalaccountid);
                        } else {
                            $Payment['payee'] = array('merchant_id' => $sellerpaypalaccountid);
                        }
                        if (!empty($old_purchase_units['shipping']['address']['address_line_1']) && !empty($old_purchase_units['shipping']['address']['country_code'])) {
                            $Payment['shipping'] = array(
                                'name' => array(
                                    'full_name' => isset($old_purchase_units['shipping']['name']['full_name']) ? $old_purchase_units['shipping']['name']['full_name'] : ''
                                ),
                                'address' => array(
                                    'address_line_1' => isset($old_purchase_units['shipping']['address']['address_line_1']) ? $old_purchase_units['shipping']['address']['address_line_1'] : '',
                                    'admin_area_2' => isset($old_purchase_units['shipping']['address']['admin_area_2']) ? $old_purchase_units['shipping']['address']['admin_area_2'] : '',
                                    'admin_area_1' => isset($old_purchase_units['shipping']['address']['admin_area_1']) ? $old_purchase_units['shipping']['address']['admin_area_1'] : '',
                                    'postal_code' => isset($old_purchase_units['shipping']['address']['postal_code']) ? $old_purchase_units['shipping']['address']['postal_code'] : '',
                                    'country_code' => isset($old_purchase_units['shipping']['address']['country_code']) ? $old_purchase_units['shipping']['address']['country_code'] : '',
                                )
                            );
                        }
                        if (!empty($this->final_payment_request_data[$sellerpaypalaccountid]['amt'])) {
                            $this->final_payment_request_data[$sellerpaypalaccountid]['amt'] = $this->final_payment_request_data[$sellerpaypalaccountid]['amt'] + $Payment['amt'];
                        } else {
                            $this->final_payment_request_data[$sellerpaypalaccountid] = $Payment;
                        }
                        if ($this->send_items && $is_mismatch == false) {
                            $Payment['items'] = $PaymentOrderItems;
                            $Payment['itemamt'] = AngellEYE_Gateway_Paypal::number_format($item_total);
                            $Payment['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($shippingamt);
                            $Payment['taxamt'] = AngellEYE_Gateway_Paypal::number_format($taxamt);
                            if (isset($discount_amt) && $discount_amt > 0) {
                                $Payment['discount'] = AngellEYE_Gateway_Paypal::number_format($discount_amt, $order);
                            }
                            if (empty($this->final_payment_request_data[$sellerpaypalaccountid]['items'])) {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['items'] = array();
                            }
                            array_push($this->final_payment_request_data[$sellerpaypalaccountid]['items'], $PaymentOrderItems);
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
                            if (!empty($this->final_payment_request_data[$sellerpaypalaccountid]['discount'])) {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['discount'] = $this->final_payment_request_data[$sellerpaypalaccountid]['discount'] + $Payment['discount'];
                            } else {
                                $this->final_payment_request_data[$sellerpaypalaccountid]['discount'] = $Payment['discount'];
                            }
                        }
                        if ($final_total > 0) {
                            array_push($new_payments, $Payment);
                            $loop = $loop + 1;
                        }
                    } else {
                        if (isset($multi_account_info['merchant_id'])) {
                            $sellerpaypalaccountid = $multi_account_info['merchant_id'];
                        }
                        if (isset($multi_account_info['multi_account_id']) && $multi_account_info['multi_account_id'] === 'default') {
                            $default_pal_id = $this->merchant_id;
                        }
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
                            $item_total = $item_total + $this->discount_array[$product_id];
                            array_push($default_new_payments_line_item, $Item);
                            $discount_amt = isset($this->discount_array[$product_id]) ? AngellEYE_Gateway_Paypal::number_format($this->discount_array[$product_id]) : '0.00';
                        } else {
                            $Item = array(
                                'name' => $line_item['name'],
                                'desc' => $line_item['desc'],
                                'amt' => AngellEYE_Gateway_Paypal::number_format($item_total / $line_item['qty']),
                                'number' => $line_item['number'],
                                'qty' => $line_item['qty']
                            );
                            array_push($default_new_payments_line_item, $Item);
                            $discount_amt = 0;
                        }
                        $default_taxamt = $default_taxamt + $taxamt;
                        $default_final_total = $default_final_total + AngellEYE_Gateway_Paypal::number_format($item_total + $shippingamt + $taxamt - $discount_amt);
                        $default_item_total = $default_item_total + $item_total;
                        $default_shippingamt = $default_shippingamt + $shippingamt;
                        $default_discount = $default_discount + $discount_amt;
                        $loop = $loop + 1;
                    }
                }
            }
        }
        if (!is_null(WC()->cart)) {
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
        }
        if ($default_final_total > 0) {
            if (empty($default_pal_id)) {
                $default_pal_id = $this->merchant_id;
            }
            $this->final_grand_total = $this->final_grand_total + $default_final_total;
            $new_default_payment = array(
                'amt' => AngellEYE_Gateway_Paypal::number_format($default_final_total),
                'currencycode' => isset($old_purchase_units['amount']['currency_code']) ? $old_purchase_units['amount']['currency_code'] : '',
                'custom_id' => isset($old_purchase_units['custom_id']) ? $old_purchase_units['custom_id'] : '',
                'invoice_id' => isset($old_purchase_units['invoice_id']) ? $old_purchase_units['invoice_id'] . '-' . $cart_item_key : '',
                'reference_id' => $default_pal_id,
                'soft_descriptor' => isset($old_purchase_units['soft_descriptor']) ? $old_purchase_units['soft_descriptor'] : ''
            );
            if (is_email($default_pal_id)) {
                $new_default_payment['payee'] = array('email_address' => $default_pal_id);
            } else {
                $new_default_payment['payee'] = array('merchant_id' => $default_pal_id);
            }
            if (!empty($old_purchase_units['shipping']['address']['address_line_1']) && !empty($old_purchase_units['shipping']['address']['country_code'])) {
                $new_default_payment['shipping'] = array(
                    'name' => array(
                        'full_name' => isset($old_purchase_units['shipping']['name']['full_name']) ? $old_purchase_units['shipping']['name']['full_name'] : ''
                    ),
                    'address' => array(
                        'address_line_1' => isset($old_purchase_units['shipping']['address']['address_line_1']) ? $old_purchase_units['shipping']['address']['address_line_1'] : '',
                        'admin_area_2' => isset($old_purchase_units['shipping']['address']['admin_area_2']) ? $old_purchase_units['shipping']['address']['admin_area_2'] : '',
                        'admin_area_1' => isset($old_purchase_units['shipping']['address']['admin_area_1']) ? $old_purchase_units['shipping']['address']['admin_area_1'] : '',
                        'postal_code' => isset($old_purchase_units['shipping']['address']['postal_code']) ? $old_purchase_units['shipping']['address']['postal_code'] : '',
                        'country_code' => isset($old_purchase_units['shipping']['address']['country_code']) ? $old_purchase_units['shipping']['address']['country_code'] : '',
                    )
                );
            }
            if (!empty($this->final_payment_request_data[$default_pal_id]['amt'])) {
                $this->final_payment_request_data[$default_pal_id]['amt'] = $this->final_payment_request_data[$default_pal_id]['amt'] + $new_default_payment['amt'];
            } else {
                $this->final_payment_request_data[$default_pal_id] = $new_default_payment;
            }
            if ($this->send_items) {
                if (!empty($default_new_payments_line_item)) {
                    $new_default_payment['items'] = $default_new_payments_line_item;
                    $new_default_payment['itemamt'] = AngellEYE_Gateway_Paypal::number_format($default_item_total);
                    $new_default_payment['shippingamt'] = AngellEYE_Gateway_Paypal::number_format($default_shippingamt);
                    $new_default_payment['taxamt'] = AngellEYE_Gateway_Paypal::number_format($default_taxamt);
                    $new_default_payment['discount'] = AngellEYE_Gateway_Paypal::number_format($default_discount);
                    if (empty($this->final_payment_request_data[$default_pal_id]['items'])) {
                        $this->final_payment_request_data[$default_pal_id]['items'] = array();
                    }
                    array_push($this->final_payment_request_data[$default_pal_id]['items'], $default_new_payments_line_item);
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
                    $this->final_payment_request_data[$default_pal_id]['discount'] = $default_discount;
                    
                }
            }
            array_push($new_payments, $new_default_payment);
        }
        if (!empty($this->final_payment_request_data)) {
            $this->final_paypal_request = array();
            $index = 0;
            foreach ($this->final_payment_request_data as $email_id => $vendor_payment) {
                if (!empty($vendor_payment['items'])) {
                    $this->final_paypal_request[$index] = $vendor_payment;
                    unset($this->final_paypal_request[$index]['items']);
                    $first_inner_index = 0;
                    foreach ($vendor_payment['items'] as $first_key => $first_order_item) {
                        foreach ($first_order_item as $second_key => $second_order_item) {
                            if (empty($this->final_paypal_request[$index]['items'])) {
                                $this->final_paypal_request[$index]['items'] = array();
                            }
                            $this->final_paypal_request[$index]['items'][$first_inner_index] = $second_order_item;
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
                $this->final_paypal_request[$index] = $this->angelleye_add_commission_payment_data($old_purchase_units, $key, $value);
            }
            if (count($this->map_item_with_account) === 1 && $this->final_grand_total != $this->final_order_grand_total) {
                $Difference = round($this->final_order_grand_total - $this->final_grand_total, $this->decimals);
                if (abs($Difference) > 0.000001 && 0.0 !== (float) $Difference) {
                    $index = $index + 1;
                    $this->final_paypal_request[$index] = $this->angelleye_after_commition_remain_part_to_web_admin($old_purchase_units, $Difference);
                }
            }
            $new_payments = $this->final_paypal_request;
        }
        if ($this->final_grand_total != $this->final_order_grand_total) {
            $Difference = round($this->final_order_grand_total - $this->final_grand_total, $this->decimals);
            if (abs($Difference) > 0.000001 && 0.0 !== (float) $Difference) {
                if (isset($new_payments[0]['amt']) && $new_payments[0]['amt'] > 1) {
                    $new_payments[0]['amt'] = $new_payments[0]['amt'] + $Difference;
                    $item_names = array();
                    if (!empty($new_payments[0]['items'])) {
                        $first_line_item = $new_payments[0]['items'];
                    }
                    if (!empty($first_line_item)) {
                        unset($new_payments[0]['items']);
                        $new_payments[0]['items'] = array();
                        $new_payments[0]['itemamt'] = $new_payments[0]['amt'];
                        foreach ($first_line_item as $key => $value) {
                            $item_names[] = $value['name'] . ' x ' . $value['qty'];
                        }
                        $item_details = implode(', ', $item_names);
                        $item_details = html_entity_decode(wc_trim_string($item_details ? wp_strip_all_tags($item_details) : __('Item', 'paypal-for-woocommerce-multi-account-management'), 127), ENT_NOQUOTES, 'UTF-8');
                        $new_payments[0]['items'][0] = array(
                            'name' => $item_details,
                            'desc' => '',
                            'amt' => AngellEYE_Gateway_Paypal::number_format($new_payments[0]['amt']),
                            'qty' => 1
                        );
                    }
                    unset($new_payments[0]['shippingamt']);
                    unset($new_payments[0]['taxamt']);
                }
            }
        }

        if ($action === 'create_order') {
            if (!empty($new_payments)) {
                foreach ($new_payments as $key_new_payments => $value_new_payments) {
                    $value_new_payments['amount'] = array(
                        'currency_code' => $old_purchase_units['amount']['currency_code'],
                        'value' => AngellEYE_Gateway_Paypal::number_format($value_new_payments['amt']),
                    );
                    if (!empty($value_new_payments['items'])) {
                        foreach ($value_new_payments['items'] as $key => $value) {
                            $value_new_payments['items'][$key]['unit_amount'] = array(
                                'currency_code' => $old_purchase_units['amount']['currency_code'],
                                'value' => AngellEYE_Gateway_Paypal::number_format($value['amt'])
                            );
                            $value_new_payments['items'][$key]['quantity'] = $value['qty'];
                            unset($value_new_payments['items'][$key]['amt']);
                            unset($value_new_payments['items'][$key]['qty']);
                        }
                    }
                    unset($value_new_payments['amt']);
                    if (!empty($value_new_payments['itemamt'])) {
                        $value_new_payments['amount']['breakdown']['item_total'] = array(
                            'currency_code' => $old_purchase_units['amount']['currency_code'],
                            'value' => AngellEYE_Gateway_Paypal::number_format($value_new_payments['itemamt'])
                        );
                    }
                    unset($value_new_payments['itemamt']);
                    if (!empty($value_new_payments['shippingamt'])) {
                        $value_new_payments['amount']['breakdown']['shipping'] = array(
                            'currency_code' => $old_purchase_units['amount']['currency_code'],
                            'value' => AngellEYE_Gateway_Paypal::number_format($value_new_payments['shippingamt'])
                        );
                    }
                    unset($value_new_payments['shippingamt']);
                    if (!empty($value_new_payments['taxamt'])) {
                        $value_new_payments['amount']['breakdown']['tax_total'] = array(
                            'currency_code' => $old_purchase_units['amount']['currency_code'],
                            'value' => AngellEYE_Gateway_Paypal::number_format($value_new_payments['taxamt'])
                        );
                    }
                    unset($value_new_payments['taxamt']);
                    if (!empty($value_new_payments['discount'])) {
                        $value_new_payments['amount']['breakdown']['discount'] = array(
                            'currency_code' => $old_purchase_units['amount']['currency_code'],
                            'value' => AngellEYE_Gateway_Paypal::number_format($value_new_payments['discount'])
                        );
                    }
                    unset($value_new_payments['discount']);
                    $request['body']['purchase_units'][$key_new_payments] = $value_new_payments;
                }
            } else {
                $request['body']['purchase_units'] = $old_purchase_units;
            }
        } elseif ($action === 'update_order') {
            $patch_request = array();
            $order = wc_get_order($order_id);
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            if (!empty($new_payments)) {
                foreach ($new_payments as $key_new_payments => $value_new_payments) {
                    if (!empty($value_new_payments['itemamt'])) {
                        $update_amount_request['item_total'] = array(
                            'currency_code' => angelleye_ppcp_get_currency($order_id),
                            'value' => AngellEYE_Gateway_Paypal::number_format($value_new_payments['itemamt'])
                        );
                    }
                    if (!empty($value_new_payments['shippingamt'])) {
                        $update_amount_request['shipping'] = array(
                            'currency_code' => angelleye_ppcp_get_currency($order_id),
                            'value' => AngellEYE_Gateway_Paypal::number_format($value_new_payments['shippingamt'])
                        );
                    }

                    if (!empty($value_new_payments['taxamt'])) {
                        $update_amount_request['tax_total'] = array(
                            'currency_code' => angelleye_ppcp_get_currency($order_id),
                            'value' => AngellEYE_Gateway_Paypal::number_format($value_new_payments['taxamt'])
                        );
                    }

                    if (!empty($value_new_payments['discount'])) {
                        $update_amount_request['discount'] = array(
                            'currency_code' => angelleye_ppcp_get_currency($order_id),
                            'value' => AngellEYE_Gateway_Paypal::number_format($value_new_payments['discount'])
                        );
                    }

                    $reference_id = $value_new_payments['reference_id'];
                    $patch_request[] = array(
                        'op' => 'replace',
                        'path' => "/purchase_units/@reference_id=='$reference_id'/amount",
                        'value' =>
                        array(
                            'currency_code' => angelleye_ppcp_get_currency($order_id),
                            'value' => AngellEYE_Gateway_Paypal::number_format($value_new_payments['amt']),
                            'breakdown' => $update_amount_request
                        ),
                    );
                    if ($order->needs_shipping_address() || WC()->cart->needs_shipping_address()) {
                        if (( $old_wc && ( $order->shipping_address_1 || $order->shipping_address_2 ) ) || (!$old_wc && $order->has_shipping_address() )) {
                            $shipping_first_name = $old_wc ? $order->shipping_first_name : $order->get_shipping_first_name();
                            $shipping_last_name = $old_wc ? $order->shipping_last_name : $order->get_shipping_last_name();
                            $shipping_address_1 = $old_wc ? $order->shipping_address_1 : $order->get_shipping_address_1();
                            $shipping_address_2 = $old_wc ? $order->shipping_address_2 : $order->get_shipping_address_2();
                            $shipping_city = $old_wc ? $order->shipping_city : $order->get_shipping_city();
                            $shipping_state = $old_wc ? $order->shipping_state : $order->get_shipping_state();
                            $shipping_postcode = $old_wc ? $order->shipping_postcode : $order->get_shipping_postcode();
                            $shipping_country = $old_wc ? $order->shipping_country : $order->get_shipping_country();
                        } else {
                            $shipping_first_name = $old_wc ? $order->billing_first_name : $order->get_billing_first_name();
                            $shipping_last_name = $old_wc ? $order->billing_last_name : $order->get_billing_last_name();
                            $shipping_address_1 = $old_wc ? $order->billing_address_1 : $order->get_billing_address_1();
                            $shipping_address_2 = $old_wc ? $order->billing_address_2 : $order->get_billing_address_2();
                            $shipping_city = $old_wc ? $order->billing_city : $order->get_billing_city();
                            $shipping_state = $old_wc ? $order->billing_state : $order->get_billing_state();
                            $shipping_postcode = $old_wc ? $order->billing_postcode : $order->get_billing_postcode();
                            $shipping_country = $old_wc ? $order->billing_country : $order->get_billing_country();
                        }
                        $shipping_address_request = array(
                            'address_line_1' => $shipping_address_1,
                            'address_line_2' => $shipping_address_2,
                            'admin_area_2' => $shipping_city,
                            'admin_area_1' => $shipping_state,
                            'postal_code' => $shipping_postcode,
                            'country_code' => $shipping_country,
                        );
                        if (!empty($shipping_address_request['address_line_1']) && !empty($shipping_address_request['country_code'])) {
                            $angelleye_ppcp_is_shipping_added = false;
                            if (class_exists('AngellEye_Session_Manager')) {
                                $angelleye_ppcp_is_shipping_added = AngellEye_Session_Manager::get('is_shipping_added', false);
                            }
                            if ($angelleye_ppcp_is_shipping_added === 'yes') {
                                $replace = 'replace';
                            } else {
                                $replace = 'add';
                            }
                            $patch_request[] = array(
                                'op' => $replace,
                                'path' => "/purchase_units/@reference_id=='$reference_id'/shipping/address",
                                'value' => $shipping_address_request
                            );
                        }
                    }
                    $patch_request[] = array(
                        'op' => 'replace',
                        'path' => "/purchase_units/@reference_id=='$reference_id'/invoice_id",
                        'value' => $this->invoice_prefix . substr(md5(microtime()), rand(0, 26), 2) . str_replace("#", "", $order->get_order_number())
                    );
                    $patch_request[] = array(
                        'op' => 'replace',
                        'path' => "/purchase_units/@reference_id=='$reference_id'/custom_id",
                        'value' => $this->invoice_prefix . substr(md5(microtime()), rand(0, 26), 2) . str_replace("#", "", $order->get_order_number())
                    );
                }
            }
            if (!empty($order_id) && !empty($this->map_item_with_account) && $this->angelleye_is_multi_account_used($this->map_item_with_account)) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $order->update_meta_data('_angelleye_multi_account_ppcp_parallel_data_map', $this->map_item_with_account);
                    $order->save_meta_data();
                }
            }
            return $patch_request;
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

    public function angelleye_get_merchant_id_for_multi($account_id, $microprocessing_array) {
        if ($this->is_sandbox) {
            $client_id = $microprocessing_array['woocommerce_angelleye_ppcp_sandbox_client_id'][0];
            $secret_id = $microprocessing_array['woocommerce_angelleye_ppcp_sandbox_secret'][0];
        } else {
            $client_id = $microprocessing_array['woocommerce_angelleye_ppcp_client_id'][0];
            $secret_id = $microprocessing_array['woocommerce_angelleye_ppcp_secret'][0];
        }
        if (!class_exists('AngellEYE_PayPal_PPCP_Payment')) {
            include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-payment.php');
        }
        $payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
        $merchant_id = '';
        // TEST  need debug
        if (!empty($merchant_id)) {
            if ($this->is_sandbox) {
                update_post_meta($account_id, 'woocommerce_angelleye_ppcp_sandbox_merchant_id', $merchant_id);
            } else {
                update_post_meta($account_id, 'woocommerce_angelleye_ppcp_merchant_id', $merchant_id);
            }
            return $merchant_id;
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
        $partition = AngellEYE_Gateway_Paypal::number_format($amount / $divided);
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

    public function angelleye_is_multi_account_api_set($microprocessing_array) {
        if ($this->is_sandbox) {
            if (!empty($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_client_id'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_secret'][0])) {
                return true;
            }
        } else {
            if (!empty($microprocessing_array['woocommerce_angelleye_ppcp_client_id'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_secret'][0])) {
                return true;
            }
        }
        return false;
    }

    public function own_angelleye_ppcp_order_data($paypal_response, $order_id) {
        $order = wc_get_order($order_id);
        $ec_parallel_data_map = $order->get_meta('_angelleye_multi_account_ppcp_parallel_data_map', true);
        if (empty($ec_parallel_data_map)) {
            return false;
        }
        $unique_transaction_data = array();
        foreach ($paypal_response['purchase_units'] as $key => $payments) {
            if (!empty($paypal_response['purchase_units'][$key]['reference_id'])) {
                if ($this->angelleye_ppcp_is_payer_email_exist($paypal_response['purchase_units'][$key]['reference_id'], $ec_parallel_data_map)) {
                    foreach ($ec_parallel_data_map as $multi_key => $parallel_data_map) {
                        if (!empty($parallel_data_map['merchant_id']) && $parallel_data_map['merchant_id'] === $paypal_response['purchase_units'][$key]['reference_id']) {
                            $ec_parallel_data_map[$parallel_data_map['product_id']]['transaction_id'] = $paypal_response['purchase_units'][$key]['payments']['captures'][0]['id'];
                            $unique_transaction_data[] = $paypal_response['purchase_units'][$key]['payments']['captures'][0]['id'];
                            wc_update_order_item_meta($parallel_data_map['order_item_id'], '_transaction_id', $paypal_response['purchase_units'][$key]['payments']['captures'][0]['id']);
                        }
                    }
                } else {
                    $ec_parallel_data_map['primary']['transaction_id'] = $paypal_response['purchase_units'][$key]['payments']['captures'][0]['id'];
                    $ec_parallel_data_map['primary']['multi_account_id'] = 'default';
                }
            }
        }
        if (!empty($ec_parallel_data_map)) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_meta_data('_angelleye_multi_account_ppcp_parallel_data_map', $ec_parallel_data_map);
                $order->save_meta_data();
            }
        }
    }

    public function angelleye_ppcp_is_payer_email_exist($paypal_email, $ec_parallel_data_map) {
        foreach ($ec_parallel_data_map as $key => $parallel_data_map) {
            if (!empty($parallel_data_map['merchant_id']) && $parallel_data_map['merchant_id'] === $paypal_email) {
                return true;
            }
        }
        return false;
    }

    public function angelleye_get_map_item_data($request_param_part_data, $ec_parallel_data_map) {
        foreach ($ec_parallel_data_map as $key => $value) {
            if ($value['order_item_id'] == $request_param_part_data) {
                return $key;
            }
        }
        return false;
    }

    public function own_woocommerce_ppcp_payment_gateway_supports($bool, $feature, $current) {
        global $theorder;
        if ($theorder instanceof WC_Order) {
            if ($feature === 'refunds' && $bool === true && $current->id === 'angelleye_ppcp') {
                $order = $theorder;
                if ($order) {
                    $angelleye_multi_account_ppcp_parallel_data_map = $order->get_meta('_angelleye_multi_account_ppcp_parallel_data_map', true);
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

    public function own_angelleye_is_ppcp_parallel_payment_not_used($bool, $order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $angelleye_multi_account_ppcp_parallel_data_map = $order->get_meta('_angelleye_multi_account_ppcp_parallel_data_map', true);
            if (!empty($angelleye_multi_account_ppcp_parallel_data_map)) {
                return false;
            }
            return $bool;
        }
        return $bool;
    }

    public function own_angelleye_is_ppcp_parallel_payment_handle($bool, $order_id, $gateway) {
        try {
            $order = wc_get_order($order_id);
            $processed_transaction_id = array();
            $angelleye_multi_account_ppcp_parallel_data_map = $order->get_meta('_angelleye_multi_account_ppcp_parallel_data_map', true);
            if (!empty($angelleye_multi_account_ppcp_parallel_data_map)) {
                foreach ($angelleye_multi_account_ppcp_parallel_data_map as $key => $value) {
                    if ($key === 'always') {
                        foreach ($value as $inner_key => $inner_value) {
                            $this->paypal_response = $this->angelleye_ppcp_load_paypal($inner_value, $gateway, $order_id);
                            $processed_transaction_id[] = $inner_value['transaction_id'];
                            if (!empty($this->paypal_response['id'])) {
                                $angelleye_multi_account_ppcp_parallel_data_map[$key][$inner_key]['id'] = $this->paypal_response['id'];
                                $angelleye_multi_account_ppcp_parallel_data_map[$key][$inner_key]['gross_amount'] = $this->paypal_response['seller_payable_breakdown']['gross_amount']['value'];
                            }
                        }
                    } elseif (isset($value['transaction_id']) && !in_array($value['transaction_id'], $processed_transaction_id)) {
                        $this->paypal_response = $this->angelleye_ppcp_load_paypal($value, $gateway, $order_id);
                        $processed_transaction_id[] = $value['transaction_id'];
                        if (!empty($this->paypal_response['id'])) {
                            $angelleye_multi_account_ppcp_parallel_data_map[$key]['id'] = $this->paypal_response['id'];
                            $angelleye_multi_account_ppcp_parallel_data_map[$key]['gross_amount'] = $this->paypal_response['seller_payable_breakdown']['gross_amount']['value'];
                        } else {
                            $angelleye_multi_account_ppcp_parallel_data_map[$key]['delete_refund_item'] = 'yes';
                        }
                    }
                }
                $order->update_meta_data('_angelleye_multi_account_ppcp_parallel_data_map', $angelleye_multi_account_ppcp_parallel_data_map);
                $order->update_meta_data('_multi_account_refund_amount', $this->final_refund_amt);
                $order->save_meta_data();
                return true;
            }
            return false;
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_load_paypal($value, $gateway, $order_id) {
        if (!empty($value['multi_account_id'])) {
            if (!class_exists('AngellEYE_PayPal_PPCP_Payment')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-payment.php');
            }
            if ($this->is_sandbox) {
                $testmode = true;
            } else {
                $testmode = false;
            }
            $payment_request = AngellEYE_PayPal_PPCP_Payment::instance();
            $this->paypal_response = $payment_request->angelleye_ppcp_multi_account_refund_order_third_party($order_id, $value, $testmode);
            return $this->paypal_response;
        }
    }

    public function own_woocommerce_order_item_add_action_buttons($order) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $angelleye_multi_account_ppcp_parallel_data_map = $order->get_meta('_angelleye_multi_account_ppcp_parallel_data_map', true);
        if (!empty($angelleye_multi_account_ppcp_parallel_data_map)) {
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
            $angelleye_multi_account_ppcp_parallel_data_map = $order->get_meta('_angelleye_multi_account_ppcp_parallel_data_map', true);
            if (!empty($angelleye_multi_account_ppcp_parallel_data_map) && $payment_method == 'angelleye_ppcp') {
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

    public function angelleye_get_account_for_ppcp_payment_load_balancer($request = null, $action = null, $order_id = null) {
        if (!isset($this->is_sandbox)) {
            return;
        }
        $found_account = false;
        $found_merchant_id = '';
        if ($this->is_sandbox == true) {
            $option_key = 'angelleye_multi_ppcp_payment_load_balancer_sandbox';
            $session_key = 'angelleye_sandbox_payment_load_balancer_ppcp_email';
            $session_key_account = 'angelleye_sandbox_payment_load_balancer_ppcp_account';
        } else {
            $option_key = 'angelleye_multi_ppcp_payment_load_balancer';
            $session_key = 'angelleye_payment_load_balancer_ppcp_email';
            $session_key_account = 'angelleye_payment_load_balancer_ppcp_account';
        }
        $found_merchant_id = WC()->session->get($session_key);
        if (empty($found_merchant_id)) {
            $found_merchant_id = '';
            $ppcp_accounts = get_option($option_key);
            if (!empty($ppcp_accounts)) {
                foreach ($ppcp_accounts as $key => $account) {
                    if (empty($account['is_used'])) {
                        if ($key != 'default' && false === get_post_status($key)) {
                            unset($ppcp_accounts[$key]);
                        } else {
                            $found_merchant_id = $account['merchant_id'];
                            WC()->session->set($session_key, $account['merchant_id']);
                            $account['is_used'] = 'yes';
                            $ppcp_accounts[$key] = $account;
                            WC()->session->set($session_key_account, $account);
                            update_option($option_key, $ppcp_accounts);
                            $found_account = true;
                            break;
                        }
                    }
                }
                if ($found_account == false) {
                    foreach ($ppcp_accounts as $key => $account) {
                        $account['is_used'] = '';
                        $ppcp_accounts[$key] = $account;
                    }
                    foreach ($ppcp_accounts as $key => $account) {
                        if ($key != 'default' && false === get_post_status($key)) {
                            unset($ppcp_accounts[$key]);
                        } else {
                            $found_merchant_id = $account['merchant_id'];
                            WC()->session->set($session_key, $account['merchant_id']);
                            $account['is_used'] = 'yes';
                            $ppcp_accounts[$key] = $account;
                            WC()->session->set($session_key_account, $account);
                            update_option($option_key, $ppcp_accounts);
                            $found_account = true;
                            break;
                        }
                    }
                }
            }
        }

        if (!empty($request)) {
            if ($found_merchant_id != 'default') {
                if (is_email($found_merchant_id)) {
                    $request['body']['purchase_units'][0]['payee']['email_address'] = $found_merchant_id;
                } else {
                    $request['body']['purchase_units'][0]['payee']['merchant_id'] = $found_merchant_id;
                }
                if (!empty($order_id)) {
                    $order = wc_get_order();
                    $angelleye_payment_load_balancer_account = WC()->session->get($session_key_account);
                    if ($order) {
                        $order->update_meta_data('_angelleye_payment_load_balancer_account', $angelleye_payment_load_balancer_account);
                        $order->save_meta_data();
                    }
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

    public function own_angelleye_is_ppcp_payment_load_balancer_handle($bool, $order_id, $gateway) {
        try {
            $order = wc_get_order($order_id);
            $processed_transaction_id = array();
            $refund_error_message_pre = __('We can not refund this order as the PayPal API keys are missing! Please go to multi-account setup and add API key to process the refund', 'paypal-for-woocommerce-multi-account-management');
            $angelleye_payment_load_balancer_account = $order->get_meta('_angelleye_payment_load_balancer_account', true);
            if (!empty($angelleye_payment_load_balancer_account)) {
                if (!empty($angelleye_payment_load_balancer_account['is_api_set']) && apply_filters('angelleye_ppcp_pfwma_is_api_set', $angelleye_payment_load_balancer_account['is_api_set'], $angelleye_payment_load_balancer_account) === true) {
                    $_transaction_id = $order->get_transaction_id();
                    $angelleye_payment_load_balancer_account['transaction_id'] = $_transaction_id;
                    $this->angelleye_ppcp_load_paypal($angelleye_payment_load_balancer_account, $gateway, $order_id);
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
        if (!is_array($args) || !array_key_exists('qty', $args) || !array_key_exists('cost', $args)) {
            wc_doing_it_wrong(__FUNCTION__, '$args must contain `cost` and `qty` keys.', '4.0.1');
        }
        include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';
        $args = apply_filters('woocommerce_evaluate_shipping_cost_args', $args, $sum, $this);
        $locale = localeconv();
        $decimals = array(wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',');
        $this->fee_cost = $args['cost'];
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
        $sum = preg_replace('/\s+/', '', $sum);
        $sum = str_replace($decimals, '.', $sum);
        $sum = rtrim(ltrim($sum, "\t\n\r\0\x0B+*/"), "\t\n\r\0\x0B+-*/");
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

    public function angelleye_add_commission_payment_data($old_purchase_units, $account_id, $item_data) {
        $microprocessing_array = get_post_meta($account_id);
        if ($this->is_sandbox == true) {
            if (isset($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_merchant_id'][0])) {
                $merchant_id = $microprocessing_array['woocommerce_angelleye_ppcp_sandbox_merchant_id'][0];
            } elseif (isset($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0])) {
                $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0];
            } else {
                $merchant_id = $this->angelleye_get_merchant_id_for_multi($account_id, $microprocessing_array);
            }
            if ($this->angelleye_is_multi_account_api_set($microprocessing_array)) {
                $is_api_set = true;
            } else {
                $is_api_set = false;
            }
        } else {
            if (isset($microprocessing_array['woocommerce_angelleye_ppcp_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_merchant_id'][0])) {
                $merchant_id = $microprocessing_array['woocommerce_angelleye_ppcp_merchant_id'][0];
            } elseif (isset($microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0])) {
                $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0];
            } else {
                $merchant_id = $this->angelleye_get_merchant_id_for_multi($account_id, $microprocessing_array);
            }
            if ($this->angelleye_is_multi_account_api_set($microprocessing_array)) {
                $is_api_set = true;
            } else {
                $is_api_set = false;
            }
        }
        $this->map_item_with_account['always'][$account_id] = array();
        $this->map_item_with_account['always'][$account_id]['multi_account_id'] = $account_id;
        $this->map_item_with_account['always'][$account_id]['merchant_id'] = $merchant_id;
        $this->map_item_with_account['always'][$account_id]['is_api_set'] = $is_api_set;
        $this->map_item_with_account['always'][$account_id]['sellerpaypalaccountid'] = $merchant_id;
        $cart_item_key = 'always-' . $account_id;
        $this->final_order_grand_total;
        $commission_amt = AngellEYE_Gateway_Paypal::number_format($this->final_order_grand_total / 100 * $item_data['commission_amount_percentage'], 2);
        $Payment = array(
            'amt' => AngellEYE_Gateway_Paypal::number_format($commission_amt),
            'currencycode' => isset($old_purchase_units['amount']['currency_code']) ? $old_purchase_units['amount']['currency_code'] : '',
            'custom_id' => isset($old_purchase_units['custom_id']) ? $old_purchase_units['custom_id'] : '',
            'invoice_id' => isset($old_purchase_units['invoice_id']) ? $old_purchase_units['invoice_id'] . '-' . $cart_item_key : '',
            'payee' => array('merchant_id' => $merchant_id),
            'reference_id' => $merchant_id,
            'itemamt' => AngellEYE_Gateway_Paypal::number_format($commission_amt),
            'shippingamt' => '0.00',
            'taxamt' => '0.00',
            'soft_descriptor' => isset($old_purchase_units['soft_descriptor']) ? $old_purchase_units['soft_descriptor'] : '',
        );
        if (is_email($merchant_id)) {
            $Payment['payee'] = array('email_address' => $merchant_id);
        } else {
            $Payment['payee'] = array('merchant_id' => $merchant_id);
        }
        if (!empty($old_purchase_units['shipping']['address']['address_line_1']) && !empty($old_purchase_units['shipping']['address']['country_code'])) {
            $Payment['shipping'] = array(
                'name' => array(
                    'full_name' => isset($old_purchase_units['shipping']['name']['full_name']) ? $old_purchase_units['shipping']['name']['full_name'] : ''
                ),
                'address' => array(
                    'address_line_1' => isset($old_purchase_units['shipping']['address']['address_line_1']) ? $old_purchase_units['shipping']['address']['address_line_1'] : '',
                    'admin_area_2' => isset($old_purchase_units['shipping']['address']['admin_area_2']) ? $old_purchase_units['shipping']['address']['admin_area_2'] : '',
                    'admin_area_1' => isset($old_purchase_units['shipping']['address']['admin_area_1']) ? $old_purchase_units['shipping']['address']['admin_area_1'] : '',
                    'postal_code' => isset($old_purchase_units['shipping']['address']['postal_code']) ? $old_purchase_units['shipping']['address']['postal_code'] : '',
                    'country_code' => isset($old_purchase_units['shipping']['address']['country_code']) ? $old_purchase_units['shipping']['address']['country_code'] : '',
                )
            );
        }
        $PaymentOrderItems = array();
        $Item = array(
            'name' => $item_data['commission_item_label'],
            'desc' => '',
            'amt' => AngellEYE_Gateway_Paypal::number_format($commission_amt),
            'qty' => '1',
        );
        array_push($PaymentOrderItems, $Item);
        $Payment['items'] = $PaymentOrderItems;
        $this->final_grand_total = $this->final_grand_total + $commission_amt;
        return $Payment;
    }

    public function angelleye_after_commition_remain_part_to_web_admin($old_purchase_units, $amount) {
        $merchant_id = $this->merchant_id;
        $is_api_set = true;
        $this->map_item_with_account['always'][$merchant_id] = array();
        $this->map_item_with_account['always'][$merchant_id]['multi_account_id'] = 'default';
        $this->map_item_with_account['always'][$merchant_id]['merchant_id'] = $merchant_id;
        $this->map_item_with_account['always'][$merchant_id]['is_api_set'] = $is_api_set;
        $this->map_item_with_account['always'][$merchant_id]['sellerpaypalaccountid'] = $merchant_id;
        $cart_item_key = 'efault';
        $commission_amt = AngellEYE_Gateway_Paypal::number_format($amount, 2);
        $Payment = array(
            'amt' => AngellEYE_Gateway_Paypal::number_format($commission_amt),
            'currencycode' => isset($old_purchase_units['amount']['currency_code']) ? $old_purchase_units['amount']['currency_code'] : '',
            'custom_id' => isset($old_purchase_units['custom_id']) ? $old_purchase_units['custom_id'] : '',
            'invoice_id' => isset($old_purchase_units['invoice_id']) ? $old_purchase_units['invoice_id'] . '-' . $cart_item_key : '',
            'payee' => array('merchant_id' => $merchant_id),
            'reference_id' => $merchant_id,
            'shippingamt' => '0.00',
            'taxamt' => '0.00',
            'soft_descriptor' => isset($old_purchase_units['soft_descriptor']) ? $old_purchase_units['soft_descriptor'] : ''
        );
        if (is_email($merchant_id)) {
            $Payment['payee'] = array('email_address' => $merchant_id);
        } else {
            $Payment['payee'] = array('merchant_id' => $merchant_id);
        }
        if (!empty($old_purchase_units['shipping']['address']['address_line_1']) && !empty($old_purchase_units['shipping']['address']['country_code'])) {
            $Payment['shipping'] = array(
                'name' => array(
                    'full_name' => isset($old_purchase_units['shipping']['name']['full_name']) ? $old_purchase_units['shipping']['name']['full_name'] : ''
                ),
                'address' => array(
                    'address_line_1' => isset($old_purchase_units['shipping']['address']['address_line_1']) ? $old_purchase_units['shipping']['address']['address_line_1'] : '',
                    'admin_area_2' => isset($old_purchase_units['shipping']['address']['admin_area_2']) ? $old_purchase_units['shipping']['address']['admin_area_2'] : '',
                    'admin_area_1' => isset($old_purchase_units['shipping']['address']['admin_area_1']) ? $old_purchase_units['shipping']['address']['admin_area_1'] : '',
                    'postal_code' => isset($old_purchase_units['shipping']['address']['postal_code']) ? $old_purchase_units['shipping']['address']['postal_code'] : '',
                    'country_code' => isset($old_purchase_units['shipping']['address']['country_code']) ? $old_purchase_units['shipping']['address']['country_code'] : '',
                )
            );
        }
        $this->final_grand_total = $this->final_grand_total + $commission_amt;
        return $Payment;
    }

    public function angelleye_ppcp_is_account_ready_to_paid($bool, $email) {
        return angelleye_ppcp_account_ready_to_paid($this->is_sandbox, $this->client_id, $this->secret_id, $email);
    }

    public function angelleye_multi_account_dokan_refund_approve($refund, $args, $vendor_refund) {
        $parent_order_id = '';
        $order = wc_get_order($refund->get_order_id());
        if (!$order instanceof \WC_Order) {
            return;
        }
        if ('angelleye_ppcp' !== $order->get_payment_method()) {
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
        $refund_error_message_pre = __('We can not refund this order as the PayPal API keys are missing! Please go to multi-account setup and add API key to process the refund', 'paypal-for-woocommerce-multi-account-management');
        $refund_error_message_after = array();
        if (!empty($parent_order_id)) {
            $parent_order = wc_get_order($parent_order_id);
            if ($parent_order) {
                $angelleye_multi_account_ppcp_parallel_data_map = $parent_order->get_meta('_angelleye_multi_account_ppcp_parallel_data_map', true);
            }
        } else {
            $angelleye_multi_account_ppcp_parallel_data_map = $order->get_meta('_angelleye_multi_account_ppcp_parallel_data_map', true);
        }
        $order_item_array = $refund->get_item_qtys();
        if (!empty($order_item_array)) {
            foreach ($order_item_array as $order_item_id_key => $order_item_id_value) {
                if (!empty($angelleye_multi_account_ppcp_parallel_data_map)) {
                    foreach ($angelleye_multi_account_ppcp_parallel_data_map as $key => $value) {
                        $product_id = get_metadata('order_item', $order_item_id_key, '_product_id', true);
                        if (isset($value['product_id']) && $product_id == $value['product_id']) {
                            if (!empty($value['product_id']) && isset($value['is_api_set']) && apply_filters('angelleye_ppcp_pfwma_is_api_set', $value['is_api_set'], $value) === false) {
                                $product = wc_get_product($value['product_id']);
                                $refund_error_message_after[] = $product->get_title();
                            } elseif ($key === 'always') {
                                foreach ($value as $inner_key => $inner_value) {
                                    if (!empty($inner_value['multi_account_id']) && isset($inner_value['is_api_set']) && apply_filters('angelleye_ppcp_pfwma_is_api_set', $inner_value['is_api_set'], $inner_value) === false) {
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
                if (!empty($angelleye_multi_account_ppcp_parallel_data_map)) {
                    foreach ($angelleye_multi_account_ppcp_parallel_data_map as $key => $value) {
                        $product_id = get_metadata('order_item', $order_item_id_key, '_product_id', true);
                        if (isset($value['product_id']) && $product_id == $value['product_id']) {
                            if ($key === 'always') {
                                foreach ($value as $inner_key => $inner_value) {
                                    $this->angelleye_ppcp_load_paypal($inner_value, $gateway, $order_id);
                                    $processed_transaction_id[] = $inner_value['transaction_id'];
                                    if (!empty($this->paypal_response['id'])) {
                                        $angelleye_multi_account_ppcp_parallel_data_map[$key][$inner_key]['id'] = $this->paypal_response['id'];
                                        $angelleye_multi_account_ppcp_parallel_data_map[$key][$inner_key]['gross_amount'] = $this->paypal_response['seller_payable_breakdown']['gross_amount']['value'];
                                    }
                                }
                            } elseif (!in_array($value['transaction_id'], $processed_transaction_id)) {
                                $this->angelleye_ppcp_load_paypal($value, $gateway, $order_id);
                                $processed_transaction_id[] = $value['transaction_id'];
                                if (!empty($this->paypal_response['id'])) {
                                    $angelleye_multi_account_ppcp_parallel_data_map[$key]['id'] = $this->paypal_response['id'];
                                    $angelleye_multi_account_ppcp_parallel_data_map[$key]['gross_amount'] = $this->paypal_response['seller_payable_breakdown']['gross_amount']['value'];
                                } else {
                                    $angelleye_multi_account_ppcp_parallel_data_map[$key]['delete_refund_item'] = 'yes';
                                }
                            }
                        }
                    }
                    $order->update_meta_data('_angelleye_multi_account_ppcp_parallel_data_map', $angelleye_multi_account_ppcp_parallel_data_map);
                    $order->update_meta_data('_multi_account_refund_amount', $this->final_refund_amt);
                    $order->save_meta_data();
                    return true;
                }
            }
        }
    }

    public function angelleye_get_list_merchant_ids($default_merchant_id) {
        $angelleye_payment_load_balancer = get_option('angelleye_payment_load_balancer', '');
        if ($angelleye_payment_load_balancer != '') {
            $merchant_id_list = $this->angelleye_get_merchant_ids_from_load_balancer();
            if (!empty($merchant_id_list)) {
                return $merchant_id_list;
            }
            return false;
        } else {
            $merchant_ids = array();
            $merchant_id_list = array();
            $product_ids = $this->angelleye_get_product_ids();
            if (!empty($product_ids)) {
                $merchant_ids = $this->angelleye_get_merchant_id_using_product_id($product_ids);
                if (!empty($merchant_ids)) {
                    foreach ($merchant_ids as $key => $value) {
                        if (!empty($value['merchant_id'])) {
                            $merchant_id_list[$value['merchant_id']] = $value['merchant_id'];
                            if (isset($value['is_commission_enable']) && $value['is_commission_enable'] === true) {
                                $merchant_id = $this->merchant_id;
                                $merchant_id_list[$merchant_id] = $merchant_id;
                            }
                        } elseif (isset($value['multi_account_id']) && 'default' === $value['multi_account_id']) {
                            $merchant_id = $this->merchant_id;
                            $merchant_id_list[$merchant_id] = $merchant_id;
                        }
                    }
                    if (!empty($merchant_id_list)) {
                        return $merchant_id_list;
                    }
                    return false;
                }
                return false;
            }
            return false;
        }
    }

    public function angelleye_get_merchant_id_using_product_id($angelleye_product_ids) {
        global $user_ID;
        $current_user_roles = array();
        $gateways = $this->angelleye_wc_gateway();
        $testmode = $this->angelleye_wc_gateway()->get_option('testmode', 'yes');
        if (is_user_logged_in()) {
            $user = new WP_User($user_ID);
            if (!empty($user->roles) && is_array($user->roles)) {
                $current_user_roles = $user->roles;
                $current_user_roles[] = 'all';
            }
        }
        $this->final_associate_account = array();
        $order_total = $this->angelleye_get_total($order_id = 0);
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
                    'key' => 'woocommerce_angelleye_ppcp_enable',
                    'value' => 'on',
                    'compare' => '='
                ),
                array(
                    'key' => 'woocommerce_angelleye_ppcp_testmode',
                    'value' => ($testmode === 'yes') ? 'on' : '',
                    'compare' => '='
                ),
                array(
                    'key' => 'woocommerce_priority',
                    'compare' => 'EXISTS'
                )
            )
        );

        array_push($args['meta_query'], array(
            'key' => ($testmode === 'yes') ? 'woocommerce_angelleye_ppcp_multi_account_on_board_status_sandbox' : 'woocommerce_angelleye_ppcp_multi_account_on_board_status_live',
            'value' => 'yes',
            'compare' => '='
        ));

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
                    if (isset($microprocessing_array['woocommerce_angelleye_ppcp_always_trigger'][0]) && 'on' === $microprocessing_array['woocommerce_angelleye_ppcp_always_trigger'][0]) {
                        $this->always_trigger_commission_total_percentage = $this->always_trigger_commission_total_percentage + $microprocessing_array['always_trigger_commission'][0];
                        continue;
                    }
                    if (!empty($microprocessing_array['woocommerce_paypal_express_api_condition_sign'][0]) && isset($microprocessing_array['woocommerce_paypal_express_api_condition_value'][0])) {
                        switch ($microprocessing_array['woocommerce_paypal_express_api_condition_sign'][0]) {
                            case 'equalto':
                                if ($order_total == $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0] || isset(WC()->cart) && WC()->cart->is_empty()) {
                                    
                                } else {
                                    unset($result[$key]);
                                    unset($passed_rules);
                                }
                                break;
                            case 'lessthan':
                                if ($order_total < $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0] || isset(WC()->cart) && WC()->cart->is_empty()) {
                                    
                                } else {
                                    unset($result[$key]);
                                    unset($passed_rules);
                                }
                                break;
                            case 'greaterthan':
                                if ($order_total > $microprocessing_array['woocommerce_paypal_express_api_condition_value'][0] || isset(WC()->cart) && WC()->cart->is_empty()) {
                                    
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
                    foreach ($angelleye_product_ids as $key => $product_id) {
                        $product = wc_get_product($product_id);
                        $this->map_item_with_account[$product_id]['product_id'] = $product_id;
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
                        $this->map_item_with_account[$product_id]['multi_account_id'] = $value->ID;
                        if (isset($microprocessing_array['ppcp_site_owner_commission'][0]) && !empty($microprocessing_array['ppcp_site_owner_commission'][0]) && $microprocessing_array['ppcp_site_owner_commission'][0] > 0) {
                            $this->map_item_with_account[$product_id]['is_commission_enable'] = true;
                            $this->is_commission_enable = true;
                            $this->map_item_with_account[$product_id]['ppcp_site_owner_commission_label'] = !empty($microprocessing_array['ppcp_site_owner_commission_label'][0]) ? $microprocessing_array['ppcp_site_owner_commission_label'][0] : __('Commission', 'paypal-for-woocommerce-multi-account-management');
                            $this->map_item_with_account[$product_id]['ppcp_site_owner_commission'] = $microprocessing_array['ppcp_site_owner_commission'][0];
                        } elseif ($this->global_ec_site_owner_commission > 0) {
                            $this->map_item_with_account[$product_id]['is_commission_enable'] = true;
                            $this->is_commission_enable = true;
                            $this->map_item_with_account[$product_id]['ppcp_site_owner_commission_label'] = !empty($this->global_ec_site_owner_commission_label) ? $this->global_ec_site_owner_commission_label : __('Commission', 'paypal-for-woocommerce-multi-account-management');
                            $this->map_item_with_account[$product_id]['ppcp_site_owner_commission'] = $this->global_ec_site_owner_commission;
                        } else {
                            $this->map_item_with_account[$product_id]['is_commission_enable'] = false;
                        }
                        if ($testmode === 'yes') {
                            if (isset($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_merchant_id'][0])) {
                                $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_sandbox_merchant_id'][0];
                            } elseif (isset($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0])) {
                                $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0];
                            } else {
                                $this->map_item_with_account[$product_id]['merchant_id'] = $this->angelleye_get_merchant_id_for_multi($value->ID, $microprocessing_array);
                            }
                            if ($this->angelleye_is_multi_account_api_set($microprocessing_array)) {
                                //$this->map_item_with_account[$product_id]['is_api_set'] = true;
                            } else {
                                //$this->map_item_with_account[$product_id]['is_api_set'] = false;
                            }
                        } else {
                            if (isset($microprocessing_array['woocommerce_angelleye_ppcp_merchant_id'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_merchant_id'][0])) {
                                $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_merchant_id'][0];
                            } elseif (isset($microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0])) {
                                $this->map_item_with_account[$product_id]['merchant_id'] = $microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0];
                            } else {
                                $this->map_item_with_account[$product_id]['merchant_id'] = $this->angelleye_get_merchant_id_for_multi($value->ID, $microprocessing_array);
                            }
                            if ($this->angelleye_is_multi_account_api_set($microprocessing_array)) {
                                // $this->map_item_with_account[$product_id]['is_api_set'] = true;
                            } else {
                                // $this->map_item_with_account[$product_id]['is_api_set'] = false;
                            }
                        }
                        $cart_loop_pass = $cart_loop_pass + 1;
                    }
                }
                unset($passed_rules);
            }
        }
        return $this->map_item_with_account;
    }

    public function angelleye_get_merchant_ids_from_load_balancer() {
        $found_account = false;
        $found_merchant_id = '';
        $merchant_ids = array();
        if ($this->is_sandbox) {
            $option_key = 'angelleye_multi_ppcp_payment_load_balancer_sandbox';
            $session_key = 'angelleye_sandbox_payment_load_balancer_ppcp_email';
            $session_key_account = 'angelleye_sandbox_payment_load_balancer_ppcp_account';
        } else {
            $option_key = 'angelleye_multi_ppcp_payment_load_balancer';
            $session_key = 'angelleye_payment_load_balancer_ppcp_email';
            $session_key_account = 'angelleye_payment_load_balancer_ppcp_account';
        }
        $found_merchant_id = '';
        $ppcp_accounts = get_option($option_key);
        if (!empty($ppcp_accounts)) {
            foreach ($ppcp_accounts as $key => $account) {
                if (empty($account['is_used'])) {
                    if ($key != 'default' && false === get_post_status($key)) {
                        unset($ppcp_accounts[$key]);
                    } else {
                        $found_merchant_id = $account['multi_account_id'];
                        $account['is_used'] = 'yes';
                        $ppcp_accounts[$key] = $account;
                        update_option($option_key, $ppcp_accounts);
                        $found_account = true;
                        break;
                    }
                }
            }
            if ($found_account == false) {
                foreach ($ppcp_accounts as $key => $account) {
                    $account['is_used'] = '';
                    $ppcp_accounts[$key] = $account;
                }
                foreach ($ppcp_accounts as $key => $account) {
                    if ($key != 'default' && false === get_post_status($key)) {
                        unset($ppcp_accounts[$key]);
                    } else {
                        $found_merchant_id = $account['multi_account_id'];
                        $account['is_used'] = 'yes';
                        $ppcp_accounts[$key] = $account;
                        update_option($option_key, $ppcp_accounts);
                        $found_account = true;
                        break;
                    }
                }
            }
        }
        if ($found_merchant_id != 'default') {
            $merchant_ids[$found_merchant_id] = $found_merchant_id;
        } else {
            $$merchant_id = $this->merchant_id;
            return $$merchant_id;
        }
        return $merchant_ids;
    }

    public function angelleye_get_product_ids() {
        global $post;
        $product_ids = array();
        if (is_product()) {
            $product_ids[] = $post->ID;
        }
        if (is_null(WC()->cart)) {
            return $product_ids;
        }
        if (isset(WC()->cart) && WC()->cart->is_empty()) {
            return $product_ids;
        }
        if (isset(WC()->cart) && sizeof(WC()->cart->get_cart()) > 0) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product_ids[] = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
            }
        }
        return $product_ids;
    }

    public function angelleye_wc_gateway() {
        global $woocommerce;
        $gateways = $woocommerce->payment_gateways->payment_gateways();
        return $gateways['angelleye_ppcp'];
    }

    public function angelleye_ppcp_get_merchant_id($default_merchant_id) {
        $merchant_id_list = $this->angelleye_get_list_merchant_ids($default_merchant_id);
        return $merchant_id_list;
    }
}
