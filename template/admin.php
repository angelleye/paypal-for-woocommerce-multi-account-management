<?php
/**
 * PayPal for WooCommerce - Settings
 */
?>
<?php
$active_tab = isset($_GET['tab']) ? wc_clean($_GET['tab']) : 'general_settings';
$gateway = isset($_GET['gateway']) ? wc_clean($_GET['gateway']) : 'paypal_payment_gateway_products';
?>
<div class="wrap">
    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo $this->plugin_slug; ?>&tab=general_settings&gateway=paypal_payment_gateway_products" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'nav-tab-active' : ''; ?>"><?php echo __('General', 'paypal-for-woocommerce'); ?></a>
    </h2>
    <?php if ($active_tab == 'general_settings') { ?>
        <h2 class="nav-tab-wrapper">
            <?php do_action('angelleye_paypal_for_woocommerce_general_settings_tab'); ?>
        </h2>
        <?php
            do_action('angelleye_paypal_for_woocommerce_general_settings_tab_content');
       
        }
        ?>
    
</div>