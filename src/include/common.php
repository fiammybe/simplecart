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
        $sepaConfig = array(
            'beneficiary_name' => icms::$config['simplecart']['sepa_beneficiary_name'] ?? 'SimpleCart Shop',
            'beneficiary_iban' => icms::$config['simplecart']['sepa_beneficiary_iban'] ?? '',
            'beneficiary_bic' => icms::$config['simplecart']['sepa_beneficiary_bic'] ?? '',
            'currency' => icms::$config['simplecart']['sepa_currency'] ?? 'EUR'
        );
    }

    if ($key === null) {
        return $sepaConfig;
    }

    return isset($sepaConfig[$key]) ? $sepaConfig[$key] : $default;
}

?>
