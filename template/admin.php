<?php
/**
 * PayPal for WooCommerce - Settings
 */
?>
<?php
$active_tab = isset($_GET['tab']) ? wc_clean($_GET['tab']) : 'general_settings';
$gateway = isset($_GET['gateway']) ? wc_clean($_GET['gateway']) : 'paypal_for_wooCommerce_for_multi_account_management';
?>
<div class="wrap">
    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
    
    <?php if ($active_tab == 'general_settings') { ?>
        <h2 class="nav-tab-wrapper">
            <?php do_action('angelleye_paypal_for_woocommerce_general_settings_tab'); ?>
        </h2>
        <?php
            do_action('angelleye_paypal_for_woocommerce_general_settings_tab_content');
        }
        ?>
    
</div>