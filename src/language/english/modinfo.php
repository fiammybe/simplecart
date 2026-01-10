<?php
// Module Info
define('_MI_SIMPLECART_NAME', 'SimpleCart');
define('_MI_SIMPLECART_DESC', 'Simple e-commerce shopping cart for ImpressCMS');

// Admin menu
define('_MI_SIMPLECART_MENU_PRODUCTS', 'Products');
define('_MI_SIMPLECART_MENU_ORDERS', 'Orders');

// Product fields (labels)
define('_MI_SIMPLECART_PRODUCT_NAME', 'Name');
define('_MI_SIMPLECART_PRODUCT_PRICE', 'Price');
define('_MI_SIMPLECART_PRODUCT_DESC', 'Description');
define('_MI_SIMPLECART_PRODUCT_ACTIVE', 'Active');

// Order fields
define('_MI_SIMPLECART_ORDER_TIMESTAMP', 'Date');
define('_MI_SIMPLECART_ORDER_TOTAL', 'Total');
define('_MI_SIMPLECART_ORDER_STATUS', 'Status');
define('_MI_SIMPLECART_ORDER_CUSTOMER_INFO', 'Customer info');

// Order item fields
define('_MI_SIMPLECART_ORDERITEM_ORDER_ID', 'Order');
define('_MI_SIMPLECART_ORDERITEM_PRODUCT_NAME', 'Product');
define('_MI_SIMPLECART_ORDERITEM_PRODUCT_PRICE', 'Price');
define('_MI_SIMPLECART_ORDERITEM_QUANTITY', 'Quantity');
define('_MI_SIMPLECART_ORDERITEM_SUBTOTAL', 'Subtotal');

// Status options
define('_MI_SIMPLECART_STATUS_PENDING', 'Pending');
define('_MI_SIMPLECART_STATUS_COMPLETED', 'Completed');
define('_MI_SIMPLECART_STATUS_CANCELLED', 'Cancelled');

// SEPA Payment Configuration
define('_MI_SIMPLECART_SEPA_BENEFICIARY_NAME', 'SEPA Beneficiary Name');
define('_MI_SIMPLECART_SEPA_BENEFICIARY_NAME_DESC', 'The name of the beneficiary (your shop name) that will appear on SEPA QR codes. Maximum 70 characters.');
define('_MI_SIMPLECART_SEPA_BENEFICIARY_IBAN', 'SEPA Beneficiary IBAN');
define('_MI_SIMPLECART_SEPA_BENEFICIARY_IBAN_DESC', 'The IBAN (International Bank Account Number) of the beneficiary. This is required for SEPA QR code generation. Example: DE89370400440532013000');
define('_MI_SIMPLECART_SEPA_BENEFICIARY_BIC', 'SEPA Beneficiary BIC');
define('_MI_SIMPLECART_SEPA_BENEFICIARY_BIC_DESC', 'The BIC (Bank Identifier Code) of the beneficiary. This is optional but recommended. Example: COBADEFFXXX');
define('_MI_SIMPLECART_SEPA_CURRENCY', 'SEPA Currency Code');
define('_MI_SIMPLECART_SEPA_CURRENCY_DESC', 'The ISO 4217 currency code for SEPA payments. Default is EUR (Euro). Only EUR is recommended for SEPA transfers.');
?>
