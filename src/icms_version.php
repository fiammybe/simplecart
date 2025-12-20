<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

$modversion = array();
$modversion['name'] = _MI_SIMPLECART_NAME;
$modversion['version'] = '0.05';
$modversion['description'] = _MI_SIMPLECART_DESC;
$modversion['author'] = 'fiammybe';
$modversion['credits'] = 'ImpressCMS, IPF, Augment Agent';
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

// Configs
$modversion['config'][] = array(
    'name' => 'beneficiary_name',
    'title' => '_MI_SIMPLECART_CONF_BENEFICIARY',
    'description' => '_MI_SIMPLECART_CONF_BENEFICIARY_DESC',
    'formtype' => 'textbox',
    'valuetype' => 'text',
    'default' => ''
);
$modversion['config'][] = array(
    'name' => 'iban',
    'title' => '_MI_SIMPLECART_CONF_IBAN',
    'description' => '_MI_SIMPLECART_CONF_IBAN_DESC',
    'formtype' => 'textbox',
    'valuetype' => 'text',
    'default' => ''
);
$modversion['config'][] = array(
    'name' => 'bic',
    'title' => '_MI_SIMPLECART_CONF_BIC',
    'description' => '_MI_SIMPLECART_CONF_BIC_DESC',
    'formtype' => 'textbox',
    'valuetype' => 'text',
    'default' => ''
);
$modversion['config'][] = array(
    'name' => 'ref_prefix',
    'title' => '_MI_SIMPLECART_CONF_REF_PREFIX',
    'description' => '_MI_SIMPLECART_CONF_REF_PREFIX_DESC',
    'formtype' => 'textbox',
    'valuetype' => 'text',
    'default' => 'ORD-'
);
