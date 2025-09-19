<?php
include_once dirname(__FILE__) . '/../../../include/cp_header.php';
include_once dirname(__FILE__) . '/../include/common.php';

global $icmsModule;
if (!is_object($icmsModule) || $icmsModule->getVar('dirname') !== 'simplecart') {
    $module_handler = icms::handler('icms_module');
    $icmsModule = $module_handler->getByDirname('simplecart');
}

icms_loadLanguageFile('simplecart', 'admin');
?>
