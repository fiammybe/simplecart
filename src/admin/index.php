<?php
// File: src/admin/index.php
// Uses ImpressCMS handlers to build $dashboard and includes the template.

include_once __DIR__ . '/header.php';

// Check if user has permission to view orders (dashboard displays order data)
simplecart_checkPermission('simplecart_order_view', '../index.php', _NOPERM);

icms_cp_header();

icms::$module->displayAdminMenu(0, 'SimpleCart');

echo '<h1>' . _MI_SIMPLECART_NAME . '</h1>';

// Handlers (module dirname assumed 'simplecart' — adjust if different)
$moduleDir = 'simplecart';
$orderHandler = icms_getModuleHandler('order', $moduleDir);

$criteria = new icms_db_criteria_Compo();
$orders = $orderHandler->getObjects($criteria, true);

$dashboard = array(
   array(
       'group_key' => 'all_orders',
       'group_name' => 'Overview',
       'total_orders' => 0,
       'total_amount' => 0.0,
       'paid_orders' => 0,
       'paid_amount' => 0.0,
       'pending_orders' => 0,
       'pending_amount' => 0.0,
   )
);

foreach ($orders as $orderObj) {
   $amount = (float)$orderObj->getVar('total_amount');
   $status = (string)$orderObj->getVar('status');

   $dashboard[0]['total_orders']++;
   $dashboard[0]['total_amount'] += $amount;
   if ($status === 'paid') {
       $dashboard[0]['paid_orders']++;
       $dashboard[0]['paid_amount'] += $amount;
   } elseif ($status === 'pending') {
       $dashboard[0]['pending_orders']++;
       $dashboard[0]['pending_amount'] += $amount;
   }
}

$orderItemHandler = icms_getModuleHandler('orderitem', $moduleDir);
$allOrderItems = $orderItemHandler->getObjects(new icms_db_criteria_Compo(), true);

$productSalesMap = array();
foreach ($allOrderItems as $itemObj) {
   $orderId = (int)$itemObj->getVar('order_id');
   $order = $orderHandler->get($orderId);
   if (!$order || $order->isNew()) {
       continue;
   }

   $productName = (string)$itemObj->getVar('product_name');
   $quantity = (int)$itemObj->getVar('quantity');
   $price = (float)$itemObj->getVar('product_price');
   $revenue = $quantity * $price;

   if (!isset($productSalesMap[$productName])) {
       $productSalesMap[$productName] = array(
           'product_name' => $productName,
           'total_quantity' => 0,
           'total_revenue' => 0.0,
       );
   }

   $productSalesMap[$productName]['total_quantity'] += $quantity;
   $productSalesMap[$productName]['total_revenue'] += $revenue;
}

$productSalesBreakdown = array(
   array(
       'group_key' => 'all_orders',
       'group_name' => 'Overview',
       'products' => array_values($productSalesMap),
   )
);

// Include template (presentation logic only)
global $icmsAdminTpl;
if (!isset($icmsAdminTpl) || !is_object($icmsAdminTpl)) {
    // Ensure admin template object exists (ImpressCMS usually provides this)
    // If not available, fall back to creating a new admin view instance
    if (class_exists('icms_view_Admin')) {
        $icmsAdminTpl = new icms_view_Admin();
    }
}
$icmsAdminTpl->assign('dashboard', $dashboard);
$icmsAdminTpl->assign('productSalesBreakdown', $productSalesBreakdown);
$icmsAdminTpl->display('db:simplecart_admin_dashboard.html');

icms_cp_footer();
