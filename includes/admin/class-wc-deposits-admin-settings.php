<?php

namespace MagePeople\WCDP;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * @brief Adds a new panel to the WooCommerce Settings
 *
 */
 
 
class WC_Deposits_Admin_Settings
{

    public function __construct()
    {


        $allowed_html = array(
            'a' => array('href' => array(), 'title' => array()),
            'br' => array(), 'em' => array(),
            'strong' => array(), 'p' => array(),
            's' => array(), 'strike' => array(),
            'del' => array(), 'u' => array(), 'b' => array()
        );


        // Hook the settings page
        add_filter('woocommerce_settings_tabs_array', array($this, 'settings_tabs_array'), 21);
        add_action('woocommerce_settings_wc-deposits', array($this, 'settings_tabs_wc_deposits'));
        add_action('woocommerce_update_options_wc-deposits', array($this, 'update_options_wc_deposits'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_settings_script'));

        add_action('woocommerce_admin_field_deposit_buttons_color', array($this, 'deposit_buttons_color'));
        // reminder datepicker
        add_action('woocommerce_admin_field_reminder_datepicker', array($this, 'reminder_datepicker'));


       
    }


    public function enqueue_settings_script()
    {

        if (function_exists('get_current_screen')) {

            if (isset($_GET['page']) && $_GET['page'] === 'wc-settings' && isset($_GET['tab']) && $_GET['tab'] === 'wc-deposits') {

                wp_enqueue_style('wp-color-picker');
                wp_enqueue_script('jquery-ui-datepicker');
                wp_enqueue_script('wc-deposits-admin-settings', WC_DEPOSITS_PLUGIN_URL . '/assets/js/admin/admin-settings.js', array('jquery', 'wp-color-picker'), WC_DEPOSITS_VERSION);
                wp_localize_script('wc-deposits-admin-settings', 'wc_deposits', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'strings' => array(
                        'success' => esc_html__('Updated successfully', 'Advanced Partial Payment and Deposit For Woocommerce')
                    )

                ));
            }

        }


    }


    public function settings_tabs_array($tabs)
    {

        $tabs['wc-deposits'] = esc_html__('Deposits', 'Advanced Partial Payment and Deposit For Woocommerce');
        return $tabs;
    }

    /**
     * @brief Write out settings html
     *
     * @param array $settings ...
     * @return void
     */
public function settings_tabs_wc_deposits()
{
    $mode_notice = wcdp_checkout_mode() ? '<span style="padding:5px 10px; color:#fff; position: relative; top: 10px; background-color:rgba(146, 52, 129, 0.8);">' . esc_html__('Checkout Mode Enabled', 'Advanced Partial Payment and Deposit For Woocommerce') . '</span>' : '';
    $debug_mode_notice = get_option('wc_deposits_debug_mode', 'no') === 'yes' ? '<span style="padding:5px 10px; color:#fff; background-color:rgba(255,63,76,0.8);">' . esc_html__('Debugging Mode Enabled', 'Advanced Partial Payment and Deposit For Woocommerce') . '</span>' : '';
    ?>

<div style="background-color: #162748; color: white; padding: 20px;">
    <h2 style="color: white; margin: 0;"><?php echo esc_html__('Deposit & Partial Payment Solution for WooCommerce - WpDepositly | MagePeople', 'Advanced Partial Payment and Deposit For Woocommerce'); ?><span style="font-size: 0.8em;"> - Version: 2.2.5</span></h2>
    <?php echo $mode_notice . $debug_mode_notice; ?>
</div>




    <?php 
    $settings_tabs = apply_filters('wc_deposits_settings_tabs', array(
        'wcdp_general' => esc_html__('General Settings', 'Advanced Partial Payment and Deposit For Woocommerce'),
        'display_text' => esc_html__('Display & Text', 'Advanced Partial Payment and Deposit For Woocommerce'),
        'checkout_mode' => esc_html__('Checkout Mode', 'Advanced Partial Payment and Deposit For Woocommerce'),
        'second_payment' => esc_html__('Future Payments & Reminders', 'Advanced Partial Payment and Deposit For Woocommerce'),
        'gateways' => esc_html__('Gateways', 'Advanced Partial Payment and Deposit For Woocommerce')
    ));

    ?>

    <div style="display: flex; ">
        <div class="wcdp-nav-tab-wrapper" style="display: flex; background: #162748; flex-direction: column; margin-top: 20px;">
            <?php
            $count = 0;
            foreach ($settings_tabs as $key => $tab_name) {
                $url = admin_url('admin.php?page=wc-settings&tab=wc-deposits&section=' . $key);
                $count++;
                $active = isset($_GET['section']) ? $key === $_GET['section'] : $count === 1;
                ?>
                <a href="<?php echo $url; ?>" class="wcdp nav-tab <?php echo $active ? 'wcdp-nav-tab-active' : ''; ?>" data-target="<?php echo $key; ?>"><?php echo $tab_name; ?></a>
                <?php
            }
            ?>
        </div>

        <div style="flex-grow: 1; padding-left: 20px;background: white;">
            

            <?php
            // echo tabs content
            $count = 0;
            foreach ($settings_tabs as $key => $tab_name) {
                $count++;
                $active = isset($_GET['section']) ? $key === $_GET['section'] : $count === 1;
                if (method_exists($this, "tab_{$key}_output")) {
                    $this->{"tab_{$key}_output"}($active);
                }
            }
            // allow addons to add their own tab content
            do_action('wc_deposits_after_settings_tabs_content');
            ?>
        </div>
        <script>
    document.addEventListener("DOMContentLoaded", function() {
        var tabs = document.querySelectorAll('.wcdp.nav-tab');

        // Function to handle tab click
        function handleTabClick(event) {
            // Remove active class from all tabs
            tabs.forEach(function(tab) {
                tab.classList.remove('wcdp-nav-tab-active');
            });
            // Add active class to the clicked tab
            event.target.classList.add('wcdp-nav-tab-active');
            // Store active tab index in sessionStorage
            var tabIndex = Array.from(tabs).indexOf(event.target);
            sessionStorage.setItem('activeTabIndex', tabIndex);
        }

        // Add click event listener to each tab
        tabs.forEach(function(tab) {
            tab.addEventListener('click', handleTabClick);
        });

        // Check if there's a stored active tab index and apply active class
        var activeTabIndex = sessionStorage.getItem('activeTabIndex');
        if (activeTabIndex !== null) {
            // Remove active class from all tabs (again to ensure no conflict)
            tabs.forEach(function(tab) {
                tab.classList.remove('wcdp-nav-tab-active');
            });
            // Add active class to the tab based on stored index
            tabs[activeTabIndex].classList.add('wcdp-nav-tab-active');
        }
    });
</script>


    </div>
<?php
}



    /*** BEGIN TABS CONTENT CALLBACKS **/

    function tab_wcdp_general_output($active)
    {
        $class = $active ? '' : 'hidden';
        ?>
        
        <div id="wcdp_general" class="wcdp-tab-content wcdp-general-tab <?php echo $class; ?>">

            <?php
            $roles_array = array();
            $user_roles = array_reverse(get_editable_roles());
            foreach ($user_roles as $key => $user_role) {

                $roles_array[$key] = $user_role['name'];
            }
            $manage_plans_link = sprintf(wp_kses(__(' <a  target="_blank" href="%s"> Manage Payment Plans</a>', 'Advanced Partial Payment and Deposit For Woocommerce'), array('a' => array('href' => array(), 'target' => array()))), admin_url('/edit-tags.php?taxonomy=wcdp_payment_plan&post_type=product'));

            //payment plans
            $payment_plans = get_terms(array(
                    'taxonomy' => WC_DEPOSITS_PAYMENT_PLAN_TAXONOMY,
                    'hide_empty' => false
                )
            );
            $all_plans = array();
            foreach ($payment_plans as $payment_plan) {
                $all_plans[$payment_plan->term_id] = $payment_plan->name;
            }
            ?>
        <div class="wcdp-custom-container">
            <?php $general_settings = array(


                /*
                 * Site-wide settings
                 */

                'deposit_storewide_values' => array(

                    'name' => esc_html__('Deposit Storewide Values', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'wc_deposits_deposit_storewide_values',
                    'class' => 'deposits_deposit_storewide_values',
                ),

                'enable_storewide_deposit' => array(
                    'name' => esc_html__('Enable deposit by default', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'desc_tip' =>true,
                    'options' => array(
                        'no' => esc_html__('No', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'yes' => esc_html__('Yes', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    ),
                    'desc' => esc_html__('Enable this to require a deposit for all products by default.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_storewide_deposit_enabled',
                    'default' => 'no'
                ),
                
                   'enable_storewide_deposit_details' => array(
                    'name' => esc_html__('On/Off Pay Deposit details in cart & checkout page', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'desc_tip' =>true,
                    'options' => array(
                        'no' => esc_html__('No', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'yes' => esc_html__('Yes', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    ),
                    'desc' => esc_html__('On/Off Pay Deposit details in cart & checkout page.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_storewide_deposit_enabled_details',
                    'default' => 'no'
                ),
                
                    'enable_storewide_deposit_btn' => array(
                    'name' => esc_html__('On/Off Pay Deposit button in product list', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'desc_tip' =>true,
                    'options' => array(
                        'no' => esc_html__('No', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'yes' => esc_html__('Yes', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    ),
                    'desc' => esc_html__('Choose whether to enable the Pay Deposit button in the product list.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_storewide_deposit_enabled_btn',
                    'default' => 'yes'
                ),
                'storewide_deposit_force_deposit' => array(
                    'name' => esc_html__('Force deposit by default', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'desc_tip' =>true,
                    'options' => array(
                        'no' => esc_html__('No', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'yes' => esc_html__('Yes', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    ), 'desc' => esc_html__('If you enable this, the customer will not be allowed to make a full payment.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_storewide_deposit_force_deposit',
                    'default' => 'no'

                ),
                'storewide_deposit_amount_type' => array(
                    'name' => esc_html__('Default Deposit Type', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'desc_tip' =>true,
                    'desc' => esc_html__('Choose amount type', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_storewide_deposit_amount_type',
                    'options' => array(
                        'fixed' => esc_html__('Fixed', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'percent' => esc_html__('Percentage', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'payment_plan' => esc_html__('Payment plan', 'Advanced Partial Payment and Deposit For Woocommerce')
                    ),
                    'default' => 'percent'
                ),
                'storewide_deposit_amount' => array(
                    'name' => esc_html__('Default Deposit Amount', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'number',
                    'desc_tip' =>true,
                    'desc' => esc_html__('Amount of deposit.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_storewide_deposit_amount',
                    'default' => '50',
                    'custom_attributes' => array(
                        'min' => '0.0',
                        'step' => '0.01'
                    )
                ),
                'storewide_deposit_payment_plans' => array(
                    'name' => esc_html__('Default Payment plan(s)', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'multiselect',
                    'desc_tip' =>true,
                    'class' => 'chosen_select',
                    'options' => $all_plans,
                    'desc' => esc_html__('Selected payment plan(s) will be available for customers to choose from.  ', 'Advanced Partial Payment and Deposit For Woocommerce') . $manage_plans_link,
                    'id' => 'wc_deposits_storewide_deposit_payment_plans',
                ),
                'deposit_storewide_values_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_deposits_deposit_storewide_values_end'
                ),
                'sitewide_title' => array(
                    'name' => esc_html__('Site-wide Settings', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'wc_deposits_site_wide_title'
                ),
                'deposits_disable' => array(
                    'name' => esc_html__('Disable Deposits', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'checkbox',
                    'desc' => esc_html__('Check this to disable all deposit functionality with one click.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_site_wide_disable',
                ),


                'deposits_default' => array(
                    'name' => esc_html__('Default Selection', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'desc_tip' =>true,
                    'desc' => esc_html__('Select the default deposit option.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_default_option',
                    'options' => array(
                        'deposit' => esc_html__('Pay Deposit', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'full' => esc_html__('Full Amount', 'Advanced Partial Payment and Deposit For Woocommerce')
                    ),
                    'default' => 'deposit'
                ),
                'deposits_stock' => array(
                    'name' => esc_html__('Reduce Stocks On', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'desc_tip' =>true,
                    'desc' => esc_html__('Choose when to reduce stocks.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_reduce_stock',
                    'options' => array(
                        'deposit' => esc_html__('Deposit Payment', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'full' => esc_html__('Full Payment', 'Advanced Partial Payment and Deposit For Woocommerce')
                    ),
                    'default' => 'full'
                ),
                'partially_paid_orders_editable' => array(
                    'name' => esc_html__('Make partially paid orders editable', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'checkbox',
                    'desc' => esc_html__('Check to make orders editable while in "partially paid" status', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_partially_paid_orders_editable',
                ),

                'order_list_table_show_has_deposit' => array(
                    'name' => esc_html__('Show "has deposit" column in admin order list table', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'checkbox',
                    'desc' => esc_html__('Check to show a column in admin order list indicating if order has deposit', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_order_list_table_show_has_deposit',
                ),

                'disable_deposit_for_user_roles' => array(
                    'name' => esc_html__('Disable deposit for selected user roles', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'multiselect',
                    'desc_tip' =>true,
                    'class' => 'chosen_select',
                    'options' => $roles_array,
                    'desc' => esc_html__('Disable deposit for selected user roles', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_disable_deposit_for_user_roles',
                ),

                'restrict_deposits_for_logged_in_users_only' => array(
                    'name' => esc_html__('Restrict deposits for logged-in users only', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'checkbox',
                    'desc' => esc_html__('Check this to disable all deposit functionality for guests', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_restrict_deposits_for_logged_in_users_only',
                ),
                'sitewide_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_deposits_site_wide_end'
                ),
               'calculation_and_structure' => array(

                    'name' => esc_html__('Calculation & Structure', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'wc_deposits_calculation_and_structure'
                ),
                'partial_payments_structure' => array(
                    'name' => esc_html__('Partial Payments Structure', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'desc_tip' =>true,
                    'desc' => esc_html__('Choose how partial payments are created. If single is checked, partial payment will consist of a single fee. 
                                               If "Copy main order items" is selected, items of main order will be created in partial payment.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_partial_payments_structure',
                    'default' => 'single',
                    'options' => array(
                        'single' => esc_html__('Single fee item', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'full' => esc_html__('Copy main order items', 'Advanced Partial Payment and Deposit For Woocommerce')
                    )
                ),
                'taxes_handling' => array(
                    'name' => esc_html__('Taxes Collection Method', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'desc_tip' =>true,
                    'desc' => esc_html__('Choose how to handle taxes.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_taxes_handling',
                    'options' => array(
                        'deposit' => esc_html__('with deposit', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'split' => esc_html__('Split according to deposit amount', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'full' => esc_html__('with future payment(s)', 'Advanced Partial Payment and Deposit For Woocommerce')
                    )
                ),
                'fees_handling' => array(
                    'name' => esc_html__('Fees Collection Method', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'desc_tip' =>true,
                    'desc' => esc_html__('Choose how to handle fees.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_fees_handling',
                    'options' => array(
                        'deposit' => esc_html__('with deposit', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'split' => esc_html__('Split according to deposit amount', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'full' => esc_html__('with future payment(s)', 'Advanced Partial Payment and Deposit For Woocommerce')
                    )
                ),
                'shipping_handling' => array(
                    'name' => esc_html__('Shipping Handling Method', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'desc_tip' =>true,
                    'desc' => esc_html__('Choose how to handle shipping.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_shipping_handling',
                    'options' => array(
                        'deposit' => esc_html__('with deposit', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'split' => esc_html__('Split according to deposit amount', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'full' => esc_html__('with future payment(s)', 'Advanced Partial Payment and Deposit For Woocommerce')
                    )
                ),
                'coupons_handling' => array(
                    'name' => esc_html__('Discount Coupons Handling', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'desc_tip' =>true,
                    'desc' => esc_html__('Choose how to handle coupon discounts', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_coupons_handling',
                    'options' => array(
                        'deposit' => esc_html__('Deduct from deposit', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'split' => esc_html__('Split according to deposit amount', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'second_payment' => esc_html__('Deduct from future payment(s)', 'Advanced Partial Payment and Deposit For Woocommerce')
                    ),
                    'default' => 'second_payment'
                ),
                'calculation_and_structure_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_deposits_calculation_and_structure_end'
                ),

            );
           


            woocommerce_admin_fields($general_settings);

            ?>
            <?php do_action('wc_deposits_settings_tabs_general_tab'); ?>
        </div>
        </div>
        <?php
    }

    function tab_display_text_output($active)
    {

        $class = $active ? '' : 'hidden';
        ?>
        <div id="display_text" class="wcdp-tab-content wrap wcdp-custom-container <?php echo $class; ?>">
            <?php
            $text_to_replace = esc_html__('Text to replace ', 'Advanced Partial Payment and Deposit For Woocommerce');

            $strings_settings = array(

                'display_title' => array(
                    'name' => esc_html__('Display & Text', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'title',
                    'id' => 'wc_deposits_display_text_title'
                ),
                'hide_when_forced' => array(
                    'name' => esc_html__('Hide Deposit UI when forced', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'checkbox',
                    'desc' => esc_html__('Check this to hide deposit UI when deposit is forced ', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_hide_ui_when_forced',
                ),
                'override_payment_form' => array(
                    'name' => esc_html__('Override payment form', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'checkbox',
                    'desc' => esc_html__('allow overriding "form-pay.php" template to display original order details during partial payment checkout', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_override_payment_form',
                    'default' => 'no',
                ),
                'deposits_tax' => array(
                    'name' => esc_html__('Display Taxes In Product page', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'checkbox',
                    'desc' => esc_html__('Check this to count taxes as part of deposits for purposes of display to the customer in product page.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_tax_display',
                ),
                'deposits_tax_cart' => array(
                    'name' => esc_html__('Display taxes in cart item Details', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'checkbox',
                    'desc' => esc_html__('Check this to count taxes as part of deposits for purposes of display to the customer in cart item details', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_tax_display_cart_item',
                ),
                'deposits_breakdown_cart_tooltip' => array(
                    'name' => esc_html__('Display Deposit-breakdown Tooltip in cart', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'checkbox',
                    'desc' => esc_html__('Check to display tooltip in cart totals detailing deposit breakdown', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_breakdown_cart_tooltip',
                ),
                'display_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_deposits_display_text_end'
                ),


                /*
                 * Section for buttons
                 */

                'buttons_title' => array(
                    'name' => esc_html__('Buttons', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'title',
                    'desc' => wp_kses(__('No HTML allowed. Text will be translated to the user if a translation is available.<br/>Please note that any overflow will be hidden, since button width is theme-dependent.', 'Advanced Partial Payment and Deposit For Woocommerce'), array('br' => array())),
                    'id' => 'wc_deposits_buttons_title'
                ),

                'basic_radio_buttons' => array(
                    'name' => esc_html__('Use Basic Deposit Buttons', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'checkbox',
                    'desc' => esc_html__('Use basic radio buttons for deposits, Check this if you are facing issues with deposits slider buttons in product page, ', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_use_basic_radio_buttons',
                    'default' => 'no',
                ),
                'buttons_color' => array(
                    'type' => 'deposit_buttons_color',
                    'class' => 'deposit_buttons_color_html',
                ),
                'buttons_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_deposits_buttons_end'
                ),
                'deposit_choice_strings_title' => array(
                    'name' => esc_html__('Deposit choice strings', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'title',
                    'desc' => esc_html__('No HTML allowed. Text will be translated to the user if a translation is available.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_strings_title'
                )
            ,
                'deposits_button_deposit' => array(
                    'name' => esc_html__('Deposit Button Text', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'text',
                    'desc' => esc_html__('Text displayed in the \'Pay Deposit\' button.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_button_deposit',
                    'default' => 'Pay Deposit'
                ),
                'deposits_button_full' => array(
                    'name' => esc_html__('Full Amount Button Text', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'text',
                    'desc' => esc_html__('Text displayed in the \'Full Amount\' button.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_button_full_amount',
                    'default' => 'Full Amount'
                ),
                'deposit_choice_strings_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_deposits_deposit_choice_strings_end'
                ),
                'checkout_and_order_strings' => array(
                    'name' => esc_html__('Checkout & Order strings', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'title',
                    'desc' => esc_html__('No HTML allowed. Text will be translated to the user if a translation is available.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_strings_title'
                ),

                'deposits_to_pay_text' => array(
                    'name' => esc_html__('To Pay', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'text',
                    'desc' => $text_to_replace . '<b>' . esc_html__('To Pay', 'Advanced Partial Payment and Deposit For Woocommerce') . '</b>',
                    'id' => 'wc_deposits_to_pay_text',
                    'default' => 'To Pay'
                ),
                'deposits_deposit_amount_text' => array(
                    'name' => esc_html__('Deposit Amount', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'text',
                    'desc' => $text_to_replace . '<b>' . esc_html__('Deposit Amount', 'Advanced Partial Payment and Deposit For Woocommerce') . '</b>',
                    'id' => 'wc_deposits_deposit_amount_text',
                    'default' => 'Deposit Amount'
                ),
                'deposits_second_payment_text' => array(
                    'name' => esc_html__('Future Payments', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'text',
                    'desc' => $text_to_replace . '<b>' . esc_html__('Future Payments', 'Advanced Partial Payment and Deposit For Woocommerce') . '</b>',
                    'id' => 'wc_deposits_second_payment_text',
                    'default' => 'Future Payments'
                ),
                'deposits_deposit_option_text' => array(
                    'name' => esc_html__('Deposit Option', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'text',
                    'desc' => $text_to_replace . '<b>' . esc_html__('Deposit Option', 'Advanced Partial Payment and Deposit For Woocommerce') . '</b>',
                    'id' => 'wc_deposits_deposit_option_text',
                    'default' => 'Deposit Option'
                ),

                'deposits_payment_link_text' => array(
                    'name' => esc_html__('Payment Link', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'text',
                    'desc' => $text_to_replace . '<b>' . esc_html__('Payment Link', 'Advanced Partial Payment and Deposit For Woocommerce') . '</b>',
                    'id' => 'wc_deposits_payment_link_text',
                    'default' => 'Payment Link'
                ),

                'strings_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_deposits_strings_end'
                ),
                /*
                 * Section for messages
                 */

                'messages_title' => array(
                    'name' => esc_html__('Messages', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'title',
                    'desc' => esc_html__('Please check the documentation for allowed HTML tags.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_messages_title'
                ),
                'deposits_message_deposit' => array(
                    'name' => esc_html__('Deposit Message', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'textarea',
                    'desc' => esc_html__('Message to show when \'Pay Deposit\' is selected on the product\'s page.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_message_deposit',
                ),
                'deposits_message_full' => array(
                    'name' => esc_html__('Full Amount Message', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'textarea',
                    'desc' => __('Message to show when \'Full Amount\' is selected on the product\'s page.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_message_full_amount',
                ),
                'messages_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_deposits_messages_end'
                ),


            );
            woocommerce_admin_fields($strings_settings);
            ?>
            <?php do_action('wc_deposits_settings_tabs_display_text_tab'); ?>
        </div>
        <?php
    }

    function tab_checkout_mode_output($active)
    {
        $class = $active ? '' : 'hidden';
        ?>
        <div id="checkout_mode" class="wcdp-tab-content wrap wcdp-custom-container <?php echo $class; ?>">
            <?php

            $cart_checkout_settings = array(

                'checkout_mode_title' => array(
                    'name' => esc_html__('Deposit on Checkout Mode', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'title',
                    'desc' => esc_html__('changes the way deposits work to be based on total amount at checkout button', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_messages_title'
                ),
                'enable_checkout_mode' => array(
                    'name' => esc_html__('Enable checkout mode', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'options' => array(
                        'no' => esc_html__('No', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'yes' => esc_html__('Yes', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    ),
                    'desc' => esc_html__('Enable checkout mode, which makes deposits calculate based on total amount during checkout instead of per product.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_checkout_mode_enabled',
                ),
                'checkout_mode_force_deposit' => array(
                    'name' => esc_html__('Force deposit', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'options' => array(
                        'no' => esc_html__('No', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'yes' => esc_html__('Yes', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    ),
                    'desc' => esc_html__('Force Checkout Mode Deposit, the customer will not be allowed to make a full payment during checkout.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_checkout_mode_force_deposit',
                ),
                'checkout_mode_amount_type' => array(
                    'name' => esc_html__('Amount Type', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'select',
                    'desc' => esc_html__('Choose amount type', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_checkout_mode_deposit_amount_type',
                    'options' => array(
                        'fixed' => esc_html__('Fixed', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'percentage' => esc_html__('Percentage', 'Advanced Partial Payment and Deposit For Woocommerce'),
                        'payment_plan' => esc_html__('Payment plan', 'Advanced Partial Payment and Deposit For Woocommerce')
                    ),
                    'default' => 'percentage'
                ),
                'checkout_mode_amount_deposit_amount' => array(
                    'name' => esc_html__('Deposit Amount', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'number',
                    'desc' => esc_html__('Amount of deposit ( should not be more than 99 for percentage or more than order total for fixed', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_checkout_mode_deposit_amount',
                    'default' => '50',
                    'custom_attributes' => array(
                        'min' => '0.0',
                        'step' => '0.01'
                    )
                ),


            );


            //payment plans
            $payment_plans = get_terms(array(
                    'taxonomy' => WC_DEPOSITS_PAYMENT_PLAN_TAXONOMY,
                    'hide_empty' => false
                )
            );

            $all_plans = array();
            foreach ($payment_plans as $payment_plan) {
                $all_plans[$payment_plan->term_id] = $payment_plan->name;
            }

            $cart_checkout_settings['checkout_mode_payment_plans'] = array(
                'name' => esc_html__('Payment plan(s)', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'type' => 'multiselect',
                'class' => 'chosen_select',
                'options' => $all_plans,
                'desc' => esc_html__('Selected payment plan(s) will be available for customers to choose from', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'id' => 'wc_deposits_checkout_mode_payment_plans'
            );

            $cart_checkout_settings['checkout_mode_end'] = array(
                'type' => 'sectionend',
                'id' => 'wc_deposits_checkout_mode_end'
            );


            woocommerce_admin_fields($cart_checkout_settings);

            ?>
            <?php do_action('wc_deposits_settings_tabs_checkout_mode_tab'); ?>

        </div>

        <?php

    }

    function tab_second_payment_output($active)
    {
        $class = $active ? '' : 'hidden';

        ?>
        <div id="second_payment" class="wcdp-tab-content wrap wcdp-custom-container <?php echo $class; ?>" >


            <?php

            $reminder_settings = array(
                'second_payment_settings' => array(
                    'name' => esc_html__('Future Payments Settings', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'title',
                    'id' => 'wc_deposits_second_payment_settings_title'
                ),
                'deposits_payaple' => array(
                    'name' => esc_html__('Enable Future Payments', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'checkbox',
                    'desc' => esc_html__('Uncheck this to prevent the customer from making any payment beyond deposit. (You\'ll have to manually mark the orders as completed)',
                        'Advanced Partial Payment and Deposit For Woocommerce'),
                    'id' => 'wc_deposits_remaining_payable',
                    'default' => 'yes',
                ),
            );

            $reminder_settings['second_payment_due_after'] = array(
                'name' => esc_html__('Days before Second Payment is due', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'type' => 'number',
                'desc' => esc_html__('Number of days before second payment is due ( if no payment plan with dates assigned, leave field empty for unlimited days )', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'id' => 'wc_deposits_second_payment_due_after',
                'default' => ''
            );
            $statuses = array();
            foreach (wc_get_is_paid_statuses() as $status) {
                $statuses[$status] = wc_get_order_status_name($status);
            }

            $reminder_settings['order_fully_paid_status'] = array(
                'name' => esc_html__('Order fully paid status', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'type' => 'select',
                'desc' => esc_html__('Order status when all partial payments are completed', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'id' => 'wc_deposits_order_fully_paid_status',
                'options' => $statuses
            );

            $reminder_settings['second_payment_settings_end'] = array(
                'type' => 'sectionend',
                'id' => 'wc_deposits_second_payment_settings_end'
            );

            $reminder_settings['reminder_settings'] = array(
                'name' => esc_html__('Reminder Email Settings', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'type' => 'title',
                'desc' => esc_html__('This section cover automation of reminder emails. ( You can always send a reminder manually from order actions ) ', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'id' => 'wc_deposits_reminder_settings_title'
            );

            $reminder_settings['enable_second_payment_reminder'] = array(
                'name' => esc_html__('Enable Partial Payment Reminder after "X" Days from deposit', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'type' => 'checkbox',
                'desc' => esc_html__('Check this to enable sending payment reminder email automatically after X number of days of deposit payment.',
                    'Advanced Partial Payment and Deposit For Woocommerce'),
                'id' => 'wc_deposits_enable_second_payment_reminder',
                'default' => 'no',
            );
            $reminder_settings['second_payment_reminder_duration'] = array(
                'name' => esc_html__('Partial Payment Reminder after "X" days from deposit', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'type' => 'number',
                'desc' => esc_html__('Duration between partial payment and payment reminder (in days)', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'id' => 'wc_deposits_second_payment_reminder_duration',
                'default' => '14'
            );
            $reminder_settings['enable_partial_payment_reminder'] = array(
                'name' => sprintf(esc_html__('Enable %s "X" days before due date', 'Advanced Partial Payment and Deposit For Woocommerce'), esc_html__('Partial Payment reminder', 'Advanced Partial Payment and Deposit For Woocommerce')),
                'type' => 'checkbox',
                'desc' => sprintf(esc_html__('Check this to enable %s "X" days before due date', 'Advanced Partial Payment and Deposit For Woocommerce'), esc_html__('Partial Payment reminder', 'Advanced Partial Payment and Deposit For Woocommerce')),
                'id' => 'wc_deposits_enable_partial_payment_reminder',
                'default' => 'yes',
            );
            $reminder_settings['partial_payment_reminder_x_days_before_due_date'] = array(
                'name' => esc_html__('Partial Payment Reminder "X" days before due date', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'type' => 'number',
                'desc' => esc_html__('Send a reminder email x days before partial payment due date', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'id' => 'wc_deposits_partial_payment_reminder_x_days_before_due_date',
                'default' => '3'
            );
            $reminder_settings['reminder_settings_end'] = array(
                'type' => 'sectionend',
                'id' => 'wc_deposits_reminder_settings_end'
            );

            $reminder_settings['custom_reminder_datepicker_title'] = array(
                'name' => esc_html__('Custom Remainder Email Settings', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'type' => 'title',
                'id' => 'wc_deposits_custom_reminder_datepicker_title'
            );

            $reminder_settings['reminder_datepicker'] = array(
                'type' => 'reminder_datepicker',
                'class' => 'reminder_datepicker_html',
            );
            $reminder_settings['custom_reminder_datepicker_end'] = array(
                'type' => 'sectionend',
                'id' => 'wc_deposits_custom_reminder_datepicker_end'
            );

            woocommerce_admin_fields($reminder_settings);

            ?>
            <?php do_action('wc_deposits_settings_tabs_second_payment_tab'); ?>

        </div>

        <?php
    }

    function tab_gateways_output($active)
    {
        $class = $active ? '' : 'hidden';

        ?>
        <div id="gateways" class="wcdp-tab-content wrap wcdp-custom-container <?php echo $class; ?>">

            <?php

            /*
     * Allowed gateways
     */

            $gateways_settings = array();

            $gateways_settings['gateways_title'] = array(
                'name' => esc_html__('Disallowed Gateways', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'type' => 'title',
                'desc' => esc_html__('Disallow the following gateways when there is a deposit in the cart.', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'id' => 'wc_deposits_gateways_title'
            );

            $gateways_array = array();
            $gateways = WC()->payment_gateways()->payment_gateways();
            if (isset($gateways['wc-booking-gateway'])) unset($gateways['wc-booking-gateway']);// Protect the wc-booking-gateway

            foreach ($gateways as $key => $gateway) {

                $gateways_array[$key] = $gateway->title;
            }


            $gateways_settings['wc_deposits_disallowed_gateways_for_deposit'] = array(
                'name' => esc_html__('Disallowed For Deposits', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'type' => 'multiselect',
                'class' => 'chosen_select',
                'options' => $gateways_array,
                'desc' => esc_html__('Disallowed For Deposits', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'id' => 'wc_deposits_disallowed_gateways_for_deposit',
            );

            $gateways_settings['wc_deposits_disallowed_gateways_for_second_payment'] = array(
                'name' => esc_html__('Disallowed For Partial Payments', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'type' => 'multiselect',
                'class' => 'chosen_select',
                'options' => $gateways_array,
                'desc' => esc_html__('Disallowed For Partial Payments', 'Advanced Partial Payment and Deposit For Woocommerce'),
                'id' => 'wc_deposits_disallowed_gateways_for_second_payment',
            );


            $gateways_settings['gateways_end'] = array(
                'type' => 'sectionend',
                'id' => 'wc_deposits_gateways_end'
            );


            woocommerce_admin_fields($gateways_settings);

            ?>
            <?php do_action('wc_deposits_settings_tabs_gateways_tab'); ?>

        </div>

        <?php
    }

   

    function tab_advanced_output($active)
    {
        $class = $active ? '' : 'hidden';
        ?>
        <div id="advanced" class="wcdp-tab-content wrap <?php echo $class; ?>">
            <?php

            $advanced_fields = array(
                'advanced_title' => array(
                    'name' => __('Advanced', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'wc_deposits_advanced_title'
                ),

                'advanced_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_deposits_advanced_end'
                )
            );
            woocommerce_admin_fields($advanced_fields);
            ?>
        </div>

        <?php
    }

    /*** END TABS CONTENT CALLBACKS **/

    /*** BEGIN DEPOSIT OPTIONS CUSTOM FIELDS CALLBACKS **/
    function reminder_datepicker()
    {

        $reminder_date = get_option('wc_deposits_reminder_datepicker');
        ob_start();

        ?>
        <script>
            jQuery(function ($) {
                'use strict';

                $("#reminder_datepicker").datepicker({

                    dateFormat: "dd-mm-yy",
                    minDate: new Date()

                }).datepicker("setDate", "<?php echo $reminder_date; ?>");
            });
        </script>
        <p>
            <b><?php echo esc_html__('If you would like to send out all partial payment reminders on a specific date in the future, set a date below.', 'Advanced Partial Payment and Deposit For Woocommerce'); ?></b>
        </p>
        <p> <?php echo esc_html__('Next Custom Reminder Date :', 'Advanced Partial Payment and Deposit For Woocommerce') ?> <input type="text"
         name="wc_deposits_reminder_datepicker"
            id="reminder_datepicker">
        </p>
        <?php
        echo ob_get_clean();
    }

    public function deposit_buttons_color()
    {

        $colors = get_option('wc_deposits_deposit_buttons_colors',array('primary'=>'','secondary'=>'','highlight'=>''));
        $primary_color = $colors['primary'];
        $secondary_color = $colors['secondary'];
        $highlight_color = $colors['highlight'];;

        ?>
        <tr class="">
            <th scope="row"
                class="titledesc"><?php echo esc_html__('Deposit Buttons Primary Colour', 'Advanced Partial Payment and Deposit For Woocommerce'); ?></th>
            <td class="forminp forminp-checkbox">
                <fieldset>
                    <input type="text" name="wc_deposits_deposit_buttons_colors_primary" class="deposits-color-field"
                           value="<?php echo $primary_color; ?>">
                </fieldset>
            </td>
        </tr>
        <tr class="">
            <th scope="row"
                class="titledesc"><?php echo esc_html__('Deposit Buttons Secondary Colour', 'Advanced Partial Payment and Deposit For Woocommerce'); ?></th>
            <td class="forminp forminp-checkbox">
                <fieldset>
                    <input type="text" name="wc_deposits_deposit_buttons_colors_secondary" class="deposits-color-field"
                           value="<?php echo $secondary_color; ?>">
                </fieldset>
            </td>
        </tr>
        <tr class="">
            <th scope="row"
                class="titledesc"><?php echo esc_html__('Deposit Buttons Highlight Colour', 'Advanced Partial Payment and Deposit For Woocommerce'); ?></th>
            <td class="forminp forminp-checkbox">
                <fieldset>
                    <input type="text" name="wc_deposits_deposit_buttons_colors_highlight" class="deposits-color-field"
                           value="<?php echo $highlight_color; ?>">
                </fieldset>
            </td>
        </tr>
        <?php
    }

    /*** END  DEPOSIT OPTIONS CUSTOM FIELDS CALLBACKS **/


   

    /**
     * @brief Save all settings on POST
     *
     * @return void
     */
    public function update_options_wc_deposits()
    {
        $allowed_html = array(
            'strong' => array(),
            'p' => array(),
            'br' => array(),
            'em' => array(),
            'b' => array(),
            's' => array(),
            'strike' => array(),
            'del' => array(),
            'u' => array(),
            'i' => array(),
            'a' => array(
                'target' => array(),
                'href' => array()
            )
        );

        $settings = array();


        $settings ['wc_deposits_site_wide_disable'] = isset($_POST['wc_deposits_site_wide_disable']) ? 'yes' : 'no';

        $settings['wc_deposits_default_option'] = isset($_POST['wc_deposits_default_option']) ?
            ($_POST['wc_deposits_default_option'] === 'deposit' ? 'deposit' : 'full') : 'deposit';

        $settings['wc_deposits_reduce_stock'] = isset($_POST['wc_deposits_reduce_stock']) ?
            ($_POST['wc_deposits_reduce_stock'] === 'deposit' ? 'deposit' : 'full') : 'full';
        $settings['wc_deposits_tax_display'] = isset($_POST['wc_deposits_tax_display']) ? 'yes' : 'no';
        $settings['wc_deposits_tax_display_cart_item'] = isset($_POST['wc_deposits_tax_display_cart_item']) ? 'yes' : 'no';
        $settings['wc_deposits_breakdown_cart_tooltip'] = isset($_POST['wc_deposits_breakdown_cart_tooltip']) ? 'yes' : 'no';
        $settings['wc_deposits_override_payment_form'] = isset($_POST['wc_deposits_override_payment_form']) ? 'yes' : 'no';
        $settings['wc_deposits_hide_ui_when_forced'] = isset($_POST['wc_deposits_hide_ui_when_forced']) ? 'yes' : 'no';
        $settings['wc_deposits_use_basic_radio_buttons'] = isset($_POST['wc_deposits_use_basic_radio_buttons']) ? 'yes' : 'no';

        $settings ['wc_deposits_partially_paid_orders_editable'] = isset($_POST['wc_deposits_partially_paid_orders_editable']) ? 'yes' : 'no';
        $settings ['wc_deposits_order_list_table_show_has_deposit'] = isset($_POST['wc_deposits_order_list_table_show_has_deposit']) ? 'yes' : 'no';
        $settings ['wc_deposits_disable_deposit_for_user_roles'] = isset($_POST['wc_deposits_disable_deposit_for_user_roles']) ? $_POST['wc_deposits_disable_deposit_for_user_roles'] : array();
        $settings ['wc_deposits_restrict_deposits_for_logged_in_users_only'] = isset($_POST['wc_deposits_restrict_deposits_for_logged_in_users_only']) ? 'yes' : 'no';


        //STRINGS
        $settings['wc_deposits_to_pay_text'] = isset($_POST['wc_deposits_to_pay_text']) ? esc_html($_POST['wc_deposits_to_pay_text']) : 'To Pay';
        $settings['wc_deposits_second_payment_text'] = isset($_POST['wc_deposits_second_payment_text']) ? esc_html($_POST['wc_deposits_second_payment_text']) : 'Future Payments';
        $settings['wc_deposits_deposit_amount_text'] = isset($_POST['wc_deposits_deposit_amount_text']) ? esc_html($_POST['wc_deposits_deposit_amount_text']) : 'Deposit Amount';
        $settings['wc_deposits_deposit_option_text'] = isset($_POST['wc_deposits_deposit_option_text']) ? esc_html($_POST['wc_deposits_deposit_option_text']) : 'Deposit Option';
        $settings['wc_deposits_payment_link_text'] = isset($_POST['wc_deposits_payment_link_text']) ? esc_html($_POST['wc_deposits_payment_link_text']) : 'Payment Link';

        $settings['wc_deposits_deposit_buttons_colors'] = array(

            'primary' => isset($_POST['wc_deposits_deposit_buttons_colors_primary']) ? $_POST['wc_deposits_deposit_buttons_colors_primary'] : false,
            'secondary' => isset($_POST['wc_deposits_deposit_buttons_colors_secondary']) ? $_POST['wc_deposits_deposit_buttons_colors_secondary'] : false,
            'highlight' => isset($_POST['wc_deposits_deposit_buttons_colors_highlight']) ? $_POST['wc_deposits_deposit_buttons_colors_highlight'] : false
        );

        $settings['wc_deposits_checkout_mode_enabled'] = isset($_POST['wc_deposits_checkout_mode_enabled']) ? $_POST['wc_deposits_checkout_mode_enabled'] : 'no';
        $settings['wc_deposits_checkout_mode_force_deposit'] = isset($_POST['wc_deposits_checkout_mode_force_deposit']) ? $_POST['wc_deposits_checkout_mode_force_deposit'] : 'no';
        $settings['wc_deposits_checkout_mode_deposit_amount'] = isset($_POST['wc_deposits_checkout_mode_deposit_amount']) ? $_POST['wc_deposits_checkout_mode_deposit_amount'] : '0';
        $settings['wc_deposits_checkout_mode_deposit_amount_type'] = isset($_POST['wc_deposits_checkout_mode_deposit_amount_type']) ? $_POST['wc_deposits_checkout_mode_deposit_amount_type'] : 'percentage';
        $settings['wc_deposits_checkout_mode_payment_plans'] = isset($_POST['wc_deposits_checkout_mode_payment_plans']) ? $_POST['wc_deposits_checkout_mode_payment_plans'] : array();

        $settings['wc_deposits_partial_payments_structure'] = isset($_POST['wc_deposits_partial_payments_structure']) ? $_POST['wc_deposits_partial_payments_structure'] : 'single';
        $settings['wc_deposits_fees_handling'] = isset($_POST['wc_deposits_fees_handling']) ? $_POST['wc_deposits_fees_handling'] : 'split';
        $settings['wc_deposits_taxes_handling'] = isset($_POST['wc_deposits_taxes_handling']) ? $_POST['wc_deposits_taxes_handling'] : 'split';
        $settings['wc_deposits_shipping_handling'] = isset($_POST['wc_deposits_shipping_handling']) ? $_POST['wc_deposits_shipping_handling'] : 'split';
        $settings['wc_deposits_coupons_handling'] = isset($_POST['wc_deposits_coupons_handling']) ? $_POST['wc_deposits_coupons_handling'] : 'full';



        $settings['wc_deposits_remaining_payable'] = isset($_POST['wc_deposits_remaining_payable']) ? 'yes' : 'no';
        $settings['wc_deposits_enable_second_payment_reminder'] = isset($_POST['wc_deposits_enable_second_payment_reminder']) ? 'yes' : 'no';
        $settings['wc_deposits_second_payment_due_after'] = isset($_POST['wc_deposits_second_payment_due_after']) ? $_POST['wc_deposits_second_payment_due_after'] : '';
        $settings['wc_deposits_second_payment_reminder_duration'] = isset($_POST['wc_deposits_second_payment_reminder_duration']) ? $_POST['wc_deposits_second_payment_reminder_duration'] : '0';
        $settings['wc_deposits_button_deposit'] = isset($_POST['wc_deposits_button_deposit']) ? esc_html($_POST['wc_deposits_button_deposit']) : esc_html__('Pay Deposit', 'Advanced Partial Payment and Deposit For Woocommerce');
        $settings['wc_deposits_button_full_amount'] = isset($_POST['wc_deposits_button_full_amount']) ? esc_html($_POST['wc_deposits_button_full_amount']) : esc_html__('Full Amount', 'Advanced Partial Payment and Deposit For Woocommerce');
        $settings['wc_deposits_message_deposit'] = isset($_POST['wc_deposits_message_deposit']) ? wp_kses($_POST['wc_deposits_message_deposit'], $allowed_html) : '';
        $settings['wc_deposits_message_full_amount'] = isset($_POST['wc_deposits_message_full_amount']) ? wp_kses($_POST['wc_deposits_message_full_amount'], $allowed_html) : '';


        //partial payment reminder
        $settings['wc_deposits_order_fully_paid_status'] = isset($_POST['wc_deposits_order_fully_paid_status']) ? $_POST['wc_deposits_order_fully_paid_status'] : 'processing';


        $settings['wc_deposits_enable_partial_payment_reminder'] = isset($_POST['wc_deposits_enable_partial_payment_reminder']) ? 'yes' : 'no';
        $settings['wc_deposits_partial_payment_reminder_x_days_before_due_date'] = isset($_POST['wc_deposits_partial_payment_reminder_x_days_before_due_date']) ? $_POST['wc_deposits_partial_payment_reminder_x_days_before_due_date'] : '3';

        //gateway options
        $settings ['wc_deposits_disallowed_gateways_for_deposit'] = isset($_POST['wc_deposits_disallowed_gateways_for_deposit']) ? $_POST['wc_deposits_disallowed_gateways_for_deposit'] : array();
        $settings ['wc_deposits_disallowed_gateways_for_second_payment'] = isset($_POST['wc_deposits_disallowed_gateways_for_second_payment']) ? $_POST['wc_deposits_disallowed_gateways_for_second_payment'] : array();


        //custom reminder date
        $settings['wc_deposits_reminder_datepicker'] = isset($_POST['wc_deposits_reminder_datepicker']) ? $_POST['wc_deposits_reminder_datepicker'] : '';


        //storewide deposit settings
         $settings['wc_deposits_storewide_deposit_enabled_details'] = $_POST['wc_deposits_storewide_deposit_enabled_details'] ?? 'no';
          $settings['wc_deposits_storewide_deposit_enabled_btn'] = $_POST['wc_deposits_storewide_deposit_enabled_btn'] ?? 'yes';
        $settings['wc_deposits_storewide_deposit_enabled'] = $_POST['wc_deposits_storewide_deposit_enabled'] ?? 'no';
        $settings['wc_deposits_storewide_deposit_force_deposit'] = isset($_POST['wc_deposits_storewide_deposit_force_deposit']) ? $_POST['wc_deposits_storewide_deposit_force_deposit'] : 'no';
        $settings['wc_deposits_storewide_deposit_amount'] = $_POST['wc_deposits_storewide_deposit_amount'] ?? '50';
        if(empty($_POST['wc_deposits_storewide_deposit_amount'])) $settings['wc_deposits_storewide_deposit_amount']  = '50';
        $settings['wc_deposits_storewide_deposit_amount_type'] = isset($_POST['wc_deposits_storewide_deposit_amount_type']) ? $_POST['wc_deposits_storewide_deposit_amount_type'] : 'percent';
        $settings['wc_deposits_storewide_deposit_payment_plans'] = isset($_POST['wc_deposits_storewide_deposit_payment_plans']) ? $_POST['wc_deposits_storewide_deposit_payment_plans'] : array();


        foreach ($settings as $key => $setting) {
            update_option($key, $setting);

        }


    }

}