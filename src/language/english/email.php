<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

/*
 * Texts of the e-mails in this language, see SimplecartEmailText
 */
return [
    'EMAIL_SUBJECT' => 'Order Confirmation #%d',
    'EMAIL_SUBJECT_CAMPAIGN' => 'Order Confirmation %2$s #%1$d',
    'EMAIL_GREETING' => 'Dear',
    'EMAIL_THANK_YOU' => 'Thank You for Your Order!',
    'EMAIL_ORDER_DETAILS' => 'Order Details',
    'ORDER_ID' => 'Order ID',
    'EMAIL_ORDER_DATE' => 'Order Date',
    'EMAIL_CUSTOMER_INFO' => 'Customer Information',
    'NAME' => 'Name',
    'EMAIL' => 'Email',
    'EMAIL_ITEMS' => 'Order Items',
    'EMAIL_UNIT_PRICE' => 'Unit Price',
    'EMAIL_QUANTITY' => 'Qty',
    'EMAIL_SUBTOTAL' => 'Subtotal',
    'TOTAL' => 'Total',
    'PAYMENT_INFO' => 'Payment Information',
    'BENEFICIARY' => 'Beneficiary',
    'IBAN' => 'IBAN',
    'AMOUNT' => 'Amount',
    'MAIL_PAYMENTINFO' => 'Please use the above information to pay by bank transfer. If you have any questions about your order, please contact us at colomaenpa@gmail.com with your order number as reference.',
    'EMAIL_FOOTER' => 'Thank you for your business. If you have any questions about your order, please contact us.',
    'PAYMENT_RECEIVED_SUBJECT' => 'Payment Received - Order #%d',
    'PAYMENT_RECEIVED_HEADING' => 'Payment Received!',
    'PAYMENT_RECEIVED_MESSAGE' => 'We have received your payment for order #%d. Thank you!',
    'SCAN_EMAIL_HEADING' => 'Your pickup QR code',
    'SCAN_EMAIL_TEXT' => 'Show this QR code when you collect your order. We will scan it to find your order.',
    'ANSWERS_HEADING' => 'Your answers',
    'DATE_FORMAT' => 'M j, Y H:i',

    // Only used to convert the former fixed fields and question; removed in the next release
    'LEGACY_PHONE' => 'Phone Number',
    'LEGACY_ADDRESS' => 'Address',
    'LEGACY_HELP' => 'Help Needed',
    'LEGACY_HELP_1' => 'Yes, I can help',
    'LEGACY_HELP_2' => 'I can help at a different time',
    'LEGACY_HELP_3' => 'I am available to help in more than one way',
    'LEGACY_HELP_4' => 'No, I am only coming for breakfast',
];
