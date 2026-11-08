<?php
include_once dirname(__DIR__, 2) . '/mainfile.php';
include_once __DIR__ . '/include/common.php';

$xoopsOption['template_main'] = 'simplecart_checkout.html.tpl';
include ICMS_ROOT_PATH . '/header.php';

$customer = array(
    'name' => '',
    'email' => '',
);
$answerValues = array();
$questionErrors = array();
$checkoutError = '';
$removedItems = simplecart_pruneUnavailableCartItems();
$checkoutSuccess = false;
$checkoutMessage = '';
$paymentInfo = array();
$orderDetails = array(
    'order_id' => 0,
    'customer' => array(),
    'items' => array(),
    'answers' => array(),
    'total' => 0.0,
    'total_formatted' => '',
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $customer = array(
        'name' => trim((string)($_POST['customer_name'] ?? '')),
        'email' => trim((string)($_POST['customer_email'] ?? '')),
        'language' => SimplecartLanguage::current(),
        'answers' => array(),
    );

    foreach ($_POST as $field => $value) {
        if (preg_match('/^q_\d+$/', (string)$field) && is_scalar($value)) {
            $customer['answers'][$field] = trim((string)$value);
        }
    }

    foreach ($customer['answers'] as $field => $value) {
        $answerValues[(int)substr($field, 2)] = $value;
    }

    $token = isset($_POST['simplecart_token']) ? (string)$_POST['simplecart_token'] : '';
    if (empty($customer['name']) || empty($customer['email'])) {
        $checkoutError = 'Name and email are required.';
    } elseif (empty($token) || !icms::$security->check(true, $token, 'simplecart_checkout')) {
        $checkoutError = _MD_SIMPLECART_CSRF_FAIL;
    } else {
        try {
            if (!empty($removedItems)) {
                throw new Exception(_MD_SIMPLECART_ITEMS_REMOVED . ' ' . implode(', ', $removedItems));
            }

            // placeOrder() refuses an empty order, with or without add-on lines
            $cart = simplecart_getCart();

            $result = simplecart_getHandler('order')->placeOrder(is_array($customer) ? $customer : array(), $cart);
            simplecart_emptyCart('order_placed');
            // Add-on output on the success page is only shown to the visitor who placed the order
            simplecart_sessionStart();
            $_SESSION['simplecart_last_order_id'] = (int)$result['order_id'];
            $redirectUrl = SIMPLECART_URL . 'checkout.php?success=1&order_id=' . (int)$result['order_id'];
            header('Location: ' . $redirectUrl);
            exit;
        } catch (SimplecartAnswersException $e) {
            $questionErrors = $e->getResult()->errors;
            $checkoutError = implode(' ', $questionErrors);
        } catch (Exception $e) {
            $checkoutError = $e->getMessage();
        }
    }
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!empty($_GET['success']) && $orderId > 0) {
    $checkoutSuccess = true;
    $checkoutMessage = _MD_SIMPLECART_ORDER_SUCCESS . ' #' . $orderId;
    $orderDetails['order_id'] = $orderId;

    try {
        $paymentOrder = simplecart_getHandler('order')->get($orderId);
        if (!$paymentOrder || $paymentOrder->isNew()) {
            throw new RuntimeException("Order {$orderId} was not found");
        }

        $paymentInfo = $paymentOrder->getPaymentData();
        $orderDetails['total'] = (float)($paymentInfo['amount'] ?? 0.0);
        $orderDetails['total_formatted'] = simplecart_formatMoney($orderDetails['total'], strtoupper((string)($paymentInfo['currency'] ?? 'EUR')));
    } catch (Exception $e) {
        $paymentInfo = array();
    }

    try {
        $orderHandler = simplecart_getHandler('order');
        $order = $orderHandler->get($orderId);
        if ($order && !$order->isNew()) {
            $orderDetails['total'] = (float)$order->getVar('total_amount');
            $orderDetails['total_formatted'] = simplecart_formatMoney($orderDetails['total'], 'EUR');

            $orderDetails['customer'] = array(
                'name' => (string)$order->getVar('customer_name', 'n'),
                'email' => (string)$order->getVar('customer_email', 'n'),
            );
            $orderDetails['answers'] = $order->getAnswerHtmlRows();

            $orderItemHandler = simplecart_getHandler('orderitem');
            $criteria = new icms_db_criteria_Compo();
            $criteria->add(new icms_db_criteria_Item('order_id', $orderId));
            $criteria->setSort('orderitem_id');
            $criteria->setOrder('ASC');
            $orderItems = $orderItemHandler->getObjects($criteria, false, true);

            foreach ($orderItems as $item) {
                $quantity = (int)$item->getVar('quantity');
                $price = (float)$item->getVar('product_price');
                $subtotal = $quantity * $price;

                $orderDetails['items'][] = array(
                    'name' => (string)$item->getVar('product_name'),
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'price_formatted' => simplecart_formatMoney($price),
                    'subtotal_formatted' => simplecart_formatMoney($subtotal),
                );
            }
        }
    } catch (Throwable $e) {
        $orderDetails['items'] = array();
        $orderDetails['customer'] = array();
    }
}

$extensionBlocks = array();
simplecart_sessionStart();
$isOwnOrder = $orderId > 0 && (int)($_SESSION['simplecart_last_order_id'] ?? 0) === $orderId;
if ($checkoutSuccess && $isOwnOrder && isset($order) && $order && !$order->isNew()) {
    $renderEvent = SimplecartExtension::dispatch(SimplecartExtension::EVENT_RENDER, array(
        'order' => $order,
        'context' => 'page',
    ));
    foreach ($renderEvent->getResults() as $result) {
        $html = trim((string)($result['html'] ?? ''));
        if ($html !== '') {
            $extensionBlocks[] = $html;
        }
    }
}

$cartSummary = simplecart_getCartSummary();
$questionRows = $checkoutSuccess ?
    array() :
    simplecart_getHandler('question')->getFormRows(simplecart_getCart(), $answerValues, $questionErrors, SimplecartLanguage::current());
$icmsTpl->assign('simplecart_questions', $questionRows);
$icmsTpl->assign('simplecart_extension_blocks', $extensionBlocks);
$icmsTpl->assign('simplecart_module_url', SIMPLECART_URL);
$icmsTpl->assign('simplecart_order_token', icms::$security->createToken(3600, 'simplecart_checkout'));
$icmsTpl->assign('simplecart_customer', $customer);
$icmsTpl->assign('simplecart_error_message', $checkoutError);
$icmsTpl->assign('simplecart_removed_items', $removedItems);
$icmsTpl->assign('simplecart_order_success', $checkoutSuccess);
$icmsTpl->assign('simplecart_order_success_message', $checkoutMessage);
$icmsTpl->assign('simplecart_order_id', $orderId);
$icmsTpl->assign('simplecart_order_details', $orderDetails);
$icmsTpl->assign('simplecart_cart_items', $cartSummary['items']);
$icmsTpl->assign('simplecart_cart_total', $cartSummary['total']);
$icmsTpl->assign('simplecart_cart_total_formatted', $cartSummary['total_formatted']);
$icmsTpl->assign('simplecart_payment', $paymentInfo);
$icmsTpl->assign('simplecart_order_total_formatted', $orderDetails['total_formatted']);

include ICMS_ROOT_PATH . '/footer.php';

