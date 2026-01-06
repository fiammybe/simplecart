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
