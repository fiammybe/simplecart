<?php
include_once __DIR__ . '/header.php';

$icms_product_handler = simplecart_getHandler('product');
$clean_op = isset($_REQUEST['op']) ? preg_replace('/[^a-z_]/', '', $_REQUEST['op']) : 'list';
$product_id = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;

function simplecart_admin_edit_product($product_id = 0) {
    global $icms_product_handler, $icmsAdminTpl;
    
    // Check permission for create or edit
    $permission = $product_id ? 'simplecart_product_edit' : 'simplecart_product_create';
    simplecart_checkPermission($permission, 'product.php', _NOPERM);
    
    $obj = $product_id ? $icms_product_handler->get($product_id) : $icms_product_handler->create();
    icms_cp_header();
    $icmsAdminTpl->assign('simplecart_product_form', $obj->getForm(_AM_SIMPLECART_PRODUCT_FORM, 'addproduct', 'product.php', _SUBMIT)->render());
    $icmsAdminTpl->display('db:simplecart_admin_product.html.tpl');
    icms_cp_footer();
}
switch ($clean_op) {
    case 'mod':
        // Permission check is done in simplecart_admin_edit_product
        icms_cp_header();
        icms::$module->displayAdminMenu(0, 'SimpleCart');
        simplecart_admin_edit_product($product_id);
        icms_cp_footer();
        break;

    case 'addproduct':
        // Check permission for create or edit
        $permission = $product_id ? 'simplecart_product_edit' : 'simplecart_product_create';
        simplecart_checkPermission($permission, 'product.php', _NOPERM);
        
        icms_cp_header();
        icms::$module->displayAdminMenu(0, 'SimpleCart');
        $controller = new icms_ipf_Controller($icms_product_handler);
        $controller->storeFromDefaultForm(_AM_SIMPLECART_PRODUCT_CREATED, _AM_SIMPLECART_PRODUCT_UPDATED, 'product.php');
        icms_cp_footer();
        break;

    case 'del':
        // Check delete permission
        simplecart_checkPermission('simplecart_product_delete', 'product.php', _NOPERM);
        
        icms_cp_header();
        icms::$module->displayAdminMenu(0, 'SimpleCart');
        $controller = new icms_ipf_Controller($icms_product_handler);
        $controller->handleObjectDeletion(_AM_SIMPLECART_PRODUCT_DELETE_CONFIRM);
        icms_cp_footer();
        break;

    default:
        // Check view permission
        simplecart_checkPermission('simplecart_product_view', '../index.php', _NOPERM);
        
        icms_cp_header();
        icms::$module->displayAdminMenu(0, 'SimpleCart');
        global $icmsAdminTpl;
        $objectTable = new icms_ipf_view_Table($icms_product_handler);
        $objectTable->addColumn(new icms_ipf_view_Column('name', _GLOBAL_LEFT, 200));
        $objectTable->addColumn(new icms_ipf_view_Column('price', 'center', 100));
        $objectTable->addColumn(new icms_ipf_view_Column('active', 'center', 60));
        
        // Only show create button if user has create permission
        if (simplecart_hasPermission('simplecart_product_create')) {
            $objectTable->addIntroButton('addproduct', 'product.php?op=mod', _AM_SIMPLECART_PRODUCT_CREATE);
        }
        
        $objectTable->addQuickSearch(array('name', 'description'));
        
        // Check if user has edit or delete permissions to show action buttons
        $hasEdit = simplecart_hasPermission('simplecart_product_edit');
        $hasDelete = simplecart_hasPermission('simplecart_product_delete');
        
        // If user doesn't have edit or delete, remove those actions
        if (!$hasEdit && !$hasDelete) {
            // Create table without default actions
            $objectTable = new icms_ipf_view_Table($icms_product_handler, false, array());
            $objectTable->addColumn(new icms_ipf_view_Column('name', _GLOBAL_LEFT, 200));
            $objectTable->addColumn(new icms_ipf_view_Column('price', 'center', 100));
            $objectTable->addColumn(new icms_ipf_view_Column('active', 'center', 60));
            $objectTable->addQuickSearch(array('name', 'description'));
        } elseif (!$hasEdit) {
            // Remove edit action only
            $objectTable = new icms_ipf_view_Table($icms_product_handler, true, array('edit'));
            $objectTable->addColumn(new icms_ipf_view_Column('name', _GLOBAL_LEFT, 200));
            $objectTable->addColumn(new icms_ipf_view_Column('price', 'center', 100));
            $objectTable->addColumn(new icms_ipf_view_Column('active', 'center', 60));
            $objectTable->addQuickSearch(array('name', 'description'));
        } elseif (!$hasDelete) {
            // Remove delete action only
            $objectTable = new icms_ipf_view_Table($icms_product_handler, true, array('delete'));
            $objectTable->addColumn(new icms_ipf_view_Column('name', _GLOBAL_LEFT, 200));
            $objectTable->addColumn(new icms_ipf_view_Column('price', 'center', 100));
            $objectTable->addColumn(new icms_ipf_view_Column('active', 'center', 60));
            $objectTable->addQuickSearch(array('name', 'description'));
        }
        
        $icmsAdminTpl->assign('simplecart_product_table', $objectTable->fetch());
        $icmsAdminTpl->display('db:simplecart_admin_product.html.tpl');
        icms_cp_footer();
        break;
}
