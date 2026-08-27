<?php
// Start output buffering to prevent any accidental output before JSON
ob_start();

// mainfile.php must be included FIRST to define ICMS_ROOT_PATH
include_once dirname(__DIR__, 2) . '/mainfile.php';
// common.php can now safely use ICMS_ROOT_PATH since it's defined by mainfile.php
include_once __DIR__ . '/include/common.php';

/**
 * Simple rate limiting implementation for order placement
 * @param string $identifier Client identifier (IP address or session)
 * @param int $maxRequests Maximum requests allowed
 * @param int $timeWindow Time window in seconds
 * @return bool True if rate limit not exceeded, false otherwise
 */
function simplecart_checkRateLimit($identifier, $maxRequests = 5, $timeWindow = 300): bool
{
    $cacheKey = 'simplecart_ratelimit_' . md5($identifier);
    $cacheHandler = icms::handler('icms_cache');

    $attempts = $cacheHandler->read($cacheKey);
    if ($attempts === false) {
        $attempts = array();
    }

    // Clean old attempts outside time window
    $now = time();
    $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });

    // Check if limit exceeded
    if (count($attempts) >= $maxRequests) {
        return false;
    }

    // Add current attempt
    $attempts[] = $now;
    $cacheHandler->write($cacheKey, $attempts, $timeWindow);

    return true;
}

// Clear any output that may have been generated
ob_end_clean();

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
// Add Referrer-Policy for CSRF token protection
header('Referrer-Policy: strict-origin-when-cross-origin');

$action = isset($_REQUEST['action']) ? strtolower(preg_replace('/[^a-z_]/', '', $_REQUEST['action'])) : '';

