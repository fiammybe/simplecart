<?php
include_once dirname(__DIR__, 2) . '/mainfile.php';
include_once __DIR__ . '/include/common.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$token = isset($_GET['token']) ? (string)$_GET['token'] : '';

if (!is_object(icms::$user)) {
    $returnUrl = SIMPLECART_URL . 'scan.php?' . http_build_query(['order_id' => $orderId, 'token' => $token]);
    redirect_header(ICMS_URL . '/user.php?xoops_redirect=' . urlencode($returnUrl), 3, _MD_SIMPLECART_SCAN_LOGIN_REQUIRED);
    exit;
}

$xoopsOption['template_main'] = 'simplecart_order_scan.html.tpl';
include ICMS_ROOT_PATH . '/header.php';

$moduleId = (int)icms::handler('icms_module')->getByDirname(SIMPLECART_DIRNAME)->getVar('mid');

if (!icms::$user->isAdmin($moduleId)) {
    $icmsTpl->assign('simplecart_scan_error', _MD_SIMPLECART_SCAN_NO_ACCESS);
    include ICMS_ROOT_PATH . '/footer.php';
    exit;
}

$orderHandler = simplecart_getHandler('order');
$order = $orderId > 0 ? $orderHandler->get($orderId) : null;

if (!$order || $order->isNew() || !$order->hasValidScanToken($token)) {
    $icmsTpl->assign('simplecart_scan_error', _MD_SIMPLECART_SCAN_INVALID);
    include ICMS_ROOT_PATH . '/footer.php';
    exit;
}

$isFirstScan = $orderHandler->markScanned($orderId, (int)icms::$user->getVar('uid'));
$order = $orderHandler->get($orderId);

$icmsTpl->assign('simplecart_scan_first', $isFirstScan);
$icmsTpl->assign(
    'simplecart_scan_message',
    $isFirstScan ?
        _MD_SIMPLECART_SCAN_PROCESSED :
        sprintf(_MD_SIMPLECART_SCAN_ALREADY_PROCESSED, $order->getScannedAtFormatted(), $order->getScannedByName())
);

$status = (string)$order->getVar('status', 'n');
$statusLabels = $orderHandler->getStatusArray();

if ($status !== 'paid') {
    $icmsTpl->assign('simplecart_scan_status_warning', _MD_SIMPLECART_SCAN_NOT_PAID);
}

$itemRows = simplecart_getOrderItemRows($orderId);

$icmsTpl->assign('simplecart_scan_order', [
    'order_id' => $orderId,
    'date' => (string)$order->getVar('timestamp'),
    'status' => $statusLabels[$status] ?? $status,
    'customer_name' => (string)$order->getVar('customer_name', 'n'),
    'customer_email' => (string)$order->getVar('customer_email', 'n'),
    'customer_phone' => (string)$order->getVar('customer_phone', 'n'),
    'items' => $itemRows['items'],
    'grand_total_fmt' => $itemRows['grand_total_fmt'],
    'currency' => strtoupper((string)simplecart_getSepaConfig('currency', 'EUR')),
    'admin_url' => SIMPLECART_URL . "admin/order.php?op=view&order_id={$orderId}",
]);

include ICMS_ROOT_PATH . '/footer.php';
