<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

$modversion = array();
$modversion['name'] = _MI_SIMPLECART_NAME;
$modversion['version'] = '1.3.0';
$modversion['description'] = _MI_SIMPLECART_DESC;
$modversion['author'] = 'Augment Agent';
$modversion['credits'] = 'ImpressCMS, IPF';
$modversion['license'] = 'MIT';
$modversion['dirname'] = 'simplecart';
$modversion['image'] = 'assets/images/module_logo.png';
+
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


$modversion['templates'][] = array('file' => 'simplecart_index.html.tpl', 'description' => 'SimpleCart Front Index');
$modversion['templates'][] = array('file' => 'simplecart_checkout.html.tpl', 'description' => 'SimpleCart Checkout');
$modversion['templates'][] = array('file' => 'simplecart_order_confirm.html.tpl', 'description' => 'SimpleCart Order Confirmation');
$modversion['templates'][] = array('file' => 'simplecart_admin_product.html.tpl', 'description' => 'Admin - Products');
$modversion['templates'][] = array('file' => 'simplecart_admin_order.html.tpl', 'description' => 'Admin - Orders');
$modversion['templates'][] = array('file' => 'simplecart_admin_dashboard.html', 'description' => 'Admin - Dashboard');

$modversion['hasSearch'] = 0;
$modversion['hasComments'] = 0;
$modversion['hasNotification'] = 0;

// Permission definitions for granular access control
$i = 0;

// Product permissions
$modversion['permissions'][$i]['name'] = 'simplecart_product_view';
$modversion['permissions'][$i]['title'] = '_MI_SIMPLECART_PERM_PRODUCT_VIEW';
$modversion['permissions'][$i]['description'] = '_MI_SIMPLECART_PERM_PRODUCT_VIEW_DESC';
$i++;

$modversion['permissions'][$i]['name'] = 'simplecart_product_create';
$modversion['permissions'][$i]['title'] = '_MI_SIMPLECART_PERM_PRODUCT_CREATE';
$modversion['permissions'][$i]['description'] = '_MI_SIMPLECART_PERM_PRODUCT_CREATE_DESC';
$i++;

$modversion['permissions'][$i]['name'] = 'simplecart_product_edit';
$modversion['permissions'][$i]['title'] = '_MI_SIMPLECART_PERM_PRODUCT_EDIT';
$modversion['permissions'][$i]['description'] = '_MI_SIMPLECART_PERM_PRODUCT_EDIT_DESC';
$i++;

$modversion['permissions'][$i]['name'] = 'simplecart_product_delete';
$modversion['permissions'][$i]['title'] = '_MI_SIMPLECART_PERM_PRODUCT_DELETE';
$modversion['permissions'][$i]['description'] = '_MI_SIMPLECART_PERM_PRODUCT_DELETE_DESC';
$i++;

// Order permissions
$modversion['permissions'][$i]['name'] = 'simplecart_order_view';
$modversion['permissions'][$i]['title'] = '_MI_SIMPLECART_PERM_ORDER_VIEW';
$modversion['permissions'][$i]['description'] = '_MI_SIMPLECART_PERM_ORDER_VIEW_DESC';
$i++;

$modversion['permissions'][$i]['name'] = 'simplecart_order_edit';
$modversion['permissions'][$i]['title'] = '_MI_SIMPLECART_PERM_ORDER_EDIT';
$modversion['permissions'][$i]['description'] = '_MI_SIMPLECART_PERM_ORDER_EDIT_DESC';
$i++;

$modversion['permissions'][$i]['name'] = 'simplecart_order_delete';
$modversion['permissions'][$i]['title'] = '_MI_SIMPLECART_PERM_ORDER_DELETE';
$modversion['permissions'][$i]['description'] = '_MI_SIMPLECART_PERM_ORDER_DELETE_DESC';
$i++;

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
