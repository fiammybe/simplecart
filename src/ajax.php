<?php
ob_start();
include_once dirname(__DIR__, 2) . '/mainfile.php';
include_once __DIR__ . '/include/common.php';
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

use SepaQr\Data;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

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
            if (empty($raw)) { throw new Exception('Empty request body'); }
            $payload = json_decode($raw, true);
            if (!is_array($payload)) { throw new Exception('Invalid JSON: ' . json_last_error_msg()); }

            $token = $payload['token'] ?? '';
            if (!icms::$security->check(true, $token, 'simplecart')) {
                throw new Exception(_MD_SIMPLECART_CSRF_FAIL);
            }

            $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
            $customer = is_array($payload['customer'] ?? null) ? $payload['customer'] : [];
            if (empty($items)) { throw new Exception(_MD_SIMPLECART_EMPTY_CART); }

            $productHandler = simplecart_getHandler('product');
            $orderHandler = simplecart_getHandler('order');
            $orderItemHandler = simplecart_getHandler('orderitem');

            $order = $orderHandler->create();
            $order->setVar('status', 'pending');
            
            // Build customer_info from base fields using fluent approach
            $baseFields = ['name', 'email', 'phone', 'address'];
            $infoParts = array_filter(array_map(function($k) use ($customer) {
                return !empty($customer[$k]) 
                    ? ucfirst($k) . ': ' . icms_core_DataFilter::htmlSpecialChars($customer[$k])
                    : null;
            }, $baseFields));
            
            $order->setVar('customer_info', implode("\n", $infoParts))
                  ->setVar('total_amount', 0.0);

            // Dynamically set order fields from customer data
            $checkoutFields = $order->getCheckoutFields();
            array_walk($checkoutFields, function($fieldDef, $fieldName) use ($order, $customer) {
                if (isset($customer[$fieldName])) {
                    $order->setVar($fieldName, icms_core_DataFilter::htmlSpecialChars($customer[$fieldName]));
                }
            });

            // Insert the order
            if (!$orderHandler->insert($order, true)) {
                $errors = $order->getErrors();
                $errorMsg = !empty($errors) ? implode(', ', $errors) : _MD_SIMPLECART_ORDER_CREATE_FAIL;
                throw new Exception($errorMsg);
            }
            $orderId = (int)$order->getVar('order_id');

            // Generate payment reference for display (not stored in DB yet)
            $reference = 'ORD-' . str_pad((string)$orderId, 6, '0', STR_PAD_LEFT);

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

            // Generate SEPA QR Code if configured
            $qrCodeDataUri = null;

            // Get module configuration for SEPA settings
            $moduleHandler = icms::handler('icms_module');
            $module = $moduleHandler->getByDirname('simplecart');
            $configHandler = icms::handler('icms_config');
            $config = $configHandler->getConfigsByCat(0, $module->getVar('mid'));

            if (!empty($config['beneficiary_name']) && !empty($config['iban'])) {
                try {
                    $paymentData = Data::create()
                        ->setName($config['beneficiary_name'])
                        ->setIban($config['iban'])
                        ->setAmount($total);

                    if (!empty($config['bic'])) {
                        $paymentData->setBic($config['bic']);
                    }

                    $paymentData->setRemittanceText($reference); // Use the generated reference

                    $result = Builder::create()
                        ->writer(new PngWriter())
                        ->writerOptions([])
                        ->data($paymentData->toString())
                        ->encoding(new Encoding('UTF-8'))
                        ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                        ->size(300)
                        ->margin(10)
                        ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                        ->build();

                    $qrCodeDataUri = $result->getDataUri();
                } catch (Exception $e) {
                    // Log error or ignore if QR generation fails, so order still succeeds
                }
            }

            echo json_encode(array('ok' => true, 'order_id' => $orderId, 'total' => $total, 'qr_code' => $qrCodeDataUri));
            break;

        default:
            echo json_encode(array('ok' => false, 'error' => 'Unknown action'));
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => $e->getMessage()));
}
exit;
