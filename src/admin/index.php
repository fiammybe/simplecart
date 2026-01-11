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
$icmsAdminTpl->display('db:simplecart_admin_dashboard.html');

icms_cp_footer();
