<?php
include_once dirname(__DIR__, 2) . '/mainfile.php';
include_once __DIR__ . '/include/common.php';

$xoopsOption['template_main'] = 'simplecart_checkout.html.tpl';
include ICMS_ROOT_PATH . '/header.php';

$customer = array(
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'helpendehanden' => '',
);
$checkoutError = '';
$checkoutSuccess = false;
$checkoutMessage = '';
$paymentInfo = array();
$orderDetails = array(
    'order_id' => 0,
    'customer' => array(),
    'items' => array(),
    'total' => 0.0,
    'total_formatted' => '',
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $customer = array(
        'name' => trim((string)($_POST['customer_name'] ?? '')),
        'email' => trim((string)($_POST['customer_email'] ?? '')),
        'phone' => trim((string)($_POST['customer_phone'] ?? '')),
        'address' => trim((string)($_POST['customer_address'] ?? '')),
        'helpendehanden' => trim((string)($_POST['helpendehanden'] ?? '')),
    );

    $token = isset($_POST['simplecart_token']) ? (string)$_POST['simplecart_token'] : '';
    if (empty($customer['name']) || empty($customer['email'])) {
        $checkoutError = 'Name and email are required.';
    } elseif (empty($token) || !icms::$security->check(true, $token, 'simplecart_checkout')) {
        $checkoutError = _MD_SIMPLECART_CSRF_FAIL;
    } else {
        try {
            $cart = simplecart_getCart();
            if (empty($cart)) {
                throw new Exception(_MD_SIMPLECART_EMPTY_CART);
            }

            $result = simplecart_placeOrderFromCustomerAndItems($customer, $cart);
            simplecart_emptyCart();
            $redirectUrl = SIMPLECART_URL . 'checkout.php?success=1&order_id=' . (int)$result['order_id'];
            header('Location: ' . $redirectUrl);
            exit;
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
        $paymentInfo = simplecart_getOrderPaymentData($orderId);
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

            $customerInfoJson = (string)$order->getVar('customer_info', 'n');
            $decodedCustomerInfo = json_decode($customerInfoJson, true);
            if (is_array($decodedCustomerInfo)) {
                $orderDetails['customer'] = $decodedCustomerInfo;
            }

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

$cartSummary = simplecart_getCartSummary();
$icmsTpl->assign('simplecart_module_url', SIMPLECART_URL);
$icmsTpl->assign('simplecart_order_token', icms::$security->createToken(3600, 'simplecart_checkout'));
$icmsTpl->assign('simplecart_customer', $customer);
$icmsTpl->assign('simplecart_error_message', $checkoutError);
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

