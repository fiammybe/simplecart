<?php
include_once dirname(__DIR__, 2) . '/mainfile.php';
include_once __DIR__ . '/include/common.php';

$orderId = isset($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;
$token = isset($_REQUEST['token']) ? (string)$_REQUEST['token'] : '';
// Scan links that belong to an add-on (e.g. a ticket) carry an 'addon' parameter and their own parameters
$isAddonScan = isset($_GET['addon']);
$scanUrl = SIMPLECART_URL . 'scan.php?' . http_build_query($isAddonScan ? $_GET : ['order_id' => $orderId, 'token' => $token]);

if (!is_object(icms::$user)) {
    redirect_header(ICMS_URL . '/user.php?xoops_redirect=' . urlencode($scanUrl), 3, _MD_SIMPLECART_SCAN_LOGIN_REQUIRED);
    exit;
}

$moduleId = (int)icms::handler('icms_module')->getByDirname(SIMPLECART_DIRNAME)->getVar('mid');
$isModuleAdmin = icms::$user->isAdmin($moduleId);

if ($isAddonScan) {
    $addon = (string)$_GET['addon'];
    $tokenName = SimplecartExtension::scanTokenName($addon);
    $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

    $scan = SimplecartExtension::resolveScan(array(
        'params' => $_GET,
        'method' => $isPost ? 'POST' : 'GET',
        'action' => $isPost && isset($_POST['scan_action']) ? (string)$_POST['scan_action'] : null,
        'user' => icms::$user,
        'is_admin' => $isModuleAdmin,
        // Only checked on a POST: check() consumes the token
        'token_valid' => $isPost && $isModuleAdmin && icms::$security->check(true, isset($_POST['scan_token']) ? (string)$_POST['scan_token'] : '', $tokenName),
    ));

    $errorMessages = array(
        'no_access' => _MD_SIMPLECART_SCAN_NO_ACCESS,
        'token_invalid' => _MD_SIMPLECART_SCAN_TOKEN_INVALID,
        'action_invalid' => _MD_SIMPLECART_SCAN_ACTION_INVALID,
        'invalid' => _MD_SIMPLECART_SCAN_INVALID,
    );

    if ($isPost) {
        // Post/redirect/get: reloading the page cannot repeat the action
        if ($scan['error'] !== null) {
            redirect_header($scanUrl, 3, $errorMessages[$scan['error']]);
        } else {
            $message = $scan['scan']['message'] !== '' ? $scan['scan']['message'] : $scan['scan']['title'];
            // ImpressCMS prints the redirect message as HTML; the add-on's text is plain text
            redirect_header($scanUrl, 2, $message !== '' ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : _MD_SIMPLECART_SCAN_ACTION_DONE);
        }
        exit;
    }

    $xoopsOption['template_main'] = 'simplecart_order_scan.html.tpl';
    include ICMS_ROOT_PATH . '/header.php';

    if ($scan['error'] !== null) {
        $icmsTpl->assign('simplecart_scan_error', $errorMessages[$scan['error']]);
    } else {
        $scanExtension = $scan['scan'];
        if (!empty($scanExtension['actions'])) {
            $scanExtension['form_action'] = $scanUrl;
            $scanExtension['token'] = icms::$security->createToken(3600, $tokenName);
        }
        $icmsTpl->assign('simplecart_scan_extension', $scanExtension);
    }

    $icmsTpl->assign('simplecart_module_url', SIMPLECART_URL);
    include ICMS_ROOT_PATH . '/footer.php';
    exit;
}

$orderHandler = simplecart_getHandler('order');
$order = $orderId > 0 ? $orderHandler->get($orderId) : null;
$isValidOrder = $order && !$order->isNew() && $order->hasValidScanToken($token);

// Add-ons render their part of the scan page and may forbid closing (allow_close = false), e.g. for an order with seats
$renderResults = array();
if ($isValidOrder && $isModuleAdmin) {
    $renderResults = SimplecartExtension::dispatch(SimplecartExtension::EVENT_RENDER, array(
        'order' => $order,
        'context' => 'scan',
    ))->getResults();
}
$allowsClose = SimplecartExtension::scanAllowsClose($renderResults);

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

    if (!$allowsClose) {
        redirect_header($scanUrl, 3, _MD_SIMPLECART_SCAN_CLOSE_NOT_ALLOWED);
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

if ($status === 'paid' && $allowsClose) {
    $icmsTpl->assign('simplecart_scan_close_form', [
        'action' => $scanUrl,
        'close_token' => icms::$security->createToken(3600, 'simplecart_order_scan'),
    ]);
}

$itemRows = $order->getItemRows();

$scanBlocks = array();
foreach ($renderResults as $result) {
    $html = trim((string)($result['html'] ?? ''));
    if ($html !== '') {
        $scanBlocks[] = $html;
    }
}
$icmsTpl->assign('simplecart_extension_blocks', $scanBlocks);

$icmsTpl->assign('simplecart_module_url', SIMPLECART_URL);
$icmsTpl->assign('simplecart_scan_order', [
    'order_id' => $orderId,
    'date' => (string)$order->getVar('timestamp'),
    'status' => $statusLabels[$status] ?? $status,
    'status_key' => $status,
    'customer_name' => (string)$order->getVar('customer_name', 'n'),
    'customer_email' => (string)$order->getVar('customer_email', 'n'),
    'answers' => $order->getAnswerHtmlRows(),
    'items' => $itemRows['items'],
    'item_count' => array_sum(array_column($itemRows['items'], 'quantity')),
    'grand_total_fmt' => $itemRows['grand_total_fmt'],
    'currency' => strtoupper((string)simplecart_getSepaConfig('currency', 'EUR')),
    'admin_url' => SIMPLECART_URL . "admin/order.php?op=view&order_id={$orderId}",
]);

include ICMS_ROOT_PATH . '/footer.php';
