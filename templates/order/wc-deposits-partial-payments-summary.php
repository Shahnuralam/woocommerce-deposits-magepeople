<?php
/**
 * Order details Summary
 *
 * This template displays a summary of partial payments
 *
 * @package MagePeople\WCDP\Templates
 * @version 3.2.6
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!$order = wc_get_order($order_id)) {
    return;
}
?> 

<h2 class="woocommerce-column__title" style="background-color: #f2f2f2; padding: 10px;"><?php echo esc_html__('Partial payments summary', 'Advanced Partial Payment and Deposit For Woocommerce') ?></h2>

<table class="woocommerce-table woocommerce_deposits_parent_order_summary" style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background-color: #ddd;">
            <th style="padding: 10px;background: yellow;"><?php echo esc_html__('Payment', 'Advanced Partial Payment and Deposit For Woocommerce'); ?></th>
            <th style="padding: 10px;background: yellow;"><?php echo esc_html__('Payment ID', 'Advanced Partial Payment and Deposit For Woocommerce'); ?></th>
            <th style="padding: 10px;background: yellow;"><?php echo esc_html__('Status', 'Advanced Partial Payment and Deposit For Woocommerce'); ?></th>
            <th style="padding: 10px;background: yellow;"><?php echo esc_html__('Amount', 'Advanced Partial Payment and Deposit For Woocommerce'); ?></th>
            <th style="padding: 10px;background: yellow;"><?php echo esc_html__('Payment Options', 'Advanced Partial Payment and Deposit For Woocommerce'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($schedule as $timestamp => $payment) :

            $title = isset($payment['title']) ? $payment['title'] : (is_numeric($timestamp) ? date_i18n(wc_date_format(), $timestamp) : '-');
            $title = apply_filters('wc_deposits_partial_payment_title', $title, $payment);

            if (!isset($payment['id']) || empty($payment['id'])) continue;
            $payment_order = wc_get_order($payment['id']);

            if (!$payment_order) continue;
            $payment_id = $payment_order->get_order_number();
            $status = wc_get_order_status_name($payment_order->get_status());
            $amount = $payment_order->get_total();
            $price_args = array('currency' => $payment_order->get_currency());

            $link = '';
            // New conditional check to show the "Pay Now" button only if the status indicates pending payment
    if ($payment_order->get_status() === 'pending') {
        $show_pay_now_button = true;
    } else {
        $show_pay_now_button = false;
    }

            if (is_account_page() && function_exists('WPO_WCPDF')) {
                $documents = WPO_WCPDF()->documents->get_documents();

                if ($documents) {
                    foreach ($documents as $document) {

                        if ($document->is_enabled() && $document->get_type() === 'partial_payment_invoice') {

                            $invoice = wcpdf_get_document('partial_payment_invoice', $payment_order, false);
                            $button_setting = $invoice->get_setting('my_account_buttons', 'available');

                            switch ($button_setting) {
                                case 'available':
                                    $invoice_allowed = $invoice->exists();
                                    break;
                                case 'always':
                                    $invoice_allowed = true;
                                    break;
                                case 'never':
                                    $invoice_allowed = false;
                                    break;
                                case 'custom':
                                    $allowed_statuses = $invoice->get_setting('my_account_restrict', array());
                                    $invoice_allowed = !empty($allowed_statuses) && in_array($payment_order->get_status(), array_keys($allowed_statuses));
                                    break;
                            }

                            $classes = $invoice && $invoice->exists() ? 'wcdp_invoice_exists' : '';

                            if ($invoice_allowed) {
                                $link .= '<a class="button btn ' . $classes . '" href="' . wp_nonce_url(admin_url("admin-ajax.php?action=generate_wpo_wcpdf&document_type=partial_payment_invoice&order_ids=" . $payment_order->get_id()), 'generate_wpo_wcpdf') . '">' . esc_html__('PDF Invoice', 'Advanced Partial Payment and Deposit For Woocommerce') . '</a>';
                            }
                        }
                    }
                }
            }
        ?>
            <tr class="order_item" style="background-color: #fff;">
                <td style="padding: 10px;border: 1px solid grey;"><?php echo $title; ?></td>
                <td style="padding: 10px;border: 1px solid grey;"><?php echo $payment_id; ?></td>
                <td style="padding: 10px;border: 1px solid grey;"><?php echo $status; ?></td>
                <td style="padding: 10px;border: 1px solid grey;"><?php echo wc_price($amount, $price_args); ?></td>
                <td style="padding: 10px;border: 1px solid grey;">
                <?php if ($show_pay_now_button) : ?>
                    <button class="pay-now-btn" data-order-id="<?php echo esc_attr($order->get_id()); ?>" style="padding: 10px; background-color: #4CAF50; color: white; border: none; cursor: pointer; border-radius: 5px;">
                        <?php esc_html_e('Pay Now', 'Advanced Partial Payment and Deposit For Woocommerce'); ?>
                    </button>
                <?php endif; ?>
            </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
    (function($) {
        $(document).ready(function() {
            $('.pay-now-btn').click(function() {
                var orderId = $(this).data('order-id');
                if (orderId) {
                    window.location.href = '<?php echo esc_url(wc_get_checkout_url()); ?>' + '?order-pay=' + orderId;
                }
            });
        });
    })(jQuery);
</script>
