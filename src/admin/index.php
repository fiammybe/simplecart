<?php
// File: src/admin/index.php
// Reads the orders once and hands them to the browser, which filters them and draws the charts.

include_once __DIR__ . '/header.php';
require_once dirname(__DIR__) . '/class/SimplecartDashboardData.php';

icms_cp_header();

icms::$module->displayAdminMenu(0, 'SimpleCart');

echo '<h1>' . _MI_SIMPLECART_NAME . '</h1>';

// The site time zone is an offset from UTC in hours; it decides on which day an order was placed
$timezoneOffset = (int)round((float)($GLOBALS['icmsConfig']['default_TZ'] ?? 0) * 3600);

$orderHandler = simplecart_getHandler('order');
$dashboardData = new SimplecartDashboardData(
    $orderHandler,
    simplecart_getHandler('orderitem'),
    simplecart_getHandler('orderanswer'),
    simplecart_getHandler('question'),
    simplecart_getHandler('campaign'),
    simplecart_getHandler('product'),
    $timezoneOffset
);
$dashboard = $dashboardData->build($orderHandler->getStatusArray(), _AM_SIMPLECART_DASH_UNKNOWN);

// The texts the script writes into the page
$dashboardStrings = array(
    'noOrders' => _AM_SIMPLECART_DASH_NO_ORDERS,
    'campaign' => _AM_SIMPLECART_DASH_CAMPAIGN,
    'product' => _AM_SIMPLECART_DASH_PRODUCT,
    'status' => _AM_SIMPLECART_DASH_STATUS,
    'answers' => _AM_SIMPLECART_DASH_ANSWERS,
    'clear' => _AM_SIMPLECART_DASH_CLEAR,
    'total' => _AM_SIMPLECART_DASH_TOTAL,
    'paid' => _AM_SIMPLECART_DASH_PAID,
    'pending' => _AM_SIMPLECART_DASH_PENDING,
    'orders' => _AM_SIMPLECART_DASH_ORDERS,
    'period' => _AM_SIMPLECART_DASH_PERIOD,
    'mode' => _AM_SIMPLECART_DASH_MODE,
    'day' => _AM_SIMPLECART_DASH_DAY,
    'week' => _AM_SIMPLECART_DASH_WEEK,
    'month' => _AM_SIMPLECART_DASH_MONTH,
    'perPeriod' => _AM_SIMPLECART_DASH_PER_PERIOD,
    'cumulative' => _AM_SIMPLECART_DASH_CUMULATIVE,
    'timezone' => _AM_SIMPLECART_DASH_TIMEZONE,
    'ordersChart' => _AM_SIMPLECART_DASH_ORDERS_CHART,
    'amountChart' => _AM_SIMPLECART_DASH_AMOUNT_CHART,
    'periodHeader' => _AM_SIMPLECART_DASH_PERIOD_HEADER,
    'value' => _AM_SIMPLECART_DASH_VALUE,
    'showTable' => _AM_SIMPLECART_DASH_SHOW_TABLE,
    'productsTitle' => _AM_SIMPLECART_DASH_PRODUCTS,
    'quantity' => _AM_SIMPLECART_DASH_QUANTITY,
);

global $icmsAdminTpl;
if (!isset($icmsAdminTpl) || !is_object($icmsAdminTpl)) {
    // Ensure admin template object exists (ImpressCMS usually provides this)
    if (class_exists('icms_view_Admin')) {
        $icmsAdminTpl = new icms_view_Admin();
    }
}

$icmsAdminTpl->assign('simplecart_module_url', SIMPLECART_URL);
$icmsAdminTpl->assign('simplecart_asset_version', rawurlencode((string)icms::$module->getVar('version')));
$icmsAdminTpl->assign('simplecart_dashboard_json', SimplecartDashboardData::toJson($dashboard));
$icmsAdminTpl->assign('simplecart_dashboard_strings', SimplecartDashboardData::toJson($dashboardStrings));
$icmsAdminTpl->display('db:simplecart_admin_dashboard.html.tpl');

icms_cp_footer();
