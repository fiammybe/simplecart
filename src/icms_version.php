<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

$modversion = array();
$modversion['name'] = _MI_SIMPLECART_NAME;
$modversion['version'] = '1.1.3';
$modversion['description'] = _MI_SIMPLECART_DESC;
$modversion['author'] = 'Augment Agent';
$modversion['credits'] = 'ImpressCMS, IPF';
$modversion['license'] = 'MIT';
$modversion['dirname'] = 'simplecart';
$modversion['image'] = 'assets/images/module_logo.png';

$modversion['hasMain'] = 1;
$modversion['hasAdmin'] = 1;
$modversion['system_menu'] = 1;
$modversion['adminindex'] = 'admin/index.php';
$modversion['adminmenu'] = 'admin/menu.php';

$modversion['sqlfile']['mysql'] = 'sql/mysql.sql';
$modversion['tables'] = array(
    'simplecart_product',
    'simplecart_order',
    'simplecart_orderitem'
);

$modversion['templates'][] = array('file' => 'simplecart_index.html', 'description' => 'SimpleCart Front Index');
$modversion['templates'][] = array('file' => 'simplecart_checkout.html', 'description' => 'SimpleCart Checkout');
$modversion['templates'][] = array('file' => 'simplecart_order_confirm.html', 'description' => 'SimpleCart Order Confirmation');
$modversion['templates'][] = array('file' => 'simplecart_admin_product.html', 'description' => 'Admin - Products');
$modversion['templates'][] = array('file' => 'simplecart_admin_order.html', 'description' => 'Admin - Orders');

$modversion['hasSearch'] = 0;
$modversion['hasComments'] = 0;
$modversion['hasNotification'] = 0;

// Configuration items for SEPA payment
$modversion['config'] = array();

// SEPA Beneficiary Name
$modversion['config'][] = array(
    'name' => 'sepa_beneficiary_name',
    'title' => '_MI_SIMPLECART_SEPA_BENEFICIARY_NAME',
    'description' => '_MI_SIMPLECART_SEPA_BENEFICIARY_NAME_DESC',
    'formtype' => 'text',
    'valuetype' => 'text',
    'default' => 'SimpleCart Shop',
    'weight' => 1
);

// SEPA Beneficiary IBAN
$modversion['config'][] = array(
    'name' => 'sepa_beneficiary_iban',
    'title' => '_MI_SIMPLECART_SEPA_BENEFICIARY_IBAN',
    'description' => '_MI_SIMPLECART_SEPA_BENEFICIARY_IBAN_DESC',
    'formtype' => 'text',
    'valuetype' => 'text',
    'default' => '',
    'weight' => 2
);

// SEPA Beneficiary BIC
$modversion['config'][] = array(
    'name' => 'sepa_beneficiary_bic',
    'title' => '_MI_SIMPLECART_SEPA_BENEFICIARY_BIC',
    'description' => '_MI_SIMPLECART_SEPA_BENEFICIARY_BIC_DESC',
    'formtype' => 'text',
    'valuetype' => 'text',
    'default' => '',
    'weight' => 3
);

// SEPA Currency Code
$modversion['config'][] = array(
    'name' => 'sepa_currency',
    'title' => '_MI_SIMPLECART_SEPA_CURRENCY',
    'description' => '_MI_SIMPLECART_SEPA_CURRENCY_DESC',
    'formtype' => 'text',
    'valuetype' => 'text',
    'default' => 'EUR',
    'weight' => 4
);

?>
