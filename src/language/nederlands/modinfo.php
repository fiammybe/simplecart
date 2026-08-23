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
define('_MI_SIMPLECART_ORDER_SHIFT', 'Shift');
define('_MI_SIMPLECART_ORDER_HELPENDE_HAND', 'Helpende hand');

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
define('_MI_SIMPLECART_SEPA_BENEFICIARY_NAME', 'Naam van de bestemmeling');
define('_MI_SIMPLECART_SEPA_BENEFICIARY_NAME_DESC', 'De naam van de rekeninghouder voor SEPA betalingen');
define('_MI_SIMPLECART_SEPA_BENEFICIARY_IBAN', 'IBAN');
define('_MI_SIMPLECART_SEPA_BENEFICIARY_IBAN_DESC', 'Rekeningnummer');
define('_MI_SIMPLECART_SEPA_BENEFICIARY_BIC', 'BIC');
define('_MI_SIMPLECART_SEPA_BENEFICIARY_BIC_DESC', 'Bank Identifier Code (niet nodig voor betalingen tussen Belgische rekening)');
define('_MI_SIMPLECART_SEPA_CURRENCY', 'Munteenheid');
define('_MI_SIMPLECART_SEPA_CURRENCY_DESC', 'Payment Reference Prefix');
define('_MI_SIMPLECART_CONF_REF_PREFIX', 'Payment Reference Prefix');
define('_MI_SIMPLECART_CONF_REF_PREFIX_DESC', 'Prefix for the auto-generated payment reference (e.g. ORD-)');

// Permission names and descriptions
define('_MI_SIMPLECART_PERM_PRODUCT_VIEW', 'Producten bekijken');
define('_MI_SIMPLECART_PERM_PRODUCT_VIEW_DESC', 'Kan producten bekijken in het beheerdersgedeelte');
define('_MI_SIMPLECART_PERM_PRODUCT_CREATE', 'Producten aanmaken');
define('_MI_SIMPLECART_PERM_PRODUCT_CREATE_DESC', 'Kan nieuwe producten aanmaken');
define('_MI_SIMPLECART_PERM_PRODUCT_EDIT', 'Producten bewerken');
define('_MI_SIMPLECART_PERM_PRODUCT_EDIT_DESC', 'Kan bestaande producten bewerken');
define('_MI_SIMPLECART_PERM_PRODUCT_DELETE', 'Producten verwijderen');
define('_MI_SIMPLECART_PERM_PRODUCT_DELETE_DESC', 'Kan producten verwijderen');

define('_MI_SIMPLECART_PERM_ORDER_VIEW', 'Bestellingen bekijken');
define('_MI_SIMPLECART_PERM_ORDER_VIEW_DESC', 'Kan bestellingen bekijken in het beheerdersgedeelte');
define('_MI_SIMPLECART_PERM_ORDER_EDIT', 'Bestellingen bewerken');
define('_MI_SIMPLECART_PERM_ORDER_EDIT_DESC', 'Kan bestellingsstatus bewerken');
define('_MI_SIMPLECART_PERM_ORDER_DELETE', 'Bestellingen verwijderen');
define('_MI_SIMPLECART_PERM_ORDER_DELETE_DESC', 'Kan bestellingen verwijderen');
