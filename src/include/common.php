<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

if (!defined('SIMPLECART_DIRNAME')) {
    define('SIMPLECART_DIRNAME', basename(dirname(__DIR__)));
    define('SIMPLECART_URL', ICMS_URL . '/modules/' . SIMPLECART_DIRNAME . '/');
    define('SIMPLECART_ROOT_PATH', ICMS_ROOT_PATH . '/modules/' . SIMPLECART_DIRNAME . '/');
//    define('SIMPLECART_VERSION', '0.08'); // Module version for cache busting
    define('SIMPLECART_DEBUG_EMAIL', false); // Set to true to enable email debug logging
    define('SIMPLECART_DEBUG_LOG_FILE', ICMS_TRUST_PATH . '/logs/debug_email.log');
}

icms_loadLanguageFile('simplecart', 'main');
icms_loadLanguageFile('simplecart', 'admin');
icms_loadLanguageFile('simplecart', 'modinfo');

/**
 * Debug logging for email sending process
 * Writes to file-based log to avoid breaking AJAX responses
 * Only logs when SIMPLECART_DEBUG_EMAIL is enabled
 *
 * @param string $message The message to log
 * @return void
 */
function simplecart_debugLog($message) {
    if (!defined('SIMPLECART_DEBUG_EMAIL') || !SIMPLECART_DEBUG_EMAIL) {
        return;
    }

    try {
        $timestamp = date('Y-m-d H:i:s');
        // Security: Sanitize message to prevent log injection
        $message = preg_replace('/[\r\n]+/', ' ', $message);
        $logMessage = "[{$timestamp}] [SIMPLECART EMAIL DEBUG] {$message}\n";

        // Ensure log file directory exists and is writable
        $logDir = dirname(SIMPLECART_DEBUG_LOG_FILE);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // Security: Ensure log file has restricted permissions
        $logFile = SIMPLECART_DEBUG_LOG_FILE;
        if (!file_exists($logFile)) {
            @touch($logFile);
            @chmod($logFile, 0640);
        }

        // Append to log file with file locking
        @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        // Silently fail - don't break the email sending process
        // Don't log the error to avoid infinite loops
    }
}

function simplecart_getHandler($name) {
    static $handlers = array();
    $name = strtolower($name);
    if (!isset($handlers[$name])) {
        // Correct parameter order: (name, module_dir, module_basename = null, optional = false)
        $handlers[$name] = icms_getModuleHandler($name, 'simplecart');
    }
    return $handlers[$name];
}

function simplecart_sessionStart() {
    if (function_exists('session_status')) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    } elseif (!isset($_SESSION)) {
        @session_start();
    }
}

function simplecart_formatMoney($amount, $currency = 'EUR') {
    $amount = (float)$amount;
    $formatted = number_format($amount, 2, ',', '.');

    if (strtoupper($currency) === 'EUR') {
        return '€ ' . $formatted;
    }

    return strtoupper($currency) . ' ' . $formatted;
}

function simplecart_getCart() {
    simplecart_sessionStart();
    $cart = isset($_SESSION['simplecart_cart']) && is_array($_SESSION['simplecart_cart']) ? $_SESSION['simplecart_cart'] : array();
    $normalized = array();

    foreach ($cart as $productId => $item) {
        if (!is_array($item)) {
            continue;
        }

        $id = isset($item['product_id']) ? (int)$item['product_id'] : (int)$productId;
        $quantity = isset($item['quantity']) ? max(1, (int)$item['quantity']) : 1;
        $normalized[$id] = array(
            'product_id' => $id,
            'quantity' => $quantity,
        );
    }

    $_SESSION['simplecart_cart'] = $normalized;
    return $normalized;
}

function simplecart_saveCart($cart) {
    simplecart_sessionStart();
    $normalized = array();

    if (is_array($cart)) {
        foreach ($cart as $productId => $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = isset($item['product_id']) ? (int)$item['product_id'] : (int)$productId;
            $quantity = isset($item['quantity']) ? max(1, (int)$item['quantity']) : 1;
            $normalized[$id] = array(
                'product_id' => $id,
                'quantity' => $quantity,
            );
        }
    }

    $_SESSION['simplecart_cart'] = $normalized;
    return $normalized;
}

