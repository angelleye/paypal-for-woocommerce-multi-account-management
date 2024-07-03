<?php

class Paypal_For_Woocommerce_Multi_Account_Management_List_Data extends WP_List_Table {

    var $account_data = array();

    function __construct() {
        global $status, $page;
        parent::__construct(array(
            'singular' => 'account',
            'plural' => 'accounts',
            'ajax' => false
        ));
    }

    public function inline_edit() {
        global $mode;

        $screen = $this->screen;

        $post = get_default_post_to_edit($screen->post_type);
        $post_type_object = get_post_type_object($screen->post_type);

        $taxonomy_names = get_object_taxonomies($screen->post_type);
        $hierarchical_taxonomies = array();
        $flat_taxonomies = array();

        foreach ($taxonomy_names as $taxonomy_name) {
            $taxonomy = get_taxonomy($taxonomy_name);

            $show_in_quick_edit = $taxonomy->show_in_quick_edit;

            /**
             * Filters whether the current taxonomy should be shown in the Quick Edit panel.
             *
             * @since 4.2.0
             *
             * @param bool   $show_in_quick_edit Whether to show the current taxonomy in Quick Edit.
             * @param string $taxonomy_name      Taxonomy name.
             * @param string $post_type          Post type of current Quick Edit post.
             */
            if (!apply_filters('quick_edit_show_taxonomy', $show_in_quick_edit, $taxonomy_name, $screen->post_type)) {
                continue;
            }

            if ($taxonomy->hierarchical) {
                $hierarchical_taxonomies[] = $taxonomy;
            } else {
                $flat_taxonomies[] = $taxonomy;
            }
        }

        $m = ( isset($mode) && 'excerpt' === $mode ) ? 'excerpt' : 'list';
        $can_publish = true;
        $core_columns = array(
            'cb' => true,
            'date' => true,
            'title' => true,
            'categories' => true,
            'tags' => true,
            'comments' => true,
            'author' => true,
        );
        ?>

        <form method="get">
            <table style="display: none"><tbody id="inlineedit">
                    <?php
                    $hclass = count($hierarchical_taxonomies) ? 'post' : 'page';
                    $inline_edit_classes = "inline-edit-row inline-edit-row-$hclass";
                    $bulk_edit_classes = "bulk-edit-row bulk-edit-row-$hclass bulk-edit-{$screen->post_type}";
                    $quick_edit_classes = "quick-edit-row quick-edit-row-$hclass inline-edit-{$screen->post_type}";

                    $bulk = 0;

                    while ($bulk < 2) :
                        $classes = $inline_edit_classes . ' ';
                        $classes .= $bulk ? $bulk_edit_classes : $quick_edit_classes;
                        ?>
                        <tr id="<?php echo $bulk ? 'bulk-edit' : 'inline-edit'; ?>" class="<?php echo $classes; ?>" style="display: none">
                            <td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">
                                <div class="inline-edit-wrapper" role="region" aria-labelledby="<?php echo $bulk ? 'bulk' : 'quick'; ?>-edit-legend">
                                    <fieldset class="inline-edit-col-left">
                                        <legend class="inline-edit-legend" id="<?php echo $bulk ? 'bulk' : 'quick'; ?>-edit-legend"><?php echo $bulk ? __('Bulk Edit') : __('Quick Edit'); ?></legend>
                                        <div class="inline-edit-col">

                                            <?php if (post_type_supports($screen->post_type, 'title')) : ?>

                                                <?php if ($bulk) : ?>

                                                    <div id="bulk-title-div">
                                                        <div id="bulk-titles"></div>
                                                    </div>

                                                <?php else : // $bulk ?>

                                                    <label>
                                                        <span class="title"><?php _e('Title'); ?></span>
                                                        <span class="input-text-wrap"><input type="text" name="post_title" class="ptitle" value="" /></span>
                                                    </label>

                                                    <?php if (is_post_type_viewable($screen->post_type)) : ?>

                                                        <label>
                                                            <span class="title"><?php _e('Slug'); ?></span>
                                                            <span class="input-text-wrap"><input type="text" name="post_name" value="" autocomplete="off" spellcheck="false" /></span>
                                                        </label>

                                                    <?php endif; // is_post_type_viewable() ?>

                                                <?php endif; // $bulk ?>

                                            <?php endif; // post_type_supports( ... 'title' ) ?>

                                            <?php if (!$bulk) : ?>
                                                <fieldset class="inline-edit-date">
                                                    <legend><span class="title"><?php _e('Date'); ?></span></legend>
                                                    <?php touch_time(1, 1, 0, 1); ?>
                                                </fieldset>
                                                <br class="clear" />
                                            <?php endif; // $bulk ?>

                                            <?php
                                            if (post_type_supports($screen->post_type, 'author')) {
                                                $authors_dropdown = '';

                                                if (current_user_can($post_type_object->cap->edit_others_posts)) {
                                                    $dropdown_name = 'post_author';
                                                    $dropdown_class = 'authors';
                                                    if (wp_is_large_user_count()) {
                                                        $authors_dropdown = sprintf('<select name="%s" class="%s hidden"></select>', esc_attr($dropdown_name), esc_attr($dropdown_class));
                                                    } else {
                                                        $users_opt = array(
                                                            'hide_if_only_one_author' => false,
                                                            'capability' => array($post_type_object->cap->edit_posts),
                                                            'name' => $dropdown_name,
                                                            'class' => $dropdown_class,
                                                            'multi' => 1,
                                                            'echo' => 0,
                                                            'show' => 'display_name_with_login',
                                                        );

                                                        if ($bulk) {
                                                            $users_opt['show_option_none'] = __('&mdash; No Change &mdash;');
                                                        }

                                                        /**
                                                         * Filters the arguments used to generate the Quick Edit authors drop-down.
                                                         *
                                                         * @since 5.6.0
                                                         *
                                                         * @see wp_dropdown_users()
                                                         *
                                                         * @param array $users_opt An array of arguments passed to wp_dropdown_users().
                                                         * @param bool $bulk A flag to denote if it's a bulk action.
                                                         */
                                                        $users_opt = apply_filters('quick_edit_dropdown_authors_args', $users_opt, $bulk);

                                                        $authors = wp_dropdown_users($users_opt);

                                                        if ($authors) {
                                                            $authors_dropdown = '<label class="inline-edit-author">';
                                                            $authors_dropdown .= '<span class="title">' . __('Author') . '</span>';
                                                            $authors_dropdown .= $authors;
                                                            $authors_dropdown .= '</label>';
                                                        }
                                                    }
                                                } // current_user_can( 'edit_others_posts' )

                                                if (!$bulk) {
                                                    echo $authors_dropdown;
                                                }
                                            } // post_type_supports( ... 'author' )
                                            ?>

                                            <?php if (!$bulk && $can_publish) : ?>

                                                <div class="inline-edit-group wp-clearfix">
                                                    <label class="alignleft">
                                                        <span class="title"><?php _e('Password'); ?></span>
                                                        <span class="input-text-wrap"><input type="text" name="post_password" class="inline-edit-password-input" value="" /></span>
                                                    </label>

                                                    <span class="alignleft inline-edit-or">
                                                        <?php
                                                        /* translators: Between password field and private checkbox on post quick edit interface. */
                                                        _e('&ndash;OR&ndash;');
                                                        ?>
                                                    </span>
                                                    <label class="alignleft inline-edit-private">
                                                        <input type="checkbox" name="keep_private" value="private" />
                                                        <span class="checkbox-title"><?php _e('Private'); ?></span>
                                                    </label>
                                                </div>

                                            <?php endif; ?>

                                        </div>
                                    </fieldset>

                                    <?php if (count($hierarchical_taxonomies) && !$bulk) : ?>

                                        <fieldset class="inline-edit-col-center inline-edit-categories">
                                            <div class="inline-edit-col">

                                                <?php foreach ($hierarchical_taxonomies as $taxonomy) : ?>

                                                    <span class="title inline-edit-categories-label"><?php echo esc_html($taxonomy->labels->name); ?></span>
                                                    <input type="hidden" name="<?php echo ( 'category' === $taxonomy->name ) ? 'post_category[]' : 'tax_input[' . esc_attr($taxonomy->name) . '][]'; ?>" value="0" />
                                                    <ul class="cat-checklist <?php echo esc_attr($taxonomy->name); ?>-checklist">
                                                        <?php wp_terms_checklist(0, array('taxonomy' => $taxonomy->name)); ?>
                                                    </ul>

                                                <?php endforeach; // $hierarchical_taxonomies as $taxonomy ?>

                                            </div>
                                        </fieldset>

                                    <?php endif; // count( $hierarchical_taxonomies ) && ! $bulk ?>

                                    <fieldset class="inline-edit-col-right">
                                        <div class="inline-edit-col">

                                            <?php
                                            if (post_type_supports($screen->post_type, 'author') && $bulk) {
                                                echo $authors_dropdown;
                                            }
                                            ?>

                                            <?php if (post_type_supports($screen->post_type, 'page-attributes')) : ?>

                                                <?php if ($post_type_object->hierarchical) : ?>

                                                    <label>
                                                        <span class="title"><?php _e('Parent'); ?></span>
                                                        <?php
                                                        $dropdown_args = array(
                                                            'post_type' => $post_type_object->name,
                                                            'selected' => $post->post_parent,
                                                            'name' => 'post_parent',
                                                            'show_option_none' => __('Main Page (no parent)'),
                                                            'option_none_value' => 0,
                                                            'sort_column' => 'menu_order, post_title',
                                                        );

                                                        if ($bulk) {
                                                            $dropdown_args['show_option_no_change'] = __('&mdash; No Change &mdash;');
                                                        }

                                                        /**
                                                         * Filters the arguments used to generate the Quick Edit page-parent drop-down.
                                                         *
                                                         * @since 2.7.0
                                                         * @since 5.6.0 The `$bulk` parameter was added.
                                                         *
                                                         * @see wp_dropdown_pages()
                                                         *
                                                         * @param array $dropdown_args An array of arguments passed to wp_dropdown_pages().
                                                         * @param bool  $bulk          A flag to denote if it's a bulk action.
                                                         */
                                                        $dropdown_args = apply_filters('quick_edit_dropdown_pages_args', $dropdown_args, $bulk);

                                                        wp_dropdown_pages($dropdown_args);
                                                        ?>
                                                    </label>

                                                <?php endif; // hierarchical ?>

                                                <?php if (!$bulk) : ?>

                                                    <label>
                                                        <span class="title"><?php _e('Order'); ?></span>
                                                        <span class="input-text-wrap"><input type="text" name="menu_order" class="inline-edit-menu-order-input" value="<?php echo $post->menu_order; ?>" /></span>
                                                    </label>

                                                <?php endif; // ! $bulk ?>

                                            <?php endif; // post_type_supports( ... 'page-attributes' ) ?>

                                            <?php if (0 < count(get_page_templates(null, $screen->post_type))) : ?>

                                                <label>
                                                    <span class="title"><?php _e('Template'); ?></span>
                                                    <select name="page_template">
                                                        <?php if ($bulk) : ?>
                                                            <option value="-1"><?php _e('&mdash; No Change &mdash;'); ?></option>
                                                        <?php endif; // $bulk ?>
                                                        <?php
                                                        /** This filter is documented in wp-admin/includes/meta-boxes.php */
                                                        $default_title = apply_filters('default_page_template_title', __('Default template'), 'quick-edit');
                                                        ?>
                                                        <option value="default"><?php echo esc_html($default_title); ?></option>
                                                        <?php page_template_dropdown('', $screen->post_type); ?>
                                                    </select>
                                                </label>

                                            <?php endif; ?>

                                            <?php if (count($flat_taxonomies) && !$bulk) : ?>

                                                <?php foreach ($flat_taxonomies as $taxonomy) : ?>

                                                    <?php if (current_user_can($taxonomy->cap->assign_terms)) : ?>
                                                        <?php $taxonomy_name = esc_attr($taxonomy->name); ?>
                                                        <div class="inline-edit-tags-wrap">
                                                            <label class="inline-edit-tags">
                                                                <span class="title"><?php echo esc_html($taxonomy->labels->name); ?></span>
                                                                <textarea data-wp-taxonomy="<?php echo $taxonomy_name; ?>" cols="22" rows="1" name="tax_input[<?php echo esc_attr($taxonomy->name); ?>]" class="tax_input_<?php echo esc_attr($taxonomy->name); ?>" aria-describedby="inline-edit-<?php echo esc_attr($taxonomy->name); ?>-desc"></textarea>
                                                            </label>
                                                            <p class="howto" id="inline-edit-<?php echo esc_attr($taxonomy->name); ?>-desc"><?php echo esc_html($taxonomy->labels->separate_items_with_commas); ?></p>
                                                        </div>
                                                    <?php endif; // current_user_can( 'assign_terms' ) ?>

                                                <?php endforeach; // $flat_taxonomies as $taxonomy ?>

                                            <?php endif; // count( $flat_taxonomies ) && ! $bulk ?>

                                            <?php if (post_type_supports($screen->post_type, 'comments') || post_type_supports($screen->post_type, 'trackbacks')) : ?>

                                                <?php if ($bulk) : ?>

                                                    <div class="inline-edit-group wp-clearfix">

                                                        <?php if (post_type_supports($screen->post_type, 'comments')) : ?>

                                                            <label class="alignleft">
                                                                <span class="title"><?php _e('Comments'); ?></span>
                                                                <select name="comment_status">
                                                                    <option value=""><?php _e('&mdash; No Change &mdash;'); ?></option>
                                                                    <option value="open"><?php _e('Allow'); ?></option>
                                                                    <option value="closed"><?php _e('Do not allow'); ?></option>
                                                                </select>
                                                            </label>

                                                        <?php endif; ?>

                                                        <?php if (post_type_supports($screen->post_type, 'trackbacks')) : ?>

                                                            <label class="alignright">
                                                                <span class="title"><?php _e('Pings'); ?></span>
                                                                <select name="ping_status">
                                                                    <option value=""><?php _e('&mdash; No Change &mdash;'); ?></option>
                                                                    <option value="open"><?php _e('Allow'); ?></option>
                                                                    <option value="closed"><?php _e('Do not allow'); ?></option>
                                                                </select>
                                                            </label>

                                                        <?php endif; ?>

                                                    </div>

                                                <?php else : // $bulk ?>

                                                    <div class="inline-edit-group wp-clearfix">

                                                        <?php if (post_type_supports($screen->post_type, 'comments')) : ?>

                                                            <label class="alignleft">
                                                                <input type="checkbox" name="comment_status" value="open" />
                                                                <span class="checkbox-title"><?php _e('Allow Comments'); ?></span>
                                                            </label>

                                                        <?php endif; ?>

                                                        <?php if (post_type_supports($screen->post_type, 'trackbacks')) : ?>

                                                            <label class="alignleft">
                                                                <input type="checkbox" name="ping_status" value="open" />
                                                                <span class="checkbox-title"><?php _e('Allow Pings'); ?></span>
                                                            </label>

                                                        <?php endif; ?>

                                                    </div>

                                                <?php endif; // $bulk ?>

                                            <?php endif; // post_type_supports( ... comments or pings ) ?>

                                            <div class="inline-edit-group wp-clearfix">

                                                <label class="inline-edit-status alignleft">
                                                    <span class="title"><?php _e('Status'); ?></span>
                                                    <select name="_status">
                                                        <?php if ($bulk) : ?>
                                                            <option value="-1"><?php _e('&mdash; No Change &mdash;'); ?></option>
                                                        <?php endif; // $bulk ?>

                                                        <?php if ($can_publish) : // Contributors only get "Unpublished" and "Pending Review". ?>
                                                            <option value="publish"><?php _e('Published'); ?></option>
                                                            <option value="future"><?php _e('Scheduled'); ?></option>
                                                            <?php if ($bulk) : ?>
                                                                <option value="private"><?php _e('Private'); ?></option>
                                                            <?php endif; // $bulk ?>
                                                        <?php endif; ?>

                                                        <option value="pending"><?php _e('Pending Review'); ?></option>
                                                        <option value="draft"><?php _e('Draft'); ?></option>
                                                    </select>
                                                </label>

                                                <?php if ('post' === $screen->post_type && $can_publish && current_user_can($post_type_object->cap->edit_others_posts)) : ?>

                                                    <?php if ($bulk) : ?>

                                                        <label class="alignright">
                                                            <span class="title"><?php _e('Sticky'); ?></span>
                                                            <select name="sticky">
                                                                <option value="-1"><?php _e('&mdash; No Change &mdash;'); ?></option>
                                                                <option value="sticky"><?php _e('Sticky'); ?></option>
                                                                <option value="unsticky"><?php _e('Not Sticky'); ?></option>
                                                            </select>
                                                        </label>

                                                    <?php else : // $bulk ?>

                                                        <label class="alignleft">
                                                            <input type="checkbox" name="sticky" value="sticky" />
                                                            <span class="checkbox-title"><?php _e('Make this post sticky'); ?></span>
                                                        </label>

                                                    <?php endif; // $bulk ?>

                                                <?php endif; // 'post' && $can_publish && current_user_can( 'edit_others_posts' ) ?>

                                            </div>

                                            <?php if ($bulk && current_theme_supports('post-formats') && post_type_supports($screen->post_type, 'post-formats')) : ?>
                                                <?php $post_formats = get_theme_support('post-formats'); ?>

                                                <label class="alignleft">
                                                    <span class="title"><?php _ex('Format', 'post format'); ?></span>
                                                    <select name="post_format">
                                                        <option value="-1"><?php _e('&mdash; No Change &mdash;'); ?></option>
                                                        <option value="0"><?php echo get_post_format_string('standard'); ?></option>
                                                        <?php if (is_array($post_formats[0])) : ?>
                                                            <?php foreach ($post_formats[0] as $format) : ?>
                                                                <option value="<?php echo esc_attr($format); ?>"><?php echo esc_html(get_post_format_string($format)); ?></option>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </select>
                                                </label>

                                            <?php endif; ?>

                                        </div>
                                    </fieldset>

                                    <?php
                                    list( $columns ) = $this->get_column_info();

                                    foreach ($columns as $column_name => $column_display_name) {
                                        if (isset($core_columns[$column_name])) {
                                            continue;
                                        }

                                        if ($bulk) {

                                            /**
                                             * Fires once for each column in Bulk Edit mode.
                                             *
                                             * @since 2.7.0
                                             *
                                             * @param string $column_name Name of the column to edit.
                                             * @param string $post_type   The post type slug.
                                             */
                                            do_action('bulk_edit_custom_box', $column_name, $screen->post_type);
                                        } else {

                                            /**
                                             * Fires once for each column in Quick Edit mode.
                                             *
                                             * @since 2.7.0
                                             *
                                             * @param string $column_name Name of the column to edit.
                                             * @param string $post_type   The post type slug, or current screen name if this is a taxonomy list table.
                                             * @param string $taxonomy    The taxonomy name, if any.
                                             */
                                            do_action('quick_edit_custom_box', $column_name, $screen->post_type, '');
                                        }
                                    }
                                    ?>

                                    <div class="submit inline-edit-save">
                                        <?php if (!$bulk) : ?>
                                            <?php wp_nonce_field('inlineeditnonce', '_inline_edit', false); ?>
                                            <button type="button" class="button button-primary save"><?php _e('Update'); ?></button>
                                        <?php else : ?>
                                            <?php submit_button(__('Update'), 'primary', 'bulk_edit', false); ?>
                                        <?php endif; ?>

                                        <button type="button" class="button cancel"><?php _e('Cancel'); ?></button>

                                        <?php if (!$bulk) : ?>
                                            <span class="spinner"></span>
                                        <?php endif; ?>

                                        <input type="hidden" name="post_view" value="<?php echo esc_attr($m); ?>" />
                                        <input type="hidden" name="screen" value="<?php echo esc_attr($screen->id); ?>" />
                                        <?php if (!$bulk && !post_type_supports($screen->post_type, 'author')) : ?>
                                            <input type="hidden" name="post_author" value="<?php echo esc_attr($post->post_author); ?>" />
                                        <?php endif; ?>

                                        <?php
                                        wp_admin_notice(
                                                '<p class="error"></p>',
                                                array(
                                                    'type' => 'error',
                                                    'additional_classes' => array('notice-alt', 'inline', 'hidden'),
                                                    'paragraph_wrap' => false,
                                                )
                                        );
                                        ?>
                                    </div>
                                </div> <!-- end of .inline-edit-wrapper -->

                            </td></tr>

                        <?php
                        ++$bulk;
                    endwhile;
                    ?>
                </tbody></table>
        </form>
        <?php
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'api_user_name':
            case 'mode':
                return $item[$column_name];
            case 'trigger_condition':
                $condition_field = get_post_meta($item['ID'], 'woocommerce_paypal_express_api_condition_field', true);
                $condition_sign = get_post_meta($item['ID'], 'woocommerce_paypal_express_api_condition_sign', true);
                $condition_value = get_post_meta($item['ID'], 'woocommerce_paypal_express_api_condition_value', true);
                $condition_role = get_post_meta($item['ID'], 'woocommerce_paypal_express_api_user_role', true);
                $condition_user = get_post_meta($item['ID'], 'woocommerce_paypal_express_api_user', true);

                $product_ids = get_post_meta($item['ID'], 'woocommerce_paypal_express_api_product_ids', true);

                $role = '';
                if ($condition_role) {
                    if ($condition_role != 'all') {
                        $role = '<p class="description">' . sprintf('When role is %s', $condition_role) . '</p>';
                    }
                }
                $user_info = '';
                if ($condition_user) {
                    if ($condition_user != 'all') {
                        $user = get_user_by('id', $condition_user);
                        if (isset($user->ID) && !empty($user->ID)) {
                            $user_string = sprintf(
                                    esc_html__('%1$s (#%2$s   %3$s)', 'woocommerce'),
                                    $user->display_name,
                                    absint($user->ID),
                                    $user->user_email
                            );
                            $user_info = '<p class="description">' . sprintf('When Author is %s', $user_string) . '</p>';
                        }
                    }
                }
                $other_condition = '';
                $buyer_countries = get_post_meta($item['ID'], 'buyer_countries', true);
                if ($buyer_countries) {
                    if ($buyer_countries != 'all') {
                        $other_condition = '<p class="description">' . sprintf('When Buyer country is %s', implode(',', $buyer_countries)) . '</p>';
                    }
                }
                $buyer_states = get_post_meta($item['ID'], 'buyer_states', true);
                if ($buyer_states) {
                    if ($buyer_states != 'all') {
                        $other_condition .= '<p class="description">' . sprintf('When Buyer state is %s', implode(',', $buyer_states)) . '</p>';
                    }
                }
                $postcode = get_post_meta($item['ID'], 'postcode', true);
                if (!empty($postcode)) {
                    $other_condition .= '<p class="description">' . sprintf('When Buyer Postal/Zip Code %s', $postcode) . '</p>';
                }
                $store_countries = get_post_meta($item['ID'], 'store_countries', true);
                if ($store_countries) {
                    if ($store_countries != 'all') {
                        $other_condition .= '<p class="description">' . sprintf('When Store country is %s', $store_countries) . '</p>';
                    }
                }
                $currency_code = get_post_meta($item['ID'], 'currency_code', true);
                if ($currency_code) {
                    if ($currency_code != 'all') {
                        $other_condition .= '<p class="description">' . sprintf('When Currency Code is %s', $currency_code) . '</p>';
                    }
                }

                if ($condition_field == 'transaction_amount') {
                    $field = __('Transaction Amount', 'paypal-for-woocommerce-multi-account-management');
                } else {
                    $field = '';
                }
                if ($condition_sign == 'lessthan') {
                    $sign = '<';
                } else if ($condition_sign == 'greaterthan') {
                    $sign = '>';
                } else if ($condition_sign == 'equalto') {
                    $sign = '=';
                } else {
                    $sign = '';
                }

                add_thickbox();
                $product_text = '';
                if (!empty($product_ids) && is_array($product_ids)) {
                    $products = $product_ids;
                    $product_text .= '<a href="#TB_inline?width=600&height=550&inlineId=modal-window-' . esc_attr($item['ID']) . '" class="thickbox" title="Products added in Trigger Condition">Products</a>';
                    $product_text .= '<div id="modal-window-' . esc_attr($item['ID']) . '" style="display:none;">';
                    if (!empty($products)) {
                        foreach ($products as $product_id) {
                            $product = wc_get_product($product_id);
                            if (is_object($product)) {
                                $product_text .= '<p><a href="' . $product->get_permalink() . '" target="_blank">' . wp_kses_post($product->get_formatted_name()) . "</a></p>";
                            }
                        }
                    }
                    $product_text .= "</div>";
                }
                if ($currency_code != 'all') {
                    return "{$field} {$sign} " . wc_price($condition_value, array('currency' => $currency_code)) . " {$role}  {$user_info} {$other_condition} {$product_text}";
                } else {
                    return "{$field} {$sign} " . wc_price($condition_value) . " {$role} {$user_info} {$other_condition} {$product_text}";
                }

            case 'status':
                $status = get_post_meta($item['ID'], 'woocommerce_paypal_express_enable', true);
                $status_pf = get_post_meta($item['ID'], 'woocommerce_paypal_pro_payflow_enable', true);
                $status_pal = get_post_meta($item['ID'], 'woocommerce_paypal_enable', true);
                $status_ppcp = get_post_meta($item['ID'], 'woocommerce_angelleye_ppcp_enable', true);
                if ($status == 'on') {
                    return __('Enabled', 'paypal-for-woocommerce-multi-account-management');
                } else if ($status_pf == 'on') {
                    return __('Enabled', 'paypal-for-woocommerce-multi-account-management');
                } else if ($status_pal == 'on') {
                    return __('Enabled', 'paypal-for-woocommerce-multi-account-management');
                } else if ($status_ppcp == 'on') {
                    $ppcp_testmode = get_post_meta($item['ID'], 'woocommerce_angelleye_ppcp_testmode', true);
                    if (!empty($ppcp_testmode) && $ppcp_testmode === 'on') {
                        $sandbox = true;
                    } else {
                        $sandbox = false;
                    }
                    if ($sandbox) {
                        $board_status = get_post_meta($item['ID'], 'woocommerce_angelleye_ppcp_multi_account_on_board_status_sandbox', true);
                    } else {
                        $board_status = get_post_meta($item['ID'], 'woocommerce_angelleye_ppcp_multi_account_on_board_status_live', true);
                    }
                    if (empty($board_status)) {
                        return __('Invitation Sent (Pending)', 'paypal-for-woocommerce-multi-account-management');
                    } else {
                        return __('Enabled', 'paypal-for-woocommerce-multi-account-management');
                    }
                } else {
                    return __('Disabled', 'paypal-for-woocommerce-multi-account-management');
                }

            case 'gateway':
                $gateway = get_post_meta($item['ID'], 'angelleye_multi_account_choose_payment_gateway', true);
                if ($gateway !== 'angelleye_ppcp') {
                    return __('Classic', 'paypal-for-woocommerce-multi-account-management');
                } else {
                    return __('PPCP', 'paypal-for-woocommerce-multi-account-management');
                }
            default:
                return print_r($item, true);
        }
    }

    function column_title($item) {
        $title = _draft_or_post_title();
        $edit_params = array('page' => 'wc-settings', 'tab' => 'multi_account_management', 'section' => 'add_edit_account', 'action' => 'edit', 'ID' => $item['ID']);
        $delete_params = array('page' => 'wc-settings', 'tab' => 'multi_account_management', 'action' => 'delete', 'ID' => $item['ID']);
        $actions = array(
            'edit' => sprintf('<a href="%s">Edit</a>', esc_url(add_query_arg($edit_params, admin_url('admin.php')))),
            'delete' => sprintf('<a href="%s">Delete</a>', esc_url(add_query_arg($delete_params, admin_url('admin.php')))),
        );
        $trash_params = array('page' => 'wc-settings', 'tab' => 'multi_account_management', 'action' => 'trash', 'ID' => $item['ID']);
        $restore_params = array('page' => 'wc-settings', 'tab' => 'multi_account_management', 'action' => 'restore', 'ID' => $item['ID']);

        if ($this->isTrashedView()) {
            $actions = array(
                'edit' => sprintf('<a href="%s">Edit</a>', esc_url(add_query_arg($edit_params, admin_url('admin.php')))),
                'restore' => sprintf('<a href="%s">Restore</a>', esc_url(add_query_arg($restore_params, admin_url('admin.php')))),
                'delete' => sprintf('<a href="%s">Delete</a>', esc_url(add_query_arg($delete_params, admin_url('admin.php'))))
            );
        } else {
            $actions = array(
                'edit' => sprintf('<a href="%s">Edit</a>', esc_url(add_query_arg($edit_params, admin_url('admin.php')))),
                'trash' => sprintf('<a href="%s">Trash</a>', esc_url(add_query_arg($trash_params, admin_url('admin.php')))),
                'inline hide-if-no-js' => sprintf(
                        '<button type="button" class="button-link editinline" aria-label="%s" aria-expanded="false">%s</button>',
                        /* translators: %s: Post title. */
                        esc_attr(sprintf(__('Quick edit &#8220;%s&#8221; inline'), $title)),
                        __('Quick&nbsp;Edit')
                )
            );
        }
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s', $item['title'], $item['ID'], $this->row_actions($actions)
        );
    }

    function column_cb($item) {
        return sprintf(
                '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item['ID']
        );
    }

    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => __('Account Name', 'paypal-for-woocommerce-multi-account-management'),
            'api_user_name' => __('PayPal Account Email', 'paypal-for-woocommerce-multi-account-management'),
            'trigger_condition' => __('Trigger Condition', 'paypal-for-woocommerce-multi-account-management'),
            'mode' => __('Sandbox/Live', 'paypal-for-woocommerce-multi-account-management'),
            'status' => __('Status', 'paypal-for-woocommerce-multi-account-management'),
            'gateway' => __('Payment Gateway', 'paypal-for-woocommerce-multi-account-management'),
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'title' => array('title', false),
            'api_user_name' => array('api_user_name', false),
            'mode' => array('mode', false)
        );
        return $sortable_columns;
    }

    function get_bulk_actions() {
        if ($this->isTrashedView()) {
            $actions = array(
                'delete' => __('Delete', 'paypal-for-woocommerce-multi-account-management')
            );
        } else {
            $actions = array(
                'trash' => __('Trash', 'paypal-for-woocommerce-multi-account-management'),
                'edit' => __('Edit')
            );
        }
        return $actions;
    }

    private function trashAccount($postId) {
        wp_trash_post($postId);
    }

    private function deleteAccount($postId) {
        wp_delete_post($postId, true);
    }

    private function unTrashPost($postId) {
        wp_publish_post($postId);
    }

    function process_bulk_action() {
        $deleteRedirect = function ($action = 'deleted') {
            do_action('update_angelleye_multi_account');
            $redirect_url = remove_query_arg(array('action', 'ID'));
            wp_redirect(add_query_arg($action, true, $redirect_url));
            switch ($action) {
                case 'restored':
                    $message = 'Account restored successfully.';
                    break;
                case 'trashed':
                    $message = 'Account trashed successfully.';
                    break;
                default:
                    $message = 'Account permanently deleted.';
            }
            $this->message = __($message, 'paypal-for-woocommerce-multi-account-management');
            exit();
        };
        if ('trash' === $this->current_action()) {
            if (!empty($_POST['account'])) {
                foreach ($_POST['account'] as $key => $postId) {
                    $this->trashAccount($postId);
                }
            } elseif (!empty($_GET['ID'])) {
                $this->trashAccount($_GET['ID']);
            }
            $deleteRedirect('trashed');
        } else if ('delete' === $this->current_action()) {
            if (!empty($_POST['account'])) {
                $account = $_POST['account'];
                foreach ($account as $key => $postId) {
                    $this->deleteAccount($postId);
                }
            } elseif (!empty($_GET['ID'])) {
                $this->deleteAccount($_GET['ID']);
            }
            $deleteRedirect();
        } else if ('restore' === $this->current_action()) {
            if (!empty($_POST['account'])) {
                $account = $_POST['account'];
                foreach ($account as $key => $postId) {
                    $this->unTrashPost($postId);
                }
            } elseif (!empty($_GET['ID'])) {
                $this->unTrashPost($_GET['ID']);
            }
            $deleteRedirect('restored');
        }
    }

    function prepare_items() {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $angelleye_multi_account_item_per_page_default = 10;
        $angelleye_multi_account_item_per_page_value = get_user_meta($current_user_id, 'angelleye_multi_account_item_per_page', true);
        if ($angelleye_multi_account_item_per_page_value == false) {
            $angelleye_multi_account_item_per_page_value = $angelleye_multi_account_item_per_page_default;
        }
        $per_page = $angelleye_multi_account_item_per_page_value;
        $account_data = array();
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();
        $angelleye_payment_load_balancer = get_option('angelleye_payment_load_balancer', '');
        $order_by = 'DESC';
        if ($angelleye_payment_load_balancer != '') {
            $order_by = 'ASC';
        }
        $args = array(
            'post_type' => 'microprocessing',
            'numberposts' => -1,
            'order' => $order_by,
            'suppress_filters' => false
        );

        if (isset($_REQUEST['filter_entity']) && $_REQUEST['filter_entity'] == 'trashed') {
            $args['post_status'] = 'trash';
        }
        if (isset($_REQUEST['filter_entity']) && $_REQUEST['filter_entity'] == 'need_email') {
            $args['post_status'] = 'draft';
        }
        if (isset($_REQUEST['orderby'])) {
            $args['orderby'] = $_REQUEST['orderby'];
        }
        if (isset($_REQUEST['order'])) {
            $args['order'] = $_REQUEST['order'];
        }
        if (class_exists('WC_Gateway_PayPal_Express_AngellEYE')) {
            $paypal_express = angelleye_wc_gateway('paypal_express');
            if (!empty($paypal_express)) {
                $paypal_express_api_mode = angelleye_wc_gateway('paypal_express')->get_option('testmode', '');
            } else {
                $paypal_express_api_mode = 'yes';
            }
        } else {
            $paypal_express_api_mode = 'yes';
        }
        if (class_exists('WC_Gateway_PayPal_Pro_PayFlow_AngellEYE')) {
            $paypal_pro_payflow = angelleye_wc_gateway('paypal_pro_payflow');
            if (!empty($paypal_pro_payflow)) {
                $paypal_pro_payflow_api_mode = angelleye_wc_gateway('paypal_pro_payflow')->get_option('testmode', '');
            } else {
                $paypal_pro_payflow_api_mode = 'yes';
            }
        } else {
            $paypal_pro_payflow_api_mode = 'yes';
        }
        if (class_exists('WC_Gateway_PPCP_AngellEYE')) {
            $angelleye_ppcp = angelleye_wc_gateway('angelleye_ppcp');
            if (!empty($angelleye_ppcp)) {
                $angelleye_ppcp_api_mode = angelleye_wc_gateway('angelleye_ppcp')->get_option('testmode', '');
            } else {
                $angelleye_ppcp_api_mode = 'yes';
            }
        } else {
            $angelleye_ppcp_api_mode = 'yes';
        }
        $paypal_express_seq = 1;
        $payflow_seq = 1;
        $ppcp_seq = 1;
        $seq_text = __('Payment Seq #', 'paypal-for-woocommerce-multi-account-management');
        $angelleye_payment_load_balancer = get_option('angelleye_payment_load_balancer', '');
        if (isset($_REQUEST['s']) && strlen($_REQUEST['s'])) {
            $posts = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT p1.post_id FROM {$wpdb->postmeta} p1, {$wpdb->posts}  WHERE wp_posts.ID = p1.post_id AND wp_posts.post_type = 'microprocessing' AND p1.meta_value LIKE %s", '%' . wc_clean($_REQUEST['s']) . '%'));
        } else {
            $posts = get_posts($args);
        }
        if (!empty($posts)) {
            foreach ($posts as $key => $value) {
                $account_data[$key]['ID'] = isset($value->ID) ? $value->ID : $value;
                $meta_data = get_post_meta(isset($value->ID) ? $value->ID : $value);
                $meta_data['angelleye_multi_account_choose_payment_gateway'][0] = empty($meta_data['angelleye_multi_account_choose_payment_gateway'][0]) ? 'paypal_express' : $meta_data['angelleye_multi_account_choose_payment_gateway'][0];
                if (!empty($meta_data['angelleye_multi_account_choose_payment_gateway'][0]) && $meta_data['angelleye_multi_account_choose_payment_gateway'][0] == 'paypal_express') {
                    $is_enable = false;
                    if (!empty($meta_data['woocommerce_paypal_express_testmode']) && $meta_data['woocommerce_paypal_express_testmode'][0] == 'on') {
                        $account_data[$key]['mode'] = 'Sandbox';
                    } else {
                        $account_data[$key]['mode'] = 'Live';
                    }

                    if (!empty($meta_data['woocommerce_paypal_express_enable'][0]) && $meta_data['woocommerce_paypal_express_enable'][0] == 'on') {
                        $is_enable = true;
                    }
                    $account_data[$key]['title'] = !empty($meta_data['woocommerce_paypal_express_account_name'][0]) ? $meta_data['woocommerce_paypal_express_account_name'][0] : '';
                    if ($account_data[$key]['mode'] == 'Sandbox') {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_sandbox_api_username'][0]) ? $meta_data['woocommerce_paypal_express_sandbox_api_username'][0] : '';
                        if (empty($account_data[$key]['api_user_name'])) {
                            $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_sandbox_email'][0]) ? $meta_data['woocommerce_paypal_express_sandbox_email'][0] : '';
                        }
                        if ($is_enable == true && $paypal_express_api_mode == 'yes' && $angelleye_payment_load_balancer != '') {
                            $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . $seq_text . $paypal_express_seq . '</span></mark>';
                            $paypal_express_seq = $paypal_express_seq + 1;
                        }
                    } else {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_api_username'][0]) ? $meta_data['woocommerce_paypal_express_api_username'][0] : '';
                        if (empty($account_data[$key]['api_user_name'])) {
                            $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_email'][0]) ? $meta_data['woocommerce_paypal_express_email'][0] : '';
                        }
                        if ($is_enable == true && $paypal_express_api_mode != 'yes' && $angelleye_payment_load_balancer != '') {
                            $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . $seq_text . $paypal_express_seq . '</span></mark>';
                            $paypal_express_seq = $paypal_express_seq + 1;
                        }
                    }
                    if (!empty($meta_data['vendor_id'][0]) && $angelleye_payment_load_balancer === '') {
                        $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . __('Vendor Rule - Auto Generated', 'paypal-for-woocommerce-multi-account-management') . '</span></mark>';
                    }
                } else if (!empty($meta_data['angelleye_multi_account_choose_payment_gateway'][0]) && $meta_data['angelleye_multi_account_choose_payment_gateway'][0] == 'paypal_pro_payflow') {
                    $is_enable = false;
                    if (!empty($meta_data['woocommerce_paypal_pro_payflow_testmode']) && $meta_data['woocommerce_paypal_pro_payflow_testmode'][0] == 'on') {
                        $account_data[$key]['mode'] = 'Sandbox';
                    } else {
                        $account_data[$key]['mode'] = 'Live';
                    }
                    if (!empty($meta_data['woocommerce_paypal_pro_payflow_enable'][0]) && $meta_data['woocommerce_paypal_pro_payflow_enable'][0] == 'on') {
                        $is_enable = true;
                    }
                    $account_data[$key]['title'] = !empty($meta_data['woocommerce_paypal_pro_payflow_account_name'][0]) ? $meta_data['woocommerce_paypal_pro_payflow_account_name'][0] : '';
                    if ($account_data[$key]['mode'] == 'Sandbox') {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user'][0]) ? $meta_data['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user'][0] : '';
                        if ($is_enable == true && $paypal_pro_payflow_api_mode == 'yes' && $angelleye_payment_load_balancer != '') {
                            $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . $seq_text . $payflow_seq . '</span></mark>';
                            $payflow_seq = $payflow_seq + 1;
                        }
                    } else {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_pro_payflow_api_paypal_user'][0]) ? $meta_data['woocommerce_paypal_pro_payflow_api_paypal_user'][0] : '';
                        if ($is_enable == true && $paypal_pro_payflow_api_mode != 'yes' && $angelleye_payment_load_balancer != '') {
                            $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . $seq_text . $payflow_seq . '</span></mark>';
                            $payflow_seq = $payflow_seq + 1;
                        }
                    }
                } else if (!empty($meta_data['angelleye_multi_account_choose_payment_gateway'][0]) && $meta_data['angelleye_multi_account_choose_payment_gateway'][0] == 'angelleye_ppcp') {
                    $is_enable = false;
                    if (!empty($meta_data['woocommerce_angelleye_ppcp_testmode']) && $meta_data['woocommerce_angelleye_ppcp_testmode'][0] == 'on') {
                        $account_data[$key]['mode'] = 'Sandbox';
                    } else {
                        $account_data[$key]['mode'] = 'Live';
                    }
                    if (!empty($meta_data['woocommerce_angelleye_ppcp_enable'][0]) && $meta_data['woocommerce_angelleye_ppcp_enable'][0] == 'on') {
                        $is_enable = true;
                    }
                    $account_data[$key]['title'] = !empty($meta_data['woocommerce_angelleye_ppcp_account_name'][0]) ? $meta_data['woocommerce_angelleye_ppcp_account_name'][0] : '';
                    if ($account_data[$key]['mode'] == 'Sandbox') {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_angelleye_ppcp_sandbox_email_address'][0]) ? $meta_data['woocommerce_angelleye_ppcp_sandbox_email_address'][0] : '';
                        if ($is_enable == true && $angelleye_ppcp_api_mode == 'yes' && $angelleye_payment_load_balancer != '') {
                            $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . $seq_text . $ppcp_seq . '</span></mark>';
                            $ppcp_seq = $ppcp_seq + 1;
                        }
                    } else {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_angelleye_ppcp_email_address'][0]) ? $meta_data['woocommerce_angelleye_ppcp_email_address'][0] : '';
                        if ($is_enable == true && $angelleye_ppcp_api_mode != 'yes' && $angelleye_payment_load_balancer != '') {
                            $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . $seq_text . $ppcp_seq . '</span></mark>';
                            $ppcp_seq = $ppcp_seq + 1;
                        }
                    }
                } else {
                    if (!empty($meta_data['woocommerce_paypal_testmode']) && $meta_data['woocommerce_paypal_testmode'][0] == 'on') {
                        $account_data[$key]['mode'] = 'Sandbox';
                    } else {
                        $account_data[$key]['mode'] = 'Live';
                    }
                    $account_data[$key]['title'] = !empty($meta_data['woocommerce_paypal_account_name'][0]) ? $meta_data['woocommerce_paypal_account_name'][0] : '';
                    if ($account_data[$key]['mode'] == 'Sandbox') {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_sandbox_api_username'][0]) ? $meta_data['woocommerce_paypal_sandbox_api_username'][0] : '';
                        if (empty($account_data[$key]['api_user_name'])) {
                            $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_sandbox_email'][0]) ? $meta_data['woocommerce_paypal_sandbox_email'][0] : '';
                        }
                    } else {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_api_username'][0]) ? $meta_data['woocommerce_paypal_api_username'][0] : '';
                        if (empty($account_data[$key]['api_user_name'])) {
                            $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_email'][0]) ? $meta_data['woocommerce_paypal_email'][0] : '';
                        }
                    }
                }
            }
            $data = $account_data;
        } else {
            $data = $account_data;
        }

        function usort_reorder($a, $b) {
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title';
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc';
            $result = strcmp($a[$orderby], $b[$orderby]);
            return ($order === 'asc') ? $result : -$result;
        }

        //usort($data, 'usort_reorder');
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
        $this->items = $data;
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    private function count_posts($postType = 'post') {
        global $wpdb;
        $cache_key = _count_posts_cache_key($postType);

        $counts = wp_cache_get($cache_key, 'counts');
        if (false !== $counts) {
            // We may have cached this before every status was registered.
            foreach (get_post_stati() as $status) {
                if (!isset($counts->{$status})) {
                    $counts->{$status} = 0;
                }
            }

            return $counts;
        }

        $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s GROUP BY post_status";
        $results = (array) $wpdb->get_results($wpdb->prepare($query, $postType), ARRAY_A);
        $counts = array_fill_keys(get_post_stati(), 0);

        foreach ($results as $row) {
            $counts[$row['post_status']] = $row['num_posts'];
        }

        $counts = (object) $counts;
        wp_cache_set($cache_key, $counts, 'counts');

        return $counts;
    }

    protected function get_views() {
        global $wpdb;
        $views = array();
        $current = (!empty($_REQUEST['filter_entity']) ? $_REQUEST['filter_entity'] : 'all');

        //All link
        $published_posts = $trashed_posts = $need_email = 0;
        $count_posts = $this->count_posts('microprocessing');

        if ($count_posts) {
            $published_posts = $count_posts->publish;
            $trashed_posts = $count_posts->trash;
            $need_email = $count_posts->draft;
        }

        $class = ($current == 'all' ? ' class="current"' : '');
        $all_url = remove_query_arg('filter_entity');
        $views['all'] = "<a href='{$all_url}' {$class} >All ($published_posts)</a>";

        //Trash link
        $foo_url = add_query_arg('filter_entity', 'trashed');
        $need_email_url = add_query_arg('filter_entity', 'need_email');
        $class = ($current == 'trashed' ? ' class="current"' : '');
        $need_email_class = ($current == 'need_email' ? ' class="current"' : '');
        $views['pending'] = "<a href='{$foo_url}' {$class} >Trashed ($trashed_posts)</a>";
        $views['need_email'] = "<a href='{$need_email_url}' {$need_email_class} >Need Email ($need_email)</a>";

        return $views;
    }

    private function isTrashedView() {
        return isset($_REQUEST['filter_entity']) && $_REQUEST['filter_entity'] == 'trashed';
    }
}
