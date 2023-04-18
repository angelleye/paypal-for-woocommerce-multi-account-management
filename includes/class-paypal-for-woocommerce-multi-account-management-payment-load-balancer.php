<?php

/**
 * @package    Paypal_For_Woocommerce_Multi_Account_Management
 * @subpackage Paypal_For_Woocommerce_Multi_Account_Management/includes
 * @author     Angell EYE <service@angelleye.com>
 */
class Paypal_For_Woocommerce_Multi_Account_Management_Payment_Load_Balancer {

    private $plugin_name;
    private $version;
    public $testmode;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function angelleye_synce_express_checkout_account() {
        if (class_exists('WC_Gateway_PayPal_Express_AngellEYE')) {
            $paypal_express = angelleye_wc_gateway('paypal_express');
            if (!empty($paypal_express)) {
                $testmode = angelleye_wc_gateway('paypal_express')->get_option('testmode', '');
            } else {
                $testmode = 'yes';
            }
        } else {
            $testmode = 'yes';
        }
        if ($testmode == 'yes') {
            $environment = 'on';
            $option_key = 'angelleye_multi_ec_payment_load_balancer_sandbox';
            $cache_key = 'angelleye_multi_ec_payment_load_balancer_synce_sandbox';
        } else {
            $environment = '';
            $option_key = 'angelleye_multi_ec_payment_load_balancer';
            $cache_key = 'angelleye_multi_ec_payment_load_balancer_synce';
        }
        $express_checkout_accounts_data = get_transient($cache_key);
        if (!empty($express_checkout_accounts_data)) {
            $express_checkout_accounts = get_option($option_key);
            return $express_checkout_accounts;
        }
        $old_express_checkout_accounts = get_option($option_key);
        if (empty($old_express_checkout_accounts)) {
            $old_express_checkout_accounts = array();
        }
        $express_checkout_accounts = array();
        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'microprocessing',
            'order' => 'ASC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'woocommerce_paypal_express_enable',
                    'value' => 'on',
                    'compare' => '='
                ),
                array(
                    'key' => 'woocommerce_paypal_express_testmode',
                    'value' => $environment,
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        );
        $query = new WP_Query($args);