function simplecart_emptyCart() {
    return simplecart_saveCart(array());
}

function simplecart_addProductToCart($productId, $quantity = 1) {
    $productId = (int)$productId;
    $quantity = max(1, (int)$quantity);

    if ($productId <= 0) {
        return false;
    }

    $productHandler = simplecart_getHandler('product');
    $product = $productHandler->get($productId);
    if (!$product || $product->isNew() || (int)$product->getVar('active') !== 1) {
        return false;
    }

    $cart = simplecart_getCart();
    $existing = isset($cart[$productId]['quantity']) ? (int)$cart[$productId]['quantity'] : 0;
    $cart[$productId] = array(
        'product_id' => $productId,
        'quantity' => min(1000, $existing + $quantity),
    );

    simplecart_saveCart($cart);
    return true;
}

function simplecart_updateCartItemQuantity($productId, $quantity) {
    $productId = (int)$productId;
    $quantity = (int)$quantity;

    if ($productId <= 0) {
        return false;
    }

    $cart = simplecart_getCart();
    if ($quantity <= 0) {
        unset($cart[$productId]);
        simplecart_saveCart($cart);
        return true;
    }

    $cart[$productId] = array(
        'product_id' => $productId,
        'quantity' => min(1000, max(1, $quantity)),
    );

    simplecart_saveCart($cart);
    return true;
}

function simplecart_removeFromCart($productId) {
    $cart = simplecart_getCart();
    unset($cart[(int)$productId]);
    simplecart_saveCart($cart);
    return true;
}

function simplecart_getProductList($onlyActive = true) {
    $productHandler = simplecart_getHandler('product');
    $criteria = new icms_db_criteria_Compo();
    if ($onlyActive) {
        $criteria->add(new icms_db_criteria_Item('active', 1));
    }
    $criteria->setSort('name');
    $criteria->setOrder('ASC');
    $products = $productHandler->getObjects($criteria, false, true);
    $list = array();

    foreach ($products as $product) {
        $productId = (int)$product->getVar('product_id');
        $list[$productId] = array(
            'product_id' => $productId,
            'name' => (string)$product->getVar('name'),
            'price' => (float)$product->getVar('price'),
            'description' => (string)$product->getVar('description'),
            'price_formatted' => simplecart_formatMoney((float)$product->getVar('price')),
        );
    }

    return $list;
}

function simplecart_getCartSummary() {
    $cart = simplecart_getCart();
    $products = simplecart_getProductList(true);
    $items = array();
    $total = 0.0;
    $count = 0;

    foreach ($cart as $productId => $item) {
        $productId = isset($item['product_id']) ? (int)$item['product_id'] : (int)$productId;
        if ($productId <= 0 || !isset($products[$productId])) {
            continue;
        }

        $quantity = max(1, (int)($item['quantity'] ?? 1));
        $product = $products[$productId];
        $price = (float)$product['price'];
        $subtotal = $price * $quantity;
        $total += $subtotal;
        $count += $quantity;

        $items[] = array(
            'product_id' => $productId,
            'name' => $product['name'],
            'price' => $price,
            'price_formatted' => simplecart_formatMoney($price),
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'subtotal_formatted' => simplecart_formatMoney($subtotal),
        );
    }

    return array(
        'items' => $items,
        'total' => $total,
        'count' => $count,
        'total_formatted' => simplecart_formatMoney($total),
    );
}

