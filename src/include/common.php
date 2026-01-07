<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

if (!defined('SIMPLECART_DIRNAME')) {
    define('SIMPLECART_DIRNAME', basename(dirname(__DIR__)));
    define('SIMPLECART_URL', ICMS_URL . '/modules/' . SIMPLECART_DIRNAME . '/');
    define('SIMPLECART_ROOT_PATH', ICMS_ROOT_PATH . '/modules/' . SIMPLECART_DIRNAME . '/');
    define('SIMPLECART_VERSION', '0.08'); // Module version for cache busting
}

icms_loadLanguageFile('simplecart', 'main');
icms_loadLanguageFile('simplecart', 'admin');
icms_loadLanguageFile('simplecart', 'modinfo');

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
    try {
        // Load email classes
        if (!class_exists('OrderConfirmationEmail')) {
            require_once SIMPLECART_ROOT_PATH . 'class/OrderConfirmationEmail.php';
        }

        if (!class_exists('EmailSender')) {
            require_once SIMPLECART_ROOT_PATH . 'class/EmailSender.php';
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
        $emailTemplate = new OrderConfirmationEmail($order, $orderItems, $sepaConfig, $currency);

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
