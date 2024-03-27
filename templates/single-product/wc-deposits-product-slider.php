<?php
// Retrieve the value of wc_deposits_storewide_deposit_amount_type and wc_deposits_storewide_deposit_amount
$storewide_deposit_amount_type = get_option('wc_deposits_storewide_deposit_amount_type');
$storewide_deposit_amount = get_option('wc_deposits_storewide_deposit_amount');
$storewide_deposit_enabled_details = get_option('wc_deposits_storewide_deposit_enabled_details');

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

do_action('wc_deposits_enqueue_product_scripts');
if ($force_deposit === 'yes') $default_checked = 'deposit';
$hide = get_option('wc_deposits_hide_ui_when_forced', 'no') === 'yes';

// Check if storewide deposit details are enabled
if ($storewide_deposit_enabled_details === 'yes') {
    // Display the deposit option
?>
    <div data-ajax-refresh="<?php echo $ajax_refresh; ?>" data-product_id="<?php echo $product->get_id(); ?>" class='webtomizer_wcdp_single_deposit_form <?php echo $basic_buttons ? 'basic-wc-deposits-options-form' : 'wc-deposits-options-form'; ?>'>

        <?php
        if (!$has_payment_plans && $product->get_type() !== 'grouped') { ?>
            <label class='deposit-option'>
                <!--<hr class='separator' />-->
                Partial Payment Details:
                <br>
                Deposit:
                <?php if ($product->get_type() === 'variable' && $deposit_info['type'] === 'percent') {
                    ?> <span id='deposit-amount'><?php echo $deposit_amount . '%'; ?></span><?php
                } else {
                    ?> <span id='deposit-amount'><?php echo wc_price($deposit_amount); ?></span><?php
                } ?>
                <br>
                Deposit Type:
                <?php
                $inherit_storewide_settings = get_post_meta($product->get_id(), '_wc_deposits_inherit_storewide_settings', true);
                $enable_deposit = get_post_meta($product->get_id(), '_wc_deposits_enable_deposit', true);
                
                if ($inherit_storewide_settings === 'no' && $enable_deposit === 'yes') {
                    // Display custom deposit amount and type if enabled
                    $deposit_amount_type = get_post_meta($product->get_id(), '_wc_deposits_amount_type', true);
                    $deposit_amount = get_post_meta($product->get_id(), '_wc_deposits_deposit_amount', true);

                    if ($deposit_amount && $deposit_amount_type) { ?>
                        <span id='wc_deposits_storewide_deposit_amount'><?php echo esc_html($deposit_amount); ?></span>
                        <span id='wc_deposits_storewide_deposit_amount_type'><?php echo esc_html($deposit_amount_type); ?></span>
                    <?php }
                } elseif ($inherit_storewide_settings === 'yes' && $enable_deposit === 'yes') {
                    // Display storewide deposit amount and type if enabled
                    if ($storewide_deposit_amount && $storewide_deposit_amount_type) { ?>
                        <span id='wc_deposits_storewide_deposit_amount'><?php echo esc_html($storewide_deposit_amount); ?></span>
                        <span id='wc_deposits_storewide_deposit_amount_type'><?php echo esc_html($storewide_deposit_amount_type); ?></span>
                    <?php }
                }
                ?>
            </label>
        <?php }
        ?>

        <div class="<?php echo $hide ? 'wcdp_hidden ' : '' ?>  <?php echo $basic_buttons ? 'basic-switch-Advanced Partial Payment and Deposit For Woocommerce' : 'deposit-options switch-toggle switch-candy switch-Advanced Partial Payment and Deposit For Woocommerce'; ?>">
            <input id='<?php echo $product->get_id(); ?>-pay-deposit' class='pay-deposit input-radio' name='<?php echo $product->get_id(); ?>-deposit-radio' type='radio' <?php checked($default_checked, 'deposit'); ?> value='deposit'>
            <label class="pay-deposit-label" for='<?php echo $product->get_id(); ?>-pay-deposit'><?php esc_html_e($deposit_text, 'Advanced Partial Payment and Deposit For Woocommerce'); ?></label>
            <input id='<?php echo $product->get_id(); ?>-pay-full-amount' class='pay-full-amount input-radio' name='<?php echo $product->get_id(); ?>-deposit-radio' type='radio' <?php checked($default_checked, 'full'); ?>
                <?php echo isset($force_deposit) && $force_deposit === 'yes' ? 'disabled' : '' ?> value="full">
            <label class="pay-full-amount-label" for='<?php echo $product->get_id(); ?>-pay-full-amount'><?php esc_html_e($full_text, 'Advanced Partial Payment and Deposit For Woocommerce'); ?></label>
            <a class='wc-deposits-switcher'></a>
        </div>
        <span class='deposit-message wc-deposits-notice'></span>
        <?php
        if ($has_payment_plans) { ?>
            <div class="wcdp-payment-plans">
                <fieldset>
                    <ul>
                        <?php
                        $count = 0;
                        foreach ($payment_plans as $plan_id => $payment_plan) {
                            wc_get_template('single-product/wc-deposits-product-single-plan.php',
                                array('count' => $count,
                                    'plan_id' => $plan_id,
                                    'deposit_text' => $deposit_text,
                                    'payment_plan' => $payment_plan,
                                    'product' => $product),
                                '', WC_DEPOSITS_TEMPLATE_PATH);
                            $count++;
                        } ?>
                    </ul>
                </fieldset>
            </div>
<?php }
    ?>
    </div>
<?php
} // End if storewide deposit details are enabled
?>





<script>
    jQuery(document).ready(function($) {
        // Hide deposit-option initially if pay-full-amount is checked
        if ($('#<?php echo $product->get_id(); ?>-pay-full-amount').is(':checked')) {
            $('.deposit-option').hide();
        }

        // Toggle deposit-option visibility on radio button change
        $('input[name="<?php echo $product->get_id(); ?>-deposit-radio"]').change(function() {
            if ($(this).val() === 'full') {
                $('.deposit-option').hide();
            } else {
                $('.deposit-option').show();
            }
        });
    });
</script>
