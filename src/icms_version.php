<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

$modversion = array();
$modversion['name'] = _MI_SIMPLECART_NAME;
$modversion['version'] = 1.00;
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

// Configs (none for now)

?>