        if (isset($old_express_checkout_accounts['default'])) {
            $express_checkout_accounts['default'] = $old_express_checkout_accounts['default'];
        } else {
            $express_checkout_accounts['default'] = array('multi_account_id' => 'default', 'is_used' => '', 'is_api_set' => true, 'email' => 'default');
        }
        if (!empty($query->posts) && count($query->posts) > 0) {
            foreach ($query->posts as $key => $post_id) {
                if (isset($old_express_checkout_accounts[$post_id])) {
                    $express_checkout_accounts[$post_id] = $old_express_checkout_accounts[$post_id];
                } else {
                    $express_checkout_accounts[$post_id] = array('multi_account_id' => $post_id, 'is_used' => '', 'is_api_set' => false, 'email' => '');
                }
                $microprocessing_array = get_post_meta($post_id);
                $bool = $this->angelleye_is_multi_account_api_set($microprocessing_array, $environment);
                if ($environment) {
                    $multi_account_identifier = isset($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0]) ? $microprocessing_array['woocommerce_paypal_express_sandbox_email'][0] : null;
                    if (empty($multi_account_identifier)) {
                        $multi_account_identifier = isset($microprocessing_array['woocommerce_paypal_express_sandbox_api_username'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_sandbox_api_username'][0]) ? $microprocessing_array['woocommerce_paypal_express_sandbox_api_username'][0] : null;
                    }
                } else {
                    $multi_account_identifier = isset($microprocessing_array['woocommerce_paypal_express_email'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_email'][0]) ? $microprocessing_array['woocommerce_paypal_express_email'][0] : null;
                    if (empty($multi_account_identifier)) {
                        $multi_account_identifier = isset($microprocessing_array['woocommerce_paypal_express_api_username'][0]) && !empty($microprocessing_array['woocommerce_paypal_express_api_username'][0]) ? $microprocessing_array['woocommerce_paypal_express_api_username'][0] : null;
                    }
                }

                $express_checkout_accounts[$post_id]['multi_account_identifier'] = $multi_account_identifier;

                if ($bool) {
                    if ($environment) {
                        $email = isset($microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0]) ? $microprocessing_array['woocommerce_paypal_express_sandbox_merchant_id'][0] : '';
                    } else {
                        $email = isset($microprocessing_array['woocommerce_paypal_express_merchant_id'][0]) ? $microprocessing_array['woocommerce_paypal_express_merchant_id'][0] : '';
                    }
                } else {
                    if ($environment) {
                        $email = isset($microprocessing_array['woocommerce_paypal_express_sandbox_email'][0]) ? $microprocessing_array['woocommerce_paypal_express_sandbox_email'][0] : '';
                    } else {
                        $email = isset($microprocessing_array['woocommerce_paypal_express_email'][0]) ? $microprocessing_array['woocommerce_paypal_express_email'][0] : '';
                    }
                }
                $express_checkout_accounts[$post_id]['is_api_set'] = $bool;
                if (apply_filters('angelleye_is_account_ready_to_paid', true, $email) === true) {
                    $express_checkout_accounts[$post_id]['email'] = $email;
                } else {
                    update_post_meta($post_id, 'woocommerce_paypal_express_enable', '');
                    unset($express_checkout_accounts[$post_id]);
                }
            }
        }
        update_option($option_key, $express_checkout_accounts);
        set_transient($cache_key, $express_checkout_accounts, 24 * HOUR_IN_SECONDS);
    }

    public function angelleye_is_multi_account_api_set($microprocessing_array, $environment) {
        if ($environment == 'on') {
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

    public function angelleye_synce_payflow_account() {
        if (class_exists('WC_Gateway_PayPal_Pro_PayFlow_AngellEYE')) {
            $paypal_pro_payflow = angelleye_wc_gateway('paypal_pro_payflow');
            if (!empty($paypal_pro_payflow)) {
                $testmode = angelleye_wc_gateway('paypal_pro_payflow')->get_option('testmode', '');
            } else {
                $testmode = 'yes';
            }
        } else {
            $testmode = 'yes';
        }
        if ($testmode == 'yes') {
            $environment = 'on';
            $option_key = 'angelleye_multi_payflow_payment_load_balancer_sandbox';
            $cache_key = 'angelleye_multi_payflow_payment_load_balancer_synce_sandbox';
        } else {
            $environment = '';
            $option_key = 'angelleye_multi_payflow_payment_load_balancer';
            $cache_key = 'angelleye_multi_payflow_payment_load_balancer_synce';
        }
        $payflow_accounts_data = get_transient($cache_key);
        if (!empty($payflow_accounts_data)) {
            $payflow_accounts = get_option($option_key);
            return $payflow_accounts;
        }
        $old_payflow_accounts = get_option($option_key);
        if (empty($old_payflow_accounts)) {
            $old_payflow_accounts = array();
        }
        $payflow_accounts = array();
        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'microprocessing',
            'order' => 'ASC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'woocommerce_paypal_pro_payflow_enable',
                    'value' => 'on',
                    'compare' => '='
                ),
                array(
                    'key' => 'woocommerce_paypal_pro_payflow_testmode',
                    'value' => $environment,
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        );
        $query = new WP_Query($args);
        if (isset($old_payflow_accounts['default'])) {
            $payflow_accounts['default'] = $old_payflow_accounts['default'];
        } else {
            $payflow_accounts['default'] = array('multi_account_id' => 'default', 'is_used' => '');
        }
        if (!empty($query->posts) && count($query->posts) > 0) {
            foreach ($query->posts as $key => $post_id) {
                if (isset($old_payflow_accounts[$post_id])) {
                    $payflow_accounts[$post_id] = $old_payflow_accounts[$post_id];
                } else {
                    $payflow_accounts[$post_id] = array('multi_account_id' => $post_id, 'is_used' => '');
                }
            }
        }
        update_option($option_key, $payflow_accounts);
        set_transient($cache_key, $payflow_accounts, 6 * HOUR_IN_SECONDS);
    }

    public function angelleye_synce_ppcp_account() {
        if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
            include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
        }
        $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
        $this->is_sandbox = 'yes' === $this->settings->get('testmode', 'no');
        if ($this->is_sandbox) {
            $environment = 'on';
            $option_key = 'angelleye_multi_ppcp_payment_load_balancer_sandbox';
            $cache_key = 'angelleye_multi_ppcp_payment_load_balancer_synce_sandbox';
        } else {
            $environment = '';
            $option_key = 'angelleye_multi_ppcp_payment_load_balancer';
            $cache_key = 'angelleye_multi_ppcp_payment_load_balancer_synce';
        }
        $ppcp_accounts_data = get_transient($cache_key);
        if (!empty($ppcp_accounts_data)) {
            $ppcp_accounts = get_option($option_key);
            return $ppcp_accounts;
        }
        $old_ppcp_accounts = get_option($option_key);
        if (empty($old_ppcp_accounts)) {
            $old_ppcp_accounts = array();
        }
        $ppcp_accounts = array();
        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'microprocessing',
            'order' => 'ASC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'woocommerce_angelleye_ppcp_enable',
                    'value' => 'on',
                    'compare' => '='
                ),
                array(
                    'key' => 'woocommerce_angelleye_ppcp_testmode',
                    'value' => $environment,
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        );
        $query = new WP_Query($args);

        if (isset($old_ppcp_accounts['default'])) {
            $ppcp_accounts['default'] = $old_ppcp_accounts['default'];
        } else {
            $ppcp_accounts['default'] = array('multi_account_id' => 'default', 'is_used' => '', 'is_api_set' => true, 'email' => 'default');
        }
        if (!empty($query->posts) && count($query->posts) > 0) {
            foreach ($query->posts as $key => $post_id) {
                if (isset($old_ppcp_accounts[$post_id])) {
                    $ppcp_accounts[$post_id] = $old_ppcp_accounts[$post_id];
                } else {
                    $ppcp_accounts[$post_id] = array('multi_account_id' => $post_id, 'is_used' => '', 'is_api_set' => false, 'email' => '');
                }
                $microprocessing_array = get_post_meta($post_id);
                $bool = $this->angelleye_ppcp_is_multi_account_api_set($microprocessing_array, $environment);

                if ($environment) {
                    $email = isset($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0]) ? $microprocessing_array['woocommerce_angelleye_ppcp_sandbox_email_address'][0] : '';
                } else {
                    $email = isset($microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0]) ? $microprocessing_array['woocommerce_angelleye_ppcp_email_address'][0] : '';
                }

                $ppcp_accounts[$post_id]['is_api_set'] = $bool;
                if (apply_filters('angelleye_ppcp_is_account_ready_to_paid', true, $email) === true) {
                    $ppcp_accounts[$post_id]['email'] = $email;
                } else {
                    update_post_meta($post_id, 'woocommerce_angelleye_ppcp_enable', '');
                    unset($ppcp_accounts[$post_id]);
                }
            }
        }
        update_option($option_key, $ppcp_accounts);
        set_transient($cache_key, $ppcp_accounts, 24 * HOUR_IN_SECONDS);
    }

    public function angelleye_ppcp_is_multi_account_api_set($microprocessing_array, $environment) {
        if ($environment == 'on') {
            if (!empty($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_secret'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_sandbox_client_id'][0])) {
                return true;
            }
        } else {
            if (!empty($microprocessing_array['woocommerce_angelleye_ppcp_client_id'][0]) && !empty($microprocessing_array['woocommerce_angelleye_ppcp_secret'][0])) {
                return true;
            }
        }
        return false;
    }

}
