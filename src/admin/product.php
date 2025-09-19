<?php
include_once __DIR__ . '/header.php';

$icms_product_handler = simplecart_getHandler('product');
$clean_op = isset($_REQUEST['op']) ? preg_replace('/[^a-z_]/', '', $_REQUEST['op']) : 'list';
$product_id = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;

function simplecart_admin_edit_product($product_id = 0) {
    global $icms_product_handler, $icmsAdminTpl;
    $obj = $product_id ? $icms_product_handler->get($product_id) : $icms_product_handler->create();
    icms_cp_header();
    $icmsAdminTpl->assign('simplecart_product_form', $obj->getForm(_AM_SIMPLECART_PRODUCT_FORM, 'addproduct', 'product.php', _SUBMIT)->render());
    $icmsAdminTpl->display('db:simplecart_admin_product.html');
    icms_cp_footer();
}

switch ($clean_op) {
    case 'mod':
        simplecart_admin_edit_product($product_id);
        break;

    case 'addproduct':
        $controller = new icms_ipf_Controller($icms_product_handler);
        $controller->storeFromDefaultForm(_AM_SIMPLECART_PRODUCT_CREATED, _AM_SIMPLECART_PRODUCT_UPDATED, 'product.php');
        break;

    case 'del':
        $controller = new icms_ipf_Controller($icms_product_handler);
        $controller->handleObjectDeletion(_AM_SIMPLECART_PRODUCT_DELETE_CONFIRM);
        break;

    default:
        icms_cp_header();
        global $icmsAdminTpl;
        $objectTable = new icms_ipf_view_Table($icms_product_handler);
        $objectTable->addColumn(new icms_ipf_view_Column('name', _GLOBAL_LEFT, 200));
        $objectTable->addColumn(new icms_ipf_view_Column('price', 'center', 100));
        $objectTable->addColumn(new icms_ipf_view_Column('active', 'center', 60));
        $objectTable->addIntroButton('addproduct', 'product.php?op=mod', _AM_SIMPLECART_PRODUCT_CREATE);
        $objectTable->addQuickSearch(array('name', 'description'));
        $icmsAdminTpl->assign('simplecart_product_table', $objectTable->fetch());
        $icmsAdminTpl->display('db:simplecart_admin_product.html');
        icms_cp_footer();
        break;
}
?>
