<?php
// File: src/handler.php
// Provides module handlers for ImpressCMS

if (!defined('XOOPS_ROOT_PATH')) {
    die('Direct access to this script is not allowed.');
}

function simplecart_getHandler($className, $modName)
{
    global $icmsConfig;

    $handlerFile = ICMS_ROOT_PATH . "/modules/{$modName}/class/{$className}.php";

    if (file_exists($handlerFile)) {
        require_once $handlerFile;

        return icms_getModuleHandler($className, $modName);
    } else {
        // Try the old location
        $handlerFile = ICMS_ROOT_PATH . "/modules/{$modName}/{$className}.php";
        if (file_exists($handlerFile)) {
            require_once $handlerFile;
            return icms_getModuleHandler($className, $modName);
        } else {
            return false;
        }
    }
}
