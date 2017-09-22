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
class Paypal_For_Woocommerce_Multi_Account_Management_Admin {

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
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/paypal-for-woocommerce-multi-account-management-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/paypal-for-woocommerce-multi-account-management-admin.js', array('jquery'), $this->version, false);
    }

    public function angelleye_multi_account_ui() {
        ?>
        <div class='angelleye_micro_account_parent'>
            <h3>Microprocessing Accounts</h3>
            <div class="paypal_micro_account_section_add_new">
                <button class="add_micro_account_section button"><?php echo __('Add New Microprocessing Account', 'paypal-for-woocommerce-multi-account-management'); ?></button>
                <br><br>
            </div>
        </div>
        <table id="micro_account_fields" style="display: none">
            <tbody class="angelleye_micro_account_body">
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_rules"><?php echo __('Order total', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <select class="select" name="microprocessing[woocommerce_paypal_express_rules][]">
                                <?php
                                for ($i = 1; $i <= 50; $i++) {
                                    echo sprintf('<option value="%1$s">Less than or equal to %2$s%3$s</option>', $i, get_woocommerce_currency_symbol(get_woocommerce_currency()), $i);
                                }
                                ?>
                            </select>
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_sandbox_api_username_microprocessing"><?php echo __('Sandbox API User Name', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php echo __('Sandbox API User Name', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                            <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_username][]" id="woocommerce_paypal_express_sandbox_api_username_microprocessing" style="" placeholder="" type="text">
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_sandbox_api_password_microprocessing"><?php echo __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php echo __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                            <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_password][]" id="woocommerce_paypal_express_sandbox_api_password_microprocessing" style="" placeholder="" type="password">
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_sandbox_api_signature_microprocessing"><?php echo __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php echo __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                            <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_signature][]" id="woocommerce_paypal_express_sandbox_api_signature_microprocessing" style="" placeholder="" type="password">
                        </fieldset>
                    </td>
                </tr>
                <tr style="display: table-row;" valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_api_username_microprocessing"><?php echo __('Live API User Name', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php echo __('Live API User Name', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                            <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_username][]" id="woocommerce_paypal_express_api_username_microprocessing" style="" placeholder="" type="text">
                        </fieldset>
                    </td>
                </tr>
                <tr style="display: table-row;" valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_api_password_microprocessing"><?php echo __('Live API Password', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php echo __('Live API Password', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                            <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_password][]" id="woocommerce_paypal_express_api_password_microprocessing" style="" placeholder="" type="password">
                        </fieldset>
                    </td>
                </tr>
                <tr style="display: table-row;" valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_api_signature_microprocessing"><?php echo __('Live API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php echo __('Live API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                            <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_signature][]" id="woocommerce_paypal_express_api_signature_microprocessing" style="" placeholder="" type="password">
                        </fieldset>
                    </td>
                </tr>

            </tbody>
        </table>
        <?php
        $woocommerce_paypal_express_settings = get_option('woocommerce_paypal_express_settings');
        if (!empty($woocommerce_paypal_express_settings['microprocessing'])) {
            $this->angelleye_display_multi_account_list($woocommerce_paypal_express_settings['microprocessing']);
        }
    }

    public function angelleye_multi_account_save_settings($settings) {
        $section = array();
        $original = array();
        if (!empty($_POST['microprocessing'])) {
            foreach ($_POST['microprocessing'] as $key_name => $value_array) {
                $count = 0;
                foreach ($value_array as $key => $value) {
                    $section[$count][$key_name] = $value;
                    $count = $count + 1;
                }
            }
            foreach ($section as $section_key => $value_array) {
                if ((empty($value_array['woocommerce_paypal_express_sandbox_api_username']) && empty($value_array['woocommerce_paypal_express_sandbox_api_username']) && empty($value_array['woocommerce_paypal_express_sandbox_api_username'])) && (empty($value_array['woocommerce_paypal_express_sandbox_api_username']) && empty($value_array['woocommerce_paypal_express_sandbox_api_username']) && empty($value_array['woocommerce_paypal_express_sandbox_api_username']))) {
                    unset($original[$section_key]);
                } else {
                    $original[$section_key] = $value_array;
                }
                if (count($value_array) < 4) {
                    unset($original[$section_key]);
                }
            }
            $settings['microprocessing'] = $original;
        }
        return $settings;
    }

    public function angelleye_display_multi_account_list($microprocessing) {
        foreach ($microprocessing as $microprocessing_key => $microprocessing_value) {
            echo '<br/><table class="form-table">
            <tbody class="angelleye_micro_account_body">';
            foreach ($microprocessing_value as $key => $value) {
                switch ($key) {
                    case 'woocommerce_paypal_express_sandbox_api_username':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_username_microprocessing">%1$s</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_username][]" id="woocommerce_paypal_express_sandbox_api_username_microprocessing" value="%3$s" style="" placeholder="" type="text"></fieldset></td></tr>', __('Sandbox API User Name', 'paypal-for-woocommerce-multi-account-management'), __('Sandbox API User Name', 'paypal-for-woocommerce-multi-account-management'), $value);
                        break;
                    case 'woocommerce_paypal_express_sandbox_api_password':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_password][]" id="woocommerce_paypal_express_sandbox_api_password_microprocessing" value="%1$s" style="" placeholder="" type="password"></fieldset></td></tr>', __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'), __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'), $value);
                        break;
                    case 'woocommerce_paypal_express_sandbox_api_signature':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_signature_microprocessing">%1$s</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_signature][]" id="woocommerce_paypal_express_sandbox_api_signature_microprocessing" value="%1$s" style="" placeholder="" type="password"></fieldset></td></tr>', __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'), __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'), $value);
                        break;
                    case 'woocommerce_paypal_express_api_username':
                        echo sprintf('<tr style="display: table-row;" valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_username_microprocessing">%1$s</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_username][]" id="woocommerce_paypal_express_api_username_microprocessing" value="%1$s" style="" placeholder="" type="text"></fieldset></td></tr>', __('Live API User Name', 'paypal-for-woocommerce-multi-account-management'), __('Live API User Name', 'paypal-for-woocommerce-multi-account-management'), $value);
                        break;
                    case 'woocommerce_paypal_express_api_password':
                        echo sprintf('<tr style="display: table-row;" valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_password][]" id="woocommerce_paypal_express_api_password_microprocessing" value="%1$s" style="" placeholder="" type="password"></fieldset></td></tr>', __('Live API Password', 'paypal-for-woocommerce-multi-account-management'), __('Live API Password', 'paypal-for-woocommerce-multi-account-management'), $value);
                        break;
                    case 'woocommerce_paypal_express_api_signature':
                        echo sprintf('<tr style="display: table-row;" valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_signature_microprocessing">%1$s</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_signature][]" id="woocommerce_paypal_express_api_signature_microprocessing" value="%1$s" style="" placeholder="" type="password"></fieldset></td></tr>', __('Live API Signature', 'paypal-for-woocommerce-multi-account-management'), __('Live API Signature', 'paypal-for-woocommerce-multi-account-management'), $value);
                        break;
                    case 'woocommerce_paypal_express_rules':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_rules">%1$s</label></th><td class="forminp"><fieldset><select class="select" name="microprocessing[woocommerce_paypal_express_rules][]">', __('Order total', 'paypal-for-woocommerce-multi-account-management'));
                        for ($i = 1; $i <= 50; $i++) {
                            if ($i == $value) {
                                $selected = 'selected';
                            } else {
                                $selected = '';
                            }
                            echo sprintf('<option value="%1$s" %2$s>Less than or equal to %3$s%4$s</option>', $i, $selected, get_woocommerce_currency_symbol(get_woocommerce_currency()), $i);
                        }
                        echo sprintf('</select></fieldset></td></tr>');
                        break;
                }
            }
            echo '</tbody></table>';
        }
    }

    public function angelleye_paypal_for_woocommerce_multi_account_api_paypal_express($gateways, $request = null) {
        if (is_null(WC()->cart)) {
            return;
        }
        if (WC()->cart->is_empty()) {
            return false;
        }
        $cart_total = $this->angelleye_get_total();
        if ($cart_total > 0) {
            $microprocessing = $gateways->get_option('microprocessing');
            foreach ($microprocessing as $microprocessing_key => $microprocessing_value) {
                if ($cart_total >= $microprocessing_value['woocommerce_paypal_express_rules']) {
                    if ($request == null) {
                        if ($gateways->testmode == true) {
                            if (!empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_username']) && !empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_password']) && !empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_signature'])) {
                                $gateways->api_username = $microprocessing_value['woocommerce_paypal_express_sandbox_api_username'];
                                $gateways->api_password = $microprocessing_value['woocommerce_paypal_express_sandbox_api_password'];
                                $gateways->api_signature = $microprocessing_value['woocommerce_paypal_express_sandbox_api_signature'];
                                WC()->session->set('multi_account_api_username', $gateways->api_username);
                                return;
                            }
                        } else {
                            if (!empty($microprocessing_value['woocommerce_paypal_express_api_username']) && !empty($microprocessing_value['woocommerce_paypal_express_api_password']) && !empty($microprocessing_value['woocommerce_paypal_express_api_signature'])) {
                                $gateways->api_username = $microprocessing_value['woocommerce_paypal_express_api_username'];
                                $gateways->api_password = $microprocessing_value['woocommerce_paypal_express_api_password'];
                                $gateways->api_signature = $microprocessing_value['woocommerce_paypal_express_api_signature'];
                                WC()->session->set('multi_account_api_username', $gateways->api_username);
                                return;
                            }
                        }
                    } else {
                        if ($gateways->testmode == true) {
                            if (!empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_username']) && !empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_password']) && !empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_signature'])) {
                                $request->api_username = $microprocessing_value['woocommerce_paypal_express_sandbox_api_username'];
                                $request->api_password = $microprocessing_value['woocommerce_paypal_express_sandbox_api_password'];
                                $request->api_signature = $microprocessing_value['woocommerce_paypal_express_sandbox_api_signature'];
                                WC()->session->set('multi_account_api_username', $request->api_username);
                                return;
                            }
                        } else {
                            if (!empty($microprocessing_value['woocommerce_paypal_express_api_username']) && !empty($microprocessing_value['woocommerce_paypal_express_api_password']) && !empty($microprocessing_value['woocommerce_paypal_express_api_signature'])) {
                                $request->api_username = $microprocessing_value['woocommerce_paypal_express_api_username'];
                                $request->api_password = $microprocessing_value['woocommerce_paypal_express_api_password'];
                                $request->api_signature = $microprocessing_value['woocommerce_paypal_express_api_signature'];
                                WC()->session->set('multi_account_api_username', $request->api_username);
                                return;
                            }
                        }
                    }
                }
            }
        }
    }

    public function angelleye_get_total() {
        if (wc_prices_include_tax()) {
            $cart_contents_total = WC()->cart->cart_contents_total;
        } else {
            $cart_contents_total = WC()->cart->cart_contents_total + WC()->cart->tax_total;
        }
        return $cart_contents_total;
    }

    public function angelleye_woocommerce_checkout_update_order_meta($order_id, $data) {
        $multi_account_api_username = WC()->session->get('multi_account_api_username');
        if( !empty($multi_account_api_username) ) {
            update_post_meta($order_id, '_multi_account_api_username', $multi_account_api_username);
        }
    }

}