try {
    switch ($action) {
        case 'products':
            $productHandler = simplecart_getHandler('product');
            $criteria = new icms_db_criteria_Compo();
            $criteria->add(new icms_db_criteria_Item('active', 1));
            $criteria->setSort('name', icms_db_criteria_Order::ASC);
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
            // Create token with 1 hour (3600 seconds) expiry for security
            $token = icms::$security->createToken(3600, 'simplecart');
            echo json_encode(array(
                'ok' => true,
                'token' => $token,
                'token_name' => 'simplecart'
            ));
            break;

        case 'place_order':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }

            // Security: Rate limiting - 5 orders per 5 minutes per IP
            $clientIdentifier = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
            if (!simplecart_checkRateLimit($clientIdentifier, 5, 300)) {
                http_response_code(429);
                throw new Exception('Rate limit exceeded. Please try again later.');
            }

            $raw = file_get_contents('php://input');
            $payload = json_decode($raw, true);
            if (!is_array($payload)) { throw new Exception('Invalid JSON'); }

            $token = isset($payload['token']) ? $payload['token'] : '';
            $tokenValid = icms::$security->check(true, $token, 'simplecart');
            if (!$tokenValid) {
                throw new Exception(_MD_SIMPLECART_CSRF_FAIL);
            }

            $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : array();
            $customer = isset($payload['customer']) && is_array($payload['customer']) ? $payload['customer'] : array();
            if (empty($items)) { throw new Exception(_MD_SIMPLECART_EMPTY_CART); }

            // Security: Validate customer input lengths and format
            $requiredFields = array('name', 'email');
            foreach ($requiredFields as $field) {
                if (empty($customer[$field])) {
                    throw new Exception('Required field missing: ' . $field);
                }
            }

            // Validate and sanitize customer data
            if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address format');
            }

            // Enforce length constraints
            $maxLengths = array(
                'name' => 100,
                'email' => 255,
                'phone' => 50,
                'address' => 500,
                'tablePreference' => 100,
                'shift' => 50,
                'helpendehanden' => 50
            );
            foreach ($maxLengths as $field => $maxLen) {
                if (isset($customer[$field]) && strlen($customer[$field]) > $maxLen) {
                    throw new Exception("Field '{$field}' exceeds maximum length of {$maxLen} characters");
                }
            }

            $productHandler = simplecart_getHandler('product');
            $orderHandler = simplecart_getHandler('order');
            $orderItemHandler = simplecart_getHandler('orderitem');

            $order = $orderHandler->create();
            $order->setVar('status', 'pending');
            $order->setVar('timestamp', time());

            // Store customer data as JSON for better data integrity and easier parsing
            $customerData = array();
            foreach (array('name','email','phone','address','tablePreference') as $k) {
                if (!empty($customer[$k])) {
                    $customerData[$k] = $customer[$k];
                }
            }
            $customerInfoJson = json_encode($customerData);
            if (defined('SIMPLECART_DEBUG_EMAIL') && SIMPLECART_DEBUG_EMAIL) {
                simplecart_debugLog("ajax.php: Storing customer_info as JSON: " . $customerInfoJson);
            }
            // Use 'n' format to store raw JSON without HTML encoding
            $order->setVar('customer_info', $customerInfoJson, 'n');
            $order->setVar('total_amount', 0.0);

            // Set shift and helpende_hand fields
            if (!empty($customer['shift'])) {
                $order->setVar('shift', icms_core_DataFilter::htmlSpecialChars($customer['shift']));
            }
            if (!empty($customer['helpendehanden'])) {
                $order->setVar('helpende_hand', icms_core_DataFilter::htmlSpecialChars($customer['helpendehanden']));
            }

            if (!$orderHandler->insert($order, true)) {
                $errors = $order->getErrors();
                $errorMsg = !empty($errors) ? implode(', ', $errors) : _MD_SIMPLECART_ORDER_CREATE_FAIL;
                throw new Exception($errorMsg);
            }
            $orderId = (int)$order->getVar('order_id');

            $total = 0.0;
            $validItemCount = 0;
            $maxQuantityPerItem = 1000; // Security: Enforce max quantity limit

            foreach ($items as $it) {
                $pid = isset($it['product_id']) ? (int)$it['product_id'] : 0;
                $qty = isset($it['quantity']) ? (int)$it['quantity'] : 0;
                if ($pid <= 0 || $qty <= 0) { continue; }

                // Security: Enforce quantity upper bound
                if ($qty > $maxQuantityPerItem) {
                    throw new Exception("Quantity for product ID {$pid} exceeds maximum allowed ({$maxQuantityPerItem})");
                }

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
                $validItemCount++;
            }

            // Security: Ensure at least one valid item was added
            if ($validItemCount === 0) {
                throw new Exception('No valid items in cart. Order cannot be placed.');
            }

            // Security: Ensure total is not zero
            if ($total <= 0) {
                throw new Exception('Order total must be greater than zero');
            }

            $order->setVar('total_amount', $total);
            $orderHandler->insert($order, true);

            // Send confirmation email
            if (defined('SIMPLECART_DEBUG_EMAIL') && SIMPLECART_DEBUG_EMAIL) {
                simplecart_debugLog("ajax.php: About to call simplecart_sendOrderConfirmationEmail() for order ID: {$orderId}");
            }
            $emailResult = simplecart_sendOrderConfirmationEmail($order, $orderId);
            if (defined('SIMPLECART_DEBUG_EMAIL') && SIMPLECART_DEBUG_EMAIL) {
                simplecart_debugLog("ajax.php: simplecart_sendOrderConfirmationEmail() returned: " . ($emailResult ? "TRUE" : "FALSE"));
            }

            echo json_encode(array('ok' => true, 'order_id' => $orderId, 'total' => $total), JSON_THROW_ON_ERROR);
            break;

        case 'sepa_qr_data':
            $order_id = isset($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;
            if ($order_id <= 0) {
                throw new Exception('Invalid order ID');
            }

            $orderHandler = simplecart_getHandler('order');
            $order = $orderHandler->get($order_id);
            if (!$order || $order->isNew()) {
                // Security: Add delay to prevent timing-based order enumeration
                usleep(100000); // 100ms delay
                throw new Exception('Order not found');
            }

            // Security: Verify order was recently created (within last 24 hours)
            // This prevents old orders from being queried indefinitely
            $orderTimestamp = (int)$order->getVar('timestamp');
            $hoursSinceCreation = (time() - $orderTimestamp) / 3600;
            if ($hoursSinceCreation > 24) {
                throw new Exception('QR code generation expired. Please contact support for payment details.');
            }

            // Get configuration from module settings using helper function
            $config = simplecart_getSepaConfig();

            // Validate that IBAN is configured
            if (empty($config['beneficiary_iban'])) {
                throw new Exception('SEPA payment is not configured. Please configure IBAN in module settings.');
            }


            // Load SEPA QR Code Generator
            if (!class_exists('SepaQrCodeGenerator')) {
                require_once __DIR__ . '/class/SepaQrCodeGenerator.php';
            }

            $generator = new SepaQrCodeGenerator($config);
            $amount = (float)$order->getVar('total_amount');
            $orderId = (int)$order->getVar('order_id');

            try {
                $orderReference = 'Bestelling ' . $orderId;
                $qrData = $generator->generateQrData($orderId, $amount, $orderReference);
                echo json_encode(array(
                    'ok' => true,
                    'qr_data' => $qrData,
                    'beneficiary_name' => $config['beneficiary_name'],
                    'beneficiary_iban' => $config['beneficiary_iban'],
                    'amount' => $amount
                ));
            } catch (Exception $e) {
                throw new Exception('Failed to generate SEPA QR data: ' . $e->getMessage());
            }
            break;

        default:
            echo json_encode(array('ok' => false, 'error' => 'Unknown action'));
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => $e->getMessage()));
}

