<?php
include_once __DIR__ . '/header.php';

$icms_order_handler = simplecart_getHandler('order');
$clean_op = isset($_REQUEST['op']) ? preg_replace('/[^a-z_]/', '', $_REQUEST['op']) : 'list';
$order_id = isset($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;

// If an order_id is provided without an explicit op, show the detail view
if ((!isset($_REQUEST['op']) || $clean_op === 'list' || $clean_op === '') && $order_id > 0) {
    $clean_op = 'view';
}

switch ($clean_op) {
    case 'view':
        icms_cp_header();
        global $icmsAdminTpl;
        $obj = $icms_order_handler->get($order_id);
        if ($obj && !$obj->isNew()) {
            $icmsAdminTpl->assign('simplecart_order_heading', _AM_SIMPLECART_ORDER_VIEW . ' #' . (int)$obj->getVar('order_id'));
            $icmsAdminTpl->assign('simplecart_order_single', $obj->displaySingleObject(true, false, array(), true));

            // Fetch order items for this order
            $orderItemHandler = simplecart_getHandler('orderitem');
            $criteria = new icms_db_criteria_Compo();
            $criteria->add(new icms_db_criteria_Item('order_id', (int)$obj->getVar('order_id')));
            $criteria->setSort('orderitem_id');
            $criteria->setOrder('ASC');
            $items = $orderItemHandler->getObjects($criteria, false, true);

            $rows = array();
            $grand = 0.0;
            foreach ($items as $it) {
                $qty = (int)$it->getVar('quantity');
                $price = (float)$it->getVar('product_price');
                $subtotal = $qty * $price;
                $grand += $subtotal;
                $rows[] = array(
                    'product_name' => (string)$it->getVar('product_name'),
                    'product_price_fmt' => number_format($price, 2),
                    'quantity' => $qty,
                    'subtotal_fmt' => number_format($subtotal, 2),
                );
            }
            $icmsAdminTpl->assign('simplecart_order_items', $rows);
            $icmsAdminTpl->assign('simplecart_order_grand_total_fmt', number_format($grand, 2));
        } else {
            $icmsAdminTpl->assign('simplecart_order_error', _AM_SIMPLECART_ORDER_NOT_FOUND);
        }
        $icmsAdminTpl->display('db:simplecart_admin_order.html');
        icms_cp_footer();
        break;

    case 'changestatus':
        // Change order status with CSRF protection
        $status = isset($_REQUEST['status']) ? preg_replace('/[^a-z_]/', '', $_REQUEST['status']) : '';
        $token = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
        if (!icms::$security->check(true, $token, 'simplecart_order_status')) {
            redirect_header('order.php', 3, 'Security token invalid.');
            exit;
        }
        $obj = $icms_order_handler->get($order_id);
        if (!$obj || $obj->isNew()) {
            redirect_header('order.php', 3, 'Order not found.');
            exit;
        }
        // Validate status against handler
        $allowed = array_keys($icms_order_handler->getStatusArray());
        if (!in_array($status, $allowed, true)) {
            redirect_header('order.php', 3, 'Invalid status.');
            exit;
        }
        $obj->setVar('status', $status);
        $icms_order_handler->insert($obj, true);
        redirect_header('order.php', 2, 'Order status updated.');
        exit;

    default:
        icms_cp_header();
        global $icmsAdminTpl;
        // Read-only list: remove default edit/delete actions
        $objectTable = new icms_ipf_view_Table($icms_order_handler, false, array());
        $objectTable->addColumn(new icms_ipf_view_Column('order_id', 'center', 60));
        $objectTable->addColumn(new icms_ipf_view_Column('timestamp', 'center', 160));
        $objectTable->addColumn(new icms_ipf_view_Column('status', 'center', 120));
        $objectTable->addColumn(new icms_ipf_view_Column('total_amount', 'center', 120));
        $objectTable->addCustomAction('getViewItemLink');
        $objectTable->addCustomAction('getStatusActionLinks');
        $icmsAdminTpl->assign('simplecart_order_table', $objectTable->fetch());
        $icmsAdminTpl->display('db:simplecart_admin_order.html');
        icms_cp_footer();
        break;
}
?>
