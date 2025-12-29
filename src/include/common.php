<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

if (!defined('SIMPLECART_DIRNAME')) {
    define('SIMPLECART_DIRNAME', basename(dirname(__DIR__)));
    define('SIMPLECART_URL', ICMS_URL . '/modules/' . SIMPLECART_DIRNAME . '/');
    define('SIMPLECART_ROOT_PATH', ICMS_ROOT_PATH . '/modules/' . SIMPLECART_DIRNAME . '/');
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
 * Get SEPA payment configuration
 *
 * @param string $key Configuration key (beneficiary_name, beneficiary_iban, beneficiary_bic, currency)
 * @param mixed $default Default value if configuration is not set
 * @return mixed Configuration value
 */
function simplecart_getSepaConfig($key = null, $default = null) {
    static $sepaConfig = null;

    if ($sepaConfig === null) {
        // Get module configuration by loading the module object with config
        $moduleHandler = icms::handler('icms_module');
        $module = $moduleHandler->getByDirname('simplecart', true);

        $moduleConfig = array();
        if ($module && isset($module->config) && is_array($module->config)) {
            $moduleConfig = $module->config;
        }

        // Debug logging
        error_log('=== simplecart_getSepaConfig DEBUG ===');
        error_log('module object exists: ' . ($module ? 'true' : 'false'));
        error_log('module->config exists: ' . (isset($module->config) ? 'true' : 'false'));
        error_log('moduleConfig type: ' . gettype($moduleConfig));
        error_log('moduleConfig is_array: ' . (is_array($moduleConfig) ? 'true' : 'false'));

        if (is_array($moduleConfig)) {
            error_log('moduleConfig keys: ' . implode(', ', array_keys($moduleConfig)));
            error_log('sepa_beneficiary_name: ' . (isset($moduleConfig['sepa_beneficiary_name']) ? $moduleConfig['sepa_beneficiary_name'] : 'NOT SET'));
            error_log('sepa_beneficiary_iban: ' . (isset($moduleConfig['sepa_beneficiary_iban']) ? $moduleConfig['sepa_beneficiary_iban'] : 'NOT SET'));
            error_log('sepa_beneficiary_bic: ' . (isset($moduleConfig['sepa_beneficiary_bic']) ? $moduleConfig['sepa_beneficiary_bic'] : 'NOT SET'));
            error_log('sepa_currency: ' . (isset($moduleConfig['sepa_currency']) ? $moduleConfig['sepa_currency'] : 'NOT SET'));
        } else {
            error_log('moduleConfig is not an array! Value: ' . var_export($moduleConfig, true));
        }

        $sepaConfig = array(
            'beneficiary_name' => isset($moduleConfig['sepa_beneficiary_name']) ? $moduleConfig['sepa_beneficiary_name'] : 'SimpleCart Shop',
            'beneficiary_iban' => isset($moduleConfig['sepa_beneficiary_iban']) ? $moduleConfig['sepa_beneficiary_iban'] : '',
            'beneficiary_bic' => isset($moduleConfig['sepa_beneficiary_bic']) ? $moduleConfig['sepa_beneficiary_bic'] : '',
            'currency' => isset($moduleConfig['sepa_currency']) ? $moduleConfig['sepa_currency'] : 'EUR'
        );

        error_log('Final sepaConfig: ' . json_encode($sepaConfig));
        error_log('=== END DEBUG ===');
    }

    if ($key === null) {
        return $sepaConfig;
    }

    return isset($sepaConfig[$key]) ? $sepaConfig[$key] : $default;
}

?>
