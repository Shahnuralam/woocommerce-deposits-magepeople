<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_Deposits_Email_Customer_Remaining_Reminder')) :

    /**
     * Customer Partially Paid Email
     *
     * An email sent to the customer when a new order is partially paid.
     *
     */
    class WC_Deposits_Email_Customer_Remaining_Reminder extends WC_Email
    {

        public $partial_payment = false;

        /**
         * Constructor
         */
        function __construct()
        {

            $this->id = 'customer_second_payment_reminder';
            $this->title = esc_html__('Partial Payment Reminder', 'Advanced Partial Payment and Deposit For Woocommerce');
            $this->description = esc_html__('Reminder of partially-paid order sent to the customer', 'Advanced Partial Payment and Deposit For Woocommerce');
            $this->customer_email = true;


            $this->template_html = 'emails/customer-order-remaining-reminder.php';
            $this->template_plain = 'emails/plain/customer-order-remaining-reminder.php';
            // Triggers for this email
            add_action('woocommerce_deposits_second_payment_reminder_email_notification', array($this, 'trigger'), 10, 3);

            // Call parent constructor
            parent::__construct();
            $this->template_base = WC_DEPOSITS_TEMPLATE_PATH;
        }

        public function get_default_subject()
        {
            return esc_html__('Your {site_title} order partial payment reminder {order_date}', 'Advanced Partial Payment and Deposit For Woocommerce');
        }

        public function get_subject()
        {
            $subject = $this->get_option('subject', $this->get_default_subject());

            return $this->format_string($subject);
        }

        public function get_default_heading()
        {
            return esc_html__('Partial Payment Reminder #{order_number}', 'Advanced Partial Payment and Deposit For Woocommerce');
        }

        public function get_heading()
        {
            $heading = $this->get_option('heading', $this->get_default_heading());

            return $this->format_string($heading);

        }


        public function get_default_email_text()
        {
            return esc_html__("Kindly be reminded that your order's partial payment is still pending payment.", 'Advanced Partial Payment and Deposit For Woocommerce');

        }

        public function get_default_payment_text()
        {
            return esc_html__('To make payment, please visit this link: {wcdp_payment_link}', 'Advanced Partial Payment and Deposit For Woocommerce');
        }

        function get_email_text()
        {
            $text = $this->get_option('email_text', $this->get_default_email_text());
            return $this->format_string($text);
        }

        function get_payment_text()
        {
            $text = $this->get_option('payment_text', $this->get_default_payment_text());
            return $this->format_string($text);
        }


        /**
         * trigger function.
         *
         * @access public
         * @return void
         */
        function trigger($order_id, $partial_payment = false)
        {

            if ($order_id) {
                $this->object = wc_get_order($order_id);

                $this->recipient = $this->object->get_billing_email();

                if (is_a('WCDP_Payment', $partial_payment)) {
                    $this->partial_payment = $partial_payment;
                }
                $this->placeholders['{order_date}'] = wc_format_datetime($this->object->get_date_created());
                $this->placeholders['{order_number}'] = $this->object->get_order_number();
                $valid_statuses = wcdp_valid_parent_statuses_for_partial_payment();

                if ($this->object->needs_payment() && in_array($this->object->get_status(), $valid_statuses) && get_option('wc_deposits_remaining_payable', 'yes') === 'yes') {
                    $payment_link_text = get_option('wc_deposits_payment_link_text', esc_html__('Payment Link', 'Advanced Partial Payment and Deposit For Woocommerce'));
                    if (empty($payment_link_text)) {
                        $payment_link_text = esc_html__('Payment Link', 'Advanced Partial Payment and Deposit For Woocommerce');
                    }
                    $this->placeholders['{wcdp_payment_link}'] = '<a href="' . esc_url($this->object->get_checkout_payment_url()) . '">' . $payment_link_text . '</a>';
                } else {
                    $this->placeholders['{wcdp_payment_link}'] = '';
                }

                if (!$this->is_enabled() || !$this->get_recipient()) {
                    return;
                }

                $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

            }
        }

        /**
         * get_content_html function.
         *
         * @access public
         */
        function get_content_html()
        {


            return wc_get_template_html($this->template_html, array(
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => version_compare(WOOCOMMERCE_VERSION, '3.7.0', '<') ? '' : $this->get_additional_content(),
                'email_text' => $this->get_email_text(),
                'payment_text' => $this->get_payment_text(),
                'partial_payment' => $this->partial_payment,
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this,
            ), '', $this->template_base);

        }

        /**
         * get_content_plain function.
         *
         * @access public
         */
        function get_content_plain()
        {

            return wc_get_template_html($this->template_html, array(
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'email_text' => $this->get_email_text(),
                'payment_text' => $this->get_payment_text(),
                'additional_content' => version_compare(WOOCOMMERCE_VERSION, '3.7.0', '<') ? '' : $this->get_additional_content(), 'sent_to_admin' => false,
                'partial_payment' => $this->partial_payment,
                'plain_text' => true,
                'email' => $this,
            ), '', $this->template_base);
        }


        public function init_form_fields()
        {
            /* translators: %s: list of placeholders */
            $placeholder_text = sprintf(wp_kses(__('Available placeholders: %s', 'woocommerce'), array('code' => array())), '<code>' . esc_html(implode('</code>, <code>', array_keys($this->placeholders))) . '</code>');
            $this->form_fields = array(
                'enabled' => array(
                    'title' => esc_html__('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => esc_html__('Enable this email notification', 'woocommerce'),
                    'default' => 'yes',
                ),
                'subject' => array(
                    'title' => esc_html__('Partial payment reminder subject', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'type' => 'text',
                    'desc_tip' => true,
                    'description' => sprintf(esc_html__('For partial payment reminders (%s)', 'Advanced Partial Payment and Deposit For Woocommerce'), esc_html__('Payment plans', 'Advanced Partial Payment and Deposit For Woocommerce')),
                    'placeholder' => $this->get_default_subject(),
                    'default' => $this->get_default_subject(),
                ),
                'heading' => array(
                    'title' => esc_html__('Partial payment reminder heading', 'woocommerce'),
                    'type' => 'text',
                    'desc_tip' => true,
                    'description' => $placeholder_text,
                    'placeholder' => $this->get_default_heading(),
                    'default' => $this->get_default_heading(),
                ),
                'email_text' => array(
                    'title' => esc_html__('Email text', 'woocommerce'),
                    'placeholder' => $this->get_default_email_text(),
                    'default' => $this->get_default_email_text(),
                    'css' => 'width:400px; height: 75px;',
                    'type' => 'textarea',
                    'desc_tip' => true,
                ),
                'payment_text' => array(
                    'title' => esc_html__('Payment text', 'Advanced Partial Payment and Deposit For Woocommerce'),
                    'description' => esc_html__('Text to appear with payment link', 'Advanced Partial Payment and Deposit For Woocommerce') . ' ' . $placeholder_text,
                    'placeholder' => $this->get_default_payment_text(),
                    'default' => $this->get_default_payment_text(),
                    'css' => 'width:400px; height: 75px;',
                    'type' => 'textarea',
                    'desc_tip' => true,
                ),
                'additional_content' => array(
                    'title' => esc_html__('Additional content', 'woocommerce'),
                    'description' => esc_html__('Text to appear below the main email content.', 'woocommerce') . ' ' . $placeholder_text,
                    'css' => 'width:400px; height: 75px;',
                    'placeholder' => esc_html__('N/A', 'woocommerce'),
                    'type' => 'textarea',
                    'default' => $this->get_default_additional_content(),
                    'desc_tip' => true,
                ),
                'email_type' => array(
                    'title' => esc_html__('Email type', 'woocommerce'),
                    'type' => 'select',
                    'description' => esc_html__('Choose which format of email to send.', 'woocommerce'),
                    'default' => 'html',
                    'class' => 'email_type wc-enhanced-select',
                    'options' => $this->get_email_type_options(),
                    'desc_tip' => true,
                ),
            );
        }
    }

endif;

return new WC_Deposits_Email_Customer_Remaining_Reminder();
