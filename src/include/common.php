<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

if (!defined('SIMPLECART_DIRNAME')) {
    define('SIMPLECART_DIRNAME', basename(dirname(__DIR__)));
    define('SIMPLECART_URL', ICMS_URL . '/modules/' . SIMPLECART_DIRNAME . '/');
    define('SIMPLECART_ROOT_PATH', ICMS_ROOT_PATH . '/modules/' . SIMPLECART_DIRNAME . '/');
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

    if (!simplecart_getHandler('product')->getActiveProduct($productId)) {
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
    $list = array();

    foreach (simplecart_getHandler('product')->getProducts((bool)$onlyActive) as $product) {
        $row = $product->toListRow();
        $list[$row['product_id']] = $row;
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