function simplecart_getOrderPaymentData($orderId) {
    try {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            throw new InvalidArgumentException('Invalid order ID');
        }

        $orderHandler = simplecart_getHandler('order');
        $order = $orderHandler->get($orderId);
        if (!$order || $order->isNew()) {
            throw new RuntimeException('Order ' . $orderId . ' was not found');
        }

        $config = simplecart_getSepaConfig();
        if (empty($config['beneficiary_iban'])) {
            throw new RuntimeException('SEPA beneficiary IBAN is not configured');
        }

        if (!class_exists('SepaQrCodeGenerator')) {
            require_once SIMPLECART_ROOT_PATH . 'class/SepaQrCodeGenerator.php';
        }
        if (!class_exists('SepaQrCodeGenerator')) {
            throw new RuntimeException('SepaQrCodeGenerator class is not available');
        }

        $generator = new SepaQrCodeGenerator($config);
        $amount = (float)$order->getVar('total_amount');
        $qrData = $generator->generateQrData($orderId, $amount, 'Bestelling ' . $orderId);
        $qrImage = '';

        if (class_exists('Endroid\\QrCode\\QrCode') && class_exists('Endroid\\QrCode\\Writer\\PngWriter')) {
            $writer = new Endroid\QrCode\Writer\PngWriter();
            $qrCode = new Endroid\QrCode\QrCode($qrData);
            $result = $writer->write($qrCode);
            $qrImage = 'data:image/png;base64,' . base64_encode($result->getString());
        }

        return array(
            'qr_data' => $qrData,
            'qr_image' => $qrImage,
            'beneficiary_name' => $config['beneficiary_name'],
            'beneficiary_iban' => $config['beneficiary_iban'],
            'amount' => $amount,
            'currency' => strtoupper($config['currency']),
        );
    } catch (Throwable $e) {
        throw new RuntimeException('Failed to generate payment data for order ' . $orderId . ': ' . $e->getMessage(), 0, $e);
    }
}

function simplecart_placeOrderFromCustomerAndItems($customer, $items) {
    try {
        if (!is_array($customer)) {
            $customer = array();
        }
        if (!is_array($items) || empty($items)) {
            throw new Exception(_MD_SIMPLECART_EMPTY_CART);
        }

        $requiredFields = array('name', 'email');
        foreach ($requiredFields as $field) {
            if (empty($customer[$field])) {
                throw new Exception('Required field missing: ' . $field);
            }
        }

        if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address format');
        }

        $maxLengths = array(
            'name' => 100,
            'email' => 255,
            'phone' => 50,
            'address' => 500,
            'helpendehanden' => 50,
        );
        foreach ($maxLengths as $field => $maxLen) {
            if (isset($customer[$field]) && strlen((string)$customer[$field]) > $maxLen) {
                throw new Exception("Field '{$field}' exceeds maximum length of {$maxLen} characters");
            }
        }

        $productHandler = simplecart_getHandler('product');
        $orderHandler = simplecart_getHandler('order');
        $orderItemHandler = simplecart_getHandler('orderitem');

        $order = $orderHandler->create();
        $order->setVar('status', 'pending');
        $order->setVar('timestamp', time());

        $customerData = array();
        foreach (array('name', 'email', 'phone', 'address') as $field) {
            if (!empty($customer[$field])) {
                $customerData[$field] = $customer[$field];
            }
        }
        $customerInfoJson = json_encode($customerData);
        if ($customerInfoJson === false) {
            throw new RuntimeException('Failed to encode customer information');
        }
        $order->setVar('customer_info', $customerInfoJson, 'n');
        $order->setVar('total_amount', 0.0);

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
        $maxQuantityPerItem = 1000;

        foreach ($items as $item) {
            $productId = isset($item['product_id']) ? (int)$item['product_id'] : (isset($item['id']) ? (int)$item['id'] : 0);
            $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            if ($quantity > $maxQuantityPerItem) {
                throw new Exception("Quantity for product ID {$productId} exceeds maximum allowed ({$maxQuantityPerItem})");
            }

            $product = $productHandler->get($productId);
            if (!$product || $product->isNew() || (int)$product->getVar('active') !== 1) {
                continue;
            }

            $price = (float)$product->getVar('price');
            $name = (string)$product->getVar('name');

            $orderItem = $orderItemHandler->create();
            $orderItem->setVar('order_id', $orderId);
            $orderItem->setVar('product_name', $name);
            $orderItem->setVar('product_price', $price);
            $orderItem->setVar('quantity', $quantity);
            if (!$orderItemHandler->insert($orderItem, true)) {
                throw new Exception(_MD_SIMPLECART_ORDERITEM_CREATE_FAIL);
            }

            $total += $quantity * $price;
            $validItemCount++;
        }

        if ($validItemCount === 0) {
            throw new Exception('No valid items in cart. Order cannot be placed.');
        }

        if ($total <= 0) {
            throw new Exception('Order total must be greater than zero');
        }

        $order->setVar('total_amount', $total);
        $orderHandler->insert($order, true);

        $emailResult = simplecart_sendOrderConfirmationEmail($order, $orderId);
        if (defined('SIMPLECART_DEBUG_EMAIL') && SIMPLECART_DEBUG_EMAIL) {
            simplecart_debugLog("Checkout submission email result for order {$orderId}: " . ($emailResult ? 'TRUE' : 'FALSE'));
        }

        return array(
            'order_id' => $orderId,
            'total' => $total,
        );
    } catch (Exception $e) {
        throw $e;
    } catch (Throwable $e) {
        throw new RuntimeException('Failed to place order: ' . $e->getMessage(), 0, $e);
    }
}

