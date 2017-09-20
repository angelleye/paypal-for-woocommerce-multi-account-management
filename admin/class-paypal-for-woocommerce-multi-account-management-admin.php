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
                <button class="add_micro_account_section button"><?php echo __('Add New Microprocessing Account', 'paypal-ipn-for-wordpress-forwarder'); ?></button>
                <br><br>
            </div>
        </div>
        <table id="micro_account_fields" style="display: none">
            <tbody class="angelleye_micro_account_body">
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_sandbox_api_username_microprocessing">Sandbox API User Name</label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span>Sandbox API User Name</span></legend>
                            <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_username][]" id="woocommerce_paypal_express_sandbox_api_username_microprocessing" style="" placeholder="" type="text">
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_sandbox_api_password_microprocessing">Sandbox API Password</label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span>Sandbox API Password</span></legend>
                            <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_password][]" id="woocommerce_paypal_express_sandbox_api_password_microprocessing" style="" placeholder="" type="password">
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_sandbox_api_signature_microprocessing">Sandbox API Signature</label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span>Sandbox API Signature</span></legend>
                            <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_signature][]" id="woocommerce_paypal_express_sandbox_api_signature_microprocessing" style="" placeholder="" type="password">
                        </fieldset>
                    </td>
                </tr>
                <tr style="display: table-row;" valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_api_username_microprocessing">Live API User Name</label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span>Live API User Name</span></legend>
                            <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_username][]" id="woocommerce_paypal_express_api_username_microprocessing" style="" placeholder="" type="text">
                        </fieldset>
                    </td>
                </tr>
                <tr style="display: table-row;" valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_api_password_microprocessing">Live API Password</label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span>Live API Password</span></legend>
                            <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_password][]" id="woocommerce_paypal_express_api_password_microprocessing" style="" placeholder="" type="password">
                        </fieldset>
                    </td>
                </tr>
                <tr style="display: table-row;" valign="top">
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_api_signature_microprocessing">Live API Signature</label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span>Live API Signature</span></legend>
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
                if (count($value_array) < 3) {
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
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_username_microprocessing">Sandbox API User Name</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>Sandbox API User Name</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_username][]" id="woocommerce_paypal_express_sandbox_api_username_microprocessing" value="%1$s" style="" placeholder="" type="text"></fieldset></td></tr>', $value);
                        break;
                    case 'woocommerce_paypal_express_sandbox_api_password':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_password_microprocessing">Sandbox API Password</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>Sandbox API Password</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_password][]" id="woocommerce_paypal_express_sandbox_api_password_microprocessing" value="%1$s" style="" placeholder="" type="password"></fieldset></td></tr>', $value);
                        break;
                    case 'woocommerce_paypal_express_sandbox_api_signature':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_signature_microprocessing">Sandbox API Signature</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>Sandbox API Signature</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_signature][]" id="woocommerce_paypal_express_sandbox_api_signature_microprocessing" value="%1$s" style="" placeholder="" type="password"></fieldset></td></tr>', $value);
                        break;
                    case 'woocommerce_paypal_express_api_username':
                        echo sprintf('<tr style="display: table-row;" valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_username_microprocessing">Live API User Name</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>Live API User Name</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_username][]" id="woocommerce_paypal_express_api_username_microprocessing" value="%1$s" style="" placeholder="" type="text"></fieldset></td></tr>', $value);
                        break;
                    case 'woocommerce_paypal_express_api_password':
                        echo sprintf('<tr style="display: table-row;" valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_password_microprocessing">Live API Password</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>Live API Password</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_password][]" id="woocommerce_paypal_express_api_password_microprocessing" value="%1$s" style="" placeholder="" type="password"></fieldset></td></tr>', $value);
                        break;
                    case 'woocommerce_paypal_express_api_signature':
                        echo sprintf('<tr style="display: table-row;" valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_signature_microprocessing">Live API Signature</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>Live API Signature</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_signature][]" id="woocommerce_paypal_express_api_signature_microprocessing" value="%1$s" style="" placeholder="" type="password"></fieldset></td></tr>', $value);
                        break;
                }
            }
            echo '</tbody></table>';
        }
    }

}
