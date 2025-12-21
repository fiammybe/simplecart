<?php
// Module Info
define('_MI_SIMPLECART_NAME', 'SimpleCart');
define('_MI_SIMPLECART_DESC', 'Simple e-commerce shopping cart for ImpressCMS');

// Admin menu
define('_MI_SIMPLECART_MENU_PRODUCTS', 'Producten');
define('_MI_SIMPLECART_MENU_ORDERS', 'Bestellingen');

// Product fields (labels)
define('_MI_SIMPLECART_PRODUCT_NAME', 'Naam');
define('_MI_SIMPLECART_PRODUCT_PRICE', 'Prijs');
define('_MI_SIMPLECART_PRODUCT_DESC', 'Beschrijving');
define('_MI_SIMPLECART_PRODUCT_ACTIVE', 'Actief');

// Order fields
define('_MI_SIMPLECART_ORDER_TIMESTAMP', 'Datum');
define('_MI_SIMPLECART_ORDER_TOTAL', 'Totaal');
define('_MI_SIMPLECART_ORDER_STATUS', 'Status');
define('_MI_SIMPLECART_ORDER_CUSTOMER_INFO', 'Klant info');
define('_MI_SIMPLECART_ORDER_PAYMENT_REF', 'Betalingsreferentie');

// Order item fields
define('_MI_SIMPLECART_ORDERITEM_ORDER_ID', 'Bestelling');
define('_MI_SIMPLECART_ORDERITEM_PRODUCT_NAME', 'Product');
define('_MI_SIMPLECART_ORDERITEM_PRODUCT_PRICE', 'Prijs');
define('_MI_SIMPLECART_ORDERITEM_QUANTITY', 'Hoeveelheid');
define('_MI_SIMPLECART_ORDERITEM_SUBTOTAL', 'Subtotaal');

// Status options
define('_MI_SIMPLECART_STATUS_PENDING', 'In Afwachting');
define('_MI_SIMPLECART_STATUS_COMPLETED', 'Voltooid');
define('_MI_SIMPLECART_STATUS_CANCELLED', 'Geannuleerd');

// Configs
define('_MI_SIMPLECART_CONF_BENEFICIARY', 'Naam van de bestemmeling');
define('_MI_SIMPLECART_CONF_BENEFICIARY_DESC', 'De naam van de rekeninghouder voor SEPA betalingen');
define('_MI_SIMPLECART_CONF_IBAN', 'IBAN');
define('_MI_SIMPLECART_CONF_IBAN_DESC', 'Rekeningnummer');
define('_MI_SIMPLECART_CONF_BIC', 'BIC');
define('_MI_SIMPLECART_CONF_BIC_DESC', 'Bank Identifier Code (niet nodig voor betalingen tussen Belgische rekening)');
define('_MI_SIMPLECART_CONF_REF_PREFIX', 'Payment Reference Prefix');
define('_MI_SIMPLECART_CONF_REF_PREFIX_DESC', 'Prefix for the auto-generated payment reference (e.g. ORD-)');