/**
 * Get SEPA configuration from module settings
 *
 * @param string $key Optional specific configuration key to retrieve
 * @param mixed $default Default value if key not found
 * @return array|mixed Configuration array or specific value
 * @throws RuntimeException If the SEPA configuration cannot be loaded
 * @throws OutOfBoundsException If a specific key is missing without a fallback value
 */
function simplecart_getModuleConfig($key = null, $default = null) {
    static $config = null;

    if ($config === null) {
        try {
            $moduleHandler = icms::handler('icms_module');
            $module = $moduleHandler->getByDirname('simplecart');

            $config = array(
                'duplicate_order_email_to' => '',
                'sepa_beneficiary_name' => 'SimpleCart Shop',
                'sepa_beneficiary_iban' => '',
                'sepa_beneficiary_bic' => '',
                'sepa_currency' => 'EUR',
            );

            if ($module) {
                $configHandler = icms::handler('icms_config');
                $moduleConfig = $configHandler->getConfigList($module->getVar('mid'), 0);

                foreach ($config as $configKey => $defaultValue) {
                    if (isset($moduleConfig[$configKey])) {
                        $config[$configKey] = $moduleConfig[$configKey];
                    }
                }
            }
        } catch (Throwable $e) {
            throw new RuntimeException('Unable to load module configuration: ' . $e->getMessage(), 0, $e);
        }
    }

    if ($key === null) {
        return $config;
    }

    if (!array_key_exists($key, $config)) {
        if (func_num_args() > 1) {
            return $default;
        }
        throw new OutOfBoundsException("Module config key '{$key}' does not exist.");
    }

    return $config[$key];
}

function simplecart_getDuplicateOrderEmailRecipients() {
    $configuredValue = (string)simplecart_getModuleConfig('duplicate_order_email_to', '');
    $emails = preg_split('/[\s,;]+/', trim($configuredValue), -1, PREG_SPLIT_NO_EMPTY);

    if (empty($emails)) {
        return array();
    }

    $normalized = array();
    foreach ($emails as $email) {
        $email = trim($email);
        if ($email === '') {
            continue;
        }
        $normalized[] = $email;
    }

    return array_values(array_unique($normalized));
}

function simplecart_getSepaConfig($key = null, $default = null) {
    static $config = null;

    if ($config === null) {
        $moduleConfig = simplecart_getModuleConfig();
        $config = array(
            'beneficiary_name' => isset($moduleConfig['sepa_beneficiary_name']) ? $moduleConfig['sepa_beneficiary_name'] : 'SimpleCart Shop',
            'beneficiary_iban' => isset($moduleConfig['sepa_beneficiary_iban']) ? $moduleConfig['sepa_beneficiary_iban'] : '',
            'beneficiary_bic' => isset($moduleConfig['sepa_beneficiary_bic']) ? $moduleConfig['sepa_beneficiary_bic'] : '',
            'currency' => isset($moduleConfig['sepa_currency']) ? $moduleConfig['sepa_currency'] : 'EUR',
        );
    }

    if ($key === null) {
        return $config;
    }

    if (!array_key_exists($key, $config)) {
        if (func_num_args() > 1) {
            return $default;
        }
        throw new OutOfBoundsException("SEPA config key '{$key}' does not exist.");
    }

    return $config[$key];
}

