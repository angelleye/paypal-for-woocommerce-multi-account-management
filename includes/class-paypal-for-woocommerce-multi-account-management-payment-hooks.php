<?php

/**
 * @package    Paypal_For_Woocommerce_Multi_Account_Management
 * @subpackage Paypal_For_Woocommerce_Multi_Account_Management/includes
 * @author     Angell EYE <service@angelleye.com>
 */
class Paypal_For_Woocommerce_Multi_Account_Management_Payment_Hooks {

    private $plugin_name;
    private $version;
    public $testmode;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function setupMetaBoxes()
    {
        add_meta_box('angelleye_admin_order_multi_account_order_summary', __('Multi-Account Summary', 'paypal-for-woocommerce'), array($this, 'multi_account_order_summary'), 'shop_order', 'side', 'default');
    }

    public function multi_account_order_summary($post)
    {
        $order_id = $post->ID;
        $order = wc_get_order($order_id);
        $is_parallel_payment_used = apply_filters('own_angelleye_is_express_checkout_parallel_payment_not_used', true, $order_id);
        if ($is_parallel_payment_used) {
            $load_payment_summary = $order->get_meta('_angelleye_multi_account_ec_payment_summary', true);
            if ($load_payment_summary) {
                echo '<table class="wp-list-table widefat striped nomargin"><thead><tr><th>Account</th><th>Amount</th></tr></thead><tbody>';
                foreach ($load_payment_summary as $payment_summary) {
                    echo '<tr><td>'.$payment_summary['mac_identifier'].'</td><td>'.(get_woocommerce_currency_symbol($order->get_currency())). $payment_summary['amount'].'</td>';
                }
                echo '<tr class="top-border "><td><b>Total</b></td><td>'.(get_woocommerce_currency_symbol($order->get_currency())). $order->get_total().'</td>';
                echo '</tbody></table>';
                $this->angelleye_show_order_payment_metabox();
                return;
            }
        }
        $this->angelleye_hide_order_payment_metabox();
    }


    public function angelleye_hide_order_payment_metabox() {
        ?>
        <style type="text/css">
            #angelleye_admin_order_multi_account_order_summary {
                display: none;
            }
            label[for="angelleye_admin_order_multi_account_order_summary-hide"] {
                display: none;
            }
        </style>
        <?php
    }

    public function angelleye_show_order_payment_metabox() {
        ?>
        <style type="text/css">
            #angelleye_admin_order_multi_account_order_summary {
                display: block;
            }
            #angelleye_admin_order_multi_account_order_summary .inside{
                padding: 0;
                margin:0;
            }
            #angelleye_admin_order_multi_account_order_summary table {
                margin:0;
                border:0;
            }
            #angelleye_admin_order_multi_account_order_summary .wp-list-table thead th {
                font-weight: bold;
            }
            #angelleye_admin_order_multi_account_order_summary .wp-list-table td:last-child {
                text-align: right;
            }
            #angelleye_admin_order_multi_account_order_summary .wp-list-table .top-border td {
                border-top: 1px solid #ccc;
            }
            table.wp-list-table.nomargin th, table.wp-list-table.nomargin td {
                margin: 0px !important;
                padding: 4px !important;
            }
        </style>
        <?php
    }

    
}
