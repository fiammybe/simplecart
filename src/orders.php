<?php
include_once dirname(__DIR__, 2) . '/mainfile.php';
include_once __DIR__ . '/include/common.php';

/**
 * @return string An error message, or an empty string when access may be granted
 */
function simplecart_validateOrderAccessRequest(SimplecartOrderHandler $orderHandler, string $email, string $code, string $token): string
{
    if ($token === '') {
        return _MD_SIMPLECART_ACCESS_SESSION_EXPIRED;
    }

    if (!icms::$security->check(true, $token, 'simplecart_order_access')) {
        return _MD_SIMPLECART_ACCESS_SESSION_EXPIRED;
    }

    if (simplecart_isOrderAccessLocked()) {
        return _MD_SIMPLECART_ACCESS_TOO_MANY_ATTEMPTS;
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL) && $orderHandler->isValidAccessCodeForEmail($email, $code)) {
        return '';
    }

    simplecart_registerFailedOrderAccess();

    return _MD_SIMPLECART_ACCESS_INVALID;
}

$op = isset($_POST['op']) ? (string)$_POST['op'] : 'index';
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$orderHandler = simplecart_getHandler('order');
$accessError = '';
$submittedEmail = '';

if ($isPost && $op === 'destroy') {
    simplecart_revokeOrderAccess();
    header('Location: ' . SIMPLECART_URL . 'orders.php');
    exit;
}

if ($isPost && $op === 'store') {
    $submittedEmail = trim((string)($_POST['email'] ?? ''));
    $code = (string)($_POST['access_code'] ?? '');
    $token = (string)($_POST['token'] ?? '');
    $accessError = simplecart_validateOrderAccessRequest($orderHandler, $submittedEmail, $code, $token);

    if ($accessError === '') {
        simplecart_grantOrderAccess($submittedEmail);
        header('Location: ' . SIMPLECART_URL . 'orders.php');
        exit;
    }
}

$xoopsOption['template_main'] = 'simplecart_customer_orders.html.tpl';
include ICMS_ROOT_PATH . '/header.php';

$icmsTpl->assign('simplecart_module_url', SIMPLECART_URL);

$accessEmail = simplecart_getOrderAccessEmail();

if ($accessEmail === null) {
    $icmsTpl->assign('simplecart_access_error', $accessError);
    $icmsTpl->assign('simplecart_access_email', $submittedEmail);
    $icmsTpl->assign('simplecart_access_token', icms::$security->createToken(3600, 'simplecart_order_access'));
    include ICMS_ROOT_PATH . '/footer.php';
    exit;
}

$currency = strtoupper((string)simplecart_getSepaConfig('currency', 'EUR'));
$statusLabels = $orderHandler->getStatusArray();
$orders = [];

foreach ($orderHandler->getOrdersByEmail($accessEmail) as $order) {
    $orderId = (int)$order->getVar('order_id');
    $status = (string)$order->getVar('status', 'n');
    $itemRows = simplecart_getOrderItemRows($orderId);

    $orders[] = [
        'order_id' => $orderId,
        'date' => (string)$order->getVar('timestamp'),
        'status' => $statusLabels[$status] ?? $status,
        'customer_name' => (string)$order->getVar('customer_name', 'n'),
        'items' => $itemRows['items'],
        'grand_total_fmt' => $itemRows['grand_total_fmt'],
    ];
}

$icmsTpl->assign('simplecart_access_heading', sprintf(_MD_SIMPLECART_ACCESS_LOGGED_IN_AS, $accessEmail));
$icmsTpl->assign('simplecart_orders', $orders);
$icmsTpl->assign('simplecart_currency', $currency);

include ICMS_ROOT_PATH . '/footer.php';
