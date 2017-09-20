<?php

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Paypal_For_Woocommerce_Multi_Account_Management
 * @subpackage Paypal_For_Woocommerce_Multi_Account_Management/includes
 * @author     Angell EYE <service@angelleye.com>
 */
class Paypal_For_Woocommerce_Multi_Account_Management_i18n {

    /**
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
                'paypal-for-woocommerce-multi-account-management', false, dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

}
