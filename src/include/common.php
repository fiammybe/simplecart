<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

if (!defined('SIMPLECART_DIRNAME')) {
    define('SIMPLECART_DIRNAME', basename(dirname(__DIR__)));
    define('SIMPLECART_URL', ICMS_URL . '/modules/' . SIMPLECART_DIRNAME . '/');
    define('SIMPLECART_ROOT_PATH', ICMS_ROOT_PATH . '/modules/' . SIMPLECART_DIRNAME . '/');
    define('SIMPLECART_VERSION', '0.08'); // Module version for cache busting
    define('SIMPLECART_DEBUG_EMAIL', true); // Set to false to disable email debug logging
    define('SIMPLECART_DEBUG_LOG_FILE', SIMPLECART_ROOT_PATH . 'debug_email.log');
}

icms_loadLanguageFile('simplecart', 'main');
icms_loadLanguageFile('simplecart', 'admin');
icms_loadLanguageFile('simplecart', 'modinfo');

/**
 * Debug logging for email sending process
 * Writes to file-based log to avoid breaking AJAX responses
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
        $logMessage = "[{$timestamp}] [SIMPLECART EMAIL DEBUG] {$message}\n";

        // Ensure log file directory exists and is writable
        $logDir = dirname(SIMPLECART_DEBUG_LOG_FILE);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // Append to log file
        @file_put_contents(SIMPLECART_DEBUG_LOG_FILE, $logMessage, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        // Silently fail - don't break the email sending process
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

/**
 * Get SEPA configuration from module settings
 *
 * @param string $key Optional specific configuration key to retrieve
 * @param mixed $default Default value if key not found
 * @return array|mixed Configuration array or specific value
 */
function simplecart_getSepaConfig($key = null, $default = null) {
    static $config = null;

    // Load configuration once
    if ($config === null) {
        // Get module handler and find simplecart module
        $moduleHandler = icms::handler('icms_module');
        $module = $moduleHandler->getByDirname('simplecart');

        if (!$module) {
            // Fallback to defaults if module not found
            $config = array(
                'beneficiary_name' => 'SimpleCart Shop',
                'beneficiary_iban' => '',
                'beneficiary_bic' => '',
                'currency' => 'EUR',
            );
        } else {
            // Get config handler and retrieve module configuration by module ID
            $configHandler = icms::handler('icms_config');
            $moduleConfig = $configHandler->getConfigList($module->getVar('mid'), 0);

            // Build config array from module settings
            $config = array(
                'beneficiary_name' => isset($moduleConfig['sepa_beneficiary_name']) ? $moduleConfig['sepa_beneficiary_name'] : 'SimpleCart Shop',
                'beneficiary_iban' => isset($moduleConfig['sepa_beneficiary_iban']) ? $moduleConfig['sepa_beneficiary_iban'] : '',
                'beneficiary_bic' => isset($moduleConfig['sepa_beneficiary_bic']) ? $moduleConfig['sepa_beneficiary_bic'] : '',
                'currency' => isset($moduleConfig['sepa_currency']) ? $moduleConfig['sepa_currency'] : 'EUR',
            );
        }
    }

    // Return specific key or entire config
    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
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
