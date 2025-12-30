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
define('_MI_SIMPLECART_ORDER_PAYMENT_REF', 'Payment Reference');
define('_MI_SIMPLECART_ORDER_SHIFT', 'Shift');
define('_MI_SIMPLECART_ORDER_HELPENDE_HAND', 'Help');

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

// Configs
define('_MI_SIMPLECART_CONF_BENEFICIARY', 'Beneficiary Name');
define('_MI_SIMPLECART_CONF_BENEFICIARY_DESC', 'Name of the account holder for SEPA payments');
define('_MI_SIMPLECART_CONF_IBAN', 'IBAN');
define('_MI_SIMPLECART_CONF_IBAN_DESC', 'International Bank Account Number');
define('_MI_SIMPLECART_CONF_BIC', 'BIC');
define('_MI_SIMPLECART_CONF_BIC_DESC', 'Bank Identifier Code');
define('_MI_SIMPLECART_CONF_REF_PREFIX', 'Payment Reference Prefix');
define('_MI_SIMPLECART_CONF_REF_PREFIX_DESC', 'Prefix for the auto-generated payment reference (e.g. ORD-)');
