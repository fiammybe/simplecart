<?php
include_once dirname(__DIR__, 2) . '/mainfile.php';
include_once __DIR__ . '/include/common.php';
header('Content-Type: application/json; charset=utf-8');

$action = isset($_REQUEST['action']) ? strtolower(preg_replace('/[^a-z_]/', '', $_REQUEST['action'])) : '';

try {
    switch ($action) {
        case 'products':
            $productHandler = simplecart_getHandler('product');
            $criteria = new icms_db_criteria_Compo();
            $criteria->add(new icms_db_criteria_Item('active', 1));
            $criteria->setSort('name');
            $criteria->setOrder('ASC');
            // Return objects to use ->getVar(); do not request array rows
            $products = $productHandler->getObjects($criteria, false, true);
            $list = array();
            foreach ($products as $p) {
                $list[] = array(
                    'id' => (int)$p->getVar('product_id'),
                    'name' => (string)$p->getVar('name'),
                    'price' => (float)$p->getVar('price'),
                    'description' => (string)$p->getVar('description'),
                );
            }
            echo json_encode(array('ok' => true, 'products' => $list));
            break;

        case 'token':
            $token = icms::$security->createToken(0, 'simplecart');
            echo json_encode(array('ok' => true, 'token' => $token, 'token_name' => 'simplecart'));
            break;

        case 'place_order':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $raw = file_get_contents('php://input');
            $payload = json_decode($raw, true);
            if (!is_array($payload)) { throw new Exception('Invalid JSON'); }

            $token = isset($payload['token']) ? $payload['token'] : '';
            if (!icms::$security->check(true, $token, 'simplecart')) {
                throw new Exception(_MD_SIMPLECART_CSRF_FAIL);
            }

            $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : array();
            $customer = isset($payload['customer']) && is_array($payload['customer']) ? $payload['customer'] : array();
            if (empty($items)) { throw new Exception(_MD_SIMPLECART_EMPTY_CART); }

            $productHandler = simplecart_getHandler('product');
            $orderHandler = simplecart_getHandler('order');
            $orderItemHandler = simplecart_getHandler('orderitem');

            $order = $orderHandler->create();
            $order->setVar('status', 'pending');
            $infoParts = array();
            foreach (array('name','email','phone','address') as $k) {
                if (!empty($customer[$k])) { $infoParts[] = ucfirst($k) . ': ' . icms_core_DataFilter::htmlSpecialChars($customer[$k]); }
            }
            $order->setVar('customer_info', implode("\n", $infoParts));
            $order->setVar('total_amount', 0.0);
            if (!$orderHandler->insert($order, true)) {
                throw new Exception(_MD_SIMPLECART_ORDER_CREATE_FAIL);
            }
            $orderId = (int)$order->getVar('order_id');

            $total = 0.0;
            foreach ($items as $it) {
                $pid = isset($it['product_id']) ? (int)$it['product_id'] : 0;
                $qty = isset($it['quantity']) ? (int)$it['quantity'] : 0;
                if ($pid <= 0 || $qty <= 0) { continue; }
                $prod = $productHandler->get($pid);
                if (!$prod || $prod->isNew() || (int)$prod->getVar('active') !== 1) { continue; }
                $price = (float)$prod->getVar('price');
                $name = (string)$prod->getVar('name');

                $item = $orderItemHandler->create();
                $item->setVar('order_id', $orderId);
                $item->setVar('product_name', $name);
                $item->setVar('product_price', $price);
                $item->setVar('quantity', $qty);
                if (!$orderItemHandler->insert($item, true)) {
                    throw new Exception(_MD_SIMPLECART_ORDERITEM_CREATE_FAIL);
                }
                $total += $qty * $price;
            }

            $order->setVar('total_amount', $total);
            $orderHandler->insert($order, true);

            echo json_encode(array('ok' => true, 'order_id' => $orderId, 'total' => $total));
            break;

        default:
            echo json_encode(array('ok' => false, 'error' => 'Unknown action'));
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => $e->getMessage()));
}

