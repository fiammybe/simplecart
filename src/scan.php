<?php
include_once dirname(__DIR__, 2) . '/mainfile.php';
include_once __DIR__ . '/include/common.php';

$orderId = isset($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;
$token = isset($_REQUEST['token']) ? (string)$_REQUEST['token'] : '';
$scanUrl = SIMPLECART_URL . 'scan.php?' . http_build_query(['order_id' => $orderId, 'token' => $token]);

if (!is_object(icms::$user)) {
    redirect_header(ICMS_URL . '/user.php?xoops_redirect=' . urlencode($scanUrl), 3, _MD_SIMPLECART_SCAN_LOGIN_REQUIRED);
    exit;
}

$moduleId = (int)icms::handler('icms_module')->getByDirname(SIMPLECART_DIRNAME)->getVar('mid');
$isModuleAdmin = icms::$user->isAdmin($moduleId);
$orderHandler = simplecart_getHandler('order');
$order = $orderId > 0 ? $orderHandler->get($orderId) : null;
$isValidOrder = $order && !$order->isNew() && $order->hasValidScanToken($token);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isModuleAdmin) {
        redirect_header($scanUrl, 3, _MD_SIMPLECART_SCAN_NO_ACCESS);
        exit;
    }

    if (!$isValidOrder) {
        redirect_header($scanUrl, 3, _MD_SIMPLECART_SCAN_INVALID);
        exit;
    }

    $closeToken = isset($_POST['close_token']) ? (string)$_POST['close_token'] : '';

    if (!icms::$security->check(true, $closeToken, 'simplecart_order_scan')) {
        redirect_header($scanUrl, 3, _MD_SIMPLECART_SCAN_TOKEN_INVALID);
        exit;
    }

    $isClosed = $orderHandler->closeScannedOrder($orderId, (int)icms::$user->getVar('uid'));

    redirect_header($scanUrl, 2, $isClosed ? _MD_SIMPLECART_SCAN_CLOSED : _MD_SIMPLECART_SCAN_CLOSE_FAILED);
    exit;
}

$xoopsOption['template_main'] = 'simplecart_order_scan.html.tpl';
include ICMS_ROOT_PATH . '/header.php';

if (!$isModuleAdmin) {
    $icmsTpl->assign('simplecart_scan_error', _MD_SIMPLECART_SCAN_NO_ACCESS);
    include ICMS_ROOT_PATH . '/footer.php';
    exit;
}

if (!$isValidOrder) {
    $icmsTpl->assign('simplecart_scan_error', _MD_SIMPLECART_SCAN_INVALID);
    include ICMS_ROOT_PATH . '/footer.php';
    exit;
}

$status = (string)$order->getVar('status', 'n');
$statusLabels = $orderHandler->getStatusArray();

if ($status === 'closed') {
    $icmsTpl->assign(
        'simplecart_scan_closed_message',
        $order->isScanned() ?
            sprintf(_MD_SIMPLECART_SCAN_ALREADY_CLOSED, $order->getScannedAtFormatted(), $order->getScannedByName()) :
            _MD_SIMPLECART_SCAN_ALREADY_CLOSED_ADMIN
    );
}

if (!in_array($status, ['paid', 'closed'], true)) {
    $icmsTpl->assign('simplecart_scan_status_warning', _MD_SIMPLECART_SCAN_NOT_PAID);
}

if ($status === 'paid') {
    $icmsTpl->assign('simplecart_scan_close_form', [
        'action' => $scanUrl,
        'close_token' => icms::$security->createToken(3600, 'simplecart_order_scan'),
    ]);
}

$itemRows = simplecart_getOrderItemRows($orderId);
$customerPhone = (string)$order->getVar('customer_phone', 'n');

$icmsTpl->assign('simplecart_module_url', SIMPLECART_URL);
$icmsTpl->assign('simplecart_scan_order', [
    'order_id' => $orderId,
    'date' => (string)$order->getVar('timestamp'),
    'status' => $statusLabels[$status] ?? $status,
    'status_key' => $status,
    'customer_name' => (string)$order->getVar('customer_name', 'n'),
    'customer_email' => (string)$order->getVar('customer_email', 'n'),
    'customer_phone' => $customerPhone,
    'customer_phone_href' => preg_replace('/[^0-9+]/', '', $customerPhone),
    'items' => $itemRows['items'],
    'item_count' => array_sum(array_column($itemRows['items'], 'quantity')),
    'grand_total_fmt' => $itemRows['grand_total_fmt'],
    'currency' => strtoupper((string)simplecart_getSepaConfig('currency', 'EUR')),
    'admin_url' => SIMPLECART_URL . "admin/order.php?op=view&order_id={$orderId}",
]);

include ICMS_ROOT_PATH . '/footer.php';