/**
 * Send order confirmation email to customer
 *
 * @param SimplecartOrder $order The order object
 * @param int $orderId The order ID
 * @return bool True if email was sent successfully, false otherwise
 */
function simplecart_sendOrderConfirmationEmail($order, $orderId) {
    simplecart_debugLog("=== START: simplecart_sendOrderConfirmationEmail() called for order ID: {$orderId}");

    try {
        // Load email classes
        simplecart_debugLog("Loading email classes...");
        if (!class_exists('OrderConfirmationEmail')) {
            $orderConfirmationEmailPath = SIMPLECART_ROOT_PATH . 'class/OrderConfirmationEmail.php';
            simplecart_debugLog("Attempting to load OrderConfirmationEmail from: {$orderConfirmationEmailPath}");
            if (!file_exists($orderConfirmationEmailPath)) {
                simplecart_debugLog("ERROR: OrderConfirmationEmail file not found at: {$orderConfirmationEmailPath}");
                return false;
            }
            try {
                require_once $orderConfirmationEmailPath;
            } catch (Exception $e) {
                simplecart_debugLog("ERROR: Exception while loading OrderConfirmationEmail: " . $e->getMessage());
                return false;
            }
            if (!class_exists('OrderConfirmationEmail')) {
                simplecart_debugLog("ERROR: OrderConfirmationEmail class not found after require_once");
                return false;
            }
            simplecart_debugLog("OrderConfirmationEmail class loaded");
        }

        if (!class_exists('EmailSender')) {
            $emailSenderPath = SIMPLECART_ROOT_PATH . 'class/EmailSender.php';
            simplecart_debugLog("Attempting to load EmailSender from: {$emailSenderPath}");
            if (!file_exists($emailSenderPath)) {
                simplecart_debugLog("ERROR: EmailSender file not found at: {$emailSenderPath}");
                return false;
            }
            try {
                require_once $emailSenderPath;
            } catch (Exception $e) {
                simplecart_debugLog("ERROR: Exception while loading EmailSender: " . $e->getMessage());
                return false;
            }
            if (!class_exists('EmailSender')) {
                simplecart_debugLog("ERROR: EmailSender class not found after require_once");
                return false;
            }
            simplecart_debugLog("EmailSender class loaded");
        }

        // Get order items
        simplecart_debugLog("Fetching order items for order ID: {$orderId}");
        $orderItemHandler = simplecart_getHandler('orderitem');
        $criteria = new icms_db_criteria_Compo();
        $criteria->add(new icms_db_criteria_Item('order_id', (int)$orderId));
        $criteria->setSort('orderitem_id');
        $criteria->setOrder('ASC');
        $orderItems = $orderItemHandler->getObjects($criteria, false, true);

        if (empty($orderItems)) {
            simplecart_debugLog("ERROR: No order items found for order ID: {$orderId}");
            simplecart_debugLog("=== END: simplecart_sendOrderConfirmationEmail() - FAILED (no items)");
            return false;
        }

        simplecart_debugLog("Found " . count($orderItems) . " order items");

        // Get SEPA configuration
        simplecart_debugLog("Retrieving SEPA configuration...");
        $sepaConfig = simplecart_getSepaConfig();
        $currency = $sepaConfig['currency'];
        simplecart_debugLog("SEPA config retrieved. Currency: {$currency}");

        // Create email template
        simplecart_debugLog("Creating OrderConfirmationEmail template...");
        $emailTemplate = new OrderConfirmationEmail($order, $orderItems, $sepaConfig, $currency);
        simplecart_debugLog("OrderConfirmationEmail template created successfully");

        $customerEmail = $emailTemplate->getCustomerEmail();
        simplecart_debugLog("Customer email extracted: " . (empty($customerEmail) ? "EMPTY" : $customerEmail));

        if (empty($customerEmail)) {
            simplecart_debugLog("ERROR: Customer email is empty");
            simplecart_debugLog("=== END: simplecart_sendOrderConfirmationEmail() - FAILED (no email)");
            return false;
        }

        // Send email
        simplecart_debugLog("Generating email subject and content...");
        $subject = $emailTemplate->getSubject();
        $textContent = $emailTemplate->getTextContent();
        simplecart_debugLog("Email subject: {$subject}");
        simplecart_debugLog("Email content length: " . strlen($textContent) . " characters");

        simplecart_debugLog("Calling EmailSender::sendTextEmail() with recipient: {$customerEmail}");
        $result = EmailSender::sendTextEmail($customerEmail, $subject, $textContent);

        $duplicateRecipients = simplecart_getDuplicateOrderEmailRecipients();
        $duplicateRecipients = array_values(array_filter(array_map('trim', $duplicateRecipients), function ($email) use ($customerEmail) {
            return $email !== '' && strtolower($email) !== strtolower($customerEmail);
        }));

        foreach ($duplicateRecipients as $duplicateRecipient) {
            if (!filter_var($duplicateRecipient, FILTER_VALIDATE_EMAIL)) {
                if (defined('SIMPLECART_DEBUG_EMAIL') && SIMPLECART_DEBUG_EMAIL) {
                    simplecart_debugLog("Skipping invalid duplicate email recipient for order {$orderId}: {$duplicateRecipient}");
                }
                continue;
            }

            $duplicateResult = EmailSender::sendTextEmail($duplicateRecipient, $subject, $textContent);
            if (defined('SIMPLECART_DEBUG_EMAIL') && SIMPLECART_DEBUG_EMAIL) {
                simplecart_debugLog("Duplicate email result for order {$orderId} to {$duplicateRecipient}: " . ($duplicateResult ? 'TRUE (success)' : 'FALSE (failed)'));
            }
        }

        simplecart_debugLog("EmailSender::sendTextEmail() returned: " . ($result ? "TRUE (success)" : "FALSE (failed)"));
        simplecart_debugLog("=== END: simplecart_sendOrderConfirmationEmail() - " . ($result ? "SUCCESS" : "FAILED"));

        return $result;
    } catch (Exception $e) {
        simplecart_debugLog("EXCEPTION caught: " . $e->getMessage());
        simplecart_debugLog("Exception trace: " . $e->getTraceAsString());
        simplecart_debugLog("=== END: simplecart_sendOrderConfirmationEmail() - EXCEPTION");
        return false;
    }
}

