<?php
// File: src/admin/index.php
// Uses ImpressCMS handlers to build $dashboard and includes the template.

include_once __DIR__ . '/header.php';
icms_cp_header();

icms::$module->displayAdminMenu(0, 'SimpleCart');

echo '<h1>' . _MI_SIMPLECART_NAME . '</h1>';

// Handlers (module dirname assumed 'simplecart' — adjust if different)
$moduleDir = 'simplecart';
$orderHandler = icms_getModuleHandler('order', $moduleDir);

// Fetch shifts
// Fetch all orders and group by their 'shift' field (no separate shift handler)
$criteria = new icms_db_criteria_Compo();
$orders = $orderHandler->getObjects($criteria, true);

$dashboardMap = array();
foreach ($orders as $orderObj) {
    $shiftName = trim((string)$orderObj->getVar('shift'));
    $shiftName = $shiftName !== '' ? $shiftName : 'Unassigned';
    $shiftKey = $shiftName; // use the raw shift string as parameter

    if (!isset($dashboardMap[$shiftKey])) {
        $dashboardMap[$shiftKey] = array(
            'shift_key' => $shiftKey,
            'shift_name' => $shiftName,
            'total_orders' => 0,
            'total_amount' => 0.0,
            'paid_orders' => 0,
            'paid_amount' => 0.0,
            'pending_orders' => 0,
            'pending_amount' => 0.0,
        );
    }

   $amount = (float)$orderObj->getVar('total_amount');
   $status = (string)$orderObj->getVar('status');

   $dashboardMap[$shiftKey]['total_orders']++;
   $dashboardMap[$shiftKey]['total_amount'] += $amount;
   if ($status === 'paid') {
       $dashboardMap[$shiftKey]['paid_orders']++;
       $dashboardMap[$shiftKey]['paid_amount'] += $amount;
   } elseif ($status === 'pending') {
       $dashboardMap[$shiftKey]['pending_orders']++;
       $dashboardMap[$shiftKey]['pending_amount'] += $amount;
   }
}

// Convert associative map to indexed array for the template
$dashboard = array_values($dashboardMap);

// Build product sales breakdown by shift
$orderItemHandler = icms_getModuleHandler('orderitem', $moduleDir);
$allOrderItems = $orderItemHandler->getObjects(new icms_db_criteria_Compo(), true);

$productSalesMap = array(); // shift_key => [product_name => {qty, revenue}]

foreach ($allOrderItems as $itemObj) {
    $orderId = (int)$itemObj->getVar('order_id');

    // Find the order to get its shift
    $order = $orderHandler->get($orderId);
    if (!$order || $order->isNew()) {
        continue;
    }

    $shiftName = trim((string)$order->getVar('shift'));
    $shiftName = $shiftName !== '' ? $shiftName : 'Unassigned';
    $shiftKey = $shiftName;

    $productName = (string)$itemObj->getVar('product_name');
    $quantity = (int)$itemObj->getVar('quantity');
    $price = (float)$itemObj->getVar('product_price');
    $revenue = $quantity * $price;

    if (!isset($productSalesMap[$shiftKey])) {
        $productSalesMap[$shiftKey] = array();
    }

    if (!isset($productSalesMap[$shiftKey][$productName])) {
        $productSalesMap[$shiftKey][$productName] = array(
            'product_name' => $productName,
            'total_quantity' => 0,
            'total_revenue' => 0.0,
        );
    }

    $productSalesMap[$shiftKey][$productName]['total_quantity'] += $quantity;
    $productSalesMap[$shiftKey][$productName]['total_revenue'] += $revenue;
}

// Convert to indexed array format for template
$productSalesBreakdown = array();
foreach ($productSalesMap as $shiftKey => $products) {
    $productSalesBreakdown[] = array(
        'shift_key' => $shiftKey,
        'shift_name' => $shiftKey,
        'products' => array_values($products),
    );
}

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