/**
 * Send payment received notification email to customer
 * Triggered when admin changes order status to 'paid'
 *
 * @param SimplecartOrder $order The order object
 * @param int $orderId The order ID
 * @return bool True if email was sent successfully, false otherwise
 */
function simplecart_sendPaymentReceivedEmail($order, $orderId) {
    try {
        // Load email classes
        if (!class_exists('PaymentReceivedEmail')) {
            $paymentReceivedEmailPath = SIMPLECART_ROOT_PATH . 'class/PaymentReceivedEmail.php';
            if (!file_exists($paymentReceivedEmailPath)) {
                return false;
            }
            require_once $paymentReceivedEmailPath;
        }

        if (!class_exists('EmailSender')) {
            $emailSenderPath = SIMPLECART_ROOT_PATH . 'class/EmailSender.php';
            if (!file_exists($emailSenderPath)) {
                return false;
            }
            require_once $emailSenderPath;
        }

        // Get order items
        $orderItemHandler = simplecart_getHandler('orderitem');
        $criteria = new icms_db_criteria_Compo();
        $criteria->add(new icms_db_criteria_Item('order_id', (int)$orderId));
        $criteria->setSort('orderitem_id');
        $criteria->setOrder('ASC');
        $orderItems = $orderItemHandler->getObjects($criteria, false, true);

        if (empty($orderItems)) {
            return false;
        }

        // Get SEPA configuration
        $sepaConfig = simplecart_getSepaConfig();
        $currency = $sepaConfig['currency'];

        // Create email template
        $emailTemplate = new PaymentReceivedEmail($order, $orderItems, $sepaConfig, $currency);

        $customerEmail = $emailTemplate->getCustomerEmail();

        if (empty($customerEmail)) {
            return false;
        }

        // Send email
        $subject = $emailTemplate->getSubject();
        $textContent = $emailTemplate->getTextContent();

        $result = EmailSender::sendTextEmail($customerEmail, $subject, $textContent);

        return $result;
    } catch (Exception $e) {
        return false;
    }
}
